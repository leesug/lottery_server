<?php
// 오류 표시 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 헤더 설정
header('Content-Type: text/html; charset=utf-8');

// 간단한 출력
echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
echo "    <title>테스트 페이지</title>\n";
echo "</head>\n";
echo "<body>\n";
echo "    <h1>PHP 테스트 페이지</h1>\n";
echo "    <p>PHP 버전: " . phpversion() . "</p>\n";
echo "    <p>현재 시간: " . date('Y-m-d H:i:s') . "</p>\n";
echo "</body>\n";
echo "</html>\n";
?>
