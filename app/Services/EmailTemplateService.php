<?php

namespace App\Services;

use App\Config\Config;

final class EmailTemplateService
{
    public static function getAppName(): string
    {
        return Config::APP_NAME();
    }

    public static function getAppUrl(): string
    {
        return Config::BASE_URL();
    }

    public static function getSupportEmail(): string
    {
        return Config::MAIL_FROM();
    }

    public static function getHeader(): string
    {
        $appName = self::getAppName();
        return "
        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px 20px; text-align: center; border-radius: 8px 8px 0 0;'>
            <h1 style='color: white; margin: 0; font-family: Arial, sans-serif; font-size: 28px; font-weight: 300;'>
                {$appName}
            </h1>
            <p style='color: rgba(255,255,255,0.9); margin: 8px 0 0 0; font-family: Arial, sans-serif; font-size: 14px;'>
                Saudi Arabia E-Invoicing Solution
            </p>
        </div>";
    }

    public static function getFooter(): string
    {
        $appName = self::getAppName();
        $supportEmail = self::getSupportEmail();
        $appUrl = self::getAppUrl();

        return "
        <div style='background: #f8f9fa; padding: 30px 20px; text-align: center; border-radius: 0 0 8px 8px; border-top: 1px solid #e9ecef;'>
            <p style='color: #6c757d; margin: 0 0 15px 0; font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5;'>
                This email was sent by <strong>{$appName}</strong><br>
                If you have any questions, please contact us at <a href='mailto:{$supportEmail}' style='color: #667eea; text-decoration: none;'>{$supportEmail}</a>
            </p>
            <div style='margin-top: 20px; padding-top: 20px; border-top: 1px solid #e9ecef;'>
                <p style='color: #adb5bd; margin: 0; font-family: Arial, sans-serif; font-size: 12px;'>
                    © " . date('Y') . " {$appName}. All rights reserved.<br>
                    <a href='{$appUrl}' style='color: #adb5bd; text-decoration: none;'>{$appUrl}</a>
                </p>
            </div>
        </div>";
    }

    public static function getButton(string $text, string $url, string $color = '#667eea'): string
    {
        return "
        <div style='text-align: center; margin: 30px 0;'>
            <a href='{$url}' style='
                background: {$color};
                color: white;
                padding: 15px 30px;
                text-decoration: none;
                border-radius: 6px;
                display: inline-block;
                font-family: Arial, sans-serif;
                font-size: 16px;
                font-weight: 600;
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
                transition: all 0.3s ease;
            '>
                {$text}
            </a>
        </div>";
    }

    public static function getConfirmationEmail(string $confirmationLink): string
    {
        $appName = self::getAppName();

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Confirm Your Account - {$appName}</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f8f9fa; font-family: Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 40px auto; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); overflow: hidden;'>
                " . self::getHeader() . "
                
                <div style='padding: 40px 30px; text-align: center;'>
                    <div style='margin-bottom: 30px;'>
                        <div style='width: 80px; height: 80px; background: #e3f2fd; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;'>
                            <span style='font-size: 40px; color: #667eea;'>✓</span>
                        </div>
                        <h2 style='color: #2c3e50; margin: 0 0 15px 0; font-size: 24px; font-weight: 600;'>
                            Welcome to {$appName}!
                        </h2>
                        <p style='color: #6c757d; margin: 0; font-size: 16px; line-height: 1.6;'>
                            Thank you for registering with us. To complete your account setup and start using our ZATCA e-invoicing services, please confirm your email address.
                        </p>
                    </div>
                    
                    " . self::getButton('Confirm Your Account', $confirmationLink) . "
                    
                    <div style='margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #667eea;'>
                        <p style='color: #495057; margin: 0; font-size: 14px; line-height: 1.5;'>
                            <strong>What's next?</strong><br>
                            Once confirmed, you'll be able to access our comprehensive ZATCA e-invoicing platform, generate compliant invoices, and manage your business transactions seamlessly.
                        </p>
                    </div>
                    
                    <div style='margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 6px; border: 1px solid #ffeaa7;'>
                        <p style='color: #856404; margin: 0; font-size: 13px; line-height: 1.4;'>
                            <strong>Security Note:</strong> This confirmation link will expire in 24 hours. If you didn't create an account with us, please ignore this email.
                        </p>
                    </div>
                </div>
                
