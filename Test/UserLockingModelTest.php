<?php

namespace Kanboard\Model {
    class UserModel
    {
        const TABLE = 'users';
    }

    class UserLockingModel
    {
        protected $db;
        public $calls = array();
        public $failedLogins = array();

        public function __construct($db)
        {
            $this->db = $db;
        }

        public function getFailedLogin($username)
        {
            $this->calls[] = array('getFailedLogin', $username);
            return isset($this->failedLogins[$username]) ? $this->failedLogins[$username] : 0;
        }

        public function hasCaptcha($username, $tries = 3)
        {
            return $this->getFailedLogin($username) >= $tries;
        }

        public function resetFailedLogin($username)
        {
            $this->calls[] = array('resetFailedLogin', $username);
            return true;
        }

        public function incrementFailedLogin($username)
        {
            $this->calls[] = array('incrementFailedLogin', $username);
            return true;
        }

        public function isLocked($username)
        {
            $this->calls[] = array('isLocked', $username);
            return false;
        }

        public function lock($username, $duration = 15)
        {
            $this->calls[] = array('lock', $username, $duration);
            return true;
        }
    }
}

namespace {
    class FakeDatabase
    {
        public $queryCount = 0;
        private $users;

        public function __construct(array $users)
        {
            $this->users = $users;
        }

        public function table($table)
        {
            $this->queryCount++;
            return new FakeTable($this->users);
        }
    }

    class FakeTable
    {
        private $filters = array();
        private $users;

        public function __construct(array $users)
        {
            $this->users = $users;
        }

        public function eq($column, $value)
        {
            $this->filters[$column] = $value;
            return $this;
        }

        public function exists()
        {
            return $this->findOne() !== false;
        }

        public function findOneColumn($column)
        {
            $user = $this->findOne();
            return $user === false ? false : $user[$column];
        }

        private function findOne()
        {
            foreach ($this->users as $user) {
                $matches = true;

                foreach ($this->filters as $column => $value) {
                    if (!array_key_exists($column, $user) || $user[$column] != $value) {
                        $matches = false;
                        break;
                    }
                }

                if ($matches) {
                    return $user;
                }
            }

            return false;
        }
    }

    function user($username, $email, $active = 1, $ldap = 0, $disableLoginForm = 0)
    {
        return array(
            'username' => $username,
            'email' => $email,
            'is_active' => $active,
            'is_ldap_user' => $ldap,
            'disable_login_form' => $disableLoginForm,
        );
    }

    function assertSameValue($expected, $actual, $message)
    {
        if ($expected !== $actual) {
            fwrite(STDERR, $message."\nExpected: ".var_export($expected, true)."\nActual: ".var_export($actual, true)."\n");
            exit(1);
        }
    }

    require_once __DIR__.'/../Model/UserLockingModel.php';

    $tests = 0;

    $db = new FakeDatabase(array(user('ingrid Kuan', 'ingrid@asiarf.com')));
    $model = new \Kanboard\Plugin\EmailLogin\Model\UserLockingModel($db);
    $model->failedLogins['ingrid Kuan'] = 3;
    assertSameValue(true, $model->hasCaptcha('ingrid@asiarf.com'), 'Email login must expose the canonical user CAPTCHA requirement.');
    assertSameValue(array('getFailedLogin', 'ingrid Kuan'), $model->calls[0], 'Email CAPTCHA lookup must use the canonical username.');
    $tests++;

    $model->incrementFailedLogin('ingrid@asiarf.com');
    $model->isLocked('ingrid@asiarf.com');
    $model->lock('ingrid@asiarf.com', 20);
    $model->resetFailedLogin('ingrid@asiarf.com');
    assertSameValue(
        array(
            array('getFailedLogin', 'ingrid Kuan'),
            array('incrementFailedLogin', 'ingrid Kuan'),
            array('isLocked', 'ingrid Kuan'),
            array('lock', 'ingrid Kuan', 20),
            array('resetFailedLogin', 'ingrid Kuan'),
        ),
        $model->calls,
        'Every brute-force operation must use the same canonical username.'
    );
    assertSameValue(2, $db->queryCount, 'Email resolution must be cached for the request.');
    $tests++;

    $db = new FakeDatabase(array(
        user('login@example.com', 'owner@example.com'),
        user('other-user', 'login@example.com'),
    ));
    $model = new \Kanboard\Plugin\EmailLogin\Model\UserLockingModel($db);
    $model->incrementFailedLogin('login@example.com');
    assertSameValue(array('incrementFailedLogin', 'login@example.com'), $model->calls[0], 'Username authentication must take precedence over email authentication.');
    assertSameValue(1, $db->queryCount, 'A matching username must not perform an email lookup.');
    $tests++;

    $db = new FakeDatabase(array(
        user('login@example.com', 'disabled-owner@example.com', 0),
        user('active-user', 'login@example.com'),
    ));
    $model = new \Kanboard\Plugin\EmailLogin\Model\UserLockingModel($db);
    $model->incrementFailedLogin('login@example.com');
    assertSameValue(array('incrementFailedLogin', 'active-user'), $model->calls[0], 'An ineligible username must not shadow an eligible email account.');
    assertSameValue(2, $db->queryCount, 'An ineligible username must fall through to email lookup.');
    $tests++;

    $db = new FakeDatabase(array(
        user('inactive-user', 'inactive@example.com', 0),
        user('ldap-user', 'ldap@example.com', 1, 1),
        user('form-disabled-user', 'disabled@example.com', 1, 0, 1),
    ));
    $model = new \Kanboard\Plugin\EmailLogin\Model\UserLockingModel($db);
    $model->incrementFailedLogin('inactive@example.com');
    $model->incrementFailedLogin('ldap@example.com');
    $model->incrementFailedLogin('disabled@example.com');
    assertSameValue(
        array(
            array('incrementFailedLogin', 'inactive@example.com'),
            array('incrementFailedLogin', 'ldap@example.com'),
            array('incrementFailedLogin', 'disabled@example.com'),
        ),
        $model->calls,
        'Email resolution must use the same account eligibility rules as email authentication.'
    );
    $tests++;

    $db = new FakeDatabase(array(user('ingrid Kuan', 'ingrid@asiarf.com')));
    $model = new \Kanboard\Plugin\EmailLogin\Model\UserLockingModel($db);
    $model->incrementFailedLogin('plain-user');
    assertSameValue(array('incrementFailedLogin', 'plain-user'), $model->calls[0], 'Plain usernames must retain core behavior.');
    assertSameValue(0, $db->queryCount, 'Plain usernames must not add database lookups.');
    $tests++;

    echo $tests." user locking behavior tests passed\n";
}
