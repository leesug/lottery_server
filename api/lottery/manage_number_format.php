<?php
/**
 * 번호 체계 추가/수정 API
 * 
 * 새로운 번호 체계를 추가하거나 기존 번호 체계를 수정합니다.
 * - action: add_format 또는 edit_format
 */

// 헤더 설정
header('Content-Type: application/json');

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// sanitize_input 함수가 없을 경우 정의
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

// 로그 파일 설정
$logFile = LOGS_PATH . '/number_format.log';

// 응답 초기화
$response = [
    'status' => 'error',
    'message' => '잘못된 요청입니다.',
    'data' => null
];

// POST 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = '허용되지 않는 요청 방식입니다.';
    echo json_encode($response);
    exit;
}

// 필수 파라미터 확인
if (!isset($_POST['action']) || !in_array($_POST['action'], ['add_format', 'edit_format'])) {
    $response['message'] = '유효하지 않은 액션입니다.';
    echo json_encode($response);
    exit;
}

$action = $_POST['action'];
$name = sanitize_input($_POST['name']);
$description = sanitize_input($_POST['description']);
$pattern = sanitize_input($_POST['pattern']);
$min_length = (int) sanitize_input($_POST['min_length']);
$max_length = (int) sanitize_input($_POST['max_length']);
$prefix = sanitize_input($_POST['prefix'] ?? '');
$suffix = sanitize_input($_POST['suffix'] ?? '');
$is_alphanumeric = isset($_POST['is_alphanumeric']) ? 1 : 0;
$allowed_characters = sanitize_input($_POST['allowed_characters'] ?? '');
$is_active = isset($_POST['is_active']) ? 1 : 0;

// 필수 필드 검증
if (empty($name) || empty($pattern) || $min_length <= 0 || $max_length <= 0) {
    $response['message'] = '필수 필드가 누락되었습니다.';
    echo json_encode($response);
    exit;
}

try {
    // 데이터베이스 연결
    $db = get_db_connection();
    
    // 테이블 존재 여부 확인
    $stmt = $db->query("SHOW TABLES LIKE 'number_formats'");
    $tableExists = $stmt->rowCount() > 0;
    
    // 테이블이 없으면 생성
    if (!$tableExists) {
        $db->exec("CREATE TABLE `number_formats` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `description` text,
            `pattern` varchar(50) NOT NULL,
            `min_length` int(11) NOT NULL,
            `max_length` int(11) NOT NULL,
            `prefix` varchar(20) DEFAULT NULL,
            `suffix` varchar(20) DEFAULT NULL,
            `is_alphanumeric` tinyint(1) NOT NULL DEFAULT '0',
            `allowed_characters` varchar(255) DEFAULT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT '1',
            `created_by` int(11) DEFAULT NULL,
            `updated_by` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        
        // 로그 기록
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - number_formats 테이블이 존재하지 않아 새로 생성했습니다.\n", FILE_APPEND);
    }
    
    // 현재 사용자 ID 가져오기
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    
    if ($action === 'add_format') {
        // 새 번호 체계 추가
        $stmt = $db->prepare("
            INSERT INTO number_formats (
                name, description, pattern, min_length, max_length,
                prefix, suffix, is_alphanumeric, allowed_characters, is_active,
                created_by, updated_by
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $name, $description, $pattern, $min_length, $max_length,
            $prefix, $suffix, $is_alphanumeric, $allowed_characters, $is_active,
            $userId, $userId
        ]);
        
        $formatId = $db->lastInsertId();
        
        $response['status'] = 'success';
        $response['message'] = '번호 체계가 성공적으로 추가되었습니다.';
        $response['data'] = ['id' => $formatId];
        
        // 로그 기록
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - 새 번호 체계 추가: $name (ID: $formatId, 사용자: $userId)\n", FILE_APPEND);
        
    } else if ($action === 'edit_format') {
        // 기존 번호 체계 수정
        if (!isset($_POST['format_id']) || empty($_POST['format_id'])) {
            $response['message'] = '번호 체계 ID가 필요합니다.';
            echo json_encode($response);
            exit;
        }
        
        $formatId = (int) sanitize_input($_POST['format_id']);
        
        $stmt = $db->prepare("
            UPDATE number_formats SET
                name = ?,
                description = ?,
                pattern = ?,
                min_length = ?,
                max_length = ?,
                prefix = ?,
                suffix = ?,
                is_alphanumeric = ?,
                allowed_characters = ?,
                is_active = ?,
                updated_by = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $name, $description, $pattern, $min_length, $max_length,
            $prefix, $suffix, $is_alphanumeric, $allowed_characters, $is_active,
            $userId, $formatId
        ]);
        
        $response['status'] = 'success';
        $response['message'] = '번호 체계가 성공적으로 수정되었습니다.';
        $response['data'] = ['id' => $formatId];
        
        // 로그 기록
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - 번호 체계 수정: $name (ID: $formatId, 사용자: $userId)\n", FILE_APPEND);
    }
    
} catch (Exception $e) {
    $response['message'] = '오류가 발생했습니다: ' . $e->getMessage();
    error_log("API 오류 (manage_number_format.php): " . $e->getMessage());
}

echo json_encode($response);
