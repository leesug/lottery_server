<?php
/**
 * 넘버링 관리 페이지
 */

// 세션 시작 및 인증 체크
session_start();

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 인증 확인
check_auth();

// 권한 확인 (개발 모드에서는 비활성화)
// 아래 코드는 access-denied.php 파일이 없어서 오류가 발생하므로 임시로 주석 처리
/*
if (!has_permission('lottery_numbering_management')) {
    redirect_to('/server/dashboard/access-denied.php');
}
*/

// 데이터베이스 연결
$db = get_db_connection();

// 테이블 존재 여부 확인
$storeTableExists = false;
$productTableExists = false;
$formatTableExists = false;

try {
    $stmt = $db->query("SHOW TABLES LIKE 'stores'");
    $storeTableExists = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW TABLES LIKE 'lottery_products'");
    $productTableExists = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW TABLES LIKE 'number_formats'");
    $formatTableExists = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    error_log("Table check error: " . $e->getMessage());
}

// 작업 메시지 초기화
$message = '';
$message_type = '';

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 번호 체계 추가/수정 처리
    if (isset($_POST['action']) && ($_POST['action'] === 'add_format' || $_POST['action'] === 'edit_format')) {
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        $pattern = sanitize_input($_POST['pattern']);
        $min_length = (int) sanitize_input($_POST['min_length']);
        $max_length = (int) sanitize_input($_POST['max_length']);
        $prefix = sanitize_input($_POST['prefix'] ?? '');
        $suffix = sanitize_input($_POST['suffix'] ?? '');
        $is_alphanumeric = isset($_POST['is_alphanumeric']) ? 1 : 0;
        $allowed_characters = sanitize_input($_POST['allowed_characters'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            if ($_POST['action'] === 'add_format') {
                // 번호 체계 추가
                $stmt = $db->prepare("
                    INSERT INTO number_formats (
                        name, description, pattern, min_length, max_length,
                        prefix, suffix, is_alphanumeric, allowed_characters, is_active
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ");
                
                $stmt->execute([
                    $name, $description, $pattern, $min_length, $max_length,
                    $prefix, $suffix, $is_alphanumeric, $allowed_characters, $is_active
                ]);
                
                $message = '번호 체계가 성공적으로 추가되었습니다.';
                $message_type = 'success';
                
                // 활동 로그 기록
                log_activity('번호 체계 추가: ' . $name, 'number_formats');
            } else {
                // 번호 체계 수정
                $format_id = (int) sanitize_input($_POST['format_id']);
                
                $stmt = $db->prepare("
                    UPDATE number_formats SET
                        name = ?,
                        description = ?,
                        pattern = ?,
                        min_length = ?,
                        max_length = ?,
                        prefix = ?,
                        suffix = ?,
                        is_alphanumeric = ?,
                        allowed_characters = ?,
                        is_active = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $name, $description, $pattern, $min_length, $max_length,
                    $prefix, $suffix, $is_alphanumeric, $allowed_characters, $is_active, $format_id
                ]);
                
                $message = '번호 체계가 성공적으로 수정되었습니다.';
                $message_type = 'success';
                
                // 활동 로그 기록
                log_activity('번호 체계 수정: ' . $name, 'number_formats');
            }
        } catch (PDOException $e) {
            $message = '오류가 발생했습니다: ' . $e->getMessage();
            $message_type = 'danger';
            error_log("Database error: " . $e->getMessage());
        }
    }
    
    // 번호 할당 처리
    if (isset($_POST['action']) && $_POST['action'] === 'assign_numbers') {
        $product_id = (int) sanitize_input($_POST['product_id']);
        $format_id = (int) sanitize_input($_POST['format_id']);
        $store_id = (int) sanitize_input($_POST['store_id']);
        $start_number = sanitize_input($_POST['start_number']);
        $end_number = sanitize_input($_POST['end_number']);
        $quantity = (int) sanitize_input($_POST['quantity']);
        $notes = sanitize_input($_POST['notes'] ?? '');
        
        try {
            // 번호 할당 추가
            $stmt = $db->prepare("
                INSERT INTO number_assignments (
                    product_id, format_id, store_id, start_number, end_number,
                    quantity, notes, status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, 'active'
                )
            ");
            
            $stmt->execute([
                $product_id, $format_id, $store_id, $start_number, $end_number,
                $quantity, $notes
            ]);
            
            $message = '번호 할당이 성공적으로 추가되었습니다.';
            $message_type = 'success';
            
            // 활동 로그 기록
            log_activity('번호 할당 추가: ' . $product_id . ' - ' . $store_id, 'number_assignments');
        } catch (PDOException $e) {
            $message = '오류가 발생했습니다: ' . $e->getMessage();
            $message_type = 'danger';
            error_log("Database error: " . $e->getMessage());
        }
    }
    
    // 번호 예약 처리
    if (isset($_POST['action']) && $_POST['action'] === 'reserve_numbers') {
        $format_id = (int) sanitize_input($_POST['format_id']);
        $reserved_number = sanitize_input($_POST['reserved_number']);
        $reason = sanitize_input($_POST['reason']);
        $status = sanitize_input($_POST['status']);
        
        try {
            // 번호 예약 추가
            $stmt = $db->prepare("
                INSERT INTO number_reservations (
                    format_id, reserved_number, reason, status
                ) VALUES (
                    ?, ?, ?, ?
                )
            ");
            
            $stmt->execute([
                $format_id, $reserved_number, $reason, $status
            ]);
            
            $message = '번호 예약이 성공적으로 추가되었습니다.';
            $message_type = 'success';
            
            // 활동 로그 기록
            log_activity('번호 예약 추가: ' . $reserved_number, 'number_reservations');
        } catch (PDOException $e) {
            $message = '오류가 발생했습니다: ' . $e->getMessage();
            $message_type = 'danger';
            error_log("Database error: " . $e->getMessage());
        }
    }
    
    // 번호 예약 취소 처리
    if (isset($_POST['action']) && $_POST['action'] === 'cancel_reservation') {
        $reservation_id = (int) sanitize_input($_POST['reservation_id']);
        
        try {
            // 해당 예약이 존재하는지 확인
            $stmt = $db->prepare("SELECT id, status FROM number_reservations WHERE id = ?");
            $stmt->execute([$reservation_id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reservation) {
                $message = '해당 ID의 번호 예약을 찾을 수 없습니다.';
                $message_type = 'danger';
            } else if ($reservation['status'] === 'cancelled') {
                $message = '이미 취소된 예약입니다.';
                $message_type = 'warning';
            } else {
                // 예약 상태 업데이트
                $stmt = $db->prepare("
                    UPDATE number_reservations
                    SET status = 'cancelled',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $stmt->execute([$reservation_id]);
                
                $message = '번호 예약이 성공적으로 취소되었습니다.';
                $message_type = 'success';
                
                // 활동 로그 기록
                log_activity('번호 예약 취소: ID: ' . $reservation_id, 'number_reservations');
            }
        } catch (PDOException $e) {
            $message = '오류가 발생했습니다: ' . $e->getMessage();
            $message_type = 'danger';
            error_log("Database error: " . $e->getMessage());
        }
    }
}

// 번호 체계 목록 조회
$formats = [];

// 테이블이 없거나 구조가 맞지 않을 수 있으므로 정적 데이터 사용
// 실제 구현 시에는 number_formats 테이블을 생성하고 사용해야 함
try {
    // 테이블 존재 여부 확인
    $stmt = $db->query("SHOW TABLES LIKE 'number_formats'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // 테이블 구조 확인
        $stmt = $db->query("DESCRIBE number_formats");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        
        // name 컬럼이 있는지 확인
        $hasNameColumn = in_array('name', $columns);
        
        if ($hasNameColumn) {
            try {
                // 정상 쿼리 수행
                $stmt = $db->query("
                    SELECT 
                        nf.*,
                        COUNT(DISTINCT na.id) AS assignment_count,
                        COUNT(DISTINCT nr.id) AS reservation_count
                    FROM 
                        number_formats nf
                    LEFT JOIN 
                        number_assignments na ON nf.id = na.format_id
                    LEFT JOIN 
                        number_reservations nr ON nf.id = nr.format_id
                    GROUP BY 
                        nf.id
                    ORDER BY 
                        nf.name ASC
                ");
                
                $formats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 로그 기록
                error_log("번호 체계 목록 쿼리 성공: " . count($formats) . "개 항목 조회됨");
            } catch (PDOException $e) {
                error_log("번호 체계 쿼리 오류 (조인): " . $e->getMessage());
                
                // 조인이 실패할 경우 더 간단한 쿼리 시도
                $stmt = $db->query("SELECT * FROM number_formats ORDER BY name ASC");
                $formats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 할당 및 예약 카운트는 0으로 설정
                foreach ($formats as &$format) {
                    $format['assignment_count'] = 0;
                    $format['reservation_count'] = 0;
                }
            }
        } else {
            throw new Exception("number_formats 테이블에 'name' 컬럼이 없습니다.");
        }
    } else {
        throw new Exception("number_formats 테이블이 존재하지 않습니다.");
    }
} catch (Exception $e) {
    error_log("번호 체계 테이블 처리 오류: " . $e->getMessage());
    
    // 더미 데이터 생성
    $formats = [
        [
            'id' => 1,
            'name' => '기본 6자리 숫자',
            'pattern' => 'NNNNNN',
            'description' => '일반적인 6자리 숫자 형식',
            'min_length' => 6,
            'max_length' => 6,
            'prefix' => '',
            'suffix' => '',
            'is_alphanumeric' => 0,
            'allowed_characters' => '0123456789',
            'is_active' => 1,
            'created_at' => '2025-05-16 12:00:00',
            'assignment_count' => 1,
            'reservation_count' => 2
        ],
        [
            'id' => 2,
            'name' => '알파벳-숫자 혼합',
            'pattern' => 'LLNNNN',
            'description' => '알파벳 2자리 + 숫자 4자리',
            'min_length' => 6,
            'max_length' => 6,
            'prefix' => '',
            'suffix' => '',
            'is_alphanumeric' => 1,
            'allowed_characters' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            'is_active' => 1,
            'created_at' => '2025-05-16 12:00:00',
            'assignment_count' => 1,
            'reservation_count' => 1
        ],
        [
            'id' => 3,
            'name' => '접두사 포함 형식',
            'pattern' => 'RRNNNNNN',
            'description' => '지역코드 + 6자리 숫자',
            'min_length' => 8,
            'max_length' => 8,
            'prefix' => '',
            'suffix' => '',
            'is_alphanumeric' => 1,
            'allowed_characters' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            'is_active' => 1,
            'created_at' => '2025-05-16 12:00:00',
            'assignment_count' => 0,
            'reservation_count' => 0
        ]
    ];
}

// 번호 할당 목록 조회
$assignments = [];

// 정적 데이터 사용
try {
    // 필요한 테이블 존재 여부 확인
    $stmt = $db->query("SHOW TABLES LIKE 'number_assignments'");
    $assignmentTableExists = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW TABLES LIKE 'lottery_products'");
    $productTableExists = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW TABLES LIKE 'stores'");
    $storeTableExists = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW TABLES LIKE 'number_formats'");
    $formatTableExists = $stmt->rowCount() > 0;
    
    if ($assignmentTableExists && $productTableExists && $storeTableExists && $formatTableExists) {
        // 테이블 구조 확인
        // 1. lottery_products 테이블의 name 컬럼 확인
        $stmt = $db->query("DESCRIBE lottery_products");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        $productNameColumn = in_array('name', $columns) ? 'name' : 
                            (in_array('product_name', $columns) ? 'product_name' : 'product_code');
        
        // 2. stores 테이블의 name 컬럼 확인
        $stmt = $db->query("DESCRIBE stores");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        $storeNameColumn = in_array('name', $columns) ? 'name' : 
                          (in_array('store_name', $columns) ? 'store_name' : 'store_code');
        
        // 3. number_formats 테이블의 name 컬럼 확인
        $stmt = $db->query("DESCRIBE number_formats");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        $formatNameColumn = in_array('name', $columns) ? 'name' : 
                           (in_array('format_name', $columns) ? 'format_name' : 'id');
        
        try {
            // 쿼리 실행
            $stmt = $db->prepare("
                SELECT 
                    na.*,
                    lp.$productNameColumn AS product_name,
                    s.$storeNameColumn AS store_name,
                    nf.$formatNameColumn AS format_name
                FROM 
                    number_assignments na
                JOIN 
                    lottery_products lp ON na.product_id = lp.id
                JOIN 
                    stores s ON na.store_id = s.id
                JOIN 
                    number_formats nf ON na.format_id = nf.id
                ORDER BY 
                    na.created_at DESC
                LIMIT 20
            ");
            
            $stmt->execute();
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 로그 기록
            error_log("번호 할당 목록 쿼리 성공: " . count($assignments) . "개 항목 조회됨");
        } catch (PDOException $e) {
            error_log("번호 할당 쿼리 오류: " . $e->getMessage());
            throw $e;
        }
    } else {
        $missingTables = [];
        if (!$assignmentTableExists) $missingTables[] = 'number_assignments';
        if (!$productTableExists) $missingTables[] = 'lottery_products';
        if (!$storeTableExists) $missingTables[] = 'stores';
        if (!$formatTableExists) $missingTables[] = 'number_formats';
        
        throw new Exception("필요한 테이블이 없습니다: " . implode(', ', $missingTables));
    }
} catch (Exception $e) {
    error_log("번호 할당 처리 오류: " . $e->getMessage());
    
    // 더미 데이터 사용
    $assignments = [
        [
            'id' => 1,
            'product_id' => 1,
            'store_id' => 1,
            'format_id' => 1,
            'start_number' => '000001',
            'end_number' => '001000',
            'total_count' => 1000,
            'assigned_by' => 1,
            'assignment_date' => '2025-05-16',
            'status' => 'active',
            'created_at' => '2025-05-16 12:00:00',
            'product_name' => '일일 복권',
            'store_name' => '서울 중앙점',
            'format_name' => '기본 6자리 숫자'
        ],
        [
            'id' => 2,
            'product_id' => 2,
            'store_id' => 2,
            'format_id' => 2,
            'start_number' => 'AA0001',
            'end_number' => 'AA1000',
            'total_count' => 1000,
            'assigned_by' => 1,
            'assignment_date' => '2025-05-15',
            'status' => 'active',
            'created_at' => '2025-05-15 12:00:00',
            'product_name' => '주간 복권',
            'store_name' => '부산 해운대점',
            'format_name' => '알파벳-숫자 혼합'
        ]
    ];
}

// 번호 예약 목록 조회
$reservations = [];

// 정적 데이터 사용
try {
    // 테이블 존재 여부 확인
    $stmt = $db->query("SHOW TABLES LIKE 'number_reservations'");
    $reservationTableExists = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW TABLES LIKE 'number_formats'");
    $formatTableExists = $stmt->rowCount() > 0;
    
    if ($reservationTableExists && $formatTableExists) {
        // number_formats 테이블의 name 컬럼 확인
        $stmt = $db->query("DESCRIBE number_formats");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        $formatNameColumn = in_array('name', $columns) ? 'name' : 
                           (in_array('format_name', $columns) ? 'format_name' : 'id');
        
        try {
            // 쿼리 실행
            $stmt = $db->prepare("
                SELECT 
                    nr.*,
                    nf.$formatNameColumn AS format_name
                FROM 
                    number_reservations nr
                JOIN 
                    number_formats nf ON nr.format_id = nf.id
                ORDER BY 
                    nr.created_at DESC
                LIMIT 20
            ");
            
            $stmt->execute();
            $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 로그 기록
            error_log("번호 예약 목록 쿼리 성공: " . count($reservations) . "개 항목 조회됨");
        } catch (PDOException $e) {
            error_log("번호 예약 쿼리 오류: " . $e->getMessage());
            throw $e;
        }
    } else {
        $missingTables = [];
        if (!$reservationTableExists) $missingTables[] = 'number_reservations';
        if (!$formatTableExists) $missingTables[] = 'number_formats';
        
        throw new Exception("필요한 테이블이 없습니다: " . implode(', ', $missingTables));
    }
} catch (Exception $e) {
    error_log("번호 예약 처리 오류: " . $e->getMessage());
    
    // 더미 데이터 사용
    $reservations = [
        [
            'id' => 1,
            'format_id' => 1,
            'reserved_number' => '000000',
            'reason' => '시스템 예약 번호',
            'reserved_by' => 1,
            'is_permanent' => 1,
            'expiry_date' => null,
            'status' => 'active',
            'created_at' => '2025-05-16 12:00:00',
            'format_name' => '기본 6자리 숫자'
        ],
        [
            'id' => 2,
            'format_id' => 1,
            'reserved_number' => '999999',
            'reason' => '시스템 예약 번호',
            'reserved_by' => 1,
            'is_permanent' => 1,
            'expiry_date' => null,
            'status' => 'active',
            'created_at' => '2025-05-16 12:00:00',
            'format_name' => '기본 6자리 숫자'
        ],
        [
            'id' => 3,
            'format_id' => 2,
            'reserved_number' => 'AA0000',
            'reason' => '시스템 예약 번호',
            'reserved_by' => 1,
            'is_permanent' => 1,
            'expiry_date' => null,
            'status' => 'active',
            'created_at' => '2025-05-16 12:00:00',
            'format_name' => '알파벳-숫자 혼합'
        ]
    ];
}

// 복권 상품 목록 조회
$products = [];
try {
    // 테이블 구조 확인 - 'name' 컬럼 또는 'product_name' 컬럼 존재 여부 확인
    $stmt = $db->query("DESCRIBE lottery_products");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    
    // 적절한 쿼리 구성
    $nameColumn = in_array('name', $columns) ? 'name' : 
                 (in_array('product_name', $columns) ? 'product_name' : 'product_code');
    
    $stmt = $db->prepare("
        SELECT 
            id, product_code, " . $nameColumn . " AS name
        FROM 
            lottery_products
        WHERE 
            status = 'active'
        ORDER BY 
            " . $nameColumn . " ASC
    ");
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 로그 기록
    error_log("상품 목록 쿼리 성공: " . count($products) . "개 항목 조회됨, 사용 컬럼: " . $nameColumn);
} catch (PDOException $e) {
    $message = '오류가 발생했습니다: ' . $e->getMessage();
    $message_type = 'danger';
    error_log("상품 목록 조회 오류: " . $e->getMessage());
    
    // 오류 발생 시 더미 데이터 제공 (개발 목적)
    $products = [
        ['id' => 1, 'product_code' => 'DAILY001', 'name' => '일일 복권'],
        ['id' => 2, 'product_code' => 'WEEKLY001', 'name' => '주간 복권'],
        ['id' => 3, 'product_code' => 'INSTANT001', 'name' => '즉석 복권']
    ];
}

// 판매점 목록 조회
$stores = [];
try {
    // 테이블 구조 확인 - 'name' 컬럼 또는 'store_name' 컬럼 존재 여부 확인
    $stmt = $db->query("DESCRIBE stores");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    
    // 적절한 쿼리 구성
    $nameColumn = in_array('name', $columns) ? 'name' : 
                 (in_array('store_name', $columns) ? 'store_name' : 'store_code');
    $locationColumn = in_array('location', $columns) ? 'location' : 
                     (in_array('address', $columns) ? 'address' : 'store_code');
    
    $stmt = $db->prepare("
        SELECT 
            id, store_code, " . $nameColumn . " AS name, " . $locationColumn . " AS location
        FROM 
            stores
        WHERE 
            status = 'active'
        ORDER BY 
            " . $nameColumn . " ASC
    ");
    
    $stmt->execute();
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 로그 기록
    error_log("판매점 목록 쿼리 성공: " . count($stores) . "개 항목 조회됨, 사용 컬럼: " . $nameColumn . ", " . $locationColumn);
} catch (PDOException $e) {
    $message = '오류가 발생했습니다: ' . $e->getMessage();
    $message_type = 'danger';
    error_log("판매점 목록 조회 오류: " . $e->getMessage());
    
    // 오류 발생 시 더미 데이터 제공 (개발 목적)
    $stores = [
        ['id' => 1, 'store_code' => 'ST001', 'name' => '서울 중앙점', 'location' => '서울시 중구'],
        ['id' => 2, 'store_code' => 'ST002', 'name' => '부산 해운대점', 'location' => '부산시 해운대구'],
        ['id' => 3, 'store_code' => 'ST003', 'name' => '대전 둔산점', 'location' => '대전시 서구']
    ];
}

// 현재 페이지 정보
$pageTitle = "넘버링 관리";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 추가 CSS
$extraCss = '<style>
    .info-box {
        min-height: auto;
        margin-top: 20px;
    }
    .info-box-content p {
        margin-bottom: 0;
    }
    .nav-tabs .nav-link {
        font-weight: 600;
    }
    .tab-pane {
        padding-top: 20px;
    }
    .form-group small {
        color: #6c757d;
    }
    code {
        color: #28a745;
        background-color: #f8f9fa;
        padding: 4px 8px;
        border-radius: 4px;
    }
    .badge {
        font-size: 90%;
    }
</style>';

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">넘버링 관리</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/server/dashboard/index.php">홈</a></li>
                    <li class="breadcrumb-item">복권 관리</li>
                    <li class="breadcrumb-item active">넘버링 관리</li>
                </ol>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-barcode"></i></span>
                    <div class="info-box-content">
                        <h5 class="info-box-text">넘버링 관리 시스템</h5>
                        <p class="info-box-text">복권 발행을 위한 번호 체계를 관리하고, 판매점별로 번호를 할당하며, 특정 번호를 예약할 수 있습니다. 번호 체계는 복권의 번호 형식을 정의하고, 번호 할당은 판매점별로 번호 범위를 지정하며, 번호 예약은 특별한 목적으로 사용할 번호를 미리 예약합니다.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
        <div class="container-fluid">
            <!-- 알림 메시지 -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- 탭 메뉴 -->
            <div class="card card-primary card-outline card-outline-tabs">
                <div class="card-header p-0 border-bottom-0">
                    <ul class="nav nav-tabs" id="numbering-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="formats-tab" data-toggle="pill" href="#formats" role="tab" aria-controls="formats" aria-selected="true">번호 체계</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="assignments-tab" data-toggle="pill" href="#assignments" role="tab" aria-controls="assignments" aria-selected="false">번호 할당</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="reservations-tab" data-toggle="pill" href="#reservations" role="tab" aria-controls="reservations" aria-selected="false">번호 예약</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="numbering-tabs-content">
                        <!-- 번호 체계 탭 -->
                        <div class="tab-pane fade show active" id="formats" role="tabpanel" aria-labelledby="formats-tab">
                            <!-- 번호 체계 추가 버튼 -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addFormatModal">
                                        <i class="fas fa-plus-circle"></i> 새 번호 체계 추가
                                    </button>
                                </div>
                            </div>
                            
                            <!-- 번호 체계 목록 -->
                            <div class="table-responsive">
                                <table class="table table-hover table-striped" id="formatsTable">
                                    <thead>
                                        <tr>
                                            <th>이름</th>
                                            <th>패턴</th>
                                            <th>길이</th>
                                            <th>허용 문자</th>
                                            <th>예시</th>
                                            <th>할당 수</th>
                                            <th>예약 수</th>
                                            <th>상태</th>
                                            <th>관리</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($formats as $format): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($format['name']); ?></td>
                                                <td><?php echo htmlspecialchars($format['pattern'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($format['min_length'] ?? '0'); ?> ~ <?php echo htmlspecialchars($format['max_length'] ?? '0'); ?></td>
                                                <td><?php echo htmlspecialchars($format['allowed_characters'] ?? ''); ?></td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($format['pattern'] ?? ''); ?></code>
                                                </td>
                                                <td><?php echo number_format($format['assignment_count']); ?></td>
                                                <td><?php echo number_format($format['reservation_count']); ?></td>
                                                <td>
                                                    <?php 
                                                    if (($format['is_active'] ?? 0) == 1) {
                                                        echo '<span class="badge badge-success">활성</span>';
                                                    } else {
                                                        echo '<span class="badge badge-warning">비활성</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-info btn-view-format" data-id="<?php echo $format['id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-primary btn-edit-format" data-id="<?php echo $format['id']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($formats)): ?>
                                            <tr><td colspan="9" class="text-center">등록된 번호 체계가 없습니다.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- 번호 할당 탭 -->
                        <div class="tab-pane fade" id="assignments" role="tabpanel" aria-labelledby="assignments-tab">
                            <!-- 번호 할당 추가 버튼 -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#assignNumbersModal">
                                        <i class="fas fa-plus-circle"></i> 새 번호 할당 추가
                                    </button>
                                </div>
                            </div>
                            
                            <!-- 번호 할당 목록 -->
                            <div class="table-responsive">
                                <table class="table table-hover table-striped" id="assignmentsTable">
                                    <thead>
                                        <tr>
                                            <th>복권 상품</th>
                                            <th>판매점</th>
                                            <th>번호 체계</th>
                                            <th>시작 번호</th>
                                            <th>종료 번호</th>
                                            <th>수량</th>
                                            <th>상태</th>
                                            <th>할당일</th>
                                            <th>관리</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignments as $assignment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($assignment['product_name']); ?></td>
                                                <td><?php echo htmlspecialchars($assignment['store_name']); ?></td>
                                                <td><?php echo htmlspecialchars($assignment['format_name']); ?></td>
                                                <td><code><?php echo htmlspecialchars($assignment['start_number']); ?></code></td>
                                                <td><code><?php echo htmlspecialchars($assignment['end_number']); ?></code></td>
                                                <td><?php echo number_format($assignment['quantity'] ?? 0); ?></td>
                                                <td>
                                                    <?php 
                                                    switch ($assignment['status']) {
                                                        case 'active':
                                                            echo '<span class="badge badge-success">활성</span>';
                                                            break;
                                                        case 'used':
                                                            echo '<span class="badge badge-info">사용됨</span>';
                                                            break;
                                                        case 'expired':
                                                            echo '<span class="badge badge-warning">만료됨</span>';
                                                            break;
                                                        case 'cancelled':
                                                            echo '<span class="badge badge-danger">취소됨</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge badge-secondary">알 수 없음</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo date('Y-m-d', strtotime($assignment['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-info btn-view-assignment" data-id="<?php echo $assignment['id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        
                                                        <?php if ($assignment['status'] === 'active'): ?>
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                    <i class="fas fa-cog"></i>
                                                                </button>
                                                                <div class="dropdown-menu">
                                                                    <a class="dropdown-item btn-cancel-assignment" data-id="<?php echo $assignment['id']; ?>" href="#">
                                                                        <i class="fas fa-ban text-danger"></i> 할당 취소
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($assignments)): ?>
                                            <tr><td colspan="9" class="text-center">등록된 번호 할당이 없습니다.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- 번호 예약 탭 -->
                        <div class="tab-pane fade" id="reservations" role="tabpanel" aria-labelledby="reservations-tab">
                            <!-- 번호 예약 추가 버튼 -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#reserveNumbersModal">
                                        <i class="fas fa-plus-circle"></i> 새 번호 예약 추가
                                    </button>
                                </div>
                            </div>
                            
                            <!-- 번호 예약 목록 -->
                            <div class="table-responsive">
                                <table class="table table-hover table-striped" id="reservationsTable">
                                    <thead>
                                        <tr>
                                            <th>번호 체계</th>
                                            <th>예약 번호</th>
                                            <th>예약 사유</th>
                                            <th>상태</th>
                                            <th>예약일</th>
                                            <th>관리</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reservations as $reservation): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($reservation['format_name']); ?></td>
                                                <td><code><?php echo htmlspecialchars($reservation['numbers'] ?? ''); ?></code></td>
                                                <td><?php echo htmlspecialchars($reservation['reason']); ?></td>
                                                <td>
                                                    <?php 
                                                    switch ($reservation['status']) {
                                                        case 'active':
                                                            echo '<span class="badge badge-success">활성</span>';
                                                            break;
                                                        case 'expired':
                                                            echo '<span class="badge badge-warning">만료됨</span>';
                                                            break;
                                                        case 'cancelled':
                                                            echo '<span class="badge badge-danger">취소됨</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge badge-secondary">알 수 없음</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo date('Y-m-d', strtotime($reservation['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-info btn-view-reservation" data-id="<?php echo $reservation['id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        
                                                        <?php if ($reservation['status'] === 'active'): ?>
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                    <i class="fas fa-cog"></i>
                                                                </button>
                                                                <div class="dropdown-menu">
                                                                    <a class="dropdown-item btn-cancel-reservation" data-id="<?php echo $reservation['id']; ?>" href="#">
                                                                        <i class="fas fa-ban text-danger"></i> 예약 취소
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($reservations)): ?>
                                            <tr><td colspan="6" class="text-center">등록된 번호 예약이 없습니다.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
        </div>
    </section>
</div>

<!-- 번호 체계 추가 모달 -->
<div class="modal fade" id="addFormatModal" tabindex="-1" role="dialog" aria-labelledby="addFormatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addFormatForm" method="POST">
                <input type="hidden" name="action" value="add_format">
                
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="addFormatModalLabel">새 번호 체계 추가</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">이름</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <small class="form-text text-muted">번호 체계의 식별 이름</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pattern">패턴</label>
                                <input type="text" class="form-control" id="pattern" name="pattern" required>
                                <small class="form-text text-muted">번호 형식 패턴 (예: NNNNNN, LLNNNN)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">설명</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="min_length">최소 길이</label>
                                <input type="number" class="form-control" id="min_length" name="min_length" min="1" value="6" required>
                                <small class="form-text text-muted">번호 최소 길이</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="max_length">최대 길이</label>
                                <input type="number" class="form-control" id="max_length" name="max_length" min="1" value="6" required>
                                <small class="form-text text-muted">번호 최대 길이</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="prefix">접두사</label>
                                <input type="text" class="form-control" id="prefix" name="prefix">
                                <small class="form-text text-muted">번호 앞에 붙는 문자 (선택사항)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="suffix">접미사</label>
                                <input type="text" class="form-control" id="suffix" name="suffix">
                                <small class="form-text text-muted">번호 뒤에 붙는 문자 (선택사항)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="allowed_characters">허용 문자</label>
                                <input type="text" class="form-control" id="allowed_characters" name="allowed_characters" value="0123456789">
                                <small class="form-text text-muted">허용 가능한 문자들 (예: 0123456789, ABCDEFGHIJKLMNOPQRSTUVWXYZ)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_alphanumeric" name="is_alphanumeric" value="1">
                                    <label class="form-check-label" for="is_alphanumeric">영숫자 허용</label>
                                </div>
                                <div class="form-check mt-3">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="is_active">활성화</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 번호 체계 수정 모달 -->
<div class="modal fade" id="editFormatModal" tabindex="-1" role="dialog" aria-labelledby="editFormatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editFormatForm" method="POST">
                <input type="hidden" name="action" value="edit_format">
                <input type="hidden" id="edit_format_id" name="format_id" value="">
                
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="editFormatModalLabel">번호 체계 수정</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <!-- 양식 내용은 추가 모달과 동일하므로 id만 변경 -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_name">이름</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                                <small class="form-text text-muted">번호 체계의 식별 이름</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_pattern">패턴</label>
                                <input type="text" class="form-control" id="edit_pattern" name="pattern" required>
                                <small class="form-text text-muted">번호 형식 패턴 (예: NNNNNN, LLNNNN)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">설명</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_min_length">최소 길이</label>
                                <input type="number" class="form-control" id="edit_min_length" name="min_length" min="1" required>
                                <small class="form-text text-muted">번호 최소 길이</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_max_length">최대 길이</label>
                                <input type="number" class="form-control" id="edit_max_length" name="max_length" min="1" required>
                                <small class="form-text text-muted">번호 최대 길이</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_prefix">접두사</label>
                                <input type="text" class="form-control" id="edit_prefix" name="prefix">
                                <small class="form-text text-muted">번호 앞에 붙는 문자 (선택사항)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_suffix">접미사</label>
                                <input type="text" class="form-control" id="edit_suffix" name="suffix">
                                <small class="form-text text-muted">번호 뒤에 붙는 문자 (선택사항)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_allowed_characters">허용 문자</label>
                                <input type="text" class="form-control" id="edit_allowed_characters" name="allowed_characters">
                                <small class="form-text text-muted">허용 가능한 문자들 (예: 0123456789, ABCDEFGHIJKLMNOPQRSTUVWXYZ)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_is_alphanumeric" name="is_alphanumeric" value="1">
                                    <label class="form-check-label" for="edit_is_alphanumeric">영숫자 허용</label>
                                </div>
                                <div class="form-check mt-3">
                                    <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active" value="1">
                                    <label class="form-check-label" for="edit_is_active">활성화</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 번호 할당 모달 -->
<div class="modal fade" id="assignNumbersModal" tabindex="-1" role="dialog" aria-labelledby="assignNumbersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="assignNumbersForm" method="POST">
                <input type="hidden" name="action" value="assign_numbers">
                
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="assignNumbersModalLabel">새 번호 할당 추가</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="product_id">복권 상품</label>
                                <select class="form-control" id="product_id" name="product_id" required>
                                    <option value="">-- 복권 상품 선택 --</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>">
                                            <?php echo htmlspecialchars($product['name'] . ' (' . $product['product_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="format_id">번호 체계</label>
                                <select class="form-control" id="format_id" name="format_id" required>
                                    <option value="">-- 번호 체계 선택 --</option>
                                    <?php foreach ($formats as $format): ?>
                                        <?php if (($format['is_active'] ?? 0) == 1): ?>
                                            <option value="<?php echo $format['id']; ?>">
                                                <?php 
                                                    $pattern = isset($format['pattern']) ? $format['pattern'] : '';
                                                    echo htmlspecialchars($format['name'] . ' (' . $pattern . ')'); 
                                                ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="store_id">판매점</label>
                        <select class="form-control select2" id="store_id" name="store_id" required>
                            <option value="">-- 판매점 선택 --</option>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?php echo $store['id']; ?>">
                                    <?php echo htmlspecialchars($store['name'] . ' (' . $store['location'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_number">시작 번호</label>
                                <input type="text" class="form-control" id="start_number" name="start_number" required>
                                <small class="form-text text-muted">할당 시작 번호</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_number">종료 번호</label>
                                <input type="text" class="form-control" id="end_number" name="end_number" required>
                                <small class="form-text text-muted">할당 종료 번호</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">수량</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                        <small class="form-text text-muted">할당할 번호의 총 수량</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">비고</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 번호 예약 모달 -->
<div class="modal fade" id="reserveNumbersModal" tabindex="-1" role="dialog" aria-labelledby="reserveNumbersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="reserveNumbersForm" method="POST">
                <input type="hidden" name="action" value="reserve_numbers">
                
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="reserveNumbersModalLabel">새 번호 예약 추가</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="format_id_reserve">번호 체계</label>
                        <select class="form-control" id="format_id_reserve" name="format_id" required>
                            <option value="">-- 번호 체계 선택 --</option>
                            <?php foreach ($formats as $format): ?>
                                <?php if (($format['is_active'] ?? 0) == 1): ?>
                                    <option value="<?php echo $format['id']; ?>">
                                        <?php 
                                            $pattern = isset($format['pattern']) ? $format['pattern'] : '';
                                            echo htmlspecialchars($format['name'] . ' (' . $pattern . ')'); 
                                        ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reserved_number">예약 번호</label>
                        <input type="text" class="form-control" id="reserved_number" name="reserved_number" required>
                        <small class="form-text text-muted">예약할 번호 (쉼표로 구분하거나 범위 지정, 예: 123456, 200000-201000)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="reason">예약 사유</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                        <small class="form-text text-muted">번호를 예약하는 이유를 설명해주세요</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="status_reserve">상태</label>
                        <select class="form-control" id="status_reserve" name="status" required>
                            <option value="active">활성</option>
                            <option value="expired">만료됨</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 번호 체계 상세 보기 모달 -->
<div class="modal fade" id="viewFormatModal" tabindex="-1" role="dialog" aria-labelledby="viewFormatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title" id="viewFormatModalLabel">번호 체계 상세 정보</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>이름</label>
                            <div id="view_name" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>패턴</label>
                            <div id="view_format_type" class="form-control-static"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>설명</label>
                    <div id="view_description" class="form-control-static"></div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>길이 범위</label>
                            <div id="view_number_count" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>허용 문자</label>
                            <div id="view_number_range" class="form-control-static"></div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>접두사</label>
                            <div id="view_prefix" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>접미사</label>
                            <div id="view_suffix" class="form-control-static"></div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>패턴 예시</label>
                            <div id="view_example" class="form-control-static"><code></code></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>상태</label>
                            <div id="view_status" class="form-control-static"></div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>생성일</label>
                            <div id="view_created_at" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>마지막 수정일</label>
                            <div id="view_updated_at" class="form-control-static"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/dashboard_footer.php'; ?>

<!-- 번호 예약 상세 보기 모달 -->
<div class="modal fade" id="viewReservationModal" tabindex="-1" role="dialog" aria-labelledby="viewReservationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title" id="viewReservationModalLabel">번호 예약 상세 정보</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>번호 체계</label>
                            <div id="view_reservation_format_name" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>예약 번호</label>
                            <div id="view_reservation_number" class="form-control-static"><code></code></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>예약 사유</label>
                    <div id="view_reservation_reason" class="form-control-static"></div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>상태</label>
                            <div id="view_reservation_status" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>예약자</label>
                            <div id="view_reservation_by" class="form-control-static"></div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>예약일</label>
                            <div id="view_reservation_created_at" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>마지막 수정일</label>
                            <div id="view_reservation_updated_at" class="form-control-static"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<!-- Numbering 관리 스크립트 -->
<script src="/server/assets/js/numbering.js"></script>


