<?php
/**
 * 판매 대시보드 페이지
 */

// 세션 시작 및 인증 체크
session_start();

// 설정 및 공통 함수
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

// 인증 확인
check_auth();

// 데이터베이스 연결
$db = get_db_connection();

// 오늘 날짜
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$thisWeekStart = date('Y-m-d', strtotime('this week Monday'));
$thisMonthStart = date('Y-m-01');

// 통계 데이터 조회
$todaySales = 0;
$yesterdaySales = 0;
$weekSales = 0;
$monthSales = 0;
$topSellingProducts = [];
$topSellingStores = [];
$salesByRegion = [];
$salesTrend = [];

try {
    // 오늘 판매량 조회
    $stmt = $db->prepare("SELECT COALESCE(SUM(price), 0) AS sales FROM tickets WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $todaySales = $result['sales'];
    
    // 어제 판매량 조회
    $stmt = $db->prepare("SELECT COALESCE(SUM(price), 0) AS sales FROM tickets WHERE DATE(created_at) = ?");
    $stmt->execute([$yesterday]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $yesterdaySales = $result['sales'];
    
    // 이번 주 판매량 조회
    $stmt = $db->prepare("SELECT COALESCE(SUM(price), 0) AS sales FROM tickets WHERE DATE(created_at) >= ?");
    $stmt->execute([$thisWeekStart]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $weekSales = $result['sales'];
    
    // 이번 달 판매량 조회
    $stmt = $db->prepare("SELECT COALESCE(SUM(price), 0) AS sales FROM tickets WHERE DATE(created_at) >= ?");
    $stmt->execute([$thisMonthStart]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $monthSales = $result['sales'];
    
    // 상품별 판매 상위 5개 조회
    $stmt = $db->prepare("
        SELECT 
            lp.name AS product_name, 
            COUNT(t.id) AS ticket_count,
            SUM(t.price) AS total_sales
        FROM 
            tickets t
        JOIN 
            lottery_products lp ON t.product_id = lp.id
        WHERE 
            DATE(t.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY 
            t.product_id
        ORDER BY 
            total_sales DESC
        LIMIT 5
    ");
    $stmt->execute();
    $topSellingProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 판매점별 판매 상위 5개 조회
    $stmt = $db->prepare("
        SELECT 
            s.name AS store_name, 
            COUNT(t.id) AS ticket_count,
            SUM(t.price) AS total_sales
        FROM 
            tickets t
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        JOIN
            stores s ON tm.store_id = s.id
        WHERE 
            DATE(t.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY 
            s.id
        ORDER BY 
            total_sales DESC
        LIMIT 5
    ");
    $stmt->execute();
    $topSellingStores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 지역별 판매 현황 조회
    $stmt = $db->prepare("
        SELECT 
            s.region_id, 
            r.name AS region_name,
            COUNT(t.id) AS ticket_count,
            SUM(t.price) AS total_sales
        FROM 
            tickets t
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        JOIN
            stores s ON tm.store_id = s.id
        JOIN
            regions r ON s.region_id = r.id
        WHERE 
            DATE(t.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY 
            s.region_id
        ORDER BY 
            total_sales DESC
    ");
    $stmt->execute();
    $salesByRegion = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 최근 30일 일별 판매 추이 조회
    $stmt = $db->prepare("
        SELECT 
            DATE(created_at) AS sale_date,
            SUM(price) AS daily_sales
        FROM 
            tickets
        WHERE 
            DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY 
            DATE(created_at)
        ORDER BY 
            sale_date ASC
    ");
    $stmt->execute();
    $salesTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // 에러 로깅
    error_log("Database error: " . $e->getMessage());
    // 에러 발생 시 기본값 유지
}

// 헤더 포함
$pageTitle = "판매 대시보드";
include '../templates/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">판매 대시보드</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">홈</a></li>
                        <li class="breadcrumb-item active">판매 대시보드</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- 상단 판매량 요약 카드 -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?php echo number_format($todaySales); ?></h3>
                            <p>오늘 판매액 (NPR)</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <a href="/server/dashboard/sales/status.php" class="small-box-footer">
                            상세 보기 <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php echo number_format($yesterdaySales); ?></h3>
                            <p>어제 판매액 (NPR)</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <a href="/server/dashboard/sales/history.php" class="small-box-footer">
                            상세 보기 <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?php echo number_format($weekSales); ?></h3>
                            <p>이번 주 판매액 (NPR)</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                        <a href="/server/dashboard/reports/sales-stats.php" class="small-box-footer">
                            상세 보기 <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?php echo number_format($monthSales); ?></h3>
                            <p>이번 달 판매액 (NPR)</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <a href="/server/dashboard/reports/sales-stats.php" class="small-box-footer">
                            상세 보기 <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- 판매 추이 차트 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">최근 30일 판매 추이</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="salesTrendChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 상품별 및 판매점별 차트 -->
            <div class="row">
                <div class="col-md-6">
                    <!-- 상품별 판매 현황 차트 -->
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">상품별 판매 현황 (최근 30일)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="productChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <!-- 판매점별 판매 현황 차트 -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">판매점별 판매 현황 (최근 30일)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="storeChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 지역별 판매 현황 및 TOP 판매점 -->
            <div class="row">
                <div class="col-md-7">
                    <!-- 지역별 판매 현황 지도 -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">지역별 판매 현황</h3>
                        </div>
                        <div class="card-body">
                            <div id="map-container" style="height: 400px;">
                                <!-- 여기에 지도가 들어갈 예정이나, 실제 구현은 별도로 필요 -->
                                <div class="d-flex justify-content-center align-items-center h-100 text-center">
                                    <div>
                                        <i class="fas fa-map-marked-alt fa-4x text-secondary mb-3"></i>
                                        <p>지역별 판매 현황 지도는 추가 설정이 필요합니다.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 지역별 판매 통계 테이블 -->
                            <div class="table-responsive mt-4">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>지역</th>
                                            <th>판매 수량</th>
                                            <th>판매액 (NPR)</th>
                                            <th>비율</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $totalRegionSales = array_sum(array_column($salesByRegion, 'total_sales'));
                                        foreach ($salesByRegion as $region) {
                                            $percentage = $totalRegionSales > 0 ? ($region['total_sales'] / $totalRegionSales * 100) : 0;
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($region['region_name']) . "</td>";
                                            echo "<td>" . number_format($region['ticket_count']) . "</td>";
                                            echo "<td>" . number_format($region['total_sales']) . "</td>";
                                            echo "<td>" . number_format($percentage, 1) . "%</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <!-- TOP 5 판매점 -->
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">TOP 5 판매점 (최근 30일)</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>순위</th>
                                            <th>판매점</th>
                                            <th>판매 수량</th>
                                            <th>판매액 (NPR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $rank = 1;
                                        foreach ($topSellingStores as $store) {
                                            echo "<tr>";
                                            echo "<td>" . $rank++ . "</td>";
                                            echo "<td>" . htmlspecialchars($store['store_name']) . "</td>";
                                            echo "<td>" . number_format($store['ticket_count']) . "</td>";
                                            echo "<td>" . number_format($store['total_sales']) . "</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="/server/dashboard/store/performance.php" class="btn btn-sm btn-warning float-right">판매점 성과 상세 보기</a>
                        </div>
                    </div>
                    
                    <!-- TOP 5 판매 상품 -->
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">TOP 5 판매 상품 (최근 30일)</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>순위</th>
                                            <th>상품명</th>
                                            <th>판매 수량</th>
                                            <th>판매액 (NPR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $rank = 1;
                                        foreach ($topSellingProducts as $product) {
                                            echo "<tr>";
                                            echo "<td>" . $rank++ . "</td>";
                                            echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
                                            echo "<td>" . number_format($product['ticket_count']) . "</td>";
                                            echo "<td>" . number_format($product['total_sales']) . "</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="/server/dashboard/lottery/products.php" class="btn btn-sm btn-success float-right">상품 상세 보기</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>

<!-- Chart.js 스크립트 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 판매 추이 차트
    var salesTrendData = <?php echo json_encode($salesTrend); ?>;
    var salesTrendLabels = salesTrendData.map(function(item) {
        var date = new Date(item.sale_date);
        return (date.getMonth()+1) + '/' + date.getDate();
    });
    var salesTrendValues = salesTrendData.map(function(item) {
        return item.daily_sales;
    });
    
    var salesTrendChartCanvas = document.getElementById('salesTrendChart').getContext('2d');
    var salesTrendChartData = {
        labels: salesTrendLabels,
        datasets: [
            {
                label: '일별 판매액 (NPR)',
                backgroundColor: 'rgba(60,141,188,0.2)',
                borderColor: 'rgba(60,141,188,1)',
                pointRadius: 3,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: salesTrendValues,
                fill: true
            }
        ]
    };
    
    var salesTrendChartOptions = {
        maintainAspectRatio: false,
        responsive: true,
        legend: {
            display: true
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
                    beginAtZero: true,
                    callback: function(value) {
                        return new Intl.NumberFormat().format(value);
                    }
                }
            }]
        },
        tooltips: {
            callbacks: {
                label: function(tooltipItem, data) {
                    return data.datasets[tooltipItem.datasetIndex].label + ': ' + 
                        new Intl.NumberFormat().format(tooltipItem.yLabel);
                }
            }
        }
    };
    
    new Chart(salesTrendChartCanvas, {
        type: 'line',
        data: salesTrendChartData,
        options: salesTrendChartOptions
    });
    
    // 상품별 판매 차트
    var productChartData = <?php echo json_encode($topSellingProducts); ?>;
    var productLabels = productChartData.map(function(item) {
        return item.product_name;
    });
    var productSalesValues = productChartData.map(function(item) {
        return item.total_sales;
    });
    
    var productChartCanvas = document.getElementById('productChart').getContext('2d');
    var productChartConfig = {
        type: 'pie',
        data: {
            labels: productLabels,
            datasets: [
                {
                    data: productSalesValues,
                    backgroundColor: ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc']
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        var value = data.datasets[0].data[tooltipItem.index];
                        var label = data.labels[tooltipItem.index];
                        var percentage = Math.round((value / productSalesValues.reduce((a, b) => a + b, 0)) * 100);
                        return label + ': ' + new Intl.NumberFormat().format(value) + ' NPR (' + percentage + '%)';
                    }
                }
            }
        }
    };
    
    new Chart(productChartCanvas, productChartConfig);
    
    // 판매점별 차트
    var storeChartData = <?php echo json_encode($topSellingStores); ?>;
    var storeLabels = storeChartData.map(function(item) {
        return item.store_name;
    });
    var storeSalesValues = storeChartData.map(function(item) {
        return item.total_sales;
    });
    
    var storeChartCanvas = document.getElementById('storeChart').getContext('2d');
    var storeChartConfig = {
        type: 'bar',
        data: {
            labels: storeLabels,
            datasets: [
                {
                    label: '판매액 (NPR)',
                    backgroundColor: 'rgba(0, 123, 255, 0.5)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 1,
                    data: storeSalesValues
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        callback: function(value) {
                            return new Intl.NumberFormat().format(value);
                        }
                    }
                }]
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        return data.datasets[tooltipItem.datasetIndex].label + ': ' + 
                            new Intl.NumberFormat().format(tooltipItem.yLabel);
                    }
                }
            }
        }
    };
    
    new Chart(storeChartCanvas, storeChartConfig);
});
</script>
