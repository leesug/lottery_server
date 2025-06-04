<?php
/**
 * System Management Dashboard Page
 * 
 * This page displays statistics and information related to system management,
 * including user statistics, system health, backup status, etc.
 */

// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "시스템 관리 대시보드";
$currentSection = "system";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = get_db_connection();

// 시스템 통계 정보 (Mock 데이터 사용)
$systemStats = [
    'total_users' => 85,
    'active_users' => 72,
    'inactive_users' => 13,
    'locked_users' => 4,
    'admin_users' => 18,
    'logins_today' => 45,
    'logins_week' => 235,
    'avg_logins_daily' => 42
];

// 시스템 자원 사용 현황 (Mock 데이터 사용)
$systemResources = [
    'cpu_usage' => 28,
    'memory_usage' => 42,
    'disk_usage' => 65,
    'network_usage' => 35,
    'database_size' => 12750, // MB
    'total_disk_space' => 500000, // MB
    'free_disk_space' => 175000 // MB
];

// 최근 사용자 활동 (Mock 데이터 사용)
$recentUserActivities = [
    ['username' => 'admin', 'action' => '로그인 성공', 'ip' => '192.168.1.101', 'date' => '2025-05-18 14:32:15', 'status' => 'success'],
    ['username' => 'finance_manager', 'action' => '재무 보고서 생성', 'ip' => '192.168.1.105', 'date' => '2025-05-18 14:25:43', 'status' => 'success'],
    ['username' => 'store_manager', 'action' => '판매점 추가', 'ip' => '192.168.1.110', 'date' => '2025-05-18 14:15:22', 'status' => 'success'],
    ['username' => 'marketing_user', 'action' => '로그인 실패', 'ip' => '192.168.1.115', 'date' => '2025-05-18 14:10:18', 'status' => 'danger'],
    ['username' => 'system_admin', 'action' => '백업 실행', 'ip' => '192.168.1.100', 'date' => '2025-05-18 14:05:32', 'status' => 'info'],
    ['username' => 'support_staff', 'action' => '고객 정보 수정', 'ip' => '192.168.1.120', 'date' => '2025-05-18 13:58:45', 'status' => 'warning'],
    ['username' => 'marketing_manager', 'action' => '캠페인 생성', 'ip' => '192.168.1.125', 'date' => '2025-05-18 13:45:10', 'status' => 'success'],
    ['username' => 'finance_staff', 'action' => '정산 처리', 'ip' => '192.168.1.130', 'date' => '2025-05-18 13:30:22', 'status' => 'success']
];

// 백업 상태 (Mock 데이터 사용)
$backupStatus = [
    'last_backup_date' => '2025-05-18 01:00:00',
    'last_backup_size' => 8540, // MB
    'last_backup_status' => 'success',
    'last_backup_duration' => 35, // 분
    'scheduled_backup' => '2025-05-19 01:00:00',
    'backup_frequency' => '매일',
    'retained_backups' => 14,
    'total_backup_size' => 112450 // MB
];

// 보안 알림 (Mock 데이터 사용)
$securityAlerts = [
    ['type' => '잠재적 무단 접근 시도', 'source_ip' => '203.0.113.15', 'count' => 8, 'date' => '2025-05-18 13:45:22', 'severity' => 'high'],
    ['type' => '실패한 로그인 시도', 'source_ip' => '192.168.1.115', 'count' => 3, 'date' => '2025-05-18 12:32:10', 'severity' => 'medium'],
    ['type' => '비정상적인 행동 패턴', 'source_ip' => '192.168.1.130', 'count' => 1, 'date' => '2025-05-18 11:15:40', 'severity' => 'low'],
    ['type' => '권한 승격 시도', 'source_ip' => '203.0.113.25', 'count' => 2, 'date' => '2025-05-18 10:22:15', 'severity' => 'medium']
];

// 역할별 사용자 통계 (Mock 데이터 사용)
$userRoleStats = [
    ['role' => '시스템 관리자', 'count' => 5],
    ['role' => '운영 관리자', 'count' => 12],
    ['role' => '재무 담당자', 'count' => 8],
    ['role' => '영업 담당자', 'count' => 20],
    ['role' => '마케팅 담당자', 'count' => 15],
    ['role' => '고객 지원', 'count' => 25]
];

// 서버 정보 (Mock 데이터 사용)
$serverInfo = [
    'operating_system' => 'CentOS Linux 8.4',
    'web_server' => 'Apache 2.4.51',
    'php_version' => 'PHP 8.1.12',
    'database_server' => 'MySQL 8.0.27',
    'server_ip' => '192.168.1.100',
    'server_hostname' => 'lottery-prod-01',
    'uptime' => '45일 12시간 28분',
    'last_reboot' => '2025-04-03 01:35:22'
];

