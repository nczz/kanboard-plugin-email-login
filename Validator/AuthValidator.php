<?php

namespace Kanboard\Plugin\EmailLogin\Validator;

use Kanboard\Model\UserModel;
use Kanboard\Validator\AuthValidator as BaseAuthValidator;

/**
 * Authentication Validator (Extended)
 *
 * Overrides locking/captcha validation to properly handle email input.
 * When a user enters their email address, resolves to real username
 * before checking lock status and captcha requirements.
 *
 * @package Kanboard\Plugin\EmailLogin\Validator
 */
class AuthValidator extends BaseAuthValidator
{
    /**
     * Resolve email to real username for brute-force checks
     *
     * @param  string $input
     * @return string  Real username, or the original input if not an email or not found
     */
    protected function resolveUsername($input)
    {
        if (strpos($input, '@') === false) {
            return $input;
        }

        $username = $this->db->table(UserModel::TABLE)
            ->eq('email', $input)
            ->findOneColumn('username');

        return !empty($username) ? $username : $input;
    }

    /**
     * Validate user locking — resolves email to username first
     *
     * @access protected
     * @param  array   $values
     * @return array
     */
    protected function validateLocking(array $values)
    {
        $result = true;
        $errors = array();

        $username = $this->resolveUsername($values['username']);

        if ($this->userLockingModel->isLocked($username)) {
            $result = false;
            $errors['login'] = t('Your account is locked for %d minutes', BRUTEFORCE_LOCKDOWN_DURATION);
            $this->logger->error('Account locked: '.$username);
        }

        return array($result, $errors);
    }

    /**
     * Validate captcha — resolves email to username first
     *
     * @access protected
     * @param  array   $values
     * @return array
     */
    protected function validateCaptcha(array $values)
    {
        $result = true;
        $errors = array();

        $username = $this->resolveUsername($values['username']);

        if ($this->userLockingModel->hasCaptcha($username) || $this->captchaModel->isLocked($this->request->getIpAddress())) {
            if (! session_exists('captcha')) {
                $result = false;
            } else {
                $builder = new \Gregwar\Captcha\CaptchaBuilder;
                $builder->setPhrase(session_get('captcha'));
                $result = $builder->testPhrase(isset($values['captcha']) ? $values['captcha'] : '');

                if (! $result) {
                    $errors['login'] = t('Bad username or password');
                }
            }
        }

        return array($result, $errors);
    }
}
