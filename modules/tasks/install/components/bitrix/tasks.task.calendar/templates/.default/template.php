<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

$isIFrame = $_REQUEST['IFRAME'] == 'Y';

Loc::loadMessages(__FILE__);
CUtil::InitJSCore(array('popup', 'tooltip', 'tasks_util_query', 'task_info_popup', 'task-popups', 'CJSTask'));

$APPLICATION->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/script.js");
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/gantt-view.js");
$APPLICATION->AddHeadScript("/bitrix/js/tasks/task-iframe-popup.js");

$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");
$APPLICATION->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass . " " : "") . "page-one-column");

if (\Bitrix\Tasks\Util\DisposableAction::needConvertTemplateFiles())
{
	$APPLICATION->IncludeComponent("bitrix:tasks.util.process",
		'',
		array(),
		false,
		array("HIDE_ICONS" => "Y")
	);
}

$APPLICATION->IncludeComponent(
	'bitrix:main.calendar',
	'',
	array(
		'SILENT' => 'Y',
	),
	null,
	array('HIDE_ICONS' => 'Y')
);

$arPaths = array(
	"PATH_TO_TASKS_TASK" => $arParams['GROUP_ID'] > 0 ? $arParams['PATH_TO_GROUP_TASKS_TASK'] : $arParams["PATH_TO_USER_TASKS_TASK"],
	"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"]
);

$APPLICATION->IncludeComponent(
	"bitrix:tasks.iframe.popup",
	".default",
	array(
		//		"ON_BEFORE_HIDE" => "onBeforeHide",
		//		"ON_AFTER_HIDE" => "onAfterHide",
		//		"ON_BEFORE_SHOW" => "onBeforeShow",
		//		"ON_AFTER_SHOW" => "onAfterShow",


		//		"ON_TASK_ADDED" => "onPopupTaskAdded",
		//		'ON_TASK_ADDED_MULTIPLE' => 'onPopupTaskAdded',
		//		"ON_TASK_CHANGED" => "onPopupTaskChanged",
		//		"ON_TASK_DELETED" => "onPopupTaskDeleted"
	),
	null,
	array("HIDE_ICONS" => "Y")
);

$cs = \Bitrix\Tasks\UI::translateCalendarSettings($arResult['CALENDAR_SETTINGS']);

$holidays = $cs['HOLIDAYS'];
$hours = $cs['HOURS'];
$weekEnds = $cs['WEEK_END'];
$weekStart = $cs['WEEK_START'];

if (Loader::IncludeModule('bitrix24'))
{
	$APPLICATION->IncludeComponent("bitrix:bitrix24.limit.lock", "", array(
		"FEATURE_GROUP_NAME" => "tasks"
	));

	$billingCurrency = \CBitrix24::BillingCurrency();
	$arProductPrices = \CBitrix24::getPrices($billingCurrency);
	$price = \CBitrix24::ConvertCurrency($arProductPrices["TF1"]["PRICE"], $billingCurrency);

	$trialTitle = GetMessageJS('TASKS_LIST_TRIAL_EXPIRED_TITLE_V2');
	$trialMessage = preg_replace(
		"#(\r\n|\n)#",
		"<br />",
		GetMessageJS('TASKS_LIST_TRIAL_EXPIRED_TEXT', array('#PRICE#' => $price))
	);
}

$currentGroupId = $arParams['GROUP_ID'];

$calendarId = 'TaskCalendarList'.rand();
$taskColor = '#FFA900';
$selectedField = false;

$uri = new \Bitrix\Main\Web\Uri(\Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest()->getRequestUri());
$uri->deleteParams(\Bitrix\Main\HttpRequest::getSystemParameters());
$uri->addParams(['sessid' => bitrix_sessid()]);
$requestUri = $uri->getUri();

$viewTaskPath = str_replace(['#user_id#', '#action#', '#group_id#'], [$arParams["USER_ID"], 'view', $arParams['GROUP_ID']], $arPaths['PATH_TO_TASKS_TASK']);
$editTaskPath = str_replace(['#user_id#', '#action#', '#task_id#', '#group_id#'], [$arParams["USER_ID"], 'edit', 0, $arParams['GROUP_ID']],
$arPaths['PATH_TO_TASKS_TASK']);
$editTaskPath = CHTTP::urlAddParams($editTaskPath, ['DEADLINE' => '#DEADLINE#']);

