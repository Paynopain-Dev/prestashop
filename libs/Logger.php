<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

class Logger {

    /**
     * Configuration key holding the random, per-shop log file prefix.
     * Log files live in a public module folder; on servers that ignore .htaccess
     * (nginx, IIS) an unguessable file name is what keeps them from being downloaded.
     */
    const PREFIX_CONFIG_KEY = 'PNP_LOG_PREFIX';

    const REDACTED = '***';

    /**
     * Keys whose values must never be written to the logs (secrets, card data and
     * customer personal data). Compared case-insensitively at any depth.
     */
    private static $sensitiveKeys = array(
        'signature', 'token', 'card_token', 'secret_key',
        'holder', 'cardholder_name', 'bin', 'account', 'phone_number', 'par', 'masked_pan',
        'email', 'first_name', 'last_name', 'phone', 'home_phone', 'work_phone', 'mobile_phone',
        'address', 'address1', 'address2', 'address3', 'zip_code',
        'shipping_address', 'billing_address',
    );

    private static $logger;
    public static function log($message)
    {
        if (empty(self::$logger)) {
            self::$logger = new \Katzgrau\KLogger\Logger(_PS_MODULE_DIR_.'paylands/logs', Psr\Log\LogLevel::INFO, self::getOptions());
        }
        self::$logger->info($message);
    }

    public static function error($message)
    {
        if (empty(self::$logger)) {
            self::$logger = new \Katzgrau\KLogger\Logger(_PS_MODULE_DIR_.'paylands/logs', Psr\Log\LogLevel::ERROR, self::getOptions());
        }
        self::$logger->error($message);
    }

    /**
     * Returns a copy of $data with sensitive values masked, ready to be json-encoded into a log.
     * Tokens keep their last 4 characters so support can still correlate them.
     *
     * @param mixed $data
     * @return mixed
     */
    public static function redact($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $sensitive = array_flip(self::$sensitiveKeys);
        $redacted = array();
        foreach ($data as $key => $value) {
            if (is_string($key) && isset($sensitive[strtolower($key)])) {
                $redacted[$key] = self::mask($key, $value);
            } else {
                $redacted[$key] = self::redact($value);
            }
        }

        return $redacted;
    }

    private static function mask($key, $value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $lower = strtolower($key);
        if (($lower === 'token' || $lower === 'card_token') && is_string($value) && strlen($value) > 8) {
            return self::REDACTED . substr($value, -4);
        }

        return self::REDACTED;
    }

    private static function getOptions()
    {
        return array(
            'extension' => 'log',
            'prefix' => self::getFilePrefix(),
        );
    }

    /**
     * Random prefix generated once per shop, e.g. "log_3f9c0a1b2d4e5f67_".
     * Falls back to the historical "log_" prefix if the configuration cannot be used.
     *
     * @return string
     */
    private static function getFilePrefix()
    {
        if (!class_exists('Configuration')) {
            return 'log_';
        }

        $prefix = Configuration::getGlobalValue(self::PREFIX_CONFIG_KEY);
        if (!empty($prefix) && preg_match('/^log_[a-f0-9]{16,}_$/', $prefix)) {
            return $prefix;
        }

        $random = self::randomHex(16);
        if ($random === null) {
            return 'log_';
        }

        $prefix = 'log_' . $random . '_';
        Configuration::updateGlobalValue(self::PREFIX_CONFIG_KEY, $prefix);

        return $prefix;
    }

    /**
     * @param int $bytes
     * @return string|null
     */
    private static function randomHex($bytes)
    {
        if (function_exists('random_bytes')) {
            try {
                return bin2hex(random_bytes($bytes));
            } catch (Exception $e) {
                // fall through
            }
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            $strong = false;
            $random = openssl_random_pseudo_bytes($bytes, $strong);
            if ($random !== false && $strong) {
                return bin2hex($random);
            }
        }

        return null;
    }
}
