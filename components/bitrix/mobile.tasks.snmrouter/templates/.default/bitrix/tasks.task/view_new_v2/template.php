<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

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
use Bitrix\Main\Text\HtmlFilter;

\Bitrix\Main\UI\Extension::load('mobile.diskfile');

Loc::loadMessages(__FILE__);

$templateData = $arResult["TEMPLATE_DATA"];
if (isset($templateData["ERROR"]))
{
	?><div class="task-detail-error">
		<div class="task-detail-error-box">
			<div class="task-detail-error-icon"></div><br />
			<div class="task-detail-error-text"><?=HtmlFilter::encode($templateData["ERROR"]["MESSAGE"])?></div>
		</div>
	</div><?php

	return;
}
?>
<?=CJSCore::Init(array('tasks_util_query'), true)?>
<?php
$APPLICATION->SetPageProperty('BodyClass', 'task-card-page');
\Bitrix\Main\UI\Extension::load("ui.progressround");

Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/log_mobile.js');
Asset::getInstance()->addJs($templateFolder.'/../.default/script.js');

$guid = str_replace('-', '', $arParams["GUID"]);
$arResult["FORM_ID"] = "MOBILE_TASK_VIEW_{$guid}";
$arResult["GRID_ID"] = "MOBILE_TASK_VIEW_{$guid}";

$task = &$arResult["DATA"]["TASK"];
$can = $arResult["CAN"]["TASK"]["ACTION"];
$can["CHECKLIST_ADD_ITEMS"] = false;

$taskId = $task['ID'];
$statuses = CTaskItem::getStatusMap();
$status = Loc::getMessage("TASKS_STATUS_{$statuses[$task['REAL_STATUS']]}");

$task['~STATUS'] = $task['STATUS'];
$task['STATUS'] = (empty($status) ? Loc::getMessage('TASKS_STATUS_STATE_UNKNOWN') : $status);
$task['PRIORITY'] = ((int)$task["PRIORITY"] === CTasks::PRIORITY_HIGH ? CTasks::PRIORITY_HIGH : CTasks::PRIORITY_LOW);

$timerTask = (
	$task['ALLOW_TIME_TRACKING'] === 'Y'
		? CTaskTimerManager::getInstance($USER->getId())->getRunningTask(false)
		: []
);
$timerTask = (is_array($timerTask) ? $timerTask : ['TASK_ID' => null]);
if (
	array_key_exists('TASK_ID', $timerTask)
	&& (int)$timerTask['TASK_ID'] === (int)$taskId
)
{
	$task["TIME_SPENT_IN_LOGS"] += $timerTask['RUN_TIME'];
}

$taskLimitExceeded = $arResult['AUX_DATA']['TASK_LIMIT_EXCEEDED'];

ob_start();
$APPLICATION->IncludeComponent(
	'bitrix:tasks.widget.checklist.new',
	'mobile',
	[
		'ENTITY_ID' => $taskId,
		'ENTITY_TYPE' => 'TASK',
		'DATA' => $task['SE_CHECKLIST'],
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

$subTasksHtml = null;
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
			"FILTER" => array("PARENT_ID" => $taskId),
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
	$subTasksHtml = ob_get_clean();
}

ob_start();
$url = CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_USER_TASKS_EDIT"], [
	"USER_ID" => $USER->GetID(),
	"TASK_ID" => $taskId,
]);
?>

