<?php
/**
 * 기금액 상세 조회 페이지
 * 회차별 기금액 현황과 상세 정보를 제공합니다.
 */

// 공통 파일 포함
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "기금액 현황";
$currentSection = "government";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = getDBConnection();

// 필터 변수
$startDraw = isset($_GET['start_draw']) ? intval($_GET['start_draw']) : 0;
$endDraw = isset($_GET['end_draw']) ? intval($_GET['end_draw']) : 0;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$fundCategory = isset($_GET['fund_category']) ? $_GET['fund_category'] : '';
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

// 회차별 기금액 목록 쿼리 구성
$query = "
    SELECT 
        d.id AS draw_id,
        d.draw_code,
        d.draw_date,
        d.product_id,
        p.name AS product_name,
        COALESCE(f.total_fund_amount, 0) AS total_fund_amount,
        COALESCE(f.arts_fund, 0) AS arts_fund,
        COALESCE(f.sports_fund, 0) AS sports_fund,
        COALESCE(f.welfare_fund, 0) AS welfare_fund,
        COALESCE(f.disaster_fund, 0) AS disaster_fund,
        COALESCE(f.community_fund, 0) AS community_fund,
        COALESCE(s.total_sales_amount, 0) AS total_sales_amount
    FROM 
        draws d
    LEFT JOIN 
        lottery_products p ON d.product_id = p.id
    LEFT JOIN (
        SELECT 
            f.id as fund_id,
            ft.reference_id as draw_id,
            SUM(ft.amount) AS total_fund_amount,
            SUM(CASE WHEN f.fund_name LIKE '%arts%' THEN ft.amount ELSE 0 END) AS arts_fund,
            SUM(CASE WHEN f.fund_name LIKE '%sports%' THEN ft.amount ELSE 0 END) AS sports_fund,
            SUM(CASE WHEN f.fund_name LIKE '%welfare%' THEN ft.amount ELSE 0 END) AS welfare_fund,
            SUM(CASE WHEN f.fund_name LIKE '%disaster%' THEN ft.amount ELSE 0 END) AS disaster_fund,
            SUM(CASE WHEN f.fund_name LIKE '%community%' THEN ft.amount ELSE 0 END) AS community_fund
        FROM 
            fund_transactions ft
        JOIN
            funds f ON ft.fund_id = f.id
        WHERE
            ft.reference_type = 'draw'
        GROUP BY 
            ft.reference_id
    ) f ON d.id = f.draw_id
    LEFT JOIN (
        SELECT 
            lottery_type_id as draw_id,
            SUM(total_amount) AS total_sales_amount
        FROM 
            sales_transactions
        GROUP BY 
            lottery_type_id
    ) s ON d.id = s.draw_id
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

