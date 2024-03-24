<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ?? "") . " no-hidden");

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.forms',
	'ui.buttons',
	'ui.notification',
]);

$user = $arResult['USER'];
$isMine = $arResult['IS_MINE'];

$task = $arResult['TASK'];
$taskActions = CBPDocument::getTaskControls($task);
$taskParams = $task['PARAMETERS'];
?>
<? $this->setViewTarget("inside_pagetitle_below", 100); ?>
<div class="rpa-task-title">
	<span class="rpa-task-title-img"<?if (!empty($user['photo'])):?> style="background-image: url(<?=htmlspecialcharsbx($user['photo'])?>); background-size: 100%"<?endif?>></span>
	<div class="rpa-task-title-info">
		<a class="rpa-task-title-user" href="<?=$user['link']?>"><?=htmlspecialcharsbx($user['fullName'])?></a>
		<div class="rpa-task-title-stage"><?=htmlspecialcharsbx($user['workPosition'])?></div>
	</div>
</div>
<? $this->endViewTarget(); ?>

<?if ($errors = $this->getComponent()->getErrors()):
	ShowError(reset($errors)->getMessage());
	return;
endif;
?>
<div class="rpa-task-block">
	<div class="rpa-automation-header">
		<div class="rpa-automation-header-text"><?=htmlspecialcharsbx($task['NAME'])?></div>
		<?if (!empty($task['DESCRIPTION'])):?>
		<div class="rpa-automation-header-desc"><?=htmlspecialcharsbx($task['DESCRIPTION'])?></div>
		<?endif;?>
	</div>
	<?php
	if ($isMine):

	if (!empty($taskParams['FIELDS_TO_SHOW']) || !empty($taskParams['FIELDS_TO_SET'])):
		$APPLICATION->IncludeComponent(
				'bitrix:rpa.task.fields',
				'',
				[
					'typeId' => $arParams['typeId'],
					'id' => $arParams['elementId'],
					'fieldsToShow' => $taskParams['FIELDS_TO_SHOW'],
					'fieldsToSet' => $taskParams['FIELDS_TO_SET'] ?? [],
					'taskId' => $task['ID'],
				]
		);
	endif;
	?>
	<form class="rpa-automation-btn" onsubmit="return false;">
		<input type="hidden" name="taskId" value="<?=(int)$task['ID']?>">
		<?foreach ($taskActions['BUTTONS'] as $btn):?>
		<button class="ui-btn ui-btn-default" data-role="btn-action"
				type="<?=htmlspecialcharsbx($btn['TYPE'])?>"
				name="<?=htmlspecialcharsbx($btn['NAME'])?>"
				value="<?=htmlspecialcharsbx($btn['VALUE'])?>"
				style="background-color: #<?=htmlspecialcharsbx($btn['COLOR'])?>;border-color: #<?=htmlspecialcharsbx($btn['COLOR'])?>"><?=htmlspecialcharsbx($btn['TEXT'])?></button>
		<?endforeach;?>
	</form>
	<script>
		BX.ready(function()
		{
			BX.message({RPA_TASK_FIELD_VALIDATION_ERROR: '<?=GetMessageJS('RPA_TASK_FIELD_VALIDATION_ERROR')?>'});
			var cmp = new BX.Rpa.TaskComponent(
				<?=(int)$arParams['typeId']?>,
				<?=(int)$arParams['elementId']?>,
				{
					buttons: Array.from(document.querySelectorAll('[data-role="btn-action"]')),
					task: <?=\Bitrix\Main\Web\Json::encode($task)?>,
					onTaskComplete: function(responseData)
					{
						var sliderInstance = BX.getClass('BX.SidePanel.Instance');
						if (sliderInstance)
						{
							var thisSlider = sliderInstance.getSliderByWindow(window);

							if (thisSlider)
							{
								thisSlider.getData().set('isCompleted', true);
								if(responseData.item)
								{
								    thisSlider.getData().set('item', responseData.item);
								}
								thisSlider.close();
							}
						}
						var tasksGrid = top.window['bxGrid_bizproc_task_list'];
						if (tasksGrid && BX.type.isFunction(tasksGrid.Reload))
						{
							tasksGrid.Reload();
						}
						else if (BX.getClass('top.BX.Bizproc.Component.TaskList.Instance'))
						{
							top.BX.Bizproc.Component.TaskList.Instance.reloadGrid();
						}
					}
				});

			cmp.init();
		});
	</script>
	<?endif;?>
</div>