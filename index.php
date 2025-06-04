<?php
/**
 * 로또 서버 메인 진입점
 * 로그인 상태를 확인하고 적절한 페이지로 리디렉션합니다.
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// 세션 시작
session_start();

// 로그인 상태 확인
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// 로그인 상태에 따라 리디렉션
if ($isLoggedIn) {
    // 로그인된 경우 대시보드로 리디렉션
    header('Location: dashboard/index.php');
    exit;
} else {
    // 로그인되지 않은 경우 로그인 페이지로 리디렉션
    header('Location: pages/login.php');
    exit;
}
