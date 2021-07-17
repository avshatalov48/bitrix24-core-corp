<?php

use Bitrix\Crm\Activity\Provider\Sms;
use Bitrix\MessageService;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Currency\CurrencyManager;
use Bitrix\SalesCenter\Driver;
use Bitrix\SalesCenter\Integration\ImOpenLinesManager;
use Bitrix\SalesCenter\Integration\LandingManager;
use Bitrix\SalesCenter\Integration\PullManager;
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\SalesCenter\Integration\CrmManager;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\SalesCenter\Integration\RestManager;
use Bitrix\Sale\Internals\SiteCurrencyTable;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Cashbox;
use Bitrix\Sale;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Crm\Timeline\Entity;
use Bitrix\Rest;
use Bitrix\SalesCenter;
use Bitrix\SalesCenter\Integration\LocationManager;
use Bitrix\Catalog\v2\Integration\JS\ProductForm;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

define('SALESCENTER_RECEIVE_PAYMENT_APP_AREA', true);

Main\Loader::includeModule('sale');

/**
 * Class CSalesCenterAppComponent
 */
class CSalesCenterAppComponent extends CBitrixComponent implements Controllerable
{
	private const TITLE_LENGTH_LIMIT = 50;
	private const LIMIT_COUNT_PAY_SYSTEM = 3;
	private const TEMPLATE_VIEW_MODE = 'view';
	private const TEMPLATE_CREATE_MODE = 'create';
	private const PAYMENT_DELIVERY_MODE = 'payment_delivery';
	private const DELIVERY_MODE = 'delivery';

	private const CONTEXT_DEAL = 'deal';
	private const CONTEXT_CHAT = 'chat';
	private const CONTEXT_SMS = 'sms';

	/** @var Crm\Order\Order $order */
	private $order;

	/** @var Crm\Order\Payment $order */
	private $payment;

	/** @var Crm\Order\Shipment $order */
	private $shipment;

	/** @var array|null */
	private $deal;

	/**
	 * @inheritDoc
	 */
	public function onPrepareComponentParams($arParams)
	{
		if (!Main\Loader::includeModule('salescenter'))
		{
			ShowError(Loc::getMessage('SALESCENTER_MODULE_ERROR'));
			Application::getInstance()->terminate();
		}

		if (!Main\Loader::includeModule('crm'))
		{
			ShowError(Loc::getMessage('CRM_MODULE_ERROR'));
			Application::getInstance()->terminate();
		}

		if (!$arParams['dialogId'])
		{
			$arParams['dialogId'] = $this->request->get('dialogId');
		}

		$arParams['sessionId'] = intval($arParams['sessionId']);
		if (!$arParams['sessionId'])
		{
			$arParams['sessionId'] = intval($this->request->get('sessionId'));
		}

		if (!isset($arParams['disableSendButton']))
		{
			$arParams['disableSendButton'] = ($this->request->get('disableSendButton') === 'y');
		}
		else
		{
			$arParams['disableSendButton'] = (bool)$arParams['disableSendButton'];
		}

		if (!isset($arParams['context']))
		{
			$arParams['context'] = $this->request->get('context');
		}

		if (!isset($arParams['orderId']))
		{
			$arParams['orderId'] = $this->request->get('orderId');
		}

		if (!isset($arParams['paymentId']))
		{
			$arParams['paymentId'] = (int)$this->request->get('paymentId');
		}

		if (!isset($arParams['shipmentId']))
		{
			$arParams['shipmentId'] = (int)$this->request->get('shipmentId');
		}

		if (!isset($arParams['initialMode']))
		{
			$arParams['initialMode'] = $this->request->get('initialMode');
		}

		if (!isset($arParams['mode']))
		{
			$arParams['mode'] = $this->request->get('mode');
		}

		if (!isset($arParams['ownerId']))
		{
			$arParams['ownerId'] = intval($this->request->get('ownerId'));
		}
		if (!isset($arParams['ownerTypeId']))
		{
			$arParams['ownerTypeId'] = $this->request->get('ownerTypeId');
		}

		if (!isset($arParams['templateMode']))
		{
			$arParams['templateMode'] = $this->request->get('templateMode');
		}

		if (empty($arParams['templateMode']))
		{
			$arParams['templateMode'] = self::TEMPLATE_CREATE_MODE;
		}

		if ($this->needOrderFromDeal($arParams))
		{
			$orderIdList = $this->getOrderIdListByDealId($arParams['ownerId']);
			if ($orderIdList)
			{
				$arParams['orderId'] = current($orderIdList);
			}
		}

		//@TODO backward compatibility

		return parent::onPrepareComponentParams($arParams);
	}

	/**
	 * @inheritDoc
	 */
	public function executeComponent()
	{
		if (!Driver::getInstance()->isEnabled())
		{
			$this->includeComponentTemplate('limit');
			return;
		}

		$this->fillComponentResult();

		if (
			$this->arParams['context'] === self::CONTEXT_DEAL
			&& (int)$this->arParams['ownerTypeId'] === CCrmOwnerType::Deal
			&& !$this->deal
		)
		{
			ShowError(Loc::getMessage('SALESCENTER_ERROR_DEAL_NO_FOUND'));
			Application::getInstance()->terminate();
		}

		$GLOBALS['APPLICATION']->setTitle($this->arResult['title']);

		$this->includeComponentTemplate();
	}

	private function needOrderFromDeal(array $arParams): bool
	{
		$isChat = (
			!empty($arParams['dialogId'])
			&& !empty($arParams['sessionId'])
			&& !empty($arParams['ownerId'])
			&& empty($arParams['orderId'])
		);

		$isSms = (
			$arParams['context'] === self::CONTEXT_SMS
			&& !empty($arParams['ownerTypeId'])
			&& (int)$arParams['ownerTypeId'] === CCrmOwnerType::Deal
			&& !empty($arParams['ownerId'])
			&& empty($arParams['orderId'])
		);

		return $isChat || $isSms;
	}

