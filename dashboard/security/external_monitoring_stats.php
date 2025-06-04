<?php
// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 세션 체크 및 권한 검증
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: /pages/access-denied.php');
    exit;
}

// 페이지 변수 설정
$pageTitle = "외부 접속 감시 통계";
$currentSection = "security";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = getDBConnection();

// 기간 설정
$period = isset($_GET['period']) ? $_GET['period'] : 'today';
$customFromDate = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-30 days'));
$customToDate = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// 기간에 따른 조건 설정
$dateCondition = '';
$periodTitle = '';
switch ($period) {
    case 'today':
        $dateCondition = "DATE(log_date) = CURDATE()";
        $periodTitle = "오늘";
        break;
    case 'yesterday':
        $dateCondition = "DATE(log_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        $periodTitle = "어제";
        break;
    case 'this_week':
        $dateCondition = "YEARWEEK(log_date, 1) = YEARWEEK(CURDATE(), 1)";
        $periodTitle = "이번 주";
        break;
    case 'last_week':
        $dateCondition = "YEARWEEK(log_date, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)";
        $periodTitle = "지난 주";
        break;
    case 'this_month':
        $dateCondition = "YEAR(log_date) = YEAR(CURDATE()) AND MONTH(log_date) = MONTH(CURDATE())";
        $periodTitle = "이번 달";
        break;
    case 'last_month':
        $dateCondition = "YEAR(log_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(log_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
        $periodTitle = "지난 달";
        break;
    case 'custom':
        $dateCondition = "DATE(log_date) BETWEEN '{$customFromDate}' AND '{$customToDate}'";
        $periodTitle = "{$customFromDate} ~ {$customToDate}";
        break;
    default:
        $dateCondition = "DATE(log_date) = CURDATE()";
        $periodTitle = "오늘";
        break;
}

