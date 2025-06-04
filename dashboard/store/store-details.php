<?php
/**
 * Store Details Page
 * 
 * This page displays detailed information about a lottery retail store.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Check if user has access to this page
checkPageAccess('store_management');

// Get store ID from URL parameter
$storeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($storeId <= 0) {
    // Redirect to store list if no valid ID provided
    header('Location: store-list.php');
    exit;
}

// Database connection
$db = getDbConnection();

// Get store information
$stmt = $db->prepare("SELECT * FROM stores WHERE id = ?");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Store not found, redirect to list
    header('Location: store-list.php');
    exit;
}

$store = $result->fetch_assoc();
$stmt->close();

// Get active contract information
$contractStmt = $db->prepare("
    SELECT * FROM store_contracts
    WHERE store_id = ? AND status = 'active'
    ORDER BY end_date DESC
    LIMIT 1
");
$contractStmt->bind_param("i", $storeId);
$contractStmt->execute();
$contractResult = $contractStmt->get_result();
$activeContract = $contractResult->num_rows > 0 ? $contractResult->fetch_assoc() : null;
$contractStmt->close();

// Get equipment count
$equipmentStmt = $db->prepare("
    SELECT 
        COUNT(*) as total_count,
        SUM(CASE WHEN status = 'operational' THEN 1 ELSE 0 END) as operational_count,
        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_count,
        SUM(CASE WHEN status = 'faulty' THEN 1 ELSE 0 END) as faulty_count
    FROM store_equipment
    WHERE store_id = ?
");
$equipmentStmt->bind_param("i", $storeId);
$equipmentStmt->execute();
$equipmentResult = $equipmentStmt->get_result();
$equipmentStats = $equipmentResult->fetch_assoc();
$equipmentStmt->close();

// Get latest performance data
$performanceStmt = $db->prepare("
    SELECT * FROM store_performance
    WHERE store_id = ?
    ORDER BY reporting_period DESC
    LIMIT 3
");
$performanceStmt->bind_param("i", $storeId);
$performanceStmt->execute();
$performanceResult = $performanceStmt->get_result();
$performanceData = [];
while ($row = $performanceResult->fetch_assoc()) {
    $performanceData[] = $row;
}
$performanceStmt->close();

// Get upcoming training sessions
$trainingStmt = $db->prepare("
    SELECT st.*, tp.program_name
    FROM store_training st
    JOIN training_programs tp ON st.training_program_id = tp.id
    WHERE st.store_id = ? AND st.status IN ('scheduled', 'postponed')
    ORDER BY st.scheduled_date ASC
    LIMIT 3
");
$trainingStmt->bind_param("i", $storeId);
$trainingStmt->execute();
$trainingResult = $trainingStmt->get_result();
$upcomingTrainings = [];
while ($row = $trainingResult->fetch_assoc()) {
    $upcomingTrainings[] = $row;
}
$trainingStmt->close();

// Page title and metadata
$pageTitle = "판매점 상세 정보: " . htmlspecialchars($store['store_name']);
$pageDescription = "판매점의 상세 정보, 계약, 장비, 성과 및 교육 데이터";
$activeMenu = "store";
$activeSubMenu = "store-list";

// Include header template
include '../../templates/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="page-title-div">
                    <h2 class="title"><?php echo htmlspecialchars($store['store_name']); ?></h2>
                    <p class="sub-title">판매점 코드: <?php echo htmlspecialchars($store['store_code']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Breadcrumb -->
        <div class="row">
            <div class="col-md-12">
                <ol class="breadcrumb">
                    <li><a href="../../dashboard/"><i class="fa fa-dashboard"></i> 대시보드</a></li>
                    <li><a href="store-list.php">판매점 관리</a></li>
                    <li class="active"><?php echo htmlspecialchars($store['store_name']); ?></li>
                </ol>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="row">
            <div class="col-md-12 mb-3">
                <div class="btn-group">
                    <a href="store-edit.php?id=<?php echo $storeId; ?>" class="btn btn-primary">
                        <i class="fa fa-edit"></i> 정보 수정
                    </a>
                    <a href="store-contracts.php?store_id=<?php echo $storeId; ?>" class="btn btn-warning">
                        <i class="fa fa-file-text"></i> 계약 관리
                    </a>
                    <a href="store-equipment.php?store_id=<?php echo $storeId; ?>" class="btn btn-success">
                        <i class="fa fa-desktop"></i> 장비 관리
                    </a>
                    <a href="store-performance.php?store_id=<?php echo $storeId; ?>" class="btn btn-info">
                        <i class="fa fa-line-chart"></i> 성과 관리
                    </a>
                    <a href="training-history.php?store_id=<?php echo $storeId; ?>" class="btn btn-default">
                        <i class="fa fa-graduation-cap"></i> 교육 관리
                    </a>
                    <?php if ($store['status'] === 'active'): ?>
                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deactivateModal">
                        <i class="fa fa-ban"></i> 비활성화
                    </button>
                    <?php elseif ($store['status'] === 'inactive'): ?>
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#activateModal">
                        <i class="fa fa-check"></i> 활성화
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Store Information -->
        <div class="row">
            <div class="col-md-8">
                <!-- Basic Information -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">기본 정보</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="dl-horizontal">
                                    <dt>판매점명:</dt>
                                    <dd><?php echo htmlspecialchars($store['store_name']); ?></dd>
                                    
                                    <dt>판매점 코드:</dt>
                                    <dd><?php echo htmlspecialchars($store['store_code']); ?></dd>
                                    
                                    <dt>대표자명:</dt>
                                    <dd><?php echo htmlspecialchars($store['owner_name']); ?></dd>
                                    
                                    <dt>이메일:</dt>
                                    <dd>
                                        <?php if (!empty($store['email'])): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($store['email']); ?>">
                                            <?php echo htmlspecialchars($store['email']); ?>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted">없음</span>
                                        <?php endif; ?>
                                    </dd>
                                    
                                    <dt>전화번호:</dt>
                                    <dd>
                                        <?php if (!empty($store['phone'])): ?>
                                        <a href="tel:<?php echo htmlspecialchars($store['phone']); ?>">
                                            <?php echo htmlspecialchars($store['phone']); ?>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted">없음</span>
                                        <?php endif; ?>
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="dl-horizontal">
                                    <dt>상태:</dt>
                                    <dd>
                                        <?php 
                                        $statusLabels = [
                                            'active' => '<span class="label label-success">활성</span>',
                                            'inactive' => '<span class="label label-warning">비활성</span>',
                                            'pending' => '<span class="label label-info">대기중</span>',
                                            'terminated' => '<span class="label label-danger">계약해지</span>'
                                        ];
                                        echo isset($statusLabels[$store['status']]) 
                                            ? $statusLabels[$store['status']] 
                                            : htmlspecialchars($store['status']);
                                        ?>
                                    </dd>
                                    
                                    <dt>카테고리:</dt>
                                    <dd>
                                        <?php 
                                        $categoryLabels = [
                                            'standard' => '<span class="label label-default">일반</span>',
                                            'premium' => '<span class="label label-primary">프리미엄</span>',
                                            'exclusive' => '<span class="label label-success">전용</span>'
                                        ];
                                        echo isset($categoryLabels[$store['store_category']]) 
                                            ? $categoryLabels[$store['store_category']] 
                                            : htmlspecialchars($store['store_category']);
                                        ?>
                                    </dd>
                                    
                                    <dt>규모:</dt>
                                    <dd>
                                        <?php 
                                        $sizeLabels = [
                                            'small' => '소형',
                                            'medium' => '중형',
                                            'large' => '대형'
                                        ];
                                        echo isset($sizeLabels[$store['store_size']]) 
                                            ? $sizeLabels[$store['store_size']] 
                                            : htmlspecialchars($store['store_size']);
                                        ?>
                                    </dd>
                                    
                                    <dt>등록일:</dt>
                                    <dd><?php echo date('Y-m-d', strtotime($store['registration_date'])); ?></dd>
                                    
                                    <dt>마지막 업데이트:</dt>
                                    <dd><?php echo date('Y-m-d H:i', strtotime($store['updated_at'])); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Address Information -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">위치 정보</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="dl-horizontal">
                                    <dt>주소:</dt>
                                    <dd><?php echo htmlspecialchars($store['address']); ?></dd>
                                    
                                    <dt>도시:</dt>
                                    <dd><?php echo htmlspecialchars($store['city']); ?></dd>
                                    
                                    <dt>주/도:</dt>
                                    <dd>
                                        <?php echo !empty($store['state']) 
                                            ? htmlspecialchars($store['state']) 
                                            : '<span class="text-muted">없음</span>'; 
                                        ?>
                                    </dd>
                                    
                                    <dt>우편번호:</dt>
                                    <dd>
                                        <?php echo !empty($store['postal_code']) 
                                            ? htmlspecialchars($store['postal_code']) 
                                            : '<span class="text-muted">없음</span>'; 
                                        ?>
                                    </dd>
                                    
                                    <dt>국가:</dt>
                                    <dd><?php echo htmlspecialchars($store['country']); ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <?php if (!empty($store['gps_latitude']) && !empty($store['gps_longitude'])): ?>
                                <dl class="dl-horizontal">
                                    <dt>GPS 좌표:</dt>
                                    <dd>
                                        위도: <?php echo $store['gps_latitude']; ?><br>
                                        경도: <?php echo $store['gps_longitude']; ?>
                                    </dd>
                                </dl>
                                <div class="text-center">
                                    <a href="https://maps.google.com/?q=<?php echo $store['gps_latitude']; ?>,<?php echo $store['gps_longitude']; ?>" 
                                        target="_blank" class="btn btn-sm btn-info">
                                        <i class="fa fa-map-marker"></i> 지도에서 보기
                                    </a>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fa fa-exclamation-triangle"></i> GPS 좌표 정보가 없습니다.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Business Information -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">사업자 정보</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="dl-horizontal">
                                    <dt>사업자 등록번호:</dt>
                                    <dd>
                                        <?php echo !empty($store['business_license_number']) 
                                            ? htmlspecialchars($store['business_license_number']) 
                                            : '<span class="text-muted">없음</span>'; 
                                        ?>
                                    </dd>
                                    
                                    <dt>세금 ID:</dt>
                                    <dd>
                                        <?php echo !empty($store['tax_id']) 
                                            ? htmlspecialchars($store['tax_id']) 
                                            : '<span class="text-muted">없음</span>'; 
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="dl-horizontal">
                                    <dt>은행명:</dt>
                                    <dd>
                                        <?php echo !empty($store['bank_name']) 
                                            ? htmlspecialchars($store['bank_name']) 
                                            : '<span class="text-muted">없음</span>'; 
                                        ?>
                                    </dd>
                                    
                                    <dt>계좌번호:</dt>
                                    <dd>
                                        <?php echo !empty($store['bank_account_number']) 
                                            ? htmlspecialchars($store['bank_account_number']) 
                                            : '<span class="text-muted">없음</span>'; 
                                        ?>
                                    </dd>
                                    
                                    <dt>은행 지점 코드:</dt>
                                    <dd>
                                        <?php echo !empty($store['bank_ifsc_code']) 
                                            ? htmlspecialchars($store['bank_ifsc_code']) 
                                            : '<span class="text-muted">없음</span>'; 
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Notes -->
                <?php if (!empty($store['notes'])): ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">비고</h3>
                    </div>
                    <div class="panel-body">
                        <p><?php echo nl2br(htmlspecialchars($store['notes'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <!-- Current Contract -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">현재 계약 정보</h3>
                    </div>
                    <div class="panel-body">
                        <?php if ($activeContract): ?>
                        <dl>
                            <dt>계약 번호:</dt>
                            <dd><?php echo htmlspecialchars($activeContract['contract_number']); ?></dd>
                            
                            <dt>계약 유형:</dt>
                            <dd>
                                <?php 
                                $contractTypeLabels = [
                                    'standard' => '표준',
                                    'premium' => '프리미엄',
                                    'seasonal' => '시즌',
                                    'temporary' => '임시',
                                    'custom' => '맞춤'
                                ];
                                echo isset($contractTypeLabels[$activeContract['contract_type']]) 
                                    ? $contractTypeLabels[$activeContract['contract_type']] 
                                    : htmlspecialchars($activeContract['contract_type']);
                                ?>
                            </dd>
                            
                            <dt>계약 기간:</dt>
                            <dd>
                                <?php echo date('Y-m-d', strtotime($activeContract['start_date'])); ?> ~ 
                                <?php echo date('Y-m-d', strtotime($activeContract['end_date'])); ?>
                                <br>
                                <small class="text-muted">
                                    계약 만료까지 
                                    <?php 
                                    $daysLeft = (strtotime($activeContract['end_date']) - time()) / (60 * 60 * 24);
                                    echo round($daysLeft);
                                    ?> 일 남음
                                </small>
                            </dd>
                            
                            <dt>수수료율:</dt>
                            <dd><?php echo $activeContract['commission_rate']; ?>%</dd>
                            
                            <dt>판매 목표액:</dt>
                            <dd>
                                <?php echo !empty($activeContract['sales_target']) 
                                    ? number_format($activeContract['sales_target'], 2) 
                                    : '<span class="text-muted">설정 안됨</span>'; 
                                ?>
                            </dd>
                        </dl>
                        <a href="contract-details.php?id=<?php echo $activeContract['id']; ?>" class="btn btn-sm btn-warning">
                            <i class="fa fa-file-text"></i> 계약 상세 보기
                        </a>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle"></i> 현재 활성화된 계약이 없습니다.
                        </div>
                        <a href="contract-add.php?store_id=<?php echo $storeId; ?>" class="btn btn-sm btn-success">
                            <i class="fa fa-plus"></i> 새 계약 추가
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Equipment Summary -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">장비 요약</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row text-center">
                            <div class="col-xs-6">
                                <div class="panel panel-primary">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">총 장비</h4>
                                    </div>
                                    <div class="panel-body">
                                        <h1><?php echo $equipmentStats['total_count']; ?></h1>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="panel panel-success">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">정상 작동</h4>
                                    </div>
                                    <div class="panel-body">
                                        <h1><?php echo $equipmentStats['operational_count']; ?></h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row text-center">
                            <div class="col-xs-6">
                                <div class="panel panel-warning">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">유지 보수 중</h4>
                                    </div>
                                    <div class="panel-body">
                                        <h1><?php echo $equipmentStats['maintenance_count']; ?></h1>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="panel panel-danger">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">고장</h4>
                                    </div>
                                    <div class="panel-body">
                                        <h1><?php echo $equipmentStats['faulty_count']; ?></h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="store-equipment.php?store_id=<?php echo $storeId; ?>" class="btn btn-sm btn-success btn-block">
                            <i class="fa fa-desktop"></i> 장비 관리
                        </a>
                    </div>
                </div>
                
                <!-- Recent Performance -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">최근 성과</h3>
                    </div>
                    <div class="panel-body">
                        <?php if (!empty($performanceData)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>기간</th>
                                        <th>판매액</th>
                                        <th>판매건수</th>
                                        <th>목표 달성률</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($performanceData as $performance): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($performance['reporting_period']); ?></td>
                                        <td><?php echo number_format($performance['sales_amount'], 2); ?></td>
                                        <td><?php echo $performance['sales_count']; ?></td>
                                        <td>
                                            <?php 
                                            if ($performance['achievement_rate'] > 0) {
                                                $rateClass = '';
                                                if ($performance['achievement_rate'] >= 100) {
                                                    $rateClass = 'text-success';
                                                } elseif ($performance['achievement_rate'] >= 80) {
                                                    $rateClass = 'text-primary';
                                                } elseif ($performance['achievement_rate'] >= 50) {
                                                    $rateClass = 'text-warning';
                                                } else {
                                                    $rateClass = 'text-danger';
                                                }
                                                echo '<span class="' . $rateClass . '">' . $performance['achievement_rate'] . '%</span>';
                                            } else {
                                                echo '<span class="text-muted">N/A</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="store-performance.php?store_id=<?php echo $storeId; ?>" class="btn btn-sm btn-info btn-block">
                            <i class="fa fa-line-chart"></i> 성과 관리
                        </a>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> 성과 데이터가 없습니다.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Upcoming Training -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">예정된 교육</h3>
                    </div>
                    <div class="panel-body">
                        <?php if (!empty($upcomingTrainings)): ?>
                        <div class="list-group">
                            <?php foreach ($upcomingTrainings as $training): ?>
                            <a href="training-details.php?id=<?php echo $training['id']; ?>" class="list-group-item">
                                <h4 class="list-group-item-heading">
                                    <?php echo htmlspecialchars($training['program_name']); ?>
                                    <?php if ($training['status'] === 'postponed'): ?>
                                    <span class="label label-warning">연기됨</span>
                                    <?php endif; ?>
                                </h4>
                                <p class="list-group-item-text">
                                    <i class="fa fa-calendar"></i> 
                                    <?php echo date('Y-m-d', strtotime($training['scheduled_date'])); ?>
                                    <?php if (!empty($training['scheduled_time'])): ?>
                                    <i class="fa fa-clock-o"></i> 
                                    <?php echo date('H:i', strtotime($training['scheduled_time'])); ?>
                                    <?php endif; ?>
                                </p>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <a href="training-history.php?store_id=<?php echo $storeId; ?>" class="btn btn-sm btn-default btn-block">
                            <i class="fa fa-graduation-cap"></i> 교육 이력 보기
                        </a>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> 예정된 교육이 없습니다.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deactivate Store Modal -->
<?php if ($store['status'] === 'active'): ?>
<div class="modal fade" id="deactivateModal" tabindex="-1" role="dialog" aria-labelledby="deactivateModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="deactivateModalLabel">판매점 비활성화 확인</h4>
            </div>
            <div class="modal-body">
                <p>
                    <strong><?php echo htmlspecialchars($store['store_name']); ?></strong> 판매점을 비활성화하시겠습니까?
                </p>
                <p class="text-danger">
                    비활성화 시 판매점은 더 이상 복권을 판매할 수 없게 됩니다.
                </p>
                <form id="deactivateForm" method="post" action="store-status-change.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="store_id" value="<?php echo $storeId; ?>">
                    <input type="hidden" name="action" value="deactivate">
                    
                    <div class="form-group">
                        <label for="reason">비활성화 사유:</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('deactivateForm').submit();">
                    비활성화
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Activate Store Modal -->
<?php if ($store['status'] === 'inactive'): ?>
<div class="modal fade" id="activateModal" tabindex="-1" role="dialog" aria-labelledby="activateModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="activateModalLabel">판매점 활성화 확인</h4>
            </div>
            <div class="modal-body">
                <p>
                    <strong><?php echo htmlspecialchars($store['store_name']); ?></strong> 판매점을 활성화하시겠습니까?
                </p>
                <p class="text-success">
                    활성화 시 판매점은 다시 복권을 판매할 수 있게 됩니다.
                </p>
                <form id="activateForm" method="post" action="store-status-change.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="store_id" value="<?php echo $storeId; ?>">
                    <input type="hidden" name="action" value="activate">
                    
                    <div class="form-group">
                        <label for="notes">비고:</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-success" onclick="document.getElementById('activateForm').submit();">
                    활성화
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Include footer template
include '../../templates/footer.php';
?>
