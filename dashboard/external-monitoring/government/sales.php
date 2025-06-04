<?php
/**
 * 판매액 상세 조회 페이지
 * 회차별 판매액 현황과 상세 정보를 제공합니다.
 */

// 공통 파일 포함
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/sales_functions.php';

// 페이지 변수 설정
$pageTitle = "판매액 현황";
$currentSection = "government";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = getDBConnection();

// 필터 변수
$startDraw = isset($_GET['start_draw']) ? intval($_GET['start_draw']) : 0;
$endDraw = isset($_GET['end_draw']) ? intval($_GET['end_draw']) : 0;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$searchKeyword = isset($_GET['search']) ? $_GET['search'] : '';

// 페이지네이션 변수
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
$recordsPerPage = 10;
$offset = ($currentPage - 1) * $recordsPerPage;

// 최근 회차 정보 가져오기
$latestDrawStmt = $db->prepare("
    SELECT draw_code, draw_date, product_id 
    FROM draws 
    ORDER BY draw_date DESC 
    LIMIT 1
");
$latestDrawStmt->execute();
$latestDraw = $latestDrawStmt->fetch(PDO::FETCH_ASSOC);
$latestDrawNumber = $latestDraw ? $latestDraw['draw_code'] : 0;

// 필터 배열 구성
$filters = [
    'start_draw' => $startDraw,
    'end_draw' => $endDraw,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'search' => $searchKeyword,
    'offset' => $offset,
    'limit' => $recordsPerPage
];

// 판매 데이터 가져오기 (판매 관리와 동일한 함수 사용)
$allSalesData = getSalesDataByDraw($db, null, $filters);

// 페이지네이션을 위한 데이터 분할
$totalRecords = count($allSalesData);
$totalPages = ceil($totalRecords / $recordsPerPage);
$salesData = array_slice($allSalesData, $offset, $recordsPerPage);

// 전체 판매액 합계 (가상 데이터 - 실제로는 DB에서 가져와야 함)
$totalSalesAmount = 0;
$totalOnlineSalesAmount = 0;
$totalOfflineSalesAmount = 0;

foreach ($salesData as $row) {
    $totalSalesAmount += $row['total_sales_amount'];
    $totalOnlineSalesAmount += $row['online_sales_amount'];
    $totalOfflineSalesAmount += $row['offline_sales_amount'];
}

// 상세 정보 모달용 데이터 (AJAX로 가져올 경우를 위한 예시)
$drawId = isset($_GET['draw_id']) ? intval($_GET['draw_id']) : 0;
$drawDetails = [];

if ($drawId > 0) {
    $detailsStmt = $db->prepare("
        SELECT 
            d.id AS draw_id,
            d.draw_code,
            d.draw_date,
            d.product_id,
            p.name AS product_name,
            s.transaction_date as sales_date,
            s.payment_method as sales_channel,
            s.total_amount as sales_amount,
            1 as store_count,
            s.ticket_quantity as ticket_count
        FROM 
            draws d
        JOIN 
            lottery_products p ON d.product_id = p.id
        LEFT JOIN 
            sales_transactions s ON d.product_id = s.lottery_type_id
        WHERE 
            d.id = :draw_id
        ORDER BY 
            s.sales_date
    ");
    $detailsStmt->bindParam(':draw_id', $drawId, PDO::PARAM_INT);
    $detailsStmt->execute();
    $drawDetails = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);
}

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
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/index.php">외부접속감시</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/government/index.php">정부 모니터링</a></li>
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
        <!-- 요약 정보 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo formatCurrency($totalSalesAmount); ?></h3>
                        <p>총 판매액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo formatCurrency($totalOnlineSalesAmount); ?></h3>
                        <p>온라인 판매액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-globe"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo formatCurrency($totalOfflineSalesAmount); ?></h3>
                        <p>오프라인 판매액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo $latestDrawNumber; ?>회</h3>
                        <p>최근 회차</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
        </div>
        <!-- /.row -->

        <!-- 검색 및 필터 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">검색 및 필터</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <form id="salesFilterForm" method="GET" action="">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="start_draw">시작 회차</label>
                                <input type="number" class="form-control" id="start_draw" name="start_draw" value="<?php echo $startDraw; ?>" placeholder="시작 회차">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="end_draw">종료 회차</label>
                                <input type="number" class="form-control" id="end_draw" name="end_draw" value="<?php echo $endDraw; ?>" placeholder="종료 회차">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="start_date">시작 날짜</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="end_date">종료 날짜</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="search">검색어</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($searchKeyword); ?>" placeholder="검색어 입력">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> 검색
                            </button>
                            <button type="button" class="btn btn-default" onclick="window.location.href='sales.php'">
                                <i class="fas fa-redo"></i> 초기화
                            </button>
                            <button type="button" class="btn btn-success" onclick="exportToExcel()">
                                <i class="fas fa-file-excel"></i> 엑셀 다운로드
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->

        <!-- 판매액 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">회차별 판매액 목록</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>회차</th>
                            <th>추첨일</th>
                            <th>복권종류</th>
                            <th>총 판매액</th>
                            <th>온라인 판매액</th>
                            <th>오프라인 판매액</th>
                            <th>상세보기</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($salesData)): ?>
                            <tr>
                                <td colspan="7" class="text-center">데이터가 없습니다.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($salesData as $row): ?>
                                <tr>
                                    <td><?php echo $row['draw_code']; ?>회</td>
                                    <td><?php echo date('Y-m-d', strtotime($row['draw_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td><?php echo formatCurrency($row['total_sales_amount']); ?></td>
                                    <td><?php echo formatCurrency($row['online_sales_amount']); ?></td>
                                    <td><?php echo formatCurrency($row['offline_sales_amount']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" onclick="showSalesDetails(<?php echo $row['draw_id']; ?>)">
                                            <i class="fas fa-search-plus"></i> 상세
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- /.card-body -->
            <div class="card-footer clearfix">
                <!-- 페이지네이션 -->
                <ul class="pagination pagination-sm m-0 float-right">
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=1<?php echo getQueryParams(['page']); ?>">&laquo;</a></li>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo getQueryParams(['page']); ?>">&lsaquo;</a></li>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $startPage + 4);
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo getQueryParams(['page']); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo getQueryParams(['page']); ?>">&rsaquo;</a></li>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo getQueryParams(['page']); ?>">&raquo;</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <!-- /.card -->

        <!-- 판매액 차트 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">판매액 추이</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <div class="chart">
                    <canvas id="salesChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                </div>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

<!-- 판매액 상세 정보 모달 -->
<div class="modal fade" id="salesDetailsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">판매액 상세 정보</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- 상세 정보가 AJAX로 로드됩니다 -->
                <div id="salesDetailsContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>로딩 중...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary" onclick="printSalesDetails()">인쇄</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<script>
// 판매액 차트 데이터
document.addEventListener('DOMContentLoaded', function() {
    // 차트 데이터 준비
    const drawNumbers = [];
    const totalSalesData = [];
    const onlineSalesData = [];
    const offlineSalesData = [];
    
    <?php 
    // 최근 6개 회차만 사용하여 차트 데이터 생성 (역순으로 표시하기 위해 역순으로 저장)
    $chartData = array_slice($salesData, 0, 6);
    $chartData = array_reverse($chartData);
    foreach ($chartData as $row): 
    ?>
        drawNumbers.push('<?php echo $row['draw_code']; ?>회');
        totalSalesData.push(<?php echo $row['total_sales_amount'] / 100000000; ?>); // 억 단위로 변환
        onlineSalesData.push(<?php echo $row['online_sales_amount'] / 100000000; ?>);
        offlineSalesData.push(<?php echo $row['offline_sales_amount'] / 100000000; ?>);
    <?php endforeach; ?>
    
    // 판매액 차트 생성
    var salesChartCanvas = document.getElementById('salesChart').getContext('2d');
    var salesChartData = {
        labels: drawNumbers,
        datasets: [
            {
                label: '총 판매액 (억원)',
                backgroundColor: 'rgba(60,141,188,0.9)',
                borderColor: 'rgba(60,141,188,0.8)',
                pointRadius: 3,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: totalSalesData
            },
            {
                label: '온라인 판매액 (억원)',
                backgroundColor: 'rgba(40,167,69,0.9)',
                borderColor: 'rgba(40,167,69,0.8)',
                pointRadius: 3,
                pointColor: '#28a745',
                pointStrokeColor: 'rgba(40,167,69,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(40,167,69,1)',
                data: onlineSalesData
            },
            {
                label: '오프라인 판매액 (억원)',
                backgroundColor: 'rgba(255,193,7,0.9)',
                borderColor: 'rgba(255,193,7,0.8)',
                pointRadius: 3,
                pointColor: '#ffc107',
                pointStrokeColor: 'rgba(255,193,7,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(255,193,7,1)',
                data: offlineSalesData
            }
        ]
    };

    var salesChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            xAxes: [{
                gridLines: {
                    display: false
                }
            }],
            yAxes: [{
                gridLines: {
                    display: true
                },
                ticks: {
                    beginAtZero: false
                }
            }]
        }
    };

    var salesChart = new Chart(salesChartCanvas, {
        type: 'line',
        data: salesChartData,
        options: salesChartOptions
    });
});

// 판매액 상세 정보 표시
function showSalesDetails(drawId) {
    // AJAX로 상세 정보 가져오기
    $('#salesDetailsModal').modal('show');
    
    // 여기에서 AJAX 요청을 통해 상세 정보를 가져옵니다.
    // 실제 구현시에는 아래 코드를 사용하세요.
    /*
    $.ajax({
        url: 'get_sales_details.php',
        type: 'GET',
        data: {
            draw_id: drawId
        },
        success: function(response) {
            $('#salesDetailsContent').html(response);
        },
        error: function() {
            $('#salesDetailsContent').html('<div class="alert alert-danger">데이터를 불러오는 데 실패했습니다.</div>');
        }
    });
    */
    
    // 여기서는 임시로 상세 정보를 표시합니다.
    setTimeout(function() {
        $('#salesDetailsContent').html(`
            <div class="row">
                <div class="col-md-12">
                    <h5>회차 정보</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>회차</th>
                            <td id="detailDrawNumber">${drawId}회</td>
                            <th>추첨일</th>
                            <td id="detailDrawDate">2022-01-01</td>
                        </tr>
                        <tr>
                            <th>복권종류</th>
                            <td id="detailProductName">로또 6/45</td>
                            <th>총 판매액</th>
                            <td id="detailTotalSales">85,456,324,000원</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <h5>판매 채널별 현황</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>채널</th>
                                <th>판매액</th>
                                <th>판매 비율</th>
                                <th>티켓 수</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>온라인</td>
                                <td>34,182,529,600원</td>
                                <td>40%</td>
                                <td>3,418,252개</td>
                            </tr>
                            <tr>
                                <td>오프라인</td>
                                <td>51,273,794,400원</td>
                                <td>60%</td>
                                <td>5,127,379개</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <h5>일자별 판매 현황</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>날짜</th>
                                <th>판매액</th>
                                <th>온라인 판매액</th>
                                <th>오프라인 판매액</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>2022-12-25</td>
                                <td>12,818,448,600원</td>
                                <td>5,127,379,440원</td>
                                <td>7,691,069,160원</td>
                            </tr>
                            <tr>
                                <td>2022-12-26</td>
                                <td>17,091,264,800원</td>
                                <td>6,836,505,920원</td>
                                <td>10,254,758,880원</td>
                            </tr>
                            <tr>
                                <td>2022-12-27</td>
                                <td>21,364,081,000원</td>
                                <td>8,545,632,400원</td>
                                <td>12,818,448,600원</td>
                            </tr>
                            <tr>
                                <td>2022-12-28</td>
                                <td>17,091,264,800원</td>
                                <td>6,836,505,920원</td>
                                <td>10,254,758,880원</td>
                            </tr>
                            <tr>
                                <td>2022-12-29</td>
                                <td>8,545,632,400원</td>
                                <td>3,418,252,960원</td>
                                <td>5,127,379,440원</td>
                            </tr>
                            <tr>
                                <td>2022-12-30</td>
                                <td>8,545,632,400원</td>
                                <td>3,418,252,960원</td>
                                <td>5,127,379,440원</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="chart-responsive">
                        <canvas id="detailSalesChannelChart" height="200"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-responsive">
                        <canvas id="detailDailySalesChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        `);
        
        // 상세 정보 차트 그리기
        drawDetailCharts();
    }, 1000);
}

// 상세 정보 차트 그리기
function drawDetailCharts() {
    // 채널별 판매액 차트
    var channelChartCanvas = document.getElementById('detailSalesChannelChart').getContext('2d');
    var channelChartData = {
        labels: ['온라인', '오프라인'],
        datasets: [
            {
                data: [40, 60],
                backgroundColor: ['#28a745', '#ffc107']
            }
        ]
    };

    var channelChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        legend: {
            position: 'bottom'
        },
        title: {
            display: true,
            text: '채널별 판매액 비율'
        }
    };

    var channelChart = new Chart(channelChartCanvas, {
        type: 'pie',
        data: channelChartData,
        options: channelChartOptions
    });
    
    // 일자별 판매액 차트
    var dailyChartCanvas = document.getElementById('detailDailySalesChart').getContext('2d');
    var dailyChartData = {
        labels: ['12/25', '12/26', '12/27', '12/28', '12/29', '12/30'],
        datasets: [
            {
                label: '일자별 판매액 (억원)',
                backgroundColor: 'rgba(60,141,188,0.9)',
                borderColor: 'rgba(60,141,188,0.8)',
                pointRadius: 3,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: [128.18, 170.91, 213.64, 170.91, 85.46, 85.46]
            }
        ]
    };

    var dailyChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        title: {
            display: true,
            text: '일자별 판매액 추이'
        },
        scales: {
            xAxes: [{
                gridLines: {
                    display: false
                }
            }],
            yAxes: [{
                gridLines: {
                    display: true
                },
                ticks: {
                    beginAtZero: false
                }
            }]
        }
    };

    var dailyChart = new Chart(dailyChartCanvas, {
        type: 'line',
        data: dailyChartData,
        options: dailyChartOptions
    });
}

