var BXFFDocLink = function () {
	this.xpi_path_en =  "/bitrix/webdav/ff_bx_integration@bitrixsoft.com.xpi";
	this.xpi_path_ru =  "/bitrix/webdav/ff_bx_integration@1c-bitrix.ru.xpi";
	this.xpi_version = "0.7";

	try
	{
		if (window.phpVars.LANGUAGE_ID == 'ru')
		{
			this.xpi_path = this.xpi_path_ru;
		} else {
			this.xpi_path = this.xpi_path_en;
		}
	} catch(e) { 
		this.xpi_path = this.xpi_path_en;
	}

	this.CheckVersion = function ()
	{
		var pluginUpdate = false;
		if ('undefined' != typeof ff_bx_integration)
		{
			var ver = 2; // version ok
			pluginUpdate = this.CompareVersions(ff_bx_integration, this.xpi_version); 
			if (pluginUpdate) ver = 1; // need update
			return ver;
		}
		return 0; // no plugin
	};

	this.GetOfficeType = function ()
	{
		if ('undefined' != typeof ff_bx_integration_office)
			return ff_bx_integration_office;
		else
			return false;
	};

	this.Bind = function (eventName, eventData)
	{
		var id = 'BXPluginDataElement';

		if (BX(id))
			BX.remove(BX(id));

		var element = document.createElement(id);
		element.setAttribute("id", id);
		element.setAttribute("data", eventData);
		document.documentElement.appendChild(element);

		var evt = document.createEvent("Events");
		evt.initEvent(eventName, true, false);
		element.dispatchEvent(evt);
		return true;
	};

	this.OpenConfig = function()
	{
		return this.Bind("BitrixWebdavConfig", "");
	};

	this.OpenDoc = function(doc)
	{
		if (null != phpVars.platform)
		{
			var items = doc.split('.');
			var ext = items.pop().toLowerCase();
			items.push(ext);
			doc = items.join('.');
			if (phpVars.platform != navigator.platform && BX.userOptions)
			{
				var oUO = BX.userOptions;
				var _this = this;
				oUO.bSend = true;
				oUO.save('webdav', 'navigator', 'platform', navigator.platform, false);
				oUO.send(function() {_this.Bind("BitrixWebdavOpenFile", doc);});
			}
			else
			{
				this.Bind("BitrixWebdavOpenFile", doc);
			}
		}
	};

	this.CompareVersions = function (v1, v2) // true if v2 > v1
	{
		var a1 = v1.split(".");
		var a2 = v2.split(".");
		for (var i=0; i<a1.length; i++)
		{
			x1 = parseInt(a1[i]) || 0;
			x2 = parseInt(a2[i]) || 0;
			if (x2 > x1) return true;
		}
		return false;
	};

	this.ShowDialog = function (mode, file)
	{
		if (mode == null || mode == false)
		{
			var version = this.CheckVersion();
			if (version == 2)
				return this.OpenConfig();
			else if (version == 1)
			mode = 'update';
			else
				mode = 'install';
		}
		var msg = ((mode=='update') ? 'ff_extension_update' : 'ff_extension_install');
		var installUrl = this.xpi_path;

		BX.CDialog.prototype.btnWdInstall = BX.CDialog.btnWdInstall = {
			title: oText[ ((mode=='update') ? 'wd_update' : 'wd_install') ],
			id: 'installbtn',
			name: 'installbtn',
			action: function () {
				window.location = installUrl;
				BX.WindowManager.Get().Close();
			}
		};

		BX.CDialog.prototype.btnWdOpen = BX.CDialog.btnWdOpen = {
			title: oText['wd_open'],
			id: 'openbtn',
			name: 'openbtn',
			action: function () {
				var disable = BX.findChild(BX.WindowManager.Get().DIV, {'attribute':{'name':'ff_extension_disable'}}, true);
				if (disable.checked)
				{
					window.suggest_ff_extension = false;
					if (null != jsUserOptions)
					{
						if(!jsUserOptions.options)
							jsUserOptions.options = new Object();
						jsUserOptions.options['webdav.suggest.ff_extension'] = ['webdav', 'suggest', 'ff_extension', false, false];
						jsUserOptions.SendData(null);
					}
				}

				var dlg = BX.WindowManager.Get();
				window.open(dlg.PARAMS.file);
				dlg.Close();
			}
		};

		BX.CDialog.prototype.btnWdInstallCancell = BX.CDialog.btnWdInstallCancell = {
			title: oText['wd_install_cancel'],
			id: 'installcancelbtn',
			name: 'installcancelbtn',
			action: function () {
				var disable = BX.findChild(BX.WindowManager.Get().DIV, {'attribute':{'name':'ff_extension_disable'}}, true);
				if (disable && disable.checked)
				{
					window.suggest_ff_extension = false;
					if (null != jsUserOptions)
					{
						jsUserOptions.SaveOption('webdav', 'suggest', 'ff_extension', false);
					}
				}
				BX.WindowManager.Get().Close();
			}
		};
		msg = "<p>" + oText[msg].replace('#LINK#', this.xpi_path) + "</p>";
		var title = oText['ff_extension_title'];
		var help = "<p>" + oText['ff_extension_help'] + "</p>";
		var disable = "";
		if (file != null)
			disable = "<p style=\"margin-top:20px;\">" + oText['ff_extension_disable'] + "</p>";
		var arParams = {'title': title, 'content': msg+help+disable, 'width':'530', 'height':'200'};
		if (file != null)
		{
			arParams['file'] = file;
			arParams['buttons'] = [BX.CDialog.btnWdInstall, BX.CDialog.btnWdOpen];
		}
		else
		{
			arParams['buttons'] = [BX.CDialog.btnWdInstall, BX.CDialog.btnWdInstallCancell];
		}
		var popup = new BX.CDialog(arParams);
		popup.Show();
	};
};

