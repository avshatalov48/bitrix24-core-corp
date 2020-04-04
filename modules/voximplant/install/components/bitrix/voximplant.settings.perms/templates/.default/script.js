BX.ViPermissionEdit = function(element)
{
	this.elements = {
		main: element,
		accessTable: null,
		accessTableBody: null,
		accessTableLastRow: null
	};
	this.ajaxUrl = '/bitrix/components/bitrix/voximplant.settings.perms/ajax.php';
	this.init();
};

BX.ViPermissionEdit.prototype =
{
	init: function()
	{
		this.elements.accessTable = this.elements.main.querySelector('table.bx-vi-js-role-access-table');
		this.elements.accessTableBody = this.elements.accessTable.querySelector('tbody');
		this.elements.accessTableLastRow = this.elements.main.querySelector('tr.bx-vi-js-access-table-last-row');
		this.bindHandlers();
		BX.Access.Init({other:{disabled:true}});
	},

	bindHandlers: function()
	{
		var deleteRoleNodes = this.elements.main.querySelectorAll('.bx-vi-js-delete-role');
		var deleteAccessNodes = this.elements.main.querySelectorAll('.bx-vi-js-delete-access');
		var addAccessNodes = this.elements.main.querySelectorAll('.bx-vi-js-add-access');
		var selectRoleNodes = this.elements.main.querySelectorAll('.bx-vi-js-select-role');
		for(var i = 0; i < deleteRoleNodes.length; i++)
		{
			deleteRoleNodes[i].removeEventListener('click', this.handleDeleteRoleClick.bind(this));
			deleteRoleNodes[i].addEventListener('click', this.handleDeleteRoleClick.bind(this));
		}

		for(i = 0; i < deleteAccessNodes.length; i++)
		{
			deleteAccessNodes[i].removeEventListener('click', this.handleDeleteAccessClick.bind(this));
			deleteAccessNodes[i].addEventListener('click', this.handleDeleteAccessClick.bind(this));
		}

		for(i = 0; i < addAccessNodes.length; i++)
		{
			addAccessNodes[i].removeEventListener('click', this.handleAddAccessClick.bind(this));
			addAccessNodes[i].addEventListener('click', this.handleAddAccessClick.bind(this));
		}

		for(i = 0; i < selectRoleNodes.length; i++)
		{
			selectRoleNodes[i].removeEventListener('change', this.handleSelectRoleChange.bind(this));
			selectRoleNodes[i].addEventListener('change', this.handleSelectRoleChange.bind(this));
		}
	},

	handleDeleteRoleClick: function(e)
	{
		e.preventDefault();
		e.stopPropagation();
		var element = e.target;
		var roleId = element.dataset.roleId;
		var self = this;
		var elementsToRemove = document.querySelectorAll('*[data-role-id="'+roleId+'"]');

		self.confirm(BX.message('VOXIMPLANT_PERM_ROLE_DELETE'), BX.message('VOXIMPLANT_PERM_ROLE_DELETE_CONFIRM'), function(e)
		{
			if(!e.confirmed)
				return;

			BX.showWait();
			BX.ajax({
				url: self.ajaxUrl,
				method: "POST",
				dataType: "json",
				data: {
					action : "deleteRole",
					roleId: roleId,
					sessid: BX.bitrix_sessid()
				},
				onsuccess: function(data)
				{
					BX.closeWait();
					if(!data || data.ERROR)
					{
						self.notify(BX.message('VOXIMPLANT_PERM_ERROR'), BX.message('VOXIMPLANT_PERM_ROLE_DELETE_ERROR'));
						return;
					}
					for(var i = 0; i < elementsToRemove.length; i++)
					{
						BX.remove(elementsToRemove[i]);
					}
				},

				onfailure: function()
				{
					BX.closeWait();
					self.notify(BX.message('VOXIMPLANT_PERM_ERROR'), BX.message('VOXIMPLANT_PERM_ROLE_DELETE_ERROR'));
				}
			});
		});
	},

	handleAddAccessClick: function()
	{
		var self = this;
		var selectedAccessCodes = {};
		var rowCount = this.elements.accessTable.rows.length;

		for(var i = 0; i < rowCount; i++)
		{
			if(this.elements.accessTable.rows[i].dataset.accessCode)
			{
				selectedAccessCodes[this.elements.accessTable.rows[i].dataset.accessCode] = true;
			}
		}

		BX.Access.SetSelected(selectedAccessCodes, 'voximplantPerms');
		BX.Access.ShowForm(
		{
			bind: 'voximplantPerms',
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
						self.renderNewAccessCode(id, providerName, accessName, 1);
					}
				}
				self.bindHandlers();
			}
		});

	},

	handleDeleteAccessClick: function(e)
	{
		e.preventDefault();
		e.stopPropagation();
		var element = e.target;
		var roleAccessCode = element.dataset.accessCode;
		var elementsToRemove = this.elements.accessTable.querySelectorAll('tr[data-access-code="'+roleAccessCode+'"]');
		for(var i = 0; i < elementsToRemove.length; i++)
		{
			BX.remove(elementsToRemove[i]);
		}
	},

	handleSelectRoleChange: function(e)
	{
		var element = e.target;
		var roleId = element.value;
		var roleAccessCode = element.dataset.accessCode;

		var tableRow = this.elements.main.querySelector('tr[data-access-code='+roleAccessCode+']');
		if(tableRow)
		{
			tableRow.dataset.roleId = roleId;
		}
	},

	renderNewAccessCode: function(accessCode, provider, name, roleId)
	{
		var template = BX('bx-vi-new-access-row').innerHTML;
		template = this.__replaceAll(template, {PROVIDER: provider, NAME: name, ACCESS_CODE: accessCode});
		var newElement = BX.create('tr', {html: template});
		newElement.dataset.roleId = roleId;
		newElement.dataset.accessCode = accessCode;
		newElement.querySelector('select').value = roleId;
		this.elements.accessTableBody.insertBefore(newElement, this.elements.accessTableLastRow);
	},

	confirm: function(title, text, callback)
	{
		var result = {
			confirmed: false
		};

		var popupId = this.elements.main.id + '-confirm-popup';

		var popupWindow = new BX.PopupWindow(popupId, null, {
			content: text,
			titleBar: title,
			closeByEsc: true,
			buttons: [
				new BX.PopupWindowButton({
					text : BX.message('VOXIMPLANT_PERM_ROLE_OK'),
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
					text : BX.message('VOXIMPLANT_PERM_ROLE_CANCEL'),
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
	},

	notify: function(title, text, callback)
	{
		var popupId = this.elements.main.id + '-notify-popup';
		var popupWindow = new BX.PopupWindow(popupId, null, {
			content: text,
			titleBar: title,
			closeByEsc: true,
			buttons: [
				new BX.PopupWindowButton({
					text : "Ok",
					className : "popup-window-button-accept",
					events : {
						click : function() {
							popupWindow.close();
							if(BX.type.isFunction(callback))
							{
								callback({});
							}
						}
					}
				})
			]
		});
		popupWindow.show();
	},

	__replaceAll: function(template, data)
	{
		if(!BX.type.isPlainObject(data))
			return template;

		var result = template.replace(/#(\w+?)#/g, function(match, variable, offset)
		{
			if(data.hasOwnProperty(variable))
				return data[variable];
			else
				return match;
		});

		return result;
	}
};

