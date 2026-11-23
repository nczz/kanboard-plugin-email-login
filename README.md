# Kanboard Plugin: EmailLogin

Allow users to log in and reset passwords using either **username** or **email address**. Enforces email uniqueness and requires email on user creation/modification.

## Features

| Feature | Description |
|---------|-------------|
| Email Login | Users can enter their email in the login form instead of username |
| Email Password Reset | The "Forgot Password" form accepts email in addition to username |
| Unique Email | Prevents duplicate email addresses when creating or editing users |
| Required Email | Email is mandatory on user creation and modification |
| Brute-force Safe | Email login correctly triggers captcha and account lockout |
| i18n | Traditional Chinese (zh_TW) included |

## How It Works

This plugin uses Kanboard's native extension points — no core files are modified.

### Login
Registers an additional `PasswordAuthenticationProvider`. The original `DatabaseAuth` runs first (username lookup). If it fails and the input contains `@`, the plugin's `EmailDatabaseAuth` provider tries email-based lookup.

### Password Reset
Overrides the password reset form and submits it to the plugin controller. The controller resolves the submitted identifier as a username first, then as an active user's email address. It creates the token with the canonical username and sends the message to the same resolved user, so the lookup and recipient cannot diverge.

### Email Validation
Overrides `UserValidator` to add:
- `Required('email')` on creation and modification
- `Unique('email')` on all operations (including API)

### Brute-force Protection
Overrides `UserLockingModel` so every CAPTCHA, lock, increment, and reset operation resolves an email address to the same canonical username used by authentication. Core Kanboard then renders CAPTCHA and applies lockout counters consistently without duplicate failure-event handling.

### Template Overrides
- `password_reset/create` — accepts username or email and submits to the email-aware controller
- `user_creation/show` — email field marked `required`
- `user_modification/show` — email field marked `required`

## Requirements

- Kanboard >= 1.2.20
- PHP >= 7.4

## Installation

### Via Git (recommended for self-hosted)

```bash
cd /path/to/kanboard/plugins
git clone https://github.com/nczz/kanboard-plugin-email-login.git EmailLogin
```

The directory **must** be named `EmailLogin` to match the namespace.

### Update

```bash
cd /path/to/kanboard/plugins/EmailLogin
git pull
```

## Uninstallation

Remove or rename the plugin directory:

```bash
cd /path/to/kanboard/plugins
rm -rf EmailLogin
```

No database changes are made by this plugin — removal is clean.

## Compatibility

- Does **not** modify any core Kanboard files
- Does **not** conflict with LDAP, SAML, or other authentication plugins
- Does **not** affect the JSON-RPC API authentication (except adding email uniqueness)
- Safe to use alongside all standard Kanboard plugins

## File Structure

```
EmailLogin/
├── Plugin.php                          # Plugin entry point
├── Auth/
│   └── EmailDatabaseAuth.php           # Email-based auth provider
├── Controller/
│   └── PasswordResetController.php     # Email-aware password reset delivery
├── Model/
│   ├── PasswordResetModel.php          # Extended password reset lookup
│   └── UserLockingModel.php            # Canonical brute-force tracking for email login
├── Validator/
│   └── UserValidator.php               # Email required + unique enforcement
├── Template/
│   ├── password_reset/
│   │   └── create.php                  # Username/email password reset form
│   ├── user_creation/
│   │   └── show.php                    # Email required on create form
│   └── user_modification/
│       └── show.php                    # Email required on edit form
├── Test/
│   ├── PasswordResetControllerTest.php # Password reset behavior regression tests
│   └── UserLockingModelTest.php         # Email CAPTCHA and lockout regression tests
├── Locale/
│   └── zh_TW/
│       └── translations.php            # Traditional Chinese
├── LICENSE
└── README.md
```

## Security Considerations

- **Email enumeration**: The password reset form does not reveal whether an email exists (always redirects to login regardless of result)
- **Brute-force**: Email login failures are correctly attributed to the real username for lockout counting
- **Disabled accounts**: Email-based password reset requires `is_active=1` (prevents probing disabled accounts)
- **Timing**: The `@` check in EmailDatabaseAuth introduces a minor timing difference, but this only reveals input format (not account existence) and is consistent with Kanboard's core security model

## Changelog

### 1.1.2

- Fix email login deadlock when a canonical username reaches the CAPTCHA threshold.
- Centralize email-to-username resolution in `UserLockingModel` for CAPTCHA display, lock checks, and failure counters.
- Preserve username-first authentication precedence and prevent duplicate failed-login increments.

## License

MIT
