<?php
/**
 * 판매 정책 페이지
 * 
 * 이 페이지는 로또 판매 정책을 관리하는 기능을 제공합니다.
 * - 판매 가격 정책
 * - 판매 제한 정책
 * - 판매 프로모션 정책
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = get_db_connection();

// 현재 페이지 정보
$pageTitle = "판매 정책";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 메시지 초기화
$message = '';
$message_type = '';

// 정책 저장 처리
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF 토큰 검증
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("보안 토큰이 유효하지 않습니다. 페이지를 새로고침한 후 다시 시도해주세요.");
        }
        
        $policy_type = $_POST['policy_type'] ?? '';
        
        // 가격 정책 저장
        if ($policy_type === 'price') {
            $product_id = intval($_POST['product_id']);
            $price = floatval($_POST['price']);
            $min_price = floatval($_POST['min_price'] ?? 0);
            $max_price = floatval($_POST['max_price'] ?? 0);
            $effective_date = $_POST['effective_date'];
            $description = $_POST['description'];
            
            if ($product_id <= 0 || $price <= 0) {
                throw new Exception("제품 선택 및 가격 입력이 필요합니다.");
            }
            
            // 가격 정책 저장
            $query = "
                INSERT INTO price_policies
                    (product_id, price, min_price, max_price, effective_date, description, created_by, created_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    price = VALUES(price),
                    min_price = VALUES(min_price),
                    max_price = VALUES(max_price),
                    effective_date = VALUES(effective_date),
                    description = VALUES(description),
                    updated_by = VALUES(created_by),
                    updated_at = NOW()
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                $product_id, $price, $min_price, $max_price, $effective_date, $description, $_SESSION['user_id']
            ]);
            
            // 상품 정보 업데이트
            $update_product = "
                UPDATE lottery_products
                SET price = ?, updated_at = NOW()
                WHERE id = ?
            ";
            
            $stmt = $db->prepare($update_product);
            $stmt->execute([$price, $product_id]);
            
            $message = "가격 정책이 성공적으로 저장되었습니다.";
            $message_type = "success";
        }
        // 판매 제한 정책 저장
        else if ($policy_type === 'limit') {
            $product_id = intval($_POST['limit_product_id']);
            $customer_daily_limit = intval($_POST['customer_daily_limit']);
            $store_daily_limit = intval($_POST['store_daily_limit']);
            $min_purchase = intval($_POST['min_purchase']);
            $max_purchase = intval($_POST['max_purchase']);
            $effective_date = $_POST['limit_effective_date'];
            $description = $_POST['limit_description'];
            
            // 판매 제한 정책 저장
            $query = "
                INSERT INTO sales_limits
                    (product_id, customer_daily_limit, store_daily_limit, min_purchase, max_purchase, effective_date, description, created_by, created_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    customer_daily_limit = VALUES(customer_daily_limit),
                    store_daily_limit = VALUES(store_daily_limit),
                    min_purchase = VALUES(min_purchase),
                    max_purchase = VALUES(max_purchase),
                    effective_date = VALUES(effective_date),
                    description = VALUES(description),
                    updated_by = VALUES(created_by),
                    updated_at = NOW()
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                $product_id, $customer_daily_limit, $store_daily_limit, $min_purchase, $max_purchase, $effective_date, $description, $_SESSION['user_id']
            ]);
            
            $message = "판매 제한 정책이 성공적으로 저장되었습니다.";
            $message_type = "success";
        }
        // 프로모션 정책 저장
        else if ($policy_type === 'promotion') {
            $promotion_name = $_POST['promotion_name'];
            $promotion_type = $_POST['promotion_type'];
            $product_ids = isset($_POST['promotion_products']) ? $_POST['promotion_products'] : [];
            $discount_amount = floatval($_POST['discount_amount']);
            $min_quantity = intval($_POST['min_quantity']);
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $status = $_POST['promotion_status'];
            $description = $_POST['promotion_description'];
            
            // 프로모션 정책 저장
            $db->beginTransaction();
            
            $query = "
                INSERT INTO promotions
                    (name, type, discount_amount, min_quantity, start_date, end_date, status, description, created_by, created_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                $promotion_name, $promotion_type, $discount_amount, $min_quantity, $start_date, $end_date, $status, $description, $_SESSION['user_id']
            ]);
            
            $promotion_id = $db->lastInsertId();
            
            // 프로모션에 연결된 상품 저장
            if (!empty($product_ids)) {
                $product_query = "
                    INSERT INTO promotion_products
                        (promotion_id, product_id, created_at)
                    VALUES
                        (?, ?, NOW())
                ";
                
                $product_stmt = $db->prepare($product_query);
                
                foreach ($product_ids as $p_id) {
                    $product_stmt->execute([$promotion_id, $p_id]);
                }
            }
            
            $db->commit();
            
            $message = "프로모션 정책이 성공적으로 저장되었습니다.";
            $message_type = "success";
        }
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        
        $message = "오류: " . $e->getMessage();
        $message_type = "danger";
    }
}

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 복권 상품 목록 가져오기
function getLotteryProducts($db) {
    $query = "SELECT id, product_code, name, price FROM lottery_products WHERE status = 'active' ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 가격 정책 목록 가져오기
function getPricePolicies($db) {
    $query = "
        SELECT 
            pp.*,
            lp.name as product_name,
            lp.product_code,
            u.username as created_by_name
        FROM 
            price_policies pp
        JOIN 
            lottery_products lp ON pp.product_id = lp.id
        LEFT JOIN 
            users u ON pp.created_by = u.id
        ORDER BY 
            pp.effective_date DESC, lp.name
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 판매 제한 정책 목록 가져오기
function getSalesLimits($db) {
    $query = "
        SELECT 
            sl.*,
            lp.name as product_name,
            lp.product_code,
            u.username as created_by_name
        FROM 
            sales_limits sl
        JOIN 
            lottery_products lp ON sl.product_id = lp.id
        LEFT JOIN 
            users u ON sl.created_by = u.id
        ORDER BY 
            sl.effective_date DESC, lp.name
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 프로모션 정책 목록 가져오기
function getPromotions($db) {
    $query = "
        SELECT 
            p.*,
            u.username as created_by_name,
            (
                SELECT GROUP_CONCAT(lp.name SEPARATOR ', ')
                FROM promotion_products pp
                JOIN lottery_products lp ON pp.product_id = lp.id
                WHERE pp.promotion_id = p.id
            ) as product_names
        FROM 
            promotions p
        LEFT JOIN 
            users u ON p.created_by = u.id
        ORDER BY 
            p.start_date DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 데이터 가져오기
$lottery_products = getLotteryProducts($db);
$price_policies = getPricePolicies($db);
$sales_limits = getSalesLimits($db);
$promotions = getPromotions($db);

// 헤더 및 사이드바 포함
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
                    <li class="breadcrumb-item">판매 관리</li>
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
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

            <!-- 정책 요약 -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?php echo isset($price_policies) ? count($price_policies) : 0; ?></h3>
                            <p>가격 정책</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php echo isset($sales_limits) ? count($sales_limits) : 0; ?></h3>
                            <p>판매 제한 정책</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-ban"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?php echo isset($promotions) ? count($promotions) : 0; ?></h3>
                            <p>프로모션 정책</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-gift"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?php echo date('Y-m-d'); ?></h3>
                            <p>현재 날짜</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 탭 내비게이션 -->
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link active" href="#price-policy" data-toggle="tab">가격 정책</a></li>
                        <li class="nav-item"><a class="nav-link" href="#limit-policy" data-toggle="tab">판매 제한 정책</a></li>
                        <li class="nav-item"><a class="nav-link" href="#promotion-policy" data-toggle="tab">판매 프로모션 정책</a></li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- 가격 정책 탭 -->
                        <div class="tab-pane active" id="price-policy">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="card card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title">가격 정책 설정</h3>
                                        </div>
                                        <form action="policy.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="policy_type" value="price">
                                            
                                            <div class="card-body">
                                                <div class="form-group">
                                                    <label for="product_id">복권 상품</label>
                                                    <select class="form-control" id="product_id" name="product_id" required>
                                                        <option value="">상품 선택</option>
                                                        <?php foreach ($lottery_products as $product): ?>
                                                            <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>">
                                                                <?php echo $product['name'] . ' (' . $product['product_code'] . ')'; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="price">판매 가격</label>
                                                    <div class="input-group">
                                                        <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">NPR</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="min_price">최소 가격</label>
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" id="min_price" name="min_price" min="0" step="0.01">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text">NPR</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="max_price">최대 가격</label>
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" id="max_price" name="max_price" min="0" step="0.01">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text">NPR</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="effective_date">적용 일자</label>
                                                    <input type="date" class="form-control" id="effective_date" name="effective_date" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="description">설명</label>
                                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                                </div>
                                            </div>
                                            <div class="card-footer">
                                                <button type="submit" class="btn btn-primary">저장</button>
                                                <button type="reset" class="btn btn-default float-right">초기화</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">가격 정책 목록</h3>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>상품</th>
                                                        <th>가격</th>
                                                        <th>적용 일자</th>
                                                        <th>설명</th>
                                                        <th>등록자</th>
                                                        <th>등록일</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($price_policies)): ?>
                                                        <tr>
                                                            <td colspan="6" class="text-center">등록된 가격 정책이 없습니다.</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($price_policies as $policy): ?>
                                                            <tr>
                                                                <td><?php echo $policy['product_name'] . ' (' . $policy['product_code'] . ')'; ?></td>
                                                                <td><?php echo number_format($policy['price'], 2); ?> NPR</td>
                                                                <td><?php echo date('Y-m-d', strtotime($policy['effective_date'])); ?></td>
                                                                <td><?php echo htmlspecialchars($policy['description']); ?></td>
                                                                <td><?php echo htmlspecialchars($policy['created_by_name']); ?></td>
                                                                <td><?php echo date('Y-m-d', strtotime($policy['created_at'])); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 판매 제한 정책 탭 -->
                        <div class="tab-pane" id="limit-policy">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="card card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title">판매 제한 정책 설정</h3>
                                        </div>
                                        <form action="policy.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="policy_type" value="limit">
                                            
                                            <div class="card-body">
                                                <div class="form-group">
                                                    <label for="limit_product_id">복권 상품</label>
                                                    <select class="form-control" id="limit_product_id" name="limit_product_id" required>
                                                        <option value="">상품 선택</option>
                                                        <?php foreach ($lottery_products as $product): ?>
                                                            <option value="<?php echo $product['id']; ?>">
                                                                <?php echo $product['name'] . ' (' . $product['product_code'] . ')'; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="customer_daily_limit">고객별 일일 한도</label>
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" id="customer_daily_limit" name="customer_daily_limit" min="0">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text">장</span>
                                                                </div>
                                                            </div>
                                                            <small class="form-text text-muted">0은 무제한</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="store_daily_limit">판매점별 일일 한도</label>
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" id="store_daily_limit" name="store_daily_limit" min="0">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text">장</span>
                                                                </div>
                                                            </div>
                                                            <small class="form-text text-muted">0은 무제한</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="min_purchase">최소 구매 수량</label>
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" id="min_purchase" name="min_purchase" min="1" value="1">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text">장</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="max_purchase">최대 구매 수량</label>
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" id="max_purchase" name="max_purchase" min="1" value="10">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text">장</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="limit_effective_date">적용 일자</label>
                                                    <input type="date" class="form-control" id="limit_effective_date" name="limit_effective_date" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="limit_description">설명</label>
                                                    <textarea class="form-control" id="limit_description" name="limit_description" rows="3"></textarea>
                                                </div>
                                            </div>
                                            <div class="card-footer">
                                                <button type="submit" class="btn btn-primary">저장</button>
                                                <button type="reset" class="btn btn-default float-right">초기화</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">판매 제한 정책 목록</h3>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>상품</th>
                                                        <th>고객별 일일 한도</th>
                                                        <th>판매점별 일일 한도</th>
                                                        <th>구매 수량 제한</th>
                                                        <th>적용 일자</th>
                                                        <th>등록자</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($sales_limits)): ?>
                                                        <tr>
                                                            <td colspan="6" class="text-center">등록된 판매 제한 정책이 없습니다.</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($sales_limits as $limit): ?>
                                                            <tr>
                                                                <td><?php echo $limit['product_name'] . ' (' . $limit['product_code'] . ')'; ?></td>
                                                                <td>
                                                                    <?php 
                                                                    echo ($limit['customer_daily_limit'] > 0) 
                                                                        ? number_format($limit['customer_daily_limit']) . '장' 
                                                                        : '무제한'; 
                                                                    ?>
                                                                </td>
                                                                <td>
                                                                    <?php 
                                                                    echo ($limit['store_daily_limit'] > 0) 
                                                                        ? number_format($limit['store_daily_limit']) . '장' 
                                                                        : '무제한'; 
                                                                    ?>
                                                                </td>
                                                                <td><?php echo number_format($limit['min_purchase']) . '~' . number_format($limit['max_purchase']) . '장'; ?></td>
                                                                <td><?php echo date('Y-m-d', strtotime($limit['effective_date'])); ?></td>
                                                                <td><?php echo htmlspecialchars($limit['created_by_name']); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 판매 프로모션 정책 탭 -->
                        <div class="tab-pane" id="promotion-policy">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="card card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title">판매 프로모션 정책 설정</h3>
                                        </div>
                                        <form action="policy.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="policy_type" value="promotion">
                                            
                                            <div class="card-body">
                                                <div class="form-group">
                                                    <label for="promotion_name">프로모션 이름</label>
                                                    <input type="text" class="form-control" id="promotion_name" name="promotion_name" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="promotion_type">프로모션 유형</label>
                                                    <select class="form-control" id="promotion_type" name="promotion_type" required>
                                                        <option value="percentage">비율 할인 (%)</option>
                                                        <option value="fixed">고정 금액 할인 (NPR)</option>
                                                        <option value="bonus">보너스 티켓</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="promotion_products">적용 상품</label>
                                                    <select class="form-control select2" id="promotion_products" name="promotion_products[]" multiple data-placeholder="상품 선택" required>
                                                        <?php foreach ($lottery_products as $product): ?>
                                                            <option value="<?php echo $product['id']; ?>">
                                                                <?php echo $product['name'] . ' (' . $product['product_code'] . ')'; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="discount_amount">할인 금액/비율</label>
                                                            <input type="number" class="form-control" id="discount_amount" name="discount_amount" min="0" step="0.01" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="min_quantity">최소 구매 수량</label>
                                                            <input type="number" class="form-control" id="min_quantity" name="min_quantity" min="1" value="1" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="start_date">시작일</label>
                                                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="end_date">종료일</label>
                                                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="promotion_status">상태</label>
                                                    <select class="form-control" id="promotion_status" name="promotion_status" required>
                                                        <option value="active">활성</option>
                                                        <option value="inactive">비활성</option>
                                                        <option value="scheduled">예약</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="promotion_description">설명</label>
                                                    <textarea class="form-control" id="promotion_description" name="promotion_description" rows="3"></textarea>
                                                </div>
                                            </div>
                                            <div class="card-footer">
                                                <button type="submit" class="btn btn-primary">저장</button>
                                                <button type="reset" class="btn btn-default float-right">초기화</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">판매 프로모션 정책 목록</h3>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>프로모션명</th>
                                                        <th>유형</th>
                                                        <th>대상 상품</th>
                                                        <th>할인</th>
                                                        <th>기간</th>
                                                        <th>상태</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($promotions)): ?>
                                                        <tr>
                                                            <td colspan="6" class="text-center">등록된 프로모션 정책이 없습니다.</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($promotions as $promotion): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($promotion['name']); ?></td>
                                                                <td>
                                                                    <?php 
                                                                    $type_text = '';
                                                                    switch ($promotion['type']) {
                                                                        case 'percentage':
                                                                            $type_text = '비율 할인';
                                                                            break;
                                                                        case 'fixed':
                                                                            $type_text = '고정 금액 할인';
                                                                            break;
                                                                        case 'bonus':
                                                                            $type_text = '보너스 티켓';
                                                                            break;
                                                                        default:
                                                                            $type_text = '기타';
                                                                    }
                                                                    echo $type_text;
                                                                    ?>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($promotion['product_names'] ?? '전체 상품'); ?></td>
                                                                <td>
                                                                    <?php 
                                                                    if ($promotion['type'] === 'percentage') {
                                                                        echo number_format($promotion['discount_amount']) . '%';
                                                                    } else if ($promotion['type'] === 'fixed') {
                                                                        echo number_format($promotion['discount_amount']) . ' NPR';
                                                                    } else if ($promotion['type'] === 'bonus') {
                                                                        echo '+' . number_format($promotion['discount_amount']) . '장';
                                                                    }
                                                                    
                                                                    if ($promotion['min_quantity'] > 1) {
                                                                        echo ' (' . number_format($promotion['min_quantity']) . '장 이상)';
                                                                    }
                                                                    ?>
                                                                </td>
                                                                <td>
                                                                    <?php 
                                                                    echo date('Y-m-d', strtotime($promotion['start_date'])) . ' ~ ' . 
                                                                         date('Y-m-d', strtotime($promotion['end_date']));
                                                                    ?>
                                                                </td>
                                                                <td>
                                                                    <?php 
                                                                    $status_class = '';
                                                                    $status_text = '';
                                                                    
                                                                    switch ($promotion['status']) {
                                                                        case 'active':
                                                                            $status_class = 'badge badge-success';
                                                                            $status_text = '활성';
                                                                            break;
                                                                        case 'inactive':
                                                                            $status_class = 'badge badge-secondary';
                                                                            $status_text = '비활성';
                                                                            break;
                                                                        case 'scheduled':
                                                                            $status_class = 'badge badge-info';
                                                                            $status_text = '예약';
                                                                            break;
                                                                        default:
                                                                            $status_class = 'badge badge-dark';
                                                                            $status_text = '기타';
                                                                    }
                                                                    ?>
                                                                    <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../templates/footer.php'; ?>

<script>
$(function() {
    // 복권 상품 선택 시 가격 자동 설정
    $('#product_id').change(function() {
        const selectedOption = $(this).find('option:selected');
        if (selectedOption.length > 0) {
            const price = selectedOption.data('price');
            if (price) {
                $('#price').val(price);
            }
        }
    });
    
    // 날짜 필드 기본값 설정
    const today = new Date().toISOString().split('T')[0];
    $('#effective_date, #limit_effective_date, #start_date').val(today);
    
    // 종료일은 기본적으로 30일 후로 설정
    const endDate = new Date();
    endDate.setDate(endDate.getDate() + 30);
    $('#end_date').val(endDate.toISOString().split('T')[0]);
    
    // Select2 초기화
    $('.select2').select2();
    
    // 프로모션 유형 변경 시 라벨 업데이트
    $('#promotion_type').change(function() {
        const type = $(this).val();
        let label = '할인 금액/비율';
        
        switch (type) {
            case 'percentage':
                label = '할인 비율 (%)';
                break;
            case 'fixed':
                label = '할인 금액 (NPR)';
                break;
            case 'bonus':
                label = '보너스 티켓 (장)';
                break;
        }
        
        $('label[for="discount_amount"]').text(label);
    });
    
    // 각 탭의 테이블에 DataTable 적용
    $('table').DataTable({
        "paging": true,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "url": "/assets/js/Korean.json"
        }
    });
});
</script>

    </div>
</section>
<!-- /.content -->

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>
