-- =====================================================
-- PustakMandi Database Schema
-- Online Book Marketplace (Admin / Seller / Customer)
-- =====================================================

CREATE DATABASE IF NOT EXISTS pustakmandi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pustakmandi;

-- -----------------------------------------------------
-- Table: users
-- Stores admin, seller and customer accounts
-- -----------------------------------------------------
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'seller', 'customer') NOT NULL DEFAULT 'customer',
    college VARCHAR(100) DEFAULT NULL,
     bio TEXT DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    profile_pic VARCHAR(255) DEFAULT 'default.png',
    status ENUM('active', 'blocked') DEFAULT 'active',
   last_login DATETIME DEFAULT NULL,
     created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
) ;

-- -----------------------------------------------------
-- Table: categories
-- -----------------------------------------------------
CREATE TABLE categories (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(50) NOT NULL,
    icon    VARCHAR(50) NOT NULL   -- font-awesome class, e.g. fa-solid fa-book
);
 
-- -----------------------------------------------------
-- Table: products (books)
-- -----------------------------------------------------
CREATE TABLE products (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(150) NOT NULL,
    description  TEXT,
    price        DECIMAL(10,2) NOT NULL,
    old_price    DECIMAL(10,2) DEFAULT NULL,
    category_id  INT,
    seller_id    INT,
    image        VARCHAR(255),
    stock        INT NOT NULL DEFAULT 1,
    rating       DECIMAL(2,1) DEFAULT 5.0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (seller_id)   REFERENCES users(user_id) ON DELETE SET NULL
);

-- -----------------------------------------------------
-- Table: cart
-- -----------------------------------------------------
CREATE TABLE cart (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    product_id  INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    added_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_item (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
-- ------------------------------------------
-- WISHLIST  (one row per user+product, like cart but no quantity)
-- ------------------------------------------
CREATE TABLE wishlist (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    product_id  INT NOT NULL,
    added_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist_item (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
-- -----------------------------------------------------
-- Table: orders
-- -----------------------------------------------------
CREATE TABLE orders (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    full_name      VARCHAR(100) NOT NULL,
    phone          VARCHAR(20)  NOT NULL,
    address        VARCHAR(255) NOT NULL,
    total_amount   DECIMAL(10,2) NOT NULL,
    status         ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- Table: order_items
-- -----------------------------------------------------
CREATE TABLE order_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    product_id  INT NOT NULL,
    quantity    INT NOT NULL,
    price       DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- -----------------------------------------------------
-- Table: contact_messages (for contact.php form)
-- -----------------------------------------------------
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) DEFAULT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Seed data
-- -----------------------------------------------------

-- Default admin account (email: admin@pustakmandi.com / password: Admin@123)
INSERT INTO users (full_name, username, email, password, role, status)
VALUES ('Admin', 'newadmin', 'newadmin@pustakmandi.com', 
        '$2b$10$b1j3O29UGGDWSWHzH67Zz.8F3Sre.0EZxk1hIAmEE117DBojLkaQy', 
        'admin', 'active');

INSERT INTO categories (name, icon) VALUES
('Books',       'fa-solid fa-book'),
('Notes',       'fa-solid fa-file-lines'),
('Laptops',     'fa-solid fa-laptop'),
('Calculators', 'fa-solid fa-calculator'),
('Mobiles',     'fa-solid fa-mobile-screen-button'),
('Hostel Items','fa-solid fa-couch');
 
INSERT INTO products (title, description, price, old_price, category_id, image, stock, rating) VALUES
('Operating System Book', 'Core OS concepts textbook, latest edition.', 850.00, NULL,
    1, 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=500', 12, 4.9),
('HP Pavilion Laptop', 'Used, good condition, 8GB RAM / 512GB SSD.', 52000.00, 58000.00,
    3, 'https://images.unsplash.com/photo-1517336714739-489689fd1ca8?w=500', 3, 4.6),
('Casio FX-991ES Plus', 'Scientific calculator, exam-approved.', 1800.00, NULL,
    4, 'https://images.unsplash.com/photo-1580910051074-3eb694886505?w=500', 20, 5.0),
('College Backpack', 'Water-resistant, laptop compartment.', 1500.00, 1900.00,
    6, 'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?w=500', 8, 4.5);
 
