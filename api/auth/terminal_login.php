<?php
/**
 * 판매점 단말기 로그인 API
 * 판매점 번호와 비밀번호로 인증
 */

require_once '../../includes/config.php';
require_once '../../includes/db.php';

// CORS 헤더 설정
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// JSON 데이터 파싱
$input = json_decode(file_get_contents('php://input'), true);

// 입력값 검증
$store_id = isset($input['store_id']) ? trim($input['store_id']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if (empty($store_id) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => '판매점 번호와 비밀번호를 입력해주세요.'
    ]);
    exit;
}

// 개발 모드에서 테스트 계정 처리
if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
    if ($store_id === '123456789' && $password === '1234') {
        // 테스트 계정 데이터
        echo json_encode([
            'success' => true,
            'message' => '로그인 성공',
            'data' => [
                'store_id' => 1,
                'store_code' => '123456789',
                'store_name' => '테스트 판매점',
                'owner_name' => '테스트 사용자',
                'grade' => 'B'
            ]
        ]);
        exit;
    }
}

try {
    $db = getDbConnection();
    
    // 판매점 정보 조회
    $query = "SELECT 
                s.id, 
                s.store_code, 
                s.store_name, 
                s.owner_name,
                s.phone,
                s.status,
                s.grade,
                s.password
              FROM stores s
              WHERE s.store_code = :store_code
              AND s.status = 'active'
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':store_code', $store_id, PDO::PARAM_STR);
    $stmt->execute();
    
    $store = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 판매점이 존재하지 않는 경우
    if (!$store) {
        echo json_encode([
            'success' => false,
            'message' => '판매점 번호 또는 비밀번호가 일치하지 않습니다.'
        ]);
        exit;
    }
    
    // 비밀번호 확인
    // 실제 운영 시에는 password_verify() 사용
    if (isset($store['password'])) {
        if (!password_verify($password, $store['password'])) {
            // 평문 비밀번호 비교 (개발용)
            if ($store['password'] !== $password) {
                echo json_encode([
                    'success' => false,
                    'message' => '판매점 번호 또는 비밀번호가 일치하지 않습니다.'
                ]);
                exit;
            }
        }
    } else {
        // 비밀번호 필드가 없는 경우 임시로 1234 허용
        if ($password !== '1234') {
            echo json_encode([
                'success' => false,
                'message' => '판매점 번호 또는 비밀번호가 일치하지 않습니다.'
            ]);
            exit;
        }
    }
    
    // 로그인 성공
    echo json_encode([
        'success' => true,
        'message' => '로그인 성공',
        'data' => [
            'store_id' => $store['id'],
            'store_code' => $store['store_code'],
            'store_name' => $store['store_name'],
            'owner_name' => $store['owner_name'],
            'grade' => $store['grade'] ?? 'B'
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Terminal login error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '로그인 처리 중 오류가 발생했습니다.',
        'debug' => DEVELOPMENT_MODE ? $e->getMessage() : null
    ]);
}
?>
