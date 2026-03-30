# 🔒 Security Policy

## Supported Versions

The following versions of Akademiq are currently supported with security updates:

| Version | Supported |
|---|---|
| 1.x (latest) | ✅ Yes |
| < 1.0 | ❌ No |

---

## 🛡️ Security Measures

Akademiq implements the following security practices:

- **Session Management** — user sessions are validated on every page load via `auth.php`
- **Input Sanitization** — all user inputs are sanitized using `htmlspecialchars()` to prevent XSS attacks
- **Per-user Data Isolation** — each user's data is stored in a separate folder, inaccessible to other users
- **No Direct SQL** — the app uses JSON file storage, eliminating SQL injection risks entirely
- **Redirect After Auth** — unauthenticated users are immediately redirected to the login page

---

## ⚠️ Known Limitations

Since this project uses **file-based JSON storage** instead of a database, please be aware of:

- JSON files should **never be publicly accessible** — ensure your web server blocks direct access to the `/data` folder
- This app is intended for **personal/local use** or **trusted environments**
- Not recommended for large-scale production deployment without additional hardening

### Recommended: protect `/data` folder via `.htaccess`

Add this file at `data/.htaccess`:

```apache
Order deny,allow
Deny from all
```

This prevents anyone from directly accessing JSON files via browser.

---

## 🐛 Reporting a Vulnerability

If you discover a security vulnerability in this project, please do the following:

1. **Do NOT open a public issue** for security vulnerabilities
2. Send a private report via email to: `warunkantideadline@gmail.com`
3. Include:
   - A clear description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (optional)

We will respond within **3 business days** and aim to release a patch within **7 days**
of confirmation.

---

## 🙏 Responsible Disclosure

We appreciate responsible disclosure and will credit researchers who report
valid vulnerabilities in the project's release notes.

---

> Security is not a feature — it's a foundation. 🔐
