<?php
    require_once 'connection_details.php';
    require_once 'sanitation.php';

    function isAuthenticated() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            // Handle unauthenticated user
            handleUnauthenticatedUser();
        } else {
            // Manage existing session
            handleExistingSession();
        }
    }

    function handleUnauthenticatedUser() {
        echo json_encode(['message' => 'Unauthorized', 'redirect' => true]);
        exit;
    }

    function handleExistingSession($userName = null, $password = null) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Login scenario: if username and password are provided
        if ($userName && $password) {
            try {
                global $pdo;
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
                    return ['message' => 'Invalid username or password', 'redirect' => true];
                }
            } catch (Exception $e) {
                return ['message' => 'Error: ' . $e->getMessage()];
            }
        } else {
            // Existing session scenario
            $timeout_duration = 1800;
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
                session_unset();
                session_destroy();
                echo json_encode(['message' => 'Session timed out', 'redirect' => true]);
                exit;
            }
            $_SESSION['last_activity'] = time();

            // Check if the session IP address matches the user's current IP address
            if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
                session_unset();
                session_destroy();
                echo json_encode(['message' => 'Session terminated due to IP address change', 'redirect' => true]);
                exit;
            }

            try {
                global $pdo;
                $user_id = $_SESSION['user_id'];
                $sanitized_user_id = sanitizeSql($user_id);

                $stmt = $pdo->prepare('SELECT * FROM Users WHERE user_id = ? LIMIT 1');
                $stmt->execute([$sanitized_user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    echo json_encode(['message' => 'Unauthorized', 'redirect' => true]);
                    exit;
                }
                return $user;
            } catch (Exception $e) {
                echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
                exit;
            }
        }
    }
?>
