<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\Component\EntityList\ActionManager;
use Bitrix\Crm\Conversion\LeadConversionScheme;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\UI\NavigationBarPanel;
use Bitrix\Main\Localization\Loc;

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if (SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable())
{
	CBitrix24::initLicenseInfoPopupJS();
}

Bitrix\Main\UI\Extension::load(
	[
		'crm.merger.batchmergemanager',
		'crm.router',
		'ui.fonts.opensans',
	]
);

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/progress_control.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/batch_conversion.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/batch_deletion.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/partial_entity_editor.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/dialog.js');

Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/autorun_proc.css');


?><div id="batchConversionWrapper"></div><?
echo (\Bitrix\Crm\Tour\NumberOfClients::getInstance())->build();
?><div id="batchDeletionWrapper"></div><?

?><div id="rebuildMessageWrapper"><?

if (!empty($arResult['NEED_TO_SHOW_DUP_INDEX_PROCESS'])):
	?><div id="backgroundLeadIndexRebuildWrapper"></div><?
endif;

if (!empty($arResult['NEED_TO_SHOW_DUP_MERGE_PROCESS'])):
	?><div id="backgroundLeadMergeWrapper"></div><?
endif;

if (!empty($arResult['NEED_TO_SHOW_DUP_VOL_DATA_PREPARE'])):
	?><div id="backgroundLeadDupVolDataPrepareWrapper"></div><?
endif;

if (!empty($arResult['NEED_FOR_REBUILD_DUP_INDEX'])):
?><div id="rebuildLeadDupIndexMsg" class="crm-view-message">
	<?=Loc::getMessage('CRM_LEAD_REBUILD_DUP_INDEX', ['#ID#' => 'rebuildLeadDupIndexLink', '#URL#' => '#'])?>
</div><?
endif;

