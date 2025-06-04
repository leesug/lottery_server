-- 개별 테이블에 대한 시도를 위한 SQL 스크립트

-- 이 스크립트는 기존 stores 테이블에 중복 키가 있을 때
-- REPLACE INTO를 사용하여 레코드를 대체합니다.

-- 판매점 데이터를 REPLACE를 사용하여 삽입
REPLACE INTO `stores` (`store_code`, `store_name`, `owner_name`, `email`, `phone`, 
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

-- 이후 다른 테이블도 같은 방식으로 필요한 경우 삽입 가능
