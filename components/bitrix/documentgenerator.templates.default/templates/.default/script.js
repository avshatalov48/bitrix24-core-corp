;(function(){

BX.namespace('BX.DocumentGenerator');

BX.DocumentGenerator.TemplatesDefault = {
	progress: false,
	errorMessageNode: 'docgen-default-templates-error-message'
};
BX.DocumentGenerator.TemplatesDefault.init = function(params)
{
	this.gridId = params.gridId || '';
};
BX.DocumentGenerator.TemplatesDefault.showError = function(error)
{
	if(!error)
	{
		return;
	}
	BX(BX.DocumentGenerator.TemplatesDefault.errorMessageNode).innerText = error;
	BX.show(BX(BX.DocumentGenerator.TemplatesDefault.errorMessageNode));
};
BX.DocumentGenerator.TemplatesDefault.install = function(code, name, node)
{
	if(!code)
	{
		BX.DocumentGenerator.TemplatesDefault.showError('Empty code');
	}

	if(BX.DocumentGenerator.TemplatesDefault.progress)
	{
		BX.UI.Notification.Center.notify({
			content: BX.message('DOCGEN_TEMPLATES_DEFAULT_TEMPLATE_INSTALL_PROGRESS')
		});
		return;
	}

	BX.DocumentGenerator.TemplatesDefault.progress = true;

	BX.Main.gridManager.getById(BX.DocumentGenerator.TemplatesDefault.gridId).instance.getLoader().show();
	BX.ajax.runAction('documentgenerator.api.template.installDefault', {
		data: {
			code: code
		}
	}).then(function(response)
	{
		BX.DocumentGenerator.TemplatesDefault.progress = false;
		BX.UI.Notification.Center.notify({
			content: BX.message('DOCGEN_TEMPLATES_DEFAULT_TEMPLATE_REINSTALL_COMPLETE').replace('#NAME#', name)
		});
		if(BX.type.isDomNode(node))
		{
			node.innerText = BX.message('DOCGEN_TEMPLATES_DEFAULT_TEMPLATE_REINSTALL');
			node.onclick = function()
			{
				BX.DocumentGenerator.TemplatesDefault.reinstall(code, name, node);
			};
		}
		BX.Main.gridManager.getById(BX.DocumentGenerator.TemplatesDefault.gridId).instance.getLoader().hide();
		var slider = BX.SidePanel.Instance.getTopSlider();
		if(slider)
		{
			BX.SidePanel.Instance.postMessage(slider, 'documentgenerator-add-template', {templateId: response.data.template.id});
		}
	}, function(response)
	{
		BX.DocumentGenerator.TemplatesDefault.progress = false;
		BX.UI.Notification.Center.notify({
			content: BX.message('DOCGEN_TEMPLATES_DEFAULT_TEMPLATE_REINSTALL_ERROR').replace('#NAME#', name)
		});
		BX.Main.gridManager.getById(BX.DocumentGenerator.TemplatesDefault.gridId).instance.getLoader().hide();
		BX.DocumentGenerator.TemplatesDefault.showError(response.errors.pop().message);
	});
};
BX.DocumentGenerator.TemplatesDefault.reinstall = function(code, name, node)
{
	if(BX.DocumentGenerator.TemplatesDefault.progress)
	{
		BX.UI.Notification.Center.notify({
			content: BX.message('DOCGEN_TEMPLATES_DEFAULT_TEMPLATE_INSTALL_PROGRESS')
		});
		return;
	}
	if(confirm(BX.message('DOCGEN_TEMPLATES_DEFAULT_TEMPLATE_REINSTALL_CONFIRM')))
	{
		BX.DocumentGenerator.TemplatesDefault.install(code, name, node);
	}
};
})(window);