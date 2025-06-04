<?php
/**
 * 판매 보고서 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "판매 보고서";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 현재 월 및 연도
$currentMonth = date('m');
$currentYear = date('Y');
$currentDay = date('d');

// 필터링 옵션 처리
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;
$lotteryType = isset($_GET['lottery_type']) ? $_GET['lottery_type'] : 'all';
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
                    <li class="breadcrumb-item active">판매 보고서</li>
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
                                    <option value="daily" <?php if($period == 'daily') echo 'selected'; ?>>일별</option>
                                    <option value="weekly" <?php if($period == 'weekly') echo 'selected'; ?>>주별</option>
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
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="lottery_type">복권 유형</label>
                                <select class="form-control" id="lottery_type" name="lottery_type">
                                    <option value="all" <?php if($lotteryType == 'all') echo 'selected'; ?>>모든 복권</option>
                                    <option value="daily" <?php if($lotteryType == 'daily') echo 'selected'; ?>>일일 복권</option>
                                    <option value="weekly" <?php if($lotteryType == 'weekly') echo 'selected'; ?>>주간 복권</option>
                                    <option value="monthly" <?php if($lotteryType == 'monthly') echo 'selected'; ?>>월간 복권</option>
                                    <option value="special" <?php if($lotteryType == 'special') echo 'selected'; ?>>특별 복권</option>
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
                        <h3>₹ 52.8M</h3>
                        <p>총 판매액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>245,672</h3>
                        <p>판매된 티켓 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>156</h3>
                        <p>판매 지점 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>15.2%</h3>
                        <p>전월 대비 증가율</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 판매 추이 그래프 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">판매 추이</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <div style="height: 300px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                        <span>판매 추이 차트 (개발 중)</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 복권 유형별 판매 -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">복권 유형별 판매</h3>
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
                                        <th>복권 유형</th>
                                        <th>판매 수량</th>
                                        <th>판매 금액</th>
                                        <th>비율</th>
                                        <th>전월 대비</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 -->
                                    <tr>
                                        <td>일일 복권</td>
                                        <td>125,482</td>
                                        <td>₹ 25,096,400</td>
                                        <td>47.5%</td>
                                        <td><span class="text-success">▲ 12.3%</span></td>
                                    </tr>
                                    <tr>
                                        <td>주간 복권</td>
                                        <td>95,230</td>
                                        <td>₹ 19,046,000</td>
                                        <td>36.1%</td>
                                        <td><span class="text-success">▲ 5.8%</span></td>
                                    </tr>
                                    <tr>
                                        <td>월간 복권</td>
                                        <td>18,460</td>
                                        <td>₹ 5,538,000</td>
                                        <td>10.5%</td>
                                        <td><span class="text-danger">▼ 2.1%</span></td>
                                    </tr>
                                    <tr>
                                        <td>특별 복권</td>
                                        <td>6,500</td>
                                        <td>₹ 3,120,000</td>
                                        <td>5.9%</td>
                                        <td><span class="text-success">▲ 24.5%</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>합계</strong></td>
                                        <td><strong>245,672</strong></td>
                                        <td><strong>₹ 52,800,400</strong></td>
                                        <td><strong>100%</strong></td>
                                        <td><span class="text-success"><strong>▲ 8.6%</strong></span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">복권 유형별 판매 비율</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 250px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <span>복권 유형별 판매 비율 차트 (개발 중)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 지역별 판매 현황 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">지역별 판매 현황</h3>
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
                            <span>지역별 판매 현황 지도 (개발 중)</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>지역</th>
                                        <th>판매량</th>
                                        <th>비율</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 -->
                                    <tr>
                                        <td>카트만두</td>
                                        <td>₹ 18,480,140</td>
                                        <td>35.0%</td>
                                    </tr>
                                    <tr>
                                        <td>포카라</td>
                                        <td>₹ 9,504,072</td>
                                        <td>18.0%</td>
                                    </tr>
                                    <tr>
                                        <td>랄리트푸르</td>
                                        <td>₹ 6,336,048</td>
                                        <td>12.0%</td>
                                    </tr>
                                    <tr>
                                        <td>박타푸르</td>
                                        <td>₹ 4,224,032</td>
                                        <td>8.0%</td>
                                    </tr>
                                    <tr>
                                        <td>비랏나가르</td>
                                        <td>₹ 3,696,028</td>
                                        <td>7.0%</td>
                                    </tr>
                                    <tr>
                                        <td>기타 지역</td>
                                        <td>₹ 10,560,080</td>
                                        <td>20.0%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>합계</strong></td>
                                        <td><strong>₹ 52,800,400</strong></td>
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
        
        <!-- 상위 판매점 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">상위 10개 판매점</h3>
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
                                <th>판매점 코드</th>
                                <th>판매점명</th>
                                <th>지역</th>
                                <th>판매 수량</th>
                                <th>판매 금액</th>
                                <th>비율</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>1</td>
                                <td>KTM-001</td>
                                <td>네팔 마트 #23</td>
                                <td>카트만두</td>
                                <td>15,240</td>
                                <td>₹ 3,048,000</td>
                                <td>5.8%</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>KTM-015</td>
                                <td>카트만두 센터 #05</td>
                                <td>카트만두</td>
                                <td>12,850</td>
                                <td>₹ 2,570,000</td>
                                <td>4.9%</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>PKR-007</td>
                                <td>포카라 샵 #18</td>
                                <td>포카라</td>
                                <td>10,524</td>
                                <td>₹ 2,104,800</td>
                                <td>4.0%</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>LLT-003</td>
                                <td>랄리트푸르 #11</td>
                                <td>랄리트푸르</td>
                                <td>9,650</td>
                                <td>₹ 1,930,000</td>
                                <td>3.7%</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>BRT-005</td>
                                <td>비랏나가르 #07</td>
                                <td>비랏나가르</td>
                                <td>8,540</td>
                                <td>₹ 1,708,000</td>
                                <td>3.2%</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>KTM-022</td>
                                <td>센트럴 복권센터</td>
                                <td>카트만두</td>
                                <td>7,950</td>
                                <td>₹ 1,590,000</td>
                                <td>3.0%</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>PKR-012</td>
                                <td>레이크사이드 로터리</td>
                                <td>포카라</td>
                                <td>7,325</td>
                                <td>₹ 1,465,000</td>
                                <td>2.8%</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>KTM-035</td>
                                <td>네팔 로터리 센터</td>
                                <td>카트만두</td>
                                <td>6,890</td>
                                <td>₹ 1,378,000</td>
                                <td>2.6%</td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>BKT-001</td>
                                <td>박타푸르 #03</td>
                                <td>박타푸르</td>
                                <td>6,230</td>
                                <td>₹ 1,246,000</td>
                                <td>2.4%</td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>LLT-009</td>
                                <td>파탄 로터리 센터</td>
                                <td>랄리트푸르</td>
                                <td>5,870</td>
                                <td>₹ 1,174,000</td>
                                <td>2.2%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                <a href="store-sales-report.php" class="btn btn-sm btn-info float-right">모든 판매점 보기</a>
            </div>
        </div>
        <!-- /.card -->
    </div>
</section>
<!-- /.content -->

<script>
// 페이지 로드 시 실행
document.addEventListener('DOMContentLoaded', function() {
    console.log('판매 보고서 페이지가 로드되었습니다.');
    
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
            window.location.href = 'sales-report.php';
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