$tasksSliderRegexp = str_replace(array('#user_id#', '#action#'), array($arParams["USER_ID"], 'edit'), $arPaths['PATH_TO_TASKS_TASK']);
$tasksSliderRegexp = preg_replace("/#task_id#/i", "(\\\\\d+)", $tasksSliderRegexp).'.*';

$filterSelect = 'DEADLINE';

$allowWrite = true;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	$request = \Bitrix\Main\Context::getCurrent()->getRequest()->toArray();
	if (isset($request['task_calendar_action']) && $request['task_calendar_action'] === 'LOAD_ENTRIES')
	{
		$entries = [];
		$offset = \CCalendar::GetOffset();
		foreach($arResult["LIST"] as $k => $taskEntity)
		{
			if (isset($taskEntity["DEADLINE"]))
			{
				$fields = [
					'ID' => $taskEntity['ID'],
					'COLOR' => $taskColor,
					'NAME' => $taskEntity['TITLE'],
					'OPEN_URL' => preg_replace("/#task_id#/", $taskEntity['ID'], $viewTaskPath)
				];

				$deadline = $taskEntity["DEADLINE"];
				$fields['DATE_FROM'] = $deadline;
				$fields['DATE_TO'] = $deadline;
				$fields['SKIP_TIME'] = false;
				$fields['DURATION'] = 0;
				$fields['ALLOW_DRAGDROP'] = $taskEntity['ACTION'] && $taskEntity['ACTION']['CHANGE_DEADLINE'];

				$entries[] = $fields;
			}
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
	"ENTITY_TYPE" => 'tasks',
	"EXTERNAL_DATA_HANDLE_MODE" => true,
	"READONLY" => !$allowWrite,
	"SHOW_FILTER" => false,
	"SHOW_SECTION_SELECTOR" => false,
	"SHOW_SETTINGS_BUTTON" => false,
	"SHOW_TOP_VIEW_SWITCHER" => false,
	"DEFAULT_SECTION_NAME" => 'calendar#task',
	"DEFAULT_SECTION_COLOR" => $taskColor,
	"NEW_ENTRY_NAME" => Loc::getMessage('TASKS_CALENDAR_NEW_TASK'),
	"COLLAPSED_ENTRIES_NAME" => Loc::getMessage('TASKS_CALENDAR_COLLAPSED_ENTRIES_NAME'),
	"AVILABLE_VIEWS" => array('day', 'week', 'month'),
	//"ADDITIONAL_VIEW_MODES" => $arParams['CALENDAR_MODE_LIST']
));
//endregion
?>

<script type="text/javascript">
BX.message({
	TASKS_DELETE_SUCCESS: '<?= Loc::getMessage('TASKS_DELETE_SUCCESS')?>',
	TASKS_CALENDAR_NOTIFY_CHANGE_DEADLINE: '<?= Loc::getMessage('TASKS_CALENDAR_NOTIFY_CHANGE_DEADLINE')?>'
});

