<?php
/**
 * 메뉴 관리 페이지
 */

// 오류 표시 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 헤더 설정
header('Content-Type: text/html; charset=utf-8');

// 출력 버퍼링 시작
ob_start();

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "메뉴 관리";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 메시지 변수
$successMessage = "";
$errorMessage = "";

// 메뉴 관리 작업 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF 토큰 검증 (실제 구현 시 추가)
    
    try {
        if ($_POST['action'] === 'add_menu') {
            $menuName = $_POST['menu_name'] ?? '';
            $menuIcon = $_POST['menu_icon'] ?? 'fas fa-circle';
            $menuUrl = $_POST['menu_url'] ?? '';
            $menuOrder = $_POST['menu_order'] ?? 0;
            $parentId = $_POST['parent_id'] ?? 0;
            $roleAccess = $_POST['role_access'] ?? [];
            $isActive = isset($_POST['is_active']) && $_POST['is_active'] === 'on';
            
            // 메뉴 추가 (예시)
            // 실제로는 데이터베이스에 저장
            
            $successMessage = "메뉴가 성공적으로 추가되었습니다.";
        } elseif ($_POST['action'] === 'edit_menu') {
            $menuId = $_POST['menu_id'] ?? 0;
            $menuName = $_POST['menu_name'] ?? '';
            $menuIcon = $_POST['menu_icon'] ?? 'fas fa-circle';
            $menuUrl = $_POST['menu_url'] ?? '';
            $menuOrder = $_POST['menu_order'] ?? 0;
            $parentId = $_POST['parent_id'] ?? 0;
            $roleAccess = $_POST['role_access'] ?? [];
            $isActive = isset($_POST['is_active']) && $_POST['is_active'] === 'on';
            
            // 메뉴 수정 (예시)
            // 실제로는 데이터베이스 업데이트
            
            $successMessage = "메뉴가 성공적으로 수정되었습니다.";
        } elseif ($_POST['action'] === 'delete_menu') {
            $menuId = $_POST['menu_id'] ?? 0;
            
            // 메뉴 삭제 (예시)
            // 실제로는 데이터베이스에서 삭제
            
            $successMessage = "메뉴가 성공적으로 삭제되었습니다.";
        } elseif ($_POST['action'] === 'update_menu_order') {
            $menuIds = $_POST['menu_ids'] ?? [];
            $parentIds = $_POST['parent_ids'] ?? [];
            $orders = $_POST['orders'] ?? [];
            
            // 메뉴 순서 업데이트 (예시)
            // 실제로는 데이터베이스 업데이트
            
            $successMessage = "메뉴 순서가 성공적으로 업데이트되었습니다.";
        }
    } catch (Exception $e) {
        $errorMessage = "작업 처리 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 메뉴 목록 가져오기 (예시 데이터)
// 실제로는 데이터베이스에서 가져옴
$menuList = [
    [
        'id' => 1,
        'name' => '대시보드',
        'icon' => 'fas fa-tachometer-alt',
        'url' => '/dashboard/index.php',
        'order' => 1,
        'parent_id' => 0,
        'is_active' => true,
        'role_access' => ['admin', 'manager', 'operator', 'staff'],
        'children' => []
    ],
    [
        'id' => 2,
        'name' => '고객 관리',
        'icon' => 'fas fa-users',
        'url' => '/dashboard/customer/index.php',
        'order' => 2,
        'parent_id' => 0,
        'is_active' => true,
        'role_access' => ['admin', 'manager', 'operator'],
        'children' => [
            [
                'id' => 8,
                'name' => '고객 목록',
                'icon' => 'fas fa-list',
                'url' => '/dashboard/customer/list.php',
                'order' => 1,
                'parent_id' => 2,
                'is_active' => true,
                'role_access' => ['admin', 'manager', 'operator'],
                'children' => []
            ],
            [
                'id' => 9,
                'name' => '고객 등록',
                'icon' => 'fas fa-user-plus',
                'url' => '/dashboard/customer/add.php',
                'order' => 2,
                'parent_id' => 2,
                'is_active' => true,
                'role_access' => ['admin', 'manager'],
                'children' => []
            ]
        ]
    ],
    [
        'id' => 3,
        'name' => '복권 관리',
        'icon' => 'fas fa-ticket-alt',
        'url' => '/dashboard/lottery/index.php',
        'order' => 3,
        'parent_id' => 0,
        'is_active' => true,
        'role_access' => ['admin', 'manager'],
        'children' => [
            [
                'id' => 10,
                'name' => '복권 목록',
                'icon' => 'fas fa-list',
                'url' => '/dashboard/lottery/list.php',
                'order' => 1,
                'parent_id' => 3,
                'is_active' => true,
                'role_access' => ['admin', 'manager'],
                'children' => []
            ],
            [
                'id' => 11,
                'name' => '복권 추가',
                'icon' => 'fas fa-plus',
                'url' => '/dashboard/lottery/add.php',
                'order' => 2,
                'parent_id' => 3,
                'is_active' => true,
                'role_access' => ['admin'],
                'children' => []
            ]
        ]
    ],
    [
        'id' => 4,
        'name' => '판매 관리',
        'icon' => 'fas fa-shopping-cart',
        'url' => '/dashboard/sales/index.php',
        'order' => 4,
        'parent_id' => 0,
        'is_active' => true,
        'role_access' => ['admin', 'manager', 'operator'],
        'children' => []
    ],
    [
        'id' => 5,
        'name' => '보고서',
        'icon' => 'fas fa-chart-bar',
        'url' => '/dashboard/reports/index.php',
        'order' => 5,
        'parent_id' => 0,
        'is_active' => true,
        'role_access' => ['admin', 'manager'],
        'children' => []
    ],
    [
        'id' => 6,
        'name' => '시스템 관리',
        'icon' => 'fas fa-cogs',
        'url' => '/dashboard/system/index.php',
        'order' => 6,
        'parent_id' => 0,
        'is_active' => true,
        'role_access' => ['admin'],
        'children' => [
            [
                'id' => 12,
                'name' => '사용자 관리',
                'icon' => 'fas fa-user-cog',
                'url' => '/dashboard/system/users.php',
                'order' => 1,
                'parent_id' => 6,
                'is_active' => true,
                'role_access' => ['admin'],
                'children' => []
            ],
            [
                'id' => 13,
                'name' => '역할 및 권한 관리',
                'icon' => 'fas fa-user-shield',
                'url' => '/dashboard/system/roles.php',
                'order' => 2,
                'parent_id' => 6,
                'is_active' => true,
                'role_access' => ['admin'],
                'children' => []
            ],
            [
                'id' => 14,
                'name' => '시스템 설정',
                'icon' => 'fas fa-sliders-h',
                'url' => '/dashboard/system/settings.php',
                'order' => 3,
                'parent_id' => 6,
                'is_active' => true,
                'role_access' => ['admin'],
                'children' => []
            ],
            [
                'id' => 15,
                'name' => '로그 관리',
                'icon' => 'fas fa-history',
                'url' => '/dashboard/system/logs.php',
                'order' => 4,
                'parent_id' => 6,
                'is_active' => true,
                'role_access' => ['admin'],
                'children' => []
            ],
            [
                'id' => 16,
                'name' => '백업 및 복원',
                'icon' => 'fas fa-database',
                'url' => '/dashboard/system/backup.php',
                'order' => 5,
                'parent_id' => 6,
                'is_active' => true,
                'role_access' => ['admin'],
                'children' => []
            ],
            [
                'id' => 17,
                'name' => '시스템 정보',
                'icon' => 'fas fa-info-circle',
                'url' => '/dashboard/system/system-info.php',
                'order' => 6,
                'parent_id' => 6,
                'is_active' => true,
                'role_access' => ['admin'],
                'children' => []
            ],
            [
                'id' => 18,
                'name' => '접근 IP 관리',
                'icon' => 'fas fa-shield-alt',
                'url' => '/dashboard/system/ip-access.php',
                'order' => 7,
                'parent_id' => 6,
                'is_active' => true,
                'role_access' => ['admin'],
                'children' => []
            ],
            [
                'id' => 19,
                'name' => '메뉴 관리',
                'icon' => 'fas fa-bars',
                'url' => '/dashboard/system/menus.php',
                'order' => 8,
                'parent_id' => 6,
                'is_active' => true,
                'role_access' => ['admin'],
                'children' => []
            ]
        ]
    ],
    [
        'id' => 7,
        'name' => '도움말',
        'icon' => 'fas fa-question-circle',
        'url' => '/dashboard/help/index.php',
        'order' => 7,
        'parent_id' => 0,
        'is_active' => true,
        'role_access' => ['admin', 'manager', 'operator', 'staff'],
        'children' => []
    ]
];

// 역할 목록 가져오기 (예시 데이터)
// 실제로는 데이터베이스에서 가져옴
$roleList = [
    ['id' => 1, 'name' => 'admin', 'display_name' => '관리자'],
    ['id' => 2, 'name' => 'manager', 'display_name' => '매니저'],
    ['id' => 3, 'name' => 'operator', 'display_name' => '운영자'],
    ['id' => 4, 'name' => 'staff', 'display_name' => '일반 직원']
];

// 평면화된 메뉴 목록 생성
$flatMenuList = [];
function flattenMenu($menuItems, &$flatList) {
    foreach ($menuItems as $item) {
        $flatItem = $item;
        unset($flatItem['children']);
        $flatList[] = $flatItem;
        
        if (!empty($item['children'])) {
            flattenMenu($item['children'], $flatList);
        }
    }
}
flattenMenu($menuList, $flatMenuList);

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
                    <li class="breadcrumb-item active">시스템 관리</li>
                    <li class="breadcrumb-item active">메뉴 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h5><i class="icon fas fa-check"></i> 성공!</h5>
            <?php echo $successMessage; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h5><i class="icon fas fa-ban"></i> 오류!</h5>
            <?php echo $errorMessage; ?>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-5">
                <!-- 새 메뉴 추가 -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">새 메뉴 추가</h3>
                    </div>
                    <form action="" method="post">
                        <input type="hidden" name="action" value="add_menu">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="menu_name">메뉴 이름</label>
                                <input type="text" class="form-control" id="menu_name" name="menu_name" placeholder="메뉴 이름 입력" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="menu_icon">아이콘</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i id="icon_preview" class="fas fa-circle"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="menu_icon" name="menu_icon" placeholder="FontAwesome 아이콘 클래스 (예: fas fa-users)" value="fas fa-circle">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#iconPickerModal">
                                            <i class="fas fa-search"></i> 아이콘 선택
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="menu_url">URL</label>
                                <input type="text" class="form-control" id="menu_url" name="menu_url" placeholder="메뉴 URL 입력 (예: /dashboard/example.php)">
                                <small class="form-text text-muted">부모 메뉴인 경우 index.php 페이지를 지정하거나 비워둘 수 있습니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="parent_id">부모 메뉴</label>
                                <select class="form-control" id="parent_id" name="parent_id">
                                    <option value="0">없음 (최상위 메뉴)</option>
                                    <?php foreach ($flatMenuList as $menu): ?>
                                    <?php if ($menu['parent_id'] == 0): ?>
                                    <option value="<?php echo $menu['id']; ?>"><?php echo htmlspecialchars($menu['name']); ?></option>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="menu_order">순서</label>
                                <input type="number" class="form-control" id="menu_order" name="menu_order" min="1" value="1">
                                <small class="form-text text-muted">메뉴가 표시되는 순서 (낮은 번호가 먼저 표시됨)</small>
                            </div>
                            
                            <div class="form-group">
                                <label>접근 권한</label>
                                <?php foreach ($roleList as $role): ?>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="role_<?php echo $role['name']; ?>" name="role_access[]" value="<?php echo $role['name']; ?>" <?php if ($role['name'] === 'admin') echo 'checked'; ?>>
                                    <label class="custom-control-label" for="role_<?php echo $role['name']; ?>"><?php echo $role['display_name']; ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                                    <label class="custom-control-label" for="is_active">활성화</label>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">메뉴 추가</button>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
                
                <!-- 메뉴 가이드라인 -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title">메뉴 구성 가이드라인</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li>메뉴는 최대 2단계까지 구성하는 것이 권장됩니다.</li>
                            <li>메뉴 이름은 간결하고 명확하게 작성하세요 (최대 20자 이내).</li>
                            <li>아이콘은 메뉴의 기능을 직관적으로 나타내는 것을 선택하세요.</li>
                            <li>사용자 역할에 따라 적절한 접근 권한을 설정하세요.</li>
                            <li>자주 사용되는 메뉴는 상단에 배치하세요.</li>
                            <li>비활성화된 메뉴는 사이드바에 표시되지 않습니다.</li>
                        </ul>
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="fas fa-exclamation-triangle"></i> 주의: 메뉴 항목을 삭제하면 해당 메뉴의 모든 하위 메뉴도 함께 삭제됩니다.
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col-md-5 -->
            
            <div class="col-md-7">
                <!-- 메뉴 구조 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">메뉴 구조</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" id="expandAllBtn">
                                <i class="fas fa-plus-square"></i> 모두 펼치기
                            </button>
                            <button type="button" class="btn btn-tool" id="collapseAllBtn">
                                <i class="fas fa-minus-square"></i> 모두 접기
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="menu-tree" class="dd">
                            <ol class="dd-list">
                                <?php foreach ($menuList as $menu): ?>
                                <li class="dd-item" data-id="<?php echo $menu['id']; ?>">
                                    <div class="dd-handle">
                                        <i class="<?php echo $menu['icon']; ?> mr-2"></i>
                                        <span><?php echo htmlspecialchars($menu['name']); ?></span>
                                        <?php if (!$menu['is_active']): ?>
                                        <span class="badge badge-secondary ml-2">비활성</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="dd-actions">
                                        <button type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#editMenuModal<?php echo $menu['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-xs" onclick="confirmDeleteMenu(<?php echo $menu['id']; ?>, '<?php echo addslashes(htmlspecialchars($menu['name'])); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <?php if (!empty($menu['children'])): ?>
                                    <ol class="dd-list">
                                        <?php foreach ($menu['children'] as $child): ?>
                                        <li class="dd-item" data-id="<?php echo $child['id']; ?>">
                                            <div class="dd-handle">
                                                <i class="<?php echo $child['icon']; ?> mr-2"></i>
                                                <span><?php echo htmlspecialchars($child['name']); ?></span>
                                                <?php if (!$child['is_active']): ?>
                                                <span class="badge badge-secondary ml-2">비활성</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="dd-actions">
                                                <button type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#editMenuModal<?php echo $child['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-xs" onclick="confirmDeleteMenu(<?php echo $child['id']; ?>, '<?php echo addslashes(htmlspecialchars($child['name'])); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ol>
                                    <?php endif; ?>
                                </li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                        <form id="nestable-output" action="" method="post">
                            <input type="hidden" name="action" value="update_menu_order">
                            <input type="hidden" id="menu_structure" name="menu_structure" value="">
                            <button type="submit" class="btn btn-success mt-3">메뉴 순서 저장</button>
                        </form>
                    </div>
                </div>
                <!-- /.card -->
                
                <!-- 접근 권한 미리보기 -->
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">접근 권한 미리보기</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="preview_role">역할 선택</label>
                            <select class="form-control" id="preview_role">
                                <?php foreach ($roleList as $role): ?>
                                <option value="<?php echo $role['name']; ?>"><?php echo $role['display_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="preview-sidebar card direct-chat direct-chat-primary">
                            <div class="card-header">
                                <h3 class="card-title">사이드바 미리보기</h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="sidebar">
                                    <nav class="mt-2">
                                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                                            <?php foreach ($menuList as $menu): ?>
                                            <?php if (in_array(reset($roleList)['name'], $menu['role_access']) && $menu['is_active']): ?>
                                            <li class="nav-item <?php echo (!empty($menu['children'])) ? 'menu-open' : ''; ?>">
                                                <a href="<?php echo $menu['url']; ?>" class="nav-link">
                                                    <i class="nav-icon <?php echo $menu['icon']; ?>"></i>
                                                    <p>
                                                        <?php echo htmlspecialchars($menu['name']); ?>
                                                        <?php if (!empty($menu['children'])): ?>
                                                        <i class="right fas fa-angle-left"></i>
                                                        <?php endif; ?>
                                                    </p>
                                                </a>
                                                <?php if (!empty($menu['children'])): ?>
                                                <ul class="nav nav-treeview">
                                                    <?php foreach ($menu['children'] as $child): ?>
                                                    <?php if (in_array(reset($roleList)['name'], $child['role_access']) && $child['is_active']): ?>
                                                    <li class="nav-item">
                                                        <a href="<?php echo $child['url']; ?>" class="nav-link">
                                                            <i class="nav-icon <?php echo $child['icon']; ?>"></i>
                                                            <p><?php echo htmlspecialchars($child['name']); ?></p>
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </ul>
                                                <?php endif; ?>
                                            </li>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col-md-7 -->
        </div>
        <!-- /.row -->
    </div>
</section>
<!-- /.content -->

<!-- 메뉴 편집 모달 -->
<?php foreach ($flatMenuList as $menu): ?>
<div class="modal fade" id="editMenuModal<?php echo $menu['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editMenuModalLabel<?php echo $menu['id']; ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="" method="post">
                <input type="hidden" name="action" value="edit_menu">
                <input type="hidden" name="menu_id" value="<?php echo $menu['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="editMenuModalLabel<?php echo $menu['id']; ?>">메뉴 편집: <?php echo htmlspecialchars($menu['name']); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_menu_name<?php echo $menu['id']; ?>">메뉴 이름</label>
                        <input type="text" class="form-control" id="edit_menu_name<?php echo $menu['id']; ?>" name="menu_name" value="<?php echo htmlspecialchars($menu['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_menu_icon<?php echo $menu['id']; ?>">아이콘</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="<?php echo $menu['icon']; ?>"></i></span>
                            </div>
                            <input type="text" class="form-control" id="edit_menu_icon<?php echo $menu['id']; ?>" name="menu_icon" value="<?php echo htmlspecialchars($menu['icon']); ?>">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary icon-picker-btn" data-target="#edit_menu_icon<?php echo $menu['id']; ?>">
                                    <i class="fas fa-search"></i> 아이콘 선택
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_menu_url<?php echo $menu['id']; ?>">URL</label>
                        <input type="text" class="form-control" id="edit_menu_url<?php echo $menu['id']; ?>" name="menu_url" value="<?php echo htmlspecialchars($menu['url']); ?>">
                        <small class="form-text text-muted">부모 메뉴인 경우 index.php 페이지를 지정하거나 비워둘 수 있습니다.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_parent_id<?php echo $menu['id']; ?>">부모 메뉴</label>
                        <select class="form-control" id="edit_parent_id<?php echo $menu['id']; ?>" name="parent_id">
                            <option value="0" <?php if ($menu['parent_id'] == 0) echo 'selected'; ?>>없음 (최상위 메뉴)</option>
                            <?php foreach ($flatMenuList as $parentMenu): ?>
                            <?php if ($parentMenu['parent_id'] == 0 && $parentMenu['id'] != $menu['id']): ?>
                            <option value="<?php echo $parentMenu['id']; ?>" <?php if ($menu['parent_id'] == $parentMenu['id']) echo 'selected'; ?>><?php echo htmlspecialchars($parentMenu['name']); ?></option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_menu_order<?php echo $menu['id']; ?>">순서</label>
                        <input type="number" class="form-control" id="edit_menu_order<?php echo $menu['id']; ?>" name="menu_order" min="1" value="<?php echo $menu['order']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>접근 권한</label>
                        <?php foreach ($roleList as $role): ?>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="edit_role_<?php echo $menu['id']; ?>_<?php echo $role['name']; ?>" name="role_access[]" value="<?php echo $role['name']; ?>" <?php if (in_array($role['name'], $menu['role_access'])) echo 'checked'; ?>>
                            <label class="custom-control-label" for="edit_role_<?php echo $menu['id']; ?>_<?php echo $role['name']; ?>"><?php echo $role['display_name']; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_is_active<?php echo $menu['id']; ?>" name="is_active" <?php if ($menu['is_active']) echo 'checked'; ?>>
                            <label class="custom-control-label" for="edit_is_active<?php echo $menu['id']; ?>">활성화</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- 아이콘 선택 모달 -->
<div class="modal fade" id="iconPickerModal" tabindex="-1" role="dialog" aria-labelledby="iconPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="iconPickerModalLabel">아이콘 선택</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" class="form-control" id="icon-search" placeholder="아이콘 검색...">
                    </div>
                </div>
                
                <div class="nav nav-tabs" id="icon-tabs" role="tablist">
                    <a class="nav-item nav-link active" id="solid-tab" data-toggle="tab" href="#solid-icons" role="tab" aria-controls="solid-icons" aria-selected="true">Solid</a>
                    <a class="nav-item nav-link" id="regular-tab" data-toggle="tab" href="#regular-icons" role="tab" aria-controls="regular-icons" aria-selected="false">Regular</a>
                    <a class="nav-item nav-link" id="brand-tab" data-toggle="tab" href="#brand-icons" role="tab" aria-controls="brand-icons" aria-selected="false">Brand</a>
                </div>
                
                <div class="tab-content" id="icon-tab-content">
                    <div class="tab-pane fade show active" id="solid-icons" role="tabpanel" aria-labelledby="solid-tab">
                        <div class="row icon-grid">
                            <!-- 여기에 Solid 아이콘 목록이 들어갑니다 -->
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-home"><i class="fas fa-home"></i><span>home</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-user"><i class="fas fa-user"></i><span>user</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-users"><i class="fas fa-users"></i><span>users</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-cog"><i class="fas fa-cog"></i><span>cog</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-cogs"><i class="fas fa-cogs"></i><span>cogs</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-wrench"><i class="fas fa-wrench"></i><span>wrench</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-chart-bar"><i class="fas fa-chart-bar"></i><span>chart-bar</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-chart-line"><i class="fas fa-chart-line"></i><span>chart-line</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-chart-pie"><i class="fas fa-chart-pie"></i><span>chart-pie</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-database"><i class="fas fa-database"></i><span>database</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-server"><i class="fas fa-server"></i><span>server</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-shield-alt"><i class="fas fa-shield-alt"></i><span>shield-alt</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-shopping-cart"><i class="fas fa-shopping-cart"></i><span>shopping-cart</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-tachometer-alt"><i class="fas fa-tachometer-alt"></i><span>tachometer-alt</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-ticket-alt"><i class="fas fa-ticket-alt"></i><span>ticket-alt</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-user-shield"><i class="fas fa-user-shield"></i><span>user-shield</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-user-cog"><i class="fas fa-user-cog"></i><span>user-cog</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-history"><i class="fas fa-history"></i><span>history</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-money-bill-alt"><i class="fas fa-money-bill-alt"></i><span>money-bill-alt</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-key"><i class="fas fa-key"></i><span>key</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-question-circle"><i class="fas fa-question-circle"></i><span>question-circle</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-info-circle"><i class="fas fa-info-circle"></i><span>info-circle</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-bars"><i class="fas fa-bars"></i><span>bars</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fas fa-sliders-h"><i class="fas fa-sliders-h"></i><span>sliders-h</span></div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="regular-icons" role="tabpanel" aria-labelledby="regular-tab">
                        <div class="row icon-grid">
                            <!-- 여기에 Regular 아이콘 목록이 들어갑니다 -->
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="far fa-user"><i class="far fa-user"></i><span>user</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="far fa-calendar"><i class="far fa-calendar"></i><span>calendar</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="far fa-chart-bar"><i class="far fa-chart-bar"></i><span>chart-bar</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="far fa-envelope"><i class="far fa-envelope"></i><span>envelope</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="far fa-eye"><i class="far fa-eye"></i><span>eye</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="far fa-file"><i class="far fa-file"></i><span>file</span></div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="brand-icons" role="tabpanel" aria-labelledby="brand-tab">
                        <div class="row icon-grid">
                            <!-- 여기에 Brand 아이콘 목록이 들어갑니다 -->
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fab fa-google"><i class="fab fa-google"></i><span>google</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fab fa-facebook"><i class="fab fa-facebook"></i><span>facebook</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fab fa-twitter"><i class="fab fa-twitter"></i><span>twitter</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fab fa-github"><i class="fab fa-github"></i><span>github</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fab fa-linkedin"><i class="fab fa-linkedin"></i><span>linkedin</span></div>
                            <div class="col-md-2 col-sm-3 col-4 icon-item" data-icon="fab fa-youtube"><i class="fab fa-youtube"></i><span>youtube</span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="select-icon-btn">아이콘 선택</button>
            </div>
        </div>
    </div>
</div>

<!-- 메뉴 삭제 Form (Hidden) -->
<form id="deleteMenuForm" action="" method="post" style="display: none;">
    <input type="hidden" name="action" value="delete_menu">
    <input type="hidden" name="menu_id" id="delete_menu_id" value="">
</form>

<style>
/* 중첩 메뉴 스타일 */
.dd {
    position: relative;
    display: block;
    margin: 0;
    padding: 0;
    list-style: none;
}

.dd-list {
    display: block;
    position: relative;
    margin: 0;
    padding: 0;
    list-style: none;
}

.dd-item {
    display: block;
    position: relative;
    margin: 0;
    padding: 0;
    list-style: none;
}

.dd-handle {
    display: block;
    margin: 5px 0;
    padding: 8px 10px;
    color: #333;
    text-decoration: none;
    font-weight: bold;
    border: 1px solid #ccc;
    background: #fafafa;
    box-sizing: border-box;
    -webkit-border-radius: 3px;
    border-radius: 3px;
    cursor: move;
}

.dd-handle:hover {
    color: #2ea8e5;
    background: #fff;
}

.dd-actions {
    position: absolute;
    top: 8px;
    right: 10px;
}

.dd-item > ol {
    margin-left: 30px;
}

/* 아이콘 선택기 스타일 */
.icon-grid {
    max-height: 300px;
    overflow-y: auto;
}

.icon-item {
    text-align: center;
    padding: 10px;
    margin-bottom: 10px;
    cursor: pointer;
    border-radius: 4px;
}

.icon-item:hover {
    background-color: #f8f9fa;
}

.icon-item i {
    font-size: 24px;
    display: block;
    margin-bottom: 5px;
}

.icon-item span {
    font-size: 12px;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.icon-item.selected {
    background-color: #007bff;
    color: white;
}

/* 사이드바 미리보기 스타일 */
.preview-sidebar {
    max-width: 100%;
    overflow: hidden;
}

.preview-sidebar .sidebar {
    width: 100%;
    background-color: #343a40;
    overflow: hidden;
}

.preview-sidebar .nav-sidebar {
    margin-right: 0;
}
</style>

<script>
// 메뉴 삭제 확인
function confirmDeleteMenu(menuId, menuName) {
    if (confirm('메뉴 "' + menuName + '"를 삭제하시겠습니까? 이 작업은 되돌릴 수 없으며, 해당 메뉴의 모든 하위 메뉴도 함께 삭제됩니다.')) {
        document.getElementById('delete_menu_id').value = menuId;
        document.getElementById('deleteMenuForm').submit();
    }
}

$(document).ready(function() {
    // 메뉴 아이콘 입력 시 미리보기 업데이트
    $('#menu_icon').on('input', function() {
        $('#icon_preview').attr('class', $(this).val());
    });
    
    // 중첩 메뉴 초기화
    var updateOutput = function(e) {
        var list = e.length ? e : $(e.target);
        var output = list.data('output');
        
        if (window.JSON) {
            var data = window.JSON.stringify(list.nestable('serialize'));
            $('#menu_structure').val(data);
        } else {
            alert('JSON 지원이 필요합니다.');
        }
    };
    
    // Nestable 초기화
    $('#menu-tree').nestable({
        group: 1,
        maxDepth: 2
    }).on('change', updateOutput);
    
    // 초기 메뉴 구조 데이터 설정
    updateOutput($('#menu-tree').data('output', $('#menu_structure')));
    
    // 아이콘 선택 모달
    var currentIconInput = null;
    
    // 아이콘 선택 버튼 클릭
    $('.icon-picker-btn, #menu_icon').on('click', function() {
        currentIconInput = $(this).hasClass('icon-picker-btn') ? 
            $($(this).data('target')) : $(this);
        $('#iconPickerModal').modal('show');
    });
    
    // 아이콘 아이템 클릭
    $('.icon-item').on('click', function() {
        $('.icon-item').removeClass('selected');
        $(this).addClass('selected');
    });
    
    // 아이콘 선택 버튼 클릭
    $('#select-icon-btn').on('click', function() {
        var selectedIcon = $('.icon-item.selected').data('icon');
        if (selectedIcon && currentIconInput) {
            currentIconInput.val(selectedIcon);
            if (currentIconInput.attr('id') === 'menu_icon') {
                $('#icon_preview').attr('class', selectedIcon);
            } else {
                currentIconInput.prev('.input-group-prepend').find('i').attr('class', selectedIcon);
            }
            $('#iconPickerModal').modal('hide');
        }
    });
    
    // 아이콘 검색
    $('#icon-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        $('.icon-item').each(function() {
            var iconName = $(this).find('span').text().toLowerCase();
            if (iconName.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // 모두 펼치기 버튼
    $('#expandAllBtn').on('click', function() {
        $('.dd-list').each(function() {
            $(this).show();
        });
    });
    
    // 모두 접기 버튼
    $('#collapseAllBtn').on('click', function() {
        $('.dd-item > .dd-list').each(function() {
            $(this).hide();
        });
    });
    
    // 접근 권한 미리보기 - 역할 변경
    $('#preview_role').on('change', function() {
        var selectedRole = $(this).val();
        
        // 각 메뉴 항목 체크
        $('.preview-sidebar .nav-item').each(function() {
            var menuId = $(this).data('menu-id');
            var menuItem = null;
            
            // 메뉴 ID로 해당 메뉴 찾기
            for (var i = 0; i < menuData.length; i++) {
                if (menuData[i].id == menuId) {
                    menuItem = menuData[i];
                    break;
                }
            }
            
            // 역할 접근 권한 확인
            if (menuItem && menuItem.role_access.indexOf(selectedRole) === -1) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    });
    
    // 샘플 메뉴 데이터
    var menuData = <?php echo json_encode($flatMenuList); ?>;
});
</script>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';

// 출력 버퍼 플러시
ob_end_flush();
?>