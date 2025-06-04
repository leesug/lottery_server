<?php
/**
 * 캠페인 관리 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "캠페인 관리";
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
                    <li class="breadcrumb-item active">캠페인 관리</li>
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
                        <h3>0</h3>
                        <p>전체 캠페인</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>0</h3>
                        <p>활성 캠페인</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>0</h3>
                        <p>예정된 캠페인</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>0%</h3>
                        <p>평균 참여율</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 필터 섹션 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">캠페인 필터</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="campaignFilterForm" class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="campaignStatus">상태</label>
                            <select id="campaignStatus" class="form-control">
                                <option value="all">모든 상태</option>
                                <option value="active">활성</option>
                                <option value="upcoming">예정됨</option>
                                <option value="completed">완료됨</option>
                                <option value="cancelled">취소됨</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="campaignType">유형</label>
                            <select id="campaignType" class="form-control">
                                <option value="all">모든 유형</option>
                                <option value="email">이메일</option>
                                <option value="sms">SMS</option>
                                <option value="push">푸시 알림</option>
                                <option value="social">소셜 미디어</option>
                                <option value="print">인쇄물</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="campaignDateRange">기간</label>
                            <select id="campaignDateRange" class="form-control">
                                <option value="all">모든 기간</option>
                                <option value="current">현재 진행 중</option>
                                <option value="upcoming">다가오는 캠페인</option>
                                <option value="past">지난 캠페인</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group pt-4 mt-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> 필터 적용
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- 캠페인 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">캠페인 목록</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#newCampaignModal">
                        <i class="fas fa-plus"></i> 새 캠페인 생성
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>캠페인명</th>
                                <th>유형</th>
                                <th>시작일</th>
                                <th>종료일</th>
                                <th>타겟 고객</th>
                                <th>상태</th>
                                <th>성과</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="9" class="text-center">등록된 캠페인이 없습니다.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<!-- 새 캠페인 생성 모달 -->
<div class="modal fade" id="newCampaignModal" tabindex="-1" aria-labelledby="newCampaignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newCampaignModalLabel">새 캠페인 생성</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="createCampaignForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="campaignName">캠페인명 <span class="text-danger">*</span></label>
                                <input type="text" id="campaignName" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="campaignTypeSelect">캠페인 유형 <span class="text-danger">*</span></label>
                                <select id="campaignTypeSelect" class="form-control" required>
                                    <option value="">유형 선택</option>
                                    <option value="email">이메일</option>
                                    <option value="sms">SMS</option>
                                    <option value="push">푸시 알림</option>
                                    <option value="social">소셜 미디어</option>
                                    <option value="print">인쇄물</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="startDate">시작일 <span class="text-danger">*</span></label>
                                <input type="date" id="startDate" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="endDate">종료일 <span class="text-danger">*</span></label>
                                <input type="date" id="endDate" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="targetAudience">타겟 고객 <span class="text-danger">*</span></label>
                        <select id="targetAudience" class="form-control" required>
                            <option value="">타겟 선택</option>
                            <option value="all">모든 고객</option>
                            <option value="active">활성 고객</option>
                            <option value="inactive">비활성 고객</option>
                            <option value="new">신규 고객</option>
                            <option value="frequent">자주 구매하는 고객</option>
                            <option value="high_value">고액 구매 고객</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="campaignDescription">설명</label>
                        <textarea id="campaignDescription" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="campaignBudget">예산</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">₹</span>
                            </div>
                            <input type="number" id="campaignBudget" class="form-control" min="0" step="0.01">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="campaignGoals">목표</label>
                        <textarea id="campaignGoals" class="form-control" rows="2"></textarea>
                        <small class="text-muted">예: 티켓 판매 증가, 신규 고객 유치, 브랜드 인지도 향상 등</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="createCampaignBtn">캠페인 생성</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 페이지 로드 시 콘솔에 디버깅 메시지
    console.log('캠페인 관리 페이지 로드됨');
    
    // 캠페인 필터링 폼 제출 이벤트
    const campaignFilterForm = document.getElementById('campaignFilterForm');
    if (campaignFilterForm) {
        campaignFilterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const status = document.getElementById('campaignStatus').value;
            const type = document.getElementById('campaignType').value;
            const dateRange = document.getElementById('campaignDateRange').value;
            
            console.log('캠페인 필터 적용:', status, type, dateRange);
            // 여기에 필터 적용 로직 추가
        });
    }
    
    // 캠페인 생성 버튼 클릭 이벤트
    const createCampaignBtn = document.getElementById('createCampaignBtn');
    if (createCampaignBtn) {
        createCampaignBtn.addEventListener('click', function() {
            // 캠페인 생성 폼 유효성 검사
            const form = document.getElementById('createCampaignForm');
            const name = document.getElementById('campaignName').value;
            const type = document.getElementById('campaignTypeSelect').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const targetAudience = document.getElementById('targetAudience').value;
            
            if (!name || !type || !startDate || !endDate || !targetAudience) {
                alert('모든 필수 필드를 입력해주세요.');
                return;
            }
            
            console.log('캠페인 생성 요청:', {name, type, startDate, endDate, targetAudience});
            // 여기에 캠페인 생성 AJAX 요청 추가
            
            // 모달 닫기
            $('#newCampaignModal').modal('hide');
        });
    }
    
    // 날짜 필드 기본값 설정
    const startDateField = document.getElementById('startDate');
    const endDateField = document.getElementById('endDate');
    
    if (startDateField && endDateField) {
        // 오늘 날짜를 기본값으로 설정
        const today = new Date();
        const nextMonth = new Date();
        nextMonth.setMonth(today.getMonth() + 1);
        
        startDateField.value = today.toISOString().split('T')[0];
        endDateField.value = nextMonth.toISOString().split('T')[0];
    }
});
</script>

<?php
// 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>
