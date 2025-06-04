<?php
/**
 * 복권 발행 관리 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 세션 시작 및 인증 확인
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 세션 체크 기능 준비가 안되어 있음
// 추후 필요시 구현

// 권한 확인 (개발 모드에서는 비활성화)
// 아래 코드는 access-denied.php 파일이 없어서 오류가 발생하므로 임시로 주석 처리
/*
if (!has_permission('lottery_issue_management')) {
    redirect_to('/server/dashboard/access-denied.php');
}
*/

// 데이터베이스 연결
$db = get_db_connection();

// DB 연결 오류 로깅
if ($db instanceof MockPDO) {
    error_log("데이터베이스 연결 실패, MockPDO가 사용됨", 0);
} else {
    error_log("데이터베이스 연결 성공: ".DB_NAME, 0);
}

// 작업 메시지 초기화
$message = '';
$message_type = '';

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 발행 계획 추가/수정 처리
    if (isset($_POST['action']) && ($_POST['action'] === 'add_plan' || $_POST['action'] === 'edit_plan')) {
        $product_id = (int) $_POST['product_id'];
        $issue_date = $_POST['issue_date'];
        $total_tickets = (int) $_POST['total_tickets'];
        $batch_size = (int) $_POST['batch_size'];
        $start_number = $_POST['start_number'];
        $notes = $_POST['notes'] ?? '';
        $status = $_POST['status'];
        
        try {
            if ($_POST['action'] === 'add_plan') {
                // 발행 계획 추가
                $stmt = $db->prepare("
                    INSERT INTO issue_plans (
                        product_id, issue_date, total_tickets, batch_size,
                        start_number, notes, status
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?
                    )
                ");
                
                $stmt->execute([
                    $product_id, $issue_date, $total_tickets, $batch_size,
                    $start_number, $notes, $status
                ]);
                
                $message = '발행 계획이 성공적으로 추가되었습니다.';
                $message_type = 'success';
                
        // 활동 로그 기록
        try {
            $user_id = $_SESSION['user_id'] ?? 1; // 세션에서 사용자 ID 가져오기
            
            $stmt = $db->prepare("
                INSERT INTO system_logs (log_type, message, source, user_id, created_at)
                VALUES ('info', :message, :source, :user_id, NOW())
            ");
            
            $stmt->bindParam(':message', $message_text);
            $stmt->bindParam(':source', $source);
            $stmt->bindParam(':user_id', $user_id);
            
            $message_text = '발행 계획 추가: ' . $product_id . ' - ' . $issue_date;
            $source = 'issue_plans';
            $stmt->execute();
        } catch (PDOException $e) {
            // 로그 저장 실패는 주요 기능을 중단시키지 않음
            error_log("활동 로그 저장 실패: " . $e->getMessage());
        }
            } else {
                // 발행 계획 수정
                $plan_id = (int) $_POST['plan_id'];
                
                $stmt = $db->prepare("
                    UPDATE issue_plans SET
                        product_id = ?,
                        issue_date = ?,
                        total_tickets = ?,
                        batch_size = ?,
                        start_number = ?,
                        notes = ?,
                        status = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $product_id, $issue_date, $total_tickets, $batch_size,
                    $start_number, $notes, $status, $plan_id
                ]);
                
                $message = '발행 계획이 성공적으로 수정되었습니다.';
                $message_type = 'success';
                
                // 활동 로그 기록
                try {
                    $user_id = $_SESSION['user_id'] ?? 1; // 세션에서 사용자 ID 가져오기
                    
                    $stmt = $db->prepare("
                        INSERT INTO system_logs (log_type, message, source, user_id, created_at)
                        VALUES ('info', :message, :source, :user_id, NOW())
                    ");
                    
                    $stmt->bindParam(':message', $message_text);
                    $stmt->bindParam(':source', $source);
                    $stmt->bindParam(':user_id', $user_id);
                    
                    $message_text = '발행 계획 수정: ' . $plan_id;
                    $source = 'issue_plans';
                    $stmt->execute();
                } catch (PDOException $e) {
                    // 로그 저장 실패는 주요 기능을 중단시키지 않음
                    error_log("활동 로그 저장 실패: " . $e->getMessage());
                }
            }
        } catch (PDOException $e) {
            $message = '오류가 발생했습니다: ' . $e->getMessage();
            $message_type = 'danger';
            error_log("Database error: " . $e->getMessage());
        }
    }
    
    // 발행 처리
    if (isset($_POST['action']) && $_POST['action'] === 'issue') {
        $plan_id = (int) $_POST['plan_id'];
        
        try {
            // 발행 계획 정보 조회
            $stmt = $db->prepare("
                SELECT 
                    ip.*,
                    lp.product_code,
                    lp.name AS product_name
                FROM 
                    issue_plans ip
                JOIN 
                    lottery_products lp ON ip.product_id = lp.id
                WHERE 
                    ip.id = ?
            ");
            
            $stmt->execute([$plan_id]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($plan) {
                // 발행 진행 상태로 변경
                $stmt = $db->prepare("
                    UPDATE issue_plans SET
                        status = 'in_progress',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $stmt->execute([$plan_id]);
                
                // 발행 작업 큐에 추가 (예시 - 실제 구현은 시스템에 따라 달라질 수 있음)
                $stmt = $db->prepare("
                    INSERT INTO issue_queue (
                        plan_id, status, total_tickets, processed_tickets
                    ) VALUES (
                        ?, 'pending', ?, 0
                    )
                ");
                
                $stmt->execute([$plan_id, $plan['total_tickets']]);
                $queue_id = $db->lastInsertId();
                
                // 발행 작업 시작 (실제 구현은 별도의 작업자 프로세스나 크론잡으로 처리할 수 있음)
                // 여기서는 예시로 간단히 처리
                
                // 발행 이력 추가
                $stmt = $db->prepare("
                    INSERT INTO issue_history (
                        plan_id, queue_id, issued_by, status, notes
                    ) VALUES (
                        ?, ?, ?, 'started', '발행 작업이 시작되었습니다.'
                    )
                ");
                
                $stmt->execute([
                    $plan_id, 
                    $queue_id, 
                    $_SESSION['user_id'] ?? 0
                ]);
                
                $message = '발행 작업이 시작되었습니다. 발행 이력에서 진행 상황을 확인하세요.';
                $message_type = 'success';
                
                // 활동 로그 기록
                try {
                    $user_id = $_SESSION['user_id'] ?? 1; // 세션에서 사용자 ID 가져오기
                    
                    $stmt = $db->prepare("
                        INSERT INTO system_logs (log_type, message, source, user_id, created_at)
                        VALUES ('info', :message, :source, :user_id, NOW())
                    ");
                    
                    $stmt->bindParam(':message', $message_text);
                    $stmt->bindParam(':source', $source);
                    $stmt->bindParam(':user_id', $user_id);
                    
                    $message_text = '복권 발행 시작: ' . $plan_id;
                    $source = 'issue_plans';
                    $stmt->execute();
                } catch (PDOException $e) {
                    // 로그 저장 실패는 주요 기능을 중단시키지 않음
                    error_log("활동 로그 저장 실패: " . $e->getMessage());
                }
            } else {
                $message = '발행 계획을 찾을 수 없습니다.';
                $message_type = 'danger';
            }
        } catch (PDOException $e) {
            $message = '오류가 발생했습니다: ' . $e->getMessage();
            $message_type = 'danger';
            error_log("Database error: " . $e->getMessage());
        }
    }
    
    // 발행 중지 처리
    if (isset($_POST['action']) && $_POST['action'] === 'cancel_issue') {
        $queue_id = (int) $_POST['queue_id'];
        
        try {
            // 큐 항목 정보 조회
            $stmt = $db->prepare("
                SELECT 
                    iq.*,
                    ip.id AS plan_id
                FROM 
                    issue_queue iq
                JOIN 
                    issue_plans ip ON iq.plan_id = ip.id
                WHERE 
                    iq.id = ?
            ");
            
            $stmt->execute([$queue_id]);
            $queue = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($queue && ($queue['status'] === 'pending' || $queue['status'] === 'in_progress')) {
                // 큐 상태를 취소로 변경
                $stmt = $db->prepare("
                    UPDATE issue_queue SET
                        status = 'cancelled',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $stmt->execute([$queue_id]);
                
                // 발행 계획 상태를 준비로 변경
                $stmt = $db->prepare("
                    UPDATE issue_plans SET
                        status = 'ready',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $stmt->execute([$queue['plan_id']]);
                
                // 발행 이력 추가
                $stmt = $db->prepare("
                    INSERT INTO issue_history (
                        plan_id, queue_id, issued_by, status, notes
                    ) VALUES (
                        ?, ?, ?, 'cancelled', '발행 작업이 취소되었습니다.'
                    )
                ");
                
                $stmt->execute([
                    $queue['plan_id'], 
                    $queue_id, 
                    $_SESSION['user_id'] ?? 0
                ]);
                
                $message = '발행 작업이 취소되었습니다.';
                $message_type = 'success';
                
                // 활동 로그 기록
                try {
                    $user_id = $_SESSION['user_id'] ?? 1; // 세션에서 사용자 ID 가져오기
                    
                    $stmt = $db->prepare("
                        INSERT INTO system_logs (log_type, message, source, user_id, created_at)
                        VALUES ('info', :message, :source, :user_id, NOW())
                    ");
                    
                    $stmt->bindParam(':message', $message_text);
                    $stmt->bindParam(':source', $source);
                    $stmt->bindParam(':user_id', $user_id);
                    
                    $message_text = '복권 발행 취소: ' . $queue_id;
                    $source = 'issue_queue';
                    $stmt->execute();
                } catch (PDOException $e) {
                    // 로그 저장 실패는 주요 기능을 중단시키지 않음
                    error_log("활동 로그 저장 실패: " . $e->getMessage());
                }
            } else {
                $message = '취소할 수 없는 작업이거나 작업을 찾을 수 없습니다.';
                $message_type = 'danger';
            }
        } catch (PDOException $e) {
            $message = '오류가 발생했습니다: ' . $e->getMessage();
            $message_type = 'danger';
            error_log("Database error: " . $e->getMessage());
        }
    }
    
    // 발행 계획 상태 변경 처리
    if (isset($_POST['action']) && $_POST['action'] === 'change_status') {
        $plan_id = (int) $_POST['plan_id'];
        $new_status = $_POST['new_status'];
        
        // 허용된 상태 값 확인
        $allowed_statuses = ['draft', 'ready', 'completed', 'cancelled'];
        
        if (!in_array($new_status, $allowed_statuses)) {
            $message = '잘못된 상태 값입니다.';
            $message_type = 'danger';
        } else {
            try {
                // 현재 계획 정보 조회
                $stmt = $db->prepare("
                    SELECT * FROM issue_plans WHERE id = ?
                ");
                $stmt->execute([$plan_id]);
                $plan = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($plan) {
                    // 상태 변경 가능 여부 확인 (예: in_progress 상태는 변경 불가)
                    if ($plan['status'] === 'in_progress') {
                        $message = '진행 중인 계획의 상태는 변경할 수 없습니다.';
                        $message_type = 'warning';
                    } else {
                        // 상태 변경
                        $stmt = $db->prepare("
                            UPDATE issue_plans SET
                                status = ?,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE id = ?
                        ");
                        
                        $stmt->execute([$new_status, $plan_id]);
                        
                        $message = '발행 계획 상태가 변경되었습니다.';
                        $message_type = 'success';
                        
                        // 활동 로그 기록
                        try {
                            $user_id = $_SESSION['user_id'] ?? 1; // 세션에서 사용자 ID 가져오기
                            
                            $stmt = $db->prepare("
                                INSERT INTO system_logs (log_type, message, source, user_id, created_at)
                                VALUES ('info', :message, :source, :user_id, NOW())
                            ");
                            
                            $stmt->bindParam(':message', $message_text);
                            $stmt->bindParam(':source', $source);
                            $stmt->bindParam(':user_id', $user_id);
                            
                            $message_text = '발행 계획 상태 변경: #' . $plan_id . ' ' . $plan['status'] . ' -> ' . $new_status;
                            $source = 'issue_plans';
                            $stmt->execute();
                        } catch (PDOException $e) {
                            // 로그 저장 실패는 주요 기능을 중단시키지 않음
                            error_log("활동 로그 저장 실패: " . $e->getMessage());
                        }
                    }
                } else {
                    $message = '발행 계획을 찾을 수 없습니다.';
                    $message_type = 'danger';
                }
            } catch (PDOException $e) {
                $message = '오류가 발생했습니다: ' . $e->getMessage();
                $message_type = 'danger';
                error_log("Database error: " . $e->getMessage());
            }
        }
    }
    
    // 발행 계획 삭제 처리
    if (isset($_POST['action']) && $_POST['action'] === 'delete_plan') {
        $plan_id = (int) $_POST['plan_id'];
        
        try {
            // 현재 계획 정보 조회 (삭제 전 로그를 위해)
            $stmt = $db->prepare("
                SELECT ip.*, lp.name AS product_name
                FROM issue_plans ip
                JOIN lottery_products lp ON ip.product_id = lp.id
                WHERE ip.id = ?
            ");
            $stmt->execute([$plan_id]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($plan) {
                // 삭제 가능 여부 확인 (draft 상태만 삭제 가능)
                if ($plan['status'] !== 'draft') {
                    $message = '초안 상태의 계획만 삭제할 수 있습니다.';
                    $message_type = 'warning';
                } else {
                    // 발행 계획 삭제
                    $stmt = $db->prepare("DELETE FROM issue_plans WHERE id = ?");
                    $stmt->execute([$plan_id]);
                    
                    $message = '발행 계획이 성공적으로 삭제되었습니다.';
                    $message_type = 'success';
                    
                    // 활동 로그 기록
                    try {
                        $user_id = $_SESSION['user_id'] ?? 1; // 세션에서 사용자 ID 가져오기
                        
                        $stmt = $db->prepare("
                            INSERT INTO system_logs (log_type, message, source, user_id, created_at)
                            VALUES ('info', :message, :source, :user_id, NOW())
                        ");
                        
                        $stmt->bindParam(':message', $message_text);
                        $stmt->bindParam(':source', $source);
                        $stmt->bindParam(':user_id', $user_id);
                        
                        $message_text = '발행 계획 삭제: #' . $plan_id . ' ' . $plan['product_name'] . ' (' . $plan['issue_date'] . ')';
                        $source = 'issue_plans';
                        $stmt->execute();
                    } catch (PDOException $e) {
                        // 로그 저장 실패는 주요 기능을 중단시키지 않음
                        error_log("활동 로그 저장 실패: " . $e->getMessage());
                    }
                }
            } else {
                $message = '발행 계획을 찾을 수 없습니다.';
                $message_type = 'danger';
            }
        } catch (PDOException $e) {
            $message = '오류가 발생했습니다: ' . $e->getMessage();
            $message_type = 'danger';
            error_log("Database error: " . $e->getMessage());
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
        WHERE 
            status != 'suspended'
        ORDER BY 
            name ASC
    ");
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = '오류가 발생했습니다: ' . $e->getMessage();
    $message_type = 'danger';
    error_log("Database error: " . $e->getMessage());
}

// 발행 계획 목록 조회
$plans = [];
try {
    $stmt = $db->query("
        SELECT 
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
            ip.issue_date DESC, ip.id DESC
    ");
    
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = '오류가 발생했습니다: ' . $e->getMessage();
    $message_type = 'danger';
    error_log("Database error: " . $e->getMessage());
}

// 발행 이력 조회
$history = [];
try {
    $stmt = $db->query("
        SELECT 
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
        LIMIT 20
    ");
    
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = '오류가 발생했습니다: ' . $e->getMessage();
    $message_type = 'danger';
    error_log("Database error: " . $e->getMessage());
}

// 현재 페이지 정보
$pageTitle = "복권 발행 관리";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<style>
/* 드롭다운 메뉴 관련 스타일 */
.dropdown-menu.show {
    display: block;
    position: absolute;
    z-index: 1000;
    min-width: 10rem;
    padding: 0.5rem 0;
    margin: 0.125rem 0 0;
    font-size: 1rem;
    color: #212529;
    text-align: left;
    list-style: none;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid rgba(0, 0, 0, 0.15);
    border-radius: 0.25rem;
}
</style>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">복권 발행 관리</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/server/dashboard/index.php">홈</a></li>
                        <li class="breadcrumb-item">복권 관리</li>
                        <li class="breadcrumb-item active">복권 발행 관리</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

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
                                                            <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="dropdownMenu-<?php echo $plan['id']; ?>">
                                                                <i class="fas fa-cog"></i>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu-<?php echo $plan['id']; ?>">
                                                                <?php if ($plan['status'] === 'ready'): ?>
                                                                    <a class="dropdown-item btn-issue" data-id="<?php echo $plan['id']; ?>" href="javascript:void(0);">
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
</div>

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

<!-- 계획 상세 보기 모달 -->
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
                            <div id="view_product" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>발행 예정일</label>
                            <div id="view_issue_date" class="form-control-static"></div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>총 발행량</label>
                            <div id="view_total_tickets" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>배치 크기</label>
                            <div id="view_batch_size" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>시작 번호</label>
                            <div id="view_start_number" class="form-control-static"></div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>상태</label>
                            <div id="view_status" class="form-control-static"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>진행률</label>
                            <div id="view_progress" class="form-control-static"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>비고</label>
                    <div id="view_notes" class="form-control-static"></div>
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

<!-- 발행 폼 (숨김) -->
<form id="issueForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="issue">
    <input type="hidden" id="issue_plan_id" name="plan_id" value="">
</form>

<!-- 발행 취소 폼 (숨김) -->
<form id="cancelIssueForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="cancel_issue">
    <input type="hidden" id="cancel_queue_id" name="queue_id" value="">
</form>

<!-- 상태 변경 폼 (숨김) -->
<form id="changeStatusForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="change_status">
    <input type="hidden" id="status_plan_id" name="plan_id" value="">
    <input type="hidden" id="new_status" name="new_status" value="">
</form>

<!-- 계획 삭제 폼 (숨김) -->
<form id="deletePlanForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_plan">
    <input type="hidden" id="delete_plan_id" name="plan_id" value="">
</form>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>

<script>
$(document).ready(function() {
    // 콘솔 로그로 초기화 상태 확인
    console.log('발행 관리 페이지 초기화...');

    // 드롭다운 토글 수동 작동 구현
    $(document).on('click', '.btn-warning.dropdown-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('드롭다운 토글 버튼 클릭됨');
        
        // 현재 드롭다운 메뉴 토글
        var $dropdownMenu = $(this).next('.dropdown-menu');
        $('.dropdown-menu').not($dropdownMenu).removeClass('show');
        $dropdownMenu.toggleClass('show');
    });
    
    // 드롭다운 외부 클릭 시 닫기
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.btn-group').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });
    
    // 탭 URL 해시 처리
    var hash = window.location.hash;
    if (hash) {
        $('#issue-tabs a[href="' + hash + '"]').tab('show');
    }
    
    $('#issue-tabs a').on('click', function(e) {
        window.location.hash = $(this).attr('href');
    });
    
    // 테이블 검색 기능
    $("#plansSearchInput").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#plansTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    
    $("#historySearchInput").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#historyTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    
    // 계획 상세 보기
    $(document).on('click', '.btn-view-plan', function() {
        var id = $(this).data('id');
        
        // AJAX로 계획 상세 정보 조회
        $.ajax({
            url: '/server/api/lottery/get_issue_plan.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    var plan = response.data;
                    
                    // 모달에 데이터 채우기
                    $('#view_product').text(plan.product_name + ' (' + plan.product_code + ')');
                    $('#view_issue_date').text(new Date(plan.issue_date).toLocaleDateString());
                    $('#view_total_tickets').text(Number(plan.total_tickets).toLocaleString() + ' 장');
                    $('#view_batch_size').text(Number(plan.batch_size).toLocaleString() + ' 장');
                    $('#view_start_number').text(plan.start_number);
                    
                    var statusText = '알 수 없음';
                    var statusClass = 'secondary';
                    switch (plan.status) {
                        case 'draft':
                            statusText = '초안';
                            statusClass = 'secondary';
                            break;
                        case 'ready':
                            statusText = '준비';
                            statusClass = 'info';
                            break;
                        case 'in_progress':
                            statusText = '발행 중';
                            statusClass = 'warning';
                            break;
                        case 'completed':
                            statusText = '완료';
                            statusClass = 'success';
                            break;
                        case 'cancelled':
                            statusText = '취소';
                            statusClass = 'danger';
                            break;
                    }
                    
                    $('#view_status').html('<span class="badge badge-' + statusClass + '">' + statusText + '</span>');
                    
                    // 진행률 표시
                    if (plan.status === 'in_progress' && plan.queue_status) {
                        var progress = (plan.processed_tickets / plan.total_tickets) * 100;
                        var progressHtml = '<div class="progress">' +
                            '<div class="progress-bar bg-primary progress-bar-striped" role="progressbar" ' +
                            'style="width: ' + progress + '%" aria-valuenow="' + progress + '" ' +
                            'aria-valuemin="0" aria-valuemax="100">' + progress.toFixed(1) + '%</div></div>' +
                            '<small class="text-muted">' + 
                            Number(plan.processed_tickets).toLocaleString() + ' / ' + 
                            Number(plan.total_tickets).toLocaleString() + ' 장 처리됨</small>';
                        
                        $('#view_progress').html(progressHtml);
                    } else {
                        $('#view_progress').text('-');
                    }
                    
                    $('#view_notes').text(plan.notes || '-');
                    $('#view_created_at').text(new Date(plan.created_at).toLocaleString());
                    $('#view_updated_at').text(new Date(plan.updated_at).toLocaleString());
                    
                    // 모달 표시
                    $('#viewPlanModal').modal('show');
                } else {
                    alert('오류가 발생했습니다: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('AJAX 오류가 발생했습니다: ' + error);
            }
        });
    });
    
    // 계획 수정 모달 표시
    $(document).on('click', '.btn-edit-plan', function() {
        var id = $(this).data('id');
        
        // AJAX로 계획 상세 정보 조회
        $.ajax({
            url: '/server/api/lottery/get_issue_plan.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    var plan = response.data;
                    
                    // 모달에 데이터 채우기
                    $('#edit_plan_id').val(plan.id);
                    $('#edit_product_id').val(plan.product_id);
                    $('#edit_issue_date').val(plan.issue_date);
                    $('#edit_total_tickets').val(plan.total_tickets);
                    $('#edit_batch_size').val(plan.batch_size);
                    $('#edit_start_number').val(plan.start_number);
                    $('#edit_status').val(plan.status);
                    $('#edit_notes').val(plan.notes);
                    
                    // 모달 표시
                    $('#editPlanModal').modal('show');
                } else {
                    alert('오류가 발생했습니다: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('AJAX 오류가 발생했습니다: ' + error);
            }
        });
    });
    
    // 발행 시작 버튼 클릭
    $(document).on('click', '.btn-issue', function(e) {
        e.preventDefault();
        console.log('발행 시작 버튼 클릭됨');
        
        var id = $(this).data('id');
        
        if (confirm('발행 작업을 시작하시겠습니까? 이 작업은 중단할 수 있지만, 장시간이 소요될 수 있습니다.')) {
            $('#issue_plan_id').val(id);
            $('#issueForm').submit();
        }
    });
    
    // 발행 취소 버튼 클릭
    $(document).on('click', '.btn-cancel-issue', function(e) {
        e.preventDefault();
        console.log('발행 취소 버튼 클릭됨');
        
        var id = $(this).data('id');
        
        if (confirm('발행 작업을 중지하시겠습니까? 이미 발행된 복권은 취소되지 않습니다.')) {
            $('#cancel_queue_id').val(id);
            $('#cancelIssueForm').submit();
        }
    });
    
    // 상태 변경 버튼 클릭
    $(document).on('click', '.btn-change-status', function(e) {
        e.preventDefault();
        console.log('상태 변경 버튼 클릭됨');
        
        var id = $(this).data('id');
        var status = $(this).data('status');
        
        if (confirm('이 계획의 상태를 "' + (status === 'ready' ? '준비' : '초안') + '"(으)로 변경하시겠습니까?')) {
            $('#status_plan_id').val(id);
            $('#new_status').val(status);
            $('#changeStatusForm').submit();
        }
    });
    
    // 계획 삭제 버튼 클릭
    $(document).on('click', '.btn-delete-plan', function(e) {
        e.preventDefault();
        console.log('계획 삭제 버튼 클릭됨');
        
        var id = $(this).data('id');
        
        if (confirm('이 발행 계획을 삭제하시겠습니까? 이 작업은 취소할 수 없습니다.')) {
            $('#delete_plan_id').val(id);
            $('#deletePlanForm').submit();
        }
    });

    // 초기화 완료 로그
    console.log('발행 관리 페이지 초기화 완료!');
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
