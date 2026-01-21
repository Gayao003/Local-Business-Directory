# 🏢 Local Business Directory & Booking Platform

A complete WordPress plugin and theme for managing local business listings with integrated booking system, payments, reviews, and AI chatbot support.

[![WordPress](https://img.shields.io/badge/WordPress-6.9-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0-green.svg)](LICENSE)

## 🌟 Features

### Core Functionality
- **Business Listings** - Custom post type with categories and detailed information
- **Advanced Booking System** - Date/time picker with availability management
- **Stripe Payment Integration** - Secure payment processing for bookings
- **Review & Rating System** - 5-star ratings with verified customer reviews
- **AI Chatbot Widget** - Customer support chatbot for businesses
- **Shortcode System** - Embed booking forms and business lists anywhere

### Customer Features
- **Customer Dashboard** - Manage bookings, reviews, and account settings
- **Advanced Search & Filters** - Find businesses by category, location, and keywords
- **Responsive Design** - Mobile-friendly interface for all devices
- **HTML Email Notifications** - Professional branded confirmation emails

### Admin Features
- **Enhanced Dashboard** - Real-time statistics with Chart.js visualizations
- **Booking Calendar** - FullCalendar integration with drag-and-drop management
- **Booking Management** - Complete CRUD operations for all bookings
- **Business Management** - Full control over business listings
- **Settings Panel** - Configure booking durations, buffer times, and Stripe keys

## 📋 Requirements

- **WordPress:** 6.0 or higher
- **PHP:** 8.0 or higher
- **MySQL:** 5.7 or higher
- **Server:** Apache/Nginx with mod_rewrite enabled

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/Gayao003/Local-Business-Directory.git
cd Local-Business-Directory
```

### 2. Set Up WordPress

```bash
# Navigate to wordpress directory
cd wordpress

# Copy wp-config-sample.php to wp-config.php
cp wp-config-sample.php wp-config.php
```

Edit `wp-config.php` with your database credentials:

```php
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASSWORD', 'your_database_password');
define('DB_HOST', 'localhost');
```

### 3. Install WordPress

Visit `http://your-site.com/wp-admin/install.php` and complete the installation wizard.

### 4. Activate Plugin & Theme

1. Navigate to **Plugins** → Activate **Business Directory Booking**
2. Navigate to **Appearance** → **Themes** → Activate **Business Directory Theme**

The plugin will automatically create required database tables on activation.

## 📊 Database Schema

The plugin creates 3 custom tables:

### wp_bookings
Stores all booking information including customer details, dates, times, and payment status.

### wp_availability
Manages business availability schedules by day of week.

### wp_bdb_settings
Stores plugin configuration settings (Stripe keys, booking durations, etc.).

## 🎯 Usage

### Creating a Business Listing

1. Go to **Business Directory** → **Add New**
2. Fill in business details (name, description, contact info)
3. Set featured image and gallery images
4. Assign categories
5. Configure availability schedule
6. Publish

### Managing Bookings

**Admin Dashboard:**
- View all bookings at **Business Directory** → **Bookings**
- Use the **Calendar** view for visual management
- Update booking statuses: Pending → Confirmed → Completed
- View real-time statistics on the main dashboard

**Customer Dashboard:**
- Create a page with template "Dashboard Template"
- Customers can view their bookings and reviews
- Leave reviews after completed bookings

### Shortcodes

Embed booking forms and business lists anywhere:

```php
// Booking form for specific business
[bdb_booking_form business_id="123"]

// Business list with filters
[bdb_business_list category="beauty" limit="6" columns="3"]

// Search form
[bdb_search]
```

### Email Configuration

The plugin sends HTML emails for:
- **Booking confirmations** - Sent immediately after booking
- **Status updates** - When admin changes booking status
- **Review reminders** - After booking completion (optional)

Emails are automatically sent via WordPress `wp_mail()` function.

### Stripe Payment Setup

1. Go to **Business Directory** → **Settings**
2. Enter your Stripe API keys:
   - **Publishable Key** - For frontend payment forms
   - **Secret Key** - For backend payment processing
3. Enable "Require Payment" to make payments mandatory
4. Save settings

Get your Stripe keys from: https://dashboard.stripe.com/apikeys

## 🎨 Theme Templates

The theme includes custom templates:

- `index.php` - Homepage with hero section and featured businesses
- `archive-business_listing.php` - Business directory with search/filters
- `single-business_listing.php` - Individual business page with booking form
- `page-dashboard.php` - Customer dashboard template
- And more...

## 🔧 Configuration

### Plugin Settings

Access at **Business Directory** → **Settings**:

- **Booking Duration** - Default appointment length (minutes)
- **Buffer Time** - Gap between bookings (minutes)
- **Stripe Publishable Key** - Frontend payment key
- **Stripe Secret Key** - Backend payment key
- **Require Payment** - Make payments mandatory
- **Enable Chatbot** - Toggle AI chatbot widget

### Business Availability

Set availability when editing a business listing:
- Configure hours for each day of the week
- Mark days as available/unavailable
- Set opening and closing times

## 🛠️ Development

### Plugin Structure

```
business-directory-booking/
├── admin/                    # Admin interface
│   ├── class-admin-pages.php
│   ├── partials/
│   │   ├── dashboard-page.php
│   │   ├── bookings-page.php
│   │   ├── calendar-page.php
│   │   └── settings-page.php
│   ├── css/
│   └── js/
├── includes/                 # Core functionality
│   ├── class-cpt.php        # Custom post types
│   ├── class-database.php   # Database operations
│   ├── class-ajax.php       # AJAX handlers
│   ├── class-stripe.php     # Payment processing
│   ├── class-shortcodes.php # Shortcode system
│   └── class-email-templates.php # Email templates
└── public/                   # Frontend templates
    ├── booking-form.php
    ├── review-form.php
    └── chatbot-widget.php
```

### Theme Structure

```
business-directory-theme/
├── index.php                # Homepage
├── archive-business_listing.php  # Business archive
├── single-business_listing.php   # Business detail
├── page-dashboard.php       # Customer dashboard
├── header.php
├── footer.php
├── functions.php
└── assets/
    ├── css/
    └── js/
```

## 🔐 Security

- ✅ All AJAX requests secured with WordPress nonces
- ✅ Input sanitization and validation
- ✅ SQL queries use prepared statements
- ✅ Capability checks for admin functions
- ✅ XSS protection with proper escaping
- ✅ CSRF protection on all forms

## 📱 Screenshots

*(Add screenshots here once you upload them to GitHub)*

1. Homepage with hero section
2. Business directory with filters
3. Booking form with date picker
4. Admin dashboard with statistics
5. Booking calendar view
6. Customer dashboard

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the GPL-2.0 License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Gayao**
- GitHub: [@Gayao003](https://github.com/Gayao003)
- Portfolio: [Gayao Portfolio](https://github.com/Gayao003/Gayao_Portfolio)

## 🙏 Acknowledgments

- WordPress Core Team
- Stripe Payment Gateway
- FullCalendar Library
- Chart.js Library
- Bootstrap Framework

## 📞 Support

For issues, questions, or suggestions:
- Create an [Issue](https://github.com/Gayao003/Local-Business-Directory/issues)
- Contact via [GitHub](https://github.com/Gayao003)

---

**Built with ❤️ for local businesses**
