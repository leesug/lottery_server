<?php
/**
 * 재무 보고서 페이지
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_reports'];
checkPermissions($requiredPermissions);

// 페이지 제목 설정
$pageTitle = "재무 보고서";
$currentSection = "finance";
$currentPage = "reports";

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
        <!-- 필터 섹션 -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">보고서 필터</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="reportFilterForm" class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="reportPeriod">보고 기간</label>
                            <select id="reportPeriod" class="form-control">
                                <option value="daily">일별</option>
                                <option value="weekly">주별</option>
                                <option value="monthly" selected>월별</option>
                                <option value="quarterly">분기별</option>
                                <option value="yearly">연별</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="reportDate">날짜 선택</label>
                            <input type="month" id="reportDate" class="form-control" value="<?php echo date('Y-m'); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group pt-4 mt-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> 보고서 생성
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 보고서 개요 -->
        <div class="alert alert-info mb-4">
            <h5><i class="fas fa-info-circle"></i> 재무 보고서</h5>
            <p>다양한 유형의 재무 보고서를 확인할 수 있습니다. 아래 링크를 통해 각 보고서에 접근하세요.</p>
        </div>
        
        <!-- 보고서 링크 카드 -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-chart-line mr-1"></i>
                        매출 보고서
                    </div>
                    <div class="card-body">
                        <p>기간별, 판매점별, 복권 종류별 매출 데이터 분석 및 보고서를 제공합니다.</p>
                    </div>
                    <div class="card-footer">
                        <a href="reports-sales.php" class="btn btn-primary btn-sm">보고서 보기</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-danger text-white">
                        <i class="fas fa-chart-pie mr-1"></i>
                        비용 보고서
                    </div>
                    <div class="card-body">
                        <p>기간별, 카테고리별 비용 데이터 분석 및 보고서를 제공합니다.</p>
                    </div>
                    <div class="card-footer">
                        <a href="reports-expenses.php" class="btn btn-danger btn-sm">보고서 보기</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-exchange-alt mr-1"></i>
                        현금 흐름 보고서
                    </div>
                    <div class="card-body">
                        <p>기간별 수입과 지출의 흐름을 분석하고 순현금 흐름을 보여주는 보고서를 제공합니다.</p>
                    </div>
                    <div class="card-footer">
                        <a href="reports-cash-flow.php" class="btn btn-success btn-sm">보고서 보기</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-piggy-bank mr-1"></i>
                        기금 상태 보고서
                    </div>
                    <div class="card-body">
                        <p>기금별 상태, 할당 및 사용 현황을 분석하고 보고서를 제공합니다.</p>
                    </div>
                    <div class="card-footer">
                        <a href="reports-funds.php" class="btn btn-info btn-sm">보고서 보기</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-warning text-white">
                        <i class="fas fa-balance-scale mr-1"></i>
                        예산 대비 실적 보고서
                    </div>
                    <div class="card-body">
                        <p>예산 할당 대비 실제 사용 현황을 분석하고 보고서를 제공합니다.</p>
                    </div>
                    <div class="card-footer">
                        <a href="reports-budget-performance.php" class="btn btn-warning btn-sm">보고서 보기</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 재무 요약 -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>₹ 0</h3>
                        <p>총 매출</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>₹ 0</h3>
                        <p>순 수익</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>₹ 0</h3>
                        <p>당첨금 지급</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>₹ 0</h3>
                        <p>운영 비용</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 매출 차트 -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar mr-1"></i>
                    월별 매출 추이
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="revenueChart" style="height: 300px; width: 100%;">
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <p class="text-muted">차트 데이터 준비중...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 매출 분석 테이블 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-table mr-1"></i>
                    재무 상세 내역
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>항목</th>
                                <th>금액 (₹)</th>
                                <th>비율</th>
                                <th>전월 대비</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>총 매출</td>
                                <td>0.00</td>
                                <td>100%</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>당첨금 지급</td>
                                <td>0.00</td>
                                <td>0%</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>판매점 수수료</td>
                                <td>0.00</td>
                                <td>0%</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>운영 비용</td>
                                <td>0.00</td>
                                <td>0%</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>세금</td>
                                <td>0.00</td>
                                <td>0%</td>
                                <td>-</td>
                            </tr>
                            <tr class="table-success">
                                <td><strong>순 수익</strong></td>
                                <td><strong>0.00</strong></td>
                                <td>0%</td>
                                <td>-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 페이지 로드 시 콘솔에 디버깅 메시지
    console.log('재무 보고서 페이지 로드됨');
    
    // 필터 폼 제출 이벤트 처리
    const reportFilterForm = document.getElementById('reportFilterForm');
    if (reportFilterForm) {
        reportFilterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const period = document.getElementById('reportPeriod').value;
            const date = document.getElementById('reportDate').value;
            console.log('보고서 필터 적용:', period, date);
            // 여기에 필터 적용 로직 추가
        });
    }
});
</script>
