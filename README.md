# Biryo Byihuse - USSD & SMS Food Order & Delivery System

A food ordering and delivery system designed for rural areas, utilizing USSD and SMS technologies for accessibility.

## Features

- USSD Menu System for Ordering
- SMS Notifications for Order Status
- Food Menu Management
- Order Tracking
- Delivery Management
- Admin Dashboard

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP/Apache Server
- AfricasTalking API Account (for USSD & SMS)

## Installation

1. Clone this repository to your XAMPP htdocs folder
2. Import the database schema from `database/schema.sql`
3. Configure your database connection in `config/database.php`
4. Set up your AfricasTalking credentials in `config/africastalking.php`
5. Access the system through your local server

## Directory Structure

```
├── config/             # Configuration files
├── database/          # Database schema and migrations
├── includes/          # Common PHP functions and classes
├── ussd/             # USSD menu handlers
├── sms/              # SMS handling logic
├── admin/            # Admin interface
└── api/              # API endpoints
```

## Setup Instructions

1. Create an AfricasTalking account
2. Configure your USSD callback URL
3. Set up your SMS shortcode
4. Configure the database connection
5. Import the database schema

## Security

- All sensitive data is encrypted
- USSD sessions are validated
- SMS messages are verified
- Admin access is protected

## License

MIT License 