<?php
/**
 * 당첨금 상세 조회 페이지
 * 회차별 당첨금 현황과 상세 정보를 제공합니다.
 */

// 공통 파일 포함
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "당첨금 현황";
$currentSection = "government";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = getDBConnection();

// 필터 변수
$startDraw = isset($_GET['start_draw']) ? intval($_GET['start_draw']) : 0;
$endDraw = isset($_GET['end_draw']) ? intval($_GET['end_draw']) : 0;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$prizeLevel = isset($_GET['prize_level']) ? intval($_GET['prize_level']) : 0;
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

// 회차별 당첨금 목록 쿼리 구성
$query = "
    SELECT 
        d.id AS draw_id,
        d.draw_code,
        d.draw_date,
        d.product_id,
        p.name AS product_name,
        COALESCE(w1.prize_amount, 0) AS prize1_amount,
        COALESCE(w1.winner_count, 0) AS prize1_winners,
        COALESCE(w2.prize_amount, 0) AS prize2_amount,
        COALESCE(w2.winner_count, 0) AS prize2_winners,
        COALESCE(w3.prize_amount, 0) AS prize3_amount,
        COALESCE(w3.winner_count, 0) AS prize3_winners,
        COALESCE(w4.prize_amount, 0) AS prize4_amount,
        COALESCE(w4.winner_count, 0) AS prize4_winners,
        COALESCE(w5.prize_amount, 0) AS prize5_amount,
        COALESCE(w5.winner_count, 0) AS prize5_winners,
        (COALESCE(w1.prize_amount, 0) + COALESCE(w2.prize_amount, 0) + COALESCE(w3.prize_amount, 0) + 
         COALESCE(w4.prize_amount, 0) + COALESCE(w5.prize_amount, 0)) AS total_prize_amount
    FROM 
        draws d
    LEFT JOIN 
        lottery_products p ON d.product_id = p.id
    LEFT JOIN (
        SELECT 
            draw_id, 
            COUNT(*) AS winner_count,
            SUM(prize_amount) AS prize_amount
        FROM 
            winnings 
        WHERE 
            prize_tier = 1 
        GROUP BY 
            draw_id
    ) w1 ON d.id = w1.draw_id
    LEFT JOIN (
        SELECT 
            draw_id, 
            COUNT(*) AS winner_count,
            SUM(prize_amount) AS prize_amount
        FROM 
            winnings 
        WHERE 
            prize_tier = 2 
        GROUP BY 
            draw_id
    ) w2 ON d.id = w2.draw_id
    LEFT JOIN (
        SELECT 
            draw_id, 
            COUNT(*) AS winner_count,
            SUM(prize_amount) AS prize_amount
        FROM 
            winnings 
        WHERE 
            prize_tier = 3 
        GROUP BY 
            draw_id
    ) w3 ON d.id = w3.draw_id
    LEFT JOIN (
        SELECT 
            draw_id, 
            COUNT(*) AS winner_count,
            SUM(prize_amount) AS prize_amount
        FROM 
            winnings 
        WHERE 
            prize_tier = 4 
        GROUP BY 
            draw_id
    ) w4 ON d.id = w4.draw_id
    LEFT JOIN (
        SELECT 
            draw_id, 
            COUNT(*) AS winner_count,
            SUM(prize_amount) AS prize_amount
        FROM 
            winnings 
        WHERE 
            prize_tier = 5 
        GROUP BY 
            draw_id
    ) w5 ON d.id = w5.draw_id
    WHERE 1=1
";

// 검색 조건 적용
$params = [];

if ($startDraw > 0) {
    $query .= " AND d.draw_code >= :start_draw";
    $params[':start_draw'] = $startDraw;
}

if ($endDraw > 0) {
    $query .= " AND d.draw_code <= :end_draw";
    $params[':end_draw'] = $endDraw;
}

if (!empty($startDate)) {
    $query .= " AND d.draw_date >= :start_date";
    $params[':start_date'] = $startDate;
}