function OpenDoc(link, officedoc)
{
	var file = link;
	if (! BX.type.isString(link))
		file = link.getAttribute('href');
	if (officedoc)
	{
		if (EditDocWithProgID(file))
			window.open(file);
	}
	else
	{
		window.open(file);
	}
	return false;
}

function WDEditOfficeTitle()
{
	if (navigator.userAgent.indexOf('Firefox') != -1)
	{
		var officetype = (new BXFFDocLink()).GetOfficeType();
		if (officetype == 'microsoft')
			return oText['wd_edit_in']+' MS Office';
		if (officetype == 'openoffice')
			return oText['wd_edit_in']+' OpenOffice';
		if (officetype == 'libreoffice')
			return oText['wd_edit_in']+' LibreOffice';
		return oText['wd_edit_in_other'];
	}
	else
	{
		return false;
	}
}

function WDCheckOfficeEdit()
{
	if (BX.browser.IsIE() || BX.browser.IsIE11())
	{
		try
		{
			if (new ActiveXObject("SharePoint.OpenDocuments.2"))
			{
				return true;
			}
		}
		catch(e) { }
		return false;
	}
	else if (navigator.userAgent.indexOf('Firefox') != -1)
	{
		var FFextension = new BXFFDocLink();
		var plugin = FFextension.CheckVersion();
		if (plugin == 2)
		{
			return true;
		}
		else if ((typeof window.suggest_ff_extension != 'undefined') && window.suggest_ff_extension == true)
		{
			return true;
		}
	}
	return false;
}

function EditDocWithProgID(file)
{
	var prefix = location.protocol + "//" + location.host;
	var url = file;
	if (url.indexOf(prefix) < 0) url = prefix + url;
	if (BX.browser.IsIE() || BX.browser.IsIE11())
	{
		try
		{
			var EditDocumentButton = new ActiveXObject("SharePoint.OpenDocuments.2");
			if (EditDocumentButton)
			{
				if (null != phpVars.platform)
				{
					if (phpVars.platform != navigator.platform && BX.userOptions)
					{
						var oUO = BX.userOptions;
						var _this = this;
						oUO.bSend = true;
						oUO.save('webdav', 'navigator', 'platform', navigator.platform, false);
						oUO.send(function() {
								EditDocumentButton.EditDocument2(window, url);
							});
						return false;
					}
					else
					{
						if(EditDocumentButton.EditDocument2(window, url))
							return false;
					}
				}
			}
		}
		catch(e) { }
		return true;
	}
	else if (navigator.userAgent.indexOf('Firefox') != -1)
	{
		var FFextension = new BXFFDocLink();
		var plugin = FFextension.CheckVersion();
		
		if (plugin == 2)
		{
			if ((navigator.userAgent.indexOf('Mac OS X') != -1) && (url.indexOf('.xl') != -1))
				url = url.replace(/ /g, "%20"); // MS Office 2011 mad
			FFextension.OpenDoc(url);
			return false;
		}
		else if ((typeof window.suggest_ff_extension != 'undefined') && window.suggest_ff_extension == true)
		{
			FFextension.ShowDialog(null, url);
			return false;
		}
		else
		{
			return true;
		}
	}
	return true;
}

