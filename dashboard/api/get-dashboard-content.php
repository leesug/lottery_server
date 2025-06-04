<?php
/**
 * API 엔드포인트: 대시보드 콘텐츠만 반환
 * 
 * 이 파일은 전체 페이지 대신 대시보드 콘텐츠 부분만 반환하는 API입니다.
 * 사이드바와 함께 로드되는 2중 메뉴 문제를 해결하기 위해 사용됩니다.
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// AJAX 요청 확인
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// 요청 매개변수 가져오기
$type = isset($_GET['type']) ? $_GET['type'] : '';

// 유효한 대시보드 타입 목록
$validTypes = [
    'lottery', 'sales', 'draw', 'prize', 'customer', 
    'store', 'finance', 'marketing', 'reports', 'system', 'logs'
];

// 대시보드 타입이 유효한지 확인
if (empty($type) || !in_array($type, $validTypes)) {
    // 오류 응답
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => '잘못된 대시보드 타입입니다.']);
    exit;
}

// 해당 타입의 대시보드 콘텐츠 파일 경로
$contentFile = "../{$type}/dashboard-content.php";

// 콘텐츠 파일이 존재하는지 확인
if (!file_exists($contentFile)) {
    // 대체 파일 시도 (이전 명명 규칙에 따른 파일)
    $alternativeFile = "../{$type}/{$type}-dashboard.php";
    
    if (!file_exists($alternativeFile)) {
        // 오류 응답
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => '대시보드 콘텐츠 파일을 찾을 수 없습니다.']);
        exit;
    }
    
    $contentFile = $alternativeFile;
}

// 출력 버퍼링 시작
ob_start();

// 필요한 전역 변수 설정 (콘텐츠 파일에서 사용할 수 있도록)
$db = getDbConnection();
$pageTitle = ucfirst($type) . " 대시보드";
$currentSection = $type;

// 콘텐츠 파일 포함 (리디렉션을 방지하기 위해 출력 버퍼링 사용)
include $contentFile;

// 출력 버퍼에서 콘텐츠 가져오기
$content = ob_get_clean();

// 응답 헤더 설정
header('Content-Type: text/html; charset=UTF-8');

// 콘텐츠 반환
echo $content;
exit;
