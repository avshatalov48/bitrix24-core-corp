<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

CUtil::InitJSCore(array('popup', 'tooltip', 'CJSTask', 'task_popups'));

// commented out probably wrong script $GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.task.edit/templates/.default/script.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/script.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/table-view.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/tasks/task-reminders.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/tasks/task-iframe-popup.js");

$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/main/core/css/core_popup.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");

$GLOBALS["APPLICATION"]->IncludeComponent(
	'bitrix:main.calendar',
	'',
	array(
		'SILENT' => 'Y',
	),
	null,
	array('HIDE_ICONS' => 'Y')
);

$arPaths = array(
	"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
	"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"]
);

$loggedInUser = $USER->getId();

$createUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"));
$createSubtaskUrl = $createUrl.(strpos($createUrl, "?") === false ? "?" : "&")."PARENT_ID=".$arResult["TASK"]["ID"];

$APPLICATION->IncludeComponent(
	"bitrix:tasks.iframe.popup",
	".default",
	array(
		"ON_TASK_ADDED" => "onPopupTaskAdded",
		"ON_TASK_CHANGED" => "onPopupTaskChanged",
		"ON_TASK_DELETED" => "onPopupTaskDeleted"
	),
	null,
	array("HIDE_ICONS" => "Y")
);

if (!defined('TASKS_MUL_INCLUDED')):
	$APPLICATION->IncludeComponent("bitrix:main.user.link",
		'',
		array(
			"AJAX_ONLY" => "Y",
			"PATH_TO_SONET_USER_PROFILE" => $arParams["~PATH_TO_USER_PROFILE"],
			"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["~PATH_TO_MESSAGES_CHAT"],
			"DATE_TIME_FORMAT" => $arParams["~DATE_TIME_FORMAT"],
			"SHOW_YEAR" => $arParams["SHOW_YEAR"],
			"NAME_TEMPLATE" => $arParams["~NAME_TEMPLATE"],
			"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
			"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
			"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
		),
		false,
		array("HIDE_ICONS" => "Y")
	);
	define('TASKS_MUL_INCLUDED', 1);
endif;

$loggedInUserWorkPosition = '';

if ($rs = CUser::getById($loggedInUser))
{
	if ($arUserData = $rs->fetch())
		$loggedInUserWorkPosition = $arUserData['WORK_POSITION'];
}

$loggedInUserFormattedName = CUser::FormatName(
	$arParams['~NAME_TEMPLATE'],
	array(
		'NAME' 			=> $USER->GetFirstName(), 
		'LAST_NAME' 	=> $USER->GetLastName(), 
		'SECOND_NAME' 	=> $USER->GetSecondName(), 
		'LOGIN'			=> $USER->GetLogin()
	),
	true,
	$bEscapeSpecChars = false
);
?>
<script type="text/javascript">
BX.message({
	TASKS_PRIORITY : '<?php echo CUtil::JSEscape(GetMessage("TASKS_PRIORITY")); ?>',
	TASKS_APPLY : '<?php echo CUtil::JSEscape(GetMessage("TASKS_APPLY")); ?>',
	TASKS_PRIORITY_LOW : '<?php echo CUtil::JSEscape(GetMessage("TASKS_PRIORITY_0")); ?>',
	TASKS_PRIORITY_MIDDLE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_PRIORITY_1")); ?>',
	TASKS_PRIORITY_HIGH : '<?php echo CUtil::JSEscape(GetMessage("TASKS_PRIORITY_2")); ?>',
	TASKS_DURATION : '<?php echo CUtil::JSEscape(GetMessage("TASKS_DURATION")); ?>',
	TASKS_SELECT : '<?php echo CUtil::JSEscape(GetMessage("TASKS_SELECT")); ?>',
	TASKS_OK : '<?php echo CUtil::JSEscape(GetMessage("TASKS_OK")); ?>',
	TASKS_CANCEL : '<?php echo CUtil::JSEscape(GetMessage("TASKS_CANCEL")); ?>',
	TASKS_DECLINE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_DECLINE_TASK")); ?>',
	TASKS_DECLINE_REASON : '<?php echo CUtil::JSEscape(GetMessage("TASKS_DECLINE_REASON")); ?>',
	TASKS_NO_TITLE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_NO_TITLE")); ?>',
	TASKS_NO_RESPONSIBLE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_NO_RESPONSIBLE")); ?>',
	TASKS_PATH_TO_USER_PROFILE : '<?php echo CUtil::JSEscape($arParams["PATH_TO_USER_PROFILE"]); ?>',
	TASKS_PATH_TO_TASK : '<?php echo CUtil::JSEscape($arParams["PATH_TO_TASKS_TASK"]); ?>',
	TASKS_PATH_TO_USER_TASKS_TASK : '<?php echo CUtil::JSEscape($arParams["PATH_TO_USER_TASKS_TASK"]); ?>',
	PATH_TO_GROUP_TASKS : '<?php echo CUtil::JSEscape($arParams["PATH_TO_GROUP_TASKS"]); ?>',
	PATH_TO_GROUP : '<?=CUtil::JSEscape($arParams["PATH_TO_GROUP"])?>',
	TASKS_HOURS_N : '<?php echo CUtil::JSEscape(GetMessage("TASKS_HOURS_N")); ?>',
	TASKS_HOURS_G : '<?php echo CUtil::JSEscape(GetMessage("TASKS_HOURS_G")); ?>',
	TASKS_HOURS_P : '<?php echo CUtil::JSEscape(GetMessage("TASKS_HOURS_P")); ?>',
	TASKS_REMINDER_TITLE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMINDER_TITLE")); ?>',
	TASKS_ABOUT_DEADLINE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_ABOUT_DEADLINE")); ?>',
	TASKS_BY_DATE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_BY_DATE")); ?>',
	TASKS_REMIND_BEFORE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMIND_BEFORE")); ?>',
	TASKS_REMIND_VIA_JABBER : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMIND_VIA_JABBER")); ?>',
	TASKS_REMIND_VIA_EMAIL : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMIND_VIA_EMAIL")); ?>',
	TASKS_REMIND_VIA_JABBER_EX : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMIND_VIA_JABBER_EX")); ?>',
	TASKS_REMIND_VIA_EMAIL_EX : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMIND_VIA_EMAIL_EX")); ?>',
	TASKS_REMINDER_OK : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMINDER_OK")); ?>',
	TASKS_DOUBLE_CLICK : '<?php echo CUtil::JSEscape(GetMessage("TASKS_DOUBLE_CLICK")); ?>',
	TASKS_MENU : '<?php echo CUtil::JSEscape(GetMessage("TASKS_MENU")); ?>',
	TASKS_FINISH : '<?php echo CUtil::JSEscape(GetMessage("TASKS_FINISH")); ?>',
	TASKS_FINISHED : '<?php echo CUtil::JSEscape(GetMessage("TASKS_FINISHED")); ?>',
	TASKS_QUICK_IN_GROUP : '<?php echo CUtil::JSEscape(GetMessage("TASKS_QUICK_IN_GROUP")); ?>',

	TASKS_ADD_TASK : '<?php echo CUtil::JSEscape(GetMessage("TASKS_ADD_TASK")); ?>',
	TASKS_DELETE_CONFIRM : '<?php echo CUtil::JSEscape(GetMessage("TASKS_DELETE_CONFIRM")); ?>',
	TASKS_DELETE_TASK_CONFIRM : '<?php echo CUtil::JSEscape(GetMessage("TASKS_DELETE_TASK_CONFIRM")); ?>',
	TASKS_DELETE_FILE_CONFIRM : '<?php echo CUtil::JSEscape(GetMessage("TASKS_DELETE_FILE_CONFIRM")); ?>',
	TASKS_FILES: '<?php echo CUtil::JSEscape(GetMessage("TASKS_TASK_FILES")); ?>',
	TASKS_START: '<?php echo GetMessageJS('TASKS_START')?>',
	TASKS_GROUP_ADD: '<?php echo CUtil::JSEscape(GetMessage("TASKS_GROUP_ADD")); ?>',
	TASKS_GROUP_LOADING: '<?php echo CUtil::JSEscape(GetMessage("TASKS_GROUP_LOADING")); ?>',
	TASKS_SIDEBAR_DEADLINE_NO: '<?php echo CUtil::JSEscape(GetMessage("TASKS_SIDEBAR_DEADLINE_NO")); ?>',
	TASKS_SIDEBAR_STOP_WATCH_CONFIRM : '<?php echo CUtil::JSEscape(GetMessage("TASKS_SIDEBAR_STOP_WATCH_CONFIRM")); ?>',
	TASKS_DATE_MUST_BE_IN_FUTURE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_DATE_MUST_BE_IN_FUTURE")); ?>',
	TASKS_LOGGED_IN_USER_FORMATTED_NAME : '<?php echo CUtil::JSEscape($loggedInUserFormattedName); ?>',
	TASKS_LOGGED_IN_USER_WORK_POSITION  : '<?php echo CUtil::JSEscape($loggedInUserWorkPosition); ?>',
	TASKS_CONTEXT_IS_IFRAME : '<?php if ($arResult['IS_IFRAME']) echo 'true'; else echo 'false'; ?>',
	TASKS_CONTEXT_PATH_TO_TEMPLATES_TEMPLATE : '<?php echo CUtil::JSEscape($arParams['PATH_TO_TEMPLATES_TEMPLATE']); ?>',
	TASKS_CONTEXT_PATH_TO_USER_PROFILE : '<?php echo CUtil::JSEscape($arParams['PATH_TO_USER_PROFILE']); ?>',
	TASKS_CONTEXT_PATH_TO_TASKS_TASK : '<?php echo CUtil::JSEscape($arParams['PATH_TO_TASKS_TASK']); ?>',
	TASKS_CONTEXT_PATH_TO_TASKS : '<?php echo CUtil::JSEscape($arParams['PATH_TO_TASKS']); ?>',
	TASKS_CONTEXT_NAME_TEMPLATE : '<?php echo CUtil::JSEscape($arParams['NAME_TEMPLATE']); ?>',
	TASKS_CONTEXT_TASK_ID : <?php echo (int) $arParams['TASK_ID']; ?>
});