// 판매액 상세 정보 인쇄
function printSalesDetails() {
    var printContents = document.getElementById('salesDetailsContent').innerHTML;
    var originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div class="container mt-3">
            <h2 class="text-center mb-4">판매액 상세 정보</h2>
            ${printContents}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
    location.reload();
}

// 엑셀 다운로드
function exportToExcel() {
    // 여기에 엑셀 다운로드 기능 구현
    alert('판매액 데이터를 엑셀로 다운로드합니다.');
    // 실제 구현시에는 아래와 같이 서버에 요청하여 엑셀 파일을 다운로드합니다.
    // window.location.href = 'export_sales.php' + window.location.search;
}

// 쿼리 파라미터 생성
function getQueryParams(excludeKeys = []) {
    var urlParams = new URLSearchParams(window.location.search);
    var queryParams = '';
    
    urlParams.forEach(function(value, key) {
        if (!excludeKeys.includes(key)) {
            queryParams += '&' + key + '=' + value;
        }
    });
    
    return queryParams;
}
</script>

<?php
// 페이지네이션을 위한 쿼리 파라미터 생성 함수
function getQueryParams($excludeKeys = []) {
    $queryParams = '';
    
    foreach ($_GET as $key => $value) {
        if (!in_array($key, $excludeKeys)) {
            $queryParams .= '&' . urlencode($key) . '=' . urlencode($value);
        }
    }
    
    return $queryParams;
}
?>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
