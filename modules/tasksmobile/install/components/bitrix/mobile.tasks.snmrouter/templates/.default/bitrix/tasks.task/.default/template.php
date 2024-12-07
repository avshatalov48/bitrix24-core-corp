<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CBitrixComponent $component */
/** @var string $templateFolder */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Tasks\Internals\Task\Priority;

$APPLICATION->SetPageProperty('BodyClass', 'task-card-page');
if (
	isset($arResult["TEMPLATE_DATA"]["ERROR"])
	&& $arResult["TEMPLATE_DATA"]["ERROR"]
)
{
	echo $arResult["TEMPLATE_DATA"]["ERROR"]["MESSAGE"];
	return;
}
?><?=CJSCore::Init(array('tasks_util_query'), true);?><?php
Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/log_mobile.js');
Asset::getInstance()->addJs($templateFolder.'/../.default/script.js');

$guid = str_replace('-', '', $arParams["GUID"]);
$arResult["FORM_ID"] = "MOBILE_TASK_EDIT_{$guid}";
$arResult["GRID_ID"] = "MOBILE_TASK_EDIT_{$guid}";

$task = &$arResult["DATA"]["TASK"];
$can = $arResult["CAN"]["TASK"]["ACTION"];

$statuses = CTaskItem::getStatusMap();
$status = (isset($task['REAL_STATUS']) ? $statuses[$task['REAL_STATUS']] : '');
$status = Loc::getMessage("TASKS_STATUS_{$status}");

$task["STATUS"] = (empty($status) ? GetMessage("TASKS_STATUS_STATE_UNKNOWN") : $status);

$taskLimitExceeded = $arResult['AUX_DATA']['TASK_LIMIT_EXCEEDED'];

ob_start();
$APPLICATION->IncludeComponent(
	'bitrix:tasks.widget.checklist.new',
	'mobile',
	[
		'ENTITY_ID' => $task['ID'],
		'ENTITY_TYPE' => 'TASK',
		'MODE' => 'edit',
		'DATA' => $task['SE_CHECKLIST'],
		'INPUT_PREFIX' => 'data[SE_CHECKLIST]',
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
		'CONVERTED' => $arResult['DATA']['CHECKLIST_CONVERTED'],
		'CAN_ADD_ACCOMPLICE' => $can['EDIT'] && !$taskLimitExceeded,
		'TASK_GUID' => $arParams['GUID'],
		'DISK_FOLDER_ID' => $arResult['AUX_DATA']['DISK_FOLDER_ID'],
	],
	null,
	['HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y']
);
$checklistHtml = ob_get_clean();

ob_start();

$url = CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_USER_TASKS_EDIT"], array(
		"USER_ID" => $USER->getid(),
		"TASK_ID" => $task["ID"]));
?>
<form name="<?=$arResult["FORM_ID"]?>" id="<?=$arResult["FORM_ID"]?>" action="<?=$url?>" method="POST">
	<?=bitrix_sessid_post();?>
	<?if (isset($arResult['FORM_GUID'])): ?><input type="hidden" name="FORM_GUID" value="<?=htmlspecialcharsbx($arResult['FORM_GUID']); ?>"><? endif ;?>
	<input type="hidden" name="_JS_STEPPER_SUPPORTED" value="Y">
	<input type="hidden" name="DESCRIPTION_IN_BBCODE" value="<?=$task['DESCRIPTION_IN_BBCODE']; ?>" />
	<input type="hidden" name="back_url" value="<?=$url."&".http_build_query(array("save" => "Y", "sessid" => bitrix_sessid())) ?>" />
	<input type="hidden" name="data[PRIORITY]" value="<?=((int)$task["PRIORITY"] === Priority::HIGH ? Priority::HIGH : Priority::LOW)?>" />
	<input type="hidden" name="data[ADD_TO_FAVORITE]" value="N" />
	<input type="hidden" name="data[SE_AUDITOR][]" value="" />
	<input type="hidden" name="data[SE_ACCOMPLICE][]" value="" />
	<div style="display: none;"><input type="text" name="AJAX_POST" value="Y" /></div><?//hack to not submit form?>
<?
$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'mobile',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'THEME_GRID_ID' => $arResult['GRID_ID'],
		'DATE_TIME_FORMAT' => $arParams["DATE_TIME_FORMAT"],
