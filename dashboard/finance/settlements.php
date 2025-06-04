<?php
/**
 * 정산 관리 페이지
 */

// 세션 및 필수 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 접근 권한 확인
check_permission('finance_management');

// 페이지 제목 설정
$pageTitle = '정산 관리';
$currentSection = 'finance';
$currentPage = basename($_SERVER['PHP_SELF']);

// 헤더 인클루드
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard">대시보드</a></li>
        <li class="breadcrumb-item">재무 관리</li>
        <li class="breadcrumb-item active">정산 관리</li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-calculator me-1"></i>
            정산 관리
        </div>
        <div class="card-body">
            <!-- 정산 기간 선택 -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <form id="settlementForm" class="row g-3">
                        <div class="col-md-4">
                            <label for="settlementType" class="form-label">정산 유형</label>
                            <select id="settlementType" class="form-select">
                                <option value="store">판매점 정산</option>
                                <option value="winners">당첨자 정산</option>
                                <option value="expenses">운영비 정산</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="settlementPeriod" class="form-label">정산 기간</label>
                            <select id="settlementPeriod" class="form-select">
                                <option value="daily">일별</option>
                                <option value="weekly">주별</option>
                                <option value="monthly" selected>월별</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">정산 조회</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newSettlementModal">
                        <i class="fas fa-plus me-1"></i> 새 정산 생성
                    </button>
                </div>
            </div>
            
            <!-- 정산 개요 -->
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> 정산 정보</h5>
                <p>판매점, 당첨자, 운영비 등 다양한 유형의 정산 내역을 관리할 수 있습니다. 정산 기간과 유형을 선택하여 상세 내역을 확인하세요.</p>
            </div>
            
            <!-- 정산 상태 개요 -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-primary text-white mb-4">
                        <div class="card-body">
                            <h2 class="text-center">0</h2>
                            <p class="text-center mb-0">전체 정산 건수</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-success text-white mb-4">
                        <div class="card-body">
                            <h2 class="text-center">0</h2>
                            <p class="text-center mb-0">완료 정산</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-warning text-white mb-4">
                        <div class="card-body">
                            <h2 class="text-center">0</h2>
                            <p class="text-center mb-0">대기 정산</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-danger text-white mb-4">
                        <div class="card-body">
                            <h2 class="text-center">₹ 0</h2>
                            <p class="text-center mb-0">정산 총액</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 정산 목록 테이블 -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-table me-1"></i>
                    정산 목록
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>정산 유형</th>
                                    <th>기간</th>
                                    <th>대상</th>
                                    <th>금액</th>
                                    <th>상태</th>
                                    <th>생성일</th>
                                    <th>처리일</th>
                                    <th>작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="9" class="text-center">정산 내역이 없습니다.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 새 정산 생성 모달 -->
<div class="modal fade" id="newSettlementModal" tabindex="-1" aria-labelledby="newSettlementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newSettlementModalLabel">새 정산 생성</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createSettlementForm">
                    <div class="mb-3">
                        <label for="newSettlementType" class="form-label">정산 유형</label>
                        <select id="newSettlementType" class="form-select" required>
                            <option value="">유형 선택</option>
                            <option value="store">판매점 정산</option>
                            <option value="winners">당첨자 정산</option>
                            <option value="expenses">운영비 정산</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="newSettlementTarget" class="form-label">정산 대상</label>
                        <select id="newSettlementTarget" class="form-select" required>
                            <option value="">대상 선택 (유형에 따라 변경됨)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="newSettlementPeriod" class="form-label">정산 기간</label>
                        <div class="row">
                            <div class="col">
                                <input type="date" id="startDate" class="form-control" required>
                                <small class="text-muted">시작일</small>
                            </div>
                            <div class="col">
                                <input type="date" id="endDate" class="form-control" required>
                                <small class="text-muted">종료일</small>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="newSettlementAmount" class="form-label">정산 금액</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" id="newSettlementAmount" class="form-control" required min="0" step="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="newSettlementDescription" class="form-label">설명</label>
                        <textarea id="newSettlementDescription" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="createSettlementBtn">정산 생성</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 페이지 로드 시 콘솔에 디버깅 메시지
    console.log('정산 관리 페이지 로드됨');
    
    // 정산 유형 변경 이벤트 처리
    const newSettlementType = document.getElementById('newSettlementType');
    const newSettlementTarget = document.getElementById('newSettlementTarget');
    
    if (newSettlementType && newSettlementTarget) {
        newSettlementType.addEventListener('change', function() {
            // 정산 유형에 따라 대상 옵션 변경
            newSettlementTarget.innerHTML = '<option value="">로딩 중...</option>';
            
            // 실제로는 AJAX 요청을 통해 데이터를 가져와야 함
            setTimeout(() => {
                switch(this.value) {
                    case 'store':
                        newSettlementTarget.innerHTML = `
                            <option value="">판매점 선택</option>
                            <option value="all">모든 판매점</option>
                        `;
                        break;
                    case 'winners':
                        newSettlementTarget.innerHTML = `
                            <option value="">당첨 유형 선택</option>
                            <option value="all">모든 당첨</option>
                            <option value="jackpot">잭팟 당첨만</option>
                        `;
                        break;
                    case 'expenses':
                        newSettlementTarget.innerHTML = `
                            <option value="">비용 유형 선택</option>
                            <option value="all">모든 비용</option>
                            <option value="operation">운영비</option>
                            <option value="marketing">마케팅비</option>
                        `;
                        break;
                    default:
                        newSettlementTarget.innerHTML = '<option value="">대상 선택 (유형에 따라 변경됨)</option>';
                }
            }, 300);
        });
    }
    
    // 정산 생성 버튼 클릭 이벤트
    const createSettlementBtn = document.getElementById('createSettlementBtn');
    if (createSettlementBtn) {
        createSettlementBtn.addEventListener('click', function() {
            // 정산 생성 폼 유효성 검사
            const form = document.getElementById('createSettlementForm');
            const type = document.getElementById('newSettlementType').value;
            const target = document.getElementById('newSettlementTarget').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const amount = document.getElementById('newSettlementAmount').value;
            
            if (!type || !target || !startDate || !endDate || !amount) {
                alert('모든 필수 필드를 입력해주세요.');
                return;
            }
            
            console.log('정산 생성 요청:', {type, target, startDate, endDate, amount});
            // 여기에 정산 생성 AJAX 요청 추가
            
            // 임시로 모달 닫기
            const modal = bootstrap.Modal.getInstance(document.getElementById('newSettlementModal'));
            modal.hide();
        });
    }
    
    // 정산 조회 폼 제출 이벤트
    const settlementForm = document.getElementById('settlementForm');
    if (settlementForm) {
        settlementForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const type = document.getElementById('settlementType').value;
            const period = document.getElementById('settlementPeriod').value;
            
            console.log('정산 조회:', type, period);
            // 여기에 정산 조회 로직 추가
        });
    }
});
</script>

<?php
// 푸터 인클루드
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>