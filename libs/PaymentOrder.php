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

namespace PaylandsSDK\Services;


use Customer;

class PaymentOrder
{

    static $API_URL = "https://api.paylands.com/v1";
    static $SANDBOX = "/sandbox";

    const REDIRECT_URL = "/payment/process/";
    const TOKENIZED_3DS_URL = "/payment/tokenized/";

    const CREATE_ORDER_ENDPOINT = '/payment';
    const DIRECT_PAYMENT_ENDPOINT = '/payment/direct';
    const ORDER_ENDPOINT = '/order/';

    /**
     * PNP User Api key - obtained from User PNP account
     * @var string
     */
    private $api_key;

    /**
     * PNP User signature - obtained from User PNP account
     * @var string
     */
    private $signature;

    /**
     * PNP User service - obtained from User PNP account
     * @var string
     */
    private $service;

    /**
     * Environment production|sandbox
     * @var string
     */
    private $environment;

    public function __construct($api_key, $signature, $service, $environment)
    {
        $this->api_key = $api_key;
        $this->signature = $signature;
        $this->service = $service;
        $this->environment = $environment;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->api_key;
    }

    /**
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->getURL(self::REDIRECT_URL);
    }

    /**
     * @return string
     */
    public function getTokenized3DSUrl()
    {
        return $this->getURL(self::TOKENIZED_3DS_URL);
    }

    /**
     * @param float $amount
     * @param string $operative
     * @param string $customer_ext_id
     * @param string $description
     * @param string $additional
     * @param bool $secure
     * @param string $url_post
     * @param string $url_ok
     * @param string $url_ko
     * @param string $source_uuid
     * @return array
     */
    public function createOrder($amount, $operative, $customer_ext_id, $description, $additional, $secure, $url_post, $url_ok, $url_ko, $source_uuid)
    {
        $customer = new Customer($customer_ext_id);
        $amount_in_cents = $this->toCents($amount);
        $payload = array(
            "amount" => $amount_in_cents,
            "operative" => $operative,
            "signature" => $this->signature,
            "customer_ext_id" => $customer_ext_id,
            "description" => $description,
            "service" => $this->service,
            "secure" => $secure,
            "additional" => $additional,
            "url_post" => $url_post,
            "url_ok" => $url_ok,
            "url_ko" => $url_ko,
            "extra_data" => [
                "profile" => [
                    "first_name" => $customer->firstname,
                    "last_name" => $customer->lastname,
                    "email" => $customer->email
                ]
            ]
        );

        if (!empty($source_uuid)) {
            $payload['source_uuid'] = $source_uuid;
        }
        $api_url = $this->getURL(self::CREATE_ORDER_ENDPOINT);
        return $this->post($api_url, $payload);
    }

    /**
     * @param string $customer_ip
     * @param string $order_uuid
     * @param string $card_uuid
     * @return array
     */
    public function directPayment($customer_ip, $order_uuid, $card_uuid)
    {
        $payload = array(
            "customer_ip" => $customer_ip,
            "order_uuid" => $order_uuid,
            "card_uuid" => $card_uuid,
            "signature" => $this->getSignature(),
        );

        $direct_payment_url = $this->getURL(self::DIRECT_PAYMENT_ENDPOINT);
        return $this->post($direct_payment_url, $payload);
    }

    /**
     * Reads an order from Paylands (GET /order/{uuid}).
     *
     * @param string $order_uuid
     * @return array|null
     */
    public function getOrder($order_uuid)
    {
        $url = $this->getURL(self::ORDER_ENDPOINT . rawurlencode($order_uuid));
        return $this->request($url, null);
    }

    /**
     * Checks the validation_hash of a url_post notification sent by Paylands.
     *
     * Paylands computes sha256(json_encode({order, client[, extra_data]}) . signature)
     * with JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES. The body is decoded as
     * objects (not associative arrays) so empty objects/arrays re-encode unchanged.
     *
     * @param string $raw_body Raw notification body
     * @param string $signature Merchant signature
     * @return bool
     */
    public static function isValidNotification($raw_body, $signature)
    {
        if (empty($raw_body) || empty($signature)) {
            return false;
        }

        $notification = json_decode($raw_body);
        if (!is_object($notification)
            || empty($notification->validation_hash)
            || !is_string($notification->validation_hash)
            || !property_exists($notification, 'order')
            || !property_exists($notification, 'client')
        ) {
            return false;
        }

        $hashed = array(
            'order' => $notification->order,
            'client' => $notification->client,
        );
        if (property_exists($notification, 'extra_data')) {
            $hashed['extra_data'] = $notification->extra_data;
        }

        $data = json_encode($hashed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($data === false) {
            return false;
        }

        $expected = hash('sha256', $data . $signature);

        return self::hashEquals($expected, strtolower($notification->validation_hash));
    }

    /**
     * Constant-time string comparison (hash_equals is not available before PHP 5.6).
     *
     * @param string $known
     * @param string $user
     * @return bool
     */
    private static function hashEquals($known, $user)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($known, $user);
        }

        return self::compareStrings($known, $user);
    }

    /**
     * Constant-time fallback used on PHP 5.4/5.5 only.
     *
     * @param string $known
     * @param string $user
     * @return bool
     */
    private static function compareStrings($known, $user)
    {
        if (strlen($known) !== strlen($user)) {
            return false;
        }

        $result = 0;
        for ($i = 0, $len = strlen($known); $i < $len; $i++) {
            $result |= ord($known[$i]) ^ ord($user[$i]);
        }

        return $result === 0;
    }

    /**
     * @param float $number
     * @return float
     */
    private function toCents($number)
    {
        return floor(100 * $number);
    }

    private function post($url, $params)
    {
        \Logger::log("PaylandsRequest:" . json_encode(\Logger::redact($params), JSON_PRETTY_PRINT));

        return $this->request($url, json_encode($params));
    }

    /**
     * @param string $url
     * @param string|null $payload_json JSON body for POST, null for GET
     * @return array|null
     */
    private function request($url, $payload_json)
    {
        $token = $this->getApiKey();

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        if ($payload_json !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_json);
        }
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
                "Authorization: Bearer " . $token,
            )
        );

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $response;
    }

    private function getURL($endpoint)
    {
        $env = $this->getEnvironment();
        if ($env == 'sandbox') {
            return self::$API_URL  . self::$SANDBOX . $endpoint;
        }

        return self::$API_URL . $endpoint;
    }
}
