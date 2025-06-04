<?php
/**
 * 추첨 관리 메인 페이지
 * 이 페이지는 추첨 관리 섹션의 메인 진입점입니다.
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = getDbConnection();

// 추첨 정보 (Mock 데이터 사용)
$drawInfo = [
    'scheduled_draws' => 3,
    'completed_draws' => 124,
    'total_winners' => 85642,
    'next_draw_date' => '2024-05-22'
];

// 최근 추첨 결과 (Mock 데이터 사용)
$recentDraws = [
    [
        'id' => 125, 
        'product' => 'KHUSHI Weekly', 
        'date' => '2024-05-18', 
        'numbers' => '08, 15, 22, 30, 37, 42', 
        'winners' => 6820, 
        'prize_pool' => 10000000
    ],
    [
        'id' => 124, 
        'product' => 'KHUSHI Daily', 
        'date' => '2024-05-17', 
        'numbers' => '03, 11, 19, 25, 32, 40', 
        'winners' => 5240, 
        'prize_pool' => 5000000
    ],
    [
        'id' => 123, 
        'product' => 'KHUSHI Weekly', 
        'date' => '2024-05-11', 
        'numbers' => '05, 13, 20, 28, 35, 41', 
        'winners' => 7152, 
        'prize_pool' => 10000000
    ],
    [
        'id' => 122, 
        'product' => 'KHUSHI Bumper', 
        'date' => '2024-05-05', 
        'numbers' => '07, 12, 24, 31, 39, 45', 
        'winners' => 12450, 
        'prize_pool' => 25000000
    ]
];

// 현재 페이지 정보
$pageTitle = "추첨 관리 대시보드";
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