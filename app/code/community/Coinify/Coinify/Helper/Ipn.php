<?php

class Coinify_Coinify_Helper_Ipn
{
    const XML_PATH_IPN_SECRET = 'payment/coinify/coinify_ipn_secret';

    /**
     * @param $header
     * @param $body
     * @return bool
     */
    public function verifyIpnSignature($header, $body)
    {
        $ipnSecret = Mage::getStoreConfig(self::XML_PATH_IPN_SECRET);
        $expectedSignature = strtolower(hash_hmac('sha256', $body, $ipnSecret, false));
        return $header == $expectedSignature;
    }


    public function getOrder($data)
    {
        if(!isset($data["data"]["custom"]["orderId"])) {
            return null;
        }
        $orderId = $data["data"]["custom"]["orderId"];
        return Mage::getModel('sales/order')->loadByIncrementId($orderId);
    }

    /**
     * @param $data
     * @return string
     */
    public function getState($data)
    {
        if(!isset($data["data"]["state"])) {
            return "";
        }
        return $data["data"]["state"];
    }

    /**
     * @param $data
     * @return string
     */
    public function getAmount($data)
    {
        if(!isset($data["data"]["bitcoin"]["amount"])) {
            return "";
        }
        return floatval($data["data"]["bitcoin"]["amount"]);
    }

    /**
     * @param $data
     * @return string
     */
    public function getAmountPaid($data)
    {
        if(!isset($data["data"]["bitcoin"]["amount_paid"])) {
            return "";
        }
        return floatval($data["data"]["bitcoin"]["amount_paid"]);
    }
}