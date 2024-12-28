<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;
use Bitrix\Tasks\Helper\RestrictionUrl;
use Bitrix\Tasks\Integration\Recyclebin\Task;
use Bitrix\Tasks\Integration\Socialnetwork\Context\Context;
use Bitrix\Tasks\UI\ScopeDictionary;
use Bitrix\UI\Toolbar\Facade\Toolbar;

$isIFrame = isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y';

Loc::loadMessages(__FILE__);

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'popup',
	'tooltip',
	'tasks_util_query',
	'task_info_popup',
	'task-popups',
	'CJSTask',
	'ui.counter',
	'ui.avatar',
]);

$isCollab = isset($arResult['CONTEXT']) && $arResult['CONTEXT'] === Context::getCollab();
$collabClass = $isCollab ? 'sn-collab-tasks__wrapper' : '';

if ($isCollab)
{
	Toolbar::deleteFavoriteStar();
	$this->SetViewTarget('in_pagetitle') ?>

	<div class="sn-collab-icon__wrapper">
		<div id="sn-collab-icon-<?=HtmlFilter::encode($arResult["OWNER_ID"])?>" class="sn-collab-icon__hexagon-bg"></div>
	</div>
	<div class="sn-collab__subtitle"><?=HtmlFilter::encode($arResult["COLLAB_NAME"])?></div>
	<?php
	$this->EndViewTarget();
}
/** intranet-settings-support */
if (($arResult['IS_TOOL_AVAILABLE'] ?? null) === false)
{
	$APPLICATION->IncludeComponent("bitrix:tasks.error", "limit", [
		'LIMIT_CODE' => RestrictionUrl::TASK_LIMIT_OFF_SLIDER_URL,
		'SOURCE' => 'calendar',
	]);

	return;
}

$APPLICATION->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/script.js");
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/gantt-view.js");
$APPLICATION->AddHeadScript("/bitrix/js/tasks/task-iframe-popup.js");

$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");
$APPLICATION->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass . " " : "") . "page-one-column"  . " " . $collabClass);
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
	"PATH_TO_TASKS_TASK" => (isset($arParams['GROUP_ID']) && $arParams['GROUP_ID'] > 0) ? $arParams['PATH_TO_GROUP_TASKS_TASK'] : $arParams["PATH_TO_USER_TASKS_TASK"],
	"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"] ?? null
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
	$price = \CBitrix24::ConvertCurrency($arProductPrices["TF1"]["PRICE"] ?? 0, $billingCurrency);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid())
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

