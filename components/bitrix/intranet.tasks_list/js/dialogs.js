if (!window.XMLHttpRequest)
{
	var XMLHttpRequest = function()
	{
		try { return new ActiveXObject("MSXML3.XMLHTTP") } catch(e) {}
		try { return new ActiveXObject("MSXML2.XMLHTTP.3.0") } catch(e) {}
		try { return new ActiveXObject("MSXML2.XMLHTTP") } catch(e) {}
		try { return new ActiveXObject("Microsoft.XMLHTTP") } catch(e) {}
	}
}

function JSIntTaskDialog(arConfig, arEvents)
{
	this.arConfig = arConfig;
	this.arEvents = arEvents;
	this.actionUrl = this.arConfig.page;
	this.parentID = this.arConfig.parentSectionId;

	this.eventXmlHttpGet = new XMLHttpRequest();

	this.Init();
}

JSIntTaskDialog.prototype.Init = function()
{
	this.menuDiv = document.getElementById('its_menu_div');
	this.bVisualEffects = false;

	this.SetMenuUrls();
}

JSIntTaskDialog.prototype.SetMenuUrls = function()
{
	var _this = this;

	var createFolderA = document.getElementById('intask_create_folder_a');
	if (createFolderA)
		createFolderA.onclick = function(){_this.ShowFolderDlg();};

	//var createTaskA = document.getElementById('intask_create_task_a');
	//createTaskA.onclick = function(){_this.ShowTaskDlg();};
}

JSIntTaskDialog.prototype.CreateFolderDlg = function()
{
	this.bFolderDlgShow = false;
	this.folderDialog = document.getElementById('intask_folder_dialog');
	document.body.appendChild(this.folderDialog);

	var _this = this;
	var closeBut = document.getElementById('intask_folder_dialog_close');
	var cancelBut = document.getElementById('intask_folder_dialog_cancel');
	closeBut.onclick = cancelBut.onclick = function() {_this.CloseFolderDlg();};

	var saveBut = document.getElementById('intask_folder_dialog_save');
	saveBut.onclick = function(){if (_this.SaveFolder()){_this.CloseFolderDlg();}};

	var delBut = document.getElementById('intask_folder_dialog_delete');
	delBut.onclick = function() {_this.DeleteFolder(_this.oFolder.currentFolder); _this.CloseFolderDlg();};
	window['BX_InTaskFld_OnKeypress'] = function(e)
	{
		if (!e) e = window.event
		if (!e) return;
		if (e.keyCode == 27)
			_this.CloseFolderDlg();
		else if (EnterAndNotTextArea(e))
			saveBut.onclick();
	};
	window['BX_InTaskFld_OnClick'] = function(e)
	{
		setTimeout(function(){
			if (!e) e = window.event;
			if (!e) return;
			if (!_this.bFolderDlgShow || _this.bEditFolderDlgOver)
				return;
			_this.CloseFolderDlg();
		}, 10);
	};
	this.folderDialog.onmouseover = function(){_this.bEditFolderDlgOver = true;};
	this.folderDialog.onmouseout = function(){_this.bEditFolderDlgOver = false;};

	// Заполнение и создание диалога

	this.oFolder = {
		oName: document.getElementById('folder_name'),
		delBut: delBut,
		dialogTitle: document.getElementById('intask_folder_dialog_title'),
		currentFolder: {}
	};
}

JSIntTaskDialog.prototype.ShowFolderDlg = function(oFolderParam)
{
	if (this.bVisualEffects)
		return;
	if (!this.folderDialog)
		this.CreateFolderDlg();
	else if (this.bFolderDlgShow)
		return this.CloseFolderDlg();

	this.folderDialog.style.display = 'block';

	if (!oFolderParam)
	{
		oFolderParam = {};
		this.oFolder.bNew = true;
		this.oFolder.dialogTitle.innerHTML = EC_MESS.NewFolderTitle;
		this.oFolder.delBut.style.display = 'none';
	}
	else
	{
		this.oFolder.bNew = false;
		this.oFolder.dialogTitle.innerHTML = EC_MESS.EditFolderTitle;
		this.oFolder.delBut.style.display = 'inline';
	}
	this.oFolder.currentFolder = oFolderParam;
	this.bEditFolderDlgOver = false;

	var _this = this;
	jsUtils.addEvent(document, "keypress", window['BX_InTaskFld_OnKeypress']);
	setTimeout(function(){jsUtils.addEvent(document, "click", window['BX_InTaskFld_OnClick']);},1);
	this.oFolder.oName.value = bxSpChBack(oFolderParam.NAME) || '';

	var h = parseInt(this.folderDialog.firstChild.offsetHeight) + 20;
	this.ResizeDialogWin(this.folderDialog, 400, h);
	var pos = this.GetCenterWindowPos(400, h);
	this.bFolderDlgShow = true;
	jsFloatDiv.Show(this.folderDialog, pos.left, pos.top, 5, false, false);
}

