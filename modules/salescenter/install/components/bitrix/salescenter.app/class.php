<?php

use Bitrix\Location\Entity\Address\Converter\StringConverter;
use Bitrix\Location\Service\FormatService;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\Application;
use Bitrix\Main\PhoneNumber;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
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
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\Entity\TimelineBindingTable;
use Bitrix\Rest;
use \Bitrix\SalesCenter;
use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Location\Entity\Address;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

define('SALESCENTER_RECEIVE_PAYMENT_APP_AREA', true);

Loader::includeModule('sale');

class CSalesCenterAppComponent extends CBitrixComponent implements Controllerable
{
	private const TITLE_LENGTH_LIMIT = 50;

	private const LIMIT_COUNT_PAY_SYSTEM = 3;

	/**
	 * @param $arParams
	 * @return array
	 */
	public function onPrepareComponentParams($arParams)
	{
		if(!$arParams['dialogId'])
		{
			$arParams['dialogId'] = $this->request->get('dialogId');
		}

		$arParams['sessionId'] = intval($arParams['sessionId']);
		if(!$arParams['sessionId'])
		{
			$arParams['sessionId'] = intval($this->request->get('sessionId'));
		}

		if(!isset($arParams['disableSendButton']))
		{
			$arParams['disableSendButton'] = ($this->request->get('disableSendButton') === 'y');
		}
		else
		{
			$arParams['disableSendButton'] = (bool)$arParams['disableSendButton'];
		}

		if(!isset($arParams['context']))
		{
			$arParams['context'] = $this->request->get('context');
		}

		if(!isset($arParams['associatedEntityId']))
		{
			$arParams['associatedEntityId'] = $this->request->get('associatedEntityId');
		}

		if(!isset($arParams['associatedEntityTypeId']))
		{
			$arParams['associatedEntityTypeId'] = $this->request->get('associatedEntityTypeId');
		}

		if(!isset($arParams['ownerId']))
		{
			$arParams['ownerId'] = intval($this->request->get('ownerId'));
		}
		if(!isset($arParams['ownerTypeId']))
		{
			$arParams['ownerTypeId'] = $this->request->get('ownerTypeId');
		}

		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		if(!\Bitrix\Main\Loader::includeModule("salescenter"))
		{
			ShowError(Loc::getMessage('SALESCENTER_MODULE_ERROR'));
			Application::getInstance()->terminate();
		}

		if(!\Bitrix\Main\Loader::includeModule("crm"))
		{
			ShowError(Loc::getMessage('CRM_MODULE_ERROR'));
			Application::getInstance()->terminate();
		}

		if(!Driver::getInstance()->isEnabled())
		{
			$this->arResult['isShowFeature'] = true;
			$this->includeComponentTemplate('limit');
			return;
		}

		$controller = new \Bitrix\SalesCenter\Controller\Order();

		PullManager::getInstance()->subscribeOnConnect();
		$this->arResult = Driver::getInstance()->getManagerParams();
		$this->arResult['isFrame'] = Application::getInstance()->getContext()->getRequest()->get('IFRAME') === 'Y';
		$this->arResult['isCatalogAvailable'] = (\Bitrix\Main\Config\Option::get('salescenter', 'is_catalog_enabled', 'N') === 'Y');
		$this->arResult['dialogId'] = $this->arParams['dialogId'];
		$this->arResult['sessionId'] = $this->arParams['sessionId'];
		$this->arResult['context'] = $this->arParams['context'];
		$this->arResult['associatedEntityId'] = $this->arParams['associatedEntityId'];
		$this->arResult['associatedEntityTypeId'] = $this->arParams['associatedEntityTypeId'];
		$this->arResult['orderAddPullTag'] = PullManager::getInstance()->subscribeOnOrderAdd();
		$this->arResult['landingUnPublicationPullTag'] = PullManager::getInstance()->subscribeOnLandingUnPublication();
		$this->arResult['landingPublicationPullTag'] = PullManager::getInstance()->subscribeOnLandingPublication();
		$this->arResult['isOrderPublicUrlExists'] = (LandingManager::getInstance()->isOrderPublicUrlExists());
		$this->arResult['isOrderPublicUrlAvailable'] = (LandingManager::getInstance()->isOrderPublicUrlAvailable());
		$this->arResult['orderPublicUrl'] = Main\Engine\UrlManager::getInstance()->getHostUrl().'/';
		$this->arResult['disableSendButton'] = $this->arParams['disableSendButton'];
		$this->arResult['ownerTypeId'] = $this->arParams['ownerTypeId'];
		$this->arResult['ownerId'] = $this->arParams['ownerId'];
		$this->arResult['isPaymentsLimitReached'] = Bitrix24Manager::getInstance()->isPaymentsLimitReached();
		$this->arResult['urlSettingsCompanyContacts'] = $this->getComponentSliderPath('bitrix:salescenter.company.contacts');
		$this->arResult['urlSettingsSmsSenders'] = $this->getUrlSmsProviderSetting();
		$this->arResult['mostPopularProducts'] = $this->getMostPopularProducts();

		$clientInfo = $controller->getClientInfo([
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

		if ($this->arParams['context'] === 'deal')
		{
			if ((int)$this->arParams['ownerTypeId'] === CCrmOwnerType::Deal)
			{
				$deal = CCrmDeal::GetByID($this->arParams['ownerId']);
				if (!$deal)
				{
					ShowError(Loc::getMessage('SALESCENTER_ERROR_DEAL_NO_FOUND'));
					Application::getInstance()->terminate();
				}

				$this->arResult['sendingMethod'] = 'sms';
				$this->arResult['sendingMethodDesc'] = $this->getSendingMethodDescByType($this->arResult['sendingMethod']);

				$stageId = CrmManager::getInstance()->getStageWithOrderPaidTrigger(
					$this->arParams['ownerId']
				);
				$this->arResult['stageOnOrderPaid'] = $stageId;
				$this->arResult['dealStageList'] = $this->getDealStageList($deal['CATEGORY_ID'], $stageId);
				$this->arResult['contactBlock'] = $this->getContactBlockInfo($deal);
				$this->arResult['contactPhone'] = $this->getDealContactPhoneFormat($deal['ID']);
				$this->arResult['title'] = Loc::getMessage('SALESCENTER_APP_PAY_TITLE');
				$this->arResult['orderPropertyValues'] = [];

				if((int)$this->arResult['associatedEntityTypeId'] === CCrmOwnerType::Order)
				{
					$orderData = $this->getOrderData((int)$this->arResult['associatedEntityId']);

					$products = $orderData['basket'];

					$this->arResult['basket'] = $products;
					$this->arResult['totals'] = $this->getTotalSumList($products);

					$this->arResult['timeline'] = $this->getOrderTimeLine((int)$this->arResult['associatedEntityId']);

					$this->arResult['orderPropertyValues'] = $orderData['propValues'];
					$this->arResult['shipmentData'] = $orderData['shipmentData'];
				}
				elseif (!$this->hasBindingOrders($deal['ID']))
				{
					$products = $this->getDealProducts($deal['ID']);
					$this->arResult['basket'] = $products;
					$this->arResult['totals'] = $this->getTotalSumList($products);
				}

				if((int)$this->arResult['associatedEntityTypeId'] !== CCrmOwnerType::Order)
				{
					$this->fillAddressToPropertyValue($deal['ID']);
					$this->fillAddressFromPropertyValue();
				}

				if (
					!isset($this->arResult['timeline'])
					|| count($this->arResult['timeline']) === 0
				)
				{
					$this->arResult['timeline'] = $this->getDefaultTimeline();
				}

				$this->arResult['paySystemList'] = $this->getPaySystemList();
				$this->arResult['deliveryList'] = $this->getDeliveryList();

				$this->arResult['cashboxList'] = [];
				if (Driver::getInstance()->isCashboxEnabled())
				{
					$this->arResult['cashboxList'] = $this->getCashboxList();
				}

				$this->arResult['isAutomationAvailable'] = Crm\Automation\Factory::isAutomationAvailable(\CCrmOwnerType::Deal);
				$this->arResult['assignedById'] = $deal['ASSIGNED_BY_ID'];
			}
		}

		if($this->arResult['sessionId'] > 0)
		{
			$sessionInfo = ImOpenLinesManager::getInstance()->setSessionId($this->arResult['sessionId'])->getSessionInfo();
			if($sessionInfo)
			{
				$this->arResult['connector'] = $sessionInfo['SOURCE'];
				$this->arResult['lineId'] = $sessionInfo['CONFIG_ID'];
			}
		}

		if (Loader::includeModule('sale')
			&& Loader::includeModule('currency')
			&& Loader::includeModule('catalog')
		)
		{
			$this->arResult['orderCreationOption'] = 'order_creation';
			$this->arResult['paySystemBannerOptionName'] = 'hide_paysystem_banner';
			$this->arResult['showPaySystemSettingBanner'] = true;

			$baseCurrency = SiteCurrencyTable::getSiteCurrency(SITE_ID);
			if (empty($baseCurrency))
			{
				$baseCurrency = CurrencyManager::getBaseCurrency();
			}
			$this->arResult['currencyCode'] = $baseCurrency;
			$currencyDescription = \CCurrencyLang::GetFormatDescription($baseCurrency);
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

			$this->arResult['currencySymbol'] = \CCrmCurrency::GetCurrencyText($baseCurrency);

			$dbMeasureResult = \CCatalogMeasure::getList(
				array('CODE' => 'ASC'),
				array(),
				false,
				array('nTopCount' => 100),
				array('CODE', 'SYMBOL_RUS', 'SYMBOL_INTL', 'IS_DEFAULT')
			);

			$this->arResult['measures'] = [];
			while($measureFields = $dbMeasureResult->Fetch())
			{
				$this->arResult['measures'][] = [
					'CODE' => intval($measureFields['CODE']),
					'IS_DEFAULT' => $measureFields['IS_DEFAULT'],
					'SYMBOL' => isset($measureFields['SYMBOL_RUS'])
						? $measureFields['SYMBOL_RUS'] : $measureFields['SYMBOL_INTL'],
				];
			}

			$this->arResult['showPaySystemSettingBanner'] = $this->needShowPaySystemSettingBanner();
			if ($this->arResult['showPaySystemSettingBanner'])
			{
				$options = \CUserOptions::GetOption('salescenter', $this->arResult['orderCreationOption'], []);
				$this->arResult['showPaySystemSettingBanner'] = ($options[$this->arResult['paySystemBannerOptionName']] !== 'Y');
			}
		}

		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('SALESCENTER_APP_TITLE'));
		$this->includeComponentTemplate();
	}

	protected function getTotalSumList(array $products)
	{
		$result = [
			'discount' => 0,
			'result' => 0,
			'sum' => 0,
		];

		foreach ($products as $product)
		{
			$discount = 0;
			if ($product['discount'] > 0)
			{
				if ($product['discountType'] === 'percent')
				{
					$discount = $product['basePrice'] * $product['discount'] / 100;
				}
				else
				{
					$discount = $product['discount'];
				}
			}

			$result['discount'] += $discount * $product['quantity'];
			$result['result'] += $product['price'] * $product['quantity'];
			$result['sum'] += $product['basePrice'] * $product['quantity'];
		}

		return $result;
	}

	protected function getContactBlockInfo($deal)
	{
		return [
			'title' => Loc::getMessage(
				'SALESCENTER_APP_CONTACT_BLOCK_TITLE_SMS',
				[
					'#PHONE#' => $this->getDealContactPhoneFormat($deal['ID']),
				]
			),
			'manager' => $this->getManagerInfo($deal['ASSIGNED_BY']),
			'smsSenders' => $this->getSmsSenderList(),
		];
	}

	protected function getDefaultTimeline()
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

	protected function getSmsSenderList()
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

	public function getSmsSenderListAction()
	{
		$result = [];
		if (Main\Loader::includeModule("salescenter") && Main\Loader::includeModule("crm"))
		{
			$result = $this->getSmsSenderList();
		}
		return $result;
	}

	/**
	 * @param $orderId
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\LoaderException
	 */
	protected function getOrderData($orderId)
	{
		$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
		/** @var Sale\Order $orderClass */
		$orderClass = $registry->getOrderClassName();

		/** @var Sale\Order $entity */
		$entity = $orderClass::load($orderId);

		return [
			'basket' => $this->getOrderProducts($entity),
			'propValues' => $this->getOrderPropValues($entity),
			'shipmentData' => $this->getShipmentData($entity),
		];
	}

	/**
	 * @param Sale\Order $entity
	 * @return array
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 */
	protected function getOrderProducts(Sale\Order $entity)
	{
		$result = [];
		$basket = $entity->getBasket();

		/**
		 * @var int $index
		 * @var Crm\Order\BasketItem $basketItem
		 */
		foreach ($basket as $index => $basketItem)
		{
			$item = [
				'productId' => $basketItem->getField('PRODUCT_ID'),
				'code' => $basketItem->getField('PRODUCT_ID'),
				'name' => $basketItem->getField('NAME'),
				'sort' => $index,
				'basePrice' => $basketItem->getField('BASE_PRICE'),
				'isCustomPrice' => $basketItem->getField('CUSTOM_PRICE'),
				'module' => 'catalog',
				'price' => $basketItem->getField('PRICE'),
				'quantity' => (float)$basketItem->getField('QUANTITY'),
				'measureCode' => $basketItem->getField('MEASURE_CODE'),
				'measureName' => $basketItem->getField('MEASURE_NAME'),
				'taxRate' => $basketItem->getField('VAT_RATE'),
				'taxIncluded' => $basketItem->getField('VAT_INCLUDED'),
				'fileControl' => (new \Bitrix\SalesCenter\Controller\Order())->getFileControl(
					$basketItem->getField('PRODUCT_ID')
				),
			];

			if ($basketItem->getDiscountPrice() > 0)
			{
				$item['discountType'] = 'currency';
				$item['discount'] = $basketItem->getDiscountPrice();
				$item['showDiscount'] = 'Y';
			}

			$result[] = $item;
		}

		return $result;
	}

	/**
	 * @param Sale\Order $entity
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\NotImplementedException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function getOrderPropValues(Sale\Order $entity)
	{
		$result = [];

		/** @var Sale\PropertyValueCollection $propValueCollection */
		$propValueCollection = $entity->getPropertyCollection();

		/** @var Sale\PropertyValue $propValue */
		foreach ($propValueCollection as $propValue)
		{
			$result[$propValue->getPropertyId()] = $propValue->getValue();
		}

		return $result;
	}

