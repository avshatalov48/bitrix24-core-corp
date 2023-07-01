<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @var \CBitrixComponent $component
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global CDatabase $DB
 */

if (!CModule::IncludeModule('crm'))
{
	return;
}

\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();

use Bitrix\Crm\Integration\Sender\Rc;
use Bitrix\Main\Localization\Loc;

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if ($CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE))
{
	return;
}

$curPage =  $APPLICATION->GetCurPage();

$arParams['PATH_TO_LEAD_LIST'] = CrmCheckPath(
	'PATH_TO_LEAD_LIST',
	$arParams['PATH_TO_LEAD_LIST'] ?? '',
	$curPage
);
$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath(
	'PATH_TO_LEAD_EDIT',
	$arParams['PATH_TO_LEAD_EDIT'] ?? '',
	$curPage . '?lead_id=#lead_id#&edit'
);
$arParams['PATH_TO_LEAD_DETAILS'] = CrmCheckPath(
	'PATH_TO_LEAD_DETAILS',
	$arParams['PATH_TO_LEAD_DETAILS'] ?? '',
	$curPage . '?lead_id=#lead_id#&details'
);
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath(
	'PATH_TO_LEAD_SHOW',
	$arParams['PATH_TO_LEAD_SHOW'] ?? '',
	$curPage . '?lead_id=#lead_id#&show'
);
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath(
	'PATH_TO_LEAD_CONVERT',
	$arParams['PATH_TO_LEAD_CONVERT'] ?? '',
	$curPage . '?lead_id=#lead_id#&convert'
);
$arParams['PATH_TO_LEAD_IMPORT'] = CrmCheckPath(
	'PATH_TO_LEAD_IMPORT',
	$arParams['PATH_TO_LEAD_IMPORT'] ?? '',
	$curPage . '?import'
);
$arParams['PATH_TO_LEAD_DEDUPE'] = CrmCheckPath(
	'PATH_TO_LEAD_DEDUPE',
	$arParams['PATH_TO_LEAD_DEDUPE'] ?? '',
	$curPage
);

$arParams['PATH_TO_MIGRATION'] = SITE_DIR . "marketplace/category/migration/";

$arResult['PATH_TO_LEAD_WIDGET'] = CrmCheckPath(
	'PATH_TO_LEAD_WIDGET',
	$arParams['PATH_TO_LEAD_WIDGET'] ?? '',
	$curPage . "?widget"
);

$arResult['PATH_TO_LEAD_KANBAN'] = CrmCheckPath(
	'PATH_TO_LEAD_KANBAN',
	$arParams['PATH_TO_LEAD_KANBAN'] ?? '',
	$curPage . "?kanban"
);

$arResult['PATH_TO_LEAD_CALENDAR'] = CrmCheckPath(
	'PATH_TO_LEAD_CALENDAR',
	$arParams['PATH_TO_LEAD_CALENDAR'] ?? '',
	$curPage."?calendar"
);

$arResult['PATH_TO_CONFIG_CHECKER'] = CComponentEngine::MakePathFromTemplate(\COption::GetOptionString('crm', 'path_to_config_checker'));

$arResult['PATH_TO_LEAD_STATUS_LIST'] = CrmCheckPath(
	'PATH_TO_LEAD_STATUS_LIST',
	$arParams['PATH_TO_LEAD_STATUS_LIST'] ?? '',
	COption::GetOptionString('crm', 'path_to_lead_status_list')
);

$arParams['ELEMENT_ID'] = (int)($arParams['ELEMENT_ID'] ?? 0);

if (!isset($arParams['TYPE']))
{
	$arParams['TYPE'] = 'list';
}

if (isset($_REQUEST['copy']))
{
	$arParams['TYPE'] = 'copy';
}

$toolbarID = 'toolbar_lead_' . $arParams['TYPE'];

if ($arParams['ELEMENT_ID'] > 0)
{
	$toolbarID .= '_' . $arParams['ELEMENT_ID'];
}

$arResult['TOOLBAR_ID'] = $toolbarID;
$arResult['BUTTONS'] = [];
$isInSlider = isset($arParams['IN_SLIDER']) && $arParams['IN_SLIDER'] === 'Y';

