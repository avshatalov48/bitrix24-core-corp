<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Recurring;

if(!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

Loc::loadMessages(__FILE__);

class CCrmDealDetailsComponent extends CBitrixComponent
{
	/** @var string */
	protected $guid = '';
	/** @var int */
	private $userID = 0;
	/** @var  CCrmPerms|null */
	private $userPermissions = null;
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

	public function __construct($component = null)
	{
		/** @global \CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

		parent::__construct($component);

		$this->userID = CCrmSecurityHelper::GetCurrentUserID();
		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$this->userType = new \CCrmUserType($USER_FIELD_MANAGER, \CCrmDeal::GetUserFieldEntityID());
		$this->userFieldDispatcher = \Bitrix\Main\UserField\Dispatcher::instance();

		$this->isTaxMode = \CCrmTax::isTaxMode();
	}
	public function initializeParams(array $params)
	{
		foreach($params as $k => $v)
		{
			if($k === 'INITIAL_DATA' && is_array($v))
			{
				$this->arResult['INITIAL_DATA'] = $this->arParams['INITIAL_DATA'] = $v;
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
			elseif($k === 'LEAD_ID' || $k === 'QUOTE_ID')
			{
				$this->arResult[$k] = $this->arParams[$k] = (int)$v;
			}
		}
	}
	public function executeComponent()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		//region Params
		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID']) ? (int)$this->arParams['~ENTITY_ID'] : 0;
		$extras = isset($this->arParams['~EXTRAS']) && is_array($this->arParams['~EXTRAS'])
			? $this->arParams['~EXTRAS'] : array();

		$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] =
			CrmCheckPath('PATH_TO_USER_PROFILE', $this->arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams['NAME_TEMPLATE']);

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

		$ufEntityID = \CCrmDeal::GetUserFieldEntityID();
		$enableUfCreation = \CCrmAuthorizationHelper::CheckConfigurationUpdatePermission();
		$this->arResult['ENABLE_USER_FIELD_CREATION'] = $enableUfCreation;
		$this->arResult['USER_FIELD_ENTITY_ID'] = $ufEntityID;
		$this->arResult['USER_FIELD_CREATE_PAGE_URL'] = CCrmOwnerType::GetUserFieldEditUrl($ufEntityID, 0);
		$this->arResult['USER_FIELD_CREATE_SIGNATURE'] = $enableUfCreation
			? $this->userFieldDispatcher->getCreateSignature(array('ENTITY_ID' => $ufEntityID))
			: '';
		$this->arResult['ENABLE_TASK'] = IsModuleInstalled('tasks');
		$this->arResult['ACTION_URI'] = $this->arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;

		$this->arResult['PRODUCT_DATA_FIELD_NAME'] = 'DEAL_PRODUCT_DATA';
		$this->arResult['PRODUCT_EDITOR_ID'] = 'deal_product_editor';

		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::DealName.'_'.$this->arResult['ENTITY_ID'];
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
		$this->isEnableRecurring = \Bitrix\Crm\Recurring\Manager::isAllowedExpose(\Bitrix\Crm\Recurring\Manager::DEAL);

		$this->arResult['ORIGIN_ID'] = $this->request->get('origin_id');
		if($this->arResult['ORIGIN_ID'] === null)
		{
			$this->arResult['ORIGIN_ID'] = '';
		}

		$this->enableSearchHistory = !isset($this->arParams['~ENABLE_SEARCH_HISTORY'])
			|| strtoupper($this->arParams['~ENABLE_SEARCH_HISTORY']) === 'Y';

		$this->arResult['INITIAL_DATA'] = isset($this->arParams['~INITIAL_DATA']) && is_array($this->arParams['~INITIAL_DATA'])
			? $this->arParams['~INITIAL_DATA'] : array();

		$this->defaultFieldValues = array();
		//endregion

		$this->setEntityID($this->arResult['ENTITY_ID']);

		//region Is Editing or Copying?
		if($this->entityID > 0)
		{
			if(!\CCrmDeal::Exists($this->entityID))
			{
				ShowError(GetMessage('CRM_DEAL_NOT_FOUND'));
				return;
			}

			if($this->request->get('copy') !== null)
			{
				$this->isCopyMode = true;
				$this->arResult['CONTEXT_PARAMS']['DEAL_ID'] = $this->entityID;
			}
			elseif ($this->request->get('expose') !== null)
			{
				$this->isExposeMode = true;
				$this->arResult['CONTEXT_PARAMS']['DEAL_ID'] = $this->entityID;
			}
			else
			{
				$this->isEditMode = true;
			}
		}
		$this->arResult['IS_EDIT_MODE'] = $this->isEditMode;
		$this->arResult['IS_COPY_MODE'] = $this->isCopyMode;
		//endregion

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

		$this->arResult['ENTITY_ATTRIBUTE_SCOPE'] = Crm\Attribute\FieldAttributeManager::resolveEntityScope(
			CCrmOwnerType::Deal,
			$this->entityID,
			array('CATEGORY_ID' => $this->categoryID)
		);

		//region Conversion & Conversion Scheme
		$this->arResult['PERMISSION_ENTITY_TYPE'] = DealCategory::convertToPermissionEntityType($this->categoryID);
		CCrmDeal::PrepareConversionPermissionFlags($this->entityID, $this->arResult, $this->userPermissions);
		if($this->arResult['CAN_CONVERT'])
		{
			$config = \Bitrix\Crm\Conversion\DealConversionConfig::load();
			if($config === null)
			{
				$config = \Bitrix\Crm\Conversion\DealConversionConfig::getDefault();
			}

			$this->arResult['CONVERSION_CONFIG'] = $config;
		}

		if(isset($this->arResult['LEAD_ID']) && $this->arResult['LEAD_ID'] > 0)
		{
			$this->leadID = $this->arResult['LEAD_ID'];
		}
		elseif(isset($this->request['lead_id']) && $this->request['lead_id'] > 0)
		{
			$this->leadID = $this->arResult['LEAD_ID'] = (int)$this->request['lead_id'];
		}

		if($this->leadID > 0)
		{
			$this->conversionWizard = \Bitrix\Crm\Conversion\LeadConversionWizard::load($this->leadID);
		}

		if(isset($this->arResult['QUOTE_ID']) && $this->arResult['QUOTE_ID'] > 0)
		{
			$this->quoteID = $this->arResult['QUOTE_ID'];
		}
		elseif(isset($this->request['conv_quote_id']) && $this->request['conv_quote_id'] > 0)
		{
			$this->quoteID = $this->arResult['QUOTE_ID'] = (int)$this->request['conv_quote_id'];
		}

		if($this->quoteID > 0)
		{
			$this->conversionWizard = \Bitrix\Crm\Conversion\QuoteConversionWizard::load($this->quoteID);
		}

		if($this->conversionWizard !== null)
		{
			$conversionContextParams = $this->conversionWizard->prepareEditorContextParams(\CCrmOwnerType::Deal);
			$this->arResult['CONTEXT_PARAMS'] = array_merge(
				$this->arResult['CONTEXT_PARAMS'],
				$conversionContextParams
			);
			if(isset($conversionContextParams['CATEGORY_ID']))
			{
				$this->arResult['CATEGORY_ID'] = $this->categoryID = $conversionContextParams['CATEGORY_ID'];
			}
		}
		//endregion

		if(!isset($this->arResult['CONTEXT_PARAMS']['CATEGORY_ID']))
		{
			$this->arResult['CONTEXT_PARAMS']['CATEGORY_ID'] = $this->categoryID;
		}

		//region Permissions check
		if($this->isCopyMode)
		{
			if(!(\CCrmDeal::CheckReadPermission($this->entityID, $this->userPermissions, $this->categoryID)
				&& \CCrmDeal::CheckCreatePermission($this->userPermissions, $this->categoryID))
			)
			{
				ShowError(GetMessage('CRM_PERMISSION_DENIED'));
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
				ShowError(GetMessage('CRM_PERMISSION_DENIED'));
				return;
			}

			$this->arResult['CATEGORY_ID'] = $this->categoryID = (int)$recurring['CATEGORY_ID'];
		}
		elseif($this->isEditMode)
		{
			if(\CCrmDeal::CheckUpdatePermission($this->entityID, $this->userPermissions, $this->categoryID))
			{
				$this->arResult['READ_ONLY'] = false;
			}
			elseif(\CCrmDeal::CheckReadPermission($this->entityID, $this->userPermissions, $this->categoryID))
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
			if(\CCrmDeal::CheckCreatePermission($this->userPermissions, $this->categoryID))
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

		$this->prepareEntityData();
		$this->prepareFieldInfos();

		$this->prepareEntityFieldAttributes();

		$this->arResult['ENTITY_FIELDS'] = $this->entityFieldInfos;
		//region GUID
		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID'])
			? $this->arParams['GUID'] : $this->getDefaultGuid();

		$this->arResult['EDITOR_CONFIG_ID'] = $this->prepareConfigID(
			isset($this->arParams['EDITOR_CONFIG_ID']) ? $this->arParams['EDITOR_CONFIG_ID'] : ''
		);
		//endregion

		//region Entity Info
		$this->arResult['ENTITY_INFO'] = array(
			'ENTITY_ID' => $this->entityID,
			'ENTITY_TYPE_ID' => $this->entityData['IS_RECURRING'] !== "Y" ? CCrmOwnerType::Deal : CCrmOwnerType::DealRecurring,
			'ENTITY_TYPE_NAME' =>  $this->entityData['IS_RECURRING'] !== "Y" ? CCrmOwnerType::DealName : CCrmOwnerType::DealRecurringName,
			'TITLE' => isset($this->entityData['TITLE']) ? $this->entityData['TITLE'] : '',
			'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Deal, $this->entityID, false),
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

		//region CONFIG
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
					array('name' => 'OPPORTUNITY_WITH_CURRENCY'),
					array('name' => 'STAGE_ID'),
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
					array('name' => 'PRODUCT_ROW_SUMMARY')
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

		//region TABS
		$this->arResult['TABS'] = array();

		$currencyID = CCrmCurrency::GetBaseCurrencyID();
		if(isset($this->entityData['CURRENCY_ID']) && $this->entityData['CURRENCY_ID'] !== '')
		{
			$currencyID = $this->entityData['CURRENCY_ID'];
		}

		// Determine person type
		$companyID = isset($this->entityData['COMPANY_ID']) ? (int)$this->entityData['COMPANY_ID'] : 0;
		$personTypes = CCrmPaySystem::getPersonTypeIDs();
		$personTypeID = 0;
		if (isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
		{
			$personTypeID = $companyID > 0 ? $personTypes['COMPANY'] : $personTypes['CONTACT'];
		}

		ob_start();
		$APPLICATION->IncludeComponent('bitrix:crm.product_row.list',
			'',
			array(
				'ID' => $this->arResult['PRODUCT_EDITOR_ID'],
				'PREFIX' => $this->arResult['PRODUCT_EDITOR_ID'],
				'FORM_ID' => '',
				'OWNER_ID' => $this->entityID,
				'OWNER_TYPE' => 'D',
				'PERMISSION_TYPE' => $this->arResult['READ_ONLY'] ? 'READ' : 'WRITE',
				'PERMISSION_ENTITY_TYPE' => $this->arResult['PERMISSION_ENTITY_TYPE'],
				'PERSON_TYPE_ID' => $personTypeID,
				'CURRENCY_ID' => $currencyID,
				'LOCATION_ID' => $this->isTaxMode && isset($this->entityData['LOCATION_ID']) ? $this->entityData['LOCATION_ID'] : '',
				'CLIENT_SELECTOR_ID' => '', //TODO: Add Client Selector
				'PRODUCT_ROWS' =>  isset($this->entityData['PRODUCT_ROWS']) ? $this->entityData['PRODUCT_ROWS'] : null,
				'HIDE_MODE_BUTTON' => !$this->isEditMode ? 'Y' : 'N',
				'TOTAL_SUM' => isset($this->entityData['OPPORTUNITY']) ? $this->entityData['OPPORTUNITY'] : null,
				'TOTAL_TAX' => isset($this->entityData['TAX_VALUE']) ? $this->entityData['TAX_VALUE'] : null,
				'PRODUCT_DATA_FIELD_NAME' => $this->arResult['PRODUCT_DATA_FIELD_NAME'],
				'PATH_TO_PRODUCT_EDIT' => $this->arResult['PATH_TO_PRODUCT_EDIT'],
				'PATH_TO_PRODUCT_SHOW' => $this->arResult['PATH_TO_PRODUCT_SHOW'],
				'INIT_LAYOUT' => 'N',
				'INIT_EDITABLE' => $this->arResult['READ_ONLY'] ? 'N' : 'Y',
				'ENABLE_MODE_CHANGE' => 'N',
				'ENABLE_SUBMIT_WITHOUT_LAYOUT' => ($this->isCopyMode || $this->conversionWizard !== null) ? 'Y' : 'N'
			),
			false,
			array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
		);
		$html = ob_get_contents();
		ob_end_clean();

		$this->arResult['TABS'][] = array(
			'id' => 'tab_products',
			'name' => Loc::getMessage('CRM_DEAL_TAB_PRODUCTS'),
			'html' => $html
		);

		if ($this->entityData['IS_RECURRING'] !== "Y")
		{
			if($this->entityID > 0)
			{
				$quoteID = isset($this->entityData['QUOTE_ID']) ? (int)$this->entityData['QUOTE_ID'] : 0;
				if($quoteID > 0)
				{
					$quoteDbResult = \CCrmQuote::GetList(
						array(),
						array('=ID' => $quoteID, 'CHECK_PERMISSIONS' => 'N'),
						false,
						false,
						array('TITLE')
					);
					$quoteFields = is_object($quoteDbResult) ? $quoteDbResult->Fetch() : null;
					if (is_array($quoteFields))
					{
						$this->arResult['TABS'][] = array(
							'id' => 'tab_quote',
							'name' => GetMessage('CRM_DEAL_TAB_QUOTE'),
							'html' => '<div class="crm-conv-info">'
								.Loc::getMessage(
									'CRM_DEAL_QUOTE_LINK',
									array(
										'#TITLE#' => htmlspecialcharsbx($quoteFields['TITLE']),
										'#URL#' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Quote, $quoteID, false)
									)
								)
								.'</div>'
						);
					}
				}
				else
				{
					$this->arResult['TABS'][] = array(
						'id' => 'tab_quote',
						'name' => Loc::getMessage('CRM_DEAL_TAB_QUOTE'),
						'loader' => array(
							'serviceUrl' => '/bitrix/components/bitrix/crm.quote.list/lazyload.ajax.php?&site'.SITE_ID.'&'.bitrix_sessid_get(),
							'componentData' => array(
								'template' => '',
								'params' => array(
									'QUOTE_COUNT' => '20',
									'PATH_TO_QUOTE_SHOW' => $this->arResult['PATH_TO_QUOTE_SHOW'],
									'PATH_TO_QUOTE_EDIT' => $this->arResult['PATH_TO_QUOTE_EDIT'],
									'INTERNAL_FILTER' => array('DEAL_ID' => $this->entityID),
									'INTERNAL_CONTEXT' => array('DEAL_ID' => $this->entityID),
									'GRID_ID_SUFFIX' => 'DEAL_DETAILS',
									'TAB_ID' => 'tab_quote',
									'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
									'ENABLE_TOOLBAR' => true,
									'PRESERVE_HISTORY' => true,
									'ADD_EVENT_NAME' => 'CrmCreateQuoteFromDeal'
								)
							)
						)
					);
				}
				$this->arResult['TABS'][] = array(
					'id' => 'tab_invoice',
					'name' => Loc::getMessage('CRM_DEAL_TAB_INVOICES'),
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
								'INTERNAL_FILTER' => array('UF_DEAL_ID' => $this->entityID),
								'SUM_PAID_CURRENCY' => $currencyID,
								'GRID_ID_SUFFIX' => 'DEAL_DETAILS',
								'TAB_ID' => 'tab_invoice',
								'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
								'ENABLE_TOOLBAR' => 'Y',
								'PRESERVE_HISTORY' => true,
								'ADD_EVENT_NAME' => 'CrmCreateInvoiceFromDeal'
							)
						)
					)
				);
				if (\Bitrix\Crm\Automation\Factory::isAutomationAvailable(CCrmOwnerType::Deal))
				{
					Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/crm.automation/templates/.default/style.css');
					Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/bizproc.automation/templates/.default/style.css');
					$this->arResult['TABS'][] = array(
						'id' => 'tab_automation',
						'name' => Loc::getMessage('CRM_DEAL_TAB_AUTOMATION'),
						'loader' => array(
							'serviceUrl' => '/bitrix/components/bitrix/crm.automation/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
							'componentData' => array(
								'template' => '',
								'params' => array(
									'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
									'ENTITY_ID' => $this->entityID,
									'ENTITY_CATEGORY_ID' => $this->categoryID,
									'back_url' => \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Deal, $this->entityID)
								)
							)
						)
					);
				}
				if (CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled())
				{
					$this->arResult['TABS'][] = array(
						'id' => 'tab_bizproc',
						'name' => Loc::getMessage('CRM_DEAL_TAB_BIZPROC'),
						'loader' => array(
							'serviceUrl' => '/bitrix/components/bitrix/bizproc.document/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
							'componentData' => array(
								'template' => 'frame',
								'params' => array(
									'MODULE_ID' => 'crm',
									'ENTITY' => 'CCrmDocumentDeal',
									'DOCUMENT_TYPE' => 'DEAL',
									'DOCUMENT_ID' => 'DEAL_'.$this->entityID
								)
							)
						)
					);
					$this->arResult['BIZPROC_STARTER_DATA'] = array(
						'templates' => CBPDocument::getTemplatesForStart(
							$this->userID,
							array('crm', 'CCrmDocumentDeal', 'DEAL'),
							array('crm', 'CCrmDocumentDeal', 'DEAL_'.$this->entityID)
						),
						'moduleId' => 'crm',
						'entity' => 'CCrmDocumentDeal',
						'documentType' => 'DEAL',
						'documentId' => 'DEAL_'.$this->entityID
					);
				}
				$this->arResult['TABS'][] = array(
					'id' => 'tab_tree',
					'name' => Loc::getMessage('CRM_DEAL_TAB_TREE'),
					'loader' => array(
						'serviceUrl' => '/bitrix/components/bitrix/crm.entity.tree/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
						'componentData' => array(
							'template' => '.default',
							'params' => array(
								'ENTITY_ID' => $this->entityID,
								'ENTITY_TYPE_NAME' => CCrmOwnerType::DealName,
							)
						)
					)
				);
				$this->arResult['TABS'][] = array(
					'id' => 'tab_event',
					'name' => Loc::getMessage('CRM_DEAL_TAB_EVENT'),
					'loader' => array(
						'serviceUrl' => '/bitrix/components/bitrix/crm.event.view/lazyload.ajax.php?&site'.SITE_ID.'&'.bitrix_sessid_get(),
						'componentData' => array(
							'template' => '',
							'contextId' => "DEAL_{$this->entityID}_EVENT",
							'params' => array(
								'AJAX_OPTION_ADDITIONAL' => "DEAL_{$this->entityID}_EVENT",
								'ENTITY_TYPE' => 'DEAL',
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
					'name' => Loc::getMessage('CRM_DEAL_TAB_QUOTE'),
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
				$this->arResult['TABS'][] = array(
					'id' => 'tab_event',
					'name' => Loc::getMessage('CRM_DEAL_TAB_EVENT'),
					'enabled' => false
				);
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
		$this->arResult['WAIT_TARGET_DATES'] = array(
			array('name' => 'BEGINDATE', 'caption' => \CAllCrmDeal::GetFieldCaption('BEGINDATE')),
			array('name' => 'CLOSEDATE', 'caption' => \CAllCrmDeal::GetFieldCaption('CLOSEDATE')),
		);

		$userFields = $this->userType->GetFields();
		foreach($userFields as $userField)
		{
			if($userField['USER_TYPE_ID'] === 'date')
			{
				$this->arResult['WAIT_TARGET_DATES'][] = array(
					'name' => $userField['FIELD_NAME'],
					'caption' => isset($userField['EDIT_FORM_LABEL'])
						? $userField['EDIT_FORM_LABEL'] : $userField['FIELD_NAME']
				);
			}
		}
		//endregion

		//region LEGEND
		if($this->arResult['ENTITY_ID'] > 0)
		{
			$categoryID = isset($this->entityData['CATEGORY_ID']) ? (int)$this->entityData['CATEGORY_ID'] : 0;
			$this->arResult['LEGEND'] = \Bitrix\Crm\Category\DealCategory::getName($categoryID);
			if(isset($this->entityData['IS_RETURN_CUSTOMER']) && $this->entityData['IS_RETURN_CUSTOMER'] === 'Y')
			{
				$this->arResult['LEGEND'] .= ' ('.Loc::getMessage('CRM_DEAL_RETURNING').')';
			}
			elseif(isset($this->entityData['IS_REPEATED_APPROACH']) && $this->entityData['IS_REPEATED_APPROACH'] === 'Y')
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

		if (!$this->isEnableRecurring && CModule::IncludeModule('bitrix24'))
		{
			CBitrix24::initLicenseInfoPopupJS();
		}

		$this->includeComponentTemplate();
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
		if ($this->entityData['IS_RECURRING'] === 'Y')
		{
			$dbResult = Recurring\Manager::getList(
				array('filter' => array('=DEAL_ID' => $this->entityID)),
				Recurring\Manager::DEAL
			);
			$recurringData = $dbResult->fetch();
			if (strlen($recurringData['NEXT_EXECUTION']) > 0 && $recurringData['ACTIVE'] === 'Y' && $this->isEnableRecurring)
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

				if (strlen($recurringLine) > 0)
				{
					$recurringLine = substr($recurringLine, 0, -2);
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
			switch (LANGUAGE_ID)
			{
				case "ru":
				case "kz":
				case "by":
					$promoLink = 'https://www.bitrix24.ru/pro/crm.php ';
					break;
				case "de":
					$promoLink = 'https://www.bitrix24.de/pro/crm.php';
					break;
				case "ua":
					$promoLink = 'https://www.bitrix24.ua/pro/crm.php';
					break;
				default:
					$promoLink = 'https://www.bitrix24.com/pro/crm.php';
			}
		}
		else
		{
			$promoLink = "";
		}
		//endregion

		$allStages = Bitrix\Crm\Category\DealCategory::getStageList($this->categoryID);
		$prohibitedStageIDS = array();
		foreach(array_keys($allStages) as $stageID)
		{
			if($this->arResult['READ_ONLY'])
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

		$this->entityFieldInfos = array(
			array(
				'name' => 'ID',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_ID'),
				'type' => 'text',
				'editable' => false,
				'enableAttributes' => false
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
				'data' => array('items' => \CCrmInstantEditorHelper::PrepareListOptions($this->prepareTypeList()))
			),
			array(
				'name' => 'SOURCE_ID',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_SOURCE_ID'),
				'type' => 'list',
				'editable' => true,
				'data' => array('items'=> \CCrmInstantEditorHelper::PrepareListOptions(
					CCrmStatus::GetStatusList('SOURCE'),
					array(
						'NOT_SELECTED' => Loc::getMessage('CRM_DEAL_SOURCE_NOT_SELECTED'),
						'NOT_SELECTED_VALUE' => ''
					)
				)
				)
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
				'editable' => ($this->entityData['IS_RECURRING'] !== "Y"),
				'enableAttributes' => false,
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
				'type' => 'money',
				'editable' => true,
				'data' => array(
					'affectedFields' => array('CURRENCY_ID', 'OPPORTUNITY'),
					'currency' => array(
						'name' => 'CURRENCY_ID',
						'items' => \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems())
					),
					'amount' => 'OPPORTUNITY',
					'formatted' => 'FORMATTED_OPPORTUNITY',
					'formattedWithCurrency' => 'FORMATTED_OPPORTUNITY_WITH_CURRENCY'
				)
			),
			array(
				'name' => 'CLOSEDATE',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_CLOSEDATE'),
				'type' => 'datetime',
				'editable' => true,
				'data' =>  array('enableTime' => false)
			),
			array(
				'name' => 'BEGINDATE',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_BEGINDATE'),
				'type' => 'datetime',
				'editable' => true,
				'data' => array('enableTime' => false)
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
			array(
				"name" => "COMMENTS",
				"title" => Loc::getMessage("CRM_DEAL_FIELD_COMMENTS"),
				"type" => "html",
				"editable" => true
			),
			array(
				'name' => 'CLIENT',
				'title' => Loc::getMessage('CRM_DEAL_FIELD_CLIENT'),
				'type' => 'client_light',
				'editable' => true,
				'data' => array(
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
					)
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
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE']

				)
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
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE'],
					'messages' => array('addObserver' => Loc::getMessage('CRM_DEAL_FIELD_ADD_OBSERVER'))
				)
			),
			array(
				"name" => "PRODUCT_ROW_SUMMARY",
				"title" => Loc::getMessage("CRM_DEAL_FIELD_PRODUCTS"),
				"type" => "product_row_summary",
				"editable" => false,
				'enableAttributes' => false,
				"transferable" => false
			),
			array(
				"name" => "RECURRING",
				"title" => Loc::getMessage("CRM_DEAL_SECTION_RECURRING"),
				"type" => "recurring",
				"editable" => is_array($this->arResult['CREATE_CATEGORY_LIST']) && count($this->arResult['CREATE_CATEGORY_LIST']) > 0,
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
					"restrictMessage" => array(
						"title" => !$this->isEnableRecurring ? Loc::getMessage("CRM_RECURRING_DEAL_B24_BLOCK_TITLE") : "",
						"text" => !$this->isEnableRecurring ? Loc::getMessage("CRM_RECURRING_DEAL_B24_BLOCK_TEXT", array("#LINK#" => $promoLink)) : "",
					)
				)
			),
		);

		Crm\Tracking\UI\Details::appendEntityFields($this->entityFieldInfos);
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

		return $this->entityFieldInfos;
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
			if(!Crm\Attribute\FieldAttributeManager::isEnabled())
			{
				$this->entityFieldAttributeConfig = array();
			}
			else
			{
				$this->entityFieldAttributeConfig = Crm\Attribute\FieldAttributeManager::getEntityConfigurations(
					CCrmOwnerType::Deal,
					Crm\Attribute\FieldAttributeManager::resolveEntityScope(
						CCrmOwnerType::Deal,
						$this->entityID,
						array('CATEGORY_ID' => $this->categoryID)
					)
				);
			}
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
		foreach($userFields as $userField)
		{
			$fieldName = $userField['FIELD_NAME'];
			$fieldInfo = array(
				'USER_TYPE_ID' => $userField['USER_TYPE_ID'],
				'ENTITY_ID' => \CCrmDeal::GetUserFieldEntityID(),
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
						'/bitrix/components/bitrix/crm.deal.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#',
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

		$this->prepareEntityUserFields();
		$this->prepareEntityUserFieldInfos();

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
			$this->entityData['OPENED'] = \Bitrix\Crm\Settings\DealSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
			//$this->entityData['CLOSED'] = 'N';

			//region Default Responsible
			if($this->userID > 0)
			{
				$this->entityData['ASSIGNED_BY_ID'] = $this->userID;
			}
			//endregion

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

			$typeList = $this->prepareTypeList();
			if(!empty($typeList))
			{
				$this->entityData['TYPE_ID'] = current(array_keys($typeList));
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

			//Save request data as initial data for restore it if according controls are not enabled in settings (please see ajax.php)
			if(!empty($requestData))
			{
				if(!isset($this->arResult['INITIAL_DATA']))
				{
					$this->arResult['INITIAL_DATA'] = array();
				}
				$this->arResult['INITIAL_DATA'] = array_merge($this->arResult['INITIAL_DATA'], $requestData);
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
						'ONCITYCHANGE' => 'BX.onCustomEvent(\'CrmProductRowSetLocation\', [\'LOC_CITY\']);',
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
		//region Observers
		if(isset($this->entityData['OBSERVER_IDS']) && !empty($this->entityData['OBSERVER_IDS']))
		{
			$this->entityData['OBSERVER_INFOS'] = array();

			$userDbResult = \CUser::GetList(
				($by = 'ID'),
				($order = 'ASC'),
				array('ID' => implode('||', $this->entityData['OBSERVER_IDS'])),
				array('FIELDS' => array('ID', 'PERSONAL_PHOTO', 'WORK_POSITION', 'NAME', 'SECOND_NAME', 'LAST_NAME'))
			);

			$observerMap = array();
			while($userData = $userDbResult->Fetch())
			{
				$userInfo = array(
					'ID' => intval($userData['ID']),
					'FORMATTED_NAME' => \CUser::FormatName(
						$this->arResult['NAME_TEMPLATE'],
						$userData,
						false,
						false
					),
					'WORK_POSITION' => isset($userData['WORK_POSITION']) ? $userData['WORK_POSITION'] : '',
					'SHOW_URL' => CComponentEngine::MakePathFromTemplate(
						$this->arResult['PATH_TO_USER_PROFILE'],
						array('user_id' => $userData['ID'])
					)
				);

				$userPhotoID = isset($userData['PERSONAL_PHOTO']) ? (int)$userData['PERSONAL_PHOTO'] : 0;
				if($userPhotoID > 0)
				{
					$file = new \CFile();
					$fileInfo = $file->ResizeImageGet(
						$userPhotoID,
						array('width' => 60, 'height'=> 60),
						BX_RESIZE_IMAGE_EXACT
					);
					if(is_array($fileInfo) && isset($fileInfo['src']))
					{
						$userInfo['PHOTO_URL'] = $fileInfo['src'];
					}
				}

				$observerMap[$userData['ID']] = $userInfo;
			}
			foreach($this->entityData['OBSERVER_IDS'] as $userID)
			{
				if(isset($observerMap[$userID]))
				{
					$this->entityData['OBSERVER_INFOS'][] = $observerMap[$userID];
				}
			}
		}
		//endregion

		//region Client Data & Multifield Data
		$clientInfo = array();
		$multiFieldData = array();

		$companyID = isset($this->entityData['COMPANY_ID']) ? (int)$this->entityData['COMPANY_ID'] : 0;
		if($companyID > 0)
		{
			$this->prepareMultifieldData(\CCrmOwnerType::Company, $companyID, 'PHONE', $multiFieldData);
			$this->prepareMultifieldData(\CCrmOwnerType::Company, $companyID, 'EMAIL', $multiFieldData);
			$this->prepareMultifieldData(\CCrmOwnerType::Company, $companyID, 'IM', $multiFieldData);

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

			$clientInfo['COMPANY_DATA'] = $companyInfo;
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
		foreach($contactIDs as $contactID)
		{
			$this->prepareMultifieldData(CCrmOwnerType::Contact, $contactID, 'PHONE', $multiFieldData);
			$this->prepareMultifieldData(\CCrmOwnerType::Contact, $contactID, 'EMAIL', $multiFieldData);
			$this->prepareMultifieldData(\CCrmOwnerType::Contact, $contactID, 'IM', $multiFieldData);

			$isEntityReadPermitted = CCrmContact::CheckReadPermission($contactID, $this->userPermissions);
			$clientInfo['CONTACT_DATA'][] = CCrmEntitySelectorHelper::PrepareEntityInfo(
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
		$this->entityData['CLIENT_INFO'] = $clientInfo;

		if($this->enableSearchHistory)
		{
			$this->entityData['LAST_COMPANY_INFOS'] = Crm\Controller\Action\Entity\SearchAction::prepareSearchResultsJson(
				Crm\Controller\Entity::getRecentlyUsedItems(
					'crm.deal.details',
					'company',
					array('EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Company)
				)
			);
			$this->entityData['LAST_CONTACT_INFOS'] = Crm\Controller\Action\Entity\SearchAction::prepareSearchResultsJson(
				Crm\Controller\Entity::getRecentlyUsedItems(
					'crm.deal.details',
					'contact',
					array('EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Contact)
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

		$this->entityData['MULTIFIELD_DATA'] = $multiFieldData;
		//endregion

		//region Product row
		$productRowCount = 0;
		$productRowTotalSum = 0.0;
		$productRowInfos = array();
		if($this->entityID > 0)
		{
			$dbResult = \CAllCrmProductRow::GetList(
				array('SORT' => 'ASC', 'ID'=>'ASC'),
				array(
					'OWNER_ID' => $this->entityID, 'OWNER_TYPE' => 'D'
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
				'RECURRING[BEGINDATE_TYPE]' => Recurring\Entity\Deal::SETTLED_FIELD_VALUE,
				'RECURRING[OFFSET_BEGINDATE_VALUE]' => 0,
				'RECURRING[OFFSET_BEGINDATE_TYPE]' => Recurring\Calculator::SALE_TYPE_DAY_OFFSET,
				'RECURRING[CLOSEDATE_TYPE]' => Recurring\Entity\Deal::SETTLED_FIELD_VALUE,
				'RECURRING[OFFSET_CLOSEDATE_VALUE]' => 0,
				'RECURRING[OFFSET_CLOSEDATE_TYPE]' => Recurring\Calculator::SALE_TYPE_DAY_OFFSET,
				'RECURRING[MULTIPLE_DATE_START]' => $today,
				'RECURRING[MULTIPLE_DATE_LIMIT]' => $today,
				'RECURRING[MULTIPLE_TIMES_LIMIT]' => 1,
				'RECURRING[CATEGORY_ID]' => $this->arResult['CATEGORY_ID']
			];
			$this->entityData['RECURRING'] = $recurringParams;
			$this->entityData = array_merge($this->entityData, $recurringParams);
		}
		//endregion

		Crm\Tracking\UI\Details::prepareEntityData(
			\CCrmOwnerType::Deal,
			$this->entityID,
			$this->entityData
		);

		return ($this->arResult['ENTITY_DATA'] = $this->entityData);
	}
	protected function prepareMultifieldData($entityTypeID, $entityID, $typeID, array &$data)
	{
		$dbResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName($entityTypeID),
				'ELEMENT_ID' => $entityID,
				'TYPE_ID' => $typeID
			)
		);

		$entityKey = "{$entityTypeID}_{$entityID}";
		while($fields = $dbResult->Fetch())
		{
			$value = isset($fields['VALUE']) ? $fields['VALUE'] : '';
			$valueType = $fields['VALUE_TYPE'];
			$multiFieldComplexID = $fields['COMPLEX_ID'];

			if($value === '')
			{
				continue;
			}

			//Is required for phone & email & messenger menu
			if($typeID === 'PHONE' || $typeID === 'EMAIL'
				|| ($typeID === 'IM' && preg_match('/^imol\|/', $value) === 1)
			)
			{
				if(!isset($data[$typeID]))
				{
					$data[$typeID] = array();
				}

				if(!isset($data[$typeID][$entityKey]))
				{
					$data[$typeID][$entityKey] = array();
				}

				$formattedValue = $typeID === 'PHONE'
					? Main\PhoneNumber\Parser::getInstance()->parse($value)->format()
					: $value;

				$data[$typeID][$entityKey][] = array(
					'ID' => $fields['ID'],
					'VALUE' => $value,
					'VALUE_TYPE' => $valueType,
					'VALUE_FORMATTED' => $formattedValue,
					'COMPLEX_ID' => $multiFieldComplexID,
					'COMPLEX_NAME' => \CCrmFieldMulti::GetEntityNameByComplex($multiFieldComplexID, false)
				);
			}
		}
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

	protected function prepareEntityFieldAttributes()
	{
		if($this->entityFieldInfos === null)
		{
			return;
		}

		$attrConfigs = $this->prepareEntityFieldAttributeConfigs();
		for($i = 0, $length = count($this->entityFieldInfos); $i < $length; $i++)
		{
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
		}
	}

	protected function prepareRecurringElements()
	{
		if (!$this->isEnableRecurring || $this->arResult['READ_ONLY'] === true)
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
				"editable" => is_array($this->arResult['CREATE_CATEGORY_LIST']) && count($this->arResult['CREATE_CATEGORY_LIST']) > 0,
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
				"editable" => is_array($this->arResult['CREATE_CATEGORY_LIST']) && count($this->arResult['CREATE_CATEGORY_LIST']) > 0,
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
				"editable" => is_array($this->arResult['CREATE_CATEGORY_LIST']) && count($this->arResult['CREATE_CATEGORY_LIST']) > 0,
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
									'VALUE' => Recurring\Entity\Deal::SETTLED_FIELD_VALUE,
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
						'SETTLED_FIELD_VALUE' => Recurring\Entity\Deal::SETTLED_FIELD_VALUE,
						'CALCULATED_FIELD_VALUE' => Recurring\Entity\Deal::CALCULATED_FIELD_VALUE,
						'MULTIPLE_EXECUTION' => Recurring\Manager::MULTIPLY_EXECUTION,
						'SINGLE_EXECUTION' => Recurring\Manager::SINGLE_EXECUTION,
					]
				)
			],
			[
				"name" => "NEW_CLOSEDATE",
				"type" => "recurring",
				"editable" => is_array($this->arResult['CREATE_CATEGORY_LIST']) && count($this->arResult['CREATE_CATEGORY_LIST']) > 0,
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
									'VALUE' => Recurring\Entity\Deal::SETTLED_FIELD_VALUE,
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
						'SETTLED_FIELD_VALUE' => Recurring\Entity\Deal::SETTLED_FIELD_VALUE,
						'CALCULATED_FIELD_VALUE' => Recurring\Entity\Deal::CALCULATED_FIELD_VALUE,
						'MULTIPLE_EXECUTION' => Recurring\Manager::MULTIPLY_EXECUTION,
						'SINGLE_EXECUTION' => Recurring\Manager::SINGLE_EXECUTION,
					]
				)
			]
		];

		if (is_array($this->arResult['CREATE_CATEGORY_LIST']) && count($this->arResult['CREATE_CATEGORY_LIST']) > 0)
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