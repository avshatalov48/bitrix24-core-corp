<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Category\EditorHelper;
use Bitrix\Crm\Component\EntityDetails\Traits;
use Bitrix\Crm\Controller\Action\Entity\SearchAction;
use Bitrix\Crm\Conversion\LeadConversionWizard;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\UtmTable;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));

	return;
}

Loc::loadMessages(__FILE__);

class CCrmContactDetailsComponent
	extends CBitrixComponent
	implements Crm\Integration\UI\EntityEditor\SupportsEditorProvider
{
	private const UTM_FIELD_CODE = 'UTM';

	use Traits\EditorConfig;
	use Traits\InitializeAttributeScope;
	use Traits\InitializeExternalContextId;
	use Traits\InitializeGuid;
	use Traits\InitializeMode;
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
	private $userFieldEntityID = "";
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
	/** @var array|null */
	private $multiFieldInfos = null;
	/** @var array|null */
	private $multiFieldValueTypeInfos = null;
	/** @var bool */
	private $isEditMode = false;
	/** @var bool */
	private $isCopyMode = false;
	/** @var bool */
	private $enableDupControl = false;
	/** @var \Bitrix\Crm\Conversion\EntityConversionWizard|null  */
	private $conversionWizard = null;
	/** @var int */
	private $leadID = 0;
	/** @var bool */
	private $enableOutmodedFields;
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
	private $editorAdapter;
	private $factory;

	public function __construct($component = null)
	{
		/** @global \CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

		parent::__construct($component);

		$this->userID = \CCrmSecurityHelper::GetCurrentUserID();
		$this->userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		$this->userFieldEntityID = \CCrmContact::GetUserFieldEntityID();
		$this->userType = new \CCrmUserType($USER_FIELD_MANAGER, $this->userFieldEntityID);
		$this->userFieldDispatcher = \Bitrix\Main\UserField\Dispatcher::instance();

		$this->multiFieldInfos = CCrmFieldMulti::GetEntityTypeInfos();
		$this->multiFieldValueTypeInfos = CCrmFieldMulti::GetEntityTypes();
		$this->factory = Container::getInstance()->getFactory(\CCrmOwnerType::Contact);
		if ($this->factory)
		{
			$this->editorAdapter = $this->factory->getEditorAdapter();
		}
	}

	public function initializeParams(array $params)
	{
		foreach ($params as $k => $v)
		{
			if ($k === 'COMPONENT_MODE' && is_numeric($v))
			{
				$this->arParams['COMPONENT_MODE'] = (int)$v;
			}
			elseif ($k === 'CATEGORY_ID' && is_numeric($v))
			{
				$this->setCategoryID((int)$v);
			}
			elseif ($k === 'LEAD_ID')
			{
				$this->leadID = $this->arResult['LEAD_ID'] = $this->arParams['LEAD_ID'] = (int)$v;
			}
			elseif($k === 'ORIGIN_ID')
			{
				$this->arResult['ORIGIN_ID'] = $v;
				$this->arParams['ORIGIN_ID'] = $v;
			}

			if (!is_string($v))
			{
				continue;
			}

			if ($k === 'PATH_TO_USER_PROFILE')
			{
				$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] = $v;
			}
			elseif ($k === 'NAME_TEMPLATE')
			{
				$this->arResult['NAME_TEMPLATE'] = $this->arParams['NAME_TEMPLATE'] = $v;
			}
			elseif($k === 'DEFAULT_PHONE_VALUE')
			{
				$this->arResult['DEFAULT_PHONE_VALUE'] = $this->arParams['DEFAULT_PHONE_VALUE'] = (int)$v;
			}
			elseif ($k === 'ENABLE_SEARCH_HISTORY')
			{
				$this->enableSearchHistory($v === 'Y');
			}
		}
	}

	public function executeComponent()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		$this->enableOutmodedFields = false;//\Bitrix\Crm\Settings\ContactSettings::getCurrent()->areOutmodedRequisitesEnabled();
		$this->isLocationModuleIncluded = Main\Loader::includeModule('location');

		//region Params
		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID']) ? (int)$this->arParams['~ENTITY_ID'] : 0;

		$this->arResult['ENABLE_TASK'] = IsModuleInstalled('tasks');
		$this->arResult['ACTION_URI'] = $this->arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;

		$this->arResult['SHOW_EMPTY_FIELDS'] = isset($this->arParams['SHOW_EMPTY_FIELDS'])
			&& $this->arParams['SHOW_EMPTY_FIELDS'];

		$this->defaultFieldValues = array();
		$this->tryGetFieldValueFromRequest('title', $this->defaultFieldValues);
		$this->tryGetFieldValueFromRequest('phone', $this->defaultFieldValues);
		//endregion

		$this->enableSearchHistory = !isset($this->arParams['~ENABLE_SEARCH_HISTORY'])
			|| mb_strtoupper($this->arParams['~ENABLE_SEARCH_HISTORY']) === 'Y';

		$this->setEntityID($this->arResult['ENTITY_ID']);

		if ($this->entityID > 0 && !\CCrmContact::Exists($this->entityID))
		{
			Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::EntityNotExist, CCrmOwnerType::Contact);

			return;
		}

		$this->arResult['CATEGORY_ID'] = $this->getCategoryId();

		//region Permissions check
		$this->initializeMode();

		if ($this->isCopyMode)
		{
			if (!\CCrmContact::CheckReadPermission($this->entityID, $this->userPermissions, $this->arResult['CATEGORY_ID']))
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoReadPermission, CCrmOwnerType::Contact);

				return;
			}
			elseif (!\CCrmContact::CheckCreatePermission($this->userPermissions, $this->arResult['CATEGORY_ID']))
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoAddPermission, CCrmOwnerType::Contact);

				return;
			}
		}
		elseif ($this->isEditMode)
		{
			if (
				!\CCrmContact::CheckUpdatePermission(0)
				&& !\CCrmContact::CheckReadPermission()
			)
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoAccessToEntityType, CCrmOwnerType::Contact);

				return;
			}
			elseif (
				!\CCrmContact::CheckUpdatePermission($this->entityID, $this->userPermissions, $this->arResult['CATEGORY_ID'])
				&& !\CCrmContact::CheckReadPermission($this->entityID, $this->userPermissions, $this->arResult['CATEGORY_ID'])
			)
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoReadPermission, CCrmOwnerType::Contact);

				return;
			}
		}
		elseif (!\CCrmContact::CheckCreatePermission($this->userPermissions, $this->arResult['CATEGORY_ID']))
		{
			Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoAddPermission, CCrmOwnerType::Contact);

			return;
		}
		//endregion

		$this->prepareEntityUserFields();
		$this->prepareEntityUserFieldInfos();

		$this->initializeEditorData();

		//region Entity Info
		$this->arResult['ENTITY_INFO'] = array(
			'ENTITY_ID' => $this->entityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::ContactName,
			'TITLE' => isset($this->entityData['FORMATTED_NAME']) ? $this->entityData['FORMATTED_NAME'] : '',
			'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Contact, $this->entityID, false),
		);
		//endregion

		//region Page title
		if($this->isCopyMode)
		{
			$APPLICATION->SetTitle(
				Crm\Category\NamingHelper::getInstance()->getLangPhrase(
					'CRM_CONTACT_COPY_PAGE_TITLE',
					$this->arResult['CATEGORY_ID']
				)
			);
		}
		elseif(isset($this->entityData['FORMATTED_NAME']))
		{
			$APPLICATION->SetTitle(htmlspecialcharsbx($this->entityData['FORMATTED_NAME']));
		}
		elseif(!$this->isEditMode)
		{
			$APPLICATION->SetTitle(
				Crm\Category\NamingHelper::getInstance()->getLangPhrase(
					'CRM_CONTACT_CREATION_PAGE_TITLE',
					$this->arResult['CATEGORY_ID']
				)
			);
		}
		//endregion

		//region TABS
		$this->arResult['TABS'] = array();

		$relationManager = Crm\Service\Container::getInstance()->getRelationManager();
		if (!$this->arResult['CATEGORY_ID'])
		{
			$this->arResult['TABS'] = array_merge(
				$this->arResult['TABS'],
				$relationManager->getRelationTabsForDynamicChildren(
					\CCrmOwnerType::Contact,
					$this->entityID,
					($this->entityID === 0)
				)
			);
		}

		if($this->entityID > 0)
		{
			if (!$this->arResult['CATEGORY_ID'])
			{
				$this->arResult['TABS'][] = [
					'id' => 'tab_deal',
					'name' => Loc::getMessage('CRM_CONTACT_TAB_DEAL'),
					'loader' => [
						'serviceUrl' => '/bitrix/components/bitrix/crm.deal.list/lazyload.ajax.php?&site'
							. SITE_ID
							. '&'
							. bitrix_sessid_get(),
						'componentData' => [
							'template' => '',
							'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
								'DEAL_COUNT' => '20',
								'PATH_TO_DEAL_SHOW' => $this->arResult['PATH_TO_DEAL_SHOW'] ?? '',
								'PATH_TO_DEAL_EDIT' => $this->arResult['PATH_TO_DEAL_EDIT'] ?? '',
								'INTERNAL_FILTER' => [
									'ASSOCIATED_CONTACT_ID' => $this->entityID,
								],
								'INTERNAL_CONTEXT' => [
									'CONTACT_ID' => $this->entityID,
								],
								'GRID_ID_SUFFIX' => 'CONTACT_DETAILS',
								'TAB_ID' => 'tab_deal',
								'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'] ?? '',
								'ENABLE_TOOLBAR' => true,
								'PRESERVE_HISTORY' => true,
								'ADD_EVENT_NAME' => 'CrmCreateDealFromContact',
								'ANALYTICS' => [
									// we dont know where from this component was opened from - it could be anywhere on portal
									'c_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_CONTACT,
									'c_sub_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_DETAILS,
								],
							], 'crm.deal.list')
						]
					]
				];

				$tabQuote = [
					'id' => 'tab_quote',
					'name' => Loc::getMessage('CRM_CONTACT_TAB_QUOTE_MSGVER_1'),
					'loader' => [
						'serviceUrl' => '/bitrix/components/bitrix/crm.quote.list/lazyload.ajax.php?&site'
							. SITE_ID
							. '&'
							. bitrix_sessid_get(),
						'componentData' => [
							'template' => '',
							'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
								'QUOTE_COUNT' => '20',
								'PATH_TO_QUOTE_SHOW' => $this->arResult['PATH_TO_QUOTE_SHOW'] ?? '',
								'PATH_TO_QUOTE_EDIT' => $this->arResult['PATH_TO_QUOTE_EDIT'] ?? '',
								'INTERNAL_FILTER' => ['ASSOCIATED_CONTACT_ID' => $this->entityID],
								'INTERNAL_CONTEXT' => ['CONTACT_ID' => $this->entityID],
								'GRID_ID_SUFFIX' => 'CONTACT_DETAILS',
								'TAB_ID' => 'tab_quote',
								'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'] ?? '',
								'ENABLE_TOOLBAR' => true,
								'PRESERVE_HISTORY' => true,
								'ADD_EVENT_NAME' => 'CrmCreateQuoteFromContact',
								'ANALYTICS' => [
									// we dont know where from this component was opened from - it could be anywhere on portal
									'c_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_CONTACT,
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
			}

			if (
				!$this->arResult['CATEGORY_ID']
				&& Crm\Settings\InvoiceSettings::getCurrent()->isOldInvoicesEnabled()
			)
			{
				$tabInvoice = [
					'id' => 'tab_invoice',
					'name' => \CCrmOwnerType::GetCategoryCaption(\CCrmOwnerType::Invoice),
					'loader' => [
						'serviceUrl' => '/bitrix/components/bitrix/crm.invoice.list/lazyload.ajax.php?&site'
							.SITE_ID
							.'&'
							.bitrix_sessid_get(),
						'componentData' => [
							'template' => '',
							'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
								'INVOICE_COUNT' => '20',
								'PATH_TO_COMPANY_SHOW' => $this->arResult['PATH_TO_COMPANY_SHOW'] ?? '',
								'PATH_TO_COMPANY_EDIT' => $this->arResult['PATH_TO_COMPANY_EDIT'] ?? '',
								'PATH_TO_CONTACT_EDIT' => $this->arResult['PATH_TO_CONTACT_EDIT'] ?? '',
								'PATH_TO_DEAL_EDIT' => $this->arResult['PATH_TO_DEAL_EDIT'] ?? '',
								'PATH_TO_INVOICE_EDIT' => $this->arResult['PATH_TO_INVOICE_EDIT'] ?? '',
								'PATH_TO_INVOICE_PAYMENT' => $this->arResult['PATH_TO_INVOICE_PAYMENT'] ?? '',
								'INTERNAL_FILTER' => ['UF_CONTACT_ID' => $this->entityID],
								'SUM_PAID_CURRENCY' => \CCrmCurrency::GetBaseCurrencyID(),
								'GRID_ID_SUFFIX' => 'CONTACT_DETAILS',
								'TAB_ID' => 'tab_invoice',
								'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'] ?? '',
								'ENABLE_TOOLBAR' => 'Y',
								'PRESERVE_HISTORY' => true,
								'ADD_EVENT_NAME' => 'CrmCreateInvoiceFromContact',
								'ANALYTICS' => [
									// we dont know where from this component was opened from - it could be anywhere on portal
									'c_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_CONTACT,
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
				&& !$this->arResult['CATEGORY_ID']
			)
			{
				$this->arResult['TABS'][] = array(
					'id' => 'tab_order',
					'name' => Loc::getMessage('CRM_CONTACT_TAB_ORDERS'),
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
								'INTERNAL_FILTER' => array('ASSOCIATED_CONTACT_ID' => $this->entityID),
								'SUM_PAID_CURRENCY' => \CCrmCurrency::GetBaseCurrencyID(),
								'GRID_ID_SUFFIX' => 'CONTACT_DETAILS',
								'TAB_ID' => 'tab_order',
								'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'] ?? '',
								'ENABLE_TOOLBAR' => 'Y',
								'PRESERVE_HISTORY' => true,
								'ADD_EVENT_NAME' => 'CrmCreateOrderFromContact',
								'BUILDER_CONTEXT' => Crm\Product\Url\ProductBuilder::TYPE_ID,
								'ANALYTICS' => [
									// we dont know where from this component was opened from - it could be anywhere on portal
									'c_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_CONTACT,
									'c_sub_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_DETAILS,
								],
							], 'crm.order.list')
						)
					)
				);
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
						'name' => Loc::getMessage('CRM_CONTACT_TAB_BIZPROC'),
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
									'ENTITY' => 'CCrmDocumentContact',
									'DOCUMENT_TYPE' => 'CONTACT',
									'DOCUMENT_ID' => 'CONTACT_'.$this->entityID
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
						'entity' => 'CCrmDocumentContact',
						'documentType' => 'CONTACT',
						'documentId' => 'CONTACT_' . $this->entityID,
					];
				}
			}
			$this->arResult['TABS'][] = array(
				'id' => 'tab_tree',
				'name' => Loc::getMessage('CRM_CONTACT_TAB_TREE'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.entity.tree/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '.default',
						'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
							'ENTITY_ID' => $this->entityID,
							'ENTITY_TYPE_NAME' => CCrmOwnerType::ContactName,
						], 'crm.entity.tree')
					)
				)
			);
			$this->arResult['TABS'][] = $this->getEventTabParams();

			if (CModule::IncludeModule('lists') && !$this->arResult['CATEGORY_ID'])
			{
				$listIblock = CLists::getIblockAttachedCrm(CCrmOwnerType::ContactName);
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
									'ENTITY_TYPE' => CCrmOwnerType::Contact,
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
			if (!$this->arResult['CATEGORY_ID'])
			{
				$this->arResult['TABS'][] = [
					'id' => 'tab_deal',
					'name' => Loc::getMessage('CRM_CONTACT_TAB_DEAL'),
					'enabled' => false
				];
				$this->arResult['TABS'][] = [
					'id' => 'tab_quote',
					'name' => Loc::getMessage('CRM_CONTACT_TAB_QUOTE_MSGVER_1'),
					'enabled' => false
				];
				$this->arResult['TABS'][] = [
					'id' => 'tab_invoice',
					'name' => Loc::getMessage('CRM_CONTACT_TAB_INVOICES'),
					'enabled' => false
				];
			}
			if (CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled())
			{
				$this->arResult['TABS'][] = array(
					'id' => 'tab_bizproc',
					'name' => Loc::getMessage('CRM_CONTACT_TAB_BIZPROC'),
					'enabled' => false
				);
			}
			$this->arResult['TABS'][] = $this->getEventTabParams();

			if (CModule::IncludeModule('lists') && !$this->arResult['CATEGORY_ID'])
			{
				$listIblock = CLists::getIblockAttachedCrm(CCrmOwnerType::ContactName);
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

		//region VIEW EVENT
		if($this->entityID > 0 && \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isViewEventEnabled())
		{
			CCrmEvent::RegisterViewEvent(CCrmOwnerType::Contact, $this->entityID, $this->userID);
		}
		//endregion

		$this->includeComponentTemplate();
	}

	public function loadConversionWizard(): void
	{
		if ($this->conversionWizard !== null || $this->leadID === 0)
		{
			return;
		}

		$this->conversionWizard = LeadConversionWizard::load($this->leadID);
	}

	public function getDefaultGuid(): string
	{
		return "contact_{$this->entityID}_details";
	}

	public function getDefaultConfigID()
	{
		return $this->getEditorConfigId();
	}

	public function prepareConfigID()
	{
		return $this->getDefaultConfigID();
	}

	public function prepareConfiguration()
	{
		if(isset($this->arResult['ENTITY_CONFIG']))
		{
			return $this->arResult['ENTITY_CONFIG'];
		}

		$multiFieldConfigElements = array();
		foreach(array_keys($this->multiFieldInfos) as $fieldName)
		{
			$multiFieldConfigElements[] = array('name' => $fieldName);
		}

		$userFieldConfigElements = array();
		foreach(array_keys($this->prepareEntityUserFieldInfos()) as $fieldName)
		{
			$userFieldConfigElements[] = array('name' => $fieldName);
		}

		$this->arResult['ENTITY_CONFIG'] = array(
			array(
				'name' => 'main',
				'title' => Loc::getMessage('CRM_CONTACT_SECTION_MAIN'),
				'type' => 'section',
				'elements' =>
					array_merge(
						array(
							array('name' => 'HONORIFIC'),
							array('name' => 'LAST_NAME'),
							array('name' => 'NAME'),
							array('name' => 'SECOND_NAME'),
							array('name' => 'PHOTO'),
							array('name' => 'BIRTHDATE'),
							array('name' => 'POST')
						),
						$multiFieldConfigElements,
						array(
							array('name' => 'COMPANY'),
							array('name' => 'ADDRESS'),
							array('name' => 'REQUISITES'),
						)
					)
			),
			array(
				'name' => 'additional',
				'title' => Loc::getMessage('CRM_CONTACT_SECTION_ADDITIONAL'),
				'type' => 'section',
				'elements' =>
					array_merge(
						array(
							array('name' => 'TYPE_ID'),
							array('name' => 'SOURCE_ID'),
							array('name' => 'SOURCE_DESCRIPTION'),
							array('name' => 'OPENED'),
							array('name' => 'EXPORT'),
							array('name' => 'ASSIGNED_BY_ID'),
							array('name' => 'OBSERVER'),
							array('name' => 'COMMENTS'),
							array('name' => self::UTM_FIELD_CODE),
						),
						$userFieldConfigElements
					)
			)
		);

		return $this->arResult['ENTITY_CONFIG'];
	}

	public function prepareEntityControllers(): array
	{
		if (!isset($this->arResult['ENTITY_CONTROLLERS']))
		{
			$this->arResult['ENTITY_CONTROLLERS'] = [
				[
					'name' => 'REQUISITE_CONTROLLER',
					'type' => 'requisite_controller',
					'config' => [
						'requisiteFieldId' => 'REQUISITES',
						'addressFieldId' => 'ADDRESS',
						'entityCategoryId' => $this->getCategoryId(),
					],
				],
			];
		}

		return $this->arResult['ENTITY_CONTROLLERS'];
	}

	public function isSearchHistoryEnabled()
	{
		return $this->enableSearchHistory;
	}
	public function enableSearchHistory($enable)
	{
		$this->enableSearchHistory = (bool)$enable;
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
		$this->prepareEntityUserFields();

		$this->userFieldInfos = null;
		$this->prepareEntityUserFieldInfos();

		$this->entityData = null;
	}

	public function setCategoryID(int $categoryID): void
	{
		$this->arResult['CATEGORY_ID'] = $categoryID;
	}

	public function prepareEntityDataScheme()
	{
		if($this->entityDataScheme === null)
		{
			$this->entityDataScheme = \CCrmContact::GetFieldsInfo();
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
			/*[
				'type' => 'person',
				'message' => Loc::getMessage('CRM_CONTACT_PERSON_VALIDATOR_MESSAGE'),
				'data' => [
					'nameField' => 'NAME',
					'lastNameField' => 'LAST_NAME'
				]
			],*/
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

		$dateFormat = Main\Type\Date::convertFormatToPhp(Main\Application::getInstance()->getContext()->getCulture()->getDateFormat());

		$observersRestriction = RestrictionManager::getObserversRestriction();

		$fakeValue = '';
		$categoryParams = CCrmComponentHelper::getEntityClientFieldCategoryParams(
			CCrmOwnerType::Contact,
			(int)($this->arResult['CATEGORY_ID'] ?? $this->getCategoryId())
		);
		$this->entityFieldInfos = array(
			array(
				'name' => 'ID',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_ID'),
				'type' => 'text',
				'editable' => false,
				'enableAttributes' => false
			),
			array(
				'name' => 'HONORIFIC',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_HONORIFIC'),
				'type' => 'list',
				'editable' => true,
				'data' => [
					'items'=> \CCrmInstantEditorHelper::PrepareListOptions(
						CCrmStatus::GetStatusList('HONORIFIC'),
						[
							'NOT_SELECTED' => Loc::getMessage('CRM_CONTACT_HONORIFIC_NOT_SELECTED'),
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
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_LAST_NAME'),
				'type' => 'text',
				'isHeading' => true,
				'visibilityPolicy' => 'edit',
				'placeholders' => array('creation' => \CCrmContact::GetDefaultTitle()),
				'editable' => true,
				'data' => array('duplicateControl' => array('groupId' => 'fullName', 'field' => array('id' => 'LAST_NAME')))
			),
			array(
				'name' => 'NAME',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_NAME'),
				'type' => 'text',
				'visibilityPolicy' => 'edit',
				'editable' => true,
				'data' => array('duplicateControl' => array('groupId' => 'fullName', 'field' => array('id' => 'NAME')))
			),
			array(
				'name' => 'SECOND_NAME',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_SECOND_NAME'),
				'type' => 'text',
				'visibilityPolicy' => 'edit',
				'editable' => true,
				'data' => array('duplicateControl' => array('groupId' => 'fullName', 'field' => array('id' => 'SECOND_NAME')))
			),
			array(
				'name' => 'PHOTO',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_PHOTO'),
				'type' => 'image',
				'editable' => true,
				'data' => array('showUrl' => 'PHOTO_SHOW_URL')
			),
			array(
				'name' => 'BIRTHDATE',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_BIRTHDATE'),
				'type' => 'datetime',
				'editable' => true,
				'data' =>  [
					'enableTime' => false,
					'defaultValue' => $this->defaultEntityData['BIRTHDATE'] ?? null,
					'dateViewFormat' => $dateFormat
				]
			),
			array(
				'name' => 'POST',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_POST'),
				'type' => 'text',
				'editable' => true
			),
			Crm\Entity\CommentsHelper::compileFieldDescriptionForDetails(
				\CCrmOwnerType::Contact,
				'COMMENTS',
				$this->entityID,
			),
			array(
				'name' => 'ASSIGNED_BY_ID',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_ASSIGNED_BY_ID'),
				'type' => 'user',
				'editable' => true,
				'data' => array(
					'enableEditInView' => true,
					'formated' => 'ASSIGNED_BY_FORMATTED_NAME',
					'position' => 'ASSIGNED_BY_WORK_POSITION',
					'photoUrl' => 'ASSIGNED_BY_PHOTO_URL',
					'showUrl' => 'PATH_TO_ASSIGNED_BY_USER',
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE']
				),
				'enableAttributes' => false
			),
			array(
				'name' => 'OBSERVER',
				'title' => Loc::getMessage('CRM_TYPE_ITEM_FIELD_OBSERVERS'),
				'type' => 'multiple_user',
				'editable' => true,
				'data' => array(
					'enableEditInView' => true,
					'map' => array('data' => 'OBSERVER_IDS'),
					'infos' => 'OBSERVER_INFOS',
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE'] ?? null,
					'messages' => array('addObserver' => Loc::getMessage('CRM_COMMON_ACTION_ADD_OBSERVER')),
					'restriction' => [
						'isRestricted' => !$observersRestriction->hasPermission(),
						'action' => $observersRestriction->prepareInfoHelperScript(),
					],
				)
			),
			array(
				'name' => 'OPENED',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_OPENED'),
				'type' => 'boolean',
				'editable' => true
			),
			array(
				'name' => 'EXPORT',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_EXPORT_NEW'),
				'type' => 'boolean',
				'editable' => true
			),
			array(
				'name' => 'TYPE_ID',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_TYPE_ID'),
				'type' => 'list',
				'editable' => true,
				'data' => [
					'items'=> \CCrmInstantEditorHelper::PrepareListOptions(
						$this->prepareTypeList(),
						[
							'NOT_SELECTED' => Loc::getMessage('CRM_CONTACT_SOURCE_NOT_SELECTED'),
							'NOT_SELECTED_VALUE' => $fakeValue
						]
					),
					'defaultValue' => $this->defaultEntityData['TYPE_ID'] ?? null,
					'innerConfig' => \CCrmInstantEditorHelper::prepareInnerConfig(
						'crm_status',
						'crm.status.setItems',
						'CONTACT_TYPE',
						[$fakeValue]
					),
				]
			),
			array(
				'name' => 'SOURCE_ID',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_SOURCE_ID'),
				'type' => 'list',
				'editable' => true,
				'data' => [
					'items'=> \CCrmInstantEditorHelper::PrepareListOptions(
						CCrmStatus::GetStatusList('SOURCE'),
						[
							'NOT_SELECTED' => Loc::getMessage('CRM_CONTACT_SOURCE_NOT_SELECTED'),
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
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_SOURCE_DESCRIPTION'),
				'type' => 'text',
				'data' => array('lineCount' => 6),
				'editable' => true
			),
			array(
				'name' => 'COMPANY',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_COMPANY'),
				'type' => 'client_light',
				'editable' => true,
				'data' => array(
					'affectedFields' => ['CLIENT_INFO'],
					'compound' => array(
						array(
							'name' => 'COMPANY_IDS',
							'type' => 'multiple_company',
							'entityTypeName' => \CCrmOwnerType::CompanyName,
							'tagName' => \CCrmOwnerType::CompanyName
						)
					),
					'categoryParams' => $categoryParams,
					'map' => array('data' => 'CLIENT_DATA'),
					'info' => 'CLIENT_INFO',
					'fixedLayoutType' => 'COMPANY',
					'enableCompanyMultiplicity' => true,
					'lastCompanyInfos' => 'LAST_COMPANY_INFOS',
					'companyLegend' => Loc::getMessage('CRM_CONTACT_FIELD_COMPANY_LEGEND'),
					'loaders' => array(
						'primary' => array(
							CCrmOwnerType::CompanyName => array(
								'action' => 'GET_CLIENT_INFO',
								'url' => '/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get()
							)
						)
					),
					'clientEditorFieldsParams' => CCrmComponentHelper::prepareClientEditorFieldsParams(
						['categoryParams' => $categoryParams]
					),
					'duplicateControl' => CCrmComponentHelper::prepareClientEditorDuplicateControlParams(
						['entityTypes' => [CCrmOwnerType::Company, CCrmOwnerType::Contact]]
					),
				)
			),
			[
				'name' => 'REQUISITES',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_REQUISITES'),
				'type' => 'requisite',
				'editable' => true,
				'data' => \CCrmComponentHelper::getFieldInfoData(
					CCrmOwnerType::Contact,
					'requisite',
					['IS_EDIT_MODE' => $this->isRequisiteEditMode()]
				),
				'enableAttributes' => false,
			]
		);

		$category = $this->getCategory();
		if (!$category || $category->isTrackingEnabled())
		{
			Tracking\UI\Details::appendEntityFields($this->entityFieldInfos);
		}
		$this->entityFieldInfos[] = array(
			'name' => self::UTM_FIELD_CODE,
			'title' => Loc::getMessage('CRM_CONTACT_FIELD_UTM'),
			'type' => 'custom',
			'data' => array(
				'view' => 'UTM_VIEW_HTML',
			),
			'editable' => false,
			'enableAttributes' => false
		);

		if($this->enableOutmodedFields)
		{
			$this->entityFieldInfos[] = array(
				'name' => 'ADDRESS',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_ADDRESS'),
				'type' => 'address_form',
				'editable' => true,
				'enableAttributes' => false,
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
					'labels' => EntityAddress::getLabels(),
					'view' => 'ADDRESS_HTML'
				)
			);
		}
		elseif (CModule::IncludeModule('location'))
		{

			$this->entityFieldInfos[] = array(
				'name' => 'ADDRESS',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_ADDRESS'),
				'type' => 'requisite_address',
				'editable' => true,
				'enableAttributes' => false,
				'virtual' => true,
				'data' => \CCrmComponentHelper::getRequisiteAddressFieldData(
					CCrmOwnerType::Contact,
					$this->getCategoryId()
				)
			);
		}

		foreach($this->multiFieldInfos as $typeName => $typeInfo)
		{
			$valueTypes = $this->multiFieldValueTypeInfos[$typeName] ?? [];

			$valueTypeItems = array();
			foreach($valueTypes as $valueTypeId => $valueTypeInfo)
			{
				$valueTypeItems[] = array(
					'NAME' => isset($valueTypeInfo['SHORT']) ? $valueTypeInfo['SHORT'] : $valueTypeInfo['FULL'],
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
		$this->entityFieldInfos = array_merge(
			$this->entityFieldInfos,
			array_values($this->userFieldInfos)
		);
		if ($this->editorAdapter)
		{
			$parentFieldsInfo = $this->editorAdapter->getParentFieldsInfo(\CCrmOwnerType::Contact);
			$this->entityFieldInfos = array_merge(
				$this->entityFieldInfos,
				array_values($parentFieldsInfo)
			);
		}

		// filter out category-specific disabled fields
		if ($category)
		{
			$disabledFieldNames = $category->getDisabledFieldNames();
			$areUtmFieldsDisabled = !empty(
				array_filter(
					$disabledFieldNames,
					static function ($disabledFieldName)
					{
						return in_array($disabledFieldName, UtmTable::getCodeList(), true);
					}
				)
			);
			$this->entityFieldInfos = array_values(
				array_filter(
					$this->entityFieldInfos,
					function ($field) use ($disabledFieldNames, $areUtmFieldsDisabled)
					{
						$fieldNames = (
							isset($field['data']['affectedFields'])
							&& is_array($field['data']['affectedFields'])
						)
							? $field['data']['affectedFields']
							: [$field['name']];

						foreach ($fieldNames as $fieldName)
						{
							$isInDisabledFieldsList = (
								(
									$fieldName === self::UTM_FIELD_CODE
									&& $areUtmFieldsDisabled
								)
								|| in_array(
									$this->factory->getCommonFieldNameByMap($fieldName),
									$disabledFieldNames,
									true
								)
							);
							if ($isInDisabledFieldsList)
							{
								return false;
							}
						}

						return true;
					}
				)
			);
		}

		$this->arResult['ENTITY_FIELDS'] = $this->entityFieldInfos;

		return $this->entityFieldInfos;
	}

	public function prepareEntityUserFields()
	{
		if($this->userFields === null)
		{
			$categoryId = $this->arResult['CATEGORY_ID'] ?? $this->getCategoryId();
			$this->userFields = $this->userType
				->setOption(['categoryId' => $categoryId])
				->GetEntityFields($this->entityID)
			;
		}

		return $this->userFields;
	}

	public function prepareEntityFieldAttributeConfigs()
	{
		if(!$this->entityFieldAttributeConfig)
		{
			$this->entityFieldAttributeConfig = FieldAttributeManager::getEntityConfigurations(
				CCrmOwnerType::Contact,
				FieldAttributeManager::resolveEntityScope(
					CCrmOwnerType::Contact,
					$this->entityID,
					['CATEGORY_ID' => $this->arResult['CATEGORY_ID']]
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

		$visibilityConfig = $this->prepareEntityFieldvisibilityConfigs(CCrmOwnerType::Contact);

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
			)
		);
	}
	protected function isSetDefaultValueForField(array $fieldsInfo, array $requiredFields, string $fieldName): bool
	{
		// if field is not required and has an attribute
		return (
			!in_array($fieldName, $requiredFields, true)
			&& $this->isFieldHasDefaultValueAttribute($fieldsInfo, $fieldName)
		);
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
					'HONORIFIC',                         // list
					'LAST_NAME',                         // text
					'NAME',                              // text
					'SECOND_NAME',                       // text
					'PHOTO',                             // image
					'BIRTHDATE',                         // datetime
					'POST',                              // text
					'PHONE',                             // multifield
					'EMAIL',                             // multifield
					'WEB',                               // multifield
					'IM',                                // multifield
					'COMPANY',                           // client_light
					Tracking\UI\Details::SourceId,       // custom
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
						case 'image':
							if (array_key_exists($fieldName, $this->entityData)
								&& $this->entityData[$fieldName] <= 0)
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
						case 'multifield':
							if (!is_array($this->entityData[$fieldName])
								|| empty($this->entityData[$fieldName]))
							{
								$result = true;
								$isResultReady = true;
							}
							break;
						case 'client_light':
							if ($fieldName === 'COMPANY')
							{
								if (is_array($this->entityData['CLIENT_INFO'])
									&& (!is_array($this->entityData['CLIENT_INFO']['COMPANY_DATA'])
										|| empty($this->entityData['CLIENT_INFO']['COMPANY_DATA'])))
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
	public function prepareEntityData()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		if($this->entityData)
		{
			return $this->entityData;
		}

		$isTrackingFieldRequired = false;

		$file = new \CFile();
		if($this->conversionWizard !== null)
		{
			$this->entityData = array();
			$mappedUserFields = array();
			\Bitrix\Crm\Entity\EntityEditor::prepareConvesionMap(
				$this->conversionWizard,
				CCrmOwnerType::Contact,
				$this->entityData,
				$mappedUserFields
			);

			foreach($mappedUserFields as $k => $v)
			{
				if(isset($this->userFields[$k]))
				{
					$this->userFields[$k]['VALUE'] = $v;
				}
			}
			//endregion
		}
		else if($this->entityID <= 0)
		{
			$requiredFields = Crm\Attribute\FieldAttributeManager::isEnabled()
				? Crm\Attribute\FieldAttributeManager::getRequiredFields(
					CCrmOwnerType::Contact,
					$this->entityID,
					['HONORIFIC', 'TYPE_ID', 'SOURCE_ID', Tracking\UI\Details::SourceId],
					Crm\Attribute\FieldOrigin::SYSTEM,
					['CATEGORY_ID' => $this->arResult['CATEGORY_ID']]
				)
				: [];
			$isTrackingFieldRequired = in_array(Tracking\UI\Details::SourceId, $requiredFields, true);
			$fieldsInfo = CCrmContact::GetFieldsInfo();
			$this->entityData = [];
			$this->entityData['OPENED'] = \Bitrix\Crm\Settings\ContactSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
			$this->entityData['EXPORT'] = 'Y';

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
			unset($typeList);

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

			\Bitrix\Crm\Entity\EntityEditor::mapRequestData(
				$this->prepareEntityDataScheme(),
				$this->entityData,
				$this->userFields
			);

			if(isset($this->defaultFieldValues['title']))
			{
				\Bitrix\Crm\Format\PersonNameFormatter::tryParseName(
					$this->defaultFieldValues['title'],
					\Bitrix\Crm\Format\PersonNameFormatter::getFormatID(),
					$this->entityData
				);
			}

			if(!empty($this->arResult['DEFAULT_PHONE_VALUE']))
			{
				$this->defaultFieldValues['phone'] = $this->arResult['DEFAULT_PHONE_VALUE'];
			}

			if(!empty($this->arResult['ORIGIN_ID']))
			{
				$this->defaultFieldValues['ORIGIN_ID'] = $this->arResult['ORIGIN_ID'];
			}
		}
		else
		{
			$dbResult = \CCrmContact::GetListEx(
				array(),
				array('=ID' => $this->entityID, 'CHECK_PERMISSIONS' => 'N')
			);

			if(is_object($dbResult))
			{
				$this->entityData = $dbResult->Fetch();
			}

			if(!is_array($this->entityData))
			{
				return ($this->arResult['ENTITY_DATA'] = $this->entityData = array());
			}

			$this->entityData['FORMATTED_NAME'] =
				\CCrmContact::PrepareFormattedName(
					array(
						'HONORIFIC' => isset($this->entityData['HONORIFIC']) ? $this->entityData['HONORIFIC'] : '',
						'NAME' => isset($this->entityData['NAME']) ? $this->entityData['NAME'] : '',
						'LAST_NAME' => isset($this->entityData['LAST_NAME']) ? $this->entityData['LAST_NAME'] : '',
						'SECOND_NAME' => $this->entityData['SECOND_NAME'] ? $this->entityData['SECOND_NAME'] : ''
					)
				);

			if($this->isCopyMode)
			{
				if($this->userID > 0)
				{
					$this->entityData['ASSIGNED_BY_ID'] = $this->userID;
				}

				unset($this->entityData['PHOTO']);
			}

			//region Observers
			$this->entityData['OBSERVER_IDS'] = Crm\Observer\ObserverManager::getEntityObserverIDs(
				CCrmOwnerType::Contact,
				$this->entityID
			);
			//endregion

			$this->entityData = Crm\Entity\CommentsHelper::prepareFieldsFromDetailsToView(
				\CCrmOwnerType::Contact,
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

		if (isset($this->entityData['CATEGORY_ID']))
		{
			$this->arResult['CATEGORY_ID'] = (int)$this->entityData['CATEGORY_ID'];
		}

		if (!isset($this->entityData['CATEGORY_ID']))
		{
			$this->entityData['CATEGORY_ID'] = (int)($this->arResult['CATEGORY_ID'] ?? 0);
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
				$this->arResult['PATH_TO_USER_PROFILE'],
				array('user_id' => $assignedByID)
			);
		}
		//endregion
		//region Observers
		if (isset($this->entityData['OBSERVER_IDS']) && !empty($this->entityData['OBSERVER_IDS']))
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
		//region Company Data & Multifield Data
		$companyData = array();
		if($this->entityID > 0)
		{
			\CCrmComponentHelper::prepareMultifieldData(
				\CCrmOwnerType::Contact,
				[$this->entityID],
				[],
				$this->entityData,
				[
					'ADD_TO_DATA_LEVEL' => true,
					'COPY_MODE' => $this->isCopyMode,
				]
			);

			//region Requisites
			if (!$this->isCopyMode)
			{
				$this->entityData['REQUISITES'] = \CCrmEntitySelectorHelper::PrepareRequisiteData(
					CCrmOwnerType::Contact,
					$this->entityID,
					['VIEW_FORMATTED' => true, 'ADDRESS_AS_JSON' => true]
				);
			}
			//endregion
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

		$companyIDs = array();
		if($this->entityID > 0)
		{
			$companyIDs = Bitrix\Crm\Binding\ContactCompanyTable::getContactCompanyIDs($this->entityID);
		}
		elseif($this->conversionWizard !== null && isset($this->entityData['COMPANY_ID']))
		{
			$companyIDs = array($this->entityData['COMPANY_ID']);
		}
		else
		{
			$companyID = $this->request->get('company_id');
			if($companyID > 0)
			{
				$companyIDs = array((int)$companyID);
			}
		}

		foreach($companyIDs as $companyID)
		{
			$isEntityReadPermitted = CCrmCompany::CheckReadPermission($companyID, $this->userPermissions);
			$companyData[] = CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::CompanyName,
				$companyID,
				array(
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_MULTIFIELDS' => true,
					'NORMALIZE_MULTIFIELDS' => true,
					'REQUIRE_BINDINGS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
				)
			);
		}
		$this->entityData['CLIENT_INFO'] = array('COMPANY_DATA' => $companyData);

		if ($this->enableSearchHistory)
		{
			$categoryParams = CCrmComponentHelper::getEntityClientFieldCategoryParams(
				CCrmOwnerType::Contact,
				(int)($this->arResult['CATEGORY_ID'] ?? $this->getCategoryId())
			);
			$this->entityData['LAST_COMPANY_INFOS'] = SearchAction::prepareSearchResultsJson(
				Crm\Controller\Entity::getRecentlyUsedItems(
					'crm.contact.details',
					'company',
					[
						'EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Company,
						'EXPAND_CATEGORY_ID' => $categoryParams[CCrmOwnerType::Company]['categoryId'],
					]
				)
			);
		}

		//endregion

		//region Images
		$photoID = isset($this->entityData['PHOTO']) ? (int)$this->entityData['PHOTO'] : 0;
		if($photoID > 0)
		{
			$fileResizeInfo = $file->ResizeImageGet(
				$photoID,
				array('width' => 200, 'height'=> 200),
				BX_RESIZE_IMAGE_EXACT
			);
			if(is_array($fileResizeInfo) && isset($fileResizeInfo['src']))
			{
				$this->entityData['PHOTO_SHOW_URL'] = $fileResizeInfo['src'];
			}
		}
		//endregion

		if($this->enableOutmodedFields)
		{
			$this->entityData['ADDRESS_HTML'] =
				AddressFormatter::getSingleInstance()->formatHtmlMultilineSpecialchar(
					Crm\ContactAddress::mapEntityFields($this->entityData)
				);
		}

		Tracking\UI\Details::prepareEntityData(
			\CCrmOwnerType::Contact,
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
					new Crm\ItemIdentifier(\CCrmOwnerType::Contact, $this->entityID)
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
	protected function prepareTypeList()
	{
		if($this->types === null)
		{
			$this->types = \CCrmStatus::GetStatusList('CONTACT_TYPE');
		}
		return $this->types;
	}
	public function prepareEntityInfo()
	{
		return CCrmEntitySelectorHelper::PrepareEntityInfo(
			\CCrmOwnerType::ContactName,
			$this->entityID,
			array(
				'ENTITY_EDITOR_FORMAT' => true,
				'REQUIRE_REQUISITE_DATA' => true,
				'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
			)

		);
	}
	public function prepareServiceUrl()
	{
		return '/bitrix/components/bitrix/crm.contact.details/ajax.php?'.bitrix_sessid_get();
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

	protected function getEventTabParams(): array
	{
		return CCrmComponentHelper::getEventTabParams(
			$this->entityID,
			Loc::getMessage('CRM_CONTACT_TAB_EVENT'),
			CCrmOwnerType::ContactName,
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
			if (\CCrmContact::CheckUpdatePermission($this->entityID, $this->userPermissions))
			{
				$this->arResult['READ_ONLY'] = false;
			}
		}
		elseif (\CCrmContact::CheckCreatePermission($this->userPermissions, $this->arResult['CATEGORY_ID']))
		{
			$this->arResult['READ_ONLY'] = false;
		}
	}

	private function initializeConfigId(): void
	{
		$this->arResult['EDITOR_CONFIG_ID'] = $this->getEditorConfigId(
			(string)($this->arParams['EDITOR_CONFIG_ID'] ?? '')
		);
	}

	private function initializeConversionScheme(): void
	{
		if (!empty($this->arResult['LEAD_ID']))
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
			$this->loadConversionWizard();
		}
	}

	private function initializeDuplicateControl(): void
	{
		$this->enableDupControl = (Crm\Integrity\DuplicateControl::isControlEnabledFor(CCrmOwnerType::Contact));

		$this->arResult['DUPLICATE_CONTROL'] = [
			'enabled' => $this->enableDupControl,
			'isSingleMode' => $this->isEditMode,
		];

		if ($this->enableDupControl)
		{
			$this->arResult['DUPLICATE_CONTROL']['serviceUrl'] = '/bitrix/components/bitrix/crm.contact.edit/ajax.php?'
				. bitrix_sessid_get();
			$this->arResult['DUPLICATE_CONTROL']['entityTypeName'] = CCrmOwnerType::ContactName;
			$this->arResult['DUPLICATE_CONTROL']['groups'] = [
				'fullName' => [
					'groupType' => 'fullName',
					'groupSummaryTitle' => Loc::getMessage('CRM_CONTACT_DUP_CTRL_FULL_NAME_SUMMARY_TITLE'),
				],
				'email' => [
					'groupType' => 'communication',
					'communicationType' => 'EMAIL',
					'groupSummaryTitle' => Loc::getMessage('CRM_CONTACT_DUP_CTRL_EMAIL_SUMMARY_TITLE'),
				],
				'phone' => [
					'groupType' => 'communication',
					'communicationType' => 'PHONE',
					'groupSummaryTitle' => Loc::getMessage('CRM_CONTACT_DUP_CTRL_PHONE_SUMMARY_TITLE'),
				],
			];

			$this->arResult['DUPLICATE_CONTROL']['ignoredItems'] = [];
			if ($this->entityID)
			{
				$this->arResult['DUPLICATE_CONTROL']['ignoredItems'][] = [
					'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
					'ENTITY_ID' => $this->entityID,
				];
			}
			else
			{
				$this->arResult['DUPLICATE_CONTROL']['ignoredItems'][] = [
					'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
					'ENTITY_ID' => $this->leadID,
				];
				$leadCompany = Crm\CompanyTable::query()
					->where('LEAD_ID', $this->leadID)
					->setSelect(['ID'])
					->setOrder(['ID' => 'desc'])
					->setLimit(1)
					->exec()
					->fetch()['ID'] ?? null;
				if ($leadCompany)
				{
					$this->arResult['DUPLICATE_CONTROL']['ignoredItems'][] = [
						'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
						'ENTITY_ID' => $leadCompany,
					];
				}
			}
		}
	}

	private function initializePath(): void
	{
		global $APPLICATION;

		$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] = \CrmCheckPath(
			'PATH_TO_USER_PROFILE',
			$this->arParams['PATH_TO_USER_PROFILE'] ?? '',
			'/company/personal/user/#user_id#/'
		);

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(["#NOBR#", "#/NOBR#"], ["", ""], $this->arParams['NAME_TEMPLATE']);

		$this->arResult['PATH_TO_CONTACT_SHOW'] = \CrmCheckPath(
			'PATH_TO_CONTACT_SHOW',
			$this->arParams['PATH_TO_CONTACT_SHOW'] ?? '',
			$APPLICATION->GetCurPage() . '?contact_id=#contact_id#&show'
		);

		$this->arResult['PATH_TO_CONTACT_EDIT'] = \CrmCheckPath(
			'PATH_TO_CONTACT_EDIT',
			$this->arParams['PATH_TO_CONTACT_EDIT'] ?? '',
			$APPLICATION->GetCurPage() . '?contact_id=#contact_id#&edit'
		);
	}

	private function initializeContext(): void
	{
		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::ContactName . '_' . $this->entityID;

		$this->arResult['CONTEXT'] = [
			'PARAMS' => [
				'PATH_TO_USER_PROFILE' => $this->arResult['PATH_TO_USER_PROFILE'],
				'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
			],
		];
		Crm\Service\EditorAdapter::addParentItemToContextIfFound($this->arResult['CONTEXT']);

		if ($this->isCopyMode)
		{
			$this->arResult['CONTEXT']['PARAMS']['CONTACT_ID'] = $this->entityID;
		}

		if (!isset($this->arResult['CONTEXT']['PARAMS']['CATEGORY_ID']))
		{
			$this->arResult['CONTEXT']['PARAMS']['CATEGORY_ID'] = $this->getCategoryId();
		}

		if ($this->conversionWizard !== null)
		{
			$this->arResult['CONTEXT']['PARAMS'] = array_merge(
				$this->arResult['CONTEXT']['PARAMS'],
				$this->conversionWizard->prepareEditorContextParams(\CCrmOwnerType::Contact)
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
		$this->loadConversionWizard();
		$this->prepareFieldInfos();
		$this->prepareEntityData();
		$this->prepareEntityFieldAttributes();
	}

	public function getEntityEditorData(): array
	{
		return [
			'ENTITY_ID' => $this->getEntityID(),
			'ENTITY_DATA' => $this->prepareEntityData(),
			'ENTITY_INFO' => $this->prepareEntityInfo(),
			'ADDITIONAL_FIELDS_DATA' => $this->getAdditionalFieldsData(),
		];
	}

	public function getCategoryId(): int
	{
		$categoryId = 0;
		if ($this->entityID > 0)
		{
			return (int)Container::getInstance()->getFactory(CCrmOwnerType::Contact)->getItemCategoryId($this->entityID);
		}

		if (isset($this->request['category_id']))
		{
			$categoryId = (int)$this->request['category_id'];
		}
		elseif ($this->conversionWizard !== null)
		{
			// get category from conversion context:
			$categoryId = $this->arResult['CONTEXT']['PARAMS']['CATEGORY_ID'] ?? $categoryId;
		}
		else
		{
			$categoryId = (int)($this->arResult['CATEGORY_ID'] ?? 0);
		}

		if ($categoryId && !($this->factory && $this->factory->isCategoryAvailable($categoryId)))
		{
			$categoryId = 0;
		}

		return $categoryId;
	}

	/**
	 * @return Crm\Category\Entity\Category|null
	 */
	protected function getCategory(): ?Crm\Category\Entity\Category
	{
		if (!$this->factory)
		{
			return null;
		}

		$categoryId = $this->arResult['CATEGORY_ID'] ?? $this->getCategoryId();
		if (!$categoryId)
		{
			return null;
		}

		return $this->factory->getCategory($categoryId);
	}

	public function getEditorConfigId(string $sourceId = ''): string
	{
		$sourceId = ($sourceId === '') ? 'contact_details' : $sourceId;

		if (!isset($this->arResult['CATEGORY_ID']))
		{
			$this->arResult['CATEGORY_ID'] = $this->getCategoryId();
		}

		return (new EditorHelper(\CCrmOwnerType::Contact))->getEditorConfigId($this->arResult['CATEGORY_ID'], $sourceId);
	}
}
