<?php
/**
 * 추첨 방송국 메인 페이지
 */

// 공통 파일 포함
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "추첨 방송국 모니터링";
$currentSection = "broadcaster";
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
                            // 체크리스트 완료 항목 개수 (가상 데이터)
                            echo 15;
                            ?>
                        </h3>
                        <p>체크리스트 완료 항목</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/broadcaster/checklist.php" class="small-box-footer">
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
                            // 예정된 추첨 일정 개수 (가상 데이터)
                            echo 3;
                            ?>
                        </h3>
                        <p>예정된 추첨 일정</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/broadcaster/schedule.php" class="small-box-footer">
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
                            // 추첨 담당자 수 (가상 데이터)
                            echo 5;
                            ?>
                        </h3>
                        <p>추첨 담당자</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/broadcaster/managers.php" class="small-box-footer">
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
                            // 참관인 신청 수 (가상 데이터)
                            echo 12;
                            ?>
                        </h3>
                        <p>참관인 신청</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/broadcaster/observers.php" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <!-- ./col -->
        </div>
        <!-- /.row -->

        <!-- 추첨 체크리스트 섹션 -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">준비사항 체크리스트</h3>
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
                                        <th style="width: 10px">#</th>
                                        <th>항목명</th>
                                        <th>상태</th>
                                        <th style="width: 40%">설명</th>
                                        <th style="width: 120px">완료일</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 - 실제로는 데이터베이스에서 가져와야 함 -->
                                    <tr>
                                        <td>1.</td>
                                        <td>추첨기 점검</td>
                                        <td>
                                            <span class="badge bg-success">완료</span>
                                        </td>
                                        <td>추첨기 장비 정상 작동 확인 및 테스트 완료</td>
                                        <td>2025-05-15</td>
                                    </tr>
                                    <tr>
                                        <td>2.</td>
                                        <td>카메라 세팅</td>
                                        <td>
                                            <span class="badge bg-success">완료</span>
                                        </td>
                                        <td>추첨 장면 촬영을 위한 카메라 설치 및 테스트</td>
                                        <td>2025-05-16</td>
                                    </tr>
                                    <tr>
                                        <td>3.</td>
                                        <td>방송 송출 테스트</td>
                                        <td>
                                            <span class="badge bg-warning">진행중</span>
                                        </td>
                                        <td>실시간 방송 송출 테스트 및 네트워크 연결 확인</td>
                                        <td>-</td>
                                    </tr>
                                    <tr>
                                        <td>4.</td>
                                        <td>참관인 배치</td>
                                        <td>
                                            <span class="badge bg-danger">미완료</span>
                                        </td>
                                        <td>추첨 참관인 좌석 배치 및 안내 자료 준비</td>
                                        <td>-</td>
                                    </tr>
                                    <tr>
                                        <td>5.</td>
                                        <td>공증인 섭외</td>
                                        <td>
                                            <span class="badge bg-success">완료</span>
                                        </td>
                                        <td>추첨 공정성 확보를 위한 공증인 섭외 완료</td>
                                        <td>2025-05-12</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/broadcaster/checklist.php" class="btn btn-sm btn-primary float-right">전체 체크리스트 보기</a>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->

        <div class="row">
            <!-- 추첨 일정 섹션 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">추첨 일정</h3>
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
                                        <th>일정</th>
                                        <th>날짜/시간</th>
                                        <th>장소</th>
                                        <th>상태</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 - 실제로는 데이터베이스에서 가져와야 함 -->
                                    <tr>
                                        <td>126회 추첨 리허설</td>
                                        <td>2025-05-20 14:00</td>
                                        <td>스튜디오 A</td>
                                        <td><span class="badge bg-info">예정</span></td>
                                    </tr>
                                    <tr>
                                        <td>126회 추첨 방송</td>
                                        <td>2025-05-21 20:00</td>
                                        <td>스튜디오 A</td>
                                        <td><span class="badge bg-info">예정</span></td>
                                    </tr>
                                    <tr>
                                        <td>127회 사전 미팅</td>
                                        <td>2025-05-26 15:00</td>
                                        <td>회의실 B</td>
                                        <td><span class="badge bg-info">예정</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/broadcaster/schedule.php" class="btn btn-sm btn-primary float-right">모든 일정 보기</a>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->

            <!-- 추첨 담당자 섹션 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">추첨 담당자</h3>
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
                                        <th>이름</th>
                                        <th>직책</th>
                                        <th>소속</th>
                                        <th>연락처</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 - 실제로는 데이터베이스에서 가져와야 함 -->
                                    <tr>
                                        <td>김상혁</td>
                                        <td>제작 책임자</td>
                                        <td>KBS</td>
                                        <td>010-1234-5678</td>
                                    </tr>
                                    <tr>
                                        <td>이민우</td>
                                        <td>추첨 감독관</td>
                                        <td>로또 위원회</td>
                                        <td>010-9876-5432</td>
                                    </tr>
                                    <tr>
                                        <td>박지은</td>
                                        <td>방송 PD</td>
                                        <td>KBS</td>
                                        <td>010-2222-3333</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/broadcaster/managers.php" class="btn btn-sm btn-primary float-right">모든 담당자 보기</a>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->

        <!-- 참관인 섹션 -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">참관인 모집 현황</h3>
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
                                        <th>이름</th>
                                        <th>소속</th>
                                        <th>직책</th>
                                        <th>연락처</th>
                                        <th>신청일</th>
                                        <th>상태</th>
                                        <th>관리</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 샘플 데이터 - 실제로는 데이터베이스에서 가져와야 함 -->
                                    <tr>
                                        <td>최재현</td>
                                        <td>시민단체</td>
                                        <td>감사위원</td>
                                        <td>010-5555-6666</td>
                                        <td>2025-05-10</td>
                                        <td><span class="badge bg-success">승인</span></td>
                                        <td>
                                            <a href="#" class="btn btn-xs btn-info">상세</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>장현석</td>
                                        <td>로또 애호가 협회</td>
                                        <td>회원</td>
                                        <td>010-7777-8888</td>
                                        <td>2025-05-12</td>
                                        <td><span class="badge bg-warning">검토중</span></td>
                                        <td>
                                            <a href="#" class="btn btn-xs btn-info">상세</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>정수미</td>
                                        <td>방송심의위원회</td>
                                        <td>위원</td>
                                        <td>010-3333-4444</td>
                                        <td>2025-05-15</td>
                                        <td><span class="badge bg-danger">반려</span></td>
                                        <td>
                                            <a href="#" class="btn btn-xs btn-info">상세</a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/broadcaster/observers.php" class="btn btn-sm btn-primary float-right">전체 참관인 목록</a>
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
