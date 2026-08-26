<?php
// 1. Έλεγχος Ασφαλείας LTI
if (!defined('LTI_AUTHORIZED') || LTI_AUTHORIZED !== true) {
    http_response_code(403);
    die("<h2>403 Απαγόρευση Πρόσβασης</h2><p>Δεν έχετε δικαίωμα πρόσβασης. Παρακαλώ συνδεθείτε μέσω LAMS.</p>");
}

// 2. Ανάκτηση Στοιχείων Χρήστη
$userId = $_POST['user_id'] ?? 'guest';
$userName = $_POST['lis_person_name_full'] ?? ($_POST['lis_person_name_given'] ?? 'Μαθητής');
?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προσομοίωση Κλιματικής Αλλαγής στην Ελλάδα</title>

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            color: #333;
            margin: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        h2 {
            color: #2a6caf;
            text-align: center;
            padding: 0px;
            margin: 0px;
        }

        .controls-panel {
            padding: 15px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: space-between;
            align-items: center;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        label {
            font-weight: bold;
            color: #495057;
        }

        select,
        input[type="range"] {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            font-size: 15px;
            background: #FFFFc5;
        }

        input[type="range"] {
            cursor: pointer;
            width: 200px;
        }

        .year-display {
            font-weight: bold;
            color: #27ae60;
            font-size: 16px;
        }

        button {
            background-color: #3498db;
            color: white;
            cursor: pointer;
            border: none;
            font-weight: bold;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            transition: background 0.3s ease;
            align-self: flex-end;
        }

        button:hover {
            background-color: #2980b9;
        }

        .legend-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
            margin-bottom: 0;
            font-weight: bold;
            font-size: 14px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .box-temp {
            width: 15px;
            height: 15px;
            background: #e74c3c;
            border-radius: 3px;
        }

        .box-hum {
            width: 15px;
            height: 15px;
            background: #3498db;
            border-radius: 3px;
        }

        .chart-container {
            width: 100%;
            display: flex;
            justify-content: center;
        }

        #instructions {
            font-size: 15px;
            color: rgb(100, 116, 139);
            text-align: center;
            min-height: 52px;
            display: block;
            margin: 10px 0;
        }

        #prediction {
            margin-top: 10px;
            font-weight: bold;
            color: #c0392b;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
    </style>
</head>

<body>

<div class="container">
    <h2>Κλιματική Αλλαγή στην Ελλάδα</h2>

    <div id="scoreContainer" style="display: flex; align-items: center; justify-content: center; gap: 5px; margin: 10px auto; max-width: max-content;"> 
        ( <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?> Ολοκλήρωση δραστηριότητας: <span id="scoreText">0</span>% )
    </div>

    <div class="controls-panel">
        <div class="control-group">
            <label for="scenarioSelect">Σενάριο / Μοντέλο Κλίματος:</label>
            <select id="scenarioSelect">
                <option value="A1B">A1B (Ισορροπημένο Σενάριο IPCC)</option>
                <option value="A2">A2 (Υψηλές Εκπομπές - Απαισιόδοξο)</option>
                <option value="B2">B2 (Χαμηλές Εκπομπές - Οικολογικό)</option>
                <option value="RegCM_A1B">RegCM (Περιφερειακό Μοντέλο Ελλάδας)</option>
            </select>
        </div>

        <div class="control-group">
            <label for="yearSlider">Πρόβλεψη για το έτος:
                <span id="yearVal" class="year-display">2050</span>
            </label>
            <input type="range" id="yearSlider" min="2000" max="2100" step="5" value="2050"
                   oninput="document.getElementById('yearVal').innerText=this.value">
        </div>

        <button id="runBtn">Προσομοίωση</button>
    </div>

    <div id="instructions">Επιλέξτε ένα κλιματικό σενάριο, επιλέξτε το Έτος Πρόβλεψης και πατήστε "Προσομοίωση".</div>

    <div class="chart-container">
        <svg id="customSvgChart" viewBox="0 0 800 400" style="background: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); display: block; margin: 0 auto; max-width: 100%; height: auto;">
            <style>
                #customSvgChart path.animated-line {
                    transition: stroke-dashoffset 4.0s ease-in-out;
                }
            </style>
        </svg>
    </div>

    <div class="legend-container">
        <div class="legend-item">
            <div class="box-temp"></div> Θερμοκρασία (°C)
        </div>
        <div class="legend-item">
            <div class="box-hum"></div> Υγρασία (%)
        </div>
    </div>
