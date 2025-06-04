<?php
/**
 * 복권 발행 관리 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "복권 발행 관리";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = get_db_connection();

// 작업 메시지 초기화
$message = '';
$message_type = '';

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 발행 계획 추가/수정 처리
    if (isset($_POST['action']) && ($_POST['action'] === 'add_plan' || $_POST['action'] === 'edit_plan')) {
        $product_id = (int) sanitizeInput($_POST['product_id']);
        $issue_date = sanitizeInput($_POST['issue_date']);
        $total_tickets = (int) sanitizeInput($_POST['total_tickets']);
        $batch_size = (int) sanitizeInput($_POST['batch_size']);
        $start_number = sanitizeInput($_POST['start_number']);
        $notes = sanitizeInput($_POST['notes']);
        $status = sanitizeInput($_POST['status']);
        
        try {
            if ($_POST['action'] === 'add_plan') {
                // 발행 계획 추가
                $query = "INSERT INTO issue_plans 
                         (product_id, issue_date, total_tickets, batch_size, start_number, notes, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $product_id, PDO::PARAM_INT);
                $stmt->bindParam(2, $issue_date, PDO::PARAM_STR);
                $stmt->bindParam(3, $total_tickets, PDO::PARAM_INT);
                $stmt->bindParam(4, $batch_size, PDO::PARAM_INT);
                $stmt->bindParam(5, $start_number, PDO::PARAM_STR);
                $stmt->bindParam(6, $notes, PDO::PARAM_STR);
                $stmt->bindParam(7, $status, PDO::PARAM_STR);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $message = '발행 계획이 성공적으로 추가되었습니다.';
                    $message_type = 'success';
                    
                    // 활동 로그 기록
                    callLogActivity('발행 계획 추가: 상품ID ' . $product_id . ' - ' . $issue_date, 'issue_plans');
                } else {
                    $message = '발행 계획 추가에 실패했습니다.';
                    $message_type = 'danger';
                }
            } else {
                // 발행 계획 수정
                $plan_id = (int) sanitizeInput($_POST['plan_id']);
                
                $query = "UPDATE issue_plans SET 
                         product_id = ?, 
                         issue_date = ?, 
                         total_tickets = ?, 
                         batch_size = ?, 
                         start_number = ?, 
                         notes = ?, 
                         status = ?, 
                         updated_at = NOW() 
                         WHERE id = ?";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $product_id, PDO::PARAM_INT);
                $stmt->bindParam(2, $issue_date, PDO::PARAM_STR);
                $stmt->bindParam(3, $total_tickets, PDO::PARAM_INT);
                $stmt->bindParam(4, $batch_size, PDO::PARAM_INT);
                $stmt->bindParam(5, $start_number, PDO::PARAM_STR);
                $stmt->bindParam(6, $notes, PDO::PARAM_STR);
                $stmt->bindParam(7, $status, PDO::PARAM_STR);
                $stmt->bindParam(8, $plan_id, PDO::PARAM_INT);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $message = '발행 계획이 성공적으로 수정되었습니다.';
                    $message_type = 'success';
                    
                    // 활동 로그 기록
                    callLogActivity('발행 계획 수정: ID ' . $plan_id, 'issue_plans');
                } else {
                    $message = '발행 계획 수정에 실패했습니다.';
                    $message_type = 'danger';
                }
            }
        } catch (Exception $e) {
            $message = '오류가 발생했습니다: ' . $e->getMessage();
            $message_type = 'danger';
            error_log("Database error: " . $e->getMessage());
        }
    }
    
    // 발행 처리
    if (isset($_POST['action']) && $_POST['action'] === 'issue') {
        $plan_id = (int) sanitizeInput($_POST['plan_id']);
        
        try {
            // 발행 계획 정보 조회
            $query = "SELECT ip.*, lp.product_code, lp.name AS product_name 
                     FROM issue_plans ip
                     JOIN lottery_products lp ON ip.product_id = lp.id
                     WHERE ip.id = ?";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $plan_id, PDO::PARAM_INT);
            $stmt->execute();
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($plan) {
                // 발행 진행 상태로 변경
                $stmt = $db->prepare("UPDATE issue_plans SET status = 'in_progress', updated_at = NOW() WHERE id = ?");
                $stmt->bindParam(1, $plan_id, PDO::PARAM_INT);
                $stmt->execute();
                
                // 발행 작업 큐에 추가
                $query = "INSERT INTO issue_queue (plan_id, status, total_tickets, processed_tickets) 
                         VALUES (?, 'pending', ?, 0)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $plan_id, PDO::PARAM_INT);
                $stmt->bindParam(2, $plan['total_tickets'], PDO::PARAM_INT);
                $stmt->execute();
                $queue_id = $db->lastInsertId();
                
                // 발행 이력 추가
                $query = "INSERT INTO issue_history (plan_id, queue_id, issued_by, status, notes) 
                         VALUES (?, ?, ?, 'started', '발행 작업이 시작되었습니다.')";
                $user_id = getCurrentUserId(); // 현재 로그인한 사용자 ID
                $status = 'started';
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $plan_id, PDO::PARAM_INT);
                $stmt->bindParam(2, $queue_id, PDO::PARAM_INT);
                $stmt->bindParam(3, $user_id, PDO::PARAM_INT);
                $stmt->execute();
                
                $message = '발행 작업이 시작되었습니다. 발행 이력에서 진행 상황을 확인하세요.';
                $message_type = 'success';
                
                // 활동 로그 기록
                callLogActivity('복권 발행 시작: 계획 ID ' . $plan_id, 'issue_plans');
            } else {
                $message = '발행 계획을 찾을 수 없습니다.';
                $message_type = 'danger';
            }
        } catch (Exception $e) {
            $message = '오류가 발생했습니다: ' . $e->getMessage();
            $message_type = 'danger';
            error_log("Database error: " . $e->getMessage());
        }
    }
    
    // 상태 변경 처리
    if (isset($_POST['action']) && $_POST['action'] === 'change_status') {
        $plan_id = (int) sanitizeInput($_POST['plan_id']);
        $status = sanitizeInput($_POST['status']);
        
        try {
            // 발행 계획 상태 변경
            $query = "UPDATE issue_plans SET 
                     status = ?, 
                     updated_at = NOW() 
                     WHERE id = ?";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $status, PDO::PARAM_STR);
            $stmt->bindParam(2, $plan_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $message = '발행 계획 상태가 성공적으로 변경되었습니다.';
                $message_type = 'success';
                
                // 활동 로그 기록
                callLogActivity('발행 계획 상태 변경: ID ' . $plan_id . ' -> ' . $status, 'issue_plans');
            } else {
                $message = '발행 계획 상태 변경에 실패했습니다.';
                $message_type = 'danger';
            }
        } catch (Exception $e) {
            $message = '오류가 발생했습니다: ' . $e->getMessage();
            $message_type = 'danger';
            error_log("Database error: " . $e->getMessage());
        }
    }
}

// 복권 상품 목록 조회
$products = [];
try {
    $query = "SELECT id, product_code, name, status 
             FROM lottery_products 
             WHERE status != 'suspended' 
             ORDER BY name ASC";
    
    $result = $db->query($query);
    
    if ($result) {
        $products = $result->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = '오류가 발생했습니다: ' . $e->getMessage();
    $message_type = 'danger';
    error_log("Database error: " . $e->getMessage());
}

// 발행 계획 목록 조회
$plans = [];
try {
    $query = "SELECT 
             ip.*,
             lp.product_code,
             lp.name AS product_name,
             IFNULL(iq.status, '') AS queue_status,
             IFNULL(iq.processed_tickets, 0) AS processed_tickets,
             IFNULL(iq.id, 0) AS queue_id
         FROM 
             issue_plans ip
         JOIN 
             lottery_products lp ON ip.product_id = lp.id
         LEFT JOIN 
             issue_queue iq ON ip.id = iq.plan_id AND iq.status IN ('pending', 'in_progress')
         ORDER BY 
             ip.issue_date DESC, ip.id DESC";
    
    $result = $db->query($query);
    
    if ($result) {
        $plans = $result->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = '오류가 발생했습니다: ' . $e->getMessage();
    $message_type = 'danger';
    error_log("Database error: " . $e->getMessage());
}

// 발행 이력 조회
$history = [];
try {
    $query = "SELECT 
             ih.*,
             ip.issue_date,
             lp.product_code,
             lp.name AS product_name,
             u.username AS issued_by_name
         FROM 
             issue_history ih
         JOIN 
             issue_plans ip ON ih.plan_id = ip.id
         JOIN 
             lottery_products lp ON ip.product_id = lp.id
         LEFT JOIN 
             users u ON ih.issued_by = u.id
         ORDER BY 
             ih.created_at DESC
         LIMIT 20";
    
    $result = $db->query($query);
    
    if ($result) {
        $history = $result->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = '오류가 발생했습니다: ' . $e->getMessage();
    $message_type = 'danger';
    error_log("Database error: " . $e->getMessage());
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
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/">홈</a></li>
                    <li class="breadcrumb-item">복권 관리</li>
                    <li class="breadcrumb-item active">복권 발행 관리</li>
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
        
        <!-- 탭 메뉴 -->
        <div class="card card-primary card-outline card-outline-tabs">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" id="issue-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="plans-tab" data-toggle="pill" href="#plans" role="tab" aria-controls="plans" aria-selected="true">발행 계획</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="history-tab" data-toggle="pill" href="#history" role="tab" aria-controls="history" aria-selected="false">발행 이력</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="issue-tabs-content">
                    <!-- 발행 계획 탭 -->
                    <div class="tab-pane fade show active" id="plans" role="tabpanel" aria-labelledby="plans-tab">
                        <!-- 계획 추가 버튼 -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPlanModal">
                                    <i class="fas fa-plus-circle"></i> 새 발행 계획 추가
                                </button>
                            </div>
                        </div>
                        
                        <!-- 발행 계획 목록 -->
                        <div class="table-responsive">
                            <table class="table table-hover table-striped" id="plansTable">
                                <thead>
                                    <tr>
                                        <th>복권 상품</th>
                                        <th>발행 예정일</th>
                                        <th>총 발행량</th>
                                        <th>배치 크기</th>
                                        <th>시작 번호</th>
                                        <th>상태</th>
                                        <th>진행률</th>
                                        <th>관리</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($plans as $plan): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($plan['product_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($plan['product_code']); ?></small>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($plan['issue_date'])); ?></td>
                                            <td><?php echo number_format($plan['total_tickets']); ?> 장</td>
                                            <td><?php echo number_format($plan['batch_size']); ?> 장</td>
                                            <td><?php echo htmlspecialchars($plan['start_number']); ?></td>
                                            <td>
                                                <?php 
                                                $badgeClass = 'secondary';
                                                $statusText = '알 수 없음';
                                                
                                                switch ($plan['status']) {
                                                    case 'draft':
                                                        $badgeClass = 'secondary';
                                                        $statusText = '초안';
                                                        break;
                                                    case 'ready':
                                                        $badgeClass = 'info';
                                                        $statusText = '준비';
                                                        break;
                                                    case 'in_progress':
                                                        $badgeClass = 'warning';
                                                        $statusText = '발행 중';
                                                        break;
                                                    case 'completed':
                                                        $badgeClass = 'success';
                                                        $statusText = '완료';
                                                        break;
                                                    case 'cancelled':
                                                        $badgeClass = 'danger';
                                                        $statusText = '취소';
                                                        break;
                                                }
                                                
                                                echo '<span class="badge badge-' . $badgeClass . '">' . $statusText . '</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($plan['status'] === 'in_progress' && !empty($plan['queue_id'])): ?>
                                                    <?php $progress = ($plan['processed_tickets'] / $plan['total_tickets']) * 100; ?>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $progress; ?>%" 
                                                             aria-valuenow="<?php echo $progress; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                            <?php echo number_format($progress, 1); ?>%
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo number_format($plan['processed_tickets']); ?> / 
                                                        <?php echo number_format($plan['total_tickets']); ?> 장 처리됨
                                                    </small>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-info btn-view-plan" data-id="<?php echo $plan['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <?php if ($plan['status'] === 'draft' || $plan['status'] === 'ready'): ?>
                                                        <button type="button" class="btn btn-primary btn-edit-plan" data-id="<?php echo $plan['id']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <i class="fas fa-cog"></i>
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <?php if ($plan['status'] === 'ready'): ?>
                                                                <a class="dropdown-item btn-issue" data-id="<?php echo $plan['id']; ?>" href="#">
                                                                    <i class="fas fa-play text-success"></i> 발행 시작
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($plan['status'] === 'in_progress' && !empty($plan['queue_id'])): ?>
                                                                <a class="dropdown-item btn-cancel-issue" data-id="<?php echo $plan['queue_id']; ?>" href="#">
                                                                    <i class="fas fa-stop text-danger"></i> 발행 중지
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($plan['status'] === 'draft'): ?>
                                                                <a class="dropdown-item btn-change-status" data-id="<?php echo $plan['id']; ?>" data-status="ready" href="#">
                                                                    <i class="fas fa-check text-info"></i> 준비 상태로 변경
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($plan['status'] === 'draft'): ?>
                                                                <a class="dropdown-item btn-delete-plan" data-id="<?php echo $plan['id']; ?>" href="#">
                                                                    <i class="fas fa-trash text-danger"></i> 계획 삭제
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($plans)): ?>
                                        <tr><td colspan="8" class="text-center">등록된 발행 계획이 없습니다.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- 발행 이력 탭 -->
                    <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped" id="historyTable">
                                <thead>
                                    <tr>
                                        <th>일시</th>
                                        <th>복권 상품</th>
                                        <th>발행 예정일</th>
                                        <th>작업자</th>
                                        <th>상태</th>
                                        <th>비고</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $record): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($record['created_at'])); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($record['product_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($record['product_code']); ?></small>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($record['issue_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($record['issued_by_name'] ?? '시스템'); ?></td>
                                            <td>
                                                <?php 
                                                $badgeClass = 'secondary';
                                                $statusText = '알 수 없음';
                                                
                                                switch ($record['status']) {
                                                    case 'started':
                                                        $badgeClass = 'primary';
                                                        $statusText = '시작';
                                                        break;
                                                    case 'in_progress':
                                                        $badgeClass = 'warning';
                                                        $statusText = '진행 중';
                                                        break;
                                                    case 'completed':
                                                        $badgeClass = 'success';
                                                        $statusText = '완료';
                                                        break;
                                                    case 'cancelled':
                                                        $badgeClass = 'danger';
                                                        $statusText = '취소';
                                                        break;
                                                    case 'failed':
                                                        $badgeClass = 'danger';
                                                        $statusText = '실패';
                                                        break;
                                                }
                                                
                                                echo '<span class="badge badge-' . $badgeClass . '">' . $statusText . '</span>';
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['notes']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($history)): ?>
                                        <tr><td colspan="6" class="text-center">발행 이력이 없습니다.</td></tr>
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

<!-- 계획 추가 모달 -->
<div class="modal fade" id="addPlanModal" tabindex="-1" role="dialog" aria-labelledby="addPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addPlanForm" method="POST">
                <input type="hidden" name="action" value="add_plan">
                
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="addPlanModalLabel">새 발행 계획 추가</h5>
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
                                <label for="issue_date">발행 예정일</label>
                                <input type="date" class="form-control" id="issue_date" name="issue_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="total_tickets">총 발행량</label>
                                <input type="number" class="form-control" id="total_tickets" name="total_tickets" min="1" required>
                                <small class="form-text text-muted">발행할 복권의 총 장수</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="batch_size">배치 크기</label>
                                <input type="number" class="form-control" id="batch_size" name="batch_size" min="1" required>
                                <small class="form-text text-muted">한 번에 처리할 복권 장수</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="start_number">시작 번호</label>
                                <input type="text" class="form-control" id="start_number" name="start_number" required>
                                <small class="form-text text-muted">복권 번호의 시작 값</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">상태</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="draft">초안</option>
                                    <option value="ready">준비</option>
                                </select>
                                <small class="form-text text-muted">'준비' 상태만 발행이 가능합니다.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">비고</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
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

<!-- 계획 수정 모달 -->
<div class="modal fade" id="editPlanModal" tabindex="-1" role="dialog" aria-labelledby="editPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editPlanForm" method="POST">
                <input type="hidden" name="action" value="edit_plan">
                <input type="hidden" id="edit_plan_id" name="plan_id" value="">
                
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="editPlanModalLabel">발행 계획 수정</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_product_id">복권 상품</label>
                                <select class="form-control" id="edit_product_id" name="product_id" required>
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
                                <label for="edit_issue_date">발행 예정일</label>
                                <input type="date" class="form-control" id="edit_issue_date" name="issue_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_total_tickets">총 발행량</label>
                                <input type="number" class="form-control" id="edit_total_tickets" name="total_tickets" min="1" required>
                                <small class="form-text text-muted">발행할 복권의 총 장수</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_batch_size">배치 크기</label>
                                <input type="number" class="form-control" id="edit_batch_size" name="batch_size" min="1" required>
                                <small class="form-text text-muted">한 번에 처리할 복권 장수</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_start_number">시작 번호</label>
                                <input type="text" class="form-control" id="edit_start_number" name="start_number" required>
                                <small class="form-text text-muted">복권 번호의 시작 값</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_status">상태</label>
                                <select class="form-control" id="edit_status" name="status" required>
                                    <option value="draft">초안</option>
                                    <option value="ready">준비</option>
                                </select>
                                <small class="form-text text-muted">'준비' 상태만 발행이 가능합니다.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_notes">비고</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
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

<!-- 계획 상세 모달 -->
<div class="modal fade" id="viewPlanModal" tabindex="-1" role="dialog" aria-labelledby="viewPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title" id="viewPlanModalLabel">발행 계획 상세 정보</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>복권 상품</label>
                            <p id="view_product_name" class="form-control-static">-</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>발행 예정일</label>
                            <p id="view_issue_date" class="form-control-static">-</p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>총 발행량</label>
                            <p id="view_total_tickets" class="form-control-static">-</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>배치 크기</label>
                            <p id="view_batch_size" class="form-control-static">-</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>시작 번호</label>
                            <p id="view_start_number" class="form-control-static">-</p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>상태</label>
                            <p id="view_status" class="form-control-static">-</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>생성일</label>
                            <p id="view_created_at" class="form-control-static">-</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>최종 수정일</label>
                            <p id="view_updated_at" class="form-control-static">-</p>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>비고</label>
                    <p id="view_notes" class="form-control-static">-</p>
                </div>
                
                <div id="view_progress_container" style="display: none;">
                    <hr>
                    <h5>발행 진행 상황</h5>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="progress">
                                <div id="view_progress_bar" class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                            <p id="view_progress_text" class="mt-2 mb-0">0 / 0 장 처리됨</p>
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

<!-- 발행 확인 모달 -->
<div class="modal fade" id="confirmIssueModal" tabindex="-1" role="dialog" aria-labelledby="confirmIssueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="issueForm" method="POST">
                <input type="hidden" name="action" value="issue">
                <input type="hidden" id="issue_plan_id" name="plan_id" value="">
                
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="confirmIssueModalLabel">발행 시작 확인</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <p>선택한 계획에 따라 복권 발행을 시작합니다. 계속하시겠습니까?</p>
                    <p>복권 상품: <strong id="confirm_product_name">-</strong></p>
                    <p>발행 예정일: <strong id="confirm_issue_date">-</strong></p>
                    <p>총 발행량: <strong id="confirm_total_tickets">-</strong> 장</p>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-warning">발행 시작</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 발행 취소 확인 모달 -->
<div class="modal fade" id="confirmCancelIssueModal" tabindex="-1" role="dialog" aria-labelledby="confirmCancelIssueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cancelIssueForm" method="POST">
                <input type="hidden" name="action" value="cancel_issue">
                <input type="hidden" id="cancel_queue_id" name="queue_id" value="">
                
                <div class="modal-header bg-danger">
                    <h5 class="modal-title" id="confirmCancelIssueModalLabel">발행 취소 확인</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <p>진행 중인 발행 작업을 취소하시겠습니까?</p>
                    <p>이 작업은 되돌릴 수 없으며, 발행이 부분적으로 완료된 경우 일관성 문제가 발생할 수 있습니다.</p>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                    <button type="submit" class="btn btn-danger">발행 취소</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 상태 변경 확인 모달 -->
<div class="modal fade" id="confirmStatusChangeModal" tabindex="-1" role="dialog" aria-labelledby="confirmStatusChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="changeStatusForm" method="POST">
                <input type="hidden" name="action" value="change_status">
                <input type="hidden" id="change_plan_id" name="plan_id" value="">
                <input type="hidden" id="change_status" name="status" value="">
                
                <div class="modal-header bg-info">
                    <h5 class="modal-title" id="confirmStatusChangeModalLabel">상태 변경 확인</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <p>발행 계획의 상태를 <strong id="new_status_text">-</strong>으로 변경하시겠습니까?</p>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-info">상태 변경</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deletePlanForm" method="POST">
                <input type="hidden" name="action" value="delete_plan">
                <input type="hidden" id="delete_plan_id" name="plan_id" value="">
                
                <div class="modal-header bg-danger">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">삭제 확인</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <p>선택한 발행 계획을 정말 삭제하시겠습니까?</p>
                    <p>이 작업은 되돌릴 수 없습니다.</p>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-danger">삭제</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('복권 발행 관리 페이지 로드됨');
    
    // DataTables 초기화
    if ($.fn.DataTable) {
        $('#plansTable').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Korean.json"
            },
            "order": [[1, 'desc']]
        });
        
        $('#historyTable').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Korean.json"
            },
            "order": [[0, 'desc']]
        });
    }
    
    // 계획 조회 처리
    $('.btn-view-plan').on('click', function() {
        var planId = $(this).data('id');
        console.log('계획 조회:', planId);
        
        // 관련 계획 데이터 찾기
        var plan = <?php echo json_encode($plans); ?>.find(function(p) {
            return p.id == planId;
        });
        
        if (plan) {
            $('#view_product_name').text(plan.product_name + ' (' + plan.product_code + ')');
            $('#view_issue_date').text(new Date(plan.issue_date).toLocaleDateString());
            $('#view_total_tickets').text(Number(plan.total_tickets).toLocaleString() + ' 장');
            $('#view_batch_size').text(Number(plan.batch_size).toLocaleString() + ' 장');
            $('#view_start_number').text(plan.start_number);
            
            var statusText = '알 수 없음';
            var statusClass = 'badge-secondary';
            
            switch (plan.status) {
                case 'draft':
                    statusText = '초안';
                    statusClass = 'badge-secondary';
                    break;
                case 'ready':
                    statusText = '준비';
                    statusClass = 'badge-info';
                    break;
                case 'in_progress':
                    statusText = '발행 중';
                    statusClass = 'badge-warning';
                    break;
                case 'completed':
                    statusText = '완료';
                    statusClass = 'badge-success';
                    break;
                case 'cancelled':
                    statusText = '취소';
                    statusClass = 'badge-danger';
                    break;
            }
            
            $('#view_status').html('<span class="badge ' + statusClass + '">' + statusText + '</span>');
            $('#view_created_at').text(new Date(plan.created_at).toLocaleString());
            $('#view_updated_at').text(plan.updated_at ? new Date(plan.updated_at).toLocaleString() : '-');
            $('#view_notes').text(plan.notes || '-');
            
            // 진행 상황 표시 처리
            if (plan.status === 'in_progress' && plan.queue_id > 0) {
                var progress = (plan.processed_tickets / plan.total_tickets) * 100;
                $('#view_progress_container').show();
                $('#view_progress_bar').css('width', progress + '%').attr('aria-valuenow', progress).text(progress.toFixed(1) + '%');
                $('#view_progress_text').text(Number(plan.processed_tickets).toLocaleString() + ' / ' + Number(plan.total_tickets).toLocaleString() + ' 장 처리됨');
            } else {
                $('#view_progress_container').hide();
            }
            
            $('#viewPlanModal').modal('show');
        }
    });
    
    // 계획 수정 처리
    $('.btn-edit-plan').on('click', function() {
        var planId = $(this).data('id');
        console.log('계획 수정:', planId);
        
        // 관련 계획 데이터 찾기
        var plan = <?php echo json_encode($plans); ?>.find(function(p) {
            return p.id == planId;
        });
        
        if (plan) {
            $('#edit_plan_id').val(plan.id);
            $('#edit_product_id').val(plan.product_id);
            $('#edit_issue_date').val(plan.issue_date);
            $('#edit_total_tickets').val(plan.total_tickets);
            $('#edit_batch_size').val(plan.batch_size);
            $('#edit_start_number').val(plan.start_number);
            $('#edit_status').val(plan.status);
            $('#edit_notes').val(plan.notes);
            
            $('#editPlanModal').modal('show');
        }
    });
    
    // 발행 시작 처리
    $('.btn-issue').on('click', function(e) {
        e.preventDefault();
        var planId = $(this).data('id');
        console.log('발행 시작:', planId);
        
        // 관련 계획 데이터 찾기
        var plan = <?php echo json_encode($plans); ?>.find(function(p) {
            return p.id == planId;
        });
        
        if (plan) {
            $('#issue_plan_id').val(plan.id);
            $('#confirm_product_name').text(plan.product_name + ' (' + plan.product_code + ')');
            $('#confirm_issue_date').text(new Date(plan.issue_date).toLocaleDateString());
            $('#confirm_total_tickets').text(Number(plan.total_tickets).toLocaleString());
            
            $('#confirmIssueModal').modal('show');
        }
    });
    
    // 발행 시작 폼 제출 처리
    $('#issueForm').on('submit', function(e) {
        e.preventDefault();
        var planId = $('#issue_plan_id').val();
        console.log('발행 시작 폼 제출:', planId);
        
        $.ajax({
            url: '../../api/lottery/start_issue.php',
            type: 'POST',
            data: {
                plan_id: planId
            },
            dataType: 'json',
            success: function(response) {
                console.log('발행 시작 응답:', response);
                
                if (response.status === 'success') {
                    // 성공 메시지 표시
                    alert(response.message);
                    // 모달 닫기
                    $('#confirmIssueModal').modal('hide');
                    // 페이지 새로고침
                    location.reload();
                } else {
                    // 오류 메시지 표시
                    alert('오류: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('발행 시작 AJAX 오류:', error);
                alert('서버 통신 오류가 발생했습니다. 다시 시도해주세요.');
            }
        });
    });
    
    // 발행 취소 처리
    $('.btn-cancel-issue').on('click', function(e) {
        e.preventDefault();
        var queueId = $(this).data('id');
        console.log('발행 취소:', queueId);
        
        $('#cancel_queue_id').val(queueId);
        $('#confirmCancelIssueModal').modal('show');
    });
    
    // 발행 취소 폼 제출 처리
    $('#cancelIssueForm').on('submit', function(e) {
        e.preventDefault();
        var queueId = $('#cancel_queue_id').val();
        console.log('발행 취소 폼 제출:', queueId);
        
        $.ajax({
            url: '../../api/lottery/cancel_issue.php',
            type: 'POST',
            data: {
                queue_id: queueId
            },
            dataType: 'json',
            success: function(response) {
                console.log('발행 취소 응답:', response);
                
                if (response.status === 'success') {
                    // 성공 메시지 표시
                    alert(response.message);
                    // 모달 닫기
                    $('#confirmCancelIssueModal').modal('hide');
                    // 페이지 새로고침
                    location.reload();
                } else {
                    // 오류 메시지 표시
                    alert('오류: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('발행 취소 AJAX 오류:', error);
                alert('서버 통신 오류가 발생했습니다. 다시 시도해주세요.');
            }
        });
    });
    
    // 상태 변경 처리
    $('.btn-change-status').on('click', function(e) {
        e.preventDefault();
        var planId = $(this).data('id');
        var newStatus = $(this).data('status');
        console.log('상태 변경:', planId, newStatus);
        
        var statusText = '알 수 없음';
        switch (newStatus) {
            case 'draft':
                statusText = '초안';
                break;
            case 'ready':
                statusText = '준비';
                break;
            case 'completed':
                statusText = '완료';
                break;
            case 'cancelled':
                statusText = '취소';
                break;
        }
        
        $('#change_plan_id').val(planId);
        $('#change_status').val(newStatus);
        $('#new_status_text').text(statusText);
        
        $('#confirmStatusChangeModal').modal('show');
    });
    
    // 상태 변경 폼 제출 처리
    $('#changeStatusForm').on('submit', function(e) {
        e.preventDefault();
        var planId = $('#change_plan_id').val();
        var newStatus = $('#change_status').val();
        console.log('상태 변경 폼 제출:', planId, newStatus);
        
        $.ajax({
            url: '../../api/lottery/change_status.php',
            type: 'POST',
            data: {
                plan_id: planId,
                new_status: newStatus
            },
            dataType: 'json',
            success: function(response) {
                console.log('상태 변경 응답:', response);
                
                if (response.status === 'success') {
                    // 성공 메시지 표시
                    alert(response.message);
                    // 모달 닫기
                    $('#confirmStatusChangeModal').modal('hide');
                    // 페이지 새로고침
                    location.reload();
                } else {
                    // 오류 메시지 표시
                    alert('오류: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('상태 변경 AJAX 오류:', error);
                alert('서버 통신 오류가 발생했습니다. 다시 시도해주세요.');
            }
        });
    });
    
    // 삭제 처리
    $('.btn-delete-plan').on('click', function(e) {
        e.preventDefault();
        var planId = $(this).data('id');
        console.log('계획 삭제:', planId);
        
        $('#delete_plan_id').val(planId);
        $('#confirmDeleteModal').modal('show');
    });
    
    // 삭제 폼 제출 처리
    $('#deletePlanForm').on('submit', function(e) {
        e.preventDefault();
        var planId = $('#delete_plan_id').val();
        console.log('삭제 폼 제출:', planId);
        
        $.ajax({
            url: '../../api/lottery/delete_plan.php',
            type: 'POST',
            data: {
                plan_id: planId
            },
            dataType: 'json',
            success: function(response) {
                console.log('삭제 응답:', response);
                
                if (response.status === 'success') {
                    // 성공 메시지 표시
                    alert(response.message);
                    // 모달 닫기
                    $('#confirmDeleteModal').modal('hide');
                    // 페이지 새로고침
                    location.reload();
                } else {
                    // 오류 메시지 표시
                    alert('오류: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('삭제 AJAX 오류:', error);
                alert('서버 통신 오류가 발생했습니다. 다시 시도해주세요.');
            }
        });
    });
    
    // 모달 닫힐 때 폼 초기화
    $('#addPlanModal').on('hidden.bs.modal', function() {
        $('#addPlanForm')[0].reset();
    });
    
    $('#editPlanModal').on('hidden.bs.modal', function() {
        $('#editPlanForm')[0].reset();
    });
    
    // 오늘 날짜를 기본값으로 설정
    var today = new Date().toISOString().split('T')[0];
    document.getElementById('issue_date').value = today;
    
    // 초기 배치 크기 및 총 발행량 설정
    document.getElementById('total_tickets').value = 10000;
    document.getElementById('batch_size').value = 1000;
});
</script>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';

/**
 * 유틸리티 함수: 현재 로그인한 사용자 ID 반환
 */
function getCurrentUserId() {
    // 세션에서 사용자 ID 가져오기
    // 실제 구현에서는 세션 관리 방식에 맞게 수정
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // 기본값으로 1 반환
}

/**
 * 유틸리티 함수: 현재 사용자 ID를 함수에 전달하는 래퍼
 * functions.php의 log_activity 함수 호출을 위한 도우미 함수
 */
function callLogActivity($activity, $module) {
    $user_id = getCurrentUserId();
    log_activity($activity, $module, $user_id);
}
?>
