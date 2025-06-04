<?php
/**
 * 모의 PDO 데이터베이스 연결 클래스
 * 실제 데이터베이스 작업 없이 UI 테스트를 위한 PDO 스타일의 클래스
 */

// MYSQLI 상수 정의 (PDO와의 호환성을 위함)
if (!defined('MYSQLI_ASSOC')) {
    define('MYSQLI_ASSOC', 1);
    define('MYSQLI_NUM', 2);
    define('MYSQLI_BOTH', 3);
}

class MockPDO {
    /**
     * SQL 쿼리를 준비하는 메소드
     * 
     * @param string $query SQL 쿼리 문자열
     * @return MockPDOStatement 준비된 쿼리 객체
     */
    public function prepare($query) {
        return new MockPDOStatement();
    }
    
    /**
     * 마지막 삽입 ID를 반환하는 메소드
     * 
     * @param string $name 시퀀스 이름 (사용되지 않음)
     * @return int 항상 1 반환
     */
    public function lastInsertId($name = null) {
        return 1;
    }
    
    /**
     * 트랜잭션을 시작하는 메소드
     * 
     * @return bool 항상 true 반환
     */
    public function beginTransaction() {
        return true;
    }
    
    /**
     * 트랜잭션을 커밋하는 메소드
     * 
     * @return bool 항상 true 반환
     */
    public function commit() {
        return true;
    }
    
    /**
     * 트랜잭션을 롤백하는 메소드
     * 
     * @return bool 항상 true 반환
     */
    public function rollBack() {
        return true;
    }
    
    /**
     * SQL 쿼리를 직접 실행하는 메소드
     * 
     * @param string $query SQL 쿼리 문자열
     * @return bool 항상 true 반환
     */
    public function exec($query) {
        return true;
    }
    
    /**
     * SQL 쿼리를 실행하고 결과 객체를 반환하는 메소드
     * 
     * @param string $query SQL 쿼리 문자열
     * @return MockPDOStatement 쿼리 결과 객체
     */
    public function query($query) {
        return new MockPDOStatement();
    }
    
    /**
     * 메소드 체이닝을 위한 자신을 반환하는 메소드
     * 
     * @param int $attribute 속성 (사용되지 않음)
     * @param mixed $value 값 (사용되지 않음)
     * @return $this 자신을 반환
     */
    public function setAttribute($attribute, $value) {
        return $this;
    }
}

/**
 * 모의 PDO 준비된 쿼리 클래스
 */
class MockPDOStatement {
    private $query_type = '';
    public $num_rows = 0; // mysqli 스타일 호환성을 위한 num_rows 속성 추가
    
    /**
     * 준비된 쿼리를 실행하는 메소드
     * 
     * @param array $params 파라미터 배열
     * @return bool 항상 true 반환
     */
    public function execute($params = []) {
        return true;
    }
    
