;(function(){

	BX.namespace('BX.DocumentGenerator');

	BX.DocumentGenerator.Region = {
		errorsNode: 'edit-region-error-message',
		culturesNode: 'edit-region-culture-select',
		languageNode: 'edit-region-language-input',
		titleNode: 'edit-region-title-input',
		formatDateNode: 'edit-region-format-date-select',
		formatTimeNode: 'edit-region-format-time-select',
		formatNameNode: 'edit-region-format-name-select',
		saveButtonNode: 'ui-button-panel-save',
		closeButtonNode: 'ui-button-panel-close',
		deleteButtonNode: 'ui-button-panel-remove',
		phraseInputClassName: 'docs-region-phrase-input',
		progress: false
	};

	BX.DocumentGenerator.Region.init = function(params)
	{
		this.dateFormats = params.dateFormats || [];
		this.timeFormats = params.timeFormats || [];
		this.nameFormats = params.nameFormats || {};
		this.cultures = params.cultures || {};
		this.region = params.region || {};

		this.bindEvents();
	};

	BX.DocumentGenerator.Region.bindEvents = function()
	{
		BX.bind(BX(this.culturesNode), 'change', BX.proxy(this.onCultureChange, this));
	};

	BX.DocumentGenerator.Region.onCultureChange = function(event)
	{
		var culture = this.cultures[BX(this.culturesNode).value];
		if(!culture)
		{
			BX(this.languageNode).value = '';
			return;
		}

		BX(this.languageNode).value = culture.LANGUAGE_ID;
		BX(this.titleNode).value = culture.NAME;
		BX(this.formatDateNode).value = culture.FORMAT_DATE;
		BX(this.formatTimeNode).value = culture.FORMAT_TIME;
		BX(this.formatNameNode).value = culture.FORMAT_NAME;
	};

	BX.DocumentGenerator.Region.close = function()
	{
		BX.fireEvent(BX(this.closeButtonNode), 'click');
		this.stopProgress();
	};

	BX.DocumentGenerator.Region.save = function(event)
	{
		event.preventDefault();
		if(this.progress)
		{
			return;
		}
		if(BX(this.titleNode).value.length <= 0)
		{
			this.showError(BX.message('DOCGEN_REGION_EDIT_ERROR_TITLE_EMPTY'));
			this.stopProgress();
			return;
		}
		this.startProgress();
		var analyticsLabel, method, id = parseInt(this.region.ID);

		var fields = {
			title: BX(this.titleNode).value,
			languageId: BX(this.languageNode).value,
			formatDate: BX(this.formatDateNode).value,
			formatDatetime: BX(this.formatDateNode).value + ' ' + BX(this.formatTimeNode).value,
			formatName: BX(this.formatNameNode).value,
			phrases: {},
		};
		var length, i, phraseInputs = BX.findChildrenByClassName(document, this.phraseInputClassName, true);
		length = phraseInputs.length;
		for(i = 0; i < length; i++)
		{
			fields['phrases'][phraseInputs[i].getAttribute('name')] = phraseInputs[i].value;
		}

		var data = {fields: fields};

		if(id > 0)
		{
			analyticsLabel = 'editRegion';
			method = 'documentgenerator.api.region.update';
			data.id = id;
		}
		else
		{
			analyticsLabel = 'addRegion';
			method = 'documentgenerator.api.region.add';
		}
		BX.ajax.runAction(method, {
			analyticsLabel: analyticsLabel,
			data: data
		}).then(function(response)
		{
			BX.DocumentGenerator.Region.stopProgress();
			BX.DocumentGenerator.Region.close();
		}, function(response)
		{
			BX.DocumentGenerator.Region.stopProgress();
			BX.DocumentGenerator.Region.showError(response.errors.pop().message);
		});
	};

	BX.DocumentGenerator.Region.showError = function(text)
	{
		var alert = new BX.UI.Alert({
			color: BX.UI.Alert.Color.DANGER,
			icon: BX.UI.Alert.Icon.DANGER,
			text: text
		});
		BX.adjust(BX(this.errorsNode), {
			html: ''
		});
		BX.append(alert.getContainer(), BX(this.errorsNode));
	};

	BX.DocumentGenerator.Region.getLoader = function()
	{
		if(!this.loader)
		{
			this.loader = new BX.Loader({size: 150});
		}

		return this.loader;
	};

	BX.DocumentGenerator.Region.startProgress = function()
	{
		if(!BX.DocumentGenerator.Region.getLoader().isShown())
		{
			BX.DocumentGenerator.Region.getLoader().show(BX(BX.DocumentGenerator.Region.errorsNode).parentNode);
		}
		BX(BX.DocumentGenerator.Region.saveButtonNode).disabled = true;
		BX.DocumentGenerator.Region.progress = true;
	};

	BX.DocumentGenerator.Region.stopProgress = function()
	{
		BX.DocumentGenerator.Region.getLoader().hide();
		BX(BX.DocumentGenerator.Region.saveButtonNode).disabled = false;
		BX.DocumentGenerator.Region.progress = false;
		setTimeout(function()
		{
			BX.removeClass(BX(BX.DocumentGenerator.Region.saveButtonNode), 'ui-btn-wait');
			BX.removeClass(BX(BX.DocumentGenerator.Region.closeButtonNode), 'ui-btn-wait');
			if(BX(BX.DocumentGenerator.Region.deleteButtonNode))
			{
				BX.removeClass(BX(BX.DocumentGenerator.Region.deleteButtonNode), 'ui-btn-wait');
			}
		}, 100);
	};

	BX.DocumentGenerator.Region.delete = function(event)
	{
		event.preventDefault();
		var analyticsLabel, method, data = {}, id = parseInt(this.region.ID);
		if(!id)
		{
			return;
		}
		if(confirm(BX.message('DOCGEN_REGION_EDIT_DELETE_CONFIRM')))
		{
			analyticsLabel = 'deleteRegion';
			method = 'documentgenerator.api.region.delete';
			data.id = id;

			BX.ajax.runAction(method, {
				analyticsLabel: analyticsLabel,
				data: data
			}).then(function(response)
			{
				BX.DocumentGenerator.Region.stopProgress();
				BX.DocumentGenerator.Region.close();
			}, function(response)
			{
				BX.DocumentGenerator.Region.stopProgress();
				BX.DocumentGenerator.Region.showError(response.errors.pop().message);
			});
		}
		else
		{
			BX.DocumentGenerator.Region.stopProgress();
		}
	};

})(window);