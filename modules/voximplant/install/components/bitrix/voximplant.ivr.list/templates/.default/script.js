(function(window)
{
	BX.namespace('BX.Voximplant');

	var instance = null;
	var ajaxUrl = "/bitrix/components/bitrix/voximplant.ivr.list/ajax.php";
	var gridId = "voximplant_ivr_list";

	var defaults = {
		createUrl: '',
		isIvrEnabled: false
	};

	BX.Voximplant.IvrList = function()
	{
		this.bindEvents();
	};

	BX.Voximplant.IvrList.getInstance = function()
	{
		if(!instance)
		{
			instance = new BX.Voximplant.IvrList();
		}
		return instance;
	};

	BX.Voximplant.IvrList.setDefaults = function(values)
	{
		for (var key in values)
		{
			if(values.hasOwnProperty(key) && defaults.hasOwnProperty(key))
			{
				defaults[key] = values[key];
			}
		}
	};

	BX.Voximplant.IvrList.prototype.bindEvents = function()
	{
		BX.bind(BX("add-ivr"), "click", this._onAddIvrButtonClick.bind(this));
		BX.addCustomEvent("SidePanel.Slider:onMessage", this._onSidePanelMessage.bind(this));
	};

	BX.Voximplant.IvrList.prototype.edit = function(editUrl)
	{
		BX.SidePanel.Instance.open(editUrl, {cacheable: false});
	};

	BX.Voximplant.IvrList.prototype.delete = function(ivrId)
	{
		ivrId = parseInt(ivrId);
		var postParams = {
			action: 'delete',
			sessid: BX.bitrix_sessid(),
			id: ivrId
		};
		var wait = BX.showWait();

		BX.ajax({
			url: ajaxUrl,
			method: 'POST',
			data: postParams,
			dataType: 'json',
			onsuccess: function(response)
			{
				BX.closeWait(null, wait);
				if(!response.SUCCESS)
				{
					var error = response.ERROR || 'Unknown error';
					window.alert(error);
				}
				else
				{
					var grid = BX.Main.gridManager.getInstanceById(gridId);
					if(grid)
					{
						grid.reload();
					}
				}
			},
			onfailure: function()
			{
				BX.closeWait(null, wait);
				window.alert("Network error");
			}
		})
	};

	BX.Voximplant.IvrList.prototype._onSidePanelMessage = function(event)
	{
		if(event.getEventId() === "IvrEditor::onSave")
		{
			var grid = BX.Main.gridManager.getInstanceById(gridId);
			if(grid)
			{
				grid.reload();
			}
		}
	};

	BX.Voximplant.IvrList.prototype._onAddIvrButtonClick = function(event)
	{
		if(defaults.isIvrEnabled)
		{
			BX.SidePanel.Instance.open(defaults.createUrl, {cacheable: false});
		}
		else
		{
			BX.UI.InfoHelper.show('limit_contact_center_telephony_ivr');
		}
	};

})(window);