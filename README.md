# The Bloom

A coffee brewing guide website built on our own PHP MVC framework. Made for our IPT10 (Integrative Programming and Technologies) final project.

**Team:** Nathaniel Rae Y. Marquez and Laurence R. Pangilinan, BSIT 3A

## What it does

Every guide on The Bloom has the same structure: coffee in grams, water in grams, water temperature, the ratio, and steps with times.

**Visitors** (no login needed) can:
- Browse published guides and filter them by brewing method or difficulty
- Search guides by title, summary or method
- Follow timed steps on each guide (for example "0:00 to 0:09")
- Use the ratio calculator to scale a recipe to any amount of coffee
- Watch the guide's YouTube video

**Staff** can:
- Log in to a dashboard showing how many guides are published and how many are drafts
- Write guides with any number of steps, save them as drafts, preview them and publish them
- Search YouTube from the guide form and attach a video

Staff are either **editors** or **admins**. Only admins can manage the list of brewing methods.

## Built with

- PHP 8.1+ with our own framework (no Laravel or Symfony)
- MySQL / MariaDB (XAMPP)
- Composer for PSR-4 autoloading
- YouTube Data API v3
- PHPUnit for tests

## The framework

The framework is split into two halves:

- `core/` is the engine. It holds the router, request, response, sessions, validation, views and database classes. It knows nothing about coffee.
- `app/` is The Bloom itself. It holds the controllers, models, views, middleware and services.

Composer maps `Core\` to `core/` and `App\` to `app/`, so we never `require` our own classes.

Every request goes through the same chain:

```
.htaccess → public/index.php → Router → Middleware → Controller → Model → View → Response
```

Routes live in `routes/web.php`:

```php
$router->get('/guides/{slug}', [GuideController::class, 'show']);

$router->group(['prefix' => '/admin', 'middleware' => ['auth']], function (Router $r) {
    $r->get('/', [AdminController::class, 'dashboard']);
});
```

There are three middleware:

| Name | What it does |
| --- | --- |
| `logger` | Writes every request to `storage/logs` |
| `csrf` | Rejects forms without the secret token (419 Page Expired) |
| `auth` / `role:admin` | Lets in staff only / admins only |

## Folder structure

```
the-bloom/
├── app/
│   ├── Controllers/   page logic
│   ├── Middleware/    Csrf, Logger, Role
│   ├── Models/        Guide, Method, User, VideoCache
│   ├── Services/      YouTubeService, RecipeScaler
│   └── Views/         HTML templates
├── core/              the framework
├── database/
│   ├── migrations/    001–005 .sql files
│   └── migrate.php
├── public/            index.php, css, js, images (the only folder the browser can reach)
├── routes/web.php     every URL the site answers
├── storage/logs/
├── tests/             PHPUnit tests
├── .env.example       settings template
├── config.php         all settings in one place
└── composer.json
```

## Getting started

You need XAMPP (PHP 8.1 or newer) and Composer.

1. Clone the project into your XAMPP `htdocs` folder:
   ```
   cd C:\xampp\htdocs
   git clone https://github.com/ZeroFaithe/the-bloom.git the_bloom
   cd the_bloom
   ```
2. Install the dependencies:
   ```
   composer install
   ```
3. Copy `.env.example` to `.env` and fill in your settings:
   ```
   BASE_URL=http://localhost/the_bloom
   DB_NAME=the_bloom
   DB_USER=root
   DB_PASS=your_mysql_password
   YOUTUBE_API_KEY=your_key_here
   ```
   The YouTube key is optional. Without it, staff can still paste a video link, but search is turned off.
4. Start **Apache** and **MySQL** in the XAMPP Control Panel.
5. Build the database:
   ```
   php database/migrate.php
   ```
   This creates the database, the five tables and the first admin account. If you leave `ADMIN_PASSWORD` empty in `.env`, a random password is printed in the terminal, so save it.
6. Open http://localhost/the_bloom and click **Staff login**.

> Make sure `mod_rewrite` is turned on in Apache. If it's off, the project blocks every page on purpose, so files like `.env` can't be downloaded.

## Getting a YouTube API key

1. Go to the [Google Cloud Console](https://console.cloud.google.com/) and create a project.
2. Enable **YouTube Data API v3**.
3. Under **Credentials**, create an **API key**.
4. Paste it into `YOUTUBE_API_KEY` in your `.env`.

The free quota is 10,000 units a day. A search costs 100 units, so about 100 searches a day. Video details are saved for 7 days, so visitors never use up quota.

## Running the tests

```
vendor\bin\phpunit
```

There are 120 tests covering the router, middleware, requests, responses, sessions, validation, config, the ratio math and the YouTube service. The YouTube tests use a fake YouTube server, so they don't need an API key or use any quota.

## Database

| Table | Holds |
| --- | --- |
| `users` | Staff accounts with hashed passwords and a role |
| `methods` | Brewing methods (V60, French Press, AeroPress…) |
| `guides` | Each recipe, linked to its author and method |
| `guide_steps` | The steps of each guide, in order |
| `video_cache` | Saved YouTube titles and thumbnails |

## Security

- Prepared statements for every query (SQL injection)
- `e()` escapes everything printed in views (XSS)
- CSRF token on every form
- Passwords stored with `password_hash()`
- Session ID regenerated on login
- Only `public/` is reachable, and `.env` and other hidden files are blocked

## Future work

- A page for admins to manage staff accounts
- Letting users change their own password
- Limiting login attempts
- Letting editors edit only their own guides
- Favourite guides for visitors
