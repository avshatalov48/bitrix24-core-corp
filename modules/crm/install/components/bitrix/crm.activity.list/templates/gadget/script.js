BX.CrmActivityGadget = function()
{
	this._id = '';
	this._settings = {};
	this._activityChangeHandler = BX.delegate(this._onActivityChange, this);
};

BX.CrmActivityGadget.prototype =
{
	initialize: function(id, settings)
	{
		this._id = id;
		this._settings = settings ? settings : {};
		var editor = BX.CrmActivityEditor.items[this.getSetting('editorID', '')];
		if(editor)
		{
			editor.addActivityChangeHandler(this._activityChangeHandler);
		}
	},
	getSetting: function(name, defaultval)
	{
		return typeof(this._settings[name]) !== 'undefined' ? this._settings[name] : defaultval;
	},
	_onActivityChange: function()
	{
		// do nothig
		//window.location = window.location;
	}
};

BX.CrmActivityGadget.items = {};

BX.CrmActivityGadget.create = function(id, settings)
{
	var self = new BX.CrmActivityGadget();
	self.initialize(id, settings);
	this.items[id] = self;
	return self;
};