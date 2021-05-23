function ITSDropdownMenu()
{
	this.oDiv = false;
	this.oControl = false;
	this.oControlPos = false;
	this.bRemoveElement = true;
	var _this = this;
	
	this.InitFromArray = function(id, data)
	{
		var ii = 0, _data = null;
		if (typeof(oObjectITS[id]) != "object")
		{
			oObjectITS[id] = {};
			for (ii in data)
			{
/*				if (ii != parseInt(ii))
					continue;
*/				_data = data[ii]
				if (_data["CONTENT"])
				{
					oObjectITS[id][ii] = {
						"TITLE" : _data["TITLE"], 
						"CLASS" : _data["CLASS"], 
						"ICON" : _data["ICON"], 
						"ONCLICK" : _data["ONCLICK"], 
						"CONTENT" : (typeof(_data["CONTENT"]) != "object" ? [_data["CONTENT"]] : _data["CONTENT"])};
				}
			}
		}
		return oObjectITS[id];
	},
	
	this.CreateMenu = function(id, data)
	{
		var oDiv = false, ii = false, jj = false, _table = '';
		if (typeof(data) == "object")
		{
			oDiv = document.body.appendChild(document.createElement("DIV"));
			oDiv.id = id + '_div';
			oDiv.style.position = 'absolute';
			oDiv.style.visibility = 'hidden';
			oDiv.className = "wd-dropdown-menu";
			
			for (ii in data)
			{
/*				if (ii != parseInt(ii))
					continue;
*/				_text = '<table border="0" cellpadding="0" cellspacing="0" class="wd-dropdown-item" onMouseOver="this.className=\'wd-dropdown-item wd-dropdown-item-over\';" onMouseOut="this.className=\'wd-dropdown-item\';"><tr>';
				_text += '<td class="gutter"><div class="icon ' + data[ii]['ICON'] +'"></div></td>';
				for (jj in data[ii]['CONTENT'])
				{
					_text += '<td class="content">' + data[ii]['CONTENT'][jj] + '</td>';
				}
				_text += '</tr></table>';
				
				_table += '<tr class="wd-dropdown-menu' + (data[ii]['CLASS'] ? (" " + data[ii]['CLASS']) : "") + '" '
					+ 'onmouseover="this.className=\'wd-dropdown-menu-over' + (data[ii]['CLASS'] ? (" " + data[ii]['CLASS'] + "-over") : "") + '\'" ' 
					+ 'onmouseout="this.className=\'wd-dropdown-menu' + (data[ii]['CLASS'] ? (" " + data[ii]['CLASS'] + "") : "") + '\'" ' + '>'
					+ '<td class="wd-dropdown-menu" ' 
					+ ((data[ii]['ONCLICK']) ? ('onclick="' + data[ii]['ONCLICK'] + '"') : "")+ '>'
					+ _text
					+'</td></tr>';
			}
			_table = '<form class="wd-form" style="padding:0px; margin:0px;"><table cellpadding="0" cellspacing="0" border="0" class="wd-dropdown-menu">'
				+ _table
				+ '</table></form>';

			oDiv.innerHTML = _table;
		}
		return oDiv;
	},
	
	this.PopupShow = function(pos, div, controlpos)
	{
		if (!this.oDiv && !div || (typeof(this.oDiv) != "object" && typeof(div) != "object"))
		{
			return;
		}
		else if (div)
		{
			if (controlpos)
				this.ControlPos = controlpos;

			this.bRemoveElement = false;
			this.oDiv = div;
		}
		else
		{
			this.bRemoveElement = true;
		}

		var w = this.oDiv.offsetWidth;
		var h = this.oDiv.offsetHeight;
		if (h > 250)
		{
			this.oDiv.style.height = "250px";
			this.oDiv.style.overflow = "auto";
			w += 15;
			this.oDiv.className = "wd-dropdown-menu-oveflow";
			h = 250;
		}
		pos = jsUtils.AlignToPos(pos, w, h);
		for (var ii in pos)
		{
			if (isNaN(pos[ii]) || !pos[ii] || pos[ii] <= 0)
				pos[ii] = 0;
		}
		this.oDiv.style.width = w + 'px';
		this.oDiv.style.visibility = 'visible';
		this.oDiv.style.display = 'block';
		jsFloatDiv.Show(this.oDiv, parseInt(pos["left"]), parseInt(pos["top"]), 5, true, false);
		if (this.oControl != null && this.oControl.className)
		{
			this.oControl.className += ' intask-dropdown-pointer-active';
		}
		jsUtils.addEvent(document, "click", _this.CheckClick);
		jsUtils.addEvent(document, "keypress", _this.OnKeyPress);

	    this.oDiv.style.MozUserSelect = 'none';
	}

	this.PopupHide = function()
	{ 
		jsUtils.removeEvent(document, "click", _this.CheckClick);
		jsUtils.removeEvent(document, "keypress", _this.OnKeyPress);
		if (!this.oDiv)
		{
			return false;
		}

		jsFloatDiv.Close(this.oDiv);
		if (this.oControl != null && this.oControl.className)
		{
			this.oControl.className = this.oControl.className.replace(" intask-dropdown-pointer-active", "");
		}
		if (this.bRemoveElement)
		{
			try
			{
				this.oDiv.parentNode.removeChild(this.oDiv);
				this.ControlPos = false;
			}
			catch(e)
			{
			}
		}
		this.oDiv.style.display = 'none';
		CloseWaitWindow();
	}

	this.CheckClick = function(e)
	{
		if(!_this.oDiv)
			return;

		if (_this.oDiv.style.visibility != 'visible')
			return;

        var windowSize = jsUtils.GetWindowSize();
        var x = e.clientX + windowSize.scrollLeft;
        var y = e.clientY + windowSize.scrollTop;

		/*menu region*/
		pos = jsUtils.GetRealPos(_this.oDiv);
		var posLeft = parseInt(pos["left"]);
		var posTop = parseInt(pos["top"])
		var posRight = posLeft + _this.oDiv.offsetWidth;
		var posBottom = posTop + _this.oDiv.offsetHeight;
		if(x >= posLeft && x <= posRight && y >= posTop && y <= posBottom)
			return;

		if(_this.ControlPos)
		{
			var pos = _this.ControlPos;
			if(x >= pos['left'] && x <= pos['right'] && y >= pos['top'] && y <= pos['bottom'])
				return;
		}
		_this.PopupHide();
	}

	this.OnKeyPress = function(e)
	{
		if(!e) e = window.event
		if(!e) return;
		if(e.keyCode == 27)
			_this.PopupHide();
	},

	this.ShowMenu = function(control, data, switcher)
	{
		var id = "wd_id", pos = {"top" : 20, "left" : 20};
		var _data = false, _div = false;
		
		this.PopupHide();
		
		if (typeof(control) == "object")
		{
			id = control.id;
			pos = jsUtils.GetRealPos(control);
			this.ControlPos = pos;
			this.oControl = control;
			if (typeof(switcher) == "object" && switcher != null)
				pos = jsUtils.GetRealPos(switcher);
		}
		
		var _data = this.InitFromArray(id, data);
		
		this.oDiv = this.CreateMenu(id, _data);
		if (this.oDiv)
		{
			this.PopupShow(pos);
		}
	}
}
