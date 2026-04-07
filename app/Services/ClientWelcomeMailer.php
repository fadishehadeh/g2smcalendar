<?php

declare(strict_types=1);

namespace App\Services;

final class ClientWelcomeMailer
{
    public function __construct(private array $config)
    {
    }

    public function send(string $email, string $name, string $companyName, string $username, string $password): void
    {
        $appUrl = rtrim((string) ($this->config['app']['url'] ?? ''), '/');
        $loginUrl = $appUrl . '/index.php?route=login';
        $mailer = $this->config['app']['mailer'] ?? [];
        $supportEmail = (string) ($mailer['reply_to_email'] ?: ($mailer['from_email'] ?? 'support@g2.local'));
        $supportName = (string) ($mailer['reply_to_name'] ?: ($mailer['from_name'] ?? 'G2 Social Calendar'));

        $subject = 'Your G2 Social Calendar Access Details';
        $textBody = "Welcome to G2 Social Calendar for {$companyName}.\n\n"
            . "Your client portal is ready.\n\n"
            . "Login URL: {$loginUrl}\n"
            . "Username: {$username}\n"
            . "Password: {$password}\n\n"
            . "Inside the workspace you can review artwork, approve posts, leave comments, and track client activity.\n\n"
            . "If you have any trouble signing in, contact {$supportName} at {$supportEmail}.";

        $escapedName = htmlspecialchars($name !== '' ? $name : $companyName, ENT_QUOTES, 'UTF-8');
        $escapedCompany = htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8');
        $escapedUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $escapedPassword = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
        $escapedLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');
        $escapedSupportEmail = htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8');
        $escapedSupportName = htmlspecialchars($supportName, ENT_QUOTES, 'UTF-8');

        $htmlBody = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#f4f7fb;padding:32px 16px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #e6e8ef;border-radius:24px;overflow:hidden;">'
            . '<tr><td style="padding:32px 36px;background:linear-gradient(135deg,#fff7f7 0%,#ffffff 60%,#fff3f2 100%);border-bottom:1px solid #eef2f7;">'
            . '<div style="font-family:Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:#e13b36;">G2 Social Calendar</div>'
            . '<h1 style="margin:14px 0 12px;font-family:Arial,sans-serif;font-size:34px;line-height:1.1;color:#0f172a;">Your client workspace is ready</h1>'
            . '<p style="margin:0;font-family:Arial,sans-serif;font-size:16px;line-height:1.7;color:#475569;">Hello ' . $escapedName . ', your access for <strong>' . $escapedCompany . '</strong> has been created. You can now review content, approve posts, and leave feedback from one shared workspace.</p>'
            . '</td></tr>'
            . '<tr><td style="padding:28px 36px 10px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#f8fafc;border:1px solid #e2e8f0;border-radius:20px;">'
            . '<tr><td style="padding:24px 24px 10px;font-family:Arial,sans-serif;font-size:22px;font-weight:700;color:#0f172a;">Access Details</td></tr>'
            . '<tr><td style="padding:0 24px 24px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;">'
            . '<tr><td style="padding:12px 0;border-bottom:1px solid #e2e8f0;font-family:Arial,sans-serif;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;">Login URL</td></tr>'
            . '<tr><td style="padding:10px 0 18px;font-family:Arial,sans-serif;font-size:16px;line-height:1.6;color:#0f172a;"><a href="' . $escapedLoginUrl . '" style="color:#e13b36;text-decoration:none;">' . $escapedLoginUrl . '</a></td></tr>'
            . '<tr><td style="padding:12px 0;border-bottom:1px solid #e2e8f0;font-family:Arial,sans-serif;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;">Username</td></tr>'
            . '<tr><td style="padding:10px 0 18px;font-family:Arial,sans-serif;font-size:18px;line-height:1.6;color:#0f172a;">' . $escapedUsername . '</td></tr>'
            . '<tr><td style="padding:12px 0;border-bottom:1px solid #e2e8f0;font-family:Arial,sans-serif;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;">Password</td></tr>'
            . '<tr><td style="padding:10px 0 0;font-family:Arial,sans-serif;font-size:18px;line-height:1.6;color:#0f172a;">' . $escapedPassword . '</td></tr>'
            . '</table>'
            . '</td></tr>'
            . '</table>'
            . '</td></tr>'
            . '<tr><td style="padding:6px 36px 0;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;">'
            . '<tr>'
            . '<td style="width:50%;padding:16px 14px 16px 0;vertical-align:top;">'
            . '<div style="padding:22px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:18px;">'
            . '<div style="font-family:Arial,sans-serif;font-size:18px;font-weight:700;color:#0f172a;margin-bottom:10px;">What you can do</div>'
            . '<div style="font-family:Arial,sans-serif;font-size:15px;line-height:1.7;color:#475569;">Review upcoming content, approve or reject posts, leave feedback, and keep your team aligned from one place.</div>'
            . '</div>'
            . '</td>'
            . '<td style="width:50%;padding:16px 0 16px 14px;vertical-align:top;">'
            . '<div style="padding:22px;background:#fff7ed;border:1px solid #fed7aa;border-radius:18px;">'
            . '<div style="font-family:Arial,sans-serif;font-size:18px;font-weight:700;color:#7c2d12;margin-bottom:10px;">Need help?</div>'
            . '<div style="font-family:Arial,sans-serif;font-size:15px;line-height:1.7;color:#9a3412;">If you have issues accessing the portal, contact ' . $escapedSupportName . ' at <a href="mailto:' . $escapedSupportEmail . '" style="color:#c2410c;text-decoration:none;">' . $escapedSupportEmail . '</a>.</div>'
            . '</div>'
            . '</td>'
            . '</tr>'
            . '</table>'
            . '</td></tr>'
            . '<tr><td style="padding:14px 36px 36px;">'
            . '<a href="' . $escapedLoginUrl . '" style="display:inline-block;padding:14px 24px;background:#e13b36;border-radius:999px;color:#ffffff;font-family:Arial,sans-serif;font-size:15px;font-weight:700;text-decoration:none;">Open G2 Social Calendar</a>'
            . '</td></tr>'
            . '</table>'
            . '</td></tr></table>';

        (new MailTransport($this->config))->send($email, $name, $subject, $textBody, $htmlBody);
    }

