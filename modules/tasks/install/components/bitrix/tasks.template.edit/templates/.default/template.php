<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

CUtil::InitJSCore(array('popup', 'tooltip', 'tasks_util_widget'));

//$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.task.edit/templates/.default/script.js");

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

$bSubordinate = CTasks::IsSubordinate($arData["RESPONSIBLE_ID"], $arData["CREATED_BY"]);
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
		TASKS_PATH_TO_TEMPLATE : '<?php echo CUtil::JSEscape($arParams["PATH_TO_TEMPLATES_TEMPLATE"]); ?>',
		TASKS_DELETE_CONFIRM : '<?php echo CUtil::JSEscape(GetMessage("TASKS_DELETE_CONFIRM")); ?>',
		TASKS_TASK_GROUP : '<?php echo CUtil::JSEscape(GetMessage("TASKS_TASK_GROUP")); ?>',

		TASKS_TEMPLATE_RESPONSIBLE_ID_UNDEFINED: '<?=CUtil::JSEscape(GetMessage("TASKS_TEMPLATE_RESPONSIBLE_ID_UNDEFINED"))?>',
		REPLICATE_TIME: '<?=CUtil::JSEscape($arData['REPLICATE_TIME'])?>'
	});

	var loggedInUser = <?php echo (int) $USER->GetID(); ?>;
	var loggedInUserName = "<?=CUtil::JSEscape($arResult['CREATED_BY_NAME_FORMATTED'])?>";

	var previousUser = currentUser = <?php echo $USER->GetID()?>;
	var previousUserName = currentUserName = "<?php echo CUtil::JSEscape(CUser::FormatName($arParams['NAME_TEMPLATE'], array("NAME" => $USER->GetFirstName(), "LAST_NAME" => $USER->GetLastName(), "LOGIN" => $USER->GetLogin(), "SECOND_NAME" => $USER->GetSecondName()
		), true, false));?>";

	var templateId = <?=intval($arParams['TEMPLATE_ID'])?>;

	var isAdmin = <?=($USER->isAdmin() ? "true" : "false")?>;
	var isPortalB24Admin = <?php
		if (CTasksTools::IsPortalB24Admin())
			echo  'true';
		else
			echo 'false';
		?>;

	BX.ready(function() {
		taskManagerForm.init({editorId: '<?=$taskDescriptionEditorId?>'});
	});