if (!empty($fundCategory)) {
    switch ($fundCategory) {
        case 'arts':
            $query .= " AND COALESCE(f.arts_fund, 0) > 0";
            break;
        case 'sports':
            $query .= " AND COALESCE(f.sports_fund, 0) > 0";
            break;
        case 'welfare':
            $query .= " AND COALESCE(f.welfare_fund, 0) > 0";
            break;
        case 'disaster':
            $query .= " AND COALESCE(f.disaster_fund, 0) > 0";
            break;
        case 'community':
            $query .= " AND COALESCE(f.community_fund, 0) > 0";
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
$fundsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 전체 기금액 합계 (가상 데이터 - 실제로는 DB에서 가져와야 함)
$totalFundAmount = 0;
$totalArtsFund = 0;
$totalSportsFund = 0;
$totalWelfareFund = 0;
$totalDisasterFund = 0;
$totalCommunityFund = 0;

foreach ($fundsData as $row) {
    $totalFundAmount += $row['total_fund_amount'];
    $totalArtsFund += $row['arts_fund'];
    $totalSportsFund += $row['sports_fund'];
    $totalWelfareFund += $row['welfare_fund'];
    $totalDisasterFund += $row['disaster_fund'];
    $totalCommunityFund += $row['community_fund'];
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
            f.fund_name as fund_category,
            ft.amount as fund_amount,
            ft.description as fund_usage,
            ft.transaction_date as fund_date
        FROM 
            draws d
        JOIN 
            lottery_products p ON d.product_id = p.id
        LEFT JOIN 
            fund_transactions ft ON CAST(d.id AS CHAR) = ft.reference_id AND ft.reference_type = 'draw'
        LEFT JOIN
            funds f ON ft.fund_id = f.id
        WHERE 
            d.id = :draw_id
        ORDER BY 
            lf.fund_category
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
                        <h3><?php echo formatCurrency($totalFundAmount); ?></h3>
                        <p>총 기금액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $totalFundAmount > 0 ? number_format($totalWelfareFund / $totalFundAmount * 100, 2) : 0; ?>%</h3>
                        <p>사회복지 기금 비율</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-heart"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo formatCurrency($totalFundAmount / count($fundsData)); ?></h3>
                        <p>회차당 평균 기금액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
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
                <form id="fundsFilterForm" method="GET" action="">
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
                                <label for="fund_category">기금 분야</label>
                                <select class="form-control" id="fund_category" name="fund_category">
                                    <option value="" <?php echo empty($fundCategory) ? 'selected' : ''; ?>>전체</option>
                                    <option value="arts" <?php echo $fundCategory == 'arts' ? 'selected' : ''; ?>>문화예술</option>
                                    <option value="sports" <?php echo $fundCategory == 'sports' ? 'selected' : ''; ?>>체육진흥</option>
                                    <option value="welfare" <?php echo $fundCategory == 'welfare' ? 'selected' : ''; ?>>사회복지</option>
                                    <option value="disaster" <?php echo $fundCategory == 'disaster' ? 'selected' : ''; ?>>재난구호</option>
                                    <option value="community" <?php echo $fundCategory == 'community' ? 'selected' : ''; ?>>지역사회</option>
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
                            <button type="button" class="btn btn-default" onclick="window.location.href='funds.php'">
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

        <!-- 기금액 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">회차별 기금액 현황</h3>
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
                            <th>총 기금액</th>
                            <th>문화예술</th>
                            <th>체육진흥</th>
                            <th>사회복지</th>
                            <th>재난구호</th>
                            <th>지역사회</th>
                            <th>상세보기</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fundsData)): ?>
                            <tr>
                                <td colspan="9" class="text-center">데이터가 없습니다.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fundsData as $row): ?>
                                <tr>
                                    <td><?php echo $row['draw_code']; ?>회</td>
                                    <td><?php echo date('Y-m-d', strtotime($row['draw_date'])); ?></td>
                                    <td><?php echo formatCurrency($row['total_fund_amount']); ?></td>
                                    <td><?php echo formatCurrency($row['arts_fund']); ?></td>
                                    <td><?php echo formatCurrency($row['sports_fund']); ?></td>
                                    <td><?php echo formatCurrency($row['welfare_fund']); ?></td>
                                    <td><?php echo formatCurrency($row['disaster_fund']); ?></td>
                                    <td><?php echo formatCurrency($row['community_fund']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" onclick="showFundsDetails(<?php echo $row['draw_id']; ?>)">
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

        <!-- 기금액 차트 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">기금액 분포 및 추이</h3>
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
                            <canvas id="fundsDistributionChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart">
                            <canvas id="fundsTrendChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->

        <!-- 기금 사용 현황 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">기금 사용 현황</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>분야</th>
                                        <th>총 기금액</th>
                                        <th>비율</th>
                                        <th>주요 사용처</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>문화예술</td>
                                        <td><?php echo formatCurrency($totalArtsFund); ?></td>
                                        <td><?php echo $totalFundAmount > 0 ? number_format($totalArtsFund / $totalFundAmount * 100, 2) : 0; ?>%</td>
                                        <td>문화시설 건립, 전통문화 보존, 공연예술 지원 등</td>
                                    </tr>
                                    <tr>
                                        <td>체육진흥</td>
                                        <td><?php echo formatCurrency($totalSportsFund); ?></td>
                                        <td><?php echo $totalFundAmount > 0 ? number_format($totalSportsFund / $totalFundAmount * 100, 2) : 0; ?>%</td>
                                        <td>체육시설 확충, 생활체육 활성화, 엘리트 체육 지원 등</td>
                                    </tr>
                                    <tr>
                                        <td>사회복지</td>
                                        <td><?php echo formatCurrency($totalWelfareFund); ?></td>
                                        <td><?php echo $totalFundAmount > 0 ? number_format($totalWelfareFund / $totalFundAmount * 100, 2) : 0; ?>%</td>
                                        <td>저소득층 지원, 장애인 복지, 노인복지시설 등</td>
                                    </tr>
                                    <tr>
                                        <td>재난구호</td>
                                        <td><?php echo formatCurrency($totalDisasterFund); ?></td>
                                        <td><?php echo $totalFundAmount > 0 ? number_format($totalDisasterFund / $totalFundAmount * 100, 2) : 0; ?>%</td>
                                        <td>자연재해 복구, 재난예방 시스템 구축 등</td>
                                    </tr>
                                    <tr>
                                        <td>지역사회</td>
                                        <td><?php echo formatCurrency($totalCommunityFund); ?></td>
                                        <td><?php echo $totalFundAmount > 0 ? number_format($totalCommunityFund / $totalFundAmount * 100, 2) : 0; ?>%</td>
                                        <td>지역발전 사업, 마을 공동체 활성화, 지역 인프라 개선 등</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>합계</th>
                                        <th><?php echo formatCurrency($totalFundAmount); ?></th>
                                        <th>100%</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
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

<!-- 기금액 상세 정보 모달 -->
<div class="modal fade" id="fundsDetailsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">기금액 상세 정보</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- 상세 정보가 AJAX로 로드됩니다 -->
                <div id="fundsDetailsContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>로딩 중...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary" onclick="printFundsDetails()">인쇄</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<script>
// 기금액 차트 데이터
document.addEventListener('DOMContentLoaded', function() {
    // 기금액 분포 차트 데이터
    const distributionLabels = ['문화예술', '체육진흥', '사회복지', '재난구호', '지역사회'];
    const distributionData = [
        <?php echo $totalFundAmount > 0 ? ($totalArtsFund / $totalFundAmount * 100) : 0; ?>,
        <?php echo $totalFundAmount > 0 ? ($totalSportsFund / $totalFundAmount * 100) : 0; ?>,
        <?php echo $totalFundAmount > 0 ? ($totalWelfareFund / $totalFundAmount * 100) : 0; ?>,
        <?php echo $totalFundAmount > 0 ? ($totalDisasterFund / $totalFundAmount * 100) : 0; ?>,
        <?php echo $totalFundAmount > 0 ? ($totalCommunityFund / $totalFundAmount * 100) : 0; ?>
    ];
    
    // 기금액 분포 차트 생성
    var distributionChartCanvas = document.getElementById('fundsDistributionChart').getContext('2d');
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
            text: '분야별 기금 분포 (%)'
        }
    };

    var distributionChart = new Chart(distributionChartCanvas, {
        type: 'pie',
        data: distributionChartData,
        options: distributionChartOptions
    });
    
    // 기금액 추이 차트 데이터 준비
    const drawNumbers = [];
    const fundData = [];
    
    <?php 
    // 최근 6개 회차만 사용하여 차트 데이터 생성 (역순으로 표시하기 위해 역순으로 저장)
    $chartData = array_slice($fundsData, 0, 6);
    $chartData = array_reverse($chartData);
    foreach ($chartData as $row): 
    ?>
        drawNumbers.push('<?php echo $row['draw_code']; ?>회');
        fundData.push(<?php echo $row['total_fund_amount'] / 100000000; ?>); // 억 단위로 변환
    <?php endforeach; ?>
    
    // 기금액 추이 차트 생성
    var trendChartCanvas = document.getElementById('fundsTrendChart').getContext('2d');
    var trendChartData = {
        labels: drawNumbers,
        datasets: [
            {
                label: '총 기금액 (억원)',
                backgroundColor: 'rgba(60,141,188,0.9)',
                borderColor: 'rgba(60,141,188,0.8)',
                pointRadius: 3,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: fundData
            }
        ]
    };

    var trendChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        title: {
            display: true,
            text: '회차별 기금액 추이'
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

// 기금액 상세 정보 표시
function showFundsDetails(drawId) {
    // AJAX로 상세 정보 가져오기
    $('#fundsDetailsModal').modal('show');
    
    // 여기에서 AJAX 요청을 통해 상세 정보를 가져옵니다.
    // 실제 구현시에는 아래 코드를 사용하세요.
    /*
    $.ajax({
        url: 'get_funds_details.php',
        type: 'GET',
        data: {
            draw_id: drawId
        },
        success: function(response) {
            $('#fundsDetailsContent').html(response);
        },
        error: function() {
            $('#fundsDetailsContent').html('<div class="alert alert-danger">데이터를 불러오는 데 실패했습니다.</div>');
        }
    });
    */
    
    // 여기서는 임시로 상세 정보를 표시합니다.
    setTimeout(function() {
        $('#fundsDetailsContent').html(`
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
                            <th>총 기금액</th>
                            <td id="detailTotalFund">25,636,897,200원</td>
                        </tr>
                        <tr>
                            <th>판매액</th>
                            <td id="detailSalesAmount">85,456,324,000원</td>
                            <th>기금 비율</th>
                            <td id="detailFundRate">30%</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <h5>분야별 기금 현황</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>분야</th>
                                <th>금액</th>
                                <th>비율</th>
                                <th>사용 기간</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>문화예술</td>
                                <td>4,614,641,496원</td>
                                <td>18%</td>
                                <td>2022-01-01 ~ 2022-12-31</td>
                            </tr>
                            <tr>
                                <td>체육진흥</td>
                                <td>5,127,379,440원</td>
                                <td>20%</td>
                                <td>2022-01-01 ~ 2022-12-31</td>
                            </tr>
                            <tr>
                                <td>사회복지</td>
                                <td>8,972,914,020원</td>
                                <td>35%</td>
                                <td>2022-01-01 ~ 2022-12-31</td>
                            </tr>
                            <tr>
                                <td>재난구호</td>
                                <td>3,845,534,580원</td>
                                <td>15%</td>
                                <td>2022-01-01 ~ 2022-12-31</td>
                            </tr>
                            <tr>
                                <td>지역사회</td>
                                <td>3,076,427,664원</td>
                                <td>12%</td>
                                <td>2022-01-01 ~ 2022-12-31</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="chart-responsive">
                        <canvas id="detailFundsChart" height="200"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5>주요 기금 사용 내역</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>분야</th>
                                <th>주요 사용처</th>
                                <th>금액</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>문화예술</td>
                                <td>지역 문화센터 건립</td>
                                <td>2,000,000,000원</td>
                            </tr>
                            <tr>
                                <td>체육진흥</td>
                                <td>전국체육대회 지원</td>
                                <td>2,500,000,000원</td>
                            </tr>
                            <tr>
                                <td>사회복지</td>
                                <td>저소득층 주거환경 개선</td>
                                <td>4,000,000,000원</td>
                            </tr>
                            <tr>
                                <td>재난구호</td>
                                <td>수해 피해 복구 지원</td>
                                <td>1,800,000,000원</td>
                            </tr>
                            <tr>
                                <td>지역사회</td>
                                <td>마을 공동체 사업</td>
                                <td>1,500,000,000원</td>
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
    // 분야별 기금 분포 차트
    var fundsChartCanvas = document.getElementById('detailFundsChart').getContext('2d');
    var fundsChartData = {
        labels: ['문화예술', '체육진흥', '사회복지', '재난구호', '지역사회'],
        datasets: [
            {
                data: [18, 20, 35, 15, 12],
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

    var fundsChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        legend: {
            position: 'right'
        },
        title: {
            display: true,
            text: '분야별 기금 비율'
        }
    };

    var fundsChart = new Chart(fundsChartCanvas, {
        type: 'pie',
        data: fundsChartData,
        options: fundsChartOptions
    });
}

// 기금액 상세 정보 인쇄
function printFundsDetails() {
    var printContents = document.getElementById('fundsDetailsContent').innerHTML;
    var originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div class="container mt-3">
            <h2 class="text-center mb-4">기금액 상세 정보</h2>
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
    alert('기금액 데이터를 엑셀로 다운로드합니다.');
    // 실제 구현시에는 아래와 같이 서버에 요청하여 엑셀 파일을 다운로드합니다.
    // window.location.href = 'export_funds.php' + window.location.search;
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
