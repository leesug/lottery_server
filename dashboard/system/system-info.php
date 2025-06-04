<?php
/**
 * 시스템 정보 페이지
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
$pageTitle = "시스템 정보";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 서버 정보 수집
$serverInfo = [
    'os' => PHP_OS,
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
    'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_input_time' => ini_get('max_input_time'),
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => ini_get('error_reporting'),
    'session_save_path' => ini_get('session.save_path'),
    'date_timezone' => date_default_timezone_get(),
];

// MySQL 정보 수집
try {
    $dbInfo = [
        'version' => 'MySQL 5.7.36', // 예시 데이터. 실제로는 쿼리를 통해 가져와야 함
        'connection' => 'Connected',
        'character_set' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'database_name' => 'khushilottery_db', // 예시 데이터
        'database_size' => '42.5 MB', // 예시 데이터
        'tables_count' => 28, // 예시 데이터
    ];
} catch (Exception $e) {
    $dbInfo = [
        'version' => 'Unknown',
        'connection' => 'Failed: ' . $e->getMessage(),
        'character_set' => 'Unknown',
        'collation' => 'Unknown',
        'database_name' => 'Unknown',
        'database_size' => 'Unknown',
        'tables_count' => 'Unknown',
    ];
}

// 디스크 사용량 정보
$diskTotalSpace = disk_total_space('/');
$diskFreeSpace = disk_free_space('/');
$diskUsedSpace = $diskTotalSpace - $diskFreeSpace;
$diskUsagePercent = round(($diskUsedSpace / $diskTotalSpace) * 100, 2);

// 메모리 사용량 정보 (가상 데이터)
$memoryTotal = 8 * 1024 * 1024 * 1024; // 8GB (예시)
$memoryUsed = 3.2 * 1024 * 1024 * 1024; // 3.2GB (예시)
$memoryFree = $memoryTotal - $memoryUsed;
$memoryUsagePercent = round(($memoryUsed / $memoryTotal) * 100, 2);

// CPU 사용량 정보 (가상 데이터)
$cpuUsagePercent = 45; // 예시

// PHP 확장 모듈 정보
$loadedExtensions = get_loaded_extensions();
sort($loadedExtensions);

// 라이센스 정보 (예시 데이터)
$licenseInfo = [
    'license_key' => 'XA-LIC-KHUSHI-LOTTERY-2025-05',
    'license_type' => 'Enterprise',
    'issued_to' => 'Khushi Lottery Corporation',
    'issued_date' => '2025-01-01',
    'expiry_date' => '2026-01-01',
    'status' => 'Active',
    'max_users' => 100,
    'current_users' => 82,
    'features' => [
        'Advanced Reporting' => true,
        'API Access' => true,
        'Multi-location Support' => true,
        'Custom Branding' => true,
        'Premium Support' => true,
        'Data Export' => true,
        'Mobile App' => true,
        'Advanced Security' => true,
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
                    <li class="breadcrumb-item active">시스템 정보</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 시스템 요약 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo phpversion(); ?></h3>
                        <p>PHP 버전</p>
                    </div>
                    <div class="icon">
                        <i class="fab fa-php"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $dbInfo['version']; ?></h3>
                        <p>MySQL 버전</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-database"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $diskUsagePercent; ?>%</h3>
                        <p>디스크 사용량</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hdd"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo $cpuUsagePercent; ?>%</h3>
                        <p>CPU 사용량</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-microchip"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 시스템 자원 그래프 -->
        <div class="row">
            <div class="col-md-6">
                <!-- 디스크 사용량 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">디스크 사용량</h3>
                    </div>
                    <div class="card-body">
                        <div class="progress-group">
                            <span class="progress-text">디스크 공간</span>
                            <span class="float-right"><b><?php echo formatBytes($diskUsedSpace); ?></b> / <?php echo formatBytes($diskTotalSpace); ?></span>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-primary" style="width: <?php echo $diskUsagePercent; ?>%"></div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <p><i class="fas fa-info-circle text-info"></i> 사용 중: <?php echo formatBytes($diskUsedSpace); ?> (<?php echo $diskUsagePercent; ?>%)</p>
                            <p><i class="fas fa-check-circle text-success"></i> 여유 공간: <?php echo formatBytes($diskFreeSpace); ?> (<?php echo 100 - $diskUsagePercent; ?>%)</p>
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            
            <div class="col-md-6">
                <!-- 메모리 사용량 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">메모리 사용량</h3>
                    </div>
                    <div class="card-body">
                        <div class="progress-group">
                            <span class="progress-text">물리 메모리</span>
                            <span class="float-right"><b><?php echo formatBytes($memoryUsed); ?></b> / <?php echo formatBytes($memoryTotal); ?></span>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-success" style="width: <?php echo $memoryUsagePercent; ?>%"></div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <p><i class="fas fa-info-circle text-info"></i> 사용 중: <?php echo formatBytes($memoryUsed); ?> (<?php echo $memoryUsagePercent; ?>%)</p>
                            <p><i class="fas fa-check-circle text-success"></i> 여유 메모리: <?php echo formatBytes($memoryFree); ?> (<?php echo 100 - $memoryUsagePercent; ?>%)</p>
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
        </div>
        <!-- /.row -->
        
        <div class="row">
            <div class="col-md-7">
                <!-- 서버 정보 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">서버 정보</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-4">운영체제</dt>
                            <dd class="col-sm-8"><?php echo $serverInfo['os']; ?></dd>
                            
                            <dt class="col-sm-4">PHP 버전</dt>
                            <dd class="col-sm-8"><?php echo $serverInfo['php_version']; ?></dd>
                            
                            <dt class="col-sm-4">웹 서버</dt>
                            <dd class="col-sm-8"><?php echo $serverInfo['server_software']; ?></dd>
                            
                            <dt class="col-sm-4">서버 호스트명</dt>
                            <dd class="col-sm-8"><?php echo $serverInfo['server_name']; ?></dd>
                            
                            <dt class="col-sm-4">서버 IP</dt>
                            <dd class="col-sm-8"><?php echo $serverInfo['server_addr']; ?></dd>
                            
                            <dt class="col-sm-4">문서 루트</dt>
                            <dd class="col-sm-8"><?php echo $serverInfo['document_root']; ?></dd>
                            
                            <dt class="col-sm-4">메모리 제한</dt>
                            <dd class="col-sm-8"><?php echo $serverInfo['memory_limit']; ?></dd>
                            
                            <dt class="col-sm-4">최대 실행 시간</dt>
                            <dd class="col-sm-8"><?php echo $serverInfo['max_execution_time']; ?> 초</dd>
                            
                            <dt class="col-sm-4">최대 업로드 크기</dt>
                            <dd class="col-sm-8"><?php echo $serverInfo['upload_max_filesize']; ?></dd>
                            
                            <dt class="col-sm-4">최대 POST 크기</dt>
                            <dd class="col-sm-8"><?php echo $serverInfo['post_max_size']; ?></dd>
                            
                            <dt class="col-sm-4">오류 표시</dt>
                            <dd class="col-sm-8"><?php echo $serverInfo['display_errors'] ? '활성화' : '비활성화'; ?></dd>
                            
                            <dt class="col-sm-4">세션 저장 경로</dt>
                            <dd class="col-sm-8"><?php echo $serverInfo['session_save_path']; ?></dd>
                            
                            <dt class="col-sm-4">시간대</dt>
                            <dd class="col-sm-8"><?php echo $serverInfo['date_timezone']; ?></dd>
                        </dl>
                    </div>
                </div>
                <!-- /.card -->
                
                <!-- 데이터베이스 정보 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">데이터베이스 정보</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-4">데이터베이스 유형</dt>
                            <dd class="col-sm-8">MySQL</dd>
                            
                            <dt class="col-sm-4">버전</dt>
                            <dd class="col-sm-8"><?php echo $dbInfo['version']; ?></dd>
                            
                            <dt class="col-sm-4">연결 상태</dt>
                            <dd class="col-sm-8"><?php echo $dbInfo['connection']; ?></dd>
                            
                            <dt class="col-sm-4">문자셋</dt>
                            <dd class="col-sm-8"><?php echo $dbInfo['character_set']; ?></dd>
                            
                            <dt class="col-sm-4">콜레이션</dt>
                            <dd class="col-sm-8"><?php echo $dbInfo['collation']; ?></dd>
                            
                            <dt class="col-sm-4">데이터베이스 이름</dt>
                            <dd class="col-sm-8"><?php echo $dbInfo['database_name']; ?></dd>
                            
                            <dt class="col-sm-4">데이터베이스 크기</dt>
                            <dd class="col-sm-8"><?php echo $dbInfo['database_size']; ?></dd>
                            
                            <dt class="col-sm-4">테이블 수</dt>
                            <dd class="col-sm-8"><?php echo $dbInfo['tables_count']; ?></dd>
                        </dl>
                    </div>
                </div>
                <!-- /.card -->
                
                <!-- PHP 확장 모듈 -->
                <div class="card collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title">PHP 확장 모듈 (<?php echo count($loadedExtensions); ?>)</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" style="display: none;">
                        <div class="row">
                            <?php 
                            $chunkedExtensions = array_chunk($loadedExtensions, ceil(count($loadedExtensions) / 3));
                            foreach ($chunkedExtensions as $chunk): 
                            ?>
                            <div class="col-md-4">
                                <ul class="list-unstyled">
                                    <?php foreach ($chunk as $extension): ?>
                                    <li><i class="fas fa-check-circle text-success"></i> <?php echo $extension; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col-md-7 -->
            
            <div class="col-md-5">
                <!-- 라이센스 정보 -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">라이센스 정보</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-5">라이센스 키</dt>
                            <dd class="col-sm-7"><?php echo $licenseInfo['license_key']; ?></dd>
                            
                            <dt class="col-sm-5">라이센스 유형</dt>
                            <dd class="col-sm-7"><?php echo $licenseInfo['license_type']; ?></dd>
                            
                            <dt class="col-sm-5">발급 대상</dt>
                            <dd class="col-sm-7"><?php echo $licenseInfo['issued_to']; ?></dd>
                            
                            <dt class="col-sm-5">발급일</dt>
                            <dd class="col-sm-7"><?php echo $licenseInfo['issued_date']; ?></dd>
                            
                            <dt class="col-sm-5">만료일</dt>
                            <dd class="col-sm-7"><?php echo $licenseInfo['expiry_date']; ?></dd>
                            
                            <dt class="col-sm-5">상태</dt>
                            <dd class="col-sm-7"><span class="badge badge-success"><?php echo $licenseInfo['status']; ?></span></dd>
                            
                            <dt class="col-sm-5">최대 사용자</dt>
                            <dd class="col-sm-7"><?php echo $licenseInfo['max_users']; ?></dd>
                            
                            <dt class="col-sm-5">현재 사용자</dt>
                            <dd class="col-sm-7"><?php echo $licenseInfo['current_users']; ?></dd>
                        </dl>
                        
                        <h5 class="mt-4">활성화된 기능</h5>
                        <ul class="list-unstyled">
                            <?php foreach ($licenseInfo['features'] as $feature => $enabled): ?>
                            <li>
                                <?php if ($enabled): ?>
                                <i class="fas fa-check-circle text-success"></i>
                                <?php else: ?>
                                <i class="fas fa-times-circle text-danger"></i>
                                <?php endif; ?>
                                <?php echo $feature; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <!-- /.card -->
                
                <!-- 시스템 진단 -->
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">시스템 진단</h3>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-info btn-block mb-3" id="runDiagnosticBtn">
                            <i class="fas fa-stethoscope"></i> 시스템 진단 실행
                        </button>
                        
                        <div id="diagnosticResults" style="display: none;">
                            <h5>진단 결과</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>항목</th>
                                            <th>상태</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>웹 서버 상태</td>
                                            <td><span class="badge badge-success">정상</span></td>
                                        </tr>
                                        <tr>
                                            <td>데이터베이스 연결</td>
                                            <td><span class="badge badge-success">정상</span></td>
                                        </tr>
                                        <tr>
                                            <td>디스크 공간</td>
                                            <td><span class="badge badge-success">정상</span></td>
                                        </tr>
                                        <tr>
                                            <td>PHP 버전</td>
                                            <td><span class="badge badge-success">정상</span></td>
                                        </tr>
                                        <tr>
                                            <td>세션 저장소</td>
                                            <td><span class="badge badge-success">정상</span></td>
                                        </tr>
                                        <tr>
                                            <td>쓰기 권한</td>
                                            <td><span class="badge badge-warning">주의</span> - 로그 폴더 권한 확인 필요</td>
                                        </tr>
                                        <tr>
                                            <td>최대 업로드 크기</td>
                                            <td><span class="badge badge-success">정상</span></td>
                                        </tr>
                                        <tr>
                                            <td>필수 확장 모듈</td>
                                            <td><span class="badge badge-success">정상</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.card -->
                
                <!-- 시스템 정보 내보내기 -->
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">시스템 정보 내보내기</h3>
                    </div>
                    <div class="card-body">
                        <p>시스템 정보를 기술 지원팀이나 개발자와 공유하기 위해 내보낼 수 있습니다.</p>
                        <div class="btn-group w-100">
                            <button type="button" class="btn btn-warning">
                                <i class="fas fa-file-pdf"></i> PDF로 내보내기
                            </button>
                            <button type="button" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Excel로 내보내기
                            </button>
                            <button type="button" class="btn btn-info">
                                <i class="fas fa-file-code"></i> JSON으로 내보내기
                            </button>
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col-md-5 -->
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

$(document).ready(function() {
    // 시스템 진단 버튼 클릭 이벤트
    $('#runDiagnosticBtn').click(function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 진단 중...');
        
        // 진단 결과를 가져오는 AJAX 요청을 시뮬레이션
        setTimeout(function() {
            $('#diagnosticResults').slideDown();
            btn.prop('disabled', false).html('<i class="fas fa-stethoscope"></i> 시스템 진단 다시 실행');
            
            // 알림 표시
            toastr.success('시스템 진단이 완료되었습니다.');
        }, 2000);
    });
});
</script>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';

// 출력 버퍼 플러시
ob_end_flush();

// 파일 크기 포맷팅 함수
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>