//		'RESTRICTED_MODE' => "Y",
		'TABS' => array( // Tabs to show for mobile version it is redundant just to keep compatibility with web version
			array(
				"id" => "task_base",
				"fields" => array(
					array(
						"type" => "text",
						"required" => true,
						"id" => "data[TITLE]",
						"name" => GetMessage("MB_TASKS_BASE_SETTINGS_TITLE"),
						"placeholder" => GetMessage("MB_TASKS_BASE_SETTINGS_TITLE_PLACEHOLDER"),
						"value" => $task["TITLE"]
					),
					array(
						"type" => "textarea",
						"id" => "data[DESCRIPTION]",
						"name" => GetMessage("MB_TASKS_BASE_SETTINGS_DESCRIPTION"),
						"placeholder" => GetMessage("MB_TASKS_BASE_SETTINGS_DESCRIPTION_PLACEHOLDER"),
						"value" => htmlspecialcharsback(
							htmlspecialchars_decode($task['DESCRIPTION'], ENT_QUOTES)
						),
					),
					array(
						"type" => "checkbox",
						"id" => "data[PRIORITY]",
						"params" => array(
							"class" => "mobile-grid-field-priority"
						),
						"name" => GetMessage("MB_TASKS_BASE_SETTINGS_PRIORITY"),
						"items" => [Priority::HIGH => ((int)$task["PRIORITY"] === Priority::HIGH ? GetMessage("TASKS_PRIORITY_2") : GetMessage("TASKS_PRIORITY_0"))],
						"value" => array($task["PRIORITY"])
					),
					array(
						"type" => "select-user",
						"id" => "data[SE_RESPONSIBLE][0][ID]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_RESPONSIBLE_MSGVER_1"),
						"item" => $task["SE_RESPONSIBLE"],
						"value" => $task["RESPONSIBLE_ID"],
						"canDrop" => false
					),
					[
						"type" => 'custom',
						"id" => 'tasks-check-list',
						"class" => 'mobile-grid-restricted',
						"value" => $checklistHtml,
					],
					array(
						"type" => ($can["EDIT"] || $can["EDIT.PLAN"] ? "datetime" : "datetimelabel"),
						"id" => "data[DEADLINE]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_DEADLINE"),
						"placeholder" => GetMessage("MB_TASKS_TASK_SETTINGS_DEADLINE_PLACEHOLDER"),
						"value" => ($task["DEADLINE"] ?? null),
					),
					($task["ID"] > 0 && !$taskLimitExceeded ? [
						"type" => "select",
						"id" => "data[MARK]",
						"class" => "bx-tasks-task-mark bx-tasks-task-mark-".$task["MARK"],
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_MARK"),
						"items" => [
							"NULL" => GetMessage('MB_TASKS_TASK_SETTINGS_MARK_NULL'),
							"P" => GetMessage('MB_TASKS_TASK_SETTINGS_MARK_P'),
							"N" => GetMessage('MB_TASKS_TASK_SETTINGS_MARK_N'),
						],
						"value" => ($task["MARK"] == "N" || $task["MARK"] == "P" ? $task["MARK"] : "NULL")
					] : null),
					array(
						"type" => "section",
						"id" => "timeplanning",
						"name" => "",
						"value" => GetMessage("MB_TASKS_TASK_SETTINGS_TIMEPLANNING"),
						"expanded" => (!empty($task["START_DATE_PLAN"]) || !empty($task["END_DATE_PLAN"]) || !empty($task["DURATION_PLAN"]))
					),
					array(
						"type" => "datetime",
						"section" => "timeplanning",
						"id" => "data[START_DATE_PLAN]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_DATETIME"),
						"placeholder" => GetMessage("MB_TASKS_TASK_SETTINGS_DATETIME_PLACEHOLDER"),
						"value" => ($task["START_DATE_PLAN"] ?? null),
					),
					array(
						"type" => "datetime",
						"section" => "timeplanning",
						"id" => "data[END_DATE_PLAN]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_END_DATE_PLAN"),
						"placeholder" => GetMessage("MB_TASKS_TASK_SETTINGS_END_DATE_PLAN_PLACEHOLDER"),
						"value" => ($task["END_DATE_PLAN"] ?? null),
					),
					array(
						"type" => "label",
						"class" => "bx-tasks-duration",
						"section" => "timeplanning",
						"id" => "data[DURATION_PLAN]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_DURATION_PLAN"),
						"value" =>
							"<input id='durationType".$task["ID"]."' type='hidden' name='data[DURATION_TYPE]' value='".($task["DURATION_TYPE"] == "hours" ? "hours" : "days")."' />".
							"<span id='durationType".$task["ID"]."Label' class=\"bx-tasks-duration-type\">".($task["DURATION_TYPE"] == "hours" ?
								GetMessage("MB_TASKS_TASK_SETTINGS_DURATION_PLAN_HOURS") : GetMessage("MB_TASKS_TASK_SETTINGS_DURATION_PLAN_DAYS")).
							"</span>".
							"<input type='number' name='data[DURATION_PLAN]' value='".(int)($task["DURATION_PLAN"] ?? null)."' />"
					),
					[
						"type" => ($can["EDIT.ORIGINATOR"] ? "select-user" : "user"),
						"id" => "data[SE_ORIGINATOR][ID]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_AUTHOR_ID"),
						"item" => $task["SE_ORIGINATOR"],
						"value" => $task["CREATED_BY"],
						"canDrop" => false,
					],
					[
						"type" => "select-users",
						"id" => "data[SE_AUDITOR][]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_AUDITORS"),
						"items" => $task["SE_AUDITOR"],
						"value" => $task["AUDITORS"],
						"canAdd" => !$taskLimitExceeded,
					],
					[
						"type" => "select-users",
						"id" => "data[SE_ACCOMPLICE][]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_ACCOMPLICES"),
						"items" => $task["SE_ACCOMPLICE"],
						"value" => $task["ACCOMPLICES"],
						"canAdd" => !$taskLimitExceeded,
					],
					[
						"type" => "checkbox",
						"id" => "ADDITIONAL[]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_ADDITIONAL"),
						"items" => [
							"ALLOW_CHANGE_DEADLINE" => GetMessage("MB_TASKS_TASK_SETTINGS_ADDITIONAL_ALLOW_CHANGE_DEADLINE_MSGVER_1"),
							"MATCH_WORK_TIME" => GetMessage("MB_TASKS_TASK_SETTINGS_ADDITIONAL_MATCH_WORK_TIME"),
						]
						+ ($taskLimitExceeded ? [] : ["TASK_CONTROL" => GetMessage("MB_TASKS_TASK_SETTINGS_ADDITIONAL_TASK_CONTROL_V2")])
						+ ($task["ID"] > 0 ? [] : ["ADD_TO_TIMEMAN" => GetMessage("MB_TASKS_TASK_SETTINGS_ADDITIONAL_ADD_INTO_DAY_PLAN")])
						+ (
							$task["ID"] <= 0 && ($can["FAVORITE.ADD"] || $can["FAVORITE.DELETE"])
								? ["ADD_TO_FAVORITE" => GetMessage("TASKS_FAVORITES_0")]
								: []
						),
						"value" => array_merge(
							($task["ALLOW_CHANGE_DEADLINE"] == "Y" ? ["ALLOW_CHANGE_DEADLINE"] : []),
							($task["MATCH_WORK_TIME"] == "Y" ? ["MATCH_WORK_TIME"] : []),
							($task["TASK_CONTROL"] == "Y" ? ["TASK_CONTROL"] : [])
						)
					],
					(is_array($arResult["AUX_DATA"]) && is_array($arResult["AUX_DATA"]["USER_FIELDS"]) &&
						array_key_exists("UF_TASK_WEBDAV_FILES", $arResult["AUX_DATA"]["USER_FIELDS"]) ? array(
						"type" => "disk",
						"id" => "data[UF_TASK_WEBDAV_FILES]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_DISK_UF"),
						"value" => array_merge(
							$arResult["AUX_DATA"]["USER_FIELDS"]["UF_TASK_WEBDAV_FILES"],
							array("FIELD_NAME" => "data[UF_TASK_WEBDAV_FILES]"))
					) : null),
					(is_array($arResult["AUX_DATA"]) && is_array($arResult["AUX_DATA"]["USER_FIELDS"]) &&
						array_key_exists("UF_CRM_TASK", $arResult["AUX_DATA"]["USER_FIELDS"]) ? array(
						"type" => "crm",
						"id" => "data[UF_CRM_TASK]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_CRM_TASK"),
						"value" => array_merge(
							$arResult["AUX_DATA"]["USER_FIELDS"]["UF_CRM_TASK"],
							array("FIELD_NAME" => "data[UF_CRM_TASK]"))
					) : null),
					array(
						"type" => "custom",
						"section" => "timetracking",
						"id" => "data[ALLOW_TIME_TRACKING]",
						"class" => "mobile-grid-field-timetracking-edit",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_TIMETRACKING"),
						"value" =>
							'<div class="'.($task["ALLOW_TIME_TRACKING"] == "Y" ? 'active' : 'nonactive').'">'.
								'<div class="mobile-grid-data-checkbox-container">'.
									'<label for="data_ALLOW_TIME_TRACKING_">'.
										'<input type="checkbox" name="data[ALLOW_TIME_TRACKING]" id="data_ALLOW_TIME_TRACKING_" value="Y" onclick="var n=this.parentNode.parentNode.parentNode; if(this.checked){BX.removeClass(n, \'nonactive\');BX.addClass(n, \'active\');}else{BX.removeClass(n, \'active\');BX.addClass(n, \'nonactive\');}" '.($task["ALLOW_TIME_TRACKING"] == "Y" ? ' checked' : '').' />'.
										'<span>'.GetMessage("MB_TASKS_TIMETRACKING_TRACK").'</span>'.
									'</label>'.
								'</div>'.
								'<div class="mobile-grid-field-custom mobile-grid-field-timetracking-estimate">'.
									'<div class="mobile-grid-section-child">'.
										'<span class="mobile-grid-title">'.GetMessage("MB_TASKS_TIMETRACKING_TIME").'</span>'.
										'<div class="mobile-grid-block">'.
											'<div class="mobile-grid-data-label-container">'.
												'<input type="hidden" id="timeEstimate'.$task["ID"].'Seconds" value="'.($task["TIME_ESTIMATE"] ?? null).'" name="data[TIME_ESTIMATE]" />'.
												'<input type="number" id="timeEstimate'.$task["ID"].'Hours" placeholder="0" min="0" value="0" />' . '<label for="timeEstimate'.$task["ID"].'Hours">'.GetMessage("MB_TASKS_TIMETRACKING_HOURS").'</label>' .
												'<input type="number" id="timeEstimate'.$task["ID"].'Minutes" placeholder="0" min="0" value="0" />' . '<label for="timeEstimate'.$task["ID"].'Minutes">'.GetMessage("MB_TASKS_TIMETRACKING_MINUTES").'</label>' .
											'</div>'.
										'</div>'.
									'</div>'.
								"</div>".
							'</div>'
					),
					array(
						"type" => "text",
						"id" => "data[TAGS]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_TAGS"),
						"placeholder" => GetMessage("MB_TASKS_TASK_SETTINGS_TAGS_PLACEHOLDER"),
						"value" => (($tags = function($list)
						{
							$result = array();
							foreach($list as $item)
								$result[] = $item["NAME"];
							return implode(', ', $result);
						}) ? $tags($task["SE_TAG"]) : "")
					),
					array(
						"type" => "select-group",
						"id" => "data[SE_PROJECT][ID]",
						"class" => "mobile-grid-field-taskgroups",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_GROUP_ID"),
						"item" => (isset($task["GROUP_ID"]) && $task["GROUP_ID"] > 0 ? $arResult["DATA"]["GROUP"][$task["GROUP_ID"]] : null),
						"value" => ($task["GROUP_ID"] ?? null),
					),
					array(
						"type" => "custom",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_PARENT_ID"),
						"class" => "mobile-grid-field-parent-id",
						"value" =>
						'<div>'.
							'<input name="data[PARENT_ID]" type="hidden" value="'.(int)($task["SE_PARENTTASK"]['ID'] ?? 0).'" id="parentId'.$task["ID"].'" />'.
							'<div class="mobile-grid-field-custom-item">'.
								'<del></del>'.
								'<span id="parentId'.$task["ID"].'Container" onclick="var n = BX(\'parentId'.$task['ID'].'\').value; if(parseInt(n)>0){BXMobileApp.PageManager.loadPageUnique({url:(\''.
									CUtil::JSEscape(CComponentEngine::MakePathFromTemplate(
										$arParams["PATH_TO_USER_TASKS_VIEW"],
										array("USER_ID" => $arParams["USER_ID"]))).'\').replace(/#TASK_ID#/gi, n), bx24ModernStyle:true});}">'.(isset($task["SE_PARENTTASK"]) && $task["SE_PARENTTASK"] ? $task["SE_PARENTTASK"]["TITLE"] : "")."</span>".
							"</div>".
							"<a class=\"mobile-grid-button select-parent add\" id=\"parentId".$task["ID"]."Select\" href=\"#\"><span>".GetMessage("MB_TASKS_TASK_ADD")."</span><span>".GetMessage("MB_TASKS_TASK_CHANGE")."</span></a>".
						'</div>'
					)
				)
			)
		),
		"SHOW_FORM_TAG" => false,
		"BUTTONS" => "app",
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>
</form>
<script>
	BX.message({
		MB_TASKS_TASK_PLACEHOLDER : '<span class="placeholder"><?=GetMessageJS("MB_TASKS_TASK_PLACEHOLDER")?></span>',
		MB_TASKS_TASK_ERROR1 : '<?=GetMessageJS("MB_TASKS_TASK_ERROR1")?>',
		MB_TASKS_TASK_ERROR2 : '<?=GetMessageJS("MB_TASKS_TASK_ERROR2")?>',
		MB_TASKS_TASK_ERROR3 : '<?=GetMessageJS("MB_TASKS_TASK_ERROR3")?>',
		MB_TASKS_TASK_DELETE : '<?=GetMessageJS("MB_TASKS_TASK_DELETE")?>',
		MB_TASKS_TASK_EDIT : '<?=GetMessageJS("MB_TASKS_TASK_EDIT")?>',
		TASKS_PRIORITY_0 : '<?=GetMessageJS("TASKS_PRIORITY_0")?>',
		TASKS_PRIORITY_2 : '<?=GetMessageJS("TASKS_PRIORITY_2")?>',
		TASKS_FAVORITES_0 : '<?=GetMessageJS("TASKS_FAVORITES_0")?>',
		TASKS_FAVORITES_1 : '<?=GetMessageJS("TASKS_FAVORITES_1")?>',
		MB_TASKS_TASK_CHECK : '<?=GetMessageJS("MB_TASKS_TASK_CHECK")?>',
		MB_TASKS_TASK_UNCHECK : '<?=GetMessageJS("MB_TASKS_TASK_UNCHECK")?>',
		MB_TASKS_TASK_CHECKLIST_PLACEHOLDER : '<?=GetMessageJS("MB_TASKS_TASK_CHECKLIST_PLACEHOLDER")?>',
		MB_TASKS_TASK_SETTINGS_DURATION_PLAN_HOURS : '<?=GetMessageJS("MB_TASKS_TASK_SETTINGS_DURATION_PLAN_HOURS")?>',
		MB_TASKS_TASK_SETTINGS_DURATION_PLAN_DAYS : '<?=GetMessageJS("MB_TASKS_TASK_SETTINGS_DURATION_PLAN_DAYS")?>',
		TASKS_TT_ERROR1_TITLE : '<?=GetMessageJS("TASKS_TT_ERROR1_TITLE")?>',
		TASKS_TT_ERROR1_DESC : '<?=GetMessageJS("TASKS_TT_ERROR1_DESC")?>',
		TASKS_TT_CONTINUE : '<?=GetMessageJS("TASKS_TT_CONTINUE")?>',
		TASKS_TT_CANCEL : '<?=GetMessageJS("TASKS_TT_CANCEL")?>'
	});
BX.ready(function(){
	app.hidePopupLoader();
	BX.message({ PAGE_TITLE : ""} );
	new BX.Mobile.Tasks.edit(<?=CUtil::PhpToJSObject(array(
		"taskData" => array(
			"ID" => $task["ID"],
			"CHECKLIST" => ($task["CHECKLIST"] ?? null),
		),
		"formId" => $arResult['FORM_ID'],
		"guid" => $arParams["GUID"],
	))?>);
});
</script>
<?
$body = ob_get_clean();
?><div id="<?=$arResult['FORM_ID']?>_errors"><?=implode("<br />", $arResult["TEMPLATE_DATA"]["ERRORS"])?></div><?=$body;