if (!empty($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']))
{
	?><div id="rebuildLeadSearchWrapper"></div><?
}

if (!empty($arResult['NEED_FOR_REBUILD_TIMELINE_SEARCH_CONTENT']))
{
	?><div id="buildTimelineSearchWrapper"></div><?
}

if (!empty($arResult['NEED_FOR_BUILD_TIMELINE']))
{
	?><div id="buildLeadTimelineWrapper"></div><?
}

if (!empty($arResult['NEED_FOR_REFRESH_ACCOUNTING']))
{
	?><div id="refreshLeadAccountingWrapper"></div><?
}

if (!empty($arResult['NEED_FOR_REBUILD_LEAD_SEMANTICS']))
{
	?><div id="rebuildLeadSemanticsWrapper"></div><?
}

if (!empty($arResult['NEED_FOR_REBUILD_SECURITY_ATTRS']))
{
	?><div id="rebuildLeadSecurityAttrsWrapper"></div><?
}

if (!empty($arResult['NEED_FOR_REBUILD_LEAD_ATTRS'])):
	?><div id="rebuildLeadAttrsMsg" class="crm-view-message">
	<?=Loc::getMessage('CRM_LEAD_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildLeadAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
	</div><?
endif;
?></div><?

if (isset($arResult['ERROR_HTML'])):
	ShowError($arResult['ERROR_HTML']);
endif;

$isInternal = $arResult['INTERNAL'];
$callListUpdateMode = $arResult['CALL_LIST_UPDATE_MODE'];
$allowWrite = $arResult['PERMS']['WRITE'] && !$callListUpdateMode;
$allowDelete = $arResult['PERMS']['DELETE'] && !$callListUpdateMode;
$allowExclude = $arResult['CAN_EXCLUDE'];
$currentUserID = $arResult['CURRENT_USER_ID'];
$activityEditorID = '';
if (!$isInternal):
	$activityEditorID = "{$arResult['GRID_ID']}_activity_editor";
	$APPLICATION->IncludeComponent(
		'bitrix:crm.activity.editor',
		'',
		[
			'EDITOR_ID' => $activityEditorID,
			'PREFIX' => $arResult['GRID_ID'],
			'OWNER_TYPE' => 'LEAD',
			'OWNER_ID' => 0,
			'READ_ONLY' => false,
			'ENABLE_UI' => false,
			'ENABLE_TOOLBAR' => false,
			'SKIP_VISUAL_COMPONENTS' => 'Y'
		],
		null,
		['HIDE_ICONS' => 'Y']
	);
endif;

$gridManagerID = $arResult['GRID_ID'] . '_MANAGER';
$preparedGridId = htmlspecialcharsbx(CUtil::JSescape($gridManagerID));
$gridManagerCfg = [
	'ownerType' => 'LEAD',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'activityEditorId' => $activityEditorID,
	'serviceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'listServiceUrl' => '/bitrix/components/bitrix/crm.lead.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'filterFields' => [],
	'userFilterHash' => $arResult['DB_FILTER_HASH'],
	'enableIterativeDeletion' => true,
	'messages' => [
		'deletionDialogTitle' => Loc::getMessage('CRM_LEAD_LIST_DEL_PROC_DLG_TITLE'),
		'deletionDialogSummary' => Loc::getMessage('CRM_LEAD_LIST_DEL_PROC_DLG_SUMMARY')
	]
];

echo CCrmViewHelper::RenderLeadStatusSettings();
$prefix = $arResult['GRID_ID'];

$prefix = $arResult['GRID_ID'] ?? '';
$arResult['GRID_DATA'] = [];
$arColumns = [];

foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}

$now = time() + CTimeZone::GetOffset();

$fieldContentTypeMap = \Bitrix\Crm\Model\FieldContentTypeTable::loadForMultipleItems(
	\CCrmOwnerType::Lead,
	array_keys($arResult['LEAD']),
);

if ($arResult['NEED_ADD_ACTIVITY_BLOCK'] ?? false)
{
	$arResult['LEAD'] = (new \Bitrix\Crm\Component\EntityList\NearestActivity\Manager(CCrmOwnerType::Lead))->appendNearestActivityBlock($arResult['LEAD']);
}

$toolsManager = Container::getInstance()->getIntranetToolsManager();
$availabilityManager = AvailabilityManager::getInstance();
$isQuoteAvailable = $toolsManager->checkEntityTypeAvailability(\CCrmOwnerType::Quote);

foreach($arResult['LEAD'] as $sKey => $arLead)
{
	$arActivityMenuItems = [];
	$arActivitySubMenuItems = [];
	$arActions = [];

	$arActions[] = [
		'TITLE' => Loc::getMessage('CRM_LEAD_SHOW_TITLE'),
		'TEXT' => Loc::getMessage('CRM_LEAD_SHOW'),
		'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arLead['PATH_TO_LEAD_SHOW'])."')",
		'DEFAULT' => true
	];

	if ($arLead['EDIT'])
	{
		$arActions[] = [
			'TITLE' => Loc::getMessage('CRM_LEAD_EDIT_TITLE'),
			'TEXT' => Loc::getMessage('CRM_LEAD_EDIT'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arLead['PATH_TO_LEAD_EDIT'])."')"
		];

		$arActions[] = [
			'TITLE' => Loc::getMessage('CRM_LEAD_COPY_TITLE'),
			'TEXT' => Loc::getMessage('CRM_LEAD_COPY'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arLead['PATH_TO_LEAD_COPY'])."')"
		];
	}

	if (!$isInternal)
	{
		if ($arLead['DELETE'])
		{
			$pathToRemove = CUtil::JSEscape($arLead['PATH_TO_LEAD_DELETE']);
			$arActions[] = [
				'TITLE' => Loc::getMessage('CRM_LEAD_DELETE_TITLE'),
				'TEXT' => Loc::getMessage('CRM_LEAD_DELETE'),
				'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
					'{$gridManagerID}',
					BX.CrmUIGridMenuCommand.remove,
					{ pathToRemove: '{$pathToRemove}' }
				)"
			];
		}

		if ($arResult['CAN_EXCLUDE'] && $arLead['CAN_EXCLUDE'])
		{
			$pathToExclude = CUtil::JSEscape($arLead['PATH_TO_LEAD_EXCLUDE']);
			$arActions[] = [
				'TITLE' => Loc::getMessage('CRM_LEAD_EXCLUDE_TITLE'),
				'TEXT' => Loc::getMessage('CRM_LEAD_EXCLUDE'),
				'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
					'{$gridManagerID}',
					BX.CrmUIGridMenuCommand.exclude,
					{ pathToExclude: '{$pathToExclude}' }
				)"
			];
		}
	}

	$arActions[] = ['SEPARATOR' => true];

	if (!$isInternal)
	{
		if ($arResult['CAN_CONVERT'])
		{
			$isReturnCustomer = $arLead['IS_RETURN_CUSTOMER'] === 'Y';

			$config = \Bitrix\Crm\Conversion\LeadConversionDispatcher::getConfiguration(
				['FIELDS' => $arLead]
			);

			$arSchemeList = [];

			foreach($config->getScheme()->getItems() as $item)
			{
				if (empty($item->getAvailabilityLock()))
				{
					$onClick = sprintf(
						"BX.Crm.Conversion.Manager.Instance.getConverter('%s').setAnalyticsElement('%s').convertBySchemeItemId('%s', %d);",
						\CUtil::JSEscape($arResult['CONVERTER_ID_PREFIX'] . '_' . $config->getTypeID()),
						Integration\Analytics\Dictionary::ELEMENT_GRID_ROW_CONTEXT_MENU,
						\CUtil::JSEscape($item->getId()),
						(int)$arLead['ID']
					);
				}
				else
				{
					$onClick = $item->getAvailabilityLock();
				}

				$arSchemeList[] = [
					'TITLE' => $item->getPhrase(),
					'TEXT' => $item->getPhrase(),
					'ONCLICK' => $onClick,
				];
			}

			if (!empty($arSchemeList))
			{
				if (!$isReturnCustomer)
				{
					$arSchemeList[] = [
						'TITLE' => Loc::getMessage('CRM_LEAD_CONV_OPEN_ENTITY_SEL'),
						'TEXT' => Loc::getMessage('CRM_LEAD_CONV_OPEN_ENTITY_SEL'),
						'ONCLICK' => sprintf(
<<<js
(function() {
	const selector = BX.Crm.Conversion.Manager.Instance.createEntitySelector(
		'%s',
		[BX.CrmEntityType.enumeration.contact, BX.CrmEntityType.enumeration.company],
		%d,
	);
	if (selector)
	{
		selector.getConverter().setAnalyticsElement('%s');
		selector.show();
	}
})()
js,
							\CUtil::JSEscape($arResult['CONVERTER_ID_PREFIX'] . '_' . $config->getTypeID()),
							(int)$arLead['ID'],
							Integration\Analytics\Dictionary::ELEMENT_GRID_ROW_CONTEXT_MENU,
						),
					];
				}

				$arActions[] = ['SEPARATOR' => true];
				$arActions[] = [
					'TITLE' => Loc::getMessage('CRM_LEAD_CREATE_ON_BASIS_TITLE'),
					'TEXT' => Loc::getMessage('CRM_LEAD_CREATE_ON_BASIS'),
					'MENU' => $arSchemeList
				];
			}
		}

		$arActions[] = ['SEPARATOR' => true];
	}

	if (!$isInternal)
	{
		if ($arLead['EDIT'])
		{
			$currentUser = CUtil::PhpToJSObject(CCrmViewHelper::getUserInfo(true, false));
			$pingSettings = CUtil::PhpToJSObject((new TodoPingSettingsProvider(\CCrmOwnerType::Lead))->fetchForJsComponent());
			$arActivitySubMenuItems[] = [
				'TEXT' => Loc::getMessage('CRM_LEAD_ADD_TODO'),
				'ONCLICK' => "BX.CrmUIGridExtension.showActivityAddingPopupFromMenu('".$preparedGridId."', " . CCrmOwnerType::Lead . ", " . (int)$arLead['ID'] . ", " . $currentUser . ", " . $pingSettings . ");"
			];

			if (IsModuleInstalled('subscribe'))
			{
				$arActions[] = [
					'TITLE' => Loc::getMessage('CRM_LEAD_ADD_EMAIL_TITLE'),
					'TEXT' => Loc::getMessage('CRM_LEAD_ADD_EMAIL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.email, settings: { ownerID: {$arLead['ID']} } }
					)"
				];

				$arActivityMenuItems[] = [
					'TITLE' => Loc::getMessage('CRM_LEAD_ADD_EMAIL_TITLE'),
					'TEXT' => Loc::getMessage('CRM_LEAD_ADD_EMAIL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.email, settings: { ownerID: {$arLead['ID']} } }
					)"
				];
			}

			if (
					IsModuleInstalled(CRM_MODULE_CALENDAR_ID)
					&& \Bitrix\Crm\Settings\ActivitySettings::areOutdatedCalendarActivitiesEnabled()
			)
			{
				$arActivityMenuItems[] = [
					'TITLE' => Loc::getMessage('CRM_LEAD_ADD_CALL_TITLE'),
					'TEXT' => Loc::getMessage('CRM_LEAD_ADD_CALL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arLead['ID']} } }
					)"
				];

				$arActivityMenuItems[] = [
					'TITLE' => Loc::getMessage('CRM_LEAD_ADD_MEETING_TITLE'),
					'TEXT' => Loc::getMessage('CRM_LEAD_ADD_MEETING'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arLead['ID']} } }
					)"
				];

				$arActivitySubMenuItems[] = [
					'TITLE' => Loc::getMessage('CRM_LEAD_ADD_CALL_TITLE'),
					'TEXT' => Loc::getMessage('CRM_LEAD_ADD_CALL_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arLead['ID']} } }
					)"
				];

				$arActivitySubMenuItems[] = [
					'TITLE' => Loc::getMessage('CRM_LEAD_ADD_MEETING_TITLE'),
					'TEXT' => Loc::getMessage('CRM_LEAD_ADD_MEETING_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arLead['ID']} } }
					)"
				];
			}

			if (IsModuleInstalled('tasks'))
			{
				$arActivityMenuItems[] = [
					'TITLE' => Loc::getMessage('CRM_LEAD_TASK_TITLE'),
					'TEXT' => Loc::getMessage('CRM_LEAD_TASK'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arLead['ID']} } }
					)"
				];

				$arActivitySubMenuItems[] = [
					'TITLE' => Loc::getMessage('CRM_LEAD_TASK_TITLE'),
					'TEXT' => Loc::getMessage('CRM_LEAD_TASK_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arLead['ID']} } }
					)"
				];
			}

			if (!empty($arActivitySubMenuItems))
			{
				$arActions[] = [
					'TITLE' => Loc::getMessage('CRM_LEAD_ADD_ACTIVITY_TITLE'),
					'TEXT' => Loc::getMessage('CRM_LEAD_ADD_ACTIVITY'),
					'MENU' => $arActivitySubMenuItems
				];
			}

			if (IsModuleInstalled('sale'))
			{
				if ($isQuoteAvailable)
				{
					$onClick = "jsUtils.Redirect([], '" . CUtil::JSEscape($arLead['PATH_TO_QUOTE_ADD']) . "');";
				}
				else
				{
					$onClick = $availabilityManager->getEntityTypeAvailabilityLock(\CCrmOwnerType::Quote);
				}

				$quoteAction = [
					'TITLE' => Loc::getMessage('CRM_LEAD_ADD_QUOTE_TITLE_MSGVER_1'),
					'TEXT' => Loc::getMessage('CRM_LEAD_ADD_QUOTE_MSGVER_1'),
					'ONCLICK' => $onClick,
				];

				if ($isQuoteAvailable && \Bitrix\Crm\Settings\QuoteSettings::getCurrent()->isFactoryEnabled())
				{
					unset($quoteAction['ONCLICK']);

					$link = Container::getInstance()->getRouter()->getItemDetailUrl(
						\CCrmOwnerType::Quote,
						0,
						null
					);

					$link->addParams([
						'lead_id' => $arLead['ID'],
					]);
					$quoteAction['HREF'] = $link;
				}
				$arActions[] = $quoteAction;
			}

			if ($arResult['IS_BIZPROC_AVAILABLE'])
			{
				$arActions[] = ['SEPARATOR' => true];
				if (isset($arLead['PATH_TO_BIZPROC_LIST']) && $arLead['PATH_TO_BIZPROC_LIST'] !== '')
				{
					$arActions[] = [
						'TITLE' => Loc::getMessage('CRM_LEAD_BIZPROC_TITLE'),
						'TEXT' => Loc::getMessage('CRM_LEAD_BIZPROC'),
						'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arLead['PATH_TO_BIZPROC_LIST'])."');"
					];
				}

				if (!empty($arLead['BIZPROC_LIST']))
				{
					$arBizprocList = [];
					foreach($arLead['BIZPROC_LIST'] as $arBizproc)
					{
						$arBizprocList[] = [
							'TITLE' => $arBizproc['DESCRIPTION'],
							'TEXT' => $arBizproc['NAME'],
							'ONCLICK' => isset($arBizproc['ONCLICK'])
								? $arBizproc['ONCLICK']
								: "jsUtils.Redirect([], '".CUtil::JSEscape($arBizproc['PATH_TO_BIZPROC_START'])."');"
						];
					}

					$arActions[] = [
						'TITLE' => Loc::getMessage('CRM_LEAD_BIZPROC_LIST_TITLE'),
						'TEXT' => Loc::getMessage('CRM_LEAD_BIZPROC_LIST'),
						'MENU' => $arBizprocList
					];
				}
			}
		}
	}

	$eventParam = [
		'ID' => $arLead['ID'],
		'CALL_LIST_ID' => $arResult['CALL_LIST_ID'],
		'CALL_LIST_CONTEXT' => $arResult['CALL_LIST_CONTEXT'],
		'GRID_ID' => $arResult['GRID_ID']
	];

	foreach (GetModuleEvents('crm', 'onCrmLeadListItemBuildMenu', true) as $event)
	{
		ExecuteModuleEventEx($event, ['CRM_LEAD_LIST_MENU', $eventParam, &$arActions]);
	}

	$dateCreate = $arLead['DATE_CREATE'] ?? '';
	$dateModify = $arLead['DATE_MODIFY'] ?? '';
	$webformId = null;
	if (isset($arLead['WEBFORM_ID']))
	{
		$webformId = $arResult['WEBFORM_LIST'][$arLead['WEBFORM_ID']] ?? $arLead['WEBFORM_ID'];
	}

	$resultItem = array(
		'id' => $arLead['ID'],
		'actions' => $arActions,
		'data' => $arLead,
		'editable' => !$arLead['EDIT'] ? $arColumns : true,
		'columns' => array(
			'LEAD_SUMMARY' => CCrmViewHelper::RenderInfo(
				$arLead['PATH_TO_LEAD_SHOW'],
				$arLead['TITLE'] ?? ('['.$arLead['ID'].']'),
				Tracking\UI\Grid::enrichSourceName(
					\CCrmOwnerType::Lead,
					$arLead['ID'],
					$arLead['LEAD_SOURCE_NAME']
				),
				[
					'TARGET' => '_top',
					'LEGEND' => $arLead['LEAD_LEGEND']
				]
			),
			'LEAD_CLIENT' => isset($arLead['CLIENT_INFO']) ? CCrmViewHelper::PrepareClientInfo($arLead['CLIENT_INFO']) : '',
			'COMMENTS' => htmlspecialcharsback($arLead['COMMENTS'] ?? ''),
			'ADDRESS' => nl2br($arLead['ADDRESS'] ?? ''),
			'ASSIGNED_BY' => isset($arLead['~ASSIGNED_BY_ID']) && $arLead['~ASSIGNED_BY_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml([
					'PREFIX' => "LEAD_{$arLead['~ID']}_RESPONSIBLE",
					'USER_ID' => $arLead['~ASSIGNED_BY_ID'],
					'USER_NAME'=> $arLead['ASSIGNED_BY'],
					'USER_PROFILE_URL' => $arLead['PATH_TO_USER_PROFILE']
				])
				: '',
			'STATUS_DESCRIPTION' => nl2br($arLead['STATUS_DESCRIPTION'] ?? ''),
			'SOURCE_DESCRIPTION' => nl2br($arLead['SOURCE_DESCRIPTION'] ?? ''),
			'DATE_CREATE' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($dateCreate), $now),
			'DATE_MODIFY' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($dateModify), $now),
			'SUM' => $arLead['FORMATTED_OPPORTUNITY'],
			'OPPORTUNITY' => $arLead['~OPPORTUNITY'] ?? 0.0,
			'CURRENCY_ID' => CCrmCurrency::GetEncodedCurrencyName($arLead['~CURRENCY_ID'] ?? null),
			'PRODUCT_ID' => isset($arLead['PRODUCT_ROWS'])
				? htmlspecialcharsbx(CCrmProductRow::RowsToString($arLead['PRODUCT_ROWS']))
				: '',
			'IS_RETURN_CUSTOMER' => isset($arResult['BOOLEAN_VALUES_LIST'], $arLead['IS_RETURN_CUSTOMER'])
				? $arResult['BOOLEAN_VALUES_LIST'][$arLead['IS_RETURN_CUSTOMER']]
				: $arLead['IS_RETURN_CUSTOMER'],
			'HONORIFIC' => !empty($arResult['HONORIFIC']) && !empty($arLead['HONORIFIC'])
				? $arResult['HONORIFIC'][$arLead['HONORIFIC']]
				: '',
			'STATUS_ID' => CCrmViewHelper::RenderLeadStatusControl(
				array(
					'PREFIX' => "{$arResult['GRID_ID']}_PROGRESS_BAR_",
					'ENTITY_ID' => $arLead['~ID'],
					'CURRENT_ID' => $arLead['~STATUS_ID'],
					'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.list/list.ajax.php',
					'CONVERSION_SCHEME' => isset($arResult['CONVERSION']['SCHEMES']) && isset($arResult['CONVERSION']['SCHEMES'][$arLead['CONVERSION_TYPE_ID']])
						? $arResult['CONVERSION']['SCHEMES'][$arLead['CONVERSION_TYPE_ID']]
						: null,
					'CAN_CONVERT' => $arResult['CAN_CONVERT'],
					'CONVERTER_ID' => $arResult['CONVERTER_ID_PREFIX'] . '_' . $arLead['CONVERSION_TYPE_ID'],
					'CONVERSION_TYPE_ID' => $arLead['CONVERSION_TYPE_ID'],
					'READ_ONLY' => !(isset($arLead['EDIT']) && $arLead['EDIT'] === true),
				)
			),
			'SOURCE_ID' => $arLead['LEAD_SOURCE_NAME'] ?? null,
			'WEBFORM_ID' => $webformId,
			'CREATED_BY' => isset($arLead['~CREATED_BY']) && $arLead['~CREATED_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "LEAD_{$arLead['~ID']}_CREATOR",
						'USER_ID' => $arLead['~CREATED_BY'],
						'USER_NAME'=> $arLead['CREATED_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arLead['PATH_TO_USER_CREATOR'],
					)
				)
				: '',
			'MODIFY_BY' => isset($arLead['~MODIFY_BY']) && $arLead['~MODIFY_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "LEAD_{$arLead['~ID']}_MODIFIER",
						'USER_ID' => $arLead['~MODIFY_BY'],
						'USER_NAME'=> $arLead['MODIFY_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arLead['PATH_TO_USER_MODIFIER']
					)
				)
				: '',
			'OBSERVERS' => CCrmViewHelper::renderObservers(\CCrmOwnerType::Lead, $arLead['ID'], $arLead['~OBSERVERS'] ?? []),
		) + CCrmViewHelper::RenderListMultiFields($arLead, "LEAD_{$arLead['ID']}_", array('ENABLE_SIP' => true, 'SIP_PARAMS' => array('ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::LeadName, 'ENTITY_ID' => $arLead['ID']))) + $arResult['LEAD_UF'][$sKey]
	);

	Tracking\UI\Grid::appendRows(
		\CCrmOwnerType::Lead,
		$arLead['ID'],
		$resultItem['columns']
	);

	$resultItem['columns'] = \Bitrix\Crm\Entity\CommentsHelper::enrichGridRow(
		\CCrmOwnerType::Lead,
		$fieldContentTypeMap[$arLead['ID']] ?? [],
		$arLead,
		$resultItem['columns'],
	);

	if (isset($arLead['~BIRTHDATE']))
	{
		$resultItem['columns']['BIRTHDATE'] = '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arLead['~BIRTHDATE'])).'</nobr>';
	}

	if (isset($arLead['ACTIVITY_BLOCK']) && $arLead['ACTIVITY_BLOCK'] instanceof \Bitrix\Crm\Component\EntityList\NearestActivity\Block)
	{
		$resultItem['columns']['ACTIVITY_ID'] = $arLead['ACTIVITY_BLOCK']->render($gridManagerID);
		if ($arLead['ACTIVITY_BLOCK']->needHighlight())
		{
			$resultItem['columnClasses'] = ['ACTIVITY_ID' => 'crm-list-deal-today'];
		}
	}

	$arResult['GRID_DATA'][] = &$resultItem;
	unset($resultItem);
}

//region Action Panel
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));

