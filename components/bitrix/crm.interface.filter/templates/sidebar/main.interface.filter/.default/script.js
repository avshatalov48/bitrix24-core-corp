if(typeof(BX.InterfaceGridFilterSidebar) === "undefined")
{
	BX.InterfaceGridFilterSidebar = function() {};
	BX.InterfaceGridFilterSidebar.initializeCalendarInterval = function(selector)
	{
		if(typeof(BX.InterfaceGridFilterPopup) === "undefined")
		{
			window.setTimeout(function(){ bxCalendarInterval.OnDateChange(selector); }, 1000);
		}
		else
		{
			bxCalendarInterval.OnDateChange(selector);
		}
	};
}
