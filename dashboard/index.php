<?php
/**
 * 종합 대시보드 페이지
 * 세션 관리 기능이 비활성화되었습니다.
 */

// 설정 및 공통 함수
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

// 데이터베이스 연결
$db = getDbConnection();

// 현재 페이지 정보
$pageTitle = "종합 대시보드";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 추가 CSS
$extraCss = SERVER_URL . '/assets/css/dashboard.css';

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
                    <li class="breadcrumb-item active">홈</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 최상단 요약 정보 -->
        <div class="row">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-shopping-cart"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">오늘 판매량</span>
                        <span class="info-box-number">10,245</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-dollar-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">오늘 매출</span>
                        <span class="info-box-number">₹ 2,048,500</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-trophy"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">당첨금 지급</span>
                        <span class="info-box-number">₹ 758,400</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-store"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">활성 판매점</span>
                        <span class="info-box-number">156</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->

        <!-- 분야별 대시보드 선택 탭 -->
        <div class="card card-primary card-outline card-tabs">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <!-- 탭 네비게이션 - 스크롤 가능한 단일 라인 -->
                <div class="nav-tabs-scroll">
                    <ul class="nav nav-tabs" id="dashboard-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="tab-main" data-toggle="pill" href="#content-main" role="tab" aria-controls="content-main" aria-selected="true">종합</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-lottery" data-toggle="pill" href="#content-lottery" role="tab" aria-controls="content-lottery" aria-selected="false">복권 관리</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-sales" data-toggle="pill" href="#content-sales" role="tab" aria-controls="content-sales" aria-selected="false">판매 관리</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-draw" data-toggle="pill" href="#content-draw" role="tab" aria-controls="content-draw" aria-selected="false">추첨 관리</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-prize" data-toggle="pill" href="#content-prize" role="tab" aria-controls="content-prize" aria-selected="false">당첨금 관리</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-customer" data-toggle="pill" href="#content-customer" role="tab" aria-controls="content-customer" aria-selected="false">고객 관리</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-store" data-toggle="pill" href="#content-store" role="tab" aria-controls="content-store" aria-selected="false">판매점 관리</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-finance" data-toggle="pill" href="#content-finance" role="tab" aria-controls="content-finance" aria-selected="false">재무 관리</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-marketing" data-toggle="pill" href="#content-marketing" role="tab" aria-controls="content-marketing" aria-selected="false">마케팅 관리</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-reports" data-toggle="pill" href="#content-reports" role="tab" aria-controls="content-reports" aria-selected="false">통계 및 보고서</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-system" data-toggle="pill" href="#content-system" role="tab" aria-controls="content-system" aria-selected="false">시스템 관리</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-logs" data-toggle="pill" href="#content-logs" role="tab" aria-controls="content-logs" aria-selected="false">로그/감사</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="tab-content" id="dashboard-tabsContent">
                    <!-- 종합 대시보드 -->
                    <div class="tab-pane fade show active" id="content-main" role="tabpanel" aria-labelledby="tab-main">
                        <div class="row">
                            <div class="col-md-6">
                                <!-- 판매 추이 그래프 -->
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">판매 추이</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart">
                                            <canvas id="salesChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- 당첨금 지급 현황 -->
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">당첨금 지급 현황</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart">
                                            <canvas id="prizeChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <!-- 최근 당첨 번호 -->
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">최근 당첨 번호</h3>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>회차</th>
                                                    <th>추첨일</th>
                                                    <th>당첨 번호</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>124</td>
                                                    <td>2024-05-15</td>
                                                    <td>
                                                        <span class="badge bg-primary">12</span>
                                                        <span class="badge bg-primary">18</span>
                                                        <span class="badge bg-primary">24</span>
                                                        <span class="badge bg-primary">35</span>
                                                        <span class="badge bg-primary">42</span>
                                                        <span class="badge bg-warning">7</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>123</td>
                                                    <td>2024-05-08</td>
                                                    <td>
                                                        <span class="badge bg-primary">8</span>
                                                        <span class="badge bg-primary">15</span>
                                                        <span class="badge bg-primary">27</span>
                                                        <span class="badge bg-primary">33</span>
                                                        <span class="badge bg-primary">44</span>
                                                        <span class="badge bg-warning">2</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>122</td>
                                                    <td>2024-05-01</td>
                                                    <td>
                                                        <span class="badge bg-primary">3</span>
                                                        <span class="badge bg-primary">11</span>
                                                        <span class="badge bg-primary">22</span>
                                                        <span class="badge bg-primary">31</span>
                                                        <span class="badge bg-primary">42</span>
                                                        <span class="badge bg-warning">9</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- 최근 알림 -->
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">최근 알림</h3>
                                    </div>
                                    <div class="card-body p-0">
                                        <ul class="products-list product-list-in-card pl-2 pr-2">
                                            <li class="item">
                                                <div class="product-img">
                                                    <i class="fas fa-bell text-warning"></i>
                                                </div>
                                                <div class="product-info">
                                                    <a href="javascript:void(0)" class="product-title">시스템 백업
                                                        <span class="badge badge-success float-right">완료</span></a>
                                                    <span class="product-description">
                                                        자동 시스템 백업이 성공적으로 완료되었습니다.
                                                    </span>
                                                </div>
                                            </li>
                                            <li class="item">
                                                <div class="product-img">
                                                    <i class="fas fa-exclamation-triangle text-danger"></i>
                                                </div>
                                                <div class="product-info">
                                                    <a href="javascript:void(0)" class="product-title">판매점 연결 문제
                                                        <span class="badge badge-danger float-right">주의</span></a>
                                                    <span class="product-description">
                                                        3개의 판매점에서 연결 문제가 발생했습니다.
                                                    </span>
                                                </div>
                                            </li>
                                            <li class="item">
                                                <div class="product-img">
                                                    <i class="fas fa-info-circle text-info"></i>
                                                </div>
                                                <div class="product-info">
                                                    <a href="javascript:void(0)" class="product-title">새 복권 상품 출시
                                                        <span class="badge badge-info float-right">정보</span></a>
                                                    <span class="product-description">
                                                        새로운 복권 상품이 출시되었습니다. 세부 정보를 확인하세요.
                                                    </span>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 분야별 대시보드는 탭 클릭 시 AJAX로 로드됨 -->
                    <div class="tab-pane fade" id="content-lottery" role="tabpanel" aria-labelledby="tab-lottery">
                        <iframe src="<?php echo SERVER_URL; ?>/dashboard/lottery/" style="width: 100%; height: 800px; border: none; overflow: hidden;"></iframe>
                    </div>
                    
                    <div class="tab-pane fade" id="content-sales" role="tabpanel" aria-labelledby="tab-sales">
                        <iframe src="<?php echo SERVER_URL; ?>/dashboard/sales/" style="width: 100%; height: 800px; border: none; overflow: hidden;"></iframe>
                    </div>
                    
                    <div class="tab-pane fade" id="content-draw" role="tabpanel" aria-labelledby="tab-draw">
                        <iframe src="<?php echo SERVER_URL; ?>/dashboard/draw/" style="width: 100%; height: 800px; border: none; overflow: hidden;"></iframe>
                    </div>
                    
                    <div class="tab-pane fade" id="content-prize" role="tabpanel" aria-labelledby="tab-prize">
                        <iframe src="<?php echo SERVER_URL; ?>/dashboard/prize/" style="width: 100%; height: 800px; border: none; overflow: hidden;"></iframe>
                    </div>
                    
                    <div class="tab-pane fade" id="content-customer" role="tabpanel" aria-labelledby="tab-customer">
                        <div class="dashboard-loader text-center py-5">
                            <i class="fas fa-spinner fa-spin fa-3x"></i>
                            <p class="mt-3">고객 관리 대시보드를 로드 중입니다...</p>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="content-store" role="tabpanel" aria-labelledby="tab-store">
                        <div class="dashboard-loader text-center py-5">
                            <i class="fas fa-spinner fa-spin fa-3x"></i>
                            <p class="mt-3">판매점 관리 대시보드를 로드 중입니다...</p>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="content-finance" role="tabpanel" aria-labelledby="tab-finance">
                        <div class="dashboard-loader text-center py-5">
                            <i class="fas fa-spinner fa-spin fa-3x"></i>
                            <p class="mt-3">재무 관리 대시보드를 로드 중입니다...</p>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="content-marketing" role="tabpanel" aria-labelledby="tab-marketing">
                        <div class="dashboard-loader text-center py-5">
                            <i class="fas fa-spinner fa-spin fa-3x"></i>
                            <p class="mt-3">마케팅 관리 대시보드를 로드 중입니다...</p>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="content-reports" role="tabpanel" aria-labelledby="tab-reports">
                        <div class="dashboard-loader text-center py-5">
                            <i class="fas fa-spinner fa-spin fa-3x"></i>
                            <p class="mt-3">통계 및 보고서 대시보드를 로드 중입니다...</p>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="content-system" role="tabpanel" aria-labelledby="tab-system">
                        <iframe src="<?php echo SERVER_URL; ?>/dashboard/system/" style="width: 100%; height: 800px; border: none; overflow: hidden;"></iframe>
                    </div>
                    
                    <div class="tab-pane fade" id="content-logs" role="tabpanel" aria-labelledby="tab-logs">
                        <iframe src="<?php echo SERVER_URL; ?>/dashboard/logs/" style="width: 100%; height: 800px; border: none; overflow: hidden;"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 관련 JavaScript 파일 -->
<?php
// 추가 JavaScript
$extraJs = SERVER_URL . '/assets/js/dashboard-v2.js?v=' . time();

// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>