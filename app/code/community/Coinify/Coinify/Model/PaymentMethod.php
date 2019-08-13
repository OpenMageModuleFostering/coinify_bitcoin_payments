<?php

class Coinify_Coinify_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
	const PLUGIN_NAME = "Magento Coinify";
	const API_URL = "https://api.coinify.com/v3/invoices";
	protected $_code = 'coinify';

	/**
	 * Payment Method features
	 * @var bool
	 */
	protected $_isGateway               = false;
	protected $_canAuthorize            = false;
	protected $_canCapture              = false;
	protected $_canOrder				= true;
	protected $_canCapturePartial       = false;
	protected $_canRefund               = false;
	protected $_canVoid                 = false;
	protected $_canUseInternal          = false;
	protected $_canUseCheckout          = true;
	protected $_canUseForMultishipping  = true;
	protected $_canSaveCc 				= false;
	protected $_isInitializeNeeded		= true;

	/**
	 * @return Coinify_Coinify_Helper_Data
	 */
	protected function getHelper()
	{
		return Mage::helper('coinify');
	}

	/**
	 * @return bool
	 */
	public function canUseCheckout()
	{
		return $this->getHelper()->canUseCheckout() && $this->_canUseCheckout;
	}


	/**
	 * @param string $paymentAction
	 * @param object $stateObject
	 * @return $this
	 */
	public function initialize($paymentAction, $stateObject)
	{
		if ($paymentAction == 'createCoinifyInvoiceAndRedirect') {
			$this->createCoinifyInvoiceAndRedirect($this->getInfoInstance());
			$stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
			$stateObject->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
		}
		return $this;
	}

	/**
	 * @param $paymentInfo
	 * @return mixed
	 * @throws Mage_Core_Exception
	 */
	protected function createCoinifyInvoiceAndRedirect($paymentInfo)
	{
		$url = self::API_URL;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($this->getHelper()->getAuthHeader()));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->getParams($paymentInfo)));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = json_decode(curl_exec($ch));
		$this->getHelper()->convertToArray($result);
		curl_close($ch);

		if (!isset($result['success']) || !$result['success'] || !isset($result['data']['payment_url'])) {
			$this->getHelper()->debug($result, "CREATE INVOICE RESPONSE:");
			Mage::throwException($this->getHelper()->__('Internal Error. Please try again later or choose another payment method'));
		}

		Mage::getSingleton('customer/session')->setData('coinify_redirect', $result['data']['payment_url']);
	}

	/**
	 * @param $paymentInfo
	 * @return array
	 */
	protected function getParams($paymentInfo)
	{
		/* @var $order Mage_Sales_Model_Order */
		$order = $paymentInfo->getOrder();

		$params = array(
			'amount' 		=> number_format($order->getBaseTotalDue(), 2, '.', ''),
			'currency'		=> $order->getBaseCurrencyCode(),
			'description'	=> $this->getDescription($order),
			'plugin_name' 	=> self::PLUGIN_NAME,
			'plugin_version'=> $this->getHelper()->getModuleVersion(),
			'callback_url' 	=> Mage::getUrl('coinify/callback/index'),
			'return_url' 	=> Mage::getUrl('checkout/onepage/success'),
			'cancel_url'	=> Mage::getUrl('checkout/onepage/failure'),
			'custom' 		=> array('orderId' => $order->getIncrementId())
		);

		return $params;
	}

	/**
	 * @param Mage_Sales_Model_Order $order
	 * @return string
	 */
	protected function getDescription(Mage_Sales_Model_Order $order)
	{
		$websiteName = $order->getStore()->getFrontendName();
		return sprintf("%s. Order# %s", $websiteName, $order->getIncrementId());
	}

	/**
	 * @return mixed
	 */
	public function getOrderPlaceRedirectUrl()
	{
		/** @var $session Mage_Checkout_Model_Session */
		$session = Mage::getSingleton('customer/session');
		$url = $session->getData('coinify_redirect');
		$session->unsetData('coinify_redirect');
		return $url;
	}

}

