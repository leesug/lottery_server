<?php
/**
 * Marketing Dashboard Page
 * 
 * This page displays statistics and analytics related to marketing activities,
 * including campaign performance, email marketing stats, promotions, etc.
 */

// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "마케팅 관리 대시보드";
$currentSection = "marketing";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = get_db_connection();

// 마케팅 통계 정보 (Mock 데이터 사용)
$marketingStats = [
    'active_campaigns' => 8,
    'completed_campaigns' => 32,
    'scheduled_campaigns' => 5,
    'total_advertisements' => 45,
    'active_promotions' => 12,
    'total_budget' => 850000000,
    'spent_budget' => 612450000,
    'remaining_budget' => 237550000
];

// 캠페인 성과 데이터 (Mock 데이터 사용)
$campaignPerformance = [
    ['campaign_name' => '여름 특별 이벤트', 'start_date' => '2025-05-01', 'end_date' => '2025-06-30', 'budget' => 120000000, 'spent' => 45250000, 'leads' => 2840, 'conversions' => 534, 'roi' => 18.5],
    ['campaign_name' => '명절 특별 프로모션', 'start_date' => '2025-04-15', 'end_date' => '2025-05-15', 'budget' => 180000000, 'spent' => 158750000, 'leads' => 6520, 'conversions' => 1243, 'roi' => 22.1],
    ['campaign_name' => '신규 회원 유치', 'start_date' => '2025-03-01', 'end_date' => '2025-05-31', 'budget' => 250000000, 'spent' => 178500000, 'leads' => 8750, 'conversions' => 1485, 'roi' => 16.8],
    ['campaign_name' => '당첨자 스토리', 'start_date' => '2025-04-01', 'end_date' => '2025-06-30', 'budget' => 85000000, 'spent' => 42500000, 'leads' => 3450, 'conversions' => 587, 'roi' => 27.5],
    ['campaign_name' => '모바일 앱 다운로드', 'start_date' => '2025-05-10', 'end_date' => '2025-06-10', 'budget' => 70000000, 'spent' => 21680000, 'leads' => 1850, 'conversions' => 742, 'roi' => 31.2]
];

// 마케팅 채널별 성과 (Mock 데이터 사용)
$channelPerformance = [
    ['channel' => '소셜 미디어', 'leads' => 12540, 'conversions' => 2345, 'budget' => 230000000, 'spent' => 183450000, 'roi' => 21.8],
    ['channel' => '이메일 마케팅', 'leads' => 8750, 'conversions' => 1654, 'budget' => 120000000, 'spent' => 98700000, 'roi' => 25.4],
    ['channel' => '인쇄 광고', 'leads' => 4520, 'conversions' => 752, 'budget' => 150000000, 'spent' => 120500000, 'roi' => 14.2],
    ['channel' => '온라인 광고', 'leads' => 15680, 'conversions' => 2978, 'budget' => 220000000, 'spent' => 185600000, 'roi' => 24.7],
    ['channel' => 'TV/라디오', 'leads' => 6890, 'conversions' => 1250, 'budget' => 130000000, 'spent' => 115700000, 'roi' => 17.5]
];

// 이메일 캠페인 성과 (Mock 데이터 사용)
$emailCampaigns = [
    ['name' => '5월 신규 고객 웰컴', 'sent' => 15420, 'opened' => 8754, 'clicked' => 3254, 'converted' => 587, 'date_sent' => '2025-05-15'],
    ['name' => '봄 시즌 프로모션', 'sent' => 42580, 'opened' => 24687, 'clicked' => 9854, 'converted' => 1542, 'date_sent' => '2025-05-01'],
    ['name' => '당첨자 인터뷰', 'sent' => 38750, 'opened' => 21580, 'clicked' => 8240, 'converted' => 1250, 'date_sent' => '2025-04-20'],
    ['name' => '복권 구매 안내', 'sent' => 28450, 'opened' => 14580, 'clicked' => 5420, 'converted' => 854, 'date_sent' => '2025-04-15'],
    ['name' => '뉴스레터 4월호', 'sent' => 45280, 'opened' => 23140, 'clicked' => 8520, 'converted' => 1420, 'date_sent' => '2025-04-01']
];

