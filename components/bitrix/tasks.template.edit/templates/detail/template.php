<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

CUtil::InitJSCore(array('popup', 'tooltip', 'task_popups'));

if(!intval($arResult['TASK']['ID']))
{
	ShowError(GetMessage("TASKS_TEMPLATE_NOT_FOUND"));
	return;
}

// commented out probably wrong script $GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.task.edit/templates/.default/script.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/script.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/table-view.js");
//$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/tasks/task-reminders.js");
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
	TASKS_HOURS_N : '<?php echo CUtil::JSEscape(GetMessage("TASKS_HOURS_N")); ?>',
	TASKS_HOURS_G : '<?php echo CUtil::JSEscape(GetMessage("TASKS_HOURS_G")); ?>',
	TASKS_HOURS_P : '<?php echo CUtil::JSEscape(GetMessage("TASKS_HOURS_P")); ?>',
	//TASKS_REMINDER_TITLE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMINDER_TITLE")); ?>',
	TASKS_ABOUT_DEADLINE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_ABOUT_DEADLINE")); ?>',
	TASKS_BY_DATE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_BY_DATE")); ?>',
	//TASKS_REMIND_BEFORE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMIND_BEFORE")); ?>',
	//TASKS_REMIND_VIA_JABBER : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMIND_VIA_JABBER")); ?>',
	//TASKS_REMIND_VIA_EMAIL : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMIND_VIA_EMAIL")); ?>',
	//TASKS_REMIND_VIA_JABBER_EX : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMIND_VIA_JABBER_EX")); ?>',
	//TASKS_REMIND_VIA_EMAIL_EX : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMIND_VIA_EMAIL_EX")); ?>',
	//TASKS_REMINDER_OK : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMINDER_OK")); ?>',
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

<?/*
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
			)
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
*/?>

var arFilter = {};
var arOrder = {};
var tasksListAjaxUrl = "/bitrix/components/bitrix/tasks.list/ajax.php?SITE_ID=<?php echo SITE_ID?><?php echo $arResult["TASK_TYPE"] == "group" ? "&GROUP_ID=".$arParams["GROUP_ID"] : ""?>&nt=<?php echo urlencode($arParams['NAME_TEMPLATE']); ?>";
var ajaxUrl = tasksListAjaxUrl;
var postFormAction = "<?php echo CUtil::JSEscape(POST_FORM_ACTION_URI)?>";
var detailTaksID = <?php echo $arResult["TASK"]["ID"]?>;

var currentUser = <?php echo $USER->GetID(); ?>;
var defaultQuickParent = <?php echo $arResult["TASK"]["ID"]?>;

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

<?/*
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
*/?>
</script>


