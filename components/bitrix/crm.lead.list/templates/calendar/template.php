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
use Bitrix\Crm\UI\NavigationBarPanel;

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable())
{
	CBitrix24::initLicenseInfoPopupJS();
}

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/progress_control.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/analytics.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/autorun_proc.css');

$calendarId = 'CrmCalendarLeadListGrid';
$newLeadUrl = preg_replace("/#lead_id#/i", "0", $arParams['PATH_TO_LEAD_DETAILS']);
$leadSliderRegexp = preg_replace("/#lead_id#/i", "(\\\\\d+)", $arParams['PATH_TO_LEAD_DETAILS']);

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

if($arResult['NEED_FOR_BUILD_TIMELINE'])
{
	?><div id="buildLeadTimelineWrapper"></div><?
}
if($arResult['NEED_FOR_REFRESH_ACCOUNTING'])
{
	?><div id="refreshLeadAccountingWrapper"></div><?
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
	'ownerType' => CCrmOwnerType::LeadName,
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'activityEditorId' => $activityEditorID,
	'serviceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'filterFields' => [],
	'destroyPreviousExtension' => true
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

	$resultItem = array(
		'id' => $arLead['ID'],
		'data' => $arLead,
		'editable' => !$arLead['EDIT'] ? $arColumns : true,
		'columns' => array(
			'LEAD_SUMMARY' => CCrmViewHelper::RenderInfo(
				$arLead['PATH_TO_LEAD_SHOW'],
				isset($arLead['TITLE']) ? $arLead['TITLE'] : ('['.$arLead['ID'].']'), $arLead['LEAD_SOURCE_NAME'],
				array('TARGET' => '_self')
			),
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
			'CURRENCY_ID' => CCrmCurrency::GetCurrencyName($arLead['~CURRENCY_ID']),
			'PRODUCT_ID' => isset($arLead['PRODUCT_ROWS']) ? htmlspecialcharsbx(CCrmProductRow::RowsToString($arLead['PRODUCT_ROWS'])) : '',
			'IS_RETURN_CUSTOMER' => isset($arResult['BOOLEAN_VALUES_LIST'][$arLead['IS_RETURN_CUSTOMER']]) ? $arResult['BOOLEAN_VALUES_LIST'][$arLead['IS_RETURN_CUSTOMER']] : $arLead['IS_RETURN_CUSTOMER'],
			'HONORIFIC' => isset($arResult['HONORIFIC'][$arLead['HONORIFIC']]) ? $arResult['HONORIFIC'][$arLead['HONORIFIC']] : '',
			'STATUS_ID' => CCrmViewHelper::RenderLeadStatusControl(
				array(
					'PREFIX' => "{$arResult['GRID_ID']}_PROGRESS_BAR_",
					'ENTITY_ID' => $arLead['~ID'],
					'CURRENT_ID' => $arLead['~STATUS_ID'],
					'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.list/list.ajax.php',
					'CONVERSION_SCHEME' => $arResult['CONVERSION_SCHEME'],
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

?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.Crm.AnalyticTracker.config =
				{
					id: "lead_calendar",
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
			'NAVIGATION_BAR' => (new NavigationBarPanel(CCrmOwnerType::Lead))
				->setItems([
					NavigationBarPanel::ID_AUTOMATION,
					NavigationBarPanel::ID_KANBAN,
					NavigationBarPanel::ID_LIST,
					NavigationBarPanel::ID_CALENDAR
				], NavigationBarPanel::ID_CALENDAR)
				->setBinding($arResult['NAVIGATION_CONTEXT_ID'])
				->get(),
			'LIMITS' => isset($arResult['LIVE_SEARCH_LIMIT_INFO']) ? $arResult['LIVE_SEARCH_LIMIT_INFO'] : null,
			'ENABLE_LIVE_SEARCH' => true,
			'DISABLE_SEARCH' => isset($arParams['~DISABLE_SEARCH']) && $arParams['~DISABLE_SEARCH'] === true,
			'LAZY_LOAD' => array(
				'GET_LIST' => '/bitrix/components/bitrix/crm.lead.list/filter.ajax.php?action=list&filter_id='.urlencode($arResult['GRID_ID']).'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'GET_FIELD' => '/bitrix/components/bitrix/crm.lead.list/filter.ajax.php?action=field&filter_id='.urlencode($arResult['GRID_ID']).'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
			),
			'ENABLE_FIELDS_SEARCH' => 'Y',
			'HEADERS_SECTIONS' => $arResult['HEADERS_SECTIONS'],
			'CONFIG' => [
				'popupColumnsCount' => 4,
				'popupWidth' => 800,
				'showPopupInCenter' => true,
			],
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

			// enable grid extension
			BX.Crm.Page.initialize();
			BX.CrmUIGridExtension.create(
				"<?=CUtil::JSEscape($gridManagerID)?>",
				<?=CUtil::PhpToJSObject($gridManagerCfg)?>
			);
		});
	</script>
	<?
}
//endregion

$uri = new \Bitrix\Main\Web\Uri(\Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest()->getRequestUri());
$uri->deleteParams(\Bitrix\Main\HttpRequest::getSystemParameters());
$currentUrl = $uri->getUri();
$filterSelect = Calendar::getCalendarViewFieldOption(CCrmOwnerType::LeadName, 'DATE_CREATE');
list($filterSelectId, $filterSelectType, $filterSelectName) = Calendar::parseUserfieldKey($filterSelect);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	$request = \Bitrix\Main\Context::getCurrent()->getRequest()->toArray();
	if (isset($request['crm_calendar_action']))
	{
		$settingsFilterSelect = CUserOptions::GetOption("calendar", "resourceBooking");

		$entries = array();
		for ($i = 0, $l = count($arResult['GRID_DATA']); $i < $l; $i++)
		{
			$crmEntity = $arResult['GRID_DATA'][$i];

			$fields = array(
				'ID' => $crmEntity['data']['ID'],
				'COLOR' => '#2FC6F6',
				'NAME' => $crmEntity['data']['LEAD_SUMMARY'],
				'OPEN_URL' => $crmEntity['data']['PATH_TO_LEAD_DETAILS']
			);

			if ($filterSelect == 'DATE_CREATE')
			{
				$fields['DATE_FROM'] = $crmEntity['data']['DATE_CREATE'];
				$fields['DATE_TO'] = $crmEntity['data']['DATE_CREATE'];
				$fields['SKIP_TIME'] = false;
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
	"DEFAULT_SECTION_NAME" => 'calendar#lead',
	"DEFAULT_SECTION_COLOR" => '#2FC6F6',
	"NEW_ENTRY_NAME" => Loc::getMessage('CRM_CALENDAR_NEW_LEAD_NAME'),
	"COLLAPSED_ENTRIES_NAME" => Loc::getMessage('CRM_CALENDAR_COLLAPSED_LEAD_NAME'),
	"AVILABLE_VIEWS" => array('day', 'week', 'month'),
	"ADDITIONAL_VIEW_MODES" => $arParams['CALENDAR_MODE_LIST'],
	"USE_VIEW_TARGET" => "N"
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
				url = '<?= Calendar::prepareNewEntityUrlFromCalendar($newLeadUrl, $filterSelect)?>',
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
			BX.userOptions.save('calendar', 'resourceBooking', '<?= CCrmOwnerType::LeadName?>', params.id);
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
			var regRes = new RegExp('<?= $leadSliderRegexp?>.*', 'ig').exec(event.getSliderPage().getUrl());
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
				text: '<?= Loc::getMessage('CRM_CALENDAR_USERFIELD_HELP_MENU')?>',
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
