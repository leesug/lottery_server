<?php
/**
 * 수수료 관리 페이지
 * 
 * 이 페이지는 판매점의 수수료 정책 및 수수료 지급 내역을 관리합니다.
 */

// 필수 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
checkPageAccess('store_management');

// 판매점 ID 가져오기 (URL 파라미터)
$storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;

// 변수 초기화
$message = '';
$messageType = '';
$storeInfo = null;
$commissionHistory = [];
$commissionPolicies = [];
$currentYear = date('Y');
$currentMonth = date('m');
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;
$selectedMonth = isset($_GET['month']) ? sanitizeInput($_GET['month']) : $currentMonth;

// 페이지 모드 설정 (store_id가 있으면 특정 판매점 수수료 관리, 없으면 전체 수수료 정책 관리)
$isSingleStore = ($storeId > 0);

// SIMULATING DATABASE CONNECTION FOR TESTING
// 임시 테스트 데이터 - 실제로는 데이터베이스에서 가져옵니다
if ($isSingleStore) {
    $storeInfo = [
        'id' => $storeId,
        'store_name' => '테스트 판매점',
        'store_code' => 'ST001',
        'status' => 'active',
        'store_category' => 'standard',
        'commission_rate' => 5.0
    ];
    
    // 판매점별 수수료 이력 데이터
    for ($m = 1; $m <= 12; $m++) {
        // 선택한 연도의 이후 월은 생성하지 않음
        if ($selectedYear == $currentYear && $m > $currentMonth) {
            continue;
        }
        
        $month = str_pad($m, 2, '0', STR_PAD_LEFT);
        $periodDate = "$selectedYear-$month";
        
        // 월별 판매액 생성 (랜덤)
        $salesAmount = mt_rand(100000, 500000);
        $commissionRate = 5.0; // 기본 수수료율
        
        // 인센티브 적용 (판매액에 따라)
        $incentiveRate = 0;
        if ($salesAmount >= 400000) {
            $incentiveRate = 1.0;
        } else if ($salesAmount >= 300000) {
            $incentiveRate = 0.5;
        }
        
        $totalRate = $commissionRate + $incentiveRate;
        $baseCommission = $salesAmount * ($commissionRate / 100);
        $incentiveAmount = $salesAmount * ($incentiveRate / 100);
        $totalCommission = $baseCommission + $incentiveAmount;
        
        $commissionHistory[] = [
            'period' => $periodDate,
            'period_label' => $selectedYear . '년 ' . $m . '월',
            'sales_amount' => $salesAmount,
            'commission_rate' => $commissionRate,
            'incentive_rate' => $incentiveRate,
            'total_rate' => $totalRate,
            'base_commission' => $baseCommission,
            'incentive_amount' => $incentiveAmount,
            'total_commission' => $totalCommission,
            'payment_status' => ($selectedYear < $currentYear || ($selectedYear == $currentYear && $m < $currentMonth)) ? 'paid' : 'pending',
            'payment_date' => ($selectedYear < $currentYear || ($selectedYear == $currentYear && $m < $currentMonth)) ? 
                date('Y-m-d', strtotime($periodDate . '-15 +1 month')) : null
        ];
    }
} else {
    // 전체 수수료 정책 데이터
    $commissionPolicies = [
        [
            'id' => 1,
            'category' => 'standard',
            'category_label' => '표준 판매점',
            'base_rate' => 5.0,
            'min_sales' => 0,
            'max_sales' => null,
            'status' => 'active',
            'effective_date' => '2023-01-01',
            'expiry_date' => null
        ],
        [
            'id' => 2,
            'category' => 'premium',
            'category_label' => '프리미엄 판매점',
            'base_rate' => 5.5,
            'min_sales' => 500000,
            'max_sales' => null,
            'status' => 'active',
            'effective_date' => '2023-01-01',
            'expiry_date' => null
        ],
        [
            'id' => 3,
            'category' => 'exclusive',
            'category_label' => '전용 판매점',
            'base_rate' => 6.0,
            'min_sales' => 1000000,
            'max_sales' => null,
            'status' => 'active',
            'effective_date' => '2023-01-01',
            'expiry_date' => null
        ],
        [
            'id' => 4,
            'category' => 'seasonal',
            'category_label' => '계절 판매점',
            'base_rate' => 4.5,
            'min_sales' => 0,
            'max_sales' => 300000,
            'status' => 'active',
            'effective_date' => '2023-01-01',
            'expiry_date' => null
        ]
    ];
}

