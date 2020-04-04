<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Binding\QuoteContactTable;
use Bitrix\Crm\Component\ComponentError;
use Bitrix\Crm\Component\EntityDetails\ComponentMode;
use Bitrix\Crm\Security\EntityPermissionType;

if(!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

Loc::loadMessages(__FILE__);

class CCrmQuoteDetailsComponent extends Crm\Component\EntityDetails\BaseComponent
{
	/** @var array|null */
	private $statuses = null;
	/** @var array|null */
	private $types = null;
	/** @var \Bitrix\Crm\Conversion\EntityConversionWizard|null  */
	private $conversionWizard = null;
	/** @var int */
	private $dealID = 0;

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Quote;
	}
	protected function getUserFieldEntityID()
	{
		return \CCrmQuote::GetUserFieldEntityID();
	}
	protected function getFileHandlerUrl()
	{
		return '/bitrix/components/bitrix/crm.quote.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#';
	}
	protected function checkIfEntityExists()
	{
		return $this->entityID > 0 && \CCrmQuote::Exists($this->entityID);
	}
	protected function getErrorMessage($error)
	{
		if($error === ComponentError::ENTITY_NOT_FOUND)
		{
			return Loc::getMessage('CRM_QUOTE_NOT_FOUND');
		}
		return ComponentError::getMessage($error);
	}
	public function initializeParams(array $params)
	{
		foreach($params as $k => $v)
		{
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
			elseif($k === 'DEAL_ID')
			{
				$this->arResult[$k] = $this->arParams[$k] = (int)$v;
			}
		}
	}
	protected function getEntityFieldsInfo()
	{
		return \CCrmQuote::GetFieldsInfo();
	}
	public function executeComponent()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		//region Params
		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID']) ? (int)$this->arParams['~ENTITY_ID'] : 0;

		$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] =
			CrmCheckPath('PATH_TO_USER_PROFILE', $this->arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams['NAME_TEMPLATE']);

		$this->arResult['PATH_TO_QUOTE_SHOW'] = CrmCheckPath(
			'PATH_TO_QUOTE_SHOW',
			$this->arParams['PATH_TO_QUOTE_SHOW'],
			$APPLICATION->GetCurPage().'?quote_id=#quote_id#&show'
		);
		$this->arResult['PATH_TO_QUOTE_EDIT'] = CrmCheckPath(
			'PATH_TO_QUOTE_EDIT',
			$this->arParams['PATH_TO_QUOTE_EDIT'],
			$APPLICATION->GetCurPage().'?quote_id=#quote_id#&edit'
		);

		$this->arResult['PATH_TO_DEAL_SHOW'] = CrmCheckPath(
			'PATH_TO_DEAL_SHOW',
			$this->arParams['PATH_TO_DEAL_SHOW'],
			$APPLICATION->GetCurPage().'?deal_id=#deal_id#&show'
		);
		$this->arResult['PATH_TO_DEAL_EDIT'] = CrmCheckPath(
			'PATH_TO_DEAL_EDIT',
			$this->arParams['PATH_TO_DEAL_EDIT'],
			$APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit'
		);

		$this->arResult['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath(
			'PATH_TO_PRODUCT_EDIT',
			$this->arParams['PATH_TO_PRODUCT_EDIT'],
			$APPLICATION->GetCurPage().'?product_id=#product_id#&edit'
		);
		$this->arResult['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath(
			'PATH_TO_PRODUCT_SHOW',
			$this->arParams['PATH_TO_PRODUCT_SHOW'],
			$APPLICATION->GetCurPage().'?product_id=#product_id#&show'
		);

		$ufEntityID = $this->getUserFieldEntityID();
		$enableUfCreation = \CCrmAuthorizationHelper::CheckConfigurationUpdatePermission();

		$this->arResult['ENABLE_USER_FIELD_CREATION'] = $enableUfCreation;
		$this->arResult['USER_FIELD_ENTITY_ID'] = $ufEntityID;
		$this->arResult['USER_FIELD_CREATE_PAGE_URL'] = CCrmOwnerType::GetUserFieldEditUrl($ufEntityID, 0);
		$this->arResult['USER_FIELD_CREATE_SIGNATURE'] = $enableUfCreation
			? $this->userFieldDispatcher->getCreateSignature(array('ENTITY_ID' => $ufEntityID))
			: '';
		$this->arResult['ACTION_URI'] = $this->arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;

		$this->arResult['PRODUCT_DATA_FIELD_NAME'] = 'QUOTE_PRODUCT_DATA';
		$this->arResult['PRODUCT_EDITOR_ID'] = 'quote_product_editor';

		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::QuoteName.'_'.$this->arResult['ENTITY_ID'];
		$this->arResult['CONTEXT_PARAMS'] = array(
			'PATH_TO_PRODUCT_SHOW' => $this->arResult['PATH_TO_PRODUCT_SHOW'],
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
		//endregion

		$this->setEntityID($this->arResult['ENTITY_ID']);

		if(!$this->tryToDetectMode())
		{
			$this->showErrors();
			return;
		}

		//region Conversion & Conversion Scheme
		CCrmQuote::PrepareConversionPermissionFlags($this->entityID, $this->arResult, $this->userPermissions);
		if($this->arResult['CAN_CONVERT'])
		{
			$config = \Bitrix\Crm\Conversion\QuoteConversionConfig::load();
			if($config === null)
			{
				$config = \Bitrix\Crm\Conversion\QuoteConversionConfig::getDefault();
			}

			$this->arResult['CONVERSION_CONFIG'] = $config;
		}

		if(isset($this->arResult['DEAL_ID']) && $this->arResult['DEAL_ID'] > 0)
		{
			$this->dealID = $this->arResult['DEAL_ID'];
		}
		elseif(isset($this->request['conv_deal_id']) && $this->request['conv_deal_id'] > 0)
		{
			$this->dealID = $this->arResult['DEAL_ID'] = (int)$this->request['conv_deal_id'];
		}

		if($this->dealID > 0)
		{
			$this->conversionWizard = \Bitrix\Crm\Conversion\DealConversionWizard::load($this->dealID);
			if($this->conversionWizard !== null)
			{
				$this->arResult['CONTEXT_PARAMS'] = array_merge(
					$this->arResult['CONTEXT_PARAMS'],
					$this->conversionWizard->prepareEditorContextParams(\CCrmOwnerType::Quote)
				);
			}
		}
		//endregion

		$this->prepareEntityUserFields();
		$this->prepareEntityUserFieldInfos();
		$this->prepareEntityData();

		//region GUID
		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID'])
			? $this->arParams['GUID'] : "quote_{$this->entityID}_details";

		$this->arResult['EDITOR_CONFIG_ID'] = isset($this->arParams['EDITOR_CONFIG_ID'])
			? $this->arParams['EDITOR_CONFIG_ID'] : 'quote_details';
		//endregion

		$progressSemantics = $this->entityData['STATUS_ID']
			? \CCrmQuote::GetStatusSemantics($this->entityData['STATUS_ID']) : '';
		$this->arResult['PROGRESS_SEMANTICS'] = $progressSemantics;
		$this->arResult['ENABLE_PROGRESS_CHANGE'] = $this->mode !== ComponentMode::VIEW;

		//region Entity Info
		$this->arResult['ENTITY_INFO'] = array(
			'ENTITY_ID' => $this->entityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::Quote,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::QuoteName,
			'TITLE' => isset($this->entityData['TITLE']) ? $this->entityData['TITLE'] : '',
			'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Quote, $this->entityID, false),
		);
		//endregion

		//region Page title
		if($this->mode === ComponentMode::CREATION)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_QUOTE_CREATION_PAGE_TITLE'));
		}
		elseif($this->mode === ComponentMode::COPING)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_QUOTE_COPY_PAGE_TITLE'));
		}
		elseif(isset($this->entityData['TITLE']))
		{
			$APPLICATION->SetTitle(htmlspecialcharsbx($this->entityData['TITLE']));
		}
		//endregion

		//region Fields
		$this->prepareFieldInfos();
		//endregion

		//region Config
		$userFieldConfigElements = array();
		foreach(array_keys($this->userFieldInfos) as $fieldName)
		{
			$userFieldConfigElements[] = array('name' => $fieldName);
		}
		$this->arResult['ENTITY_CONFIG'] = array(
			array(
				'name' => 'main',
				'title' => Loc::getMessage('CRM_QUOTE_SECTION_MAIN'),
				'type' => 'section',
				'elements' => array(
					array('name' => 'QUOTE_NUMBER'),
					array('name' => 'TITLE'),
					array('name' => 'STATUS_ID'),
					array('name' => 'OPPORTUNITY_WITH_CURRENCY'),
					array('name' => 'CLIENT'),
					array('name' => 'MYCOMPANY_ID'),
					array('name' => 'FILES')
				)
			),
			array(
				'name' => 'additional',
				'title' => Loc::getMessage('CRM_QUOTE_SECTION_ADDITIONAL'),
				'type' => 'section',
				'elements' =>
					array_merge(
						array(
							array('name' => 'LEAD_ID'),
							array('name' => 'DEAL_ID'),
							array('name' => 'BEGINDATE'),
							array('name' => 'CLOSEDATE'),
							array('name' => 'OPENED'),
							array('name' => 'ASSIGNED_BY_ID'),
							array('name' => 'CONTENT'),
							array('name' => 'TERMS'),
							array('name' => 'COMMENTS'),
							array('name' => 'UTM'),
						),
						$userFieldConfigElements
					)
			),
			array(
				'name' => 'products',
				'title' => Loc::getMessage('CRM_QUOTE_SECTION_PRODUCTS'),
				'type' => 'section',
				'elements' => array(
					array('name' => 'PRODUCT_ROW_SUMMARY')
				)
			)
		);
		//endregion

		//region CONTROLLERS
		$this->arResult['ENTITY_CONTROLLERS'] = array(
			array(
				"name" => "PRODUCT_ROW_PROXY",
				"type" => "product_row_proxy",
				"config" => array("editorId" => $this->arResult['PRODUCT_EDITOR_ID'])
			),
		);
		//endregion

		//region Tabs
		$this->arResult['TABS'] = array();

		$currencyID = CCrmCurrency::GetBaseCurrencyID();
		if(isset($this->entityData['CURRENCY_ID']) && $this->entityData['CURRENCY_ID'] !== '')
		{
			$currencyID = $this->entityData['CURRENCY_ID'];
		}

		$bTaxMode = \CCrmTax::isTaxMode();
		$personTypeID = CCrmQuote::ResolvePersonType($this->entityData, CCrmPaySystem::getPersonTypeIDs());

		ob_start();
		$APPLICATION->IncludeComponent('bitrix:crm.product_row.list',
			'',
			array(
				'ID' => $this->arResult['PRODUCT_EDITOR_ID'],
				'PREFIX' => $this->arResult['PRODUCT_EDITOR_ID'],
				'FORM_ID' => '',
				'OWNER_ID' => $this->entityID,
				'OWNER_TYPE' => \CCrmQuote::OWNER_TYPE,
				'PERMISSION_TYPE' => $this->mode === ComponentMode::VIEW ? 'READ' : 'WRITE',
				'PERMISSION_ENTITY_TYPE' => $this->arResult['PERMISSION_ENTITY_TYPE'],
				'PERSON_TYPE_ID' => $personTypeID,
				'CURRENCY_ID' => $currencyID,
				'LOCATION_ID' => $bTaxMode && isset($this->entityData['LOCATION_ID']) ? $this->entityData['LOCATION_ID'] : '',
				//'CLIENT_SELECTOR_ID' => '',
				'PRODUCT_ROWS' =>  isset($this->entityData['PRODUCT_ROWS']) ? $this->entityData['PRODUCT_ROWS'] : null,
				'HIDE_MODE_BUTTON' => !$this->isEditMode ? 'Y' : 'N',
				'TOTAL_SUM' => isset($this->entityData['OPPORTUNITY']) ? $this->entityData['OPPORTUNITY'] : null,
				'TOTAL_TAX' => isset($this->entityData['TAX_VALUE']) ? $this->entityData['TAX_VALUE'] : null,
				'PRODUCT_DATA_FIELD_NAME' => $this->arResult['PRODUCT_DATA_FIELD_NAME'],
				'PATH_TO_PRODUCT_EDIT' => $this->arResult['PATH_TO_PRODUCT_EDIT'],
				'PATH_TO_PRODUCT_SHOW' => $this->arResult['PATH_TO_PRODUCT_SHOW'],
				'INIT_LAYOUT' => 'N',
				'INIT_EDITABLE' => $this->mode === ComponentMode::VIEW ? 'N' : 'Y',
				'ENABLE_MODE_CHANGE' => 'N'
			),
			false,
			array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
		);
		$html = ob_get_contents();
		ob_end_clean();

		$this->arResult['TABS'][] = array(
			'id' => 'tab_products',
			'name' => Loc::getMessage('CRM_QUOTE_TAB_PRODUCTS'),
			'html' => $html
		);

		if($this->entityID > 0)
		{
			//TODO: ADD DEALS and INVOICES
			$this->arResult['TABS'][] = array(
				'id' => 'tab_tree',
				'name' => Loc::getMessage('CRM_QUOTE_TAB_TREE'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.entity.tree/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '.default',
						'params' => array(
							'ENTITY_ID' => $this->entityID,
							'ENTITY_TYPE_NAME' => CCrmOwnerType::QuoteName,
						)
					)
				)
			);
			$this->arResult['TABS'][] = array(
				'id' => 'tab_event',
				'name' => Loc::getMessage('CRM_QUOTE_TAB_EVENT'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.event.view/lazyload.ajax.php?&site'.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '',
						'contextId' => "QUOTE_{$this->entityID}_EVENT",
						'params' => array(
							'AJAX_OPTION_ADDITIONAL' => "QUOTE_{$this->entityID}_EVENT",
							'ENTITY_TYPE' => 'QUOTE',
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
		else
		{
			$this->arResult['TABS'][] = array(
				'id' => 'tab_event',
				'name' => Loc::getMessage('CRM_QUOTE_TAB_EVENT'),
				'enabled' => false
			);
		}
		//endregion

		//region VIEW EVENT
		if($this->entityID > 0 && \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isViewEventEnabled())
		{
			CCrmEvent::RegisterViewEvent(CCrmOwnerType::Quote, $this->entityID, $this->userID);
		}
		//endregion

		$this->includeComponentTemplate();
	}
	protected function prepareFieldInfos()
	{
		if(isset($this->arResult['ENTITY_FIELDS']))
		{
			return $this->arResult['ENTITY_FIELDS'];
		}

		//region Disabled Statuses
		$disabledStatusIDs = array();
		$allStatuses = CCrmStatus::GetStatusList('QUOTE_STATUS');

		$statusSelectorPermissionType = ($this->mode === ComponentMode::CREATION ||
			$this->mode === ComponentMode::COPING) ? EntityPermissionType::CREATE : EntityPermissionType::UPDATE;

		foreach(array_keys($allStatuses) as $statusID)
		{
			if($this->mode === ComponentMode::VIEW)
			{
				$disabledStatusIDs[] = $statusID;
			}
			else
			{
				if(!\CCrmQuote::CheckStatusPermission($statusID, $statusSelectorPermissionType, $this->userPermissions))
				{
					$disabledStatusIDs[] = $statusID;
				}
			}
		}
		//endregion

		//region Client primary entity
		$companyID = isset($this->entityData['COMPANY_ID']) ? (int)$this->entityData['COMPANY_ID'] : 0;
		if(isset($this->entityData['CONTACT_BINDINGS']))
		{
			$contactBindings = $this->entityData['CONTACT_BINDINGS'];
		}
		elseif($this->entityID > 0)
		{
			$contactBindings = QuoteContactTable::getQuoteBindings($this->entityID);
		}
		elseif(isset($this->entityData['CONTACT_ID']))
		{
			//For backward compatibility
			$contactBindings = EntityBinding::prepareEntityBindings(
				CCrmOwnerType::Contact,
				array($this->entityData['CONTACT_ID'])
			);
		}
		else
		{
			$contactBindings = array();
		}
		//endregion

		$primaryEntityTypeName = ($companyID > 0 || empty($contactBindings))
			? CCrmOwnerType::CompanyName : CCrmOwnerType::ContactName;

		$this->arResult['ENTITY_FIELDS'] = array(
			array(
				'name' => 'ID',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_ID'),
				'type' => 'text',
				'editable' => false
			),
			array(
				'name' => 'QUOTE_NUMBER',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_NUMBER'),
				'type' => 'text',
				'editable' => true
			),
			array(
				'name' => 'TITLE',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_TITLE'),
				'type' => 'text',
				'isHeading' => true,
				'visibilityPolicy' => 'edit',
				'editable' => true
			),
			array(
				'name' => 'STATUS_ID',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_STATUS_ID'),
				'type' => 'list',
				'editable' => true,
				'data' => array(
					'items'=> \CCrmInstantEditorHelper::PrepareListOptions(
						$allStatuses,
						array('EXCLUDE_FROM_EDIT' => $disabledStatusIDs)
					)
				)
			),
			array(
				'name' => 'OPPORTUNITY_WITH_CURRENCY',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_OPPORTUNITY_WITH_CURRENCY'),
				'type' => 'money',
				'editable' => true,
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
				'name' => 'ASSIGNED_BY_ID',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_ASSIGNED_BY_ID'),
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
				'name' => 'BEGINDATE',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_BEGINDATE'),
				'type' => 'datetime',
				'editable' => true,
				'data' => array('enableTime' => false)
			),
			array(
				'name' => 'CLOSEDATE',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_CLOSEDATE'),
				'type' => 'datetime',
				'editable' => true,
				'data' =>  array('enableTime' => false)
			),
			array(
				'name' => 'LEAD_ID',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_LEAD_ID'),
				'type' => 'crm_entity',
				'editable' => true,
				'data' =>  array('typeId' => \CCrmOwnerType::Lead)
			),
			array(
				'name' => 'DEAL_ID',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_DEAL_ID'),
				'type' => 'crm_entity',
				'editable' => true,
				'data' =>  array('typeId' => \CCrmOwnerType::Deal)
			),
			array(
				'name' => 'OPENED',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_OPENED'),
				'type' => 'boolean',
				'editable' => true
			),
			array(
				'name' => 'CONTENT',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_CONTENT'),
				'type' => 'html',
				'editable' => true
			),
			array(
				'name' => 'TERMS',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_TERMS'),
				'type' => 'html',
				'editable' => true
			),
			array(
				'name' => 'COMMENTS',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_COMMENTS'),
				'type' => 'html',
				'editable' => true
			),
			array(
				'name' => 'LEAD_ID',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_LEAD'),
				'type' => 'crm_entity',
				'editable' => true,
				'data' => array(
					'entityTypeName' => \CCrmOwnerType::LeadName,
					'info' => 'LEAD_INFO',
					'loader' => array(
						\CCrmOwnerType::LeadName => array(
							'action' => 'GET_ENTITY_INFO',
							'url' => '/bitrix/components/bitrix/crm.lead.show/ajax.php?'.bitrix_sessid_get()
						)
					)
				)
			),
			array(
				'name' => 'DEAL_ID',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_DEAL'),
				'type' => 'crm_entity',
				'editable' => true,
				'data' => array(
					'entityTypeName' => \CCrmOwnerType::DealName,
					'info' => 'DEAL_INFO',
					'loader' => array(
						\CCrmOwnerType::DealName => array(
							'action' => 'GET_ENTITY_INFO',
							'url' => '/bitrix/components/bitrix/crm.deal.show/ajax.php?'.bitrix_sessid_get()
						)
					)
				)
			),
			array(
				'name' => 'CLIENT',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_CLIENT'),
				'type' => 'client',
				'editable' => true,
				'data' => array(
					'map' => array(
						'primaryEntityType' => 'CLIENT_PRIMARY_ENTITY_TYPE',
						'primaryEntityId' => 'CLIENT_PRIMARY_ENTITY_ID',
						'secondaryEntityType' => 'CLIENT_SECONDARY_ENTITY_TYPE',
						'secondaryEntityIds' => 'CLIENT_SECONDARY_ENTITY_IDS',
						'unboundSecondaryEntityIds' => 'CLIENT_UBOUND_SECONDARY_ENTITY_IDS',
						'boundSecondaryEntityIds' => 'CLIENT_BOUND_SECONDARY_ENTITY_IDS'
					),
					'info' => 'CLIENT_INFO',
					'primaryEntityTypeName' => $primaryEntityTypeName,
					'secondaryEntityTypeName' => CCrmOwnerType::ContactName,
					'secondaryEntityLegend' => Loc::getMessage('CRM_QUOTE_FIELD_CONTACT_LEGEND'),
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
								'url' => '/bitrix/components/bitrix/crm.quote.edit/ajax.php?'.bitrix_sessid_get()
							)
						)
					)
				)
			),
			array(
				'name' => 'MYCOMPANY_ID',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_MYCOMPANY'),
				'type' => 'crm_entity',
				'editable' => true,
				'data' => array(
					'entityTypeName' => \CCrmOwnerType::CompanyName,
					'enableMyCompanyOnly' => true,
					'info' => 'MYCOMPANY_INFO',
					'loader' => array(
						\CCrmOwnerType::CompanyName => array(
							'action' => 'GET_CLIENT_INFO',
							'url' => '/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get()
						)
					)
				)
			),
			array(
				'name' => 'FILES',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_FILES'),
				'type' => 'file_storage',
				'editable' => true,
				'data' => array(
					'diskFileInfos' => 'DISK_FILES',
					'storageElementIds' => 'STORAGE_ELEMENT_IDS',
					'storageTypeId' => 'STORAGE_TYPE_ID'
				)
			),
			array(
				'name' => 'PRODUCT_ROW_SUMMARY',
				'title' => Loc::getMessage('CRM_QUOTE_FIELD_PRODUCTS'),
				'type' => 'product_row_summary',
				'editable' => false,
				'transferable' => false
			)
		);

		Crm\Tracking\UI\Details::appendEntityFields($this->arResult['ENTITY_FIELDS']);
		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'UTM',
			'title' => Loc::getMessage('CRM_QUOTE_FIELD_UTM'),
			'type' => 'custom',
			'data' => array('view' => 'UTM_VIEW_HTML'),
			'editable' => false
		);

		$this->arResult['ENTITY_FIELDS'] = array_merge(
			$this->arResult['ENTITY_FIELDS'],
			array_values($this->userFieldInfos)
		);

		return $this->arResult['ENTITY_FIELDS'];
	}
	protected function getStatusList($entityPermissionTypeID)
	{
		$statuses = array();
		$allStatuses = CCrmStatus::GetStatusList('QUOTE_STATUS');
		foreach ($allStatuses as $ID => $title)
		{
			if(\CCrmQuote::CheckStatusPermission($ID, $entityPermissionTypeID, $this->userPermissions))
			{
				$statuses[$ID] = $title;
			}
		}
		return $statuses;
	}
	public function prepareEntityData()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		if($this->entityData)
		{
			return $this->entityData;
		}

		if($this->conversionWizard !== null)
		{
			$this->entityData = array();
			$mappedUserFields = array();
			\Bitrix\Crm\Entity\EntityEditor::prepareConvesionMap(
				$this->conversionWizard,
				CCrmOwnerType::Quote,
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

			if(!isset($this->entityData['CURRENCY_ID']) || $this->entityData['CURRENCY_ID'] === '')
			{
				$this->entityData['CURRENCY_ID'] = \CCrmCurrency::GetBaseCurrencyID();
			}

			$this->entityData['OPENED'] = \Bitrix\Crm\Settings\QuoteSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
		}
		elseif($this->mode === ComponentMode::CREATION)
		{
			$this->entityData = array();
			//region Default Dates
			$beginDate = time() + \CTimeZone::GetOffset();
			$time = localtime($beginDate, true);
			$beginDate -= $time['tm_sec'] + 60 * $time['tm_min'] + 3600 * $time['tm_hour'];

			$this->entityData['BEGINDATE'] = ConvertTimeStamp($beginDate, 'SHORT', SITE_ID);
			$this->entityData['CLOSEDATE'] = ConvertTimeStamp($beginDate + 7 * 86400, 'SHORT', SITE_ID);
			//endregion
			//leave OPPORTUNITY unassigned
			//$this->entityData['OPPORTUNITY'] = 0.0;
			$this->entityData['CURRENCY_ID'] = \CCrmCurrency::GetBaseCurrencyID();
			$this->entityData['OPENED'] = \Bitrix\Crm\Settings\QuoteSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
			//$this->entityData['CLOSED'] = 'N';

			//region Default Responsible
			if($this->userID > 0)
			{
				$this->entityData['ASSIGNED_BY_ID'] = $this->userID;
			}
			//endregion

			//region Default Stage ID
			$statusList = $this->getStatusList(EntityPermissionType::CREATE);
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

			\Bitrix\Crm\Entity\EntityEditor::mapRequestData(
				$this->prepareEntityDataScheme(),
				$this->entityData,
				$this->userFields
			);
		}
		else
		{
			$dbResult = \CCrmQuote::GetList(
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

			//HACK: Removing time from BEGINDATE because of 'datetime' type (see CCrmQuote::GetFields)
			if(isset($this->entityData['BEGINDATE']))
			{
				$this->entityData['BEGINDATE'] = \CCrmComponentHelper::TrimZeroTime($this->entityData['BEGINDATE']);
			}

			//HACK: Removing time from CLOSEDATE because of 'datetime' type (see CCrmQuote::GetFields)
			if(isset($this->entityData['CLOSEDATE']))
			{
				$this->entityData['CLOSEDATE'] = \CCrmComponentHelper::TrimZeroTime($this->entityData['CLOSEDATE']);
			}

			if(!isset($this->entityData['CURRENCY_ID']) || $this->entityData['CURRENCY_ID'] === '')
			{
				$this->entityData['CURRENCY_ID'] = \CCrmCurrency::GetBaseCurrencyID();
			}

			//region Default Responsible and Status ID for copy mode
			if($this->mode === ComponentMode::COPING)
			{
				if($this->userID > 0)
				{
					$this->entityData['ASSIGNED_BY_ID'] = $this->userID;
				}

				$statusList = $this->getStatusList(EntityPermissionType::CREATE);
				if(!empty($statusList))
				{
					$this->entityData['STATUS_ID'] = current(array_keys($statusList));
				}
			}
			//endregion

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
			$user = self::getUser($this->entityData['ASSIGNED_BY_ID']);
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
		//region Opportunity & Currency
		$this->entityData['FORMATTED_OPPORTUNITY_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['OPPORTUNITY'],
			$this->entityData['CURRENCY_ID'],
			''
		);
		$this->entityData['FORMATTED_OPPORTUNITY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['OPPORTUNITY'],
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
				$this->arResult['PATH_TO_USER_PROFILE'],
				array('user_id' => $assignedByID)
			);
		}
		//endregion
		//region Client Data & Multifield Data
		$clientInfo = array();
		$multiFildData = array();

		$companyID = isset($this->entityData['COMPANY_ID']) ? (int)$this->entityData['COMPANY_ID'] : 0;
		if($companyID > 0)
		{
			self::prepareMultifieldData(\CCrmOwnerType::Company, $companyID, 'PHONE', $multiFildData);
			self::prepareMultifieldData(\CCrmOwnerType::Company, $companyID, 'EMAIL', $multiFildData);

			$isEntityReadPermitted = \CCrmCompany::CheckReadPermission($companyID, $this->userPermissions);
			$companyInfo = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::CompanyName,
				$companyID,
				array(
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_MULTIFIELDS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
				)
			);

			$clientInfo['PRIMARY_ENTITY_DATA'] = $companyInfo;
		}

		$contactBindings = array();
		if($this->entityID > 0)
		{
			$contactBindings = \Bitrix\Crm\Binding\QuoteContactTable::getQuoteBindings($this->entityID);
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
		$clientInfo['SECONDARY_ENTITY_DATA'] = array();
		foreach($contactIDs as $contactID)
		{
			self::prepareMultifieldData(CCrmOwnerType::Contact, $contactID, 'PHONE', $multiFildData);
			self::prepareMultifieldData(CCrmOwnerType::Contact, $contactID, 'EMAIL', $multiFildData);

			$isEntityReadPermitted = CCrmContact::CheckReadPermission($contactID, $this->userPermissions);
			$clientInfo['SECONDARY_ENTITY_DATA'][] = CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::ContactName,
				$contactID,
				array(
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_MULTIFIELDS' => true,
					'REQUIRE_BINDINGS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
				)
			);
		}
		if(!isset($clientInfo['PRIMARY_ENTITY_DATA']) && !empty($clientInfo['SECONDARY_ENTITY_DATA']))
		{
			$clientInfo['PRIMARY_ENTITY_DATA'] = array_shift($clientInfo['SECONDARY_ENTITY_DATA']);
		}
		$this->entityData['CLIENT_INFO'] = $clientInfo;

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

		$this->entityData['MULTIFIELD_DATA'] = $multiFildData;
		//endregion

		//region MyCompany Data
		$myCompanyID = isset($this->entityData['MYCOMPANY_ID']) ? (int)$this->entityData['MYCOMPANY_ID'] : 0;
		if($myCompanyID > 0)
		{
			$this->entityData['MYCOMPANY_INFO'] = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::CompanyName,
				$myCompanyID,
				array(
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !\CCrmCompany::CheckReadPermission($myCompanyID, $this->userPermissions),
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_MULTIFIELDS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
				)
			);
		}
		//endregion

		//region Lead Data
		$leadID = isset($this->entityData['LEAD_ID']) ? (int)$this->entityData['LEAD_ID'] : 0;
		if($leadID > 0)
		{
			$this->entityData['LEAD_INFO'] = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::LeadName,
				$leadID,
				array(
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !\CCrmLead::CheckReadPermission($leadID, $this->userPermissions),
					'REQUIRE_REQUISITE_DATA' => false,
					'REQUIRE_MULTIFIELDS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
				)
			);
		}
		//endregion

		//region Deal Data
		$dealID = isset($this->entityData['DEAL_ID']) ? (int)$this->entityData['DEAL_ID'] : 0;
		if($dealID > 0)
		{
			$this->entityData['DEAL_INFO'] = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::DealName,
				$dealID,
				array(
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !\CCrmDeal::CheckReadPermission($dealID, $this->userPermissions),
					'REQUIRE_REQUISITE_DATA' => false,
					'REQUIRE_MULTIFIELDS' => false,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
				)
			);
		}
		//endregion

		//region Product row & Files
		if($this->entityID > 0)
		{
			\CCrmQuote::PrepareStorageElementIDs($this->entityData);
			\CCrmQuote::PrepareStorageElementInfo($this->entityData);

			$productRowCount = 0;
			$productRowTotalSum = 0.0;
			$productRowInfos = array();
			$dbResult = \CAllCrmProductRow::GetList(
				array('SORT' => 'ASC', 'ID'=>'ASC'),
				array(
					'OWNER_ID' => $this->entityID, 'OWNER_TYPE' => \CCrmQuote::OWNER_TYPE
				),
				false,
				false,
				array(
					'PRODUCT_ID',
					'PRODUCT_NAME',
					'ORIGINAL_PRODUCT_NAME',
					'PRICE',
					'PRICE_EXCLUSIVE',
					'QUANTITY',
					'TAX_INCLUDED',
					'TAX_RATE'
				)
			);
			while($fields = $dbResult->Fetch())
			{
				$productName = isset($fields['PRODUCT_NAME']) ? $fields['PRODUCT_NAME'] : '';
				if($productName === '' && isset($fields['ORIGINAL_PRODUCT_NAME']))
				{
					$productName = $fields['ORIGINAL_PRODUCT_NAME'];
				}

				$productID = isset($fields['PRODUCT_ID']) ? (int)$fields['PRODUCT_ID'] : 0;
				$url = '';
				if($productID > 0)
				{
					$url = CComponentEngine::MakePathFromTemplate(
						$this->arResult['PATH_TO_PRODUCT_SHOW'],
						array('product_id' => $fields['PRODUCT_ID'])
					);
				}

				if($fields['TAX_INCLUDED'] === 'Y')
				{
					$sum = $fields['PRICE'] * $fields['QUANTITY'];
				}
				else
				{
					$sum = $fields['PRICE_EXCLUSIVE'] * $fields['QUANTITY'] * (1 + $fields['TAX_RATE'] / 100);
				}

				$productRowTotalSum += $sum;
				$productRowCount++;

				if($productRowCount <= 10)
				{
					$productRowInfos[] = array(
						'PRODUCT_NAME' => $productName,
						'SUM' => CCrmCurrency::MoneyToString($sum, $this->entityData['CURRENCY_ID']),
						'URL' => $url
					);
				}
			}
			$this->entityData['PRODUCT_ROW_SUMMARY'] = array(
				'count' => $productRowCount,
				'total' => CCrmCurrency::MoneyToString($productRowTotalSum, $this->entityData['CURRENCY_ID']),
				'items' => $productRowInfos
			);
		}

		Crm\Tracking\UI\Details::prepareEntityData(
			\CCrmOwnerType::Quote,
			$this->entityID,
			$this->entityData
		);

		//endregion
		return ($this->arResult['ENTITY_DATA'] = $this->entityData);
	}
}