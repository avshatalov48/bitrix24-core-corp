<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); 

$APPLICATION->AddHeadScript('/bitrix/js/main/dd.js');
?>


<!-- =========================== begin checklist =========================== -->


<div class="task-detail-checklist" id="task-detail-checklist-scope">
	<script type="text/javascript">
		BX.message({
			TASKS_DETAIL_CHECKLIST : '<?php echo GetMessageJS('TASKS_DETAIL_CHECKLIST'); ?>',
			TASKS_DETAIL_CHECKLIST_DETAILED : '<?php echo GetMessageJS('TASKS_DETAIL_CHECKLIST_DETAILED'); ?>'
		});
	</script>
	<div class="task-detail-checklist-title">
		<label id="task-detail-checklist-title-text" style="margin-right:0px;"><?php
			echo GetMessage('TASKS_DETAIL_CHECKLIST');
		?></label>:
	</div>
	<div id="task-detail-checklist-items" style="position: relative;">
		<div id="task-detail-checklist-top-land-zone" style="height:3px;">
		</div>
		<script type="text/javascript">
		(function(){
			BX.ready(function(){
				window.jsDD.Reset();
				window.jsDD.registerDest(BX('task-detail-checklist-top-land-zone'));
			});
		})();
		</script>
	<?php

	$isCreateTaskMode = false;
	if ($arParams['MODE'] === 'CREATE TASK FORM')
		$isCreateTaskMode = true;

	if ( ! empty($arResult['CHECKLIST_ITEMS']) )
	{
		echo '<div id="task-detail-checklist-items-loader">' . GetMessage('TASKS_DETAIL_CHECKLIST_LOADING') . '</div>';

		?><script type="text/javascript">
		(function(){
			BX.ready(function(){
				window.jsDD.Reset();
				window.jsDD.registerDest(BX('task-detail-checklist-top-land-zone'));

				BX.remove(BX('task-detail-checklist-items-loader'));

				<?php

				$tp = new CTextParser();
				$tp->allow = array('ANCHOR' => 'Y', 'BIU' => 'N', 'HTML' => 'N');

				foreach ($arResult['CHECKLIST_ITEMS'] as $itemData)
				{
					?>
					BX('task-detail-checklist-items').appendChild(
						tasksDetailPartsNS.renderChecklistItem(
							<?php echo ($isCreateTaskMode ? 'true' : 'false'); ?>, 
							<?php echo (int) $arResult['TASK_ID']; ?>, 
							'<?php echo $itemData['ID']; ?>', 
							'<?=CUtil::JSEscape($itemData['~TITLE']); ?>',
							<?=(($itemData['IS_COMPLETE'] === 'Y') ? 'true' : 'false');?>,
							{<?=($arParams['READ_ONLY'] != 'Y' ? "" : "readonly: true,")?>display: "<?=CUtil::JSEscape($tp->convertText(htmlspecialcharsbx($itemData['~TITLE'])))?>"}
						)
					);

					<?if($arParams['READ_ONLY'] != 'Y'):?>
						tasksDetailPartsNS.initChecklistItem(
							<?php echo ($isCreateTaskMode ? 'true' : 'false'); ?>, 
							<?php echo (int) $arResult['TASK_ID']; ?>, 
							'<?php echo $itemData['ID']; ?>', 
							'<?php echo CUtil::JSEscape($itemData['~TITLE']); ?>', 
							<?php echo (($itemData['IS_COMPLETE'] === 'Y') ? 'true' : 'false'); ?>
						);
					<?endif?>
					<?php
				}
				?>

				tasksDetailPartsNS.recalcChecklist();
			});
		})();
		</script><?php
	}
	?>
	</div>
	<?php
	if ($arResult['ALLOWED_ACTIONS']['ACTION_CHECKLIST_ADD_ITEMS'] === true)
	{
		?>
		<a href="javascript:void(0);"
			class="webform-field-action-link tasks-checklist-action-link"
			onclick="
				BX('task-detail-checklist-add-area').style.display = 'block';
				this.parentNode.removeChild(this);
				BX('task-detail-checklist-add-text').focus();
			"
			><?php
				echo GetMessage('TASKS_DETAIL_CHECKLIST_ADD');
		?></a>
		<div class="task-detail-checklist-add" id="task-detail-checklist-add-area">
			<input id="task-detail-checklist-add-text" type="text" value=""
				maxlength="250"
				onkeypress="tasksDetailPartsNS.checklistSaveItem(
					event,
					<?php echo ($isCreateTaskMode ? 'true' : 'false'); ?> ,
					<?php echo (int) $arResult['TASK_ID']; ?>
					);"
			>
			<a href="javascript:void(0);"
				id="task-detail-checklist-add-link"
				class="webform-field-action-link"
				onclick="
					tasksDetailPartsNS.checklistAddItem(
						<?php echo ($isCreateTaskMode ? 'true' : 'false'); ?> ,
						<?php echo (int) $arResult['TASK_ID']; ?>
					);
					return false;"
			><?=GetMessage('TASKS_DETAIL_CHECKLIST_ADD')?></a>
		</div>
		<?php
	}
	?>
	<?if(!$isCreateTaskMode):?>
		<a id="task-detail-checklist-show-completed" class="webform-field-action-link tasks-checklist-action-link" href="javascript:void(0);" style="margin-right: 20px"><?=GetMessage('TASKS_DETAIL_CHECKLIST_SHOW_COMPLETED')?></a>
		<a id="task-detail-checklist-hide-completed" class="webform-field-action-link tasks-checklist-action-link" href="javascript:void(0);" style="margin-right: 20px"><?=GetMessage('TASKS_DETAIL_CHECKLIST_HIDE_COMPLETED')?></a>
	<?endif?>

	<script>
		tasksDetailPartsNS.initChecklist(<?=($isCreateTaskMode ? 'true' : 'false')?>);
	</script>
</div>


<!-- =========================== end of checklist =========================== -->

