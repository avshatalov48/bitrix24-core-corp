<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

if ($arResult['needStep'])
{
	?>
	<div id="task-reminder-link"><!--
		don't remove this, cause item with id="task-reminder-link" checked to be exists by javascript when page opened in iframe
	--></div>
	<div class="task-edit-stepper-message">
	<?php
		echo str_replace(
			array(
				'#DONE#',
				'#TOTAL#'
			),
			array(
				(int) $arResult['stepIndex'],
				(int) $arResult['stepIndexesTotal']
			),
			GetMessage('TASKS_STEPPER_CREATED_PROGRESS_TITLE')
		) . "<br>\n";
		?>
	</div>
	<form action="<?php echo POST_FORM_ACTION_URI ?>" method="post" id="task-edit-stepper-form">
		<input type="hidden" name="_JS_STEPPER_DO_NEXT_STEP" value="Y">
		<input type="hidden" name="FORM_GUID" value="<?php echo htmlspecialcharsbx($arResult['PREV_FORM_GUID']); ?>">
		<?php echo bitrix_sessid_post(); ?>
	</form>
	<script type="text/javascript">
		<?php if ($arResult["IS_IFRAME"]):?>
		(function() {
			var iframePopup = window.top.BX.TasksIFrameInst;
			if (iframePopup)
				iframePopup.onTaskLoaded();
		})();
		<?php endif?>
		BX('task-edit-stepper-form').submit();
	</script>
	<?php
	exit();
}

CUtil::InitJSCore(array('popup', 'tooltip', 'date', 'CJSTask', 'task-popups'));

$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.task.edit/templates/.default/script.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/tasks/task-reminders.js");

$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/main/core/css/core_popup.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");

$GLOBALS["APPLICATION"]->IncludeComponent(
	'bitrix:main.calendar', '', array(
		'SILENT' => 'Y',
	), null, array('HIDE_ICONS' => 'Y')
);

$arPriorities = array(
	array("name" => GetMessage("TASKS_PRIORITY_LOW"), "class" => "low"),
	array("name" => GetMessage("TASKS_PRIORITY_MIDDLE"), "class" => "middle"),
	array("name" => GetMessage("TASKS_PRIORITY_HIGH"), "class" => "high")
);
$arPeriods = array(
	array("key" => "daily", "name" => GetMessage("TASKS_REPEAT_PERIOD_DAILY")),
	array("key" => "weekly", "name" => GetMessage("TASKS_REPEAT_PERIOD_WEEKLY")),
	array("key" => "monthly", "name" => GetMessage("TASKS_REPEAT_PERIOD_MONTHLY")),
	array("key" => "yearly", "name" => GetMessage("TASKS_REPEAT_PERIOD_YEARLY"))
);

$arData = &$arResult["DATA"];

$arPaths = array(
	"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
	"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"]
);

// Will be true, if $arData["CREATED_BY"] is manager of $arData["RESPONSIBLE_ID"]
$bSubordinate = CTasks::IsSubordinate($arData["RESPONSIBLE_ID"], $arData["CREATED_BY"])
	|| CTasks::IsSubordinate($arData["RESPONSIBLE_ID"], $USER->getId());

$taskDescriptionEditorId = 'task'.intval($arResult['DATA']['ID']).'description';
?>
<script type="text/javascript">
	BX.message({
		TASKS_DEFAULT_TITLE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_DEFAULT_TITLE")); ?>',
		TASKS_CANCEL : '<?php echo CUtil::JSEscape(GetMessage("TASKS_CANCEL")); ?>',
		TASKS_SELECT : '<?php echo CUtil::JSEscape(GetMessage("TASKS_SELECT")); ?>',
		TASKS_CLOSE_POPUP : '<?php echo CUtil::JSEscape(GetMessage("TASKS_CLOSE_POPUP")); ?>',
		TASKS_RESPONSIBLES : '<?php echo CUtil::JSEscape(GetMessage("TASKS_RESPONSIBLES")); ?>',
		TASKS_RESPONSIBLE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_RESPONSIBLE")); ?>',
		TASKS_PATH_TO_USER_PROFILE : '<?php echo CUtil::JSEscape($arParams["PATH_TO_USER_PROFILE"]); ?>',
		TASKS_PATH_TO_TASK : '<?php echo CUtil::JSEscape($arParams["PATH_TO_TASKS_TASK"]); ?>',
		TASKS_PATH_TO_GROUP : '<?php echo CUtil::JSEscape($arParams["PATH_TO_GROUP"]); ?>',
		TASKS_DELETE_CONFIRM : '<?php echo CUtil::JSEscape(GetMessage("TASKS_DELETE_CONFIRM")); ?>',
		TASKS_REMINDER_TITLE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMINDER_TITLE")); ?>',
		TASKS_ABOUT_DEADLINE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_ABOUT_DEADLINE")); ?>',
		TASKS_BY_DATE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_BY_DATE")); ?>',
		TASKS_REMIND_BEFORE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMIND_BEFORE")); ?>',
		TASKS_REMIND_VIA_JABBER : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMIND_VIA_JABBER")); ?>',
		TASKS_REMIND_VIA_EMAIL : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMIND_VIA_EMAIL")); ?>',
		TASKS_REMIND_VIA_JABBER_EX : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMIND_VIA_JABBER_EX")); ?>',
		TASKS_REMIND_VIA_EMAIL_EX : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMIND_VIA_EMAIL_EX")); ?>',
		TASKS_REMINDER_OK : '<?php echo CUtil::JSEscape(GetMessage("TASKS_REMINDER_OK")); ?>',
		TASKS_TASK_GROUP : '<?php echo CUtil::JSEscape(GetMessage("TASKS_TASK_GROUP")); ?>',
		TASKS_DATE_MUST_BE_IN_FUTURE : '<?php echo CUtil::JSEscape(GetMessage("TASKS_DATE_MUST_BE_IN_FUTURE")); ?>',
		TASKS_META_RECCOMEND_TASK_CONTROL : '<?php echo $arResult['RECCOMEND_TASK_CONTROL']; ?>',		
		TASKS_WARNING_RESPONSIBLE_NOT_IN_TASK_GROUP : 
			'<?php echo CUtil::JSEscape(GetMessage('TASKS_WARNING_RESPONSIBLE_NOT_IN_TASK_GROUP')); ?>'
	});

	<?
	if ($arResult["IS_IFRAME"])
	{
		?>
		(function() {
			var columnsIds = null;

			if (window.top.tasksListNS && window.top.tasksListNS.getColumnsOrder)
			{
				columnsIds = window.top.tasksListNS.getColumnsOrder();

				if (columnsIds)
				{
					BX.ready((function(columnsIds){
						return function(){
							BX('tasks-meta-columnsIds').value = columnsIds.join(',');
						};
					}(columnsIds)));
				}
			}

			BX.addCustomEvent(
				window, 
				'OnEditorInitedAfter', 
				function(editor){
					if (
						window.top.BX.TasksIFrameInst
						&& (window.top.BX.TasksIFrameInst.descriptionBuffered !== null)
					)
					{
						editor.SetContent(window.top.BX.TasksIFrameInst.descriptionBuffered);
						window.top.BX.TasksIFrameInst.descriptionBuffered = null;
					}
				}
			);

			var iframePopup = window.top.BX.TasksIFrameInst;
			if (iframePopup)
			{
				<?php
				if (
					$arResult["TASK"] 
					&& ($arResult["CALLBACK"] == "CHANGED" || $arResult["CALLBACK"] == "ADDED")
				)
				{
					?>
					window.top.BX.TasksIFrameInst.isEditMode = true;
					window.top.BX.TasksIFrameInst.<?php if ($arResult["CALLBACK"] == "CHANGED"):?>onTaskChanged<?php else:?>onTaskAdded<?php endif?>(<?php

							$bSkipJsMenu = false;
							$bIsIe = false;
							$userAgent = strtolower($_SERVER["HTTP_USER_AGENT"]);
							if (strpos($userAgent, "opera") === false && strpos($userAgent, "msie") !== false)
								$bIsIe = true;

							if (isset($arResult["IS_IFRAME"]) && ($arResult["IS_IFRAME"] === true) && $bIsIe)
								$bSkipJsMenu = true;

							tasksRenderJSON($arResult["TASK"], $arResult["TASK"]["CHILDREN_COUNT"],
								$arPaths, true, true, true, $arParams["NAME_TEMPLATE"], 
								$arAdditionalFields = array(), $bSkipJsMenu
							);
						?>);
					<?php
				}
				?>

				BX.ready(
					(function(iframePopup){
						return function()
						{
							<?//seems it doesnt work?>
							BX.addCustomEvent(
								iframePopup,
								'onBeforeCloseByEscape',
								function(params){

									var editor = null;
									if(typeof window.BXHtmlEditor != 'undefined')
										editor = window.BXHtmlEditor.Get('<?=$taskDescriptionEditorId?>');

									if (BX('task-title') && (typeof editor !== 'undefined' && editor !== null))
									{
										if (
											BX('task-title').value.length
											|| editor.GetContent().length
										)
										{
											params.canClose = false;
										}
									}
								}	
							);
						};
					})(iframePopup)
				)
			}
		})();
		<?php
	}
	?>

	var prevUserSubordinate = <?php echo ($bSubordinate ? 'true' : 'false'); ?>;
	var previousUser = currentUser = <?php echo (int) $arData["RESPONSIBLE_ID"];?>;
	var isAdmin = <?php echo $USER->isAdmin() ? "true" : "false"?>;
	var isPortalB24Admin = <?php
		if (CTasksTools::IsPortalB24Admin())
			echo  'true';
		else
			echo 'false';
		?>;
	var loggedInUser = <?php echo (int) $USER->GetID(); ?>;
	var previousUserName = currentUserName = "<?php	echo CUtil::JSEscape(CUser::FormatName($arParams['NAME_TEMPLATE'], array(
		"NAME" => $arData["~RESPONSIBLE_NAME"],
		"LAST_NAME" => $arData["~RESPONSIBLE_LAST_NAME"],
		"SECOND_NAME" => $arData["~RESPONSIBLE_SECOND_NAME"],
		"LOGIN" => $arData["~RESPONSIBLE_LOGIN"]
	), true, false)); ?>";

	var reminders = <?php echo $arData["REMINDERS"] ? CUtil::PhpToJsObject($arData["REMINDERS"]) : "[]" ?>;

	var tasksRemindersPopUp;

	BX.ready(function() {

		taskManagerForm.init({editorId: '<?=$taskDescriptionEditorId?>'});

		tasksRemindersPopUp = new BX.TaskReminders.create("tasks-reminder-popup", BX("task-reminder-link"), reminders, <?php echo $arData["DEADLINE"] ? "\"".CUtil::JSEscape($arData["DEADLINE"])."\"" : "false" ?>, {
			events: {
				onRemindersChange: function (reminders) {
					var reminderSpan = BX("task-reminder-link").parentNode;
					var link = BX("task-reminder-link");
					BX.cleanNode(reminderSpan);
					reminderSpan.appendChild(link);
					for (var i = 0; i < this.reminders.length; i++) {
						reminderSpan.appendChild(BX.create("input", {
							props: {
								type: "hidden",
								name: "REMINDERS[" + i + "][date]",
								value: BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')), this.reminders[i].date)
								//value: this.calendar.FormatDate(this.reminders[i].date)
							}
						}));
						reminderSpan.appendChild(BX.create("input", {
							props: {
								type: "hidden",
								name: "REMINDERS[" + i + "][type]",
								value: this.reminders[i].type
							}
						}));
						reminderSpan.appendChild(BX.create("input", {
							props: {
								type: "hidden",
								name: "REMINDERS[" + i + "][transport]",
								value: this.reminders[i].transport
							}
						}));
					}
					if (reminders.length) {
						BX.addClass(BX("task-reminder-link").parentNode, "task-reminder-selected");
					} else {
						BX.removeClass(BX("task-reminder-link").parentNode, "task-reminder-selected");
					}
				}
			},
			defaultTime: {
				hour: <?=intval($arResult['COMPANY_WORKTIME']['START']['H'])?>,
				minute: <?=intval($arResult['COMPANY_WORKTIME']['START']['M'])?>
			}
		});

		BX.bind(BX("task-reminder-link"), "click", function (e) {
			if(!e) e = window.event;

			tasksRemindersPopUp.show();

			BX.PreventDefault(e);
		});
	});
