<?php
/**
 * 추첨 계획 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = getDbConnection();

// 현재 페이지 정보
$pageTitle = "추첨 계획";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 메시지 초기화
$message = '';
$message_type = '';

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
                    <li class="breadcrumb-item">추첨 관리</li>
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
        <!-- 알림 메시지 -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <!-- 다가오는 추첨 -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">다가오는 추첨</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <a href="execution.php?draw_id=125" class="info-box bg-gradient-info">
                            <span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">일일 복권 #125</span>
                                <span class="info-box-number">오늘 18:00</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 90%"></div>
                                </div>
                                <span class="progress-description">
                                    90% 준비 완료
                                </span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="execution.php?draw_id=123" class="info-box bg-gradient-success">
                            <span class="info-box-icon"><i class="fas fa-calendar-week"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">주간 복권 #123</span>
                                <span class="info-box-number">2025-05-18 15:00</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 70%"></div>
                                </div>
                                <span class="progress-description">
                                    70% 준비 완료
                                </span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 추첨 계획 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">추첨 계획 목록</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addDrawPlanModal">
                        <i class="fas fa-plus"></i> 새 추첨 계획
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>추첨 코드</th>
                            <th>복권 상품</th>
                            <th>추첨 일시</th>
                            <th>예상 당첨 금액</th>
                            <th>판매량</th>
                            <th>준비 상태</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>D-DAILY-125</td>
                            <td>일일 복권</td>
                            <td>2025-05-16 18:00</td>
                            <td>₹ 10,000,000</td>
                            <td>4,250 / 10,000</td>
                            <td>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-green" role="progressbar" style="width: 90%"></div>
                                </div>
                                <small>준비 완료 90%</small>
                            </td>
                            <td>
                                <a href="results.php?draw_id=125" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="execution.php?draw_id=125" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>D-WEEKLY-123</td>
                            <td>주간 복권</td>
                            <td>2025-05-18 15:00</td>
                            <td>₹ 25,000,000</td>
                            <td>12,350 / 50,000</td>
                            <td>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-green" role="progressbar" style="width: 70%"></div>
                                </div>
                                <small>준비 완료 70%</small>
                            </td>
                            <td>
                                <a href="results.php?draw_id=123" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="execution.php?draw_id=123" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>D-DAILY-126</td>
                            <td>일일 복권</td>
                            <td>2025-05-17 18:00</td>
                            <td>₹ 10,000,000</td>
                            <td>1,250 / 10,000</td>
                            <td>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-green" role="progressbar" style="width: 50%"></div>
                                </div>
                                <small>준비 완료 50%</small>
                            </td>
                            <td>
                                <a href="results.php?draw_id=126" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="execution.php?draw_id=126" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>D-DAILY-127</td>
                            <td>일일 복권</td>
                            <td>2025-05-18 18:00</td>
                            <td>₹ 10,000,000</td>
                            <td>250 / 10,000</td>
                            <td>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-green" role="progressbar" style="width: 30%"></div>
                                </div>
                                <small>준비 완료 30%</small>
                            </td>
                            <td>
                                <a href="results.php?draw_id=127" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="execution.php?draw_id=127" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>D-MONTHLY-052</td>
                            <td>월간 특별 복권</td>
                            <td>2025-05-31 15:00</td>
                            <td>₹ 100,000,000</td>
                            <td>8,450 / 100,000</td>
                            <td>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-green" role="progressbar" style="width: 20%"></div>
                                </div>
                                <small>준비 완료 20%</small>
                            </td>
                            <td>
                                <a href="results.php?draw_id=52" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="execution.php?draw_id=52" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <ul class="pagination pagination-sm m-0 float-right">
                    <li class="page-item"><a class="page-link" href="#">&laquo;</a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">&raquo;</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- 추첨 계획 추가 모달 -->
<div class="modal fade" id="addDrawPlanModal" tabindex="-1" role="dialog" aria-labelledby="addDrawPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDrawPlanModalLabel">새 추첨 계획 추가</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="draw_code">추첨 코드</label>
                                <input type="text" class="form-control" id="draw_code" name="draw_code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="product_id">복권 상품</label>
                                <select class="form-control" id="product_id" name="product_id" required>
                                    <option value="">선택하세요</option>
                                    <option value="1">일일 복권</option>
                                    <option value="2">주간 복권</option>
                                    <option value="3">월간 특별 복권</option>
                                    <option value="4">연간 대형 복권</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="draw_date">추첨 날짜</label>
                                <input type="date" class="form-control" id="draw_date" name="draw_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="draw_time">추첨 시간</label>
                                <input type="time" class="form-control" id="draw_time" name="draw_time" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="expected_tickets">예상 판매량</label>
                                <input type="number" class="form-control" id="expected_tickets" name="expected_tickets" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="expected_prize">예상 1등 당첨금</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">₹</span>
                                    </div>
                                    <input type="number" class="form-control" id="expected_prize" name="expected_prize" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="prize_structure">당첨 구조</label>
                        <textarea class="form-control" id="prize_structure" name="prize_structure" rows="4" placeholder="예: 1등: 5,000,000 NPR (1명), 2등: 1,000,000 NPR (5명), ..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="notes">메모</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo md5(uniqid()); ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>