// 통계 데이터 조회
try {
    // 총 로그 수
    $totalSql = "SELECT COUNT(*) as total FROM external_monitoring_logs WHERE {$dateCondition}";
    $totalStmt = $db->query($totalSql);
    $totalLogs = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 고유 IP 주소 수
    $uniqueIpSql = "SELECT COUNT(DISTINCT ip_address) as count FROM external_monitoring_logs WHERE {$dateCondition}";
    $uniqueIpStmt = $db->query($uniqueIpSql);
    $uniqueIps = $uniqueIpStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // 엔터티 유형별 통계
    $entitySql = "SELECT entity_type, COUNT(*) as count FROM external_monitoring_logs 
                 WHERE {$dateCondition} GROUP BY entity_type ORDER BY count DESC";
    $entityStmt = $db->query($entitySql);
    $entityStats = $entityStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 활동 유형별 통계
    $activitySql = "SELECT activity_type, COUNT(*) as count FROM external_monitoring_logs 
                   WHERE {$dateCondition} GROUP BY activity_type ORDER BY count DESC";
    $activityStmt = $db->query($activitySql);
    $activityStats = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 시간별 통계
    $hourlySql = "SELECT HOUR(log_date) as hour, COUNT(*) as count FROM external_monitoring_logs 
                 WHERE {$dateCondition} GROUP BY HOUR(log_date) ORDER BY hour";
    $hourlyStmt = $db->query($hourlySql);
    $hourlyStats = $hourlyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 빈 시간대 채우기
    $hourlyData = [];
    for ($i = 0; $i < 24; $i++) {
        $hourlyData[$i] = 0;
    }
    
    foreach ($hourlyStats as $stat) {
        $hourlyData[(int)$stat['hour']] = (int)$stat['count'];
    }
    
    // 일별 통계 (최근 30일)
    $dailySql = "SELECT DATE(log_date) as date, COUNT(*) as count FROM external_monitoring_logs 
                WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(log_date) ORDER BY date";
    $dailyStmt = $db->query($dailySql);
    $dailyStats = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 상위 IP 주소
    $ipSql = "SELECT ip_address, COUNT(*) as count FROM external_monitoring_logs 
             WHERE {$dateCondition} GROUP BY ip_address ORDER BY count DESC LIMIT 10";
    $ipStmt = $db->query($ipSql);
    $ipStats = $ipStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 의심스러운 활동
    $suspiciousSql = "SELECT l.*, 
                     CASE 
                         WHEN l.entity_type = 'broadcaster' THEN b.name
                         WHEN l.entity_type = 'bank' THEN bk.bank_name
                         WHEN l.entity_type = 'government' THEN ga.agency_name
                         WHEN l.entity_type = 'fund' THEN fd.department_name
                         ELSE 'Unknown'
                     END as entity_name
                     FROM external_monitoring_logs l
                     LEFT JOIN broadcaster b ON l.entity_type = 'broadcaster' AND l.entity_id = b.id
                     LEFT JOIN banks bk ON l.entity_type = 'bank' AND l.entity_id = bk.id
                     LEFT JOIN government_agencies ga ON l.entity_type = 'government' AND l.entity_id = ga.id
                     LEFT JOIN fund_departments fd ON l.entity_type = 'fund' AND l.entity_id = fd.id
                     WHERE {$dateCondition} AND l.activity_type = '로그인 실패'
                     ORDER BY l.log_date DESC LIMIT 5";
    $suspiciousStmt = $db->query($suspiciousSql);
    $suspiciousActivities = $suspiciousStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 엔터티별 활동 통계
    $entityDetailsSql = "SELECT 
                        l.entity_type,
                        CASE 
                            WHEN l.entity_type = 'broadcaster' THEN b.name
                            WHEN l.entity_type = 'bank' THEN bk.bank_name
                            WHEN l.entity_type = 'government' THEN ga.agency_name
                            WHEN l.entity_type = 'fund' THEN fd.department_name
                            ELSE 'Unknown'
                        END as entity_name,
                        l.entity_id,
                        COUNT(*) as total_activities
                        FROM external_monitoring_logs l
                        LEFT JOIN broadcaster b ON l.entity_type = 'broadcaster' AND l.entity_id = b.id
                        LEFT JOIN banks bk ON l.entity_type = 'bank' AND l.entity_id = bk.id
                        LEFT JOIN government_agencies ga ON l.entity_type = 'government' AND l.entity_id = ga.id
                        LEFT JOIN fund_departments fd ON l.entity_type = 'fund' AND l.entity_id = fd.id
                        WHERE {$dateCondition}
                        GROUP BY l.entity_type, l.entity_id
                        ORDER BY total_activities DESC
                        LIMIT 10";
    $entityDetailsStmt = $db->query($entityDetailsSql);
    $entityDetails = $entityDetailsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // 에러 로깅
    error_log("외부 접속 감시 통계 조회 오류: " . $e->getMessage());
    $error = "통계 데이터 조회 중 오류가 발생했습니다.";
}

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?php echo $pageTitle; ?> - <?php echo $periodTitle; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/index.php">홈</a></li>
                    <li class="breadcrumb-item">보안 관리</li>
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
        <!-- 기간 선택 카드 -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">기간 선택</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="periodForm" method="GET" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>기간</label>
                                <select class="form-control" name="period" id="period">
                                    <option value="today" <?php echo $period == 'today' ? 'selected' : ''; ?>>오늘</option>
                                    <option value="yesterday" <?php echo $period == 'yesterday' ? 'selected' : ''; ?>>어제</option>
                                    <option value="this_week" <?php echo $period == 'this_week' ? 'selected' : ''; ?>>이번 주</option>
                                    <option value="last_week" <?php echo $period == 'last_week' ? 'selected' : ''; ?>>지난 주</option>
                                    <option value="this_month" <?php echo $period == 'this_month' ? 'selected' : ''; ?>>이번 달</option>
                                    <option value="last_month" <?php echo $period == 'last_month' ? 'selected' : ''; ?>>지난 달</option>
                                    <option value="custom" <?php echo $period == 'custom' ? 'selected' : ''; ?>>직접 설정</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 custom-date-range" <?php echo $period != 'custom' ? 'style="display:none;"' : ''; ?>>
                            <div class="form-group">
                                <label>시작일</label>
                                <input type="date" class="form-control" name="from_date" value="<?php echo htmlspecialchars($customFromDate); ?>">
                            </div>
                        </div>
                        <div class="col-md-3 custom-date-range" <?php echo $period != 'custom' ? 'style="display:none;"' : ''; ?>>
                            <div class="form-group">
                                <label>종료일</label>
                                <input type="date" class="form-control" name="to_date" value="<?php echo htmlspecialchars($customToDate); ?>">
                            </div>
                        </div>
                        <div class="col-md-3 mt-4">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> 적용
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- /기간 선택 카드 -->

        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <h5><i class="icon fas fa-ban"></i> 오류</h5>
            <?php echo $error; ?>
        </div>
        <?php else: ?>

        <!-- 통계 개요 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo number_format($totalLogs); ?></h3>
                        <p>총 접속 기록</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <a href="<?php echo SERVER_URL; ?>/dashboard/security/external_monitoring.php?<?php echo http_build_query(['from_date' => $customFromDate, 'to_date' => $customToDate]); ?>" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo number_format($uniqueIps); ?></h3>
                        <p>고유 IP 주소</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <a href="#ip-analysis" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <?php
                        // 가장 많은 활동이 있는 엔터티 유형 찾기
                        $topEntityType = !empty($entityStats) ? $entityStats[0]['entity_type'] : 'N/A';
                        $topEntityCount = !empty($entityStats) ? $entityStats[0]['count'] : 0;
                        
                        $entityTypeLabel = '';
                        switch ($topEntityType) {
                            case 'broadcaster': $entityTypeLabel = '방송국'; break;
                            case 'bank': $entityTypeLabel = '은행'; break;
                            case 'government': $entityTypeLabel = '정부기관'; break;
                            case 'fund': $entityTypeLabel = '기금처'; break;
                            default: $entityTypeLabel = '알 수 없음'; break;
                        }
                        ?>
                        <h3><?php echo $entityTypeLabel; ?></h3>
                        <p>가장 활발한 엔터티 유형 (<?php echo number_format($topEntityCount); ?>건)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <a href="#entity-stats" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <?php
                        // 가장 많은 활동이 있는 시간대 찾기
                        $maxHour = 0;
                        $maxCount = 0;
                        
                        foreach ($hourlyData as $hour => $count) {
                            if ($count > $maxCount) {
                                $maxHour = $hour;
                                $maxCount = $count;
                            }
                        }
                        
                        // 시간대 형식 변환 (24시간 -> 12시간)
                        $formattedHour = $maxHour % 12;
                        if ($formattedHour == 0) $formattedHour = 12;
                        $amPm = $maxHour < 12 ? '오전' : '오후';
                        ?>
                        <h3><?php echo "{$amPm} {$formattedHour}시"; ?></h3>
                        <p>가장 활발한 시간대 (<?php echo number_format($maxCount); ?>건)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <a href="#hourly-stats" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <!-- /통계 개요 -->

        <div class="row">
            <!-- 시간별 통계 차트 -->
            <div class="col-md-8">
                <div class="card" id="hourly-stats">
                    <div class="card-header">
                        <h3 class="card-title">시간별 접속 통계</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="hourlyChart" style="min-height: 300px; height: 300px; max-height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /시간별 통계 차트 -->

            <!-- 엔터티 유형별 통계 -->
            <div class="col-md-4">
                <div class="card" id="entity-stats">
                    <div class="card-header">
                        <h3 class="card-title">엔터티 유형별 통계</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="entityChart" style="min-height: 300px; height: 300px; max-height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /엔터티 유형별 통계 -->
        </div>

        <div class="row">
            <!-- 활동 유형별 통계 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">활동 유형별 통계</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>활동 유형</th>
                                        <th>건수</th>
                                        <th>비율</th>
                                        <th>그래프</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!empty($activityStats)) {
                                        foreach ($activityStats as $activity) {
                                            $percentage = $totalLogs > 0 ? round(($activity['count'] / $totalLogs) * 100, 1) : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                                <td><?php echo number_format($activity['count']); ?></td>
                                                <td><?php echo $percentage; ?>%</td>
                                                <td>
                                                    <div class="progress progress-xs">
                                                        <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="4" class="text-center">데이터가 없습니다.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /활동 유형별 통계 -->

            <!-- 상위 IP 주소 -->
            <div class="col-md-6" id="ip-analysis">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">상위 IP 주소</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>IP 주소</th>
                                        <th>접속 횟수</th>
                                        <th>비율</th>
                                        <th>상태</th>
                                        <th>작업</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!empty($ipStats)) {
                                        foreach ($ipStats as $ip) {
                                            $percentage = $totalLogs > 0 ? round(($ip['count'] / $totalLogs) * 100, 1) : 0;
                                            
                                            // IP 차단 여부 확인
                                            $isBlockedSql = "SELECT COUNT(*) as blocked FROM ip_blocklist WHERE ip_address = :ip_address AND is_active = 1";
                                            $isBlockedStmt = $db->prepare($isBlockedSql);
                                            $isBlockedStmt->bindParam(':ip_address', $ip['ip_address'], PDO::PARAM_STR);
                                            $isBlockedStmt->execute();
                                            $isBlocked = ($isBlockedStmt->fetch(PDO::FETCH_ASSOC)['blocked'] > 0);
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($ip['ip_address']); ?></td>
                                                <td><?php echo number_format($ip['count']); ?></td>
                                                <td><?php echo $percentage; ?>%</td>
                                                <td>
                                                    <?php if ($isBlocked): ?>
                                                    <span class="badge badge-danger">차단됨</span>
                                                    <?php else: ?>
                                                    <span class="badge badge-success">허용됨</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!$isBlocked): ?>
                                                    <button type="button" class="btn btn-xs btn-danger block-ip" data-ip="<?php echo htmlspecialchars($ip['ip_address']); ?>">
                                                        <i class="fas fa-ban"></i> 차단
                                                    </button>
                                                    <?php else: ?>
                                                    <button type="button" class="btn btn-xs btn-success unblock-ip" data-ip="<?php echo htmlspecialchars($ip['ip_address']); ?>">
                                                        <i class="fas fa-check"></i> 차단 해제
                                                    </button>
                                                    <?php endif; ?>
                                                    <a href="<?php echo SERVER_URL; ?>/dashboard/security/external_monitoring.php?ip_address=<?php echo urlencode($ip['ip_address']); ?>" class="btn btn-xs btn-info">
                                                        <i class="fas fa-eye"></i> 상세
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center">데이터가 없습니다.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /상위 IP 주소 -->
        </div>

        <div class="row">
            <!-- 일별 통계 차트 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">일별 접속 통계 (최근 30일)</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="dailyChart" style="min-height: 300px; height: 300px; max-height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /일별 통계 차트 -->

            <!-- 의심스러운 활동 -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">의심스러운 활동</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="products-list product-list-in-card pl-2 pr-2">
                            <?php if (!empty($suspiciousActivities)): ?>
                                <?php foreach ($suspiciousActivities as $activity): ?>
                                <li class="item">
                                    <div class="product-img">
                                        <?php
                                        $iconClass = '';
                                        switch ($activity['entity_type']) {
                                            case 'broadcaster': $iconClass = 'fas fa-broadcast-tower text-primary'; break;
                                            case 'bank': $iconClass = 'fas fa-university text-success'; break;
                                            case 'government': $iconClass = 'fas fa-landmark text-warning'; break;
                                            case 'fund': $iconClass = 'fas fa-money-bill-wave text-info'; break;
                                            default: $iconClass = 'fas fa-question-circle text-secondary'; break;
                                        }
                                        ?>
                                        <i class="<?php echo $iconClass; ?> fa-2x"></i>
                                    </div>
                                    <div class="product-info">
                                        <a href="<?php echo SERVER_URL; ?>/dashboard/security/external_monitoring.php?id=<?php echo $activity['id']; ?>" class="product-title">
                                            <?php echo htmlspecialchars($activity['entity_name']); ?>
                                            <span class="badge badge-danger float-right"><?php echo htmlspecialchars($activity['activity_type']); ?></span>
                                        </a>
                                        <span class="product-description">
                                            <?php echo htmlspecialchars($activity['description']); ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> <?php echo date('Y-m-d H:i:s', strtotime($activity['log_date'])); ?>
                                                <i class="fas fa-network-wired ml-2"></i> <?php echo htmlspecialchars($activity['ip_address']); ?>
                                            </small>
                                        </span>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="item">
                                    <div class="product-info text-center py-3">
                                        <span>의심스러운 활동이 없습니다.</span>
                                    </div>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="card-footer text-center">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/security/external_monitoring.php?activity_type=로그인 실패" class="uppercase">모든 의심스러운 활동 보기</a>
                    </div>
                </div>
            </div>
            <!-- /의심스러운 활동 -->
        </div>

        <!-- 엔터티별 활동 통계 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">엔터티별 활동 통계</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>엔터티 유형</th>
                                <th>엔터티 이름</th>
                                <th>활동 건수</th>
                                <th>비율</th>
                                <th>그래프</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($entityDetails)) {
                                foreach ($entityDetails as $entity) {
                                    $percentage = $totalLogs > 0 ? round(($entity['total_activities'] / $totalLogs) * 100, 1) : 0;
                                    
                                    $entityTypeLabel = '';
                                    $badgeClass = '';
                                    switch ($entity['entity_type']) {
                                        case 'broadcaster': 
                                            $entityTypeLabel = '방송국'; 
                                            $badgeClass = 'badge-primary';
                                            break;
                                        case 'bank': 
                                            $entityTypeLabel = '은행'; 
                                            $badgeClass = 'badge-success';
                                            break;
                                        case 'government': 
                                            $entityTypeLabel = '정부기관'; 
                                            $badgeClass = 'badge-warning';
                                            break;
                                        case 'fund': 
                                            $entityTypeLabel = '기금처'; 
                                            $badgeClass = 'badge-info';
                                            break;
                                        default: 
                                            $entityTypeLabel = '알 수 없음'; 
                                            $badgeClass = 'badge-secondary';
                                            break;
                                    }
                                    ?>
                                    <tr>
                                        <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $entityTypeLabel; ?></span></td>
                                        <td><?php echo htmlspecialchars($entity['entity_name']); ?></td>
                                        <td><?php echo number_format($entity['total_activities']); ?></td>
                                        <td><?php echo $percentage; ?>%</td>
                                        <td>
                                            <div class="progress progress-xs">
                                                <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="<?php echo SERVER_URL; ?>/dashboard/security/external_monitoring.php?entity_type=<?php echo urlencode($entity['entity_type']); ?>&entity_id=<?php echo $entity['entity_id']; ?>" class="btn btn-xs btn-info">
                                                <i class="fas fa-eye"></i> 상세
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center">데이터가 없습니다.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- /엔터티별 활동 통계 -->

        <?php endif; ?>
    </div>
</section>
<!-- /.content -->

<!-- IP 차단 모달 -->
<div class="modal fade" id="blockIpModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">IP 주소 차단</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="blockIpForm">
                    <input type="hidden" id="ipAddress" name="ip_address">
                    <div class="form-group">
                        <label for="blockReason">차단 이유</label>
                        <textarea class="form-control" id="blockReason" name="reason" rows="3" placeholder="차단 이유를 입력하세요."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger" id="confirmBlockIp">차단</button>
            </div>
        </div>
    </div>
</div>
<!-- /IP 차단 모달 -->

<!-- IP 차단 해제 모달 -->
<div class="modal fade" id="unblockIpModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">IP 주소 차단 해제</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>다음 IP 주소의 차단을 해제하시겠습니까?</p>
                <h5 id="unblockIpAddress" class="text-center"></h5>
                <input type="hidden" id="unblockIpValue">
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-success" id="confirmUnblockIp">차단 해제</button>
            </div>
        </div>
    </div>
</div>
<!-- /IP 차단 해제 모달 -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 기간 선택 변경 시 커스텀 날짜 필드 표시/숨김
    document.getElementById('period').addEventListener('change', function() {
        var customDateFields = document.querySelectorAll('.custom-date-range');
        var display = this.value === 'custom' ? 'block' : 'none';
        customDateFields.forEach(function(field) {
            field.style.display = display;
        });
    });

    // 차트 데이터 준비
    var hourlyData = <?php echo json_encode(array_values($hourlyData)); ?>;
    var hourLabels = [];
    for (var i = 0; i < 24; i++) {
        var hour = i % 12;
        if (hour === 0) hour = 12;
        var amPm = i < 12 ? '오전' : '오후';
        hourLabels.push(amPm + ' ' + hour + '시');
    }

    // 시간별 통계 차트
    var hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    var hourlyChart = new Chart(hourlyCtx, {
        type: 'bar',
        data: {
            labels: hourLabels,
            datasets: [{
                label: '접속 건수',
                data: hourlyData,
                backgroundColor: 'rgba(60, 141, 188, 0.7)',
                borderColor: 'rgba(60, 141, 188, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // 엔터티 유형별 통계 차트
    var entityData = {
        labels: [],
        datasets: [{
            data: [],
            backgroundColor: [
                '#3c8dbc', // Primary (Blue)
                '#00a65a', // Success (Green)
                '#f39c12', // Warning (Yellow)
                '#00c0ef', // Info (Light Blue)
                '#d2d6de'  // Gray
            ]
        }]
    };

    <?php
    if (!empty($entityStats)) {
        foreach ($entityStats as $entity) {
            $label = '';
            switch ($entity['entity_type']) {
                case 'broadcaster': $label = '방송국'; break;
                case 'bank': $label = '은행'; break;
                case 'government': $label = '정부기관'; break;
                case 'fund': $label = '기금처'; break;
                default: $label = '알 수 없음'; break;
            }
            echo "entityData.labels.push('{$label}');\n";
            echo "entityData.datasets[0].data.push({$entity['count']});\n";
        }
    }
    ?>

    var entityCtx = document.getElementById('entityChart').getContext('2d');
    var entityChart = new Chart(entityCtx, {
        type: 'doughnut',
        data: entityData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // 일별 통계 차트
    var dailyData = {
        labels: [],
        datasets: [{
            label: '접속 건수',
            data: [],
            borderColor: 'rgba(60, 141, 188, 1)',
            backgroundColor: 'rgba(60, 141, 188, 0.2)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    };

    <?php
    if (!empty($dailyStats)) {
        foreach ($dailyStats as $daily) {
            $formattedDate = date('m/d', strtotime($daily['date']));
            echo "dailyData.labels.push('{$formattedDate}');\n";
            echo "dailyData.datasets[0].data.push({$daily['count']});\n";
        }
    }
    ?>

    var dailyCtx = document.getElementById('dailyChart').getContext('2d');
    var dailyChart = new Chart(dailyCtx, {
        type: 'line',
        data: dailyData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // IP 차단 모달
    var blockIpButtons = document.querySelectorAll('.block-ip');
    var ipAddressInput = document.getElementById('ipAddress');
    var blockReasonInput = document.getElementById('blockReason');
    var confirmBlockIpButton = document.getElementById('confirmBlockIp');

    blockIpButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var ip = this.getAttribute('data-ip');
            ipAddressInput.value = ip;
            blockReasonInput.value = '의심스러운 접속 패턴';
            $('#blockIpModal').modal('show');
        });
    });

    confirmBlockIpButton.addEventListener('click', function() {
        var ip = ipAddressInput.value;
        var reason = blockReasonInput.value;

        if (!ip) {
            alert('IP 주소가 필요합니다.');
            return;
        }

        if (!reason) {
            alert('차단 이유를 입력해주세요.');
            return;
        }

        // IP 차단 API 호출
        fetch('/api/ip_blocklist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'block_ip',
                ip_address: ip,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('IP 주소가 성공적으로 차단되었습니다.');
                location.reload();
            } else {
                alert('오류: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('IP 차단 중 오류가 발생했습니다.');
        });

        $('#blockIpModal').modal('hide');
    });

    // IP 차단 해제 모달
    var unblockIpButtons = document.querySelectorAll('.unblock-ip');
    var unblockIpAddressSpan = document.getElementById('unblockIpAddress');
    var unblockIpValueInput = document.getElementById('unblockIpValue');
    var confirmUnblockIpButton = document.getElementById('confirmUnblockIp');

    unblockIpButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var ip = this.getAttribute('data-ip');
            unblockIpAddressSpan.textContent = ip;
            unblockIpValueInput.value = ip;
            $('#unblockIpModal').modal('show');
        });
    });

    confirmUnblockIpButton.addEventListener('click', function() {
        var ip = unblockIpValueInput.value;

        if (!ip) {
            alert('IP 주소가 필요합니다.');
            return;
        }

        // IP 차단 해제 API 호출 (실제로는 차단 정보를 찾아서 해제해야 함)
        fetch('/api/ip_blocklist.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'unblock_ip',
                ip_address: ip,
                is_active: 0
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('IP 주소 차단이 해제되었습니다.');
                location.reload();
            } else {
                alert('오류: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('IP 차단 해제 중 오류가 발생했습니다.');
        });

        $('#unblockIpModal').modal('hide');
    });

    // 콘솔에 로딩 완료 로그
    console.log("[외부 접속 감시 통계] 페이지 로딩 완료");
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