JSIntTaskDialog.prototype.GetCenterWindowPos = function(w, h)
{
	if (!w) w = 400;
	if (!h) h = 300;
	var S = jsUtils.GetWindowSize();
	var top = bxInt(bxInt(S.scrollTop) + (S.innerHeight - h) / 2 - 30);
	var left = bxInt(bxInt(S.scrollLeft) + (S.innerWidth - w) / 2 - 30);
	return {top: top, left: left};
}

JSIntTaskDialog.prototype.ResizeDialogWin = function(div, w, h)
{
	if (w !== false)
		div.style.width = parseInt(w) + 'px';
	if (h !== false)
		div.style.height = parseInt(h) + 'px';
	jsFloatDiv.AdjustShadow(div);
}

JSIntTaskDialog.prototype.CloseFolderDlg = function()
{
	this.bFolderDlgShow = false;
	this.folderDialog.style.display = 'none';
	jsFloatDiv.Close(this.folderDialog);
	jsUtils.removeEvent(document, "keypress", window['BX_InTaskFld_OnKeypress']);
	jsUtils.removeEvent(document, "click", window['BX_InTaskFld_OnClick']);
}

JSIntTaskDialog.prototype.SaveFolder = function()
{
	if (this.eventXmlHttpGet.readyState % 4)
		return;

	var el = this.oFolder.currentFolder;
	if (this.oFolder.oName.value.length <= 0)
	{
		alert(EC_MESS.FolderNameErr);
		this.bEditFolderDlgOver = true;
		return false;
	}
	var str_params = '';
	if (this.actionUrl.indexOf('?') != -1)
		str_params += '&';
	else
		str_params += '?';
	str_params += 'action=folder_edit&parent=' + jsUtils.urlencode(this.parentID);
	if (el.ID)
		str_params += '&id=' + jsUtils.urlencode(el.ID);
	str_params += '&name=' + jsUtils.urlencode(this.oFolder.oName.value);
	str_params += '&bx_task_action_request=Y';
	str_params += '&' + this.arConfig.userSessId;
	str_params += '&r=' + Math.floor(Math.random() * 1000);

	this.eventXmlHttpGet.open("get", this.actionUrl + str_params);
	this.eventXmlHttpGet.send(null);

	var _this = this;
	this.eventXmlHttpGet.onreadystatechange = function()
	{
		if (_this.eventXmlHttpGet.readyState == 4 && _this.eventXmlHttpGet.status == 200)
		{
			_this.CloseWaitWindow();
			if (_this.eventXmlHttpGet.responseText)
				return _this.DisplayError(_this.eventXmlHttpGet.responseText);
			else
				window.location.reload();
		}
	}

	this.ShowWaitWindow();
	return true;
}

JSIntTaskDialog.prototype.DisplayError = function(str)
{
	alert(str || 'Error!');
}

JSIntTaskDialog.prototype.ShowWaitWindow = function()
{
//	jsAjaxUtil.ShowLocalWaitWindow(567, this.menuDiv);
}

JSIntTaskDialog.prototype.CloseWaitWindow = function()
{
//	jsAjaxUtil.CloseLocalWaitWindow(567, this.menuDiv);
}

JSIntTaskDialog.prototype.DeleteFolder = function(el)
{
	if (!el.ID || !confirm(EC_MESS.DelFolderConfirm))
		return;

	if (this.eventXmlHttpGet.readyState % 4)
		return;

	var str_params = '';
	if (this.actionUrl.indexOf('?') != -1)
		str_params += '&';
	else
		str_params += '?';
	str_params += 'action=folder_delete&parent=' + jsUtils.urlencode(this.parentID);
	if (el.ID)
		str_params += '&id=' + jsUtils.urlencode(el.ID);
	str_params += '&bx_task_action_request=Y';
	str_params += '&' + this.arConfig.userSessId;
	str_params += '&r=' + Math.floor(Math.random() * 1000);

	this.eventXmlHttpGet.open("get", this.actionUrl + str_params);
	this.eventXmlHttpGet.send(null);

	var _this = this;
	this.eventXmlHttpGet.onreadystatechange = function()
	{
		if (_this.eventXmlHttpGet.readyState == 4 && _this.eventXmlHttpGet.status == 200)
		{
			_this.CloseWaitWindow();
			if (_this.eventXmlHttpGet.responseText)
				return _this.DisplayError(_this.eventXmlHttpGet.responseText);
			else
				window.location.reload();
		}
	}

	this.ShowWaitWindow();
	return true;
}

window.EnterAndNotTextArea = function(e)
{
	if(e.keyCode == 13)
	{
		var targ = e.target || e.srcElement;
		if (targ && targ.nodeName && targ.nodeName.toLowerCase() != 'textarea')
		{
			jsUtils.PreventDefault(e);
			return true;
		}
	}
	return false;
}

window.bxSpChBack = function(str)
{
	if (!str)
		return '';
	str = str.replace(/&lt;/g, '<');
	str = str.replace(/&gt;/g, '>');
	str = str.replace(/&quot;/g, '"');
	str = str.replace(/&amp;/g, '&');
	str = str.replace(/script_>/g, 'script>');
	return str;
}

window.bxInt = function(x)
{
	return parseInt(x, 10);
}




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
