<?php
/**
 * 복권 관리 메인 페이지
 * 이 페이지는 복권 관리 섹션의 메인 진입점입니다.
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = getDbConnection();

// 복권 상품 정보 (Mock 데이터 사용)
$lotteryProducts = [
    ['name' => 'KHUSHI Bumper', 'status' => 'active', 'prize_pool' => 25000000, 'sales' => 145320],
    ['name' => 'KHUSHI Weekly', 'status' => 'active', 'prize_pool' => 10000000, 'sales' => 89745],
    ['name' => 'KHUSHI Daily', 'status' => 'active', 'prize_pool' => 5000000, 'sales' => 125680],
    ['name' => 'KHUSHI Special', 'status' => 'pending', 'prize_pool' => 30000000, 'sales' => 0]
];

// 최근 배치 정보 (Mock 데이터 사용)
$recentBatches = [
    ['id' => 'B2024051801', 'product' => 'KHUSHI Bumper', 'date' => '2024-05-18', 'quantity' => 50000, 'allocated' => 15000, 'status' => 'active'],
    ['id' => 'B2024051701', 'product' => 'KHUSHI Weekly', 'date' => '2024-05-17', 'quantity' => 30000, 'allocated' => 28500, 'status' => 'active'],
    ['id' => 'B2024051601', 'product' => 'KHUSHI Daily', 'date' => '2024-05-16', 'quantity' => 20000, 'allocated' => 20000, 'status' => 'closed'],
    ['id' => 'B2024051501', 'product' => 'KHUSHI Bumper', 'date' => '2024-05-15', 'quantity' => 50000, 'allocated' => 50000, 'status' => 'closed']
];

// 현재 페이지 정보
$pageTitle = "복권 관리 대시보드";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';

// 콘텐츠 로드
include_once 'dashboard-content.php';

// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>