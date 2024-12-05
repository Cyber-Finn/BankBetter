<?php
function createUser($pdo, $userName, $password) {
    try {
        $pdo->beginTransaction();

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert into Users table
        $stmt = $pdo->prepare("INSERT INTO Users (user_name, password_hash, user_active) VALUES (:userName, :passwordHash, TRUE)");
        $stmt->execute([':userName' => $userName, ':passwordHash' => $hashedPassword]);
        $userId = $pdo->lastInsertId();

        $pdo->commit();
        return ['message' => 'User created successfully.', 'user_id' => $userId];
    } catch (PDOException $e) {
        $pdo->rollBack();
        return ['message' => 'Error: ' . $e->getMessage()];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['message' => 'Error: ' . $e->getMessage()];
    }
}

function createAccount($pdo, $userId, $accountTypeId) {
    try {
        $pdo->beginTransaction();

        // Ensure userId and accountTypeId are integers
        $userId = (int)$userId;
        $accountTypeId = (int)$accountTypeId;

        // Insert into Accounts table
        $stmt = $pdo->prepare("INSERT INTO Accounts (user_id, account_type, balance) VALUES (:userId, :accountTypeId, 0)");
        $stmt->execute([':userId' => $userId, ':accountTypeId' => $accountTypeId]);

        $accountId = $pdo->lastInsertId();

        // Create user-specific debit and credit tables
        $accountType = $accountTypeId == 1 ? 'standard' : 'realtime';
        $debitTableName = "user_{$userId}_{$accountType}_debits";
        $creditTableName = "user_{$userId}_{$accountType}_credits";

        // Prepare and execute debits table creation
        $createDebitsTableSql = "CREATE TABLE {$debitTableName} (
            transaction_id INT AUTO_INCREMENT PRIMARY KEY,
            owner_account_id INT NOT NULL,
            account_id INT NOT NULL,
            amount DECIMAL(18, 2),
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            description VARCHAR(255),
            FOREIGN KEY (account_id) REFERENCES Accounts(account_id)
        )";
        $stmt = $pdo->prepare($createDebitsTableSql);
        $stmt->execute();

        // Prepare and execute credits table creation
        $createCreditsTableSql = "CREATE TABLE {$creditTableName} (
            transaction_id INT AUTO_INCREMENT PRIMARY KEY,
            owner_account_id INT NOT NULL,
            account_id INT NOT NULL,
            amount DECIMAL(18, 2),
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            description VARCHAR(255),
            FOREIGN KEY (account_id) REFERENCES Accounts(account_id)
        )";
        $stmt = $pdo->prepare($createCreditsTableSql);
        $stmt->execute();
        $pdo->commit();

        return ['message' => 'Transaction committed', 'account_id' => $accountId];
    } catch (PDOException $e) {
        $pdo->rollBack();
        $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
        return ['message' => 'Error: ' . $e->getMessage()];
    } catch (Exception $e) {
        $pdo->rollBack();
        $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
        return ['message' => 'Error: ' . $e->getMessage()];
    }
}


function getUserBalances($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT account_id, balance FROM Accounts WHERE user_id = :userId");
        $stmt->execute([':userId' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}

function getBankStatement($pdo, $userId, $startDate, $endDate) {
    try {
        // Fetch all account types for the user
        $stmt = $pdo->prepare("SELECT account_id, account_type, at_description FROM Accounts JOIN ACCOUNT_TYPES ON Accounts.account_type = ACCOUNT_TYPES.at_id WHERE user_id = :userId");
        $stmt->execute([':userId' => $userId]);
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        
        foreach ($accounts as $account) {
            $accountId = $account['account_id'];
            $accountType = $account['at_description'];

            // Dynamically generate table names
            $debitTableName = "user_" . $userId . "_" . $accountType . "_debits";
            $creditTableName = "user_" . $userId . "_" . $accountType . "_credits";

            // Fetch debits
            $stmt = $pdo->prepare("SELECT * FROM $debitTableName WHERE owner_account_id = :accountId AND timestamp BETWEEN :startDate AND :endDate");
            $stmt->execute([':accountId' => $accountId, ':startDate' => $startDate, ':endDate' => $endDate]);
            $debits = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch credits
            $stmt = $pdo->prepare("SELECT * FROM $creditTableName WHERE owner_account_id = :accountId AND timestamp BETWEEN :startDate AND :endDate");
            $stmt->execute([':accountId' => $accountId, ':startDate' => $startDate, ':endDate' => $endDate]);
            $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $results[$accountId] = [
                'account_type' => $accountType,
                'debits' => $debits,
                'credits' => $credits
            ];
        }

        return $results;
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}
?>