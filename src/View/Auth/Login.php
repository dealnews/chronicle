<?php

namespace DealNews\Chronicle\View\Auth;

use DealNews\Chronicle\View\AbstractHTML;
use DealNews\GetConfig\GetConfig;

/**
 * Login / first-run setup view.
 *
 * When no users exist (first install), renders a "Create Admin Account" form
 * that posts to /auth/setup. Once at least one user exists, renders the
 * normal email/password sign-in form, with an optional Google OAuth button
 * when credentials are configured.
 *
 * @package DealNews\Chronicle
 */
class Login extends AbstractHTML {

    /**
     * Total number of users in the system. 0 triggers first-run setup UI.
     *
     * @var int
     */
    protected int $user_count = 0;

    /**
     * @return void
     */
    protected function prepareDocument(): void {
        $this->page_title = $this->user_count === 0
            ? 'Set Up Chronicle'
            : 'Sign In — Chronicle';
    }

    /**
     * @return void
     */
    protected function generateBody(): void {
        if ($this->user_count === 0) {
            $this->generateSetupForm();
        } else {
            $this->generateLoginForm();
        }
    }

    /**
     * Renders the first-run account creation form.
     *
     * @return void
     */
    protected function generateSetupForm(): void {
        ?>
<div class="login-page">
    <h1>Welcome to Chronicle</h1>
    <p>No users exist yet. Create your admin account to get started.</p>

    <form method="POST" action="/auth/setup">
        <?= $this->csrfField() ?>
        <div class="field">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required autofocus>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="8">
        </div>
        <button type="submit">Create Account</button>
    </form>
</div>
<?php
    }

    /**
     * Renders the normal sign-in form, plus an optional Google OAuth button.
     *
     * @return void
     */
    protected function generateLoginForm(): void {
        $google_url = $this->buildGoogleAuthUrl();
        ?>
<div class="login-page">
    <h1>Sign In</h1>

    <form method="POST" action="/auth/login">
        <?= $this->csrfField() ?>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Sign In</button>
    </form>

<?php if ($google_url !== null): ?>
    <div class="oauth-divider">or</div>
    <a class="google-btn" href="<?= htmlspecialchars($google_url) ?>">
        Sign in with Google
    </a>
<?php endif; ?>
</div>
<?php
    }

    /**
     * Builds the Google OAuth authorization URL if Google credentials are
     * configured, returning null otherwise.
     *
     * @return string|null
     */
    protected function buildGoogleAuthUrl(): ?string {
        $config = GetConfig::init();

        $client_id    = $config->get('chronicle.google.client_id');
        $redirect_uri = $config->get('chronicle.google.redirect_uri');

        if (empty($client_id) || empty($redirect_uri)) {
            return null;
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        return 'https://accounts.google.com/o/oauth2/v2/auth?' .
               http_build_query([
                   'client_id'     => $client_id,
                   'redirect_uri'  => $redirect_uri,
                   'response_type' => 'code',
                   'scope'         => 'openid email profile',
                   'state'         => $state,
               ]);
    }
}
