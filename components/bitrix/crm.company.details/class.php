<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Attribute\FieldAttributeType;
use Bitrix\Crm\Attribute\FieldAttributePhaseGroupType;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\Format\CompanyAddressFormatter;
use Bitrix\Crm\Format\AddressSeparator;
use Bitrix\Crm\Tracking;

if(!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

Loc::loadMessages(__FILE__);

class CCrmCompanyDetailsComponent extends CBitrixComponent
{
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
	}
	public function initializeParams(array $params)
	{
		foreach($params as $k => $v)
		{
			if(!is_string($v))
			{
				continue;
			}

			if($k === 'PATH_TO_USER_PROFILE')
			{
				$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] = $v;
			}
			elseif($k === 'NAME_TEMPLATE')
			{
				$this->arResult['NAME_TEMPLATE'] = $this->arParams['NAME_TEMPLATE'] = $v;
			}
			elseif($k === 'LEAD_ID')
			{
				$this->arResult['LEAD_ID'] = $this->arParams['LEAD_ID'] = (int)$v;
			}
		}
	}
	public function executeComponent()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		$this->enableOutmodedFields = false;//\Bitrix\Crm\Settings\CompanySettings::getCurrent()->areOutmodedRequisitesEnabled();
		$this->isLocationModuleIncluded = Main\Loader::includeModule('location');

		//region Params
		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID']) ? (int)$this->arParams['~ENTITY_ID'] : 0;

		$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] =
			\CrmCheckPath('PATH_TO_USER_PROFILE', $this->arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams['NAME_TEMPLATE']);

		$this->arResult['PATH_TO_COMPANY_SHOW'] = \CrmCheckPath(
			'PATH_TO_COMPANY_SHOW',
			$this->arParams['PATH_TO_COMPANY_SHOW'],
			$APPLICATION->GetCurPage().'?company_id=#company_id#&show'
		);
		$this->arResult['PATH_TO_COMPANY_EDIT'] = \CrmCheckPath(
			'PATH_TO_COMPANY_EDIT',
			$this->arParams['PATH_TO_COMPANY_EDIT'],
			$APPLICATION->GetCurPage().'?company_id=#company_id#&edit'
		);

		$enableUfCreation = \CCrmAuthorizationHelper::CheckConfigurationUpdatePermission();
		$this->arResult['ENABLE_USER_FIELD_CREATION'] = $enableUfCreation;
		$this->arResult['USER_FIELD_ENTITY_ID'] = $this->userFieldEntityID;
		$this->arResult['USER_FIELD_CREATE_PAGE_URL'] = \CCrmOwnerType::GetUserFieldEditUrl($this->userFieldEntityID, 0);
		$this->arResult['USER_FIELD_CREATE_SIGNATURE'] = $enableUfCreation
			? $this->userFieldDispatcher->getCreateSignature(array('ENTITY_ID' => $this->userFieldEntityID))
			: '';
		$this->arResult['ENABLE_TASK'] = IsModuleInstalled('tasks');
		$this->arResult['ACTION_URI'] = $this->arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;

		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::CompanyName.'_'.$this->arResult['ENTITY_ID'];
		$this->arResult['CONTEXT_PARAMS'] = array(
			'PATH_TO_USER_PROFILE' => $this->arResult['PATH_TO_USER_PROFILE'],
			'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE']
		);

		$this->enableSearchHistory = !isset($this->arParams['~ENABLE_SEARCH_HISTORY'])
			|| mb_strtoupper($this->arParams['~ENABLE_SEARCH_HISTORY']) === 'Y';

		$this->setEntityID($this->arResult['ENTITY_ID']);

		if($this->isMyCompany())
		{
			$this->arResult['CONTEXT_PARAMS']['IS_MY_COMPANY'] = 'Y';
		}

		$this->arResult['EXTERNAL_CONTEXT_ID'] = $this->request->get('external_context_id');
		if($this->arResult['EXTERNAL_CONTEXT_ID'] === null)
		{
			$this->arResult['EXTERNAL_CONTEXT_ID'] = $this->request->get('external_context');
			if($this->arResult['EXTERNAL_CONTEXT_ID'] === null)
			{
				$this->arResult['EXTERNAL_CONTEXT_ID'] = '';
			}
		}

		$this->arResult['ORIGIN_ID'] = $this->request->get('origin_id');
		if($this->arResult['ORIGIN_ID'] === null)
		{
			$this->arResult['ORIGIN_ID'] = '';
		}

		$this->defaultFieldValues = array();
		$this->tryGetFieldValueFromRequest('phone', $this->defaultFieldValues);
		//endregion

		//region Is Editing or Copying?
		if($this->entityID > 0)
		{
			if(!\CCrmCompany::Exists($this->entityID))
			{
				ShowError(GetMessage('CRM_COMPANY_NOT_FOUND'));
				return;
			}

			if($this->request->get('copy') !== null)
			{
				$this->isCopyMode = true;
				$this->arResult['CONTEXT_PARAMS']['COMPANY_ID'] = $this->entityID;
			}
			else
			{
				$this->isEditMode = true;
			}
		}
		$this->arResult['IS_EDIT_MODE'] = $this->isEditMode;
		$this->arResult['IS_COPY_MODE'] = $this->isCopyMode;
		//endregion

		//region Is Control of Duplicates enabled?
		$this->arResult['DUPLICATE_CONTROL'] = array();
		$this->enableDupControl = $this->arResult['DUPLICATE_CONTROL']['enabled'] =
			!$this->isEditMode && \Bitrix\Crm\Integrity\DuplicateControl::isControlEnabledFor(CCrmOwnerType::Company);

		if($this->enableDupControl)
		{
			$this->arResult['DUPLICATE_CONTROL']['serviceUrl'] = '/bitrix/components/bitrix/crm.company.edit/ajax.php?'.bitrix_sessid_get();
			$this->arResult['DUPLICATE_CONTROL']['entityTypeName'] = CCrmOwnerType::CompanyName;
			$this->arResult['DUPLICATE_CONTROL']['groups'] = array(
				'title' => array(
					'parameterName' => 'TITLE',
					'groupType' => 'single',
					'groupSummaryTitle' => Loc::getMessage('CRM_COMPANY_DUP_CTRL_TTL_SUMMARY_TITLE')
				),
				'email' => array(
					'groupType' => 'communication',
					'communicationType' => 'EMAIL',
					'groupSummaryTitle' => Loc::getMessage('CRM_COMPANY_DUP_CTRL_EMAIL_SUMMARY_TITLE')
				),
				'phone' => array(
					'groupType' => 'communication',
					'communicationType' => 'PHONE',
					'groupSummaryTitle' => Loc::getMessage('CRM_COMPANY_DUP_CTRL_PHONE_SUMMARY_TITLE')
				)
			);
		}
		//endregion

		//region Conversion Scheme
		if(isset($this->arResult['LEAD_ID']) && $this->arResult['LEAD_ID'] > 0)
		{
			$this->leadID = $this->arResult['LEAD_ID'];
		}
		else
		{
			$leadID = $this->request->getQuery('lead_id');
			if($leadID > 0)
			{
				$this->leadID = $this->arResult['LEAD_ID'] = (int)$leadID;
			}
		}

		if($this->leadID > 0)
		{
			$this->conversionWizard = \Bitrix\Crm\Conversion\LeadConversionWizard::load($this->leadID);
			if($this->conversionWizard !== null)
			{
				$this->arResult['CONTEXT_PARAMS'] = array_merge(
					$this->arResult['CONTEXT_PARAMS'],
					$this->conversionWizard->prepareEditorContextParams(\CCrmOwnerType::Company)
				);
			}
		}
		//endregion

		//region Permissions check
		if($this->isMyCompany())
		{
			if (!$this->userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
			{
				ShowError(GetMessage('CRM_PERMISSION_DENIED'));
				return;
			}
		}
		else if($this->isCopyMode)
		{
			if(!(\CCrmCompany::CheckReadPermission($this->entityID, $this->userPermissions)
				&& \CCrmCompany::CheckCreatePermission($this->userPermissions))
			)
			{
				ShowError(GetMessage('CRM_PERMISSION_DENIED'));
				return;
			}
		}
		elseif($this->isEditMode)
		{
			if(\CCrmCompany::CheckUpdatePermission($this->entityID, $this->userPermissions))
			{
				$this->arResult['READ_ONLY'] = false;
			}
			elseif(\CCrmCompany::CheckReadPermission($this->entityID, $this->userPermissions))
			{
				$this->arResult['READ_ONLY'] = true;
			}
			else
			{
				ShowError(GetMessage('CRM_PERMISSION_DENIED'));
				return;
			}
		}
		else
		{
			if(\CCrmCompany::CheckCreatePermission($this->userPermissions))
			{
				$this->arResult['READ_ONLY'] = false;
			}
			else
			{
				ShowError(GetMessage('CRM_PERMISSION_DENIED'));
				return;
			}
		}
		//endregion

		$this->prepareEntityUserFields();
		$this->prepareEntityUserFieldInfos();
		$this->prepareEntityData();

		$this->arResult['IS_MY_COMPANY'] = $this->isMyCompany();

		//region GUID
		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID'])
			? $this->arParams['GUID']
			: ($this->isMyCompany() ? "my_company_{$this->entityID}_details" : "company_{$this->entityID}_details");

		$this->arResult['EDITOR_CONFIG_ID'] = isset($this->arParams['EDITOR_CONFIG_ID'])
			? $this->arParams['EDITOR_CONFIG_ID'] : $this->getDefaultConfigID();
		//endregion

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
		if($this->isCopyMode)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_COMPANY_COPY_PAGE_TITLE'));
		}
		elseif(isset($this->entityData['TITLE']))
		{
			$APPLICATION->SetTitle(htmlspecialcharsbx($this->entityData['TITLE']));
		}
		elseif(!$this->isEditMode)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_COMPANY_CREATION_PAGE_TITLE'));
		}
		//endregion

		//region Fields
		$this->prepareFieldInfos();

		$this->prepareEntityFieldAttributes();

		$this->arResult['ENTITY_FIELDS'] = $this->entityFieldInfos;
		$this->arResult['ENTITY_ATTRIBUTE_SCOPE'] = FieldAttributeManager::resolveEntityScope(
			CCrmOwnerType::Company,
			$this->entityID
		);
		//endregion

		//region Config
		$this->prepareConfiguration();
		//endregion

		//region CONTROLLERS
		$this->arResult['ENTITY_CONTROLLERS'] = array(
			array(
				"name" => "REQUISITE_CONTROLLER",
				"type" => "requisite_controller",
				"config" => array(
					'requisiteFieldId' => 'REQUISITES',
					'addressFieldId' => 'ADDRESS',
				)
			),
		);
		//endregion

		//region Validators
		$this->prepareValidators();
		//endregion

		//region TABS
		$this->arResult['TABS'] = array();
		if($this->entityID > 0)
		{
			if(!$this->isMyCompany())
			{
				$this->arResult['TABS'][] = array(
					'id' => 'tab_deal',
					'name' => Loc::getMessage('CRM_COMPANY_TAB_DEAL'),
					'loader' => array(
						'serviceUrl' => '/bitrix/components/bitrix/crm.deal.list/lazyload.ajax.php?&site'.SITE_ID.'&'.bitrix_sessid_get(),
						'componentData' => array(
							'template' => '',
							'params' => array(
								'DEAL_COUNT' => '20',
								'PATH_TO_DEAL_SHOW' => $this->arResult['PATH_TO_DEAL_SHOW'],
								'PATH_TO_DEAL_EDIT' => $this->arResult['PATH_TO_DEAL_EDIT'],
								'INTERNAL_FILTER' => array('COMPANY_ID' => $this->entityID),
								'INTERNAL_CONTEXT' => array('COMPANY_ID' => $this->entityID),
								'GRID_ID_SUFFIX' => 'COMPANY_DETAILS',
								'TAB_ID' => 'tab_deal',
								'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
								'ENABLE_TOOLBAR' => true,
								'PRESERVE_HISTORY' => true,
								'ADD_EVENT_NAME' => 'CrmCreateDealFromCompany'
							)
						)
					)
				);
				$this->arResult['TABS'][] = array(
					'id' => 'tab_quote',
					'name' => Loc::getMessage('CRM_COMPANY_TAB_QUOTE'),
					'loader' => array(
						'serviceUrl' => '/bitrix/components/bitrix/crm.quote.list/lazyload.ajax.php?&site'.SITE_ID.'&'.bitrix_sessid_get(),
						'componentData' => array(
							'template' => '',
							'params' => array(
								'QUOTE_COUNT' => '20',
								'PATH_TO_QUOTE_SHOW' => $this->arResult['PATH_TO_QUOTE_SHOW'],
								'PATH_TO_QUOTE_EDIT' => $this->arResult['PATH_TO_QUOTE_EDIT'],
								'INTERNAL_FILTER' => array('COMPANY_ID' => $this->entityID),
								'INTERNAL_CONTEXT' => array('COMPANY_ID' => $this->entityID),
								'GRID_ID_SUFFIX' => 'COMPANY_DETAILS',
								'TAB_ID' => 'tab_quote',
								'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
								'ENABLE_TOOLBAR' => true,
								'PRESERVE_HISTORY' => true,
								'ADD_EVENT_NAME' => 'CrmCreateQuoteFromCompany'
							)
						)
					)
				);
				$this->arResult['TABS'][] = array(
					'id' => 'tab_invoice',
					'name' => Loc::getMessage('CRM_COMPANY_TAB_INVOICES'),
					'loader' => array(
						'serviceUrl' => '/bitrix/components/bitrix/crm.invoice.list/lazyload.ajax.php?&site'.SITE_ID.'&'.bitrix_sessid_get(),
						'componentData' => array(
							'template' => '',
							'params' => array(
								'INVOICE_COUNT' => '20',
								'PATH_TO_COMPANY_SHOW' => $this->arResult['PATH_TO_COMPANY_SHOW'],
								'PATH_TO_COMPANY_EDIT' => $this->arResult['PATH_TO_COMPANY_EDIT'],
								'PATH_TO_CONTACT_EDIT' => $this->arResult['PATH_TO_CONTACT_EDIT'],
								'PATH_TO_DEAL_EDIT' => $this->arResult['PATH_TO_DEAL_EDIT'],
								'PATH_TO_INVOICE_EDIT' => $this->arResult['PATH_TO_INVOICE_EDIT'],
								'PATH_TO_INVOICE_PAYMENT' => $this->arResult['PATH_TO_INVOICE_PAYMENT'],
								'INTERNAL_FILTER' => array('UF_COMPANY_ID' => $this->entityID),
								'SUM_PAID_CURRENCY' => \CCrmCurrency::GetBaseCurrencyID(),
								'GRID_ID_SUFFIX' => 'COMPANY_DETAILS',
								'TAB_ID' => 'tab_invoice',
								'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
								'ENABLE_TOOLBAR' => 'Y',
								'PRESERVE_HISTORY' => true,
								'ADD_EVENT_NAME' => 'CrmCreateInvoiceFromCompany'
							)
						)
					)
				);
				if (CModule::IncludeModule('sale') && Main\Config\Option::get("crm", "crm_shop_enabled") === "Y")
				{
					$this->arResult['TABS'][] = array(
						'id' => 'tab_order',
						'name' => Loc::getMessage('CRM_COMPANY_TAB_ORDERS'),
						'loader' => array(
							'serviceUrl' => '/bitrix/components/bitrix/crm.order.list/lazyload.ajax.php?&site'.SITE_ID.'&'.bitrix_sessid_get(),
							'componentData' => array(
								'template' => '',
								'params' => array(
									'INVOICE_COUNT' => '20',
									'PATH_TO_COMPANY_SHOW' => $this->arResult['PATH_TO_COMPANY_SHOW'],
									'PATH_TO_COMPANY_EDIT' => $this->arResult['PATH_TO_COMPANY_EDIT'],
									'PATH_TO_CONTACT_EDIT' => $this->arResult['PATH_TO_CONTACT_EDIT'],
									'PATH_TO_DEAL_EDIT' => $this->arResult['PATH_TO_DEAL_EDIT'],
									'PATH_TO_INVOICE_EDIT' => $this->arResult['PATH_TO_INVOICE_EDIT'],
									'PATH_TO_INVOICE_PAYMENT' => $this->arResult['PATH_TO_INVOICE_PAYMENT'],
									'INTERNAL_FILTER' => array('COMPANY_ID' => $this->entityID),
									'SUM_PAID_CURRENCY' => \CCrmCurrency::GetBaseCurrencyID(),
									'GRID_ID_SUFFIX' => 'COMPANY_DETAILS',
									'TAB_ID' => 'tab_order',
									'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
									'ENABLE_TOOLBAR' => 'Y',
									'PRESERVE_HISTORY' => true,
									'ADD_EVENT_NAME' => 'CrmCreateOrderFromCompany'
								)
							)
						)
					);
				}
				if (CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled())
				{
					$this->arResult['TABS'][] = array(
						'id' => 'tab_bizproc',
						'name' => Loc::getMessage('CRM_COMPANY_TAB_BIZPROC'),
						'loader' => array(
							'serviceUrl' => '/bitrix/components/bitrix/bizproc.document/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
							'componentData' => array(
								'template' => 'frame',
								'params' => array(
									'MODULE_ID' => 'crm',
									'ENTITY' => 'CCrmDocumentCompany',
									'DOCUMENT_TYPE' => 'COMPANY',
									'DOCUMENT_ID' => 'COMPANY_'.$this->entityID
								)
							)
						)
					);
					$this->arResult['BIZPROC_STARTER_DATA'] = array(
						'templates' => CBPDocument::getTemplatesForStart(
							$this->userID,
							array('crm', 'CCrmDocumentCompany', 'COMPANY'),
							array('crm', 'CCrmDocumentCompany', 'COMPANY_'.$this->entityID),
							[
								'DocumentStates' => []
							]
						),
						'moduleId' => 'crm',
						'entity' => 'CCrmDocumentCompany',
						'documentType' => 'COMPANY',
						'documentId' => 'COMPANY_'.$this->entityID
					);
				}
				$this->arResult['TABS'][] = array(
					'id' => 'tab_tree',
					'name' => Loc::getMessage('CRM_COMPANY_TAB_TREE'),
					'loader' => array(
						'serviceUrl' => '/bitrix/components/bitrix/crm.entity.tree/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
						'componentData' => array(
							'template' => '.default',
							'params' => array(
								'ENTITY_ID' => $this->entityID,
								'ENTITY_TYPE_NAME' => CCrmOwnerType::CompanyName,
							)
						)
					)
				);
				$this->arResult['TABS'][] = array(
				'id' => 'tab_event',
				'name' => Loc::getMessage('CRM_COMPANY_TAB_EVENT'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.event.view/lazyload.ajax.php?&site'.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '',
						'contextId' => "COMPANY_{$this->entityID}_EVENT",
						'params' => array(
							'AJAX_OPTION_ADDITIONAL' => "COMPANY_{$this->entityID}_EVENT",
							'ENTITY_TYPE' => CCrmOwnerType::CompanyName,
							'ENTITY_ID' => $this->entityID,
							'PATH_TO_USER_PROFILE' => $this->arResult['PATH_TO_USER_PROFILE'],
							'TAB_ID' => 'tab_event',
							'INTERNAL' => 'Y',
							'SHOW_INTERNAL_FILTER' => 'Y',
							'PRESERVE_HISTORY' => true,
							'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE']
						)
					)
				)
			);

				$this->arResult['TABS'][] = array(
					'id' => 'tab_portrait',
					'name' => Loc::getMessage('CRM_COMPANY_TAB_PORTRAIT'),
					'loader' => array(
						'serviceUrl' => '/bitrix/components/bitrix/crm.client.portrait/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
						'componentData' => array(
							'template' => '.default',
							'params' => array(
								'ELEMENT_ID' => $this->entityID,
								'ELEMENT_TYPE' => CCrmOwnerType::Company,
								'IS_FRAME' => 'Y'
							)
						)
					)
				);

				if (CModule::IncludeModule('lists'))
				{
					$listIblock = CLists::getIblockAttachedCrm(CCrmOwnerType::CompanyName);
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
				$this->arResult['TABS'][] = array(
				'id' => 'tab_event',
				'name' => Loc::getMessage('CRM_COMPANY_TAB_EVENT'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.event.view/lazyload.ajax.php?&site'.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '',
						'contextId' => "COMPANY_{$this->entityID}_EVENT",
						'params' => array(
							'AJAX_OPTION_ADDITIONAL' => "COMPANY_{$this->entityID}_EVENT",
							'ENTITY_TYPE' => CCrmOwnerType::CompanyName,
							'ENTITY_ID' => $this->entityID,
							'PATH_TO_USER_PROFILE' => $this->arResult['PATH_TO_USER_PROFILE'],
							'TAB_ID' => 'tab_event',
							'INTERNAL' => 'Y',
							'SHOW_INTERNAL_FILTER' => 'Y',
							'PRESERVE_HISTORY' => true,
							'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE']
						)
					)
				)
			);
			}

		}
		else
		{
			$this->arResult['TABS'][] = array(
				'id' => 'tab_deal',
				'name' => Loc::getMessage('CRM_COMPANY_TAB_DEAL'),
				'enabled' => false
			);
			$this->arResult['TABS'][] = array(
				'id' => 'tab_quote',
				'name' => Loc::getMessage('CRM_COMPANY_TAB_QUOTE'),
				'enabled' => false
			);
			$this->arResult['TABS'][] = array(
				'id' => 'tab_invoice',
				'name' => Loc::getMessage('CRM_COMPANY_TAB_INVOICES'),
				'enabled' => false
			);
			if (CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled())
			{
				$this->arResult['TABS'][] = array(
					'id' => 'tab_bizproc',
					'name' => Loc::getMessage('CRM_COMPANY_TAB_BIZPROC'),
					'enabled' => false
				);
			}
			$this->arResult['TABS'][] = array(
				'id' => 'tab_event',
				'name' => Loc::getMessage('CRM_COMPANY_TAB_EVENT'),
				'enabled' => false
			);
			$this->arResult['TABS'][] = array(
				'id' => 'tab_portrait',
				'name' => Loc::getMessage('CRM_COMPANY_TAB_PORTRAIT'),
				'enabled' => false
			);
			if (CModule::IncludeModule('lists'))
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
		if($this->entityID > 0 && \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isViewEventEnabled())
		{
			CCrmEvent::RegisterViewEvent(CCrmOwnerType::Company, $this->entityID, $this->userID);
		}
		//endregion

		$this->arResult['USER_FIELD_FILE_URL_TEMPLATE'] = $this->getFileUrlTemplate();

		$this->includeComponentTemplate();
	}
	public function getDefaultConfigID()
	{
		return 'company_details';
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
							array('name' => 'COMMENTS'),
							array('name' => 'UTM'),
						),
						$userFieldConfigElements
					)
			)
		);

		return $this->arResult['ENTITY_CONFIG'];
	}
	public function isSearchHistoryEnabled()
	{
		return $this->enableSearchHistory;
	}
	public function enableSearchHistory($enable)
	{
		if(!is_bool($enable))
		{
			$enable = (bool)$enable;
		}
		$this->enableSearchHistory = $enable;
	}
	public function getEntityID()
	{
		return $this->entityID;
	}
	public function setEntityID($entityID)
	{
		$this->entityID = $entityID;

		$this->userFields = null;
		$this->prepareEntityUserFields();

		$this->userFieldInfos = null;
		$this->prepareEntityUserFieldInfos();
	}
	public function prepareEntityDataScheme()
	{
		if($this->entityDataScheme === null)
		{
			$this->entityDataScheme = \CCrmCompany::GetFieldsInfo();
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
							'NOT_SELECTED_VALUE' => ''
						]
					),
					'defaultValue' => $this->defaultEntityData['COMPANY_TYPE'] ?? null
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
							'NOT_SELECTED_VALUE' => ''
						]
					),
					'defaultValue' => $this->defaultEntityData['INDUSTRY'] ?? null
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
							'NOT_SELECTED_VALUE' => ''
						]
					),
					'defaultValue' => $this->defaultEntityData['EMPLOYEES'] ?? null
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
			array(
				'name' => 'COMMENTS',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_COMMENTS'),
				'type' => 'html',
				'editable' => true
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
					'compound' => array(
						array(
							'name' => 'CONTACT_ID',
							'type' => 'multiple_contact',
							'entityTypeName' => \CCrmOwnerType::ContactName,
							'tagName' => \CCrmOwnerType::ContactName
						)
					),
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
					'clientEditorFieldsParams' => $this->prepareClientEditorFieldsParams()
				)
			),
			array(
				'name' => 'REQUISITES',
				'title' => Loc::getMessage('CRM_COMPANY_FIELD_REQUISITES'),
				'type' => 'requisite',
				'editable' => true,
				'data' => \CCrmComponentHelper::getFieldInfoData(CCrmOwnerType::Company,'requisite'),
                'enableAttributes' => false
			)
		);

		Tracking\UI\Details::appendEntityFields($this->entityFieldInfos);
		$this->entityFieldInfos[] = array(
			'name' => 'UTM',
			'title' => Loc::getMessage('CRM_COMPANY_FIELD_UTM'),
			'type' => 'custom',
			'data' => array('view' => 'UTM_VIEW_HTML'),
			'editable' => false,
			'enableAttributes' => false
		);

		if($this->enableOutmodedFields)
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
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_ADDRESS'),
				'type' => 'requisite_address',
				'editable' => true,
                'enableAttributes' => false,
				'virtual' => true,
				'data' => \CCrmComponentHelper::getFieldInfoData(CCrmOwnerType::Company,'requisite_address')
			);
		}

		foreach($this->multiFieldInfos as $typeName => $typeInfo)
		{
			$valueTypes = isset($this->multiFieldValueTypeInfos[$typeName])
				? $this->multiFieldValueTypeInfos[$typeName] : array();

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

		return $this->entityFieldInfos;
	}
	public function prepareEntityUserFields()
	{
		if($this->userFields === null)
		{
			if($this->isMyCompany())
			{
				$this->userType->setOptions(['isMyCompany' => true]);
			}
			$this->userFields = $this->userType->GetEntityFields($this->entityID);
		}
		return $this->userFields;
	}
	public function prepareEntityFieldAttributeConfigs()
	{
		if(!$this->entityFieldAttributeConfig)
		{
			$this->entityFieldAttributeConfig = FieldAttributeManager::getEntityConfigurations(
				CCrmOwnerType::Company,
				FieldAttributeManager::resolveEntityScope(
					CCrmOwnerType::Company,
					$this->entityID
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

		$isEntityDataModified = false;
		$attrConfigs = $this->prepareEntityFieldAttributeConfigs();
		for($i = 0, $length = count($this->entityFieldInfos); $i < $length; $i++)
		{
			$isPhaseDependent = FieldAttributeManager::isPhaseDependent();
			if (!$isPhaseDependent)
			{
				if (!is_array($this->entityFieldInfos[$i]['data']))
				{
					$this->entityFieldInfos[$i]['data'] = [];
				}
				$this->entityFieldInfos[$i]['data']['isPhaseDependent'] = false;
			}

			$fieldName = $this->entityFieldInfos[$i]['name'];
			if(!isset($attrConfigs[$fieldName]))
			{
				continue;
			}

			if(!isset($this->entityFieldInfos[$i]['data']))
			{
				$this->entityFieldInfos[$i]['data'] = array();
			}

			$this->entityFieldInfos[$i]['data']['attrConfigs'] = $attrConfigs[$fieldName];

			if (is_array($attrConfigs[$fieldName]) && !empty($attrConfigs[$fieldName]))
			{
				$isRequiredByAttribute = false;
				$ready = false;
				$attrConfig = $attrConfigs[$fieldName];
				foreach ($attrConfig as $item)
				{
					if (is_array($item) && isset($item['typeId'])
						&& $item['typeId'] === FieldAttributeType::REQUIRED)
					{
						if ($isPhaseDependent)
						{
							if (is_array($item['groups']))
							{
								foreach ($item['groups'] as $group)
								{
									if (is_array($group) && isset($group['phaseGroupTypeId'])
										&& $group['phaseGroupTypeId'] === FieldAttributePhaseGroupType::ALL)
									{
										$isRequiredByAttribute = true;
										$ready = true;
										break;
									}
								}
							}
						}
						else
						{
							$isRequiredByAttribute = true;
							$ready = true;
						}
						if ($ready)
						{
							break;
						}
					}
				}
				if ($isRequiredByAttribute)
				{
					if (!is_array($this->entityFieldInfos[$i]['data']))
					{
						$this->entityFieldInfos[$i]['data'] = [];
					}
					$this->entityFieldInfos[$i]['data']['isRequiredByAttribute'] = true;

					// This block allows in the component crm.entity.editor to determine the presence of mandatory
					// standard entity fields with empty values.
					if (is_array($this->entityData)
						&& $this->isEntityFieldHasEmpyValue($this->entityFieldInfos[$i]))
					{
						if (!is_array($this->entityData['EMPTY_REQUIRED_SYSTEM_FIELD_MAP']))
						{
							$this->entityData['EMPTY_REQUIRED_SYSTEM_FIELD_MAP'] = [];
						}
						$this->entityData['EMPTY_REQUIRED_SYSTEM_FIELD_MAP'][$fieldName] = true;
						$isEntityDataModified = true;
					}
				}
			}
		}

		if ($isEntityDataModified)
		{
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
					'COMMENTS'                           // html
				];
				if (in_array($fieldName, $fieldsToCheck, true))
				{
					switch ($fieldType)
					{
						case 'text':
						case 'html':
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
				CCrmOwnerType::Company,
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

			$this->entityData['CURRENCY_ID'] = \CCrmCurrency::GetBaseCurrencyID();
			$this->entityData['OPENED'] = \Bitrix\Crm\Settings\CompanySettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
		}
		else if($this->entityID <= 0)
		{
			$requiredFields = Crm\Attribute\FieldAttributeManager::isEnabled()
				? Crm\Attribute\FieldAttributeManager::getRequiredFields(
					CCrmOwnerType::Company,
					$this->entityID,
					['COMPANY_TYPE', 'INDUSTRY', 'EMPLOYEES', Tracking\UI\Details::SourceId],
					Crm\Attribute\FieldOrigin::SYSTEM
				)
				: [];
			$isTrackingFieldRequired = in_array(Tracking\UI\Details::SourceId, $requiredFields, true);
			$fieldsInfo = CCrmCompany::GetFieldsInfo();
			$this->entityData = [];
			//leave REVENUE unassigned
			//$this->entityData['REVENUE'] = 0.0;
			$this->entityData['CURRENCY_ID'] = \CCrmCurrency::GetBaseCurrencyID();
			$this->entityData['OPENED'] = \Bitrix\Crm\Settings\CompanySettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';

			if($this->isMyCompany())
			{
				$this->entityData['IS_MY_COMPANY'] = 'Y';
			}

			// set first option by default if the field is not required
			$typeList = $this->prepareTypeList();
			if(
				!empty($typeList)
				&& $this->isFieldHasDefaultValueAttribute($fieldsInfo, 'COMPANY_TYPE'))
			{
				$this->arResult['FIELDS_SET_DEFAULT_VALUE'][] = 'COMPANY_TYPE';
				$this->defaultEntityData['COMPANY_TYPE'] = current(array_keys($typeList));
				if($this->isSetDefaultValueForField($fieldsInfo, $requiredFields, 'COMPANY_TYPE'))
				{
					$this->entityData['COMPANY_TYPE'] = $this->defaultEntityData['COMPANY_TYPE'];
				}
			}
			unset($typeList);

			if($this->isFieldHasDefaultValueAttribute($fieldsInfo, 'INDUSTRY'))
			{
				$this->arResult['FIELDS_SET_DEFAULT_VALUE'][] = 'INDUSTRY';
				$statusList = CCrmStatus::GetStatusList('INDUSTRY');
				$this->defaultEntityData['INDUSTRY'] = current(array_keys($statusList));
				if($this->isSetDefaultValueForField($fieldsInfo, $requiredFields, 'INDUSTRY'))
				{
					$this->entityData['INDUSTRY'] = $this->defaultEntityData['INDUSTRY'];
				}
			}
			
			if($this->isFieldHasDefaultValueAttribute($fieldsInfo, 'EMPLOYEES'))
			{
				$this->arResult['FIELDS_SET_DEFAULT_VALUE'][] = 'EMPLOYEES';
				$statusList = CCrmStatus::GetStatusList('EMPLOYEES');
				$this->defaultEntityData['EMPLOYEES'] = current(array_keys($statusList));
				if($this->isSetDefaultValueForField($fieldsInfo, $requiredFields, 'EMPLOYEES'))
				{
					$this->entityData['EMPLOYEES'] = $this->defaultEntityData['EMPLOYEES'];
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
		}
		else
		{
			$this->entityData = $this->loadEntityData();

			if(!isset($this->entityData['REVENUE']))
			{
				$this->entityData['REVENUE'] = 0.0;
			}

			if(!isset($this->entityData['CURRENCY_ID']) || $this->entityData['CURRENCY_ID'] === '')
			{
				$this->entityData['CURRENCY_ID'] = \CCrmCurrency::GetBaseCurrencyID();
			}

			if($this->isCopyMode)
			{
				if($this->userID > 0)
				{
					$this->entityData['ASSIGNED_BY_ID'] = $this->userID;
				}

				unset($this->entityData['LOGO']);
			}

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
			$by = 'ID';
			$order = 'ASC';
			$dbUsers = \CUser::GetList(
				$by, $order,
				array('ID' => $this->entityData['ASSIGNED_BY_ID']),
				array(
					'FIELDS' => array(
						'ID',  'LOGIN', 'PERSONAL_PHOTO',
						'NAME', 'SECOND_NAME', 'LAST_NAME'
					)
				)
			);
			unset($by, $order);
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

		//region Revenue & Currency
		$this->entityData['FORMATTED_REVENUE_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['REVENUE'],
			$this->entityData['CURRENCY_ID'],
			''
		);

		$this->entityData['FORMATTED_REVENUE'] = \CCrmCurrency::MoneyToString(
			$this->entityData['REVENUE'],
			$this->entityData['CURRENCY_ID'],
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
		//region Contact Data & Multifield Data
		$contactData = array();
		$multiFieldData = array();
		if($this->entityID > 0)
		{
			$multiFieldDbResult = \CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array(
					'ENTITY_ID' => CCrmOwnerType::CompanyName,
					'ELEMENT_ID' => $this->entityID
				)
			);

			$entityKey = CCrmOwnerType::Company.'_'.$this->entityID;
			$multiFieldEntityTypes = \CCrmFieldMulti::GetEntityTypes();
			$multiFieldViewClassNames = array(
				'PHONE' => 'crm-entity-phone-number',
				'EMAIL' => 'crm-entity-email',
				'IM' => 'crm-entity-phone-number'

			);

			while($multiField = $multiFieldDbResult->Fetch())
			{
				$typeID = $multiField['TYPE_ID'];
				if(!isset($this->entityData[$typeID]))
				{
					$this->entityData[$typeID] = array();
				}

				$multiFieldID = $multiField['ID'];
				if($this->isCopyMode)
				{
					$multiFieldID = "n0{$multiFieldID}";
				}

				$multiFieldComplexID = $multiField['COMPLEX_ID'];
				$value = $multiField['VALUE'];
				$valueType = $multiField['VALUE_TYPE'];
				$multiFieldEntityType = $multiFieldEntityTypes[$typeID];

				$this->entityData[$typeID][] = array(
					'ID' => $multiFieldID,
					'VALUE' => $value,
					'VALUE_TYPE' => $valueType,
					'VIEW_DATA' => \CCrmViewHelper::PrepareMultiFieldValueItemData(
						$typeID,
						array(
							'VALUE' => $value,
							'VALUE_TYPE_ID' => $valueType,
							'VALUE_TYPE' => isset($multiFieldEntityType[$valueType]) ? $multiFieldEntityType[$valueType] : null,
							'CLASS_NAME' => isset($multiFieldViewClassNames[$typeID]) ? $multiFieldViewClassNames[$typeID] : ''
						),
						array(
							'ENABLE_SIP' => false,
							'SIP_PARAMS' => array(
								'ENTITY_TYPE_NAME' => CCrmOwnerType::CompanyName,
								'ENTITY_ID' => $this->entityID,
								'AUTO_FOLD' => true
							)
						)
					)
				);

				//Is required for phone & email & messenger menu
				if($typeID === 'PHONE' || $typeID === 'EMAIL'
					|| ($typeID === 'IM' && preg_match('/^imol\|/', $value) === 1)
				)
				{
					if(!isset($multiFieldData[$typeID]))
					{
						$multiFieldData[$typeID] = array();
					}

					if(!isset($multiFieldData[$typeID][$entityKey]))
					{
						$multiFieldData[$typeID][$entityKey] = array();
					}

					$formattedValue = $typeID === 'PHONE'
						? Main\PhoneNumber\Parser::getInstance()->parse($value)->format()
						: $value;

					$multiFieldData[$typeID][$entityKey][] = array(
						'ID' => $multiFieldID,
						'VALUE' => $value,
						'VALUE_TYPE' => $valueType,
						'VALUE_FORMATTED' => $formattedValue,
						'COMPLEX_ID' => $multiFieldComplexID,
						'COMPLEX_NAME' => \CCrmFieldMulti::GetEntityNameByComplex($multiFieldComplexID, false)
					);
				}
			}

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
		$this->entityData['MULTIFIELD_DATA'] = $multiFieldData;

		$contactIDs = array();
		if($this->entityID > 0)
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
					'REQUIRE_REQUISITE_DATA' => false,
					'REQUIRE_MULTIFIELDS' => true,
					'NORMALIZE_MULTIFIELDS' => true,
					'REQUIRE_BINDINGS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
				)
			);
		}
		$this->entityData['CLIENT_INFO'] = array('CONTACT_DATA' => $contactData);

		if($this->enableSearchHistory)
		{
			$this->entityData['LAST_CONTACT_INFOS'] = Crm\Controller\Action\Entity\SearchAction::prepareSearchResultsJson(
				Crm\Controller\Entity::getRecentlyUsedItems(
					'crm.company.details',
					'contact',
					array('EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Contact)
				)
			);
		}
		//endregion

		//region Images
		$logoID = isset($this->entityData['LOGO']) ? (int)$this->entityData['LOGO'] : 0;
		if($logoID > 0)
		{
			$fileResizeInfo = $file->ResizeImageGet(
				$logoID,
				array('width' => 300, 'height'=> 300),
				BX_RESIZE_IMAGE_PROPORTIONAL
			);
			if(is_array($fileResizeInfo) && isset($fileResizeInfo['src']))
			{
				$this->entityData['LOGO_SHOW_URL'] = $fileResizeInfo['src'];
			}
		}
		//endregion

		if($this->enableOutmodedFields)
		{
			$this->entityData['ADDRESS_HTML'] = CompanyAddressFormatter::format(
				$this->entityData,
				array(
					'TYPE_ID' => EntityAddressType::Primary,
					'SEPARATOR' => AddressSeparator::HtmlLineBreak,
					'NL2BR' => true,
					'HTML_ENCODE' => true
				)
			);

			$this->entityData['REG_ADDRESS_HTML'] = CompanyAddressFormatter::format(
				$this->entityData,
				array(
					'TYPE_ID' => EntityAddressType::Registered,
					'SEPARATOR' => AddressSeparator::HtmlLineBreak,
					'NL2BR' => true,
					'HTML_ENCODE' => true
				)
			);
		}

		Tracking\UI\Details::prepareEntityData(
			\CCrmOwnerType::Company,
			$this->entityID,
			$this->entityData,
			$isTrackingFieldRequired
		);

		return ($this->arResult['ENTITY_DATA'] = $this->entityData);
	}
	protected function prepareTypeList()
	{
		if($this->types === null)
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
		if($value === null)
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
		if($this->isMyCompany === null)
		{
			$this->isMyCompany = false;
			$isMyCompany = $this->request->get('mycompany');
			if(is_string($isMyCompany) && mb_strtoupper($isMyCompany) === 'Y')
			{
				$this->isMyCompany = true;
			}
			else
			{
				$entityData = $this->loadEntityData();
				if(isset($entityData['IS_MY_COMPANY']) && $entityData['IS_MY_COMPANY'] === 'Y')
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
		if($this->rawEntityData === null)
		{
			$this->rawEntityData = [];
			if($this->entityID > 0)
			{
				$dbResult = \CCrmCompany::GetListEx(
					[],
					['=ID' => $this->entityID, 'CHECK_PERMISSIONS' => 'N']
				);

				if(is_object($dbResult))
				{
					$this->rawEntityData = $dbResult->Fetch();
				}
			}
		}

		return $this->rawEntityData;
	}

	protected function getFileUrlTemplate(): string
	{
		return '/bitrix/components/bitrix/crm.company.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#';
	}

	protected function prepareClientEditorFieldsParams(): array
	{
		$result = [
			CCrmOwnerType::ContactName => [
				'REQUISITES' => \CCrmComponentHelper::getFieldInfoData(CCrmOwnerType::Contact, 'requisite')
			],
			CCrmOwnerType::CompanyName => [
				'REQUISITES' => \CCrmComponentHelper::getFieldInfoData(CCrmOwnerType::Company, 'requisite')
			]
		];
		if ($this->isLocationModuleIncluded)
		{
			$result[CCrmOwnerType::ContactName]['ADDRESS'] = \CCrmComponentHelper::getFieldInfoData(CCrmOwnerType::Contact,'requisite_address');
			$result[CCrmOwnerType::CompanyName]['ADDRESS'] = \CCrmComponentHelper::getFieldInfoData(CCrmOwnerType::Company,'requisite_address');
		}

		return $result;
	}
}