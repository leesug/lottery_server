<?php
/**
 * 재무 보고서 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "재무 보고서";
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
$transactionType = isset($_GET['transaction_type']) ? $_GET['transaction_type'] : 'all';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

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
                    <li class="breadcrumb-item active">재무 보고서</li>
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
                                <label for="transaction_type">거래 유형</label>
                                <select class="form-control" id="transaction_type" name="transaction_type">
                                    <option value="all" <?php if($transactionType == 'all') echo 'selected'; ?>>모든 거래</option>
                                    <option value="income" <?php if($transactionType == 'income') echo 'selected'; ?>>수입</option>
                                    <option value="expense" <?php if($transactionType == 'expense') echo 'selected'; ?>>지출</option>
                                    <option value="transfer" <?php if($transactionType == 'transfer') echo 'selected'; ?>>이체</option>
                                    <option value="adjustment" <?php if($transactionType == 'adjustment') echo 'selected'; ?>>조정</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="category">카테고리</label>
                                <select class="form-control" id="category" name="category">
                                    <option value="all" <?php if($category == 'all') echo 'selected'; ?>>모든 카테고리</option>
                                    <option value="sales" <?php if($category == 'sales') echo 'selected'; ?>>판매 수익</option>
                                    <option value="prize" <?php if($category == 'prize') echo 'selected'; ?>>당첨금 지급</option>
                                    <option value="commission" <?php if($category == 'commission') echo 'selected'; ?>>커미션</option>
                                    <option value="operations" <?php if($category == 'operations') echo 'selected'; ?>>운영 비용</option>
                                    <option value="payroll" <?php if($category == 'payroll') echo 'selected'; ?>>급여</option>
                                    <option value="marketing" <?php if($category == 'marketing') echo 'selected'; ?>>마케팅</option>
                                    <option value="it" <?php if($category == 'it') echo 'selected'; ?>>IT 및 시스템</option>
                                    <option value="tax" <?php if($category == 'tax') echo 'selected'; ?>>세금</option>
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
        
        <!-- 재무 요약 정보 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>₹ 52.8M</h3>
                        <p>총 수입</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-arrow-circle-down"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>₹ 38.6M</h3>
                        <p>총 지출</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-arrow-circle-up"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>₹ 14.2M</h3>
                        <p>순이익</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>26.9%</h3>
                        <p>이익률</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-percent"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 수입 및 지출 추이 차트 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">수입 및 지출 추이</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <div style="height: 300px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                        <span>수입 및 지출 추이 차트 (개발 중)</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 수입 및 지출 상세 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">수입 상세</h3>
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
                                        <th>수입 카테고리</th>
                                        <th>금액</th>
                                        <th>비율</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 -->
                                    <tr>
                                        <td>복권 판매</td>
                                        <td>₹ 50,450,000</td>
                                        <td>95.6%</td>
                                    </tr>
                                    <tr>
                                        <td>판매점 등록비</td>
                                        <td>₹ 850,000</td>
                                        <td>1.6%</td>
                                    </tr>
                                    <tr>
                                        <td>기타 수수료</td>
                                        <td>₹ 750,000</td>
                                        <td>1.4%</td>
                                    </tr>
                                    <tr>
                                        <td>광고 수입</td>
                                        <td>₹ 650,000</td>
                                        <td>1.2%</td>
                                    </tr>
                                    <tr>
                                        <td>이자 수입</td>
                                        <td>₹ 100,000</td>
                                        <td>0.2%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>합계</strong></td>
                                        <td><strong>₹ 52,800,000</strong></td>
                                        <td><strong>100%</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div style="height: 200px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; margin-top: 15px;">
                            <span>수입 분포 차트 (개발 중)</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">지출 상세</h3>
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
                                        <th>지출 카테고리</th>
                                        <th>금액</th>
                                        <th>비율</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 -->
                                    <tr>
                                        <td>당첨금 지급</td>
                                        <td>₹ 25,250,000</td>
                                        <td>65.4%</td>
                                    </tr>
                                    <tr>
                                        <td>판매점 커미션</td>
                                        <td>₹ 5,280,000</td>
                                        <td>13.7%</td>
                                    </tr>
                                    <tr>
                                        <td>급여 및 복리후생</td>
                                        <td>₹ 3,520,000</td>
                                        <td>9.1%</td>
                                    </tr>
                                    <tr>
                                        <td>마케팅 및 홍보</td>
                                        <td>₹ 1,850,000</td>
                                        <td>4.8%</td>
                                    </tr>
                                    <tr>
                                        <td>IT 및 시스템</td>
                                        <td>₹ 850,000</td>
                                        <td>2.2%</td>
                                    </tr>
                                    <tr>
                                        <td>사무실 및 관리</td>
                                        <td>₹ 650,000</td>
                                        <td>1.7%</td>
                                    </tr>
                                    <tr>
                                        <td>세금</td>
                                        <td>₹ 950,000</td>
                                        <td>2.5%</td>
                                    </tr>
                                    <tr>
                                        <td>기타 비용</td>
                                        <td>₹ 250,000</td>
                                        <td>0.6%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>합계</strong></td>
                                        <td><strong>₹ 38,600,000</strong></td>
                                        <td><strong>100%</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div style="height: 200px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; margin-top: 15px;">
                            <span>지출 분포 차트 (개발 중)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 복권 유형별 재무 성과 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">복권 유형별 재무 성과</h3>
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
                                <th>판매 수익</th>
                                <th>당첨금 지급</th>
                                <th>판매점 커미션</th>
                                <th>운영 비용</th>
                                <th>순이익</th>
                                <th>이익률</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>일일 복권</td>
                                <td>₹ 25,096,400</td>
                                <td>₹ 12,548,200</td>
                                <td>₹ 2,509,640</td>
                                <td>₹ 3,764,460</td>
                                <td>₹ 6,274,100</td>
                                <td>25.0%</td>
                            </tr>
                            <tr>
                                <td>주간 복권</td>
                                <td>₹ 19,046,000</td>
                                <td>₹ 9,523,000</td>
                                <td>₹ 1,904,600</td>
                                <td>₹ 2,856,900</td>
                                <td>₹ 4,761,500</td>
                                <td>25.0%</td>
                            </tr>
                            <tr>
                                <td>월간 복권</td>
                                <td>₹ 5,538,000</td>
                                <td>₹ 2,214,000</td>
                                <td>₹ 553,800</td>
                                <td>₹ 830,700</td>
                                <td>₹ 1,939,500</td>
                                <td>35.0%</td>
                            </tr>
                            <tr>
                                <td>특별 복권</td>
                                <td>₹ 3,120,000</td>
                                <td>₹ 964,800</td>
                                <td>₹ 312,000</td>
                                <td>₹ 624,000</td>
                                <td>₹ 1,219,200</td>
                                <td>39.1%</td>
                            </tr>
                            <tr>
                                <td><strong>합계</strong></td>
                                <td><strong>₹ 52,800,400</strong></td>
                                <td><strong>₹ 25,250,000</strong></td>
                                <td><strong>₹ 5,280,040</strong></td>
                                <td><strong>₹ 8,076,060</strong></td>
                                <td><strong>₹ 14,194,300</strong></td>
                                <td><strong>26.9%</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 기금 현황 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">기금 현황</h3>
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
                                        <th>기금 유형</th>
                                        <th>기금명</th>
                                        <th>현재 잔액</th>
                                        <th>총 할당액</th>
                                        <th>사용률</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 -->
                                    <tr>
                                        <td>당첨금</td>
                                        <td>롤오버 당첨금 기금</td>
                                        <td>₹ 75,650,000</td>
                                        <td>₹ 125,000,000</td>
                                        <td>60.5%</td>
                                    </tr>
                                    <tr>
                                        <td>자선</td>
                                        <td>사회 복지 기금</td>
                                        <td>₹ 12,850,000</td>
                                        <td>₹ 15,000,000</td>
                                        <td>85.7%</td>
                                    </tr>
                                    <tr>
                                        <td>개발</td>
                                        <td>지역 개발 지원 기금</td>
                                        <td>₹ 18,500,000</td>
                                        <td>₹ 20,000,000</td>
                                        <td>92.5%</td>
                                    </tr>
                                    <tr>
                                        <td>운영</td>
                                        <td>운영 예비 기금</td>
                                        <td>₹ 8,750,000</td>
                                        <td>₹ 10,000,000</td>
                                        <td>87.5%</td>
                                    </tr>
                                    <tr>
                                        <td>예비</td>
                                        <td>일반 예비 기금</td>
                                        <td>₹ 25,350,000</td>
                                        <td>₹ 30,000,000</td>
                                        <td>84.5%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div style="height: 300px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <span>기금 분포 차트 (개발 중)</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer clearfix">
                <a href="../finance/funds.php" class="btn btn-sm btn-info float-right">기금 관리로 이동</a>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 최근 주요 거래 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">최근 주요 거래</h3>
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
                                <th>거래 ID</th>
                                <th>거래 유형</th>
                                <th>카테고리</th>
                                <th>금액</th>
                                <th>거래일</th>
                                <th>설명</th>
                                <th>상태</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>TRX-25051601</td>
                                <td><span class="badge badge-success">수입</span></td>
                                <td>복권 판매</td>
                                <td>₹ 2,580,000</td>
                                <td>2025-05-16</td>
                                <td>일일 복권 판매 정산</td>
                                <td><span class="badge badge-success">완료</span></td>
                            </tr>
                            <tr>
                                <td>TRX-25051602</td>
                                <td><span class="badge badge-danger">지출</span></td>
                                <td>당첨금 지급</td>
                                <td>₹ 1,250,000</td>
                                <td>2025-05-16</td>
                                <td>대형 당첨금 지급 (ID: WIN-2505150023)</td>
                                <td><span class="badge badge-success">완료</span></td>
                            </tr>
                            <tr>
                                <td>TRX-25051603</td>
                                <td><span class="badge badge-info">이체</span></td>
                                <td>기금 할당</td>
                                <td>₹ 500,000</td>
                                <td>2025-05-16</td>
                                <td>롤오버 당첨금 기금으로 이체</td>
                                <td><span class="badge badge-success">완료</span></td>
                            </tr>
                            <tr>
                                <td>TRX-25051604</td>
                                <td><span class="badge badge-danger">지출</span></td>
                                <td>판매점 커미션</td>
                                <td>₹ 258,000</td>
                                <td>2025-05-16</td>
                                <td>판매점 커미션 정산 (15일차)</td>
                                <td><span class="badge badge-success">완료</span></td>
                            </tr>
                            <tr>
                                <td>TRX-25051605</td>
                                <td><span class="badge badge-danger">지출</span></td>
                                <td>마케팅</td>
                                <td>₹ 350,000</td>
                                <td>2025-05-16</td>
                                <td>신규 TV 광고 캠페인 계약금</td>
                                <td><span class="badge badge-warning">승인 대기</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                <a href="../finance/transactions.php" class="btn btn-sm btn-info float-right">모든 거래 보기</a>
            </div>
        </div>
        <!-- /.card -->
    </div>
</section>
<!-- /.content -->

<script>
// 페이지 로드 시 실행
document.addEventListener('DOMContentLoaded', function() {
    console.log('재무 보고서 페이지가 로드되었습니다.');
    
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
            window.location.href = 'financial-report.php';
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