</script>
<form action="<?php echo POST_FORM_ACTION_URI?>" method="post" name="task-edit-form" id="task-edit-form" class="<?=implode(' ', $arResult['CSS_MODES'])?>" >
	<?php echo bitrix_sessid_post()?>
	<input type="hidden" name="DESCRIPTION_IN_BBCODE" value="<?=($arData['DESCRIPTION_IN_BBCODE'] == 'Y' ? 'Y' : 'N')?>">
	<div class="webform task-webform">
		<?php if (isset($arResult["ERRORS"]) && sizeof($arResult["ERRORS"]) > 0):?>
		<div class="webform-round-corners webform-error-block">
			<div class="webform-corners-top"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>
			<div class="webform-content">
				<ul class="webform-error-list">
					<?php foreach($arResult["ERRORS"] as $error):?>
						<li><?php echo $error["text"]?></li>
					<?php endforeach?>
				</ul>
			</div>
			<div class="webform-corners-bottom"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>
		</div>
		<?php endif?>

		<div class="webform-round-corners webform-main-fields task-main-fields">
			<div class="webform-corners-top">
				<div class="webform-left-corner"></div>
				<div class="webform-right-corner"></div>
			</div>
			<div class="webform-content">

				<div class="webform-row task-title-row">
					<div class="webform-field-label"><label for="task-title"><?php echo GetMessage("TASKS_TASK")?></label></div>
					<div class="webform-field webform-field-textbox-double task-title">
						<div class="webform-field-textbox-inner"><input type="text" name="TITLE" id="task-title" style="height:23px;" class="webform-field-textbox<?php echo $arData["TITLE"] ? "" : " inactive"?>" value="<?php echo $arData["TITLE"] ? $arData["TITLE"] : ''?>" placeholder="<?=GetMessage("TASKS_DEFAULT_TITLE")?>" /></div>
					</div>
				</div>

				<div class="webform-row task-responsible-employee-row">
					<table cellspacing="0" class="task-responsible-employee-layout">
						<tr>
							<td class="task-responsible-employee-layout-left">
								<div class="webform-field-label"><label for="task-responsible-employee" id="task-responsible-employee-label"><?php if ($arData["MULTITASK"] == "Y"):?><?php echo GetMessage("TASKS_RESPONSIBLES")?><?php else:?><?php echo GetMessage("TASKS_RESPONSIBLE")?><?php endif?></label></div>

								<div class="webform-field webform-field-combobox<?if($arResult['RESPONSIBLE_DISABLED']):?> webform-field-combobox-disabled<?endif?> task-responsible-employee" id="task-responsible-employee-block"<?php if ($arData["MULTITASK"] == "Y"):?> style="display:none;"<?php endif?>>
									<div class="webform-field-combobox-inner">
										<input 
											type="text"
											id="task-responsible-employee"
											<?=($arResult['RESPONSIBLE_DISABLED'] ? 'disabled="disabled"' : '')?>
											class="webform-field-combobox"
											value="<?=($arResult['USER_CREATE_TEMPLATE'] ? GetMessage('TASKS_TEMPLATE_RESPONSIBLE_ID_UNDEFINED') : $arResult['RESPONSIBLE_NAME_FORMATTED'])?>"

											data-permanently-disabled="<?=($arData["CREATED_BY"] != $USER->GetID() ? '1' : '')?>"
											data-previous-value="<?=($arResult['USER_CREATE_TEMPLATE'] ? $arResult['CREATED_BY_NAME_FORMATTED'] : $arResult['RESPONSIBLE_NAME_FORMATTED'])?>"
										/><a href="" class="webform-field-combobox-arrow">&nbsp;</a>
										
										<input 
											type="hidden"
											name="RESPONSIBLE_ID"
											value="<?=intval($arData["RESPONSIBLE_ID"])?>"
											data-previous-value="<?=($arResult['USER_CREATE_TEMPLATE'] ? $arData["CREATED_BY"] : $arData["RESPONSIBLE_ID"])?>"
										/>
									</div>
								</div>
								<?php
									$name = $APPLICATION->IncludeComponent(
										"bitrix:intranet.user.selector.new",
										".default",
										array(
											"MULTIPLE" => "N",
											"NAME" => "RESPONSIBLE",
											"INPUT_NAME" => "task-responsible-employee",
											"VALUE" => $arResult['USER_CREATE_TEMPLATE'] ? $arData['CREATED_BY'] : $arData["RESPONSIBLE_ID"],
											"POPUP" => "Y",
											"ON_SELECT" => "onResponsibleSelect",
											"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
											"SITE_ID" => SITE_ID,
											"GROUP_ID_FOR_SITE" => (intval($_GET["GROUP_ID"]) > 0 ? $_GET["GROUP_ID"] : (intval($arParams["GROUP_ID"]) > 0 ? $arParams["GROUP_ID"] : false)),
											'SHOW_EXTRANET_USERS' => 'FROM_MY_GROUPS',
											'DISPLAY_TAB_GROUP' => 'Y',
											'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
											'SHOW_LOGIN' => 'Y'
										),
										null,
										array("HIDE_ICONS" => "Y")
									);
								?>

								<div class="webform-field task-responsible-employees" id="task-responsible-employees-block"<?php if ($arData["MULTITASK"] != "Y"):?> style="display:none;"<?php endif?>>
									<div class="task-responsible-employees-list" id="task-responsible-employees-list">
										<?php if (sizeof($arData["RESPONSIBLES"]) > 0):?>
											<?php
												$rsResponsibles = CUser::GetList($by = 'last_name', $order = 'asc', array("ID" => implode("|", $arData["RESPONSIBLES"])), array('SELECT' => array('UF_*')));
												while($user = $rsResponsibles->GetNext()):
											?>
											<div class="task-responsible-employee-item"><a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array("user_id" => $user["ID"]))?>" class="task-responsible-employee-link" title="<?php echo CUser::FormatName($arParams["NAME_TEMPLATE"], $user, true, false)?>" target="_blank"><?php echo CUser::FormatName($arParams["NAME_TEMPLATE"], $user, true, false)?></a></div>
											<?php endwhile?>
										<?php endif?>
									</div>
									<div class="task-responsible-employees-change-link"><a href="" class="webform-field-action-link" id="task-responsibles-link"><?php echo GetMessage("TASKS_TASK_CHANGE_RESPONSIBLES")?></a></div>
									<input type="hidden" name="RESPONSIBLES_IDS" value="<?php echo is_array($arData["RESPONSIBLES"]) ?  implode(",", array_map('intval', $arData["RESPONSIBLES"])) : ""?>" />
								</div>
								<?php
									$name = $APPLICATION->IncludeComponent(
										"bitrix:intranet.user.selector.new",
										".default",
										array(
											"MULTIPLE" => "Y",
											"NAME" => "RESPONSIBLES",
											"VALUE" => $arData["RESPONSIBLES"],
											"POPUP" => "Y",
											"ON_CHANGE" => "onResponsiblesChange",
											"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
											"SITE_ID" => SITE_ID,
											"GROUP_ID_FOR_SITE" => (intval($_GET["GROUP_ID"]) > 0 ? $_GET["GROUP_ID"] : (intval($arParams["GROUP_ID"]) > 0 ? $arParams["GROUP_ID"] : false)),
											'SHOW_EXTRANET_USERS' => 'FROM_MY_GROUPS',
											'DISPLAY_TAB_GROUP' => 'Y',
											'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
											'SHOW_LOGIN' => 'Y'
										),
										null,
										array("HIDE_ICONS" => "Y")
									);
								?>

								<div class="webform-field task-director" id="task-director-employees-block"<?php if ($arData["MULTITASK"] == "Y"):?> style="display:none;"<?php endif?>>
									<div class="task-director-item"><a href="javascript:void(0)" class="webform-field-action-link" id="task-author-employee"><?php echo GetMessage("TASKS_DIRECTOR")?>:</a><span class="task-director-link"><?php echo CUser::FormatName($arParams["NAME_TEMPLATE"], array("NAME" => $arData["CREATED_BY_NAME"], "LAST_NAME" => $arData["CREATED_BY_LAST_NAME"], "LOGIN" => $arData["CREATED_BY_LOGIN"], "SECOND_NAME" => $arData["CREATED_BY_SECOND_NAME"]))?></span></div>
									<input type="hidden" name="CREATED_BY" value="<?=intval($arData["CREATED_BY"])?>" />
								</div>
								<?php
									$name = $APPLICATION->IncludeComponent(
										"bitrix:intranet.user.selector.new",
										".default",
										array(
											"MULTIPLE" => "N",
											"NAME" => "AUTHOR",
											"VALUE" => $arData["CREATED_BY"],
											"POPUP" => "Y",
											"ON_SELECT" => "onAuthorSelect",
											"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
											"SITE_ID" => SITE_ID,
											"GROUP_ID_FOR_SITE" => (intval($_GET["GROUP_ID"]) > 0 ? $_GET["GROUP_ID"] : (intval($arParams["GROUP_ID"]) > 0 ? $arParams["GROUP_ID"] : false)),
											'SHOW_EXTRANET_USERS' => 'FROM_MY_GROUPS',
											'DISPLAY_TAB_GROUP' => 'Y',
											'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
											'SHOW_LOGIN' => 'Y'
										),
										null,
										array("HIDE_ICONS" => "Y")
									);
								?>

								<div class="webform-field task-assistants" id="task-assistants-block"<?php if ($arData["MULTITASK"] == "Y"):?> style="display:none;"<?php endif?>>
									<div class="task-assistants-label"><a href="" class="webform-field-action-link" id="task-assistants-link"><?php echo GetMessage("TASKS_TASK_ACCOMPLICES")?><?php if (sizeof($arData["ACCOMPLICES"]) > 0):?>:<?php endif?></a></div>
									<div class="task-assistants-list" id="task-assistants-list">
										<?php if (sizeof($arData["ACCOMPLICES"]) > 0):?>
											<?php
												$rsAccomplices = CUser::GetList($by = 'last_name', $order = 'asc', array("ID" => implode("|", $arData["ACCOMPLICES"])), array('SELECT' => array('UF_*')));
												while($user = $rsAccomplices->GetNext()):
											?>
											<div class="task-assistant-item"><span class="task-assistant-link" title="<?php echo CUser::FormatName($arParams["NAME_TEMPLATE"], $user, true, false)?>"><?php echo CUser::FormatName($arParams["NAME_TEMPLATE"], $user, true, false)?></span></div>
											<?php endwhile?>
										<?php endif?>
									</div>
									<input type="hidden" name="ACCOMPLICES_IDS" value="<?php echo is_array($arData["ACCOMPLICES"]) ?  implode(",", $arData["ACCOMPLICES"]) : ""?>" />
								</div>
								<?php
									$name = $APPLICATION->IncludeComponent(
										"bitrix:intranet.user.selector.new",
										".default",
										array(
											"MULTIPLE" => "Y",
											"NAME" => "ACCOMPLICES",
											"VALUE" => $arData["ACCOMPLICES"],
											"POPUP" => "Y",
											"ON_CHANGE" => "onAccomplicesChange",
											"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
											"SITE_ID" => SITE_ID,
											"GROUP_ID_FOR_SITE" => (intval($_GET["GROUP_ID"]) > 0 ? $_GET["GROUP_ID"] : (intval($arParams["GROUP_ID"]) > 0 ? $arParams["GROUP_ID"] : false)),
											'SHOW_EXTRANET_USERS' => 'FROM_MY_GROUPS',
											'DISPLAY_TAB_GROUP' => 'Y',
											'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
											'SHOW_LOGIN' => 'Y'
										),
										null,
										array("HIDE_ICONS" => "Y")
									);
								?>

							</td>
							<td class="task-responsible-employee-layout-right">

								<div class="webform-field task-priority" id="task-priority">
									<label><?php echo GetMessage("TASKS_TASK_PRIORITY")?>:</label><?php foreach($arPriorities as $key=>$priority):?><a href="" id="task-priority-<?php echo $key?>" class="task-priority-<?php echo $priority["class"]?><?php echo ($arData["PRIORITY"] == $key ? " selected" : "")?>"><i></i><span><?php echo $priority["name"]?></span><b></b></a><?php endforeach?>
									<input type="hidden" name="PRIORITY" id="task-priority-field" value="<?php echo ($arData["PRIORITY"] ? $arData["PRIORITY"] : 0)?>" />
								</div>

								<div class="webform-field task-duplicate">
									<div class="webform-field-checkbox-option<?php if ($arData["CREATED_BY"] != $USER->GetID()):?> webform-field-checkbox-option-disabled<?php endif?>"><input type="checkbox" value="Y" id="duplicate-task"<?php if ($arData["CREATED_BY"] != $USER->GetID()):?> disabled="disabled"<?php endif?> name="MULTITASK" class="webform-field-checkbox" onclick="CopyTask(this);"<?php if ($arData["MULTITASK"] == "Y"):?> checked<?php endif?> /><label for="duplicate-task"><?php echo GetMessage("TASKS_TASK_COPY")?></label></div>
								</div>

								<div class="webform-field task-tags">
									<label><?php echo GetMessage("TASKS_TASK_TAGS")?>:</label><?php $name = $APPLICATION->IncludeComponent(
										"bitrix:tasks.tags.selector",
										".default",
										array(
											"NAME" => "TAGS",
											"VALUE" => $arData["TAGS"]
										),
										null,
										array("HIDE_ICONS" => "Y")
									);?>
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
								<?php
									$deadlineField = '<span class="webform-field webform-field-textbox task-duration"><span class="webform-field-textbox-inner"><input type="text" id="task-duration" maxlength="3" class="webform-field-textbox" name="DEADLINE_AFTER" value="'.($arData["DEADLINE_AFTER"] ? $arData["DEADLINE_AFTER"] : "").'" /></span></span>';
								?>
								<div class="webform-field task-deadline-settings"><?php echo str_replace("#FIELD#", $deadlineField, GetMessage("TASKS_TEMPLATE_DEADLINE"))?></div>
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
						<div class="webform-field-checkbox-option"><input type="checkbox" value="Y" id="allow-change-deadline" name="ALLOW_CHANGE_DEADLINE" class="webform-field-checkbox"<?php echo ($arData["ALLOW_CHANGE_DEADLINE"] != "N" ? " checked" : "")?> /><label for="allow-change-deadline"><?php echo GetMessage("TASKS_TASK_ALLOW_CHANGE_DEADLINE")?></label></div>
						<?php
							$bCanTaskControl = true;
						?>
						<div class="webform-field-checkbox-option<?php if (!$bCanTaskControl) echo ' webform-field-checkbox-option-disabled'; ?>"
							><input type="checkbox" value="Y" id="task-control"
								<?php if (!$bCanTaskControl) echo ' disabled="disabled"'; ?>
								name="TASK_CONTROL" class="webform-field-checkbox"<?php echo ($arData["TASK_CONTROL"] == "Y" ? " checked" : "")?> /><label for="task-control"><?php echo GetMessage("TASKS_TASK_CONTROL")?></label></div>
						<div style="display: none;" class="webform-field-checkbox-option<?php if (!$bSubordinate):?> webform-field-checkbox-option-disabled<?php endif?>"><input type="checkbox" value="Y" id="add-in-report" name="ADD_IN_REPORT" class="webform-field-checkbox"<?php echo ($bSubordinate && $arData["ADD_IN_REPORT"] != "N" ? " checked" : "")?><?php if (!$bSubordinate):?> disabled="disabled"<?php endif?> /><label for="add-in-report"><?php echo GetMessage("TASKS_TASK_ADD_IN_REPORT")?></label></div>

						<?//this block is visible when: either you a bx24-admin, or the template is already a system template?>
						<?if(($arResult['BX24_MODE'] && $arResult['USER_IS_ADMIN']) || (intval($arParams['TEMPLATE_ID']) && $arResult['DATA']['TPARAM_TYPE'] == CTaskTemplates::TYPE_FOR_NEW_USER)):?>
							<div class="webform-field-checkbox-option<?=(intval($arParams["TEMPLATE_ID"]) ? ' webform-field-checkbox-option-disabled' : '')?>">
								<input 
									type="checkbox" 
									id="task-user-create-checkbox" 
									class="webform-field-checkbox"<?=($arResult['USER_CREATE_TEMPLATE'] ? " checked" : "")?>
									name="TPARAM_TYPE"
									value="<?=CTaskTemplates::TYPE_FOR_NEW_USER?>"
									<?=(intval($arParams["TEMPLATE_ID"]) ? 'disabled="disabled"' : '')?>
								/><label for="task-user-create-checkbox"><?=GetMessage("TASKS_TEMPLATE_FOR_NEW_USER")?></label>

								<?if(intval($arParams["TEMPLATE_ID"])):?>
									<input type="hidden" name="TPARAM_TYPE" value="<?=intval($arResult['DATA']['TPARAM_TYPE'])?>" /> <?//instead of disabled checkbox?>
								<?endif?>
							</div>

						<?endif?>

					</div>
				</div>

			</div>
		</div>

		<div class="webform-round-corners webform-additional-fields task-additional-fields">
			<div class="webform-content">

				<div class="webform-row task-description-row">
					<div class="webform-field-label"><label for="task-description"><?php echo GetMessage("TASKS_TASK_DESCRIPTION")?></label></div>
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
							'TASK_ID'              => $arResult['DATA']['ID'],
							'CHECKLIST_ITEMS'      => $arResult['DATA']['CHECKLIST_ITEMS']
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);
					?>
				</div>

				<?$diskUf = \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode();?>

				<?if(\Bitrix\Tasks\Integration\Disk::isInstalled()):?>

					<?foreach($arResult["USER_FIELDS"] as $field):?>
						<?if($field['FIELD_NAME'] == \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode()):?>
							<?
							$APPLICATION->IncludeComponent(
								'bitrix:system.field.edit',
								$field["USER_TYPE"]["USER_TYPE_ID"],
								array(
									"bVarsFromForm" => false,
									"arUserField" => $field,
									"form_name" => "task-edit-form",
								), null, array("HIDE_ICONS" => "Y")
							);
							?>
						<?endif?>
					<?endforeach;?>

				<?else:?>

					<div class="webform-row task-attachments-row">
						<div class="webform-field webform-field-attachments">
							<ol class="webform-field-upload-list" id="webform-field-upload-list">
								<?php if ($arData["FILES"]):?>
									<?php
										$resFiles = CFile::GetList(array(), array("@ID" => implode(",", $arData["FILES"])));
									?>
									<?php while($file = $resFiles->GetNext()):?>
										<li class="saved"><a href="/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=<?php echo $file["ID"]?>&tid=<?php echo $arParams["TEMPLATE_ID"]?>" target="_blank" class="upload-file-name"><?php echo $file["FILE_NAME"]?></a><i></i><a href="" class="delete-file"></a><input type="hidden" name="FILES[]" value="<?php echo $file["ID"]?>" /></li>
									<?php endwhile?>
								<?php endif?>
							</ol>
							<div class="webform-field-upload">
								<span class="webform-button webform-button-upload"><span class="webform-button-left"></span><span class="webform-button-text"><?php echo GetMessage("TASKS_TASK_UPLOAD_FILES")?></span><span class="webform-button-right"></span></span>
								<input type="file" name="task-attachments[]" size="1" multiple="multiple" id="task-upload" />
							</div>
						</div>
					</div>

				<?endif?>

				<?php if (sizeof($arResult["GROUPS"]) > 0): ?>
					<?php
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
						<a href="" id="task-sonet-group-selector" class="webform-field-action-link"><?php echo GetMessage("TASKS_TASK_GROUP")?><?php
							if ($arData["GROUP_ID"])
							{
								$arGroup = CSocNetGroup::GetByID($arData["GROUP_ID"]);
								echo ": ".$arGroup["NAME"];
							}
						?></a>
						<?php if ($arData["GROUP_ID"]):?><input type="hidden" name="GROUP_ID" value="<?php echo $arGroup["ID"]?>" /><span class="task-group-delete" onclick="deleteGroup(<?php echo $arGroup["ID"]?>)"></span><?php endif?>
					</div>
				<?php endif ?>
			</div>
		</div>

		<div class="webform-round-corners webform-additional-fields task-special-fields">
			<div class="webform-content">

				<div class="webform-field-additional-link<?php echo ($arData["REPLICATE"] == "Y" || $arData["AUDITORS"] || $arData["PARENT_ID"] || $arData["DEPENDS_ON"] ? " selected" : "")?>" id="webform-field-additional-link"><i></i><span><?php echo GetMessage("TASKS_TASK_EXTRA")?></span></div>

				<div<?php echo ($arData["REPLICATE"] != "Y" && !$arData["AUDITORS"] && !$arData["PARENT_ID"] && !$arData["DEPENDS_ON"] ? " style=\"display:none;\"" : "")?> id="webform-additional-fields-content" class="webform-additional-fields-content">

					<div class="webform-row task-auditors-row">
						<div class="webform-row task-auditors-row">

							<div class="task-auditors-title"><?php echo GetMessage("TASKS_TASK_AUDITORS")?>:</div>

							<div class="task-auditors-block">
								<div class="webform-round-corners webform-additional-select-block">
									<div class="webform-corners-top">
										<div class="webform-left-corner"></div>
										<div class="webform-right-corner"></div>
									</div>
									<div class="webform-content">
										<?php
											$name = $APPLICATION->IncludeComponent(
												"bitrix:intranet.user.selector.new",
												".default",
												array(
													"MULTIPLE" => "Y",
													"NAME" => "AUDITORS",
													"VALUE" => $arData["AUDITORS"],
													"SHOW_BUTTON" => "N",
													"GET_FULL_INFO" => "Y",
													"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
													"GROUP_ID_FOR_SITE" => (intval($_GET["GROUP_ID"]) > 0 ? $_GET["GROUP_ID"] : (intval($arParams["GROUP_ID"]) > 0 ? $arParams["GROUP_ID"] : false)),
													'SHOW_EXTRANET_USERS' => 'FROM_MY_GROUPS',
													'DISPLAY_TAB_GROUP' => 'Y',
													'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
													'SHOW_LOGIN' => 'Y'
												),
												null,
												array("HIDE_ICONS" => "Y")
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
										<tr class="task-template-relations">

											<?// base template?>
											<td class="task-base-template">
												<?
													$APPLICATION->IncludeComponent(
														"bitrix:tasks.template.selector",
														"",
														array(
															"MULTIPLE" => "N",
															"NAME" => "BASE_TEMPLATE",
															"VALUE" => intval($arData["BASE_TEMPLATE_ID"]) ? intval($arData["BASE_TEMPLATE_ID"]) : false,
															"POPUP" => "Y",
															"ON_SELECT" => "onBaseTemplateSelect",
															"PATH_TO_TEMPLATES_TEMPLATE" => $arParams["PATH_TO_TEMPLATES_TEMPLATE"],
															"SITE_ID" => SITE_ID,
															"TEMPLATE_ID" => intval($arData['ID']) ? intval($arData['ID']) : false // to exclude subtree of a template being currently edited
														),
														null,
														array("HIDE_ICONS" => "Y")
													);
												?>
												<a href="" id="task-base-template-link" class="webform-field-action-link"><?php echo GetMessage("TASKS_BASE_TEMPLATE")?></a>
												<ol class="task-to-tasks-list task-to-tasks-list-single" id="task-base-template-list">
													<?if(intval($arData["BASE_TEMPLATE_ID"]) && is_array($arData['BASE_TEMPLATE_DATA'])):?>
														<li class="task-to-tasks-item">
															<a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => $arData['BASE_TEMPLATE_DATA']["ID"], "action" => "view"))?>" target="_blank" class="task-to-tasks-item-name"><?=$arData['BASE_TEMPLATE_DATA']["TITLE"]?></a>
															<span class="task-to-tasks-item-delete" onclick="onBaseTemplateRemove(<?=intval($arData['BASE_TEMPLATE_DATA']["ID"])?>, this)"></span>
														</li>
													<?endif?>
												</ol>
												<input type="hidden" name="BASE_TEMPLATE_ID" value="<?=$arData["BASE_TEMPLATE_ID"]?>" />
											</td>

											<?// previous tasks?>
											<td class="task-previous-tasks">
												<?php
													$name = $APPLICATION->IncludeComponent(
														"bitrix:tasks.task.selector",
														".default",
														array(
															"MULTIPLE" => "Y",
															"NAME" => "PREV_TASKS",
															"VALUE" => $arData["DEPENDS_ON"],
															"POPUP" => "Y",
															"ON_CHANGE" => "onPrevTasksChange",
															"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
															"SITE_ID" => SITE_ID,
															"SELECT" => array('ID', 'TITLE', 'STATUS'),
														),
														null,
														array("HIDE_ICONS" => "Y")
													);
												?>
												<a href="" id="task-previous-tasks-link" class="webform-field-action-link"><?php echo GetMessage("TASKS_TASK_PREVIOUS_TASKS")?></a>
												<ol class="task-to-tasks-list" id="task-previous-tasks-list">
													<?php if ($arData["DEPENDS_ON"]):?>
														<?php
															$rsDependTasks = CTasks::GetList(array("TITLE" => "ASC"), array("ID" => $arData["DEPENDS_ON"]));
															while($task = $rsDependTasks->GetNext()):
														?>
														<li class="task-to-tasks-item">
															<a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => $task["ID"], "action" => "view"))?>" target="_blank" class="task-to-tasks-item-name"><?php echo $task["TITLE"]?></a>
															<span class="task-to-tasks-item-delete" onclick="onPrevTasksUnselect(<?php echo $task["ID"]?>, this)"></span>
														</li>
														<?php endwhile?>
													<?php endif?>
												</ol>
												<input type="hidden" name="PREV_TASKS_IDS" value="<?php echo is_array($arData["DEPENDS_ON"]) ?  implode(",", $arData["DEPENDS_ON"]) : ""?>" />
											</td>

											<?// parent task?>
											<td class="task-supertask">
												<?php
													$name = $APPLICATION->IncludeComponent(
														"bitrix:tasks.task.selector",
														".default",
														array(
															"MULTIPLE" => "N",
															"NAME" => "PARENT_TASK",
															"VALUE" => $arData["PARENT_ID"],
															"POPUP" => "Y",
															"ON_SELECT" => "onParentTaskSelect",
															"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
															"SITE_ID" => SITE_ID,
															"SELECT" => array('ID', 'TITLE', 'STATUS'),
														),
														null,
														array("HIDE_ICONS" => "Y")
													);
												?>
												<a href="" id="task-supertask-link" class="webform-field-action-link"><?php echo GetMessage("TASKS_TASK_PARENT_TASK")?></a>
												<ol class="task-to-tasks-list task-to-tasks-list-single" id="task-parent-tasks-list">
													<?php if ($arData["PARENT_ID"]):?>
														<?php
															$rsParentTask = CTasks::GetList(array("TITLE" => "ASC"), array("ID" => $arData["PARENT_ID"]));
															if($task = $rsParentTask->GetNext()):
														?>
														<li class="task-to-tasks-item">
															<a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => $task["ID"], "action" => "view"))?>" target="_blank" class="task-to-tasks-item-name"><?php echo $task["TITLE"]?></a>
															<span class="task-to-tasks-item-delete" onclick="onParentTasksRemove(<?php echo $task["ID"]?>, this)"></span>
														</li>
														<?php endif?>
													<?php endif?>
												</ol>
												<input type="hidden" name="PARENT_ID" value="<?php echo $arData["PARENT_ID"]?>" />
											</td>
										</tr>
									</table>
								</div>
								<div class="webform-corners-bottom"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>
							</div>
						</div>
					</div>

					<div class="webform-row task-repeating-row">
						<table cellspacing="0" class="task-repeating-layout">
							<tr>
								<td class="task-repeating-label"><div class="webform-field-checkbox-option"><input type="checkbox" value="Y" id="task-repeating-checkbox" class="webform-field-checkbox" name="REPLICATE"<?php echo ($arData["REPLICATE"] == "Y" ? " checked" : "")?> /><label for="task-repeating-checkbox"><?php echo GetMessage("TASKS_TASK_REPEAT")?></label></div></td>
								<td class="task-repeating-settings">
									<div class="task-repeating<?php echo ($arData["REPLICATE"] == "Y" ? " selected" : "")?>" id="task-repeating">
										<div class="task-repeating-timespan" id="task-repeating-timespan"><?php foreach($arPeriods as $key=>$period):?><a href="" id="task-repeating-by-<?php echo $period["key"]?>"<?php echo ($arData["REPLICATE_PERIOD"] == $period["key"] ? "class=\"selected\"" : "")?>><i></i><span><?php echo $period["name"]?></span><b></b></a><?php endforeach?></div>
										<input type="hidden" name="REPLICATE_PERIOD" id="task-repeat-period" value="<?php echo ($arData["REPLICATE_PERIOD"] ? $arData["REPLICATE_PERIOD"] : "daily")?>" />
										<div class="task-repeating-timespan-details" id="task-repeating-timespan-details">
											<div class="task-repeating-timespan-details-inner">

												<div class="task-repeating-by task-repeating-by-daily<?php echo ($arData["REPLICATE_PERIOD"] == "daily" ? " selected" : "")?>">
													<div class="task-repeating-by-every-day-option"><label for="task-repeating-by-every-day"><?php echo GetMessage("TASKS_TASK_REPEAT_EVERY_1")?></label><span class="webform-field webform-field-textbox task-repeating-by-every-day"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-by-every-day" class="webform-field-textbox" maxlength="3" name="REPLICATE_EVERY_DAY" value="<?php echo ($arData["REPLICATE_EVERY_DAY"] ? $arData["REPLICATE_EVERY_DAY"] : 1)?>" /></span></span><label for="task-repeating-by-every-day"><?php echo GetMessage("TASKS_TASK_REPEAT_DAY")?></label></div>
													<div class="webform-field-checkbox-option task-repeating-working-day"><input type="checkbox" value="Y" id="task-repeating-working-day" class="webform-field-checkbox" name="REPLICATE_WORKDAY_ONLY"<?php echo ($arData["REPLICATE_WORKDAY_ONLY"] == 'Y' ? " checked" : "")?>  /><label for="task-repeating-working-day"><?php echo GetMessage("TASKS_TASK_REPEAT_WORK_ONLY")?></label></div>
												</div>

												<div class="task-repeating-by task-repeating-by-weekly<?php echo ($arData["REPLICATE_PERIOD"] == "weekly" ? " selected" : "")?>">
													<div class="task-repeating-by-every-week-option"><label for="task-repeating-by-every-week"><?php echo GetMessage("TASKS_TASK_REPEAT_EVERY_2")?></label><span class="webform-field webform-field-textbox task-repeating-by-every-week"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-by-every-week" class="webform-field-textbox" maxlength="2" name="REPLICATE_EVERY_WEEK" value="<?php echo ($arData["REPLICATE_EVERY_WEEK"] ? $arData["REPLICATE_EVERY_WEEK"] : 1)?>" /></span></span><label for="task-repeating-by-every-week"><?php echo GetMessage("TASKS_TASK_REPEAT_WEEK")?></label></div>
													<div class="task-repeating-timespan task-repeating-timespan-days" id="task-repeating-timespan-days"><?php for($key = 0; $key < 7; $key++):?><a href="" id="task-repeat-day-<?php echo $key+1?>"<?php echo (is_array($arData["REPLICATE_WEEK_DAYS"]) && in_array($key+1, $arData["REPLICATE_WEEK_DAYS"]) ? " class=\"selected\"" : "")?>><i></i><span><?php echo GetMessage("TASKS_REPEAT_DAY_SHORT_".$key)?></span><b></b></a><?php endfor?></div>
													<input type="hidden" name="REPLICATE_WEEK_DAYS" id="task-week-days" value="<?php echo is_array($arData["REPLICATE_WEEK_DAYS"]) ? implode(",", $arData["REPLICATE_WEEK_DAYS"]) : "1"?>" />
												</div>

												<div class="task-repeating-by task-repeating-by-monthly<?php echo ($arData["REPLICATE_PERIOD"] == "monthly" ? " selected" : "")?>">
													<table cellspacing="0" class="task-repeating-by-month-layout">
														<tr>
															<td class="task-repeating-by-month-number-radio"><input type="radio" name="REPLICATE_MONTHLY_TYPE" value="1"<?php echo ($arData["REPLICATE_MONTHLY_TYPE"] != 2 ? " checked" : "")?> /></td>
															<td class="task-repeating-by-month-number"><span class="webform-field webform-field-textbox task-repeating-every-month-day-number"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-every-month-day-number" class="webform-field-textbox" value="<?php echo ($arData["REPLICATE_MONTHLY_DAY_NUM"] ? $arData["REPLICATE_MONTHLY_DAY_NUM"] : 1)?>" name="REPLICATE_MONTHLY_DAY_NUM" /></span></span><label><?php echo GetMessage("TASKS_TASK_REPEAT_DATE")?> <?php echo GetMessage("TASKS_TASK_REPEAT_EVERY_3")?></label><span class="webform-field webform-field-textbox task-repeating-every-month-by-number"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-every-month-by-number" class="webform-field-textbox" value="<?php echo ($arData["REPLICATE_MONTHLY_MONTH_NUM_1"] ? $arData["REPLICATE_MONTHLY_MONTH_NUM_1"] : 1)?>" name="REPLICATE_MONTHLY_MONTH_NUM_1" /></span></span><label><?php echo GetMessage("TASKS_TASK_REPEAT_MONTH")?></label></td>
														</tr>
														<tr>
															<td class="task-repeating-by-month-day-radio"><input type="radio" name="REPLICATE_MONTHLY_TYPE" value="2"<?php echo ($arData["REPLICATE_MONTHLY_TYPE"] == 2 ? " checked" : "")?> /></td>
															<td class="task-repeating-by-month-day">
																<select name="REPLICATE_MONTHLY_WEEK_DAY_NUM">
																	<?php for($key = 0; $key < 5; $key++):?>
																	<option value="<?php echo $key?>"<?php echo ($arData["REPLICATE_MONTHLY_WEEK_DAY_NUM"] == $key ? " selected" : "")?>><?php echo GetMessage("TASKS_REPEAT_DAY_NUM_".$key)?></option>
																	<?php endfor?>
																</select>
																<select name="REPLICATE_MONTHLY_WEEK_DAY">
																	<?php for($key = 0; $key < 7; $key++):?>
																	<option value="<?php echo $key?>"<?php echo ($arData["REPLICATE_MONTHLY_WEEK_DAY"] == $key ? " selected" : "")?>><?php echo GetMessage("TASKS_REPEAT_DAY_".$key)?></option>
																	<?php endfor?>
																</select>
																<label for="task-repeating-every-month-by-day"><?php echo GetMessage("TASKS_TASK_REPEAT_EVERY_4")?></label><span class="webform-field webform-field-textbox task-repeating-every-month-by-day"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-every-month-by-day" class="webform-field-textbox" value="<?php echo ($arData["REPLICATE_MONTHLY_MONTH_NUM_2"] ? $arData["REPLICATE_MONTHLY_MONTH_NUM_2"] : 1)?>" name="REPLICATE_MONTHLY_MONTH_NUM_2" /></span></span><label for="task-repeating-every-month-by-day"><?php echo GetMessage("TASKS_TASK_REPEAT_MONTH")?></label>
															</td>
														</tr>
													</table>
												</div>

												<div class="task-repeating-by task-repeating-by-yearly<?php echo ($arData["REPLICATE_PERIOD"] == "yearly" ? " selected" : "")?>">
													<table cellspacing="0" class="task-repeating-by-year-layout">
														<tr>
															<td class="task-repeating-by-year-number-radio"><input type="radio" name="REPLICATE_YEARLY_TYPE" value="1"<?php echo ($arData["REPLICATE_YEARLY_TYPE"] != 2 ? " checked" : "")?> /></td>
															<td class="task-repeating-by-year-number"><label for="task-repeating-every-year-day-number"><?php echo GetMessage("TASKS_TASK_REPEAT_EVERY_5")?></label><span class="webform-field webform-field-textbox task-repeating-every-year-day-number"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-every-year-day-number" class="webform-field-textbox" value="<?php echo ($arData["REPLICATE_YEARLY_DAY_NUM"] ? $arData["REPLICATE_YEARLY_DAY_NUM"] : 1)?>" name="REPLICATE_YEARLY_DAY_NUM" /></span></span><label><?php echo GetMessage("TASKS_TASK_REPEAT_DAY_OF_MONTH")?></label>
																<select name="REPLICATE_YEARLY_MONTH_1">
																	<?php for($key = 0; $key < 12; $key++):?>
																	<option value="<?php echo $key?>"<?php echo ($arData["REPLICATE_YEARLY_MONTH_1"] == $key ? " selected" : "")?>><?php echo GetMessage("TASKS_REPEAT_MONTH_".$key)?></option>
																	<?php endfor?>
																</select>
															</td>
														</tr>
														<tr>
															<td class="task-repeating-by-year-day-radio"><input type="radio" name="REPLICATE_YEARLY_TYPE" value="2"<?php echo ($arData["REPLICATE_YEARLY_TYPE"] == 2 ? " checked" : "")?> /></td>
															<td class="task-repeating-by-year-day">
																<label><?php echo GetMessage("TASKS_TASK_REPEAT_AT")?></label>
																<select name="REPLICATE_YEARLY_WEEK_DAY_NUM">
																	<?php for($key = 0; $key < 5; $key++):?>
																	<option value="<?php echo $key?>"<?php echo ($arData["REPLICATE_YEARLY_WEEK_DAY_NUM"] == $key ? " selected" : "")?>><?php echo GetMessage("TASKS_REPEAT_DAY_NUM_".$key)?></option>
																	<?php endfor?>
																</select>
																<select name="REPLICATE_YEARLY_WEEK_DAY">
																	<?php for($key = 0; $key < 7; $key++):?>
																	<option value="<?php echo $key?>"<?php echo ($arData["REPLICATE_YEARLY_WEEK_DAY"] == $key ? " selected" : "")?>><?php echo GetMessage("TASKS_REPEAT_DAY_".$key)?></option>
																	<?php endfor?>
																</select>
																<label><?php echo GetMessage("TASKS_TASK_REPEAT_YEARLY_MONTH")?></label>
																<select name="REPLICATE_YEARLY_MONTH_2">
																	<?php for($key = 0; $key < 12; $key++):?>
																	<option value="<?php echo $key?>"<?php echo ($arData["REPLICATE_YEARLY_MONTH_2"] == $key ? " selected" : "")?>><?php echo GetMessage("TASKS_REPEAT_MONTH_".$key)?></option>
																	<?php endfor?>
																</select>
															</td>
														</tr>
													</table>
												</div>

												<?if(!intval($arData['ID'])):?>
													<?$arData['REPLICATE_TIMEZONE_OFFSET'] = \Bitrix\Tasks\Util\User::getTimeZoneOffset();?>
												<?endif?>

												<?if(array_key_exists('REPLICATE_TIMEZONE_OFFSET', $arData)):?>
													<input type="hidden" name="REPLICATE_TIMEZONE_OFFSET" value="<?=intval($arData['REPLICATE_TIMEZONE_OFFSET'])?>" />
												<?endif?>
												<?if(array_key_exists('REPLICATE_DAILY_MONTH_INTERVAL', $arData)):?>
													<input type="hidden" name="REPLICATE_DAILY_MONTH_INTERVAL" value="<?=intval($arData['REPLICATE_DAILY_MONTH_INTERVAL'])?>" />
												<?endif?>
												<?if(array_key_exists('REPLICATE_REPEAT_TILL', $arData)):?>
													<input type="hidden" name="REPLICATE_REPEAT_TILL" value="<?=htmlspecialcharsbx($arData['REPLICATE_REPEAT_TILL'])?>" />
												<?endif?>
												<?if(array_key_exists('REPLICATE_TIMES', $arData)):?>
													<input type="hidden" name="REPLICATE_TIMES" value="<?=intval($arData['REPLICATE_TIMES'])?>" />
												<?endif?>

												<div class="task-repeating-interval"><label for="task-repeating-interval-start-date"><?php echo GetMessage("TASKS_TASK_REPEAT_START")?></label><span class="webform-field webform-field-textbox webform-field-textbox-clearable<?php echo (!$arData["REPLICATE_START_DATE"] ? " webform-field-textbox-empty" : "")?> task-repeating-interval-start-date"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-interval-start-date" class="webform-field-textbox" name="REPLICATE_START_DATE" value="<?php echo CTasksTools::stripZeroTime($arData["REPLICATE_START_DATE"]); ?>" readonly="readonly" /><a class="webform-field-textbox-clear" href=""></a></span></span><label for="task-repeating-interval-end-date"><?php echo GetMessage("TASKS_TASK_REPEAT_END")?></label><span class="webform-field webform-field-textbox webform-field-textbox-clearable<?php echo (!$arData["REPLICATE_END_DATE"] ? " webform-field-textbox-empty" : "")?> task-repeating-interval-end-date"><span class="webform-field-textbox-inner"><input type="text" id="task-repeating-interval-end-date" class="webform-field-textbox" name="REPLICATE_END_DATE" value="<?php echo CTasksTools::stripZeroTime($arData["REPLICATE_END_DATE"]); ?>" readonly="readonly" /><a class="webform-field-textbox-clear" href=""></a></span></span></div>

												<div class="task-repeating-time">

													<style>
														.task-main-clock-monkeyfix
														{
															visibility: hidden;
															height: 0;
															width: 0;
															overflow: hidden;
															display: inline-block;
															margin-top: -3px;
															margin-left: -7px;
														}
													</style>

													<?=GetMessage("TASKS_TASK_REPEAT_TIME")?>
												    <span id="template-replication-timepicker">
										                <input data-bx-id="timepicker-display" type="text" readonly="readonly" style="width:100px" />
											            <input data-bx-id="timepicker-value" name="REPLICATE_TIME" type="hidden" value="<?=htmlspecialcharsbx($arData['REPLICATE_TIME'])?>" />
										                <div class="task-main-clock-monkeyfix">
											                <?$GLOBALS['APPLICATION']->IncludeComponent('bitrix:main.clock', '', array(
												                'INIT_TIME' => $arData['REPLICATE_TIME'] ? $arData['REPLICATE_TIME'] : '00:00',
												                'INPUT_ID' => 'taskReplicationTimeFake'
											                ));?>
										                </div>
										            </span>

												</div>
											</div>
										</div>
									</div>
								</td>
							</tr>
						</table>

					</div>

					<?php if (sizeof($arResult["USER_FIELDS"])): ?>
						<?//todo: uf managing form here (as a component)?>
						<div class="webform-row task-additional-properties-row">
							<table cellspacing="0" class="task-properties-layout">
								<?$deprecatedUfs = array('file', 'vote', 'video', 'disk_version', 'string_formatted', 'url_preview');?>
								<?foreach ($arResult["USER_FIELDS"] as $arUserField):?>

									<?if(
										$arUserField['FIELD_NAME'] == \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode() ||
										in_array($arUserField['USER_TYPE_ID'], $deprecatedUfs)
									):?>
										<?continue;?>
									<?endif?>

									<tr>
										<td class="task-property-name"><?php echo htmlspecialcharsbx($arUserField["EDIT_FORM_LABEL"]) ?>:</td>
										<td class="task-property-value">
											<?\Bitrix\Tasks\Util\UserField\UI::showEdit($arUserField);?>
										</td>
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
		<div class="webform-buttons task-buttons">
			<a href="" class="webform-button webform-button-create" id="task-submit-button"><span class="webform-button-left"></span><span class="webform-button-text"><?php if ($arResult["ACTION"] == "create"):?><?php echo GetMessage("TASKS_TASK_ADD_TEMPLATE")?><?php else:?><?php echo GetMessage("TASKS_TASK_SAVE_TEMPLATE")?><?php endif?></span><span class="webform-button-right"></span></a>
			<a href="<?php echo $arResult["RETURN_URL"]?>" class="webform-button-link webform-button-link-cancel"><?php echo GetMessage("TASKS_TASK_CANCEL")?></a>
		</div>
	</div>
	<input type="hidden" name="apply" value="save" />
</form>

<?php $this->SetViewTarget("pagetitle", 100);?>
<div class="task-title-buttons task-detail-title-buttons"><a class="task-title-button task-title-button-back" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TEMPLATES"], array());?>"><i class="task-title-button-back-icon"></i><span class="task-title-button-back-text"><?php echo GetMessage("TASKS_ADD_BACK_TO_TEMPLATES_LIST")?></span></a></div>
<?php $this->EndViewTarget();?>