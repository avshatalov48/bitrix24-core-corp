;(function()
{
	"use strict";

	BX.namespace('BX.Crm.Activity');

	if(typeof BX.Crm.Activity.CrmGetRequisitesInfoActivity !== "undefined")
	{
		return;
	}

	BX.Crm.Activity.CrmGetRequisitesInfoActivity = {};

	BX.Crm.Activity.CrmGetRequisitesInfoActivity.init = function(params)
	{
		this.selectCountryNodeId = params.selectCountryNodeId;
		this.selectPresetNodeId = params.selectPresetNodeId;
		this.presetCountry = params.presetCountry;
		this.countriesOfPresets = params.countriesOfPresets;

		BX.bind(
			BX(this.selectCountryNodeId),
			'change',
			this.hidePresetsOfUnselectedCountries.bind(this)
		);
	}

	BX.Crm.Activity.CrmGetRequisitesInfoActivity.hidePresetsOfUnselectedCountries = function()
	{
		var selectedCountry = BX(this.selectCountryNodeId).value;
		var selectPresetNode = BX(this.selectPresetNodeId);

		var options = selectPresetNode.childNodes;
		for(var i = 0; i < options.length; ++i)
		{
			if(options[i].value && this.countriesOfPresets[options[i].value] !== selectedCountry)
			{
				if(options[i].selected)
				{
					selectPresetNode.selectedIndex = 0;
				}

				options[i].setAttribute('hidden', '');
			}
			else
			{
				options[i].removeAttribute('hidden');
			}
		}
	}
})();