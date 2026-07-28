<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Wabot setup

The application uses Laravel 12, Filament 4, MySQL, and a separate local Node.js service for Baileys.

Run the Laravel database migrations and seed the default admin user:

```bash
php artisan migrate --seed
```

The admin panel is available at `/admin`. The initial credentials are `admin@mail.com` / `admin`; change the password after the first login.

Configure the Laravel `.env` file:

```env
WHATSAPP_GATEWAY_URL=http://127.0.0.1:3001
WHATSAPP_GATEWAY_TOKEN=change-this-token
```

Configure and run the WhatsApp service in a second terminal:

```bash
cd whatsapp-service
cp .env.example .env
# Set WHATSAPP_API_TOKEN to the same value as WHATSAPP_GATEWAY_TOKEN
npm start
```

Scan the QR Code shown in the Node.js terminal. Then open `/admin/whatsapp-connection` to check the status and synchronize WhatsApp groups. A report can be sent from `/admin/work-reports` using the `Kirim ke WhatsApp` action.

Run these processes for queue and scheduled delivery:

```bash
php artisan queue:work --tries=3
php artisan schedule:work
```

Configure weekly schedules at `/admin/message-schedules`.

## Multi-tenant

Data operasional dipisahkan berdasarkan workspace/tenant. Setelah deploy perubahan ini, jalankan:

```bash
php artisan migrate --force
php artisan optimize:clear
```

Workspace dikelola melalui menu `Pengaturan > Workspace`. Pengguna hanya melihat data dari workspace aktif. Service Baileys juga memakai sesi autentikasi terpisah per workspace di bawah `WHATSAPP_AUTH_DIR`, sehingga setiap workspace perlu menghubungkan perangkat WhatsApp-nya sendiri.

Akun `admin@mail.com` bertipe `superadmin` dan dapat melihat seluruh workspace. Akun lain yang dibuat dari menu manajemen pengguna bertipe `user` dan otomatis mendapatkan workspace pribadi.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
# botwalaporanpekerjaan
# botwalaporanpekerjaan