</div>

<script>
// Στοιχεία Χρήστη από PHP
const LAMS_USER = {
    id: "<?php echo htmlspecialchars($userId, ENT_QUOTES, 'UTF-8'); ?>",
    name: "<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>"
};

const width = 800;
const height = 400;
const padding = 60;

const testedScenarios = new Set();
const savedScores = new Set(); // Καταγράφει ποια βαθμολογία έχει ήδη σταλεί στη ΒΔ
let currentClimateState = "";
let animFrameId = null;

// ==========================================================
// ΣΥΝΑΡΤΗΣΗ: Αποστολή Βαθμολογίας στη Βάση Δεδομένων
// ==========================================================
function submitGradeToDB(score) {
    const formData = new FormData();
    formData.append('user_id', LAMS_USER.id);
    formData.append('user_name', LAMS_USER.name);
    formData.append('score', score);

    fetch('save_grade.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            console.log(`Ο βαθμός (${score}%) αποθηκεύτηκε επιτυχώς στη ΒΔ!`);
        } else {
            console.error('Σφάλμα αποθήκευσης:', data.message);
        }
    })
    .catch(error => {
        console.error('Σφάλμα δικτύου κατά την αποθήκευση βαθμού:', error);
    });
}

// ==========================================================
// ΕΝΗΜΕΡΩΜΕΝΗ ΣΥΝΑΡΤΗΣΗ: Υπολογισμός & Αποθήκευση Προόδου
// ==========================================================
function updateScore(scenario) {
    testedScenarios.add(scenario);
    const currentScore = Math.min(testedScenarios.size * 25, 100);

    const scoreTextEl = document.getElementById('scoreText');
    if (scoreTextEl) {
        scoreTextEl.textContent = currentScore;
    }

    // Αν το τρέχον score είναι > 0 και ΔΕΝ έχει σταλεί ξανά στη ΒΔ
    if (currentScore > 0 && !savedScores.has(currentScore)) {
        savedScores.add(currentScore); // Σημείωσε ότι το στείλαμε
        submitGradeToDB(currentScore); // Αποστολή στη ΒΔ (25%, 50%, 75% ή 100%)

        // Αν φτάσαμε στο 100%, εμφάνισε ειδοποίηση ολοκλήρωσης
        if (currentScore === 100) {
            setTimeout(() => {
                alert(`Συγχαρητήρια ${LAMS_USER.name}! Ολοκλήρωσες επιτυχώς όλα τα σενάρια!`);
            }, 4200);
        }
    }
}

function runSimulationData() {
    const scenario = document.getElementById('scenarioSelect').value;
    const targetYear = parseInt(document.getElementById('yearSlider').value);

    updateScore(scenario);

    const baseYear = 2000;
    const baseTemp = 15.5;
    const baseHumidity = 65;
    let timeline = [];

    for (let year = baseYear; year <= targetYear; year += 5) {
        const yearsPassed = year - baseYear;
        let temp = baseTemp;
        let humidity = baseHumidity;

        if (scenario === 'A2') {
            temp += yearsPassed * 0.045;
            humidity -= yearsPassed * 0.15;
        } else if (scenario === 'B2') {
            temp += yearsPassed * 0.022;
            humidity -= yearsPassed * 0.05;
        } else if (scenario === 'A1B') {
            temp += yearsPassed * 0.035;
            humidity -= yearsPassed * 0.10;
        } else if (scenario === 'RegCM_A1B') {
            temp += yearsPassed * 0.039;
            humidity -= yearsPassed * 0.14;
        }

        timeline.push({
            year: year,
            temp: parseFloat(temp.toFixed(2)),
            humidity: Math.max(10, Math.round(humidity))
        });
    }

    const finalInfo = timeline[timeline.length - 1];
    const startInfo = timeline[0];

    let state = "Σταθερό Κλίμα";
    if (finalInfo.temp > 18.5) {
        state = "Έντονη Ερημοποίηση & Συχνοί Καύσωνες";
    } else if (finalInfo.temp > 17.0) {
        state = "Αύξηση Ακραίων Καιρικών Φαινομένων (Medicane)";
    } else if (finalInfo.temp > 16.0) {
        state = "Ήπια Κλιματική Μεταβολή";
    }

    currentClimateState = state;

    const instructions = document.getElementById('instructions');
    if (instructions) {
        instructions.style.display = 'block';
        instructions.innerHTML = 
            `Μέση Ετήσια Θερμοκρασία: <span style="color:#e74c3c; font-weight:bold;">` +
            `<span id="animTemp">${startInfo.temp.toFixed(2)}</span>°C (` +
            `<span id="animTempDiff">+0.00</span>°C)</span> | ` +
            `Μέση Ετήσια Υγρασία: <span style="color:#3498db; font-weight:bold;">` +
            `<span id="animHum">${startInfo.humidity}</span>% (` +
            `<span id="animHumDiff">0</span>%)</span>` +
            `<div id="prediction"></div>`;
    }

    setTimeout(() => {
        drawChartData(timeline);
        animateResultValues(startInfo.temp, finalInfo.temp, startInfo.humidity, finalInfo.humidity, 4000);
    }, 200);
}

