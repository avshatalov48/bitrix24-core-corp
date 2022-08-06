'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetTagSelector != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetTagSelector = BX.Tasks.Component.extend({
		sys: {
			code: 'tag-sel-is'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
				this.subInstance('items', function(){
					return new this.constructor.Items({
						scope: this.scope(),
						data: this.option('data'),
						groupId: this.option('groupId'),
						taskId: this.option('taskId'),
						isScrumTask: (this.option('isScrumTask') === 'Y'),
						preRendered: true
					});
				});
			}
		}
	});

	BX.Tasks.Component.TasksWidgetTagSelector.Items = BX.Tasks.Util.ItemSet.extend({
		sys: {
			code: 'tag-sel'
		},
		options: {
			controlBind: 'class',
			itemFx: 'horizontal',
			itemFxHoverDelete: true,
			dialog: null,
			dialogCallback: true
		},
		methods: {

			bindEvents: function()
			{
				this.callMethod(BX.Tasks.Util.ItemSet, 'bindEvents');
				this.bindDelegateControl('form-control', 'click', this.passCtx(this.openAddForm));
				BX.addCustomEvent(window, 'onTaskTagSelectAlt', this.onTagsChange.bind(this));

				this.getTagSelector().load();
			},

			onTagsChange: function(event)
			{
				var displayedItems = [];
				this.each(function(item){
					displayedItems.push(item.display());
				});

				var items = this.getTagSelector().getItems();
				var selectedItems = [];
				for (var k = 0; k < items.length; k++)
				{
					var item = items[k];
					if (item.isSelected())
					{
						selectedItems.push(item.getId());
						if(!BX.util.in_array(item.getId(), displayedItems))
						{
							this.addItem({NAME: item.getId()});
						}
					}
				}

				// delete deleted
				this.each(function(item){
					if(!BX.util.in_array(item.display(), selectedItems))
					{
						this.deleteItem(item.value());
					}
				});
			},

			openAddForm: function(node)
			{
				this.getTagSelector().show();
			},

			onItemDeleteByCross: function(value)
			{
				BX.onCustomEvent("onTaskTagDeleteByCross", [value.opts.data]);
				this.callMethod(BX.Tasks.Util.ItemSet, 'onItemDeleteByCross', arguments);
				this.unselectDialogItem(value);
			},

			unselectDialogItem: function(value)
			{
				var dialog = this.getTagSelector();

				if (!dialog)
				{
					return;
				}

				this.opts.dialogCallback = false;

				if (typeof value === 'object')
				{
					value = value.data();
				}

				var item = dialog.getItem(this.prepareItemData(value));
				item && item.deselect();

				this.opts.dialogCallback = true;
			},

			prepareItemData: function (data)
			{
				return ['task-tag', data.NAME];
			},

			extractItemDisplay: function(data)
			{
				return data.NAME;
			},

			extractItemValue: function(data)
			{
				if('VALUE' in data)
				{
					return data.VALUE;
				}

				return Math.abs(this.hashCode(data.NAME));
			},

			hashCode: function(str)
			{
				if(!BX.type.isNotEmptyString(str))
				{
					return 0;
				}

				var hash = 0;
				for (var i = 0; i < str.length; i++)
				{
					var c = str.charCodeAt(i);

					if (c > 0xFF)
					{
						c -= 0x350;
					}

					hash = ((hash << 5) - hash) + c;
					hash = hash & hash;
				}

				return hash;
			},

			getTagSelector: function()
			{
				if (!this.opts.dialog)
				{
					this.opts.dialog = new BX.UI.EntitySelector.Dialog({
						id: 'tasksTagSelector',
						targetNode: this.control('form-control'),
						enableSearch: true,
						width: 350,
						height: 400,
						multiple: true,
						dropdownMode: true,
						compactView: true,
						context: this.option('isScrumTask') ? 'TASKS_SCRUM_TAG_' + this.option('groupId') : 'TASKS_TAG',
						entities: [
							{
								id: 'task-tag',
								options: {
									taskId: this.option('taskId'),
									groupId: this.option('isScrumTask') ? this.option('groupId') : 0
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
										entityId: 'task-tag',
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
								if (this.opts.dialogCallback === false)
								{
									return;
								}

								this.onTagsChange(event);
							}.bind(this),
							'Item:onDeselect': function (event) {
								if (this.opts.dialogCallback === false)
								{
									return;
								}

								this.onTagsChange(event);
							}.bind(this),
						}
					});
				}
				return this.opts.dialog;
			}
		}
	});

}).call(this);
