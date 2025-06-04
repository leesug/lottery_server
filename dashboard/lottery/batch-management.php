<?php
/**
 * 복권 배치 관리
 * 복권의 발행 배치(Batch)를 관리하는 페이지
 * 
 * @package Lottery Management
 * @author Claude
 * @created 2025-05-16
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = getDbConnection();

// 현재 페이지 정보
$pageTitle = "복권 배치 관리";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 메시지 초기화
$message = '';
$message_type = '';

// CSRF 토큰 생성
$csrf_token = SecurityManager::generateCsrfToken();

// 페이지 제목 설정
$page_description = "복권 배치를 생성, 수정, 관리하는 페이지입니다.";

// 작업 결과 메시지
$success_message = '';
$error_message = '';

// 폼 처리: 배치 추가/수정/삭제
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF 토큰 검증
    if (!SecurityManager::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = "보안 토큰이 유효하지 않습니다. 페이지를 새로고침 후 다시 시도해 주세요.";
    } else {
        try {
            switch ($_POST['action']) {
                case 'add_batch':
                    // 배치 추가 처리
                    $batch_name = sanitizeInput($_POST['batch_name'] ?? '');
                    $lottery_product_id = sanitizeInput($_POST['lottery_product_id'] ?? '');
                    $issue_id = sanitizeInput($_POST['issue_id'] ?? '');
                    $batch_size = sanitizeInput($_POST['batch_size'] ?? '');
                    $start_serial = sanitizeInput($_POST['start_serial'] ?? '');
                    $end_serial = sanitizeInput($_POST['end_serial'] ?? '');
                    $status = sanitizeInput($_POST['status'] ?? 'pending');
                    $scheduled_date = sanitizeInput($_POST['scheduled_date'] ?? '');
                    $notes = sanitizeInput($_POST['notes'] ?? '');
                    
                    if (empty($batch_name) || empty($lottery_product_id) || empty($issue_id) || empty($batch_size)) {
                        $error_message = "배치 이름, 복권 상품, 발행 ID, 배치 크기는 필수입니다.";
                    } else {
                        $stmt = $db->prepare("INSERT INTO lottery_batches 
                            (batch_name, lottery_product_id, issue_id, batch_size, start_serial, end_serial, status, scheduled_date, notes, created_by, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                        
                        $user_id = AuthManager::getUserId();
                        
                        $stmt->bindParam(1, $batch_name, PDO::PARAM_STR);
                        $stmt->bindParam(2, $lottery_product_id, PDO::PARAM_INT);
                        $stmt->bindParam(3, $issue_id, PDO::PARAM_INT);
                        $stmt->bindParam(4, $batch_size, PDO::PARAM_INT);
                        $stmt->bindParam(5, $start_serial, PDO::PARAM_STR);
                        $stmt->bindParam(6, $end_serial, PDO::PARAM_STR);
                        $stmt->bindParam(7, $status, PDO::PARAM_STR);
                        $stmt->bindParam(8, $scheduled_date, PDO::PARAM_STR);
                        $stmt->bindParam(9, $notes, PDO::PARAM_STR);
                        $stmt->bindParam(10, $user_id, PDO::PARAM_INT);
                        
                        if ($stmt->execute()) {
                            $success_message = "배치가 성공적으로 추가되었습니다.";
                            logActivity('배치 추가: ' . $batch_name);
                        } else {
                            $error_message = "배치 추가 중 오류가 발생했습니다: " . $db->errorInfo()[2];
                        }
                    }
                    break;
                    
                case 'edit_batch':
                    // 배치 수정 처리
                    $batch_id = sanitizeInput($_POST['batch_id'] ?? '');
                    $batch_name = sanitizeInput($_POST['batch_name'] ?? '');
                    $lottery_product_id = sanitizeInput($_POST['lottery_product_id'] ?? '');
                    $issue_id = sanitizeInput($_POST['issue_id'] ?? '');
                    $batch_size = sanitizeInput($_POST['batch_size'] ?? '');
                    $start_serial = sanitizeInput($_POST['start_serial'] ?? '');
                    $end_serial = sanitizeInput($_POST['end_serial'] ?? '');
                    $status = sanitizeInput($_POST['status'] ?? 'pending');
                    $scheduled_date = sanitizeInput($_POST['scheduled_date'] ?? '');
                    $notes = sanitizeInput($_POST['notes'] ?? '');
                    
                    if (empty($batch_id) || empty($batch_name) || empty($lottery_product_id) || empty($issue_id) || empty($batch_size)) {
                        $error_message = "배치 ID, 이름, 복권 상품, 발행 ID, 배치 크기는 필수입니다.";
                    } else {
                        // 배치 상태 확인
                        $check_stmt = $db->prepare("SELECT status FROM lottery_batches WHERE id = ?");
                        $check_stmt->bindParam(1, $batch_id, PDO::PARAM_INT);
                        $check_stmt->execute();
                        $current_batch = $check_stmt->fetch(PDO::FETCH_ASSOC);
                        $check_stmt->closeCursor();
                        
                        // 완료된 배치는 수정 불가
                        if ($current_batch['status'] === 'completed' || $current_batch['status'] === 'distributed') {
                            $error_message = "완료되거나 배포된 배치는 수정할 수 없습니다.";
                        } else {
                            $stmt = $db->prepare("UPDATE lottery_batches 
                                SET batch_name = ?, 
                                    lottery_product_id = ?, 
                                    issue_id = ?, 
                                    batch_size = ?, 
                                    start_serial = ?, 
                                    end_serial = ?, 
                                    status = ?,
                                    scheduled_date = ?,
                                    notes = ?,
                                    updated_by = ?,
                                    updated_at = NOW()
                                WHERE id = ?");
                            
                            $user_id = AuthManager::getUserId();
                            
                            $stmt->bindParam(1, $batch_name, PDO::PARAM_STR);
                            $stmt->bindParam(2, $lottery_product_id, PDO::PARAM_INT);
                            $stmt->bindParam(3, $issue_id, PDO::PARAM_INT);
                            $stmt->bindParam(4, $batch_size, PDO::PARAM_INT);
                            $stmt->bindParam(5, $start_serial, PDO::PARAM_STR);
                            $stmt->bindParam(6, $end_serial, PDO::PARAM_STR);
                            $stmt->bindParam(7, $status, PDO::PARAM_STR);
                            $stmt->bindParam(8, $scheduled_date, PDO::PARAM_STR);
                            $stmt->bindParam(9, $notes, PDO::PARAM_STR);
                            $stmt->bindParam(10, $user_id, PDO::PARAM_INT);
                            $stmt->bindParam(11, $batch_id, PDO::PARAM_INT);
                            
                            if ($stmt->execute()) {
                                $success_message = "배치가 성공적으로 수정되었습니다.";
                                logActivity('배치 수정: ' . $batch_name);
                            } else {
                                $error_message = "배치 수정 중 오류가 발생했습니다: " . $db->errorInfo()[2];
                            }
                        }
                    }
                    break;
                    
                case 'delete_batch':
                    // 배치 삭제 처리
                    $batch_id = sanitizeInput($_POST['batch_id'] ?? '');
                    
                    if (empty($batch_id)) {
                        $error_message = "배치 ID는 필수입니다.";
                    } else {
                        // 배치 상태 확인
                        $check_stmt = $db->prepare("SELECT status FROM lottery_batches WHERE id = ?");
                        $check_stmt->bindParam(1, $batch_id, PDO::PARAM_INT);
                        $check_stmt->execute();
                        $current_batch = $check_stmt->fetch(PDO::FETCH_ASSOC);
                        $check_stmt->closeCursor();
                        
                        // 진행 중이거나 완료된 배치는 삭제 불가
                        if ($current_batch['status'] !== 'pending' && $current_batch['status'] !== 'cancelled') {
                            $error_message = "진행 중이거나 완료된 배치는 삭제할 수 없습니다.";
                        } else {
                            $stmt = $db->prepare("DELETE FROM lottery_batches WHERE id = ?");
                            $stmt->bindParam(1, $batch_id, PDO::PARAM_INT);
                            
                            if ($stmt->execute()) {
                                $success_message = "배치가 성공적으로 삭제되었습니다.";
                                logActivity('배치 삭제: ID ' . $batch_id);
                            } else {
                                $error_message = "배치 삭제 중 오류가 발생했습니다: " . $db->errorInfo()[2];
                            }
                        }
                    }
                    break;
                    
                case 'process_batch':
                    // 배치 처리 (상태 변경) 처리
                    $batch_id = sanitizeInput($_POST['batch_id'] ?? '');
                    $new_status = sanitizeInput($_POST['new_status'] ?? '');
                    
                    if (empty($batch_id) || empty($new_status)) {
                        $error_message = "배치 ID와 새 상태는 필수입니다.";
                    } else {
                        // 상태 변경 처리
                        $stmt = $db->prepare("UPDATE lottery_batches 
                            SET status = ?, 
                                processed_date = NOW(),
                                updated_by = ?,
                                updated_at = NOW()
                            WHERE id = ?");
                        
                        $user_id = AuthManager::getUserId();
                        
                        $stmt->bindParam(1, $new_status, PDO::PARAM_STR);
                        $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
                        $stmt->bindParam(3, $batch_id, PDO::PARAM_INT);
                        
                        if ($stmt->execute()) {
                            $success_message = "배치 상태가 성공적으로 변경되었습니다.";
                            logActivity('배치 상태 변경: ID ' . $batch_id . ' → ' . $new_status);
                            
                            // 완료 상태로 변경된 경우 후속 처리
                            if ($new_status === 'completed') {
                                // 로그 추가
                                logActivity('배치 완료: ID ' . $batch_id);
                                
                                // 다른 필요한 처리 로직 추가
                            }
                        } else {
                            $error_message = "배치 상태 변경 중 오류가 발생했습니다: " . $db->errorInfo()[2];
                        }
                    }
                    break;
            }
        } catch (Exception $e) {
            $error_message = "오류가 발생했습니다: " . $e->getMessage();
            logError('batch_management.php: ' . $e->getMessage());
        }
    }
}

// 배치 목록 불러오기
$batches = [];
$query = "SELECT b.*, p.name as product_name, i.issue_code 
          FROM lottery_batches b
          LEFT JOIN lottery_products p ON b.lottery_product_id = p.id
          LEFT JOIN lottery_issues i ON b.issue_id = i.id
          ORDER BY b.created_at DESC";
$result = $db->query($query);

if ($result) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $batches[] = $row;
    }
}

// 복권 상품 목록 불러오기
$products = [];
$query = "SELECT id, name as product_name FROM lottery_products WHERE status = 'active'";
$result = $db->query($query);

if ($result) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $products[] = $row;
    }
}

// 발행 목록 불러오기
$issues = [];
$query = "SELECT id, issue_code FROM lottery_issues WHERE status = 'active'";
$result = $db->query($query);

if ($result) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $issues[] = $row;
    }
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
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-cubes me-1"></i>
            <?php echo $page_description; ?>
        </div>
        <div class="card-body">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- 새 배치 추가 버튼 -->
            <div class="mb-4">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBatchModal">
                    <i class="fas fa-plus"></i> 새 배치 추가
                </button>
                <a href="/dashboard/lottery/batch-report.php" class="btn btn-info ms-2">
                    <i class="fas fa-chart-bar"></i> 배치 리포트
                </a>
            </div>
            
            <!-- 배치 목록 테이블 -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="batchesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>배치명</th>
                            <th>복권 상품</th>
                            <th>발행 코드</th>
                            <th>크기</th>
                            <th>시작 일련번호</th>
                            <th>종료 일련번호</th>
                            <th>상태</th>
                            <th>예정일</th>
                            <th>생성일</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($batches as $batch): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($batch['id']); ?></td>
                            <td><?php echo htmlspecialchars($batch['batch_name']); ?></td>
                            <td><?php echo htmlspecialchars($batch['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($batch['issue_code']); ?></td>
                            <td><?php echo htmlspecialchars($batch['batch_size']); ?></td>
                            <td><?php echo htmlspecialchars($batch['start_serial']); ?></td>
                            <td><?php echo htmlspecialchars($batch['end_serial']); ?></td>
                            <td>
                                <?php 
                                $status_class = '';
                                $status_text = '';
                                
                                switch ($batch['status']) {
                                    case 'pending':
                                        $status_class = 'bg-secondary';
                                        $status_text = '대기 중';
                                        break;
                                    case 'in_progress':
                                        $status_class = 'bg-primary';
                                        $status_text = '진행 중';
                                        break;
                                    case 'completed':
                                        $status_class = 'bg-success';
                                        $status_text = '완료';
                                        break;
                                    case 'distributed':
                                        $status_class = 'bg-info';
                                        $status_text = '배포됨';
                                        break;
                                    case 'cancelled':
                                        $status_class = 'bg-danger';
                                        $status_text = '취소됨';
                                        break;
                                    case 'error':
                                        $status_class = 'bg-warning';
                                        $status_text = '오류';
                                        break;
                                    default:
                                        $status_class = 'bg-secondary';
                                        $status_text = $batch['status'];
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </td>
                            <td><?php echo !empty($batch['scheduled_date']) ? formatDate($batch['scheduled_date']) : '-'; ?></td>
                            <td><?php echo formatDateTime($batch['created_at']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-info btn-sm view-batch" data-id="<?php echo $batch['id']; ?>" title="상세보기">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($batch['status'] === 'pending'): ?>
                                    <button type="button" class="btn btn-primary btn-sm edit-batch" data-id="<?php echo $batch['id']; ?>" title="수정">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($batch['status'] === 'pending'): ?>
                                    <button type="button" class="btn btn-success btn-sm process-batch" data-id="<?php echo $batch['id']; ?>" data-status="in_progress" title="처리 시작">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($batch['status'] === 'in_progress'): ?>
                                    <button type="button" class="btn btn-success btn-sm process-batch" data-id="<?php echo $batch['id']; ?>" data-status="completed" title="완료 처리">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($batch['status'] === 'completed'): ?>
                                    <button type="button" class="btn btn-info btn-sm process-batch" data-id="<?php echo $batch['id']; ?>" data-status="distributed" title="배포 처리">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($batch['status'] === 'pending' || $batch['status'] === 'in_progress'): ?>
                                    <button type="button" class="btn btn-warning btn-sm process-batch" data-id="<?php echo $batch['id']; ?>" data-status="cancelled" title="취소">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($batch['status'] === 'pending' || $batch['status'] === 'cancelled'): ?>
                                    <button type="button" class="btn btn-danger btn-sm delete-batch" data-id="<?php echo $batch['id']; ?>" data-name="<?php echo htmlspecialchars($batch['batch_name']); ?>" title="삭제">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 배치 추가 모달 -->
<div class="modal fade" id="addBatchModal" tabindex="-1" aria-labelledby="addBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBatchModalLabel">새 배치 추가</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addBatchForm" method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_batch">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="batch_name" class="form-label">배치명 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="batch_name" name="batch_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="lottery_product_id" class="form-label">복권 상품 <span class="text-danger">*</span></label>
                            <select class="form-select" id="lottery_product_id" name="lottery_product_id" required>
                                <option value="">선택하세요</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo htmlspecialchars($product['id']); ?>"><?php echo htmlspecialchars($product['product_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="issue_id" class="form-label">발행 코드 <span class="text-danger">*</span></label>
                            <select class="form-select" id="issue_id" name="issue_id" required>
                                <option value="">선택하세요</option>
                                <?php foreach ($issues as $issue): ?>
                                    <option value="<?php echo htmlspecialchars($issue['id']); ?>"><?php echo htmlspecialchars($issue['issue_code']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="batch_size" class="form-label">배치 크기 <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="batch_size" name="batch_size" min="1" required>
                            <div class="form-text">이 배치에 포함될 복권의 수량</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_serial" class="form-label">시작 일련번호</label>
                            <input type="text" class="form-control" id="start_serial" name="start_serial">
                        </div>
                        <div class="col-md-6">
                            <label for="end_serial" class="form-label">종료 일련번호</label>
                            <input type="text" class="form-control" id="end_serial" name="end_serial">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="status" class="form-label">상태</label>
                            <select class="form-select" id="status" name="status">
                                <option value="pending" selected>대기 중</option>
                                <option value="in_progress">진행 중</option>
                                <option value="completed">완료</option>
                                <option value="distributed">배포됨</option>
                                <option value="cancelled">취소됨</option>
                                <option value="error">오류</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="scheduled_date" class="form-label">예정일</label>
                            <input type="date" class="form-control" id="scheduled_date" name="scheduled_date">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">메모</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 배치 수정 모달 -->
<div class="modal fade" id="editBatchModal" tabindex="-1" aria-labelledby="editBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editBatchModalLabel">배치 수정</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editBatchForm" method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_batch">
                    <input type="hidden" name="batch_id" id="edit_batch_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_batch_name" class="form-label">배치명 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_batch_name" name="batch_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_lottery_product_id" class="form-label">복권 상품 <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_lottery_product_id" name="lottery_product_id" required>
                                <option value="">선택하세요</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo htmlspecialchars($product['id']); ?>"><?php echo htmlspecialchars($product['product_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_issue_id" class="form-label">발행 코드 <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_issue_id" name="issue_id" required>
                                <option value="">선택하세요</option>
                                <?php foreach ($issues as $issue): ?>
                                    <option value="<?php echo htmlspecialchars($issue['id']); ?>"><?php echo htmlspecialchars($issue['issue_code']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_batch_size" class="form-label">배치 크기 <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_batch_size" name="batch_size" min="1" required>
                            <div class="form-text">이 배치에 포함될 복권의 수량</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_start_serial" class="form-label">시작 일련번호</label>
                            <input type="text" class="form-control" id="edit_start_serial" name="start_serial">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_end_serial" class="form-label">종료 일련번호</label>
                            <input type="text" class="form-control" id="edit_end_serial" name="end_serial">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_status" class="form-label">상태</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="pending">대기 중</option>
                                <option value="in_progress">진행 중</option>
                                <option value="completed">완료</option>
                                <option value="distributed">배포됨</option>
                                <option value="cancelled">취소됨</option>
                                <option value="error">오류</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_scheduled_date" class="form-label">예정일</label>
                            <input type="date" class="form-control" id="edit_scheduled_date" name="scheduled_date">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">메모</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 배치 상세 보기 모달 -->
<div class="modal fade" id="viewBatchModal" tabindex="-1" aria-labelledby="viewBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewBatchModalLabel">배치 상세 정보</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="batchDetails">
                    <!-- 배치 상세 정보가 JavaScript로 채워집니다 -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                <a href="#" id="printBatchDetails" class="btn btn-primary" target="_blank">인쇄</a>
            </div>
        </div>
    </div>
</div>

<!-- 배치 처리 모달 -->
<div class="modal fade" id="processBatchModal" tabindex="-1" aria-labelledby="processBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="processBatchModalLabel">배치 상태 변경</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="processBatchForm" method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="process_batch">
                    <input type="hidden" name="batch_id" id="process_batch_id">
                    <input type="hidden" name="new_status" id="process_new_status">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <p>배치 <strong id="process_batch_name"></strong>의 상태를 <span id="process_status_text" class="badge bg-primary"></span>로 변경하시겠습니까?</p>
                    
                    <div class="alert alert-info" id="process_info_message"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">확인</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 배치 삭제 모달 -->
<div class="modal fade" id="deleteBatchModal" tabindex="-1" aria-labelledby="deleteBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteBatchModalLabel">배치 삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>정말로 <strong id="delete_batch_name"></strong> 배치를 삭제하시겠습니까?</p>
                <p class="text-danger">이 작업은 되돌릴 수 없습니다.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <form id="deleteBatchForm" method="post" action="">
                    <input type="hidden" name="action" value="delete_batch">
                    <input type="hidden" name="batch_id" id="delete_batch_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <button type="submit" class="btn btn-danger">삭제</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// 페이지 하단 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // DataTable 초기화
    $('#batchesTable').DataTable({
        language: {
            url: '/assets/js/dataTables.korean.json'
        },
        responsive: true,
        order: [[9, 'desc']] // 생성일 기준으로 내림차순 정렬
    });
    
    // 배치 크기 변경 시 자동으로 종료 일련번호 계산
    document.getElementById('batch_size').addEventListener('change', function() {
        const startSerial = document.getElementById('start_serial').value;
        const batchSize = parseInt(this.value);
        
        if (startSerial && !isNaN(batchSize)) {
            try {
                // 시작 일련번호가 숫자인 경우
                const startNum = parseInt(startSerial);
                if (!isNaN(startNum)) {
                    const endNum = startNum + batchSize - 1;
                    document.getElementById('end_serial').value = endNum.toString();
                }
                // 시작 일련번호가 알파벳+숫자 형태인 경우
                else if (/^([A-Za-z]+)(\d+)$/.test(startSerial)) {
                    const matches = startSerial.match(/^([A-Za-z]+)(\d+)$/);
                    const prefix = matches[1];
                    const startNum = parseInt(matches[2]);
                    const endNum = startNum + batchSize - 1;
                    document.getElementById('end_serial').value = prefix + endNum.toString();
                }
            } catch (e) {
                console.error('일련번호 계산 오류:', e);
            }
        }
    });
    
    // 배치 상세 보기 버튼 클릭 이벤트
    document.querySelectorAll('.view-batch').forEach(function(button) {
        button.addEventListener('click', function() {
            const batchId = this.getAttribute('data-id');
            console.log('배치 상세 보기 버튼 클릭: ID = ' + batchId);
            
            // AJAX로 배치 데이터 가져오기
            fetch('/api/lottery/get_batch.php?id=' + batchId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const batch = data.batch;
                        
                        // 배치 상세 정보 템플릿 생성
                        let detailsHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="fw-bold">기본 정보</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="35%">배치 ID</th>
                                            <td>${batch.id}</td>
                                        </tr>
                                        <tr>
                                            <th>배치명</th>
                                            <td>${batch.batch_name}</td>
                                        </tr>
                                        <tr>
                                            <th>복권 상품</th>
                                            <td>${batch.product_name}</td>
                                        </tr>
                                        <tr>
                                            <th>발행 코드</th>
                                            <td>${batch.issue_code}</td>
                                        </tr>
                                        <tr>
                                            <th>배치 크기</th>
                                            <td>${batch.batch_size}</td>
                                        </tr>
                                        <tr>
                                            <th>일련번호 범위</th>
                                            <td>${batch.start_serial || '-'} ~ ${batch.end_serial || '-'}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold">상태 정보</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="35%">상태</th>
                                            <td>
                                                <span class="badge ${getStatusClass(batch.status)}">${getStatusText(batch.status)}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>예정일</th>
                                            <td>${batch.scheduled_date || '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>처리일</th>
                                            <td>${batch.processed_date || '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>생성자</th>
                                            <td>${batch.created_by_name || '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>생성일</th>
                                            <td>${formatDateTime(batch.created_at)}</td>
                                        </tr>
                                        <tr>
                                            <th>최종 수정일</th>
                                            <td>${batch.updated_at ? formatDateTime(batch.updated_at) : '-'}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            ${batch.notes ? `
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6 class="fw-bold">메모</h6>
                                    <div class="p-3 border rounded bg-light">
                                        ${batch.notes}
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                        `;
                        
                        document.getElementById('batchDetails').innerHTML = detailsHTML;
                        document.getElementById('printBatchDetails').href = '/dashboard/lottery/batch-print.php?id=' + batchId;
                        
                        // 모달 열기
                        new bootstrap.Modal(document.getElementById('viewBatchModal')).show();
                    } else {
                        alert('배치 정보를 가져오는 중 오류가 발생했습니다: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('배치 정보를 가져오는 중 오류가 발생했습니다.');
                });
        });
    });
    
    // 배치 수정 버튼 클릭 이벤트
    document.querySelectorAll('.edit-batch').forEach(function(button) {
        button.addEventListener('click', function() {
            const batchId = this.getAttribute('data-id');
            console.log('배치 수정 버튼 클릭: ID = ' + batchId);
            
            // AJAX로 배치 데이터 가져오기
            fetch('/api/lottery/get_batch.php?id=' + batchId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const batch = data.batch;
                        
                        // 폼에 배치 데이터 채우기
                        document.getElementById('edit_batch_id').value = batch.id;
                        document.getElementById('edit_batch_name').value = batch.batch_name;
                        document.getElementById('edit_lottery_product_id').value = batch.lottery_product_id;
                        document.getElementById('edit_issue_id').value = batch.issue_id;
                        document.getElementById('edit_batch_size').value = batch.batch_size;
                        document.getElementById('edit_start_serial').value = batch.start_serial;
                        document.getElementById('edit_end_serial').value = batch.end_serial;
                        document.getElementById('edit_status').value = batch.status;
                        document.getElementById('edit_scheduled_date').value = batch.scheduled_date ? batch.scheduled_date.split(' ')[0] : '';
                        document.getElementById('edit_notes').value = batch.notes;
                        
                        // 모달 열기
                        new bootstrap.Modal(document.getElementById('editBatchModal')).show();
                    } else {
                        alert('배치 정보를 가져오는 중 오류가 발생했습니다: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('배치 정보를 가져오는 중 오류가 발생했습니다.');
                });
        });
    });
    
    // 배치 처리 버튼 클릭 이벤트
    document.querySelectorAll('.process-batch').forEach(function(button) {
        button.addEventListener('click', function() {
            const batchId = this.getAttribute('data-id');
            const newStatus = this.getAttribute('data-status');
            console.log('배치 처리 버튼 클릭: ID = ' + batchId + ', 새 상태 = ' + newStatus);
            
            // AJAX로 배치 데이터 가져오기
            fetch('/api/lottery/get_batch.php?id=' + batchId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const batch = data.batch;
                        
                        // 처리 폼에 배치 데이터 채우기
                        document.getElementById('process_batch_id').value = batch.id;
                        document.getElementById('process_new_status').value = newStatus;
                        document.getElementById('process_batch_name').textContent = batch.batch_name;
                        document.getElementById('process_status_text').textContent = getStatusText(newStatus);
                        document.getElementById('process_status_text').className = 'badge ' + getStatusClass(newStatus);
                        
                        // 상태에 따른 안내 메시지 설정
                        let infoMessage = '';
                        switch (newStatus) {
                            case 'in_progress':
                                infoMessage = '배치 처리를 시작합니다. 이 작업은 복권을 생성하는 과정을 시작합니다.';
                                break;
                            case 'completed':
                                infoMessage = '배치 처리를 완료합니다. 이 작업은 복권 생성이 완료되었음을 의미합니다.';
                                break;
                            case 'distributed':
                                infoMessage = '배치를 배포 상태로 변경합니다. 이 작업은 생성된 복권이 판매점에 배포되었음을 의미합니다.';
                                break;
                            case 'cancelled':
                                infoMessage = '배치를 취소합니다. 이 작업은 되돌릴 수 없습니다.';
                                break;
                        }
                        document.getElementById('process_info_message').textContent = infoMessage;
                        
                        // 모달 열기
                        new bootstrap.Modal(document.getElementById('processBatchModal')).show();
                    } else {
                        alert('배치 정보를 가져오는 중 오류가 발생했습니다: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('배치 정보를 가져오는 중 오류가 발생했습니다.');
                });
        });
    });
    
    // 배치 삭제 버튼 클릭 이벤트
    document.querySelectorAll('.delete-batch').forEach(function(button) {
        button.addEventListener('click', function() {
            const batchId = this.getAttribute('data-id');
            const batchName = this.getAttribute('data-name');
            console.log('배치 삭제 버튼 클릭: ID = ' + batchId + ', 이름 = ' + batchName);
            
            // 삭제 확인 모달에 정보 설정
            document.getElementById('delete_batch_id').value = batchId;
            document.getElementById('delete_batch_name').textContent = batchName;
            
            // 모달 열기
            new bootstrap.Modal(document.getElementById('deleteBatchModal')).show();
        });
    });
    
    // 상태 클래스 반환 함수
    function getStatusClass(status) {
        switch (status) {
            case 'pending': return 'bg-secondary';
            case 'in_progress': return 'bg-primary';
            case 'completed': return 'bg-success';
            case 'distributed': return 'bg-info';
            case 'cancelled': return 'bg-danger';
            case 'error': return 'bg-warning';
            default: return 'bg-secondary';
        }
    }
    
    // 상태 텍스트 반환 함수
    function getStatusText(status) {
        switch (status) {
            case 'pending': return '대기 중';
            case 'in_progress': return '진행 중';
            case 'completed': return '완료';
            case 'distributed': return '배포됨';
            case 'cancelled': return '취소됨';
            case 'error': return '오류';
            default: return status;
        }
    }
    
    // 날짜 포맷 함수
    function formatDateTime(dateString) {
        if (!dateString) return '-';
        
        const date = new Date(dateString);
        return date.getFullYear() + '-' + 
               String(date.getMonth() + 1).padStart(2, '0') + '-' + 
               String(date.getDate()).padStart(2, '0') + ' ' + 
               String(date.getHours()).padStart(2, '0') + ':' + 
               String(date.getMinutes()).padStart(2, '0');
    }
});
</script>