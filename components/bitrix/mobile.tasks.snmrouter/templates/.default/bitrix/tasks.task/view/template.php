<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CBitrixComponent $component */
/** @var string $templateFolder */
$templateData = $arResult["TEMPLATE_DATA"];
if (isset($templateData["ERROR"]))
{
	?><div class="task-detail-error">
		<div class="task-detail-error-box">
			<div class="task-detail-error-icon"></div><br />
			<div class="task-detail-error-text"><?=htmlspecialcharsbx($templateData["ERROR"]["MESSAGE"])?></div>
		</div>
	</div><?
	return;
}
?><?=CJSCore::Init(array('tasks_util_query'), true);?><?
$APPLICATION->SetPageProperty('BodyClass', 'task-card-page');
\Bitrix\Main\Page\Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/log_mobile.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs($templateFolder.'/../.default/script.js');
$task = &$arResult["DATA"]["TASK"];
$can = $arResult["CAN"]["TASK"]["ACTION"];
$can["CHECKLIST_ADD_ITEMS"] = false;
$arResult["FORM_ID"] = 'MOBILE_TASK_VIEW';
$arResult['GRID_ID'] = 'MOBILE_TASK_VIEW';
$task["PRIORITY"] = ($task["PRIORITY"] == CTasks::PRIORITY_HIGH ? CTasks::PRIORITY_HIGH : CTasks::PRIORITY_LOW);
$arResult["STATUSES"] = CTaskItem::getStatusMap();
$task["~STATUS"] = $task["STATUS"];
$task["STATUS"] = GetMessage("TASKS_STATUS_" . $arResult["STATUSES"][$task["REAL_STATUS"]]);
$task["STATUS"] = ( empty($task["STATUS"]) ? GetMessage("TASKS_STATUS_STATE_UNKNOWN") : $task["STATUS"]);
$timerTask = ($task["ALLOW_TIME_TRACKING"] == "Y" ? CTaskTimerManager::getInstance($USER->getId())->getRunningTask(false) : array());
$timerTask = is_array($timerTask) ? $timerTask : array();
if ($timerTask['TASK_ID'] == $task['ID'])
	$task["TIME_SPENT_IN_LOGS"] += $timerTask['RUN_TIME'];
$subtasks_html = null;
if ($templateData["SUBTASKS_EXIST"] && $component->getParent())
{
	ob_start();
	$APPLICATION->IncludeComponent(
		"bitrix:tasks.list",
		"subtasks",
		array(
			"COMMON_FILTER" => array(),
			"ORDER" => array("GROUP_ID"  => "ASC"),
			"PREORDER" => array("STATUS_COMPLETE" => "ASC"),
			"FILTER" => array("PARENT_ID" => $task["ID"]),
			"VIEW_STATE" => array(),
			"CONTEXT_ID" => CTaskColumnContext::CONTEXT_TASK_DETAIL,
			"PATH_TO_USER_PROFILE" => $arParams["~PATH_TO_USER_PROFILE"],
			"PATH_TO_USER_TASKS" => $arParams["~PATH_TO_USER_TASKS"],
			"PATH_TO_USER_TASKS_TASK" => $arParams["~PATH_TO_USER_TASKS_VIEW"],
			"FORCE_LIST_MODE" => "Y"
		),
		$component->getParent(),
		array("HIDE_ICONS" => "Y")
	);
	$subtasks_html = ob_get_clean();
}

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
	<?if ($can["EDIT"]) : ?><input type="hidden" name="data[SE_AUDITOR][]" value="" /><input type="hidden" name="data[PRIORITY]" value="<?=$task["PRIORITY"]?>" /><? endif; ?>
	<div style="display: none;"><input type="text" name="AJAX_POST" value="Y" /></div><?//hack to not submit form?>
