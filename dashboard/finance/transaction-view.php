<?php
/**
 * 재무 관리 - 거래 상세 정보 페이지
 * 
 * 이 페이지는 특정 재무 거래의 상세 정보를 표시합니다.
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = get_db_connection();

// 거래 ID 확인
$transactionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($transactionId <= 0) {
    // 잘못된 ID인 경우 목록 페이지로 리다이렉트
    header('Location: transactions.php');
    exit;
}

// 거래 정보 조회
$sql = "SELECT ft.*, fc.category_name, u1.username as created_by_name, u2.username as approved_by_name
        FROM financial_transactions ft
        LEFT JOIN financial_categories fc ON ft.category_id = fc.id
        LEFT JOIN users u1 ON ft.created_by = u1.id
        LEFT JOIN users u2 ON ft.approved_by = u2.id
        WHERE ft.id = ?";
$stmt = $db->prepare($sql);
$stmt->bindParam(1, $transactionId, PDO::PARAM_INT);
$stmt->execute();
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

// 거래 정보가 없는 경우 (실제 데이터베이스 연결이 없을 경우를 위한 Mock 데이터)
if (!$transaction) {
    // 거래 유형 및 상태 옵션
    $transactionTypes = ['income' => '수입', 'expense' => '지출', 'transfer' => '이체', 'adjustment' => '조정'];
    $transactionStatuses = [
        'pending' => '처리 중', 
        'completed' => '완료됨', 
        'failed' => '실패', 
        'cancelled' => '취소됨', 
        'reconciled' => '대사완료'
    ];
    
    $type = array_rand($transactionTypes);
    $stat = array_rand($transactionStatuses);
    $date = date('Y-m-d H:i:s', strtotime("-3 days"));
    
    $transaction = [
        'id' => $transactionId,
        'transaction_code' => 'TR' . date('Ymd') . str_pad($transactionId, 4, '0', STR_PAD_LEFT),
        'transaction_type' => $type,
        'amount' => rand(1000, 1000000) / 100,
        'currency' => 'NPR',
        'transaction_date' => $date,
        'description' => $type == 'income' ? '판매 수입' : ($type == 'expense' ? '운영 비용' : ($type == 'transfer' ? '계좌 이체' : '잔액 조정')),
        'category_name' => $type == 'income' ? '판매 수입' : ($type == 'expense' ? '운영 비용' : '기타'),
        'reference_type' => $type == 'income' ? 'sale' : ($type == 'expense' ? 'expense' : ''),
        'reference_id' => $type == 'income' ? 'S'.rand(10000, 99999) : ($type == 'expense' ? 'E'.rand(10000, 99999) : ''),
        'payment_method' => ['cash', 'bank_transfer', 'check'][rand(0, 2)],
        'payment_details' => $type == 'income' ? '판매점 #'.rand(100, 999).' 수입' : ($type == 'expense' ? '운영 비용 #'.rand(100, 999) : ''),
        'status' => $stat,
        'created_by' => 1,
        'created_by_name' => '관리자',
        'approved_by' => $stat == 'completed' ? 2 : null,
        'approved_by_name' => $stat == 'completed' ? '승인자' : null,
        'notes' => '테스트 거래 내역입니다.',
        'created_at' => $date,
        'updated_at' => $date
    ];
}

// 현재 페이지 정보
$pageTitle = "거래 상세 정보";
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
                <h1 class="m-0"><?php echo $pageTitle; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/index.php">홈</a></li>
                    <li class="breadcrumb-item">재무 관리</li>
                    <li class="breadcrumb-item"><a href="transactions.php">거래 목록</a></li>
                    <li class="breadcrumb-item active">거래 상세 정보</li>
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
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-check"></i> 성공!</h5>
            거래 정보가 성공적으로 업데이트되었습니다.
        </div>
        <?php endif; ?>
        
        <!-- 작업 버튼 -->
        <div class="mb-3">
            <a href="transactions.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 목록으로 돌아가기
            </a>
            <a href="transaction-edit.php?id=<?php echo $transaction['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> 수정
            </a>
            <?php if ($transaction['status'] == 'pending'): ?>
            <a href="transaction-approve.php?id=<?php echo $transaction['id']; ?>&action=approve" class="btn btn-success" onclick="return confirm('이 거래를 승인하시겠습니까?');">
                <i class="fas fa-check"></i> 승인
            </a>
            <a href="transaction-approve.php?id=<?php echo $transaction['id']; ?>&action=reject" class="btn btn-danger" onclick="return confirm('이 거래를 거부하시겠습니까?');">
                <i class="fas fa-times"></i> 거부
            </a>
            <?php endif; ?>
            
            <?php if (in_array($transaction['status'], ['pending', 'failed'])): ?>
            <a href="transaction-delete.php?id=<?php echo $transaction['id']; ?>" class="btn btn-danger float-right" onclick="return confirm('이 거래를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.');">
                <i class="fas fa-trash"></i> 삭제
            </a>
            <?php endif; ?>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <!-- 거래 기본 정보 카드 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">거래 기본 정보</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-5">거래 코드:</dt>
                                    <dd class="col-sm-7"><?php echo htmlspecialchars($transaction['transaction_code']); ?></dd>
                                    
                                    <dt class="col-sm-5">거래 유형:</dt>
                                    <dd class="col-sm-7">
                                        <?php 
                                        $transactionTypes = ['income' => '수입', 'expense' => '지출', 'transfer' => '이체', 'adjustment' => '조정'];
                                        $typeLabel = $transactionTypes[$transaction['transaction_type']] ?? '알 수 없음';
                                        $typeClass = '';
                                        
                                        switch($transaction['transaction_type']) {
                                            case 'income':
                                                $typeClass = 'badge bg-success';
                                                break;
                                            case 'expense':
                                                $typeClass = 'badge bg-danger';
                                                break;
                                            case 'transfer':
                                                $typeClass = 'badge bg-info';
                                                break;
                                            case 'adjustment':
                                                $typeClass = 'badge bg-warning';
                                                break;
                                            default:
                                                $typeClass = 'badge bg-secondary';
                                        }
                                        ?>
                                        <span class="<?php echo $typeClass; ?>"><?php echo $typeLabel; ?></span>
                                    </dd>
                                    
                                    <dt class="col-sm-5">금액:</dt>
                                    <dd class="col-sm-7">
                                        <?php 
                                        $amount = number_format($transaction['amount'], 2);
                                        $amountClass = $transaction['transaction_type'] == 'income' ? 'text-success' : ($transaction['transaction_type'] == 'expense' ? 'text-danger' : '');
                                        ?>
                                        <span class="<?php echo $amountClass; ?> font-weight-bold"><?php echo $amount; ?> <?php echo htmlspecialchars($transaction['currency']); ?></span>
                                    </dd>
                                    
                                    <dt class="col-sm-5">거래일:</dt>
                                    <dd class="col-sm-7"><?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?></dd>
                                    
                                    <dt class="col-sm-5">카테고리:</dt>
                                    <dd class="col-sm-7"><?php echo htmlspecialchars($transaction['category_name'] ?? '분류 없음'); ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-5">결제 방법:</dt>
                                    <dd class="col-sm-7">
                                        <?php
                                        $paymentMethods = [
                                            'cash' => '현금',
                                            'bank_transfer' => '계좌 이체',
                                            'check' => '수표',
                                            'credit_card' => '신용카드',
                                            'debit_card' => '직불카드',
                                            'mobile_payment' => '모바일 결제',
                                            'other' => '기타'
                                        ];
                                        echo $paymentMethods[$transaction['payment_method']] ?? '알 수 없음';
                                        ?>
                                    </dd>
                                    
                                    <dt class="col-sm-5">상태:</dt>
                                    <dd class="col-sm-7">
                                        <?php
                                        $transactionStatuses = [
                                            'pending' => '처리 중', 
                                            'completed' => '완료됨', 
                                            'failed' => '실패', 
                                            'cancelled' => '취소됨', 
                                            'reconciled' => '대사완료'
                                        ];
                                        $statusLabel = $transactionStatuses[$transaction['status']] ?? '알 수 없음';
                                        $statusClass = '';
                                        
                                        switch($transaction['status']) {
                                            case 'pending':
                                                $statusClass = 'badge bg-warning';
                                                break;
                                            case 'completed':
                                                $statusClass = 'badge bg-success';
                                                break;
                                            case 'failed':
                                                $statusClass = 'badge bg-danger';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'badge bg-secondary';
                                                break;
                                            case 'reconciled':
                                                $statusClass = 'badge bg-info';
                                                break;
                                            default:
                                                $statusClass = 'badge bg-secondary';
                                        }
                                        ?>
                                        <span class="<?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                                    </dd>
                                    
                                    <dt class="col-sm-5">참조 유형:</dt>
                                    <dd class="col-sm-7"><?php echo htmlspecialchars($transaction['reference_type'] ?? '없음'); ?></dd>
                                    
                                    <dt class="col-sm-5">참조 ID:</dt>
                                    <dd class="col-sm-7"><?php echo htmlspecialchars($transaction['reference_id'] ?? '없음'); ?></dd>
                                    
                                    <dt class="col-sm-5">생성일:</dt>
                                    <dd class="col-sm-7"><?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?></dd>
                                </dl>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <dl class="row">
                                    <dt class="col-sm-3">설명:</dt>
                                    <dd class="col-sm-9"><?php echo nl2br(htmlspecialchars($transaction['description'] ?? '')); ?></dd>
                                    
                                    <dt class="col-sm-3">결제 상세 정보:</dt>
                                    <dd class="col-sm-9"><?php echo nl2br(htmlspecialchars($transaction['payment_details'] ?? '')); ?></dd>
                                    
                                    <dt class="col-sm-3">비고:</dt>
                                    <dd class="col-sm-9"><?php echo nl2br(htmlspecialchars($transaction['notes'] ?? '')); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- 거래 이력 카드 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">거래 이력</h3>
                    </div>
                    <div class="card-body">
                        <ul class="timeline">
                            <li class="time-label">
                                <span class="bg-success">
                                    <?php echo date('Y-m-d', strtotime($transaction['created_at'])); ?>
                                </span>
                            </li>
                            <li>
                                <i class="fas fa-plus bg-primary"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($transaction['created_at'])); ?></span>
                                    <h3 class="timeline-header">거래 생성</h3>
                                    <div class="timeline-body">
                                        <?php echo htmlspecialchars($transaction['created_by_name'] ?? '사용자'); ?>님이 거래를 생성했습니다.
                                    </div>
                                </div>
                            </li>
                            
                            <?php if ($transaction['status'] == 'completed' && $transaction['approved_by']): ?>
                            <li>
                                <i class="fas fa-check bg-success"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($transaction['updated_at'])); ?></span>
                                    <h3 class="timeline-header">거래 승인</h3>
                                    <div class="timeline-body">
                                        <?php echo htmlspecialchars($transaction['approved_by_name'] ?? '승인자'); ?>님이 거래를 승인했습니다.
                                    </div>
                                </div>
                            </li>
                            <?php elseif ($transaction['status'] == 'failed'): ?>
                            <li>
                                <i class="fas fa-times bg-danger"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($transaction['updated_at'])); ?></span>
                                    <h3 class="timeline-header">거래 실패</h3>
                                    <div class="timeline-body">
                                        거래가 실패했습니다.
                                    </div>
                                </div>
                            </li>
                            <?php elseif ($transaction['status'] == 'cancelled'): ?>
                            <li>
                                <i class="fas fa-ban bg-secondary"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($transaction['updated_at'])); ?></span>
                                    <h3 class="timeline-header">거래 취소</h3>
                                    <div class="timeline-body">
                                        거래가 취소되었습니다.
                                    </div>
                                </div>
                            </li>
                            <?php elseif ($transaction['status'] == 'reconciled'): ?>
                            <li>
                                <i class="fas fa-sync bg-info"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($transaction['updated_at'])); ?></span>
                                    <h3 class="timeline-header">거래 대사 완료</h3>
                                    <div class="timeline-body">
                                        거래 대사 처리가 완료되었습니다.
                                    </div>
                                </div>
                            </li>
                            <?php endif; ?>
                            <li><i class="fas fa-clock bg-gray"></i></li>
                        </ul>
                    </div>
                </div>
                
                <!-- 관련 정보 카드 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">관련 정보</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($transaction['reference_type'] == 'sale'): ?>
                        <a href="<?php echo SERVER_URL; ?>/dashboard/sales/sale-details.php?id=<?php echo htmlspecialchars($transaction['reference_id']); ?>" class="btn btn-block btn-outline-primary">
                            <i class="fas fa-shopping-cart"></i> 관련 판매 내역 보기
                        </a>
                        <?php elseif ($transaction['reference_type'] == 'expense'): ?>
                        <a href="<?php echo SERVER_URL; ?>/dashboard/finance/expense-details.php?id=<?php echo htmlspecialchars($transaction['reference_id']); ?>" class="btn btn-block btn-outline-danger">
                            <i class="fas fa-file-invoice"></i> 관련 비용 내역 보기
                        </a>
                        <?php else: ?>
                        <div class="text-muted">
                            관련 참조 정보가 없습니다.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>