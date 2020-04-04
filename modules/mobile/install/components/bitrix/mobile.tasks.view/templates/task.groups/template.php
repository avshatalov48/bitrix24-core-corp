<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$GLOBALS['APPLICATION']->SetPageProperty('BodyClass', 'tasks-folders-page');
$GLOBALS['APPLICATION']->addHeadScript(SITE_TEMPLATE_PATH.'/tasks/logic.js');
$GLOBALS['APPLICATION']->addHeadScript($templateFolder.'/logic.js');
$GLOBALS['APPLICATION']->ShowCSS();  // bug: style.css can not be displayed, so a little hack here
?>

<div id="tasks-all-items" class="tasks-folders-wrap">

	<?
	if(is_array($arResult['DATA']['ITEMS']))
	{
		foreach($arResult['DATA']['ITEMS'] as $item)
		{
			?>
			<div
				class="task-folder-block"

				data-bx-id="taskgroups-group"
				data-group-id="<?=htmlspecialcharsbx($item['ID'])?>"
				data-url="<?=htmlspecialcharsbx($item['URL'])?>"
				>
				<div class="task-folder-left"></div>
				<div class="task-folder-corner"></div>
				<div class="task-folder-right"></div>
				<div class="task-folder-repeat"></div>
				<div class="task-folder-header"></div>
				<div class="task-folder-right-repeat"></div>
				<div class="task-folder-text-wrap">
					<span class="task-folder-text">
						<?=htmlspecialcharsbx($item['TITLE'])?>
					</span>
				</div>
				<div class="task-folder-index-wrap" style="right: 30px">
					<?if(intval($item['TASK_COUNT']) > 0):?>
						<span class="task-folder-index"><?=intval($item['TASK_COUNT'])?></span>
					<?endif?>
					<div class="red-alert hidden" data-bx-id="taskgroups-counter">
						0
					</div>
				</div>
				<div class="task-folder-arrow"></div>
				<div class="task-folder-label label-red"></div>
				<div class="task-folder-label label-orange"></div>
				<div class="task-folder-label label-green"></div>
			</div>
			<?php
		}
	}
	?>

</div>

<script>
	<?if((string) $arParams['LOGIC_INSTANCE_CODE'] != ''):?>BX['<?=CUtil::JSEscape($arParams['LOGIC_INSTANCE_CODE'])?>']<?/*temporal solution*/?> = <?endif?>new BX.Mobile.Tasks.View.Bitrix.taskgroups({scope: BX('tasks-all-items')});
</script>
