<?php

use PaylandsSDK\Services\PaymentOrder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * PNP_API_URL: development-only override of the Paylands API base URL.
 * Merchants never set it, so the default must stay the public API.
 */
final class PaymentOrderApiUrlTest extends TestCase
{
    protected function setUp(): void
    {
        Configuration::reset();
    }

    protected function tearDown(): void
    {
        Configuration::reset();
    }

    private static function url(string $environment, string $method): string
    {
        $order = new PaymentOrder('key', 'signature', 'service', $environment);

        return (new ReflectionMethod(PaymentOrder::class, $method))->invoke($order, ...($method === 'getURL' ? array('/payment') : array()));
    }

    public function testDefaultsToThePublicApi(): void
    {
        $this->assertSame('https://api.paylands.com/v1/sandbox/payment', self::url('sandbox', 'getURL'));
        $this->assertSame('https://api.paylands.com/v1/payment', self::url('prod', 'getURL'));
        $this->assertSame('https://api.paylands.com/v1/sandbox/payment/process/', self::url('sandbox', 'getRedirectUrl'));
    }

    public function testUsesTheOverrideForApiCallsAndRedirects(): void
    {
        Configuration::updateValue('PNP_API_URL', 'http://ws.paylands.loc/v1/');

        $this->assertSame('http://ws.paylands.loc/v1/sandbox/payment', self::url('sandbox', 'getURL'));
        $this->assertSame('http://ws.paylands.loc/v1/payment', self::url('prod', 'getURL'));
        $this->assertSame('http://ws.paylands.loc/v1/sandbox/payment/process/', self::url('sandbox', 'getRedirectUrl'));
        $this->assertSame('http://ws.paylands.loc/v1/sandbox/payment/tokenized/', self::url('sandbox', 'getTokenized3DSUrl'));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function ignoredValues(): array
    {
        return array(
            'not set (Configuration::get returns false)' => array(false),
            'empty' => array(''),
            'blank' => array('   '),
            'not a url' => array('ws.paylands.loc'),
            'other scheme' => array('ftp://ws.paylands.loc/v1'),
            'javascript' => array('javascript://alert(1)'),
        );
    }

    #[DataProvider('ignoredValues')]
    public function testIgnoresEmptyOrInvalidValues($value): void
    {
        if ($value !== false) {
            Configuration::updateValue('PNP_API_URL', $value);
        }

        $this->assertSame('https://api.paylands.com/v1', PaymentOrder::getApiBaseUrl());
    }

    public function testTheOverrideIsNotPartOfTheAdminForm(): void
    {
        $module = file_get_contents(dirname(__DIR__, 2) . '/paylands.php');

        $this->assertStringNotContainsString('PNP_API_URL', $module);
    }
}
