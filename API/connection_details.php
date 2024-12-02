<?php
    // Database connection details
    $servername = "localhost"; // The machine that the DB is running on. If you're using PHP on the same server as your MySQL database, this will be localhost
    $username = "root";
    $password = "mysql"; // Ensure that the password is correct. I kept mine default for this example
    $dbname = "BankBetter";
    $port = "3306"; // Ensure that the port is correct here. The default would likely be 3306 if you just did a generic install of MySQL
    $dsn = "mysql:host=$servername;port=$port;dbname=$dbname";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo;
    try {
        $pdo = new PDO($dsn, $username, $password, $options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo json_encode(['message' => 'Connection failed: ' . $e->getMessage()]);
        exit();
    }
?>
