<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

final class TwilioSmsService
{
    public function __construct(
        private readonly ?string $accountSid,
        private readonly ?string $authToken,
        private readonly ?string $fromNumber,
        private readonly ?string $toNumber,
        private readonly Connection $connection,
    ) {
    }

    /**
     * Lit les identifiants Twilio depuis app_settings (cle / valeur), ex. TWILIO_SID, TWILIO_TOKEN, TWILIO_FROM.
     *
     * @return array{sid: string, token: string, from: string, to: string, sms_enabled: ?bool}
     */
    private function loadTwilioFromAppSettingsKeyValue(): array
    {
        $sid = '';
        $token = '';
        $from = '';
        $to = '';
        $smsFlag = null;

        try {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT cle, valeur FROM app_settings WHERE cle IN (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    'TWILIO_SID',
                    'TWILIO_TOKEN',
                    'TWILIO_FROM',
                    'TWILIO_TO',
                    'TWILIO_ACCOUNT_SID',
                    'TWILIO_AUTH_TOKEN',
                    'TWILIO_FROM_NUMBER',
                    'TWILIO_TO_NUMBER',
                    'SMS_ENABLED',
                    'sms_enabled',
                    // Notation « propriétés », ex. comme sur ta capture / Spring-style
                    'twilio.accountSid',
                    'twilio.authToken',
                    'twilio.phoneNumber',
                    'twilio.toNumber',
                ]
            );

            foreach ($rows as $row) {
                $cleRaw = trim((string) ($row['cle'] ?? ''));
                $key = strtoupper($cleRaw);
                $lcDots = strtolower($cleRaw);
                $val = trim((string) ($row['valeur'] ?? ''));

                if ($key === 'SMS_ENABLED' || $lcDots === 'sms_enabled') {
                    $smsFlag = in_array(strtolower($val), ['1', 'true', 'yes', 'on'], true);
                    continue;
                }

                if ('' === $val) {
                    continue;
                }

                // Clefs type twilio.accountSid / twilio.authToken / twilio.phoneNumber (insensible à la casse du nom)
                if ($lcDots === 'twilio.accountsid') {
                    $sid = $val;
                    continue;
                }
                if ($lcDots === 'twilio.authtoken') {
                    $token = $val;
                    continue;
                }
                if ($lcDots === 'twilio.phonenumber') {
                    $from = $val;
                    continue;
                }
                if ($lcDots === 'twilio.tonumber') {
                    $to = $val;
                    continue;
                }

                if (in_array($key, ['TWILIO_SID', 'TWILIO_ACCOUNT_SID'], true)) {
                    $sid = $val;
                } elseif (in_array($key, ['TWILIO_TOKEN', 'TWILIO_AUTH_TOKEN'], true)) {
                    $token = $val;
                } elseif (in_array($key, ['TWILIO_FROM', 'TWILIO_FROM_NUMBER'], true)) {
                    $from = $val;
                } elseif (in_array($key, ['TWILIO_TO', 'TWILIO_TO_NUMBER'], true)) {
                    $to = $val;
                }
            }
        } catch (\Throwable) {
            // Schéma app_settings différent (ancienne ligne id=1, etc.)
        }

