<?php
/**
 * 문서 삭제 처리 페이지
 * 
 * 이 페이지는 고객 문서를 삭제하는 기능을 제공합니다.
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
checkPageAccess('customer_management');

// POST 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // POST가 아닌 요청은 문서 목록 페이지로 리다이렉트
    header('Location: customer-list.php');
    exit;
}

// CSRF 토큰 검증
validateCsrfToken($_POST['csrf_token']);

// 필수 파라미터 확인
if (!isset($_POST['document_id']) || !isset($_POST['customer_id'])) {
    // 필수 파라미터가 없는 경우 문서 목록 페이지로 리다이렉트
    header('Location: customer-list.php');
    exit;
}

// 파라미터 값 가져오기
$documentId = (int)$_POST['document_id'];
$customerId = (int)$_POST['customer_id'];

// 유효성 검사
if ($documentId <= 0 || $customerId <= 0) {
    // 유효하지 않은 ID인 경우 문서 목록 페이지로 리다이렉트
    header('Location: customer-list.php');
    exit;
}

// 데이터베이스 연결
$db = getDbConnection();

// 문서 정보 조회
$sql = "SELECT document_path FROM customer_documents WHERE id = ? AND customer_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('ii', $documentId, $customerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // 문서 정보가 없는 경우 문서 목록 페이지로 리다이렉트
    $stmt->close();
    header('Location: customer-documents.php?customer_id=' . $customerId);
    exit;
}

$document = $result->fetch_assoc();
$stmt->close();

// 문서 파일 경로
$documentPath = $document['document_path'];
$fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $documentPath;

// 문서 DB 정보 삭제
$sql = "DELETE FROM customer_documents WHERE id = ? AND customer_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('ii', $documentId, $customerId);
$result = $stmt->execute();
$stmt->close();

// 삭제 결과에 따라 처리
if ($result) {
    // 작업 로그 기록
    logAction('document_delete', '문서 삭제: ' . $documentId . ', 고객: ' . $customerId);
    
    // 파일 삭제 시도
    if (file_exists($fullPath)) {
        @unlink($fullPath);
    }
    
    // 성공 메시지 설정
    $message = "문서가 성공적으로 삭제되었습니다.";
    $alertType = "success";
} else {
    // 실패 메시지 설정
    $message = "문서 삭제 중 오류가 발생했습니다.";
    $alertType = "danger";
    
    // 오류 로그 기록
    logError('document_delete_fail', '문서 삭제 실패: ' . $db->error);
}

// 세션에 메시지 저장
$_SESSION['document_delete_message'] = $message;
$_SESSION['document_delete_alert_type'] = $alertType;

// 문서 목록 페이지로 리다이렉트
header('Location: customer-documents.php?customer_id=' . $customerId);
exit;
