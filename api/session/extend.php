<?php
/**
 * 세션 연장 API
 * 
 * 이 API는 세션 타임아웃을 방지하기 위해 세션을 연장합니다.
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// API 응답 형식 설정
header('Content-Type: application/json');

// CSRF 토큰 검증
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(API_FORBIDDEN);
    echo json_encode([
        'status' => 'error',
        'message' => '유효하지 않은 CSRF 토큰입니다.'
    ]);
    exit;
}

// 로그인 확인
if (!isLoggedIn()) {
    http_response_code(API_UNAUTHORIZED);
    echo json_encode([
        'status' => 'error',
        'message' => '인증되지 않은 요청입니다.'
    ]);
    exit;
}

// 세션 활동 시간 갱신
SessionManager::checkSessionExpiration();

// 응답
http_response_code(API_SUCCESS);
echo json_encode([
    'status' => 'success',
    'message' => '세션이 성공적으로 연장되었습니다.',
    'data' => [
        'remainingTime' => SessionManager::getRemainingTime()
    ]
]);
