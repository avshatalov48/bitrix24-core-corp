<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Cashbox\Internals\CashboxTable;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Main\Loader;
use Bitrix\Sale\PaymentCollection;

class SaleManager extends Base
{
	/**
	 * @return string
	 */
	protected function getModuleName()
	{
		return 'sale';
	}

	/**
	 * @param $orderId
	 * @param $sessionId
	 * @return Result
	 */
	public static function pushOrder($orderId, $sessionId)
	{
		$result = new Result();
		if(!static::getInstance()->isEnabled())
		{
			return $result;
		}
		$orderId = (int)$orderId;
		$sessionId = (int)$sessionId;
		$order = Order::load($orderId);
		if(!$order)
		{
			$result->addError(new Error('Order not found'));
			return $result;
		}
		if($orderId > 0 && $sessionId > 0)
		{
			PullManager::getInstance()->sendOrderAddEvent($orderId, $sessionId);
		}

		if(ImOpenLinesManager::getInstance()->isEnabled())
		{
			$dialogId = ImOpenLinesManager::getInstance()->setSessionId($sessionId)->getDialogId();
			if($dialogId)
			{
				$result = ImOpenLinesManager::getInstance()->sendOrderNotify($order, $dialogId, false);
			}
		}

		return $result;
	}

	/**
	 * @param $orderId
	 * @return string
	 */
	public function getOrderLink($orderId)
	{
		return '/saleshub/orders/order/?orderId='.$orderId;
	}

