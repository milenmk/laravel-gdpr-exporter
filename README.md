# Laravel GDPR Exporter

[![Latest Version on Packagist](https://img.shields.io/packagist/v/milenmk/laravel-gdpr-exporter.svg?style=flat-square)](https://packagist.org/packages/milenmk/laravel-gdpr-exporter)
[![License](https://img.shields.io/packagist/l/milenmk/laravel-gdpr-exporter.svg?style=flat-square)](https://github.com/milenmk/laravel-gdpr-exporter/blob/main/LICENSE)

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

2. Optional: Publish the Blade view if you want to customize the UI:

   ```copy
   php artisan vendor:publish --tag=laravel-gdpr-exporter-views
   ```

   This will publish the file in `resources/views/vendor/laravel-gdpr-exporter/livewire/gdpr.blade.php`

📁 Export Formats

| Format | Output                   | Content-Type     |
| ------ | ------------------------ | ---------------- |
| JSON   | Pretty-printed user data | application/json |
| CSV    | Key-value flat list      | text/csv         |
| XML    | Nested XML document      | application/xml  |
| HTML   | Styled HTML table        | text/html        |

## 🧠 How It Works

- Uses reflection to detect all Eloquent relationships on the User model.
- Loads relations and transforms the entire user structure to an array.
- Removes internal ID fields and flattens pivot data.
- Outputs the cleaned data in the selected format.

## DISCLAIMER

This package is provided "as is" without warranty of any kind, either express or implied, including but not limited to the warranties of merchantability, fitness for a particular
purpose, or noninfringement.

The author(s) makes no representations or warranties regarding the accuracy, reliability or completeness of the code or its suitability for any specific use case. It is recommended
that you thoroughly test this package in your environment before deploying it to production.

By using this package, you acknowledge and agree that the author(s) shall not be held liable for any damages, losses or other issues arising from the use of this software.

## Contributing

You can review the source code, report bugs, or contribute to the project by visiting the GitHub repository:

[GitHub Repository](https://github.com/milenmk/laravel-gdpr-exporter)

Feel free to open issues or submit pull requests. Contributions are welcome!

## License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.
