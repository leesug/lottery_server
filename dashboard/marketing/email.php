<?php
/**
 * 이메일 마케팅 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "이메일 마케팅";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

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
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/">홈</a></li>
                    <li class="breadcrumb-item active">마케팅 관리</li>
                    <li class="breadcrumb-item active">이메일 마케팅</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 상단 요약 정보 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>12,345</h3>
                        <p>이번 달 발송된 이메일</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>32.8%</h3>
                        <p>평균 오픈율</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>15.2%</h3>
                        <p>평균 클릭률</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-mouse-pointer"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>3</h3>
                        <p>예약된 캠페인</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 이메일 캠페인 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">이메일 캠페인 목록</h3>
                <div class="card-tools">
                    <a href="email-campaign-add.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> 새 이메일 캠페인
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px">#</th>
                                <th>캠페인명</th>
                                <th>제목</th>
                                <th>대상 그룹</th>
                                <th>발송일</th>
                                <th>발송 수</th>
                                <th>오픈율</th>
                                <th>클릭률</th>
                                <th>상태</th>
                                <th style="width: 150px">액션</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>1</td>
                                <td>5월 추첨 뉴스레터</td>
                                <td>KHUSHI LOTTERY: 5월 대형 추첨 소식</td>
                                <td>모든 고객</td>
                                <td>2025-05-10</td>
                                <td>12,345</td>
                                <td>37.6%</td>
                                <td>18.2%</td>
                                <td><span class="badge badge-success">완료됨</span></td>
                                <td>
                                    <a href="email-campaign-details.php?id=1" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="email-campaign-duplicate.php?id=1" class="btn btn-warning btn-xs">
                                        <i class="fas fa-copy"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>당첨자 안내 이메일</td>
                                <td>축하합니다! KHUSHI LOTTERY 당첨 안내</td>
                                <td>최근 당첨자</td>
                                <td>2025-05-12</td>
                                <td>246</td>
                                <td>92.3%</td>
                                <td>85.4%</td>
                                <td><span class="badge badge-success">완료됨</span></td>
                                <td>
                                    <a href="email-campaign-details.php?id=2" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="email-campaign-duplicate.php?id=2" class="btn btn-warning btn-xs">
                                        <i class="fas fa-copy"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal2">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>신규 복권 출시 안내</td>
                                <td>KHUSHI LOTTERY 신규 복권 출시 및 대형 당첨금 안내</td>
                                <td>활성 고객</td>
                                <td>2025-05-20</td>
                                <td>8,500</td>
                                <td>-</td>
                                <td>-</td>
                                <td><span class="badge badge-warning">예약됨</span></td>
                                <td>
                                    <a href="email-campaign-details.php?id=3" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="email-campaign-edit.php?id=3" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal3">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>비활성 고객 재활성화</td>
                                <td>KHUSHI LOTTERY가 그리워요! 특별 프로모션 코드를 확인하세요</td>
                                <td>휴면 고객</td>
                                <td>2025-05-05</td>
                                <td>3,578</td>
                                <td>22.5%</td>
                                <td>8.7%</td>
                                <td><span class="badge badge-success">완료됨</span></td>
                                <td>
                                    <a href="email-campaign-details.php?id=4" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="email-campaign-duplicate.php?id=4" class="btn btn-warning btn-xs">
                                        <i class="fas fa-copy"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal4">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>월간 복권 소식지</td>
                                <td>KHUSHI LOTTERY 5월 소식: 당첨자 인터뷰 및 새로운 복권 정보</td>
                                <td>구독자</td>
                                <td>2025-05-25</td>
                                <td>7,500</td>
                                <td>-</td>
                                <td>-</td>
                                <td><span class="badge badge-warning">예약됨</span></td>
                                <td>
                                    <a href="email-campaign-details.php?id=5" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="email-campaign-edit.php?id=5" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal5">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                <ul class="pagination pagination-sm m-0 float-right">
                    <li class="page-item"><a class="page-link" href="#">&laquo;</a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">&raquo;</a></li>
                </ul>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 이메일 템플릿 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">이메일 템플릿</h3>
                <div class="card-tools">
                    <a href="email-template-add.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> 새 템플릿
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- 템플릿 카드 -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-primary">
                                <h5 class="card-title m-0">기본 뉴스레터</h5>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0" style="max-height: 200px; overflow: hidden;">
                                <img src="<?php echo SERVER_URL; ?>/assets/img/email-template-1.jpg" alt="뉴스레터 템플릿" class="img-fluid" style="width: 100%; height: auto;">
                            </div>
                            <div class="card-footer">
                                <div class="btn-group btn-block">
                                    <a href="email-template-preview.php?id=1" class="btn btn-default btn-sm">
                                        <i class="fas fa-eye"></i> 미리보기
                                    </a>
                                    <a href="email-template-edit.php?id=1" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> 편집
                                    </a>
                                    <a href="email-campaign-add.php?template=1" class="btn btn-success btn-sm">
                                        <i class="fas fa-envelope"></i> 사용
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-success">
                                <h5 class="card-title m-0">당첨 안내</h5>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0" style="max-height: 200px; overflow: hidden;">
                                <img src="<?php echo SERVER_URL; ?>/assets/img/email-template-2.jpg" alt="당첨 안내 템플릿" class="img-fluid" style="width: 100%; height: auto;">
                            </div>
                            <div class="card-footer">
                                <div class="btn-group btn-block">
                                    <a href="email-template-preview.php?id=2" class="btn btn-default btn-sm">
                                        <i class="fas fa-eye"></i> 미리보기
                                    </a>
                                    <a href="email-template-edit.php?id=2" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> 편집
                                    </a>
                                    <a href="email-campaign-add.php?template=2" class="btn btn-success btn-sm">
                                        <i class="fas fa-envelope"></i> 사용
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h5 class="card-title m-0">프로모션</h5>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0" style="max-height: 200px; overflow: hidden;">
                                <img src="<?php echo SERVER_URL; ?>/assets/img/email-template-3.jpg" alt="프로모션 템플릿" class="img-fluid" style="width: 100%; height: auto;">
                            </div>
                            <div class="card-footer">
                                <div class="btn-group btn-block">
                                    <a href="email-template-preview.php?id=3" class="btn btn-default btn-sm">
                                        <i class="fas fa-eye"></i> 미리보기
                                    </a>
                                    <a href="email-template-edit.php?id=3" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> 편집
                                    </a>
                                    <a href="email-campaign-add.php?template=3" class="btn btn-success btn-sm">
                                        <i class="fas fa-envelope"></i> 사용
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 이메일 통계 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">이메일 성과 분석 (최근 6개월)</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px; display: flex; align-items: center; justify-content: center;">
                            <p>여기에 차트가 표시됩니다.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">대상 그룹별 오픈율 및 클릭률</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px; display: flex; align-items: center; justify-content: center;">
                            <p>여기에 차트가 표시됩니다.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
    </div>
</section>
<!-- /.content -->

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">이메일 캠페인 삭제</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>정말로 이 이메일 캠페인을 삭제하시겠습니까?</p>
                <p><strong>5월 추첨 뉴스레터</strong></p>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger">삭제</button>
            </div>
        </div>
    </div>
</div>

<script>
// 페이지 로드 시 실행
document.addEventListener('DOMContentLoaded', function() {
    console.log('이메일 마케팅 페이지가 로드되었습니다.');
    
    // 이메일 목록 테이블 초기화
    initDataTable();
    
    // 차트 초기화 (실제 구현 시 차트 라이브러리 사용)
    initCharts();
});

// 데이터 테이블 초기화
function initDataTable() {
    console.log('데이터 테이블 초기화');
    // 여기에 실제 데이터 테이블 초기화 코드 추가
}

// 차트 초기화
function initCharts() {
    console.log('차트 초기화');
    // 여기에 실제 차트 초기화 코드 추가
}
</script>

<?php
// 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>
