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


use Address;
use Country;
use Customer;
use State;

class PaymentOrder
{

    static $API_URL = "https://api.paylands.com/v1";
    static $SANDBOX = "/sandbox";

    const REDIRECT_URL = "/payment/process/";
    const TOKENIZED_3DS_URL = "/payment/tokenized/";

    const CREATE_ORDER_ENDPOINT = '/payment';
    const DIRECT_PAYMENT_ENDPOINT = '/payment/direct';

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
    public function createOrder($amount, $operative, $customer_ext_id, $description, $additional, $secure, $url_post, $url_ok, $url_ko, $source_uuid, $extra_data_extra = array(), $cart = null)
    {
        $customer = new Customer($customer_ext_id);
        $amount_in_cents = $this->toCents($amount);

        $extra_data = array_merge(
            $this->buildExtraData($customer, $cart),
            $extra_data_extra
        );

        $payload = array(
            "amount" => $amount_in_cents,
            "operative" => $operative,
            "signature" => $this->signature,
            "customer_ext_id" => "" . $customer_ext_id,
            "description" => $description,
            "service" => $this->service,
            "secure" => $secure,
            "additional" => $additional,
            "url_post" => $url_post,
            "url_ok" => $url_ok,
            "url_ko" => $url_ko,
            "extra_data" => $extra_data,
        );

        if (!empty($source_uuid)) {
            $payload['source_uuid'] = $source_uuid;
        }
        $api_url = $this->getURL(self::CREATE_ORDER_ENDPOINT);
        return $this->post($api_url, $payload);
    }

    private function buildExtraData($customer, $cart)
    {
        $extra = array('profile' => $this->buildProfile($customer, $cart));

        if ($cart === null) {
            return $extra;
        }

        $delivery = new Address((int)$cart->id_address_delivery);
        $invoice  = new Address((int)$cart->id_address_invoice);

        $extra['address']          = $this->buildAddress($delivery, 'SHIPPING', true);
        $extra['shipping_address'] = $this->buildAddress($delivery, 'SHIPPING', true);
        $extra['billing_address']  = $this->buildAddress($invoice,  'BILLING',  true);

        return $extra;
    }

    private function buildProfile($customer, $cart)
    {
        $profile = array(
            'first_name' => $customer->firstname,
            'last_name'  => $customer->lastname,
            'email'      => $customer->email,
        );

        if ($cart !== null) {
            $addr = new Address((int)$cart->id_address_delivery);

            $cardholder = trim($addr->firstname . ' ' . $addr->lastname);
            if (!empty($cardholder)) {
                $profile['cardholder_name'] = $cardholder;
            }

            $phone        = $addr->phone        ?: null;
            $phone_mobile = $addr->phone_mobile ?: $phone;

            if (!empty($phone)) {
                $profile['phone']        = array('number' => $phone);
                $profile['home_phone']   = array('number' => $phone);
                $profile['work_phone']   = array('number' => $phone);
            }
            if (!empty($phone_mobile)) {
                $profile['mobile_phone'] = array('number' => $phone_mobile);
            }
        }

        if (!empty($customer->birthday) && $customer->birthday !== '0000-00-00') {
            $profile['birthdate'] = $customer->birthday;
        }

        return $profile;
    }

    private function buildAddress(Address $address, $type, $default)
    {
        $data = array(
            'address1'   => substr($address->address1, 0, 50),
            'city'       => $address->city,
            'country'    => $this->toIso3($address->id_country),
            'default'    => $default,
            'state_code' => $this->getStateName($address->id_state),
            'type'       => $type,
            'zip_code'   => $address->postcode,
        );

        if (!empty($address->address2)) {
            $data['address2'] = substr($address->address2, 0, 50);
        }

        return $data;
    }

    private function getStateName($id_state)
    {
        if (empty($id_state)) {
            return '';
        }
        $state = new State((int)$id_state);
        return $state->name ?: '';
    }

