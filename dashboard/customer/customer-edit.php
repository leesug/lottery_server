<?php
/**
 * 고객 정보 수정 페이지
 * 
 * 이 페이지는 기존 고객의 정보를 수정하는 기능을 제공합니다.
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
checkPageAccess('customer_management');

// 초기 변수 설정
$customerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = false;
$errors = [];
$formData = [];

// 유효한 고객 ID 확인
if ($customerId <= 0) {
    header('Location: customer-list.php');
    exit;
}

// 데이터베이스 연결
$db = getDbConnection();

// 고객 정보 조회
$sql = "SELECT * FROM customers WHERE id = ?";
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

// 폼 데이터 초기화
$formData = $customer;

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    validateCsrfToken($_POST['csrf_token']);
    
    // 폼 데이터 유효성 검사
    $formData = [
        'id' => $customerId,
        'customer_code' => $customer['customer_code'], // 고객 코드는 변경 불가
        'first_name' => sanitizeInput($_POST['first_name']),
        'last_name' => sanitizeInput($_POST['last_name']),
        'email' => sanitizeInput($_POST['email']),
        'phone' => sanitizeInput($_POST['phone']),
        'address' => sanitizeInput($_POST['address']),
        'city' => sanitizeInput($_POST['city']),
        'state' => sanitizeInput($_POST['state']),
        'postal_code' => sanitizeInput($_POST['postal_code']),
        'country' => sanitizeInput($_POST['country']),
        'status' => sanitizeInput($_POST['status']),
        'verification_status' => sanitizeInput($_POST['verification_status']),
        'notes' => sanitizeInput($_POST['notes'])
    ];
    
    // 필수 필드 검사
    if (empty($formData['first_name'])) {
        $errors['first_name'] = '이름을 입력해주세요.';
    }
    
    if (empty($formData['last_name'])) {
        $errors['last_name'] = '성을 입력해주세요.';
    }
    
    if (!empty($formData['email'])) {
        if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '유효한 이메일 주소를 입력해주세요.';
        }
        
        // 이메일 중복 검사 (현재 고객 제외)
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT id FROM customers WHERE email = ? AND id != ? LIMIT 1");
        $stmt->bind_param('si', $formData['email'], $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors['email'] = '이미 등록된 이메일 주소입니다.';
        }
        
        $stmt->close();
    }
    
    // 에러가 없으면 고객 정보 업데이트
    if (empty($errors)) {
        $db = getDbConnection();
        
        // 고객 정보 업데이트
        $sql = "UPDATE customers SET 
                first_name = ?, 
                last_name = ?, 
                email = ?, 
                phone = ?, 
                address = ?, 
                city = ?, 
                state = ?, 
                postal_code = ?, 
                country = ?, 
                status = ?, 
                verification_status = ?, 
                notes = ?
                WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            'ssssssssssssi',
            $formData['first_name'],
            $formData['last_name'],
            $formData['email'],
            $formData['phone'],
            $formData['address'],
            $formData['city'],
            $formData['state'],
            $formData['postal_code'],
            $formData['country'],
            $formData['status'],
            $formData['verification_status'],
            $formData['notes'],
            $customerId
        );
        
        $result = $stmt->execute();
        
        if ($result) {
            // 성공 메시지 설정
            $success = true;
            
            // 작업 로그 기록
            logAction('customer_update', '고객 정보 수정: ' . $customerId . ' (' . $formData['first_name'] . ' ' . $formData['last_name'] . ')');
            
            // 업데이트된 고객 정보로 폼 데이터 갱신
            $sql = "SELECT * FROM customers WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('i', $customerId);
            $stmt->execute();
            $result = $stmt->get_result();
            $formData = $result->fetch_assoc();
        } else {
            $errors['general'] = '고객 정보 수정 중 오류가 발생했습니다. 다시 시도해주세요.';
            logError('customer_update_fail', '고객 정보 수정 실패: ' . $db->error);
        }
        
        $stmt->close();
    }
}

// 페이지 제목 및 기타 메타 정보
$pageTitle = "고객 정보 수정: " . $formData['first_name'] . ' ' . $formData['last_name'];
$pageDescription = "고객 ID: " . $formData['customer_code'] . "의 정보를 수정합니다.";
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
                        <li class="breadcrumb-item active">고객 정보 수정</li>
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
                    <a href="customer-details.php?id=<?php echo $customerId; ?>" class="btn btn-default">
                        <i class="fas fa-arrow-left"></i> 고객 세부 정보로 돌아가기
                    </a>
                    <a href="customer-list.php" class="btn btn-default">
                        <i class="fas fa-list"></i> 고객 목록
                    </a>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-check"></i> 성공!</h5>
                고객 정보가 성공적으로 수정되었습니다.
            </div>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> 오류!</h5>
                <?php echo $errors['general']; ?>
            </div>
            <?php endif; ?>

            <!-- 고객 정보 수정 폼 -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">고객 정보 수정</h3>
                </div>
                <form method="post" id="customerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="card-body">
                        <div class="row">
                            <!-- 기본 정보 섹션 -->
                            <div class="col-md-6">
                                <div class="card card-info">
                                    <div class="card-header">
                                        <h3 class="card-title">기본 정보</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="customer_code">고객 코드</label>
                                            <input type="text" class="form-control" id="customer_code" 
                                                   value="<?php echo htmlspecialchars($formData['customer_code']); ?>" readonly>
                                            <small class="form-text text-muted">고객 코드는 변경할 수 없습니다.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="first_name">이름 <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" 
                                                id="first_name" name="first_name" placeholder="이름 입력" 
                                                value="<?php echo htmlspecialchars($formData['first_name']); ?>" required>
                                            <?php if (isset($errors['first_name'])): ?>
                                                <span class="error invalid-feedback"><?php echo $errors['first_name']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="last_name">성 <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" 
                                                id="last_name" name="last_name" placeholder="성 입력" 
                                                value="<?php echo htmlspecialchars($formData['last_name']); ?>" required>
                                            <?php if (isset($errors['last_name'])): ?>
                                                <span class="error invalid-feedback"><?php echo $errors['last_name']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="email">이메일</label>
                                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                                id="email" name="email" placeholder="이메일 입력" 
                                                value="<?php echo htmlspecialchars($formData['email']); ?>">
                                            <?php if (isset($errors['email'])): ?>
                                                <span class="error invalid-feedback"><?php echo $errors['email']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="phone">전화번호</label>
                                            <input type="text" class="form-control" id="phone" name="phone" 
                                                placeholder="전화번호 입력" value="<?php echo htmlspecialchars($formData['phone']); ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="status">계정 상태</label>
                                            <select class="form-control" id="status" name="status">
                                                <option value="active" <?php echo ($formData['status'] == 'active') ? 'selected' : ''; ?>>활성</option>
                                                <option value="inactive" <?php echo ($formData['status'] == 'inactive') ? 'selected' : ''; ?>>비활성</option>
                                                <option value="blocked" <?php echo ($formData['status'] == 'blocked') ? 'selected' : ''; ?>>차단됨</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="verification_status">인증 상태</label>
                                            <select class="form-control" id="verification_status" name="verification_status">
                                                <option value="unverified" <?php echo ($formData['verification_status'] == 'unverified') ? 'selected' : ''; ?>>미인증</option>
                                                <option value="verified" <?php echo ($formData['verification_status'] == 'verified') ? 'selected' : ''; ?>>인증됨</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 주소 정보 섹션 -->
                            <div class="col-md-6">
                                <div class="card card-info">
                                    <div class="card-header">
                                        <h3 class="card-title">주소 정보</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="address">주소</label>
                                            <input type="text" class="form-control" id="address" name="address" 
                                                placeholder="주소 입력" value="<?php echo htmlspecialchars($formData['address']); ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="city">도시</label>
                                            <input type="text" class="form-control" id="city" name="city" 
                                                placeholder="도시 입력" value="<?php echo htmlspecialchars($formData['city']); ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="state">주/도</label>
                                            <input type="text" class="form-control" id="state" name="state" 
                                                placeholder="주/도 입력" value="<?php echo htmlspecialchars($formData['state']); ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="postal_code">우편번호</label>
                                            <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                                placeholder="우편번호 입력" value="<?php echo htmlspecialchars($formData['postal_code']); ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="country">국가</label>
                                            <input type="text" class="form-control" id="country" name="country" 
                                                placeholder="국가 입력" value="<?php echo htmlspecialchars($formData['country']); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 메모 섹션 -->
                        <div class="form-group">
                            <label for="notes">메모</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="고객에 대한 추가 메모 입력"><?php echo htmlspecialchars($formData['notes']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 변경사항 저장
                        </button>
                        <a href="customer-details.php?id=<?php echo $customerId; ?>" class="btn btn-default">
                            <i class="fas fa-times"></i> 취소
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
// 브라우저 콘솔에 디버깅 정보 출력
console.log('고객 정보 수정 페이지 로드됨');
console.log('고객 ID:', <?php echo json_encode($customerId); ?>);

// 폼 유효성 검사
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('customerForm');
    
    form.addEventListener('submit', function(e) {
        console.log('폼 제출 시도');
        let isValid = true;
        
        // 이름 유효성 검사
        const firstName = document.getElementById('first_name').value.trim();
        if (firstName === '') {
            isValid = false;
            console.log('이름 필드가 비어 있음');
        }
        
        // 성 유효성 검사
        const lastName = document.getElementById('last_name').value.trim();
        if (lastName === '') {
            isValid = false;
            console.log('성 필드가 비어 있음');
        }
        
        // 이메일 유효성 검사 (입력된 경우에만)
        const email = document.getElementById('email').value.trim();
        if (email !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                isValid = false;
                console.log('이메일 형식이 유효하지 않음');
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            console.log('폼 유효성 검사 실패');
        } else {
            console.log('폼 유효성 검사 통과');
        }
    });
    
    // 상태 변경 이벤트 처리
    document.getElementById('status').addEventListener('change', function() {
        console.log('계정 상태 변경:', this.value);
    });
    
    document.getElementById('verification_status').addEventListener('change', function() {
        console.log('인증 상태 변경:', this.value);
    });
});
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>