// This needs for __onBeforeUnload preventer
var iframePopup = window.top.BX.TasksIFrameInst;
if (iframePopup)
{
	window.top.BX.TasksIFrameInst.isEditMode = false;
}

var taskData = <?php
	$bSkipJsMenu = false;
	$bIsIe = false;
	$userAgent = strtolower($_SERVER["HTTP_USER_AGENT"]);
	if (strpos($userAgent, "opera") === false && strpos($userAgent, "msie") !== false)
		$bIsIe = true;

	if (isset($arResult["IS_IFRAME"]) && ($arResult["IS_IFRAME"] === true) && $bIsIe)
		$bSkipJsMenu = true;

	tasksRenderJSON($arResult["TASK"], sizeof($arResult["SUBTASKS"]), $arPaths, true, true, true, $arParams["~NAME_TEMPLATE"], $arAdditionalFields = array(), $bSkipJsMenu);
?>;

if (!window.top.BX("gantt-container"))
{
	for(var i = taskData.menuItems.length - 1; i >= 0; i--)
	{
		if (taskData.menuItems[i].className == "task-menu-popup-item-add-deadline" || taskData.menuItems[i].className == "task-menu-popup-item-remove-deadline")
		{
			taskData.menuItems.splice (i, 1);
		}
	}
}

