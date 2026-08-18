# Kanboard Plugin: EmailLogin

Allow users to log in and reset passwords using either **username** or **email address**. Also enforces email uniqueness across all users.

## Features

| Feature | Description |
|---------|-------------|
| Email Login | Users can enter their email in the login form instead of username |
| Email Password Reset | The "Forgot Password" form accepts email in addition to username |
| Unique Email Enforcement | Prevents duplicate email addresses when creating or editing users |

## How It Works

This plugin uses Kanboard's native extension points — no core files are modified.

- **Login**: Registers an additional `PasswordAuthenticationProvider`. The original `DatabaseAuth` runs first (username lookup). If it fails and the input contains `@`, the plugin's `EmailDatabaseAuth` provider tries an email-based lookup.
- **Password Reset**: Overrides `PasswordResetModel` via DI container. The `create()` method first tries username lookup (original behavior), then falls back to email lookup.
- **Email Uniqueness**: Overrides `UserValidator` to add a `Unique` constraint on the `email` field. Only applies when email is non-empty, so bot/service accounts without email are unaffected.

## Requirements

- Kanboard >= 1.2.20
- PHP >= 7.4

## Installation

### Via Git (recommended for self-hosted)

```bash
cd /path/to/kanboard/plugins
git clone https://github.com/mxp-tw/kanboard-plugin-email-login.git EmailLogin
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
- Does **not** affect the JSON-RPC API authentication
- Safe to use alongside all standard Kanboard plugins

## File Structure

```
EmailLogin/
├── Plugin.php                      # Plugin entry point
├── Auth/
│   └── EmailDatabaseAuth.php       # Email-based auth provider
├── Model/
│   └── PasswordResetModel.php      # Extended password reset (email fallback)
├── Validator/
│   └── UserValidator.php           # Email uniqueness enforcement
└── README.md
```

## License

MIT