if ($arParams['ELEMENT_ID'] > 0)
{
	$dbRes = CCrmLead::GetListEx([], array('=ID' => $arParams['ELEMENT_ID'],  'CHECK_PERMISSIONS' => 'N'), false, false, array('ID', 'STATUS_ID', 'IS_RETURN_CUSTOMER'));
	$arFields = $dbRes->Fetch();
}
else
{
	$arFields = [];
}

$bConfig = false;
if ($arParams['TYPE'] === 'list')
{
	$bRead   = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ');
	$bExport = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'EXPORT');
	$bImport = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'IMPORT');
	$bAdd    = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'WRITE');
	$bDelete = false;
	$bConfig = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

	$bDedupe = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'WRITE')
		&& !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'DELETE');
}
else
{
	$bExport = false;
	$bImport = false;
	$bDedupe = false;

	$bRead   = CCrmLead::CheckReadPermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bAdd    = CCrmLead::CheckCreatePermission($CrmPerms);
	$bWrite  = CCrmLead::CheckUpdatePermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bDelete = CCrmLead::CheckDeletePermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bExclude = \Bitrix\Crm\Exclusion\Access::current()->canWrite();
}

if (isset($arParams['DISABLE_IMPORT']) && $arParams['DISABLE_IMPORT'] === 'Y')
{
	$bImport = false;
}
if (isset($arParams['DISABLE_DEDUPE']) && $arParams['DISABLE_DEDUPE'] === 'Y')
{
	$bDedupe = false;
}
if (isset($arParams['DISABLE_EXPORT']) && $arParams['DISABLE_EXPORT'] === 'Y')
{
	$bExport = false;
}

if (!$bRead && !$bAdd && !$bWrite)
{
	return false;
}

$conversionConfig = \Bitrix\Crm\Conversion\LeadConversionDispatcher::getConfiguration(array('FIELDS' => $arFields));
$conversionTypeID = $conversionConfig->getTypeID();
$conversionSchemeID = $conversionConfig->getCurrentSchemeID();

if($arParams['ELEMENT_ID'] > 0)
{
	\CCrmLead::PrepareConversionPermissionFlags($arParams['ELEMENT_ID'], $arResult, $CrmPerms);
}

$isSliderEnabled = \CCrmOwnerType::IsSliderEnabled(\CCrmOwnerType::Lead);

//Skip COPY menu in slider mode
if($arParams['TYPE'] == 'copy' && $isSliderEnabled)
{
	return false;
}

