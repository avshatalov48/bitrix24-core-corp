if (null == window.phpVars)
	window.phpVars = {};
if (null == phpVars.ADMIN_THEME_ID)
	phpVars.ADMIN_THEME_ID = '.default';

function JCCalendar()
{
	var _this = this;
	this.mess = {};
	if (jsCalendarMess) this.mess = jsCalendarMess;
	this.floatDiv = null;
	this.content = null;
	this.dateInitial = new Date();
	this.dateCurrent = null;
	this.dateCreate = new Date();
	this.bTime = false;
	this.bFirst = true;
	this.menu = null;
	this.form = this.field = this.fieldFrom = this.fieldTo = null;

	/* Main functions */
	this.Show = function(obj, field, fieldFrom, fieldTo, bTime, serverTime, form_name, bHideTimebar)
	{
		if (!form_name) form_name = null;

		this.bTime = !bHideTimebar && bTime;

		if(this.floatDiv)
			this.Close();

		if (null != form_name)
			this.field = document.forms[form_name][field];
		else
			this.field = document.getElementById(field);
		if (null == this.field) this.field = document.getElementsByName(field)[0];

		if (!this.field)
		{
			alert(this.mess.error_fld);
			return;
		}

		this.form = (null == form_name) ? this.field.form : document.forms[form_name];

		if (null != fieldFrom)
		{
			if (null != this.form) this.fieldFrom = this.form.elements[fieldFrom];
			if (null == this.fieldFrom) this.fieldFrom = document.getElementById(fieldFrom);
			if (null == this.fieldFrom) this.fieldFrom = document.getElementsByName(fieldFrom)[0];
		}

		if (null != fieldTo)
		{
			if (null != this.form) this.fieldTo = this.form.elements[fieldTo];
			if (null == this.fieldTo) this.fieldTo = document.getElementById(fieldTo);
			if (null == this.fieldTo) this.fieldTo = document.getElementsByName(fieldTo)[0];
		}


		var difference = serverTime*1000 - (this.dateCreate.valueOf() - this.dateCreate.getTimezoneOffset()*60000);

		this.dateCurrent = this.ParseDate(this.field.value);
		if(this.dateCurrent)
			this.dateInitial.setTime(this.dateCurrent.valueOf());
		else if(this.bFirst)
		{
			this.dateInitial.setTime((new Date()).valueOf() + difference);
			this.dateInitial.setHours(0, 0, 0);
		}

		var div = document.body.appendChild(document.createElement("DIV"));
		div.id = "calendar_float_div";
		div.className = "bx-calendar-float";
		div.style.position = 'absolute';
		div.style.left = '-1000px';
		div.style.top = '-1000px';
		div.style.zIndex = '150';

		this.hoursSpin = new JCSpinner('hours');
		this.minutesSpin = new JCSpinner('minutes');
		this.secondsSpin = new JCSpinner('seconds');

		div.innerHTML =
			'<div class="bx-calendar-title">'+
			'<table cellspacing="0" style="width:100% !important; padding:0px !important; ">'+
			'	<tr>'+
			'		<td class="bx-calendar-title-text" onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById(\'calendar_float_div\'));" id="calendar_float_title">'+this.mess["title"]+'</td><td width="0%"><a class="bx-calendar-close" href="javascript:jsCalendar.Close();" title="'+this.mess["close"]+'"></a></td></tr>'+
			'</table>'+
			'</div>'+
			'<div class="bx-calendar-content"></div>'+
			'<div class="bx-calendar-time" align="center" style="display:'+(this.bTime? 'block':'none')+'">'+
			'<form name="float_calendar_time" style="margin:0; padding:0;">'+
			'<table cellspacing="0">'+
			'	<tr>'+
			'		<td>'+this.mess["hour"]+'</td>'+
			'		<td><input type="text" name="hours" value="'+this.Number(this.dateInitial.getHours())+'" size="2" title="'+this.mess['hour_title']+'" onchange="jsCalendar.TimeChange(this);" onblur="jsCalendar.TimeChange(this);"></td>'+
			'		<td>'+this.hoursSpin.Show('jsCalendar.hoursSpin')+'</td>'+
			'		<td>&nbsp;'+this.mess["minute"]+'</td>'+
			'		<td><input type="text" name="minutes" value="'+this.Number(this.dateInitial.getMinutes())+'" size="2" title="'+this.mess['minute_title']+'" onchange="jsCalendar.TimeChange(this);" onblur="jsCalendar.TimeChange(this);"></td>'+
			'		<td>'+this.hoursSpin.Show('jsCalendar.minutesSpin')+'</td>'+
			'		<td>&nbsp;'+this.mess["second"]+'</td>'+
			'		<td><input type="text" name="seconds" value="'+this.Number(this.dateInitial.getSeconds())+'" size="2" title="'+this.mess['second_title']+'" onchange="jsCalendar.TimeChange(this);" onblur="jsCalendar.TimeChange(this);"></td>'+
			'		<td>'+this.hoursSpin.Show('jsCalendar.secondsSpin')+'</td>'+
			'		<td>&nbsp;</td>'+
			'		<td><a title="'+this.mess["set_time"]+'" href="javascript:jsCalendar.CurrentTime('+difference+');" class="bx-calendar-time bx-calendar-set-time"></a></td>'+
			'		<td><a title="'+this.mess["clear_time"]+'" href="javascript:jsCalendar.ClearTime();" class="bx-calendar-time bx-calendar-clear-time"></a></td>'+
			'	</tr>'+
			'</table>'+
			'</form>'+
			'</div>';

		if (!bHideTimebar)
		{
			div.innerHTML += '<table cellspacing="0" class="bx-calendar-timebar">'+
			'	<tr>'+
			'		<td><a id="calendar_time_button" hidefocus="true" tabindex="-1" title="'+(this.bTime? this.mess["time_hide"]:this.mess["time"])+'" href="javascript:jsCalendar.ToggleTime();" class="bx-calendar-button '+(this.bTime? 'bx-calendar-arrow-up' : 'bx-calendar-arrow-down')+'"></a></td>'+
			'	</tr>'+
			'</table>';
		}

		this.floatDiv = div;
		this.content = jsUtils.FindChildObject(this.floatDiv, 'div', 'bx-calendar-content');
		this.content.innerHTML = this.GetMonthPage();

		var pos = jsUtils.GetRealPos(obj);
		pos["bottom"]+=2;
		pos = jsUtils.AlignToPos(pos, div.offsetWidth, div.offsetHeight);

		jsFloatDiv.Show(div, pos["left"], pos["top"]);

		setTimeout(function(){jsUtils.addEvent(document, "click", _this.CheckClick)}, 10);
		jsUtils.addEvent(document, "keypress", _this.OnKeyPress);

		this.bFirst = false;
	}

	this.GetMonthPage = function()
	{
		var aMonths = [this.mess["jan"], this.mess["feb"], this.mess["mar"], this.mess["apr"], this.mess["may"], this.mess["jun"], this.mess["jul"], this.mess["aug"], this.mess["sep"], this.mess["okt"], this.mess["nov"], this.mess["des"]];
		var initYear = this.dateInitial.getFullYear(), initMonth = this.dateInitial.getMonth(), initDay = this.dateInitial.getDate();
		var today = new Date();
		today.setHours(this.dateInitial.getHours(), this.dateInitial.getMinutes(), this.dateInitial.getSeconds());
		var bCurMonth = (today.getFullYear() == initYear && today.getMonth() == initMonth);

		document.getElementById('calendar_float_title').innerHTML = aMonths[initMonth]+', '+initYear;

		var s = '';
		s +=
			'<div style="width:100%; height:100%;">'+
			'<table cellspacing="0" class="bx-calendar-toolbar">'+
			'<tr>'+
				'<td><a title="'+this.mess["prev_mon"]+'" href="javascript:jsCalendar.NavigateMonth('+(initMonth-1)+');" class="bx-calendar-button bx-calendar-left"></a></td>'+
				'<td width="50%"></td>'+
				'<td><a title="'+(bCurMonth? this.mess["curr_day"]:this.mess["curr"])+'" href="javascript:'+(bCurMonth? 'jsCalendar.InsertDate(\''+today.valueOf()+'\')':'jsCalendar.NavigateToday()')+';" class="bx-calendar-button bx-calendar-today"></a></td>'+
				'<td><a title="'+this.mess["per_mon"]+'" href="javascript:jsCalendar.InsertPeriod(\''+this.getMonthFirst().valueOf()+'\', \''+this.getMonthLast().valueOf()+'\');" class="bx-calendar-button bx-calendar-menu">'+aMonths[initMonth]+'</a></td>'+
				'<td><a title="'+this.mess["month"]+'" href="javascript:void(0)" onclick="jsCalendar.MenuMonth(this);" class="bx-calendar-button bx-calendar-arrow"></a></td>'+
				'<td><a title="'+this.mess["per_year"]+'" href="javascript:jsCalendar.InsertPeriod(\''+this.getYearFirst().valueOf()+'\', \''+this.getYearLast().valueOf()+'\');" class="bx-calendar-button bx-calendar-menu">'+initYear+'</a></td>'+
				'<td><a title="'+this.mess["year"]+'" href="javascript:void(0)" onclick="jsCalendar.MenuYear(this);" class="bx-calendar-button bx-calendar-arrow"></a></td>'+
				'<td width="50%"></td>'+
				'<td><a title="'+this.mess["next_mon"]+'" href="javascript:jsCalendar.NavigateMonth('+(initMonth+1)+');" class="bx-calendar-button bx-calendar-right"></a></td>'+
			'</tr>'+
			'</table>';
		s +=
			'<div class="bx-calendar">'+
			'<div style="width:100%;">'+
			'<table cellspacing="0">'+
			'<tr class="bx-calendar-head">'+
			'<td class="bx-calendar-week">&nbsp;</td>'+
			'<td>'+this.mess["mo"]+'</td>'+
			'<td>'+this.mess["tu"]+'</td>'+
			'<td>'+this.mess["we"]+'</td>'+
			'<td>'+this.mess["th"]+'</td>'+
			'<td>'+this.mess["fr"]+'</td>'+
			'<td>'+this.mess["sa"]+'</td>'+
			'<td>'+this.mess["su"]+'</td>'+
			'</tr>';

		var firstDate = new Date(initYear, initMonth, 1, this.dateInitial.getHours(), this.dateInitial.getMinutes(), this.dateInitial.getSeconds());
		var firstDay = firstDate.getDay()-1;
		if(firstDay == -1)
			firstDay = 6;

		var date = new Date();
		var bBreak = false;
		for(var i=0; i<6; i++)
		{
			var row = i*7;
			date.setTime(firstDate.valueOf());
			date.setDate(1-firstDay+row);
			if(i > 0 && date.getDate() == 1)
				break;

			var nWeek = this.WeekNumber(date);

			s += '<tr><td class="bx-calendar-week"><a title="'+this.mess["per_week"]+'" href="javascript:jsCalendar.InsertPeriod(\''+date.valueOf()+'\', \'';

			date.setTime(firstDate.valueOf());
			date.setDate(1-firstDay+row+6);
			s += date.valueOf()+'\');">'+nWeek+'</a></td>';

			for(var j=0; j<7; j++)
			{
				date.setTime(firstDate.valueOf());
				date.setDate(1-firstDay+row+j);
				var d = date.getDate();

				if(i > 0 && d == 1)
					bBreak = true;

				var sClass = '';
				if(row+j+1 > firstDay && !bBreak)
				{
					if(d == today.getDate() && bCurMonth)
						sClass += ' bx-calendar-today';
					if(this.dateCurrent && d == this.dateCurrent.getDate() && initMonth == this.dateCurrent.getMonth() && initYear == this.dateCurrent.getFullYear())
						sClass += ' bx-calendar-current';
				}
				if(j==5 || j==6)
					sClass += ' bx-calendar-holiday';
				if(!(row+j+1 > firstDay && !bBreak))
					sClass += ' bx-calendar-inactive';

				s += '<td'+(sClass != ''? ' class="'+sClass+'"':'')+'>';
				s += '<a title="'+this.mess["date"]+'" href="javascript:jsCalendar.InsertDate(\''+date.valueOf()+'\')">'+d+'</a>';
				s += '</td>';
			}
			s += '</tr>';
			if(bBreak)
				break;
		}
		s +=
			'</table>'+
			'</div>'+
			'</div>'+
			'</div>';
		return s;
	}

	/* Dates arithmetics */
	this.WeekNumber = function(date)
	{
		date.setHours(0, 0, 0, 0);

		var firstYearDate = new Date(date.getFullYear(), 0, 1);
		var firstYearDay = firstYearDate.getDay()-1;
		if(firstYearDay == -1)
			firstYearDay = 6;

		var nDays = Math.round((date.valueOf() - firstYearDate.valueOf())/(24*60*60*1000));

		var nWeek = (nDays-(7-firstYearDay))/7 + 1;

		if(firstYearDay < 4)
			nWeek++;
		if(nWeek > 52)
		{
			firstYearDate = new Date(date.getFullYear()+1, 0, 1);
			firstYearDay = firstYearDate.getDay()-1;
			if(firstYearDay == -1)
				firstYearDay = 6;
			if(firstYearDay < 4)
				nWeek = 1;
		}

		return nWeek;
	}

	this.NavigateToday = function()
	{
		var h = this.dateInitial.getHours(), m = this.dateInitial.getMinutes(), s = this.dateInitial.getSeconds();
		this.dateInitial.setTime((new Date()).valueOf());
		this.dateInitial.setHours(h, m, s);
		this.content.innerHTML = jsCalendar.GetMonthPage();
	}

	this.NavigateMonth = function(mon)
	{
		this.dateInitial.setMonth(mon);
		this.content.innerHTML = jsCalendar.GetMonthPage();
	}

	this.NavigateYear = function(year)
	{
		this.dateInitial.setFullYear(year);
		this.content.innerHTML = jsCalendar.GetMonthPage();
	}

	this.getMonthFirst = function()
	{
		var d = new Date();
		d.setTime(this.dateInitial.valueOf());
		d.setDate(1);
		return d;
	}

	this.getMonthLast = function()
	{
		var d = new Date();
		d.setTime(this.dateInitial.valueOf());
		d.setMonth(d.getMonth()+1);
		d.setDate(0);
		return d;
	}

	this.getYearFirst = function()
	{
		var d = new Date();
		d.setTime(this.dateInitial.valueOf());
		d.setMonth(0);
		d.setDate(1);
		return d;
	}

	this.getYearLast = function()
	{
		var d = new Date();
		d.setTime(this.dateInitial.valueOf());
		d.setFullYear(d.getFullYear()+1);
		d.setMonth(0);
		d.setDate(0);
		return d;
	}

	/* Input / Output */
	this.InsertDaysBack = function(input, days)
	{
		if(days != '')
		{
			var d = new Date();
			if(days > 0)
				d.setTime(d.valueOf() - days*24*60*60*1000);
			input.value = this.FormatDate(d, phpVars.FORMAT_DATE);
			input.disabled = true;
		}
		else
		{
			input.disabled = false;
			input.value = '';
		}
	}

	this.ValueToString = function(value)
	{
		var date = new Date();
		date.setTime(value);
		if(this.bTime)
		{
			var form = document.float_calendar_time;
			date.setHours(parseInt(form.hours.value, 10));
			date.setMinutes(parseInt(form.minutes.value, 10));
			date.setSeconds(parseInt(form.seconds.value, 10));
		}
		return this.FormatDate(date);
	}

	this.CurrentTime = function(difference)
	{
		var time = new Date();
		time.setTime(time.valueOf() + difference);

		var form = document.float_calendar_time;
		form.hours.value = time.getHours();
		form.minutes.value = time.getMinutes();
		form.seconds.value = time.getSeconds();

		form.hours.onchange();
		form.minutes.onchange();
		form.seconds.onchange();
	}

	this.ClearTime = function()
	{
		var form = document.float_calendar_time;
		form.hours.value = form.minutes.value = form.seconds.value = '00';
	}

	this.InsertDate = function(value)
	{
		this.field.value = this.ValueToString(value);
		if (this.field.onchange && typeof this.field.onchange == 'function')
			this.field.onchange();
		this.Close();
	}

	this.InsertPeriod = function(value1, value2)
	{
		if(null != this.fieldFrom && null != this.fieldTo)
		{
			this.fieldFrom.value = this.ValueToString(value1);
			this.fieldTo.value = this.ValueToString(value2);
		}
		else
			this.field.value = this.ValueToString(value1);
		this.Close();
	}

	this.Number = function(val)
	{
		return (val < 10? '0'+val : val);
	}

	this.FormatDate = function(date, format)
	{
		var val;
		var str = (format? format : (this.bTime? phpVars.FORMAT_DATETIME : phpVars.FORMAT_DATE));
		str = str.replace(/YYYY/ig, date.getFullYear());
		str = str.replace(/MM/ig, this.Number(date.getMonth()+1));
		str = str.replace(/DD/ig, this.Number(date.getDate()));
		str = str.replace(/HH/ig, this.Number(date.getHours()));
		str = str.replace(/MI/ig, this.Number(date.getMinutes()));
		str = str.replace(/SS/ig, this.Number(date.getSeconds()));
		return str;
	}

	this.ParseDate = function(str)
	{
		var aDate = str.split(/\D/ig);
		var aFormat = phpVars.FORMAT_DATE.split(/\W/ig);
		if(aDate.length > aFormat.length)
			aFormat = phpVars.FORMAT_DATETIME.split(/\W/ig);

		var i, cnt;
		var aDateArgs=[], aFormatArgs=[];
		for(i = 0, cnt = aDate.length; i < cnt; i++)
			if(jsUtils.trim(aDate[i]) != '')
				aDateArgs[aDateArgs.length] = aDate[i];
		for(i = 0, cnt = aFormat.length; i < cnt; i++)
			if(jsUtils.trim(aFormat[i]) != '')
				aFormatArgs[aFormatArgs.length] = aFormat[i];

		var aResult={};
		for(i = 0, cnt = aFormatArgs.length; i < cnt; i++)
			aResult[aFormatArgs[i].toUpperCase()] = parseInt(aDateArgs[i], 10);

		if(aResult['DD'] > 0 && aResult['MM'] > 0 && aResult['YYYY'] > 0)
		{
			var d = new Date();
			d.setDate(1);
			d.setFullYear(aResult['YYYY']);
			d.setMonth(aResult['MM']-1);
			d.setDate(aResult['DD']);
			d.setHours(0, 0, 0);
			if(!isNaN(aResult['HH']) && !isNaN(aResult['MI']) && !isNaN(aResult['SS']))
			{
				this.bTime = true;
				d.setHours(aResult['HH'], aResult['MI'], aResult['SS']);
			}
			return d;
		}
		return null;
	}

	/* Navigation interface */
	this.MenuMonth = function(a)
	{
		var aMonths = [this.mess["jan"], this.mess["feb"], this.mess["mar"], this.mess["apr"], this.mess["may"], this.mess["jun"], this.mess["jul"], this.mess["aug"], this.mess["sep"], this.mess["okt"], this.mess["nov"], this.mess["des"]];
		var items = [];
		var mon = this.dateInitial.getMonth();
		for(var i in aMonths)
			items[i] = {'ICONCLASS': (mon == i? 'checked':''), 'TEXT': aMonths[i], 'ONCLICK': 'jsCalendar.NavigateMonth('+i+')', 'DEFAULT': ((new Date()).getMonth() == i? true:false)};
		this.ShowMenu(a, items);
	}

	this.MenuYear = function(a)
	{
		var items = [];
		var y = this.dateInitial.getFullYear();
		for(var i=0; i<11; i++)
		{
			item_year = y-5+i;
			items[i] = {'ICONCLASS': (y == item_year? 'checked':''), 'TEXT': item_year, 'ONCLICK': 'jsCalendar.menu.PopupHide(); jsCalendar.NavigateYear('+item_year+')', 'DEFAULT': ((new Date()).getFullYear() == item_year? true:false)};
		}
		this.ShowMenu(a, items);
	}

	this.ShowMenu = function(a, items)
	{
		if(!this.menu)
		{
			this.menu = new PopupMenu('calendar_float_menu');
			this.menu.Create(160, 0);
			this.menu.OnClose = function()
			{
				setTimeout(
					function()
					{
						jsUtils.removeEvent(document, "click", _this.CheckClick);
						jsUtils.removeEvent(document, "keypress", _this.OnKeyPress);

						if (_this.floatDiv)
							jsUtils.addEvent(document, "click", _this.CheckClick)
					}, 50);
				jsUtils.addEvent(document, "keypress", _this.OnKeyPress);
			}
		}
		if(this.menu.IsVisible())
			return;

		this.menu.SetItems(items);
		this.menu.BuildItems();

		var pos = jsUtils.GetRealPos(a);
		pos["bottom"]+=1;

		jsUtils.removeEvent(document, "click", _this.CheckClick);
		jsUtils.removeEvent(document, "keypress", _this.OnKeyPress);
		this.menu.PopupShow(pos);
	}

	this.ToggleTime = function()
	{
		var div = jsUtils.FindChildObject(this.floatDiv, 'div', 'bx-calendar-time');
		var a = document.getElementById('calendar_time_button');
		if(div.style.display == 'none')
		{
			div.style.display = 'block';
			a.className = 'bx-calendar-button bx-calendar-arrow-up';
			a.title = this.mess['time_hide'];
		}
		else
		{
			div.style.display = 'none';
			a.className = 'bx-calendar-button bx-calendar-arrow-down';
			a.title = this.mess['time'];
		}
		a.blur();
		jsFloatDiv.AdjustShadow(this.floatDiv);
	}

	this.TimeChange = function(input)
	{
		this.bTime = true;

		var val = parseInt(input.value, 10);
		if(isNaN(val))
			val = '00';
		else if(val < 0)
		{
			if(input.name == 'hours')
				val = '23';
			else
				val = '59';
		}
		else if(input.name == 'hours' && val > 23 || val > 59)
			val = '00';
		else
			val = this.Number(val);

		input.value = val;
	}

	/* Window operations: close, drag, move */
	this.Close =  function()
	{
		jsUtils.removeEvent(document, "click", _this.CheckClick);
		jsUtils.removeEvent(document, "keypress", _this.OnKeyPress);

		jsFloatDiv.Close(this.floatDiv);

		this.floatDiv.parentNode.removeChild(this.floatDiv);
		this.floatDiv = null;
	}

	this.OnKeyPress = function(e)
	{
		if(!e) e = window.event
		if(!e) return;
		if(e.keyCode == 27)
			_this.Close();
	}

	this.CheckClick = function(e)
	{
		var div = _this.floatDiv;
		if(!div)
			return;

		var windowSize = jsUtils.GetWindowSize();
		var x = e.clientX + windowSize.scrollLeft;
		var y = e.clientY + windowSize.scrollTop;

		var arPos = jsUtils.GetRealPos(div);
		/*region*/
		//var posLeft = parseInt(div.style.left);
		//var posTop = parseInt(div.style.top);
		//var posRight = posLeft + div.offsetWidth;
		//var posBottom = posTop + div.offsetHeight;
		if(x >= arPos.left && x <= arPos.right && y >= arPos.top && y <= arPos.bottom)
			return;

		_this.Close();
	}
}

