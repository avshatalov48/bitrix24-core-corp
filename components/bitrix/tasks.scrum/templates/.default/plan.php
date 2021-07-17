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
/** @var string $filterId */
/** @var Filter $filterInstance */

use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Helper\Filter;

require_once __DIR__.'/header.php';

$pathToTask = str_replace('#action#', 'view', $arParams['PATH_TO_GROUP_TASKS_TASK']);
$pathToTask = str_replace('#group_id#', $arParams['GROUP_ID'], $pathToTask);
?>

<div id="tasks-scrum-container" class='tasks-scrum-container'></div>

<script>
	BX.ready(function()
	{
		BX.message(<?= Json::encode($messages) ?>);
		BX.Tasks.Scrum.Entry = new BX.Tasks.Scrum.Entry({
			viewName: 'plan',
			signedParameters: '<?= $this->getComponent()->getSignedParameters() ?>',
			debugMode: '<?= $arResult['debugMode'] ?>',
			isOwnerCurrentUser: '<?= ($arResult['isOwnerCurrentUser'] ? 'Y' : 'N') ?>',
			userId: '<?= (int)$arParams['USER_ID'] ?>',
			groupId: '<?= (int)$arParams['GROUP_ID'] ?>',
			defaultSprintDuration: '<?=(int) $arResult['defaultSprintDuration'] ?>',
			pageNumberToCompletedSprints: '1',
			pathToTask: '<?= \CUtil::jSEscape($pathToTask) ?>',
			tags: <?= Json::encode($arResult['tags']) ?>,
			backlog: <?= Json::encode($arResult['backlog']) ?>,
			sprints: <?= Json::encode($arResult['sprints']) ?>,
			views: <?= Json::encode($arResult['views']) ?>,
			activeSprintId: '<?= $arResult['activeSprintId'] ?>',
			filterId: '<?= $filterId ?>',
			defaultResponsible: <?= Json::encode($arResult['defaultResponsible']) ?>,
			counters: <?= $arResult['counters'] ? Json::encode($arResult['counters']) : 'null' ?>,
		});
		BX.Tasks.Scrum.Entry.renderTabsTo(document.getElementById('tasks-scrum-switcher'));
		BX.Tasks.Scrum.Entry.renderCountersTo(document.getElementById('tasks-scrum-counters-container'));
		BX.Tasks.Scrum.Entry.renderButtonsTo(document.getElementById('tasks-scrum-buttons-container'));
		BX.Tasks.Scrum.Entry.renderTo(document.getElementById('tasks-scrum-container'));
	});
</script>