	// region event handlers
	/**
	 * @param Event $event
	 *
	 * @return EventResult
	 */
	public static function onSalePayOrder(Event $event)
	{
		$parameters = $event->getParameters();

		/** @var Order $order */
		$order = $parameters['ENTITY'];
		if (!$order instanceof Order)
		{
			return new EventResult(EventResult::ERROR,null,'sale');
		}

		if ($order->isPaid())
		{
			$result = ImOpenLinesManager::getInstance()->sendOrderPayNotify($order);
			if (!$result->isSuccess())
			{
				return new EventResult(EventResult::ERROR,null,'sale');
			}

		}

		return new EventResult( EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onSalePsServiceProcessRequestBeforePaid(Event $event)
	{
		$result = new EventResult( EventResult::SUCCESS, null, 'sale');

		try
		{
			$status = $event->getParameter('status');
			/** @var Payment $payment */
			$payment = $event->getParameter('payment');
			$paySystem = $payment->getPaySystem();
			$type = $paySystem->getField('ACTION_FILE');
			if($type === 'yandexcheckout')
			{
				$type = $paySystem->getField('PS_MODE');
			}

			static::getInstance()->addAnalyticsLabelToFile('salescenterPayment', $type, $status);
		}
		finally
		{
			return $result;
		}
	}

	public static function OnCheckPrintError(Event $event): EventResult
	{
		$result = new EventResult( EventResult::SUCCESS, null, 'sale');

		$parameters = $event->getParameters();
		if(empty($parameters) || !is_array($parameters))
		{
			return $result;
		}

		$data = reset($parameters);
		if(!is_array($data) || empty($data) || !isset($data['ID']) || !isset($data['ERROR']))
		{
			return $result;
		}

		$checkId = (int) $data['ID'];
		if($checkId <= 0)
		{
			return $result;
		}

		$error = $data['ERROR'];
		if(!is_array($error) || !isset($error['MESSAGE']))
		{
			return $result;
		}

		$message = (string) $error['MESSAGE'];
		if(empty($message))
		{
			return $result;
		}

		$check = \Bitrix\Sale\Cashbox\CheckManager::getObjectById($checkId);
		if(!$check)
		{
			return $result;
		}

		$orderId = (int)$check->getField('ORDER_ID');
		if($orderId <= 0)
		{
			return $result;
		}

		$order = Order::load($orderId);
		if(!$order)
		{
			return $result;
		}

		ImOpenLinesManager::getInstance()->sendOrderCheckNotifyError($checkId, $order, $message);

		return $result;
	}

	public static function OnPrintableCheckSend(Event $event): EventResult
	{
		$result = new EventResult( EventResult::SUCCESS, null, 'sale');

		$checkId = 0;
		$check = $event->getParameter('CHECK');
		if(is_array($check) && isset($check['ID']))
		{
			$checkId = (int)$check['ID'];
		}
		if($checkId <= 0)
		{
			return $result;
		}
		$payment = $event->getParameter('PAYMENT');
		if($payment && $payment instanceof Payment)
		{
			$paymentCollection = $payment->getCollection();
			if($paymentCollection && $paymentCollection instanceof PaymentCollection)
			{
				$order = $paymentCollection->getOrder();
				if($order instanceof Order)
				{
					ImOpenLinesManager::getInstance()->sendOrderCheckNotify($checkId, $order);
				}
			}
		}

		return $result;
	}

	private function addAnalyticsLabelToFile($action, $tag, $label)
	{
		if(!function_exists('AddEventToStatFile'))
		{
			function AddEventToStatFile($module, $action, $tag, $label)
			{
				static $search = array("\t", "\n", "\r");
				static $replace = " ";
				if (defined('ANALYTICS_FILENAME') && is_writable(ANALYTICS_FILENAME))
				{
					$content =
						date('Y-m-d H:i:s')
						."\t".str_replace($search, $replace, $_SERVER["HTTP_HOST"])
						."\t".str_replace($search, $replace, $module)
						."\t".str_replace($search, $replace, $action)
						."\t".str_replace($search, $replace, $tag)
						."\t".str_replace($search, $replace, $label)
						."\n";
					$fp = @fopen(ANALYTICS_FILENAME, "ab");
					if ($fp)
					{
						if (flock($fp, LOCK_EX))
						{
							@fwrite($fp, $content);
							@fflush($fp);
							@flock($fp, LOCK_UN);
							@fclose($fp);
						}
					}
				}
			}
		}

		AddEventToStatFile('salescenter', $action, $tag, $label);
	}
	//endregion

	/**
	 * @param Order $order
	 * @return string
	 */
	public function getOrderPayStatus(Order $order)
	{
		$payment = false;
		if($order->isPaid())
		{
			$payment = $this->getOrderPrimaryPayment($order);
		}
		if($payment)
		{
			$status = Loc::getMessage('SALESCENTER_SALEMANAGER_SYSTEM_ORDER_PAID_TEXT', [
				'#PAYSYSTEM#' => $payment->getPaymentSystemName(),
				'#DATE#' => FormatDate('j F', $payment->getField('DATE_PAID')),
			]);
		}
		else
		{
			$status = Loc::getMessage('SALESCENTER_SALEMANAGER_SYSTEM_ORDER_NOT_PAID_TEXT');
		}

		return $status;
	}

	/**
	 * @param Order $order
	 * @return bool|Payment
	 */
	protected function getOrderPrimaryPayment(Order $order)
	{
		$payments = $order->getPaymentCollection();
		foreach($payments as $payment)
		{
			return $payment;
		}

		return false;
	}

	/**
	 * @param Order $order
	 * @return bool|mixed|null|string|string[]
	 */
	public function getOrderFormattedPrice(Order $order)
	{
		return SaleFormatCurrency($order->getPrice(), $order->getField('CURRENCY'));
	}

	/**
	 * @param Order $order
	 * @return false|string
	 */
	public function getOrderFormattedDiscountPrice(Order $order)
	{
		$discountPrice = 0;
		foreach($order->getBasket() as $item)
		{
			$discountPrice += ($item->getField('DISCOUNT_PRICE') * $item->getField('QUANTITY'));
		}
		if($discountPrice > 0)
		{
			return SaleFormatCurrency($discountPrice, $order->getField('CURRENCY'));
		}

		return false;
	}

	/**
	 * @param Order $order
	 * @return string
	 */
	public function getOrderFormattedInsertDate(Order $order)
	{
		return FormatDate('j F', $order->getDateInsert());
	}

	/**
	 * @return array
	 */
	public function getPaySystemFilter()
	{
		$paySystemHandlerList = [
			'cash',
			'paypal',
			'sberbankonline',
			'qiwi',
			'webmoney',
			'liqpay',
			'uapay',
		];

		$yandexPaySystemHandler = [
			'yandexcheckout'
		];

		$yandexPaySystemMode = [
			'bank_card',
			'sberbank',
			'sberbank_sms',
			'alfabank',
			'yandex_money',
			'webmoney',
			'qiwi'
		];

		return [
			'ACTIVE' => 'Y',
			[
				'LOGIC' => 'OR',
				[
					'=ACTION_FILE' => $paySystemHandlerList,
				],
				[
					'=ACTION_FILE' => $yandexPaySystemHandler,
					'=PS_MODE' => $yandexPaySystemMode,
				]
			],

		];
	}

	/**
	 * @return array
	 */
	public function getCashboxHandlers()
	{
		return [
			'\Bitrix\Sale\Cashbox\CashboxAtolFarmV4',
			'\Bitrix\Sale\Cashbox\CashboxOrangeData',
		];
	}

	/**
	 * @param bool $activeOnly
	 * @return array
	 */
	public function getCashboxFilter($activeOnly = true)
	{
		$filter = [
			'=HANDLER' => $this->getCashboxHandlers(),
		];

		if($activeOnly)
		{
			$filter['=ACTIVE'] = 'Y';
		}

		return $filter;
	}

	/**
	 * @return bool
	 */
	public function isManagerAccess()
	{
		return $this->isEnabled() && Loader::includeModule("crm") && \CCrmSaleHelper::isShopAccess();
	}

	/**
	 * @return bool
	 */
	public function isFullAccess()
	{
		return $this->isManagerAccess() && \CCrmSaleHelper::isShopAccess('admin');
	}

	/**
	 * @param Order $order
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function isOrderContactPropertiesFilled(Order $order)
	{
		$email = null;
		$emailProperty = $order->getPropertyCollection()->getUserEmail();
		if($emailProperty)
		{
			$email = $emailProperty->getValue();
		}

		return !empty($email);
	}

	/**
	 * @param Order $order
	 * @return string|false
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getOrderCheckWarning(Order $order)
	{
		$result = false;

		$filter = $this->getCashboxFilter();
		if(CashboxTable::getCount($filter) <= 0)
		{
			$result = Loc::getMessage('SALESCENTER_SALEMANAGER_SYSTEM_ORDER_NO_CASHBOXES_WARNING');
		}
		elseif(!$this->isOrderContactPropertiesFilled($order))
		{
			if($this->isCheckPublicUrlAvailable($order))
			{
				if(empty($this->getAdminEmail()))
				{
					$result = Loc::getMessage('SALESCENTER_SALEMANAGER_SYSTEM_ORDER_EMAIL_WARNING');
				}
				else
				{
					$result = Loc::getMessage('SALESCENTER_SALEMANAGER_SYSTEM_ORDER_NO_CONTACT_EMAIL_WARNING');
				}
			}
			else
			{
				$result = Loc::getMessage('SALESCENTER_SALEMANAGER_SYSTEM_ORDER_NO_CHECK_URL_WARNING');
			}
		}

		return $result;
	}

	/**
	 * @param Order $order
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected function isCheckPublicUrlAvailable(Order $order)
	{
		$firstIteration = true;
		$result = [];

		foreach($this->getOrderEntities($order) as $entity)
		{
			$cashboxList = \Bitrix\Sale\Cashbox\Manager::getListWithRestrictions($entity);
			if ($firstIteration)
			{
				$result = $cashboxList;
				$firstIteration = false;
				continue;
			}

			$result = array_intersect_assoc($result, $cashboxList);
		}

		foreach($result as $cashbox)
		{
			if(!empty($cashbox['OFD']))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param Order $order
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected function getOrderEntities(Order $order)
	{
		$entities = [];

		foreach($order->getPaymentCollection() as $payment)
		{
			$entities[] = $payment;
		}

		foreach($order->getShipmentCollection() as $shipment)
		{
			$entities[] = $shipment;
		}

		return $entities;
	}

	/**
	 * @return string
	 */
	protected function getAdminEmail()
	{
		return Option::get('main', 'email_from');
	}
}