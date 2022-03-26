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
	BX.Tasks.Component.TasksWidgetTemplateAccess = BX.Tasks.Component.extend({
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

	BX.Tasks.Component.TasksWidgetTemplateAccess.ItemManager = BX.Tasks.Util.ItemSet.extend({
		sys: {
			code: 'rights-is'
		},
		options: {
			controlBind: 'class',
			useSmartCodeNaming: true
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.ItemSet);
				this.preselectedItems = this.option('data');
			},

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
				item.data().PERMISSION_ID = levelId;
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
				this.setField('PERMISSION_ID', data, first.ID);
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
				this.getDialog().show();
			},

			getDialog: function()
			{
				if (!this.dialog)
				{
					this.dialog = new BX.UI.EntitySelector.Dialog({
						targetNode: this.control('open-form'),
						enableSearch: true,
						multiple: true,
						hideOnSelect: true,
						context: 'TASKS_RIGHTS',
						searchOptions: {
							allowCreateItem: false,
						},
						preload: true,
						preselectedItems: this.prepareDialogItems(this.preselectedItems),
						entities: [
							{
								id: 'user',
								options: {
									inviteGuestLink: false,
									inviteEmployeeLink: false
								}
							},
							{
								id: 'project',
							},
							{
								id: 'department',
								options: {
									selectMode: 'usersAndDepartments'
								}
							},
							{
								id: 'meta-user',
								options: {
									'all-users': true
								}
							},
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

				return this.dialog;
			},

			prepareDialogItems: function(items)
			{
				var entityTypes = {
					U: 'user',
					SG: 'project',
					DR: 'department',
					UA: 'meta-user'
				};

				return items.map(
					function(item) {
						var entityId = item.MEMBER_ID;
						var entityType = entityTypes[item.MEMBER_TYPE];

						if (
							entityType === entityTypes.DR
							&& entityId.toString() === this.optionP('mainDepartment').ID.toString()
						)
						{
							entityId = 'all-users';
							entityType = entityTypes.UA;
						}

						return [entityType, entityId];
					}.bind(this)
				);
			},

			onSelectorItemSelected: function(dialogItem)
			{
				dialogItem = this.prepareItemData(dialogItem);
				dialogItem = this.changeAllEmployeesToMainDepartment(dialogItem);

				var paths = {
					'U': BX.message('path_user').replace('#user_id#', dialogItem.id),
					'SG': BX.message('path_group').replace('#group_id#', dialogItem.id),
					'DR': BX.message('path_department').replace('#ID#', dialogItem.id)
				};
				var id = dialogItem.entityType + dialogItem.id;

				if (!this.isItemAdded(id))
				{
					dialogItem.MEMBER_ID = dialogItem.id;
					dialogItem.MEMBER_TYPE = dialogItem.entityType;
					dialogItem.URL = paths[dialogItem.entityType];
					delete(dialogItem.id);

					this.addItem(dialogItem);
				}
			},

			onSelectorItemDeselected: function(dialogItem)
			{
				dialogItem = this.prepareItemData(dialogItem);
				dialogItem = this.changeAllEmployeesToMainDepartment(dialogItem);

				var id = dialogItem.entityType + dialogItem.id;
				if (this.isItemAdded(id))
				{
					var item = Object.values(this.vars.items).find(
						function(item) {
							var itemData = item.data();
							return (id.toString() === itemData.MEMBER_TYPE.toString() + itemData.MEMBER_ID.toString());
						}
					);
					if (item)
					{
						this.callMethod(BX.Tasks.Util.ItemSet, 'deleteItem', [item]);
						this.deleteFromAddedItems(id);
					}
				}
			},

			prepareItemData: function(item)
			{
				var customData = item.getCustomData();
				var entityType = item.getEntityType();
				var entityIds = {
					user: 'U',
					project: 'SG',
					department: 'DR',
					'meta-user': 'UA'
				};

				return {
					avatar: item.getAvatar(),
					description: customData.get('position'),
					email: customData.get('email'),
					entityType: entityIds[item.getEntityId()],
					id: item.getId(),
					lastName: customData.get('lastName'),
					name: customData.get('name'),
					nameFormatted: BX.Text.encode(item.getTitle()),
					networkId: '',
					type: {
						crmemail: false,
						extranet: (entityType === 'extranet'),
						email: (entityType === 'email'),
						network: (entityType === 'network')
					}
				};
			},

			changeAllEmployeesToMainDepartment: function(item)
			{
				if (item.entityType === 'UA')
				{
					var mainDepartment = this.optionP('mainDepartment');

					item.id = mainDepartment.ID;
					item.entityType = 'DR';
					item.nameFormatted = mainDepartment.NAME;
				}

				return item;
			},

			isItemAdded: function(id)
			{
				return this.parent().vars.addedItems.includes(id);
			},

			addItem: function(data)
			{
				var id = data.MEMBER_TYPE.toString() + data.MEMBER_ID.toString();

				this.callMethod(BX.Tasks.Util.ItemSet, 'addItem', arguments);
				this.addToAddedItems(id);
			},

			addToAddedItems: function(id)
			{
				this.parent().vars.addedItems.push(id);
			},

			deleteItem: function(value)
			{
				var itemData = value.data();
				var idToDelete = itemData.MEMBER_TYPE.toString() + itemData.MEMBER_ID.toString();

				this.callMethod(BX.Tasks.Util.ItemSet, 'deleteItem', arguments);
				this.deleteFromAddedItems(idToDelete);
				this.deleteFromPreselected(idToDelete);
				this.deselectDialogItem({
					MEMBER_ID: itemData.MEMBER_ID,
					MEMBER_TYPE: itemData.MEMBER_TYPE
				});
			},

			deleteFromAddedItems: function(id)
			{
				var key = this.parent().vars.addedItems.indexOf(id);
				this.parent().vars.addedItems.splice(key, 1);
			},

			deleteFromPreselected: function(id)
			{
				var itemToDelete = this.preselectedItems.findIndex(
					function(item) {
						return (item.MEMBER_TYPE.toString() + item.MEMBER_ID.toString() === id);
					}
				);
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
			}
		}
	});

}).call(this);