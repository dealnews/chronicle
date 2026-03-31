<?php

namespace DealNews\Chronicle\View;

use PageMill\MVC\Template\HTMLAbstract;

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
    <span class="nav-links">
        <a href="/admin/sources">Sources</a>
        <a href="/admin/types">Types</a>
        <a href="/admin/api-keys">API Keys</a>
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
    <p>Chronicle &mdash; &copy; DealNews</p>
</footer>
</body>
</html>
<?php
    }
}
