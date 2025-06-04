<?php
/**
 * 번호 체계 조회 API
 * 
 * 특정 ID의 번호 체계 정보를 조회합니다.
 */

// 헤더 설정
header('Content-Type: application/json');

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 응답 초기화
$response = [
    'status' => 'error',
    'message' => '잘못된 요청입니다.',
    'data' => null
];

// ID 확인
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode($response);
    exit;
}

$id = (int) $_GET['id'];

try {
    // 데이터베이스 연결
    $db = get_db_connection();
    
    // 번호 체계 조회
    $stmt = $db->prepare("
        SELECT 
            *,
            IF(created_at IS NOT NULL, created_at, NOW()) as created_at,
            IF(updated_at IS NOT NULL, updated_at, NULL) as updated_at
        FROM 
            number_formats
        WHERE 
            id = ?
    ");
    
    $stmt->execute([$id]);
    $format = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($format) {
        $response['status'] = 'success';
        $response['message'] = '';
        $response['data'] = $format;
    } else {
        $response['message'] = '번호 체계를 찾을 수 없습니다.';
    }
} catch (PDOException $e) {
    $response['message'] = '데이터베이스 오류가 발생했습니다: ' . $e->getMessage();
    error_log("API 오류: " . $e->getMessage());
}

echo json_encode($response);
