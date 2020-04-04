<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CBitrixComponent $component */
/** @var string $templateFolder */

$APPLICATION->SetPageProperty('BodyClass', 'task-card-page');
if ($arResult["TEMPLATE_DATA"]["ERROR"])
{
	echo $arResult["TEMPLATE_DATA"]["ERROR"]["MESSAGE"];
	return;
}
?><?=CJSCore::Init(array('tasks_util_query'), true);?><?
\Bitrix\Main\Page\Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/log_mobile.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs($templateFolder.'/../.default/script.js');

$task = &$arResult["DATA"]["TASK"];
$can = $arResult["CAN"]["TASK"]["ACTION"];
$arResult["FORM_ID"] = 'MOBILE_TASK_EDIT';
$arResult["GRID_ID"] = 'MOBILE_TASK_EDIT';
$arResult["STATUSES"] = CTaskItem::getStatusMap();
$task["STATUS"] = GetMessage("TASKS_STATUS_" . $arResult["STATUSES"][$task["REAL_STATUS"]]);
$task["STATUS"] = ( empty($task["STATUS"]) ? GetMessage("TASKS_STATUS_STATE_UNKNOWN") : $task["STATUS"]);

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
	<input type="hidden" name="data[PRIORITY]" value="<?=($task["PRIORITY"] == CTasks::PRIORITY_HIGH ? CTasks::PRIORITY_HIGH : CTasks::PRIORITY_LOW)?>" />
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
				"title" => GetMessage("MB_TASKS_BASE_SETTINGS"),
				"name" => GetMessage("MB_TASKS_BASE_SETTINGS"),
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
						"value" => $task["DESCRIPTION"]
					),
					array(
						"type" => "checkbox",
						"id" => "data[PRIORITY]",
						"params" => array(
							"class" => "mobile-grid-field-priority"
						),
						"name" => GetMessage("MB_TASKS_BASE_SETTINGS_PRIORITY"),
						"items" => array(CTasks::PRIORITY_HIGH => ($task["PRIORITY"] == CTasks::PRIORITY_HIGH ? GetMessage("TASKS_PRIORITY_2") : GetMessage("TASKS_PRIORITY_0"))),
						"value" => array($task["PRIORITY"])
					),
					array(
						"type" => "select-user",
						"id" => "data[SE_RESPONSIBLE][0][ID]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_RESPONSIBLE"),
						"item" => $task["SE_RESPONSIBLE"],
						"value" => $task["RESPONSIBLE_ID"],
						"canDrop" => false
					),
					array(
						"type" => "custom",
						"id" => "tasks-check-list",
						"class" => "mobile-grid-field-tasks-checklist",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_CHECKLIST"),
						"value" => "<div id=\"checkList".$task["ID"]."Container\" class=\"mobile-grid-field-tasks-checklist-container\">".(($checkList = function($list, &$ids)
							{
								$result = "";
								$ids = array();
								foreach($list as $item)
								{
									$separator = \Bitrix\Tasks\UI\Task\CheckList::checkIsSeparatorValue($item["TITLE"]);
									$ids[] = $item["ID"];
									$item["TITLE"] = htmlspecialcharsbx($item["TITLE"]);
									$result .=
									"<label id=\"checkListItem".$item["ID"]."Label\" for=\"checkListItem".$item["ID"]."\" class=\"task-view-checklist task-view-checklist-toggle task-view-checklist-modify task-view-checklist-remove\">".
										"<span class=\"".($separator ? "mobile-grid-field-divider" : "mobile-grid-field-tasks-checklist-item")."\">".
											"<input type=\"hidden\" name=\"data[SE_CHECKLIST][".$item["ID"]."][ID]\" value=\"".$item["ID"]."\" />".
											"<input type=\"checkbox\" name=\"data[SE_CHECKLIST][".$item["ID"]."][IS_COMPLETE]\" id=\"checkListItem".$item["ID"]."\"".($item["IS_COMPLETE"] == "Y" ? " checked " : "")." value=\"Y\" />".
											($separator ? "" : "<span class=\"mobile-grid-field-tasks-checklist-item-text\">".$item["TITLE"]."</span>").
											"<i class=\"mobile-grid-menu\" id=\"checkListItem".$item["ID"]."Menu\"></i>".
											"<input type=\"hidden\" name=\"data[SE_CHECKLIST][".$item["ID"]."][TITLE]\" value=\"".$item["TITLE"]."\" />".
											"<input type=\"hidden\" name=\"data[SE_CHECKLIST][".$item["ID"]."][SORT_INDEX]\" value=\"".$item["SORT_INDEX"]."\" />".
										"</span>".
									"</label>";
								}
								return $result;
							}) ? $checkList($task["SE_CHECKLIST"], $task["CHECKLIST"]) : "")."</div>".
							"<div class='mobile-grid-button'>".
								"<a id=\"checkList".$task["ID"]."Add\" href=\"#\">".GetMessage("MB_TASKS_TASK_ADD")."</a>".
								"<a id=\"checkList".$task["ID"]."Separator\" href=\"#\">".GetMessage("MB_TASKS_TASK_ADD_SEPARATOR")."</a>".
							"</div>"
					),
					array(
						"type" => ($can["EDIT"] || $can["EDIT.PLAN"] ? "datetime" : "datetimelabel"),
						"id" => "data[DEADLINE]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_DEADLINE"),
						"placeholder" => GetMessage("MB_TASKS_TASK_SETTINGS_DEADLINE_PLACEHOLDER"),
						"value" => $task["DEADLINE"]
					),
					($task["ID"] > 0 ? array(
						"type" => "select",
						"id" => "data[MARK]",
						"class" => "bx-tasks-task-mark bx-tasks-task-mark-".$task["MARK"],
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_MARK"),
						"items" => array(
							"NULL" => GetMessage('MB_TASKS_TASK_SETTINGS_MARK_NULL'),
							"P" => GetMessage('MB_TASKS_TASK_SETTINGS_MARK_P'),
							"N" => GetMessage('MB_TASKS_TASK_SETTINGS_MARK_N')),
						"value" => ($task["MARK"] == "N" || $task["MARK"] == "P" ? $task["MARK"] : "NULL")
					) : null),
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
						"value" => $task["START_DATE_PLAN"]
					),
					array(
						"type" => "datetime",
						"section" => "timeplanning",
						"id" => "data[END_DATE_PLAN]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_END_DATE_PLAN"),
						"placeholder" => GetMessage("MB_TASKS_TASK_SETTINGS_END_DATE_PLAN_PLACEHOLDER"),
						"value" => $task["END_DATE_PLAN"]
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
							"<input type='number' name='data[DURATION_PLAN]' value='".intval($task["DURATION_PLAN"])."' />"
					),
					array(
						"type" => ($can["EDIT.ORIGINATOR"] ? "select-user" : "user"),
						"id" => "data[SE_ORIGINATOR][ID]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_AUTHOR_ID"),
						"item" => $task["SE_ORIGINATOR"],
						"value" => $task["CREATED_BY"],
						"canDrop" => false
					),
					array(
						"type" => "select-users",
						"id" => "data[SE_AUDITOR][]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_AUDITORS"),
						"items" => $task["SE_AUDITOR"],
						"value" => $task["AUDITORS"]
					),
					array(
						"type" => "select-users",
						"id" => "data[SE_ACCOMPLICE][]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_ACCOMPLICES"),
						"items" => $task["SE_ACCOMPLICE"],
						"value" => $task["ACCOMPLICES"]
					),
					array(
						"type" => "checkbox",
						"id" => "ADDITIONAL[]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_ADDITIONAL"),
						"items" => array(
							"ALLOW_CHANGE_DEADLINE" => GetMessage("MB_TASKS_TASK_SETTINGS_ADDITIONAL_ALLOW_CHANGE_DEADLINE"),
							"MATCH_WORK_TIME" => GetMessage("MB_TASKS_TASK_SETTINGS_ADDITIONAL_MATCH_WORK_TIME"),
							"TASK_CONTROL" => GetMessage("MB_TASKS_TASK_SETTINGS_ADDITIONAL_TASK_CONTROL"))
							+ ($task["ID"] > 0 ? array() : array(
//								"ADD_IN_REPORT" => GetMessage("MB_TASKS_TASK_SETTINGS_ADDITIONAL_ADD_IN_REPORT"),
								"ADD_TO_TIMEMAN" => GetMessage("MB_TASKS_TASK_SETTINGS_ADDITIONAL_ADD_INTO_DAY_PLAN")
							))
							+ ((!($task["ID"] > 0) && ($can["FAVORITE.ADD"] || $can["FAVORITE.DELETE"])) ?
								array("ADD_TO_FAVORITE" => GetMessage("TASKS_FAVORITES_0")) :
								array()
							)
							//"CONSEQUENCE" => GetMessage("MB_TASKS_TASK_SETTINGS_ADDITIONAL_CONSEQUENCE")
						,
						"value" => array_merge(($task["TASK_CONTROL"]=="Y" ? array("TASK_CONTROL") : array()),
								($task["MATCH_WORK_TIME"]=="Y" ? array("MATCH_WORK_TIME") : array()),
								($task["ALLOW_CHANGE_DEADLINE"]=="Y" ? array("ALLOW_CHANGE_DEADLINE") : array())
							//	+ ($task["CONSEQUENCE"]=="Y" ? array("CONSEQUENCE") : array())
						)
					),
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
												'<input type="hidden" id="timeEstimate'.$task["ID"].'Seconds" value="'.$task["TIME_ESTIMATE"].'" name="data[TIME_ESTIMATE]" />'.
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
						"item" => (is_array($arResult["DATA"]["GROUP"]) ? reset($arResult["DATA"]["GROUP"]) : null),
						"value" => $task["GROUP_ID"]
					),
					array(
						"type" => "custom",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_PARENT_ID"),
						"class" => "mobile-grid-field-parent-id",
						"value" =>
						'<div>'.
							'<input name="data[PARENT_ID]" type="hidden" value="'.intval(isset($task["SE_PARENTTASK"]) ? $task["SE_PARENTTASK"]["ID"] : 0).'" id="parentId'.$task["ID"].'" />'.
							'<div class="mobile-grid-field-custom-item">'.
								'<del></del>'.
								'<span id="parentId'.$task["ID"].'Container" onclick="var n = BX(\'parentId'.$task['ID'].'\').value; if(parseInt(n)>0){BXMobileApp.PageManager.loadPageUnique({url:(\''.
									CUtil::JSEscape(CComponentEngine::MakePathFromTemplate(
										$arParams["PATH_TO_USER_TASKS_VIEW"],
										array("USER_ID" => $arParams["USER_ID"]))).'\').replace(/#TASK_ID#/gi, n), bx24ModernStyle:true});}">'.($task["SE_PARENTTASK"] ? $task["SE_PARENTTASK"]["TITLE"] : "")."</span>".
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
				"CHECKLIST" => $task["CHECKLIST"],
		),
		"formId" => $arResult['FORM_ID'],
	))?>);
});
</script>
<?
$body = ob_get_clean();
?><div id="<?=$arResult['FORM_ID']?>_errors"><?=implode("<br />", $arResult["TEMPLATE_DATA"]["ERRORS"])?></div><?=$body;
