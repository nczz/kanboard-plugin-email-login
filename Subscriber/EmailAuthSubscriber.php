<?php

namespace Kanboard\Plugin\EmailLogin\Subscriber;

use Kanboard\Core\Base;
use Kanboard\Core\Security\AuthenticationManager;
use Kanboard\Event\AuthFailureEvent;
use Kanboard\Model\UserModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Email Auth Subscriber
 *
 * Resolves email → real username on login failure so that
 * brute-force counters (captcha + lockout) work correctly
 * when users attempt login with an email address.
 *
 * @package Kanboard\Plugin\EmailLogin\Subscriber
 */
class EmailAuthSubscriber extends Base implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            AuthenticationManager::EVENT_FAILURE => ['onLoginFailure', 10], // priority 10 > default 0
        ];
    }

    /**
     * On login failure: if the input was an email, resolve to real username
     * and increment the brute-force counter for that user.
     *
     * @param AuthFailureEvent $event
     */
    public function onLoginFailure(AuthFailureEvent $event)
    {
        $input = $event->getUsername();

        // Only act if the input looks like an email
        if (empty($input) || strpos($input, '@') === false) {
            return;
        }

        // Resolve email → real username
        $username = $this->db->table(UserModel::TABLE)
            ->eq('email', $input)
            ->findOneColumn('username');

        if (!empty($username)) {
            // Increment failed login counter for the real username
            // (Core AuthSubscriber will also try with the email but won't find a row)
            $this->userLockingModel->incrementFailedLogin($username);

            if ($this->userLockingModel->getFailedLogin($username) > BRUTEFORCE_LOCKDOWN) {
                $this->userLockingModel->lock($username, BRUTEFORCE_LOCKDOWN_DURATION);
            }
        }
    }
}
