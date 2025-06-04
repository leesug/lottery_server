<?php
/**
 * 접근 IP 관리 페이지
 */

// 오류 표시 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 헤더 설정
header('Content-Type: text/html; charset=utf-8');

// 출력 버퍼링 시작
ob_start();

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "접근 IP 관리";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 메시지 변수
$successMessage = "";
$errorMessage = "";

// IP 접근 설정 관리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF 토큰 검증 (실제 구현 시 추가)
    
    try {
        if ($_POST['action'] === 'add_ip') {
            $ipAddress = $_POST['ip_address'] ?? '';
            $description = $_POST['description'] ?? '';
            $accessType = $_POST['access_type'] ?? 'allow';
            $status = isset($_POST['status']) && $_POST['status'] === 'on' ? 'active' : 'inactive';
            
            // IP 주소 유효성 검사
            if (!filter_var($ipAddress, FILTER_VALIDATE_IP) && !isValidIpRange($ipAddress)) {
                throw new Exception("유효하지 않은 IP 주소 또는 범위입니다.");
            }
            
            // IP 주소 추가 (예시)
            // 실제로는 데이터베이스에 저장
            
            $successMessage = "IP 주소가 성공적으로 추가되었습니다.";
        } elseif ($_POST['action'] === 'edit_ip') {
            $ipId = $_POST['ip_id'] ?? 0;
            $ipAddress = $_POST['ip_address'] ?? '';
            $description = $_POST['description'] ?? '';
            $accessType = $_POST['access_type'] ?? 'allow';
            $status = isset($_POST['status']) && $_POST['status'] === 'on' ? 'active' : 'inactive';
            
            // IP 주소 유효성 검사
            if (!filter_var($ipAddress, FILTER_VALIDATE_IP) && !isValidIpRange($ipAddress)) {
                throw new Exception("유효하지 않은 IP 주소 또는 범위입니다.");
            }
            
            // IP 주소 수정 (예시)
            // 실제로는 데이터베이스 업데이트
            
            $successMessage = "IP 주소가 성공적으로 수정되었습니다.";
        } elseif ($_POST['action'] === 'delete_ip') {
            $ipId = $_POST['ip_id'] ?? 0;
            
            // IP 주소 삭제 (예시)
            // 실제로는 데이터베이스에서 삭제
            
            $successMessage = "IP 주소가 성공적으로 삭제되었습니다.";
        } elseif ($_POST['action'] === 'update_settings') {
            $ipRestrictionEnabled = isset($_POST['ip_restriction_enabled']) && $_POST['ip_restriction_enabled'] === 'on';
            $defaultPolicy = $_POST['default_policy'] ?? 'deny';
            $adminIpExempt = isset($_POST['admin_ip_exempt']) && $_POST['admin_ip_exempt'] === 'on';
            
            // 설정 업데이트 (예시)
            // 실제로는 데이터베이스 업데이트
            
            $successMessage = "IP 접근 설정이 성공적으로 업데이트되었습니다.";
        }
    } catch (Exception $e) {
        $errorMessage = "작업 처리 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 접근 IP 목록 가져오기 (예시 데이터)
// 실제로는 데이터베이스에서 가져옴
$ipList = [
    [
        'id' => 1,
        'ip_address' => '192.168.1.100',
        'description' => '관리자 사무실',
        'access_type' => 'allow',
        'status' => 'active',
        'created_at' => '2025-05-01 09:30:15',
        'created_by' => 'admin'
    ],
    [
        'id' => 2,
        'ip_address' => '10.0.0.0/24',
        'description' => '본사 내부 네트워크',
        'access_type' => 'allow',
        'status' => 'active',
        'created_at' => '2025-05-01 09:45:22',
        'created_by' => 'admin'
    ],
    [
        'id' => 3,
        'ip_address' => '203.0.113.0/24',
        'description' => '원격 지사 네트워크',
        'access_type' => 'allow',
        'status' => 'active',
        'created_at' => '2025-05-10 14:20:33',
        'created_by' => 'admin'
    ],
    [
        'id' => 4,
        'ip_address' => '198.51.100.50',
        'description' => '의심스러운 IP',
        'access_type' => 'deny',
        'status' => 'active',
        'created_at' => '2025-05-15 11:10:45',
        'created_by' => 'admin'
    ]
];

// 현재 IP 접근 설정 (예시)
// 실제로는 데이터베이스에서 가져옴
$ipSettings = [
    'ip_restriction_enabled' => true,
    'default_policy' => 'deny',
    'admin_ip_exempt' => true
];

// 접근 로그 (예시 데이터)
// 실제로는 데이터베이스에서 가져옴
$accessLogs = [
    [
        'id' => 1,
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'url' => '/dashboard/index.php',
        'user' => 'admin',
        'access_status' => 'allowed',
        'timestamp' => '2025-05-18 09:15:22'
    ],
    [
        'id' => 2,
        'ip_address' => '198.51.100.50',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'url' => '/dashboard/index.php',
        'user' => 'unknown',
        'access_status' => 'denied',
        'timestamp' => '2025-05-18 10:20:33'
    ],
    [
        'id' => 3,
        'ip_address' => '203.0.113.15',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
        'url' => '/dashboard/reports/sales.php',
        'user' => 'manager1',
        'access_status' => 'allowed',
        'timestamp' => '2025-05-18 11:30:15'
    ],
    [
        'id' => 4,
        'ip_address' => '172.16.0.100',
        'user_agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:88.0) Gecko/20100101 Firefox/88.0',
        'url' => '/dashboard/system/users.php',
        'user' => 'unknown',
        'access_status' => 'denied',
        'timestamp' => '2025-05-18 12:45:52'
    ]
];

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';

// IP 범위 유효성 검사 함수
function isValidIpRange($ipRange) {
    // CIDR 표기법 (예: 192.168.1.0/24)
    if (preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/', $ipRange, $matches)) {
        for ($i = 1; $i <= 4; $i++) {
            if ($matches[$i] < 0 || $matches[$i] > 255) {
                return false;
            }
        }
        if ($matches[5] < 0 || $matches[5] > 32) {
            return false;
        }
        return true;
    }
    
    // 범위 표기법 (예: 192.168.1.1-192.168.1.10)
    if (preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})-(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $ipRange, $matches)) {
        for ($i = 1; $i <= 8; $i++) {
            if ($matches[$i] < 0 || $matches[$i] > 255) {
                return false;
            }
        }
        return true;
    }
    
    return false;
}
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
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/">홈</a></li>
                    <li class="breadcrumb-item active">시스템 관리</li>
                    <li class="breadcrumb-item active">접근 IP 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h5><i class="icon fas fa-check"></i> 성공!</h5>
            <?php echo $successMessage; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h5><i class="icon fas fa-ban"></i> 오류!</h5>
            <?php echo $errorMessage; ?>
        </div>
        <?php endif; ?>

        <!-- 상태 개요 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo count($ipList); ?></h3>
                        <p>등록된 IP 규칙</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo count(array_filter($ipList, function($ip) { return $ip['access_type'] === 'allow'; })); ?></h3>
                        <p>허용 IP</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo count(array_filter($ipList, function($ip) { return $ip['access_type'] === 'deny'; })); ?></h3>
                        <p>차단 IP</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-ban"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo count(array_filter($accessLogs, function($log) { return $log['access_status'] === 'denied'; })); ?></h3>
                        <p>최근 차단 횟수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <div class="row">
            <div class="col-md-5">
                <!-- IP 접근 설정 -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">IP 접근 설정</h3>
                    </div>
                    <form action="" method="post">
                        <input type="hidden" name="action" value="update_settings">
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="ip_restriction_enabled" name="ip_restriction_enabled" <?php if ($ipSettings['ip_restriction_enabled']) echo 'checked'; ?>>
                                    <label class="custom-control-label" for="ip_restriction_enabled">IP 접근 제한 활성화</label>
                                </div>
                                <small class="form-text text-muted">이 설정을 활성화하면 등록된 IP 규칙에 따라 시스템 접근이 제한됩니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="default_policy">기본 정책</label>
                                <select class="form-control" id="default_policy" name="default_policy">
                                    <option value="allow" <?php if ($ipSettings['default_policy'] === 'allow') echo 'selected'; ?>>허용 (화이트리스트 방식)</option>
                                    <option value="deny" <?php if ($ipSettings['default_policy'] === 'deny') echo 'selected'; ?>>차단 (블랙리스트 방식)</option>
                                </select>
                                <small class="form-text text-muted">허용: 명시적으로 차단된 IP만 접근 거부, 차단: 명시적으로 허용된 IP만 접근 허용</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="admin_ip_exempt" name="admin_ip_exempt" <?php if ($ipSettings['admin_ip_exempt']) echo 'checked'; ?>>
                                    <label class="custom-control-label" for="admin_ip_exempt">관리자는 IP 제한에서 제외</label>
                                </div>
                                <small class="form-text text-muted">관리자 계정은 IP 제한에 상관없이 항상 접근을 허용합니다.</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">설정 저장</button>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
                
                <!-- IP 추가 -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">새 IP 주소 추가</h3>
                    </div>
                    <form action="" method="post">
                        <input type="hidden" name="action" value="add_ip">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="ip_address">IP 주소 / 범위</label>
                                <input type="text" class="form-control" id="ip_address" name="ip_address" placeholder="단일 IP (예: 192.168.1.100) 또는 범위 (예: 192.168.1.0/24)" required>
                                <small class="form-text text-muted">단일 IP 주소 또는 CIDR 표기법(예: 192.168.1.0/24)으로 IP 범위를 입력하세요.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">설명</label>
                                <input type="text" class="form-control" id="description" name="description" placeholder="이 IP의 용도나 소유자 정보">
                            </div>
                            
                            <div class="form-group">
                                <label for="access_type">접근 유형</label>
                                <select class="form-control" id="access_type" name="access_type">
                                    <option value="allow">허용</option>
                                    <option value="deny">차단</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="status" name="status" checked>
                                    <label class="custom-control-label" for="status">활성화</label>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-info">IP 추가</button>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col-md-5 -->
            
            <div class="col-md-7">
                <!-- IP 목록 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">IP 접근 규칙 목록</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>IP 주소 / 범위</th>
                                    <th>설명</th>
                                    <th>접근 유형</th>
                                    <th>상태</th>
                                    <th>생성 일시</th>
                                    <th>액션</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ipList as $ip): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ip['ip_address']); ?></td>
                                    <td><?php echo htmlspecialchars($ip['description']); ?></td>
                                    <td>
                                        <?php if ($ip['access_type'] === 'allow'): ?>
                                        <span class="badge badge-success">허용</span>
                                        <?php else: ?>
                                        <span class="badge badge-danger">차단</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ip['status'] === 'active'): ?>
                                        <span class="badge badge-success">활성</span>
                                        <?php else: ?>
                                        <span class="badge badge-secondary">비활성</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $ip['created_at']; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#editIpModal<?php echo $ip['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-xs" onclick="confirmDeleteIp(<?php echo $ip['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- /.card -->
                
                <!-- 접근 로그 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">최근 접근 로그</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>시간</th>
                                    <th>IP 주소</th>
                                    <th>사용자</th>
                                    <th>URL</th>
                                    <th>상태</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($accessLogs as $log): ?>
                                <tr>
                                    <td><?php echo $log['timestamp']; ?></td>
                                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                    <td><?php echo htmlspecialchars($log['user']); ?></td>
                                    <td><?php echo htmlspecialchars($log['url']); ?></td>
                                    <td>
                                        <?php if ($log['access_status'] === 'allowed'): ?>
                                        <span class="badge badge-success">허용</span>
                                        <?php else: ?>
                                        <span class="badge badge-danger">차단</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer clearfix">
                        <a href="access-logs.php" class="btn btn-sm btn-info float-right">모든 로그 보기</a>
                    </div>
                </div>
                <!-- /.card -->
                
                <!-- IP 정보 -->
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">현재 접속 정보</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-4">현재 IP 주소</dt>
                            <dd class="col-sm-8"><?php echo $_SERVER['REMOTE_ADDR']; ?></dd>
                            
                            <dt class="col-sm-4">현재 시간</dt>
                            <dd class="col-sm-8"><?php echo date('Y-m-d H:i:s'); ?></dd>
                            
                            <dt class="col-sm-4">브라우저</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT']); ?></dd>
                        </dl>
                        <div class="alert alert-warning">
                            <h5><i class="icon fas fa-exclamation-triangle"></i> 주의!</h5>
                            <p>현재 사용 중인 IP 주소를 차단 목록에 추가할 경우, 시스템 접근이 불가능해질 수 있습니다.</p>
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col-md-7 -->
        </div>
        <!-- /.row -->
    </div>
