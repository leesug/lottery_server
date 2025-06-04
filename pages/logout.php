<?php
/**
 * 로그아웃 페이지
 * 세션을 파기하고 로그인 페이지로 리디렉션합니다.
 */

// 설정 파일 포함
require_once '../includes/config.php';

// 세션 시작
session_start();

// 세션 변수 비우기
$_SESSION = array();

// 쿠키에 저장된 세션 ID 삭제
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// 세션 파기
session_destroy();

// 로그인 페이지로 리디렉션
header('Location: ' . SERVER_URL . '/pages/login.php?logout=1');
exit;
