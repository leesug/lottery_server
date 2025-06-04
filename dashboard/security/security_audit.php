<?php
/**
 * 보안 감사 페이지
 * 
 * 이 페이지는 시스템의 보안 감사 기능을 제공합니다.
 * 보안 취약점 스캔, 감사 로그, 보안 권장사항 등을 관리합니다.
 */

// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "보안 감사";
$currentSection = "security";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = get_db_connection();

// 보안 감사 실행 처리
if (isset($_POST['run_audit'])) {
    // 실제 환경에서는 여기에 보안 감사 로직 구현
    // 예: 권한 설정, 파일 권한, 소프트웨어 버전, 보안 설정 등 확인
    
    // 감사 기록 추가
    $userId = 1; // 현재 로그인 사용자 ID (세션에서 가져오기)
    $auditType = $_POST['audit_type'];
    $status = 'completed';
    $summary = '보안 감사 완료';
    
    $sql = "INSERT INTO security_audit_logs (audit_type, user_id, status, summary, audit_date)
            VALUES (:audit_type, :user_id, :status, :summary, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':audit_type', $auditType, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->bindParam(':summary', $summary, PDO::PARAM_STR);
    $stmt->execute();
    
    $auditId = $db->lastInsertId();
    
    // 감사 결과 저장
    switch ($auditType) {
        case 'full':
            runFullAudit($db, $auditId);
            break;
        case 'permissions':
            runPermissionsAudit($db, $auditId);
            break;
        case 'passwords':
            runPasswordAudit($db, $auditId);
            break;
        case 'database':
            runDatabaseAudit($db, $auditId);
            break;
        case 'network':
            runNetworkAudit($db, $auditId);
            break;
    }
    
    logInfo("보안 감사가 수행되었습니다: " . $auditType, "security");
}

// 감사 이력 조회
$sql = "SELECT sal.*, u.username 
        FROM security_audit_logs sal
        LEFT JOIN users u ON sal.user_id = u.id
        ORDER BY sal.audit_date DESC
        LIMIT 10";
$stmt = $db->prepare($sql);
$stmt->execute();
$auditLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 가장 최근 감사 결과 조회
$recentAuditSql = "SELECT sal.id, sal.audit_type, sal.audit_date
                  FROM security_audit_logs sal
                  WHERE sal.status = 'completed'
                  ORDER BY sal.audit_date DESC
                  LIMIT 1";
$recentAuditStmt = $db->prepare($recentAuditSql);
$recentAuditStmt->execute();
$recentAudit = $recentAuditStmt->fetch(PDO::FETCH_ASSOC);

$auditResults = [];
if ($recentAudit) {
    $auditResultsSql = "SELECT category, risk_level, description, recommendation
                      FROM security_audit_results
                      WHERE audit_id = :audit_id
                      ORDER BY risk_level DESC";
    $auditResultsStmt = $db->prepare($auditResultsSql);
    $auditResultsStmt->bindParam(':audit_id', $recentAudit['id'], PDO::PARAM_INT);
    $auditResultsStmt->execute();
    $auditResults = $auditResultsStmt->fetchAll(PDO::FETCH_ASSOC);
}

// 감사 요약 정보
$statsBaseSql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN risk_level = 'high' THEN 1 ELSE 0 END) as high_risk,
                SUM(CASE WHEN risk_level = 'medium' THEN 1 ELSE 0 END) as medium_risk,
                SUM(CASE WHEN risk_level = 'low' THEN 1 ELSE 0 END) as low_risk
                FROM security_audit_results";

// 최근 감사 통계
$recentStatsSql = $statsBaseSql . " WHERE audit_id = :audit_id";
$recentStatsStmt = $db->prepare($recentStatsSql);
$recentStatsStmt->bindParam(':audit_id', $recentAudit['id'] ?? 0, PDO::PARAM_INT);
$recentStatsStmt->execute();
$recentStats = $recentStatsStmt->fetch(PDO::FETCH_ASSOC);

// 전체 감사 통계 (최근 30일)
$overallStatsSql = $statsBaseSql . " WHERE audit_id IN (
                                    SELECT id FROM security_audit_logs 
                                    WHERE audit_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                    )";
$overallStatsStmt = $db->prepare($overallStatsSql);
$overallStatsStmt->execute();
$overallStats = $overallStatsStmt->fetch(PDO::FETCH_ASSOC);

