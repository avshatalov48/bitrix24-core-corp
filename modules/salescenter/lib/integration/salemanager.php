<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Cashbox\Internals\CashboxTable;
use Bitrix\Sale\PaySystem\ServiceResult;
use Bitrix\Sale;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale\Payment;
use Bitrix\Main\Loader;
use Bitrix\Sale\PaySystem\ApplePay;
use Bitrix\Sale\PaySystem\ClientType;
use Bitrix\SalesCenter;
use Bitrix\Salescenter\Analytics;
use Bitrix\Salescenter\SaleshubItem;
use Bitrix\Crm\Service;

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

		$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
		/** @var Sale\Order $orderClass */
		$orderClass = $registry->getOrderClassName();

		$orderId = (int)$orderId;
		$sessionId = (int)$sessionId;
		$order = $orderClass::load($orderId);
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
	 * @param $dealId
	 * @return string
	 */
	public function getDealLink($dealId): string
	{
		return \CComponentEngine::MakePathFromTemplate(
			Option::get('crm', 'path_to_deal_details'),
			[
				'deal_id' => $dealId,
			]
		);
	}

	/**
	 * @param $orderId
	 * @return string
	 */
	public function getOrderLink($orderId)
	{
		return '/saleshub/orders/order/?orderId='.$orderId;
	}

	/**
	 * @param $paymentId
	 * @return string
	 */
	public function getPaymentLink($paymentId): string
	{
		return Loader::includeModule('crm')
			? Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()->getPaymentDetailsLink($paymentId)
			: '';
	}

	// region event handlers
	/**
	 * @param Event $event
	 * @return EventResult|void
	 */
	public static function onPaymentPaid(Event $event)
	{
		$parameters = $event->getParameters();

		/** @var Crm\Order\Payment $payment */
		$payment = $parameters['ENTITY'];
		if (!$payment instanceof Crm\Order\Payment)
		{
			return;
		}

		$result = ImOpenLinesManager::getInstance()->sendPaymentPayNotify($payment);
		if (!$result->isSuccess())
		{
			return new EventResult(EventResult::ERROR,null,'sale');
		}

		return new EventResult( EventResult::SUCCESS, null, 'sale');
	}

	public static function onSalescenterPaymentCreated(Payment $payment): void
	{
		$constructor = new Analytics\LabelConstructor();

		$event = $constructor->getAnalyticsEventForPayment('payment_created', $payment);
		$event->send();
	}

	/**
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onSalePsServiceProcessRequestBeforePaid(Event $event)
	{
		$result = new EventResult(EventResult::SUCCESS, null, 'sale');

		try
		{
			/** @var Payment $payment */
			$payment = $event->getParameter('payment');
			$order = $payment->getOrder();

			$constructor = new Analytics\LabelConstructor();

			$analyticsEvent = $constructor->getAnalyticsEventForPayment('payment_proceeded', $payment);
			$paySystemName = $constructor->getPaySystemTag($payment);
			$analyticsEvent->setP1('paysystem_' . $paySystemName);
			$analyticsEvent->setP2('sum_' . $payment->getSum());
			$analyticsEvent->setP3('currency_' . $payment->getField('CURRENCY'));

			$analyticsEvent->send();

			// legacy analytics, to be removed later
			AddEventToStatFile('salescenter', 'salescenterPayment', $payment->getId(), $constructor->getPaySystemTag($payment), 'pay_system');
			AddEventToStatFile('salescenter', 'salescenterPayment', $payment->getId(), $payment->getSum(), 'amount');
			AddEventToStatFile('salescenter', 'salescenterPayment', $payment->getId(), $payment->getField('CURRENCY'), 'currency');
			AddEventToStatFile('salescenter', 'salescenterPayment', $payment->getId(), $constructor->getContextLabel($order), 'context');

			if ($order instanceof Crm\Order\Order)
			{
				AddEventToStatFile('salescenter', 'salescenterPayment', $payment->getId(), $constructor->getChannelLabel($order), 'channel');
				AddEventToStatFile('salescenter', 'salescenterPayment', $payment->getId(), $constructor->getChannelNameLabel($order), 'channel_name');
			}
		}
		finally
		{
			return $result;
		}
	}

	/**
	 * @param Event $event
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function OnSaleOrderSaved(Event $event)
	{
		/** @var Crm\Order\Order $order */
		$order = $event->getParameter('ENTITY');

		// legacy analytics, to be removed later
		if ($order->isNew())
		{
			$constructor = new Analytics\LabelConstructor();

			AddEventToStatFile('salescenter', 'orderCreate', $order->getId(), $constructor->getContextLabel($order), 'context');
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

		$check = Sale\Cashbox\CheckManager::getObjectById($checkId);
		if(!$check)
		{
			return $result;
		}

		$orderId = (int)$check->getField('ORDER_ID');
		if($orderId <= 0)
		{
			return $result;
		}

		$paymentId = (int)$check->getField('PAYMENT_ID');
		if ($paymentId <= 0)
		{
			return $result;
		}

		$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
		/** @var Crm\Order\Order $orderClass */
		$orderClass = $registry->getOrderClassName();

		$order = $orderClass::load($orderId);
		if(!$order)
		{
			return $result;
		}

		$payment = $order->getPaymentCollection()->getItemById($paymentId);
		if ($payment instanceof Crm\Order\Payment)
		{
			ImOpenLinesManager::getInstance()->sendPaymentCheckNotifyError($checkId, $payment, $message);
		}

		return $result;
	}

	/**
	 * @param Event $event
	 * @return EventResult|null
	 */
	public static function OnPrintableCheckSend(Event $event): ?EventResult
	{
		$checkId = 0;
		$check = $event->getParameter('CHECK');
		if (is_array($check) && isset($check['ID']))
		{
			$checkId = (int)$check['ID'];
		}

		if ($checkId <= 0)
		{
			return null;
		}

		/** @var Crm\Order\Payment $payment */
		$payment = $event->getParameter('PAYMENT');
		if (!($payment instanceof Crm\Order\Payment))
		{
			return null;
		}

		$order = $payment->getOrder();

		$ownerId = 0;
		$entityBinding = $order->getEntityBinding();
		if ($entityBinding)
		{
			$ownerId = $entityBinding->getOwnerId();
		}

		$sessionIds = ImOpenLinesManager::getInstance()->getSessionIdsByUserId($order->getUserId());
		foreach ($sessionIds as $sessionId)
		{
			$crmInfo = ImOpenLinesManager::getInstance()->setSessionId($sessionId)->getCrmInfo();
			if (!empty($crmInfo['DEAL']) && (int)$crmInfo['DEAL'] === $ownerId)
			{
				ImOpenLinesManager::getInstance()->sendPaymentCheckNotify($checkId, $payment);
				return new EventResult( EventResult::SUCCESS, null, 'sale');
			}
		}

		return null;
	}

	/**
	 * @param Sale\Order $order
	 * @return string
	 */
	public function getOrderPayStatus(Sale\Order $order)
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
	 * @param Sale\Order $order
	 * @return bool|Payment
	 */
	protected function getOrderPrimaryPayment(Sale\Order $order)
	{
		$payments = $order->getPaymentCollection();
		foreach($payments as $payment)
		{
			return $payment;
		}

		return false;
	}

	/**
	 * @param Payment $payment
	 * @return string
	 */
	public function getPaymentPayStatus(Sale\Payment $payment)
	{
		if ($payment->isPaid())
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
	 * @param Sale\Order $order
	 * @return bool|mixed|null|string|string[]
	 */
	public function getOrderFormattedPrice(Sale\Order $order)
	{
		return SaleFormatCurrency($order->getPrice(), $order->getField('CURRENCY'));
	}

	/**
	 * @param Sale\Order $order
	 * @return false|string
	 */
	public function getOrderFormattedDiscountPrice(Sale\Order $order)
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
	 * @param Payment $payment
	 * @return bool|mixed|null|string|string[]
	 */
	public function getPaymentFormattedPrice(Sale\Payment $payment)
	{
		return SaleFormatCurrency($payment->getSum(), $payment->getField('CURRENCY'));
	}

	/**
	 * @param Sale\Order $order
	 * @return string
	 */
	public function getOrderFormattedInsertDate(Sale\Order $order)
	{
		return FormatDate('j F', $order->getDateInsert());
	}

	/**
	 * @param Payment $payment
	 * @return string
	 */
	public function getPaymentFormattedInsertDate(Sale\Payment $payment)
	{
		return FormatDate('j F', $payment->getField('DATE_BILL'));
	}

	/**
	 * @return array
	 */
	public function getPaySystemFilter()
	{
		return [
			'ACTIVE' => 'Y',
			'!=ACTION_FILE' => [
				'inner',
			],
		];
	}

	/**
	 * @param array $additionalFilter
	 * @param int $limit
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public function getPaySystemList(array $additionalFilter = [], $limit = 0) : array
	{
		$filter = [
			'=ENTITY_REGISTRY_TYPE' => Sale\Registry::REGISTRY_TYPE_ORDER,
			'=ACTIVE' => 'Y',
		];
		if ($additionalFilter)
		{
			$filter = array_merge($filter, $additionalFilter);
		}
		$params = [
			'select' => ['ID', 'NAME', 'ACTION_FILE', 'PS_MODE', 'PS_CLIENT_TYPE', 'SORT'],
			'filter' => $filter,
			'order' => ['ID' => 'DESC'],
		];

		if ($limit > 0)
		{
			$params['limit'] = (int)$limit;
		}

		$dbRes = Sale\PaySystem\Manager::getList($params);

		$result = [];
		while ($item = $dbRes->fetch())
		{
			$item['PS_CLIENT_TYPE'] = $item['PS_CLIENT_TYPE'] ?: ClientType::DEFAULT;

			$result[$item['ID']] = $item;
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public static function getCashboxHandlers()
	{
		$result = [];
		$zone = '';
		$isCloud = Main\Loader::includeModule("bitrix24");
		if ($isCloud)
		{
			$zone = \CBitrix24::getLicensePrefix();
		}
		elseif (Main\Loader::includeModule('intranet'))
		{
			$zone = \CIntranetUtils::getPortalZone();
		}
		if ($zone === 'ru')
		{
			$result = array_merge($result, [
				'\Bitrix\Sale\Cashbox\CashboxAtolFarmV4',
				'\Bitrix\Sale\Cashbox\CashboxAtolFarmV5',
				'\Bitrix\Sale\Cashbox\CashboxOrangeData',
				'\Bitrix\Sale\Cashbox\CashboxOrangeDataFfd12',
				'\Bitrix\Sale\Cashbox\CashboxBusinessRu',
				'\Bitrix\Sale\Cashbox\CashboxBusinessRuV5',
				'\Bitrix\Sale\Cashbox\CashboxYooKassa',
			]);
		}
		if ($zone === 'ua' || ($zone === 'ru' && !$isCloud))
		{
			$result[] ='\Bitrix\Sale\Cashbox\CashboxCheckbox';
		}

		$result[] = '\Bitrix\Sale\Cashbox\CashboxRest';

		$cashboxList = Sale\Cashbox\Manager::getListFromCache();
		foreach ($cashboxList as $cashbox)
		{
			if ($cashbox['ACTIVE'] === 'N')
			{
				continue;
			}

			if ($cashbox['HANDLER'] === '\\' . Sale\Cashbox\CashboxRobokassa::class)
			{
				$result[] = $cashbox['HANDLER'];
			}
		}

		return $result;
	}

	/**
	 * @param bool $activeOnly
	 * @return array
	 */
	public function getCashboxFilter($activeOnly = true)
	{
		$filter = [
			'=HANDLER' => static::getCashboxHandlers(),
		];

		if($activeOnly)
		{
			$filter['=ACTIVE'] = 'Y';
		}

		return $filter;
	}

	/**
	 * @param int $limit
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getCashboxList($limit = 0) : array
	{
		$params = [
			'select' => ['ID', 'NAME', 'HANDLER', 'SETTINGS'],
			'filter' => $this->getCashboxFilter(true),
			'order' => ['ID' => 'DESC']
		];

		if ($limit > 0)
		{
			$params['limit'] = $limit;
		}

		$dbRes = Sale\Cashbox\Manager::getList($params);

		$result = [];

		while ($item = $dbRes->fetch())
		{
			if (isset($item['SETTINGS']['REST']['REST_CODE']))
			{
				$item['REST_CODE'] = $item['SETTINGS']['REST']['REST_CODE'];
				unset($item['SETTINGS']);
			}
			$result[$item['ID']] = $item;
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function isManagerAccess(bool $checkSalePermissions = false)
	{
		$saleModulePermissions = \CMain::GetGroupRight('sale');

		$access =
			$this->isEnabled()
			&& Loader::includeModule('crm')
			&& \CCrmSaleHelper::isShopAccess()
		;

		if ($checkSalePermissions)
		{
			$access = $access && $saleModulePermissions >= 'U';
		}

		return $access;
	}

	/**
	 * @return bool
	 */
	public function isFullAccess(bool $checkSalePermissions = false)
	{
		$saleModulePermissions = \CMain::GetGroupRight('sale');

		$access =
			$this->isManagerAccess()
			&& \CCrmSaleHelper::isShopAccess('admin')
		;

		if ($checkSalePermissions)
		{
			$access = $access && $saleModulePermissions >= 'W';
		}

		return $access;
	}

	/**
	 * @param Sale\Order $order
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function isOrderContactPropertiesFilled(Sale\Order $order)
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
	 * @param Sale\Order $order
	 * @return string|false
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getOrderCheckWarning(Sale\Order $order)
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
	 * @param Sale\Order $order
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected function isCheckPublicUrlAvailable(Sale\Order $order)
	{
		$documents = Sale\Cashbox\CheckManager::collateDocuments($this->getOrderEntities($order));
		$document = current($documents);

		if ($document)
		{
			$check = Sale\Cashbox\CheckManager::createByType($document['TYPE']);
			if ($check)
			{
				$check->setEntities($document['ENTITIES']);
				$check->setRelatedEntities($document['RELATED_ENTITIES']);

				$result = Sale\Cashbox\Manager::getAvailableCashboxList($check);
				foreach($result as $cashbox)
				{
					if(!empty($cashbox['OFD']))
					{
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * @param Sale\Order $order
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected function getOrderEntities(Sale\Order $order)
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

	/**
	 * @param array $paySystem
	 * @return bool
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 */
	public function isApplePayPayment(array $paySystem): bool
	{
		if (!Loader::includeModule("sale"))
		{
			return false;
		}

		return ApplePay::isApplePaySystem($paySystem);
	}

	public function isTelegramOrder(Crm\Order\Order $order): bool
	{
		$collection = $order->getTradeBindingCollection();

		/** @var Crm\Order\TradeBindingEntity $binding */
		foreach ($collection as $binding)
		{
			$platform = $binding->getTradePlatform();
			if (
				$platform
				&& $platform->getCode() === Crm\Order\TradingPlatform\Telegram\Telegram::TRADING_PLATFORM_CODE
			)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public static function getSaleshubPaySystemItems(): array
	{
		$cacheTTL = 86400;
		$cacheId = "salescenter_paysystem_items";
		$cachePath = "/salescenter/saleshubpaysystemitems/";
		$cache = Main\Application::getInstance()->getCache();
		if($cache->initCache($cacheTTL, $cacheId, $cachePath))
		{
			$result = $cache->getVars();
		}
		else
		{
			$result = [];
			$paysystemItems = SalesCenter\SaleshubItem::getPaysystemItems();
			foreach ($paysystemItems as $paysystemItem)
			{
				$values = [
					'sort' => $paysystemItem['SORT'],
					'main' => $paysystemItem['MAIN'] ? true : false,
					'recommendation' => $paysystemItem['RECOMMENDATION'] ? true : false,
					'slider' => $paysystemItem['SLIDER'] ? true : false,
				];

				if ($sliderSort = (int)$paysystemItem['SLIDER_SORT'])
				{
					$values['sliderSort'] = $sliderSort;
				}

				if ($psMode = $paysystemItem['PS_MODE'])
				{
					$result[$paysystemItem['HANDLER']]['psMode'][$psMode] = $values;
				}
				else
				{
					$result[$paysystemItem['HANDLER']] = $values;
				}
			}

			if($result)
			{
				$cache->startDataCache();
				$cache->endDataCache($result);
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public static function getSaleshubSmsProviderItems(): array
	{
		$cacheTTL = 86400;
		$cacheId = "salescenter_smsprovider_items";
		$cachePath = "/salescenter/saleshubsmsprovideritems/";
		$cache = Main\Application::getInstance()->getCache();
		if($cache->initCache($cacheTTL, $cacheId, $cachePath))
		{
			$result = $cache->getVars();
		}
		else
		{
			$result = [];
			$smsProviderItems = SaleshubItem::getSmsProviderItems();
			foreach ($smsProviderItems as $providerItem)
			{
				$providerId = $providerItem['PROVIDER'];

				$values = [
					'recommendation' => $providerItem['RECOMMENDATION'] === 'Y',
					'sort' => $providerItem['SORT'],
					'main' => $providerItem['MAIN'] === 'Y',
				];

				$result[$providerId] = $values;
			}

			if($result)
			{
				$cache->startDataCache();
				$cache->endDataCache($result);
			}
		}

		return $result;
	}

	public function getEmptyDeliveryServiceId()
	{
		if (!$this->isEnabled())
		{
			return 0;
		}

		return Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
	}

	public function isAvailableCorrection()
	{
		if ($this->isEnabled())
		{
			return Sale\Cashbox\CheckManager::isAvailableCorrection();
		}

		return false;

	}

	/**
	 * Send message to user when success payment appear
	 *
	 * @return void
	 */
	public static function onPaySystemServiceProcessRequest(Event $event): void
	{
		$payment = $event->getParameter('payment');
		$serviceResult = $event->getParameter('serviceResult');

		if (
			!($payment instanceof Payment)
			|| !($serviceResult instanceof ServiceResult)
		)
		{
			return;
		}

		if (
			$serviceResult->isSuccess()
			&& $serviceResult->getOperationType() === ServiceResult::MONEY_COMING
			&& CrmManager::getInstance()->isPaymentFromTerminal($payment)
		)
		{
			CrmManager::getInstance()->sendPaymentSlipBySms($payment);
		}
	}
}