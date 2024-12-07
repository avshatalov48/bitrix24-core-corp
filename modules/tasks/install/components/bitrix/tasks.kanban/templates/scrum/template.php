<?php

/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
/** @var CBitrixComponent $component */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Helper\RestrictionUrl;
use Bitrix\Tasks\Scrum\Service\TaskService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** intranet-settings-support */
if (($arResult['IS_TOOL_AVAILABLE'] ?? null) === false)
{
	$APPLICATION->IncludeComponent("bitrix:tasks.error", "limit", [
		'LIMIT_CODE' => RestrictionUrl::TASK_LIMIT_OFF_SLIDER_URL,
		'SOURCE' => 'kanban_report',
	]);

	return;
}

if (!empty($arResult['ERRORS']))
{
	ShowError(implode("\n", $arResult['ERRORS']));
	return;
}

$taskService = new TaskService($arParams['USER_ID']);

$filterInstance = $taskService->getFilterInstance(
	$arParams['GROUP_ID'],
	$arParams['IS_COMPLETED_SPRINT'] === 'Y' ? 'complete' : 'active'
);

$messages = Loc::loadLanguageFile(__FILE__);

Extension::load([
	'tasks.kanban-sort',
	'task_kanban',
	'ui.notification',
	'ui.dialogs.messagebox',
	'ui.counter',
	'ui.label',
	'ui.fonts.opensans',
	'pull.queuemanager',
]);

$isBitrix24Template = (SITE_TEMPLATE_ID === 'bitrix24');

$data = $arResult['DATA'];
?>

<div id="scrum_kanban">
	<div class="tasks-scrum-kanban-header-target-observer"></div>
</div>

<script>

	BX.message(<?=Json::encode($messages)?>);

	BX.Tasks.Scrum.Kanban = new BX.Tasks.Scrum.KanbanManager({
		groupId: <?= (int) $arParams['GROUP_ID'] ?>,
		signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
		filterId: '<?=$filterInstance->getId()?>',
		defaultPresetId:'<?=$arResult['DEFAULT_PRESET_KEY']?>',
		ajaxComponentPath: '<?=$this->getComponent()->getPath()?>/ajax.php',
		ajaxComponentParams: {
			USER_ID: <?=(int) $arParams['USER_ID']?>,
			GROUP_ID: <?=(int) $arParams['GROUP_ID']?>,
			GROUP_ID_FORCED: <?=(int) $arParams['GROUP_ID_FORCED']?>,
			SPRINT_ID: <?=($arParams['SPRINT_ID'] > 0 ? (int)$arParams['SPRINT_ID'] : -1)?>,
			IS_COMPLETED_SPRINT: '<?=(
				(isset($arParams['IS_COMPLETED_SPRINT']) && $arParams['IS_COMPLETED_SPRINT'] == 'Y') ? 'Y' : 'N'
			)?>',
			PERSONAL: '<?=$arParams['PERSONAL']?>',
			TIMELINE_MODE: '<?=$arParams['TIMELINE_MODE']?>',
		},
		siteTemplateId: <?=(SITE_TEMPLATE_ID === 'bitrix24' ? '"transparent"' : 'null')?>,
		sprintSelected: <?=($arParams['SPRINT_SELECTED'] == 'Y' && $arParams['SPRINT_ID'] ? 'true' : 'false')?>,
		isActiveSprint: <?=($arParams['IS_ACTIVE_SPRINT'] == 'Y' ? 'true' : 'false')?>,
		parentTasks: <?=CUtil::PhpToJSObject($data['parentTasks'], false, false, true)?>
	});

	BX.ready(function() {
		BX.Tasks.KanbanAjaxComponent.Parameters = new BX.Tasks.KanbanAjaxComponent({
			ajaxComponentPath: '<?=$this->getComponent()->getPath()?>/ajax.php',
			ajaxComponentParams: {
				USER_ID: <?=(int)$arParams['USER_ID']?>,
				GROUP_ID: <?=(int)$arParams['GROUP_ID']?>,
				GROUP_ID_FORCED: <?=(int)$arParams['GROUP_ID_FORCED']?>,
				SPRINT_ID: <?=($arParams['SPRINT_ID'] > 0 ? (int)$arParams['SPRINT_ID'] : -1)?>,
				IS_COMPLETED_SPRINT: '<?=(
				(isset($arParams['IS_COMPLETED_SPRINT']) && $arParams['IS_COMPLETED_SPRINT'] == 'Y') ? 'Y' : 'N'
				)?>',
				PERSONAL: '<?=$arParams['PERSONAL']?>',
				TIMELINE_MODE: '<?=$arParams['TIMELINE_MODE']?>',
			},
		});
	});

	BX.ready(function() {
		var kanbanParams = {
			columns: <?=CUtil::PhpToJSObject($data['columns'], false, false, true)?>,
			items: <?=CUtil::PhpToJSObject($data['items'], false, false, true)?>,
			pathToTask: '<?= CUtil::JSEscape(str_replace('#action#', 'view', $arParams['~PATH_TO_TASKS_TASK']))?>',
			pathToTaskCreate: '<?= CUtil::JSEscape(str_replace('#action#', 'edit', $arParams['~PATH_TO_TASKS_TASK']))?>',
			pathToUser: '<?= CUtil::JSEscape($arParams['~PATH_TO_USER_PROFILE'])?>',
			addItemInSlider: <?= $arResult['MANDATORY_EXISTS'] ? 'true' : 'false'?>,
			newTaskOrder: '<?= $arResult['NEW_TASKS_ORDER']?>',
			setClientDate: <?= $arResult['NEED_SET_CLIENT_DATE'] ? 'true' : 'false'?>,
			admins: <?= CUtil::PhpToJSObject(array_values($arResult['ADMINS']))?>,
			ownerId: <?= (int) $arParams['USER_ID'] ?>,
			groupId: <?= (int) $arParams['GROUP_ID'] ?>
		};

		BX.Tasks.Scrum.Kanban.drawKanban(document.getElementById('scrum_kanban'), kanbanParams);
	});

</script>