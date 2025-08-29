# Laravel GDPR Exporter

<div align="center">

<a href="https://packagist.org/packages/milenmk/laravel-gdpr-exporter">![Latest Version on Packagist](https://img.shields.io/packagist/v/milenmk/laravel-gdpr-exporter.svg?style=flat)</a>
<a href="https://packagist.org/packages/milenmk/laravel-gdpr-exporter">![Total Downloads](https://img.shields.io/packagist/dt/milenmk/laravel-gdpr-exporter.svg?style=flat)</a>
<a href="https://github.com/milenmk/laravel-gdpr-exporter">![GitHub User's stars](https://img.shields.io/github/stars/milenmk/laravel-gdpr-exporter)</a>
<a href="https://laravel.com/docs">![Laravel 10 Support](https://img.shields.io/badge/Laravel-10.x|11.x|12.x-orange?style=flat&logo=laravel)</a>
<a href="https://www.php.net">![PHP Version Support](https://img.shields.io/packagist/php-v/milenmk/laravel-gdpr-exporter?style=flat)</a>
<a href="https://github.com/milenmk/laravel-gdpr-exporter/blob/develop/LICENSE.md">![License](https://img.shields.io/packagist/l/milenmk/laravel-gdpr-exporter.svg?style=flat)</a>
<a href="https://github.com/milenmk/laravel-gdpr-exporter/issues">![Contributions Welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat)</a>
<a href="https://www.patreon.com/c/LaravelAddonsbyMilen">![Sponsor me](https://img.shields.io/badge/Sponsor-%E2%9D%A4-ff69b4?style=flat)</a>

</div>

A lightweight Livewire component for exporting user data in multiple GDPR-compliant formats: JSON, CSV, XML, and HTML.

---

## ✨ Features

- Export authenticated user data in:
  - ✅ JSON
  - ✅ CSV
  - ✅ XML
  - ✅ HTML
- Automatically loads all Eloquent relations
- Filter out ID columns and other columns ending in `_id` or `_by`
- Streamed downloads
- Beautifully formatted output
- Built with Livewire 3
- Configurable relation detection (reflection or whitelist)
- Customizable export settings

---

## 🧰 Requirements

- PHP 8.2+
- Laravel 10+
- Livewire 3+

---

## 📦 Installation

```copy
composer require milenmk/laravel-gdpr-exporter
```

## 🚀 Usage

1. Add the Livewire component in your Blade view:

   ```copy
   <livewire:gdpr-exporter />
   ```

2. Optional: Publish the configuration file:

   ```copy
   php artisan vendor:publish --tag=laravel-gdpr-exporter-config
   ```

3. Optional: Publish the Blade view if you want to customize the UI:

   ```copy
   php artisan vendor:publish --tag=laravel-gdpr-exporter-views
   ```

   This will publish the file in `resources/views/vendor/laravel-gdpr-exporter/livewire/gdpr.blade.php`

## 📁 Export Formats

| Format | Output                   | Content-Type     |
| ------ | ------------------------ | ---------------- |
| JSON   | Pretty-printed user data | application/json |
| CSV    | Key-value flat list      | text/csv         |
| XML    | Nested XML document      | application/xml  |
| HTML   | Styled HTML table        | text/html        |

## ⚙️ Configuration

The package provides several configuration options in config/gdpr-exporter.php:

### User Model

Specify the user model class to use for GDPR exports:

<pre><code>'user_model' => env('GDPR_USER_MODEL', 'App\Models\User'),
</code></pre>

### Relations Detection

Choose between automatic reflection-based detection or explicit whitelist:

<pre><code>'relations_detection' => [
    'method' => env('GDPR_RELATIONS_METHOD', 'whitelist'), // 'reflection' or 'whitelist'
    
    // When using 'whitelist' method, only these relations will be loaded
    'whitelist' => [
        // Example: 'posts', 'profile', 'roles', 'permissions'
    ],
    
    // When using 'reflection' method, these methods will be excluded
    'excluded_methods' => [
        'delete', 'destroy', 'forceDelete', 'restore', 'save',
        // ... more methods listed in the config file
    ],
],
</code></pre>

### Export Settings

Configure how data is processed during export:

<pre><code>'export' => [
    // Whether to remove ID fields from the exported data
    'remove_ids' => env('GDPR_REMOVE_IDS', true),
    
    // Whether to flatten pivot table data in the export
    'flatten_pivot' => env('GDPR_FLATTEN_PIVOT', true),
],
</code></pre>

## 🧠 How It Works

- Uses reflection to detect all Eloquent relationships on the User model.
- Loads relations and transforms the entire user structure to an array.
- Removes internal ID fields and flattens pivot data.
- Outputs the cleaned data in the selected format.

## 🔧 Troubleshooting

### "Table 'notifications' doesn't exist" Error

If you encounter an error like `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'notifications' doesn't exist`, this happens when your User model uses the `Notifiable` trait but you don't have the notifications database table (common when using only email notifications).

**Solutions:**

1. **Use Whitelist Method (Recommended)**: Configure the package to use the whitelist method and only include the relations you want to export:

   ```php
   // config/gdpr-exporter.php
   'relations_detection' => [
       'method' => 'whitelist',
       'whitelist' => [
           'posts', 'profile', 'roles', // Add your actual relations here
           // Don't include 'notifications' if you don't have the table
       ],
   ],
   ```

2. **Exclude Notifications from Reflection**: If you prefer using reflection, add 'notifications' to the excluded methods:

   ```php
   // config/gdpr-exporter.php
   'relations_detection' => [
       'method' => 'reflection',
       'excluded_methods' => [
           // ... other excluded methods
           'notifications', // Add this line
       ],
   ],
   ```

3. **Create the Notifications Table**: If you want to support database notifications in the future:

   ```bash
   php artisan notifications:table
   php artisan migrate
   ```

The package now includes built-in error handling that will gracefully skip relations that cause database errors, but using the whitelist method is still the safest approach.

## Contributing

You can review the source code, report bugs, or contribute to the project by visiting the GitHub repository:

[GitHub Repository](https://github.com/milenmk/laravel-gdpr-exporter)

Feel free to open issues or submit pull requests. Contributions are welcome!

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Support My Work

If this package saves you time, you can support ongoing development:  
👉 [Become a Patron](https://www.patreon.com/c/LaravelAddonsbyMilen)

## Other Packages

Check out my other Laravel packages:

- **[Laravel GDPR Cookie Manager](https://packagist.org/packages/milenmk/laravel-gdpr-cookie-manager)** - GDPR-compliant
  cookie consent management with user preference tracking
- **[Laravel Blacklist](https://packagist.org/packages/milenmk/laravel-blacklist)** - A Laravel package for blacklist
  validation of user input
- **[Laravel Email Change Confirmation](https://packagist.org/packages/milenmk/laravel-email-change-confirmation)** -
  Secure email change confirmation system
- **[Laravel Locations](https://packagist.org/packages/milenmk/laravel-locations)** - Add Countries, Cities, Areas,
  Languages and Currencies models to your Laravel application
- **[Laravel Rate Limiting](https://packagist.org/packages/milenmk/laravel-rate-limiting)** - Advanced rate limiting
  capabilities with exponential backoff
- **[Laravel Datatables and Forms](https://packagist.org/packages/milenmk/laravel-simple-datatables-and-forms)** - Easy
  to use package to create datatables and forms for Livewire components

## License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

## Disclaimer

This package is provided "as is", without warranty of any kind, express or implied, including but not limited to
warranties of merchantability, fitness for a particular purpose, or noninfringement.

The author(s) make no guarantees regarding the accuracy, reliability, or completeness of the code, and shall not be held
liable for any damages or losses arising from its use.

Please ensure you thoroughly test this package in your environment before deploying it to production.