	private function fillComponentResult(): void
	{
		PullManager::getInstance()->subscribeOnConnect();
		$this->arResult = Driver::getInstance()->getManagerParams();

		$this->arResult['templateMode'] = $this->arParams['templateMode'];
		$this->arResult['paymentId'] = (int)$this->arParams['paymentId'];
		$this->arResult['shipmentId'] = (int)$this->arParams['shipmentId'];
		$this->arResult['mode'] = ($this->arParams['mode'] === self::DELIVERY_MODE)
			? self::DELIVERY_MODE
			: self::PAYMENT_DELIVERY_MODE;
		$this->arResult['initialMode'] = $this->arParams['initialMode'] ?? $this->arResult['mode'];

		if ($this->arParams['orderId'] > 0)
		{
			$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
			/** @var Crm\Order\Order $orderClassName */
			$orderClassName = $registry->getOrderClassName();

			$this->order = $orderClassName::load($this->arParams['orderId']);
			if ($this->arResult['paymentId'])
			{
				$this->payment = $this->order->getPaymentCollection()->getItemById($this->arResult['paymentId']);
				if ($this->arResult['shipmentId'] === 0)
				{
					/** @var Sale\PayableShipmentItem $payableItem */
					foreach ($this->payment->getPayableItemCollection()->getShipments() as $payableItem)
					{
						$this->arResult['shipmentId'] = $payableItem->getField('ENTITY_ID');
					}
				}
			}
			if ($this->arResult['shipmentId'])
			{
				$this->shipment = $this->order->getShipmentCollection()->getItemById($this->arResult['shipmentId']);
			}

			$this->arResult['orderId'] = ($this->order ? $this->order->getId() : 0);

			$this->arResult['paymentId'] = ($this->payment ? $this->payment->getId() : 0);
			if ($this->payment)
			{
				$this->arResult['payment'] = $this->payment->getFieldValues();
			}

			$this->arResult['shipmentId'] = ($this->shipment ? $this->shipment->getId() : 0);
			if ($this->shipment)
			{
				$this->arResult['shipment'] = $this->shipment->getFieldValues();
			}
		}

		$this->arResult['isFrame'] = Application::getInstance()->getContext()->getRequest()->get('IFRAME') === 'Y';
		$this->arResult['isCatalogAvailable'] = (\Bitrix\Main\Config\Option::get('salescenter', 'is_catalog_enabled', 'N') === 'Y');
		$this->arResult['dialogId'] = $this->arParams['dialogId'];
		$this->arResult['sessionId'] = $this->arParams['sessionId'];
		$this->arResult['context'] = $this->arParams['context'];
		$this->arResult['orderAddPullTag'] = PullManager::getInstance()->subscribeOnOrderAdd();
		$this->arResult['landingUnPublicationPullTag'] = PullManager::getInstance()->subscribeOnLandingUnPublication();
		$this->arResult['landingPublicationPullTag'] = PullManager::getInstance()->subscribeOnLandingPublication();
		$this->arResult['isOrderPublicUrlExists'] = (LandingManager::getInstance()->isOrderPublicUrlExists());
		$this->arResult['isOrderPublicUrlAvailable'] = (LandingManager::getInstance()->isOrderPublicUrlAvailable());
		$this->arResult['orderPublicUrl'] = Main\Engine\UrlManager::getInstance()->getHostUrl().'/';
		$this->arResult['disableSendButton'] = $this->arParams['disableSendButton'];
		$this->arResult['ownerTypeId'] = $this->arParams['ownerTypeId'];
		$this->arResult['ownerId'] = $this->arParams['ownerId'];
		$this->arResult['isBitrix24'] = Bitrix24Manager::getInstance()->isEnabled();
		$this->arResult['isPaymentsLimitReached'] = Bitrix24Manager::getInstance()->isPaymentsLimitReached();
		$this->arResult['urlSettingsCompanyContacts'] = $this->getComponentSliderPath('bitrix:salescenter.company.contacts');
		$this->fillSendersData($this->arResult);
		$this->arResult['mostPopularProducts'] = $this->getMostPopularProducts();
		$this->arResult['vatList'] = $this->getProductVatList();
		$this->arResult['catalogIblockId'] = \CCrmCatalog::GetDefaultID();
		$this->arResult['basePriceId'] = \CCatalogGroup::GetBaseGroup()['ID'];
		$this->arResult['showProductDiscounts'] = \CUserOptions::GetOption('catalog.product-form', 'showDiscountBlock', 'Y');
		$this->arResult['showProductTaxes'] = \CUserOptions::GetOption('catalog.product-form', 'showTaxBlock', 'Y');
		$collapseOptions = CUserOptions::GetOption(
			'salescenter',
			($this->arResult['mode'] === self::PAYMENT_DELIVERY_MODE) ? 'add_payment_collapse_options' : 'add_shipment_collapse_options',
			[]
		);
		$this->arResult['isPaySystemCollapsed'] = $collapseOptions['pay_system'] ?? null;
		$this->arResult['isCashboxCollapsed'] = $collapseOptions['cashbox'] ?? null;
		$this->arResult['isDeliveryCollapsed'] = $collapseOptions['delivery'] ?? null;
		$this->arResult['isAutomationCollapsed'] = $collapseOptions['automation'] ?? null;
		$this->arResult['urlProductBuilderContext'] = Crm\Product\Url\ShopBuilder::TYPE_ID;

		$this->arResult['isIntegrationButtonVisible'] = Bitrix24Manager::getInstance()->isIntegrationRequestPossible();

		$baseCurrency = SiteCurrencyTable::getSiteCurrency(SITE_ID);
		if (empty($baseCurrency))
		{
			$baseCurrency = CurrencyManager::getBaseCurrency();
		}
		$this->arResult['currencyCode'] = $baseCurrency;

		//@TODO get rid of it
		$clientInfo = (new SalesCenter\Controller\Order())->getClientInfo([
			'sessionId' => $this->arResult['sessionId'],
			'ownerTypeId' => $this->arResult['ownerTypeId'],
			'ownerId' => $this->arResult['ownerId'],
		]);

		$this->arResult['personTypeId'] = (!empty($clientInfo['COMPANY_ID']))
			? (int)Crm\Order\PersonType::getCompanyPersonTypeId()
			: (int)Crm\Order\PersonType::getContactPersonTypeId();

		if ($this->arResult['personTypeId'] <= 0)
		{
			$this->arResult['personTypeId'] = (int)Sale\Helpers\Admin\Blocks\OrderBuyer::getDefaultPersonType(SITE_ID);
		}

		if (
			(
				isset($this->arParams['ownerId'])
				&& (int)$this->arParams['ownerId'] > 0
			)
			&& (
				(int)$this->arParams['ownerTypeId'] === CCrmOwnerType::Deal
				|| $this->arParams['context'] === self::CONTEXT_CHAT
				|| $this->arParams['context'] === self::CONTEXT_SMS
			)
		)
		{
			$this->arResult['orderList'] = $this->getOrderIdListByDealId((int)$this->arParams['ownerId']);
		}

		if ($this->arParams['context'] === self::CONTEXT_DEAL)
		{
			if (
				(int)$this->arParams['ownerTypeId'] === CCrmOwnerType::Deal
				&& $this->deal = CCrmDeal::GetByID($this->arParams['ownerId'])
			)
			{
				$this->arResult['currencyCode'] = $this->deal['CURRENCY_ID'];
				$this->arResult['sendingMethod'] = 'sms';
				$this->arResult['sendingMethodDesc'] = $this->getSendingMethodDescByType($this->arResult['sendingMethod']);
				$this->arResult['stageOnOrderPaid'] = CrmManager::getInstance()->getStageWithOrderPaidTrigger(
					$this->arParams['ownerId']
				);
				$this->arResult['stageOnDeliveryFinished'] = CrmManager::getInstance()->getStageWithDeliveryFinishedTrigger(
					$this->arParams['ownerId']
				);
				$this->arResult['dealStageList'] = $this->getDealStageList();
				$this->arResult['dealResponsible'] = $this->getManagerInfo($this->deal['ASSIGNED_BY']);
				$this->arResult['contactPhone'] = CrmManager::getInstance()->getDealContactPhoneFormat($this->deal['ID']);
				$this->arResult['orderPropertyValues'] = [];
				$this->arResult['timeline'] = $this->getTimeLine();

				$this->arResult['basket'] = $this->getBasket();
				$this->arResult['totals'] = $this->getTotalSumList(
					array_column($this->arResult['basket'], 'fields'),
					$this->arResult['currencyCode']
				);

				$this->arResult['shipmentData'] = $this->getShipmentData();
				$this->arResult['emptyDeliveryServiceId'] = Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();

				// region shipment presets
				$fromPropId = $this->getDeliveryFromPropId();
				if ($fromPropId)
				{
					$this->arResult['deliveryOrderPropOptions'][$fromPropId] = [
						'defaultItems' => $this->getDeliveryFromList(),
						'isFromAddress' => true,
					];
				}

				$toPropId = $this->getDeliveryToPropId();
				if ($toPropId)
				{
					$this->arResult['deliveryOrderPropOptions'][$toPropId] = [
						'defaultItems' => $this->getDeliveryToList(),
					];
				}
				// endregion

				$this->arResult['paySystemList'] = $this->getPaySystemList();
				$this->arResult['deliveryList'] = $this->getDeliveryList();

				$this->arResult['cashboxList'] = [];
				if (Driver::getInstance()->isCashboxEnabled())
				{
					$this->arResult['cashboxList'] = $this->getCashboxList();
				}

				$this->arResult['isAutomationAvailable'] = Crm\Automation\Factory::isAutomationAvailable(\CCrmOwnerType::Deal);
				$this->arResult['assignedById'] = $this->deal['ASSIGNED_BY_ID'];
				$this->arResult['urlProductBuilderContext'] = Crm\Product\Url\ProductBuilder::TYPE_ID;
			}
		}
		elseif ($this->arParams['context'] === self::CONTEXT_CHAT)
		{
			$this->arResult['basket'] = $this->getBasket();
		}
		elseif (
			$this->arParams['context'] === self::CONTEXT_SMS
			&& (int)$this->arParams['ownerTypeId'] === CCrmOwnerType::Deal
		)
		{
			$this->arResult['basket'] = $this->getBasket();
		}

		if ($this->arResult['sessionId'] > 0)
		{
			$sessionInfo = ImOpenLinesManager::getInstance()->setSessionId($this->arResult['sessionId'])->getSessionInfo();
			if ($sessionInfo)
			{
				$this->arResult['connector'] = $sessionInfo['SOURCE'];
				$this->arResult['lineId'] = $sessionInfo['CONFIG_ID'];
			}
		}

		if (
			Main\Loader::includeModule('sale')
			&& Main\Loader::includeModule('currency')
			&& Main\Loader::includeModule('catalog')
		)
		{
			$this->arResult['orderCreationOption'] = 'order_creation';
			$this->arResult['paySystemBannerOptionName'] = 'hide_paysystem_banner';
			$this->arResult['showPaySystemSettingBanner'] = true;

			$currencyDescription = \CCurrencyLang::GetFormatDescription($this->arResult['currencyCode']);
			$this->arResult['CURRENCIES'][] = [
				'CURRENCY' => $currencyDescription['CURRENCY'],
				'FORMAT' => [
					'FORMAT_STRING' => $currencyDescription['FORMAT_STRING'],
					'DEC_POINT' => $currencyDescription['DEC_POINT'],
					'THOUSANDS_SEP' => $currencyDescription['THOUSANDS_SEP'],
					'DECIMALS' => $currencyDescription['DECIMALS'],
					'THOUSANDS_VARIANT' => $currencyDescription['THOUSANDS_VARIANT'],
					'HIDE_ZERO' => $currencyDescription['HIDE_ZERO'],
				],
			];

			$this->arResult['currencySymbol'] = \CCrmCurrency::GetCurrencyText($this->arResult['currencyCode']);

			$dbMeasureResult = \CCatalogMeasure::getList(
				array('CODE' => 'ASC'),
				array(),
				false,
				array('nTopCount' => 100),
				array('CODE', 'SYMBOL_RUS', 'SYMBOL_INTL', 'IS_DEFAULT')
			);

			$this->arResult['measures'] = [];
			while ($measureFields = $dbMeasureResult->Fetch())
			{
				$this->arResult['measures'][] = [
					'CODE' => (int)$measureFields['CODE'],
					'IS_DEFAULT' => $measureFields['IS_DEFAULT'],
					'SYMBOL' => $measureFields['SYMBOL_RUS'] ?? $measureFields['SYMBOL_INTL'],
				];
			}

			$this->arResult['showPaySystemSettingBanner'] = $this->needShowPaySystemSettingBanner();
			if ($this->arResult['showPaySystemSettingBanner'])
			{
				$options = \CUserOptions::GetOption('salescenter', $this->arResult['orderCreationOption'], []);
				$this->arResult['showPaySystemSettingBanner'] = ($options[$this->arResult['paySystemBannerOptionName']] !== 'Y');
			}
		}

		$this->arResult['title'] = $this->makeTitle();
		$this->arResult['isWithOrdersMode'] = \CCrmSaleHelper::isWithOrdersMode();
	}

