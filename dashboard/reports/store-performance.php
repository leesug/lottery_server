<?php
/**
 * 판매점 성과 상세 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 판매점 ID 확인
$storeId = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;

if (!$storeId) {
    // ID가 없으면 목록 페이지로 리다이렉트
    header('Location: store-report.php');
    exit;
}

// 현재 페이지 정보
$pageTitle = "판매점 성과 상세";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 판매점 기본 정보 조회
$storeInfo = null;
$stmt = $db->prepare("SELECT * FROM stores WHERE store_code = ?");
$stmt->bind_param("s", $storeId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $storeInfo = $result->fetch_assoc();
}
$stmt->close();

// 판매점이 존재하지 않으면 목록 페이지로 리다이렉트
if (!$storeInfo) {
    header('Location: store-report.php');
    exit;
}

// 현재 월 및 연도
$currentMonth = date('m');
$currentYear = date('Y');

// 필터링 옵션 처리
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;

// 시작일 및 종료일 설정
$startDate = "";
$endDate = "";

switch ($period) {
    case 'monthly':
        $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        break;
    case 'quarterly':
        $quarter = ceil($month / 3);
        $startMonth = ($quarter - 1) * 3 + 1;
        $startDate = "$year-" . str_pad($startMonth, 2, '0', STR_PAD_LEFT) . "-01";
        $endDate = date('Y-m-t', strtotime("$year-" . str_pad($startMonth + 2, 2, '0', STR_PAD_LEFT) . "-01"));
        break;
    case 'yearly':
        $startDate = "$year-01-01";
        $endDate = "$year-12-31";
        break;
    case 'custom':
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        break;
}

// 판매점 성과 데이터 조회 (실제 구현에서는 DB에서 가져옴)
// 여기서는 예시 데이터를 사용
$performanceData = [
    'monthly_sales' => [
        'amount' => 2570000,
        'count' => 12850,
        'target' => 2230000,
        'target_achievement' => 115,
        'trend' => [
            ['month' => '1월', 'sales' => 2380000, 'target' => 2200000],
            ['month' => '2월', 'sales' => 2520000, 'target' => 2200000],
            ['month' => '3월', 'sales' => 2680000, 'target' => 2230000],
            ['month' => '4월', 'sales' => 2450000, 'target' => 2230000],
            ['month' => '5월', 'sales' => 2570000, 'target' => 2230000],
            ['month' => '6월', 'sales' => 0, 'target' => 2230000],
        ]
    ],
    'commission' => [
        'rate' => 8.5,
        'amount' => 218450,
        'year_to_date' => 1025680
    ],
    'customer_data' => [
        'total' => 1850,
        'new_this_period' => 120,
        'repeat_rate' => 68.5
    ],
    'prize_claims' => [
        'count' => 1420,
        'amount' => 865000,
        'largest' => 65000
    ],
    'terminal_status' => [
        'total' => 3,
        'operational' => 2,
        'maintenance' => 1,
        'faulty' => 0
    ]
];

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?php echo htmlspecialchars($storeInfo['store_name']); ?> - 성과 상세</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/">홈</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/reports/">통계 및 보고서</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/reports/store-report.php">판매점 보고서</a></li>
                    <li class="breadcrumb-item active">판매점 성과 상세</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 판매점 정보 -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">판매점 정보</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 35%">판매점 코드</th>
                                <td><?php echo htmlspecialchars($storeId); ?></td>
                            </tr>
                            <tr>
                                <th>판매점명</th>
                                <td><?php echo htmlspecialchars($storeInfo['store_name']); ?></td>
                            </tr>
                            <tr>
                                <th>소유자</th>
                                <td><?php echo htmlspecialchars($storeInfo['owner_name']); ?></td>
                            </tr>
                            <tr>
                                <th>연락처</th>
                                <td><?php echo htmlspecialchars($storeInfo['phone']); ?></td>
                            </tr>
                            <tr>
                                <th>이메일</th>
                                <td><?php echo htmlspecialchars($storeInfo['email']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 35%">주소</th>
                                <td><?php echo htmlspecialchars($storeInfo['address']); ?></td>
                            </tr>
                            <tr>
                                <th>지역</th>
                                <td><?php echo htmlspecialchars($storeInfo['city']); ?></td>
                            </tr>
                            <tr>
                                <th>카테고리</th>
                                <td>
                                    <?php
                                    switch ($storeInfo['store_category']) {
                                        case 'standard':
                                            echo '<span class="badge badge-secondary">일반</span>';
                                            break;
                                        case 'premium':
                                            echo '<span class="badge badge-primary">프리미엄</span>';
                                            break;
                                        case 'exclusive':
                                            echo '<span class="badge badge-success">독점</span>';
                                            break;
                                        default:
                                            echo '<span class="badge badge-secondary">일반</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>등록일</th>
                                <td><?php echo date('Y-m-d', strtotime($storeInfo['registration_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>상태</th>
                                <td>
                                    <?php
                                    switch ($storeInfo['status']) {
                                        case 'active':
                                            echo '<span class="badge badge-success">활성</span>';
                                            break;
                                        case 'inactive':
                                            echo '<span class="badge badge-warning">비활성</span>';
                                            break;
                                        case 'pending':
                                            echo '<span class="badge badge-info">대기중</span>';
                                            break;
                                        case 'terminated':
                                            echo '<span class="badge badge-danger">해지</span>';
                                            break;
                                        default:
                                            echo '<span class="badge badge-secondary">알 수 없음</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card -->

        <!-- 필터 옵션 -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">성과 기간 필터</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="performanceFilterForm" method="GET" action="">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($storeId); ?>">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="period">기간 선택</label>
                                <select class="form-control" id="period" name="period">
                                    <option value="monthly" <?php if($period == 'monthly') echo 'selected'; ?>>월별</option>
                                    <option value="quarterly" <?php if($period == 'quarterly') echo 'selected'; ?>>분기별</option>
                                    <option value="yearly" <?php if($period == 'yearly') echo 'selected'; ?>>연간</option>
                                    <option value="custom" <?php if($period == 'custom') echo 'selected'; ?>>사용자 지정</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="year">연도</label>
                                <select class="form-control" id="year" name="year">
                                    <?php for($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                                        <option value="<?php echo $y; ?>" <?php if($year == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="month">월</label>
                                <select class="form-control" id="month" name="month">
                                    <?php for($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?php echo $m; ?>" <?php if($month == $m) echo 'selected'; ?>><?php echo $m; ?>월</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row custom-date-range" style="display: <?php echo $period == 'custom' ? 'flex' : 'none'; ?>;">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="start_date">시작일</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="end_date">종료일</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">적용</button>
                            <button type="button" id="resetFilter" class="btn btn-default">초기화</button>
                            <div class="float-right">
                                <button type="button" id="exportPdf" class="btn btn-danger">
                                    <i class="fas fa-file-pdf"></i> PDF 내보내기
                                </button>
                                <button type="button" id="exportExcel" class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Excel 내보내기
                                </button>
                                <button type="button" id="printReport" class="btn btn-info">
                                    <i class="fas fa-print"></i> 인쇄
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- /.card -->

        <!-- 성과 요약 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>₹ <?php echo number_format($performanceData['monthly_sales']['amount']); ?></h3>
                        <p>총 판매액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo number_format($performanceData['monthly_sales']['count']); ?></h3>
                        <p>판매 건수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $performanceData['monthly_sales']['target_achievement']; ?>%</h3>
                        <p>목표 달성률</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>₹ <?php echo number_format($performanceData['commission']['amount']); ?></h3>
                        <p>수수료 금액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->

        <!-- 판매 성과 차트 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">판매 성과 추이</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="salesPerformanceChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
        <!-- /.card -->

        <!-- 상세 성과 지표 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">판매 상세 정보</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width:40%">판매 기간</th>
                                <td><?php echo date('Y년 m월 d일', strtotime($startDate)); ?> ~ <?php echo date('Y년 m월 d일', strtotime($endDate)); ?></td>
                            </tr>
                            <tr>
                                <th>총 판매액</th>
                                <td>₹ <?php echo number_format($performanceData['monthly_sales']['amount']); ?></td>
                            </tr>
                            <tr>
                                <th>판매 건수</th>
                                <td><?php echo number_format($performanceData['monthly_sales']['count']); ?> 건</td>
                            </tr>
                            <tr>
                                <th>티켓당 평균 금액</th>
                                <td>₹ <?php echo number_format($performanceData['monthly_sales']['amount'] / $performanceData['monthly_sales']['count'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>판매 목표</th>
                                <td>₹ <?php echo number_format($performanceData['monthly_sales']['target']); ?></td>
                            </tr>
                            <tr>
                                <th>목표 달성률</th>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                            style="width: <?php echo min($performanceData['monthly_sales']['target_achievement'], 100); ?>%"
                                            aria-valuenow="<?php echo $performanceData['monthly_sales']['target_achievement']; ?>" 
                                            aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $performanceData['monthly_sales']['target_achievement']; ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>수수료 요율</th>
                                <td><?php echo $performanceData['commission']['rate']; ?>%</td>
                            </tr>
                            <tr>
                                <th>수수료 금액</th>
                                <td>₹ <?php echo number_format($performanceData['commission']['amount']); ?></td>
                            </tr>
                            <tr>
                                <th>연간 누적 수수료</th>
                                <td>₹ <?php echo number_format($performanceData['commission']['year_to_date']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">고객 및 당첨 정보</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width:40%">총 고객 수</th>
                                <td><?php echo number_format($performanceData['customer_data']['total']); ?> 명</td>
                            </tr>
                            <tr>
                                <th>신규 고객 수 (이번 기간)</th>
                                <td><?php echo number_format($performanceData['customer_data']['new_this_period']); ?> 명</td>
                            </tr>
                            <tr>
                                <th>고객 재방문율</th>
                                <td><?php echo $performanceData['customer_data']['repeat_rate']; ?>%</td>
                            </tr>
                            <tr>
                                <th>당첨 건수</th>
                                <td><?php echo number_format($performanceData['prize_claims']['count']); ?> 건</td>
                            </tr>
                            <tr>
                                <th>당첨금 총액</th>
                                <td>₹ <?php echo number_format($performanceData['prize_claims']['amount']); ?></td>
                            </tr>
                            <tr>
                                <th>최고 당첨금액</th>
                                <td>₹ <?php echo number_format($performanceData['prize_claims']['largest']); ?></td>
                            </tr>
                            <tr>
                                <th>터미널 수</th>
                                <td><?php echo $performanceData['terminal_status']['total']; ?> 대</td>
                            </tr>
                            <tr>
                                <th>터미널 상태</th>
                                <td>
                                    <span class="badge badge-success">정상: <?php echo $performanceData['terminal_status']['operational']; ?></span>
                                    <span class="badge badge-warning">유지보수: <?php echo $performanceData['terminal_status']['maintenance']; ?></span>
                                    <span class="badge badge-danger">고장: <?php echo $performanceData['terminal_status']['faulty']; ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->

        <!-- 판매 상품별 데이터 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">복권 상품별 판매 현황</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 200px">복권 상품</th>
                                <th>판매 수량</th>
                                <th>판매 금액</th>
                                <th>비율</th>
                                <th>월별 변화</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>스크래치 로또</td>
                                <td>4,850</td>
                                <td>₹ 970,000</td>
                                <td>37.7%</td>
                                <td><span class="text-success"><i class="fas fa-arrow-up"></i> 5.2%</span></td>
                            </tr>
                            <tr>
                                <td>디지털 로또</td>
                                <td>4,320</td>
                                <td>₹ 864,000</td>
                                <td>33.6%</td>
                                <td><span class="text-danger"><i class="fas fa-arrow-down"></i> 2.8%</span></td>
                            </tr>
                            <tr>
                                <td>인스턴트 로또</td>
                                <td>3,680</td>
                                <td>₹ 736,000</td>
                                <td>28.7%</td>
                                <td><span class="text-success"><i class="fas fa-arrow-up"></i> 1.5%</span></td>
                            </tr>
                            <tr>
                                <td><strong>합계</strong></td>
                                <td><strong>12,850</strong></td>
                                <td><strong>₹ 2,570,000</strong></td>
                                <td><strong>100%</strong></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- /.card -->

        <!-- 판매 일별 데이터 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">일별 판매 현황</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="dailySalesChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
        <!-- /.card -->

        <!-- 터미널 현황 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">터미널 현황</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>터미널 ID</th>
                                <th>장비 유형</th>
                                <th>모델</th>
                                <th>설치일</th>
                                <th>마지막 유지보수</th>
                                <th>상태</th>
                                <th>조치</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>TRM-056</td>
                                <td>터미널</td>
                                <td>LT-5000X</td>
                                <td>2023-10-15</td>
                                <td>2025-03-22</td>
                                <td><span class="badge badge-success">정상</span></td>
                                <td>
                                    <a href="../store/equipment-details.php?id=TRM-056" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>TRM-057</td>
                                <td>터미널</td>
                                <td>LT-5000X</td>
                                <td>2023-10-15</td>
                                <td>2025-04-10</td>
                                <td><span class="badge badge-success">정상</span></td>
                                <td>
                                    <a href="../store/equipment-details.php?id=TRM-057" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>PRT-024</td>
                                <td>프린터</td>
                                <td>LP-2000</td>
                                <td>2023-10-15</td>
                                <td>2025-05-15</td>
                                <td><span class="badge badge-warning">유지보수 중</span></td>
                                <td>
                                    <a href="../store/equipment-details.php?id=PRT-024" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                <a href="../store/equipment-list.php?store_id=<?php echo htmlspecialchars($storeId); ?>" class="btn btn-sm btn-info float-right">모든 장비 보기</a>
            </div>
        </div>
        <!-- /.card -->

        <!-- 관련 링크 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">관련 정보</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-file-contract"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">계약 정보</span>
                                <a href="../store/contract-details.php?store_id=<?php echo htmlspecialchars($storeId); ?>" class="btn btn-sm btn-info mt-2">계약 상세 보기</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-money-check-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">정산 내역</span>
                                <a href="../finance/settlement-list.php?store_id=<?php echo htmlspecialchars($storeId); ?>" class="btn btn-sm btn-success mt-2">정산 내역 보기</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-graduation-cap"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">교육 내역</span>
                                <a href="../store/training-list.php?store_id=<?php echo htmlspecialchars($storeId); ?>" class="btn btn-sm btn-warning mt-2">교육 내역 보기</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card -->
    </div>
</section>
<!-- /.content -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1"></script>
<script>
// 페이지 로드 시 실행
document.addEventListener('DOMContentLoaded', function() {
    console.log('판매점 성과 상세 페이지가 로드되었습니다.');
    
    // 기간 선택에 따른 날짜 필드 토글
    const periodSelect = document.getElementById('period');
    const customDateRange = document.querySelector('.custom-date-range');
    
    if (periodSelect && customDateRange) {
        periodSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateRange.style.display = 'flex';
            } else {
                customDateRange.style.display = 'none';
            }
        });
    }
    
    // 필터 초기화 버튼
    const resetButton = document.getElementById('resetFilter');
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            window.location.href = 'store-performance.php?id=<?php echo htmlspecialchars($storeId); ?>';
        });
    }
    
    // PDF 내보내기 버튼
    const exportPdfButton = document.getElementById('exportPdf');
    if (exportPdfButton) {
        exportPdfButton.addEventListener('click', function() {
            alert('PDF 내보내기 기능은 현재 개발 중입니다.');
        });
    }
    
    // Excel 내보내기 버튼
    const exportExcelButton = document.getElementById('exportExcel');
    if (exportExcelButton) {
        exportExcelButton.addEventListener('click', function() {
            alert('Excel 내보내기 기능은 현재 개발 중입니다.');
        });
    }
    
    // 인쇄 버튼
    const printButton = document.getElementById('printReport');
    if (printButton) {
        printButton.addEventListener('click', function() {
            window.print();
        });
    }

    // 판매 성과 차트
    const salesPerformanceChart = document.getElementById('salesPerformanceChart');
    if (salesPerformanceChart) {
        new Chart(salesPerformanceChart, {
            type: 'line',
            data: {
                labels: ['1월', '2월', '3월', '4월', '5월', '6월'],
                datasets: [
                    {
                        label: '실제 판매액',
                        backgroundColor: 'rgba(60,141,188,0.9)',
                        borderColor: 'rgba(60,141,188,0.8)',
                        pointRadius: 3,
                        pointColor: '#3b8bba',
                        pointStrokeColor: 'rgba(60,141,188,1)',
                        pointHighlightFill: '#fff',
                        pointHighlightStroke: 'rgba(60,141,188,1)',
                        data: [2380000, 2520000, 2680000, 2450000, 2570000, 0]
                    },
                    {
                        label: '목표 판매액',
                        backgroundColor: 'rgba(210, 214, 222, 1)',
                        borderColor: 'rgba(210, 214, 222, 1)',
                        pointRadius: 3,
                        pointColor: 'rgba(210, 214, 222, 1)',
                        pointStrokeColor: '#c1c7d1',
                        pointHighlightFill: '#fff',
                        pointHighlightStroke: 'rgba(220,220,220,1)',
                        data: [2200000, 2200000, 2230000, 2230000, 2230000, 2230000]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        grid: {
                            display: true
                        }
                    }
                }
            }
        });
    }

    // 일별 판매 차트
    const dailySalesChart = document.getElementById('dailySalesChart');
    if (dailySalesChart) {
        // 현재 달의 일수에 따라 레이블 생성
        const daysInMonth = new Date(<?php echo $year; ?>, <?php echo $month; ?>, 0).getDate();
        const dayLabels = Array.from({length: daysInMonth}, (_, i) => (i + 1) + '일');
        
        // 랜덤 데이터 생성 (실제 구현에서는 DB에서 가져온 데이터 사용)
        const generateRandomData = (min, max, count) => {
            return Array.from({length: count}, () => Math.floor(Math.random() * (max - min + 1) + min));
        };
        
        const salesData = generateRandomData(50000, 150000, daysInMonth);
        
        new Chart(dailySalesChart, {
            type: 'bar',
            data: {
                labels: dayLabels,
                datasets: [
                    {
                        label: '일별 판매액',
                        backgroundColor: 'rgba(60,141,188,0.9)',
                        borderColor: 'rgba(60,141,188,0.8)',
                        pointRadius: false,
                        pointColor: '#3b8bba',
                        pointStrokeColor: 'rgba(60,141,188,1)',
                        pointHighlightFill: '#fff',
                        pointHighlightStroke: 'rgba(60,141,188,1)',
                        data: salesData
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        grid: {
                            display: true
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php
// 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>
