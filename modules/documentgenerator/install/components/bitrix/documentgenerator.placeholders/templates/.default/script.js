;(function(){

	BX.namespace('BX.DocumentGenerator');

	BX.DocumentGenerator.Placeholders = {
		popupCopyMessage: null,
		filterId: 'documentgenerator-placeholders-filter',
		getFilterNode: function()
		{
			return BX('popup-window-content-documentgenerator-placeholders-filter_search_container');
		},
		filter: null,
		getFilter: function()
		{
			if(!this.filter)
			{
				var filter = BX.Main.filterManager.getById(this.filterId);
				this.filter = filter.getApi();
			}

			return this.filter;
		},
		provider: null,
		moduleId: null,
		rootProviders: {},
	};

	BX.DocumentGenerator.Placeholders.init = function(params)
	{
		this.moduleId = params.moduleId;
		this.maxDepthProviderLevel = params.maxDepthProviderLevel || 2;
		BX.addCustomEvent('UI::Select::change', BX.proxy(this.onSelectChange, this));

		BX.addCustomEvent('BX.Main.Filter:show', BX.proxy(this.onShowFilter, this));
	};

	BX.DocumentGenerator.Placeholders.onSelectChange = function(select, data)
	{
		if(!BX.isParentForNode(this.getFilterNode(), select.input))
		{
			return;
		}

		this.removeFields(select);
		this.addFields(data);
	};

	BX.DocumentGenerator.Placeholders.getProviderFields = function()
	{
		var filter = this.getFilter(), fields = [];
		var filterFields = filter.parent.presets.getFields();
		if(!BX.type.isArray(filterFields))
		{
			return fields;
		}
		for(var i in filterFields)
		{
			if(filterFields.hasOwnProperty(i))
			{
				var name = filterFields[i].getAttribute('data-name');
				if(name && name.indexOf('provider') >= 0)
				{
					fields.push(filterFields[i]);
				}
			}
		}

		return fields;
	};

	BX.DocumentGenerator.Placeholders.getSelectedData = function(field, name)
	{
		name = name || 'value';
		var selectNode = BX.findChildByClassName(field, 'main-ui-select');
		if(selectNode)
		{
			return BX.parseJSON(selectNode.dataset[name]);
		}
	};

	BX.DocumentGenerator.Placeholders.removeFields = function(select)
	{
		var providerFields = this.getProviderFields(), i;
		var startClear = false;
		for(i = 0; i < providerFields.length; i++)
		{
			if(startClear)
			{
				this.getFilter().parent.presets.removeField(providerFields[i]);
			}
			if(BX.findChildByClassName(providerFields[i], 'main-ui-select') === select.node)
			{
				startClear = true;
			}
		}
	};

	BX.DocumentGenerator.Placeholders.addFields = function(data)
	{
		var providerFields = this.getProviderFields(), i, placeholder = data.PLACEHOLDER || '';
		var providersLength = providerFields.length;
		var rootProvidersCount = 1;
		for(i = 0; i < providersLength; i++)
		{
			var value = this.getSelectedData(providerFields[i]);
			if(value && value.CLASS)
			{
				value.CLASS = value.CLASS.toLowerCase();
				if(this.rootProviders[value.CLASS])
				{
					rootProvidersCount++;
				}
			}
		}

		this.provider = this.getSelectedData(providerFields[0]).VALUE;

		var options = data.OPTIONS;
		if (!BX.Type.isArray(options))
		{
			options = [];
		}
		BX.ajax.runAction('documentgenerator.dataprovider.getProviderFields', {
			data: {
				provider: data.CLASS,
				module: this.moduleId,
				options: options,
				placeholder: placeholder,
			}
		}).then(BX.proxy(function(response)
		{
			if(placeholder.length > 0)
			{
				placeholder += '.';
			}
			var fieldDescription = {
				LABEL: data.NAME,
				TYPE: 'SELECT',
				ITEMS: [
					{
						NAME: BX.message('DOCGEN_PLACEHOLDERS_FIELD_EMPTY'),
						VALUE: '',
					}
				],
				NAME: 'provider' + providersLength
			};
			for(i = 0; i < response.data.fields.length; i++)
			{
				if(!response.data.fields[i].provider)
				{
					continue;
				}
				if(rootProvidersCount > this.maxDepthProviderLevel && this.rootProviders[response.data.fields[i].provider.toLowerCase()])
				{
					continue;
				}
				fieldDescription.ITEMS.push({
					NAME: response.data.fields[i].title,
					VALUE: placeholder + response.data.fields[i].placeholder,
					OPTIONS: response.data.fields[i].options,
					CLASS: response.data.fields[i].provider
				})
			}

			if(fieldDescription.ITEMS.length > 1)
			{
				var presets = this.getFilter().parent.presets;
				presets.addField(fieldDescription);
			}

		}, this)).then(function(response)
		{

		});
	};

	BX.DocumentGenerator.Placeholders.onShowFilter = function()
	{
		BX.removeCustomEvent('BX.Main.Filter:show', BX.proxy(this.onShowFilter, this));

		var providerFields = this.getProviderFields();
		this.provider = this.getSelectedData(providerFields[0]).VALUE;

		var items = (this.getSelectedData(providerFields[0], 'items'));
		if(items)
		{
			for(var i = 0; i < items.length; i++)
			{
				if(items[i].CLASS)
				{
					this.rootProviders[items[i].CLASS.toLowerCase()] = 1;
				}
			}
		}

		if(this.provider)
		{
			this.addFields(this.getSelectedData(providerFields[0]));
		}
	};

	BX.DocumentGenerator.Placeholders.Copy = function (link, placeholder)
	{
		var message;
		if(BX.clipboard.copy('{' + placeholder + '}'))
		{
			message = BX.message('DOCGEN_PLACEHOLDERS_COPY_PLACEHOLDER');
		}
		else
		{
			message = BX.message('DOCGEN_PLACEHOLDERS_COPY_PLACEHOLDER');
		}
		BX.DocumentGenerator.Placeholders.showCopyLinkPopup(link, message);
	};

	BX.DocumentGenerator.Placeholders.showCopyLinkPopup = function(node, message) 
	{
		if(this.popupCopyMessage)
		{
			return;
		}

		this.popupCopyMessage = new BX.PopupWindow('crm-popup-copy-link', node, {
			className: 'crm-popup-copy-link',
			bindPosition: {
				position: 'top'
			},
			offsetLeft: 30,
			darkMode: true,
			angle: true,
			content: message
		});

		this.popupCopyMessage.show();

		setTimeout(function() {
			BX.hide(BX(this.popupCopyMessage.uniquePopupId));
		}.bind(this), 2000);

		setTimeout(function() {
			this.popupCopyMessage.destroy();
			this.popupCopyMessage = null;
		}.bind(this), 2200)
	};

})(window);