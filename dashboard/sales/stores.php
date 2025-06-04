<?php
/**
 * 판매처 목록 페이지
 */

require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 로그인 확인
requireLogin();

// 관리자 권한 확인
requireAdmin();

// 페이지 제목 설정
$pageTitle = '판매처 목록';
$pageHeader = '판매처 목록';

// 추가 CSS
$extraCss = '/server/assets/css/sales.css';

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// 검색 필터
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$regionFilter = isset($_GET['region']) ? sanitizeInput($_GET['region']) : '';

// SQL 쿼리 구성 (가상의 stores 테이블 사용)
$sql = "SELECT s.*, r.region_name, u.username as manager_name 
        FROM stores s
        LEFT JOIN regions r ON s.region_id = r.id
        LEFT JOIN users u ON s.manager_id = u.id
        WHERE 1=1";
$countSql = "SELECT COUNT(*) as total FROM stores WHERE 1=1";
$params = [];

// 검색어 필터 적용
if (!empty($search)) {
    $sql .= " AND (s.store_name LIKE ? OR s.address LIKE ? OR s.phone LIKE ?)";
    $countSql .= " AND (store_name LIKE ? OR address LIKE ? OR phone LIKE ?)";
    $params[] = ['type' => 's', 'value' => "%$search%"];
    $params[] = ['type' => 's', 'value' => "%$search%"];
    $params[] = ['type' => 's', 'value' => "%$search%"];
}

// 상태 필터 적용
if (!empty($statusFilter)) {
    $sql .= " AND s.status = ?";
    $countSql .= " AND status = ?";
    $params[] = ['type' => 's', 'value' => $statusFilter];
}

// 지역 필터 적용
if (!empty($regionFilter)) {
    $sql .= " AND s.region_id = ?";
    $countSql .= " AND region_id = ?";
    $params[] = ['type' => 'i', 'value' => (int)$regionFilter];
}

// 정렬 적용
$sql .= " ORDER BY s.store_name ASC LIMIT ?, ?";
$params[] = ['type' => 'i', 'value' => $offset];
$params[] = ['type' => 'i', 'value' => $perPage];

// 테이블이 없을 경우를 대비한 더미 데이터
$stores = [];
$dummyData = true;
$total = 0;
$totalPages = 1;

try {
    // 쿼리 실행 시도
    $stores = fetchAll($sql, $params);
    
    // 전체 판매처 수 계산
    $countParams = array_slice($params, 0, -2); // LIMIT 파라미터 제외
    $totalResult = fetchOne($countSql, $countParams);
    $total = $totalResult ? $totalResult['total'] : 0;
    
    // 총 페이지 수
    $totalPages = ceil($total / $perPage);
    $dummyData = false;
} catch (Exception $e) {
    // 테이블이 없거나 오류 발생 시 더미 데이터 사용
    logError("판매처 데이터 조회 실패: " . $e->getMessage(), 'sales');
    
    // 더미 데이터 생성 - 지역
    $regions = [
        1 => '서울',
        2 => '경기',
        3 => '인천',
        4 => '부산',
        5 => '대구',
        6 => '광주',
        7 => '대전',
        8 => '울산',
        9 => '세종',
        10 => '강원',
        11 => '충북',
        12 => '충남',
        13 => '전북',
        14 => '전남',
        15 => '경북',
        16 => '경남',
        17 => '제주'
    ];
    
    // 더미 데이터 생성 - 판매처
    $stores = [];
    for ($i = 1; $i <= 20; $i++) {
        $regionId = rand(1, 17);
        $status = ['active', 'inactive', 'suspended'][rand(0, 2)];
        
        $stores[] = [
            'id' => $i,
            'store_name' => '로또 판매점 ' . $i,
            'address' => $regions[$regionId] . ' 지역 주소 ' . $i,
            'phone' => '02-' . rand(1000, 9999) . '-' . rand(1000, 9999),
            'region_id' => $regionId,
            'region_name' => $regions[$regionId],
            'manager_id' => $i <= 10 ? rand(1, 5) : null,
            'manager_name' => $i <= 10 ? '관리자 ' . rand(1, 5) : null,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 365) . ' days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'))
        ];
    }
    
    $total = count($stores);
    $totalPages = ceil($total / $perPage);
    
    // 더미 데이터 필터링 - 검색어
    if (!empty($search)) {
        $stores = array_filter($stores, function($store) use ($search) {
            return stripos($store['store_name'], $search) !== false || 
                   stripos($store['address'], $search) !== false || 
                   stripos($store['phone'], $search) !== false;
        });
    }
    
    // 더미 데이터 필터링 - 상태
    if (!empty($statusFilter)) {
        $stores = array_filter($stores, function($store) use ($statusFilter) {
            return $store['status'] === $statusFilter;
        });
    }
    
    // 더미 데이터 필터링 - 지역
    if (!empty($regionFilter)) {
        $stores = array_filter($stores, function($store) use ($regionFilter) {
            return $store['region_id'] == (int)$regionFilter;
        });
    }
    
    // 배열 인덱스 재설정
    $stores = array_values($stores);
    
    // 페이지네이션 적용
    $stores = array_slice($stores, $offset, $perPage);
}

