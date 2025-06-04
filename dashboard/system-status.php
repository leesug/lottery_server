<?php
/**
 * 시스템 상태 대시보드 페이지
 */

// 세션 시작 및 인증 체크
session_start();

// 설정 및 공통 함수
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

// 인증 확인
check_auth();

// 높은 권한 레벨 필요 (시스템 관리자)
check_admin_level();

// 데이터베이스 연결
$db = get_db_connection();

// 현재 날짜/시간
$now = date('Y-m-d H:i:s');
$today = date('Y-m-d');

// 시스템 상태 데이터 조회
$serverStatus = [
    'cpu_usage' => rand(10, 60), // 예시 데이터 (실제로는 서버 모니터링 API 등을 통해 가져와야 함)
    'memory_usage' => rand(30, 70),
    'disk_usage' => rand(40, 80),
    'uptime' => '7일 14시간 33분', // 예시 데이터
    'last_reboot' => date('Y-m-d H:i:s', strtotime('-7 days')),
];

// 데이터베이스 상태 데이터 조회
$dbStatus = [
    'connection' => 'Active',
    'version' => 'MySQL 8.0.28',
    'size' => '4.7 GB',
    'tables' => 42,
    'performance' => 'Good',
];

// 단말기 상태 데이터
$terminalStatus = [];
$terminalStatusSummary = [
    'total' => 0,
    'online' => 0,
    'offline' => 0,
    'maintenance' => 0,
];

