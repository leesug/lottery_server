<?php
// 등급별 레버리지 설정
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 로그인 체크 및 권한 확인
checkLogin();
checkPermission('system_settings');

// 페이지 제목
$pageTitle = "등급별 판매한도 설정";
$currentSection = "system";
$currentPage = "deposit-grade-settings.php";

// 데이터베이스 연결
$conn = get_db_connection();

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = false;
    
    try {
        $conn->beginTransaction();
        
        // 각 등급별 설정 업데이트
        foreach (['S', 'A', 'B', 'C', 'D'] as $grade) {
            $leverage_rate = isset($_POST['leverage_' . $grade]) ? floatval($_POST['leverage_' . $grade]) : 1.0;
            $min_monthly_sales = isset($_POST['min_sales_' . $grade]) ? floatval($_POST['min_sales_' . $grade]) : 0;
            $min_deposit_amount = isset($_POST['min_deposit_' . $grade]) ? floatval($_POST['min_deposit_' . $grade]) : 200000;
            $benefits = isset($_POST['benefits_' . $grade]) ? trim($_POST['benefits_' . $grade]) : '';
            
            // 유효성 검증
            if ($leverage_rate < 0.5 || $leverage_rate > 3.0) {
                $errors[] = "{$grade}등급의 레버리지 비율은 0.5~3.0 사이여야 합니다.";
                continue;
            }
            
            $updateQuery = "
                UPDATE store_grade_leverage 
                SET leverage_rate = ?,
                    min_monthly_sales = ?,
                    min_deposit_amount = ?,
                    benefits = ?
                WHERE grade = ?
            ";
            $stmt = $conn->prepare($updateQuery);
            $stmt->execute([
                $leverage_rate,
                $min_monthly_sales,
                $min_deposit_amount,
                $benefits,
                $grade
            ]);
        }
        
        if (empty($errors)) {
            // 모든 판매점의 판매한도 재계산
            $recalcQuery = "
                UPDATE store_deposits sd
                INNER JOIN store_grade_leverage sgl ON sd.store_grade = sgl.grade
                SET sd.leverage_rate = sgl.leverage_rate,
                    sd.sales_limit = sd.sales_deposit * 1.05 * sgl.leverage_rate
            ";
            $conn->exec($recalcQuery);
            
            $conn->commit();
            $success = true;
            $_SESSION['success_message'] = "등급별 설정이 저장되었습니다. 모든 판매점의 판매한도가 재계산되었습니다.";
        } else {
            $conn->rollBack();
        }
        
    } catch (Exception $e) {
        $conn->rollBack();
        $errors[] = "처리 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 현재 설정 조회
$settingsQuery = "
    SELECT * FROM store_grade_leverage 
    ORDER BY 
        CASE grade 
            WHEN 'S' THEN 1 
            WHEN 'A' THEN 2 
            WHEN 'B' THEN 3 
            WHEN 'C' THEN 4 
            WHEN 'D' THEN 5 
        END
";
$settings = $conn->query($settingsQuery)->fetchAll(PDO::FETCH_KEY_PAIR);

// 등급별 판매점 통계
$statsQuery = "
    SELECT 
        sd.store_grade,
        COUNT(*) as store_count,
        AVG(sd.sales_deposit) as avg_deposit,
        AVG(sd.sales_limit) as avg_limit,
        AVG(sd.usage_percentage) as avg_usage
    FROM store_deposits sd
    GROUP BY sd.store_grade
";
$gradeStats = $conn->query($statsQuery)->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);

