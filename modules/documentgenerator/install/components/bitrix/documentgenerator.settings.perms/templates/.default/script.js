;(function(){

	BX.namespace('BX.DocumentGenerator');

	BX.DocumentGenerator.Perms = {
		main: null,
		accessTable: null,
		accessTableBody: null,
		accessTableLastRow: null,
		rolesTableBody: null,
		rolesTableLastRow: null,
		isPermissionsFeatureEnabled: true,
	};

	BX.DocumentGenerator.Perms.init = function(element, params)
	{
		if(params.isPermissionsFeatureEnabled === false)
		{
			this.isPermissionsFeatureEnabled = params.isPermissionsFeatureEnabled;
		}
		this.main = element;
		this.accessTable = this.main.querySelector('table.docgen-role-access-table');
		this.accessTableBody = this.accessTable.querySelector('tbody');
		this.accessTableLastRow = this.main.querySelector('tr.docgen-access-table-last-row');
		this.rolesTableBody = this.main.querySelector('table.docgen-roles-table tbody');
		this.rolesTableLastRow = this.main.querySelector('tr.docgen-roles-table-last-row');
		BX.Access.Init({other:{disabled:true}});

		BX.bindDelegate(this.main, 'click', {className: 'docgen-delete-role'}, BX.proxy(this.onDeleteRole, this));
		BX.bindDelegate(this.main, 'click', {className: 'docgen-delete-access'}, BX.proxy(this.onDeleteAccess, this));
		BX.bindDelegate(this.main, 'click', {className: 'docgen-add-access'}, BX.proxy(this.onAddAccess, this));
		BX.bindDelegate(this.main, 'change', {className: 'docgen-select-role'}, BX.proxy(this.onSelectRole, this));
		BX.bindDelegate(this.main, 'click', {className: 'docgen-edit-role'}, BX.proxy(this.onEditRole, this));

		BX.addCustomEvent('SidePanel.Slider:onMessage', BX.proxy(function(message)
		{
			if(message.getEventId() === 'documentgenerator-add-role')
			{
				var data = message.getData();
				var role = data.role;
				if(BX.type.isNotEmptyObject(role))
				{
					var template = BX('docgen-new-role-row').innerHTML;
					template = this.replaceTemplateData(template, {ID: parseInt(role.id), NAME: BX.Text.encode(role.name), EDIT_URL: this.getEditRoleUrl(role.id)});
					var newElement = BX.create('tr', {html: template});
					newElement.dataset.roleId = role.id;
					this.rolesTableBody.insertBefore(newElement, this.rolesTableLastRow);
					BX.DocumentGenerator.Perms.updateRoleFields();
				}
			}
		}, this));
	};

	BX.DocumentGenerator.Perms.onDeleteRole = function(e)
	{
		e.preventDefault();
		e.stopPropagation();
		var element = e.target;
		var roleId = element.dataset.roleId;
		var elementsToRemove = document.querySelectorAll('*[data-role-id="'+roleId+'"]');

		this.confirm(BX.message('DOCGEN_SETTINGS_PERMS_ROLE_DELETE'), BX.message('DOCGEN_SETTINGS_PERMS_ROLE_DELETE_CONFIRM'), function(e)
		{
			if(!e.confirmed)
				return;

			if(!BX.DocumentGenerator.Perms.isPermissionsFeatureEnabled)
			{
				BX.DocumentGenerator.Perms.showPermissionsFeaturePopup();
				return;
			}

			var analyticsLabel = 'docgenDeleteRole';
			var method = 'documentgenerator.api.role.delete';
			BX.showWait();
			BX.ajax.runAction(method, {
				analyticsLabel: analyticsLabel,
				data: {id: roleId}
			}).then(function()
			{
				BX.closeWait();
				for(var i = 0; i < elementsToRemove.length; i++)
				{
					BX.remove(elementsToRemove[i]);
				}
			}, function(response)
			{
				BX.closeWait();
				BX.DocumentGenerator.Perms.showError(response.errors.pop().message);
			});
		});
	};

	BX.DocumentGenerator.Perms.onDeleteAccess = function(e)
	{
		e.preventDefault();
		e.stopPropagation();
		var element = e.target;
		var roleAccessCode = element.dataset.accessCode;
		var elementsToRemove = this.accessTable.querySelectorAll('tr[data-access-code="'+roleAccessCode+'"]');
		for(var i = 0; i < elementsToRemove.length; i++)
		{
			BX.remove(elementsToRemove[i]);
		}
	};
	
	BX.DocumentGenerator.Perms.onAddAccess = function(e)
	{
		var selectedAccessCodes = {};
		var rowCount = this.accessTable.rows.length;

		for(var i = 0; i < rowCount; i++)
		{
			if(this.accessTable.rows[i].dataset.accessCode)
			{
				selectedAccessCodes[this.accessTable.rows[i].dataset.accessCode] = true;
			}
		}

		BX.Access.SetSelected(selectedAccessCodes, 'documentgeneratorPerms');
		BX.Access.ShowForm(
			{
				bind: 'documentgeneratorPerms',
				callback: function(data)
				{
					var providerName;
					var accessName;
					for(var provider in data)
					{
						for(var id in data[provider])
						{
							providerName = BX.Access.GetProviderName(data[provider][id].provider);
							accessName = data[provider][id].name;
							BX.DocumentGenerator.Perms.renderNewAccessCode(id, providerName, accessName, 1);
						}
					}
				}
			});
	};

	BX.DocumentGenerator.Perms.renderNewAccessCode = function(accessCode, provider, name, roleId)
	{
		accessCode = BX.Text.encode(accessCode);
		provider = BX.Text.encode(provider);
		name = BX.Text.encode(name);
		roleId = parseInt(accessCode);
		var template = BX('docgen-new-access-row').innerHTML;
		template = this.replaceTemplateData(template, {PROVIDER: provider, NAME: name, ACCESS_CODE: accessCode});
		var newElement = BX.create('tr', {html: template});
		newElement.dataset.roleId = roleId;
		newElement.dataset.accessCode = accessCode;
		BX.DocumentGenerator.Perms.updateRoleSelect(newElement.querySelector('select'), BX.DocumentGenerator.Perms.getRoles());
		newElement.querySelector('select').value = roleId;
		this.accessTableBody.insertBefore(newElement, this.accessTableLastRow);
	};

	BX.DocumentGenerator.Perms.onSelectRole = function(e)
	{
		var element = e.target;
		var roleId = element.value;
		var roleAccessCode = element.dataset.accessCode;

		var tableRow = this.main.querySelector('tr[data-access-code='+roleAccessCode+']');
		if(tableRow)
		{
			tableRow.dataset.roleId = roleId;
		}
	};

	BX.DocumentGenerator.Perms.confirm = function(title, text, callback)
	{
		var result = {
			confirmed: false
		};

		var popupId = this.main.id + '-confirm-popup';

		var popupWindow = new BX.PopupWindow(popupId, null, {
			content: text,
			titleBar: title,
			closeByEsc: true,
			buttons: [
				new BX.PopupWindowButton({
					text : BX.message('DOCGEN_SETTINGS_PERMS_ROLE_OK'),
					className : "popup-window-button-accept",
					events : {
						click : function() {
							popupWindow.close();
							result.confirmed = true;
							if(BX.type.isFunction(callback))
							{
								callback(result);
							}
						}
					}
				}),
				new BX.PopupWindowButtonLink({
					text : BX.message('DOCGEN_SETTINGS_PERMS_ROLE_CANCEL'),
					className : "popup-window-button-link-cancel",
					events : {
						click : function() {
							popupWindow.close();
							result.confirmed = false;
							if(BX.type.isFunction(callback))
							{
								callback(result);
							}
						}
					}
				})
			]
		});
		popupWindow.show();
	};

	BX.DocumentGenerator.Perms.onEditRole = function(e)
	{
		e.preventDefault();
		e.stopPropagation();

		if(BX.SidePanel)
		{
			BX.SidePanel.Instance.open(e.target.href, {width: 780, cacheable: false});
		}
		else
		{
			location.href = e.target.href;
		}
	};

	BX.DocumentGenerator.Perms.save = function()
	{
		if(!BX.DocumentGenerator.Perms.isPermissionsFeatureEnabled)
		{
			BX.DocumentGenerator.Perms.showPermissionsFeaturePopup();
			BX('ui-button-panel-save').disabled = false;
			setTimeout(function()
			{
				BX.removeClass(BX('ui-button-panel-save'), 'ui-btn-wait');
				BX.removeClass(BX('ui-button-panel-close'), 'ui-btn-wait');
			}, 100);
			return;
		}
		var accesses = [];
		var rows = this.accessTable.querySelectorAll('tr');
		var length = rows.length;
		for(var i = 1; i < length - 1; i++)
		{
			accesses.push({
				accessCode: rows[i].dataset.accessCode,
				roleId: rows[i].dataset.roleId
			})
		}
		BX.ajax.runAction('documentgenerator.api.role.fillAccesses', {
			analyticsLabel: 'docgenFillAccesses',
			data: {
				accesses: accesses
			}
		}).then(function()
		{
			BX.DocumentGenerator.Perms.close();
		}, function(response)
		{
			BX.DocumentGenerator.Perms.showError(response.errors.pop().message);
			BX('ui-button-panel-save').disabled = false;
			setTimeout(function()
			{
				BX.removeClass(BX('ui-button-panel-save'), 'ui-btn-wait');
				BX.removeClass(BX('ui-button-panel-close'), 'ui-btn-wait');
			}, 100);
		});
	};

	BX.DocumentGenerator.Perms.close = function()
	{
		BX.fireEvent(BX('ui-button-panel-close'), 'click');
		BX.removeClass(BX('ui-button-panel-save'), 'ui-btn-wait');
		BX.removeClass(BX('ui-button-panel-close'), 'ui-btn-wait');
	};

	BX.DocumentGenerator.Perms.showError = function(text)
	{
		var alert = new BX.UI.Alert({
			color: BX.UI.Alert.Color.DANGER,
			icon: BX.UI.Alert.Icon.DANGER,
			text: text
		});
		BX.adjust(BX('perms-alert-container'), {
			html: ''
		});
		BX.append(alert.getContainer(), BX('perms-alert-container'));
	};

	BX.DocumentGenerator.Perms.replaceTemplateData = function(template, data)
	{
		if(!BX.type.isPlainObject(data))
			return template;

		return template.replace(/#(\w+?)#/g, function(match, variable)
		{
			if(data.hasOwnProperty(variable))
				return data[variable];
			else
				return match;
		});
	};

	BX.DocumentGenerator.Perms.getEditRoleUrl = function(roleId)
	{
		var addRoleUrl = this.rolesTableBody.querySelector('.docgen-edit-role a').href;
		return BX.util.add_url_param(addRoleUrl, {roleId: roleId});
	};

	BX.DocumentGenerator.Perms.showPermissionsFeaturePopup = function()
	{
		top.BX.UI.InfoHelper.show('limit_crm_document_access_permissions');
	};

	BX.DocumentGenerator.Perms.getRoles = function()
	{
		var roles = {};
		var nodes = this.rolesTableBody.querySelectorAll('tr');
		for(var i = 1, length = nodes.length - 1; i < length; i++)
		{
			roles[nodes[i].dataset.roleId] = nodes[i].children[0].innerText;
		}

		return roles;
	};

	BX.DocumentGenerator.Perms.updateRoleFields = function()
	{
		var roles = BX.DocumentGenerator.Perms.getRoles();
		var selectItems = this.accessTableBody.querySelectorAll('.docgen-select-role');
		for(var i = 0, length = selectItems.length; i < length; i++)
		{
			BX.DocumentGenerator.Perms.updateRoleSelect(selectItems[i], roles);
		}
	};

	BX.DocumentGenerator.Perms.updateRoleSelect = function(selectNode, roles)
	{
		var currentRoles = {};
		var options = selectNode.children;
		var roleId;
		for(var i = 0, length = options.length; i < length; i++)
		{
			roleId = options[i].dataset.roleId;
			if(!roles[roleId])
			{
				BX.remove(options[i]);
			}
			else
			{
				currentRoles[roleId] = options[i].innerText;
			}
		}
		var option;
		for(roleId in roles)
		{
			if(roles.hasOwnProperty(roleId) && !currentRoles.hasOwnProperty(roleId))
			{
				option = BX.create('option', {
					attrs: {
						title: roles[roleId],
						value: roleId,
						'data-role-id': roleId
					},
					text: roles[roleId]
				});
				selectNode.appendChild(option);
			}
		}
	};

	BX.DocumentGenerator.Role = {
		isPermissionsFeatureEnabled: true,
	};

	BX.DocumentGenerator.Role.init = function(params)
	{
		if(params.isPermissionsFeatureEnabled === false)
		{
			this.isPermissionsFeatureEnabled = params.isPermissionsFeatureEnabled;
		}
	};

	BX.DocumentGenerator.Role.save = function()
	{
		if(!BX.DocumentGenerator.Role.isPermissionsFeatureEnabled)
		{
			BX.DocumentGenerator.Perms.showPermissionsFeaturePopup();
			BX('ui-button-panel-save').disabled = false;
			setTimeout(function()
			{
				BX.removeClass(BX('ui-button-panel-save'), 'ui-btn-wait');
				BX.removeClass(BX('ui-button-panel-close'), 'ui-btn-wait');
			}, 100);
			return;
		}
		var form = BX('docs-role').querySelector('form');
		var data = {fields: {permissions: {}}}, i, length = form.length, action, analyticsLabel;
		for(i = 0; i < length; i++)
		{
			if(form.elements[i].name === 'id')
			{
				data.id = form.elements[i].value
			}
			else if(form.elements[i].name.indexOf('permissions') === 0)
			{
				if(!data.fields.permissions[form.elements[i].dataset.entity])
				{
					data.fields.permissions[form.elements[i].dataset.entity] = {};
				}
				data.fields.permissions[form.elements[i].dataset.entity][form.elements[i].dataset.action] = form.elements[i].value;
			}
			else
			{
				data.fields[form.elements[i].name] = form.elements[i].value;
			}
		}
		if(data.id > 0)
		{
			action = 'documentgenerator.api.role.update';
			analyticsLabel = 'docgenAddRole';
		}
		else
		{
			action = 'documentgenerator.api.role.add';
			analyticsLabel = 'docgenUpdateRole';
		}
		BX.ajax.runAction(action, {
			analyticsLabel: analyticsLabel,
			data: data
		}).then(function(response)
		{
			if(BX.SidePanel && action === 'documentgenerator.api.role.add')
			{
				var slider = BX.SidePanel.Instance.getTopSlider();
				if(slider)
				{
					BX.SidePanel.Instance.postMessage(slider, 'documentgenerator-add-role', {role: response.data.role});
				}
			}
			BX.DocumentGenerator.Role.close();
		}, function(response)
		{
			BX.DocumentGenerator.Role.showError(response.errors.pop().message);
			BX('ui-button-panel-save').disabled = false;
			setTimeout(function()
			{
				BX.removeClass(BX('ui-button-panel-save'), 'ui-btn-wait');
				BX.removeClass(BX('ui-button-panel-close'), 'ui-btn-wait');
			}, 100);
		});
	};

	BX.DocumentGenerator.Role.close = function()
	{
		BX.fireEvent(BX('ui-button-panel-close'), 'click');
		BX.removeClass(BX('ui-button-panel-save'), 'ui-btn-wait');
		BX.removeClass(BX('ui-button-panel-close'), 'ui-btn-wait');
	};

	BX.DocumentGenerator.Role.showError = function(text)
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
	};

})(window);