function BXWdCloseBnr(obBnr)
{
	if (null != obBnr)
		obBnr.parentNode.removeChild(obBnr);
	if (null != jsUserOptions)
	{
		if(!jsUserOptions.options)
			jsUserOptions.options = new Object();
		jsUserOptions.options['webdav.note.show'] = ['webdav', 'note', 'show', false, false];
		jsUserOptions.SendData(null);
	}
}
/************************************************/
if (window.JCFloatDiv == null)
{
	function JCFloatDiv()
	{
		var _this = this;
		this.floatDiv = null;
		this.x = this.y = 0;
	
		this.Show = function(div, left, top, dxShadow, restrictDrag, showSubFrame)
		{
			if (showSubFrame !== false)
				showSubFrame = true;
			var zIndex = parseInt(div.style.zIndex);
			if(zIndex <= 0 || isNaN(zIndex))
				zIndex = 100;
	
			div.style.zIndex = zIndex;
	
			if (left < 0)
				left = 0;
	
			if (top < 0)
				top = 0;
	
			div.style.left = left + "px";
			div.style.top = top + "px";
	
			if(jsUtils.IsIE() && showSubFrame)
			{
				var frame = document.getElementById(div.id+"_frame");
				if(!frame)
				{
					frame = document.createElement("IFRAME");
					frame.src = "javascript:''";
					frame.id = div.id+"_frame";
					frame.style.position = 'absolute';
					frame.style.zIndex = zIndex-1;
					document.body.appendChild(frame);
				}
				frame.style.width = div.offsetWidth + "px";
				frame.style.height = div.offsetHeight + "px";
				frame.style.left = div.style.left;
				frame.style.top = div.style.top;
				frame.style.visibility = 'visible';
			}
	
			/*Restrict drag*/
			div.restrictDrag = restrictDrag || false;
	
			/*shadow*/
			if(isNaN(dxShadow))
				dxShadow = 5;
				
			if(dxShadow > 0)
			{
				var img = document.getElementById(div.id+'_shadow');
				if(!img)
				{
					tName = ".default";
					if ((typeof(phpVars) == "object")&&(phpVars.ADMIN_THEME_ID))
					{
						tName = phpVars.ADMIN_THEME_ID;
					}
					if(jsUtils.IsIE())
					{
			 			img = document.createElement("DIV");
			 			img.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='/bitrix/themes/"+tName+"/images/shadow.png',sizingMethod='scale')";
					}
					else
					{
			 			img = document.createElement("IMG");
						img.src = '/bitrix/themes/'+tName+'/images/shadow.png';
					}
					img.id = div.id+'_shadow';
					img.style.position = 'absolute';
					img.style.zIndex = zIndex-2;
					img.style.left = '-1000px';
					img.style.top = '-1000px';
					img.style.lineHeight = 'normal';
					document.body.appendChild(img);
				}
				img.style.width = div.offsetWidth+'px';
				img.style.height = div.offsetHeight+'px';
				img.style.left = parseInt(div.style.left)+dxShadow+'px';
				img.style.top = parseInt(div.style.top)+dxShadow+'px';
				img.style.visibility = 'visible';
			}
			div.dxShadow = dxShadow;
		}
	
		this.Close = function(div)
		{
			if(!div)
				return;
			var sh = document.getElementById(div.id+"_shadow");
			if(sh)
				sh.style.visibility = 'hidden';
	
			var frame = document.getElementById(div.id+"_frame");
			if(frame)
				frame.style.visibility = 'hidden';
		}
	
		this.Move = function(div, x, y)
		{
			if(!div)
				return;
	
			var dxShadow = div.dxShadow;
			var left = parseInt(div.style.left)+x;
			var top = parseInt(div.style.top)+y;
	
			if (div.restrictDrag)
			{
				//Left side
				if (left < 0)
					left = 0;
	
				//Right side
				if ( (document.compatMode && document.compatMode == "CSS1Compat"))
					windowWidth = document.documentElement.scrollWidth;
				else
				{
					if (document.body.scrollWidth > document.body.offsetWidth ||
						(document.compatMode && document.compatMode == "BackCompat") ||
						(document.documentElement && !document.documentElement.clientWidth)
					)
						windowWidth = document.body.scrollWidth;
					else
						windowWidth = document.body.offsetWidth;
				}
	
				var floatWidth = div.offsetWidth;
				if (left > (windowWidth - floatWidth - dxShadow))
					left = windowWidth - floatWidth - dxShadow;
	
				//Top side
				if (top < 0)
					top = 0;
			}
	
			div.style.left = left+'px';
			div.style.top = top+'px';
	
			this.AdjustShadow(div);
		}
	
		this.HideShadow = function(div)
		{
			var sh = document.getElementById(div.id + "_shadow");
			sh.style.visibility = 'hidden';
		}
	
		this.UnhideShadow = function(div)
		{
			var sh = document.getElementById(div.id + "_shadow");
			sh.style.visibility = 'visible';
		}
	
		this.AdjustShadow = function(div)
		{
			var sh = document.getElementById(div.id + "_shadow");
			if(sh && sh.style.visibility != 'hidden')
			{
				var dxShadow = div.dxShadow;
	
				sh.style.width = div.offsetWidth+'px';
				sh.style.height = div.offsetHeight+'px';
				sh.style.left = parseInt(div.style.left)+dxShadow+'px';
				sh.style.top = parseInt(div.style.top)+dxShadow+'px';
			}
	
			var frame = document.getElementById(div.id+"_frame");
			if(frame)
			{
				frame.style.width = div.offsetWidth + "px";
				frame.style.height = div.offsetHeight + "px";
				frame.style.left = div.style.left;
				frame.style.top = div.style.top;
			}
		}
	
		this.StartDrag = function(e, div)
		{
			if(!e)
				e = window.event;
			this.x = e.clientX + document.body.scrollLeft;
			this.y = e.clientY + document.body.scrollTop;
			this.floatDiv = div;
	
			jsUtils.addEvent(document, "mousemove", this.MoveDrag);
			document.onmouseup = this.StopDrag;
			if(document.body.setCapture)
				document.body.setCapture();
	
			var b = document.body;
		    b.ondrag = jsUtils.False;
		    b.onselectstart = jsUtils.False;
		    b.style.MozUserSelect = _this.floatDiv.style.MozUserSelect = 'none';
		    b.style.cursor = 'move';
	    }
	
		this.StopDrag = function(e)
		{
			if(document.body.releaseCapture)
				document.body.releaseCapture();
	
			jsUtils.removeEvent(document, "mousemove", _this.MoveDrag);
			document.onmouseup = null;
	
			this.floatDiv = null;
	
			var b = document.body;
			b.ondrag = null;
			b.onselectstart = null;
			b.style.MozUserSelect = _this.floatDiv.style.MozUserSelect = '';
		    b.style.cursor = '';
		}
	
		this.MoveDrag = function(e)
		{
			var x = e.clientX + document.body.scrollLeft;
			var y = e.clientY + document.body.scrollTop;
	
			if(_this.x == x && _this.y == y)
				return;
	
			_this.Move(_this.floatDiv, (x - _this.x), (y - _this.y));
			_this.x = x;
			_this.y = y;
		}
	}
}

