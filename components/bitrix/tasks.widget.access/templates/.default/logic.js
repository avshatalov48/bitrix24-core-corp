'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetRights != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetAccess = BX.Tasks.Component.extend({
		sys: {
			code: 'rights'
		},
		methods: {

			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);

				// todo: i wish we have automatically-created collections, to get rid of such code:
				var firstLevel = null;
				BX.Tasks.each(this.option('levels'), function(level){
					firstLevel = level;
					return false;
				});

				this.vars.firstLevel = firstLevel;
				this.vars.addedItems = [];

				this.getManager(); // just init
			},

			getManager: function()
			{
				return this.subInstance('items', function(){
					return new this.constructor.ItemManager({
						scope: this.scope(),
						preRendered: true,
						data: this.option('data'),
						parent: this
					});
				});
			}
		}
	});

	BX.Tasks.Component.TasksWidgetAccess.ItemManager = BX.Tasks.Util.ItemSet.extend({
		sys: {
			code: 'rights-is'
		},
		options: {
			controlBind: 'class',
			useSmartCodeNaming: true
		},
		methods: {

			bindItemActions: function()
			{
				this.callMethod(BX.Tasks.Util.ItemSet, 'bindItemActions');

				this.bindOnItemEx('i-operation-title', 'click', this.onItemOperationClick.bind(this));
			},

			onItemOperationClick: function(item, node)
			{
				var menu = [];
				BX.Tasks.each(this.optionP('levels'), function(level){

					menu.push({
						enabled: true,
						text: level.TITLE,
						onclick: this.passCtx(this.doMenuAction),
						itemRef: item,
						levelId: level.ID,
						levelTitle: level.TITLE
					});

				}.bind(this));

				// todo: when we delete item, we also should remove its menu!
				BX.PopupMenu.show(
					this.id()+'-op-popup-'+item.value(),
					node,
					menu,
					{angle: true, position: 'right', offsetLeft: 40, offsetTop: 0}
				);
			},

			doMenuAction: function(menu, e, menuItem)
			{
				menu.popupWindow.close();
				this.setItemOperation(menuItem.itemRef, menuItem.levelId, menuItem.levelTitle);
			},

			setItemOperation: function(item, levelId, levelTitle)
			{
				item.data().TASK_ID = levelId;
				item.control('operation-title').innerHTML = levelTitle;
				item.control('operation').value = levelId;
			},

			extractItemValue: function(data)
			{
				if('VALUE' in data)
				{
					return data.VALUE;
				}
				data.VALUE = this.getRandomHash();

				return data.VALUE;
			},

			prepareData: function(data)
			{
				var first = this.parent().vars.firstLevel;

				this.setField('ID', data, '');
				this.setField('TITLE', data, first.TITLE);
				this.setField('TASK_ID', data, first.ID);
				this.setField('MEMBER_ID', data, data.id);
				this.setField('MEMBER_TYPE', data, first.MEMBER_TYPE);
				this.setField('DISPLAY', data, function(data)
				{
					if('nameFormatted' in data)
					{
						return BX.util.htmlspecialcharsback(data.nameFormatted); // socnetlogdest returns escaped name, we want unescaped
					}

					var nameTemplate = this.option('nameTemplate');
					if(nameTemplate)
					{
						var formatted = BX.formatName(data, nameTemplate, 'Y');
						if(formatted == 'Noname') // Noname - bad, login - good
						{
							formatted = data.LOGIN || data.login;
						}

						return formatted;
					}

					return data.LOGIN;
				});

				data.ITEM_SET_INVISIBLE = '';

				return data;
			},

			openAddForm: function()
			{
				this.getSelector().open()
			},

			getSelector: function()
			{
				return this.subInstance('socnet', function(){
					var selector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
						scope: this.control('open-form'),
						id: this.id()+'socnet-sel',
						mode: 'all',
						useSearch: true,
						useAdd: false,
						controlBind: this.option('controlBind'),
						parent: this,
						popupOffsetTop: 5,
						popupOffsetLeft: 40,
						lastSelectedContext: 'TASKS_RIGHTS'
					});
					selector.bindEvent('item-selected', this.onSelectorItemSelected.bind(this));

					return selector;
				});
			},

			addItem: function(data)
			{
				var id = data.MEMBER_TYPE.toString() + data.MEMBER_ID.toString();

				this.callMethod(BX.Tasks.Util.ItemSet, 'addItem', arguments);
				this.addToAddedItems(id);
			},

			deleteItem: function(value)
			{
				var itemData = this.getItem(value.value()).opts.data;
				var idToDelete = itemData.MEMBER_TYPE.toString() + itemData.MEMBER_ID.toString();
				var idToDeselect = idToDelete;

				if (this.checkMainDepartmentDeselecting(itemData))
				{
					idToDeselect = 'UA';
				}

				this.callMethod(BX.Tasks.Util.ItemSet, 'deleteItem', arguments);
				this.getSelector().deselectItem(idToDeselect);
				this.deleteFromAddedItems(idToDelete);
			},

			checkMainDepartmentDeselecting: function(data)
			{
				var mainDepartment = this.optionP('mainDepartment');
				return data.MEMBER_TYPE.toString() + data.MEMBER_ID.toString() === 'DR' + mainDepartment.ID;
			},

			changeAllEmployeesToMainDepartment: function(data)
			{
				if (data.entityType === 'UA')
				{
					var mainDepartment = this.optionP('mainDepartment');

					data.id = mainDepartment.ID;
					data.entityType = 'DR';
					data.nameFormatted = mainDepartment.NAME;
				}

				return data;
			},

			onSelectorItemSelected: function(data)
			{
				data = this.changeAllEmployeesToMainDepartment(data);

				var paths = {
					'U': BX.message('path_user').replace('#user_id#', data.id),
					'SG': BX.message('path_group').replace('#group_id#', data.id),
					'DR': BX.message('path_department').replace('#ID#', data.id)
				};
				var id = data.entityType + data.id;

				if (!this.isItemAdded(id))
				{
					data.MEMBER_ID = data.id;
					data.MEMBER_TYPE = data.entityType;
					data.URL = paths[data.entityType];

					delete(data.id);

					this.addItem(data);

					// deselect it again
					this.getSelector().close();
				}
			},

			isItemAdded: function(id)
			{
				return this.parent().vars.addedItems.includes(id);
			},

			addToAddedItems: function(id)
			{
				this.parent().vars.addedItems.push(id);
			},

			deleteFromAddedItems: function(id)
			{
				var key = this.parent().vars.addedItems.indexOf(id);
				this.parent().vars.addedItems.splice(key, 1);
			}
		}
	});

}).call(this);