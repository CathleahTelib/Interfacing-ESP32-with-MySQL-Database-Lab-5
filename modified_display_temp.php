<?php
$servername = "localhost";
$username = "root";
$password = "";
$database_name = "esp32_sensor";

// Handle AJAX requests for live data
if (isset($_GET['ajax']) && $_GET['ajax'] == 'latest') {
    header('Content-Type: application/json');
    $conn = new mysqli($servername, $username, $password, $database_name);
    
    if ($conn->connect_error) {
        echo json_encode(['error' => 'Connection failed']);
        exit;
    }
    
    $sql = "SELECT temp_id, temp_value, uv_index, date_collected FROM temp_data ORDER BY date_collected DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'No data found']);
    }
    
    $conn->close();
    exit;
}

// Get initial data for page load
$conn = new mysqli($servername, $username, $password, $database_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT temp_id, temp_value, temp_description, uv_index, date_collected FROM temp_data ORDER BY date_collected DESC";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather-Weather Lang</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
            padding: 80px 20px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            font-size: 3.5rem;
            margin-bottom: 60px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb, #f5576c);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientShift 4s ease infinite;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
            letter-spacing: 2px;
            position: relative;
            padding-top: 20px;
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        @keyframes glow {
            from { box-shadow: 0 0 10px rgba(102, 126, 234, 0.5); }
            to { box-shadow: 0 0 20px rgba(118, 75, 162, 0.8), 0 0 30px rgba(102, 126, 234, 0.3); }
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4);
            opacity: 0.7;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
        }

        .temp-card {
            text-align: center;
        }

        .temp-icon {
            font-size: 4rem;
            margin-bottom: 15px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        }

        .temp-value {
            font-size: 3rem;
            font-weight: 600;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .temp-time {
            font-size: 0.9rem;
            opacity: 0.8;
            font-weight: 300;
        }

        .uv-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05));
        }

        .uv-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .uv-main {
            font-size: 4rem;
            font-weight: 300;
            margin-bottom: 5px;
            line-height: 1;
        }

        .uv-category {
            font-size: 1.4rem;
            font-weight: 500;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .uv-bar {
            height: 6px;
            background: linear-gradient(to right, 
                #4CAF50 0%, 
                #8BC34A 16.67%, 
                #FFEB3B 33.33%, 
                #FF9800 50%, 
                #FF5722 66.67%, 
                #9C27B0 83.33%, 
                #673AB7 100%);
            border-radius: 3px;
            position: relative;
            margin-bottom: 20px;
        }

        .uv-indicator {
            position: absolute;
            top: -4px;
            width: 14px;
            height: 14px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            transition: left 0.3s ease;
        }

        .uv-advice {
            font-size: 0.95rem;
            line-height: 1.4;
            opacity: 0.85;
            font-weight: 400;
        }

        .stats-section {
            margin: 60px 0;
        }

        .stats-title {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 30px;
            font-weight: 600;
            opacity: 0.9;
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .chart-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .chart-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        canvas {
            border-radius: 10px;
        }

        .data-table {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: background 0.2s ease;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }

        .table-row.hidden {
            display: none;
        }

        .see-more-container {
            text-align: center;
            margin: 20px 0;
        }

        .see-more-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .see-more-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .see-more-btn:active {
            transform: translateY(0);
        }

        .status-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .status-online {
            background: rgba(76, 175, 80, 0.9);
            color: white;
        }

        .status-offline {
            background: rgba(244, 67, 54, 0.9);
            color: white;
        }

        .last-update {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8rem;
            opacity: 0.7;
        }

        @media (max-width: 768px) {
            body {
                padding: 60px 15px 20px;
            }
            
            .dashboard {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            h1 {
                font-size: 2.5rem;
                margin-bottom: 40px;
            }
            
            .uv-main {
                font-size: 3rem;
            }
            
            .temp-value {
                font-size: 2.5rem;
            }
            
            .charts-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="status-indicator" id="status-indicator">● Live</div>
    
    <div class="container">
        <h1>Weather-Weather Lang</h1>
        
        <div class="dashboard">
            <div class="card temp-card">
                <div class="temp-icon" id="temp-icon">🌡️</div>
                <div class="temp-value" id="temp-value">--°C</div>
                <div class="temp-time" id="temp-desc">Loading...</div>
                <div class="temp-time" id="temp-time">Loading...</div>
            </div>
            
            <div class="card uv-card">
                <div class="uv-header">
                    <span>☀️</span>
                    <span>UV INDEX</span>
                </div>
                <div class="uv-main" id="uv-main">--</div>
                <div class="uv-category" id="uv-category">Loading...</div>
                <div class="uv-bar">
                    <div class="uv-indicator" id="uv-indicator"></div>
                </div>
                <div class="uv-advice" id="uv-advice">Fetching UV data...</div>
            </div>
        </div>
        
        <!-- Statistics Section -->
        <div class="stats-section">
            <h2 class="stats-title">📊 Statistics & Trends</h2>
            <div class="charts-container">
                <div class="chart-card">
                    <div class="chart-header">
                        <span>🌡️</span>
                        <span>Temperature Trend</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="tempChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <span>☀️</span>
                        <span>UV Index Trend</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="uvChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Temperature (°C)</th>
                        <th>Temperature Description</th>
                        <th>UV Index</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody id="data-tbody" style="text-align: center;">
                    <?php foreach ($data as $index => $row): ?>
                        <tr class="table-row <?= $index >= 10 ? 'hidden' : '' ?>" onclick='displayData(<?php echo json_encode($row); ?>)'>
                            <td><?= htmlspecialchars($row['temp_id']) ?></td>
                            <td><?= number_format($row['temp_value'], 1) ?></td>
                            <td><?= htmlspecialchars($row['temp_description']) ?></td>
                            <td><?= htmlspecialchars($row['uv_index']) ?></td>
                            <td><?= htmlspecialchars($row['date_collected']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (count($data) > 10): ?>
        <div class="see-more-container">
            <button class="see-more-btn" id="see-more-btn" onclick="toggleTableRows()">
                See More Data
            </button>
        </div>
        <?php endif; ?>
        
        <div class="last-update" id="last-update">Last updated: Never</div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        let isOnline = true;
        let lastUpdateTime = null;
        let tempChart = null;
        let uvChart = null;
        let showingAllRows = false;
        let chartData = {
            temperature: [],
            uvIndex: [],
            timestamps: []
        };

        function toggleTableRows() {
            const hiddenRows = document.querySelectorAll('.table-row.hidden');
            const btn = document.getElementById('see-more-btn');
            
            if (!showingAllRows) {
                // Show all rows
                hiddenRows.forEach(row => {
                    row.classList.remove('hidden');
                });
                btn.textContent = 'Show Less';
                showingAllRows = true;
            } else {
                // Hide rows beyond first 10
                const allRows = document.querySelectorAll('.table-row');
                allRows.forEach((row, index) => {
                    if (index >= 10) {
                        row.classList.add('hidden');
                    }
                });
                btn.textContent = 'See More Data';
                showingAllRows = false;
                
                // Scroll back to table
                document.querySelector('.data-table').scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }
        }

        function initializeCharts() {
            // Temperature Chart
            const tempCtx = document.getElementById('tempChart').getContext('2d');
            tempChart = new Chart(tempCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Temperature (°C)',
                        data: [],
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                        pointBorderColor: 'rgba(255, 255, 255, 1)',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: 'rgba(255, 255, 255, 0.8)',
                                font: { size: 12 }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: 'rgba(255, 255, 255, 0.7)' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        },
                        y: {
                            ticks: { color: 'rgba(255, 255, 255, 0.7)' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        }
                    }
                }
            });

            // UV Index Chart
            const uvCtx = document.getElementById('uvChart').getContext('2d');
            uvChart = new Chart(uvCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'UV Index',
                        data: [],
                        borderColor: 'rgba(255, 206, 84, 1)',
                        backgroundColor: 'rgba(255, 206, 84, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(255, 206, 84, 1)',
                        pointBorderColor: 'rgba(255, 255, 255, 1)',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: 'rgba(255, 255, 255, 0.8)',
                                font: { size: 12 }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: 'rgba(255, 255, 255, 0.7)' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        },
                        y: {
                            ticks: { color: 'rgba(255, 255, 255, 0.7)' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' },
                            min: 0
                        }
                    }
                }
            });
        }

        function updateCharts(data) {
            const temp = parseFloat(data.temp_value);
            const uv = parseFloat(data.uv_index);
            const timestamp = new Date(data.date_collected).toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });

            // Add new data point
            chartData.temperature.push(temp);
            chartData.uvIndex.push(uv);
            chartData.timestamps.push(timestamp);

            // Keep only last 20 data points for better visibility
            if (chartData.temperature.length > 20) {
                chartData.temperature.shift();
                chartData.uvIndex.shift();
                chartData.timestamps.shift();
            }

            // Update temperature chart
            tempChart.data.labels = [...chartData.timestamps];
            tempChart.data.datasets[0].data = [...chartData.temperature];
            tempChart.update('none');

            // Update UV chart
            uvChart.data.labels = [...chartData.timestamps];
            uvChart.data.datasets[0].data = [...chartData.uvIndex];
            uvChart.update('none');
        }

        function updateStatus(online) {
            const indicator = document.getElementById('status-indicator');
            if (online) {
                indicator.textContent = '● Live';
                indicator.className = 'status-indicator status-online';
            } else {
                indicator.textContent = '● Offline';
                indicator.className = 'status-indicator status-offline';
            }
        }

        function getUVCategory(uv) {
            if (uv >= 11) return { category: 'Extreme', advice: 'Avoid being outside. UV will burn skin in minutes.', icon: '🔥' };
            if (uv >= 8) return { category: 'Very High', advice: 'Use SPF 50+, hat, and sunglasses. Limit midday sun.', icon: '🌞' };
            if (uv >= 6) return { category: 'High', advice: 'Use SPF 30+. Wear protective clothing and sunglasses.', icon: '☀️' };
            if (uv >= 3) return { category: 'Moderate', advice: 'Use sun protection until 16:00.', icon: '⛅' };
            if (uv >= 1) return { category: 'Low', advice: 'Minimal protection needed for most people.', icon: '🌤️' };
            return { category: 'None', advice: 'No UV protection needed.', icon: '🌙' };
        }

        function getTempIcon(temp) {
            if (temp >= 35) return '🔥';
            if (temp >= 30) return '🌞';
            if (temp >= 25) return '☀️';
            if (temp >= 20) return '🌤️';
            if (temp >= 15) return '⛅';
            if (temp >= 10) return '🌥️';
            if (temp >= 0) return '❄️';
            return '🧊';
        }

        function displayData(data) {
            const temp = parseFloat(data.temp_value);
            const desc = data.temp_description;
            const uv = parseFloat(data.uv_index);
            const date = new Date(data.date_collected);
            
            // Temperature card
            document.getElementById('temp-icon').textContent = getTempIcon(temp);
            document.getElementById('temp-value').textContent = temp.toFixed(1) + '°C';
            document.getElementById('temp-desc').textContent = desc;
            document.getElementById('temp-time').textContent = 'Recorded ' + date.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // UV card
            const uvInfo = getUVCategory(uv);
            document.getElementById('uv-main').textContent = uv.toFixed(1);
            document.getElementById('uv-category').textContent = uvInfo.category;
            document.getElementById('uv-advice').textContent = uvInfo.advice;
            
            // UV bar indicator
            const uvPercent = Math.min(100, (uv / 12) * 100);
            document.getElementById('uv-indicator').style.left = `calc(${uvPercent}% - 7px)`;
            
            // Update timestamp
            lastUpdateTime = new Date();
            document.getElementById('last-update').textContent = 
                'Last updated: ' + lastUpdateTime.toLocaleTimeString();
        }

        function fetchLatestData() {
            fetch('?ajax=latest')
                .then(response => response.json())
                .then(data => {
                    if (!data.error) {
                        displayData(data);
                        updateCharts(data);
                        updateStatus(true);
                        isOnline = true;
                    } else {
                        throw new Error(data.error);
                    }
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    updateStatus(false);
                    isOnline = false;
                });
        }

        // Initialize charts
        initializeCharts();
        
        // Initialize with first data point if available
        <?php if (!empty($data)): ?>
            displayData(<?php echo json_encode($data[0]); ?>);
            updateCharts(<?php echo json_encode($data[0]); ?>);
        <?php endif; ?>
        
        // Auto-refresh every 15 seconds
        setInterval(fetchLatestData, 5000);
        
        // Initial fetch
        fetchLatestData();
    </script>
</body>
</html>