</script>

<form action="<?php echo POST_FORM_ACTION_URI ?>" method="post" name="task-edit-form" id="task-edit-form" enctype="multipart/form-data">
	<?php echo bitrix_sessid_post() ?>
	<?php
	if (isset($arResult['FORM_GUID']))
	{
		?>
		<input type="hidden" name="FORM_GUID" value="<?php echo htmlspecialcharsbx($arResult['FORM_GUID']); ?>">
		<?php
	}
	?>
	<input type="hidden" name="_JS_STEPPER_SUPPORTED" value="Y">
	<input type="hidden" name="DESCRIPTION_IN_BBCODE" value="<?php echo $arData['DESCRIPTION_IN_BBCODE']; ?>">
	<div class="webform task-webform">
		<?php if (isset($arResult["ERRORS"]) && sizeof($arResult["ERRORS"]) > 0): ?>
			<div class="webform-round-corners webform-error-block">
				<div class="webform-corners-top"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>
				<div class="webform-content">
					<ul class="webform-error-list">
						<?php foreach ($arResult["ERRORS"] as $error): ?>
							<li><?php echo htmlspecialcharsbx($error["text"]) ?></li>
						<?php endforeach ?>
					</ul>
				</div>
				<div class="webform-corners-bottom"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>
			</div>
		<?php endif ?>

		<div class="webform-round-corners webform-main-fields task-main-fields">
			<div class="webform-corners-top">
				<div class="webform-left-corner"></div>
				<div class="webform-right-corner"></div>
			</div>
			<div class="webform-content">

				<div class="webform-row task-title-row">
					<div class="webform-field-label"><label for="task-title"><?php echo GetMessage("TASKS_TASK") ?></label></div>
					<div class="webform-field webform-field-textbox-double task-title">
						<div class="webform-field-textbox-inner"><input type="text" name="TITLE" id="task-title" style="height:23px;" class="webform-field-textbox<?php echo $arData["TITLE"] ? "" : " inactive" ?>" value="<?php echo $arData["TITLE"] ? $arData["TITLE"] : GetMessage("TASKS_DEFAULT_TITLE") ?>" /></div>
					</div>
				</div>

				<div class="webform-row task-responsible-employee-row">
					<table cellspacing="0" class="task-responsible-employee-layout">
						<tr>
							<td class="task-responsible-employee-layout-left">
								<div class="webform-field-label"><label for="task-responsible-employee" id="task-responsible-employee-label"><?php if ($arData["MULTITASK"] == "Y" && $arResult["ACTION"] == "create"): ?><?php echo GetMessage("TASKS_RESPONSIBLES") ?><?php else: ?><?php echo GetMessage("TASKS_RESPONSIBLE") ?><?php endif ?></label></div>

								<div class="webform-field webform-field-combobox<?php if ($arData["CREATED_BY"] != $USER->GetID() && $arResult["ACTION"] == "create"): ?> webform-field-combobox-disabled<?php endif ?> task-responsible-employee" id="task-responsible-employee-block"<?php if ($arData["MULTITASK"] == "Y" && $arResult["ACTION"] == "create"): ?> style="display:none;"<?php endif ?>>
									<div class="webform-field-combobox-inner">
										<input type="text" autocomplete="off" id="task-responsible-employee"<?php if ($arData["CREATED_BY"] != $USER->GetID() && $arResult["ACTION"] == "create"): ?> disabled="disabled"<?php endif ?> class="webform-field-combobox" value="<?php echo ($arData["~RESPONSIBLE_NAME"] || $arData["~RESPONSIBLE_LAST_NAME"] || $arData["~RESPONSIBLE_LOGIN"] ? CUser::FormatName($arParams["NAME_TEMPLATE"], array("NAME" => $arData["~RESPONSIBLE_NAME"], "LAST_NAME" => $arData["~RESPONSIBLE_LAST_NAME"], "LOGIN" => $arData["~RESPONSIBLE_LOGIN"], "SECOND_NAME" => $arData["~RESPONSIBLE_SECOND_NAME"]), true, true) : "") ?>" /><a href="" class="webform-field-combobox-arrow">&nbsp;</a>
										<input type="hidden" name="RESPONSIBLE_ID" value="<?php echo $arData["RESPONSIBLE_ID"] ?>" />
									</div>
								</div>
								<?php
								$name = $APPLICATION->IncludeComponent(
									"bitrix:intranet.user.selector.new", ".default", array(
										"MULTIPLE" => "N",
										"NAME" => "RESPONSIBLE",
										"INPUT_NAME" => "task-responsible-employee",
										"VALUE" => $arData["RESPONSIBLE_ID"],
										"POPUP" => "Y",
										"ON_SELECT" => "onResponsibleSelect",
										"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
										"SITE_ID" => SITE_ID,
										"GROUP_ID_FOR_SITE" => (isset($_GET["GROUP_ID"]) && intval($_GET["GROUP_ID"]) > 0 ? $_GET["GROUP_ID"] : (isset($arParams["GROUP_ID"]) && intval($arParams["GROUP_ID"]) > 0 ? $arParams["GROUP_ID"] : false)),
										'SHOW_EXTRANET_USERS' => 'FROM_MY_GROUPS',
										'DISPLAY_TAB_GROUP' => 'Y',
										'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
										'SHOW_LOGIN' => 'Y'
									), null, array("HIDE_ICONS" => "Y")
								);
								?>

								<?php if ($arResult["ACTION"] == "create"): ?>
									<div class="webform-field task-responsible-employees" id="task-responsible-employees-block"<?php if ($arData["MULTITASK"] != "Y"): ?> style="display:none;"<?php endif ?>>
										<div class="task-responsible-employees-list" id="task-responsible-employees-list">
											<?php if (sizeof($arData["RESPONSIBLES"]) > 0): ?>
												<?php
												$rsResponsibles = CUser::GetList($by = 'last_name', $order = 'asc', array("ID" => implode("|", $arData["RESPONSIBLES"])), array('SELECT' => array('UF_*','SECOND_NAME')));
												while ($user = $rsResponsibles->GetNext()):
													?>
													<div class="task-responsible-employee-item"><a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array("user_id" => $user["ID"])) ?>" class="task-responsible-employee-link" title="<?php echo CUser::FormatName($arParams["NAME_TEMPLATE"], $user, true, false) ?>" target="_blank"><?php echo CUser::FormatName($arParams["NAME_TEMPLATE"], $user, true, false) ?></a></div>
												<?php endwhile ?>
											<?php endif ?>
										</div>
										<div class="task-responsible-employees-change-link"><a href="" class="webform-field-action-link" id="task-responsibles-link"><?php echo GetMessage("TASKS_TASK_CHANGE_RESPONSIBLES") ?></a></div>
										<input type="hidden" name="RESPONSIBLES_IDS" value="<?php echo is_array($arData["RESPONSIBLES"]) ? implode(",", $arData["RESPONSIBLES"]) : "" ?>" />
									</div>
									<?php
									$name = $APPLICATION->IncludeComponent(
										"bitrix:intranet.user.selector.new", ".default", array(
											"MULTIPLE" => "Y",
											"NAME" => "RESPONSIBLES",
											"VALUE" => $arData["RESPONSIBLES"],
											"POPUP" => "Y",
											"ON_CHANGE" => "onResponsiblesChange",
											"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
											"SITE_ID" => SITE_ID,
											"GROUP_ID_FOR_SITE" => (isset($_GET["GROUP_ID"]) && intval($_GET["GROUP_ID"]) > 0 ? $_GET["GROUP_ID"] : (isset($arParams["GROUP_ID"]) && intval($arParams["GROUP_ID"]) > 0 ? $arParams["GROUP_ID"] : false)),
											'SHOW_EXTRANET_USERS' => 'FROM_MY_GROUPS',
											'DISPLAY_TAB_GROUP' => 'Y',
											'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
											'SHOW_LOGIN' => 'Y'
										), null, array("HIDE_ICONS" => "Y")
									);
									?>
								<?php endif ?>

								<?php
								if (
									($arResult["ACTION"] === 'create')
									|| $USER->isAdmin() 
									|| CTasksTools::IsPortalB24Admin()
									|| ($USER->GetID() == $arData['CREATED_BY'])
									|| (isset($arResult['ALLOWED_ACTIONS']) && $arResult['ALLOWED_ACTIONS']['ACTION_CHANGE_DIRECTOR'])
								): ?>
									<div class="webform-field task-director" id="task-director-employees-block"<?php if ($arData["MULTITASK"] == "Y"): ?> style="display:none;"<?php endif ?>>
										<div class="task-director-item">
											<a href="" class="webform-field-action-link" 
												id="task-author-employee"><?php echo GetMessage("TASKS_DIRECTOR") ?>:</a>
											<span><?php 
													echo CUser::FormatName(
														$arParams["NAME_TEMPLATE"], 
														array(
															"NAME"       => $arData["CREATED_BY_NAME"], 
															"LAST_NAME"  => $arData["CREATED_BY_LAST_NAME"], 
															"LOGIN"      => $arData["CREATED_BY_LOGIN"],
															"SECOND_NAME"=> $arData["CREATED_BY_SECOND_NAME"]));
															?>
											</span>
										</div>
										<input type="hidden" name="CREATED_BY" value="<?php echo $arData["CREATED_BY"] ?>" />
									</div>
									<?php
									$name = $APPLICATION->IncludeComponent(
										"bitrix:intranet.user.selector.new", ".default", array(
											"MULTIPLE" => "N",
											"NAME" => "AUTHOR",
											"VALUE" => $arData["CREATED_BY"],
											"POPUP" => "Y",
											"ON_SELECT" => "onAuthorSelect",
											"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
											"SITE_ID" => SITE_ID,
											"GROUP_ID_FOR_SITE" => (isset($_GET["GROUP_ID"]) && intval($_GET["GROUP_ID"]) > 0 ? $_GET["GROUP_ID"] : (isset($arParams["GROUP_ID"]) && intval($arParams["GROUP_ID"]) > 0 ? $arParams["GROUP_ID"] : false)),
											'SHOW_EXTRANET_USERS' => 'FROM_MY_GROUPS',
											'DISPLAY_TAB_GROUP' => 'Y',
											'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
											'SHOW_LOGIN' => 'Y'
										), null, array("HIDE_ICONS" => "Y")
									);
									?>
								<?php endif ?>

								<div class="webform-field task-assistants" id="task-assistants-block"<?php if ($arData["MULTITASK"] == "Y" && $arResult["ACTION"] == "create"): ?> style="display:none;"<?php endif ?>>
									<div class="task-assistants-label"><a href="" class="webform-field-action-link" id="task-assistants-link"><?php echo GetMessage("TASKS_TASK_ACCOMPLICES") ?><?php if (sizeof($arData["ACCOMPLICES"]) > 0): ?>:<?php endif ?></a></div>
									<div class="task-assistants-list" id="task-assistants-list">
										<?php if (is_array($arData["ACCOMPLICES"]) && (sizeof($arData["ACCOMPLICES"]) > 0)): ?>
											<?php
											$rsAccomplices = CUser::GetList($by = 'last_name', $order = 'asc', array("ID" => implode("|", $arData["ACCOMPLICES"])), array('SELECT' => array('UF_*')));
											while ($user = $rsAccomplices->GetNext()):
												?>
												<div class="task-assistant-item"><span class="task-assistant-link" title="<?php echo CUser::FormatName($arParams["NAME_TEMPLATE"], $user, true, false) ?>"><?php echo CUser::FormatName($arParams["NAME_TEMPLATE"], $user, true, false) ?></span></div>
											<?php endwhile ?>
										<?php endif ?>
									</div>
									<input type="hidden" name="ACCOMPLICES_IDS" value="<?php echo is_array($arData["ACCOMPLICES"]) ? implode(",", $arData["ACCOMPLICES"]) : "" ?>" />
								</div>
								<?php
								$name = $APPLICATION->IncludeComponent(
									"bitrix:intranet.user.selector.new", ".default", array(
										"MULTIPLE" => "Y",
										"NAME" => "ACCOMPLICES",
										"VALUE" => $arData["ACCOMPLICES"],
										"POPUP" => "Y",
										"ON_CHANGE" => "onAccomplicesChange",
										"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
										"SITE_ID" => SITE_ID,
										"GROUP_ID_FOR_SITE" => (isset($_GET["GROUP_ID"]) && intval($_GET["GROUP_ID"]) > 0 ? $_GET["GROUP_ID"] : (isset($arParams["GROUP_ID"]) && intval($arParams["GROUP_ID"]) > 0 ? $arParams["GROUP_ID"] : false)),
										'SHOW_EXTRANET_USERS' => 'FROM_MY_GROUPS',
										'DISPLAY_TAB_GROUP' => 'Y',
										'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
										'SHOW_LOGIN' => 'Y'
									), null, array("HIDE_ICONS" => "Y")
								);
								?>

							</td>
							<td class="task-responsible-employee-layout-right">

								<div class="webform-field task-priority" id="task-priority">
									<label><?php echo GetMessage("TASKS_TASK_PRIORITY") ?>:</label><?php foreach ($arPriorities as $key => $priority): ?><a href="" id="task-priority-<?php echo $key ?>" class="task-priority-<?php echo $priority["class"] ?><?php echo ($arData["PRIORITY"] == $key ? " selected" : "") ?>"><i></i><span><?php echo $priority["name"] ?></span><b></b></a><?php endforeach ?>
									<input type="hidden" name="PRIORITY" id="task-priority-field" value="<?php echo ($arData["PRIORITY"] ? $arData["PRIORITY"] : 0) ?>" />
								</div>

								<?php if ($arResult["ACTION"] == "create"): ?>
									<div class="webform-field task-duplicate">
										<div class="webform-field-checkbox-option<?php if ($arData["CREATED_BY"] != $USER->GetID()): ?> webform-field-checkbox-option-disabled<?php endif ?>"><input type="checkbox" value="Y" id="duplicate-task"<?php if ($arData["CREATED_BY"] != $USER->GetID()): ?> disabled="disabled"<?php endif ?> name="MULTITASK" class="webform-field-checkbox" onclick="CopyTask(this);"<?php if ($arData["MULTITASK"] == "Y"): ?> checked<?php endif ?> /><label for="duplicate-task"><?php echo GetMessage("TASKS_TASK_COPY") ?></label></div>
									</div>
								<?php endif ?>

								<div class="webform-field task-tags">
									<label><?php echo GetMessage("TASKS_TASK_TAGS") ?>:</label><?php
								$name = $APPLICATION->IncludeComponent(
									"bitrix:tasks.tags.selector", ".default", array(
										"NAME" => "TAGS",
										"VALUE" => $arData["TAGS"]
									), null, array("HIDE_ICONS" => "Y")
								);
								?>
								</div>
							</td>
						</tr>
					</table>
				</div>

				<div class="webform-row task-dates-row">
					<div class="webform-field webform-field-round-corners">
						<div class="webform-round-corners">
							<div class="webform-corners-top">
								<div class="webform-left-corner"></div>
								<div class="webform-right-corner"></div>
							</div>
							<div class="webform-content">
								<div class="webform-field task-deadline-settings"><label for="task-deadline-date"><?php echo GetMessage("TASKS_TASK_DEADLINE") ?>:</label><span class="webform-field webform-field-textbox webform-field-textbox-clearable<?php echo (!$arData["DEADLINE"] ? " webform-field-textbox-empty" : "") ?> task-deadline-date"><span class="webform-field-textbox-inner"><input type="text" id="task-deadline-date" class="webform-field-textbox" name="DEADLINE" value="<?php echo tasksTimeCutZeros($arData["DEADLINE"]) ?>" readonly="readonly" data-default-hour="<?=intval($arResult['COMPANY_WORKTIME']['END']['H'])?>" data-default-minute="<?=intval($arResult['COMPANY_WORKTIME']['END']['M'])?>" /><a class="webform-field-textbox-clear" href=""></a></span></span><a href="" class="webform-field-action-link task-planning-dates-link" id="task-planning-dates-link"><?php echo GetMessage("TASKS_TASK_PERION_PLANING") ?></a><span class="task-reminder<?php if ($arData["REMINDERS"]):?> task-reminder-selected<?php endif?>"><a href="" class="webform-field-action-link task-reminder-link" id="task-reminder-link"><?php echo GetMessage("TASKS_TASK_REMIND") ?></a></span></div>
								<div class="webform-field task-planning-dates" id="task-planning-dates"<?php if (!$arData["START_DATE_PLAN"] && !$arData["END_DATE_PLAN"] && !$arData["DURATION_PLAN"]): ?> style="display:none;"<?php endif ?>>
									<table cellspacing="0" class="task-dates-layout">
										<tr>
											<td class="task-planning-interval-label"><label for="task-start-date"><?php echo GetMessage("TASKS_TASK_START_PLAN") ?></label></td>
											<td class="task-planning-interval"><span class="webform-field webform-field-textbox webform-field-textbox-clearable<?php echo (!$arData["START_DATE_PLAN"] ? " webform-field-textbox-empty" : "") ?> task-start-date"><span class="webform-field-textbox-inner"><input type="text" id="task-start-date" class="webform-field-textbox" name="START_DATE_PLAN" value="<?php echo tasksTimeCutZeros($arData["START_DATE_PLAN"]) ?>" readonly="readonly" data-default-hour="<?=intval($arResult['COMPANY_WORKTIME']['START']['H'])?>" data-default-minute="<?=intval($arResult['COMPANY_WORKTIME']['START']['M'])?>" /><a class="webform-field-textbox-clear" href=""></a></span></span><label for="task-end-date"><?php echo GetMessage("TASKS_TASK_END_PLAN") ?></label><span class="webform-field webform-field-textbox webform-field-textbox-clearable<?php echo (!$arData["END_DATE_PLAN"] ? " webform-field-textbox-empty" : "") ?> task-end-date"><span class="webform-field-textbox-inner"><input type="text" id="task-end-date" class="webform-field-textbox" name="END_DATE_PLAN" value="<?php echo tasksTimeCutZeros($arData["END_DATE_PLAN"]) ?>" readonly="readonly" data-default-hour="<?=intval($arResult['COMPANY_WORKTIME']['END']['H'])?>" data-default-minute="<?=intval($arResult['COMPANY_WORKTIME']['END']['M'])?>"/><a class="webform-field-textbox-clear" href=""></a></span></span></td>
										</tr>
										<tr>
											<td class="task-planning-duration-label"><label for="task-duration"><?php echo GetMessage("TASKS_TASK_DURATION_PLAN") ?>:</label></td>
											<td class="task-planning-duration"><span class="webform-field webform-field-textbox task-duration"><span class="webform-field-textbox-inner"><input type="text" id="task-duration" maxlength="3" class="webform-field-textbox" name="DURATION_PLAN" value="<?php echo $arData["DURATION_PLAN"] ?>" /></span></span><?php if ($arData["DURATION_TYPE"] != "days"): ?><a href="" id="task-duration-hours" class="webform-field-action-link selected"><?php echo GetMessage("TASKS_TASK_DURATION_HOURS") ?></a><a href="" id="task-duration-days" class="webform-field-action-link"><?php echo GetMessage("TASKS_TASK_DURATION_DAYS") ?></a><?php else: ?><a href="" id="task-duration-days" class="webform-field-action-link selected"><?php echo GetMessage("TASKS_TASK_DURATION_DAYS") ?></a><a href="" id="task-duration-hours" class="webform-field-action-link"><?php echo GetMessage("TASKS_TASK_DURATION_HOURS") ?></a><?php endif ?><input type="hidden" name="DURATION_TYPE" id="task-duration-type" value="<?php echo ($arData["DURATION_TYPE"] != "days" ? "hours" : "days") ?>" /></td>
										</tr>
									</table>
								</div>
								<div style="display:none;" id="task-reminder-content">&nbsp;</div>
							</div>
							<div class="webform-corners-bottom">
								<div class="webform-left-corner"></div>
								<div class="webform-right-corner"></div>
							</div>
						</div>
					</div>
				</div>

				<div class="webform-row task-options-row">
					<div class="webform-field webform-field-checkbox-options task-options">

						<?if(is_array($arResult['COPY_PARAMS'])):?>
							<?if(intval($arResult['COPY_PARAMS']['ORIGIN_TASK'])):?>
								<input type="hidden" name="COPY_PARAMS[ORIGIN_TASK]" value="<?=intval($arResult['COPY_PARAMS']['ORIGIN_TASK'])?>" />
								<div class="webform-field-checkbox-option">
									<input type="checkbox" value="Y" id="copy-child-tasks" name="COPY_PARAMS[COPY_CHILD_TASKS]" class="webform-field-checkbox" <?php echo ($arResult['COPY_PARAMS']['COPY_CHILD_TASKS'] ? " checked" : "") ?> /><label for="copy-child-tasks"><?php echo GetMessage("TASKS_TASK_COPY_CHILD_TASKS") ?></label>
								</div>
							<?endif?>
							<?if(intval($arResult['COPY_PARAMS']['ORIGIN_TEMPLATE'])):?>
								<input type="hidden" name="COPY_PARAMS[ORIGIN_TEMPLATE]" value="<?=intval($arResult['COPY_PARAMS']['ORIGIN_TEMPLATE'])?>" />
								<div class="webform-field-checkbox-option" style="display: none">
									<input type="checkbox" value="Y" id="copy-child-templates" name="COPY_PARAMS[COPY_CHILD_TEMPLATES]" class="webform-field-checkbox" <?php echo ($arResult['COPY_PARAMS']['COPY_CHILD_TEMPLATES'] ? " checked" : "") ?> /><label for="copy-child-templates"><?php echo GetMessage("TASKS_TASK_COPY_CHILD_TEMPLATES") ?></label>
								</div>
							<?endif?>
						<?endif?>

						<div class="webform-field-checkbox-option"><input type="checkbox" value="Y" id="allow-change-deadline" name="ALLOW_CHANGE_DEADLINE" class="webform-field-checkbox"<?php echo ($arData["ALLOW_CHANGE_DEADLINE"] != "N" ? " checked" : "") ?> /><label for="allow-change-deadline"><?php echo GetMessage("TASKS_TASK_ALLOW_CHANGE_DEADLINE") ?></label></div>
						<?php
							$bCanTaskControl = false;
							if (!is_object($USER) || $arData["RESPONSIBLE_ID"] != $USER->GetID())
								$bCanTaskControl = true;
						?>
						<div class="webform-field-checkbox-option<?php if (!$bCanTaskControl) echo ' webform-field-checkbox-option-disabled'; ?>"
							><input type="checkbox" value="Y" id="task-control" name="TASK_CONTROL" 
								<?php if (!$bCanTaskControl) echo ' disabled="disabled"'; ?>
								class="webform-field-checkbox"<?php echo (($bCanTaskControl && ($arData["TASK_CONTROL"] == "Y")) ? " checked" : ""); ?> 
							/><label for="task-control"><?php echo GetMessage("TASKS_TASK_CONTROL") 
						?></label></div>
						<div class="webform-field-checkbox-option<?php if (!$bSubordinate): ?> webform-field-checkbox-option-disabled<?php endif ?>"><input type="checkbox" value="Y" id="add-in-report" name="ADD_IN_REPORT" class="webform-field-checkbox"<?php echo ($bSubordinate && $arData["ADD_IN_REPORT"] != "N" ? " checked" : "") ?><?php if (!$bSubordinate): ?> disabled="disabled"<?php endif ?> /><label for="add-in-report"><?php echo GetMessage("TASKS_TASK_ADD_IN_REPORT") ?></label></div>
						<div class="webform-field-checkbox-option"><input type="checkbox" value="Y" id="match-work-time" name="MATCH_WORK_TIME" class="webform-field-checkbox"<?php echo ($arData["MATCH_WORK_TIME"] == "Y" ? " checked" : "") ?> /><label for="match-work-time"><?=GetMessage("TASKS_TASK_MATCH_WORK_TIME") ?></label></div>
						<?php
						if (
							($arResult["ACTION"] == "create")
							&& isset($arResult['META:ENVIRONMENT'])
							&& $arResult['META:ENVIRONMENT']['TIMEMAN_AVAILABLE']
						)
						{
							?>
							<div class="webform-field-checkbox-option<?php if (!is_object($USER) || $arData["RESPONSIBLE_ID"] != $USER->GetID()): ?> webform-field-checkbox-option-disabled<?php endif ?>"><input type="checkbox" value="Y" id="add-to-timeman" name="ADD_TO_TIMEMAN" class="webform-field-checkbox"<?php echo (is_object($USER) && $arData["RESPONSIBLE_ID"] == $USER->GetID() && $arData["ADD_TO_TIMEMAN"] == "Y" ? " checked" : "") ?><?php if (!is_object($USER) || $arData["RESPONSIBLE_ID"] != $USER->GetID()): ?> disabled="disabled"<?php endif ?> /><label for="add-to-timeman"><?php echo GetMessage("TASKS_ADD_TASK_TO_TIMEMAN")?></label></div>
							<?php
						}
						?>
						<div class="webform-field-checkbox-option <?php echo ($arData['ALLOW_TIME_TRACKING'] === 'Y' ? ' task-edit-allowed-time-tracking' : '') ?>"
							><input type="checkbox" value="Y" id="allow-time-tracking" 
								onclick="
									if (this.checked)
										BX.addClass(this.parentNode, 'task-edit-allowed-time-tracking');
									else
										BX.removeClass(this.parentNode, 'task-edit-allowed-time-tracking');"
								onchange="
									if (this.checked)
										BX.addClass(this.parentNode, 'task-edit-allowed-time-tracking');
									else
										BX.removeClass(this.parentNode, 'task-edit-allowed-time-tracking');"
								name="ALLOW_TIME_TRACKING" 
								class="webform-field-checkbox"<?php echo ($arData["ALLOW_TIME_TRACKING"] === "Y" ? " checked" : "") ?> 
							/><label for="allow-time-tracking" class="task-edit-allowed-time-tracking-hide"><?php
								echo GetMessage("TASKS_TASK_ALLOW_TIME_TRACKING");
							?></label><label for="allow-time-tracking" class="task-edit-allowed-time-tracking-show"><?php
								echo GetMessage("TASKS_TASK_ALLOW_TIME_TRACKING_DETAILS");
							?></label>
								<span style="display:inline;"><span style="display:inline-block;"><span style="height:18px; display:inline-block; border:1px;"></span></span></span>
								<span class="task-edit-allowed-time-tracking-show">
								<span class="webform-field webform-field-textbox task-time-tracking-hours"><span class="webform-field-textbox-inner"><input type="text" id="task-time-tracking-hours" 
									value="<?php echo $arResult['ESTIMATE_HOURS']; ?>" 
									class="webform-field-textbox task-time-tracking-hours-input"
									name="ESTIMATE_HOURS"
									maxlength="4"
									></span></span><span><?php
										echo GetMessage("TASKS_TASK_TIME_TRACKING_HOURS");
									?></span><span class="webform-field webform-field-textbox task-time-tracking-minutes"><span class="webform-field-textbox-inner"><input type="text" id="task-time-tracking-minutes" 
									value="<?php echo $arResult['ESTIMATE_MINUTES']; ?>" 
									class="webform-field-textbox task-time-tracking-minutes-input"
									name="ESTIMATE_MINUTES"
									maxlength="2"
									></span></span><span><?php
										echo GetMessage("TASKS_TASK_TIME_TRACKING_MINUTES");
							?></span><span class="task-deadline-delete" style="" onclick="BX('task-time-tracking-hours').value = ''; BX('task-time-tracking-minutes').value = ''; "></span></span></div>
						
					</div>
				</div>
			</div>
		</div>

		<div class="webform-round-corners webform-additional-fields task-additional-fields">
			<div class="webform-content">

				<div class="webform-row task-description-row">
					<div class="webform-field-label"><label for="task-description"><?php echo GetMessage("TASKS_TASK_DESCRIPTION") ?></label></div>
					<div class="webform-field webform-field-textarea task-description-textarea">
						<div class="webform-field-textarea-inner">

							<?if(!CModule::IncludeModule("fileman")):?>
								<?=ShowError('Cannot include fileman module')?>
							<?else:?>

								<?
								// check if we use BBCODE
								$bbCode = ($arData['DESCRIPTION_IN_BBCODE'] === 'Y');

								if ($bbCode)
								{
									$rawDescription = $arData['META:DESCRIPTION_FOR_BBCODE'];
								}
								else
								{
									$rawDescription = $arData['DESCRIPTION'];
								}

								$Editor = new CHTMLEditor;
								$res = array_merge(
									array(
										'minBodyWidth' => 350,
										'minBodyHeight' => 200,
										'normalBodyWidth' => 555,
										'bAllowPhp' => false,
										'limitPhpAccess' => false,
										'showTaskbars' => false,
										'showNodeNavi' => false,
										'askBeforeUnloadPage' => true,
										'bbCode' => $bbCode,
										'siteId' => SITE_ID,
										'autoResize' => true,
										'autoResizeOffset' => 40,
										'saveOnBlur' => true,
										'setFocusAfterShow' => false,
										'controlsMap' => array(
											array('id' => 'Bold',  'compact' => true, 'sort' => 80),
											array('id' => 'Italic',  'compact' => true, 'sort' => 90),
											array('id' => 'Underline',  'compact' => true, 'sort' => 100),
											array('id' => 'Strikeout',  'compact' => true, 'sort' => 110),
											array('id' => 'RemoveFormat',  'compact' => true, 'sort' => 120),
											array('id' => 'Color',  'compact' => true, 'sort' => 130),
											array('id' => 'FontSelector',  'compact' => false, 'sort' => 135),
											array('id' => 'FontSize',  'compact' => false, 'sort' => 140),
											array('separator' => true, 'compact' => false, 'sort' => 145),
											array('id' => 'OrderedList',  'compact' => true, 'sort' => 150),
											array('id' => 'UnorderedList',  'compact' => true, 'sort' => 160),
											array('id' => 'AlignList', 'compact' => false, 'sort' => 190),
											array('separator' => true, 'compact' => false, 'sort' => 200),
											array('id' => 'InsertLink',  'compact' => true, 'sort' => 210, /*'wrap' => 'bx-b-link-'.$arParams["FORM_ID"]*/),
											array('id' => 'InsertImage',  'compact' => false, 'sort' => 220),
											array('id' => 'InsertVideo',  'compact' => true, 'sort' => 230, /*'wrap' => 'bx-b-video-'.$arParams["FORM_ID"]*/),
											array('id' => 'InsertTable',  'compact' => false, 'sort' => 250),
											array('id' => 'Code',  'compact' => true, 'sort' => 260),
											array('id' => 'Quote',  'compact' => true, 'sort' => 270, /*'wrap' => 'bx-b-quote-'.$arParams["FORM_ID"]*/),
											//array('id' => 'Smile',  'compact' => false, 'sort' => 280),
											array('separator' => true, 'compact' => false, 'sort' => 290),
											array('id' => 'Fullscreen',  'compact' => false, 'sort' => 310),
											array('id' => 'BbCode',  'compact' => true, 'sort' => 340),
											array('id' => 'More',  'compact' => true, 'sort' => 400),
										)
									),
									/*(is_array($arParams["LHE"]) ? $arParams["LHE"] : array()),*/
									array(
										'name' => 'DESCRIPTION', // inputName
										'id' => $taskDescriptionEditorId,
										'width' => '100%',
										'arSmiles' => array(),
										'content' => htmlspecialcharsBack($rawDescription),
										'iframeCss' => 'body{font-family: "Helvetica Neue",Helvetica,Arial,sans-serif; font-size: 13px;}'.
											'.bx-spoiler {border:1px solid #C0C0C0;background-color:#fff4ca;padding: 4px 4px 4px 24px;color:#373737;border-radius:2px;min-height:1em;margin: 0;}'
											/*.(is_array($arParams["LHE"]) && isset($arParams["LHE"]["iframeCss"]) ? $arParams["LHE"]["iframeCss"] : ""),*/
									)
								);

								$Editor->Show($res);
								?>

							<?endif?>
						</div>
					</div>
				</div>

				<div class="webform-row task-description-row">
					<?php
					$APPLICATION->IncludeComponent(
						"bitrix:tasks.task.detail.parts",
						".default",
						array(
							'MODE'                 => 'CREATE TASK FORM',
							'BLOCKS'               => array("checklist"),
							'IS_IFRAME'            => $arResult['IS_IFRAME'],
							'GROUP_ID'             => $arParams['GROUP_ID'],
							'PATH_TO_TASKS_TASK'   => $arParams['PATH_TO_TASKS_TASK'],
							'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
							'NAME_TEMPLATE'        => $arParams['NAME_TEMPLATE'],
							'ALLOWED_ACTIONS'      => array('ACTION_CHECKLIST_ADD_ITEMS' => true),
							'TASK_ID'              => $arResult['TASK']['ID'],
							'CHECKLIST_ITEMS'      => $arResult['DATA']['CHECKLIST_ITEMS']
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);
					?>
				</div>

				<?php if ($arData["FILES"]): ?>
					<div class="webform-row task-attachments-row">
						<div class="webform-field webform-field-attachments">
							<ol class="webform-field-upload-list" id="webform-field-upload-list">
								<?php
								if (is_array($arData["FILES"]))
									$strFilesIds = implode(",", $arData["FILES"]);
								else
									$strFilesIds = $arData["FILES"];

								$resFiles = CFile::GetList(array(), array("@ID" => $strFilesIds));
								?>
								<?php while ($file = $resFiles->GetNext()): ?>
									<li class="saved"><a 
										href="/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=<?php echo $file["ID"]?>" 
										target="_blank" 
										title="<?php echo htmlspecialcharsbx($file["ORIGINAL_NAME"]); ?>"
										class="upload-file-name"><?php
											if (strlen($file["ORIGINAL_NAME"]) >= 95)
												echo substr($file["ORIGINAL_NAME"], 0, 91) . '...';
											else
												echo $file["ORIGINAL_NAME"];
										?></a><i></i><a href="" class="delete-file"></a><input type="hidden" name="FILES[]" value="<?php echo $file["ID"] ?>" /></li>
								<?php endwhile ?>
							</ol>
							<div class="webform-field-upload">
								<span class="webform-button webform-button-upload"><span class="webform-button-left"></span><span class="webform-button-text"><?php echo GetMessage("TASKS_TASK_UPLOAD_FILES") ?></span><span class="webform-button-right"></span></span>
								<div id="task-upload-container">
									<input type="file" name="task-attachments[]" size="1" multiple="multiple" id="task-upload" />
								</div>
							</div>
						</div>
					</div>
				<?php endif ?>

				<?php
				foreach ($arResult["USER_FIELDS"] as $arUserField)
				{
					if (
						($arUserField['FIELD_NAME'] === 'UF_TASK_WEBDAV_FILES')
						&& ($arUserField['XML_ID'] === 'TASK_WEBDAV_FILES')
					)
					{
						$component_name = "bitrix:system.field.view";
						if ($arUserField['EDIT_IN_LIST'] === 'Y')
							$component_name = "bitrix:system.field.edit";

						$APPLICATION->IncludeComponent(
							$component_name,
							$arUserField["USER_TYPE"]["USER_TYPE_ID"],
							array(
								"bVarsFromForm" => $arResult["bVarsFromForm"],
								"arUserField" => $arUserField,
								"form_name" => "task-edit-form"
							), null, array("HIDE_ICONS" => "Y")
						);
						break;
					}
				}

				if (sizeof($arResult["GROUPS"]) > 0)
				{
					$name = $APPLICATION->IncludeComponent(
						"bitrix:socialnetwork.group.selector", ".default", array(
							"BIND_ELEMENT" => "task-sonet-group-selector",
							"ON_SELECT" => "onGroupSelect",
							"FEATURES_PERMS" => array("tasks", "create_tasks"),
							"SELECTED" => $arData["GROUP_ID"] ? $arData["GROUP_ID"] : 0
						), null, array("HIDE_ICONS" => "Y")
					);
					?>
					<div class="webform-row task-group-row">
						<a href="" id="task-sonet-group-selector" class="webform-field-action-link"><?php
							echo GetMessage("TASKS_TASK_GROUP");

							if ($arData["GROUP_ID"])
							{
								$arGroup = CSocNetGroup::GetByID($arData["GROUP_ID"]);
								echo ": ".$arGroup["NAME"];
							}
						?></a>
						<?php
						if ($arData["GROUP_ID"])
						{
							?><input type="hidden" class="tasks-notclass-GROUP_ID" name="GROUP_ID" value="<?php echo $arGroup["ID"]?>" 
								/><span class="task-group-delete" onclick="deleteGroup(<?php echo $arGroup["ID"]?>)"></span>
							<input type="hidden" class="tasks-notclass-GROUP_NAME" name="GROUP_NAME" value="<?php echo htmlspecialcharsbx($arGroup['NAME']); ?>">
							<?php
						}
						?>
					</div>
					<?php
				}
				?>
			</div>
		</div>

		<div class="webform-round-corners webform-additional-fields task-special-fields">
			<div class="webform-content">
				<?php
				$showExtra = false;
				if ($arData["REPLICATE"] == "Y" || $arData["AUDITORS"] || $arData["PARENT_ID"] || $arData["DEPENDS_ON"])
				{
					$showExtra = true;
				}
				else
				{
					foreach ($arResult["USER_FIELDS"] as $fieldName => $arUserField)
					{
						if ($arUserField["VALUE"] || ($arResult["bVarsFromForm"] && $_REQUEST[$fieldName]))
						{
							$showExtra = true;
							break;
						}
					}
				}
				?>

				<div class="webform-field-additional-link<?php echo $showExtra ? " selected" : "" ?>" id="webform-field-additional-link"><i></i><span><?php echo GetMessage("TASKS_TASK_EXTRA") ?></span></div>

				<div<?php echo (!$showExtra ? " style=\"display:none;\"" : "") ?> id="webform-additional-fields-content" class="webform-additional-fields-content">

					<div class="webform-row task-auditors-row">
						<div class="webform-row task-auditors-row">

							<div class="task-auditors-title"><?php echo GetMessage("TASKS_TASK_AUDITORS") ?>:</div>

							<div class="task-auditors-block">
								<div class="webform-round-corners webform-additional-select-block">
									<div class="webform-corners-top">
										<div class="webform-left-corner"></div>
										<div class="webform-right-corner"></div>
									</div>
									<div class="webform-content">
										<?php
										$name = $APPLICATION->IncludeComponent(
											"bitrix:intranet.user.selector.new", ".default", array(
												"MULTIPLE" => "Y",
												"NAME" => "AUDITORS",
												"VALUE" => $arData["AUDITORS"],
												"SHOW_BUTTON" => "N",
												"GET_FULL_INFO" => "Y",
												"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
												"GROUP_ID_FOR_SITE" => (isset($_GET["GROUP_ID"]) && intval($_GET["GROUP_ID"]) > 0 ? $_GET["GROUP_ID"] : (isset($arParams["GROUP_ID"]) && intval($arParams["GROUP_ID"]) > 0 ? $arParams["GROUP_ID"] : false)),
												'SHOW_EXTRANET_USERS' => 'FROM_MY_GROUPS',
												'DISPLAY_TAB_GROUP' => 'Y',
												'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
												'SHOW_LOGIN' => 'Y'
											), null, array("HIDE_ICONS" => "Y")
										);
										?>
									</div>
									<div class="webform-corners-bottom">
										<div class="webform-left-corner"></div>
										<div class="webform-right-corner"></div>
									</div>
								</div>
							</div>

						</div>
					</div>

					<div class="webform-row task-to-tasks-row">
						<div class="webform-field webform-field-round-corners">
							<div class="webform-round-corners">
								<div class="webform-corners-top"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>
								<div class="webform-content">

									<table cellspacing="0" class="task-to-tasks-layout">
										<tr>
											<td class="task-previous-tasks">
												<?php
												$name = $APPLICATION->IncludeComponent(
													"bitrix:tasks.task.selector", ".default", array(
														"MULTIPLE" => "Y",
														"NAME" => "PREV_TASKS",
														"VALUE" => $arData["DEPENDS_ON"],
														"POPUP" => "Y",
														"ON_CHANGE" => "onPrevTasksChange",
														"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
														"SITE_ID" => SITE_ID,
														"SELECT" => array('ID', 'TITLE', 'STATUS'),
													), null, array("HIDE_ICONS" => "Y")
												);
												?>
												<a href="" id="task-previous-tasks-link" class="webform-field-action-link"><?php echo GetMessage("TASKS_TASK_PREVIOUS_TASKS") ?></a>
												<div id="task-previous-tasks-list-outer">
													<ol class="task-to-tasks-list" id="task-previous-tasks-list">
														<?php if ($arData["DEPENDS_ON"]): ?>
															<?php
															$rsDependTasks = CTasks::GetList(array("TITLE" => "ASC"), array("ID" => $arData["DEPENDS_ON"]));
															while ($task = $rsDependTasks->GetNext()):
																?>
																<li class="task-to-tasks-item">
																	<a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => $task["ID"], "action" => "view")) ?>" target="_blank" class="task-to-tasks-item-name"><?php echo $task["TITLE"] ?></a>
																	<span class="task-to-tasks-item-delete" onclick="onPrevTasksUnselect(<?php echo $task["ID"] ?>, this)"></span>
																</li>
															<?php endwhile ?>
														<?php endif ?>
													</ol>
												</div>
												<input type="hidden" name="PREV_TASKS_IDS" value="<?php echo is_array($arData["DEPENDS_ON"]) ? implode(",", $arData["DEPENDS_ON"]) : "" ?>" />
											</td>
											<td class="task-supertask">
												<?php
												$name = $APPLICATION->IncludeComponent(
													"bitrix:tasks.task.selector", ".default", array(
														"MULTIPLE" => "N",
														"NAME" => "PARENT_TASK",
														"VALUE" => $arData["PARENT_ID"],
														"POPUP" => "Y",
														"ON_SELECT" => "onParentTaskSelect",
														"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
														"SITE_ID" => SITE_ID,
														"SELECT" => array('ID', 'TITLE', 'STATUS'),
													), null, array("HIDE_ICONS" => "Y")
												);
												?>
												<a href="" id="task-supertask-link" class="webform-field-action-link"><?php echo GetMessage("TASKS_TASK_PARENT_TASK") ?></a>
												<ol class="task-to-tasks-list task-to-tasks-list-single" id="task-parent-tasks-list">
													<?php if ($arData["PARENT_ID"]): ?>
														<?php
														$rsParentTask = CTasks::GetList(array("TITLE" => "ASC"), array("ID" => $arData["PARENT_ID"]));
														$parentTaskID = 0;
														if ($task = $rsParentTask->GetNext()):
															$parentTaskID = $task["ID"];
															?>
															<li class="task-to-tasks-item">
																<a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => $task["ID"], "action" => "view")) ?>" target="_blank" class="task-to-tasks-item-name"><?php echo $task["TITLE"] ?></a>
																<span class="task-to-tasks-item-delete" onclick="onParentTasksRemove(<?php echo $task["ID"] ?>, this)"></span>
															</li>
														<?php endif ?>
													<?php endif ?>
												</ol>
												<input type="hidden" name="PARENT_ID" value="<?php echo $parentTaskID ?>" />
											</td>
										</tr>
									</table>
								</div>
								<div class="webform-corners-bottom"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>
							</div>
						</div>
					</div>

					<?php if ($arResult["ACTION"] == "create"): ?>
						<div class="webform-row task-repeating-row">
							<table cellspacing="0" class="task-repeating-layout">
								<tr>
									<td class="task-repeating-label"><div class="webform-field-checkbox-option"><input type="checkbox" value="Y" id="task-repeating-checkbox" class="webform-field-checkbox" name="REPLICATE"<?php echo ($arData["REPLICATE"] == "Y" ? " checked" : "") ?> /><label for="task-repeating-checkbox"><?php echo GetMessage("TASKS_TASK_REPEAT") ?></label></div></td>
									<td class="task-repeating-settings">
										<div class="task-repeating<?php echo ($arData["REPLICATE"] == "Y" ? " selected" : "") ?>" id="task-repeating">
											<div class="task-repeating-timespan" id="task-repeating-timespan"><?php foreach ($arPeriods as $key => $period): ?><a href="" id="task-repeating-by-<?php echo $period["key"] ?>"<?php echo ($arData["REPLICATE_PERIOD"] == $period["key"] ? "class=\"selected\"" : "") ?>><i></i><span><?php echo $period["name"] ?></span><b></b></a><?php endforeach ?></div>
											<input type="hidden" name="REPLICATE_PERIOD" id="task-repeat-period" value="<?php echo ($arData["REPLICATE_PERIOD"] ? $arData["REPLICATE_PERIOD"] : "daily") ?>" />
											<div class="task-repeating-timespan-details" id="task-repeating-timespan-details">
												<div class="task-repeating-timespan-details-inner">

													<div class="task-repeating-by task-repeating-by-daily<?php echo ($arData["REPLICATE_PERIOD"] == "daily" ? " selected" : "") ?>">
														<div class="task-repeating-by-every-day-option"><label for="task-repeating-by-every-day"><?php echo GetMessage("TASKS_TASK_REPEAT_EVERY_1") ?></label><span class="webform-field webform-field-textbox task-repeating-by-every-day"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-by-every-day" class="webform-field-textbox" maxlength="3" name="REPLICATE_EVERY_DAY" value="<?php echo ($arData["REPLICATE_EVERY_DAY"] ? $arData["REPLICATE_EVERY_DAY"] : 1) ?>" /></span></span><label for="task-repeating-by-every-day"><?php echo GetMessage("TASKS_TASK_REPEAT_DAY") ?></label></div>
														<div class="webform-field-checkbox-option task-repeating-working-day"><input type="checkbox" value="Y" id="task-repeating-working-day" class="webform-field-checkbox" name="REPLICATE_WORKDAY_ONLY"<?php echo ($arData["REPLICATE_WORKDAY_ONLY"] ? " checked" : "") ?>  /><label for="task-repeating-working-day"><?php echo GetMessage("TASKS_TASK_REPEAT_WORK_ONLY") ?></label></div>
													</div>

													<div class="task-repeating-by task-repeating-by-weekly<?php echo ($arData["REPLICATE_PERIOD"] == "weekly" ? " selected" : "") ?>">
														<div class="task-repeating-by-every-week-option"><label for="task-repeating-by-every-week"><?php echo GetMessage("TASKS_TASK_REPEAT_EVERY_2") ?></label><span class="webform-field webform-field-textbox task-repeating-by-every-week"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-by-every-week" class="webform-field-textbox" maxlength="2" name="REPLICATE_EVERY_WEEK" value="<?php echo ($arData["REPLICATE_EVERY_WEEK"] ? $arData["REPLICATE_EVERY_WEEK"] : 1) ?>" /></span></span><label for="task-repeating-by-every-week"><?php echo GetMessage("TASKS_TASK_REPEAT_WEEK") ?></label></div>
														<div class="task-repeating-timespan task-repeating-timespan-days" id="task-repeating-timespan-days"><?php for ($key = 0; $key < 7; $key++): ?><a href="" id="task-repeat-day-<?php echo $key + 1 ?>"<?php echo (is_array($arData["REPLICATE_WEEK_DAYS"]) && in_array($key + 1, $arData["REPLICATE_WEEK_DAYS"]) ? " class=\"selected\"" : "") ?>><i></i><span><?php echo GetMessage("TASKS_REPEAT_DAY_SHORT_".$key) ?></span><b></b></a><?php endfor ?></div>
														<input type="hidden" name="REPLICATE_WEEK_DAYS" id="task-week-days" value="<?php echo is_array($arData["REPLICATE_WEEK_DAYS"]) ? implode(",", $arData["REPLICATE_WEEK_DAYS"]) : "1" ?>" />
													</div>

													<div class="task-repeating-by task-repeating-by-monthly<?php echo ($arData["REPLICATE_PERIOD"] == "monthly" ? " selected" : "") ?>">
														<table cellspacing="0" class="task-repeating-by-month-layout">
															<tr>
																<td class="task-repeating-by-month-number-radio"><input type="radio" name="REPLICATE_MONTHLY_TYPE" value="1"<?php echo ($arData["REPLICATE_MONTHLY_TYPE"] != 2 ? " checked" : "") ?> /></td>
																<td class="task-repeating-by-month-number"><span class="webform-field webform-field-textbox task-repeating-every-month-day-number"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-every-month-day-number" class="webform-field-textbox" value="<?php echo ($arData["REPLICATE_MONTHLY_DAY_NUM"] ? $arData["REPLICATE_MONTHLY_DAY_NUM"] : 1) ?>" name="REPLICATE_MONTHLY_DAY_NUM" /></span></span><label><?php echo GetMessage("TASKS_TASK_REPEAT_DATE") ?> <?php echo GetMessage("TASKS_TASK_REPEAT_EVERY_3") ?></label><span class="webform-field webform-field-textbox task-repeating-every-month-by-number"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-every-month-by-number" class="webform-field-textbox" value="<?php echo ($arData["REPLICATE_MONTHLY_MONTH_NUM_1"] ? $arData["REPLICATE_MONTHLY_MONTH_NUM_1"] : 1) ?>" name="REPLICATE_MONTHLY_MONTH_NUM_1" /></span></span><label><?php echo GetMessage("TASKS_TASK_REPEAT_MONTH") ?></label></td>
															</tr>
															<tr>
																<td class="task-repeating-by-month-day-radio"><input type="radio" name="REPLICATE_MONTHLY_TYPE" value="2"<?php echo ($arData["REPLICATE_MONTHLY_TYPE"] == 2 ? " checked" : "") ?> /></td>
																<td class="task-repeating-by-month-day">
																	<select name="REPLICATE_MONTHLY_WEEK_DAY_NUM">
																		<?php for ($key = 0; $key < 5; $key++): ?>
																			<option value="<?php echo $key ?>"<?php echo ($arData["REPLICATE_MONTHLY_WEEK_DAY_NUM"] == $key ? " selected" : "") ?>><?php echo GetMessage("TASKS_REPEAT_DAY_NUM_".$key) ?></option>
																		<?php endfor ?>
																	</select>
																	<select name="REPLICATE_MONTHLY_WEEK_DAY">
																		<?php for ($key = 0; $key < 7; $key++): ?>
																			<option value="<?php echo $key ?>"<?php echo ($arData["REPLICATE_MONTHLY_WEEK_DAY"] == $key ? " selected" : "") ?>><?php echo GetMessage("TASKS_REPEAT_DAY_".$key) ?></option>
																		<?php endfor ?>
																	</select>
																	<label for="task-repeating-every-month-by-day"><?php echo GetMessage("TASKS_TASK_REPEAT_EVERY_4") ?></label><span class="webform-field webform-field-textbox task-repeating-every-month-by-day"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-every-month-by-day" class="webform-field-textbox" value="<?php echo ($arData["REPLICATE_MONTHLY_MONTH_NUM_2"] ? $arData["REPLICATE_MONTHLY_MONTH_NUM_2"] : 1) ?>" name="REPLICATE_MONTHLY_MONTH_NUM_2" /></span></span><label for="task-repeating-every-month-by-day"><?php echo GetMessage("TASKS_TASK_REPEAT_MONTH") ?></label>
																</td>
															</tr>
														</table>
													</div>

													<div class="task-repeating-by task-repeating-by-yearly<?php echo ($arData["REPLICATE_PERIOD"] == "yearly" ? " selected" : "") ?>">
														<table cellspacing="0" class="task-repeating-by-year-layout">
															<tr>
																<td class="task-repeating-by-year-number-radio"><input type="radio" name="REPLICATE_YEARLY_TYPE" value="1"<?php echo ($arData["REPLICATE_YEARLY_TYPE"] != 2 ? " checked" : "") ?> /></td>
																<td class="task-repeating-by-year-number"><label for="task-repeating-every-year-day-number"><?php echo GetMessage("TASKS_TASK_REPEAT_EVERY_5") ?></label><span class="webform-field webform-field-textbox task-repeating-every-year-day-number"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-every-year-day-number" class="webform-field-textbox" value="<?php echo ($arData["REPLICATE_YEARLY_DAY_NUM"] ? $arData["REPLICATE_YEARLY_DAY_NUM"] : 1) ?>" name="REPLICATE_YEARLY_DAY_NUM" /></span></span><label><?php echo GetMessage("TASKS_TASK_REPEAT_DAY_OF_MONTH") ?></label>
																	<select name="REPLICATE_YEARLY_MONTH_1">
																		<?php for ($key = 0; $key < 12; $key++): ?>
																			<option value="<?php echo $key ?>"<?php echo ($arData["REPLICATE_YEARLY_MONTH_1"] == $key ? " selected" : "") ?>><?php echo GetMessage("TASKS_REPEAT_MONTH_".$key) ?></option>
																		<?php endfor ?>
																	</select>
																</td>
															</tr>
															<tr>
																<td class="task-repeating-by-year-day-radio"><input type="radio" name="REPLICATE_YEARLY_TYPE" value="2"<?php echo ($arData["REPLICATE_YEARLY_TYPE"] == 2 ? " checked" : "") ?> /></td>
																<td class="task-repeating-by-year-day">
																	<label><?php echo GetMessage("TASKS_TASK_REPEAT_AT") ?></label>
																	<select name="REPLICATE_YEARLY_WEEK_DAY_NUM">
																		<?php for ($key = 0; $key < 5; $key++): ?>
																			<option value="<?php echo $key ?>"<?php echo ($arData["REPLICATE_YEARLY_WEEK_DAY_NUM"] == $key ? " selected" : "") ?>><?php echo GetMessage("TASKS_REPEAT_DAY_NUM_".$key) ?></option>
																		<?php endfor ?>
																	</select>
																	<select name="REPLICATE_YEARLY_WEEK_DAY">
																		<?php for ($key = 0; $key < 7; $key++): ?>
																			<option value="<?php echo $key ?>"<?php echo ($arData["REPLICATE_YEARLY_WEEK_DAY"] == $key ? " selected" : "") ?>><?php echo GetMessage("TASKS_REPEAT_DAY_".$key) ?></option>
																		<?php endfor ?>
																	</select>
																	<label><?php echo GetMessage("TASKS_TASK_REPEAT_YEARLY_MONTH") ?></label>
																	<select name="REPLICATE_YEARLY_MONTH_2">
																		<?php for ($key = 0; $key < 12; $key++): ?>
																			<option value="<?php echo $key ?>"<?php echo ($arData["REPLICATE_YEARLY_MONTH_2"] == $key ? " selected" : "") ?>><?php echo GetMessage("TASKS_REPEAT_MONTH_".$key) ?></option>
																		<?php endfor ?>
																	</select>
																</td>
															</tr>
														</table>
													</div>

													<div class="task-repeating-interval"><label for="task-repeating-interval-start-date"><?php echo GetMessage("TASKS_TASK_REPEAT_START") ?></label><span class="webform-field webform-field-textbox webform-field-textbox-clearable<?php echo (!$arData["REPLICATE_START_DATE"] ? " webform-field-textbox-empty" : "") ?> task-repeating-interval-start-date"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-interval-start-date" class="webform-field-textbox" name="REPLICATE_START_DATE" value="<?php echo $arData["REPLICATE_START_DATE"] ?>" readonly="readonly" /><a class="webform-field-textbox-clear" href=""></a></span></span><label for="task-repeating-interval-end-date"><?php echo GetMessage("TASKS_TASK_REPEAT_END") ?></label><span class="webform-field webform-field-textbox webform-field-textbox-clearable<?php echo (!$arData["REPLICATE_END_DATE"] ? " webform-field-textbox-empty" : "") ?> task-repeating-interval-end-date"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-interval-end-date" class="webform-field-textbox" name="REPLICATE_END_DATE" value="<?php echo $arData["REPLICATE_END_DATE"] ?>" readonly="readonly" /><a class="webform-field-textbox-clear" href=""></a></span></span></div>

													<div class="task-repeating-time">
														<?=GetMessage("TASKS_TASK_REPEAT_TIME")?>
														<?$APPLICATION->IncludeComponent('bitrix:main.clock', '', array(
															'INPUT_NAME' => 'REPLICATE_TIME',
															'INPUT_ID' => 'id',
															'INIT_TIME' => (string) $arData["REPLICATE_TIME"] != '' ? $arData["REPLICATE_TIME"] : '05:00'
														))?>
													</div>

													<div class="task-simple-note">
														<?=GetMessage("TASKS_TASK_REPLICATION_NOTE")?>
													</div>
												</div>
											</div>
										</div>
									</td>
								</tr>
							</table>

						</div>
					<?php endif ?>

					<?php if (sizeof($arResult["USER_FIELDS"])): ?>
						<div class="webform-row task-additional-properties-row">
							<table cellspacing="0" class="task-properties-layout">
								<?php foreach ($arResult["USER_FIELDS"] as $arUserField): 
									if (
										($arUserField['FIELD_NAME'] === 'UF_TASK_WEBDAV_FILES')
										&& ($arUserField['XML_ID'] === 'TASK_WEBDAV_FILES')
									)
									{
										// Don't show this field in "extra-section", because we showed it below
										continue;
									}
									?>
									<tr>
										<td class="task-property-name"><?php echo htmlspecialcharsbx($arUserField["EDIT_FORM_LABEL"]) ?>:</td>
										<td class="task-property-value"><?php
											$component_name = "bitrix:system.field.view";
											if ($arUserField['EDIT_IN_LIST'] === 'Y')
												$component_name = "bitrix:system.field.edit";

											$APPLICATION->IncludeComponent(
												$component_name,
												$arUserField["USER_TYPE"]["USER_TYPE_ID"],
												array(
													"bVarsFromForm" => false,
													"arUserField" => $arUserField,
													"form_name" => "task-edit-form",
													'SHOW_FILE_PATH'    => false,
													'FILE_URL_TEMPLATE' => '/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=#file_id#'
												), null, array("HIDE_ICONS" => "Y")
											);
									?></td>
									</tr>
								<?php endforeach ?>
							</table>
						</div>
					<?php endif ?>
				</div>
			</div>
			<div class="webform-corners-bottom">
				<div class="webform-left-corner"></div>
				<div class="webform-right-corner"></div>
			</div>
		</div>

		<div class="webform-round-corners webform-warning-block" id="task-edit-warnings-area" 
			style="display: none; margin:10px 0;">
			<div class="webform-corners-top"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>
			<div class="webform-content">
				<div id="task-edit-warnings-area-message"></div>
			</div>
			<div class="webform-corners-bottom"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>
		</div>

		<div class="webform-buttons task-buttons">
			<a href="javascript: void(0);" class="webform-button webform-button-create" id="task-submit-button"><span class="webform-button-left"></span><span class="webform-button-text"><?php if ($arResult["ACTION"] == "create"): ?><?php echo GetMessage("TASKS_TASK_ADD_TASK") ?><?php else: ?><?php echo GetMessage("TASKS_TASK_SAVE_TASK") ?><?php endif ?></span><span class="webform-button-right"></span></a>
			<?php if ($arResult["ACTION"] == "create"):?>
				<a href="javascript: void(0);" class="webform-button-link task-button-create-link" id="task-submit-and-create-new-when-back-to-form-button"><?php echo GetMessage("TASKS_TASK_ADD_TASK_AND_NEW")?></a>
			<?php endif?>
			<a href="<?php echo isset($arResult["RETURN_URL"]) && strlen($arResult["RETURN_URL"]) ? $arResult["RETURN_URL"] : $arParams["PATH_TO_TASKS"] ?>" class="webform-button-link webform-button-link-cancel" onclick="onCancelClick(event, '<?php echo $arResult["ACTION"]?>');"><?php echo GetMessage("TASKS_TASK_CANCEL") ?></a>
		</div>
	</div>
	<input type="hidden" id="tasks-meta-columnsIds" name="TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE" value="" />
	<input type="hidden" name="apply" value="save" />
</form>

<?php $this->SetViewTarget("pagetitle", 100); ?>
<div class="task-title-buttons task-detail-title-buttons"><a class="task-title-button task-title-button-back" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array()); ?>"><i class="task-title-button-back-icon"></i><span class="task-title-button-back-text"><?php echo GetMessage("TASKS_ADD_BACK_TO_TASKS_LIST") ?></span></a></div>
<?php $this->EndViewTarget(); ?>