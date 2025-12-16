# Digital Marketplace - Laravel Application

A production-ready digital marketplace platform built with Laravel 12, featuring full Playerok-level functionality.

## Features

### Core Functionality
- **Authentication & Users**: Registration, login, roles (buyer, seller, admin), profiles, ratings, reputation, account restrictions
- **Marketplace**: Categories, products, search, filters, sorting, product moderation
- **Orders & Escrow**: Escrow-based order system, status state machine, delivery confirmation, auto/manual fund release, refunds
- **Wallet & Finance**: Internal wallet, balance top-up/withdraw, platform commission, transaction ledger, admin payout approval
- **Messaging**: Buyer-seller chat, order-based chats, file attachments
- **Disputes**: Dispute opening, evidence uploads, admin arbitration, decision enforcement, audit trail
- **Reviews & Ratings**: Product and seller reviews with rating system

### Admin Panel
- **RBAC**: Role-based access control
- **User Management**: Ban, freeze, user administration
- **Order Management**: View and manage all orders
- **Dispute Management**: Resolve disputes with full arbitration tools
- **Finance Dashboard**: Transaction management, withdrawal approvals
- **Product Moderation**: Approve, reject, suspend products
- **Settings**: System configuration

### Localization
- **Russian (ru)**: Full translation support
- **Uzbek (uz, Latin)**: Full translation support
- Language switcher
- Multilingual admin panel
- Localized emails and notifications

### Security
- CSRF protection
- XSS protection
- Rate limiting
- Input validation
- Secure file uploads
- Financial action logs
- Activity logging

### Performance
- Database indexing
- Query optimization
- Caching support
- Pagination everywhere

## Technology Stack

- **Backend**: Laravel 12 (LTS)
- **Language**: PHP 8.2+
- **Database**: MySQL/PostgreSQL
- **Auth**: Laravel Auth
- **Queue**: Laravel Queue (Redis preferred)
- **Cache**: Redis
- **Storage**: Laravel Storage (S3-ready)

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Copy environment file:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure your `.env` file with database credentials and other settings

5. Run migrations:
   ```bash
   php artisan migrate
   ```

6. Seed the database:
   ```bash
   php artisan db:seed
   ```

7. Create storage link:
   ```bash
   php artisan storage:link
   ```

8. Build assets:
   ```bash
   npm run build
   ```

## Default Admin Credentials

After seeding:
- Email: `admin@example.com`
- Password: `password`

**⚠️ Change these credentials immediately in production!**

## Scheduled Tasks

Add to your cron (for auto-completing orders):
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Or manually run:
```bash
php artisan orders:auto-complete
```

## Development

Run the development server:
```bash
php artisan serve
```

Or use the dev script:
```bash
composer run dev
```

## Project Structure

```
app/
├── Console/Commands/     # Artisan commands
├── Http/
│   ├── Controllers/      # Application controllers
│   │   ├── Admin/        # Admin panel controllers
│   │   └── Auth/         # Authentication controllers
│   └── Middleware/       # Custom middleware
├── Models/               # Eloquent models
└── Services/            # Business logic services

database/
├── migrations/          # Database migrations
└── seeders/            # Database seeders

resources/
├── lang/               # Localization files
│   ├── ru/            # Russian translations
│   └── uz/            # Uzbek translations
└── views/             # Blade templates
```

## Key Services

- **WalletService**: Handles wallet operations, deposits, withdrawals
- **EscrowService**: Manages escrow holds, releases, refunds
- **OrderService**: Order creation, payment, delivery, completion
- **DisputeService**: Dispute management and resolution
- **ProductService**: Product creation and moderation
- **ActivityLogService**: Activity logging

## Database Schema

The application includes comprehensive migrations for:
- Users (extended with roles, ratings, reputation)
- Categories (with translations)
- Products (with translations, images, attributes)
- Orders & Escrow
- Wallets & Transactions
- Messages & Conversations
- Disputes & Evidence
- Reviews
- Activity Logs
- Settings

## Security Considerations

- All financial operations use database transactions
- Input validation on all user inputs
- File upload restrictions and validation
- CSRF protection enabled
- Rate limiting configured
- Activity logging for sensitive operations
- Account restrictions (ban, freeze) implemented

## Localization

The application supports Russian and Uzbek (Latin) languages. Language can be switched via:
- URL parameter: `?lang=ru` or `?lang=uz`
- Session storage
- Accept-Language header

## License

This is a commercial application. All rights reserved.

## Support

For issues and questions, please contact the development team.
