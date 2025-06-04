<?php
/**
 * 외부접속감시 메인 페이지
 */

// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "외부접속감시 대시보드";
$currentSection = "external-monitoring";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$conn = getDBConnection();

// 페이지별 로직 처리
// 각 카테고리별 최근 활동 데이터 가져오기
$recentActivities = [];

// 추첨 방송국 최근 활동
$stmt = $conn->prepare("
    SELECT log_date, activity_type, description 
    FROM external_monitoring_logs 
    WHERE entity_type = 'broadcaster' 
    ORDER BY log_date DESC 
    LIMIT 5
");
$stmt->execute();
$recentActivities['broadcaster'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 은행 최근 활동
$stmt = $conn->prepare("
    SELECT log_date, activity_type, description 
    FROM external_monitoring_logs 
    WHERE entity_type = 'bank' 
    ORDER BY log_date DESC 
    LIMIT 5
");
$stmt->execute();
$recentActivities['bank'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 정부 최근 활동
$stmt = $conn->prepare("
    SELECT log_date, activity_type, description 
    FROM external_monitoring_logs 
    WHERE entity_type = 'government' 
    ORDER BY log_date DESC 
    LIMIT 5
");
$stmt->execute();
$recentActivities['government'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 기금처 최근 활동
$stmt = $conn->prepare("
    SELECT log_date, activity_type, description 
    FROM external_monitoring_logs 
    WHERE entity_type = 'fund' 
    ORDER BY log_date DESC 
    LIMIT 5
");
$stmt->execute();
$recentActivities['fund'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <!-- Info boxes -->
        <div class="row">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-tv"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">추첨 방송국</span>
                        <span class="info-box-number">
                            <?php 
                            // 추첨 방송국 접속 카운트
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM external_monitoring_logs WHERE entity_type = 'broadcaster'");
                            $stmt->execute();
                            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                            echo $count;
                            ?>
                            <small>건 접속</small>
                        </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-university"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">은행</span>
                        <span class="info-box-number">
                            <?php 
                            // 은행 접속 카운트
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM external_monitoring_logs WHERE entity_type = 'bank'");
                            $stmt->execute();
                            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                            echo $count;
                            ?>
                            <small>건 접속</small>
                        </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->

            <!-- fix for small devices only -->
            <div class="clearfix hidden-md-up"></div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-landmark"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">정부</span>
                        <span class="info-box-number">
                            <?php 
                            // 정부 접속 카운트
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM external_monitoring_logs WHERE entity_type = 'government'");
                            $stmt->execute();
                            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                            echo $count;
                            ?>
                            <small>건 접속</small>
                        </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-hand-holding-usd"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">기금처</span>
                        <span class="info-box-number">
                            <?php 
                            // 기금처 접속 카운트
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM external_monitoring_logs WHERE entity_type = 'fund'");
                            $stmt->execute();
                            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                            echo $count;
                            ?>
                            <small>건 접속</small>
                        </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->

        <!-- Main row -->
        <div class="row">
            <!-- Left col -->
            <div class="col-md-6">
                <!-- 추첨 방송국 & 은행 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">추첨 방송국 최근 활동</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped m-0">
                                <thead>
                                    <tr>
                                        <th>날짜</th>
                                        <th>활동 유형</th>
                                        <th>설명</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recentActivities['broadcaster']) > 0): ?>
                                        <?php foreach($recentActivities['broadcaster'] as $activity): ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d H:i', strtotime($activity['log_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">최근 활동이 없습니다.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/broadcaster/index.php" class="btn btn-sm btn-primary float-right">더보기</a>
                    </div>
                    <!-- /.card-footer -->
                </div>
                <!-- /.card -->
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">은행 최근 활동</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped m-0">
                                <thead>
                                    <tr>
                                        <th>날짜</th>
                                        <th>활동 유형</th>
                                        <th>설명</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recentActivities['bank']) > 0): ?>
                                        <?php foreach($recentActivities['bank'] as $activity): ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d H:i', strtotime($activity['log_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">최근 활동이 없습니다.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/bank/index.php" class="btn btn-sm btn-primary float-right">더보기</a>
                    </div>
                    <!-- /.card-footer -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->

            <div class="col-md-6">
                <!-- 정부 & 기금처 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">정부 최근 활동</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped m-0">
                                <thead>
                                    <tr>
                                        <th>날짜</th>
                                        <th>활동 유형</th>
                                        <th>설명</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recentActivities['government']) > 0): ?>
                                        <?php foreach($recentActivities['government'] as $activity): ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d H:i', strtotime($activity['log_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">최근 활동이 없습니다.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/government/index.php" class="btn btn-sm btn-primary float-right">더보기</a>
                    </div>
                    <!-- /.card-footer -->
                </div>
                <!-- /.card -->
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">기금처 최근 활동</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped m-0">
                                <thead>
                                    <tr>
                                        <th>날짜</th>
                                        <th>활동 유형</th>
                                        <th>설명</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recentActivities['fund']) > 0): ?>
                                        <?php foreach($recentActivities['fund'] as $activity): ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d H:i', strtotime($activity['log_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">최근 활동이 없습니다.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/fund/index.php" class="btn btn-sm btn-primary float-right">더보기</a>
                    </div>
                    <!-- /.card-footer -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div>
    <!--/. container-fluid -->
</section>
<!-- /.content -->

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
