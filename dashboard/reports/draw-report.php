<?php
/**
 * 추첨 보고서 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "추첨 보고서";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 현재 연도 및 월
$currentYear = date('Y');
$currentMonth = date('m');
$currentDay = date('d');

// 필터링 옵션 처리
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;
$lotteryType = isset($_GET['lottery_type']) ? $_GET['lottery_type'] : 'all';

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
                    <li class="breadcrumb-item active">추첨 보고서</li>
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
                        <h3>58</h3>
                        <p>총 추첨 횟수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-random"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>₹ 38.2M</h3>
                        <p>총 당첨금 지급</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>725</h3>
                        <p>당첨자 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>₹ 15M</h3>
                        <p>최대 당첨금</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-crown"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 추첨 현황 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">추첨 현황</h3>
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
                                <th>추첨 ID</th>
                                <th>복권 유형</th>
                                <th>추첨 일시</th>
                                <th>총 판매 티켓</th>
                                <th>당첨자 수</th>
                                <th>당첨금 총액</th>
                                <th>최대 당첨금</th>
                                <th>상태</th>
                                <th>상세 보기</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>DRW-25050501</td>
                                <td>일일 복권</td>
                                <td>2025-05-01 15:00</td>
                                <td>15,482</td>
                                <td>125</td>
                                <td>₹ 4,320,000</td>
                                <td>₹ 1,000,000</td>
                                <td><span class="badge badge-success">완료됨</span></td>
                                <td>
                                    <a href="draw-details.php?id=DRW-25050501" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>DRW-25050801</td>
                                <td>주간 복권</td>
                                <td>2025-05-08 15:00</td>
                                <td>38,250</td>
                                <td>245</td>
                                <td>₹ 12,800,000</td>
                                <td>₹ 5,000,000</td>
                                <td><span class="badge badge-success">완료됨</span></td>
                                <td>
                                    <a href="draw-details.php?id=DRW-25050801" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>DRW-25051501</td>
                                <td>월간 복권</td>
                                <td>2025-05-15 15:00</td>
                                <td>8,745</td>
                                <td>85</td>
                                <td>₹ 15,500,000</td>
                                <td>₹ 10,000,000</td>
                                <td><span class="badge badge-success">완료됨</span></td>
                                <td>
                                    <a href="draw-details.php?id=DRW-25051501" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>DRW-25051601</td>
                                <td>일일 복권</td>
                                <td>2025-05-16 15:00</td>
                                <td>12,345</td>
                                <td>110</td>
                                <td>₹ 3,850,000</td>
                                <td>₹ 1,000,000</td>
                                <td><span class="badge badge-success">완료됨</span></td>
                                <td>
                                    <a href="draw-details.php?id=DRW-25051601" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>DRW-25051801</td>
                                <td>주간 복권</td>
                                <td>2025-05-18 15:00</td>
                                <td>42,850</td>
                                <td>0</td>
                                <td>₹ 0</td>
                                <td>₹ 0</td>
                                <td><span class="badge badge-warning">예정됨</span></td>
                                <td>
                                    <a href="draw-details.php?id=DRW-25051801" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                <ul class="pagination pagination-sm m-0 float-right">
                    <li class="page-item"><a class="page-link" href="#">&laquo;</a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">&raquo;</a></li>
                </ul>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 복권 유형별 추첨 통계 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">복권 유형별 당첨금 분포</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <span>복권 유형별 당첨금 분포 차트 (개발 중)</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">복권 유형별 당첨자 분포</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <span>복권 유형별 당첨자 분포 차트 (개발 중)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 당첨금 등급별 통계 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">당첨금 등급별 통계</h3>
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
                                <th>당첨 등급</th>
                                <th>당첨 기준</th>
                                <th>당첨자 수</th>
                                <th>당첨금 (단위 당)</th>
                                <th>총 당첨금</th>
                                <th>비율</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>1등</td>
                                <td>모든 번호 일치</td>
                                <td>3</td>
                                <td>₹ 10,000,000</td>
                                <td>₹ 30,000,000</td>
                                <td>78.5%</td>
                            </tr>
                            <tr>
                                <td>2등</td>
                                <td>6개 중 5개 + 보너스 번호</td>
                                <td>12</td>
                                <td>₹ 200,000</td>
                                <td>₹ 2,400,000</td>
                                <td>6.3%</td>
                            </tr>
                            <tr>
                                <td>3등</td>
                                <td>6개 중 5개 일치</td>
                                <td>48</td>
                                <td>₹ 50,000</td>
                                <td>₹ 2,400,000</td>
                                <td>6.3%</td>
                            </tr>
                            <tr>
                                <td>4등</td>
                                <td>6개 중 4개 일치</td>
                                <td>210</td>
                                <td>₹ 5,000</td>
                                <td>₹ 1,050,000</td>
                                <td>2.7%</td>
                            </tr>
                            <tr>
                                <td>5등</td>
                                <td>6개 중 3개 일치</td>
                                <td>452</td>
                                <td>₹ 5,000</td>
                                <td>₹ 2,350,000</td>
                                <td>6.2%</td>
                            </tr>
                            <tr>
                                <td><strong>합계</strong></td>
                                <td></td>
                                <td><strong>725</strong></td>
                                <td></td>
                                <td><strong>₹ 38,200,000</strong></td>
                                <td><strong>100%</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 당첨 번호 통계 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">당첨 번호 통계</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>가장 많이 당첨된 번호 (상위 10개)</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>번호</th>
                                        <th>당첨 횟수</th>
                                        <th>가장 최근 당첨</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 -->
                                    <tr>
                                        <td>23</td>
                                        <td>12</td>
                                        <td>2025-05-15</td>
                                    </tr>
                                    <tr>
                                        <td>7</td>
                                        <td>11</td>
                                        <td>2025-05-16</td>
                                    </tr>
                                    <tr>
                                        <td>34</td>
                                        <td>11</td>
                                        <td>2025-05-08</td>
                                    </tr>
                                    <tr>
                                        <td>17</td>
                                        <td>10</td>
                                        <td>2025-05-01</td>
                                    </tr>
                                    <tr>
                                        <td>42</td>
                                        <td>9</td>
                                        <td>2025-05-08</td>
                                    </tr>
                                    <tr>
                                        <td>13</td>
                                        <td>9</td>
                                        <td>2025-04-24</td>
                                    </tr>
                                    <tr>
                                        <td>29</td>
                                        <td>8</td>
                                        <td>2025-05-15</td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td>8</td>
                                        <td>2025-04-17</td>
                                    </tr>
                                    <tr>
                                        <td>39</td>
                                        <td>7</td>
                                        <td>2025-05-16</td>
                                    </tr>
                                    <tr>
                                        <td>21</td>
                                        <td>7</td>
                                        <td>2025-04-10</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5>가장 적게 당첨된 번호 (하위 10개)</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>번호</th>
                                        <th>당첨 횟수</th>
                                        <th>가장 최근 당첨</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 -->
                                    <tr>
                                        <td>41</td>
                                        <td>1</td>
                                        <td>2025-03-12</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>1</td>
                                        <td>2025-02-25</td>
                                    </tr>
                                    <tr>
                                        <td>48</td>
                                        <td>2</td>
                                        <td>2025-04-03</td>
                                    </tr>
                                    <tr>
                                        <td>36</td>
                                        <td>2</td>
                                        <td>2025-03-27</td>
                                    </tr>
                                    <tr>
                                        <td>19</td>
                                        <td>2</td>
                                        <td>2025-03-20</td>
                                    </tr>
                                    <tr>
                                        <td>45</td>
                                        <td>3</td>
                                        <td>2025-04-24</td>
                                    </tr>
                                    <tr>
                                        <td>11</td>
                                        <td>3</td>
                                        <td>2025-04-17</td>
                                    </tr>
                                    <tr>
                                        <td>25</td>
                                        <td>3</td>
                                        <td>2025-04-10</td>
                                    </tr>
                                    <tr>
                                        <td>30</td>
                                        <td>3</td>
                                        <td>2025-03-27</td>
                                    </tr>
                                    <tr>
                                        <td>8</td>
                                        <td>3</td>
                                        <td>2025-03-20</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5>숫자 빈도 분포</h5>
                        <div style="height: 200px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <span>숫자 빈도 분포 차트 (개발 중)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card -->
    </div>
</section>
<!-- /.content -->

<script>
// 페이지 로드 시 실행
document.addEventListener('DOMContentLoaded', function() {
    console.log('추첨 보고서 페이지가 로드되었습니다.');
    
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
            window.location.href = 'draw-report.php';
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
