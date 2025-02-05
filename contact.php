<?php
// Database connection
require_once 'connect.php';

// Function to fetch users with optional search
function getUsers($conn, $search = '') {
    $query = "SELECT 
                r.user_id,
                r.first_name, 
                r.last_name, 
                r.mobile_number, 
                r.city,
                l.email
              FROM table_registration r
              JOIN table_login l ON r.user_id = l.user_id
              WHERE 1=1";
    
    // Add search conditions
    if (!empty($search)) {
        $search = mysqli_real_escape_string($conn, $search);
        $query .= " AND (r.first_name LIKE '%$search%' 
                         OR r.last_name LIKE '%$search%' 
                         OR r.city LIKE '%$search%')";
    }
    
    $result = mysqli_query($conn, $query);
    $users = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    return $users;
}

// Process search
$search = $_GET['search'] ?? '';
$users = getUsers($conn, $search);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Directory</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .search-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .search-input {
            width: 300px;
            padding: 10px;
            border: 2px solid #ff6f61;
            border-radius: 5px;
            font-size: 16px;
        }
        .search-button {
            padding: 10px 20px;
            background-color: #ff6f61;
            color: white;
            border: none;
            border-radius: 5px;
            margin-left: 10px;
            cursor: pointer;
        }
        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .user-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
            margin-top:40px;
        }
        .user-card:hover {
            transform: scale(1.05);
        }
        .user-card h3 {
            margin: 10px 0;
            color: #333;
        }
        .user-card p {
            color: #666;
            margin: 5px 0;
        }
    </style>
</head>
<body>
<?php include "header.php"?>
    <div class="search-container">
        <form method="GET">
            <input type="text" 
                   name="search" 
                   class="search-input" 
                   placeholder="Search by name or city"
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="search-button">Search</button>
        </form>
    </div>

    <div class="user-grid">
        <?php if (empty($users)): ?>
            <p style="width: 100%; text-align: center;">No users found.</p>
        <?php else: ?>
            <?php foreach ($users as $user): ?>
                <div class="user-card">
                    <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($user['mobile_number']) ?></p>
                    <p><strong>City:</strong> <?= htmlspecialchars($user['city']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
// Close the database connection
mysqli_close($conn);
?>