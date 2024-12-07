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

$isKanban = true;

if ($arParams['CONTEXT'] === 'group')
{
	require_once __DIR__.'/header.php';
}
else
{
	require_once __DIR__.'/external_header.php';
}

$APPLICATION->includeComponent(
	'bitrix:tasks.kanban',
	'scrum',
	[
		'PERSONAL' => 'N',
		'TIMELINE_MODE' => 'N',
		'KANBAN_SHOW_VIEW_MODE'=>'N',
		'SPRINT_ID' => $arResult['activeSprintId'] ?? null,
		'GROUP_ID' => $arParams['GROUP_ID'] ?? null,
		'ITEMS_COUNT' => '50',
		'PAGE_VAR' => $arParams['PAGE_VAR'] ?? null,
		'GROUP_VAR' => $arParams['GROUP_VAR'] ?? null,
		'VIEW_VAR' => $arParams['VIEW_VAR'] ?? null,
		'TASK_VAR' => $arParams['TASK_VAR'] ?? null,
		'ACTION_VAR' => ($arParams['TASK_VAR']['ACTION_VAR'] ?? ''),
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'] ?? null,
		'PATH_TO_GROUP_TASKS' => $arParams['PATH_TO_GROUP_TASKS'] ?? null,
		'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'] ?? null,
		'PATH_TO_GROUP_TASKS_VIEW' => $arParams['PATH_TO_GROUP_TASKS_VIEW'] ?? null,
		'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'] ?? null,
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER'] ?? null,
		'PATH_TO_GROUP' => $arParams['PATH_TO_GROUP'] ?? null,
		'PATH_TO_MESSAGES_CHAT' => $arParams['PATH_TO_MESSAGES_CHAT'] ?? null,
		'PATH_TO_VIDEO_CALL' => $arParams['PATH_TO_VIDEO_CALL'] ?? null,
		'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT'] ?? null,
		'SET_NAV_CHAIN' => $arParams['SET_NAV_CHAIN'] ?? null,
		'FORUM_ID' => $arParams['TASK_FORUM_ID'] ?? null,
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'] ?? null,
		'SHOW_LOGIN' => $arParams['SHOW_LOGIN'] ?? null,
		'DATE_TIME_FORMAT' => $arParams['DATE_TIME_FORMAT'] ?? null,
		'SHOW_YEAR' => $arParams['SHOW_YEAR'] ?? null,
		'CACHE_TYPE' => $arParams['CACHE_TYPE'] ?? null,
		'CACHE_TIME' => $arParams['CACHE_TIME'] ?? null,
		'USE_THUMBNAIL_LIST' => 'N',
		'INLINE' => 'Y',
		'HIDE_OWNER_IN_TITLE' => $arParams['HIDE_OWNER_IN_TITLE'] ?? null,
		'TASKS_ALWAYS_EXPANDED' => 'Y'
	],
	$component,
	['HIDE_ICONS' => 'Y']
);

$pathToTask = str_replace('#action#', 'view', $arParams['PATH_TO_GROUP_TASKS_TASK']);
$pathToTask = str_replace('#group_id#', $arParams['GROUP_ID'], $pathToTask);

?>

<script>
	BX.ready(function()
	{
		BX.message(<?= Json::encode($messages) ?>);
		BX.Tasks.Scrum.Entry = new BX.Tasks.Scrum.Entry({
			viewName: 'activeSprint',
			signedParameters: '<?= $this->getComponent()->getSignedParameters() ?>',
			debugMode: '<?= $arResult['debugMode'] ?>',
			isOwnerCurrentUser: '<?= ($arResult['isOwnerCurrentUser'] ? 'Y' : 'N') ?>',
			userId: '<?= (int)$arParams['USER_ID'] ?>',
			groupId: '<?= (int)$arParams['GROUP_ID'] ?>',
			views: <?= Json::encode($arResult['views']) ?>,
			culture: <?= Json::encode($arResult['culture']) ?>,
			activeSprintId: '<?= $arResult['activeSprintId'] ?>',
			pathToTask: '<?= \CUtil::jSEscape($pathToTask) ?>',
			taskLimitExceeded: '<?= ($arResult['taskLimitExceeded'] ? 'Y' : 'N') ?>',
			canUseAutomation: '<?= ($arResult['canUseAutomation'] ? 'Y' : 'N') ?>',
			isAutomationEnabled: '<?= ($arResult['isAutomationEnabled'] ? 'Y' : 'N') ?>',
			canCompleteSprint: '<?= ($arResult['canCompleteSprint'] ? 'Y' : 'N') ?>'
		});

		<?php if ($arParams['CONTEXT'] === 'group'): ?>
			BX.Tasks.Scrum.Entry.renderTabsTo(document.getElementById('tasks-scrum-switcher'));
			BX.Tasks.Scrum.Entry.renderSprintStatsTo(document.getElementById('tasks-scrum-sprint-stats'));
			BX.Tasks.Scrum.Entry.renderRightElementsTo(document.getElementById('tasks-scrum-right-container'));
		<?php endif; ?>
	});
</script>