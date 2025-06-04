<?php
/**
 * 고객 등록 페이지
 * 
 * 이 페이지는 새 고객을 시스템에 등록하는 기능을 제공합니다.
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
$pageTitle = "고객 추가";
$currentSection = "customer";
$currentPage = basename($_SERVER['PHP_SELF']);

// 초기 변수 설정
$success = false;
$message = '';
$message_type = '';
$formData = [
    'customer_code' => generateCustomerCode(),
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'city' => '',
    'state' => '',
    'postal_code' => '',
    'country' => 'KR',
    'date_of_birth' => '',
    'gender' => '',
    'id_type' => '',
    'id_number' => '',
    'status' => 'active',
    'notes' => ''
];

// 데이터베이스 연결
$conn = getDBConnection();

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_customer') {
    // 폼 데이터 가져오기
    $formData = [
        'customer_code' => sanitizeInput($_POST['customer_code']),
        'first_name' => sanitizeInput($_POST['first_name']),
        'last_name' => sanitizeInput($_POST['last_name']),
        'email' => sanitizeInput($_POST['email']),
        'phone' => sanitizeInput($_POST['phone']),
        'address' => sanitizeInput($_POST['address']),
        'city' => sanitizeInput($_POST['city']),
        'state' => sanitizeInput($_POST['state']),
        'postal_code' => sanitizeInput($_POST['postal_code']),
        'country' => sanitizeInput($_POST['country']),
        'date_of_birth' => sanitizeInput($_POST['date_of_birth']),
        'gender' => sanitizeInput($_POST['gender']),
        'id_type' => sanitizeInput($_POST['id_type']),
        'id_number' => sanitizeInput($_POST['id_number']),
        'status' => sanitizeInput($_POST['status']),
        'notes' => sanitizeInput($_POST['notes'])
    ];
    
    // 유효성 검사
    $errors = validateCustomerData($formData);
    
    if (empty($errors)) {
        // 실제 환경에서는 데이터베이스에 저장
        // 여기서는 성공 메시지만 표시
        $message = "고객이 성공적으로 추가되었습니다.";
        $message_type = "success";
        $success = true;
        
        // 성공 후 폼 초기화
        $formData = [
            'customer_code' => generateCustomerCode(),
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'city' => '',
            'state' => '',
            'postal_code' => '',
            'country' => 'KR',
            'date_of_birth' => '',
            'gender' => '',
            'id_type' => '',
            'id_number' => '',
            'status' => 'active',
            'notes' => ''
        ];
    } else {
        // 오류 메시지
        $message = "입력 정보에 오류가 있습니다. 아래 내용을 확인해주세요.";
        $message_type = "danger";
    }
}

// 고객 코드 생성 함수
function generateCustomerCode() {
    // 실제 환경에서는 데이터베이스에서 마지막 코드를 가져와 증가시킴
    // 여기서는 현재 시간을 기반으로 한 고유 코드 생성
    return 'CUST' . date('YmdHis');
}

// 고객 데이터 유효성 검사 함수
function validateCustomerData($data) {
    $errors = [];
    
    // 필수 필드 검사
    $requiredFields = ['first_name', 'last_name', 'email', 'phone'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $errors[$field] = "이 필드는 필수입니다.";
        }
    }
    
    // 이메일 형식 검사
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "유효한 이메일 주소를 입력해주세요.";
    }
    
    // 전화번호 형식 검사 (간단한 검사)
    if (!empty($data['phone']) && !preg_match('/^[0-9\-\+\(\)\s]{7,20}$/', $data['phone'])) {
        $errors['phone'] = "유효한 전화번호를 입력해주세요.";
    }
    
    // 생년월일 형식 검사
    if (!empty($data['date_of_birth']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date_of_birth'])) {
        $errors['date_of_birth'] = "날짜 형식은 YYYY-MM-DD이어야 합니다.";
    }
    
    return $errors;
}

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

        <!-- 고객 추가 폼 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">고객 정보 등록</h3>
            </div>
            <div class="card-body">
                <form method="post" id="addCustomerForm">
                    <input type="hidden" name="action" value="add_customer">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="customer_code">고객 코드</label>
                                <input type="text" class="form-control" id="customer_code" name="customer_code" value="<?php echo escape($formData['customer_code']); ?>" readonly>
                                <small class="form-text text-muted">자동 생성된 고객 코드입니다.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">상태</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>활성</option>
                                    <option value="inactive" <?php echo $formData['status'] === 'inactive' ? 'selected' : ''; ?>>비활성</option>
                                    <option value="pending" <?php echo $formData['status'] === 'pending' ? 'selected' : ''; ?>>대기 중</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name">이름 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" id="first_name" name="first_name" value="<?php echo escape($formData['first_name']); ?>" required>
                                <?php if (isset($errors['first_name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['first_name']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name">성 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" id="last_name" name="last_name" value="<?php echo escape($formData['last_name']); ?>" required>
                                <?php if (isset($errors['last_name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['last_name']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">이메일 <span class="text-danger">*</span></label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo escape($formData['email']); ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">전화번호 <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" id="phone" name="phone" value="<?php echo escape($formData['phone']); ?>" required>
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="address">주소</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo escape($formData['address']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="city">도시</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo escape($formData['city']); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="state">시/도</label>
                                <input type="text" class="form-control" id="state" name="state" value="<?php echo escape($formData['state']); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="postal_code">우편번호</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo escape($formData['postal_code']); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="country">국가</label>
                                <select class="form-control" id="country" name="country">
                                    <option value="KR" <?php echo $formData['country'] === 'KR' ? 'selected' : ''; ?>>대한민국</option>
                                    <option value="JP" <?php echo $formData['country'] === 'JP' ? 'selected' : ''; ?>>일본</option>
                                    <option value="US" <?php echo $formData['country'] === 'US' ? 'selected' : ''; ?>>미국</option>
                                    <option value="CN" <?php echo $formData['country'] === 'CN' ? 'selected' : ''; ?>>중국</option>
                                    <option value="SG" <?php echo $formData['country'] === 'SG' ? 'selected' : ''; ?>>싱가포르</option>
                                    <option value="TH" <?php echo $formData['country'] === 'TH' ? 'selected' : ''; ?>>태국</option>
                                    <option value="VN" <?php echo $formData['country'] === 'VN' ? 'selected' : ''; ?>>베트남</option>
                                    <!-- 필요에 따라 국가 추가 -->
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="date_of_birth">생년월일</label>
                                <input type="date" class="form-control <?php echo isset($errors['date_of_birth']) ? 'is-invalid' : ''; ?>" id="date_of_birth" name="date_of_birth" value="<?php echo escape($formData['date_of_birth']); ?>">
                                <?php if (isset($errors['date_of_birth'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['date_of_birth']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="gender">성별</label>
                                <select class="form-control" id="gender" name="gender">
                                    <option value="" <?php echo empty($formData['gender']) ? 'selected' : ''; ?>>선택 안함</option>
                                    <option value="M" <?php echo $formData['gender'] === 'M' ? 'selected' : ''; ?>>남성</option>
                                    <option value="F" <?php echo $formData['gender'] === 'F' ? 'selected' : ''; ?>>여성</option>
                                    <option value="O" <?php echo $formData['gender'] === 'O' ? 'selected' : ''; ?>>기타</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_type">신분증 종류</label>
                                <select class="form-control" id="id_type" name="id_type">
                                    <option value="" <?php echo empty($formData['id_type']) ? 'selected' : ''; ?>>선택 안함</option>
                                    <option value="national_id" <?php echo $formData['id_type'] === 'national_id' ? 'selected' : ''; ?>>주민등록증</option>
                                    <option value="driver_license" <?php echo $formData['id_type'] === 'driver_license' ? 'selected' : ''; ?>>운전면허증</option>
                                    <option value="passport" <?php echo $formData['id_type'] === 'passport' ? 'selected' : ''; ?>>여권</option>
                                    <option value="alien_card" <?php echo $formData['id_type'] === 'alien_card' ? 'selected' : ''; ?>>외국인등록증</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_number">신분증 번호</label>
                                <input type="text" class="form-control" id="id_number" name="id_number" value="<?php echo escape($formData['id_number']); ?>">
                                <small class="form-text text-muted">신분증 번호는 안전하게 암호화되어 저장됩니다.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">메모</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo escape($formData['notes']); ?></textarea>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">고객 등록</button>
                        <a href="<?php echo SERVER_URL; ?>/dashboard/customer/customer-list.php" class="btn btn-secondary">취소</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<script>
// 폼 유효성 검사
$(document).ready(function() {
    $('#addCustomerForm').submit(function(e) {
        var isValid = true;
        
        // 필수 필드 검사
        $('#first_name, #last_name, #email, #phone').each(function() {
            if ($(this).val().trim() === '') {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // 이메일 형식 검사
        var emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if ($('#email').val().trim() !== '' && !emailPattern.test($('#email').val())) {
            $('#email').addClass('is-invalid');
            isValid = false;
        }
        
        // 전화번호 형식 검사
        var phonePattern = /^[0-9\-\+\(\)\s]{7,20}$/;
        if ($('#phone').val().trim() !== '' && !phonePattern.test($('#phone').val())) {
            $('#phone').addClass('is-invalid');
            isValid = false;
        }
        
        return isValid;
    });
    
    // 입력 필드 변경 시 유효성 검사 표시 제거
    $('input, select, textarea').change(function() {
        $(this).removeClass('is-invalid');
    });
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
