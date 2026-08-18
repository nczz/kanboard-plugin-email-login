<?php

namespace Kanboard\Plugin\EmailLogin\Auth;

use Kanboard\Core\Base;
use Kanboard\Core\Security\PasswordAuthenticationProviderInterface;
use Kanboard\Model\UserModel;
use Kanboard\User\DatabaseUserProvider;

/**
 * Email Database Authentication Provider
 *
 * Fallback provider that allows authentication using email address
 * when the standard DatabaseAuth (username-based) fails.
 *
 * Security: On successful email lookup, the real username is stored
 * so that AuthFailureEvent and brute-force counters work correctly.
 *
 * @package Kanboard\Plugin\EmailLogin\Auth
 */
class EmailDatabaseAuth extends Base implements PasswordAuthenticationProviderInterface
{
    /**
     * @var array
     */
    protected $userInfo = [];

    /**
     * The input value (could be email)
     *
     * @var string
     */
    protected $username = '';

    /**
     * @var string
     */
    protected $password = '';

    /**
     * The resolved real username (for brute-force tracking)
     *
     * @var string
     */
    protected $resolvedUsername = '';

    /**
     * @return string
     */
    public function getName()
    {
        return 'EmailDatabase';
    }

    /**
     * Authenticate using email address as identifier
     *
     * @return boolean
     */
    public function authenticate()
    {
        // Only try email lookup if input looks like an email
        if (strpos($this->username, '@') === false) {
            return false;
        }

        $user = $this->db
            ->table(UserModel::TABLE)
            ->columns('id', 'username', 'password')
            ->eq('email', $this->username)
            ->eq('disable_login_form', 0)
            ->eq('is_ldap_user', 0)
            ->eq('is_active', 1)
            ->findOne();

        if (!empty($user) && password_verify($this->password, $user['password'])) {
            $this->userInfo = $user;
            $this->resolvedUsername = $user['username'];
            return true;
        }

        // Even on failure, resolve the username for brute-force tracking
        if (!empty($user)) {
            $this->resolvedUsername = $user['username'];
        }

        return false;
    }

    /**
     * @return DatabaseUserProvider|null
     */
    public function getUser()
    {
        if (empty($this->userInfo)) {
            return null;
        }

        return new DatabaseUserProvider($this->userInfo);
    }

    /**
     * Get the resolved real username (for external brute-force integration)
     *
     * @return string
     */
    public function getResolvedUsername()
    {
        return $this->resolvedUsername;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
        $this->resolvedUsername = '';
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }
}
