(function() {
var BX = window.BX;

if (!!BX.CrmUISelector)
{
	return;
}

BX.CrmUISelector = {

	onGetEntityTypes: function(params)
	{
		if (
			!BX.type.isNotEmptyObject(params)
			|| !BX.type.isNotEmptyObject(params.selector)
		)
		{
			return;
		}

		var
			selectorInstance = params.selector;

		if (selectorInstance.getOption('enableCrm') != 'Y')
		{
			return;
		}

		if (selectorInstance.getOption('enableCrmContacts') == 'Y')
		{
			selectorInstance.entityTypes.CONTACTS = {
				options: {
					enableSearch: 'Y',
					searchById: 'Y',
					addTab: (selectorInstance.getOption('addTabCrmContacts') == 'Y' ? 'Y' : 'N'),
					onlyWithEmail: (selectorInstance.getOption('onlyWithEmail') == 'Y' ? 'Y' : 'N'),
					prefixType: (BX.type.isNotEmptyString(selectorInstance.getOption('crmPrefixType')) ? selectorInstance.getOption('crmPrefixType') : 'FULL'),
					returnItemUrl: (selectorInstance.getOption('returnItemUrl') == 'N' ? 'N' : 'Y'),
					returnMultiEmail: (selectorInstance.getOption('returnMultiEmail') == 'Y' ? 'Y' : 'N')
				}
			};
		}

		if (selectorInstance.getOption('enableCrmCompanies') == 'Y')
		{
			selectorInstance.entityTypes.COMPANIES = {
				options: {
					enableSearch: 'Y',
					searchById: 'Y',
					addTab: (selectorInstance.getOption('addTabCrmCompanies') == 'Y' ? 'Y' : 'N'),
					onlyWithEmail: (selectorInstance.getOption('onlyWithEmail') == 'Y' ? 'Y' : 'N'),
					onlyMy: (selectorInstance.getOption('onlyMyCompanies') == 'Y' ? 'Y' : 'N'),
					prefixType: (BX.type.isNotEmptyString(selectorInstance.getOption('crmPrefixType')) ? selectorInstance.getOption('crmPrefixType') : 'FULL'),
					returnItemUrl: (selectorInstance.getOption('returnItemUrl') == 'N' ? 'N' : 'Y'),
					returnMultiEmail: (selectorInstance.getOption('returnMultiEmail') == 'Y' ? 'Y' : 'N')
				}
			};
		}

		var dynamicTypes = selectorInstance.getOption('enableCrmDynamics');
		if (dynamicTypes)
		{
			var dynamicTitles = (selectorInstance.getOption('crmDynamicTitles') ? selectorInstance.getOption('crmDynamicTitles') : {});

			for(var typeId in dynamicTypes)
			{
				if (dynamicTypes[typeId] === 'Y')
				{
					var entityTypeId = 'DYNAMICS_'+typeId;
					var addTabCrmDynamics = selectorInstance.getOption('addTabCrmDynamics');

					selectorInstance.entityTypes[entityTypeId] = {
						options: {
							enableSearch: 'Y',
							searchById: 'Y',
							addTab: ((addTabCrmDynamics && addTabCrmDynamics[typeId] === 'Y') ? 'Y' : 'N'),
							typeId: typeId,
							onlyWithEmail: (selectorInstance.getOption('onlyWithEmail') === 'Y' ? 'Y' : 'N'),
							prefixType: (BX.type.isNotEmptyString(selectorInstance.getOption('crmPrefixType')) ? selectorInstance.getOption('crmPrefixType') : 'FULL'),
							returnItemUrl: (selectorInstance.getOption('returnItemUrl') === 'N' ? 'N' : 'Y'),
							title: (dynamicTitles[entityTypeId] ? dynamicTitles[entityTypeId] : '')
						}
					};
				}
			}
		}

		if (selectorInstance.getOption('enableCrmLeads') == 'Y')
		{
			selectorInstance.entityTypes.LEADS = {
				options: {
					enableSearch: 'Y',
					searchById: 'Y',
					addTab: (selectorInstance.getOption('addTabCrmLeads') == 'Y' ? 'Y' : 'N'),
					onlyWithEmail: (selectorInstance.getOption('onlyWithEmail') == 'Y' ? 'Y' : 'N'),
					prefixType: (BX.type.isNotEmptyString(selectorInstance.getOption('crmPrefixType')) ? selectorInstance.getOption('crmPrefixType') : 'FULL'),
					returnItemUrl: (selectorInstance.getOption('returnItemUrl') == 'N' ? 'N' : 'Y'),
					returnMultiEmail: (selectorInstance.getOption('returnMultiEmail') == 'Y' ? 'Y' : 'N')
				}
			};
		}

		if (selectorInstance.getOption('enableCrmDeals') == 'Y')
		{
			selectorInstance.entityTypes.DEALS = {
				options: {
					enableSearch: 'Y',
					searchById: 'Y',
					addTab: (selectorInstance.getOption('addTabCrmDeals') == 'Y' ? 'Y' : 'N'),
					prefixType: (BX.type.isNotEmptyString(selectorInstance.getOption('crmPrefixType')) ? selectorInstance.getOption('crmPrefixType') : 'FULL'),
					returnItemUrl: (selectorInstance.getOption('returnItemUrl') == 'N' ? 'N' : 'Y')
				}
			};
		}

		if (selectorInstance.getOption('enableCrmOrders') == 'Y')
		{
			selectorInstance.entityTypes.ORDERS = {
				options: {
					enableSearch: 'Y',
					searchById: 'Y',
					addTab: (selectorInstance.getOption('addTabCrmOrders') == 'Y' ? 'Y' : 'N'),
					prefixType: (BX.type.isNotEmptyString(selectorInstance.getOption('crmPrefixType')) ? selectorInstance.getOption('crmPrefixType') : 'FULL'),
					returnItemUrl: (selectorInstance.getOption('returnItemUrl') == 'N' ? 'N' : 'Y')
				}
			};
		}

		if (selectorInstance.getOption('enableCrmProducts') == 'Y')
		{
			selectorInstance.entityTypes.PRODUCTS = {
				options: {
					enableSearch: 'Y',
					searchById: 'Y',
					addTab: (selectorInstance.getOption('addTabCrmProducts') == 'Y' ? 'Y' : 'N'),
					prefixType: (BX.type.isNotEmptyString(selectorInstance.getOption('crmPrefixType')) ? selectorInstance.getOption('crmPrefixType') : 'FULL'),
					returnItemUrl: (selectorInstance.getOption('returnItemUrl') == 'N' ? 'N' : 'Y')
				}
			};
		}

		if (selectorInstance.getOption('enableCrmQuotes') == 'Y')
		{
			selectorInstance.entityTypes.QUOTES = {
				options: {
					enableSearch: 'Y',
					searchById: 'Y',
					addTab: (selectorInstance.getOption('addTabCrmQuotes') == 'Y' ? 'Y' : 'N'),
					prefixType: (BX.type.isNotEmptyString(selectorInstance.getOption('crmPrefixType')) ? selectorInstance.getOption('crmPrefixType') : 'FULL'),
					returnItemUrl: (selectorInstance.getOption('returnItemUrl') == 'N' ? 'N' : 'Y')
				}
			};
		}

		if (selectorInstance.getOption('enableCrmSmartInvoices') === 'Y')
		{
			selectorInstance.entityTypes.SMART_INVOICES = {
				options: {
					enableSearch: 'Y',
					searchById: 'Y',
					addTab: (selectorInstance.getOption('addTabCrmSmartInvoices') === 'Y' ? 'Y' : 'N'),
					prefixType: (BX.type.isNotEmptyString(selectorInstance.getOption('crmPrefixType')) ? selectorInstance.getOption('crmPrefixType') : 'FULL'),
					returnItemUrl: (selectorInstance.getOption('returnItemUrl') === 'N' ? 'N' : 'Y')
				}
			};
		}

	},

	onSearchRequestCallbackSussess: function(params)
	{
		if (
			!BX.type.isNotEmptyObject(params)
			|| !BX.type.isNotEmptyObject(params.responseData)
			|| !BX.type.isNotEmptyObject(params.responseData.ENTITIES)
			|| !BX.type.isNotEmptyObject(params.selector)
		)
		{
			return;
		}

		var
			responseData = params.responseData,
			selectorInstance = params.selector;

		var
			entityTypesList = ['CONTACTS', 'COMPANIES', 'LEADS', 'DEALS', 'ORDERS', 'PRODUCTS', 'QUOTES', 'SMART_INVOICES'],
			entityType = null;

		for (entityType in responseData.ENTITIES)
		{
			if (
				(entityTypesList.indexOf(entityType) > -1 || entityType.match(/^DYNAMICS_\d+$/))
				&& BX.type.isNotEmptyObject(responseData.ENTITIES[entityType])
				&& BX.type.isNotEmptyObject(responseData.ENTITIES[entityType].ITEMS)
			)
			{
				for (var itemCode in responseData.ENTITIES[entityType].ITEMS)
				{
					if (!responseData.ENTITIES[entityType].ITEMS.hasOwnProperty(itemCode))
					{
						continue;
					}
					selectorInstance.entities[entityType].items[itemCode] = responseData.ENTITIES[entityType].ITEMS[itemCode];
					if (BX.type.isNotEmptyObject(params.eventResult))
					{
						params.eventResult.found = true;
						params.eventResult.itemCodeList.push(itemCode);
					}
				}
			}
		}
	},

	onFilterDestinationSelectorConvert: function(params, eventResult)
	{
		if (
			!BX.type.isNotEmptyObject(params)
			|| !BX.type.isNotEmptyString(params.selectorId)
			|| !BX.type.isNotEmptyString(params.value)
			|| !BX.type.isNotEmptyObject(BX.Main)
			|| !BX.type.isNotEmptyObject(BX.Main.selectorManagerV2)
			|| !BX.type.isObject(eventResult)
		)
		{
			return;
		}

		var
			selectorInstance = BX.Main.selectorManagerV2.getById(params.selectorId);

		if (!BX.type.isNotEmptyObject(selectorInstance))
		{
			return;
		}

		if (selectorInstance.getOption('convertJson') == 'Y')
		{
			var
				split = null,
				value = null;

			if (split = params.value.match(/CRMQUOTE(\d+)/))
			{
				value = {
					'QUOTE': [ parseInt(split[1]) ]
				};
			}
			else if (split = params.value.match(/CRMPRODUCT(\d+)/))
			{
				value = {
					'PRODUCT': [ parseInt(split[1]) ]
				};
			}
			else if (split = params.value.match(/CRMCOMPANY(\d+)/))
			{
				value = {
					'COMPANY': [ parseInt(split[1]) ]
				};
			}
			else if (split = params.value.match(/CRMCONTACT(\d+)/))
			{
				value = {
					'CONTACT': [ parseInt(split[1]) ]
				};
			}
			else if (split = params.value.match(/CRMLEAD(\d+)/))
			{
				value = {
					'LEAD': [ parseInt(split[1]) ]
				};
			}
			else if (split = params.value.match(/CRMDEAL(\d+)/))
			{
				value = {
					'DEAL': [ parseInt(split[1]) ]
				};
			}
			else if (split = params.value.match(/CRMSMART_INVOICE(\d+)/))
			{
				value = {
					'SMART_INVOICE': [ parseInt(split[1]) ]
				};
			}
			else if (split = params.value.match(/CRMDYNAMIC-(\d+)_(\d+)/))
			{
				var entityType =  'DYNAMIC_' + split[1];
				value = {};
				value[entityType] = [ parseInt(split[2]) ];
			}

			if (BX.type.isNotEmptyObject(value))
			{
				eventResult.value = JSON.stringify(value);
			}
		}
	},

	setFilterSelected: function(params)
	{
		if (
			!BX.type.isNotEmptyObject(params)
			|| !BX.type.isNotEmptyString(params.selectorId)
			|| !BX.type.isNotEmptyObject(BX.UI.SelectorManager)
			|| !BX.type.isNotEmptyObject(BX.Main)
			|| !BX.type.isNotEmptyObject(BX.Main.selectorManagerV2)
		)
		{
			return;
		}

		var
			selectorInstance = BX.UI.SelectorManager.instances[params.selectorId],
			componentSelectorInstance = BX.Main.selectorManagerV2.getById(params.selectorId);

		if (
			!BX.type.isNotEmptyObject(selectorInstance)
			|| !BX.type.isNotEmptyObject(componentSelectorInstance)
		)
		{
			return;
		}

		var
			isNumeric = componentSelectorInstance.getOption('isNumeric'),
			prefix = componentSelectorInstance.getOption('prefix'),
			convertJson = componentSelectorInstance.getOption('convertJson'),
			i = null;

		if (
			convertJson == 'Y'
			&& BX.type.isNotEmptyString(params.current.value)
		)
		{
			var parsedValue = JSON.parse(params.current.value);
			if (BX.type.isNotEmptyObject(parsedValue))
			{
				if (BX.type.isArray(parsedValue.QUOTE))
				{
					for (i = 0; i < parsedValue.QUOTE.length; i++)
					{
						componentSelectorInstance.items.selected['CRMQUOTE' + parsedValue.QUOTE[i]] = 'quotes';
					}
				}
				if (BX.type.isArray(parsedValue.ORDER))
				{
					for (i = 0; i < parsedValue.ORDER.length; i++)
					{
						componentSelectorInstance.items.selected['CRMORDER' + parsedValue.ORDER[i]] = 'orders';
					}
				}
				if (BX.type.isArray(parsedValue.PRODUCT))
				{
					for (i = 0; i < parsedValue.PRODUCT.length; i++)
					{
						componentSelectorInstance.items.selected['CRMPRODUCT' + parsedValue.ORDER[i]] = 'products';
					}
				}
				if (BX.type.isArray(parsedValue.DEAL))
				{
					for (i = 0; i < parsedValue.DEAL.length; i++)
					{
						componentSelectorInstance.items.selected['CRMDEAL' + parsedValue.DEAL[i]] = 'deals';
					}
				}
				if (BX.type.isArray(parsedValue.LEAD))
				{
					for (i = 0; i < parsedValue.LEAD.length; i++)
					{
						componentSelectorInstance.items.selected['CRMLEAD' + parsedValue.LEAD[i]] = 'leads';
					}
				}
				if (BX.type.isArray(parsedValue.CONTACT))
				{
					for (i = 0; i < parsedValue.CONTACT.length; i++)
					{
						componentSelectorInstance.items.selected['CRMCONTACT' + parsedValue.CONTACT[i]] = 'contacts';
					}
				}
				if (BX.type.isArray(parsedValue.COMPANY))
				{
					for (i = 0; i < parsedValue.COMPANY.length; i++)
					{
						componentSelectorInstance.items.selected['CRMCOMPANY' + parsedValue.COMPANY[i]] = 'companies';
					}
				}
			}
		}
		else if (isNumeric == 'Y')
		{
			if (prefix == 'CRMCOMPANY')
			{
				componentSelectorInstance.items.selected[prefix + params.current.value] = 'companies';
			}
			else if (prefix == 'CRMCONTACT')
			{
				componentSelectorInstance.items.selected[prefix + params.current.value] = 'contacts';
			}
			else if (prefix == 'CRMLEAD')
			{
				componentSelectorInstance.items.selected[prefix + params.current.value] = 'leads';
			}
			else if (prefix == 'CRMDEAL')
			{
				componentSelectorInstance.items.selected[prefix + params.current.value] = 'deals';
			}
			else if (prefix == 'CRMORDER')
			{
				componentSelectorInstance.items.selected[prefix + params.current.value] = 'orders';
			}
			else if (prefix == 'CRMPRODUCT')
			{
				componentSelectorInstance.items.selected[prefix + params.current.value] = 'products';
			}
			else if (prefix == 'CRMQUOTE')
			{
				componentSelectorInstance.items.selected[prefix + params.current.value] = 'quotes';
			}
		}
	}
};

BX.ready(function () {
	BX.addCustomEvent('BX.Main.SelectorV2:onGetEntityTypes', BX.CrmUISelector.onGetEntityTypes);
	BX.addCustomEvent('BX.UI.Selector:onSearchRequestCallbackSussess', BX.CrmUISelector.onSearchRequestCallbackSussess);
	BX.addCustomEvent('BX.Filter.DestinationSelector:convert', BX.CrmUISelector.onFilterDestinationSelectorConvert);
	BX.addCustomEvent('BX.Filter.DestinationSelector:setSelected', BX.CrmUISelector.setFilterSelected);
});

})();
