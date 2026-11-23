<?php

namespace Kanboard\Plugin\EmailLogin\Controller;

use Kanboard\Controller\PasswordResetController as BasePasswordResetController;

/**
 * Password Reset Controller (Extended)
 *
 * Resolves the submitted identifier once and uses the canonical username to
 * create the reset token, ensuring email-based requests have a real recipient.
 *
 * @package Kanboard\Plugin\EmailLogin\Controller
 */
class PasswordResetController extends BasePasswordResetController
{
    /**
     * Send a password reset email using a username or email address.
     *
     * @param string $identifier
     */
    protected function sendEmail($identifier)
    {
        $user = $this->userModel->getByUsername($identifier);
        $matchedByEmail = false;

        if (!$user && strpos($identifier, '@') !== false) {
            $user = $this->userModel->getByEmail($identifier);
            $matchedByEmail = true;
        }

        if (
            !$user ||
            empty($user['email']) ||
            ($matchedByEmail && (int) $user['is_active'] !== 1)
        ) {
            return;
        }

        $token = $this->passwordResetModel->create($user['username']);

        if ($token !== false) {
            $this->emailClient->send(
                $user['email'],
                $user['name'] ?: $user['username'],
                t('Password Reset for Kanboard'),
                $this->template->render('password_reset/email', array('token' => $token))
            );
        }
    }
}