/**
 * 전체 보안 감사 수행
 */
function runFullAudit($db, $auditId) {
    // 모든 감사 함수 호출
    runPermissionsAudit($db, $auditId);
    runPasswordAudit($db, $auditId);
    runDatabaseAudit($db, $auditId);
    runNetworkAudit($db, $auditId);
    
    // 추가 감사: 소프트웨어 버전
    saveAuditResult($db, $auditId, '소프트웨어 버전', 'medium', 
                   'PHP 버전이 오래되었습니다 (현재: ' . phpversion() . ')', 
                   'PHP를 최신 버전으로 업데이트하세요.');
                   
    // 추가 감사: 서버 구성
    saveAuditResult($db, $auditId, '서버 구성', 'low', 
                   '디버그 모드가 활성화되어 있습니다', 
                   '프로덕션 환경에서는 디버그 모드를 비활성화하세요.');
}

/**
 * 권한 감사 수행
 */
function runPermissionsAudit($db, $auditId) {
    // 사용자 권한 감사
    saveAuditResult($db, $auditId, '사용자 권한', 'high', 
                   '관리자 권한을 가진 사용자가 너무 많습니다 (10명)', 
                   '관리자 권한을 필요한 사용자로만 제한하세요.');
                   
    // 파일 권한 감사
    saveAuditResult($db, $auditId, '파일 권한', 'medium', 
                   '일부 설정 파일이 과도한 읽기 권한을 가지고 있습니다', 
                   '설정 파일의 권한을 640 또는 644로 설정하세요.');
                   
    // 디렉토리 권한 감사
    saveAuditResult($db, $auditId, '디렉토리 권한', 'low', 
                   '로그 디렉토리에 쓰기 권한이 없습니다', 
                   '로그 디렉토리에 적절한 쓰기 권한을 부여하세요.');
}

/**
 * 비밀번호 감사 수행
 */
function runPasswordAudit($db, $auditId) {
    // 비밀번호 정책 감사
    saveAuditResult($db, $auditId, '비밀번호 정책', 'medium', 
                   '비밀번호 복잡성 요구사항이 낮습니다', 
                   '비밀번호 복잡성을 높게 설정하세요.');
                   
    // 기본 비밀번호 감사
    saveAuditResult($db, $auditId, '기본 비밀번호', 'high', 
                   '3개의 계정이 기본 비밀번호를 사용하고 있습니다', 
                   '모든 기본 비밀번호를 변경하세요.');
                   
    // 비밀번호 재사용 감사
    saveAuditResult($db, $auditId, '비밀번호 재사용', 'low', 
                   '비밀번호 재사용 방지 정책이 활성화되어 있지 않습니다', 
                   '비밀번호 재사용 방지 정책을 활성화하세요.');
}

/**
 * 데이터베이스 감사 수행
 */
function runDatabaseAudit($db, $auditId) {
    // 데이터베이스 권한 감사
    saveAuditResult($db, $auditId, '데이터베이스 권한', 'high', 
                   '데이터베이스 사용자가 과도한 권한을 가지고 있습니다', 
                   '최소 권한의 원칙에 따라 데이터베이스 사용자 권한을 제한하세요.');
                   
    // SQL 인젝션 취약점 감사
    saveAuditResult($db, $auditId, 'SQL 인젝션', 'medium', 
                   '일부 쿼리가 매개변수 바인딩을 사용하지 않습니다', 
                   '모든 동적 쿼리에 매개변수 바인딩을 사용하세요.');
                   
    // 데이터베이스 백업 감사
    saveAuditResult($db, $auditId, '데이터베이스 백업', 'low', 
                   '데이터베이스 백업이 정기적으로 수행되지 않고 있습니다', 
                   '자동 데이터베이스 백업 일정을 설정하세요.');
}

/**
 * 네트워크 감사 수행
 */
function runNetworkAudit($db, $auditId) {
    // SSL/TLS 감사
    saveAuditResult($db, $auditId, 'SSL/TLS', 'high', 
                   'SSL 인증서가 만료되었습니다', 
                   'SSL 인증서를 갱신하세요.');
                   
    // 방화벽 감사
    saveAuditResult($db, $auditId, '방화벽', 'medium', 
                   '일부 포트가 불필요하게 개방되어 있습니다', 
                   '필요하지 않은 포트를 닫으세요.');
                   
    // CORS 정책 감사
    saveAuditResult($db, $auditId, 'CORS 정책', 'low', 
                   'CORS 정책이 너무 관대합니다', 
                   'CORS 정책을 필요한 도메인으로만 제한하세요.');
}