try {
    // 단말기 상태 요약 조회
    $stmt = $db->prepare("
        SELECT 
            status,
            COUNT(*) AS count
        FROM 
            terminals
        GROUP BY 
            status
    ");
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $terminalStatusSummary['total'] += $row['count'];
        
        if ($row['status'] == 'active') {
            $terminalStatusSummary['online'] = $row['count'];
        } else if ($row['status'] == 'inactive') {
            $terminalStatusSummary['offline'] = $row['count'];
        } else if ($row['status'] == 'maintenance') {
            $terminalStatusSummary['maintenance'] = $row['count'];
        }
    }
    
    // 최근 로그인 활동 조회
    $stmt = $db->prepare("
        SELECT 
            l.id,
            u.username,
            l.ip_address,
            l.user_agent,
            l.status,
            l.created_at
        FROM 
            login_logs l
        JOIN 
            users u ON l.user_id = u.id
        ORDER BY 
            l.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentLogins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 시스템 로그 요약 조회
    $stmt = $db->prepare("
        SELECT 
            log_type,
            COUNT(*) AS count
        FROM 
            system_logs
        WHERE 
            DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY 
            log_type
    ");
    $stmt->execute();
    $logSummary = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $logSummary[$row['log_type']] = $row['count'];
    }
    
    // 최근 시스템 로그 조회
    $stmt = $db->prepare("
        SELECT 
            id,
            log_type,
            message,
            source,
            ip_address,
            created_at
        FROM 
            system_logs
        ORDER BY 
            created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // API 호출 통계 (예시 데이터)
    $apiCallStats = [
        'total_today' => rand(5000, 15000),
        'avg_response_time' => rand(100, 300) / 1000, // 초 단위
        'error_rate' => rand(1, 5) / 100, // 백분율
    ];
    
    // 최근 에러 로그 조회
    $stmt = $db->prepare("
        SELECT 
            id,
            log_type,
            message,
            source,
            created_at
        FROM 
            system_logs
        WHERE 
            log_type IN ('error', 'critical')
        ORDER BY 
            created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $errorLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // 에러 로깅
    error_log("Database error: " . $e->getMessage());
    // 에러 발생 시 기본값 유지
}

// 헤더 포함
$pageTitle = "시스템 상태 대시보드";
include '../templates/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">시스템 상태 대시보드</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">홈</a></li>
                        <li class="breadcrumb-item active">시스템 상태 대시보드</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- 상단 상태 요약 카드 -->
            <div class="row">
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-server"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">서버 상태</span>
                            <span class="info-box-number">정상</span>
                            <div class="progress">
                                <div class="progress-bar bg-info" style="width: <?php echo $serverStatus['cpu_usage']; ?>%"></div>
                            </div>
                            <span class="progress-description">
                                CPU 사용률: <?php echo $serverStatus['cpu_usage']; ?>%
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-database"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">데이터베이스 상태</span>
                            <span class="info-box-number"><?php echo $dbStatus['connection']; ?></span>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: <?php echo $serverStatus['disk_usage']; ?>%"></div>
                            </div>
                            <span class="progress-description">
                                디스크 사용률: <?php echo $serverStatus['disk_usage']; ?>%
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-desktop"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">단말기 온라인 상태</span>
                            <span class="info-box-number"><?php echo $terminalStatusSummary['online']; ?> / <?php echo $terminalStatusSummary['total']; ?></span>
                            <div class="progress">
                                <?php
                                $onlinePercentage = $terminalStatusSummary['total'] > 0 ? 
                                    ($terminalStatusSummary['online'] / $terminalStatusSummary['total'] * 100) : 0;
                                ?>
                                <div class="progress-bar bg-warning" style="width: <?php echo $onlinePercentage; ?>%"></div>
                            </div>
                            <span class="progress-description">
                                온라인 비율: <?php echo number_format($onlinePercentage, 1); ?>%
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">오류 비율</span>
                            <span class="info-box-number"><?php echo number_format($apiCallStats['error_rate'] * 100, 2); ?>%</span>
                            <div class="progress">
                                <div class="progress-bar bg-danger" style="width: <?php echo $apiCallStats['error_rate'] * 100; ?>%"></div>
                            </div>
                            <span class="progress-description">
                                API 오류 비율
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 서버 및 DB 상태 -->
            <div class="row">
                <div class="col-md-6">
                    <!-- 서버 상태 카드 -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">서버 상태</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <canvas id="serverStatusChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                </div>
                                <div class="col-md-4">
                                    <ul class="list-unstyled">
                                        <li>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span><i class="fas fa-clock mr-2"></i> 가동 시간</span>
                                                <span class="badge bg-primary"><?php echo $serverStatus['uptime']; ?></span>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span><i class="fas fa-redo mr-2"></i> 마지막 재부팅</span>
                                                <span class="badge bg-info"><?php echo date('Y-m-d H:i', strtotime($serverStatus['last_reboot'])); ?></span>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span><i class="fas fa-microchip mr-2"></i> CPU 사용률</span>
                                                <span class="badge bg-warning"><?php echo $serverStatus['cpu_usage']; ?>%</span>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span><i class="fas fa-memory mr-2"></i> 메모리 사용률</span>
                                                <span class="badge bg-success"><?php echo $serverStatus['memory_usage']; ?>%</span>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span><i class="fas fa-hdd mr-2"></i> 디스크 사용률</span>
                                                <span class="badge bg-danger"><?php echo $serverStatus['disk_usage']; ?>%</span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="/server/dashboard/system/server.php" class="btn btn-sm btn-primary">서버 관리</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <!-- 데이터베이스 상태 카드 -->
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">데이터베이스 상태</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-striped">
                                        <tr>
                                            <td><i class="fas fa-plug mr-2 text-primary"></i> 연결 상태</td>
                                            <td><span class="badge bg-success"><?php echo $dbStatus['connection']; ?></span></td>
                                        </tr>
                                        <tr>
                                            <td><i class="fas fa-code-branch mr-2 text-warning"></i> 버전</td>
                                            <td><?php echo $dbStatus['version']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><i class="fas fa-database mr-2 text-danger"></i> 데이터베이스 크기</td>
                                            <td><?php echo $dbStatus['size']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><i class="fas fa-table mr-2 text-info"></i> 테이블 수</td>
                                            <td><?php echo $dbStatus['tables']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><i class="fas fa-tachometer-alt mr-2 text-success"></i> 성능</td>
                                            <td><span class="badge bg-info"><?php echo $dbStatus['performance']; ?></span></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <div class="chart-responsive">
                                        <canvas id="dbPieChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="/server/dashboard/system/database.php" class="btn btn-sm btn-success">데이터베이스 관리</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 단말기 상태 및 API 호출 통계 -->
            <div class="row">
                <div class="col-md-8">
                    <!-- 단말기 상태 -->
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">단말기 상태</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <canvas id="terminalStatusChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box bg-success">
                                        <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">온라인 단말기</span>
                                            <span class="info-box-number"><?php echo $terminalStatusSummary['online']; ?></span>
                                        </div>
                                    </div>
                                    <div class="info-box bg-danger">
                                        <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">오프라인 단말기</span>
                                            <span class="info-box-number"><?php echo $terminalStatusSummary['offline']; ?></span>
                                        </div>
                                    </div>
                                    <div class="info-box bg-warning">
                                        <span class="info-box-icon"><i class="fas fa-tools"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">유지보수 중 단말기</span>
                                            <span class="info-box-number"><?php echo $terminalStatusSummary['maintenance']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="/server/dashboard/store/equipment.php" class="btn btn-sm btn-warning">단말기 관리</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- API 호출 통계 -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">API 호출 통계</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <div style="display: inline-block; position: relative; width: 180px; height: 180px;">
                                    <canvas id="apiGaugeChart"></canvas>
                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 24px; font-weight: bold;">
                                        <?php echo $apiCallStats['avg_response_time']; ?>s
                                    </div>
                                </div>
                                <p class="text-center mt-3">평균 응답 시간</p>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <h5>오늘 API 호출 수</h5>
                                <span class="badge bg-primary"><?php echo number_format($apiCallStats['total_today']); ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" style="width: <?php echo min($apiCallStats['total_today'] / 20000 * 100, 100); ?>%"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <h5>API 오류율</h5>
                                <span class="badge bg-danger"><?php echo number_format($apiCallStats['error_rate'] * 100, 2); ?>%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-danger" style="width: <?php echo min($apiCallStats['error_rate'] * 100 * 10, 100); ?>%"></div>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="/server/dashboard/system/api-logs.php" class="btn btn-sm btn-info">API 로그 보기</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 로그 섹션 -->
            <div class="row">
                <div class="col-md-6">
                    <!-- 시스템 로그 요약 -->
                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title">시스템 로그 요약 (최근 7일)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="logSummaryChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                        <div class="card-footer text-center">
                            <a href="/server/dashboard/logs/system-logs.php" class="btn btn-sm btn-danger">모든 로그 보기</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <!-- 최근 오류 로그 -->
                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title">최근 오류 로그</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>시간</th>
                                            <th>타입</th>
                                            <th>소스</th>
                                            <th>메시지</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($errorLogs as $log) {
                                            echo "<tr>";
                                            echo "<td>" . date('Y-m-d H:i', strtotime($log['created_at'])) . "</td>";
                                            
                                            $typeClass = ($log['log_type'] == 'error') ? 'warning' : 'danger';
                                            echo "<td><span class='badge bg-" . $typeClass . "'>" 
                                                . strtoupper(htmlspecialchars($log['log_type'])) . "</span></td>";
                                            
                                            echo "<td>" . htmlspecialchars($log['source']) . "</td>";
                                            
                                            // 메시지 길이 제한
                                            $message = htmlspecialchars($log['message']);
                                            if (strlen($message) > 60) {
                                                $message = substr($message, 0, 57) . '...';
                                            }
                                            echo "<td>" . $message . "</td>";
                                            
                                            echo "</tr>";
                                        }
                                        
                                        if (empty($errorLogs)) {
                                            echo "<tr><td colspan='4' class='text-center'>오류 로그가 없습니다.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="/server/dashboard/logs/system-logs.php?type=error" class="btn btn-sm btn-danger">모든 오류 로그 보기</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 최근 로그인 활동 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">최근 로그인 활동</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>사용자</th>
                                            <th>IP 주소</th>
                                            <th>브라우저</th>
                                            <th>상태</th>
                                            <th>시간</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($recentLogins as $login) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($login['username']) . "</td>";
                                            echo "<td>" . htmlspecialchars($login['ip_address']) . "</td>";
                                            
                                            // 간단한 User-Agent 파싱 (예시)
                                            $userAgent = htmlspecialchars($login['user_agent']);
                                            $browser = "알 수 없음";
                                            
                                            if (strpos($userAgent, 'Chrome') !== false) {
                                                $browser = 'Chrome';
                                            } elseif (strpos($userAgent, 'Firefox') !== false) {
                                                $browser = 'Firefox';
                                            } elseif (strpos($userAgent, 'Safari') !== false) {
                                                $browser = 'Safari';
                                            } elseif (strpos($userAgent, 'Edge') !== false || strpos($userAgent, 'Edg') !== false) {
                                                $browser = 'Edge';
                                            } elseif (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
                                                $browser = 'Internet Explorer';
                                            }
                                            
                                            echo "<td>" . $browser . "</td>";
                                            
                                            $statusClass = ($login['status'] == 'success') ? 'success' : 'danger';
                                            $statusText = ($login['status'] == 'success') ? '성공' : '실패';
                                            echo "<td><span class='badge bg-" . $statusClass . "'>" . $statusText . "</span></td>";
                                            
                                            echo "<td>" . date('Y-m-d H:i:s', strtotime($login['created_at'])) . "</td>";
                                            echo "</tr>";
                                        }
                                        
                                        if (empty($recentLogins)) {
                                            echo "<tr><td colspan='5' class='text-center'>최근 로그인 활동이 없습니다.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="/server/dashboard/logs/user-activity.php" class="btn btn-sm btn-info">모든 사용자 활동 로그 보기</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>

<!-- Chart.js 스크립트 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 서버 상태 차트
    var serverStatusChartCanvas = document.getElementById('serverStatusChart').getContext('2d');
    var serverStatusChartData = {
        labels: ['CPU', '메모리', '디스크'],
        datasets: [
            {
                label: '사용률 (%)',
                backgroundColor: ['rgba(60,141,188,0.8)', 'rgba(40,167,69,0.8)', 'rgba(220,53,69,0.8)'],
                borderColor: ['rgba(60,141,188,1)', 'rgba(40,167,69,1)', 'rgba(220,53,69,1)'],
                pointRadius: false,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: [<?php echo $serverStatus['cpu_usage']; ?>, <?php echo $serverStatus['memory_usage']; ?>, <?php echo $serverStatus['disk_usage']; ?>]
            }
        ]
    };
    
    var serverStatusChartOptions = {
        maintainAspectRatio: false,
        responsive: true,
        legend: {
            display: true
        },
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    max: 100
                }
            }]
        }
    };
    
    new Chart(serverStatusChartCanvas, {
        type: 'bar',
        data: serverStatusChartData,
        options: serverStatusChartOptions
    });
    
    // 데이터베이스 파이 차트 (예시 데이터)
    var dbPieChartCanvas = document.getElementById('dbPieChart').getContext('2d');
    var dbPieChartData = {
        labels: ['사용자 데이터', '거래 데이터', '시스템 로그', '설정 데이터', '기타'],
        datasets: [
            {
                data: [30, 45, 15, 5, 5],
                backgroundColor: ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc']
            }
        ]
    };
    
    var dbPieChartOptions = {
        maintainAspectRatio: false,
        responsive: true
    };
    
    new Chart(dbPieChartCanvas, {
        type: 'pie',
        data: dbPieChartData,
        options: dbPieChartOptions
    });
    
    // 단말기 상태 차트
    var terminalStatusChartCanvas = document.getElementById('terminalStatusChart').getContext('2d');
    var terminalStatusChartData = {
        labels: ['온라인', '오프라인', '유지보수'],
        datasets: [
            {
                data: [
                    <?php echo $terminalStatusSummary['online']; ?>, 
                    <?php echo $terminalStatusSummary['offline']; ?>, 
                    <?php echo $terminalStatusSummary['maintenance']; ?>
                ],
                backgroundColor: ['#00a65a', '#dc3545', '#ffc107']
            }
        ]
    };
    
    var terminalStatusChartOptions = {
        maintainAspectRatio: false,
        responsive: true
    };
    
    new Chart(terminalStatusChartCanvas, {
        type: 'doughnut',
        data: terminalStatusChartData,
        options: terminalStatusChartOptions
    });
    
    // API 게이지 차트
    var apiGaugeChartCanvas = document.getElementById('apiGaugeChart').getContext('2d');
    
    // 응답 시간 게이지 차트 (0-2초 범위, 낮을수록 좋음)
    var responseTime = <?php echo $apiCallStats['avg_response_time']; ?>;
    var color = 'green';
    
    if (responseTime > 0.5) {
        color = 'yellow';
    }
    if (responseTime > 1.0) {
        color = 'red';
    }
    
    var apiGaugeChartData = {
        datasets: [{
            data: [responseTime, 2 - responseTime],
            backgroundColor: [
                responseTime <= 0.5 ? '#00a65a' : (responseTime <= 1.0 ? '#ffc107' : '#dc3545'),
                '#f0f0f0'
            ],
            borderWidth: 0
        }]
    };
    
    var apiGaugeChartOptions = {
        cutoutPercentage: 70,
        rotation: Math.PI,
        circumference: Math.PI,
        maintainAspectRatio: false,
        tooltips: {
            enabled: false
        },
        legend: {
            display: false
        }
    };
    
    new Chart(apiGaugeChartCanvas, {
        type: 'doughnut',
        data: apiGaugeChartData,
        options: apiGaugeChartOptions
    });
    
    // 로그 요약 차트
    var logSummaryChartCanvas = document.getElementById('logSummaryChart').getContext('2d');
    var logSummaryChartData = {
        labels: ['정보', '경고', '오류', '심각'],
        datasets: [
            {
                label: '로그 수',
                backgroundColor: ['#17a2b8', '#ffc107', '#dc3545', '#6610f2'],
                borderColor: ['#17a2b8', '#ffc107', '#dc3545', '#6610f2'],
                pointRadius: false,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: [
                    <?php echo isset($logSummary['info']) ? $logSummary['info'] : 0; ?>,
                    <?php echo isset($logSummary['warning']) ? $logSummary['warning'] : 0; ?>,
                    <?php echo isset($logSummary['error']) ? $logSummary['error'] : 0; ?>,
                    <?php echo isset($logSummary['critical']) ? $logSummary['critical'] : 0; ?>
                ]
            }
        ]
    };
    
    var logSummaryChartOptions = {
        maintainAspectRatio: false,
        responsive: true,
        legend: {
            display: true
        },
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true
                }
            }]
        }
    };
    
    new Chart(logSummaryChartCanvas, {
        type: 'bar',
        data: logSummaryChartData,
        options: logSummaryChartOptions
    });
});
</script>

<?php
// 시스템 관리자 권한 확인 함수 (예시)
function check_admin_level() {
    // 실제 구현은 사용자 권한에 따라 달라질 수 있음
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
        // 권한이 없으면 접근 거부 페이지로 리다이렉트
        header('Location: /server/dashboard/access-denied.php');
        exit;
    }
}
?>
