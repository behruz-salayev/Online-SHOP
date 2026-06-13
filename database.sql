-- Online Market Database Schema
-- Combined full schema for the marketplace, including seller application and moderation support.
-- This file now contains the complete database structure; separate migration SQL is no longer required.

CREATE DATABASE IF NOT EXISTS online_market CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE online_market;

-- ========== USERS ==========
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','seller','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========== REGIONS ==========
CREATE TABLE regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_uz VARCHAR(100) NOT NULL,
    name_ru VARCHAR(100),
    name_en VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========== DISTRICTS ==========
CREATE TABLE districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT NOT NULL,
    name_uz VARCHAR(100) NOT NULL,
    name_ru VARCHAR(100),
    name_en VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== SELLER REQUESTS ==========
CREATE TABLE seller_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_name VARCHAR(200) NOT NULL,
    business_description TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    region_id INT NOT NULL,
    district_id INT NOT NULL,
    address TEXT NOT NULL,
    logo VARCHAR(255),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE RESTRICT,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ========== SELLERS ==========
CREATE TABLE sellers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_name VARCHAR(200) NOT NULL,
    business_description TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    region_id INT NOT NULL,
    district_id INT NOT NULL,
    address TEXT NOT NULL,
    logo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE RESTRICT,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ========== CATEGORIES ==========
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========== PRODUCTS ==========
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    seller_id INT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    old_price DECIMAL(12,2),
    stock INT NOT NULL DEFAULT 0,
    image VARCHAR(255),
    images TEXT,
    specs TEXT,
    is_featured TINYINT(1) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    approval_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========== WISHLIST ==========
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
) ENGINE=InnoDB;

-- ========== ORDERS ==========
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    region_id INT,
    district_id INT,
    address TEXT NOT NULL,
    latitude DECIMAL(10,7),
    longitude DECIMAL(10,7),
    phone VARCHAR(20) NOT NULL,
    ip_address VARCHAR(45),
    payment_method ENUM('cash','card','transfer') DEFAULT 'cash',
    status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    admin_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========== ORDER ITEMS ==========
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    product_price DECIMAL(12,2) NOT NULL,
    quantity INT NOT NULL,
    seller_id INT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========== ORDER STATUS HISTORY ==========
CREATE TABLE order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    comment TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== SEED DATA =====
INSERT INTO users (full_name, email, phone, password, role) VALUES
('Admin', 'admin@gmail.com', '+998901234567', '$2y$12$6T66il0l6Lxk0Vf2c6yssuhDXYFawAly1I/iujf6IAWtFcPEImcfO', 'admin'),
('Seller', 'seller@gmail.com', '+998901234568', '$2y$12$2e3B3Kna0uTE9XXJgtwAFe19ugdtmd.5vHBSBNtM.m7LgJXwyuWBu', 'seller');

INSERT INTO regions (id, name_uz) VALUES
(1, 'Toshkent shahri'),
(2, 'Toshkent viloyati'),
(3, 'Samarqand viloyati'),
(4, 'Buxoro viloyati'),
(5, 'Farg\'ona viloyati'),
(6, 'Andijon viloyati'),
(7, 'Namangan viloyati'),
(8, 'Qashqadaryo viloyati'),
(9, 'Surxondaryo viloyati'),
(10, 'Navoiy viloyati'),
(11, 'Jizzax viloyati'),
(12, 'Sirdaryo viloyati'),
(13, 'Xorazm viloyati'),
(14, 'Qoraqalpog\'iston Respublikasi');

INSERT INTO districts (region_id, name_uz) VALUES
(1, 'Bektemir tumani'), (1, 'Chilonzor tumani'), (1, 'Hamza tumani'),
(1, 'Mirobod tumani'), (1, 'Mirzo Ulug\'bek tumani'), (1, 'Sergeli tumani'),
(1, 'Shayxontohur tumani'), (1, 'Uchtepa tumani'), (1, 'Yakkasaroy tumani'),
(1, 'Yunusobod tumani'), (1, 'Yashnobod tumani'), (1, 'Olmazor tumani'),
(13, 'Urganch shahri'), (13, 'Bog\'ot tumani'), (13, 'Gurlan tumani'),
(13, 'Qo\'shko\'pir tumani'), (13, 'Xonqa tumani'), (13, 'Xiva tumani'),
(13, 'Shovot tumani'), (13, 'Yangiariq tumani'), (13, 'Yangi bozor tumani'),
(3, 'Samarqand shahri'), (3, 'Bulung\'ur tumani'), (3, 'Ishtixon tumani'),
(3, 'Jomboy tumani'), (3, 'Kattaqo\'rg\'on tumani'), (3, 'Narpay tumani'),
(3, 'Nurobod tumani'), (3, 'Oqdaryo tumani'), (3, 'Payariq tumani');

INSERT INTO categories (id, name, slug, description) VALUES
(1, 'Apple iPhone', 'apple-iphone', 'iPhone smartfonlari - iPhone 15, iPhone 14, iPhone SE va boshqalar'),
(2, 'Samsung Galaxy', 'samsung-galaxy', 'Samsung Galaxy smartfonlari - S24, S23, A seriyasi va boshqalar'),
(3, 'Xiaomi', 'xiaomi', 'Xiaomi smartfonlari - Redmi, POCO, Mi seriyalari'),
(4, 'Realme', 'realme', 'Realme smartfonlari - GT, C, Narzo seriyalari'),
(5, 'OPPO', 'oppo', 'OPPO smartfonlari - Find, Reno, A seriyalari'),
(6, 'Vivo', 'vivo', 'Vivo smartfonlari - X, V, Y seriyalari'),
(7, 'Tecno', 'tecno', 'Tecno smartfonlari - Spark, Camon, Pova seriyalari'),
(8, 'Google Pixel', 'google-pixel', 'Google Pixel smartfonlari');

INSERT INTO sellers (user_id, business_name, business_description, phone, region_id, district_id, address) VALUES
(2, 'Phone Store', 'Professional phone retailer selling quality smartphones and accessories', '+998901234568', 1, 1, 'Toshkent shahri, Bektemir tumani');

INSERT INTO products (category_id, seller_id, name, slug, description, price, old_price, stock, image, specs, is_featured, approval_status) VALUES
(1, 2, 'iPhone 15 Pro Max 256GB', 'iphone-15-pro-max-256', 'Apple\'ning eng so\'nggi flagmani. A17 Pro chip, titan korpus, 48MP pro kamera tizimi. Akkumulyator 29 soatgacha video ko\'rish.', 14999000, 16999000, 15, 'placeholder.svg', '{"Rangi":"Natural Titanium","RAM":"8 GB","Xotira":"256 GB","Ekran":"6.7\" Super Retina XDR","Protsessor":"A17 Pro","Kamera":"48+12+12 MP","Old kamera":"12 MP","Batareya":"4422 mAh","OS":"iOS 17"}', 1, 'approved');


