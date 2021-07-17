<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Location\Entity\Address\AddressLinkCollection;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Attribute\FieldAttributePhaseGroupType;
use Bitrix\Crm\Attribute\FieldAttributeType;
use Bitrix\Crm\CustomerType;
use Bitrix\Crm\Conversion\LeadConversionDispatcher;
use Bitrix\Crm\Conversion\LeadConversionScheme;
use Bitrix\Crm\Tracking;
use Bitrix\Currency;
use Bitrix\Catalog;
use Bitrix\Main\Component\ParameterSigner;

if(!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

Loc::loadMessages(__FILE__);


class CCrmLeadDetailsComponent extends CBitrixComponent
{
	use Crm\Entity\Traits\VisibilityConfig;

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
	/** @var array */
	private $defaultEntityData = [];

	public function __construct($component = null)
	{
		/** @global \CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

		parent::__construct($component);

		$this->userID = CCrmSecurityHelper::GetCurrentUserID();
		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$this->userType = new \CCrmUserType($USER_FIELD_MANAGER, \CCrmLead::GetUserFieldEntityID());
		$this->userFieldDispatcher = \Bitrix\Main\UserField\Dispatcher::instance();

		$this->multiFieldInfos = CCrmFieldMulti::GetEntityTypeInfos();
		$this->multiFieldValueTypeInfos = CCrmFieldMulti::GetEntityTypes();

		$this->isTaxMode = \CCrmTax::isTaxMode();
	}
	public function executeComponent()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		$useNewProductList =
			Main\Loader::includeModule('catalog')
			&& \Bitrix\Catalog\Config\Feature::isCommonProductProcessingEnabled()
		;

		$this->isLocationModuleIncluded = Main\Loader::includeModule('location');

		//region Params
		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID']) ? (int)$this->arParams['~ENTITY_ID'] : 0;
		$extras = isset($this->arParams['~EXTRAS']) && is_array($this->arParams['~EXTRAS'])
			? $this->arParams['~EXTRAS'] : array();

		$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] =
			CrmCheckPath('PATH_TO_USER_PROFILE', $this->arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams['NAME_TEMPLATE']);

		$this->arResult['PATH_TO_LEAD_SHOW'] = CrmCheckPath(
			'PATH_TO_LEAD_SHOW',
			$this->arParams['PATH_TO_LEAD_SHOW'],
			$APPLICATION->GetCurPage().'?lead_id=#lead_id#&show'
		);
		$this->arResult['PATH_TO_LEAD_EDIT'] = CrmCheckPath(
			'PATH_TO_LEAD_EDIT',
			$this->arParams['PATH_TO_LEAD_EDIT'],
			$APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit'
		);

		$this->arResult['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath(
			'PATH_TO_PRODUCT_EDIT',
			$this->arParams['PATH_TO_PRODUCT_EDIT'],
			$APPLICATION->GetCurPage().'?product_id=#product_id#&edit'
		);

		if ($useNewProductList && \Bitrix\Catalog\Config\State::isProductCardSliderEnabled())
		{
			$catalogId = CCrmCatalog::EnsureDefaultExists();
			$this->arResult['PATH_TO_PRODUCT_SHOW'] = "/shop/catalog/{$catalogId}/product/#product_id#/";
		}
		else
		{
			$this->arResult['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath(
				'PATH_TO_PRODUCT_SHOW',
				$this->arParams['PATH_TO_PRODUCT_SHOW'],
				$APPLICATION->GetCurPage().'?product_id=#product_id#&show'
			);
		}

		$ufEntityID = \CCrmLead::GetUserFieldEntityID();
		$enableUfCreation = \CCrmAuthorizationHelper::CheckConfigurationUpdatePermission();
		$this->arResult['ENABLE_USER_FIELD_CREATION'] = $enableUfCreation;
		$this->arResult['USER_FIELD_ENTITY_ID'] = $ufEntityID;
		$this->arResult['USER_FIELD_CREATE_PAGE_URL'] = CCrmOwnerType::GetUserFieldEditUrl($ufEntityID, 0);
		$this->arResult['USER_FIELD_CREATE_SIGNATURE'] = $enableUfCreation
			? $this->userFieldDispatcher->getCreateSignature(array('ENTITY_ID' => $ufEntityID))
			: '';
		$this->arResult['ENABLE_TASK'] = IsModuleInstalled('tasks');
		$this->arResult['ACTION_URI'] = $this->arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;

		$this->arResult['PRODUCT_DATA_FIELD_NAME'] = 'LEAD_PRODUCT_DATA';
		$this->arResult['PRODUCT_EDITOR_ID'] = 'lead_product_editor';

		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::LeadName.'_'.$this->arResult['ENTITY_ID'];
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

		$this->enableSearchHistory = !isset($this->arParams['~ENABLE_SEARCH_HISTORY'])
			|| mb_strtoupper($this->arParams['~ENABLE_SEARCH_HISTORY']) === 'Y';

		$this->arResult['INITIAL_DATA'] = isset($this->arParams['~INITIAL_DATA']) && is_array($this->arParams['~INITIAL_DATA'])
			? $this->arParams['~INITIAL_DATA'] : array();

		$this->defaultFieldValues = array();
		$this->tryGetFieldValueFromRequest('phone', $this->defaultFieldValues);
		//endregion

		$this->setEntityID($this->arResult['ENTITY_ID']);

		//region Is Editing or Copying?
		if($this->entityID > 0)
		{
			if(!\CCrmLead::Exists($this->entityID))
			{
				ShowError(GetMessage('CRM_LEAD_NOT_FOUND'));
				return;
			}

			if($this->request->get('copy') !== null)
			{
				$this->isCopyMode = true;
				$this->arResult['CONTEXT_PARAMS']['LEAD_ID'] = $this->entityID;
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
			!$this->isEditMode && \Bitrix\Crm\Integrity\DuplicateControl::isControlEnabledFor(CCrmOwnerType::Lead);

		if($this->enableDupControl)
		{
			$this->arResult['DUPLICATE_CONTROL']['serviceUrl'] = '/bitrix/components/bitrix/crm.lead.edit/ajax.php?'.bitrix_sessid_get();
			$this->arResult['DUPLICATE_CONTROL']['entityTypeName'] = CCrmOwnerType::LeadName;
			$this->arResult['DUPLICATE_CONTROL']['groups'] = array(
				'fullName' => array(
					'groupType' => 'fullName',
					'groupSummaryTitle' => Loc::getMessage('CRM_LEAD_DUP_CTRL_FULL_NAME_SUMMARY_TITLE')
				),
				'email' => array(
					'groupType' => 'communication',
					'communicationType' => 'EMAIL',
					'groupSummaryTitle' => Loc::getMessage('CRM_LEAD_DUP_CTRL_EMAIL_SUMMARY_TITLE')
				),
				'phone' => array(
					'groupType' => 'communication',
					'communicationType' => 'PHONE',
					'groupSummaryTitle' => Loc::getMessage('CRM_LEAD_DUP_CTRL_PHONE_SUMMARY_TITLE')
				),
				'companyTitle' => array(
					'parameterName' => 'COMPANY_TITLE',
					'groupType' => 'single',
					'groupSummaryTitle' => Loc::getMessage('CRM_LEAD_DUP_CTRL_COMPANY_TTL_SUMMARY_TITLE')
				)
			);
		}
		//endregion

		//region Permissions check
		if($this->isCopyMode)
		{
			if(!(\CCrmLead::CheckReadPermission($this->entityID, $this->userPermissions)
				&& \CCrmLead::CheckCreatePermission($this->userPermissions))
			)
			{
				ShowError(GetMessage('CRM_PERMISSION_DENIED'));
				return;
			}
		}
		elseif($this->isEditMode)
		{
			if(\CCrmLead::CheckUpdatePermission($this->entityID, $this->userPermissions))
			{
				$this->arResult['READ_ONLY'] = false;
			}
			elseif(\CCrmLead::CheckReadPermission($this->entityID, $this->userPermissions))
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
			if(\CCrmLead::CheckCreatePermission($this->userPermissions))
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
		$this->prepareStatusList();

		$this->initializeData();

		//region GUID & Editor Config ID
		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID'])
			? $this->arParams['GUID'] : $this->getDefaultGuid();

		$this->arResult['EDITOR_CONFIG_ID'] = isset($this->arParams['EDITOR_CONFIG_ID'])
			? $this->arParams['EDITOR_CONFIG_ID'] : $this->getDefaultConfigID();
		//endregion

		$progressSemantics = $this->entityData['STATUS_ID']
			? \CCrmLead::GetStatusSemantics($this->entityData['STATUS_ID']) : '';
		$this->arResult['PROGRESS_SEMANTICS'] = $progressSemantics;

		$this->arResult['ENTITY_ATTRIBUTE_SCOPE'] = "";

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
		$this->arResult['ENABLE_PROGRESS_CHANGE'] = !$this->arResult['READ_ONLY'];

		$this->arResult['CONVERSION_TYPE_ID'] = LeadConversionDispatcher::resolveTypeID($this->entityData);
		if($this->arResult['CAN_CONVERT'])
		{
			$config = LeadConversionDispatcher::getConfiguration(array('FIELDS' => $this->entityData));
			$schemeID = $config->getSchemeID();

			$this->arResult['CONVERSION_SCHEME'] = array(
				'ORIGIN_URL' => $APPLICATION->GetCurPage(),
				'SCHEME_ID' => $schemeID,
				'SCHEME_NAME' => LeadConversionScheme::resolveName($schemeID),
				'SCHEME_DESCRIPTION' => LeadConversionScheme::getDescription($schemeID),
				'SCHEME_CAPTION' => GetMessage('CRM_LEAD_CREATE_ON_BASIS')
			);

			$this->arResult['CONVERSION_CONFIGS'] = LeadConversionDispatcher::getJavaScriptConfigurations();
			$this->arResult['CONVERSION_SCRIPT_DESCRIPTIONS'] = LeadConversionScheme::getJavaScriptDescriptions(false);
		}
		//endregion

		//region Config
		$this->prepareConfiguration();
		//endregion

		$currencyID = CCrmCurrency::GetBaseCurrencyID();
		if(isset($this->entityData['CURRENCY_ID']) && $this->entityData['CURRENCY_ID'] !== '')
		{
			$currencyID = $this->entityData['CURRENCY_ID'];
		}

		//region Controllers
		$this->arResult['ENTITY_CONTROLLERS'] = [];
		if ($useNewProductList)
		{
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

			$this->arResult['ENTITY_CONTROLLERS'][] = [
				'name' => 'PRODUCT_LIST',
				'type' => 'product_list',
				'config' => [
					'productListId' => $this->arResult['PRODUCT_EDITOR_ID'],
					'currencyList' => $currencyList,
					'currencyId' => $currencyID
				]
			];
			unset($currencyList);
		}
		else
		{
			$this->arResult['ENTITY_CONTROLLERS'][] = [
				'name' => 'PRODUCT_ROW_PROXY',
				'type' => 'product_row_proxy',
				'config' => array('editorId' => $this->arResult['PRODUCT_EDITOR_ID'])
			];
		}
		//endregion

		//region Validators
		$this->prepareValidators();
		//endregion

		//region Tabs
		$this->arResult['TABS'] = array();
		ob_start();

		if ($useNewProductList)
		{
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
					'CURRENCY_ID' => $currencyID,
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
		}
		else
		{
			$APPLICATION->IncludeComponent(
				'bitrix:crm.product_row.list',
				'',
				[
					'ID' => $this->arResult['PRODUCT_EDITOR_ID'],
					'PREFIX' => $this->arResult['PRODUCT_EDITOR_ID'],
					'FORM_ID' => '',
					'OWNER_ID' => $this->entityID,
					'OWNER_TYPE' => 'L',
					'PERMISSION_TYPE' => $this->arResult['READ_ONLY'] ? 'READ' : 'WRITE',
					'PERMISSION_ENTITY_TYPE' => $this->arResult['PERMISSION_ENTITY_TYPE'],
					'PERSON_TYPE_ID' => $this->resolvePersonTypeID($this->entityData),
					'CURRENCY_ID' => $currencyID,
					'LOCATION_ID' => $this->isTaxMode && isset($this->entityData['LOCATION_ID']) ? $this->entityData['LOCATION_ID'] : '',
					'CLIENT_SELECTOR_ID' => '', //TODO: Add Client Selector
					//'PRODUCT_ROWS' =>  null,
					'HIDE_MODE_BUTTON' => !$this->isEditMode ? 'Y' : 'N',
					'TOTAL_SUM' => isset($this->entityData['OPPORTUNITY']) ? $this->entityData['OPPORTUNITY'] : null,
					'TOTAL_TAX' => isset($this->entityData['TAX_VALUE']) ? $this->entityData['TAX_VALUE'] : null,
					'PRODUCT_DATA_FIELD_NAME' => $this->arResult['PRODUCT_DATA_FIELD_NAME'],
					'PATH_TO_PRODUCT_EDIT' => $this->arResult['PATH_TO_PRODUCT_EDIT'],
					'PATH_TO_PRODUCT_SHOW' => $this->arResult['PATH_TO_PRODUCT_SHOW'],
					'INIT_LAYOUT' => 'N',
					'INIT_EDITABLE' => $this->arResult['READ_ONLY'] ? 'N' : 'Y',
					'ENABLE_MODE_CHANGE' => 'N',
					'USE_ASYNC_ADD_PRODUCT' => 'Y',
					'BUILDER_CONTEXT' => Crm\Product\Url\ProductBuilder::TYPE_ID,
				],
				false,
				[
					'HIDE_ICONS' => 'Y',
					'ACTIVE_COMPONENT' => 'Y',
				]
			);
		}

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
			$this->arResult['TABS'][] = array(
				'id' => 'tab_quote',
				'name' => Loc::getMessage('CRM_LEAD_TAB_QUOTE'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.quote.list/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '',
						'params' => array(
							'QUOTE_COUNT' => '20',
							'PATH_TO_QUOTE_SHOW' => $this->arResult['PATH_TO_QUOTE_SHOW'],
							'PATH_TO_QUOTE_EDIT' => $this->arResult['PATH_TO_QUOTE_EDIT'],
							'INTERNAL_FILTER' => array('LEAD_ID' => $this->entityID),
							'INTERNAL_CONTEXT' => array('LEAD_ID' => $this->entityID),
							'GRID_ID_SUFFIX' => 'LEAD_DETAILS',
							'TAB_ID' => 'tab_quote',
							'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
							'ENABLE_TOOLBAR' => true,
							'PRESERVE_HISTORY' => true,
							'ADD_EVENT_NAME' => 'CrmCreateQuoteFromLead'
						)
					)
				)
			);
			if (\Bitrix\Crm\Automation\Factory::isAutomationAvailable(CCrmOwnerType::Lead))
			{
				Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/crm.automation/templates/.default/style.css');
				Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/bizproc.automation/templates/.default/style.css');
				$this->arResult['TABS'][] = array(
					'id' => 'tab_automation',
					'name' => Loc::getMessage('CRM_LEAD_TAB_AUTOMATION'),
					'loader' => array(
						'serviceUrl' => '/bitrix/components/bitrix/crm.automation/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
						'componentData' => array(
							'template' => '',
							'params' => array(
								'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
								'ENTITY_ID' => $this->entityID,
								'back_url' => \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Lead, $this->entityID)
							)
						)
					)
				);

			}
			if (CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled())
			{
				$this->arResult['TABS'][] = array(
					'id' => 'tab_bizproc',
					'name' => Loc::getMessage('CRM_LEAD_TAB_BIZPROC'),
					'loader' => array(
						'serviceUrl' => '/bitrix/components/bitrix/bizproc.document/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
						'componentData' => array(
							'template' => 'frame',
							'params' => array(
								'MODULE_ID' => 'crm',
								'ENTITY' => 'CCrmDocumentLead',
								'DOCUMENT_TYPE' => 'LEAD',
								'DOCUMENT_ID' => 'LEAD_'.$this->entityID
							)
						)
					)
				);
				$this->arResult['BIZPROC_STARTER_DATA'] = array(
					'templates' => CBPDocument::getTemplatesForStart(
						$this->userID,
						array('crm', 'CCrmDocumentLead', 'LEAD'),
						array('crm', 'CCrmDocumentLead', 'LEAD_'.$this->entityID),
						[
							'DocumentStates' => []
						]
					),
					'moduleId' => 'crm',
					'entity' => 'CCrmDocumentLead',
					'documentType' => 'LEAD',
					'documentId' => 'LEAD_'.$this->entityID
				);
			}
			$this->arResult['TABS'][] = array(
				'id' => 'tab_tree',
				'name' => Loc::getMessage('CRM_LEAD_TAB_TREE'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.entity.tree/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '.default',
						'params' => array(
							'ENTITY_ID' => $this->entityID,
							'ENTITY_TYPE_NAME' => CCrmOwnerType::LeadName,
						)
					)
				)
			);
			$this->arResult['TABS'][] = array(
				'id' => 'tab_event',
				'name' => Loc::getMessage('CRM_LEAD_TAB_EVENT'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.event.view/lazyload.ajax.php?&site'.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '',
						'contextId' => "LEAD_{$this->entityID}_EVENT",
						'params' => array(
							'AJAX_OPTION_ADDITIONAL' => "LEAD_{$this->entityID}_EVENT",
							'ENTITY_TYPE' => 'LEAD',
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
				'name' => Loc::getMessage('CRM_LEAD_TAB_QUOTE'),
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
			$this->arResult['TABS'][] = array(
				'id' => 'tab_event',
				'name' => Loc::getMessage('CRM_LEAD_TAB_EVENT'),
				'enabled' => false
			);
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
		$this->arResult['WAIT_TARGET_DATES'] = array();
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

		$this->arResult['USER_FIELD_FILE_URL_TEMPLATE'] = $this->getFileUrlTemplate();

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
		if(isset($this->arResult['ENTITY_FIELDS']))
		{
			return $this->arResult['ENTITY_FIELDS'];
		}

		$prohibitedStatusIDs = array();
		$allStatuses = CCrmStatus::GetStatusList('STATUS');
		foreach(array_keys($allStatuses) as $statusID)
		{
			if($this->arResult['READ_ONLY'])
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

		$this->arResult['ENTITY_FIELDS'] = array(
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
				'title' => Loc::getMessage('CRM_LEAD_FIELD_STATUS_ID'),
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
				'title' => Loc::getMessage('CRM_LEAD_FIELD_STATUS_DESCRIPTION'),
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
							'NOT_SELECTED_VALUE' => ''
						]
					),
					'defaultValue' => $this->defaultEntityData['SOURCE_ID'] ?? null,
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
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE']

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
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE'],
					'messages' => array('addObserver' => Loc::getMessage('CRM_LEAD_FIELD_ADD_OBSERVER'))
				)
			),
			array(
				'name' => 'OPENED',
				'title' => Loc::getMessage('CRM_LEAD_FIELD_OPENED'),
				'type' => 'boolean',
				'editable' => true
			),
			array(
				'name' => 'COMMENTS',
				'title' => Loc::getMessage('CRM_LEAD_FIELD_COMMENTS'),
				'type' => 'html',
				'editable' => true
			),
			array(
				'name' => 'PRODUCT_ROW_SUMMARY',
				'title' => Loc::getMessage('CRM_LEAD_FIELD_PRODUCTS'),
				'type' => 'product_row_summary',
				'editable' => false,
				'enableAttributes' => false,
				'transferable' => false,
				'mergeable' => false
			)
		);

		if($this->customerType === CustomerType::GENERAL)
		{
			$this->arResult['ENTITY_FIELDS'] = array_merge(
				$this->arResult['ENTITY_FIELDS'],
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
									'NOT_SELECTED_VALUE' => '',
								]
							),
							'defaultValue' => $this->defaultEntityData['HONORIFIC'] ?? null,
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
			$this->arResult['ENTITY_FIELDS'] = array_merge(
				$this->arResult['ENTITY_FIELDS'],
				[$addressField]
			);

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
		}

		//region Client

		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'CLIENT',
			'title' => Loc::getMessage('CRM_LEAD_FIELD_CLIENT'),
			'type' => 'client_light',
			'editable' => true,
			'data' => array(
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
							'url' => '/bitrix/components/bitrix/crm.lead.edit/ajax.php?'.bitrix_sessid_get()
						)
					)
				),
				'clientEditorFieldsParams' => $this->prepareClientEditorFieldsParams()
			)
		);
		//endregion

		Tracking\UI\Details::appendEntityFields($this->arResult['ENTITY_FIELDS']);
		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'UTM',
			'title' => Loc::getMessage('CRM_LEAD_FIELD_UTM'),
			'type' => 'custom',
			'data' => array('view' => 'UTM_VIEW_HTML'),
			'editable' => false,
			'enableAttributes' => false
		);

		$this->arResult['ENTITY_FIELDS'] = array_merge(
			$this->arResult['ENTITY_FIELDS'],
			array_values($this->userFieldInfos)
		);

		$isEntityDataModified = false;
		$attrConfigs = $this->prepareEntityFieldAttributeConfigs();
		for($i = 0, $length = count($this->arResult['ENTITY_FIELDS']); $i < $length; $i++)
		{
			$isPhaseDependent = FieldAttributeManager::isPhaseDependent();
			if (!$isPhaseDependent)
			{
				if (!is_array($this->arResult['ENTITY_FIELDS'][$i]['data']))
				{
					$this->arResult['ENTITY_FIELDS'][$i]['data'] = [];
				}
				$this->arResult['ENTITY_FIELDS'][$i]['data']['isPhaseDependent'] = false;
			}

			$fieldName = $this->arResult['ENTITY_FIELDS'][$i]['name'];
			if(!isset($attrConfigs[$fieldName]))
			{
				continue;
			}

			if(!isset($this->arResult['ENTITY_FIELDS'][$i]['data']))
			{
				$this->arResult['ENTITY_FIELDS'][$i]['data'] = array();
			}

			$this->arResult['ENTITY_FIELDS'][$i]['data']['attrConfigs'] = $attrConfigs[$fieldName];

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
					if (!is_array($this->arResult['ENTITY_FIELDS'][$i]['data']))
					{
						$this->arResult['ENTITY_FIELDS'][$i]['data'] = [];
					}
					$this->arResult['ENTITY_FIELDS'][$i]['data']['isRequiredByAttribute'] = true;

					// This block allows in the component crm.entity.editor to determine the presence of mandatory
					// standard entity fields with empty values.
					if (is_array($this->entityData)
						&& $this->isEntityFieldHasEmpyValue($this->arResult['ENTITY_FIELDS'][$i]))
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

		return $this->arResult['ENTITY_FIELDS'];
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

		$userFieldConfigElements = array();
		foreach(array_keys($this->userFieldInfos) as $fieldName)
		{
			$userFieldConfigElements[] = array('name' => $fieldName);
		}

		$sectionMain = array(
			'name' => 'main',
			'title' => Loc::getMessage('CRM_LEAD_SECTION_MAIN'),
			'type' => 'section',
			'elements' => array()
		);
		$sectionAdditional = array(
			'name' => 'additional',
			'title' => Loc::getMessage('CRM_LEAD_SECTION_ADDITIONAL'),
			'type' => 'section',
			'elements' => array()
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
			$multiFieldConfigElements = array();
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

		$this->userFieldInfos = array();
		$userFields = $this->prepareEntityUserFields();
		$enumerationFields = array();
		foreach($userFields as $userField)
		{
			$fieldName = $userField['FIELD_NAME'];
			$fieldInfo = array(
				'USER_TYPE_ID' => $userField['USER_TYPE_ID'],
				'ENTITY_ID' => \CCrmLead::GetUserFieldEntityID(),
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
			$this->entityFieldAttributeConfig = Crm\Attribute\FieldAttributeManager::getEntityConfigurations(
				CCrmOwnerType::Lead,
				Crm\Attribute\FieldAttributeManager::resolveEntityScope(
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
			$requiredFields = Crm\Attribute\FieldAttributeManager::isEnabled()
				? Crm\Attribute\FieldAttributeManager::getRequiredFields(
					CCrmOwnerType::Lead,
					$this->entityID,
					['SOURCE_ID', 'HONORIFIC', Tracking\UI\Details::SourceId],
					Crm\Attribute\FieldOrigin::SYSTEM
				)
				: [];
			$fieldsInfo = \CCrmLead::GetFieldsInfo();
			$this->entityData = array();
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
			$dbResult = \CCrmLead::GetListEx(
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
			$this->entityData['OBSERVER_INFOS'] = array();

			$userDbResult = \CUser::GetList(
				'ID',
				'ASC',
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
		//region Client Info
		$clientInfo = array();

		$companyID = isset($this->entityData['COMPANY_ID']) ? (int)$this->entityData['COMPANY_ID'] : 0;
		if($companyID > 0)
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

		$contactBindings = array();
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
		$clientInfo['CONTACT_DATA'] = array();
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

		if($this->enableSearchHistory)
		{
			$this->entityData['LAST_COMPANY_INFOS'] = Crm\Controller\Action\Entity\SearchAction::prepareSearchResultsJson(
				Crm\Controller\Entity::getRecentlyUsedItems(
					'crm.lead.details',
					'company',
					array('EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Company)
				)
			);
			$this->entityData['LAST_CONTACT_INFOS'] = Crm\Controller\Action\Entity\SearchAction::prepareSearchResultsJson(
				Crm\Controller\Entity::getRecentlyUsedItems(
					'crm.lead.details',
					'contact',
					array('EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Contact)
				)
			);
		}

		//endregion
		//region Multifield Data
		if($this->customerType === CustomerType::GENERAL)
		{
			if($this->entityID > 0)
			{
				$this->prepareMultifieldData(
					CCrmOwnerType::Lead,
					array($this->entityID),
					array()
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
			if($companyID > 0)
			{
				$this->prepareMultifieldData(\CCrmOwnerType::Company, array($companyID), array('PHONE', 'EMAIL', 'IM'));
			}

			if(!empty($contactIDs))
			{
				$this->prepareMultifieldData(CCrmOwnerType::Contact, $contactIDs, array('PHONE', 'EMAIL', 'IM'));
			}
		}
		//endregion

		//region Product row
		$productRowCount = 0;
		$productRowInfos = array();
		if($this->entityID > 0)
		{
			$productRows = \CCrmProductRow::LoadRows('L', $this->entityID);
			foreach($productRows as $productRow)
			{
				$productName = isset($productRow['PRODUCT_NAME']) ? $productRow['PRODUCT_NAME'] : '';
				if($productName === '' && isset($productRow['ORIGINAL_PRODUCT_NAME']))
				{
					$productName = $productRow['ORIGINAL_PRODUCT_NAME'];
				}

				$productID = isset($productRow['PRODUCT_ID']) ? (int)$productRow['PRODUCT_ID'] : 0;
				$url = '';
				if($productID > 0)
				{
					$url = CComponentEngine::MakePathFromTemplate(
						$this->arResult['PATH_TO_PRODUCT_SHOW'],
						array('product_id' => $productRow['PRODUCT_ID'])
					);
				}

				if($productRow['TAX_INCLUDED'] === 'Y')
				{
					$sum = $productRow['PRICE'] * $productRow['QUANTITY'];
				}
				else
				{
					$sum = round($productRow['PRICE_EXCLUSIVE'] * $productRow['QUANTITY'], 2) * (1 + $productRow['TAX_RATE'] / 100);
				}

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

			$calculateOptions = array();
			if($this->isTaxMode)
			{
				$calcOptions['ALLOW_LD_TAX'] = 'Y';
				$calcOptions['LOCATION_ID'] = isset($this->entityData['LOCATION_ID']) ? $this->entityData['LOCATION_ID'] : '';
			}

			$result = CCrmSaleHelper::Calculate(
				$productRows,
				$this->entityData['CURRENCY_ID'],
				$this->resolvePersonTypeID($this->entityData),
				false,
				SITE_ID,
				$calculateOptions
			);

			$this->entityData['PRODUCT_ROW_SUMMARY'] = array(
				'count' => $productRowCount,
				'total' => CCrmCurrency::MoneyToString(
					isset($result['PRICE']) ? round((double)$result['PRICE'], 2) : 0.0,
					$this->entityData['CURRENCY_ID']
				),
				'items' => $productRowInfos
			);
		}
		//endregion

		Tracking\UI\Details::prepareEntityData(
			\CCrmOwnerType::Lead,
			$this->entityID,
			$this->entityData,
			$isTrackingFieldRequired
		);

		return ($this->arResult['ENTITY_DATA'] = $this->entityData);
	}
	protected function prepareMultifieldData($entityTypeID, array $entityIDs, array $typeIDs)
	{
		if(empty($entityIDs))
		{
			return;
		}

		$multiFieldEntityTypes = \CCrmFieldMulti::GetEntityTypes();
		$multiFieldViewClassNames = array(
			'PHONE' => 'crm-entity-phone-number',
			'EMAIL' => 'crm-entity-email',
			'IM' => 'crm-entity-phone-number'
		);

		if(!isset($this->entityData['MULTIFIELD_DATA']))
		{
			$this->entityData['MULTIFIELD_DATA'] = array();
		}

		$filter = array(
			'=ENTITY_ID' => CCrmOwnerType::ResolveName($entityTypeID),
			'@ELEMENT_ID' => $entityIDs
		);

		if(!empty($typeIDs))
		{
			$filter['@TYPE_ID'] = $typeIDs;
		}

		$dbResult = CCrmFieldMulti::GetListEx(array('ID' => 'asc'), $filter);
		while($fields = $dbResult->Fetch())
		{
			$elementID = (int)$fields['ELEMENT_ID'];
			$typeID = $fields['TYPE_ID'];
			$value = isset($fields['VALUE']) ? $fields['VALUE'] : '';
			if($value === '')
			{
				continue;
			}

			$ID = $fields['ID'];
			$complexID = isset($fields['COMPLEX_ID']) ? $fields['COMPLEX_ID'] : '';
			$valueTypeID = isset($fields['VALUE_TYPE']) ? $fields['VALUE_TYPE'] : '';

			//Is required for phone & email & messenger menu
			if($typeID === 'PHONE' || $typeID === 'EMAIL'
				|| ($typeID === 'IM' && preg_match('/^imol\|/', $value) === 1)
			)
			{
				$entityKey = "{$entityTypeID}_{$elementID}";
				if(!isset($this->entityData['MULTIFIELD_DATA'][$typeID]))
				{
					$this->entityData['MULTIFIELD_DATA'][$typeID] = array();
				}

				if(!isset($this->entityData['MULTIFIELD_DATA'][$typeID][$entityKey]))
				{
					$this->entityData['MULTIFIELD_DATA'][$typeID][$entityKey] = array();
				}

				$formattedValue = $typeID === 'PHONE'
					? Main\PhoneNumber\Parser::getInstance()->parse($value)->format()
					: $value;

				$this->entityData['MULTIFIELD_DATA'][$typeID][$entityKey][] = array(
					'ID' => $ID,
					'VALUE' => $value,
					'VALUE_TYPE' => $valueTypeID,
					'VALUE_FORMATTED' => $formattedValue,
					'COMPLEX_ID' => $complexID,
					'COMPLEX_NAME' => \CCrmFieldMulti::GetEntityNameByComplex($complexID, false)
				);
			}

			if($entityTypeID === CCrmOwnerType::Lead && $elementID === $this->entityID)
			{
				$multiFieldID = $ID;
				if($this->isCopyMode)
				{
					$multiFieldID = "n0{$multiFieldID}";
				}

				$this->entityData[$typeID][] = array(
					'ID' => $multiFieldID,
					'VALUE' => $value,
					'VALUE_TYPE' => $valueTypeID,
					'VIEW_DATA' => \CCrmViewHelper::PrepareMultiFieldValueItemData(
						$typeID,
						array(
							'VALUE' => $value,
							'VALUE_TYPE_ID' => $valueTypeID,
							'VALUE_TYPE' => isset($multiFieldEntityTypes[$typeID][$valueTypeID])
								? $multiFieldEntityTypes[$typeID][$valueTypeID] : null,
							'CLASS_NAME' => isset($multiFieldViewClassNames[$typeID])
								? $multiFieldViewClassNames[$typeID] : ''
						),
						array(
							'ENABLE_SIP' => false,
							'SIP_PARAMS' => array(
								'ENTITY_TYPE_NAME' => CCrmOwnerType::LeadName,
								'ENTITY_ID' => $this->entityID,
								'AUTO_FOLD' => true
							)
						)
					)
				);
			}
		}
	}
	protected function prepareStatusList()
	{
		if($this->statuses === null)
		{
			$this->statuses = array();
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
	protected function getFileUrlTemplate(): string
	{
		return '/bitrix/components/bitrix/crm.lead.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#';
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
	}

	public function getEntityEditorData(): array
	{
		return [
			'ENTITY_ID' => $this->getEntityID(),
			'ENTITY_DATA' => $this->prepareEntityData()
		];
	}
}
