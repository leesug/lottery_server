<?php
/**
 * 시스템 통화 설정
 * 로또 시스템에서 사용되는 통화 관련 설정입니다.
 */

// 통화 타입 설정
define('CURRENCY_CODE', 'INR'); // 인도 루피
define('CURRENCY_SYMBOL', '₹'); // 루피 기호

// 통화 형식 설정
define('DECIMAL_PLACES', 2); // 소수점 자리수
define('THOUSANDS_SEPARATOR', ','); // 천 단위 구분자
define('DECIMAL_SEPARATOR', '.'); // 소수점 구분자

/**
 * 금액을 형식화하는 함수
 * 
 * @param float $amount 형식화할 금액
 * @param bool $includeSymbol 통화 기호 포함 여부 (기본값: true)
 * @return string 형식화된 금액 문자열
 */
function formatCurrency($amount, $includeSymbol = true) {
    $formattedAmount = number_format(
        $amount, 
        DECIMAL_PLACES, 
        DECIMAL_SEPARATOR, 
        THOUSANDS_SEPARATOR
    );
    
    if ($includeSymbol) {
        return CURRENCY_SYMBOL . $formattedAmount;
    } else {
        return $formattedAmount;
    }
}
