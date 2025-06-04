<?php
/**
 * 은행 접속 모니터링 메인 페이지
 */

// 공통 파일 포함
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "은행 모니터링";
$currentSection = "bank";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = getDBConnection();

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
        <!-- 요약 정보 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>
                            <?php
                            // 당첨금 지급 절차 수 (가상 데이터)
                            echo 12;
                            ?>
                        </h3>
                        <p>당첨금 지급 절차</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-procedures"></i>
                    </div>
                    <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/bank/payment-procedures.php" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>
                            <?php
                            // 당첨금 지급 건수 (가상 데이터)
                            echo 24;
                            ?>
                        </h3>
                        <p>당첨금 지급 건수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/bank/payment-history.php" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>
                            <?php
                            // 인터뷰 답변 건수 (가상 데이터)
                            echo 8;
                            ?>
                        </h3>
                        <p>당첨자 인터뷰</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/bank/interviews.php" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>
                            <?php
                            // 회차별 당첨금액 정보 (가상 데이터)
                            echo 126;
                            ?>
                        </h3>
                        <p>회차별 당첨금액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/bank/prize-amounts.php" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <!-- ./col -->
        </div>
        <!-- /.row -->

        <!-- 당첨금 지급 절차 섹션 -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">당첨금 지급 절차</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">1등 당첨금 지급 절차</h3>
                                    </div>
                                    <!-- /.card-header -->
                                    <div class="card-body">
                                        <ol class="todo-list" data-widget="todo-list">
                                            <li>
                                                <span class="text">1. 당첨 복권 원본 확인</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="바코드, 일련번호, 회차, 발행일자 확인"></i>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="text">2. 당첨자 신분 확인</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="신분증, 주민등록등본 등 제출"></i>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="text">3. 당첨 복권 위조 여부 검증</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="특수 장비로 진위 여부 확인"></i>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="text">4. 개인정보 수집 동의서 작성</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="법적 절차 안내 및 동의"></i>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="text">5. 당첨자 인터뷰 진행</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="당첨 소감 및 사용계획 인터뷰"></i>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="text">6. 세금 안내 및 상담</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="세무사 상담 연계"></i>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="text">7. 당첨금 지급</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="계좌이체 또는 자금 관리 방안 제시"></i>
                                                </div>
                                            </li>
                                        </ol>
                                    </div>
                                    <!-- /.card-body -->
                                </div>
                                <!-- /.card -->
                            </div>
                            <!-- /.col -->
                            
                            <div class="col-md-4">
                                <div class="card card-success">
                                    <div class="card-header">
                                        <h3 class="card-title">2등 당첨금 지급 절차</h3>
                                    </div>
                                    <!-- /.card-header -->
                                    <div class="card-body">
                                        <ol class="todo-list" data-widget="todo-list">
                                            <li>
                                                <span class="text">1. 당첨 복권 원본 확인</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="바코드, 일련번호, 회차, 발행일자 확인"></i>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="text">2. 당첨자 신분 확인</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="신분증 확인"></i>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="text">3. 당첨 복권 위조 여부 검증</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="위조방지 요소 확인"></i>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="text">4. 개인정보 수집 동의서 작성</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="법적 절차 안내 및 동의"></i>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="text">5. 세금 안내</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="원천징수세 등 설명"></i>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="text">6. 당첨금 지급</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="계좌이체 또는 현금 지급"></i>
                                                </div>
                                            </li>
                                        </ol>
                                    </div>
                                    <!-- /.card-body -->
                                </div>
                                <!-- /.card -->
                            </div>
                            <!-- /.col -->
                            
                            <div class="col-md-4">
                                <div class="card card-warning">
                                    <div class="card-header">
                                        <h3 class="card-title">3등 당첨금 지급 절차</h3>
                                    </div>
                                    <!-- /.card-header -->
                                    <div class="card-body">
                                        <ol class="todo-list" data-widget="todo-list">
                                            <li>
                                                <span class="text">1. 당첨 복권 원본 확인</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="바코드, 일련번호, 회차 확인"></i>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="text">2. 당첨자 신분 확인</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="신분증 확인"></i>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="text">3. 당첨 복권 위조 여부 검증</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="간이 검증"></i>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="text">4. 당첨금 지급</span>
                                                <div class="tools">
                                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="현금 또는 계좌이체"></i>
                                                </div>
                                            </li>
                                        </ol>
                                    </div>
                                    <!-- /.card-body -->
                                </div>
                                <!-- /.card -->
                            </div>
                            <!-- /.col -->
                        </div>
                        <!-- /.row -->
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/bank/payment-procedures.php" class="btn btn-sm btn-primary float-right">상세 절차 보기</a>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->

        <div class="row">
            <!-- 위조 방지 체크리스트 섹션 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">복권 위조 방지 체크리스트</h3>
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
                                        <th width="50">#</th>
                                        <th>체크항목</th>
                                        <th width="100">확인 방법</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 - 실제로는 데이터베이스에서 가져와야 함 -->
                                    <tr>
                                        <td>1</td>
                                        <td>복권 용지 재질 확인</td>
                                        <td><span class="badge bg-primary">특수 조명</span></td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>바코드 스캐닝 및 검증</td>
                                        <td><span class="badge bg-success">바코드 리더</span></td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>홀로그램 검증</td>
                                        <td><span class="badge bg-warning">육안 확인</span></td>
                                    </tr>
                                    <tr>
                                        <td>4</td>
                                        <td>마이크로 텍스트 확인</td>
                                        <td><span class="badge bg-info">확대경</span></td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td>UV 반응 확인</td>
                                        <td><span class="badge bg-danger">UV 램프</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/bank/verification-checklist.php" class="btn btn-sm btn-primary float-right">전체 체크리스트</a>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->

            <!-- 당첨자 인터뷰 템플릿 섹션 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">당첨자 인터뷰 템플릿</h3>
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
                                        <th width="50">#</th>
                                        <th>질문</th>
                                        <th width="80">등수</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 - 실제로는 데이터베이스에서 가져와야 함 -->
                                    <tr>
                                        <td>1</td>
                                        <td>복권에 당첨되었을 때 첫 느낌은 어떠셨나요?</td>
                                        <td><span class="badge bg-primary">모든 등수</span></td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>당첨금을 어떻게 사용하실 계획인가요?</td>
                                        <td><span class="badge bg-primary">모든 등수</span></td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>당첨 소식을 가족이나 지인들과 공유하셨나요?</td>
                                        <td><span class="badge bg-success">1, 2등</span></td>
                                    </tr>
                                    <tr>
                                        <td>4</td>
                                        <td>당첨 후 생활에 어떤 변화가 있을 것으로 예상하시나요?</td>
                                        <td><span class="badge bg-success">1, 2등</span></td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td>정기적으로 복권을 구매하셨나요? 특별한 번호 선택 방법이 있으신가요?</td>
                                        <td><span class="badge bg-primary">모든 등수</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/bank/interviews.php" class="btn btn-sm btn-primary float-right">인터뷰 관리</a>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->

        <!-- 회차별 당첨금액 섹션 -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">최근 회차별 당첨금액</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>회차</th>
                                        <th>추첨일</th>
                                        <th>1등 당첨금</th>
                                        <th>1등 당첨자수</th>
                                        <th>2등 당첨금</th>
                                        <th>2등 당첨자수</th>
                                        <th>3등 당첨금</th>
                                        <th>3등 당첨자수</th>
                                        <th>총 판매액</th>
                                        <th>상태</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 - 실제로는 데이터베이스에서 가져와야 함 -->
                                    <tr>
                                        <td>제126회</td>
                                        <td>2025-05-21</td>
                                        <td>2,500,000,000원</td>
                                        <td>4명</td>
                                        <td>50,000,000원</td>
                                        <td>12명</td>
                                        <td>1,500,000원</td>
                                        <td>250명</td>
                                        <td>84,225,000,000원</td>
                                        <td><span class="badge bg-warning">예정</span></td>
                                    </tr>
                                    <tr>
                                        <td>제125회</td>
                                        <td>2025-05-14</td>
                                        <td>3,125,750,000원</td>
                                        <td>3명</td>
                                        <td>52,546,150원</td>
                                        <td>9명</td>
                                        <td>1,486,215원</td>
                                        <td>238명</td>
                                        <td>85,456,324,000원</td>
                                        <td><span class="badge bg-success">지급 중</span></td>
                                    </tr>
                                    <tr>
                                        <td>제124회</td>
                                        <td>2025-05-07</td>
                                        <td>2,845,234,500원</td>
                                        <td>5명</td>
                                        <td>48,256,782원</td>
                                        <td>15명</td>
                                        <td>1,425,625원</td>
                                        <td>242명</td>
                                        <td>82,345,678,000원</td>
                                        <td><span class="badge bg-success">지급 중</span></td>
                                    </tr>
                                    <tr>
                                        <td>제123회</td>
                                        <td>2025-04-30</td>
                                        <td>2,758,654,250원</td>
                                        <td>4명</td>
                                        <td>46,754,215원</td>
                                        <td>12명</td>
                                        <td>1,458,754원</td>
                                        <td>225명</td>
                                        <td>80,254,687,000원</td>
                                        <td><span class="badge bg-primary">지급 완료</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/bank/prize-amounts.php" class="btn btn-sm btn-primary float-right">전체 회차 보기</a>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
