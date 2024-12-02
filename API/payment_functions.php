<?php
// We had to move this out from the database and into PHP because of limitations on MySQL.
// MySQL doesn't support dynamic queries - which we were using to generate tables for each user, etc.
// So we now manage it via PHP, we also manage the function of deducting and increasing amounts via PHP
// It just makes it simpler and easier to manage, and takes up less tablespace. PHP and MySQL are also optimized for read/writes, so this is faster like this

function makePayment($pdo, $fromAccountId, $toAccountId, $amount, $description) {
    try {
        $pdo->beginTransaction();

        // Check if fromAccount has sufficient balance
        $stmt = $pdo->prepare("SELECT balance FROM Accounts WHERE account_id = :fromAccountId");
        $stmt->execute([':fromAccountId' => $fromAccountId]);
        $fromAccountBalance = $stmt->fetchColumn();

        if ($fromAccountBalance < $amount) {
            return json_encode(['message' => 'Insufficient balance']);
        }

        // Deduct amount from fromAccount balance
        $stmt = $pdo->prepare("UPDATE Accounts SET balance = balance - :amount WHERE account_id = :fromAccountId");
        $stmt->execute([':amount' => $amount, ':fromAccountId' => $fromAccountId]);

        // Add amount to toAccount balance
        $stmt = $pdo->prepare("UPDATE Accounts SET balance = balance + :amount WHERE account_id = :toAccountId");
        $stmt->execute([':amount' => $amount, ':toAccountId' => $toAccountId]);

        // Determine table names based on account types
        $stmt = $pdo->prepare("SELECT user_id, account_type FROM Accounts WHERE account_id = :fromAccountId");
        $stmt->execute([':fromAccountId' => $fromAccountId]);
        $fromAccount = $stmt->fetch(PDO::FETCH_ASSOC);
        $fromDebitTableName = "user_" . $fromAccount['user_id'] . "_" . $fromAccount['account_type'] . "_debits";

        $stmt = $pdo->prepare("SELECT user_id, account_type FROM Accounts WHERE account_id = :toAccountId");
        $stmt->execute([':toAccountId' => $toAccountId]);
        $toAccount = $stmt->fetch(PDO::FETCH_ASSOC);
        $toCreditTableName = "user_" . $toAccount['user_id'] . "_" . $toAccount['account_type'] . "_credits";

        // Insert into fromAccount debits table
        $stmt = $pdo->prepare("INSERT INTO $fromDebitTableName (owner_account_id, account_id, amount, description) VALUES (:fromAccountId, :toAccountId, :amount, :description)");
        $stmt->execute([':fromAccountId' => $fromAccountId, ':toAccountId' => $toAccountId, ':amount' => $amount, ':description' => $description]);

        // Insert into toAccount credits table
        $stmt = $pdo->prepare("INSERT INTO $toCreditTableName (owner_account_id, account_id, amount, description) VALUES (:toAccountId, :fromAccountId, :amount, :description)");
        $stmt->execute([':toAccountId' => $toAccountId, ':fromAccountId' => $fromAccountId, ':amount' => $amount, ':description' => $description]);

        // Commit transaction
        $pdo->commit();
        return json_encode(['message' => 'Payment made successfully']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        return json_encode(['message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