// 템플릿 헤더 포함 - 여기서 content-wrapper 클래스를 가진 div가 시작됨
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
                    <li class="breadcrumb-item">시스템 관리</li>
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
        <!-- 빠른 액션 버튼 -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="btn-group">
                    <a href="users.php" class="btn btn-primary"><i class="fas fa-users-cog"></i> 사용자 관리</a>
                    <a href="roles.php" class="btn btn-success"><i class="fas fa-user-shield"></i> 권한 관리</a>
                    <a href="settings.php" class="btn btn-info"><i class="fas fa-cogs"></i> 시스템 설정</a>
                    <a href="backup.php" class="btn btn-warning"><i class="fas fa-database"></i> 백업 및 복원</a>
                    <a href="logs.php" class="btn btn-secondary"><i class="fas fa-list"></i> 로그 관리</a>
                </div>
            </div>
        </div>

        <!-- 시스템 통계 요약 카드 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo $systemStats['total_users']; ?></h3>
                        <p>총 사용자 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <a href="users.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $backupStatus['retained_backups']; ?></h3>
                        <p>보관 중인 백업</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <a href="backup.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $systemStats['logins_today']; ?></h3>
                        <p>오늘 로그인 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <a href="logs.php?filter=login" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo count($securityAlerts); ?></h3>
                        <p>보안 경고</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <a href="logs.php?filter=security" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 시스템 자원 사용량 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">시스템 자원 사용량</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <!-- CPU 사용량 -->
                                <div class="progress-group">
                                    <span class="progress-text">CPU 사용량</span>
                                    <span class="float-right"><?php echo $systemResources['cpu_usage']; ?>%</span>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-primary" style="width: <?php echo $systemResources['cpu_usage']; ?>%"></div>
                                    </div>
                                </div>
                                <!-- 메모리 사용량 -->
                                <div class="progress-group">
                                    <span class="progress-text">메모리 사용량</span>
                                    <span class="float-right"><?php echo $systemResources['memory_usage']; ?>%</span>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-success" style="width: <?php echo $systemResources['memory_usage']; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- 디스크 사용량 -->
                                <div class="progress-group">
                                    <span class="progress-text">디스크 사용량</span>
                                    <span class="float-right"><?php echo $systemResources['disk_usage']; ?>%</span>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-warning" style="width: <?php echo $systemResources['disk_usage']; ?>%"></div>
                                    </div>
                                </div>
                                <!-- 네트워크 사용량 -->
                                <div class="progress-group">
                                    <span class="progress-text">네트워크 사용량</span>
                                    <span class="float-right"><?php echo $systemResources['network_usage']; ?>%</span>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-info" style="width: <?php echo $systemResources['network_usage']; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text">데이터베이스 크기</span>
                                        <span class="info-box-number"><?php echo number_format($systemResources['database_size'] / 1024, 2); ?> GB</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text">여유 디스크 공간</span>
                                        <span class="info-box-number"><?php echo number_format($systemResources['free_disk_space'] / 1024, 2); ?> GB</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 백업 상태 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">백업 상태</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-6">마지막 백업</dt>
                                    <dd class="col-sm-6"><?php echo $backupStatus['last_backup_date']; ?></dd>
                                    <dt class="col-sm-6">백업 상태</dt>
                                    <dd class="col-sm-6">
                                        <?php if ($backupStatus['last_backup_status'] === 'success'): ?>
                                            <span class="badge badge-success">성공</span>
                                        <?php elseif ($backupStatus['last_backup_status'] === 'warning'): ?>
                                            <span class="badge badge-warning">경고</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">실패</span>
                                        <?php endif; ?>
                                    </dd>
                                    <dt class="col-sm-6">백업 크기</dt>
                                    <dd class="col-sm-6"><?php echo number_format($backupStatus['last_backup_size'] / 1024, 2); ?> GB</dd>
                                    <dt class="col-sm-6">백업 시간</dt>
                                    <dd class="col-sm-6"><?php echo $backupStatus['last_backup_duration']; ?>분</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-6">예정된 백업</dt>
                                    <dd class="col-sm-6"><?php echo $backupStatus['scheduled_backup']; ?></dd>
                                    <dt class="col-sm-6">백업 주기</dt>
                                    <dd class="col-sm-6"><?php echo $backupStatus['backup_frequency']; ?></dd>
                                    <dt class="col-sm-6">보관 백업 수</dt>
                                    <dd class="col-sm-6"><?php echo $backupStatus['retained_backups']; ?>개</dd>
                                    <dt class="col-sm-6">총 백업 크기</dt>
                                    <dd class="col-sm-6"><?php echo number_format($backupStatus['total_backup_size'] / 1024, 2); ?> GB</dd>
                                </dl>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <a href="backup.php" class="btn btn-primary mr-2">
                                <i class="fas fa-database mr-1"></i> 백업 관리
                            </a>
                            <a href="#" class="btn btn-success">
                                <i class="fas fa-plus-circle mr-1"></i> 백업 생성
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 최근 사용자 활동 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">최근A 사용자 활동</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>사용자</th>
                                        <th>작업</th>
                                        <th>IP 주소</th>
                                        <th>일시</th>
                                        <th>상태</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentUserActivities as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['username']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['ip']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['date']); ?></td>
                                        <td>
                                            <?php if ($activity['status'] === 'success'): ?>
                                                <span class="badge badge-success">성공</span>
                                            <?php elseif ($activity['status'] === 'danger'): ?>
                                                <span class="badge badge-danger">실패</span>
                                            <?php elseif ($activity['status'] === 'warning'): ?>
                                                <span class="badge badge-warning">경고</span>
                                            <?php else: ?>
                                                <span class="badge badge-info">정보</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer clearfix">
                        <a href="logs.php" class="btn btn-sm btn-secondary float-right">모든 로그 보기</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- 역할별 사용자 통계 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">역할별 사용자 통계</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="roleChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>

                <!-- 보안 경고 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">보안 경고</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="products-list product-list-in-card pl-2 pr-2">
                            <?php foreach ($securityAlerts as $alert): ?>
                            <li class="item">
                                <div class="product-img">
                                    <?php if ($alert['severity'] === 'high'): ?>
                                        <i class="fas fa-exclamation-circle text-danger fa-2x"></i>
                                    <?php elseif ($alert['severity'] === 'medium'): ?>
                                        <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                                    <?php else: ?>
                                        <i class="fas fa-info-circle text-info fa-2x"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <a href="#" class="product-title">
                                        <?php echo htmlspecialchars($alert['type']); ?>
                                        <?php if ($alert['severity'] === 'high'): ?>
                                            <span class="badge badge-danger float-right">높음</span>
                                        <?php elseif ($alert['severity'] === 'medium'): ?>
                                            <span class="badge badge-warning float-right">중간</span>
                                        <?php else: ?>
                                            <span class="badge badge-info float-right">낮음</span>
                                        <?php endif; ?>
                                    </a>
                                    <span class="product-description">
                                        <?php echo htmlspecialchars($alert['source_ip']); ?> - <?php echo htmlspecialchars($alert['count']); ?>회 - <?php echo htmlspecialchars($alert['date']); ?>
                                    </span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="card-footer text-center">
                        <a href="ip-access.php" class="btn btn-sm btn-danger">
                            <i class="fas fa-shield-alt mr-1"></i> IP 접근 관리
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 서버 정보 카드 -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">서버 정보</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text">운영체제</span>
                                        <span class="info-box-number"><?php echo htmlspecialchars($serverInfo['operating_system']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text">웹 서버</span>
                                        <span class="info-box-number"><?php echo htmlspecialchars($serverInfo['web_server']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text">PHP 버전</span>
                                        <span class="info-box-number"><?php echo htmlspecialchars($serverInfo['php_version']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text">데이터베이스 서버</span>
                                        <span class="info-box-number"><?php echo htmlspecialchars($serverInfo['database_server']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text">서버 IP</span>
                                        <span class="info-box-number"><?php echo htmlspecialchars($serverInfo['server_ip']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text">호스트명</span>
                                        <span class="info-box-number"><?php echo htmlspecialchars($serverInfo['server_hostname']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text">가동 시간</span>
                                        <span class="info-box-number"><?php echo htmlspecialchars($serverInfo['uptime']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text">마지막 재부팅</span>
                                        <span class="info-box-number"><?php echo htmlspecialchars($serverInfo['last_reboot']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="system-info.php" class="btn btn-primary">
                            <i class="fas fa-server mr-1"></i> 상세 시스템 정보 보기
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<!-- 필요한 JavaScript 포함 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 역할별 사용자 통계 차트
    const roleCtx = document.getElementById('roleChart').getContext('2d');
    const roleData = <?php echo json_encode(array_column($userRoleStats, 'count')); ?>;
    const roleLabels = <?php echo json_encode(array_column($userRoleStats, 'role')); ?>;
    
    new Chart(roleCtx, {
        type: 'pie',
        data: {
            labels: roleLabels,
            datasets: [{
                data: roleData,
                backgroundColor: ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de']
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true
        }
    });
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
