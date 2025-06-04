<?php
/**
 * 사용자 관리 페이지
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 로그인 확인
requireLogin();

// 관리자 권한 확인
requireAdmin();

// 페이지 제목 설정
$pageTitle = '사용자 관리';
$pageHeader = '사용자 관리';

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// 검색 필터
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// SQL 쿼리 구성
$sql = "SELECT * FROM users WHERE 1=1";
$countSql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$params = [];

// 검색어 필터 적용
if (!empty($search)) {
    $sql .= " AND (username LIKE ? OR email LIKE ?)";
    $countSql .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = ['type' => 's', 'value' => "%$search%"];
    $params[] = ['type' => 's', 'value' => "%$search%"];
}

// 역할 필터 적용
if (!empty($roleFilter)) {
    $sql .= " AND role = ?";
    $countSql .= " AND role = ?";
    $params[] = ['type' => 's', 'value' => $roleFilter];
}

// 상태 필터 적용
if (!empty($statusFilter)) {
    $sql .= " AND status = ?";
    $countSql .= " AND status = ?";
    $params[] = ['type' => 's', 'value' => $statusFilter];
}

// 정렬 적용
$sql .= " ORDER BY created_at DESC LIMIT ?, ?";
$params[] = ['type' => 'i', 'value' => $offset];
$params[] = ['type' => 'i', 'value' => $perPage];

// 쿼리 실행
$users = fetchAll($sql, $params);

// 전체 사용자 수 계산
$countParams = array_slice($params, 0, -2); // LIMIT 파라미터 제외
$totalResult = fetchOne($countSql, $countParams);
$total = $totalResult ? $totalResult['total'] : 0;

// 총 페이지 수
$totalPages = ceil($total / $perPage);

// 역할 옵션
$roleOptions = [
    'admin' => '관리자',
    'agent' => '에이전트',
    'user' => '사용자'
];

// 상태 옵션
$statusOptions = [
    'active' => '활성',
    'inactive' => '비활성',
    'suspended' => '정지'
];

// 사용자 상태 변경 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $userId = (int)$_POST['user_id'];
    $newStatus = sanitizeInput($_POST['new_status']);
    
    // 현재 로그인한 사용자가 자신의 상태를 변경하려는지 확인
    if ($userId === getCurrentUserId()) {
        $_SESSION['flash_message'] = "자신의 상태는 변경할 수 없습니다.";
        $_SESSION['flash_type'] = "danger";
    } else {
        // 유효한 상태인지 확인
        if (array_key_exists($newStatus, $statusOptions)) {
            // 상태 업데이트
            $result = execute("UPDATE users SET status = ? WHERE id = ?", [
                ['type' => 's', 'value' => $newStatus],
                ['type' => 'i', 'value' => $userId]
            ]);
            
            if ($result) {
                logInfo("사용자 ID: $userId 상태 변경: $newStatus", 'user');
                $_SESSION['flash_message'] = "사용자 상태가 성공적으로 변경되었습니다.";
                $_SESSION['flash_type'] = "success";
            } else {
                logError("사용자 ID: $userId 상태 변경 실패", 'user');
                $_SESSION['flash_message'] = "사용자 상태 변경 중 오류가 발생했습니다.";
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            $_SESSION['flash_message'] = "잘못된 상태 값입니다.";
            $_SESSION['flash_type'] = "danger";
        }
    }
    
    // 현재 페이지로 리디렉션
    header("Location: /server/dashboard/users.php");
    exit;
}

// 헤더 포함
include_once '../templates/header.php';
?>

<!-- 사용자 목록 -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">사용자 목록</h5>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
            <i class="fas fa-user-plus"></i> 사용자 추가
        </button>
    </div>
    <div class="card-body">
        <!-- 검색 및 필터 폼 -->
        <form action="" method="get" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="이름 또는 이메일 검색..." value="<?php echo $search; ?>">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="role" class="form-control" onchange="this.form.submit()">
                        <option value="">모든 역할</option>
                        <?php foreach ($roleOptions as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $roleFilter === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
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
                <div class="col-md-4 text-right">
                    <a href="/server/dashboard/users.php" class="btn btn-secondary">필터 초기화</a>
                </div>
            </div>
        </form>
        
        <!-- 사용자 목록 테이블 -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>이름</th>
                        <th>이메일</th>
                        <th>역할</th>
                        <th>상태</th>
                        <th>생성일</th>
                        <th>최종 수정일</th>
                        <th>작업</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users && count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo sanitizeInput($user['username']); ?></td>
                                <td><?php echo sanitizeInput($user['email']); ?></td>
                                <td><?php echo $roleOptions[$user['role']]; ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $user['status'] === 'active' ? 'success' : 
                                            ($user['status'] === 'inactive' ? 'secondary' : 'danger');
                                    ?>">
                                        <?php echo $statusOptions[$user['status']]; ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($user['created_at'], 'Y-m-d'); ?></td>
                                <td><?php echo formatDate($user['updated_at'], 'Y-m-d'); ?></td>
                                <td>
                                    <!-- 현재 로그인한 사용자는 편집할 수 없음 -->
                                    <?php if ($user['id'] !== getCurrentUserId()): ?>
                                        <div class="btn-group">
                                            <!-- 상태 변경 버튼 -->
                                            <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                상태 변경
                                            </button>
                                            <div class="dropdown-menu">
                                                <?php foreach ($statusOptions as $key => $label): ?>
                                                    <?php if ($key !== $user['status']): ?>
                                                        <form action="" method="post">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <input type="hidden" name="new_status" value="<?php echo $key; ?>">
                                                            <button type="submit" name="update_status" class="dropdown-item">
                                                                <?php echo $label; ?>로 변경
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <!-- 수정 버튼 -->
                                            <button type="button" class="btn btn-sm btn-primary edit-user" 
                                                    data-id="<?php echo $user['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>"
                                                    data-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>"
                                                    data-role="<?php echo $user['role']; ?>"
                                                    data-status="<?php echo $user['status']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <!-- 비밀번호 재설정 버튼 -->
                                            <button type="button" class="btn btn-sm btn-warning reset-password" 
                                                    data-id="<?php echo $user['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>">
                                                <i class="fas fa-key"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">현재 로그인 중</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">사용자가 없습니다.</td>
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
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">이전</a>
                    </li>
                    
                    <!-- 페이지 번호 -->
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- 다음 페이지 -->
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">다음</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- 사용자 추가 모달 -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">사용자 추가</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/server/dashboard/user-process.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="username">이름</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">이메일</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">비밀번호</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">비밀번호 확인</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">역할</label>
                        <select class="form-control" id="role" name="role" required>
                            <?php foreach ($roleOptions as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">상태</label>
                        <select class="form-control" id="status" name="status" required>
                            <?php foreach ($statusOptions as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">추가</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 사용자 수정 모달 -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">사용자 수정</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/server/dashboard/user-process.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="edit_username">이름</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_email">이메일</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_role">역할</label>
                        <select class="form-control" id="edit_role" name="role" required>
                            <?php foreach ($roleOptions as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_status">상태</label>
                        <select class="form-control" id="edit_status" name="status" required>
                            <?php foreach ($statusOptions as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 비밀번호 재설정 모달 -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">비밀번호 재설정</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/server/dashboard/user-process.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="reset_user_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <p>다음 사용자의 비밀번호를 재설정합니다: <strong id="reset_username"></strong></p>
                    
                    <div class="form-group">
                        <label for="new_password">새 비밀번호</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_new_password">새 비밀번호 확인</label>
                        <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-warning">재설정</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// 자바스크립트 설정
$inlineJs = <<<JS
// 사용자 수정 모달 초기화
$(document).ready(function() {
    $('.edit-user').click(function() {
        var id = $(this).data('id');
        var username = $(this).data('username');
        var email = $(this).data('email');
        var role = $(this).data('role');
        var status = $(this).data('status');
        
        $('#edit_user_id').val(id);
        $('#edit_username').val(username);
        $('#edit_email').val(email);
        $('#edit_role').val(role);
        $('#edit_status').val(status);
        
        $('#editUserModal').modal('show');
    });
    
    // 비밀번호 재설정 모달 초기화
    $('.reset-password').click(function() {
        var id = $(this).data('id');
        var username = $(this).data('username');
        
        $('#reset_user_id').val(id);
        $('#reset_username').text(username);
        
        $('#resetPasswordModal').modal('show');
    });
    
    // 비밀번호 일치 확인 (사용자 추가)
    $('#addUserModal form').submit(function(e) {
        var password = $('#password').val();
        var confirmPassword = $('#confirm_password').val();
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('비밀번호가 일치하지 않습니다.');
        }
    });
    
    // 비밀번호 일치 확인 (비밀번호 재설정)
    $('#resetPasswordModal form').submit(function(e) {
        var password = $('#new_password').val();
        var confirmPassword = $('#confirm_new_password').val();
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('비밀번호가 일치하지 않습니다.');
        }
    });
});
JS;

// 푸터 포함
include_once '../templates/footer.php';
?>
