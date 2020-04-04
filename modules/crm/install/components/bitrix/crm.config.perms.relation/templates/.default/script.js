
function CrmPermRemoveRow(row)
{
	var roleId = row.getAttribute("data-roleId");
	if(BX.type.isNotEmptyString(roleId))
	{
		BX.Access.DeleteSelected(roleId, "crmPerms");
	}

	BX.remove(row);
}

function CrmSelectEntityInit()
{
	BX.Access.Init({other:{disabled:true}});
	BX.addCustomEvent(BX.Access, 'onSelectProvider', CrmPermAccessSelectProvider);
}

function CrmSelectEntity()
{
	BX.Access.SetSelected(arCrmSelected, "crmPerms");
	BX.Access.ShowForm({ bind: "crmPerms", callback: CrmPermAddRow});
}

function CrmPermAddRow(obSelected)
{
	for(var provider in obSelected)
	{	
		for(var id in obSelected[provider])
		{
			var el = BX('crmPermTableInsertTd');
			var clone_el = BX.clone(el, true);
			clone_el.setAttribute("data-roleId", id);

			clone_el.style.display = '';
			clone_el.id = '';
			var td = BX.findChild(clone_el, {tag:'td'}, false, false);
			td.id = '';
			if (BX.Access.GetProviderName(provider) != '')
				td.innerHTML = '<b>'+BX.Access.GetProviderName(provider)+':</b> '+obSelected[provider][id].name;
			else 
				td.innerHTML = obSelected[provider][id].name;
			var select = BX.findChild(clone_el, {tag:'select'}, true, false);
			select.name = 'PERMS['+id+'][]';	
			el.parentNode.insertBefore(clone_el, el);
		}
	}
}

function CrmRoleDelete(title, message, btnTitle, path)
{
	var dlg = new BX.PopupWindow(
		'CRM_ROLE_DELETE_' + Math.random().toString().substring(2),
		null,
		{
			autoHide: false,
			draggable: true,
			offsetLeft: 0,
			offsetTop: 0,
			bindOptions: { forceBindPosition: false },
			closeByEsc: true,
			closeIcon: { top: '10px', right: '15px' },
			titleBar: title,
			events:
			{
				onPopupClose: function(){ if(dlg) dlg.destroy(); }
			},

			content: BX.create(
				'SPAN',
				{
					'style': { 'marginLeft': '12px', 'marginTop': '12px' },
					'text': message
				}
			),
			buttons:
			[
				new BX.PopupWindowButton(
					{
						'text': btnTitle,
						'className': 'popup-window-button-accept',
						'events':
						{
							'click': function()
							{
								dlg.close();
								window.location.href = path;
							}
						}
					}
				),
				new BX.PopupWindowButton(
					{
						'text': BX.message('JS_CORE_WINDOW_CANCEL'),
						'className': 'popup-window-button-cancel',
						'events':
						{
							'click': function() { dlg.close(); }
						}
					}
				)
			]
		}
	);

	dlg.show();
}

function _CrmPermDisableProvider(providerId)
{
	var button = BX('access_btn_' + providerId);
	if(button && button.style.display !== 'none')
	{
		button.style.display = 'none';
		var delimiter = BX.findNextSibling(button, { 'class': 'access-buttons-delimiter' });
		if(delimiter)
		{
			delimiter.style.display = 'none';
		}
	}
}

function CrmPermAccessSelectProvider(params)
{
	if(!(BX.Access && arCrmPermSettings && BX.type.isArray(arCrmPermSettings['DISABLED_PROVIDERS']) && arCrmPermSettings['DISABLED_PROVIDERS'].length > 0))
	{
		return;
	}

	var curProviderId = BX.type.isNotEmptyString(params['provider']) ? params['provider'] : BX.Access.selectedProvider;
	for(var i = 0; i < arCrmPermSettings['DISABLED_PROVIDERS'].length; i++)
	{
		var providerId = arCrmPermSettings['DISABLED_PROVIDERS'][i];
		if(providerId === curProviderId)
		{
			BX.Access.SelectProvider('user');
		}
		_CrmPermDisableProvider(providerId);
	}
}