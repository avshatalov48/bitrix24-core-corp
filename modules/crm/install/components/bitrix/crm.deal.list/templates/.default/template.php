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

use Bitrix\Crm\Tracking;
use Bitrix\Main\Localization\Loc;

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
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/autorun_proc.css');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/batch_deletion.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/partial_entity_editor.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/dialog.js');

?><div id="batchDeletionWrapper"></div>
<?
	echo \Bitrix\Crm\Update\Order\DealGenerator::getHtml();
?>
<div id="rebuildMessageWrapper"><?

if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'])
{
	?><div id="rebuildDealSearchWrapper"></div><?
}
if($arResult['NEED_FOR_REBUILD_TIMELINE_SEARCH_CONTENT'])
{
	?><div id="buildTimelineSearchWrapper"></div><?
}
if($arResult['NEED_FOR_BUILD_TIMELINE'])
{
	?><div id="buildDealTimelineWrapper"></div><?
}
if($arResult['NEED_FOR_REFRESH_ACCOUNTING'])
{
	?><div id="refreshDealAccountingWrapper"></div><?
}
if($arResult['NEED_FOR_REBUILD_DEAL_SEMANTICS'])
{
	?><div id="rebuildDealSemanticsWrapper"></div><?
}
if($arResult['NEED_FOR_REBUILD_SECURITY_ATTRS'])
{
	?><div id="rebuildDealSecurityAttrsWrapper"></div><?
}
if($arResult['NEED_FOR_REBUILD_DEAL_ATTRS'])
{
	?><div id="rebuildDealAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_DEAL_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildDealAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
	</div><?
}
?></div><?
$isRecurring = isset($arParams['IS_RECURRING']) && $arParams['IS_RECURRING'] === 'Y';
$isInternal = $arResult['INTERNAL'];
$callListUpdateMode = $arResult['CALL_LIST_UPDATE_MODE'];
$allowWrite = $arResult['PERMS']['WRITE'];
$allowDelete = $arResult['PERMS']['DELETE'];
$allowExclude = $arResult['CAN_EXCLUDE'];
$currentUserID = $arResult['CURRENT_USER_ID'];
$activityEditorID = '';
if(!$isInternal)
{
	$activityEditorID = "{$arResult['GRID_ID']}_activity_editor";
	$APPLICATION->IncludeComponent(
		'bitrix:crm.activity.editor',
		'',
		array(
			'EDITOR_ID' => $activityEditorID,
			'PREFIX' => $arResult['GRID_ID'],
			'OWNER_TYPE' => 'DEAL',
			'OWNER_ID' => 0,
			'READ_ONLY' => false,
			'ENABLE_UI' => false,
			'ENABLE_TOOLBAR' => false,
			'SKIP_VISUAL_COMPONENTS' => 'Y'
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
}

$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$gridManagerCfg = array(
	'ownerType' => 'DEAL',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'activityEditorId' => $activityEditorID,
	'serviceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'filterFields' => array()
);
echo CCrmViewHelper::RenderDealStageSettings($arParams['CATEGORY_ID']);
$prefix = $arResult['GRID_ID'];
$prefixLC = mb_strtolower($arResult['GRID_ID']);

$arResult['GRID_DATA'] = array();
$arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
	$arColumns[$arHead['id']] = false;

$now = time() + CTimeZone::GetOffset();

foreach($arResult['DEAL'] as $sKey =>  $arDeal)
{
	$jsTitle = isset($arDeal['~TITLE']) ? CUtil::JSEscape($arDeal['~TITLE']) : '';
	$jsShowUrl = isset($arDeal['PATH_TO_DEAL_SHOW']) ? CUtil::JSEscape($arDeal['PATH_TO_DEAL_SHOW']) : '';

	$arActivityMenuItems = array();
	$arActivitySubMenuItems = array();
	$arActions = array();

	$arActions[] = array(
		'TITLE' => GetMessage('CRM_DEAL_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_DEAL_SHOW'),
		'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arDeal['PATH_TO_DEAL_SHOW'])."')",
		'DEFAULT' => true
	);

	if($arDeal['EDIT'])
	{
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_DEAL_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_DEAL_EDIT'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arDeal['PATH_TO_DEAL_EDIT'])."')"
		);

		if ($arParams['IS_RECURRING'] !== 'Y')
		{
			$arActions[] = array(
				'TITLE' => GetMessage('CRM_DEAL_COPY_TITLE'),
				'TEXT' => GetMessage('CRM_DEAL_COPY'),
				'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arDeal['PATH_TO_DEAL_COPY'])."')"
			);
		}
	}

	if(!$isInternal && $arDeal['DELETE'])
	{
		$pathToRemove = CUtil::JSEscape($arDeal['PATH_TO_DEAL_DELETE']);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_DEAL_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_DEAL_DELETE'),
			'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
				'{$gridManagerID}',
				BX.CrmUIGridMenuCommand.remove,
				{ pathToRemove: '{$pathToRemove}' }
			)"
		);
	}

	if($allowExclude && $arDeal['CAN_EXCLUDE'])
	{
		$pathToExclude = CUtil::JSEscape($arDeal['PATH_TO_DEAL_EXCLUDE']);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_DEAL_EXCLUDE_TITLE'),
			'TEXT' => GetMessage('CRM_DEAL_EXCLUDE'),
			'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
					'{$gridManagerID}',
					BX.CrmUIGridMenuCommand.exclude,
					{ pathToExclude: '{$pathToExclude}' }
				)"
		);
	}

	$arActions[] = array('SEPARATOR' => true);

	if(!$isInternal && $arParams['IS_RECURRING'] !== 'Y')
	{
		if($arResult['CAN_CONVERT'])
		{
			if($arResult['CONVERSION_PERMITTED'])
			{
				$arSchemeDescriptions = \Bitrix\Crm\Conversion\DealConversionScheme::getJavaScriptDescriptions(true);
				$arSchemeList = array();
				foreach($arSchemeDescriptions as $name => $description)
				{
					$arSchemeList[] = array(
						'TITLE' => $description,
						'TEXT' => $description,
						'ONCLICK' => "BX.CrmDealConverter.getCurrent().convert({$arDeal['ID']}, BX.CrmDealConversionScheme.createConfig('{$name}'), '".CUtil::JSEscape($APPLICATION->GetCurPage())."');"
					);
				}
				if(!empty($arSchemeList))
				{
					$arActions[] = array('SEPARATOR' => true);
					$arActions[] = array(
						'TITLE' => GetMessage('CRM_DEAL_CREATE_ON_BASIS_TITLE'),
						'TEXT' => GetMessage('CRM_DEAL_CREATE_ON_BASIS'),
						'MENU' => $arSchemeList
					);
				}
			}
			else
			{
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_DEAL_CREATE_ON_BASIS_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_CREATE_ON_BASIS'),
					'ONCLICK' => isset($arResult['CONVERSION_LOCK_SCRIPT']) ? $arResult['CONVERSION_LOCK_SCRIPT'] : ''
				);
			}

			$arActions[] = array('SEPARATOR' => true);
		}

		if($arDeal['EDIT'])
		{
			$arActions[] = $arActivityMenuItems[] = array(
				'TITLE' => GetMessage('CRM_DEAL_EVENT_TITLE'),
				'TEXT' => GetMessage('CRM_DEAL_EVENT'),
				'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
					'{$gridManagerID}',
					BX.CrmUIGridMenuCommand.createEvent,
					{ entityTypeName: BX.CrmEntityType.names.deal, entityId: {$arDeal['ID']} }
				)"
			);

			if(IsModuleInstalled(CRM_MODULE_CALENDAR_ID))
			{
				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_CALL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);

				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_MEETING'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_CALL_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_MEETING_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);
			}

			if(IsModuleInstalled('tasks'))
			{
				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_TASK'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_TASK_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);
			}

			if(!empty($arActivitySubMenuItems))
			{
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_DEAL_ADD_ACTIVITY_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_ACTIVITY'),
					'MENU' => $arActivitySubMenuItems
				);
			}

			if($arResult['IS_BIZPROC_AVAILABLE'])
			{
				$arActions[] = array('SEPARATOR' => true);
				if(isset($arContact['PATH_TO_BIZPROC_LIST']) && $arContact['PATH_TO_BIZPROC_LIST'] !== '')
					$arActions[] = array(
						'TITLE' => GetMessage('CRM_DEAL_BIZPROC_TITLE'),
						'TEXT' => GetMessage('CRM_DEAL_BIZPROC'),
						'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arDeal['PATH_TO_BIZPROC_LIST'])."');"
					);
				if(!empty($arDeal['BIZPROC_LIST']))
				{
					$arBizprocList = array();
					foreach($arDeal['BIZPROC_LIST'] as $arBizproc)
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
						'TITLE' => GetMessage('CRM_DEAL_BIZPROC_LIST_TITLE'),
						'TEXT' => GetMessage('CRM_DEAL_BIZPROC_LIST'),
						'MENU' => $arBizprocList
					);
				}
			}
		}
	}

	$eventParam = array(
		'ID' => $arDeal['ID'],
		'CALL_LIST_ID' => $arResult['CALL_LIST_ID'],
		'CALL_LIST_CONTEXT' => $arResult['CALL_LIST_CONTEXT'],
		'GRID_ID' => $arResult['GRID_ID']
	);
	foreach(GetModuleEvents('crm', 'onCrmDealListItemBuildMenu', true) as $event)
	{
		ExecuteModuleEventEx($event, array('CRM_DEAL_LIST_MENU', $eventParam, &$arActions));
	}

	$resultItem = array(
		'id' => $arDeal['ID'],
		'actions' => $arActions,
		'data' => $arDeal,
		'editable' => !$arDeal['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
		'columns' => array(
			'DEAL_SUMMARY' => CCrmViewHelper::RenderInfo(
				$arDeal['PATH_TO_DEAL_SHOW'],
				isset($arDeal['TITLE']) ? $arDeal['TITLE'] : ('['.$arDeal['ID'].']'),
				Tracking\UI\Grid::enrichSourceName(
					\CCrmOwnerType::Deal,
					$arDeal['ID'],
					$arDeal['DEAL_DESCRIPTION']
				),
				array(
					'TARGET' => '_top',
					'LEGEND' => $arDeal['DEAL_LEGEND']
				)
			),
			'DEAL_CLIENT' => isset($arDeal['CLIENT_INFO']) ? CCrmViewHelper::PrepareClientInfo($arDeal['CLIENT_INFO']) : '',
			'COMPANY_ID' => isset($arDeal['COMPANY_INFO']) ? CCrmViewHelper::PrepareClientInfo($arDeal['COMPANY_INFO']) : '',
			'CONTACT_ID' => isset($arDeal['CONTACT_INFO']) ? CCrmViewHelper::PrepareClientInfo($arDeal['CONTACT_INFO']) : '',
			'TITLE' => '<a target="_self" href="'.$arDeal['PATH_TO_DEAL_SHOW'].'"
				class="'.($arDeal['BIZPROC_STATUS'] != '' ? 'bizproc bizproc_status_'.$arDeal['BIZPROC_STATUS'] : '').'"
				'.($arDeal['BIZPROC_STATUS_HINT'] != '' ? 'onmouseover="BX.hint(this, \''.CUtil::JSEscape($arDeal['BIZPROC_STATUS_HINT']).'\');"' : '').'>'.$arDeal['TITLE'].'</a>',
			'CLOSED' => $arDeal['CLOSED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
			'ASSIGNED_BY' => $arDeal['~ASSIGNED_BY_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "DEAL_{$arDeal['~ID']}_RESPONSIBLE",
						'USER_ID' => $arDeal['~ASSIGNED_BY_ID'],
						'USER_NAME'=> $arDeal['ASSIGNED_BY'],
						'USER_PROFILE_URL' => $arDeal['PATH_TO_USER_PROFILE']
					)
				) : '',
			'COMMENTS' => htmlspecialcharsback($arDeal['COMMENTS']),
			'SUM' => $arDeal['FORMATTED_OPPORTUNITY'],
			'OPPORTUNITY' => $arDeal['OPPORTUNITY'],
			'PROBABILITY' => "{$arDeal['PROBABILITY']}%",
			'DATE_CREATE' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arDeal['DATE_CREATE']), $now),
			'DATE_MODIFY' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arDeal['DATE_MODIFY']), $now),
			'TYPE_ID' => isset($arResult['TYPE_LIST'][$arDeal['TYPE_ID']]) ? $arResult['TYPE_LIST'][$arDeal['TYPE_ID']] : $arDeal['TYPE_ID'],
			'SOURCE_ID' => isset($arResult['SOURCE_LIST'][$arDeal['SOURCE_ID']]) ? $arResult['SOURCE_LIST'][$arDeal['SOURCE_ID']] : $arDeal['SOURCE_ID'],
			'EVENT_ID' => isset($arResult['EVENT_LIST'][$arDeal['EVENT_ID']]) ? $arResult['EVENT_LIST'][$arDeal['EVENT_ID']] : $arDeal['EVENT_ID'],
			'CURRENCY_ID' => CCrmCurrency::GetEncodedCurrencyName	($arDeal['CURRENCY_ID']),
			'PRODUCT_ID' => isset($arDeal['PRODUCT_ROWS']) ? htmlspecialcharsbx(CCrmProductRow::RowsToString($arDeal['PRODUCT_ROWS'])) : '',
			'STATE_ID' => isset($arResult['STATE_LIST'][$arDeal['STATE_ID']]) ? $arResult['STATE_LIST'][$arDeal['STATE_ID']] : $arDeal['STATE_ID'],
			'WEBFORM_ID' => isset($arResult['WEBFORM_LIST'][$arDeal['WEBFORM_ID']]) ? $arResult['WEBFORM_LIST'][$arDeal['WEBFORM_ID']] : $arDeal['WEBFORM_ID'],
			'PAYMENT_STAGE' => isset($arDeal['PAYMENT_STAGE']) ? CCrmViewHelper::RenderDealPaymentStageControl($arDeal['PAYMENT_STAGE']) : '',
			'DELIVERY_STAGE' => CCrmViewHelper::RenderDealDeliveryStageControl($arDeal['DELIVERY_STAGE']),
			'STAGE_ID' => CCrmViewHelper::RenderDealStageControl(
				array(
					'PREFIX' => "{$arResult['GRID_ID']}_PROGRESS_BAR_",
					'ENTITY_ID' => $arDeal['~ID'],
					'CURRENT_ID' => $arDeal['~STAGE_ID'],
					'CATEGORY_ID' => $arDeal['~CATEGORY_ID'],
					'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.list/list.ajax.php',
					'READ_ONLY' => !(isset($arDeal['EDIT']) && $arDeal['EDIT'] === true)
				)
			),
			'CATEGORY_ID' => $arDeal['DEAL_CATEGORY_NAME'],
			'IS_RETURN_CUSTOMER' => $arDeal['IS_RETURN_CUSTOMER'] === 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
			'IS_REPEATED_APPROACH' => $arDeal['IS_REPEATED_APPROACH'] === 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
			'ORIGINATOR_ID' => isset($arDeal['ORIGINATOR_NAME']) ? $arDeal['ORIGINATOR_NAME'] : '',
			'CREATED_BY' => $arDeal['~CREATED_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "DEAL_{$arDeal['~ID']}_CREATOR",
						'USER_ID' => $arDeal['~CREATED_BY'],
						'USER_NAME'=> $arDeal['CREATED_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arDeal['PATH_TO_USER_CREATOR']
					)
				) : '',
			'MODIFY_BY' => $arDeal['~MODIFY_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "DEAL_{$arDeal['~ID']}_MODIFIER",
						'USER_ID' => $arDeal['~MODIFY_BY'],
						'USER_NAME'=> $arDeal['MODIFY_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arDeal['PATH_TO_USER_MODIFIER']
					)
				) : '',
		) + (is_array($arResult['DEAL_UF'][$sKey]) ? $arResult['DEAL_UF'][$sKey] : [])
	);

	if (isset($arDeal['COMPANY_REVENUE']))
	{
		$resultItem['columns']['COMPANY_REVENUE'] =
			'<nobr>'
			. number_format($arDeal['COMPANY_REVENUE'], 2, ',', ' ')
			. '</nobr>'
		;
	}

	$extraUserIdFields = [
		'CONTACT_CREATED_BY_ID',
		'CONTACT_MODIFY_BY_ID',
		'CONTACT_ASSIGNED_BY_ID',
		'COMPANY_CREATED_BY_ID',
		'COMPANY_MODIFY_BY_ID',
		'COMPANY_ASSIGNED_BY_ID',
	];
	foreach ($extraUserIdFields as $extraUserIdField)
	{
		if (isset($arDeal[$extraUserIdField]) && $arDeal[$extraUserIdField] > 0)
		{
			$resultItem['columns'][$extraUserIdField] =
				CCrmViewHelper::PrepareUserBaloonHtml(
					[
						'PREFIX' => "DEAL_{$arDeal['~ID']}_".$extraUserIdField,
						'USER_ID' => $arDeal[$extraUserIdField],
						'USER_NAME' => $arDeal[$extraUserIdField . '_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arDeal[$extraUserIdField . '_SHOW_URL'],
					]
				)
			;
		}
	}

	$extraWebformFields = [
		'CONTACT_WEBFORM_ID',
		'COMPANY_WEBFORM_ID',
	];
	foreach ($extraWebformFields as $extraWebformField)
	{
		if (
			isset($arDeal[$extraWebformField])
			&& $arDeal[$extraWebformField] != ''
			&& isset($arResult['WEBFORM_LIST'][$arDeal[$extraWebformField]])
		)
		{
			$resultItem['columns'][$extraWebformField] = $arResult['WEBFORM_LIST'][$arDeal[$extraWebformField]];
		}
	}

	if (isset($arDeal['CONTACT_SOURCE_DESCRIPTION']))
	{
		$resultItem['columns']['CONTACT_SOURCE_DESCRIPTION'] = nl2br($arDeal['CONTACT_SOURCE_DESCRIPTION']);
	}
	if (isset($arDeal['CONTACT_COMMENTS']))
	{
		$resultItem['columns']['CONTACT_COMMENTS'] = htmlspecialcharsback($arDeal['CONTACT_COMMENTS']);
	}
	if (isset($arDeal['COMPANY_BANKING_DETAILS']))
	{
		$resultItem['columns']['COMPANY_BANKING_DETAILS'] = nl2br($arDeal['COMPANY_BANKING_DETAILS']);
	}
	if (isset($arDeal['COMPANY_COMMENTS']))
	{
		$resultItem['columns']['COMPANY_COMMENTS'] = htmlspecialcharsback($arDeal['COMPANY_COMMENTS']);
	}

	Tracking\UI\Grid::appendRows(
		\CCrmOwnerType::Deal,
		$arDeal['ID'],
		$resultItem['columns']
	);

	$userActivityID = isset($arDeal['~ACTIVITY_ID']) ? intval($arDeal['~ACTIVITY_ID']) : 0;
	$commonActivityID = isset($arDeal['~C_ACTIVITY_ID']) ? intval($arDeal['~C_ACTIVITY_ID']) : 0;
	if($userActivityID > 0)
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Deal),
				'ENTITY_ID' => $arDeal['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arDeal['~ASSIGNED_BY'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ACTIVITY_ID' => $userActivityID,
				'ACTIVITY_SUBJECT' => isset($arDeal['~ACTIVITY_SUBJECT']) ? $arDeal['~ACTIVITY_SUBJECT'] : '',
				'ACTIVITY_TIME' => isset($arDeal['~ACTIVITY_TIME']) ? $arDeal['~ACTIVITY_TIME'] : '',
				'ACTIVITY_EXPIRED' => isset($arDeal['~ACTIVITY_EXPIRED']) ? $arDeal['~ACTIVITY_EXPIRED'] : '',
				'ACTIVITY_TYPE_ID' => isset($arDeal['~ACTIVITY_TYPE_ID']) ? $arDeal['~ACTIVITY_TYPE_ID'] : '',
				'ACTIVITY_PROVIDER_ID' => isset($arDeal['~ACTIVITY_PROVIDER_ID']) ? $arDeal['~ACTIVITY_PROVIDER_ID'] : '',
				'ALLOW_EDIT' => $arDeal['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'USE_GRID_EXTENSION' => true
			)
		);

		$counterData = array(
			'CURRENT_USER_ID' => $currentUserID,
			'ENTITY' => $arDeal,
			'ACTIVITY' => array(
				'RESPONSIBLE_ID' => $currentUserID,
				'TIME' => isset($arDeal['~ACTIVITY_TIME']) ? $arDeal['~ACTIVITY_TIME'] : '',
				'IS_CURRENT_DAY' => isset($arDeal['~ACTIVITY_IS_CURRENT_DAY']) ? $arDeal['~ACTIVITY_IS_CURRENT_DAY'] : false
			)
		);

		if(CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentDealActivies, $counterData))
		{
			$resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-deal-today');
		}
	}
	elseif($commonActivityID > 0)
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Deal),
				'ENTITY_ID' => $arDeal['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arDeal['~ASSIGNED_BY'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ACTIVITY_ID' => $commonActivityID,
				'ACTIVITY_SUBJECT' => isset($arDeal['~C_ACTIVITY_SUBJECT']) ? $arDeal['~C_ACTIVITY_SUBJECT'] : '',
				'ACTIVITY_TIME' => isset($arDeal['~C_ACTIVITY_TIME']) ? $arDeal['~C_ACTIVITY_TIME'] : '',
				'ACTIVITY_RESPONSIBLE_ID' => isset($arDeal['~C_ACTIVITY_RESP_ID']) ? intval($arDeal['~C_ACTIVITY_RESP_ID']) : 0,
				'ACTIVITY_RESPONSIBLE_LOGIN' => isset($arDeal['~C_ACTIVITY_RESP_LOGIN']) ? $arDeal['~C_ACTIVITY_RESP_LOGIN'] : '',
				'ACTIVITY_RESPONSIBLE_NAME' => isset($arDeal['~C_ACTIVITY_RESP_NAME']) ? $arDeal['~C_ACTIVITY_RESP_NAME'] : '',
				'ACTIVITY_RESPONSIBLE_LAST_NAME' => isset($arDeal['~C_ACTIVITY_RESP_LAST_NAME']) ? $arDeal['~C_ACTIVITY_RESP_LAST_NAME'] : '',
				'ACTIVITY_RESPONSIBLE_SECOND_NAME' => isset($arDeal['~C_ACTIVITY_RESP_SECOND_NAME']) ? $arDeal['~C_ACTIVITY_RESP_SECOND_NAME'] : '',
				'ACTIVITY_TYPE_ID' => isset($arDeal['~C_ACTIVITY_TYPE_ID']) ? $arDeal['~C_ACTIVITY_TYPE_ID'] : '',
				'ACTIVITY_PROVIDER_ID' => isset($arDeal['~C_ACTIVITY_PROVIDER_ID']) ? $arDeal['~C_ACTIVITY_PROVIDER_ID'] : '',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
				'ALLOW_EDIT' => $arDeal['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'USE_GRID_EXTENSION' => true
			)
		);
	}
	else
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Deal),
				'ENTITY_ID' => $arDeal['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arDeal['~ASSIGNED_BY'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ALLOW_EDIT' => $arDeal['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'HINT_TEXT' => isset($arDeal['~WAITING_TITLE']) ? $arDeal['~WAITING_TITLE'] : '',
				'USE_GRID_EXTENSION' => true
			)
		);

		$counterData = array('CURRENT_USER_ID' => $currentUserID, 'ENTITY' => $arDeal);
		if($waitingID <= 0
			&& CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentDealActivies, $counterData)
		)
		{
			$resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-enitity-action-need');
		}
	}

	$arResult['GRID_DATA'][] = &$resultItem;
	unset($resultItem);
}
$APPLICATION->IncludeComponent('bitrix:main.user.link',
	'',
	array(
		'AJAX_ONLY' => 'Y',
	),
	false,
	array('HIDE_ICONS' => 'Y')
);