<?
$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'mobile',
	array(
		'FORM_ID' => $arResult["FORM_ID"],
		'THEME_GRID_ID' => $arResult["GRID_ID"],
		'DATE_TIME_FORMAT' => $arParams["DATE_TIME_FORMAT"],
		'RESTRICTED_MODE' => "Y",
		'TABS' => array( // Tabs to show for mobile version it is redundant just to keep compatibility with web version
			array(
				"id" => "task_base",
				"title" => GetMessage("MB_TASKS_BASE_SETTINGS"),
				"name" => GetMessage("MB_TASKS_BASE_SETTINGS"),
				"fields" => array(
					array(
						"class" => "bx-tasks-title",
						"type" => "label",
						"id" => "data[TITLE]",
						"value" => (
							($can["FAVORITE.ADD"] || $can["FAVORITE.DELETE"] ? "<div id=\"favorites".$task["ID"]."\" class=\"favorites".($can["FAVORITE.DELETE"] ? " active" : "")."\"></div>" : "").
							($can["EDIT"] ? "<input id=\"title".$task["ID"]."\" type=\"hidden\" data-bx-type=\"text\" name=\"data[TITLE]\" value=\"".htmlspecialcharsbx($task["TITLE"])."\" />" : "").
							"<label>".($can["EDIT"] ? "<span id=\"title".$task["ID"]."Container\">" : "").htmlspecialcharsbx($task["TITLE"]).($can["EDIT"] ? "</span>" : "")." (".GetMessage("MB_TASKS_BASE_SETTINGS_TITLE_TASK", array("#TASK_ID#" => $task["ID"])).")</label>").
							($task["~STATUS"] == CTasks::METASTATE_EXPIRED ? '<div class="mobile-grid-field-expired">'.GetMessage("MB_TASKS_TASK_DETAIL_NOTIFICATION_EXPIRED_BRIEF").'</div>' : null)
							.($task["~STATUS"] == CTasks::METASTATE_EXPIRED_SOON ? '<div class="mobile-grid-field-expired">'.GetMessage("MB_TASKS_TASK_DETAIL_NOTIFICATION_EXPIRED_SOON_BRIEF").'</div>' : null)
							.($task["~STATUS"] == CTasks::METASTATE_VIRGIN_NEW ? '<div class="mobile-grid-field-expired">'.GetMessage("MB_TASKS_TASK_DETAIL_NOTIFICATION_NEW_BRIEF").'</div>' : null)
							.($task["~STATUS"] == CTasks::STATE_SUPPOSEDLY_COMPLETED ? '<div class="mobile-grid-field-expired">'.GetMessage("MB_TASKS_TASK_DETAIL_NOTIFICATION_STATE_SUPPOSEDLY_COMPLETED").'</div>' : null)
							.($task["DEADLINE"] == '' && $task['CREATED_BY'] != $task['RESPONSIBLE_ID'] ? '<div class="mobile-grid-field-expired">'.GetMessage("MB_TASKS_TASK_DETAIL_NOTIFICATION_WO_DEADLINE_BRIEF").'</div>' : null)
					),
					(!empty($task["DESCRIPTION"]) ? ( $can["EDIT"] && false ?
						array(
						"type" => "texteditor",
						"text-type" => $task['DESCRIPTION_IN_BBCODE'] == 'Y' ? "bbcode" : "html",
						"id" => "data[DESCRIPTION]",
						"name" => GetMessage("MB_TASKS_BASE_SETTINGS_DESCRIPTION"),
						"placeholder" => GetMessage("MB_TASKS_BASE_SETTINGS_DESCRIPTION_PLACEHOLDER"),
						"value" => $task["~DESCRIPTION"]
					) : array(
						"type" => "label",
						"id" => "data[DESCRIPTION]",
						"value" => $task["DESCRIPTION"]
					)) : null),
					(($can["CHECKLIST_ADD_ITEMS"] || !empty($task["SE_CHECKLIST"])) ? array(
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
									$item['ACTION']['MODIFY'] = false;
									$item['ACTION']['REMOVE'] = false;

									$separator = \Bitrix\Tasks\UI\Task\CheckList::checkIsSeparatorValue($item["TITLE"]);
									$ids[] = $item["ID"];
									$item["TITLE"] = htmlspecialcharsbx($item["TITLE"]);
									$result .=
									"<label id=\"checkListItem".$item["ID"]."Label\" for=\"checkListItem".$item["ID"]."\" class=\"task-view-checklist".(
										($item["ACTION"]["TOGGLE"] ? " task-view-checklist-toggle" : "").
										($item["ACTION"]["MODIFY"] ? " task-view-checklist-modify" : "").
										($item["ACTION"]["REMOVE"] ? " task-view-checklist-remove" : "")
									)."\">".
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
							($can["CHECKLIST_ADD_ITEMS"] ?
								"<div class='mobile-grid-button'>".
									"<a id=\"checkList".$task["ID"]."Add\" href=\"#\">".GetMessage("MB_TASKS_TASK_ADD")."</a>".
									"<a id=\"checkList".$task["ID"]."Separator\" href=\"#\">".GetMessage("MB_TASKS_TASK_ADD_SEPARATOR")."</a>".
								"</div>" : "")
					) : null),
					array(
						"type" => "label",
						"id" => "data[STATUS]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_STATUS"),
						"value" => '<span id="bx-task-status-'.$task["ID"].'">'.$task["STATUS"]."</span>"
					),
					($can["EDIT"] ? array(
						"type" => "checkbox",
						"id" => "data[PRIORITY]",
						"params" => array(
							"class" => "mobile-grid-field-priority"
						),
						"name" => GetMessage("MB_TASKS_BASE_SETTINGS_PRIORITY"),
						"items" => array(CTasks::PRIORITY_HIGH => GetMessage("MB_TASKS_BASE_SETTINGS_PRIORITY_VALUE") ),
						"value" => array($task["PRIORITY"])
					) : array(
						"type" => "label",
						"id" => "data[PRIORITY]",
						"class" => "mobile-grid-field-priority",
						"name" => GetMessage("MB_TASKS_BASE_SETTINGS_PRIORITY"),
						"value" => "<label class=\"mobile-grid-field-priority mobile-grid-field-priority-".$task["PRIORITY"]."\"><span>".GetMessage("MB_TASKS_BASE_SETTINGS_PRIORITY_VALUE")."</span></label>"
					)),
					array(
						"type" => ($can["EDIT.PLAN"] ? "datetime" : "datetimelabel"),
						"id" => "data[DEADLINE]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_DEADLINE"),
						"placeholder" => GetMessage("MB_TASKS_TASK_SETTINGS_DEADLINE_PLACEHOLDER"),
						"value" => ($can["EDIT.PLAN"] && !$can["EDIT"] && empty($task["DEADLINE"]) ? "0" : $task["DEADLINE"])
					),
					($task["ALLOW_TIME_TRACKING"] == "Y" ?
						array(
						"type" => "label",
						"class" => "mobile-grid-field-timetracking-block",
						"id" => "data[TIMETRACKING]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_TIMETRACKING"),
						"value" =>
							"<label for=\"bx-task-timetracking-".$task["ID"]."\" class=\"mobile-grid-field-timetracking\">".
								"<input type=\"checkbox\" id=\"bx-task-timetracking-".$task["ID"]."\" value=\"".intval($task["TIME_SPENT_IN_LOGS"])."\" ".($timerTask['TASK_ID'] == $task['ID'] ? "checked" : "")." ".
								($timerTask['TASK_ID'] == $task['ID'] || $can["DAYPLAN.TIMER.TOGGLE"] ? "" : " disabled")." />".
								"<span class=\"start\">".($task["TIME_SPENT_IN_LOGS"] > 0 ? GetMessage("TASKS_TT_CONTINUE") : GetMessage("TASKS_TT_START"))."</span>".
								"<span class=\"pause\">".GetMessage("TASKS_TT_PAUSE")."</span>".
								"<span class=\"timetracking\"><span class=\"spent\"  id=\"bx-task-timetracking-".$task["ID"]."-value\">".sprintf('%02d:%02d:%02d', floor($task['TIME_SPENT_IN_LOGS']  / 3600), floor($task['TIME_SPENT_IN_LOGS'] / 60) % 60, $task['TIME_SPENT_IN_LOGS'] % 60)."</span>".
							(
								$task["TIME_ESTIMATE"] > 0 ?
									"<span class=\"divider\"> / </span><span class=\"estimated\">". sprintf('%02d:%02d', floor($task['TIME_ESTIMATE']  / 3600), floor($task['TIME_ESTIMATE'] / 60) % 60). "</span>" :
									""
							).
							"</span></label>"
					) : null),
					(($mark = array(
						"NULL" => GetMessage('MB_TASKS_TASK_SETTINGS_MARK_NULL'),
						"P" => GetMessage('MB_TASKS_TASK_SETTINGS_MARK_P'),
						"N" => GetMessage('MB_TASKS_TASK_SETTINGS_MARK_N'))) &&
					($task["MARK"] = ($task["MARK"] == "N" || $task["MARK"] == "P" ? $task["MARK"] : "NULL")) &&
					$can["EDIT"] ?
					array(
						"type" => "select",
						"class" => "bx-tasks-task-mark bx-tasks-task-mark-".$task["MARK"],
						"id" => "data[MARK]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_MARK"),
						"items" => $mark,
						"value" => $task["MARK"]
					) : array(
						"type" => "label",
						"class" => "bx-tasks-task-mark bx-tasks-task-mark-".$task["MARK"],
						"id" => "data[MARK]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_MARK"),
						"value" => $mark[$task["MARK"]]
					)),
					array(
						"type" => ($can["EDIT"] ? "select-user" : "user"),
						"id" => "data[SE_RESPONSIBLE][0][ID]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_RESPONSIBLE"),
						"item" => $task["SE_RESPONSIBLE"],
						"value" => $task["RESPONSIBLE_ID"],
						"canDrop" => false
					),
					array(
						"type" => "user",
						"id" => "data[SE_ORIGINATOR][ID]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_AUTHOR_ID"),
						"item" => $task["SE_ORIGINATOR"],
						"value" => $task["CREATED_BY"],
						"canDrop" => false
					),
					(!empty($task["SE_AUDITOR"]) || $can["EDIT"] ?
					array(
						"type" => ($can["EDIT"] ? "select-users" : "users"),
						"id" => "data[SE_AUDITOR][]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_AUDITORS"),
						"items" => $task["SE_AUDITOR"],
						"value" => $task["AUDITORS"]
					) : null),
					(!empty($task["SE_ACCOMPLICE"]) ? array(
						"type" => "users",
						"id" => "data[SE_ACCOMPLICE][]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_ACCOMPLICES"),
						"items" => $task["SE_ACCOMPLICE"],
						"value" => $task["ACCOMPLICES"]
					) : null),
					(is_array($arResult["AUX_DATA"]) && is_array($arResult["AUX_DATA"]["USER_FIELDS"]) &&
						array_key_exists("UF_TASK_WEBDAV_FILES", $arResult["AUX_DATA"]["USER_FIELDS"]) &&
						($can["EDIT"] || !empty($arResult["AUX_DATA"]["USER_FIELDS"]["UF_TASK_WEBDAV_FILES"]["VALUE"])) ?
					array(
						"type" => ($can["EDIT"] ? "disk" : "diskview"),
						"id" => "data[UF_TASK_WEBDAV_FILES]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_DISK_UF"),
						"value" => array_merge(
							$arResult["AUX_DATA"]["USER_FIELDS"]["UF_TASK_WEBDAV_FILES"],
							array("FIELD_NAME" => "data[UF_TASK_WEBDAV_FILES]"))
					) : null),
					(is_array($arResult["AUX_DATA"]) && is_array($arResult["AUX_DATA"]["USER_FIELDS"]) &&
						array_key_exists("UF_CRM_TASK", $arResult["AUX_DATA"]["USER_FIELDS"]) ? array(
						"type" => "crmview",
						"id" => "data[UF_CRM_TASK]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_CRM_TASK"),
						"value" => array_merge(
							$arResult["AUX_DATA"]["USER_FIELDS"]["UF_CRM_TASK"],
							array("FIELD_NAME" => "data[UF_CRM_TASK]"))
					) : null),
					($task["GROUP_ID"] > 0 && is_array($arResult["TEMPLATE_DATA"]["GROUP"]) ?
					array(
						"type" => ($can["EDIT"] ? "select-group" : "group"),
						"id" => "data[SE_PROJECT][ID]",
						"class" => "mobile-grid-field-taskgroups",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_GROUP_ID"),
						"item" => reset($arResult["DATA"]["GROUP"]),
						"value" => $task["GROUP_ID"]
					) : null),
					(!empty($task["SE_TAGS"]) ?
					array(
						"type" => ($can["EDIT"] ? "text" : "label"),
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
					) : null),
					(!empty($task["SE_PARENTTASK"]) ?
					array(
						"type" => "label",
						"id" => "data[PARENT_ID]",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_PARENT_ID"),
						"class" => "mobile-grid-field-parent-id",
						"value" =>
							"<span onclick=\"BXMobileApp.PageManager.loadPageBlank({url: '".CUtil::JSEscape(CComponentEngine::MakePathFromTemplate(
								$arParams["~PATH_TO_USER_TASKS_TASK"],
								array(
									"USER_ID" => $arParams["USER_ID"],
									"TASK_ID" => $task["SE_PARENTTASK"]["ID"])))."',bx24ModernStyle : true});\">".htmlspecialcharsbx($task["SE_PARENTTASK"]["TITLE"]).
							"</span>"
					) : null),
					($subtasks_html !== null ? array(
						"type" => "custom",
						"id" => "SUBTASKS",
						"name" => GetMessage("MB_TASKS_TASK_SETTINGS_SUBTASKS"),
						"class" => "mobile-grid-field-subtasks",
						"value" => $subtasks_html
					) : null)
				)
			)
		),
		"SHOW_FORM_TAG" => false
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?></form><?
$task_html = ob_get_clean();
ob_start();
global $APPLICATION;
$APPLICATION->IncludeComponent("bitrix:forum.comments", "", array(
		"FORUM_ID" => $task['FORUM_ID'],
		"ENTITY_TYPE" => "TK",
		"ENTITY_ID" => $task['ID'],
		"ENTITY_XML_ID" => "TASK_".$task['ID'],
		"POST_CONTENT_TYPE_ID" => "TASK",
		"URL_TEMPLATES_PROFILE_VIEW" => $arParams['PATH_TEMPLATE_TO_USER_PROFILE'],
		"CACHE_TYPE" => "Y",
		"CACHE_TIME" => 3600,
		"IMAGE_HTML_SIZE" => 400,
		"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
		"SHOW_RATING" => "Y",
		"RATING_TYPE" => "like",
		'PREORDER' => 'N',
		"PERMISSION" => "M",
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"]
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$task_comments_html = ob_get_clean();

$component->arResult["HTML"] = array(
	"body" => $task_html,
	"comments" => $task_comments_html
);
?>
<div class="task-comments-block" id="task-block"><?=$task_html?>
<?
$voteId = "TASK".'_'.$task["ID"].'-'.(time()+rand(0, 1000));
$emotion = (!empty($arResult["RATING"][$arResult["Post"]["ID"]]["USER_REACTION"])? mb_strtoupper($arResult["RATING"][$arResult["Post"]["ID"]]["USER_REACTION"]) : 'LIKE');

?><div id="post_inform_wrap_two" class="post-item-inform-wrap"><?
		?><span class="post-item-informers bx-ilike-block" id="rating_block_<?=$task["ID"]?>" data-counter="<?=intval($arResult["TEMPLATE_DATA"]["RATING"]["TOTAL_VOTES"])?>"><?
			?><span data-rating-vote-id="<?=htmlspecialcharsbx($voteId)?>" id="bx-ilike-button-<?=htmlspecialcharsbx($voteId)?>" class="post-item-informer-like feed-inform-ilike"><?
				?><span class="bx-ilike-left-wrap<?=(isset($arResult["TEMPLATE_DATA"]["RATING"]["USER_HAS_VOTED"]) && $arResult["TEMPLATE_DATA"]["RATING"]["USER_HAS_VOTED"] == "Y" ? ' bx-you-like-button' : '')?>"><span class="bx-ilike-text"><?=\CRatingsComponentsMain::getRatingLikeMessage($emotion)?></span></span><?
			?></span><?
		?></span><?
		?><div class="post-item-informers post-item-inform-comments" onclick="BX.onCustomEvent(window, 'OnUCUserReply', ['TASK_<?=$task['ID']?>']);"><?
			?><div class="post-item-inform-left"><?=GetMessage("MB_TASKS_TASK_COMMENT")?></div><?
		?></div><?
?></div>
</div>
<div class="post-item-inform-wrap-tree" id="rating-footer-wrap"><?
	?><div class="feed-post-emoji-top-panel-outer"><?
		?><div id="feed-post-emoji-top-panel-container-<?=htmlspecialcharsbx($voteId)?>" class="feed-post-emoji-top-panel-box <?=(intval($arResult["TEMPLATE_DATA"]["RATING"]["TOTAL_POSITIVE_VOTES"]) > 0 ? 'feed-post-emoji-top-panel-container-active' : '')?>"><?
			$APPLICATION->IncludeComponent(
				"bitrix:rating.vote",
				"like_react",
				array(
					"MOBILE" => "Y",
					"ENTITY_TYPE_ID" => "TASK",
					"ENTITY_ID" => $task["ID"],
					"OWNER_ID" => $task["CREATED_BY"],
					"USER_VOTE" => $arResult["TEMPLATE_DATA"]["RATING"]["USER_VOTE"],
					"USER_REACTION" => $arResult["RATING"]["USER_REACTION"],
					"USER_HAS_VOTED" => $arResult["TEMPLATE_DATA"]["RATING"]["USER_HAS_VOTED"],
					"TOTAL_VOTES" => $arResult["TEMPLATE_DATA"]["RATING"]["TOTAL_VOTES"],
					"TOTAL_POSITIVE_VOTES" => $arResult["TEMPLATE_DATA"]["RATING"]["TOTAL_POSITIVE_VOTES"],
					"TOTAL_NEGATIVE_VOTES" => $arResult["TEMPLATE_DATA"]["RATING"]["TOTAL_NEGATIVE_VOTES"],
					"TOTAL_VALUE" => $arResult["TEMPLATE_DATA"]["RATING"]["TOTAL_VALUE"],
					"REACTIONS_LIST" => $arResult["RATING"]["REACTIONS_LIST"],
					"PATH_TO_USER_PROFILE" => $arParams["~PATH_TO_USER"],
					'TOP_DATA' => (!empty($arResult['TOP_RATING_DATA']) ? $arResult['TOP_RATING_DATA'] : false),
					'VOTE_ID' => $voteId
				),
				$component->__parent,
				array("HIDE_ICONS" => "Y")
			);
		?></div><?
	?></div><?
?></div>
<div class="task-comments-block" id="task-comments-block"><?=$task_comments_html?></div>
<script type="text/javascript">
BX.message({
	PAGE_TITLE : '<?=GetMessageJS("MB_TASKS_GENERAL_TITLE")?>',
	MB_TASKS_TASK_PLACEHOLDER : '<span class="placeholder"><?=GetMessageJS("MB_TASKS_TASK_PLACEHOLDER")?></span>',
	MB_TASKS_TASK_DETAIL_BTN_EDIT:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_EDIT'); ?>',
	MB_TASKS_TASK_DETAIL_BTN_REMOVE:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_REMOVE'); ?>',
	MB_TASKS_TASK_DETAIL_BTN_ACCEPT_TASK:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_ACCEPT_TASK'); ?>',
	MB_TASKS_TASK_DETAIL_BTN_START_TASK:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_START_TASK'); ?>',
	MB_TASKS_TASK_DETAIL_BTN_DECLINE_TASK:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_DECLINE_TASK'); ?>',
	MB_TASKS_TASK_DETAIL_BTN_RENEW_TASK:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_RENEW_TASK'); ?>',
	MB_TASKS_TASK_DETAIL_BTN_CLOSE_TASK:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_CLOSE_TASK'); ?>',
	MB_TASKS_TASK_DETAIL_BTN_PAUSE_TASK:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_PAUSE_TASK'); ?>',
	MB_TASKS_TASK_DETAIL_BTN_APPROVE_TASK:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_APPROVE_TASK'); ?>',
	MB_TASKS_TASK_DETAIL_BTN_REDO_TASK:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_REDO_TASK'); ?>',
	MB_TASKS_TASK_DETAIL_BTN_DELEGATE_TASK:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_DELEGATE_TASK'); ?>',
	MB_TASKS_TASK_DETAIL_BTN_ADD_FAVORITE_TASK:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_ADD_FAVORITE_TASK')?>',
	MB_TASKS_TASK_DETAIL_BTN_DELETE_FAVORITE_TASK:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_DELETE_FAVORITE_TASK')?>',
	MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED'); ?>',
	MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED_OR_NOT_ENOUGH_RIGHTS:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED_OR_NOT_ENOUGH_RIGHTS'); ?>',
	MB_TASKS_TASK_DETAIL_NOTIFICATION_EXPIRED:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_NOTIFICATION_EXPIRED'); ?>',
	MB_TASKS_TASK_DETAIL_NOTIFICATION_STATE_SUPPOSEDLY_COMPLETED:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_NOTIFICATION_STATE_SUPPOSEDLY_COMPLETED'); ?>',
	MB_TASKS_TASK_DETAIL_USER_SELECTOR_BTN_CANCEL:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_USER_SELECTOR_BTN_CANCEL'); ?>',
	MB_TASKS_TASK_DETAIL_CONFIRM_REMOVE:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_CONFIRM_REMOVE'); ?>',
	MB_TASKS_TASK_REMOVE_CONFIRM_TITLE:'<?=GetMessageJS('MB_TASKS_TASK_REMOVE_CONFIRM_TITLE'); ?>',
	MB_TASKS_TASK_DETAIL_TASK_ADD:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_TASK_ADD')?>',
	MB_TASKS_TASK_DETAIL_TASK_ADD_SUBTASK:'<?=GetMessageJS('MB_TASKS_TASK_DETAIL_TASK_ADD_SUBTASK')?>',
	TASKS_LIST_GROUP_ACTION_ERROR1:'<?=GetMessageJS("TASKS_LIST_GROUP_ACTION_ERROR1")?>',
	// For task.task.edit/script.js
	TASKS_PRIORITY_0 : '<?=GetMessageJS("TASKS_PRIORITY_0")?>',
	TASKS_PRIORITY_2 : '<?=GetMessageJS("TASKS_PRIORITY_2")?>',
	MB_TASKS_TASK_ERROR1 : '<?=GetMessageJS("MB_TASKS_TASK_ERROR1")?>',
	MB_TASKS_TASK_ERROR2 : '<?=GetMessageJS("MB_TASKS_TASK_ERROR2")?>',
	MB_TASKS_TASK_DELETE : '<?=GetMessageJS("MB_TASKS_TASK_DELETE")?>',
	MB_TASKS_TASK_EDIT : '<?=GetMessageJS("MB_TASKS_TASK_EDIT")?>',
	MB_TASKS_TASK_CHECK : '<?=GetMessageJS("MB_TASKS_TASK_CHECK")?>',
	MB_TASKS_TASK_UNCHECK : '<?=GetMessageJS("MB_TASKS_TASK_UNCHECK")?>',
	MB_TASKS_TASK_CHECKLIST_PLACEHOLDER : '<?=GetMessageJS("MB_TASKS_TASK_CHECKLIST_PLACEHOLDER")?>',
	TASKS_STATUS_METASTATE_EXPIRED : '<?=GetMessageJS("TASKS_STATUS_METASTATE_EXPIRED")?>',
	TASKS_STATUS_STATE_NEW : '<?=GetMessageJS("TASKS_STATUS_STATE_NEW")?>',
	TASKS_STATUS_STATE_PENDING : '<?=GetMessageJS("TASKS_STATUS_STATE_PENDING")?>',
	TASKS_STATUS_STATE_IN_PROGRESS : '<?=GetMessageJS("TASKS_STATUS_STATE_IN_PROGRESS")?>',
	TASKS_STATUS_STATE_SUPPOSEDLY_COMPLETED : '<?=GetMessageJS("TASKS_STATUS_STATE_SUPPOSEDLY_COMPLETED")?>',
	TASKS_STATUS_STATE_COMPLETED : '<?=GetMessageJS("TASKS_STATUS_STATE_COMPLETED")?>',
	TASKS_STATUS_STATE_DEFERRED : '<?=GetMessageJS("TASKS_STATUS_STATE_DEFERRED")?>',
	TASKS_STATUS_STATE_DECLINED : '<?=GetMessageJS("TASKS_STATUS_STATE_DECLINED")?>',
	TASKS_STATUS_STATE_UNKNOWN : '<?=GetMessageJS("TASKS_STATUS_STATE_UNKNOWN")?>',
	TASKS_TT_ERROR1_TITLE : '<?=GetMessageJS("TASKS_TT_ERROR1_TITLE")?>',
	TASKS_TT_ERROR1_DESC : '<?=GetMessageJS("TASKS_TT_ERROR1_DESC")?>',
	TASKS_TT_CONTINUE : '<?=GetMessageJS("TASKS_TT_CONTINUE")?>',
	TASKS_TT_CANCEL : '<?=GetMessageJS("TASKS_TT_CANCEL")?>'
});
BX.ready(function(){
	new BX.Mobile.Tasks.detail({
			taskData : <?=CUtil::PhpToJSObject(array(
				"ID" => $task["ID"],
				"TITLE" => $task['TITLE'],
				"DESCRIPTION" => $task['DESCRIPTION'],
				"RESPONSIBLE_ID" => $task['RESPONSIBLE_ID'],
				"CREATED_BY" => $task['CREATED_BY'],
				"PRIORITY" => $task['PRIORITY'],
				"STATUS" => $task['STATUS'],
				"REAL_STATUS" => $task['REAL_STATUS'],
				"GROUP_ID" => $task["GROUP_ID"],
				"DEADLINE" => $task['DEADLINE'],
				"ACCOMPLICES" => $task['ACCOMPLICES'],
				"AUDITORS" => $task['AUDITORS'],
				"CHECKLIST" => $task["CHECKLIST"],
				"ACTION" => $task['ACTION'],
				"LOG_ID" => (isset($templateData["LOG_ID"]) ? intval($templateData["LOG_ID"]) : 0)
			))?>,
			formId : '<?=CUtil::JSEscape($arResult["FORM_ID"])?>',
			currentTs : <?=(isset($templateData["CURRENT_TS"]) ? intval($templateData["CURRENT_TS"]) : 0)?>
		}
	);

    setTimeout(function(){
        if (BX('post-comments-wrap'))
        {
            var firstNewComment = BX.findChild(BX('post-comments-wrap'), { className : 'post-comment-block-new' }, true);
            if (firstNewComment)
            {
                document.body.scrollTop = firstNewComment.offsetTop;
            }
            // else
            // {
            //     var firstComment = BX.findChild(BX('post-comments-wrap'), { className : 'post-comment-block' }, true);
            //     document.body.scrollTop = (firstComment ? firstComment.offsetTop : 0);
            // }
        }
    }, 100);
});

if(typeof BX.MSL != 'undefined')
	BX.MSL.viewImageBind('tasks-detail-card-container-over', { tag: 'IMG', attr: 'data-bx-image' });
</script>