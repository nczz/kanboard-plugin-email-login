<?php

namespace Kanboard\Controller {
    class PasswordResetController
    {
        public $userModel;
        public $passwordResetModel;
        public $emailClient;
        public $template;
    }
}

namespace {
    function t($text)
    {
        return $text;
    }

    require_once __DIR__.'/../Controller/PasswordResetController.php';

    class TestablePasswordResetController extends \Kanboard\Plugin\EmailLogin\Controller\PasswordResetController
    {
        public function sendPasswordReset($identifier)
        {
            $this->sendEmail($identifier);
        }
    }

    class UserModelStub
    {
        public $usersByUsername = array();
        public $usersByEmail = array();
        public $emailLookups = 0;

        public function getByUsername($username)
        {
            return isset($this->usersByUsername[$username]) ? $this->usersByUsername[$username] : null;
        }

        public function getByEmail($email)
        {
            $this->emailLookups++;
            return isset($this->usersByEmail[$email]) ? $this->usersByEmail[$email] : null;
        }
    }

    class PasswordResetModelStub
    {
        public $createdFor = array();
        public $token = 'reset-token';

        public function create($username)
        {
            $this->createdFor[] = $username;
            return $this->token;
        }
    }

    class EmailClientStub
    {
        public $messages = array();

        public function send($email, $name, $subject, $html)
        {
            $this->messages[] = compact('email', 'name', 'subject', 'html');
        }
    }

    class TemplateStub
    {
        public function render($template, array $params)
        {
            return $template.':'.$params['token'];
        }
    }

    function user($active = 1, $email = 'chun@knockers.com.tw')
    {
        return array(
            'username' => 'chun',
            'name' => 'Chun',
            'email' => $email,
            'is_active' => $active,
        );
    }

    function controller(UserModelStub $userModel)
    {
        $controller = new TestablePasswordResetController();
        $controller->userModel = $userModel;
        $controller->passwordResetModel = new PasswordResetModelStub();
        $controller->emailClient = new EmailClientStub();
        $controller->template = new TemplateStub();
        return $controller;
    }

    function assertSameValue($expected, $actual, $message)
    {
        if ($expected !== $actual) {
            fwrite(STDERR, $message."\nExpected: ".var_export($expected, true)."\nActual: ".var_export($actual, true)."\n");
            exit(1);
        }
    }

    $tests = 0;

    $users = new UserModelStub();
    $users->usersByEmail['chun@knockers.com.tw'] = user();
    $controller = controller($users);
    $controller->sendPasswordReset('chun@knockers.com.tw');
    assertSameValue(array('chun'), $controller->passwordResetModel->createdFor, 'Email reset must create the token for the canonical username.');
    assertSameValue('chun@knockers.com.tw', $controller->emailClient->messages[0]['email'], 'Email reset must send to the resolved user email.');
    assertSameValue('password_reset/email:reset-token', $controller->emailClient->messages[0]['html'], 'Email reset must render the generated token.');
    $tests++;

    $users = new UserModelStub();
    $users->usersByUsername['chun'] = user();
    $controller = controller($users);
    $controller->sendPasswordReset('chun');
    assertSameValue(0, $users->emailLookups, 'Username reset must not perform an email lookup.');
    assertSameValue(array('chun'), $controller->passwordResetModel->createdFor, 'Username reset must preserve the core username path.');
    assertSameValue(1, count($controller->emailClient->messages), 'Username reset must send one email.');
    $tests++;

    $users = new UserModelStub();
    $users->usersByEmail['chun@knockers.com.tw'] = user(0);
    $controller = controller($users);
    $controller->sendPasswordReset('chun@knockers.com.tw');
    assertSameValue(array(), $controller->passwordResetModel->createdFor, 'Inactive users must not receive email-based reset tokens.');
    assertSameValue(array(), $controller->emailClient->messages, 'Inactive users must not receive reset emails.');
    $tests++;

    $users = new UserModelStub();
    $controller = controller($users);
    $controller->sendPasswordReset('missing@example.com');
    assertSameValue(array(), $controller->passwordResetModel->createdFor, 'Unknown emails must not create reset tokens.');
    assertSameValue(array(), $controller->emailClient->messages, 'Unknown emails must not send reset emails.');
    $tests++;

    $users = new UserModelStub();
    $users->usersByUsername['chun'] = user(1, '');
    $controller = controller($users);
    $controller->sendPasswordReset('chun');
    assertSameValue(array(), $controller->passwordResetModel->createdFor, 'Users without email addresses must not create reset tokens.');
    assertSameValue(array(), $controller->emailClient->messages, 'Users without email addresses must not send reset emails.');
    $tests++;

    echo $tests." password reset behavior tests passed\n";
}
