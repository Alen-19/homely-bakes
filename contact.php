<?php
require_once 'connect.php';

function getUsers($conn, $search = '', $page = 1, $per_page = 12) {
    $offset = ($page - 1) * $per_page;
    
    $query = "SELECT 
                r.user_id,
                r.first_name, 
                r.last_name, 
                r.mobile_number, 
                r.city,
                l.email,
                l.user_type
              FROM table_registration r
              JOIN table_login l ON r.user_id = l.user_id
              WHERE 1=1";
    
    if (!empty($search)) {
        $search = mysqli_real_escape_string($conn, $search);
        $query .= " AND (r.first_name LIKE '%$search%' 
                         OR r.last_name LIKE '%$search%' 
                         OR r.city LIKE '%$search%'
                         OR l.email LIKE '%$search%')";
    }
    
    // Add pagination
    $query .= " LIMIT $per_page OFFSET $offset";
    
    $result = mysqli_query($conn, $query);
    $users = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) as total FROM table_registration r
                    JOIN table_login l ON r.user_id = l.user_id
                    WHERE 1=1";
    if (!empty($search)) {
        $count_query .= " AND (r.first_name LIKE '%$search%' 
                              OR r.last_name LIKE '%$search%' 
                              OR r.city LIKE '%$search%'
                              OR l.email LIKE '%$search%')";
    }
    
    $count_result = mysqli_query($conn, $count_query);
    $total_users = mysqli_fetch_assoc($count_result)['total'];
    
    return [
        'users' => $users,
        'total' => $total_users,
        'pages' => ceil($total_users / $per_page)
    ];
}

$search = $_GET['search'] ?? '';
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$result = getUsers($conn, $search, $current_page);
$users = $result['users'];
$total_pages = $result['pages'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Directory - Homely Bakes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="contact.css">
</head>
<body>
    <?php include "header.php"?>

    <div class="page-header">
        <h1>Contact Directory</h1>
        <p>Connect with our community of bakers and food enthusiasts</p>
    </div>

    <div class="search-container">
        <form method="GET" class="search-form" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%;">
            <input type="text" 
                   name="search" 
                   class="search-input" 
                   placeholder="Search by name, city, or email"
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="search-button">
                <i class="fas fa-search"></i> Search
            </button>
        </form>
    </div>

    <div class="user-grid">
        <?php if (empty($users)): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h2>No users found</h2>
                <p>Try adjusting your search criteria</p>
            </div>
        <?php else: ?>
            <?php foreach ($users as $user): ?>
                <div class="user-card">
                    <h3>
                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                        <?php if ($user['user_type'] === '0'): ?>
                            <i class="fas fa-cookie" title="Baker"></i>
                        <?php endif; ?>
                    </h3>
                    <div class="user-info">
                        <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
                        <p><i class="fas fa-phone"></i> <?= htmlspecialchars($user['mobile_number']) ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($user['city']) ?></p>
                    </div>
                    <div class="contact-buttons">
                        <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="contact-btn email-btn">
                            <i class="fas fa-envelope"></i> Email
                        </a>
                        <a href="tel:<?= htmlspecialchars($user['mobile_number']) ?>" class="contact-btn call-btn">
                            <i class="fas fa-phone"></i> Call
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                       class="page-link <?= $i === $current_page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add loading state when searching
        document.querySelector('.search-form').addEventListener('submit', function() {
            document.querySelector('.user-grid').innerHTML = `
                <div class="loading">
                    <div class="loading-spinner"></div>
                    <p>Searching...</p>
                </div>
            `;
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>