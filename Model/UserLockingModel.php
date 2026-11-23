<?php

namespace Kanboard\Plugin\EmailLogin\Model;

use Kanboard\Model\UserLockingModel as BaseUserLockingModel;
use Kanboard\Model\UserModel;

/**
 * Email-aware user locking model.
 *
 * Authentication accepts either a username or an email address. All brute-force
 * checks and updates must therefore resolve the same canonical username before
 * accessing Kanboard's username-based counters.
 *
 * @package Kanboard\Plugin\EmailLogin\Model
 */
class UserLockingModel extends BaseUserLockingModel
{
    /**
     * Canonical usernames resolved during the current request.
     *
     * @var array
     */
    protected $resolvedUsernames = array();

    /**
     * Resolve an authentication identifier using the same precedence as login:
     * eligible local username first, then an eligible local account's email.
     *
     * @param  string $identifier
     * @return string
     */
    protected function resolveUsername($identifier)
    {
        if (strpos($identifier, '@') === false) {
            return $identifier;
        }

        if (array_key_exists($identifier, $this->resolvedUsernames)) {
            return $this->resolvedUsernames[$identifier];
        }

        $username = $identifier;
        $isUsername = $this->db->table(UserModel::TABLE)
            ->eq('username', $identifier)
            ->eq('disable_login_form', 0)
            ->eq('is_ldap_user', 0)
            ->eq('is_active', 1)
            ->exists();

        if (!$isUsername) {
            $resolvedUsername = $this->db->table(UserModel::TABLE)
                ->eq('email', $identifier)
                ->eq('disable_login_form', 0)
                ->eq('is_ldap_user', 0)
                ->eq('is_active', 1)
                ->findOneColumn('username');

            if ($resolvedUsername !== false && $resolvedUsername !== null && $resolvedUsername !== '') {
                $username = $resolvedUsername;
            }
        }

        $this->resolvedUsernames[$identifier] = $username;

        return $username;
    }

    public function getFailedLogin($username)
    {
        return parent::getFailedLogin($this->resolveUsername($username));
    }

    public function resetFailedLogin($username)
    {
        return parent::resetFailedLogin($this->resolveUsername($username));
    }

    public function incrementFailedLogin($username)
    {
        return parent::incrementFailedLogin($this->resolveUsername($username));
    }

    public function isLocked($username)
    {
        return parent::isLocked($this->resolveUsername($username));
    }

    public function lock($username, $duration = 15)
    {
        return parent::lock($this->resolveUsername($username), $duration);
    }
}
