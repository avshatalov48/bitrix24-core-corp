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

use Bitrix\Main\Web\Json;

require_once __DIR__.'/header.php';

$pathToTask = str_replace('#action#', 'view', $arParams['PATH_TO_GROUP_TASKS_TASK']);
$pathToTask = str_replace('#group_id#', $arParams['GROUP_ID'], $pathToTask);
?>

<div id="tasks-scrum-container" class='tasks-scrum-container'></div>

<script>
	BX.ready(function() {
		BX.message(<?=Json::encode($messages)?>);
		BX.Tasks.Scrum.ScrumManager = new BX.Tasks.Scrum.Scrum({
			signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
			debugMode: '<?=$arResult['debugMode']?>',
			defaultSprintDuration: '<?=(int) $arResult['defaultSprintDuration']?>',
			pathToTask: '<?=\CUtil::jSEscape($pathToTask)?>',
			tags: <?=Json::encode($arResult['tags'])?>,
			backlog: <?=Json::encode($arResult['backlog'])?>,
			sprints: <?=Json::encode($arResult['sprints'])?>,
			tabs: <?=Json::encode($arResult['tabs'])?>,
			activeTab: 'plan',
			activeSprintId: '<?=$arResult['activeSprintId']?>',
			filterId: '<?=$filterId?>'
		});
		BX.Tasks.Scrum.ScrumManager.renderTo(document.getElementById('tasks-scrum-container'));
	});
</script>