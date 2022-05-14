import Manager from "./manager";

/** @memberof BX.Crm.Timeline */
export default class Steam
{
	constructor()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._manager = null;
		this._activityEditor = null;

		this._userTimezoneOffset = null;
		this._serverTimezoneOffset = null;
		this._timeFormat = "";
		this._year = 0;

		this._isStubMode = false;
		this._userId = 0;
		this._readOnly = false;

		this._serviceUrl = "";
	}

	initialize(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this._settings = settings ? settings : {};

		this._container = BX(this.getSetting("container"));
		if (!BX.type.isElementNode(this._container))
		{
			throw "Timeline. Container node is not found.";
		}
		this._editorContainer = BX(this.getSetting("editorContainer"));
		this._manager = this.getSetting("manager");
		if (!(this._manager instanceof Manager))
		{
			throw "Timeline. Manager instance is not found.";
		}

		//
		const datetimeFormat = BX.message("FORMAT_DATETIME").replace(/:SS/, "");
		const dateFormat = BX.message("FORMAT_DATE");
		this._timeFormat = BX.date.convertBitrixFormat(BX.util.trim(datetimeFormat.replace(dateFormat, "")));
		//
		this._year = (new Date()).getFullYear();

		this._activityEditor = this.getSetting("activityEditor");

		this._isStubMode = BX.prop.getBoolean(this._settings, "isStubMode", false);
		this._readOnly = BX.prop.getBoolean(this._settings, "readOnly", false);
		this._userId = BX.prop.getInteger(this._settings, "userId", 0);
		this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");

		this.doInitialize();
	}

	getId()
	{
		return this._id;
	}

	getSetting(name, defaultval)
	{
		return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	}

	doInitialize()
	{
	}

	layout()
	{
	}

	isStubMode()
	{
		return this._isStubMode;
	}

	isReadOnly()
	{
		return this._readOnly;
	}

	getUserId()
	{
		return this._userId;
	}

	getServiceUrl()
	{
		return this._serviceUrl;
	}

	refreshLayout()
	{
	}

	getManager()
	{
		return this._manager;
	}

	getOwnerInfo()
	{
		return this._manager.getOwnerInfo();
	}

	reload()
	{
		const currentUrl = this.getSetting("currentUrl");
		const ajaxId = this.getSetting("ajaxId");
		if (ajaxId !== "")
		{
			BX.ajax.insertToNode(BX.util.add_url_param(currentUrl, {bxajaxid: ajaxId}), "comp_" + ajaxId);
		}
		else
		{
			window.location = currentUrl;
		}
	}

	getUserTimezoneOffset()
	{
		if (!this._userTimezoneOffset)
		{
			this._userTimezoneOffset = parseInt(BX.message("USER_TZ_OFFSET"));
			if (isNaN(this._userTimezoneOffset))
			{
				this._userTimezoneOffset = 0;
			}
		}
		return this._userTimezoneOffset;
	}

	getServerTimezoneOffset()
	{
		if (!this._serverTimezoneOffset)
		{
			this._serverTimezoneOffset = parseInt(BX.message("SERVER_TZ_OFFSET"));
			if (isNaN(this._serverTimezoneOffset))
			{
				this._serverTimezoneOffset = 0;
			}
		}
		return this._serverTimezoneOffset;
	}

	formatTime(time, now, utc)
	{
		return BX.date.format(this._timeFormat, time, now, utc);
	}

	formatDate(date)
	{
		return (
			BX.date.format(
				[
					["today", "today"],
					["tommorow", "tommorow"],
					["yesterday", "yesterday"],
					["", (date.getFullYear() === this._year) ? "j F" : "j F Y"]
				],
				date
			)
		);
	}

	cutOffText(text, length)
	{
		if (!BX.type.isNumber(length))
		{
			length = 0;
		}

		if (length <= 0 || text.length <= length)
		{
			return text;
		}

		let offset = length - 1;
		const whitespaceOffset = text.substring(offset).search(/\s/i);
		if (whitespaceOffset > 0)
		{
			offset += whitespaceOffset;
		}
		return text.substring(0, offset) + "...";
	}
}
