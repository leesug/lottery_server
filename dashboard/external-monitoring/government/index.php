<?php
/**
 * 정부 접속 모니터링 메인 페이지
 */

// 공통 파일 포함
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "정부 모니터링";
$currentSection = "government";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = getDBConnection();

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
                        <h3>
                            <?php
                            // 총 판매량 (가상 데이터)
                            echo number_format(85456324000);
                            ?>원
                        </h3>
                        <p>최근 회차 판매액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/government/sales.php" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>
                            <?php
                            // 당첨자 수 (가상 데이터)
                            echo number_format(250);
                            ?>명
                        </h3>
                        <p>최근 회차 당첨자수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/government/winners.php" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>
                            <?php
                            // 당첨금액 (가상 데이터)
                            echo number_format(3125750000);
                            ?>원
                        </h3>
                        <p>최근 회차 1등 당첨금</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/government/prizes.php" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>
                            <?php
                            // 기금액수 (가상 데이터)
                            echo number_format(25636897200);
                            ?>원
                        </h3>
                        <p>최근 회차 기금액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/government/funds.php" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <!-- ./col -->
        </div>
        <!-- /.row -->

        <!-- 회차별 판매량 섹션 -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">회차별 판매량</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="salesChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/government/sales.php" class="btn btn-sm btn-primary float-right">판매량 상세 보기</a>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->

        <div class="row">
            <!-- 당첨자수 추이 섹션 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">당첨자수 추이</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="winnersChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/government/winners.php" class="btn btn-sm btn-primary float-right">당첨자 통계 상세 보기</a>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->

            <!-- 당첨금 추이 섹션 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">당첨금 추이</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="prizesChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/government/prizes.php" class="btn btn-sm btn-primary float-right">당첨금 통계 상세 보기</a>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->

        <!-- 기금액수 섹션 -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">기금액수 및 분배</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="chart">
                                    <canvas id="fundsChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>분야</th>
                                                <th>비율</th>
                                                <th>금액 (원)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- 샘플 데이터 - 실제로는 데이터베이스에서 가져와야 함 -->
                                            <tr>
                                                <td>문화예술</td>
                                                <td>18%</td>
                                                <td>4,614,641,496</td>
                                            </tr>
                                            <tr>
                                                <td>체육진흥</td>
                                                <td>20%</td>
                                                <td>5,127,379,440</td>
                                            </tr>
                                            <tr>
                                                <td>사회복지</td>
                                                <td>35%</td>
                                                <td>8,972,914,020</td>
                                            </tr>
                                            <tr>
                                                <td>재난구호</td>
                                                <td>15%</td>
                                                <td>3,845,534,580</td>
                                            </tr>
                                            <tr>
                                                <td>지역사회</td>
                                                <td>12%</td>
                                                <td>3,076,427,664</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/government/funds.php" class="btn btn-sm btn-primary float-right">기금 사용 현황 상세 보기</a>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

<!-- 차트 스크립트 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 회차별 판매량 차트
    var salesChartCanvas = document.getElementById('salesChart').getContext('2d');
    var salesChartData = {
        labels: ['제120회', '제121회', '제122회', '제123회', '제124회', '제125회'],
        datasets: [
            {
                label: '판매량 (억원)',
                backgroundColor: 'rgba(60,141,188,0.9)',
                borderColor: 'rgba(60,141,188,0.8)',
                pointRadius: 3,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: [783, 790, 798, 802, 823, 854]
            }
        ]
    };

    var salesChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            xAxes: [{
                gridLines: {
                    display: false
                }
            }],
            yAxes: [{
                gridLines: {
                    display: false
                },
                ticks: {
                    beginAtZero: false
                }
            }]
        }
    };

    var salesChart = new Chart(salesChartCanvas, {
        type: 'line',
        data: salesChartData,
        options: salesChartOptions
    });

    // 당첨자수 추이 차트
    var winnersChartCanvas = document.getElementById('winnersChart').getContext('2d');
    var winnersChartData = {
        labels: ['제120회', '제121회', '제122회', '제123회', '제124회', '제125회'],
        datasets: [
            {
                label: '1등 당첨자',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgb(255, 99, 132)',
                borderWidth: 1,
                data: [4, 5, 3, 4, 5, 3]
            },
            {
                label: '2등 당첨자',
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                borderColor: 'rgb(255, 159, 64)',
                borderWidth: 1,
                data: [10, 12, 9, 12, 15, 9]
            },
            {
                label: '3등 당첨자',
                backgroundColor: 'rgba(255, 205, 86, 0.2)',
                borderColor: 'rgb(255, 205, 86)',
                borderWidth: 1,
                data: [225, 234, 240, 225, 242, 238]
            }
        ]
    };

    var winnersChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
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
                    beginAtZero: true
                }
            }]
        }
    };

    var winnersChart = new Chart(winnersChartCanvas, {
        type: 'bar',
        data: winnersChartData,
        options: winnersChartOptions
    });

    // 당첨금 추이 차트
    var prizesChartCanvas = document.getElementById('prizesChart').getContext('2d');
    var prizesChartData = {
        labels: ['제120회', '제121회', '제122회', '제123회', '제124회', '제125회'],
        datasets: [
            {
                label: '1등 당첨금 (억원)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1,
                data: [25.5, 24.8, 26.2, 27.5, 28.4, 31.2]
            },
            {
                label: '2등 당첨금 (천만원)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgb(75, 192, 192)',
                borderWidth: 1,
                data: [4.8, 4.7, 4.9, 4.6, 4.8, 5.2]
            }
        ]
    };

    var prizesChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
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

    var prizesChart = new Chart(prizesChartCanvas, {
        type: 'line',
        data: prizesChartData,
        options: prizesChartOptions
    });

    // 기금액수 차트
    var fundsChartCanvas = document.getElementById('fundsChart').getContext('2d');
    var fundsChartData = {
        labels: ['문화예술', '체육진흥', '사회복지', '재난구호', '지역사회'],
        datasets: [
            {
                data: [18, 20, 35, 15, 12],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 205, 86, 0.8)',
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
        }
    };

    var fundsChart = new Chart(fundsChartCanvas, {
        type: 'doughnut',
        data: fundsChartData,
        options: fundsChartOptions
    });
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
