BX.namespace("BX.Crm");

//region TOOL PANEL
if(typeof BX.Crm.EntityEditorToolPanel === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityEditorToolPanel = BX.UI.EntityEditorToolPanel;
}
//endregion

if(typeof BX.Crm.EntityEditorToolPanelProxy === "undefined")
{
	BX.Crm.EntityEditorToolPanelProxy = function()
	{
		BX.Crm.EntityEditorToolPanelProxy.superclass.constructor.apply(this);
		this._parentPanel = null;
	};
	BX.extend(BX.Crm.EntityEditorToolPanelProxy, BX.UI.EntityEditorToolPanel);

	BX.Crm.EntityEditorToolPanelProxy.prototype.initialize = function(id, settings)
	{
		BX.Crm.EntityEditorToolPanelProxy.superclass.initialize.apply(this, arguments);
		this._parentPanel = BX.prop.get(this._settings, "parentPanel", null);
	};
	BX.Crm.EntityEditorToolPanelProxy.prototype.isVisible = function()
	{
		return false;
	};
	BX.Crm.EntityEditorToolPanelProxy.prototype.layout = function()
	{
		// no layout
	};
	BX.Crm.EntityEditorToolPanelProxy.prototype.setLocked = function(locked)
	{
		BX.Crm.EntityEditorToolPanelProxy.superclass.setLocked.apply(this, arguments);
		if (this._parentPanel)
		{
			this._parentPanel.setLocked(locked);
		}
	};

	BX.Crm.EntityEditorToolPanelProxy.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorToolPanelProxy();
		self.initialize(id, settings);
		return self;
	};
}