// 지역 목록 가져오기
$regions = [];
try {
    $regions = fetchAll("SELECT id, region_name FROM regions ORDER BY region_name ASC");
    if (empty($regions)) {
        throw new Exception("No regions found");
    }
} catch (Exception $e) {
    // 더미 지역 데이터 생성
    $regions = [
        ['id' => 1, 'region_name' => '서울'],
        ['id' => 2, 'region_name' => '경기'],
        ['id' => 3, 'region_name' => '인천'],
        ['id' => 4, 'region_name' => '부산'],
        ['id' => 5, 'region_name' => '대구'],
        ['id' => 6, 'region_name' => '광주'],
        ['id' => 7, 'region_name' => '대전'],
        ['id' => 8, 'region_name' => '울산'],
        ['id' => 9, 'region_name' => '세종'],
        ['id' => 10, 'region_name' => '강원'],
        ['id' => 11, 'region_name' => '충북'],
        ['id' => 12, 'region_name' => '충남'],
        ['id' => 13, 'region_name' => '전북'],
        ['id' => 14, 'region_name' => '전남'],
        ['id' => 15, 'region_name' => '경북'],
        ['id' => 16, 'region_name' => '경남'],
        ['id' => 17, 'region_name' => '제주']
    ];
}

// 판매처 상태 옵션
$statusOptions = [
    'active' => '영업중',
    'inactive' => '휴업중',
    'suspended' => '정지'
];

// 판매처 상태 변경 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $storeId = (int)$_POST['store_id'];
    $newStatus = sanitizeInput($_POST['new_status']);
    
    // 유효한 상태인지 확인
    if (array_key_exists($newStatus, $statusOptions)) {
        // 실제 데이터베이스가 있을 경우 상태 업데이트
        if (!$dummyData) {
            $result = execute("UPDATE stores SET status = ? WHERE id = ?", [
                ['type' => 's', 'value' => $newStatus],
                ['type' => 'i', 'value' => $storeId]
            ]);
            
            if ($result) {
                logInfo("판매처 ID: $storeId 상태 변경: $newStatus", 'sales');
                $_SESSION['flash_message'] = "판매처 상태가 성공적으로 변경되었습니다.";
                $_SESSION['flash_type'] = "success";
            } else {
                logError("판매처 ID: $storeId 상태 변경 실패", 'sales');
                $_SESSION['flash_message'] = "판매처 상태 변경 중 오류가 발생했습니다.";
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            // 더미 데이터일 경우 메시지만 표시
            $_SESSION['flash_message'] = "더미 데이터 모드: 판매처 ID $storeId의 상태를 '$newStatus'로 변경 요청됨";
            $_SESSION['flash_type'] = "warning";
        }
    } else {
        $_SESSION['flash_message'] = "잘못된 상태 값입니다.";
        $_SESSION['flash_type'] = "danger";
    }
    
    // 현재 페이지로 리디렉션
    header("Location: /server/dashboard/sales/stores.php");
    exit;
}