// 인센티브 정책 데이터
$incentivePolicies = [
    [
        'id' => 1,
        'name' => '판매 목표 달성 인센티브',
        'category' => 'all',
        'condition_type' => 'sales_target',
        'condition_value' => 300000,
        'rate' => 0.5,
        'status' => 'active',
        'effective_date' => '2023-01-01',
        'expiry_date' => null
    ],
    [
        'id' => 2,
        'name' => '최우수 판매 인센티브',
        'category' => 'all',
        'condition_type' => 'sales_target',
        'condition_value' => 400000,
        'rate' => 1.0,
        'status' => 'active',
        'effective_date' => '2023-01-01',
        'expiry_date' => null
    ],
    [
        'id' => 3,
        'name' => '고객 만족도 인센티브',
        'category' => 'all',
        'condition_type' => 'customer_rating',
        'condition_value' => 4.5,
        'rate' => 0.3,
        'status' => 'active',
        'effective_date' => '2023-01-01',
        'expiry_date' => null
    ],
    [
        'id' => 4,
        'name' => '신규 고객 유치 인센티브',
        'category' => 'standard,premium',
        'condition_type' => 'new_customer',
        'condition_value' => 20,
        'rate' => 0.2,
        'status' => 'active',
        'effective_date' => '2023-01-01',
        'expiry_date' => null
    ]
];

// 세션에 메시지가 있는지 확인
if (isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// 페이지 제목 및 메타데이터 설정
if ($isSingleStore) {
    $pageTitle = "수수료 관리: " . htmlspecialchars($storeInfo['store_name']);
    $pageDescription = "판매점의 수수료 정책 및 지급 내역 관리";
} else {
    $pageTitle = "수수료 정책 관리";
    $pageDescription = "판매점 유형별 수수료 정책 및 인센티브 관리";
}

$activeMenu = "store";
$activeSubMenu = $isSingleStore ? "store-list" : "commission-management";

// CSRF 토큰 생성 (테스트용)
$_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true));

