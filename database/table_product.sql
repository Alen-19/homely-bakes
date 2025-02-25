CREATE TABLE IF NOT EXISTS table_product (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    baker_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    category_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (baker_id) REFERENCES table_baker(baker_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES table_category(category_id) ON DELETE CASCADE,
    INDEX idx_baker_id (baker_id),
    INDEX idx_category_id (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
