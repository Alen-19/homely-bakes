<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homely Bakes</title>
    <link rel="stylesheet" href="product.css">
    <script defer src="product.js"></script>
</head>
<body>
    <?php include "header.php"?>
    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h2>Connecting You to Homemade Goodness</h2>
            <p>Find the best homemade bakers near you.</p>
            <div class="search-bar">
                <input type="text" id="search-input" placeholder="Search by name, menu, or location...">
                <select id="filter-options">
                    <option value="all">All</option>
                    <option value="name">Name</option>
                    <option value="menu">Menu</option>
                    <option value="location">Location</option>
                </select>
                <button id="search-button">Search</button>
            </div>
        </div>
    </section>

    <!-- Bakers Section -->
    <section id="bakers" class="section">
        <div class="container">
            <h2>Our Bakers</h2>
            <div class="baker-grid" id="baker-list">
                <!-- Example Baker -->
                <div class="baker-card" data-name="Jane's Cakes" data-menu="cakes pastries" data-location="New York">
                    <img src="img/bakers.jpg" alt="Baker Profile">
                    <h3>Jane's Cakes</h3>
                    <p>Specializes in custom cakes and pastries.</p>
                    <button class="view-profile">View Profile</button>
                    
                </div>
                <div class="baker-card" data-name="Mike's Bakery" data-menu="bread cookies" data-location="Los Angeles">
                    <img src="img/bakers.jpg" alt="Baker Profile">
                    <h3>Mike's Bakery</h3>
                    <p>Known for freshly baked bread and cookies.</p>
                    <button class="view-profile">View Profile</button>
                    
                </div>
                <!-- Add more baker cards here -->
            </div>
        </div>
    </section>

    <h2 class="features-heading">Food of The Gods, Freshly Baked</h2>
    <section class="feature-section">
            
        <div class="features-box1">
            <div class="feature-item">
                <h3 class="feature-title">AUTHENTIC RECECIPES</h3>
                <p class="feature-description">Our products are based on traditional home-style recipes using fresh ingredients</p>
            </div>
            <div class="feature-item">
                <H3 class="feature-title">BAKED WITH LOVE</H3>
                <p class="feature-description">Our passion for poured into every recipe,serving smiles on a plate everyday.</p>
            </div>
        </div>
            <div class="features-box2">
                <img src="img\feature-img.png" alt="image of a house">
            </div>
            <div class="feature-box3">
                <div class="feature-item">
                    <h3 class="feature-title">COMMITTED TO QUALITY</h3>
                    <p class="feature-description">From our ingredients to our kitchen operatios 
                        & guest services, we always prioritize quality
                    </p>
                </div>
                <div class="feature-item">
                    <h3 class="feature-title">HONESTLY PRICED</h3>
                    <p class="feature-description">We constantly strive to offer the best products at the right prices.</p>
                </div>
            </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Homely Bakes. All rights reserved.</p>
            <div class="social-links">
                <a href="#">Facebook</a>
                <a href="#">Instagram</a>
                <a href="#">Twitter</a>
            </div>
        </div>
    </footer>

