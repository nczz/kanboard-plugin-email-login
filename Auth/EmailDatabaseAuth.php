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
 * @package Kanboard\Plugin\EmailLogin\Auth
 */
class EmailDatabaseAuth extends Base implements PasswordAuthenticationProviderInterface
{
    /**
     * @var array
     */
    protected $userInfo = [];

    /**
     * @var string
     */
    protected $username = '';

    /**
     * @var string
     */
    protected $password = '';

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
            ->columns('id', 'password')
            ->eq('email', $this->username)
            ->eq('disable_login_form', 0)
            ->eq('is_ldap_user', 0)
            ->eq('is_active', 1)
            ->findOne();

        if (!empty($user) && password_verify($this->password, $user['password'])) {
            $this->userInfo = $user;
            return true;
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
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }
}