/**
 * 감사 결과 저장
 */
function saveAuditResult($db, $auditId, $category, $riskLevel, $description, $recommendation) {
    $sql = "INSERT INTO security_audit_results (audit_id, category, risk_level, description, recommendation)
            VALUES (:audit_id, :category, :risk_level, :description, :recommendation)";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':audit_id', $auditId, PDO::PARAM_INT);
    $stmt->bindParam(':category', $category, PDO::PARAM_STR);
    $stmt->bindParam(':risk_level', $riskLevel, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':recommendation', $recommendation, PDO::PARAM_STR);
    return $stmt->execute();
}

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
        <!-- 감사 요약 -->
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">높은 위험 취약점</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <h1 class="text-danger"><?php echo $recentStats['high_risk'] ?? 0; ?></h1>
                            <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                        </div>
                        <p class="text-muted">최근 감사에서 발견된 높은 위험 취약점</p>
                        <div class="progress">
                            <div class="progress-bar bg-danger" style="width: <?php echo ($recentStats['total'] > 0) ? ($recentStats['high_risk'] / $recentStats['total'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">중간 위험 취약점</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <h1 class="text-warning"><?php echo $recentStats['medium_risk'] ?? 0; ?></h1>
                            <i class="fas fa-shield-alt fa-3x text-warning"></i>
                        </div>
                        <p class="text-muted">최근 감사에서 발견된 중간 위험 취약점</p>
                        <div class="progress">
                            <div class="progress-bar bg-warning" style="width: <?php echo ($recentStats['total'] > 0) ? ($recentStats['medium_risk'] / $recentStats['total'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">낮은 위험 취약점</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <h1 class="text-success"><?php echo $recentStats['low_risk'] ?? 0; ?></h1>
                            <i class="fas fa-info-circle fa-3x text-success"></i>
                        </div>
                        <p class="text-muted">최근 감사에서 발견된 낮은 위험 취약점</p>
                        <div class="progress">
                            <div class="progress-bar bg-success" style="width: <?php echo ($recentStats['total'] > 0) ? ($recentStats['low_risk'] / $recentStats['total'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /감사 요약 -->
        
        <div class="row">
            <div class="col-md-4">
                <!-- 감사 실행 카드 -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">보안 감사 실행</h3>
                    </div>
                    <div class="card-body">
                        <p>보안 감사를 실행하여 시스템의 취약점을 스캔하고 보안 권장사항을 확인할 수 있습니다.</p>
                        <form method="post">
                            <div class="form-group">
                                <label for="audit_type">감사 유형</label>
                                <select class="form-control" id="audit_type" name="audit_type">
                                    <option value="full">전체 감사</option>
                                    <option value="permissions">권한 감사</option>
                                    <option value="passwords">비밀번호 감사</option>
                                    <option value="database">데이터베이스 감사</option>
                                    <option value="network">네트워크 감사</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="send_report" name="send_report" checked>
                                    <label class="custom-control-label" for="send_report">감사 결과 이메일 발송</label>
                                </div>
                            </div>
                            <button type="submit" name="run_audit" class="btn btn-primary">감사 실행</button>
                        </form>
                    </div>
                </div>
                <!-- /감사 실행 카드 -->
                
                <!-- 감사 일정 카드 -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">감사 일정</h3>
                    </div>
                    <div class="card-body">
                        <p>정기적인 보안 감사를 예약하여 시스템을 지속적으로 모니터링할 수 있습니다.</p>
                        <form>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="scheduled_audit" checked>
                                    <label class="custom-control-label" for="scheduled_audit">정기 감사 활성화</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="schedule_frequency">감사 주기</label>
                                <select class="form-control" id="schedule_frequency">
                                    <option value="daily">매일</option>
                                    <option value="weekly" selected>매주</option>
                                    <option value="monthly">매월</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="schedule_day">실행 요일</label>
                                <select class="form-control" id="schedule_day">
                                    <option value="1">월요일</option>
                                    <option value="2">화요일</option>
                                    <option value="3">수요일</option>
                                    <option value="4">목요일</option>
                                    <option value="5">금요일</option>
                                    <option value="6">토요일</option>
                                    <option value="0">일요일</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="schedule_time">실행 시간</label>
                                <input type="time" class="form-control" id="schedule_time" value="03:00">
                            </div>
                            <button type="button" class="btn btn-primary">일정 저장</button>
                        </form>
                    </div>
                </div>
                <!-- /감사 일정 카드 -->
            </div>
            
            <div class="col-md-8">
                <!-- 감사 결과 카드 -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <?php if ($recentAudit): ?>
                            최근 보안 감사 결과 (<?php echo date('Y-m-d', strtotime($recentAudit['audit_date'])); ?>)
                            <?php else: ?>
                            보안 감사 결과
                            <?php endif; ?>
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($auditResults)): ?>
                        <div class="p-3">
                            <p class="text-center">감사 결과가 없습니다. 보안 감사를 실행하세요.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 15%">범주</th>
                                        <th style="width: 15%">위험 수준</th>
                                        <th style="width: 35%">설명</th>
                                        <th style="width: 35%">권장 사항</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($auditResults as $result): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['category']); ?></td>
                                        <td>
                                            <?php
                                            $riskClass = '';
                                            switch ($result['risk_level']) {
                                                case 'high':
                                                    $riskClass = 'danger';
                                                    $riskLabel = '높음';
                                                    break;
                                                case 'medium':
                                                    $riskClass = 'warning';
                                                    $riskLabel = '중간';
                                                    break;
                                                case 'low':
                                                    $riskClass = 'success';
                                                    $riskLabel = '낮음';
                                                    break;
                                                default:
                                                    $riskClass = 'secondary';
                                                    $riskLabel = '알 수 없음';
                                            }
                                            ?>
                                            <span class="badge badge-<?php echo $riskClass; ?>"><?php echo $riskLabel; ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($result['description']); ?></td>
                                        <td><?php echo htmlspecialchars($result['recommendation']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="#" class="btn btn-sm btn-info">상세 보고서 보기</a>
                        <a href="#" class="btn btn-sm btn-success">PDF로 내보내기</a>
                    </div>
                </div>
                <!-- /감사 결과 카드 -->
                
                <!-- 감사 이력 카드 -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">감사 이력</h3>
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
                                        <th>감사 ID</th>
                                        <th>감사 유형</th>
                                        <th>실행자</th>
                                        <th>상태</th>
                                        <th>날짜</th>
                                        <th>작업</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($auditLogs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">감사 이력이 없습니다.</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($auditLogs as $log): ?>
                                        <tr>
                                            <td><?php echo $log['id']; ?></td>
                                            <td>
                                                <?php
                                                switch ($log['audit_type']) {
                                                    case 'full':
                                                        echo '전체 감사';
                                                        break;
                                                    case 'permissions':
                                                        echo '권한 감사';
                                                        break;
                                                    case 'passwords':
                                                        echo '비밀번호 감사';
                                                        break;
                                                    case 'database':
                                                        echo '데이터베이스 감사';
                                                        break;
                                                    case 'network':
                                                        echo '네트워크 감사';
                                                        break;
                                                    default:
                                                        echo htmlspecialchars($log['audit_type']);
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['username'] ?? '시스템'); ?></td>
                                            <td>
                                                <?php if ($log['status'] === 'completed'): ?>
                                                <span class="badge badge-success">완료</span>
                                                <?php elseif ($log['status'] === 'in_progress'): ?>
                                                <span class="badge badge-warning">진행 중</span>
                                                <?php elseif ($log['status'] === 'failed'): ?>
                                                <span class="badge badge-danger">실패</span>
                                                <?php else: ?>
                                                <span class="badge badge-secondary"><?php echo htmlspecialchars($log['status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($log['audit_date'])); ?></td>
                                            <td>
                                                <a href="#" class="btn btn-xs btn-info" data-toggle="tooltip" title="결과 보기">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="#" class="btn btn-xs btn-success" data-toggle="tooltip" title="보고서 다운로드">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="#" class="btn btn-sm btn-primary">모든 감사 이력 보기</a>
                    </div>
                </div>
                <!-- /감사 이력 카드 -->
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log("보안 감사 페이지가 로드되었습니다.");
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>