if (!empty($endDate)) {
    $query .= " AND d.draw_date <= :end_date";
    $params[':end_date'] = $endDate;
}

if ($prizeLevel > 0) {
    switch ($prizeLevel) {
        case 1:
            $query .= " AND COALESCE(w1.prize_amount, 0) > 0";
            break;
        case 2:
            $query .= " AND COALESCE(w2.prize_amount, 0) > 0";
            break;
        case 3:
            $query .= " AND COALESCE(w3.prize_amount, 0) > 0";
            break;
        case 4:
            $query .= " AND COALESCE(w4.prize_amount, 0) > 0";
            break;
        case 5:
            $query .= " AND COALESCE(w5.prize_amount, 0) > 0";
            break;
    }
}

if (!empty($searchKeyword)) {
    $query .= " AND (p.name LIKE :search OR d.draw_code LIKE :search)";
    $params[':search'] = '%' . $searchKeyword . '%';
}

// 총 레코드 수 쿼리
$countQuery = "SELECT COUNT(*) FROM (" . $query . ") AS count_table";
$countStmt = $db->prepare($countQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalRecords = $countStmt->fetchColumn();

// 전체 페이지 수 계산
$totalPages = ceil($totalRecords / $recordsPerPage);

// 정렬 및 페이지네이션 추가
$query .= " ORDER BY d.draw_date DESC, d.draw_code DESC";
$query .= " LIMIT :offset, :records_per_page";
$params[':offset'] = $offset;
$params[':records_per_page'] = $recordsPerPage;

// 쿼리 실행
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$prizesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 전체 당첨금 합계 (가상 데이터 - 실제로는 DB에서 가져와야 함)
$totalPrize1Amount = 0;
$totalPrize2Amount = 0;
$totalPrize3Amount = 0;
$totalPrize4Amount = 0;
$totalPrize5Amount = 0;
$totalAllPrizeAmount = 0;

foreach ($prizesData as $row) {
    $totalPrize1Amount += $row['prize1_amount'];
    $totalPrize2Amount += $row['prize2_amount'];
    $totalPrize3Amount += $row['prize3_amount'];
    $totalPrize4Amount += $row['prize4_amount'];
    $totalPrize5Amount += $row['prize5_amount'];
    $totalAllPrizeAmount += $row['total_prize_amount'];
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
            w.prize_tier,
            COUNT(*) AS winner_count,
            AVG(w.prize_amount) AS avg_prize_amount,
            SUM(w.prize_amount) AS total_prize_amount
        FROM 
            draws d
        JOIN 
            lottery_products p ON d.product_id = p.id
        LEFT JOIN 
            winnings w ON d.id = w.draw_id
        WHERE 
            d.id = :draw_id
        GROUP BY 
            d.id, d.draw_code, d.draw_date, d.product_id, p.name, w.prize_tier
        ORDER BY 
            w.prize_tier
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
                        <h3><?php echo number_format($totalPrize1Amount); ?>원</h3>
                        <p>1등 당첨금 총액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo number_format($totalAllPrizeAmount); ?>원</h3>
                        <p>전체 당첨금 총액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $totalAllPrizeAmount > 0 ? number_format($totalPrize1Amount / $totalAllPrizeAmount * 100, 2) : 0; ?>%</h3>
                        <p>1등 당첨금 비율</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-percent"></i>
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
                <form id="prizesFilterForm" method="GET" action="">
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
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="start_date">시작 날짜</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="end_date">종료 날짜</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="prize_level">당첨 등수</label>
                                <select class="form-control" id="prize_level" name="prize_level">
                                    <option value="0" <?php echo $prizeLevel == 0 ? 'selected' : ''; ?>>전체</option>
                                    <option value="1" <?php echo $prizeLevel == 1 ? 'selected' : ''; ?>>1등</option>
                                    <option value="2" <?php echo $prizeLevel == 2 ? 'selected' : ''; ?>>2등</option>
                                    <option value="3" <?php echo $prizeLevel == 3 ? 'selected' : ''; ?>>3등</option>
                                    <option value="4" <?php echo $prizeLevel == 4 ? 'selected' : ''; ?>>4등</option>
                                    <option value="5" <?php echo $prizeLevel == 5 ? 'selected' : ''; ?>>5등</option>
                                </select>
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
                            <button type="button" class="btn btn-default" onclick="window.location.href='prizes.php'">
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

        <!-- 당첨금 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">회차별 당첨금 현황</h3>
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
                            <th>1등 당첨금</th>
                            <th>1등 당첨자</th>
                            <th>총 당첨금</th>
                            <th>상세보기</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prizesData)): ?>
                            <tr>
                                <td colspan="7" class="text-center">데이터가 없습니다.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($prizesData as $row): ?>
                                <tr>
                                    <td><?php echo $row['draw_code']; ?>회</td>
                                    <td><?php echo date('Y-m-d', strtotime($row['draw_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td><?php echo number_format($row['prize1_amount']); ?>원</td>
                                    <td><?php echo number_format($row['prize1_winners']); ?>명</td>
                                    <td><?php echo number_format($row['total_prize_amount']); ?>원</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" onclick="showPrizesDetails(<?php echo $row['draw_id']; ?>)">
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

        <!-- 당첨금 차트 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">당첨금 분포 및 추이</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart">
                            <canvas id="prizesDistributionChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart">
                            <canvas id="prizesTrendChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

<!-- 당첨금 상세 정보 모달 -->
<div class="modal fade" id="prizesDetailsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">당첨금 상세 정보</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- 상세 정보가 AJAX로 로드됩니다 -->
                <div id="prizesDetailsContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>로딩 중...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary" onclick="printPrizesDetails()">인쇄</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<script>
// 당첨금 차트 데이터
document.addEventListener('DOMContentLoaded', function() {
    // 당첨금 분포 차트 데이터
    const distributionLabels = ['1등', '2등', '3등', '4등', '5등'];
    const distributionData = [
        <?php echo $totalPrize1Amount / 100000000; ?>, // 억 단위로 변환
        <?php echo $totalPrize2Amount / 100000000; ?>,
        <?php echo $totalPrize3Amount / 100000000; ?>,
        <?php echo $totalPrize4Amount / 100000000; ?>,
        <?php echo $totalPrize5Amount / 100000000; ?>
    ];
    
    // 당첨금 분포 차트 생성
    var distributionChartCanvas = document.getElementById('prizesDistributionChart').getContext('2d');
    var distributionChartData = {
        labels: distributionLabels,
        datasets: [
            {
                data: distributionData,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ]
            }
        ]
    };

    var distributionChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        legend: {
            position: 'right'
        },
        title: {
            display: true,
            text: '등수별 당첨금 분포 (억원)'
        }
    };

    var distributionChart = new Chart(distributionChartCanvas, {
        type: 'pie',
        data: distributionChartData,
        options: distributionChartOptions
    });
    
    // 당첨금 추이 차트 데이터 준비
    const drawNumbers = [];
    const prize1Data = [];
    
    <?php 
    // 최근 6개 회차만 사용하여 차트 데이터 생성 (역순으로 표시하기 위해 역순으로 저장)
    $chartData = array_slice($prizesData, 0, 6);
    $chartData = array_reverse($chartData);
    foreach ($chartData as $row): 
    ?>
        drawNumbers.push('<?php echo $row['draw_code']; ?>회');
        prize1Data.push(<?php echo $row['prize1_amount'] / 100000000; ?>); // 억 단위로 변환
    <?php endforeach; ?>
    
    // 당첨금 추이 차트 생성
    var trendChartCanvas = document.getElementById('prizesTrendChart').getContext('2d');
    var trendChartData = {
        labels: drawNumbers,
        datasets: [
            {
                label: '1등 당첨금 (억원)',
                backgroundColor: 'rgba(60,141,188,0.9)',
                borderColor: 'rgba(60,141,188,0.8)',
                pointRadius: 3,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: prize1Data
            }
        ]
    };

    var trendChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        title: {
            display: true,
            text: '회차별 1등 당첨금 추이'
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

    var trendChart = new Chart(trendChartCanvas, {
        type: 'line',
        data: trendChartData,
        options: trendChartOptions
    });
});

// 당첨금 상세 정보 표시
function showPrizesDetails(drawId) {
    // AJAX로 상세 정보 가져오기
    $('#prizesDetailsModal').modal('show');
    
    // 여기에서 AJAX 요청을 통해 상세 정보를 가져옵니다.
    // 실제 구현시에는 아래 코드를 사용하세요.
    /*
    $.ajax({
        url: 'get_prizes_details.php',
        type: 'GET',
        data: {
            draw_id: drawId
        },
        success: function(response) {
            $('#prizesDetailsContent').html(response);
        },
        error: function() {
            $('#prizesDetailsContent').html('<div class="alert alert-danger">데이터를 불러오는 데 실패했습니다.</div>');
        }
    });
    */
    
    // 여기서는 임시로 상세 정보를 표시합니다.
    setTimeout(function() {
        $('#prizesDetailsContent').html(`
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
                            <th>총 당첨금액</th>
                            <td id="detailTotalPrize">11,943,873,000원</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <h5>등수별 당첨금 현황</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>등수</th>
                                <th>당첨자수</th>
                                <th>1인당 당첨금</th>
                                <th>총 당첨금액</th>
                                <th>비율</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1등</td>
                                <td>3명</td>
                                <td>3,125,750,000원</td>
                                <td>9,377,250,000원</td>
                                <td>78.51%</td>
                            </tr>
                            <tr>
                                <td>2등</td>
                                <td>9명</td>
                                <td>52,096,000원</td>
                                <td>468,864,000원</td>
                                <td>3.93%</td>
                            </tr>
                            <tr>
                                <td>3등</td>
                                <td>238명</td>
                                <td>1,968,000원</td>
                                <td>468,384,000원</td>
                                <td>3.92%</td>
                            </tr>
                            <tr>
                                <td>4등</td>
                                <td>12,145명</td>
                                <td>50,000원</td>
                                <td>607,250,000원</td>
                                <td>5.08%</td>
                            </tr>
                            <tr>
                                <td>5등</td>
                                <td>204,425명</td>
                                <td>5,000원</td>
                                <td>1,022,125,000원</td>
                                <td>8.56%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="chart-responsive">
                        <canvas id="detailPrizesChart" height="200"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5>당첨금 이월 정보</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>구분</th>
                                <th>이월 금액</th>
                                <th>이월 회차</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>이번 회차 이월된 금액</td>
                                <td>0원</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>다음 회차 이월될 금액</td>
                                <td>0원</td>
                                <td>-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        `);
        
        // 상세 정보 차트 그리기
        drawDetailCharts();
    }, 1000);
}

// 상세 정보 차트 그리기
function drawDetailCharts() {
    // 등수별 당첨금 분포 차트
    var prizesChartCanvas = document.getElementById('detailPrizesChart').getContext('2d');
    var prizesChartData = {
        labels: ['1등', '2등', '3등', '4등', '5등'],
        datasets: [
            {
                data: [78.51, 3.93, 3.92, 5.08, 8.56],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ]
            }
        ]
    };

    var prizesChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        legend: {
            position: 'right'
        },
        title: {
            display: true,
            text: '등수별 당첨금 비율'
        }
    };

    var prizesChart = new Chart(prizesChartCanvas, {
        type: 'pie',
        data: prizesChartData,
        options: prizesChartOptions
    });
}

// 당첨금 상세 정보 인쇄
function printPrizesDetails() {
    var printContents = document.getElementById('prizesDetailsContent').innerHTML;
    var originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div class="container mt-3">
            <h2 class="text-center mb-4">당첨금 상세 정보</h2>
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
    alert('당첨금 데이터를 엑셀로 다운로드합니다.');
    // 실제 구현시에는 아래와 같이 서버에 요청하여 엑셀 파일을 다운로드합니다.
    // window.location.href = 'export_prizes.php' + window.location.search;
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
