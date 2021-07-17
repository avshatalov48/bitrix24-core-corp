<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

use Bitrix\Crm\Integration;
use Bitrix\Crm\Tracking;

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable())
{
	CBitrix24::initLicenseInfoPopupJS();
}
Bitrix\Main\UI\Extension::load(['crm.merger.batchmergemanager']);

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/progress_control.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/analytics.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/batch_conversion.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/batch_deletion.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/partial_entity_editor.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/dialog.js');

Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/autorun_proc.css');

use \Bitrix\Crm\Conversion\LeadConversionScheme;
use \Bitrix\Crm\Category\DealCategory;
use \Bitrix\Crm\Conversion\EntityConverter;
use Bitrix\Main\Localization\Loc;

?><div id="batchConversionWrapper"></div><?
?><div id="batchDeletionWrapper"></div><?

?><div id="rebuildMessageWrapper"><?
if($arResult['NEED_FOR_REBUILD_DUP_INDEX']):
	?><div id="rebuildLeadDupIndexMsg" class="crm-view-message">
		<?=GetMessage('CRM_LEAD_REBUILD_DUP_INDEX', array('#ID#' => 'rebuildLeadDupIndexLink', '#URL#' => '#'))?>
	</div><?
endif;

if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'])
{
	?><div id="rebuildLeadSearchWrapper"></div><?
}
if($arResult['NEED_FOR_REBUILD_TIMELINE_SEARCH_CONTENT'])
{
	?><div id="buildTimelineSearchWrapper"></div><?
}
if($arResult['NEED_FOR_BUILD_TIMELINE'])
{
	?><div id="buildLeadTimelineWrapper"></div><?
}
if($arResult['NEED_FOR_REFRESH_ACCOUNTING'])
{
	?><div id="refreshLeadAccountingWrapper"></div><?
}
if($arResult['NEED_FOR_REBUILD_LEAD_SEMANTICS'])
{
	?><div id="rebuildLeadSemanticsWrapper"></div><?
}
if($arResult['NEED_FOR_REBUILD_LEAD_ATTRS']):
	?><div id="rebuildLeadAttrsMsg" class="crm-view-message">
	<?=GetMessage('CRM_LEAD_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildLeadAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
	</div><?
endif;
?></div><?

if(isset($arResult['ERROR_HTML'])):
	ShowError($arResult['ERROR_HTML']);
endif;

$isInternal = $arResult['INTERNAL'];
$callListUpdateMode = $arResult['CALL_LIST_UPDATE_MODE'];
$allowWrite = $arResult['PERMS']['WRITE'] && !$callListUpdateMode;
$allowDelete = $arResult['PERMS']['DELETE'] && !$callListUpdateMode;
$allowExclude = $arResult['CAN_EXCLUDE'];
$currentUserID = $arResult['CURRENT_USER_ID'];
$activityEditorID = '';
if(!$isInternal):
	$activityEditorID = "{$arResult['GRID_ID']}_activity_editor";
	$APPLICATION->IncludeComponent(
		'bitrix:crm.activity.editor',
		'',
		array(
			'EDITOR_ID' => $activityEditorID,
			'PREFIX' => $arResult['GRID_ID'],
			'OWNER_TYPE' => 'LEAD',
			'OWNER_ID' => 0,
			'READ_ONLY' => false,
			'ENABLE_UI' => false,
			'ENABLE_TOOLBAR' => false,
			'SKIP_VISUAL_COMPONENTS' => 'Y'
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
endif;

$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$gridManagerCfg = array(
	'ownerType' => 'LEAD',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'activityEditorId' => $activityEditorID,
	'serviceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'listServiceUrl' => '/bitrix/components/bitrix/crm.lead.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'filterFields' => array(),
	'userFilterHash' => $arResult['DB_FILTER_HASH'],
	'enableIterativeDeletion' => true,
	'messages' => array(
		'deletionDialogTitle' => GetMessage('CRM_LEAD_LIST_DEL_PROC_DLG_TITLE'),
		'deletionDialogSummary' => GetMessage('CRM_LEAD_LIST_DEL_PROC_DLG_SUMMARY')
	)
);

echo CCrmViewHelper::RenderLeadStatusSettings();
$prefix = $arResult['GRID_ID'];

$arResult['GRID_DATA'] = array();
$arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
	$arColumns[$arHead['id']] = false;

$now = time() + CTimeZone::GetOffset();

foreach($arResult['LEAD'] as $sKey => $arLead)
{
	$arActivityMenuItems = array();
	$arActivitySubMenuItems = array();
	$arActions = array();

	$arActions[] = array(
		'TITLE' => GetMessage('CRM_LEAD_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_LEAD_SHOW'),
		'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arLead['PATH_TO_LEAD_SHOW'])."')",
		'DEFAULT' => true
	);
	if($arLead['EDIT'])
	{
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_LEAD_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_LEAD_EDIT'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arLead['PATH_TO_LEAD_EDIT'])."')"
		);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_LEAD_COPY_TITLE'),
			'TEXT' => GetMessage('CRM_LEAD_COPY'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arLead['PATH_TO_LEAD_COPY'])."')"
		);
	}

	if(!$isInternal)
	{
		if($arLead['DELETE'])
		{
			$pathToRemove = CUtil::JSEscape($arLead['PATH_TO_LEAD_DELETE']);
			$arActions[] = array(
				'TITLE' => GetMessage('CRM_LEAD_DELETE_TITLE'),
				'TEXT' => GetMessage('CRM_LEAD_DELETE'),
				'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
					'{$gridManagerID}',
					BX.CrmUIGridMenuCommand.remove,
					{ pathToRemove: '{$pathToRemove}' }
				)"
			);
		}
		if($arResult['CAN_EXCLUDE'] && $arLead['CAN_EXCLUDE'])
		{
			$pathToExclude = CUtil::JSEscape($arLead['PATH_TO_LEAD_EXCLUDE']);
			$arActions[] = array(
				'TITLE' => GetMessage('CRM_LEAD_EXCLUDE_TITLE'),
				'TEXT' => GetMessage('CRM_LEAD_EXCLUDE'),
				'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
					'{$gridManagerID}',
					BX.CrmUIGridMenuCommand.exclude,
					{ pathToExclude: '{$pathToExclude}' }
				)"
			);
		}
	}

	$arActions[] = array('SEPARATOR' => true);

	if(!$isInternal)
	{
		if($arResult['CAN_CONVERT'])
		{
			$isReturnCustomer = $arLead['IS_RETURN_CUSTOMER'] == 'Y';
			$arSchemeDescriptions = \Bitrix\Crm\Conversion\LeadConversionDispatcher::getConfiguration(
				array('FIELDS' => $arLead)
			)->getSchemeJavaScriptDescriptions(true);

			$arSchemeList = array();
			foreach($arSchemeDescriptions as $name => $description)
			{
				$arSchemeList[] = array(
					'TITLE' => $description,
					'TEXT' => $description,
					'ONCLICK' => "BX.CrmLeadConverter.getCurrent().convert({$arLead['ID']}, BX.CrmLeadConversionScheme.createConfig('{$name}'), '".CUtil::JSEscape($APPLICATION->GetCurPage())."');"
				);
			}
			if(!empty($arSchemeList))
			{
				if (!$isReturnCustomer)
				{
					$arSchemeList[] = array(
						'TITLE' => GetMessage('CRM_LEAD_CONV_OPEN_ENTITY_SEL'),
						'TEXT' => GetMessage('CRM_LEAD_CONV_OPEN_ENTITY_SEL'),
						'ONCLICK' => "BX.CrmLeadConverter.getCurrent().openEntitySelector(function(result){ BX.CrmLeadConverter.getCurrent().convert({$arLead['ID']}, result.config, '".CUtil::JSEscape($APPLICATION->GetCurPage())."', result.data); });"
					);
				}

				$arActions[] = array('SEPARATOR' => true);
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_LEAD_CREATE_ON_BASIS_TITLE'),
					'TEXT' => GetMessage('CRM_LEAD_CREATE_ON_BASIS'),
					'MENU' => $arSchemeList
				);
			}
		}

		$arActions[] = array('SEPARATOR' => true);
	}

	if(!$isInternal)
	{
		$arActions[] = $arActivityMenuItems[] = array(
			'TITLE' => GetMessage('CRM_LEAD_EVENT_TITLE'),
			'TEXT' => GetMessage('CRM_LEAD_EVENT'),
			'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
				'{$gridManagerID}',
				BX.CrmUIGridMenuCommand.createEvent,
				{ entityTypeName: BX.CrmEntityType.names.lead, entityId: {$arLead['ID']} }
			)"
		);

		if($arLead['EDIT'])
		{
			if(IsModuleInstalled('subscribe'))
			{
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_LEAD_ADD_EMAIL_TITLE'),
					'TEXT' => GetMessage('CRM_LEAD_ADD_EMAIL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.email, settings: { ownerID: {$arLead['ID']} } }
					)"
				);

				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_LEAD_ADD_EMAIL_TITLE'),
					'TEXT' => GetMessage('CRM_LEAD_ADD_EMAIL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.email, settings: { ownerID: {$arLead['ID']} } }
					)"
				);
			}

			if(IsModuleInstalled(CRM_MODULE_CALENDAR_ID))
			{
				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_LEAD_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_LEAD_ADD_CALL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arLead['ID']} } }
					)"
				);

				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_LEAD_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_LEAD_ADD_MEETING'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arLead['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_LEAD_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_LEAD_ADD_CALL_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arLead['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_LEAD_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_LEAD_ADD_MEETING_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arLead['ID']} } }
					)"
				);
			}

			if(IsModuleInstalled('tasks'))
			{
				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_LEAD_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_LEAD_TASK'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
					'{$gridManagerID}',
					BX.CrmUIGridMenuCommand.createActivity,
					{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arLead['ID']} } }
				)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_LEAD_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_LEAD_TASK_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
					'{$gridManagerID}',
					BX.CrmUIGridMenuCommand.createActivity,
					{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arLead['ID']} } }
				)"
				);
			}

			if(!empty($arActivitySubMenuItems))
			{
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_LEAD_ADD_ACTIVITY_TITLE'),
					'TEXT' => GetMessage('CRM_LEAD_ADD_ACTIVITY'),
					'MENU' => $arActivitySubMenuItems
				);
			}

			if(IsModuleInstalled('sale'))
			{
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_LEAD_ADD_QUOTE_TITLE'),
					'TEXT' => GetMessage('CRM_LEAD_ADD_QUOTE'),
					'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arLead['PATH_TO_QUOTE_ADD'])."');"
				);
			}
			if($arResult['IS_BIZPROC_AVAILABLE'])
			{
				$arActions[] = array('SEPARATOR' => true);
				if(isset($arContact['PATH_TO_BIZPROC_LIST']) && $arContact['PATH_TO_BIZPROC_LIST'] !== '')
				{
					$arActions[] = array(
						'TITLE' => GetMessage('CRM_LEAD_BIZPROC_TITLE'),
						'TEXT' => GetMessage('CRM_LEAD_BIZPROC'),
						'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arLead['PATH_TO_BIZPROC_LIST'])."');"
					);
				}
				if(!empty($arLead['BIZPROC_LIST']))
				{
					$arBizprocList = array();
					foreach($arLead['BIZPROC_LIST'] as $arBizproc)
					{
						$arBizprocList[] = array(
							'TITLE' => $arBizproc['DESCRIPTION'],
							'TEXT' => $arBizproc['NAME'],
							'ONCLICK' => isset($arBizproc['ONCLICK']) ?
								$arBizproc['ONCLICK']
								: "jsUtils.Redirect([], '".CUtil::JSEscape($arBizproc['PATH_TO_BIZPROC_START'])."');"
						);
					}
					$arActions[] = array(
						'TITLE' => GetMessage('CRM_LEAD_BIZPROC_LIST_TITLE'),
						'TEXT' => GetMessage('CRM_LEAD_BIZPROC_LIST'),
						'MENU' => $arBizprocList
					);
				}
			}
		}
	}

	$eventParam = array(
		'ID' => $arLead['ID'],
		'CALL_LIST_ID' => $arResult['CALL_LIST_ID'],
		'CALL_LIST_CONTEXT' => $arResult['CALL_LIST_CONTEXT'],
		'GRID_ID' => $arResult['GRID_ID']
	);
	foreach(GetModuleEvents('crm', 'onCrmLeadListItemBuildMenu', true) as $event)
	{
		ExecuteModuleEventEx($event, array('CRM_LEAD_LIST_MENU', $eventParam, &$arActions));
	}

	$resultItem = array(
		'id' => $arLead['ID'],
		'actions' => $arActions,
		'data' => $arLead,
		'editable' => !$arLead['EDIT'] ? $arColumns : true,
		'columns' => array(
			'LEAD_SUMMARY' => CCrmViewHelper::RenderInfo(
				$arLead['PATH_TO_LEAD_SHOW'],
				isset($arLead['TITLE']) ? $arLead['TITLE'] : ('['.$arLead['ID'].']'),
				Tracking\UI\Grid::enrichSourceName(
					\CCrmOwnerType::Lead,
					$arLead['ID'],
					$arLead['LEAD_SOURCE_NAME']
				),
				array(
					'TARGET' => '_top',
					'LEGEND' => $arLead['LEAD_LEGEND']
				)
			),
			'LEAD_CLIENT' => isset($arLead['CLIENT_INFO']) ? CCrmViewHelper::PrepareClientInfo($arLead['CLIENT_INFO']) : '',
			'COMMENTS' => htmlspecialcharsback($arLead['COMMENTS']),
			'ADDRESS' => nl2br($arLead['ADDRESS']),
			'ASSIGNED_BY' => $arLead['~ASSIGNED_BY_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "LEAD_{$arLead['~ID']}_RESPONSIBLE",
						'USER_ID' => $arLead['~ASSIGNED_BY_ID'],
						'USER_NAME'=> $arLead['ASSIGNED_BY'],
						'USER_PROFILE_URL' => $arLead['PATH_TO_USER_PROFILE']
					)
				) : '',
			'STATUS_DESCRIPTION' => nl2br($arLead['STATUS_DESCRIPTION']),
			'SOURCE_DESCRIPTION' => nl2br($arLead['SOURCE_DESCRIPTION']),
			'DATE_CREATE' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arLead['DATE_CREATE']), $now),
			'DATE_MODIFY' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arLead['DATE_MODIFY']), $now),
			'SUM' => $arLead['FORMATTED_OPPORTUNITY'],
			'OPPORTUNITY' => $arLead['~OPPORTUNITY'],
			'CURRENCY_ID' => CCrmCurrency::GetEncodedCurrencyName($arLead['~CURRENCY_ID']),
			'PRODUCT_ID' => isset($arLead['PRODUCT_ROWS']) ? htmlspecialcharsbx(CCrmProductRow::RowsToString($arLead['PRODUCT_ROWS'])) : '',
			'IS_RETURN_CUSTOMER' => isset($arResult['BOOLEAN_VALUES_LIST'][$arLead['IS_RETURN_CUSTOMER']]) ? $arResult['BOOLEAN_VALUES_LIST'][$arLead['IS_RETURN_CUSTOMER']] : $arLead['IS_RETURN_CUSTOMER'],
			'HONORIFIC' => isset($arResult['HONORIFIC'][$arLead['HONORIFIC']]) ? $arResult['HONORIFIC'][$arLead['HONORIFIC']] : '',
			'STATUS_ID' => CCrmViewHelper::RenderLeadStatusControl(
				array(
					'PREFIX' => "{$arResult['GRID_ID']}_PROGRESS_BAR_",
					'ENTITY_ID' => $arLead['~ID'],
					'CURRENT_ID' => $arLead['~STATUS_ID'],
					'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.list/list.ajax.php',
					'CONVERSION_SCHEME' => isset($arResult['CONVERSION']['SCHEMES']) && isset($arResult['CONVERSION']['SCHEMES'][$arLead['CONVERSION_TYPE_ID']])
						? $arResult['CONVERSION']['SCHEMES'][$arLead['CONVERSION_TYPE_ID']] : null,
					'CAN_CONVERT' => $arResult['CAN_CONVERT'],
					'CONVERSION_TYPE_ID' => $arLead['CONVERSION_TYPE_ID'],
					'READ_ONLY' => !(isset($arLead['EDIT']) && $arLead['EDIT'] === true)
				)
			),
			'SOURCE_ID' => $arLead['LEAD_SOURCE_NAME'],
			'WEBFORM_ID' => isset($arResult['WEBFORM_LIST'][$arLead['WEBFORM_ID']]) ? $arResult['WEBFORM_LIST'][$arLead['WEBFORM_ID']] : $arLead['WEBFORM_ID'],
			'CREATED_BY' => $arLead['~CREATED_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "LEAD_{$arLead['~ID']}_CREATOR",
						'USER_ID' => $arLead['~CREATED_BY'],
						'USER_NAME'=> $arLead['CREATED_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arLead['PATH_TO_USER_CREATOR'],
					)
				) : '',
			'MODIFY_BY' => $arLead['~MODIFY_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "LEAD_{$arLead['~ID']}_MODIFIER",
						'USER_ID' => $arLead['~MODIFY_BY'],
						'USER_NAME'=> $arLead['MODIFY_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arLead['PATH_TO_USER_MODIFIER']
					)
				) : '',
		) + CCrmViewHelper::RenderListMultiFields($arLead, "LEAD_{$arLead['ID']}_", array('ENABLE_SIP' => true, 'SIP_PARAMS' => array('ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::LeadName, 'ENTITY_ID' => $arLead['ID']))) + $arResult['LEAD_UF'][$sKey]
	);

	Tracking\UI\Grid::appendRows(
		\CCrmOwnerType::Lead,
		$arLead['ID'],
		$resultItem['columns']
	);

	if(isset($arLead['~BIRTHDATE']))
	{
		$resultItem['columns']['BIRTHDATE'] = '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arLead['~BIRTHDATE'])).'</nobr>';
	}

	$userActivityID = isset($arLead['~ACTIVITY_ID']) ? intval($arLead['~ACTIVITY_ID']) : 0;
	$commonActivityID = isset($arLead['~C_ACTIVITY_ID']) ? intval($arLead['~C_ACTIVITY_ID']) : 0;
	if($userActivityID > 0)
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Lead),
				'ENTITY_ID' => $arLead['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arLead['~ASSIGNED_BY'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ACTIVITY_ID' => $userActivityID,
				'ACTIVITY_SUBJECT' => isset($arLead['~ACTIVITY_SUBJECT']) ? $arLead['~ACTIVITY_SUBJECT'] : '',
				'ACTIVITY_TIME' => isset($arLead['~ACTIVITY_TIME']) ? $arLead['~ACTIVITY_TIME'] : '',
				'ACTIVITY_EXPIRED' => isset($arLead['~ACTIVITY_EXPIRED']) ? $arLead['~ACTIVITY_EXPIRED'] : '',
				'ALLOW_EDIT' => $arLead['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'USE_GRID_EXTENSION' => true
			)
		);

		$counterData = array(
			'CURRENT_USER_ID' => $currentUserID,
			'ENTITY' => $arLead,
			'ACTIVITY' => array(
				'RESPONSIBLE_ID' => $currentUserID,
				'TIME' => isset($arLead['~ACTIVITY_TIME']) ? $arLead['~ACTIVITY_TIME'] : '',
				'IS_CURRENT_DAY' => isset($arLead['~ACTIVITY_IS_CURRENT_DAY']) ? $arLead['~ACTIVITY_IS_CURRENT_DAY'] : false
			)
		);

		if(CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentLeadActivies, $counterData))
		{
			$resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-deal-today');
		}
	}
	elseif($commonActivityID > 0)
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Lead),
				'ENTITY_ID' => $arLead['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arLead['~ASSIGNED_BY'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ACTIVITY_ID' => $commonActivityID,
				'ACTIVITY_SUBJECT' => isset($arLead['~C_ACTIVITY_SUBJECT']) ? $arLead['~C_ACTIVITY_SUBJECT'] : '',
				'ACTIVITY_TIME' => isset($arLead['~C_ACTIVITY_TIME']) ? $arLead['~C_ACTIVITY_TIME'] : '',
				'ACTIVITY_RESPONSIBLE_ID' => isset($arLead['~C_ACTIVITY_RESP_ID']) ? intval($arLead['~C_ACTIVITY_RESP_ID']) : 0,
				'ACTIVITY_RESPONSIBLE_LOGIN' => isset($arLead['~C_ACTIVITY_RESP_LOGIN']) ? $arLead['~C_ACTIVITY_RESP_LOGIN'] : '',
				'ACTIVITY_RESPONSIBLE_NAME' => isset($arLead['~C_ACTIVITY_RESP_NAME']) ? $arLead['~C_ACTIVITY_RESP_NAME'] : '',
				'ACTIVITY_RESPONSIBLE_LAST_NAME' => isset($arLead['~C_ACTIVITY_RESP_LAST_NAME']) ? $arLead['~C_ACTIVITY_RESP_LAST_NAME'] : '',
				'ACTIVITY_RESPONSIBLE_SECOND_NAME' => isset($arLead['~C_ACTIVITY_RESP_SECOND_NAME']) ? $arLead['~C_ACTIVITY_RESP_SECOND_NAME'] : '',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
				'ALLOW_EDIT' => $arLead['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'USE_GRID_EXTENSION' => true
			)
		);
	}
	else
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Lead),
				'ENTITY_ID' => $arLead['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arLead['~ASSIGNED_BY'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ALLOW_EDIT' => $arLead['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'HINT_TEXT' => isset($arLead['~WAITING_TITLE']) ? $arLead['~WAITING_TITLE'] : '',
				'USE_GRID_EXTENSION' => true
			)
		);

		$counterData = array('CURRENT_USER_ID' => $currentUserID, 'ENTITY' => $arLead);
		if($waitingID <= 0
			&& CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentLeadActivies, $counterData)
		)
		{
			$resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-enitity-action-need');
		}
	}

	$arResult['GRID_DATA'][] = &$resultItem;
	unset($resultItem);
}