<div class="webform task-detail">

	<div class="webform-round-corners webform-main-block webform-main-block-topless webform-main-block-bottomless">
		<div class="webform-content">
			<div class="task-detail-title"><b><?php echo $arResult["TASK"]["TITLE"]?></b></div><br />
			<div class="task-detail-description"><?php
				echo $arResult['TASK']['DESCRIPTION'];
			?></div>

			<?if(is_array($arResult['CHECKLIST_ITEMS']) && !empty($arResult['CHECKLIST_ITEMS'])):?>

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
						'CHECKLIST_ITEMS'      => $arResult['CHECKLIST_ITEMS'],
						'READ_ONLY'            => 'Y'
					),
					null,
					array('HIDE_ICONS' => 'Y')
				);
				?>

			<?endif?>
		</div>
	</div>

	<?if($arResult['DISPLAY_BLOCKS']['CONNECTED_ENTITIES'] || $arResult['DISPLAY_BLOCKS']['PROPERTIES']):?>

		<div class="webform-round-corners webform-additional-block webform-additional-block-topless">
			<div class="webform-content">

				<?if(!empty($arResult["TASK"]["TAGS"])):/*remove when write access enabled*/?>

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
												"VALUE" => $arResult["TASK"]["TAGS"],
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
											echo implode(", ", array_map('htmlspecialcharsbx', $arResult["TASK"]["TAGS"]));
										}
										else
										{
											echo htmlspecialcharsbx($arResult["TASK"]["TAGS"]);
										}
									}
									else
									{
										echo GetMessage("TASKS_TASK_NO_TAGS");
									}
								?></span>
							</td>
						</tr>
					</table>

				<?endif?>

				<?// group?>
				<?php if ($arResult['ALLOWED_ACTIONS']['ACTION_EDIT']):?>
					<div class="task-detail-group"><label><?php echo GetMessage("TASKS_TASK_GROUP")?>:</label><span class="task-detail-group-name task-detail-group-name-inline"><a href="<?php echo $arResult["TASK"]["GROUP_ID"] ? CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $arResult["TASK"]["GROUP_ID"])) : "javascript: void(0);"?>" class="webform-field-action-link" id="task-group-change"><?php if ($arResult["TASK"]["GROUP_ID"]):?><?php echo $arResult["TASK"]["GROUP_NAME"]?></a><span class="task-group-delete" onclick="tasksDetailsNS.clearGroup(<?php echo $arResult["TASK"]["GROUP_ID"]?>, this)"></span><?php else:?><?php echo GetMessage("TASKS_GROUP_ADD")?></a><?php endif?></span></div>
					<input id="task-detail-selected-group-id" type="hidden" value="<?php echo (int) $arResult["TASK"]["GROUP_ID"]; ?>">
				<?php elseif ($arResult["TASK"]["GROUP_ID"] && CSocNetGroup::CanUserViewGroup($USER->GetID(), $arResult["TASK"]["GROUP_ID"])):?>
					<div class="task-detail-group"><span class="task-detail-group-label"><?php echo GetMessage("TASKS_TASK_GROUP")?>:</span><span class="task-detail-group-name"><a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $arResult["TASK"]["GROUP_ID"]))?>" class="task-detail-group-link" target="_top"><?php echo $arResult["GROUPS"][$arResult["TASK"]["GROUP_ID"]]['NAME']?></a></span></div>
				<?php endif?>

				<?
				if (intval($arResult["TASK"]["BASE_TEMPLATE_ID"]))
				{
					?>
						<div class="task-detail-supertask">
							<span class="task-detail-supertask-label"><?php echo GetMessage("TASKS_BASE_TEMPLATE")?>:</span>
							<span class="task-detail-group-name"><a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => $arResult["TASK"]["BASE_TEMPLATE_ID"], "action" => "view"))?>" class="task-detail-group-link"<?php if ($arResult["IS_IFRAME"]):?> onclick="taskIFramePopup.view(<?php echo $arResult["TASK"]["BASE_TEMPLATE_ID"]?>);return false;"<?php endif?>><?php echo $arResult["TASK"]["BASE_TEMPLATE_DATA"]["TITLE"]?></a></span>
						</div>
					<?
				}

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
				?>

				<?
				if (is_array($arResult["USER_FIELDS"]))
				{
					foreach ($arResult["USER_FIELDS"] as $arUserField)
					{
						if($arUserField['FIELD_NAME'] == \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode())
						{
							\Bitrix\Tasks\Util\UserField\UI::showView($arUserField);
						}
					}
				}
				?>

				<?if($arResult['DISPLAY_BLOCKS']['PROPERTIES']):?>

					<?if(is_array($arResult["USER_FIELDS"])):?>

						<div class="task-detail-properties<?=(!$arResult['DISPLAY_BLOCKS']['CONNECTED_ENTITIES'] ? ' task-detail-properties-no-line' : '')?>">
							<table cellspacing="0" class="task-properties-layout">
								<?//todo: uf managing form here (as a component)?>
								<?
								$deprecatedUfs = array('file', 'vote', 'video', 'disk_version', 'string_formatted', 'url_preview');
								foreach($arResult["USER_FIELDS"] as $arUserField)
								{
									// Don't show this field in "extra-section", because we have shown it above
									if (
										$arUserField['FIELD_NAME'] === \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode() ||
										in_array($arUserField['USER_TYPE_ID'], $deprecatedUfs) ||
										empty($arUserField["VALUE"])
									)
									{
										continue;
									}
									?>
									<tr>
										<td class="task-property-name"><?php echo htmlspecialcharsbx($arUserField["EDIT_FORM_LABEL"])?>:</td>
										<td class="task-property-value"><span class="fields">
											<?\Bitrix\Tasks\Util\UserField\UI::showView($arUserField);?>
										</td>
									</tr>
									<?php
								}
								?>
							</table>
						</div>

					<?endif?>

				<?endif?>

			</div>
			<div class="webform-corners-bottom">
				<div class="webform-left-corner"></div>
				<div class="webform-right-corner"></div>
			</div>
		</div>

	<?endif?>

	<?
	// =========================== Start of buttons area ===========================

	$menuItems = array();

	if($arResult['TASK']['TPARAM_TYPE'] != CTaskTemplates::TYPE_FOR_NEW_USER)
	{
		array_unshift($menuItems, array(
				'TITLE' => GetMessage("TASKS_TEMPLATE_CREATE_SUB"),
				'CLASS_NAME' => "menu-popup-item-create",
				'HREF' => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => 0, "action" => "edit")).'?BASE_TEMPLATE='.intval($arResult['TASK']['ID'])
		));
	}

	if(!intval($arResult['TASK']['BASE_TEMPLATE_ID']) && $arResult['TASK']['TPARAM_TYPE'] != CTaskTemplates::TYPE_FOR_NEW_USER)
	{
		array_unshift($menuItems, array(
			'TITLE' => GetMessage("TASKS_TEMPLATE_CREATE_TASK"),
			'CLASS_NAME' => "menu-popup-item-create",
			'HREF' => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit")).'?TEMPLATE='.intval($arResult['TASK']['ID'])
		));
	}

	$APPLICATION->IncludeComponent(
		"bitrix:tasks.task.detail.parts",
		".default",
		array(
			'MODE'                 => 'VIEW TASK',
			'BLOCKS'               => array("buttons"),
			'IS_IFRAME'            => $arResult['IS_IFRAME'],
			'GROUP_ID'             => $arParams['GROUP_ID'],
			'PATH_TO_TASKS_TASK'   => str_replace('#template_id#', '#task_id#', $arParams['PATH_TO_TEMPLATES_TEMPLATE']),
			'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
			'NAME_TEMPLATE'        => $arParams['NAME_TEMPLATE'],
			'ALLOWED_ACTIONS'      => array(
				'ACTION_REMOVE' => true,
				'ACTION_EDIT' => true
			),
			'TASK_ID'              => $arResult['TASK']['ID'],
			'TASK'                 => $arResult['TASK'],

			'SHOW_RATING'          => 'N',
			'MENU_ITEMS'           => $menuItems,
			'CONTROLLER_ID'        => 'tasks.template.edit', // append to all links this controller id value
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
	// =========================== End of buttons area ===========================
	?>
</div>

<div class="webform task-detail" style="overflow-x:auto; margin-bottom:20px;">

	<div class="task-detail-subtasks <?php if ($arResult['IS_IFRAME']) echo 'task-detail-subtasks-iframe'; ?>"
		id="task-detail-subtasks-block"
		<?if(!intval($arResult['TASK']['TEMPLATE_CHILDREN_COUNT'])):?>style="display: none"<?endif?>
	>

		<?
		$pathParams = array();
		if(is_array($arParams))
		{
			foreach($arParams as $param => $value)
			{
				if(strpos($param, 'PATH_') == 0)
					$pathParams[$param] = $value;
			}
		}

		$APPLICATION->IncludeComponent(
			'bitrix:tasks.templates.list', '.default',
			array_merge($pathParams, array(
				'HIDE_VIEWS'         => 'Y',
				'HIDE_MENU'          => 'Y',
				'HIDE_GROUP_ACTIONS' => 'Y',
				'FORCE_LIST_MODE'    => 'Y',
				'PREVENT_PAGE_ONE_COLUMN' => 'Y',
				'PREVENT_FLEXIBLE_LAYOUT' => ($arResult['IS_IFRAME'] ? 'N' : 'Y'),
				'COMMON_FILTER'      => array(),
				'ORDER'              => array('GROUP_ID'  => 'ASC'),
				'VIEW_STATE'         => array(),
				'CONTEXT_ID'         => CTaskColumnContext::CONTEXT_TASK_DETAIL,
				'BASE_TEMPLATE_ID'   => $arResult['TASK']['ID'],

				'SHOW_GROUP_ACTIONS' =>	'N',
			)), null, array("HIDE_ICONS" => "Y")
		);
		?>
	</div>

	<?/*
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
											<div class="task-project-name"><span class="task-project-folding<?php if (!$arResult["GROUPS"][$task["GROUP_ID"]]["EXPANDED"]):?> task-project-folding-closed<?php endif?>" onclick="ToggleProjectTasks(<?php echo $arResult["GROUPS"][$task["GROUP_ID"]]["ID"]?>, event);"></span><a class="task-project-name-link" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_TASKS"], array("group_id" => $arResult["GROUPS"][$task["GROUP_ID"]]["ID"]))?>" onclick="ToggleProjectTasks(<?php echo $arResult["GROUPS"][$task["GROUP_ID"]]["ID"]?>, event);"><?php echo $arResult["GROUPS"][$task["GROUP_ID"]]["NAME"]?></a></div>
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
	*/?>

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

		"DISPLAY_DATA"            => array(
			'CREATOR',
			'RESPONSIBLE',
			'ACCOMPLICES',
			'AUDITORS',
			'PRIORITY'
		),
		"SHOW_EDIT_MEMBERS" => 'N'
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$this->EndViewTarget();
// =========================== End of right sidebar area ===========================

// tasks.list.controls

/*
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
		((defined('SITE_TEMPLATE_ID') && (SITE_TEMPLATE_ID === 'bitrix24')) ? 'bitrix24' : '.default'),
		$arComponentParams,
		null,
		array('HIDE_ICONS' => 'Y')
	);
}
*/
?>

<script>tasksTaskDetailDefaultTemplateInit()</script>