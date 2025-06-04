<?php
/**
 * 판매점 보고서 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "판매점 보고서";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 현재 월 및 연도
$currentMonth = date('m');
$currentYear = date('Y');

// 필터링 옵션 처리
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;
$region = isset($_GET['region']) ? $_GET['region'] : 'all';
$storeCategory = isset($_GET['store_category']) ? $_GET['store_category'] : 'all';

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
                    <li class="breadcrumb-item active">통계 및 보고서</li>
                    <li class="breadcrumb-item active">판매점 보고서</li>
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
        
        <!-- 요약 정보 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>156</h3>
                        <p>총 판매점 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>142</h3>
                        <p>활성 판매점</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>₹ 338,462</h3>
                        <p>판매점당 평균 매출</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>8</h3>
                        <p>비활성 판매점</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-store-slash"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 판매점 성과 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">판매점 성과 요약</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="chart">
                            <div style="height: 300px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                <span>판매점 판매 추이 차트 (개발 중)</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>카테고리</th>
                                        <th>판매점 수</th>
                                        <th>총 매출</th>
                                        <th>평균 매출</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 -->
                                    <tr>
                                        <td>일반</td>
                                        <td>120</td>
                                        <td>₹ 32,568,450</td>
                                        <td>₹ 271,404</td>
                                    </tr>
                                    <tr>
                                        <td>프리미엄</td>
                                        <td>28</td>
                                        <td>₹ 15,624,800</td>
                                        <td>₹ 557,314</td>
                                    </tr>
                                    <tr>
                                        <td>독점</td>
                                        <td>8</td>
                                        <td>₹ 4,606,750</td>
                                        <td>₹ 575,844</td>
                                    </tr>
                                    <tr>
                                        <td><strong>합계</strong></td>
                                        <td><strong>156</strong></td>
                                        <td><strong>₹ 52,800,000</strong></td>
                                        <td><strong>₹ 338,462</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 지역별 판매점 분포 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">지역별 판매점 분포</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div style="height: 400px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <span>지역별 판매점 분포 지도 (개발 중)</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>지역</th>
                                        <th>판매점 수</th>
                                        <th>활성</th>
                                        <th>비활성</th>
                                        <th>총 매출</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 -->
                                    <tr>
                                        <td>카트만두</td>
                                        <td>58</td>
                                        <td>54</td>
                                        <td>4</td>
                                        <td>₹ 21,120,000</td>
                                    </tr>
                                    <tr>
                                        <td>포카라</td>
                                        <td>32</td>
                                        <td>30</td>
                                        <td>2</td>
                                        <td>₹ 9,504,000</td>
                                    </tr>
                                    <tr>
                                        <td>랄리트푸르</td>
                                        <td>28</td>
                                        <td>26</td>
                                        <td>2</td>
                                        <td>₹ 6,336,000</td>
                                    </tr>
                                    <tr>
                                        <td>박타푸르</td>
                                        <td>20</td>
                                        <td>20</td>
                                        <td>0</td>
                                        <td>₹ 4,224,000</td>
                                    </tr>
                                    <tr>
                                        <td>비랏나가르</td>
                                        <td>18</td>
                                        <td>18</td>
                                        <td>0</td>
                                        <td>₹ 3,696,000</td>
                                    </tr>
                                    <tr>
                                        <td><strong>합계</strong></td>
                                        <td><strong>156</strong></td>
                                        <td><strong>148</strong></td>
                                        <td><strong>8</strong></td>
                                        <td><strong>₹ 52,800,000</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 상위 10개 판매점 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">상위 10개 판매점 (매출 기준)</h3>
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
                                <th style="width: 50px">순위</th>
                                <th>판매점 코드</th>
                                <th>판매점명</th>
                                <th>지역</th>
                                <th>카테고리</th>
                                <th>판매 수량</th>
                                <th>판매 금액</th>
                                <th>목표 달성률</th>
                                <th>상세 보기</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>1</td>
                                <td>KTM-001</td>
                                <td>네팔 마트 #23</td>
                                <td>카트만두</td>
                                <td>독점</td>
                                <td>15,240</td>
                                <td>₹ 3,048,000</td>
                                <td>128%</td>
                                <td>
                                    <a href="store-performance.php?id=KTM-001" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>KTM-015</td>
                                <td>카트만두 센터 #05</td>
                                <td>카트만두</td>
                                <td>프리미엄</td>
                                <td>12,850</td>
                                <td>₹ 2,570,000</td>
                                <td>115%</td>
                                <td>
                                    <a href="store-performance.php?id=KTM-015" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>PKR-007</td>
                                <td>포카라 샵 #18</td>
                                <td>포카라</td>
                                <td>프리미엄</td>
                                <td>10,524</td>
                                <td>₹ 2,104,800</td>
                                <td>108%</td>
                                <td>
                                    <a href="store-performance.php?id=PKR-007" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>LLT-003</td>
                                <td>랄리트푸르 #11</td>
                                <td>랄리트푸르</td>
                                <td>독점</td>
                                <td>9,650</td>
                                <td>₹ 1,930,000</td>
                                <td>112%</td>
                                <td>
                                    <a href="store-performance.php?id=LLT-003" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>BRT-005</td>
                                <td>비랏나가르 #07</td>
                                <td>비랏나가르</td>
                                <td>프리미엄</td>
                                <td>8,540</td>
                                <td>₹ 1,708,000</td>
                                <td>105%</td>
                                <td>
                                    <a href="store-performance.php?id=BRT-005" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>KTM-022</td>
                                <td>센트럴 복권센터</td>
                                <td>카트만두</td>
                                <td>프리미엄</td>
                                <td>7,950</td>
                                <td>₹ 1,590,000</td>
                                <td>98%</td>
                                <td>
                                    <a href="store-performance.php?id=KTM-022" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>PKR-012</td>
                                <td>레이크사이드 로터리</td>
                                <td>포카라</td>
                                <td>프리미엄</td>
                                <td>7,325</td>
                                <td>₹ 1,465,000</td>
                                <td>95%</td>
                                <td>
                                    <a href="store-performance.php?id=PKR-012" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>KTM-035</td>
                                <td>네팔 로터리 센터</td>
                                <td>카트만두</td>
                                <td>일반</td>
                                <td>6,890</td>
                                <td>₹ 1,378,000</td>
                                <td>122%</td>
                                <td>
                                    <a href="store-performance.php?id=KTM-035" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>BKT-001</td>
                                <td>박타푸르 #03</td>
                                <td>박타푸르</td>
                                <td>독점</td>
                                <td>6,230</td>
                                <td>₹ 1,246,000</td>
                                <td>88%</td>
                                <td>
                                    <a href="store-performance.php?id=BKT-001" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>LLT-009</td>
                                <td>파탄 로터리 센터</td>
                                <td>랄리트푸르</td>
                                <td>일반</td>
                                <td>5,870</td>
                                <td>₹ 1,174,000</td>
                                <td>118%</td>
                                <td>
                                    <a href="store-performance.php?id=LLT-009" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                <a href="store-performance-list.php" class="btn btn-sm btn-info float-right">모든 판매점 성과 보기</a>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 판매점 터미널 상태 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">판매점 터미널 상태 요약</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>터미널 상태</th>
                                        <th>수량</th>
                                        <th>비율</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 -->
                                    <tr>
                                        <td><span class="badge badge-success">정상 작동</span></td>
                                        <td>182</td>
                                        <td>92.4%</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-warning">유지보수 중</span></td>
                                        <td>8</td>
                                        <td>4.1%</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-danger">비정상</span></td>
                                        <td>5</td>
                                        <td>2.5%</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-secondary">비활성</span></td>
                                        <td>2</td>
                                        <td>1.0%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>합계</strong></td>
                                        <td><strong>197</strong></td>
                                        <td><strong>100%</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div style="height: 200px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <span>터미널 상태 차트 (개발 중)</span>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5>최근 터미널 유지보수 기록</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>터미널 ID</th>
                                        <th>판매점</th>
                                        <th>유지보수 유형</th>
                                        <th>시작일</th>
                                        <th>예상 완료일</th>
                                        <th>상태</th>
                                        <th>기술자</th>
                                        <th>메모</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 -->
                                    <tr>
                                        <td>TRM-056</td>
                                        <td>네팔 마트 #23</td>
                                        <td>정기 점검</td>
                                        <td>2025-05-15</td>
                                        <td>2025-05-17</td>
                                        <td><span class="badge badge-warning">진행 중</span></td>
                                        <td>라메쉬 고랄리</td>
                                        <td>프린터 교체 필요</td>
                                    </tr>
                                    <tr>
                                        <td>TRM-128</td>
                                        <td>카트만두 센터 #05</td>
                                        <td>수리</td>
                                        <td>2025-05-14</td>
                                        <td>2025-05-16</td>
                                        <td><span class="badge badge-warning">진행 중</span></td>
                                        <td>비릇 샤르마</td>
                                        <td>터치스크린 고장</td>
                                    </tr>
                                    <tr>
                                        <td>TRM-098</td>
                                        <td>포카라 샵 #18</td>
                                        <td>소프트웨어 업데이트</td>
                                        <td>2025-05-13</td>
                                        <td>2025-05-14</td>
                                        <td><span class="badge badge-success">완료</span></td>
                                        <td>산딥 자이스왈</td>
                                        <td>최신 펌웨어 설치 완료</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer clearfix">
                <a href="../store/equipment-maintenance.php" class="btn btn-sm btn-info float-right">모든 유지보수 기록 보기</a>
            </div>
        </div>
        <!-- /.card -->
    </div>
</section>
<!-- /.content -->

<script>
// 페이지 로드 시 실행
document.addEventListener('DOMContentLoaded', function() {
    console.log('판매점 보고서 페이지가 로드되었습니다.');
    
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
            window.location.href = 'store-report.php';
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
});
</script>

<?php
// 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>
