<?php
/**
 * 판매점 추가 페이지
 * 
 * 이 페이지는 새 판매점을 등록하는 기능을 제공합니다.
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
checkPageAccess('store_management');

// 변수 초기화
$message = '';
$message_type = '';
$errors = [];
$formData = [
    'store_name' => '',
    'owner_name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'city' => '',
    'state' => '',
    'postal_code' => '',
    'country' => 'Nepal',
    'gps_latitude' => '',
    'gps_longitude' => '',
    'business_license_number' => '',
    'tax_id' => '',
    'bank_name' => '',
    'bank_account_number' => '',
    'bank_ifsc_code' => '',
    'status' => 'pending',
    'store_category' => 'standard',
    'store_size' => 'small',
    'notes' => ''
];

// 데이터베이스 연결
$db = get_db_connection();

// 판매점 코드 자동 생성
$storeCode = generateStoreCode($db);

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    validateCsrfToken($_POST['csrf_token']);
    
    // 폼 데이터 수집
    $formData['store_name'] = isset($_POST['store_name']) ? trim($_POST['store_name']) : '';
    $formData['owner_name'] = isset($_POST['owner_name']) ? trim($_POST['owner_name']) : '';
    $formData['email'] = isset($_POST['email']) ? trim($_POST['email']) : '';
    $formData['phone'] = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $formData['address'] = isset($_POST['address']) ? trim($_POST['address']) : '';
    $formData['city'] = isset($_POST['city']) ? trim($_POST['city']) : '';
    $formData['state'] = isset($_POST['state']) ? trim($_POST['state']) : '';
    $formData['postal_code'] = isset($_POST['postal_code']) ? trim($_POST['postal_code']) : '';
    $formData['country'] = isset($_POST['country']) ? trim($_POST['country']) : 'Nepal';
    $formData['gps_latitude'] = isset($_POST['gps_latitude']) ? trim($_POST['gps_latitude']) : '';
    $formData['gps_longitude'] = isset($_POST['gps_longitude']) ? trim($_POST['gps_longitude']) : '';
    $formData['business_license_number'] = isset($_POST['business_license_number']) ? trim($_POST['business_license_number']) : '';
    $formData['tax_id'] = isset($_POST['tax_id']) ? trim($_POST['tax_id']) : '';
    $formData['bank_name'] = isset($_POST['bank_name']) ? trim($_POST['bank_name']) : '';
    $formData['bank_account_number'] = isset($_POST['bank_account_number']) ? trim($_POST['bank_account_number']) : '';
    $formData['bank_ifsc_code'] = isset($_POST['bank_ifsc_code']) ? trim($_POST['bank_ifsc_code']) : '';
    $formData['status'] = isset($_POST['status']) ? trim($_POST['status']) : 'pending';
    $formData['store_category'] = isset($_POST['store_category']) ? trim($_POST['store_category']) : 'standard';
    $formData['store_size'] = isset($_POST['store_size']) ? trim($_POST['store_size']) : 'small';
    $formData['notes'] = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // 데이터 유효성 검사
    $errors = validateStoreData($formData);
    
    // 오류가 없으면 판매점 등록 처리
    if (empty($errors)) {
        // 판매점 코드 생성
        $storeCode = generateStoreCode($db);
        
        // 판매점 등록 쿼리
        $sql = "INSERT INTO stores (
                    store_code, store_name, owner_name, email, phone, 
                    address, city, state, postal_code, country, 
                    gps_latitude, gps_longitude, business_license_number, tax_id, 
                    bank_name, bank_account_number, bank_ifsc_code, 
                    status, store_category, store_size, notes, registration_date
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, 
                    ?, ?, ?, ?, NOW()
                )";
        
        $stmt = $db->prepare($sql);
        
        // NULL 값 처리
        $email = !empty($formData['email']) ? $formData['email'] : null;
        $state = !empty($formData['state']) ? $formData['state'] : null;
        $postalCode = !empty($formData['postal_code']) ? $formData['postal_code'] : null;
        $gpsLat = !empty($formData['gps_latitude']) ? $formData['gps_latitude'] : null;
        $gpsLng = !empty($formData['gps_longitude']) ? $formData['gps_longitude'] : null;
        $businessLicense = !empty($formData['business_license_number']) ? $formData['business_license_number'] : null;
        $taxId = !empty($formData['tax_id']) ? $formData['tax_id'] : null;
        $bankName = !empty($formData['bank_name']) ? $formData['bank_name'] : null;
        $bankAccount = !empty($formData['bank_account_number']) ? $formData['bank_account_number'] : null;
        $bankIfsc = !empty($formData['bank_ifsc_code']) ? $formData['bank_ifsc_code'] : null;
        $notes = !empty($formData['notes']) ? $formData['notes'] : null;
        
        // PDO 스타일로 파라미터 바인딩
        $stmt->bindParam(1, $storeCode);
        $stmt->bindParam(2, $formData['store_name']);
        $stmt->bindParam(3, $formData['owner_name']);
        $stmt->bindParam(4, $email);
        $stmt->bindParam(5, $formData['phone']);
        $stmt->bindParam(6, $formData['address']);
        $stmt->bindParam(7, $formData['city']);
        $stmt->bindParam(8, $state);
        $stmt->bindParam(9, $postalCode);
        $stmt->bindParam(10, $formData['country']);
        $stmt->bindParam(11, $gpsLat);
        $stmt->bindParam(12, $gpsLng);
        $stmt->bindParam(13, $businessLicense);
        $stmt->bindParam(14, $taxId);
        $stmt->bindParam(15, $bankName);
        $stmt->bindParam(16, $bankAccount);
        $stmt->bindParam(17, $bankIfsc);
        $stmt->bindParam(18, $formData['status']);
        $stmt->bindParam(19, $formData['store_category']);
        $stmt->bindParam(20, $formData['store_size']);
        $stmt->bindParam(21, $notes);;
        
        if ($stmt->execute()) {
            // 새 판매점 ID 가져오기 (PDO 스타일)
            $newStoreId = $db->lastInsertId();
            
            // 활동 로그 기록
            logActivity(
                'store_create',
                sprintf("새 판매점 등록: %s (%s)", $formData['store_name'], $storeCode),
                $newStoreId,
                'stores'
            );
            
            // 판매점 상세 페이지로 리다이렉트
            header("Location: store-details.php?id=" . $newStoreId . "&created=1");
            exit;
        } else {
            $message = "판매점 등록 중 오류가 발생했습니다: " . $stmt->errorInfo()[2];
            $message_type = "danger";
            
            // 오류 로그 기록
            logError(
                'store_create_error',
                sprintf("판매점 등록 오류: %s", $stmt->errorInfo()[2]),
                0,
                'stores'
            );
        }
    } else {
        // 오류 메시지
        $message = "입력 정보에 오류가 있습니다. 아래 내용을 확인해주세요.";
        $message_type = "danger";
    }
}

// 페이지 정보 설정
$pageTitle = "새 판매점 등록";
$currentSection = "store";
$currentPage = basename($_SERVER['PHP_SELF']);

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';

/**
 * 판매점 데이터 유효성 검사 함수
 * 
 * @param array $data 검증할 판매점 데이터
 * @return array 오류 메시지 배열
 */
