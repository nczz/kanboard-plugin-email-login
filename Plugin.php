<?php

namespace Kanboard\Plugin\EmailLogin;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;
use Kanboard\Plugin\EmailLogin\Auth\EmailDatabaseAuth;
use Kanboard\Plugin\EmailLogin\Subscriber\EmailAuthSubscriber;

/**
 * EmailLogin Plugin
 *
 * Allows users to log in and reset passwords using either username or email address.
 * Enforces email uniqueness and required email on user creation/modification.
 *
 * @author  Knockers Dev Team
 * @license MIT
 */
class Plugin extends Base
{
    public function initialize()
    {
        // Register additional auth provider for email-based login
        $this->authenticationManager->register(new EmailDatabaseAuth($this->container));

        // Register event subscriber for brute-force protection with email login
        $this->dispatcher->addSubscriber(new EmailAuthSubscriber($this->container));

        // Override templates to mark email as required
        $this->template->setTemplateOverride('user_creation/show', 'EmailLogin:user_creation/show');
        $this->template->setTemplateOverride('user_modification/show', 'EmailLogin:user_modification/show');
    }

    public function getClasses()
    {
        return [
            'Plugin\\EmailLogin\\Model' => [
                'PasswordResetModel',
            ],
            'Plugin\\EmailLogin\\Validator' => [
                'UserValidator',
                'AuthValidator',
            ],
        ];
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__.'/Locale');
    }

    public function getPluginName()
    {
        return 'EmailLogin';
    }

    public function getPluginDescription()
    {
        return t('Allow login and password reset with email address. Enforce unique email.');
    }

    public function getPluginAuthor()
    {
        return 'Knockers Dev Team';
    }

    public function getPluginVersion()
    {
        return '1.1.0';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/nczz/kanboard-plugin-email-login';
    }

    public function getCompatibleVersion()
    {
        return '>=1.2.20';
    }
}
