<?php

use PHPUnit\Framework\TestCase;

/**
 * Log file naming: on servers that ignore .htaccess (nginx, IIS) the random per-shop prefix
 * is what keeps logs/ from being downloadable by guessing the date.
 */
final class LoggerFileTest extends TestCase
{
    private const PREFIX_PATTERN = '/^log_[a-f0-9]{32}_$/';

    protected function setUp(): void
    {
        Configuration::reset();
        $this->resetLoggerInstance();
        $this->clearLogs();
    }

    protected function tearDown(): void
    {
        $this->resetLoggerInstance();
        $this->clearLogs();
        Configuration::reset();
    }

    private function resetLoggerInstance(): void
    {
        (new ReflectionProperty(Logger::class, 'logger'))->setValue(null, null);
    }

    private function clearLogs(): void
    {
        foreach (glob(_PS_MODULE_DIR_ . 'paylands/logs/*') ?: array() as $file) {
            unlink($file);
        }
    }

    private function filePrefix(): string
    {
        return (new ReflectionMethod(Logger::class, 'getFilePrefix'))->invoke(null);
    }

    public function testGeneratesAndStoresARandomPrefix(): void
    {
        $prefix = $this->filePrefix();

        $this->assertMatchesRegularExpression(self::PREFIX_PATTERN, $prefix);
        $this->assertSame($prefix, Configuration::getGlobalValue(Logger::PREFIX_CONFIG_KEY));
    }

    public function testReusesTheStoredPrefix(): void
    {
        $first = $this->filePrefix();

        $this->assertSame($first, $this->filePrefix());
    }

    public function testEachShopGetsADifferentPrefix(): void
    {
        $first = $this->filePrefix();
        Configuration::reset();

        $this->assertNotSame($first, $this->filePrefix());
    }

    public function testReplacesAnInvalidStoredPrefix(): void
    {
        foreach (array('log_', '../../../evil_', 'log_ABC_', 'log_1234_') as $invalid) {
            Configuration::updateGlobalValue(Logger::PREFIX_CONFIG_KEY, $invalid);

            $prefix = $this->filePrefix();

            $this->assertMatchesRegularExpression(self::PREFIX_PATTERN, $prefix, "stored value: $invalid");
        }
    }

    public function testWritesToAnUnguessableFileName(): void
    {
        Logger::log('PNP_TEST#hello');

        $files = array_map('basename', glob(_PS_MODULE_DIR_ . 'paylands/logs/*.log'));
        $prefix = Configuration::getGlobalValue(Logger::PREFIX_CONFIG_KEY);

        $this->assertSame(array($prefix . date('Y-m-d') . '.log'), $files);
        $this->assertNotContains('log_' . date('Y-m-d') . '.log', $files, 'old predictable name');
        $this->assertStringContainsString('PNP_TEST#hello', file_get_contents(_PS_MODULE_DIR_ . 'paylands/logs/' . $files[0]));
    }

    public function testErrorsGoToTheSameFile(): void
    {
        Logger::log('first');
        Logger::error('second');

        $files = glob(_PS_MODULE_DIR_ . 'paylands/logs/*.log');

        $this->assertCount(1, $files);
        $content = file_get_contents($files[0]);
        $this->assertStringContainsString('first', $content);
        $this->assertStringContainsString('second', $content);
    }
}