if($arParams['TYPE'] === 'details')
{
	if($arParams['ELEMENT_ID'] <= 0)
	{
		return false;
	}

	$scripts = isset($arParams['~SCRIPTS']) && is_array($arParams['~SCRIPTS']) ? $arParams['~SCRIPTS'] : [];


	//region APPLICATION PLACEMENT
	$placementGroupInfos = \Bitrix\Crm\Integration\Rest\AppPlacementManager::getHandlerInfos(
		\Bitrix\Crm\Integration\Rest\AppPlacement::LEAD_DETAIL_TOOLBAR
	);
	foreach($placementGroupInfos as $placementGroupName => $placementInfos)
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'rest-app-toolbar',
			'NAME' => $placementGroupName,
			'DATA' => array(
				'OWNER_INFO' => isset($arParams['OWNER_INFO']) ? $arParams['OWNER_INFO'] : [],
				'PLACEMENT' => \Bitrix\Crm\Integration\Rest\AppPlacement::LEAD_DETAIL_TOOLBAR,
				'APP_INFOS' => $placementInfos
			)
		);
	}
	//endregion

	if (!empty($arParams['BIZPROC_STARTER_DATA']))
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'bizproc-starter-button',
			'DATA' => $arParams['BIZPROC_STARTER_DATA']
		);
	}

	if($arResult['CAN_CONVERT'])
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'toolbar-conv-scheme',
			'PARAMS' => array(
				'NAME' => 'lead_converter',
				'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
				'ENTITY_TYPE_NAME' => CCrmOwnerType::LeadName,
				'ENTITY_ID' => $arParams['ELEMENT_ID'],
				'TYPE_ID' => $conversionTypeID,
				'SCHEME_ID' => $conversionSchemeID,
				'SCHEME_NAME' => \Bitrix\Crm\Conversion\LeadConversionScheme::resolveName($conversionSchemeID),
				'SCHEME_DESCRIPTION' => \Bitrix\Crm\Conversion\LeadConversionScheme::getDescription($conversionSchemeID),
				'IS_PERMITTED' => $arResult['CONVERSION_PERMITTED'],
				'LOCK_SCRIPT' => isset($arResult['CONVERSION_LOCK_SCRIPT']) ? $arResult['CONVERSION_LOCK_SCRIPT'] : ''
			),
			'CODE' => 'convert',
			'TEXT' => GetMessage('LEAD_CREATE_ON_BASIS'),
			'TITLE' => GetMessage('LEAD_CREATE_ON_BASIS_TITLE'),
			'ICON' => 'btn-convert'
		);
	}

	//Force start new bar after first button
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

	if($bWrite)
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'crm-communication-panel',
			'DATA' => array(
				'ENABLE_CALL' => \Bitrix\Main\ModuleManager::isModuleInstalled('calendar'),
				'OWNER_INFO' => isset($arParams['OWNER_INFO']) ? $arParams['OWNER_INFO'] : [],
				'MULTIFIELDS' => isset($arParams['MULTIFIELD_DATA']) ? $arParams['MULTIFIELD_DATA'] : []
			)
		);
	}

	if($bAdd)
	{
		$copyUrl = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_LEAD_DETAILS'],
				array('lead_id' => $arParams['ELEMENT_ID'])
			),
			array('copy' => 1)
		);

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('LEAD_COPY'),
			'TITLE' => GetMessage('LEAD_COPY_TITLE'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($copyUrl)."')",
			'ICON' => 'btn-copy'
		);
	}

	if($bExclude && isset($scripts['EXCLUDE']))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('LEAD_EXCLUDE'),
			'TITLE' => GetMessage('LEAD_EXCLUDE_TITLE'),
			'ONCLICK' => $scripts['EXCLUDE'],
			'ICON' => 'btn-delete'
		);
	}

	if($bDelete && isset($scripts['DELETE']))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('LEAD_DELETE'),
			'TITLE' => GetMessage('LEAD_DELETE_TITLE'),
			'ONCLICK' => $scripts['DELETE'],
			'ICON' => 'btn-delete'
		);
	}

	if(\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->isDocumentButtonAvailable())
	{
		$arResult['BUTTONS'][] = [
			'TEXT' => GetMessage('DOCUMENT_BUTTON_TEXT'),
			'TITLE' => GetMessage('DOCUMENT_BUTTON_TITLE'),
			'TYPE' => 'crm-document-button',
			'PARAMS' => \Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->getDocumentButtonParameters(\Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Lead::class, $arParams['ELEMENT_ID']),
		];
	}

	$this->IncludeComponentTemplate();
	return;
}

