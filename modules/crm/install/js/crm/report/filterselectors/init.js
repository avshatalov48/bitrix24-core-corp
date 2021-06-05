;(function ()
{
	BX.namespace("BX.Crm.Report");
	BX.Crm.Report.initFilterSelectors = function ()
	{
		try
		{
			BX.CrmEntityType.setCaptions(JSON.parse(BX.message("crm_type_descriptions")));
		} catch (e)
		{
			//nop
		}

		if (typeof (BX.CrmEntitySelector) !== "undefined")
		{
			BX.CrmEntitySelector.messages["selectButton"] = BX.message("CRM_GRID_ENTITY_SEL_BTN");
			BX.CrmEntitySelector.messages["noresult"] = BX.message("CRM_GRID_SEL_SEARCH_NO_RESULT");
			BX.CrmEntitySelector.messages["search"] = BX.message("CRM_GRID_ENTITY_SEL_SEARCH");
			BX.CrmEntitySelector.messages["last"] = BX.message("CRM_GRID_ENTITY_SEL_LAST");
		}
	};
})();