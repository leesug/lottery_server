<?php
/**
 * 인증 관련 추가 함수
 */

// 필요한 파일 포함
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';

/**
 * 인증 상태를 확인하고 필요시 리다이렉트합니다.
 * 로그인되지 않은 경우 로그인 페이지로 리다이렉트합니다.
 */
function check_auth() {
    if (!isLoggedIn()) {
        // 로그인되지 않은 경우 로그인 페이지로 리다이렉트
        header('Location: /server/pages/login.php');
        exit;
    }
    
    // 세션 만료 확인
    if (isSessionExpired()) {
        // 세션이 만료된 경우 로그아웃 처리 후 리다이렉트
        logoutUser();
        header('Location: /server/pages/login.php?expired=1');
        exit;
    }
    
    // 세션 활동 시간 갱신
    updateSessionActivity();
}

/**
 * 데이터베이스 연결을 가져옵니다.
 * (함수명 변경 이슈 해결용)
 */
function get_db_connection() {
    return getDbConnection();
}
