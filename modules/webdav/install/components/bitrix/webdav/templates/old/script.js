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


if (window.WaitOnKeyPress == null)
{
	function WaitOnKeyPress(e)
	{
		if(!e) e = window.event
		if(!e) return;
		if(e.keyCode == 27)
			CloseWaitWindow();
	}
}

if (window.ShowWaitWindow == null)
{
	function ShowWaitWindow()
	{
		CloseWaitWindow();
	
		var obWndSize = jsUtils.GetWindowSize();
	
		var div = document.body.appendChild(document.createElement("DIV"));
		div.id = "wait_window_div";
		div.innerHTML = phpVars.messLoading;
		div.className = "waitwindow";
		//div.style.left = obWndSize.scrollLeft + (obWndSize.innerWidth - div.offsetWidth) - (jsUtils.IsIE() ? 5 : 20) + "px";
		div.style.right = (5 - obWndSize.scrollLeft) + 'px';
		div.style.top = obWndSize.scrollTop + 5 + "px";
	
		if(jsUtils.IsIE())
		{
			var frame = document.createElement("IFRAME");
			frame.src = "javascript:''";
			frame.id = "wait_window_frame";
			frame.className = "waitwindow";
			frame.style.width = div.offsetWidth + "px";
			frame.style.height = div.offsetHeight + "px";
			frame.style.right = div.style.right;
			frame.style.top = div.style.top;
			document.body.appendChild(frame);
		}
		jsUtils.addEvent(document, "keypress", WaitOnKeyPress);
	}
}

if (window.CloseWaitWindow == null)
{
	function CloseWaitWindow()
	{
		jsUtils.removeEvent(document, "keypress", WaitOnKeyPress);
	
		var frame = document.getElementById("wait_window_frame");
		if(frame)
			frame.parentNode.removeChild(frame);
	
		var div = document.getElementById("wait_window_div");
		if(div)
			div.parentNode.removeChild(div);
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
					if(jsUtils.IsIE())
					{
			 			img = document.createElement("DIV");
			 			img.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='/bitrix/themes/"+phpVars.ADMIN_THEME_ID+"/images/shadow.png',sizingMethod='scale')";
					}
					else
					{
			 			img = document.createElement("IMG");
						img.src = '/bitrix/themes/'+phpVars.ADMIN_THEME_ID+'/images/shadow.png';
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
bIsLoadedUtils = true;