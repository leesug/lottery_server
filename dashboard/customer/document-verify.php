<?php
/**
 * 문서 확인 및 승인/거부 페이지
 * 
 * 이 페이지는 고객 문서의 인증 상태를 관리하는 기능을 제공합니다.
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
checkPageAccess('customer_management');

// 문서 ID 및 고객 ID 유효성 검사
$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

if ($documentId <= 0 || $customerId <= 0) {
    // 유효하지 않은 ID인 경우 고객 목록 페이지로 리다이렉트
    header('Location: customer-list.php');
    exit;
}

// 데이터베이스 연결
$db = getDbConnection();

// 문서 정보 조회
$sql = "SELECT d.*, c.first_name, c.last_name, c.customer_code, c.email 
        FROM customer_documents d
        JOIN customers c ON d.customer_id = c.id
        WHERE d.id = ? AND d.customer_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('ii', $documentId, $customerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // 문서 정보가 없는 경우 고객 문서 목록 페이지로 리다이렉트
    $stmt->close();
    header('Location: customer-documents.php?customer_id=' . $customerId);
    exit;
}

$document = $result->fetch_assoc();
$stmt->close();

// 초기 변수 설정
$success = false;
$errors = [];
$message = '';

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    validateCsrfToken($_POST['csrf_token']);
    
    // 문서 인증 상태 업데이트
    if (isset($_POST['verify_action'])) {
        $action = $_POST['verify_action'];
        $newStatus = '';
        $rejectionReason = '';
        
        switch ($action) {
            case 'verify':
                $newStatus = 'verified';
                break;
            case 'reject':
                $newStatus = 'rejected';
                $rejectionReason = sanitizeInput($_POST['rejection_reason']);
                if (empty($rejectionReason)) {
                    $errors['rejection_reason'] = '거부 사유를 입력해주세요.';
                }
                break;
            case 'pending':
                $newStatus = 'pending';
                break;
            default:
                $errors['action'] = '유효하지 않은 작업입니다.';
        }
        
        if (empty($errors)) {
            $sql = "UPDATE customer_documents SET 
                    verification_status = ?, 
                    verification_date = NOW(), 
                    verified_by = ?, 
                    rejection_reason = ?
                    WHERE id = ?";
            
            $userId = getCurrentUserId(); // 현재 로그인한 사용자 ID
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param('sisi', $newStatus, $userId, $rejectionReason, $documentId);
            $result = $stmt->execute();
            
            if ($result) {
                $success = true;
                
                // 메시지 설정
                switch ($newStatus) {
                    case 'verified':
                        $message = '문서가 성공적으로 인증되었습니다.';
                        break;
                    case 'rejected':
                        $message = '문서가 거부되었습니다.';
                        break;
                    case 'pending':
                        $message = '문서 상태가 대기중으로 변경되었습니다.';
                        break;
                }
                
                // 작업 로그 기록
                logAction('document_verify', '문서 인증 상태 변경: ' . $documentId . ' (' . $newStatus . ')');
                
                // 업데이트된 문서 정보 다시 조회
                $sql = "SELECT d.*, c.first_name, c.last_name, c.customer_code, c.email 
                        FROM customer_documents d
                        JOIN customers c ON d.customer_id = c.id
                        WHERE d.id = ? AND d.customer_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param('ii', $documentId, $customerId);
                $stmt->execute();
                $result = $stmt->get_result();
                $document = $result->fetch_assoc();
            } else {
                $errors['general'] = '문서 상태 업데이트 중 오류가 발생했습니다.';
                logError('document_verify_fail', '문서 인증 상태 변경 실패: ' . $db->error);
            }
            
            $stmt->close();
        }
    }
}

// 현재 로그인한 사용자 ID 가져오기
function getCurrentUserId() {
    // 세션에서 사용자 ID 가져오기
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    
    // 기본값 반환
    return 1; // 관리자 ID로 가정
}

// 페이지 제목 및 기타 메타 정보
$pageTitle = "문서 확인 및 승인";
$pageDescription = "문서 ID: " . $documentId . "의 인증 상태를 관리합니다.";
$activeMenu = "customer";
$activeSubMenu = "customer-list";

// 헤더 포함
include '../../templates/header.php';
?>

<div class="content-wrapper">
    <!-- 콘텐츠 헤더 -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo $pageTitle; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard/">대시보드</a></li>
                        <li class="breadcrumb-item">고객 관리</li>
                        <li class="breadcrumb-item"><a href="customer-list.php">고객 목록</a></li>
                        <li class="breadcrumb-item"><a href="customer-details.php?id=<?php echo $customerId; ?>">고객 세부 정보</a></li>
                        <li class="breadcrumb-item"><a href="customer-documents.php?customer_id=<?php echo $customerId; ?>">문서</a></li>
                        <li class="breadcrumb-item active">문서 확인</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- 메인 콘텐츠 -->
    <section class="content">
        <div class="container-fluid">
            <!-- 버튼 행 -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <a href="customer-documents.php?customer_id=<?php echo $customerId; ?>" class="btn btn-default">
                        <i class="fas fa-arrow-left"></i> 문서 목록으로 돌아가기
                    </a>
                    <a href="customer-details.php?id=<?php echo $customerId; ?>" class="btn btn-info">
                        <i class="fas fa-user"></i> 고객 세부 정보
                    </a>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-check"></i> 성공!</h5>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> 오류!</h5>
                <?php echo $errors['general']; ?>
            </div>
            <?php endif; ?>

            <div class="row">
                <!-- 고객 정보 -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">고객 정보</h3>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <tr>
                                    <th>고객명</th>
                                    <td><?php echo htmlspecialchars($document['first_name'] . ' ' . $document['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>고객 코드</th>
                                    <td><?php echo htmlspecialchars($document['customer_code']); ?></td>
                                </tr>
                                <tr>
                                    <th>이메일</th>
                                    <td><?php echo htmlspecialchars($document['email']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 문서 정보 -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">문서 정보</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table">
                                        <tr>
                                            <th>문서 ID</th>
                                            <td><?php echo $document['id']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>문서 유형</th>
                                            <td>
                                                <?php
                                                $typeText = '';
                                                
                                                switch ($document['document_type']) {
                                                    case 'id_proof':
                                                        $typeText = '신분증';
                                                        break;
                                                    case 'address_proof':
                                                        $typeText = '주소 증명';
                                                        break;
                                                    case 'bank_details':
                                                        $typeText = '은행 정보';
                                                        break;
                                                    default:
                                                        $typeText = '기타';
                                                }
                                                ?>
                                                <?php echo $typeText; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>문서 번호</th>
                                            <td><?php echo htmlspecialchars($document['document_number'] ?: '없음'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>업로드 날짜</th>
                                            <td><?php echo date('Y-m-d H:i', strtotime($document['uploaded_date'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table">
                                        <tr>
                                            <th>인증 상태</th>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                $statusText = '';
                                                
                                                switch ($document['verification_status']) {
                                                    case 'pending':
                                                        $statusClass = 'warning';
                                                        $statusText = '대기중';
                                                        break;
                                                    case 'verified':
                                                        $statusClass = 'success';
                                                        $statusText = '인증됨';
                                                        break;
                                                    case 'rejected':
                                                        $statusClass = 'danger';
                                                        $statusText = '거부됨';
                                                        break;
                                                    default:
                                                        $statusClass = 'secondary';
                                                        $statusText = '알 수 없음';
                                                }
                                                ?>
                                                <span class="badge badge-<?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>인증 날짜</th>
                                            <td>
                                                <?php echo $document['verification_date'] ? date('Y-m-d H:i', strtotime($document['verification_date'])) : '없음'; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>인증자</th>
                                            <td>
                                                <?php 
                                                echo $document['verified_by'] ? getUserName($document['verified_by']) : '없음'; 
                                                
                                                // 사용자 이름 가져오기 함수
                                                function getUserName($userId) {
                                                    global $db;
                                                    
                                                    $sql = "SELECT username FROM users WHERE id = ?";
                                                    $stmt = $db->prepare($sql);
                                                    $stmt->bind_param('i', $userId);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    
                                                    if ($result->num_rows > 0) {
                                                        $user = $result->fetch_assoc();
                                                        return $user['username'];
                                                    }
                                                    
                                                    $stmt->close();
                                                    return '관리자';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>거부 사유</th>
                                            <td><?php echo htmlspecialchars($document['rejection_reason'] ?: '없음'); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 문서 미리보기 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">문서 미리보기</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <?php 
                            $filePath = $document['document_path'];
                            $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                            
                            // 이미지 파일인 경우 직접 표시
                            if (in_array($fileExt, ['jpg', 'jpeg', 'png'])) : 
                            ?>
                                <img src="../../<?php echo $filePath; ?>" class="img-fluid" style="max-height: 500px;" alt="문서 이미지">
                            <?php 
                            // PDF 파일인 경우 iframe으로 표시
                            elseif ($fileExt === 'pdf') : 
                            ?>
                                <embed src="../../<?php echo $filePath; ?>" type="application/pdf" width="100%" height="500px" />
                            <?php 
                            // 기타 파일 형식은 다운로드 링크만 제공
                            else : 
                            ?>
                                <p>이 문서 형식은 미리보기를 지원하지 않습니다. 아래 링크를 통해 다운로드하세요.</p>
                                <a href="../../<?php echo $filePath; ?>" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-download"></i> 문서 다운로드
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 인증 상태 변경 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">인증 상태 변경</h3>
                </div>
                <div class="card-body">
                    <form method="post" id="verifyForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group clearfix">
                                    <div class="icheck-success d-inline">
                                        <input type="radio" name="verify_action" id="verify" value="verify">
                                        <label for="verify">문서 인증</label>
                                    </div>
                                </div>
                                <p>이 문서가 유효하고 인증 조건을 충족하는 경우 선택하세요.</p>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group clearfix">
                                    <div class="icheck-danger d-inline">
                                        <input type="radio" name="verify_action" id="reject" value="reject">
                                        <label for="reject">문서 거부</label>
                                    </div>
                                </div>
                                <p>이 문서가 유효하지 않거나 인증 조건을 충족하지 않는 경우 선택하세요.</p>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group clearfix">
                                    <div class="icheck-warning d-inline">
                                        <input type="radio" name="verify_action" id="pending" value="pending">
                                        <label for="pending">대기 상태로 변경</label>
                                    </div>
                                </div>
                                <p>추가 검토가 필요한 경우 선택하세요.</p>
                            </div>
                        </div>
                        
                        <div class="form-group" id="rejectionReasonGroup" style="display: none;">
                            <label for="rejection_reason">거부 사유 <span class="text-danger">*</span></label>
                            <textarea class="form-control <?php echo isset($errors['rejection_reason']) ? 'is-invalid' : ''; ?>" 
                                      id="rejection_reason" name="rejection_reason" rows="3" 
                                      placeholder="문서 거부 사유를 입력하세요."><?php echo htmlspecialchars(isset($_POST['rejection_reason']) ? $_POST['rejection_reason'] : ''); ?></textarea>
                            <?php if (isset($errors['rejection_reason'])): ?>
                                <span class="error invalid-feedback"><?php echo $errors['rejection_reason']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">상태 변경</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
// 브라우저 콘솔에 디버깅 정보 출력
console.log('문서 확인 및 승인 페이지 로드됨');
console.log('문서 ID:', <?php echo json_encode($documentId); ?>);
console.log('고객 ID:', <?php echo json_encode($customerId); ?>);
console.log('문서 정보:', <?php echo json_encode($document); ?>);

// 페이지 로드 시 이벤트 처리
document.addEventListener('DOMContentLoaded', function() {
    // 라디오 버튼 변경 이벤트 처리
    const radioButtons = document.querySelectorAll('input[name="verify_action"]');
    const rejectionReasonGroup = document.getElementById('rejectionReasonGroup');
    
    radioButtons.forEach(function(radio) {
        radio.addEventListener('change', function() {
            console.log('선택된 작업:', this.value);
            
            // 거부 사유 입력란 표시/숨김 처리
            if (this.value === 'reject') {
                rejectionReasonGroup.style.display = 'block';
            } else {
                rejectionReasonGroup.style.display = 'none';
            }
        });
    });
    
    // 폼 제출 이벤트 처리
    document.getElementById('verifyForm').addEventListener('submit', function(e) {
        console.log('폼 제출 시도');
        
        // 작업 선택 여부 확인
        const selectedAction = document.querySelector('input[name="verify_action"]:checked');
        if (!selectedAction) {
            e.preventDefault();
            alert('인증 상태 변경 작업을 선택해주세요.');
            return;
        }
        
        // 거부 선택 시 사유 입력 여부 확인
        if (selectedAction.value === 'reject') {
            const rejectionReason = document.getElementById('rejection_reason').value.trim();
            if (!rejectionReason) {
                e.preventDefault();
                alert('거부 사유를 입력해주세요.');
                return;
            }
        }
    });
});
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>
