<?php
/**
 * 날짜 관련 헬퍼 함수
 */

/**
 * 날짜/시간을 포맷팅합니다.
 * 
 * @param string $dateTime 포맷팅할 날짜/시간 문자열
 * @param string $format 원하는 날짜/시간 형식 (선택 사항)
 * @return string 포맷팅된 날짜/시간
 */
function formatDateTime($dateTime, $format = 'Y-m-d H:i:s') {
    if (empty($dateTime)) return '-';
    $timestamp = strtotime($dateTime);
    return date($format, $timestamp);
}
