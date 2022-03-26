'use strict';

BX.namespace('Tasks.Component');

(function() {
	if (typeof BX.Tasks.Component.TasksWidgetRelatedSelector != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetRelatedSelector = BX.Tasks.Component.extend({
		sys: {
			code: 'task-sel-is'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);

				var manager = this.getManager();
				manager.bindEvent('change', this.onChanged.bind(this));

				this.setTypes(this.option('types'), true);
			},

			onChanged: function(items)
			{
				// forward 'change' event
				this.fireEvent('change', arguments);
			},

			getIdByValue: function(value)
			{
				var item = this.getManager().get(value);
				if (item)
				{
					return item.id();
				}

				return null;
			},

			setTypes: function(types, initial)
			{
				this.vars.types = this.vars.types || {};

				if (!initial && this.vars.types.TASK === types.TASK) // already chosen
				{
					return;
				}

				this.vars.types = {};
				BX.Tasks.each(types, function(item, k) {
					if (k === 'TASK' || k === 'TASK_TEMPLATE')
					{
						this.vars.types[k] = true;
					}
				}.bind(this));

				this.changeTypes(types, initial);
			},

			changeTypes: function(types, initial)
			{
				var ctrl = this.subInstance('items');

				if (!initial)
				{
					// dropping all for now
					ctrl.unload();
				}

				ctrl.option('selectorCode', this.option(types.TASK ? 'selectorCodeTask' : 'selectorCodeTaskTemplate'));
				ctrl.option('types', types);
				ctrl.option('path', this.option(types.TASK ? 'pathTask' : 'pathTaskTemplate'));
			},

			getManager: function()
			{
				return this.subInstance('items', function() {
					return new this.constructor.ItemManager({
						scope: this.scope(),
						data: this.option('data'),
						multiple: (this.option('max') > 1),
						preRendered: true,
						templateSubtaskLimitExceeded: this.option('templateSubtaskLimitExceeded')
					});
				});
			}
		}
	});

	BX.Tasks.Component.TasksWidgetRelatedSelector.ItemManager = BX.Tasks.Util.ItemSet.extend({
		sys: {
			code: 'task-sel'
		},
		options: {
			controlBind: 'class',
			itemFx: 'horizontal',
			selectorCode: '',
			itemFxHoverDelete: true,
			types: {}
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.ItemSet);
				this.preselectedItems = this.option('data');
			},

			openAddForm: function()
			{
				if (this.option('templateSubtaskLimitExceeded'))
				{
					BX.UI.InfoHelper.show('limit_tasks_templates_subtasks', {
						isLimit: true,
						limitAnalyticsLabels: {
							module: 'tasks',
							source: 'templateEdit'
						}
					});
					return;
				}

				this.getDialog().show();
			},

			getDialog: function()
			{
				if (this.getType() === 'T')
				{
					return this.getTasksDialog();
				}

				return this.getTemplatesDialog();
			},

			getTasksDialog: function()
			{
				if (!this.tasksDialog)
				{
					this.tasksDialog = new BX.UI.EntitySelector.Dialog({
						targetNode: this.scope(),
						enableSearch: true,
						multiple: this.option('multiple'),
						dropdownMode: true,
						compactView: true,
						hideOnSelect: !this.option('multiple'),
						hideOnDeselect: !this.option('multiple'),
						context: 'TASKS_TASKS',
						searchOptions: {
							allowCreateItem: false,
						},
						preselectedItems: this.prepareDialogItems(this.preselectedItems),
						entities: [
							{
								id: 'task'
							}
						],
						events: {
							'Item:onSelect': function(event) {
								this.onSelectorItemSelected(event.getData().item);
							}.bind(this),
							'Item:onDeselect': function(event) {
								this.onSelectorItemDeselected(event.getData().item);
							}.bind(this)
						}
					});
				}

				return this.tasksDialog;
			},

			getTemplatesDialog: function()
			{
				if (!this.templatesDialog)
				{
					this.templatesDialog = new BX.UI.EntitySelector.Dialog({
						targetNode: this.scope(),
						enableSearch: true,
						multiple: this.option('multiple'),
						dropdownMode: true,
						compactView: true,
						hideOnSelect: !this.option('multiple'),
						hideOnDeselect: !this.option('multiple'),
						context: 'TASKS_TEMPLATES',
						searchOptions: {
							allowCreateItem: false,
						},
						preselectedItems: this.prepareDialogItems(this.preselectedItems),
						entities: [
							{
								id: 'task-template'
							}
						],
						events: {
							'Item:onSelect': function(event) {
								this.onSelectorItemSelected(event.getData().item);
							}.bind(this),
							'Item:onDeselect': function(event) {
								this.onSelectorItemDeselected(event.getData().item);
							}.bind(this)
						}
					});
				}

				return this.templatesDialog;
			},

			onSelectorItemSelected: function(dialogItem)
			{
				this.addItem(this.prepareItemData(dialogItem));
			},

			onSelectorItemDeselected: function(dialogItem)
			{
				dialogItem = this.prepareItemData(dialogItem);

				var id = (this.getType() + dialogItem.id);
				var item = Object.values(this.vars.items).find(function(item) {
					return (id.toString() === item.data().VALUE);
				});
				if (item)
				{
					this.callMethod(BX.Tasks.Util.ItemSet, 'deleteItem', [item]);
				}
			},

			prepareItemData: function(item)
			{
				return {
					id: item.getId(),
					name: item.getTitle(),
				};
			},

			prepareDialogItems: function(items)
			{
				var type = (this.getType() === 'T' ? 'task' : 'task-template');

				return items.map(function(item) {
					return [type, item.ID];
				});
			},

			extractItemDisplay: function(data)
			{
				if (typeof data.DISPLAY != 'undefined')
				{
					return data.DISPLAY;
				}

				if (typeof data.name != 'undefined')
				{
					return data.name;
				}

				return data.TITLE;
			},

			extractItemValue: function(data)
			{
				data.ID = (data.ID || data.id);

				if ('VALUE' in data)
				{
					return data.VALUE;
				}

				return (this.getType() + data.ID);
			},

			getType: function()
			{
				return (this.option('types').TASK ? 'T' : 'TT');
			},

			prepareData: function(data)
			{
				if (!('URL' in data))
				{
					data.URL = this.option('path').toString().replace('#id#', data.ID);
				}

				return data;
			},

			deleteItem: function(value, parameters)
			{
				if (BX.type.isString(value))
				{
					value = this.get(value);
				}

				var item = value.data();

				this.callMethod(BX.Tasks.Util.ItemSet, 'deleteItem', arguments);
				this.deleteFromPreselected(item);
				this.deselectDialogItem(item);
			},

			deleteFromPreselected: function(item)
			{
				var itemToDelete = this.preselectedItems.findIndex(function(preselectedItem) {
					return (preselectedItem.VALUE.toString() === item.VALUE.toString());
				});
				if (itemToDelete !== -1)
				{
					this.preselectedItems.splice(itemToDelete, 1);
				}
			},

			deselectDialogItem: function(item)
			{
				if (
					(this.getType() === 'T' && this.tasksDialog)
					|| (this.getType() === 'TT' && this.templatesDialog)
				)
				{
					var dialogItem = this.getDialog().getItem(this.prepareDialogItems([item])[0]);
					dialogItem && dialogItem.deselect();
				}
			}
		}
	});
}).call(this);