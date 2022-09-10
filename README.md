# Crashub client for your Laravel project

Use this library to start reporting exceptions to Crashub.

## Install
Install the crashub-laravel package via composer:
```bash
composer require crashub-com/crashub-laravel
```
Add Crashub reporting to `app/Exceptions/Handler.php` (Laravel version 8/9):
```php
public function register()
{
    $this->reportable(function (\Throwable $e) {
        if (app()->bound('crashub')) {
            app('crashub')->report($e);
        }
    });
}
```
Run the crashub:install artisan command:
```bash
php artisan crashub:install <project key>
```
Your application should now report uncaught errors to Crashub.

## Add context to errors
You can add custom context to the errors in the form of key/value pairs.

To add global context, use the `$crashub->context()` method:
```php
$crashub->context('key', $value);
```
You can add multiple items:
```php
$crashub->context([
    'key1' => $value1,
    'key2' => $value2,
]);
```
To add context to a particular error notification, pass an associative array to `$crashub->report()`:
```php
$crashub->report($e, ['key' => $value]);
```