function drawAxesOnly() {
    const svg = document.getElementById('customSvgChart');
    if (!svg) return;

    const styleTag = svg.querySelector('style');
    svg.innerHTML = '';
    if (styleTag) svg.appendChild(styleTag);

    let gridLines = '';

    for (let i = 0; i <= 5; i++) {
        let y = padding + (i * (height - 2 * padding) / 5);
        gridLines += `<line x1="${padding}" y1="${y}" x2="${width - padding}" y2="${y}" stroke="#f1f5f9" stroke-width="1" />`;

        let tempLabel = (21 - (i * (21 - 14) / 5)).toFixed(1);
        gridLines += `<text x="${padding - 10}" y="${y + 4}" font-size="12" fill="#e74c3c" text-anchor="end">${tempLabel}°C</text>`;

        let humLabel = Math.round(75 - (i * (75 - 30) / 5));
        gridLines += `<text x="${width - padding + 10}" y="${y + 4}" font-size="12" fill="#3498db" text-anchor="start">${humLabel}%</text>`;
    }

    gridLines += `<line x1="${padding}" y1="${padding}" x2="${padding}" y2="${height - padding}" stroke="#94a3b8" stroke-width="2" />`;
    gridLines += `<line x1="${width - padding}" y1="${padding}" x2="${width - padding}" y2="${height - padding}" stroke="#94a3b8" stroke-width="2" />`;
    gridLines += `<line x1="${padding}" y1="${height - padding}" x2="${width - padding}" y2="${height - padding}" stroke="#94a3b8" stroke-width="2" />`;

    gridLines += `<path id="tempPath" class="animated-line" fill="none" stroke="#e74c3c" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" d="" />`;
    gridLines += `<path id="humPath" class="animated-line" fill="none" stroke="#3498db" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" d="" />`;

    svg.innerHTML += gridLines;
}

function animatePathDrawing(pathElement) {
    if (!pathElement) return;
    pathElement.style.transition = 'none';
    const length = pathElement.getTotalLength();
    pathElement.style.strokeDasharray = length + ' ' + length;
    pathElement.style.strokeDashoffset = length;
    pathElement.getBoundingClientRect();
    pathElement.style.transition = 'stroke-dashoffset 4.0s ease-out';
    pathElement.style.strokeDashoffset = '0';
}