// 헤더 템플릿 포함
include 'test-header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="page-title-div">
                    <h2 class="title"><?php echo $pageTitle; ?></h2>
                    <p class="sub-title"><?php echo $pageDescription; ?></p>
                </div>
            </div>
        </div>
        
        <!-- 탐색 경로 -->
        <div class="row">
            <div class="col-md-12">
                <ol class="breadcrumb">
                    <li><a href="../../dashboard/"><i class="fa fa-dashboard"></i> 대시보드</a></li>
                    <li><a href="store-list.php">판매점 관리</a></li>
                    <?php if ($isSingleStore): ?>
                    <li><a href="store-details.php?id=<?php echo $storeId; ?>"><?php echo htmlspecialchars($storeInfo['store_name']); ?></a></li>
                    <li class="active">수수료 관리</li>
                    <?php else: ?>
                    <li class="active">수수료 정책 관리</li>
                    <?php endif; ?>
                </ol>
            </div>
        </div>
        
        <!-- 알림 메시지 -->
        <?php if (!empty($message)): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <?php echo $message; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($isSingleStore): /* 특정 판매점 수수료 관리 */ ?>
        
        <!-- 판매점 정보 카드 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">판매점 정보</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <dl class="dl-horizontal">
                                    <dt>판매점명:</dt>
                                    <dd><?php echo htmlspecialchars($storeInfo['store_name']); ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <dl class="dl-horizontal">
                                    <dt>판매점 코드:</dt>
                                    <dd><?php echo htmlspecialchars($storeInfo['store_code']); ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <dl class="dl-horizontal">
                                    <dt>수수료율:</dt>
                                    <dd><?php echo number_format($storeInfo['commission_rate'], 1); ?>%</dd>
                                </dl>
                            </div>
                        </div>
                        <div class="text-right">
                            <a href="store-details.php?id=<?php echo $storeId; ?>" class="btn btn-info btn-sm">
                                <i class="fa fa-eye"></i> 판매점 상세 정보
                            </a>
                            <a href="store-contracts.php?store_id=<?php echo $storeId; ?>" class="btn btn-primary btn-sm">
                                <i class="fa fa-file-contract"></i> 계약 관리
                            </a>
                            <a href="store-performance.php?store_id=<?php echo $storeId; ?>" class="btn btn-success btn-sm">
                                <i class="fa fa-chart-line"></i> 성과 대시보드
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 필터 및 컨트롤 -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <form method="get" action="commission-management.php" class="form-inline">
                            <input type="hidden" name="store_id" value="<?php echo $storeId; ?>">
                            
                            <div class="form-group mr-3">
                                <label for="year" class="mr-2">연도:</label>
                                <select class="form-control" id="year" name="year">
                                    <?php for ($y = $currentYear - 5; $y <= $currentYear; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($y == $selectedYear) ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>년
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-filter"></i> 필터 적용
                            </button>
                            
                            <div class="pull-right">
                                <a href="#" class="btn btn-success" id="exportBtn">
                                    <i class="fa fa-download"></i> 내보내기 (Excel)
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 수수료 요약 카드 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?php echo $selectedYear; ?>년 수수료 요약</h3>
                    </div>
                    <div class="panel-body">
                        <?php
                        // 연간 합계 계산
                        $totalSales = 0;
                        $totalBaseCommission = 0;
                        $totalIncentive = 0;
                        $totalCommission = 0;
                        
                        foreach ($commissionHistory as $history) {
                            $totalSales += $history['sales_amount'];
                            $totalBaseCommission += $history['base_commission'];
                            $totalIncentive += $history['incentive_amount'];
                            $totalCommission += $history['total_commission'];
                        }
                        
                        // 평균 수수료율 계산
                        $avgCommissionRate = $totalSales > 0 ? ($totalCommission / $totalSales) * 100 : 0;
                        ?>
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="card">
                                    <h4>총 판매액</h4>
                                    <h2 class="text-primary">₩ <?php echo number_format($totalSales); ?></h2>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="card">
                                    <h4>기본 수수료</h4>
                                    <h2 class="text-success">₩ <?php echo number_format($totalBaseCommission); ?></h2>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="card">
                                    <h4>인센티브</h4>
                                    <h2 class="text-info">₩ <?php echo number_format($totalIncentive); ?></h2>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="card">
                                    <h4>총 수수료</h4>
                                    <h2 class="text-danger">₩ <?php echo number_format($totalCommission); ?></h2>
                                    <p>평균 <?php echo number_format($avgCommissionRate, 2); ?>%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 월별 수수료 그래프 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?php echo $selectedYear; ?>년 월별 수수료 추이</h3>
                    </div>
                    <div class="panel-body">
                        <canvas id="monthlyCommissionChart" width="100%" height="50"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 수수료 이력 테이블 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?php echo $selectedYear; ?>년 월별 수수료 내역</h3>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>기간</th>
                                        <th>판매액 (₩)</th>
                                        <th>기본 수수료율</th>
                                        <th>인센티브율</th>
                                        <th>총 수수료율</th>
                                        <th>기본 수수료 (₩)</th>
                                        <th>인센티브 (₩)</th>
                                        <th>총 수수료 (₩)</th>
                                        <th>지급 상태</th>
                                        <th>지급일</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($commissionHistory)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">데이터가 없습니다.</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($commissionHistory as $history): ?>
                                        <tr>
                                            <td><?php echo $history['period_label']; ?></td>
                                            <td class="text-right"><?php echo number_format($history['sales_amount']); ?></td>
                                            <td class="text-center"><?php echo number_format($history['commission_rate'], 1); ?>%</td>
                                            <td class="text-center"><?php echo number_format($history['incentive_rate'], 1); ?>%</td>
                                            <td class="text-center"><?php echo number_format($history['total_rate'], 1); ?>%</td>
                                            <td class="text-right"><?php echo number_format($history['base_commission']); ?></td>
                                            <td class="text-right"><?php echo number_format($history['incentive_amount']); ?></td>
                                            <td class="text-right"><?php echo number_format($history['total_commission']); ?></td>
                                            <td class="text-center">
                                                <?php 
                                                $statusLabels = [
                                                    'paid' => '<span class="label label-success">지급완료</span>',
                                                    'pending' => '<span class="label label-warning">지급예정</span>',
                                                    'processing' => '<span class="label label-info">처리중</span>',
                                                    'cancelled' => '<span class="label label-danger">취소됨</span>'
                                                ];
                                                echo isset($statusLabels[$history['payment_status']]) 
                                                    ? $statusLabels[$history['payment_status']] 
                                                    : htmlspecialchars($history['payment_status']);
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <?php echo $history['payment_date'] ? date('Y-m-d', strtotime($history['payment_date'])) : '-'; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="active">
                                        <th>합계</th>
                                        <th class="text-right"><?php echo number_format($totalSales); ?></th>
                                        <th class="text-center">-</th>
                                        <th class="text-center">-</th>
                                        <th class="text-center"><?php echo number_format($avgCommissionRate, 2); ?>%</th>
                                        <th class="text-right"><?php echo number_format($totalBaseCommission); ?></th>
                                        <th class="text-right"><?php echo number_format($totalIncentive); ?></th>
                                        <th class="text-right"><?php echo number_format($totalCommission); ?></th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 인센티브 정책 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">적용 가능한 인센티브 정책</h3>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>인센티브명</th>
                                        <th>조건</th>
                                        <th>추가 수수료율</th>
                                        <th>상태</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($incentivePolicies)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">인센티브 정책이 없습니다.</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($incentivePolicies as $policy): ?>
                                        <?php 
                                        // 판매점 카테고리에 적용 가능한 인센티브만 표시
                                        $policyCategories = explode(',', $policy['category']);
                                        if ($policy['category'] !== 'all' && !in_array($storeInfo['store_category'], $policyCategories)) {
                                            continue;
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($policy['name']); ?></td>
                                            <td>
                                                <?php 
                                                switch ($policy['condition_type']) {
                                                    case 'sales_target':
                                                        echo '월 판매액 ₩ ' . number_format($policy['condition_value']) . ' 이상';
                                                        break;
                                                    case 'customer_rating':
                                                        echo '고객 평가 ' . number_format($policy['condition_value'], 1) . '/5.0 이상';
                                                        break;
                                                    case 'new_customer':
                                                        echo '월 ' . $policy['condition_value'] . '명 이상의 신규 고객 유치';
                                                        break;
                                                    default:
                                                        echo htmlspecialchars($policy['condition_type'] . ': ' . $policy['condition_value']);
                                                }
                                                ?>
                                            </td>
                                            <td class="text-center">+<?php echo number_format($policy['rate'], 1); ?>%</td>
                                            <td class="text-center">
                                                <?php 
                                                $statusLabels = [
                                                    'active' => '<span class="label label-success">활성</span>',
                                                    'inactive' => '<span class="label label-default">비활성</span>',
                                                    'pending' => '<span class="label label-info">대기중</span>',
                                                    'expired' => '<span class="label label-warning">만료됨</span>'
                                                ];
                                                echo isset($statusLabels[$policy['status']]) 
                                                    ? $statusLabels[$policy['status']] 
                                                    : htmlspecialchars($policy['status']);
                                                ?>
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
            
        <?php else: /* 전체 수수료 정책 관리 */ ?>
        
        <!-- 수수료 정책 관리 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-md-6">
                                <h3 class="panel-title">판매점 유형별 기본 수수료 정책</h3>
                            </div>
                            <div class="col-md-6 text-right">
                                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addCommissionPolicyModal">
                                    <i class="fa fa-plus"></i> 새 수수료 정책 추가
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>판매점 유형</th>
                                        <th>기본 수수료율</th>
                                        <th>최소 판매액</th>
                                        <th>최대 판매액</th>
                                        <th>적용 시작일</th>
                                        <th>적용 종료일</th>
                                        <th>상태</th>
                                        <th>작업</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($commissionPolicies)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">등록된 수수료 정책이 없습니다.</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($commissionPolicies as $policy): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($policy['category_label']); ?></td>
                                            <td class="text-center"><?php echo number_format($policy['base_rate'], 1); ?>%</td>
                                            <td class="text-right"><?php echo $policy['min_sales'] ? '₩ ' . number_format($policy['min_sales']) : '-'; ?></td>
                                            <td class="text-right"><?php echo $policy['max_sales'] ? '₩ ' . number_format($policy['max_sales']) : '제한 없음'; ?></td>
                                            <td class="text-center"><?php echo formatDate($policy['effective_date']); ?></td>
                                            <td class="text-center"><?php echo $policy['expiry_date'] ? formatDate($policy['expiry_date']) : '무기한'; ?></td>
                                            <td class="text-center">
                                                <?php 
                                                $statusLabels = [
                                                    'active' => '<span class="label label-success">활성</span>',
                                                    'inactive' => '<span class="label label-default">비활성</span>',
                                                    'pending' => '<span class="label label-info">대기중</span>',
                                                    'expired' => '<span class="label label-warning">만료됨</span>'
                                                ];
                                                echo isset($statusLabels[$policy['status']]) 
                                                    ? $statusLabels[$policy['status']] 
                                                    : htmlspecialchars($policy['status']);
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button class="btn btn-primary btn-xs edit-policy-btn" data-id="<?php echo $policy['id']; ?>">
                                                        <i class="fa fa-edit"></i> 수정
                                                    </button>
                                                    <?php if ($policy['status'] === 'active'): ?>
                                                    <button class="btn btn-default btn-xs deactivate-policy-btn" data-id="<?php echo $policy['id']; ?>">
                                                        <i class="fa fa-ban"></i> 비활성화
                                                    </button>
                                                    <?php else: ?>
                                                    <button class="btn btn-success btn-xs activate-policy-btn" data-id="<?php echo $policy['id']; ?>">
                                                        <i class="fa fa-check"></i> 활성화
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
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
        
        <!-- 인센티브 정책 관리 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-md-6">
                                <h3 class="panel-title">인센티브 정책</h3>
                            </div>
                            <div class="col-md-6 text-right">
                                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addIncentiveModal">
                                    <i class="fa fa-plus"></i> 새 인센티브 추가
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>인센티브 이름</th>
                                        <th>적용 판매점 유형</th>
                                        <th>조건</th>
                                        <th>추가 수수료율</th>
                                        <th>적용 시작일</th>
                                        <th>적용 종료일</th>
                                        <th>상태</th>
                                        <th>작업</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($incentivePolicies)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">등록된 인센티브 정책이 없습니다.</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($incentivePolicies as $policy): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($policy['name']); ?></td>
                                            <td>
                                                <?php 
                                                if ($policy['category'] === 'all') {
                                                    echo '모든 판매점';
                                                } else {
                                                    $categories = explode(',', $policy['category']);
                                                    $categoryLabels = [];
                                                    
                                                    $categoryMapping = [
                                                        'standard' => '표준',
                                                        'premium' => '프리미엄',
                                                        'exclusive' => '전용',
                                                        'seasonal' => '계절'
                                                    ];
                                                    
                                                    foreach ($categories as $category) {
                                                        if (isset($categoryMapping[$category])) {
                                                            $categoryLabels[] = $categoryMapping[$category];
                                                        } else {
                                                            $categoryLabels[] = $category;
                                                        }
                                                    }
                                                    
                                                    echo implode(', ', $categoryLabels);
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                switch ($policy['condition_type']) {
                                                    case 'sales_target':
                                                        echo '월 판매액 ₩ ' . number_format($policy['condition_value']) . ' 이상';
                                                        break;
                                                    case 'customer_rating':
                                                        echo '고객 평가 ' . number_format($policy['condition_value'], 1) . '/5.0 이상';
                                                        break;
                                                    case 'new_customer':
                                                        echo '월 ' . $policy['condition_value'] . '명 이상의 신규 고객 유치';
                                                        break;
                                                    default:
                                                        echo htmlspecialchars($policy['condition_type'] . ': ' . $policy['condition_value']);
                                                }
                                                ?>
                                            </td>
                                            <td class="text-center">+<?php echo number_format($policy['rate'], 1); ?>%</td>
                                            <td class="text-center"><?php echo formatDate($policy['effective_date']); ?></td>
                                            <td class="text-center"><?php echo $policy['expiry_date'] ? formatDate($policy['expiry_date']) : '무기한'; ?></td>
                                            <td class="text-center">
                                                <?php 
                                                $statusLabels = [
                                                    'active' => '<span class="label label-success">활성</span>',
                                                    'inactive' => '<span class="label label-default">비활성</span>',
                                                    'pending' => '<span class="label label-info">대기중</span>',
                                                    'expired' => '<span class="label label-warning">만료됨</span>'
                                                ];
                                                echo isset($statusLabels[$policy['status']]) 
                                                    ? $statusLabels[$policy['status']] 
                                                    : htmlspecialchars($policy['status']);
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button class="btn btn-primary btn-xs edit-incentive-btn" data-id="<?php echo $policy['id']; ?>">
                                                        <i class="fa fa-edit"></i> 수정
                                                    </button>
                                                    <?php if ($policy['status'] === 'active'): ?>
                                                    <button class="btn btn-default btn-xs deactivate-incentive-btn" data-id="<?php echo $policy['id']; ?>">
                                                        <i class="fa fa-ban"></i> 비활성화
                                                    </button>
                                                    <?php else: ?>
                                                    <button class="btn btn-success btn-xs activate-incentive-btn" data-id="<?php echo $policy['id']; ?>">
                                                        <i class="fa fa-check"></i> 활성화
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
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
        
        <!-- 새 수수료 정책 추가 모달 -->
        <div class="modal fade" id="addCommissionPolicyModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">새 수수료 정책 추가</h4>
                    </div>
                    <form id="addCommissionPolicyForm" method="post" action="commission-policy-save.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="category">판매점 유형</label>
                                <select class="form-control" id="category" name="category" required>
                                    <option value="">-- 선택하세요 --</option>
                                    <option value="standard">표준 판매점</option>
                                    <option value="premium">프리미엄 판매점</option>
                                    <option value="exclusive">전용 판매점</option>
                                    <option value="seasonal">계절 판매점</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="base_rate">기본 수수료율 (%)</label>
                                <input type="number" class="form-control" id="base_rate" name="base_rate" 
                                       step="0.1" min="0" max="100" required>
                            </div>
                            <div class="form-group">
                                <label for="min_sales">최소 판매액 (₩)</label>
                                <input type="number" class="form-control" id="min_sales" name="min_sales" 
                                       step="1000" min="0">
                                <small class="text-muted">※ 이 금액 이상 판매해야 해당 수수료율이 적용됩니다. 비워두면 제한 없음.</small>
                            </div>
                            <div class="form-group">
                                <label for="max_sales">최대 판매액 (₩)</label>
                                <input type="number" class="form-control" id="max_sales" name="max_sales" 
                                       step="1000" min="0">
                                <small class="text-muted">※ 이 금액까지만 해당 수수료율이 적용됩니다. 비워두면 제한 없음.</small>
                            </div>
                            <div class="form-group">
                                <label for="effective_date">적용 시작일</label>
                                <input type="date" class="form-control" id="effective_date" name="effective_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="expiry_date">적용 종료일</label>
                                <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                                <small class="text-muted">※ 비워두면 무기한 적용됩니다.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                            <button type="submit" class="btn btn-primary">저장</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- 새 인센티브 추가 모달 -->
        <div class="modal fade" id="addIncentiveModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">새 인센티브 추가</h4>
                    </div>
                    <form id="addIncentiveForm" method="post" action="incentive-save.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="name">인센티브 이름</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="incentive_category">적용 판매점 유형</label>
                                <select class="form-control" id="incentive_category" name="incentive_category" required>
                                    <option value="all">모든 판매점</option>
                                    <option value="standard">표준 판매점</option>
                                    <option value="premium">프리미엄 판매점</option>
                                    <option value="exclusive">전용 판매점</option>
                                    <option value="seasonal">계절 판매점</option>
                                    <option value="standard,premium">표준 + 프리미엄 판매점</option>
                                    <option value="premium,exclusive">프리미엄 + 전용 판매점</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="condition_type">조건 유형</label>
                                <select class="form-control" id="condition_type" name="condition_type" required>
                                    <option value="sales_target">판매액 목표</option>
                                    <option value="customer_rating">고객 평가 점수</option>
                                    <option value="new_customer">신규 고객 유치</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="condition_value">조건 값</label>
                                <input type="number" class="form-control" id="condition_value" name="condition_value" required>
                                <small class="text-muted condition-help" id="sales_target_help">
                                    ※ 이 금액(₩) 이상 판매해야 인센티브가 적용됩니다.
                                </small>
                                <small class="text-muted condition-help" id="customer_rating_help" style="display:none;">
                                    ※ 이 평점(0~5) 이상 받아야 인센티브가 적용됩니다.
                                </small>
                                <small class="text-muted condition-help" id="new_customer_help" style="display:none;">
                                    ※ 이 인원 이상의 신규 고객을 유치해야 인센티브가 적용됩니다.
                                </small>
                            </div>
                            <div class="form-group">
                                <label for="rate">추가 수수료율 (%)</label>
                                <input type="number" class="form-control" id="rate" name="rate" 
                                       step="0.1" min="0" max="100" required>
                            </div>
                            <div class="form-group">
                                <label for="incentive_effective_date">적용 시작일</label>
                                <input type="date" class="form-control" id="incentive_effective_date" name="incentive_effective_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="incentive_expiry_date">적용 종료일</label>
                                <input type="date" class="form-control" id="incentive_expiry_date" name="incentive_expiry_date">
                                <small class="text-muted">※ 비워두면 무기한 적용됩니다.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                            <button type="submit" class="btn btn-primary">저장</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<?php if ($isSingleStore): ?>
<!-- Chart.js 라이브러리 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>

<!-- 차트 초기화 스크립트 -->
<script>
// 월별 수수료 차트
var ctx = document.getElementById('monthlyCommissionChart').getContext('2d');

// 월별 레이블 생성
var labels = [
    <?php foreach ($commissionHistory as $history): ?>
    '<?php echo $history['period_label']; ?>',
    <?php endforeach; ?>
];

// 월별 데이터 생성
var salesData = [
    <?php foreach ($commissionHistory as $history): ?>
    <?php echo $history['sales_amount']; ?>,
    <?php endforeach; ?>
];

var baseCommissionData = [
    <?php foreach ($commissionHistory as $history): ?>
    <?php echo $history['base_commission']; ?>,
    <?php endforeach; ?>
];

var incentiveData = [
    <?php foreach ($commissionHistory as $history): ?>
    <?php echo $history['incentive_amount']; ?>,
    <?php endforeach; ?>
];

var totalCommissionData = [
    <?php foreach ($commissionHistory as $history): ?>
    <?php echo $history['total_commission']; ?>,
    <?php endforeach; ?>
];

var monthlyCommissionChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: '판매액',
                type: 'line',
                data: salesData,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointBorderColor: '#fff',
                yAxisID: 'y-axis-1',
                fill: false
            },
            {
                label: '기본 수수료',
                type: 'bar',
                data: baseCommissionData,
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1,
                yAxisID: 'y-axis-2'
            },
            {
                label: '인센티브',
                type: 'bar',
                data: incentiveData,
                backgroundColor: 'rgba(255, 159, 64, 0.5)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1,
                yAxisID: 'y-axis-2'
            },
            {
                label: '총 수수료',
                type: 'line',
                data: totalCommissionData,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                pointBorderColor: '#fff',
                yAxisID: 'y-axis-2',
                fill: false
            }
        ]
    },
    options: {
        responsive: true,
        title: {
            display: true,
            text: '월별 판매액 및 수수료'
        },
        scales: {
            yAxes: [
                {
                    id: 'y-axis-1',
                    type: 'linear',
                    position: 'left',
                    ticks: {
                        beginAtZero: true,
                        callback: function(value, index, values) {
                            return '₩' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                        }
                    },
                    scaleLabel: {
                        display: true,
                        labelString: '판매액'
                    }
                },
                {
                    id: 'y-axis-2',
                    type: 'linear',
                    position: 'right',
                    ticks: {
                        beginAtZero: true,
                        callback: function(value, index, values) {
                            return '₩' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                        }
                    },
                    scaleLabel: {
                        display: true,
                        labelString: '수수료'
                    },
                    gridLines: {
                        drawOnChartArea: false
                    }
                }
            ]
        },
        tooltips: {
            callbacks: {
                label: function(tooltipItem, data) {
                    var label = data.datasets[tooltipItem.datasetIndex].label || '';
                    if (label) {
                        label += ': ';
                    }
                    label += '₩' + tooltipItem.yLabel.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    return label;
                }
            }
        }
    }
});

