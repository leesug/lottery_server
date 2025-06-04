<?php
/**
 * 사용자 관리 페이지
 */

// 오류 표시 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 헤더 설정
header('Content-Type: text/html; charset=utf-8');

// 출력 버퍼링 시작
ob_start();

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "사용자 관리";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 검색 파라미터
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? $_GET['role'] : 'all';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

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
                    <li class="breadcrumb-item active">시스템 관리</li>
                    <li class="breadcrumb-item active">사용자 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 상단 정보 카드 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>82</h3>
                        <p>총 사용자 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>68</h3>
                        <p>활성 사용자</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>5</h3>
                        <p>관리자</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>14</h3>
                        <p>비활성 사용자</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 검색 및 필터 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">사용자 검색 및 필터</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="searchForm" method="GET" action="">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="search">검색어</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="이름, 이메일 또는 ID 검색" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="role">역할</label>
                                <select class="form-control" id="role" name="role">
                                    <option value="all" <?php if($role == 'all') echo 'selected'; ?>>모든 역할</option>
                                    <option value="admin" <?php if($role == 'admin') echo 'selected'; ?>>관리자</option>
                                    <option value="manager" <?php if($role == 'manager') echo 'selected'; ?>>매니저</option>
                                    <option value="operator" <?php if($role == 'operator') echo 'selected'; ?>>운영자</option>
                                    <option value="staff" <?php if($role == 'staff') echo 'selected'; ?>>일반 직원</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="status">상태</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="all" <?php if($status == 'all') echo 'selected'; ?>>모든 상태</option>
                                    <option value="active" <?php if($status == 'active') echo 'selected'; ?>>활성</option>
                                    <option value="inactive" <?php if($status == 'inactive') echo 'selected'; ?>>비활성</option>
                                    <option value="locked" <?php if($status == 'locked') echo 'selected'; ?>>잠금</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-group mb-0 w-100">
                                <button type="submit" class="btn btn-primary btn-block">검색</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 사용자 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">사용자 목록</h3>
                <div class="card-tools">
                    <a href="user-add.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-user-plus"></i> 사용자 추가
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px">ID</th>
                                <th>이름</th>
                                <th>이메일</th>
                                <th>역할</th>
                                <th>부서</th>
                                <th>최근 로그인</th>
                                <th>상태</th>
                                <th style="width: 150px">액션</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>1</td>
                                <td>라젠드라 프라사드</td>
                                <td>rajendra.prasad@khushilottery.com</td>
                                <td><span class="badge badge-danger">관리자</span></td>
                                <td>경영진</td>
                                <td>2025-05-16 09:45</td>
                                <td><span class="badge badge-success">활성</span></td>
                                <td>
                                    <a href="user-view.php?id=1" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="user-edit.php?id=1" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>수닐 바타차르야</td>
                                <td>sunil.bhattacharya@khushilottery.com</td>
                                <td><span class="badge badge-danger">관리자</span></td>
                                <td>IT</td>
                                <td>2025-05-16 10:15</td>
                                <td><span class="badge badge-success">활성</span></td>
                                <td>
                                    <a href="user-view.php?id=2" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="user-edit.php?id=2" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal2">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>프리티 샤르마</td>
                                <td>preeti.sharma@khushilottery.com</td>
                                <td><span class="badge badge-warning">매니저</span></td>
                                <td>영업</td>
                                <td>2025-05-15 16:30</td>
                                <td><span class="badge badge-success">활성</span></td>
                                <td>
                                    <a href="user-view.php?id=3" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="user-edit.php?id=3" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal3">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>아니쉬 바뜨라이</td>
                                <td>anish.bhattarai@khushilottery.com</td>
                                <td><span class="badge badge-warning">매니저</span></td>
                                <td>마케팅</td>
                                <td>2025-05-16 08:45</td>
                                <td><span class="badge badge-success">활성</span></td>
                                <td>
                                    <a href="user-view.php?id=4" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="user-edit.php?id=4" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal4">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>카말 구룽</td>
                                <td>kamal.gurung@khushilottery.com</td>
                                <td><span class="badge badge-info">운영자</span></td>
                                <td>IT</td>
                                <td>2025-05-16 09:15</td>
                                <td><span class="badge badge-success">활성</span></td>
                                <td>
                                    <a href="user-view.php?id=5" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="user-edit.php?id=5" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal5">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>마야 타마응</td>
                                <td>maya.tamang@khushilottery.com</td>
                                <td><span class="badge badge-secondary">일반 직원</span></td>
                                <td>고객 지원</td>
                                <td>2025-05-16 08:30</td>
                                <td><span class="badge badge-success">활성</span></td>
                                <td>
                                    <a href="user-view.php?id=6" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="user-edit.php?id=6" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal6">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>라빈드라 싱</td>
                                <td>ravindra.singh@khushilottery.com</td>
                                <td><span class="badge badge-secondary">일반 직원</span></td>
                                <td>판매</td>
                                <td>2025-05-14 14:20</td>
                                <td><span class="badge badge-warning">잠금</span></td>
                                <td>
                                    <a href="user-view.php?id=7" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="user-edit.php?id=7" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal7">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>싯탈 프라단</td>
                                <td>sital.pradhan@khushilottery.com</td>
                                <td><span class="badge badge-secondary">일반 직원</span></td>
                                <td>회계</td>
                                <td>2025-04-30 10:45</td>
                                <td><span class="badge badge-danger">비활성</span></td>
                                <td>
                                    <a href="user-view.php?id=8" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="user-edit.php?id=8" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal8">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>사미르 카르키</td>
                                <td>samir.karki@khushilottery.com</td>
                                <td><span class="badge badge-info">운영자</span></td>
                                <td>IT</td>
                                <td>2025-05-16 11:20</td>
                                <td><span class="badge badge-success">활성</span></td>
                                <td>
                                    <a href="user-view.php?id=9" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="user-edit.php?id=9" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal9">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>지사 추딩</td>
                                <td>jeesha.chhuding@khushilottery.com</td>
                                <td><span class="badge badge-secondary">일반 직원</span></td>
                                <td>고객 지원</td>
                                <td>2025-05-15 12:35</td>
                                <td><span class="badge badge-success">활성</span></td>
                                <td>
                                    <a href="user-view.php?id=10" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="user-edit.php?id=10" class="btn btn-primary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal10">
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
        
        <!-- 역할별 사용자 통계 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">역할별 사용자 분포</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px; display: flex; align-items: center; justify-content: center;">
                            <span>역할별 사용자 분포 차트 (개발 중)</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">부서별 사용자 분포</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px; display: flex; align-items: center; justify-content: center;">
                            <span>부서별 사용자 분포 차트 (개발 중)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 최근 활동 로그 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">최근 사용자 활동 로그</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px">ID</th>
                                <th>사용자</th>
                                <th>활동 유형</th>
                                <th>IP 주소</th>
                                <th>브라우저</th>
                                <th>설명</th>
                                <th>일시</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>1024</td>
                                <td>수닐 바타차르야</td>
                                <td><span class="badge badge-success">로그인</span></td>
                                <td>192.168.1.105</td>
                                <td>Chrome 120.0.6099.129</td>
                                <td>성공적인 로그인</td>
                                <td>2025-05-16 10:15:32</td>
                            </tr>
                            <tr>
                                <td>1023</td>
                                <td>라젠드라 프라사드</td>
                                <td><span class="badge badge-success">로그인</span></td>
                                <td>192.168.1.102</td>
                                <td>Chrome 120.0.6099.129</td>
                                <td>성공적인 로그인</td>
                                <td>2025-05-16 09:45:18</td>
                            </tr>
                            <tr>
                                <td>1022</td>
                                <td>아니쉬 바뜨라이</td>
                                <td><span class="badge badge-primary">사용자 수정</span></td>
                                <td>192.168.1.110</td>
                                <td>Firefox 123.0</td>
                                <td>사용자 ID 8 정보 업데이트</td>
                                <td>2025-05-16 09:30:45</td>
                            </tr>
                            <tr>
                                <td>1021</td>
                                <td>사미르 카르키</td>
                                <td><span class="badge badge-success">로그인</span></td>
                                <td>192.168.1.115</td>
                                <td>Chrome 120.0.6099.129</td>
                                <td>성공적인 로그인</td>
                                <td>2025-05-16 09:20:12</td>
                            </tr>
                            <tr>
                                <td>1020</td>
                                <td>라빈드라 싱</td>
                                <td><span class="badge badge-danger">로그인 실패</span></td>
                                <td>192.168.1.120</td>
                                <td>Edge 120.0.2210.133</td>
                                <td>잘못된 비밀번호 (3회 시도 실패 - 계정 잠금)</td>
                                <td>2025-05-16 09:15:06</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                <a href="user-activity-logs.php" class="btn btn-sm btn-info float-right">모든 활동 로그 보기</a>
            </div>
        </div>
        <!-- /.card -->
    </div>
