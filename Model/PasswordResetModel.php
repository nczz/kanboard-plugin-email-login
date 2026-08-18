<?php

namespace Kanboard\Plugin\EmailLogin\Model;

use Kanboard\Model\PasswordResetModel as BasePasswordResetModel;
use Kanboard\Model\UserModel;

/**
 * Password Reset Model (Extended)
 *
 * Overrides the core PasswordResetModel to support password reset
 * using either username or email address.
 *
 * Behavior notes:
 * - Username path: matches core behavior exactly (requires non-empty email, no is_active check)
 * - Email path: requires is_active=1 (disabled accounts should not be discoverable by email)
 *
 * @package Kanboard\Plugin\EmailLogin\Model
 */
class PasswordResetModel extends BasePasswordResetModel
{
    /**
     * Generate a new reset token for a user
     *
     * Looks up user by username first, then falls back to email.
     *
     * @param  string  $username  Username or email address
     * @param  integer $expiration
     * @return boolean|string
     */
    public function create($username, $expiration = 0)
    {
        // First try: lookup by username (matches core behavior exactly)
        $user_id = $this->db->table(UserModel::TABLE)
            ->eq('username', $username)
            ->neq('email', '')
            ->notNull('email')
            ->findOneColumn('id');

        // Second try: lookup by email if input contains @
        if (!$user_id && strpos($username, '@') !== false) {
            $user_id = $this->db->table(UserModel::TABLE)
                ->eq('email', $username)
                ->eq('is_active', 1)
                ->findOneColumn('id');
        }

        if (!$user_id) {
            return false;
        }

        $token = $this->token->getToken();

        $result = $this->db->table(self::TABLE)->insert([
            'token' => $token,
            'user_id' => $user_id,
            'date_expiration' => $expiration ?: time() + self::DURATION,
            'date_creation' => time(),
            'ip' => $this->request->getIpAddress(),
            'user_agent' => $this->request->getUserAgent(),
            'is_active' => 1,
        ]);

        return $result ? $token : false;
    }
}
