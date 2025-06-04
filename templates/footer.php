            </div><!-- .dashboard-content -->
        </div><!-- .main-content -->
    </div><!-- .dashboard-layout -->
    
    <script src="/server/assets/js/common.js"></script>
    <?php if (isset($extraJs)): ?>
        <script src="<?php echo $extraJs; ?>"></script>
    <?php endif; ?>
    
    <?php if (isset($inlineJs)): ?>
        <script>
            <?php echo $inlineJs; ?>
        </script>
    <?php endif; ?>
    
    <!-- 세션 타임아웃 관리 스크립트 -->
    <script>
        // 세션 타임아웃 관리
        (function() {
            // 세션 타임아웃 시간 (밀리초)
            const sessionTimeout = <?php echo SESSION_TIMEOUT * 1000; ?>;
            // 경고 표시 시간 (밀리초, 타임아웃 1분 전)
            const warningTime = 60000;
            // 세션 연장 AJAX 호출 간격 (밀리초, 5분)
            const refreshInterval = 300000;
            
            let timeoutId;
            let warningId;
            
            // 세션 타이머 재설정
            function resetSessionTimer() {
                clearTimeout(timeoutId);
                clearTimeout(warningId);
                
                // 경고 타이머 설정
                warningId = setTimeout(function() {
                    showTimeoutWarning();
                }, sessionTimeout - warningTime);
                
                // 세션 만료 타이머 설정
                timeoutId = setTimeout(function() {
                    window.location.href = '/server/pages/logout.php';
                }, sessionTimeout);
            }
            
            // 세션 연장
            function extendSession() {
                fetch('/server/api/session/extend.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'csrf_token=<?php echo SecurityManager::generateCsrfToken(); ?>'
                })
                .then(response => response.json())
                .then(data => {
                    console.log('세션이 연장되었습니다.');
                })
                .catch(error => {
                    console.error('세션 연장 실패:', error);
                });
                
                resetSessionTimer();
            }
            
            // 타임아웃 경고 표시
            function showTimeoutWarning() {
                // 경고 표시 (간단한 경고 메시지)
                if (confirm('세션이 곧 만료됩니다. 계속 작업하시겠습니까?')) {
                    extendSession();
                }
            }
            
            // 초기 타이머 설정
            resetSessionTimer();
            
            // 주기적인 세션 연장
            setInterval(extendSession, refreshInterval);
            
            // 사용자 활동 감지
            document.addEventListener('click', extendSession);
            document.addEventListener('keypress', extendSession);
            
            // 전역 함수로 등록
            window.extendSession = extendSession;
        })();
    </script>
</body>
</html>
