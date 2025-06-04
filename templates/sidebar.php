<?php
/**
 * 사이드바 템플릿 파일
 */

// 현재 페이지 경로
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 사용자 권한 확인 (메뉴 표시 제어용)
$userRole = SessionManager::getUserRole();
$isAdmin = AuthManager::isAdmin();
$isAgent = AuthManager::isAgent();
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>KHUSHI LOTTERY</h2>
    </div>