if (!$isInternal
	&& ($allowWrite || $allowDelete || $allowExclude ||  $callListUpdateMode))
{
	$yesnoList = array(
		array('NAME' => Loc::getMessage('MAIN_YES'), 'VALUE' => 'Y'),
		array('NAME' => Loc::getMessage('MAIN_NO'), 'VALUE' => 'N')
	);

	$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
	$applyButton = $snippet->getApplyButton(
		array(
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processApplyButtonClick('{$gridManagerID}')"))
				)
			)
		)
	);

	$actionList = array(array('NAME' => Loc::getMessage('CRM_LEAD_LIST_CHOOSE_ACTION'), 'VALUE' => 'none'));

	if ($allowWrite)
	{
		//region Add letter & Add to segment
		Integration\Sender\GridPanel::appendActions($actionList, $applyButton, $gridManagerID);
		//endregion
		//region Add Task
		if (IsModuleInstalled('tasks'))
		{
			$actionList[] = array(
				'NAME' => Loc::getMessage('CRM_LEAD_TASK'),
				'VALUE' => 'tasks',
				'ONCHANGE' => array(
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
						'DATA' => array($applyButton)
					),
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'tasks')"))
					)
				)
			);
		}
		//endregion
		//region Set Status
		$statusList = array(array('NAME' => Loc::getMessage('CRM_STATUS_INIT_MSGVER_1'), 'VALUE' => ''));
		foreach($arResult['STATUS_LIST_WRITE'] as $statusID => $statusName)
		{
			$statusList[] = array('NAME' => $statusName, 'VALUE' => $statusID);
		}
		$actionList[] = array(
			'NAME' => Loc::getMessage('CRM_LEAD_SET_STATUS_MSGVER_1'),
			'VALUE' => 'set_status',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
							'ID' => 'action_status_id',
							'NAME' => 'ACTION_STATUS_ID',
							'ITEMS' => $statusList
						),
						$applyButton
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'set_status')"))
				)
			)
		);
		//endregion

		//region Assign To
		//region Render User Search control
		if (!Bitrix\Main\Grid\Context::isInternalRequest())
		{
			//action_assigned_by_search + _control
			//Prefix control will be added by main.ui.grid
			$APPLICATION->IncludeComponent(
				'bitrix:intranet.user.selector.new',
				'',
				array(
					'MULTIPLE' => 'N',
					'NAME' => "{$prefix}_ACTION_ASSIGNED_BY",
					'INPUT_NAME' => 'action_assigned_by_search_control',
					'SHOW_EXTRANET_USERS' => 'NONE',
					'POPUP' => 'Y',
					'SITE_ID' => SITE_ID,
					'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE'] ?? ''
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);
		}
		//endregion
		$actionList[] = array(
			'NAME' => Loc::getMessage('CRM_LEAD_ASSIGN_TO'),
			'VALUE' => 'assign_to',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::TEXT,
							'ID' => 'action_assigned_by_search',
							'NAME' => 'ACTION_ASSIGNED_BY_SEARCH'
						),
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::HIDDEN,
							'ID' => 'action_assigned_by_id',
							'NAME' => 'ACTION_ASSIGNED_BY_ID'
						),
						$applyButton
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(
						array('JS' => "BX.CrmUIGridExtension.prepareAction('{$gridManagerID}', 'assign_to',  { searchInputId: 'action_assigned_by_search_control', dataInputId: 'action_assigned_by_id_control', componentName: '{$prefix}_ACTION_ASSIGNED_BY' })")
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'assign_to')"))
				)
			)
		);
		//endregion

		//region Convert
		if ($arResult['CAN_CONVERT'])
		{
			$schemeList = [];
			foreach(LeadConversionScheme::getJavaScriptDescriptions(true) as $schemeName => $schemeDescr)
			{
				$schemeList[] = array('NAME' => $schemeDescr, 'VALUE' => $schemeName);
			}

			if (!empty($schemeList))
			{
				$actionList[] = array(
					'NAME' => Loc::getMessage('CRM_LEAD_CONVERT'),
					'VALUE' => 'convert',
					'ONCHANGE' => array(
						array(
							'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
							'DATA' => array(
								array(
									'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
									'ID' => 'conversion_scheme_id',
									'NAME' => 'CONVERSION_SCHEME_ID',
									'ITEMS' => $schemeList
								),
								$applyButton
							)
						),
						array(
							'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
							'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'convert')"))
						)
					)
				);
			}
		}
		//endregion

		//region Create call list
		if (IsModuleInstalled('voximplant'))
		{
			$actionList[] = array(
				'NAME' => Loc::getMessage('CRM_LEAD_CREATE_CALL_LIST'),
				'VALUE' => 'create_call_list',
				'ONCHANGE' => array(
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
						'DATA' => array(
							$applyButton
						)
					),
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'create_call_list')"))
					)
				)
			);
		}
		//endregion

		if ($allowDelete && !$arResult['IS_EXTERNAL_FILTER'])
		{
			$actionList[] = [
				'NAME' => Loc::getMessage('CRM_LEAD_ACTION_MERGE'),
				'VALUE' => 'merge',
				'ONCHANGE' => [
					[
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
						'DATA' => [
							array_merge(
								$applyButton,
								['SETTINGS' => [
									'minSelectedRows' => 2,
									'buttonId' => 'apply_button'
								]]
							)
						]
					],
					[
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => [['JS' => "BX.CrmUIGridExtension.applyAction('{$gridManagerID}', 'merge')"]]
					]
				]
			];
		}
	}

	if ($allowDelete)
	{
		//region Remove button
		//$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getRemoveButton();
		$button = $snippet->getRemoveButton();
		$snippet->setButtonActions(
			$button,
			array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'CONFIRM' => false,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.applyAction('{$gridManagerID}', 'delete')"))
				)
			)
		);
		$controlPanel['GROUPS'][0]['ITEMS'][] = $button;
		//endregion

		//$actionList[] = $snippet->getRemoveAction();
		$actionList[] = array(
			'NAME' => Loc::getMessage('CRM_LEAD_ACTION_DELETE'),
			'VALUE' => 'delete',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::RESET_CONTROLS,
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.applyAction('{$gridManagerID}', 'delete')"))
				)
			)
		);
	}

	if ($allowExclude)
	{
		$actionList[] = array(
			'NAME' => Loc::getMessage('CRM_LEAD_EXCLUDE'),
			'VALUE' => 'exclude',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array($applyButton)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'exclude')"))
				)
			)
		);
	}

	if ($allowWrite)
	{
		//region Edit Button
		$actionManager = new ActionManager($gridManagerID);
		$controlPanel['GROUPS'][0]['ITEMS'][] = $actionManager->getEditButton();
		$actionList[] = $actionManager->getEditAction();
		//endregion

		//region Mark as Opened
		$actionList[] = array(
			'NAME' => Loc::getMessage('CRM_LEAD_MARK_AS_OPENED'),
			'VALUE' => 'mark_as_opened',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
							'ID' => 'action_opened',
							'NAME' => 'ACTION_OPENED',
							'ITEMS' => $yesnoList
						),
						$applyButton
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'mark_as_opened')"))
				)
			)
		);
		//endregion
		//region Refresh Accounting Data
		$actionList[] = array(
			'NAME' => Loc::getMessage('CRM_LEAD_REFRESH_ACCOUNT'),
			'VALUE' => 'refresh_account',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array($applyButton)
				)
			)

		);
		//endregion
	}

	if ($callListUpdateMode)
	{
		$callListContext = \CUtil::jsEscape($arResult['CALL_LIST_CONTEXT']);
		$controlPanel['GROUPS'][0]['ITEMS'][] = [
			"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
			"TEXT" => Loc::getMessage("CRM_LEAD_UPDATE_CALL_LIST"),
			"ID" => "update_call_list",
			"NAME" => "update_call_list",
			'ONCHANGE' => [
				[
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => [['JS' => "BX.CrmUIGridExtension.updateCallList('{$gridManagerID}', {$arResult['CALL_LIST_ID']}, '{$callListContext}')"]]
				]
			]
		];
	}
	else
	{
		//region Start call list
		if (IsModuleInstalled('voximplant'))
		{
			$controlPanel['GROUPS'][0]['ITEMS'][] = array(
				"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
				"TEXT" => Loc::getMessage('CRM_LEAD_START_CALL_LIST'),
				"VALUE" => "start_call_list",
				"ONCHANGE" => array(
					array(
						"ACTION" => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						"DATA" => array(array('JS' => "BX.CrmUIGridExtension.createCallList('{$gridManagerID}', true)"))
					)
				)
			);
		}
		//endregion
		$controlPanel['GROUPS'][0]['ITEMS'][] = array(
			"TYPE" => \Bitrix\Main\Grid\Panel\Types::DROPDOWN,
			"ID" => "action_button_{$prefix}",
			"NAME" => "action_button_{$prefix}",
			"ITEMS" => $actionList
		);
	}

	$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getForAllCheckbox();
}
//endregion

