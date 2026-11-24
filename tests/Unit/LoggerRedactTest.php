<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Logger::redact(): secrets, card data and personal data must never reach the log files.
 */
final class LoggerRedactTest extends TestCase
{
    private const CARD_TOKEN = '95d37eaa18762d7d8c7b4ef098c4dd1e55450f21a9efaea27466d42da183b21b6cf3d0d8b74001b17ee3ba7d23e1daba7dda9b54a37979f3bb7cebc6e18ee7fe';

    /**
     * Shape of what the module logs: the create-order request and a Paylands order response.
     */
    private static function paylandsPayload(): array
    {
        return array(
            'signature' => '341f7de8e6fc49da8d8736473af6b03a',
            'amount' => 1050,
            'customer_ext_id' => '42',
            'url_post' => 'https://shop.test/module/paylands/paymentcallback?id=42',
            'extra_data' => array(
                'profile' => array(
                    'first_name' => 'Ana',
                    'last_name' => 'García',
                    'email' => 'ana@example.com',
                    'phone' => array('number' => '600000000', 'prefix' => '34'),
                ),
                'billing_address' => array('address1' => 'Calle Mayor 1', 'zip_code' => '28001', 'city' => 'Madrid'),
                'halcash' => array('sender_name' => 'sender', 'secret_key' => '1234'),
            ),
            'order' => array(
                'uuid' => 'E89DFBF6-23D3-4D78-BC98-06936F38D85F',
                'status' => 'SUCCESS',
                'token' => 'ORDERTOKEN0123456789abcdef',
                'transactions' => array(
                    array(
                        'uuid' => '7DD3AE71-A758-416C-B813-D3EE936500F3',
                        'status' => 'SUCCESS',
                        'source' => array(
                            'uuid' => '80C55ED9-3D72-4636-8AC7-27898AAD36B3',
                            'token' => self::CARD_TOKEN,
                            'brand' => 'VISA',
                            'holder' => 'Miguel C',
                            'bin' => 401881,
                            'last4' => '0011',
                            'expire_month' => '12',
                            'expire_year' => '34',
                        ),
                        'bizum' => array('account' => 'ES51XXXXXXXXXXXXXXXX0001', 'phone_number' => '346XXXXX306'),
                    ),
                ),
            ),
            'client' => array('uuid' => '42B8CF56-A7D7-4D4A-8349-4E27263CB2D5'),
        );
    }

    public function testNoSecretOrPersonalValueSurvivesInTheEncodedLogLine(): void
    {
        $logged = json_encode(Logger::redact(self::paylandsPayload()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        foreach (array(
            '341f7de8e6fc49da8d8736473af6b03a', self::CARD_TOKEN, 'ORDERTOKEN0123456789',
            'Ana', 'García', 'ana@example.com', '600000000', 'Calle Mayor', '28001',
            'Miguel C', '401881', 'ES51XXXXXXXXXXXXXXXX0001', '346XXXXX306', '"1234"',
        ) as $secret) {
            $this->assertStringNotContainsString($secret, $logged);
        }
    }

    public function testKeepsWhatSupportNeedsToTraceAPayment(): void
    {
        $redacted = Logger::redact(self::paylandsPayload());
        $source = $redacted['order']['transactions'][0]['source'];

        $this->assertSame(1050, $redacted['amount']);
        $this->assertSame('E89DFBF6-23D3-4D78-BC98-06936F38D85F', $redacted['order']['uuid']);
        $this->assertSame('SUCCESS', $redacted['order']['status']);
        $this->assertSame('https://shop.test/module/paylands/paymentcallback?id=42', $redacted['url_post']);
        $this->assertSame('80C55ED9-3D72-4636-8AC7-27898AAD36B3', $source['uuid']);
        $this->assertSame('VISA', $source['brand']);
        $this->assertSame('0011', $source['last4']);
    }

    public function testMasksWholeSensitiveSubtrees(): void
    {
        $redacted = Logger::redact(self::paylandsPayload());

        $this->assertSame(Logger::REDACTED, $redacted['extra_data']['billing_address']);
        $this->assertSame(Logger::REDACTED, $redacted['extra_data']['profile']['phone']);
    }

    public function testTokensKeepOnlyTheirLastFourCharacters(): void
    {
        $redacted = Logger::redact(self::paylandsPayload());

        $this->assertSame(Logger::REDACTED . 'e7fe', $redacted['order']['transactions'][0]['source']['token']);
        $this->assertSame(Logger::REDACTED . 'cdef', $redacted['order']['token']);
    }

    public function testShortTokensAreFullyMasked(): void
    {
        $this->assertSame(array('token' => Logger::REDACTED), Logger::redact(array('token' => '12345678')));
    }

    public function testKeysAreMatchedCaseInsensitively(): void
    {
        $redacted = Logger::redact(array('Signature' => 'secret', 'EMAIL' => 'a@b.c', 'Card_Token' => 'abcdefghijkl'));

        $this->assertSame(Logger::REDACTED, $redacted['Signature']);
        $this->assertSame(Logger::REDACTED, $redacted['EMAIL']);
        $this->assertSame(Logger::REDACTED . 'ijkl', $redacted['Card_Token']);
    }

    public function testEmptySensitiveValuesAreLeftAsTheyAre(): void
    {
        // Seeing "null" vs "***" in a log tells support whether a field was sent at all.
        $this->assertSame(
            array('email' => null, 'holder' => '', 'token' => null),
            Logger::redact(array('email' => null, 'holder' => '', 'token' => null))
        );
    }

    public function testDoesNotModifyTheOriginalData(): void
    {
        $payload = self::paylandsPayload();
        $copy = $payload;

        Logger::redact($payload);

        $this->assertSame($copy, $payload);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function nonArrayValues(): array
    {
        return array(
            'null (failed API call)' => array(null),
            'string' => array('raw response'),
            'int' => array(500),
            'false' => array(false),
        );
    }

    #[DataProvider('nonArrayValues')]
    public function testNonArrayValuesAreReturnedUnchanged($value): void
    {
        $this->assertSame($value, Logger::redact($value));
    }
}
