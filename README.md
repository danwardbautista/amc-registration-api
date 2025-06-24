# Laravel Version 12

Secured backend API registration

## Live API
https://example.com/

## API Documentation
https://example.com/

## Prerequisite
1. Composer
2. Local MySQL XAMPP

## Running Development
### Package installation
```shell
composer install
```
### Local Setup
1. Run MySQL with XAMPP.
2. Create a database called `amc-registration-user`.
3. Duplicate `env.example` and change the name to `.env`.
4. Configure `.env` database section to your local MySQL configuration.

### Local
Migrate the database
```shell
php artisan migrate
```
Create the first user, you need to setup `INITIAL_PASSWORD` and `INITIAL_EMAIL` at `.env` which will be your initial login.
```shell
php artisan db:seed --class=InitialAccountSeeder
```
Serve in port 8000
```shell
php artisan serve
```
