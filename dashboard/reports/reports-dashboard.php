<?php
/**
 * Reports Dashboard Page
 * 
 * This page displays an overview of various reports and statistics available in the system.
 */

// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "통계 및 보고서 대시보드";
$currentSection = "reports";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = get_db_connection();

// 주요 지표 통계 (Mock 데이터 사용)
$mainStats = [
    'total_sales' => 234580000000,
    'total_prizes' => 125680000000,
    'total_customers' => 2450000,
    'active_tickets' => 8750000,
    'total_draws' => 520,
    'total_winners' => 12580000,
    'jackpot_winners' => 58,
    'avg_prize_per_ticket' => 14350
];

// 연간 판매 통계 (Mock 데이터 사용)
$yearlySales = [
    ['year' => 2020, 'amount' => 175450000000],
    ['year' => 2021, 'amount' => 185680000000],
    ['year' => 2022, 'amount' => 198750000000],
    ['year' => 2023, 'amount' => 212450000000],
    ['year' => 2024, 'amount' => 225870000000],
    ['year' => 2025, 'amount' => 234580000000, 'projected' => true]
];

// 월별 판매 통계 (2025년 데이터, Mock 데이터 사용)
$monthlySales = [
    ['month' => '2025-01', 'label' => '1월', 'amount' => 18750000000],
    ['month' => '2025-02', 'label' => '2월', 'amount' => 17850000000],
    ['month' => '2025-03', 'label' => '3월', 'amount' => 19250000000],
    ['month' => '2025-04', 'label' => '4월', 'amount' => 20150000000],
    ['month' => '2025-05', 'label' => '5월', 'amount' => 19580000000]
];

// 지역별 판매 통계 (Mock 데이터 사용)
$regionSales = [
    ['region' => '서울', 'amount' => 58450000000, 'percentage' => 24.9],
    ['region' => '경기', 'amount' => 45780000000, 'percentage' => 19.5],
    ['region' => '인천', 'amount' => 18450000000, 'percentage' => 7.8],
    ['region' => '부산', 'amount' => 15860000000, 'percentage' => 6.7],
    ['region' => '대구', 'amount' => 12540000000, 'percentage' => 5.3],
    ['region' => '광주', 'amount' => 8750000000, 'percentage' => 3.7],
    ['region' => '대전', 'amount' => 7950000000, 'percentage' => 3.4],
    ['region' => '울산', 'amount' => 6850000000, 'percentage' => 2.9],
    ['region' => '세종', 'amount' => 2150000000, 'percentage' => 0.9],
    ['region' => '강원', 'amount' => 6450000000, 'percentage' => 2.7],
    ['region' => '충북', 'amount' => 5850000000, 'percentage' => 2.5],
    ['region' => '충남', 'amount' => 6780000000, 'percentage' => 2.9],
    ['region' => '전북', 'amount' => 5450000000, 'percentage' => 2.3],
    ['region' => '전남', 'amount' => 5650000000, 'percentage' => 2.4],
    ['region' => '경북', 'amount' => 7950000000, 'percentage' => 3.4],
    ['region' => '경남', 'amount' => 8540000000, 'percentage' => 3.6],
    ['region' => '제주', 'amount' => 3580000000, 'percentage' => 1.5],
    ['region' => '온라인', 'amount' => 7550000000, 'percentage' => 3.2]
];

// 복권 유형별 판매 통계 (Mock 데이터 사용)
$productSales = [
    ['product' => '로또 6/45', 'amount' => 145680000000, 'percentage' => 62.1],
    ['product' => '연금복권', 'amount' => 45750000000, 'percentage' => 19.5],
    ['product' => '스피또', 'amount' => 25450000000, 'percentage' => 10.8],
    ['product' => '전자복권', 'amount' => 12540000000, 'percentage' => 5.3],
    ['product' => '인스턴트 복권', 'amount' => 5160000000, 'percentage' => 2.3]
];

