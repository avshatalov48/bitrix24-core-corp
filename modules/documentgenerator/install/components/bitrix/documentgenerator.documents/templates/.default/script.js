;(function(){

	BX.namespace('BX.DocumentGenerator');

	BX.DocumentGenerator.DocumentList = {
		gridId: null
	};

	BX.DocumentGenerator.DocumentList.init = function(gridId)
	{
		if(this.gridId)
		{
			return;
		}
		this.gridId = gridId;

		if(window !== window.top && BX.type.isFunction(top.BX.ajax.UpdatePageData))
		{
			top.BX.ajax.UpdatePageData = (function() {});
		}
	};

	// copy paste from BX.DocumentGenerator.TemplateList.edit()
	/**
	 * @see BX.DocumentGenerator.TemplateList.edit()
	 * @param templateUrl
	 */
	BX.DocumentGenerator.DocumentList.viewTemplate = function(templateUrl)
	{
		if(BX.SidePanel)
		{
			BX.SidePanel.Instance.open(templateUrl, {width: 845,
				events: {
					onMessage: function (event)
					{
						if (event.getEventId() === 'numerator-saved-event')
						{
							var numeratorData = event.getData();
							var numSelect = this.iframe.contentDocument.querySelector('#docs-template-num-select');
							if (numSelect)
							{
								var options = numSelect.options;
								var isNew = true;
								for (var i = 0; i < options.length; i++)
								{
									var option = options[i];
									if (option.value === numeratorData.id)
									{
										isNew = false;
										option.innerText = numeratorData.name;
									}
								}
								if (isNew)
								{
									numSelect.appendChild(BX.create('option', {
										attrs: {value: numeratorData.id},
										text: numeratorData.name
									}, this.iframe.contentDocument));
								}
								numSelect.value = numeratorData.id;
							}
						}
					}
				}});
		}
		else
		{
			top.location.href = templateUrl;
		}
	};

	BX.DocumentGenerator.DocumentList.delete = function(documentId)
	{
		if(confirm(BX.message('DOCGEN_DOCUMENTS_DELETE_CONFIRM')))
		{
			var grid = BX.Main.gridManager.getById(BX.DocumentGenerator.DocumentList.gridId);
			if(grid)
			{
				grid.instance.getLoader().show();
			}
			BX.ajax.runAction('documentgenerator.api.document.delete', {
				data: {
					id: documentId
				}
			}).then(function()
			{
				var grid = BX.Main.gridManager.getById(BX.DocumentGenerator.DocumentList.gridId);
				if(grid)
				{
					grid.instance.getLoader().hide();
					grid.instance.removeRow(documentId);
				}
				BX.UI.Notification.Center.notify({
					content: BX.message('DOCGEN_DOCUMENTS_DELETE_SUCCESS')
				});
			}, function(response)
			{
				var grid = BX.Main.gridManager.getById(BX.DocumentGenerator.DocumentList.gridId);
				if(grid)
				{
					grid.instance.getLoader().hide();
				}
				BX.UI.Notification.Center.notify({
					content: BX.message('DOCGEN_DOCUMENTS_DELETE_ERROR') + response.errors.pop().message
				});
			});
		}
	};

})(window);