// 월별 마케팅 성과 추이 (Mock 데이터 사용)
$monthlyPerformance = [
    ['month' => '2024-06', 'label' => '6월', 'leads' => 12540, 'conversions' => 1982, 'roi' => 18.2],
    ['month' => '2024-07', 'label' => '7월', 'leads' => 13280, 'conversions' => 2154, 'roi' => 19.4],
    ['month' => '2024-08', 'label' => '8월', 'leads' => 14520, 'conversions' => 2345, 'roi' => 20.1],
    ['month' => '2024-09', 'label' => '9월', 'leads' => 15870, 'conversions' => 2587, 'roi' => 21.2],
    ['month' => '2024-10', 'label' => '10월', 'leads' => 16250, 'conversions' => 2785, 'roi' => 22.5],
    ['month' => '2024-11', 'label' => '11월', 'leads' => 18540, 'conversions' => 3120, 'roi' => 23.8],
    ['month' => '2024-12', 'label' => '12월', 'leads' => 21580, 'conversions' => 3854, 'roi' => 25.4],
    ['month' => '2025-01', 'label' => '1월', 'leads' => 14580, 'conversions' => 2541, 'roi' => 20.8],
    ['month' => '2025-02', 'label' => '2월', 'leads' => 15420, 'conversions' => 2687, 'roi' => 21.5],
    ['month' => '2025-03', 'label' => '3월', 'leads' => 16850, 'conversions' => 2954, 'roi' => 22.8],
    ['month' => '2025-04', 'label' => '4월', 'leads' => 18240, 'conversions' => 3251, 'roi' => 23.5],
    ['month' => '2025-05', 'label' => '5월', 'leads' => 19750, 'conversions' => 3624, 'roi' => 24.2]
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
                    <li class="breadcrumb-item">마케팅 관리</li>
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
                    <a href="campaigns.php" class="btn btn-primary"><i class="fas fa-bullhorn"></i> 캠페인 관리</a>
                    <a href="email.php" class="btn btn-success"><i class="fas fa-envelope"></i> 이메일 마케팅</a>
                    <a href="sms.php" class="btn btn-info"><i class="fas fa-sms"></i> SMS 마케팅</a>
                    <a href="advertisements.php" class="btn btn-warning"><i class="fas fa-ad"></i> 광고 관리</a>
                    <a href="promotions.php" class="btn btn-secondary"><i class="fas fa-percent"></i> 프로모션 관리</a>
                </div>
            </div>
        </div>

        <!-- 마케팅 통계 요약 카드 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo $marketingStats['active_campaigns']; ?></h3>
                        <p>진행 중인 캠페인</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <a href="campaigns.php?filter=active" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $marketingStats['active_promotions']; ?></h3>
                        <p>진행 중인 프로모션</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-percent"></i>
                    </div>
                    <a href="promotions.php?filter=active" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $marketingStats['scheduled_campaigns']; ?></h3>
                        <p>예정된 캠페인</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <a href="campaigns.php?filter=scheduled" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo number_format($marketingStats['remaining_budget']); ?></h3>
                        <p>남은 예산</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <a href="budget-allocation-manage.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 월별 마케팅 성과 추이 차트 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">월별 마케팅 성과 추이</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="marketingPerformanceChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 마케팅 예산 현황 -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">마케팅 예산 현황</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="budgetDoughnutChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        <div class="mt-3">
                            <div class="row">
                                <div class="col-6 text-center">
                                    <div class="text-primary font-weight-bold">
                                        <?php echo number_format($marketingStats['spent_budget']); ?>원
                                    </div>
                                    <div class="text-muted">
                                        사용 예산
                                    </div>
                                </div>
                                <div class="col-6 text-center">
                                    <div class="text-success font-weight-bold">
                                        <?php echo number_format($marketingStats['remaining_budget']); ?>원
                                    </div>
                                    <div class="text-muted">
                                        남은 예산
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 현재 진행 중인 캠페인 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">현재 진행 중인 캠페인</h3>
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
                                        <th>캠페인명</th>
                                        <th>시작일</th>
                                        <th>종료일</th>
                                        <th>예산</th>
                                        <th>지출</th>
                                        <th>ROI</th>
                                        <th>진행률</th>
                                        <th>액션</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campaignPerformance as $campaign): 
                                        $progressPercentage = round(($campaign['spent'] / $campaign['budget']) * 100);
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($campaign['campaign_name']); ?></td>
                                        <td><?php echo htmlspecialchars($campaign['start_date']); ?></td>
                                        <td><?php echo htmlspecialchars($campaign['end_date']); ?></td>
                                        <td><?php echo number_format($campaign['budget']); ?>원</td>
                                        <td><?php echo number_format($campaign['spent']); ?>원</td>
                                        <td><?php echo $campaign['roi']; ?>%</td>
                                        <td>
                                            <div class="progress progress-xs">
                                                <div class="progress-bar bg-primary" style="width: <?php echo $progressPercentage; ?>%"></div>
                                            </div>
                                            <span class="badge bg-primary"><?php echo $progressPercentage; ?>%</span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="campaigns_new.php?id=<?php echo urlencode($campaign['campaign_name']); ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="campaigns_new.php?id=<?php echo urlencode($campaign['campaign_name']); ?>&action=edit" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
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
                        <a href="campaigns.php" class="btn btn-sm btn-secondary float-right">모든 캠페인 보기</a>
                    </div>
                </div>
            </div>

            <!-- 마케팅 채널별 성과 -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">마케팅 채널별 성과</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>채널</th>
                                    <th>전환</th>
                                    <th>ROI</th>
                                    <th>효율</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($channelPerformance as $channel): 
                                    $efficiency = ($channel['conversions'] / $channel['spent']) * 1000000; // 백만원당 전환 수
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($channel['channel']); ?></td>
                                    <td><?php echo number_format($channel['conversions']); ?></td>
                                    <td><?php echo $channel['roi']; ?>%</td>
                                    <td>
                                        <div class="progress progress-xs">
                                            <div class="progress-bar bg-success" style="width: <?php echo min($efficiency/10, 100); ?>%"></div>
                                        </div>
                                        <span class="badge bg-success"><?php echo number_format($efficiency/100, 1); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer clearfix">
                        <a href="#" class="btn btn-sm btn-secondary float-right">채널 효율성 분석</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 이메일 마케팅 성과 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">최근 이메일 캠페인 성과</h3>
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
                                        <th>캠페인명</th>
                                        <th>발송일</th>
                                        <th>발송수</th>
                                        <th>오픈율</th>
                                        <th>클릭율</th>
                                        <th>전환율</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($emailCampaigns as $email): 
                                        $openRate = round(($email['opened'] / $email['sent']) * 100, 1);
                                        $clickRate = round(($email['clicked'] / $email['sent']) * 100, 1);
                                        $conversionRate = round(($email['converted'] / $email['sent']) * 100, 1);
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($email['name']); ?></td>
                                        <td><?php echo htmlspecialchars($email['date_sent']); ?></td>
                                        <td><?php echo number_format($email['sent']); ?></td>
                                        <td><?php echo $openRate; ?>%</td>
                                        <td><?php echo $clickRate; ?>%</td>
                                        <td><?php echo $conversionRate; ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer clearfix">
                        <a href="email.php" class="btn btn-sm btn-secondary float-right">모든 이메일 캠페인 보기</a>
                    </div>
                </div>
            </div>

            <!-- 이메일 성과 차트 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">이메일 캠페인 통계</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="emailPerformanceChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- 홍보 채널 및 고객 유입 경로 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">신규 고객 유입 경로</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="customerSourceChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">마케팅 효과 요약</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box bg-gradient-info">
                                    <span class="info-box-icon"><i class="fas fa-users"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">총 리드</span>
                                        <span class="info-box-number"><?php echo number_format(array_sum(array_column($monthlyPerformance, 'leads'))); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-gradient-success">
                                    <span class="info-box-icon"><i class="fas fa-handshake"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">총 전환</span>
                                        <span class="info-box-number"><?php echo number_format(array_sum(array_column($monthlyPerformance, 'conversions'))); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="info-box bg-gradient-warning">
                                    <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">평균 ROI</span>
                                        <span class="info-box-number"><?php echo number_format(array_sum(array_column($monthlyPerformance, 'roi')) / count($monthlyPerformance), 1); ?>%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-gradient-danger">
                                    <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">전환당 비용</span>
                                        <span class="info-box-number"><?php echo number_format($marketingStats['spent_budget'] / array_sum(array_column($monthlyPerformance, 'conversions'))); ?>원</span>
                                    </div>
                                </div>
                            </div>
                        </div>
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
    // 월별 마케팅 성과 추이 차트
    const performanceCtx = document.getElementById('marketingPerformanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthlyPerformance, 'label')); ?>,
            datasets: [
                {
                    label: '리드 수',
                    backgroundColor: 'rgba(60,141,188,0.2)',
                    borderColor: 'rgba(60,141,188,1)',
                    pointRadius: 3,
                    pointColor: '#3b8bba',
                    pointStrokeColor: 'rgba(60,141,188,1)',
                    pointHighlightFill: '#fff',
                    pointHighlightStroke: 'rgba(60,141,188,1)',
                    data: <?php echo json_encode(array_column($monthlyPerformance, 'leads')); ?>
                },
                {
                    label: '전환 수',
                    backgroundColor: 'rgba(210, 214, 222, 0.2)',
                    borderColor: 'rgba(210, 214, 222, 1)',
                    pointRadius: 3,
                    pointColor: 'rgba(210, 214, 222, 1)',
                    pointStrokeColor: '#c1c7d1',
                    pointHighlightFill: '#fff',
                    pointHighlightStroke: 'rgba(220,220,220,1)',
                    data: <?php echo json_encode(array_column($monthlyPerformance, 'conversions')); ?>
                }
            ]
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

    // 마케팅 예산 현황 차트
    const budgetCtx = document.getElementById('budgetDoughnutChart').getContext('2d');
    new Chart(budgetCtx, {
        type: 'doughnut',
        data: {
            labels: ['사용 예산', '남은 예산'],
            datasets: [{
                data: [
                    <?php echo $marketingStats['spent_budget']; ?>,
                    <?php echo $marketingStats['remaining_budget']; ?>
                ],
                backgroundColor: ['#007bff', '#28a745']
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true
        }
    });

    // 이메일 캠페인 성과 차트
    const emailCtx = document.getElementById('emailPerformanceChart').getContext('2d');
    new Chart(emailCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($emailCampaigns, 'name')); ?>,
            datasets: [
                {
                    label: '오픈율 (%)',
                    backgroundColor: 'rgba(60,141,188,0.8)',
                    data: <?php 
                        $openRates = array_map(function($email) { 
                            return round(($email['opened'] / $email['sent']) * 100, 1); 
                        }, $emailCampaigns);
                        echo json_encode($openRates); 
                    ?>
                },
                {
                    label: '클릭율 (%)',
                    backgroundColor: 'rgba(210, 214, 222, 0.8)',
                    data: <?php 
                        $clickRates = array_map(function($email) { 
                            return round(($email['clicked'] / $email['sent']) * 100, 1); 
                        }, $emailCampaigns);
                        echo json_encode($clickRates); 
                    ?>
                },
                {
                    label: '전환율 (%)',
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    data: <?php 
                        $conversionRates = array_map(function($email) { 
                            return round(($email['converted'] / $email['sent']) * 100, 1); 
                        }, $emailCampaigns);
                        echo json_encode($conversionRates); 
                    ?>
                }
            ]
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

    // 신규 고객 유입 경로 차트
    const sourceCtx = document.getElementById('customerSourceChart').getContext('2d');
    new Chart(sourceCtx, {
        type: 'pie',
        data: {
            labels: ['소셜 미디어', '친구 추천', '검색 엔진', '오프라인 광고', '이메일 마케팅', '기타'],
            datasets: [{
                data: [35, 25, 15, 10, 10, 5],
                backgroundColor: ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de']
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