<?php if ($arResult["IS_IFRAME"] && ($arResult["CALLBACK"] == "CHANGED" || $arResult["CALLBACK"] == "ADDED")):?>
(function(){
	var html;
	var iframePopup = window.top.BX.TasksIFrameInst;

	if ( ! iframePopup )
		return;

	<?php
	ob_start();
	{
		$params = array(
			'PATHS'           =>  $arPaths,
			'PLAIN'           =>  false,
			'DEFER'           =>  true,
			'SITE_ID'         =>  SITE_ID,
			'TASK_ADDED'      =>  true,
			'IFRAME'          => 'Y',
			'NAME_TEMPLATE'   =>  $arParams['NAME_TEMPLATE'],
			'DATA_COLLECTION' =>  array(
				array(
					'CHILDREN_COUNT'   => $arResult['CHILDREN_COUNT']['PARENT_' . $arResult['TASK']['ID']],
					'DEPTH'            => 0,
					'UPDATES_COUNT'    => 0,
					'PROJECT_EXPANDED' => true,
					'ALLOWED_ACTIONS'  => null,
					'TASK'             => $arResult['TASK']
				)
			),
		);

		if (isset($arResult['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE']))
			$params['COLUMNS_IDS'] = $arResult['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE'];

		$APPLICATION->IncludeComponent(
			'bitrix:tasks.list.items',
			'.default',
			$params,
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}
	$html = ob_get_clean();
	?>
	html = '<?php echo CUtil::JSEscape($html); ?>';
	<?php

	if ($arResult["CALLBACK"] == "CHANGED")
	{
		?>
		iframePopup.onTaskChanged(taskData, null, null, null, html);
		<?php
	}
	else
	{
		if (is_array($arResult["SUBTASKS"]) && count($arResult["SUBTASKS"]))
		{
			?>
			iframePopup.onTaskAdded(
				taskData,
				null,
				{
					multipleTasksAdded : true,
					firstTask          : true,
					callbackOnAfterAdd : function () {
						var subTaskData = null;
						<?php

						foreach ($arResult["SUBTASKS"] as $subTaskData)
						{
							?>
							subTaskData = <?php tasksRenderJSON($subTaskData, 0, $arPaths, true, true, true, $arParams["NAME_TEMPLATE"]); ?>
							iframePopup.onTaskAdded(
								subTaskData,
								null,
								{
									multipleTasksAdded : true,
									firstTask          : false
								}
							);
							<?php
						}
						?>
					}
				},
				null,
				html
			);
			<?php
		}
		else
		{
			?>
			iframePopup.onTaskAdded(taskData, null, null, null, html);
			<?php
		}
	}
	?>

	if (iframePopup.lastAction != "view")
	{
		iframePopup.close();
	}
})();
<?php endif?>

var arFilter = {};
var arOrder = {};
var tasksListAjaxUrl = "/bitrix/components/bitrix/tasks.list/ajax.php?SITE_ID=<?php echo SITE_ID?><?php echo $arResult["TASK_TYPE"] == "group" ? "&GROUP_ID=".$arParams["GROUP_ID"] : ""?>&nt=<?php echo urlencode($arParams['NAME_TEMPLATE']); ?>";
var ajaxUrl = tasksListAjaxUrl;
var postFormAction = "<?php echo CUtil::JSEscape(POST_FORM_ACTION_URI)?>";
var detailTaksID = <?php echo $arResult["TASK"]["ID"]?>;

var currentUser = <?php echo $USER->GetID(); ?>;
var defaultQuickParent = <?php echo $arResult["TASK"]["ID"]?>;

var reminders = <?php echo $arResult["REMINDERS"] ? CUtil::PhpToJsObject($arResult["REMINDERS"]) : "[]" ?>;

<?php
// Prevent loading page without header and footer when not in iframe (it's may happens on "open in new window")
if ($arResult["IS_IFRAME"])
{
	?>
	if (window == window.top)
	{
		// not in iframe, so reload page as not in IFRAME
		window.location = '<?php echo CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('IFRAME'))); ?>';
	}
	<?php
}
?>

var tasksRemindersPopUp;
BX.ready(function() {

	<?/*
	if (BX('pagetitle'))
	{
		<?php
		if (defined('SITE_TEMPLATE_ID') && (SITE_TEMPLATE_ID === 'bitrix24'))
		{
			?>BX('pagetitle').style.paddingRight = '250px';<?php
		}
		else
		{
			?>BX('pagetitle').style.paddingRight = '380px';<?php
		}
		?>
	}
	*/?>

	tasksRemindersPopUp = new BX.TaskReminders.create("tasks-reminder-popup", BX("task-reminder-link"), reminders, <?php echo $arResult["TASK"]["DEADLINE"] ? "\"".CUtil::JSEscape($arResult["TASK"]["DEADLINE"])."\"" : "false" ?>, {
		events: {
			onRemindersSave: function (reminders)
			{
				for (var i = 0; i < this.reminders.length; i++)
				{
					reminders[i].r_date = BX.date.format(
						BX.date.convertBitrixFormat(
							BX.message('FORMAT_DATETIME')
						),
						reminders[i].date
					);
				}

				var data = {
					mode : "reminders",
					sessid : BX.message("bitrix_sessid"),
					id : <?php echo $arResult["TASK"]["ID"]?>,
					reminders : reminders
				};
				BX.ajax.post(ajaxUrl, data);
			},
			onRemindersChange: function (reminders) {
				if (reminders.length) {
					BX.addClass(BX("task-reminder-link").parentNode, "task-reminder-selected");
				} else {
					BX.removeClass(BX("task-reminder-link").parentNode, "task-reminder-selected");
				}
			}
		},
		defaultTime: {
			hour: parseInt(<?=$arResult['COMPANY_WORKTIME']['START']['H']?>),
			minute: parseInt(<?=$arResult['COMPANY_WORKTIME']['START']['M']?>)
		}
	});

	BX.bind(BX("task-reminder-link"), "click", function (e) {
		if(!e) e = window.event;

		tasksRemindersPopUp.show();

		BX.PreventDefault(e);
	});

	BX.bind(BX("task-toggle-favorite"), "click", function(){
		tasksDetailsNS.toggleFavorite(<?=intval($arResult["TASK"]["ID"])?>, !(BX.data(this, 'is-favorite') == '1'));
	});
	BX.addCustomEvent(window.document, 'onTaskListTaskToggleFavorite', function(params){

		var button = BX("task-toggle-favorite");
		var way = params.way;

		if(BX.type.isElementNode(button))
		{
			BX[way ? 'addClass' : 'removeClass'](button, 'feed-post-important-switch-active');
			BX.data(button, 'is-favorite', way);
		}
	});
});

var tasks_funcOnChangeOfSomeDateFields = function (field)
{
	value = field.value;

	if (field.id == "task-new-item-deadline" || field.id == "task-deadline-hidden")
		BX.removeClass(field.parentNode.parentNode, "webform-field-textbox-empty");

	if (field.id == "task-deadline-hidden")
	{
		var arFilter = '', columnsOrder = null;
		var dateSpan = field.previousSibling;

		if (window.top != window)
		{
			if (window.top.tasksListNS && window.top.tasksListNS.arFilter)
			{
				arFilter = window.top.tasksListNS.arFilter;
				columnsOrder = window.top.tasksListNS.getColumnsOrder();
			}
		}

		var defaultTime = BX.CJSTask.ui.extractDefaultTimeFromDataAttribute(field);
		value = BX.CJSTask.addTimeToDateTime(value, defaultTime);

		dateSpan.innerHTML = value;
		dateSpan.className = "task-detail-deadline webform-field-action-link";
		field.nextSibling.style.display = "";
		field.value = value;
		tasksRemindersPopUp.setDeadline(field.value)
		var data = {
			type : 'json_with_html',
			arFilter : arFilter,
			mode : "deadline",
			sessid : BX.message("bitrix_sessid"),
			id : <?php echo $arResult["TASK"]["ID"]?>,
			deadline : value
		};

		if (columnsOrder !== null)
			data['columnsOrder'] = columnsOrder;

		BX.ajax({
			'url' : ajaxUrl,
			'dataType': 'json',
			'method' : 'POST',
			'data' : data,
			'processData' : true,
			'onsuccess': function(reply) {
				var taskData, legacyHtmlTaskItem;

				var data = {
					'PATH_TO_USER_PROFILE' : '<?php echo CUtil::JSEscape($arParams['PATH_TO_USER_PROFILE']); ?>',
					'sessid' : BX.message('bitrix_sessid'),
					'task_id' : <?php echo (int) $arResult['TASK']['ID']; ?>
				};

				taskData = BX.parseJSON(reply.tasksRenderJSON);
				legacyHtmlTaskItem = taskData.html;

				window.top.BX.TasksIFrameInst.onTaskChanged(taskData, null, null, null, legacyHtmlTaskItem);

				// name format
				var urlRequest = '<?php
					echo CUtil::JSEscape($this->__component->GetPath() 
						. '/ajax.php?lang=' . urlencode(LANGUAGE_ID) 
						. '&action=render_task_log_last_row_with_date_change'
						. '&SITE_ID=' . urlencode($arParams['SITE_ID'])
						. '&nt=' . urlencode($arParams['NAME_TEMPLATE']));
					?>';

				BX.ajax({
					'method': 'POST',
					'dataType': 'json',
					'url': urlRequest,
					'data':  data,
					'processData' : true,
					'onsuccess': function(datum)
					{

						var count = parseInt(BX('task-switcher-text-log-count').innerHTML, 10) + 1;
						BX('task-switcher-text-log-count').innerHTML = count.toString();

						var row = BX.create("tr", {  children : [
							BX.create(
								"td",
								{
									props : { className: "task-log-date-column" },
									html : datum.td1
								}
							),
							BX.create(
								"td",
								{
									props : { className: "task-log-author-column" },
									html : datum.td2
								}
							),
							BX.create(
								"td",
								{
									props : { className: "task-log-where-column" },
									html : datum.td3
								}
							),
							BX.create(
								"td",
								{
									props : { className: "task-log-what-column" },
									html : datum.td4
								}
							)
						]});

						BX('task-log-table').appendChild(row);
						return;
					}
				});
				return;
			}
		});

		if (!taskData.dateDeadline || taskData.dateDeadline.getTime() != value)
		{
			taskData.dateDeadline = new Date(BX.parseDate(value));
			var form = document.float_calendar_time;
			if (form)
			{
				taskData.dateDeadline.setHours(parseInt(form.hours.value, 10));
				taskData.dateDeadline.setMinutes(parseInt(form.minutes.value, 10));
				taskData.dateDeadline.setSeconds(parseInt(form.seconds.value, 10));
			}
		}
	}
};

var createMenu = [
	{
		text : '<?php echo CUtil::JSEscape(GetMessage("TASKS_ADD_SUBTASK_2")); ?>',
		title : '<?php echo CUtil::JSEscape(GetMessage("TASKS_ADD_SUBTASK_2")); ?>',
		className : "menu-popup-item-create",
		href: '<?php echo CUtil::JSEscape($createSubtaskUrl)?>',
		onclick : function(event) {
			AddQuickPopupTask(event, {
				PARENT_ID: <?php echo (int) $arResult['TASK']['ID']; ?>
				<?php
				if ($arResult["TASK"]['GROUP_ID'] > 0)
				{
					?>
					, GROUP_ID : <?php echo (int) $arResult["TASK"]['GROUP_ID']; ?>
					<?php
				}
				?>
			});
			this.popupWindow.close();
		}
	},
	{
		text : '<?php echo CUtil::JSEscape(GetMessage("TASKS_ADD_TASK")); ?>',
		title : '<?php echo CUtil::JSEscape(GetMessage("TASKS_ADD_TASK")); ?>',
		className : "menu-popup-item-create",
		href: '<?php echo CUtil::JSEscape($createUrl); ?>',
		onclick : function(event) {
			AddQuickPopupTask(event);
			this.popupWindow.close();
		}
	}
]
</script>
<?php $APPLICATION->ShowViewContent("task_menu"); ?>
<div class="webform task-detail">
	<div class="webform-round-corners webform-main-fields">
		<div class="webform-corners-top">
			<div class="webform-left-corner"></div>
			<div class="webform-right-corner"></div>
		</div>
		<div class="webform-content task-detail-title-label"><?php echo GetMessage("TASKS_TASK_TITLE")?>
			<div class="task-reminder<?php if ($arResult["REMINDERS"]):?> task-reminder-selected<?php endif?>"><a href="" class="webform-field-action-link task-reminder-link" id="task-reminder-link"><?php echo GetMessage("TASKS_REMIND")?></a></div>
		</div>
	</div>

	<div class="webform-round-corners webform-main-block webform-main-block-topless webform-main-block-bottomless">
		<div class="webform-content">
			<?$fav = $arResult['TASK']['FAVORITE'] == 'Y';?>
			<div class="feed-post-important-switch <?=($fav ? 'feed-post-important-switch-active' : '')?>" data-is-favorite="<?=intval($fav)?>" id="task-toggle-favorite" title="<?=GetMessage("TASKS_TASK_ADD_TO_FAVORITES")?>"></div>
			<div class="task-detail-title"><?php echo $arResult["TASK"]["TITLE"]?></div>
			<div class="task-detail-description"><?php
				echo $arResult['TASK']['DESCRIPTION'];
			?></div>
			<?php
			$APPLICATION->IncludeComponent(
				"bitrix:tasks.task.detail.parts",
				".default",
				array(
					'MODE'                 => 'VIEW TASK',
					'BLOCKS'               => array("checklist"),
					'IS_IFRAME'            => $arResult['IS_IFRAME'],
					'GROUP_ID'             => $arParams['GROUP_ID'],
					'PATH_TO_TASKS_TASK'   => $arParams['PATH_TO_TASKS_TASK'],
					'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
					'NAME_TEMPLATE'        => $arParams['NAME_TEMPLATE'],
					'ALLOWED_ACTIONS'      => $arResult['ALLOWED_ACTIONS'],
					'TASK_ID'              => $arResult['TASK']['ID'],
					'CHECKLIST_ITEMS'      => $arResult['CHECKLIST_ITEMS']
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);
			?>
		</div>
	</div>

	<div class="webform-round-corners webform-additional-block webform-additional-block-topless">
		<div class="webform-content">
			<table cellspacing="0" class="task-detail-additional-layout">
				<tr>
					<td class="task-detail-additional-layout-tags">
						<label><?php echo GetMessage("TASKS_TASK_TAGS")?>:</label><span class="task-detail-tags"><?php
							if ($arResult['ALLOWED_ACTIONS']['ACTION_EDIT'])
							{
								$name = $APPLICATION->IncludeComponent(
									"bitrix:tasks.tags.selector",
									".default",
									array(
										"NAME" => "TAGS",
										"VALUE" => $arResult["TASK"]["~TAGS"],
										"ON_SELECT" => "SaveTags"
									),
									null,
									array('HIDE_ICONS' => 'Y')
								);
							}
							elseif ($arResult["TASK"]["TAGS"])
							{
								if (is_array($arResult["TASK"]["TAGS"]))
								{
									echo implode(", ", $arResult["TASK"]["TAGS"]);
								}
								else
								{
									echo $arResult["TASK"]["TAGS"];
								}
							}
							else
							{
								echo GetMessage("TASKS_TASK_NO_TAGS");
							}
						?></span>
					</td>
					<td class="task-detail-additional-layout-files">
						<div class="task-detail-files">
							<?php if ($arResult["TASK"]["FILES"] || $arResult["TASK"]["FORUM_FILES"]):?>
								<label class="task-detail-files-title"><?php echo GetMessage("TASKS_TASK_FILES")?>:</label>
								<div class="task-detail-files-list">
									<?php

									$bCanRemoveFiles = false;
									if ($arResult['ALLOWED_ACTIONS']['ACTION_EDIT'])
										$bCanRemoveFiles = true;

									$i = 0;
									foreach($arResult["TASK"]["FILES"] as $key=>$file)
									{
										$linkId = 'task-detail-file-href-' . (int) $file['ID'];


										?>
										<?php $i++?>
										<div class="task-detail-file webform-field-upload-list"
											><span class="task-detail-file-number"><?php echo $i; 
											?>.</span><span class="task-detail-file-info"
											><a id="<?php echo $linkId; ?>" 
												href="/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=<?php echo $file["ID"]?>" 
												target="_blank" class="task-detail-file-link"
											><?php
												echo $file["ORIGINAL_NAME"];
											?></a><span class="task-detail-file-size">(<?php
												echo CFile::FormatSize($file["FILE_SIZE"]);
											?>)</span><?php

											if ($bCanRemoveFiles)
											{
												?><a href="#" class="delete-file"
													onclick="
														BX.PreventDefault(event);
														return tasksDetailsNS.deleteFile(
															<?php echo (int) $file['ID']; ?>, 
															<?php echo (int) $arResult["TASK"]['ID']; ?>, 
															'<?php echo $linkId; ?>',
															this
														);"
												></a><?php
											}
										?></span></div>
										<?php
									}

									foreach($arResult["TASK"]["FORUM_FILES"] as $file):?>
										<?php $i++?>
										<div class="task-detail-file"><span class="task-detail-file-number"><?php echo $i?>.</span><span class="task-detail-file-info"><a href="#message<?php echo $file["MESSAGE_ID"]?>" class="task-detail-file-comment"/><a class="task-detail-file-link" target="_blank" href="/bitrix/components/bitrix/forum.interface/show_file.php?fid=<?php echo $file["ID"]?>"><?php echo $file["ORIGINAL_NAME"]?></a><span class="task-detail-file-size">(<?php echo CFile::FormatSize($file["FILE_SIZE"])?>)</span></span></div>
									<?php endforeach?>
								</div>
							<?php else:?>
							&nbsp;
							<?php endif?>
						</div>
					</td>
				</tr>
			</table>
			<?if($arResult['ALLOWED_ACTIONS']['ACTION_EDIT']):?>

				<?$groupSet = $arResult["TASK"]["GROUP_ID"];?>
				<div class="task-detail-group">
					<label>
						<?=GetMessage("TASKS_TASK_GROUP")?>:
					</label>
					<span class="task-detail-group-name task-detail-group-name-inline <?=($groupSet ? 'task-detail-group-set' : '')?>">

						<span id="task-group-name"></span>

						<a href="#" class="webform-field-action-link task-detail-group-add <?=($groupSet? 'hidden' : '')?>" id="task-group-add">
							<?=GetMessage("TASKS_GROUP_ADD")?>
						</a>

						<span id="task-group-selected" class="task-detail-group-selected <?=(!$groupSet? 'hidden' : '')?>">
							<a id="task-group-title" href="<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $arResult["TASK"]["GROUP_ID"]))?>" target="_blank">
								<?=($groupSet ? $arResult["TASK"]["GROUP_NAME"] : '')?>
							</a>&nbsp;<span class="task-detail-group-edit" id="task-group-edit"></span><span class="task-detail-group-remove" id="task-group-remove"></span>
						</span>

					</span>
				</div>
				<input id="task-detail-selected-group-id" type="hidden" value="<?=($arResult["TASK"]["GROUP_ID"])?>">

			<?php elseif ($arResult["TASK"]["GROUP_ID"] && CSocNetGroup::CanUserViewGroup($USER->GetID(), $arResult["TASK"]["GROUP_ID"])):?>
				<div class="task-detail-group"><span class="task-detail-group-label"><?php echo GetMessage("TASKS_TASK_GROUP")?>:</span><span class="task-detail-group-name"><a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $arResult["TASK"]["GROUP_ID"]))?>" class="task-detail-group-link" target="_top"><?php echo $arResult["TASK"]["GROUP_NAME"]?></a></span></div>
			<?php endif?>
			<?php
			if ($arResult["TASK"]["PARENT_ID"])
			{
				$rsTask = CTasks::GetList(array(), array("ID" => $arResult["TASK"]["PARENT_ID"]), array("ID", "TITLE"));
				if ($parent = $rsTask->GetNext())
				{
					?>
					<div class="task-detail-supertask">
						<span class="task-detail-supertask-label"><?php echo GetMessage("TASKS_PARENT_TASK")?>:</span>
						<span class="task-detail-group-name"><a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => $parent["ID"], "action" => "view"))?>" class="task-detail-group-link"<?php if ($arResult["IS_IFRAME"]):?> onclick="taskIFramePopup.view(<?php echo $parent["ID"]?>);return false;"<?php endif?>><?php echo $parent["TITLE"]?></a></span>
					</div>
					<?php
				}
			}

			if ($arResult["SHOW_USER_FIELDS"])
			{
				foreach ($arResult["USER_FIELDS"] as $arUserField)
				{
					if (
						($arUserField['FIELD_NAME'] === 'UF_TASK_WEBDAV_FILES')
						&& ($arUserField['XML_ID'] === 'TASK_WEBDAV_FILES')
						&& ( ! empty($arUserField['VALUE']) )
					)
					{
						?>
						<div id="tasks_webdav_area">
							<?php
								$APPLICATION->IncludeComponent(
									"bitrix:system.field.view",
									$arUserField["USER_TYPE"]["USER_TYPE_ID"],
									array("arUserField" => $arUserField),
									null,
									array("HIDE_ICONS"=>"Y")
								);
							?>
						</div>
						<script type="text/javascript">
							top.BX.viewElementBind(
								BX('tasks_webdav_area'),
								{},
								function(node){
									return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer') || node.getAttribute('data-bx-image'));
								}
							);
						</script>
						<?php
					}
				}
			}

			if($arResult["SHOW_USER_FIELDS"]):?>
				<div class="task-detail-properties">
					<table cellspacing="0" class="task-properties-layout">
						<?php
						foreach($arResult["USER_FIELDS"] as $arUserField)
						{
							if (empty($arUserField["VALUE"]))
								continue;

							// Don't show this field in "extra-section", because we showed it below
							if (
								($arUserField['FIELD_NAME'] === 'UF_TASK_WEBDAV_FILES')
								&& ($arUserField['XML_ID'] === 'TASK_WEBDAV_FILES')
							)
							{
								continue;
							}
							?>
							<tr>
								<td class="task-property-name"><?php echo htmlspecialcharsbx($arUserField["EDIT_FORM_LABEL"])?>:</td>
								<td class="task-property-value"><span class="fields"><?php
								if ($arUserField['USER_TYPE']['USER_TYPE_ID'] === 'file')
								{
									if ( ! is_array($arUserField['VALUE']) )
										$arUserField['VALUE'] = array($arUserField['VALUE']);

									$first = true;
									foreach ($arUserField['VALUE'] as $fileId)
									{
										$isImage = false;
										$arFile = CFile::GetFileArray($fileId);

										if ( ! $arFile )
											continue;

										if (
											(substr($arFile["CONTENT_TYPE"], 0, 6) == "image/")
											//&& (CFile::CheckImageFile($arFile) === null)
										)
										{
											$isImage = true;
										}


										if ( ! $first )
											echo '<span class="bx-br-separator"><br /></span>';
										else
											$first = false;

										echo '<span class="fields files">';

										if ($isImage)
										{
											$arFile['SRC'] = "/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=" . $arFile['ID'] . "&amp;TASK_ID=" . (int) $arResult['TASK']['ID'];

											echo CFile::ShowImage(
												$arFile, 
												$arParams["FILE_MAX_WIDTH"], 
												$arParams["FILE_MAX_HEIGHT"], 
												"", 
												"", 
												($arParams["FILE_SHOW_POPUP"]=="Y")
											);
										}
										else
										{
											?>
											<span class="task-detail-file-info"><a 
												href="/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=<?php echo $arFile['ID']; ?>&amp;TASK_ID=<?php echo (int) $arResult['TASK']['ID']; ?>"
												target="_blank" class="task-detail-file-link"><?php
													echo htmlspecialcharsbx($arFile['ORIGINAL_NAME']);
												?></a><span class="task-detail-file-size">(<?php
													echo CFile::FormatSize($arFile['FILE_SIZE']);
											?>)</span></span>
											<?php
										}

										echo '</span>';
									}
								}
								else
								{
									$APPLICATION->IncludeComponent(
										"bitrix:system.field.view",
										$arUserField["USER_TYPE"]["USER_TYPE_ID"],
										array("arUserField" => $arUserField),
										null,
										array("HIDE_ICONS"=>"Y")
									);
								}
								?></td>
							</tr>
							<?php
						}
						?>
					</table>
				</div>
			<?php endif?>
		</div>
		<div class="webform-corners-bottom">
			<div class="webform-left-corner"></div>
			<div class="webform-right-corner"></div>
		</div>
	</div>
	<?php
	// =========================== Start of buttons area ===========================
	$APPLICATION->IncludeComponent(
		"bitrix:tasks.task.detail.parts",
		".default",
		array(
			'MODE'                 => 'VIEW TASK',
			'BLOCKS'               => array("buttons"),
			'IS_IFRAME'            => $arResult['IS_IFRAME'],
			'GROUP_ID'             => $arParams['GROUP_ID'],
			'PATH_TO_TASKS_TASK'   => $arParams['PATH_TO_TASKS_TASK'],
			'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
			'NAME_TEMPLATE'        => $arParams['NAME_TEMPLATE'],
			'ALLOWED_ACTIONS'      => $arResult['ALLOWED_ACTIONS'],
			'TASK_ID'              => $arResult['TASK']['ID'],
			'TASK'                 => $arResult['TASK']
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
	// =========================== End of buttons area ===========================
	?>
</div>
<div class="webform task-detail" style="overflow-x:auto; margin-bottom:20px;">
	<div class="task-detail-subtasks <?php if ($arResult['IS_IFRAME']) echo 'task-detail-subtasks-iframe'; ?>"
		id="task-detail-subtasks-block"<?php if (empty($arResult["SUBTASKS"])):?> style="display: none;"<?php endif?>
	>
		<?php
		$APPLICATION->IncludeComponent(
			'bitrix:tasks.list', '.default',
			array(
				'HIDE_VIEWS'         => 'Y',
				'HIDE_MENU'          => 'Y',
				'HIDE_GROUP_ACTIONS' => 'Y',
				'FORCE_LIST_MODE'    => 'Y',
				'PREVENT_PAGE_ONE_COLUMN' => 'Y',
				'PREVENT_FLEXIBLE_LAYOUT' => ($arResult['IS_IFRAME'] ? 'N' : 'Y'),
				'COMMON_FILTER'      => array(),
				'ORDER'              => array('GROUP_ID'  => 'ASC'),
				'PREORDER'           => array('STATUS_COMPLETE' => 'ASC'),
				'FILTER'             => array('PARENT_ID' => $arParams['TASK_ID']),
				'VIEW_STATE'         => array(),
				'CONTEXT_ID'         => CTaskColumnContext::CONTEXT_TASK_DETAIL,

				'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],

				'TASKS_ALWAYS_EXPANDED' => 'Y',

			), null, array("HIDE_ICONS" => "Y")
		);
		?>
	</div>

	<?php if (sizeof($arResult["PREV_TASKS"])):?>
	<div class="task-detail-previous-tasks">
		<div class="task-list">
			<div class="task-list-left-corner"></div>
			<div class="task-list-right-corner"></div>
			<table class="task-list-table task-list-table-unsortable" cellspacing="0">

				<colgroup>
					<col class="task-title-column" />
					<col class="task-menu-column" />
					<col class="task-flag-column" />
					<col class="task-priority-column" />
					<col class="task-deadline-column" />
					<col class="task-responsible-column" />
					<col class="task-director-column" />
					<col class="task-grade-column" />
					<col class="task-complete-column" />
				</colgroup>

				<thead>
				<tr>
					<th class="task-title-column"  colspan="4">
						<div class="task-head-cell">
							<span class="task-head-cell-sort-order"></span>
							<span class="task-head-cell-title"><?php echo GetMessage("TASKS_TASK_PREVIOUS_TASKS")?></span>
							<span class="task-head-cell-clear-underlay"><a class="task-head-cell-sort-clear" href="#"><i class="task-head-cell-sort-clear-icon"></i></a></span></div>
					</th>
					<th class="task-deadline-column">
						<div class="task-head-cell"><span class="task-head-cell-sort-order"></span><span class="task-head-cell-title"><?php echo GetMessage("TASKS_DEADLINE")?></span><span class="task-head-cell-clear-underlay"><a class="task-head-cell-sort-clear" href="#"><i class="task-head-cell-sort-clear-icon"></i></a></span></div></th>
					<th class="task-responsible-column">
						<div class="task-head-cell"><span class="task-head-cell-sort-order"></span><span class="task-head-cell-title"><?php echo GetMessage("TASKS_RESPONSIBLE")?></span><span class="task-head-cell-clear-underlay"><a class="task-head-cell-sort-clear" href="#"><i class="task-head-cell-sort-clear-icon"></i></a></span></div></th>
					<th  class="task-director-column" >
						<div class="task-head-cell"><span class="task-head-cell-sort-order"></span><span class="task-head-cell-title"><?php echo GetMessage("TASKS_CREATOR")?></span><span class="task-head-cell-clear-underlay"><a class="task-head-cell-sort-clear" href="#"><i class="task-head-cell-sort-clear-icon"></i></a></span></div></th>

					<th class="task-grade-column">&nbsp;</th>
					<th class="task-complete-column">&nbsp;</th>

				</tr>
				</thead>
				<tbody>

					<?php if (sizeof($arResult["PREV_TASKS"])):?>
						<?php $currentProject = false?>
						<?php foreach($arResult["PREV_TASKS"] as $task):?>
							<?php if ($arResult["TASK_TYPE"] != "group" && $task["GROUP_ID"] && $task["GROUP_ID"] != $currentProject):?>
								<?php
									$currentProject = $task["GROUP_ID"];
									$task["GROUP_NAME"] = $arResult["GROUPS"][$task["GROUP_ID"]]["NAME"];
								?>
								<tr class="task-list-item" id="task-project-<?php echo $task["GROUP_ID"]?>">
									<td class="task-project-column" colspan="9">
										<div class="task-project-column-inner">
											<div class="task-project-name"><span class="task-project-folding<?php if (!$arResult["GROUPS"][$task["GROUP_ID"]]["EXPANDED"]):?> task-project-folding-closed<?php endif?>" onclick="ToggleProjectTasks(<?php echo $arResult["GROUPS"][$task["GROUP_ID"]]["ID"]?>, event);"></span><a class="task-project-name-link" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_TASKS"], array("group_id" => $arResult["GROUPS"][$task["GROUP_ID"]]["ID"]))?>" onclick="ToggleProjectTasks(<?php echo $arResult["GROUPS"][$task["GROUP_ID"]]["ID"]?>, event);"><?=htmlspecialcharsbx($arResult["GROUPS"][$task["GROUP_ID"]]["NAME"])?></a></div>
											<?php if (is_object($USER) && $USER->IsAuthorized()):?>
												<div class="task-project-actions"><a class="task-project-action-link" href="<?php $path = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit")); echo $path.(strstr($path, "?") ? "&" : "?")."GROUP_ID=".$arResult["GROUPS"][$task["GROUP_ID"]]["ID"].($arResult["IS_IFRAME"] ? "&IFRAME=Y" : "");?>"><i class="task-project-action-icon"></i><span class="task-project-action-text"><?php echo GetMessage("TASKS_ADD_TASK")?></span></a></div>
											<?php endif?>
										</div>
									</td>
								</tr>
							<?php endif?>
							<?php tasksRenderListItem($task, $arResult["CHILDREN_COUNT"]["PARENT_".$task["ID"]], $arPaths, 0, false, false, SITE_ID, 0, true, false, "bitrix:tasks.list.item", ".default", $arParams["NAME_TEMPLATE"])?>
						<?php endforeach?>
					<?php endif?>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif?>