        return [
            'sid' => $sid,
            'token' => $token,
            'from' => $from,
            'to' => $to,
            'sms_enabled' => $smsFlag,
        ];
    }

    /**
     * @return array{
     *   configured: bool,
     *   message: string,
     *   toNumber: ?string,
     *   accountSid?: string,
     *   authToken?: string,
     *   fromNumber?: string
     * }
     */
    private function resolveConfiguration(): array
    {
        $sid = trim((string) $this->accountSid);
        $token = trim((string) $this->authToken);
        $from = trim((string) $this->fromNumber);
        $to = trim((string) $this->toNumber);

        $kv = $this->loadTwilioFromAppSettingsKeyValue();

        if (false === $kv['sms_enabled']) {
            return [
                'configured' => false,
                'message' => 'SMS désactivé dans app_settings (SMS_ENABLED).',
                'toNumber' => null,
            ];
        }

        /*
         * Priorité : variables d'environnement (.env / .env.local) pour éviter qu'une ancienne ligne
         * app_settings (ex. TWILIO_SID = ACxx… de démo) n'écrase de vrais identifiants.
         * La base ne complète que les champs vides ou encore « exemple / placeholder ».
         */
        if (('' === $sid || $this->twilioSidLooksPlaceholder($sid)) && '' !== $kv['sid'] && !$this->twilioSidLooksPlaceholder($kv['sid'])) {
            $sid = $kv['sid'];
        }
        if (('' === $token || $this->twilioTokenLooksPlaceholder($token)) && '' !== $kv['token'] && !$this->twilioTokenLooksPlaceholder($kv['token'])) {
            $token = $kv['token'];
        }
        if (('' === $from || $this->twilioFromLooksPlaceholder($from)) && '' !== $kv['from'] && !$this->twilioFromLooksPlaceholder($kv['from'])) {
            $from = $kv['from'];
        }
        if ('' === $to && '' !== $kv['to']) {
            $to = $kv['to'];
        }

        try {
            /** @var array{sms_enabled?: int|string|null, phone_number?: string|null}|false $settings */
            $settings = $this->connection->fetchAssociative('SELECT sms_enabled, phone_number FROM app_settings WHERE id = 1');
            if (false !== $settings) {
                $smsEnabled = (int) ($settings['sms_enabled'] ?? 0) === 1;
                if (!$smsEnabled) {
                    return [
                        'configured' => false,
                        'message' => 'SMS désactivé dans app_settings.',
                        'toNumber' => null,
                    ];
                }

                $dbPhone = trim((string) ($settings['phone_number'] ?? ''));
                if ('' !== $dbPhone) {
                    $to = $dbPhone;
                }
            }
        } catch (\Throwable) {
            // Pas de colonnes id / sms_enabled : on garde la fusion .env + cle/valeur
        }

        $missing = [];
        if ('' === $sid) {
            $missing[] = 'TWILIO_ACCOUNT_SID ou app_settings.TWILIO_SID';
        }
        if ('' === $token) {
            $missing[] = 'TWILIO_AUTH_TOKEN ou app_settings.TWILIO_TOKEN';
        }
        if ('' === $from) {
            $missing[] = 'TWILIO_FROM_NUMBER ou app_settings.TWILIO_FROM';
        }
        if ('' === $to) {
            $missing[] = 'TWILIO_TO_NUMBER / app_settings.TWILIO_TO / phone_number';
        }

        if ([] !== $missing) {
            return [
                'configured' => false,
                'message' => 'Configuration Twilio incomplète: '.implode(', ', $missing),
                'toNumber' => null,
            ];
        }

        if ($this->credentialsLookLikePlaceholders($sid, $token, $from)) {
            return [
                'configured' => false,
                'message' => 'SMS non envoyé : identifiants Twilio factices (vérifiez .env ou les lignes TWILIO_SID / TWILIO_TOKEN dans app_settings).',
                'toNumber' => null,
            ];
        }

        return [
            'configured' => true,
            'message' => 'Twilio configuré.',
            'toNumber' => $to,
            'accountSid' => $sid,
            'authToken' => $token,
            'fromNumber' => $from,
        ];
    }

    private function twilioSidLooksPlaceholder(string $sid): bool
    {
        return 1 === preg_match('/^AC[xX]{32}$/', $sid);
    }

    private function twilioTokenLooksPlaceholder(string $token): bool
    {
        return 1 === preg_match('/^[xX]{32}$/', $token);
    }

    private function twilioFromLooksPlaceholder(string $from): bool
    {
        return 1 === preg_match('/^\+1Y+$/', $from) || str_contains($from, 'YYYY');
    }

    /**
     * Detect default placeholder values from .env.example / templates (invalid at Twilio API).
     */
    private function credentialsLookLikePlaceholders(string $sid, string $token, string $from): bool
    {
        if ($this->twilioSidLooksPlaceholder($sid)) {
            return true;
        }
        if ($this->twilioTokenLooksPlaceholder($token)) {
            return true;
        }
        if ($this->twilioFromLooksPlaceholder($from)) {
            return true;
        }

        return false;
    }

    /**
     * @return array{sent: bool, message: string}
     */
    public function send(string $body): array
    {
        $config = $this->resolveConfiguration();
        if (!$config['configured']) {
            return [
                'sent' => false,
                'message' => $config['message'],
            ];
        }

        $sid = (string) ($config['accountSid'] ?? '');
        $token = (string) ($config['authToken'] ?? '');
        $from = (string) ($config['fromNumber'] ?? '');
        $to = (string) $config['toNumber'];

        $url = sprintf(
            'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json',
            rawurlencode($sid)
        );

        $payload = http_build_query([
            'From' => $from,
            'To' => $to,
            'Body' => $body,
        ]);

        $authHeader = 'Authorization: Basic '.base64_encode($sid.':'.$token);
        $statusCode = 0;
        $response = '';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if (false === $ch) {
                return [
                    'sent' => false,
                    'message' => 'Impossible d’initialiser cURL.',
                ];
            }

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    $authHeader,
                ],
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_TIMEOUT => 12,
            ]);

            $curlResponse = curl_exec($ch);
            if (false === $curlResponse) {
                $curlError = curl_error($ch);
                curl_close($ch);

                return [
                    'sent' => false,
                    'message' => 'Erreur réseau Twilio: '.$curlError,
                ];
            }

            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $response = (string) $curlResponse;
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", [
                        'Content-Type: application/x-www-form-urlencoded',
                        $authHeader,
                    ]),
                    'content' => $payload,
                    'timeout' => 12,
                    'ignore_errors' => true,
                ],
            ]);

            $streamResponse = @file_get_contents($url, false, $context);
            if (false === $streamResponse) {
                return [
                    'sent' => false,
                    'message' => 'Impossible de contacter Twilio (stream).',
                ];
            }
            $response = (string) $streamResponse;

            /** @var array<int, string> $http_response_header */
            if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
                $statusCode = (int) $matches[1];
            }
        }

        if ($statusCode >= 200 && $statusCode < 300) {
            return [
                'sent' => true,
                'message' => 'SMS Twilio envoyé.',
            ];
        }

        $twilioError = null;
        try {
            /** @var array{message?: string, code?: int|string} $decoded */
            $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            $errorMessage = trim((string) ($decoded['message'] ?? ''));
            $errorCode = trim((string) ($decoded['code'] ?? ''));
            if ('' !== $errorMessage) {
                $twilioError = '' !== $errorCode
                    ? sprintf('Twilio %s: %s', $errorCode, $errorMessage)
                    : sprintf('Twilio: %s', $errorMessage);
            }
        } catch (\JsonException) {
            $twilioError = null;
        }

        return [
            'sent' => false,
            'message' => $twilioError ?? sprintf('Twilio a rejeté la requête (HTTP %d).', $statusCode),
        ];
    }
}