var jsCalendar = new JCCalendar();

if (null == window.JCSpinner)
{
	function JCSpinner(name)
	{
		var _this = this;
		this.name = name;
		this.mousedown = false;

		this.Show = function(name)
		{
			var s =
				'<table cellspacing="0" class="bx-calendar-spin">'+
				'	<tr><td><a hidefocus="true" tabindex="-1" href="javascript:void(0);" onmousedown="'+name+'.Start(1);" class="bx-calendar-spin bx-calendar-spin-up"></a></td></tr>'+
				'	<tr><td><a hidefocus="true" tabindex="-1" href="javascript:void(0);" onmousedown="'+name+'.Start(-1);" class="bx-calendar-spin bx-calendar-spin-down"></a></td></tr>'+
				'</table>';
			return s;
		}

		this.Start = function(delta)
		{
			this.mousedown = true;
			jsUtils.addEvent(document, "mouseup", _this.MouseUp);
			this.ChangeValue(delta, true);
		}

		this.ChangeValue = function(delta, bFirst)
		{
			if(!this.mousedown)
				return;

			var input = document.float_calendar_time.elements[this.name];
			input.value = parseInt(input.value, 10) + delta;
			input.onchange();
			setTimeout(function(){_this.ChangeValue(delta, false)}, (bFirst? 1000:150));
		}

		this.MouseUp = function()
		{
			_this.mousedown = false;
			jsUtils.removeEvent(document, "mouseup", _this.MouseUp);
		}
	}
}