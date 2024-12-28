<?php

use Bitrix\Main\Web\Json;
use Bitrix\Main\Text\HtmlFilter;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 */

?>

<div class="task-options-item-open-inner widget-task-selector-container">
	<span id="bx-component-scope-<?= HtmlFilter::encode($arResult['NAME']) . '-' . $arResult['TEMPLATE_ID'] ?>">
		<?php
		if($arResult['MULTIPLE']): ?>
			<?php
			foreach($arResult['TASKS'] as $task): ?>
				<input
					type="hidden"
					name="<?= HtmlFilter::encode($arResult['INPUT_PREFIX']) ?>[<?= $arResult['BLOCK_NAME'] ?>][<?= $task['ID'] ?>][ID]"
					id="input-set-<?= HtmlFilter::encode($arResult['NAME']) . '-' . $arResult['TEMPLATE_ID'] ?>-<?= $task['ID'] ?>"
					value="<?= $task['ID'] ?>"
					data-bx-id="task-edit-parent-input"/>
			<?php
			endforeach; ?>
			<input type="hidden" name="<?=HtmlFilter::encode($arResult['INPUT_PREFIX'])?>[<?=$arResult['BLOCK_NAME']?>][]" value="">
		<?php
		else: ?>
			<input
				type="hidden"
				name="<?= HtmlFilter::encode($arResult['INPUT_PREFIX']) ?>[<?= $arResult['BLOCK_NAME'] ?>][ID]"
				id="input-set-<?= HtmlFilter::encode($arResult['NAME']) . '-' . $arResult['TEMPLATE_ID'] ?>"
				value="<?= empty($arResult['TASKS']) ? '' : current($arResult['TASKS'])['ID'] ?>"
				data-bx-id="task-edit-parent-input"/>
		<?php
		endif; ?>
	</span>
</div>

<script>
	function initTaskSelector()
	{
		const multiple = <?= $arResult['MULTIPLE'] ? 'true' : 'false' ?>;
		const currentTasks = '<?= CUtil::JSEscape(Json::encode($arResult['TASKS'])) ?>';
		const userId = '<?= $arResult['USER_ID'] ?>';

		const taskSelector = (new BX.Tasks.TaskSelector({
				multiple: multiple,
				currentTasks: currentTasks,
				userId: userId,
			}));

		const fieldName = '<?= HtmlFilter::encode($arResult['NAME']) ?>';
		const templateId = '<?= $arResult['TEMPLATE_ID'] ?>';
		const inputPrefix = '<?= HtmlFilter::encode($arResult['INPUT_PREFIX']) ?>';
		const blockName = '<?= $arResult['BLOCK_NAME'] ?>';

		const tagDomManager = new BX.Tasks.TagDomManager({
			'inputNodeId': 'input-set-' + fieldName + '-' + templateId,
			'scopeContainerId': 'bx-component-scope-' + fieldName + '-' + templateId,
			'inputPrefix': inputPrefix,
			'blockName': blockName,
		});

		taskSelector.subscribe('tagAdded', (event) => {
			tagDomManager.onTagAdd(event);
		});

		taskSelector.subscribe('tagRemoved', (event) => {
			tagDomManager.onTagRemove(event);
		});

		taskSelector
			.getSelector()
			.renderTo(document.getElementById('bx-component-scope-' + fieldName + '-' + templateId));
	}

	initTaskSelector();
</script>