// Excel 내보내기 버튼 클릭 이벤트
document.getElementById('exportBtn').addEventListener('click', function(e) {
    e.preventDefault();
    alert('Excel 내보내기 기능은 아직 구현되지 않았습니다.');
});
</script>
<?php else: ?>
<!-- 정책 관리 스크립트 -->
<script>
// 조건 유형에 따른 도움말 표시
document.getElementById('condition_type').addEventListener('change', function() {
    // 모든 도움말 숨기기
    var helps = document.getElementsByClassName('condition-help');
    for (var i = 0; i < helps.length; i++) {
        helps[i].style.display = 'none';
    }
    
    // 선택된 조건에 맞는 도움말 표시
    var selectedValue = this.value;
    document.getElementById(selectedValue + '_help').style.display = 'block';
    
    // 조건 유형에 따라 입력 항목 조정
    var conditionValueInput = document.getElementById('condition_value');
    switch (selectedValue) {
        case 'sales_target':
            conditionValueInput.setAttribute('min', '0');
            conditionValueInput.setAttribute('step', '1000');
            conditionValueInput.setAttribute('placeholder', '판매액 (₩)');
            break;
        case 'customer_rating':
            conditionValueInput.setAttribute('min', '0');
            conditionValueInput.setAttribute('max', '5');
            conditionValueInput.setAttribute('step', '0.1');
            conditionValueInput.setAttribute('placeholder', '평가 점수 (0~5)');
            break;
        case 'new_customer':
            conditionValueInput.setAttribute('min', '0');
            conditionValueInput.setAttribute('step', '1');
            conditionValueInput.setAttribute('placeholder', '신규 고객 수');
            break;
    }
});

