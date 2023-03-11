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
			itemFxHoverDelete: true
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.ItemSet);
				this.preselectedItems = this.option('data');
			},

			openAddForm: function(node)
			{
				this.getDialog(node).show();
			},

			getDialog: function(node)
			{
				var getTargetContainer = function()
				{
					var fields = document.querySelectorAll('div.task-options-item-open-inner');
					var target = node
					fields.forEach(function(field){
						if (field.contains(target))
						{
							target = field;
						}
					});

					return target;
				}.bind(this);

				if (!this.dialog)
				{
					this.dialog = new BX.UI.EntitySelector.Dialog({
						id: 'tasks-widget-tag-selector-template-edit',
						targetNode: getTargetContainer(),
						enableSearch: true,
						width: 350,
						height: 400,
						multiple: true,
						dropdownMode: true,
						compactView: true,
						entities: [
							{
								id: 'template-tag',
								options: {},
								dynamicLoad: true,
								dynamicSearch: true,
							}
						],
						selectedItems: this.preselectedItems.map(function(tag) {
							return {
								id: tag.NAME,
								entityId: 'template-tag',
								title: tag.NAME,
								tabs: 'all'
							};
						}),
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
										entityId: 'template-tag',
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
							'Item:onSelect': this.onTagsChange.bind(this),
							'Item:onDeselect': this.onTagsChange.bind(this)
						}
					});
				}

				return this.dialog;
			},

			onTagsChange: function()
			{
				var displayedItems = [];
				this.each(function(item) {
					displayedItems.push(item.display());
				});

				var tags = this.getDialog().getSelectedItems().map(function(item) {
					return item.getTitle();
				});
				tags.forEach(function(tag) {
					if (!BX.util.in_array(tag, displayedItems))
					{
						this.addItem({NAME: tag});
					}
				}.bind(this));

				this.each(function(item) {
					if (!BX.util.in_array(item.display(), tags))
					{
						this.deleteItem(item.value());
					}
				});
			},

			onItemDeleteByCross: function(value)
			{
				this.callMethod(BX.Tasks.Util.ItemSet, 'onItemDeleteByCross', arguments);

				var itemData = value.data();

				this.deleteFromPreselected(itemData.VALUE.toString());
				this.deselectDialogItem(itemData);
			},

			deleteFromPreselected: function(id)
			{
				var itemToDelete = this.preselectedItems.findIndex(function(item) {
					return (this.extractItemValue(item) === id);
				}.bind(this));
				if (itemToDelete !== -1)
				{
					this.preselectedItems.splice(itemToDelete, 1);
				}
			},

			deselectDialogItem: function(item)
			{
				if (this.dialog)
				{
					var dialogItem = this.getDialog().getItem(this.prepareDialogItems([item])[0]);
					dialogItem && dialogItem.deselect();
				}
			},

			prepareDialogItems: function(items)
			{
				return items.map(function(item) {
					return ['task-tag', this.extractItemDisplay(item)];
				}.bind(this));
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
			}
		}
	});

}).call(this);