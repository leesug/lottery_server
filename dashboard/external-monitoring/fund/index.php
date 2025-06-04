<?php
/**
 * 기금처 접속 모니터링 메인 페이지
 */

// 공통 파일 포함
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "기금처 모니터링";
$currentSection = "fund";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = getDBConnection();

// 로그인 여부 확인 (기금처 분과별 로그인)
$loggedIn = false;
$departmentName = '';

if (isset($_SESSION['fund_department_id'])) {
    $loggedIn = true;
    $departmentId = $_SESSION['fund_department_id'];
    
    // 분과 정보 가져오기 (실제로는 DB에서)
    // 분과명 가져오기 (샘플 데이터)
    switch ($departmentId) {
        case 1:
            $departmentName = '문화예술 분과';
            break;
        case 2:
            $departmentName = '체육진흥 분과';
            break;
        case 3:
            $departmentName = '사회복지 분과';
            break;
        case 4:
            $departmentName = '재난구호 분과';
            break;
        case 5:
            $departmentName = '지역사회 분과';
            break;
        default:
            $departmentName = '알 수 없는 분과';
    }
}

// 분과별 로그인 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fund_login'])) {
    $departmentId = $_POST['department_id'];
    $password = $_POST['password'];
    
    // 실제로는 DB에서 확인해야 함
    if ($departmentId && $password === '1234') {
        $_SESSION['fund_department_id'] = $departmentId;
        $loggedIn = true;
        
        // 분과명 가져오기 (샘플 데이터)
        switch ($departmentId) {
            case 1:
                $departmentName = '문화예술 분과';
                break;
            case 2:
                $departmentName = '체육진흥 분과';
                break;
            case 3:
                $departmentName = '사회복지 분과';
                break;
            case 4:
                $departmentName = '재난구호 분과';
                break;
            case 5:
                $departmentName = '지역사회 분과';
                break;
            default:
                $departmentName = '알 수 없는 분과';
        }
        
        // departmentName 변수가 확실히 설정된 후에 성공 메시지 구성
        $successMessage = "로그인에 성공했습니다. {$departmentName}에 오신 것을 환영합니다.";
    } else {
        $errorMessage = "로그인에 실패했습니다. 분과 ID와 비밀번호를 확인해주세요.";
    }
}

// 로그아웃 처리
if (isset($_GET['logout'])) {
    unset($_SESSION['fund_department_id']);
    $loggedIn = false;
    $successMessage = "로그아웃 되었습니다.";
}

// 기금 분과 목록 (샘플 데이터)
$departments = [
    ['id' => 1, 'name' => '문화예술 분과'],
    ['id' => 2, 'name' => '체육진흥 분과'],
    ['id' => 3, 'name' => '사회복지 분과'],
    ['id' => 4, 'name' => '재난구호 분과'],
    ['id' => 5, 'name' => '지역사회 분과']
];