	/**
	 * @param Sale\Order $entity
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 */
	protected function getShipmentData(Sale\Order $entity)
	{
		$result = [
			'deliveryServiceId' => null,
			'responsibleId' => null,
			'extraServicesValues' => [],
		];

		$shipmentCollection = $entity->getShipmentCollection()->getNotSystemItems();

		$shipment = null;
		/** @var Sale\Shipment $shipment */
		foreach ($shipmentCollection as $shipment)
		{
			break;
		}

		if (is_null($shipment))
		{
			return $result;
		}

		$result['deliveryServiceId'] = $shipment->getDeliveryId();
		$result['responsibleId'] = $shipment->getField('RESPONSIBLE_ID');
		$result['deliveryPrice'] = $shipment->getPrice();
		$result['expectedDeliveryPrice'] = (float)$shipment->getField('EXPECTED_PRICE_DELIVERY');

		$extraServiceManager = new Sale\Delivery\ExtraServices\Manager($shipment->getDeliveryId());
		$extraServiceManager->setValues($shipment->getExtraServices());
		$items = $extraServiceManager->getItems();

		$extraServicesValues = [];
		foreach ($items as $id => $item)
		{
			$extraServicesValues[$id] = $item->getValue();
		}

		$result['extraServicesValues'] = $extraServicesValues;

		return $result;
	}

