<?php
/**
 * 복권 상품 관리 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
// 데이터베이스 연결
$db = get_db_connection();

// DB 연결 오류 로깅
if ($db instanceof MockPDO) {
    error_log("데이터베이스 연결 실패, MockPDO가 사용됨", 0);
} else {
    error_log("데이터베이스 연결 성공: ".DB_NAME, 0);
}

// 현재 페이지 정보
$pageTitle = "복권 상품 관리";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 메시지 초기화
$message = '';
$message_type = '';

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        // 폼 데이터 가져오기
        $product_code = $_POST['product_code'] ?? '';
        $name = $_POST['name'] ?? '';
        $price = (float)($_POST['price'] ?? 0);
        $number_format = $_POST['number_format'] ?? '';
        $description = $_POST['description'] ?? '';
        $draw_schedule = $_POST['draw_schedule'] ?? '';
        $status = $_POST['status'] ?? '';
        $prize_structure = $_POST['prize_structure'] ?? '';
        
        // 기본 검증
        if (empty($product_code) || empty($name) || $price <= 0) {
            $message = '모든 필수 필드를 입력해주세요.';
            $message_type = 'danger';
        } else {
            try {
                // 상품 코드 중복 확인
                $stmt = $db->prepare("SELECT COUNT(*) FROM lottery_products WHERE product_code = :product_code");
                $stmt->bindParam(':product_code', $product_code);
                $stmt->execute();
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $message = '이미 사용 중인 상품 코드입니다.';
                    $message_type = 'danger';
                } else {
                    // 새 상품 추가
                    $insertSQL = "INSERT INTO lottery_products (product_code, name, description, price, number_format, draw_schedule, status, created_by) 
                                 VALUES (:product_code, :name, :description, :price, :number_format, :draw_schedule, :status, :created_by)";
                    
                    $stmt = $db->prepare($insertSQL);
                    $stmt->bindParam(':product_code', $product_code);
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':price', $price);
                    $stmt->bindParam(':number_format', $number_format);
                    $stmt->bindParam(':draw_schedule', $draw_schedule);
                    
                    // status 값 매핑
                    $mappedStatus = $status;
                    if ($status === 'preparing') {
                        $mappedStatus = 'preparation';
                    }
                    $stmt->bindParam(':status', $mappedStatus);
                    
                    // 현재는 고정값으로 설정 (향후 세션에서 가져오도록 수정 필요)
                    $created_by = 1;
                    $stmt->bindParam(':created_by', $created_by);
                    
                    if ($stmt->execute()) {
                        $message = '복권 상품이 성공적으로 추가되었습니다.';
                        $message_type = 'success';
                        
                        // 로그 기록
                        error_log("복권 상품 추가 성공: $product_code - $name");
                    } else {
                        $message = '상품 추가 중 오류가 발생했습니다.';
                        $message_type = 'danger';
                        error_log("복권 상품 추가 실패: " . print_r($stmt->errorInfo(), true));
                    }
                }
            } catch (PDOException $e) {
                error_log("DB 오류: " . $e->getMessage());
                $message = '데이터베이스 작업 중 오류가 발생했습니다.';
                $message_type = 'danger';
            }
        }
    } else if ($_POST['action'] === 'edit' && isset($_POST['product_id'])) {
        // 상품 편집 로직
        $productId = (int)$_POST['product_id'];
        $product_code = $_POST['product_code'] ?? '';
        $name = $_POST['name'] ?? '';
        $price = (float)($_POST['price'] ?? 0);
        $number_format = $_POST['number_format'] ?? '';
        $description = $_POST['description'] ?? '';
        $draw_schedule = $_POST['draw_schedule'] ?? '';
        $status = $_POST['status'] ?? '';
        $prize_structure = $_POST['prize_structure'] ?? '';
        
        // 기본 검증
        if (empty($product_code) || empty($name) || $price <= 0) {
            $message = '모든 필수 필드를 입력해주세요.';
            $message_type = 'danger';
        } else {
            try {
                // 상품 코드 중복 확인 (현재 상품 제외)
                $stmt = $db->prepare("SELECT COUNT(*) FROM lottery_products WHERE product_code = :product_code AND id != :id");
                $stmt->bindParam(':product_code', $product_code);
                $stmt->bindParam(':id', $productId);
                $stmt->execute();
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $message = '이미 사용 중인 상품 코드입니다.';
                    $message_type = 'danger';
                } else {
                    // 상품 업데이트
                    $updateSQL = "UPDATE lottery_products SET 
                                   product_code = :product_code,
                                   name = :name,
                                   description = :description,
                                   price = :price,
                                   number_format = :number_format,
                                   draw_schedule = :draw_schedule,
                                   status = :status,
                                   updated_by = :updated_by
                                   WHERE id = :id";
                    
                    $stmt = $db->prepare($updateSQL);
                    $stmt->bindParam(':product_code', $product_code);
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':price', $price);
                    $stmt->bindParam(':number_format', $number_format);
                    $stmt->bindParam(':draw_schedule', $draw_schedule);
                    
                    // status 값 매핑
                    $mappedStatus = $status;
                    if ($status === 'preparing') {
                        $mappedStatus = 'preparation';
                    }
                    $stmt->bindParam(':status', $mappedStatus);
                    
                    // 현재는 고정값으로 설정 (향후 세션에서 가져오도록 수정 필요)
                    $updated_by = 1;
                    $stmt->bindParam(':updated_by', $updated_by);
                    $stmt->bindParam(':id', $productId);
                    
                    if ($stmt->execute()) {
                        $message = '복권 상품이 성공적으로 수정되었습니다.';
                        $message_type = 'success';
                        
                        // 로그 기록
                        error_log("복권 상품 수정 성공: $product_code - $name");
                    } else {
                        $message = '상품 수정 중 오류가 발생했습니다.';
                        $message_type = 'danger';
                        error_log("복권 상품 수정 실패: " . print_r($stmt->errorInfo(), true));
                    }
                }
            } catch (PDOException $e) {
                error_log("DB 오류: " . $e->getMessage());
                $message = '데이터베이스 작업 중 오류가 발생했습니다.';
                $message_type = 'danger';
            }
        }
    } else if ($_POST['action'] === 'delete' && isset($_POST['product_id'])) {
        // 상품 삭제 로직
        $productId = (int)$_POST['product_id'];
        
        try {
            $stmt = $db->prepare("DELETE FROM lottery_products WHERE id = :id");
            $stmt->bindParam(':id', $productId);
            
            if ($stmt->execute()) {
                $message = '복권 상품이 성공적으로 삭제되었습니다.';
                $message_type = 'success';
            } else {
                $message = '상품 삭제 중 오류가 발생했습니다.';
                $message_type = 'danger';
                error_log("복권 상품 삭제 실패: " . print_r($stmt->errorInfo(), true));
            }
        } catch (PDOException $e) {
            error_log("삭제 오류: " . $e->getMessage());
            $message = '데이터베이스 작업 중 오류가 발생했습니다.';
            $message_type = 'danger';
        }
    } else if ($_POST['action'] === 'get_product' && isset($_POST['product_id'])) {
        // AJAX 요청: 상품 정보 가져오기
        $productId = (int)$_POST['product_id'];
        
        try {
            $stmt = $db->prepare("SELECT * FROM lottery_products WHERE id = :id");
            $stmt->bindParam(':id', $productId);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                // JSON 응답 반환
                header('Content-Type: application/json');
                echo json_encode($product);
                exit;
            } else {
                http_response_code(404);
                echo json_encode(['error' => '상품을 찾을 수 없습니다.']);
                exit;
            }
        } catch (PDOException $e) {
            error_log("상품 조회 오류: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => '데이터베이스 오류가 발생했습니다.']);
            exit;
        }
    }
}

// 복권 상품 목록 가져오기
$products = [];
try {
    $stmt = $db->prepare("SELECT * FROM lottery_products ORDER BY created_at DESC");
    $stmt->execute();
    $dbProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($dbProducts)) {
        $products = $dbProducts;
    } else {
        // DB에 데이터가 없으면 샘플 데이터 추가 (최초 실행 시)
        if (!isset($_POST['action'])) { // 폼 제출 중이 아닌 경우만 샘플 데이터 추가
            $sampleProducts = [
                [
                    'product_code' => 'DAILY001',
                    'name' => '일일 복권',
                    'description' => '매일 추첨되는 기본 복권 상품입니다.',
                    'price' => 2000,
                    'number_format' => '6digit',
                    'draw_schedule' => '매일 18:00',
                    'status' => 'active'
                ],
                [
                    'product_code' => 'WEEKLY001',
                    'name' => '주간 복권',
                    'description' => '매주 일요일에 추첨되는 주간 복권입니다.',
                    'price' => 5000,
                    'number_format' => '6digit',
                    'draw_schedule' => '매주 일요일 15:00',
                    'status' => 'active'
                ]
            ];
            
            // 샘플 데이터 삽입
            $insertSQL = "INSERT INTO lottery_products (product_code, name, description, price, number_format, draw_schedule, status, created_by) 
                           VALUES (:product_code, :name, :description, :price, :number_format, :draw_schedule, :status, :created_by)";
            
            foreach ($sampleProducts as $product) {
                try {
                    $stmt = $db->prepare($insertSQL);
                    $stmt->bindParam(':product_code', $product['product_code']);
                    $stmt->bindParam(':name', $product['name']);
                    $stmt->bindParam(':description', $product['description']);
                    $stmt->bindParam(':price', $product['price']);
                    $stmt->bindParam(':number_format', $product['number_format']);
                    $stmt->bindParam(':draw_schedule', $product['draw_schedule']);
                    
                    // status 값 매핑
                    $mappedStatus = $product['status'];
                    if ($mappedStatus === 'preparing') {
                        $mappedStatus = 'preparation';
                    }
                    $stmt->bindParam(':status', $mappedStatus);
                    
                    // 고정 created_by 값
                    $created_by = 1;
                    $stmt->bindParam(':created_by', $created_by);
                    
                    $stmt->execute();
                } catch (PDOException $e) {
                    error_log("샘플 데이터 추가 실패: " . $e->getMessage());
                }
            }
            
            // 샘플 데이터 추가 후 다시 조회
            $stmt = $db->prepare("SELECT * FROM lottery_products ORDER BY created_at DESC");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    error_log("조회 오류: " . $e->getMessage());
    $message = '상품 목록을 가져오는 중 오류가 발생했습니다.';
    $message_type = 'danger';
    
    // 오류 발생 시 모의 데이터 사용
    $products = [
        [
            'id' => 1,
            'product_code' => 'DAILY001',
            'name' => '일일 복권',
            'price' => 2000,
            'number_format' => '6자리 숫자',
            'draw_schedule' => '매일 18:00',
            'status' => 'active',
            'updated_at' => '2025-05-15'
        ],
        [
            'id' => 2, 
            'product_code' => 'WEEKLY001',
            'name' => '주간 복권',
            'price' => 5000,
            'number_format' => '6자리 숫자',
            'draw_schedule' => '매주 일요일 15:00',
            'status' => 'active',
            'updated_at' => '2025-05-14'
        ]
    ];
}

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?php echo $pageTitle; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/index.php">홈</a></li>
                    <li class="breadcrumb-item">복권 관리</li>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
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
        
        <!-- 상품 추가 버튼 -->
        <div class="row mb-3">
            <div class="col-12">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addProductModal">
                    <i class="fas fa-plus-circle"></i> 새 복권 상품 추가
                </button>
            </div>
        </div>
        
        <!-- 복권 상품 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">복권 상품 목록</h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" id="productSearchInput" class="form-control float-right" placeholder="검색">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap" id="productTable">
                    <thead>
                        <tr>
                            <th>상품코드</th>
                            <th>상품명</th>
                            <th>가격</th>
                            <th>번호 형식</th>
                            <th>추첨 일정</th>
                            <th>상태</th>
                            <th>마지막 수정</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="text-center">등록된 복권 상품이 없습니다.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>₹ <?php echo number_format($product['price']); ?></td>
                            <td><?php
                                $format_display = '';
                                switch ($product['number_format']) {
                                    case '6digit': $format_display = '6자리 숫자'; break;
                                    case '8digit': $format_display = '8자리 숫자'; break;
                                    case '10digit': $format_display = '10자리 숫자'; break;
                                    case '12digit': $format_display = '12자리 숫자'; break;
                                    default: $format_display = $product['number_format']; break;
                                }
                                echo htmlspecialchars($format_display);
                            ?></td>
                            <td><?php echo htmlspecialchars($product['draw_schedule']); ?></td>
                            <td>
                                <?php 
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    switch ($product['status']) {
                                        case 'active':
                                            $status_class = 'success';
                                            $status_text = '활성';
                                            break;
                                        case 'preparing':
                                            $status_class = 'warning';
                                            $status_text = '준비중';
                                            break;
                                        case 'inactive':
                                            $status_class = 'secondary';
                                            $status_text = '비활성';
                                            break;
                                        default:
                                            $status_class = 'info';
                                            $status_text = $product['status'];
                                            break;
                                    }
                                ?>
                                <span class="badge badge-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </td>
                            <td><?php echo isset($product['updated_at']) ? date('Y-m-d', strtotime($product['updated_at'])) : ''; ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info edit-product" data-id="<?php echo (int)$product['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete-product" data-id="<?php echo (int)$product['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <ul class="pagination pagination-sm m-0 float-right">
                    <li class="page-item"><a class="page-link" href="#">&laquo;</a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">&raquo;</a></li>
                </ul>
            </div>
        </div>
        <!-- /.card -->
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

<!-- 상품 추가 모달 -->
<div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="addProductModalLabel">새 복권 상품 추가</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="product_code">상품 코드</label>
                                <input type="text" class="form-control" id="product_code" name="product_code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">상품명</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="price">가격 (NPR)</label>
                                <input type="number" class="form-control" id="price" name="price" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="number_format">번호 형식</label>
                                <select class="form-control" id="number_format" name="number_format" required>
                                    <option value="6digit">6자리 숫자</option>
                                    <option value="8digit">8자리 숫자</option>
                                    <option value="10digit">10자리 숫자</option>
                                    <option value="12digit">12자리 숫자</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">상품 설명</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="draw_schedule">추첨 일정</label>
                                <input type="text" class="form-control" id="draw_schedule" name="draw_schedule" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">상태</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="active">활성</option>
                                    <option value="preparing">준비중</option>
                                    <option value="inactive">비활성</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="prize_structure">당첨 구조</label>
                        <textarea class="form-control" id="prize_structure" name="prize_structure" rows="4" placeholder="예: 1등: 5,000,000 NPR (1명), 2등: 1,000,000 NPR (5명), ..."></textarea>
                    </div>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo md5(uniqid()); ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 상품 편집 모달 -->
<div class="modal fade" id="editProductModal" tabindex="-1" role="dialog" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProductModalLabel">복권 상품 편집</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="post" id="editProductForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_product_code">상품 코드</label>
                                <input type="text" class="form-control" id="edit_product_code" name="product_code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_name">상품명</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_price">가격 (NPR)</label>
                                <input type="number" class="form-control" id="edit_price" name="price" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_number_format">번호 형식</label>
                                <select class="form-control" id="edit_number_format" name="number_format" required>
                                    <option value="6digit">6자리 숫자</option>
                                    <option value="8digit">8자리 숫자</option>
                                    <option value="10digit">10자리 숫자</option>
                                    <option value="12digit">12자리 숫자</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">상품 설명</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_draw_schedule">추첨 일정</label>
                                <input type="text" class="form-control" id="edit_draw_schedule" name="draw_schedule" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_status">상태</label>
                                <select class="form-control" id="edit_status" name="status" required>
                                    <option value="active">활성</option>
                                    <option value="preparing">준비중</option>
                                    <option value="inactive">비활성</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_prize_structure">당첨 구조</label>
                        <textarea class="form-control" id="edit_prize_structure" name="prize_structure" rows="4" placeholder="예: 1등: 5,000,000 NPR (1명), 2등: 1,000,000 NPR (5명), ..."></textarea>
                    </div>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    <input type="hidden" name="csrf_token" value="<?php echo md5(uniqid()); ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">변경사항 저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 스크립트 -->
<?php
$inlineJs = <<<EOT
$(document).ready(function() {
    // 검색 기능
    $("#productSearchInput").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#productTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});
EOT;

// 상품 관리를 위한 스크립트 추가
$extraJs = <<<EXTRAJS
<script>
$(document).ready(function() {
    // 검색 기능
    $("#productSearchInput").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#productTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    
    // 편집 버튼 클릭 이벤트
    $('.edit-product').on('click', function() {
        var productId = $(this).data('id');
        
        // AJAX로 상품 데이터 가져오기
        $.ajax({
            url: '',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_product',
                product_id: productId
            },
            success: function(product) {
                console.log('상품 정보 로드:', product);
                
                // 편집 모달에 상품 데이터 채우기
                $('#edit_product_id').val(product.id);
                $('#edit_product_code').val(product.product_code);
                $('#edit_name').val(product.name);
                $('#edit_price').val(product.price);
                $('#edit_number_format').val(product.number_format);
                $('#edit_description').val(product.description);
                $('#edit_draw_schedule').val(product.draw_schedule);
                $('#edit_status').val(product.status);
                $('#edit_prize_structure').val(product.prize_structure);
                
                // 편집 모달 열기
                $('#editProductModal').modal('show');
            },
            error: function(xhr, status, error) {
                console.error('상품 데이터 로드 실패:', error);
                alert('상품 정보를 로드하는 중 오류가 발생했습니다: ' + error);
            }
        });
    });
    
    // 삭제 버튼 클릭 이벤트
    $('.delete-product').on('click', function() {
        var productId = $(this).data('id');
        
        if (confirm('정말로 이 상품을 삭제하시겠습니까?')) {
            // AJAX 요청으로 삭제 처리
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    action: 'delete',
                    product_id: productId
                },
                success: function(response) {
                    // 새로고침하여 목록 업데이트
                    location.reload();
                },
                error: function(xhr, status, error) {
                    alert('삭제 중 오류가 발생했습니다: ' + error);
                }
            });
        }
    });
});
</script>
EXTRAJS;

// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';

// 추가 스크립트 출력
echo $extraJs;
?>