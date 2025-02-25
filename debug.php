<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'connect.php';

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Connected successfully to database<br>";

// Check if database exists
$result = mysqli_query($conn, "SELECT DATABASE()");
$row = mysqli_fetch_row($result);
echo "Current database: " . $row[0] . "<br>";

// List all tables
$result = mysqli_query($conn, "SHOW TABLES");
echo "<h3>Tables in database:</h3>";
if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_row($result)) {
        echo $row[0] . "<br>";
    }
} else {
    echo "No tables found<br>";
}

// Check table_product structure
echo "<h3>table_product structure:</h3>";
$result = mysqli_query($conn, "DESCRIBE table_product");
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}<br>";
    }
} else {
    echo "Error getting table_product structure: " . mysqli_error($conn) . "<br>";
}

// Check table_baker structure
echo "<h3>table_baker structure:</h3>";
$result = mysqli_query($conn, "DESCRIBE table_baker");
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}<br>";
    }
} else {
    echo "Error getting table_baker structure: " . mysqli_error($conn) . "<br>";
}

// Check table_category structure
echo "<h3>table_category structure:</h3>";
$result = mysqli_query($conn, "DESCRIBE table_category");
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}<br>";
    }
} else {
    echo "Error getting table_category structure: " . mysqli_error($conn) . "<br>";
}

// Try simple select from each table
echo "<h3>Sample data:</h3>";
$tables = ['table_product', 'table_baker', 'table_category'];
foreach ($tables as $table) {
    echo "Checking $table:<br>";
    $result = mysqli_query($conn, "SELECT * FROM $table LIMIT 1");
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            echo "Found data in $table<br>";
        } else {
            echo "No data in $table<br>";
        }
    } else {
        echo "Error querying $table: " . mysqli_error($conn) . "<br>";
    }
}

mysqli_close($conn);
?>
