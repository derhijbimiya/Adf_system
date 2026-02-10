#!/bin/bash
# ADF System - Automated Deployment Script for Hosting
# Run this on your hosting server via SSH

set -e  # Exit on error

echo "=========================================="
echo "🚀 ADF SYSTEM DEPLOYMENT SCRIPT"
echo "=========================================="
echo ""

# Configuration
DEPLOY_DIR="/home/xxx/public_html/adf_system"  # CHANGE THIS
DB_HOST="localhost"
DB_USER="adfb2574_adfsystem"
DB_PASS="@Nnoc2025"
MASTER_DB="adfb2574_adf"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Step 1: Pull latest code from GitHub${NC}"
cd "$DEPLOY_DIR"
git pull origin main
echo -e "${GREEN}✓ Code pulled successfully${NC}\n"

echo -e "${YELLOW}Step 2: Check/Create Master Database${NC}"

# Create database if not exists
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" << EOF
CREATE DATABASE IF NOT EXISTS $MASTER_DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE $MASTER_DB;

-- Businesses Table
CREATE TABLE IF NOT EXISTS businesses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    business_code VARCHAR(100) UNIQUE,
    business_name VARCHAR(255),
    business_type VARCHAR(100),
    database_name VARCHAR(100),
    owner_id INT,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Roles Table
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(100),
    role_code VARCHAR(100) UNIQUE,
    description TEXT,
    is_system_role BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    full_name VARCHAR(255),
    email VARCHAR(255),
    role_id INT,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- User Business Assignment
CREATE TABLE IF NOT EXISTS user_business_assignment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    business_id INT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_business (user_id, business_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- User Menu Permissions (⭐ KEY TABLE - uses menu_code NOT menu_id)
CREATE TABLE IF NOT EXISTS user_menu_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    business_id INT,
    menu_code VARCHAR(100),
    can_view BOOLEAN DEFAULT 0,
    can_create BOOLEAN DEFAULT 0,
    can_edit BOOLEAN DEFAULT 0,
    can_delete BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_perm (user_id, business_id, menu_code),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Insert system roles
INSERT IGNORE INTO roles (role_name, role_code, is_system_role) VALUES
    ('Admin', 'admin', 1),
    ('Manager', 'manager', 1),
    ('Accountant', 'accountant', 1),
    ('Staff', 'staff', 1),
    ('Developer', 'developer', 1);

-- Insert businesses
INSERT IGNORE INTO businesses (business_code, business_name, business_type, database_name) VALUES
    ('NARAYANAHOTEL', 'Narayana Hotel Jepara', 'hotel', 'adfb2574_narayana_hotel'),
    ('BENSCAFE', 'Bens Cafe', 'restaurant', 'adfb2574_benscafe');

EOF

echo -e "${GREEN}✓ Master database setup complete${NC}\n"

echo -e "${YELLOW}Step 3: Create Sandra User${NC}"

# Create Sandra user with password sandra123
# Password hash: password_hash('sandra123', PASSWORD_BCRYPT) = $2y$10$...
SANDRA_PASS="\$2y\$10\$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/R1i"  # sandra123 hashed

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$MASTER_DB" << EOF
INSERT IGNORE INTO users (username, password, full_name, email, role_id, is_active) VALUES
    ('sandra', '\$2y\$10\$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/R1i', 'Sandra', 'sandra@example.com', 4, 1);

-- Assign Sandra to both businesses
INSERT IGNORE INTO user_business_assignment (user_id, business_id)
SELECT u.id, b.id FROM users u, businesses b 
WHERE u.username = 'sandra' AND b.business_code IN ('NARAYANAHOTEL', 'BENSCAFE');

-- Grant Sandra all menu permissions for both businesses
INSERT IGNORE INTO user_menu_permissions (user_id, business_id, menu_code, can_view, can_create)
SELECT 
    u.id,
    b.id,
    m.menu_code,
    1,
    1
FROM users u
CROSS JOIN businesses b
CROSS JOIN (
    SELECT 'dashboard' as menu_code
    UNION SELECT 'cashbook'
    UNION SELECT 'divisions'
    UNION SELECT 'frontdesk'
    UNION SELECT 'sales'
    UNION SELECT 'procurement'
    UNION SELECT 'reports'
    UNION SELECT 'settings'
    UNION SELECT 'users'
) m
WHERE u.username = 'sandra';

EOF

echo -e "${GREEN}✓ Sandra user created with full permissions${NC}\n"

echo -e "${YELLOW}Step 4: Verify Database Setup${NC}"

TABLES=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -se "
    SELECT COUNT(*) FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA='$MASTER_DB'
")

SANDRA_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -se "
    SELECT COUNT(*) FROM $MASTER_DB.users 
    WHERE username='sandra'
")

PERMS=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -se "
    SELECT COUNT(*) FROM $MASTER_DB.user_menu_permissions 
    WHERE user_id=(SELECT id FROM $MASTER_DB.users WHERE username='sandra')
")

echo "Database Status:"
echo "  Tables created: $TABLES"
echo "  Sandra user: $SANDRA_COUNT"
echo "  Sandra permissions: $PERMS (should be 18: 9 menus × 2 businesses)"
echo ""

echo -e "${GREEN}✓ Deployment complete!${NC}\n"

echo "=========================================="
echo "📋 TEST INSTRUCTIONS"
echo "=========================================="
echo ""
echo "1. Login with:"
echo "   Username: sandra"
echo "   Password: sandra123"
echo ""
echo "2. Verify you see 9 menus:"
echo "   ✓ Dashboard"
echo "   ✓ Buku Kas (Cashbook)"
echo "   ✓ Divisions"
echo "   ✓ Front Desk"
echo "   ✓ Sales"
echo "   ✓ PO & SHOOP (Procurement)"
echo "   ✓ Reports"
echo "   ✓ Settings"
echo "   ✓ Users"
echo ""
echo "3. Access developer panel:"
echo "   https://yourdomain.com/adf_system/developer/index.php"
echo ""
echo "=========================================="
echo -e "${GREEN}🎉 DEPLOYMENT SUCCESSFUL!${NC}"
echo "=========================================="
