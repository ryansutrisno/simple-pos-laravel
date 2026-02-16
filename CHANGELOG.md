# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-02-16

### Added

#### Customer Management
- Customer model with profile data (name, phone, email, address)
- Customer points and statistics tracking
- CustomerResource with CRUD operations in Filament
- Points history tracking per customer
- Transaction history per customer
- CustomerFactory for testing

#### Loyalty Points System
- PointService for point calculations
- Earn points: Rp 10.000 = 1 point
- Redeem points: 1 point = Rp 1.000
- Minimum redeem: 10 points
- Maximum redeem: 50% of transaction
- Point redemption in POS checkout
- Automatic point earning after transaction

#### POS Enhancements
- Customer selection during checkout
- Customer search by name/phone
- Point redemption option with validation
- Display available points
- Display points to be earned

### Changed
- Transaction model: added customer_id, points_earned, points_redeemed, discount_from_points columns
- POS component: integrated customer selection and points functionality
- TransactionResource: added customer column and filter
- ShieldSeeder: added Customer permissions for all roles
- DatabaseSeeder: added 5 sample customers

### Database Tables
- `customers` - Customer profiles with points and stats
- `customer_points` - Points transaction history (earn/redeem/adjust)

### Filament Resources
- CustomerResource - Customer management with view pages

### Services
- `PointService` - Loyalty point calculations and management

### Tests
- CustomerTest with 21 test cases covering CRUD, points, and validations

## [1.0.0] - 2026-02-14

### Added

#### Core Setup
- Fresh Laravel application setup with PHP 8.2
- Pest testing framework integration
- Filament v3 admin panel integration

#### Product Management
- Category model with CRUD operations
- Product model with stock tracking
- Low stock threshold alerts
- Product barcode support
- Product activation/deactivation

#### Point of Sale (POS) System
- Livewire-based POS interface
- Shopping cart functionality
- Real-time stock validation
- Multiple payment method support (cash, qris, transfer)
- Transaction processing with automatic stock deduction
- Profit calculation per transaction item

#### Receipt Printing
- Bluetooth thermal printer integration
- Customizable receipt templates
- ESC/POS encoding support
- Receipt preview functionality
- Per-store template configuration

#### Inventory Management
- Supplier management system
- Purchase Order (PO) workflow
- Purchase Order Item tracking
- Supplier debt management
- Debt payment tracking
- Stock adjustment with approval workflow
- Stock Opname (Stock Take) functionality
- Stock history tracking

#### Financial Records
- Automatic financial record creation on checkout
- Profit tracking per transaction
- Financial dashboard charts

#### Reporting System
- Sales Report with date filtering
- Purchase Report with supplier filtering
- Profit & Loss Report
- Stock Card Report
- Debt Report
- End of Day (Cashier Closing) Report
- PDF export for all reports
- Excel export for all reports

#### Dashboard Widgets
- Stats Overview (Categories, Products, Stock)
- Sales Chart Widget
- Profit Chart Widget
- Transactions Chart Widget
- Financial Records Chart
- Top Products Widget
- Payment Method Chart Widget
- Low Stock Alert Widget

#### Authentication & Authorization
- Filament Shield integration for role-based access control
- Spatie Laravel Permission integration
- Pre-defined roles:
  - `super_admin` - Full system access
  - `admin` - Administrative access
  - `manager` - Operational management access
  - `kasir` - Cashier access (POS + transactions)
  - `panel_user` - Basic panel access
- Permission-based access for all Resources, Pages, and Widgets
- User management with role assignment
- Policies for all models

### Technical Details

#### Database Tables
- `users` - User accounts
- `categories` - Product categories
- `products` - Product inventory
- `transactions` - Sales transactions
- `transaction_items` - Transaction line items
- `financial_records` - Financial tracking
- `stores` - Store configuration
- `receipt_templates` - Receipt template storage
- `suppliers` - Supplier information
- `purchase_orders` - Purchase order headers
- `purchase_order_items` - Purchase order line items
- `supplier_debts` - Supplier debt tracking
- `debt_payments` - Debt payment records
- `stock_adjustments` - Stock adjustment headers
- `stock_adjustment_items` - Stock adjustment items
- `stock_opnames` - Stock take headers
- `stock_opname_items` - Stock take items
- `stock_histories` - Stock movement history
- `end_of_days` - Cashier closing records
- `roles` - User roles (Shield)
- `permissions` - User permissions (Shield)
- `role_has_permissions` - Role-permission mapping
- `model_has_roles` - Model-role mapping
- `model_has_permissions` - Model-permission mapping

#### Filament Resources
- UserResource - User management with role assignment
- CategoryResource - Category CRUD
- ProductResource - Product management
- TransactionResource - Transaction viewing
- SupplierResource - Supplier management
- PurchaseOrderResource - Purchase order management
- SupplierDebtResource - Supplier debt tracking
- DebtPaymentResource - Debt payment records
- StockAdjustmentResource - Stock adjustment management
- StockOpnameResource - Stock take management
- FinancialRecordResource - Financial records viewing
- ReceiptTemplateResource - Receipt template management
- StoreResource - Store configuration
- RoleResource (Shield) - Role and permission management

#### Filament Pages
- Dashboard
- POS (Point of Sale)
- Sales Report
- Purchase Report
- Profit & Loss Report
- Stock Card Report
- Debt Report
- End of Day Report

#### Services
- `ReportService` - Report data generation
- `StockService` - Stock operations
- `ReceiptTemplateService` - Template management
- `ReceiptRenderer` - ESC/POS receipt rendering

#### Tests
- Comprehensive test suite using Pest
- Feature tests for all major functionality
- Shield permission tests (11 test cases)

### Changed
- Indonesian localization for Filament Shield
- Custom navigation labels ("Manajemen Role", "Role")

### Dependencies
- Laravel Framework v12
- Filament v3.3
- Livewire v3
- Spatie Laravel Permission v6
- Filament Shield v3
- Maatwebsite Excel
- Barryvdh DomPDF
- Flowframe Laravel Trend
- Blade UI Kit Heroicons

---

## Release Summary

| Version | Date | Description |
|---------|------|-------------|
| 1.1.0 | 2026-02-16 | Customer management with loyalty points system |
| 1.0.0 | 2026-02-14 | Initial release with complete POS system |

### Feature Breakdown

```
├── Core System
│   ├── Product Management
│   ├── Category Management
│   └── Store Configuration
├── Point of Sale
│   ├── Cart System
│   ├── Transaction Processing
│   ├── Receipt Printing
│   └── Customer Selection & Points
├── Inventory
│   ├── Supplier Management
│   ├── Purchase Orders
│   ├── Supplier Debts
│   ├── Stock Adjustments
│   └── Stock Opname
├── Customer
│   ├── Customer Database
│   ├── Loyalty Points
│   └── Purchase History
├── Reports
│   ├── Sales Report
│   ├── Purchase Report
│   ├── Profit & Loss
│   ├── Stock Card
│   ├── Debt Report
│   └── End of Day
├── Dashboard
│   ├── Stats Overview
│   ├── Sales Chart
│   ├── Profit Chart
│   └── Alert Widgets
└── Access Control
    ├── Role Management
    ├── Permission Management
    └── User Management
```