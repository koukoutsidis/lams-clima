<?php
// Επιστροφή απάντησης αποκλειστικά σε μορφή JSON
header('Content-Type: application/json; charset=utf-8');

// 1. Συμπερίληψη του αρχείου σύνδεσης με τη Βάση Δεδομένων
require "db.php";

// 2. Έλεγχος αν η μέθοδος αιτήματος είναι POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_json_encode([
        'status' => 'error',
        'message' => 'Μη επιτρεπτή μέθοδος αιτήματος.'
    ]);
    exit;
}

// 3. Λήψη και καθαρισμός δεδομένων από το POST αίτημα
$userId   = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
$username = filter_input(INPUT_POST, 'user_name', FILTER_DEFAULT);
$score    = filter_input(INPUT_POST, 'score', FILTER_VALIDATE_INT);

// 4. Έλεγχος εγκυρότητας των παραμέτρων
if ($userId === false || $userId === null || empty($username) || $score === false) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Μη έγκυρα ή ελλιπή δεδομένα.'
    ]);
    exit;
}

// Περιορισμός του score στο εύρος 0-100 για λόγους ασφαλείας
$score = max(0, min(100, $score));

try {
    // 5. Προετοιμασία του SQL ερωτήματος (Prepared Statement)
    // Χρησιμοποιούμε ON DUPLICATE KEY UPDATE ώστε αν ο μαθητής υπάρχει, 
    // να ενημερώνεται ο βαθμός του μόνο αν ο νέος βαθμός είναι μεγαλύτερος ή ίσος
    $sql = "INSERT INTO clima_grades (user_id, username, score) 
            VALUES (:user_id, :username, :score) 
            ON DUPLICATE KEY UPDATE 
                username = VALUES(username),
                score = GREATEST(score, VALUES(score))";

    $stmt = $pdo->prepare($sql);

    // 6. Εκτέλεση με ασφαλή ανάθεση παραμέτρων (Binding)
    $stmt->execute([
        ':user_id'  => $userId,
        ':username' => $username,
        ':score'    => $score
    ]);

    // 7. Επιστροφή επιτυχούς απάντησης
    echo json_encode([
        'status'  => 'success',
        'message' => 'Ο βαθμός αποθηκεύτηκε/ενημερώθηκε επιτυχώς.',
        'data'    => [
            'user_id' => $userId,
            'score'   => $score
        ]
    ]);

} catch (PDOException $e) {
    // Καταγραφή σφάλματος στο server log (για λόγους ασφαλείας δεν εκθέτουμε το σφάλμα στον χρήστη)
    error_log("Database error in save_grade.php: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Σφάλμα κατά την επικοινωνία με τη βάση δεδομένων.'
    ]);
}