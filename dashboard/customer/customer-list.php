<?php
/**
 * 고객 목록 페이지
 * 
 * 이 페이지는 시스템에 등록된 모든 고객의 목록을 표시합니다.
 * 검색, 필터링, 정렬 기능을 제공합니다.
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
// functions.php에 정의된 checkPageAccess 함수 사용
if (function_exists('checkPageAccess')) {
    checkPageAccess('customer_management');
} else {
    // 함수가 로드되지 않은 경우 로그 기록
    error_log("checkPageAccess 함수를 찾을 수 없습니다.");
}

// 현재 페이지 정보
$pageTitle = "고객 목록";
$currentSection = "customer";
$currentPage = basename($_SERVER['PHP_SELF']);

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;

// 검색 및 필터링 파라미터
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$sortBy = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'id';
$sortOrder = isset($_GET['order']) ? sanitizeInput($_GET['order']) : 'DESC';

// 데이터베이스 연결
$conn = getDBConnection();

// 메시지 초기화
$message = '';
$message_type = '';

// 고객 데이터 가져오기 (실제 환경에서는 데이터베이스에서 가져옴)
function getCustomers($conn, $search = '', $status = '', $sortBy = 'id', $sortOrder = 'DESC', $limit = 20, $offset = 0) {
    // 더미 데이터 반환
    return [
        [
            'id' => 1,
            'customer_code' => 'CUST00001',
            'first_name' => '홍',
            'last_name' => '길동',
            'full_name' => '홍길동',
            'email' => 'hong@example.com',
            'phone' => '010-1234-5678',
            'status' => 'active',
            'created_at' => '2023-01-01 10:00:00',
            'last_login' => '2023-05-15 15:30:00',
            'verification_status' => 'verified'
        ],
        [
            'id' => 2,
            'customer_code' => 'CUST00002',
            'first_name' => '김',
            'last_name' => '철수',
            'full_name' => '김철수',
            'email' => 'kim@example.com',
            'phone' => '010-2345-6789',
            'status' => 'active',
            'created_at' => '2023-01-15 11:30:00',
            'last_login' => '2023-05-10 09:45:00',
            'verification_status' => 'pending'
        ],
        [
            'id' => 3,
            'customer_code' => 'CUST00003',
            'first_name' => '이',
            'last_name' => '영희',
            'full_name' => '이영희',
            'email' => 'lee@example.com',
            'phone' => '010-3456-7890',
            'status' => 'inactive',
            'created_at' => '2023-02-01 09:15:00',
            'last_login' => '2023-04-20 14:10:00',
            'verification_status' => 'unverified'
        ],
        [
            'id' => 4,
            'customer_code' => 'CUST00004',
            'first_name' => '박',
            'last_name' => '지성',
            'full_name' => '박지성',
            'email' => 'park@example.com',
            'phone' => '010-4567-8901',
            'status' => 'active',
            'created_at' => '2023-02-15 13:45:00',
            'last_login' => '2023-05-14 16:20:00',
            'verification_status' => 'verified'
        ],
        [
            'id' => 5,
            'customer_code' => 'CUST00005',
            'first_name' => '최',
            'last_name' => '민수',
            'full_name' => '최민수',
            'email' => 'choi@example.com',
            'phone' => '010-5678-9012',
            'status' => 'active',
            'created_at' => '2023-03-01 08:30:00',
            'last_login' => '2023-05-12 10:50:00',
            'verification_status' => 'verified'
        ]
    ];
}

// 총 고객 수 가져오기 (실제 환경에서는 데이터베이스에서 가져옴)
function getCustomerCount($conn, $search = '', $status = '') {
    return 5; // 더미 데이터 카운트
}

// 고객 데이터 가져오기
$customers = getCustomers($conn, $search, $status, $sortBy, $sortOrder, $limit, $offset);
$totalCustomers = getCustomerCount($conn, $search, $status);
$totalPages = ceil($totalCustomers / $limit);

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
                    <li class="breadcrumb-item">고객 관리</li>
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
        <!-- 알림 메시지 -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- 검색 및 필터 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">검색 및 필터</h3>
            </div>
            <div class="card-body">
                <form method="get" class="form-horizontal">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="search">검색</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="이름, 이메일, 전화번호 등" value="<?php echo escape($search); ?>">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="status">상태</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">모든 상태</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>활성</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>비활성</option>
                                <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>정지</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="limit">표시 개수</label>
                            <select class="form-control" id="limit" name="limit">
                                <option value="10" <?php echo $limit === 10 ? 'selected' : ''; ?>>10개</option>
                                <option value="20" <?php echo $limit === 20 ? 'selected' : ''; ?>>20개</option>
                                <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50개</option>
                                <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100개</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="sort">정렬 기준</label>
                            <select class="form-control" id="sort" name="sort">
                                <option value="id" <?php echo $sortBy === 'id' ? 'selected' : ''; ?>>고객 ID</option>
                                <option value="full_name" <?php echo $sortBy === 'full_name' ? 'selected' : ''; ?>>이름</option>
                                <option value="email" <?php echo $sortBy === 'email' ? 'selected' : ''; ?>>이메일</option>
                                <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>등록일</option>
                                <option value="last_login" <?php echo $sortBy === 'last_login' ? 'selected' : ''; ?>>마지막 로그인</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="order">정렬 순서</label>
                            <select class="form-control" id="order" name="order">
                                <option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>오름차순</option>
                                <option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>내림차순</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">검색</button>
                            <a href="<?php echo SERVER_URL; ?>/dashboard/customer/customer-list.php" class="btn btn-secondary ml-2">초기화</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 고객 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">고객 목록</h3>
                <div class="card-tools">
                    <a href="<?php echo SERVER_URL; ?>/dashboard/customer/customer-add.php" class="btn btn-success btn-sm">
                        <i class="fas fa-user-plus"></i> 고객 추가
                    </a>
                    <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#importModal">
                        <i class="fas fa-file-import"></i> 일괄 가져오기
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" id="exportButton">
                        <i class="fas fa-file-export"></i> 내보내기
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>고객 코드</th>
                            <th>이름</th>
                            <th>이메일</th>
                            <th>전화번호</th>
                            <th>상태</th>
                            <th>인증 상태</th>
                            <th>등록일</th>
                            <th>마지막 로그인</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="10" class="text-center">고객 정보가 없습니다.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo escape($customer['id']); ?></td>
                                    <td><?php echo escape($customer['customer_code']); ?></td>
                                    <td><?php echo escape($customer['full_name']); ?></td>
                                    <td><?php echo escape($customer['email']); ?></td>
                                    <td><?php echo escape($customer['phone']); ?></td>
                                    <td>
                                        <?php if ($customer['status'] === 'active'): ?>
                                            <span class="badge badge-success">활성</span>
                                        <?php elseif ($customer['status'] === 'inactive'): ?>
                                            <span class="badge badge-secondary">비활성</span>
                                        <?php elseif ($customer['status'] === 'suspended'): ?>
                                            <span class="badge badge-danger">정지</span>
                                        <?php else: ?>
                                            <span class="badge badge-info"><?php echo escape($customer['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($customer['verification_status'] === 'verified'): ?>
                                            <span class="badge badge-success">인증됨</span>
                                        <?php elseif ($customer['verification_status'] === 'pending'): ?>
                                            <span class="badge badge-warning">대기 중</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">미인증</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($customer['created_at'], 'Y-m-d'); ?></td>
                                    <td><?php echo formatDate($customer['last_login'], 'Y-m-d H:i'); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="customer-view.php?id=<?php echo $customer['id']; ?>" class="btn btn-info btn-sm" title="보기">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="customer-edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary btn-sm" title="수정">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm delete-btn" title="삭제" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo $customer['id']; ?>" data-name="<?php echo escape($customer['full_name']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <div class="float-left">
                    전체 <?php echo $totalCustomers; ?>명 중 <?php echo count($customers); ?>명 표시
                </div>
                <ul class="pagination pagination-sm m-0 float-right">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>&limit=<?php echo $limit; ?>">&laquo;</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>&limit=<?php echo $limit; ?>">&lsaquo;</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>&limit=<?php echo $limit; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>&limit=<?php echo $limit; ?>">&rsaquo;</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>&limit=<?php echo $limit; ?>">&raquo;</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">고객 삭제 확인</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>고객 '<span id="customerName"></span>'을(를) 삭제하시겠습니까?</p>
                <p class="text-danger">이 작업은 되돌릴 수 없습니다.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                <form method="post">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="customer_id" id="deleteCustomerId">
                    <button type="submit" class="btn btn-danger">삭제</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 일괄 가져오기 모달 -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">고객 일괄 가져오기</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="importFile">CSV 파일 선택</label>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="importFile" name="import_file" accept=".csv">
                                <label class="custom-file-label" for="importFile">파일 선택</label>
                            </div>
                        </div>
                        <small class="form-text text-muted">CSV 형식의 파일만 지원합니다. <a href="<?php echo SERVER_URL; ?>/assets/templates/customer_import_template.csv">템플릿 다운로드</a></small>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="headerRow" name="header_row" checked>
                            <label class="custom-control-label" for="headerRow">첫 번째 행은 헤더입니다</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="updateExisting" name="update_existing" checked>
                            <label class="custom-control-label" for="updateExisting">기존 고객 정보 업데이트</label>
                        </div>
                    </div>
                    <input type="hidden" name="action" value="import">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">가져오기</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// 삭제 모달 이벤트
$('#deleteModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    var name = button.data('name');
    
    var modal = $(this);
    modal.find('#customerName').text(name);
    modal.find('#deleteCustomerId').val(id);
});

// 파일 입력 이벤트
$('.custom-file-input').on('change', function() {
    var fileName = $(this).val().split('\\').pop();
    $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
});

// 내보내기 버튼 이벤트
$('#exportButton').on('click', function() {
    // 실제 환경에서는 AJAX 요청 또는 새 창을 열어 내보내기 처리
    alert('고객 목록 내보내기 기능은 구현 중입니다.');
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
