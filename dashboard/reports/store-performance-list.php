<?php
/**
 * 판매점 성과 목록 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "판매점 성과 목록";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 현재 월 및 연도
$currentMonth = date('m');
$currentYear = date('Y');

// 페이징 설정
$currentPageNum = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($currentPageNum - 1) * $itemsPerPage;

// 필터링 옵션 처리
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;
$region = isset($_GET['region']) ? $_GET['region'] : 'all';
$storeCategory = isset($_GET['store_category']) ? $_GET['store_category'] : 'all';
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'sales_amount';
$sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'desc';
$searchKeyword = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

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

// 정렬 기준에 따른 SQL 조건 설정
$sortingColumn = 'sp.sales_amount';
switch ($sortBy) {
    case 'store_name':
        $sortingColumn = 's.store_name';
        break;
    case 'store_code':
        $sortingColumn = 's.store_code';
        break;
    case 'region':
        $sortingColumn = 's.city';
        break;
    case 'sales_amount':
        $sortingColumn = 'sp.sales_amount';
        break;
    case 'sales_count':
        $sortingColumn = 'sp.sales_count';
        break;
    case 'achievement_rate':
        $sortingColumn = 'sp.achievement_rate';
        break;
    case 'performance_rating':
        $sortingColumn = 'sp.performance_rating';
        break;
    default:
        $sortingColumn = 'sp.sales_amount';
}

// SQL ORDER BY 부분 설정
$orderBy = $sortingColumn . ' ' . ($sortOrder === 'asc' ? 'ASC' : 'DESC');

// 판매점 성과 데이터 조회 (샘플 데이터 사용)
// 실제 구현에서는 데이터베이스에서 쿼리
$totalRecords = 156; // 전체 레코드 수

// 전체 페이지 수 계산
$totalPages = ceil($totalRecords / $itemsPerPage);

// 페이지 목록 범위 계산
$pageListStart = max(1, $currentPageNum - 2);
$pageListEnd = min($totalPages, $currentPageNum + 2);

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
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/">홈</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/reports/">통계 및 보고서</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/reports/store-report.php">판매점 보고서</a></li>
                    <li class="breadcrumb-item active">판매점 성과 목록</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 필터 옵션 -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">보고서 필터</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="reportFilterForm" method="GET" action="">
                    <div class="row">
                        <div class="col-md-2">
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
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="year">연도</label>
                                <select class="form-control" id="year" name="year">
                                    <?php for($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                                        <option value="<?php echo $y; ?>" <?php if($year == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="month">월</label>
                                <select class="form-control" id="month" name="month">
                                    <?php for($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?php echo $m; ?>" <?php if($month == $m) echo 'selected'; ?>><?php echo $m; ?>월</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="region">지역</label>
                                <select class="form-control" id="region" name="region">
                                    <option value="all" <?php if($region == 'all') echo 'selected'; ?>>전체 지역</option>
                                    <option value="kathmandu" <?php if($region == 'kathmandu') echo 'selected'; ?>>카트만두</option>
                                    <option value="pokhara" <?php if($region == 'pokhara') echo 'selected'; ?>>포카라</option>
                                    <option value="lalitpur" <?php if($region == 'lalitpur') echo 'selected'; ?>>랄리트푸르</option>
                                    <option value="bhaktapur" <?php if($region == 'bhaktapur') echo 'selected'; ?>>박타푸르</option>
                                    <option value="biratnagar" <?php if($region == 'biratnagar') echo 'selected'; ?>>비랏나가르</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="store_category">판매점 카테고리</label>
                                <select class="form-control" id="store_category" name="store_category">
                                    <option value="all" <?php if($storeCategory == 'all') echo 'selected'; ?>>모든 카테고리</option>
                                    <option value="standard" <?php if($storeCategory == 'standard') echo 'selected'; ?>>일반</option>
                                    <option value="premium" <?php if($storeCategory == 'premium') echo 'selected'; ?>>프리미엄</option>
                                    <option value="exclusive" <?php if($storeCategory == 'exclusive') echo 'selected'; ?>>독점</option>
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
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="sort_by">정렬 기준</label>
                                <select class="form-control" id="sort_by" name="sort_by">
                                    <option value="sales_amount" <?php if($sortBy == 'sales_amount') echo 'selected'; ?>>판매 금액</option>
                                    <option value="sales_count" <?php if($sortBy == 'sales_count') echo 'selected'; ?>>판매 건수</option>
                                    <option value="achievement_rate" <?php if($sortBy == 'achievement_rate') echo 'selected'; ?>>목표 달성률</option>
                                    <option value="performance_rating" <?php if($sortBy == 'performance_rating') echo 'selected'; ?>>성과 등급</option>
                                    <option value="store_name" <?php if($sortBy == 'store_name') echo 'selected'; ?>>판매점명</option>
                                    <option value="region" <?php if($sortBy == 'region') echo 'selected'; ?>>지역</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="sort_order">정렬 순서</label>
                                <select class="form-control" id="sort_order" name="sort_order">
                                    <option value="desc" <?php if($sortOrder == 'desc') echo 'selected'; ?>>내림차순</option>
                                    <option value="asc" <?php if($sortOrder == 'asc') echo 'selected'; ?>>오름차순</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="search">판매점 검색</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search" name="search" placeholder="판매점 코드, 이름, 소유자 검색" value="<?php echo htmlspecialchars($searchKeyword); ?>">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-default">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
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
        
        <!-- 판매점 성과 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">판매점 성과 목록</h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm">
                        <select class="form-control" id="itemsPerPage" name="limit">
                            <option value="10" <?php if($itemsPerPage == 10) echo 'selected'; ?>>10개씩 보기</option>
                            <option value="20" <?php if($itemsPerPage == 20) echo 'selected'; ?>>20개씩 보기</option>
                            <option value="50" <?php if($itemsPerPage == 50) echo 'selected'; ?>>50개씩 보기</option>
                            <option value="100" <?php if($itemsPerPage == 100) echo 'selected'; ?>>100개씩 보기</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th style="width: 50px">순위</th>
                            <th>판매점 코드</th>
                            <th>판매점명</th>
                            <th>지역</th>
                            <th>카테고리</th>
                            <th>판매 수량</th>
                            <th>판매 금액</th>
                            <th>목표 달성률</th>
                            <th>성과 등급</th>
                            <th style="width: 100px">상세 보기</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- 샘플 데이터 -->
                        <?php for($i = 1; $i <= min(20, $totalRecords - $offset); $i++): ?>
                            <?php
                            // 랭킹 계산
                            $rank = $offset + $i;
                            
                            // 더미 데이터 (실제 구현에서는 DB에서 가져온 데이터 사용)
                            $dummyData = [
                                [
                                    'code' => 'KTM-001',
                                    'name' => '네팔 마트 #23',
                                    'region' => '카트만두',
                                    'category' => 'exclusive',
                                    'sales_count' => 15240,
                                    'sales_amount' => 3048000,
                                    'achievement_rate' => 128,
                                    'rating' => 'excellent'
                                ],
                                [
                                    'code' => 'KTM-015',
                                    'name' => '카트만두 센터 #05',
                                    'region' => '카트만두',
                                    'category' => 'premium',
                                    'sales_count' => 12850,
                                    'sales_amount' => 2570000,
                                    'achievement_rate' => 115,
                                    'rating' => 'excellent'
                                ],
                                [
                                    'code' => 'PKR-007',
                                    'name' => '포카라 샵 #18',
                                    'region' => '포카라',
                                    'category' => 'premium',
                                    'sales_count' => 10524,
                                    'sales_amount' => 2104800,
                                    'achievement_rate' => 108,
                                    'rating' => 'good'
                                ],
                                [
                                    'code' => 'LLT-003',
                                    'name' => '랄리트푸르 #11',
                                    'region' => '랄리트푸르',
                                    'category' => 'exclusive',
                                    'sales_count' => 9650,
                                    'sales_amount' => 1930000,
                                    'achievement_rate' => 112,
                                    'rating' => 'good'
                                ],
                                [
                                    'code' => 'BRT-005',
                                    'name' => '비랏나가르 #07',
                                    'region' => '비랏나가르',
                                    'category' => 'premium',
                                    'sales_count' => 8540,
                                    'sales_amount' => 1708000,
                                    'achievement_rate' => 105,
                                    'rating' => 'good'
                                ]
                            ];
                            
                            // 현재 행의 데이터 설정
                            $index = ($rank - 1) % count($dummyData);
                            $store = $dummyData[$index];
                            
                            // 랜덤 변형 (더 다양한 데이터처럼 보이게)
                            $variation = 0.8 + (0.4 * (20 - $rank) / 20); // 상위 순위일수록 더 높은 값
                            $store['sales_count'] = round($store['sales_count'] * $variation);
                            $store['sales_amount'] = round($store['sales_amount'] * $variation);
                            $store['achievement_rate'] = round($store['achievement_rate'] * $variation);
                            
                            // 등급 설정
                            if($store['achievement_rate'] >= 110) {
                                $store['rating'] = 'excellent';
                            } elseif($store['achievement_rate'] >= 90) {
                                $store['rating'] = 'good';
                            } elseif($store['achievement_rate'] >= 70) {
                                $store['rating'] = 'average';
                            } else {
                                $store['rating'] = 'poor';
                            }
                            ?>
                            <tr>
                                <td><?php echo $rank; ?></td>
                                <td><?php echo $store['code']; ?></td>
                                <td><?php echo $store['name']; ?></td>
                                <td><?php echo $store['region']; ?></td>
                                <td>
                                    <?php
                                    switch ($store['category']) {
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
                                <td><?php echo number_format($store['sales_count']); ?></td>
                                <td>₹ <?php echo number_format($store['sales_amount']); ?></td>
                                <td>
                                    <div class="progress progress-xs">
                                        <?php
                                        $progressClass = 'bg-danger';
                                        if ($store['achievement_rate'] >= 90) {
                                            $progressClass = 'bg-success';
                                        } elseif ($store['achievement_rate'] >= 70) {
                                            $progressClass = 'bg-warning';
                                        }
                                        ?>
                                        <div class="progress-bar <?php echo $progressClass; ?>" style="width: <?php echo min($store['achievement_rate'], 100); ?>%"></div>
                                    </div>
                                    <small><?php echo $store['achievement_rate']; ?>%</small>
                                </td>
                                <td>
                                    <?php
                                    switch ($store['rating']) {
                                        case 'excellent':
                                            echo '<span class="badge badge-success">우수</span>';
                                            break;
                                        case 'good':
                                            echo '<span class="badge badge-primary">양호</span>';
                                            break;
                                        case 'average':
                                            echo '<span class="badge badge-warning">보통</span>';
                                            break;
                                        case 'poor':
                                            echo '<span class="badge badge-danger">미흡</span>';
                                            break;
                                        default:
                                            echo '<span class="badge badge-secondary">평가 없음</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="store-performance.php?id=<?php echo $store['code']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> 상세
                                    </a>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <div class="float-left">
                    총 <strong><?php echo $totalRecords; ?></strong>개 판매점 중 
                    <strong><?php echo $offset + 1; ?></strong>번부터 
                    <strong><?php echo min($offset + $itemsPerPage, $totalRecords); ?></strong>번까지 표시
                </div>
                <ul class="pagination pagination-sm m-0 float-right">
                    <?php if ($currentPageNum > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1<?php echo getQueryStringExcept('page'); ?>">&laquo;</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $currentPageNum - 1; ?><?php echo getQueryStringExcept('page'); ?>">&lsaquo;</a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <a class="page-link" href="#">&laquo;</a>
                        </li>
                        <li class="page-item disabled">
                            <a class="page-link" href="#">&lsaquo;</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = $pageListStart; $i <= $pageListEnd; $i++): ?>
                        <li class="page-item <?php echo ($i == $currentPageNum) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo getQueryStringExcept('page'); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($currentPageNum < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $currentPageNum + 1; ?><?php echo getQueryStringExcept('page'); ?>">&rsaquo;</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo getQueryStringExcept('page'); ?>">&raquo;</a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <a class="page-link" href="#">&rsaquo;</a>
                        </li>
                        <li class="page-item disabled">
                            <a class="page-link" href="#">&raquo;</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 성과 요약 차트 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">성과 분포 요약</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="chart-responsive">
                            <canvas id="performanceChart" height="200"></canvas>
                        </div>
                        <div class="text-center mt-3">
                            <h5>성과 등급 분포</h5>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-responsive">
                            <canvas id="categoryChart" height="200"></canvas>
                        </div>
                        <div class="text-center mt-3">
                            <h5>판매점 카테고리별 평균 달성률</h5>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-responsive">
                            <canvas id="regionChart" height="200"></canvas>
                        </div>
                        <div class="text-center mt-3">
                            <h5>지역별 평균 달성률</h5>
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
    console.log('판매점 성과 목록 페이지가 로드되었습니다.');
    
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
            window.location.href = 'store-performance-list.php';
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

    // 페이지당 항목 수 변경 이벤트
    const itemsPerPageSelect = document.getElementById('itemsPerPage');
    if (itemsPerPageSelect) {
        itemsPerPageSelect.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('limit', this.value);
            url.searchParams.set('page', '1'); // 페이지를 1로 리셋
            window.location.href = url.toString();
        });
    }

    // 성과 등급 분포 차트
    const performanceChart = document.getElementById('performanceChart');
    if (performanceChart) {
        new Chart(performanceChart, {
            type: 'pie',
            data: {
                labels: ['우수', '양호', '보통', '미흡'],
                datasets: [
                    {
                        data: [42, 65, 38, 11],
                        backgroundColor: ['#28a745', '#007bff', '#ffc107', '#dc3545'],
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // 카테고리별 평균 달성률 차트
    const categoryChart = document.getElementById('categoryChart');
    if (categoryChart) {
        new Chart(categoryChart, {
            type: 'bar',
            data: {
                labels: ['일반', '프리미엄', '독점'],
                datasets: [
                    {
                        label: '평균 달성률(%)',
                        data: [82, 95, 115],
                        backgroundColor: ['#6c757d', '#007bff', '#28a745'],
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    // 지역별 평균 달성률 차트
    const regionChart = document.getElementById('regionChart');
    if (regionChart) {
        new Chart(regionChart, {
            type: 'bar',
            data: {
                labels: ['카트만두', '포카라', '랄리트푸르', '박타푸르', '비랏나가르'],
                datasets: [
                    {
                        label: '평균 달성률(%)',
                        data: [95, 88, 84, 79, 82],
                        backgroundColor: '#17a2b8',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});

// 페이지 파라미터를 제외한 쿼리 문자열 생성 함수
function getQueryStringExcept(excludeParam) {
    const url = new URL(window.location.href);
    const params = url.searchParams;
    
    // 제외할 파라미터 삭제
    params.delete(excludeParam);
    
    // 쿼리 문자열 생성
    const queryString = params.toString();
    
    // 쿼리 문자열이 비어있지 않으면 &를 추가하여 반환
    return queryString.length > 0 ? '&' + queryString : '';
}
</script>

<?php
// 유틸리티 함수

/**
 * 페이지 파라미터를 제외한 쿼리 문자열 생성 PHP 함수
 */
function getQueryStringExcept($excludeParam) {
    $params = $_GET;
    
    // 제외할 파라미터 삭제
    if (isset($params[$excludeParam])) {
        unset($params[$excludeParam]);
    }
    
    // 쿼리 문자열 생성
    $queryString = http_build_query($params);
    
    // 쿼리 문자열이 비어있지 않으면 &를 추가하여 반환
    return $queryString ? '&' . $queryString : '';
}

// 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>