if($arParams['TYPE'] === 'list')
{
	if ($bAdd)
	{
		$arResult['RC'] = [
			'CAN_USE' => Rc\Service::canUse(),
			'IS_AVAILABLE' => Rc\Service::isAvailable(),
			'NAME' => Rc\Service::getName(),
			'PATH_TO_ADD' => Rc\Service::getPathToAddLead(),
			'JS_AVAILABLE_POPUP_SHOWER' => Rc\Service::getJsAvailablePopupShower(),
		];

		if($arResult['RC']['CAN_USE'] && !$arResult['RC']['IS_AVAILABLE'])
		{
			Rc\Service::initJsExtensions();
		}
	}

	$link = CComponentEngine::MakePathFromTemplate(
		$arParams[$isSliderEnabled ? 'PATH_TO_LEAD_DETAILS' : 'PATH_TO_LEAD_EDIT'],
		['lead_id' => 0]
	);

	if($arResult['RC']['CAN_USE'])
	{
		$itemAdd = ['TEXT' => GetMessage('LEAD_CREATE')];
		if($isSliderEnabled)
		{
			$itemAdd['ONCLICK'] = 'BX.SidePanel.Instance.open("' . CUtil::JSEscape($link) . '")';
		}
		else
		{
			$itemAdd['LINK'] = $link;
		}

		$arResult['BUTTONS'][] = [
			'TYPE' => 'crm-btn-double',
			'TEXT' => GetMessage('CRM_COMMON_ACTION_CREATE'),
			'LINK' => $link,
			'ITEMS' => [
				$itemAdd,
				[
					'TEXT' => $arResult['RC']['NAME'],
					'ONCLICK' => $arResult['RC']['IS_AVAILABLE']
						?
						'BX.SidePanel.Instance.open("' . CUtil::JSEscape($arResult['RC']['PATH_TO_ADD']) . '")'
						:
						$arResult['RC']['JS_AVAILABLE_POPUP_SHOWER'],
					'CLASS_NAME' => $arResult['RC']['IS_AVAILABLE'] ? '' : 'b24-tariff-lock'
				],
			],
			'HIGHLIGHT' => true,
			'IS_DISABLED' => !$bAdd,
			'HINT' => GetMessage('CRM_LEAD_ADD_HINT')
		];
	}
	else
	{
		$arResult['BUTTONS'][] = [
			'TEXT' => GetMessage('CRM_COMMON_ACTION_CREATE'),
			'LINK' => $link,
			'HIGHLIGHT' => true,
			'IS_DISABLED' => !$bAdd,
			'HINT' => GetMessage('CRM_LEAD_ADD_HINT')
		];
	}

	if ($bImport && !$isInSlider)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('LEAD_IMPORT'),
			'TITLE' => GetMessage('LEAD_IMPORT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_IMPORT'], array()),
			'ICON' => 'btn-import'
		);

		CModule::IncludeModule('rest');
		CJSCore::Init(array('marketplace'));

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('LEAD_MIGRATION'),
			'TITLE' => GetMessage('LEAD_MIGRATION_TITLE'),
			'ONCLICK' => 'BX.rest.Marketplace.open({}, \'migration\');',
			'ICON' => 'btn-migration'
		);

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('LEAD_GENERATOR'),
			'TITLE' => GetMessage('LEAD_GENERATOR_TITLE'),
			'ONCLICK' => 'BX.rest.Marketplace.open({}, \'leads\');',
			'ICON' => 'btn-migration'
		);
		$arResult['BUTTONS'][] = array('SEPARATOR' => true);
	}

	if ($bExport && !$isInSlider)
	{
		$entityType = \CCrmOwnerType::LeadName;
		$stExportId = 'EXPORT_'.$entityType;
		$componentName = 'bitrix:crm.lead.list';

		$componentParams = array(
			'LEAD_COUNT' => '20',
			'PATH_TO_LEAD_SHOW' => $arResult['PATH_TO_LEAD_SHOW'] ?? '',
			'PATH_TO_LEAD_EDIT' => $arResult['PATH_TO_LEAD_EDIT'] ?? '',
			'PATH_TO_LEAD_CONVERT' => $arResult['PATH_TO_LEAD_CONVERT'] ?? '',
			'PATH_TO_LEAD_WIDGET' => $arResult['PATH_TO_LEAD_WIDGET'] ?? '',
			'PATH_TO_LEAD_KANBAN' => $arResult['PATH_TO_LEAD_KANBAN'] ?? '',
			'PATH_TO_LEAD_CALENDAR' => $arResult['PATH_TO_LEAD_CALENDAR'] ?? '',
			'NAVIGATION_CONTEXT_ID' => $entityType
		);

		if (isset($_REQUEST['WG']) && mb_strtoupper($_REQUEST['WG']) === 'Y')
		{
			$widgetDataFilter = \Bitrix\Crm\Widget\Data\LeadDataSource::extractDetailsPageUrlParams($_REQUEST);
			if (!empty($widgetDataFilter))
			{
				$componentParams['WIDGET_DATA_FILTER'] = $widgetDataFilter;
			}
		}

		$arResult['EXPORT_CSV_PARAMS'] = [
			'id' => $stExportId. '_CSV',
			'controller' => 'bitrix:crm.api.export',
			'queue' => [
				[
					'action' => 'dispatcher',
				],
			],
			'params' => [
				'SITE_ID' => SITE_ID,
				'ENTITY_TYPE' => $entityType,
				'EXPORT_TYPE' => 'csv',
				'COMPONENT_NAME' => $componentName,
				'signedParameters' => \Bitrix\Main\Component\ParameterSigner::signParameters(
					$componentName,
					$componentParams
				),
			],
			'optionsFields' => array(
				'EXPORT_ALL_FIELDS' => array(
					'name' => 'EXPORT_ALL_FIELDS',
					'type' => 'checkbox',
					'title' => Loc::getMessage('LEAD_EXPORT_OPTION_EXPORT_ALL_FIELDS'),
					'value' => 'N'
				),
				'EXPORT_PRODUCT_FIELDS' => array(
					'name' => 'EXPORT_PRODUCT_FIELDS',
					'type' => 'checkbox',
					'title' => Loc::getMessage('LEAD_EXPORT_OPTION_EXPORT_PRODUCT_FIELDS'),
					'value' => 'N'
				),
			),
			'messages' => array(
				'DialogTitle' => Loc::getMessage('LEAD_EXPORT_CSV_TITLE'),
				'DialogSummary' => Loc::getMessage('LEAD_EXPORT_SUMMARY'),
			),
			'dialogMaxWidth' => 650,
		];

		// clone params for excel export
		$arResult['EXPORT_EXCEL_PARAMS'] = $arResult['EXPORT_CSV_PARAMS'];
		$arResult['EXPORT_EXCEL_PARAMS']['id'] = $stExportId. '_EXCEL';
		$arResult['EXPORT_EXCEL_PARAMS']['params']['EXPORT_TYPE'] = 'excel';
		$arResult['EXPORT_EXCEL_PARAMS']['messages']['DialogTitle'] = Loc::getMessage('LEAD_EXPORT_EXCEL_TITLE');

		$arResult['BUTTONS'][] = array('SEPARATOR' => true);

		$arResult['BUTTONS'][] = array(
			'TITLE' => Loc::getMessage('LEAD_EXPORT_CSV_TITLE'),
			'TEXT' => Loc::getMessage('LEAD_EXPORT_CSV'),
			'ONCLICK' => "BX.UI.StepProcessing.ProcessManager.get('{$stExportId}_CSV').showDialog()",
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => Loc::getMessage('LEAD_EXPORT_EXCEL_TITLE'),
			'TEXT' => Loc::getMessage('LEAD_EXPORT_EXCEL'),
			'ONCLICK' => "BX.UI.StepProcessing.ProcessManager.get('{$stExportId}_EXCEL').showDialog()",
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array('SEPARATOR' => true);

		unset($entityType, $stExportId, $randomSequence, $stExportManagerId);
	}

	if ($bDedupe && !$isInSlider)
	{
		$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getDuplicateControlRestriction();
		if($restriction->hasPermission())
		{
			$dedupePath = CComponentEngine::MakePathFromTemplate(
				\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isDedupeWizardEnabled()
					? $arParams['PATH_TO_LEAD_DEDUPEWIZARD']
					: $arParams['PATH_TO_LEAD_DEDUPE']
			);

			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('LEAD_DEDUPE'),
				'TITLE' => GetMessage('LEAD_DEDUPE_TITLE'),
				'ONCLICK' => 'BX.Crm.Page.openSlider("'.$dedupePath.'", {cacheable: false})'
			);
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('LEAD_DEDUPE_AUTOSEARCH'),
				'TITLE' => GetMessage('LEAD_DEDUPE_AUTOSEARCH'),
				'ONCLICK' => 'BX.Crm.DedupeAutosearch.getDefault("LEAD").showSettings()'
			);
			$arResult['BUTTONS'][] = array(
				'HTML' => GetMessage('LEAD_DEDUPE_HELP').' <span class="ui-hint"><span class="ui-hint-icon"></span></span>',
				'TITLE' => GetMessage('LEAD_DEDUPE_HELP'),
				'ONCLICK' => 'BX.Helper.show("redirect=detail&code=10649014")'
			);
		}
		else
		{
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('LEAD_DEDUPE'),
				'TITLE' => GetMessage('LEAD_DEDUPE_TITLE'),
				'ONCLICK' => $restriction->prepareInfoHelperScript(),
				'MENU_ICON' => 'grid-lock'
			);
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('LEAD_DEDUPE_AUTOSEARCH'),
				'TITLE' => GetMessage('LEAD_DEDUPE_AUTOSEARCH'),
				'ONCLICK' => $restriction->prepareInfoHelperScript(),
				'MENU_ICON' => 'grid-lock'
			);
			$arResult['BUTTONS'][] = array(
				'HTML' => GetMessage('LEAD_DEDUPE_HELP').' <span class="ui-hint"><span class="ui-hint-icon"></span></span>',
				'TITLE' => GetMessage('LEAD_DEDUPE_HELP'),
				'ONCLICK' => 'BX.Helper.show("redirect=detail&code=10649014")'
			);
		}
		$arResult['BUTTONS'][] = array('SEPARATOR' => true);
	}

	if(
		\Bitrix\Main\Loader::includeModule('rest')
		&& is_callable('\Bitrix\Rest\Marketplace\Url::getConfigurationPlacementUrl')
		&& ($bAdd || $bWrite || $bConfig)
		&& !$isInSlider
	)
	{
		if ($bConfig)
		{
			$arResult['BUTTONS'][] = [
				'TEXT' => GetMessage("CONFIG_CHECKER"),
				'TITLE' => GetMessage("CONFIG_CHECKER_TITLE"),
				'ONCLICK' => 'BX.SidePanel.Instance.open(\''.$arResult["PATH_TO_CONFIG_CHECKER"].'\', {cacheable: false});'
			];
			$arResult["IS_NEED_TO_CHECK"] = true;
		}
		$url = \Bitrix\Rest\Marketplace\Url::getConfigurationPlacementUrl('crm_lead', 'setting_list');
		$arResult['BUTTONS'][] = [
			'TEXT' => GetMessage('LEAD_VERTICAL_CRM'),
			'TITLE' => GetMessage('LEAD_VERTICAL_CRM_TITLE'),
			'ONCLICK' => 'BX.SidePanel.Instance.open(\''.$url.'\');'
		];
		$arResult['BUTTONS'][] = array('SEPARATOR' => true);
	}

	if ($bConfig && !$isInSlider)
	{
		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');
		$arResult['BUTTONS'][] = \Bitrix\Crm\Settings\LeadSettings::getCrmTypeMenuItem(true);
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('LEAD_CRM_CONFIG_STATUSES'),
			'TITLE' => GetMessage('LEAD_CRM_CONFIG_STATUSES_TITLE'),
			'ONCLICK' => 'BX.Crm.Lead.Menu.onClickConfigStatuses(\''.CUtil::JSEscape($arResult["PATH_TO_LEAD_STATUS_LIST"]).'\')'
		);
	}

	if (
		isset($arParams['ADDITIONAL_SETTINGS_MENU_ITEMS'])
		&& is_array($arParams['ADDITIONAL_SETTINGS_MENU_ITEMS'])
		&& !$isInSlider
	)
	{
		$arResult['BUTTONS'] = array_merge($arResult['BUTTONS'], $arParams['ADDITIONAL_SETTINGS_MENU_ITEMS']);
	}

	if(count($arResult['BUTTONS']) > 1)
	{
		//Force start new bar after first button
		array_splice($arResult['BUTTONS'], 1, 0, [['NEWBAR' => true]]);
	}

	$this->IncludeComponentTemplate();

	return;
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show')
	&& !empty($arParams['ELEMENT_ID'])
	&& $bWrite
)
{
	$plannerButton = \Bitrix\Crm\Activity\Planner::getToolbarButton($arParams['ELEMENT_ID'], CCrmOwnerType::Lead);
	if($plannerButton)
	{
		CJSCore::Init(array('crm_activity_planner'));
		$arResult['BUTTONS'][] = $plannerButton;
	}
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show')
	&& $arParams['ELEMENT_ID'] > 0
	&& $arResult['CAN_CONVERT']
)
{
	$arResult['BUTTONS'][] = array(
		'TYPE' => 'toolbar-conv-scheme',
		'PARAMS' => array(
			'NAME' => 'lead_converter',
			'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::LeadName,
			'ENTITY_ID' => $arParams['ELEMENT_ID'],
			'TYPE_ID' => $conversionTypeID,
			'SCHEME_ID' => $conversionSchemeID,
			'SCHEME_NAME' => \Bitrix\Crm\Conversion\LeadConversionScheme::resolveName($conversionSchemeID),
			'SCHEME_DESCRIPTION' => \Bitrix\Crm\Conversion\LeadConversionScheme::getDescription($conversionSchemeID),
			'IS_PERMITTED' => true
		),
		'CODE' => 'convert',
		'TEXT' => GetMessage('LEAD_CREATE_ON_BASIS'),
		'TITLE' => GetMessage('LEAD_CREATE_ON_BASIS_TITLE'),
		'ICON' => 'btn-convert'
	);
}

