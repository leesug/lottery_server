<?php
/**
 * Customer Dashboard Page
 * 
 * This page displays statistics and analytics related to customer activities,
 * including purchase trends, demographics, activity levels, etc.
 */

// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "고객 활동 대시보드";
$currentSection = "customer";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = get_db_connection();

// 대시보드 데이터 수집 (Mock 데이터 사용)
$customerStats = [
    'total' => 8742,
    'active' => 6531,
    'inactive' => 1982,
    'new_30_days' => 412,
    'verified' => 7125,
    'blocked' => 229,
    'pending_verification' => 874,
    'vip' => 658
];

// 고객 등급별 분포
$customerTiers = [
    ['tier' => '일반', 'count' => 4532],
    ['tier' => '실버', 'count' => 2104],
    ['tier' => '골드', 'count' => 1420],
    ['tier' => 'VIP', 'count' => 658],
    ['tier' => 'VVIP', 'count' => 28]
];

// 지역별 고객 분포
$regionDistribution = [
    ['region' => '서울', 'count' => 2340],
    ['region' => '경기', 'count' => 1984],
    ['region' => '인천', 'count' => 823],
    ['region' => '부산', 'count' => 712],
    ['region' => '대구', 'count' => 542],
    ['region' => '광주', 'count' => 389],
    ['region' => '대전', 'count' => 356],
    ['region' => '울산', 'count' => 298],
    ['region' => '기타', 'count' => 1298]
];

// 월별 신규 가입 고객 추이 (최근 12개월)
$monthlyRegistrations = [
    ['month' => '2024-06', 'label' => '6월', 'count' => 389],
    ['month' => '2024-07', 'label' => '7월', 'count' => 412],
    ['month' => '2024-08', 'label' => '8월', 'count' => 378],
    ['month' => '2024-09', 'label' => '9월', 'count' => 401],
    ['month' => '2024-10', 'label' => '10월', 'count' => 452],
    ['month' => '2024-11', 'label' => '11월', 'count' => 489],
    ['month' => '2024-12', 'label' => '12월', 'count' => 524],
    ['month' => '2025-01', 'label' => '1월', 'count' => 568],
    ['month' => '2025-02', 'label' => '2월', 'count' => 487],
    ['month' => '2025-03', 'label' => '3월', 'count' => 423],
    ['month' => '2025-04', 'label' => '4월', 'count' => 402],
    ['month' => '2025-05', 'label' => '5월', 'count' => 412]
];

// 거래 유형별 분포
$transactionTypes = [
    ['type' => '복권 구매', 'count' => 68450],
    ['type' => '당첨금 수령', 'count' => 12856],
    ['type' => '충전', 'count' => 9872],
    ['type' => '환불', 'count' => 1245],
    ['type' => '기타', 'count' => 874]
];

