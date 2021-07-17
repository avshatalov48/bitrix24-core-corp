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

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration\Calendar;

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable())
{
	CBitrix24::initLicenseInfoPopupJS();
}

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/progress_control.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/analytics.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/autorun_proc.css');

$calendarId = 'CrmCalendarDealListGrid';
$newDealUrl = preg_replace("/#deal_id#/i", "0", $arParams['PATH_TO_DEAL_DETAILS']);
$dealSliderRegexp = preg_replace("/#deal_id#/i", "(\\\\\d+)", $arParams['PATH_TO_DEAL_DETAILS']);
$dealColor = '#FFA900';

if (isset($arParams['CATEGORY_ID']) && $arParams['CATEGORY_ID'] > 0)
{
	$newDealUrl = CCrmUrlUtil::AddUrlParams($newDealUrl, array('category_id' => intval($arParams['CATEGORY_ID'])));
}

?><div id="rebuildMessageWrapper"><?

if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'])
{
	?><div id="rebuildDealSearchWrapper"></div><?
}
if($arResult['NEED_FOR_BUILD_TIMELINE'])
{
	?><div id="buildDealTimelineWrapper"></div><?
}
if($arResult['NEED_FOR_REFRESH_ACCOUNTING'])
{
	?><div id="refreshDealAccountingWrapper"></div><?
}
if($arResult['NEED_FOR_REBUILD_DEAL_ATTRS'])
{
	?><div id="rebuildDealAttrsMsg" class="crm-view-message">
		<?=Loc::getMessage('CRM_DEAL_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildDealAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
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

//if(!$isInternal)
//{
//	$activityEditorID = "{$arResult['GRID_ID']}_activity_editor";
//	$APPLICATION->IncludeComponent(
//		'bitrix:crm.activity.editor',
//		'',
//		array(
//			'EDITOR_ID' => $activityEditorID,
//			'PREFIX' => $arResult['GRID_ID'],
//			'OWNER_TYPE' => 'DEAL',
//			'OWNER_ID' => 0,
//			'READ_ONLY' => false,
//			'ENABLE_UI' => false,
//			'ENABLE_TOOLBAR' => false
//		),
//		null,
//		array('HIDE_ICONS' => 'Y')
//	);
//}

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
echo CCrmViewHelper::RenderDealStageSettings();
$prefix = $arResult['GRID_ID'];
$prefixLC = mb_strtolower($arResult['GRID_ID']);

$arResult['GRID_DATA'] = array();
$arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
	$arColumns[$arHead['id']] = false;

$now = time() + CTimeZone::GetOffset();

foreach($arResult['DEAL'] as $sKey => $arDeal)
{
	$jsTitle = isset($arDeal['~TITLE']) ? CUtil::JSEscape($arDeal['~TITLE']) : '';
	$jsShowUrl = isset($arDeal['PATH_TO_DEAL_SHOW']) ? CUtil::JSEscape($arDeal['PATH_TO_DEAL_SHOW']) : '';

	$eventParam = array(
		'ID' => $arDeal['ID'],
		'CALL_LIST_ID' => $arResult['CALL_LIST_ID'],
		'CALL_LIST_CONTEXT' => $arResult['CALL_LIST_CONTEXT'],
		'GRID_ID' => $arResult['GRID_ID']
	);

	$resultItem = array(
		'id' => $arDeal['ID'],
		'data' => $arDeal,
		'editable' => !$arDeal['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
		'columns' => array(
			'DEAL_SUMMARY' => CCrmViewHelper::RenderInfo(
				$arDeal['PATH_TO_DEAL_SHOW'],
				isset($arDeal['TITLE']) ? $arDeal['TITLE'] : ('['.$arDeal['ID'].']'),
				$arDeal['DEAL_TYPE_NAME'],
				array('TARGET' => '_self')
			),
			'DEAL_CLIENT' => isset($arDeal['CLIENT_INFO']) ? CCrmViewHelper::PrepareClientInfo($arDeal['CLIENT_INFO']) : '',
			'COMPANY_ID' => isset($arDeal['COMPANY_INFO']) ? CCrmViewHelper::PrepareClientInfo($arDeal['COMPANY_INFO']) : '',
			'CONTACT_ID' => isset($arDeal['CONTACT_INFO']) ? CCrmViewHelper::PrepareClientInfo($arDeal['CONTACT_INFO']) : '',
			'TITLE' => '<a target="_self" href="'.$arDeal['PATH_TO_DEAL_SHOW'].'"
				class="'.($arDeal['BIZPROC_STATUS'] != '' ? 'bizproc bizproc_status_'.$arDeal['BIZPROC_STATUS'] : '').'"
				'.($arDeal['BIZPROC_STATUS_HINT'] != '' ? 'onmouseover="BX.hint(this, \''.CUtil::JSEscape($arDeal['BIZPROC_STATUS_HINT']).'\');"' : '').'>'.$arDeal['TITLE'].'</a>',
			'CLOSED' => $arDeal['CLOSED'] == 'Y' ? Loc::getMessage('MAIN_YES') : Loc::getMessage('MAIN_NO'),
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
			'EVENT_ID' => isset($arResult['EVENT_LIST'][$arDeal['EVENT_ID']]) ? $arResult['EVENT_LIST'][$arDeal['EVENT_ID']] : $arDeal['EVENT_ID'],
			'CURRENCY_ID' => CCrmCurrency::GetCurrencyName($arDeal['CURRENCY_ID']),
			'PRODUCT_ID' => isset($arDeal['PRODUCT_ROWS']) ? htmlspecialcharsbx(CCrmProductRow::RowsToString($arDeal['PRODUCT_ROWS'])) : '',
			'STATE_ID' => isset($arResult['STATE_LIST'][$arDeal['STATE_ID']]) ? $arResult['STATE_LIST'][$arDeal['STATE_ID']] : $arDeal['STATE_ID'],
			'WEBFORM_ID' => isset($arResult['WEBFORM_LIST'][$arDeal['WEBFORM_ID']]) ? $arResult['WEBFORM_LIST'][$arDeal['WEBFORM_ID']] : $arDeal['WEBFORM_ID'],
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
			'IS_RETURN_CUSTOMER' => $arDeal['IS_RETURN_CUSTOMER'] == 'Y' ? Loc::getMessage('MAIN_YES') : Loc::getMessage('MAIN_NO'),
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
		) + $arResult['DEAL_UF'][$sKey]
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
				'ALLOW_EDIT' => $arDeal['EDIT'],
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
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
				'ALLOW_EDIT' => $arDeal['EDIT'],
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


if($arResult['ENABLE_TOOLBAR'])
{
	$addButton =array(
		'TEXT' => Loc::getMessage('CRM_DEAL_LIST_ADD_SHORT'),
		'TITLE' => Loc::getMessage('CRM_DEAL_LIST_ADD'),
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

?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.Crm.AnalyticTracker.config =
				{
					id: "deal_calendar",
					settings: { params: <?=CUtil::PhpToJSObject($arResult['ANALYTIC_TRACKER'])?> }
				};
		}
	);
</script><?

//region Filter
//Skip rendering of grid filter for internal grid request (filter already created)
if(!Bitrix\Main\Grid\Context::isInternalRequest()
	&& isset($arResult['FILTER']) && isset($arResult['FILTER_PRESETS']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.filter',
		isset($arParams['~FILTER_TEMPLATE']) ? $arParams['~FILTER_TEMPLATE'] : 'title',
		array(
			'GRID_ID' => $arResult['GRID_ID'],
			'FILTER_ID' => $arResult['GRID_ID'],
			'FILTER' => $arResult['FILTER'],
			'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],

			'NAVIGATION_BAR' => array(
				'ITEMS' => array_merge(
					\Bitrix\Crm\Automation\Helper::getNavigationBarItems(\CCrmOwnerType::Deal, $arResult['CATEGORY_ID']),
					array(
						array(
							'id' => 'kanban',
							'name' => Loc::getMessage('CRM_DEAL_LIST_FILTER_NAV_BUTTON_KANBAN'),
							'active' => false,
							'url' => isset($arResult['PATH_TO_DEAL_KANBANCATEGORY'])
								? $arResult['PATH_TO_DEAL_KANBANCATEGORY']
								: $arResult['PATH_TO_DEAL_KANBAN']
						),
						array(
							'id' => 'list',
							'name' => Loc::getMessage('CRM_DEAL_LIST_FILTER_NAV_BUTTON_LIST'),
							'active' => false,
							'url' => isset($arResult['PATH_TO_DEAL_CATEGORY'])
								? $arResult['PATH_TO_DEAL_CATEGORY']
								: $arResult['PATH_TO_DEAL_LIST']
						)
					),
					(Calendar::isResourceBookingEnabled()
						?
						array(
							array(
								'id' => 'calendar',
								'name' => Loc::getMessage('CRM_DEAL_LIST_FILTER_NAV_BUTTON_CALENDAR'),
								'active' => true,
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
			'LIMITS' => isset($arResult['LIVE_SEARCH_LIMIT_INFO']) ? $arResult['LIVE_SEARCH_LIMIT_INFO'] : null,
			'ENABLE_LIVE_SEARCH' => true,
			'DISABLE_SEARCH' => isset($arParams['~DISABLE_SEARCH']) && $arParams['~DISABLE_SEARCH'] === true,
			'LAZY_LOAD' => array(
				'GET_LIST' => '/bitrix/components/bitrix/crm.deal.list/filter.ajax.php?action=list&filter_id='.urlencode($arResult['GRID_ID']).'&category_id='.$arResult['CATEGORY_ID'].'&is_recurring='.$arParams['IS_RECURRING'].'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'GET_FIELD' => '/bitrix/components/bitrix/crm.deal.list/filter.ajax.php?action=field&filter_id='.urlencode($arResult['GRID_ID']).'&category_id='.$arResult['CATEGORY_ID'].'&is_recurring='.$arParams['IS_RECURRING'].'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
			)
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);

	?>
	<script>
		// Handler to refresh calendar as a reaction for CRM fiter changes
		BX.ready(function(){
			var filterId = "<?= HtmlFilter::encode($arResult['GRID_ID'])?>";
			BX.addCustomEvent('BX.Main.Filter:apply', function(id, data, ctx, promise, params)
			{
				if (id == filterId && window.BXEventCalendar)
				{
					var eventCalendar = window.BXEventCalendar.Get('<?= $calendarId?>');
					if (eventCalendar)
					{
						eventCalendar.reload();
					}
				}
			});
		});
	</script>
	<?
}
//endregion
$uri = new \Bitrix\Main\Web\Uri(\Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest()->getRequestUri());
$uri->deleteParams(\Bitrix\Main\HttpRequest::getSystemParameters());
$currentUrl = $uri->getUri();
$filterSelect = Calendar::getCalendarViewFieldOption(CCrmOwnerType::DealName, 'CLOSEDATE');
list($filterSelectId, $filterSelectType, $filterSelectName) = Calendar::parseUserfieldKey($filterSelect);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	$request = \Bitrix\Main\Context::getCurrent()->getRequest()->toArray();
	if (isset($request['crm_calendar_action']))
	{
		$entries = array();
		for ($i = 0, $l = count($arResult['GRID_DATA']); $i < $l; $i++)
		{
			$crmEntity = $arResult['GRID_DATA'][$i];
			$fields = array(
				'ID' => $crmEntity['data']['ID'],
				'COLOR' => $dealColor,
				'NAME' => $crmEntity['data']['DEAL_SUMMARY'],
				'OPEN_URL' => $crmEntity['data']['PATH_TO_DEAL_DETAILS']
			);

			if ($filterSelect == 'DATE_CREATE')
			{
				$fields['DATE_FROM'] = $crmEntity['data']['DATE_CREATE'];
				$fields['DATE_TO'] = $crmEntity['data']['DATE_CREATE'];
				$fields['SKIP_TIME'] = false;
			}
			elseif ($filterSelect == 'CLOSEDATE')
			{
				$fields['DATE_FROM'] = $crmEntity['data']['CLOSEDATE'];
				$fields['DATE_TO'] = $crmEntity['data']['CLOSEDATE'];
				$fields['SKIP_TIME'] = true;
			}
			elseif($filterSelectType == 'resourcebooking')
			{
				$fields = Calendar::handleCrmEntityBookingEntry($crmEntity['data'], $fields);
			}
			else
			{
				$fields['DATE_FROM'] = $crmEntity['data'][$filterSelectName];
				$fields['DATE_TO'] = $crmEntity['data'][$filterSelectName];
				$fields['SKIP_TIME'] = $filterSelectType == 'date';
			}

			$entries[] = $fields;
		}

		$APPLICATION->ShowAjaxHead();
		$APPLICATION->RestartBuffer();
		echo \Bitrix\Main\Web\Json::encode(array(
			'data' => array(
				'entries' => $entries
			)
		));
		CMain::FinalActions();
		die();
	}
}

//region Calendar
$APPLICATION->IncludeComponent("bitrix:calendar.interface.grid", "", Array(
	"ID" => $calendarId,
	"ENTITY_TYPE" => 'crm',
	"EXTERNAL_DATA_HANDLE_MODE" => true,
	"READONLY" => !$allowWrite,
	"SHOW_FILTER" => false,
	"SHOW_SECTION_SELECTOR" => false,
	"SHOW_SETTINGS_BUTTON" => false,
	"SHOW_TOP_VIEW_SWITCHER" => false,
	"DEFAULT_SECTION_NAME" => 'calendar#deal',
	"DEFAULT_SECTION_COLOR" => $dealColor,
	"NEW_ENTRY_NAME" => Loc::getMessage('CRM_CALENDAR_NEW_DEAL_NAME'),
	"COLLAPSED_ENTRIES_NAME" => Loc::getMessage('CRM_CALENDAR_COLLAPSED_DEAL_NAME'),
	"AVILABLE_VIEWS" => array('day', 'week', 'month'),
	"ADDITIONAL_VIEW_MODES" => $arParams['CALENDAR_MODE_LIST']
));
//endregion

?>
<script type="text/javascript">
	//region Javascript External Handlers calendar
	BX.ready(function(){
		var
			requestUri = '<?= CUtil::JSEscape($currentUrl)?>',
			eventCalendar = window.BXEventCalendar.Get('<?= $calendarId?>');

		BX.addCustomEvent(eventCalendar, 'loadEntries', function(params)
		{
			var data = {
				sessid: BX.bitrix_sessid(),
				crm_calendar_action: 'LOAD_ENTRIES'
			};

			if (params && params.params && params.params.startDate && params.params.finishDate)
			{
				data.crm_calendar_start_date = BX.formatDate(params.params.startDate, BX.message('FORMAT_DATE'));
				data.crm_calendar_finish_date = BX.formatDate(params.params.finishDate, BX.message('FORMAT_DATE'));
			}

			BX.ajax({
				method: 'POST',
				dataType: 'json',
				url: requestUri,
				data: data,
				onsuccess: function(json)
				{
					if (BX.type.isFunction(params.onLoadCallback))
					{
						if (!json.data)
						{
							json.data = {};
						}
						params.onLoadCallback(json.data);
					}
				}
			});
		});

		BX.addCustomEvent(eventCalendar, 'entryClick', function(params)
		{
			if (params && params.entry && params.entry.data && BX.type.isString(params.entry.data.OPEN_URL))
			{
				BX.SidePanel.Instance.open(params.entry.data.OPEN_URL,
				{
					cacheable: false,
					loader: "crm-entity-details-loader"
				});
			}
		});

		<?if ($allowWrite):?>
			BX.addCustomEvent(eventCalendar, 'createNewEntry', function(params)
			{
				if (params)
				{
					var
						filterSelect = "<?= HtmlFilter::encode($filterSelect)?>",
						url = '<?= Calendar::prepareNewEntityUrlFromCalendar($newDealUrl, $filterSelect)?>',
						format = BX.date.convertBitrixFormat(filterSelect.indexOf('|date|') !== -1 ?  BX.message("FORMAT_DATE") : BX.message("FORMAT_DATETIME")),
						from = BX.date.format(format, params.entryTime.from.getTime() / 1000),
						to = BX.date.format(format, params.entryTime.to.getTime() / 1000);

					if (from)
					{
						url = url.replace('#DATE_FROM#', from);
					}
					if (to)
					{
						url = url.replace('#DATE_TO#', to);
					}

					BX.SidePanel.Instance.open(url, {cacheable: false, loader: "crm-entity-details-loader"});
				}
			});
		<?endif;?>

		// Handle of changing type of field which is used to display entries on calendar grid
		BX.addCustomEvent(eventCalendar, 'changeViewMode', function(params)
		{
			if (params && params.id)
			{
				BX.userOptions.save('calendar', 'resourceBooking', '<?= CCrmOwnerType::DealName?>', params.id);
				if (eventCalendar.showLoader)
				{
					eventCalendar.showLoader();
				}
				BX.reload();
			}
		});

		// Handle closing of slider and check if we should reload calendar
		BX.addCustomEvent("SidePanel.Slider:onClose", function(event){
			if (event && event.getSliderPage && eventCalendar)
			{
				var regRes = new RegExp('<?= $dealSliderRegexp?>.*', 'ig').exec(event.getSliderPage().getUrl());
				if (BX.type.isArray(regRes) && regRes[1] > 0)
				{
					eventCalendar.reload();
				}
			}
		});

		// help item for menu
		BX.addCustomEvent(eventCalendar, 'beforeViewModePopupOpened', function(menuItems)
		{
			if (BX.Helper)
			{
				menuItems.push({
					text: '<?= Loc::getMessage('CRM_CALENDAR_DEAL_USERFIELD_HELP_MENU')?>',
					className: 'menu-popup-item-help',
					onclick: BX.delegate(function(e, item)
					{
						BX.Helper.show('redirect=detail&code=7481073');
						if (item && item.menuWindow)
						{
							item.menuWindow.close();
						}
					}, this)
				});
			}
		});
	});

	//endregion
</script>

<?\Bitrix\Crm\Integration\NotificationsManager::showSignUpFormOnCrmShopCreated()?>