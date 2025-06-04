<?php
/**
 * 세션 활동 시간을 갱신합니다.
 * 
 * 이 함수는 사용자의 마지막 활동 시간을 현재 시간으로 업데이트합니다.
 * 세션 만료 시간을 연장하는 데 사용됩니다.
 * 
 * @return void
 */
function updateSessionActivity() {
    $_SESSION['last_activity'] = time();
}