</div>
<div class="webform task-detail">
	<div class="task-comments-and-log" <?php if ($arResult['IS_IFRAME']) echo ' style="width:630px;"' ?>>

		<?
		$secondsSign = ($arResult["FULL_ELAPSED_TIME"] > 0 ? 1 : -1);
		$elapsedHours = (int) $secondsSign*floor(abs($arResult["FULL_ELAPSED_TIME"]) / 60);
		$elapsedMinutes = ($secondsSign*abs($arResult["FULL_ELAPSED_TIME"])) % 60;
		?>

		<div class="task-comments-log-switcher">
			<span class="task-switcher task-switcher-selected" id="task-comments-switcher"><span class="task-switcher-left"></span><span class="task-switcher-text"><span class="task-switcher-text-inner"><?php echo GetMessage("TASKS_TASK_COMMENTS")?> (<?php echo intval($arResult["TASK"]["COMMENTS_COUNT"])?>)</span></span><span class="task-switcher-right"></span></span>
			<span class="task-switcher" id="task-log-switcher"><span class="task-switcher-left"></span><span class="task-switcher-text"><span class="task-switcher-text-inner"><?php echo GetMessage("TASKS_TASK_LOG")?> (<span id="task-switcher-text-log-count"><?php echo sizeof($arResult["LOG"])?></span>)</span></span><span class="task-switcher-right"></span></span>
			<span class="task-switcher" id="task-time-switcher"><span class="task-switcher-left"></span><span class="task-switcher-text"><span class="task-switcher-text-inner"><?php echo GetMessage("TASKS_ELAPSED_TIME")?> (<?=intval($elapsedHours)?><?php echo GetMessage("TASKS_ELAPSED_H")?> <?=intval($elapsedMinutes)?><?php echo GetMessage("TASKS_ELAPSED_M")?>)</span></span><span class="task-switcher-right"></span></span>
		</div>

		<div class="task-comments-block task-comments-block-selected" id="task-comments-block">
			<a name="comments"></a>
			<?

			if(!intval($arResult['FORUM_ID']))
			{
				print('Forum does not exist');
			}
			else
			{
				// User already have access to task, so user have access to read/create comments
				$arParams['PERMISSION'] = 'M';

				$APPLICATION->IncludeComponent("bitrix:forum.comments", "bitrix24", array(
						"FORUM_ID" => $arResult['FORUM_ID'],
						"ENTITY_TYPE" => "TK",
						"ENTITY_ID" => $arResult['TASK']['ID'],
						"ENTITY_XML_ID" => "TASK_".$arResult['TASK']['ID'],
						"URL_TEMPLATES_PROFILE_VIEW" => $arParams['PATH_TO_USER_PROFILE'],
						"CACHE_TYPE" => $arParams["CACHE_TYPE"],
						"CACHE_TIME" => $arParams["CACHE_TIME"],
						"IMAGE_HTML_SIZE" => 400,
						"MESSAGES_PER_PAGE" => $arParams['ITEM_DETAIL_COUNT'],
						"PAGE_NAVIGATION_TEMPLATE" => "arrows",
						"DATE_TIME_FORMAT" => \Bitrix\Tasks\UI::getDateTimeFormat(),
						"PATH_TO_SMILE" => $arParams['PATH_TO_FORUM_SMILE'],
						"EDITOR_CODE_DEFAULT" => "N",
						"SHOW_MODERATION" => "Y",
						"SHOW_AVATAR" => "Y",
						"SHOW_RATING" => $arParams['SHOW_RATING'],
						"RATING_TYPE" => $arParams['RATING_TYPE'],
						"SHOW_MINIMIZED" => "N",
						"USE_CAPTCHA" => "N",
						'PREORDER' => 'N',
						"SHOW_LINK_TO_FORUM" => "N",
						"SHOW_SUBSCRIBE" => "N",
						"FILES_COUNT" => 10,
						"SHOW_WYSIWYG_EDITOR" => "Y",
						"AUTOSAVE" => true,
						"PERMISSION" => $arParams['PERMISSION'],
						"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
						"MESSAGE_COUNT" => 3,
					),
					($component->__parent ? $component->__parent : $component),
					array('HIDE_ICONS' => 'Y')
				);
			}
			?>
		</div>
		<script type="text/javascript">
			top.BX.viewElementBind(
				BX('task-comments-block'),
				{},
				function(node){
					return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer') || node.getAttribute('data-bx-image'));
				}
			);
			/*
			BX.ready(function(){
				tasksDetailsNS.loadCommentsComponent(
					BX('task-comments-block'),	// targetDomNode
					<?php echo (int) $arResult['FORUM_ID']; ?>,
					<?php echo (int) $arResult['TASK']['ID']; ?>,
					'<?php echo CUtil::JSEscape($arParams['PATH_TO_USER_PROFILE']); ?>',
					<?php echo (int) $arParams['ITEM_DETAIL_COUNT']; ?>,
					'<?php echo CUtil::JSEscape($arParams['PATH_TO_FORUM_SMILE']); ?>',
					'<?php echo CUtil::JSEscape($arParams['SHOW_RATING']); ?>',
					'<?php echo CUtil::JSEscape($arParams['RATING_TYPE']); ?>'
				)
			});
			*/
		</script>

		<div class="task-log-block" id="task-log-block">
			<a name="updates"></a>
			<?php
				if (sizeof($arResult["LOG"]) > 0):
			?>

			<table id="task-log-table" class="task-log-table" cellspacing="0">
				<col class="task-log-date-column" />
				<col class="task-log-author-column" />
				<col class="task-log-where-column" />
				<col class="task-log-what-column" />

				<tr>
					<th class="task-log-date-column"><?php echo GetMessage("TASKS_LOG_WHEN")?></th>
					<th class="task-log-author-column"><?php echo GetMessage("TASKS_LOG_WHO")?></th>
					<th class="task-log-where-column"><?php echo GetMessage("TASKS_LOG_WHERE")?></th>
					<th class="task-log-what-column"><?php echo GetMessage("TASKS_LOG_WHAT")?></th>
				</tr>
				<?php

				$commentsCurrPage = (intval($_GET["PAGEN_2"]) > 1 ? intval($_GET["PAGEN_2"]) : 1);

				if ( ! function_exists('lambda_sgkrg456d_funcFormatForHuman') )
				{
					function lambda_sgkrg456d_funcFormatForHuman($seconds)
					{
						if ($seconds === NULL)
							return '';

						$hours = (int) ($seconds / 3600);

						if ($hours < 24)
						{
							$duration = $hours . ' ' . CTasksTools::getMessagePlural(
								$hours,
								'TASKS_TASK_DURATION_HOURS'
							);
						}
						elseif ($houresInResid = $hours % 24)
						{
							$days = (int) ($hours / 24);
							$duration = $days 
								. ' ' 
								. CTasksTools::getMessagePlural(
									$days,
									'TASKS_TASK_DURATION_DAYS'
								) 
								. ' ' 
								. (int) $houresInResid 
								. ' ' 
								. CTasksTools::getMessagePlural(
									(int) $houresInResid,
									'TASKS_TASK_DURATION_HOURS'
								) ;
						}
						else
						{
							$days = (int) ($hours / 24);
							$duration = $days 
								. ' ' 
								. CTasksTools::getMessagePlural(
									$days,
									'TASKS_TASK_DURATION_DAYS'
								);
						}

						return ($duration);
					}
				}

				if ( ! function_exists('lambda_sgkrg457d_funcFormatForHumanMinutes') )
				{
					function lambda_sgkrg457d_funcFormatForHumanMinutes($in, $bDataInSeconds = false)
					{
						if ($in === NULL)
							return '';

						if ($bDataInSeconds)
							$minutes = (int) round($in / 60, 0);

						$hours = (int) ($minutes / 60);

						if ($minutes < 60)
						{
							$duration = $minutes . ' ' . CTasksTools::getMessagePlural(
								$minutes,
								'TASKS_TASK_DURATION_MINUTES'
							);
						}
						elseif ($minutesInResid = $minutes % 60)
						{
							$duration = $hours 
								. ' ' 
								. CTasksTools::getMessagePlural(
									$hours,
									'TASKS_TASK_DURATION_HOURS'
								) 
								. ' ' 
								. (int) $minutesInResid 
								. ' ' 
								. CTasksTools::getMessagePlural(
									(int) $minutesInResid,
									'TASKS_TASK_DURATION_MINUTES'
								);
						}
						else
						{
							$duration = $hours . ' ' . CTasksTools::getMessagePlural(
								$hours,
								'TASKS_TASK_DURATION_HOURS'
							);
						}

						if ($bDataInSeconds && ($in < 3600))
						{
							if ($secondsInResid = $in % 60)
							{
								$duration .= ' ' . (int) $secondsInResid 
									. ' ' 
									. CTasksTools::getMessagePlural(
										(int) $secondsInResid,
										'TASKS_TASK_DURATION_SECONDS'
									);
							}
						}

						return ($duration);
					}
				}

				$anchor_id_base  = RandString(12) . '_';
				$anchor_id_index = 0;
				$randString = RandString(9) . '_';
				$randStrIndex = 0;
				foreach($arResult["LOG"] as $record):?>
					<?php $anchor_id = $anchor_id_base . ($anchor_id_index++);?>
					<tr>
						<td class="task-log-date-column"><span class="task-log-date"><?php echo FormatDateFromDB($record["CREATED_DATE"]);?></span></td>
						<td class="task-log-author-column"><script type="text/javascript">BX.tooltip(<?php echo $record["USER_ID"]?>, "anchor_log_<?php echo $anchor_id?>", "");</script><a id="anchor_log_<?php echo $anchor_id?>" class="task-log-author" target="_top" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array("user_id" => $record["USER_ID"]))?>"><?php 
							echo tasksFormatNameShort(
								$record["USER_NAME"], 
								$record["USER_LAST_NAME"], 
								$record["USER_LOGIN"], 
								$record["USER_SECOND_NAME"], 
								$arParams["NAME_TEMPLATE"],
								false
								)?></a></td>
						<td class="task-log-where-column"><span class="task-log-where"><?php echo GetMessage("TASKS_LOG_".$record["FIELD"])?><?php
							if ($record["FIELD"] == "DELETED_FILES")
							{
								?>: <?php echo $record["FROM_VALUE"]?><?php
							}
							elseif ($record["FIELD"] == "NEW_FILES")
							{
								?>: <?php echo $record["TO_VALUE"]?><?php
							}
							elseif ($record["FIELD"] == "COMMENT" || $record["FIELD"] == "COMMENT_EDIT" || $record["FIELD"] == "COMMENT_DEL")
							{
								$link = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => $arResult["TASK"]["ID"], "action" => "view"));
								if ($pageNumber != 1)
								{
									$link .= (strpos($link, "?") === false ? "?" : "&")."MID=".intval($record["TO_VALUE"]);
								}
								if ($arResult["IS_IFRAME"])
								{
									$link .= (strpos($link, "?") === false ? "?" : "&")."IFRAME=Y";
								}
								$link .= "#message".$record["TO_VALUE"];
								?>

								<?if($record["FIELD"] != "COMMENT_DEL"):?>
									<a href="javascript: void(0)" onClick="GoToComment('<?=$link?>', <?=($pageNumber == $commentsCurrPage ? "true" : "false")?>)">#<?=$record["TO_VALUE"]?></a>
								<?else:?>
									#<?=$record["TO_VALUE"]?>
								<?endif?>
								<?
							}
						?></span></td>
						<td class="task-log-what-column"><span class="task-log-what"><?php
							switch($record["FIELD"])
							{
								case "DURATION_PLAN_SECONDS":
									echo lambda_sgkrg456d_funcFormatForHuman($record['FROM_VALUE']);;
									?><span class="task-log-arrow">&rarr;</span><?php
									echo lambda_sgkrg456d_funcFormatForHuman($record['TO_VALUE']);;
								break;

								case "TITLE":
								case "DURATION_PLAN":
								case 'CHECKLIST_ITEM_CREATE':
								case 'CHECKLIST_ITEM_REMOVE':
								case 'CHECKLIST_ITEM_RENAME':
									echo $record["FROM_VALUE"];
										?><span class="task-log-arrow">&rarr;</span><?php
									echo $record["TO_VALUE"];
								break;

								case 'CHECKLIST_ITEM_UNCHECK':
									echo '<span style="text-decoration:line-through; color: grey;">' . $record["FROM_VALUE"] . '</span>';
										?><span class="task-log-arrow">&rarr;</span><?php
									echo $record["TO_VALUE"];
								break;

								case 'CHECKLIST_ITEM_CHECK':
									echo $record["FROM_VALUE"];
										?><span class="task-log-arrow">&rarr;</span><?php
									echo '<span style="text-decoration:line-through; color: grey;">' . $record["TO_VALUE"] . '</span>';
								break;

								case "DURATION_FACT":
									echo lambda_sgkrg457d_funcFormatForHumanMinutes($record["FROM_VALUE"]);
										?><span class="task-log-arrow">&rarr;</span><?php
									echo lambda_sgkrg457d_funcFormatForHumanMinutes($record["TO_VALUE"]);
								break;								

								case "TIME_ESTIMATE":
								case "TIME_SPENT_IN_LOGS":
									$bDataInSeconds = true;
									echo lambda_sgkrg457d_funcFormatForHumanMinutes($record["FROM_VALUE"], true);	// true => data in seconds
										?><span class="task-log-arrow">&rarr;</span><?php
									echo lambda_sgkrg457d_funcFormatForHumanMinutes($record["TO_VALUE"], true);	// true => data in seconds
								break;								

								case "CREATED_BY":
								case "RESPONSIBLE_ID":
									if (isset($arResult['USERS_DATA'][$record['FROM_VALUE']]))
									{
										$arUserFrom = $arResult['USERS_DATA'][$record['FROM_VALUE']];
										$anchor_id = $randString . ($randStrIndex++);
										$sUserFrom = '<script type="text/javascript">BX.tooltip('.$arUserFrom["ID"].', "anchor_log_'.$anchor_id.'", "");</script><a id="anchor_log_'.$anchor_id.'" class="task-log-author" href="'.CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array("user_id" => $arUserFrom["ID"])).'">'
											. tasksFormatNameShort(
												$arUserFrom["NAME"], 
												$arUserFrom["LAST_NAME"], 
												$arUserFrom["LOGIN"], 
												$arUserFrom["SECOND_NAME"], 
												$arParams["NAME_TEMPLATE"],
												false
												).'</a>';
									}

									if (isset($arResult['USERS_DATA'][$record['TO_VALUE']]))
									{
										$arUserTo = $arResult['USERS_DATA'][$record['TO_VALUE']];
										$anchor_id = $randString . ($randStrIndex++);
										$sUserTo = '<script type="text/javascript">BX.tooltip('.$arUserTo["ID"].', "anchor_log_'.$anchor_id.'", "");</script><a id="anchor_log_'.$anchor_id.'" class="task-log-author" href="'.CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array("user_id" => $arUserTo["ID"])).'">'
										. tasksFormatNameShort(
											$arUserTo["NAME"], 
											$arUserTo["LAST_NAME"], 
											$arUserTo["LOGIN"], 
											$arUserTo["SECOND_NAME"], 
											$arParams["NAME_TEMPLATE"],
											false
											).'</a>';
									}
									?>
									<?php echo $sUserFrom?><span class="task-log-arrow">&rarr;</span><?php echo $sUserTo?>
									<?php
									break;
								
								case "DEADLINE":
								case "START_DATE_PLAN":
								case "END_DATE_PLAN":
									if ($record['FROM_VALUE'] > 0)
									{
										print(\Bitrix\Tasks\UI::formatDateTime($record['FROM_VALUE'], '^'.\Bitrix\Tasks\UI::getDateTimeFormat()));
									}

									?><span class="task-log-arrow">&rarr;</span><?php

									if ($record['TO_VALUE'] > 0)
									{
										print(\Bitrix\Tasks\UI::formatDateTime($record['TO_VALUE'], '^'.\Bitrix\Tasks\UI::getDateTimeFormat()));
									}
									break;
								
								case "ACCOMPLICES":
								case "AUDITORS":
									$arUsersFromStr = array();
									if ($record["FROM_VALUE"])
									{
										foreach (explode(',', $record['FROM_VALUE']) as $userId)
										{
											if (isset($arResult['USERS_DATA'][$userId]))
											{
												$arUserFrom = $arResult['USERS_DATA'][$userId];
												$anchor_id = $randString . ($randStrIndex++);
												$arUsersFromStr[] = '<script type="text/javascript">BX.tooltip('.$arUserFrom["ID"].', "anchor_log_'.$anchor_id.'", "");</script><a id="anchor_log_'.$anchor_id.'" class="task-log-link" href="'.CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array("user_id" => $arUserFrom["ID"])).'">'
												. tasksFormatNameShort(
													$arUserFrom["NAME"], 
													$arUserFrom["LAST_NAME"], 
													$arUserFrom["LOGIN"], 
													$arUserFrom["SECOND_NAME"], 
													$arParams["NAME_TEMPLATE"],
													false
													).'</a>';
											}
										}
									}

									$arUsersToStr = array();
									if ($record["TO_VALUE"])
									{
										foreach (explode(',', $record['TO_VALUE']) as $userId)
										{
											if (isset($arResult['USERS_DATA'][$userId]))
											{
												$arUserTo = $arResult['USERS_DATA'][$userId];
												$anchor_id = $randString . ($randStrIndex++);
												$arUsersToStr[] = '<script type="text/javascript">BX.tooltip('.$arUserTo["ID"].', "anchor_log_'.$anchor_id.'", "");</script><a id="anchor_log_'.$anchor_id.'" class="task-log-link" href="'.CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array("user_id" => $arUserTo["ID"])).'">'
												. tasksFormatNameShort(
													$arUserTo["NAME"], 
													$arUserTo["LAST_NAME"], 
													$arUserTo["LOGIN"], 
													$arUserTo["SECOND_NAME"], 
													$arParams["NAME_TEMPLATE"],
													false).'</a>';
											}
										}
									}
									?>
									<?php echo implode(", ", $arUsersFromStr)?><span class="task-log-arrow">&rarr;</span><?php echo implode(", ", $arUsersToStr)?>
									<?php
									break;

								case "TAGS":
									?>
									<?php echo str_replace(",", ", ", $record["FROM_VALUE"])?><span class="task-log-arrow">&rarr;</span><?php echo str_replace(",", ", ", $record["TO_VALUE"])?>
									<?php
									break;

								case "PRIORITY":
									?>
									<?php echo GetMessage("TASKS_PRIORITY_".$record["FROM_VALUE"])?><span class="task-log-arrow">&rarr;</span><?php echo GetMessage("TASKS_PRIORITY_".$record["TO_VALUE"])?>
									<?php
									break;

								case "GROUP_ID":
									if ($record['FROM_VALUE'] && isset($arResult['GROUPS_DATA'][$record['FROM_VALUE']]))
									{
										$arGroupFrom = $arResult['GROUPS_DATA'][$record['FROM_VALUE']];
										?><a href="<?php echo $arGroupFrom['URL']; ?>"><?php echo $arGroupFrom['NAME']?></a><?php
									}
									?><span class="task-log-arrow">&rarr;</span><?php
									if ($record['TO_VALUE'] && isset($arResult['GROUPS_DATA'][$record['TO_VALUE']]))
									{
										$arGroupTo = $arResult['GROUPS_DATA'][$record['TO_VALUE']];
										?><a href="<?php echo $arGroupTo['URL']; ?>"><?php echo $arGroupTo['NAME']?></a><?php
									}
									break;

								case "PARENT_ID":
									if ($record['FROM_VALUE'] && isset($arResult['TASKS_DATA'][$record['FROM_VALUE']]))
									{
										$arTaskFrom = $arResult['TASKS_DATA'][$record['FROM_VALUE']];
											?><a href="<?php echo $arTaskFrom['URL']; ?>"><?php echo $arTaskFrom['TITLE']?></a><?php
									}
									?><span class="task-log-arrow">&rarr;</span><?php
									if ($record['TO_VALUE'] && isset($arResult['TASKS_DATA'][$record['TO_VALUE']]))
									{
										$arTaskFrom = $arResult['TASKS_DATA'][$record['TO_VALUE']];
											?><a href="<?php echo $arTaskFrom['URL']; ?>"><?php echo $arTaskFrom['TITLE']?></a><?php
									}
									break;

								case "DEPENDS_ON":
									$arTasksFromStr = array();
									if ($record['FROM_VALUE'])
									{
										foreach (explode(",", $record['FROM_VALUE']) as $taskId)
										{
											if ( ! isset($arResult['TASKS_DATA'][$taskId]) )
												continue;

											$arTaskFrom = $arResult['TASKS_DATA'][$taskId];
											$arTasksFromStr[] = '<a class="task-log-link" href="' . $arTaskFrom['URL'] . '">' . $arTaskFrom['TITLE'] . '</a>';
										}
									}

									$arTasksToStr = array();
									if ($record['TO_VALUE'])
									{
										foreach (explode(",", $record['TO_VALUE']) as $taskId)
										{
											if ( ! isset($arResult['TASKS_DATA'][$taskId]) )
												continue;

											$rsTasksTo = $arResult['TASKS_DATA'][$taskId];
											$arTasksToStr[] = '<a class="task-log-link" href="' . $rsTasksTo['URL'] . '">' . $rsTasksTo['TITLE'] . '</a>';
										}
									}
									
									echo implode(", ", $arTasksFromStr)?><span class="task-log-arrow">&rarr;</span><?php echo implode(", ", $arTasksToStr);
									break;

								case "STATUS":
									?>
									<?php echo GetMessage("TASKS_STATUS_".$record["FROM_VALUE"])?><span class="task-log-arrow">&rarr;</span><?php echo GetMessage("TASKS_STATUS_".$record["TO_VALUE"])?>
									<?php
									break;

								case "MARK":
									?>
									<?php echo !$record["FROM_VALUE"] ? GetMessage("TASKS_MARK_NONE") : GetMessage("TASKS_MARK_".$record["FROM_VALUE"])?><span class="task-log-arrow">&rarr;</span><?php echo !$record["TO_VALUE"] ? GetMessage("TASKS_MARK_NONE") : GetMessage("TASKS_MARK_".$record["TO_VALUE"])?>
									<?php
									break;

								case "ADD_IN_REPORT":
									?>
									<?php echo $record["FROM_VALUE"] == "Y" ? GetMessage("TASKS_SIDEBAR_IN_REPORT_YES") : GetMessage("TASKS_SIDEBAR_IN_REPORT_NO")?><span class="task-log-arrow">&rarr;</span><?php echo $record["TO_VALUE"] == "Y" ? GetMessage("TASKS_SIDEBAR_IN_REPORT_YES") : GetMessage("TASKS_SIDEBAR_IN_REPORT_NO")?>
									<?php
									break;

								default:
									echo "&nbsp;";
									break;
							}
						?></span></td>
					</tr>
				<?php endforeach;?>
			</table>
			<?php endif?>
		</div>
		<script type="text/javascript">
			if (document.location.hash == "#updates")
			{
				(BX.proxy(ToggleSwitcher, BX("task-log-switcher", true)))();
			}
		</script>
		<div id="task-time-block" class="task-time-block">
			<form method="post" action="<?php echo POST_FORM_ACTION_URI?>" name="task-elapsed-time-form" id="task-elapsed-time-form">
				<?php echo bitrix_sessid_post()?>
				<input type="hidden" name="ELAPSED_ID" value="" />
				<input type="hidden" name="ACTION" value="elapsed_add" />
				<table id="task-time-table" class="task-time-table" cellspacing="0" cellpadding="0">
					<col class="task-time-date-column" />
					<col class="task-time-author-column" />
					<col class="task-time-spent-column" />
					<col class="task-time-comments-column" />
					<tr>
						<th class="task-time-date-column"><?php echo GetMessage("TASKS_ELAPSED_DATE")?></th>
						<th class="task-time-author-column"><?php echo GetMessage("TASKS_ELAPSED_AUTHOR")?></th>
						<th class="task-time-spent-column"><?php echo GetMessage("TASKS_ELAPSED_TIME_SHORT")?></th>
						<th class="task-time-comment-column"><?php echo GetMessage("TASKS_ELAPSED_COMMENT")?></th>
					</tr>
					<?php foreach($arResult["ELAPSED_TIME"] as $time):?>
						<tr id="elapsed-time-<?php echo $time["ID"]?>">
							<td class="task-time-date-column"><span class="task-time-date"><?=FormatDateFromDB($time["CREATED_DATE"], FORMAT_DATE);?></span></td>
							<td class="task-time-author-column"><script type="text/javascript">BX.tooltip(<?php echo $time["USER_ID"]?>, "anchor_elapsed_<?php echo $anchor_id?>", "");</script><a id="anchor_elapsed_<?php echo $anchor_id?>" class="task-log-author" target="_top" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array("user_id" => $time["USER_ID"]))?>"><?php 
								echo tasksFormatNameShort(
									$time["USER_NAME"], 
									$time["USER_LAST_NAME"], 
									$time["USER_LOGIN"], 
									$time["USER_SECOND_NAME"], 
									$arParams["NAME_TEMPLATE"],
									false)?></a></td>
							<td class="task-time-spent-column">

								<?
									$secondsSign = ($time["SECONDS"] > 0 ? 1 : -1);
									$hours = (int) $secondsSign*floor(abs($time["SECONDS"]) / 3600);

									$minutes = ($secondsSign*floor(abs($time["SECONDS"]) / 60)) % 60;
									$seconds = $time["SECONDS"] % 60;
								?>

								<?=intval($hours)?><span><?=GetMessage("TASKS_ELAPSED_H")?></span><?=sprintf("%02d", $minutes)?><span><?=GetMessage("TASKS_ELAPSED_M")?></span><?=sprintf("%02d", $seconds)?><span>
								<?php echo GetMessage("TASKS_ELAPSED_S");

								switch ($time['SOURCE'])
								{
									case CTaskElapsedItem::SOURCE_SYSTEM:
									break;

									case CTaskElapsedItem::SOURCE_MANUAL:
										echo ' <img border="0" style="position:relative; top:3px;" src="/bitrix/components/bitrix/tasks.task.detail/templates/.default/images/s01.png" title="' . GetMessage("TASKS_ELAPSED_SOURCE_MANUAL") . '">';
									break;

									default:
									case CTaskElapsedItem::SOURCE_UNDEFINED:
										echo ' <img border="0" style="position:relative; top:3px;" src="/bitrix/components/bitrix/tasks.task.detail/templates/.default/images/s02.png" title="' . GetMessage("TASKS_ELAPSED_SOURCE_UNDEFINED") . '">';
									break;
								}
							?></span></td>
							<td class="task-time-comment-column">
								<div class="wrap-edit-nav">
									<span class="task-time-comment"><?php echo $time["COMMENT_TEXT"] ? $time["COMMENT_TEXT"] : "&nbsp;"?></span>
									<?php if ($time['META:CAN_REMOVE'] || $time['META:CAN_MODIFY']):?>
										<span class="task-edit-nav">
											<?php
											if ($time['META:CAN_MODIFY'])
											{
												?>
												<a class="task-table-edit" onclick="EditElapsedTime(<?=$time["ID"]?>, <?=$hours?>, <?=$minutes?>, '<?=CUtil::JSEscape($time["COMMENT_TEXT"])?>', <?=$seconds?>, '<?=FormatDateFromDB($time["CREATED_DATE"], FORMAT_DATE)?>')"></a>
												<?php
											}
											
											if ($time['META:CAN_REMOVE'])
											{
												?>
												<a class="task-table-remove" onclick="return confirm('<?=GetMessage("TASKS_ELAPSED_REMOVE_CONFIRM")?>')" href="<?php echo $APPLICATION->GetCurPageParam("ACTION=elapsed_delete&ELAPSED_ID=".$time["ID"]."&".bitrix_sessid_get(), array("sessid", "ACTION"));?>"></a>
												<?php
											}
											?>
										</span>
									<?php endif?>
								</div>
							</td>
						</tr>
					<?php endforeach?>
						<tr id="task-elapsed-time-button-row"
							<?php if ( ! $arResult['ALLOWED_ACTIONS']['ACTION_ELAPSED_TIME_ADD'] ):?>
								style="display: none;"
							<?php endif?>
						>
							<td class="task-time-date-column"><a class="task-add-new" id="task-add-elapsed-time"><span></span><?php echo GetMessage("TASKS_ELAPSED_ADD")?></a></td>
							<td class="task-time-author-column">&nbsp;</td>
							<td class="task-time-spent-column">&nbsp;</td>
							<td class="task-time-comment-column">
								<div class="wrap-edit-nav">&nbsp;</div>
							</td>
						</tr>
						<tr id="task-elapsed-time-form-row" style="display: none;">
							<td class="task-time-date-column">
								<span class="webform-field-textbox-inner">
									<input type="text" id="task-elapsed-time-date" class="webform-field-textbox" name="CREATED_DATE" value="" onclick="BX.calendar({
													node: this, 
													field: 'task-elapsed-time-date', 
													form: '', 
													bTime: false,  
													value: BX('task-elapsed-time-date').value,
													bHideTimebar: true
												})"
										readonly="readonly"
									/>
								</span>
							</td>
							<td class="task-time-author-column">&nbsp;</td>
							<td class="task-time-spent-column"
								><nobr><span class="webform-field-textbox-inner"><input type="text" name="HOURS" value="1" class="webform-field-textbox" /></span><span><?php
									echo GetMessage("TASKS_ELAPSED_H");
								?></span><span class="webform-field-textbox-inner"><input type="text" name="MINUTES" value="00" class="webform-field-textbox" /></span><span><?php
									echo GetMessage("TASKS_ELAPSED_M");
								?></span><input type="text" name="SECONDS" value="00" style="display:none;" /><span><?php
									//echo GetMessage("TASKS_ELAPSED_S");
								?></span></nobr></td>
							<td class="task-time-comment-column" id="task-time-comment-column"><div class="wrap-edit-nav"><span class="webform-field-textbox-inner" style="width:80%"><input type="text" name="COMMENT_TEXT" value="" class="webform-field-textbox" style="width:100%" /></span><span class="task-edit-nav"><a class="task-table-edit-ok" id="task-send-elapsed-time"></a><a class="task-table-edit-remove" id="task-cancel-elapsed-time"></a></span></div></td>
						</tr>
				</table>
			</form>
		</div>
	</div>
	<script type="text/javascript">
		if (window.location.hash == "#elapsed")
		{
			(BX.proxy(ToggleSwitcher, BX("task-time-switcher")))();
		}
	</script>

