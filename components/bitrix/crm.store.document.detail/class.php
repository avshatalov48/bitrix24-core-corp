<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Catalog\Config\State;
use Bitrix\Catalog\Product\Store\BatchManager;
use Bitrix\Catalog\StoreBatchDocumentElementTable;
use Bitrix\Catalog\Url\InventoryManagementSourceBuilder;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Crm;
use Bitrix\Crm\Integration\Catalog\Contractor\CategoryRepository;
use Bitrix\Crm\Integration\Catalog\Contractor\Provider;
use Bitrix\Main;
use Bitrix\Crm\Order;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Main\Config\Option;
use Bitrix\Sale;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Component\ComponentError;
use Bitrix\Crm\Component\EntityDetails\ComponentMode;
use Bitrix\Catalog;
use Bitrix\Sale\Tax\VatCalculator;
use Bitrix\UI;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\ShipmentDocumentRealization;
use Bitrix\Crm\Integration\Catalog\WarehouseOnboarding;

if (!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
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
	private ?Order\Shipment $shipment;

	/** @var Order\Payment */
	private ?Order\Payment $payment = null;

	/** @var Order\Order */
	private ?Order\Order $order;

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::ShipmentDocument;
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
		if ($error instanceof Main\Error)
		{
			return $error->getMessage();
		}

		if ($error === ComponentError::ENTITY_NOT_FOUND)
		{
			return Loc::getMessage('CRM_STORE_DOCUMENT_SD_SHIPMENT_NOT_FOUND');
		}

		if($error === self::COMPONENT_ERROR_EMPTY_ORDER_ID)
		{
			return Loc::getMessage('CRM_STORE_DOCUMENT_SD_ORDER_NOT_FOUND');
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

		$this->init();
		if (!$this->checkDocumentReadRight())
		{
			$this->arResult['ERROR_MESSAGES'][] = Loc::getMessage(
				Main\Loader::includeModule('bitrix24')
					? 'CRM_STORE_DOCUMENT_SHIPMENT_ERR_ACCESS_DENIED_CLOUD'
					: 'CRM_STORE_DOCUMENT_SHIPMENT_ERR_ACCESS_DENIED_BOX'
			);
			$this->includeComponentTemplate();

			return;
		}
		//region Params
		$this->arResult['DOCUMENT_ID'] = isset($this->arParams['~DOCUMENT_ID']) ? (int)$this->arParams['~DOCUMENT_ID'] : 0;

		if ($this->arResult['DOCUMENT_ID'] === 0 && !$this->checkDocumentModifyRight())
		{
			$this->arResult['ERROR_MESSAGES'][] = Loc::getMessage(
				Main\Loader::includeModule('bitrix24')
					? 'CRM_STORE_DOCUMENT_SHIPMENT_ERR_ACCESS_DENIED_CLOUD'
					: 'CRM_STORE_DOCUMENT_SHIPMENT_ERR_ACCESS_DENIED_BOX'
			);
			$this->includeComponentTemplate();

			return;
		}

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

		$this->arResult['WAREHOUSE_CRM_TOUR_DATA'] = $this->getWarehouseOnboardTourData($this->arResult['OWNER_TYPE_ID']);

		$this->arResult['ORDER_ID'] = 0;
		if (!\CCrmSaleHelper::isWithOrdersMode())
		{
			$this->arResult['ORDER_ID'] = (int)($this->arParams['CONTEXT']['ORDER_ID'] ?? 0);
		}
		$this->arResult['PAYMENT_ID'] = (int)($this->arParams['CONTEXT']['PAYMENT_ID'] ?? 0);

		$this->arResult['ACTION_URI'] = $this->arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;
		$this->arResult['PRODUCT_DATA_FIELD_NAME'] = 'ORDER_SHIPMENT_PRODUCT_DATA';
		$this->arResult['PRODUCT_EDITOR_ID'] = 'order_shipment_product_editor';

		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::OrderShipmentName.'_'.$this->arResult['DOCUMENT_ID'];

		// $shipment id
		$this->setEntityID($this->arResult['DOCUMENT_ID']);

		if (!$this->tryToDetectMode())
		{
			$this->showErrors();

			return;
		}

		if ($this->getEntityID() > 0)
		{
			$this->shipment = Sale\Repository\ShipmentRepository::getInstance()->getById($this->entityID);
			if ($this->shipment)
			{
				$this->order = $this->shipment->getOrder();
			}
			else
			{
				$this->addError(ComponentError::ENTITY_NOT_FOUND);
			}
		}
		elseif ($this->arResult['PAYMENT_ID'])
		{
			$this->payment = Sale\Repository\PaymentRepository::getInstance()->getById($this->arResult['PAYMENT_ID']);
			if ($this->payment)
			{
				$this->order = $this->payment->getOrder();
			}
			else
			{
				$this->addError(new Main\Error(
					Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_PAYMENT_NOT_FOUND')
				));
			}
		}
		elseif ($this->arResult['ORDER_ID'] && $this->arResult['ORDER_ID'] > 0)
		{
			$this->order = Crm\Order\Order::load($this->arResult['ORDER_ID']);
			if (!$this->order)
			{
				$this->addError(self::COMPONENT_ERROR_EMPTY_ORDER_ID);
			}
		}
		else
		{
			$this->order = Crm\Order\Manager::createEmptyOrder($this->getSiteId());

			$bindingEntity = $this->getOwnerEntity();
			if ($bindingEntity)
			{
				$this->order->setFieldNoDemand('CURRENCY', $bindingEntity->getCurrencyId());
			}
		}

		if ($this->getErrors())
		{
			$this->showErrors();

			return;
		}

		$this->arResult['ORDER_ID'] = $this->order->getId();

		$shipments = $this->order->getShipmentCollection();
		if ($this->mode === ComponentMode::CREATION)
		{
			$this->shipment = $shipments->createItem();
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

		$this->arResult['FOCUS_TO_PRODUCT_LIST'] = (bool)($this->request->get('productListFocus') ?? false);
		$this->arResult['FOCUSED_TAB'] = $this->request->get('focusedTab');

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

		$this->arResult['TOOLBAR_ID'] = "toolbar_realization_document_{$this->entityID}";
		//endregion

		//region Entity Info
		$this->arResult['ENTITY_INFO'] = [
			'DOCUMENT_ID' => $this->entityID,
			'ENTITY_ID' => $this->entityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::ShipmentDocument,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::ShipmentDocumentName,
			'TITLE' => $this->entityData['TITLE'],
			'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::OrderShipment, $this->entityID, false),
		];
		//endregion

		//region Page title
		if ($this->mode === ComponentMode::CREATION)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_CREATION_PAGE_TITLE_MSGVER_1'));
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
				if ($userField['USER_TYPE_ID'] === 'date' && $userField['MULTIPLE'] !== 'Y')
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
		$this->checkIfInventoryManagementIsDisabled();

		$this->arResult['DOCUMENT_PERMISSIONS'] = [
			'conduct' => $this->checkDocumentConductRight(),
			'cancel' => $this->checkDocumentCancelRight(),
		];

		$this->arResult['IS_PRODUCT_BATCH_METHOD_SELECTED'] = \Bitrix\Catalog\Config\State::isProductBatchMethodSelected();

		$this->arResult['IS_READ_ONLY'] = !$this->checkDocumentModifyRight();
		$this->arResult['UI_ENTITY_CARD_SETTINGS_EDITABLE'] = AccessController::getCurrent()->check(ActionDictionary::ACTION_STORE_DOCUMENT_CARD_EDIT);
		$this->arResult['IS_TOOL_PANEL_ALWAYS_VISIBLE'] = $this->checkDocumentCancelRight()	|| $this->checkDocumentConductRight();

		$this->arResult['BUTTONS'] = $this->getToolbarButtons();
		$this->arResult['COMPONENT_PRODUCTS'] = $this->getDocumentProducts();

		$this->arResult['INVENTORY_MANAGEMENT_SOURCE'] =
			InventoryManagementSourceBuilder::getInstance()->getInventoryManagementSource()
		;
		$this->arResult['IS_ONEC_MODE'] = Catalog\Store\EnableWizard\Manager::isOnecMode();

		$this->includeComponentTemplate();
	}

	/**
	 * @return array
	 */
	private function getToolbarButtons(): array
	{
		$result = [];

		if (DocumentGeneratorManager::getInstance()->isDocumentButtonAvailable())
		{
			$result[] = [
				'TEXT' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_DOCUMENT_BUTTON'),
				'TYPE' => 'crm-document-button',
				'PARAMS' => DocumentGeneratorManager::getInstance()->getDocumentButtonParameters(
					ShipmentDocumentRealization::class,
					$this->entityID
				),
			];
		}

		return $result;
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

		$categoryParams = [
			CCrmOwnerType::Company => CategoryRepository::getIdByEntityTypeId(CCrmOwnerType::Company) ?? 0,
			CCrmOwnerType::Contact => CategoryRepository::getIdByEntityTypeId(CCrmOwnerType::Contact) ?? 0,
		];

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
								'url' => '/bitrix/components/bitrix/crm.store.document.detail/ajax.php?'.bitrix_sessid_get(),
							],
						],
					],
					'clientEditorFieldsParams' => CCrmComponentHelper::prepareClientEditorFieldsParams(
						['categoryParams' => $categoryParams]
					),
				],
			],
			[
				'name' => 'ID',
				'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_FIELD_ID_MSGVER_1'),
				'type' => 'hidden',
				'editable' => false,
			],
			[
				'name' => 'XML_ID',
				'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_FIELD_XML_ID_MSGVER_1'),
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
				'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_FIELD_PRODUCT_2'),
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

		$extraConfig = [
			'name' => 'extra',
			'title' => Loc::getMessage('CRM_STORE_DOCUMENT_SHIPMENT_SECTION_EXTRA'),
			'type' => 'section',
			'elements' => [
				['name' => 'RESPONSIBLE_ID'],
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
				$extraConfig,
			];
		}
		else
		{
			$this->arResult['ENTITY_CONFIG'] = [
				$mainConfig,
				$productsConfig,
				$extraConfig,
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
			if ($this->order->getCurrency())
			{
				$this->entityData['CURRENCY'] = $this->order->getCurrency();
			}
			else
			{
				$this->entityData['CURRENCY'] = \CCrmCurrency::GetBaseCurrencyID();
			}
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

	protected function prepareClientData(): void
	{
		$companyId = 0;
		$contactIds = [];

		if ($this->mode === ComponentMode::CREATION)
		{
			$bindingEntity = $this->getOwnerEntity();
			if ($bindingEntity)
			{
				$companyId = $bindingEntity->getCompanyId();

				$contacts = $bindingEntity->getContacts();
				foreach ($contacts as $contact)
				{
					$contactIds[] = $contact->getId();
				}
			}
		}
		else
		{
			$clientCollection = $this->order->getContactCompanyCollection();
			if ($clientCollection && !$clientCollection->isEmpty())
			{
				/** @var Crm\Order\Company $company */
				if ($company = $clientCollection->getPrimaryCompany())
				{
					$companyId = $company->getField('ENTITY_ID');
				}

				$contacts = $clientCollection->getContacts();
				/** @var Crm\Order\Contact $contact */
				foreach ($contacts as $contact)
				{
					$contactIds[] = $contact->getField('ENTITY_ID');
				}
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

		if ($companyId > 0)
		{
			\CCrmComponentHelper::prepareMultifieldData(
				\CCrmOwnerType::Company,
				[$companyId],
				[
					'PHONE',
					'EMAIL',
				],
				$this->entityData
			);
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
		\CCrmComponentHelper::prepareMultifieldData(
			\CCrmOwnerType::Contact,
			$contactIds,
			[
				'PHONE',
				'EMAIL',
			],
			$this->entityData
		);
		foreach ($contactIds as $contactID)
		{
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
					'NORMALIZE_MULTIFIELDS' => true,
				]
			);
			$iteration++;
		}
		$this->entityData['CLIENT_INFO'] = $clientInfo;

		$this->entityData['REQUISITE_BINDING'] = $this->order->getRequisiteLink();

		$this->entityData['USER_LIST_SELECT'] = $this->getDefaultUserList();

		$categoryParams = CCrmComponentHelper::getEntityClientFieldCategoryParams(CCrmOwnerType::OrderShipment);
		$this->entityData['LAST_COMPANY_INFOS'] = Crm\Controller\Action\Entity\SearchAction::prepareSearchResultsJson(
			Crm\Controller\Entity::getRecentlyUsedItems(
				'crm.deal.details',
				'company',
				[
					'EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Company,
					'EXPAND_CATEGORY_ID' => $categoryParams[CCrmOwnerType::Company]['categoryId'],
				]
			)
		);
		$this->entityData['LAST_CONTACT_INFOS'] = Crm\Controller\Action\Entity\SearchAction::prepareSearchResultsJson(
			Crm\Controller\Entity::getRecentlyUsedItems(
				'crm.deal.details',
				'contact',
				[
					'EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
					'EXPAND_CATEGORY_ID' => $categoryParams[CCrmOwnerType::Contact]['categoryId'],
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
					'PRICE' => $basketItem->getPriceWithVat(),
					'PRODUCT_NAME' => $basketItem->getField('NAME'),
					'CURRENCY' => $basketItem->getCurrency(),
					'QUANTITY' => $shipmentItem->getQuantity(),
					'ID' => $basketItem->getId(),
					'PRODUCT_ID' => $basketItem->getProductId()
				];

				$total += $basketItem->getPriceWithVat() * $shipmentItem->getQuantity();
			}
		}

		$currency = $this->shipment->getOrder()->getCurrency();
		$documentIsDeducted = $this->entityData['DEDUCTED'] ?? 'Y';
		$result = [
			'count' => count($documentProducts),
			'total' => \CCrmCurrency::MoneyToString($total, $currency),
			'items' => [],
			'isReadOnly' => $documentIsDeducted === 'Y' || !$this->checkDocumentModifyRight(),
		];

		foreach ($documentProducts as $product)
		{
			$result['items'][] = EditorAdapter::formProductRowData(Crm\ProductRow::createFromArray($product), $currency);
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
		$this->arResult['IS_DEDUCT_LOCKED'] = !State::isUsedInventoryManagement();
		if ($this->arResult['IS_DEDUCT_LOCKED'])
		{
			$sliderPath = \CComponentEngine::makeComponentPath('bitrix:catalog.store.enablewizard');
			$sliderPath = getLocalPath('components' . $sliderPath . '/slider.php');
			$this->arResult['MASTER_SLIDER_URL'] = $sliderPath;
		}
	}

	private function checkIfInventoryManagementIsDisabled(): void
	{
		$this->arResult['IS_INVENTORY_MANAGEMENT_DISABLED'] = !\Bitrix\Catalog\Config\Feature::checkInventoryManagementFeatureByCurrentMode();

		if ($this->arResult['IS_INVENTORY_MANAGEMENT_DISABLED'])
		{
			$this->arResult['INVENTORY_MANAGEMENT_FEATURE_SLIDER_CODE'] = \Bitrix\Catalog\Config\Feature::getInventoryManagementHelpLink()['FEATURE_CODE'] ?? null;
		}
		else
		{
			$this->arResult['INVENTORY_MANAGEMENT_FEATURE_SLIDER_CODE'] = null;
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
				'ID' => uniqid('bx_', true),
				'STORE_TO' => 0,
				'STORE_FROM' => 0,
				'ELEMENT_ID' => $basketItem->getProductId(),
				'PURCHASING_PRICE' => 0,
				'BASE_PRICE' => $basketItem->getPrice(),
				'BASE_PRICE_EXTRA' => '',
				'BASE_PRICE_EXTRA_RATE' => '',
				'BASKET_ID' => $basketItem->getId(),
				'AMOUNT' => 0,
				'BARCODE' => '',
				'TAX_RATE' => $basketItem->getVatRate() === null ? null : $basketItem->getVatRate() * 100,
				'TAX_INCLUDED' => $basketItem->getField('VAT_INCLUDED'),
				'QUANTITY' => $basketItem->getQuantity(),
			];

			$pricesConverter = new Order\ProductManager\ProductConverter\PricesConverter();
			$productRowPrices = $pricesConverter->convertToProductRowPrices(
				$basketItem->getPrice(),
				$basketItem->getBasePrice(),
				$basketItem->getVatRate() ?? 0,
				$basketItem->isVatInPrice()
			);

			$documentProduct += $productRowPrices;

			$shipmentItemStoreCollection = $shipmentItem->getShipmentItemStoreCollection();
			$batchManager = new BatchManager($basketItem->getProductId());
			if ($shipmentItemStoreCollection && !$shipmentItemStoreCollection->isEmpty())
			{
				$shipmentIds = array_column($shipmentItemStoreCollection->toArray(), 'ID');
				$shipmentBatchPrices = [];
				$shipmentBatchData = StoreBatchDocumentElementTable::getList([
					'filter' => ['=SHIPMENT_ITEM_STORE_ID' => $shipmentIds],
					'select' => ['SHIPMENT_ITEM_STORE_ID', 'BATCH_PRICE', 'AMOUNT'],
				]);
				while ($priceMap = $shipmentBatchData->fetch())
				{
					$shipmentBatchPrices[$priceMap['SHIPMENT_ITEM_STORE_ID']] ??= [
						'AMOUNT' => 0,
						'COST_SUM' => 0,
					];

					$amount = -$priceMap['AMOUNT'];
					$costSum = $amount * $priceMap['BATCH_PRICE'];
					$shipmentBatchPrices[$priceMap['SHIPMENT_ITEM_STORE_ID']]['AMOUNT'] += $amount;
					$shipmentBatchPrices[$priceMap['SHIPMENT_ITEM_STORE_ID']]['COST_SUM'] += $costSum;
				}

				/** @var Crm\Order\ShipmentItemStore $shipmentItemStore */
				foreach ($shipmentItemStoreCollection as $shipmentItemStore)
				{
					if ($basketItem->isReservableItem())
					{
						$documentProduct['STORE_FROM'] = $shipmentItemStore->getStoreId();
					}

					$documentProduct['AMOUNT'] = $shipmentItemStore->getQuantity();
					$documentProduct['BARCODE'] = $shipmentItemStore->getBarcode();
					$documentProduct['PURCHASING_PRICE'] = 0;
					if (isset($shipmentBatchPrices[$shipmentItemStore->getId()]))
					{
						$batchInfo = $shipmentBatchPrices[$shipmentItemStore->getId()];
						$costPrice = $batchInfo['COST_SUM'] / $batchInfo['AMOUNT'];
						$precision = (int)Option::get('sale', 'value_precision', 2);
						$documentProduct['PURCHASING_PRICE'] = round($costPrice, $precision);
					}

					if (!$this->shipment->isShipped())
					{
						$documentProduct['PURCHASING_PRICE'] = $batchManager->calculateCostPrice(
							$documentProduct['AMOUNT'],
							$documentProduct['STORE_FROM'],
							$basketItem->getCurrency()
						);
					}

					$products[] = $documentProduct;
				}
			}
			else
			{
				$storeId = $defaultStore;

				$reserveQuantityCollection = $basketItem->getReserveQuantityCollection();
				if ($reserveQuantityCollection && $reserveQuantityCollection->count() === 1)
				{
					/** @var Sale\ReserveQuantity $reserveQuantity */
					$reserveQuantity = $reserveQuantityCollection->current();
					$storeId = $reserveQuantity->getStoreId();
				}

				if ($basketItem->isReservableItem())
				{
					$documentProduct['STORE_FROM'] = $storeId;
				}

				$documentProduct['AMOUNT'] = $shipmentItem->getQuantity();
				if (!$this->shipment->isShipped() && !empty($documentProduct['STORE_FROM']))
				{
					$documentProduct['PURCHASING_PRICE'] = $batchManager->calculateCostPrice(
						$documentProduct['AMOUNT'],
						$documentProduct['STORE_FROM'],
						$basketItem->getCurrency()
					);
				}

				$products[] = $documentProduct;
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
		$currency = $bindingEntity->getCurrencyId();

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

			$basketIdsFilter = $this->getEntityProductsBasketIdFilter();
			$deliverableProducts = $productManager->getRealizationableItems();
			foreach ($deliverableProducts as $deliverableProduct)
			{
				if (isset($deliverableProduct['PRODUCT_ID']) && $deliverableProduct['PRODUCT_ID'] <= 0)
				{
					continue;
				}

				if (
					!empty($basketIdsFilter)
					&& !in_array($deliverableProduct['BASKET_CODE'], $basketIdsFilter, true)
				)
				{
					continue;
				}

				$reserve = $deliverableProduct['RESERVE'] ? current($deliverableProduct['RESERVE']) : [];
				if (empty($reserve['STORE_ID']))
				{
					$deliverableProduct['STORE_ID'] = (int)$defaultStore;
				}
				else
				{
					$deliverableProduct['STORE_ID'] = (int)$reserve['STORE_ID'];
				}

				$quantity = $this->getEntityProductQuantity($deliverableProduct);

				if ($quantity <= 0)
				{
					continue;
				}

				$batchManager = new \Bitrix\Catalog\Product\Store\BatchManager($deliverableProduct['PRODUCT_ID']);
				$costPrice = $batchManager->calculateCostPrice(
					$quantity,
					$deliverableProduct['STORE_ID'],
					$currency
				);
				$vatRate = $deliverableProduct['VAT_RATE'] ?? null;

				$product = [
					'ID' => uniqid('bx_', true),
					'STORE_FROM' => $deliverableProduct['STORE_ID'],
					'STORE_TO' => 0,
					'ELEMENT_ID' => $deliverableProduct['PRODUCT_ID'],
					'BASKET_ID' => $deliverableProduct['BASKET_CODE'],
					'AMOUNT' => $quantity,
					'PURCHASING_PRICE' => $costPrice,
					'BASE_PRICE' => $deliverableProduct['PRICE'],
					'BASE_PRICE_EXTRA' => '',
					'BASE_PRICE_EXTRA_RATE' => '',
					'BARCODE' => '',
					'TAX_RATE' => $vatRate === null ? $vatRate : ($vatRate * 100),
					'TAX_INCLUDED' => $deliverableProduct['VAT_INCLUDED'],
					'QUANTITY' => $quantity,
				];

				$pricesConverter = new Order\ProductManager\ProductConverter\PricesConverter();
				$productRowPrices = $pricesConverter->convertToProductRowPrices(
					$deliverableProduct['PRICE'],
					$deliverableProduct['BASE_PRICE'],
					$vatRate ?? 0,
					$deliverableProduct['VAT_INCLUDED'] === 'Y'
				);

				$products[] = $product + $productRowPrices;
			}
		}

		return $products;
	}

	private function getEntityProductsBasketIdFilter(): array
	{
		if (!$this->payment)
		{
			return [];
		}

		$result = [];

		/** @var Order\PayableBasketItem $basketItem */
		foreach ($this->payment->getPayableItemCollection()->getBasketItems() as $payableItem)
		{
			/** @var Order\BasketItem $basketItem */
			$basketItem = $payableItem->getEntityObject();

			$result[] = $basketItem->getId();
		}

		return $result;
	}

	private function getEntityProductQuantity(array $product): float
	{
		$quantity = $product['QUANTITY'];

		if ($product['TYPE'] !== Sale\BasketItem::TYPE_SERVICE)
		{
			if ((int)$product['BASKET_CODE'] > 0)
			{
				/** @var Sale\Reservation\BasketReservationService $basketReservation */
				$basketReservation = Main\DI\ServiceLocator::getInstance()->get('sale.basketReservation');

				$availableQuantity = $basketReservation->getAvailableCountForBasketItem(
					(int)$product['BASKET_CODE'],
					$product['STORE_ID']
				);

				$quantity = min($quantity, $availableQuantity);
			}
			else
			{
				$storeQuantityRow = Catalog\StoreProductTable::getRow([
					'select' => [
						'AMOUNT',
						'QUANTITY_RESERVED',
					],
					'filter' => [
						'=STORE_ID' => $product['STORE_ID'],
						'=PRODUCT_ID' => $product['PRODUCT_ID'],
					],
				]);
				if ($storeQuantityRow)
				{
					$availableQuantity = $storeQuantityRow['AMOUNT'] - $storeQuantityRow['QUANTITY_RESERVED'];
					$quantity = min($quantity, $availableQuantity);
				}
			}
		}

		return $quantity;
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

		$isOwnerContext = $ownerTypeId && $ownerId;
		if (!$isOwnerContext)
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

	private function getWarehouseOnboardTourData(int $ownerTypeId): array
	{
		$tourData = [];

		if
		(
			WarehouseOnboarding::isCrmWarehouseOnboardingAvailable($this->userID)
			&& $ownerTypeId === CCrmOwnerType::Deal
		)
		{
			$warehouseOnboarding = new WarehouseOnboarding($this->userID);
			if ($warehouseOnboarding->isStoreDocumentChainStepAvailable())
			{
				$tourData = [
					'IS_TOUR_AVAILABLE' => true,
					'CHAIN_DATA' => $warehouseOnboarding->getCurrentChainData(),
				];
			}
			else
			{
				$tourData['IS_TOUR_AVAILABLE'] = false;
			}
		}
		else
		{
			$tourData['IS_TOUR_AVAILABLE'] = false;
		}


		return $tourData;
	}

	private function checkDocumentReadRight(): bool
	{
		return
			AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ)
			&& AccessController::getCurrent()->checkByValue(
				ActionDictionary::ACTION_STORE_DOCUMENT_VIEW,
				StoreDocumentTable::TYPE_SALES_ORDERS
			)
			;
	}

	private function checkDocumentModifyRight(): bool
	{
		return
			$this->checkDocumentReadRight()
			&& AccessController::getCurrent()->checkByValue(
				ActionDictionary::ACTION_STORE_DOCUMENT_MODIFY,
				StoreDocumentTable::TYPE_SALES_ORDERS
			)
			;
	}

	private function checkDocumentConductRight(): bool
	{
		return
			$this->checkDocumentReadRight()
			&& AccessController::getCurrent()->checkByValue(
				ActionDictionary::ACTION_STORE_DOCUMENT_CONDUCT,
				StoreDocumentTable::TYPE_SALES_ORDERS
			)
			;
	}

	private function checkDocumentCancelRight(): bool
	{
		return
			$this->checkDocumentReadRight()
			&& AccessController::getCurrent()->checkByValue(
				ActionDictionary::ACTION_STORE_DOCUMENT_CANCEL,
				StoreDocumentTable::TYPE_SALES_ORDERS
			)
			;
	}
}
