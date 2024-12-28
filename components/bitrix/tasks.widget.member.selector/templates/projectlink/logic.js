'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetMemberSelectorProjectLink != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetMemberSelectorProjectLink = BX.Tasks.Component.extend({
		dialog: null,
		sys: {
			code: 'ms-plink'
		},
		options: {
			entityRoute: ''
		},
		methods: {

			bindEvents: function()
			{
				this.bindDelegateControl('deselect', 'click', this.onDeSelect.bind(this));
				this.bindDelegateControl('control', 'click', this.onOpenForm.bind(this));

				BX.PULL.subscribe({
					type: BX.PullClient.SubscriptionType.Server,
					moduleId: 'tasks',
					command: 'task_update',
					callback: this.onTaskUpdated.bind(this),
				});
			},

			onTaskUpdated(params, extra, command)
			{
				const isEventByCurrentTask = parseInt(params?.TASK_ID, 10) === this.option('entityId');
				const isEventContainsGroup = !BX.Type.isUndefined(params.AFTER.GROUP_ID);

				if (!isEventByCurrentTask || !isEventContainsGroup)
				{
					return;
				}

				const currentItemId = Number(this.currentItemId ?? 0);
				const isGroupChange = currentItemId !== params.AFTER.GROUP_ID;
				if (!isGroupChange)
				{
					return;
				}

				const groupId = params.AFTER?.GROUP_ID;
				if (groupId === 0)
				{
					this.onDeSelect();

					return;
				}

				this.reloadGroup();
			},

			reloadGroup()
			{
				BX.ajax.runComponentAction('bitrix:tasks.widget.member.selector', 'getTaskGroup', {
					mode: 'class',
					data: {
						taskId: this.option('entityId'),
					},
				})
					.then((response) => {
						const group = response.data;
						if (group === null)
						{
							return;
						}

						const item = new BX.UI.EntitySelector.Item({
							id: group.ID,
							title: group?.NAME,
							entityType: group?.TYPE ?? 'project',
							entityId: 'project',
							customData: {
								dialogId: group?.DIALOG_ID,
							},
						});

						this.renderItem(item.getId(), item.getTitle(), item);
					});
			},

			onDeSelect: function()
			{
				this.renderItem(0);
				this.saveId(0);
				this.getProjectDialog().deselectAll();
			},

			renderItem: function(id, text, item)
			{
				id = parseInt(id);
				this.currentItemId = id;

				if(id)
				{
					BX.removeClass(this.control('item'), 'invisible');
					BX.addClass(this.control('control'), 'invisible');

					this.control('item-link').innerHTML = BX.util.htmlspecialchars(text);
					this.control('item-link').setAttribute('href', this.getProjectLink(id, item));

					this.option('groupId', id);
				}
				else
				{
					BX.addClass(this.control('item'), 'invisible');
					BX.removeClass(this.control('control'), 'invisible');
				}

				this.rerenderTitle(item);
			},

			onOpenForm: function()
			{
				if (this.option('isProjectLimitExceeded') === true)
				{
					BX.Runtime.loadExtension('tasks.limit').then((exports) => {
						const { Limit } = exports;
						Limit.showInstance({
							featureId: 'socialnetwork_projects_groups',
						});
					});

					return;
				}
				this.getProjectDialog().show();
			},

			getProjectLink: function(groupId, item)
			{
				if (item?.entityType === 'collab')
				{
					const dialogId = item?.getCustomData().get('dialogId');
					if (!dialogId)
					{
						return '';
					}

					return this.option('path').collab?.replace('#DIALOG_ID#', dialogId);
				}

				return this.option('path').SG.toString().replace('{{ID}}', groupId);
			},

			saveId: function(groupId)
			{
				var prefix = this.option('entityRoute');
				if (!prefix)
				{
					return;
				}

				var entityId = this.option('entityId');
				groupId = parseInt(groupId);

				BX.ajax.runComponentAction('bitrix:tasks.widget.member.selector', 'setProject', {
					mode: 'class',
					data: {
						taskId: entityId,
						context: this.option('context') ?? '',
						groupId: groupId
					}
				}).then(
					function(response)
					{
						BX.Tasks.Util.fireGlobalTaskEvent(
							'UPDATE',
							{ID: entityId},
							{STAY_AT_PAGE: true},
							{id: entityId}
						);
						BX.onCustomEvent(this, 'onChangeProjectLink', [groupId, entityId]);
						if (response.status === 'success')
						{
							BX.onCustomEvent(this, 'onProjectChanged', groupId);
						}
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

			onSelectorItemSelected: function(data)
			{
				this.renderItem(data.id, BX.util.htmlspecialcharsback(data.nameFormatted), data.item);
				this.saveId(data.id);
			},

			getProjectDialog: function()
			{
				if (!this.dialog)
				{
					this.dialog = new BX.UI.EntitySelector.Dialog({
						targetNode: this.control('control'),
						enableSearch: true,
						multiple: false,
						context: 'TASKS_PROJECTLINK',
						entities: [
							{
								id: 'project',
								options: {
									lockProjectLink: this.option('isProjectLimitExceeded'),
									lockProjectLinkFeatureId: this.option('projectFeatureId'),
									shouldSelectDialogId: true,
								},
							},
						],
						events: {
							'Item:onSelect': function(event) {
								const item = event.getData().item;
								const data = {
									id: item.getId(),
									nameFormatted: item.getTitle(),
									item,
								};
								this.onSelectorItemSelected(data);
								BX.addClass(this.control('control'), 'invisible');

								this.dialog.hide();
							}.bind(this),
							'Item:onDeselect': function(event)
							{

							}.bind(this)
						}
					});
				}
				return this.dialog;
			},

			rerenderTitle(item)
			{
				const taskId = this.option('entityId');

				const titleNode = document.querySelector(`#task-${taskId}-group-title`);
				const valueNode = document.querySelector(`#task-${taskId}-group-value`);

				if (!titleNode || !valueNode)
				{
					return;
				}

				if (item?.entityType === 'collab')
				{
					titleNode.textContent = this.option('loc')?.type?.collab;
					BX.Dom.addClass(valueNode, '--collab');

					return;
				}

				titleNode.textContent = this.option('loc')?.type?.group;
				BX.Dom.removeClass(valueNode, '--collab');
			},
		},
	});

}).call(this);