if ($arResult['ENABLE_TOOLBAR'])
{
	$addButton =array(
		'TEXT' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Lead),
		'TITLE' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Lead),
		'LINK' => $arResult['PATH_TO_LEAD_ADD'],
		'ICON' => 'btn-new'
	);

	if (!empty($arResult['ADD_EVENT_NAME']))
	{
		$addButton['ONCLICK'] = "BX.onCustomEvent(window, '{$arResult['ADD_EVENT_NAME']}')";
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		array(
			'TOOLBAR_ID' => mb_strtolower($arResult['GRID_ID']).'_toolbar',
			'BUTTONS' => array($addButton)
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}

$messages = [];
if (isset($arResult['ERRORS']) && is_array($arResult['ERRORS']))
{
	foreach($arResult['ERRORS'] as $error)
	{
		$messages[] = array(
			'TYPE' => \Bitrix\Main\Grid\MessageType::ERROR,
			'TITLE' => $error['TITLE'],
			'TEXT' => $error['TEXT']
		);
	}
}
if (isset($arResult['MESSAGES']) && is_array($arResult['MESSAGES']))
{
	foreach($arResult['MESSAGES'] as $message)
	{
		$messages[] = array(
			'TYPE' => \Bitrix\Main\Grid\MessageType::MESSAGE,
			'TITLE' => $message['TITLE'],
			'TEXT' => $message['TEXT']
		);
	}
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.newentity.counter.panel',
	'',
	array(
		'ENTITY_TYPE_NAME' => CCrmOwnerType::LeadName,
		'GRID_ID' => $arResult['GRID_ID']
	),
	$component
);

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'titleflex',
	[
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'HEADERS_SECTIONS' => $arResult['HEADERS_SECTIONS'],
		'ENABLE_FIELDS_SEARCH' => 'Y',
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FORM_ID' => $arResult['FORM_ID'] ?? null,
		'TAB_ID' => $arResult['TAB_ID'] ?? null,
		'AJAX_ID' => $arResult['AJAX_ID'] ?? null,
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_HISTORY' => 'N',
		'HIDE_FILTER' => ($arParams['HIDE_FILTER'] ?? null),
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'FILTER_PARAMS' => [
			'LAZY_LOAD' => [
				'CONTROLLER' => [
					'getList' => 'crm.api.filter.lead.getlist',
					'getField' => 'crm.api.filter.lead.getfield',
					'getFields' => 'crm.api.filter.lead.getfields',
				],
			],
			'ENABLE_FIELDS_SEARCH' => 'Y',
			'HEADERS_SECTIONS' => $arResult['HEADERS_SECTIONS'],
			'CONFIG' => [
				'popupColumnsCount' => 4,
				'popupWidth' => 800,
				'showPopupInCenter' => true,
			],
			'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => (bool)(
				$arParams['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] ?? \Bitrix\Main\ModuleManager::isModuleInstalled('ui')
			),
			'RESTRICTED_FIELDS' => $arResult['RESTRICTED_FIELDS'] ?? [],
		],
		'LIVE_SEARCH_LIMIT_INFO' => ($arResult['LIVE_SEARCH_LIMIT_INFO'] ?? null),
		'ENABLE_LIVE_SEARCH' => true,
		'ACTION_PANEL' => $controlPanel,
		'PAGINATION' => (
			isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
				? $arResult['PAGINATION']
				: []
		),
		'ENABLE_ROW_COUNT_LOADER' => true,
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'MESSAGES' => $messages,
		'DISABLE_NAVIGATION_BAR' => $arResult['DISABLE_NAVIGATION_BAR'],
		'NAVIGATION_BAR' => (new NavigationBarPanel(CCrmOwnerType::Lead))
			->setItems([
				NavigationBarPanel::ID_KANBAN,
				NavigationBarPanel::ID_LIST,
				NavigationBarPanel::ID_ACTIVITY,
				NavigationBarPanel::ID_CALENDAR,
				NavigationBarPanel::ID_AUTOMATION
			], NavigationBarPanel::ID_LIST)
			->setBinding($arResult['NAVIGATION_CONTEXT_ID'])
			->get(),
		'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
		'EXTENSION' => [
			'ID' => $gridManagerID,
			'CONFIG' => [
				'ownerTypeName' => CCrmOwnerType::LeadName,
				'gridId' => $arResult['GRID_ID'],
				'activityEditorId' => $activityEditorID,
				'activityServiceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'taskCreateUrl'=> ($arResult['TASK_CREATE_URL'] ?? ''),
				'serviceUrl' => '/bitrix/components/bitrix/crm.lead.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'loaderData' => ($arParams['AJAX_LOADER'] ?? null),
			],
			'MESSAGES' => [
				'deletionDialogTitle' => Loc::getMessage('CRM_LEAD_DELETE_TITLE'),
				'deletionDialogMessage' => Loc::getMessage('CRM_LEAD_DELETE_CONFIRM'),
				'deletionDialogButtonTitle' => Loc::getMessage('CRM_LEAD_DELETE'),
				'exclusionDialogTitle' => Loc::getMessage('CRM_LEAD_EXCLUDE_TITLE'),
				'exclusionDialogMessage' => Loc::getMessage('CRM_LEAD_EXCLUDE_CONFIRM'),
				'exclusionDialogMessageHelp' => Loc::getMessage('CRM_LEAD_EXCLUDE_CONFIRM_HELP'),
				'exclusionDialogButtonTitle' => Loc::getMessage('CRM_LEAD_EXCLUDE'),
			],
		],
	],
	$component
);
?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmLongRunningProcessDialog.messages =
			{
				startButton: "<?=GetMessageJS('CRM_LEAD_LRP_DLG_BTN_START')?>",
				stopButton: "<?=GetMessageJS('CRM_LEAD_LRP_DLG_BTN_STOP')?>",
				closeButton: "<?=GetMessageJS('CRM_LEAD_LRP_DLG_BTN_CLOSE')?>",
				wait: "<?=GetMessageJS('CRM_LEAD_LRP_DLG_WAIT')?>",
				requestError: "<?=GetMessageJS('CRM_LEAD_LRP_DLG_REQUEST_ERR')?>"
			};

			BX.Crm.PartialEditorDialog.registerEntityEditorUrl(
				"<?=CCrmOwnerType::LeadName?>",
				"<?='/bitrix/components/bitrix/crm.lead.details/ajax.php?'.bitrix_sessid_get()?>"
			);

			BX.Crm.PartialEditorDialog.messages =
			{
				entityHasInaccessibleFields: "<?= CUtil::JSEscape(Loc::getMessage('CRM_LEAD_HAS_INACCESSIBLE_FIELDS')) ?>",
			};

			var gridId = "<?= CUtil::JSEscape($arResult['GRID_ID'])?>";
			BX.Crm.BatchDeletionManager.create(
				gridId,
				{
					gridId: gridId,
					entityTypeId: <?=CCrmOwnerType::Lead?>,
					container: "batchDeletionWrapper",
					stateTemplate: "<?=GetMessageJS('CRM_LEAD_STEPWISE_STATE_TEMPLATE')?>",
					messages:
					{
						title: "<?=GetMessageJS('CRM_LEAD_LIST_DEL_PROC_DLG_TITLE')?>",
						confirmation: "<?=GetMessageJS('CRM_LEAD_LIST_DEL_PROC_DLG_SUMMARY')?>",
						summaryCaption: "<?=GetMessageJS('CRM_LEAD_BATCH_DELETION_COMPLETED')?>",
						summarySucceeded: "<?=GetMessageJS('CRM_LEAD_BATCH_DELETION_COUNT_SUCCEEDED')?>",
						summaryFailed: "<?=GetMessageJS('CRM_LEAD_BATCH_DELETION_COUNT_FAILED')?>"
					}
				}
			);

			BX.Crm.BatchMergeManager.create(
				gridId,
				{
					gridId: gridId,
					entityTypeId: <?=CCrmOwnerType::Lead?>,
					mergerUrl: "<?=\CUtil::JSEscape($arParams['PATH_TO_LEAD_MERGE'])?>"
				}
			);
		}
	);