<form name="<?=$arResult["FORM_ID"]?>" id="<?=$arResult["FORM_ID"]?>" action="<?=$url?>" method="POST" style="margin-top: 60px">
	<?=bitrix_sessid_post();?>
	<?if (isset($arResult['FORM_GUID'])): ?><input type="hidden" name="FORM_GUID" value="<?=HtmlFilter::encode($arResult['FORM_GUID'])?>"><?php endif; ?>
	<input type="hidden" name="_JS_STEPPER_SUPPORTED" value="Y">
	<input type="hidden" name="DESCRIPTION_IN_BBCODE" value="<?=$task['DESCRIPTION_IN_BBCODE']; ?>" />
	<input type="hidden" name="back_url" value="<?=$url."&".http_build_query(array("save" => "Y", "sessid" => bitrix_sessid())) ?>" />
	<?if($can["EDIT"]):?>
		<input type="hidden" name="data[SE_AUDITOR][]" value=""/>
		<input type="hidden" name="data[SE_ACCOMPLICE][]" value=""/>
		<input type="hidden" name="data[PRIORITY]" value="<?=$task["PRIORITY"]?>"/>
	<?endif?>
	<div style="display: none;"><input type="text" name="AJAX_POST" value="Y" /></div><?//hack to not submit form?>

	<?php
	$favoriteButton = '';
	if ($can['FAVORITE.ADD'] || $can['FAVORITE.DELETE'])
	{
		$isActive = ($can['FAVORITE.DELETE'] ? 'active' : '');
		$favoriteButton = "<div id=\"favorites{$taskId}\" class=\"favorites {$isActive}\"></div>";
	}
	$title = htmlspecialcharsbx($task['TITLE']);
	$subtitle = GetMessage('MB_TASKS_BASE_SETTINGS_TITLE_TASK', ["#TASK_ID#" => $taskId]);
	$hiddenTitle = '';
	$titleLabel = "$title ({$subtitle})";
	if ($can['EDIT'])
	{
		$hiddenTitle = "<input id='title{$taskId}' type='hidden' data-bx-type='text' name='data[TITLE]' value='{$title}' />";
		$titleLabel = "<span id='title{$taskId}Container'>{$titleLabel}</span>";
	}
	$titleLabel = "<label>{$titleLabel}</label>";

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
					"fields" => array(
						[
							'class' => 'bx-tasks-title',
							'type' => 'label',
							'id' => 'data[TITLE]',
							'value' => $favoriteButton . $hiddenTitle . $titleLabel,
						],
						(
							empty($task['DESCRIPTION'])
								? null
								: [
									'type' => 'label',
									'id' => 'data[DESCRIPTION]',
									'value' => $task['DESCRIPTION'],
								]
						),
						[
							"type" => 'custom',
							"id" => 'checklist',
							"class" => '',
							"value" => $checklistHtml,
						],
						[
							'type' => 'label',
							'id' => 'data[STATUS]',
							'name' => GetMessage('MB_TASKS_TASK_SETTINGS_STATUS'),
							'value' => "<span id='bx-task-status-{$taskId}'>{$task['STATUS']}</span>",
						],
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
							"value" => ($can['EDIT.PLAN'] && empty($task['DEADLINE']) ? '0' : $task['DEADLINE']),
						),
						($task["ALLOW_TIME_TRACKING"] == "Y" ?
							array(
								"type" => "label",
								"class" => "mobile-grid-field-timetracking-block",
								"id" => "data[TIMETRACKING]",
								"name" => GetMessage("MB_TASKS_TASK_SETTINGS_TIMETRACKING"),
								"value" =>
									"<label for=\"bx-task-timetracking-".$taskId."\" class=\"mobile-grid-field-timetracking\">".
									"<input type=\"checkbox\" id=\"bx-task-timetracking-".$taskId."\" value=\"".intval($task["TIME_SPENT_IN_LOGS"])."\" ".($timerTask['TASK_ID'] == $taskId ? "checked" : "")." ".
									($timerTask['TASK_ID'] == $taskId || $can["DAYPLAN.TIMER.TOGGLE"] ? "" : " disabled")." />".
									"<span class=\"start\">".($task["TIME_SPENT_IN_LOGS"] > 0 ? GetMessage("TASKS_TT_CONTINUE") : GetMessage("TASKS_TT_START"))."</span>".
									"<span class=\"pause\">".GetMessage("TASKS_TT_PAUSE")."</span>".
									"<span class=\"timetracking\"><span class=\"spent\"  id=\"bx-task-timetracking-".$taskId."-value\">".sprintf('%02d:%02d:%02d', floor($task['TIME_SPENT_IN_LOGS']  / 3600), floor($task['TIME_SPENT_IN_LOGS'] / 60) % 60, $task['TIME_SPENT_IN_LOGS'] % 60)."</span>".
									(
									$task["TIME_ESTIMATE"] > 0 ?
										"<span class=\"divider\"> / </span><span class=\"estimated\">". sprintf('%02d:%02d', floor($task['TIME_ESTIMATE']  / 3600), floor($task['TIME_ESTIMATE'] / 60) % 60). "</span>" :
										""
									).
									"</span></label>"
							) : null),
						(
							($mark = [
								"NULL" => GetMessage('MB_TASKS_TASK_SETTINGS_MARK_NULL'),
								"P" => GetMessage('MB_TASKS_TASK_SETTINGS_MARK_P'),
								"N" => GetMessage('MB_TASKS_TASK_SETTINGS_MARK_N')
							])
							&& ($task["MARK"] = ($task["MARK"] == "N" || $task["MARK"] == "P" ? $task["MARK"] : "NULL"))
							&& $can["EDIT"]
							&& !$taskLimitExceeded
								? [
									"type" => "select",
									"class" => "bx-tasks-task-mark bx-tasks-task-mark-".$task["MARK"],
									"id" => "data[MARK]",
									"name" => GetMessage("MB_TASKS_TASK_SETTINGS_MARK"),
									"items" => $mark,
									"value" => $task["MARK"],
								]
								: [
									"type" => "label",
									"class" => "bx-tasks-task-mark bx-tasks-task-mark-".$task["MARK"],
									"id" => "data[MARK]",
									"name" => GetMessage("MB_TASKS_TASK_SETTINGS_MARK"),
									"value" => $mark[$task["MARK"]],
								]
						),
						array(
							"type" => (($can["EDIT.RESPONSIBLE"] || $can['EDIT']) ? "select-user" : "user"),
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
						(
							!empty($task["SE_AUDITOR"]) || ($can["EDIT"] && !$taskLimitExceeded)
							? [
								"type" => ($can["EDIT"] ? "select-users" : "users"),
								"id" => "data[SE_AUDITOR][]",
								"name" => GetMessage("MB_TASKS_TASK_SETTINGS_AUDITORS"),
								"items" => $task["SE_AUDITOR"],
								"value" => (!empty($task["SE_AUDITOR"]) ? $task["AUDITORS"] : '0'),
								"canAdd" => !$taskLimitExceeded,
							]
							: null
						),
						(
							!empty($task["SE_ACCOMPLICE"]) || ($can["EDIT"] && !$taskLimitExceeded)
							? [
								"type" => ($can["EDIT"] ? "select-users" : "users"),
								"id" => "data[SE_ACCOMPLICE][]",
								"name" => GetMessage("MB_TASKS_TASK_SETTINGS_ACCOMPLICES"),
								"items" => $task["SE_ACCOMPLICE"],
								"value" => (!empty($task["SE_ACCOMPLICE"]) ? $task["ACCOMPLICES"] : '0'),
								"canAdd" => !$taskLimitExceeded,
							]
							: null
						),
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
						(
							($task["GROUP_ID"] > 0 && is_array($templateData["GROUP"])) || $can["EDIT"]
								? [
									"type" => ($can["EDIT"] ? "select-group" : "group"),
									"id" => "data[SE_PROJECT][ID]",
									"class" => "mobile-grid-field-taskgroups",
									"name" => GetMessage("MB_TASKS_TASK_SETTINGS_GROUP_ID"),
									"item" => $templateData['GROUP'],
									"value" => ($task["GROUP_ID"] > 0 ? $task["GROUP_ID"] : '0'),
									'useLink' => 'Y',
								]
								: null
						),
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
									'<span onclick="'.CMobileHelper::getTaskLink($task['SE_PARENTTASK']['ID']).'">'
										.htmlspecialcharsbx($task["SE_PARENTTASK"]["TITLE"])
									.'</span>'
							) : null),
						(
							$subTasksHtml !== null ? [
								"type" => "custom",
								"id" => "SUBTASKS",
								"name" => GetMessage("MB_TASKS_TASK_SETTINGS_SUBTASKS"),
								"class" => "mobile-grid-field-subtasks",
								"value" => $subTasksHtml,
							] : null
						)
					)
				)
			),
			'SHOW_FORM_TAG' => false,
			'SKIP_LOADING_SCREEN_HIDING' => true,
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
	?>
