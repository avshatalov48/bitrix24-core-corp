<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$containerID = 'tasks_group_selector';
if (isset($arResult['GROUPS'][$arParams['GROUP_ID']]))
{
	$currentGroup = $arResult['GROUPS'][$arParams['GROUP_ID']];
	unset($arResult['GROUPS'][$arParams['GROUP_ID']]);
}
else
{
	$currentGroup = array(
		'id'   => 'wo',
		'text' => \GetMessage('TASKS_BTN_GROUP_WO')
	);
}
?>

<div class="pagetitle-container pagetitle-flexible-space">
	<div id="<?=htmlspecialcharsbx($containerID)?>"
		 class="tasks-interface-toolbar-button-container">
		<div class="webform-small-button webform-small-button-transparent webform-small-button-dropdown">
			<span class="webform-small-button-text"
				  id="<?=htmlspecialcharsbx($containerID)?>_text">
					<?=htmlspecialcharsbx($currentGroup['text'])?>
				</span>
			<span class="webform-small-button-icon"></span>
		</div>
	</div>
</div>

<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.TasksGroupsSelectorInit({
				groupId: <?= intval($arParams['GROUP_ID'])?>,
				selectorId: "<?= \CUtil::JSEscape($containerID)?>",
				buttonAddId: "tasks-buttonAdd",
				pathTaskAdd: "<?= \CUtil::JSEscape(\CComponentEngine::makePathFromTemplate(
					$arParams['MENU_GROUP_ID'] > 0
						? $arParams['PATH_TO_GROUP_TASKS_TASK']
						: $arParams['PATH_TO_USER_TASKS_TASK'],
					array(
						'action'   => 'edit',
						'task_id'  => 0,
						'user_id'  => $arResult['USER_ID'],
						'group_id' => $arParams['MENU_GROUP_ID']
					)
				))?>",
				groups: <?= \CUtil::PhpToJSObject(array_values($arResult['GROUPS']))?>,
				currentGroup: <?= \CUtil::PhpToJSObject($currentGroup)?>,
				groupLimit: <?= intval($arParams['GROUP_SELECTOR_LIMIT'])?>,
				messages: {
					TASKS_BTN_GROUP_WO: "<?= \GetMessageJS('TASKS_BTN_GROUP_WO')?>",
					TASKS_BTN_GROUP_SELECT: "<?= \GetMessageJS('TASKS_BTN_GROUP_SELECT')?>"
				},
				offsetLeft: <?= $arParams['PROJECT_VIEW'] === 'Y' ? 19 : 0 ?>
			});
		}
	);
</script>