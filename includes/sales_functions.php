<?php
/**
 * 판매 데이터 관련 함수
 * 
 * 판매 데이터를 가져오고 처리하는 함수들을 포함합니다.
 */

/**
 * 회차별 판매 정보를 가져옵니다.
 * 
 * @param PDO $db 데이터베이스 연결 객체
 * @param int $drawId 회차 ID (선택적)
 * @param array $filters 추가 필터 (시작일, 종료일 등)
 * @return array 판매 데이터 배열
 */
function getSalesDataByDraw($db, $drawId = null, $filters = []) {
    // 쿼리 구성
    $query = "
        SELECT 
            d.id AS draw_id,
            d.draw_code,
            d.draw_date,
            d.product_id,
            p.name AS product_name,
            COALESCE(s.total_sales_amount, 0) AS total_sales_amount,
            COALESCE(s.online_sales_amount, 0) AS online_sales_amount,
            COALESCE(s.offline_sales_amount, 0) AS offline_sales_amount
        FROM 
            draws d
        LEFT JOIN 
            lottery_products p ON d.product_id = p.id
        LEFT JOIN (
            SELECT 
                lottery_type_id as draw_id,
                SUM(total_amount) AS total_sales_amount,
                SUM(CASE WHEN payment_method = 'online' THEN total_amount ELSE 0 END) AS online_sales_amount,
                SUM(CASE WHEN payment_method = 'offline' THEN total_amount ELSE 0 END) AS offline_sales_amount
            FROM 
                sales_transactions
            GROUP BY 
                lottery_type_id
        ) s ON d.product_id = s.draw_id
        WHERE 1=1
    ";
    
    // 파라미터 배열
    $params = [];
    
    // 특정 회차 ID에 대한 필터 적용
    if (!is_null($drawId)) {
        $query .= " AND d.id = :draw_id";
        $params[':draw_id'] = $drawId;
    }
    
    // 시작 회차 필터
    if (isset($filters['start_draw']) && $filters['start_draw'] > 0) {
        $query .= " AND d.draw_code >= :start_draw";
        $params[':start_draw'] = $filters['start_draw'];
    }
    
    // 종료 회차 필터
    if (isset($filters['end_draw']) && $filters['end_draw'] > 0) {
        $query .= " AND d.draw_code <= :end_draw";
        $params[':end_draw'] = $filters['end_draw'];
    }
    
    // 시작 날짜 필터
    if (!empty($filters['start_date'])) {
        $query .= " AND d.draw_date >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    
    // 종료 날짜 필터
    if (!empty($filters['end_date'])) {
        $query .= " AND d.draw_date <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    
    // 검색어 필터
    if (!empty($filters['search'])) {
        $query .= " AND (p.name LIKE :search OR d.draw_code LIKE :search)";
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    
    // 정렬 적용
    $query .= " ORDER BY d.draw_date DESC, d.draw_code DESC";
    
    // 쿼리 실행
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 특정 회차의 판매 상세 정보를 가져옵니다.
 * 
 * @param PDO $db 데이터베이스 연결 객체
 * @param int $drawId 회차 ID
 * @return array 판매 상세 데이터 배열
 */
function getSalesDetailsByDrawId($db, $drawId) {
    $query = "
        SELECT 
            d.id AS draw_id,
            d.draw_code,
            d.draw_date,
            d.product_id,
            p.name AS product_name,
            st.transaction_date as sales_date,
            st.payment_method as sales_channel,
            st.total_amount as sales_amount,
            COUNT(DISTINCT st.store_id) as store_count,
            SUM(st.ticket_quantity) as ticket_count
        FROM 
            draws d
        JOIN 
            lottery_products p ON d.product_id = p.id
        LEFT JOIN 
            sales_transactions st ON d.product_id = st.lottery_type_id
        WHERE 
            d.id = :draw_id
        GROUP BY
            st.transaction_date, st.payment_method
        ORDER BY 
            st.transaction_date
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':draw_id', $drawId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 판매 실적 통계를 가져옵니다.
 * 
 * @param PDO $db 데이터베이스 연결 객체
 * @param array $filters 필터 (시작일, 종료일 등)
 * @return array 판매 통계 데이터
 */
function getSalesStatistics($db, $filters = []) {
    // 필터에 따른 조건 설정
    $conditions = "WHERE 1=1";
    $params = [];
    
    if (!empty($filters['start_date'])) {
        $conditions .= " AND transaction_date >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    
    if (!empty($filters['end_date'])) {
        $conditions .= " AND transaction_date <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    
    // 총 판매액 쿼리
    $query = "
        SELECT 
            SUM(total_amount) AS total_sales,
            SUM(CASE WHEN payment_method = 'online' THEN total_amount ELSE 0 END) AS online_sales,
            SUM(CASE WHEN payment_method = 'offline' THEN total_amount ELSE 0 END) AS offline_sales,
            COUNT(DISTINCT store_id) AS total_stores,
            SUM(ticket_quantity) AS total_tickets
        FROM 
            sales_transactions
        $conditions
    ";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 일자별 판매 추이 쿼리
    $trendQuery = "
        SELECT 
            DATE(transaction_date) AS sale_date,
            SUM(total_amount) AS daily_sales
        FROM 
            sales_transactions
        $conditions
        GROUP BY 
            DATE(transaction_date)
        ORDER BY 
            sale_date DESC
        LIMIT 30
    ";
    
    $trendStmt = $db->prepare($trendQuery);
    foreach ($params as $key => $value) {
        $trendStmt->bindValue($key, $value);
    }
    $trendStmt->execute();
    
    $result['daily_trend'] = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $result;
}
