<?php
/**
 * 고객 보고서 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "고객 보고서";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 현재 연도 및 월
$currentYear = date('Y');
$currentMonth = date('m');

// 필터링 옵션 처리
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;
$customerSegment = isset($_GET['customer_segment']) ? $_GET['customer_segment'] : 'all';
$region = isset($_GET['region']) ? $_GET['region'] : 'all';

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
                    <li class="breadcrumb-item active">고객 보고서</li>
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
                                <label for="customer_segment">고객 세그먼트</label>
                                <select class="form-control" id="customer_segment" name="customer_segment">
                                    <option value="all" <?php if($customerSegment == 'all') echo 'selected'; ?>>모든 고객</option>
                                    <option value="new" <?php if($customerSegment == 'new') echo 'selected'; ?>>신규 고객</option>
                                    <option value="active" <?php if($customerSegment == 'active') echo 'selected'; ?>>활성 고객</option>
                                    <option value="inactive" <?php if($customerSegment == 'inactive') echo 'selected'; ?>>휴면 고객</option>
                                    <option value="vip" <?php if($customerSegment == 'vip') echo 'selected'; ?>>VIP 고객</option>
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
                        <h3>15,482</h3>
                        <p>총 고객 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>1,245</h3>
                        <p>이번 달 신규 고객</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>₹ 3,420</h3>
                        <p>고객당 평균 구매액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>12.8%</h3>
                        <p>전월 대비 고객 증가율</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 고객 성장 추이 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">고객 성장 추이</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <div style="height: 300px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                        <span>고객 성장 추이 차트 (개발 중)</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 고객 세그먼트 분석 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">고객 세그먼트 분포</h3>
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
                                        <th>세그먼트</th>
                                        <th>고객 수</th>
                                        <th>비율</th>
                                        <th>전월 대비</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 -->
                                    <tr>
                                        <td>신규 고객 (< 30일)</td>
                                        <td>1,245</td>
                                        <td>8.0%</td>
                                        <td><span class="text-success">▲ 12.8%</span></td>
                                    </tr>
                                    <tr>
                                        <td>활성 고객</td>
                                        <td>9,845</td>
                                        <td>63.6%</td>
                                        <td><span class="text-success">▲ 5.3%</span></td>
                                    </tr>
                                    <tr>
                                        <td>휴면 고객</td>
                                        <td>3,850</td>
                                        <td>24.9%</td>
                                        <td><span class="text-danger">▼ 2.1%</span></td>
                                    </tr>
                                    <tr>
                                        <td>VIP 고객</td>
                                        <td>542</td>
                                        <td>3.5%</td>
                                        <td><span class="text-success">▲ 7.5%</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>합계</strong></td>
                                        <td><strong>15,482</strong></td>
                                        <td><strong>100%</strong></td>
                                        <td><span class="text-success"><strong>▲ 4.8%</strong></span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">고객 세그먼트 분포</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 250px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <span>고객 세그먼트 분포 차트 (개발 중)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 고객 구매 행동 분석 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">고객 구매 행동 분석</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>세그먼트별 평균 구매액</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>세그먼트</th>
                                        <th>평균 구매액 (월)</th>
                                        <th>평균 구매 횟수 (월)</th>
                                        <th>1회 평균 구매액</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 -->
                                    <tr>
                                        <td>신규 고객</td>
                                        <td>₹ 1,850</td>
                                        <td>2.3</td>
                                        <td>₹ 804</td>
                                    </tr>
                                    <tr>
                                        <td>활성 고객</td>
                                        <td>₹ 3,240</td>
                                        <td>4.5</td>
                                        <td>₹ 720</td>
                                    </tr>
                                    <tr>
                                        <td>휴면 고객</td>
                                        <td>₹ 580</td>
                                        <td>0.8</td>
                                        <td>₹ 725</td>
                                    </tr>
                                    <tr>
                                        <td>VIP 고객</td>
                                        <td>₹ 12,450</td>
                                        <td>10.8</td>
                                        <td>₹ 1,153</td>
                                    </tr>
                                    <tr>
                                        <td><strong>전체 평균</strong></td>
                                        <td><strong>₹ 3,420</strong></td>
                                        <td><strong>3.9</strong></td>
                                        <td><strong>₹ 877</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5>복권 유형별 선호도</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>복권 유형</th>
                                        <th>구매 비율</th>
                                        <th>신규 고객</th>
                                        <th>활성 고객</th>
                                        <th>VIP 고객</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 -->
                                    <tr>
                                        <td>일일 복권</td>
                                        <td>47.5%</td>
                                        <td>58.2%</td>
                                        <td>45.3%</td>
                                        <td>36.8%</td>
                                    </tr>
                                    <tr>
                                        <td>주간 복권</td>
                                        <td>36.1%</td>
                                        <td>32.5%</td>
                                        <td>38.4%</td>
                                        <td>42.1%</td>
                                    </tr>
                                    <tr>
                                        <td>월간 복권</td>
                                        <td>10.5%</td>
                                        <td>6.8%</td>
                                        <td>11.2%</td>
                                        <td>11.6%</td>
                                    </tr>
                                    <tr>
                                        <td>특별 복권</td>
                                        <td>5.9%</td>
                                        <td>2.5%</td>
                                        <td>5.1%</td>
                                        <td>9.5%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5>구매 빈도 분석</h5>
                        <div style="height: 250px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <span>구매 빈도 분석 차트 (개발 중)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 지역별 고객 분포 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">지역별 고객 분포</h3>
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
                            <span>지역별 고객 분포 지도 (개발 중)</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>지역</th>
                                        <th>고객 수</th>
                                        <th>비율</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 -->
                                    <tr>
                                        <td>카트만두</td>
                                        <td>6,350</td>
                                        <td>41.0%</td>
                                    </tr>
                                    <tr>
                                        <td>포카라</td>
                                        <td>2,865</td>
                                        <td>18.5%</td>
                                    </tr>
                                    <tr>
                                        <td>랄리트푸르</td>
                                        <td>1,950</td>
                                        <td>12.6%</td>
                                    </tr>
                                    <tr>
                                        <td>박타푸르</td>
                                        <td>1,240</td>
                                        <td>8.0%</td>
                                    </tr>
                                    <tr>
                                        <td>비랏나가르</td>
                                        <td>1,180</td>
                                        <td>7.6%</td>
                                    </tr>
                                    <tr>
                                        <td>기타 지역</td>
                                        <td>1,897</td>
                                        <td>12.3%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>합계</strong></td>
                                        <td><strong>15,482</strong></td>
                                        <td><strong>100%</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 상위 고객 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">상위 10명 고객 (구매액 기준)</h3>
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
                                <th>순위</th>
                                <th>고객 ID</th>
                                <th>이름</th>
                                <th>지역</th>
                                <th>총 구매액</th>
                                <th>구매 횟수</th>
                                <th>당첨 횟수</th>
                                <th>총 당첨금</th>
                                <th>세그먼트</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>1</td>
                                <td>CUST-001245</td>
                                <td>라메쉬 쿠마르</td>
                                <td>카트만두</td>
                                <td>₹ 152,450</td>
                                <td>124</td>
                                <td>15</td>
                                <td>₹ 240,000</td>
                                <td><span class="badge badge-warning">VIP</span></td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>CUST-003789</td>
                                <td>아니타 샤르마</td>
                                <td>포카라</td>
                                <td>₹ 145,850</td>
                                <td>118</td>
                                <td>12</td>
                                <td>₹ 180,000</td>
                                <td><span class="badge badge-warning">VIP</span></td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>CUST-002534</td>
                                <td>비나야크 타파</td>
                                <td>카트만두</td>
                                <td>₹ 142,200</td>
                                <td>115</td>
                                <td>10</td>
                                <td>₹ 125,000</td>
                                <td><span class="badge badge-warning">VIP</span></td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>CUST-004152</td>
                                <td>수니타 라이</td>
                                <td>랄리트푸르</td>
                                <td>₹ 138,750</td>
                                <td>112</td>
                                <td>8</td>
                                <td>₹ 95,000</td>
                                <td><span class="badge badge-warning">VIP</span></td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>CUST-006547</td>
                                <td>프라카쉬 바타라이</td>
                                <td>카트만두</td>
                                <td>₹ 125,400</td>
                                <td>102</td>
                                <td>11</td>
                                <td>₹ 285,000</td>
                                <td><span class="badge badge-warning">VIP</span></td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>CUST-002187</td>
                                <td>숙메쉬 파틸</td>
                                <td>포카라</td>
                                <td>₹ 118,950</td>
                                <td>96</td>
                                <td>7</td>
                                <td>₹ 75,000</td>
                                <td><span class="badge badge-warning">VIP</span></td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>CUST-007823</td>
                                <td>샨카르 바스넷</td>
                                <td>비랏나가르</td>
                                <td>₹ 112,350</td>
                                <td>91</td>
                                <td>9</td>
                                <td>₹ 120,000</td>
                                <td><span class="badge badge-warning">VIP</span></td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>CUST-005628</td>
                                <td>마누샤 타파</td>
                                <td>카트만두</td>
                                <td>₹ 108,750</td>
                                <td>88</td>
                                <td>6</td>
                                <td>₹ 65,000</td>
                                <td><span class="badge badge-warning">VIP</span></td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>CUST-004591</td>
                                <td>소니아 마하잔</td>
                                <td>랄리트푸르</td>
                                <td>₹ 102,400</td>
                                <td>83</td>
                                <td>5</td>
                                <td>₹ 55,000</td>
                                <td><span class="badge badge-warning">VIP</span></td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>CUST-001872</td>
                                <td>아슈토쉬 찬드</td>
                                <td>카트만두</td>
                                <td>₹ 98,650</td>
                                <td>80</td>
                                <td>8</td>
                                <td>₹ 90,000</td>
                                <td><span class="badge badge-warning">VIP</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                <a href="top-customers.php" class="btn btn-sm btn-info float-right">모든 VIP 고객 보기</a>
            </div>
        </div>
        <!-- /.card -->
    </div>
</section>
<!-- /.content -->

<script>
// 페이지 로드 시 실행
document.addEventListener('DOMContentLoaded', function() {
    console.log('고객 보고서 페이지가 로드되었습니다.');
    
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
            window.location.href = 'customer-report.php';
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