//region Action Panel
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));

if(!$isInternal
	&& ($allowWrite || $allowExclude || $allowDelete))
{
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

	$actionList = array(array('NAME' => GetMessage('CRM_DEAL_LIST_CHOOSE_ACTION'), 'VALUE' => 'none'));

	$yesnoList = array(
		array('NAME' => GetMessage('MAIN_YES'), 'VALUE' => 'Y'),
		array('NAME' => GetMessage('MAIN_NO'), 'VALUE' => 'N')
	);

	if($allowWrite && $arParams['IS_RECURRING'] !== "Y")
	{
		//region Add Task
		if (IsModuleInstalled('tasks'))
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_DEAL_TASK'),
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

		if ($arParams['IS_RECURRING'] !== "Y")
		{
			//region Set Stage
			if($arResult['EFFECTIVE_CATEGORY_ID'] >= 0)
			{
				//TODO: if category not selected show 2 selectors: category and stage
				$stageList = array(array('NAME' => GetMessage('CRM_STAGE_INIT'), 'VALUE' => ''));
				if(isset($arResult['CATEGORY_STAGE_LIST']))
				{
					foreach($arResult['CATEGORY_STAGE_LIST'] as $stageID => $stageName)
					{
						$stageList[] = array('NAME' => $stageName, 'VALUE' => $stageID);
					}
				}
				elseif(isset($arResult['CATEGORY_STAGE_GROUPS']))
				{
					foreach($arResult['CATEGORY_STAGE_GROUPS'] as $group)
					{
						$groupName = isset($group['name']) ? $group['name'] : '';
						if($groupName !== '')
						{
							$stageList[] = array('NAME' => $groupName, 'VALUE' => '', 'IS_GROUP' => true);
						}

						$groupItems = isset($group['items']) && is_array($group['items']) ? $group['items'] : array();
						foreach($groupItems as $itemKey => $itemName)
						{
							$stageList[] = array('NAME' => $itemName, 'VALUE' => $itemKey);
						}
					}
				}

				$actionList[] = array(
					'NAME' => GetMessage('CRM_DEAL_SET_STAGE'),
					'VALUE' => 'set_stage',
					'ONCHANGE' => array(
						array(
							'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
							'DATA' => array(
								array(
									'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
									'ID' => 'action_stage_id',
									'NAME' => 'ACTION_STAGE_ID',
									'ITEMS' => $stageList
								),
								$applyButton
							)
						),
						array(
							'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
							'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'set_stage')"))
						)
					)
				);
			}
			else
			{
				$actionList[] = array(
					'NAME' => GetMessage('CRM_DEAL_SET_STAGE'),
					'VALUE' => 'set_stage',
					'PSEUDO' => true,
					'ONCHANGE' => array(
						array(
							'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
							'DATA' => array($applyButton)
						),
						array(
							'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
							'DATA' => array(array('JS' => "BX.NotificationPopup.show(\"select_category\", { timeout: 5000, align: \"justify\", messages: [\"".GetMessageJS('CRM_DEAL_LIST_SELECT_CATEGORY_FOR_SET_STAGE_TITLE')."\", \"".GetMessageJS('CRM_DEAL_LIST_SELECT_CATEGORY_FOR_SET_STAGE_CONTENT')."\"] })"))
						)
					)
				);
			}
			//endregion
		}

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
			'NAME' => GetMessage('CRM_DEAL_ASSIGN_TO'),
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
		//region Create call list
		if(IsModuleInstalled('voximplant'))
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_DEAL_CREATE_CALL_LIST'),
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

		//region Refresh Accounting Data
		$actionList[] = array(
			'NAME' => GetMessage('CRM_DEAL_REFRESH_ACCOUNT'),
			'VALUE' => 'refresh_account',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array($applyButton)
				)
			)

		);
		//endregion

		if($allowDelete)
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_DEAL_ACTION_MERGE'),
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
			'NAME' => GetMessage('CRM_DEAL_ACTION_DELETE'),
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
			'NAME' => GetMessage('CRM_DEAL_EXCLUDE'),
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
			'NAME' => GetMessage('CRM_DEAL_MARK_AS_OPENED'),
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

		//region Change category
		if($arResult['HAVE_CUSTOM_CATEGORIES'] && $arResult['CATEGORY_ID'] >= 0)
		{
			$categoryList = array();
			foreach($arResult['CATEGORY_LIST'] as $categoryID => $categoryName)
			{
				if($categoryID !== $arResult['CATEGORY_ID'])
				{
					$categoryList[] = array('NAME' => $categoryName, 'VALUE' => $categoryID);
				}
			}
			$actionList[] = array(
				'NAME' => GetMessage('CRM_DEAL_MOVE_TO_CATEGORY'),
				'VALUE' => 'move_to_category',
				'ONCHANGE' => array(
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
						'DATA' => array(
							array(
								'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
								'ID' => 'action_move_to_category',
								'NAME' => 'ACTION_CATEGORY_ID',
								'ITEMS' => $categoryList
							),
							$applyButton
						)
					),
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'set_stage')"))
					)
				)
			);
		}
	}

	if($callListUpdateMode)
	{
		$controlPanel['GROUPS'][0]['ITEMS'][] = array(
			"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
			"TEXT" => GetMessage("CRM_DEAL_UPDATE_CALL_LIST"),
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
		//region Create & start call list
		if(IsModuleInstalled('voximplant'))
		{
			$controlPanel['GROUPS'][0]['ITEMS'][] = array(
				"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
				"TEXT" => GetMessage('CRM_DEAL_START_CALL_LIST'),
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

if($arResult['ENABLE_TOOLBAR'])
{
	$addButton =array(
		'TEXT' => GetMessage('CRM_DEAL_LIST_ADD_SHORT'),
		'TITLE' => GetMessage('CRM_DEAL_LIST_ADD'),
		'LINK' => $arResult['PATH_TO_DEAL_ADD'],
		'ICON' => 'btn-new'
	);

	if($arResult['ADD_EVENT_NAME'] !== '')
	{
		$addButton['ONCLICK'] = "BX.onCustomEvent(window, '{$arResult['ADD_EVENT_NAME']}')";
	}
	else
	{
		$urlParams = isset($arResult['DEAL_ADD_URL_PARAMS']) && is_array($arResult['DEAL_ADD_URL_PARAMS'])
			? $arResult['DEAL_ADD_URL_PARAMS'] : array();
		$addButton['ONCLICK'] = 'BX.CrmEntityManager.createEntity(BX.CrmEntityType.names.deal, { urlParams: '.CUtil::PhpToJSObject($urlParams).' })';
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

//$arParams['IS_RECURRING']
$APPLICATION->IncludeComponent(
	'bitrix:crm.newentity.counter.panel',
	'',
	array(
		'ENTITY_TYPE_NAME' => CCrmOwnerType::DealName,
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
		'HEADERS_SECTIONS' => $arResult['HEADERS_SECTIONS'],
		'ENABLE_FIELDS_SEARCH' => 'Y',
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_ID' => $arResult['AJAX_ID'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
		'AJAX_LOADER' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null,
		'HIDE_FILTER' => isset($arParams['HIDE_FILTER']) ? $arParams['HIDE_FILTER'] : null,
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'FILTER_PARAMS' => [
			'LAZY_LOAD' => [
				'GET_LIST' => '/bitrix/components/bitrix/crm.deal.list/filter.ajax.php?action=list&filter_id='.urlencode($arResult['GRID_ID']).'&category_id='.$arResult['CATEGORY_ID'].'&is_recurring='.$arParams['IS_RECURRING'].'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'GET_FIELD' => '/bitrix/components/bitrix/crm.deal.list/filter.ajax.php?action=field&filter_id='.urlencode($arResult['GRID_ID']).'&category_id='.$arResult['CATEGORY_ID'].'&is_recurring='.$arParams['IS_RECURRING'].'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
			],
			'ENABLE_FIELDS_SEARCH' => 'Y',
			'HEADERS_SECTIONS' => $arResult['HEADERS_SECTIONS'],
			'CONFIG' => [
				'popupColumnsCount' => 4,
				'popupWidth' => 800,
				'showPopupInCenter' => true,
			],
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
				\Bitrix\Crm\Automation\Helper::getNavigationBarItems(\CCrmOwnerType::Deal, $arResult['CATEGORY_ID']),
				array(
					array(
						//'icon' => 'kanban',
						'id' => 'kanban',
						'name' => GetMessage('CRM_DEAL_LIST_FILTER_NAV_BUTTON_KANBAN'),
						'active' => false,
						'url' => isset($arResult['PATH_TO_DEAL_KANBANCATEGORY'])
							? $arResult['PATH_TO_DEAL_KANBANCATEGORY']
							: $arResult['PATH_TO_DEAL_KANBAN']
					),
					array(
						//'icon' => 'table',
						'id' => 'list',
						'name' => GetMessage('CRM_DEAL_LIST_FILTER_NAV_BUTTON_LIST'),
						'active' => true,
						'url' => isset($arResult['PATH_TO_DEAL_CATEGORY'])
							? $arResult['PATH_TO_DEAL_CATEGORY']
							: $arResult['PATH_TO_DEAL_LIST']
					)
				),
				(\Bitrix\Crm\Integration\Calendar::isResourceBookingEnabled()
					?
					array(
						array(
							'id' => 'calendar',
							'name' => GetMessage('CRM_DEAL_LIST_FILTER_NAV_BUTTON_CALENDAR'),
							'active' => false,
							'url' => isset($arResult['PATH_TO_DEAL_CALENDARCATEGORY'])
								? $arResult['PATH_TO_DEAL_CALENDARCATEGORY']
								: $arResult['PATH_TO_DEAL_CALENDAR']
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
				'ownerTypeName' => CCrmOwnerType::DealName,
				'gridId' => $arResult['GRID_ID'],
				'activityEditorId' => $activityEditorID,
				'activityServiceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'taskCreateUrl'=> isset($arResult['TASK_CREATE_URL']) ? $arResult['TASK_CREATE_URL'] : '',
				'serviceUrl' => '/bitrix/components/bitrix/crm.deal.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
			),
			'MESSAGES' => array(
				'deletionDialogTitle' => GetMessage('CRM_DEAL_DELETE_TITLE'),
				'deletionDialogMessage' => GetMessage('CRM_DEAL_DELETE_CONFIRM'),
				'deletionDialogButtonTitle' => GetMessage('CRM_DEAL_DELETE'),
				'moveToCategoryDialogTitle' => GetMessage('CRM_DEAL_MOVE_TO_CATEGORY_DLG_TITLE'),
				'moveToCategoryDialogMessage' => GetMessage('CRM_DEAL_MOVE_TO_CATEGORY_DLG_SUMMARY'),
				'exclusionDialogTitle' => GetMessage('CRM_DEAL_EXCLUDE_TITLE'),
				'exclusionDialogMessage' => GetMessage('CRM_DEAL_EXCLUDE_CONFIRM'),
				'exclusionDialogMessageHelp' => GetMessage('CRM_DEAL_EXCLUDE_CONFIRM_HELP'),
				'exclusionDialogButtonTitle' => GetMessage('CRM_DEAL_EXCLUDE'),
			)
		),
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	),
	$component
);
?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.Crm.PartialEditorDialog.messages =
			{
				entityHasInaccessibleFields: "<?= CUtil::JSEscape(Loc::getMessage('CRM_DEAL_HAS_INACCESSIBLE_FIELDS')) ?>",
			};

			BX.CrmEntityManager.entityCreateUrls = <?=CUtil::PhpToJSObject($arResult['ENTITY_CREATE_URLS'])?>;
			BX.CrmDealCategory.infos = <?=CUtil::PhpToJSObject(
				Bitrix\Crm\Category\DealCategory::getJavaScriptInfos($arResult['CATEGORY_ACCESS']['CREATE'])
			)?>;
			BX.CrmDealCategorySelectDialog.messages =
				{
					title: "<?=GetMessageJS('CRM_DEAL_CATEGORY_DLG_TITLE')?>",
					field: "<?=GetMessageJS('CRM_DEAL_CATEGORY_DLG_FIELD')?>",
					saveButton: "<?=GetMessageJS('CRM_DEAL_BUTTON_SAVE')?>",
					cancelButton: "<?=GetMessageJS('CRM_DEAL_BUTTON_CANCEL')?>"
				};

			BX.CrmLongRunningProcessDialog.messages =
				{
					startButton: "<?=GetMessageJS('CRM_DEAL_LRP_DLG_BTN_START')?>",
					stopButton: "<?=GetMessageJS('CRM_DEAL_LRP_DLG_BTN_STOP')?>",
					closeButton: "<?=GetMessageJS('CRM_DEAL_LRP_DLG_BTN_CLOSE')?>",
					wait: "<?=GetMessageJS('CRM_DEAL_LRP_DLG_WAIT')?>",
					requestError: "<?=GetMessageJS('CRM_DEAL_LRP_DLG_REQUEST_ERR')?>"
				};

			BX.Crm.PartialEditorDialog.registerEntityEditorUrl(
				"<?=CCrmOwnerType::DealName?>",
				"<?='/bitrix/components/bitrix/crm.deal.details/ajax.php?'.bitrix_sessid_get()?>"
			);

			var gridId = "<?= CUtil::JSEscape($arResult['GRID_ID'])?>";
			BX.Crm.BatchDeletionManager.create(
				gridId,
				{
					gridId: gridId,
					entityTypeId: <?=CCrmOwnerType::Deal?>,
					extras: { CATEGORY_ID: <?=$arResult['CATEGORY_ID']?>, IS_RECURRING: "<?=CUtil::JSEscape($arParams['IS_RECURRING'])?>" },
					container: "batchDeletionWrapper",
					stateTemplate: "<?=GetMessageJS('CRM_DEAL_STEPWISE_STATE_TEMPLATE')?>",
					messages:
						{
							title: "<?=GetMessageJS('CRM_DEAL_LIST_DEL_PROC_DLG_TITLE')?>",
							confirmation: "<?=GetMessageJS('CRM_DEAL_LIST_DEL_PROC_DLG_SUMMARY')?>",
							summaryCaption: "<?=GetMessageJS('CRM_DEAL_BATCH_DELETION_COMPLETED')?>",
							summarySucceeded: "<?=GetMessageJS('CRM_DEAL_BATCH_DELETION_COUNT_SUCCEEDED')?>",
							summaryFailed: "<?=GetMessageJS('CRM_DEAL_BATCH_DELETION_COUNT_FAILED')?>"
						}
				}
			);

			BX.Crm.BatchMergeManager.create(
				gridId,
				{
					gridId: gridId,
					entityTypeId: <?=CCrmOwnerType::Deal?>,
					mergerUrl: "<?=\CUtil::JSEscape($arParams['PATH_TO_DEAL_MERGE'])?>"
				}
			);

			BX.Crm.AnalyticTracker.config =
				{
					id: "deal_list",
					settings: { params: <?=CUtil::PhpToJSObject($arResult['ANALYTIC_TRACKER'])?> }
				};

			<?php if (isset($arResult['RESTRICTED_VALUE_CLICK_CALLBACK'])):?>
			BX.addCustomEvent(window, 'onCrmRestrictedValueClick', function() {
				<?=$arResult['RESTRICTED_VALUE_CLICK_CALLBACK'];?>
			});
			<?php endif;?>
		}
	);
</script><?
if(!$isInternal):
?><script type="text/javascript">
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
<?if($arResult['CONVERSION_PERMITTED'] && $arResult['CAN_CONVERT'] && isset($arResult['CONVERSION_CONFIG'])):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.CrmDealConversionScheme.messages =
					<?=CUtil::PhpToJSObject(\Bitrix\Crm\Conversion\DealConversionScheme::getJavaScriptDescriptions(false))?>;

				BX.CrmDealConverter.messages =
				{
					accessDenied: "<?=GetMessageJS("CRM_DEAL_CONV_ACCESS_DENIED")?>",
					generalError: "<?=GetMessageJS("CRM_DEAL_CONV_GENERAL_ERROR")?>",
					dialogTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_TITLE")?>",
					syncEditorLegend: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_LEGEND")?>",
					syncEditorFieldListTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_FILED_LIST_TITLE")?>",
					syncEditorEntityListTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE")?>",
					continueButton: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_CONTINUE_BTN")?>",
					cancelButton: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_CANCEL_BTN")?>"
				};
				BX.CrmDealConverter.permissions =
				{
					invoice: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_INVOICE'])?>,
					quote: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_QUOTE'])?>
				};
				BX.CrmDealConverter.settings =
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.show/ajax.php?action=convert&'.bitrix_sessid_get()?>",
					config: <?=CUtil::PhpToJSObject($arResult['CONVERSION_CONFIG']->toJavaScript())?>
				};
				BX.CrmEntityType.setCaptions(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetJavascriptDescriptions())?>);
			}
		);
	</script>
<?endif;?>
<?if (!empty($arResult['CLIENT_FIELDS_RESTRICTIONS'])):
	Bitrix\Main\UI\Extension::load(['crm.restriction.client-fields']);
?>
		<script type="text/javascript">
		BX.ready(
			function()
			{
				new BX.Crm.Restriction.ClientFieldsRestriction(
					<?=CUtil::PhpToJSObject($arResult['CLIENT_FIELDS_RESTRICTIONS'])?>
				);
			}
		);
		</script>
<?endif;?>
<?if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("rebuildDealSearch"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_DEAL_REBUILD_SEARCH_CONTENT_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_REBUILD_SEARCH_CONTENT_STATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("rebuildDealSearch",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SEARCH_CONTENT",
						container: "rebuildDealSearchWrapper",
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
<?if($arResult['NEED_FOR_REBUILD_SECURITY_ATTRS']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.AutorunProcessManager.createIfNotExists(
					"rebuildDealSecurityAttrs",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SECURITY_ATTRS",
						container: "rebuildDealSecurityAttrsWrapper",
						title: "<?=GetMessageJS('CRM_DEAL_REBUILD_SECURITY_ATTRS_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_DEAL_STEPWISE_STATE_TEMPLATE')?>",
						enableLayout: true
					}
				).runAfter(100);
			}
		);
	</script>
<?endif;?>
<?if($arResult['NEED_FOR_BUILD_TIMELINE']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("buildDealTimeline"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_DEAL_BUILD_TIMELINE_DLG_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_DEAL_BUILD_TIMELINE_STATE')?>"
				};
				var manager = BX.AutorunProcessManager.create("buildDealTimeline",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "<?=$isRecurring ? 'BUILD_RECURRING_TIMELINE' : 'BUILD_TIMELINE'?>",
						container: "buildDealTimelineWrapper",
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
				if(BX.AutorunProcessPanel.isExists("refreshDealAccounting"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_DEAL_REFRESH_ACCOUNTING_DLG_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_DEAL_STEPWISE_STATE_TEMPLATE')?>"
				};
				var manager = BX.AutorunProcessManager.create("refreshDealAccounting",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REFRESH_ACCOUNTING",
						container: "refreshDealAccountingWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>
<?if($arResult['NEED_FOR_REBUILD_DEAL_SEMANTICS']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("rebuildDealSemantics"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_DEAL_REBUILD_SEMANTICS_DLG_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_DEAL_STEPWISE_STATE_TEMPLATE')?>"
				};
				var manager = BX.AutorunProcessManager.create("rebuildDealSemantics",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SEMANTICS",
						container: "rebuildDealSemanticsWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>
<?if($arResult['NEED_FOR_REBUILD_DEAL_ATTRS']):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var link = BX("rebuildDealAttrsLink");
			if(link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						var msg = BX("rebuildDealAttrsMsg");
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

<?\Bitrix\Crm\Integration\NotificationsManager::showSignUpFormOnCrmShopCreated()?>