</section>
<!-- /.content -->

<!-- IP 편집 모달 -->
<?php foreach ($ipList as $ip): ?>
<div class="modal fade" id="editIpModal<?php echo $ip['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editIpModalLabel<?php echo $ip['id']; ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="" method="post">
                <input type="hidden" name="action" value="edit_ip">
                <input type="hidden" name="ip_id" value="<?php echo $ip['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="editIpModalLabel<?php echo $ip['id']; ?>">IP 주소 편집</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_ip_address<?php echo $ip['id']; ?>">IP 주소 / 범위</label>
                        <input type="text" class="form-control" id="edit_ip_address<?php echo $ip['id']; ?>" name="ip_address" value="<?php echo htmlspecialchars($ip['ip_address']); ?>" required>
                        <small class="form-text text-muted">단일 IP 주소 또는 CIDR 표기법(예: 192.168.1.0/24)으로 IP 범위를 입력하세요.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description<?php echo $ip['id']; ?>">설명</label>
                        <input type="text" class="form-control" id="edit_description<?php echo $ip['id']; ?>" name="description" value="<?php echo htmlspecialchars($ip['description']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_access_type<?php echo $ip['id']; ?>">접근 유형</label>
                        <select class="form-control" id="edit_access_type<?php echo $ip['id']; ?>" name="access_type">
                            <option value="allow" <?php if ($ip['access_type'] === 'allow') echo 'selected'; ?>>허용</option>
                            <option value="deny" <?php if ($ip['access_type'] === 'deny') echo 'selected'; ?>>차단</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_status<?php echo $ip['id']; ?>" name="status" <?php if ($ip['status'] === 'active') echo 'checked'; ?>>
                            <label class="custom-control-label" for="edit_status<?php echo $ip['id']; ?>">활성화</label>
                        </div>
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
<?php endforeach; ?>