include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- 컨텐츠 시작 -->
<div class="container-fluid">
    <!-- 페이지 헤더 -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0"><?php echo $pageTitle; ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
                    <li class="breadcrumb-item"><a href="/dashboard/system">시스템 설정</a></li>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- 설명 카드 -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <h5 class="card-title">판매한도 계산 방식</h5>
            <p class="card-text">
                판매한도 = 판매보증금 × 1.05 × <strong>등급별 레버리지</strong><br>
                예) B등급(레버리지 1.0) 판매보증금 200,000원 → 판매한도 210,000원<br>
                예) A등급(레버리지 1.1) 판매보증금 200,000원 → 판매한도 231,000원
            </p>
        </div>
    </div>

    <!-- 등급별 설정 폼 -->
    <form method="post" action="">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">등급별 레버리지 설정</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="10%">등급</th>
                                <th width="15%">레버리지</th>
                                <th width="20%">최소 월매출</th>
                                <th width="20%">최소 예치금</th>
                                <th width="25%">혜택 설명</th>
                                <th width="10%">현황</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grades = [
                                'S' => ['color' => 'primary', 'name' => 'S등급 (최우수)'],
                                'A' => ['color' => 'success', 'name' => 'A등급 (우수)'],
                                'B' => ['color' => 'info', 'name' => 'B등급 (일반)'],
                                'C' => ['color' => 'warning', 'name' => 'C등급 (신규)'],
                                'D' => ['color' => 'danger', 'name' => 'D등급 (주의)']
                            ];
                            
                            foreach ($grades as $grade => $info): 
                                $setting = null;
                                foreach ($settings as $s) {
                                    if ($s['grade'] == $grade) {
                                        $setting = $s;
                                        break;
                                    }
                                }
                                $stats = isset($gradeStats[$grade]) ? $gradeStats[$grade][0] : null;
                            ?>
                            <tr>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo $info['color']; ?> fs-6">
                                        <?php echo $grade; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="number" name="leverage_<?php echo $grade; ?>" 
                                               class="form-control" 
                                               value="<?php echo $setting['leverage_rate'] ?? 1.0; ?>"
                                               min="0.5" max="3.0" step="0.1" required>
                                        <span class="input-group-text">배</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">₩</span>
                                        <input type="number" name="min_sales_<?php echo $grade; ?>" 
                                               class="form-control" 
                                               value="<?php echo $setting['min_monthly_sales'] ?? 0; ?>"
                                               min="0" step="100000">
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">₩</span>
                                        <input type="number" name="min_deposit_<?php echo $grade; ?>" 
                                               class="form-control" 
                                               value="<?php echo $setting['min_deposit_amount'] ?? 200000; ?>"
                                               min="0" step="10000">
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="benefits_<?php echo $grade; ?>" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($setting['benefits'] ?? ''); ?>"
                                           placeholder="<?php echo $info['name']; ?> 혜택">
                                </td>
                                <td class="text-center">
                                    <?php if ($stats): ?>
                                        <small>
                                            <?php echo $stats['store_count']; ?>개점<br>
                                            사용률 <?php echo number_format($stats['avg_usage'], 1); ?>%
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">없음</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 설정 저장
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- 등급별 현황 -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">등급별 판매점 현황</h6>
        </div>
        <div class="card-body">
            <canvas id="gradeChart" height="100"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 등급별 차트
const ctx = document.getElementById('gradeChart').getContext('2d');
const gradeData = <?php echo json_encode($gradeStats); ?>;

const labels = ['S등급', 'A등급', 'B등급', 'C등급', 'D등급'];
const data = {
    labels: labels,
    datasets: [{
        label: '판매점 수',
        data: [
            gradeData['S'] ? gradeData['S'][0]['store_count'] : 0,
            gradeData['A'] ? gradeData['A'][0]['store_count'] : 0,
            gradeData['B'] ? gradeData['B'][0]['store_count'] : 0,
            gradeData['C'] ? gradeData['C'][0]['store_count'] : 0,
            gradeData['D'] ? gradeData['D'][0]['store_count'] : 0
        ],
        backgroundColor: [
            'rgba(13, 110, 253, 0.5)',
            'rgba(25, 135, 84, 0.5)',
            'rgba(13, 202, 240, 0.5)',
            'rgba(255, 193, 7, 0.5)',
            'rgba(220, 53, 69, 0.5)'
        ],
        borderColor: [
            'rgb(13, 110, 253)',
            'rgb(25, 135, 84)',
            'rgb(13, 202, 240)',
            'rgb(255, 193, 7)',
            'rgb(220, 53, 69)'
        ],
        borderWidth: 1
    }]
};

new Chart(ctx, {
    type: 'bar',
    data: data,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