</section>
<!-- /.content -->

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">사용자 삭제</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>정말로 이 사용자를 삭제하시겠습니까?</p>
                <p><strong>라젠드라 프라사드 (rajendra.prasad@khushilottery.com)</strong></p>
                <p class="text-danger">주의: 이 작업은 되돌릴 수 없습니다.</p>
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
    console.log('사용자 관리 페이지가 로드되었습니다.');
    
    // 데이터 테이블 초기화 (실제 구현 시 데이터 테이블 라이브러리 사용)
    initDataTable();
    
    // 차트 초기화 (실제 구현 시 차트 라이브러리 사용)
    initCharts();
    
    // 모달 관련 이벤트
    setupModalEvents();
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

// 모달 이벤트 설정
function setupModalEvents() {
    console.log('모달 이벤트 설정');
    // 삭제 버튼 클릭 시 처리
    const deleteButtons = document.querySelectorAll('.modal-footer .btn-danger');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            // 실제 삭제 요청 대신 알림 표시 (데모 용)
            alert('삭제 기능은 현재 개발 중입니다.');
            // 모달 닫기
            $(this).closest('.modal').modal('hide');
        });
    });
}

// 사용자 상태 변경 함수
function changeUserStatus(userId, newStatus) {
    console.log(`사용자 ID ${userId}의 상태를 ${newStatus}로 변경합니다.`);
    // 여기에 실제 상태 변경 API 호출 코드 추가
    alert(`사용자 상태 변경 기능은 현재 개발 중입니다. (ID: ${userId}, 상태: ${newStatus})`);
}
</script>

<?php
// 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';

// 출력 버퍼 플러시
ob_end_flush();
?>
