<?php
// 1. Ορισμός Ζώνης Ώρας Ελλάδος
date_default_timezone_set('Europe/Athens');

// 2. Συμπερίληψη αρχείου σύνδεσης
require "db.php";

// 3. Ανάκτηση εγγραφών απευθείας από τη βάση
$stmt = $pdo->query("SELECT user_id, username, score, updated_at FROM clima_grades ORDER BY score DESC, updated_at ASC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Υπολογισμός στατιστικών (χωρίς μέσο όρο)
$totalStudents = count($students);
$completedCount = 0;

foreach ($students as $student) {
    if ((int)$student['score'] === 100) {
        $completedCount++;
    }
}

$completionRate = $totalStudents > 0 ? round(($completedCount / $totalStudents) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Πίνακας Ελέγχου Εκπαιδευτικού - Προσομοίωση Κλίματος</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        h1 {
            margin: 0;
            color: #2a6caf;
            font-size: 24px;
        }

        /* Κάρτες Στατιστικών */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-align: center;
            border-top: 4px solid #3498db;
        }

        .stat-card.green { border-top-color: #2ecc71; }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin-top: 5px;
            color: #2c3e50;
        }

        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Πίνακας Δεδομένων */
        .table-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: 14px 18px;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f1f5f9;
        }

        /* Badges Κατάστασης */
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-0 { background-color: #e74c3c; color: white; }
        .badge-progress { background-color: #f39c12; color: white; }
        .badge-100 { background-color: #2ecc71; color: white; }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <h1>Πίνακας Ελέγχου</h1>
        <small style="color: #7f8c8d;">Τελευταία ενημέρωση: <?php echo date('H:i:s'); ?></small>
    </div>

    <!-- Κάρτες Στατιστικών -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Συνδεδεμένοι φοιτητές</div>
            <div class="stat-value"><?php echo $totalStudents; ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Ολοκλήρωσαν...</div>
            <div class="stat-value"><?php echo $completedCount; ?> <span style="font-size:16px; font-weight:normal;">(<?php echo $completionRate; ?>%)</span></div>
        </div>
    </div>

    <!-- Πίνακας Μαθητών -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ονοματεπώνυμο φοιτητή</th>
                    <th>Πρόοδος</th>
                    <th>Κατάσταση</th>
                    <th>Τελευταία Δραστηριότητα</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($totalStudents > 0): ?>
                    <?php foreach ($students as $student): ?>
                        <?php 
                            $score = max(0, min(100, (int)$student['score']));
                            $badgeClass = 'badge-progress';
                            $statusText = 'Σε εξέλιξη';

                            if ($score === 0) {
                                $badgeClass = 'badge-0';
                                $statusText = 'Μόλις ξεκίνησε';
                            } elseif ($score === 100) {
                                $badgeClass = 'badge-100';
                                $statusText = 'Ολοκληρώθηκε';
                            }

                            // Διόρθωση ώρας για την εμφάνιση της τελευταίας δραστηριότητας
                            $date = new DateTime($student['updated_at']);
                            $date->setTimezone(new DateTimeZone('Europe/Athens'));
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['user_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><strong><?php echo htmlspecialchars($student['username'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><strong><?php echo $score; ?>%</strong></td>
                            <td>
                                <span class="badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
                            </td>
                            <td><?php echo $date->format('d/m/Y H:i'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-data">Δεν έχουν καταγραφεί ακόμη δεδομένα μαθητών.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
// Αυτόματη ανανέωση της σελίδας κάθε 5 δευτερόλεπτα (5000ms)
setInterval(() => {
    window.location.reload();
}, 5000);
</script>

</body>
</html>