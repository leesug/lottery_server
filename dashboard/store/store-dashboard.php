<?php
/**
 * Store Dashboard Page
 * 
 * This page displays statistics and analytics related to store activities,
 * including sales performance, equipment status, contract information, etc.
 */

// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "판매점 관리 대시보드";
$currentSection = "store";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = get_db_connection();

// 판매점 통계 정보 (Mock 데이터 사용)
$storeStats = [
    'total' => 1250,
    'active' => 1082,
    'inactive' => 128,
    'suspended' => 40,
    'new_30_days' => 57,
    'pending_approval' => 32,
    'contract_expiring' => 68
];

// 장비 상태 통계 (Mock 데이터 사용)
$equipmentStats = [
    'total' => 1450,
    'active' => 1325,
    'maintenance' => 87,
    'inactive' => 38,
    'new_30_days' => 45
];

// 지역별 판매점 분포 (Mock 데이터 사용)
$regionDistribution = [
    ['region' => '서울', 'count' => 380],
    ['region' => '경기', 'count' => 298],
    ['region' => '인천', 'count' => 115],
    ['region' => '부산', 'count' => 98],
    ['region' => '대구', 'count' => 76],
    ['region' => '광주', 'count' => 52],
    ['region' => '대전', 'count' => 48],
    ['region' => '울산', 'count' => 35],
    ['region' => '강원', 'count' => 41],
    ['region' => '충북', 'count' => 28],
    ['region' => '충남', 'count' => 32],
    ['region' => '전북', 'count' => 24],
    ['region' => '전남', 'count' => 28],
    ['region' => '경북', 'count' => 31],
    ['region' => '경남', 'count' => 37],
    ['region' => '제주', 'count' => 18],
    ['region' => '세종', 'count' => 9]
];

// 판매점 유형별 분포 (Mock 데이터 사용)
$storeTypes = [
    ['type' => '일반 소매점', 'count' => 720],
    ['type' => '복권 전문점', 'count' => 320],
    ['type' => '편의점', 'count' => 190],
    ['type' => '대형마트', 'count' => 20]
];

// 월별 판매점 등록 추이 (Mock 데이터 사용)
$monthlyRegistrations = [
    ['month' => '2024-06', 'label' => '6월', 'count' => 28],
    ['month' => '2024-07', 'label' => '7월', 'count' => 35],
    ['month' => '2024-08', 'label' => '8월', 'count' => 32],
    ['month' => '2024-09', 'label' => '9월', 'count' => 38],
    ['month' => '2024-10', 'label' => '10월', 'count' => 42],
    ['month' => '2024-11', 'label' => '11월', 'count' => 46],
    ['month' => '2024-12', 'label' => '12월', 'count' => 40],
    ['month' => '2025-01', 'label' => '1월', 'count' => 52],
    ['month' => '2025-02', 'label' => '2월', 'count' => 48],
    ['month' => '2025-03', 'label' => '3월', 'count' => 38],
    ['month' => '2025-04', 'label' => '4월', 'count' => 42],
    ['month' => '2025-05', 'label' => '5월', 'count' => 45]
];

// 최근 유지보수 요청 (Mock 데이터 사용)
$recentMaintenanceRequests = [
    ['id' => 387, 'equipment_code' => 'EQ2505T00128', 'store_name' => '행운복권', 'type' => '단말기 고장', 'status' => 'pending', 'request_date' => '2025-05-18 09:32:15'],
    ['id' => 386, 'equipment_code' => 'EQ2505T00415', 'store_name' => '드림복권', 'type' => '프린터 용지 교체', 'status' => 'in_progress', 'request_date' => '2025-05-18 08:15:43'],
    ['id' => 385, 'equipment_code' => 'EQ2505T00731', 'store_name' => '로또명당', 'type' => '네트워크 연결 오류', 'status' => 'completed', 'request_date' => '2025-05-17 15:48:22'],
    ['id' => 384, 'equipment_code' => 'EQ2505T00982', 'store_name' => '럭키복권', 'type' => '소프트웨어 업데이트', 'status' => 'completed', 'request_date' => '2025-05-17 14:20:11'],
    ['id' => 383, 'equipment_code' => 'EQ2505T00567', 'store_name' => '황금복권', 'type' => '화면 이상', 'status' => 'pending', 'request_date' => '2025-05-17 11:05:38']
];

// 상위 판매 성과 판매점 (Mock 데이터 사용)
$topPerformingStores = [
    ['store_code' => '240100032', 'store_name' => '행운복권 강남점', 'region' => '서울', 'sales_amount' => 254780000, 'sales_count' => 12530, 'winning_tickets' => 452],
    ['store_code' => '240200119', 'store_name' => '럭키복권 명동점', 'region' => '서울', 'sales_amount' => 230150000, 'sales_count' => 11230, 'winning_tickets' => 389],
    ['store_code' => '240300087', 'store_name' => '로또드림 부산점', 'region' => '부산', 'sales_amount' => 198540000, 'sales_count' => 9765, 'winning_tickets' => 325],
    ['store_code' => '240400128', 'store_name' => '당첨복권 대구점', 'region' => '대구', 'sales_amount' => 185720000, 'sales_count' => 9150, 'winning_tickets' => 298],
    ['store_code' => '240500056', 'store_name' => '황금복권 일산점', 'region' => '경기', 'sales_amount' => 178450000, 'sales_count' => 8745, 'winning_tickets' => 276]
];