if (($arParams['TYPE'] == 'show') && $bRead && $arParams['ELEMENT_ID'] > 0)
{
	$subscrTypes = CCrmSonetSubscription::GetRegistationTypes(
		CCrmOwnerType::Lead,
		$arParams['ELEMENT_ID'],
		$currentUserID
	);

	$isResponsible = in_array(CCrmSonetSubscriptionType::Responsibility, $subscrTypes, true);
	if(!$isResponsible)
	{
		$subscriptionID = 'lead_sl_subscribe';
		$arResult['SONET_SUBSCRIBE'] = array(
			'ID' => $subscriptionID,
			'SERVICE_URL' => CComponentEngine::makePathFromTemplate(
				'#SITE_DIR#bitrix/components/bitrix/crm.lead.edit/ajax.php?site_id=#SITE#&sessid=#SID#',
				array('SID' => bitrix_sessid())
			),
			'ACTION_NAME' => 'ENABLE_SONET_SUBSCRIPTION',
			'RELOAD' => true
		);

		$isObserver = in_array(CCrmSonetSubscriptionType::Observation, $subscrTypes, true);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_unsubscribe',
			'TEXT' => GetMessage('CRM_LEAD_SL_UNSUBSCRIBE'),
			'TITLE' => GetMessage('CRM_LEAD_SL_UNSUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].unsubscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_unsubscribe', false); tb.setButtonVisible('sl_subscribe', true); })",
			'ICON' => 'btn-nofollow',
			'VISIBLE' => $isObserver
		);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_subscribe',
			'TEXT' => GetMessage('CRM_LEAD_SL_SUBSCRIBE'),
			'TITLE' => GetMessage('CRM_LEAD_SL_SUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].subscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_subscribe', false); tb.setButtonVisible('sl_unsubscribe', true); })",
			'ICON' => 'btn-follow',
			'VISIBLE' => !$isObserver
		);
	}
}

