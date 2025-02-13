<?php
// setup_database.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "homely_bakes";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully\n";
} else {
    echo "Error creating database: " . $conn->error . "\n";
}

// Select the database
$conn->select_db($dbname);

// SQL to create tables and insert data
$sql = "
-- Create Tables
CREATE TABLE IF NOT EXISTS bakers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    location VARCHAR(100),
    rating DECIMAL(3,2),
    profile_image VARCHAR(255),
    specialties VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    baker_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50),
    image_url VARCHAR(255),
    stock INT DEFAULT 0,
    FOREIGN KEY (baker_id) REFERENCES bakers(id)
);

-- Insert Demo Bakers
INSERT INTO bakers (name, description, location, rating, profile_image, specialties) VALUES
('Sarah''s Sweet Treats', 'Specializing in custom celebration cakes and French pastries', 'New York, NY', 4.8, 'baker_profiles/sarah.jpg', 'Wedding Cakes, Macarons'),
('Mike''s Artisan Bread', 'Traditional sourdough and European-style breads', 'Brooklyn, NY', 4.7, 'baker_profiles/mike.jpg', 'Sourdough, Baguettes'),
('Sweet Dreams by Emily', 'Unique cupcake flavors and themed birthday cakes', 'Queens, NY', 4.9, 'baker_profiles/emily.jpg', 'Cupcakes, Birthday Cakes'),
('The Cookie Master', 'Gourmet cookies and cookie cakes', 'Manhattan, NY', 4.6, 'baker_profiles/cookie_master.jpg', 'Cookies, Cookie Cakes'),
('Fresh Bakes by David', 'All-natural ingredients and vegan options available', 'Bronx, NY', 4.5, 'baker_profiles/david.jpg', 'Vegan Pastries, Gluten-free');

-- Insert Demo Products
INSERT INTO products (baker_id, name, description, price, category, image_url, stock) VALUES
-- Sarah's Sweet Treats Products
('1', 'Classic Vanilla Wedding Cake', 'Three-tier vanilla cake with buttercream frosting', 299.99, 'cakes', 'products/wedding_cake.jpg', 5),
('1', 'French Macarons Box', 'Assorted flavors - 12 pieces', 24.99, 'pastries', 'products/macarons.jpg', 20),
('1', 'Red Velvet Cupcakes', 'Box of 6 cupcakes with cream cheese frosting', 18.99, 'pastries', 'products/red_velvet.jpg', 15),

-- Mike's Artisan Bread Products
('2', 'Traditional Sourdough', 'Naturally fermented sourdough bread', 8.99, 'bread', 'products/sourdough.jpg', 25),
('2', 'French Baguette', 'Classic crusty French baguette', 4.99, 'bread', 'products/baguette.jpg', 30),
('2', 'Multigrain Loaf', 'Healthy blend of various grains and seeds', 7.99, 'bread', 'products/multigrain.jpg', 20),

-- Sweet Dreams by Emily Products
('3', 'Rainbow Unicorn Cake', 'Colorful layered cake with unicorn decoration', 49.99, 'cakes', 'products/unicorn_cake.jpg', 8),
('3', 'Mini Cupcake Party Pack', '24 mini cupcakes in assorted flavors', 34.99, 'pastries', 'products/mini_cupcakes.jpg', 12),
('3', 'Custom Photo Cake', 'Personalized cake with edible photo print', 59.99, 'cakes', 'products/photo_cake.jpg', 10),

-- The Cookie Master Products
('4', 'Chocolate Chip Cookies', 'Classic cookies - Box of 12', 15.99, 'cookies', 'products/choc_chip.jpg', 40),
('4', 'Giant Cookie Cake', 'Personalized message available', 29.99, 'cookies', 'products/cookie_cake.jpg', 15),
('4', 'Assorted Cookie Box', 'Mix of 24 gourmet cookies', 32.99, 'cookies', 'products/assorted_cookies.jpg', 25),

-- Fresh Bakes by David Products
('5', 'Vegan Chocolate Cake', 'Rich chocolate cake made with plant-based ingredients', 39.99, 'cakes', 'products/vegan_cake.jpg', 10),
('5', 'Gluten-Free Muffins', 'Pack of 6 blueberry muffins', 16.99, 'pastries', 'products/gf_muffins.jpg', 18),
('5', 'Vegan Cinnamon Rolls', 'Pack of 4 dairy-free cinnamon rolls', 14.99, 'pastries', 'products/cinnamon_rolls.jpg', 22);
";

// Execute multi query
if ($conn->multi_query($sql)) {
    echo "Tables created and data inserted successfully\n";
} else {
    echo "Error: " . $sql . "\n" . $conn->error;
}

$conn->close();
echo "Database setup completed!\n";
?>