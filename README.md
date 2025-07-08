# Magento 2 Non-Composer Registration Plugin

A Composer plugin that dynamically generates the `NonComposerComponentRegistration.php` file during Composer operations, replacing the static file with a dynamically generated one based on glob patterns.

## Installation

```bash
composer require perfcom/magento2-non-composer-registration
```

## Configuration

### Exclude Configuration

For monorepo projects where you need to exclude certain components, create `app/etc/NonComposerComponentRegistrationExclude.php`:

```php
<?php
return [
    'Vendor/ModuleName',
    'AnotherVendor/AnotherModule',
    'path/to/exclude',
    // Add more exclusions as needed
];
```

The exclude file should return an array of paths to exclude. The plugin matches these paths against the last two directory segments of the discovered registration files.

**Example:**
- If a registration file is found at `app/code/Vendor/Module/registration.php`
- The path checked against exclusions would be `Vendor/Module`
- To exclude this module, add `'Vendor/Module'` to the exclude array

This is particularly useful in monorepo setups where:
- Multiple Magento instances share the same codebase
- Certain modules should only be active in specific environments
- You need to selectively disable modules without removing them from the codebase

## How It Works

1. **Trigger**: Plugin runs after `composer install` and `composer update`
2. **Backup**: Creates backup of existing `NonComposerComponentRegistration.php` if it exists
3. **Scan**: Uses glob patterns from `registration_globlist.php` to find registration files
4. **Filter**: Applies exclusions from `NonComposerComponentRegistrationExclude.php` if present
5. **Generate**: Creates new registration file with all discovered components
6. **Output**: Saves the generated file to `app/etc/NonComposerComponentRegistration.php`

## Generated File Structure

The plugin generates a PHP file that looks like this:

```php
<?php

$registrationFiles = array(
    '/path/to/app/code/Vendor/Module/registration.php',
    '/path/to/app/design/frontend/Vendor/Theme/registration.php',
    // ... more registration files
);

foreach ($registrationFiles as $registrationFile) {
    require_once $registrationFile;
}
```


## Commands

The plugin automatically runs during:
- `composer install`
- `composer update`

No manual commands are required.

## Requirements

- PHP 8.0 or higher
- Composer Plugin API ^1.0 or ^2.0
- Magento 2.x

## License

MIT License