// 최근 보고서 목록 (Mock 데이터 사용)
$recentReports = [
    ['id' => 'R2505001', 'title' => '2025년 5월 판매 현황 보고서', 'type' => 'sales', 'created_at' => '2025-05-18 09:30:12', 'created_by' => '김재원', 'format' => 'pdf', 'size' => '2.4MB'],
    ['id' => 'R2505002', 'title' => '2025년 5월 당첨자 통계 분석', 'type' => 'winner', 'created_at' => '2025-05-17 14:25:45', 'created_by' => '이현우', 'format' => 'excel', 'size' => '3.8MB'],
    ['id' => 'R2505003', 'title' => '2025년 1분기 재무 보고서', 'type' => 'finance', 'created_at' => '2025-05-15 10:15:32', 'created_by' => '박소연', 'format' => 'pdf', 'size' => '5.2MB'],
    ['id' => 'R2504001', 'title' => '판매점 성과 분석 (2025년 4월)', 'type' => 'store', 'created_at' => '2025-04-30 16:40:18', 'created_by' => '정민준', 'format' => 'excel', 'size' => '4.5MB'],
    ['id' => 'R2504002', 'title' => '로또 6/45 추첨 결과 분석 (2025년 4월)', 'type' => 'draw', 'created_at' => '2025-04-28 11:20:55', 'created_by' => '최지원', 'format' => 'pdf', 'size' => '3.1MB']
];

// 자주 조회되는 보고서 (Mock 데이터 사용)
$popularReports = [
    ['id' => 'T001', 'title' => '판매 현황 일일 보고서', 'description' => '일별 판매량, 판매액, 판매점별 통계 등을 포함한 일일 판매 현황 보고서', 'frequency' => '일별'],
    ['id' => 'T002', 'title' => '당첨금 지급 현황', 'description' => '등수별 당첨자 수, 당첨금 지급 상태, 미지급 당첨금 현황 등을 포함한 보고서', 'frequency' => '주간'],
    ['id' => 'T003', 'title' => '재무 요약 보고서', 'description' => '판매액, 당첨금 지급액, 수익금, 기금 적립 등의 재무 정보를 포함한 요약 보고서', 'frequency' => '월간'],
    ['id' => 'T004', 'title' => '판매점 성과 분석', 'description' => '판매점별 판매 성과, 판매액 추이, 우수 판매점 분석 등을 포함한 보고서', 'frequency' => '월간'],
    ['id' => 'T005', 'title' => '마케팅 캠페인 효과 분석', 'description' => '마케팅 활동별 효과, 투자 대비 수익률(ROI), 고객 유입 분석 등을 포함한 보고서', 'frequency' => '분기별']
];