if (($arParams['TYPE'] == 'show' || $arParams['TYPE'] == 'convert') && $bWrite
	&& !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('LEAD_EDIT'),
		'TITLE' => GetMessage('LEAD_EDIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
			array(
				'lead_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-edit'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'convert') && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('LEAD_SHOW'),
		'TITLE' => GetMessage('LEAD_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
			array(
				'lead_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

$qty = count($arResult['BUTTONS']);

if (!empty($arResult['BUTTONS']) && ($arParams['TYPE'] == 'list' ||
	($arParams['TYPE'] == 'edit' && empty($arParams['ELEMENT_ID']))))
	$arResult['BUTTONS'][] = array('SEPARATOR' => true);
elseif ($arParams['TYPE'] == 'show' && $qty > 1)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
elseif ($qty >= 3 || ($arFields['STATUS_ID'] == 'CONVERTED' && $qty >= 2))
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

if ($bAdd && ($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show' || $arParams['TYPE'] == 'convert')
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('LEAD_COPY'),
		'TITLE' => GetMessage('LEAD_COPY_TITLE'),
		'LINK' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
			array(
				'lead_id' => $arParams['ELEMENT_ID']
			)),
			array('copy' => 1)
		),
		'ICON' => 'btn-copy'
	);
}

/*if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show' || $arParams['TYPE'] == 'convert') && $bDelete
	&& !empty($arParams['ELEMENT_ID']))*/
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('LEAD_DELETE'),
		'TITLE' => GetMessage('LEAD_DELETE_TITLE'),
		'LINK' => "javascript:BX.Crm.Lead.Menu.onClickDelete('".GetMessage('LEAD_DELETE_DLG_TITLE')."', '".GetMessage('LEAD_DELETE_DLG_MESSAGE')."', '".GetMessage('LEAD_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
				array(
					'lead_id' => $arParams['ELEMENT_ID']
				)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

if ($bAdd && $arParams['TYPE'] != 'list')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_COMMON_ACTION_CREATE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams[$isSliderEnabled ? 'PATH_TO_LEAD_DETAILS' : 'PATH_TO_LEAD_EDIT'],
			array('lead_id' => 0)
		),
		'TARGET' => '_blank',
		'ICON' => 'btn-new'
	);
}

$this->IncludeComponentTemplate();
