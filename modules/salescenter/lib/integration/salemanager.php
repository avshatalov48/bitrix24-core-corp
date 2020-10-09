<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Cashbox\Internals\CashboxTable;
use Bitrix\Sale;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale\Payment;
use Bitrix\Main\Loader;
use Bitrix\Sale\PaymentCollection;
use Bitrix\SalesCenter;

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

		/** @var Crm\Order\Order $order */
		$order = $parameters['ENTITY'];
		if (!$order instanceof Crm\Order\Order)
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
		$result = new EventResult(EventResult::SUCCESS, null, 'sale');

		try
		{
			/** @var Payment $payment */
			$payment = $event->getParameter('payment');

			AddEventToStatFile('salescenter', 'salescenterPayment', $payment->getId(), self::getPaySystemTag($payment), 'pay_system');
			AddEventToStatFile('salescenter', 'salescenterPayment', $payment->getId(), $payment->getSum(), 'amount');
			AddEventToStatFile('salescenter', 'salescenterPayment', $payment->getId(), $payment->getField('CURRENCY'), 'currency');
			AddEventToStatFile('salescenter', 'salescenterPayment', $payment->getId(), self::getContextTag($payment->getOrder()), 'context');
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
		/** @var Sale\Order $payment */
		$order = $event->getParameter('ENTITY');

		if ($order->isNew())
		{
			AddEventToStatFile('salescenter', 'orderCreate', $order->getId(), self::getContextTag($order), 'context');
		}
	}

	/**
	 * @param Payment $payment
	 * @return string
	 */
	private static function getPaySystemTag(Payment $payment) : string
	{
		$service = $payment->getPaySystem();

		if ($service === null)
		{
			return '';
		}

		$tag = $service->getField('ACTION_FILE');
		if ($service->getField('PS_MODE'))
		{
			$tag .= ':'.$service->getField('PS_MODE');
		}

		return $tag;
	}

	/**
	 * @param Sale\Order $order
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private static function getContextTag(Sale\Order $order) : string
	{
		/** @var Sale\TradeBindingEntity $item */
		foreach ($order->getTradeBindingCollection() as $item)
		{
			/** @var Sale\TradingPlatform\Platform $platform */
			$platform = $item->getTradePlatform();
			if ($platform)
			{
				$info = $platform->getInfo();
				if (isset($info['XML_ID']))
				{
					return self::getValueByXmlId((string)$info['XML_ID']);
				}
			}
		}

		if (
			$order->getField('ID_1C')
			&& $order->getField('EXTERNAL_ORDER') === 'Y'
		)
		{
			return '1c';
		}

		return '';
	}

	/**
	 * @param string $xmlId
	 * @return string
	 */
	private static function getValueByXmlId(string $xmlId) : string
	{
		if (mb_strpos($xmlId, 'clothes') !== false)
		{
			return 'clothes';
		}
		elseif (mb_strpos($xmlId, 'instagram') !== false)
		{
			return 'instagram';
		}
		elseif (mb_strpos($xmlId, 'chats') !== false)
		{
			return 'chats';
		}
		elseif (mb_strpos($xmlId, 'mini-one-element') !== false)
		{
			return 'mini-one-element';
		}
		elseif (mb_strpos($xmlId, 'mini-catalog') !== false)
		{
			return 'mini-catalog';
		}

		return $xmlId;
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

		$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
		/** @var Crm\Order\Order $orderClass */
		$orderClass = $registry->getOrderClassName();

		$order = $orderClass::load($orderId);
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
				if($order instanceof Crm\Order\Order)
				{
					ImOpenLinesManager::getInstance()->sendOrderCheckNotify($checkId, $order);
				}
			}
		}

		return $result;
	}

	/**
	 * @param $event
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\NotImplementedException
	 * @throws Main\SystemException
	 */
	public static function OnSaleOrderEntitySaved(Event $event)
	{
		/** @var Crm\Order\Order $order */
		$order = $event->getParameter('ENTITY');

		if (!($order instanceof Crm\Order\Order))
		{
			return;
		}

		$dealBinding = $order->getDealBinding();
		if (!$dealBinding && $order->isNew())
		{
			$dealId = static::createCrmDeal($order);

			if ($dealId)
			{
				$dealBinding = $order->createDealBinding();
				if ($dealBinding)
				{
					$dealBinding->setField('DEAL_ID', $dealId);
					$dealBinding->markCrmDealAsNew();
				}
			}
		}
	}

	/**
	 * @return int|null
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\SystemException
	 */
	protected static function createCrmDeal(Crm\Order\Order $order)
	{
		$selector = static::getActualEntitySelector($order);

		$facility = new Crm\EntityManageFacility($selector);
		$facility->setDirection(Crm\EntityManageFacility::DIRECTION_OUTGOING);

		$fields = static::getDealFieldsOnCreate($order);
		return (int)$facility->registerDeal($fields);
	}

	/**
	 * @return Crm\Integrity\ActualEntitySelector
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\SystemException
	 */
	public static function getActualEntitySelector(Crm\Order\Order $order)
	{
		$selector = new Crm\Integrity\ActualEntitySelector();

		$contactCompanyCollection = $order->getContactCompanyCollection();

		foreach($contactCompanyCollection as $item)
		{
			$selector->setEntity($item->getEntityType(), $item->getField('ENTITY_ID'));
		}

		$selector->setEntity(\CCrmOwnerType::Order, $order->getId());

		return $selector;
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\SystemException
	 */
	protected static function getDealFieldsOnCreate(Crm\Order\Order $order) : array
	{
		$contactIds = [];

		$companyId = null;
		$contactId = null;

		$company = $order->getContactCompanyCollection()->getPrimaryCompany();
		if ($company)
		{
			$companyId = $company->getField('ENTITY_ID');
		}

		foreach ($order->getContactCompanyCollection()->getContacts() as $contact)
		{
			if ($contact->isPrimary())
			{
				$contactId = $contact->getField('ENTITY_ID');
			}

			$contactIds[] = $contact->getField('ENTITY_ID');
		}

		return [
			'OPPORTUNITY' => $order->getPrice(),
			'CURRENCY_ID' => $order->getCurrency(),
			'ASSIGNED_BY_ID' => $order->getField('RESPONSIBLE_ID'),
			'CREATED_BY_ID' => $order->getField('RESPONSIBLE_ID'),
			'CONTACT_IDS' => $contactIds,
			'CONTACT_ID' => $contactId,
			'COMPANY_ID' => $companyId,
		];
	}
	//endregion

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
	 * @param Sale\Order $order
	 * @return string
	 */
	public function getOrderFormattedInsertDate(Sale\Order $order)
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
			'adyen',
			'uapay',
		];

		$paySystemHandlerWithMode = [
			'yandexcheckout',
			'skb',
		];

		$paySystemModeList = [
			'bank_card',
			'sberbank',
			'sberbank_sms',
			'alfabank',
			'yandex_money',
			'webmoney',
			'qiwi',
			'embedded',
			'skb',
		];

		return [
			'ACTIVE' => 'Y',
			[
				'LOGIC' => 'OR',
				[
					'=ACTION_FILE' => $paySystemHandlerList,
				],
				[
					'=ACTION_FILE' => $paySystemHandlerWithMode,
					'=PS_MODE' => $paySystemModeList,
				]
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
			'select' => ['ID', 'NAME', 'ACTION_FILE', 'PS_MODE', 'SORT'],
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
		if (Main\Loader::includeModule("bitrix24"))
		{
			$zone = \CBitrix24::getLicensePrefix();
		}
		elseif (Main\Loader::includeModule('intranet'))
		{
			$zone = \CIntranetUtils::getPortalZone();
		}
		if ($zone === 'ru')
		{
			$result = [
				'\Bitrix\Sale\Cashbox\CashboxAtolFarmV4',
				'\Bitrix\Sale\Cashbox\CashboxOrangeData',
			];
		}
		elseif ($zone === 'ua')
		{
			$result = [
				'\Bitrix\Sale\Cashbox\CashboxCheckbox',
			];
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
			'select' => ['ID', 'NAME', 'HANDLER'],
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
			$result[$item['ID']] = $item;
		}

		return $result;
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

		[$className] = Sale\PaySystem\Manager::includeHandler('adyen');

		if (isset($paySystem['ACTION_FILE'])
			&& $paySystem['ACTION_FILE'] === Sale\PaySystem\Manager::getFolderFromClassName($className)
		)
		{
			if (isset($paySystem['PS_MODE'])
				&& $paySystem['PS_MODE'] === $className::PAYMENT_METHOD_APPLE_PAY
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

	public function getEmptyDeliveryServiceId()
	{
		if (!$this->isEnabled())
		{
			return 0;
		}

		return Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
	}
}