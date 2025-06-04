<?php
/**
 * 재무 관리 - 예산 관리
 * 
 * 이 페이지는 예산 기간, 할당 및 활용 현황을 보여주는 메인 페이지입니다.
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = get_db_connection();

// 1. 현재 활성 예산 기간 정보
$currentPeriodSql = "SELECT * FROM budget_periods 
                    WHERE status = 'active' 
                    AND start_date <= CURDATE() 
                    AND end_date >= CURDATE()
                    ORDER BY start_date DESC
                    LIMIT 1";

$currentPeriodResult = $db->query($currentPeriodSql);
$currentPeriod = $currentPeriodResult->fetch(PDO::FETCH_ASSOC);

// 예산 기간이 없는 경우 가장 최근 기간을 가져옴
if (!$currentPeriod) {
    $latestPeriodSql = "SELECT * FROM budget_periods 
                        ORDER BY end_date DESC
                        LIMIT 1";
    $latestPeriodResult = $db->query($latestPeriodSql);
    $currentPeriod = $latestPeriodResult->fetch(PDO::FETCH_ASSOC);
}

$periodId = $currentPeriod ? $currentPeriod['id'] : 0;

// 2. 예산 할당 내역 - 가장 기본적인 쿼리로 단순화
$budgetAllocationsSql = "SELECT * 
                         FROM budget_allocations
                         WHERE period_id = $periodId";

$budgetAllocationsResult = $db->query($budgetAllocationsSql);
$budgetAllocations = [];
$totalAllocated = 0;

// 테이블 구조 확인을 위한 로그 파일 생성
if ($budgetAllocationsResult && $row = $budgetAllocationsResult->fetch(PDO::FETCH_ASSOC)) {
    // 첫 번째 행의 컬럼명을 로그에 기록
    $logContent = "[" . date('Y-m-d H:i:s') . "] budget_allocations 테이블 구조 확인\n";
    $logContent .= "컬럼명: " . implode(', ', array_keys($row)) . "\n\n";
    $logContent .= "샘플 데이터:\n";
    $logContent .= print_r($row, true);
    
    file_put_contents('C:/xampp/htdocs/server/logs/budget_table_structure.log', $logContent);
    
    // 데이터 처리 계속
    $budgetAllocations[] = $row;
    $totalAllocated += isset($row['allocated_amount']) ? $row['allocated_amount'] : 0;
}

while ($row = $budgetAllocationsResult->fetch(PDO::FETCH_ASSOC)) {
    // funds 테이블에서 추가 정보 가져오기 - 안전하게 처리
    $fundId = 0;
    foreach (['fund', 'fund_id', 'funds_id', 'fund_type_id'] as $possibleColumn) {
        if (isset($row[$possibleColumn]) && !empty($row[$possibleColumn])) {
            $fundId = $row[$possibleColumn];
            break;
        }
    }
    
    if ($fundId > 0) {
        $fundSql = "SELECT fund_name, fund_code, fund_type FROM funds WHERE id = $fundId";
        $fundResult = $db->query($fundSql);
        if ($fundResult) {
            $fundData = $fundResult->fetch(PDO::FETCH_ASSOC);
            if ($fundData) {
                $row = array_merge($row, $fundData);
            }
        }
    }
    
    $budgetAllocations[] = $row;
    $totalAllocated += isset($row['allocated_amount']) ? $row['allocated_amount'] : 0;
}

// 3. 부서별 예산 할당 요약
// 3. 부서별 예산 할당 요약 - 임시 비활성화 (컬럼 확인 후 재작성 필요)
$departmentSummarySql = "";
$departmentSummary = [];
$departments = [];
$allocatedData = [];
$utilizedData = [];

// 부서별 데이터를 PHP에서 처리
foreach ($budgetAllocations as $allocation) {
    $dept = isset($allocation['dept_id']) ? $allocation['dept_id'] : 
           (isset($allocation['department_id']) ? $allocation['department_id'] : 
            (isset($allocation['dept']) ? $allocation['dept'] : 'Unknown'));
            
    if (!isset($departmentSummary[$dept])) {
        $departmentSummary[$dept] = [
            'department' => $dept,
            'total_allocated' => 0,
            'total_utilized' => 0
        ];
        $departments[] = $dept;
    }
    
    $departmentSummary[$dept]['total_allocated'] += isset($allocation['allocated_amount']) ? 
                                                  $allocation['allocated_amount'] : 0;
    $departmentSummary[$dept]['total_utilized'] += isset($allocation['utilized_amount']) ? 
                                                 $allocation['utilized_amount'] : 0;
}

// 차트 데이터 준비
foreach ($departmentSummary as $summary) {
    $allocatedData[] = $summary['total_allocated'];
    $utilizedData[] = $summary['total_utilized'];
}

// 배열 형태로 변환
$departmentSummary = array_values($departmentSummary);

// 4. 카테고리별 예산 할당 요약
// 4. 카테고리별 예산 할당 요약 - 임시 비활성화 (컬럼 확인 후 재작성 필요)
$categorySummarySql = "";
$categorySummary = [];
$categories = [];
$categoryAllocated = [];
$categoryUtilized = [];

// 카테고리별 데이터를 PHP에서 처리
foreach ($budgetAllocations as $allocation) {
    $cat = isset($allocation['category_id']) ? $allocation['category_id'] : 
          (isset($allocation['category']) ? $allocation['category'] : 
           (isset($allocation['cat_id']) ? $allocation['cat_id'] : 'Unknown'));
           
    if (!isset($categorySummary[$cat])) {
        $categorySummary[$cat] = [
            'category' => $cat,
            'total_allocated' => 0,
            'total_utilized' => 0
        ];
        $categories[] = $cat;
    }
    
    $categorySummary[$cat]['total_allocated'] += isset($allocation['allocated_amount']) ? 
                                               $allocation['allocated_amount'] : 0;
    $categorySummary[$cat]['total_utilized'] += isset($allocation['utilized_amount']) ? 
                                              $allocation['utilized_amount'] : 0;
}

// 차트 데이터 준비
foreach ($categorySummary as $summary) {
    $categoryAllocated[] = $summary['total_allocated'];
    $categoryUtilized[] = $summary['total_utilized'];
}

// 배열 형태로 변환
$categorySummary = array_values($categorySummary);

// 5. 예산 기간 목록 (최근 5개)
$recentPeriodsSql = "SELECT * FROM budget_periods 
                     ORDER BY start_date DESC
                     LIMIT 5";

$recentPeriodsResult = $db->query($recentPeriodsSql);
$recentPeriods = [];

while ($row = $recentPeriodsResult->fetch(PDO::FETCH_ASSOC)) {
    $recentPeriods[] = $row;
}

// 페이지 제목 설정
$pageTitle = "예산 관리";
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
                    <li class="breadcrumb-item active">예산 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 현재 예산 기간 정보 -->
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            현재 예산 기간
                        </h3>
                        <div class="card-tools">
                            <a href="budget-period-add.php" class="btn btn-tool btn-sm">
                                <i class="fas fa-plus"></i> 신규 기간 추가
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($currentPeriod): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">예산 기간 이름:</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars(isset($currentPeriod['period_name']) ? $currentPeriod['period_name'] : 'N/A'); ?></dd>
                                        
                                        <dt class="col-sm-4">시작일:</dt>
                                        <dd class="col-sm-8"><?php echo isset($currentPeriod['start_date']) ? date('Y-m-d', strtotime($currentPeriod['start_date'])) : 'N/A'; ?></dd>
                                        
                                        <dt class="col-sm-4">종료일:</dt>
                                        <dd class="col-sm-8"><?php echo isset($currentPeriod['end_date']) ? date('Y-m-d', strtotime($currentPeriod['end_date'])) : 'N/A'; ?></dd>
                                        
                                        <dt class="col-sm-4">상태:</dt>
                                        <dd class="col-sm-8">
                                            <?php 
                                            $status = isset($currentPeriod['status']) ? $currentPeriod['status'] : 'unknown';
                                            $statusClass = '';
                                            switch ($status) {
                                                case 'active':
                                                    $statusClass = 'badge badge-success';
                                                    $statusText = '활성';
                                                    break;
                                                case 'pending':
                                                    $statusClass = 'badge badge-warning';
                                                    $statusText = '대기중';
                                                    break;
                                                case 'closed':
                                                    $statusClass = 'badge badge-danger';
                                                    $statusText = '마감됨';
                                                    break;
                                                default:
                                                    $statusClass = 'badge badge-secondary';
                                                    $statusText = $status;
                                            }
                                            echo "<span class=\"$statusClass\">$statusText</span>";
                                            ?>
                                        </dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">총 예산 금액:</dt>
                                        <dd class="col-sm-8"><?php echo number_format(isset($currentPeriod['total_budget']) ? $currentPeriod['total_budget'] : 0, 0); ?> NPR</dd>
                                        
                                        <dt class="col-sm-4">할당된 금액:</dt>
                                        <dd class="col-sm-8"><?php echo number_format($totalAllocated, 0); ?> NPR</dd>
                                        
                                        <dt class="col-sm-4">남은 할당 가능액:</dt>
                                        <dd class="col-sm-8"><?php echo number_format((isset($currentPeriod['total_budget']) ? $currentPeriod['total_budget'] : 0) - $totalAllocated, 0); ?> NPR</dd>
                                        
                                        <dt class="col-sm-4">작업:</dt>
                                        <dd class="col-sm-8">
                                            <div class="btn-group">
                                                <a href="budget-period-details.php?id=<?php echo isset($currentPeriod['id']) ? $currentPeriod['id'] : 0; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> 상세 보기
                                                </a>
                                                <a href="budget-period-edit.php?id=<?php echo isset($currentPeriod['id']) ? $currentPeriod['id'] : 0; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i> 수정
                                                </a>
                                                <a href="budget-allocation-manage.php?period_id=<?php echo isset($currentPeriod['id']) ? $currentPeriod['id'] : 0; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-money-bill-wave"></i> 예산 할당
                                                </a>
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 현재 활성 상태인 예산 기간이 없습니다.
                                <a href="budget-period-add.php" class="btn btn-sm btn-warning ml-3">
                                    <i class="fas fa-plus"></i> 새 예산 기간 추가
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 예산 관리 주요 메뉴 -->
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="card-title">예산 기간 관리</h3>
                    </div>
                    <div class="card-body">
                        <p>회계 연도, 분기, 월별 등 예산 기간을 설정하고 관리합니다.</p>
                        <ul>
                            <li>예산 기간 추가 및 수정</li>
                            <li>예산 기간 상태 관리</li>
                            <li>전체 예산 금액 설정</li>
                        </ul>
                    </div>
                    <div class="card-footer">
                        <a href="budget-periods.php" class="btn btn-primary btn-block">
                            <i class="fas fa-calendar-alt mr-1"></i> 예산 기간 관리 이동
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success">
                        <h3 class="card-title">예산 할당 관리</h3>
                    </div>
                    <div class="card-body">
                        <p>각 예산 기간의 부서/카테고리별 예산 할당을 관리합니다.</p>
                        <ul>
                            <li>부서별 예산 할당</li>
                            <li>카테고리별 예산 할당</li>
                            <li>할당 내역 조회 및 수정</li>
                        </ul>
                    </div>
                    <div class="card-footer">
                        <a href="budget-allocations.php" class="btn btn-success btn-block">
                            <i class="fas fa-money-bill-wave mr-1"></i> 예산 할당 관리 이동
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h3 class="card-title">예산 활용 현황</h3>
                    </div>
                    <div class="card-body">
                        <p>할당된 예산의 사용 현황을 모니터링합니다.</p>
                        <ul>
                            <li>예산 사용률 확인</li>
                            <li>초과 지출 알림</li>
                            <li>예산 조정 요청 관리</li>
                        </ul>
                    </div>
                    <div class="card-footer">
                        <a href="budget-utilization.php" class="btn btn-warning btn-block">
                            <i class="fas fa-chart-pie mr-1"></i> 예산 활용 현황 이동
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($currentPeriod): ?>
        <!-- 부서별 예산 할당 및 활용 차트 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-bar mr-1"></i>
                            부서별 예산 할당
                        </h3>
                    </div>
                    <div class="card-body">
                        <canvas id="departmentBudgetChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-pie mr-1"></i>
                            카테고리별 예산 할당
                        </h3>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryBudgetChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 최근 예산 기간 목록 -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history mr-1"></i>
                            최근 예산 기간
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>기간명</th>
                                        <th>시작일</th>
                                        <th>종료일</th>
                                        <th class="text-right">총 예산</th>
                                        <th>상태</th>
                                        <th>작업</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPeriods as $period): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(isset($period['period_name']) ? $period['period_name'] : 'N/A'); ?></td>
                                            <td><?php echo isset($period['start_date']) ? date('Y-m-d', strtotime($period['start_date'])) : 'N/A'; ?></td>
                                            <td><?php echo isset($period['end_date']) ? date('Y-m-d', strtotime($period['end_date'])) : 'N/A'; ?></td>
                                            <td class="text-right"><?php echo number_format(isset($period['total_budget']) ? $period['total_budget'] : 0, 0); ?> NPR</td>
                                            <td>
                                                <?php 
                                                $status = isset($period['status']) ? $period['status'] : 'unknown';
                                                $statusClass = '';
                                                switch ($status) {
                                                    case 'active':
                                                        $statusClass = 'badge badge-success';
                                                        $statusText = '활성';
                                                        break;
                                                    case 'pending':
                                                        $statusClass = 'badge badge-warning';
                                                        $statusText = '대기중';
                                                        break;
                                                    case 'closed':
                                                        $statusClass = 'badge badge-danger';
                                                        $statusText = '마감됨';
                                                        break;
                                                    default:
                                                        $statusClass = 'badge badge-secondary';
                                                        $statusText = $status;
                                                }
                                                echo "<span class=\"$statusClass\">$statusText</span>";
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="budget-period-details.php?id=<?php echo isset($period['id']) ? $period['id'] : 0; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="budget-allocation-manage.php?period_id=<?php echo isset($period['id']) ? $period['id'] : 0; ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="budget-periods.php" class="btn btn-primary">모든 예산 기간 보기</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($currentPeriod): ?>
    // 부서별 예산 차트
    var departmentCtx = document.getElementById('departmentBudgetChart').getContext('2d');
    var departmentChart = new Chart(departmentCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($departments); ?>,
            datasets: [
                {
                    label: '할당된 예산',
                    backgroundColor: 'rgba(60, 141, 188, 0.7)',
                    borderColor: 'rgba(60, 141, 188, 1)',
                    borderWidth: 1,
                    data: <?php echo json_encode($allocatedData); ?>
                },
                {
                    label: '사용된 예산',
                    backgroundColor: 'rgba(210, 214, 222, 0.7)',
                    borderColor: 'rgba(210, 214, 222, 1)',
                    borderWidth: 1,
                    data: <?php echo json_encode($utilizedData); ?>
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                xAxes: [{
                    gridLines: {
                        display: false
                    }
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        callback: function(value) {
                            return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' NPR';
                        }
                    }
                }]
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        var label = data.datasets[tooltipItem.datasetIndex].label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += tooltipItem.yLabel.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' NPR';
                        return label;
                    }
                }
            }
        }
    });
    
    // 카테고리별 예산 차트
    var categoryCtx = document.getElementById('categoryBudgetChart').getContext('2d');
    var categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($categories); ?>,
            datasets: [{
                data: <?php echo json_encode($categoryAllocated); ?>,
                backgroundColor: [
                    'rgba(60, 141, 188, 0.7)',
                    'rgba(210, 214, 222, 0.7)',
                    'rgba(40, 167, 69, 0.7)',
                    'rgba(220, 53, 69, 0.7)',
                    'rgba(23, 162, 184, 0.7)',
                    'rgba(253, 126, 20, 0.7)',
                    'rgba(253, 253, 0, 0.7)',
                    'rgba(102, 16, 242, 0.7)'
                ],
                borderColor: [
                    'rgba(60, 141, 188, 1)',
                    'rgba(210, 214, 222, 1)',
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)',
                    'rgba(23, 162, 184, 1)',
                    'rgba(253, 126, 20, 1)',
                    'rgba(253, 253, 0, 1)',
                    'rgba(102, 16, 242, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'right'
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        var label = data.labels[tooltipItem.index] || '';
                        if (label) {
                            label += ': ';
                        }
                        var value = data.datasets[0].data[tooltipItem.index];
                        label += value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' NPR';
                        return label;
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>
