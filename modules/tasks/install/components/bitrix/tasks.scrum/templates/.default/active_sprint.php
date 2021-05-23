<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var $APPLICATION \CMain */
/** @var array $arResult */
/** @var array $arParams */
/** @var \CBitrixComponent $component */
/** @var array $messages */

use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;

$isKanban = true;

require_once __DIR__.'/header.php';

Extension::load('tasks.scrum.dod');

$APPLICATION->IncludeComponent(
	'bitrix:tasks.kanban',
	'scrum',
	[
		'PERSONAL' => 'N',
		'TIMELINE_MODE' => 'N',
		'KANBAN_SHOW_VIEW_MODE'=>'N',
		'SPRINT_ID' => $arResult['activeSprintId'],
		'GROUP_ID' => $arParams['GROUP_ID'],
		'ITEMS_COUNT' => '50',
		'PAGE_VAR' => $arParams['PAGE_VAR'],
		'GROUP_VAR' => $arParams['GROUP_VAR'],
		'VIEW_VAR' => $arParams['VIEW_VAR'],
		'TASK_VAR' => $arParams['TASK_VAR'],
		'ACTION_VAR' => $arParams['TASK_VAR']['ACTION_VAR'],
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
		'PATH_TO_GROUP_TASKS' => $arParams['PATH_TO_GROUP_TASKS'],
		'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'],
		'PATH_TO_GROUP_TASKS_VIEW' => $arParams['PATH_TO_GROUP_TASKS_VIEW'],
		'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'],
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER'],
		'PATH_TO_GROUP' => $arParams['PATH_TO_GROUP'],
		'PATH_TO_MESSAGES_CHAT' => $arParams['PATH_TO_MESSAGES_CHAT'],
		'PATH_TO_VIDEO_CALL' => $arParams['PATH_TO_VIDEO_CALL'],
		'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT'],
		'SET_NAV_CHAIN' => $arParams['SET_NAV_CHAIN'],
		'FORUM_ID' => $arParams['TASK_FORUM_ID'],
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
		'SHOW_LOGIN' => $arParams['SHOW_LOGIN'],
		'DATE_TIME_FORMAT' => $arParams['DATE_TIME_FORMAT'],
		'SHOW_YEAR' => $arParams['SHOW_YEAR'],
		'CACHE_TYPE' => $arParams['CACHE_TYPE'],
		'CACHE_TIME' => $arParams['CACHE_TIME'],
		'USE_THUMBNAIL_LIST' => 'N',
		'INLINE' => 'Y',
		'HIDE_OWNER_IN_TITLE' => $arParams['HIDE_OWNER_IN_TITLE'],
		'TASKS_ALWAYS_EXPANDED' => 'Y'
	],
	$component,
	['HIDE_ICONS' => 'Y']
);

$pathToTask = str_replace('#action#', 'view', $arParams['PATH_TO_GROUP_TASKS_TASK']);
$pathToTask = str_replace('#group_id#', $arParams['GROUP_ID'], $pathToTask);

?>

<script>
	BX.ready(function() {
		BX.message(<?= Json::encode($messages) ?>);
		BX.Tasks.Scrum.Entry = new BX.Tasks.Scrum.Entry({
			viewName: 'activeSprint',
			signedParameters: '<?= $this->getComponent()->getSignedParameters() ?>',
			debugMode: '<?= $arResult['debugMode'] ?>',
			isOwnerCurrentUser: '<?= ($arResult['isOwnerCurrentUser'] ? 'Y' : 'N') ?>',
			userId: '<?= (int)$arParams['USER_ID'] ?>',
			groupId: '<?= (int)$arParams['GROUP_ID'] ?>',
			views: <?= Json::encode($arResult['views']) ?>,
			activeSprintId: '<?= $arResult['activeSprintId'] ?>',
			activeSprint: <?= Json::encode($arResult['activeSprintData']) ?>,
			sprints: <?= Json::encode($arResult['sprints']) ?>,
			pathToTask: '<?= \CUtil::jSEscape($pathToTask) ?>'
		});
		BX.Tasks.Scrum.Entry.renderCountersTo(document.getElementById('tasks-scrum-counters-container'));
	});
</script>
