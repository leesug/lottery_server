<?php
/**
 * 단말기 관리 페이지
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
$pageTitle = '단말기 관리';
$pageHeader = '단말기 관리';

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// 검색 필터
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// SQL 쿼리 구성
$sql = "SELECT t.*, u.username as agent_name 
        FROM terminals t
        LEFT JOIN users u ON t.agent_id = u.id
        WHERE 1=1";

$countSql = "SELECT COUNT(*) as total FROM terminals t WHERE 1=1";
$params = [];

// 검색어 필터 적용
if (!empty($search)) {
    $sql .= " AND (t.terminal_code LIKE ? OR t.location LIKE ?)";
    $countSql .= " AND (t.terminal_code LIKE ? OR t.location LIKE ?)";
    $params[] = ['type' => 's', 'value' => "%$search%"];
    $params[] = ['type' => 's', 'value' => "%$search%"];
}

// 상태 필터 적용
if (!empty($statusFilter)) {
    $sql .= " AND t.status = ?";
    $countSql .= " AND t.status = ?";
    $params[] = ['type' => 's', 'value' => $statusFilter];
}

// 정렬 적용
$sql .= " ORDER BY t.created_at DESC LIMIT ?, ?";
$params[] = ['type' => 'i', 'value' => $offset];
$params[] = ['type' => 'i', 'value' => $perPage];

// 쿼리 실행
$terminals = fetchAll($sql, $params);

// 전체 단말기 수 계산
$countParams = array_slice($params, 0, -2); // LIMIT 파라미터 제외
$totalResult = fetchOne($countSql, $countParams);
$total = $totalResult ? $totalResult['total'] : 0;

// 총 페이지 수
$totalPages = ceil($total / $perPage);

// 단말기 상태 옵션
$statusOptions = [
    'active' => '활성',
    'inactive' => '비활성',
    'maintenance' => '유지보수'
];

// 단말기 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_terminal'])) {
    $terminalId = (int)$_POST['delete_terminal'];
    
    // 단말기 삭제 전에 관련 티켓이 있는지 확인
    $checkTickets = fetchOne("SELECT COUNT(*) as count FROM tickets WHERE terminal_id = ?", [
        ['type' => 'i', 'value' => $terminalId]
    ]);
    
    if ($checkTickets && $checkTickets['count'] > 0) {
        $_SESSION['flash_message'] = "단말기에 연결된 티켓이 있어 삭제할 수 없습니다.";
        $_SESSION['flash_type'] = "danger";
    } else {
        // 단말기 삭제
        $result = execute("DELETE FROM terminals WHERE id = ?", [
            ['type' => 'i', 'value' => $terminalId]
        ]);
        
        if ($result) {
            logInfo("단말기 ID: $terminalId 삭제됨", 'terminal');
            $_SESSION['flash_message'] = "단말기가 성공적으로 삭제되었습니다.";
            $_SESSION['flash_type'] = "success";
        } else {
            logError("단말기 ID: $terminalId 삭제 실패", 'terminal');
            $_SESSION['flash_message'] = "단말기 삭제 중 오류가 발생했습니다.";
            $_SESSION['flash_type'] = "danger";
        }
    }
    
    // 현재 페이지로 리디렉션
    header("Location: /server/dashboard/terminals.php");
    exit;
}

// 단말기 상태 변경 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $terminalId = (int)$_POST['terminal_id'];
    $newStatus = sanitizeInput($_POST['new_status']);
    
    // 유효한 상태인지 확인
    if (array_key_exists($newStatus, $statusOptions)) {
        // 상태 업데이트
        $result = execute("UPDATE terminals SET status = ? WHERE id = ?", [
            ['type' => 's', 'value' => $newStatus],
            ['type' => 'i', 'value' => $terminalId]
        ]);
        
        if ($result) {
            logInfo("단말기 ID: $terminalId 상태 변경: $newStatus", 'terminal');
            $_SESSION['flash_message'] = "단말기 상태가 성공적으로 변경되었습니다.";
            $_SESSION['flash_type'] = "success";
        } else {
            logError("단말기 ID: $terminalId 상태 변경 실패", 'terminal');
            $_SESSION['flash_message'] = "단말기 상태 변경 중 오류가 발생했습니다.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_message'] = "잘못된 상태 값입니다.";
        $_SESSION['flash_type'] = "danger";
    }
    
    // 현재 페이지로 리디렉션
    header("Location: /server/dashboard/terminals.php");
    exit;
}

// 에이전트 목록 가져오기 (단말기 추가/수정 폼용)
$agents = fetchAll("SELECT id, username FROM users WHERE role = 'agent' AND status = 'active'");

// 헤더 포함
include_once '../templates/header.php';
?>

<!-- 단말기 목록 -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">단말기 목록</h5>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addTerminalModal">
            <i class="fas fa-plus"></i> 단말기 추가
        </button>
    </div>
    <div class="card-body">
        <!-- 검색 및 필터 폼 -->
        <form action="" method="get" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="검색어 입력..." value="<?php echo $search; ?>">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="">모든 상태</option>
                        <?php foreach ($statusOptions as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $statusFilter === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5 text-right">
                    <a href="/server/dashboard/terminals.php" class="btn btn-secondary">필터 초기화</a>
                </div>
            </div>
        </form>
        
        <!-- 단말기 목록 테이블 -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>단말기 코드</th>
                        <th>위치</th>
                        <th>담당 에이전트</th>
                        <th>상태</th>
                        <th>마지막 연결</th>
                        <th>생성일</th>
                        <th>작업</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($terminals && count($terminals) > 0): ?>
                        <?php foreach ($terminals as $terminal): ?>
                            <tr>
                                <td><?php echo $terminal['id']; ?></td>
                                <td><?php echo sanitizeInput($terminal['terminal_code']); ?></td>
                                <td><?php echo sanitizeInput($terminal['location']); ?></td>
                                <td><?php echo $terminal['agent_name'] ? sanitizeInput($terminal['agent_name']) : '없음'; ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $terminal['status'] === 'active' ? 'success' : 
                                            ($terminal['status'] === 'inactive' ? 'danger' : 'warning');
                                    ?>">
                                        <?php echo $statusOptions[$terminal['status']]; ?>
                                    </span>
                                </td>
                                <td><?php echo $terminal['last_connection'] ? formatDate($terminal['last_connection'], 'Y-m-d H:i:s') : '연결 없음'; ?></td>
                                <td><?php echo formatDate($terminal['created_at'], 'Y-m-d'); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <!-- 상태 변경 버튼 -->
                                        <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            상태 변경
                                        </button>
                                        <div class="dropdown-menu">
                                            <?php foreach ($statusOptions as $key => $label): ?>
                                                <?php if ($key !== $terminal['status']): ?>
                                                    <form action="" method="post">
                                                        <input type="hidden" name="terminal_id" value="<?php echo $terminal['id']; ?>">
                                                        <input type="hidden" name="new_status" value="<?php echo $key; ?>">
                                                        <button type="submit" name="update_status" class="dropdown-item">
                                                            <?php echo $label; ?>로 변경
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <!-- 수정 버튼 -->
                                        <button type="button" class="btn btn-sm btn-primary edit-terminal" 
                                                data-id="<?php echo $terminal['id']; ?>"
                                                data-code="<?php echo htmlspecialchars($terminal['terminal_code'], ENT_QUOTES); ?>"
                                                data-location="<?php echo htmlspecialchars($terminal['location'], ENT_QUOTES); ?>"
                                                data-agent="<?php echo $terminal['agent_id']; ?>"
                                                data-status="<?php echo $terminal['status']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <!-- 삭제 버튼 -->
                                        <form action="" method="post" class="d-inline" onsubmit="return confirm('정말로 이 단말기를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.');">
                                            <button type="submit" name="delete_terminal" value="<?php echo $terminal['id']; ?>" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">단말기가 없습니다.</td>
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
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>">이전</a>
                    </li>
                    
                    <!-- 페이지 번호 -->
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- 다음 페이지 -->
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>">다음</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- 단말기 추가 모달 -->
<div class="modal fade" id="addTerminalModal" tabindex="-1" role="dialog" aria-labelledby="addTerminalModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTerminalModalLabel">단말기 추가</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/server/dashboard/terminal-process.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="terminal_code">단말기 코드</label>
                        <input type="text" class="form-control" id="terminal_code" name="terminal_code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">위치</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="agent_id">담당 에이전트</label>
                        <select class="form-control" id="agent_id" name="agent_id">
                            <option value="">에이전트 선택</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo $agent['id']; ?>"><?php echo sanitizeInput($agent['username']); ?></option>
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

<!-- 단말기 수정 모달 -->
<div class="modal fade" id="editTerminalModal" tabindex="-1" role="dialog" aria-labelledby="editTerminalModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTerminalModalLabel">단말기 수정</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/server/dashboard/terminal-process.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="terminal_id" id="edit_terminal_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="edit_terminal_code">단말기 코드</label>
                        <input type="text" class="form-control" id="edit_terminal_code" name="terminal_code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_location">위치</label>
                        <input type="text" class="form-control" id="edit_location" name="location" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_agent_id">담당 에이전트</label>
                        <select class="form-control" id="edit_agent_id" name="agent_id">
                            <option value="">에이전트 선택</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo $agent['id']; ?>"><?php echo sanitizeInput($agent['username']); ?></option>
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

<?php
// 자바스크립트 설정
$inlineJs = <<<JS
// 단말기 수정 모달 초기화
$(document).ready(function() {
    $('.edit-terminal').click(function() {
        var id = $(this).data('id');
        var code = $(this).data('code');
        var location = $(this).data('location');
        var agent = $(this).data('agent');
        var status = $(this).data('status');
        
        $('#edit_terminal_id').val(id);
        $('#edit_terminal_code').val(code);
        $('#edit_location').val(location);
        $('#edit_agent_id').val(agent);
        $('#edit_status').val(status);
        
        $('#editTerminalModal').modal('show');
    });
});
JS;

// 푸터 포함
include_once '../templates/footer.php';
?>
