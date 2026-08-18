<?php

namespace Kanboard\Plugin\EmailLogin\Validator;

use Kanboard\Validator\UserValidator as BaseUserValidator;
use Kanboard\Model\UserModel;
use SimpleValidator\Validator;
use SimpleValidator\Validators;

/**
 * User Validator (Extended)
 *
 * Overrides the core UserValidator to:
 * 1. Enforce email as a required field on user creation
 * 2. Enforce email uniqueness on creation and modification
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

        // Enforce email uniqueness
        $rules[] = new Validators\Unique(
            'email',
            t('This email is already used'),
            $this->db->getConnection(),
            UserModel::TABLE,
            'id'
        );

        return $rules;
    }

    /**
     * Validate user creation
     *
     * Adds email as a required field (in addition to username).
     *
     * @access public
     * @param  array   $values           Form values
     * @return array   $valid, $errors   [0] = Success or not, [1] = List of errors
     */
    public function validateCreation(array $values)
    {
        $rules = array(
            new Validators\Required('username', t('The username is required')),
            new Validators\Required('email', t('The email address is required')),
        );

        if (isset($values['is_ldap_user']) && $values['is_ldap_user'] == 1) {
            $v = new Validator($values, array_merge($rules, $this->commonValidationRules()));
        } else {
            $v = new Validator($values, array_merge($rules, $this->commonValidationRules(), $this->commonPasswordValidationRules()));
        }

        return array(
            $v->execute(),
            $v->getErrors()
        );
    }
}