<!-- IP 삭제 Form (Hidden) -->
<form id="deleteIpForm" action="" method="post" style="display: none;">
    <input type="hidden" name="action" value="delete_ip">
    <input type="hidden" name="ip_id" id="delete_ip_id" value="">
</form>

<script>
// IP 삭제 확인
function confirmDeleteIp(ipId) {
    if (confirm('이 IP 주소를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
        document.getElementById('delete_ip_id').value = ipId;
        document.getElementById('deleteIpForm').submit();
    }
}

// IP 주소 유효성 검사
function validateIpAddress(ipAddress) {
    // 단일 IP 주소
    var ipPattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
    
    // CIDR 표기법
    var cidrPattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/;
    
    // 범위 표기법
    var rangePattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})-(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
    
    return ipPattern.test(ipAddress) || cidrPattern.test(ipAddress) || rangePattern.test(ipAddress);
}

$(document).ready(function() {
    // IP 주소 입력 유효성 검사
    $('#ip_address').on('blur', function() {
        var ipAddress = $(this).val();
        if (!validateIpAddress(ipAddress) && ipAddress.trim() !== '') {
            $(this).addClass('is-invalid');
            $(this).after('<div class="invalid-feedback">유효하지 않은 IP 주소 또는 범위입니다.</div>');
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });
    
    // 폼 제출 전 유효성 검사
    $('form').submit(function() {
        var ipAddress = $('#ip_address').val();
        if (!validateIpAddress(ipAddress) && ipAddress.trim() !== '') {
            alert('유효하지 않은 IP 주소 또는 범위입니다.');
            return false;
        }
        return true;
    });
});
</script>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';

// 출력 버퍼 플러시
ob_end_flush();
?>