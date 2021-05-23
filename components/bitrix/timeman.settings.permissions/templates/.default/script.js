;(function ()
{
	BX.namespace('BX.Timeman.Component.Settings');
	BX.Timeman.Component.Settings.Permissions = function (options)
	{
		BX.Timeman.Component.BaseComponent.apply(this, arguments);

		this.accessTable = this.selectOneByRole('tm-role-access-table');
		this.addTaskAccessBtn = this.selectOneByRole('add-role-access-mapping');
		this.accessTableBody = this.accessTable.querySelector('tbody');
		this.accessTableLastRow = this.accessTable.querySelector('tr.tm-access-table-last-row');
		this.tasksTableLastRow = this.container.querySelector('tr.tm-roles-table-last-row');
		this.rolesTableBody = this.container.querySelector('table.tm-roles-table tbody');

		this.saveBtn = this.selectOneByRole('tm-save-task-to-access-code-map');
		BX.Access.Init({other: {disabled: true}});
		if (window === window.top)
		{
			BX.SidePanel.Instance.bindAnchors({
				rules: [
					{
						condition: [
							new RegExp("/timeman/settings/permissions/($|\\?)", "i")
						],
						options: {
							cacheable: false,
							allowChangeHistory: false,
							width: 1200
						}
					}
				]
			});
		}

		this.addEventHandlers();
	};
	BX.Timeman.Component.Settings.Permissions.prototype = {
		__proto__: BX.Timeman.Component.BaseComponent.prototype,
		constructor: BX.Timeman.Component.Settings.Permissions,
		addEventHandlers: function ()
		{
			BX.bind(this.saveBtn, 'click', BX.delegate(this.onSaveBtnClick, this));
			BX.bind(this.addTaskAccessBtn, 'click', BX.delegate(this.onAddTaskAccessBtnClick, this));
			BX.bindDelegate(this.container, 'click', {className: 'tm-delete-access'}, BX.proxy(this.onDeleteAccess, this));
			BX.bindDelegate(this.container, 'click', {className: 'tm-delete-role'}, BX.proxy(this.onDeleteRole, this));

			BX.addCustomEvent('SidePanel.Slider:onMessage', BX.proxy(function (message)
			{
				if (message.getEventId() === 'timeman-add-task')
				{
					var data = message.getData();
					var task = data.task;
					if (BX.type.isNotEmptyObject(task))
					{
						var template = BX('tm-new-role-row').innerHTML;
						template = this.replaceTemplateData(template, {
							ID: task.id,
							NAME: BX.util.htmlspecialchars(task.name),
							EDIT_URL: this.getEditTaskUrl(task.id)
						});
						var newElement = BX.create('tr', {html: template});
						newElement.dataset.taskId = task.id;
						this.rolesTableBody.insertBefore(newElement, this.tasksTableLastRow);
						var option, selectItems = this.accessTableBody.querySelectorAll('.tm-select-role');
						for (var i = 0, length = selectItems.length; i < length; i++)
						{
							option = BX.create('option', {
								attrs: {
									title: task.name,
									value: task.id,
									'data-task-id': task.id
								},
								text: task.name
							});
							selectItems[i].appendChild(option);
						}
					}
				}
			}, this))
		},
		getEditTaskUrl: function (taskId)
		{
			var addRoleUrl = this.rolesTableBody.querySelector('.tm-edit-task a').href;
			return BX.util.add_url_param(addRoleUrl, {taskId: taskId});
		},
		onDeleteRole: function (e)
		{
			e.preventDefault();
			e.stopPropagation();
			var element = e.target;
			var taskId = element.dataset.taskId;
			var elementsToRemove = document.querySelectorAll('*[data-task-id="' + taskId + '"]');

			this.confirm(
				BX.message('TIMEMAN_SETTINGS_PERMS_ROLE_DELETE'),
				BX.util.htmlspecialchars(BX.message('TIMEMAN_SETTINGS_PERMS_ROLE_DELETE_CONFIRM')),
				function (e)
				{
					if (!e.confirmed)
					{
						return;
					}

					BX.showWait();
					BX.ajax.runAction(
						'timeman.permissions.deleteTask',
						{
							data: {id: taskId}
						}
					).then(function ()
					{
						BX.closeWait();
						for (var i = 0; i < elementsToRemove.length; i++)
						{
							BX.remove(elementsToRemove[i]);
						}
					}, function (response)
					{
						BX.closeWait();
					});
				}
			);
		},
		confirm: function (title, text, callback)
		{
			var result = {
				confirmed: false
			};

			var popupId = 'tm-delete-task-confirm-popup';

			var popupWindow = new BX.PopupWindow(popupId, null, {
				content: text,
				titleBar: title,
				closeByEsc: true,
				buttons: [
					new BX.PopupWindowButton({
						text: BX.message('TIMEMAN_SETTINGS_PERMS_ROLE_OK'),
						className: "popup-window-button-accept",
						events: {
							click: function ()
							{
								popupWindow.close();
								result.confirmed = true;
								if (BX.type.isFunction(callback))
								{
									callback(result);
								}
							}
						}
					}),
					new BX.PopupWindowButtonLink({
						text: BX.message('TIMEMAN_SETTINGS_PERMS_ROLE_CANCEL'),
						className: "popup-window-button-link-cancel",
						events: {
							click: function ()
							{
								popupWindow.close();
								result.confirmed = false;
								if (BX.type.isFunction(callback))
								{
									callback(result);
								}
							}
						}
					})
				]
			});
			popupWindow.show();
		},
		onSaveBtnClick: function ()
		{
			if (this.saveBtn.disabled)
			{
				return;
			}
			this.saveBtn.disabled = true;
			BX.showWait();
			var accesses = [];
			var rows = this.accessTable.querySelectorAll('tr');
			var length = rows.length;
			for (var i = 0; i < length - 1; i++)
			{
				if (!rows[i].dataset.accessCode)
				{
					continue;
				}
				accesses.push({
					accessCode: rows[i].dataset.accessCode,
					taskId: rows[i].querySelector('select').value
				})
			}

			BX.ajax.runAction(
				'timeman.permissions.addTaskToAccessCode',
				{
					data: {accesses: accesses}
				}
			).then(
				function (response)
				{
					BX.reload();
				}.bind(this),
				function (response)
				{
					this.saveBtn.disabled = false;

				}.bind(this));
		},
		onDeleteAccess: function (e)
		{
			e.preventDefault();
			e.stopPropagation();
			var element = e.target;
			var roleAccessCode = element.dataset.accessCode;
			var elementsToRemove = this.accessTable.querySelectorAll('tr[data-access-code="' + roleAccessCode + '"]');
			for (var i = 0; i < elementsToRemove.length; i++)
			{
				BX.remove(elementsToRemove[i]);
			}
		},
		onAddTaskAccessBtnClick: function ()
		{
			var selectedAccessCodes = {};
			var rowCount = this.accessTable.rows.length;

			for (var i = 0; i < rowCount; i++)
			{
				if (this.accessTable.rows[i].dataset.accessCode)
				{
					selectedAccessCodes[this.accessTable.rows[i].dataset.accessCode] = true;
				}
			}

			BX.Access.SetSelected(selectedAccessCodes, 'timemanPerms');
			BX.Access.ShowForm(
				{
					bind: 'timemanPerms',
					callback: function (data)
					{
						var providerName;
						var accessName;
						for (var provider in data)
						{
							for (var id in data[provider])
							{
								providerName = BX.Access.GetProviderName(data[provider][id].provider);
								accessName = BX.util.htmlspecialchars(data[provider][id].name);
								this.renderNewAccessCode(id, providerName, accessName, 1);
							}
						}
					}.bind(this)
				});
		},
		renderNewAccessCode: function (accessCode, provider, name, taskId)
		{
			var template = BX('tm-new-access-row').innerHTML;
			template = this.replaceTemplateData(template, {PROVIDER: provider, NAME: name, ACCESS_CODE: accessCode});
			var newElement = BX.create('tr', {html: template});
			newElement.dataset.taskId = taskId;
			newElement.dataset.accessCode = accessCode;
			newElement.querySelector('select').value = taskId;
			this.accessTableBody.insertBefore(newElement, this.accessTableLastRow);
		},
		replaceTemplateData: function (template, data)
		{
			if (!BX.type.isPlainObject(data))
			{
				return template;
			}

			var result = template.replace(/#(\w+?)#/g, function (match, variable, offset)
			{
				if (data.hasOwnProperty(variable))
				{
					return data[variable];
				}
				else
				{
					return match;
				}
			});

			return result;
		}
	};


	BX.Timeman.Component.Settings.Permissions.Role = function (options)
	{
		BX.Timeman.Component.BaseComponent.apply(this, arguments);
		this.form = this.selectOneByRole('task-form');
		this.isSystem = options.isSystem;
		this.saveBtn = document.querySelector('#tm-save-task');

		if (this.isSystem)
		{
			this.saveBtn.disabled = true;
		}
		this.addEventHandlers();
	};
	BX.Timeman.Component.Settings.Permissions.Role.prototype = {
		__proto__: BX.Timeman.Component.BaseComponent.prototype,
		constructor: BX.Timeman.Component.Settings.Permissions.Role,
		addEventHandlers: function ()
		{
			BX.bind(this.saveBtn, 'click', BX.delegate(this.onSaveBtnClick, this));
		},
		onSaveBtnClick: function ()
		{
			if (this.saveBtn.disabled)
			{
				return;
			}
			this.saveBtn.disabled = true;
			var formData = new FormData(this.form);
			BX.ajax.runAction(
				'timeman.permissions.saveTask',
				{
					data: formData
				}
			).then(
				function (response)
				{
					this.saveBtn.disabled = false;
					if (BX.SidePanel && formData.get('TaskForm[id]') === '')
					{
						var slider = BX.SidePanel.Instance.getTopSlider();
						if (slider)
						{
							BX.SidePanel.Instance.postMessageAll(slider, 'timeman-add-task', {task: response.data.task});
						}
					}
					this.closeSlider();
				}.bind(this),
				function (response)
				{
					this.saveBtn.disabled = false;
					this.showError(response.errors.pop().message);
					BX('tm-save-task').disabled = false;
					setTimeout(function ()
					{
						BX('tm-save-task').classList.remove('ui-btn-wait');
					}.bind(this), 100);
				}.bind(this));

		},
		showError: function (text)
		{
			var alert = new BX.UI.Alert({
				color: BX.UI.Alert.Color.DANGER,
				icon: BX.UI.Alert.Icon.DANGER,
				text: text
			});
			BX.adjust(BX('role-alert-container'), {
				html: ''
			});
			BX.append(alert.getContainer(), BX('role-alert-container'));
		},
		closeSlider: function ()
		{
			if (window.top.BX.SidePanel && window.top.BX.SidePanel.Instance)
			{
				var slider = window.top.BX.SidePanel.Instance.getTopSlider();
				if (slider)
				{
					slider.close();
				}
			}
		}
	};
})();