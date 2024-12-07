<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

CUtil::InitJSCore(['tags']);

$isSilent = (isset($arParams['SILENT']) && $arParams['SILENT'] === 'Y');
$canEdit = $arResult['CAN_EDIT'];
?>

<?php if (!$isSilent):?>
	<?php if (count($arResult['VALUE']) > 0): ?>
		<span id="task-tags-line" class="task-tags-line">
			<?php
			$items = [];
			foreach ($arResult['VALUE'] as $tag)
			{
				$items[] = '<a data-slider-ignore-autobinding="true" target="_top" href="'.$arResult['PATH_TO_TASKS'] . '?apply_filter=Y&TAG=' . $tag.'">' . $tag . '</a>';
			}
			echo implode(', ', $items);
			unset($items);
			?>
		</span>
		<?php if ($canEdit): ?>
			<span class="task-dashed-link">
				<span class="task-dashed-link-inner" id="task-tags-link"><?=GetMessage('TASKS_TAGS_CHANGE')?></span>
			</span>
		<?php endif ?>
	<?php else: ?>
		<span id="task-tags-line" class="task-tags-line"></span>
		<?php if ($canEdit): ?>
			<span class="task-dashed-link">
				<span class="task-dashed-link-inner" id="task-tags-link"><?=GetMessage("TASKS_TAGS_ADD")?></span>
			</span>
		<?php endif ?>
	<?php endif ?>
	<?php if ($canEdit): ?>
		<input type="hidden" name="<?php echo $arResult["NAME"]?>" value="<?php echo sizeof($arResult["VALUE"]) > 0 ? implode(", ", $arResult["VALUE"]) : ""?>" id="task-tags-input" />
	<?php endif ?>
<?php endif?>

<script>
	function TasksShowTagsPopup(e)
	{
		if (!e)
		{
			e = window.event;
		}

		tasksTagsPopUp.popupWindow.setBindElement(this.parentNode);
		tasksTagsPopUp.showPopup();

		BX.PreventDefault(e);
	}

	var tags = [
		<?php foreach ($arResult['~VALUE'] as $tag): ?>
			{
				name: '<?= CUtil::JSEscape($tag) ?>',
				selected: true
			},
		<?php endforeach ?>
		<?php foreach ($arResult['~USER_TAGS'] as $tag) :?>
			<?php if (!in_array($tag, $arResult['~VALUE'])):?>
				{name: '<?= CUtil::JSEscape($tag) ?>'},
			<?php endif ?>
		<?php endforeach ?>
		{name: ''}
	];
	var tasksTagsPopUp = null;

	BX.ready(function() {
		BX.message({
			TAGS_BUTTON_OK: '<?= GetMessageJS('TASKS_TAGS_OK') ?>',
			TAGS_BUTTON_CANCEL: '<?= GetMessageJS('TASKS_TAGS_CANCEL') ?>',
			TAGS_BUTTON_SAVE: '<?= GetMessageJS('TASKS_TAGS_SAVE') ?>',
			TAGS_BUTTON_DISCARD: '<?= GetMessageJS('TASKS_TAGS_DISCARD') ?>',
			TAGS_BUTTON_ADD: '<?= GetMessageJS('TASKS_TAGS_ADD') ?>',
			TAGS_BUTTON_CHANGE: '<?= GetMessageJS('TASKS_TAGS_CHANGE') ?>'
		});

		tasksTagsPopUp = new BX.TagsWindow.create('task-tags-popup', null, tags, {
			events: {
				'onSaveButtonClick': function(tagsWindow) {
					var data = {
						sessid: BX.message('bitrix_sessid'),
						deleted: [],
						oldNames: [],
						newNames: []
					};
					for (var i = 0; i < tagsWindow.windowArea.deletedTags.length; i++)
					{
						data.deleted.push(tagsWindow.windowArea.deletedTags[i].name);
					}
					for (var i in tagsWindow.windowArea.renamedTags)
					{
						data.oldNames.push(i);
						data.newNames.push(tagsWindow.windowArea.renamedTags[i]);
					}
					BX.ajax.post('/bitrix/components/bitrix/tasks.tags.selector/ajax.php', data);
				},
				'onSelectButtonClick': function() {
					var tags = this.windowArea.getSelectedTags();
					<?php if (!$isSilent): ?>
						var tagsString = '';
						for (var i = 0, length = tags.length; i < length; i++)
						{
							if (i > 0)
							{
								tagsString += ', ';
							}
							tagsString += tags[i].name;
						}
						BX('task-tags-input').value = tagsString;
						BX.onCustomEvent('onTaskTagSelect', [tags]);
					<?php else: ?>
						var changedTags = [];
						for (var i = 0, length = tags.length; i < length; i++)
						{
							changedTags.push(tags[i].name);
						}
						BX.onCustomEvent('onTaskTagSelectAlt', [changedTags]);
					<?php endif ?>
					<?php if ($arParams['ON_SELECT'] !== ''): ?>
						<?= $arParams['ON_SELECT'] ?>(tags);
					<?php endif?>
				}<?php if(!$isSilent): ?>,
				'onUpdateTagLine': function(tagsWindow) {
					var tags = this.windowArea.getSelectedTags();
					var tagsString = '';
					for (var i = 0, length = tags.length; i < length; i++)
					{
						if (i > 0)
						{
							tagsString += ', ';
						}
						tagsString += tags[i].name;
					}
					var tagLine = BX('task-tags-line');
					BX.cleanNode(tagLine);
					BX.adjust(tagLine, {text: tagsString});
					if (tagsString.length > 0)
					{
						tagLine.innerHTML += '&nbsp;&nbsp;';
						BX('task-tags-link').innerHTML = BX.message('TAGS_BUTTON_CHANGE');
					}
					else
					{
						BX('task-tags-link').innerHTML = BX.message('TAGS_BUTTON_ADD');
					}
					<?php if ($arParams['ON_UPDATE'] !== '') :?>
						<?= $arParams['ON_UPDATE'] ?>(tags);
					<?php endif ?>
				}<?php elseif ($arParams['ON_UPDATE']): ?>,
					'onUpdateTagLine': <?= $arParams['ON_UPDATE'] ?>
				<?php endif ?>
			}
		});

		BX.bind(BX('task-tags-popup'), 'click', function(e) {
			if (!e)
			{
				e = window.event;
			}

			if (e.stopPropagation)
			{
				e.stopPropagation();
			}
			else
			{
				e.cancelBubble = true;
			}
		});

		BX.addCustomEvent(window, 'onTaskTagDeleteByCross', function(tagData) {
			var tags = tasksTagsPopUp.windowArea.getTags();
			var index = tasksTagsPopUp.windowArea.indexOfTagName(tagData.NAME);
			var tag = tags[index];

			tasksTagsPopUp.windowArea.selectTag(tag, false);
		});

		<?php if (!$isSilent && $canEdit): ?>
			BX.bind(BX('task-tags-link'), 'click', TasksShowTagsPopup);
		<?php endif?>
	});
</script>