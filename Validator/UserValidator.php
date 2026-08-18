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
 * 1. Enforce email as a required field on user creation and modification
 * 2. Enforce email uniqueness on all operations (create, modify, API modify)
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

    /**
     * Validate user modification
     *
     * Adds email as a required field.
     *
     * @access public
     * @param  array   $values           Form values
     * @return array   $valid, $errors   [0] = Success or not, [1] = List of errors
     */
    public function validateModification(array $values)
    {
        $rules = array(
            new Validators\Required('id', t('The user id is required')),
            new Validators\Required('username', t('The username is required')),
            new Validators\Required('email', t('The email address is required')),
        );

        $v = new Validator($values, array_merge($rules, $this->commonValidationRules()));

        return array(
            $v->execute(),
            $v->getErrors()
        );
    }

    /**
     * Validate user API modification
     *
     * Enforces email uniqueness (but does not require email on partial updates).
     *
     * @access public
     * @param  array   $values           Form values
     * @return array   $valid, $errors   [0] = Success or not, [1] = List of errors
     */
    public function validateApiModification(array $values)
    {
        $rules = array(
            new Validators\Required('id', t('The user id is required')),
        );

        $v = new Validator($values, array_merge($rules, $this->commonValidationRules()));

        return array(
            $v->execute(),
            $v->getErrors()
        );
    }
}
