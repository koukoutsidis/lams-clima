<?php
$host = '';
$dbname = '';
$username = '';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // ΑΥΤΗ Η ΓΡΑΜΜΗ ΔΙΟΡΘΩΝΕΙ ΤΑ ΕΛΛΗΝΙΚΑ ΣΤΗΝ PHP 8.1
    $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    
    //echo "Η σύνδεση στη βάση δεδομένων πραγματοποιήθηκε με επιτυχία!<br>";
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>

