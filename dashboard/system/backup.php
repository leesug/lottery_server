<?php
/**
 * 백업 및 복원 관리 페이지
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
$pageTitle = "백업 및 복원";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 백업 디렉토리
$backupDir = $_SERVER['DOCUMENT_ROOT'] . '/backups/';

// 메시지 변수
$successMessage = "";
$errorMessage = "";

// 백업 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF 토큰 검증 (실제 구현 시 추가)
    
    try {
        if ($_POST['action'] === 'create_backup') {
            $backupType = $_POST['backup_type'] ?? 'full';
            $backupName = $_POST['backup_name'] ?? 'backup_' . date('Y-m-d_H-i-s');
            $includeFiles = isset($_POST['include_files']) && $_POST['include_files'] === 'on';
            
            // 실제 백업 생성 코드 (예시)
            // 실제로는 여기서 mysqldump 명령을 사용하여 데이터베이스 백업 등 수행
            
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            // 백업 파일 생성 (예시)
            $backupFilePath = $backupDir . $backupName . '.sql';
            $fileContent = "-- 백업 생성 시간: " . date('Y-m-d H:i:s') . "\n";
            file_put_contents($backupFilePath, $fileContent);
            
            // 백업 기록 저장 (예시)
            // $stmt = $db->prepare("INSERT INTO backups (name, type, path, size, created_at, created_by) VALUES (?, ?, ?, ?, NOW(), ?)");
            // $stmt->execute([$backupName, $backupType, $backupFilePath, filesize($backupFilePath), $_SESSION['user_id']]);
            
            $successMessage = "백업이 성공적으로 생성되었습니다.";
        } elseif ($_POST['action'] === 'restore_backup') {
            $backupId = $_POST['backup_id'] ?? 0;
            
            // 실제 복원 코드 (예시)
            // 실제로는 여기서 선택된 백업 파일을 이용하여 데이터베이스 복원 수행
            
            $successMessage = "백업이 성공적으로 복원되었습니다.";
        } elseif ($_POST['action'] === 'delete_backup') {
            $backupId = $_POST['backup_id'] ?? 0;
            
            // 실제 백업 삭제 코드 (예시)
            // 실제로는 여기서 선택된 백업 파일을 삭제하고 데이터베이스 기록도 삭제
            
            $successMessage = "백업이 성공적으로 삭제되었습니다.";
        } elseif ($_POST['action'] === 'download_backup') {
            $backupId = $_POST['backup_id'] ?? 0;
            
            // 실제 다운로드 코드 (예시)
            // 실제로는 여기서 선택된 백업 파일을 다운로드하게 함
            
            $successMessage = "백업 다운로드가 시작됩니다.";
        } elseif ($_POST['action'] === 'update_schedule') {
            $scheduleEnabled = isset($_POST['schedule_enabled']) && $_POST['schedule_enabled'] === 'on';
            $scheduleFrequency = $_POST['schedule_frequency'] ?? 'daily';
            $scheduleTime = $_POST['schedule_time'] ?? '00:00';
            $scheduleRetention = $_POST['schedule_retention'] ?? 7;
            
            // 설정 업데이트 (예시)
            // 실제로는 여기서 백업 스케줄 설정을 저장
            
            $successMessage = "백업 스케줄이 성공적으로 업데이트되었습니다.";
        }
    } catch (Exception $e) {
        $errorMessage = "작업 처리 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 백업 목록 가져오기 (예시 데이터)
// 실제로는 여기서 데이터베이스에서 백업 정보를 가져옴
$backups = [
    [
        'id' => 1,
        'name' => 'weekly_backup_2025_05_15',
        'type' => 'full',
        'size' => 12582912, // 12MB
        'created_at' => '2025-05-15 03:00:00',
        'created_by' => 'system',
        'status' => 'completed'
    ],
    [
        'id' => 2,
        'name' => 'daily_backup_2025_05_17',
        'type' => 'db_only',
        'size' => 8388608, // 8MB
        'created_at' => '2025-05-17 03:00:00',
        'created_by' => 'system',
        'status' => 'completed'
    ],
    [
        'id' => 3,
        'name' => 'manual_backup_2025_05_18',
        'type' => 'full',
        'size' => 15728640, // 15MB
        'created_at' => '2025-05-18 10:15:22',
        'created_by' => 'admin',
        'status' => 'completed'
    ],
    [
        'id' => 4,
        'name' => 'daily_backup_2025_05_18',
        'type' => 'db_only',
        'size' => 8912896, // 8.5MB
        'created_at' => '2025-05-18 03:00:00',
        'created_by' => 'system',
        'status' => 'completed'
    ]
];

// 현재 백업 스케줄 설정 (예시)
// 실제로는 여기서 데이터베이스에서 설정 정보를 가져옴
$scheduleSettings = [
    'enabled' => true,
    'frequency' => 'daily',
    'time' => '03:00',
    'retention' => 7, // 일
    'next_backup' => '2025-05-19 03:00:00'
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
                    <li class="breadcrumb-item active">백업 및 복원</li>
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

        <!-- 백업 상태 개요 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo count($backups); ?></h3>
                        <p>총 백업 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-database"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo date('Y-m-d H:i', strtotime($backups[0]['created_at'])); ?></h3>
                        <p>최근 백업</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo date('Y-m-d H:i', strtotime($scheduleSettings['next_backup'])); ?></h3>
                        <p>다음 자동 백업</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <?php
                        $totalSize = array_sum(array_column($backups, 'size'));
                        $formattedSize = formatBytes($totalSize);
                        ?>
                        <h3><?php echo $formattedSize; ?></h3>
                        <p>총 백업 크기</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hdd"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <div class="row">
            <div class="col-md-5">
                <!-- 새 백업 생성 -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">새 백업 생성</h3>
                    </div>
                    <form action="" method="post">
                        <input type="hidden" name="action" value="create_backup">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="backup_name">백업 이름</label>
                                <input type="text" class="form-control" id="backup_name" name="backup_name" placeholder="백업 이름 (옵션)" value="backup_<?php echo date('Y-m-d_H-i-s'); ?>">
                                <small class="form-text text-muted">백업 파일의 이름. 비워두면 자동 생성됩니다.</small>
                            </div>
                            <div class="form-group">
                                <label for="backup_type">백업 유형</label>
                                <select class="form-control" id="backup_type" name="backup_type">
                                    <option value="full">전체 백업 (데이터베이스 + 파일)</option>
                                    <option value="db_only">데이터베이스만</option>
                                    <option value="files_only">파일만</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="include_files" name="include_files" checked>
                                    <label class="custom-control-label" for="include_files">업로드 파일 포함</label>
                                </div>
                                <small class="form-text text-muted">사용자가 업로드한 파일(이미지, 문서 등)을 백업에 포함합니다.</small>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="compress_backup" name="compress_backup" checked>
                                    <label class="custom-control-label" for="compress_backup">백업 압축</label>
                                </div>
                                <small class="form-text text-muted">백업 파일을 압축하여 저장 공간을 절약합니다.</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">백업 생성</button>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
                
                <!-- 백업 스케줄 설정 -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">자동 백업 설정</h3>
                    </div>
                    <form action="" method="post">
                        <input type="hidden" name="action" value="update_schedule">
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="schedule_enabled" name="schedule_enabled" <?php if ($scheduleSettings['enabled']) echo 'checked'; ?>>
                                    <label class="custom-control-label" for="schedule_enabled">자동 백업 활성화</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="schedule_frequency">백업 주기</label>
                                <select class="form-control" id="schedule_frequency" name="schedule_frequency">
                                    <option value="daily" <?php if ($scheduleSettings['frequency'] === 'daily') echo 'selected'; ?>>매일</option>
                                    <option value="weekly" <?php if ($scheduleSettings['frequency'] === 'weekly') echo 'selected'; ?>>매주</option>
                                    <option value="monthly" <?php if ($scheduleSettings['frequency'] === 'monthly') echo 'selected'; ?>>매월</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="schedule_time">백업 시간</label>
                                <input type="time" class="form-control" id="schedule_time" name="schedule_time" value="<?php echo $scheduleSettings['time']; ?>">
                                <small class="form-text text-muted">백업이 실행될 시간 (서버 시간)</small>
                            </div>
                            <div class="form-group">
                                <label for="schedule_retention">보관 기간 (일)</label>
                                <input type="number" class="form-control" id="schedule_retention" name="schedule_retention" min="1" max="365" value="<?php echo $scheduleSettings['retention']; ?>">
                                <small class="form-text text-muted">자동 백업 파일을 보관할 기간. 이 기간이 지나면 자동으로 삭제됩니다.</small>
                            </div>
                            <div class="form-group">
                                <label for="backup_type_auto">백업 유형</label>
                                <select class="form-control" id="backup_type_auto" name="backup_type_auto">
                                    <option value="full">전체 백업 (데이터베이스 + 파일)</option>
                                    <option value="db_only" selected>데이터베이스만</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-info">설정 저장</button>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col-md-5 -->
            
            <div class="col-md-7">
                <!-- 백업 목록 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">백업 목록</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>이름</th>
                                    <th>유형</th>
                                    <th>크기</th>
                                    <th>생성 일시</th>
                                    <th>생성자</th>
                                    <th>액션</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($backup['name']); ?></td>
                                    <td>
                                        <?php
                                        switch ($backup['type']) {
                                            case 'full':
                                                echo '<span class="badge badge-primary">전체</span>';
                                                break;
                                            case 'db_only':
                                                echo '<span class="badge badge-info">DB만</span>';
                                                break;
                                            case 'files_only':
                                                echo '<span class="badge badge-success">파일만</span>';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo formatBytes($backup['size']); ?></td>
                                    <td><?php echo $backup['created_at']; ?></td>
                                    <td><?php echo htmlspecialchars($backup['created_by']); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                                액션
                                            </button>
                                            <div class="dropdown-menu">
                                                <form action="" method="post">
                                                    <input type="hidden" name="action" value="restore_backup">
                                                    <input type="hidden" name="backup_id" value="<?php echo $backup['id']; ?>">
                                                    <button type="button" class="dropdown-item" onclick="confirmRestore(this.form)">
                                                        <i class="fas fa-undo-alt mr-2"></i> 복원
                                                    </button>
                                                </form>
                                                <form action="" method="post">
                                                    <input type="hidden" name="action" value="download_backup">
                                                    <input type="hidden" name="backup_id" value="<?php echo $backup['id']; ?>">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-download mr-2"></i> 다운로드
                                                    </button>
                                                </form>
                                                <div class="dropdown-divider"></div>
                                                <form action="" method="post">
                                                    <input type="hidden" name="action" value="delete_backup">
                                                    <input type="hidden" name="backup_id" value="<?php echo $backup['id']; ?>">
                                                    <button type="button" class="dropdown-item text-danger" onclick="confirmDelete(this.form)">
                                                        <i class="fas fa-trash mr-2"></i> 삭제
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- /.card -->
                
                <!-- 복원 안내 -->
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">백업 복원 안내</h3>
                    </div>
                    <div class="card-body">
                        <div class="callout callout-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> 주의사항:</h5>
                            <p>백업 복원은 현재 데이터를 백업 시점의 데이터로 대체하는 작업입니다. 이 과정은 되돌릴 수 없으며, 현재 데이터가 영구적으로 손실될 수 있습니다.</p>
                        </div>
                        <p>복원 작업을 수행하기 전에 다음 사항을 확인하세요:</p>
                        <ul>
                            <li>현재 시스템 데이터의 백업을 생성하세요.</li>
                            <li>가능하면 비수기 시간에 복원 작업을 수행하세요.</li>
                            <li>복원 중에는 시스템 사용이 중단될 수 있습니다.</li>
                            <li>복원 후에는 시스템 기능을 철저히 테스트하세요.</li>
                        </ul>
                        <p>복원에 문제가 있는 경우 즉시 시스템 관리자에게 문의하세요.</p>
                        <p class="mb-0">복원이 완료되면 모든 사용자를 로그아웃시키고 캐시를 지우는 것이 좋습니다.</p>
                    </div>
                </div>
                <!-- /.card -->
                
                <!-- 수동 백업 업로드 -->
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">백업 파일 업로드</h3>
                    </div>
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="backup_file">백업 파일 선택</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="backup_file" name="backup_file">
                                        <label class="custom-file-label" for="backup_file">파일 선택</label>
                                    </div>
                                </div>
                                <small class="form-text text-muted">업로드할 백업 파일 (.sql 또는 .zip)</small>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="confirm_upload" name="confirm_upload" required>
                                    <label class="custom-control-label" for="confirm_upload">이 파일이 유효한 백업 파일임을 확인합니다.</label>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-secondary">백업 업로드</button>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col-md-7 -->
        </div>
        <!-- /.row -->
    </div>
</section>
<!-- /.content -->

<script>
// 바이트 포맷 함수
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// 백업 복원 확인
function confirmRestore(form) {
    if (confirm('정말로 이 백업을 복원하시겠습니까? 이 작업은 현재 데이터를 백업 시점의 데이터로 대체합니다.')) {
        form.submit();
    }
}

// 백업 삭제 확인
function confirmDelete(form) {
    if (confirm('정말로 이 백업을 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
        form.submit();
    }
}

$(document).ready(function() {
    // 파일 업로드 선택 시 파일명 표시
    bsCustomFileInput.init();
});
</script>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';

// 출력 버퍼 플러시
ob_end_flush();
?>