<script>
BX.message({
	TASKS_DELETE_SUCCESS: '<?= Loader::includeModule('recyclebin') ? Task::getDeleteMessage((int)$arParams['USER_ID']) : Loc::getMessage('TASKS_DELETE_SUCCESS') ?>',
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

		if (BX.type.isDate(params.params.viewRange.start))
		{
			data.deadlineFrom = BX.date.format(
				BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')),
				params.params.viewRange.start.getTime() / 1000
			);
		}
		if (BX.type.isDate(params.params.viewRange.end))
		{
			data.deadlineTo = BX.date.format(
				BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')),
				params.params.viewRange.end.getTime() / 1000
			);
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
			const openUrl = BX.Uri.addParam(params.entry.data.OPEN_URL, {
				ta_sec: '<?= !empty($currentGroupId) ? 'project' : 'tasks' ?>',
				ta_sub: 'calendar',
				ta_el: 'title_click',
			});

			BX.SidePanel.Instance.open(openUrl,
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
			let url = '<?= CUtil::JSEscape($editTaskPath)?>';
			const fromDate = BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')), params.entryTime.from.getTime() / 1000);
			const toDate = BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')), params.entryTime.to.getTime() / 1000);

			if (fromDate)
			{
				url = url.replace('#START_DATE_PLAN#', BX.util.urlencode(fromDate));
			}

			if (toDate)
			{
				url = url.replace('#END_DATE_PLAN#', BX.util.urlencode(toDate));
				url = url.replace('#DEADLINE#', BX.util.urlencode(toDate));
			}

			const createUrl = BX.Uri.addParam(url, {
				ta_sec: '<?= !empty($currentGroupId) ? 'project' : 'tasks' ?>',
				ta_sub: 'calendar',
				ta_el: 'quick_button',
			});

			BX.SidePanel.Instance.open(createUrl, {loader: "task-new-loader"});
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
	<?php if ($isCollab): ?>
		const collabImagePath = "<?=$arResult["COLLAB_IMAGE"]?>" || null;
		const collabName = "<?=HtmlFilter::encode($arResult["COLLAB_NAME"])?>";
		const ownerId = "<?=HtmlFilter::encode($arResult["OWNER_ID"])?>";
		const avatar = new BX.UI.AvatarHexagonGuest({
			size: 42,
			userName: collabName.toUpperCase(),
			baseColor: '#19CC45',
			userpicPath: collabImagePath,
		});
		avatar.renderTo(BX('sn-collab-icon-' + ownerId));
	<?php endif; ?>
});
//endregion

//region Tasks.TopMenu::onItem
BX.addCustomEvent('Tasks.TopMenu:onItem', function(roleId, url) {
	var filterManager = BX.Main.filterManager.getById("<?=HtmlFilter::encode($arParams["FILTER_ID"])?>");
	if (!filterManager)
	{
		alert('BX.Main.filterManager not initialised');
		return;
	}

	var fields = {
		preset_id: "<?=HtmlFilter::encode($arResult["DEFAULT_PRESET_KEY"])?>",
		additional: {ROLEID: (roleId === 'view_all' ? 0 : roleId)}
	};
	var filterApi = filterManager.getApi();
	filterApi.setFilter(fields, {ROLE_TYPE: 'TASKS_ROLE_TYPE_' + (roleId === '' ? 'view_all' : roleId)});

	window.history.pushState(null, null, url);
});
//endregion

//region Tasks.Toolbar:onItem
BX.addCustomEvent('Tasks.Toolbar:onItem', function(event) {
	var data = event.getData();
	if (data.counter && data.counter.filter)
	{
		data.counter.filter.toggleByField({PROBLEM: data.counter.filterValue});
	}
});
//endregion

</script>

<?php
$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";
if ($isBitrix24Template)
{
	$this->SetViewTarget('inside_pagetitle');
}

if ($arResult['CONTEXT'] !== Context::getSpaces())
{
	$APPLICATION->IncludeComponent(
		'bitrix:tasks.interface.header',
		'',
		array(
			'FILTER_ID' => $arParams["FILTER_ID"] ?? null,
			'GRID_ID' => $arParams["GRID_ID"] ?? null,
			'FILTER' => $arResult['FILTER'] ?? null,
			'PRESETS' => $arResult['PRESETS'] ?? null,
			'SHOW_QUICK_FORM' => 'N',
			'PROJECT_VIEW' => $arParams['PROJECT_VIEW'] ?? null,
			'GET_LIST_PARAMS' => $arResult['GET_LIST_PARAMS'] ?? null,
			'COMPANY_WORKTIME' => $arResult['COMPANY_WORKTIME'] ?? null,
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'] ?? null,
			'USER_ID' => $arParams['USER_ID'] ?? null,
			'GROUP_ID' => $arParams['GROUP_ID'] ?? null,
			'MARK_ACTIVE_ROLE' => $arParams['MARK_ACTIVE_ROLE'] ?? null,
			'MARK_SECTION_ALL' => $arParams['MARK_SECTION_ALL'] ?? null,
			'MARK_SPECIAL_PRESET' => $arParams['MARK_SPECIAL_PRESET'] ?? null,
			'MARK_SECTION_PROJECTS' => $arParams['MARK_SECTION_PROJECTS'] ?? null,
			'PATH_TO_USER_TASKS' => $arParams['PATH_TO_USER_TASKS'] ?? null,
			'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_USER_TASKS_TASK'] ?? null,
			'PATH_TO_USER_TASKS_VIEW' => $arParams['PATH_TO_USER_TASKS_VIEW'] ?? null,
			'PATH_TO_USER_TASKS_REPORT' => $arParams['PATH_TO_USER_TASKS_REPORT'] ?? null,
			'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'] ?? null,
			'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'] ?? null,
			'PATH_TO_GROUP' => $arParams['PATH_TO_GROUP'] ?? null,
			'PATH_TO_GROUP_TASKS' => $arParams['PATH_TO_GROUP_TASKS'] ?? null,
			'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'] ?? null,
			'PATH_TO_GROUP_TASKS_VIEW' => $arParams['PATH_TO_GROUP_TASKS_VIEW'] ?? null,
			'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'] ?? null,
			'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'] ?? null,
			'PATH_TO_MESSAGES_CHAT' => $arParams['PATH_TO_MESSAGES_CHAT'] ?? null,
			'PATH_TO_VIDEO_CALL' => $arParams['PATH_TO_VIDEO_CALL'] ?? null,
			'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT'] ?? null,
			'USE_EXPORT' => 'N',
			'USE_GROUP_BY_SUBTASKS' => 'N',
			'USE_GROUP_BY_GROUPS' => 'N',
			'GROUP_BY_PROJECT' => 'N',
			'SHOW_USER_SORT' => 'N',
			'SORT_FIELD' => $arParams['SORT_FIELD'] ?? null,
			'SORT_FIELD_DIR' => $arParams['SORT_FIELD_DIR'] ?? null,
			'USE_LIVE_SEARCH' => 'Y',
			'SHOW_SECTION_TEMPLATES' => (isset($arParams['GROUP_ID']) && $arParams['GROUP_ID'] > 0) ? 'N' : 'Y',
			'DEFAULT_ROLEID' => $arParams['DEFAULT_ROLEID'] ?? null,
			'USE_AJAX_ROLE_FILTER' => 'Y',
			'SCOPE' => ScopeDictionary::SCOPE_TASKS_CALENDAR,
			'CONTEXT' => $arResult['CONTEXT'] ?? null,
			'CONTEXT' => $arResult['CONTEXT'] ?? null,
		),
		$component,
		array('HIDE_ICONS' => true)
	);
}

if (
	isset($arResult['ERROR']['FATAL'])
	&& is_array($arResult['ERROR']['FATAL'])
	&& !empty($arResult['ERROR']['FATAL'])
)
{
	foreach ($arResult['ERROR']['FATAL'] as $error)
	{
		ShowError($error['MESSAGE']);
	}
	return;
}

if (
	isset($arResult['ERROR']['WARNING'])
	&& is_array($arResult['ERROR']['WARNING'])
)
{
	foreach ($arResult['ERROR']['WARNING'] as $error)
	{
		ShowError($error['MESSAGE']);
	}
}

if ($isBitrix24Template)
{
	$this->EndViewTarget();
}
