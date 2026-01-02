<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;

$containerID = 'tasks_sprint_selector';

$currentSprintName = htmlspecialcharsbx($arResult['SPRINT']['START_TIME'])
	. ' - ' . htmlspecialcharsbx($arResult['SPRINT']['FINISH_TIME'])
;

$tasksSprintSelector = new Buttons\Button([
	'color' => Buttons\Color::LIGHT_BORDER,
	'text' => $currentSprintName,
	'dropdown' => true,
]);

$tasksSprintSelector->addAttribute('id', $containerID);

Toolbar::addButton($tasksSprintSelector, ButtonLocation::AFTER_FILTER);
?>

<script>
	BX.ready(function()
	{
		BX.Tasks.SprintSelector(
			<?= $containerID;?>,
			{
				sprintId: <?= $arParams['SPRINT_ID'];?>,
				groupId: <?= $arParams['GROUP_ID'];?>
			}
		);
	});
</script>