// 템플릿 헤더 포함 - 여기서 content-wrapper 클래스를 가진 div가 시작됨
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
                    <li class="breadcrumb-item">통계 및 보고서</li>
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
        <!-- 빠른 액션 버튼 -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="btn-group">
                    <a href="sales-report.php" class="btn btn-primary"><i class="fas fa-chart-line"></i> 판매 보고서</a>
                    <a href="financial-report.php" class="btn btn-success"><i class="fas fa-money-bill-wave"></i> 재무 보고서</a>
                    <a href="draw-report.php" class="btn btn-info"><i class="fas fa-random"></i> 추첨 보고서</a>
                    <a href="store-report.php" class="btn btn-warning"><i class="fas fa-store"></i> 판매점 보고서</a>
                    <a href="customer-report.php" class="btn btn-secondary"><i class="fas fa-users"></i> 고객 보고서</a>
                </div>
            </div>
        </div>

        <!-- 주요 통계 요약 카드 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo number_format($mainStats['total_sales'] / 1000000000); ?>B</h3>
                        <p>누적 판매액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <a href="sales-report.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo number_format($mainStats['total_prizes'] / 1000000000); ?>B</h3>
                        <p>누적 당첨금</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <a href="draw-report.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo number_format($mainStats['total_customers'] / 10000); ?>만</h3>
                        <p>누적 고객 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <a href="customer-report.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo number_format($mainStats['jackpot_winners']); ?></h3>
                        <p>1등 당첨자 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-medal"></i>
                    </div>
                    <a href="draw-report.php?filter=jackpot" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 연간 판매 추이 차트 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">연간 판매 추이</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="yearlyChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 복권 유형별 판매 분포 차트 -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">복권 유형별 판매 분포</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="productChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 최근 생성된 보고서 목록 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">최근 생성된 보고서</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>보고서 ID</th>
                                        <th>제목</th>
                                        <th>유형</th>
                                        <th>생성일시</th>
                                        <th>생성자</th>
                                        <th>형식</th>
                                        <th>액션</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentReports as $report): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($report['id']); ?></td>
                                        <td><?php echo htmlspecialchars($report['title']); ?></td>
                                        <td>
                                            <?php 
                                            $typeClass = '';
                                            $typeLabel = '';
                                            
                                            switch($report['type']) {
                                                case 'sales':
                                                    $typeClass = 'badge-primary';
                                                    $typeLabel = '판매';
                                                    break;
                                                case 'winner':
                                                    $typeClass = 'badge-success';
                                                    $typeLabel = '당첨자';
                                                    break;
                                                case 'finance':
                                                    $typeClass = 'badge-warning';
                                                    $typeLabel = '재무';
                                                    break;
                                                case 'store':
                                                    $typeClass = 'badge-info';
                                                    $typeLabel = '판매점';
                                                    break;
                                                case 'draw':
                                                    $typeClass = 'badge-danger';
                                                    $typeLabel = '추첨';
                                                    break;
                                                default:
                                                    $typeClass = 'badge-secondary';
                                                    $typeLabel = '기타';
                                            }
                                            ?>
                                            <span class="badge <?php echo $typeClass; ?>"><?php echo $typeLabel; ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($report['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($report['created_by']); ?></td>
                                        <td>
                                            <?php if ($report['format'] === 'pdf'): ?>
                                                <span class="badge badge-danger">PDF</span>
                                            <?php elseif ($report['format'] === 'excel'): ?>
                                                <span class="badge badge-success">Excel</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary"><?php echo strtoupper($report['format']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="#" class="btn btn-sm btn-info" title="보기">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-success" title="다운로드">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-warning" title="공유">
                                                    <i class="fas fa-share-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer clearfix">
                        <a href="#" class="btn btn-sm btn-secondary float-right">모든 보고서 보기</a>
                    </div>
                </div>
            </div>

            <!-- 자주 조회되는 보고서 -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">자주 조회되는 보고서</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="products-list product-list-in-card pl-2 pr-2">
                            <?php foreach ($popularReports as $report): ?>
                            <li class="item">
                                <div class="product-img">
                                    <i class="fas fa-file-alt fa-2x text-info"></i>
                                </div>
                                <div class="product-info">
                                    <a href="#" class="product-title">
                                        <?php echo htmlspecialchars($report['title']); ?>
                                        <span class="badge badge-info float-right"><?php echo htmlspecialchars($report['frequency']); ?></span>
                                    </a>
                                    <span class="product-description">
                                        <?php echo htmlspecialchars($report['description']); ?>
                                    </span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="card-footer text-center">
                        <a href="#" class="btn btn-sm btn-primary">
                            즐겨찾는 보고서 관리
                        </a>
                    </div>
                </div>

                <!-- 보고서 생성 카드 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">맞춤형 보고서 생성</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>보고서 유형</label>
                            <select class="form-control">
                                <option>판매 보고서</option>
                                <option>재무 보고서</option>
                                <option>추첨 보고서</option>
                                <option>판매점 보고서</option>
                                <option>고객 보고서</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>기간 선택</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="far fa-calendar-alt"></i>
                                    </span>
                                </div>
                                <input type="text" class="form-control" placeholder="시작일 ~ 종료일">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>포함할 데이터</label>
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="customCheckbox1" checked>
                                <label for="customCheckbox1" class="custom-control-label">판매 데이터</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="customCheckbox2" checked>
                                <label for="customCheckbox2" class="custom-control-label">수익 데이터</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="customCheckbox3" checked>
                                <label for="customCheckbox3" class="custom-control-label">비교 분석</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="customCheckbox4">
                                <label for="customCheckbox4" class="custom-control-label">그래프 및 차트</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>출력 형식</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="outputFormat" checked>
                                <label class="form-check-label">PDF</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="outputFormat">
                                <label class="form-check-label">Excel</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="outputFormat">
                                <label class="form-check-label">HTML</label>
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="button" class="btn btn-primary">보고서 생성</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 지역별 판매 분포 및 월별 판매 추이 -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">지역별 판매 분포</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="chart-responsive">
                                    <canvas id="regionChart" style="height: 300px;"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>지역</th>
                                            <th>판매액 (억원)</th>
                                            <th style="width: 40%">비율</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // 상위 10개 지역 표시
                                        $topRegions = array_slice($regionSales, 0, 10);
                                        foreach ($topRegions as $region): 
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($region['region']); ?></td>
                                            <td><?php echo number_format($region['amount'] / 100000000); ?></td>
                                            <td>
                                                <div class="progress progress-xs">
                                                    <div class="progress-bar bg-primary" style="width: <?php echo $region['percentage']; ?>%"></div>
                                                </div>
                                                <span class="badge bg-primary"><?php echo number_format($region['percentage'], 1); ?>%</span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">월별 판매 추이 (2025년)</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<!-- 필요한 JavaScript 포함 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 연간 판매 추이 차트
    const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
    const yearlyData = <?php echo json_encode(array_column($yearlySales, 'amount')); ?>;
    const yearlyLabels = <?php echo json_encode(array_column($yearlySales, 'year')); ?>;
    
    new Chart(yearlyCtx, {
        type: 'bar',
        data: {
            labels: yearlyLabels,
            datasets: [{
                label: '연간 판매액 (억원)',
                backgroundColor: 'rgba(60,141,188,0.8)',
                data: yearlyData.map(amount => amount / 100000000)
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: '판매액 (억원)'
                    }
                }
            }
        }
    });

    // 복권 유형별 판매 분포 차트
    const productCtx = document.getElementById('productChart').getContext('2d');
    const productData = <?php echo json_encode(array_column($productSales, 'percentage')); ?>;
    const productLabels = <?php echo json_encode(array_column($productSales, 'product')); ?>;
    
    new Chart(productCtx, {
        type: 'pie',
        data: {
            labels: productLabels,
            datasets: [{
                data: productData,
                backgroundColor: ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc']
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true
        }
    });

    // 월별 판매 추이 차트
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyData = <?php echo json_encode(array_column($monthlySales, 'amount')); ?>;
    const monthlyLabels = <?php echo json_encode(array_column($monthlySales, 'label')); ?>;
    
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: '월별 판매액 (억원)',
                backgroundColor: 'rgba(210, 214, 222, 0.2)',
                borderColor: 'rgba(210, 214, 222, 1)',
                pointRadius: 3,
                pointColor: 'rgba(210, 214, 222, 1)',
                pointStrokeColor: '#c1c7d1',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(220,220,220,1)',
                data: monthlyData.map(amount => amount / 100000000)
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // 지역별 판매 분포 차트
    const regionCtx = document.getElementById('regionChart').getContext('2d');
    // 상위 5개 지역만 표시
    const topRegions = <?php echo json_encode(array_slice($regionSales, 0, 5)); ?>;
    const regionData = topRegions.map(region => region.percentage);
    const regionLabels = topRegions.map(region => region.region);
    
    new Chart(regionCtx, {
        type: 'doughnut',
        data: {
            labels: regionLabels,
            datasets: [{
                data: regionData,
                backgroundColor: ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc']
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true
        }
    });
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
