<?php
/**
 * 판매 이력 페이지
 * 
 * 이 페이지는 로또 판매 이력을 조회하고 분석하는 기능을 제공합니다.
 * - 판매 기록 조회
 * - 판매 데이터 분석
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 쿼리 타입 전역 변수 (모의 데이터베이스 처리용)
$query_type = '';

// 데이터베이스 연결
$db = get_db_connection();

// 모의 데이터베이스 연결 객체 생성
$db = new MockPDO();

// 현재 페이지 정보
$pageTitle = "판매 이력";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 메시지 초기화
$message = '';
$message_type = '';

// 필터링을 위한 기본값 설정
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-30 days'));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$region_id = isset($_GET['region_id']) ? intval($_GET['region_id']) : 0;
$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
$ticket_status = isset($_GET['ticket_status']) ? $_GET['ticket_status'] : '';

// 페이지네이션 설정
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// 판매 이력 데이터 가져오기
function getSalesHistory($db, $from_date, $to_date, $product_id = 0, $region_id = 0, $store_id = 0, $ticket_status = '', $limit = 50, $offset = 0) {
    global $query_type;
    $query_type = 'sales_history';
    
    $query = "
        SELECT 
            t.id,
            t.ticket_number,
            t.numbers,
            t.price,
            t.status,
            t.created_at,
            lp.name as product_name,
            s.name as store_name,
            s.store_code,
            r.name as region_name,
            tm.terminal_code
        FROM 
            tickets t
        JOIN 
            lottery_products lp ON t.product_id = lp.id
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        JOIN 
            stores s ON tm.store_id = s.id
        JOIN 
            regions r ON s.region_id = r.id
        WHERE 
            DATE(t.created_at) BETWEEN ? AND ?
    ";
    
    $params = [$from_date, $to_date];
    
    if ($product_id > 0) {
        $query .= " AND t.product_id = ?";
        $params[] = $product_id;
    }
    
    if ($region_id > 0) {
        $query .= " AND s.region_id = ?";
        $params[] = $region_id;
    }
    
    if ($store_id > 0) {
        $query .= " AND s.id = ?";
        $params[] = $store_id;
    }
    
    if (!empty($ticket_status)) {
        $query .= " AND t.status = ?";
        $params[] = $ticket_status;
    }
    
    $query .= "
        ORDER BY 
            t.created_at DESC
        LIMIT ?, ?
    ";
    
    $params[] = $offset;
    $params[] = $limit;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 판매 이력 총 개수 가져오기 (페이지네이션용)
function getSalesHistoryCount($db, $from_date, $to_date, $product_id = 0, $region_id = 0, $store_id = 0, $ticket_status = '') {
    global $query_type;
    $query_type = 'count';
    
    $query = "
        SELECT 
            COUNT(*) as total
        FROM 
            tickets t
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        JOIN 
            stores s ON tm.store_id = s.id
        WHERE 
            DATE(t.created_at) BETWEEN ? AND ?
    ";
    
    $params = [$from_date, $to_date];
    
    if ($product_id > 0) {
        $query .= " AND t.product_id = ?";
        $params[] = $product_id;
    }
    
    if ($region_id > 0) {
        $query .= " AND s.region_id = ?";
        $params[] = $region_id;
    }
    
    if ($store_id > 0) {
        $query .= " AND s.id = ?";
        $params[] = $store_id;
    }
    
    if (!empty($ticket_status)) {
        $query .= " AND t.status = ?";
        $params[] = $ticket_status;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return isset($result['total']) ? $result['total'] : 0;
}

// 판매 추이 데이터 가져오기 (그래프용)
function getSalesTrend($db, $from_date, $to_date, $product_id = 0, $region_id = 0, $store_id = 0) {
    global $query_type;
    $query_type = 'trend';
    
    $query = "
        SELECT 
            DATE(t.created_at) as sale_date,
            COUNT(t.id) as tickets_count,
            SUM(t.price) as sales_amount
        FROM 
            tickets t
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        JOIN 
            stores s ON tm.store_id = s.id
        WHERE 
            DATE(t.created_at) BETWEEN ? AND ?
    ";
    
    $params = [$from_date, $to_date];
    
    if ($product_id > 0) {
        $query .= " AND t.product_id = ?";
        $params[] = $product_id;
    }
    
    if ($region_id > 0) {
        $query .= " AND s.region_id = ?";
        $params[] = $region_id;
    }
    
    if ($store_id > 0) {
        $query .= " AND s.id = ?";
        $params[] = $store_id;
    }
    
    $query .= "
        GROUP BY 
            DATE(t.created_at)
        ORDER BY 
            sale_date
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 복권 상품 목록 가져오기 (필터용)
function getLotteryProducts($db) {
    global $query_type;
    $query_type = 'products';
    
    $query = "SELECT id, product_code, name FROM lottery_products WHERE status = 'active' ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 지역 목록 가져오기 (필터용)
function getRegions($db) {
    global $query_type;
    $query_type = 'regions';
    
    $query = "SELECT id, name FROM regions ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 판매점 목록 가져오기 (필터용)
function getStores($db, $region_id = 0) {
    global $query_type;
    $query_type = 'stores';
    
    $query = "SELECT id, store_code, name FROM stores WHERE status = 'active'";
    $params = [];
    
    if ($region_id > 0) {
        $query .= " AND region_id = ?";
        $params[] = $region_id;
    }
    
    $query .= " ORDER BY name";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 필요한 데이터 가져오기
$sales_history = getSalesHistory($db, $from_date, $to_date, $product_id, $region_id, $store_id, $ticket_status, $per_page, $offset);
$total_records = getSalesHistoryCount($db, $from_date, $to_date, $product_id, $region_id, $store_id, $ticket_status);
$total_pages = ceil($total_records / $per_page);
$sales_trend = getSalesTrend($db, $from_date, $to_date, $product_id, $region_id, $store_id);
$lottery_products = getLotteryProducts($db);
$regions = getRegions($db);
$stores = getStores($db, $region_id);

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
                    <li class="breadcrumb-item">판매 관리</li>
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
        <!-- 필터 -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">검색 필터</h3>
            </div>
            <div class="card-body">
                <form action="" method="get" class="form-row">
                    <div class="form-group col-md-2">
                        <label for="from_date">시작 날짜</label>
                        <input type="date" class="form-control" id="from_date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="to_date">종료 날짜</label>
                        <input type="date" class="form-control" id="to_date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="product_id">복권 상품</label>
                        <select class="form-control" id="product_id" name="product_id">
                            <option value="0">전체</option>
                            <?php foreach ($lottery_products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" <?php echo ($product_id == $product['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($product['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="region_id">지역</label>
                        <select class="form-control" id="region_id" name="region_id" onchange="this.form.submit()">
                            <option value="0">전체</option>
                            <?php foreach ($regions as $region): ?>
                                <option value="<?php echo $region['id']; ?>" <?php echo ($region_id == $region['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($region['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="store_id">판매점</label>
                        <select class="form-control" id="store_id" name="store_id">
                            <option value="0">전체</option>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?php echo $store['id']; ?>" <?php echo ($store_id == $store['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($store['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="ticket_status">상태</label>
                        <select class="form-control" id="ticket_status" name="ticket_status">
                            <option value="">전체</option>
                            <option value="active" <?php echo ($ticket_status === 'active') ? 'selected' : ''; ?>>활성</option>
                            <option value="used" <?php echo ($ticket_status === 'used') ? 'selected' : ''; ?>>사용됨</option>
                            <option value="winning" <?php echo ($ticket_status === 'winning') ? 'selected' : ''; ?>>당첨</option>
                            <option value="claimed" <?php echo ($ticket_status === 'claimed') ? 'selected' : ''; ?>>수령됨</option>
                            <option value="expired" <?php echo ($ticket_status === 'expired') ? 'selected' : ''; ?>>만료됨</option>
                            <option value="cancelled" <?php echo ($ticket_status === 'cancelled') ? 'selected' : ''; ?>>취소됨</option>
                        </select>
                    </div>
                    <div class="form-group col-md-12 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search mr-1"></i> 검색
                        </button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary ml-2">
                            <i class="fas fa-redo mr-1"></i> 필터 초기화
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- 판매 요약 -->
        <div class="row">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-ticket-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">판매 티켓 수</span>
                        <span class="info-box-number"><?php echo number_format($total_records ?? 0); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-dollar-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">총 매출액</span>
                        <span class="info-box-number">₹ <?php echo number_format(array_sum(array_column($sales_history, 'price'))); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-calendar-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">조회 기간</span>
                        <span class="info-box-number"><?php echo date('Y.m.d', strtotime($from_date)) . ' ~ ' . date('Y.m.d', strtotime($to_date)); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-danger"><i class="fas fa-chart-pie"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">평균 일일 매출</span>
                        <span class="info-box-number">₹ <?php 
                            $date_diff = max(1, (strtotime($to_date) - strtotime($from_date)) / 86400);
                            echo number_format(array_sum(array_column($sales_history, 'price')) / $date_diff); 
                        ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 판매 이력 테이블 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">판매 이력 목록</h3>
                <div class="card-tools">
                    <a href="#" class="btn btn-tool" onclick="exportToExcel(event)">
                        <i class="fas fa-file-excel"></i> 엑셀로 내보내기
                    </a>
                    <a href="#" class="btn btn-tool" onclick="printTable(event)">
                        <i class="fas fa-print"></i> 인쇄
                    </a>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>번호</th>
                            <th>티켓 번호</th>
                            <th>복권 상품</th>
                            <th>판매점</th>
                            <th>지역</th>
                            <th>가격</th>
                            <th>상태</th>
                            <th>판매일</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sales_history)): ?>
                            <tr>
                                <td colspan="9" class="text-center">검색 조건에 맞는 판매 이력이 없습니다.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sales_history as $index => $sale): ?>
                                <tr>
                                    <td><?php echo $offset + $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($sale['ticket_number']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['store_name']); ?> (<?php echo htmlspecialchars($sale['store_code']); ?>)</td>
                                    <td><?php echo htmlspecialchars($sale['region_name']); ?></td>
                                    <td>₹ <?php echo number_format($sale['price']); ?></td>
                                    <td>
                                        <?php
                                        $status_class = 'secondary';
                                        switch ($sale['status']) {
                                            case 'active':
                                                $status_class = 'primary';
                                                $status_text = '활성';
                                                break;
                                            case 'used':
                                                $status_class = 'info';
                                                $status_text = '사용됨';
                                                break;
                                            case 'winning':
                                                $status_class = 'success';
                                                $status_text = '당첨';
                                                break;
                                            case 'claimed':
                                                $status_class = 'success';
                                                $status_text = '수령됨';
                                                break;
                                            case 'expired':
                                                $status_class = 'warning';
                                                $status_text = '만료됨';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'danger';
                                                $status_text = '취소됨';
                                                break;
                                            default:
                                                $status_text = $sale['status'];
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($sale['created_at'])); ?></td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-info view-details" data-id="<?php echo $sale['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <ul class="pagination pagination-sm m-0 float-right">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&product_id=<?php echo $product_id; ?>&region_id=<?php echo $region_id; ?>&store_id=<?php echo $store_id; ?>&ticket_status=<?php echo urlencode($ticket_status); ?>">«</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&product_id=<?php echo $product_id; ?>&region_id=<?php echo $region_id; ?>&store_id=<?php echo $store_id; ?>&ticket_status=<?php echo urlencode($ticket_status); ?>">‹</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($start + 4, $total_pages);
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&product_id=<?php echo $product_id; ?>&region_id=<?php echo $region_id; ?>&store_id=<?php echo $store_id; ?>&ticket_status=<?php echo urlencode($ticket_status); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&product_id=<?php echo $product_id; ?>&region_id=<?php echo $region_id; ?>&store_id=<?php echo $store_id; ?>&ticket_status=<?php echo urlencode($ticket_status); ?>">›</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&product_id=<?php echo $product_id; ?>&region_id=<?php echo $region_id; ?>&store_id=<?php echo $store_id; ?>&ticket_status=<?php echo urlencode($ticket_status); ?>">»</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- 티켓 상세 모달 -->
<div class="modal fade" id="ticketDetailsModal" tabindex="-1" role="dialog" aria-labelledby="ticketDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ticketDetailsModalLabel">티켓 상세 정보</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="ticketDetailsContent">
                <!-- 티켓 상세 정보가 여기에 로드됩니다 -->
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">로딩중...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary" id="printTicket">인쇄</button>
            </div>
        </div>
    </div>
</div>

<!-- 푸터 포함 -->
<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>

<script>
    // 티켓 상세 정보 보기
    $(document).on('click', '.view-details', function(e) {
        e.preventDefault();
        const ticketId = $(this).data('id');
        
        $('#ticketDetailsContent').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">로딩중...</span></div></div>');
        $('#ticketDetailsModal').modal('show');
        
        // AJAX로 티켓 상세 정보 로드
        $.ajax({
            url: '../../api/sales/get_ticket_details.php',
            type: 'GET',
            data: { id: ticketId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // 티켓 상세 정보 표시
                    let html = '<div class="card">';
                    html += '<div class="card-header bg-primary text-white">티켓 기본 정보</div>';
                    html += '<div class="card-body">';
                    html += '<table class="table table-bordered">';
                    html += '<tr><th width="30%">티켓 번호</th><td>' + response.data.ticket_number + '</td></tr>';
                    html += '<tr><th>복권 상품</th><td>' + response.data.product_name + '</td></tr>';
                    html += '<tr><th>선택 번호</th><td>' + response.data.numbers + '</td></tr>';
                    html += '<tr><th>가격</th><td>₹ ' + response.data.price + '</td></tr>';
                    html += '<tr><th>상태</th><td>' + getStatusBadge(response.data.status) + '</td></tr>';
                    html += '<tr><th>판매일</th><td>' + response.data.created_at + '</td></tr>';
                    html += '</table>';
                    html += '</div>';
                    html += '</div>';
                    
                    html += '<div class="card mt-3">';
                    html += '<div class="card-header bg-info text-white">판매 정보</div>';
                    html += '<div class="card-body">';
                    html += '<table class="table table-bordered">';
                    html += '<tr><th width="30%">판매점</th><td>' + response.data.store_name + ' (' + response.data.store_code + ')</td></tr>';
                    html += '<tr><th>지역</th><td>' + response.data.region_name + '</td></tr>';
                    html += '<tr><th>단말기</th><td>' + response.data.terminal_code + '</td></tr>';
                    html += '<tr><th>판매자</th><td>' + (response.data.seller_name || '-') + '</td></tr>';
                    html += '</table>';
                    html += '</div>';
                    html += '</div>';
                    
                    $('#ticketDetailsContent').html(html);
                } else {
                    $('#ticketDetailsContent').html('<div class="alert alert-danger">티켓 정보를 불러오는데 실패했습니다: ' + response.message + '</div>');
                }
            },
            error: function() {
                $('#ticketDetailsContent').html('<div class="alert alert-danger">서버 오류가 발생했습니다. 다시 시도해주세요.</div>');
            }
        });
    });
    
    // 티켓 인쇄
    $('#printTicket').on('click', function() {
        const content = document.getElementById('ticketDetailsContent').innerHTML;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>티켓 상세 정보</title>
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
                    <style>
                        body { padding: 20px; }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h3 class="mb-4">티켓 상세 정보</h3>
                        ${content}
                        <div class="text-center mt-4 no-print">
                            <button class="btn btn-primary" onclick="window.print()">인쇄</button>
                            <button class="btn btn-secondary ml-2" onclick="window.close()">닫기</button>
                        </div>
                    </div>
                </body>
            </html>
        `);
        printWindow.document.close();
    });
    
    // 테이블 인쇄
    function printTable(e) {
        e.preventDefault();
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>판매 이력</title>
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
                    <style>
                        body { padding: 20px; }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h3 class="mb-4">판매 이력 목록</h3>
                        <p>조회 기간: ${$('#from_date').val()} ~ ${$('#to_date').val()}</p>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>번호</th>
                                    <th>티켓 번호</th>
                                    <th>복권 상품</th>
                                    <th>판매점</th>
                                    <th>지역</th>
                                    <th>가격</th>
                                    <th>상태</th>
                                    <th>판매일</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${$('table tbody tr').map(function() {
                                    return '<tr>' + $(this).find('td:not(:last-child)').map(function() {
                                        return '<td>' + $(this).html() + '</td>';
                                    }).get().join('') + '</tr>';
                                }).get().join('')}
                            </tbody>
                        </table>
                        <div class="text-center mt-4 no-print">
                            <button class="btn btn-primary" onclick="window.print()">인쇄</button>
                            <button class="btn btn-secondary ml-2" onclick="window.close()">닫기</button>
                        </div>
                    </div>
                </body>
            </html>
        `);
        printWindow.document.close();
    }
    
    // 엑셀로 내보내기
    function exportToExcel(e) {
        e.preventDefault();
        // 실제 구현에서는 서버에 요청하여 엑셀 파일 생성 로직 추가
        alert('엑셀 내보내기 기능은 개발 중입니다.');
    }
    
    // 상태에 따른 배지 클래스 반환
    function getStatusBadge(status) {
        let statusClass = 'secondary';
        let statusText = status;
        
        switch (status) {
            case 'active':
                statusClass = 'primary';
                statusText = '활성';
                break;
            case 'used':
                statusClass = 'info';
                statusText = '사용됨';
                break;
            case 'winning':
                statusClass = 'success';
                statusText = '당첨';
                break;
            case 'claimed':
                statusClass = 'success';
                statusText = '수령됨';
                break;
            case 'expired':
                statusClass = 'warning';
                statusText = '만료됨';
                break;
            case 'cancelled':
                statusClass = 'danger';
                statusText = '취소됨';
                break;
        }
        
        return '<span class="badge badge-' + statusClass + '">' + statusText + '</span>';
    }
    
    // 지역 선택에 따라 판매점 필터링
    $('#region_id').on('change', function() {
        const regionId = $(this).val();
        
        $.ajax({
            url: '../../api/stores/get_stores_by_region.php',
            type: 'GET',
            data: { region_id: regionId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    let html = '<option value="0">전체</option>';
                    response.data.forEach(function(store) {
                        html += '<option value="' + store.id + '">' + store.name + ' (' + store.store_code + ')</option>';
                    });
                    $('#store_id').html(html);
                }
            }
        });
    });
</script>