function animateResultValues(startTemp, endTemp, startHum, endHum, duration) {
    const tempEl = document.getElementById('animTemp');
    const humEl = document.getElementById('animHum');
    const tempDiffEl = document.getElementById('animTempDiff');
    const humDiffEl = document.getElementById('animHumDiff');

    if (!tempEl || !humEl || !tempDiffEl || !humDiffEl) return;

    if (animFrameId) cancelAnimationFrame(animFrameId);

    const startTime = performance.now();

    function update(now) {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const easeProgress = progress * (2 - progress);

        const currentTemp = startTemp + (endTemp - startTemp) * easeProgress;
        const currentHum = startHum + (endHum - startHum) * easeProgress;

        const diffTemp = currentTemp - startTemp;
        const diffHum = currentHum - startHum;

        tempEl.textContent = currentTemp.toFixed(2);
        humEl.textContent = Math.round(currentHum);
        tempDiffEl.textContent = (diffTemp >= 0 ? '+' : '') + diffTemp.toFixed(2);
        humDiffEl.textContent = (diffHum >= 0 ? '+' : '') + Math.round(diffHum);

        if (progress < 1) {
            animFrameId = requestAnimationFrame(update);
        } else {
            const prediction = document.getElementById('prediction');
            if (prediction) {
                prediction.style.opacity = '1';
                prediction.innerHTML = `<span style="font-weight:bold;">${currentClimateState}</span>`;
            }
        }
    }

    animFrameId = requestAnimationFrame(update);
}

function drawChartData(timeline) {
    const svg = document.getElementById('customSvgChart');
    if (!svg || !timeline || timeline.length === 0) return;

    svg.querySelectorAll('.x-axis-label, .x-axis-tick').forEach(el => el.remove());

    const totalPoints = timeline.length;
    const xStep = totalPoints > 1 ? (width - 2 * padding) / (totalPoints - 1) : (width - 2 * padding);

    let tempD = "";
    let humD = "";

    timeline.forEach((item, index) => {
        let x = padding + (index * xStep);
        let yTemp = height - padding - ((item.temp - 14) / (21 - 14)) * (height - 2 * padding);
        let yHum = height - padding - ((item.humidity - 30) / (75 - 30)) * (height - 2 * padding);

        if (index === 0) {
            tempD += `M ${x},${yTemp} `;
            humD += `M ${x},${yHum} `;
        } else {
            tempD += `L ${x},${yTemp} `;
            humD += `L ${x},${yHum} `;
        }

        if (item.year % 20 === 0 || index === totalPoints - 1 || index === 0) {
            svg.innerHTML += `<text x="${x}" y="${height - padding + 20}" font-size="12" fill="#64748b" text-anchor="middle" class="x-axis-label">${item.year}</text>`;
            svg.innerHTML += `<line x1="${x}" y1="${height - padding}" x2="${x}" y2="${height - padding + 5}" stroke="#cbd5e1" class="x-axis-tick" />`;
        }
    });

    const tempPath = document.getElementById('tempPath');
    const humPath = document.getElementById('humPath');

    if (tempPath && humPath) {
        tempPath.setAttribute('d', tempD);
        humPath.setAttribute('d', humD);
        animatePathDrawing(tempPath);
        animatePathDrawing(humPath);
    }
}

function resetChartAndResults() {
    if (animFrameId) cancelAnimationFrame(animFrameId);

    const tempPath = document.getElementById('tempPath');
    const humPath = document.getElementById('humPath');

    if (tempPath) tempPath.setAttribute('d', '');
    if (humPath) humPath.setAttribute('d', '');

    const svg = document.getElementById('customSvgChart');
    if (svg) {
        svg.querySelectorAll('.x-axis-label, .x-axis-tick').forEach(el => el.remove());
    }

    const instructions = document.getElementById('instructions');
    if (instructions) {
        instructions.style.display = 'block';
        instructions.innerHTML = `Επιλέξτε ένα κλιματικό σενάριο, επιλέξτε το Έτος Πρόβλεψης και πατήστε "Προσομοίωση".`;
    }

    currentClimateState = "";
}

window.addEventListener('DOMContentLoaded', () => {
    drawAxesOnly();

// Καταγραφή αρχικής εισόδου μαθητή με 0% στη ΒΔ
    submitGradeToDB(0);

    const btn = document.getElementById('runBtn');
    if (btn) {
        btn.addEventListener('click', () => {
            resetChartAndResults();
            runSimulationData();
        });
    }

    const scenarioSelect = document.getElementById('scenarioSelect');
    if (scenarioSelect) scenarioSelect.addEventListener('change', resetChartAndResults);

    const yearSlider = document.getElementById('yearSlider');
    if (yearSlider) yearSlider.addEventListener('input', resetChartAndResults);
});
</script>

</body>
</html>