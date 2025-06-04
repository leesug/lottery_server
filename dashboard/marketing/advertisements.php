<?php
/**
 * 광고 관리 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "광고 관리";
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
                    <li class="breadcrumb-item active">광고 관리</li>
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
                        <h3>5</h3>
                        <p>활성 광고</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-ad"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>₹ 850K</h3>
                        <p>광고 예산 (이번 달)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>12.5%</h3>
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
                        <p>예정된 광고</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 광고 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">광고 목록</h3>
                <div class="card-tools">
                    <a href="advertisement-add.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> 광고 추가
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px">#</th>
                                <th>광고명</th>
                                <th>매체</th>
                                <th>시작일</th>
                                <th>종료일</th>
                                <th>예산</th>
                                <th>상태</th>
                                <th style="width: 150px">액션</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>1</td>
                                <td>페이스북 - 복권 홍보</td>
                                <td><span class="badge badge-primary">소셜 미디어</span></td>
                                <td>2025-05-01</td>
                                <td>2025-06-30</td>
                                <td>₹ 250,000</td>
                                <td><span class="badge badge-success">진행 중</span></td>
                                <td>
                                    <a href="advertisement-details.php?id=1" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="advertisement-edit.php?id=1" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>TV 광고 - 대형 당첨금</td>
                                <td><span class="badge badge-warning">TV</span></td>
                                <td>2025-05-01</td>
                                <td>2025-05-31</td>
                                <td>₹ 500,000</td>
                                <td><span class="badge badge-success">진행 중</span></td>
                                <td>
                                    <a href="advertisement-details.php?id=2" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="advertisement-edit.php?id=2" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal2">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>라디오 광고 - 주간 추첨</td>
                                <td><span class="badge badge-info">라디오</span></td>
                                <td>2025-05-10</td>
                                <td>2025-06-10</td>
                                <td>₹ 150,000</td>
                                <td><span class="badge badge-success">진행 중</span></td>
                                <td>
                                    <a href="advertisement-details.php?id=3" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="advertisement-edit.php?id=3" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal3">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>구글 광고 - 검색 키워드</td>
                                <td><span class="badge badge-primary">온라인</span></td>
                                <td>2025-06-01</td>
                                <td>2025-06-30</td>
                                <td>₹ 180,000</td>
                                <td><span class="badge badge-warning">예정됨</span></td>
                                <td>
                                    <a href="advertisement-details.php?id=4" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="advertisement-edit.php?id=4" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal4">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>신문 광고 - 전면</td>
                                <td><span class="badge badge-secondary">인쇄 매체</span></td>
                                <td>2025-04-01</td>
                                <td>2025-04-30</td>
                                <td>₹ 320,000</td>
                                <td><span class="badge badge-secondary">종료됨</span></td>
                                <td>
                                    <a href="advertisement-details.php?id=5" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="advertisement-edit.php?id=5" class="btn btn-primary btn-xs">
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
        
        <!-- 광고 효과 분석 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">매체별 광고 효과</h3>
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
                        <h3 class="card-title">광고 투자 대비 수익률</h3>
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
                <h4 class="modal-title">광고 삭제</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>정말로 이 광고를 삭제하시겠습니까?</p>
                <p><strong>페이스북 - 복권 홍보</strong></p>
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
    console.log('광고 관리 페이지가 로드되었습니다.');
    
    // 광고 목록 테이블 초기화
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
