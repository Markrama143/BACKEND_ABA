<?php
try {
    $pdo = new PDO('sqlite:database/database.sqlite');
    echo "Connected successfully to SQLite database\n";

    // Create a test table
    $pdo->exec("CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY, name TEXT)");
    echo "Test table created successfully\n";

    // Insert a test record
    $stmt = $pdo->prepare("INSERT INTO test (name) VALUES (?)");
    $stmt->execute(['Test Record']);
    echo "Test record inserted successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
