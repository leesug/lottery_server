<?php
/**
 * 실시간 판매 데이터 API
 * 
 * 실시간 판매 데이터를 JSON 형식으로 제공하는 API
 * 
 * @return JSON 실시간 판매 데이터
 */

// 세션 시작 및 로그인 체크
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    exit(json_encode([
        'status' => 'error',
        'message' => '인증되지 않은 접근입니다.'
    ]));
}

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 데이터베이스 연결
$conn = getDBConnection();

// 오늘의 판매 데이터 가져오기 (시간대별)
function getTodaySales($conn) {
    $today = date('Y-m-d');
    $query = "
        SELECT 
            HOUR(created_at) as hour,
            COUNT(id) as tickets_count,
            SUM(price) as sales_amount
        FROM 
            tickets
        WHERE 
            DATE(created_at) = ?
        GROUP BY 
            HOUR(created_at)
        ORDER BY 
            hour
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$today]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 오늘의 판매 현황 요약
function getTodaySalesSummary($conn) {
    $today = date('Y-m-d');
    $query = "
        SELECT 
            COUNT(id) as total_tickets,
            SUM(price) as total_sales,
            COUNT(DISTINCT terminal_id) as active_terminals,
            MAX(created_at) as last_sale_time
        FROM 
            tickets
        WHERE 
            DATE(created_at) = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$today]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 상품별 오늘 판매 현황
function getTodayProductSales($conn) {
    $today = date('Y-m-d');
    $query = "
        SELECT 
            lp.id,
            lp.name as product_name,
            COUNT(t.id) as tickets_count,
            SUM(t.price) as sales_amount
        FROM 
            tickets t
        JOIN 
            lottery_products lp ON t.product_id = lp.id
        WHERE 
            DATE(t.created_at) = ?
        GROUP BY 
            lp.id, lp.name
        ORDER BY 
            tickets_count DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$today]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 지역별 오늘 판매 현황
function getTodayRegionSales($conn) {
    $today = date('Y-m-d');
    $query = "
        SELECT 
            r.id,
            r.name as region_name,
            COUNT(t.id) as tickets_count,
            SUM(t.price) as sales_amount
        FROM 
            tickets t
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        JOIN 
            stores s ON tm.store_id = s.id
        JOIN 
            regions r ON s.region_id = r.id
        WHERE 
            DATE(t.created_at) = ?
        GROUP BY 
            r.id, r.name
        ORDER BY 
            tickets_count DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$today]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 최근 판매 내역
function getRecentSales($conn, $limit = 5) {
    $query = "
        SELECT 
            t.id,
            t.ticket_number,
            lp.name as product_name,
            t.price,
            t.created_at,
            s.name as store_name,
            r.name as region_name
        FROM 
            tickets t
        JOIN 
            lottery_products lp ON t.product_id = lp.id
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        JOIN 
            stores s ON tm.store_id = s.id
        JOIN 
            regions r ON s.region_id = r.id
        ORDER BY 
            t.created_at DESC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 데이터 가져오기
$today_sales = getTodaySales($conn);
$today_summary = getTodaySalesSummary($conn);
$product_sales = getTodayProductSales($conn);
$region_sales = getTodayRegionSales($conn);
$recent_sales = getRecentSales($conn);

// JSON 응답 출력
header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => [
        'today_sales' => $today_sales,
        'today_summary' => $today_summary,
        'product_sales' => $product_sales,
        'region_sales' => $region_sales,
        'recent_sales' => $recent_sales
    ]
]);
