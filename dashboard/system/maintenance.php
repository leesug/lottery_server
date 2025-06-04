<?php
/**
 * 시스템 유지보수 페이지
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
$pageTitle = "시스템 유지보수";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 작업 처리
$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF 토큰 검증 (실제 구현 시 추가)
    
    try {
        switch ($_POST['action']) {
            case 'clear_cache':
                // 캐시 정리 로직
                $successMessage = "시스템 캐시가 성공적으로 정리되었습니다.";
                break;
                
            case 'clear_temp_files':
                // 임시 파일 정리 로직
                $successMessage = "임시 파일이 성공적으로 정리되었습니다.";
                break;
                
            case 'optimize_database':
                // 데이터베이스 최적화 로직
                $successMessage = "데이터베이스가 성공적으로 최적화되었습니다.";
                break;
                
            case 'rebuild_indexes':
                // 인덱스 재구축 로직
                $successMessage = "데이터베이스 인덱스가 성공적으로 재구축되었습니다.";
                break;
                
            case 'clear_logs':
                // 로그 정리 로직
                $daysToKeep = isset($_POST['days_to_keep']) ? (int)$_POST['days_to_keep'] : 30;
                $successMessage = "{$daysToKeep}일 이전의 시스템 로그가 성공적으로 정리되었습니다.";
                break;
                
            default:
                $errorMessage = "알 수 없는 작업입니다.";
        }
    } catch (Exception $e) {
        $errorMessage = "작업 처리 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 시스템 상태 정보 (예시 데이터)
$systemStatus = [
    'maintenance_mode' => false,
    'last_maintenance' => '2025-05-15 08:30:22',
    'cache_size' => '345 MB',
    'temp_files_size' => '128 MB',
    'database_size' => '2.4 GB',
    'log_files_size' => '567 MB',
    'oldest_log' => '2025-01-01',
    'scheduled_tasks' => [
        [
            'name' => '일일 백업',
            'status' => '완료',
            'last_run' => '2025-05-17 03:00:15',
            'next_run' => '2025-05-18 03:00:00'
        ],
        [
            'name' => '주간 최적화',
            'status' => '대기 중',
            'last_run' => '2025-05-11 04:00:05',
            'next_run' => '2025-05-18 04:00:00'
        ],
        [
            'name' => '월간 정리',
            'status' => '대기 중',
            'last_run' => '2025-04-30 05:00:12',
            'next_run' => '2025-05-31 05:00:00'
        ]
    ]
];

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
                    <li class="breadcrumb-item active">시스템 유지보수</li>
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

        <!-- 시스템 상태 요약 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo $systemStatus['cache_size']; ?></h3>
                        <p>캐시 크기</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-memory"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $systemStatus['temp_files_size']; ?></h3>
                        <p>임시 파일 크기</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $systemStatus['database_size']; ?></h3>
                        <p>데이터베이스 크기</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-database"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo $systemStatus['log_files_size']; ?></h3>
                        <p>로그 파일 크기</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-medical-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 유지보수 작업 -->
        <div class="row">
            <div class="col-md-6">
                <!-- 캐시 및 임시 파일 관리 -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">캐시 및 임시 파일 관리</h3>
                    </div>
                    <div class="card-body">
                        <p>마지막 정리 날짜: <?php echo $systemStatus['last_maintenance']; ?></p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="clear_cache">
                                    <button type="submit" class="btn btn-block btn-primary">
                                        <i class="fas fa-broom mr-2"></i> 캐시 정리
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="clear_temp_files">
                                    <button type="submit" class="btn btn-block btn-success">
                                        <i class="fas fa-trash-alt mr-2"></i> 임시 파일 정리
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.card -->
                
                <!-- 데이터베이스 관리 -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">데이터베이스 관리</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="optimize_database">
                                    <button type="submit" class="btn btn-block btn-warning">
                                        <i class="fas fa-database mr-2"></i> 데이터베이스 최적화
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="rebuild_indexes">
                                    <button type="submit" class="btn btn-block btn-info">
                                        <i class="fas fa-sort-alpha-down mr-2"></i> 인덱스 재구축
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            
            <div class="col-md-6">
                <!-- 로그 관리 -->
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">로그 관리</h3>
                    </div>
                    <div class="card-body">
                        <p>가장 오래된 로그: <?php echo $systemStatus['oldest_log']; ?></p>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="clear_logs">
                            <div class="form-group">
                                <label for="days_to_keep">보관할 로그 일수:</label>
                                <select class="form-control" id="days_to_keep" name="days_to_keep">
                                    <option value="7">7일</option>
                                    <option value="14">14일</option>
                                    <option value="30" selected>30일</option>
                                    <option value="60">60일</option>
                                    <option value="90">90일</option>
                                    <option value="180">180일</option>
                                    <option value="365">1년</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-block btn-danger">
                                <i class="fas fa-eraser mr-2"></i> 오래된 로그 정리
                            </button>
                        </form>
                    </div>
                </div>
                <!-- /.card -->
                
                <!-- 유지보수 모드 -->
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">유지보수 모드</h3>
                    </div>
                    <div class="card-body">
                        <p>현재 상태: <strong><?php echo $systemStatus['maintenance_mode'] ? '활성화' : '비활성화'; ?></strong></p>
                        
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="maintenance_mode" <?php echo $systemStatus['maintenance_mode'] ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="maintenance_mode">유지보수 모드 <?php echo $systemStatus['maintenance_mode'] ? '끄기' : '켜기'; ?></label>
                        </div>
                        <small class="form-text text-muted">유지보수 모드를 활성화하면 관리자를 제외한 모든 사용자는 서비스에 접근할 수 없습니다.</small>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-block btn-secondary" id="toggle_maintenance">
                                <i class="fas fa-wrench mr-2"></i> 유지보수 모드 <?php echo $systemStatus['maintenance_mode'] ? '비활성화' : '활성화'; ?>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 예약된 작업 -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">예약된 유지보수 작업</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>작업명</th>
                                    <th>상태</th>
                                    <th>마지막 실행</th>
                                    <th>다음 실행</th>
                                    <th>작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($systemStatus['scheduled_tasks'] as $task): ?>
                                <tr>
                                    <td><?php echo $task['name']; ?></td>
                                    <td>
                                        <?php if ($task['status'] === '완료'): ?>
                                            <span class="badge bg-success">완료</span>
                                        <?php elseif ($task['status'] === '진행 중'): ?>
                                            <span class="badge bg-warning">진행 중</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">대기 중</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $task['last_run']; ?></td>
                                    <td><?php echo $task['next_run']; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary">지금 실행</button>
                                        <button type="button" class="btn btn-sm btn-warning">일정 변경</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- /.card -->
            </div>
        </div>
        <!-- /.row -->
    </div>
</section>
<!-- /.content -->

<script>
    // 유지보수 모드 토글 스크립트
    document.addEventListener('DOMContentLoaded', function () {
        const maintenanceSwitch = document.getElementById('maintenance_mode');
        const maintenanceButton = document.getElementById('toggle_maintenance');
        
        if (maintenanceSwitch && maintenanceButton) {
            maintenanceSwitch.addEventListener('change', function () {
                // 실제 환경에서는 AJAX 요청으로 서버에 상태 변경을 요청
                console.log('유지보수 모드 상태 변경:', this.checked);
                
                // 버튼 텍스트 업데이트
                maintenanceButton.innerHTML = '<i class="fas fa-wrench mr-2"></i> 유지보수 모드 ' + (this.checked ? '비활성화' : '활성화');
            });
            
            maintenanceButton.addEventListener('click', function () {
                // 체크박스 상태 토글
                maintenanceSwitch.checked = !maintenanceSwitch.checked;
                
                // change 이벤트 발생
                const event = new Event('change');
                maintenanceSwitch.dispatchEvent(event);
            });
        }
    });
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; 

// 출력 버퍼 플러시
ob_end_flush();
?>
