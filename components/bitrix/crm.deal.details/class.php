<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Catalog;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Crm;
use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Component\EntityDetails\ComponentMode;
use Bitrix\Crm\Component\EntityDetails\Traits;
use Bitrix\Crm\Controller\Action\Entity\SearchAction;
use Bitrix\Crm\Conversion\LeadConversionWizard;
use Bitrix\Crm\Conversion\QuoteConversionWizard;
use Bitrix\Crm\Integration\Catalog\WarehouseOnboarding;
use Bitrix\Crm\Recurring;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Crm\Tracking;
use Bitrix\Currency;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\SalesCenter\Integration\LandingManager;

if (!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));

	return;
}

Loc::loadMessages(__FILE__);

class CCrmDealDetailsComponent
	extends CBitrixComponent
	implements Crm\Integration\UI\EntityEditor\SupportsEditorProvider
{
	use Traits\EditorConfig;
	use Traits\InitializeAttributeScope;
	use Traits\InitializeExternalContextId;
	use Traits\InitializeGuid;
	use Traits\InitializeUFConfig;
	use Traits\InitializeAdditionalFieldsData;
	use Crm\Entity\Traits\VisibilityConfig;

	/** @var string */
	protected $guid = '';
	/** @var int */
	private $userID = 0;
	/** @var  CCrmPerms|null */
	private $userPermissions = null;
	/** @var string */
	private $userFieldEntityID;
	/** @var CCrmUserType|null  */
	private $userType = null;
	/** @var array|null */
	private $userFields = null;
	/** @var array|null */
	private $userFieldInfos = null;
	/** @var \Bitrix\Main\UserField\Dispatcher|null */
	private $userFieldDispatcher = null;
	/** @var int */
	private $entityID = 0;
	/** @var array|null */
	private $entityFieldInfos = null;
	/** @var array|null */
	private $entityData = null;
	/** @var array|null */
	private $entityDataScheme = null;
	/** @var array|null */
	private $entityFieldAttributeConfig = null;
	/** @var int */
	private $categoryID = 0;
	/** @var array|null */
	private $stages = null;
	/** @var bool */
	private $isEditMode = false;
	/** @var bool */
	private $isCopyMode = false;
	/** @var bool */
	private $isExposeMode = false;
	/** @var bool */
	private $isEnableRecurring = true;
	/** @var bool */
	private $isTaxMode = false;
	/** @var \Bitrix\Crm\Conversion\EntityConversionWizard|null  */
	private $conversionWizard = null;
	/** @var int */
	private $leadID = 0;
	/** @var int */
	private $quoteID = 0;
	/** @var array|null */
	private $defaultFieldValues = null;
	/** @var array|null */
	private $types = null;
	/** @var bool */
	private $enableSearchHistory = true;
	/** @var array */
	private $defaultEntityData = [];
	/** @var bool */
	private $isLocationModuleIncluded = false;
	/** @var bool */
	private $isCatalogModuleIncluded = false;
	private bool $isSalescenterModuleIncluded = false;
	/** @var Crm\Service\Factory\Deal|null */
	private ?Crm\Service\Factory\Deal $factory;
	/** @var EditorAdapter|null */
	private ?EditorAdapter $editorAdapter;
	private $parentFieldInfos;

	public function __construct($component = null)
	{
		/** @global \CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

		parent::__construct($component);

		$this->userID = CCrmSecurityHelper::GetCurrentUserID();
		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$this->userFieldEntityID = \CCrmDeal::GetUserFieldEntityID();
		$this->userType = new \CCrmUserType($USER_FIELD_MANAGER, $this->userFieldEntityID);
		$this->userFieldDispatcher = \Bitrix\Main\UserField\Dispatcher::instance();

		$this->isTaxMode = \CCrmTax::isTaxMode();
		$factory = Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
		if ($factory)
		{
			$this->factory = $factory;
			$this->editorAdapter = $this->factory->getEditorAdapter();
		}
		else
		{
			$this->factory = $this->editorAdapter = null;
		}
	}

	public function initializeParams(array $params)
	{
		$this->includeModules();

		foreach($params as $k => $v)
		{
			if($k === 'INITIAL_DATA' && is_array($v))
			{
				$this->arResult['INITIAL_DATA'] = $this->arParams['INITIAL_DATA'] = $v;
			}
			elseif ($k === 'COMPONENT_MODE' && is_numeric($v))
			{
				$this->arParams['COMPONENT_MODE'] = (int)$v;
			}
			elseif ($k === 'CATEGORY_ID' && is_numeric($v))
			{
				$this->setCategoryID((int)$v);
			}
			elseif($k === 'LEAD_ID' || $k === 'QUOTE_ID')
			{
				$this->arResult[$k] = $this->arParams[$k] = (int)$v;

				if($k === 'LEAD_ID')
				{
					$this->leadID = (int)$v;
				}

				if($k === 'QUOTE_ID')
				{
					$this->quoteID = (int)$v;
				}
			}

			if(!is_string($v))
			{
				continue;
			}

			if($k === 'PATH_TO_PRODUCT_SHOW')
			{
				$this->arResult['PATH_TO_PRODUCT_SHOW'] = $this->arParams['PATH_TO_PRODUCT_SHOW'] = $v;
			}
			elseif($k === 'PATH_TO_USER_PROFILE')
			{
				$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] = $v;
			}
			elseif($k === 'NAME_TEMPLATE')
			{
				$this->arResult['NAME_TEMPLATE'] = $this->arParams['NAME_TEMPLATE'] = $v;
			}
			elseif ($k === 'ENABLE_SEARCH_HISTORY')
			{
				$this->enableSearchHistory($v === 'Y');
			}
			elseif($k === 'DEFAULT_CONTACT_ID')
			{
				$this->arResult['DEFAULT_CONTACT_ID'] = $this->arParams['DEFAULT_CONTACT_ID'] = (int)$v;
			}
		}
	}

	public function prepareConfiguration()
	{
		if (isset($this->arResult['ENTITY_CONFIG']))
		{
			return $this->arResult['ENTITY_CONFIG'];
		}

		$userFieldConfigElements = array();
		foreach(array_keys($this->userFieldInfos) as $fieldName)
		{
			$userFieldConfigElements[] = array('name' => $fieldName);
		}

		$this->arResult['ENTITY_CONFIG'] = array(
			array(
				'name' => 'main',
				'title' => Loc::getMessage('CRM_DEAL_SECTION_MAIN'),
				'type' => 'section',
				'elements' => array(
					array('name' => 'TITLE'),
					array('name' => 'STAGE_ID'),
					array('name' => 'OPPORTUNITY_WITH_CURRENCY'),
					array('name' => 'CLOSEDATE'),
					array('name' => 'CLIENT'),
				)
			),
			array(
				'name' => 'additional',
				'title' => Loc::getMessage('CRM_DEAL_SECTION_ADDITIONAL'),
				'type' => 'section',
				'elements' =>
					array_merge(
						array(
							array('name' => 'TYPE_ID'),
							array('name' => 'SOURCE_ID'),
							array('name' => 'SOURCE_DESCRIPTION'),
							array('name' => 'BEGINDATE'),
							//array('name' => 'LOCATION_ID'),
							array('name' => 'OPENED'),
							array('name' => 'ASSIGNED_BY_ID'),
							array('name' => 'OBSERVER'),
							array('name' => 'COMMENTS'),
							array('name' => 'UTM'),
						),
						$userFieldConfigElements
					)
			),
			array(
				'name' => 'products',
				'title' => Loc::getMessage('CRM_DEAL_SECTION_PRODUCTS'),
				'type' => 'section',
				'elements' => array(
					array('name' => "PRODUCT_ROW_SUMMARY")
				)
			),
			array(
				'name' => 'recurring',
				'title' => Loc::getMessage('CRM_DEAL_SECTION_RECURRING'),
				'type' => 'section',
				'elements' => array(
					array('name' => 'RECURRING')
				)
			)
		);

		return $this->arResult['ENTITY_CONFIG'];
	}

	private function includeModules(): void
	{
		$this->isLocationModuleIncluded = Main\Loader::includeModule('location');
		$this->isCatalogModuleIncluded = Main\Loader::includeModule('catalog');
		$this->isSalescenterModuleIncluded = Main\Loader::includeModule('salescenter');
	}

	public function prepareEntityControllers(): array
	{
		if (!isset($this->arResult['ENTITY_CONTROLLERS']))
		{
			$this->arResult['ENTITY_CONTROLLERS'] = [];

			$currencyList = [];
			// TODO: remove to api
			if (Main\Loader::includeModule('currency'))
			{
				$currencyIterator = Currency\CurrencyTable::getList([
					'select' => ['CURRENCY']
				]);
				while ($currency = $currencyIterator->fetch())
				{
					$currencyFormat = \CCurrencyLang::GetFormatDescription($currency['CURRENCY']);
					$currencyList[] = [
						'CURRENCY' => $currency['CURRENCY'],
						'FORMAT' => [
							'FORMAT_STRING' => $currencyFormat['FORMAT_STRING'],
							'DEC_POINT' => $currencyFormat['DEC_POINT'],
							'THOUSANDS_SEP' => $currencyFormat['THOUSANDS_SEP'],
							'DECIMALS' => $currencyFormat['DECIMALS'],
							'THOUSANDS_VARIANT' => $currencyFormat['THOUSANDS_VARIANT'],
							'HIDE_ZERO' => $currencyFormat['HIDE_ZERO']
						]
					];
				}
				unset($currencyFormat, $currency, $currencyIterator);
			}

			$currencyID = CCrmCurrency::GetBaseCurrencyID();
			if(isset($this->entityData['CURRENCY_ID']) && $this->entityData['CURRENCY_ID'] !== '')
			{
				$currencyID = $this->entityData['CURRENCY_ID'] ?? null;
			}

			$this->arResult['ENTITY_CONTROLLERS'][] = [
				'name' => 'PRODUCT_LIST',
				'type' => 'product_list',
				'config' => [
					'productListId' => $this->arResult['PRODUCT_EDITOR_ID'] ?? null,
					'currencyList' => $currencyList,
					'currencyId' => $this->getCurrencyId(),
				]
			];
		}

		return $this->arResult['ENTITY_CONTROLLERS'];
	}

	public function executeComponent()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		$this->includeModules();

		//region Params
		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID']) ? (int)$this->arParams['~ENTITY_ID'] : 0;
		$extras = isset($this->arParams['~EXTRAS']) && is_array($this->arParams['~EXTRAS'])
			? $this->arParams['~EXTRAS'] : array();

		$this->arResult['WAREHOUSE_CRM_TOUR_DATA'] = $this->getWarehouseOnboardTourData();
		$this->arResult['ENABLE_TASK'] = IsModuleInstalled('tasks');
		$this->arResult['ACTION_URI'] = $this->arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;

		$this->arResult['PRODUCT_DATA_FIELD_NAME'] = 'DEAL_PRODUCT_DATA';
		$this->arResult['PRODUCT_EDITOR_ID'] = 'deal_product_editor';

		$this->isEnableRecurring = \Bitrix\Crm\Recurring\Manager::isAllowedExpose(\Bitrix\Crm\Recurring\Manager::DEAL);

		$this->enableSearchHistory = !isset($this->arParams['~ENABLE_SEARCH_HISTORY'])
			|| mb_strtoupper($this->arParams['~ENABLE_SEARCH_HISTORY']) === 'Y';

		$this->defaultFieldValues = array();
		//endregion

		$this->setEntityID($this->arResult['ENTITY_ID']);

		if ($this->entityID > 0 && !\CCrmDeal::Exists($this->entityID))
		{
			Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::EntityNotExist, CCrmOwnerType::Deal);

			return;
		}

		//region Category && Category List
		$categoryReadMap = array_fill_keys(\CCrmDeal::GetPermittedToReadCategoryIDs($this->userPermissions), true);
		$categoryCreateMap = array_fill_keys(\CCrmDeal::GetPermittedToCreateCategoryIDs($this->userPermissions), true);
		$this->arResult['READ_CATEGORY_LIST'] = $this->arResult['CREATE_CATEGORY_LIST'] = array();
		foreach(DealCategory::getAll(true) as $item)
		{
			if (isset($categoryReadMap[$item['ID']]))
			{
				$this->arResult['READ_CATEGORY_LIST'][$item['ID']] = array(
					'NAME' => isset($item['NAME']) ? $item['NAME'] : "[{$item['ID']}]",
					'VALUE' => $item['ID']
				);
			}
			if (isset($categoryCreateMap[$item['ID']]))
			{
				$this->arResult['CREATE_CATEGORY_LIST'][] = array(
					'NAME' => isset($item['NAME']) ? $item['NAME'] : "[{$item['ID']}]",
					'VALUE' => $item['ID']
				);
			}
		}

		$categoryID = -1;
		if(isset($extras['DEAL_CATEGORY_ID']) && $extras['DEAL_CATEGORY_ID'] >= 0)
		{
			$categoryID = (int)$extras['DEAL_CATEGORY_ID'];
		}
		if($categoryID < 0 && $this->entityID > 0)
		{
			$categoryID = \CCrmDeal::GetCategoryID($this->entityID);
		}
		if($categoryID < 0 && isset($this->request['category_id']) && $this->request['category_id'] >= 0)
		{
			$categoryID = (int)$this->request['category_id'];
			if($categoryID > 0 && !Bitrix\Crm\Category\DealCategory::isEnabled($categoryID))
			{
				$categoryID = -1;
			}
		}
		if($this->entityID <= 0)
		{
			//We are in CREATE or COPY mode
			//Check if specified category is permitted
			if($categoryID >= 0 && !isset($categoryCreateMap[$categoryID]))
			{
				$categoryID = -1;
			}

			//Get default category if category is not specified
			if($categoryID < 0 && !empty($categoryCreateMap))
			{
				$categoryID = current(array_keys($categoryCreateMap));
			}
		}

		$this->arResult['CATEGORY_ID'] = $this->categoryID = max($categoryID, 0);
		//endregion

		//region Permissions check
		$this->initializeMode();

		if ($this->isCopyMode)
		{
			if (!\CCrmDeal::CheckReadPermission($this->entityID, $this->userPermissions, $this->categoryID))
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoReadPermission, CCrmOwnerType::Deal);

				return;
			}
			elseif (!\CCrmDeal::CheckCreatePermission($this->userPermissions, $this->categoryID))
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoAddPermission, CCrmOwnerType::Deal);

				return;
			}
		}
		elseif ($this->isExposeMode)
		{
			$dealRecurringData = \Bitrix\Crm\Recurring\Manager::getList(
				array(
					'filter' => array('DEAL_ID' => (int)$this->arResult['ENTITY_ID']),
					'limit' => 1
				),
				\Bitrix\Crm\Recurring\Manager::DEAL
			);
			$recurring = $dealRecurringData->fetch();
			if (!($recurring
				&&\CCrmDeal::CheckReadPermission($this->entityID, $this->userPermissions, $this->categoryID)
				&& \CCrmDeal::CheckCreatePermission($this->userPermissions, $recurring['CATEGORY_ID'])))
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoAddPermission, CCrmOwnerType::Deal);

				return;
			}

			$this->arResult['CATEGORY_ID'] = $this->categoryID = (int)$recurring['CATEGORY_ID'];
		}
		elseif ($this->isEditMode)
		{
			if (
				!\CCrmDeal::CheckUpdatePermission(0)
				&& !\CCrmDeal::CheckReadPermission()
			)
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoAccessToEntityType, CCrmOwnerType::Deal);

				return;
			}
			elseif (
				!\CCrmDeal::CheckUpdatePermission($this->entityID, $this->userPermissions, $this->categoryID)
				&& !\CCrmDeal::CheckReadPermission($this->entityID, $this->userPermissions, $this->categoryID)
			)
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoReadPermission, CCrmOwnerType::Deal);

				return;
			}
		}
		elseif (!\CCrmDeal::CheckCreatePermission($this->userPermissions, $this->categoryID))
		{
			Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoAddPermission, CCrmOwnerType::Deal);

			return;
		}
		//endregion

		//expose recurring region
		if ($this->isExposeMode)
		{
			$recurringInstance = Bitrix\Crm\Recurring\Entity\Deal::getInstance();
			$resultExposing = $recurringInstance->expose(
				['DEAL_ID' => (int)$this->arResult['ENTITY_ID']], 1, false
			);

			if ($resultExposing->isSuccess())
			{
				$exposedData = $resultExposing->getData();
				$this->isEditMode = true;
				$this->arResult['IS_EDIT_MODE'] = true;
				$newId = $exposedData['ID'][0];
				$this->setEntityID($newId);
				$this->arResult['ENTITY_ID'] = $newId;
				$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::DealName.'_'.$newId;
			}
		}
		//endregion

		$this->prepareStageList();

		$this->prepareEntityUserFields();
		$this->prepareEntityUserFieldInfos();

		$this->initializeEditorData();

		if ($this->entityID <= 0 || $this->entityData['IS_RECURRING'] === "Y" || $this->isCopyMode)
		{
			$this->arResult['CAN_CONVERT'] = 0;
		}
		if($this->arResult['CAN_CONVERT'])
		{
			$config = \Bitrix\Crm\Conversion\DealConversionConfig::load();
			if($config === null)
			{
				$config = \Bitrix\Crm\Conversion\DealConversionConfig::getDefault();
			}
			if ($config)
			{
				// hide conversion to smart document from interface
				$config->deleteItemByEntityTypeId(CCrmOwnerType::SmartDocument);
				$config->deleteItemByEntityTypeId(CCrmOwnerType::SmartB2eDocument);
			}

			$this->arResult['CONVERSION_CONFIG'] = $config;

			$entityID = (int)$this->arResult['ENTITY_ID'];

			$this->arResult['CONVERSION_CONTAINER_ID'] = "toolbar_deal_details_{$entityID}_convert_label";
			$this->arResult['CONVERSION_LABEL_ID'] = $this->arResult['CONVERSION_CONTAINER_ID'];
			$this->arResult['CONVERSION_BUTTON_ID'] = "'toolbar_deal_details_{$entityID}_convert_button'";
		}
		else
		{
			$this->arResult['CONVERSION_CONTAINER_ID'] = '';
			$this->arResult['CONVERSION_LABEL_ID'] = $this->arResult['CONVERSION_CONTAINER_ID'];
			$this->arResult['CONVERSION_BUTTON_ID'] = '';
		}

		//region Entity Info
		$isRecurring = isset($this->entityData['IS_RECURRING']) && $this->entityData['IS_RECURRING'] === 'Y';

		$this->arResult['ENTITY_INFO'] = array(
			'ENTITY_ID' => $this->entityID,
			'ENTITY_TYPE_ID' => $isRecurring ? CCrmOwnerType::DealRecurring : CCrmOwnerType::Deal,
			'ENTITY_TYPE_NAME' => $isRecurring ? CCrmOwnerType::DealRecurringName : CCrmOwnerType::DealName,
			'ENTITY_TYPE_CODE' => CCrmOwnerTypeAbbr::Deal,
			'TITLE' => $this->entityData['TITLE'] ?? '',
			'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Deal, $this->entityID),
			'ORDER_LIST' => $this->entityData['ORDER_LIST'] ?? [],
		);
		//endregion

		$progressSemantics = $this->entityData['STAGE_ID']
			? \CCrmDeal::GetStageSemantics($this->entityData['STAGE_ID']) : '';
		$this->arResult['PROGRESS_SEMANTICS'] = $progressSemantics;

		//region Page title
		if($this->isCopyMode)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_DEAL_COPY_PAGE_TITLE'));
		}
		elseif(isset($this->entityData['TITLE']))
		{
			if ($this->entityData['IS_RECURRING'] === "Y")
			{
				$APPLICATION->SetTitle(
					Loc::getMessage(
						"CRM_DEAL_FIELD_RECURRING_TITLE",
						array(
							"#TITLE#" => $this->entityData['TITLE']
						)
					)
				);
			}
			else
			{
				$APPLICATION->SetTitle(htmlspecialcharsbx($this->entityData['TITLE']));
			}
		}
		elseif(!$this->isEditMode)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_DEAL_CREATION_PAGE_TITLE'));
		}
		//endregion

		//region TABS
		if ($this->request->get('active_tab'))
		{
			$this->arResult['ACTIVE_TAB'] = $this->request->get('active_tab');
		}

		$this->arResult['TABS'] = array();
		ob_start();

		$currencyId = $this->getCurrencyId();

		$APPLICATION->IncludeComponent(
			'bitrix:crm.entity.product.list',
			'.default',
			[
				'INTERNAL_FILTER' => [
					'OWNER_ID' => $this->arResult['ENTITY_INFO']['ENTITY_ID'],
					'OWNER_TYPE' => $this->arResult['ENTITY_INFO']['ENTITY_TYPE_CODE']
				],
				'PATH_TO_ENTITY_PRODUCT_LIST' => Crm\Component\EntityDetails\ProductList::getComponentUrl(
					['site' => $this->getSiteId()],
					bitrix_sessid_get()
				),
				'ACTION_URL' => Crm\Component\EntityDetails\ProductList::getLoaderUrl(
					['site' => $this->getSiteId()],
					bitrix_sessid_get()
				),
				'ENTITY_ID' => $this->arResult['ENTITY_INFO']['ENTITY_ID'],
				'ENTITY_TYPE_NAME' => $this->arResult['ENTITY_INFO']['ENTITY_TYPE_NAME'],
				'ENTITY_TITLE' => $this->arResult['ENTITY_INFO']['TITLE'],
				'CUSTOM_SITE_ID' => $this->getSiteId(),
				'CUSTOM_LANGUAGE_ID' => $this->getLanguageId(),
				'ALLOW_EDIT' => (!$this->arResult['READ_ONLY'] ? 'Y' : 'N'),
				'ALLOW_ADD_PRODUCT' => (!$this->arResult['READ_ONLY'] ? 'Y' : 'N'),
				//'ALLOW_CREATE_NEW_PRODUCT' => (!$this->arResult['READ_ONLY'] ? 'Y' : 'N'),

				'ID' => $this->arResult['PRODUCT_EDITOR_ID'],
				'PREFIX' => $this->arResult['PRODUCT_EDITOR_ID'],

				'FORM_ID' => '',

				'PERMISSION_TYPE' => $this->arResult['READ_ONLY'] ? 'READ' : 'WRITE',
				'PERMISSION_ENTITY_TYPE' => $this->arResult['PERMISSION_ENTITY_TYPE'],
				'PERSON_TYPE_ID' => $this->resolvePersonTypeID($this->entityData),
				'CURRENCY_ID' => $currencyId,
				'LOCATION_ID' => $this->isTaxMode && isset($this->entityData['LOCATION_ID']) ? $this->entityData['LOCATION_ID'] : '',
				'CLIENT_SELECTOR_ID' => '', //TODO: Add Client Selector
				'PRODUCTS' => $this->entityData['PRODUCT_ROWS'] ?? null,
				'PRODUCT_DATA_FIELD_NAME' => $this->arResult['PRODUCT_DATA_FIELD_NAME'] ?? '',
				'CATEGORY_ID' => $this->entityData['CATEGORY_ID'] ?? null,
				'BUILDER_CONTEXT' => Crm\Product\Url\ProductBuilder::TYPE_ID,
			],
			false,
			[
				'HIDE_ICONS' => 'Y',
				'ACTIVE_COMPONENT' => 'Y',
			]
		);

		$this->arResult['TABS'][] = array(
			'id' => 'tab_products',
			'name' => Loc::getMessage('CRM_DEAL_TAB_PRODUCTS'),
			'html' => ob_get_clean()
		);

		if (!$isRecurring)
		{
			if($this->entityID > 0)
			{
				$quoteID = isset($this->entityData['QUOTE_ID']) ? (int)$this->entityData['QUOTE_ID'] : 0;
				$tabQuote = null;
				if ($quoteID > 0)
				{
					$quoteDbResult = \CCrmQuote::GetList(
						[],
						[
							'=ID' => $quoteID,
							'CHECK_PERMISSIONS' => 'N',
						],
						false,
						false,
						['TITLE']
					);
					$quoteFields = is_object($quoteDbResult) ? $quoteDbResult->Fetch() : null;
					if (is_array($quoteFields))
					{
						$replace = [
							'#TITLE#' => htmlspecialcharsbx($quoteFields['TITLE']),
							'#URL#' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Quote, $quoteID, false),
						];
						$tabQuote = [
							'id' => 'tab_quote',
							'name' => Loc::getMessage('CRM_DEAL_TAB_QUOTE_MSGVER_1'),
							'html' => '<div class="crm-conv-info">'
								. Loc::getMessage('CRM_DEAL_QUOTE_LINK_MSGVER_1', $replace)
								.'</div>',
						];
					}
				}
				else
				{
					$tabQuote = [
						'id' => 'tab_quote',
						'name' => Loc::getMessage('CRM_DEAL_TAB_QUOTE_MSGVER_1'),
						'loader' => [
							'serviceUrl' => '/bitrix/components/bitrix/crm.quote.list/lazyload.ajax.php?&site'
								.SITE_ID
								.'&'
								.bitrix_sessid_get()
							,
							'componentData' => [
								'template' => '',
								'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
									'QUOTE_COUNT' => '20',
									'PATH_TO_QUOTE_SHOW' => $this->arResult['PATH_TO_QUOTE_SHOW'],
									'PATH_TO_QUOTE_EDIT' => $this->arResult['PATH_TO_QUOTE_EDIT'],
									'INTERNAL_FILTER' => ['DEAL_ID' => $this->entityID],
									'INTERNAL_CONTEXT' => ['DEAL_ID' => $this->entityID],
									'GRID_ID_SUFFIX' => 'DEAL_DETAILS',
									'TAB_ID' => 'tab_quote',
									'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
									'ENABLE_TOOLBAR' => true,
									'PRESERVE_HISTORY' => true,
									'ADD_EVENT_NAME' => 'CrmCreateQuoteFromDeal',
									'ANALYTICS' => [
										// we dont know where from this component was opened from - it could be anywhere on portal
										'c_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_DEAL,
										'c_sub_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_DETAILS,
									],
								], 'crm.quote.list'),
							],
						],
					];
				}

				if ($tabQuote)
				{
					$toolsManager = \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager();
					if (!$toolsManager->checkEntityTypeAvailability(\CCrmOwnerType::Quote))
					{
						$availabilityLock = \Bitrix\Crm\Restriction\AvailabilityManager::getInstance()
							->getEntityTypeAvailabilityLock(\CCrmOwnerType::Quote)
						;
						$tabQuote['availabilityLock'] = $availabilityLock;
					}

					$this->arResult['TABS'][] = $tabQuote;
				}

				if (Crm\Settings\InvoiceSettings::getCurrent()->isOldInvoicesEnabled())
				{
					$tabInvoice = [
						'id' => 'tab_invoice',
						'name' => \CCrmOwnerType::GetCategoryCaption(\CCrmOwnerType::Invoice),
						'loader' => [
							'serviceUrl' => '/bitrix/components/bitrix/crm.invoice.list/lazyload.ajax.php?&site'
								.SITE_ID.
								'&'.
								bitrix_sessid_get(),
							'componentData' => [
								'template' => '',
								'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
									'INVOICE_COUNT' => '20',
									'PATH_TO_COMPANY_SHOW' => $this->arResult['PATH_TO_COMPANY_SHOW'] ?? null,
									'PATH_TO_COMPANY_EDIT' => $this->arResult['PATH_TO_COMPANY_EDIT'] ?? null,
									'PATH_TO_CONTACT_EDIT' => $this->arResult['PATH_TO_CONTACT_EDIT'] ?? null,
									'PATH_TO_DEAL_EDIT' => $this->arResult['PATH_TO_DEAL_EDIT'] ?? null,
									'PATH_TO_INVOICE_EDIT' => $this->arResult['PATH_TO_INVOICE_EDIT'] ?? null,
									'PATH_TO_INVOICE_PAYMENT' => $this->arResult['PATH_TO_INVOICE_PAYMENT'] ?? null,
									'INTERNAL_FILTER' => ['UF_DEAL_ID' => $this->entityID],
									'SUM_PAID_CURRENCY' => $currencyId,
									'GRID_ID_SUFFIX' => 'DEAL_DETAILS',
									'TAB_ID' => 'tab_invoice',
									'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'] ?? null,
									'ENABLE_TOOLBAR' => 'Y',
									'PRESERVE_HISTORY' => true,
									'ADD_EVENT_NAME' => 'CrmCreateInvoiceFromDeal',
									'ANALYTICS' => [
										// we dont know where from this component was opened from - it could be anywhere on portal
										'c_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_DEAL,
										'c_sub_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_DETAILS,
									],
								], 'crm.invoice.list'),
							],
						],
					];

					$toolsManager = \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager();
					if (!$toolsManager->checkEntityTypeAvailability(\CCrmOwnerType::Invoice))
					{
						$availabilityLock = \Bitrix\Crm\Restriction\AvailabilityManager::getInstance()
							->getEntityTypeAvailabilityLock(\CCrmOwnerType::Invoice)
						;
						$tabInvoice['availabilityLock'] = $availabilityLock;
					}

					$this->arResult['TABS'][] = $tabInvoice;
				}
				if (
					CModule::IncludeModule('sale')
					&& CCrmSaleHelper::isWithOrdersMode()
				)
				{
					$this->arResult['TABS'][] = array(
						'id' => 'tab_order',
						'name' => Loc::getMessage('CRM_DEAL_TAB_ORDERS'),
						'loader' => array(
							'serviceUrl' => '/bitrix/components/bitrix/crm.order.list/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
							'componentData' => array(
								'template' => '',
								'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
									'INVOICE_COUNT' => '20',
									'PATH_TO_COMPANY_SHOW' => $this->arResult['PATH_TO_COMPANY_SHOW'] ?? '',
									'PATH_TO_COMPANY_EDIT' => $this->arResult['PATH_TO_COMPANY_EDIT'] ?? '',
									'PATH_TO_CONTACT_EDIT' => $this->arResult['PATH_TO_CONTACT_EDIT'] ?? '',
									'PATH_TO_DEAL_EDIT' => $this->arResult['PATH_TO_DEAL_EDIT'] ?? '',
									'PATH_TO_INVOICE_EDIT' => $this->arResult['PATH_TO_INVOICE_EDIT'] ?? '',
									'PATH_TO_INVOICE_PAYMENT' => $this->arResult['PATH_TO_INVOICE_PAYMENT'] ?? '',
									'INTERNAL_FILTER' => array('ASSOCIATED_DEAL_ID' => $this->entityID),
									'SUM_PAID_CURRENCY' => \CCrmCurrency::GetBaseCurrencyID(),
									'GRID_ID_SUFFIX' => 'DEAL_DETAILS',
									'TAB_ID' => 'tab_order',
									'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'] ?? '',
									// 'ENABLE_TOOLBAR' => 'N',
									'PRESERVE_HISTORY' => true,
									'BUILDER_CONTEXT' => Crm\Product\Url\ProductBuilder::TYPE_ID,
									'ANALYTICS' => [
										// we dont know where from this component was opened from - it could be anywhere on portal
										'c_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_DEAL,
										'c_sub_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_DETAILS,
									],
								], 'crm.order.list')
							)
						)
					);
				}
				if (\Bitrix\Crm\Automation\Factory::isAutomationAvailable(CCrmOwnerType::Deal))
				{
					$robotsTab = [
						'id' => 'tab_automation',
						'name' => Loc::getMessage('CRM_DEAL_TAB_AUTOMATION'),
						'url' => Container::getInstance()->getRouter()
							->getAutomationUrl(CCrmOwnerType::Deal, $this->categoryID)
							->addParams(['id' => $this->entityID]),
					];

					$toolsManager = \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager();
					if (!$toolsManager->checkRobotsAvailability())
					{
						$robotsTab['availabilityLock'] = \Bitrix\Crm\Restriction\AvailabilityManager::getInstance()
							->getRobotsAvailabilityLock()
						;
						$robotsTab['url'] = '';
					}

					$this->arResult['TABS'][] = $robotsTab;
					$checkAutomationTourGuideData = CCrmBizProcHelper::getHowCheckAutomationTourGuideData(
						CCrmOwnerType::Deal,
						$this->categoryID,
						$this->userID
					);
					if ($checkAutomationTourGuideData)
					{
						$this->arResult['AUTOMATION_CHECK_AUTOMATION_TOUR_GUIDE_DATA'] = [
							'options' => $checkAutomationTourGuideData,
						];
					}
					unset($checkAutomationTourGuideData);

				}
				if (Main\Loader::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled())
				{
					$toolsManager = \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager();
					$bizprocAvailabilityLock =
						$toolsManager->checkBizprocAvailability()
							? null
							: \Bitrix\Crm\Restriction\AvailabilityManager::getInstance()->getBizprocAvailabilityLock()
					;

					if (CCrmBizProcHelper::needShowBPTab())
					{
						$bpTab = [
							'id' => 'tab_bizproc',
							'name' => Loc::getMessage('CRM_DEAL_TAB_BIZPROC'),
							'loader' => [
								'serviceUrl' =>
									'/bitrix/components/bitrix/bizproc.document/lazyload.ajax.php?&site='
									. SITE_ID
									. '&'
									. bitrix_sessid_get()
								,
								'componentData' => [
									'template' => 'frame',
									'params' => [
										'MODULE_ID' => 'crm',
										'ENTITY' => 'CCrmDocumentDeal',
										'DOCUMENT_TYPE' => 'DEAL',
										'DOCUMENT_ID' => 'DEAL_'.$this->entityID
									]
								]
							]
						];

						if ($bizprocAvailabilityLock !== null)
						{
							$bpTab['availabilityLock'] = $bizprocAvailabilityLock;
							unset($bpTab['loader']);
						}

						$this->arResult['TABS'][] = $bpTab;
					}

					if ($bizprocAvailabilityLock !== null)
					{
						$this->arResult['BIZPROC_STARTER_DATA'] = ['availabilityLock' => $bizprocAvailabilityLock];
					}
					else
					{
						$this->arResult['BIZPROC_STARTER_DATA'] = [
							'moduleId' => 'crm',
							'entity' => 'CCrmDocumentDeal',
							'documentType' => 'DEAL',
							'documentId' => 'DEAL_' . $this->entityID
						];
					}
				}

				$relationManager = Crm\Service\Container::getInstance()->getRelationManager();
				$this->arResult['TABS'] = array_merge(
					$this->arResult['TABS'],
					$relationManager->getRelationTabsForDynamicChildren(
						\CCrmOwnerType::Deal,
						$this->entityID,
						($this->entityID === 0)
					)
				);

				$this->arResult['TABS'][] = array(
					'id' => 'tab_tree',
					'name' => Loc::getMessage('CRM_DEAL_TAB_TREE'),
					'loader' => array(
						'serviceUrl' => '/bitrix/components/bitrix/crm.entity.tree/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
						'componentData' => array(
							'template' => '.default',
							'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
								'ENTITY_ID' => $this->entityID,
								'ENTITY_TYPE_NAME' => CCrmOwnerType::DealName,
							], 'crm.entity.tree')
						)
					)
				);
				$this->arResult['TABS'][] = $this->getEventTabParams();
				if (CModule::IncludeModule('lists'))
				{
					$listIblock = CLists::getIblockAttachedCrm(CCrmOwnerType::DealName);
					foreach($listIblock as $iblockId => $iblockName)
					{
						$this->arResult['TABS'][] = array(
							'id' => 'tab_lists_'.$iblockId,
							'name' => $iblockName,
							'loader' => array(
								'serviceUrl' => '/bitrix/components/bitrix/lists.element.attached.crm/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get().'',
								'componentData' => array(
									'template' => '',
									'params' => array(
										'ENTITY_ID' => $this->entityID,
										'ENTITY_TYPE' => CCrmOwnerType::Deal,
										'TAB_ID' => 'tab_lists_'.$iblockId,
										'IBLOCK_ID' => $iblockId
									)
								)
							)
						);
					}
				}
			}
			else
			{
				$this->arResult['TABS'][] = array(
					'id' => 'tab_quote',
					'name' => Loc::getMessage('CRM_DEAL_TAB_QUOTE_MSGVER_1'),
					'enabled' => false
				);
				$this->arResult['TABS'][] = array(
					'id' => 'tab_invoice',
					'name' => Loc::getMessage('CRM_DEAL_TAB_INVOICES'),
					'enabled' => false
				);
				if (\Bitrix\Crm\Automation\Factory::isAutomationAvailable(CCrmOwnerType::Deal))
				{
					$this->arResult['TABS'][] = array(
						'id' => 'tab_automation',
						'name' => Loc::getMessage('CRM_DEAL_TAB_AUTOMATION'),
						'enabled' => false
					);
				}
				if (CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled())
				{
					$this->arResult['TABS'][] = array(
						'id' => 'tab_bizproc',
						'name' => Loc::getMessage('CRM_DEAL_TAB_BIZPROC'),
						'enabled' => false
					);
				}
				$this->arResult['TABS'][] = $this->getEventTabParams();
				if (CModule::IncludeModule('lists'))
				{
					$listIblock = CLists::getIblockAttachedCrm(CCrmOwnerType::DealName);
					foreach($listIblock as $iblockId => $iblockName)
					{
						$this->arResult['TABS'][] = array(
							'id' => 'tab_lists_'.$iblockId,
							'name' => $iblockName,
							'enabled' => false
						);
					}
				}
			}
		}
		//endregion

		//region WAIT TARGET DATES
		$this->arResult['WAIT_TARGET_DATES'] = [
			['name' => 'BEGINDATE', 'caption' => \CAllCrmDeal::GetFieldCaption('BEGINDATE')],
			['name' => 'CLOSEDATE', 'caption' => \CAllCrmDeal::GetFieldCaption('CLOSEDATE')],
		];
		if ($this->userType)
		{
			$userFields = $this->userType->GetFields();
			foreach($userFields as $userField)
			{
				if($userField['USER_TYPE_ID'] === 'date' && $userField['MULTIPLE'] !== 'Y')
				{
					$this->arResult['WAIT_TARGET_DATES'][] = [
						'name' => $userField['FIELD_NAME'],
						'caption' => $userField['EDIT_FORM_LABEL'] ?? $userField['FIELD_NAME']
					];
				}
			}
		}
		//endregion

		//region LEGEND
		$categoryID = (int)($this->entityData['CATEGORY_ID'] ?? 0);
		$this->arResult['LEGEND'] = \Bitrix\Crm\Category\DealCategory::getName($categoryID);

		if ($this->arResult['ENTITY_ID'] > 0)
		{
			if (
				isset($this->entityData['IS_RETURN_CUSTOMER'])
				&& $this->entityData['IS_RETURN_CUSTOMER'] === 'Y'
			)
			{
				$this->arResult['LEGEND'] .= ' ('.Loc::getMessage('CRM_DEAL_RETURNING').')';
			}
			elseif(
				isset($this->entityData['IS_REPEATED_APPROACH'])
				&& $this->entityData['IS_REPEATED_APPROACH'] === 'Y'
			)
			{
				$this->arResult['LEGEND'] .= ' ('.Loc::getMessage('CRM_DEAL_REPEATED_APPROACH').')';
			}
		}
		//endregion

		//region VIEW EVENT
		if($this->entityID > 0 && \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isViewEventEnabled())
		{
			CCrmEvent::RegisterViewEvent(CCrmOwnerType::Deal, $this->entityID, $this->userID);
		}
		//endregion

		//region SCORING
		$stageSemanticId = \CCrmDeal::GetSemanticID(
			$this->entityData['STAGE_ID'],
			(isset($this->entityData['CATEGORY_ID']) ? $this->entityData['CATEGORY_ID'] : 0)
		);

		$isStageFinal = Crm\PhaseSemantics::isFinal($stageSemanticId);
		$this->arResult['IS_STAGE_FINAL'] = $isStageFinal;
		if($this->entityID > 0)
		{
			if(!$isStageFinal)
			{
				Crm\Ml\ViewHelper::subscribePredictionUpdate(CCrmOwnerType::Deal, $this->entityID);
			}
			$this->arResult['SCORING'] = Crm\Ml\ViewHelper::prepareData(CCrmOwnerType::Deal, $this->entityID);
		}
		//endregion

		// region AUTOMATION DEBUG
		$activeDebugEntityIds = CCrmBizProcHelper::getActiveDebugEntityIds(CCrmOwnerType::Deal);
		if (in_array($this->entityID, $activeDebugEntityIds))
		{
			$this->arResult['IS_AUTOMATION_DEBUG_ITEM'] = 'Y';
		}
		// endregion

		if ((!$this->isEnableRecurring || !Crm\Ml\Scoring::isEnabled()) && CModule::IncludeModule('bitrix24'))
		{
			CBitrix24::initLicenseInfoPopupJS();
		}

		if(Bitrix\Main\Loader::includeModule('pull'))
		{
			\CPullWatch::Add($this->userID, 'CRM_ENTITY_ORDER');
			\CPullWatch::Add($this->userID, 'CRM_ENTITY_ORDER_PAYMENT');
			\CPullWatch::Add($this->userID, 'CRM_ENTITY_ORDER_SHIPMENT');
			\CPullWatch::Add($this->userID, 'SALE_DELIVERY_SERVICE');
			\CPullWatch::Add($this->userID, 'SALE_DELIVERY_REQUEST');
		}

		$this->includeComponentTemplate();
	}

	public function loadLeadConversionWizard(): void
	{
		if ($this->conversionWizard !== null || $this->leadID === 0)
		{
			return;
		}

		$this->conversionWizard = LeadConversionWizard::load($this->leadID);
	}

	public function loadQuoteConversionWizard(): void
	{
		if ($this->conversionWizard !== null || $this->quoteID === 0)
		{
			return;
		}

		$this->conversionWizard = QuoteConversionWizard::load($this->quoteID);
	}

	public function isSearchHistoryEnabled()
	{
		return $this->enableSearchHistory;
	}
	public function enableSearchHistory($enable)
	{
		$this->enableSearchHistory = (bool)$enable;
	}
	public function getDefaultGuid()
	{
		return "deal_{$this->entityID}_details";
	}
	public function getDefaultConfigID()
	{
		return $this->prepareConfigID();
	}
	public function prepareConfigID($sourceID = '')
	{
		if($sourceID === '')
		{
			$sourceID = 'deal_details';
		}
		return Crm\Category\DealCategory::prepareFormID($this->categoryID, $sourceID, false);
	}
	public function getEntityID()
	{
		return $this->entityID;
	}
	public function setEntityID($entityID)
	{
		$this->entityID = $entityID;
		$this->arResult['ENTITY_ID'] = $this->entityID;

		$this->userFields = null;
		$this->userFieldInfos = null;
	}
	public function getCategoryID()
	{
		return $this->categoryID;
	}
	public function setCategoryID($categoryID)
	{
		$this->categoryID = $categoryID;
	}
	public function prepareFieldInfos()
	{
		if(isset($this->entityFieldInfos))
		{
			return $this->entityFieldInfos;
		}

		//region Recurring Deals
		if (isset($this->entityData['IS_RECURRING']) && $this->entityData['IS_RECURRING'] === 'Y')
		{
			$dbResult = Recurring\Manager::getList(
				array('filter' => array('=DEAL_ID' => $this->entityID)),
				Recurring\Manager::DEAL
			);
			$recurringData = $dbResult->fetch();
			if ($recurringData['NEXT_EXECUTION'] <> '' && $recurringData['ACTIVE'] === 'Y' && $this->isEnableRecurring)
			{
				$recurringViewText =  Loc::getMessage(
					'CRM_DEAL_FIELD_RECURRING_DATE_NEXT_EXECUTION',
					array(
						'#NEXT_DATE#' => $recurringData['NEXT_EXECUTION']
					)
				);
			}
			else
			{
				$recurringViewText = Loc::getMessage('CRM_DEAL_FIELD_RECURRING_NOTHING_SELECTED');
			}
		}
		elseif ($this->entityID > 0)
		{
			$dbResult = Recurring\Manager::getList(
				array(
					'filter' => array('=BASED_ID' => $this->entityID),
					'select' => array('DEAL_ID')
				),
				Recurring\Manager::DEAL
			);

			$recurringLine = "";
			$recurringList = $dbResult->fetchAll();
			$recurringCount = count($recurringList);
			if ($recurringCount === 1)
			{
				$recurringViewText =  Loc::getMessage(
					'CRM_DEAL_FIELD_RECURRING_CREATED_FROM_CURRENT',
					array(
						'#RECURRING_ID#' => $recurringList[0]['DEAL_ID']
					)
				);
			}
			elseif ($recurringCount > 1)
			{
				foreach ($recurringList as $item)
				{
					$recurringLine .= Loc::getMessage('CRM_DEAL_FIELD_NUM_SIGN', array("#DEAL_ID#" => $item['DEAL_ID'])).", ";
				}

				if ($recurringLine <> '')
				{
					$recurringLine = mb_substr($recurringLine, 0, -2);
					$recurringViewText =  Loc::getMessage(
						'CRM_DEAL_FIELD_RECURRING_CREATED_MANY_FROM_CURRENT',
						array(
							'#RECURRING_LIST#' => $recurringLine
						)
					);
				}
			}
		}

		if (empty($recurringViewText) && empty($this->arResult['CREATE_CATEGORY_LIST']) )
		{
			$recurringViewText  =  Loc::getMessage("CRM_DEAL_FIELD_RECURRING_RESTRICTED");
		}
		if (empty($recurringViewText))
		{
			$recurringViewText  =  Loc::getMessage("CRM_DEAL_FIELD_RECURRING_NOTHING_SELECTED");
		}
		if (!$this->isEnableRecurring)
		{
			$dealRecurringRestriction = RestrictionManager::getDealRecurringRestriction();
		}
		//endregion

		$allStages = Bitrix\Crm\Category\DealCategory::getStageList($this->categoryID);
		$prohibitedStageIDS = array();
		foreach(array_keys($allStages) as $stageID)
		{
			if(isset($this->arResult['READ_ONLY']) && $this->arResult['READ_ONLY'])
			{
				$prohibitedStageIDS[] = $stageID;
			}
			else
			{
				$permissionType = $this->isEditMode
					? \CCrmDeal::GetStageUpdatePermissionType($stageID, $this->userPermissions, $this->categoryID)
					: \CCrmDeal::GetStageCreatePermissionType($stageID, $this->userPermissions, $this->categoryID);

				if($permissionType == BX_CRM_PERM_NONE)
				{
					$prohibitedStageIDS[] = $stageID;
				}
			}
		}

		$observersRestriction = RestrictionManager::getObserversRestriction();

		$fakeValue = '';
		$categoryParams = CCrmComponentHelper::getEntityClientFieldCategoryParams(
			CCrmOwnerType::Deal,
			$this->categoryID
		);
		$this->entityFieldInfos = array(
			array(
				'name' => 'ID',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_ID'),
				'type' => 'text',
				'editable' => false,
				'enableAttributes' => false,
				'mergeable' => false,
			),
			array(
				'name' => 'DATE_CREATE',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_DATE_CREATE'),
				'type' => 'datetime',
				'editable' => false,
				'enableAttributes' => false,
				'mergeable' => false
			),
			array(
				'name' => 'DATE_MODIFY',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_DATE_MODIFY'),
				'type' => 'datetime',
				'editable' => false,
				'enableAttributes' => false,
				'mergeable' => false
			),
			array(
				'name' => 'TITLE',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_TITLE'),
				'type' => 'text',
				'isHeading' => true,
				'visibilityPolicy' => 'edit',
				'placeholders' => array('creation' => \CCrmDeal::GetDefaultTitle()),
				'required' => false,
				'editable' => true
			),
			array(
				'name' => 'TYPE_ID',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_TYPE_ID'),
				'type' => 'list',
				'editable' => true,
				'data' => [
					'items' => \CCrmInstantEditorHelper::PrepareListOptions(
						$this->prepareTypeList(),
						[
							'NOT_SELECTED' => Loc::getMessage('CRM_DEAL_SOURCE_NOT_SELECTED'),
							'NOT_SELECTED_VALUE' => $fakeValue
						]
					),
					'defaultValue' => $this->defaultEntityData['TYPE_ID'] ?? null,
					'innerConfig' => \CCrmInstantEditorHelper::prepareInnerConfig(
						'crm_status',
						'crm.status.setItems',
						'DEAL_TYPE',
						[$fakeValue]
					),
				]
			),
			array(
				'name' => 'SOURCE_ID',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_SOURCE_ID'),
				'type' => 'list',
				'editable' => true,
				'data' => [
					'items'=> \CCrmInstantEditorHelper::PrepareListOptions(
						CCrmStatus::GetStatusList('SOURCE'),
						[
							'NOT_SELECTED' => Loc::getMessage('CRM_DEAL_SOURCE_NOT_SELECTED'),
							'NOT_SELECTED_VALUE' => $fakeValue
						]
					),
					'innerConfig' => \CCrmInstantEditorHelper::prepareInnerConfig(
						'crm_status',
						'crm.status.setItems',
						'SOURCE',
						[$fakeValue]
					),
				]
			),
			array(
				'name' => 'SOURCE_DESCRIPTION',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_SOURCE_DESCRIPTION'),
				'type' => 'text',
				'data' => array('lineCount' => 6),
				'editable' => true
			),
			array(
				'name' => 'STAGE_ID',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_STAGE_ID'),
				'type' => 'list',
				'editable' => ($this->entityData['IS_RECURRING'] ?? 'N') !== "Y",
				'enableAttributes' => false,
				'mergeable' => false,
				'data' => array(
					'items' => \CCrmInstantEditorHelper::PrepareListOptions(
						$allStages,
						array('EXCLUDE_FROM_EDIT' => $prohibitedStageIDS)
					)
				)
			),
			array(
				'name' => 'OPPORTUNITY_WITH_CURRENCY',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_OPPORTUNITY_WITH_CURRENCY'),
				'type' => ((($this->entityData['IS_RECURRING'] ?? 'N') !== 'Y') && Main\Loader::includeModule('salescenter'))
					? 'moneyPay' : 'money',
				'editable' => true,
				'mergeable' => false,
				'data' => [
					'affectedFields' => [
						'CURRENCY_ID',
						'OPPORTUNITY',
					],
					'currency' => [
						'name' => 'CURRENCY_ID',
						'items' => \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems()),
					],
					'amount' => 'OPPORTUNITY',
					'formatted' => 'FORMATTED_OPPORTUNITY',
					'formattedWithCurrency' => 'FORMATTED_OPPORTUNITY_WITH_CURRENCY',
					'isDeliveryAvailable' => Crm\Integration\SalesCenterManager::getInstance()->hasInstallableDeliveryItems(),
					'isTerminalAvailable' => Crm\Terminal\AvailabilityManager::getInstance()->isAvailable(),
					'disableSendButton' => Crm\Integration\SmsManager::canSendMessage() ? '' : 'y',
					'isShowPaymentDocuments' => ($this->entityData['IS_RECURRING'] ?? 'N') !== 'Y',
					'isWithOrdersMode' => \CCrmSaleHelper::isWithOrdersMode(),
					'isOnecMode' => $this->isCatalogModuleIncluded
						? Catalog\Store\EnableWizard\Manager::isOnecMode()
						: false
					,
					'isInventoryManagementToolEnabled' => $this->isCatalogModuleIncluded
						? \Bitrix\Catalog\Restriction\ToolAvailabilityManager::getInstance()->checkInventoryManagementAvailability()
						: false
					,
					'isTerminalToolEnabled' => Container::getInstance()->getIntranetToolsManager()->checkTerminalAvailability(),
					'isSalescenterToolEnabled' => $this->isSalescenterModuleIncluded
						? \Bitrix\Salescenter\Restriction\ToolAvailabilityManager::getInstance()->checkSalescenterAvailability()
						: false
					,
					'shouldShowCashboxChecks' => Main\Application::getInstance()->getLicense()->getRegion() === 'ru',
				],
			),
			array(
				'name' => 'CLOSEDATE',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_CLOSEDATE'),
				'type' => 'datetime',
				'editable' => true,
				'data' => [
					'enableTime' => false,
					'defaultValue' => $this->defaultEntityData['CLOSEDATE'] ?? null,
				],
			),
			array(
				'name' => 'BEGINDATE',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_BEGINDATE'),
				'type' => 'datetime',
				'editable' => true,
				'data' => [
					'enableTime' => false,
					'defaultValue' => $this->defaultEntityData['BEGINDATE'] ?? null,
				],
			),
			array(
				"name" => "PROBABILITY",
				"title" => Loc::getMessage("CRM_DEAL_FIELD_PROBABILITY"),
				"type" => "number",
				"editable" => true
			),
			array(
				"name" => "OPENED",
				"title" => Loc::getMessage("CRM_DEAL_FIELD_OPENED"),
				"type" => "boolean",
				"editable" => true
			),
			Crm\Entity\CommentsHelper::compileFieldDescriptionForDetails(
				\CCrmOwnerType::Deal,
				'COMMENTS',
				$this->entityID,
			),
			array(
				'name' => 'CLIENT',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_CLIENT'),
				'type' => 'client_light',
				'editable' => true,
				'data' => array(
					'affectedFields' => ['CLIENT_INFO'],
					'compound' => array(
						array(
							'name' => 'COMPANY_ID',
							'type' => 'company',
							'entityTypeName' => \CCrmOwnerType::CompanyName,
							'tagName' => \CCrmOwnerType::CompanyName
						),
						array(
							'name' => 'CONTACT_IDS',
							'type' => 'multiple_contact',
							'entityTypeName' => \CCrmOwnerType::ContactName,
							'tagName' => \CCrmOwnerType::ContactName
						)
					),
					'categoryParams' => $categoryParams,
					'map' => array('data' => 'CLIENT_DATA'),
					'info' => 'CLIENT_INFO',
					'lastCompanyInfos' => 'LAST_COMPANY_INFOS',
					'lastContactInfos' => 'LAST_CONTACT_INFOS',
					'loaders' => array(
						'primary' => array(
							CCrmOwnerType::CompanyName => array(
								'action' => 'GET_CLIENT_INFO',
								'url' => '/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get()
							),
							CCrmOwnerType::ContactName => array(
								'action' => 'GET_CLIENT_INFO',
								'url' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get()
							)
						),
						'secondary' => array(
							CCrmOwnerType::CompanyName => array(
								'action' => 'GET_SECONDARY_ENTITY_INFOS',
								'url' => '/bitrix/components/bitrix/crm.deal.edit/ajax.php?'.bitrix_sessid_get()
							)
						)
					),
					'clientEditorFieldsParams' => CCrmComponentHelper::prepareClientEditorFieldsParams(
						['categoryParams' => $categoryParams]
					),
					'useExternalRequisiteBinding' => true,
					'duplicateControl' => CCrmComponentHelper::prepareClientEditorDuplicateControlParams(
						['entityTypes' => [CCrmOwnerType::Company, CCrmOwnerType::Contact]]
					),
				)
			),
			array(
				'name' => 'ASSIGNED_BY_ID',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_ASSIGNED_BY_ID'),
				'type' => 'user',
				'editable' => true,
				'data' => array(
					'enableEditInView' => true,
					'formated' => 'ASSIGNED_BY_FORMATTED_NAME',
					'position' => 'ASSIGNED_BY_WORK_POSITION',
					'photoUrl' => 'ASSIGNED_BY_PHOTO_URL',
					'showUrl' => 'PATH_TO_ASSIGNED_BY_USER',
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE'] ?? null

				),
				'enableAttributes' => false
			),
			array(
				'name' => 'OBSERVER',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_OBSERVERS'),
				'type' => 'multiple_user',
				'editable' => true,
				'data' => array(
					'enableEditInView' => true,
					'map' => array('data' => 'OBSERVER_IDS'),
					'infos' => 'OBSERVER_INFOS',
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE'] ?? null,
					'messages' => array('addObserver' => Loc::getMessage('CRM_DEAL_FIELD_ADD_OBSERVER')),
					'restriction' => [
						'isRestricted' => !$observersRestriction->hasPermission(),
						'action' => $observersRestriction->prepareInfoHelperScript(),
					],
				)
			),
			EditorAdapter::getProductRowSummaryField(
				Loc::getMessage("CRM_DEAL_FIELD_PRODUCTS"),
				"PRODUCT_ROW_SUMMARY"
			),
			array(
				"name" => "RECURRING",
				"title" => Loc::getMessage("CRM_DEAL_SECTION_RECURRING"),
				"type" => "recurring",
				"editable" => isset($this->arResult['CREATE_CATEGORY_LIST'])
								&& is_array($this->arResult['CREATE_CATEGORY_LIST'])
								&& count($this->arResult['CREATE_CATEGORY_LIST']) > 0,
				"transferable" => false,
				'enableAttributes' => false,
				"enableRecurring" => $this->isEnableRecurring,
				"elements" => $this->prepareRecurringElements(),
				"data" => array(
					'loaders' => array(
						'action' => 'GET_DEAL_HINT',
						'url' => '/bitrix/components/bitrix/crm.interface.form.recurring/ajax.php?'.bitrix_sessid_get()
					),
					"view" => array(
						'text' => $recurringViewText
					),
					"fieldData" => [
						'MULTIPLE_EXECUTION' => Recurring\Manager::MULTIPLY_EXECUTION,
						'SINGLE_EXECUTION' => Recurring\Manager::SINGLE_EXECUTION,
						'NON_ACTIVE' => Recurring\Calculator::SALE_TYPE_NON_ACTIVE_DATE,
					],
					"restrictScript" => (!$this->isEnableRecurring && !empty($dealRecurringRestriction)) ? $dealRecurringRestriction->prepareInfoHelperScript() : ""
				)
			),
		);

		Tracking\UI\Details::appendEntityFields($this->entityFieldInfos);
		$this->entityFieldInfos[] = array(
			'name' => 'UTM',
			'title' => Loc::getMessage('CRM_DEAL_FIELD_UTM'),
			'type' => 'custom',
			'data' => array('view' => 'UTM_VIEW_HTML'),
			'editable' => false,
			'enableAttributes' => false
		);

		//region WAITING FOR LOCATION SUPPORT
		/*
		if($this->isTaxMode)
		{
			$this->entityFieldInfos[] = array(
				'name' => 'LOCATION_ID',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_LOCATION_ID'),
				'type' => 'custom',
				'data' => array(
					'edit' => 'LOCATION_EDIT_HTML',
					'view' => 'LOCATION_VIEW_HTML'
				),
				'editable' => true
			);
		}
		*/
		//endregion

		$this->entityFieldInfos = array_merge(
			$this->entityFieldInfos,
			array_values($this->prepareEntityUserFieldInfos())
		);

		$this->entityFieldInfos = array_merge(
			$this->entityFieldInfos,
			array_values($this->prepareParentFieldInfos())
		);

		$this->arResult['ENTITY_FIELDS'] = $this->entityFieldInfos;

		return $this->entityFieldInfos;
	}

	protected function prepareParentFieldInfos(): array
	{
		if ($this->parentFieldInfos === null)
		{
			$this->parentFieldInfos = [];
			if ($this->editorAdapter)
			{
				$this->parentFieldInfos = $this->editorAdapter->getParentFieldsInfo(\CCrmOwnerType::Deal);
			}
		}

		return $this->parentFieldInfos;
	}

	protected function getOrderList() : array
	{
		$data = Crm\Order\EntityBinding::getList([
			'select' => ['ORDER_ID'],
			'filter' => [
				'=OWNER_ID' => $this->getEntityID(),
				'=OWNER_TYPE_ID' => CCrmOwnerType::Deal
			]
		])->fetchAll();

		return $data ?? [];
	}

	public function prepareEntityDataScheme()
	{
		if($this->entityDataScheme === null)
		{
			$this->entityDataScheme = \CCrmDeal::GetFieldsInfo();
			$this->userType->PrepareFieldsInfo($this->entityDataScheme);
		}
		return $this->entityDataScheme;
	}
	public function prepareValidators()
	{
		if(isset($this->arResult['ENTITY_VALIDATORS']))
		{
			return $this->arResult['ENTITY_VALIDATORS'];
		}

		$this->arResult['ENTITY_VALIDATORS'] = [
			[
				'type' => 'trackingSource',
				'data' => ['fieldName' => Tracking\UI\Details::SourceId]
			]
		];
		return $this->arResult['ENTITY_VALIDATORS'];
	}
	public function prepareEntityUserFields()
	{
		if($this->userFields === null)
		{
			$this->userFields = $this->userType->GetEntityFields($this->entityID);
		}
		return $this->userFields;
	}
	public function prepareEntityFieldAttributeConfigs()
	{
		if(!$this->entityFieldAttributeConfig)
		{
			$this->entityFieldAttributeConfig = FieldAttributeManager::getEntityConfigurations(
				CCrmOwnerType::Deal,
				FieldAttributeManager::resolveEntityScope(
					CCrmOwnerType::Deal,
					$this->entityID,
					array('CATEGORY_ID' => $this->categoryID)
				)
			);
		}
		return $this->entityFieldAttributeConfig;
	}
	public function prepareEntityUserFieldInfos()
	{
		if($this->userFieldInfos !== null)
		{
			return $this->userFieldInfos;
		}

		$this->userFieldInfos = array();
		$userFields = $this->prepareEntityUserFields();
		$enumerationFields = array();

		$visibilityConfig = $this->prepareEntityFieldvisibilityConfigs(CCrmOwnerType::Deal);

		foreach($userFields as $userField)
		{
			$fieldName = $userField['FIELD_NAME'];
			$fieldInfo = array(
				'USER_TYPE_ID' => $userField['USER_TYPE_ID'],
				'ENTITY_ID' => $this->userFieldEntityID,
				'ENTITY_VALUE_ID' => $this->entityID,
				'FIELD' => $fieldName,
				'MULTIPLE' => $userField['MULTIPLE'],
				'MANDATORY' => $userField['MANDATORY'],
				'SETTINGS' => isset($userField['SETTINGS']) ? $userField['SETTINGS'] : null
			);

			if($userField['USER_TYPE_ID'] === 'enumeration')
			{
				$enumerationFields[$fieldName] = $userField;
			}
			elseif($userField['USER_TYPE_ID'] === 'file')
			{
				$fieldInfo['ADDITIONAL'] = array(
					'URL_TEMPLATE' => \CComponentEngine::MakePathFromTemplate(
						$this->getFileUrlTemplate(),
						array(
							'owner_id' => $this->entityID,
							'field_name' => $fieldName
						)
					)
				);
			}

			$data = ['fieldInfo' => $fieldInfo];

			if ($userField['USER_TYPE_ID'] === 'crm_status')
			{
				if (
					is_array($userField['SETTINGS'])
					&& isset($userField['SETTINGS']['ENTITY_TYPE'])
					&& is_string($userField['SETTINGS']['ENTITY_TYPE'])
					&& $userField['SETTINGS']['ENTITY_TYPE'] !== ''
				)
				{
					$data['innerConfig'] = \CCrmInstantEditorHelper::prepareInnerConfig(
						$userField['USER_TYPE_ID'],
						'crm.status.setItems',
						$userField['SETTINGS']['ENTITY_TYPE'],
						['']
					);
				}
				unset($statusEntityId);
			}

			if(isset($visibilityConfig[$fieldName]))
			{
				$data['visibilityConfigs'] = $visibilityConfig[$fieldName];
			}

			$this->userFieldInfos[$fieldName] = array(
				'name' => $fieldName,
				'title' => isset($userField['EDIT_FORM_LABEL']) ? $userField['EDIT_FORM_LABEL'] : $fieldName,
				'type' => 'userField',
				'data' => $data
			);

			if(isset($userField['MANDATORY']) && $userField['MANDATORY'] === 'Y')
			{
				$this->userFieldInfos[$fieldName]['required'] = true;
			}
		}

		if(!empty($enumerationFields))
		{
			$enumInfos = \CCrmUserType::PrepareEnumerationInfos($enumerationFields);
			foreach($enumInfos as $fieldName => $enums)
			{
				if(isset($this->userFieldInfos[$fieldName])
					&& isset($this->userFieldInfos[$fieldName]['data'])
					&& isset($this->userFieldInfos[$fieldName]['data']['fieldInfo'])
				)
				{
					$this->userFieldInfos[$fieldName]['data']['fieldInfo']['ENUM'] = $enums;
				}
			}
		}

		return $this->userFieldInfos;
	}

	protected function isFieldHasDefaultValueAttribute(array $fieldsInfo, string $fieldName): bool
	{
		return (
			isset($fieldsInfo[$fieldName]['ATTRIBUTES'])
			&& in_array(
				CCrmFieldInfoAttr::HasDefaultValue,
				$fieldsInfo[$fieldName]['ATTRIBUTES'],
				true
			));
	}

	protected function isSetDefaultValueForField(
		array $fieldsInfo,
		array $requiredFields,
		string $fieldName
	): bool
	{
		// if field is not required and has an attribute
		return (
			!in_array($fieldName, $requiredFields, true)
			&& $this->isFieldHasDefaultValueAttribute($fieldsInfo, $fieldName)
		);
	}

	public function prepareEntityData()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		if($this->entityData)
		{
			return $this->entityData;
		}

		$this->prepareEntityUserFields();
		$this->prepareEntityUserFieldInfos();

		$isTrackingFieldRequired = false;

		if($this->conversionWizard !== null)
		{
			$this->entityData = array();
			$mappedUserFields = array();
			\Bitrix\Crm\Entity\EntityEditor::prepareConvesionMap(
				$this->conversionWizard,
				CCrmOwnerType::Deal,
				$this->entityData,
				$mappedUserFields
			);

			if(isset($this->entityData['CONTACT_IDS']) && !empty($this->entityData['CONTACT_IDS']))
			{
				$this->entityData['CONTACT_BINDINGS'] = \Bitrix\Crm\Binding\EntityBinding::prepareEntityBindings(
					CCrmOwnerType::Contact,
					$this->entityData['CONTACT_IDS']
				);
			}

			foreach($mappedUserFields as $k => $v)
			{
				if(isset($this->userFields[$k]))
				{
					$this->userFields[$k]['VALUE'] = $v;
				}
			}

			if(!isset($this->entityData['CURRENCY_ID']) || $this->entityData['CURRENCY_ID'] === '')
			{
				$this->entityData['CURRENCY_ID'] = \CCrmCurrency::GetBaseCurrencyID();
			}

			$this->entityData['OPENED'] = \Bitrix\Crm\Settings\DealSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
		}
		elseif($this->entityID <= 0)
		{
			$requiredFields = Crm\Attribute\FieldAttributeManager::isEnabled()
				? Crm\Attribute\FieldAttributeManager::getRequiredFields(
					CCrmOwnerType::Deal,
					$this->entityID,
					['TYPE_ID', 'BEGINDATE', 'CLOSEDATE', Tracking\UI\Details::SourceId],
					Crm\Attribute\FieldOrigin::SYSTEM
				)
				: [];
			$isTrackingFieldRequired = in_array(Tracking\UI\Details::SourceId, $requiredFields, true);
			$fieldsInfo = CCrmDeal::GetFieldsInfo();
			$this->entityData = [];
			//region Default Dates
			$beginDate = time() + \CTimeZone::GetOffset();
			$time = localtime($beginDate, true);
			$beginDate -= $time['tm_sec'] + 60 * $time['tm_min'] + 3600 * $time['tm_hour'];

			if($this->isFieldHasDefaultValueAttribute($fieldsInfo, 'BEGINDATE'))
			{
				$this->arResult['FIELDS_SET_DEFAULT_VALUE'][] = 'BEGINDATE';
				$this->defaultEntityData['BEGINDATE'] = ConvertTimeStamp($beginDate, 'SHORT', SITE_ID);;
				if($this->isSetDefaultValueForField($fieldsInfo, $requiredFields, 'BEGINDATE'))
				{
					$this->entityData['BEGINDATE'] = $this->defaultEntityData['BEGINDATE'];
				}
			}
			if($this->isFieldHasDefaultValueAttribute($fieldsInfo, 'CLOSEDATE'))
			{
				$this->arResult['FIELDS_SET_DEFAULT_VALUE'][] = 'CLOSEDATE';
				$this->defaultEntityData['CLOSEDATE'] = ConvertTimeStamp($beginDate + 7 * 86400, 'SHORT', SITE_ID);
				if($this->isSetDefaultValueForField($fieldsInfo, $requiredFields, 'CLOSEDATE'))
				{
					$this->entityData['CLOSEDATE'] = $this->defaultEntityData['CLOSEDATE'];
				}
			}
			//endregion
			//leave OPPORTUNITY unassigned
			//$this->entityData['OPPORTUNITY'] = 0.0;
			$this->entityData['CURRENCY_ID'] = \CCrmCurrency::GetBaseCurrencyID();
			$this->entityData['OPENED'] = \Bitrix\Crm\Settings\DealSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
			//$this->entityData['CLOSED'] = 'N';

			//region Default Responsible
			if($this->userID > 0)
			{
				$this->entityData['ASSIGNED_BY_ID'] = $this->userID;
			}
			//endregion

			$this->entityData['IS_MANUAL_OPPORTUNITY'] = 'N';
			$this->entityData['IS_PAY_BUTTON_CONTROL_VISIBLE'] = 'N';

			//region Default Stage ID
			$stageList = $this->prepareStageList();
			if(!empty($stageList))
			{
				$requestStageId = $this->request->get('stage_id');
				if (isset($stageList[$requestStageId]))
				{
					$this->entityData['STAGE_ID'] = $requestStageId;
				}
				else
				{
					$this->entityData['STAGE_ID'] = current(array_keys($stageList));
				}
			}
			//endregion

			// set first option by default if the field is not required
			$typeList = $this->prepareTypeList();
			if(
				!empty($typeList)
				&& $this->isFieldHasDefaultValueAttribute($fieldsInfo, 'TYPE_ID'))
			{
				$this->arResult['FIELDS_SET_DEFAULT_VALUE'][] = 'TYPE_ID';
				$this->defaultEntityData['TYPE_ID'] = current(array_keys($typeList));
				if($this->isSetDefaultValueForField($fieldsInfo, $requiredFields, 'TYPE_ID'))
				{
					$this->entityData['TYPE_ID'] = $this->defaultEntityData['TYPE_ID'];
				}
			}

			$this->arResult['INITIAL_DATA'] =
				isset($this->arParams['~INITIAL_DATA']) && is_array($this->arParams['~INITIAL_DATA'])
					? $this->arParams['~INITIAL_DATA']
					: [];

			if (!empty($this->arResult['DEFAULT_CONTACT_ID']))
			{
				$this->arResult['INITIAL_DATA']['CONTACT_ID'] = $this->arResult['DEFAULT_CONTACT_ID'];
			}

			if(isset($this->arResult['INITIAL_DATA']) && !empty($this->arResult['INITIAL_DATA']))
			{
				\Bitrix\Crm\Entity\EntityEditor::mapData(
					$this->prepareEntityDataScheme(),
					$this->entityData,
					$this->userFields,
					$this->arResult['INITIAL_DATA']
				);
			}

			$requestData = \Bitrix\Crm\Entity\EntityEditor::mapRequestData(
				$this->prepareEntityDataScheme(),
				$this->entityData,
				$this->userFields
			);

			if(isset($this->entityData['COMPANY_ID']) && !isset($this->entityData['CONTACT_ID']))
			{
				$contactIDs = static::prepareLastBoundEntityIDs(
					CCrmOwnerType::Contact,
					CCrmOwnerType::Deal,
					array(
						'userID' => $this->userID,
						'userPermissions' => $this->userPermissions,
						'companyID' => $this->entityData['COMPANY_ID']
					)
				);

				if(!empty($contactIDs))
				{
					$this->entityData['CONTACT_BINDINGS'] = \Bitrix\Crm\Binding\EntityBinding::prepareEntityBindings(
						CCrmOwnerType::Contact,
						$contactIDs
					);
				}
			}

			//Save request data as initial data for restore it if according controls are not enabled in settings (please see ajax.php)
			if(!empty($requestData))
			{
				if(!isset($this->arResult['INITIAL_DATA']))
				{
					$this->arResult['INITIAL_DATA'] = array();
				}
				$this->arResult['INITIAL_DATA'] = array_merge($this->arResult['INITIAL_DATA'], $requestData);
			}

			if ($this->categoryID > 0)
			{
				$this->entityData['CATEGORY_ID'] = $this->categoryID;
			}
		}
		else
		{
			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('=ID' => $this->entityID, 'CHECK_PERMISSIONS' => 'N')
			);

			if(is_object($dbResult))
			{
				$this->entityData = $dbResult->Fetch();
				if (is_null($this->entityData['OPPORTUNITY']))
				{
					$this->entityData['OPPORTUNITY'] = 0.0;
				}
			}

			if(!is_array($this->entityData))
			{
				$this->entityData = array();
			}

			if(isset($this->arResult['INITIAL_DATA']) && !empty($this->arResult['INITIAL_DATA']))
			{
				\Bitrix\Crm\Entity\EntityEditor::mapData(
					$this->prepareEntityDataScheme(),
					$this->entityData,
					$this->userFields,
					$this->arResult['INITIAL_DATA']
				);
			}

			if(isset($this->entityData['CATEGORY_ID']))
			{
				$this->arResult['CATEGORY_ID'] = $this->categoryID = (int)$this->entityData['CATEGORY_ID'];
			}

			if(!isset($this->entityData['CURRENCY_ID']) || $this->entityData['CURRENCY_ID'] === '')
			{
				$this->entityData['CURRENCY_ID'] = \CCrmCurrency::GetBaseCurrencyID();
			}

			$this->entityData['ORDER_LIST'] = $this->getOrderList();

			//region UTM
			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:crm.utm.entity.view',
				'',
				array('FIELDS' => $this->entityData),
				false,
				array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
			);
			$this->entityData['UTM_VIEW_HTML'] = ob_get_contents();
			ob_end_clean();
			//endregion

			//region WAITING FOR LOCATION SUPPORT
			/*
			if($this->isTaxMode)
			{
				$locationID = isset($this->entityData['LOCATION_ID']) ? $this->entityData['LOCATION_ID'] : '';
				ob_start();
				\CSaleLocation::proxySaleAjaxLocationsComponent(
					array(
						'AJAX_CALL' => 'N',
						'COUNTRY_INPUT_NAME' => 'LOC_COUNTRY',
						'REGION_INPUT_NAME' => 'LOC_REGION',
						'CITY_INPUT_NAME' => 'LOC_CITY',
						'CITY_OUT_LOCATION' => 'Y',
						'LOCATION_VALUE' => $locationID,
						'ORDER_PROPS_ID' => "DEAL_{$this->entityID}",
						'ONCITYCHANGE' => 'CrmProductRowSetLocation',
						'SHOW_QUICK_CHOOSE' => 'N'
					),
					array(
						"CODE" => $locationID,
						"ID" => "",
						"PROVIDE_LINK_BY" => "code",
						"JS_CALLBACK" => 'CrmProductRowSetLocation'
					),
					'popup'
				);
				$locationHtml = ob_get_contents();
				ob_end_clean();

				$this->entityData['LOCATION_EDIT_HTML'] = $locationHtml;
				$this->entityData['LOCATION_VIEW_HTML'] = '';
			}
			*/
			//region Default Responsible and Stage ID for copy mode
			if($this->isCopyMode)
			{
				if($this->userID > 0)
				{
					$this->entityData['ASSIGNED_BY_ID'] = $this->userID;
				}

				$stageList = $this->prepareStageList();
				if(!empty($stageList))
				{
					$this->entityData['STAGE_ID'] = current(array_keys($stageList));
				}
			}
			//endregion

			//region Observers
			$this->entityData['OBSERVER_IDS'] = Crm\Observer\ObserverManager::getEntityObserverIDs(
				CCrmOwnerType::Deal,
				$this->entityID
			);
			//endregion

			$this->entityData = Crm\Entity\CommentsHelper::prepareFieldsFromDetailsToView(
				\CCrmOwnerType::Deal,
				$this->entityID,
				$this->entityData,
			);
		}

		//region Responsible
		if(isset($this->entityData['ASSIGNED_BY_ID']) && $this->entityData['ASSIGNED_BY_ID'] > 0)
		{
			$dbUsers = \CUser::GetList(
				'ID',
				'ASC',
				array('ID' => $this->entityData['ASSIGNED_BY_ID']),
				array(
					'FIELDS' => array(
						'ID',  'LOGIN', 'PERSONAL_PHOTO',
						'NAME', 'SECOND_NAME', 'LAST_NAME'
					)
				)
			);
			$user = is_object($dbUsers) ? $dbUsers->Fetch() : null;
			if(is_array($user))
			{
				$this->entityData['ASSIGNED_BY_LOGIN'] = $user['LOGIN'];
				$this->entityData['ASSIGNED_BY_NAME'] = isset($user['NAME']) ? $user['NAME'] : '';
				$this->entityData['ASSIGNED_BY_SECOND_NAME'] = isset($user['SECOND_NAME']) ? $user['SECOND_NAME'] : '';
				$this->entityData['ASSIGNED_BY_LAST_NAME'] = isset($user['LAST_NAME']) ? $user['LAST_NAME'] : '';
				$this->entityData['ASSIGNED_BY_PERSONAL_PHOTO'] = isset($user['PERSONAL_PHOTO']) ? $user['PERSONAL_PHOTO'] : '';
			}
		}
		//endregion

		if(isset($this->entityData['CATEGORY_ID']))
		{
			$this->entityData['CATEGORY_NAME'] = Bitrix\Crm\Category\DealCategory::getName($this->entityData['CATEGORY_ID']);
		}

		//region User Fields
		foreach($this->userFields as $fieldName => $userField)
		{
			$fieldValue = isset($userField['VALUE']) ? $userField['VALUE'] : '';
			$fieldData = isset($this->userFieldInfos[$fieldName])
				? $this->userFieldInfos[$fieldName] : null;

			if(!is_array($fieldData))
			{
				continue;
			}

			$isEmptyField = true;
			$fieldParams = $fieldData['data']['fieldInfo'];
			if((is_string($fieldValue) && $fieldValue !== '')
				|| (is_array($fieldValue) && !empty($fieldValue))
			)
			{
				$fieldParams['VALUE'] = $fieldValue;
				$isEmptyField = false;
			}

			$fieldSignature = $this->userFieldDispatcher->getSignature($fieldParams);
			if($isEmptyField)
			{
				$this->entityData[$fieldName] = array(
					'SIGNATURE' => $fieldSignature,
					'IS_EMPTY' => true
				);
			}
			else
			{
				$this->entityData[$fieldName] = array(
					'VALUE' => $fieldValue,
					'SIGNATURE' => $fieldSignature,
					'IS_EMPTY' => false
				);

				if($fieldData['data']['fieldInfo']['USER_TYPE_ID'] === 'file')
				{
					$values = is_array($fieldValue) ? $fieldValue : array($fieldValue);
					$this->entityData[$fieldName]['EXTRAS'] = array(
						'OWNER_TOKEN' => \CCrmFileProxy::PrepareOwnerToken(array_fill_keys($values, $this->entityID))
					);
				}
			}
		}
		//endregion
		//region Opportunity & Currency
		$this->entityData['FORMATTED_OPPORTUNITY_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['OPPORTUNITY'] ?? null,
			$this->entityData['CURRENCY_ID'],
			''
		);
		$this->entityData['FORMATTED_OPPORTUNITY_ACCOUNT_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['OPPORTUNITY_ACCOUNT'] ?? null,
			$this->entityData['ACCOUNT_CURRENCY_ID'] ?? null,
			''
		);
		$this->entityData['FORMATTED_OPPORTUNITY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['OPPORTUNITY'] ?? null,
			$this->entityData['CURRENCY_ID'] ?? null,
			'#'
		);
		//endregion
		//region Responsible
		$assignedByID = isset($this->entityData['ASSIGNED_BY_ID']) ? (int)$this->entityData['ASSIGNED_BY_ID'] : 0;
		if($assignedByID > 0)
		{
			$this->entityData['ASSIGNED_BY_FORMATTED_NAME'] =
				\CUser::FormatName(
					$this->arResult['NAME_TEMPLATE'],
					array(
						'LOGIN' => $this->entityData['ASSIGNED_BY_LOGIN'],
						'NAME' => $this->entityData['ASSIGNED_BY_NAME'],
						'LAST_NAME' => $this->entityData['ASSIGNED_BY_LAST_NAME'],
						'SECOND_NAME' => $this->entityData['ASSIGNED_BY_SECOND_NAME']
					),
					true,
					false
				);

			$assignedByPhotoID = isset($this->entityData['ASSIGNED_BY_PERSONAL_PHOTO'])
				? (int)$this->entityData['ASSIGNED_BY_PERSONAL_PHOTO'] : 0;

			if($assignedByPhotoID > 0)
			{
				$file = new CFile();
				$fileInfo = $file->ResizeImageGet(
					$assignedByPhotoID,
					array('width' => 60, 'height'=> 60),
					BX_RESIZE_IMAGE_EXACT
				);
				if(is_array($fileInfo) && isset($fileInfo['src']))
				{
					$this->entityData['ASSIGNED_BY_PHOTO_URL'] = $fileInfo['src'];
				}
			}

			$this->entityData['PATH_TO_ASSIGNED_BY_USER'] = CComponentEngine::MakePathFromTemplate(
				$this->arResult['PATH_TO_USER_PROFILE'] ?? null,
				array('user_id' => $assignedByID)
			);
		}
		//endregion
		//region Observers
		if(isset($this->entityData['OBSERVER_IDS']) && !empty($this->entityData['OBSERVER_IDS']))
		{
			$userBroker = Container::getInstance()->getUserBroker();

			$users = $userBroker->getBunchByIds($this->entityData['OBSERVER_IDS']);

			$this->entityData['OBSERVER_INFOS'] = [];
			foreach ($users as $singleUser)
			{
				$this->entityData['OBSERVER_INFOS'][] = [
					'ID' => $singleUser['ID'],
					'FORMATTED_NAME' => $singleUser['FORMATTED_NAME'],
					'WORK_POSITION' => $singleUser['WORK_POSITION'] ?? '',
					'SHOW_URL' => (string)$singleUser['SHOW_URL'],
					'PHOTO_URL' => $singleUser['PHOTO_URL'],
				];
			}
		}
		//endregion

		//region Client Data & Multifield Data
		$clientInfo = array();

		$companyID = isset($this->entityData['COMPANY_ID']) ? (int)$this->entityData['COMPANY_ID'] : 0;
		if($companyID > 0)
		{
			\CCrmComponentHelper::prepareMultifieldData(
				\CCrmOwnerType::Company,
				[$companyID],
				[
					'PHONE',
					'EMAIL',
					'IM',
				],
				$this->entityData
			);

			$isEntityReadPermitted = \CCrmCompany::CheckReadPermission($companyID, $this->userPermissions);
			$companyInfo = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::CompanyName,
				$companyID,
				array(
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'USER_PERMISSIONS' => $this->userPermissions,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_EDIT_REQUISITE_DATA' => true,
					'REQUIRE_MULTIFIELDS' => true,
					'NORMALIZE_MULTIFIELDS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
				)
			);

			$clientInfo['COMPANY_DATA'] = array($companyInfo);
		}

		$contactBindings = array();
		if($this->entityID > 0)
		{
			$contactBindings = \Bitrix\Crm\Binding\DealContactTable::getDealBindings($this->entityID);
		}
		elseif(isset($this->entityData['CONTACT_BINDINGS']) && is_array($this->entityData['CONTACT_BINDINGS']))
		{
			$contactBindings = $this->entityData['CONTACT_BINDINGS'];
		}
		elseif(isset($this->entityData['CONTACT_ID']) && $this->entityData['CONTACT_ID'] > 0)
		{
			$contactBindings = \Bitrix\Crm\Binding\EntityBinding::prepareEntityBindings(
				CCrmOwnerType::Contact,
				array($this->entityData['CONTACT_ID'])
			);
		}

		$contactIDs = \Bitrix\Crm\Binding\EntityBinding::prepareEntityIDs(\CCrmOwnerType::Contact, $contactBindings);
		$clientInfo['CONTACT_DATA'] = array();
		$iteration= 0;

		\CCrmComponentHelper::prepareMultifieldData(
			\CCrmOwnerType::Contact,
			$contactIDs,
			[
				'PHONE',
				'EMAIL',
				'IM',
			],
			$this->entityData
		);
		foreach($contactIDs as $contactID)
		{
			$isEntityReadPermitted = CCrmContact::CheckReadPermission($contactID, $this->userPermissions);
			$clientInfo['CONTACT_DATA'][] = CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::ContactName,
				$contactID,
				array(
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'USER_PERMISSIONS' => $this->userPermissions,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_EDIT_REQUISITE_DATA' => ($iteration === 0), // load full requisite data for first item only (due to performance optimisation)
					'REQUIRE_MULTIFIELDS' => true,
					'NORMALIZE_MULTIFIELDS' => true,
					'REQUIRE_BINDINGS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
				)
			);
			$iteration++;
		}
		$this->entityData['CLIENT_INFO'] = $clientInfo;

		if ($this->enableSearchHistory)
		{
			$categoryParams = CCrmComponentHelper::getEntityClientFieldCategoryParams(
				CCrmOwnerType::Deal,
				$this->categoryID
			);
			$this->entityData['LAST_COMPANY_INFOS'] = SearchAction::prepareSearchResultsJson(
				Crm\Controller\Entity::getRecentlyUsedItems(
					'crm.deal.details',
					'company',
					[
						'EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Company,
						'EXPAND_CATEGORY_ID' => $categoryParams[CCrmOwnerType::Company]['categoryId'],
					]
				)
			);
			$this->entityData['LAST_CONTACT_INFOS'] = SearchAction::prepareSearchResultsJson(
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

		//region Requisites
		$this->entityData['REQUISITE_BINDING'] = array();

		$requisiteEntityList = array();
		$requisite = new \Bitrix\Crm\EntityRequisite();
		$requisiteEntityList[] = array('ENTITY_TYPE_ID' => CCrmOwnerType::Deal, 'ENTITY_ID' => $this->entityID);
		if(isset($this->entityData['COMPANY_ID']) && $this->entityData['COMPANY_ID'] > 0)
		{
			$requisiteEntityList[] = array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
				'ENTITY_ID' => $this->entityData['COMPANY_ID']
			);
		}
		if(!empty($contactBindings))
		{
			$primaryBoundEntityID = \Bitrix\Crm\Binding\EntityBinding::getPrimaryEntityID(
				CCrmOwnerType::Contact,
				$contactBindings
			);
			if($primaryBoundEntityID > 0)
			{
				$requisiteEntityList[] = array(
					'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
					'ENTITY_ID' => $primaryBoundEntityID
				);
			}
		}

		$requisiteLinkInfo = $requisite->getDefaultRequisiteInfoLinked($requisiteEntityList);
		if (is_array($requisiteLinkInfo))
		{
			/* requisiteLinkInfo contains following fields: REQUISITE_ID, BANK_DETAIL_ID */
			$this->entityData['REQUISITE_BINDING'] = $requisiteLinkInfo;
		}
		//endregion
		//endregion

		//region Product row

		$this->entityData["PRODUCT_ROW_SUMMARY"] = $this->getProductRowSummaryData();

		//endregion
		//region Recurring Deals
		if($this->entityID > 0 && $this->entityData['IS_RECURRING'] === 'Y' && $this->isEnableRecurring)
		{
			$dbResult = Recurring\Manager::getList(
				array('filter' => array('=DEAL_ID' => $this->entityID)),
				Recurring\Manager::DEAL
			);
			$recurringData = $dbResult->fetch();
			if(is_array($recurringData))
			{
				$recurringParams = $recurringData['PARAMS'];
				if (isset($recurringParams['EXECUTION_TYPE']) && !isset($recurringParams['MODE']))
				{
					$recurringParams['MODE'] = $recurringParams['EXECUTION_TYPE'];
				}
				if ($recurringData['ACTIVE'] === 'N')
				{
					$recurringParams['MODE'] = Recurring\Calculator::SALE_TYPE_NON_ACTIVE_DATE;
				}
				if (isset($recurringParams['PERIOD_DEAL']) && !isset($recurringParams['MULTIPLE_TYPE']))
				{
					$recurringParams['MULTIPLE_TYPE'] = $recurringParams['PERIOD_DEAL'];
				}
				if (isset($recurringParams['DEAL_TYPE_BEFORE']) && !isset($recurringParams['SINGLE_TYPE']))
				{
					$recurringParams['SINGLE_TYPE'] = $recurringParams['DEAL_TYPE_BEFORE'];
				}
				if (isset($recurringParams['DEAL_COUNT_BEFORE']) && !isset($recurringParams['SINGLE_INTERVAL_VALUE']))
				{
					$recurringParams['SINGLE_INTERVAL_VALUE'] = $recurringParams['DEAL_COUNT_BEFORE'];
				}
				$recurringParams['SINGLE_INTERVAL_VALUE'] = (int)$recurringParams['SINGLE_INTERVAL_VALUE'];
				$singleDateBefore = null;
				if (isset($recurringParams['DEAL_DATEPICKER_BEFORE']) && !isset($recurringParams['SINGLE_DATE_BEFORE']))
				{
					$recurringParams['SINGLE_DATE_BEFORE'] = $recurringParams['DEAL_DATEPICKER_BEFORE'];
				}
				if (CheckDateTime($recurringParams['SINGLE_DATE_BEFORE']))
				{
					$singleDateBefore = $recurringParams['SINGLE_DATE_BEFORE'];
				}
				$recurringParams['SINGLE_DATE_BEFORE']  = new \Bitrix\Main\Type\Date($singleDateBefore);
				if (isset($recurringParams['REPEAT_TILL']) && !isset($recurringParams['MULTIPLE_TYPE_LIMIT']))
				{
					$recurringParams['MULTIPLE_TYPE_LIMIT'] = $recurringParams['REPEAT_TILL'];
				}
				$dateLimit = null;
				if (isset($recurringParams['END_DATE']) && !isset($recurringParams['MULTIPLE_DATE_LIMIT']))
				{
					$recurringParams['MULTIPLE_DATE_LIMIT'] = $recurringParams['END_DATE'];
				}
				if (CheckDateTime($recurringParams['MULTIPLE_DATE_LIMIT']))
				{
					$dateLimit = $recurringParams['MULTIPLE_DATE_LIMIT'];
				}
				$recurringParams['MULTIPLE_DATE_LIMIT']  = new \Bitrix\Main\Type\Date($dateLimit);
				if (isset($recurringParams['LIMIT_REPEAT']) && !isset($recurringParams['MULTIPLE_TIMES_LIMIT']))
				{
					$recurringParams['MULTIPLE_TIMES_LIMIT'] = $recurringParams['LIMIT_REPEAT'];
				}
				$startDateValue = null;
				if (CheckDateTime($recurringParams['MULTIPLE_DATE_START']))
				{
					$startDateValue = $recurringParams['MULTIPLE_DATE_START'];
				}
				$recurringParams['MULTIPLE_DATE_START'] = new \Bitrix\Main\Type\Date($startDateValue);
				$recurringParams['MULTIPLE_CUSTOM_INTERVAL_VALUE'] = (int)$recurringParams['MULTIPLE_CUSTOM_INTERVAL_VALUE'];
				$recurringParams['OFFSET_BEGINDATE_VALUE'] = (int)$recurringParams['OFFSET_BEGINDATE_VALUE'];
				$recurringParams['OFFSET_CLOSEDATE_VALUE'] = (int)$recurringParams['OFFSET_CLOSEDATE_VALUE'];

				$selectDateTypeFields = ['MULTIPLE_TYPE', 'MULTIPLE_CUSTOM_TYPE', 'SINGLE_TYPE', 'OFFSET_BEGINDATE_TYPE', 'OFFSET_CLOSEDATE_TYPE'];
				foreach ($selectDateTypeFields as $code)
				{
					if ((int)$recurringParams[$code] <= 0)
					{
						$recurringParams[$code] = Recurring\Calculator::SALE_TYPE_DAY_OFFSET;
					}
				}

				if (isset($recurringData['CATEGORY_ID']) || (int)$recurringData['CATEGORY_ID'] > 0)
				{
					$recurringParams['CATEGORY_ID'] = $recurringData['CATEGORY_ID'];
				}
				else
				{
					$recurringParams['CATEGORY_ID'] = $this->arResult['CATEGORY_ID'];
				}

				foreach ($recurringParams as $name => $value)
				{
					$changedName = "RECURRING[{$name}]";
					$this->entityData['RECURRING'][$changedName] = $value;
				}
			}
		}
		else
		{
			$today = new \Bitrix\Main\Type\Date();
			$recurringParams = [
				'RECURRING[MODE]' => Recurring\Calculator::SALE_TYPE_NON_ACTIVE_DATE,
				'RECURRING[SINGLE_TYPE]' => Recurring\Calculator::SALE_TYPE_DAY_OFFSET,
				'RECURRING[SINGLE_INTERVAL_VALUE]' => 0,
				'RECURRING[SINGLE_DATE_BEFORE]' => $today,
				'RECURRING[MULTIPLE_TYPE]' => Recurring\Calculator::SALE_TYPE_DAY_OFFSET,
				'RECURRING[MULTIPLE_CUSTOM_TYPE]' => Recurring\Calculator::SALE_TYPE_DAY_OFFSET,
				'RECURRING[MULTIPLE_CUSTOM_INTERVAL_VALUE]' => 1,
				'RECURRING[BEGINDATE_TYPE]' => Recurring\Entity\Deal::SETTED_FIELD_VALUE,
				'RECURRING[OFFSET_BEGINDATE_VALUE]' => 0,
				'RECURRING[OFFSET_BEGINDATE_TYPE]' => Recurring\Calculator::SALE_TYPE_DAY_OFFSET,
				'RECURRING[CLOSEDATE_TYPE]' => Recurring\Entity\Deal::SETTED_FIELD_VALUE,
				'RECURRING[OFFSET_CLOSEDATE_VALUE]' => 0,
				'RECURRING[OFFSET_CLOSEDATE_TYPE]' => Recurring\Calculator::SALE_TYPE_DAY_OFFSET,
				'RECURRING[MULTIPLE_DATE_START]' => $today,
				'RECURRING[MULTIPLE_DATE_LIMIT]' => $today,
				'RECURRING[MULTIPLE_TIMES_LIMIT]' => 1,
				'RECURRING[CATEGORY_ID]' => $this->arResult['CATEGORY_ID'] ?? null
			];
			$this->entityData['RECURRING'] = $recurringParams;
			$this->entityData = array_merge($this->entityData, $recurringParams);
		}
		//endregion

		$isUsedInventoryManagement = false;
		$salesOrderRights = [];
		if ($this->isCatalogModuleIncluded)
		{
			$isUsedInventoryManagement = \Bitrix\Catalog\Config\State::isEnabledInventoryManagement();
			$actionController = AccessController::getCurrent();
			$rightMap = [
				'view' => ActionDictionary::ACTION_STORE_DOCUMENT_VIEW,
				'modify' => ActionDictionary::ACTION_STORE_DOCUMENT_MODIFY,
				'conduct' => ActionDictionary::ACTION_STORE_DOCUMENT_CONDUCT,
				'cancel' => ActionDictionary::ACTION_STORE_DOCUMENT_CANCEL,
				'delete' => ActionDictionary::ACTION_STORE_DOCUMENT_DELETE,
			];

			foreach ($rightMap as $code => $action)
			{
				$salesOrderRights[$code] = $actionController->checkByValue(
					$action,
					\Bitrix\Catalog\StoreDocumentTable::TYPE_SALES_ORDERS
				);
			}
		}

		$this->entityData['IS_USED_INVENTORY_MANAGEMENT'] = $isUsedInventoryManagement;
		$this->entityData['IS_ONEC_MODE'] = $this->isCatalogModuleIncluded && Catalog\Store\EnableWizard\Manager::isOnecMode();
		$this->entityData['SALES_ORDERS_RIGHTS'] = $salesOrderRights;
		$this->entityData['IS_INVENTORY_MANAGEMENT_RESTRICTED'] = !\Bitrix\Crm\Restriction\RestrictionManager::getInventoryControlIntegrationRestriction()->hasPermission();
		$this->entityData['IS_1C_PLAN_RESTRICTED'] = !\Bitrix\Crm\Restriction\RestrictionManager::getInventoryControl1cRestriction()->hasPermission();
		$this->entityData['IS_INVENTORY_MANAGEMENT_TOOL_ENABLED'] =
			$this->isCatalogModuleIncluded
			&& \Bitrix\Catalog\Restriction\ToolAvailabilityManager::getInstance()->checkInventoryManagementAvailability()
		;
		$this->entityData['IS_SALESCENTER_TOOL_ENABLED'] =
			$this->isSalescenterModuleIncluded
			&& \Bitrix\Salescenter\Restriction\ToolAvailabilityManager::getInstance()->checkSalescenterAvailability()
		;
		$this->entityData['MODE_WITH_ORDERS'] = \CCrmSaleHelper::isWithOrdersMode();
		$this->entityData['IS_COPY_MODE'] = $this->isCopyMode;
		$this->entityData['RECEIVE_PAYMENT_MODE'] = CUserOptions::GetOption('crm', 'receive_payment_mode', 'payment_delivery');
		$this->entityData['IS_TERMINAL_AVAILABLE'] = Crm\Terminal\AvailabilityManager::getInstance()->isAvailable();
		$this->entityData['IS_TERMINAL_TOOL_ENABLED'] = Container::getInstance()->getIntranetToolsManager()->checkTerminalAvailability();

		$this->entityData['IS_PHONE_CONFIRMED'] = $this->isSalescenterModuleIncluded && LandingManager::getInstance()->isPhoneConfirmed();
		$this->entityData['CONNECTED_SITE_ID'] = $this->isSalescenterModuleIncluded ? LandingManager::getInstance()->getConnectedSiteId() : 0;

		Tracking\UI\Details::prepareEntityData(
			\CCrmOwnerType::Deal,
			$this->entityID,
			$this->entityData,
			$isTrackingFieldRequired
		);

		if ($this->editorAdapter)
		{
			$parentElements = [];
			if ($this->entityID > 0)
			{
				$relationManager = Container::getInstance()->getRelationManager();
				$parentElements = $relationManager->getParentElements(
					new Crm\ItemIdentifier(\CCrmOwnerType::Deal, $this->entityID)
				);
			}
			else
			{
				$parentItem = ParentFieldManager::tryParseParentItemFromRequest($this->request);
				if ($parentItem)
				{
					$parentElements = [$parentItem];
				}
			}

			if (!empty($parentElements))
			{
				$this->editorAdapter->addParentFieldsEntityData(
					$parentElements,
					$this->prepareParentFieldInfos(),
					$this->entityData
				);
			}
		}

		$this->arResult['ENTITY_DATA'] = $this->entityData;

		return $this->entityData;
	}

	protected function getProductRowSummaryData(): array
	{
		$productRowSummaryData = [];

		if ($this->factory && $this->editorAdapter)
		{
			$entityId = $this->entityID;
			$item = null;
			if($this->conversionWizard !== null)
			{
				$entityId = $this->conversionWizard->converter->getEntityID();
				if($entityId > 0)
				{
					$entityTypeId = $this->conversionWizard->converter->getEntityTypeID();
					$item = Container::getInstance()->getFactory($entityTypeId)->getItem($entityId);
				}
			}
			else if($entityId > 0)
			{
				$item = $this->factory->getItem($entityId);
			}

			if(!$item)
			{
				$item = $this->factory->createItem();
			}


			$mode = $this->getComponentMode();
			$productRowSummaryData = $this->editorAdapter->getProductRowSummaryDataByItem($item, $mode);
		}
		else
		{
			$productRowCount = 0;
			$productRowInfos = [];
			if ($this->entityID > 0)
			{
				$productRows = \CCrmProductRow::LoadRows(\CCrmOwnerTypeAbbr::Deal, $this->entityID);
				foreach ($productRows as $productRow)
				{
					$productRowCount++;
					if ($productRowCount <= 10)
					{
						$productRowInfos[] = EditorAdapter::formProductRowData(
							Crm\ProductRow::createFromArray($productRow),
							$this->entityData['CURRENCY_ID'],
							true
						);
					}
				}

				$calculateOptions = [];
				if ($this->isTaxMode)
				{
					$calculateOptions['ALLOW_LD_TAX'] = 'Y';
					$calculateOptions['LOCATION_ID'] = $this->entityData['LOCATION_ID'] ?? '';
				}

				$result = CCrmSaleHelper::Calculate(
					$productRows,
					$this->entityData['CURRENCY_ID'],
					$this->resolvePersonTypeID($this->entityData),
					false,
					SITE_ID,
					$calculateOptions
				);

				$priceAmount = isset($result['PRICE']) ? round((double)$result['PRICE'], 2) : 0.0;
				$productRowSummaryData = [
					'count' => $productRowCount,
					'total' => CCrmCurrency::MoneyToString($priceAmount, $this->entityData['CURRENCY_ID']),
					'totalRaw' => [
						'amount' => $priceAmount,
						'currency' => $this->entityData['CURRENCY_ID'],
					],
					'items' => $productRowInfos,
				];
			}
			$productRowSummaryData['isReadOnly'] = $this->arResult['READ_ONLY'] ?? true;
		}

		return $productRowSummaryData;
	}

	protected function getComponentMode(): int
	{
		if ($this->isEditMode)
		{
			return ComponentMode::MODIFICATION;
		}

		if ($this->isCopyMode)
		{
			return ComponentMode::COPING;
		}

		return ComponentMode::VIEW;
	}

	protected function prepareTypeList()
	{
		if($this->types === null)
		{
			$this->types = \CCrmStatus::GetStatusList('DEAL_TYPE');
		}
		return $this->types;
	}
	protected function prepareStageList()
	{
		if($this->stages === null)
		{
			$this->stages = array();
			$allStages = Bitrix\Crm\Category\DealCategory::getStageList($this->categoryID);
			foreach ($allStages as $stageID => $stageTitle)
			{
				$permissionType = $this->isEditMode
					? \CCrmDeal::GetStageUpdatePermissionType($stageID, $this->userPermissions, $this->categoryID)
					: \CCrmDeal::GetStageCreatePermissionType($stageID, $this->userPermissions, $this->categoryID);

				if ($permissionType > BX_CRM_PERM_NONE)
				{
					$this->stages[$stageID] = $stageTitle;
				}
			}
		}
		return $this->stages;
	}

	public function prepareEntityFieldAttributes()
	{
		if($this->entityFieldInfos === null)
		{
			return;
		}

		$requiredByAttributesFieldNames = FieldAttributeManager::prepareEditorFieldInfosWithAttributes(
			$this->prepareEntityFieldAttributeConfigs(),
			$this->entityFieldInfos
		);

		$this->arResult['ENTITY_FIELDS'] = $this->entityFieldInfos;

		//region Update entity data
		// This block allows in the component crm.entity.editor to determine the presence of mandatory
		if (!empty($requiredByAttributesFieldNames))
		{
			$entityFieldInfoMap = [];
			for($i = 0, $length = count($this->entityFieldInfos); $i < $length; $i++)
			{
				$entityFieldInfoMap[$this->entityFieldInfos[$i]['name']] = $i;
			}

			foreach ($requiredByAttributesFieldNames as $fieldName)
			{
				if ($this->isEntityFieldHasEmpyValue($this->entityFieldInfos[$entityFieldInfoMap[$fieldName]]))
				{
					$this->entityData['EMPTY_REQUIRED_SYSTEM_FIELD_MAP'][$fieldName] = true;
				}
			}

			$this->arResult['ENTITY_DATA'] = $this->entityData;
		}
	}

	protected function isEntityFieldHasEmpyValue($fieldInfo)
	{
		$result = false;
		$isResultReady = false;

		if (is_array($fieldInfo) && isset($fieldInfo['name'])
			&& is_string($fieldInfo['name']) && $fieldInfo['name'] !== '')
		{
			$fieldName = $fieldInfo['name'];
			$fieldType = $fieldInfo['type'] ?? '';

			if ($fieldType === 'userField')
			{
				$fieldInfo = $fieldInfo['data']['fieldInfo'] ?? [];

				if(isset($fieldInfo['USER_TYPE_ID']) && $fieldInfo['USER_TYPE_ID'] === 'boolean')
				{
					$isResultReady = true;
				}
			}

			if (!$isResultReady
				&& isset($this->entityData[$fieldName]['IS_EMPTY'])
				&& is_array($this->entityData[$fieldName])
				&& $this->entityData[$fieldName]['IS_EMPTY']
			)
			{
				$result = true;
				$isResultReady = true;
			}

			if (!$isResultReady)
			{
				$fieldsToCheck = [
					'TITLE',                             // text
					'OPPORTUNITY_WITH_CURRENCY',         // money
					'BEGINDATE',                         // datetime
					'CLOSEDATE',                         // datetime
					'CLIENT',                            // client_light
					Tracking\UI\Details::SourceId,       // custom
					'PROBABILITY',                       // number
					'TYPE_ID',                           // list
					'SOURCE_ID',                         // list
					'SOURCE_DESCRIPTION',                // text
					'OBSERVER',                          // multiple_user
					'COMMENTS'                           // html or bb
				];
				if (in_array($fieldName, $fieldsToCheck, true))
				{
					switch ($fieldType)
					{
						case 'text':
						case 'html':
						case 'bb':
						case 'list':
							if (array_key_exists($fieldName, $this->entityData)
								&& (!is_string($this->entityData[$fieldName])
									|| $this->entityData[$fieldName] === ''))
							{
								$result = true;
								$isResultReady = true;
							}
							break;
						case 'number':
							if (array_key_exists($fieldName, $this->entityData)
								&& $this->entityData[$fieldName] == 0)
							{
								$result = true;
								$isResultReady = true;
							}
							break;
						case 'money':
							if ($fieldName === 'OPPORTUNITY_WITH_CURRENCY')
							{
								$dataFieldName = 'OPPORTUNITY';
								if (array_key_exists($dataFieldName, $this->entityData)
									&& empty($this->entityData[$dataFieldName]))
								{
									$result = true;
									$isResultReady = true;
								}
							}
							break;
						case 'datetime':
							if (array_key_exists($fieldName, $this->entityData)
								&& ($this->entityData[$fieldName] === null
									|| $this->entityData[$fieldName] === ''
									|| !is_string($this->entityData[$fieldName])))
							{
								$result = true;
								$isResultReady = true;
							}
							break;
						case 'client_light':
							if ($fieldName === 'CLIENT')
							{
								if (is_array($this->entityData['CLIENT_INFO'])
									&& (!is_array($this->entityData['CLIENT_INFO']['COMPANY_DATA'])
										|| empty($this->entityData['CLIENT_INFO']['COMPANY_DATA']))
									&& (!is_array($this->entityData['CLIENT_INFO']['CONTACT_DATA'])
										|| empty($this->entityData['CLIENT_INFO']['CONTACT_DATA'])))
								{
									$result = true;
									$isResultReady = true;
								}
							}
							break;
						case 'multiple_user':
							if ($fieldName === 'OBSERVER')
							{
								$dataFieldName = 'OBSERVER_IDS';
								if (array_key_exists($dataFieldName, $this->entityData)
									&& (!is_array($this->entityData[$dataFieldName])
										|| empty($this->entityData[$dataFieldName])))
								{
									$result = true;
									$isResultReady = true;
								}
							}
							break;
						case 'custom':
							if ($fieldName === Tracking\UI\Details::SourceId)
							{
								if (array_key_exists($fieldName, $this->entityData)
									&& ($this->entityData[$fieldName] === null
										|| $this->entityData[$fieldName] < 0))
								{
									$result = true;
									$isResultReady = true;
								}
							}
							break;
					}
				}
			}
		}

		return $result;
	}
	protected function prepareRecurringElements()
	{
		if (!$this->isEnableRecurring || (($this->arResult['READ_ONLY'] ?? null) === true))
		{
			return [];
		}
		$data = [
			[
				'name' => 'RECURRING[MODE]',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_RECURRING'),
				'type' => 'list',
				'editable' => true,
				'enableAttributes' => false,
				'enabledMenu' => false,
				'data' => array(
					'items' => [
						[
							'VALUE' => Recurring\Calculator::SALE_TYPE_NON_ACTIVE_DATE,
							'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_NOT_REPEAT")
						],
						[
							'VALUE' => Recurring\Manager::MULTIPLY_EXECUTION,
							'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_MANY_TIMES")
						],
						[
							'VALUE' => Recurring\Manager::SINGLE_EXECUTION,
							'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_ONCE_TIME")
						]
					]
				),
			],
			[
				'name' => 'SINGLE_PARAMS',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_RECURRING_SINGLE_TITLE'),
				'type' => 'recurring_single_row',
				'editable' => true,
				'enableAttributes' => false,
				'enabledMenu' => false,
				'data' => array(
					'select' => array(
						'name' => 'RECURRING[SINGLE_TYPE]',
						'items' => [
							[
								'VALUE' => Recurring\Calculator::SALE_TYPE_DAY_OFFSET,
								'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_SINGLE_TYPE_DAY")
							],
							[
								'VALUE' => Recurring\Calculator::SALE_TYPE_WEEK_OFFSET,
								'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_SINGLE_TYPE_WEEK")
							],
							[
								'VALUE' => Recurring\Calculator::SALE_TYPE_MONTH_OFFSET,
								'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_SINGLE_TYPE_MONTH")
							]
						]
					),
					'amount' => 'RECURRING[SINGLE_INTERVAL_VALUE]',
					'date' => 'RECURRING[SINGLE_DATE_BEFORE]',
				),
			],
			[
				"name" => "MULTIPLE_PARAMS",
				"type" => "recurring",
				"editable" => isset($this->arResult['CREATE_CATEGORY_LIST'])
					&& is_array($this->arResult['CREATE_CATEGORY_LIST'])
					&& count($this->arResult['CREATE_CATEGORY_LIST']) > 0,
				"transferable" => false,
				'enableAttributes' => false,
				"enableRecurring" => $this->isEnableRecurring,
				"elements" => [
					[
						'name' => 'RECURRING[MULTIPLE_TYPE]',
						'title' => Loc::getMessage('CRM_DEAL_FIELD_RECURRING_MUTLTIPLE_PERIOD_TITLE'),
						'type' => 'list',
						'editable' => true,
						'enableAttributes' => false,
						'enabledMenu' => false,
						'data' => array(
							'items' => [
								[
									'VALUE' => Recurring\Calculator::SALE_TYPE_DAY_OFFSET,
									'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_EVERYDAY")
								],
								[
									'VALUE' => Recurring\Calculator::SALE_TYPE_WEEK_OFFSET,
									'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_EVERY_WEEK")
								],
								[
									'VALUE' => Recurring\Calculator::SALE_TYPE_MONTH_OFFSET,
									'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_EVERY_MONTH")
								],
								[
									'VALUE' => Recurring\Calculator::SALE_TYPE_YEAR_OFFSET,
									'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_EVERY_YEAR")
								],
								[
									'VALUE' => Recurring\Calculator::SALE_TYPE_CUSTOM_OFFSET,
									'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_CUSTOM_INTERVAL")
								],
							]
						),
					],
					[
						'name' => 'MULTIPLE_CUSTOM',
						'title' => Loc::getMessage('CRM_DEAL_FIELD_RECURRING_CUSTOM_INTERVAL_TITLE'),
						'type' => 'recurring_custom_row',
						'editable' => true,
						'enableAttributes' => false,
						'enabledMenu' => false,
						'data' => array(
							'select' => array(
								'name' => 'RECURRING[MULTIPLE_CUSTOM_TYPE]',
								'items' => [
									[
										'VALUE' => Recurring\Calculator::SALE_TYPE_DAY_OFFSET,
										'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_CUSTOM_DAY")
									],
									[
										'VALUE' => Recurring\Calculator::SALE_TYPE_WEEK_OFFSET,
										'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_CUSTOM_WEEK")
									],
									[
										'VALUE' => Recurring\Calculator::SALE_TYPE_MONTH_OFFSET,
										'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_CUSTOM_MONTH")
									],
									[
										'VALUE' => Recurring\Calculator::SALE_TYPE_YEAR_OFFSET,
										'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_CUSTOM_YEAR")
									]
								]
							),
							'amount' => 'RECURRING[MULTIPLE_CUSTOM_INTERVAL_VALUE]',

						),
					]
				],
				"data" => array(
					"view" => [],
					"fieldData" => [
						'MULTIPLE_EXECUTION' => Recurring\Manager::MULTIPLY_EXECUTION,
						'SINGLE_EXECUTION' => Recurring\Manager::SINGLE_EXECUTION,
						'MULTIPLE_CUSTOM' => Recurring\Calculator::SALE_TYPE_CUSTOM_OFFSET,
					]
				)
			],
			[
				'name' => 'RECURRING[MULTIPLE_DATE_START]',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_RECURRING_MULTIPLE_START_DATE_TITLE'),
				'type' => 'datetime',
				'editable' => true,
				'enableAttributes' => false,
				'enabledMenu' => false,
				'data' =>  array('enableTime' => false)
			],
			[
				"name" => "MULTIPLE_LIMIT",
				"type" => "recurring",
				"editable" => isset($this->arResult['CREATE_CATEGORY_LIST'])
					&& is_array($this->arResult['CREATE_CATEGORY_LIST'])
					&& count($this->arResult['CREATE_CATEGORY_LIST']) > 0,
				"transferable" => false,
				'enableAttributes' => false,
				"enableRecurring" => $this->isEnableRecurring,
				"elements" => [
					[
						'name' => 'RECURRING[MULTIPLE_TYPE_LIMIT]',
						'title' => Loc::getMessage('CRM_DEAL_FIELD_RECURRING_MULTIPLE_FINAL_LIMIT_TITLE'),
						'type' => 'list',
						'editable' => true,
						'enableAttributes' => false,
						'enabledMenu' => false,
						'data' => array(
							'items' => [
								[
									'VALUE' => Recurring\Entity\Base::NO_LIMITED,
									'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_MULTIPLE_FINAL_NO_LIMIT")
								],
								[
									'VALUE' => Recurring\Entity\Base::LIMITED_BY_DATE,
									'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_MULTIPLE_FINAL_LIMIT_DATE")
								],
								[
									'VALUE' => Recurring\Entity\Base::LIMITED_BY_TIMES,
									'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_MULTIPLE_FINAL_LIMIT_TIMES")
								]
							]
						),
					],
					[
						'name' => 'RECURRING[MULTIPLE_DATE_LIMIT]',
						'title' => Loc::getMessage('CRM_DEAL_FIELD_RECURRING_MULTIPLE_LIMIT_DATE_TITLE'),
						'type' => 'datetime',
						'editable' => true,
						'enabledMenu' => false,
						'enableAttributes' => false,
						'data' =>  array('enableTime' => false)
					],
					[
						'name' => 'RECURRING[MULTIPLE_TIMES_LIMIT]',
						'title' => Loc::getMessage('CRM_DEAL_FIELD_RECURRING_MULTIPLE_LIMIT_TIMES_TITLE'),
						'type' => 'number',
						'editable' => true,
						'enabledMenu' => false,
						'enableAttributes' => false,
					]
				],
				"data" => array(
					"view" => [],
					"fieldData" => [
						'MULTIPLE_EXECUTION' => Recurring\Manager::MULTIPLY_EXECUTION,
						'SINGLE_EXECUTION' => Recurring\Manager::SINGLE_EXECUTION,
						'NO_LIMIT' => Recurring\Entity\Deal::NO_LIMITED,
						'LIMITED_BY_DATE' => Recurring\Entity\Deal::LIMITED_BY_DATE,
						'LIMITED_BY_TIMES' => Recurring\Entity\Deal::LIMITED_BY_TIMES,
					]
				)
			],
			[
				"name" => "NEW_BEGINDATE",
				"type" => "recurring",
				"editable" => isset($this->arResult['CREATE_CATEGORY_LIST'])
					&&  is_array($this->arResult['CREATE_CATEGORY_LIST'])
					&& count($this->arResult['CREATE_CATEGORY_LIST']) > 0,
				"transferable" => false,
				'enableAttributes' => false,
				"enableRecurring" => $this->isEnableRecurring,
				"elements" => [
					[
						'name' => 'RECURRING[BEGINDATE_TYPE]',
						'title' => Loc::getMessage('CRM_DEAL_FIELD_RECURRING_NEW_BEGINDATE_VALUE_TITLE'),
						'type' => 'list',
						'editable' => true,
						'enableAttributes' => false,
						'enabledMenu' => false,
						'data' => array(
							'items' => [
								[
									'VALUE' => Recurring\Entity\Deal::SETTED_FIELD_VALUE,
									'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_NEW_VALUE_CURRENT_FIELD")
								],
								[
									'VALUE' => Recurring\Entity\Deal::CALCULATED_FIELD_VALUE,
									'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_NEW_VALUE_DATE_CREATION_OFFSET")
								]
							]
						),
					],
					[
						'name' => 'OFFSET_BEGINDATE',
						'title' => Loc::getMessage('CRM_DEAL_FIELD_RECURRING_DATE_CREATION_BEGINDATE_OFFSET_TITLE'),
						'type' => 'recurring_custom_row',
						'editable' => true,
						'enableAttributes' => false,
						'enabledMenu' => false,
						'data' => array(
							'select' => array(
								'name' => 'RECURRING[OFFSET_BEGINDATE_TYPE]',
								'items' => [
									[
										'VALUE' => Recurring\Calculator::SALE_TYPE_DAY_OFFSET,
										'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_CUSTOM_DAY")
									],
									[
										'VALUE' => Recurring\Calculator::SALE_TYPE_WEEK_OFFSET,
										'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_CUSTOM_WEEK")
									],
									[
										'VALUE' => Recurring\Calculator::SALE_TYPE_MONTH_OFFSET,
										'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_CUSTOM_MONTH")
									]
								]
							),
							'amount' => 'RECURRING[OFFSET_BEGINDATE_VALUE]',
						),
					]
				],
				"data" => array(
					"view" => [],
					"fieldData" => [
						'SETTED_FIELD_VALUE' => Recurring\Entity\Deal::SETTED_FIELD_VALUE,
						'CALCULATED_FIELD_VALUE' => Recurring\Entity\Deal::CALCULATED_FIELD_VALUE,
						'MULTIPLE_EXECUTION' => Recurring\Manager::MULTIPLY_EXECUTION,
						'SINGLE_EXECUTION' => Recurring\Manager::SINGLE_EXECUTION,
					]
				)
			],
			[
				"name" => "NEW_CLOSEDATE",
				"type" => "recurring",
				"editable" => isset($this->arResult['CREATE_CATEGORY_LIST'])
					&& is_array($this->arResult['CREATE_CATEGORY_LIST'])
					&& count($this->arResult['CREATE_CATEGORY_LIST']) > 0,
				"transferable" => false,
				'enableAttributes' => false,
				"enableRecurring" => $this->isEnableRecurring,
				"elements" => [
					[
						'name' => 'RECURRING[CLOSEDATE_TYPE]',
						'title' => Loc::getMessage('CRM_DEAL_FIELD_RECURRING_NEW_CLOSEDATE_VALUE_TITLE'),
						'type' => 'list',
						'editable' => true,
						'enableAttributes' => false,
						'enabledMenu' => false,
						'data' => array(
							'items' => [
								[
									'VALUE' => Recurring\Entity\Deal::SETTED_FIELD_VALUE,
									'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_NEW_VALUE_CURRENT_FIELD")
								],
								[
									'VALUE' => Recurring\Entity\Deal::CALCULATED_FIELD_VALUE,
									'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_NEW_VALUE_DATE_CREATION_OFFSET")
								]
							]
						),
					],
					[
						'name' => 'OFFSET_CLOSEDATE',
						'title' => Loc::getMessage('CRM_DEAL_FIELD_RECURRING_DATE_CREATION_CLOSEDATE_OFFSET_TITLE'),
						'type' => 'recurring_custom_row',
						'editable' => true,
						'enableAttributes' => false,
						'enabledMenu' => false,
						'data' => array(
							'select' => array(
								'name' => 'RECURRING[OFFSET_CLOSEDATE_TYPE]',
								'items' => [
									[
										'VALUE' => Recurring\Calculator::SALE_TYPE_DAY_OFFSET,
										'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_CUSTOM_DAY")
									],
									[
										'VALUE' => Recurring\Calculator::SALE_TYPE_WEEK_OFFSET,
										'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_CUSTOM_WEEK")
									],
									[
										'VALUE' => Recurring\Calculator::SALE_TYPE_MONTH_OFFSET,
										'NAME' => Loc::getMessage("CRM_DEAL_FIELD_RECURRING_CUSTOM_MONTH")
									]
								]
							),
							'amount' => 'RECURRING[OFFSET_CLOSEDATE_VALUE]',
						),
					]
				],
				"data" => array(
					"view" => [],
					"fieldData" => [
						'SETTED_FIELD_VALUE' => Recurring\Entity\Deal::SETTED_FIELD_VALUE,
						'CALCULATED_FIELD_VALUE' => Recurring\Entity\Deal::CALCULATED_FIELD_VALUE,
						'MULTIPLE_EXECUTION' => Recurring\Manager::MULTIPLY_EXECUTION,
						'SINGLE_EXECUTION' => Recurring\Manager::SINGLE_EXECUTION,
					]
				)
			]
		];

		if (
			isset($this->arResult['CREATE_CATEGORY_LIST'])
			&& is_array($this->arResult['CREATE_CATEGORY_LIST'])
			&& count($this->arResult['CREATE_CATEGORY_LIST']) > 0
		)
		{
			$data[] = [
				'name' => 'RECURRING[CATEGORY_ID]',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_CATEGORY_RECURRING'),
				'type' => 'list',
				'editable' => true,
				'enabledMenu' => false,
				'enableAttributes' => false,
				'data' => array(
					'items' => $this->arResult['CREATE_CATEGORY_LIST']
				),
			];
		}

		return $data;
	}

	protected function prepareScoringData()
	{
	}

	protected function resolvePersonTypeID(array $entityData)
	{
		$companyID = isset($entityData['COMPANY_ID']) ? (int)$entityData['COMPANY_ID'] : 0;
		$personTypes = CCrmPaySystem::getPersonTypeIDs();
		$personTypeID = 0;
		if (isset($personTypes['COMPANY']) && isset($personTypes['CONTACT']))
		{
			$personTypeID = $companyID > 0 ? $personTypes['COMPANY'] : $personTypes['CONTACT'];
		}

		return $personTypeID;
	}

	protected function tryGetFieldValueFromRequest($name, array &$params)
	{
		$value = $this->request->get($name);
		if($value === null)
		{
			return false;
		}

		$params[$name] = $value;
		return true;
	}

	public static function prepareLastBoundEntityIDs(int $entityTypeID, int $ownerEntityTypeID, array $params = null): array
	{
		if($params === null)
		{
			$params = array();
		}

		$userID = (isset($params['userID']) && $params['userID'] > 0)
			? (int)$params['userID'] : \CCrmSecurityHelper::GetCurrentUserID();
		$userPermissions = $params['userPermissions'] ?? \CCrmPerms::GetCurrentUserPermissions();

		$results = array();
		if($ownerEntityTypeID === \CCrmOwnerType::Deal && \CCrmDeal::CheckReadPermission(0, $userPermissions))
		{
			if($entityTypeID === \CCrmOwnerType::Contact)
			{
				$companyID = isset($params['companyID']) ? (int)$params['companyID'] : 0;
				if($companyID > 0)
				{
					$dbResult = \CCrmDeal::GetListEx(
						array('ID' => 'DESC'),
						array(
							'=COMPANY_ID' => $companyID,
							'=ASSIGNED_BY_ID' => $userID,
							'CHECK_PERMISSIONS' => 'N'
						),
						false,
						array('nTopCount' => 5),
						array('ID')
					);

					$ownerIDs = array();
					while($ary = $dbResult->Fetch())
					{
						$ownerIDs[] = (int)$ary['ID'];
					}

					$dealsContacts = Crm\Binding\DealContactTable::getDealsContactIds($ownerIDs);
					foreach ($ownerIDs as $dealId)
					{
						$contactIds = $dealsContacts[$dealId] ?? [];
						foreach ($contactIds as $contactId)
						{
							if(\CCrmContact::CheckReadPermission($contactId, $userPermissions))
							{
								$results[] = $contactId;
							}
						}

						if(!empty($results))
						{
							break;
						}
					}

					if(empty($results))
					{
						$results = Crm\Binding\ContactCompanyTable::getCompanyContactIDs($companyID);
					}
				}
			}
		}

		return $results;
	}

	protected function getEventTabParams(): array
	{
		return CCrmComponentHelper::getEventTabParams(
			$this->entityID,
			Loc::getMessage('CRM_DEAL_TAB_EVENT'),
			CCrmOwnerType::DealName,
			$this->arResult
		);
	}

	public function initializeEditorData(): void
	{
		$this->initializeMode();
		$this->initializeReadOnly();
		$this->initializeGuid();
		$this->initializeConfigId();
		$this->initializeConversionScheme();
		$this->initializePath();
		$this->initializeData();
		$this->prepareConfiguration();
		$this->prepareEntityControllers();
		$this->prepareValidators();
		$this->initializeAttributeScope();
		$this->initializeUFConfig();
		$this->initializeContext();
		$this->initializeExternalContextId();
	}

	private function initializeMode(): void
	{
		if ($this->entityID > 0)
		{
			$componentMode = $this->arParams['COMPONENT_MODE'] ?? null;

			if ($componentMode === ComponentMode::COPING || $this->request->get('copy') !== null)
			{
				$this->isCopyMode = true;
			}
			elseif ($this->isEnableRecurring && $this->request->get('expose') !== null)
			{
				$this->isExposeMode = true;
			}
			else
			{
				$this->isEditMode = true;
			}
		}

		$this->arResult['IS_EDIT_MODE'] = $this->isEditMode;
		$this->arResult['IS_COPY_MODE'] = $this->isCopyMode;
	}

	private function initializeReadOnly(): void
	{
		$this->arResult['READ_ONLY'] = true;

		if ($this->isEditMode)
		{
			if (\CCrmDeal::CheckUpdatePermission($this->entityID, $this->userPermissions))
			{
				$this->arResult['READ_ONLY'] = false;
			}
		}
		elseif (\CCrmDeal::CheckCreatePermission($this->userPermissions))
		{
			$this->arResult['READ_ONLY'] = false;
		}
	}

	private function initializeConfigId(): void
	{
		$this->arResult['EDITOR_CONFIG_ID'] = $this->prepareConfigID($this->arParams['EDITOR_CONFIG_ID'] ?? '');
	}

	private function initializeConversionScheme(): void
	{
		$this->arResult['PERMISSION_ENTITY_TYPE'] = DealCategory::convertToPermissionEntityType($this->categoryID);
		CCrmDeal::PrepareConversionPermissionFlags($this->entityID, $this->arResult, $this->userPermissions);

		if (isset($this->arResult['LEAD_ID']) && $this->arResult['LEAD_ID'] > 0 && isset($this->arParams['LEAD_ID']))
		{
			$this->leadID = $this->arResult['LEAD_ID'];
		}
		else
		{
			$leadID = $this->request->getQuery('lead_id');
			if ($leadID > 0)
			{
				$this->leadID = $this->arResult['LEAD_ID'] = (int)$leadID;
			}
		}

		if ($this->leadID > 0)
		{
			$this->loadLeadConversionWizard();
		}

		if (isset($this->arResult['QUOTE_ID']) && $this->arResult['QUOTE_ID'] > 0)
		{
			$this->quoteID = $this->arResult['QUOTE_ID'];
		}
		else
		{
			$quoteID = $this->request->getQuery('conv_quote_id');
			if ($quoteID > 0)
			{
				$this->quoteID = $this->arResult['QUOTE_ID'] = (int)$quoteID;
			}
		}

		if ($this->quoteID > 0)
		{
			$this->loadQuoteConversionWizard();
		}

		if ($this->conversionWizard !== null)
		{
			$conversionContextParams = $this->conversionWizard->prepareEditorContextParams(\CCrmOwnerType::Deal);
			if (isset($conversionContextParams['CATEGORY_ID']))
			{
				$this->arResult['CATEGORY_ID'] = $this->categoryID = $conversionContextParams['CATEGORY_ID'];
				$this->arResult['EDITOR_CONFIG_ID'] = $this->prepareConfigID($this->arParams['EDITOR_CONFIG_ID'] ?? '');
			}
		}
	}

	private function initializePath(): void
	{
		global $APPLICATION;

		$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] =
			CrmCheckPath(
				'PATH_TO_USER_PROFILE',
				$this->arParams['PATH_TO_USER_PROFILE'] ?? '',
				'/company/personal/user/#user_id#/'
			);

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(["#NOBR#", "#/NOBR#"], ["", ""], $this->arParams['NAME_TEMPLATE']);

		$this->arResult['PATH_TO_DEAL_SHOW'] = CrmCheckPath(
			'PATH_TO_DEAL_SHOW',
			$this->arParams['PATH_TO_DEAL_SHOW'] ?? '',
			$APPLICATION->GetCurPage() . '?deal_id=#deal_id#&show'
		);
		$this->arResult['PATH_TO_DEAL_EDIT'] = CrmCheckPath(
			'PATH_TO_DEAL_EDIT',
			$this->arParams['PATH_TO_DEAL_EDIT'] ?? '',
			$APPLICATION->GetCurPage() . '?deal_id=#deal_id#&edit'
		);

		$this->arResult['PATH_TO_QUOTE_SHOW'] = CrmCheckPath(
			'PATH_TO_QUOTE_SHOW',
			$this->arParams['PATH_TO_QUOTE_SHOW'] ?? '',
			$APPLICATION->GetCurPage() . '?quote_id=#quote_id#&show'
		);
		$this->arResult['PATH_TO_QUOTE_EDIT'] = CrmCheckPath(
			'PATH_TO_QUOTE_EDIT',
			$this->arParams['PATH_TO_QUOTE_EDIT'] ?? '',
			$APPLICATION->GetCurPage() . '?quote_id=#quote_id#&edit'
		);

		$this->arResult['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath(
			'PATH_TO_PRODUCT_EDIT',
			$this->arParams['PATH_TO_PRODUCT_EDIT'] ?? '',
			$APPLICATION->GetCurPage() . '?product_id=#product_id#&edit'
		);

		if ($this->isCatalogModuleIncluded && LayoutSettings::getCurrent()->isFullCatalogEnabled())
		{
			$catalogId = CCrmCatalog::EnsureDefaultExists();
			$this->arResult['PATH_TO_PRODUCT_SHOW'] = "/crm/catalog/{$catalogId}/product/#product_id#/";
		}
		else
		{
			$this->arResult['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath(
				'PATH_TO_PRODUCT_SHOW',
				$this->arParams['PATH_TO_PRODUCT_SHOW'] ?? '',
				$APPLICATION->GetCurPage() . '?product_id=#product_id#&show'
			);
		}
	}

	private function initializeContext(): void
	{
		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::DealName . '_' . $this->entityID;
		$this->arResult['CONTEXT'] = [
			'PARAMS' => [
				'PATH_TO_PRODUCT_SHOW' => $this->arResult['PATH_TO_PRODUCT_SHOW'],
				'PATH_TO_USER_PROFILE' => $this->arResult['PATH_TO_USER_PROFILE'],
				'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
			],
		];

		Crm\Service\EditorAdapter::addParentItemToContextIfFound($this->arResult['CONTEXT']);

		if ($this->isCopyMode || $this->isExposeMode)
		{
			$this->arResult['CONTEXT']['PARAMS']['DEAL_ID'] = $this->entityID;
		}

		if (!isset($this->arResult['CONTEXT']['PARAMS']['CATEGORY_ID']))
		{
			$this->arResult['CONTEXT']['PARAMS']['CATEGORY_ID'] = $this->categoryID;
		}

		if (isset($this->arResult['INITIAL_DATA']))
		{
			$this->arResult['CONTEXT']['INITIAL_DATA'] = $this->arResult['INITIAL_DATA'];
		}

		if ($this->conversionWizard !== null)
		{
			$conversionContextParams = $this->conversionWizard->prepareEditorContextParams(\CCrmOwnerType::Deal);
			$this->arResult['CONTEXT']['PARAMS'] = array_merge(
				$this->arResult['CONTEXT']['PARAMS'],
				$conversionContextParams
			);
		}

		$this->arResult['ORIGIN_ID'] = $this->request->get('origin_id');
		if ($this->arResult['ORIGIN_ID'] === null)
		{
			$this->arResult['ORIGIN_ID'] = '';
		}

		if (isset($this->arResult['ORIGIN_ID']) && $this->arResult['ORIGIN_ID'] !== '')
		{
			$this->arResult['CONTEXT']['ORIGIN_ID'] = $this->arResult['ORIGIN_ID'];
		}
	}

	public function initializeData()
	{
		$this->loadLeadConversionWizard();
		$this->loadQuoteConversionWizard();
		$this->prepareEntityData();
		$this->prepareFieldInfos();
		$this->prepareEntityFieldAttributes();
	}

	public function getEntityEditorData(): array
	{
		return [
			'ENTITY_ID' => $this->getEntityID(),
			'ENTITY_DATA' => $this->prepareEntityData(),
			'ADDITIONAL_FIELDS_DATA' => $this->getAdditionalFieldsData(),
		];
	}

	private function getWarehouseOnboardTourData(): array
	{
		$tourData = [
			'IS_TOUR_AVAILABLE' => WarehouseOnboarding::isCrmWarehouseOnboardingAvailable($this->userID),
		];

		if ($tourData['IS_TOUR_AVAILABLE'])
		{
			$tourData['CHAIN_DATA'] = (new WarehouseOnboarding($this->userID))->getCurrentChainData();
		}

		return $tourData;
	}

	private function getCurrencyId()
	{
		$currencyId = $this->entityData['CURRENCY_ID'] ?? null;

		if (!$currencyId)
		{
			$currencyId = CCrmCurrency::GetBaseCurrencyID();
		}

		return $currencyId;
	}
}
