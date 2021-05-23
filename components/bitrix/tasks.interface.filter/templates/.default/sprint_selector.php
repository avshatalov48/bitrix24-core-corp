<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

$currentSprint = $arResult['SPRINTS'][$arParams['SPRINT_ID']];
$containerID = 'tasks_sprint_selector';

$currentSprintName = htmlspecialcharsbx($currentSprint['START_TIME']). ' - ' .
	htmlspecialcharsbx($currentSprint['FINISH_TIME']);
?>

<div class="pagetitle-container pagetitle-flexible-space">
	<div id="<?= $containerID;?>" class="tasks-interface-toolbar-button-container">
		<div class="webform-small-button webform-small-button-transparent webform-small-button-dropdown">
			<span class="webform-small-button-text"><?=$currentSprintName?></span>
			<span class="webform-small-button-icon"></span>
		</div>
	</div>
</div>

<script type="text/javascript">
	BX.ready(function()
	{
		BX.Tasks.SprintSelector(
			<?= $containerID;?>,
			<?= \CUtil::phpToJSObject(array_values($arResult['SPRINTS']));?>,
			{
				sprintId: <?= $arParams['SPRINT_ID'];?>,
				groupId: <?= $arParams['GROUP_ID'];?>
			}
		);
	});
</script>
