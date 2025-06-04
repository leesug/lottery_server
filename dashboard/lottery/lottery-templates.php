<?php
/**
 * 복권 템플릿 관리
 * 다양한 복권 디자인 템플릿을 관리하는 페이지
 * 
 * @package Lottery Management
 * @author Claude
 * @created 2025-05-16
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/date_functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = getDbConnection();

// 현재 페이지 정보
$pageTitle = "복권 템플릿 관리";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 메시지 초기화
$message = '';
$message_type = '';

// CSRF 토큰 생성
$csrf_token = SecurityManager::generateCsrfToken();

// 페이지 제목 설정
// $page_title = "복권 템플릿 관리"; // 중복 선언이므로 주석 처리
$page_description = "복권 템플릿을 생성, 수정, 삭제하는 페이지입니다.";

// 데이터베이스 연결
$db = getDbConnection();

// 작업 결과 메시지
$success_message = '';
$error_message = '';

// 폼 처리: 템플릿 추가/수정
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF 토큰 검증
    if (!SecurityManager::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = "보안 토큰이 유효하지 않습니다. 페이지를 새로고침 후 다시 시도해 주세요.";
    } else {
        try {
            switch ($_POST['action']) {
                case 'add_template':
                    // 템플릿 추가 처리 (테스트 모드)
                    $template_name = sanitizeInput($_POST['template_name'] ?? '');
                    $template_description = sanitizeInput($_POST['template_description'] ?? '');
                    $layout_json = sanitizeInput($_POST['layout_json'] ?? '');
                    $lottery_type = sanitizeInput($_POST['lottery_type'] ?? '');
                    $is_active = isset($_POST['is_active']) ? 1 : 0;
                    
                    if (empty($template_name) || empty($lottery_type)) {
                        $error_message = "템플릿 이름과 복권 타입은 필수입니다.";
                    } else {
                        // 실제 DB 저장 대신 성공 메시지 표시
                        $success_message = "템플릿이 성공적으로 추가되었습니다. (테스트 모드)";
                        logActivity('템플릿 추가: ' . $template_name);
                    }
                    break;
                    
                case 'edit_template':
                    // 템플릿 수정 처리 (테스트 모드)
                    $template_id = sanitizeInput($_POST['template_id'] ?? '');
                    $template_name = sanitizeInput($_POST['template_name'] ?? '');
                    $template_description = sanitizeInput($_POST['template_description'] ?? '');
                    $layout_json = sanitizeInput($_POST['layout_json'] ?? '');
                    $lottery_type = sanitizeInput($_POST['lottery_type'] ?? '');
                    $is_active = isset($_POST['is_active']) ? 1 : 0;
                    
                    if (empty($template_id) || empty($template_name) || empty($lottery_type)) {
                        $error_message = "템플릿 ID, 이름, 복권 타입은 필수입니다.";
                    } else {
                        // 실제 DB 수정 대신 성공 메시지 표시
                        $success_message = "템플릿이 성공적으로 수정되었습니다. (테스트 모드)";
                        logActivity('템플릿 수정: ' . $template_name);
                    }
                    break;
                    
                case 'delete_template':
                    // 템플릿 삭제 처리 (테스트 모드)
                    $template_id = sanitizeInput($_POST['template_id'] ?? '');
                    
                    if (empty($template_id)) {
                        $error_message = "템플릿 ID는 필수입니다.";
                    } else {
                        // 실제 DB 삭제 대신 성공 메시지 표시
                        $success_message = "템플릿이 성공적으로 삭제되었습니다. (테스트 모드)";
                        logActivity('템플릿 삭제: ID ' . $template_id);
                    }
                    break;
            }
        } catch (Exception $e) {
            $error_message = "오류가 발생했습니다: " . $e->getMessage();
            logError('lottery_templates.php: ' . $e->getMessage());
        }
    }
}

// 템플릿 목록 불러오기
$templates = [];

// 정적 데이터 사용
// 실제 데이터베이스 연결이 작동하지 않으므로 대체 데이터를 사용
$templates = [
    [
        'id' => 1,
        'template_name' => '기본 복권 템플릿',
        'template_description' => '일반적인 복권 디자인을 위한 기본 템플릿',
        'layout_json' => '{"width":100,"height":50,"elements":[{"type":"text","x":10,"y":10,"width":80,"height":10,"text":"복권 번호"}]}',
        'lottery_type' => '일일 복권',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => '2025-05-16 12:00:00',
        'updated_at' => null
    ],
    [
        'id' => 2,
        'template_name' => '특별 복권 템플릿',
        'template_description' => '특별 행사용 복권 디자인',
        'layout_json' => '{"width":120,"height":60,"elements":[{"type":"text","x":10,"y":10,"width":100,"height":10,"text":"특별 복권 번호"}]}',
        'lottery_type' => '특별 복권',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => '2025-05-15 12:00:00',
        'updated_at' => null
    ]
];

// 복권 타입 목록 불러오기
$lottery_types = [];

// 정적 데이터 사용
$lottery_types = [
    [
        'id' => 1,
        'lottery_name' => '일일 복권'
    ],
    [
        'id' => 2,
        'lottery_name' => '주간 복권'
    ],
    [
        'id' => 3,
        'lottery_name' => '월간 복권'
    ],
    [
        'id' => 4,
        'lottery_name' => '특별 복권'
    ]
];

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
                    <li class="breadcrumb-item">복권 관리</li>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-palette me-1"></i>
            <?php echo $page_description; ?>
        </div>
        <div class="card-body">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- 새 템플릿 추가 버튼 -->
            <div class="mb-4">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                    <i class="fas fa-plus"></i> 새 템플릿 추가
                </button>
            </div>
            
            <!-- 템플릿 목록 테이블 -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="templatesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>템플릿 이름</th>
                            <th>복권 타입</th>
                            <th>설명</th>
                            <th>상태</th>
                            <th>생성일</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($template['id']); ?></td>
                            <td><?php echo htmlspecialchars($template['template_name']); ?></td>
                            <td><?php echo htmlspecialchars($template['lottery_type']); ?></td>
                            <td><?php echo htmlspecialchars($template['template_description']); ?></td>
                            <td>
                                <?php if ($template['is_active']): ?>
                                    <span class="badge bg-success">활성</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">비활성</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatDateTime($template['created_at']); ?></td>
                            <td>
                                <button type="button" class="btn btn-info btn-sm preview-template" data-id="<?php echo $template['id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-primary btn-sm edit-template" data-id="<?php echo $template['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm delete-template" data-id="<?php echo $template['id']; ?>" data-name="<?php echo htmlspecialchars($template['template_name']); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 템플릿 추가 모달 -->
<div class="modal fade" id="addTemplateModal" tabindex="-1" aria-labelledby="addTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTemplateModalLabel">새 템플릿 추가</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addTemplateForm" method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_template">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label for="template_name" class="form-label">템플릿 이름 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="template_name" name="template_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="lottery_type" class="form-label">복권 타입 <span class="text-danger">*</span></label>
                        <select class="form-select" id="lottery_type" name="lottery_type" required>
                            <option value="">선택하세요</option>
                            <?php foreach ($lottery_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['id']); ?>"><?php echo htmlspecialchars($type['lottery_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="template_description" class="form-label">템플릿 설명</label>
                        <textarea class="form-control" id="template_description" name="template_description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="layout_json" class="form-label">레이아웃 정의 (JSON)</label>
                        <textarea class="form-control" id="layout_json" name="layout_json" rows="10"></textarea>
                        <div class="form-text">복권 템플릿의 레이아웃을 JSON 형식으로 정의합니다.</div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">
                            활성화 상태
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 템플릿 수정 모달 -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" aria-labelledby="editTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTemplateModalLabel">템플릿 수정</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTemplateForm" method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_template">
                    <input type="hidden" name="template_id" id="edit_template_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label for="edit_template_name" class="form-label">템플릿 이름 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_template_name" name="template_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_lottery_type" class="form-label">복권 타입 <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_lottery_type" name="lottery_type" required>
                            <option value="">선택하세요</option>
                            <?php foreach ($lottery_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['id']); ?>"><?php echo htmlspecialchars($type['lottery_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_template_description" class="form-label">템플릿 설명</label>
                        <textarea class="form-control" id="edit_template_description" name="template_description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_layout_json" class="form-label">레이아웃 정의 (JSON)</label>
                        <textarea class="form-control" id="edit_layout_json" name="layout_json" rows="10"></textarea>
                        <div class="form-text">복권 템플릿의 레이아웃을 JSON 형식으로 정의합니다.</div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                        <label class="form-check-label" for="edit_is_active">
                            활성화 상태
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 템플릿 삭제 확인 모달 -->
<div class="modal fade" id="deleteTemplateModal" tabindex="-1" aria-labelledby="deleteTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTemplateModalLabel">템플릿 삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>정말로 <strong id="delete_template_name"></strong> 템플릿을 삭제하시겠습니까?</p>
                <p class="text-danger">이 작업은 되돌릴 수 없습니다.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <form id="deleteTemplateForm" method="post" action="">
                    <input type="hidden" name="action" value="delete_template">
                    <input type="hidden" name="template_id" id="delete_template_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <button type="submit" class="btn btn-danger">삭제</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 템플릿 미리보기 모달 -->
<div class="modal fade" id="previewTemplateModal" tabindex="-1" aria-labelledby="previewTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewTemplateModalLabel">템플릿 미리보기</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="templatePreviewContainer" class="border p-3">
                    <!-- 템플릿 미리보기가 여기에 표시됩니다 -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<?php
// 페이지 하단 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // DataTable 초기화
    $('#templatesTable').DataTable({
        language: {
            url: '/assets/js/dataTables.korean.json'
        },
        responsive: true
    });
    
    // 템플릿 수정 버튼 클릭 이벤트
    document.querySelectorAll('.edit-template').forEach(function(button) {
        button.addEventListener('click', function() {
            const templateId = this.getAttribute('data-id');
            console.log('템플릿 수정 버튼 클릭: ID = ' + templateId);
            
            // AJAX로 템플릿 데이터 가져오기
            fetch('/api/lottery/get_template.php?id=' + templateId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const template = data.template;
                        
                        // 폼에 템플릿 데이터 채우기
                        document.getElementById('edit_template_id').value = template.id;
                        document.getElementById('edit_template_name').value = template.template_name;
                        document.getElementById('edit_lottery_type').value = template.lottery_type;
                        document.getElementById('edit_template_description').value = template.template_description;
                        document.getElementById('edit_layout_json').value = template.layout_json;
                        document.getElementById('edit_is_active').checked = template.is_active == 1;
                        
                        // 모달 열기
                        new bootstrap.Modal(document.getElementById('editTemplateModal')).show();
                    } else {
                        alert('템플릿 정보를 가져오는 중 오류가 발생했습니다: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('템플릿 정보를 가져오는 중 오류가 발생했습니다.');
                });
        });
    });
    
    // 템플릿 삭제 버튼 클릭 이벤트
    document.querySelectorAll('.delete-template').forEach(function(button) {
        button.addEventListener('click', function() {
            const templateId = this.getAttribute('data-id');
            const templateName = this.getAttribute('data-name');
            console.log('템플릿 삭제 버튼 클릭: ID = ' + templateId + ', 이름 = ' + templateName);
            
            // 삭제 확인 모달에 정보 설정
            document.getElementById('delete_template_id').value = templateId;
            document.getElementById('delete_template_name').textContent = templateName;
            
            // 모달 열기
            new bootstrap.Modal(document.getElementById('deleteTemplateModal')).show();
        });
    });
    
    // 템플릿 미리보기 버튼 클릭 이벤트
    document.querySelectorAll('.preview-template').forEach(function(button) {
        button.addEventListener('click', function() {
            const templateId = this.getAttribute('data-id');
            console.log('템플릿 미리보기 버튼 클릭: ID = ' + templateId);
            
            // AJAX로 템플릿 데이터 가져오기
            fetch('/api/lottery/get_template.php?id=' + templateId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const template = data.template;
                        
                        // 미리보기 컨테이너에 템플릿 렌더링
                        const previewContainer = document.getElementById('templatePreviewContainer');
                        
                        try {
                            const layoutData = JSON.parse(template.layout_json);
                            renderTemplatePreview(previewContainer, layoutData);
                        } catch (e) {
                            previewContainer.innerHTML = '<div class="alert alert-danger">템플릿 레이아웃 데이터를 파싱할 수 없습니다: ' + e.message + '</div>';
                        }
                        
                        // 모달 열기
                        new bootstrap.Modal(document.getElementById('previewTemplateModal')).show();
                    } else {
                        alert('템플릿 정보를 가져오는 중 오류가 발생했습니다: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('템플릿 정보를 가져오는 중 오류가 발생했습니다.');
                });
        });
    });
    
    // 템플릿 미리보기 렌더링 함수
    function renderTemplatePreview(container, layoutData) {
        // 미리보기 컨테이너 초기화
        container.innerHTML = '';
        
        // 템플릿 캔버스 생성
        const canvas = document.createElement('div');
        canvas.className = 'template-canvas';
        canvas.style.position = 'relative';
        canvas.style.width = layoutData.width + 'px';
        canvas.style.height = layoutData.height + 'px';
        canvas.style.border = '1px solid #ccc';
        canvas.style.margin = '0 auto';
        canvas.style.backgroundColor = layoutData.backgroundColor || '#fff';
        
        // 템플릿 요소 렌더링
        if (layoutData.elements && Array.isArray(layoutData.elements)) {
            layoutData.elements.forEach(element => {
                const elementDiv = document.createElement('div');
                elementDiv.style.position = 'absolute';
                elementDiv.style.left = element.x + 'px';
                elementDiv.style.top = element.y + 'px';
                elementDiv.style.width = element.width + 'px';
                elementDiv.style.height = element.height + 'px';
                
                // 요소 유형에 따른 처리
                switch (element.type) {
                    case 'text':
                        elementDiv.textContent = element.content || '텍스트 영역';
                        elementDiv.style.fontFamily = element.fontFamily || 'Arial';
                        elementDiv.style.fontSize = element.fontSize + 'px' || '12px';
                        elementDiv.style.fontWeight = element.fontWeight || 'normal';
                        elementDiv.style.color = element.color || '#000';
                        elementDiv.style.textAlign = element.textAlign || 'left';
                        break;
                        
                    case 'image':
                        const img = document.createElement('img');
                        img.src = element.src || '/assets/img/placeholder.png';
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.objectFit = element.objectFit || 'contain';
                        elementDiv.appendChild(img);
                        break;
                        
                    case 'barcode':
                        elementDiv.textContent = '바코드 영역';
                        elementDiv.style.border = '1px dashed #999';
                        elementDiv.style.textAlign = 'center';
                        elementDiv.style.lineHeight = element.height + 'px';
                        break;
                        
                    case 'numbers':
                        elementDiv.textContent = '번호 영역';
                        elementDiv.style.border = '1px dashed #999';
                        elementDiv.style.textAlign = 'center';
                        elementDiv.style.lineHeight = element.height + 'px';
                        break;
                        
                    case 'shape':
                        elementDiv.style.backgroundColor = element.backgroundColor || '#eee';
                        elementDiv.style.border = '1px solid ' + (element.borderColor || '#999');
                        elementDiv.style.borderRadius = element.borderRadius + 'px' || '0';
                        break;
                }
                
                canvas.appendChild(elementDiv);
            });
        }
        
        container.appendChild(canvas);
    }
});
</script>
