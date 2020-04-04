function JCEmployeeSelectControl(arParams)
{
	var _this = this;
	this.arParams = arParams; // {LANGUAGE_ID, ONSELECT, MULTIPLE, VALUE, SITE_ID, IS_EXTRANET, SESSID, NAME_TEMPLATE}

	this.arEmployeesData = {};

	this.multiple = this.arParams.MULTIPLE;

	if (null != arParams.VALUE)
		this.SetValue(arParams.VALUE);

	this.div = null;
	this._onkeypress = function(e)
	{
		if (null == e) e = window.event;
		if (null == e) return;

		if (e.keyCode == 27)
			_this.CloseDialog();
	}

	// current value and its setter and getter
	var current_value = this.multiple ? [] : 0;
	this.SetValue = function(value)
	{
		if (this.multiple)
		{
			if (typeof value == 'string' || typeof value == 'object' && value.constructor == String)
				value = value.split(',');

			if (typeof value == 'object')
			{
				current_value = [];
				for (var i = 0; i < value.length; i++)
				{
					var q = parseInt(value[i]);
					if (!isNaN(q))
						current_value[current_value.length] = q;
				}
			}

			return typeof current_value == 'object';
		}
		else
		{
			current_value = parseInt(value);
			return !isNaN(current_value);
		}
	}

	this.GetValue = function(tostring)
	{
		if (this.multiple)
		{
			if (null == tostring) tostring = false;

			if (tostring)
			{
				if (null != current_value)
					return current_value.join(',');
			}

			return current_value;
		}
		else
		{
			return parseInt(current_value);
		}
	}
}

JCEmployeeSelectControl.prototype.OnSelect = function()
{
	if (null != this.arParams.ONSELECT)
	{
		var value = this.GetValue();
		if (this.arParams.GET_FULL_INFO)
		{
			if (!this.multiple)
			{
				value = this.arEmployeesData[value];
				value.NAME = value.NAME.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&amp;/g, '&');
			}
			else
			{
				var new_value = [];
				for (var i = 0; i < value.length; i++)
				{
					var v = this.arEmployeesData[value[i]];
					v.NAME = v.NAME.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&amp;/g, '&');

					new_value[new_value.length] = v;
				}

				value = new_value;
			}

			this.arParams.ONSELECT(value);
		}
		else
		{
			this.arParams.ONSELECT(value);
		}
	}
}

JCEmployeeSelectControl.prototype.Show = function(arParams)
{
	if(null != this.div)
		return;

	var _this = this;

	if (null == arParams) arParams = {};
	if (null == arParams.id) arParams.id = 'employee_select_control';
	if (null == arParams.className) arParams.className = '';

	this.arParams.WIN = arParams;

	CHttpRequest.Action = function(result) {_this._ShowWindow(result)};
	var url = '/bitrix/components/bitrix/intranet.user.search/ajax.php?lang=' + this.arParams.LANGUAGE_ID + '&win_id=' +this.arParams.WIN.id + '&SITE_ID=' + this.arParams.SITE_ID + '&IS_EXTRANET=' + this.arParams.IS_EXTRANET + '&sessid=' + this.arParams.SESSID + '&nt=' + this.arParams.NAME_TEMPLATE;

	var value = this.GetValue(true);
	if ((this.multiple ? value.length : value) > 0)
		url += '&value=' + value;

	if (this.multiple)
		url += '&multiple=Y';

	ShowWaitWindow();
	CHttpRequest.Send(url);
}

JCEmployeeSelectControl.prototype._ShowWindow = function(result)
{
	CloseWaitWindow();

	var _this = this;

	this.div = document.body.appendChild(document.createElement("DIV"));

	this.div.id = this.arParams.WIN.id;
	this.div.className = "settings-float-form" + (this.arParams.WIN && this.arParams.WIN.className ? ' ' + this.arParams.WIN.className : '');

	this.div.style.position = 'absolute';
	this.div.style.zIndex = '2200';
	if(!!BX.WindowManager && !!BX.WindowManager.Get())
	{
		this.div.style.zIndex = BX.WindowManager.GetZIndex();
	}

	this.div.innerHTML = result;

	this.div.__object = this;

	var obSize = BX.GetWindowSize();

	var left = parseInt(obSize.scrollLeft + obSize.innerWidth/2 - this.div.offsetWidth/2);
	var top = parseInt(obSize.scrollTop + obSize.innerHeight/2 - this.div.offsetHeight/2);

	jsFloatDiv.Show(this.div, left, top, false, false);

	jsUtils.onCustomEvent('onEmployeeSearchShow', {div: this.div});

	BX.bind(document, "keypress", this._onkeypress);
}

JCEmployeeSelectControl.prototype.CloseDialog = function()
{
	BX.unbind(document, "keypress", this._onkeypress);

	jsUtils.onCustomEvent('onEmployeeSearchClose', {div: this.div});

	jsFloatDiv.Close(this.div);
	this.div.parentNode.removeChild(this.div);
	this.div = null;
}
