<?php
/**
 * 추첨 방송국 체크리스트 페이지
 */

// 공통 파일 포함
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "추첨 방송국 체크리스트";
$currentSection = "broadcaster";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = getDBConnection();

// 현재 진행 중인 추첨 가져오기 (샘플)
$currentDrawId = 126; // 실제로는 DB에서 조회해야 함

// 체크리스트 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_checklist'])) {
    try {
        // 트랜잭션 시작
        $db->beginTransaction();
        
        foreach ($_POST['checklist'] as $id => $status) {
            $isCompleted = isset($status['completed']) ? 1 : 0;
            $remarks = htmlspecialchars($status['remarks']);
            $completionDate = $isCompleted ? date('Y-m-d H:i:s') : NULL;
            
            // 체크리스트 업데이트 쿼리
            $stmt = $db->prepare("
                UPDATE broadcaster_checklist 
                SET is_completed = ?, remarks = ?, completion_date = ? 
                WHERE id = ?
            ");
            $stmt->execute([$isCompleted, $remarks, $completionDate, $id]);
        }
        
        // 성공적으로 수행되면 커밋
        $db->commit();
        $successMessage = "체크리스트가 성공적으로 저장되었습니다.";
    } catch (Exception $e) {
        // 오류 발생 시 롤백
        $db->rollBack();
        $errorMessage = "체크리스트 저장 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 체크리스트 아이템 추가 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    try {
        $checklistItem = htmlspecialchars($_POST['checklist_item']);
        $remarks = htmlspecialchars($_POST['remarks']);
        
        // 새 체크리스트 항목 추가
        $stmt = $db->prepare("
            INSERT INTO broadcaster_checklist (draw_id, broadcaster_id, checklist_item, is_completed, remarks, created_at)
            VALUES (?, 1, ?, 0, ?, NOW())
        ");
        $stmt->execute([$currentDrawId, $checklistItem, $remarks]);
        
        $successMessage = "새 체크리스트 항목이 추가되었습니다.";
    } catch (Exception $e) {
        $errorMessage = "체크리스트 항목 추가 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 샘플 체크리스트 데이터 - 실제로는 DB에서 가져와야 함
$checklist = [
    [
        'id' => 1,
        'item' => '추첨기 점검',
        'completed' => true,
        'remarks' => '추첨기 장비 정상 작동 확인 및 테스트 완료',
        'completion_date' => '2025-05-15'
    ],
    [
        'id' => 2,
        'item' => '카메라 세팅',
        'completed' => true,
        'remarks' => '추첨 장면 촬영을 위한 카메라 설치 및 테스트',
        'completion_date' => '2025-05-16'
    ],
    [
        'id' => 3,
        'item' => '방송 송출 테스트',
        'completed' => false,
        'remarks' => '실시간 방송 송출 테스트 및 네트워크 연결 확인',
        'completion_date' => null
    ],
    [
        'id' => 4,
        'item' => '참관인 배치',
        'completed' => false,
        'remarks' => '추첨 참관인 좌석 배치 및 안내 자료 준비',
        'completion_date' => null
    ],
    [
        'id' => 5,
        'item' => '공증인 섭외',
        'completed' => true,
        'remarks' => '추첨 공정성 확보를 위한 공증인 섭외 완료',
        'completion_date' => '2025-05-12'
    ],
    [
        'id' => 6,
        'item' => '추첨 공 검수',
        'completed' => true,
        'remarks' => '추첨 공 무게 및 크기 균일성 검사 완료',
        'completion_date' => '2025-05-14'
    ],
    [
        'id' => 7,
        'item' => '스튜디오 조명 설정',
        'completed' => true,
        'remarks' => '방송 스튜디오 조명 설정 및 테스트 완료',
        'completion_date' => '2025-05-13'
    ],
    [
        'id' => 8,
        'item' => '사회자 리허설',
        'completed' => false,
        'remarks' => '추첨 방송 사회자 리허설 및 스크립트 검토',
        'completion_date' => null
    ],
    [
        'id' => 9,
        'item' => '백업 시스템 점검',
        'completed' => true,
        'remarks' => '장비 오작동 시 백업 시스템 작동 테스트 완료',
        'completion_date' => '2025-05-15'
    ],
    [
        'id' => 10,
        'item' => '보안 요원 배치',
        'completed' => false,
        'remarks' => '추첨 행사장 보안 요원 배치 및 안전 점검',
        'completion_date' => null
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
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/index.php">외부접속감시</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/broadcaster/index.php">추첨 방송국</a></li>
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
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-check"></i> 성공!</h5>
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> 오류!</h5>
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <!-- 체크리스트 요약 -->
        <div class="row">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-check-square"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">완료된 항목</span>
                        <span class="info-box-number">
                            <?php
                            // 완료된 체크리스트 항목 수 계산
                            $completedCount = array_reduce($checklist, function($carry, $item) {
                                return $carry + ($item['completed'] ? 1 : 0);
                            }, 0);
                            echo $completedCount;
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-spinner"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">진행 중 항목</span>
                        <span class="info-box-number">
                            <?php
                            // 진행 중인 체크리스트 항목 수 계산 (샘플)
                            echo 3;
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-danger"><i class="fas fa-times-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">미완료 항목</span>
                        <span class="info-box-number">
                            <?php
                            // 미완료 체크리스트 항목 수 계산
                            $incompleteCount = count($checklist) - $completedCount;
                            echo $incompleteCount;
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-tasks"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">전체 항목</span>
                        <span class="info-box-number">
                            <?php echo count($checklist); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 체크리스트 카드 -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">제126회 추첨 준비 체크리스트</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modal-add-item">
                        <i class="fas fa-plus"></i> 항목 추가
                    </button>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <form id="checklist-form" method="post" action="">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 10px">#</th>
                                    <th>체크리스트 항목</th>
                                    <th style="width: 100px">상태</th>
                                    <th>비고</th>
                                    <th style="width: 120px">완료일</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($checklist as $index => $item): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($item['item']); ?></td>
                                    <td>
                                        <div class="icheck-primary d-inline">
                                            <input type="checkbox" id="checklistItem<?php echo $item['id']; ?>" 
                                                name="checklist[<?php echo $item['id']; ?>][completed]" 
                                                <?php echo $item['completed'] ? 'checked' : ''; ?>>
                                            <label for="checklistItem<?php echo $item['id']; ?>">완료</label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" 
                                            name="checklist[<?php echo $item['id']; ?>][remarks]" 
                                            value="<?php echo htmlspecialchars($item['remarks']); ?>">
                                    </td>
                                    <td><?php echo $item['completion_date'] ? $item['completion_date'] : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <button type="submit" name="save_checklist" class="btn btn-primary">체크리스트 저장</button>
                    </div>
                </form>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
        
        <!-- 체크리스트 진행 상황 -->
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">체크리스트 진행 상황</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <div class="progress-group">
                    <span class="progress-text">전체 진행률</span>
                    <span class="float-right">
                        <b><?php echo $completedCount; ?></b>/<?php echo count($checklist); ?>
                    </span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-primary" style="width: <?php echo (count($checklist) > 0) ? ($completedCount / count($checklist) * 100) : 0; ?>%"></div>
                    </div>
                </div>
                
                <div class="progress-group mt-4">
                    <span class="progress-text">추첨 장비 준비</span>
                    <span class="float-right"><b>3</b>/4</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-success" style="width: 75%"></div>
                    </div>
                </div>
                
                <div class="progress-group">
                    <span class="progress-text">방송 시스템 준비</span>
                    <span class="float-right"><b>2</b>/3</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-warning" style="width: 66%"></div>
                    </div>
                </div>
                
                <div class="progress-group">
                    <span class="progress-text">참관인 관련 준비</span>
                    <span class="float-right"><b>1</b>/3</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-danger" style="width: 33%"></div>
                    </div>
                </div>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

<!-- 체크리스트 항목 추가 모달 -->
<div class="modal fade" id="modal-add-item">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">새 체크리스트 항목 추가</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="checklist_item">체크리스트 항목</label>
                        <input type="text" class="form-control" id="checklist_item" name="checklist_item" required>
                    </div>
                    <div class="form-group">
                        <label for="remarks">비고</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
                    <button type="submit" name="add_item" class="btn btn-primary">항목 추가</button>
                </div>
            </form>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
