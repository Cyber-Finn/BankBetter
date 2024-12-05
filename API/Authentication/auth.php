<?php
    require_once 'connection_details.php';
    require_once 'sanitation.php';

    function isAuthenticated() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            // Handle unauthenticated user
            handleLogin();
        } else {
            // Manage existing session
            handleExistingSession();
        }
    }

    function handleUnauthorizedUser() {
        return ['message' => 'Unauthorized', 'redirect' => true];
    }

    function handleExistingSession() {
        // Set the session timeout duration (e.g., 1800 seconds = 30 minutes)
        $timeout_duration = 1800;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
            session_unset();
            session_destroy();
            return ['message' => 'Session timed out', 'redirect' => true];
        }
        $_SESSION['last_activity'] = time();

        // Check if the session IP address matches the user's current IP address
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            session_unset();
            session_destroy();
            return ['message' => 'Session terminated due to IP address change', 'redirect' => true];
        }

        // Proceed with user authentication
        try {
            $pdo = CreateNewPDO();
            $user_id = $_SESSION['user_id'];

            // Sanitize the user ID
            $sanitized_user_id = sanitizeSql($user_id);

            $stmt = $pdo->prepare('SELECT * FROM Users WHERE id = ? LIMIT 1');
            $stmt->execute([$sanitized_user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['message' => 'Unauthorized', 'redirect' => true];
            }
            return $user;
        } catch (Exception $e) {
            return ['message' => 'Error: ' . $e->getMessage()];
        }
    }

    function handleLogin($userName, $password) {
        try {
            $pdo = CreateNewPDO();
            $sanitizedUserName = sanitizeSql($userName);
            $sanitizedPassword = sanitizeSql($password);
    
            $stmt = $pdo->prepare('SELECT * FROM Users WHERE user_name = :userName LIMIT 1');
            $stmt->execute([':userName' => $sanitizedUserName]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($user && password_verify($sanitizedPassword, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['last_activity'] = time();
                return ['message' => 'Login successful', 'user_id' => $user['user_id']];
            } else {
                return handleUnauthorizedUser();
            }
        } catch (Exception $e) {
            return ['message' => 'Error: ' . $e->getMessage()];
        }
    }
?>
