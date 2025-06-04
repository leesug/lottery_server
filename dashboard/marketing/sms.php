<?php
/**
 * SMS 마케팅 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "SMS 마케팅";
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
                    <li class="breadcrumb-item active">SMS 마케팅</li>
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
                        <h3>15,248</h3>
                        <p>이번 달 발송된 SMS</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-sms"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>96.2%</h3>
                        <p>전송 성공률</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>8.5%</h3>
                        <p>평균 응답률</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-reply"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>2</h3>
                        <p>예약된 캠페인</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- SMS 캠페인 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">SMS 캠페인 목록</h3>
                <div class="card-tools">
                    <a href="sms-campaign-add.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> 새 SMS 캠페인
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
                                <th>대상 그룹</th>
                                <th>메시지</th>
                                <th>발송일</th>
                                <th>발송 수</th>
                                <th>성공률</th>
                                <th>상태</th>
                                <th style="width: 150px">액션</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>1</td>
                                <td>5월 대형 추첨 알림</td>
                                <td>활성 고객</td>
                                <td>KHUSHI LOTTERY: 5월 대형 추첨이 내일 진행됩니다. 아직...</td>
                                <td>2025-05-15</td>
                                <td>8,452</td>
                                <td>98.7%</td>
                                <td><span class="badge badge-success">완료됨</span></td>
                                <td>
                                    <a href="sms-campaign-details.php?id=1" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="sms-campaign-resend.php?id=1" class="btn btn-warning btn-xs">
                                        <i class="fas fa-redo"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>당첨 안내 알림</td>
                                <td>최근 당첨자</td>
                                <td>축하합니다! KHUSHI LOTTERY 추첨에 당첨되셨습니다. 상금...</td>
                                <td>2025-05-12</td>
                                <td>246</td>
                                <td>100%</td>
                                <td><span class="badge badge-success">완료됨</span></td>
                                <td>
                                    <a href="sms-campaign-details.php?id=2" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="sms-campaign-resend.php?id=2" class="btn btn-warning btn-xs">
                                        <i class="fas fa-redo"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal2">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>신규 복권 출시 안내</td>
                                <td>모든 고객</td>
                                <td>KHUSHI LOTTERY에서 새로운 복권이 출시되었습니다. 1등 상금...</td>
                                <td>2025-05-20</td>
                                <td>15,000</td>
                                <td>-</td>
                                <td><span class="badge badge-warning">예약됨</span></td>
                                <td>
                                    <a href="sms-campaign-details.php?id=3" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="sms-campaign-edit.php?id=3" class="btn btn-primary btn-xs">
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
                                <td>휴면 고객</td>
                                <td>KHUSHI LOTTERY가 그리워요! 귀하를 위한 특별 프로모션이...</td>
                                <td>2025-05-05</td>
                                <td>3,578</td>
                                <td>94.2%</td>
                                <td><span class="badge badge-success">완료됨</span></td>
                                <td>
                                    <a href="sms-campaign-details.php?id=4" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="sms-campaign-resend.php?id=4" class="btn btn-warning btn-xs">
                                        <i class="fas fa-redo"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteModal4">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>프로모션 코드 발송</td>
                                <td>VIP 고객</td>
                                <td>KHUSHI LOTTERY VIP 프로모션: 코드 'VIP25'를 입력하시면...</td>
                                <td>2025-05-25</td>
                                <td>500</td>
                                <td>-</td>
                                <td><span class="badge badge-warning">예약됨</span></td>
                                <td>
                                    <a href="sms-campaign-details.php?id=5" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="sms-campaign-edit.php?id=5" class="btn btn-primary btn-xs">
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
        
        <!-- 새 SMS 작성 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">새 SMS 작성</h3>
            </div>
            <div class="card-body">
                <form id="smsForm">
                    <div class="form-group">
                        <label for="campaignName">캠페인명</label>
                        <input type="text" class="form-control" id="campaignName" placeholder="캠페인명 입력">
                    </div>
                    <div class="form-group">
                        <label for="targetGroup">대상 그룹</label>
                        <select class="form-control" id="targetGroup">
                            <option value="">대상 그룹 선택</option>
                            <option value="all">모든 고객</option>
                            <option value="active">활성 고객</option>
                            <option value="inactive">휴면 고객</option>
                            <option value="vip">VIP 고객</option>
                            <option value="winners">최근 당첨자</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="messageText">메시지 내용</label>
                        <textarea class="form-control" id="messageText" rows="4" placeholder="SMS 메시지 내용 입력" maxlength="160"></textarea>
                        <small class="form-text text-muted">
                            <span id="charCount">0</span>/160자 (한 번에 보낼 수 있는 최대 길이)
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="sendDate">발송 일시</label>
                        <input type="datetime-local" class="form-control" id="sendDate">
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="sendNow">
                            <label class="custom-control-label" for="sendNow">즉시 발송</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">SMS 캠페인 저장</button>
                    <button type="button" class="btn btn-success" id="previewBtn">미리보기</button>
                </form>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- SMS 통계 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">SMS 발송 통계 (최근 6개월)</h3>
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
                        <h3 class="card-title">대상 그룹별 응답률</h3>
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
                <h4 class="modal-title">SMS 캠페인 삭제</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>정말로 이 SMS 캠페인을 삭제하시겠습니까?</p>
                <p><strong>5월 대형 추첨 알림</strong></p>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger">삭제</button>
            </div>
        </div>
    </div>
</div>

<!-- SMS 미리보기 모달 -->
<div class="modal fade" id="previewModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">SMS 미리보기</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="sms-preview-container" style="max-width: 300px; margin: 0 auto; border: 1px solid #ccc; border-radius: 10px; padding: 15px; background-color: #f5f5f5;">
                    <div class="sms-header" style="margin-bottom: 10px; color: #666;">SMS 메시지</div>
                    <div id="previewText" style="background-color: #d4f7d4; border-radius: 8px; padding: 10px; font-size: 14px;"></div>
                    <div class="sms-footer" style="margin-top: 10px; font-size: 12px; color: #888; text-align: right;">
                        <span id="previewLength">0</span>/160
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<script>
// 페이지 로드 시 실행
document.addEventListener('DOMContentLoaded', function() {
    console.log('SMS 마케팅 페이지가 로드되었습니다.');
    
    // SMS 목록 테이블 초기화
    initDataTable();
    
    // 차트 초기화 (실제 구현 시 차트 라이브러리 사용)
    initCharts();
    
    // 메시지 길이 카운터
    const messageText = document.getElementById('messageText');
    const charCount = document.getElementById('charCount');
    
    if (messageText && charCount) {
        messageText.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }
    
    // 미리보기 버튼 이벤트
    const previewBtn = document.getElementById('previewBtn');
    if (previewBtn) {
        previewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const messageText = document.getElementById('messageText').value;
            const previewText = document.getElementById('previewText');
            const previewLength = document.getElementById('previewLength');
            
            if (previewText && previewLength) {
                previewText.textContent = messageText;
                previewLength.textContent = messageText.length;
                
                $('#previewModal').modal('show');
            }
        });
    }
    
    // 즉시 발송 체크박스 이벤트
    const sendNow = document.getElementById('sendNow');
    const sendDate = document.getElementById('sendDate');
    
    if (sendNow && sendDate) {
        sendNow.addEventListener('change', function() {
            sendDate.disabled = this.checked;
        });
    }
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