// 폼 제출 이벤트 처리
document.getElementById('addCommissionPolicyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('정책 저장 기능은 아직 구현되지 않았습니다.');
});

document.getElementById('addIncentiveForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('인센티브 저장 기능은 아직 구현되지 않았습니다.');
});

// 수수료 정책 버튼 이벤트 처리
document.querySelectorAll('.edit-policy-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        alert('수수료 정책 수정 기능은 아직 구현되지 않았습니다.');
    });
});

document.querySelectorAll('.deactivate-policy-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (confirm('이 정책을 비활성화하시겠습니까?')) {
            alert('정책 비활성화 기능은 아직 구현되지 않았습니다.');
        }
    });
});

document.querySelectorAll('.activate-policy-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (confirm('이 정책을 활성화하시겠습니까?')) {
            alert('정책 활성화 기능은 아직 구현되지 않았습니다.');
        }
    });
});

// 인센티브 버튼 이벤트 처리
document.querySelectorAll('.edit-incentive-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        alert('인센티브 수정 기능은 아직 구현되지 않았습니다.');
    });
});

document.querySelectorAll('.deactivate-incentive-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (confirm('이 인센티브를 비활성화하시겠습니까?')) {
            alert('인센티브 비활성화 기능은 아직 구현되지 않았습니다.');
        }
    });
});

document.querySelectorAll('.activate-incentive-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (confirm('이 인센티브를 활성화하시겠습니까?')) {
            alert('인센티브 활성화 기능은 아직 구현되지 않았습니다.');
        }
    });
});
</script>
<?php endif; ?>

<?php
/**
 * 날짜 형식 변환 함수
 * 
 * @param string $date Y-m-d 형식의 날짜 문자열
 * @return string 변환된 날짜 문자열 (YYYY-MM-DD)
 */
function formatDate($date) {
    return date('Y-m-d', strtotime($date));
}

/**
 * 날짜와 시간 형식 변환 함수
 * 
 * @param string $dateTime Y-m-d H:i:s 형식의 날짜/시간 문자열
 * @return string 변환된 날짜/시간 문자열 (YYYY-MM-DD HH:MM:SS)
 */
function formatDateTime($dateTime) {
    return date('Y-m-d H:i:s', strtotime($dateTime));
}

// 푸터 템플릿 포함
include 'test-footer.php';
?>