	/**
	 * @return string
	 */
	private function makeTitle(): string
	{
		if ($this->arParams['context'] === self::CONTEXT_DEAL)
		{
			if ($this->arResult['templateMode'] === self::TEMPLATE_VIEW_MODE)
			{
				if ($this->arResult['mode'] === self::PAYMENT_DELIVERY_MODE && $this->payment)
				{
					/** @var \Bitrix\Main\Type\DateTime $dateBill */
					$dateBill = $this->payment->getField('DATE_BILL');

					return sprintf(
						'%s %s (%s %s)',
						Loc::getMessage('SALESCENTER_PAYMENT_CREATED_AT'),
						ConvertTimeStamp($dateBill->getTimestamp(),'SHORT'),
						Loc::getMessage('SALESCENTER_AMOUNT_TO_PAY'),
						SaleFormatCurrency(
							$this->payment->getField('SUM'),
							$this->payment->getField('CURRENCY')
						)
					);
				}
				elseif ($this->arResult['mode'] === self::DELIVERY_MODE && $this->shipment)
				{
					/** @var \Bitrix\Main\Type\DateTime $dateInsert */
					$dateInsert = $this->shipment->getField('DATE_INSERT');

					return sprintf(
						'%s %s (%s, %s %s)',
						Loc::getMessage('SALESCENTER_SHIPMENT_CREATED_AT'),
						ConvertTimeStamp($dateInsert->getTimestamp(),'SHORT'),
						$this->shipment->getDelivery()->getNameWithParent(),
						Loc::getMessage('SALESCENTER_AMOUNT_TO_PAY'),
						SaleFormatCurrency(
							$this->shipment->getPrice(),
							$this->shipment->getCurrency()
						)
					);
				}
				else
				{
					return '';
				}
			}
			else
			{
				return ($this->arResult['mode'] === self::PAYMENT_DELIVERY_MODE)
					? Loc::getMessage('SALESCENTER_APP_PAYMENT_AND_DELIVERY_TITLE')
					: Loc::getMessage('SALESCENTER_APP_DELIVERY_TITLE');
			}
		}
		else
		{
			return Loc::getMessage('SALESCENTER_APP_TITLE');
		}
	}