// 최근 등록 고객 목록
$recentCustomers = [
    ['id' => 8742, 'customer_code' => 'CUST24050001', 'name' => '김철수', 'email' => 'cs.kim@example.com', 'phone' => '010-1234-5678', 'registration_date' => '2025-05-18 08:42:15', 'status' => 'active'],
    ['id' => 8741, 'customer_code' => 'CUST24050002', 'name' => '이영희', 'email' => 'yh.lee@example.com', 'phone' => '010-2345-6789', 'registration_date' => '2025-05-18 07:35:22', 'status' => 'active'],
    ['id' => 8740, 'customer_code' => 'CUST24050003', 'name' => '박지성', 'email' => 'js.park@example.com', 'phone' => '010-3456-7890', 'registration_date' => '2025-05-17 22:18:03', 'status' => 'pending'],
    ['id' => 8739, 'customer_code' => 'CUST24050004', 'name' => '최민준', 'email' => 'mj.choi@example.com', 'phone' => '010-4567-8901', 'registration_date' => '2025-05-17 19:05:44', 'status' => 'active'],
    ['id' => 8738, 'customer_code' => 'CUST24050005', 'name' => '정서연', 'email' => 'sy.jung@example.com', 'phone' => '010-5678-9012', 'registration_date' => '2025-05-17 16:32:21', 'status' => 'active'],
    ['id' => 8737, 'customer_code' => 'CUST24050006', 'name' => '강지원', 'email' => 'jw.kang@example.com', 'phone' => '010-6789-0123', 'registration_date' => '2025-05-17 15:48:56', 'status' => 'pending'],
    ['id' => 8736, 'customer_code' => 'CUST24050007', 'name' => '윤서준', 'email' => 'sj.yoon@example.com', 'phone' => '010-7890-1234', 'registration_date' => '2025-05-17 14:23:05', 'status' => 'active'],
    ['id' => 8735, 'customer_code' => 'CUST24050008', 'name' => '임하은', 'email' => 'he.lim@example.com', 'phone' => '010-8901-2345', 'registration_date' => '2025-05-17 12:11:38', 'status' => 'active'],
    ['id' => 8734, 'customer_code' => 'CUST24050009', 'name' => '한도윤', 'email' => 'dy.han@example.com', 'phone' => '010-9012-3456', 'registration_date' => '2025-05-17 10:54:17', 'status' => 'active'],
    ['id' => 8733, 'customer_code' => 'CUST24050010', 'name' => '오지민', 'email' => 'jm.oh@example.com', 'phone' => '010-0123-4567', 'registration_date' => '2025-05-17 09:29:02', 'status' => 'blocked']
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
                    <li class="breadcrumb-item">고객 관리</li>
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
                    <a href="customer-list.php" class="btn btn-primary"><i class="fas fa-users"></i> 고객 목록</a>
                    <a href="customer-add.php" class="btn btn-success"><i class="fas fa-user-plus"></i> 고객 등록</a>
                    <a href="transaction-add.php" class="btn btn-info"><i class="fas fa-money-bill"></i> 거래 등록</a>
                    <a href="verification.php" class="btn btn-warning"><i class="fas fa-check-circle"></i> 고객 인증 관리</a>
                    <a href="customer-activity-report.php" class="btn btn-secondary"><i class="fas fa-chart-line"></i> 활동 보고서</a>
                </div>
            </div>
        </div>

        <!-- 고객 통계 요약 카드 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo number_format($customerStats['total']); ?></h3>
                        <p>총 고객 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <a href="customer-list.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo number_format($customerStats['new_30_days']); ?></h3>
                        <p>최근 30일 신규 가입</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <a href="customer-list.php?filter=new" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo number_format($customerStats['active']); ?></h3>
                        <p>활성 고객</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <a href="customer-list.php?filter=active" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo number_format($customerStats['vip']); ?></h3>
                        <p>VIP 고객</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <a href="customer-list.php?filter=vip" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 고객 가입 추이 차트 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">월별 고객 가입 추이</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="customerRegistrationChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 고객 상태 분포 차트 -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">고객 상태 분포</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="customerStatusChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 최근 등록 고객 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">최근 등록 고객</h3>
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
                                        <th>고객 코드</th>
                                        <th>이름</th>
                                        <th>이메일</th>
                                        <th>전화번호</th>
                                        <th>등록일</th>
                                        <th>상태</th>
                                        <th>액션</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentCustomers as $customer): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($customer['customer_code']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['registration_date']); ?></td>
                                        <td>
                                            <?php if ($customer['status'] === 'active'): ?>
                                                <span class="badge badge-success">활성</span>
                                            <?php elseif ($customer['status'] === 'pending'): ?>
                                                <span class="badge badge-warning">대기</span>
                                            <?php elseif ($customer['status'] === 'blocked'): ?>
                                                <span class="badge badge-danger">차단</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">비활성</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="customer-details.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="customer-edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-warning">
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
                        <a href="customer-list.php" class="btn btn-sm btn-secondary float-right">모든 고객 보기</a>
                    </div>
                </div>
            </div>

            <!-- 차트 및 통계 컬럼 -->
            <div class="col-md-4">
                <!-- 고객 등급 분포 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">고객 등급 분포</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="customerTierChart" style="min-height: 200px; height: 200px; max-height: 200px; max-width: 100%;"></canvas>
                    </div>
                </div>

                <!-- 고객 지역 분포 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">지역별 고객 분포 (상위 5개)</h3>
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
                                    <th>지역</th>
                                    <th>고객 수</th>
                                    <th style="width: 40%">비율</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // 상위 5개 지역 표시
                                $topRegions = array_slice($regionDistribution, 0, 5);
                                $totalCustomers = $customerStats['total'];
                                foreach ($topRegions as $region): 
                                    $percentage = ($region['count'] / $totalCustomers) * 100;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($region['region']); ?></td>
                                    <td><?php echo number_format($region['count']); ?></td>
                                    <td>
                                        <div class="progress progress-xs">
                                            <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                        <span class="badge bg-primary"><?php echo number_format($percentage, 1); ?>%</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 거래 유형 분포 및 활동 요약 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">거래 유형 분포</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="transactionTypeChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">고객 활동 요약</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">오늘 구매 건수</span>
                                        <span class="info-box-number"><?php echo number_format(rand(500, 2000)); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon"><i class="fas fa-trophy"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">오늘 당첨 건수</span>
                                        <span class="info-box-number"><?php echo number_format(rand(100, 500)); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon"><i class="fas fa-user-check"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">오늘 로그인 건수</span>
                                        <span class="info-box-number"><?php echo number_format(rand(1000, 3000)); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">인증 대기 고객</span>
                                        <span class="info-box-number"><?php echo number_format($customerStats['pending_verification']); ?></span>
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
    // 월별 고객 가입 추이 차트
    const registrationCtx = document.getElementById('customerRegistrationChart').getContext('2d');
    const registrationChart = new Chart(registrationCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthlyRegistrations, 'label')); ?>,
            datasets: [{
                label: '신규 가입 고객 수',
                backgroundColor: 'rgba(60,141,188,0.2)',
                borderColor: 'rgba(60,141,188,1)',
                pointRadius: 3,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: <?php echo json_encode(array_column($monthlyRegistrations, 'count')); ?>
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

    // 고객 상태 분포 차트
    const statusCtx = document.getElementById('customerStatusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['활성', '비활성', '차단됨', '인증 대기'],
            datasets: [{
                data: [
                    <?php echo $customerStats['active']; ?>,
                    <?php echo $customerStats['inactive']; ?>,
                    <?php echo $customerStats['blocked']; ?>,
                    <?php echo $customerStats['pending_verification']; ?>
                ],
                backgroundColor: ['#00a65a', '#f39c12', '#f56954', '#00c0ef']
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true
        }
    });

    // 고객 등급 분포 차트
    const tierCtx = document.getElementById('customerTierChart').getContext('2d');
    new Chart(tierCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($customerTiers, 'tier')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($customerTiers, 'count')); ?>,
                backgroundColor: ['#f56954', '#f39c12', '#00a65a', '#00c0ef', '#3c8dbc']
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true
        }
    });

    // 거래 유형 분포 차트
    const transactionCtx = document.getElementById('transactionTypeChart').getContext('2d');
    new Chart(transactionCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($transactionTypes, 'type')); ?>,
            datasets: [{
                label: '거래 건수',
                data: <?php echo json_encode(array_column($transactionTypes, 'count')); ?>,
                backgroundColor: [
                    'rgba(60, 141, 188, 0.8)',
                    'rgba(0, 166, 90, 0.8)',
                    'rgba(243, 156, 18, 0.8)',
                    'rgba(245, 105, 84, 0.8)',
                    'rgba(210, 214, 222, 0.8)'
                ],
                borderColor: [
                    'rgba(60, 141, 188, 1)',
                    'rgba(0, 166, 90, 1)',
                    'rgba(243, 156, 18, 1)',
                    'rgba(245, 105, 84, 1)',
                    'rgba(210, 214, 222, 1)'
                ],
                borderWidth: 1
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
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
