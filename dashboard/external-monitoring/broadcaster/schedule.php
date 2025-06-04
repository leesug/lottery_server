<?php
/**
 * 추첨 방송국 일정 관리 페이지
 */

// 공통 파일 포함
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "추첨 일정 관리";
$currentSection = "broadcaster";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = getDBConnection();

// 추첨 일정 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_schedule'])) {
    try {
        $scheduleTitle = htmlspecialchars($_POST['schedule_title']);
        $scheduleDate = $_POST['schedule_date'];
        $duration = intval($_POST['duration']);
        $location = htmlspecialchars($_POST['location']);
        $notes = htmlspecialchars($_POST['notes']);
        $drawId = intval($_POST['draw_id']);
        $broadcasterId = 1; // 기본값, 실제로는 선택된 방송국 ID를 사용해야 함
        
        // 새 일정 추가
        $stmt = $db->prepare("
            INSERT INTO draw_schedule (draw_id, broadcaster_id, schedule_title, schedule_date, 
                                      duration_minutes, location, status, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'scheduled', ?, NOW())
        ");
        $stmt->execute([$drawId, $broadcasterId, $scheduleTitle, $scheduleDate, $duration, $location, $notes]);
        
        $successMessage = "추첨 일정이 성공적으로 저장되었습니다.";
    } catch (Exception $e) {
        $errorMessage = "추첨 일정 저장 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 일정 상태 변경 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $scheduleId = intval($_POST['schedule_id']);
        $status = $_POST['status'];
        
        // 상태 업데이트
        $stmt = $db->prepare("
            UPDATE draw_schedule 
            SET status = ? 
            WHERE id = ?
        ");
        $stmt->execute([$status, $scheduleId]);
        
        $successMessage = "일정 상태가 변경되었습니다.";
    } catch (Exception $e) {
        $errorMessage = "상태 변경 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 샘플 일정 데이터 - 실제로는 DB에서 가져와야 함
$schedules = [
    [
        'id' => 1,
        'title' => '126회 추첨 사전 점검',
        'date' => '2025-05-19 15:00:00',
        'duration' => 120,
        'location' => '스튜디오 A',
        'status' => 'completed',
        'notes' => '추첨기 및 시스템 최종 점검, 기술 담당자 참석 필수'
    ],
    [
        'id' => 2,
        'title' => '126회 추첨 리허설',
        'date' => '2025-05-20 14:00:00',
        'duration' => 180,
        'location' => '스튜디오 A',
        'status' => 'scheduled',
        'notes' => '출연자, 진행자, 기술진 전원 참석 필수, 카메라 앵글 및 조명 최종 확인'
    ],
    [
        'id' => 3,
        'title' => '126회 추첨 방송',
        'date' => '2025-05-21 20:00:00',
        'duration' => 60,
        'location' => '스튜디오 A',
        'status' => 'scheduled',
        'notes' => '생방송 (공증인, 감시단 참석)'
    ],
    [
        'id' => 4,
        'title' => '127회 사전 미팅',
        'date' => '2025-05-26 15:00:00',
        'duration' => 90,
        'location' => '회의실 B',
        'status' => 'scheduled',
        'notes' => '127회 추첨 관련 기획 회의, 담당자 전원 참석'
    ],
    [
        'id' => 5,
        'title' => '127회 방송 장비 점검',
        'date' => '2025-05-28 13:00:00',
        'duration' => 120,
        'location' => '기술실',
        'status' => 'scheduled',
        'notes' => '방송 장비 점검 및 테스트, 기술 담당자만 참석'
    ]
];

// 현재 진행 중인 추첨 회차 정보 - 샘플 데이터
$currentDraws = [
    ['id' => 125, 'name' => '제125회 추첨'],
    ['id' => 126, 'name' => '제126회 추첨'],
    ['id' => 127, 'name' => '제127회 추첨'],
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
        
        <div class="row">
            <div class="col-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">추첨 일정 목록</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modal-add-schedule">
                                <i class="fas fa-plus"></i> 새 일정 추가
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th style="width: 50px">ID</th>
                                    <th>일정 제목</th>
                                    <th>일시</th>
                                    <th>시간</th>
                                    <th>장소</th>
                                    <th>상태</th>
                                    <th>비고</th>
                                    <th style="width: 120px">관리</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td><?php echo $schedule['id']; ?></td>
                                    <td><?php echo htmlspecialchars($schedule['title']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($schedule['date'])); ?></td>
                                    <td><?php echo $schedule['duration']; ?>분</td>
                                    <td><?php echo htmlspecialchars($schedule['location']); ?></td>
                                    <td>
                                        <?php
                                        $statusBadge = '';
                                        switch ($schedule['status']) {
                                            case 'scheduled':
                                                $statusBadge = '<span class="badge bg-info">예정</span>';
                                                break;
                                            case 'in_progress':
                                                $statusBadge = '<span class="badge bg-warning">진행중</span>';
                                                break;
                                            case 'completed':
                                                $statusBadge = '<span class="badge bg-success">완료</span>';
                                                break;
                                            case 'cancelled':
                                                $statusBadge = '<span class="badge bg-danger">취소</span>';
                                                break;
                                            default:
                                                $statusBadge = '<span class="badge bg-secondary">알 수 없음</span>';
                                        }
                                        echo $statusBadge;
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($schedule['notes']); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" 
                                                data-toggle="modal" 
                                                data-target="#modal-view-schedule" 
                                                data-id="<?php echo $schedule['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($schedule['title']); ?>"
                                                data-date="<?php echo $schedule['date']; ?>"
                                                data-duration="<?php echo $schedule['duration']; ?>"
                                                data-location="<?php echo htmlspecialchars($schedule['location']); ?>"
                                                data-status="<?php echo $schedule['status']; ?>"
                                                data-notes="<?php echo htmlspecialchars($schedule['notes']); ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-warning" 
                                                data-toggle="modal" 
                                                data-target="#modal-change-status" 
                                                data-id="<?php echo $schedule['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($schedule['title']); ?>"
                                                data-status="<?php echo $schedule['status']; ?>">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
        
        <!-- 캘린더 뷰 -->
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">일정 캘린더</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- THE CALENDAR -->
                        <div id="calendar"></div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

<!-- 새 일정 추가 모달 -->
<div class="modal fade" id="modal-add-schedule">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">새 추첨 일정 추가</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="draw_id">추첨 회차</label>
                        <select class="form-control" id="draw_id" name="draw_id" required>
                            <?php foreach ($currentDraws as $draw): ?>
                                <option value="<?php echo $draw['id']; ?>"><?php echo htmlspecialchars($draw['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="schedule_title">일정 제목</label>
                        <input type="text" class="form-control" id="schedule_title" name="schedule_title" required>
                    </div>
                    <div class="form-group">
                        <label for="schedule_date">일시</label>
                        <input type="datetime-local" class="form-control" id="schedule_date" name="schedule_date" required>
                    </div>
                    <div class="form-group">
                        <label for="duration">예상 소요 시간 (분)</label>
                        <input type="number" class="form-control" id="duration" name="duration" min="15" value="60" required>
                    </div>
                    <div class="form-group">
                        <label for="location">장소</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                    </div>
                    <div class="form-group">
                        <label for="notes">비고</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
                    <button type="submit" name="save_schedule" class="btn btn-primary">일정 저장</button>
                </div>
            </form>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<!-- 일정 상세 보기 모달 -->
<div class="modal fade" id="modal-view-schedule">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">일정 상세 정보</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-4">일정 제목:</dt>
                    <dd class="col-sm-8" id="view-title"></dd>
                    
                    <dt class="col-sm-4">일시:</dt>
                    <dd class="col-sm-8" id="view-date"></dd>
                    
                    <dt class="col-sm-4">소요 시간:</dt>
                    <dd class="col-sm-8"><span id="view-duration"></span>분</dd>
                    
                    <dt class="col-sm-4">장소:</dt>
                    <dd class="col-sm-8" id="view-location"></dd>
                    
                    <dt class="col-sm-4">상태:</dt>
                    <dd class="col-sm-8" id="view-status"></dd>
                    
                    <dt class="col-sm-4">비고:</dt>
                    <dd class="col-sm-8" id="view-notes"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<!-- 상태 변경 모달 -->
<div class="modal fade" id="modal-change-status">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">일정 상태 변경</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="status-schedule-id" name="schedule_id">
                    <p>다음 일정의 상태를 변경합니다: <strong id="status-title"></strong></p>
                    <div class="form-group">
                        <label for="status">새 상태</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="scheduled">예정</option>
                            <option value="in_progress">진행 중</option>
                            <option value="completed">완료</option>
                            <option value="cancelled">취소</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
                    <button type="submit" name="update_status" class="btn btn-primary">상태 변경</button>
                </div>
            </form>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<!-- 추가 스크립트 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 모달 데이터 설정
    $('#modal-view-schedule').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var title = button.data('title');
        var date = new Date(button.data('date'));
        var duration = button.data('duration');
        var location = button.data('location');
        var status = button.data('status');
        var notes = button.data('notes');
        
        var statusText = '';
        switch (status) {
            case 'scheduled': statusText = '<span class="badge bg-info">예정</span>'; break;
            case 'in_progress': statusText = '<span class="badge bg-warning">진행중</span>'; break;
            case 'completed': statusText = '<span class="badge bg-success">완료</span>'; break;
            case 'cancelled': statusText = '<span class="badge bg-danger">취소</span>'; break;
            default: statusText = '<span class="badge bg-secondary">알 수 없음</span>';
        }
        
        var modal = $(this);
        modal.find('#view-title').text(title);
        modal.find('#view-date').text(date.toLocaleString());
        modal.find('#view-duration').text(duration);
        modal.find('#view-location').text(location);
        modal.find('#view-status').html(statusText);
        modal.find('#view-notes').text(notes);
    });
    
    $('#modal-change-status').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var title = button.data('title');
        var status = button.data('status');
        
        var modal = $(this);
        modal.find('#status-schedule-id').val(id);
        modal.find('#status-title').text(title);
        modal.find('#status').val(status);
    });
    
    // 캘린더 초기화
    var calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            themeSystem: 'bootstrap',
            events: [
                <?php foreach ($schedules as $schedule): ?>
                {
                    id: '<?php echo $schedule['id']; ?>',
                    title: '<?php echo addslashes($schedule['title']); ?>',
                    start: '<?php echo $schedule['date']; ?>',
                    end: '<?php 
                        $endDate = new DateTime($schedule['date']);
                        $endDate->add(new DateInterval('PT' . $schedule['duration'] . 'M'));
                        echo $endDate->format('Y-m-d H:i:s');
                    ?>',
                    backgroundColor: '<?php 
                        switch ($schedule['status']) {
                            case 'scheduled': echo '#17a2b8'; break; // info
                            case 'in_progress': echo '#ffc107'; break; // warning
                            case 'completed': echo '#28a745'; break; // success
                            case 'cancelled': echo '#dc3545'; break; // danger
                            default: echo '#6c757d'; // secondary
                        }
                    ?>',
                    borderColor: '<?php 
                        switch ($schedule['status']) {
                            case 'scheduled': echo '#17a2b8'; break;
                            case 'in_progress': echo '#ffc107'; break;
                            case 'completed': echo '#28a745'; break;
                            case 'cancelled': echo '#dc3545'; break;
                            default: echo '#6c757d';
                        }
                    ?>',
                    allDay: false
                },
                <?php endforeach; ?>
            ],
            eventClick: function(info) {
                // 이벤트 클릭 시 상세 정보 모달 표시
                var id = info.event.id;
                var title = info.event.title;
                var start = info.event.start;
                var end = info.event.end;
                var status = '';
                
                // 상태 결정 (배경색으로 판단)
                var bgColor = info.event.backgroundColor;
                switch (bgColor) {
                    case '#17a2b8': status = 'scheduled'; break;
                    case '#ffc107': status = 'in_progress'; break;
                    case '#28a745': status = 'completed'; break;
                    case '#dc3545': status = 'cancelled'; break;
                    default: status = '';
                }
                
                // 해당 일정 찾기
                <?php echo "var schedules = " . json_encode($schedules) . ";"; ?>
                var schedule = schedules.find(function(s) { return s.id == id; });
                
                if (schedule) {
                    var modal = $('#modal-view-schedule');
                    var statusText = '';
                    switch (schedule.status) {
                        case 'scheduled': statusText = '<span class="badge bg-info">예정</span>'; break;
                        case 'in_progress': statusText = '<span class="badge bg-warning">진행중</span>'; break;
                        case 'completed': statusText = '<span class="badge bg-success">완료</span>'; break;
                        case 'cancelled': statusText = '<span class="badge bg-danger">취소</span>'; break;
                        default: statusText = '<span class="badge bg-secondary">알 수 없음</span>';
                    }
                    
                    modal.find('#view-title').text(schedule.title);
                    modal.find('#view-date').text(new Date(schedule.date).toLocaleString());
                    modal.find('#view-duration').text(schedule.duration);
                    modal.find('#view-location').text(schedule.location);
                    modal.find('#view-status').html(statusText);
                    modal.find('#view-notes').text(schedule.notes);
                    modal.modal('show');
                }
            }
        });
        calendar.render();
    }
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
