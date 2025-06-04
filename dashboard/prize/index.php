<?php
/**
 * 당첨금 관리 메인 페이지
 * 이 페이지는 당첨금 관리 섹션의 메인 진입점입니다.
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = getDbConnection();

// 당첨금 정보 (Mock 데이터 사용)
$prizeInfo = [
    'total_prize_pool' => 45000000,
    'claimed_amount' => 12850000,
    'unclaimed_amount' => 7150000,
    'carryover_amount' => 25000000
];

// 최근 당첨금 지급 내역 (Mock 데이터 사용)
$recentPrizes = [
    [
        'id' => 'P202405180001', 
        'draw_id' => 125, 
        'product' => 'KHUSHI Weekly', 
        'ticket' => 'TK24W17505', 
        'amount' => 5000000, 
        'rank' => 1, 
        'date' => '2024-05-18 14:30:25', 
        'status' => 'paid'
    ],
    [
        'id' => 'P202405180002', 
        'draw_id' => 125, 
        'product' => 'KHUSHI Weekly', 
        'ticket' => 'TK24W18720', 
        'amount' => 2000000, 
        'rank' => 2, 
        'date' => '2024-05-18 15:10:45', 
        'status' => 'paid'
    ],
    [
        'id' => 'P202405180003', 
        'draw_id' => 125, 
        'product' => 'KHUSHI Weekly', 
        'ticket' => 'TK24W20145', 
        'amount' => 1000000, 
        'rank' => 3, 
        'date' => '2024-05-18 15:45:12', 
        'status' => 'processing'
    ],
    [
        'id' => 'P202405170001', 
        'draw_id' => 124, 
        'product' => 'KHUSHI Daily', 
        'ticket' => 'TK24D08925', 
        'amount' => 2500000, 
        'rank' => 1, 
        'date' => '2024-05-17 14:15:30', 
        'status' => 'paid'
    ]
];

// 현재 페이지 정보
$pageTitle = "당첨금 관리 대시보드";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
include_once TEMPLATES_PATH . '/page_header.php';

// 콘텐츠 로드
include_once 'dashboard-content.php';

// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>