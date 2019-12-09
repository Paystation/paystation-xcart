<?php

/**
 * Paystation Payment module for X-Cart 5.0.13
 *
 *
 * @category  X-Cart 5
 * @author    Paystation Ltd <info@paystation.co.nz>
 * @copyright Copyright (c) 2014 Paystation Ltd <info@paystation.co.nz>. All rights reserved
 * @license   http://www.x-cart.com/license-agreement.html X-Cart 5 License Agreement
 * @link      http://www.paystation.co.nz
 */

namespace XLite\Module\Paystation\ThreeParty\Model\Payment\Processor;

class PaystationProcessor extends \XLite\Model\Payment\Base\WebBased
{
    /**
     * Get operation types
     *
     * @return array
     */
    public function getOperationTypes()
    {
        return array(
            self::OPERATION_SALE,
        );
    }

    /**
     * Get settings widget or template
     *
     * @return string Widget class name or template path
     */
    public function getSettingsWidget()
    {
        return 'modules/Paystation/ThreeParty/config.twig';
    }

    /**
     * Process return
     *
     * @param \XLite\Model\Payment\Transaction $transaction Return-owner transaction
     *
     * @return void
     */
    public function processReturn(\XLite\Model\Payment\Transaction $transaction)
    {
        parent::processReturn($transaction);

        $request = \XLite\Core\Request::getInstance();
        $status = $transaction::STATUS_SUCCESS;
        $orderId = $request->order_number;

        if ($_GET['ec'] == '0') {
            $paystationID = $this->getSetting('PaystationID');
            $responseCode = $this->_transactionVerification($paystationID, $_GET['ti'], $_GET['ms']);

            if ((int)$responseCode != 0) $status = $transaction::STATUS_FAILED;
        } else {
            $status = $transaction::STATUS_FAILED;
        }

        $this->transaction->setStatus($status);
    }

    /**
     * Check - payment method is configured or not
     *
     * @param \XLite\Model\Payment\Method $method Payment method
     *
     * @return boolean
     */
    public function isConfigured(\XLite\Model\Payment\Method $method)
    {
        return parent::isConfigured($method)
            && $method->getSetting('PaystationID')
            && $method->getSetting('Gateway');
    }

    /**
     * Get return type
     *
     * @return string
     */
    public function getReturnType()
    {
        return self::RETURN_TYPE_HTML_REDIRECT;
    }

    /**
     * Returns the list of settings available for this payment processor
     *
     * @return array
     */
    public function getAvailableSettings()
    {
        return array(
            'PaystationID',
            'Gateway',
            'TestMode',
        );
    }

    /**
     * Get return request owner transaction or null
     *
     * @return \XLite\Model\Payment\Transaction|void
     */
    public function getReturnOwnerTransaction()
    {
        $merchant_ref = $_GET['merchant_ref'];
        $xpl = explode('-', $merchant_ref);
        $transactionId = (int)$xpl[1];
        return \XLite\Core\Database::getRepo('XLite\Model\Payment\Transaction')
            ->find($transactionId);
    }

    /**
     * Get payment method admin zone icon URL
     *
     * @param \XLite\Model\Payment\Method $method Payment method
     *
     * @return string
     */
    public function getAdminIconURL(\XLite\Model\Payment\Method $method)
    {
        return true;
    }

    /**
     * Get redirect form URL
     *
     * @return string
     */
    protected function getFormURL()
    {
        $sessionID = $this->_makePaystationSessionID();
        $merchantref = "xcart-" . $this->getOrder()->getOrderNumber();
        $amount = ($this->getFormattedPrice($this->transaction->getValue())) * 100;
        $gateway = $this->getSetting('Gateway');
        $paystationID = $this->getSetting('PaystationID');
        $testmode = $this->getSetting('TestMode');

        if ($testmode == 'test') $test_string = '&pstn_tm=t';
        else $test_string = '';

        $pstn_pi = $paystationID;
        $pstn_gi = $gateway;
        $pstn_am = $amount;
        $pstn_mr = "xcart-" . $this->getOrder()->getOrderNumber();
        $pstn_ms = $pstn_mr . '_' . time() . '-' . $sessionID;

        $paystationURL = 'https://www.paystation.co.nz/direct/paystation.dll';
        $paystationParams = "paystation=_empty&pstn_nr=t&pstn_pi=$pstn_pi&pstn_gi=$pstn_gi&pstn_ms=$pstn_ms&pstn_am=$pstn_am&pstn_mr=$pstn_mr" . $test_string;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $paystationParams);
        curl_setopt($ch, CURLOPT_URL, $paystationURL);
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        $p = xml_parser_create();
        xml_parse_into_struct($p, $result, $vals, $tags);
        xml_parser_free($p);
        for ($j = 0; $j < count($vals); $j++) {
            if (!strcmp($vals[$j]["tag"], "TI") && isset($vals[$j]["value"])) {
                $returnTI = $vals[$j]["value"];
            }
            if (!strcmp($vals[$j]["tag"], "EC") && isset($vals[$j]["value"])) {
                $errorCode = $vals[$j]["value"];
            }
            if (!strcmp($vals[$j]["tag"], "DIGITALORDER") && isset($vals[$j]["value"])) {
                $digitalOrder = $vals[$j]["value"];
            }
        }

