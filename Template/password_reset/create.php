<div class="form-login">
    <h2><?= t('Password Reset') ?></h2>
    <form method="post" action="<?= $this->url->href('PasswordResetController', 'save', array('plugin' => 'EmailLogin')) ?>">
        <?= $this->form->csrf() ?>

        <?= $this->form->label(t('Username or email address'), 'username') ?>
        <?= $this->form->text('username', $values, $errors, array('autofocus', 'required', 'autocomplete="username"')) ?>
        <p class="form-help"><?= t('Enter your username or the email address configured in your profile.') ?></p>

        <?= $this->form->label(t('Enter the text below'), 'captcha') ?>
        <img src="<?= $this->url->href('CaptchaController', 'image') ?>" alt="Captcha">
        <?= $this->form->text('captcha', array(), $errors, array('required')) ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-blue"><?= t('Change Password') ?></button>
        </div>
    </form>
</div>
