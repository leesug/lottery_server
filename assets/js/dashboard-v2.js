/**
 * 종합 대시보드 기능 v2
 */

// 서버 URL 설정 (PHP에서 정의된 값 사용)
var SERVER_URL = '';

// 문서 준비 완료시 실행
$(document).ready(function() {
    console.log('대시보드 초기화 중...');
    
    // 서버 URL 가져오기
    SERVER_URL = $('meta[name="server-url"]').attr('content') || '';
    console.log('서버 URL:', SERVER_URL);
    
    // 대시보드 초기화
    initDashboard();
    
    // 차트 초기화
    initCharts();
    
    // 탭 변경 이벤트 리스너
    $('#dashboard-tabs a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        console.log('탭 변경됨: ' + e.target.id);
        let tabId = e.target.id;
        let contentId = $(e.target).attr('aria-controls');
        
        // 탭이 처음 활성화되었을 때 콘텐츠 로드
        loadTabContent(tabId, contentId);
    });
});

/**
 * 대시보드 초기화 함수
 */
function initDashboard() {
    console.log('대시보드 UI 초기화...');
    
    // 스크롤 가능한 탭 네비게이션 설정
    initScrollableTabs();
    
    // 첫 로드 시 종합 대시보드 외의 모든 탭은 콘텐츠를 로드하지 않음
    // 종합 대시보드는 index.php에 직접 포함되어 있음
}

/**
 * 스크롤 가능한 탭 네비게이션 설정
 */
function initScrollableTabs() {
    console.log('스크롤 가능한 탭 초기화...');
    
    // 가로 스크롤 활성화
    $('.nav-tabs-scroll').css({
        'overflow-x': 'auto',
        'overflow-y': 'hidden',
        'flex-wrap': 'nowrap',
        'display': 'block',
        'white-space': 'nowrap',
        '-webkit-overflow-scrolling': 'touch'
    });
    
    // 탭 아이템 표시 설정
    $('.nav-tabs-scroll .nav-item').css({
        'display': 'inline-block',
        'float': 'none'
    });
    
    // 오른쪽/왼쪽 스크롤 버튼 추가
    if ($('.tab-scroll-buttons').length === 0) {
        $('.nav-tabs-scroll').before(
            '<div class="tab-scroll-buttons">' +
            '<button type="button" class="btn btn-sm btn-default tab-scroll-left">' +
            '<i class="fas fa-chevron-left"></i></button>' +
            '<button type="button" class="btn btn-sm btn-default tab-scroll-right">' +
            '<i class="fas fa-chevron-right"></i></button>' +
            '</div>'
        );
        
        // 스크롤 버튼 이벤트 연결
        $('.tab-scroll-left').on('click', function() {
            $('.nav-tabs-scroll').animate({ scrollLeft: '-=200' }, 300);
        });
        
        $('.tab-scroll-right').on('click', function() {
            $('.nav-tabs-scroll').animate({ scrollLeft: '+=200' }, 300);
        });
    }
}

/**
 * 차트 초기화
 */
