<?php

namespace DealNews\Chronicle\View;

use DateTimeImmutable;
use DateTimeZone;
use DealNews\GetConfig\GetConfig;
use PageMill\MVC\Template\HTMLAbstract;
use Throwable;

/**
 * Shared HTML layout for all server-rendered pages.
 *
 * Provides a common <head>, navigation bar, error banner, and footer.
 * Subclasses implement prepareDocument() to set the page title and
 * generateBody() to render the page-specific content.
 *
 * @package DealNews\Chronicle
 */
abstract class AbstractHTML extends HTMLAbstract {

    /**
     * Page title, set by subclasses in prepareDocument().
     *
     * @var string
     */
    protected string $page_title = 'Chronicle';

    /**
     * Cached timezone instance for date formatting.
     *
     * @var DateTimeZone|null
     */
    protected ?DateTimeZone $timezone = null;

    /**
     * Errors to display in the error banner.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Outputs the DOCTYPE, <head>, and opening navigation.
     * Ensures a CSRF token exists in the session for form protection.
     *
     * @return void
     */
    protected function generateHeader(): void {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($this->page_title) ?></title>
    <link rel="stylesheet" href="/style.css">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
</head>
<body>
<nav>
    <a href="/" class="nav-brand">
        <img src="/logo_white.svg" alt="" class="nav-logo">
        Chronicle
    </a>
    <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false"
            onclick="var l=document.querySelector('.nav-links');
                     var open=l.classList.toggle('is-open');
                     this.setAttribute('aria-expanded', open);">
        <span></span><span></span><span></span>
    </button>
    <span class="nav-links">
        <a href="/">Logs</a>
        <a href="/admin/sources">Sources</a>
        <a href="/admin/types">Types</a>
        <a href="/admin/api-keys">API Keys</a>
        <a href="/admin/users">Users</a>
        <a href="/auth/logout">Sign Out</a>
    </span>
</nav>
<main>
<?php
        if (!empty($this->errors)) {
            echo '<div class="errors"><ul>';
            foreach ($this->errors as $error) {
                echo '<li>' . htmlspecialchars((string) $error) . '</li>';
            }
            echo '</ul></div>';
        }
    }

    /**
     * Returns the configured timezone, lazily initialised from config.
     *
     * @return DateTimeZone
     */
    protected function getTimezone(): DateTimeZone {
        if ($this->timezone === null) {
            $tz_name        = GetConfig::init()->get('chronicle.timezone') ?? 'UTC';
            $this->timezone = new DateTimeZone($tz_name);
        }

        return $this->timezone;
    }

    /**
     * Formats a stored UTC date string for display in the configured timezone,
     * appending the timezone abbreviation (e.g. "2024-01-15 05:30:00 EST").
     * Falls back to the raw string if parsing fails.
     *
     * @param  string $date_string Stored date in Y-m-d H:i:s UTC.
     * @return string
     */
    protected function formatDate(string $date_string): string {
        try {
            $dt = new DateTimeImmutable($date_string, new DateTimeZone('UTC'));
        } catch (Throwable) {
            return $date_string;
        }

        return $dt->setTimezone($this->getTimezone())->format('Y-m-d H:i:s T');
    }

    /**
     * Returns a hidden input element containing the CSRF token.
     *
     * @return string
     */
    protected function csrfField(): string {
        $token = htmlspecialchars($_SESSION['csrf_token'] ?? '');
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }

    /**
     * Outputs the closing </main>, footer, and </body></html>.
     *
     * @return void
     */
    protected function generateFooter(): void {
        ?>
</main>
<footer>
    <p>Chronicle &mdash; &copy; <a href="https://www.dealnews.com/">DealNews</a></p>
</footer>
</body>
</html>
<?php
    }
}
