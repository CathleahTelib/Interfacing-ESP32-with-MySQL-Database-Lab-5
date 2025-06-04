<?php
$servername = "localhost";
$username = "root";
$password = "";
$database_name = "esp32_sensor";

// Create connection
$conn = new mysqli($servername, $username, $password, $database_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to select data from tbl_temp
$sql = "SELECT temp_id, temp_value, date_collected FROM temp_data ORDER BY date_collected DESC";
$result = $conn->query($sql);

// Begin HTML output
echo "<style>
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: center;
    }
    th {
        background-color: #4CAF50;
        color: white;
    }
    tr:hover {
        background-color: #007BFF; /* Blue highlight */
        color: white; /* Change text color to white on hover */
    }
    h2 {
        text-align: center;
        color: #333;
    }
</style>";

echo "<h2>Temperature Monitoring</h2>"; // Title
echo "<table>
        <tr>
            <th>Temperature ID</th>
            <th>Temperature Value (°C)</th>
            <th>Date Collected</th>
        </tr>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['temp_id']}</td>
                <td>{$row['temp_value']}</td>
                <td>{$row['date_collected']}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='3'>No results found.</td></tr>";
}

echo "</table>";

// Close the connection
$conn->close();
?>