function validateStoreData($data) {
    $errors = [];
    
    // 필수 필드 검사
    $requiredFields = ['store_name', 'owner_name', 'phone', 'address', 'city', 'country'];
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
    
    // GPS 좌표 형식 검사
    if (!empty($data['gps_latitude']) && !is_numeric($data['gps_latitude'])) {
        $errors['gps_latitude'] = "위도는 숫자 형식이어야 합니다.";
    }
    
    if (!empty($data['gps_longitude']) && !is_numeric($data['gps_longitude'])) {
        $errors['gps_longitude'] = "경도는 숫자 형식이어야 합니다.";
    }
    
    return $errors;
}

/**
 * 고유한 판매점 코드 생성 함수
 * 
 * @param PDO $db 데이터베이스 연결 객체
 * @return string 생성된 판매점 코드
 */
function generateStoreCode($db) {
    $prefix = 'STORE';
    $unique = false;
    $storeCode = '';
    
    // 단순화된 코드 생성 로직 - MockPDO 환경에서의 안정성을 위해
    $randomNumber = mt_rand(10000000, 99999999);
    $storeCode = $prefix . $randomNumber;
    
    try {
        // 코드가 이미 존재하는지 확인 (PDO 스타일)
        $stmt = $db->prepare("SELECT id FROM stores WHERE store_code = ?");
        $stmt->bindParam(1, $storeCode);
        $stmt->execute();
        
        // PDO 스타일로 결과 확인
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 코드가 이미 존재하면 다시 생성
        if ($result !== false) {
            $randomNumber = mt_rand(10000000, 99999999);
            $storeCode = $prefix . $randomNumber;
        }
    } catch (Exception $e) {
        // 예외 발생 시 기본값 사용
        error_log('Error in generateStoreCode: ' . $e->getMessage());
    }
    
    return $storeCode;
}
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
                    <li class="breadcrumb-item">판매점 관리</li>
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

        <!-- 판매점 추가 폼 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">판매점 정보 등록</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="post" id="addStoreForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <!-- 기본 정보 -->
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header">
                            <h3 class="card-title">기본 정보</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="store_code">판매점 코드</label>
                                        <input type="text" class="form-control" id="store_code" name="store_code" value="<?php echo htmlspecialchars($storeCode); ?>" readonly>
                                        <small class="form-text text-muted">자동 생성된 판매점 코드입니다.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">상태</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="pending" <?php echo $formData['status'] === 'pending' ? 'selected' : ''; ?>>대기중</option>
                                            <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>활성</option>
                                            <option value="inactive" <?php echo $formData['status'] === 'inactive' ? 'selected' : ''; ?>>비활성</option>
                                            <option value="terminated" <?php echo $formData['status'] === 'terminated' ? 'selected' : ''; ?>>계약해지</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="store_name">판매점명 <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control <?php echo isset($errors['store_name']) ? 'is-invalid' : ''; ?>" id="store_name" name="store_name" value="<?php echo htmlspecialchars($formData['store_name']); ?>" required>
                                        <?php if (isset($errors['store_name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['store_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="owner_name">대표자명 <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control <?php echo isset($errors['owner_name']) ? 'is-invalid' : ''; ?>" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($formData['owner_name']); ?>" required>
                                        <?php if (isset($errors['owner_name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['owner_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">이메일</label>
                                        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>">
                                        <?php if (isset($errors['email'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">전화번호 <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" id="phone" name="phone" value="<?php echo htmlspecialchars($formData['phone']); ?>" required>
                                        <?php if (isset($errors['phone'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="store_category">판매점 카테고리</label>
                                        <select class="form-control" id="store_category" name="store_category">
                                            <option value="standard" <?php echo $formData['store_category'] === 'standard' ? 'selected' : ''; ?>>일반</option>
                                            <option value="premium" <?php echo $formData['store_category'] === 'premium' ? 'selected' : ''; ?>>프리미엄</option>
                                            <option value="exclusive" <?php echo $formData['store_category'] === 'exclusive' ? 'selected' : ''; ?>>전용</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="store_size">판매점 규모</label>
                                        <select class="form-control" id="store_size" name="store_size">
                                            <option value="small" <?php echo $formData['store_size'] === 'small' ? 'selected' : ''; ?>>소형</option>
                                            <option value="medium" <?php echo $formData['store_size'] === 'medium' ? 'selected' : ''; ?>>중형</option>
                                            <option value="large" <?php echo $formData['store_size'] === 'large' ? 'selected' : ''; ?>>대형</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 주소 정보 -->
                    <div class="card card-outline card-success mb-4">
                        <div class="card-header">
                            <h3 class="card-title">주소 정보</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="address">주소 <span class="text-danger">*</span></label>
                                        <textarea class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>" id="address" name="address" rows="3" required><?php echo htmlspecialchars($formData['address']); ?></textarea>
                                        <?php if (isset($errors['address'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['address']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city">도시 <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control <?php echo isset($errors['city']) ? 'is-invalid' : ''; ?>" id="city" name="city" value="<?php echo htmlspecialchars($formData['city']); ?>" required>
                                        <?php if (isset($errors['city'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['city']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="state">주/도</label>
                                        <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($formData['state']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="postal_code">우편번호</label>
                                        <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($formData['postal_code']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="country">국가 <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control <?php echo isset($errors['country']) ? 'is-invalid' : ''; ?>" id="country" name="country" value="<?php echo htmlspecialchars($formData['country']); ?>" required>
                                        <?php if (isset($errors['country'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['country']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="gps_latitude">GPS 위도</label>
                                        <input type="text" class="form-control <?php echo isset($errors['gps_latitude']) ? 'is-invalid' : ''; ?>" id="gps_latitude" name="gps_latitude" value="<?php echo htmlspecialchars($formData['gps_latitude']); ?>" placeholder="예: 27.700769">
                                        <?php if (isset($errors['gps_latitude'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['gps_latitude']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="gps_longitude">GPS 경도</label>
                                        <input type="text" class="form-control <?php echo isset($errors['gps_longitude']) ? 'is-invalid' : ''; ?>" id="gps_longitude" name="gps_longitude" value="<?php echo htmlspecialchars($formData['gps_longitude']); ?>" placeholder="예: 85.300140">
                                        <?php if (isset($errors['gps_longitude'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['gps_longitude']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 사업자 정보 -->
                    <div class="card card-outline card-info mb-4">
                        <div class="card-header">
                            <h3 class="card-title">사업자 정보</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="business_license_number">사업자 등록번호</label>
                                        <input type="text" class="form-control" id="business_license_number" name="business_license_number" value="<?php echo htmlspecialchars($formData['business_license_number']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tax_id">세금 ID</label>
                                        <input type="text" class="form-control" id="tax_id" name="tax_id" value="<?php echo htmlspecialchars($formData['tax_id']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="bank_name">은행명</label>
                                        <input type="text" class="form-control" id="bank_name" name="bank_name" value="<?php echo htmlspecialchars($formData['bank_name']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="bank_account_number">계좌번호</label>
                                        <input type="text" class="form-control" id="bank_account_number" name="bank_account_number" value="<?php echo htmlspecialchars($formData['bank_account_number']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="bank_ifsc_code">은행 지점 코드</label>
                                        <input type="text" class="form-control" id="bank_ifsc_code" name="bank_ifsc_code" value="<?php echo htmlspecialchars($formData['bank_ifsc_code']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 추가 정보 -->
                    <div class="card card-outline card-secondary mb-4">
                        <div class="card-header">
                            <h3 class="card-title">추가 정보</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="notes">비고</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="5"><?php echo htmlspecialchars($formData['notes']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 버튼 영역 -->
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 판매점 등록
                            </button>
                            <a href="store-list.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> 목록으로 돌아가기
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 폼 제출 이벤트 리스너
    document.getElementById('addStoreForm').addEventListener('submit', function(e) {
        let isValid = true;
        
        // 필수 입력 필드 검증
        const requiredFields = ['store_name', 'owner_name', 'phone', 'address', 'city', 'country'];
        requiredFields.forEach(function(field) {
            const input = document.getElementById(field);
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                
                // 이미 생성된 피드백이 없으면 피드백 메시지 추가
                if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = '이 필드는 필수입니다.';
                    input.parentNode.insertBefore(feedback, input.nextSibling);
                }
                
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
                
                // 피드백 메시지 제거
                if (input.nextElementSibling && input.nextElementSibling.classList.contains('invalid-feedback')) {
                    input.parentNode.removeChild(input.nextElementSibling);
                }
            }
        });
        
        // 이메일 형식 검증
        const emailInput = document.getElementById('email');
        if (emailInput.value.trim() && !validateEmail(emailInput.value.trim())) {
            emailInput.classList.add('is-invalid');
            
            // 이미 생성된 피드백이 없으면 피드백 메시지 추가
            if (!emailInput.nextElementSibling || !emailInput.nextElementSibling.classList.contains('invalid-feedback')) {
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = '유효한 이메일 주소를 입력해주세요.';
                emailInput.parentNode.insertBefore(feedback, emailInput.nextSibling);
            }
            
            isValid = false;
        } else if (emailInput.value.trim()) {
            emailInput.classList.remove('is-invalid');
            
            // 피드백 메시지 제거
            if (emailInput.nextElementSibling && emailInput.nextElementSibling.classList.contains('invalid-feedback')) {
                emailInput.parentNode.removeChild(emailInput.nextElementSibling);
            }
        }
        
        // 유효성 검사 실패 시 제출 취소
        if (!isValid) {
            e.preventDefault();
            
            // 첫 번째 오류 필드로 스크롤
            const firstInvalid = document.querySelector('.is-invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
            }
        }
    });
    
    // 이메일 유효성 검사 함수
    function validateEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(email);
    }
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
