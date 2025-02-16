<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true){
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Category\EditorHelper;
use Bitrix\Crm\CompanyAddress;
use Bitrix\Crm\Component\EntityDetails\Traits;
use Bitrix\Crm\Controller\Action\Entity\SearchAction;
use Bitrix\Crm\Conversion\LeadConversionWizard;
use Bitrix\Crm\EntityAddressType;
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

class CCrmCompanyDetailsComponent
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
	/** @var array */
	private $rawEntityData;
	/** @var array|null */
	private $entityDataScheme = null;
	/** @var array|null */
	private $entityFieldAttributeConfig = null;
	/** @var array|null */
	private $entityFieldRequiredByAttributes = null;
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
	private $isMyCompany;
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
		$this->userFieldEntityID = \CCrmCompany::GetUserFieldEntityID();
		$this->userType = new \CCrmUserType($USER_FIELD_MANAGER, $this->userFieldEntityID);
		$this->userFieldDispatcher = \Bitrix\Main\UserField\Dispatcher::instance();

		$this->multiFieldInfos = CCrmFieldMulti::GetEntityTypeInfos();
		$this->multiFieldValueTypeInfos = CCrmFieldMulti::GetEntityTypes();
		$this->factory = Container::getInstance()->getFactory(\CCrmOwnerType::Company);
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

		$this->enableOutmodedFields = false; //\Bitrix\Crm\Settings\CompanySettings::getCurrent()->areOutmodedRequisitesEnabled();
		$this->isLocationModuleIncluded = Main\Loader::includeModule('location');

		//region Params
		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID']) ? (int)$this->arParams['~ENTITY_ID'] : 0;

		$this->arResult['ENABLE_TASK'] = IsModuleInstalled('tasks');
		$this->arResult['ACTION_URI'] = $this->arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;

		$this->enableSearchHistory = !isset($this->arParams['~ENABLE_SEARCH_HISTORY'])
			|| mb_strtoupper($this->arParams['~ENABLE_SEARCH_HISTORY']) === 'Y';

		$this->setEntityID($this->arResult['ENTITY_ID']);

		$this->defaultFieldValues = [];
		$this->tryGetFieldValueFromRequest('phone', $this->defaultFieldValues);
		//endregion

		if ($this->entityID > 0 && !\CCrmCompany::Exists($this->entityID))
		{
			Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::EntityNotExist, CCrmOwnerType::Company);

			return;
		}

		$this->arResult['CATEGORY_ID'] = $this->getCategoryId();
		$this->arResult['ANALYTICS'] = $this->arParams['EXTRAS']['ANALYTICS'] ?? [];
		$this->arResult['ANALYTICS']['c_sub_section'] = Crm\Integration\Analytics\Dictionary::SUB_SECTION_DETAILS;
		$this->arResult['ANALYTICS']['c_section'] = $this->isMyCompany() ?
			Crm\Integration\Analytics\Dictionary::SECTION_MYCOMPANY :
			Crm\Integration\Analytics\Dictionary::SECTION_COMPANY
		;

		//region Permissions check
		$this->initializeMode();

		if ($this->isMyCompany())
		{
			if (!$this->userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoAddPermission, CCrmOwnerType::Company);

				return;
			}
		}
		elseif ($this->isCopyMode)
		{
			if (!\CCrmCompany::CheckReadPermission($this->entityID, $this->userPermissions, $this->arResult['CATEGORY_ID']))
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoReadPermission, CCrmOwnerType::Company);

				return;
			}
			elseif (!\CCrmCompany::CheckCreatePermission($this->userPermissions, $this->arResult['CATEGORY_ID']))
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoAddPermission, CCrmOwnerType::Company);

				return;
			}
		}
		elseif ($this->isEditMode)
		{
			if (
				!\CCrmCompany::CheckUpdatePermission(0)
				&& !\CCrmCompany::CheckReadPermission()
			)
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoAccessToEntityType, CCrmOwnerType::Company);

				return;
			}
			elseif (
				!\CCrmCompany::CheckUpdatePermission($this->entityID, $this->userPermissions, $this->arResult['CATEGORY_ID'])
				&& !\CCrmCompany::CheckReadPermission($this->entityID, $this->userPermissions, $this->arResult['CATEGORY_ID'])
			)
			{
				Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoReadPermission, CCrmOwnerType::Company);

				return;
			}
		}
		elseif (!\CCrmCompany::CheckCreatePermission($this->userPermissions, $this->arResult['CATEGORY_ID']))
		{
			Crm\Component\EntityDetails\Error::showError(Crm\Component\EntityDetails\Error::NoAddPermission, CCrmOwnerType::Company);

			return;
		}
		//endregion

		$this->prepareEntityUserFields();
		$this->prepareEntityUserFieldInfos();

		$this->initializeEditorData();

		$this->arResult['IS_MY_COMPANY'] = $this->isMyCompany();

		//region Entity Info
		$this->arResult['ENTITY_INFO'] = array(
			'ENTITY_ID' => $this->entityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::CompanyName,
			'TITLE' => isset($this->entityData['TITLE']) ? $this->entityData['TITLE'] : '',
			'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Company, $this->entityID, false),
		);
		//endregion

		//region Page title
		if ($this->isCopyMode)
		{
			$APPLICATION->SetTitle(
				Crm\Category\NamingHelper::getInstance()->getLangPhrase(
					'CRM_COMPANY_COPY_PAGE_TITLE',
					$this->arResult['CATEGORY_ID']
				)
			);
		}
		elseif(isset($this->entityData['TITLE']))
		{
			$APPLICATION->SetTitle(htmlspecialcharsbx($this->entityData['TITLE']));
		}
		elseif(!$this->isEditMode)
		{
			$APPLICATION->SetTitle(
				Crm\Category\NamingHelper::getInstance()->getLangPhrase(
					'CRM_COMPANY_CREATION_PAGE_TITLE',
					$this->arResult['CATEGORY_ID']
				)
			);
		}
		//endregion

		//region TABS
		$this->arResult['TABS'] = [];
		if ($this->entityID > 0)
		{
			if (!$this->isMyCompany())
			{
				if (!$this->arResult['CATEGORY_ID'])
				{
					$this->arResult['TABS'][] = [
						'id' => 'tab_deal',
						'name' => Loc::getMessage('CRM_COMPANY_TAB_DEAL'),
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
									'INTERNAL_FILTER' => ['COMPANY_ID' => $this->entityID],
									'INTERNAL_CONTEXT' => ['COMPANY_ID' => $this->entityID],
									'GRID_ID_SUFFIX' => 'COMPANY_DETAILS',
									'TAB_ID' => 'tab_deal',
									'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'] ?? '',
									'ENABLE_TOOLBAR' => true,
									'PRESERVE_HISTORY' => true,
									'ADD_EVENT_NAME' => 'CrmCreateDealFromCompany',
									'ANALYTICS' => [
										// we dont know where from this component was opened from - it could be anywhere on portal
										'c_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_COMPANY,
										'c_sub_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_DETAILS,
									],
								], 'crm.deal.list')
							]
						]
					];

					$relationManager = Crm\Service\Container::getInstance()->getRelationManager();
					$this->arResult['TABS'] = array_merge(
						$this->arResult['TABS'],
						$relationManager->getRelationTabsForDynamicChildren(
							\CCrmOwnerType::Company,
							$this->entityID,
							($this->entityID === 0)
						)
					);

					$tabQuote = [
						'id' => 'tab_quote',
						'name' => Loc::getMessage('CRM_COMPANY_TAB_QUOTE_MSGVER_1'),
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
									'INTERNAL_FILTER' => ['COMPANY_ID' => $this->entityID],
									'INTERNAL_CONTEXT' => ['COMPANY_ID' => $this->entityID],
									'GRID_ID_SUFFIX' => 'COMPANY_DETAILS',
									'TAB_ID' => 'tab_quote',
									'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'] ?? '',
									'ENABLE_TOOLBAR' => true,
									'PRESERVE_HISTORY' => true,
									'ADD_EVENT_NAME' => 'CrmCreateQuoteFromCompany',
									'ANALYTICS' => [
										// we dont know where from this component was opened from - it could be anywhere on portal
										'c_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_COMPANY,
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
								.bitrix_sessid_get()
							,
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
									'INTERNAL_FILTER' => ['UF_COMPANY_ID' => $this->entityID],
									'SUM_PAID_CURRENCY' => \CCrmCurrency::GetBaseCurrencyID(),
									'GRID_ID_SUFFIX' => 'COMPANY_DETAILS',
									'TAB_ID' => 'tab_invoice',
									'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'] ?? '',
									'ENABLE_TOOLBAR' => 'Y',
									'PRESERVE_HISTORY' => true,
									'ADD_EVENT_NAME' => 'CrmCreateInvoiceFromCompany',
									'ANALYTICS' => [
										// we dont know where from this component was opened from - it could be anywhere on portal
										'c_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_COMPANY,
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
						'name' => Loc::getMessage('CRM_COMPANY_TAB_ORDERS'),
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
									'INTERNAL_FILTER' => array('COMPANY_ID' => $this->entityID),
									'SUM_PAID_CURRENCY' => \CCrmCurrency::GetBaseCurrencyID(),
									'GRID_ID_SUFFIX' => 'COMPANY_DETAILS',
									'TAB_ID' => 'tab_order',
									'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'] ?? '',
									'ENABLE_TOOLBAR' => 'Y',
									'PRESERVE_HISTORY' => true,
									'ADD_EVENT_NAME' => 'CrmCreateOrderFromCompany',
									'BUILDER_CONTEXT' => Crm\Product\Url\ProductBuilder::TYPE_ID,
									'ANALYTICS' => [
										// we dont know where from this component was opened from - it could be anywhere on portal
										'c_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_COMPANY,
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
							'name' => Loc::getMessage('CRM_COMPANY_TAB_BIZPROC'),
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
										'ENTITY' => 'CCrmDocumentCompany',
										'DOCUMENT_TYPE' => 'COMPANY',
										'DOCUMENT_ID' => 'COMPANY_' . $this->entityID,
									],
								],
							],
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
							'entity' => 'CCrmDocumentCompany',
							'documentType' => 'COMPANY',
							'documentId' => 'COMPANY_' . $this->entityID,
						];
					}
				}
				$this->arResult['TABS'][] = array(
					'id' => 'tab_tree',
					'name' => Loc::getMessage('CRM_COMPANY_TAB_TREE'),
					'loader' => array(
						'serviceUrl' =>
							'/bitrix/components/bitrix/crm.entity.tree/lazyload.ajax.php?&site='
							. SITE_ID . '&' . bitrix_sessid_get()
						,
						'componentData' => array(
							'template' => '.default',
							'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
								'ENTITY_ID' => $this->entityID,
								'ENTITY_TYPE_NAME' => CCrmOwnerType::CompanyName,
							], 'crm.entity.tree')
						)
					)
				);
				$this->arResult['TABS'][] = $this->getEventTabParams();

				if (CModule::IncludeModule('lists') && !$this->arResult['CATEGORY_ID'])
				{
					$listIblock = CLists::getIblockAttachedCrm(CCrmOwnerType::CompanyName);
					foreach($listIblock as $iblockId => $iblockName)
					{
						$this->arResult['TABS'][] = array(
							'id' => 'tab_lists_'.$iblockId,
							'name' => $iblockName,
							'loader' => array(
								'serviceUrl' =>
									'/bitrix/components/bitrix/lists.element.attached.crm/lazyload.ajax.php?&site='
									. SITE_ID . '&'.bitrix_sessid_get().''
								,
								'componentData' => array(
									'template' => '',
									'params' => array(
										'ENTITY_ID' => $this->entityID,
										'ENTITY_TYPE' => CCrmOwnerType::Company,
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
				$this->arResult['TABS'][] = $this->getEventTabParams();
			}
		}
		else
		{
			if (!$this->arResult['CATEGORY_ID'])
			{
				$this->arResult['TABS'][] = [
					'id' => 'tab_deal',
					'name' => Loc::getMessage('CRM_COMPANY_TAB_DEAL'),
					'enabled' => false
				];
				$this->arResult['TABS'][] = [
					'id' => 'tab_quote',
					'name' => Loc::getMessage('CRM_COMPANY_TAB_QUOTE_MSGVER_1'),
					'enabled' => false
				];
				$this->arResult['TABS'][] = [
					'id' => 'tab_invoice',
					'name' => Loc::getMessage('CRM_COMPANY_TAB_INVOICES'),
					'enabled' => false
				];
			}
			if (CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled())
			{
				$this->arResult['TABS'][] = array(
					'id' => 'tab_bizproc',
					'name' => Loc::getMessage('CRM_COMPANY_TAB_BIZPROC'),
					'enabled' => false
				);
			}
			$this->arResult['TABS'][] = $this->getEventTabParams();

			if (CModule::IncludeModule('lists') && !$this->arResult['CATEGORY_ID'])
			{
				$listIblock = CLists::getIblockAttachedCrm(CCrmOwnerType::CompanyName);
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
		if ($this->entityID > 0 && \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isViewEventEnabled())
		{
			CCrmEvent::RegisterViewEvent(CCrmOwnerType::Company, $this->entityID, $this->userID);
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
		return (
			$this->isMyCompany()
				? "my_company_{$this->entityID}_details"
				: "company_{$this->entityID}_details"
		);
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
		if (isset($this->arResult['ENTITY_CONFIG']))
		{
			return $this->arResult['ENTITY_CONFIG'];
		}

		$multiFieldConfigElements = [];
		foreach(array_keys($this->multiFieldInfos) as $fieldName)
		{
			$multiFieldConfigElements[] = array('name' => $fieldName);
		}

		$userFieldConfigElements = [];
		foreach(array_keys($this->userFieldInfos) as $fieldName)
		{
			$userFieldConfigElements[] = array('name' => $fieldName);
		}

		$this->arResult['ENTITY_CONFIG'] = array(
			array(
				'name' => 'main',
				'title' => Loc::getMessage('CRM_COMPANY_SECTION_MAIN'),
				'type' => 'section',
				'elements' =>
					array_merge(
						array(
							array('name' => 'TITLE'),
							array('name' => 'LOGO'),
							array('name' => 'COMPANY_TYPE'),
							array('name' => 'INDUSTRY'),
							array('name' => 'REVENUE_WITH_CURRENCY'),
							//array('name' => 'IS_MY_COMPANY')
						),
						$multiFieldConfigElements,
						array(
							array('name' => 'CONTACT'),
							array('name' => 'ADDRESS'),
							array('name' => 'REQUISITES')
						)
					)
			),
			array(
				'name' => 'additional',
				'title' => Loc::getMessage('CRM_COMPANY_SECTION_ADDITIONAL'),
				'type' => 'section',
				'elements' =>
					array_merge(
						array(
							array('name' => 'EMPLOYEES'),
							array('name' => 'OPENED'),
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
	}

	public function setCategoryID(int $categoryID): void
	{
		$this->arResult['CATEGORY_ID'] = $categoryID;
	}

	public function prepareEntityDataScheme()
	{
		if ($this->entityDataScheme === null)
		{
			$this->entityDataScheme = \CCrmCompany::GetFieldsInfo();
			$this->userType->PrepareFieldsInfo($this->entityDataScheme);
		}
		return $this->entityDataScheme;
	}
	public function prepareValidators()
	{
		if (isset($this->arResult['ENTITY_VALIDATORS']))
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
		if (isset($this->entityFieldInfos))
		{
			return $this->entityFieldInfos;
		}

		$observersRestriction = RestrictionManager::getObserversRestriction();

		$fakeValue = '';
		$categoryParams = CCrmComponentHelper::getEntityClientFieldCategoryParams(
			CCrmOwnerType::Company,
			(int)($this->arResult['CATEGORY_ID'] ?? $this->getCategoryId())
		);
		$this->entityFieldInfos = array(
			array(
				'name' => 'ID',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_ID'),
				'type' => 'text',
				'editable' => false,
				'enableAttributes' => false
			),
			array(
				'name' => 'TITLE',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_TITLE'),
				'type' => 'text',
				'isHeading' => true,
				'visibilityPolicy' => 'edit',
				'placeholders' => array('creation' => \CCrmCompany::GetAutoTitle()),
				'editable' => true,
				'data' => array('duplicateControl' => array('groupId' => 'title', 'field' => array('id' => 'TITLE')))
			),
			array(
				'name' => 'ASSIGNED_BY_ID',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_ASSIGNED_BY_ID'),
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
				'name' => 'LOGO',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_LOGO'),
				'type' => 'image',
				'editable' => true,
				'data' => array('showUrl' => 'LOGO_SHOW_URL')
			),
			array(
				'name' => 'COMPANY_TYPE',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_COMPANY_TYPE'),
				'type' => 'list',
				'editable' => true,
				'data' => [
					'items'=> \CCrmInstantEditorHelper::PrepareListOptions(
						$this->prepareTypeList(),
						[
							'NOT_SELECTED' => Loc::getMessage('CRM_COMPANY_TYPE_NOT_SELECTED'),
							'NOT_SELECTED_VALUE' => $fakeValue
						]
					),
					'defaultValue' => $this->defaultEntityData['COMPANY_TYPE'] ?? null,
					'innerConfig' => \CCrmInstantEditorHelper::prepareInnerConfig(
						'crm_status',
						'crm.status.setItems',
						'COMPANY_TYPE',
						[$fakeValue]
					),
				]
			),
			array(
				'name' => 'INDUSTRY',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_INDUSTRY'),
				'type' => 'list',
				'editable' => true,
				'data' => [
					'items'=> \CCrmInstantEditorHelper::PrepareListOptions(
						CCrmStatus::GetStatusList('INDUSTRY'),
						[
							'NOT_SELECTED' => Loc::getMessage('CRM_COMPANY_INDUSTRY_NOT_SELECTED'),
							'NOT_SELECTED_VALUE' => $fakeValue
						]
					),
					'defaultValue' => $this->defaultEntityData['INDUSTRY'] ?? null,
					'innerConfig' => \CCrmInstantEditorHelper::prepareInnerConfig(
						'crm_status',
						'crm.status.setItems',
						'INDUSTRY',
						[$fakeValue]
					),
				]

			),
			array(
				'name' => 'EMPLOYEES',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_EMPLOYEES'),
				'type' => 'list',
				'editable' => true,
				'data' => [
					'items'=> \CCrmInstantEditorHelper::PrepareListOptions(
						CCrmStatus::GetStatusList('EMPLOYEES'),
						[
							'NOT_SELECTED' => Loc::getMessage('CRM_COMPANY_EMPLOYEES_NOT_SELECTED'),
							'NOT_SELECTED_VALUE' => $fakeValue
						]
					),
					'defaultValue' => $this->defaultEntityData['EMPLOYEES'] ?? null,
					'innerConfig' => \CCrmInstantEditorHelper::prepareInnerConfig(
						'crm_status',
						'crm.status.setItems',
						'EMPLOYEES',
						[$fakeValue]
					),
				]
			),
			array(
				'name' => 'REVENUE_WITH_CURRENCY',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_REVENUE'),
				'type' => 'money',
				'editable' => true,
				'data' => array(
					"affectedFields" => array("CURRENCY_ID", "REVENUE"),
					"currency" => array(
						"name" => "CURRENCY_ID",
						"items"=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems())
					),
					"amount" => "REVENUE",
					"formatted" => "FORMATTED_REVENUE",
					"formattedWithCurrency" => "FORMATTED_REVENUE_WITH_CURRENCY"
				)
			),
			Crm\Entity\CommentsHelper::compileFieldDescriptionForDetails(
				\CCrmOwnerType::Company,
				'COMMENTS',
				$this->entityID,
			),
			array(
				'name' => 'OPENED',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_OPENED'),
				'type' => 'boolean',
				'editable' => true
			),
//			array(
//				'name' => 'IS_MY_COMPANY',
//				'title' => Loc::getMessage('CRM_COMPANY_FIELD_IS_MY_COMPANY'),
//				'type' => 'boolean',
//				'editable' => true
//			),
			array(
				'name' => 'BANKING_DETAILS',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_BANKING_DETAILS'),
				'type' => 'text',
				'data' => array('lineCount' => 6),
				'editable' => true
			),
			array(
				'name' => 'CONTACT',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_CONTACT'),
				'type' => 'client_light',
				'editable' => true,
				'data' => array(
					'affectedFields' => ['CLIENT_INFO'],
					'compound' => array(
						array(
							'name' => 'CONTACT_ID',
							'type' => 'multiple_contact',
							'entityTypeName' => \CCrmOwnerType::ContactName,
							'tagName' => \CCrmOwnerType::ContactName
						)
					),
					'categoryParams' => $categoryParams,
					'map' => array('data' => 'CLIENT_DATA'),
					'info' => 'CLIENT_INFO',
					'fixedLayoutType' => 'CONTACT',
					'lastContactInfos' => 'LAST_CONTACT_INFOS',
					'contactLegend' => Loc::getMessage('CRM_COMPANY_FIELD_CONTACT_LEGEND'),
					'loaders' => array(
						'primary' => array(
							CCrmOwnerType::ContactName => array(
								'action' => 'GET_CLIENT_INFO',
								'url' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get()
							)
						)
					),
					'clientEditorFieldsParams' => CCrmComponentHelper::prepareClientEditorFieldsParams(
						['categoryParams' => $categoryParams]
					),
					'duplicateControl' => CCrmComponentHelper::prepareClientEditorDuplicateControlParams(
						['entityTypes' => [CCrmOwnerType::Contact]]
					),
				)
			),
			[
				'name' => 'REQUISITES',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_REQUISITES'),
				'type' => 'requisite',
				'editable' => true,
				'data' => CCrmComponentHelper::getFieldInfoData(
					CCrmOwnerType::Company,
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
			'title' => Loc::getMessage('CRM_COMPANY_FIELD_UTM'),
			'type' => 'custom',
			'data' => array(
				'view' => 'UTM_VIEW_HTML',
			),
			'editable' => false,
			'enableAttributes' => false
		);

		if ($this->enableOutmodedFields)
		{
			$this->entityFieldInfos[] = array(
				'name' => 'ADDRESS',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_ADDRESS'),
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
					'labels' => \Bitrix\Crm\EntityAddress::getLabels(),
					'view' => 'ADDRESS_HTML'
				)
			);

			$this->entityFieldInfos[] = array(
				'name' => 'ADDRESS_LEGAL',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_ADDRESS_LEGAL'),
				'type' => 'address_form',
				'editable' => true,
				'enableAttributes' => false,
				'data' => array(
					'fields' => array(
						'ADDRESS' => array('NAME' => 'REG_ADDRESS', 'IS_MULTILINE' => true),
						'ADDRESS_2' => array('NAME' => 'REG_ADDRESS_2'),
						'CITY' => array('NAME' => 'REG_ADDRESS_CITY'),
						'REGION' => array('NAME' => 'REG_ADDRESS_REGION'),
						'PROVINCE' => array('NAME' => 'REG_ADDRESS_PROVINCE'),
						'POSTAL_CODE' => array('NAME' => 'REG_ADDRESS_POSTAL_CODE'),
						'COUNTRY' => array('NAME' => 'REG_ADDRESS_COUNTRY')
					),
					'labels' => \Bitrix\Crm\EntityAddress::getLabels(),
					'view' => 'REG_ADDRESS_HTML'
				)
			);
		}
		elseif (CModule::IncludeModule('location'))
		{
			$this->entityFieldInfos[] = array(
				'name' => 'ADDRESS',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_REQUISITE_ADDRESS'),
				'type' => 'requisite_address',
				'editable' => true,
				'enableAttributes' => false,
				'virtual' => true,
				'data' => CCrmComponentHelper::getRequisiteAddressFieldData(
					CCrmOwnerType::Company,
					$this->getCategoryId()
				)
			);
		}

		foreach($this->multiFieldInfos as $typeName => $typeInfo)
		{
			$valueTypes = $this->multiFieldValueTypeInfos[$typeName] ?? [];

			$valueTypeItems = [];
			foreach($valueTypes as $valueTypeId => $valueTypeInfo)
			{
				/** @var array $valueTypeInfo */
				$valueTypeItems[] = array(
					'NAME' => $valueTypeInfo['SHORT'] ?? $valueTypeInfo['FULL'],
					'VALUE' => $valueTypeId
				);
			}

			$data = array('type' => $typeName, 'items'=> $valueTypeItems);
			if ($typeName === 'PHONE')
			{
				$data['duplicateControl'] = array('groupId' => 'phone');
			}
			else if ($typeName === 'EMAIL')
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
			$parentFieldsInfo = $this->editorAdapter->getParentFieldsInfo(\CCrmOwnerType::Company);
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
		if ($this->userFields === null)
		{
			if ($this->isMyCompany())
			{
				$this->userType->setOption(['isMyCompany' => true]);
			}

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
		if (!$this->entityFieldAttributeConfig)
		{
			$this->entityFieldAttributeConfig = FieldAttributeManager::getEntityConfigurations(
				CCrmOwnerType::Company,
				FieldAttributeManager::resolveEntityScope(
					CCrmOwnerType::Company,
					$this->entityID,
					['CATEGORY_ID' => $this->arResult['CATEGORY_ID']]
				)
			);
		}
		return $this->entityFieldAttributeConfig;
	}

	public function prepareEntityUserFieldInfos()
	{
		if ($this->userFieldInfos !== null)
		{
			return $this->userFieldInfos;
		}

		$this->userFieldInfos = [];
		$userFields = $this->prepareEntityUserFields();
		$enumerationFields = [];

		$visibilityConfig = $this->prepareEntityFieldvisibilityConfigs(CCrmOwnerType::Company);

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

			if ($userField['USER_TYPE_ID'] === 'enumeration')
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

			if (isset($visibilityConfig[$fieldName]))
			{
				$data['visibilityConfigs'] = $visibilityConfig[$fieldName];
			}

			$this->userFieldInfos[$fieldName] = array(
				'name' => $fieldName,
				'title' => isset($userField['EDIT_FORM_LABEL']) ? $userField['EDIT_FORM_LABEL'] : $fieldName,
				'type' => 'userField',
				'data' => $data
			);

			if (isset($userField['MANDATORY']) && $userField['MANDATORY'] === 'Y')
			{
				$this->userFieldInfos[$fieldName]['required'] = true;
			}
		}

		if (!empty($enumerationFields))
		{
			$enumInfos = \CCrmUserType::PrepareEnumerationInfos($enumerationFields);
			foreach($enumInfos as $fieldName => $enums)
			{
				if (isset($this->userFieldInfos[$fieldName])
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
		if ($this->entityFieldInfos === null)
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

				if (isset($fieldInfo['USER_TYPE_ID']) && $fieldInfo['USER_TYPE_ID'] === 'boolean')
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
					'LOGO',                              // image
					'COMPANY_TYPE',                      // list
					'INDUSTRY',                          // list
					'REVENUE_WITH_CURRENCY',             // money
					'PHONE',                             // multifield
					'EMAIL',                             // multifield
					'WEB',                               // multifield
					'IM',                                // multifield
					'CONTACT',                           // client_light
					'BANKING_DETAILS',                   // text
					Tracking\UI\Details::SourceId,       // custom
					'EMPLOYEES',                         // list
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
						case 'money':
							if ($fieldName === 'REVENUE_WITH_CURRENCY')
							{
								$dataFieldName = 'REVENUE';
								if (array_key_exists($dataFieldName, $this->entityData)
									&& empty($this->entityData[$dataFieldName]))
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
						case 'client_light':
							if ($fieldName === 'CONTACT')
							{
								if (is_array($this->entityData['CLIENT_INFO'])
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

	public function prepareEntityData()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		if ($this->entityData)
		{
			return $this->entityData;
		}

		$isTrackingFieldRequired = false;

		$file = new \CFile();
		if ($this->conversionWizard !== null)
		{
			$this->entityData = [];
			$mappedUserFields = [];
			\Bitrix\Crm\Entity\EntityEditor::prepareConvesionMap(
				$this->conversionWizard,
				CCrmOwnerType::Company,
				$this->entityData,
				$mappedUserFields
			);

			foreach($mappedUserFields as $k => $v)
			{
				if (isset($this->userFields[$k]))
				{
					$this->userFields[$k]['VALUE'] = $v;
				}
			}

			$this->entityData['CURRENCY_ID'] = \CCrmCurrency::GetBaseCurrencyID();
			$this->entityData['OPENED'] = \Bitrix\Crm\Settings\CompanySettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
		}
		else if ($this->entityID <= 0)
		{
			$requiredFields = Crm\Attribute\FieldAttributeManager::isEnabled()
				? Crm\Attribute\FieldAttributeManager::getRequiredFields(
					CCrmOwnerType::Company,
					$this->entityID,
					['COMPANY_TYPE', 'INDUSTRY', 'EMPLOYEES', Tracking\UI\Details::SourceId],
					Crm\Attribute\FieldOrigin::SYSTEM,
					['CATEGORY_ID' => $this->arResult['CATEGORY_ID']]
				)
				: [];
			$isTrackingFieldRequired = in_array(Tracking\UI\Details::SourceId, $requiredFields, true);
			$fieldsInfo = CCrmCompany::GetFieldsInfo();
			$this->entityData = [];
			//leave REVENUE unassigned
			//$this->entityData['REVENUE'] = 0.0;
			$this->entityData['CURRENCY_ID'] = \CCrmCurrency::GetBaseCurrencyID();
			$this->entityData['OPENED'] = \Bitrix\Crm\Settings\CompanySettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';

			if ($this->isMyCompany())
			{
				$this->entityData['IS_MY_COMPANY'] = 'Y';
			}

			// set first option by default if the field is not required
			$typeList = $this->prepareTypeList();
			if (
				!empty($typeList)
				&& $this->isFieldHasDefaultValueAttribute($fieldsInfo, 'COMPANY_TYPE'))
			{
				$this->arResult['FIELDS_SET_DEFAULT_VALUE'][] = 'COMPANY_TYPE';
				$this->defaultEntityData['COMPANY_TYPE'] = current(array_keys($typeList));
				if ($this->isSetDefaultValueForField($fieldsInfo, $requiredFields, 'COMPANY_TYPE'))
				{
					$this->entityData['COMPANY_TYPE'] = $this->defaultEntityData['COMPANY_TYPE'];
				}
			}
			unset($typeList);

			if ($this->isFieldHasDefaultValueAttribute($fieldsInfo, 'INDUSTRY'))
			{
				$this->arResult['FIELDS_SET_DEFAULT_VALUE'][] = 'INDUSTRY';
				$statusList = CCrmStatus::GetStatusList('INDUSTRY');
				$this->defaultEntityData['INDUSTRY'] = current(array_keys($statusList));
				if ($this->isSetDefaultValueForField($fieldsInfo, $requiredFields, 'INDUSTRY'))
				{
					$this->entityData['INDUSTRY'] = $this->defaultEntityData['INDUSTRY'];
				}
			}

			if ($this->isFieldHasDefaultValueAttribute($fieldsInfo, 'EMPLOYEES'))
			{
				$this->arResult['FIELDS_SET_DEFAULT_VALUE'][] = 'EMPLOYEES';
				$statusList = CCrmStatus::GetStatusList('EMPLOYEES');
				$this->defaultEntityData['EMPLOYEES'] = current(array_keys($statusList));
				if ($this->isSetDefaultValueForField($fieldsInfo, $requiredFields, 'EMPLOYEES'))
				{
					$this->entityData['EMPLOYEES'] = $this->defaultEntityData['EMPLOYEES'];
				}
			}

			//region Default Responsible
			if ($this->userID > 0)
			{
				$this->entityData['ASSIGNED_BY_ID'] = $this->userID;
			}
			//endregion

			\Bitrix\Crm\Entity\EntityEditor::mapRequestData(
				$this->prepareEntityDataScheme(),
				$this->entityData,
				$this->userFields
			);
		}
		else
		{
			$this->entityData = $this->loadEntityData();

			if (!isset($this->entityData['REVENUE']))
			{
				$this->entityData['REVENUE'] = 0.0;
			}

			if (!isset($this->entityData['CURRENCY_ID']) || $this->entityData['CURRENCY_ID'] === '')
			{
				$this->entityData['CURRENCY_ID'] = \CCrmCurrency::GetBaseCurrencyID();
			}

			if ($this->isCopyMode)
			{
				if ($this->userID > 0)
				{
					$this->entityData['ASSIGNED_BY_ID'] = $this->userID;
				}

				unset($this->entityData['LOGO']);
			}

			//region Observers
			$this->entityData['OBSERVER_IDS'] = Crm\Observer\ObserverManager::getEntityObserverIDs(
				CCrmOwnerType::Company,
				$this->entityID
			);
			//endregion

			$this->entityData = Crm\Entity\CommentsHelper::prepareFieldsFromDetailsToView(
				\CCrmOwnerType::Company,
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
		if (isset($this->entityData['ASSIGNED_BY_ID']) && $this->entityData['ASSIGNED_BY_ID'] > 0)
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
			if (is_array($user))
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

			if (!is_array($fieldData))
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
			if ($isEmptyField)
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

				if ($fieldData['data']['fieldInfo']['USER_TYPE_ID'] === 'file')
				{
					$values = is_array($fieldValue) ? $fieldValue : array($fieldValue);
					$this->entityData[$fieldName]['EXTRAS'] = array(
						'OWNER_TOKEN' => \CCrmFileProxy::PrepareOwnerToken(array_fill_keys($values, $this->entityID))
					);
				}
			}
		}
		//endregion

		//region Revenue & Currency
		$this->entityData['FORMATTED_REVENUE_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['REVENUE'] ?? 0,
			$this->entityData['CURRENCY_ID'],
			''
		);

		$this->entityData['FORMATTED_REVENUE'] = \CCrmCurrency::MoneyToString(
			$this->entityData['REVENUE'] ?? 0,
			$this->entityData['CURRENCY_ID'],
			'#'
		);
		//endregion

		//region Responsible
		$assignedByID = isset($this->entityData['ASSIGNED_BY_ID']) ? (int)$this->entityData['ASSIGNED_BY_ID'] : 0;
		if ($assignedByID > 0)
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
				? (int)$this->entityData['ASSIGNED_BY_PERSONAL_PHOTO']
				: 0;

			if ($assignedByPhotoID > 0)
			{
				$fileInfo = $file->ResizeImageGet(
					$assignedByPhotoID,
					array('width' => 60, 'height'=> 60),
					BX_RESIZE_IMAGE_EXACT
				);
				if (is_array($fileInfo) && isset($fileInfo['src']))
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
		//region Contact Data & Multifield Data
		$contactData = [];
		if ($this->entityID > 0)
		{
			CCrmComponentHelper::prepareMultifieldData(
				\CCrmOwnerType::Company,
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
					CCrmOwnerType::Company,
					$this->entityID,
					['VIEW_FORMATTED' => true, 'ADDRESS_AS_JSON' => true]
				);
			}
			//endregion
		}
		else
		{
			if (isset($this->defaultFieldValues['phone']))
			{
				$phone = trim($this->defaultFieldValues['phone']);
				if ($phone !== '')
				{
					$this->entityData['PHONE'] = array(
						array('ID' => 'n0', 'VALUE' => $phone, 'VALUE_TYPE' => 'WORK')
					);
				}
			}
		}

		$contactIDs = [];
		if ($this->entityID > 0)
		{
			$contactIDs = Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs($this->entityID);
		}
		elseif($this->conversionWizard !== null && isset($this->entityData['CONTACT_ID']))
		{
			$contactIDs = array($this->entityData['CONTACT_ID']);
		}

		foreach($contactIDs as $contactID)
		{
			$isEntityReadPermitted = CCrmContact::CheckReadPermission($contactID, $this->userPermissions);
			$contactData[] = CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::ContactName,
				$contactID,
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
		$this->entityData['CLIENT_INFO'] = array('CONTACT_DATA' => $contactData);

		if ($this->enableSearchHistory)
		{
			$categoryParams = CCrmComponentHelper::getEntityClientFieldCategoryParams(
				CCrmOwnerType::Company,
				(int)($this->arResult['CATEGORY_ID'] ?? $this->getCategoryId())
			);
			$this->entityData['LAST_CONTACT_INFOS'] = SearchAction::prepareSearchResultsJson(
				Crm\Controller\Entity::getRecentlyUsedItems(
					'crm.company.details',
					'contact',
					[
						'EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
						'EXPAND_CATEGORY_ID' => $categoryParams[CCrmOwnerType::Contact]['categoryId'],
					]
				)
			);
		}
		//endregion

		//region Images
		$logoID = isset($this->entityData['LOGO']) ? (int)$this->entityData['LOGO'] : 0;
		if ($logoID > 0)
		{
			$fileResizeInfo = $file->ResizeImageGet(
				$logoID,
				array('width' => 300, 'height'=> 300),
				BX_RESIZE_IMAGE_PROPORTIONAL
			);
			if (is_array($fileResizeInfo) && isset($fileResizeInfo['src']))
			{
				$this->entityData['LOGO_SHOW_URL'] = $fileResizeInfo['src'];
			}
		}
		//endregion

		if ($this->enableOutmodedFields)
		{
			$this->entityData['ADDRESS_HTML'] =
				AddressFormatter::getSingleInstance()->formatHtmlMultilineSpecialchar(
					CompanyAddress::mapEntityFields(
						$this->entityData,
						['TYPE_ID' => EntityAddressType::Primary]
					)
				);
			$this->entityData['REG_ADDRESS_HTML'] =
				AddressFormatter::getSingleInstance()->formatHtmlMultilineSpecialchar(
					CompanyAddress::mapEntityFields(
						$this->entityData,
						['TYPE_ID' => EntityAddressType::Registered]
					)
				);
		}

		Tracking\UI\Details::prepareEntityData(
			\CCrmOwnerType::Company,
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
					new Crm\ItemIdentifier(\CCrmOwnerType::Company, $this->entityID)
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
		if ($this->types === null)
		{
			$this->types = \CCrmStatus::GetStatusList('COMPANY_TYPE');
		}
		return $this->types;
	}
	public function prepareEntityInfo()
	{
		return CCrmEntitySelectorHelper::PrepareEntityInfo(
			\CCrmOwnerType::CompanyName,
			$this->entityID,
			array(
				'ENTITY_EDITOR_FORMAT' => true,
				'REQUIRE_REQUISITE_DATA' => true,
				'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
			)

		);
	}

	protected function tryGetFieldValueFromRequest($name, array &$params)
	{
		$value = $this->request->get($name);
		if ($value === null)
		{
			return false;
		}

		$params[$name] = $value;
		return true;
	}

	/**
	 * @return bool
	 */
	protected function isMyCompany()
	{
		if ($this->isMyCompany === null)
		{
			$this->isMyCompany = false;
			$isMyCompany = $this->request->get('mycompany');
			if (is_string($isMyCompany) && mb_strtoupper($isMyCompany) === 'Y')
			{
				$this->isMyCompany = true;
			}
			else
			{
				$entityData = $this->loadEntityData();
				if (isset($entityData['IS_MY_COMPANY']) && $entityData['IS_MY_COMPANY'] === 'Y')
				{
					$this->isMyCompany = true;
				}
			}
		}

		return $this->isMyCompany;
	}

	/**
	 * @return array
	 */
	protected function loadEntityData()
	{
		if ($this->rawEntityData === null)
		{
			$this->rawEntityData = [];
			if ($this->entityID > 0)
			{
				$dbResult = \CCrmCompany::GetListEx(
					[],
					['=ID' => $this->entityID, 'CHECK_PERMISSIONS' => 'N']
				);

				if (is_object($dbResult))
				{
					$this->rawEntityData = $dbResult->Fetch();
				}
			}
		}

		return $this->rawEntityData;
	}

	protected function getEventTabParams(): array
	{
		return CCrmComponentHelper::getEventTabParams(
			$this->entityID,
			Loc::getMessage('CRM_COMPANY_TAB_EVENT'),
			CCrmOwnerType::CompanyName,
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
			if (\CCrmCompany::CheckUpdatePermission($this->entityID, $this->userPermissions))
			{
				$this->arResult['READ_ONLY'] = false;
			}
		}
		elseif (\CCrmCompany::CheckCreatePermission($this->userPermissions, $this->arResult['CATEGORY_ID']))
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
		if (isset($this->arResult['LEAD_ID']) && $this->arResult['LEAD_ID'] > 0)
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
		$this->enableDupControl = (Crm\Integrity\DuplicateControl::isControlEnabledFor(CCrmOwnerType::Company));

		$this->arResult['DUPLICATE_CONTROL'] = [
			'enabled' => $this->enableDupControl,
			'isSingleMode' => $this->isEditMode,
		];

		if ($this->enableDupControl)
		{
			$this->arResult['DUPLICATE_CONTROL']['serviceUrl'] = '/bitrix/components/bitrix/crm.company.edit/ajax.php?'
				. bitrix_sessid_get();
			$this->arResult['DUPLICATE_CONTROL']['entityTypeName'] = CCrmOwnerType::CompanyName;
			$this->arResult['DUPLICATE_CONTROL']['groups'] = [
				'title' => [
					'parameterName' => 'TITLE',
					'groupType' => 'single',
					'groupSummaryTitle' => Loc::getMessage('CRM_COMPANY_DUP_CTRL_TTL_SUMMARY_TITLE'),
				],
				'email' => [
					'groupType' => 'communication',
					'communicationType' => 'EMAIL',
					'groupSummaryTitle' => Loc::getMessage('CRM_COMPANY_DUP_CTRL_EMAIL_SUMMARY_TITLE'),
				],
				'phone' => [
					'groupType' => 'communication',
					'communicationType' => 'PHONE',
					'groupSummaryTitle' => Loc::getMessage('CRM_COMPANY_DUP_CTRL_PHONE_SUMMARY_TITLE'),
				],
			];

			$this->arResult['DUPLICATE_CONTROL']['ignoredItems'] = [];
			if ($this->entityID)
			{
				$this->arResult['DUPLICATE_CONTROL']['ignoredItems'][] = [
					'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
					'ENTITY_ID' => $this->entityID,
				];
			}
			else
			{
				$this->arResult['DUPLICATE_CONTROL']['ignoredItems'][] = [
					'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
					'ENTITY_ID' => $this->leadID,
				];
				$leadContact = Crm\ContactTable::query()
					->where('LEAD_ID', $this->leadID)
					->setSelect(['ID'])
					->setOrder(['ID' => 'desc'])
					->setLimit(1)
					->exec()
					->fetch()['ID'] ?? null;
				if ($leadContact)
				{
					$this->arResult['DUPLICATE_CONTROL']['ignoredItems'][] = [
						'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
						'ENTITY_ID' => $leadContact,
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

		$this->arResult['PATH_TO_COMPANY_SHOW'] = \CrmCheckPath(
			'PATH_TO_COMPANY_SHOW',
			$this->arParams['PATH_TO_COMPANY_SHOW'] ?? '',
			$APPLICATION->GetCurPage() . '?company_id=#company_id#&show'
		);

		$this->arResult['PATH_TO_COMPANY_EDIT'] = \CrmCheckPath(
			'PATH_TO_COMPANY_EDIT',
			$this->arParams['PATH_TO_COMPANY_EDIT'] ?? '',
			$APPLICATION->GetCurPage() . '?company_id=#company_id#&edit'
		);
	}

	private function initializeContext(): void
	{
		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::CompanyName . '_' . $this->entityID;

		$this->arResult['CONTEXT'] = [
			'PARAMS' => [
				'PATH_TO_USER_PROFILE' => $this->arResult['PATH_TO_USER_PROFILE'],
				'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
			],
		];

		Crm\Service\EditorAdapter::addParentItemToContextIfFound($this->arResult['CONTEXT']);

		if ($this->isMyCompany())
		{
			$this->arResult['CONTEXT']['PARAMS']['IS_MY_COMPANY'] = 'Y';
		}

		if ($this->isCopyMode)
		{
			$this->arResult['CONTEXT']['PARAMS']['COMPANY_ID'] = $this->entityID;
		}

		if (!isset($this->arResult['CONTEXT']['PARAMS']['CATEGORY_ID']))
		{
			$this->arResult['CONTEXT']['PARAMS']['CATEGORY_ID'] = $this->getCategoryId();
		}

		if ($this->conversionWizard !== null)
		{
			$this->arResult['CONTEXT']['PARAMS'] = array_merge(
				$this->arResult['CONTEXT']['PARAMS'],
				$this->conversionWizard->prepareEditorContextParams(\CCrmOwnerType::Company)
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
			return (int)Container::getInstance()->getFactory(CCrmOwnerType::Company)->getItemCategoryId($this->entityID);
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
		$sourceId = ($sourceId === '') ? 'company_details' : $sourceId;

		if (!isset($this->arResult['CATEGORY_ID']))
		{
			$this->arResult['CATEGORY_ID'] = $this->getCategoryId();
		}

		return (new EditorHelper(\CCrmOwnerType::Company))->getEditorConfigId($this->arResult['CATEGORY_ID'], $sourceId);
	}
}