</script>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmSipManager.getCurrent().setServiceUrl(
				"CRM_<?=CUtil::JSEscape(CCrmOwnerType::LeadName)?>",
				"/bitrix/components/bitrix/crm.lead.show/ajax.php?<?=bitrix_sessid_get()?>"
			);

			if (typeof(BX.CrmSipManager.messages) === 'undefined')
			{
				BX.CrmSipManager.messages =
				{
					"unknownRecipient": "<?= GetMessageJS('CRM_SIP_MGR_UNKNOWN_RECIPIENT')?>",
					"makeCall": "<?= GetMessageJS('CRM_SIP_MGR_MAKE_CALL')?>"
				};
			}
		}
	);
</script>
<?php if (
	!$isInternal
	&& \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('IFRAME') !== 'Y'
):
	\Bitrix\Main\UI\Extension::load(['crm.settings-button-extender', 'crm.toolbar-component']);
	?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			const settingsButton = BX.Crm.ToolbarComponent.Instance.getSettingsButton();
			const settingsMenu = settingsButton ? settingsButton.getMenuWindow() : undefined;
			if (settingsMenu)
			{
				new BX.Crm.SettingsButtonExtender({
					smartActivityNotificationSupported: <?= Container::getInstance()->getFactory(\CCrmOwnerType::Lead)->isSmartActivityNotificationSupported() ? 'true' : 'false' ?>,
					entityTypeId: <?= \CCrmOwnerType::Lead ?>,
					categoryId: <?= isset($arResult['CATEGORY_ID']) ? (int)$arResult['CATEGORY_ID'] : 'null' ?>,
					pingSettings: <?= \CUtil::PhpToJSObject((new TodoPingSettingsProvider(\CCrmOwnerType::Lead))->fetchAll()) ?>,
					rootMenu: settingsMenu,
					grid: BX.Reflection.getClass('BX.Main.gridManager') ? BX.Main.gridManager.getInstanceById('<?= \CUtil::JSEscape($arResult['GRID_ID']) ?>') : undefined,
					<?php if (
						\Bitrix\Crm\Integration\AI\AIManager::isAiCallAutomaticProcessingAllowed()
						&& in_array(\CCrmOwnerType::Lead, \Bitrix\Crm\Integration\AI\AIManager::SUPPORTED_ENTITY_TYPE_IDS, true)
						&& Container::getInstance()->getUserPermissions()->isAdmin()
					): ?>
					aiAutostartSettings: '<?= \Bitrix\Main\Web\Json::encode(\Bitrix\Crm\Integration\AI\Operation\AutostartSettings::get(
						\CCrmOwnerType::Lead,
						isset($arResult['CATEGORY_ID']) ? (int)$arResult['CATEGORY_ID'] : null,
					)) ?>',
					<?php endif; ?>
				});
			}
		}
	);