                " . self::getFooter() . "
            </div>
        </body>
        </html>";
    }

    public static function getResendConfirmationEmail(string $confirmationLink): string
    {
        $appName = self::getAppName();

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Resend Confirmation - {$appName}</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f8f9fa; font-family: Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 40px auto; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); overflow: hidden;'>
                " . self::getHeader() . "
                
                <div style='padding: 40px 30px; text-align: center;'>
                    <div style='margin-bottom: 30px;'>
                        <div style='width: 80px; height: 80px; background: #fff3cd; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;'>
                            <span style='font-size: 40px; color: #f39c12;'>↻</span>
                        </div>
                        <h2 style='color: #2c3e50; margin: 0 0 15px 0; font-size: 24px; font-weight: 600;'>
                            Resend Confirmation
                        </h2>
                        <p style='color: #6c757d; margin: 0; font-size: 16px; line-height: 1.6;'>
                            We've received your request to resend the account confirmation email. Click the button below to confirm your email address and activate your {$appName} account.
                        </p>
                    </div>
                    
                    " . self::getButton('Confirm Your Account', $confirmationLink) . "
                    
                    <div style='margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #f39c12;'>
                        <p style='color: #495057; margin: 0; font-size: 14px; line-height: 1.5;'>
                            <strong>Important:</strong> This is a new confirmation link. Any previous confirmation links have been invalidated for security purposes.
                        </p>
                    </div>
                    
                    <div style='margin-top: 30px; padding: 15px; background: #d1ecf1; border-radius: 6px; border: 1px solid #bee5eb;'>
                        <p style='color: #0c5460; margin: 0; font-size: 13px; line-height: 1.4;'>
                            <strong>Need Help?</strong> If you continue to have issues, please contact our support team at " . self::getSupportEmail() . "
                        </p>
                    </div>
                </div>
                
                " . self::getFooter() . "
            </div>
        </body>
        </html>";
    }

    public static function getWelcomeEmail(string $userEmail): string
    {
        $appName = self::getAppName();
        $appUrl = self::getAppUrl();

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Welcome to {$appName}</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f8f9fa; font-family: Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 40px auto; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); overflow: hidden;'>
                " . self::getHeader() . "
                
                <div style='padding: 40px 30px; text-align: center;'>
                    <div style='margin-bottom: 30px;'>
                        <div style='width: 80px; height: 80px; background: #d4edda; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;'>
                            <span style='font-size: 40px; color: #28a745;'>🎉</span>
                        </div>
                        <h2 style='color: #2c3e50; margin: 0 0 15px 0; font-size: 24px; font-weight: 600;'>
                            Account Confirmed Successfully!
                        </h2>
                        <p style='color: #6c757d; margin: 0; font-size: 16px; line-height: 1.6;'>
                            Welcome to {$appName}! Your account has been successfully confirmed and you're now ready to start using our ZATCA e-invoicing platform.
                        </p>
                    </div>
                    
                    " . self::getButton('Access Your Dashboard', $appUrl . '/dashboard') . "
                    
                    <div style='margin-top: 40px; text-align: left;'>
                        <h3 style='color: #2c3e50; margin: 0 0 20px 0; font-size: 18px; font-weight: 600;'>
                            What you can do now:
                        </h3>
                        <ul style='color: #495057; margin: 0; padding-left: 20px; line-height: 1.8;'>
                            <li>Generate ZATCA-compliant invoices and credit notes</li>
                            <li>Manage your business transactions securely</li>
                            <li>Access real-time compliance reporting</li>
                            <li>Integrate with your existing business systems</li>
                            <li>Get 24/7 support from our expert team</li>
                        </ul>
                    </div>
                    
                    <div style='margin-top: 40px; padding: 20px; background: #e3f2fd; border-radius: 6px; border-left: 4px solid #2196f3;'>
                        <p style='color: #1565c0; margin: 0; font-size: 14px; line-height: 1.5;'>
                            <strong>Getting Started:</strong> Check out our comprehensive documentation and tutorials to make the most of your {$appName} experience. Our support team is always here to help!
                        </p>
                    </div>
                </div>
                
                " . self::getFooter() . "
            </div>
        </body>
        </html>";
    }
}
