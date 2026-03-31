<?php

namespace DealNews\Chronicle\Action;

use DealNews\Chronicle\Data\User;
use DealNews\Chronicle\Mapper\User as UserMapper;
use DealNews\GetConfig\GetConfig;
use PageMill\MVC\ActionAbstract;

/**
 * Handles the Google OAuth callback.
 *
 * Exchanges the authorization code for an access token, retrieves the
 * user's profile from Google, and finds or creates a local user record.
 * On success, writes user_id to the session and redirects to /.
 *
 * @package DealNews\Chronicle
 */
class GoogleOAuthCallback extends ActionAbstract {

    /**
     * Authorization code from Google.
     *
     * @var string
     */
    protected string $code = '';

    /**
     * State token returned by Google, used to prevent CSRF.
     *
     * @var string
     */
    protected string $state = '';

    /**
     * @param  array<string, mixed> $data
     * @return null
     */
    public function doAction(array $data = []): mixed {
        if (empty($this->code)) {
            $this->errors[] = 'Missing OAuth authorization code.';
            return null;
        }

        $expected_state = $_SESSION['oauth_state'] ?? '';
        unset($_SESSION['oauth_state']);

        if (empty($this->state) || !hash_equals($expected_state, $this->state)) {
            $this->errors[] = 'Invalid OAuth state. Please try signing in again.';
            return null;
        }

        $config = GetConfig::init();

        $client_id     = $config->get('chronicle.google.client_id');
        $client_secret = $config->get('chronicle.google.client_secret');
        $redirect_uri  = $config->get('chronicle.google.redirect_uri');

        $token = $this->fetchToken($client_id, $client_secret, $redirect_uri);

        if (empty($token['access_token'])) {
            $detail = $token['error_description'] ?? $token['error'] ?? null;
            $this->errors[] = 'Failed to obtain access token from Google.' .
                ($detail !== null ? ' Google said: ' . $detail : '');
            return null;
        }

        $profile = $this->fetchProfile($token['access_token']);

        if (empty($profile['sub']) || empty($profile['email'])) {
            $this->errors[] = 'Failed to retrieve user profile from Google.';
            return null;
        }

        if (!$this->isAllowedEmail($profile['email'], $config)) {
            $this->errors[] = 'Your account is not authorized to access this application.';
            return null;
        }

        $user = $this->findOrCreateUser($profile);

        $_SESSION['user_id'] = $user->user_id;

        header('Location: /');
        exit;
    }

    /**
     * Exchanges the authorization code for an access token.
     *
     * @param  string $client_id
     * @param  string $client_secret
     * @param  string $redirect_uri
     * @return array<string, mixed>
     */
    protected function fetchToken(
        string $client_id,
        string $client_secret,
        string $redirect_uri
    ): array {
        $post_data = http_build_query([
            'code'          => $this->code,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $redirect_uri,
            'grant_type'    => 'authorization_code',
        ]);

        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => 'Content-Type: application/x-www-form-urlencoded',
                'content'       => $post_data,
                'ignore_errors' => true,
            ],
        ]);

        $response = file_get_contents(
            'https://oauth2.googleapis.com/token',
            false,
            $context
        );

        if ($response === false) {
            return [];
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Retrieves the authenticated user's profile from Google.
     *
     * @param  string $access_token
     * @return array<string, mixed>
     */
    protected function fetchProfile(string $access_token): array {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer {$access_token}",
            ],
        ]);

        $response = file_get_contents(
            'https://www.googleapis.com/oauth2/v3/userinfo',
            false,
            $context
        );

        if ($response === false) {
            return [];
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Returns true if the email is permitted to log in.
     *
     * When chronicle.google.allowed_domains is set, the email's domain must
     * appear in the comma-separated list. When the config key is absent or
     * empty, all Google accounts are allowed.
     *
     * @param  string    $email
     * @param  GetConfig $config
     * @return bool
     */
    protected function isAllowedEmail(string $email, GetConfig $config): bool {
        $allowed = $config->get('chronicle.google.allowed_domains');

        if (empty($allowed)) {
            return true;
        }

        $domain  = substr($email, strrpos($email, '@') + 1);
        $domains = array_map('trim', explode(',', $allowed));

        return in_array($domain, $domains, true);
    }

    /**
     * Finds an existing user by google_id or email, or creates a new one.
     *
     * @param  array<string, mixed> $profile
     * @return User
     */
    protected function findOrCreateUser(array $profile): User {
        $mapper = new UserMapper();

        $users = $mapper->find(['google_id' => $profile['sub']]);

        if (!empty($users)) {
            $user = reset($users);
        } else {
            $users = $mapper->find(['email' => $profile['email']]);
            $user  = !empty($users) ? reset($users) : new User();
        }

        $user->google_id     = $profile['sub'];
        $user->email         = $profile['email'];
        $user->name          = $profile['name'] ?? $user->name;
        $user->last_login_at = date('Y-m-d H:i:s');

        return $mapper->save($user);
    }
}