function initCharts() {
    console.log('차트 초기화...');
    
    // Chart.js 라이브러리 로드 확인
    if (typeof Chart === 'undefined') {
        console.error('Chart.js 라이브러리가 로드되지 않았습니다. 차트를 초기화할 수 없습니다.');
        return;
    }
    
    // 판매 추이 차트
    if ($('#salesChart').length > 0) {
        try {
            let salesChartCanvas = $('#salesChart').get(0).getContext('2d');
            
            let salesChartData = {
                labels: ['1월', '2월', '3월', '4월', '5월'],
                datasets: [
                    {
                        label: '2024년',
                        backgroundColor: 'rgba(60,141,188,0.3)',
                        borderColor: 'rgba(60,141,188,1)',
                        pointRadius: 4,
                        pointColor: '#3b8bba',
                        pointStrokeColor: 'rgba(60,141,188,1)',
                        pointHighlightFill: '#fff',
                        pointHighlightStroke: 'rgba(60,141,188,1)',
                        fill: true,
                        data: [28000, 29000, 33000, 36000, 31000]
                    },
                    {
                        label: '2023년',
                        backgroundColor: 'rgba(210, 214, 222, 0.3)',
                        borderColor: 'rgba(210, 214, 222, 1)',
                        pointRadius: 4,
                        pointColor: 'rgba(210, 214, 222, 1)',
                        pointStrokeColor: '#c1c7d1',
                        pointHighlightFill: '#fff',
                        pointHighlightStroke: 'rgba(220,220,220,1)',
                        fill: true,
                        data: [25000, 27000, 25000, 28000, 29000]
                    }
                ]
            };
            
            let salesChartOptions = {
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            };
            
            createChart('salesChart', 'line', salesChartData, salesChartOptions);
        } catch (error) {
            console.error('판매 추이 차트 생성 중 오류:', error);
        }
    }
    
    // 당첨금 지급 현황 차트
    if ($('#prizeChart').length > 0) {
        try {
            let prizeChartData = {
                labels: ['1등', '2등', '3등', '4등', '5등'],
                datasets: [
                    {
                        label: '당첨금 지급 (백만)',
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(255, 206, 86, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(153, 102, 255, 0.6)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1,
                        data: [350, 180, 120, 80, 40]
                    }
                ]
            };
            
            let prizeChartOptions = {
                maintainAspectRatio: false,
                responsive: true,
            };
            
            createChart('prizeChart', 'bar', prizeChartData, prizeChartOptions);
        } catch (error) {
            console.error('당첨금 지급 현황 차트 생성 중 오류:', error);
        }
    }
}

/**
 * 탭 콘텐츠 로드 함수
 * @param {string} tabId - 활성화된 탭의 ID
 * @param {string} contentId - 활성화된 탭의 콘텐츠 ID
 */
function loadTabContent(tabId, contentId) {
    console.log('탭 콘텐츠 로드: ' + tabId + ' -> ' + contentId);
    
    // 이미 로드된 콘텐츠인지 확인
    if ($('#' + contentId).data('loaded')) {
        console.log('이미 로드된 콘텐츠입니다.');
        return;
    }
    
    // 종합 대시보드 탭은 이미 로드되어 있음
    if (tabId === 'tab-main') {
        console.log('종합 대시보드는 이미 로드되어 있습니다.');
        $('#' + contentId).data('loaded', true);
        return;
    }
    
    // 탭 ID에서 타입 추출 (예: tab-lottery -> lottery)
    const dashboardType = tabId.replace('tab-', '');
    
    // API 엔드포인트를 사용하여 콘텐츠만 로드
    const dashboardPath = SERVER_URL + '/dashboard/api/get-dashboard-content.php?type=' + dashboardType;
    
    // 대시보드 경로 확인
    if (!dashboardType) {
        console.error('대시보드 유형을 파악할 수 없습니다: ' + tabId);
        
        // 오류 메시지 표시
        $('#' + contentId).html(
            '<div class="alert alert-warning">' +
            '<h5><i class="icon fas fa-exclamation-triangle"></i> 알림</h5>' +
            '해당 대시보드를 찾을 수 없습니다. 경로: ' + tabId + '</div>'
        ).data('loaded', true);
        
        return;
    }
    
    // AJAX로 대시보드 콘텐츠 로드
    console.log('AJAX 요청: ' + dashboardPath);
    $.ajax({
        url: dashboardPath,
        type: 'GET',
        dataType: 'html',
        timeout: 10000, // 10초 타임아웃 설정
        beforeSend: function() {
            // 로딩 표시
            $('#' + contentId).html(
                '<div class="dashboard-loader text-center py-5">' +
                '<i class="fas fa-spinner fa-spin fa-3x"></i>' +
                '<p class="mt-3">' + tabId.replace('tab-', '') + ' 대시보드를 로드 중입니다...</p>' +
                '</div>'
            );
        },
        success: function(response) {
            console.log('대시보드 콘텐츠 로드 성공');
            
            // HTML 내용 필터링 (중복 메뉴, 헤더, 푸터 제거)
            let filteredContent = response;
            
            // HTML 문서 구조 속성(meta, title 등) 및 중복 스크립트 제거
            filteredContent = filteredContent.replace(/<html[\s\S]*?<body[^>]*>/gi, '');
            filteredContent = filteredContent.replace(/<\/body>[\s\S]*?<\/html>/gi, '');
            
            // 헤더, 네비게이션 바, 사이드바 등 제거
            filteredContent = filteredContent.replace(/<nav[\s\S]*?<\/nav>/gi, '');
            filteredContent = filteredContent.replace(/<aside[\s\S]*?<\/aside>/gi, '');
            filteredContent = filteredContent.replace(/<header[\s\S]*?<\/header>/gi, '');
            filteredContent = filteredContent.replace(/<footer[^>]*>[\s\S]*?<\/footer>/gi, '');
            
            // wrapper 태그 제거
            filteredContent = filteredContent.replace(/<div class="wrapper"[^>]*>/gi, '');
            filteredContent = filteredContent.replace(/<\/div><!-- \.\/wrapper -->/gi, '');
            
            // content-wrapper 태그 제거
            filteredContent = filteredContent.replace(/<div class="content-wrapper"[^>]*>/gi, '');
            filteredContent = filteredContent.replace(/<\/div><!-- \/\.content-wrapper -->/gi, '');
            
            // 스크립트 및 중복 CSS 제거 (필요한 경우)
            filteredContent = filteredContent.replace(/<script[\s\S]*?<\/script>/gi, '');
            filteredContent = filteredContent.replace(/<link[^>]*>/gi, '');
            
            // 내용만 포함
            $('#' + contentId).html(filteredContent).data('loaded', true);
            
            // 로드된 콘텐츠에 필요한 초기화 작업
            initLoadedContent(contentId);
        },
        error: function(xhr, status, error) {
            console.error('대시보드 콘텐츠 로드 실패: ' + error);
            console.log('상태: ' + status);
            console.log('HTTP 상태 코드: ' + xhr.status);
            
            let errorMessage = '알 수 없는 오류가 발생했습니다.';
            
            if (xhr.status === 404) {
                errorMessage = '요청한 대시보드 파일을 찾을 수 없습니다. (404)';
            } else if (xhr.status === 500) {
                errorMessage = '서버 내부 오류가 발생했습니다. (500)';
            } else if (status === 'timeout') {
                errorMessage = '요청 시간이 초과되었습니다. 서버 부하가 높거나 네트워크 문제가 있을 수 있습니다.';
            } else if (status === 'parsererror') {
                errorMessage = '서버 응답을 처리하는 중 오류가 발생했습니다.';
            }
            
            $('#' + contentId).html(
                '<div class="alert alert-danger">' +
                '<h5><i class="icon fas fa-ban"></i> 오류 발생!</h5>' +
                '<p>' + errorMessage + '</p>' +
                '<p>자세한 오류: ' + error + '</p>' +
                '<button class="btn btn-sm btn-outline-danger mt-2 retry-load" data-tab-id="' + tabId + '" data-content-id="' + contentId + '">' +
                '<i class="fas fa-redo"></i> 다시 시도</button>' +
                '</div>'
            ).data('loaded', false);
            
            // 다시 시도 버튼 이벤트 연결
            $('.retry-load').on('click', function() {
                let retryTabId = $(this).data('tab-id');
                let retryContentId = $(this).data('content-id');
                
                // loaded 상태 초기화
                $('#' + retryContentId).data('loaded', false);
                
                // 다시 로드
                loadTabContent(retryTabId, retryContentId);
            });
        }
    });
}

/**
 * 로드된 콘텐츠 초기화
 * @param {string} contentId - 로드된 콘텐츠의 ID
 */
function initLoadedContent(contentId) {
    console.log('로드된 콘텐츠 초기화: ' + contentId);
    
    // 차트 초기화
    if ($('#' + contentId + ' .chart').length > 0) {
        console.log('로드된 콘텐츠의 차트 초기화');
        
        // 각 차트 요소 초기화
        $('#' + contentId + ' .chart canvas').each(function() {
            var canvasId = $(this).attr('id');
            if (canvasId) {
                // 차트 객체가 이미 존재하는지 확인
                if (window[canvasId + 'Chart']) {
                    window[canvasId + 'Chart'].destroy();
                }
                
                // 차트 초기화 함수 호출 (외부 스크립트에서 정의된 함수 호출)
                if (typeof window['init' + canvasId.charAt(0).toUpperCase() + canvasId.slice(1) + 'Chart'] === 'function') {
                    window['init' + canvasId.charAt(0).toUpperCase() + canvasId.slice(1) + 'Chart']();
                }
            }
        });
    }
    
    // 데이터테이블 초기화
    if ($('#' + contentId + ' .datatable').length > 0) {
        console.log('로드된 콘텐츠의 데이터테이블 초기화');
        
        // DataTable 라이브러리 로드 확인
        if ($.fn.DataTable) {
            $('#' + contentId + ' .datatable').each(function() {
                if (!$.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable({
                        "responsive": true,
                        "autoWidth": false,
                        "language": {
                            "url": SERVER_URL + "/assets/plugins/datatables/ko.json"
                        }
                    });
                }
            });
        } else {
            console.warn('DataTable 라이브러리가 로드되지 않았습니다.');
        }
    }
    
    // 툴팁 초기화
    $('#' + contentId + ' [data-toggle="tooltip"]').tooltip();
    
    // 팝오버 초기화
    $('#' + contentId + ' [data-toggle="popover"]').popover();
    
    // 폼 유효성 검사 초기화
    $('#' + contentId + ' form.needs-validation').on('submit', function(event) {
        if (this.checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        $(this).addClass('was-validated');
    });
}

/**
 * 차트 생성 함수
 * @param {string} canvasId - 차트를 그릴 캔버스 요소의 ID
 * @param {string} type - 차트 유형 (line, bar, pie 등)
 * @param {object} data - 차트 데이터
 * @param {object} options - 차트 옵션
 */
function createChart(canvasId, type, data, options) {
    // 이전 차트 인스턴스 파괴
    if (window[canvasId + 'Chart']) {
        window[canvasId + 'Chart'].destroy();
    }
    
    // 차트 생성
    var ctx = document.getElementById(canvasId).getContext('2d');
    window[canvasId + 'Chart'] = new Chart(ctx, {
        type: type,
        data: data,
        options: options
    });
    
    return window[canvasId + 'Chart'];
}
