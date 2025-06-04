@echo off
echo 복권 관리 시스템 디렉토리 구조 생성 중...

REM 대시보드 내 기능별 폴더 생성
mkdir "C:\xampp\htdocs\server\dashboard\lottery"
mkdir "C:\xampp\htdocs\server\dashboard\sales"
mkdir "C:\xampp\htdocs\server\dashboard\settlement"
mkdir "C:\xampp\htdocs\server\dashboard\deposit"
mkdir "C:\xampp\htdocs\server\dashboard\sales-manage"
mkdir "C:\xampp\htdocs\server\dashboard\fund"
mkdir "C:\xampp\htdocs\server\dashboard\government"
mkdir "C:\xampp\htdocs\server\dashboard\terminals"
mkdir "C:\xampp\htdocs\server\dashboard\reports"
mkdir "C:\xampp\htdocs\server\dashboard\api"

echo 디렉토리 구조가 성공적으로 생성되었습니다.
echo.
echo 폴더 구조:
echo - C:\xampp\htdocs\server\dashboard\lottery (복권 관리)
echo - C:\xampp\htdocs\server\dashboard\sales (판매 관리)
echo - C:\xampp\htdocs\server\dashboard\settlement (정산 관리)
echo - C:\xampp\htdocs\server\dashboard\deposit (입금 관리)
echo - C:\xampp\htdocs\server\dashboard\sales-manage (영업 관리)
echo - C:\xampp\htdocs\server\dashboard\fund (기금 관리)
echo - C:\xampp\htdocs\server\dashboard\government (정부 감시)
echo - C:\xampp\htdocs\server\dashboard\terminals (단말기 관리)
echo - C:\xampp\htdocs\server\dashboard\reports (보고서)
echo - C:\xampp\htdocs\server\dashboard\api (API 관리)
echo.
pause