    /**
     * 결과를 연관 배열로 반환하는 메소드
     * 
     * @param int $fetch_style 결과 스타일 (사용되지 않음)
     * @return array 테스트 데이터 배열
     */
    public function fetchAll($fetch_style = PDO::FETCH_ASSOC) {
        // 테스트 데이터 반환
        global $query_type;
        
        // 판매 이력 테스트 데이터
        if ($query_type && strpos($query_type, 'sales_history') !== false) {
            return [
                [
                    'id' => 1,
                    'ticket_number' => 'T123456789',
                    'numbers' => '1,7,19,23,34,45',
                    'price' => 1000,
                    'status' => 'sold',
                    'created_at' => '2025-05-10 15:30:45',
                    'product_name' => '로또 6/45',
                    'store_name' => '행운복권방',
                    'store_code' => 'ST12345',
                    'region_name' => '서울',
                    'terminal_code' => 'TR7890'
                ],
                [
                    'id' => 2,
                    'ticket_number' => 'T123456790',
                    'numbers' => '5,13,20,25,37,42',
                    'price' => 1000,
                    'status' => 'sold',
                    'created_at' => '2025-05-11 09:15:22',
                    'product_name' => '로또 6/45',
                    'store_name' => '행운복권방',
                    'store_code' => 'ST12345',
                    'region_name' => '서울',
                    'terminal_code' => 'TR7890'
                ]
            ];
        }
        // 계약 목록 테스트 데이터
        else if ($query_type && strpos($query_type, 'contracts_list') !== false) {
            $currentDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime('-1 month'));
            $endDate = date('Y-m-d', strtotime('+11 months'));
            
            return [
                [
                    'id' => 1,
                    'store_id' => 1,
                    'contract_code' => 'CONTRACT001',
                    'store_name' => '네팔 마트 #23',
                    'owner_name' => '김철수',
                    'contract_type' => 'standard',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'active',
                    'commission_rate' => 5.00,
                    'signing_bonus' => 10000.00,
                    'signed_by' => '김관리자',
                    'signed_date' => $startDate,
                    'created_at' => $startDate . ' 10:00:00'
                ],
                [
                    'id' => 2,
                    'store_id' => 2,
                    'contract_code' => 'CONTRACT002',
                    'store_name' => '카트만두 센터 #05',
                    'owner_name' => '이영희',
                    'contract_type' => 'premium',
                    'start_date' => date('Y-m-d', strtotime('-2 months')),
                    'end_date' => date('Y-m-d', strtotime('+10 months')),
                    'status' => 'active',
                    'commission_rate' => 7.50,
                    'signing_bonus' => 15000.00,
                    'signed_by' => '이관리자',
                    'signed_date' => date('Y-m-d', strtotime('-2 months')),
                    'created_at' => date('Y-m-d', strtotime('-2 months')) . ' 11:00:00'
                ],
                [
                    'id' => 3,
                    'store_id' => 3,
                    'contract_code' => 'CONTRACT003',
                    'store_name' => '포카라 샵 #18',
                    'owner_name' => '박지민',
                    'contract_type' => 'standard',
                    'start_date' => date('Y-m-d', strtotime('-3 months')),
                    'end_date' => date('Y-m-d', strtotime('+9 months')),
                    'status' => 'active',
                    'commission_rate' => 5.00,
                    'signing_bonus' => 10000.00,
                    'signed_by' => '박관리자',
                    'signed_date' => date('Y-m-d', strtotime('-3 months')),
                    'created_at' => date('Y-m-d', strtotime('-3 months')) . ' 12:00:00'
                ]
            ];
        }
        // 판매점 목록 테스트 데이터
        else if ($query_type && strpos($query_type, 'store_list') !== false) {
            return [
                [
                    'id' => 1,
                    'store_name' => '네팔 마트 #23'
                ],
                [
                    'id' => 2,
                    'store_name' => '카트만두 센터 #05'
                ],
                [
                    'id' => 3,
                    'store_name' => '포카라 샵 #18'
                ]
            ];
        }
        // 취소/환불 이력 테스트 데이터
        else if ($query_type && strpos($query_type, 'cancellation_history') !== false) {
            return [
                [
                    'id' => 1,
                    'ticket_number' => 'T123456789',
                    'product_name' => '로또 6/45',
                    'price' => 1000,
                    'store_name' => '행운복권방',
                    'store_code' => 'ST12345',
                    'terminal_code' => 'TR7890',
                    'cancel_reason' => '고객 요청',
                    'cancel_notes' => '고객이 번호 변경을 원함',
                    'cancelled_at' => '2025-05-10 15:35:45',
                    'cancelled_by_name' => '관리자',
                    'refund_amount' => 1000,
                    'refund_method' => '현금',
                    'refund_reference' => 'REF123456',
                    'refunded_at' => '2025-05-10 15:36:00'
                ],
                [
                    'id' => 2,
                    'ticket_number' => 'T123456790',
                    'product_name' => '로또 6/45',
                    'price' => 1000,
                    'store_name' => '행운복권방',
                    'store_code' => 'ST12345',
                    'terminal_code' => 'TR7890',
                    'cancel_reason' => '시스템 오류',
                    'cancel_notes' => '인쇄 오류로 인한 취소',
                    'cancelled_at' => '2025-05-11 09:20:22',
                    'cancelled_by_name' => '판매원',
                    'refund_amount' => 1000,
                    'refund_method' => '현금',
                    'refund_reference' => 'REF123457',
                    'refunded_at' => '2025-05-11 09:21:00'
                ]
            ];
        }
        // 상품 목록 테스트 데이터
        else if ($query_type && strpos($query_type, 'products') !== false) {
            return [
                [
                    'id' => 1,
                    'product_code' => 'L645',
                    'name' => '로또 6/45',
                    'description' => '1~45 중 6개의 숫자를 맞추는 복권',
                    'price' => 1000,
                    'status' => 'active',
                    'created_at' => '2025-01-01 00:00:00'
                ],
                [
                    'id' => 2,
                    'product_code' => 'PS720',
                    'name' => '연금복권720+',
                    'description' => '매주 720명에게 연금을 지급하는 복권'
                ]
            ];
        }
        // 판매점 목록 테스트 데이터
        else if ($query_type && strpos($query_type, 'store_list') !== false) {
            return [
                [
                    'id' => 1,
                    'store_code' => 'ST001',
                    'store_name' => '행운복권',
                    'business_number' => '123-45-67890',
                    'owner_name' => '홍길동',
                    'phone' => '02-1234-5678',
                    'email' => 'lucky@store.co.kr',
                    'address' => '서울시 종로구 종로 123',
                    'city' => '서울',
                    'store_category' => 'standard',
                    'status' => 'active',
                    'equipment_count' => 2,
                    'registration_date' => '2023-01-15'
                ],
                [
                    'id' => 2,
                    'store_code' => 'ST002',
                    'store_name' => '럭키로또',
                    'business_number' => '234-56-78901',
                    'owner_name' => '김행운',
                    'phone' => '02-2345-6789',
                    'email' => 'lotto@lucky.co.kr',
                    'address' => '서울시 중구 명동길 45',
                    'city' => '서울',
                    'store_category' => 'premium',
                    'status' => 'active',
                    'equipment_count' => 3,
                    'registration_date' => '2023-02-10'
                ],
                [
                    'id' => 3,
                    'store_code' => 'ST003',
                    'store_name' => '드림복권방',
                    'business_number' => '345-67-89012',
                    'owner_name' => '이꿈나',
                    'phone' => '051-345-6789',
                    'email' => 'dream@lotto.co.kr',
                    'address' => '부산시 중구 광복로 78',
                    'city' => '부산',
                    'store_category' => 'standard',
                    'status' => 'active',
                    'equipment_count' => 1,
                    'registration_date' => '2023-03-05'
                ],
                [
                    'id' => 4,
                    'store_code' => 'ST004',
                    'store_name' => '행복복권센터',
                    'business_number' => '456-78-90123',
                    'owner_name' => '박행복',
                    'phone' => '031-456-7890',
                    'email' => 'happy@lotto.co.kr',
                    'address' => '경기도 수원시 팔달구 인계동 123',
                    'city' => '수원',
                    'store_category' => 'premium',
                    'status' => 'active',
                    'equipment_count' => 2,
                    'registration_date' => '2023-04-20'
                ],
                [
                    'id' => 5,
                    'store_code' => 'ST005',
                    'store_name' => '황금로또',
                    'business_number' => '567-89-01234',
                    'owner_name' => '최황금',
                    'phone' => '053-567-8901',
                    'email' => 'gold@lotto.co.kr',
                    'address' => '대구시 중구 동성로 67',
                    'city' => '대구',
                    'store_category' => 'exclusive',
                    'status' => 'active',
                    'equipment_count' => 4,
                    'registration_date' => '2023-05-15'
                ],
                [
                    'id' => 6,
                    'store_code' => 'ST006',
                    'store_name' => '스마일복권',
                    'business_number' => '678-90-12345',
                    'owner_name' => '정스마일',
                    'phone' => '042-678-9012',
                    'email' => 'smile@lotto.co.kr',
                    'address' => '대전시 중구 대흥동 123',
                    'city' => '대전',
                    'store_category' => 'standard',
                    'status' => 'inactive',
                    'equipment_count' => 0,
                    'registration_date' => '2023-06-10'
                ],
                [
                    'id' => 7,
                    'store_code' => 'ST007',
                    'store_name' => '해피데이로또',
                    'business_number' => '789-01-23456',
                    'owner_name' => '한해피',
                    'phone' => '062-789-0123',
                    'email' => 'happyday@lotto.co.kr',
                    'address' => '광주시 동구 충장로 45',
                    'city' => '광주',
                    'store_category' => 'standard',
                    'status' => 'pending',
                    'equipment_count' => 0,
                    'registration_date' => '2023-07-05'
                ],
                [
                    'id' => 8,
                    'store_code' => 'ST008',
                    'store_name' => '부자복권방',
                    'business_number' => '890-12-34567',
                    'owner_name' => '임부자',
                    'phone' => '032-890-1234',
                    'email' => 'rich@lotto.co.kr',
                    'address' => '인천시 미추홀구 주안동 67',
                    'city' => '인천',
                    'store_category' => 'premium',
                    'status' => 'active',
                    'equipment_count' => 2,
                    'registration_date' => '2023-08-20'
                ],
                [
                    'id' => 9,
                    'store_code' => 'ST009',
                    'store_name' => '행운의 티켓',
                    'business_number' => '901-23-45678',
                    'owner_name' => '윤행운',
                    'phone' => '052-901-2345',
                    'email' => 'luckyticket@lotto.co.kr',
                    'address' => '울산시 남구 삼산동 89',
                    'city' => '울산',
                    'store_category' => 'standard',
                    'status' => 'terminated',
                    'equipment_count' => 0,
                    'registration_date' => '2023-09-15'
                ],
                [
                    'id' => 10,
                    'store_code' => 'ST010',
                    'store_name' => '로또 프리미엄',
                    'business_number' => '012-34-56789',
                    'owner_name' => '김프리미엄',
                    'phone' => '064-012-3456',
                    'email' => 'premium@lotto.co.kr',
                    'address' => '제주시 연동 12',
                    'city' => '제주',
                    'store_category' => 'exclusive',
                    'status' => 'active',
                    'equipment_count' => 5,
                    'registration_date' => '2023-10-10'
                ]
            ];
        }
        // 발행 목록 테스트 데이터
        else if ($query_type && strpos($query_type, 'issues') !== false) {
            return [
                [
                    'id' => 1,
                    'issue_code' => 'I202501',
                    'product_id' => 1,
                    'product_name' => '로또 6/45',
                    'issue_date' => '2025-01-01',
                    'draw_date' => '2025-01-06',
                    'total_tickets' => 10000,
                    'sold_tickets' => 8500,
                    'status' => 'active',
                    'created_at' => '2025-01-01 00:00:00'
                ]
            ];
        }
        // 기본 빈 데이터
        else {
            return [];
        }
    }
    
    /**
     * 단일 행을 연관 배열로 반환하는 메소드 (PDO 스타일)
     * 
     * @param int $fetch_style 결과 스타일 (사용되지 않음)
     * @return array 테스트 데이터 배열
     */
    public function fetch($fetch_style = PDO::FETCH_ASSOC) {
        global $query_type;
        
        // 판매점 목록 카운트 테스트 데이터
        if ($query_type && strpos($query_type, 'store_count') !== false) {
            return [
                'total' => 10
            ];
        }
        // 상품 정보 테스트 데이터
        else if ($query_type && strpos($query_type, 'product_info') !== false) {
            return [
                'id' => 1,
                'product_code' => 'L645',
                'name' => '로또 6/45',
                'description' => '1~45 중 6개의 숫자를 맞추는 복권',
                'price' => 1000,
                'status' => 'active',
                'created_at' => '2025-01-01 00:00:00'
            ];
        }
        // 티켓 정보 테스트 데이터
        else if ($query_type && strpos($query_type, 'ticket_info') !== false) {
            return [
                'id' => 1,
                'ticket_number' => 'T123456789',
                'numbers' => '1,7,19,23,34,45',
                'price' => 1000,
                'status' => 'active',
                'created_at' => '2025-05-10 15:30:45',
                'product_name' => '로또 6/45',
                'product_code' => 'L645',
                'store_name' => '행운복권방',
                'store_code' => 'ST12345',
                'region_name' => '서울',
                'terminal_code' => 'TR7890'
            ];
        }
        // 장비 정보 테스트 데이터
        else if ($query_type && strpos($query_type, 'equipment_info') !== false) {
            return [
                'id' => 1,
                'equipment_code' => 'EQ-TERM-001',
                'store_id' => 1,
                'equipment_type' => 'terminal',
                'model_name' => 'LT-2000',
                'serial_number' => 'SN12345678',
                'installation_date' => '2025-01-15',
                'warranty_end_date' => '2026-01-14',
                'status' => 'operational',
                'last_maintenance_date' => '2025-04-20',
                'notes' => '최신 펌웨어 업데이트 적용됨',
                'created_at' => '2025-01-15 10:30:00',
                'updated_at' => '2025-04-20 14:45:00',
                'store_name' => '행운복권방',
                'store_code' => 'ST12345'
            ];
        }
        // 기본 빈 데이터
        else {
            return [];
        }
    }
    
    /**
     * 결과 리소스를 반환하는 메소드 (mysqli 스타일 - 호환성 지원)
     * 
     * @return $this 자신을 반환
     */
    public function get_result() {
        return $this;
    }
    
    /**
     * 결과 행 수를 반환하는 메소드
     * 
     * @return int 항상 0 반환
     */
    public function rowCount() {
        return 0;
    }
    
    /**
     * 단일 행을 연관 배열로 반환하는 메소드 (mysqli 스타일 - 호환성 지원)
     * 
     * @return array 테스트 데이터 배열
     */
    public function fetch_assoc() {
        global $query_type;
        
        // count 쿼리인 경우 'total' 키를 포함하는 배열 반환
        if ($query_type && (strpos($query_type, 'count') !== false || strpos($query_type, 'total') !== false)) {
            if (strpos($query_type, 'store_count') !== false) {
                return ['total' => 10];
            }
            return ['total' => 100];
        }
        
        // 판매점 목록 쿼리인 경우 무작위 판매점 데이터 반환
        if ($query_type && strpos($query_type, 'store_list') !== false) {
            static $counter = 0;
            
            // Mock 데이터 배열
            $stores = [
                [
                    'id' => 1,
                    'store_code' => 'ST001',
                    'store_name' => '행운복권',
                    'business_number' => '123-45-67890',
                    'owner_name' => '홍길동',
                    'phone' => '02-1234-5678',
                    'email' => 'lucky@store.co.kr',
                    'address' => '서울시 종로구 종로 123',
                    'city' => '서울',
                    'store_category' => 'standard',
                    'status' => 'active',
                    'equipment_count' => 2,
                    'registration_date' => '2023-01-15'
                ],
                [
                    'id' => 2,
                    'store_code' => 'ST002',
                    'store_name' => '럭키로또',
                    'business_number' => '234-56-78901',
                    'owner_name' => '김행운',
                    'phone' => '02-2345-6789',
                    'email' => 'lotto@lucky.co.kr',
                    'address' => '서울시 중구 명동길 45',
                    'city' => '서울',
                    'store_category' => 'premium',
                    'status' => 'active',
                    'equipment_count' => 3,
                    'registration_date' => '2023-02-10'
                ],
                [
                    'id' => 3,
                    'store_code' => 'ST003',
                    'store_name' => '드림복권방',
                    'business_number' => '345-67-89012',
                    'owner_name' => '이꿈나',
                    'phone' => '051-345-6789',
                    'email' => 'dream@lotto.co.kr',
                    'address' => '부산시 중구 광복로 78',
                    'city' => '부산',
                    'store_category' => 'standard',
                    'status' => 'active',
                    'equipment_count' => 1,
                    'registration_date' => '2023-03-05'
                ],
                [
                    'id' => 4,
                    'store_code' => 'ST004',
                    'store_name' => '행복복권센터',
                    'business_number' => '456-78-90123',
                    'owner_name' => '박행복',
                    'phone' => '031-456-7890',
                    'email' => 'happy@lotto.co.kr',
                    'address' => '경기도 수원시 팔달구 인계동 123',
                    'city' => '수원',
                    'store_category' => 'premium',
                    'status' => 'active',
                    'equipment_count' => 2,
                    'registration_date' => '2023-04-20'
                ],
                [
                    'id' => 5,
                    'store_code' => 'ST005',
                    'store_name' => '황금로또',
                    'business_number' => '567-89-01234',
                    'owner_name' => '최황금',
                    'phone' => '053-567-8901',
                    'email' => 'gold@lotto.co.kr',
                    'address' => '대구시 중구 동성로 67',
                    'city' => '대구',
                    'store_category' => 'exclusive',
                    'status' => 'active',
                    'equipment_count' => 4,
                    'registration_date' => '2023-05-15'
                ],
                [
                    'id' => 6,
                    'store_code' => 'ST006',
                    'store_name' => '스마일복권',
                    'business_number' => '678-90-12345',
                    'owner_name' => '정스마일',
                    'phone' => '042-678-9012',
                    'email' => 'smile@lotto.co.kr',
                    'address' => '대전시 중구 대흥동 123',
                    'city' => '대전',
                    'store_category' => 'standard',
                    'status' => 'inactive',
                    'equipment_count' => 0,
                    'registration_date' => '2023-06-10'
                ],
                [
                    'id' => 7,
                    'store_code' => 'ST007',
                    'store_name' => '해피데이로또',
                    'business_number' => '789-01-23456',
                    'owner_name' => '한해피',
                    'phone' => '062-789-0123',
                    'email' => 'happyday@lotto.co.kr',
                    'address' => '광주시 동구 충장로 45',
                    'city' => '광주',
                    'store_category' => 'standard',
                    'status' => 'pending',
                    'equipment_count' => 0,
                    'registration_date' => '2023-07-05'
                ],
                [
                    'id' => 8,
                    'store_code' => 'ST008',
                    'store_name' => '부자복권방',
                    'business_number' => '890-12-34567',
                    'owner_name' => '임부자',
                    'phone' => '032-890-1234',
                    'email' => 'rich@lotto.co.kr',
                    'address' => '인천시 미추홀구 주안동 67',
                    'city' => '인천',
                    'store_category' => 'premium',
                    'status' => 'active',
                    'equipment_count' => 2,
                    'registration_date' => '2023-08-20'
                ],
                [
                    'id' => 9,
                    'store_code' => 'ST009',
                    'store_name' => '행운의 티켓',
                    'business_number' => '901-23-45678',
                    'owner_name' => '윤행운',
                    'phone' => '052-901-2345',
                    'email' => 'luckyticket@lotto.co.kr',
                    'address' => '울산시 남구 삼산동 89',
                    'city' => '울산',
                    'store_category' => 'standard',
                    'status' => 'terminated',
                    'equipment_count' => 0,
                    'registration_date' => '2023-09-15'
                ],
                [
                    'id' => 10,
                    'store_code' => 'ST010',
                    'store_name' => '로또 프리미엄',
                    'business_number' => '012-34-56789',
                    'owner_name' => '김프리미엄',
                    'phone' => '064-012-3456',
                    'email' => 'premium@lotto.co.kr',
                    'address' => '제주시 연동 12',
                    'city' => '제주',
                    'store_category' => 'exclusive',
                    'status' => 'active',
                    'equipment_count' => 5,
                    'registration_date' => '2023-10-10'
                ]
            ];
            
            // 데이터의 끝에 도달하면 null 반환
            if ($counter >= count($stores)) {
                $counter = 0;
                return null;
            }
            
            // 현재 인덱스의 데이터 반환 후 카운터 증가
            return $stores[$counter++];
        }
        
        // PDO 스타일의 fetch 메소드를 호출하여 동일한 결과 반환
        return $this->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 모든 행을 배열로 반환하는 메소드 (mysqli 스타일 - 호환성 지원)
     * 
     * @param int $result_type 결과 타입
     * @return array 테스트 데이터 배열
     */
    public function fetch_all($result_type = MYSQLI_ASSOC) {
        // PDO 스타일의 fetchAll 메소드를 호출하여 동일한 결과 반환
        $fetch_style = ($result_type == MYSQLI_ASSOC) ? PDO::FETCH_ASSOC : PDO::FETCH_NUM;
        return $this->fetchAll($fetch_style);
    }
    
    /**
     * 단일 컬럼 값을 반환하는 메소드
     * 
     * @param int $column_number 가져올 컬럼 번호 (0-indexed)
     * @return mixed 단일 값 반환 (테스트 환경에서는 100을 기본 반환)
     */
    public function fetchColumn($column_number = 0) {
        global $query_type;
        
        // COUNT 쿼리인 경우
        if (strpos(strtolower($this->query_type), 'count') !== false || 
            strpos(strtolower($this->query_type), 'total') !== false) {
            return 100; // 기본값으로 100 반환
        }
        
        // 계약 카운트 관련 쿼리인 경우
        if (strpos(strtolower($this->query_type), 'contract') !== false) {
            return 3; // 계약 수는 3개로 가정
        }
        
        // 판매점 카운트 관련 쿼리인 경우
        if (strpos(strtolower($this->query_type), 'store') !== false) {
            return 10; // 판매점 수는 10개로 가정
        }
        
        // 일반적인 경우 기본값 반환
        return 0;
    }
    
    /**
     * 결과 리소스를 해제하는 메소드 (mysqli 스타일 - 호환성 지원)
     * 
     * @return bool 항상 true 반환
     */
    public function free() {
        return true;
    }
    
    /**
     * 파라미터를 바인딩하는 메소드
     * 
     * @param mixed $param 파라미터 이름 또는 인덱스
     * @param mixed $value 파라미터 값
     * @param int $type 파라미터 타입 (사용되지 않음)
     * @return bool 항상 true 반환
     */
    public function bindParam($param, &$value, $type = PDO::PARAM_STR) {
        return true;
    }
    
    /**
     * 파라미터를 바인딩하는 메소드 (mysqli 스타일 - 호환성 지원)
     * 
     * @param string $types 파라미터 타입
     * @param mixed ...$params 파라미터들
     * @return bool 항상 true 반환
     */
    public function bind_param($types, ...$params) {
        return true;
    }
    
    /**
     * 값을 바인딩하는 메소드
     * 
     * @param mixed $param 파라미터 이름 또는 인덱스
     * @param mixed $value 파라미터 값
     * @param int $type 파라미터 타입 (사용되지 않음)
     * @return bool 항상 true 반환
     */
    public function bindValue($param, $value, $type = PDO::PARAM_STR) {
        return true;
    }
    
    /**
     * 결과 커서를 닫는 메소드
     * 
     * @return bool 항상 true 반환
     */
    public function closeCursor() {
        return true;
    }
    
    /**
     * 결과 리소스를 해제하는 메소드 (mysqli 스타일 - 호환성 지원)
     * 
     * @return bool 항상 true 반환
     */
    public function close() {
        return true;
    }
    
    /**
     * 결과 집합을 저장하는 메소드 (mysqli_stmt 호환용)
     * 
     * @return $this 자신을 반환
     */
    public function store_result() {
        return $this;
    }
    
    /**
     * 칼럼 정보를 반환하는 메소드
     * 
     * @param int $column 칼럼 인덱스
     * @return array 빈 배열 반환
     */
    public function getColumnMeta($column) {
        return [];
    }
    
    /**
     * 쿼리 타입을 설정하는 메소드
     * 
     * @param string $type 쿼리 타입
     * @return $this 자신을 반환
     */
    public function setQueryType($type) {
        $this->query_type = $type;
        return $this;
    }
    
    /**
     * 결과를 순차 배열로 가져오는 메서드 (mysqli 스타일 - 호환성 지원)
     * 
     * @return array 순차 배열로 반환된 결과 행
     */
    public function fetch_row() {
        global $query_type;
        
        // count 쿼리인 경우 [100] 형태로 반환
        if ($query_type && (strpos($query_type, 'count') !== false || strpos($query_type, 'total') !== false)) {
            return [100];
        }
        
        // 일반적인 경우 - 첫 번째 레코드의 값들만 배열로 반환
        $data = $this->fetch(PDO::FETCH_NUM);
        if ($data) {
            return array_values($data);
        }
        
        return [10]; // 기본값
    }
}