//region Javascript External Handlers calendar
BX.ready(function(){
	var eventCalendar = window.BXEventCalendar.Get('<?= $calendarId?>');
	BX.addCustomEvent(eventCalendar, 'loadEntries', function(params)
	{
		var data = {
			task_calendar_action: 'LOAD_ENTRIES'
		};

		if (BX.type.isDate(params.params.startDate))
		{
			data.deadlineFrom = BX.date.format(
				BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')),
				params.params.startDate.getTime() / 1000);
		}
		if (BX.type.isDate(params.params.finishDate))
		{
			data.deadlineTo = BX.date.format(
				BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')),
				params.params.finishDate.getTime() / 1000);
		}

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			data: data,
			url: '<?= CUtil::JSEscape($requestUri)?>',
			onsuccess: function(json)
			{
				eventCalendar.entryController.clearLoadIndexCache();
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
				loader: "task-new-loader"
			});
		}
	});

	<?if ($allowWrite):?>
	BX.addCustomEvent(eventCalendar, 'createNewEntry', function(params)
	{
		if (params)
		{
			var
				url = '<?= CUtil::JSEscape($editTaskPath)?>',
				fromDate = BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')), params.entryTime.from.getTime() / 1000),
				toDate = BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')), params.entryTime.to.getTime() / 1000);

			if (fromDate)
			{
				url = url.replace('#START_DATE_PLAN#', BX.util.urlencode(fromDate));
			}

			if (toDate)
			{
				url = url.replace('#END_DATE_PLAN#', BX.util.urlencode(toDate));
				url = url.replace('#DEADLINE#', BX.util.urlencode(toDate));
			}

			BX.SidePanel.Instance.open(url, {loader: "task-new-loader"});
		}
	});
	<?endif;?>

	// Handle of changing type of field which is used to display entries on calendar grid
	BX.addCustomEvent(eventCalendar, 'changeViewMode', function(params)
	{
		if (params && params.id)
		{
			BX.userOptions.save('tasks', 'calendarViewOptions', 'calendarViewMode', params.id);
			if (eventCalendar.showLoader)
			{
				eventCalendar.showLoader();
			}
			BX.reload();
		}
	});

	BX.addCustomEvent('tasksTaskEvent', function(params)
	{
		if (eventCalendar && eventCalendar.reload)
		{
			eventCalendar.reload();
		}
	});

	// Handle closing of slider and check if we should reload calendar
	BX.addCustomEvent("SidePanel.Slider:onClose", function(event)
	{
		if (event && event.getSliderPage && eventCalendar)
		{
			var
				url = event.getSliderPage().getUrl(),
				regRes = new RegExp('<?= $tasksSliderRegexp?>', 'ig').exec(url);

			if (BX.type.isArray(regRes) && regRes[1] !== undefined)
			{
				eventCalendar.reload();
			}
		}
	});

	// Handler to refresh calendar as a reaction for CRM fiter changes
	var filterId = "<?= HtmlFilter::encode($arParams["FILTER_ID"])?>";
	BX.addCustomEvent('BX.Main.Filter:apply', function(id, data, ctx, promise, params)
	{
		if (id === filterId && window.BXEventCalendar)
		{
			var eventCalendar = window.BXEventCalendar.Get('<?= $calendarId?>');
			if (eventCalendar)
			{
				eventCalendar.reload();
			}
		}
	});

	BX.addCustomEvent(eventCalendar, 'entryOnDragEnd', function(params)
	{
		eventCalendar.entryController.clearLoadIndexCache();
		var
			previousDeadline = params.previousDateTo || params.previousDateFrom,
			deadline = params.dateTo || params.dateFrom,
			deadlineText = eventCalendar.util.formatDateUsable(deadline, false);

		if (BX.type.isDate(previousDeadline) && BX.type.isDate(deadline)
			&& previousDeadline.getFullYear() === deadline.getFullYear()
			&& previousDeadline.getMonth() === deadline.getMonth()
			&& previousDeadline.getDate() === deadline.getDate()
		)
		{
			deadlineText += ' ' + eventCalendar.util.formatTime(deadline.getHours(), deadline.getMinutes(), true);
		}

		BX.loadExt('ui.notification').then(function(){
			BX.UI.Notification.Center.notify({
				content: BX.message('TASKS_CALENDAR_NOTIFY_CHANGE_DEADLINE').replace("#date#", deadlineText)
			});
		});

		BX.ajax.runComponentAction('<?= $this->getComponent()->getName()?>',
			'changeDeadline',
			{
				data: {
					taskId: parseInt(params.entry.id),
					deadline: BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')), deadline)
				}
			}).then(function (response)
			{
			});
	});
});
//endregion

//region Tasks.Toolbar:onItem
BX.addCustomEvent('Tasks.Toolbar:onItem', function(counterId)
{
	var
		fields, f,
		filterId = "<?= HtmlFilter::encode($arParams["FILTER_ID"])?>",
		defaultPresetId = "<?= HtmlFilter::encode($arResult["DEFAULT_PRESET_KEY"])?>",
		filterManager = BX.Main.filterManager.getById(filterId);

	if(!filterManager)
	{
		alert('BX.Main.filterManager not initialised');
		return;
	}
	var filterApi = filterManager.getApi();
	if(Number(counterId) === <?= \CTaskListState::VIEW_TASK_CATEGORY_WAIT_CTRL?>)
	{
		fields = {STATUS:{0:'4'}};
		f = filterManager.getFilterFieldsValues();
		if (f.hasOwnProperty('ROLEID') && f.ROLEID != '')
		{
			fields.ROLEID = f.ROLEID;
		}
		else
		{
			fields.ROLEID = 'view_role_originator';
		}

		filterApi.setFields(fields);
		filterApi.apply();
	}
	else
	{
		fields = {additional:{}};
		f = filterManager.getFilterFieldsValues();
		if(f.hasOwnProperty('ROLEID'))
		{
			fields.additional.ROLEID = f.ROLEID;
		}
		fields.preset_id= defaultPresetId;
		fields.additional.PROBLEM= counterId;
		filterApi.setFilter(fields);
	}
});
//endregion

