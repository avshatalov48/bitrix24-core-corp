;(function(){

	BX.namespace('BX.DocumentGenerator');

	BX.DocumentGenerator.Config = {

	};

	BX.DocumentGenerator.Config.save = function()
	{
		var config = {document_enable_public_b24_sign: 'Y'};
		if(!BX('document_enable_public_b24_sign'))
		{
			BX.DocumentGenerator.Config.close();
		}
		if(!BX('document_enable_public_b24_sign').checked)
		{
			config.document_enable_public_b24_sign = 'N';
		}

		BX.ajax.runComponentAction('bitrix:documentgenerator.config', 'saveConfig', {
			mode: 'class',
			data: {config: config}
		}).then(function()
		{
			BX.DocumentGenerator.Config.close();
		}).then(function(response)
		{
			BX.DocumentGenerator.Config.showError(response.errors.pop().message);
		});
	};

	BX.DocumentGenerator.Config.close = function()
	{
		BX.fireEvent(BX('ui-button-panel-close'), 'click');
		BX.removeClass(BX('ui-button-panel-save'), 'ui-btn-wait');
		BX.removeClass(BX('ui-button-panel-close'), 'ui-btn-wait');
	};

	BX.DocumentGenerator.Config.showError = function(text)
	{
		var alert = new BX.UI.Alert({
			color: BX.UI.Alert.Color.DANGER,
			icon: BX.UI.Alert.Icon.DANGER,
			text: text
		});
		BX.adjust(BX('config-alert-container'), {
			html: ''
		});
		BX.append(alert.getContainer(), BX('config-alert-container'));
	};

})(window);