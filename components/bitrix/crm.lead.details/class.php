<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Component\EntityDetails\ComponentMode;
use Bitrix\Crm\Component\EntityDetails\Traits;
use Bitrix\Crm\Controller\Action\Entity\SearchAction;
use Bitrix\Crm\Conversion\LeadConversionDispatcher;
use Bitrix\Crm\Conversion\LeadConversionScheme;
use Bitrix\Crm\CustomerType;
use Bitrix\Crm\Integrity\DuplicateControl;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Crm\Tracking;
use Bitrix\Currency;
use Bitrix\Location\Entity\Address\AddressLinkCollection;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));

	return;
}

Loc::loadMessages(__FILE__);

class CCrmLeadDetailsComponent
	extends CBitrixComponent
	implements Crm\Integration\UI\EntityEditor\SupportsEditorProvider
{
	use Traits\EditorConfig;
	use Traits\InitializeAttributeScope;
	use Traits\InitializeExternalContextId;
	use Traits\InitializeGuid;
	use Traits\InitializeMode;
	use Traits\InitializeUFConfig;
	use Traits\InitializeAdditionalFieldsData;
	use Crm\Entity\Traits\VisibilityConfig;

	private const PRODUCT_EDITOR_ID = 'lead_product_editor';

	/** @var int */
	private $customerType = CustomerType::GENERAL;
	/** @var string|null */
	protected $guidPrefix = null;
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
	private $initialData = null;
	/** @var array|null */
	private $entityDataScheme = null;
	/** @var array|null */
	private $entityFieldAttributeConfig = null;
	/** @var array|null */
	private $statuses = null;
	/** @var array|null */
	private $multiFieldInfos = null;
	/** @var array|null */
	private $multiFieldValueTypeInfos = null;
	/** @var bool */
	private $isEditMode = false;
	/** @var bool */
	private $isCopyMode = false;
	/** @var bool */
	private $isTaxMode = false;
	/** @var bool */
	private $enableDupControl = false;
	/** @var array|null */
	private $defaultFieldValues = null;
	/** @var bool */
	private $enableSearchHistory = true;
	//Enable or disable editor config change depends on entity state general or return customer lead)
	/** @var bool */
	private $enableConfigVariability = true;
	/** @var bool */
	private $isLocationModuleIncluded = false;
	/** @var bool */
	private $isCatalogModuleIncluded = false;
	/** @var array */
	private $defaultEntityData = [];
	/** @var Crm\Service\Factory\Lead|null */
	private ?Crm\Service\Factory\Lead $factory;
	/** @var EditorAdapter|null */
	private ?EditorAdapter $editorAdapter;

	public function __construct($component = null)
	{
		/** @global \CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

		parent::__construct($component);

		$this->userID = CCrmSecurityHelper::GetCurrentUserID();
		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$this->userFieldEntityID = \CCrmLead::GetUserFieldEntityID();
		$this->userType = new \CCrmUserType($USER_FIELD_MANAGER, $this->userFieldEntityID);
		$this->userFieldDispatcher = \Bitrix\Main\UserField\Dispatcher::instance();

		$this->multiFieldInfos = CCrmFieldMulti::GetEntityTypeInfos();
		$this->multiFieldValueTypeInfos = CCrmFieldMulti::GetEntityTypes();

		$this->isTaxMode = \CCrmTax::isTaxMode();

		$factory = Container::getInstance()->getFactory(\CCrmOwnerType::Lead);
		if ($factory)
		{
			$this->factory = $factory;
			$this->editorAdapter = $factory->getEditorAdapter();
		}
		else
		{
			$this->factory = $this->editorAdapter = null;
		}
	}
	public function executeComponent()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		$this->isCatalogModuleIncluded = Main\Loader::includeModule('catalog');
		$this->isLocationModuleIncluded = Main\Loader::includeModule('location');

		//region Params
		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID']) ? (int)$this->arParams['~ENTITY_ID'] : 0;

		$this->arResult['ENABLE_TASK'] = IsModuleInstalled('tasks');
		$this->arResult['ACTION_URI'] = $this->arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;

		$this->arResult['PRODUCT_DATA_FIELD_NAME'] = 'LEAD_PRODUCT_DATA';
		$this->arResult['PRODUCT_EDITOR_ID'] = self::PRODUCT_EDITOR_ID;

		$this->enableSearchHistory = !isset($this->arParams['~ENABLE_SEARCH_HISTORY'])
			|| mb_strtoupper($this->arParams['~ENABLE_SEARCH_HISTORY']) === 'Y';

		$this->arResult['INITIAL_DATA'] = isset($this->arParams['~INITIAL_DATA']) && is_array($this->arParams['~INITIAL_DATA'])
			? $this->arParams['~INITIAL_DATA'] : [];

		$this->defaultFieldValues = [];
		$this->tryGetFieldValueFromRequest('phone', $this->defaultFieldValues);
		//endregion

		$this->setEntityId($this->arResult['ENTITY_ID']);

		if ($this->entityID > 0 && !\CCrmLead::Exists($this->entityID))
		{
			Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::EntityNotExist, CCrmOwnerType::Lead);

			return;
		}

		//region Permissions check
		$this->initializeMode();

		if ($this->isCopyMode)
		{
			if (!\CCrmLead::CheckReadPermission($this->entityID, $this->userPermissions))
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoReadPermission, CCrmOwnerType::Lead);

				return;
			}
			elseif (!\CCrmLead::CheckCreatePermission($this->userPermissions))
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoAddPermission, CCrmOwnerType::Lead);

				return;
			}
		}
		elseif ($this->isEditMode)
		{
			if (
				!\CCrmLead::CheckUpdatePermission(0)
				&& !\CCrmLead::CheckReadPermission()
			)
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoAccessToEntityType, CCrmOwnerType::Lead);

				return;
			}
			elseif (
				!\CCrmLead::CheckUpdatePermission($this->entityID, $this->userPermissions)
				&& !\CCrmLead::CheckReadPermission($this->entityID, $this->userPermissions)
			)
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoReadPermission, CCrmOwnerType::Lead);

				return;
			}
		}
		elseif (!\CCrmLead::CheckCreatePermission($this->userPermissions))
		{
			Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoAddPermission, CCrmOwnerType::Lead);

			return;
		}
		//endregion

		$this->prepareEntityUserFields();
		$this->prepareEntityUserFieldInfos();
		$this->prepareStatusList();

		$this->initializeEditorData();

		$progressSemantics = $this->entityData['STATUS_ID']
			? \CCrmLead::GetStatusSemantics($this->entityData['STATUS_ID']) : '';
		$this->arResult['PROGRESS_SEMANTICS'] = $progressSemantics;

		//region Entity Info
		$this->arResult['ENTITY_INFO'] = array(
			'ENTITY_ID' => $this->entityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::LeadName,
			'ENTITY_TYPE_CODE' => CCrmOwnerTypeAbbr::Lead,
			'TITLE' => isset($this->entityData['TITLE']) ? $this->entityData['TITLE'] : '',
			'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Lead, $this->entityID, false),
		);
		//endregion

		//region Page title
		if($this->isCopyMode)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_LEAD_COPY_PAGE_TITLE'));
		}
		elseif(isset($this->entityData['TITLE']))
		{
			$APPLICATION->SetTitle(htmlspecialcharsbx($this->entityData['TITLE']));
		}
		elseif(!$this->isEditMode)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_LEAD_CREATION_PAGE_TITLE'));
		}
		//endregion

		//region Conversion
		$this->arResult['PERMISSION_ENTITY_TYPE'] = 'LEAD';
		\CCrmLead::PrepareConversionPermissionFlags($this->entityID, $this->arResult, $this->userPermissions);
		if ($this->isCopyMode || $this->entityID <= 0)
		{
			$this->arResult['CAN_CONVERT'] = false;
		}
		$this->arResult['ENABLE_PROGRESS_CHANGE'] = !$this->arResult['READ_ONLY'];

		$this->arResult['CONVERSION_TYPE_ID'] = LeadConversionDispatcher::resolveTypeID($this->entityData);
		$config = LeadConversionDispatcher::getConfiguration(array('FIELDS' => $this->entityData));
		$schemeID = $config->getSchemeID();

		// always prepare a scheme. we need it for correct rendering of termination control
		$this->arResult['CONVERSION_SCHEME'] = array(
			'ORIGIN_URL' => $APPLICATION->GetCurPage(),
			'SCHEME_ID' => $schemeID,
			'SCHEME_NAME' => LeadConversionScheme::resolveName($schemeID),
			'SCHEME_DESCRIPTION' => LeadConversionScheme::getDescription($schemeID),
			'SCHEME_CAPTION' => GetMessage('CRM_LEAD_CREATE_ON_BASIS')
		);

		if($this->arResult['CAN_CONVERT'])
		{
			$this->arResult['CONVERSION_CONFIG'] = $config;

			$this->arResult['CONVERTER_ID'] = $this->guid;
			$this->arResult['CONVERSION_CONTAINER_ID'] = "toolbar_lead_details_{$this->getEntityID()}_convert_label";
			$this->arResult['CONVERSION_LABEL_ID'] = $this->arResult['CONVERSION_CONTAINER_ID'];
			$this->arResult['CONVERSION_BUTTON_ID'] = "toolbar_lead_details_{$this->getEntityID()}_convert_button";
		}
		//endregion

		//region Tabs
		$this->arResult['TABS'] = [];
		ob_start();

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
				'OWNER_ID' => $this->entityID,
				'OWNER_TYPE' => 'L',
				'PERMISSION_TYPE' => $this->arResult['READ_ONLY'] ? 'READ' : 'WRITE',
				'PERMISSION_ENTITY_TYPE' => $this->arResult['PERMISSION_ENTITY_TYPE'],
				'PERSON_TYPE_ID' => $this->resolvePersonTypeID($this->entityData),
				'CURRENCY_ID' => $this->getCurrencyId(),
				'LOCATION_ID' => $this->isTaxMode && isset($this->entityData['LOCATION_ID']) ? $this->entityData['LOCATION_ID'] : '',
				'CLIENT_SELECTOR_ID' => '', //TODO: Add Client Selector
				//'PRODUCT' =>  null,
				'PRODUCT_DATA_FIELD_NAME' => $this->arResult['PRODUCT_DATA_FIELD_NAME'],
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
			'name' => Loc::getMessage('CRM_LEAD_TAB_PRODUCTS'),
			'html' => ob_get_clean()
		);

		$relationManager = Crm\Service\Container::getInstance()->getRelationManager();
		$this->arResult['TABS'] = array_merge(
			$this->arResult['TABS'],
			$relationManager->getRelationTabsForDynamicChildren(
				\CCrmOwnerType::Lead,
				$this->entityID,
				($this->entityID === 0)
			)
		);

		if($this->entityID > 0)
		{
			$tabQuote = [
				'id' => 'tab_quote',
				'name' => Loc::getMessage('CRM_LEAD_TAB_QUOTE_MSGVER_1'),
				'loader' => [
					'serviceUrl' => '/bitrix/components/bitrix/crm.quote.list/lazyload.ajax.php?&site='
						.SITE_ID
						.'&'
						.bitrix_sessid_get()
					,
					'componentData' => [
						'template' => '',
						'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
							'QUOTE_COUNT' => '20',
							'PATH_TO_QUOTE_SHOW' => $this->arResult['PATH_TO_QUOTE_SHOW'] ?? '',
							'PATH_TO_QUOTE_EDIT' => $this->arResult['PATH_TO_QUOTE_EDIT'] ?? '',
							'INTERNAL_FILTER' => ['LEAD_ID' => $this->entityID],
							'INTERNAL_CONTEXT' => ['LEAD_ID' => $this->entityID],
							'GRID_ID_SUFFIX' => 'LEAD_DETAILS',
							'TAB_ID' => 'tab_quote',
							'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'] ?? '',
							'ENABLE_TOOLBAR' => true,
							'PRESERVE_HISTORY' => true,
							'ADD_EVENT_NAME' => 'CrmCreateQuoteFromLead',
							'ANALYTICS' => [
								// we dont know where from this component was opened from - it could be anywhere on portal
								'c_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_LEAD,
								'c_sub_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_DETAILS,
							],
						], 'crm.quote.list'),
					],
				],
			];

			$toolsManager = \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager();
			if (!$toolsManager->checkEntityTypeAvailability(\CCrmOwnerType::Quote))
			{
				$availabilityLock = \Bitrix\Crm\Restriction\AvailabilityManager::getInstance()
					->getEntityTypeAvailabilityLock(\CCrmOwnerType::Quote)
				;
				$tabQuote['availabilityLock'] = $availabilityLock;
			}

			$this->arResult['TABS'][] = $tabQuote;

			if (\Bitrix\Crm\Automation\Factory::isAutomationAvailable(CCrmOwnerType::Lead))
			{
				$robotsTab = [
					'id' => 'tab_automation',
					'name' => Loc::getMessage('CRM_LEAD_TAB_AUTOMATION'),
					'url' => Container::getInstance()->getRouter()->getAutomationUrl(CCrmOwnerType::Lead)
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
					CCrmOwnerType::Lead,
					0,
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
			if (Main\Loader::includeModule('bizproc') && CBPRuntime::isFeatureEnabled())
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
						'name' => Loc::getMessage('CRM_LEAD_TAB_BIZPROC'),
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
									'ENTITY' => 'CCrmDocumentLead',
									'DOCUMENT_TYPE' => 'LEAD',
									'DOCUMENT_ID' => 'LEAD_' . $this->entityID
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
						'entity' => 'CCrmDocumentLead',
						'documentType' => 'LEAD',
						'documentId' => 'LEAD_' . $this->entityID,
					];
				}
			}
			$this->arResult['TABS'][] = array(
				'id' => 'tab_tree',
				'name' => Loc::getMessage('CRM_LEAD_TAB_TREE'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.entity.tree/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '.default',
						'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
							'ENTITY_ID' => $this->entityID,
							'ENTITY_TYPE_NAME' => CCrmOwnerType::LeadName,
						], 'crm.entity.tree')
					)
				)
			);
			$this->arResult['TABS'][] = $this->getEventTabParams();
			if (CModule::IncludeModule('lists'))
			{
				$listIblock = CLists::getIblockAttachedCrm(CCrmOwnerType::LeadName);
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
									'ENTITY_TYPE' => CCrmOwnerType::Lead,
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
				'name' => Loc::getMessage('CRM_LEAD_TAB_QUOTE_MSGVER_1'),
				'enabled' => false
			);
			if (\Bitrix\Crm\Automation\Factory::isAutomationAvailable(CCrmOwnerType::Lead))
			{
				$this->arResult['TABS'][] = array(
					'id' => 'tab_automation',
					'name' => Loc::getMessage('CRM_LEAD_TAB_AUTOMATION'),
					'enabled' => false
				);
			}
			if (CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled())
			{
				$this->arResult['TABS'][] = array(
					'id' => 'tab_bizproc',
					'name' => Loc::getMessage('CRM_LEAD_TAB_BIZPROC'),
					'enabled' => false
				);
			}
			$this->arResult['TABS'][] = $this->getEventTabParams();
			if (CModule::IncludeModule('lists'))
			{
				$listIblock = CLists::getIblockAttachedCrm(CCrmOwnerType::LeadName);
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
		//endregion

		//region Wait Target Dates
		$this->arResult['WAIT_TARGET_DATES'] = [];
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
		if($this->arResult['ENTITY_ID'] > 0)
		{
			if(isset($this->entityData['IS_RETURN_CUSTOMER']) && $this->entityData['IS_RETURN_CUSTOMER'] === 'Y')
			{
				$this->arResult['LEGEND'] = Loc::getMessage('CRM_LEAD_RETURNING');
			}
		}
		//endregion

		//region VIEW EVENT
		if($this->entityID > 0 && \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isViewEventEnabled())
		{
			CCrmEvent::RegisterViewEvent(CCrmOwnerType::Lead, $this->entityID, $this->userID);
		}
		//endregion

		//region SCORING
		$stageSemanticId = \CCrmLead::GetSemanticID($this->entityData['STATUS_ID']);
		$this->arResult['IS_STAGE_FINAL'] = Crm\PhaseSemantics::isFinal($stageSemanticId);

		if($this->entityID > 0)
		{
			if(!$this->arResult['IS_STAGE_FINAL'])
			{
				Crm\Ml\ViewHelper::subscribePredictionUpdate(CCrmOwnerType::Lead, $this->entityID);
			}
			$this->arResult['SCORING'] = Crm\Ml\ViewHelper::prepareData(CCrmOwnerType::Lead, $this->entityID);
		}

		if(!Crm\Ml\Scoring::isEnabled())
		{
			CBitrix24::initLicenseInfoPopupJS();
		}
		//endregion

		$this->includeComponentTemplate();
	}
	public function isSearchHistoryEnabled()
	{
		return $this->enableSearchHistory;
	}

	public function enableSearchHistory($enable)
	{
		$this->enableSearchHistory = (bool)$enable;
	}

	public function isConfigVariabilityEnabled()
	{
		return $this->enableConfigVariability;
	}
	public function enableConfigVariability($enable)
	{
		if(!is_bool($enable))
		{
			$enable = (bool)$enable;
		}
		$this->enableConfigVariability = $enable;
	}
	public function initializeParams(array $params)
	{
		$this->isLocationModuleIncluded = Main\Loader::includeModule('location');

		foreach($params as $k => $v)
		{
			if ($k === 'INITIAL_DATA' && is_array($v))
			{
				$this->arResult['INITIAL_DATA'] = $this->arParams['INITIAL_DATA'] = $v;
			}
			elseif ($k === 'COMPONENT_MODE' && is_numeric($v))
			{
				$this->arParams['COMPONENT_MODE'] = (int)$v;
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
			elseif($k === 'ORIGIN_ID')
			{
				$this->arResult['ORIGIN_ID'] = $v;
				$this->arParams['ORIGIN_ID'] = $v;
			}
			elseif($k === 'DEFAULT_PHONE_VALUE')
			{
				$this->arResult['DEFAULT_PHONE_VALUE'] = (int)$v;
				$this->arParams['DEFAULT_PHONE_VALUE'] = (int)$v;
			}
			elseif ($k === 'ENABLE_SEARCH_HISTORY')
			{
				$this->enableSearchHistory($v === 'Y');
			}
		}
	}

	public function getGuidPrefix()
	{
		if($this->guidPrefix !== null)
		{
			return $this->guidPrefix;
		}

		return ($this->guidPrefix = $this->customerType !== CustomerType::GENERAL
			? mb_strtolower(CustomerType::resolveName($this->customerType))
			: ''
		);
	}
	public function getDefaultGuid()
	{
		$prefix = $this->getGuidPrefix();
		return $prefix !== '' ? "{$prefix}_lead_{$this->entityID}_details" : "lead_{$this->entityID}_details";
	}
	public function getDefaultConfigID()
	{
		$prefix = $this->getGuidPrefix();
		return $prefix !== '' ? "{$prefix}_lead_details" : 'lead_details';
	}
	public function getEntityID()
	{
		return $this->entityID;
	}
	public function setEntityID($entityID)
	{
		$this->entityID = $entityID;
		$this->arResult['ENTITY_ID'] = $this->entityID;

		if($this->enableConfigVariability)
		{
			$this->customerType = $this->entityID > 0
				? \CCrmLead::GetCustomerType($this->entityID) : CustomerType::GENERAL;
		}

		$this->guidPrefix = null;

		$this->userFields = null;
		$this->prepareEntityUserFields();

		$this->userFieldInfos = null;
		$this->prepareEntityUserFieldInfos();
	}
	public function prepareEntityDataScheme()
	{
		if($this->entityDataScheme === null)
		{
			$this->entityDataScheme = \CCrmLead::GetFieldsInfo();
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
	public function prepareFieldInfos()
	{
		if(isset($this->entityFieldInfos))
		{
			return $this->entityFieldInfos;
		}

		$prohibitedStatusIDs = [];
		$allStatuses = CCrmStatus::GetStatusList('STATUS');
		foreach(array_keys($allStatuses) as $statusID)
		{
			if (isset($this->arResult['READ_ONLY']) && $this->arResult['READ_ONLY'])
			{
				$prohibitedStatusIDs[] = $statusID;
			}
			else
			{
				$permissionType = $this->isEditMode
					? \CCrmLead::GetStatusUpdatePermissionType($statusID, $this->userPermissions)
					: \CCrmLead::GetStatusCreatePermissionType($statusID, $this->userPermissions);

				if($permissionType == BX_CRM_PERM_NONE)
				{
					$prohibitedStatusIDs[] = $statusID;
				}
			}
		}

		$fakeValue = '';
		$this->entityFieldInfos = array(
			array(
				'name' => 'ID',
				'title' => Loc::getMessage('CRM_LEAD_FIELD_ID'),
				'type' => 'text',
				'editable' => false,
				'enableAttributes' => false,
				'mergeable' => false,
			),
			array(
				'name' => 'DATE_CREATE',
				'title' => Loc::getMessage('CRM_LEAD_FIELD_DATE_CREATE'),
				'type' => 'datetime',
				'editable' => false,
				'enableAttributes' => false,
				'mergeable' => false,
			),
			array(
				'name' => 'DATE_MODIFY',
				'title' => Loc::getMessage('CRM_LEAD_FIELD_DATE_MODIFY'),
				'type' => 'datetime',
				'editable' => false,
				'enableAttributes' => false,
				'mergeable' => false,
			),
			array(
				'name' => 'TITLE',
				'title' => Loc::getMessage('CRM_LEAD_FIELD_TITLE'),
				'type' => 'text',
				'isHeading' => true,
				'visibilityPolicy' => 'edit',
				'placeholders' => array('creation' => \CCrmLead::GetDefaultTitle()),
				'editable' => true
			),
			array(
				'name' => 'STATUS_ID',
				'title' => Loc::getMessage('CRM_LEAD_FIELD_STATUS_ID_MSGVER_1'),
				'type' => 'list',
				'editable' => true,
				'enableAttributes' => false,
				'mergeable' => false,
				'data' => array(
					'items'=> \CCrmInstantEditorHelper::PrepareListOptions(
						$allStatuses,
						array('EXCLUDE_FROM_EDIT' => array_merge($prohibitedStatusIDs, array('CONVERTED')))
					)
				)
			),
			array(
				'name' => 'STATUS_DESCRIPTION',
				'title' => Loc::getMessage('CRM_LEAD_FIELD_STATUS_DESCRIPTION_MSGVER_1'),
				'type' => 'text',
				'data' => array('lineCount' => 6),
				'editable' => true,
				'mergeable' => false,
			),
			array(
				'name' => 'OPPORTUNITY_WITH_CURRENCY',
				'title' => Loc::getMessage('CRM_LEAD_FIELD_OPPORTUNITY_WITH_CURRENCY'),
				'type' => 'money',
				'editable' => true,
				'mergeable' => false,
				'data' => array(
					'affectedFields' => array('CURRENCY_ID', 'OPPORTUNITY'),
					'currency' => array(
						'name' => 'CURRENCY_ID',
						'items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems())
					),
					'amount' => 'OPPORTUNITY',
					'formatted' => 'FORMATTED_OPPORTUNITY',
					'formattedWithCurrency' => 'FORMATTED_OPPORTUNITY_WITH_CURRENCY'
				)
			),
			array(
				'name' => 'SOURCE_ID',
				'title' => Loc::getMessage('CRM_LEAD_FIELD_SOURCE_ID'),
				'type' => 'list',
				'editable' => true,
				'data' => [
					'items'=> \CCrmInstantEditorHelper::PrepareListOptions(
						CCrmStatus::GetStatusList('SOURCE'),
						[
							'NOT_SELECTED' => Loc::getMessage('CRM_LEAD_HONORIFIC_NOT_SELECTED'),
							'NOT_SELECTED_VALUE' => $fakeValue
						]
					),
					'defaultValue' => $this->defaultEntityData['SOURCE_ID'] ?? null,
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
				'title' => Loc::getMessage('CRM_LEAD_FIELD_SOURCE_DESCRIPTION'),
				'type' => 'text',
				'data' => array('lineCount' => 6),
				'editable' => true
			),
			array(
				'name' => 'ASSIGNED_BY_ID',
				'title' => Loc::getMessage('CRM_LEAD_FIELD_ASSIGNED_BY_ID'),
				'type' => 'user',
				'editable' => true,
				'data' => array(
					'enableEditInView' => true,
					'formated' => 'ASSIGNED_BY_FORMATTED_NAME',
					'position' => 'ASSIGNED_BY_WORK_POSITION',
					'photoUrl' => 'ASSIGNED_BY_PHOTO_URL',
					'showUrl' => 'PATH_TO_ASSIGNED_BY_USER',
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE'] ?? ''

				),
				'enableAttributes' => false
			),
			array(
				'name' => 'OBSERVER',
				'title' => Loc::getMessage('CRM_LEAD_FIELD_OBSERVERS'),
				'type' => 'multiple_user',
				'editable' => true,
				'data' => array(
					'enableEditInView' => true,
					'map' => array('data' => 'OBSERVER_IDS'),
					'infos' => 'OBSERVER_INFOS',
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE'] ?? '',
					'messages' => array('addObserver' => Loc::getMessage('CRM_LEAD_FIELD_ADD_OBSERVER'))
				)
			),
			array(
				'name' => 'OPENED',
				'title' => Loc::getMessage('CRM_LEAD_FIELD_OPENED'),
				'type' => 'boolean',
				'editable' => true
			),
			Crm\Entity\CommentsHelper::compileFieldDescriptionForDetails(
				\CCrmOwnerType::Lead,
				'COMMENTS',
				$this->entityID,
			),
			EditorAdapter::getProductRowSummaryField(
				Loc::getMessage('CRM_LEAD_FIELD_PRODUCTS'),
				'PRODUCT_ROW_SUMMARY'
			),
		);

		if($this->customerType === CustomerType::GENERAL)
		{
			$this->entityFieldInfos = array_merge(
				$this->entityFieldInfos,
				array(
					array(
						'name' => 'HONORIFIC',
						'title' => Loc::getMessage('CRM_LEAD_FIELD_HONORIFIC'),
						'type' => 'list',
						'editable' => true,
						'data' => [
							'items'=> \CCrmInstantEditorHelper::PrepareListOptions(
								CCrmStatus::GetStatusList('HONORIFIC'),
								[
									'NOT_SELECTED' => Loc::getMessage('CRM_LEAD_HONORIFIC_NOT_SELECTED'),
									'NOT_SELECTED_VALUE' => $fakeValue,
								]
							),
							'defaultValue' => $this->defaultEntityData['HONORIFIC'] ?? null,
							'innerConfig' => \CCrmInstantEditorHelper::prepareInnerConfig(
								'crm_status',
								'crm.status.setItems',
								'HONORIFIC',
								[$fakeValue]
							),
						]
					),
					array(
						'name' => 'LAST_NAME',
						'title' => Loc::getMessage('CRM_LEAD_FIELD_LAST_NAME'),
						'type' => 'text',
						'editable' => true,
						'data' => array('duplicateControl' => array('groupId' => 'fullName', 'field' => array('id' => 'LAST_NAME')))
					),
					array(
						'name' => 'NAME',
						'title' => Loc::getMessage('CRM_LEAD_FIELD_NAME'),
						'type' => 'text',
						'editable' => true,
						'data' => array('duplicateControl' => array('groupId' => 'fullName', 'field' => array('id' => 'NAME')))
					),
					array(
						'name' => 'SECOND_NAME',
						'title' => Loc::getMessage('CRM_LEAD_FIELD_SECOND_NAME'),
						'type' => 'text',
						'editable' => true,
						'data' => array('duplicateControl' => array('groupId' => 'fullName', 'field' => array('id' => 'SECOND_NAME')))
					),
					array(
						'name' => 'BIRTHDATE',
						'title' => Loc::getMessage('CRM_LEAD_FIELD_BIRTHDATE'),
						'type' => 'datetime',
						'editable' => true,
						'data' =>  array('enableTime' => false)
					),
					array(
						'name' => 'POST',
						'title' => Loc::getMessage('CRM_LEAD_FIELD_POST'),
						'type' => 'text',
						'editable' => true
					),
					array(
						'name' => 'COMPANY_TITLE',
						'title' => Loc::getMessage('CRM_LEAD_FIELD_COMPANY_TITLE'),
						'type' => 'text',
						'editable' => true,
						'data' => array('duplicateControl' => array('groupId' => 'companyTitle', 'field' => array('id' => 'COMPANY_TITLE')))
					),

				)
			);
			if ($this->isLocationModuleIncluded)
			{
				$addressField = array(
					'name' => 'ADDRESS',
					'title' => Loc::getMessage('CRM_LEAD_FIELD_ADDRESS'),
					'type' => 'address',
					'editable' => true,
					'data' => CCrmComponentHelper::getFieldInfoData(CCrmOwnerType::Lead,'address')
				);
			}
			else
			{
				$addressField = array(
					'name' => 'ADDRESS',
					'title' => Loc::getMessage('CRM_LEAD_FIELD_ADDRESS'),
					'type' => 'address_form',
					'editable' => true,
					'data' => array(
						'fields' => array(
							'ADDRESS' => array('NAME' => 'ADDRESS', 'IS_MULTILINE' => true),
							'ADDRESS_2' => array('NAME' => 'ADDRESS_2'),
							'CITY' => array('NAME' => 'ADDRESS_CITY'),
							'REGION' => array('NAME' => 'ADDRESS_REGION'),
							'PROVINCE' => array('NAME' => 'ADDRESS_PROVINCE'),
							'POSTAL_CODE' => array('NAME' => 'ADDRESS_POSTAL_CODE'),
							'COUNTRY' => array('NAME' => 'ADDRESS_COUNTRY')
						),
						'labels' => \Bitrix\Crm\EntityAddress::getLabels(),
						'view' => 'ADDRESS_HTML'
					)
				);
			}
			$this->entityFieldInfos = array_merge(
				$this->entityFieldInfos,
				[$addressField]
			);

			foreach($this->multiFieldInfos as $typeName => $typeInfo)
			{
				$valueTypes = $this->multiFieldValueTypeInfos[$typeName] ?? [];
				$valueTypeItems = [];

				$valueTypeItems = array();
				foreach($valueTypes as $valueTypeId => $valueTypeInfo)
				{
					$valueTypeItems[] = array(
						'NAME' => $valueTypeInfo['SHORT'] ?? $valueTypeInfo['FULL'],
						'VALUE' => $valueTypeId
					);
				}

				$data = array('type' => $typeName, 'items'=> $valueTypeItems);
				if($typeName === 'PHONE')
				{
					$data['duplicateControl'] = array('groupId' => 'phone');
				}
				else if($typeName === 'EMAIL')
				{
					$data['duplicateControl'] = array('groupId' => 'email');
				}

				$this->entityFieldInfos[] = array(
					'name' => $typeName,
					'title' => $typeInfo['NAME'],
					'type' => 'multifield',
					'editable' => true,
					'data' => $data
				);
			}
		}

		//region Client

		$categoryParams = CCrmComponentHelper::getEntityClientFieldCategoryParams(CCrmOwnerType::Lead);
		$this->entityFieldInfos[] = [
			'name' => 'CLIENT',
			'title' => Loc::getMessage('CRM_LEAD_FIELD_CLIENT'),
			'type' => 'client_light',
			'editable' => true,
			'data' => [
				'affectedFields' => ['CLIENT_INFO'],
				'compound' => [
					[
						'name' => 'COMPANY_ID',
						'type' => 'company',
						'entityTypeName' => \CCrmOwnerType::CompanyName,
						'tagName' => \CCrmOwnerType::CompanyName
					],
					[
						'name' => 'CONTACT_IDS',
						'type' => 'multiple_contact',
						'entityTypeName' => \CCrmOwnerType::ContactName,
						'tagName' => \CCrmOwnerType::ContactName
					]
				],
				'categoryParams' => $categoryParams,
				'map' => ['data' => 'CLIENT_DATA'],
				'info' => 'CLIENT_INFO',
				'lastCompanyInfos' => 'LAST_COMPANY_INFOS',
				'lastContactInfos' => 'LAST_CONTACT_INFOS',
				'loaders' => [
					'primary' => [
						CCrmOwnerType::CompanyName => [
							'action' => 'GET_CLIENT_INFO',
							'url' => '/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get()
						],
						CCrmOwnerType::ContactName => [
							'action' => 'GET_CLIENT_INFO',
							'url' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get()
						]
					],
					'secondary' => [
						CCrmOwnerType::CompanyName => [
							'action' => 'GET_SECONDARY_ENTITY_INFOS',
							'url' => '/bitrix/components/bitrix/crm.lead.edit/ajax.php?'.bitrix_sessid_get()
						]
					]
				],
				'clientEditorFieldsParams' =>
					CCrmComponentHelper::prepareClientEditorFieldsParams(
						['categoryParams' => $categoryParams]
					)
				,
				'duplicateControl' => CCrmComponentHelper::prepareClientEditorDuplicateControlParams(
					['entityTypes' => [CCrmOwnerType::Company, CCrmOwnerType::Contact]]
				),
			]
		];
		//endregion

		Tracking\UI\Details::appendEntityFields($this->entityFieldInfos);
		$this->entityFieldInfos[] = array(
			'name' => 'UTM',
			'title' => Loc::getMessage('CRM_LEAD_FIELD_UTM'),
			'type' => 'custom',
			'data' => array('view' => 'UTM_VIEW_HTML'),
			'editable' => false,
			'enableAttributes' => false
		);

		$this->entityFieldInfos = array_merge(
			$this->entityFieldInfos,
			array_values($this->userFieldInfos)
		);
		if ($this->editorAdapter)
		{
			$parentFieldsInfo = $this->editorAdapter->getParentFieldsInfo(\CCrmOwnerType::Lead);
			$this->entityFieldInfos = array_merge(
				$this->entityFieldInfos,
				array_values($parentFieldsInfo)
			);
		}

		$this->arResult['ENTITY_FIELDS'] = $this->entityFieldInfos;

		return $this->entityFieldInfos;
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

		if (
			is_array($fieldInfo)
			&& isset($fieldInfo['name'])
			&& is_string($fieldInfo['name'])
			&& $fieldInfo['name'] !== ''
		)
		{
			$fieldName = $fieldInfo['name'] ?? '';
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
					'STATUS_DESCRIPTION',                // text
					'OPPORTUNITY_WITH_CURRENCY',         // money
					'CLIENT',                            // client_light
					'HONORIFIC',                         // list
					'LAST_NAME',                         // text
					'NAME',                              // text
					'SECOND_NAME',                       // text
					'BIRTHDATE',                         // datetime
					'POST',                              // text
					'PHONE',                             // multifield
					'EMAIL',                             // multifield
					'WEB',                               // multifield
					'IM',                                // multifield
					'ADDRESS',                           // address
					'SOURCE_ID',                         // list
					'SOURCE_DESCRIPTION',                // text
					Tracking\UI\Details::SourceId,       // custom
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
						case 'address':
							if ($fieldName === 'ADDRESS')
							{
								if (array_key_exists($fieldName, $this->entityData)
									&& (!is_string($this->entityData[$fieldName])
										|| $this->entityData[$fieldName] === ''))
								{
									$result = true;
									$isResultReady = true;
								}
							}
							break;
						case 'multifield':
							if (!is_array($this->entityData[$fieldName])
								|| empty($this->entityData[$fieldName]))
							{
								$result = true;
								$isResultReady = true;
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
	public function prepareConfiguration()
	{
		if(isset($this->arResult['ENTITY_CONFIG']))
		{
			return $this->arResult['ENTITY_CONFIG'];
		}

		$userFieldConfigElements = [];
		foreach(array_keys($this->userFieldInfos) as $fieldName)
		{
			$userFieldConfigElements[] = array('name' => $fieldName);
		}

		$sectionMain = array(
			'name' => 'main',
			'title' => Loc::getMessage('CRM_LEAD_SECTION_MAIN'),
			'type' => 'section',
			'elements' => []
		);
		$sectionAdditional = array(
			'name' => 'additional',
			'title' => Loc::getMessage('CRM_LEAD_SECTION_ADDITIONAL'),
			'type' => 'section',
			'elements' => []
		);
		$sectionProductRow = array(
			'name' => 'products',
			'title' => Loc::getMessage('CRM_LEAD_SECTION_PRODUCTS'),
			'type' => 'section',
			'elements' => array(
				array('name' => 'PRODUCT_ROW_SUMMARY')
			)
		);

		if($this->customerType === CustomerType::GENERAL)
		{
			$multiFieldConfigElements = [];
			foreach(array_keys($this->multiFieldInfos) as $fieldName)
			{
				$multiFieldConfigElements[] = array('name' => $fieldName);
			}

			$sectionMain['elements'] = array_merge(
				array(
					array('name' => 'TITLE'),
					array('name' => 'STATUS_ID'),
					array('name' => 'OPPORTUNITY_WITH_CURRENCY'),
					array('name' => 'CLIENT'),
					array('name' => 'HONORIFIC'),
					array('name' => 'LAST_NAME'),
					array('name' => 'NAME'),
					array('name' => 'SECOND_NAME'),
					array('name' => 'BIRTHDATE'),
					array('name' => 'POST'),
					array('name' => 'COMPANY_TITLE')
				),
				$multiFieldConfigElements
			);

			$sectionAdditional['elements'] = array_merge(
				array(
					array('name' => 'SOURCE_ID'),
					array('name' => 'SOURCE_DESCRIPTION'),
					array('name' => 'OPENED'),
					array('name' => 'ASSIGNED_BY_ID'),
					array('name' => 'OBSERVER'),
					array('name' => 'COMMENTS'),
					array('name' => 'ADDRESS'),
					array('name' => 'UTM'),
				),
				$userFieldConfigElements
			);
		}
		elseif($this->customerType === CustomerType::RETURNING)
		{
			$sectionMain['elements'] = array(
				array('name' => 'TITLE'),
				array('name' => 'STATUS_ID'),
				array('name' => 'OPPORTUNITY_WITH_CURRENCY'),
				array('name' => 'CLIENT')
			);

			$sectionAdditional['elements'] = array_merge(
				array(
					array('name' => 'SOURCE_ID'),
					array('name' => 'SOURCE_DESCRIPTION'),
					array('name' => 'OPENED'),
					array('name' => 'ASSIGNED_BY_ID'),
					array('name' => 'COMMENTS'),
					array('name' => 'UTM'),
				),
				$userFieldConfigElements
			);
		}

		$this->arResult['ENTITY_CONFIG'] = array($sectionMain, $sectionAdditional, $sectionProductRow);
		return $this->arResult['ENTITY_CONFIG'];
	}
	public function prepareEntityUserFields()
	{
		if($this->userFields === null)
		{
			$this->userFields = $this->userType->GetEntityFields($this->entityID);
		}
		return $this->userFields;
	}
	public function prepareEntityUserFieldInfos()
	{
		if($this->userFieldInfos !== null)
		{
			return $this->userFieldInfos;
		}

		$attrConfigs = $this->prepareEntityFieldAttributeConfigs();
		$visibilityConfig = $this->prepareEntityFieldvisibilityConfigs(CCrmOwnerType::Lead);

		$this->userFieldInfos = [];
		$userFields = $this->prepareEntityUserFields();
		$enumerationFields = [];
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
				//'CONTEXT' => $this->guid
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

			//region Define Field attribute configuration
			$data = array('fieldInfo' => $fieldInfo);
			if(isset($attrConfigs[$fieldName]))
			{
				$data['attrConfigs'] = $attrConfigs[$fieldName];
			}
			//endregion

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
	public function prepareEntityFieldAttributeConfigs()
	{
		if(!$this->entityFieldAttributeConfig)
		{
			$this->entityFieldAttributeConfig = FieldAttributeManager::getEntityConfigurations(
				CCrmOwnerType::Lead,
				FieldAttributeManager::resolveEntityScope(
					CCrmOwnerType::Lead,
					$this->entityID
				)
			);
		}
		return $this->entityFieldAttributeConfig;
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

		$isTrackingFieldRequired = false;

		if($this->entityID <= 0)
		{
			$requiredFields = FieldAttributeManager::isEnabled()
				? FieldAttributeManager::getRequiredFields(
					CCrmOwnerType::Lead,
					$this->entityID,
					['SOURCE_ID', 'HONORIFIC', Tracking\UI\Details::SourceId],
					Crm\Attribute\FieldOrigin::SYSTEM
				)
				: [];
			$fieldsInfo = \CCrmLead::GetFieldsInfo();
			$this->entityData = [];
			//leave OPPORTUNITY unassigned
			//$this->entityData['OPPORTUNITY'] = 0.0;
			$this->entityData['CURRENCY_ID'] = \CCrmCurrency::GetBaseCurrencyID();
			$this->entityData['OPENED'] = \Bitrix\Crm\Settings\LeadSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
			//$this->entityData['CLOSED'] = 'N';

			if($this->isFieldHasDefaultValueAttribute($fieldsInfo, 'HONORIFIC'))
			{
				$this->arResult['FIELDS_SET_DEFAULT_VALUE'][] = 'HONORIFIC';
				$honorificList = CCrmStatus::GetStatusList('HONORIFIC');
				$this->defaultEntityData['HONORIFIC'] = current(array_keys($honorificList));
				if($this->isSetDefaultValueForField($fieldsInfo, $requiredFields, 'HONORIFIC'))
				{
					$this->entityData['HONORIFIC'] = $this->defaultEntityData['HONORIFIC'];
				}
			}

			if($this->isFieldHasDefaultValueAttribute($fieldsInfo, 'SOURCE_ID'))
			{
				$this->arResult['FIELDS_SET_DEFAULT_VALUE'][] = 'SOURCE_ID';
				$statusList = CCrmStatus::GetStatusList('SOURCE');
				$this->defaultEntityData['SOURCE_ID'] = current(array_keys($statusList));
				if($this->isSetDefaultValueForField($fieldsInfo, $requiredFields, 'SOURCE_ID'))
				{
					$this->entityData['SOURCE_ID'] = $this->defaultEntityData['SOURCE_ID'];
				}
			}

			//region Default Responsible
			if($this->userID > 0)
			{
				$this->entityData['ASSIGNED_BY_ID'] = $this->userID;
			}
			//endregion

			$this->entityData['IS_MANUAL_OPPORTUNITY'] = 'N';

			//region Default Status ID
			$statusList = $this->prepareStatusList();
			if(!empty($statusList))
			{
				$requestStatusId = $this->request->get('status_id');
				if (isset($statusList[$requestStatusId]))
				{
					$this->entityData['STATUS_ID'] = $requestStatusId;
				}
				else
				{
					$this->entityData['STATUS_ID'] = current(array_keys($statusList));
				}
			}
			//endregion

			if(!empty($this->arResult['DEFAULT_PHONE_VALUE']))
			{
				$this->defaultFieldValues['phone'] = $this->arResult['DEFAULT_PHONE_VALUE'];
			}

			if(!empty($this->arResult['ORIGIN_ID']))
			{
				$this->defaultFieldValues['ORIGIN_ID'] = $this->arResult['ORIGIN_ID'];
			}

			if(isset($this->defaultFieldValues['phone']))
			{
				$phone = trim($this->defaultFieldValues['phone']);
				if($phone !== '')
				{
					$this->entityData['FM']['PHONE'] = array(
						'n0' => array('VALUE' => $phone, 'VALUE_TYPE' => 'WORK'));
				}
			}

			\Bitrix\Crm\Entity\EntityEditor::mapRequestData(
				$this->prepareEntityDataScheme(),
				$this->entityData,
				$this->userFields
			);

			$isTrackingFieldRequired = in_array(Tracking\UI\Details::SourceId, $requiredFields, true);
		}
		else
		{
			$dbResult = CCrmLead::GetListEx(
				[],
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
				$this->entityData = [];
			}

			if(isset($this->arResult['INITIAL_DATA']))
			{
				$this->entityData = array_merge($this->entityData, $this->arResult['INITIAL_DATA']);
			}

			$this->entityData['FORMATTED_NAME'] =
				\CUser::FormatName(
					$this->arResult['NAME_TEMPLATE'],
					array(
						'NAME' => isset($this->entityData['NAME']) ? $this->entityData['NAME'] : '',
						'LAST_NAME' => isset($this->entityData['LAST_NAME']) ? $this->entityData['LAST_NAME'] : '',
						'SECOND_NAME' => $this->entityData['SECOND_NAME'] ? $this->entityData['SECOND_NAME'] : ''
					),
					false,
					false
				);

			$this->entityData['ADDRESS'] = $this->initAddressField($this->entityData);

			if(!isset($this->entityData['OPPORTUNITY']))
			{
				$this->entityData['OPPORTUNITY'] = 0.0;
			}

			if(!isset($this->entityData['CURRENCY_ID']) || $this->entityData['CURRENCY_ID'] === '')
			{
				$this->entityData['CURRENCY_ID'] = \CCrmCurrency::GetBaseCurrencyID();
			}

			//region Default Responsible and Status ID for copy mode
			if($this->isCopyMode)
			{
				if($this->userID > 0)
				{
					$this->entityData['ASSIGNED_BY_ID'] = $this->userID;
				}

				$statusList = $this->prepareStatusList();
				if(!empty($statusList))
				{
					$this->entityData['STATUS_ID'] = current(array_keys($statusList));
				}
			}
			//endregion


			//region Observers
			$this->entityData['OBSERVER_IDS'] = Crm\Observer\ObserverManager::getEntityObserverIDs(
				CCrmOwnerType::Lead,
				$this->entityID
			);
			//endregion

			$this->entityData = Crm\Entity\CommentsHelper::prepareFieldsFromDetailsToView(
				\CCrmOwnerType::Lead,
				$this->entityID,
				$this->entityData,
			);

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

		//region User Fields
		foreach($this->userFields as $userField)
		{
			$fieldName = $userField['FIELD_NAME'];
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
			$this->entityData['OPPORTUNITY'] ?? 0.0,
			$this->entityData['CURRENCY_ID'] ?? null,
			''
		);

		$this->entityData['FORMATTED_OPPORTUNITY_ACCOUNT_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['OPPORTUNITY_ACCOUNT'] ?? 0.0,
			$this->entityData['ACCOUNT_CURRENCY_ID'] ?? null,
			''
		);
		$this->entityData['FORMATTED_OPPORTUNITY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['OPPORTUNITY'] ?? 0.0,
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
				$this->arResult['PATH_TO_USER_PROFILE'] ?? '',
				array('user_id' => $assignedByID)
			);
		}
		//endregion
		//region Client Info
		$clientInfo = [];

		$companyID = (int)($this->entityData['COMPANY_ID'] ?? 0);
		if ($companyID > 0)
		{
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
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
				)
			);
			$clientInfo['COMPANY_DATA'] = array($companyInfo);
		}

		$contactBindings = [];
		if($this->entityID > 0)
		{
			$contactBindings = \Bitrix\Crm\Binding\LeadContactTable::getLeadBindings($this->entityID);
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
		$clientInfo['CONTACT_DATA'] = [];
		$iteration= 0;
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
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
				)
			);
			$iteration++;
		}
		$this->entityData['CLIENT_INFO'] = $clientInfo;

		if ($this->enableSearchHistory)
		{
			$categoryParams = CCrmComponentHelper::getEntityClientFieldCategoryParams(CCrmOwnerType::Lead);
			$this->entityData['LAST_COMPANY_INFOS'] = SearchAction::prepareSearchResultsJson(
				Crm\Controller\Entity::getRecentlyUsedItems(
					'crm.lead.details',
					'company',
					[
						'EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Company,
						'EXPAND_CATEGORY_ID' => $categoryParams[CCrmOwnerType::Company]['categoryId'],
					]
				)
			);
			$this->entityData['LAST_CONTACT_INFOS'] = SearchAction::prepareSearchResultsJson(
				Crm\Controller\Entity::getRecentlyUsedItems(
					'crm.lead.details',
					'contact',
					[
						'EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
						'EXPAND_CATEGORY_ID' => $categoryParams[CCrmOwnerType::Contact]['categoryId'],
					]
				)
			);
		}

		//endregion
		//region Multifield Data
		if ($this->customerType === CustomerType::GENERAL)
		{
			if ($this->entityID > 0)
			{
				\CCrmComponentHelper::prepareMultifieldData(
					\CCrmOwnerType::Lead,
					[$this->entityID],
					[],
					$this->entityData,
					[
						'ADD_TO_DATA_LEVEL' => true,
						'COPY_MODE' => $this->isCopyMode,
					]
				);
			}
			else
			{
				if(isset($this->defaultFieldValues['phone']))
				{
					$phone = trim($this->defaultFieldValues['phone']);
					if($phone !== '')
					{
						$this->entityData['PHONE'] = array(
							array('ID' => 'n0', 'VALUE' => $phone, 'VALUE_TYPE' => 'WORK')
						);
					}
				}
			}
		}
		elseif($this->customerType === CustomerType::RETURNING)
		{
			if ($companyID > 0)
			{
				\CCrmComponentHelper::prepareMultifieldData(
					\CCrmOwnerType::Company,
					[$companyID],
					[
						'PHONE',
						'EMAIL',
						'IM',
					],
					$this->entityData,
					[
						'ADD_TO_DATA_LEVEL' => false,
						'COPY_MODE' => $this->isCopyMode,
					]
				);
			}

			if (!empty($contactIDs))
			{
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
			}
		}
		//endregion

		//region Product row

		$this->entityData['PRODUCT_ROW_SUMMARY'] = $this->getProductRowSummaryData();

		//endregion

		Tracking\UI\Details::prepareEntityData(
			\CCrmOwnerType::Lead,
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
					new Crm\ItemIdentifier(\CCrmOwnerType::Lead, $this->entityID)
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
					$this->entityFieldInfos,
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
			$item = $this->factory->getItem($this->entityID) ?? $this->factory->createItem();
			$mode = $this->getComponentMode();
			$productRowSummaryData = $this->editorAdapter->getProductRowSummaryDataByItem($item, $mode);
		}
		else
		{
			$productRowCount = 0;
			$productRowInfos = [];
			if ($this->entityID > 0)
			{
				$productRows = \CCrmProductRow::LoadRows(\CCrmOwnerTypeAbbr::Lead, $this->entityID);
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
				$productRowSummaryData = [
					'count' => $productRowCount,
					'total' => CCrmCurrency::MoneyToString(
						isset($result['PRICE']) ? round((double)$result['PRICE'], 2) : 0.0,
						$this->entityData['CURRENCY_ID']
					),
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

	protected function prepareStatusList()
	{
		if($this->statuses === null)
		{
			$this->statuses = [];
			$allStatuses = CCrmStatus::GetStatusList('STATUS');
			foreach ($allStatuses as $statusID => $statusTitle)
			{
				$permissionType = $this->isEditMode
					? \CCrmLead::GetStatusUpdatePermissionType($statusID, $this->userPermissions)
					: \CCrmLead::GetStatusCreatePermissionType($statusID, $this->userPermissions);

				if ($permissionType > BX_CRM_PERM_NONE)
				{
					$this->statuses[$statusID] = $statusTitle;
				}
			}
		}
		return $this->statuses;
	}
	protected function getStatusSemanticID()
	{
		return isset($this->entityData['STATUS_ID'])
			? CCrmLead::GetSemanticID($this->entityData['STATUS_ID']) : Bitrix\Crm\PhaseSemantics::UNDEFINED;
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
	protected function initAddressField($fields)
	{
		if(!$this->isLocationModuleIncluded)
		{
			return "";
		}
		$addressFields = [];
		foreach ($fields as $fieldCode=>$fieldValue)
		{
			if (mb_strpos($fieldCode, 'ADDRESS') === 0)
			{
				unset($fields[$fieldCode]);
				$addressValue = (string)$fieldValue;

				if ($addressValue !== '')
				{
					if ($fieldCode !== 'ADDRESS' && $fieldCode !== 'ADDRESS_2')
					{
						$fieldCode = str_replace('ADDRESS_', '', $fieldCode);
					}
					if ($fieldCode === 'ADDRESS')
					{
						$fieldCode = 'ADDRESS_1';
					}
					$addressFields[$fieldCode] = $addressValue;
				}
			}
		}
		if (!empty($addressFields))
		{
			$address = \Bitrix\Crm\EntityAddress::makeLocationAddressByFields($addressFields);
			if ($address)
			{
				if ($this->isCopyMode)
				{
					$address->setId(0);
					$address->setLinks(new AddressLinkCollection());

				}
				return $address->toJson();
			}
		}
		return "";
	}

	protected function getEventTabParams(): array
	{
		return CCrmComponentHelper::getEventTabParams(
			$this->entityID,
			Loc::getMessage('CRM_LEAD_TAB_EVENT'),
			CCrmOwnerType::LeadName,
			$this->arResult
		);
	}

	public function initializeEditorData(): void
	{
		$this->initializeMode();
		$this->initializeReadOnly();
		$this->initializeGuid();
		$this->initializeConfigId();
		$this->initializeDuplicateControl();
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

	private function initializeReadOnly(): void
	{
		$this->arResult['READ_ONLY'] = true;

		if ($this->isEditMode)
		{
			if (\CCrmLead::CheckUpdatePermission($this->entityID, $this->userPermissions))
			{
				$this->arResult['READ_ONLY'] = false;
			}
		}
		elseif (\CCrmLead::CheckCreatePermission($this->userPermissions))
		{
			$this->arResult['READ_ONLY'] = false;
		}
	}

	private function initializeConfigId(): void
	{
		$this->arResult['EDITOR_CONFIG_ID'] = $this->arParams['EDITOR_CONFIG_ID'] ?? $this->getDefaultConfigID();
	}

	private function initializeDuplicateControl(): void
	{
		$this->enableDupControl = DuplicateControl::isControlEnabledFor(CCrmOwnerType::Lead);

		$this->arResult['DUPLICATE_CONTROL'] = [
			'enabled' => $this->enableDupControl,
			'isSingleMode' => $this->isEditMode,
		];

		if ($this->enableDupControl)
		{
			$this->arResult['DUPLICATE_CONTROL']['serviceUrl'] = '/bitrix/components/bitrix/crm.lead.edit/ajax.php?'
				. bitrix_sessid_get();
			$this->arResult['DUPLICATE_CONTROL']['entityTypeName'] = CCrmOwnerType::LeadName;
			$this->arResult['DUPLICATE_CONTROL']['groups'] = [
				'fullName' => [
					'groupType' => 'fullName',
					'groupSummaryTitle' => Loc::getMessage('CRM_LEAD_DUP_CTRL_FULL_NAME_SUMMARY_TITLE'),
				],
				'email' => [
					'groupType' => 'communication',
					'communicationType' => 'EMAIL',
					'groupSummaryTitle' => Loc::getMessage('CRM_LEAD_DUP_CTRL_EMAIL_SUMMARY_TITLE'),
				],
				'phone' => [
					'groupType' => 'communication',
					'communicationType' => 'PHONE',
					'groupSummaryTitle' => Loc::getMessage('CRM_LEAD_DUP_CTRL_PHONE_SUMMARY_TITLE'),
				],
				'companyTitle' => [
					'parameterName' => 'COMPANY_TITLE',
					'groupType' => 'single',
					'groupSummaryTitle' => Loc::getMessage('CRM_LEAD_DUP_CTRL_COMPANY_TTL_SUMMARY_TITLE'),
				],
			];

			if ($this->entityID)
			{
				$this->arResult['DUPLICATE_CONTROL']['ignoredItems'][] = [
					'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
					'ENTITY_ID' => $this->entityID,
				];
			}
		}
	}

	private function initializePath(): void
	{
		global $APPLICATION;

		$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] =
			CrmCheckPath('PATH_TO_USER_PROFILE', $this->arParams['PATH_TO_USER_PROFILE'] ?? null,
				'/company/personal/user/#user_id#/');

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'] ?? null)
			? CSite::GetNameFormat(false)
			: str_replace(["#NOBR#", "#/NOBR#"], ["", ""], $this->arParams['NAME_TEMPLATE'] ?? '');

		$this->arResult['PATH_TO_LEAD_SHOW'] = CrmCheckPath(
			'PATH_TO_LEAD_SHOW',
			$this->arParams['PATH_TO_LEAD_SHOW'] ?? null,
			$APPLICATION->GetCurPage() . '?lead_id=#lead_id#&show'
		);
		$this->arResult['PATH_TO_LEAD_EDIT'] = CrmCheckPath(
			'PATH_TO_LEAD_EDIT',
			$this->arParams['PATH_TO_LEAD_EDIT'] ?? null,
			$APPLICATION->GetCurPage() . '?lead_id=#lead_id#&edit'
		);

		$this->arResult['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath(
			'PATH_TO_PRODUCT_EDIT',
			$this->arParams['PATH_TO_PRODUCT_EDIT'] ?? null,
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
				$this->arParams['PATH_TO_PRODUCT_SHOW'] ?? null,
				$APPLICATION->GetCurPage() . '?product_id=#product_id#&show'
			);
		}
	}

	private function initializeContext(): void
	{
		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::LeadName . '_' . $this->entityID;

		$this->arResult['CONTEXT'] = [
			'PARAMS' => [
				'PATH_TO_PRODUCT_SHOW' => $this->arResult['PATH_TO_PRODUCT_SHOW'],
				'PATH_TO_USER_PROFILE' => $this->arResult['PATH_TO_USER_PROFILE'],
				'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
			],
		];

		Crm\Service\EditorAdapter::addParentItemToContextIfFound($this->arResult['CONTEXT']);

		if ($this->isCopyMode)
		{
			$this->arResult['CONTEXT']['PARAMS']['LEAD_ID'] = $this->entityID;
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
		$this->prepareFieldInfos();
		$this->prepareEntityData();
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

	public function setCategoryID(int $categoryId)
	{
	}

	public function getCategoryID()
	{
		return 0;
	}

	public function prepareConfigId()
	{
		return $this->getDefaultConfigID();
	}

	public function prepareEntityControllers(): array
	{
		if (!isset($this->arResult['ENTITY_CONTROLLERS']))
		{
			$this->arResult['ENTITY_CONTROLLERS'] = [];

			$currencyList = [];

			if (Main\Loader::includeModule('currency'))
			{
				$currencyIterator = Currency\CurrencyTable::getList([
					'select' => ['CURRENCY'],
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
							'HIDE_ZERO' => $currencyFormat['HIDE_ZERO'],
						],
					];
				}
			}

			$this->arResult['ENTITY_CONTROLLERS'][] = [
				'name' => 'PRODUCT_LIST',
				'type' => 'product_list',
				'config' => [
					'productListId' => $this->arResult['PRODUCT_EDITOR_ID'] ?? self::PRODUCT_EDITOR_ID,
					'currencyList' => $currencyList,
					'currencyId' => $this->getCurrencyId(),
				],
			];
		}

		return $this->arResult['ENTITY_CONTROLLERS'];
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
