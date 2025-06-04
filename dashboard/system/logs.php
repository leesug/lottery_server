<?php
/**
 * 로그 관리 페이지
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
$pageTitle = "로그 관리";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 검색 필터
$logType = isset($_GET['log_type']) ? $_GET['log_type'] : 'system';
$severity = isset($_GET['severity']) ? $_GET['severity'] : 'all';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// 로그 삭제 처리
$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF 토큰 검증 (실제 구현 시 추가)
    
    try {
        if ($_POST['action'] === 'delete_logs') {
            $deleteLogType = $_POST['delete_log_type'] ?? 'system';
            $deleteOlderThan = $_POST['delete_older_than'] ?? '30';
            
            // 로그 삭제 (예시 코드)
            // 실제로는 여기에 데이터베이스에서 로그 삭제 쿼리 실행
            
            $successMessage = "$deleteLogType 로그 중 {$deleteOlderThan}일 이상 지난 항목이 성공적으로 삭제되었습니다.";
        } elseif ($_POST['action'] === 'export_logs') {
            // 로그 내보내기 처리 (예시 코드)
            // 실제로는 여기에 로그를 CSV 또는 엑셀 형식으로 내보내는 코드 추가
            
            $successMessage = "로그가 성공적으로 내보내기 되었습니다.";
        }
    } catch (Exception $e) {
        $errorMessage = "작업 처리 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 로그 데이터 가져오기 (예시 데이터)
// 실제로는 여기에 데이터베이스에서 로그를 가져오는 쿼리 실행
$logData = [
    [
        'id' => 1,
        'timestamp' => '2025-05-18 09:42:15',
        'type' => 'system',
        'severity' => 'info',
        'message' => '시스템이 성공적으로 시작되었습니다.',
        'user' => 'System',
        'ip_address' => '127.0.0.1'
    ],
    [
        'id' => 2,
        'timestamp' => '2025-05-18 09:45:30',
        'type' => 'user',
        'severity' => 'info',
        'message' => '사용자 로그인',
        'user' => 'admin',
        'ip_address' => '192.168.1.100'
    ],
    [
        'id' => 3,
        'timestamp' => '2025-05-18 10:15:42',
        'type' => 'security',
        'severity' => 'warning',
        'message' => '여러 번의 로그인 실패 감지',
        'user' => 'unknown',
        'ip_address' => '203.0.113.42'
    ],
    [
        'id' => 4,
        'timestamp' => '2025-05-18 11:05:18',
        'type' => 'system',
        'severity' => 'error',
        'message' => '데이터베이스 연결 오류 발생',
        'user' => 'System',
        'ip_address' => '127.0.0.1'
    ],
    [
        'id' => 5,
        'timestamp' => '2025-05-18 11:10:55',
        'type' => 'system',
        'severity' => 'info',
        'message' => '데이터베이스 연결 복구됨',
        'user' => 'System',
        'ip_address' => '127.0.0.1'
    ],
    [
        'id' => 6,
        'timestamp' => '2025-05-18 13:25:10',
        'type' => 'user',
        'severity' => 'info',
        'message' => '사용자 설정 변경',
        'user' => 'manager1',
        'ip_address' => '192.168.1.105'
    ],
    [
        'id' => 7,
        'timestamp' => '2025-05-18 14:30:22',
        'type' => 'application',
        'severity' => 'error',
        'message' => '파일 업로드 실패: 파일 크기 초과',
        'user' => 'operator2',
        'ip_address' => '192.168.1.110'
    ],
    [
        'id' => 8,
        'timestamp' => '2025-05-18 15:45:30',
        'type' => 'security',
        'severity' => 'critical',
        'message' => '무단 접근 시도 감지',
        'user' => 'unknown',
        'ip_address' => '198.51.100.75'
    ],
    [
        'id' => 9,
        'timestamp' => '2025-05-18 16:20:18',
        'type' => 'user',
        'severity' => 'info',
        'message' => '새 사용자 등록됨',
        'user' => 'admin',
        'ip_address' => '192.168.1.100'
    ],
    [
        'id' => 10,
        'timestamp' => '2025-05-18 17:05:44',
        'type' => 'application',
        'severity' => 'warning',
        'message' => '디스크 공간 부족 경고',
        'user' => 'System',
        'ip_address' => '127.0.0.1'
    ]
];

// 전체 로그 수 (페이징용)
$totalLogs = count($logData);
$totalPages = ceil($totalLogs / $perPage);

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
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/">홈</a></li>
                    <li class="breadcrumb-item active">시스템 관리</li>
                    <li class="breadcrumb-item active">로그 관리</li>
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

        <!-- 로그 개요 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>587</h3>
                        <p>시스템 로그</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>1,248</h3>
                        <p>사용자 활동 로그</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>32</h3>
                        <p>경고 로그</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>15</h3>
                        <p>오류 로그</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-bug"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 검색 및 필터 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">로그 검색 및 필터</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="searchForm" method="GET" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="log_type">로그 유형</label>
                                <select class="form-control" id="log_type" name="log_type">
                                    <option value="all" <?php if($logType == 'all') echo 'selected'; ?>>모든 로그</option>
                                    <option value="system" <?php if($logType == 'system') echo 'selected'; ?>>시스템 로그</option>
                                    <option value="user" <?php if($logType == 'user') echo 'selected'; ?>>사용자 활동 로그</option>
                                    <option value="security" <?php if($logType == 'security') echo 'selected'; ?>>보안 로그</option>
                                    <option value="application" <?php if($logType == 'application') echo 'selected'; ?>>애플리케이션 로그</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="severity">심각도</label>
                                <select class="form-control" id="severity" name="severity">
                                    <option value="all" <?php if($severity == 'all') echo 'selected'; ?>>모든 심각도</option>
                                    <option value="info" <?php if($severity == 'info') echo 'selected'; ?>>정보</option>
                                    <option value="warning" <?php if($severity == 'warning') echo 'selected'; ?>>경고</option>
                                    <option value="error" <?php if($severity == 'error') echo 'selected'; ?>>오류</option>
                                    <option value="critical" <?php if($severity == 'critical') echo 'selected'; ?>>심각</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="start_date">시작 날짜</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="end_date">종료 날짜</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="search">검색어</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="로그 메시지, 사용자 또는 IP 검색" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> 검색
                            </button>
                            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#exportLogsModal">
                                <i class="fas fa-file-export"></i> 로그 내보내기
                            </button>
                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteLogsModal">
                                <i class="fas fa-trash"></i> 로그 삭제
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 로그 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">로그 목록</h3>
                <div class="card-tools">
                    <span class="badge badge-info"><?php echo number_format($totalLogs); ?> 개의 로그</span>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th style="width: 60px">ID</th>
                            <th style="width: 150px">시간</th>
                            <th style="width: 100px">유형</th>
                            <th style="width: 100px">심각도</th>
                            <th>메시지</th>
                            <th style="width: 120px">사용자</th>
                            <th style="width: 120px">IP 주소</th>
                            <th style="width: 80px">액션</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logData as $log): ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td><?php echo $log['timestamp']; ?></td>
                            <td>
                                <?php
                                $typeClass = '';
                                switch ($log['type']) {
                                    case 'system':
                                        $typeClass = 'badge-info';
                                        break;
                                    case 'user':
                                        $typeClass = 'badge-primary';
                                        break;
                                    case 'security':
                                        $typeClass = 'badge-warning';
                                        break;
                                    case 'application':
                                        $typeClass = 'badge-secondary';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $typeClass; ?>"><?php echo $log['type']; ?></span>
                            </td>
                            <td>
                                <?php
                                $severityClass = '';
                                switch ($log['severity']) {
                                    case 'info':
                                        $severityClass = 'badge-info';
                                        break;
                                    case 'warning':
                                        $severityClass = 'badge-warning';
                                        break;
                                    case 'error':
                                        $severityClass = 'badge-danger';
                                        break;
                                    case 'critical':
                                        $severityClass = 'badge-dark';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $severityClass; ?>"><?php echo $log['severity']; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($log['message']); ?></td>
                            <td><?php echo htmlspecialchars($log['user']); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            <td>
                                <button type="button" class="btn btn-info btn-xs" data-toggle="modal" data-target="#viewLogModal<?php echo $log['id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <ul class="pagination pagination-sm m-0 float-right">
                    <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=1&log_type=<?php echo $logType; ?>&severity=<?php echo $severity; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&search=<?php echo urlencode($search); ?>">«</a></li>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&log_type=<?php echo $logType; ?>&severity=<?php echo $severity; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&search=<?php echo urlencode($search); ?>">‹</a></li>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&log_type=<?php echo $logType; ?>&severity=<?php echo $severity; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&log_type=<?php echo $logType; ?>&severity=<?php echo $severity; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&search=<?php echo urlencode($search); ?>">›</a></li>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $totalPages; ?>&log_type=<?php echo $logType; ?>&severity=<?php echo $severity; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&search=<?php echo urlencode($search); ?>">»</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <!-- /.card -->
    </div>
</section>
<!-- /.content -->

<!-- Log View Modals -->
<?php foreach ($logData as $log): ?>
<div class="modal fade" id="viewLogModal<?php echo $log['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewLogModalLabel<?php echo $log['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewLogModalLabel<?php echo $log['id']; ?>">로그 상세 정보</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>로그 ID</label>
                            <p><?php echo $log['id']; ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>타임스탬프</label>
                            <p><?php echo $log['timestamp']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>유형</label>
                            <p><span class="badge <?php echo $typeClass; ?>"><?php echo $log['type']; ?></span></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>심각도</label>
                            <p><span class="badge <?php echo $severityClass; ?>"><?php echo $log['severity']; ?></span></p>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>메시지</label>
                    <p><?php echo htmlspecialchars($log['message']); ?></p>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>사용자</label>
                            <p><?php echo htmlspecialchars($log['user']); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>IP 주소</label>
                            <p><?php echo htmlspecialchars($log['ip_address']); ?></p>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>추가 정보</label>
                    <pre class="bg-light p-3" style="max-height: 200px; overflow-y: auto;">
{
    "request": {
        "method": "<?php echo ($log['type'] === 'user') ? 'POST' : 'GET'; ?>",
        "path": "<?php echo ($log['type'] === 'user') ? '/dashboard/system/users.php' : '/dashboard/index.php'; ?>",
        "query_string": "<?php echo ($log['type'] === 'user') ? 'action=update&id=15' : ''; ?>",
        "headers": {
            "user-agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
            "referer": "<?php echo SERVER_URL; ?>/dashboard/"
        }
    },
    "session_id": "<?php echo md5(rand(1000, 9999)); ?>",
    "server": {
        "hostname": "web-server-01",
        "php_version": "8.2.0"
    }
}
</pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Export Logs Modal -->
<div class="modal fade" id="exportLogsModal" tabindex="-1" role="dialog" aria-labelledby="exportLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="" method="post">
                <input type="hidden" name="action" value="export_logs">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportLogsModalLabel">로그 내보내기</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="export_log_type">로그 유형</label>
                        <select class="form-control" id="export_log_type" name="export_log_type">
                            <option value="all">모든 로그</option>
                            <option value="system">시스템 로그</option>
                            <option value="user">사용자 활동 로그</option>
                            <option value="security">보안 로그</option>
                            <option value="application">애플리케이션 로그</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="export_severity">심각도</label>
                        <select class="form-control" id="export_severity" name="export_severity">
                            <option value="all">모든 심각도</option>
                            <option value="info">정보</option>
                            <option value="warning">경고</option>
                            <option value="error">오류</option>
                            <option value="critical">심각</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="export_start_date">시작 날짜</label>
                                <input type="date" class="form-control" id="export_start_date" name="export_start_date" value="<?php echo $startDate; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="export_end_date">종료 날짜</label>
                                <input type="date" class="form-control" id="export_end_date" name="export_end_date" value="<?php echo $endDate; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="export_format">내보내기 형식</label>
                        <select class="form-control" id="export_format" name="export_format">
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                            <option value="json">JSON</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-success">내보내기</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Logs Modal -->
<div class="modal fade" id="deleteLogsModal" tabindex="-1" role="dialog" aria-labelledby="deleteLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="" method="post">
                <input type="hidden" name="action" value="delete_logs">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteLogsModalLabel">로그 삭제</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 주의: 삭제된 로그는 복구할 수 없습니다.
                    </div>
                    <div class="form-group">
                        <label for="delete_log_type">로그 유형</label>
                        <select class="form-control" id="delete_log_type" name="delete_log_type">
                            <option value="all">모든 로그</option>
                            <option value="system">시스템 로그</option>
                            <option value="user">사용자 활동 로그</option>
                            <option value="security">보안 로그</option>
                            <option value="application">애플리케이션 로그</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="delete_older_than">경과 기간</label>
                        <select class="form-control" id="delete_older_than" name="delete_older_than">
                            <option value="7">7일 이상 지난 로그</option>
                            <option value="30" selected>30일 이상 지난 로그</option>
                            <option value="90">90일 이상 지난 로그</option>
                            <option value="180">180일 이상 지난 로그</option>
                            <option value="365">1년 이상 지난 로그</option>
                            <option value="all">모든 로그</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="confirm_delete" name="confirm_delete" required>
                            <label class="custom-control-label" for="confirm_delete">삭제를 확인합니다. 이 작업은 되돌릴 수 없습니다.</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-danger" id="deleteLogsBtn" disabled>로그 삭제</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 삭제 확인 체크박스 이벤트
    $('#confirm_delete').change(function() {
        if ($(this).is(':checked')) {
            $('#deleteLogsBtn').prop('disabled', false);
        } else {
            $('#deleteLogsBtn').prop('disabled', true);
        }
    });
    
    // 검색 폼 제출 전 유효성 검사
    $('#searchForm').submit(function() {
        var startDate = new Date($('#start_date').val());
        var endDate = new Date($('#end_date').val());
        
        if (startDate > endDate) {
            alert('시작 날짜는 종료 날짜보다 이전이어야 합니다.');
            return false;
        }
        
        return true;
    });
    
    // 내보내기 폼 유효성 검사
    $('#exportLogsModal form').submit(function() {
        var startDate = new Date($('#export_start_date').val());
        var endDate = new Date($('#export_end_date').val());
        
        if (startDate > endDate) {
            alert('시작 날짜는 종료 날짜보다 이전이어야 합니다.');
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