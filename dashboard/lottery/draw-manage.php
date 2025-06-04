<?php
/**
 * 회차 관리 페이지
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
$pageTitle = '회차 관리';
$pageHeader = '회차 관리';

// 추가 CSS
$extraCss = '/server/assets/css/lottery.css';

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// 검색 필터
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// SQL 쿼리 구성 (가상의 draws 테이블 사용)
$sql = "SELECT * FROM draws WHERE 1=1";
$countSql = "SELECT COUNT(*) as total FROM draws WHERE 1=1";
$params = [];

// 검색어 필터 적용
if (!empty($search)) {
    $sql .= " AND (draw_number LIKE ? OR description LIKE ?)";
    $countSql .= " AND (draw_number LIKE ? OR description LIKE ?)";
    $params[] = ['type' => 's', 'value' => "%$search%"];
    $params[] = ['type' => 's', 'value' => "%$search%"];
}

// 상태 필터 적용
if (!empty($statusFilter)) {
    $sql .= " AND status = ?";
    $countSql .= " AND status = ?";
    $params[] = ['type' => 's', 'value' => $statusFilter];
}

// 정렬 적용
$sql .= " ORDER BY draw_date DESC LIMIT ?, ?";
$params[] = ['type' => 'i', 'value' => $offset];
$params[] = ['type' => 'i', 'value' => $perPage];

// 테이블이 없을 경우를 대비한 더미 데이터
$draws = [];
$dummyData = true;
$total = 0;
$totalPages = 1;

try {
    // 쿼리 실행 시도
    $draws = fetchAll($sql, $params);
    
    // 전체 회차 수 계산
    $countParams = array_slice($params, 0, -2); // LIMIT 파라미터 제외
    $totalResult = fetchOne($countSql, $countParams);
    $total = $totalResult ? $totalResult['total'] : 0;
    
    // 총 페이지 수
    $totalPages = ceil($total / $perPage);
    $dummyData = false;
} catch (Exception $e) {
    // 테이블이 없거나 오류 발생 시 더미 데이터 사용
    logError("회차 데이터 조회 실패: " . $e->getMessage(), 'lottery');
    
    // 더미 데이터 생성
    $draws = [];
    for ($i = 1; $i <= 10; $i++) {
        $drawNumber = 1000 + $i;
        $startDate = date('Y-m-d', strtotime("-" . ($i * 7) . " days"));
        $endDate = date('Y-m-d', strtotime("-" . ($i * 7 - 6) . " days"));
        $drawDate = date('Y-m-d', strtotime("-" . ($i * 7 - 7) . " days"));
        
        $status = 'completed';
        if ($i == 1) {
            $status = 'active';
        } else if ($i == 2) {
            $status = 'pending';
        }
        
        $draws[] = [
            'id' => $i,
            'draw_number' => $drawNumber,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'draw_date' => $drawDate,
            'status' => $status,
            'description' => $drawNumber . "회차 로또복권",
            'created_at' => date('Y-m-d H:i:s', strtotime("-" . ($i * 7 + 1) . " days")),
            'updated_at' => date('Y-m-d H:i:s', strtotime("-" . ($i * 7 + 1) . " days"))
        ];
    }
    
    $total = count($draws);
    $totalPages = ceil($total / $perPage);
}

// 회차 상태 옵션
$statusOptions = [
    'pending' => '대기중',
    'active' => '진행중',
    'completed' => '완료',
    'canceled' => '취소'
];

// 회차 상태 변경 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $drawId = (int)$_POST['draw_id'];
    $newStatus = sanitizeInput($_POST['new_status']);
    
    // 유효한 상태인지 확인
    if (array_key_exists($newStatus, $statusOptions)) {
        // 실제 데이터베이스가 있을 경우 상태 업데이트
        if (!$dummyData) {
            $result = execute("UPDATE draws SET status = ? WHERE id = ?", [
                ['type' => 's', 'value' => $newStatus],
                ['type' => 'i', 'value' => $drawId]
            ]);
            
            if ($result) {
                logInfo("회차 ID: $drawId 상태 변경: $newStatus", 'lottery');
                $_SESSION['flash_message'] = "회차 상태가 성공적으로 변경되었습니다.";
                $_SESSION['flash_type'] = "success";
            } else {
                logError("회차 ID: $drawId 상태 변경 실패", 'lottery');
                $_SESSION['flash_message'] = "회차 상태 변경 중 오류가 발생했습니다.";
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            // 더미 데이터일 경우 메시지만 표시
            $_SESSION['flash_message'] = "더미 데이터 모드: 회차 ID $drawId의 상태를 '$newStatus'로 변경 요청됨";
            $_SESSION['flash_type'] = "warning";
        }
    } else {
        $_SESSION['flash_message'] = "잘못된 상태 값입니다.";
        $_SESSION['flash_type'] = "danger";
    }
    
    // 현재 페이지로 리디렉션
    header("Location: /server/dashboard/lottery/draw-manage.php");
    exit;
}

// 회차 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_draw'])) {
    $drawId = (int)$_POST['delete_draw'];
    
    // 실제 데이터베이스가 있을 경우 삭제
    if (!$dummyData) {
        // 회차에 연결된 다른 데이터가 있는지 확인 (실제 환경에서는 필요한 검증 진행)
        $canDelete = true;
        
        if ($canDelete) {
            $result = execute("DELETE FROM draws WHERE id = ?", [
                ['type' => 'i', 'value' => $drawId]
            ]);
            
            if ($result) {
                logInfo("회차 ID: $drawId 삭제됨", 'lottery');
                $_SESSION['flash_message'] = "회차가 성공적으로 삭제되었습니다.";
                $_SESSION['flash_type'] = "success";
            } else {
                logError("회차 ID: $drawId 삭제 실패", 'lottery');
                $_SESSION['flash_message'] = "회차 삭제 중 오류가 발생했습니다.";
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            $_SESSION['flash_message'] = "회차에 연결된 데이터가 있어 삭제할 수 없습니다.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        // 더미 데이터일 경우 메시지만 표시
        $_SESSION['flash_message'] = "더미 데이터 모드: 회차 ID $drawId의 삭제 요청됨";
        $_SESSION['flash_type'] = "warning";
    }
    
    // 현재 페이지로 리디렉션
    header("Location: /server/dashboard/lottery/draw-manage.php");
    exit;
}

// 현재 페이지 정보
$pageTitle = "추첨 관리";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- 회차 관리 -->
<div class="content-wrapper">
    <!-- 페이지 헤더 -->
    <div class="page-header">
        <h2 class="page-title"><?php echo $pageHeader; ?></h2>
        <p class="page-description">로또 회차를 등록, 수정 및 관리합니다.</p>
    </div>
    
    <?php if ($dummyData): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 현재 더미 데이터를 표시하고 있습니다. 실제 데이터베이스 테이블이 없거나 연결되지 않았습니다.
        </div>
    <?php endif; ?>
    
    <!-- 회차 관리 카드 -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">회차 목록</h5>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addDrawModal">
                <i class="fas fa-plus"></i> 새 회차 등록
            </button>
        </div>
        <div class="card-body">
            <!-- 검색 및 필터 폼 -->
            <form action="" method="get" class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="회차번호 또는 설명 검색..." value="<?php echo $search; ?>">
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
                        <a href="/server/dashboard/lottery/draw-manage.php" class="btn btn-secondary">필터 초기화</a>
                    </div>
                </div>
            </form>
            
            <!-- 회차 목록 테이블 -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>회차번호</th>
                            <th>판매시작일</th>
                            <th>판매종료일</th>
                            <th>추첨일</th>
                            <th>상태</th>
                            <th>설명</th>
                            <th>등록일</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($draws) > 0): ?>
                            <?php foreach ($draws as $draw): ?>
                                <tr>
                                    <td><?php echo $draw['id']; ?></td>
                                    <td><?php echo sanitizeInput($draw['draw_number']); ?></td>
                                    <td><?php echo formatDate($draw['start_date'], 'Y-m-d'); ?></td>
                                    <td><?php echo formatDate($draw['end_date'], 'Y-m-d'); ?></td>
                                    <td><?php echo formatDate($draw['draw_date'], 'Y-m-d'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $draw['status'] === 'active' ? 'success' : 
                                                ($draw['status'] === 'pending' ? 'warning' : 
                                                    ($draw['status'] === 'completed' ? 'primary' : 'danger'));
                                        ?>">
                                            <?php echo $statusOptions[$draw['status']]; ?>
                                        </span>
                                    </td>
                                    <td><?php echo sanitizeInput($draw['description']); ?></td>
                                    <td><?php echo formatDate($draw['created_at'], 'Y-m-d'); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <!-- 상태 변경 버튼 -->
                                            <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                상태 변경
                                            </button>
                                            <div class="dropdown-menu">
                                                <?php foreach ($statusOptions as $key => $label): ?>
                                                    <?php if ($key !== $draw['status']): ?>
                                                        <form action="" method="post">
                                                            <input type="hidden" name="draw_id" value="<?php echo $draw['id']; ?>">
                                                            <input type="hidden" name="new_status" value="<?php echo $key; ?>">
                                                            <button type="submit" name="update_status" class="dropdown-item">
                                                                <?php echo $label; ?>로 변경
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <!-- 수정 버튼 -->
                                            <button type="button" class="btn btn-sm btn-primary edit-draw" 
                                                    data-id="<?php echo $draw['id']; ?>"
                                                    data-number="<?php echo htmlspecialchars($draw['draw_number'], ENT_QUOTES); ?>"
                                                    data-start="<?php echo htmlspecialchars($draw['start_date'], ENT_QUOTES); ?>"
                                                    data-end="<?php echo htmlspecialchars($draw['end_date'], ENT_QUOTES); ?>"
                                                    data-draw="<?php echo htmlspecialchars($draw['draw_date'], ENT_QUOTES); ?>"
                                                    data-status="<?php echo htmlspecialchars($draw['status'], ENT_QUOTES); ?>"
                                                    data-description="<?php echo htmlspecialchars($draw['description'], ENT_QUOTES); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <!-- 추첨번호 관리 버튼 -->
                                            <a href="/server/dashboard/lottery/winning-numbers.php?draw_id=<?php echo $draw['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-random"></i>
                                            </a>
                                            
                                            <!-- 삭제 버튼 -->
                                            <?php if ($draw['status'] !== 'active'): ?>
                                                <form action="" method="post" class="d-inline" onsubmit="return confirm('정말로 이 회차를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.');">
                                                    <button type="submit" name="delete_draw" value="<?php echo $draw['id']; ?>" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">회차 정보가 없습니다.</td>
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
</div>

<!-- 회차 추가 모달 -->
<div class="modal fade" id="addDrawModal" tabindex="-1" role="dialog" aria-labelledby="addDrawModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDrawModalLabel">새 회차 등록</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/server/dashboard/lottery/draw-process.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="draw_number">회차번호</label>
                        <input type="number" class="form-control" id="draw_number" name="draw_number" required min="1">
                    </div>
                    
                    <div class="form-group">
                        <label for="start_date">판매시작일</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">판매종료일</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="draw_date">추첨일</label>
                        <input type="date" class="form-control" id="draw_date" name="draw_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">상태</label>
                        <select class="form-control" id="status" name="status" required>
                            <?php foreach ($statusOptions as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">설명</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">등록</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 회차 수정 모달 -->
<div class="modal fade" id="editDrawModal" tabindex="-1" role="dialog" aria-labelledby="editDrawModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDrawModalLabel">회차 수정</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/server/dashboard/lottery/draw-process.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="draw_id" id="edit_draw_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="edit_draw_number">회차번호</label>
                        <input type="number" class="form-control" id="edit_draw_number" name="draw_number" required min="1">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_start_date">판매시작일</label>
                        <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_end_date">판매종료일</label>
                        <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_draw_date">추첨일</label>
                        <input type="date" class="form-control" id="edit_draw_date" name="draw_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_status">상태</label>
                        <select class="form-control" id="edit_status" name="status" required>
                            <?php foreach ($statusOptions as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">설명</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
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
// 회차 관리 페이지 초기화
document.addEventListener('DOMContentLoaded', function() {
    console.log('회차 관리 페이지 초기화');
    
    // 현재 날짜를 YYYY-MM-DD 형식으로 가져오기
    function getCurrentDate() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        return `\${year}-\${month}-\${day}`;
    }
    
    // 새 회차 등록 시 기본 날짜 설정
    const currentDate = getCurrentDate();
    
    if (document.getElementById('start_date')) {
        document.getElementById('start_date').value = currentDate;
    }
    
    if (document.getElementById('end_date')) {
        // 종료일은 7일 후로 설정
        const endDate = new Date();
        endDate.setDate(endDate.getDate() + 7);
        const endYear = endDate.getFullYear();
        const endMonth = String(endDate.getMonth() + 1).padStart(2, '0');
        const endDay = String(endDate.getDate()).padStart(2, '0');
        document.getElementById('end_date').value = `\${endYear}-\${endMonth}-\${endDay}`;
    }
    
    if (document.getElementById('draw_date')) {
        // 추첨일은 7일 후 + 1일로 설정
        const drawDate = new Date();
        drawDate.setDate(drawDate.getDate() + 8);
        const drawYear = drawDate.getFullYear();
        const drawMonth = String(drawDate.getMonth() + 1).padStart(2, '0');
        const drawDay = String(drawDate.getDate()).padStart(2, '0');
        document.getElementById('draw_date').value = `\${drawYear}-\${drawMonth}-\${drawDay}`;
    }
    
    // 회차 번호 자동 증가 (더미 데이터에서는 1000 + 1)
    if (document.getElementById('draw_number')) {
        document.getElementById('draw_number').value = 1011; // 예시로 마지막 회차 + 1
    }
    
    // 회차 수정 모달 초기화
    const editButtons = document.querySelectorAll('.edit-draw');
    
    editButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const number = this.getAttribute('data-number');
            const startDate = this.getAttribute('data-start');
            const endDate = this.getAttribute('data-end');
            const drawDate = this.getAttribute('data-draw');
            const status = this.getAttribute('data-status');
            const description = this.getAttribute('data-description');
            
            document.getElementById('edit_draw_id').value = id;
            document.getElementById('edit_draw_number').value = number;
            document.getElementById('edit_start_date').value = startDate;
            document.getElementById('edit_end_date').value = endDate;
            document.getElementById('edit_draw_date').value = drawDate;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_description').value = description;
            
            $('#editDrawModal').modal('show');
        });
    });
    
    // 날짜 유효성 검사
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const startDate = new Date(this.querySelector('[name="start_date"]').value);
            const endDate = new Date(this.querySelector('[name="end_date"]').value);
            const drawDate = new Date(this.querySelector('[name="draw_date"]').value);
            
            if (endDate <= startDate) {
                e.preventDefault();
                alert('판매종료일은 판매시작일보다 이후여야 합니다.');
                return false;
            }
            
            if (drawDate <= endDate) {
                e.preventDefault();
                alert('추첨일은 판매종료일보다 이후여야 합니다.');
                return false;
            }
        });
    });
});
JS;

// 푸터 포함
include_once '../../templates/footer.php';
?>
