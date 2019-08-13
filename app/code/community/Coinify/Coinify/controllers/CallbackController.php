<?php

class Coinify_Coinify_CallbackController extends Mage_Core_Controller_Front_Action
{
	/**
	 * @throws Zend_Controller_Request_Exception
	 */
	public function indexAction()
	{
		$body = file_get_contents('php://input');
		$header = $this->getRequest()->getHeader("X-Coinify-Callback-Signature");

		/** @var $helper Coinify_Coinify_Helper_Data */
		$helper = Mage::helper('coinify');
		$helper->debug($body, "IPN Body");

		/** @var $ipnHelper Coinify_Coinify_Helper_Ipn */
		$ipnHelper = Mage::helper("coinify/ipn");
		if(!$ipnHelper->verifyIpnSignature($header, $body)) {
			$helper->debug("IPN Verification fail. please check INP Secret in Admin");
			return;
		}

		$data = Mage::helper("core")->jsonDecode($body);

		/** @var $order Mage_Sales_Model_Order */
		$order = $ipnHelper->getOrder($data);
		if (!$order)
		{
			$helper->debug("Empty order Id");
			return;
		}
		$state = $ipnHelper->getState($data);
		$expectedAmount = $ipnHelper->getAmount($data);
		$amountPaid = $ipnHelper->getAmountPaid($data);

		$helper->debug($state, "State parsed:");
		switch($state) {
			case 'expired':
				$this->_cancel($order, $amountPaid, $expectedAmount);
				break;
			case 'complete':
				$this->_pay($order, $amountPaid, $expectedAmount);
				break;
		}
	}

	/**
	 * @param Mage_Sales_Model_Order $order
	 * @param $amountPaid
	 * @param $expectedAmount
	 * @throws Exception
	 */
	protected function _cancel(Mage_Sales_Model_Order $order, $amountPaid, $expectedAmount)
	{
		if ($amountPaid > 0) {
			$status = Coinify_Coinify_Helper_Data::ORDER_STATUS_PARTIALLY_PAID;
			$state = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
			$comment = Mage::helper('coinify')->__("Partially Paid. Expected Payment: %f BTC. Received: %f BTC", $expectedAmount, $amountPaid);
		} else {
			$status = Mage_Sales_Model_Order::STATE_CANCELED;
			$state = Mage_Sales_Model_Order::STATE_CANCELED;
			$comment = '';
		}
		$order->setState($state, $status, $comment)->save();
		$order->save();
	}

	/**
	 * @param Mage_Sales_Model_Order $order
	 * @param $amountPaid
	 * @param $expectedAmount
	 * @throws Exception
	 */
	protected function _pay(Mage_Sales_Model_Order $order, $amountPaid, $expectedAmount)
	{
		if ($amountPaid > $expectedAmount) {
			$status = Coinify_Coinify_Helper_Data::ORDER_STATUS_OVERPAID;
			$comment = Mage::helper('coinify')->__("Overpaid Paid: Expected Payment: %f BTC. Received: %f BTC", $expectedAmount, $amountPaid);
		} else {
			$status = Mage_Sales_Model_Order::STATE_PROCESSING;
			$comment = '';
		}

		if (!Mage::getResourceModel('sales/order_invoice_collection')->setOrderFilter($order)->count()) {
			Mage::getModel('sales/order_invoice_api')->create($order->getIncrementId(), array());
		}
		/** @var $invoice Mage_Sales_Model_Order_Invoice */
		foreach($order->getInvoiceCollection() as $invoice) {
			$invoice->pay();
		}

		$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $status, $comment)->save();
		$order->sendNewOrderEmail();
		$order->setEmailSent(true);
		$order->save();
	}
}