</script>

<?php
if ($isBitrix24Template)
{
	$this->SetViewTarget('inside_pagetitle');
}
?>
<?php $APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.header',
	'',
	array(
		'FILTER_ID' => $arParams["FILTER_ID"],
		'GRID_ID' => $arParams["GRID_ID"],
		'FILTER' => $arResult['FILTER'],
		'PRESETS' => $arResult['PRESETS'],
		'SHOW_QUICK_FORM' => 'N',
		'GET_LIST_PARAMS' => $arResult['GET_LIST_PARAMS'],
		'COMPANY_WORKTIME' => $arResult['COMPANY_WORKTIME'],
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
		'GANTT_MODE' => true,
		'USER_ID' => $arParams['USER_ID'],
		'GROUP_ID' => $arParams['GROUP_ID'],
		'MARK_ACTIVE_ROLE' => $arParams['MARK_ACTIVE_ROLE'],
		'MARK_SECTION_ALL' => $arParams['MARK_SECTION_ALL'],
		'MARK_SPECIAL_PRESET' => $arParams['MARK_SPECIAL_PRESET'],
		'PATH_TO_USER_TASKS' => $arParams['PATH_TO_USER_TASKS'],
		'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_USER_TASKS_TASK'],
		'PATH_TO_USER_TASKS_VIEW' => $arParams['PATH_TO_USER_TASKS_VIEW'],
		'PATH_TO_USER_TASKS_REPORT' => $arParams['PATH_TO_USER_TASKS_REPORT'],
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],
		'PATH_TO_GROUP' => $arParams['PATH_TO_GROUP'],
		'PATH_TO_GROUP_TASKS' => $arParams['PATH_TO_GROUP_TASKS'],
		'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'],
		'PATH_TO_GROUP_TASKS_VIEW' => $arParams['PATH_TO_GROUP_TASKS_VIEW'],
		'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'],
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
		'PATH_TO_MESSAGES_CHAT' => $arParams['PATH_TO_MESSAGES_CHAT'],
		'PATH_TO_VIDEO_CALL' => $arParams['PATH_TO_VIDEO_CALL'],
		'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT'],
		'USE_EXPORT' => 'N',
		'USE_GROUP_BY_SUBTASKS' => 'N',
		'USE_GROUP_BY_GROUPS' => 'N',
		'GROUP_BY_PROJECT' => 'N',
		'SHOW_USER_SORT' => 'N',
		'SORT_FIELD'=>$arParams['SORT_FIELD'],
		'SORT_FIELD_DIR'=>$arParams['SORT_FIELD_DIR'],
		'USE_LIVE_SEARCH' => 'Y',
		'SHOW_SECTION_TEMPLATES'=>$arParams['GROUP_ID'] > 0 ? 'N' : 'Y',
		'DEFAULT_ROLEID'=>$arParams['DEFAULT_ROLEID'],
		'USE_AJAX_ROLE_FILTER'=>'Y'
	),
	$component,
	array('HIDE_ICONS' => true)
); ?>

<?php
if (is_array($arResult['ERROR']['FATAL']) && !empty($arResult['ERROR']['FATAL'])):
	foreach ($arResult['ERROR']['FATAL'] as $error):
		echo ShowError($error['MESSAGE']);
	endforeach;
	return;
endif
?>

<? if (is_array($arResult['ERROR']['WARNING'])): ?>
	<? foreach ($arResult['ERROR']['WARNING'] as $error): ?>
		<?= ShowError($error['MESSAGE']) ?>
	<? endforeach ?>
<? endif ?>
<?php
if ($isBitrix24Template)
{
	$this->EndViewTarget();
}
?>
