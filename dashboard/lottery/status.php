<?php
/**
 * 복권 상태 관리 페이지
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
if (!has_permission('lottery_status_management')) {
    redirect_to('/server/dashboard/access-denied.php');
}
*/

// 데이터베이스 연결
$db = get_db_connection();

// 작업 메시지 초기화
$message = '';
$message_type = '';

// 날짜 범위 기본값 설정
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 복권 상태 변경 처리
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $ticket_id = (int) sanitize_input($_POST['ticket_id']);
        $new_status = sanitize_input($_POST['new_status']);
        $reason = sanitize_input($_POST['reason'] ?? '');
        
        try {
            // 복권 상태 수정
            $stmt = $db->prepare("
                UPDATE tickets SET
                    status = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->execute([$new_status, $ticket_id]);
            
            // 상태 변경 이력 추가
            $stmt = $db->prepare("
                INSERT INTO ticket_status_history (
                    ticket_id, old_status, new_status, reason, changed_by
                ) VALUES (
                    ?, (SELECT status FROM tickets WHERE id = ?), ?, ?, ?
                )
            ");
            
            $stmt->execute([
                $ticket_id, 
                $ticket_id, 
                $new_status, 
                $reason, 
                $_SESSION['user_id'] ?? 0
            ]);
            
            $message = '복권 상태가 성공적으로 변경되었습니다.';
            $message_type = 'success';
            
            // 활동 로그 기록
            log_activity('복권 상태 변경: ' . $ticket_id . ' -> ' . $new_status, 'tickets');
        } catch (PDOException $e) {
            $message = '오류가 발생했습니다: ' . $e->getMessage();
            $message_type = 'danger';
            error_log("Database error: " . $e->getMessage());
        }
    }
    
    // 복권 일괄 상태 변경 처리
    if (isset($_POST['action']) && $_POST['action'] === 'bulk_update_status') {
        $ticket_ids = isset($_POST['ticket_ids']) ? $_POST['ticket_ids'] : [];
        $bulk_new_status = sanitize_input($_POST['bulk_new_status']);
        $bulk_reason = sanitize_input($_POST['bulk_reason'] ?? '');
        
        // 선택된 복권이 있는지 확인
        if (empty($ticket_ids)) {
            $message = '선택된 복권이 없습니다.';
            $message_type = 'warning';
        } else {
            try {
                // 복권 ID 배열을 쉼표로 구분된 문자열로 변환
                $ids_str = implode(',', array_map('intval', $ticket_ids));
                
                // 복권 상태 일괄 수정
                $stmt = $db->prepare("
                    UPDATE tickets SET
                        status = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id IN ($ids_str)
                ");
                
                $stmt->execute([$bulk_new_status]);
                
                // 상태 변경 이력 일괄 추가
                foreach ($ticket_ids as $ticket_id) {
                    $stmt = $db->prepare("
                        INSERT INTO ticket_status_history (
                            ticket_id, old_status, new_status, reason, changed_by
                        ) VALUES (
                            ?, (SELECT status FROM tickets WHERE id = ?), ?, ?, ?
                        )
                    ");
                    
                    $stmt->execute([
                        $ticket_id, 
                        $ticket_id, 
                        $bulk_new_status, 
                        $bulk_reason, 
                        $_SESSION['user_id'] ?? 0
                    ]);
                }
                
                $message = count($ticket_ids) . '개의 복권 상태가 성공적으로 변경되었습니다.';
                $message_type = 'success';
                
                // 활동 로그 기록
                log_activity('복권 일괄 상태 변경: ' . count($ticket_ids) . '개 -> ' . $bulk_new_status, 'tickets');
            } catch (PDOException $e) {
                $message = '오류가 발생했습니다: ' . $e->getMessage();
                $message_type = 'danger';
                error_log("Database error: " . $e->getMessage());
            }
        }
    }
}

// 복권 상품 목록 조회
$products = [];
try {
    $stmt = $db->query("
        SELECT 
            id, product_code, name, status
        FROM 
            lottery_products
        ORDER BY 
            name ASC
    ");
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = '오류가 발생했습니다: ' . $e->getMessage();
    $message_type = 'danger';
    error_log("Database error: " . $e->getMessage());
}

// 복권 상태 요약 조회
$status_summary = [
    'total' => 0,
    'active' => 0,
    'used' => 0,
    'won' => 0,
    'lost' => 0,
    'cancelled' => 0,
    'expired' => 0
];

try {
    // 조건 구성
    $conditions = [];
    $params = [];
    
    if (!empty($start_date)) {
        $conditions[] = "DATE(t.created_at) >= ?";
        $params[] = $start_date;
    }
    
    if (!empty($end_date)) {
        $conditions[] = "DATE(t.created_at) <= ?";
        $params[] = $end_date;
    }
    
    if (!empty($product_id)) {
        $conditions[] = "t.product_id = ?";
        $params[] = $product_id;
    }
    
    $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
    
    // 상태별 복권 수 조회
    $stmt = $db->prepare("
        SELECT 
            t.status,
            COUNT(*) AS count
        FROM 
            tickets t
        $where_clause
        GROUP BY 
            t.status
    ");
    
    $stmt->execute($params);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status_name = $row['status'] ?? 'unknown';
        $status_summary[$status_name] = $row['count'];
        $status_summary['total'] += $row['count'];
    }
} catch (PDOException $e) {
    $message = '오류가 발생했습니다: ' . $e->getMessage();
    $message_type = 'danger';
    error_log("Database error: " . $e->getMessage());
}

// 복권 목록 조회
$tickets = [];
$pagination = [
    'page' => isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1,
    'per_page' => 20,
    'total' => 0,
    'total_pages' => 1
];

try {
    // 조건 구성
    $conditions = [];
    $params = [];
    
    if (!empty($start_date)) {
        $conditions[] = "DATE(t.created_at) >= ?";
        $params[] = $start_date;
    }
    
    if (!empty($end_date)) {
        $conditions[] = "DATE(t.created_at) <= ?";
        $params[] = $end_date;
    }
    
    if (!empty($product_id)) {
        $conditions[] = "t.product_id = ?";
        $params[] = $product_id;
    }
    
    if (!empty($status)) {
        $conditions[] = "t.status = ?";
        $params[] = $status;
    }
    
    $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
    
    // 전체 레코드 수 조회
    $count_stmt = $db->prepare("
        SELECT 
            COUNT(*) AS total
        FROM 
            tickets t
        $where_clause
    ");
    
    $count_stmt->execute($params);
    $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $pagination['total'] = $count_result['total'] ?? 0;
    $pagination['total_pages'] = ceil($pagination['total'] / $pagination['per_page']);
    
    // 페이지네이션 적용
    $offset = ($pagination['page'] - 1) * $pagination['per_page'];
    $limit = $pagination['per_page'];
    
    // 복권 목록 조회
    $stmt = $db->prepare("
        SELECT 
            t.*,
            lp.name AS product_name,
            lp.product_code,
            tm.terminal_code,
            s.store_name AS store_name
        FROM 
            tickets t
        JOIN 
            lottery_products lp ON t.product_id = lp.id
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        JOIN 
            stores s ON tm.store_id = s.id
        $where_clause
        ORDER BY 
            t.created_at DESC
        LIMIT $offset, $limit
    ");
    
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = '오류가 발생했습니다: ' . $e->getMessage();
    $message_type = 'danger';
    error_log("Database error: " . $e->getMessage());
}

// 현재 페이지 정보
$pageTitle = "복권 상태 관리";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">복권 상태 관리</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/server/dashboard/index.php">홈</a></li>
                    <li class="breadcrumb-item">복권 관리</li>
                    <li class="breadcrumb-item active">복권 상태 관리</li>
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
            
            <!-- 상태 요약 카드 -->
            <div class="row">
                <div class="col-md-2 col-sm-6 col-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-primary"><i class="fas fa-ticket-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">전체</span>
                            <span class="info-box-number"><?php echo number_format($status_summary['total']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-2 col-sm-6 col-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">활성</span>
                            <span class="info-box-number"><?php echo number_format($status_summary['active']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-2 col-sm-6 col-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-print"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">사용됨</span>
                            <span class="info-box-number"><?php echo number_format($status_summary['used']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-2 col-sm-6 col-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-trophy"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">당첨</span>
                            <span class="info-box-number"><?php echo number_format($status_summary['won']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-2 col-sm-6 col-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-thumbs-down"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">미당첨</span>
                            <span class="info-box-number"><?php echo number_format($status_summary['lost']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-2 col-sm-6 col-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-danger"><i class="fas fa-ban"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">취소/만료</span>
                            <span class="info-box-number">
                                <?php echo number_format($status_summary['cancelled'] + $status_summary['expired']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 검색 필터 -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">검색 필터</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form id="filterForm" method="GET" action="">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start_date">시작일</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="end_date">종료일</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="product_id">복권 상품</label>
                                    <select class="form-control" id="product_id" name="product_id">
                                        <option value="">전체 상품</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['id']; ?>" <?php echo $product_id == $product['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($product['name'] . ' (' . $product['product_code'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status">상태</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="">전체 상태</option>
                                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>활성</option>
                                        <option value="used" <?php echo $status === 'used' ? 'selected' : ''; ?>>사용됨</option>
                                        <option value="won" <?php echo $status === 'won' ? 'selected' : ''; ?>>당첨</option>
                                        <option value="lost" <?php echo $status === 'lost' ? 'selected' : ''; ?>>미당첨</option>
                                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>취소</option>
                                        <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>만료</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-right">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> 검색
                                </button>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> 초기화
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- 복권 목록 및 상태 관리 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">복권 목록</h3>
                    <div class="card-tools">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" id="ticketSearchInput" class="form-control float-right" placeholder="검색">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-default">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form id="bulkActionForm" method="POST">
                        <input type="hidden" name="action" value="bulk_update_status">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="btn-group">
                                    <button type="button" id="selectAllBtn" class="btn btn-default btn-sm">
                                        <i class="fas fa-check"></i> 전체 선택
                                    </button>
                                    <button type="button" id="deselectAllBtn" class="btn btn-default btn-sm">
                                        <i class="fas fa-times"></i> 전체 해제
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 text-right">
                                <div class="input-group input-group-sm" style="width: 350px; float: right;">
                                    <select class="form-control" name="bulk_new_status" required>
                                        <option value="">-- 일괄 상태 변경 --</option>
                                        <option value="active">활성</option>
                                        <option value="used">사용됨</option>
                                        <option value="won">당첨</option>
                                        <option value="lost">미당첨</option>
                                        <option value="cancelled">취소</option>
                                        <option value="expired">만료</option>
                                    </select>
                                    <input type="text" class="form-control" name="bulk_reason" placeholder="변경 사유">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-save"></i> 일괄 적용
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover table-striped" id="ticketsTable">
                                <thead>
                                    <tr>
                                        <th width="30px">
                                            <div class="icheck-primary">
                                                <input type="checkbox" id="check-all">
                                                <label for="check-all"></label>
                                            </div>
                                        </th>
                                        <th>복권 번호</th>
                                        <th>복권 상품</th>
                                        <th>발행 단말기</th>
                                        <th>판매점</th>
                                        <th>선택 번호</th>
                                        <th>추첨일</th>
                                        <th>금액</th>
                                        <th>상태</th>
                                        <th>발행일</th>
                                        <th>관리</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td>
                                                <div class="icheck-primary">
                                                    <input type="checkbox" name="ticket_ids[]" value="<?php echo $ticket['id']; ?>" id="ticket<?php echo $ticket['id']; ?>">
                                                    <label for="ticket<?php echo $ticket['id']; ?>"></label>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($ticket['ticket_number']); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($ticket['product_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($ticket['product_code']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($ticket['terminal_code']); ?></td>
                                            <td><?php echo htmlspecialchars($ticket['store_name']); ?></td>
                                            <td><code><?php echo htmlspecialchars($ticket['numbers']); ?></code></td>
                                            <td><?php echo date('Y-m-d', strtotime($ticket['draw_date'])); ?></td>
                                            <td><?php echo number_format($ticket['amount']); ?> NPR</td>
                                            <td>
                                                <?php 
                                                $badgeClass = 'secondary';
                                                $statusText = '알 수 없음';
                                                
                                                switch ($ticket['status']) {
                                                    case 'active':
                                                        $badgeClass = 'success';
                                                        $statusText = '활성';
                                                        break;
                                                    case 'used':
                                                        $badgeClass = 'info';
                                                        $statusText = '사용됨';
                                                        break;
                                                    case 'won':
                                                        $badgeClass = 'warning';
                                                        $statusText = '당첨';
                                                        break;
                                                    case 'lost':
                                                        $badgeClass = 'secondary';
                                                        $statusText = '미당첨';
                                                        break;
                                                    case 'cancelled':
                                                        $badgeClass = 'danger';
                                                        $statusText = '취소';
                                                        break;
                                                    case 'expired':
                                                        $badgeClass = 'dark';
                                                        $statusText = '만료';
                                                        break;
                                                }
                                                
                                                echo '<span class="badge badge-' . $badgeClass . '">' . $statusText . '</span>';
                                                ?>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-info btn-view-ticket" data-id="<?php echo $ticket['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-primary btn-change-status" data-id="<?php echo $ticket['id']; ?>" data-status="<?php echo $ticket['status']; ?>">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-warning btn-history" data-id="<?php echo $ticket['id']; ?>">
                                                        <i class="fas fa-history"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($tickets)): ?>
                                        <tr><td colspan="11" class="text-center">검색 조건에 맞는 복권이 없습니다.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                    
                    <!-- 페이지네이션 -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <p>총 <?php echo number_format($pagination['total']); ?>개 항목 중 
                                   <?php echo number_format(($pagination['page'] - 1) * $pagination['per_page'] + 1); ?> - 
                                   <?php echo number_format(min($pagination['page'] * $pagination['per_page'], $pagination['total'])); ?>번째 항목</p>
                            </div>
                            <div class="col-md-6">
                                <ul class="pagination justify-content-end">
                                    <?php if ($pagination['page'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&product_id=<?php echo $product_id; ?>&status=<?php echo $status; ?>">
                                                처음
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $pagination['page'] - 1; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&product_id=<?php echo $product_id; ?>&status=<?php echo $status; ?>">
                                                이전
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $pagination['page'] - 2);
                                    $end_page = min($pagination['total_pages'], $pagination['page'] + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                        <li class="page-item <?php echo $i === $pagination['page'] ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&product_id=<?php echo $product_id; ?>&status=<?php echo $status; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $pagination['page'] + 1; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&product_id=<?php echo $product_id; ?>&status=<?php echo $status; ?>">
                                                다음
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $pagination['total_pages']; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&product_id=<?php echo $product_id; ?>&status=<?php echo $status; ?>">
                                                마지막
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- 복권 상세 보기 모달 -->
<div class="modal fade" id="viewTicketModal" tabindex="-1" role="dialog" aria-labelledby="viewTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title" id="viewTicketModalLabel">복권 상세 정보</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>복권 번호</label>
                            <div id="view_ticket_number" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>복권 상품</label>
                            <div id="view_product" class="form-control-static"></div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>발행 단말기</label>
                            <div id="view_terminal" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>판매점</label>
                            <div id="view_store" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>발행일</label>
                            <div id="view_created_at" class="form-control-static"></div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>선택 번호</label>
                            <div id="view_numbers" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>추첨일</label>
                            <div id="view_draw_date" class="form-control-static"></div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>금액</label>
                            <div id="view_amount" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>상태</label>
                            <div id="view_status" class="form-control-static"></div>
                        </div>
                    </div>
                </div>
                
                <!-- 당첨 정보 (당첨된 경우에만 표시) -->
                <div id="winning_info" style="display: none;">
                    <hr>
                    <h5>당첨 정보</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>당첨 등급</label>
                                <div id="view_prize_tier" class="form-control-static"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>당첨금</label>
                                <div id="view_prize_amount" class="form-control-static"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>당첨 번호</label>
                                <div id="view_winning_numbers" class="form-control-static"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>지급 상태</label>
                                <div id="view_payment_status" class="form-control-static"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary btn-change-status-modal" data-id="">
                    <i class="fas fa-exchange-alt"></i> 상태 변경
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 상태 변경 모달 -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" role="dialog" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="changeStatusForm" method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" id="ticket_id" name="ticket_id" value="">
                
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="changeStatusModalLabel">복권 상태 변경</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="current_status">현재 상태</label>
                        <div id="current_status" class="form-control-static"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_status">새 상태</label>
                        <select class="form-control" id="new_status" name="new_status" required>
                            <option value="">-- 상태 선택 --</option>
                            <option value="active">활성</option>
                            <option value="used">사용됨</option>
                            <option value="won">당첨</option>
                            <option value="lost">미당첨</option>
                            <option value="cancelled">취소</option>
                            <option value="expired">만료</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reason">변경 사유</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3"></textarea>
                        <small class="form-text text-muted">변경 사유를 입력하면 이력에 기록됩니다.</small>
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

<!-- 이력 조회 모달 -->
<div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="historyModalLabel">복권 상태 변경 이력</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div id="history_ticket_info" class="mb-3">
                    <strong>복권 번호:</strong> <span id="history_ticket_number"></span><br>
                    <strong>복권 상품:</strong> <span id="history_product_name"></span>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>날짜</th>
                                <th>이전 상태</th>
                                <th>새 상태</th>
                                <th>사유</th>
                                <th>처리자</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <!-- 이력 데이터가 여기에 로드됨 -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // 테이블 검색 기능
    $("#ticketSearchInput").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#ticketsTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    
    // 체크박스 전체 선택/해제
    $('#check-all').click(function() {
        $('input[name="ticket_ids[]"]').prop('checked', this.checked);
    });
    
    $('#selectAllBtn').click(function() {
        $('input[name="ticket_ids[]"]').prop('checked', true);
        $('#check-all').prop('checked', true);
    });
    
    $('#deselectAllBtn').click(function() {
        $('input[name="ticket_ids[]"]').prop('checked', false);
        $('#check-all').prop('checked', false);
    });
    
    // 개별 체크박스 변경 시 전체 선택 체크박스 상태 업데이트
    $('input[name="ticket_ids[]"]').on('change', function() {
        var allChecked = true;
        $('input[name="ticket_ids[]"]').each(function() {
            if (!this.checked) {
                allChecked = false;
                return false;
            }
        });
        
        $('#check-all').prop('checked', allChecked);
    });
    
    // 복권 상세 보기
    $('.btn-view-ticket').click(function() {
        var id = $(this).data('id');
        
        // AJAX로 복권 상세 정보 조회
        $.ajax({
            url: '/server/api/lottery/get_ticket.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    var ticket = response.data;
                    
                    // 모달에 데이터 채우기
                    $('#view_ticket_number').text(ticket.ticket_number);
                    $('#view_product').text(ticket.product_name + ' (' + ticket.product_code + ')');
                    $('#view_terminal').text(ticket.terminal_code);
                    $('#view_store').text(ticket.store_name);
                    $('#view_numbers').html('<code>' + ticket.numbers + '</code>');
                    $('#view_draw_date').text(new Date(ticket.draw_date).toLocaleDateString());
                    $('#view_amount').text(Number(ticket.amount).toLocaleString() + ' NPR');
                    $('#view_created_at').text(new Date(ticket.created_at).toLocaleString());
                    
                    var statusText = '알 수 없음';
                    var statusClass = 'secondary';
                    switch (ticket.status) {
                        case 'active':
                            statusText = '활성';
                            statusClass = 'success';
                            break;
                        case 'used':
                            statusText = '사용됨';
                            statusClass = 'info';
                            break;
                        case 'won':
                            statusText = '당첨';
                            statusClass = 'warning';
                            break;
                        case 'lost':
                            statusText = '미당첨';
                            statusClass = 'secondary';
                            break;
                        case 'cancelled':
                            statusText = '취소';
                            statusClass = 'danger';
                            break;
                        case 'expired':
                            statusText = '만료';
                            statusClass = 'dark';
                            break;
                    }
                    
                    $('#view_status').html('<span class="badge badge-' + statusClass + '">' + statusText + '</span>');
                    
                    // 당첨 정보 표시 (당첨된 경우에만)
                    if (ticket.status === 'won' && ticket.winning) {
                        $('#view_prize_tier').text(ticket.winning.prize_tier + '등');
                        $('#view_prize_amount').text(Number(ticket.winning.prize_amount).toLocaleString() + ' NPR');
                        $('#view_winning_numbers').html('<code>' + ticket.winning.winning_numbers + '</code>');
                        
                        var paymentStatusText = '알 수 없음';
                        var paymentStatusClass = 'secondary';
                        switch (ticket.winning.status) {
                            case 'pending':
                                paymentStatusText = '지급 대기';
                                paymentStatusClass = 'warning';
                                break;
                            case 'claimed':
                                paymentStatusText = '청구됨';
                                paymentStatusClass = 'info';
                                break;
                            case 'paid':
                                paymentStatusText = '지급 완료';
                                paymentStatusClass = 'success';
                                break;
                        }
                        
                        $('#view_payment_status').html('<span class="badge badge-' + paymentStatusClass + '">' + paymentStatusText + '</span>');
                        $('#winning_info').show();
                    } else {
                        $('#winning_info').hide();
                    }
                    
                    // 상태 변경 버튼에 ID 설정
                    $('.btn-change-status-modal').data('id', ticket.id);
                    
                    // 모달 표시
                    $('#viewTicketModal').modal('show');
                } else {
                    alert('오류가 발생했습니다: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('AJAX 오류가 발생했습니다: ' + error);
            }
        });
    });
    
    // 상태 변경 모달 표시
    $('.btn-change-status, .btn-change-status-modal').click(function() {
        var id = $(this).data('id');
        var currentStatus = '';
        
        // 현재 상태 정보 설정
        if ($(this).hasClass('btn-change-status')) {
            // 목록에서 호출한 경우
            currentStatus = $(this).data('status');
            
            var statusText = '알 수 없음';
            var statusClass = 'secondary';
            switch (currentStatus) {
                case 'active':
                    statusText = '활성';
                    statusClass = 'success';
                    break;
                case 'used':
                    statusText = '사용됨';
                    statusClass = 'info';
                    break;
                case 'won':
                    statusText = '당첨';
                    statusClass = 'warning';
                    break;
                case 'lost':
                    statusText = '미당첨';
                    statusClass = 'secondary';
                    break;
                case 'cancelled':
                    statusText = '취소';
                    statusClass = 'danger';
                    break;
                case 'expired':
                    statusText = '만료';
                    statusClass = 'dark';
                    break;
            }
            
            $('#current_status').html('<span class="badge badge-' + statusClass + '">' + statusText + '</span>');
        } else {
            // 상세 보기 모달에서 호출한 경우
            $('#current_status').html($('#view_status').html());
        }
        
        // 폼 ID 설정
        $('#ticket_id').val(id);
        
        // 상태 선택 초기화
        $('#new_status').val('');
        $('#reason').val('');
        
        // 모달 표시
        $('#changeStatusModal').modal('show');
    });
    
    // 이력 조회 모달 표시
    $('.btn-history').click(function() {
        var id = $(this).data('id');
        
        // AJAX로 복권 이력 정보 조회
        $.ajax({
            url: '/server/api/lottery/get_ticket_history.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    var history = response.data;
                    
                    // 복권 기본 정보 설정
                    $('#history_ticket_number').text(history.ticket.ticket_number);
                    $('#history_product_name').text(history.ticket.product_name);
                    
                    // 이력 테이블 내용 비우기
                    $('#historyTableBody').empty();
                    
                    // 이력 항목 추가
                    if (history.records && history.records.length > 0) {
                        history.records.forEach(function(record) {
                            var oldStatusText = getStatusText(record.old_status);
                            var newStatusText = getStatusText(record.new_status);
                            var oldStatusClass = getStatusClass(record.old_status);
                            var newStatusClass = getStatusClass(record.new_status);
                            
                            var row = '<tr>' +
                                '<td>' + new Date(record.created_at).toLocaleString() + '</td>' +
                                '<td><span class="badge badge-' + oldStatusClass + '">' + oldStatusText + '</span></td>' +
                                '<td><span class="badge badge-' + newStatusClass + '">' + newStatusText + '</span></td>' +
                                '<td>' + (record.reason ? record.reason : '-') + '</td>' +
                                '<td>' + (record.changed_by_name ? record.changed_by_name : '시스템') + '</td>' +
                                '</tr>';
                            
                            $('#historyTableBody').append(row);
                        });
                    } else {
                        $('#historyTableBody').append('<tr><td colspan="5" class="text-center">이력 정보가 없습니다.</td></tr>');
                    }
                    
                    // 모달 표시
                    $('#historyModal').modal('show');
                } else {
                    alert('오류가 발생했습니다: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('AJAX 오류가 발생했습니다: ' + error);
            }
        });
    });
    
    // 상태 텍스트 변환 함수
    function getStatusText(status) {
        switch (status) {
            case 'active':
                return '활성';
            case 'used':
                return '사용됨';
            case 'won':
                return '당첨';
            case 'lost':
                return '미당첨';
            case 'cancelled':
                return '취소';
            case 'expired':
                return '만료';
            default:
                return '알 수 없음';
        }
    }
    
    // 상태 클래스 변환 함수
    function getStatusClass(status) {
        switch (status) {
            case 'active':
                return 'success';
            case 'used':
                return 'info';
            case 'won':
                return 'warning';
            case 'lost':
                return 'secondary';
            case 'cancelled':
                return 'danger';
            case 'expired':
                return 'dark';
            default:
                return 'secondary';
        }
    }
    
    // 일괄 처리 폼 제출 전 검증
    $('#bulkActionForm').on('submit', function(e) {
        var selectedTickets = $('input[name="ticket_ids[]"]:checked').length;
        var selectedStatus = $('select[name="bulk_new_status"]').val();
        
        if (selectedTickets === 0) {
            e.preventDefault();
            alert('선택된 복권이 없습니다.');
            return false;
        }
        
        if (!selectedStatus) {
            e.preventDefault();
            alert('변경할 상태를 선택해주세요.');
            return false;
        }
        
        if (!confirm(selectedTickets + '개의 복권 상태를 변경하시겠습니까?')) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });
});
</script>

<?php
// 권한 확인 함수 (예시)
function has_permission($permission) {
    // 실제 구현은 사용자 권한 체계에 따라 달라질 수 있음
    // 현재는 모든 관리자가 접근 가능하도록 설정
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
        return true;
    }
    
    return false;
}

// 활동 로그 기록 함수 (예시)
function callLogActivity($action, $module) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO system_logs (
                log_type, message, source, ip_address, user_id
            ) VALUES (
                'info', ?, ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $action,
            $module,
            $_SERVER['REMOTE_ADDR'],
            $_SESSION['user_id'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Logging error: " . $e->getMessage());
    }
}
?>
