<?php

class Coinify_Coinify_Block_Info extends Mage_Core_Block_Text
{
	const XML_PATH_PAYMENT_COINIFY_MESSAGE = 'payment/coinify/message';

	/**
	 * @return string
	 */
	protected function _toHtml()
	{
		$quote = Mage::getSingleton('checkout/session')->getQuote();
		if ($quote->getPayment()->getMethod() != "coinify") {
			return parent::_toHtml();
		}

		$message = Mage::getStoreConfig(self::XML_PATH_PAYMENT_COINIFY_MESSAGE);
		$text = "<div>{$message}</div>";
		$this->setText($text);
		return parent::_toHtml();
	}
}
