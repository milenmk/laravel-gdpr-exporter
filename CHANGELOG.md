## v1.4.0

#### Published: 2025-05-24

- [IMPROVEMENT] Add support for Laravel 13.x

## v1.3.2

#### Published: 2025-08-29

- Enhance README with responsive badge links
- Adds a list of other Laravel packages to the README
- Improved DISCLAIMER in README
- Removes the explicit version from composer.json to allow for dynamic versioning

## v1.3.1

#### Published at: 2025-08-09

- **[FIX]** Fixed "Table 'notifications' doesn't exist" error when using Notifiable trait without notifications table
- Added `notifications` to default excluded methods list to prevent database errors
- Implemented safe relation loading with graceful error handling for missing tables
- Enhanced documentation with troubleshooting section for common notification table issues
- Improved robustness when loading relations that may not have corresponding database tables

## v1.3.0

#### Published at: 2025-08-09

- Added configuration file with customizable options
- Added support for different relation detection methods (reflection or whitelist)
- Added ability to publish configuration file
- Enhanced UserDataExporterService with more robust functionality
- Improved security with excluded methods list for reflection-based relation detection

## v1.2.1

#### Published at: 2025-07-31

- Add CHANGELOG.md to the package
- Update README.md

## v1.2.0

#### Published at: 2025-07-31

- Add code style rules and dev tools

## v1.1.0

#### Published at: 2025-07-31

- [FIX] Replace `mount` with `construct` in the package Livewire component

## v1.0.0

#### Published at: 2025-07-31

- Initial release