var WDJSFloatDiv = new JCFloatDiv();
if(window.phpVars && (!window.phpVars.ADMIN_THEME_ID || window.phpVars.ADMIN_THEME_ID && window.phpVars.ADMIN_THEME_ID == 'ADMIN_THEME_ID'))
{
	window.phpVars.ADMIN_THEME_ID = '.default';
}
WDJSFloatDiv.Show = function(div, left, top, dxShadow, bSubstrate, bIframe)
{
	var zIndex = parseInt(div.style.zIndex);
	if(zIndex <= 0 || isNaN(zIndex))
		zIndex = 100;
	div.style.zIndex = zIndex;
	div.style.left = left + "px";
	div.style.top = top + "px";

	if(jsUtils.IsIE() && bIframe != "N")
	{
		var frame = document.getElementById(div.id+"_frame");
		if(!frame)
		{
			frame = document.createElement("IFRAME");
			frame.src = "javascript:''";
			frame.id = div.id+"_frame";
			frame.style.position = 'absolute';
			frame.style.zIndex = zIndex-1;
			document.body.appendChild(frame);
		}
		frame.style.width = div.offsetWidth + "px";
		frame.style.height = div.offsetHeight + "px";
		frame.style.left = div.style.left;
		frame.style.top = div.style.top;
		frame.style.visibility = 'visible';
	}

	/*shadow*/
	if(isNaN(dxShadow))
		dxShadow = 5;
	if(dxShadow > 0)
	{
		var img = document.getElementById(div.id+'_shadow');
		if(!img)
		{
			if(jsUtils.IsIE())
			{
	 			img = document.createElement("DIV");
	 			img.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src=/bitrix/components/bitrix/webdav/templates/.default/images/shadow.png',sizingMethod='scale')";
			}
			else
			{
	 			img = document.createElement("IMG");
				img.src = '/bitrix/components/bitrix/webdav/templates/.default/images/shadow.png';
			}
			img.id = div.id+'_shadow';
			img.style.position = 'absolute';
			img.style.zIndex = zIndex-2;
			document.body.appendChild(img);
		}
		img.style.width = div.offsetWidth+'px';
		img.style.height = div.offsetHeight+'px';
		img.style.left = parseInt(div.style.left)+dxShadow+'px';
		img.style.top = parseInt(div.style.top)+dxShadow+'px';
		img.style.visibility = 'visible';
	}
	
	if (bSubstrate != "N")
	{
		var substrate = document.getElementById("wd_substrate");
		if(!substrate)
		{
			substrate = document.createElement("DIV");
			substrate.id = 	"wd_substrate";
			substrate.style.zIndex = zIndex-3;
			substrate.style.position = 	'absolute';
			substrate.style.display = 'none';
			substrate.style.visibility = 'hidden';
			substrate.style.background = '#052635';
			substrate.style.opacity = '0.5';
			if (substrate.style.MozOpacity)
				substrate.style.MozOpacity = '0.5';
			else if (substrate.style.KhtmlOpacity)
				substrate.style.KhtmlOpacity = '0.5';
			if (jsUtils.IsIE())
			{
		 		substrate.style.filter += "progid:DXImageTransform.Microsoft.Alpha(opacity=50)";
			}
			document.body.appendChild(substrate);
		}
		substrate.style.display = 'block';
		substrate.style.left = 0;
		substrate.style.top = 0;
		var WindowSize = jsUtils.GetWindowSize();
		substrate.style.width = WindowSize["scrollWidth"] + "px";
		substrate.style.height = WindowSize["scrollHeight"] + "px";
		substrate.style.visibility = 'visible';
	}
}
WDJSFloatDiv.Close = function(div)
{
	if(!div)
		return;
	var sh = document.getElementById(div.id+"_shadow");
	if(sh)
		sh.style.visibility = 'hidden';

	var frame = document.getElementById(div.id+"_frame");
	if(frame)
		frame.style.visibility = 'hidden';
		
	var substrate = document.getElementById("wd_substrate");
	if(substrate)
	{
		substrate.style.display = 'none';
		substrate.style.visibility = 'hidden';
	}
}
		
