/** @memberof BX.Crm.Timeline */
export default class Action
{
	constructor()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
	}

	initialize(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this._settings = settings ? settings : {};

		this._container = this.getSetting("container");
		if(!BX.type.isElementNode(this._container))
		{
			throw "BX.CrmTimelineAction: Could not find container.";
		}

		this.doInitialize();
	}

	doInitialize()
	{
	}

	getId()
	{
		return this._id;
	}

	getSetting(name, defaultval)
	{
		return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	}

	layout()
	{
		this.doLayout();
	}

	doLayout()
	{
	}
}
