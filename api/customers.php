<?php
/**
 * Customer API Endpoints
 * 
 * This file provides REST API endpoints for customer management.
 * 
 * Endpoints:
 * - GET /api/customers/list - List all customers
 * - GET /api/customers/get/{id} - Get customer details
 * - POST /api/customers/create - Create new customer
 * - PUT /api/customers/update/{id} - Update customer information
 * - DELETE /api/customers/delete/{id} - Delete customer
 * - GET /api/customers/transactions/{id} - Get customer transactions
 * - GET /api/customers/documents/{id} - Get customer documents
 * - GET /api/customers/preferences/{id} - Get customer preferences
 * - PUT /api/customers/preferences/{id} - Update customer preferences
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/api_functions.php';

// Set headers for JSON responses
header('Content-Type: application/json');

// Check API authentication
if (!checkApiAuth()) {
    outputApiError('Unauthorized access', 401);
    exit;
}

// Get request method and route
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$baseUri = '/api/customers/';
$route = '';

// Extract the route from URI
if (strpos($uri, $baseUri) === 0) {
    $route = substr($uri, strlen($baseUri));
}

// Extract IDs from route if present
$id = null;
if (preg_match('~^(list|get|update|delete|transactions|documents|preferences)/([0-9]+)$~', $route, $matches)) {
    $route = $matches[1];
    $id = (int)$matches[2];
} else if (preg_match('~^(list|get|update|delete|transactions|documents|preferences)/?$~', $route, $matches)) {
    $route = $matches[1];
}

// Database connection
$db = getDbConnection();

// Route handling
switch ($route) {
    case 'list':
        if ($method === 'GET') {
            // Get query parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : null;
            $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;
            
            // Calculate offset
            $offset = ($page - 1) * $limit;
            
            // Prepare query
            $sql = "SELECT * FROM customers WHERE 1=1";
            $countSql = "SELECT COUNT(*) as total FROM customers WHERE 1=1";
            $params = [];
            $types = '';
            
            // Add status filter if provided
            if ($status) {
                $sql .= " AND status = ?";
                $countSql .= " AND status = ?";
                $params[] = $status;
                $types .= 's';
            }
            
            // Add search filter if provided
            if ($search) {
                $sql .= " AND (customer_code LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
                $countSql .= " AND (customer_code LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= 'sssss';
            }
            
            // Add pagination
            $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
            
            // Prepare and execute count query
            $countStmt = $db->prepare($countSql);
            if (!empty($params)) {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $totalCount = $countResult->fetch_assoc()['total'];
            $countStmt->close();
            
            // Prepare and execute main query
            $stmt = $db->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Fetch results
            $customers = [];
            while ($row = $result->fetch_assoc()) {
                // Remove sensitive information
                unset($row['notes']);
                $customers[] = $row;
            }
            
            // Prepare pagination metadata
            $totalPages = ceil($totalCount / $limit);
            $nextPage = ($page < $totalPages) ? $page + 1 : null;
            $prevPage = ($page > 1) ? $page - 1 : null;
            
            // Return response
            outputApiResponse([
                'status' => 'success',
                'data' => [
                    'customers' => $customers,
                    'pagination' => [
                        'total' => $totalCount,
                        'page' => $page,
                        'limit' => $limit,
                        'total_pages' => $totalPages,
                        'next_page' => $nextPage,
                        'prev_page' => $prevPage
                    ]
                ]
            ]);
            $stmt->close();
        } else {
            outputApiError('Method not allowed', 405);
        }
        break;
        
    case 'get':
        if ($method === 'GET') {
            if (!$id) {
                // Extract ID from query parameter if not in URL
                $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            }
            
            if (!$id) {
                outputApiError('Customer ID is required', 400);
                break;
            }
            
            // Prepare and execute query
            $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                outputApiError('Customer not found', 404);
                $stmt->close();
                break;
            }
            
            // Fetch customer data
            $customer = $result->fetch_assoc();
            $stmt->close();
            
            // Get customer preferences
            $prefsStmt = $db->prepare("SELECT * FROM customer_preferences WHERE customer_id = ?");
            $prefsStmt->bind_param("i", $id);
            $prefsStmt->execute();
            $prefsResult = $prefsStmt->get_result();
            
            if ($prefsResult->num_rows > 0) {
                $customer['preferences'] = $prefsResult->fetch_assoc();
            } else {
                $customer['preferences'] = null;
            }
            $prefsStmt->close();
            
            // Return response
            outputApiResponse([
                'status' => 'success',
                'data' => $customer
            ]);
        } else {
            outputApiError('Method not allowed', 405);
        }
        break;
        
    case 'create':
        if ($method === 'POST') {
            // Get and validate request data
            $requestData = getRequestData();
            
            // Validate required fields
            $requiredFields = ['first_name', 'last_name'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!isset($requestData[$field]) || empty($requestData[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                outputApiError('Missing required fields: ' . implode(', ', $missingFields), 400);
                break;
            }
            
            // Generate customer code
            $customerCode = generateCustomerCode();
            
            // Prepare data for insertion
            $firstName = sanitizeInput($requestData['first_name']);
            $lastName = sanitizeInput($requestData['last_name']);
            $email = isset($requestData['email']) ? sanitizeInput($requestData['email']) : null;
            $phone = isset($requestData['phone']) ? sanitizeInput($requestData['phone']) : null;
            $address = isset($requestData['address']) ? sanitizeInput($requestData['address']) : null;
            $city = isset($requestData['city']) ? sanitizeInput($requestData['city']) : null;
            $state = isset($requestData['state']) ? sanitizeInput($requestData['state']) : null;
            $postalCode = isset($requestData['postal_code']) ? sanitizeInput($requestData['postal_code']) : null;
            $country = isset($requestData['country']) ? sanitizeInput($requestData['country']) : null;
            $status = isset($requestData['status']) ? sanitizeInput($requestData['status']) : 'active';
            $verificationStatus = isset($requestData['verification_status']) ? sanitizeInput($requestData['verification_status']) : 'unverified';
            $notes = isset($requestData['notes']) ? sanitizeInput($requestData['notes']) : null;
            
            // Check if email is unique if provided
            if ($email) {
                $emailCheckStmt = $db->prepare("SELECT id FROM customers WHERE email = ?");
                $emailCheckStmt->bind_param("s", $email);
                $emailCheckStmt->execute();
                $emailCheckResult = $emailCheckStmt->get_result();
                
                if ($emailCheckResult->num_rows > 0) {
                    outputApiError('Email address is already in use', 400);
                    $emailCheckStmt->close();
                    break;
                }
                $emailCheckStmt->close();
            }
            
            // Prepare and execute insert query
            $stmt = $db->prepare("
                INSERT INTO customers (
                    customer_code, first_name, last_name, email, phone, address, city, state, postal_code, country, 
                    status, verification_status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "sssssssssssss",
                $customerCode, $firstName, $lastName, $email, $phone, $address, $city, $state, $postalCode, $country,
                $status, $verificationStatus, $notes
            );
            
            if ($stmt->execute()) {
                $newCustomerId = $db->insert_id;
                
                // Log activity
                logActivity(
                    'customer_create', 
                    sprintf("New customer created: %s %s (%s)", $firstName, $lastName, $customerCode),
                    $newCustomerId,
                    'customers'
                );
                
                // Add preferences if provided
                if (isset($requestData['preferences']) && is_array($requestData['preferences'])) {
                    $prefs = $requestData['preferences'];
                    
                    $language = isset($prefs['language']) ? sanitizeInput($prefs['language']) : 'en';
                    $notificationEmail = isset($prefs['notification_email']) ? (int)$prefs['notification_email'] : 1;
                    $notificationSms = isset($prefs['notification_sms']) ? (int)$prefs['notification_sms'] : 1;
                    $notificationPush = isset($prefs['notification_push']) ? (int)$prefs['notification_push'] : 1;
                    $marketingConsent = isset($prefs['marketing_consent']) ? (int)$prefs['marketing_consent'] : 0;
                    
                    $prefsStmt = $db->prepare("
                        INSERT INTO customer_preferences (
                            customer_id, language, notification_email, notification_sms, notification_push, marketing_consent
                        ) VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    $prefsStmt->bind_param(
                        "isiiis",
                        $newCustomerId,
                        $language,
                        $notificationEmail,
                        $notificationSms,
                        $notificationPush,
                        $marketingConsent
                    );
                    
                    $prefsStmt->execute();
                    $prefsStmt->close();
                }
                
                // Fetch the newly created customer
                $newCustomerStmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
                $newCustomerStmt->bind_param("i", $newCustomerId);
                $newCustomerStmt->execute();
                $newCustomer = $newCustomerStmt->get_result()->fetch_assoc();
                $newCustomerStmt->close();
                
                // Return response
                outputApiResponse([
                    'status' => 'success',
                    'message' => 'Customer created successfully',
                    'data' => $newCustomer
                ], 201);
            } else {
                outputApiError('Failed to create customer: ' . $db->error, 500);
            }
            
            $stmt->close();
        } else {
            outputApiError('Method not allowed', 405);
        }
        break;
        
    case 'update':
        if ($method === 'PUT') {
            if (!$id) {
                outputApiError('Customer ID is required', 400);
                break;
            }
            
            // Check if customer exists
            $checkStmt = $db->prepare("SELECT id FROM customers WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                outputApiError('Customer not found', 404);
                $checkStmt->close();
                break;
            }
            $checkStmt->close();
            
            // Get and validate request data
            $requestData = getRequestData();
            
            // Prepare update fields
            $updateFields = [];
            $params = [];
            $types = '';
            
            $fieldMappings = [
                'first_name' => ['type' => 's', 'field' => 'first_name'],
                'last_name' => ['type' => 's', 'field' => 'last_name'],
                'email' => ['type' => 's', 'field' => 'email'],
                'phone' => ['type' => 's', 'field' => 'phone'],
                'address' => ['type' => 's', 'field' => 'address'],
                'city' => ['type' => 's', 'field' => 'city'],
                'state' => ['type' => 's', 'field' => 'state'],
                'postal_code' => ['type' => 's', 'field' => 'postal_code'],
                'country' => ['type' => 's', 'field' => 'country'],
                'status' => ['type' => 's', 'field' => 'status'],
                'verification_status' => ['type' => 's', 'field' => 'verification_status'],
                'notes' => ['type' => 's', 'field' => 'notes']
            ];
            
            foreach ($fieldMappings as $requestField => $mapping) {
                if (isset($requestData[$requestField])) {
                    $updateFields[] = $mapping['field'] . ' = ?';
                    $params[] = sanitizeInput($requestData[$requestField]);
                    $types .= $mapping['type'];
                }
            }
            
            if (empty($updateFields)) {
                outputApiError('No fields to update', 400);
                break;
            }
            
            // Check if email is unique if provided
            if (isset($requestData['email'])) {
                $email = sanitizeInput($requestData['email']);
                $emailCheckStmt = $db->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
                $emailCheckStmt->bind_param("si", $email, $id);
                $emailCheckStmt->execute();
                $emailCheckResult = $emailCheckStmt->get_result();
                
                if ($emailCheckResult->num_rows > 0) {
                    outputApiError('Email address is already in use by another customer', 400);
                    $emailCheckStmt->close();
                    break;
                }
                $emailCheckStmt->close();
            }
            
            // Prepare and execute update query
            $sql = "UPDATE customers SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $params[] = $id;
            $types .= 'i';
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                // Log activity
                logActivity(
                    'customer_update', 
                    sprintf("Customer ID %d updated", $id),
                    $id,
                    'customers'
                );
                
                // Update preferences if provided
                if (isset($requestData['preferences']) && is_array($requestData['preferences'])) {
                    $prefs = $requestData['preferences'];
                    
                    // Check if preferences exist
                    $prefsCheckStmt = $db->prepare("SELECT customer_id FROM customer_preferences WHERE customer_id = ?");
                    $prefsCheckStmt->bind_param("i", $id);
                    $prefsCheckStmt->execute();
                    $prefsExist = $prefsCheckStmt->get_result()->num_rows > 0;
                    $prefsCheckStmt->close();
                    
                    // Prepare preference data
                    $language = isset($prefs['language']) ? sanitizeInput($prefs['language']) : 'en';
                    $notificationEmail = isset($prefs['notification_email']) ? (int)$prefs['notification_email'] : 1;
                    $notificationSms = isset($prefs['notification_sms']) ? (int)$prefs['notification_sms'] : 1;
                    $notificationPush = isset($prefs['notification_push']) ? (int)$prefs['notification_push'] : 1;
                    $marketingConsent = isset($prefs['marketing_consent']) ? (int)$prefs['marketing_consent'] : 0;
                    
                    if ($prefsExist) {
                        // Update existing preferences
                        $prefsStmt = $db->prepare("
                            UPDATE customer_preferences 
                            SET language = ?, notification_email = ?, notification_sms = ?, notification_push = ?, marketing_consent = ?
                            WHERE customer_id = ?
                        ");
                        
                        $prefsStmt->bind_param(
                            "siiii",
                            $language,
                            $notificationEmail,
                            $notificationSms,
                            $notificationPush,
                            $marketingConsent,
                            $id
                        );
                    } else {
                        // Insert new preferences
                        $prefsStmt = $db->prepare("
                            INSERT INTO customer_preferences (
                                customer_id, language, notification_email, notification_sms, notification_push, marketing_consent
                            ) VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        
                        $prefsStmt->bind_param(
                            "isiiii",
                            $id,
                            $language,
                            $notificationEmail,
                            $notificationSms,
                            $notificationPush,
                            $marketingConsent
                        );
                    }
                    
                    $prefsStmt->execute();
                    $prefsStmt->close();
                }
                
                // Fetch the updated customer
                $updatedCustomerStmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
                $updatedCustomerStmt->bind_param("i", $id);
                $updatedCustomerStmt->execute();
                $updatedCustomer = $updatedCustomerStmt->get_result()->fetch_assoc();
                $updatedCustomerStmt->close();
                
                // Return response
                outputApiResponse([
                    'status' => 'success',
                    'message' => 'Customer updated successfully',
                    'data' => $updatedCustomer
                ]);
            } else {
                outputApiError('Failed to update customer: ' . $db->error, 500);
            }
            
            $stmt->close();
        } else {
            outputApiError('Method not allowed', 405);
        }
        break;
        
    case 'delete':
        if ($method === 'DELETE') {
            if (!$id) {
                outputApiError('Customer ID is required', 400);
                break;
            }
            
            // Check if customer exists
            $checkStmt = $db->prepare("SELECT id, first_name, last_name, customer_code FROM customers WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                outputApiError('Customer not found', 404);
                $checkStmt->close();
                break;
            }
            
            $customerInfo = $checkResult->fetch_assoc();
            $checkStmt->close();
            
            // Begin transaction
            $db->begin_transaction();
            
            try {
                // Delete preferences
                $prefsStmt = $db->prepare("DELETE FROM customer_preferences WHERE customer_id = ?");
                $prefsStmt->bind_param("i", $id);
                $prefsStmt->execute();
                $prefsStmt->close();
                
                // Delete documents
                $docsStmt = $db->prepare("DELETE FROM customer_documents WHERE customer_id = ?");
                $docsStmt->bind_param("i", $id);
                $docsStmt->execute();
                $docsStmt->close();
                
                // Delete transactions
                $transStmt = $db->prepare("DELETE FROM customer_transactions WHERE customer_id = ?");
                $transStmt->bind_param("i", $id);
                $transStmt->execute();
                $transStmt->close();
                
                // Delete customer
                $deleteStmt = $db->prepare("DELETE FROM customers WHERE id = ?");
                $deleteStmt->bind_param("i", $id);
                $deleteStmt->execute();
                $deleteStmt->close();
                
                // Commit transaction
                $db->commit();
                
                // Log activity
                logActivity(
                    'customer_delete', 
                    sprintf("Customer deleted: %s %s (%s)", 
                        $customerInfo['first_name'], 
                        $customerInfo['last_name'], 
                        $customerInfo['customer_code']
                    ),
                    $id,
                    'customers'
                );
                
                // Return response
                outputApiResponse([
                    'status' => 'success',
                    'message' => 'Customer deleted successfully'
                ]);
            } catch (Exception $e) {
                // Rollback transaction on error
                $db->rollback();
                outputApiError('Failed to delete customer: ' . $e->getMessage(), 500);
            }
        } else {
            outputApiError('Method not allowed', 405);
        }
        break;
        
    case 'transactions':
        if ($method === 'GET') {
            if (!$id) {
                outputApiError('Customer ID is required', 400);
                break;
            }
            
            // Check if customer exists
            $checkStmt = $db->prepare("SELECT id FROM customers WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                outputApiError('Customer not found', 404);
                $checkStmt->close();
                break;
            }
            $checkStmt->close();
            
            // Get pagination parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : null;
            
            // Calculate offset
            $offset = ($page - 1) * $limit;
            
            // Prepare query
            $sql = "SELECT * FROM customer_transactions WHERE customer_id = ?";
            $countSql = "SELECT COUNT(*) as total FROM customer_transactions WHERE customer_id = ?";
            $params = [$id];
            $types = 'i';
            
            // Add type filter if provided
            if ($type) {
                $sql .= " AND transaction_type = ?";
                $countSql .= " AND transaction_type = ?";
                $params[] = $type;
                $types .= 's';
            }
            
            // Add pagination
            $sql .= " ORDER BY transaction_date DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
            
            // Prepare and execute count query
            $countStmt = $db->prepare($countSql);
            $countStmt->bind_param(substr($types, 0, strlen($types) - 2), ...array_slice($params, 0, count($params) - 2));
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $totalCount = $countResult->fetch_assoc()['total'];
            $countStmt->close();
            
            // Prepare and execute main query
            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Fetch results
            $transactions = [];
            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }
            
            // Prepare pagination metadata
            $totalPages = ceil($totalCount / $limit);
            $nextPage = ($page < $totalPages) ? $page + 1 : null;
            $prevPage = ($page > 1) ? $page - 1 : null;
            
            // Return response
            outputApiResponse([
                'status' => 'success',
                'data' => [
                    'transactions' => $transactions,
                    'pagination' => [
                        'total' => $totalCount,
                        'page' => $page,
                        'limit' => $limit,
                        'total_pages' => $totalPages,
                        'next_page' => $nextPage,
                        'prev_page' => $prevPage
                    ]
                ]
            ]);
            $stmt->close();
        } else {
            outputApiError('Method not allowed', 405);
        }
        break;
        
    case 'documents':
        if ($method === 'GET') {
            if (!$id) {
                outputApiError('Customer ID is required', 400);
                break;
            }
            
            // Check if customer exists
            $checkStmt = $db->prepare("SELECT id FROM customers WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                outputApiError('Customer not found', 404);
                $checkStmt->close();
                break;
            }
            $checkStmt->close();
            
            // Get documents for the customer
            $stmt = $db->prepare("SELECT * FROM customer_documents WHERE customer_id = ? ORDER BY uploaded_date DESC");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Fetch results
            $documents = [];
            while ($row = $result->fetch_assoc()) {
                $documents[] = $row;
            }
            
            // Return response
            outputApiResponse([
                'status' => 'success',
                'data' => [
                    'documents' => $documents
                ]
            ]);
            $stmt->close();
        } else {
            outputApiError('Method not allowed', 405);
        }
        break;
        
    case 'preferences':
        if ($method === 'GET') {
            if (!$id) {
                outputApiError('Customer ID is required', 400);
                break;
            }
            
            // Check if customer exists
            $checkStmt = $db->prepare("SELECT id FROM customers WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                outputApiError('Customer not found', 404);
                $checkStmt->close();
                break;
            }
            $checkStmt->close();
            
            // Get preferences for the customer
            $stmt = $db->prepare("SELECT * FROM customer_preferences WHERE customer_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                // Return default preferences if none are set
                outputApiResponse([
                    'status' => 'success',
                    'data' => [
                        'preferences' => [
                            'customer_id' => $id,
                            'language' => 'en',
                            'notification_email' => 1,
                            'notification_sms' => 1,
                            'notification_push' => 1,
                            'marketing_consent' => 0
                        ]
                    ]
                ]);
            } else {
                // Return the customer's preferences
                $preferences = $result->fetch_assoc();
                outputApiResponse([
                    'status' => 'success',
                    'data' => [
                        'preferences' => $preferences
                    ]
                ]);
            }
            $stmt->close();
        } else if ($method === 'PUT') {
            if (!$id) {
                outputApiError('Customer ID is required', 400);
                break;
            }
            
            // Check if customer exists
            $checkStmt = $db->prepare("SELECT id FROM customers WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                outputApiError('Customer not found', 404);
                $checkStmt->close();
                break;
            }
            $checkStmt->close();
            
            // Get and validate request data
            $requestData = getRequestData();
            
            // Prepare preference data
            $language = isset($requestData['language']) ? sanitizeInput($requestData['language']) : 'en';
            $notificationEmail = isset($requestData['notification_email']) ? (int)$requestData['notification_email'] : 1;
            $notificationSms = isset($requestData['notification_sms']) ? (int)$requestData['notification_sms'] : 1;
            $notificationPush = isset($requestData['notification_push']) ? (int)$requestData['notification_push'] : 1;
            $marketingConsent = isset($requestData['marketing_consent']) ? (int)$requestData['marketing_consent'] : 0;
            
            // Check if preferences exist
            $prefsCheckStmt = $db->prepare("SELECT customer_id FROM customer_preferences WHERE customer_id = ?");
            $prefsCheckStmt->bind_param("i", $id);
            $prefsCheckStmt->execute();
            $prefsExist = $prefsCheckStmt->get_result()->num_rows > 0;
            $prefsCheckStmt->close();
            
            if ($prefsExist) {
                // Update existing preferences
                $stmt = $db->prepare("
                    UPDATE customer_preferences 
                    SET language = ?, notification_email = ?, notification_sms = ?, notification_push = ?, marketing_consent = ?
                    WHERE customer_id = ?
                ");
                
                $stmt->bind_param(
                    "siiii",
                    $language,
                    $notificationEmail,
                    $notificationSms,
                    $notificationPush,
                    $marketingConsent,
                    $id
                );
            } else {
                // Insert new preferences
                $stmt = $db->prepare("
                    INSERT INTO customer_preferences (
                        customer_id, language, notification_email, notification_sms, notification_push, marketing_consent
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->bind_param(
                    "isiiii",
                    $id,
                    $language,
                    $notificationEmail,
                    $notificationSms,
                    $notificationPush,
                    $marketingConsent
                );
            }
            
            if ($stmt->execute()) {
                // Log activity
                logActivity(
                    'customer_preferences_update', 
                    sprintf("Customer ID %d preferences updated", $id),
                    $id,
                    'customers'
                );
                
                // Fetch the updated preferences
                $prefStmt = $db->prepare("SELECT * FROM customer_preferences WHERE customer_id = ?");
                $prefStmt->bind_param("i", $id);
                $prefStmt->execute();
                $preferences = $prefStmt->get_result()->fetch_assoc();
                $prefStmt->close();
                
                // Return response
                outputApiResponse([
                    'status' => 'success',
                    'message' => 'Preferences updated successfully',
                    'data' => [
                        'preferences' => $preferences
                    ]
                ]);
            } else {
                outputApiError('Failed to update preferences: ' . $db->error, 500);
            }
            $stmt->close();
        } else {
            outputApiError('Method not allowed', 405);
        }
        break;
        
    default:
        outputApiError('Endpoint not found', 404);
        break;
}

/**
 * Get request data from JSON body
 * 
 * @return array The request data
 */
function getRequestData() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        outputApiError('Invalid JSON in request body', 400);
        exit;
    }
    
    return $data;
}

/**
 * Generate a unique customer code
 * 
 * @return string The generated customer code
 */
function generateCustomerCode() {
    $db = getDbConnection();
    $prefix = 'CUST';
    $unique = false;
    $customerCode = '';
    
    while (!$unique) {
        // Generate a random 8-digit number
        $randomNumber = mt_rand(10000000, 99999999);
        $customerCode = $prefix . $randomNumber;
        
        // Check if the code already exists
        $stmt = $db->prepare("SELECT id FROM customers WHERE customer_code = ?");
        $stmt->bind_param("s", $customerCode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $unique = true;
        }
        
        $stmt->close();
    }
    
    return $customerCode;
}
