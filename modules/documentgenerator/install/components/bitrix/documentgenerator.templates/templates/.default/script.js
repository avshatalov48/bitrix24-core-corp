;(function(){

BX.namespace('BX.DocumentGenerator');

BX.DocumentGenerator.TemplateList = {
	gridId: null,
	uploadUri: '',
	errorNode: 'docgen-templates-error-message'
};
BX.DocumentGenerator.TemplateList.init = function(gridId, params)
{
	if(this.gridId)
	{
		return;
	}
	this.uploadUri = params.uploadUri || '';
	this.gridId = gridId;
	this.settingsButtonNode = 'docgen-templates-settings-button';
	BX.addCustomEvent('SidePanel.Slider:onMessage', function(message)
	{
		if(message.getEventId() === 'documentgenerator-add-template')
		{
			var data = message.getData();
			var templateId = data.templateId;
			if(templateId > 0)
			{
				var grid = BX.Main.gridManager.getById(BX.DocumentGenerator.TemplateList.gridId);
				if(grid)
				{
					grid.instance.reloadTable('GET', {}, function()
					{
						BX.DocumentGenerator.TemplateList.highlightRow(templateId);
					});
				}
			}
		}
	});

	BX.addCustomEvent("Grid::rowMoved", BX.DocumentGenerator.TemplateList.onRowMoved);

	this.initSettingsMenu(params.settingsMenu);
};
BX.DocumentGenerator.TemplateList.delete = function(templateId)
{
	if(confirm(BX.message('DOCGEN_TEMPLATE_LIST_DELETE_CONFIRM')))
	{
		BX.hide(BX(BX.DocumentGenerator.TemplateList.errorNode));
		var grid = BX.Main.gridManager.getById(BX.DocumentGenerator.TemplateList.gridId);
		if(grid)
		{
			grid.instance.getLoader().show();
		}
		BX.ajax.runAction('documentgenerator.api.template.delete', {
			data: {
				id: templateId
			}
		}).then(function(response)
		{
			var grid = BX.Main.gridManager.getById(BX.DocumentGenerator.TemplateList.gridId);
			if(grid)
			{
				grid.instance.getLoader().hide();
				grid.instance.removeRow(templateId);
			}
		}, function(response)
		{
			var grid = BX.Main.gridManager.getById(BX.DocumentGenerator.TemplateList.gridId);
			if(grid)
			{
				grid.instance.getLoader().hide();
			}
			BX(BX.DocumentGenerator.TemplateList.errorNode).innerHTML = response.errors.pop().message;
			BX.show(BX(BX.DocumentGenerator.TemplateList.errorNode));
		});
	}
};
BX.DocumentGenerator.TemplateList.highlightRow = function(templateId)
{
	var grid = BX.Main.gridManager.getById(BX.DocumentGenerator.TemplateList.gridId);
	if(grid)
	{
		var newRow = grid.instance.getRows().getById(templateId);
		if(newRow)
		{
			newRow.select();
		}
	}
};
BX.DocumentGenerator.TemplateList.edit = function(templateId)
{
	var uploadUri = BX.DocumentGenerator.TemplateList.uploadUri;
	if(templateId > 0)
	{
		uploadUri = BX.util.add_url_param(uploadUri, {ID: templateId});
	}
	if(BX.SidePanel)
	{
		BX.SidePanel.Instance.open(uploadUri, {width: 845});
	}
	else
	{
		top.location.href = uploadUri;
	}
};
BX.DocumentGenerator.TemplateList.onRowMoved = function(ids, rowItem, grid)
{
	BX.hide(BX(BX.DocumentGenerator.TemplateList.errorNode));
	if(grid.getId() !== BX.DocumentGenerator.TemplateList.gridId)
	{
		return;
	}
	grid = BX.Main.gridManager.getById(BX.DocumentGenerator.TemplateList.gridId);
	if(grid)
	{
		grid.instance.getLoader().show();
	}
	BX.ajax.runComponentAction('bitrix:documentgenerator.templates', 'resortList', {
		data: {
			order: ids
		},
		mode: 'class'
	}).then(function(response)
	{
		var grid = BX.Main.gridManager.getById(BX.DocumentGenerator.TemplateList.gridId);
		if(grid)
		{
			grid.instance.getLoader().hide();
		}
	}, function(response)
	{
		var grid = BX.Main.gridManager.getById(BX.DocumentGenerator.TemplateList.gridId);
		if(grid)
		{
			grid.instance.getLoader().hide();
		}
		BX(BX.DocumentGenerator.TemplateList.errorNode).innerHTML = response.errors.pop().message;
		BX.show(BX(BX.DocumentGenerator.TemplateList.errorNode));
	});
};
BX.DocumentGenerator.TemplateList.initSettingsMenu = function(menuItems)
{
	if(BX.type.isArray(menuItems) && menuItems.length > 0)
	{
		var i, length = menuItems.length, items = [];
		for(i = 0; i < length; i++)
		{
			if(BX.type.isArray(menuItems[i].items) && menuItems[i].items.length > 0)
			{
				var j, length2 = menuItems[i].items.length;
				for(j = 0; j < length2; j++)
				{
					menuItems[i].items[j].onclick = function(event, item)
					{
						if(BX.SidePanel)
						{
							BX.SidePanel.Instance.open(item.uri, {width: 845});
						}
						else
						{
							top.location.href = item.uri;
						}
						if(BX.PopupMenu.getCurrentMenu())
						{
							BX.PopupMenu.getCurrentMenu().popupWindow.close();
						}
					};
				}
			}
			else
			{
				menuItems[i].onclick = function(event, item)
				{
					if(BX.SidePanel)
					{
						BX.SidePanel.Instance.open(item.uri, {width: 845});
					}
					else
					{
						top.location.href = item.uri;
					}
					if(BX.PopupMenu.getCurrentMenu())
					{
						BX.PopupMenu.getCurrentMenu().popupWindow.close();
					}
				};
			}
			items.push(menuItems[i])
		}
		BX.bind(BX(this.settingsButtonNode), 'click', BX.proxy(function()
		{
			BX.PopupMenu.show(this.settingsButtonNode + '-menu', BX(this.settingsButtonNode), items,
				{
					offsetLeft: 0,
					offsetTop: 0,
					closeByEsc: true
				}
			);
		}, this));
	}
};

BX.DocumentGenerator.TemplateList.openMoreLink = function(event)
{
	if(top.BX.Helper)
	{
		top.BX.Helper.show("redirect=detail&code=7622241");
		event.preventDefault();
	}
}

})(window);