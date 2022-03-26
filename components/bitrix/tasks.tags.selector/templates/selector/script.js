'use strict';

BX.namespace('BX.Tasks');

BX.Tasks.TagsSelector = function(options)
{
	this.taskId = (options.taskId || 0);
	this.templateId = (options.templateId || 0);
	this.tags = options.tags;

	this.entity = {
		id: 'task-tag'
	};
	if (this.taskId)
	{
		this.entity.options = {
			taskId: this.taskId
		};
		this.tags = [];
	}
};

BX.Tasks.TagsSelector.prototype = {
	constructor: BX.Tasks.TagsSelector,

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
					this.entity
				],
				selectedItems: this.tags.map(
					function(tag) {
						return {
							id: BX.util.htmlspecialcharsback(tag),
							entityId: 'task-tag',
							title: BX.util.htmlspecialcharsback(tag),
							tabs: 'all'
						};
					}
				),
				searchOptions: {
					allowCreateItem: true,
				},
				events: {
					'Search:onItemCreateAsync': function (event) {
						var promise = new BX.Promise();
						var searchQuery = event.getData().searchQuery;
						var dialog = event.getTarget();

						setTimeout(function () {
							var item = dialog.addItem({
								id: searchQuery.getQuery(),
								entityId: 'task-tag',
								title: searchQuery.getQuery(),
								tabs: 'all'
							});
							if (item)
							{
								item.select();
							}
							promise.fulfill();
						}, 1000);

						return promise;
					},
					'Item:onSelect': function () {
						this.onTagsChange();
					}.bind(this),
					'Item:onDeselect': function () {
						this.onTagsChange();
					}.bind(this),
				}
			});
		}

		return this.dialog;
	},

	onTagsChange: function()
	{
		var selectedItems = this.getSelector().getSelectedItems();
		var	tagIds = selectedItems.map(
			function(item) {
				return item.getId();
			}
		);

		var tagsString = tagIds.join(', ');
		this.updateNode(tagsString);

		// event goes to tasks.task.template to update tags in task's template
		var tags = tagIds.map(
			function(tag) {
				return {name: tag};
			}
		);
		BX.onCustomEvent('onTaskTagSelect', [tags]);

		if (this.taskId)
		{
			this.updateTask(tagIds);
		}
	},

	updateNode: function(tagsString)
	{
		var tagLine = BX('task-tags-line');
		var tagLink = BX('task-tags-link');

		BX.cleanNode(tagLine);
		BX.adjust(tagLine, {text: tagsString});

		if (tagsString.length > 0)
		{
			tagLine.innerHTML += '&nbsp;&nbsp;';
			tagLink.innerHTML = BX.message('TAGS_BUTTON_CHANGE');
		}
		else
		{
			tagLink.innerHTML = BX.message('TAGS_BUTTON_ADD');
		}
	},

	updateTask: function(tagIds)
	{
		BX.ajax.runComponentAction('bitrix:tasks.tags.selector', 'updateTags', {
			mode: 'class',
			data: {
				taskId: this.taskId,
				tagIds: tagIds
			}
		}).catch(function(response) {
			if (response.errors)
			{
				BX.Tasks.alert(response.errors);
			}
		});
	}
};