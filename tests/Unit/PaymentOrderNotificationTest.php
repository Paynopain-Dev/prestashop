<?php

use PaylandsSDK\Services\PaymentOrder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * PaymentOrder::isValidNotification(): the validation_hash check of url_post notifications.
 *
 * A false negative is tolerated (the callback falls back to asking the Paylands API),
 * a false positive is not: it would let anyone mark orders as paid.
 */
final class PaymentOrderNotificationTest extends TestCase
{
    /**
     * Public example signature from the official docs (notification-validation.mdx),
     * which states the example notification is a real case signed with it.
     */
    private const DOC_SIGNATURE = '341f7de8e6fc49da8d8736473af6b03a';

    private const SIGNATURE = 'test-merchant-signature';

    private static function docExample(): string
    {
        return file_get_contents(dirname(__DIR__) . '/fixtures/notification_doc_example.json');
    }

    /**
     * Signs a notification exactly as the Paylands docs describe.
     */
    private static function sign(array $notification, string $signature): string
    {
        $object = json_decode(json_encode($notification));
        $hashed = array('order' => $object->order, 'client' => $object->client);
        if (property_exists($object, 'extra_data')) {
            $hashed['extra_data'] = $object->extra_data;
        }
        $object->validation_hash = hash(
            'sha256',
            json_encode($hashed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . $signature
        );

        return json_encode($object);
    }

    private static function notification(array $orderOverrides = array()): array
    {
        return array(
            'message' => 'OK',
            'code' => 200,
            'order' => array_merge(array(
                'uuid' => 'E89DFBF6-23D3-4D78-BC98-06936F38D85F',
                'amount' => 1050,
                'paid' => true,
                'status' => 'SUCCESS',
                'additional' => 'PSCartId:42',
            ), $orderOverrides),
            'client' => array('uuid' => '42B8CF56-A7D7-4D4A-8349-4E27263CB2D5'),
        );
    }

    public function testAcceptsTheRealExampleFromTheOfficialDocs(): void
    {
        $this->assertTrue(PaymentOrder::isValidNotification(self::docExample(), self::DOC_SIGNATURE));
    }

    public function testRejectsTheRealExampleWithAnotherSignature(): void
    {
        $this->assertFalse(PaymentOrder::isValidNotification(self::docExample(), self::SIGNATURE));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function tamperedDocExample(): array
    {
        return array(
            'amount changed' => array('"amount": 10,', '"amount": 100000,'),
            'paid flipped' => array('"paid": true,', '"paid": false,'),
            'status changed' => array('"status": "SUCCESS",
		"safe"', '"status": "CANCELLED",
		"safe"'),
            'order uuid swapped' => array('E89DFBF6-23D3-4D78-BC98-06936F38D85F', 'D16004FF-3421-409C-ADFC-DA2618D36135'),
            'client uuid swapped' => array('42B8CF56-A7D7-4D4A-8349-4E27263CB2D5', '00000000-0000-0000-0000-000000000000'),
            'hash altered' => array('eae6e4c9d3dcb2706', 'eae6e4c9d3dcb2707'),
            'extra_data added' => array('"client": {', '"extra_data": {"x": 1}, "client": {'),
        );
    }

    #[DataProvider('tamperedDocExample')]
    public function testRejectsTamperedNotifications(string $search, string $replace): void
    {
        $body = self::docExample();
        $this->assertStringContainsString($search, $body, 'fixture changed: update the test');

        $tampered = str_replace($search, $replace, $body);

        $this->assertFalse(PaymentOrder::isValidNotification($tampered, self::DOC_SIGNATURE));
    }

    public function testAcceptsAnUppercaseHash(): void
    {
        $body = preg_replace_callback(
            '/"validation_hash": "([a-f0-9]+)"/',
            function ($m) {
                return '"validation_hash": "' . strtoupper($m[1]) . '"';
            },
            self::docExample()
        );

        $this->assertTrue(PaymentOrder::isValidNotification($body, self::DOC_SIGNATURE));
    }

    public function testIncludesExtraDataInTheHashWhenPresent(): void
    {
        $notification = self::notification();
        $notification['extra_data'] = array('profile' => array('first_name' => 'Ñandú', 'url' => 'https://shop.test/a/b'));
        $body = self::sign($notification, self::SIGNATURE);

        $this->assertTrue(PaymentOrder::isValidNotification($body, self::SIGNATURE));

        // Dropping extra_data from a signed notification must break the hash.
        $withoutExtra = json_decode($body, true);
        unset($withoutExtra['extra_data']);
        $this->assertFalse(PaymentOrder::isValidNotification(json_encode($withoutExtra), self::SIGNATURE));
    }

    public function testKeepsEmptyObjectsAndArraysWhenRecomputingTheHash(): void
    {
        // Decoding into PHP arrays would turn {} into [] and silently change the hashed JSON.
        $body = '{"order":{"uuid":"A","status":"SUCCESS","transactions":[],"threeds_data":{}},'
            . '"client":{"uuid":"B"},"extra_data":{}}';
        $object = json_decode($body);
        $hash = hash('sha256', json_encode(
            array('order' => $object->order, 'client' => $object->client, 'extra_data' => $object->extra_data),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) . self::SIGNATURE);
        $this->assertStringContainsString('"threeds_data":{}', json_encode($object->order));

        $signed = substr($body, 0, -1) . ',"validation_hash":"' . $hash . '"}';

        $this->assertTrue(PaymentOrder::isValidNotification($signed, self::SIGNATURE));
    }

    /**
     * @return array<string, array{mixed, mixed}>
     */
    public static function invalidInput(): array
    {
        $valid = self::sign(self::notification(), self::SIGNATURE);
        $decoded = json_decode($valid, true);

        $noHash = $decoded;
        unset($noHash['validation_hash']);
        $emptyHash = $decoded;
        $emptyHash['validation_hash'] = '';
        $arrayHash = $decoded;
        $arrayHash['validation_hash'] = array($decoded['validation_hash']);
        $noOrder = $decoded;
        unset($noOrder['order']);
        $noClient = $decoded;
        unset($noClient['client']);

        return array(
            'empty body' => array('', self::SIGNATURE),
            'null body' => array(null, self::SIGNATURE),
            'not json' => array('order=1&status=SUCCESS', self::SIGNATURE),
            'json scalar' => array('"SUCCESS"', self::SIGNATURE),
            'json list' => array('[1,2,3]', self::SIGNATURE),
            'missing validation_hash' => array(json_encode($noHash), self::SIGNATURE),
            'empty validation_hash' => array(json_encode($emptyHash), self::SIGNATURE),
            'validation_hash not a string' => array(json_encode($arrayHash), self::SIGNATURE),
            'missing order' => array(json_encode($noOrder), self::SIGNATURE),
            'missing client' => array(json_encode($noClient), self::SIGNATURE),
            'no signature configured' => array($valid, ''),
            'signature null' => array($valid, null),
            'signature false (Configuration::get miss)' => array($valid, false),
        );
    }

    #[DataProvider('invalidInput')]
    public function testRejectsInvalidInput($body, $signature): void
    {
        $this->assertFalse(PaymentOrder::isValidNotification($body, $signature));
    }

    public function testSignedNotificationBuiltByTheTestHelperIsAccepted(): void
    {
        // Guards the helper used above: if this fails, the "invalid input" cases prove nothing.
        $this->assertTrue(PaymentOrder::isValidNotification(self::sign(self::notification(), self::SIGNATURE), self::SIGNATURE));
    }

    /**
     * hashEquals() uses hash_equals(); compareStrings() is the fallback for PHP 5.4/5.5,
     * which never runs on a modern PHP, so it is tested directly.
     */
    public function testConstantTimeComparison(): void
    {
        foreach (array('hashEquals', 'compareStrings') as $name) {
            $method = new ReflectionMethod(PaymentOrder::class, $name);

            $this->assertTrue($method->invoke(null, 'abc123', 'abc123'), $name);
            $this->assertTrue($method->invoke(null, '', ''), $name);
            $this->assertFalse($method->invoke(null, 'abc123', 'abc124'), $name);
            $this->assertFalse($method->invoke(null, 'abc123', 'xbc123'), $name);
            $this->assertFalse($method->invoke(null, 'abc123', 'abc12'), $name);
            $this->assertFalse($method->invoke(null, 'abc123', 'abc1234'), $name);
            $this->assertFalse($method->invoke(null, 'abc123', ''), $name);
        }
    }
}