    private function toIso3($id_country)
    {
        static $map = array(
            'AD'=>'AND','AE'=>'ARE','AF'=>'AFG','AG'=>'ATG','AL'=>'ALB','AM'=>'ARM',
            'AO'=>'AGO','AR'=>'ARG','AT'=>'AUT','AU'=>'AUS','AZ'=>'AZE','BA'=>'BIH',
            'BB'=>'BRB','BD'=>'BGD','BE'=>'BEL','BF'=>'BFA','BG'=>'BGR','BH'=>'BHR',
            'BI'=>'BDI','BJ'=>'BEN','BN'=>'BRN','BO'=>'BOL','BR'=>'BRA','BS'=>'BHS',
            'BT'=>'BTN','BW'=>'BWA','BY'=>'BLR','BZ'=>'BLZ','CA'=>'CAN','CD'=>'COD',
            'CF'=>'CAF','CG'=>'COG','CH'=>'CHE','CI'=>'CIV','CL'=>'CHL','CM'=>'CMR',
            'CN'=>'CHN','CO'=>'COL','CR'=>'CRI','CU'=>'CUB','CV'=>'CPV','CY'=>'CYP',
            'CZ'=>'CZE','DE'=>'DEU','DJ'=>'DJI','DK'=>'DNK','DM'=>'DMA','DO'=>'DOM',
            'DZ'=>'DZA','EC'=>'ECU','EE'=>'EST','EG'=>'EGY','ER'=>'ERI','ES'=>'ESP',
            'ET'=>'ETH','FI'=>'FIN','FJ'=>'FJI','FM'=>'FSM','FR'=>'FRA','GA'=>'GAB',
            'GB'=>'GBR','GD'=>'GRD','GE'=>'GEO','GH'=>'GHA','GM'=>'GMB','GN'=>'GIN',
            'GQ'=>'GNQ','GR'=>'GRC','GT'=>'GTM','GW'=>'GNB','GY'=>'GUY','HN'=>'HND',
            'HR'=>'HRV','HT'=>'HTI','HU'=>'HUN','ID'=>'IDN','IE'=>'IRL','IL'=>'ISR',
            'IN'=>'IND','IQ'=>'IRQ','IR'=>'IRN','IS'=>'ISL','IT'=>'ITA','JM'=>'JAM',
            'JO'=>'JOR','JP'=>'JPN','KE'=>'KEN','KG'=>'KGZ','KH'=>'KHM','KI'=>'KIR',
            'KM'=>'COM','KN'=>'KNA','KP'=>'PRK','KR'=>'KOR','KW'=>'KWT','KZ'=>'KAZ',
            'LA'=>'LAO','LB'=>'LBN','LC'=>'LCA','LI'=>'LIE','LK'=>'LKA','LR'=>'LBR',
            'LS'=>'LSO','LT'=>'LTU','LU'=>'LUX','LV'=>'LVA','LY'=>'LBY','MA'=>'MAR',
            'MC'=>'MCO','MD'=>'MDA','ME'=>'MNE','MG'=>'MDG','MH'=>'MHL','MK'=>'MKD',
            'ML'=>'MLI','MM'=>'MMR','MN'=>'MNG','MR'=>'MRT','MT'=>'MLT','MU'=>'MUS',
            'MV'=>'MDV','MW'=>'MWI','MX'=>'MEX','MY'=>'MYS','MZ'=>'MOZ','NA'=>'NAM',
            'NE'=>'NER','NG'=>'NGA','NI'=>'NIC','NL'=>'NLD','NO'=>'NOR','NP'=>'NPL',
            'NR'=>'NRU','NZ'=>'NZL','OM'=>'OMN','PA'=>'PAN','PE'=>'PER','PG'=>'PNG',
            'PH'=>'PHL','PK'=>'PAK','PL'=>'POL','PT'=>'PRT','PW'=>'PLW','PY'=>'PRY',
            'QA'=>'QAT','RO'=>'ROU','RS'=>'SRB','RU'=>'RUS','RW'=>'RWA','SA'=>'SAU',
            'SB'=>'SLB','SC'=>'SYC','SD'=>'SDN','SE'=>'SWE','SG'=>'SGP','SI'=>'SVN',
            'SK'=>'SVK','SL'=>'SLE','SM'=>'SMR','SN'=>'SEN','SO'=>'SOM','SR'=>'SUR',
            'SS'=>'SSD','ST'=>'STP','SV'=>'SLV','SY'=>'SYR','SZ'=>'SWZ','TD'=>'TCD',
            'TG'=>'TGO','TH'=>'THA','TJ'=>'TJK','TL'=>'TLS','TM'=>'TKM','TN'=>'TUN',
            'TO'=>'TON','TR'=>'TUR','TT'=>'TTO','TV'=>'TUV','TZ'=>'TZA','UA'=>'UKR',
            'UG'=>'UGA','US'=>'USA','UY'=>'URY','UZ'=>'UZB','VC'=>'VCT','VE'=>'VEN',
            'VN'=>'VNM','VU'=>'VUT','WS'=>'WSM','YE'=>'YEM','ZA'=>'ZAF','ZM'=>'ZMB',
            'ZW'=>'ZWE',
        );
        $iso2 = Country::getIsoById((int)$id_country);
        return isset($map[$iso2]) ? $map[$iso2] : $iso2;
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
     * @param float $number
     * @return float
     */
    private function toCents($number)
    {
        return floor(100 * $number);
    }

    private function post($url, $params)
    {
        \Logger::log("PaylandsRequest:" . json_encode($params, JSON_PRETTY_PRINT));

        $payload_json = json_encode($params);
        $token = $this->getApiKey();

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_json);
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