// 계약 만료 예정 판매점 (Mock 데이터 사용)
$expiringContracts = [
    ['store_code' => '230500089', 'store_name' => '로또파워 수원점', 'region' => '경기', 'contract_start' => '2023-06-01', 'contract_end' => '2025-05-31', 'days_left' => 13],
    ['store_code' => '230500102', 'store_name' => '복권나라 안양점', 'region' => '경기', 'contract_start' => '2023-06-01', 'contract_end' => '2025-06-01', 'days_left' => 14],
    ['store_code' => '230500118', 'store_name' => '행운복권 인천점', 'region' => '인천', 'contract_start' => '2023-06-05', 'contract_end' => '2025-06-05', 'days_left' => 18],
    ['store_code' => '230500132', 'store_name' => '드림로또 광명점', 'region' => '경기', 'contract_start' => '2023-06-10', 'contract_end' => '2025-06-10', 'days_left' => 23],
    ['store_code' => '230500178', 'store_name' => '당첨복권 부천점', 'region' => '경기', 'contract_start' => '2023-06-15', 'contract_end' => '2025-06-15', 'days_left' => 28]
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
                    <li class="breadcrumb-item">판매점 관리</li>
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
                    <a href="store-list.php" class="btn btn-primary"><i class="fas fa-store"></i> 판매점 목록</a>
                    <a href="store-add.php" class="btn btn-success"><i class="fas fa-plus-circle"></i> 판매점 등록</a>
                    <a href="equipment-list.php" class="btn btn-info"><i class="fas fa-desktop"></i> 장비 관리</a>
                    <a href="contract-list.php" class="btn btn-warning"><i class="fas fa-file-contract"></i> 계약 관리</a>
                    <a href="store-performance.php" class="btn btn-secondary"><i class="fas fa-chart-line"></i> 성과 분석</a>
                </div>
            </div>
        </div>

        <!-- 판매점 통계 요약 카드 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo number_format($storeStats['total']); ?></h3>
                        <p>총 판매점 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <a href="store-list.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo number_format($storeStats['active']); ?></h3>
                        <p>활성 판매점</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <a href="store-list.php?filter=active" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo number_format($storeStats['contract_expiring']); ?></h3>
                        <p>계약 만료 예정</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <a href="contract-list.php?filter=expiring" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo number_format($equipmentStats['maintenance']); ?></h3>
                        <p>유지보수 필요 장비</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <a href="equipment-maintenance.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 판매점 등록 추이 차트 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">월별 판매점 등록 추이</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="storeRegistrationChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 판매점 상태 분포 차트 -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">판매점 상태 분포</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="storeStatusChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 상위 판매 성과 판매점 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">상위 판매 성과 판매점</h3>
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
                                        <th>판매점 코드</th>
                                        <th>판매점명</th>
                                        <th>지역</th>
                                        <th>판매액</th>
                                        <th>판매 건수</th>
                                        <th>당첨 티켓수</th>
                                        <th>액션</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topPerformingStores as $store): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($store['store_code']); ?></td>
                                        <td><?php echo htmlspecialchars($store['store_name']); ?></td>
                                        <td><?php echo htmlspecialchars($store['region']); ?></td>
                                        <td><?php echo number_format($store['sales_amount']); ?>원</td>
                                        <td><?php echo number_format($store['sales_count']); ?>건</td>
                                        <td><?php echo number_format($store['winning_tickets']); ?>장</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="store-details.php?code=<?php echo $store['store_code']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="store-performance.php?code=<?php echo $store['store_code']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-chart-bar"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer clearfix">
                        <a href="store-performance.php" class="btn btn-sm btn-secondary float-right">모든 판매점 성과 보기</a>
                    </div>
                </div>
            </div>

            <!-- 차트 및 통계 컬럼 -->
            <div class="col-md-4">
                <!-- 판매점 유형 분포 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">판매점 유형 분포</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="storeTypeChart" style="min-height: 200px; height: 200px; max-height: 200px; max-width: 100%;"></canvas>
                    </div>
                </div>

                <!-- 장비 상태 분포 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">장비 상태 분포</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box bg-gradient-info">
                                    <span class="info-box-icon"><i class="fas fa-desktop"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">총 장비</span>
                                        <span class="info-box-number"><?php echo number_format($equipmentStats['total']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-gradient-success">
                                    <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">정상 장비</span>
                                        <span class="info-box-number"><?php echo number_format($equipmentStats['active']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <div class="info-box bg-gradient-warning">
                                    <span class="info-box-icon"><i class="fas fa-tools"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">유지보수 중</span>
                                        <span class="info-box-number"><?php echo number_format($equipmentStats['maintenance']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-gradient-danger">
                                    <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">비활성 장비</span>
                                        <span class="info-box-number"><?php echo number_format($equipmentStats['inactive']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 계약 만료 예정 및 최근 유지보수 요청 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">계약 만료 예정 판매점</h3>
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
                                        <th>판매점 코드</th>
                                        <th>판매점명</th>
                                        <th>지역</th>
                                        <th>계약 만료일</th>
                                        <th>남은 일수</th>
                                        <th>액션</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expiringContracts as $contract): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($contract['store_code']); ?></td>
                                        <td><?php echo htmlspecialchars($contract['store_name']); ?></td>
                                        <td><?php echo htmlspecialchars($contract['region']); ?></td>
                                        <td><?php echo htmlspecialchars($contract['contract_end']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $contract['days_left'] <= 14 ? 'badge-danger' : 'badge-warning'; ?>">
                                                <?php echo $contract['days_left']; ?>일
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="contract-details.php?code=<?php echo $contract['store_code']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-file-contract"></i>
                                                </a>
                                                <a href="contract-renew.php?code=<?php echo $contract['store_code']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-sync-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer clearfix">
                        <a href="contract-list.php?filter=expiring" class="btn btn-sm btn-secondary float-right">모든 만료 예정 계약 보기</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">최근 유지보수 요청</h3>
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
                                        <th>장비 코드</th>
                                        <th>판매점명</th>
                                        <th>유형</th>
                                        <th>요청일시</th>
                                        <th>상태</th>
                                        <th>액션</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentMaintenanceRequests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['equipment_code']); ?></td>
                                        <td><?php echo htmlspecialchars($request['store_name']); ?></td>
                                        <td><?php echo htmlspecialchars($request['type']); ?></td>
                                        <td><?php echo htmlspecialchars($request['request_date']); ?></td>
                                        <td>
                                            <?php if ($request['status'] === 'pending'): ?>
                                                <span class="badge badge-warning">대기중</span>
                                            <?php elseif ($request['status'] === 'in_progress'): ?>
                                                <span class="badge badge-info">진행중</span>
                                            <?php elseif ($request['status'] === 'completed'): ?>
                                                <span class="badge badge-success">완료</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">취소</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="equipment-maintenance.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($request['status'] === 'pending'): ?>
                                                <a href="equipment-maintenance-edit.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer clearfix">
                        <a href="equipment-maintenance.php" class="btn btn-sm btn-secondary float-right">모든 유지보수 요청 보기</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 지역별 판매점 분포 지도 -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">지역별 판매점 분포</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div id="korea-map" style="height: 400px; position: relative;">
                                    <!-- 여기에 지도가 들어갈 예정 - SVG나 라이브러리 사용 가능 -->
                                    <div class="text-center" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                        <p><i class="fas fa-map-marked-alt fa-4x text-info"></i></p>
                                        <p class="text-muted">지역별 판매점 분포 지도는 외부 라이브러리를 통해 구현됩니다.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>지역</th>
                                            <th>판매점 수</th>
                                            <th style="width: 40%">비율</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // 상위 10개 지역 표시
                                        $topRegions = array_slice($regionDistribution, 0, 10);
                                        $totalStores = $storeStats['total'];
                                        foreach ($topRegions as $region): 
                                            $percentage = ($region['count'] / $totalStores) * 100;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($region['region']); ?></td>
                                            <td><?php echo number_format($region['count']); ?></td>
                                            <td>
                                                <div class="progress progress-xs">
                                                    <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                                                </div>
                                                <span class="badge bg-primary"><?php echo number_format($percentage, 1); ?>%</span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
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
    // 월별 판매점 등록 추이 차트
    const registrationCtx = document.getElementById('storeRegistrationChart').getContext('2d');
    const registrationChart = new Chart(registrationCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthlyRegistrations, 'label')); ?>,
            datasets: [{
                label: '신규 등록 판매점 수',
                backgroundColor: 'rgba(60,141,188,0.2)',
                borderColor: 'rgba(60,141,188,1)',
                pointRadius: 3,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: <?php echo json_encode(array_column($monthlyRegistrations, 'count')); ?>
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // 판매점 상태 분포 차트
    const statusCtx = document.getElementById('storeStatusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['활성', '비활성', '일시정지', '승인 대기'],
            datasets: [{
                data: [
                    <?php echo $storeStats['active']; ?>,
                    <?php echo $storeStats['inactive']; ?>,
                    <?php echo $storeStats['suspended']; ?>,
                    <?php echo $storeStats['pending_approval']; ?>
                ],
                backgroundColor: ['#00a65a', '#f39c12', '#f56954', '#00c0ef']
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true
        }
    });

    // 판매점 유형 분포 차트
    const typeCtx = document.getElementById('storeTypeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($storeTypes, 'type')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($storeTypes, 'count')); ?>,
                backgroundColor: ['#f56954', '#f39c12', '#00a65a', '#00c0ef']
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
