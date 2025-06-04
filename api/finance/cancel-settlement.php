<?php
/**
 * 재무 관리 API - 정산 취소
 * 
 * 이 API는 정산을 취소하는 기능을 제공합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// API 응답 헤더 설정
header('Content-Type: application/json');

// 인증 확인
if (!isAuthenticated()) {
    echo json_encode([
        'success' => false,
        'message' => '인증되지 않은 요청입니다.'
    ]);
    exit;
}

// 필요한 권한 확인
if (!hasPermission('finance_settlements_cancel')) {
    echo json_encode([
        'success' => false,
        'message' => '권한이 없습니다.'
    ]);
    exit;
}

// POST 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '잘못된 요청 방식입니다.'
    ]);
    exit;
}

// 필수 파라미터 확인
if (!isset($_POST['settlement_id']) || !is_numeric($_POST['settlement_id'])) {
    echo json_encode([
        'success' => false,
        'message' => '정산 ID가 유효하지 않습니다.'
    ]);
    exit;
}

if (!isset($_POST['reason']) || empty(trim($_POST['reason']))) {
    echo json_encode([
        'success' => false,
        'message' => '취소 사유는 필수입니다.'
    ]);
    exit;
}

$settlementId = intval($_POST['settlement_id']);
$reason = sanitizeInput($_POST['reason']);
$userId = $_SESSION['user_id'];

// 데이터베이스 연결
$conn = getDBConnection();

try {
    // 트랜잭션 시작
    $conn->begin_transaction();
    
    // 정산 정보 조회
    $sql = "SELECT settlement_code, status FROM settlements WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("데이터베이스 준비 오류: " . $conn->error);
    }
    
    $stmt->bind_param("i", $settlementId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("존재하지 않는 정산입니다.");
    }
    
    $settlement = $result->fetch_assoc();
    
    // 정산 상태 확인
    if ($settlement['status'] === 'completed') {
        throw new Exception("이미 완료된 정산은 취소할 수 없습니다.");
    }
    
    // 취소 메모 생성
    $cancelNotes = "취소 사유: " . $reason . " (취소 처리자: " . getUserName($userId) . ")";
    
    // 정산 취소 처리 (상태를 'cancelled'로 변경)
    $updateSql = "UPDATE settlements SET 
                  status = 'cancelled', 
                  notes = CONCAT(IFNULL(notes, ''), '\n\n', ?), 
                  updated_at = NOW() 
                  WHERE id = ?";
    
    $updateStmt = $conn->prepare($updateSql);
    if (!$updateStmt) {
        throw new Exception("데이터베이스 준비 오류: " . $conn->error);
    }
    
    $updateStmt->bind_param("si", $cancelNotes, $settlementId);
    
    $updateResult = $updateStmt->execute();
    if (!$updateResult) {
        throw new Exception("정산 취소 중 오류가 발생했습니다: " . $updateStmt->error);
    }
    
    // 로그 기록
    logActivity('finance', 'settlement_cancel', "정산 취소: {$settlement['settlement_code']}, 사유: {$reason}");
    
    // 트랜잭션 커밋
    $conn->commit();
    
    // 성공 응답
    echo json_encode([
        'success' => true,
        'message' => '정산이 성공적으로 취소되었습니다.',
        'settlement_id' => $settlementId
    ]);
    
} catch (Exception $e) {
    // 트랜잭션 롤백
    $conn->rollback();
    
    // 오류 로깅
    logError("정산 취소 오류: " . $e->getMessage());
    
    // 오류 응답
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // 연결 종료
    if (isset($stmt)) $stmt->close();
    if (isset($updateStmt)) $updateStmt->close();
    $conn->close();
}

/**
 * 사용자 이름 가져오기
 *
 * @param int $userId 사용자 ID
 * @return string 사용자 이름
 */
function getUserName($userId) {
    global $conn;
    
    $sql = "SELECT username FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return $user['username'];
    }
    
    return "Unknown";
}
?>