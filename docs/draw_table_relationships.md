# 추첨 관련 테이블 구조 및 관계

## 테이블 관계 다이어그램

```
+----------------+       +----------------+       +------------------+       +----------------+
|    draws       |       |   draw_plans   |       | draw_executions  |       |  draw_results  |
+----------------+       +----------------+       +------------------+       +----------------+
| id             |       | id             |       | id               |       | id             |
| draw_code      |<----->| draw_code      |       | draw_plan_id     |<----->| draw_execution_id
| product_id     |       | product_id     |       | execution_code   |       | result_code    |
| draw_date      |       | draw_date      |       | execution_date   |       | winning_numbers|
| draw_venue     |       | draw_time      |       | draw_method      |       | bonus_numbers  |
| total_tickets  |       | expected_tickets|      | status           |       | result_date    |
| total_sold     |       | status         |       | executed_by      |       | total_prize_amount
| prize_pool_amount|     | ...            |       | ...              |       | ...            |
| status         |       |                |       |                  |       |                |
| ...            |       |                |       |                  |       |                |
+----------------+       +----------------+       +------------------+       +----------------+
        |                                                  |
        |                                                  |
        v                                                  v
+----------------+                                +------------------+
|lottery_products|                                |   draw_winners   |
+----------------+                                +------------------+
| id             |                                | id               |
| product_code   |                                | draw_id          |<---------+
| name           |                                | ticket_id        |
| description    |                                | customer_id      |
| price          |                                | prize_tier       |
| ...            |                                | prize_amount     |
+----------------+                                | payment_status   |
                                                 | ...              |
                                                 +------------------+
```

## 주요 테이블 설명

### 1. draws
추첨의 기본 정보를 담고 있는 테이블입니다. 추첨 코드, 일자, 장소, 상태 등의 정보가 포함됩니다.

### 2. draw_plans
추첨 계획에 대한 정보를 담고 있는 테이블입니다. 추첨 준비에 관련된 정보가 있으며, `draws` 테이블과 `draw_code`를 통해 연결됩니다.

### 3. draw_executions
실제 추첨 실행에 대한 정보를 담고 있는 테이블입니다. `draw_plans` 테이블과 `draw_plan_id` 외래 키로 연결됩니다.

### 4. draw_results
추첨 결과에 대한 정보를 담고 있는 테이블입니다. 당첨 번호, 보너스 번호 등의 정보가 포함되며, `draw_executions` 테이블과 `draw_execution_id` 외래 키로 연결됩니다.

### 5. draw_winners
당첨자 정보를 담고 있는 테이블입니다. `draws` 테이블과 `draw_id` 외래 키로 연결됩니다.

## 주요 관계 설명

1. `draws.draw_code` ↔ `draw_plans.draw_code`: 추첨 정보와 추첨 계획 간의 관계
2. `draw_plans.id` ↔ `draw_executions.draw_plan_id`: 추첨 계획과 추첨 실행 간의 관계
3. `draw_executions.id` ↔ `draw_results.draw_execution_id`: 추첨 실행과 추첨 결과 간의 관계
4. `draws.id` ↔ `draw_winners.draw_id`: 추첨 정보와 당첨자 간의 관계

## SQL 쿼리 예시

### 추첨 이력 조회
```sql
SELECT 
    d.id,
    d.draw_code,
    d.draw_date,
    d.draw_venue,
    dp.draw_method,
    dr.winning_numbers,
    d.status,
    COUNT(dw.id) as winners_count,
    d.prize_pool_amount
FROM 
    draws d
LEFT JOIN 
    draw_plans dp ON d.draw_code = dp.draw_code
LEFT JOIN 
    draw_executions de ON dp.id = de.draw_plan_id
LEFT JOIN 
    draw_results dr ON de.id = dr.draw_execution_id
LEFT JOIN 
    draw_winners dw ON d.id = dw.draw_id
GROUP BY 
    d.id, dr.winning_numbers
ORDER BY 
    d.draw_date DESC
```

### 특정 추첨의 당첨자 수 조회
```sql
SELECT 
    d.draw_code,
    d.draw_date,
    COUNT(dw.id) as winners_count,
    SUM(dw.prize_amount) as total_prize_amount
FROM 
    draws d
LEFT JOIN 
    draw_winners dw ON d.id = dw.draw_id
WHERE 
    d.draw_code = 'DRW-2025-05-01'
GROUP BY 
    d.id
```

## 데이터 흐름
1. 추첨 계획 등록 (`draw_plans` 테이블)
2. 추첨 기본 정보 등록 (`draws` 테이블)
3. 추첨 실행 (`draw_executions` 테이블)
4. 추첨 결과 기록 (`draw_results` 테이블)
5. 당첨자 처리 (`draw_winners` 테이블)

## 참고사항
- 코드 작성 시 테이블 관계를 정확히 파악하고 조인해야 합니다.
- 직접 연결되지 않은 테이블은 중간 테이블을 통해 조인해야 합니다.
- 조회 성능을 위해 적절한 인덱스 사용이 필요합니다.
