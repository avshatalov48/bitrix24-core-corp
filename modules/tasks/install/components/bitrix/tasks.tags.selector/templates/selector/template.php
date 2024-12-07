<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Update\TagConverter;

$isSilent = (isset($arParams['SILENT']) && $arParams['SILENT'] === 'Y');
$canEdit = $arResult['CAN_EDIT'];

//Checking for working tags agent
$tagsAreConverting = TagConverter::isProceed();
?>

<?php if (!$isSilent):?>
	<?php if (count($arResult['VALUE']) > 0): ?>
		<span id="task-tags-line" class="task-tags-line">
			<?= $arResult['TAGS'] ?>
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
	BX.ready(function() {
		BX.message({
			TAGS_BUTTON_OK: '<?= GetMessageJS('TASKS_TAGS_OK') ?>',
			TAGS_BUTTON_CANCEL: '<?= GetMessageJS('TASKS_TAGS_CANCEL') ?>',
			TAGS_BUTTON_SAVE: '<?= GetMessageJS('TASKS_TAGS_SAVE') ?>',
			TAGS_BUTTON_DISCARD: '<?= GetMessageJS('TASKS_TAGS_DISCARD') ?>',
			TAGS_BUTTON_ADD: '<?= GetMessageJS('TASKS_TAGS_ADD') ?>',
			TAGS_BUTTON_CHANGE: '<?= GetMessageJS('TASKS_TAGS_CHANGE') ?>',
			TASKS_TAG_SELECTOR_TAGS_ARE_CONVERTING_TITLE: '<?= GetMessageJS('TASKS_TAG_SELECTOR_TAGS_ARE_CONVERTING_TITLE') ?>',
			TASKS_TAG_SELECTOR_TAGS_ARE_CONVERTING_COME_BACK_LATER: '<?= GetMessageJS('TASKS_TAG_SELECTOR_TAGS_ARE_CONVERTING_COME_BACK_LATER') ?>',
			TASKS_TAG_SELECTOR_TAGS_ARE_CONVERTING_TEXT: '<?= Json::encode(GetMessageJS('TASKS_TAG_SELECTOR_TAGS_ARE_CONVERTING_TEXT')) ?>',
		});

		var tasksTagsSelector = new BX.Tasks.TagsSelector(
			<?= Json::encode([
				'tagsAreConverting' => $tagsAreConverting,
				'groupId' => (int) $arResult['GROUP_ID'],
				'taskId' => (int) $arResult['TASK_ID'],
				'isScrumTask' => $arResult['IS_SCRUM_TASK'] ? 'Y' : 'N',
				'templateId' => (int) $arResult['TEMPLATE_ID'],
				'tags' =>  $arResult['VALUE'],
				'userId' => \Bitrix\Main\Engine\CurrentUser::get()->getId(),
			])?>
		);

		<?php if (!$isSilent && $canEdit): ?>
			BX.bind(BX('task-tags-link'), 'click', function() { tasksTagsSelector.show(); });
		<?php endif?>
	});
</script>