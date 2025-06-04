<?php
/**
 * 고객 인증 관리 페이지
 * 
 * 이 페이지는 고객 계정의 인증 상태를 관리하는 기능을 제공합니다.
 * - 신분증 인증 상태 관리
 * - 이메일/전화번호 인증 상태 관리
 * - 기타 인증 문서 관리
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
// functions.php에 정의된 checkPageAccess 함수 사용
if (function_exists('checkPageAccess')) {
    checkPageAccess('customer_management');
} else {
    // 함수가 로드되지 않은 경우 로그 기록
    error_log("checkPageAccess 함수를 찾을 수 없습니다.");
}

// 현재 페이지 정보
$pageTitle = "고객 인증 관리";
$currentSection = "customer";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$conn = getDBConnection();

// 메시지 초기화
$message = '';
$message_type = '';

// 인증 승인/거부 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
    $verification_id = isset($_POST['verification_id']) ? (int)$_POST['verification_id'] : 0;
    
    if ($customer_id > 0) {
        if ($action === 'approve') {
            // 인증 승인 처리
            $message = "고객 ID {$customer_id}의 인증이 승인되었습니다.";
            $message_type = "success";
        } else if ($action === 'reject') {
            // 인증 거부 처리
            $rejection_reason = isset($_POST['rejection_reason']) ? sanitizeInput($_POST['rejection_reason']) : '';
            $message = "고객 ID {$customer_id}의 인증이 거부되었습니다.";
            $message_type = "warning";
        } else if ($action === 'request') {
            // 추가 서류 요청 처리
            $request_message = isset($_POST['request_message']) ? sanitizeInput($_POST['request_message']) : '';
            $message = "고객 ID {$customer_id}에게 추가 인증 서류가 요청되었습니다.";
            $message_type = "info";
        }
    } else {
        $message = "유효하지 않은 고객 ID입니다.";
        $message_type = "danger";
    }
}

// 인증 대기 목록 가져오기
function getPendingVerifications($conn, $limit = 10, $offset = 0) {
    // 실제 환경에서는 데이터베이스에서 데이터를 가져옵니다.
    // 여기서는 더미 데이터를 반환합니다.
    return [
        [
            'id' => 1,
            'customer_id' => 101,
            'customer_name' => '홍길동',
            'verification_type' => 'id_card',
            'submitted_date' => '2023-05-01 09:30:00',
            'status' => 'pending',
            'document_path' => '/uploads/verifications/id_card_101.jpg'
        ],
        [
            'id' => 2,
            'customer_id' => 102,
            'customer_name' => '김철수',
            'verification_type' => 'address_proof',
            'submitted_date' => '2023-05-02 11:45:00',
            'status' => 'pending',
            'document_path' => '/uploads/verifications/address_102.pdf'
        ],
        [
            'id' => 3,
            'customer_id' => 103,
            'customer_name' => '이영희',
            'verification_type' => 'bank_statement',
            'submitted_date' => '2023-05-03 14:20:00',
            'status' => 'pending',
            'document_path' => '/uploads/verifications/bank_103.pdf'
        ],
    ];
}

// 인증 이력 가져오기
function getVerificationHistory($conn, $limit = 10, $offset = 0) {
    // 실제 환경에서는 데이터베이스에서 데이터를 가져옵니다.
    // 여기서는 더미 데이터를 반환합니다.
    return [
        [
            'id' => 4,
            'customer_id' => 104,
            'customer_name' => '박지성',
            'verification_type' => 'id_card',
            'submitted_date' => '2023-04-25 10:15:00',
            'status' => 'approved',
            'processed_date' => '2023-04-26 09:30:00',
            'processed_by' => '관리자'
        ],
        [
            'id' => 5,
            'customer_id' => 105,
            'customer_name' => '손흥민',
            'verification_type' => 'address_proof',
            'submitted_date' => '2023-04-27 13:40:00',
            'status' => 'rejected',
            'processed_date' => '2023-04-28 11:20:00',
            'processed_by' => '관리자',
            'rejection_reason' => '문서가 명확하지 않음'
        ],
        [
            'id' => 6,
            'customer_id' => 106,
            'customer_name' => '김연아',
            'verification_type' => 'bank_statement',
            'submitted_date' => '2023-04-29 15:30:00',
            'status' => 'approved',
            'processed_date' => '2023-04-30 14:10:00',
            'processed_by' => '관리자'
        ],
    ];
}

// 데이터 가져오기
$pendingVerifications = getPendingVerifications($conn);
$verificationHistory = getVerificationHistory($conn);

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
                    <li class="breadcrumb-item">고객 관리</li>
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

        <!-- 탭 메뉴 -->
        <ul class="nav nav-tabs" id="verificationTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="pending-tab" data-toggle="tab" href="#pending" role="tab" aria-controls="pending" aria-selected="true">인증 대기 목록</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="history-tab" data-toggle="tab" href="#history" role="tab" aria-controls="history" aria-selected="false">인증 이력</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="settings-tab" data-toggle="tab" href="#settings" role="tab" aria-controls="settings" aria-selected="false">인증 설정</a>
            </li>
        </ul>

        <!-- 탭 컨텐츠 -->
        <div class="tab-content" id="verificationTabContent">
            <!-- 인증 대기 목록 탭 -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">인증 대기 목록</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pendingVerifications)): ?>
                            <div class="alert alert-info">
                                현재 인증 대기 중인 신청이 없습니다.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>고객 ID</th>
                                            <th>고객 이름</th>
                                            <th>인증 유형</th>
                                            <th>제출 일시</th>
                                            <th>상태</th>
                                            <th>작업</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingVerifications as $verification): ?>
                                        <tr>
                                            <td><?php echo escape($verification['id']); ?></td>
                                            <td><?php echo escape($verification['customer_id']); ?></td>
                                            <td><?php echo escape($verification['customer_name']); ?></td>
                                            <td>
                                                <?php 
                                                switch ($verification['verification_type']) {
                                                    case 'id_card':
                                                        echo '신분증';
                                                        break;
                                                    case 'address_proof':
                                                        echo '주소 증명';
                                                        break;
                                                    case 'bank_statement':
                                                        echo '은행 거래내역서';
                                                        break;
                                                    default:
                                                        echo escape($verification['verification_type']);
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo formatDate($verification['submitted_date'], 'Y-m-d H:i'); ?></td>
                                            <td>
                                                <span class="badge badge-warning">대기 중</span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#viewDocumentModal" data-id="<?php echo $verification['id']; ?>" data-path="<?php echo $verification['document_path']; ?>">
                                                    문서 보기
                                                </button>
                                                <button type="button" class="btn btn-sm btn-success approve-btn" data-toggle="modal" data-target="#approveModal" data-id="<?php echo $verification['id']; ?>" data-customer-id="<?php echo $verification['customer_id']; ?>">
                                                    승인
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger reject-btn" data-toggle="modal" data-target="#rejectModal" data-id="<?php echo $verification['id']; ?>" data-customer-id="<?php echo $verification['customer_id']; ?>">
                                                    거부
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning request-btn" data-toggle="modal" data-target="#requestModal" data-id="<?php echo $verification['id']; ?>" data-customer-id="<?php echo $verification['customer_id']; ?>">
                                                    추가 요청
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 인증 이력 탭 -->
            <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">인증 이력</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($verificationHistory)): ?>
                            <div class="alert alert-info">
                                인증 이력이 없습니다.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>고객 ID</th>
                                            <th>고객 이름</th>
                                            <th>인증 유형</th>
                                            <th>제출 일시</th>
                                            <th>상태</th>
                                            <th>처리 일시</th>
                                            <th>처리자</th>
                                            <th>작업</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($verificationHistory as $verification): ?>
                                        <tr>
                                            <td><?php echo escape($verification['id']); ?></td>
                                            <td><?php echo escape($verification['customer_id']); ?></td>
                                            <td><?php echo escape($verification['customer_name']); ?></td>
                                            <td>
                                                <?php 
                                                switch ($verification['verification_type']) {
                                                    case 'id_card':
                                                        echo '신분증';
                                                        break;
                                                    case 'address_proof':
                                                        echo '주소 증명';
                                                        break;
                                                    case 'bank_statement':
                                                        echo '은행 거래내역서';
                                                        break;
                                                    default:
                                                        echo escape($verification['verification_type']);
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo formatDate($verification['submitted_date'], 'Y-m-d H:i'); ?></td>
                                            <td>
                                                <?php if ($verification['status'] === 'approved'): ?>
                                                    <span class="badge badge-success">승인됨</span>
                                                <?php elseif ($verification['status'] === 'rejected'): ?>
                                                    <span class="badge badge-danger">거부됨</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary"><?php echo escape($verification['status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDate($verification['processed_date'], 'Y-m-d H:i'); ?></td>
                                            <td><?php echo escape($verification['processed_by']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info view-history-btn" data-toggle="modal" data-target="#historyModal" data-id="<?php echo $verification['id']; ?>">
                                                    상세 보기
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 인증 설정 탭 -->
            <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">인증 설정</h3>
                    </div>
                    <div class="card-body">
                        <form id="verificationSettingsForm" method="post">
                            <input type="hidden" name="action" value="update_settings">
                            
                            <div class="form-group">
                                <label for="require_id_verification">신분증 인증 필수 여부</label>
                                <select class="form-control" id="require_id_verification" name="require_id_verification">
                                    <option value="1" selected>필수</option>
                                    <option value="0">선택</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="require_address_verification">주소 인증 필수 여부</label>
                                <select class="form-control" id="require_address_verification" name="require_address_verification">
                                    <option value="1" selected>필수</option>
                                    <option value="0">선택</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="require_phone_verification">전화번호 인증 필수 여부</label>
                                <select class="form-control" id="require_phone_verification" name="require_phone_verification">
                                    <option value="1" selected>필수</option>
                                    <option value="0">선택</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="require_email_verification">이메일 인증 필수 여부</label>
                                <select class="form-control" id="require_email_verification" name="require_email_verification">
                                    <option value="1" selected>필수</option>
                                    <option value="0">선택</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="verification_expiry_days">인증 유효 기간 (일)</label>
                                <input type="number" class="form-control" id="verification_expiry_days" name="verification_expiry_days" value="365" min="1" max="3650">
                            </div>
                            
                            <div class="form-group">
                                <label for="allowed_document_types">허용되는 문서 형식</label>
                                <select class="form-control" id="allowed_document_types" name="allowed_document_types[]" multiple>
                                    <option value="jpg" selected>JPG</option>
                                    <option value="jpeg" selected>JPEG</option>
                                    <option value="png" selected>PNG</option>
                                    <option value="pdf" selected>PDF</option>
                                    <option value="doc">DOC</option>
                                    <option value="docx">DOCX</option>
                                </select>
                                <small class="form-text text-muted">Ctrl 또는 Cmd 키를 누른 상태에서 클릭하여 여러 항목을 선택할 수 있습니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="max_file_size">최대 파일 크기 (MB)</label>
                                <input type="number" class="form-control" id="max_file_size" name="max_file_size" value="5" min="1" max="50">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">설정 저장</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<!-- 문서 보기 모달 -->
<div class="modal fade" id="viewDocumentModal" tabindex="-1" aria-labelledby="viewDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewDocumentModalLabel">인증 문서 보기</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <img id="documentImage" src="" alt="인증 문서" class="img-fluid" style="max-height: 500px;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<!-- 승인 모달 -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveModalLabel">인증 승인</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <p>선택한 인증 신청을 승인하시겠습니까?</p>
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="verification_id" id="approve_verification_id">
                    <input type="hidden" name="customer_id" id="approve_customer_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-success">승인</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 거부 모달 -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">인증 거부</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rejection_reason">거부 사유</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                    </div>
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="verification_id" id="reject_verification_id">
                    <input type="hidden" name="customer_id" id="reject_customer_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-danger">거부</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 추가 요청 모달 -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="requestModalLabel">추가 서류 요청</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="request_message">요청 메시지</label>
                        <textarea class="form-control" id="request_message" name="request_message" rows="3" required></textarea>
                    </div>
                    <input type="hidden" name="action" value="request">
                    <input type="hidden" name="verification_id" id="request_verification_id">
                    <input type="hidden" name="customer_id" id="request_customer_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-warning">요청</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 이력 상세 보기 모달 -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historyModalLabel">인증 이력 상세 정보</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th>ID</th>
                            <td id="history_id"></td>
                        </tr>
                        <tr>
                            <th>고객 ID</th>
                            <td id="history_customer_id"></td>
                        </tr>
                        <tr>
                            <th>고객 이름</th>
                            <td id="history_customer_name"></td>
                        </tr>
                        <tr>
                            <th>인증 유형</th>
                            <td id="history_verification_type"></td>
                        </tr>
                        <tr>
                            <th>제출 일시</th>
                            <td id="history_submitted_date"></td>
                        </tr>
                        <tr>
                            <th>상태</th>
                            <td id="history_status"></td>
                        </tr>
                        <tr>
                            <th>처리 일시</th>
                            <td id="history_processed_date"></td>
                        </tr>
                        <tr>
                            <th>처리자</th>
                            <td id="history_processed_by"></td>
                        </tr>
                        <tr id="history_reason_row" style="display: none;">
                            <th>거부 사유</th>
                            <td id="history_rejection_reason"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<script>
// 문서 보기 모달 이벤트
$('#viewDocumentModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    var path = button.data('path');
    
    // 실제 환경에서는 경로가 올바르게 설정되어야 합니다.
    $('#documentImage').attr('src', '<?php echo SERVER_URL; ?>' + path);
});

// 승인 모달 이벤트
$('#approveModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    var customerId = button.data('customer-id');
    
    $('#approve_verification_id').val(id);
    $('#approve_customer_id').val(customerId);
});

// 거부 모달 이벤트
$('#rejectModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    var customerId = button.data('customer-id');
    
    $('#reject_verification_id').val(id);
    $('#reject_customer_id').val(customerId);
});

// 추가 요청 모달 이벤트
$('#requestModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    var customerId = button.data('customer-id');
    
    $('#request_verification_id').val(id);
    $('#request_customer_id').val(customerId);
});

// 이력 상세 보기 모달 이벤트
$('#historyModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    
    // 실제 환경에서는 AJAX를 사용하여 데이터를 가져와야 합니다.
    // 여기서는 더미 데이터를 사용합니다.
    var verificationHistory = <?php echo json_encode($verificationHistory); ?>;
    var verification = null;
    
    for (var i = 0; i < verificationHistory.length; i++) {
        if (verificationHistory[i].id == id) {
            verification = verificationHistory[i];
            break;
        }
    }
    
    if (verification) {
        $('#history_id').text(verification.id);
        $('#history_customer_id').text(verification.customer_id);
        $('#history_customer_name').text(verification.customer_name);
        
        // 인증 유형 변환
        var verificationType = '';
        switch (verification.verification_type) {
            case 'id_card':
                verificationType = '신분증';
                break;
            case 'address_proof':
                verificationType = '주소 증명';
                break;
            case 'bank_statement':
                verificationType = '은행 거래내역서';
                break;
            default:
                verificationType = verification.verification_type;
        }
        $('#history_verification_type').text(verificationType);
        
        $('#history_submitted_date').text(formatDate(verification.submitted_date));
        
        // 상태 변환
        var status = '';
        var statusClass = '';
        switch (verification.status) {
            case 'approved':
                status = '승인됨';
                statusClass = 'text-success font-weight-bold';
                break;
            case 'rejected':
                status = '거부됨';
                statusClass = 'text-danger font-weight-bold';
                break;
            default:
                status = verification.status;
                statusClass = 'text-secondary';
        }
        $('#history_status').text(status).removeClass().addClass(statusClass);
        
        $('#history_processed_date').text(formatDate(verification.processed_date));
        $('#history_processed_by').text(verification.processed_by);
        
        // 거부 사유가 있는 경우에만 표시
        if (verification.status === 'rejected' && verification.rejection_reason) {
            $('#history_rejection_reason').text(verification.rejection_reason);
            $('#history_reason_row').show();
        } else {
            $('#history_reason_row').hide();
        }
    }
});

// 날짜 형식 변환 함수
function formatDate(dateStr) {
    var date = new Date(dateStr);
    return date.getFullYear() + '-' + 
           ('0' + (date.getMonth() + 1)).slice(-2) + '-' + 
           ('0' + date.getDate()).slice(-2) + ' ' + 
           ('0' + date.getHours()).slice(-2) + ':' + 
           ('0' + date.getMinutes()).slice(-2);
}
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
