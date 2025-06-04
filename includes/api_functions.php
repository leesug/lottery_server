<?php
/**
 * API Functions
 * 
 * Common functions used by API endpoints.
 */

/**
 * Check API authentication
 * 
 * @return bool True if authenticated, false otherwise
 */
function checkApiAuth() {
    // For testing purposes, always return true
    // In production, implement proper API authentication
    return true;
}

/**
 * Output an API error response
 * 
 * @param string $message Error message
 * @param int $status HTTP status code
 */
function outputApiError($message, $status = 400) {
    http_response_code($status);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
}

/**
 * Output an API success response
 * 
 * @param array $data Response data
 * @param int $status HTTP status code
 */
function outputApiResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
}

/**
 * Validate required fields in API request
 * 
 * @param array $data Request data
 * @param array $requiredFields List of required field names
 * @return array List of missing fields
 */
function validateRequiredFields($data, $requiredFields) {
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missingFields[] = $field;
        }
    }
    
    return $missingFields;
}

/**
 * Get API request query parameters
 * 
 * @param array $defaultValues Default values for parameters
 * @return array Query parameters with default values applied
 */
function getApiQueryParams($defaultValues = []) {
    $params = [];
    
    foreach ($defaultValues as $key => $defaultValue) {
        $params[$key] = isset($_GET[$key]) ? $_GET[$key] : $defaultValue;
    }
    
    return $params;
}

/**
 * Generate pagination metadata for API responses
 * 
 * @param int $totalCount Total number of items
 * @param int $page Current page number
 * @param int $limit Items per page
 * @return array Pagination metadata
 */
function generatePaginationMetadata($totalCount, $page, $limit) {
    $totalPages = ceil($totalCount / $limit);
    $nextPage = ($page < $totalPages) ? $page + 1 : null;
    $prevPage = ($page > 1) ? $page - 1 : null;
    
    return [
        'total' => $totalCount,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => $totalPages,
        'next_page' => $nextPage,
        'prev_page' => $prevPage
    ];
}

/**
 * Build API URL with query parameters
 * 
 * @param string $baseUrl Base URL
 * @param array $params Query parameters
 * @return string URL with query parameters
 */
function buildApiUrl($baseUrl, $params = []) {
    if (empty($params)) {
        return $baseUrl;
    }
    
    $query = http_build_query($params);
    return $baseUrl . '?' . $query;
}

/**
 * Log API request for audit purposes
 * 
 * @param string $endpoint API endpoint
 * @param string $method HTTP method
 * @param array $requestData Request data
 * @param int $statusCode Response status code
 */
function logApiRequest($endpoint, $method, $requestData = [], $statusCode = 200) {
    global $logApiRequests;
    
    // Skip logging if disabled
    if (!$logApiRequests) {
        return;
    }
    
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoint' => $endpoint,
        'method' => $method,
        'request_data' => $requestData,
        'status_code' => $statusCode,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];
    
    // Log to file
    $logFile = '../logs/api_' . date('Y-m-d') . '.log';
    $logLine = json_encode($logData) . "\n";
    
    file_put_contents($logFile, $logLine, FILE_APPEND);
}