</script>
<?php endif; ?>

<?if (!$isInternal):?>
<script type="text/javascript">
	BX.ready(
			function()
			{
				BX.CrmActivityEditor.items['<?= CUtil::JSEscape($activityEditorID)?>'].addActivityChangeHandler(
						function()
						{
							BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
						}
				);
				BX.namespace('BX.Crm.Activity');
				if(typeof BX.Crm.Activity.Planner !== 'undefined')
				{
					BX.Crm.Activity.Planner.Manager.setCallback('onAfterActivitySave', function()
					{
						BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
					});
				}
			}
	);
</script>
<?php endif;

if ($arResult['CAN_CONVERT']):
	\Bitrix\Main\UI\Extension::load('crm.conversion');
	?><script type="text/javascript">
	BX.ready(
		function()
		{
			<?php foreach (\Bitrix\Crm\Conversion\LeadConversionDispatcher::getAllConfigurations() as $conversionConfig): ?>
			BX.Crm.Conversion.Manager.Instance.initializeConverter(
				BX.CrmEntityType.enumeration.lead,
				{
					configItems: <?= CUtil::PhpToJSObject($conversionConfig->toJson()) ?>,
					scheme: <?= CUtil::PhpToJSObject($conversionConfig->getScheme()->toJson(true)) ?>,
					params: {
						id: '<?= \CUtil::JSEscape($arResult['CONVERTER_ID_PREFIX'] . '_' . $conversionConfig->getTypeID()) ?>',
						serviceUrl: "<?='/bitrix/components/bitrix/crm.lead.show/ajax.php?action=convert&'.bitrix_sessid_get()?>",
						messages: {
							accessDenied: "<?=GetMessageJS("CRM_LEAD_CONV_ACCESS_DENIED")?>",
							generalError: "<?=GetMessageJS("CRM_LEAD_CONV_GENERAL_ERROR")?>",
							dialogTitle: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_TITLE")?>",
							syncEditorLegend: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_SYNC_LEGEND")?>",
							syncEditorFieldListTitle: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_SYNC_FILED_LIST_TITLE")?>",
							syncEditorEntityListTitle: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE")?>",
							continueButton: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_CONTINUE_BTN")?>",
							cancelButton: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_CANCEL_BTN")?>",

							selectButton: "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_BTN")?>",
							openEntitySelector: "<?=GetMessageJS("CRM_LEAD_CONV_OPEN_ENTITY_SEL")?>",
							entitySelectorTitle: "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_TITLE")?>",
							contact: "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_CONTACT")?>",
							company: "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_COMPANY")?>",
							noresult: "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_SEARCH_NO_RESULT")?>",
							search : "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_SEARCH")?>",
							last : "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_LAST")?>",
						},
						analytics: {
							c_sub_section: '<?= Integration\Analytics\Dictionary::SUB_SECTION_LIST ?>',
						},
					}
				},
			);
			<?php endforeach; ?>

			BX.CrmEntityType.setCaptions(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetJavascriptDescriptions())?>);
			BX.CrmEntityType.setNotFoundMessages(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetNotFoundMessages())?>);

			var gridId = "<?= CUtil::JSEscape($arResult['GRID_ID'])?>";
			BX.Crm.BatchConversionManager.create(
				gridId,
				{
					entityTypeId: BX.CrmEntityType.enumeration.lead,
					gridId: gridId,
					serviceUrl: "/bitrix/components/bitrix/crm.lead.list/list.ajax.php?sessid=" + BX.bitrix_sessid(),
					container: "batchConversionWrapper",
					stateTemplate: "<?=GetMessageJS('CRM_LEAD_BATCH_CONVERSION_STATE')?>",
					messages:
					{
						title: "<?=GetMessageJS('CRM_LEAD_BATCH_CONVERSION_TITLE')?>",
						windowCloseConfirm: "<?=GetMessageJS('CRM_LEAD_BATCH_CONVERSION_DLG_CLOSE_CONFIRMATION')?>",
						summaryCaption: "<?=GetMessageJS('CRM_LEAD_BATCH_CONVERSION_COMPLETED')?>",
						summarySucceeded: "<?=GetMessageJS('CRM_LEAD_BATCH_CONVERSION_COUNT_SUCCEEDED')?>",
						summaryFailed: "<?=GetMessageJS('CRM_LEAD_BATCH_CONVERSION_COUNT_FAILED')?>"
					},
					analytics: {
						c_section: '<?= \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_LEAD ?>',
						c_sub_section: '<?= \Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_LIST ?>',
						c_element: '<?= \Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_GRID_GROUP_ACTIONS ?>',
					}
				}
			);
		}
	);
