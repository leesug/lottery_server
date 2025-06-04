<?php
/**
 * 프로모션 관리 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "프로모션 관리";
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
                    <li class="breadcrumb-item active">프로모션 관리</li>
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
                        <h3>7</h3>
                        <p>진행 중인 프로모션</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-gift"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>15</h3>
                        <p>완료된 프로모션</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>3</h3>
                        <p>예정된 프로모션</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>₹ 1.5M</h3>
                        <p>할인 금액 (이번 달)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-tags"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 프로모션 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">프로모션 목록</h3>
                <div class="card-tools">
                    <a href="promotion-add.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> 프로모션 추가
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px">#</th>
                                <th>프로모션명</th>
                                <th>유형</th>
                                <th>시작일</th>
                                <th>종료일</th>
                                <th>할인율/금액</th>
                                <th>상태</th>
                                <th style="width: 150px">액션</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>1</td>
                                <td>여름 특별 프로모션</td>
                                <td><span class="badge badge-primary">복권 할인</span></td>
                                <td>2025-05-01</td>
                                <td>2025-06-30</td>
                                <td>10%</td>
                                <td><span class="badge badge-success">진행 중</span></td>
                                <td>
                                    <a href="promotion-details.php?id=1" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="promotion-edit.php?id=1" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>신규 가입자 할인</td>
                                <td><span class="badge badge-warning">신규 고객</span></td>
                                <td>2025-05-01</td>
                                <td>2025-07-31</td>
                                <td>₹ 500</td>
                                <td><span class="badge badge-success">진행 중</span></td>
                                <td>
                                    <a href="promotion-details.php?id=2" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="promotion-edit.php?id=2" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal2">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>추첨일 특별 이벤트</td>
                                <td><span class="badge badge-info">이벤트</span></td>
                                <td>2025-05-20</td>
                                <td>2025-05-20</td>
                                <td>5%</td>
                                <td><span class="badge badge-warning">예정됨</span></td>
                                <td>
                                    <a href="promotion-details.php?id=3" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="promotion-edit.php?id=3" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal3">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>대량 구매 할인</td>
                                <td><span class="badge badge-primary">복권 할인</span></td>
                                <td>2025-05-10</td>
                                <td>2025-06-10</td>
                                <td>15%</td>
                                <td><span class="badge badge-success">진행 중</span></td>
                                <td>
                                    <a href="promotion-details.php?id=4" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="promotion-edit.php?id=4" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal4">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>명절 특별 프로모션</td>
                                <td><span class="badge badge-info">이벤트</span></td>
                                <td>2025-04-01</td>
                                <td>2025-04-15</td>
                                <td>20%</td>
                                <td><span class="badge badge-secondary">종료됨</span></td>
                                <td>
                                    <a href="promotion-details.php?id=5" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="promotion-edit.php?id=5" class="btn btn-primary btn-xs">
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
        
        <!-- 프로모션 통계 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">프로모션 유형별 분포</h3>
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
                        <h3 class="card-title">프로모션 효과 분석</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px; display: flex; align-items: center; justify-content: center;">
                            <p>여기에 차트가 표시됩니다.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">프로모션 삭제</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>정말로 이 프로모션을 삭제하시겠습니까?</p>
                <p><strong>여름 특별 프로모션</strong></p>
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
    console.log('프로모션 관리 페이지가 로드되었습니다.');
    
    // 프로모션 목록 테이블 초기화
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