/************************************************/
function WDMenu()
{
	var _this = this;
	this.active = null;
	
	this.PopupShow = function(div, pos)
	{
		this.PopupHide();
		if(!div)
			return;
		if (typeof(pos) != "object")
			pos = {};
			
		this.active = div.id;
	    div.ondrag = jsUtils.False;
		
		jsUtils.addEvent(document, "keypress", _this.OnKeyPress);
		
		div.style.width = div.offsetWidth + 'px';
		div.style.visibility = 'visible';
		
		var res = jsUtils.GetWindowSize();
		pos['top'] = parseInt(res["scrollTop"] + res["innerHeight"]/2 - div.offsetHeight/2);
		pos['left'] = parseInt(res["scrollLeft"] + res["innerWidth"]/2 - div.offsetWidth/2);
		WDJSFloatDiv.Show(div, pos["left"], pos["top"]);

/*	    div.onselectstart = jsUtils.False;
	    div.style.MozUserSelect = 'none';
*/	}

	this.PopupHide = function()
	{
		var div = document.getElementById(_this.active);
		if(div)
		{
			WDJSFloatDiv.Close(div);
			div.parentNode.removeChild(div);
		}

		this.active = null;
//		jsUtils.removeEvent(document, "click", _this.CheckClick);
		jsUtils.removeEvent(document, "keypress", _this.OnKeyPress);
	}

	this.CheckClick = function(e)
	{
		var div = document.getElementById(_this.active);
		
		if(!div)
		{
			return;
		}

		if (div.style.visibility != 'visible')
			return;
			
		if (!jsUtils.IsIE() && e.target.tagName == 'OPTION')
			return false;
			
		var x = e.clientX + document.body.scrollLeft;
		var y = e.clientY + document.body.scrollTop;

		/*menu region*/
		var posLeft = parseInt(div.style.left);
		var posTop = parseInt(div.style.top);
		var posRight = posLeft + div.offsetWidth;
		var posBottom = posTop + div.offsetHeight;
		if(x >= posLeft && x <= posRight && y >= posTop && y <= posBottom)
			return;

		if(_this.controlDiv)
		{
			var pos = jsUtils.GetRealPos(_this.controlDiv);
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

	this.IsVisible = function()
	{
		return (document.getElementById(this.active).style.visibility != 'hidden');
	}
}
WDMenu = new WDMenu();

function WDConfirm(title, msg, callee)
{
    var wdc = new BX.CDialog({'title' : title, 'content' : "<div style=\"font-size:115%;\">"+msg+"</div>", 'width':500, 'height':60});
    wdc.onOK = callee;
    wdc.SetButtons("<input type=\"button\" onClick=\"var wnd = BX.WindowManager.Get(); wnd.Close(); wnd.onOK(); \" value=\""+phpVars2.messYes+"\"/><input type=\"button\" onClick=\"BX.WindowManager.Get().Close();\" value=\""+phpVars2.messNo+"\"/>");
    wdc.Show();
}

function WDConfirmDelete(title, msg, moveMsg, forceMsg, cancelMsg, moveCallback, forceCallback)
{
    var wdc = new BX.CDialog({'title' : title, 'content' :
	    "<div style=\"font-size:115%;\">"+msg+"<div style=\"margin-top: 5px;\">" +
	    "</div></div>", 'width':500, 'height':70});
	wdc.onOK = moveCallback;
	wdc.onForceOK = forceCallback;

    wdc.SetButtons("<input type=\"button\" class=\"adm-btn-save\" onClick=\"var wnd = BX.WindowManager.Get(); wnd.Close(); wnd.onOK(); \" value=\""+moveMsg+"\"/><input type=\"button\" onClick=\"var wnd = BX.WindowManager.Get(); wnd.Close(); wnd.onForceOK(); \" value=\""+forceMsg+"\"/><input type=\"button\" onClick=\"BX.WindowManager.Get().Close();\" value=\""+cancelMsg+"\"/>");
    wdc.Show();
}

function WDConfirmTrash(title, msg, callee)
{
    var wdc = new BX.CDialog({'title' : title, 'content' : "<div>"+msg+"</div>", 'width':500, 'height':60});
	BX.addCustomEvent(wdc, 'onBeforeWindowClose', function(){
		if(wdGlobalStopDropTrash)
		{
			//BX.reload();
		}
	});
    wdc.onOK = callee;
    wdc.SetButtons("<input type=\"button\" onClick=\"var wnd = BX.WindowManager.Get(); wnd.onOK(); \" value=\""+phpVars2.messYes+"\"/><input type=\"button\" onClick=\"BX.WindowManager.Get().Close();\" value=\""+phpVars2.messNo+"\"/>");
    wdc.Show();
}
wdGlobalStopDropTrash = false;
wdGlobalAllItemsToDelete = 0;
wdGlobalAllItemsAlreadyDelete = 0;
function WDDropTrashFlow(startUrl)
{
	wdGlobalStopDropTrash = false;
	var stepForDrop = function(){
		if(wdGlobalStopDropTrash)
		{
			wdGlobalStopDropTrash = false;
			wdGlobalAllItemsToDelete = 0;
			wdGlobalAllItemsAlreadyDelete = 0;
			return false;
		}
		BX.ajax({
			method : "POST",
			dataType : "json",
			url : BX.util.remove_url_param(startUrl, 'get_count_elements'),
			data :  {
				portion_delete: 'Y',
				sessid: BX.bitrix_sessid()
			},
			onsuccess: function(result) {
				if (result && result.status == 'success')
				{
					if(!result.finish)
					{
						//run next step
						stepForDrop();
						if(result.deleteItems)
						{
							var count = BX('wd_elemens_to_drop');
							if(count)
							{
								var alreadyCount = count.textContent || count.innerText;
								wdGlobalAllItemsAlreadyDelete += parseInt(result.deleteItems, 10);
								var number = parseInt(alreadyCount, 10) - parseInt(result.deleteItems, 10);
								if(wdGlobalAllItemsAlreadyDelete >= wdGlobalAllItemsToDelete)
								{
									number = '0';
									wdGlobalStopDropTrash = true;
									BX.hide(BX('wd_progress'));
									if(wdGlobalAllItemsToDelete)
									{
										BX.reload();
									}
								}
								BX.adjust(count, {text: number});
							}
						}
					}
					else
					{
						BX.reload();
					}
				}
			}
		});
		return true;
	};

	BX.ajax({
		method : "POST",
		dataType : "json",
		url : startUrl,
		data: {
			sessid: BX.bitrix_sessid()
		},
		onsuccess: function(result) {
			if (result && result.status == 'success')
			{
				if(result.items > 0)
				{
					wdGlobalAllItemsToDelete = result.items;
					wdGlobalAllItemsAlreadyDelete = 0;
					if(!stepForDrop())
					{
						return;
					}
					var wnd = BX.WindowManager.Get();
					wnd.ClearButtons();
					wnd.SetButtons([
						new BX.CWindowButton({
							title: BX.message('stop_drop_trash'),
							action: function()
							{
								wdGlobalStopDropTrash = true;
								if(wdGlobalStopDropTrash)
								{
									//BX.reload();
								}
								this.parentWindow.Close();
							}
						})
					]);
					wnd.SetContent(BX.create('div', {
						children: [
							BX.create('div', {
								id: 'wd_progress',
								children: [
									BX.create('span', {
										text: BX.message('drop_trash_count_elements')
									}),
									BX.create('span', {
										props:{
											id: 'wd_elemens_to_drop'
										},
										text: result.items
									}),
									BX.create('span', {
										style: {
											margin: '0 auto',
											backgroundColor: 'transparent',
											border: 'none',
											position: 'relative'
										},
										props:{
											id: 'wd_progress',
											className: 'bx-core-waitwindow'
										}
									})
								]
							})
						]
					}));
				}
			}
		}
	});
}

function debug_info(text)
{
	container_id = 'debug_info';
	var div = document.getElementById(container_id);
	if (!div || div == null)
	{
		div = document.body.appendChild(document.createElement("DIV"));
		div.id = container_id;
	}
	if (div.className != "debug-info")
	{
		div.className = "debug-info";
		div.style.left = document.body.scrollLeft + (document.body.clientWidth - div.offsetWidth) - 5 + "px";
		div.style.top = document.body.scrollTop + 5 + "px";
	}
	div.innerHTML += text + "<br />";
	return;
}

function WDDownloadDesktop()
{
	document.location.href = (BX.browser.IsMac()? "http://dl.bitrix24.com/b24/bitrix24_desktop.dmg": "http://dl.bitrix24.com/b24/bitrix24_desktop.exe");
	WDCloseDiskBanner();
}

function WDCloseDiskBanner()
{
	var banner = BX('wd-banner-disk-install-offer');
	if(banner)
	{
		BX.hide(banner);
	}
	BX.userOptions.save('webdav', '~banner-offer', 'disk', true);
	return window.event? BX.PreventDefault() : false;
}

bIsLoadedUtils = true;