// 샘플 기금 할당 데이터
$allocations = [
    [
        'round' => 125,
        'date' => '2025-05-16',
        'amount' => 4614641496,
        'department' => '문화예술 분과',
        'status' => 'transferred',
        'contact_person' => '김예술',
        'contact_phone' => '010-1111-2222'
    ],
    [
        'round' => 125,
        'date' => '2025-05-16',
        'amount' => 5127379440,
        'department' => '체육진흥 분과',
        'status' => 'transferred',
        'contact_person' => '박체육',
        'contact_phone' => '010-2222-3333'
    ],
    [
        'round' => 125,
        'date' => '2025-05-16',
        'amount' => 8972914020,
        'department' => '사회복지 분과',
        'status' => 'transferred',
        'contact_person' => '이복지',
        'contact_phone' => '010-3333-4444'
    ],
    [
        'round' => 125,
        'date' => '2025-05-16',
        'amount' => 3845534580,
        'department' => '재난구호 분과',
        'status' => 'transferred',
        'contact_person' => '최재난',
        'contact_phone' => '010-4444-5555'
    ],
    [
        'round' => 125,
        'date' => '2025-05-16',
        'amount' => 3076427664,
        'department' => '지역사회 분과',
        'status' => 'transferred',
        'contact_person' => '정지역',
        'contact_phone' => '010-5555-6666'
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
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/index.php">홈</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/index.php">외부접속감시</a></li>
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
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-check"></i> 성공!</h5>
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> 오류!</h5>
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$loggedIn): ?>
        <!-- 분과별 로그인 -->
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">기금분과 로그인</h3>
                    </div>
                    <!-- /.card-header -->
                    
                    <form method="post" action="">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="department_id">분과 선택</label>
                                <select class="form-control" id="department_id" name="department_id" required>
                                    <option value="">분과를 선택하세요</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?php echo $department['id']; ?>"><?php echo htmlspecialchars($department['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="password">비밀번호</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="비밀번호" required>
                            </div>
                        </div>
                        <!-- /.card-body -->
                        
                        <div class="card-footer">
                            <button type="submit" name="fund_login" class="btn btn-primary">로그인</button>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
                
                <div class="callout callout-info">
                    <h5>안내사항</h5>
                    <p>각 기금 분과는 전용 계정으로 로그인하여 기금 정보를 확인할 수 있습니다. 계정이 없거나 비밀번호를 잊으신 경우 관리자에게 문의하세요.</p>
                </div>
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
        <?php else: ?>
        <!-- 로그인 후 화면 -->
        
        <!-- 상단 정보 요약 -->
        <div class="row">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-university"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">분과명</span>
                        <span class="info-box-number"><?php echo htmlspecialchars($departmentName); ?></span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-money-bill-wave"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">최근 할당 기금</span>
                        <span class="info-box-number"><?php echo number_format(4614641496); ?>원</span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-calendar-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">최근 기금 부여일</span>
                        <span class="info-box-number">2025-05-16</span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-danger"><i class="fas fa-user-tie"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">담당자</span>
                        <span class="info-box-number">김예술 (010-1111-2222)</span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
        
        <!-- 안내사항 -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">안내사항</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <div class="callout callout-info">
                    <h5>기금 사용 보고서 제출 안내</h5>
                    <p>각 분과별 기금 사용 보고서는 매월 말일까지 제출해주시기 바랍니다. 보고서 양식은 <a href="#">여기</a>에서 다운로드 가능합니다.</p>
                </div>
                <div class="callout callout-warning">
                    <h5>2025년 2분기 기금 배분 계획</h5>
                    <p>2025년 2분기 기금 배분 계획이 확정되었습니다. 자세한 내용은 <a href="#">공지사항</a>을 참고해주세요.</p>
                </div>
                <div class="callout callout-success">
                    <h5>기금 사용 우수 사례 공모</h5>
                    <p>2025년 상반기 기금 사용 우수 사례 공모를 진행합니다. 적극적인 참여 부탁드립니다. 접수 기간: 2025-06-01 ~ 2025-06-30</p>
                </div>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
        
        <!-- 회차별 기금액수 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">회차별 기금액수 (<?php echo htmlspecialchars($departmentName); ?>)</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <div class="chart">
                    <canvas id="fundChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                </div>
            </div>
            <!-- /.card-body -->
            <div class="card-footer clearfix">
                <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/fund/allocation-history.php" class="btn btn-sm btn-primary float-right">전체 기금 내역 보기</a>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 기금 송금 내역 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">최근 기금 송금 내역</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body table-responsive p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>회차</th>
                            <th>분과</th>
                            <th>금액 (원)</th>
                            <th>송금일</th>
                            <th>상태</th>
                            <th>담당자</th>
                            <th>연락처</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // 현재 로그인한 분과의 기금 내역만 보여줌
                        foreach ($allocations as $allocation): 
                            if ($allocation['department'] === $departmentName):
                        ?>
                            <tr>
                                <td>제<?php echo $allocation['round']; ?>회</td>
                                <td><?php echo htmlspecialchars($allocation['department']); ?></td>
                                <td><?php echo number_format($allocation['amount']); ?></td>
                                <td><?php echo $allocation['date']; ?></td>
                                <td>
                                    <?php 
                                    $statusBadge = '';
                                    switch ($allocation['status']) {
                                        case 'pending':
                                            $statusBadge = '<span class="badge bg-warning">대기중</span>';
                                            break;
                                        case 'approved':
                                            $statusBadge = '<span class="badge bg-info">승인됨</span>';
                                            break;
                                        case 'transferred':
                                            $statusBadge = '<span class="badge bg-success">송금완료</span>';
                                            break;
                                        case 'completed':
                                            $statusBadge = '<span class="badge bg-primary">처리완료</span>';
                                            break;
                                        default:
                                            $statusBadge = '<span class="badge bg-secondary">알 수 없음</span>';
                                    }
                                    echo $statusBadge;
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($allocation['contact_person']); ?></td>
                                <td><?php echo htmlspecialchars($allocation['contact_phone']); ?></td>
                            </tr>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
            <!-- /.card-body -->
            <div class="card-footer clearfix">
                <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/fund/transfer-history.php" class="btn btn-sm btn-primary float-right">전체 송금 내역 보기</a>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 로그아웃 버튼 -->
        <div class="row">
            <div class="col-md-12 text-right mb-4">
                <a href="?logout=1" class="btn btn-danger">로그아웃</a>
            </div>
        </div>
        <?php endif; ?>
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

<?php if ($loggedIn): ?>
<!-- 차트 스크립트 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 회차별 기금액수 차트
    var fundChartCanvas = document.getElementById('fundChart').getContext('2d');
    var fundChartData = {
        labels: ['제120회', '제121회', '제122회', '제123회', '제124회', '제125회'],
        datasets: [
            {
                label: '기금액수 (억원)',
                backgroundColor: 'rgba(60,141,188,0.9)',
                borderColor: 'rgba(60,141,188,0.8)',
                pointRadius: 3,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: [42.5, 43.2, 44.8, 43.7, 45.6, 46.1]
            }
        ]
    };

    var fundChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            xAxes: [{
                gridLines: {
                    display: false
                }
            }],
            yAxes: [{
                gridLines: {
                    display: false
                },
                ticks: {
                    beginAtZero: false
                }
            }]
        }
    };

    var fundChart = new Chart(fundChartCanvas, {
        type: 'line',
        data: fundChartData,
        options: fundChartOptions
    });
});
</script>
<?php endif; ?>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
