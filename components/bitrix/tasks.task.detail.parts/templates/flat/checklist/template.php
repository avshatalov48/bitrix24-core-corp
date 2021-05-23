<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); 

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$templateId = $arResult['TEMPLATE_DATA']['ID'];
$prefix = htmlspecialcharsbx($arResult['TEMPLATE_DATA']['INPUT_PREFIX']);

$canReorder = $arResult['TEMPLATE_DATA']['TASK_CAN']['CHECKLIST.REORDER'];
$canAddOwn = $arResult['TEMPLATE_DATA']['TASK_CAN']['CHECKLIST.ADD'];
?>

<div id="bx-component-scope-<?=htmlspecialcharsbx($templateId)?>" class="task-options task-checklist<?if(!$canReorder):?> nodrag<?endif?>">

	<div class="task-checklist-title"><?=Loc::getMessage('TASKS_TTDP_CHECKLIST_TEMPLATE_BLOCK_TITLE')?><span data-bx-id="checklist-counterset" class="task-checklist-status hidden">&nbsp;(<?=Loc::getMessage('TASKS_TTDP_CHECKLIST_COMPLETE')?> <span data-bx-id="checklist-complete-counter">0</span> <?=Loc::getMessage('TASKS_TTDP_CHECKLIST_OF')?> <span data-bx-id="checklist-total-counter">0</span>)</span></div>

	<div data-bx-id="checklist-items-ongoing" class="tasks-checklist-dropzone">

		<div class="tasks-checklist-zone-marker"></div>
		<script data-bx-id="checklist-item" type="text/html">

			<div data-bx-id="checklist-item-appearance" data-item-id="{{ID}}" class="tasks-checklist-item mode-read {{APPEARANCE}} {{READONLY}}">
				<div class="task-checklist-field generic">
					<div class="task-checklist-field-inner">
						<span data-bx-id="checklist-item-drag" class="task-field-drg-btn"></span>
						<input data-bx-id="checklist-item-btn-check" id="chl_item_{{ID}}" class="task-checklist-field-checkbox" type="checkbox" {{CHECKED}} />

						<?//read mode?>
						<label class="block-read task-checklist-field-label" for="chl_item_{{ID}}"><span data-bx-id="checklist-item-number"></span>.&nbsp;<span data-bx-id="checklist-item-title">{{{TITLE_HTML}}}</span></label>
						<span data-bx-id="checklist-item-btn-edit" class="block-read task-field-title-edit"></span>

						<?//edit mode?>
						<input data-bx-id="checklist-item-title-edit" class="block-edit task-checklist-field-add" type="text" value="{{TITLE}}" name="<?=$prefix?>[{{ID}}][TITLE]" placeholder="<?=Loc::getMessage('TASKS_TTDP_CHECKLIST_WHAT_TO_BE_DONE')?>" maxlength="255" />
						<span data-bx-id="checklist-item-btn-apply" class="block-edit task-field-title-ok"></span>

						<span data-bx-id="checklist-item-btn-delete" class="task-field-title-del"></span>
					</div>
				</div>

				<div data-bx-id="checklist-item-drag" class="task-field-divider separator">
					<div data-bx-id="checklist-item-btn-delete" class="task-field-divider-close"></div>
				</div>

				<div class="tasks-checklist-item-marker"></div>

				<input type="hidden" name="<?=$prefix?>[{{ID}}][ID]" value="{{ID}}" />
				<input data-bx-id="checklist-item-sort-index-fld" type="hidden" name="<?=$prefix?>[{{ID}}][SORT_INDEX]" value="{{SORT_INDEX}}" />
				<input data-bx-id="checklist-item-is-complete-fld" type="hidden" name="<?=$prefix?>[{{ID}}][IS_COMPLETE]" value="{{IS_COMPLETE}}" />
			</div>

		</script>
		<script data-bx-id="checklist-item-flying" type="text/html">
			<div class="task-checklist-field">
				<div class="task-checklist-field-inner">
					<span class="task-field-drg-btn"></span>
					<input id="chl_item_{{ID}}-f" class="task-checklist-field-checkbox" type="checkbox" {{CHECKED}} />
					<label for="chl_item_{{ID}}-f" class="task-checklist-field-label">{{{TITLE_HTML}}}</label>
				</div>
			</div>
		</script>
		<script data-bx-id="checklist-separator-flying" type="text/html">
			<div data-bx-id="checklist-item-drag" class="task-field-divider"><div data-bx-id="checklist-item-btn-delete" class="task-field-divider-close"></div></div>
		</script>

	</div>

	<?if($canAddOwn):?>
		<div data-bx-id="checklist-add-item-form" class="task-checklist-field tasks-checklist-dropzone off">
			<span class="block-on task-checklist-field-inner-add">
				<input data-bx-id="checklist-add-item-title" type="text" class="task-checklist-field-add" placeholder="<?=Loc::getMessage('TASKS_TTDP_CHECKLIST_WHAT_TO_BE_DONE')?>" maxlength="255" />
				<span data-bx-id="checklist-add-item" class="block-edit task-field-title-ok"></span>
				<span data-bx-id="checklist-add-item-form-close" class="task-field-title-del"></span>
			</span>

            <div class="task-checklist-actions">
                <span class="block-off task-dashed-link"><span data-bx-id="checklist-add-item-form-open" class="task-dashed-link-inner"><?=Loc::getMessage('TASKS_TTDP_CHECKLIST_ADD')?></span></span>
                <span data-bx-id="checklist-add-separator" class="task-dashed-link"><span class="task-dashed-link-inner"><?=Loc::getMessage('TASKS_TTDP_CHECKLIST_SEPARATOR')?></span></span>
            </div>
		</div>
	<?endif?>

	<div data-bx-id="checklist-complete-block" class="task-checklist-resolved hidden">
		<div class="task-checklist-subtitle"><span data-bx-id="checklist-toggle-complete"><?=Loc::getMessage('TASKS_TTDP_CHECKLIST_COMPLETE')?> (<span data-bx-id="checklist-complete-counter">0</span>)</span></div>
		<div data-bx-id="checklist-items-complete" class="tasks-checklist-dropzone">
			<div class="tasks-checklist-zone-marker"></div>
		</div>
	</div>

	<?// in case of all items removed, the field should be sent anyway?>
	<input type="hidden" name="<?=$prefix?>[]" value="">
</div>

<script>
	new BX.Tasks.Component.TaskDetailPartsChecklist(<?=CUtil::PhpToJSObject(array(
		'id' => $templateId,
		'registerDispatcher' => true,
		'data' => $arResult['TEMPLATE_DATA']['ITEMS']['DATA'],
		'taskId' => intval($arResult['TEMPLATE_DATA']['TASK_ID']),
        'taskCanEdit' => $arResult['TEMPLATE_DATA']['TASK_CAN_EDIT'],
		'taskCan' => $arResult['TEMPLATE_DATA']['TASK_CAN'],
        'autoSync' => !!$arResult['TEMPLATE_DATA']['AUTO_SYNC'],
	), false, false, true)?>);
</script>