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

<script type="text/javascript">
	function TasksShowTagsPopup(e)
	{
		if (!e)
		{
			e = window.event;
		}

		tasksTagsSelector.show();

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

	BX.ready(function() {

		BX.message({
			TAGS_BUTTON_OK: '<?= GetMessageJS('TASKS_TAGS_OK') ?>',
			TAGS_BUTTON_CANCEL: '<?= GetMessageJS('TASKS_TAGS_CANCEL') ?>',
			TAGS_BUTTON_SAVE: '<?= GetMessageJS('TASKS_TAGS_SAVE') ?>',
			TAGS_BUTTON_DISCARD: '<?= GetMessageJS('TASKS_TAGS_DISCARD') ?>',
			TAGS_BUTTON_ADD: '<?= GetMessageJS('TASKS_TAGS_ADD') ?>',
			TAGS_BUTTON_CHANGE: '<?= GetMessageJS('TASKS_TAGS_CHANGE') ?>'
		});

		tasksTagsSelector = {
			dialog: null,
			query: null,
			taskId: <?= $arResult['TASK_ID']; ?>,

			init: function ()
			{
				this.getSelector().load();
			},

			show: function()
			{
				this.getSelector().show();
			},

			getSelector: function()
			{
				if (!this.dialog)
				{
					this.dialog = new BX.UI.EntitySelector.Dialog({
						targetNode: BX('task-tags-link'),
						enableSearch: true,
						width: 350,
						height: 400,
						multiple: true,
						dropdownMode: true,
						compactView: true,
						context: 'TASKS_TAG',
						entities: [
							{
								id: 'task-tag',
								options: {
									taskId: this.taskId
								}
							}
						],
						searchOptions: {
							allowCreateItem: true,
						},
						events: {
							'onShow': function () {

							}.bind(this),
							'onHide': function () {

							}.bind(this),
							'Search:onItemCreateAsync': function (event) {
								var promise = new BX.Promise();
								var searchQuery = event.getData().searchQuery;
								var dialog = event.getTarget();

								setTimeout(function () {
									var item = dialog.addItem({
										id: searchQuery.getQuery(),
										entityId: 'tag',
										title: searchQuery.getQuery(),
										tabs: 'all'
									});
									if (item) {
										item.select();
									}
									promise.fulfill();
								}, 1000);

								return promise;
							},
							'Item:onSelect': function (event) {
								if (this.dialogCallback === false)
								{
									return;
								}

								this.onTagsChange(event);
							}.bind(this),
							'Item:onDeselect': function (event) {
								if (this.dialogCallback === false)
								{
									return;
								}

								this.onTagsChange(event);
							}.bind(this),
						}
					});
				}
				return this.dialog;
			},

			onTagsChange: function(event)
			{
				var	tagIds = this.getSelector()
					.getSelectedItems()
					.map(function(item) {
						return item.getId();
					});

				var tags = [];
				for (var i = 0, length = tagIds.length; i < length; i++)
				{
					tags.push({
						name: tagIds[i]
					});
				}

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

				BX.onCustomEvent('onTaskTagSelect', [tags]);
				this.updateTask({data: {TAGS: tagIds}});
			},

			updateTask: function(args)
			{
				args = args || {};
				args['id'] = this.taskId;

				this.getQuery()
					.add(
						'task.update',
						args,
						{},
						BX.delegate(function (errors, data) {
							if (!errors.checkHasErrors()) {

							}
						}, this)
					);
			},

			getQuery: function () {
				if (!this.query) {
					this.query = new BX.Tasks.Util.Query({
						autoExec: true
					});
				}

				return this.query;
			},
		};

		tasksTagsSelector.init();

		<?php if (!$isSilent && $canEdit): ?>
			BX.bind(BX('task-tags-link'), 'click', function() { tasksTagsSelector.show(); });
		<?php endif?>
	});
</script>
