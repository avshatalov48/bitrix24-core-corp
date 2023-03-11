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
			},

			onDeSelect: function()
			{
				this.setItem(0);
			},

			setItem: function(id, text)
			{
				id = parseInt(id);

				if(id)
				{
					BX.removeClass(this.control('item'), 'invisible');
					BX.addClass(this.control('control'), 'invisible');

					this.control('item-link').innerHTML = BX.util.htmlspecialchars(text);
					this.control('item-link').setAttribute('href', this.getProjectLink(id));

					this.option('groupId', id);
				}
				else
				{
					BX.addClass(this.control('item'), 'invisible');
					BX.removeClass(this.control('control'), 'invisible');
				}

				this.saveId(id);
			},

			onOpenForm: function()
			{
				this.getProjectDialog().show();
			},

			getProjectLink: function(groupId)
			{
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
				this.setItem(data.id, BX.util.htmlspecialcharsback(data.nameFormatted));
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
							}
						],
						events: {
							'Item:onSelect': function(event) {
								var item = event.getData().item;
								var data = {
									id: item.getId(),
									nameFormatted: item.getTitle()
								}
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
			}
		}
	});

}).call(this);
