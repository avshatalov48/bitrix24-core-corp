'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetMemberSelectorView != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetMemberSelectorView = BX.Tasks.Component.extend({
		sys: {
			code: 'mem-sel'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
				this.switchDeleteButtonShow();
			},

			bindEvents: function()
			{
				this.getManager();

				var roleMap = {
					'AUDITORS': 'auditor',
					'ACCOMPLICES': 'accomplice',
					'RESPONSIBLE': 'responsible',
					'RESPONSIBLES': 'responsible'
				};

				var self = this;

				if (this.option('role') === 'AUDITORS' || this.option('role') === 'ACCOMPLICES')
				{
					BX.Event.EventEmitter.subscribe(
						'BX.Tasks.CheckListItem:' + roleMap[this.option('role')] + 'Added',
						function(data)
						{
							self.getManager().onSelectorItemSelected(data.data);
							self.onChangeByUser();
						}
					);
				}

				if (roleMap[this.option('role')])
				{
					BX.Event.EventEmitter.subscribe(
						'BX.Tasks.MemberSelector:' + roleMap[this.option('role')] + 'Selected',
						function(data)
						{
							self.getManager().onSelectorItemSelected(data.data);
							self.onChangeByUser();
						}
					);

					BX.Event.EventEmitter.subscribe(
						'BX.Tasks.MemberSelector:' + roleMap[this.option('role')] + 'Deselected',
						function(data)
						{
							self.getManager().onSelectorItemDeselected(data.data);

							if (
								self.option('context') === 'template'
								|| self.option('role') === 'AUDITORS'
								|| self.option('role') === 'ACCOMPLICES'
							)
							{
								self.onChangeByUser();
							}
						}
					);
				}

				if (this.option('role') === 'AUDITORS')
				{
					BX.PULL.subscribe({
						type: BX.PullClient.SubscriptionType.Server,
						moduleId: 'tasks',
						command: 'task_update',
						callback: this.onPullTask.bind(this),
					});
				}

				if (this.option('role') === 'RESPONSIBLE')
				{
					BX.PULL.subscribe({
						type: BX.PullClient.SubscriptionType.Server,
						moduleId: 'tasks',
						command: 'task_update',
						callback: this.onPullTask.bind(this),
					});
				}
			},

			onPullTask: function(params, extra, command)
			{
				const taskId = this.recognizeTaskId(params);
				if (taskId !== parseInt(this.option('entityId'), 10))
				{
					return;
				}

				if (params.BEFORE?.RESPONSIBLE_ID !== params.AFTER?.RESPONSIBLE_ID)
				{
					this.resetResponsible(taskId);
				}

				this.resetAuditors(params.BEFORE?.AUDITORS, params.AFTER?.AUDITORS, taskId);
			},

			resetAuditors(auditorsBefore, auditorsAfter, taskId)
			{
				if (this.option('role') !== 'AUDITORS')
				{
					return;
				}

				let auditors = [];
				if (!auditorsBefore && auditorsAfter)
				{
					auditorsAfter = auditorsAfter.split(',').map(Number);
					auditors = auditorsAfter;
				}
				else if (auditorsBefore && auditorsAfter)
				{
					auditorsBefore = auditorsBefore.split(',').map(Number);
					auditorsAfter = auditorsAfter.split(',').map(Number);
					auditors = auditorsAfter.filter((auditor) => !auditorsBefore.includes(auditor));
				}

				if (auditors.length === 0)
				{
					return;
				}

				BX.ajax.runComponentAction('bitrix:tasks.widget.member.selector', 'getMember', {
						mode: 'class',
						data: {
							userIds: auditors,
							taskId,
						}
					})
					.then((response) => {
						const users = Array.from(response.data);
						const manager = this.getManager();
						const preselected = [];
						users.forEach((user) => {
							manager.onSelectorItemSelected(user);
							preselected.push(['user', user.id]);
						});
						manager.unsetDialog();
						manager.getDialog().setPreselectedItems(preselected);

						const readOnly = Boolean(this.option('readOnly'));

						if (readOnly)
						{
							const currentUser = Number(this.option('currentUser'));

							if (auditorsAfter.includes(currentUser))
							{
								this.setHeaderButtonLabelText(this.option('leaveAuditorMessage'));
								BX.Tasks.Component.TaskViewSidebarObject?.setAmIAuditorValue(true);
							}
							else
							{
								this.setHeaderButtonLabelText(this.option('enterAuditorMessage'));
								BX.Tasks.Component.TaskViewSidebarObject?.setAmIAuditorValue(false);
							}
						}
					});
			},

			resetResponsible(taskId)
			{
				if (this.option('role') !== 'RESPONSIBLE')
				{
					return;
				}

				BX.ajax.runComponentAction('bitrix:tasks.widget.member.selector', 'getResponsible', {
					mode: 'class',
					data: {
						taskId,
					},
				})
					.then((response) => {
						const user = response.data;
						const manager = this.getManager();
						manager.onSelectorItemSelected(user);
						manager.unsetDialog();
						manager.getDialog().setPreselectedItems([['user', user.id]]);
					})
				;
			},

			recognizeTaskId(pullData)
			{
				if ('TASK_ID' in pullData)
				{
					return parseInt(pullData.TASK_ID, 10);
				}

				if ('taskId' in pullData)
				{
					return parseInt(pullData.taskId, 10);
				}

				if (
					'entityXmlId' in pullData
					&& pullData.entityXmlId.indexOf('TASK_') === 0
				)
				{
					return parseInt(pullData.entityXmlId.slice(5), 10);
				}

				return 0;
			},

			setHeaderButtonLabelText: function(text)
			{
				this.control('header-button').innerHTML = BX.util.htmlspecialchars(text);
			},

			getManager: function()
			{
				return this.subInstance('mgr', function(){
					var mgr = new this.constructor.Manager({
						scope: this.scope(),
						data: this.option('data'),
						nameTemplate: this.option('nameTemplate'),

						min: this.option('min'),
						max: this.option('max'),

						flowId: this.option('flowId'),

						path: this.option('path'),

						role: this.option('role'),
						taskLimitExceeded: this.option('taskLimitExceeded'),
						viewSelectorEnabled: this.option('viewSelectorEnabled'),
						taskMailUserIntegrationEnabled: this.option('taskMailUserIntegrationEnabled'),
						taskMailUserIntegrationFeatureId: this.option('taskMailUserIntegrationFeatureId'),
						networkEnabled: this.option('networkEnabled'),
					});

					mgr.bindEvent('change-by-user', this.onChangeByUser.bind(this));

					return mgr;
				});
			},

			onChangeByUser: function()
			{
				if(this.option('enableSync'))
				{
					var id = parseInt(this.option('entityId'));
					var route = this.option('entityRoute');
					var fieldName = this.option('fieldName');

					if(!id || !route || !fieldName)
					{
						return;
					}

					var args = {
						id: id,
						data: {}
					};

					var data = [];
					var userIds = [];

					this.getManager().each(function(item){
						userIds.push(item.value());
						data.push({
							ID: item.value(),
							NAME: item.data().NAME,
							LAST_NAME: item.data().LAST_NAME,
							EMAIL: item.data().EMAIL
						});
					});

					var mngr = this.getManager();

					BX.ajax.runComponentAction('bitrix:tasks.widget.member.selector', 'isAbsence', {
						mode: 'class',
						data: {
							userIds: userIds
						}
					}).then(
						function(response)
						{
							if (
								!response.status
								|| response.status !== 'success'
							)
							{
								return;
							}
							if (!response.data.length)
							{
								return;
							}
							var text = response.data.reduce(function(sum, current)
							{
								return sum + '<br />' + current;
							});

							var popup = BX.PopupWindowManager.create(
								"popupMenuOptions",
								BX(mngr.scope()),
								{
									content: text,
									darkMode: true,
									autoHide: true,
									width: 200
								}
							);

							popup.show();
						}.bind(this),
						function(response)
						{

						}.bind(this)
					);

					args.data[fieldName] = data;

					this.sendSetMembersRequest(data);

					this.switchDeleteButtonShow();
				}
			},

			sendSetMembersRequest: function(data)
			{
				var actionMap = {
					'AUDITORS': 'setAuditors',
					'ACCOMPLICES': 'setAccomplices',
					'RESPONSIBLE': 'setResponsible',
					'RESPONSIBLES': 'setResponsible'
				};

				var taskId = this.option('entityId');
				var action = actionMap[this.option('role')];

				BX.ajax.runComponentAction('bitrix:tasks.widget.member.selector', action, {
					mode: 'class',
					data: {
						taskId: taskId,
						context: this.option('context') ?? '',
						data: data
					}
				}).then(
					function(response)
					{
						BX.Tasks.Util.fireGlobalTaskEvent('UPDATE', {ID: taskId}, {STAY_AT_PAGE: true}, {id: taskId});
					}.bind(this)
				).catch(
					function(response)
					{
						if (response.errors)
						{
							BX.Tasks.alert(response.errors);
						}
					}.bind(this)
				);
			},

			addItem: function(data)
			{
				this.getManager().addItem(data);
			},

			deleteItem: function(data)
			{
				this.getManager().deleteItem(this.getManager().extractItemValue(data));
			},

			switchDeleteButtonShow: function(beforeDelete)
			{
				if (this.option('min') !== 1)
				{
					return;
				}

				var crosses = document.getElementsByClassName('js-id-mem-sel-is-i-delete');

				if (beforeDelete)
				{
					if (crosses.length === 2)
					{
						Object.keys(crosses).forEach(function(key)
						{
							BX.addClass(crosses[key], 'hidden');
						});
					}
				}
				else
				{
					if (crosses.length === 1)
					{
						BX.addClass(crosses[0], 'hidden');
					}
					else
					{
						Object.keys(crosses).forEach(function(key)
						{
							BX.removeClass(crosses[key], 'hidden');
						});
					}
				}
			}
		}
	});

	BX.Tasks.Component.TasksWidgetMemberSelectorView.Manager = BX.Tasks.UserItemSet.extend({
		dialog: null,
		sys: {
			code: 'mem-sel-is'
		},
		options: {
			preRendered: true,
			autoSync: true,
			role: false,
			multiple: false,
			useSearch: true,
			forceTop: true,
			useAdd: true,
			controlBind: 'class',
			itemFx: 'vertical',
			useSmartCodeNaming: true
		},
		methods: {

			construct: function()
			{
				this.callConstruct(BX.Tasks.UserItemSet);

				this.fireUserTriggeredChangeDebounce = BX.debounce(this.fireUserTriggeredChangeDebounce, 800);

				this.initDialog();
			},

			getDialog: function()
			{
				if (this.dialog)
				{
					return this.dialog;
				}

				this.dialog = new BX.UI.EntitySelector.Dialog({
					enableSearch: true,
					multiple: this.option('max') > 1,
					context: 'TASKS_MEMBER_SELECTOR_VIEW_' + this.option('role'),
					entities: this.getDialogEntities(),
					preselectedItems: this.getDialogSelectedItems(),
					undeselectedItems: this.getDialogUndeselectedItems(),
					dropdownMode: this.option('flowId') > 0,
					events: {
						'Item:onSelect': function(event) {
							var item = event.getData().item;
							var userData = this.prepareUserData(item);

							var events = {
								ACCOMPLICES: 'accomplice',
								AUDITORS: 'auditor',
								RESPONSIBLE: 'responsible',
								RESPONSIBLES: 'responsible'
							}

							BX.Event.EventEmitter.emit('BX.Tasks.MemberSelector:'+ events[this.option('role')] +'Selected', userData);
						}.bind(this),
						'Item:onDeselect': function(event)
						{
							var item = event.getData().item;
							var userData = this.prepareUserData(item);

							var events = {
								ACCOMPLICES: 'accomplice',
								AUDITORS: 'auditor',
								RESPONSIBLE: 'responsible',
								RESPONSIBLES: 'responsible'
							}

							BX.Event.EventEmitter.emit('BX.Tasks.MemberSelector:'+ events[this.option('role')] +'Deselected', userData);
						}.bind(this)
					}
				});

				return this.dialog;
			},

			initDialog: function()
			{
				var targetNodes = this.scope().getElementsByClassName('js-id-mem-sel-is-control');
				for (var i = 0; i < targetNodes.length; i++)
				{
					const targetNode = targetNodes[i];
					targetNode.addEventListener('click', function(node, event) {
						var userType = this.option('role');
						var viewSelectorEnabled = this.option('viewSelectorEnabled');

						if (
							(userType === 'ACCOMPLICES' || userType === 'AUDITORS')
							&& !viewSelectorEnabled
						)
						{
							BX.Runtime.loadExtension('tasks.limit').then((exports) => {
								const { Limit } = exports;
								Limit.showInstance({
									featureId: 'tasks_observers_participants',
									limitAnalyticsLabels: {
										module: 'tasks',
										source: 'sidebar',
									},
									bindElement: node,
								});
							});

							return;
						}

						this.getDialog().setTargetNode(event.target.parentNode);
						this.getDialog().show();
					}.bind(this, targetNode));
				}
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
				for (var i in data)
				{
					value = this.prepareItemData(data[i]);
					if (value)
					{
						items.push(value);
					}
				}

				return items;
			},

			getDialogSelectedItems: function()
			{
				var data = this.option('data');
				var items = [];

				var value = null;
				for (var i in data)
				{
					value = this.prepareItemData(data[i]);
					if (value)
					{
						items.push(value);
					}
				}

				return items;
			},

			prepareItemData: function(data)
			{
				var id = 0;

				if (data.ID)
				{
					id = data.ID;
				}
				else if (data.id)
				{
					id = data.id;
				}

				if (id <= 0)
				{
					return null;
				}

				var mode = this.option('mode');
				return [(mode === 'group') ? 'project' : 'user', id];
			},

			getDialogEntities: function()
			{
				const mode = this.option('mode');
				const networkEnabled = this.option('networkEnabled');
				const flowId = this.option('flowId');

				const entityConfigurations = {
					'flowTeam': [
						{
							id: 'flow-user',
							options: {
								flowId,
							},
							dynamicLoad: true,
						},
						{
							id: 'department',
						}
					],
					'user': [
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
					],
					'group': [
						{
							id: 'project',
						}
					]
				};

				if (flowId > 0)
				{
					return entityConfigurations['flowTeam'];
				}

				return entityConfigurations[mode] || [];
			},

			prepareUserData: function(user)
			{
				var role = this.option('role');
				var customData = user.getCustomData();
				var entityType = user.getEntityType();
				var mode = this.option('mode');

				var types = {
					ACCOMPLICES: 'A',
					AUDITORS: 'U',
					RESPONSIBLE: 'R'
				};

				return {
					AVATAR: user.avatar,
					DESCRIPTION: '',
					entityType: types[role],
					id: user.getId(),
					name: customData.get('name'),
					lastName: customData.get('lastName'),
					email: customData.get('email'),
					nameFormatted: BX.Text.encode(user.getTitle()),
					networkId: '',
					url: user.getLink(),
					user_type: entityType,
					type: {
						crmemail: false,
						extranet: (entityType === 'extranet'),
						email: (entityType === 'email'),
						network: (entityType === 'network')
					},
					VALUE: ((mode === 'group') ? 'SG' : 'U') + user.getId()
				};
			},

			prepareData: function(data)
			{
				data = this.callMethod(BX.Tasks.UserItemSet, 'prepareData', arguments);
				data.AVATAR_CSS = data.AVATAR ? "background: url('"+encodeURI(data.AVATAR)+"') center no-repeat; background-size: 35px;" : '';

				return data;
			},

			openAddForm: function(node)
			{
				var userType = this.option('role');
				var viewSelectorEnabled = this.option('viewSelectorEnabled');

				if (
					(userType === 'ACCOMPLICES' || userType === 'AUDITORS')
					&& !viewSelectorEnabled
				)
				{
					BX.Runtime.loadExtension('tasks.limit').then((exports) => {
						const { Limit } = exports;
						Limit.showInstance({
							featureId: 'tasks_observers_participants',
							limitAnalyticsLabels: {
								module: 'tasks',
								source: 'sidebar',
							},
							bindElement: node,
						});
					});

					return;
				}

				this.callMethod(BX.Tasks.UserItemSet, 'openAddForm');
			},

			// sync all on popup close
			onClose: function()
			{
				if(this.vars.changed)
				{
					this.fireUserTriggeredChange();
				}

				this.vars.changed = false;
			},

			// sync all on item deleted by clicking "delete" button
			onItemDeleteClicked: function(node)
			{
				this.parent().switchDeleteButtonShow(true);

				var value = this.doOnItem(node, this.deleteItem);
				if(value)
				{
					this.fireUserTriggeredChangeDebounce();
				}
			},

			fireUserTriggeredChangeDebounce: function()
			{
				this.fireUserTriggeredChange();
			},

			fireUserTriggeredChange: function()
			{
				this.fireEvent('change-by-user');
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
				if (typeof value !== 'object')
				{
					return;
				}

				if (!this.getDialog())
				{
					return;
				}

				value = this.prepareItemData(value.data());
				if (!value)
				{
					return;
				}

				var dialogItem = this.getDialog().getItem(value);
				dialogItem && dialogItem.deselect();
			},

			unsetDialog()
			{
				if (this.dialog)
				{
					this.dialog.destroy();
					this.dialog = null;
				}
			}
		}
	});

}).call(this);