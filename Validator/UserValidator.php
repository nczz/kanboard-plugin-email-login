<?php

namespace Kanboard\Plugin\EmailLogin\Validator;

use Kanboard\Validator\UserValidator as BaseUserValidator;
use Kanboard\Model\UserModel;
use SimpleValidator\Validators;

/**
 * User Validator (Extended)
 *
 * Overrides the core UserValidator to enforce email uniqueness.
 *
 * @package Kanboard\Plugin\EmailLogin\Validator
 */
class UserValidator extends BaseUserValidator
{
    /**
     * Common validation rules
     *
     * Adds email uniqueness check to the base rules.
     *
     * @access protected
     * @return array
     */
    protected function commonValidationRules()
    {
        $rules = parent::commonValidationRules();

        // Add email uniqueness constraint
        // Note: Unique validator only fires when the field is non-empty,
        // so users without email (e.g. bot accounts) are not affected.
        $rules[] = new Validators\Unique(
            'email',
            t('This email is already used'),
            $this->db->getConnection(),
            UserModel::TABLE,
            'id'
        );

        return $rules;
    }
}
