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
     * @return array{configured: bool, message: string, toNumber: ?string}
     */
    private function resolveConfiguration(): array
    {
        $sid = trim((string) $this->accountSid);
        $token = trim((string) $this->authToken);
        $from = trim((string) $this->fromNumber);
        $to = trim((string) $this->toNumber);

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
            // Keep env fallback when table/settings are unavailable.
        }

        $missing = [];
        if ('' === $sid) {
            $missing[] = 'TWILIO_ACCOUNT_SID';
        }
        if ('' === $token) {
            $missing[] = 'TWILIO_AUTH_TOKEN';
        }
        if ('' === $from) {
            $missing[] = 'TWILIO_FROM_NUMBER';
        }
        if ('' === $to) {
            $missing[] = 'TWILIO_TO_NUMBER/app_settings.phone_number';
        }

        if ([] !== $missing) {
            return [
                'configured' => false,
                'message' => 'Configuration Twilio incomplète: '.implode(', ', $missing),
                'toNumber' => null,
            ];
        }

        return [
            'configured' => true,
            'message' => 'Twilio configuré.',
            'toNumber' => $to,
        ];
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

        $sid = trim((string) $this->accountSid);
        $token = trim((string) $this->authToken);
        $from = trim((string) $this->fromNumber);
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
