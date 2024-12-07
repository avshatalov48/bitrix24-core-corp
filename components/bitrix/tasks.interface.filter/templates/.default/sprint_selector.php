<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$containerID = 'tasks_sprint_selector';

$currentSprintName = htmlspecialcharsbx($arResult['SPRINT']['START_TIME'])
	. ' - ' . htmlspecialcharsbx($arResult['SPRINT']['FINISH_TIME'])
;
?>

<div class="pagetitle-container pagetitle-flexible-space">
	<div id="<?= $containerID;?>" class="tasks-interface-toolbar-button-container">
		<div class="webform-small-button webform-small-button-transparent webform-small-button-dropdown">
			<span class="webform-small-button-text"><?=$currentSprintName?></span>
			<span class="webform-small-button-icon"></span>
		</div>
	</div>
</div>

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
