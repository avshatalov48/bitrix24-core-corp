<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Catalog;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\v2\Integration\JS\ProductForm;
use Bitrix\Catalog\v2\Integration\JS\ProductForm\BasketItem;
use Bitrix\Catalog\v2\Integration\Seo\Facebook\FacebookFacade;
use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Catalog\VatTable;
use Bitrix\Crm;
use Bitrix\Crm\Service\Container;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Web\Uri;
use Bitrix\Rest;
use Bitrix\Sale;
use Bitrix\Sale\Cashbox;
use Bitrix\Sale\Internals\SiteCurrencyTable;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\PaySystem\ClientType;
use Bitrix\Sale\Tax\VatCalculator;
use Bitrix\SalesCenter;
use Bitrix\SalesCenter\Component\PaymentSlip;
use Bitrix\SalesCenter\Component\ReceivePaymentHelper;
use Bitrix\SalesCenter\Driver;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\SalesCenter\Integration\CatalogManager;
use Bitrix\SalesCenter\Integration\CrmManager;
use Bitrix\SalesCenter\Integration\ImOpenLinesManager;
use Bitrix\SalesCenter\Integration\LandingManager;
use Bitrix\SalesCenter\Integration\LocationManager;
use Bitrix\SalesCenter\Integration\PullManager;
use Bitrix\SalesCenter\Integration\RestManager;
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\Salescenter\Restriction\ToolAvailabilityManager;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

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
	private const PAYMENT_MODE = 'payment';
	private const DELIVERY_MODE = 'delivery';
	private const TERMINAL_PAYMENT_MODE = 'terminal_payment';

	/** @var Crm\Order\Order $order */
	private $order;

	/** @var Crm\Order\Payment $order */
	private $payment;

	/** @var Crm\Order\Shipment $order */
	private $shipment;

	/** @var Crm\Item|null */
	private $item;

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

		if (empty($arParams['dialogId']))
		{
			$arParams['dialogId'] = $this->request->get('dialogId');
		}

		if (isset($arParams['sessionId']))
		{
			$arParams['sessionId'] = (int)$arParams['sessionId'];
		}

		if (empty($arParams['sessionId']))
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

		$arParams['context'] = $this->getContextFromParams($arParams);

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
			$arParams['ownerId'] = (int)$this->request->get('ownerId');
		}

		if (!isset($arParams['ownerTypeId']))
		{
			$arParams['ownerTypeId'] = $this->request->get('ownerTypeId');
		}

		if (!isset($arParams['ownerTypeId']))
		{
			$arParams['ownerTypeId'] = CCrmOwnerType::Deal;
		}

		if (!isset($arParams['templateMode']))
		{
			$arParams['templateMode'] = $this->request->get('templateMode');
		}

		if (empty($arParams['templateMode']))
		{
			$arParams['templateMode'] = self::TEMPLATE_CREATE_MODE;
		}

		$arParams['orderId'] = $this->getOrderIdFromParams($arParams);

		if ($this->needOrderFromEntity($arParams))
		{
			$orderIdList = $this->getOrderIdListByEntityId($arParams['ownerId'], $arParams['ownerTypeId']);
			if ($orderIdList)
			{
				$arParams['orderId'] = current($orderIdList);
			}
		}

		if ($arParams['context'] === SalesCenter\Component\ContextDictionary::CHAT)
		{
			$crmManager = CrmManager::getInstance();
			if (
				!$crmManager->isOwnerEntityExists(
					$arParams['ownerId'],
					CCrmOwnerType::Deal
				)
				|| $crmManager->isOwnerEntityInFinalStage(
					$arParams['ownerId'],
					CCrmOwnerType::Deal
				)
			)
			{
				$arParams['ownerId'] = 0;
			}
			$arParams['ownerTypeId'] = CCrmOwnerType::Deal;
		}

		$arParams['compilationId'] ??= $this->request->get('compilationId');

		$arParams['showModeList'] = (int)$arParams['ownerTypeId'] === CCrmOwnerType::Deal;

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

		if (
			!ToolAvailabilityManager::getInstance()->checkSalescenterAvailability()
			&& $this->arParams['context'] === SalesCenter\Component\ContextDictionary::CHAT
			&& $this->arParams['templateMode'] === self::TEMPLATE_CREATE_MODE
		)
		{
			$this->includeComponentTemplate('tool_disabled');

			return;
		}

		if ((int)$this->getOrderIdFromParams($this->arParams) <= 0 && CrmManager::getInstance()->isOrderLimitReached())
		{
			$this->arResult['isOrderLimitReached'] = true;
			$this->includeComponentTemplate('limit');

			return;
		}

		$this->fillComponentResult();

		if (
			$this->arParams['context'] === SalesCenter\Component\ContextDictionary::DEAL
			&& !$this->item
		)
		{
			ShowError(Loc::getMessage('SALESCENTER_ERROR_DEAL_NO_FOUND'));
			Application::getInstance()->terminate();
		}

		$GLOBALS['APPLICATION']->setTitle($this->arResult['title']);

		$this->includeComponentTemplate();
	}

	private function getOrderIdFromParams(array $arParams): ?int
	{
		if (
			$arParams['templateMode'] === self::TEMPLATE_CREATE_MODE
			&& \CCrmSaleHelper::isWithOrdersMode()
		)
		{
			return null;
		}

		return (int)($arParams['orderId'] ?? $this->request->get('orderId'));
	}

	private function needOrderFromEntity(array $arParams): bool
	{
		if (!empty($arParams['orderId']) && (int)$arParams['orderId'] > 0)
		{
			return false;
		}

		if (\CCrmSaleHelper::isWithOrdersMode())
		{
			return false;
		}

		if ($arParams['context'] === SalesCenter\Component\ContextDictionary::TERMINAL_LIST)
		{
			return false;
		}

		if (
			$arParams['context'] === SalesCenter\Component\ContextDictionary::CHAT
			&& CrmManager::getInstance()->isOwnerEntityInFinalStage($arParams['ownerId'], $arParams['ownerTypeId'])
		)
		{
			return false;
		}

		if (
			$arParams['context'] === SalesCenter\Component\ContextDictionary::SMS
			&& (
				(int)$arParams['ownerTypeId'] === CCrmOwnerType::Contact
				|| (int)$arParams['ownerTypeId'] === CCrmOwnerType::Company
			)
		)
		{
			return false;
		}

		$isChat = (
			!empty($arParams['dialogId'])
			&& !empty($arParams['sessionId'])
			&& !empty($arParams['ownerId'])
		);

		$isSms = (
			$arParams['context'] === SalesCenter\Component\ContextDictionary::SMS
			&& !empty($arParams['ownerId'])
			&& !empty($arParams['ownerTypeId'])
		);

		return $isChat || $isSms;
	}

	private function getContextFromParams(array $arParams): ?string
	{
		return $arParams['context'] ?? $this->request->get('context');
	}

	private function fillComponentResult(): void
	{
		PullManager::getInstance()->subscribeOnConnect();
		$this->arResult = Driver::getInstance()->getManagerParams();

		$ownerId = (int)($this->arParams['ownerId'] ?? 0);
		$ownerTypeId = (int)($this->arParams['ownerTypeId'] ?? 0);

		$factory = Crm\Service\Container::getInstance()->getFactory($ownerTypeId);
		if ($factory && $factory->isPaymentsEnabled())
		{
			$this->item = $factory->getItem($ownerId);
		}
		else
		{
			$this->item = null;
		}

		if ($this->item)
		{
			$assignedById = $this->item->getAssignedById();
			$this->arResult['currencyCode'] = $this->item->getCurrencyId();
			$this->arResult['assignedById'] = $assignedById;
			$this->arResult['entityResponsible'] = $this->getManagerInfo($assignedById);
			CrmManager::getInstance()->getItemContactFields($this->item);
			$contact = CrmManager::getInstance()->getItemContactFields($this->item);
			$this->arResult['contactName'] = is_array($contact) ? \CCrmContact::PrepareFormattedName($contact) : '';
			$this->arResult['contactPhone'] = is_array($contact) ? CrmManager::getInstance()->getFormattedContactPhone((int)$contact['ID']) : '';
			$this->arResult['contactEditorUrl'] = CrmManager::getInstance()->getItemContactEditorUrl($this->item);
		}
		else
		{
			$responsibleId = Crm\Settings\OrderSettings::getCurrent()->getDefaultResponsibleId() ?: Main\Engine\CurrentUser::get()->getId();
			$this->arResult['entityResponsible'] = $this->getManagerInfo($responsibleId);
			$this->arResult['assignedById'] = $responsibleId;
		}

		if (Crm\Automation\Factory::isAutomationAvailable($ownerTypeId))
		{
			$this->arResult['stageOnOrderPaid']	= CrmManager::getInstance()->getStageWithOrderPaidTrigger(
				$ownerId,
				$ownerTypeId
			);
			$this->arResult['stageOnDeliveryFinished'] = CrmManager::getInstance()->getStageWithDeliveryFinishedTrigger(
				$ownerId,
				$ownerTypeId
			);
		}

		$this->arResult['emptyDeliveryServiceId'] = Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();

		$this->arResult['templateMode'] = $this->arParams['templateMode'];
		$this->arResult['paymentId'] = (int)$this->arParams['paymentId'];
		$this->arResult['shipmentId'] = (int)$this->arParams['shipmentId'];
		$mode = self::PAYMENT_DELIVERY_MODE;
		$allowedModes = [static::PAYMENT_MODE, static::DELIVERY_MODE, static::TERMINAL_PAYMENT_MODE];
		if (in_array($this->arParams['mode'], $allowedModes, true))
		{
			$mode = $this->arParams['mode'];
		}
		$this->arResult['mode'] = $mode;
		$this->arResult['initialMode'] = $this->arParams['initialMode'] ?? $this->arResult['mode'];
		$this->arResult['showModeList'] = $this->arParams['showModeList'] ?? true;

		if ($this->arParams['orderId'] > 0)
		{
			$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
			/** @var Crm\Order\Order $orderClassName */
			$orderClassName = $registry->getOrderClassName();

			$this->order = $orderClassName::load($this->arParams['orderId']);
			if ($this->order)
			{
				if ($this->arResult['paymentId'])
				{
					$this->payment = $this->order->getPaymentCollection()->getItemById($this->arResult['paymentId']);
					if ($this->payment && $this->arResult['shipmentId'] === 0)
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
		$this->arResult['compilation'] = $this->getCompilation();
		$this->arResult['sessionId'] = $this->arParams['sessionId'];
		$this->arResult['context'] = $this->getContextFromParams($this->arParams);
		$this->arResult['orderAddPullTag'] = PullManager::getInstance()->subscribeOnOrderAdd();
		$this->arResult['landingUnPublicationPullTag'] = PullManager::getInstance()->subscribeOnLandingUnPublication();
		$this->arResult['landingPublicationPullTag'] = PullManager::getInstance()->subscribeOnLandingPublication();
		$this->arResult['isOrderPublicUrlExists'] = (LandingManager::getInstance()->isOrderPublicUrlExists());
		$this->arResult['isOrderPublicUrlAvailable'] = (LandingManager::getInstance()->isOrderPublicUrlAvailable());
		$this->arResult['orderPublicUrl'] = Main\Engine\UrlManager::getInstance()->getHostUrl().'/';
		$this->arResult['disableSendButton'] = $this->arParams['disableSendButton'];
		$this->arResult['ownerTypeId'] = $ownerTypeId;
		$this->arResult['ownerId'] = $ownerId;
		$this->arResult['isBitrix24'] = Bitrix24Manager::getInstance()->isEnabled();
		$this->arResult['isPaymentsLimitReached'] = Bitrix24Manager::getInstance()->isPaymentsLimitReached();
		$this->arResult['urlSettingsCompanyContacts'] = $this->getComponentSliderPath('bitrix:salescenter.company.contacts');
		$this->fillSendersData($this->arResult);
		$this->arResult['mostPopularProducts'] = $this->getMostPopularProducts();
		$this->arResult['vatList'] = $this->getProductVatList();
		$this->arResult['catalogIblockId'] = (int)Crm\Product\Catalog::getDefaultId();
		$this->arResult['basePriceId'] = Catalog\GroupTable::getBasePriceTypeId();
		$notificationCenterEnabled = $this->arResult['currentSenderCode'] === \Bitrix\Crm\Integration\NotificationsManager::getSenderCode();
		$this->arResult['showCompilationModeSwitcher'] = !$notificationCenterEnabled && !$this->arResult['compilation'] ? 'Y' : 'N';
		$this->arResult['showProductDiscounts'] = \CUserOptions::GetOption('catalog.product-form', 'showDiscountBlock', 'Y');
		$this->arResult['showProductTaxes'] = \CUserOptions::GetOption('catalog.product-form', 'showTaxBlock', 'Y');
		$collapseOptions = $this->getCollapseOptions();
		$this->arResult['isPaySystemCollapsed'] = $collapseOptions['pay_system'] ?? null;
		$this->arResult['isCashboxCollapsed'] = $collapseOptions['cashbox'] ?? null;
		$this->arResult['isDeliveryCollapsed'] = $collapseOptions['delivery'] ?? null;
		$this->arResult['isAutomationCollapsed'] = $collapseOptions['automation'] ?? null;
		$this->arResult['urlProductBuilderContext'] = Main\Loader::includeModule('catalog') ? Catalog\Url\ShopBuilder::TYPE_ID : '';
		if (\CCrmOwnerType::isCorrectEntityTypeId($ownerTypeId))
		{
			$this->arResult['isCatalogPriceEditEnabled'] = AccessController::getCurrent()->checkByValue(
				ActionDictionary::ACTION_PRICE_ENTITY_EDIT,
				$ownerTypeId
			);
			$this->arResult['isCatalogDiscountSetEnabled'] = AccessController::getCurrent()->checkByValue(
				ActionDictionary::ACTION_PRODUCT_DISCOUNT_SET,
				$ownerTypeId
			);
		}
		$this->arResult['fieldHints'] = [];
		if (!$this->arResult['isCatalogPriceEditEnabled'])
		{
			$this->arResult['fieldHints']['price'] = Crm\Config\State::getProductPriceChangingNotification();
		}

		$this->arResult['isIntegrationButtonVisible'] = Bitrix24Manager::getInstance()->isIntegrationRequestPossible();

		if (empty($this->arResult['currencyCode']))
		{
			$baseCurrency = SiteCurrencyTable::getSiteCurrency(SITE_ID);
			if (empty($baseCurrency))
			{
				$baseCurrency = CurrencyManager::getBaseCurrency();
			}
			$this->arResult['currencyCode'] = $baseCurrency;
		}

		//@TODO get rid of it
		$clientInfo = (new SalesCenter\Controller\Order())->getClientInfo([
			'sessionId' => $this->arResult['sessionId'],
			'ownerTypeId' => $ownerTypeId,
			'ownerId' => $ownerId,
		]);

		$this->arResult['personTypeId'] = (!empty($clientInfo['COMPANY_ID']))
			? (int)Crm\Order\PersonType::getCompanyPersonTypeId()
			: (int)Crm\Order\PersonType::getContactPersonTypeId()
		;

		if ($this->arResult['personTypeId'] <= 0)
		{
			$this->arResult['personTypeId'] = (int)Sale\Helpers\Admin\Blocks\OrderBuyer::getDefaultPersonType(SITE_ID);
		}

		// region shipment presets
		$deliveryFromList = $this->getDeliveryFromList();
		$fromPropIds = $this->getDeliveryFromPropIds($this->arResult['personTypeId']);
		foreach ($fromPropIds as $fromPropId)
		{
			$this->arResult['deliveryOrderPropOptions'][$fromPropId] = [
				'defaultItems' => $deliveryFromList,
			];
		}

		$contactId = 0;
		if ($this->item && $this->item->getPrimaryContact())
		{
			$contactId = $this->item->getPrimaryContact()->getId();
		}
		if ($contactId > 0)
		{
			$deliveryToList = $this->getDeliveryToList($contactId);
			$toPropIds = $this->getDeliveryToPropIds($this->arResult['personTypeId']);
			foreach ($toPropIds as $toPropId)
			{
				$this->arResult['deliveryOrderPropOptions'][$toPropId] = [
					'defaultItems' => $deliveryToList,
				];
			}

			$this->arResult['shipmentData'] = $this->getShipmentData($contactId, $this->arResult['personTypeId']);
		}
		// endregion

		if (
			$ownerId > 0
			&& $ownerTypeId > 0
			&& !\CCrmSaleHelper::isWithOrdersMode()
		)
		{
			$this->arResult['orderList'] = $this->getOrderIdListByEntityId($ownerId, $ownerTypeId);
		}

		$this->arResult['sendingMethod'] = '';
		if (
			$this->arResult['context'] === SalesCenter\Component\ContextDictionary::DEAL
			|| $this->arResult['context'] === SalesCenter\Component\ContextDictionary::SMART_INVOICE
		)
		{
			$this->arResult['sendingMethod'] = 'sms';
		}
		elseif ($this->arResult['context'] === SalesCenter\Component\ContextDictionary::CHAT)
		{
			$this->arResult['sendingMethod'] = 'chat';
		}

		if (!$this->arResult['sendingMethod'] && $this->arResult['context'] === SalesCenter\Component\ContextDictionary::SMS)
		{
			$this->arResult['sendingMethodDesc'] = ReceivePaymentHelper::getSendingMethodDescByType(
				'sms',
				$this->arParams['templateMode'],
				$this->payment
			);
		}
		else
		{
			$this->arResult['sendingMethodDesc'] = ReceivePaymentHelper::getSendingMethodDescByType(
				$this->arResult['sendingMethod'],
				$this->arParams['templateMode'],
				$this->payment
			);
		}

		$this->arResult['isAutomationAvailable'] = Crm\Automation\Factory::isAutomationAvailable($ownerTypeId);
		$this->arResult['entityStageList'] = $this->getEntityStageList($ownerId, $ownerTypeId);

		$this->arResult['timeline'] = $this->getTimeLine();
		$this->arResult['paySystemList'] = $this->getPaySystemList();

		$ownerTypesForDelivery = [CCrmOwnerType::Deal, CCrmOwnerType::Contact, CCrmOwnerType::Company];
		if (in_array((int)$this->arResult['ownerTypeId'], $ownerTypesForDelivery, true))
		{
			$this->arResult['deliveryList'] = $this->getDeliveryList();
		}

		$this->arResult['cashboxList'] = [];
		if (Driver::getInstance()->isCashboxEnabled())
		{
			$this->arResult['cashboxList'] = $this->getCashboxList();
		}

		$this->arResult['urlProductBuilderContext'] = Crm\Product\Url\ProductBuilder::TYPE_ID;

		if (
			($this->arParams['context'] === SalesCenter\Component\ContextDictionary::TERMINAL_LIST || $this->item)
			&&
			(
				!\CCrmSaleHelper::isWithOrdersMode()
				|| $this->arParams['templateMode'] === self::TEMPLATE_VIEW_MODE
				|| count($this->getOrderIdListByEntityId($ownerId, $ownerTypeId)) === 0
				|| !empty($this->arResult['compilation'])
			)
			&&
			!(
				$this->arParams['context'] === SalesCenter\Component\ContextDictionary::CHAT
				&& CrmManager::getInstance()->isOwnerEntityInFinalStage($ownerId, $ownerTypeId)
			)
		)
		{
			$this->arResult['basket'] = $this->getBasket();
			$this->arResult['totals'] = $this->getTotalSumList(
				array_column($this->arResult['basket'], 'fields'),
				$this->arResult['currencyCode']
			);
		}

		if ($this->arResult['mode'] === self::TERMINAL_PAYMENT_MODE)
		{
			if ($this->arParams['templateMode'] === self::TEMPLATE_VIEW_MODE && $this->payment)
			{
				$this->arResult['paymentResponsible'] = (int)$this->payment->getField('RESPONSIBLE_ID');
				$this->arResult['entityResponsible'] = $this->getManagerInfo($this->arResult['paymentResponsible']);

				$psAction = $this->payment->getPaySystem()?->getField('ACTION_FILE');

				if (
					count($this->arResult['basket']) === 1
					&& is_null($this->arResult['basket'][0]['offerId'])
				)
				{
					$this->arResult['isPaymentByAmount'] = true;
					$this->arResult['currencyCode'] = $this->order->getCurrency();
				}

				if (!$this->item && $this->order)
				{
					/* @var \Bitrix\Crm\Order\Contact $contact */
					$contact = $this->order->getContactCompanyCollection()->getPrimaryContact();
					if ($contact)
					{
						$contactId = (int)$contact->getField('ENTITY_ID');
						$contact = \CCrmContact::GetById($contactId);
						$this->arResult['contactName'] = is_array($contact) ? \CCrmContact::PrepareFormattedName($contact) : '';
						$this->arResult['contactPhone'] = CrmManager::getInstance()->getFormattedContactPhone($contactId);
					}
				}
			}
			else
			{
				$this->arResult['paymentResponsible'] = Main\Engine\CurrentUser::get()->getId();
				if (SalesCenter\Integration\MobileManager::getInstance()->isEnabled())
				{
					$this->arResult['isMobileInstalledForResponsible'] =
						SalesCenter\Integration\MobileManager::getInstance()
							->isMobileInstalledForUser($this->arResult['paymentResponsible'])
					;
				}
				else
				{
					$this->arResult['isMobileInstalledForResponsible'] = false;
				}
			}
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

		$this->arResult['documentSelector'] = $this->getDocumentSelectorParameters();

		$this->arResult['isAllowedFacebookRegion'] = $this->isFacebookExportAvailable();

		$this->arResult['facebookSettingsPath'] = $this->getFacebookSettingsPath();

		$this->arResult['isTerminalAvailable'] = Crm\Terminal\AvailabilityManager::getInstance()->isAvailable();

		$this->arResult['isSalescenterToolEnabled'] = ToolAvailabilityManager::getInstance()->checkSalescenterAvailability();

		$this->arResult['isTerminalToolEnabled'] = Container::getInstance()->getIntranetToolsManager()->checkTerminalAvailability();

		$canShowCrmTerminalTour = Main\Config\Option::get('crm', 'can-show-crm-terminal-tour', 'N') === 'Y';
		if ($canShowCrmTerminalTour && $this->arResult['isTerminalAvailable'])
		{
			$defaultTourSettings = [
				'step-1-shown' => 'N',
				'step-2-shown' => 'N',
			];
		}
		else
		{
			$defaultTourSettings = [
				'step-1-shown' => 'Y',
				'step-2-shown' => 'Y',
			];
		}

		$this->arResult['terminalTour'] = CUserOptions::GetOption('salescenter.tour', 'crm-terminal-tour', $defaultTourSettings);
		$this->arResult['mobileAppLink'] = SalesCenter\Integration\MobileManager::MOBILE_APP_LINK;
		$this->arResult['currentLanguage'] = Loc::getCurrentLang();

		$this->arResult['isPhoneConfirmed'] = LandingManager::getInstance()->isPhoneConfirmed();
	}

	private function getCompilation(): ?array
	{
		if ($this->arParams['compilationId'])
		{
			$compilation = CatalogManager::getInstance()->getCompilationById($this->arParams['compilationId']);
			$exportedProducts = $this->getFacebookExportedProduct($compilation['PRODUCT_IDS']);
			$failProducts = [];
			foreach ($exportedProducts as $exportedProduct)
			{
				if (!empty($exportedProduct['ERROR']))
				{
					$failProducts[$exportedProduct['ID']] = $exportedProduct['ERROR'];
				}
			}
			$compilation['FAIL_PRODUCTS'] = $failProducts;
			$date = $compilation['CREATION_DATE'];
			$culture = \Bitrix\Main\Context::getCurrent()->getCulture();
			$shortDateFormat = $culture->getShortDateFormat();
			$formattedDate = $date->format($shortDateFormat);
			$compilation['TITLE'] = Loc::getMessage(
				'SALESCENTER_APP_FACEBOOK_COMPILATION_TITLE',
				[
					'#COMPILAITON_DATE#' => $formattedDate,
				]
			);
			$compilation['TITLE_TAB'] = Loc::getMessage(
				'SALESCENTER_APP_FACEBOOK_COMPILATION_TITLE_TAB',
				[
					'#COMPILAITON_DATE#' => $formattedDate,
				]
			);
			if (!$compilation['FAIL_PRODUCTS'])
			{
				$this->arResult['templateMode'] = 'view';
			}

			return $compilation;
		}

		return null;
	}

	/**
	 * @return string
	 */
	private function makeTitle(): string
	{
		if (!empty($this->arResult['compilation']))
		{
			return $this->arResult['compilation']['TITLE'];
		}

		if (
			$this->arParams['context'] === SalesCenter\Component\ContextDictionary::DEAL
			|| $this->arParams['context'] === SalesCenter\Component\ContextDictionary::SMART_INVOICE
			|| $this->arParams['context'] === SalesCenter\Component\ContextDictionary::TERMINAL_LIST
		)
		{
			if ($this->arResult['templateMode'] === self::TEMPLATE_VIEW_MODE)
			{
				if (
					$this->payment
					&& (
						$this->arResult['mode'] === self::PAYMENT_DELIVERY_MODE
						|| $this->arResult['mode'] === self::PAYMENT_MODE
						|| $this->arResult['mode'] === self::TERMINAL_PAYMENT_MODE
					)
				)
				{
					/** @var \Bitrix\Main\Type\DateTime $dateBill */
					$dateBill = $this->payment->getField('DATE_BILL');
					$paymentSum = SaleFormatCurrency(
						$this->payment->getField('SUM'),
						$this->payment->getField('CURRENCY')
					);

					$messageCode = $this->arResult['mode'] === self::TERMINAL_PAYMENT_MODE ? 'SALESCENTER_TERMINAL_PAYMENT_DETAILS_TITLE' : 'SALESCENTER_PAYMENT_DETAILS_TITLE' ;

					return Loc::getMessage($messageCode, [
						'#ACCOUNT_NUMBER#' => $this->payment->getField('ACCOUNT_NUMBER'),
						'#DATE#' => ConvertTimeStamp($dateBill->getTimestamp()),
						'#SUM#' => $paymentSum,
					]);
				}
				elseif ($this->arResult['mode'] === self::DELIVERY_MODE && $this->shipment)
				{
					/** @var \Bitrix\Main\Type\DateTime $dateInsert */
					$dateInsert = $this->shipment->getField('DATE_INSERT');
					$deliveryName = $this->shipment->getDelivery() ? $this->shipment->getDelivery()->getNameWithParent() : '';
					$deliverySum = SaleFormatCurrency(
						$this->shipment->getPrice(),
						$this->shipment->getCurrency()
					);

					return Loc::getMessage('SALESCENTER_SHIPMENT_DETAILS_TITLE', [
						'#ACCOUNT_NUMBER#' => $this->shipment->getField('ACCOUNT_NUMBER'),
						'#DATE#' => ConvertTimeStamp($dateInsert->getTimestamp()),
						'#DELIVERY_NAME#' => $deliveryName,
						'#SUM#' => $deliverySum,
					]);
				}
				else
				{
					return '';
				}
			}
			else
			{
				if ($this->arResult['mode'] === static::PAYMENT_MODE)
				{
					$title = Loc::getMessage('SALESCENTER_APP_PAYMENT_TITLE_MSGVER_1');
				}
				elseif ($this->arResult['mode'] === static::DELIVERY_MODE)
				{
					$title = Loc::getMessage('SALESCENTER_APP_DELIVERY_TITLE');
				}
				elseif ($this->arResult['mode'] === static::TERMINAL_PAYMENT_MODE)
				{
					$title = Loc::getMessage('SALESCENTER_APP_TERMINAL_PAYMENT_TITLE');
				}
				else
				{
					$title = Loc::getMessage('SALESCENTER_APP_PAYMENT_AND_DELIVERY_TITLE_MSGVER_1');
				}

				return $title;
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
			$shipmentItemCollection = $this->shipment->getShipmentItemCollection()->getShippableItems();

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

			$this->fillVat($item, $product);

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
		if ($this->arParams['compilationId'])
		{
			return $this->getCompilationProducts();
		}

		if ($this->arParams['templateMode'] === self::TEMPLATE_VIEW_MODE)
		{
			return $this->getOrderProducts();
		}

		if ($this->arParams['templateMode'] === self::TEMPLATE_CREATE_MODE)
		{
			return $this->getProducts();
		}

		return [];
	}

	private function getCompilationProducts(): array
	{
		$productIds = $this->arResult['compilation']['PRODUCT_IDS'];
		$formBuilder = new ProductForm\BasketBuilder();

		foreach ($productIds as $productId)
		{
			$item = $formBuilder->loadItemBySkuId($productId);

			if ($item === null)
			{
				$item = $formBuilder->createItem();
			}

			$formBuilder->setItem($item);
			$item->setSort($formBuilder->count() * 100);
		}

		return $formBuilder->getFormattedItems();
	}

	private function getShipmentData(int $contactId, int $personTypeId)
	{
		if ($this->arParams['templateMode'] === self::TEMPLATE_CREATE_MODE)
		{
			$propValues = [];

			$toPropIds = $this->getDeliveryToPropIds($personTypeId);
			$toList = $this->getDeliveryToList($contactId);
			if ($toList)
			{
				foreach ($toPropIds as $toPropId)
				{
					$propValues[$toPropId] = $toList[0]['address'];
				}
			}

			$fromPropIds = $this->getDeliveryFromPropIds($personTypeId);
			$fromList = $this->getDeliveryFromList();
			if ($fromList)
			{
				foreach ($fromPropIds as $fromPropId)
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
	 * @return int[]
	 */
	private function getDeliveryFromPropIds(int $personTypeId): array
	{
		return $this->getDeliveryAddressPropIdsByCode($personTypeId, 'IS_ADDRESS_FROM');
	}

	/**
	 * @return int[]
	 */
	private function getDeliveryToPropIds(int $personTypeId): array
	{
		return $this->getDeliveryAddressPropIdsByCode($personTypeId, 'IS_ADDRESS_TO');
	}

	/**
	 * @param int $personTypeId
	 * @param string $attribute
	 * @return int[]
	 */
	private function getDeliveryAddressPropIdsByCode(int $personTypeId, string $attribute): array
	{
		$result = [];

		$propsList = Sale\ShipmentProperty::getList(
			[
				'filter' => [
					'=PERSON_TYPE_ID' => $personTypeId,
					'=ACTIVE' => 'Y',
					'=TYPE' => 'ADDRESS',
					'=' . $attribute => 'Y',
				],
			]
		);

		while ($prop = $propsList->fetch())
		{
			$result[] = $prop['ID'];
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getDeliveryFromList(): array
	{
		$result = LocationManager::getInstance()->getFormattedLocations(
			CrmManager::getInstance()->getMyCompanyAddressList()
		);

		$defaultLocationFrom = LocationManager::getInstance()->getLocationsFromList();
		if ($defaultLocationFrom)
		{
			$result = array_merge($defaultLocationFrom, $result);
		}

		return array_values($result);
	}

	/**
	 * @param int $contactId
	 * @return array
	 */
	private function getDeliveryToList(int $contactId): array
	{
		return array_values(
			LocationManager::getInstance()->getFormattedLocations(
				CrmManager::getInstance()->getClientAddressList($contactId)
			)
		);
	}

	/**
	 * @return array
	 */
	private function getTimeline(): array
	{
		if (
			$this->arResult['mode'] !== self::PAYMENT_DELIVERY_MODE
			&& $this->arResult['mode'] !== self::PAYMENT_MODE
			&& $this->arResult['mode'] !== self::TERMINAL_PAYMENT_MODE
		)
		{
			return [];
		}

		if (!$this->payment)
		{
			return [];
		}

		$result = [];

		if ($this->payment->isPaid())
		{
			$result[] = [
				'type' => 'payment',
				'sum' => \CCrmCurrency::MoneyToString(
					$this->payment->getSum(),
					$this->payment->getField('CURRENCY'),
					'#'
				),
				'currency' => \CCrmCurrency::GetCurrencyText($this->payment->getField('CURRENCY')),
				'currencyCode' => $this->payment->getField('CURRENCY'),
				'content' => Loc::getMessage('SALESCENTER_TIMELINE_PAYMENT_SUBTITLE', [
					'#PAY_SYSTEM_NAME#' => $this->payment->getField('PAY_SYSTEM_NAME'),
				]),
				'title' => Loc::getMessage('SALESCENTER_TIMELINE_PAYMENT_TITLE', [
					'#DATE_CREATED#' => $this->prepareTimeLineItemsDateTime($this->payment->getField('DATE_PAID')),
				]),
			];

			$culture = Application::getInstance()->getContext()->getCulture();

			if ($this->arResult['mode'] === self::TERMINAL_PAYMENT_MODE)
			{
				$result[] = [
					'type' => 'custom',
					'icon' => 'check',
					'content' => Loc::getMessage('SALESCENTER_TIMELINE_TERMINAL_CHECK_CONTENT', [
						'[link]' => '<a href="' . PaymentSlip::getFullPathToSlip($this->payment->getId()) . '" target="_blank"">',
						'[/link]' => '</a>',
						'#DATE_CREATED#' => $this->prepareTimeLineItemsDateTime($this->payment->getField('DATE_PAID'), $culture->getLongDateFormat()),
					]),
				];
			}

			$checks = Cashbox\CheckManager::getList([
				'select' => ['ID'],
				'filter' => [
					'=PAYMENT_ID' => $this->payment->getId(),
					'=STATUS' => 'Y',
				]
			])->fetchAll();
			if (!empty($checks))
			{
				foreach ($checks as $check)
				{
					$checkObject = Cashbox\CheckManager::getObjectById($check['ID']);
					if (!$checkObject)
					{
						continue;
					}

					$result[] = [
						'type' => 'check',
						'url' => $checkObject->getUrl(),
						'content' => Loc::getMessage('SALESCENTER_TIMELINE_CHECK_CONTENT', [
							'#ID#' => $checkObject->getField('ID'),
							'#DATE_CREATED#' => $this->prepareTimeLineItemsDateTime($checkObject->getField('DATE_CREATE'), $culture->getLongDateFormat()),
						]),
					];
				}
			}
		}

		return $result;
	}

	/**
	 * @param int $entityId
	 * @param int $entityTypeId
	 * @return array
	 */
	private function getEntityStageList(int $entityId, int $entityTypeId) : array
	{
		$result = [
			[
				'type' => 'invariable',
				'name' => Loc::getMessage('SALESCENTER_AUTOMATION_STEPS_STAY'),
			],
		];

		$stageList = CrmManager::getInstance()->getStageList($entityId, $entityTypeId);

		if ($stageList)
		{
			foreach ($stageList as $stage)
			{
				$result[] = [
					'id' => $stage['STATUS_ID'],
					'type' => 'stage',
					'name' => $stage['NAME'],
					'color' => $stage['COLOR'],
					'colorText' => $this->getStageColorText($stage['COLOR']),
				];
			}
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
			'fullName' => '',
			'position' => '',
		];

		$by = 'id';
		$order = 'asc';

		$dbRes = \CUser::GetList(
			$by,
			$order,
			['ID' => $userId],
			['FIELDS' => ['PERSONAL_PHOTO', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'TITLE', 'WORK_POSITION']]
		);

		if ($user = $dbRes->Fetch())
		{
			$result['name'] = $user['NAME'];
			$result['fullName'] = CUser::FormatName(CSite::GetNameFormat(false), $user, true);
			$result['position'] = $user['WORK_POSITION'];

			$fileInfo = \CFile::ResizeImageGet(
				$user['PERSONAL_PHOTO'] ?? '',
				['width' => 63, 'height' => 63],
				BX_RESIZE_IMAGE_EXACT
			);

			if (is_array($fileInfo) && isset($fileInfo['src']))
			{
				$result['photo'] = Uri::urnEncode(htmlspecialcharsbx($fileInfo['src']));
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
		$productManager = new Crm\Order\ProductManager($this->arResult['ownerTypeId'], $this->arResult['ownerId']);

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

			if (Main\Loader::includeModule('sale'))
			{
				$type = Sale\Internals\Catalog\ProductTypeMapper::getCatalogType($product['TYPE']);
				$item->setType($type);
			}

			$this->fillVat($item, $product);

			if (isset($product['XML_ID']))
			{
				$item->setId($product['XML_ID']);
			}

			$formBuilder->setItem($item);
			$item->setSort($formBuilder->count() * 100);
		}

		return $formBuilder->getFormattedItems();
	}

	/**
	 * Fill basket item vat's info by input fields.
	 *
	 * @param BasketItem $basketItem
	 * @param array $basketItemFields
	 *
	 * @return void
	 */
	private function fillVat(BasketItem $basketItem, array $basketItemFields): void
	{
		$vatRate = null;
		if (array_key_exists('VAT_RATE', $basketItemFields))
		{
			$vatRate =
				(string)$basketItemFields['VAT_RATE'] !== ''
					? (float)$basketItemFields['VAT_RATE']
					: null
			;

			if (Main\Loader::includeModule('catalog'))
			{
				$vatId =
					isset($vatRate)
						? VatTable::getActiveVatIdByRate($vatRate * 100)
						: VatTable::getExcludeVatId()
				;
				if (isset($vatId))
				{
					$basketItem->setTaxId($vatId);
				}
			}
		}

		$vatIncluded = $basketItemFields['VAT_INCLUDED'] ?? 'Y';
		$basketItem->setTaxIncluded($vatIncluded);

		if ($vatIncluded === 'N' && $vatRate > 0)
		{
			$price = (float)$basketItemFields['PRICE'];

			$vatCalculator = new VatCalculator($vatRate);
			$priceWithVat = $vatCalculator->accrue($price);

			$basketItem
				->setPrice($priceWithVat)
				->setPriceExclusive($priceWithVat)
			;
		}
	}

	/**
	 * @return array
	 */
	private function getPaySystemList(): array
	{
		$result = [];

		$result['groups'] = [
			[
				'id' => ClientType::B2C,
				'name' => Loc::getMessage('SALESCENTER_APP_CLIENT_TYPE_B2C'),
			],
			[
				'id' => ClientType::B2B,
				'name' => Loc::getMessage('SALESCENTER_APP_CLIENT_TYPE_B2B'),
			],
			[
				'id' => 'other',
			],
		];

		$paySystemPath = $this->getComponentSliderPath('bitrix:salescenter.paysystem');
		$queryParams = [
			'lang' => LANGUAGE_ID,
			'publicSidePanel' => 'Y',
		];

		$paySystemList = SaleManager::getInstance()->getPaySystemList([
			'!=ACTION_FILE' => [
				'inner',
				'cash',
				'orderdocument',
			],
		]);
		if ($paySystemList)
		{
			$result['isSet'] = true;

			foreach ($paySystemList as $paySystem)
			{
				$queryParams['ID'] = $paySystem['ID'];
				$paySystemPath->addParams($queryParams);

				$result['items'][] = [
					'id' => $paySystem['ID'],
					'name' => $paySystem['NAME'],
					'link' => $paySystemPath->getLocator(),
					'type' => 'paysystem',
					'sort' => $paySystem['SORT'],
					'group' => $paySystem['PS_CLIENT_TYPE'],
				];
			}

			Main\Type\Collection::sortByColumn($result['items'], ['sort' => SORT_ASC]);

			$result['items'][] = [
				'name' => Loc::getMessage('SALESCENTER_APP_ADD_TITLE'),
				'link' => $this->getComponentSliderPath('bitrix:salescenter.paysystem.panel')->getLocator(),
				'type' => 'more',
				'group' => 'other',
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
					'group' => 'other',
				];
			}
		}
		else
		{
			$paySystemHandlerList = $this->getSliderPaySystemHandlers();
			$handlerList = PaySystem\Manager::getHandlerList();
			$systemHandlers = array_keys($handlerList['SYSTEM']);
			foreach ($systemHandlers as $systemHandler)
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

				$img = '/bitrix/components/bitrix/salescenter.paysystem.panel/templates/.default/images/' . $systemHandler;
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

						$psModeImage = $img . '_' . $psMode . '.svg';
						if (!Main\IO\File::isFileExists(Application::getDocumentRoot().$psModeImage))
						{
							$psModeImage = $img . '.svg';
						}

						$result['items'][] = [
							'name' => $handlerDescription['NAME'] ?? $handlerList['SYSTEM'][$systemHandler],
							'psModeName' => $psModeList[$psMode],
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
							'showTitle' => true,
							'sort' => $this->getPaySystemSort($systemHandler, $psMode),
						];
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
						'showTitle' => true,
						'sort' => $this->getPaySystemSort($systemHandler),
					];
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

				$result['items'] = array_slice($result['items'], 0, self::LIMIT_COUNT_PAY_SYSTEM);
			}

			$result['isSet'] = false;
			$result['items'][] = [
				'name' => Loc::getMessage('SALESCENTER_APP_PAYSYSTEM_ITEM_EXTRA'),
				'link' => $this->getComponentSliderPath('bitrix:salescenter.paysystem.panel')->getLocator(),
				'type' => 'more',
				'group' => 'other',
			];
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
						?: '/bitrix/components/bitrix/salescenter.paysystem.panel/templates/.default/images/marketplace_default.svg'
					;
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

		if (!empty($result['items']))
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
			'items' => [],
		];

		$handlers = $handlersCollection->getInstallableItems();

		/* load internal deliveries */
		foreach ($handlers as $handler)
		{
			if ($handler->isInstalled())
			{
				$result['isInstalled'] = true;
			}

			$result['items'][] = [
				'code' => $handler->getCode(),
				'name' => $handler->getName(),
				'link' => $handler->getInstallationLink(),
				'img' => $handler->getImagePath(),
				'info' => $handler->getShortDescription(),
				'type' => 'delivery',
				'showTitle' => !$handler->doesImageContainName(),
				'width' => 835,
			];
		}

		/** load marketplace deliveries */
		if (RestManager::getInstance()->isEnabled())
		{
			$marketplaceItems = $this->getDeliveryMarketplaceItems();
			if (!empty($marketplaceItems))
			{
				$result['items'] = array_merge($result['items'], $marketplaceItems);
			}
			unset($marketplaceItems);
		}

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
						?: '/bitrix/components/bitrix/salescenter.delivery.panel/templates/.default/images/marketplace_default.svg'
					;
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
			$businessRuCashboxHandlers = [
				'\\' . Cashbox\CashboxBusinessRu::class,
				'\\' . Cashbox\CashboxBusinessRuV5::class
			];
			/** @var Cashbox\Cashbox $handler */
			foreach ($this->getCashboxHandlerList() as $handler)
			{
				$queryParams['handler'] = $handler;
				$cashboxPath->addParams($queryParams);

				if (in_array($handler, $businessRuCashboxHandlers, true))
				{
					foreach ([Cashbox\KkmRepository::ATOL, Cashbox\KkmRepository::EVOTOR] as $kkm)
					{
						$queryParams['kkm-id'] = $kkm;
						$cashboxPath->addParams($queryParams);

						$info = $handler::getSupportedKkmModels()[$kkm];

						$result['items'][] = [
							'name' => $info['NAME']
								. (
									$handler::getFfdVersion() === 1.2
										? ' (' . Loc::getMessage('SALESCENTER_APP_CASHBOX_FFD_12_SUPPORT') . ')'
										: ''
								),
							'img' => '/bitrix/components/bitrix/salescenter.cashbox.panel/templates/.default/images/businessru_'.$kkm.'.svg',
							'link' => $cashboxPath->getLocator(),
							'info' => Loc::getMessage(
								'SALESCENTER_APP_CASHBOX_INFO',
								['#CASHBOX_NAME#' => $info['NAME']]
							)
								. (
									$handler::getFfdVersion() === 1.2
										? ' (' . Loc::getMessage('SALESCENTER_APP_CASHBOX_FFD_12_SUPPORT') . ')'
										: ''
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
				elseif (is_a($queryParams['handler'], Cashbox\CashboxOrangeData::class, true))
				{
					$result['items'][] = [
						'name' => $handler::getName(),
						'img' => '/bitrix/components/bitrix/salescenter.cashbox.panel/templates/.default/images/orangedata.svg',
						'link' => $cashboxPath->getLocator(),
						'info' => Loc::getMessage(
							'SALESCENTER_APP_CASHBOX_INFO',
							[
								'#CASHBOX_NAME#' => $handler::getName(),
							]
						),
						'type' => 'cashbox',
						'showTitle' => true,
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

		$atolCashboxHandlers = [
			'\\' . Cashbox\CashboxAtolFarmV4::class,
			'\\' . Cashbox\CashboxAtolFarmV5::class,
		];

		/** @var Cashbox\Cashbox $handler */
		foreach (SaleManager::getCashboxHandlers() as $handler)
		{
			if (in_array($handler, $atolCashboxHandlers, true))
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
		$iterator = PaySystem\Manager::getList([
			'select' => ['ID', 'NAME', 'ACTION_FILE'],
			'filter' => [
				'!ID' => PaySystem\Manager::getInnerPaySystemId(),
				'!=ACTION_FILE' => 'cash',
				'=ACTIVE' => 'Y',
			],
			'limit' => 1,
		]);

		$row = $iterator->fetch();

		return empty($row);
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

		return new Main\Web\Uri($path);
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
		$actionCrmWriteFilterClass = new class extends Main\Engine\ActionFilter\Base
		{
			public function onBeforeAction(Main\Event $event)
			{
				Main\Loader::includeModule('salescenter');
				Main\Loader::includeModule('crm');

				global $USER;
				$crmPerms = new \CCrmPerms($USER->GetID());
				if (!$crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
				{
					$this->addError(new Main\Error(
						Loc::getMessage('SALESCENTER_CRM_PERMISSION_DENIED'),
					));

					return new Main\EventResult(Main\EventResult::ERROR, null, null, $this);
				}

				return null;
			}
		};

		return [
			'saveSmsTemplate' => [
				'+prefilters' => [
					new $actionCrmWriteFilterClass,
				]
			],
		];
	}

	/**
	 * @param $date
	 * @param string|null $format
	 * @return string
	 */
	private function prepareTimeLineItemsDateTime($date, $format = null): string
	{
		if (!$format)
		{
			$culture = Application::getInstance()->getContext()->getCulture();
			$format = $culture->getLongDateFormat() . ' ' . $culture->getShortTimeFormat();
		}
		$result = '';
		if ($date instanceof \Bitrix\Main\Type\DateTime)
		{
			$result = FormatDate($format, $date->getTimestamp() + CTimeZone::GetOffset());
		}

		return $result;
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

	private function getOrderIdListByEntityId(int $ownerId, int $ownerTypeId): array
	{
		static $result = [];

		if (!empty($result[$ownerTypeId][$ownerId]))
		{
			return $result[$ownerTypeId][$ownerId];
		}

		$result[$ownerTypeId][$ownerId] = [];

		$relationManager = Crm\Service\Container::getInstance()->getRelationManager();
		$relation = $relationManager->getRelation(
			new Crm\RelationIdentifier(
				$ownerTypeId,
				\CCrmOwnerType::Order
			)
		);
		if ($relation)
		{
			$orderIdentifiers = $relation->getChildElements(new Crm\ItemIdentifier(
				$ownerTypeId,
				$ownerId
			));
			foreach ($orderIdentifiers as $identifier)
			{
				$result[$ownerTypeId][$ownerId][] = $identifier->getEntityId();
			}
		}

		return $result[$ownerTypeId][$ownerId] ?? [];
	}

	/**
	 * @param array $result
	 */
	private function fillSendersData(array &$result): void
	{
		$result['senders'] = \Bitrix\SalesCenter\Component\ReceivePaymentHelper::getSendersData();

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

	private function getFacebookSettingsPath(): ?string
	{
		if (!$this->arResult['isAllowedFacebookRegion'])
		{
			return null;
		}

		if ($this->arParams['dialogId'] && Main\Loader::includeModule('im'))
		{
			$chatId = \Bitrix\Im\Dialog::getChatId($this->arParams['dialogId']);
			$chat = \Bitrix\Im\Chat::getById($chatId);
			[$connector, $line] = explode('|', $chat['ENTITY_ID']);

			if ($connector !== 'facebook' || $this->hasCatalogExportAuth())
			{
				return null;
			}

			return
				Main\Loader::includeModule('bitrix24')
					? '/contact_center/connector?ID=facebook&LINE=' . $line . '&action-line=create&MENU_TAB=catalog'
					: '/services/contact_center/connector?ID=facebook&LINE=' . $line . '&action-line=create&MENU_TAB=catalog'
			;
		}

		return null;
	}

	private function getFacebookFacade(): ?FacebookFacade
	{
		static $facade = null;

		if ($facade === null)
		{
			if (Main\Loader::includeModule('catalog') && Main\Loader::includeModule('crm'))
			{
				try
				{
					$iblockId = CCrmCatalog::EnsureDefaultExists();
					$facade = ServiceContainer::get('integration.seo.facebook.facade', compact('iblockId'));
				}
				catch (ObjectNotFoundException)
				{
				}
			}
		}

		return $facade;
	}

	private function hasCatalogExportAuth(): bool
	{
		if ($facebookFacade = $this->getFacebookFacade())
		{
			return $facebookFacade->hasAuth();
		}

		return false;
	}

	private function isFacebookExportAvailable(): bool
	{
		$facebookFacade = $this->getFacebookFacade();

		return $facebookFacade && $facebookFacade->isExportAvailable();
	}

	private function getFacebookExportedProduct(array $productIds): array
	{
		$facebookFacade = $this->getFacebookFacade();

		return $facebookFacade ? $facebookFacade->getExportedProducts($productIds) : [];
	}

	// region Actions

	/**
	 * @param array $arParams
	 * @return array
	 */
	public function getComponentResultAction(array $arParams): array
	{
		$this->arParams = $this->onPrepareComponentParams($arParams);

		$this->fillComponentResult();

		return $this->arResult;
	}

	/**
	 * @param string $smsTemplate
	 * @param string $mode
	 */
	public function saveSmsTemplateAction(string $smsTemplate, string $mode = CrmManager::SMS_MODE_PAYMENT): void
	{
		if (Main\Loader::includeModule('salescenter'))
		{
			$currentSmsTemplate = CrmManager::getInstance()->getSmsTemplate($mode);
			if ($smsTemplate !== $currentSmsTemplate)
			{
				CrmManager::getInstance()->saveSmsTemplate($smsTemplate, $mode);
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
	 * @param $fields
	 * @return array
	 */
	public function refreshContactPhoneAction($fields): array
	{
		$result = [];

		$ownerId = (int)($fields['ownerId'] ?? 0);
		$ownerTypeId = (int)($fields['ownerTypeId'] ?? 0);

		$factory = Crm\Service\Container::getInstance()->getFactory($ownerTypeId);
		if ($factory && $factory->isPaymentsEnabled())
		{
			$this->item = $factory->getItem($ownerId);
		}
		else
		{
			$this->item = null;
		}

		if ($this->item)
		{
			$result['contactPhone'] = CrmManager::getInstance()->getItemContactPhoneFormatted($this->item);

			$contact = CrmManager::getInstance()->getItemContactFields($this->item);
			$result['title'] = is_array($contact) ? htmlspecialcharsbx($contact['FULL_NAME']) : '';
		}

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

	protected function getDocumentSelectorParameters(): ?array
	{
		if (!$this->item)
		{
			return null;
		}
		$documentGeneratorManager = Crm\Integration\DocumentGeneratorManager::getInstance();
		$isEnabled = $documentGeneratorManager->isEnabled();
		if (!$isEnabled)
		{
			return null;
		}

		$documentsUserPermissions = \Bitrix\DocumentGenerator\Driver::getInstance()->getUserPermissions();
		if (!$documentsUserPermissions->canViewDocuments())
		{
			return null;
		}

		$entityTypeId = $this->item->getEntityTypeId();
		if (!$documentGeneratorManager->isEntitySupportsPaymentDocumentBinding($entityTypeId))
		{
			return null;
		}
		$parameters = [];
		$itemIdentifier = Crm\ItemIdentifier::createByItem($this->item);
		if ($this->payment)
		{
			$paymentId = $this->payment->getId();
			$parameters['boundDocumentId'] = $documentGeneratorManager->getPaymentBoundDocumentId($paymentId);
			$documents = $documentGeneratorManager->getDocumentsByIdentifier($itemIdentifier, $paymentId);
			foreach ($documents as $document)
			{
				$parameters['documents'][] = $document->jsonSerialize();
			}
		}
		if ($documentsUserPermissions->canModifyTemplates())
		{
			$parameters['templateAddUrl'] = $documentGeneratorManager->getAddTemplateUrl();
			$parameters['entityTypeId'] = $entityTypeId;
			$parameters['entityId'] = $this->item->getId();
		}

		$selectedTemplateId = $documentGeneratorManager->getLastBoundPaymentDocumentTemplateId();
		$templates = $documentGeneratorManager->getTemplatesByIdentifier($itemIdentifier);
		foreach ($templates as $template)
		{
			if ($documentsUserPermissions->canCreateDocumentOnTemplate($template->getId()))
			{
				$parameters['templates'][] = $template->jsonSerialize();
				if ($selectedTemplateId > 0 && $template->getId() === $selectedTemplateId)
				{
					$parameters['selectedTemplateId'] = $selectedTemplateId;
				}
			}
		}

		return $parameters;
	}

	private function getCollapseOptions(): array
	{
		$optionsName = 'add_shipment_collapse_options';
		if ($this->arResult['mode'] === self::PAYMENT_DELIVERY_MODE)
		{
			$optionsName = 'add_payment_collapse_options';
		}
		elseif ($this->arResult['mode'] === self::PAYMENT_MODE || $this->arResult['mode'] === self::TERMINAL_PAYMENT_MODE)
		{
			$optionsName = 'add_payment_collapse_options';
		}

		return (array)CUserOptions::GetOption(
			'salescenter',
			$optionsName,
			[]
		);
	}

	public function getFacebookSettingsPathAction($dialogId): ?string
	{
		$this->arParams['dialogId'] = $dialogId;
		$this->arResult['isAllowedFacebookRegion'] = $this->isFacebookExportAvailable();

		return $this->getFacebookSettingsPath();
	}
}
