# Online-SHOP

## Project Overview

Online-SHOP is a multi-vendor e-commerce marketplace platform built with PHP and MySQL. It allows sellers to publish products, customers to browse and purchase items, and administrators to manage the platform.

## Key Features

### For Customers
- Browse products by categories
- Add items to shopping cart and wishlist
- Place orders with multiple payment methods (cash, card, transfer)
- Track order status
- User profile management

### For Sellers
- Become a seller through registration and approval process
- Add and manage products
- Upload product images and specifications
- View sales orders
- Track business performance
- Create advertisements for products

### For Administrators
- Manage all users (customers, sellers, admins)
- Approve seller applications
- Approve/reject seller products before publishing
- Manage product categories
- View and manage all orders
- Monitor platform advertisements

## Technical Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML, CSS, JavaScript
- **Architecture**: MVC (Model-View-Controller)

## Project Structure

```
├── app/                      # Application logic
│   ├── core/
│   │   ├── Database.php      # Database connection & queries
│   │   └── helpers.php       # Helper functions
│   └── models/               # Data models
│       ├── User.php
│       ├── Product.php
│       ├── Order.php
│       ├── Seller.php
│       ├── Category.php
│       └── [other models]
├── config/
│   └── config.php            # Database configuration
├── includes/                 # Header/footer templates
├── links/
│   └── images/              # Uploaded images
├── admin/                   # Admin panel
├── seller/                  # Seller dashboard
├── users/                   # User authentication & profile
├── index.php                # Homepage
├── product.php              # Product detail page
├── cart.php                 # Shopping cart
├── checkout.php             # Checkout process
└── database.sql             # Database schema
```

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- XAMPP or similar local development environment

### Setup Steps

1. **Clone or extract the project** to your web root:
   ```bash
   cd /opt/lampp/htdocs/
   # Extract or clone the project here
   ```

2. **Create the database**:
   ```bash
   mysql -u root -p < database.sql
   ```

3. **Configure database connection** in `config/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'online_market');
   ```

4. **Create uploads directory**:
   ```bash
   mkdir -p links/images
   chmod 755 links/images
   ```

5. **Access the application**:
   - Homepage: `http://localhost/online`
   - Admin Panel: `http://localhost/online/admin`
   - Seller Dashboard: `http://localhost/online/seller`

## Default Credentials

### 🔐 Admin Account
| Field | Value |
|-------|-------|
| **Email** | `admin@gmail.com` |
| **Password** | `admin2026` |
| **Dashboard** | http://localhost/online/admin |

### 👨‍💼 Seller Account
| Field | Value |
|-------|-------|
| **Email** | `seller@gmail.com` |
| **Password** | `seller2026` |
| **Dashboard** | http://localhost/online/seller |

> ⚠️ **IMPORTANT**: Change these credentials in production! Use strong, unique passwords.

## Database Schema

### Main Tables

- **users**: Stores all users (customers, sellers, admins)
- **products**: Product listings with seller info and approval status
- **categories**: Product categories
- **orders**: Customer orders
- **order_items**: Individual items in orders
- **wishlist**: User wishlist items
- **sellers**: Seller business profiles
- **seller_requests**: Pending seller applications
- **regions**: Geographic regions
- **districts**: Geographic districts
- **advertisements**: Product advertisements/promotions

## User Roles

### Customer (user)
- Browse products and categories
- Add items to cart and wishlist
- Place orders
- View order history and status

### Seller
- Manage own products
- Create advertisements
- Receive orders from customers
- View business statistics

### Admin
- Manage all users
- Approve seller applications
- Approve product listings
- Manage categories
- Monitor platform activity

## Features in Detail

### Product Management
- Products require admin approval before being published
- Sellers can upload multiple images and detailed specifications
- Products can be marked as featured on the homepage
- Stock management and pricing options (current and discounted)

### Order System
- Orders go through status progression: pending → confirmed → processing → shipped → delivered
- Customers can pay via cash, card, or transfer
- Order items track seller information
- Order status history is maintained

### Seller Application Process
1. User applies to become a seller with business details
2. Admin reviews the application
3. Upon approval, seller profile is created
4. Seller can then add products for sale

### Advertisement System
- Sellers can purchase ads for 1, 3, or 7 days
- Ads have pending, active, and expired states
- Featured products appear in carousel

## API Endpoints (AJAX)

- `cart_ajax.php`: Add/remove items from cart
- `wishlist_ajax.php`: Add/remove items from wishlist
- `get_districts.php`: Get districts by region

## File Uploads

Products and seller profiles support image uploads. Images are stored in `links/images/` directory with size validation.

## Security Features

- Passwords are hashed using bcrypt (PASSWORD_BCRYPT)
- Session-based authentication
- Input validation and sanitization
- Database prepared statements to prevent SQL injection

## Future Enhancements

- Payment gateway integration
- Email notifications
- Product reviews and ratings
- Search and filtering improvements
- Mobile app
- Analytics dashboard
- Multi-language support

## Support

For issues or questions, contact the development team or refer to the admin panel documentation.

## License

This project is proprietary and confidential.
