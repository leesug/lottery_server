<?php
/**
 * API 진입점
 * 
 * 모든 API 요청은 이 파일을 통해 처리됩니다.
 */

header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// CORS 설정
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// OPTIONS 요청 처리 (CORS 프리플라이트)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 요청 경로 분석
$path = $_GET['path'] ?? '';
$pathParts = explode('/', $path);

$resource = $pathParts[0] ?? '';
$action = $pathParts[1] ?? '';
$id = $pathParts[2] ?? null;

// 요청 메서드
$method = $_SERVER['REQUEST_METHOD'];

// 요청 데이터
$data = [];
if ($method !== 'GET') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?? [];
}

// API 인증 확인 (토큰 기반)
$authorized = false;
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (strpos($authHeader, 'Bearer ') === 0) {
    $token = substr($authHeader, 7);
    
    // 토큰 검증 (예: JWT 검증)
    // 여기서는 간단한 예시만 제공
    if ($token === APP_SECRET) {
        $authorized = true;
    }
} else {
    // 개발 환경에서는 인증 우회 (실제 서비스에서는 제거 필요)
    $authorized = true;
}

// 인증이 필요하지 않은 엔드포인트 목록
$publicEndpoints = [
    'auth/login',
    'auth/register',
];

// 인증 확인 (로그인 API 등 일부는 제외)
if (!$authorized && !in_array("$resource/$action", $publicEndpoints)) {
    sendErrorResponse('Unauthorized access', [], API_UNAUTHORIZED);
}

// API 요청 처리
try {
    // 해당 리소스 파일이 존재하는지 확인
    $resourceFile = __DIR__ . "/$resource/$action.php";
    
    if (file_exists($resourceFile)) {
        // 리소스 파일 포함
        require_once $resourceFile;
        
        // 메서드에 맞는 함수 이름 생성
        $functionName = strtolower($method) . ucfirst($action);
        
        // 해당 함수가 존재하는지 확인
        if (function_exists($functionName)) {
            // 함수 호출 및 응답
            $response = $functionName($data, $id);
            
            // 응답이 배열인 경우 JSON으로 변환
            if (is_array($response)) {
                echo json_encode($response);
            }
        } else {
            sendErrorResponse("Method not allowed: $method", [], API_FORBIDDEN);
        }
    } else {
        sendErrorResponse("Resource not found: $resource/$action", [], API_NOT_FOUND);
    }
} catch (Exception $e) {
    // 오류 로깅
    logError($e->getMessage(), 'api');
    
    // 오류 응답
    sendErrorResponse('Internal server error: ' . $e->getMessage(), [], API_SERVER_ERROR);
}