    public function sendPasswordReset(string $email, string $name, string $companyName, string $username, string $password): void
    {
        $appUrl = rtrim((string) ($this->config['app']['url'] ?? ''), '/');
        $loginUrl = $appUrl . '/index.php?route=login';
        $mailer = $this->config['app']['mailer'] ?? [];
        $supportEmail = (string) ($mailer['reply_to_email'] ?: ($mailer['from_email'] ?? 'support@g2.local'));
        $supportName = (string) ($mailer['reply_to_name'] ?: ($mailer['from_name'] ?? 'G2 Social Calendar'));

        $subject = 'Your G2 Social Calendar Password Reset';
        $textBody = "A password reset was requested for {$companyName}.\n\n"
            . "Login URL: {$loginUrl}\n"
            . "Username: {$username}\n"
            . "New temporary password: {$password}\n\n"
            . "Please sign in and change this password from your profile after logging in.\n\n"
            . "If you did not expect this email or need help, contact {$supportName} at {$supportEmail}.";

        $escapedName = htmlspecialchars($name !== '' ? $name : $companyName, ENT_QUOTES, 'UTF-8');
        $escapedCompany = htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8');
        $escapedUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $escapedPassword = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
        $escapedLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');
        $escapedSupportEmail = htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8');
        $escapedSupportName = htmlspecialchars($supportName, ENT_QUOTES, 'UTF-8');

        $htmlBody = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#f4f7fb;padding:32px 16px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #e6e8ef;border-radius:24px;overflow:hidden;">'
            . '<tr><td style="padding:32px 36px;background:linear-gradient(135deg,#fff7f7 0%,#ffffff 60%,#fff3f2 100%);border-bottom:1px solid #eef2f7;">'
            . '<div style="font-family:Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:#e13b36;">G2 Social Calendar</div>'
            . '<h1 style="margin:14px 0 12px;font-family:Arial,sans-serif;font-size:34px;line-height:1.1;color:#0f172a;">Your password has been reset</h1>'
            . '<p style="margin:0;font-family:Arial,sans-serif;font-size:16px;line-height:1.7;color:#475569;">Hello ' . $escapedName . ', a new temporary password has been issued for <strong>' . $escapedCompany . '</strong>. Use the details below to sign back in.</p>'
            . '</td></tr>'
            . '<tr><td style="padding:28px 36px 10px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#f8fafc;border:1px solid #e2e8f0;border-radius:20px;">'
            . '<tr><td style="padding:24px 24px 10px;font-family:Arial,sans-serif;font-size:22px;font-weight:700;color:#0f172a;">Reset Access Details</td></tr>'
            . '<tr><td style="padding:0 24px 24px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;">'
            . '<tr><td style="padding:12px 0;border-bottom:1px solid #e2e8f0;font-family:Arial,sans-serif;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;">Login URL</td></tr>'
            . '<tr><td style="padding:10px 0 18px;font-family:Arial,sans-serif;font-size:16px;line-height:1.6;color:#0f172a;"><a href="' . $escapedLoginUrl . '" style="color:#e13b36;text-decoration:none;">' . $escapedLoginUrl . '</a></td></tr>'
            . '<tr><td style="padding:12px 0;border-bottom:1px solid #e2e8f0;font-family:Arial,sans-serif;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;">Username</td></tr>'
            . '<tr><td style="padding:10px 0 18px;font-family:Arial,sans-serif;font-size:18px;line-height:1.6;color:#0f172a;">' . $escapedUsername . '</td></tr>'
            . '<tr><td style="padding:12px 0;border-bottom:1px solid #e2e8f0;font-family:Arial,sans-serif;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;">Temporary Password</td></tr>'
            . '<tr><td style="padding:10px 0 0;font-family:Arial,sans-serif;font-size:18px;line-height:1.6;color:#0f172a;">' . $escapedPassword . '</td></tr>'
            . '</table>'
            . '</td></tr>'
            . '</table>'
            . '</td></tr>'
            . '<tr><td style="padding:6px 36px 0;">'
            . '<div style="padding:22px;background:#fff7ed;border:1px solid #fed7aa;border-radius:18px;font-family:Arial,sans-serif;font-size:15px;line-height:1.7;color:#9a3412;">For security, sign in with this temporary password and then change it from your profile page. If you need help, contact ' . $escapedSupportName . ' at <a href="mailto:' . $escapedSupportEmail . '" style="color:#c2410c;text-decoration:none;">' . $escapedSupportEmail . '</a>.</div>'
            . '</td></tr>'
            . '<tr><td style="padding:14px 36px 36px;">'
            . '<a href="' . $escapedLoginUrl . '" style="display:inline-block;padding:14px 24px;background:#e13b36;border-radius:999px;color:#ffffff;font-family:Arial,sans-serif;font-size:15px;font-weight:700;text-decoration:none;">Open G2 Social Calendar</a>'
            . '</td></tr>'
            . '</table>'
            . '</td></tr></table>';

        (new MailTransport($this->config))->send($email, $name, $subject, $textBody, $htmlBody);
    }
}