</form>

<?$taskHtml = ob_get_clean();?>

<?php
ob_start();
$APPLICATION->IncludeComponent(
	"bitrix:forum.comments",
	"",
	array(
		"FORUM_ID" => $task['FORUM_ID'],
		"ENTITY_TYPE" => "TK",
		"ENTITY_ID" => $taskId,
		"ENTITY_XML_ID" => "TASK_".$taskId,
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
$taskCommentsHtml = ob_get_clean();

$component->arResult["HTML"] = [
	"body" => $taskHtml,
	"comments" => $taskCommentsHtml,
];
?>

<div id="task-block"><?=$taskHtml?>
	<?php
	$voteId = HtmlFilter::encode("TASK".'_'.$taskId.'-'.(time() + random_int(0, 1000)));
	$userReaction = (
		isset($arResult['Post']['ID'])
			? $arResult['RATING'][$arResult['Post']['ID']]['USER_REACTION']
			: []
	);
	$emotion = (!empty($userReaction)? mb_strtoupper($userReaction) : 'LIKE');
	$userHasVoted = (isset($templateData['RATING']['USER_HAS_VOTED']) && $templateData['RATING']['USER_HAS_VOTED'] === 'Y');
	$totalVotes = (int)($templateData['RATING']['TOTAL_VOTES'] ?? null);
	$totalPositiveVotes = (int)($templateData['RATING']['TOTAL_POSITIVE_VOTES'] ?? null);
	$totalNegativeVotes = (int)($templateData['RATING']['TOTAL_NEGATIVE_VOTES'] ?? null);
	?>
	<div class="post-item-inform-wrap-tree" id="rating-footer-wrap">
		<div class="feed-post-emoji-top-panel-outer">
			<div class="feed-post-emoji-top-panel-box <?=($totalPositiveVotes > 0 ? 'feed-post-emoji-top-panel-container-active' : '')?>"
				 id="feed-post-emoji-top-panel-container-<?=$voteId?>">
				<?php
				$APPLICATION->IncludeComponent(
					"bitrix:rating.vote",
					"like_react",
					array(
						"MOBILE" => "Y",
						"ENTITY_TYPE_ID" => "TASK",
						"ENTITY_ID" => $taskId,
						"OWNER_ID" => $task["CREATED_BY"],
						"USER_VOTE" => ($templateData["RATING"]["USER_VOTE"] ?? null),
						"USER_REACTION" => ($arResult["RATING"]["USER_REACTION"] ?? null),
						"USER_HAS_VOTED" => ($templateData["RATING"]["USER_HAS_VOTED"] ?? null),
						"TOTAL_VOTES" => $totalVotes,
						"TOTAL_POSITIVE_VOTES" => $totalPositiveVotes,
						"TOTAL_NEGATIVE_VOTES" => $totalNegativeVotes,
						"TOTAL_VALUE" => ($templateData["RATING"]["TOTAL_VALUE"] ?? null),
						"REACTIONS_LIST" => ($arResult["RATING"]["REACTIONS_LIST"] ?? null),
						"PATH_TO_USER_PROFILE" => ($arParams["~PATH_TO_USER"] ?? null),
						'TOP_DATA' => (!empty($arResult['TOP_RATING_DATA']) ? $arResult['TOP_RATING_DATA'] : false),
						'VOTE_ID' => $voteId,
						'TYPE' => 'POST'
					),
					$component->__parent,
					array("HIDE_ICONS" => "Y")
				);
				?>
			</div>
		</div>
	</div>
	<div id="post_inform_wrap_two" class="post-item-inform-wrap">
		<span class="post-item-informers bx-ilike-block" id="rating_block_<?=$taskId?>"
			  data-counter="<?=$totalVotes?>">
			<span class="post-item-informer-like feed-inform-ilike" id="bx-ilike-button-<?=$voteId?>"
				  data-rating-vote-id="<?=$voteId?>">
				<span class="bx-ilike-left-wrap<?=($userHasVoted ? ' bx-you-like-button' : '')?>">
					<span class="bx-ilike-text"><?=\CRatingsComponentsMain::getRatingLikeMessage($emotion)?></span>
				</span>
			</span>
		</span>
		<?/*>
		<div class="post-item-informers post-item-inform-comments" onclick="BX.onCustomEvent(window, 'OnUCUserReply', ['TASK_<?=$taskId?>']);">
			<div class="post-item-inform-left"><?=Loc::getMessage("MB_TASKS_TASK_COMMENT")?></div>
		</div>
		*/?>
	</div>
</div>

<?php
$APPLICATION->IncludeComponent(
	"bitrix:tasks.widget.result",
	"mobile",
	[
		'TASK_ID' => $taskId,
	],
	$component,
);
?>

<div id="task-comments-block"><?=$taskCommentsHtml?></div>

<input class="task-detail-left-bottom-options-button" id="options_button" type="button" />
<input class="task-detail-right-bottom-down-button" id="down_button" type="button" />
<div class="task-detail-top-button-up-button" id="up_button">
	<div class="task-detail-top-button-up-button-inner">
		<div class="task-detail-top-button-up-button-arrow"></div>
		<div class="task-detail-top-button-up-button-text"><?=Loc::getMessage('MB_TASKS_TASK_DETAIL_UP_BUTTON_TEXT')?></div>
	</div>
</div>
<div class="task-detail-no-comments" id="commentsStub">
	<div class="task-detail-no-comments-inner">
		<div class="task-detail-no-comments-top-image-container">
			<div class="task-detail-no-comments-top-image"></div>
		</div>
		<div class="task-detail-no-comments-text">
			<?=Loc::getMessage('MB_TASKS_TASK_DETAIL_NO_COMMENTS_STUB_TEXT')?>
		</div>
		<div class="task-detail-no-comments-arrow-container">
			<div class="task-detail-no-comments-arrow"></div>
		</div>
	</div>
</div>

<script type="text/javascript">
	BX.message({
		PAGE_TITLE: '<?=GetMessageJS("MB_TASKS_GENERAL_TITLE")?>',
		MB_TASKS_TASK_PLACEHOLDER: '<span class="placeholder"><?=GetMessageJS("MB_TASKS_TASK_PLACEHOLDER")?></span>',
		MB_TASKS_TASK_DETAIL_BTN_EDIT: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_EDIT')?>',
		MB_TASKS_TASK_DETAIL_BTN_REMOVE: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_REMOVE')?>',
		MB_TASKS_TASK_DETAIL_BTN_ACCEPT_TASK: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_ACCEPT_TASK')?>',
		MB_TASKS_TASK_DETAIL_BTN_START_TASK: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_START_TASK')?>',
		MB_TASKS_TASK_DETAIL_BTN_DECLINE_TASK: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_DECLINE_TASK')?>',
		MB_TASKS_TASK_DETAIL_BTN_RENEW_TASK: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_RENEW_TASK')?>',
		MB_TASKS_TASK_DETAIL_BTN_CLOSE_TASK: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_CLOSE_TASK')?>',
		MB_TASKS_TASK_DETAIL_BTN_PAUSE_TASK: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_PAUSE_TASK')?>',
		MB_TASKS_TASK_DETAIL_BTN_APPROVE_TASK_MSGVER_1:  '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_APPROVE_TASK_MSGVER_1')?>',
		MB_TASKS_TASK_DETAIL_BTN_REDO_TASK: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_REDO_TASK')?>',
		MB_TASKS_TASK_DETAIL_BTN_DELEGATE_TASK: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_DELEGATE_TASK')?>',
		MB_TASKS_TASK_DETAIL_BTN_ADD_FAVORITE_TASK: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_ADD_FAVORITE_TASK')?>',
		MB_TASKS_TASK_DETAIL_BTN_DELETE_FAVORITE_TASK: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_DELETE_FAVORITE_TASK')?>',
		MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED')?>',
		MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED_OR_NOT_ENOUGH_RIGHTS: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED_OR_NOT_ENOUGH_RIGHTS')?>',
		MB_TASKS_TASK_DETAIL_NOTIFICATION_EXPIRED: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_NOTIFICATION_EXPIRED')?>',
		MB_TASKS_TASK_DETAIL_NOTIFICATION_STATE_SUPPOSEDLY_COMPLETED: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_NOTIFICATION_STATE_SUPPOSEDLY_COMPLETED')?>',
		MB_TASKS_TASK_DETAIL_USER_SELECTOR_BTN_CANCEL: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_USER_SELECTOR_BTN_CANCEL')?>',
		MB_TASKS_TASK_DETAIL_CONFIRM_REMOVE: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_CONFIRM_REMOVE')?>',
		MB_TASKS_TASK_REMOVE_CONFIRM_TITLE: '<?=GetMessageJS('MB_TASKS_TASK_REMOVE_CONFIRM_TITLE')?>',
		MB_TASKS_TASK_DETAIL_TASK_ADD: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_TASK_ADD')?>',
		MB_TASKS_TASK_DETAIL_TASK_ADD_SUBTASK: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_TASK_ADD_SUBTASK')?>',
		MB_TASKS_TASK_DETAIL_NO_COMMENTS_STUB_TEXT: '<?=GetMessageJS('MB_TASKS_TASK_DETAIL_NO_COMMENTS_STUB_TEXT')?>',
		TASKS_LIST_GROUP_ACTION_ERROR1: '<?=GetMessageJS("TASKS_LIST_GROUP_ACTION_ERROR1")?>',
		// For task.task.edit/script.js
		TASKS_PRIORITY_0: '<?=GetMessageJS("TASKS_PRIORITY_0")?>',
		TASKS_PRIORITY_2: '<?=GetMessageJS("TASKS_PRIORITY_2")?>',
		MB_TASKS_TASK_ERROR1: '<?=GetMessageJS("MB_TASKS_TASK_ERROR1")?>',
		MB_TASKS_TASK_ERROR2: '<?=GetMessageJS("MB_TASKS_TASK_ERROR2")?>',
		MB_TASKS_TASK_DELETE: '<?=GetMessageJS("MB_TASKS_TASK_DELETE")?>',
		MB_TASKS_TASK_EDIT: '<?=GetMessageJS("MB_TASKS_TASK_EDIT")?>',
		MB_TASKS_TASK_CHECK: '<?=GetMessageJS("MB_TASKS_TASK_CHECK")?>',
		MB_TASKS_TASK_UNCHECK: '<?=GetMessageJS("MB_TASKS_TASK_UNCHECK")?>',
		MB_TASKS_TASK_CHECKLIST_PLACEHOLDER: '<?=GetMessageJS("MB_TASKS_TASK_CHECKLIST_PLACEHOLDER")?>',
		TASKS_STATUS_METASTATE_EXPIRED: '<?=GetMessageJS("TASKS_STATUS_METASTATE_EXPIRED")?>',
		TASKS_STATUS_STATE_NEW: '<?=GetMessageJS("TASKS_STATUS_STATE_NEW")?>',
		TASKS_STATUS_STATE_PENDING: '<?=GetMessageJS("TASKS_STATUS_STATE_PENDING")?>',
		TASKS_STATUS_STATE_IN_PROGRESS: '<?=GetMessageJS("TASKS_STATUS_STATE_IN_PROGRESS")?>',
		TASKS_STATUS_STATE_SUPPOSEDLY_COMPLETED: '<?=GetMessageJS("TASKS_STATUS_STATE_SUPPOSEDLY_COMPLETED")?>',
		TASKS_STATUS_STATE_COMPLETED: '<?=GetMessageJS("TASKS_STATUS_STATE_COMPLETED")?>',
		TASKS_STATUS_STATE_DEFERRED: '<?=GetMessageJS("TASKS_STATUS_STATE_DEFERRED")?>',
		TASKS_STATUS_STATE_DECLINED: '<?=GetMessageJS("TASKS_STATUS_STATE_DECLINED")?>',
		TASKS_STATUS_STATE_UNKNOWN: '<?=GetMessageJS("TASKS_STATUS_STATE_UNKNOWN")?>',
		TASKS_TT_ERROR1_TITLE: '<?=GetMessageJS("TASKS_TT_ERROR1_TITLE")?>',
		TASKS_TT_ERROR1_DESC: '<?=GetMessageJS("TASKS_TT_ERROR1_DESC")?>',
		TASKS_TT_CONTINUE: '<?=GetMessageJS("TASKS_TT_CONTINUE")?>',
		TASKS_TT_CANCEL: '<?=GetMessageJS("TASKS_TT_CANCEL")?>'
	});

	BX.ready(function()
	{
		new BX.Mobile.Tasks.detail({
			taskData: <?= CUtil::PhpToJSObject([
				'ID' => $taskId,
				'TITLE' => $task['TITLE'],
				'DESCRIPTION' => $task['DESCRIPTION'],
				'RESPONSIBLE_ID' => $task['RESPONSIBLE_ID'],
				'CREATED_BY' => $task['CREATED_BY'],
				'PRIORITY' => $task['PRIORITY'],
				'STATUS' => $task['STATUS'],
				'REAL_STATUS' => $task['REAL_STATUS'],
				'GROUP_ID' => $task['GROUP_ID'],
				'DEADLINE' => $task['DEADLINE'],
				'ACCOMPLICES' => $task['ACCOMPLICES'],
				'AUDITORS' => $task['AUDITORS'],
				'CHECKLIST' => $task['CHECKLIST'],
				'ACTION' => $task['ACTION'],
				'LOG_ID' => (isset($templateData['LOG_ID']) ? (int)$templateData['LOG_ID'] : 0),
			]) ?>,
			formId: '<?= CUtil::JSEscape($arResult['FORM_ID']) ?>',
			currentTs: <?= (isset($templateData['CURRENT_TS']) ? (int)$templateData['CURRENT_TS'] : 0) ?>,
			guid: '<?= CUtil::JSEscape($arParams['GUID']) ?>',
			statuses: <?= CUtil::PhpToJSObject($statuses) ?>,
		});
	});

	if (typeof BX.MSL != 'undefined')
	{
		BX.MSL.viewImageBind('tasks-detail-card-container-over', {tag: 'IMG', attr: 'data-bx-image'});
	}
</script>