	protected function getOrderTimeline($orderId)
	{
		$items = $this->getTimeLineItemsByFilter([
			'TYPE_ID'=>[
				CCrmOwnerType::Order,
				CCrmOwnerType::OrderCheck,
			],
			'ENTITY_ID'=>$orderId,
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
	 * @param $categoryId
	 * @param $stageId
	 * @return array
	 */
	protected function getDealStageList($categoryId, $stageId)
	{
		$result = [
			[
				'type' => 'invariable',
				'name' => Loc::getMessage('SALESCENTER_AUTOMATION_STEPS_STAY'),
				'selected' => $stageId === ''
			],
		];

		$dealStageList = CCrmViewHelper::GetDealStageInfos($categoryId);
		foreach ($dealStageList as $stage)
		{
			$result[] = [
				'id' => $stage['STATUS_ID'],
				'type' => 'stage',
				'name' => $stage['NAME'],
				'color' => $stage['COLOR'],
				'colorText' => $this->getStageColorText($stage['COLOR']),
				'selected' => $stage['STATUS_ID'] === $stageId
			];
		}

		return $result;
	}

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
	protected function getManagerInfo($userId)
	{
		$result = [
			'name' => '',
			'photo' => '',
		];

		$dbRes = \CUser::GetList(
			($by = 'id'),
			($order = 'asc'),
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
	 * @param $dealId
	 * @return array
	 */
	protected function getDealProducts($dealId)
	{
		$result = [];

		$productRows = CCrmDeal::LoadProductRows($dealId);
		foreach ($productRows as $index => $product)
		{
			$item = [
				'productId' => $product['PRODUCT_ID'],
				'code' => $product['PRODUCT_ID'],
				'name' => $product['PRODUCT_NAME'],
				'sort' => $index,
				'module' => $product['PRODUCT_ID'] > 0 ? 'catalog' : '',
				'basePrice' => $product['PRICE_BRUTTO'],
				'isCustomPrice' => 'Y',
				'price' => $product['PRICE_BRUTTO'],
				'quantity' => $product['QUANTITY'],
				'measureCode' => $product['MEASURE_CODE'],
				'measureName' => $product['MEASURE_NAME'],
				'taxRate' => $product['TAX_RATE'],
				'taxIncluded' => $product['TAX_INCLUDED'],
				'fileControl' => (new \Bitrix\SalesCenter\Controller\Order())->getFileControl(
					$product['PRODUCT_ID']
				),
			];

			if ((int)$product['DISCOUNT_TYPE_ID'] === \Bitrix\Crm\Discount::PERCENTAGE)
			{
				if ($product['DISCOUNT_RATE'] > 0)
				{
					$item['discountType'] = 'percent';
					$item['discount'] = $product['DISCOUNT_RATE'];
					$item['showDiscount'] = 'Y';
				}
			}
			elseif ((int)$product['DISCOUNT_TYPE_ID'] === \Bitrix\Crm\Discount::MONETARY)
			{
				$item['discountType'] = 'currency';
				$item['discount'] = $product['DISCOUNT_SUM'];
				$item['showDiscount'] = 'Y';
			}

			$result[] = $item;
		}

		return $result;
	}

	/**
	 * @param int $dealId
	 * @return string
	 * @throws Main\ArgumentException
	 */
	protected function getDealContactPhoneFormat(int $dealId)
	{
		$contact = $this->getPrimaryContact($dealId);
		if (!$contact)
		{
			return '';
		}

		$phones = CCrmFieldMulti::GetEntityFields(
			'CONTACT',
			$contact['CONTACT_ID'],
			'PHONE',
			true,
			false
		);
		$phone = current($phones);
		if (!is_array($phone))
		{
			return '';
		}

		return PhoneNumber\Parser::getInstance()->parse($phone['VALUE'])->format();
	}

	/**
	 * @param int $dealId
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getClientLocationsList(int $dealId)
	{
		$contact = $this->getPrimaryContact($dealId);
		if (!$contact)
		{
			return [];
		}

		$requisite = Crm\EntityRequisite::getSingleInstance()->getList(
			[
				'filter' => [
					'=ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
					'=ENTITY_ID' => (int)$contact['CONTACT_ID']
				],
			]
		)->fetch();

		if (!$requisite)
		{
			return [];
		}

		$result = [];

		$addresses = Crm\AddressTable::getList(
			[
				'filter' => [
					'ENTITY_ID' => (int)$requisite['ID'],
					'ENTITY_TYPE_ID' => CCrmOwnerType::Requisite,
					'>LOC_ADDR_ID' => 0,
				],
			]
		)->fetchAll();

		$defaultAddressTypeId = Crm\RequisiteAddress::getDefaultTypeId();

		$sortingMap = [
			Crm\EntityAddress::Delivery => 10,
			$defaultAddressTypeId => 20,
		];

		foreach ($addresses as $address)
		{
			$locationArray = SalesCenter\Integration\LocationManager::getInstance()
				->getFormattedLocationArray(
					(int)$address['LOC_ADDR_ID']
				);

			if (!$locationArray)
			{
				continue;
			}

			$result[$address['TYPE_ID']] = [
				'VALUE' => $locationArray,
				'SORT' => isset($sortingMap[$address['TYPE_ID']]) ? $sortingMap[$address['TYPE_ID']] : 100,
			];
		}

		uasort($result, function ($a, $b) {
			return $a['SORT'] < $b['SORT'] ? -1 : 1;
		});

		return array_column($result, 'VALUE');
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getMyCompanyLocationsList()
	{
		$requisite = Crm\EntityRequisite::getSingleInstance()->getList(
			[
				'filter' => [
					'=ENTITY_TYPE_ID' => CCrmOwnerType::Company,
					'=ENTITY_ID' => (int)Crm\Requisite\EntityLink::getDefaultMyCompanyId()
				],
			]
		)->fetch();

		if (!$requisite)
		{
			return [];
		}

		$result = [];

		$addresses = Crm\AddressTable::getList(
			[
				'filter' => [
					'ENTITY_ID' => (int)$requisite['ID'],
					'ENTITY_TYPE_ID' => CCrmOwnerType::Requisite,
					'>LOC_ADDR_ID' => 0,
				],
			]
		)->fetchAll();

		$sortingMap = [
			Crm\EntityAddress::Primary => 10,
			Crm\EntityAddress::Delivery => 20,
		];

		foreach ($addresses as $address)
		{
			$locationArray = SalesCenter\Integration\LocationManager::getInstance()
				->getFormattedLocationArray(
					(int)$address['LOC_ADDR_ID']
				);
			if (!$locationArray)
			{
				continue;
			}

			$result[$address['TYPE_ID']] = [
				'VALUE' => $locationArray,
				'SORT' => isset($sortingMap[$address['TYPE_ID']]) ? $sortingMap[$address['TYPE_ID']] : 100,
			];
		}

		uasort($result, function ($a, $b) {
			return $a['SORT'] < $b['SORT'] ? -1 : 1;
		});

		return array_column($result, 'VALUE');
	}

	/**
	 * @param int $dealId
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function fillAddressToPropertyValue(int $dealId)
	{
		if(!Loader::includeModule('location'))
		{
			return;
		}

		$clientLocationsList = $this->getClientLocationsList($dealId);
		if (!$clientLocationsList)
		{
			return;
		}

		$props = Sale\Property::getList(
			[
				'filter' => [
					'=PERSON_TYPE_ID' => $this->arResult['personTypeId'],
					'ACTIVE' => 'Y',
					'TYPE' => 'ADDRESS',
					'CODE' => Sale\Delivery\Services\OrderPropsDictionary::ADDRESS_TO_PROPERTY_CODE,
				],
			]
		)->fetchAll();

		foreach ($props as $prop)
		{
			$this->arResult['orderPropertyValues'][$prop['ID']] = $clientLocationsList[0]['address'];

			$this->arResult['deliveryOrderPropOptions'][$prop['ID']] = [
				'defaultItems' => $clientLocationsList
			];
		}
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function fillAddressFromPropertyValue()
	{
		if(!Loader::includeModule('location'))
		{
			return;
		}

		$locationsList = $this->getMyCompanyLocationsList();
		$defaultLocationFrom = SalesCenter\Integration\LocationManager::getInstance()->getDefaultLocationFrom();

		if ($defaultLocationFrom)
		{
			array_unshift($locationsList, $defaultLocationFrom);
		}

		if (!$locationsList)
		{
			return;
		}

		$props = Sale\Property::getList(
			[
				'filter' => [
					'=PERSON_TYPE_ID' => $this->arResult['personTypeId'],
					'ACTIVE' => 'Y',
					'TYPE' => 'ADDRESS',
					'CODE' => Sale\Delivery\Services\OrderPropsDictionary::ADDRESS_FROM_PROPERTY_CODE,
				],
			]
		)->fetchAll();

		foreach ($props as $prop)
		{
			$this->arResult['orderPropertyValues'][$prop['ID']] = $locationsList[0]['address'];

			$this->arResult['deliveryOrderPropOptions'][$prop['ID']] = [
				'defaultItems' => $locationsList
			];
		}
	}

	/**
	 * @param int $dealId
	 * @return mixed|null
	 * @throws Main\ArgumentException
	 */
	private function getPrimaryContact(int $dealId)
	{
		$contacts = DealContactTable::getDealBindings($dealId);
		foreach ($contacts as $contact)
		{
			if ($contact['IS_PRIMARY'] !== 'Y')
			{
				continue;
			}


			return $contact;
		}

		return null;
	}

	protected function hasBindingOrders($dealId)
	{
		$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);

		/** @var Crm\Order\DealBinding $dealBinding */
		$dealBinding = $registry->get(ENTITY_CRM_ORDER_DEAL_BINDING);

		return (bool)$dealBinding::getList([
			'select' => ['ORDER_ID'],
			'filter' => [
				'=DEAL_ID' => $dealId,
			],
		])->fetch();
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\IO\FileNotFoundException
	 * @throws Main\SystemException
	 */
	protected function getPaySystemList(): array
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
			$systemHandlers = array_keys($handlerList["SYSTEM"]);
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
	 * @throws Main\SystemException
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
		foreach($marketplaceItemCodeList as $marketplaceItemCode)
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
	 * @throws Main\SystemException
	 */
	protected function getDeliveryList(): array
	{
		$result = [
			'isInstalled' => false,
			'items' => []
		];

		$handlers = (new SalesCenter\Delivery\Handlers\HandlersRepository())
			->getCollection()
			->getInstallableItems();

		$internalItems = [];
		foreach ($handlers as $handler)
		{
			if ($handler->isInstalled())
			{
				$result['isInstalled'] = true;
			}

			$showTitle = false;
			if ($handler->isRestHandler())
			{
				$showTitle = true;
			}

			$internalItems[] = [
				'code' => $handler->getCode(),
				'name' => $handler->getName(),
				'link' => $handler->getInstallationLink(),
				'img' => $handler->getImagePath(),
				'info' => $handler->getShortDescription(),
				'type' => 'delivery',
				'showTitle' => $showTitle,
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
	 * @throws Main\SystemException
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
		foreach($marketplaceItemCodeList as $marketplaceItemCode)
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
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function getCashboxList()
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
			$cashboxHandlers = SaleManager::getCashboxHandlers();
			/** @var Cashbox\Cashbox $cashboxHandler */
			foreach ($cashboxHandlers as $cashboxHandler)
			{
				$queryParams['handler'] = $cashboxHandler;
				$cashboxPath->addParams($queryParams);

				$img = '';
				if (mb_strpos($queryParams['handler'], Cashbox\CashboxAtolFarmV4::class) !== false)
				{
					$img = '/bitrix/components/bitrix/salescenter.cashbox.panel/templates/.default/images/atol.svg';
				}
				elseif (mb_strpos($queryParams['handler'], Cashbox\CashboxOrangeData::class) !== false)
				{
					$img = '/bitrix/components/bitrix/salescenter.cashbox.panel/templates/.default/images/orangedata.svg';
				}
				elseif (mb_strpos($queryParams['handler'], Cashbox\CashboxCheckbox::class) !== false)
				{
					$img = '/bitrix/components/bitrix/salescenter.cashbox.panel/templates/.default/images/checkbox.svg';
				}
				elseif (mb_strpos($queryParams['handler'], Cashbox\CashboxRest::class) !== false)
				{
					$img = '/bitrix/components/bitrix/salescenter.cashbox.panel/templates/.default/images/offline.svg';
				}

				if (!mb_strpos($queryParams['handler'], Cashbox\CashboxRest::class))
				{
					$name = $cashboxHandler::getName();
					$result['items'][] = [
						'name' => $cashboxHandler::getName(),
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
				else
				{
					$restHandlers = Cashbox\Manager::getRestHandlersList();
					foreach ($restHandlers as $restHandlerCode => $restHandler)
					{
						$queryParams['restHandler'] = $restHandlerCode;
						$cashboxPath->addParams($queryParams);
						$name = $restHandler['NAME'];
						$result['items'][] = [
							'name' => $name,
							'img' => $img,
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
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function needShowPaySystemSettingBanner() : bool
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
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
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

		$result = [];
		while ($product = $mostPopularProducts->fetch())
		{
			$result[] = $product;
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
	 * @throws Main\ArgumentException
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
	 * @throws Main\ArgumentException
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
	 * @param string $type
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\IO\FileNotFoundException
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 */
	public function getAjaxDataAction(string $type): array
	{
		$result = [];
		if (Main\Loader::includeModule("salescenter"))
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

	/**
	 * @inheritDoc
	 */
	public function configureActions()
	{
		return [];
	}

	protected function prepareTimeLineItemsDateTime($date, $format = 'j F Y H:i')
	{
		$result = '';
		if ($date instanceof \Bitrix\Main\Type\DateTime)
		{
			$result = FormatDate($format, $date->getTimestamp() + CTimeZone::GetOffset());
		}
		return $result;
	}

	protected function prepareTimeLineItems($items)
	{
		$result = [];
		foreach ($items as $item)
		{
			if($item['ASSOCIATED_ENTITY_TYPE_ID'] == CCrmOwnerType::OrderPayment)
			{
				if($item['CHANGED_ENTITY'] == CCrmOwnerType::OrderPaymentName
					&& $item['FIELDS']['ORDER_PAID'] == 'Y')
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
				if($item['TYPE_CATEGORY_ID'] == TimelineType::MARK)
				{
					if(isset($item['SENDED']) && $item['SENDED'] == 'Y')
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
				elseif($item['TYPE_CATEGORY_ID'] == TimelineType::UNDEFINED)
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
			elseif($item['ASSOCIATED_ENTITY_TYPE_ID'] == CCrmOwnerType::Order)
			{
				if($item['TYPE_CATEGORY_ID'] == TimelineType::MODIFICATION)
				{
					if(isset($item['FIELDS']['VIEWED']) && $item['FIELDS']['VIEWED'] == 'Y')
					{
						$result[] = [
							'type'=>'watch',
							'content'=>Loc::getMessage('SALESCENTER_TIMELINE_WATCH_CONTENT',[
								'#DATE_CREATED#'=>$this->prepareTimeLineItemsDateTime($item['CREATED']),
							]),
						];
					}
					elseif (isset($item['FIELDS']['SENT']) && $item['FIELDS']['SENT'] == 'Y')
					{
						$result[] = [
							'type'=>'sent',
							'url'=>$item['ASSOCIATED_ENTITY']['SHOW_URL'],
							'content'=>Loc::getMessage('SALESCENTER_TIMELINE_SENT_CONTENT',[
								'#DATE_CREATED#'=>$this->prepareTimeLineItemsDateTime($item['CREATED']),
							]),
						];
					}
				}
			}
		}
		return $result;
	}

	protected function getTimeLineItemsByFilter($filter)
	{
		$query = new Query(TimelineTable::getEntity());
		$query->addSelect('*');

		$bindingQuery = new Query(TimelineBindingTable::getEntity());
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

		while($fields = $dbResult->fetch())
		{
			$itemID = (int)$fields['ID'];
			$items[] = $fields;
			$itemIDs[] = $itemID;
		}

		$itemsMap = array_combine($itemIDs, $items);
		\Bitrix\Crm\Timeline\TimelineManager::prepareDisplayData($itemsMap);
		return array_values($itemsMap);
	}

	protected function getDefaultSmsProvider()
	{
		$list = $this->getSmsSenderList();

		return count($list)>0 ? $list[0]['id']:'';
	}

	protected function getSendingMethodDescByType($type)
	{
		if ($type === 'sms')
		{
			$text = '';
			if (
				(int)$this->arResult['associatedEntityTypeId'] === CCrmOwnerType::Order
				&& $this->arResult['associatedEntityId'] > 0
			)
			{
				$text = $this->getLastSmsMessageByOrderId((int)$this->arResult['associatedEntityId']);
			}

			return [
				'provider' => $this->getDefaultSmsProvider(),
				'text' => $text <> ''? $text:CrmManager::getInstance()->getSmsTemplate(),
			];
		}

		return [];
	}

	private function getZone()
	{
		if (Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			$zone = \CBitrix24::getPortalZone();
		}
		else
		{
			$iterator = Main\Localization\LanguageTable::getList([
				'select' => ['ID'],
				'filter' => ['=DEF' => 'Y', '=ACTIVE' => 'Y'],
			]);
			$row = $iterator->fetch();
			$zone = $row['ID'];
		}

		return $zone;
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

	protected function getUrlSmsProviderSetting()
	{
		$path = \CComponentEngine::makeComponentPath('bitrix:salescenter.smsprovider.panel');
		$path = getLocalPath('components'.$path.'/slider.php');
		$path = new \Bitrix\Main\Web\Uri($path);
		return $path->getLocator();
	}

	protected function getLastSmsMessageByOrderId($orderId = 0)
	{
		$result = '';
		if(intval($orderId)>0)
		{
			if (Loader::includeModule('messageservice'))
			{
				$filter['BINDINGS'][] = [
					'OWNER_ID' => $orderId,
					'OWNER_TYPE_ID' => CCrmOwnerType::Order,
				];

				$filter['PROVIDER_ID'] = Bitrix\Crm\Activity\Provider\Sms::getId();
				$filter['PROVIDER_TYPE_ID'] = Bitrix\Crm\Activity\Provider\Sms::getTypeId([]);

				$res = \CCrmActivity::GetList(
					["ID"=>"DESC"],
					$filter,
					false, false,
					[]
				);
				if($row = $res->fetch())
				{
					$id = intval($row['ASSOCIATED_ENTITY_ID']) ?:0;
					$message = $id > 0 ? \Bitrix\MessageService\Message::getFieldsById($id):[];

					$result = isset($message['MESSAGE_BODY']) ? $message['MESSAGE_BODY']:'';
				}
			}
		}

		return $result;
	}

	/**
	 * @param string $category
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getMarketplaceInstalledApps(string $category): array
	{
		if(!RestManager::getInstance()->isEnabled())
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

	/**
	 * @param $smsTemplate
	 * @noinspection PhpUnused
	 */
	public function saveSmsTemplateAction($smsTemplate): void
	{
		if (Main\Loader::includeModule("salescenter"))
		{
			$currentSmsTemplate = CrmManager::getInstance()->getSmsTemplate();
			if ($smsTemplate !== $currentSmsTemplate)
			{
				CrmManager::getInstance()->saveSmsTemplate($smsTemplate);
			}
		}
	}
}