//region Action Panel
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));

if(!$isInternal
	&& ($allowWrite || $allowDelete || $allowExclude ||  $callListUpdateMode))
{
	$yesnoList = array(
		array('NAME' => GetMessage('MAIN_YES'), 'VALUE' => 'Y'),
		array('NAME' => GetMessage('MAIN_NO'), 'VALUE' => 'N')
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

	$actionList = array(array('NAME' => GetMessage('CRM_LEAD_LIST_CHOOSE_ACTION'), 'VALUE' => 'none'));

	if($allowWrite)
	{
		//region Add letter & Add to segment
		Integration\Sender\GridPanel::appendActions($actionList, $applyButton, $gridManagerID);
		//endregion
		//region Add Task
		if (IsModuleInstalled('tasks'))
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_LEAD_TASK'),
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
		$statusList = array(array('NAME' => GetMessage('CRM_STATUS_INIT'), 'VALUE' => ''));
		foreach($arResult['STATUS_LIST_WRITE'] as $statusID => $statusName)
		{
			$statusList[] = array('NAME' => $statusName, 'VALUE' => $statusID);
		}
		$actionList[] = array(
			'NAME' => GetMessage('CRM_LEAD_SET_STATUS'),
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
		if(!Bitrix\Main\Grid\Context::isInternalRequest())
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
					'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE']
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);
		}
		//endregion
		$actionList[] = array(
			'NAME' => GetMessage('CRM_LEAD_ASSIGN_TO'),
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
		if($arResult['CAN_CONVERT'])
		{
			$schemeList = array();
			foreach(LeadConversionScheme::getJavaScriptDescriptions(true) as $schemeName => $schemeDescr)
			{
				$schemeList[] = array('NAME' => $schemeDescr, 'VALUE' => $schemeName);
			}

			if(!empty($schemeList))
			{
				$actionList[] = array(
					'NAME' => GetMessage('CRM_LEAD_CONVERT'),
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
		if(IsModuleInstalled('voximplant'))
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_LEAD_CREATE_CALL_LIST'),
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

		if($allowDelete)
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_LEAD_ACTION_MERGE'),
				'VALUE' => 'merge',
				'ONCHANGE' => array(
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
						'DATA' => array(
							array_merge(
								$applyButton,
								['SETTINGS' => [
									'minSelectedRows' => 2,
									'buttonId' => 'apply_button'
								]]
							)
						)
					),
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(array('JS' => "BX.CrmUIGridExtension.applyAction('{$gridManagerID}', 'merge')"))
					)
				)
			);
		}
	}

	if($allowDelete)
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
			'NAME' => GetMessage('CRM_LEAD_ACTION_DELETE'),
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

	if($allowExclude)
	{
		$actionList[] = array(
			'NAME' => GetMessage('CRM_LEAD_EXCLUDE'),
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

	if($allowWrite)
	{
		//region Edit Button
		$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getEditButton();
		$actionList[] = $snippet->getEditAction();
		//endregion
		//region Mark as Opened
		$actionList[] = array(
			'NAME' => GetMessage('CRM_LEAD_MARK_AS_OPENED'),
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
			'NAME' => GetMessage('CRM_LEAD_REFRESH_ACCOUNT'),
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

	if($callListUpdateMode)
	{
		$controlPanel['GROUPS'][0]['ITEMS'][] = array(
			"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
			"TEXT" => GetMessage("CRM_LEAD_UPDATE_CALL_LIST"),
			"ID" => "update_call_list",
			"NAME" => "update_call_list",
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.updateCallList('{$gridManagerID}', {$arResult['CALL_LIST_ID']}, '{$arResult['CALL_LIST_CONTEXT']}')"))
				)
			)
		);
	}
	else
	{
		//region Start call list
		if(IsModuleInstalled('voximplant'))
		{
			$controlPanel['GROUPS'][0]['ITEMS'][] = array(
				"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
				"TEXT" => GetMessage('CRM_LEAD_START_CALL_LIST'),
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

$messages = array();
if(isset($arResult['ERRORS']) && is_array($arResult['ERRORS']))
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
if(isset($arResult['MESSAGES']) && is_array($arResult['MESSAGES']))
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
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_ID' => $arResult['AJAX_ID'],
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_HISTORY' => 'N',
		'HIDE_FILTER' => isset($arParams['HIDE_FILTER']) ? $arParams['HIDE_FILTER'] : null,
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'FILTER_PARAMS' => [
			'LAZY_LOAD' => [
				'CONTROLLER' => [
					'getList' => 'crm.api.filter.lead.getlist',
					'getField' => 'crm.api.filter.lead.getfield'
				]
			]
		],
		'LIVE_SEARCH_LIMIT_INFO' => isset($arResult['LIVE_SEARCH_LIMIT_INFO'])
			? $arResult['LIVE_SEARCH_LIMIT_INFO'] : null,
		'ENABLE_LIVE_SEARCH' => true,
		'ACTION_PANEL' => $controlPanel,
		'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
			? $arResult['PAGINATION'] : array(),
		'ENABLE_ROW_COUNT_LOADER' => true,
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'MESSAGES' => $messages,
		'DISABLE_NAVIGATION_BAR' => $arResult['DISABLE_NAVIGATION_BAR'],
		'NAVIGATION_BAR' => array(
			'ITEMS' => array_merge(
				\Bitrix\Crm\Automation\Helper::getNavigationBarItems(\CCrmOwnerType::Lead),
				array(
					array(
						'id' => 'kanban',
						'name' => GetMessage('CRM_LEAD_LIST_FILTER_NAV_BUTTON_KANBAN'),
						'active' => false,
						'url' => $arParams['PATH_TO_LEAD_KANBAN']
					),
					array(
						'id' => 'list',
						'name' => GetMessage('CRM_LEAD_LIST_FILTER_NAV_BUTTON_LIST'),
						'active' => true,
						'url' => $arParams['PATH_TO_LEAD_LIST']
					)
				),
				(\Bitrix\Crm\Integration\Calendar::isResourceBookingEnabled()
					? array(
						array(
							'id' => 'calendar',
							'name' => GetMessage('CRM_LEAD_LIST_FILTER_NAV_BUTTON_CALENDAR'),
							'active' => false,
							'url' => $arParams['PATH_TO_LEAD_CALENDAR']
						)
					)
					: array()
				)
			),
			'BINDING' => array(
				'category' => 'crm.navigation',
				'name' => 'index',
				'key' => mb_strtolower($arResult['NAVIGATION_CONTEXT_ID'])
			)
		),
		'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
		'EXTENSION' => array(
			'ID' => $gridManagerID,
			'CONFIG' => array(
				'ownerTypeName' => CCrmOwnerType::LeadName,
				'gridId' => $arResult['GRID_ID'],
				'activityEditorId' => $activityEditorID,
				'activityServiceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'taskCreateUrl'=> isset($arResult['TASK_CREATE_URL']) ? $arResult['TASK_CREATE_URL'] : '',
				'serviceUrl' => '/bitrix/components/bitrix/crm.lead.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
			),
			'MESSAGES' => array(
				'deletionDialogTitle' => GetMessage('CRM_LEAD_DELETE_TITLE'),
				'deletionDialogMessage' => GetMessage('CRM_LEAD_DELETE_CONFIRM'),
				'deletionDialogButtonTitle' => GetMessage('CRM_LEAD_DELETE'),
				'exclusionDialogTitle' => GetMessage('CRM_LEAD_EXCLUDE_TITLE'),
				'exclusionDialogMessage' => GetMessage('CRM_LEAD_EXCLUDE_CONFIRM'),
				'exclusionDialogMessageHelp' => GetMessage('CRM_LEAD_EXCLUDE_CONFIRM_HELP'),
				'exclusionDialogButtonTitle' => GetMessage('CRM_LEAD_EXCLUDE'),
			)
		)
	),
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

			BX.Crm.AnalyticTracker.config =
				{
					id: "lead_list",
					settings: { params: <?=CUtil::PhpToJSObject($arResult['ANALYTIC_TRACKER'])?> }
				};
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

			if(typeof(BX.CrmSipManager.messages) === 'undefined')
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
<?if(!$isInternal):?>
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
<?endif;?>
<?if($arResult['CAN_CONVERT'] && isset($arResult['CONVERSION']['CONFIGS'])):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmLeadConversionType.configs = <?=CUtil::PhpToJSObject($arResult['CONVERSION']['CONFIGS'])?>;
			BX.CrmLeadConversionScheme.messages =
				<?=CUtil::PhpToJSObject(LeadConversionScheme::getJavaScriptDescriptions(false))?>;
			BX.CrmLeadConverter.messages =
			{
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
				last : "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_LAST")?>"
			};
			BX.CrmLeadConverter.permissions =
			{
				contact: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_CONTACT'])?>,
				company: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_COMPANY'])?>,
				deal: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_DEAL'])?>
			};
			BX.CrmLeadConverter.settings =
			{
				serviceUrl: "<?='/bitrix/components/bitrix/crm.lead.show/ajax.php?action=convert&'.bitrix_sessid_get()?>"
			};
			BX.CrmDealCategory.infos = <?=CUtil::PhpToJSObject(
				DealCategory::getJavaScriptInfos(EntityConverter::getPermittedDealCategoryIDs())
			)?>;
			BX.CrmDealCategorySelectDialog.messages =
			{
				title: "<?=GetMessageJS('CRM_LEAD_LIST_CONV_DEAL_CATEGORY_DLG_TITLE')?>",
				field: "<?=GetMessageJS('CRM_LEAD_LIST_CONV_DEAL_CATEGORY_DLG_FIELD')?>",
				saveButton: "<?=GetMessageJS('CRM_LEAD_LIST_BUTTON_SAVE')?>",
				cancelButton: "<?=GetMessageJS('CRM_LEAD_LIST_BUTTON_CANCEL')?>"
			};
			BX.CrmEntityType.setCaptions(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetJavascriptDescriptions())?>);
			BX.CrmEntityType.setNotFoundMessages(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetNotFoundMessages())?>);

			var gridId = "<?= CUtil::JSEscape($arResult['GRID_ID'])?>";
			BX.Crm.BatchConversionManager.create(
				gridId,
				{
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
					}
				}
			);
		}
	);
</script>
<?endif;?>
<?if($arResult['NEED_FOR_REBUILD_DUP_INDEX']):?>
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
					if(msg)
					{
						msg.style.display = "none";
					}
				}
			);

			var link = BX("rebuildLeadDupIndexLink");
			if(link)
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
<?if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']):?>
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
<?if($arResult['NEED_FOR_REBUILD_TIMELINE_SEARCH_CONTENT']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("rebuildTimelineSearch"))
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
<?if($arResult['NEED_FOR_BUILD_TIMELINE']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("buildLeadTimeline"))
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
<?if($arResult['NEED_FOR_REFRESH_ACCOUNTING']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("refreshLeadAccounting"))
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
<?if($arResult['NEED_FOR_REBUILD_LEAD_SEMANTICS']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("rebuildLeadSemantics"))
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
<?if($arResult['NEED_FOR_REBUILD_LEAD_ATTRS']):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var link = BX("rebuildLeadAttrsLink");
			if(link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						var msg = BX("rebuildLeadAttrsMsg");
						if(msg)
						{
							msg.style.display = "none";
						}
					}
				);
			}
		}
	);
</script>
<?endif;?>
