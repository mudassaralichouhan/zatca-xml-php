<?php

namespace App\Controllers;

use App\Database\Database;
use App\Exceptions\BadRequestException;
use App\Exceptions\UnauthorizedException;
use Illuminate\Database\Capsule\Manager as DB;
use App\Services\CryptoService;
use App\Services\HttpService;
use App\Services\MailService;
use App\Services\ValidatorService;
use App\Services\ZatcaModeHeaderService;
use Firebase\JWT\JWT;
use App\Config\Config;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Services\ApiResponse;
use App\Services\EmailTemplateService;
use App\Helpers\Helper;

class AuthController extends BaseController
{
    public function register(Request $request): Response
    {
        $mode = ZatcaModeHeaderService::checkZatcaModeHeader($request);

        $data = ValidatorService::load($request->getContent(), 'auth.register.json');

        $email = $data->email;
        $pass = $data->password;
        Database::get($mode);

        // check duplicate
        if (DB::table('users')->where('email', $email)->exists()) {
            // Return generic success message to prevent account enumeration
            return ApiResponse::success([
                'message' => 'If an account with this email exists, you will receive a confirmation email.',
                ...['debug' => Helper::isProduction() ? null : 'If an account with this email exists.'],
            ]);
        }

        DB::connection()->beginTransaction();
        $now = date('Y-m-d H:i:s');
        $userId = DB::table('users')
            ->insertGetId([
                'email' => $email,
                'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
                'confirmation_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $data_to_encrypt = json_encode(['email' => $email, 'id' => $userId, 'zatca_mode' => $mode, 'timestamp' => time()]);
        $encrypted_data = CryptoService::encrypt($data_to_encrypt);
        DB::table('users')
            ->where('id', $userId)
            ->update([
                'confirmation_token' => $encrypted_data,
            ]);

        MailService::init();
        $confirmationLink = EmailTemplateService::getAppUrl() . "/api/v1/auth/confirm?token=" . $encrypted_data;

        $emailBody = EmailTemplateService::getConfirmationEmail($confirmationLink);
        $subject = "Confirm Your Account - " . EmailTemplateService::getAppName();

        MailService::sendMail($email, $subject, $emailBody);

        $user = DB::table('users')
            ->select([
                'id',
                'email',
            ])
            ->where('id', $userId)
            ->first();

        DB::connection()->commit();

        return ApiResponse::success([
            'message' => 'If an account with this email exists, you will receive a confirmation email.',
            'user' => $user,
            ...['debug' => Helper::isProduction() ? null : 'If an account with this email exists.'],
        ]);
    }

    public function resendMail(Request $request): Response
    {
        $mode = ZatcaModeHeaderService::checkZatcaModeHeader($request);

        $data = ValidatorService::load($request->getContent(), 'auth.resend-mail.json');

        $email = strtolower(trim($data->email));

        Database::get($mode);

        // check if user is already confirmed
        if (DB::table('users')->where('email', $email)->where('is_confirmed', 1)->exists()) {
            return ApiResponse::success([
                'message' => 'If an account with this email exists, you will receive a confirmation email.'
            ]);
        }

        $user = DB::table('users')
            ->select(['email', 'id'])
            ->where([
                'email' => $email,
            ])
            ->first();

        if (!$user) {
            return ApiResponse::success([
                'message' => 'If an account with this email exists, you will receive a confirmation email.'
            ]);
        }

        DB::connection()->beginTransaction();

        $data_to_encrypt = json_encode(['email' => $user->email, 'id' => $user->id, 'zatca_mode' =>  $mode, 'timestamp' => time()]);
        $encrypted_data = CryptoService::encrypt($data_to_encrypt);
        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'confirmation_token' => $encrypted_data,
            ]);

        MailService::init();
        $confirmationLink = EmailTemplateService::getAppUrl() . "/api/v1/auth/confirm?token=" . $encrypted_data;

        $emailBody = EmailTemplateService::getResendConfirmationEmail($confirmationLink);
        $subject = "Resend Confirmation - " . EmailTemplateService::getAppName();

        MailService::sendMail($email, $subject, $emailBody);

        DB::connection()->commit();
        return ApiResponse::success([
            'message' => 'If an account with this email exists, you will receive a confirmation email.',
            ...['debug' => Helper::isProduction() ? null : 'If an account with this email exists.'],
        ]);
    }

    public function confirm(Request $request): Response
    {
        $token = $request->query->get('token') ?? throw new BadRequestException(['token' => 'Token is required. in query parameters.']);

        // Decrypt the token to retrieve user data
        try {
            $decrypted_json = CryptoService::decrypt($token);
            $decrypted_data = json_decode($decrypted_json);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new BadRequestException([
                    'token' => 'The confirmation link has expired.',
                    'exception' => 'Invalid token format: ' . json_last_error_msg(),
                ]);
            }

            if (!$decrypted_data || !is_object($decrypted_data)) {
                throw new BadRequestException([
                    'token' => 'The confirmation link has expired.',
                    'exception' => 'Invalid token data structure',
                ]);
            }
        } catch (\Exception $e) {
            throw new BadRequestException([
                'token' => 'The confirmation link has expired.',
                'exception' => $e->getMessage(),
            ]);
        }