</script>
<?endif;?>

<?if (isset($arResult['NEED_FOR_REBUILD_DUP_INDEX']) && $arResult['NEED_FOR_REBUILD_DUP_INDEX']):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmDuplicateManager.messages =
			{
				rebuildLeadIndexDlgTitle: "<?=GetMessageJS('CRM_LEAD_REBUILD_DUP_INDEX_DLG_TITLE')?>",
				rebuildLeadIndexDlgSummary: "<?=GetMessageJS('CRM_LEAD_REBUILD_DUP_INDEX_DLG_SUMMARY')?>"
			};

			var mgr = BX.CrmDuplicateManager.create("mgr", { entityTypeName: "<?=CUtil::JSEscape(CCrmOwnerType::LeadName)?>", serviceUrl: "<?=SITE_DIR?>bitrix/components/bitrix/crm.lead.list/list.ajax.php?&<?=bitrix_sessid_get()?>" });
			BX.addCustomEvent(
				mgr,
				'ON_LEAD_INDEX_REBUILD_COMPLETE',
				function()
				{
					var msg = BX("rebuildLeadDupIndexMsg");
					if (msg)
					{
						msg.style.display = "none";
					}
				}
			);

			var link = BX("rebuildLeadDupIndexLink");
			if (link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						mgr.rebuildIndex();
						return BX.PreventDefault(e);
					}
				);
			}
		}
	);
