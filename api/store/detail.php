<?php
/**
 * 판매점 상세 정보 API
 * 
 * 판매점의 상세 정보와 판매 현황을 제공하는 API
 * 
 * @param int store_id 판매점 ID
 * @param string from_date 시작일(선택)
 * @param string to_date 종료일(선택)
 * @return string HTML 형식의 판매점 상세 정보
 */

// 세션 시작 및 로그인 체크
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    exit(json_encode([
        'status' => 'error',
        'message' => '인증되지 않은 접근입니다.'
    ]));
}

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 입력 파라미터 검증
$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-30 days'));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

if ($store_id <= 0) {
    http_response_code(400);
    exit(json_encode([
        'status' => 'error',
        'message' => '유효하지 않은 판매점 ID입니다.'
    ]));
}

// 데이터베이스 연결
$conn = getDBConnection();

// 판매점 기본 정보 가져오기
function getStoreInfo($conn, $store_id) {
    $query = "
        SELECT 
            s.*,
            r.name as region_name,
            u.username as manager_name
        FROM 
            stores s
        LEFT JOIN 
            regions r ON s.region_id = r.id
        LEFT JOIN 
            users u ON s.manager_id = u.id
        WHERE 
            s.id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$store_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 판매점에 할당된 단말기 목록 가져오기
function getStoreTerminals($conn, $store_id) {
    $query = "
        SELECT 
            id,
            terminal_code,
            model,
            serial_number,
            status,
            last_connection
        FROM 
            terminals
        WHERE 
            store_id = ?
        ORDER BY 
            terminal_code
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$store_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 판매점의 판매 요약 정보 가져오기
function getStoreSalesSummary($conn, $store_id, $from_date, $to_date) {
    $query = "
        SELECT 
            COUNT(t.id) as total_tickets,
            SUM(t.price) as total_sales,
            COUNT(DISTINCT DATE(t.created_at)) as active_days,
            COUNT(DISTINCT t.product_id) as products_count
        FROM 
            tickets t
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        WHERE 
            tm.store_id = ? AND
            DATE(t.created_at) BETWEEN ? AND ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$store_id, $from_date, $to_date]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 판매점의 상품별 판매 현황 가져오기
function getStoreProductSales($conn, $store_id, $from_date, $to_date) {
    $query = "
        SELECT 
            lp.id,
            lp.name as product_name,
            lp.product_code,
            COUNT(t.id) as tickets_count,
            SUM(t.price) as sales_amount
        FROM 
            tickets t
        JOIN 
            lottery_products lp ON t.product_id = lp.id
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        WHERE 
            tm.store_id = ? AND
            DATE(t.created_at) BETWEEN ? AND ?
        GROUP BY 
            lp.id, lp.name, lp.product_code
        ORDER BY 
            tickets_count DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$store_id, $from_date, $to_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 판매점의 일별 판매 추이 가져오기
function getStoreDailySales($conn, $store_id, $from_date, $to_date) {
    $query = "
        SELECT 
            DATE(t.created_at) as sale_date,
            COUNT(t.id) as tickets_count,
            SUM(t.price) as sales_amount
        FROM 
            tickets t
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        WHERE 
            tm.store_id = ? AND
            DATE(t.created_at) BETWEEN ? AND ?
        GROUP BY 
            DATE(t.created_at)
        ORDER BY 
            sale_date
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$store_id, $from_date, $to_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 최근 판매 내역 가져오기
function getStoreRecentSales($conn, $store_id, $limit = 10) {
    $query = "
        SELECT 
            t.ticket_number,
            lp.name as product_name,
            t.price,
            t.created_at,
            tm.terminal_code
        FROM 
            tickets t
        JOIN 
            lottery_products lp ON t.product_id = lp.id
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        WHERE 
            tm.store_id = ?
        ORDER BY 
            t.created_at DESC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$store_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 필요한 데이터 가져오기
$store_info = getStoreInfo($conn, $store_id);
if (!$store_info) {
    http_response_code(404);
    exit(json_encode([
        'status' => 'error',
        'message' => '판매점 정보를 찾을 수 없습니다.'
    ]));
}

$terminals = getStoreTerminals($conn, $store_id);
$sales_summary = getStoreSalesSummary($conn, $store_id, $from_date, $to_date);
$product_sales = getStoreProductSales($conn, $store_id, $from_date, $to_date);
$daily_sales = getStoreDailySales($conn, $store_id, $from_date, $to_date);
$recent_sales = getStoreRecentSales($conn, $store_id);

// HTML 응답 출력
header('Content-Type: text/html; charset=UTF-8');
?>

<div class="container-fluid px-0">
    <!-- 판매점 기본 정보 -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">판매점 정보</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th style="width: 130px;">판매점 코드:</th>
                            <td><?php echo $store_info['store_code']; ?></td>
                        </tr>
                        <tr>
                            <th>판매점명:</th>
                            <td><?php echo $store_info['name']; ?></td>
                        </tr>
                        <tr>
                            <th>지역:</th>
                            <td><?php echo $store_info['region_name']; ?></td>
                        </tr>
                        <tr>
                            <th>위치:</th>
                            <td><?php echo $store_info['location']; ?></td>
                        </tr>
                        <tr>
                            <th>연락처:</th>
                            <td><?php echo $store_info['contact_name'] . ' (' . $store_info['contact_phone'] . ')'; ?></td>
                        </tr>
                        <tr>
                            <th>이메일:</th>
                            <td><?php echo $store_info['contact_email']; ?></td>
                        </tr>
                        <tr>
                            <th>상태:</th>
                            <td>
                                <?php 
                                $status_class = '';
                                $status_text = '';
                                
                                switch ($store_info['status']) {
                                    case 'active':
                                        $status_class = 'badge badge-success';
                                        $status_text = '활성';
                                        break;
                                    case 'inactive':
                                        $status_class = 'badge badge-secondary';
                                        $status_text = '비활성';
                                        break;
                                    case 'suspended':
                                        $status_class = 'badge badge-danger';
                                        $status_text = '정지';
                                        break;
                                    default:
                                        $status_class = 'badge badge-dark';
                                        $status_text = '기타';
                                }
                                ?>
                                <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>수수료율:</th>
                            <td><?php echo $store_info['commission_rate']; ?>%</td>
                        </tr>
                        <tr>
                            <th>관리자:</th>
                            <td><?php echo $store_info['manager_name'] ?? '미지정'; ?></td>
                        </tr>
                        <tr>
                            <th>등록일:</th>
                            <td><?php echo date('Y-m-d', strtotime($store_info['created_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">판매 요약 (<?php echo $from_date; ?> ~ <?php echo $to_date; ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="info-box bg-info">
                                <div class="info-box-content">
                                    <span class="info-box-text">총 판매 티켓</span>
                                    <span class="info-box-number"><?php echo number_format($sales_summary['total_tickets'] ?? 0); ?> 장</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-box bg-success">
                                <div class="info-box-content">
                                    <span class="info-box-text">총 판매액</span>
                                    <span class="info-box-number"><?php echo number_format($sales_summary['total_sales'] ?? 0); ?> NPR</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-box bg-warning">
                                <div class="info-box-content">
                                    <span class="info-box-text">활동 일수</span>
                                    <span class="info-box-number"><?php echo number_format($sales_summary['active_days'] ?? 0); ?> 일</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-box bg-danger">
                                <div class="info-box-content">
                                    <span class="info-box-text">판매 상품 수</span>
                                    <span class="info-box-number"><?php echo number_format($sales_summary['products_count'] ?? 0); ?> 종</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 단말기 정보 -->
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="card-title">단말기 정보</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>단말기 코드</th>
                            <th>모델</th>
                            <th>시리얼 번호</th>
                            <th>상태</th>
                            <th>최근 연결</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($terminals)): ?>
                            <tr>
                                <td colspan="5" class="text-center">등록된 단말기가 없습니다.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($terminals as $terminal): ?>
                                <tr>
                                    <td><?php echo $terminal['terminal_code']; ?></td>
                                    <td><?php echo $terminal['model']; ?></td>
                                    <td><?php echo $terminal['serial_number']; ?></td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        $status_text = '';
                                        
                                        switch ($terminal['status']) {
                                            case 'active':
                                                $status_class = 'badge badge-success';
                                                $status_text = '활성';
                                                break;
                                            case 'inactive':
                                                $status_class = 'badge badge-secondary';
                                                $status_text = '비활성';
                                                break;
                                            case 'maintenance':
                                                $status_class = 'badge badge-warning';
                                                $status_text = '유지보수';
                                                break;
                                            default:
                                                $status_class = 'badge badge-dark';
                                                $status_text = '기타';
                                        }
                                        ?>
                                        <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($terminal['last_connection'])) {
                                            echo date('Y-m-d H:i:s', strtotime($terminal['last_connection']));
                                        } else {
                                            echo '<span class="text-muted">연결 기록 없음</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- 상품별 판매 현황 -->
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="card-title">상품별 판매 현황</h5>
        </div>
        <div class="card-body">
            <?php if (empty($product_sales)): ?>
                <div class="alert alert-info">선택한 기간 내 판매 데이터가 없습니다.</div>
            <?php else: ?>
                <canvas id="productSalesChart" style="height: 250px;"></canvas>
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>상품명</th>
                                <th>상품 코드</th>
                                <th>판매 티켓 수</th>
                                <th>판매액</th>
                                <th>비율</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_amount = array_sum(array_column($product_sales, 'sales_amount'));
                            foreach ($product_sales as $product): 
                                $percentage = ($total_amount > 0) ? ($product['sales_amount'] / $total_amount * 100) : 0;
                            ?>
                                <tr>
                                    <td><?php echo $product['product_name']; ?></td>
                                    <td><?php echo $product['product_code']; ?></td>
                                    <td><?php echo number_format($product['tickets_count']); ?> 장</td>
                                    <td><?php echo number_format($product['sales_amount']); ?> NPR</td>
                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 일별 판매 추이 -->
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="card-title">일별 판매 추이</h5>
        </div>
        <div class="card-body">
            <?php if (empty($daily_sales)): ?>
                <div class="alert alert-info">선택한 기간 내 판매 데이터가 없습니다.</div>
            <?php else: ?>
                <canvas id="dailySalesChart" style="height: 250px;"></canvas>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 최근 판매 내역 -->
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="card-title">최근 판매 내역</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>티켓 번호</th>
                            <th>상품명</th>
                            <th>가격</th>
                            <th>판매일시</th>
                            <th>단말기</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_sales)): ?>
                            <tr>
                                <td colspan="5" class="text-center">판매 내역이 없습니다.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_sales as $sale): ?>
                                <tr>
                                    <td><?php echo $sale['ticket_number']; ?></td>
                                    <td><?php echo $sale['product_name']; ?></td>
                                    <td><?php echo number_format($sale['price']); ?> NPR</td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($sale['created_at'])); ?></td>
                                    <td><?php echo $sale['terminal_code']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    // 차트 데이터
    <?php if (!empty($product_sales)): ?>
    const productLabels = <?php echo json_encode(array_column($product_sales, 'product_name')); ?>;
    const productData = <?php echo json_encode(array_column($product_sales, 'sales_amount')); ?>;
    const productColors = [
        '#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de',
        '#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#17a2b8', '#6c757d'
    ];
    
    // 상품별 판매 차트
    new Chart(document.getElementById('productSalesChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: productLabels,
            datasets: [{
                data: productData,
                backgroundColor: productColors
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'right'
            }
        }
    });
    <?php endif; ?>
    
    <?php if (!empty($daily_sales)): ?>
    const dailyLabels = <?php echo json_encode(array_column($daily_sales, 'sale_date')); ?>;
    const dailyTickets = <?php echo json_encode(array_column($daily_sales, 'tickets_count')); ?>;
    const dailyAmounts = <?php echo json_encode(array_column($daily_sales, 'sales_amount')); ?>;
    
    // 일별 판매 추이 차트
    new Chart(document.getElementById('dailySalesChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: dailyLabels,
            datasets: [
                {
                    label: '판매 티켓 수',
                    data: dailyTickets,
                    borderColor: 'rgba(60, 141, 188, 1)',
                    backgroundColor: 'rgba(60, 141, 188, 0.2)',
                    pointRadius: 3,
                    pointColor: '#3b8bba',
                    pointStrokeColor: 'rgba(60, 141, 188, 1)',
                    pointHighlightFill: '#fff',
                    pointHighlightStroke: 'rgba(60, 141, 188, 1)',
                    fill: true
                },
                {
                    label: '판매액 (NPR)',
                    data: dailyAmounts,
                    borderColor: 'rgba(40, 167, 69, 1)',
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    pointRadius: 3,
                    pointColor: '#28a745',
                    pointStrokeColor: 'rgba(40, 167, 69, 1)',
                    pointHighlightFill: '#fff',
                    pointHighlightStroke: 'rgba(40, 167, 69, 1)',
                    fill: true,
                    yAxisID: 'y-axis-2'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [
                    {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        id: 'y-axis-1',
                        scaleLabel: {
                            display: true,
                            labelString: '판매 티켓 수'
                        }
                    },
                    {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        id: 'y-axis-2',
                        gridLines: {
                            drawOnChartArea: false
                        },
                        scaleLabel: {
                            display: true,
                            labelString: '판매액 (NPR)'
                        }
                    }
                ]
            },
            tooltips: {
                mode: 'index',
                intersect: false
            }
        }
    });
    <?php endif; ?>
});
</script>
