<?php
/**
 * 번호 체계 목록 조회 API
 * 
 * 등록된 모든 번호 체계를 조회합니다.
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

try {
    // 데이터베이스 연결
    $db = get_db_connection();
    
    // 테이블 존재 여부 확인
    $stmt = $db->query("SHOW TABLES LIKE 'number_formats'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // 번호 체계 목록 조회
        $stmt = $db->query("
            SELECT 
                nf.*,
                COALESCE((SELECT COUNT(*) FROM number_assignments WHERE format_id = nf.id), 0) AS assignment_count,
                COALESCE((SELECT COUNT(*) FROM number_reservations WHERE format_id = nf.id), 0) AS reservation_count
            FROM 
                number_formats nf
            ORDER BY 
                nf.name ASC
        ");
        
        $formats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['status'] = 'success';
        $response['message'] = '';
        $response['data'] = $formats;
    } else {
        $response['message'] = '번호 체계 테이블이 존재하지 않습니다.';
        $response['data'] = [];
    }
} catch (PDOException $e) {
    $response['message'] = '데이터베이스 오류가 발생했습니다: ' . $e->getMessage();
    error_log("API 오류: " . $e->getMessage());
}

echo json_encode($response);