</div>

<?php
// =========================== Start of right sidebar area ===========================
$this->SetViewTarget("sidebar_tools_1", 100);
$APPLICATION->IncludeComponent(
	"bitrix:tasks.task.detail.parts",
	".default",
	array(
		'MODE'                 => 'VIEW TASK',
		'DEFER_LOAD'           => 'N',		// load not inline, but on ajax
		'BLOCKS'               => array('right_sidebar'),
		'IS_IFRAME'            => $arResult['IS_IFRAME'],
		'GROUP_ID'             => $arParams['GROUP_ID'],
		'PATH_TO_TEMPLATES_TEMPLATE' => $arParams['PATH_TO_TEMPLATES_TEMPLATE'],
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
		'NAME_TEMPLATE'        => $arParams['NAME_TEMPLATE'],
		'ALLOWED_ACTIONS'      => $arResult['ALLOWED_ACTIONS'],
		'TASK_ID'              => $arResult['TASK']['ID'],
		'TASK'                 => $arResult['TASK'],
		"RATING_TYPE"          => $arParams['RATING_TYPE'],
		"COMPANY_WORKTIME"     => $arResult['COMPANY_WORKTIME']
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$this->EndViewTarget();
// =========================== End of right sidebar area ===========================

if ( ! $arResult['IS_IFRAME'] )
{
	ob_start();
	?>
	<div class="task-popup-templates" id="task-popup-templates-popup-content" style="display:none;">
		<div class="task-popup-templates-title"><?php echo GetMessage("TASKS_ADD_TEMPLATE_SUBTASK")?></div>
		<div class="popup-window-hr"><i></i></div>
		<?php if (sizeof($arResult["TEMPLATES"]) > 0):?>
			<ol class="task-popup-templates-items">
				<?php $commonUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"))?>
				<?php foreach($arResult["TEMPLATES"] as $template):?>
				<?php $createUrl = $commonUrl.(strpos($commonUrl, "?") === false ? "?" : "&")."TEMPLATE=".$template["ID"]."&PARENT_ID=".$arResult["TASK"]["ID"];?>
				<li class="task-popup-templates-item"><a class="task-popup-templates-item-link" href="<?php echo $createUrl?>" onclick="AddPopupTemplateSubtask(<?php echo $template["ID"]?>, <?php echo $arResult["TASK"]["ID"]?>, event)"><?php echo $template["TITLE"]?></a></li>
				<?php endforeach?>
			</ol>
		<?php else:?>
			<div class="task-popup-templates-empty"><?php echo GetMessage("TASKS_NO_TEMPLATES")?></div>
		<?php endif?>
		<div class="popup-window-hr"><i></i></div>
		<a class="task-popup-templates-item task-popup-templates-item-all" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TEMPLATES"], array())?>"><?php echo GetMessage("TASKS_TEMPLATES_LIST")?></a>
	</div>
	<?php
	$templatesPopupHtml = ob_get_clean();

	$ynNotGroupList = 'Y';
	if ($arParams['GROUP_ID'] != 0)
		$ynNotGroupList = 'N';

	$arComponentParams = array(
		'USER_ID'                => $arParams['USER_ID'],
		'GROUP_ID'               => $arParams['GROUP_ID'],
		'SHOW_TASK_LIST_MODES'   => 'N',
		'SHOW_HELP_ICON'         => 'N',
		'SHOW_SEARCH_FIELD'      => 'N',
		'SHOW_TEMPLATES_TOOLBAR' => 'N',
		'SHOW_QUICK_TASK_ADD'    => 'N',
		'SHOW_ADD_TASK_BUTTON'   => 'N',
		'SHOW_FILTER_BUTTON'     => 'N',
		'SHOW_SECTIONS_BAR'      => 'Y',
		'SHOW_FILTER_BAR'        => 'N',
		'SHOW_COUNTERS_BAR'      => 'N',
		'SHOW_SECTION_PROJECTS'  =>  $ynNotGroupList,
		'SHOW_SECTION_MANAGE'    => 'A',
		'SHOW_SECTION_COUNTERS'  =>  $ynNotGroupList,
		'MARK_ACTIVE_ROLE'       => 'N',
		'SECTION_URL_PREFIX'     =>  CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array()),
		'CUSTOM_ELEMENTS' => array(
			'ADD_BUTTON' => array(
				'name'            =>  GetMessage('TASKS_ADD_SUBTASK_2'),
				'onclick'         => 'ShowNewTaskMenu(this, ' . (int) $arResult["TASK"]["ID"] . ', createMenu)',
				'url'             =>  null,
				'separator_after' => 'Y'
			),
			'TEMPLATES_TOOLBAR' => array(
				'title'           =>  GetMessage('TASKS_ADD_TEMPLATE_SUBTASK'),
				'onclick'         => 'return ShowTemplatesPopup(this)',
				'url'             => '',
				'html_after'      => $templatesPopupHtml,
				'separator_after' => 'Y'
			),
			'BACK_BUTTON_ALT' => array(
				'name'    =>  GetMessage('TASKS_ADD_BACK_TO_TASKS_LIST'),
				'onclick' =>  null,
				'url'     =>  CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array())
			)
		)
	);

	if ($arParams['USER_ID'] > 0)
	{
		$arComponentParams['PATH_TO_PROJECTS'] = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],
			array('user_id' => $arParams['USER_ID'])
		);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:tasks.list.controls',
		'.default',
		$arComponentParams,
		null,
		array('HIDE_ICONS' => 'Y')
	);
}
?>

<script>tasksTaskDetailDefaultTemplateInit()</script>