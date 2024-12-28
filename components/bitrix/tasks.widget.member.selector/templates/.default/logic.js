'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetMemberSelector != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetMemberSelector = BX.Tasks.Component.extend({
		sys: {
			code: 'tdp-mem-sel',
			types: []
		},
		methodsStatic: {
			instances: [],

			getInstance: function(name)
			{
				return BX.Tasks.Component.TasksWidgetMemberSelector.instances[name];
			},

			addInstance: function(name, obj)
			{
				BX.Tasks.Component.TasksWidgetMemberSelector.instances[name] = obj;
			}
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
				BX.Tasks.Component.TasksWidgetMemberSelector.addInstance(this.id(), this);

				this.getSelector();

				if(this.option('inputSpecial'))
				{
					this.getSelector().bindEvent('change', this.onChanged.bind(this));
				}

				var self = this;

				if (this.option('userType') === 'auditor' || this.option('userType') === 'accomplice')
				{
					BX.Event.EventEmitter.subscribe(
						'BX.Tasks.CheckListItem:' + this.option('userType') + 'Added',
						function(data)
						{
							self.getSelector().onSelectorItemSelected(data.data);
						}
					);
				}

				BX.Event.EventEmitter.subscribe(
					'BX.Tasks.MemberSelector:' + this.option('userType') + 'Selected',
					function(data)
					{
						self.getSelector().onControlItemSelected(data.data);
					}
				);

				BX.Event.EventEmitter.subscribe(
					'BX.Tasks.MemberSelector:' + this.option('userType') + 'Deselected',
					function(data)
					{
						self.getSelector().onSelectorItemDeselected(data.data);
					}
				);
			},

			onChanged: function(items)
			{
				var value = '';
				var project = '';
				if (items[0])
				{
					value = this.getSelector().get(items[0]).id();
				}
				this.control('sole-input').value = value;

				//SG - group prefix
				if(items[0] && items[0].substr(0, 2) === 'SG')
				{
					project = this.getSelector().get(items[0]).opts.data.DISPLAY;
					BX.onCustomEvent(this, 'onProjectChanged', {
						groupId: items,
						owner: project,
					});
				}
				else
				{
					BX.onCustomEvent(this, 'onProjectChanged', {});
				}
			},

			getSelector: function()
			{
				return this.subInstance('selector', function(){

					var options = {
						loc: this.option('loc'),

						scope: this.scope(),
						hidePreviousIfSingleAndRequired: true,
						data: this.option('data'),
						max: this.option('max'),
						min: this.option('min'),
						nameTemplate: this.option('nameTemplate'),
						path: this.option('path'),
						preRendered: true,

						popupOffsetTop: 3,
						popupOffsetLeft: 40,

						readOnly: this.option('readOnly'),
						parent: this,

						userType: this.option('userType'),
						taskLimitExceeded: this.option('taskLimitExceeded'),
						viewSelectorEnabled: this.option('viewSelectorEnabled'),
						taskMailUserIntegrationEnabled: this.option('taskMailUserIntegrationEnabled'),
						taskMailUserIntegrationFeatureId: this.option('taskMailUserIntegrationFeatureId'),
						networkEnabled: this.option('networkEnabled'),
						isProjectLimitExceeded: this.option('isProjectLimitExceeded'),
						projectFeatureId: this.option('projectFeatureId'),
						entityId: this.option('entityId'),

						isCollaber: this.option('isCollaber'),
						isNeedShowPreselectedCollabHint: this.option('isNeedShowPreselectedCollabHint'),
					};

					var types = this.option('types');

					// todo: setting both 'user' and 'group' is not implemented
					// todo: setting 'depratment' is not implemented
					options.mode = types.USER ? 'user' : 'group';
					options.useAdd = !!types['USER.MAIL'] && this.option('modulesAvailable').mail;

					var selector = new this.constructor.ItemManager(options);

					// proxy 'change' event of the aggregated controller
					selector.bindEvent('change', function ProxyChangeEvent(){
						this.fireEvent('change', [arguments[0]]);
					}.bind(this));

					return selector;
				});
			},

			count: function()
			{
				return this.getSelector().count();
			},

			export: function()
			{
				return this.getSelector().exportItemData(true);
			},

			replaceItem: function(value, data)
			{
				this.getSelector().replaceItem(value, data);
			},

			value: function()
			{
				return this.getSelector().value();
			},

			replaceAll: function(data) {
				var selector = this.getSelector();
				selector.unload({
					itemFx: false, // no effects
					checkRestrictions: false // no restrictions: i know what i am doing, i am code, not a user
				});
				selector.load(data);
			},

			readOnly: function(flag)
			{
				this.getSelector().readonly(flag);
			}
		}
	});

	BX.Tasks.Component.TasksWidgetMemberSelector.ItemManager = BX.Tasks.UserItemSet.extend({
		dialog: null,
		dialogCallback: true,
		sys: {
			code: 'tdp-mem-sel-is'
		},
		options: {
			controlBind: 'class',
			itemFx: 'horizontal',
			itemFxHoverDelete: true,
			prefixId: true,
			mode: 'all', // users, groups and departments selected
			path: {},

			// hacky flag, allows to hide previously selected item when picking a new one in (single mode (max == 1) and required mode (min > 0))
			hidePreviousIfSingleAndRequired: false
		},
		methods: {

			construct: function()
			{
				this.callConstruct(BX.Tasks.UserItemSet);
				this.initDialog();
			},

			getDialog: function()
			{
				if (this.dialog)
				{
					return this.dialog;
				}

				this.dialog = new BX.UI.EntitySelector.Dialog({
					id: 'tasksMemberSelector_' + this.option('userType'),
					enableSearch: true,
					multiple: this.option('max') > 1,
					context: 'TASKS_MEMBER_SELECTOR_EDIT_' + this.option('userType'),
					entities: this.getDialogEntities(),
					preselectedItems: this.getDialogSelectedItems(),
					autoHide: true,
					autoHideHandler: function(event) {
						if (!BX.Dom.hasClass(event.target, 'task-form-field-item-delete'))
						{
							return true;
						}

						var itemNode = event.target.parentElement;
						var item = this.getItemByNode(itemNode);

						return (!item || item.detectScope() !== itemNode);
					}.bind(this),
					hideOnSelect: (this.option('userType') === 'responsible'),
					events: {
						'Item:onSelect': function(event) {
							if (this.dialogCallback === false)
							{
								return;
							}

							var item = event.getData().item;
							var userData = this.prepareUserData(item);

							BX.Event.EventEmitter.emit(
								'BX.Tasks.MemberSelector:' + this.option('userType') + 'Selected',
								userData
							);

							this.rerenderTitle(item);
						}.bind(this),
						'Item:onDeselect': function(event) {
							if (this.dialogCallback === false)
							{
								return;
							}

							if(
								this.option('hidePreviousIfSingleAndRequired')
								&& this.vars.constraint.min === 1
								&& this.count() === 1
							)
							{
								this.forceDeleteFirst();
							}
							else
							{
								var item = event.getData().item;
								var userData = this.prepareUserData(item);
								BX.Event.EventEmitter.emit(
									'BX.Tasks.MemberSelector:' + this.option('userType') + 'Deselected',
									userData
								);
							}
						}.bind(this),
						'onHide': function() {
							if (
								this.option('hidePreviousIfSingleAndRequired')
								&& this.vars.constraint.min > 0
								&& this.count() < 1
							)
							{
								this.restoreKept();
							}
						}.bind(this)
					}
				});

				return this.dialog;
			},

			showPreselectedCollabHint: function(target)
			{
				BX.Runtime.loadExtension('popup').then(() => {
					const popupTile = this.option('loc')?.preselectedCollabHint;
					const popup = BX.PopupWindowManager.create('tasks-preselected-collab-hint', target, {
						offsetLeft: 30,
						angle: true,
						closeByEsc: true,
						closeIcon: { top: '2px', right: '2px' },
						autoHide: true,

						content: `<div class="tasks-preselected-collab-popup-title">${popupTile}</div>`,
					});

					const changePopupPositionListener = () => {
						const getBindElement = () => {
							const rect = target.getBoundingClientRect();
							const x = rect.left + window.pageXOffset;
							const y = rect.bottom + window.pageYOffset;

							return { left: x, top: y };
						};

						const position = getBindElement();
						if (position.left === 0 && position.top === 0)
						{
							return;
						}

						const editor = document.querySelector('.bx-html-editor');
						if (!editor)
						{
							return;
						}

						const resizeObserver = new ResizeObserver((entry) => {
							popup.setBindElement(getBindElement());
							popup.adjustPosition();
						});

						resizeObserver.observe(editor);

						popup.subscribe('onClose', () => {
							resizeObserver.unobserve(editor);
						});
					};

					changePopupPositionListener();
					popup.show();
				});
			},

			initDialog: function()
			{
				var targetNodes = this.scope().getElementsByClassName('js-id-tdp-mem-sel-is-control');

				for (var i = 0; i < targetNodes.length; i++)
				{
					var node = targetNodes[i];
					if (this.option('isNeedShowPreselectedCollabHint'))
					{
						this.showPreselectedCollabHint(node);
					}

					node.addEventListener('click', function(event) {
						var userType = this.option('userType');
						var taskLimitExceeded = this.option('taskLimitExceeded');
						var viewSelectorEnabled = this.option('viewSelectorEnabled');

						if (
							(userType === 'accomplice' || userType === 'auditor')
							&& (taskLimitExceeded || !viewSelectorEnabled)
						)
						{
							this.showLimit('tasks_observers_participants', event.target);

							return;
						}

						if (
							userType === 'project'
							&& this.option('isProjectLimitExceeded')
							&& !this.option('isCollaber')
						)
						{
							this.showLimit(this.option('projectFeatureId'));

							return;
						}

						this.getDialog().setTargetNode(event.target.closest('.task-options-item-open-inner'));
						this.getDialog().show();
					}.bind(this));
				}
			},

			showLimit(featureId, bindElement = null)
			{
				BX.Runtime.loadExtension('tasks.limit').then((exports) => {
					const { Limit } = exports;
					Limit.showInstance({
						featureId,
						bindElement,
						limitAnalyticsLabels: {
							module: 'tasks',
							source: 'edit',
						},
					});
				});
			},

			getDialogSelectedItems: function()
			{
				var data = this.option('data');
				var items = [];

				var value = null;
				for (var i = 0; i < data.length; i++)
				{
					value = this.prepareItemData(data[i]);
					if (value)
					{
						items.push(value);
					}
				}

				return items;
			},

			getDialogUndeselectedItems: function()
			{
				var data = this.option('data');
				var items = [];

				if (
					this.option('min') !== 1
					|| this.option('max') !== 1
				)
				{
					return;
				}

				var value = null;
				for (var i = 0; i < data.length; i++)
				{
					value = this.prepareItemData(data[i]);
					if (value)
					{
						items.push(value);
					}
				}

				return items;
			},

			getDialogEntities: function()
			{
				var mode = this.option('mode');
				var networkEnabled = this.option('networkEnabled');
				var entities = [];

				if (mode === 'user')
				{
					entities = [
						{
							id: 'user',
							options: {
								emailUsers: true,
								networkUsers: networkEnabled,
								extranetUsers: true,
								inviteGuestLink: true,
								myEmailUsers: true,
								analyticsSource: 'tasks',
								lockGuestLink: !this.option('taskMailUserIntegrationEnabled'),
								lockGuestLinkFeatureId: this.option('taskMailUserIntegrationFeatureId'),
							}
						},
						{
							id: 'department',
						}
					];
				}
				else if (mode === 'group')
				{
					entities = [
						{
							id: 'project',
							options: {
								shouldSelectDialogId: true,
							},
						},
					];
				}

				return entities;
			},

			prepareItemData: function(data)
			{
				var id = 0;

				if (typeof data === 'string')
				{
					id =  data.replace(/[A-Za-z]/gi, '');
				}
				else if (typeof data === 'object')
				{
					if (data.ID)
					{
						id = data.ID;
					}
					else if (data.id)
					{
						id = data.id;
					}
				}

				if (id <= 0)
				{
					return null;
				}

				var mode = this.option('mode');
				return [(mode === 'group') ? 'project' : 'user', id];
			},

			prepareUserData: function(user)
			{
				var customData = user.getCustomData();
				var entityType = user.getEntityType();
				var mode = this.option('mode');

				const data = {
					AVATAR: user.avatar,
					DESCRIPTION: '',
					ENTITY_TYPE: ((mode === 'group') ? 'SG' : 'U'),
					ID: user.getId(),
					NAME: customData.get('name'),
					LAST_NAME: customData.get('lastName'),
					EMAIL: customData.get('email'),
					nameFormatted: BX.Text.encode(user.getTitle()),
					NETWORK_ID: '',
					USER_TYPE: entityType,
					type: {
						crmemail: false,
						extranet: (entityType === 'extranet'),
						email: (entityType === 'email'),
						network: (entityType === 'network'),
						collab: (entityType === 'collab'),
						collaber: (entityType === 'collaber'),
					},
					VALUE: ((mode === 'group') ? 'SG' : 'U') + user.getId()
				};
				if (entityType === 'collab')
				{
					const dialogId = user.getCustomData().get('dialogId');
					if (dialogId)
					{
						data.URL = this.option('path').collab?.replace('#DIALOG_ID#', dialogId);
					}
					else
					{
						data.URL = '';
					}
				}
				else
				{
					data.URL = user.getLink();
				}

				return data;
			},

			onSearchBlurred: function()
			{
				var emailUserPopup = BX('invite-email-email-user-popup');
				if (emailUserPopup !== null && emailUserPopup.style.display === 'block')
				{
					return;
				}

				if (this.callMethod(BX.Tasks.UserItemSet, 'onSearchBlurred'))
				{
					if (this.option('hidePreviousIfSingleAndRequired') && this.vars.constraint.min > 0)
					{
						this.restoreKept();
					}
				}
			},

			onSelectorItemSelected: function(data)
			{
				if(this.option('hidePreviousIfSingleAndRequired') && this.vars.constraint.min > 0)
				{
					this.vars.changed = true;
					var value = this.extractItemValue(data);

					if(!this.hasItem(value))
					{
						this.addItem(data);
						this.vars.toDelete = false;

						if(!this.checkCanAddItems()) // can not add new items anymore - close search form
						{
							this.instances.selector.close();
							this.onSearchBlurred();
						}
					}

					this.resetInput();
				}
				else
				{
					this.callMethod(BX.Tasks.UserItemSet, 'onSelectorItemSelected', arguments);
				}
			},

			onControlItemSelected: function(data)
			{
				this.callMethod(BX.Tasks.UserItemSet, 'onSelectorItemSelected', arguments);
			},

			// link clicked
			openAddForm: function()
			{
				var userType = this.option('userType');
				var taskLimitExceeded = this.option('taskLimitExceeded');

				if ((userType === 'accomplice' || userType === 'auditor') && taskLimitExceeded)
				{
					BX.Runtime.loadExtension('tasks.limit').then((exports) => {
						const { Limit } = exports;
						Limit.showInstance({
							featureId: 'tasks_observers_participants',
							limitAnalyticsLabels: {
								module: 'tasks',
								source: 'taskEdit',
							},
						});
					});

					return;
				}

				if(this.option('hidePreviousIfSingleAndRequired')) // special behaviour
				{
					if(this.vars.constraint.min == 1 && this.vars.constraint.max == 1)
					{
						this.forceDeleteFirst();
					}
				}

				this.callMethod(BX.Tasks.UserItemSet, 'openAddForm');
			},

			// item "delete" cross clicked
			onItemDeleteByCross: function(value)
			{
				if (this.callMethod(BX.Tasks.UserItemSet, 'onItemDeleteByCross', arguments))
				{
					return true;
				}

				if (
					this.option('hidePreviousIfSingleAndRequired')
					&& this.vars.constraint.min === 1
					&& this.count() === 1
				)
				{
					this.forceDeleteFirst();
					this.getDialog().setTargetNode(this.scope());
					this.getDialog().show();
				}

				return false;
			},

			forceDeleteFirst: function()
			{
				var first = this.getItemFirst();
				if(first)
				{
					this.vars.toDelete = first.data();
					this.deleteItem(first.value(), {checkRestrictions: false});
				}
			},

			restoreKept: function()
			{
				if(this.vars.toDelete)
				{
					this.addItem(this.vars.toDelete, {checkRestrictions: false});
					this.vars.toDelete = false;
				}
			},

			addItem: function (value, parameters)
			{
				this.callMethod(BX.Tasks.UserItemSet, 'addItem', arguments);
				this.selectDialogItem(value);
			},

			deleteItem: function (value, parameters)
			{
				if (this.callMethod(BX.Tasks.UserItemSet, 'deleteItem', arguments))
				{
					this.unselectDialogItem(value);
					return true;
				}

				return false;
			},

			unselectDialogItem: function(value)
			{
				if (!this.getDialog())
				{
					return;
				}

				this.dialogCallback = false;

				if (typeof value === 'object')
				{
					value = value.data();
				}

				value = this.prepareItemData(value);
				if (value)
				{
					var item = this.getDialog().getItem(value);
					item && item.deselect();
				}
				this.dialogCallback = true;
			},

			selectDialogItem: function(value)
			{
				if (!this.getDialog())
				{
					return;
				}

				this.dialogCallback = false;

				value = this.prepareItemData(value);

				if (value)
				{
					var item = this.getDialog().getItem(value);
					item && item.select(true);
				}
				this.dialogCallback = true;
			},

			rerenderTitle(item)
			{
				if (item.getEntityId() !== 'project')
				{
					return;
				}

				const taskId = this.option('entityId');

				const titleNode = document.querySelector(`#task-${taskId}-group-title-edit`);

				if (!titleNode)
				{
					return;
				}

				if (item?.entityType === 'collab')
				{
					titleNode.textContent = this.option('loc')?.type?.collab;

					return;
				}

				titleNode.textContent = this.option('loc')?.type?.group;
			},
		}
	});

}).call(this);