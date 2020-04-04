<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\Format\ContactAddressFormatter;
use Bitrix\Crm\Format\AddressSeparator;
use Bitrix\Crm\EntityAddress;

if(!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

Loc::loadMessages(__FILE__);

class CCrmContactDetailsComponent extends CBitrixComponent
{
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
	private $entityData = null;
	/** @var array|null */
	private $entityDataScheme = null;
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
	/** @var bool */
	private $enableSearchHistory = true;

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

		$this->enableOutmodedFields = \Bitrix\Crm\Settings\ContactSettings::getCurrent()->areOutmodedRequisitesEnabled();

		//region Params
		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID']) ? (int)$this->arParams['~ENTITY_ID'] : 0;

		$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] =
			\CrmCheckPath('PATH_TO_USER_PROFILE', $this->arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams['NAME_TEMPLATE']);

		$this->arResult['PATH_TO_CONTACT_SHOW'] = \CrmCheckPath(
			'PATH_TO_CONTACT_SHOW',
			$this->arParams['PATH_TO_CONTACT_SHOW'],
			$APPLICATION->GetCurPage().'?contact_id=#contact_id#&show'
		);
		$this->arResult['PATH_TO_CONTACT_EDIT'] = \CrmCheckPath(
			'PATH_TO_CONTACT_EDIT',
			$this->arParams['PATH_TO_CONTACT_EDIT'],
			$APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit'
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

		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::ContactName.'_'.$this->arResult['ENTITY_ID'];
		$this->arResult['CONTEXT_PARAMS'] = array(
			'PATH_TO_USER_PROFILE' => $this->arResult['PATH_TO_USER_PROFILE'],
			'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE']
		);

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
		$this->tryGetFieldValueFromRequest('title', $this->defaultFieldValues);
		$this->tryGetFieldValueFromRequest('phone', $this->defaultFieldValues);
		//endregion

		$this->enableSearchHistory = !isset($this->arParams['~ENABLE_SEARCH_HISTORY'])
			|| strtoupper($this->arParams['~ENABLE_SEARCH_HISTORY']) === 'Y';

		$this->setEntityID($this->arResult['ENTITY_ID']);

		//region Is Editing or Copying?
		if($this->entityID > 0)
		{
			if(!\CCrmContact::Exists($this->entityID))
			{
				ShowError(GetMessage('CRM_CONTACT_NOT_FOUND'));
				return;
			}

			if($this->request->get('copy') !== null)
			{
				$this->isCopyMode = true;
				$this->arResult['CONTEXT_PARAMS']['CONTACT_ID'] = $this->entityID;
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
			!$this->isEditMode && \Bitrix\Crm\Integrity\DuplicateControl::isControlEnabledFor(CCrmOwnerType::Contact);

		if($this->enableDupControl)
		{
			$this->arResult['DUPLICATE_CONTROL']['serviceUrl'] = '/bitrix/components/bitrix/crm.contact.edit/ajax.php?'.bitrix_sessid_get();
			$this->arResult['DUPLICATE_CONTROL']['entityTypeName'] = CCrmOwnerType::ContactName;
			$this->arResult['DUPLICATE_CONTROL']['groups'] = array(
				'fullName' => array(
					'groupType' => 'fullName',
					'groupSummaryTitle' => Loc::getMessage('CRM_CONTACT_DUP_CTRL_FULL_NAME_SUMMARY_TITLE')
				),
				'email' => array(
					'groupType' => 'communication',
					'communicationType' => 'EMAIL',
					'groupSummaryTitle' => Loc::getMessage('CRM_CONTACT_DUP_CTRL_EMAIL_SUMMARY_TITLE')
				),
				'phone' => array(
					'groupType' => 'communication',
					'communicationType' => 'PHONE',
					'groupSummaryTitle' => Loc::getMessage('CRM_CONTACT_DUP_CTRL_PHONE_SUMMARY_TITLE')
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
					$this->conversionWizard->prepareEditorContextParams(\CCrmOwnerType::Contact)
				);
			}
		}
		//endregion

		//region Permissions check
		if($this->isCopyMode)
		{
			if(!(\CCrmContact::CheckReadPermission($this->entityID, $this->userPermissions)
				&& \CCrmContact::CheckCreatePermission($this->userPermissions))
			)
			{
				ShowError(GetMessage('CRM_PERMISSION_DENIED'));
				return;
			}
		}
		elseif($this->isEditMode)
		{
			if(\CCrmContact::CheckUpdatePermission($this->entityID, $this->userPermissions))
			{
				$this->arResult['READ_ONLY'] = false;
			}
			elseif(\CCrmContact::CheckReadPermission($this->entityID, $this->userPermissions))
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
			if(\CCrmContact::CheckCreatePermission($this->userPermissions))
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

		//region GUID
		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID'])
			? $this->arParams['GUID'] : "contact_{$this->entityID}_details";

		$this->arResult['EDITOR_CONFIG_ID'] = isset($this->arParams['EDITOR_CONFIG_ID'])
			? $this->arParams['EDITOR_CONFIG_ID'] : $this->getDefaultConfigID();
		//endregion

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
			$APPLICATION->SetTitle(Loc::getMessage('CRM_CONTACT_COPY_PAGE_TITLE'));
		}
		elseif(isset($this->entityData['FORMATTED_NAME']))
		{
			$APPLICATION->SetTitle(htmlspecialcharsbx($this->entityData['FORMATTED_NAME']));
		}
		elseif(!$this->isEditMode)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_CONTACT_CREATION_PAGE_TITLE'));
		}
		//endregion

		//region Fields
		$this->prepareFieldInfos();
		//endregion

		//region Config
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
							array('name' => 'COMMENTS'),
							array('name' => 'UTM'),
						),
						$userFieldConfigElements
					)
			)
		);
		//endregion

		//region Validators
		$this->prepareValidators();
		//endregion

		//region TABS
		$this->arResult['TABS'] = array();
		if($this->entityID > 0)
		{
			$this->arResult['TABS'][] = array(
				'id' => 'tab_deal',
				'name' => Loc::getMessage('CRM_CONTACT_TAB_DEAL'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.deal.list/lazyload.ajax.php?&site'.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '',
						'params' => array(
							'DEAL_COUNT' => '20',
							'PATH_TO_DEAL_SHOW' => $this->arResult['PATH_TO_DEAL_SHOW'],
							'PATH_TO_DEAL_EDIT' => $this->arResult['PATH_TO_DEAL_EDIT'],
							'INTERNAL_FILTER' => array('ASSOCIATED_CONTACT_ID' => $this->entityID),
							'INTERNAL_CONTEXT' => array('CONTACT_ID' => $this->entityID),
							'GRID_ID_SUFFIX' => 'CONTACT_DETAILS',
							'TAB_ID' => 'tab_deal',
							'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
							'ENABLE_TOOLBAR' => true,
							'PRESERVE_HISTORY' => true,
							'ADD_EVENT_NAME' => 'CrmCreateDealFromContact'
						)
					)
				)
			);
			$this->arResult['TABS'][] = array(
				'id' => 'tab_quote',
				'name' => Loc::getMessage('CRM_CONTACT_TAB_QUOTE'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.quote.list/lazyload.ajax.php?&site'.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '',
						'params' => array(
							'QUOTE_COUNT' => '20',
							'PATH_TO_QUOTE_SHOW' => $this->arResult['PATH_TO_QUOTE_SHOW'],
							'PATH_TO_QUOTE_EDIT' => $this->arResult['PATH_TO_QUOTE_EDIT'],
							'INTERNAL_FILTER' => array('ASSOCIATED_CONTACT_ID' => $this->entityID),
							'INTERNAL_CONTEXT' => array('CONTACT_ID' => $this->entityID),
							'GRID_ID_SUFFIX' => 'CONTACT_DETAILS',
							'TAB_ID' => 'tab_quote',
							'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
							'ENABLE_TOOLBAR' => true,
							'PRESERVE_HISTORY' => true,
							'ADD_EVENT_NAME' => 'CrmCreateQuoteFromContact'
						)
					)
				)
			);
			$this->arResult['TABS'][] = array(
				'id' => 'tab_invoice',
				'name' => Loc::getMessage('CRM_CONTACT_TAB_INVOICES'),
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
							'INTERNAL_FILTER' => array('UF_CONTACT_ID' => $this->entityID),
							'SUM_PAID_CURRENCY' => \CCrmCurrency::GetBaseCurrencyID(),
							'GRID_ID_SUFFIX' => 'CONTACT_DETAILS',
							'TAB_ID' => 'tab_invoice',
							'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
							'ENABLE_TOOLBAR' => 'Y',
							'PRESERVE_HISTORY' => true,
							'ADD_EVENT_NAME' => 'CrmCreateInvoiceFromContact'
						)
					)
				)
			);
			if (CModule::IncludeModule('sale') && Main\Config\Option::get("crm", "crm_shop_enabled") === "Y")
			{
				$this->arResult['TABS'][] = array(
					'id' => 'tab_order',
					'name' => Loc::getMessage('CRM_CONTACT_TAB_ORDERS'),
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
								'INTERNAL_FILTER' => array('ASSOCIATED_CONTACT_ID' => $this->entityID),
								'SUM_PAID_CURRENCY' => \CCrmCurrency::GetBaseCurrencyID(),
								'GRID_ID_SUFFIX' => 'CONTACT_DETAILS',
								'TAB_ID' => 'tab_order',
								'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
								'ENABLE_TOOLBAR' => 'Y',
								'PRESERVE_HISTORY' => true,
								'ADD_EVENT_NAME' => 'CrmCreateOrderFromContact'
							)
						)
					)
				);
			}
			if (CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled())
			{
				$this->arResult['TABS'][] = array(
					'id' => 'tab_bizproc',
					'name' => Loc::getMessage('CRM_CONTACT_TAB_BIZPROC'),
					'loader' => array(
						'serviceUrl' => '/bitrix/components/bitrix/bizproc.document/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
						'componentData' => array(
							'template' => 'frame',
							'params' => array(
								'MODULE_ID' => 'crm',
								'ENTITY' => 'CCrmDocumentContact',
								'DOCUMENT_TYPE' => 'CONTACT',
								'DOCUMENT_ID' => 'CONTACT_'.$this->entityID
							)
						)
					)
				);
				$this->arResult['BIZPROC_STARTER_DATA'] = array(
					'templates' => CBPDocument::getTemplatesForStart(
						$this->userID,
						array('crm', 'CCrmDocumentContact', 'CONTACT'),
						array('crm', 'CCrmDocumentContact', 'CONTACT_'.$this->entityID),
						[
							'DocumentStates' => []
						]
					),
					'moduleId' => 'crm',
					'entity' => 'CCrmDocumentContact',
					'documentType' => 'CONTACT',
					'documentId' => 'CONTACT_'.$this->entityID
				);
			}
			$this->arResult['TABS'][] = array(
				'id' => 'tab_tree',
				'name' => Loc::getMessage('CRM_CONTACT_TAB_TREE'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.entity.tree/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '.default',
						'params' => array(
							'ENTITY_ID' => $this->entityID,
							'ENTITY_TYPE_NAME' => CCrmOwnerType::ContactName,
						)
					)
				)
			);
			$this->arResult['TABS'][] = array(
				'id' => 'tab_event',
				'name' => Loc::getMessage('CRM_CONTACT_TAB_EVENT'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.event.view/lazyload.ajax.php?&site'.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '',
						'contextId' => "CONTACT_{$this->entityID}_EVENT",
						'params' => array(
							'AJAX_OPTION_ADDITIONAL' => "CONTACT_{$this->entityID}_EVENT",
							'ENTITY_TYPE' => CCrmOwnerType::ContactName,
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
				'name' => Loc::getMessage('CRM_CONTACT_TAB_PORTRAIT'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.client.portrait/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '.default',
						'params' => array(
							'ELEMENT_ID' => $this->entityID,
							'ELEMENT_TYPE' => CCrmOwnerType::Contact,
							'IS_FRAME' => 'Y'
						)
					)
				)
			);
			if (CModule::IncludeModule('lists'))
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
			$this->arResult['TABS'][] = array(
				'id' => 'tab_deal',
				'name' => Loc::getMessage('CRM_CONTACT_TAB_DEAL'),
				'enabled' => false
			);
			$this->arResult['TABS'][] = array(
				'id' => 'tab_quote',
				'name' => Loc::getMessage('CRM_CONTACT_TAB_QUOTE'),
				'enabled' => false
			);
			$this->arResult['TABS'][] = array(
				'id' => 'tab_invoice',
				'name' => Loc::getMessage('CRM_CONTACT_TAB_INVOICES'),
				'enabled' => false
			);
			if (CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled())
			{
				$this->arResult['TABS'][] = array(
					'id' => 'tab_bizproc',
					'name' => Loc::getMessage('CRM_CONTACT_TAB_BIZPROC'),
					'enabled' => false
				);
			}
			$this->arResult['TABS'][] = array(
				'id' => 'tab_event',
				'name' => Loc::getMessage('CRM_CONTACT_TAB_EVENT'),
				'enabled' => false
			);
			$this->arResult['TABS'][] = array(
				'id' => 'tab_portrait',
				'name' => Loc::getMessage('CRM_CONTACT_TAB_PORTRAIT'),
				'enabled' => false
			);
			if (CModule::IncludeModule('lists'))
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
	public function getDefaultConfigID()
	{
		return 'contact_details';
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

		$this->arResult['ENTITY_VALIDATORS'] = array(
			array(
				'type' => 'person',
				'message' => Loc::getMessage('CRM_CONTACT_PERSON_VALIDATOR_MESSAGE'),
				'data' => array(
					'nameField' => 'NAME',
					'lastNameField' => 'LAST_NAME'
				)
			)
		);
		return $this->arResult['ENTITY_VALIDATORS'];
	}
	public function prepareFieldInfos()
	{
		if(isset($this->arResult['ENTITY_FIELDS']))
		{
			return $this->arResult['ENTITY_FIELDS'];
		}

		$this->arResult['ENTITY_FIELDS'] = array(
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
				'visibilityPolicy' => 'edit',
				'data' => array(
					'items'=> \CCrmInstantEditorHelper::PrepareListOptions(
						CCrmStatus::GetStatusList('HONORIFIC'),
						array('NOT_SELECTED' => Loc::getMessage('CRM_CONTACT_HONORIFIC_NOT_SELECTED'))
					)
				)
			),
			array(
				'name' => 'LAST_NAME',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_LAST_NAME'),
				'type' => 'text',
				'isHeading' => true,
				'visibilityPolicy' => 'edit',
				'requiredConditionally' => true,
				'editable' => true,
				'data' => array('duplicateControl' => array('groupId' => 'fullName', 'field' => array('id' => 'LAST_NAME')))
			),
			array(
				'name' => 'NAME',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_NAME'),
				'type' => 'text',
				'visibilityPolicy' => 'edit',
				'requiredConditionally' => true,
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
				'data' =>  array('enableTime' => false)
			),
			array(
				'name' => 'POST',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_POST'),
				'type' => 'text',
				'editable' => true
			),
			array(
				'name' => 'COMMENTS',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_COMMENTS'),
				'type' => 'html',
				'editable' => true
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
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_EXPORT'),
				'type' => 'boolean',
				'editable' => true
			),
			array(
				'name' => 'TYPE_ID',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_TYPE_ID'),
				'type' => 'list',
				'editable' => true,
				'data' => array('items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmStatus::GetStatusList('CONTACT_TYPE')))
			),
			array(
				'name' => 'SOURCE_ID',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_SOURCE_ID'),
				'type' => 'list',
				'editable' => true,
				'data' => array('items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmStatus::GetStatusList('SOURCE')))
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
					)
				)
			),
			array(
				'name' => 'REQUISITES',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_REQUISITES'),
				'type' => 'requisite_list',
				'editable' => true,
				'data' => array(
					'presets'=> \CCrmInstantEditorHelper::PrepareListOptions(
						\Bitrix\Crm\EntityPreset::getActiveItemList()
					)
				)
			)
		);

		\Bitrix\Crm\Tracking\UI\Details::appendEntityFields($this->arResult['ENTITY_FIELDS']);
		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'UTM',
			'title' => Loc::getMessage('CRM_CONTACT_FIELD_UTM'),
			'type' => 'custom',
			'data' => array('view' => 'UTM_VIEW_HTML'),
			'editable' => false,
			'enableAttributes' => false
		);

		if($this->enableOutmodedFields)
		{
			$this->arResult['ENTITY_FIELDS'][] = array(
				'name' => 'ADDRESS',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_ADDRESS'),
				'type' => 'address',
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

			$this->arResult['ENTITY_FIELDS'][] = array(
				'name' => $typeName,
				'title' => $typeInfo['NAME'],
				'type' => 'multifield',
				'editable' => true,
				'data' => $data
			);
		}
		$this->arResult['ENTITY_FIELDS'] = array_merge(
			$this->arResult['ENTITY_FIELDS'],
			array_values($this->userFieldInfos)
		);

		return $this->arResult['ENTITY_FIELDS'];
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

		$this->userFieldInfos = array();
		$userFields = $this->prepareEntityUserFields();
		$enumerationFields = array();
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
						'/bitrix/components/bitrix/crm.contact.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#',
						array(
							'owner_id' => $this->entityID,
							'field_name' => $fieldName
						)
					)
				);
			}

			$this->userFieldInfos[$fieldName] = array(
				'name' => $fieldName,
				'title' => isset($userField['EDIT_FORM_LABEL']) ? $userField['EDIT_FORM_LABEL'] : $fieldName,
				'type' => 'userField',
				'data' => array('fieldInfo' => $fieldInfo)
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
	public function prepareEntityData()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		if($this->entityData)
		{
			return $this->entityData;
		}

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
			$this->entityData = array();
			$this->entityData['OPENED'] = \Bitrix\Crm\Settings\ContactSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
			$this->entityData['EXPORT'] = 'Y';

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
				$this->entityData = array();
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
				$by = 'ID',
				$order = 'ASC',
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
		//region Company Data & Multifield Data
		$companyData = array();
		$multiFieldData = array();
		if($this->entityID > 0)
		{
			$multiFieldDbResult = \CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array(
					'ENTITY_ID' => CCrmOwnerType::ContactName,
					'ELEMENT_ID' => $this->entityID
				)
			);

			$entityKey = CCrmOwnerType::Contact.'_'.$this->entityID;
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
								'ENTITY_TYPE_NAME' => CCrmOwnerType::ContactName,
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
			$this->entityData['REQUISITES'] = \CCrmEntitySelectorHelper::PrepareRequisiteData(
				CCrmOwnerType::Contact,
				$this->entityID
			);
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
					'REQUIRE_REQUISITE_DATA' => false,
					'REQUIRE_MULTIFIELDS' => true,
					'NORMALIZE_MULTIFIELDS' => true,
					'REQUIRE_BINDINGS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
				)
			);
		}
		$this->entityData['CLIENT_INFO'] = array('COMPANY_DATA' => $companyData);

		if($this->enableSearchHistory)
		{
			$this->entityData['LAST_COMPANY_INFOS'] = Crm\Controller\Action\Entity\SearchAction::prepareSearchResultsJson(
				Crm\Controller\Entity::getRecentlyUsedItems(
					'crm.contact.details',
					'company',
					array('EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Company)
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

		$this->entityData['ADDRESS_HTML'] = ContactAddressFormatter::format(
			$this->entityData,
			array(
				'SEPARATOR' => AddressSeparator::HtmlLineBreak,
				'NL2BR' => true,
				'HTML_ENCODE' => true
			)
		);

		\Bitrix\Crm\Tracking\UI\Details::prepareEntityData(
			\CCrmOwnerType::Contact,
			$this->entityID,
			$this->entityData
		);

		return ($this->arResult['ENTITY_DATA'] = $this->entityData);
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
}