</script>
<?endif;?>

<?if (isset($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']) && $arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("rebuildLeadSearch"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_LEAD_REBUILD_SEARCH_CONTENT_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_REBUILD_SEARCH_CONTENT_STATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("rebuildLeadSearch",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.lead.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SEARCH_CONTENT",
						container: "rebuildLeadSearchWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>

<?if (isset($arResult['NEED_FOR_REBUILD_TIMELINE_SEARCH_CONTENT']) && $arResult['NEED_FOR_REBUILD_TIMELINE_SEARCH_CONTENT']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if (BX.AutorunProcessPanel.isExists("rebuildTimelineSearch"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_TIMELINE_REBUILD_SEARCH_CONTENT_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_REBUILD_SEARCH_CONTENT_STATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("rebuildTimelineSearch",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.timeline/ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_TIMELINE_SEARCH_CONTENT",
						container: "buildTimelineSearchWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>

<?if (isset($arResult['NEED_FOR_REBUILD_SECURITY_ATTRS']) && $arResult['NEED_FOR_REBUILD_SECURITY_ATTRS']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.AutorunProcessManager.createIfNotExists(
					"rebuildLeadSecurityAttrs",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.lead.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SECURITY_ATTRS",
						container: "rebuildLeadSecurityAttrsWrapper",
						title: "<?=GetMessageJS('CRM_LEAD_REBUILD_SECURITY_ATTRS_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_LEAD_STEPWISE_STATE_TEMPLATE')?>",
						enableLayout: true
					}
				).runAfter(100);
			}
		);
	</script>
<?endif;?>

<?if (isset($arResult['NEED_FOR_BUILD_TIMELINE']) && $arResult['NEED_FOR_BUILD_TIMELINE']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if (BX.AutorunProcessPanel.isExists("buildLeadTimeline"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_LEAD_BUILD_TIMELINE_DLG_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_LEAD_BUILD_TIMELINE_STATE')?>"
				};
				var manager = BX.AutorunProcessManager.create("buildLeadTimeline",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.lead.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "BUILD_TIMELINE",
						container: "buildLeadTimelineWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>

<?if (isset($arResult['NEED_FOR_REFRESH_ACCOUNTING']) && $arResult['NEED_FOR_REFRESH_ACCOUNTING']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if (BX.AutorunProcessPanel.isExists("refreshLeadAccounting"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_LEAD_REFRESH_ACCOUNTING_DLG_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_LEAD_STEPWISE_STATE_TEMPLATE')?>"
				};
				var manager = BX.AutorunProcessManager.create("refreshLeadAccounting",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.lead.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REFRESH_ACCOUNTING",
						container: "refreshLeadAccountingWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>

<?if (isset($arResult['NEED_FOR_REBUILD_LEAD_SEMANTICS']) && $arResult['NEED_FOR_REBUILD_LEAD_SEMANTICS']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if (BX.AutorunProcessPanel.isExists("rebuildLeadSemantics"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_LEAD_REBUILD_SEMANTICS_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_LEAD_STEPWISE_STATE_TEMPLATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("rebuildLeadSemantics",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.lead.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SEMANTICS",
						container: "rebuildLeadSemanticsWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>

<?if (isset($arResult['NEED_FOR_REBUILD_LEAD_ATTRS']) && $arResult['NEED_FOR_REBUILD_LEAD_ATTRS']):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var link = BX("rebuildLeadAttrsLink");
			if (link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						var msg = BX("rebuildLeadAttrsMsg");
						if (msg)
						{
							msg.style.display = "none";
						}
					}
				);
			}
		}
	);
</script>
<?endif;

if (isset($arResult['NEED_TO_SHOW_DUP_INDEX_PROCESS']) && $arResult['NEED_TO_SHOW_DUP_INDEX_PROCESS'])
{?>
	<script type="text/javascript">
		BX.ready(function () {
			if (BX.AutorunProcessPanel.isExists("backgroundLeadIndexRebuild"))
			{
				return;
			}
			BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_LEAD_BACKGROUND_DUPLICATE_INDEX_REBUILD_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_LEAD_BACKGROUND_DUPLICATE_INDEX_REBUILD_STATE')?>"
				};
			var manager = BX.AutorunProcessManager.create(
				"backgroundLeadIndexRebuild",
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.lead.list/list.ajax.php?'.bitrix_sessid_get()?>",
					actionName: "BACKGROUND_INDEX_REBUILD",
					container: "backgroundLeadIndexRebuildWrapper",
					enableLayout: true
				}
			);
			manager.runAfter(100);
		});
	</script><?
}

if (isset($arResult['NEED_TO_SHOW_DUP_MERGE_PROCESS']) && $arResult['NEED_TO_SHOW_DUP_MERGE_PROCESS'])
{?>
	<script type="text/javascript">
		BX.ready(function () {
			if (BX.AutorunProcessPanel.isExists("backgroundLeadMerge"))
			{
				return;
			}
			BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_LEAD_BACKGROUND_DUPLICATE_MERGE_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_LEAD_BACKGROUND_DUPLICATE_MERGE_STATE')?>"
				};
			var manager = BX.AutorunProcessManager.create(
				"backgroundLeadMerge",
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.lead.list/list.ajax.php?'.bitrix_sessid_get()?>",
					actionName: "BACKGROUND_MERGE",
					container: "backgroundLeadMergeWrapper",
					enableLayout: true
				}
			);
			manager.runAfter(100);
		});
	</script><?
}

if (isset($arResult['NEED_TO_SHOW_DUP_VOL_DATA_PREPARE']) && $arResult['NEED_TO_SHOW_DUP_VOL_DATA_PREPARE'])
{?>
	<script type="text/javascript">
		BX.ready(function () {
			if (BX.AutorunProcessPanel.isExists("backgroundLeadIndexRebuild"))
			{
				return;
			}
			BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_LEAD_BACKGROUND_DUPLICATE_VOL_DATA_PREPARE_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_LEAD_BACKGROUND_DUPLICATE_VOL_DATA_PREPARE_STATE')?>"
				};
			var manager = BX.AutorunProcessManager.create(
				"backgroundLeadDupVolDataPrepare",
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.lead.list/list.ajax.php?'.bitrix_sessid_get()?>",
					actionName: "BACKGROUND_DUP_VOL_DATA_PREPARE",
					container: "backgroundLeadDupVolDataPrepareWrapper",
					enableLayout: true
				}
			);
			manager.runAfter(100);
		});
	</script><?
}

if (!empty($arResult['RESTRICTED_FIELDS_ENGINE']))
{
	Bitrix\Main\UI\Extension::load(['crm.restriction.filter-fields']);

	echo $arResult['RESTRICTED_FIELDS_ENGINE'];
}