	/**
	 * @param array $products
	 * @param $currency
	 * @return int[]
	 */
	private function getTotalSumList(array $products, $currency): array
	{
		$result = [
			'discount' => 0,
			'result' => 0,
			'sum' => 0,
		];

		foreach ($products as $product)
		{
			$result['discount'] += $product['discount'] * $product['quantity'];
			$result['result'] += $product['price'] * $product['quantity'];
			$result['sum'] += $product['basePrice'] * $product['quantity'];
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getSmsSenderList(): array
	{
		$result = [];
		$restSender = null;

		$senderList = Crm\Integration\SmsManager::getSenderInfoList(true);
		foreach ($senderList as $sender)
		{
			if ($sender['canUse'])
			{
				if ($sender['id'] === 'rest')
				{
					$restSender = $sender;

					continue;
				}

				$result[] = $sender;
			}
		}

		if ($restSender !== null)
		{
			foreach ($restSender['fromList'] as $sender)
			{
				$result[] = $sender;
			}
		}

		return $result;
	}

	protected function getOrderProducts()
	{
		$productList = [];

		if ($this->payment)
		{
			/** @var Crm\Order\PayableItemCollection $shipmentItemCollection */
			$payableItemCollection = $this->payment->getPayableItemCollection()->getBasketItems();

			/** @var Bitrix\Crm\Order\PayableBasketItem $payableItem */
			foreach ($payableItemCollection as $payableItem)
			{
				$entity = $payableItem->getEntityObject();

				$item = $entity->getFieldValues();
				$item['BASKET_CODE'] = $entity->getBasketCode();
				$item['QUANTITY'] = $payableItem->getQuantity();

				$productList[] = $item;
			}
		}
		elseif ($this->shipment)
		{
			/** @var Crm\Order\ShipmentItemCollection $shipmentItemCollection */
			$shipmentItemCollection = $this->shipment->getShipmentItemCollection();

			/** @var Bitrix\Crm\Order\ShipmentItem $shipmentItem */
			foreach ($shipmentItemCollection as $shipmentItem)
			{
				$entity = $shipmentItem->getBasketItem();

				$item = $entity->getFieldValues();
				$item['BASKET_CODE'] = $entity->getBasketCode();
				$item['QUANTITY'] = $shipmentItem->getQuantity();

				$productList[] = $item;
			}
		}

		if (empty($productList))
		{
			return [];
		}

		$formBuilder = new ProductForm\BasketBuilder();

		foreach ($productList as $index => $product)
		{
			$item = null;

			$quantity = $product['QUANTITY'];

			$skuId = (int)$product['PRODUCT_ID'];
			if ($skuId > 0)
			{
				$item = $formBuilder->loadItemBySkuId($skuId);
			}

			if ($item === null)
			{
				$item = $formBuilder->createItem();
			}

			$item
				->setName($product['NAME'])
				->setPrice($product['PRICE'])
				->setCode($product['BASKET_CODE'])
				->setBasePrice($product['BASE_PRICE'])
				->setPriceExclusive($product['PRICE'])
				->setCustomPriceType($product['CUSTOM_PRICE'])
				->setQuantity($quantity)
				->setSort($index)
				->setMeasureCode((int)$product['MEASURE_CODE'])
				->setMeasureName($product['MEASURE_NAME'])
			;

			if ($product['DISCOUNT_PRICE'] > 0)
			{
				$discountRate = $product['DISCOUNT_PRICE'] / $product['BASE_PRICE'] * 100;
				$item
					->setDiscountType(Crm\Discount::MONETARY)
					->setDiscountValue($product['DISCOUNT_PRICE'])
					->setDiscountRate(round($discountRate, 2))
				;
			}

			$formBuilder->setItem($item);
		}

		return $formBuilder->getFormattedItems();
	}

	/**
	 * @return array
	 */
	private function getBasket(): array
	{
		if ($this->arParams['templateMode'] === self::TEMPLATE_VIEW_MODE) {
			return $this->getOrderProducts();
		}

		if ($this->arParams['templateMode'] === self::TEMPLATE_CREATE_MODE) {
			return $this->getProducts();
		}

		return [];
	}

	private function getShipmentData()
	{
		if ($this->arParams['templateMode'] === self::TEMPLATE_CREATE_MODE) {
			$toPropId = $this->getDeliveryToPropId();
			$fromPropId = $this->getDeliveryFromPropId();

			$propValues = [];
			if ($toPropId)
			{
				$toList = $this->getDeliveryToList();
				if ($toList && isset($toList[0]['address']))
				{
					$propValues[$toPropId] = $toList[0]['address'];
				}
			}
			if ($fromPropId)
			{
				$fromList = $this->getDeliveryFromList();
				if ($fromList && isset($fromList[0]['address']))
				{
					$propValues[$fromPropId] = $fromList[0]['address'];
				}
			}

			return [
				'deliveryServiceId' => Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId(),
				'extraServicesValues' => [],
				'propValues' => $propValues,
			];
		}

		return [];
	}

	/**
	 * @return int|null
	 */
	private function getDeliveryFromPropId(): ?int
	{
		return $this->getDeliveryAddressPropIdByCode(
			Sale\Delivery\Services\OrderPropsDictionary::ADDRESS_FROM_PROPERTY_CODE
		);
	}

	/**
	 * @return int|null
	 */
	private function getDeliveryToPropId(): ?int
	{
		return $this->getDeliveryAddressPropIdByCode(
			Sale\Delivery\Services\OrderPropsDictionary::ADDRESS_TO_PROPERTY_CODE
		);
	}

	/**
	 * @return int|null
	 */
	private function getDeliveryAddressPropIdByCode(string $propCode): ?int
	{
		$prop = Sale\ShipmentProperty::getList(
			[
				'filter' => [
					'=PERSON_TYPE_ID' => $this->arResult['personTypeId'],
					'=ACTIVE' => 'Y',
					'=TYPE' => 'ADDRESS',
					'=CODE' => $propCode,
				],
			]
		)->fetch();

		return $prop ? $prop['ID'] : null;
	}

	/**
	 * @return array
	 */
	private function getDeliveryFromList(): array
	{
		$result = LocationManager::getInstance()->getFormattedLocations(
			CrmManager::getInstance()->getMyCompanyAddressList()
		);

		$defaultLocationFrom = LocationManager::getInstance()->getDefaultLocationFrom();
		if ($defaultLocationFrom)
		{
			array_unshift($result, $defaultLocationFrom);
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getDeliveryToList(): array
	{
		return LocationManager::getInstance()->getFormattedLocations(
			CrmManager::getInstance()->getDealClientAddressList($this->deal['ID'])
		);
	}

	/**
	 * @return array
	 */
	private function getTimeline(): array
	{
		if ($this->arResult['mode'] !== self::PAYMENT_DELIVERY_MODE)
		{
			return [];
		}

		if ($this->arResult['templateMode'] === self::TEMPLATE_CREATE_MODE)
		{
			$result = [
				[
					'type' => 'sent',
					'disabled' => true,
					'content' => Loc::getMessage('SALESCENTER_TIMELINE_WATCH_CONTENT_DEFAULT'),
				],
				[
					'type' => 'cash',
					'disabled' => true,
					'content' => Loc::getMessage('SALESCENTER_TIMELINE_PAYMENT_TITLE_DEFAULT'),
				],
			];

			if (Driver::getInstance()->isCashboxEnabled())
			{
				$result[] = [
					'type' => 'check-sent',
					'disabled' => true,
					'content' => Loc::getMessage('SALESCENTER_TIMELINE_CHECK_CONTENT_DEFAULT'),
				];
			}

			return $result;
		}

		$items = $this->getTimeLineItemsByFilter([
			'TYPE_ID'=>[
				CCrmOwnerType::Order,
				CCrmOwnerType::OrderCheck,
			],
			'ENTITY_ID'=>$this->arResult['orderId'],
			'ENTITY_TYPE_ID'=>CCrmOwnerType::Order,
			'ASSOCIATED_ENTITY_TYPE_ID'=>[
				CCrmOwnerType::Order,
				CCrmOwnerType::OrderPayment,
				CCrmOwnerType::OrderCheck,
			],
		]);

		return count($items)>0 ? $this->prepareTimeLineItems($items):[];
	}

	/**
	 * @param $stageId
	 * @return array
	 */
	private function getDealStageList() : array
	{
		$result = [
			[
				'type' => 'invariable',
				'name' => Loc::getMessage('SALESCENTER_AUTOMATION_STEPS_STAY'),
			],
		];

		$dealStageList = CCrmViewHelper::GetDealStageInfos($this->deal['CATEGORY_ID']);
		foreach ($dealStageList as $stage)
		{
			$result[] = [
				'id' => $stage['STATUS_ID'],
				'type' => 'stage',
				'name' => $stage['NAME'],
				'color' => $stage['COLOR'],
				'colorText' => $this->getStageColorText($stage['COLOR']),
			];
		}

		return $result;
	}

	/**
	 * @param $hexColor
	 * @return string
	 */
	private function getStageColorText($hexColor): string
	{
		if (!preg_match("/^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/", $hexColor))
		{
			return 'light';
		}

		$hexColor = str_replace('#', '', $hexColor);
		if (mb_strlen($hexColor) === 3)
		{
			$hexColor = preg_replace("/([a-f0-9])/", '$1$1', $hexColor);
		}

		[$red, $green, $blue] = str_split($hexColor, 2);
		$yiq = (hexdec($red) * 299 + hexdec($green) * 587 + hexdec($blue) * 114) / 1000;

		return $yiq >= 140 ? 'dark' : 'light';
	}

	/**
	 * @param $userId
	 * @return array
	 */
	private function getManagerInfo($userId) : array
	{
		$result = [
			'name' => '',
			'photo' => '',
		];

		$by = 'id';
		$order = 'asc';

		$dbRes = \CUser::GetList(
			$by,
			$order,
			['ID' => $userId],
			['FIELDS' => ['PERSONAL_PHOTO', 'NAME']]
		);

		if ($user = $dbRes->Fetch())
		{
			$result['name'] = $user['NAME'];

			$fileInfo = \CFile::ResizeImageGet(
				$user['PERSONAL_PHOTO'] ?? '',
				['width' => 63, 'height' => 63],
				BX_RESIZE_IMAGE_EXACT
			);

			if (is_array($fileInfo) && isset($fileInfo['src']))
			{
				$result['photo'] = $fileInfo['src'];
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getProducts() : array
	{
		$formBuilder = new ProductForm\BasketBuilder();
		$productManager = new Crm\Order\ProductManager($this->arResult['ownerId']);

		if ($this->order)
		{
			$productManager->setOrder($this->order);
		}

		if ($this->arResult['mode'] === self::DELIVERY_MODE)
		{
			$productRows = $productManager->getDeliverableItems();
		}
		else
		{
			$productRows = $productManager->getPayableItems();
		}

		foreach ($productRows as $product)
		{
			$item = null;
			$skuId = (int)$product['PRODUCT_ID'];
			if ($skuId > 0)
			{
				$item = $formBuilder->loadItemBySkuId($skuId);
			}

			if ($item === null)
			{
				$item = $formBuilder->createItem();
			}

			$originBasketCode = '';
			if (mb_strpos($product['BASKET_CODE'], 'n') === false)
			{
				$originBasketCode = $product['BASKET_CODE'];
			}

			$item
				->setDetailUrlManagerType(Crm\Product\Url\ProductBuilder::TYPE_ID)
				->addAdditionalField('originProductId', $product['PRODUCT_ID'] ?? 0)
				->addAdditionalField('originBasketId', $originBasketCode)
				->setName($product['NAME'])
				->setPrice((float)$product['PRICE'])
				->setCode($product['BASKET_CODE'])
				->setBasePrice((float)$product['BASE_PRICE'])
				->setPriceExclusive((float)$product['PRICE'])
				->setQuantity((float)$product['QUANTITY'])
				->setDiscountType((int)$product['DISCOUNT_TYPE_ID'])
				->setDiscountRate((float)$product['DISCOUNT_RATE'])
				->setDiscountValue((float)$product['DISCOUNT_SUM'])
				->setMeasureCode((int)$product['MEASURE_CODE'])
				->setMeasureName($product['MEASURE_NAME'])
			;

			$formBuilder->setItem($item);
			$item->setSort($formBuilder->count() * 100);
		}

		return $formBuilder->getFormattedItems();
	}

	/**
	 * @return array
	 */
	private function getPaySystemList(): array
	{
		$result = [];

		$paySystemPath = $this->getComponentSliderPath('bitrix:salescenter.paysystem');
		$queryParams = [
			'lang' => LANGUAGE_ID,
			'publicSidePanel' => 'Y',
		];

		$paySystemList = SaleManager::getInstance()->getPaySystemList([
			'!=ACTION_FILE' => ['inner', 'cash'],
		]);
		if ($paySystemList)
		{
			$result['isSet'] = true;

			foreach ($paySystemList as $paySystem)
			{
				$queryParams['ID'] = $paySystem['ID'];
				$paySystemPath->addParams($queryParams);

				$result['items'][] = [
					'name' => $paySystem['NAME'],
					'link' => $paySystemPath->getLocator(),
					'type' => 'paysystem',
					'sort' => $paySystem['SORT'],
				];
			}

			Main\Type\Collection::sortByColumn($result['items'], ['sort' => SORT_ASC]);

			$result['items'][] = [
				'name' => Loc::getMessage('SALESCENTER_APP_ADD_TITLE'),
				'link' => $this->getComponentSliderPath('bitrix:salescenter.paysystem.panel')->getLocator(),
				'type' => 'more',
			];

			if (Bitrix24Manager::getInstance()->isEnabled())
			{
				$feedbackPath = $this->getComponentSliderPath('bitrix:salescenter.feedback');
				$queryParams = [
					'lang' => LANGUAGE_ID,
					'feedback_type' => 'paysystem_offer',
				];
				$feedbackPath->addParams($queryParams);

				$result['items'][] = [
					'name' => Loc::getMessage('SALESCENTER_APP_RECOMMENDATION_TITLE'),
					'link' => $feedbackPath->getLocator(),
					'width' => 735,
					'type' => 'offer',
				];
			}
		}
		else
		{
			$paySystemHandlerList = $this->getSliderPaySystemHandlers();
			$handlerList = PaySystem\Manager::getHandlerList();
			$systemHandlers = array_keys($handlerList['SYSTEM']);
			foreach ($systemHandlers as $key => $systemHandler)
			{
				if (mb_strpos($systemHandler, 'quote_') !== false)
				{
					continue;
				}

				$handlerDescription = PaySystem\Manager::getHandlerDescription($systemHandler);
				if (empty($handlerDescription))
				{
					continue;
				}

				if (!array_key_exists($systemHandler, $paySystemHandlerList))
				{
					continue;
				}

				$img = '/bitrix/components/bitrix/salescenter.paysystem.panel/templates/.default/images/'.$systemHandler;
				$queryParams['ACTION_FILE'] = $systemHandler;

				[$handlerClass] = PaySystem\Manager::includeHandler($systemHandler);
				$psModeList = $handlerClass::getHandlerModeList();
				if ($psModeList)
				{
					foreach (array_keys($psModeList) as $psMode)
					{
						if (!in_array($psMode, $paySystemHandlerList[$systemHandler], true))
						{
							continue;
						}

						$queryParams['PS_MODE'] = $psMode;
						$paySystemPath->addParams($queryParams);

						$psModeImage = $img.'_'.$psMode.'.svg';
						if (!Main\IO\File::isFileExists(Main\Application::getDocumentRoot().$psModeImage))
						{
							$psModeImage = $img.'.svg';
						}

						$result['items'][] = [
							'name' => $handlerDescription['NAME'] ?? $handlerList['SYSTEM'][$systemHandler],
							'img' => $psModeImage,
							'info' => Loc::getMessage(
								'SALESCENTER_APP_PAYSYSTEM_MODE_INFO',
								[
									'#PAYSYSTEM_NAME#' => $handlerDescription['NAME'],
									'#MODE_NAME#' => $psModeList[$psMode],
								]
							),
							'link' => $paySystemPath->getLocator(),
							'type' => 'paysystem',
							'showTitle' => false,
							'sort' => $this->getPaySystemSort($systemHandler, $psMode),
						];

						if (count($result['items']) >= self::LIMIT_COUNT_PAY_SYSTEM)
						{
							break 2;
						}
					}
				}
				else
				{
					$paySystemPath->addParams($queryParams);

					$result['items'][] = [
						'name' => $handlerDescription['NAME'] ?? $handlerList['SYSTEM'][$systemHandler],
						'img' => $img.'.svg',
						'info' => Loc::getMessage(
							'SALESCENTER_APP_PAYSYSTEM_INFO',
							[
								'#PAYSYSTEM_NAME#' => $handlerDescription['NAME'],
							]
						),
						'link' => $paySystemPath->getLocator(),
						'type' => 'paysystem',
						'showTitle' => false,
						'sort' => $this->getPaySystemSort($systemHandler),
					];

					if (count($result['items']) >= self::LIMIT_COUNT_PAY_SYSTEM)
					{
						break;
					}
				}
			}

			if (RestManager::getInstance()->isEnabled())
			{
				$partnersItems = $this->getPaySystemMarketplaceItems();
				if ($partnersItems)
				{
					$result = array_merge_recursive($result, $partnersItems);
				}
			}

			if ($result['items'])
			{
				Main\Type\Collection::sortByColumn($result['items'], ['sort' => SORT_ASC]);
			}

			$result['isSet'] = false;
			$result['items'][] = [
				'name' => Loc::getMessage('SALESCENTER_APP_PAYSYSTEM_ITEM_EXTRA'),
				'link' => $this->getComponentSliderPath('bitrix:salescenter.paysystem.panel')->getLocator(),
				'type' => 'more',
			];

			if (Bitrix24Manager::getInstance()->isEnabled())
			{
				$feedbackPath = $this->getComponentSliderPath('bitrix:salescenter.feedback');
				$queryParams = [
					'lang' => LANGUAGE_ID,
					'feedback_type' => 'paysystem_offer',
				];
				$feedbackPath->addParams($queryParams);

				$result['items'][] = [
					'name' => Loc::getMessage('SALESCENTER_APP_OFFER_TITLE'),
					'link' => $feedbackPath->getLocator(),
					'width' => 735,
					'type' => 'offer',
				];
			}
		}
		return $result;
	}

	/**
	 * @return array
	 */
	private function getPaySystemMarketplaceItems(): array
	{
		$result = [];
		$zone = $this->getZone();
		$installedAppList = $this->getMarketplaceInstalledApps('payment');
		$partnerItems = RestManager::getInstance()->getByTag([
			RestManager::TAG_PAYSYSTEM_PAYMENT,
			RestManager::TAG_PAYSYSTEM_MAKE_PAYMENT,
			$zone,
		]);
		$marketplaceItemCodeList = [];
		if (!empty($partnerItems['ITEMS']))
		{
			foreach ($partnerItems['ITEMS'] as $partnerItem)
			{
				$marketplaceItemCodeList[] = $partnerItem['CODE'];
			}
		}

		$marketplaceItemCodeList = array_unique(array_merge(array_keys($installedAppList), $marketplaceItemCodeList));
		foreach ($marketplaceItemCodeList as $marketplaceItemCode)
		{
			if ($marketplaceApp = RestManager::getInstance()->getMarketplaceAppByCode($marketplaceItemCode))
			{
				$title = $marketplaceApp['NAME']
					?? $marketplaceApp['LANG'][$zone]['NAME']
					?? current($marketplaceApp['LANG'])['NAME']
					?? '';

				$img = $marketplaceApp['ICON_PRIORITY'];
				if (!$img)
				{
					$img = $marketplaceApp['ICON']
						? $marketplaceApp['ICON']
						: '/bitrix/components/bitrix/salescenter.paysystem.panel/templates/.default/images/marketplace_default.svg';
				}

				$result['items'][] = [
					'id' => (array_key_exists($marketplaceItemCode, $installedAppList)
						? $installedAppList[$marketplaceItemCode]['ID']
						: $marketplaceApp['ID']
					),
					'code' => $marketplaceApp['CODE'],
					'name' => $this->getFormattedTitle($title),
					'img' => $img,
					'installedApp' => array_key_exists($marketplaceItemCode, $installedAppList),
					'info' => $marketplaceApp['SHORT_DESC'],
					'type' => 'marketplace',
					'showTitle' => false,
					'sort' => $marketplaceApp['ID'],
				];
			}
		}

		if ($result['items'])
		{
			Main\Type\Collection::sortByColumn($result['items'], ['sort' => SORT_ASC]);
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getDeliveryList(): array
	{
		$handlersCollection = (new SalesCenter\Delivery\Handlers\HandlersRepository())->getCollection();

		$result = [
			'hasInstallable' => $handlersCollection->hasInstallableItems(),
			'isInstalled' => false,
			'items' => []
		];

		$handlers = $handlersCollection->getInstallableItems();

		$internalItems = [];
		foreach ($handlers as $handler)
		{
			if ($handler->isInstalled())
			{
				$result['isInstalled'] = true;
			}

			$internalItems[] = [
				'code' => $handler->getCode(),
				'name' => $handler->getName(),
				'link' => $handler->getInstallationLink(),
				'img' => $handler->getImagePath(),
				'info' => $handler->getShortDescription(),
				'type' => 'delivery',
				'showTitle' => !$handler->doesImageContainName(),
				'width' => 835
			];
		}

		$marketplaceItems = [];
		if (RestManager::getInstance()->isEnabled())
		{
			$marketplaceItems = $this->getDeliveryMarketplaceItems();
		}

		$result['items'] = array_merge($internalItems, $marketplaceItems);

		if (Bitrix24Manager::getInstance()->isEnabled())
		{
			$feedbackPath = $this->getComponentSliderPath('bitrix:salescenter.feedback');
			$queryParams = [
				'lang' => LANGUAGE_ID,
				'feedback_type' => 'delivery_offer',
			];
			$feedbackPath->addParams($queryParams);

			$result['items'][] = [
				'name' => Loc::getMessage('SALESCENTER_APP_RECOMMENDATION_TITLE'),
				'link' => $feedbackPath->getLocator(),
				'width' => 735,
				'type' => 'offer',
			];
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getDeliveryMarketplaceItems(): array
	{
		$result = [];
		$zone = $this->getZone();
		$installedAppList = $this->getMarketplaceInstalledApps('delivery');
		$partnerItems = RestManager::getInstance()->getByTag([
			RestManager::TAG_DELIVERY,
			RestManager::TAG_DELIVERY_MAKE_DELIVERY,
			$zone,
		]);
		$marketplaceItemCodeList = [];
		if (!empty($partnerItems['ITEMS']))
		{
			foreach ($partnerItems['ITEMS'] as $partnerItem)
			{
				$marketplaceItemCodeList[] = $partnerItem['CODE'];
			}
		}

		$marketplaceItemCodeList = array_unique(array_merge(array_keys($installedAppList), $marketplaceItemCodeList));
		foreach ($marketplaceItemCodeList as $marketplaceItemCode)
		{
			if ($marketplaceApp = RestManager::getInstance()->getMarketplaceAppByCode($marketplaceItemCode))
			{
				$title = $marketplaceApp['NAME']
					?? $marketplaceApp['LANG'][$zone]['NAME']
					?? current($marketplaceApp['LANG'])['NAME']
					?? '';

				$img = $marketplaceApp['ICON_PRIORITY'];
				if (!$img)
				{
					$img = $marketplaceApp['ICON']
						? $marketplaceApp['ICON']
						: '/bitrix/components/bitrix/salescenter.delivery.panel/templates/.default/images/marketplace_default.svg';
				}

				$result[] = [
					'id' => (array_key_exists($marketplaceItemCode, $installedAppList)
						? $installedAppList[$marketplaceItemCode]['ID']
						: $marketplaceApp['ID']
					),
					'code' => $marketplaceApp['CODE'],
					'name' => $this->getFormattedTitle($title),
					'img' => $img,
					'installedApp' => array_key_exists($marketplaceItemCode, $installedAppList),
					'info' => $marketplaceApp['SHORT_DESC'],
					'type' => 'marketplace',
					'showTitle' => ($marketplaceApp['CODE'] === 'integrations24.terminal'),
					'sort' => $marketplaceApp['ID'],
				];
			}
		}

		if ($result)
		{
			Main\Type\Collection::sortByColumn($result, ['sort' => SORT_ASC]);
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getCashboxList(): array
	{
		$result = [];

		$cashboxPath = $this->getComponentSliderPath('bitrix:salescenter.cashbox');
		$queryParams = [
			'lang' => LANGUAGE_ID,
			'publicSidePanel' => 'Y',
		];

		$cashboxList = SaleManager::getInstance()->getCashboxList();
		if ($cashboxList)
		{
			$result['isSet'] = true;

			foreach ($cashboxList as $item)
			{
				$queryParams['id'] = $item['ID'];
				$queryParams['handler'] = $item['HANDLER'];
				if (isset($item['REST_CODE']))
				{
					$queryParams['restHandler'] = $item['REST_CODE'];
				}
				$cashboxPath->addParams($queryParams);

				$result['items'][] = [
					'name' => $item['NAME'],
					'link' => $cashboxPath->getLocator(),
					'type' => 'cashbox'
				];
			}

			$result['items'][] = [
				'name' => Loc::getMessage('SALESCENTER_APP_ADD_TITLE'),
				'link' => $this->getComponentSliderPath('bitrix:salescenter.cashbox.panel')->getLocator(),
				'type' => 'more'
			];

			if (Bitrix24Manager::getInstance()->isEnabled())
			{
				$feedbackPath = $this->getComponentSliderPath('bitrix:salescenter.feedback');
				$queryParams = [
					'lang' => LANGUAGE_ID,
					'feedback_type' => 'paysystem_offer',
				];
				$feedbackPath->addParams($queryParams);

				$result['items'][] = [
					'name' => Loc::getMessage('SALESCENTER_APP_RECOMMENDATION_TITLE'),
					'link' => $feedbackPath->getLocator(),
					'width' => 735,
					'type' => 'offer',
				];
			}
		}
		else
		{
			/** @var Cashbox\Cashbox $handler */
			foreach ($this->getCashboxHandlerList() as $handler)
			{
				$queryParams['handler'] = $handler;
				$cashboxPath->addParams($queryParams);

				if (mb_strpos($handler, Cashbox\CashboxBusinessRu::class) !== false)
				{
					$kkmList = [
						Cashbox\CashboxBusinessRu::SUPPORTED_KKM_ATOL,
						Cashbox\CashboxBusinessRu::SUPPORTED_KKM_EVOTOR,
					];
					foreach ($kkmList as $kkm)
					{
						$img = '/bitrix/components/bitrix/salescenter.cashbox.panel/templates/.default/images/businessru_'.$kkm.'.svg';

						$queryParams['kkm-id'] = $kkm;
						$cashboxPath->addParams($queryParams);

						$info = $handler::getSupportedKkmModels()[$kkm];

						$result['items'][] = [
							'name' => $info['NAME'],
							'img' => $img,
							'link' => $cashboxPath->getLocator(),
							'info' => Loc::getMessage(
								'SALESCENTER_APP_CASHBOX_INFO',
								[
									'#CASHBOX_NAME#' => $info['NAME'],
								]
							),
							'type' => 'cashbox',
							'showTitle' => true,
						];
					}
				}
				elseif (mb_strpos($handler, Cashbox\CashboxRest::class) !== false)
				{
					$restHandlers = Cashbox\Manager::getRestHandlersList();
					foreach ($restHandlers as $restHandlerCode => $restHandler)
					{
						$queryParams['restHandler'] = $restHandlerCode;
						$cashboxPath->addParams($queryParams);
						$name = $restHandler['NAME'];
						$result['items'][] = [
							'name' => $name,
							'img' => '/bitrix/components/bitrix/salescenter.cashbox.panel/templates/.default/images/offline.svg',
							'link' => $cashboxPath->getLocator(),
							'info' => Loc::getMessage(
								'SALESCENTER_APP_CASHBOX_INFO',
								[
									'#CASHBOX_NAME#' => $name,
								]
							),
							'type' => 'cashbox',
							'showTitle' => true,
						];
					}
				}
				else
				{
					$img = '';
					if (mb_strpos($queryParams['handler'], Cashbox\CashboxOrangeData::class) !== false)
					{
						$img = '/bitrix/components/bitrix/salescenter.cashbox.panel/templates/.default/images/orangedata.svg';
					}
					elseif (mb_strpos($queryParams['handler'], Cashbox\CashboxCheckbox::class) !== false)
					{
						$img = '/bitrix/components/bitrix/salescenter.cashbox.panel/templates/.default/images/checkbox.svg';
					}

					$name = $handler::getName();
					$result['items'][] = [
						'name' => $handler::getName(),
						'img' => $img,
						'link' => $cashboxPath->getLocator(),
						'info' => Loc::getMessage(
							'SALESCENTER_APP_CASHBOX_INFO',
							[
								'#CASHBOX_NAME#' => $name,
							]
						),
						'type' => 'cashbox',
						'showTitle' => false,
					];
				}
			}

			$queryParams['handler'] = 'offline';
			$cashboxPath->addParams($queryParams);
			$result['items'][] = [
				'name' => Loc::getMessage('SALESCENTER_APP_CASHBOX_OFFLINE_TITLE'),
				'img' => '/bitrix/components/bitrix/salescenter.cashbox.panel/templates/.default/images/offline.svg',
				'link' => $cashboxPath->getLocator(),
				'info' => Loc::getMessage('SALESCENTER_APP_CASHBOX_OFFLINE_INFO'),
				'type' => 'cashbox',
				'showTitle' => true,
			];

			$result['items'][] = [
				'name' => Loc::getMessage('SALESCENTER_APP_CASHBOX_ITEM_EXTRA'),
				'link' => $this->getComponentSliderPath('bitrix:salescenter.cashbox.panel')->getLocator(),
				'type' => 'more',
			];

			if (Bitrix24Manager::getInstance()->isEnabled())
			{
				$feedbackPath = $this->getComponentSliderPath('bitrix:salescenter.feedback');
				$queryParams = [
					'lang' => LANGUAGE_ID,
					'feedback_type' => 'paysystem_offer',
				];
				$feedbackPath->addParams($queryParams);

				$result['items'][] = [
					'name' => Loc::getMessage('SALESCENTER_APP_RECOMMENDATION_TITLE'),
					'link' => $feedbackPath->getLocator(),
					'width' => 735,
					'type' => 'offer',
				];
			}

			$result['isSet'] = false;
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getCashboxHandlerList(): array
	{
		$result = [];

		/** @var Cashbox\Cashbox $handler */
		foreach (SaleManager::getCashboxHandlers() as $handler)
		{
			if (mb_strpos($handler, Cashbox\CashboxAtolFarmV4::class) !== false)
			{
				continue;
			}

			$result[] = $handler;
		}

		return $result;
	}


	/**
	 * @return bool
	 */
	private function needShowPaySystemSettingBanner() : bool
	{
		$dbRes = PaySystem\Manager::getList([
			'select' => ['ID', 'NAME', 'ACTION_FILE'],
			'filter' => [
				'!ID' => PaySystem\Manager::getInnerPaySystemId(),
				'!ACTION_FILE' => 'cash',
				'=ACTIVE' => 'Y',
			],
		]);

		return $dbRes->fetch() ? false : true;
	}

	/**
	 * @param int $cnt
	 * @return array
	 */
	private function getMostPopularProducts(int $cnt = 5): array
	{
		$catalogIblockId = (int)Main\Config\Option::get('crm', 'default_product_catalog_id');

		if (!Main\Loader::includeModule('iblock')
			|| !Main\Loader::includeModule('catalog')
			|| !$catalogIblockId)
		{
			return [];
		}

		$mostPopularProducts = \CIBlockElement::GetList(
			[
				'PRODUCT_RANK' => 'DESC',
				'NAME' => 'ASC',
			],
			[
				'IBLOCK_ID' => $catalogIblockId,
				'CHECK_PERMISSIONS' => 'N',
				'ACTIVE' => 'Y',
			],
			false,
			['nTopCount' => $cnt],
			['ID', 'NAME']
		);

		$products = [];
		while ($product = $mostPopularProducts->fetch())
		{
			$products[] = $product;
		}

		$productIds = array_column($products, 'ID');

		$measureRatios = \Bitrix\Catalog\MeasureRatioTable::getCurrentRatio($productIds);

		$result = [];
		foreach ($products as $product)
		{
			$resultItem = $product;
			$resultItem['MEASURE_RATIO'] = $measureRatios[(int)$product['ID']];
			$result[] = $resultItem;
		}

		return $result;
	}

	/**
	 * @param $name
	 * @return Main\Web\Uri
	 */
	private function getComponentSliderPath($name): Main\Web\Uri
	{
		$path = \CComponentEngine::makeComponentPath($name);
		$path = getLocalPath('components'.$path.'/slider.php');
		$path = new Main\Web\Uri($path);

		return $path;
	}

	/**
	 * @return array
	 */
	private function getSliderPaySystemHandlers(): array
	{
		$paySystemList = SaleManager::getSaleshubPaySystemItems();

		$paySystemPanel = [];
		foreach ($paySystemList as $handler => $handlerItem)
		{
			if (!empty($handlerItem['psMode']))
			{
				foreach ($handlerItem['psMode'] as $psMode => $psModeItem)
				{
					if ($psModeItem['slider'])
					{
						$paySystemPanel[$handler][] = $psMode;
					}
				}
			}
			elseif ($handlerItem['slider'])
			{
				$paySystemPanel[$handler] = [];
			}
		}

		return $paySystemPanel;
	}

	/**
	 * @param $handler
	 * @param bool $psMode
	 * @return int|mixed
	 */
	private function getPaySystemSort($handler, $psMode = false)
	{
		$paySystemList = SaleManager::getSaleshubPaySystemItems();

		$defaultSort = 100;
		if ($psMode)
		{
			return $paySystemList[$handler]['psMode'][$psMode]['sliderSort'] ?? $defaultSort;
		}

		return $paySystemList[$handler]['sliderSort'] ?? $defaultSort;
	}

	/**
	 * @inheritDoc
	 */
	public function configureActions()
	{
		return [];
	}

	/**
	 * @param $date
	 * @param string $format
	 * @return string
	 */
	private function prepareTimeLineItemsDateTime($date, $format = 'j F Y H:i'): string
	{
		$result = '';
		if ($date instanceof \Bitrix\Main\Type\DateTime)
		{
			$result = FormatDate($format, $date->getTimestamp() + CTimeZone::GetOffset());
		}
		return $result;
	}

	/**
	 * @param array $items
	 * @return array
	 */
	private function prepareTimeLineItems(array $items): array
	{
		$result = [];
		foreach ($items as $item)
		{
			if ($item['ASSOCIATED_ENTITY_TYPE_ID'] == CCrmOwnerType::OrderPayment)
			{
				if ($item['CHANGED_ENTITY'] == CCrmOwnerType::OrderPaymentName
					&& $item['FIELDS']['ORDER_PAID'] === 'Y')
				{
					$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
					/** @var Sale\Payment $paymentClassName */
					$paymentClassName = $registry->getPaymentClassName();
					$dbRes = $paymentClassName::getList(array('filter' => array('ID' => $item['ASSOCIATED_ENTITY_ID'])));
					$data = $dbRes->fetch();

					$result[$item['ID']] = [
						'type'=>'payment',
						'sum'=>\CCrmCurrency::MoneyToString($data['SUM'], $data['CURRENCY'], '#'),
						'currency'=>\CCrmCurrency::GetCurrencyText($data['CURRENCY']),
						'content'=>$item['ASSOCIATED_ENTITY']['SUBLEGEND'],
						'title'=>Loc::getMessage('SALESCENTER_TIMELINE_PAYMENT_TITLE',[
							'#DATE_CREATED#'=>$this->prepareTimeLineItemsDateTime($item['CREATED']),
						]),
					];
				}
			}
			elseif ($item['ASSOCIATED_ENTITY_TYPE_ID'] == CCrmOwnerType::OrderCheck)
			{
				if ($item['TYPE_CATEGORY_ID'] == TimelineType::MARK)
				{
					if (isset($item['SENDED']) && $item['SENDED'] === 'Y')
					{
						$check = \Bitrix\Sale\Cashbox\CheckManager::getObjectById($item['ASSOCIATED_ENTITY_ID']);
						$result[] = [
							'type'=>'check-sent',
							'url'=>(is_null($check) ? '':$check->getUrl()),
							'content'=>Loc::getMessage('SALESCENTER_TIMELINE_CHECKSENT_CONTENT',[
								'#ID#'=>$item['ASSOCIATED_ENTITY_ID'],
								'#DATE_CREATED#'=>$this->prepareTimeLineItemsDateTime($item['CREATED']),
							]),
						];
					}
				}
				elseif ($item['TYPE_CATEGORY_ID'] == TimelineType::UNDEFINED)
				{
					$result[] = [
						'type'=>'check',
						'url'=>$item['CHECK_URL'],
						'content'=>Loc::getMessage('SALESCENTER_TIMELINE_CHECK_CONTENT',[
							'#ID#'=>$item['ASSOCIATED_ENTITY_ID'],
							'#DATE_CREATED#'=>$this->prepareTimeLineItemsDateTime($item['CREATED'], 'j F Y')]),
					];
				}
			}
			elseif ($item['ASSOCIATED_ENTITY_TYPE_ID'] == CCrmOwnerType::Order)
			{
				if ($item['TYPE_CATEGORY_ID'] == TimelineType::MODIFICATION)
				{
					if (isset($item['FIELDS']['VIEWED']) && $item['FIELDS']['VIEWED'] === 'Y')
					{
						$result[] = [
							'type'=>'watch',
							'content'=>Loc::getMessage('SALESCENTER_TIMELINE_WATCH_CONTENT',[
								'#DATE_CREATED#'=>$this->prepareTimeLineItemsDateTime($item['CREATED']),
							]),
						];
					}
					elseif (isset($item['FIELDS']['SENT']) && $item['FIELDS']['SENT'] === 'Y')
					{
						$paymentInfo = isset($item['ASSOCIATED_ENTITY']['PAYMENTS_INFO'])
							? current($item['ASSOCIATED_ENTITY']['PAYMENTS_INFO'])
							: [];

						$result[] = [
							'type'=>'sent',
							'url'=>$paymentInfo['SHOW_URL'] ?? '',
							'content'=>Loc::getMessage('SALESCENTER_TIMELINE_SENT_CONTENT_PAYMENT',[
								'#DATE_CREATED#'=>$this->prepareTimeLineItemsDateTime($item['CREATED']),
							]),
						];
					}
				}
			}
		}
		return $result;
	}

	/**
	 * @param array $filter
	 * @return array
	 */
	private function getTimeLineItemsByFilter(array $filter): array
	{
		$query = new Query(Entity\TimelineTable::getEntity());
		$query->addSelect('*');

		$bindingQuery = new Query(Entity\TimelineBindingTable::getEntity());
		$bindingQuery->addSelect('OWNER_ID');
		$bindingQuery->addFilter('=ENTITY_TYPE_ID', $filter['ENTITY_TYPE_ID']);
		$bindingQuery->addFilter('=ENTITY_ID', $filter['ENTITY_ID']);
		$bindingQuery->addSelect('IS_FIXED');
		$query->addSelect('bind.IS_FIXED', 'IS_FIXED');

		$query->registerRuntimeField('',
			new ReferenceField('bind',
				Base::getInstanceByQuery($bindingQuery),
				array('=this.ID' => 'ref.OWNER_ID'),
				array('join_type' => 'INNER')
			)
		);
		$query->whereNotIn(
			'ASSOCIATED_ENTITY_TYPE_ID',
			Crm\Timeline\TimelineManager::getIgnoredEntityTypeIDs()
		);
		$query->addFilter('TYPE_CATEGORY_ID', [TimelineType::CREATION, TimelineType::MODIFICATION, TimelineType::MARK, TimelineType::UNDEFINED]);
		$query->addFilter('TYPE_ID', $filter['TYPE_ID']);
		$query->addFilter('ASSOCIATED_ENTITY_TYPE_ID', $filter['ASSOCIATED_ENTITY_TYPE_ID']);
		$query->setOrder(array('CREATED' => 'ASC', 'ID' => 'ASC'));
		$query->setLimit(10);

		$dbResult = $query->exec();
		$itemIDs = [];
		$items = [];

		while ($fields = $dbResult->fetch())
		{
			$itemID = (int)$fields['ID'];
			$items[] = $fields;
			$itemIDs[] = $itemID;
		}

		$itemsMap = array_combine($itemIDs, $items);
		\Bitrix\Crm\Timeline\TimelineManager::prepareDisplayData($itemsMap);
		return array_values($itemsMap);
	}

	/**
	 * @return string[]
	 */
	private function getAvailableSmsProviderIds(): array
	{
		$result = [];
		$list = $this->getSmsSenderList();
		foreach ($list as $provider)
		{
			if (isset($provider['id']) && $provider['id'] !== '')
			{
				$result[] = (string)$provider['id'];
			}
		}
		return $result;
	}

	/**
	 * @param $type
	 * @return array
	 */
	private function getSendingMethodDescByType($type)
	{
		if ($type === 'sms')
		{
			$lastPaymentSms = null;
			$provider = null;
			$availableProviders = $this->getAvailableSmsProviderIds();
			$defaultProvider = $availableProviders[0] ?? '';

			if ($this->payment && $this->arParams['templateMode'] === self::TEMPLATE_VIEW_MODE)
			{
				$lastPaymentSmsParams = $this->getLastPaymentSmsParams();
				if (is_array($lastPaymentSmsParams))
				{
					if (isset($lastPaymentSmsParams['SENDER_ID']))
					{
						$provider = $lastPaymentSmsParams['SENDER_ID'];
					}

					if (isset($lastPaymentSmsParams['MESSAGE_BODY']))
					{
						$lastPaymentSms = $lastPaymentSmsParams['MESSAGE_BODY'];
					}
				}
			}
			else
			{
				$userOptions = \CUserOptions::GetOption('salescenter', 'payment_sms_provider_options');
				if (is_array($userOptions) && isset($userOptions['latest_selected_provider']))
				{
					$provider = $userOptions['latest_selected_provider'];
				}
			}

			return [
				'provider' => in_array($provider, $availableProviders) ? $provider : $defaultProvider,
				'text' => $lastPaymentSms ?? CrmManager::getInstance()->getSmsTemplate(),
				'sent' => $lastPaymentSms ? true : false,
			];
		}

		return [];
	}

	/**
	 * @return string|null
	 */
	private function getZone(): ?string
	{
		if (Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			return (string)\CBitrix24::getPortalZone();
		}

		$iterator = Main\Localization\LanguageTable::getList(
			[
				'select' => ['ID'],
				'filter' => ['=DEF' => 'Y', '=ACTIVE' => 'Y'],
			]
		);
		if ($row = $iterator->fetch())
		{
			return (string)$row['ID'];
		}

		return null;
	}

	/**
	 * @param $title
	 * @return string
	 */
	private function getFormattedTitle($title): string
	{
		if (mb_strlen($title) > self::TITLE_LENGTH_LIMIT)
		{
			$title = mb_substr($title, 0, self::TITLE_LENGTH_LIMIT - 3).'...';
		}

		return $title;
	}

	/**
	 * @return string
	 */
	private function getUrlSmsProviderSetting(): string
	{
		$path = \CComponentEngine::makeComponentPath('bitrix:salescenter.smsprovider.panel');
		$path = getLocalPath('components'.$path.'/slider.php');
		$path = new \Bitrix\Main\Web\Uri($path);
		return $path->getLocator();
	}

	/**
	 * @return array
	 */
	private function getProductVatList(): array
	{
		$productVatList = [];
		$vatList = CCrmTax::GetVatRateInfos();
		foreach ($vatList as $vatRow)
		{
			$productVatList[] = $vatRow['VALUE'];
		}
		unset($vatRow, $vatList);
		sort($productVatList, SORT_NUMERIC);
		return $productVatList;
	}

	/**
	 * @return ?array
	 */
	private function getLastPaymentSmsParams(): ?array
	{
		if (!$this->payment || !Main\Loader::includeModule('messageservice'))
		{
			return null;
		}

		$activity = \CCrmActivity::GetList(
			['ID' => 'DESC'],
			[
				'BINDINGS' => [
					[
						'OWNER_ID' => $this->payment->getId(),
						'OWNER_TYPE_ID' => CCrmOwnerType::OrderPayment,
					]
				],
				'PROVIDER_ID' => Bitrix\Crm\Activity\Provider\Sms::getId(),
				'PROVIDER_TYPE_ID' => Sms::PROVIDER_TYPE_SALESCENTER_PAYMENT_SENT,
			]
		)->fetch();

		if (!$activity)
		{
			return null;
		}

		$message = MessageService\Message::getFieldsById((int)$activity['ASSOCIATED_ENTITY_ID']);

		return is_array($message) ? $message : null;
	}

	/**
	 * @return string|null
	 */
	private function getLastPaymentSms(): ?string
	{
		$message = $this->getLastPaymentSmsParams();

		return $message ? $message['MESSAGE_BODY'] : null;
	}

	/**
	 * @param string $category
	 * @return array
	 */
	private function getMarketplaceInstalledApps(string $category): array
	{
		if (!RestManager::getInstance()->isEnabled())
		{
			return [];
		}

		$marketplaceInstalledApps = [];
		$marketplaceAppCodeList = RestManager::getInstance()->getMarketplaceAppCodeList($category);
		$appIterator = Rest\AppTable::getList([
			'select' => [
				'ID',
				'CODE',
			],
			'filter' => [
				'=CODE' => $marketplaceAppCodeList,
				'=ACTIVE' => 'Y',
			],
		]);
		while ($row = $appIterator->fetch())
		{
			$marketplaceInstalledApps[$row['CODE']] = $row;
		}

		return $marketplaceInstalledApps;
	}

	private function getOrderIdListByDealId(int $dealId): array
	{
		static $result = [];

		if (!empty($result[$dealId]))
		{
			return $result[$dealId];
		}

		$dealBindingIterator = Crm\Order\DealBinding::getList([
			'select' => ['ORDER_ID'],
			'filter' => [
				'=DEAL_ID' => $dealId,
			],
			'order' => ['ORDER_ID' => 'DESC'],
		]);
		while ($dealBindingData = $dealBindingIterator->fetch())
		{
			$result[$dealId][] = $dealBindingData['ORDER_ID'];
		}

		return $result[$dealId] ?? [];
	}

	/**
	 * @param array $result
	 */
	private function fillSendersData(array &$result): void
	{
		$senders = Crm\MessageSender\SenderRepository::getPrioritizedList();
		foreach ($senders as $sender)
		{
			$senderData = [
				'code' => $sender::getSenderCode(),
				'isAvailable' => $sender::isAvailable(),
				'isConnected' => $sender::isConnected(),
				'connectUrl' => $sender::getConnectUrl(),
				'usageErrors' =>  $sender::getUsageErrors(),
			];
			if ($sender::getSenderCode() === Crm\Integration\SmsManager::getSenderCode())
			{
				$senderData['smsSenders'] = $this->getSmsSenderList();
			}

			$result['senders'][] = $senderData;
		}

		/** @var Crm\MessageSender\ICanSendMessage|null $currentSender */
		$currentSender = Crm\MessageSender\SenderPicker::getCurrentSender();
		$result['currentSenderCode'] = $currentSender ? $currentSender::getSenderCode() : '';

		$userOptions = \CUserOptions::GetOption('salescenter', 'payment_sender_options');
		$result['pushedToUseBitrix24Notifications'] = (
			is_array($userOptions)
			&& isset($userOptions['pushed_to_use_bitrix24_notifications'])
			&& in_array($userOptions['pushed_to_use_bitrix24_notifications'], ['Y', 'N'], true)
		)
			? $userOptions['pushed_to_use_bitrix24_notifications']
			: 'N';
	}

	// region Actions

	/**
	 * @param array $arParams
	 * @return array
	 */
	public function getComponentResultAction(array $arParams): array
	{
		$this->arParams = $arParams;

		$this->fillComponentResult();

		return $this->arResult;
	}

	/**
	 * @param string $smsTemplate
	 * @noinspection PhpUnused
	 */
	public function saveSmsTemplateAction(string $smsTemplate): void
	{
		if (Main\Loader::includeModule('salescenter'))
		{
			$currentSmsTemplate = CrmManager::getInstance()->getSmsTemplate();
			if ($smsTemplate !== $currentSmsTemplate)
			{
				CrmManager::getInstance()->saveSmsTemplate($smsTemplate);
			}
		}
	}

	/**
	 * @return array
	 */
	public function refreshSenderSettingsAction(): array
	{
		$result = [];

		$this->fillSendersData($result);

		return $result;
	}

	/**
	 * @param string $type
	 * @return array
	 */
	public function getAjaxDataAction(string $type): array
	{
		$result = [];
		if (Main\Loader::includeModule('salescenter'))
		{
			if ($type === 'PAY_SYSTEM')
			{
				$result = $this->getPaySystemList();
			}
			elseif ($type === 'CASHBOX')
			{
				$result = $this->getCashboxList();
			}
			elseif ($type === 'DELIVERY')
			{
				$result = $this->getDeliveryList();
			}
		}

		return $result;
	}

	// endregion
}
