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

use Bitrix\Crm\Integration\Calendar;
use Bitrix\Crm\UI\NavigationBarPanel;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");

if (SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable())
{
	CBitrix24::initLicenseInfoPopupJS();
}

Bitrix\Main\UI\Extension::load(['ui.fonts.opensans', 'crm.autorun']);

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/progress_control.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');

$calendarId = 'CrmCalendarLeadListGrid';
$newLeadUrl = preg_replace("/#lead_id#/i", "0", $arParams['PATH_TO_LEAD_DETAILS']);
$leadSliderRegexp = preg_replace("/#lead_id#/i", "(\\\\\d+)", $arParams['PATH_TO_LEAD_DETAILS']);

?><div id="rebuildMessageWrapper"><?
if (isset($arResult['NEED_FOR_REBUILD_DUP_INDEX']) && $arResult['NEED_FOR_REBUILD_DUP_INDEX']):
	?><div id="rebuildLeadDupIndexMsg" class="crm-view-message">
		<?=GetMessage('CRM_LEAD_REBUILD_DUP_INDEX', array('#ID#' => 'rebuildLeadDupIndexLink', '#URL#' => '#'))?>
	</div><?
endif;

if (!empty($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']))
{
	?><div id="rebuildLeadSearchWrapper"></div><?
}

if (!empty($arResult['NEED_FOR_BUILD_TIMELINE']))
{
	?><div id="buildLeadTimelineWrapper"></div><?
}

if (!empty($arResult['NEED_FOR_REFRESH_ACCOUNTING']))
{
	?><div id="refreshLeadAccountingWrapper"></div><?
}

if (!empty($arResult['NEED_FOR_REBUILD_LEAD_ATTRS'])):
	?><div id="rebuildLeadAttrsMsg" class="crm-view-message">
	<?=GetMessage('CRM_LEAD_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildLeadAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
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
$currentUserID = $arResult['CURRENT_USER_ID'];
$activityEditorID = '';
if (!$isInternal):
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

$gridManagerID = $arResult['GRID_ID'] . '_MANAGER';

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

$arResult['GRID_DATA'] = [];
$arColumns = [];
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}

$now = time() + CTimeZone::GetOffset();

foreach($arResult['LEAD'] as $sKey => $arLead)
{
	$arActivityMenuItems = [];
	$arActivitySubMenuItems = [];
	$arActions = [];

	$dateCreate = $arLead['DATE_CREATE'] ?? '';
	$dateModify = $arLead['DATE_MODIFY'] ?? '';
	$isReturnCustomer = null;
	if (isset($arLead['IS_RETURN_CUSTOMER']))
	{
		$isReturnCustomer = $arResult['BOOLEAN_VALUES_LIST'][$arLead['IS_RETURN_CUSTOMER']] ?? $arLead['IS_RETURN_CUSTOMER'];
	}

	$webformId = null;
	if (isset($arLead['WEBFORM_ID']))
	{
		$webformId = $arResult['WEBFORM_LIST'][$arLead['WEBFORM_ID']] ?? $arLead['WEBFORM_ID'];
	}

	$honorific = '';
	if (isset($arLead['HONORIFIC']))
	{
		$honorific = $arResult['HONORIFIC'][$arLead['HONORIFIC']] ?? $arLead['HONORIFIC'];
	}

	$resultItem = array(
		'id' => $arLead['ID'],
		'data' => $arLead,
		'editable' => !$arLead['EDIT'] ? $arColumns : true,
		'columns' => array(
			'LEAD_SUMMARY' => CCrmViewHelper::RenderInfo(
				$arLead['PATH_TO_LEAD_SHOW'] ?? '',
				$arLead['TITLE'] ?? ('['.$arLead['ID'].']'),
				$arLead['LEAD_SOURCE_NAME'] ?? '',
				array('TARGET' => '_self')
			),
			'COMMENTS' => htmlspecialcharsback($arLead['COMMENTS'] ?? ''),
			'ADDRESS' => nl2br($arLead['ADDRESS'] ?? ''),
			'ASSIGNED_BY' => isset($arLead['~ASSIGNED_BY_ID']) && $arLead['~ASSIGNED_BY_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "LEAD_{$arLead['~ID']}_RESPONSIBLE",
						'USER_ID' => $arLead['~ASSIGNED_BY_ID'],
						'USER_NAME'=> $arLead['ASSIGNED_BY'],
						'USER_PROFILE_URL' => $arLead['PATH_TO_USER_PROFILE']
					)
				)
				: '',
			'STATUS_DESCRIPTION' => nl2br($arLead['STATUS_DESCRIPTION'] ?? ''),
			'SOURCE_DESCRIPTION' => nl2br($arLead['SOURCE_DESCRIPTION'] ?? ''),
			'DATE_CREATE' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($dateCreate), $now),
			'DATE_MODIFY' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($dateModify), $now),
			'SUM' => $arLead['FORMATTED_OPPORTUNITY'],
			'OPPORTUNITY' => $arLead['~OPPORTUNITY'] ?? 0.0,
			'CURRENCY_ID' => CCrmCurrency::GetCurrencyName($arLead['~CURRENCY_ID'] ?? null),
			'PRODUCT_ID' => isset($arLead['PRODUCT_ROWS'])
				? htmlspecialcharsbx(CCrmProductRow::RowsToString($arLead['PRODUCT_ROWS']))
				: '',
			'IS_RETURN_CUSTOMER' => $isReturnCustomer,
			'HONORIFIC' => $honorific,
			'STATUS_ID' => CCrmViewHelper::RenderLeadStatusControl(
				array(
					'PREFIX' => "{$arResult['GRID_ID']}_PROGRESS_BAR_",
					'ENTITY_ID' => $arLead['~ID'],
					'CURRENT_ID' => $arLead['~STATUS_ID'] ?? null,
					'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.list/list.ajax.php',
					'CONVERSION_SCHEME' => $arResult['CONVERSION_SCHEME'] ?? null,
					'READ_ONLY' => !(isset($arLead['EDIT']) && $arLead['EDIT'] === true)
				)
			),
			'SOURCE_ID' => $arLead['LEAD_SOURCE_NAME'] ?? '',
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
		) + CCrmViewHelper::RenderListMultiFields($arLead, "LEAD_{$arLead['ID']}_", array('ENABLE_SIP' => true, 'SIP_PARAMS' => array('ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::LeadName, 'ENTITY_ID' => $arLead['ID']))) + $arResult['LEAD_UF'][$sKey]
	);

	if (isset($arLead['~BIRTHDATE']))
	{
		$resultItem['columns']['BIRTHDATE'] = '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arLead['~BIRTHDATE'])).'</nobr>';
	}

	$arResult['GRID_DATA'][] = &$resultItem;
	unset($resultItem);
}

//region Filter
//Skip rendering of grid filter for internal grid request (filter already created)
if(!Bitrix\Main\Grid\Context::isInternalRequest()
	&& isset($arResult['FILTER']) && isset($arResult['FILTER_PRESETS']))
{
	$lazyLoadPath = '/bitrix/components/bitrix/crm.lead.list/filter.ajax.php'
		. '?filter_id=' . urlencode($arResult['GRID_ID']) . '&siteID=' . SITE_ID . '&' . bitrix_sessid_get()
	;
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.filter',
		($arParams['~FILTER_TEMPLATE'] ?? 'title'),
		[
			'GRID_ID' => $arResult['GRID_ID'],
			'FILTER_ID' => $arResult['GRID_ID'],
			'FILTER' => $arResult['FILTER'],
			'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
			'NAVIGATION_BAR' => (new NavigationBarPanel(CCrmOwnerType::Lead))
				->setItems([
					NavigationBarPanel::ID_KANBAN,
					NavigationBarPanel::ID_LIST,
					NavigationBarPanel::ID_ACTIVITY,
					NavigationBarPanel::ID_CALENDAR,
					NavigationBarPanel::ID_AUTOMATION
				], NavigationBarPanel::ID_CALENDAR)
				->setBinding($arResult['NAVIGATION_CONTEXT_ID'])
				->get(),
			'LIMITS' => ($arResult['LIVE_SEARCH_LIMIT_INFO'] ?? null),
			'ENABLE_LIVE_SEARCH' => true,
			'DISABLE_SEARCH' => isset($arParams['~DISABLE_SEARCH']) && $arParams['~DISABLE_SEARCH'] === true,
			'LAZY_LOAD' => [
				'GET_LIST' => $lazyLoadPath . '&action=list',
				'GET_FIELD' => $lazyLoadPath . '&action=field',
				'GET_FIELDS' => $lazyLoadPath . '&action=fields',
			],
			'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => (bool)(
				$arParams['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] ?? \Bitrix\Main\ModuleManager::isModuleInstalled('ui')
			),
			'RESTRICTED_FIELDS' => $arResult['RESTRICTED_FIELDS'] ?? [],
			'ENABLE_FIELDS_SEARCH' => 'Y',
			'HEADERS_SECTIONS' => $arResult['HEADERS_SECTIONS'],
			'CONFIG' => [
				'popupColumnsCount' => 4,
				'popupWidth' => 800,
				'showPopupInCenter' => true,
			],
		],
		$component,
		[
			'HIDE_ICONS' => 'Y',
		]
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
$filterSelectId = null;
$filterSelectType = null;
$filterSelectName = null;
$parsedKeys = Calendar::parseUserfieldKey($filterSelect);
if (count($parsedKeys) > 1)
{
	[$filterSelectId, $filterSelectType, $filterSelectName] = $parsedKeys;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid())
{
	$request = \Bitrix\Main\Context::getCurrent()->getRequest()->toArray();
	if (isset($request['crm_calendar_action']))
	{
		$settingsFilterSelect = CUserOptions::GetOption("calendar", "resourceBooking");

		$entries = [];
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

if (!empty($arResult['RESTRICTED_FIELDS_ENGINE']))
{
	Bitrix\Main\UI\Extension::load(['crm.restriction.filter-fields']);

	echo $arResult['RESTRICTED_FIELDS_ENGINE'];
}

?>

<script>
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
					loader: "crm-entity-details-loader",
					label: {
						text: "<?= Loc::getMessage('CRM_COMMON_LEAD')?>",
						bgColor: "#55D0E0",
					},
					width: window.innerWidth < 1500
						? null
						: 1500 + Math.floor((window.innerWidth - 1500) / 3)
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

			BX.SidePanel.Instance.open(
				url,
				{
					cacheable: false,
					loader: "crm-entity-details-loader",
					label: {
						text: "<?= Loc::getMessage('CRM_COMMON_LEAD')?>",
						bgColor: "#55D0E0",
					},
					width: window.innerWidth < 1500
						? null
						: 1500 + Math.floor((window.innerWidth - 1500) / 3)
				}
			);
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
