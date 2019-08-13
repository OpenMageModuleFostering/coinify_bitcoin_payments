<?php

class Coinify_Coinify_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_API_KEY = 'payment/coinify/coinify_api_key';
    const XML_PATH_API_SECRET = 'payment/coinify/coinify_api_secret';


    const XML_PATH_DEBUG = 'payment/coinify/debug';
    const API_HEADER_TEMPLATE = "Authorization: Coinify apikey=\"%s\", nonce=\"%s\", signature=\"%s\"";
    const LOG_FILE = "coinify.log";

    const ORDER_STATUS_OVERPAID = 'coinify_overpaid';
    const ORDER_STATUS_PARTIALLY_PAID = 'coinify_partially_paid';


    /**
     * @return bool
     */
    public function canUseCheckout()
    {
        return $this->getApiKey() && $this->getApiSecret();
    }

    /**
     * @return mixed
     */
    public function getApiKey()
    {
        return Mage::getStoreConfig(self::XML_PATH_API_KEY);
    }

    /**
     * @return mixed
     */
    public function getApiSecret()
    {
        return Mage::getStoreConfig(self::XML_PATH_API_SECRET);
    }

    /**
     * @return string
     */
    protected function generateNonce()
    {
        $mt = explode(' ', microtime());
        return $mt[1] . substr($mt[0], 2, 6);
    }

    /**
     * @param $nonce
     * @return string
     */
    public function getSignature($nonce)
    {
        $message = $nonce . $this->getApiKey();
        return strtolower( hash_hmac('sha256', $message, $this->getApiSecret(), false ) );
    }

    /**
     * @return string
     */
    public function getAuthHeader()
    {
        $nonce = $this->generateNonce();
        return sprintf(self::API_HEADER_TEMPLATE, $this->getApiKey(), $nonce, $this->getSignature($nonce));
    }

    /**
     * @return string
     */
    public function getModuleVersion()
    {
        return (string) Mage::getConfig()->getNode('modules')->Coinify_Coinify->version;
    }

    /**
     * @return mixed
     */
    public function isDebugMode()
    {
        return Mage::getStoreConfig(self::XML_PATH_DEBUG);
    }

    /**
     * @param $data
     * @param null $title
     */
    public function debug($data, $title = null)
    {
        if (!$this->isDebugMode()){
            return;
        }
        if ($title) {
            Mage::log($title, null, self::LOG_FILE, true);
        }
        Mage::log($data, null, self::LOG_FILE, true);
    }

    /**
     * @param $data
     */
    public function convertToArray(&$data)
    {
        if (is_object($data)) {
            $data =  get_object_vars($data);
        }
        foreach($data as $key => $value) {
            if (is_object($value)) {
                $data[$key] = get_object_vars($value);
                $this->convertToArray($data[$key]);
            }
        }
    }


}