        if (isset($digitalOrder)) {
            return $digitalOrder;
        } else {
            exit ("digitalOrder not set");
        }
    }

    /**
     * Blablabla
     *
     * @param \XLite\Model\Address $address Address model (could be shipping or billing address)
     *
     * @return string
     */
    private function _makePaystationSessionID($min = 8, $max = 8)
    {
        // seed the random number generator - straight from PHP manual
        $seed = (double)microtime() * getrandmax();
        srand($seed);

        $pass = '';
        // make a string of $max characters with ASCII values of 40-122
        $p = 0;
        while ($p < $max):
            $r = chr(123 - (rand() % 75));

            // get rid of all non-alphanumeric characters
            if (!($r >= 'a' && $r <= 'z') && !($r >= 'A' && $r <= 'Z') && !($r >= '1' && $r <= '9')) continue;
            $pass .= $r;

            $p++; endwhile;
        // if string is too short, remake it
        if (strlen($pass) < $min):
            $pass = $this->makePaystationSessionID($min, $max);
        endif;

        return $pass;
    }

    /**
     * Format name for request. (firstname + lastname from shipping/billing address)
     *
     * @param \XLite\Model\Address $address Address model (could be shipping or billing address)
     *
     * @return string
     */
    protected function getName($address)
    {
        return $address->getFirstname()
            . ' ' . $address->getLastname();
    }

    /**
     * Format state of billing address for request
     *
     * @return string
     */
    protected function getBillingState()
    {
        return $this->getState($this->getProfile()->getBillingAddress());
    }

    /**
     * Format state of shipping address for request
     *
     * @return string
     */
    protected function getShippingState()
    {
        return $this->getState($this->getProfile()->getShippingAddress());
    }

    /**
     * Format state that is provided from $address model for request.
     *
     * @param \XLite\Model\Address $address Address model (could be shipping or billing address)
     *
     * @return string
     */
    protected function getState($address)
    {
        $state = $this->getStateFieldValue($address);

        if (empty($state)) {
            $state = 'n/a';
        } elseif (!in_array($this->getCountryField($address), array('US', 'CA'))) {
            $state = 'XX';
        }

        return $state;
    }

    /**
     * Return State field value. If country is US then state code must be used.
     *
     * @param \XLite\Model\Address $address Address model (could be shipping or billing address)
     *
     * @return string
     */
    protected function getStateFieldValue($address)
    {
        return 'US' === $this->getCountryField($address)
            ? $address->getState()->getCode()
            : $address->getState()->getState();
    }

    /**
     * Return Country field value. if no country defined we should use '' value
     *
     * @param \XLite\Model\Address $address Address model (could be shipping or billing address)
     *
     * @return string
     */
    protected function getCountryField($address)
    {
        return $address->getCountry()
            ? $address->getCountry()->getCode()
            : '';
    }

    /**
     * Return formatted price.
     *
     * @param float $price Price value
     *
     * @return string
     */
    protected function getFormattedPrice($price)
    {
        return sprintf('%.2f', round((double)($price) + 0.00000000001, 2));
    }


    /**
     * Get redirect form fields list
     *
     * @return array
     */
    protected function getFormFields()
    {
        $fields = array(
            'sid' => $this->getSetting('account'),
            'total' => $this->getFormattedPrice($this->transaction->getValue()),
            'cart_order_id' => $this->transaction->getTransactionId(),
            'merchant_order_id' => $this->getSetting('prefix') . $this->getOrder()->getOrderNumber(),
            'pay_method' => 'CC',
            'lang' => $this->getSetting('language'),
            'skip_landing' => '1',
            'card_holder_name' => $this->getName($this->getProfile()->getBillingAddress()),
            'street_address' => $this->getProfile()->getBillingAddress()->getStreet(),
            'city' => $this->getProfile()->getBillingAddress()->getCity(),
            'state' => $this->getBillingState(),
            'zip' => $this->getProfile()->getBillingAddress()->getZipcode(),
            'country' => $this->getCountryField($this->getProfile()->getBillingAddress()),
            'email' => $this->getProfile()->getLogin(),
            'phone' => $this->getProfile()->getBillingAddress()->getPhone(),
            'fixed' => 'Y',
            'id_type' => '1',
            'sh_cost' => $this->getFormattedPrice($this->getOrder()->getSurchargeSumByType('SHIPPING')),
        );

        if ($shippingAddress = $this->getProfile()->getShippingAddress()) {

            $fields += array(
                'ship_name' => $this->getName($shippingAddress),
                'ship_street_address' => $shippingAddress->getStreet(),
                'ship_city' => $shippingAddress->getCity(),
                'ship_state' => $this->getShippingState(),
                'ship_zip' => $shippingAddress->getZipcode(),
                'ship_country' => $this->getCountryField($shippingAddress),
            );
        }

        if ('test' === $this->getSetting('mode')) {

            $fields['demo'] = 'Y';
        }

        $i = -1;

        foreach ($this->getOrder()->getItems() as $item) {

            $product = $item->getProduct();

            $i++;
            $suffix = 0 == $i ? '' : ('_' . $i);

            $description = $product->getCommonDescription() ?: $product->getName();

            $fields['c_prod' . $suffix] = $product->getProductId() . ',' . $item->getAmount();
            $fields['c_name' . $suffix] = substr($product->getName(), 0, 127);
            $fields['c_price' . $suffix] = $this->getFormattedPrice($item->getPrice());
            $fields['c_description' . $suffix] = strip_tags(substr(($description), 0, 254));
        }

        static::log(
            array('fields' => $fields)
        );

        return $fields;
    }

    /**
     * Get allowed currencies
     *
     * @param \XLite\Model\Payment\Method $method Payment method
     *
     * @return array
     */
    protected function getAllowedCurrencies(\XLite\Model\Payment\Method $method)
    {
        //return true;
        array_merge(
            parent::getAllowedCurrencies($method),
            array($method->getSetting("NZD"))
        );
    }

    /**
     * Logging the data under 2Checkout
     * Available if developer_mode is on in the config file
     *
     * @param mixed $data
     *
     * @return void
     */
    protected static function log($data)
    {
        if (LC_DEVELOPER_MODE) {
            \XLite\Logger::logCustom('Paystation', $data);
        }
    }

    private function _transactionVerification($paystationID, $transactionID, $merchantSession)
    {
        $transactionVerified = '';
        $lookupXML = $this->_quickLookup($paystationID, 'ms', $merchantSession);
        $p = xml_parser_create();
        xml_parse_into_struct($p, $lookupXML, $vals, $tags);
        xml_parser_free($p);
        foreach ($tags as $key => $val) {
            if ($key == "PAYSTATIONERRORCODE") {
                for ($i = 0; $i < count($val); $i++) {
                    $responseCode = $this->_parseCode($vals);
                    $transactionVerified = $responseCode;
                }
            } else continue;
        }

        return $transactionVerified;
    }

    private function _quickLookup($pi, $type, $value)
    {
        $url = "https://www.paystation.co.nz/lookup/quick/?pi=$pi&$type=$value";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    private function _parseCode($mvalues)
    {
        $result = '';
        for ($i = 0; $i < count($mvalues); $i++) {
            if (!strcmp($mvalues[$i]["tag"], "QSIRESPONSECODE") && isset($mvalues[$i]["value"])) {
                $result = $mvalues[$i]["value"];
            }
        }
        return $result;
    }

    protected function logRedirect(array $list)
    {
        \XLite\Logger::getInstance()->log(
            $this->transaction->getPaymentMethod()->getServiceName() . ' payment gateway : redirect' . PHP_EOL
            . 'Method: ' . $this->getFormMethod() . PHP_EOL
            . 'Data: ' . var_export($list, true),
            LOG_DEBUG
        );
    }

    public function getCheckoutTemplate(\XLite\Model\Payment\Method $method)
    {
        return 'modules/Paystation/ThreeParty/checkout.twig';
    }
}
