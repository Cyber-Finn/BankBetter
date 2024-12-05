<?php
require_once 'connection_details.php';
require_once 'auth.php';
require_once 'sanitation.php';
require_once 'user_functions.php';
require_once 'payment_functions.php';

// Set the appropriate Content-Type for JSON responses
header('Content-Type: application/json');

// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Capture the request action
$action = $_REQUEST['action'] ?? '';

// Ensure the user is authenticated for actions other than user creation and login
if ($action !== 'createUser' && $action !== 'loginUser') {
    isAuthenticated();
}

try {
    switch ($action) {
        case 'createUser':
            try{
                $pdo = CreateNewPDO();
                $input = json_decode(file_get_contents('php://input'), true);
                $userName = fullSanitize($input['user_name']);
                $password = fullSanitize($input['password']);
                $result = createUser($pdo, $userName, $password);
                echo json_encode($result);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;

        case 'createAccount':
            try{
                $pdo = CreateNewPDO();
                $input = json_decode(file_get_contents('php://input'), true);
                if (isset($input['user_id']) && isset($input['account_type_id'])) {
                    $userId = fullSanitize($input['user_id']);
                    $accountTypeId = fullSanitize($input['account_type_id']);

                    setAutoCommit($pdo, false);
                    $result = createAccount($pdo, $userId, $accountTypeId);
                    setAutoCommit($pdo, true);
                    echo json_encode($result);
                } else {
                    echo json_encode(['message' => 'Error: Missing required fields']);
                }
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;

        case 'loginUser':
            try{
                $pdo = CreateNewPDO();
                $input = json_decode(file_get_contents('php://input'), true);
                $userName = fullSanitize($input['user_name']);
                $password = fullSanitize($input['password']);
                $result = isAuthenticated($userName, $password);
                // if (isset($result['message']) && $result['message'] === 'Login successful') {
                //     $_SESSION['user_id'] = $result['user_id'];
                // }
                echo json_encode($result);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }            
            break;

        case 'makePayment':
            try{
                $pdo = CreateNewPDO();
                $input = json_decode(file_get_contents('php://input'), true);
                $fromAccountId = fullSanitize($input['from_account_id']);
                $toAccountId = fullSanitize($input['to_account_id']);
                $amount = fullSanitize($input['amount']);
                $description = fullSanitize($input['description']);
                echo json_encode(['message' => makePayment($pdo, $fromAccountId, $toAccountId, $amount, $description)]);
                
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;

        case 'getUserBalances':
            try{
                $pdo = CreateNewPDO();
                $userId = fullSanitize($_GET['user_id']);
                echo json_encode(getUserBalances($pdo, $userId));
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;

        case 'getBankStatement':
            try{
                $pdo = CreateNewPDO();
                $userId = fullSanitize($_GET['user_id']);
                $startDate = fullSanitize($_GET['start_date']);
                $endDate = fullSanitize($_GET['end_date']);
                echo json_encode(getBankStatement($pdo, $userId, $startDate, $endDate));
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['message' => 'Invalid action.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>