<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Crm\Order;
use Bitrix\Sale;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Component\ComponentError;
use Bitrix\Crm\Component\EntityDetails\ComponentMode;
use Bitrix\Catalog;
use Bitrix\UI;

if (!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!Main\Loader::includeModule('salescenter'))
{
	ShowError(GetMessage('SALESCENTER_MODULE_NOT_INSTALLED'));
	return;
}

if (!Main\Loader::includeModule('catalog'))
{
	ShowError(GetMessage('CATALOG_MODULE_NOT_INSTALLED'));
	return;
}

Loc::loadMessages(__FILE__);

class CrmStoreDocumentDetailComponent extends Crm\Component\EntityDetails\BaseComponent implements Main\Engine\Contract\Controllerable
{
	use Crm\Component\EntityDetails\SaleProps\ComponentTrait;

	private const PATH_TO_USER_PROFILE = '/company/personal/user/#user_id#/';
	private const PATH_TO_SHIPMENT_DETAIL = '/shop/documents/details/sales_order/#DOCUMENT_ID#/';

	private const COMPONENT_ERROR_EMPTY_ORDER_ID = -0x3;

	/** @var Order\Shipment */
	private $shipment;

	/** @var Order\Order */
	private $order;

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::OrderShipment;
	}

	protected function getUserFieldEntityID()
	{
		return Order\Shipment::getUfId();
	}

	protected function checkIfEntityExists()
	{
		if ($this->entityID > 0)
		{
			$res = Crm\Order\Shipment::getList([
				'filter' => ['=ID' => $this->entityID],
			]);

			return (bool)$res->fetch();
		}

		return false;
	}

	protected function getErrorMessage($error)
	{
		if ($error === ComponentError::ENTITY_NOT_FOUND)
		{
			return Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_SHIPMENT_NOT_FOUND');
		}
		return ComponentError::getMessage($error);
	}

	public function initializeParams(array $params)
	{
		foreach ($params as $k => $v)
		{
			if (!is_string($v))
			{
				continue;
			}

			if ($k === 'NAME_TEMPLATE')
			{
				$this->arResult['NAME_TEMPLATE'] = $this->arParams['NAME_TEMPLATE'] = $v;
			}
			elseif ($k === 'ORDER_SHIPMENT_ID')
			{
				$this->arResult[$k] = $this->arParams[$k] = (int)$v;
			}
		}
	}

	public function setShipment(Order\Shipment $shipment): void
	{
		$this->shipment = $shipment;
		$this->order = $shipment->getOrder();
		$this->arResult['SITE_ID'] = $this->shipment->getOrder()->getSiteId();
	}

	public function executeComponent()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();

		//region Params
		$this->arResult['DOCUMENT_ID'] = isset($this->arParams['~DOCUMENT_ID']) ? (int)$this->arParams['~DOCUMENT_ID'] : 0;

		$this->arResult['PATH_TO_SHIPMENT_DETAIL'] = $this->arParams['PATH_TO']['SHIPMENT'] ?? self::PATH_TO_SHIPMENT_DETAIL;

		$this->arParams['PATH_TO_USER_PROFILE'] = self::PATH_TO_USER_PROFILE;
		$this->arResult['PATH_TO_USER_PROFILE'] = CrmCheckPath(
			'PATH_TO_USER_PROFILE',
			$this->arParams['PATH_TO_USER_PROFILE'],
			'/company/personal/user/#user_id#/'
		);

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(['#NOBR#','#/NOBR#'], ['',''], $this->arParams['NAME_TEMPLATE'])
		;

		$this->arResult['OWNER_TYPE_ID'] = (int)($this->arParams['CONTEXT']['OWNER_TYPE_ID'] ?? 0);
		$this->arResult['OWNER_ID'] = (int)($this->arParams['CONTEXT']['OWNER_ID'] ?? 0);

		$this->arResult['ORDER_ID'] = 0;
		if (!\CCrmSaleHelper::isWithOrdersMode())
		{
			$this->arResult['ORDER_ID'] = (int)($this->arParams['CONTEXT']['ORDER_ID'] ?? 0);
		}

		$this->arResult['ACTION_URI'] = $this->arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;
		$this->arResult['PRODUCT_DATA_FIELD_NAME'] = 'ORDER_SHIPMENT_PRODUCT_DATA';
		$this->arResult['PRODUCT_EDITOR_ID'] = 'order_shipment_product_editor';

		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::OrderShipmentName.'_'.$this->arResult['DOCUMENT_ID'];

		$this->setEntityID($this->arResult['DOCUMENT_ID']);

		if (!$this->tryToDetectMode())
		{
			$this->showErrors();
			return;
		}

		if ($this->entityID > 0)
		{
			$this->shipment = Crm\Order\Manager::getShipmentObject($this->entityID);
			if (!$this->shipment)
			{
				$this->addError(new Main\Error(Loc::getMessage('CRM_STORE_DOCUMENT_SD_SHIPMENT_NOT_FOUND')));
				$this->showErrors();
				return;
			}

			$this->order = $this->shipment->getOrder();
		}
		elseif ($this->arResult['ORDER_ID'])
		{
			$this->order = Crm\Order\Order::load($this->arResult['ORDER_ID']);
		}
		else
		{
			$this->order = Crm\Order\Manager::createEmptyOrder($this->getSiteId());
		}

		if ($this->order)
		{
			$this->arResult['ORDER_ID'] = $this->order->getId();
		}

		$shipments = $this->order->getShipmentCollection();
		if ($this->mode === ComponentMode::CREATION)
		{
			$this->shipment = $shipments->createItem();
		}
		elseif (!$this->shipment)
		{
			$this->shipment = $shipments->getItemById($this->entityID);
			if (!$this->shipment)
			{
				$this->addError(new Main\Error(Loc::getMessage('CRM_STORE_DOCUMENT_SD_SHIPMENT_NOT_FOUND')));
				$this->showErrors();
				return;
			}
		}

		$this->arResult['CONTEXT_PARAMS'] = [
			'PATH_TO_USER_PROFILE' => $this->arResult['PATH_TO_USER_PROFILE'],
			'PATH_TO_SHIPMENT_DETAIL' => $this->arResult['PATH_TO_SHIPMENT_DETAIL'],
			'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
			'OWNER_TYPE_ID' => $this->arResult['OWNER_TYPE_ID'],
			'OWNER_ID' => $this->arResult['OWNER_ID'],
			'ORDER_ID' => $this->arResult['ORDER_ID'],
		];

		$this->arResult['SITE_ID'] = $this->order->getSiteId();
		$this->prepareEntityData();

		//region GUID
		$this->guid = $this->arResult['GUID'] = "realization_document_{$this->entityID}_details";

		if ($this->needDeliveryBlock())
		{
			$this->arResult['EDITOR_CONFIG_ID'] = 'realization_document_delivery_details';
		}
		else
		{
			$this->arResult['EDITOR_CONFIG_ID'] = 'realization_document_shipment_details';
		}
		//endregion

		//region Entity Info
		$this->arResult['ENTITY_INFO'] = [
			'DOCUMENT_ID' => $this->entityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::OrderShipment,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::OrderShipmentName,
			'TITLE' => $this->entityData['TITLE'],
			'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::OrderShipment, $this->entityID, false),
		];
		//endregion

		//region Page title
		if ($this->mode === ComponentMode::CREATION)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_CREATION_PAGE_TITLE'));
		}
		elseif (!empty($this->entityData['TITLE']))
		{
			$APPLICATION->SetTitle($this->entityData['TITLE']);
		}
		//endregion

		//region Fields
		$this->prepareFieldInfos();
		//endregion

		//region Config
		$this->prepareEntityConfig();
		//endregion

		//region Controllers
		$this->prepareEntityControllers();
		//endregion

		//region WAIT TARGET DATES
		$this->arResult['WAIT_TARGET_DATES'] = [];

		if ($this->userType)
		{
			$userFields = $this->userType->GetFields();
			foreach ($userFields as $userField)
			{
				if ($userField['USER_TYPE_ID'] === 'date')
				{
					$this->arResult['WAIT_TARGET_DATES'][] = [
						'name' => $userField['FIELD_NAME'],
						'caption' => $userField['EDIT_FORM_LABEL'] ?? $userField['FIELD_NAME'],
					];
				}
			}
		}
		//endregion

		//region VIEW EVENT
		if ($this->entityID > 0 && Crm\Settings\HistorySettings::getCurrent()->isViewEventEnabled())
		{
			CCrmEvent::RegisterViewEvent(CCrmOwnerType::OrderShipment, $this->entityID, $this->userID);
		}
		//endregion

		if (Main\Loader::includeModule('pull'))
		{
			\CPullWatch::Add($this->userID, 'CRM_ENTITY_ORDER_SHIPMENT');
			\CPullWatch::Add($this->userID, 'SALE_DELIVERY_SERVICE');
			\CPullWatch::Add($this->userID, 'SALE_DELIVERY_REQUEST');
		}

		$this->checkIfInventoryManagementIsUsed();

		$this->arResult['COMPONENT_PRODUCTS'] = $this->getDocumentProducts();

		$this->includeComponentTemplate();
	}

	private function needDeliveryBlock(): bool
	{
		$isOwnerContext = $this->arResult['OWNER_TYPE_ID'] && $this->arResult['OWNER_ID'];
		$hasBinding = $this->order->getEntityBinding() && !empty($this->order->getEntityBinding()->toArray());

		return $isOwnerContext || $hasBinding;
	}

	protected function prepareFieldInfos()
	{
		if (isset($this->arResult['ENTITY_FIELDS']))
		{
			return $this->arResult['ENTITY_FIELDS'];
		}

		$this->arResult['SHIPMENT_PROPERTIES'] = $this->prepareProperties(
			$this->shipment->getPropertyCollection(),
			Order\ShipmentProperty::class,
			$this->shipment->getPersonTypeId(),
			($this->shipment->getId() === 0)
		);

		$this->arResult['ENTITY_FIELDS'] = [
			[
				'name' => 'CLIENT',
				'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_FIELD_CLIENT'),
				'type' => 'order_client',
				'editable' => true,
				'requiredConditionally' => true,
				'required' => true,
				'data' => [
					'map' => [
						'data' => 'CLIENT',
						'companyId' => 'COMPANY_ID',
						'contactIds' => 'CONTACT_IDS',
					],
					'info' => 'CLIENT_INFO',
					'lastCompanyInfos' => 'LAST_COMPANY_INFOS',
					'lastContactInfos' => 'LAST_CONTACT_INFOS',
					'loaders' => [
						'primary' => [
							CCrmOwnerType::CompanyName => [
								'action' => 'GET_CLIENT_INFO',
								'url' => '/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get(),
							],
							CCrmOwnerType::ContactName => [
								'action' => 'GET_CLIENT_INFO',
								'url' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get(),
							],
						],
						'secondary' => [
							CCrmOwnerType::CompanyName => [
								'action' => 'GET_SECONDARY_ENTITY_INFOS',
								'url' => '/bitrix/components/bitrix/crm.order.details/ajax.php?'.bitrix_sessid_get(),
							],
						],
					],
					'clientEditorFieldsParams' => $this->prepareClientEditorFieldsParams(),
				],
			],
			[
				'name' => 'ID',
				'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_FIELD_ID'),
				'type' => 'hidden',
				'editable' => false,
			],
			[
				'name' => 'ORDER_ID',
				'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_ORDER_ID'),
				'type' => 'hidden',
				'editable' => false,
			],
			[
				'name' => 'XML_ID',
				'title' => 'XML_ID',
				'type' => 'hidden',
				'editable' => false,
			],
			[
				'name' => 'RESPONSIBLE_ID',
				'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_FIELD_RESPONSIBLE_ID'),
				'type' => 'user',
				'editable' => true,
				'data' => [
					'enableEditInView' => true,
					'formated' => 'RESPONSIBLE_FORMATTED_NAME',
					'position' => 'RESPONSIBLE_WORK_POSITION',
					'photoUrl' => 'RESPONSIBLE_PHOTO_URL',
					'showUrl' => 'PATH_TO_RESPONSIBLE_USER',
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE'],
				],
			],
			[
				'name' => 'DOCUMENT_PRODUCTS',
				'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_FIELD_PRODUCT'),
				'type' => 'product_row_summary',
				'editable' => false,
			],
		];

		if ($this->needDeliveryBlock())
		{
			$deliveryFields = [
				[
					'name' => 'PRICE_DELIVERY_WITH_CURRENCY',
					'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_PRICE_DELIVERY_WITH_CURRENCY'),
					'type' => 'money',
					'editable' => true,
					'data' => [
						'affectedFields' => ['CURRENCY', 'PRICE_DELIVERY'],
						'currency' => [
							'name' => 'CURRENCY',
							'items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems()),
						],
						'amount' => 'PRICE_DELIVERY',
						'formatted' => 'FORMATTED_PRICE_DELIVERY',
						'formattedWithCurrency' => 'FORMATTED_PRICE_DELIVERY_WITH_CURRENCY',
					],
				],
				[
					'name' => 'PRICE_DELIVERY_CALCULATED_WITH_CURRENCY',
					'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_PRICE_DELIVERY_CALCULATED_WITH_CURRENCY'),
					'type' => 'calculated_delivery_price',
					'editable' => false,
					'data' => [
						'affectedFields' => ['CURRENCY', 'PRICE_DELIVERY_CALCULATED'],
						'currency' => [
							'name' => 'CURRENCY',
							'items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems()),
						],
						'amount' => 'PRICE_DELIVERY_CALCULATED',
						'formatted' => 'FORMATTED_PRICE_DELIVERY_CALCULATED',
						'formattedWithCurrency' => 'FORMATTED_PRICE_DELIVERY_CALCULATED_WITH_CURRENCY',
					],
				],
				[
					'name' => 'CUSTOM_PRICE_DELIVERY',
					'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_CUSTOM_PRICE_DELIVERY'),
					'type' => 'hidden',
					'editable' => false,
				],
				[
					'name' => 'COMMENTS',
					'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_COMMENTS'),
					'type' => 'text',
					'editable' => true,
				],
				[
					'name' => 'DELIVERY_ID',
					'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_DELIVERY_SERVICE'),
					'type' => 'delivery_selector',
					'editable' => true,
				],
				[
					'name' => 'EXTRA_SERVICES_DATA',
					'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_EXTRA_SERVICES'),
					'type' => 'shipment_extra_services',
					'editable' => true,
				],
				[
					'name' => 'PROPERTIES',
					'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_SECTION_PROPERTIES'),
					'type' => 'order_property_wrapper',
					'transferable' => false,
					'editable' => true,
					'isDragEnabled' => $this->userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'),
					'elements' => [],
					'sortedElements' => [
						'active' => is_array($this->arResult['SHIPMENT_PROPERTIES']['ACTIVE']) ? $this->arResult['SHIPMENT_PROPERTIES']['ACTIVE'] : [],
						'hidden' => is_array($this->arResult['SHIPMENT_PROPERTIES']['HIDDEN']) ? $this->arResult['SHIPMENT_PROPERTIES']['HIDDEN'] : [],
					],
					'data' => [
						'entityType' => 'shipment',
					],
				]
			];

			if ($this->entityData['EXTRA_SERVICES_DATA'])
			{
				$this->arResult['ENTITY_FIELDS'][] = [
					'name' => 'EXTRA_SERVICES_DATA',
					'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_EXTRA_SERVICES'),
					'type' => 'shipment_extra_services',
					'editable' => true,
				];
			}

			$this->arResult['ENTITY_FIELDS'] = array_merge($this->arResult['ENTITY_FIELDS'], $deliveryFields);
		}

		$this->arResult['ENTITY_FIELDS'] = array_merge(
			$this->arResult['ENTITY_FIELDS'],
			array_values($this->userFieldInfos)
		);

		return $this->arResult['ENTITY_FIELDS'];
	}

	protected function prepareEntityConfig(): void
	{
		$mainConfig = [
			'name' => 'main',
			'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_SECTION_MAIN'),
			'type' => 'section',
			'elements' => [
				['name' => 'CLIENT'],
				['name' => 'RESPONSIBLE_ID'],
			],
		];

		$deliveryConfig = [
			'name' => 'delivery',
			'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_SECTION_DELIVERY'),
			'type' => 'section',
			'elements' => [
				['name' => 'DELIVERY_ID'],
				['name' => 'PRICE_DELIVERY_CALCULATED_WITH_CURRENCY'],
				['name' => 'PRICE_DELIVERY_WITH_CURRENCY'],
				['name' => 'COMMENTS'],
				['name' => 'EXTRA_SERVICES_DATA'],
			],
		];

		$deliveryPropertiesConfig = [
			'name' => 'properties',
			'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_SECTION_PROPERTIES'),
			'type' => 'section',
			'data' => [
				'showButtonPanel' => false,
			],
			'elements' => [
				['name' => 'PROPERTIES'],
			],
		];

		$productsConfig = [
			'name' => 'products',
			'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_SECTION_PRODUCT'),
			'type' => 'section',
			'elements' => [
				['name' => 'DOCUMENT_PRODUCTS'],
			],
			'data' => [
				'isRemovable' => 'false',
			],
		];

		if ($this->needDeliveryBlock())
		{
			$this->arResult['ENTITY_CONFIG'] = [
				$mainConfig,
				$deliveryConfig,
				$deliveryPropertiesConfig,
				$productsConfig,
			];
		}
		else
		{
			$this->arResult['ENTITY_CONFIG'] = [
				$mainConfig,
				$productsConfig,
			];
		}
	}

	protected function prepareEntityControllers(): void
	{
		$this->arResult['ENTITY_CONTROLLERS'] = [
			[
				'name' => 'ORDER_SHIPMENT_CONTROLLER',
				'type' => 'document_order_shipment_controller',
				'config' => [
					'editorId' => $this->arResult['PRODUCT_EDITOR_ID'],
					'serviceUrl' => '/bitrix/components/bitrix/crm.store.document.detail/ajax.php',
					'productDataFieldName' => $this->arResult['PRODUCT_DATA_FIELD_NAME'],
					'orderId' => $this->arResult['ORDER_ID'],
				],
			],
			[
				'name' => 'PRODUCT_LIST_CONTROLLER',
				'type' => 'store_document_product_list',
				'config' => [],
			],
		];
	}

	public function prepareEntityData(): ?array
	{
		if ($this->entityData)
		{
			return $this->entityData;
		}

		$this->entityData = $this->shipment->getFieldValues();

		$properties = $this->getPropertyEntityData($this->shipment->getPropertyCollection());
		$this->entityData = array_merge($this->entityData, $properties);

		if (isset($this->entityData['DATE_INSERT']))
		{
			$this->entityData['DATE_INSERT'] = \CCrmComponentHelper::TrimZeroTime($this->entityData['DATE_INSERT']);
		}

		if (!isset($this->entityData['CURRENCY']) || $this->entityData['CURRENCY'] === '')
		{
			$this->entityData['CURRENCY'] = \CCrmCurrency::GetBaseCurrencyID();
		}

		if ($this->mode === ComponentMode::CREATION)
		{
			$this->entityData['RESPONSIBLE_ID'] = Main\Engine\CurrentUser::get()->getId();

			$bindingEntity = $this->getOwnerEntity();
			if ($bindingEntity)
			{
				$this->entityData['RESPONSIBLE_ID'] = $bindingEntity->getAssignedById();
			}
		}

		//region DELIVERY_SERVICE
		if (
			(int)$this->entityData['DELIVERY_ID'] > 0
			&& $currentDeliveryService = Sale\Delivery\Services\Manager::getObjectById($this->entityData['DELIVERY_ID'])
		)
		{
			$this->entityData['DELIVERY_SERVICE_NAME'] = htmlspecialcharsbx($currentDeliveryService->getNameWithParent());

			$restrictResult = Sale\Delivery\Restrictions\Manager::checkService(
				$this->entityData['DELIVERY_ID'],
				$this->shipment,
				Sale\Delivery\Restrictions\Manager::MODE_MANAGER
			);
			if (($restrictResult !== Sale\Delivery\Restrictions\Manager::SEVERITY_NONE)
				|| (!$currentDeliveryService->isCompatible($this->shipment)))
			{
				$this->addError(new Main\Error(Loc::getMessage('CRM_STORE_DOCUMENT_ERROR_SHIPMENT_SERVICE_RESTRICTED')));
			}
		}
		else
		{
			$this->entityData['DELIVERY_SERVICE_NAME'] = '';
		}

		$this->entityData['DELIVERY_SERVICES_LIST'] = Crm\Order\Manager::getDeliveryServicesList($this->shipment);
		$this->entityData['DELIVERY_PROFILES_LIST'] = Crm\Order\Manager::getDeliveryProfiles($this->shipment->getDeliveryId(), $this->entityData['DELIVERY_SERVICES_LIST']);

		$deliveryId = 0;
		if (isset($this->entityData['DELIVERY_SERVICES_LIST'][$this->entityData['DELIVERY_ID']]))
		{
			$deliveryId = $this->entityData['DELIVERY_ID'];
		}

		if ($deliveryId <= 0)
		{
			foreach ($this->entityData['DELIVERY_SERVICES_LIST'] as $delivery)
			{
				if (isset($delivery['ITEMS']))
				{
					foreach ($delivery['ITEMS'] as $item)
					{
						if ($item['ID'] == $this->entityData['DELIVERY_ID'])
						{
							$deliveryId = $this->entityData['DELIVERY_ID'];
							break 2;
						}
					}
				}
			}
		}

		$profileId = 0;
		if ($deliveryId <= 0 && isset($this->entityData['DELIVERY_PROFILES_LIST'][$this->entityData['DELIVERY_ID']]))
		{
			$profileId = $this->entityData['DELIVERY_PROFILES_LIST'][$this->entityData['DELIVERY_ID']]['ID'];
			$profile = Sale\Delivery\Services\Manager::getById($profileId);
			$deliveryId = $profile['PARENT_ID'];
		}

		$this->entityData['DELIVERY_SELECTOR_DELIVERY_ID'] = $deliveryId;
		$this->entityData['DELIVERY_SELECTOR_PROFILE_ID'] = $profileId;
		$this->entityData['DELIVERY_STORE_ID'] = $this->shipment->getStoreId();

		$this->entityData['DELIVERY_STORES_LIST'] =
			Sale\Helpers\Admin\Blocks\OrderShipment::getStoresList(
				$this->entityData['DELIVERY_ID'],
				$this->entityData['DELIVERY_STORE_ID']
			);

		if (!empty($this->entityData['DELIVERY_STORES_LIST']))
		{
			$this->entityData['DELIVERY_STORES_LIST'] =
				[0 => ['ID' => 0, 'TITLE' => Loc::getMessage('CRM_STORE_DOCUMENT_SD_NOT_CHOSEN')]] +
				$this->entityData['DELIVERY_STORES_LIST'];
		}

		if (isset($this->entityData['DELIVERY_STORES_LIST'][$this->entityData['DELIVERY_STORE_ID']]['TITLE']))
		{
			$this->entityData['DELIVERY_STORE_TITLE'] = $this->entityData['DELIVERY_STORES_LIST'][$this->entityData['DELIVERY_STORE_ID']]['TITLE'];
		}

		if (isset($this->entityData['DELIVERY_STORES_LIST'][$this->entityData['DELIVERY_STORE_ID']]['ADDRESS']))
		{
			$this->entityData['DELIVERY_STORE_ADDRESS'] = $this->entityData['DELIVERY_STORES_LIST'][$this->entityData['DELIVERY_STORE_ID']]['ADDRESS'];
		}
		//endregion

		$this->entityData['FORMATED_TITLE_WITH_DATE_INSERT'] = Loc::getMessage(
			'CRM_STORE_DOCUMENT_SHIPMENT_SUBTITLE_MASK',
			[
				'#ID#' => $this->entityData['ID'],
				'#DATE_INSERT#' => 	CCrmComponentHelper::TrimDateTimeString(
					ConvertTimeStamp(
						MakeTimeStamp(
							$this->entityData['DATE_INSERT'],
							'SHORT'
						)
					)
				),
			]
		);

		$calcPrice = $this->shipment->calculateDelivery();
		if ($this->shipment->getId() <= 0 && $this->entityData['CUSTOM_PRICE_DELIVERY'] !== 'Y')
		{
			$this->entityData['PRICE_DELIVERY'] = $calcPrice->getPrice();
		}

		//region PRICE_DELIVERY & Currency
		$this->entityData['FORMATTED_PRICE_DELIVERY_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['PRICE_DELIVERY'],
			$this->entityData['CURRENCY'],
			''
		);
		$this->entityData['FORMATTED_PRICE_DELIVERY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['PRICE_DELIVERY'],
			$this->entityData['CURRENCY'],
			'#'
		);
		//endregion

		//region PRICE_DELIVERY_CALCULATED & Currency

		$calcPrice = $this->shipment->calculateDelivery();
		if (!$calcPrice->isSuccess())
		{
			$this->entityData['ERRORS'] = $calcPrice->getErrorMessages();
		}

		$this->entityData['PRICE_DELIVERY_CALCULATED'] = $calcPrice->getPrice();

		$this->entityData['FORMATTED_PRICE_DELIVERY_CALCULATED_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['PRICE_DELIVERY_CALCULATED'],
			$this->entityData['CURRENCY'],
			''
		);
		$this->entityData['FORMATTED_PRICE_DELIVERY_CALCULATED'] = \CCrmCurrency::MoneyToString(
			$this->entityData['PRICE_DELIVERY_CALCULATED'],
			$this->entityData['CURRENCY'],
			'#'
		);
		//endregion

		$this->addUserDataToEntity('RESPONSIBLE');

		$title = Loc::getMessage(
			'CRM_STORE_DOCUMENT_SHIPMENT_TITLE',
			[
				'#ACCOUNT_NUMBER#' => $this->entityData['ACCOUNT_NUMBER'],
			]
		);
		$this->entityData['TITLE'] = $title;
		$this->entityData['SITE_ID'] = $this->arResult['SITE_ID'];

		if ($this->entityData['DELIVERY_ID'] > 0)
		{
			$extraServiceManager = new Sale\Delivery\ExtraServices\Manager($this->entityData['DELIVERY_ID']);
			$extraServiceManager->setOperationCurrency($this->entityData['CURRENCY']);
			$extraServiceManager->setValues($this->shipment->getExtraServices());
			$extraService = $extraServiceManager->getItems();

			if ($extraService)
			{
				$this->entityData['EXTRA_SERVICES_DATA'] = $this->getExtraServices(
					$extraService,
					$this->shipment
				);
			}
		}

		$personTypes = Crm\Order\PersonType::load($this->arResult['SITE_ID']);
		if (empty($this->entityData['PERSON_TYPE_ID']))
		{
			$this->entityData['PERSON_TYPE_ID'] = key($personTypes);
		}

		$this->prepareClientData();
		$this->prepareDocumentProductsPreview();

		return ($this->arResult['ENTITY_DATA'] = $this->entityData);
	}

	public function getExtraServices($extraService, Order\Shipment $shipment)
	{
		$result = [];

		foreach ($extraService as $itemId => $item)
		{
			$viewHtml = $item->getViewControl();
			$editHtml = '';

			if ($item->canManagerEditValue())
			{
				$editHtml = $item->getEditControl('EXTRA_SERVICES['.(int)$itemId.']');
			}

			if ($price = $item->getPriceShipment($shipment))
			{
				$price = \CCrmCurrency::MoneyToString(
					floatval($price),
					$item->getOperatingCurrency(),
					''
				);
			}

			if ($cost = $item->getCostShipment($shipment))
			{
				$cost = \CCrmCurrency::MoneyToString(
					floatval($cost),
					$item->getOperatingCurrency(),
					''
				);
			}

			$result[] = [
				'NAME' => htmlspecialcharsbx($item->getName()),
				'EDIT_HTML' => $editHtml,
				'VIEW_HTML' => $viewHtml,
				'PRICE' => $price ?: '',
				'COST' => $cost ?: '',
			];
		}

		return $result;
	}

	protected function addUserDataToEntity($entityPrefix)
	{
		$userId = isset($this->entityData[$entityPrefix.'_ID']) ? (int)$this->entityData[$entityPrefix.'_ID'] : 0;
		if ($userId <= 0)
		{
			return;
		}

		$user = self::getUser($this->entityData[$entityPrefix.'_ID']);
		if (is_array($user))
		{
			$this->entityData[$entityPrefix.'_LOGIN'] = $user['LOGIN'];
			$this->entityData[$entityPrefix.'_NAME'] = $user['NAME'] ?? '';
			$this->entityData[$entityPrefix.'_SECOND_NAME'] = $user['SECOND_NAME'] ?? '';
			$this->entityData[$entityPrefix.'_LAST_NAME'] = $user['LAST_NAME'] ?? '';
			$this->entityData[$entityPrefix.'_PERSONAL_PHOTO'] = $user['PERSONAL_PHOTO'] ?? '';
		}

		$this->entityData[$entityPrefix.'_FORMATTED_NAME'] =
			\CUser::FormatName(
				$this->arResult['NAME_TEMPLATE'],
				[
					'LOGIN' => $this->entityData[$entityPrefix.'_LOGIN'],
					'NAME' => $this->entityData[$entityPrefix.'_NAME'],
					'LAST_NAME' => $this->entityData[$entityPrefix.'_LAST_NAME'],
					'SECOND_NAME' => $this->entityData[$entityPrefix.'_SECOND_NAME'],
				],
				true,
				false
			)
		;

		$photoId = isset($this->entityData[$entityPrefix.'_PERSONAL_PHOTO'])
			? (int)$this->entityData[$entityPrefix.'_PERSONAL_PHOTO']
			: 0
		;

		if ($photoId > 0)
		{
			$fileInfo = \CFile::ResizeImageGet(
				$photoId,
				[
					'width' => 60,
					'height'=> 60,
				],
				BX_RESIZE_IMAGE_EXACT
			);
			if (is_array($fileInfo) && isset($fileInfo['src']))
			{
				$this->entityData[$entityPrefix.'_PHOTO_URL'] = $fileInfo['src'];
			}
		}

		$this->entityData['PATH_TO_'.$entityPrefix.'_USER'] = CComponentEngine::MakePathFromTemplate(
			$this->arResult['PATH_TO_USER_PROFILE'],
			[
				'user_id' => $userId,
			]
		);
	}

	protected function prepareClientEditorFieldsParams(): array
	{
		$result = [
			\CCrmOwnerType::ContactName => [
				'REQUISITES' => \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Contact, 'requisite'),
			],
			\CCrmOwnerType::CompanyName => [
				'REQUISITES' => \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Company, 'requisite'),
			],
		];
		if (Main\Loader::includeModule('location'))
		{
			$result[\CCrmOwnerType::ContactName]['ADDRESS'] = \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Contact,'requisite_address');
			$result[\CCrmOwnerType::CompanyName]['ADDRESS'] = \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Company,'requisite_address');
		}

		return $result;
	}

	protected function prepareClientData(): void
	{
		$companyId = 0;
		$contactIds = [];

		$clientCollection = $this->order->getContactCompanyCollection();
		if ($clientCollection)
		{
			/** @var Crm\Order\Company $company */
			if ($company = $clientCollection->getPrimaryCompany())
			{
				$companyId = $company->getField('ENTITY_ID');
				$this->entityData['COMPANY_ID'] = $companyId;
			}

			$contacts = $clientCollection->getContacts();
			/** @var Crm\Order\Contact $contact */
			foreach ($contacts as $contact)
			{
				$contactIds[] = $contact->getField('ENTITY_ID');
			}
		}

		$bindingEntity = $this->getOwnerEntity();
		if (empty($companyId) && empty($contactIds) && $bindingEntity)
		{
			$companyId = $bindingEntity->getCompanyId();

			$contacts = $bindingEntity->getContacts();
			foreach ($contacts as $contact)
			{
				$contactIds[] = $contact->getId();
			}
		}

		if (!empty($companyId) || !empty($contactIds))
		{
			$this->entityData['CLIENT'] = [
				'COMPANY_ID' => $companyId,
				'CONTACT_IDS' => $contactIds,
			];
		}

		$clientInfo = [
			'COMPANY_DATA' => [],
			'CONTACT_DATA' => [],
		];

		$multiFieldData = [];
		if ($companyId > 0)
		{
			self::prepareMultifieldData(\CCrmOwnerType::Company, $companyId, 'PHONE', $multiFieldData);
			self::prepareMultifieldData(\CCrmOwnerType::Company, $companyId, 'EMAIL', $multiFieldData);

			$isEntityReadPermitted = \CCrmCompany::CheckReadPermission($companyId, $this->userPermissions);
			$companyInfo = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				\CCrmOwnerType::CompanyName,
				$companyId,
				[
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_MULTIFIELDS' => true,
					'NAME_TEMPLATE' => Crm\Format\PersonNameFormatter::getFormat(),
				]
			);

			$clientInfo['COMPANY_DATA'] = [$companyInfo];
		}

		$iteration= 0;
		foreach ($contactIds as $contactID)
		{
			self::prepareMultifieldData(\CCrmOwnerType::Contact, $contactID, 'PHONE', $multiFieldData);
			self::prepareMultifieldData(\CCrmOwnerType::Contact, $contactID, 'EMAIL', $multiFieldData);

			$isEntityReadPermitted = \CCrmContact::CheckReadPermission($contactID, $this->userPermissions);
			$clientInfo['CONTACT_DATA'][] = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				\CCrmOwnerType::ContactName,
				$contactID,
				[
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_EDIT_REQUISITE_DATA' => ($iteration === 0), // load full requisite data for first item only (due to performance optimisation)
					'REQUIRE_MULTIFIELDS' => true,
					'REQUIRE_BINDINGS' => true,
					'NAME_TEMPLATE' => Crm\Format\PersonNameFormatter::getFormat(),
				]
			);
			$iteration++;
		}
		$this->entityData['CLIENT_INFO'] = $clientInfo;

		$this->entityData['REQUISITE_BINDING'] = $this->order->getRequisiteLink();

		$this->entityData['MULTIFIELD_DATA'] = $multiFieldData;
		$this->entityData['USER_LIST_SELECT'] = $this->getDefaultUserList();

		$this->entityData['LAST_COMPANY_INFOS'] = Crm\Controller\Action\Entity\SearchAction::prepareSearchResultsJson(
			Crm\Controller\Entity::getRecentlyUsedItems(
				'crm.deal.details',
				'company',
				[
					'EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Company
				]
			)
		);
		$this->entityData['LAST_CONTACT_INFOS'] = Crm\Controller\Action\Entity\SearchAction::prepareSearchResultsJson(
			Crm\Controller\Entity::getRecentlyUsedItems(
				'crm.deal.details',
				'contact',
				[
					'EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Contact
				]
			)
		);
	}

	protected function prepareDocumentProductsPreview(): void
	{
		$documentProducts = [];
		$total = 0;

		if ($this->shipment && $this->shipment->getId() > 0)
		{
			/** @var Crm\Order\ShipmentItem $shipmentItem */
			foreach ($this->shipment->getShipmentItemCollection() as $shipmentItem)
			{
				$basketItem = $shipmentItem->getBasketItem();
				$documentProducts[] = [
					'PRICE' => $basketItem->getPrice(),
					'PRODUCT_NAME' => $basketItem->getField('NAME'),
					'CURRENCY' => $basketItem->getCurrency(),
					'QUANTITY' => $shipmentItem->getQuantity(),
				];

				$total += $basketItem->getPrice() * $shipmentItem->getQuantity();
			}
		}

		$currency = $this->shipment->getOrder()->getCurrency();
		$result = [
			'count' => count($documentProducts),
			'total' => \CCrmCurrency::MoneyToString($total, $currency),
			'items' => [],
		];

		foreach ($documentProducts as $product)
		{
			$productSum = Sale\PriceMaths::roundPrecision((float)$product['PRICE'] * (float)$product['QUANTITY']);
			$result['items'][] = [
				'PRODUCT_NAME' => $product['PRODUCT_NAME'],
				'SUM' => CCurrencyLang::CurrencyFormat($productSum, $currency),
			];
		}

		$this->entityData['DOCUMENT_PRODUCTS'] = $result;
	}

	protected function getDefaultUserList(): array
	{
		$resultList = [];
		$clientCollection = $this->order->getContactCompanyCollection();
		$primaryClient = $clientCollection->getPrimaryContact();
		$type = CCrmOwnerType::Contact;
		if (!$primaryClient)
		{
			$primaryClient = $clientCollection->getPrimaryCompany();
			$type = CCrmOwnerType::Company;
		}

		if (!$primaryClient)
		{
			$resultList[] = ['subTitle' => Loc::getMessage('CRM_STORE_DOCUMENT_EMPTY_USER_INPUT_VALUE_WITH_CLIENT')];
			return $resultList;
		}

		$userDataRaw = Crm\Binding\OrderContactCompanyTable::getList([
			'select' => [
				'USER_ID' => 'ORDER.USER_ID',
				'USER_NAME' => 'ORDER.USER.NAME',
				'USER_SECOND_NAME' => 'ORDER.USER.SECOND_NAME',
				'USER_LAST_NAME' => 'ORDER.USER.LAST_NAME',
				'USER_LOGIN' => 'ORDER.USER.LOGIN',
				'USER_EMAIL' => 'ORDER.USER.EMAIL',
			],
			'filter' => [
				'ENTITY_ID' => $primaryClient->getField('ENTITY_ID'),
				'ENTITY_TYPE_ID' => $type,
			],
			'group' => ['ORDER.USER_ID'],
		]);

		$anonymousId = (int)Order\Manager::getAnonymousUserID();
		$nameFormat = \CSite::getNameFormat(false);
		while ($user = $userDataRaw->fetch())
		{
			if ($anonymousId === (int)$user['USER_ID'])
			{
				continue;
			}

			$resultList[] = [
				'id' => $user['USER_ID'],
				'title' => \CUser::FormatName(
					$nameFormat,
					[
						'LOGIN' => $user['USER_LOGIN'],
						'NAME' => $user['USER_NAME'],
						'LAST_NAME' => $user['USER_LAST_NAME'],
						'SECOND_NAME' => $user['USER_SECOND_NAME'],
					],
					true,
					false
				),
				'subTitle' => $user['USER_LOGIN'],
				'attributes' => [
					'email' => [
						['value' => $user['USER_EMAIL']],
					],
				],
			];
		}

		if (empty($resultList))
		{
			$resultList[] = ['subTitle' => Loc::getMessage('CRM_STORE_DOCUMENT_EMPTY_USER_INPUT_VALUE')];
		}

		return $resultList;
	}

	private function checkIfInventoryManagementIsUsed()
	{
		$this->arResult['IS_DEDUCT_LOCKED'] = !Catalog\Component\UseStore::isUsed();
		if ($this->arResult['IS_DEDUCT_LOCKED'])
		{
			$sliderPath = \CComponentEngine::makeComponentPath('bitrix:catalog.warehouse.master.clear');
			$sliderPath = getLocalPath('components' . $sliderPath . '/slider.php');
			$this->arResult['MASTER_SLIDER_URL'] = $sliderPath;
		}
	}

	protected function getDocumentProducts(): array
	{
		if ($this->shipment->getId() > 0)
		{
			return $this->getShipmentProducts();
		}

		return $this->getEntityProducts();
	}

	private function getShipmentProducts(): array
	{
		$products = [];

		$defaultStore = Catalog\StoreTable::getDefaultStoreId();

		/** @var Crm\Order\ShipmentItem $shipmentItem */
		foreach ($this->shipment->getShipmentItemCollection() as $shipmentItem)
		{
			$basketItem = $shipmentItem->getBasketItem();

			$documentProduct = [
				'STORE_TO' => 0,
				'ELEMENT_ID' => $basketItem->getProductId(),
				'PURCHASING_PRICE' => $basketItem->getBasePrice(),
				'BASE_PRICE' => $basketItem->getPrice(),
				'BASE_PRICE_EXTRA' => '',
				'BASE_PRICE_EXTRA_RATE' => '',
				'BASKET_ID' => $basketItem->getId(),
			];

			$shipmentItemStoreCollection = $shipmentItem->getShipmentItemStoreCollection();
			if ($shipmentItemStoreCollection->isEmpty())
			{
				$storeId = $defaultStore;

				$reserveQuantityCollection = $basketItem->getReserveQuantityCollection();
				if ($reserveQuantityCollection->count() === 1)
				{
					/** @var Sale\ReserveQuantity $reserveQuantity */
					$reserveQuantity = $reserveQuantityCollection->current();
					$storeId = $reserveQuantity->getStoreId();
				}

				$documentProduct['ID'] = uniqid('bx_', true);
				$documentProduct['STORE_FROM'] = $storeId;
				$documentProduct['AMOUNT'] = $shipmentItem->getQuantity();

				$products[count($products) + 1] = $documentProduct;
			}
			else
			{
				/** @var Crm\Order\ShipmentItemStore $shipmentItemStore */
				foreach ($shipmentItemStoreCollection as $shipmentItemStore)
				{
					$documentProduct['ID'] = uniqid('bx_', true);
					$documentProduct['STORE_FROM'] = $shipmentItemStore->getStoreId();
					$documentProduct['AMOUNT'] = $shipmentItemStore->getQuantity();
					$documentProduct['BARCODE'] = $shipmentItemStore->getBarcode();

					$products[count($products) + 1] = $documentProduct;
				}
			}
		}

		return $products;
	}

	private function getEntityProducts(): array
	{
		$products = [];

		$bindingEntity = $this->getOwnerEntity();
		if (!$bindingEntity)
		{
			return [];
		}

		$ownerTypeId = $bindingEntity->getEntityTypeId();
		$ownerId = $bindingEntity->getId();

		$orderIds = Crm\Binding\OrderEntityTable::getOrderIdsByOwner($ownerId, $ownerTypeId);

		if (
			!\CCrmSaleHelper::isWithOrdersMode()
			|| count($orderIds) === 0
		)
		{
			$productManager = new Crm\Order\ProductManager($ownerTypeId, $ownerId);
			$productManager->setProductConverter(
				new Crm\Order\ProductManager\EntityProductConverterWithReserve()
			);

			if ($orderIds)
			{
				$order = Crm\Order\Order::load(max($orderIds));
				if ($order)
				{
					$productManager->setOrder($order);
				}
			}

			$defaultStore = Catalog\StoreTable::getDefaultStoreId();

			$deliverableProducts = $productManager->getDeliverableItems();
			foreach ($deliverableProducts as $deliverableProduct)
			{
				$reserve = $deliverableProduct['RESERVE'] ? current($deliverableProduct['RESERVE']) : [];
				if (empty($reserve['STORE_ID']))
				{
					$deliverableProduct['STORE_ID'] = $defaultStore;
				}
				else
				{
					$deliverableProduct['STORE_ID'] = (int)$reserve['STORE_ID'];
				}

				$products[] = [
					'ID' => uniqid('bx_', true),
					'STORE_FROM' => $deliverableProduct['STORE_ID'],
					'STORE_TO' => 0,
					'ELEMENT_ID' => $deliverableProduct['PRODUCT_ID'],
					'BASKET_ID' => $deliverableProduct['BASKET_CODE'],
					'AMOUNT' => $deliverableProduct['QUANTITY'],
					'PURCHASING_PRICE' => $deliverableProduct['BASE_PRICE'],
					'BASE_PRICE' => $deliverableProduct['PRICE'],
					'BASE_PRICE_EXTRA' => '',
					'BASE_PRICE_EXTRA_RATE' => '',
					'BARCODE' => '',
				];
			}
		}

		return $products;
	}

	private function getOwnerEntity(): ?Crm\Item
	{
		static $item = null;

		if ($item)
		{
			return $item;
		}

		$ownerTypeId = $this->arResult['OWNER_TYPE_ID'];
		$ownerId = $this->arResult['OWNER_ID'];

		if ($this->order)
		{
			$entityBinding = $this->order->getEntityBinding();
			if ($entityBinding)
			{
				$ownerTypeId = $entityBinding->getOwnerTypeId();
				$ownerId = $entityBinding->getOwnerId();
			}
		}

		if ($ownerTypeId && $ownerId)
		{
			$factory = Crm\Service\Container::getInstance()->getFactory($ownerTypeId);
			if ($factory)
			{
				$item = $factory->getItem($ownerId);
				if ($item)
				{
					return $item;
				}
			}
		}

		return null;
	}

	public function sendAnalyticsAction()
	{
		return null;
	}

	public function configureActions()
	{
		// TODO: Implement configureActions() method.
	}
}