        // Extract email, id, and timestamp from decrypted data
        $email = $decrypted_data->email ?? null;
        $id = $decrypted_data->id ?? null;
        $timestamp = $decrypted_data->timestamp ?? null;
        $mode = $decrypted_data->zatca_mode ?? null;

        if (!$email || !$id || !$timestamp || !$mode) {
            throw new BadRequestException([
                'token' => 'The confirmation link has expired.',
                'exception' => 'Missing required token data',
            ]);
        }

        // Check if the token is older than 1 day
        if ($timestamp < strtotime('-1 day')) {
            throw new BadRequestException(['message' => 'The confirmation link has expired.']);
        }

        ZatcaModeHeaderService::mapMode($mode);
        Database::get($mode);

        $user = DB::table('users')
            ->where('email', $email)
            ->where('id', $id)
            ->first();

        if (!$user) {
            throw new BadRequestException(['message' => 'Invalid or expired confirmation link.']);
        }

        if ($user->is_confirmed) {
            throw new BadRequestException(['message' => 'Your account is already confirmed.']);
        }

        $now = date('Y-m-d H:i:s');
        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'is_confirmed' => 1,
                'confirmation_token' => null,
                'updated_at' => $now,
            ]);

        // Send welcome email
        try {
            MailService::init();
            $welcomeEmailBody = EmailTemplateService::getWelcomeEmail($email);
            $welcomeSubject = "Welcome to " . EmailTemplateService::getAppName();
            MailService::sendMail($email, $welcomeSubject, $welcomeEmailBody);
        } catch (\Throwable $e) {
        }

        $starts = new \DateTime();
        $ends = (clone $starts)->add(new \DateInterval('P30D'));
        // DB::table('subscriptions')->insert([
        //     'user_id' => $user->id,
        //     'starts_at' => $starts->format('Y-m-d H:i:s'),
        //     'ends_at' => $ends->format('Y-m-d H:i:s'),
        //     'created_at' => $now,
        //     'updated_at' => $now,
        // ]);

        return ApiResponse::success([
            'message' => 'Account confirmed',
            'subscription' => [
                'starts_at' => $starts->format(\DateTime::ATOM),
                'ends_at' => $ends->format(\DateTime::ATOM),
            ],
        ]);
    }

    public function login(Request $request): Response
    {
        $mode = ZatcaModeHeaderService::checkZatcaModeHeader($request);

        $data = ValidatorService::load($request->getContent(), 'auth.login.json');

        $email = $data->email;
        $pass = $data->password;

        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        Database::get($mode);

        $user = DB::table('users')
            ->select(['id', 'email', 'password_hash', 'is_confirmed', 'confirmation_token'])
            ->where('email', $email)
            ->where('is_confirmed', 1)
            ->whereNull('confirmation_token')
            ->where('active', 1)
            ->first();

        if (!$user || !password_verify($pass, $user->password_hash)) {
            throw new UnauthorizedException([
                'email' => 'Invalid credentials',
            ]);
        }

        if (!$user->is_confirmed) {
            throw new UnauthorizedException([
                'email' => 'Invalid credentials',
            ]);
        }

        $now = time();
        $exp = $now + ((int)$_ENV['JWT_EXPIRY_SECONDS']);
        $payload = [
            'iat' => $now,
            'exp' => $exp,
            'jti' => bin2hex(random_bytes(16)),
            'id' => $user->id,
            'email' => $user->email,
            'zatca_mode' => $mode,
        ];

        try {
            $jwt = JWT::encode($payload, Config::JWT_SECRET(), 'HS256');
        } catch (\Throwable $e) {
            throw new BadRequestException([
                'message' => 'Could not generate access token',
                'error' => $e->getMessage(),
                'email' => $email,
            ]);
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        try {
            @[$browser, $version, $os] = \donatj\UserAgent\parse_user_agent($userAgent);
        } catch (\Throwable $e) {
            $browser = $os = 'unknown';
        }

        if (HttpService::isLocalIp($ipAddress)) {
            $locationData = ['error' => 'Location lookup not allow for local IP address'];
        } else {
            try {
                $geoJson = file_get_contents("http://ip-api.com/json/{$ipAddress}");
                $locationData = json_decode($geoJson, true);
            } catch (\Throwable $e) {
                $locationData = ['error' => 'Location lookup failed'];
            }
        }

        // Log login details
        DB::table('user_logins')->insert([
            'user_id' => $user->id,
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
            'browser' => $browser,
            'os' => $os,
            'device' => $locationData['mobile'] ?? null,
            'city' => $locationData['city'] ?? null,
            'region' => $locationData['regionName'] ?? null,
            'country' => $locationData['country'] ?? null,
            'zip' => $locationData['zip'] ?? null,
            'lat' => $locationData['lat'] ?? null,
            'lon' => $locationData['lon'] ?? null,
            'timezone' => $locationData['timezone'] ?? null,
            'isp' => $locationData['isp'] ?? null,
            'org' => $locationData['org'] ?? null,
            'as_info' => $locationData['as'] ?? null,
            'login_at' => date('Y-m-d H:i:s'),
        ]);


        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'last_login_at' => date('Y-m-d H:i:s'),
            ]);

        return ApiResponse::success([
            'access_token' => $jwt,
            'token_type' => 'Bearer',
            'expires_in' => $exp - $now,
        ]);
    }
}
