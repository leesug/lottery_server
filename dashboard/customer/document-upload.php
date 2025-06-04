<?php
/**
 * 고객 문서 업로드 페이지
 * 
 * 이 페이지는 고객의 새 문서를 업로드하는 기능을 제공합니다.
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
checkPageAccess('customer_management');

// 고객 ID 유효성 검사
$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

if ($customerId <= 0) {
    // 유효하지 않은 ID인 경우 고객 목록 페이지로 리다이렉트
    header('Location: customer-list.php');
    exit;
}

// 데이터베이스 연결
$db = getDbConnection();

// 고객 정보 조회
$sql = "SELECT id, customer_code, first_name, last_name, email, phone FROM customers WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $customerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // 고객 정보가 없는 경우 고객 목록 페이지로 리다이렉트
    $stmt->close();
    header('Location: customer-list.php');
    exit;
}

$customer = $result->fetch_assoc();
$stmt->close();

// 초기 변수 설정
$success = false;
$errors = [];
$formData = [
    'document_type' => '',
    'document_number' => '',
    'verification_status' => 'pending'
];

// 업로드 디렉토리 설정
$uploadDir = 'uploads/customer_documents/';
$fullUploadDir = $_SERVER['DOCUMENT_ROOT'] . '/' . $uploadDir;

// 디렉토리가 없으면 생성
if (!is_dir($fullUploadDir)) {
    mkdir($fullUploadDir, 0777, true);
}

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    validateCsrfToken($_POST['csrf_token']);
    
    // 폼 데이터 유효성 검사
    $formData = [
        'document_type' => sanitizeInput($_POST['document_type']),
        'document_number' => sanitizeInput($_POST['document_number']),
        'verification_status' => 'pending' // 신규 업로드 문서는 항상 대기 상태로 시작
    ];
    
    // 필수 필드 검사
    if (empty($formData['document_type'])) {
        $errors['document_type'] = '문서 유형을 선택해주세요.';
    }
    
    // 파일 업로드 처리
    if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
        $errors['document_file'] = '파일을 선택해주세요.';
    } else {
        // 파일 정보
        $file = $_FILES['document_file'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        
        // 파일 확장자 추출
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // 허용된 확장자
        $allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        
        // 파일 확장자 검사
        if (!in_array($fileExt, $allowedExts)) {
            $errors['document_file'] = '허용되지 않은 파일 형식입니다. 허용된 형식: ' . implode(', ', $allowedExts);
        }
        
        // 파일 크기 검사 (5MB 제한)
        if ($fileSize > 5 * 1024 * 1024) {
            $errors['document_file'] = '파일 크기가 너무 큽니다. 최대 5MB까지 업로드할 수 있습니다.';
        }
    }
    
    // 에러가 없으면 파일 업로드 및 DB 등록 진행
    if (empty($errors)) {
        // 고유한 파일명 생성
        $newFileName = uniqid('doc_') . '_' . $customer['customer_code'] . '.' . $fileExt;
        $filePath = $uploadDir . $newFileName;
        $fullFilePath = $fullUploadDir . $newFileName;
        
        // 파일 이동
        if (move_uploaded_file($fileTmpName, $fullFilePath)) {
            // 문서 정보 DB에 저장
            $sql = "INSERT INTO customer_documents (
                        customer_id, document_type, document_number, document_path, 
                        uploaded_date, verification_status
                    ) VALUES (?, ?, ?, ?, NOW(), ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param(
                'issss',
                $customerId,
                $formData['document_type'],
                $formData['document_number'],
                $filePath,
                $formData['verification_status']
            );
            
            $result = $stmt->execute();
            
            if ($result) {
                $documentId = $db->insert_id;
                
                // 성공 메시지 설정
                $success = true;
                
                // 작업 로그 기록
                logAction('document_upload', '새 문서 업로드: ' . $documentId . ', 고객: ' . $customerId);
                
                // 폼 데이터 초기화
                $formData = [
                    'document_type' => '',
                    'document_number' => '',
                    'verification_status' => 'pending'
                ];
            } else {
                $errors['general'] = '문서 정보 저장 중 오류가 발생했습니다. 다시 시도해주세요.';
                logError('document_upload_fail', '문서 정보 저장 실패: ' . $db->error);
                
                // 업로드된 파일 삭제
                @unlink($fullFilePath);
            }
            
            $stmt->close();
        } else {
            $errors['document_file'] = '파일 업로드 중 오류가 발생했습니다. 다시 시도해주세요.';
            logError('document_upload_fail', '파일 업로드 실패');
        }
    }
}

// 페이지 제목 및 기타 메타 정보
$pageTitle = "문서 업로드: " . $customer['first_name'] . ' ' . $customer['last_name'];
$pageDescription = "고객 코드: " . $customer['customer_code'] . "의 새 문서를 업로드합니다.";
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
                        <li class="breadcrumb-item active">문서 업로드</li>
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
                문서가 성공적으로 업로드되었습니다.
            </div>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> 오류!</h5>
                <?php echo $errors['general']; ?>
            </div>
            <?php endif; ?>

            <!-- 고객 정보 요약 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-user"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">고객 정보</span>
                            <span class="info-box-number"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></span>
                            <span class="info-box-text">고객 코드: <?php echo htmlspecialchars($customer['customer_code']); ?></span>
                            <span class="info-box-text">이메일: <?php echo htmlspecialchars($customer['email']); ?></span>
                            <span class="info-box-text">전화번호: <?php echo htmlspecialchars($customer['phone']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 문서 업로드 폼 -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">문서 업로드</h3>
                </div>
                <form method="post" id="documentForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="document_type">문서 유형 <span class="text-danger">*</span></label>
                                    <select class="form-control <?php echo isset($errors['document_type']) ? 'is-invalid' : ''; ?>" 
                                        id="document_type" name="document_type" required>
                                        <option value="">-- 문서 유형 선택 --</option>
                                        <option value="id_proof" <?php echo ($formData['document_type'] == 'id_proof') ? 'selected' : ''; ?>>신분증</option>
                                        <option value="address_proof" <?php echo ($formData['document_type'] == 'address_proof') ? 'selected' : ''; ?>>주소 증명</option>
                                        <option value="bank_details" <?php echo ($formData['document_type'] == 'bank_details') ? 'selected' : ''; ?>>은행 정보</option>
                                        <option value="other" <?php echo ($formData['document_type'] == 'other') ? 'selected' : ''; ?>>기타</option>
                                    </select>
                                    <?php if (isset($errors['document_type'])): ?>
                                        <span class="error invalid-feedback"><?php echo $errors['document_type']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="document_number">문서 번호</label>
                                    <input type="text" class="form-control" id="document_number" name="document_number" 
                                        placeholder="문서 번호 입력 (예: 주민등록번호, 계좌번호 등)" 
                                        value="<?php echo htmlspecialchars($formData['document_number']); ?>">
                                    <small class="form-text text-muted">신분증 번호, 계좌번호 등 문서의 고유 식별 번호를 입력하세요.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="document_file">문서 파일 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input <?php echo isset($errors['document_file']) ? 'is-invalid' : ''; ?>" 
                                        id="document_file" name="document_file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required>
                                    <label class="custom-file-label" for="document_file">파일 선택</label>
                                </div>
                            </div>
                            <?php if (isset($errors['document_file'])): ?>
                                <span class="error text-danger"><?php echo $errors['document_file']; ?></span>
                            <?php endif; ?>
                            <small class="form-text text-muted">
                                허용된 파일 형식: JPG, JPEG, PNG, PDF, DOC, DOCX<br>
                                최대 파일 크기: 5MB
                            </small>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> 문서 업로드
                        </button>
                        <a href="customer-documents.php?customer_id=<?php echo $customerId; ?>" class="btn btn-default">
                            <i class="fas fa-times"></i> 취소
                        </a>
                    </div>
                </form>
            </div>

            <!-- 문서 업로드 안내 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">문서 업로드 안내</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-id-card"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">신분증</span>
                                    <span class="info-box-number">주민등록증, 운전면허증, 여권 등</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-home"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">주소 증명</span>
                                    <span class="info-box-number">주민등록등본, 공과금 고지서 등</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-university"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">은행 정보</span>
                                    <span class="info-box-number">통장 사본, 계좌 거래 내역서 등</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-file-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">기타</span>
                                    <span class="info-box-number">기타 필요한 문서</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <h5><i class="icon fas fa-info"></i> 주의사항</h5>
                        <ul>
                            <li>모든 문서는 선명하게 스캔하거나 촬영해주세요.</li>
                            <li>문서의 모든 내용이 잘 보이도록 해주세요.</li>
                            <li>지나치게 큰 파일은 업로드가 제한될 수 있습니다.</li>
                            <li>개인정보 보호를 위해 업로드된 문서는 승인된 직원만 열람할 수 있습니다.</li>
                            <li>업로드된 문서는 인증 과정을 거친 후 유효한 것으로 처리됩니다.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
// 브라우저 콘솔에 디버깅 정보 출력
console.log('문서 업로드 페이지 로드됨');
console.log('고객 ID:', <?php echo json_encode($customerId); ?>);

// 폼 유효성 검사
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('documentForm');
    
    form.addEventListener('submit', function(e) {
        console.log('폼 제출 시도');
        let isValid = true;
        
        // 문서 유형 유효성 검사
        const documentType = document.getElementById('document_type').value;
        if (!documentType) {
            isValid = false;
            console.log('문서 유형이 선택되지 않음');
        }
        
        // 파일 유효성 검사
        const documentFile = document.getElementById('document_file').files[0];
        if (!documentFile) {
            isValid = false;
            console.log('파일이 선택되지 않음');
        } else {
            // 파일 확장자 검사
            const fileName = documentFile.name;
            const fileExt = fileName.split('.').pop().toLowerCase();
            const allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
            
            if (!allowedExts.includes(fileExt)) {
                isValid = false;
                console.log('허용되지 않은 파일 형식:', fileExt);
            }
            
            // 파일 크기 검사 (5MB 제한)
            if (documentFile.size > 5 * 1024 * 1024) {
                isValid = false;
                console.log('파일 크기가 너무 큼:', documentFile.size);
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            console.log('폼 유효성 검사 실패');
        } else {
            console.log('폼 유효성 검사 통과');
        }
    });
    
    // 파일 선택 이벤트 처리
    document.getElementById('document_file').addEventListener('change', function(e) {
        console.log('파일 선택됨');
        
        // 파일명 표시
        const fileName = e.target.files[0]?.name;
        const label = document.querySelector('.custom-file-label');
        
        if (fileName) {
            label.textContent = fileName;
            console.log('선택된 파일:', fileName);
            
            // 파일 크기 검사
            const fileSize = e.target.files[0].size;
            if (fileSize > 5 * 1024 * 1024) {
                alert('파일 크기가 너무 큽니다. 최대 5MB까지 업로드할 수 있습니다.');
                e.target.value = '';
                label.textContent = '파일 선택';
            }
            
            // 파일 확장자 검사
            const fileExt = fileName.split('.').pop().toLowerCase();
            const allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
            
            if (!allowedExts.includes(fileExt)) {
                alert('허용되지 않은 파일 형식입니다. 허용된 형식: ' + allowedExts.join(', '));
                e.target.value = '';
                label.textContent = '파일 선택';
            }
        } else {
            label.textContent = '파일 선택';
        }
    });
});
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>
