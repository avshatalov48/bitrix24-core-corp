'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TopMenu != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TopMenu = BX.Tasks.Component.extend({
		sys: {
			code: 'topmenu'
		},

		methodsStatic: {
			instances: {},

			getInstance: function(name)
			{
				return BX.Tasks.Component.TopMenu.instances[name];
			},

			addInstance: function(name, obj)
			{
				BX.Tasks.Component.TopMenu.instances[name] = obj;
			}
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
				BX.Tasks.Component.TopMenu.addInstance(this.sys.code, this);

				this.userId = Number(this.option('userId'));
				this.ownerId = Number(this.option('ownerId'));
				this.groupId = Number(this.option('groupId'));

				this.isScrumLimitExceeded = Boolean(this.option('isScrumLimitExceeded'));
				this.isTaskAccessPermissionsLimit = Boolean(this.option('isTaskAccessPermissionsLimit'));
			},

			bindEvents: function()
			{
				try
				{
					var elements = this.option('use_ajax_filter') ? this.scope().getElementsByClassName("tasks_role_link") : {};
					if (elements.length)
					{
						for (var key = 0; key < elements.length; key++)
						{
							BX.bind(elements[key], 'click', function(event) {
								event.preventDefault();

								var targetClass = event.target.className;
								var roleId = (this.dataset.id == 'view_all' ? '' : this.dataset.id);
								var url = this.dataset.url;

								if (
									(targetClass === 'main-buttons-item-sublink ' && roleId === '')
									|| targetClass === 'main-buttons-item-edit-button'
								)
								{
									return;
								}

								BX.onCustomEvent('Tasks.TopMenu:onItem', [roleId, url]);

								var elements = this.parentElement.getElementsByClassName('tasks_role_link');
								if (elements.length)
								{
									for (var key = 0; key < elements.length; key++)
									{
										BX.removeClass(elements[key], 'main-buttons-item-active');
									}
								}
								BX.addClass(this, 'main-buttons-item-active');
							});
						}
					}
				}
				catch(e){}

				BX.addCustomEvent('onPullEvent-tasks', function(command, params) {
					switch (command)
					{
						case 'user_counter':
							this.onUserCounter(params);
							break;
						case 'project_add':
							this.onProjectAdd(params);
							break;
						case 'project_remove':
							this.onProjectRemove(params);
							break;
					}
				}.bind(this));

				BX.addCustomEvent('BX.Main.Filter:apply', function(filterId, data, ctx) {
					this.onFilterApply(filterId, data, ctx);
				}.bind(this));
			},

			isMyList: function()
			{
				return this.userId === this.ownerId;
			},

			onUserCounter: function(data)
			{
				if (!this.isMyList() || this.userId !== Number(data.userId))
				{
					return;
				}

				var roleButton = BX('tasks_panel_menu_view_projects');
				if (roleButton)
				{
					roleButton.querySelector('.main-buttons-item-counter').innerText = this.getCounterValue(data.projects_major);
				}

				Object.keys(data[0]).forEach(function(role) {
					var roleButton = BX('tasks_panel_menu_' + (this.groupId ? 'group_' : '') + role);
					if (roleButton)
					{
						roleButton.querySelector('.main-buttons-item-counter').innerText = this.getCounterValue(data[0][role].total);
					}
				}.bind(this));

				var scrumButton = BX('tasks_panel_menu_view_scrum');
				if (scrumButton)
				{
					scrumButton.querySelector('.main-buttons-item-counter').innerText = this.getCounterValue(data.scrum_total_comments);
				}
			},

			createScrum: function(createLink, sidePanelId)
			{
				if (this.isScrumLimitExceeded)
				{
					BX.UI.InfoHelper.show(
						sidePanelId,
						{
							isLimit: true,
							limitAnalyticsLabels: {
								module: 'tasks',
								source: 'scrumList'
							}
						}
					);
				}
				else
				{
					BX.SidePanel.Instance.open(createLink);
				}
			},

			showConfigPermissions: function()
			{
				if (this.isTaskAccessPermissionsLimit)
				{
					BX.UI.InfoHelper.show(
						'limit_task_access_permissions',
						{
							isLimit: true,
							limitAnalyticsLabels: {
								module: 'tasks',
								source: 'topMenu'
							}
						}
					);
				}
				else
				{
					BX.SidePanel.Instance.open(
						'/tasks/config/permissions/',
						{
							cacheable: false,
							events: {
								onOpen: function () {
									var manager = BX.Main.interfaceButtonsManager;
									for (var menuId in manager.data)
									{
										manager.data[menuId].closeSubmenu();
									}
								}
							}
						}
					);
				}
			},

			onProjectAdd: function(params)
			{
				this.updateScrumLimit();
			},

			onProjectRemove: function(params)
			{
				this.updateScrumLimit();
			},

			updateScrumLimit()
			{
				var scrumButton = BX('tasks_panel_menu_view_scrum');
				if (!scrumButton)
				{
					return;
				}

				this.checkScrumLimit()
					.then(function(isLimitExceeded) {
						this.isScrumLimitExceeded = Boolean(isLimitExceeded);
					}.bind(this))
				;
			},

			checkScrumLimit: function()
			{
				return BX.ajax.runAction(
					'bitrix:tasks.scrum.info.checkScrumLimit',
					{
						data: {},
						signedParameters: this.signedParameters,
					}
				)
					.then(function(response) {
						return response.data;
					})
				;
			},

			getCounterValue: function(value)
			{
				if (!value)
				{
					return '';
				}

				var maxValue = 99;

				return (value > maxValue ? maxValue + '+' : value);
			},

			onFilterApply: function(filterId, data, ctx)
			{
				try
				{
					var roleId = ctx.getFilterFieldsValues().ROLEID;
					var scope = BX.Tasks.Component.TopMenu.getInstance('topmenu').scope();
					var el = scope.querySelectorAll('.tasks_role_link');

					for (var i = 0; i < el.length; i++)
					{
						BX.removeClass(el[i], 'main-buttons-item-active');
					}

					if (typeof roleId !== 'undefined')
					{
						if (!roleId)
						{
							roleId = 'view_all';
						}
						BX.addClass(BX('tasks_panel_menu_' + roleId), 'main-buttons-item-active');

						var toolbar = BX.Tasks.Component.TasksToolbar.getInstance();
						if (toolbar)
						{
							toolbar.rerender(roleId);
						}
					}
				}
				catch (e)
				{

				}
			}
		}
	});
}).call(this);