// 판매처 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_store'])) {
    $storeId = (int)$_POST['delete_store'];
    
    // 실제 데이터베이스가 있을 경우 삭제
    if (!$dummyData) {
        // 판매처에 연결된 다른 데이터가 있는지 확인 (실제 환경에서는 필요한 검증 진행)
        $canDelete = true;
        
        if ($canDelete) {
            $result = execute("DELETE FROM stores WHERE id = ?", [
                ['type' => 'i', 'value' => $storeId]
            ]);
            
            if ($result) {
                logInfo("판매처 ID: $storeId 삭제됨", 'sales');
                $_SESSION['flash_message'] = "판매처가 성공적으로 삭제되었습니다.";
                $_SESSION['flash_type'] = "success";
            } else {
                logError("판매처 ID: $storeId 삭제 실패", 'sales');
                $_SESSION['flash_message'] = "판매처 삭제 중 오류가 발생했습니다.";
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            $_SESSION['flash_message'] = "판매처에 연결된 데이터가 있어 삭제할 수 없습니다.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        // 더미 데이터일 경우 메시지만 표시
        $_SESSION['flash_message'] = "더미 데이터 모드: 판매처 ID $storeId의 삭제 요청됨";
        $_SESSION['flash_type'] = "warning";
    }
    
    // 현재 페이지로 리디렉션
    header("Location: /server/dashboard/sales/stores.php");
    exit;
}

// 헤더 포함
include_once '../../templates/header.php';
?>

<!-- 판매처 목록 -->
<div class="content-wrapper">
    <!-- 페이지 헤더 -->
    <div class="page-header">
        <h2 class="page-title"><?php echo $pageHeader; ?></h2>
        <p class="page-description">로또 판매처를 조회하고 관리합니다.</p>
    </div>
    
    <?php if ($dummyData): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 현재 더미 데이터를 표시하고 있습니다. 실제 데이터베이스 테이블이 없거나 연결되지 않았습니다.
        </div>
    <?php endif; ?>
    
    <!-- 판매처 목록 카드 -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">판매처 목록</h5>
            <a href="/server/dashboard/sales/add-store.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> 판매처 등록
            </a>
        </div>
        <div class="card-body">
            <!-- 검색 및 필터 폼 -->
            <form action="" method="get" class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="이름, 주소, 연락처 검색..." value="<?php echo $search; ?>">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="region" class="form-control" onchange="this.form.submit()">
                            <option value="">모든 지역</option>
                            <?php foreach ($regions as $region): ?>
                                <option value="<?php echo $region['id']; ?>" <?php echo $regionFilter == $region['id'] ? 'selected' : ''; ?>>
                                    <?php echo sanitizeInput($region['region_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">모든 상태</option>
                            <?php foreach ($statusOptions as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $statusFilter === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 text-right">
                        <a href="/server/dashboard/sales/stores.php" class="btn btn-secondary">필터 초기화</a>
                    </div>
                </div>
            </form>
            
            <!-- 판매처 목록 테이블 -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>판매처명</th>
                            <th>지역</th>
                            <th>주소</th>
                            <th>연락처</th>
                            <th>담당 관리자</th>
                            <th>상태</th>
                            <th>등록일</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($stores) > 0): ?>
                            <?php foreach ($stores as $store): ?>
                                <tr>
                                    <td><?php echo $store['id']; ?></td>
                                    <td><?php echo sanitizeInput($store['store_name']); ?></td>
                                    <td><?php echo sanitizeInput($store['region_name']); ?></td>
                                    <td><?php echo sanitizeInput($store['address']); ?></td>
                                    <td><?php echo sanitizeInput($store['phone']); ?></td>
                                    <td><?php echo $store['manager_name'] ? sanitizeInput($store['manager_name']) : '미지정'; ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $store['status'] === 'active' ? 'success' : 
                                                ($store['status'] === 'inactive' ? 'warning' : 'danger');
                                        ?>">
                                            <?php echo $statusOptions[$store['status']]; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($store['created_at'], 'Y-m-d'); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <!-- 상태 변경 버튼 -->
                                            <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                상태 변경
                                            </button>
                                            <div class="dropdown-menu">
                                                <?php foreach ($statusOptions as $key => $label): ?>
                                                    <?php if ($key !== $store['status']): ?>
                                                        <form action="" method="post">
                                                            <input type="hidden" name="store_id" value="<?php echo $store['id']; ?>">
                                                            <input type="hidden" name="new_status" value="<?php echo $key; ?>">
                                                            <button type="submit" name="update_status" class="dropdown-item">
                                                                <?php echo $label; ?>로 변경
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <!-- 수정 버튼 -->
                                            <a href="/server/dashboard/sales/edit-store.php?id=<?php echo $store['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <!-- 상세 보기 버튼 -->
                                            <a href="/server/dashboard/sales/store-detail.php?id=<?php echo $store['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <!-- 삭제 버튼 -->
                                            <form action="" method="post" class="d-inline" onsubmit="return confirm('정말로 이 판매처를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.');">
                                                <button type="submit" name="delete_store" value="<?php echo $store['id']; ?>" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">판매처 정보가 없습니다.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <!-- 이전 페이지 -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&region=<?php echo urlencode($regionFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">이전</a>
                        </li>
                        
                        <!-- 페이지 번호 -->
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&region=<?php echo urlencode($regionFilter); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- 다음 페이지 -->
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&region=<?php echo urlencode($regionFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">다음</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 스타일 -->
<style>
.badge {
    padding: 5px 10px;
    font-size: 0.85em;
}
</style>

<?php
// 자바스크립트 설정
$inlineJs = <<<JS
// 판매처 목록 페이지 초기화
document.addEventListener('DOMContentLoaded', function() {
    console.log('판매처 목록 페이지 초기화');
});
JS;

// 푸터 포함
include_once '../../templates/footer.php';
?>
