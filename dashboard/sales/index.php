<?php
/**
 * 판매 관리 메인 페이지
 * 이 페이지는 판매 관리 섹션의 메인 진입점입니다.
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = getDbConnection();

// 판매 현황 정보 (Mock 데이터 사용)
$salesStats = [
    'today_sales_count' => 10245,
    'today_sales_amount' => 2048500,
    'week_sales_count' => 65750,
    'total_sales_amount' => 12456800
];

// 최근 판매 내역 (Mock 데이터 사용)
$recentSales = [
    ['id' => 'S202405180001', 'store' => '판매점 #123', 'type' => 'KHUSHI Bumper', 'quantity' => 5, 'amount' => 1000, 'date' => '2024-05-18 09:15:22', 'status' => 'complete'],
    ['id' => 'S202405180002', 'store' => '판매점 #045', 'type' => 'KHUSHI Weekly', 'quantity' => 10, 'amount' => 1000, 'date' => '2024-05-18 09:18:45', 'status' => 'complete'],
    ['id' => 'S202405180003', 'store' => '판매점 #078', 'type' => 'KHUSHI Daily', 'quantity' => 20, 'amount' => 1000, 'date' => '2024-05-18 09:22:10', 'status' => 'complete'],
    ['id' => 'S202405180004', 'store' => '판매점 #156', 'type' => 'KHUSHI Bumper', 'quantity' => 2, 'amount' => 400, 'date' => '2024-05-18 09:25:33', 'status' => 'complete'],
    ['id' => 'S202405180005', 'store' => '판매점 #091', 'type' => 'KHUSHI Weekly', 'quantity' => 15, 'amount' => 1500, 'date' => '2024-05-18 09:30:15', 'status' => 'processing']
];

// 지역별 판매 현황 (Mock 데이터 사용)
$regionSales = [
    ['region' => '카트만두', 'stores' => 45, 'count' => 3820, 'amount' => 764000, 'percentage' => 38],
    ['region' => '포카라', 'stores' => 32, 'count' => 2640, 'amount' => 528000, 'percentage' => 26],
    ['region' => '비랏나가르', 'stores' => 24, 'count' => 1950, 'amount' => 390000, 'percentage' => 19],
    ['region' => '네팔군지', 'stores' => 18, 'count' => 1520, 'amount' => 304000, 'percentage' => 15]
];

// 현재 페이지 정보
$pageTitle = "판매 관리 대시보드";
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