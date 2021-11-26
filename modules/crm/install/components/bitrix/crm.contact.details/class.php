<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Attribute\FieldAttributeType;
use Bitrix\Crm\Attribute\FieldAttributePhaseGroupType;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\Tracking;

if(!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

Loc::loadMessages(__FILE__);

class CCrmContactDetailsComponent extends CBitrixComponent
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

		$this->enableOutmodedFields = false;//\Bitrix\Crm\Settings\ContactSettings::getCurrent()->areOutmodedRequisitesEnabled();
		$this->isLocationModuleIncluded = Main\Loader::includeModule('location');

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

		$this->arResult['SHOW_EMPTY_FIELDS'] = isset($this->arParams['SHOW_EMPTY_FIELDS'])
			&& $this->arParams['SHOW_EMPTY_FIELDS'];

		$this->defaultFieldValues = array();
		$this->tryGetFieldValueFromRequest('title', $this->defaultFieldValues);
		$this->tryGetFieldValueFromRequest('phone', $this->defaultFieldValues);
		//endregion

		$this->enableSearchHistory = !isset($this->arParams['~ENABLE_SEARCH_HISTORY'])
			|| mb_strtoupper($this->arParams['~ENABLE_SEARCH_HISTORY']) === 'Y';

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
		$this->initializeData();

		//region GUID
		$this->arResult['GUID'] = $this->arParams['GUID'] ?? "contact_{$this->entityID}_details";
		$this->guid = $this->arResult['GUID'];

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
		$this->arResult['ENTITY_FIELDS'] = $this->prepareFieldInfos();
		$this->arResult['ENTITY_ATTRIBUTE_SCOPE'] = FieldAttributeManager::resolveEntityScope(
			CCrmOwnerType::Contact,
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

		$relationManager = Crm\Service\Container::getInstance()->getRelationManager();
		$this->arResult['TABS'] = array_merge(
			$this->arResult['TABS'],
			$relationManager->getRelationTabsForDynamicChildren(
				\CCrmOwnerType::Contact,
				$this->entityID,
				($this->entityID === 0)
			)
		);

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
			if (
				CModule::IncludeModule('sale')
				&& Main\Config\Option::get("crm", "crm_shop_enabled") === "Y"
				&& CCrmSaleHelper::isWithOrdersMode()
			)
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

		$this->arResult['USER_FIELD_FILE_URL_TEMPLATE'] = $this->getFileUrlTemplate();

		$this->includeComponentTemplate();
	}
	public function getDefaultConfigID()
	{
		return 'contact_details';
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

		$this->entityData = null;
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

		$fakeValue = '';
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
				),
				'enableAttributes' => false
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
					'compound' => array(
						array(
							'name' => 'COMPANY_IDS',
							'type' => 'multiple_company',
							'entityTypeName' => \CCrmOwnerType::CompanyName,
							'tagName' => \CCrmOwnerType::CompanyName
						)
					),
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
					'clientEditorFieldsParams' => $this->prepareClientEditorFieldsParams()
				)
			),
			array(
				'name' => 'REQUISITES',
				'title' => Loc::getMessage('CRM_CONTACT_FIELD_REQUISITES'),
				'type' => 'requisite',
				'editable' => true,
				'data' => \CCrmComponentHelper::getFieldInfoData(CCrmOwnerType::Contact,'requisite'),
				'enableAttributes' => false
			)
		);

		Tracking\UI\Details::appendEntityFields($this->entityFieldInfos);
		$this->entityFieldInfos[] = array(
			'name' => 'UTM',
			'title' => Loc::getMessage('CRM_CONTACT_FIELD_UTM'),
			'type' => 'custom',
			'data' => array('view' => 'UTM_VIEW_HTML'),
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
				'data' => \CCrmComponentHelper::getFieldInfoData(CCrmOwnerType::Contact,'requisite_address')
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
			$this->userFields = $this->userType->GetEntityFields($this->entityID);
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

		//region Update entity data
		// This block allows in the component crm.entity.editor to determine the presence of mandatory
		if (!empty($requiredByAttributesFieldNames))
		{
			$entityFieldInfoMap = [];
			for($i = 0, $length = count($this->entityFieldInfos); $i < $length; $i++)
			{
				$entityFieldInfoMap[$this->entityFieldInfos[$i]['name']] = $i;
			}

			$isEntityDataModified = false;
			foreach ($requiredByAttributesFieldNames as $fieldName)
			{
				if ($this->isEntityFieldHasEmpyValue($this->entityFieldInfos[$entityFieldInfoMap[$fieldName]]))
				{
					$this->entityData['EMPTY_REQUIRED_SYSTEM_FIELD_MAP'][$fieldName] = true;
					$isEntityDataModified = true;
				}
			}

			if ($isEntityDataModified)
			{
				$this->arResult['ENTITY_DATA'] = $this->entityData;
			}
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
					Crm\Attribute\FieldOrigin::SYSTEM
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

		return ($this->arResult['ENTITY_DATA'] = $this->entityData);
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

	protected function getFileUrlTemplate(): string
	{
		return '/bitrix/components/bitrix/crm.contact.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#';
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

	public function initializeData()
	{
		$this->prepareEntityData();
		$this->prepareFieldInfos();
		$this->prepareEntityFieldAttributes();
	}

	public function getEntityEditorData(): array
	{
		return [
			'ENTITY_ID' => $this->getEntityID(),
			'ENTITY_DATA' => $this->prepareEntityData(),
			'ENTITY_INFO' => $this->prepareEntityInfo()
		];
	}
}
