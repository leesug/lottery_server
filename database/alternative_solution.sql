-- DELETE 명령어를 사용하여 테이블 데이터를 삭제하는 스크립트
-- TRUNCATE보다 더 안전한 방법입니다

-- 외래 키 제약 조건 비활성화 (확실히 적용되는지 확인용)
SET FOREIGN_KEY_CHECKS = 0;

-- 관계를 고려하여 순서대로 데이터 삭제
DELETE FROM equipment_maintenance;
DELETE FROM equipment;
DELETE FROM store_settlements;
DELETE FROM store_performance;
DELETE FROM contracts;
DELETE FROM stores;

-- 외래 키 제약 조건 활성화
SET FOREIGN_KEY_CHECKS = 1;

-- 테스트용 판매점 데이터 삽입
INSERT INTO `stores` (`store_code`, `store_name`, `owner_name`, `email`, `phone`, 
                     `address`, `city`, `state`, `postal_code`, `country`, 
                     `status`, `store_category`, `store_size`, `registration_date`) 
VALUES 
('STORE12345678', '네팔 마트 #23', '김철수', 'test@example.com', '01012345678',
 '판매점 주소', '서울', '서울특별시', '12345', '대한민국',
 'active', 'standard', 'medium', NOW()),
('STORE23456789', '카트만두 센터 #05', '이영희', 'test2@example.com', '01098765432',
 '판매점 주소 2', '부산', '부산광역시', '54321', '대한민국',
 'active', 'premium', 'large', NOW()),
('STORE34567890', '포카라 샵 #18', '박지민', 'test3@example.com', '01011112222',
 '판매점 주소 3', '대구', '대구광역시', '33333', '대한민국',
 'active', 'standard', 'small', NOW());

-- 테스트용 계약 데이터 삽입
INSERT INTO `contracts` (`store_id`, `contract_code`, `contract_type`, 
                        `start_date`, `end_date`, `status`, `commission_rate`, 
                        `signing_bonus`, `signed_by`, `signed_date`)
VALUES
(1, 'CONTRACT001', 'standard', '2025-01-01', '2025-12-31', 'active', 5.00, 
 10000.00, '김관리자', '2024-12-15'),
(2, 'CONTRACT002', 'premium', '2025-02-01', '2026-01-31', 'active', 7.50, 
 15000.00, '이관리자', '2025-01-15'),
(3, 'CONTRACT003', 'standard', '2025-03-01', '2026-02-28', 'active', 5.00, 
 10000.00, '박관리자', '2025-02-15');

-- 테스트용 장비 데이터 삽입
INSERT INTO `equipment` (`store_id`, `equipment_code`, `equipment_type`, 
                        `model`, `serial_number`, `manufacturer`,
                        `purchase_date`, `status`, `software_version`)
VALUES
(1, 'EQUIP001', 'terminal', 'LT-2000', 'SN123456789', '로또테크',
 '2025-01-10', 'active', '2.1.3'),
(2, 'EQUIP002', 'printer', 'LP-1000', 'SN234567890', '로또테크',
 '2025-01-20', 'active', '1.5.2'),
(3, 'EQUIP003', 'terminal', 'LT-2000', 'SN345678901', '로또테크',
 '2025-02-05', 'active', '2.1.3');

-- 테스트용 장비 유지보수 데이터 삽입
INSERT INTO `equipment_maintenance` (`equipment_id`, `maintenance_code`, `maintenance_type`,
                                  `start_date`, `end_date`, `status`,
                                  `technician_name`, `technician_contact`, `cost`,
                                  `actions_taken`, `result`)
VALUES
(1, 'MAINT001', 'regular', '2025-03-15 10:00:00', '2025-03-15 11:30:00', 'completed',
 '정비사1', '010-1234-5678', 50000.00,
 '정기 유지보수 수행', '정상 작동 확인'),
(2, 'MAINT002', 'repair', '2025-03-20 14:00:00', '2025-03-20 16:00:00', 'completed',
 '정비사2', '010-2345-6789', 120000.00,
 '프린터 헤드 교체', '인쇄 품질 복구'),
(3, 'MAINT003', 'regular', '2025-04-10 09:00:00', NULL, 'scheduled',
 '정비사1', '010-1234-5678', 50000.00,
 '정기 유지보수 예정', NULL);

-- 데이터 확인
SELECT 'stores' AS table_name, COUNT(*) AS record_count FROM stores
UNION ALL
SELECT 'contracts' AS table_name, COUNT(*) AS record_count FROM contracts
UNION ALL
SELECT 'equipment' AS table_name, COUNT(*) AS record_count FROM equipment
UNION ALL
SELECT 'equipment_maintenance' AS table_name, COUNT(*) AS record_count FROM equipment_maintenance;
