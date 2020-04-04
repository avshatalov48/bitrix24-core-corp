function JCCalendarViewWeek(date)
{
	this.ID = 'week';
	this._parent = null;

	this.SETTINGS = {};
	this.ENTRIES = [];

	if (!window._WEEK_STYLE_LOADED)
	{
		BX.loadCSS('/bitrix/components/bitrix/intranet.absence.calendar.view/templates/week/view.css');
		window._WEEK_STYLE_LOADED = true;
	}

	BX.bind(window, 'resize', BX.proxy(this.__onresize, this));
}

JCCalendarViewWeek.prototype.__onresize = function ()
{
	this.UnloadData(true);
	this.__drawData();
}

JCCalendarViewWeek.prototype.Load = function()
{
	this._parent.FILTER.SHORT_EVENTS = 'Y';

	if (null != this.ENTRIES && this.ENTRIES.length > 0) this.UnloadData();

	this.__drawLayout();

	this.TYPE_BGCOLORS = this._parent.TYPE_BGCOLORS;

	this._parent.LoadData(
		this.SETTINGS.DATE_START,
		this.SETTINGS.DATE_FINISH
	);
}

JCCalendarViewWeek.prototype.LoadData = function(DATA)
{
	this.ENTRIES = DATA;

	if (BX.browser.IsIE())
		setTimeout(BX.proxy(this.__drawData, this), 10);
	else
		this.__drawData();
}

JCCalendarViewWeek.prototype.UnloadData = function(bClearOnlyVisual)
{
	if (null == this.ENTRIES)
		return;

	if (null == bClearOnlyVisual) bClearOnlyVisual = false;

	for (var i = 0; i < this.ENTRIES.length; i++)
	{
		if (null != this.ENTRIES[i].DATA)
		{
			for (var j = 0; j < this.ENTRIES[i].DATA.length; j++)
			{
				if (null == this.ENTRIES[i].DATA[j].VISUAL) continue;

				this._parent.UnRegisterEntry(this.ENTRIES[i].DATA[j]);
				BX.cleanNode(this.ENTRIES[i].DATA[j].VISUAL, true)
				this.ENTRIES[i].DATA[j].VISUAL = null;
			}
		}

		var obRow = document.getElementById('bx_calendar_user_' + this.ENTRIES[i]['ID']);
		if (null != obRow) {obRow.parentNode.removeChild(obRow); delete obRow;}
	}
	if (!bClearOnlyVisual) this.ENTRIES = null;
}

JCCalendarViewWeek.prototype.SetSettings = function (SETTINGS)
{
	this.SETTINGS = SETTINGS;

	var today = new Date();

	if (null == this.SETTINGS.DATE_START || today >= this.SETTINGS.DATE_START && today <= this.SETTINGS.DATE_FINISH)
		this.SETTINGS.DATE_START = today

	var adder = this.SETTINGS.DATE_START.getDay() >= this.SETTINGS.FIRST_DAY ? 0 : -7;

	this.SETTINGS.DATE_START.setDate(
		this.SETTINGS.DATE_START.getDate() - this.SETTINGS.DATE_START.getDay() + this.SETTINGS.FIRST_DAY + adder
	);

	this.SETTINGS.DATE_START.setHours(0);
	this.SETTINGS.DATE_START.setMinutes(0);
	this.SETTINGS.DATE_START.setSeconds(0);
	this.SETTINGS.DATE_START.setMilliseconds(0);

	this.SETTINGS.DATE_FINISH = new Date(this.SETTINGS.DATE_START.valueOf());
	this.SETTINGS.DATE_FINISH.setDate(this.SETTINGS.DATE_FINISH.getDate()+7);
}

JCCalendarViewWeek.prototype.Unload = function()
{
	BX.unbind(window, 'resize', BX.proxy(this.__onresize, this));
	this.UnloadData();
}

JCCalendarViewWeek.prototype.changeWeek = function(dir)
{
	if (dir != -1) dir = 1;

	this.SETTINGS.DATE_START.setDate(this.SETTINGS.DATE_START.getDate() + dir * 7);

	this.SETTINGS.DATE_FINISH = new Date(this.SETTINGS.DATE_START);
	this.SETTINGS.DATE_FINISH.setDate(this.SETTINGS.DATE_FINISH.getDate() + 7)

	this.Load();
}

JCCalendarViewWeek.prototype.__drawLayout = function()
{
	var _this = this;

	var today = new Date();
	today.setHours(0);
	today.setMinutes(0);
	today.setSeconds(0);
	today.setMilliseconds(0);

	this._parent.CONTROLS.CALENDAR.innerHTML = '';

	this.obTable = document.createElement('TABLE');
	this.obTable.className = 'bx-calendar-week-main-table';
	this.obTable.setAttribute('cellSpacing', '0');

	this._parent.CONTROLS.CALENDAR.appendChild(this.obTable);

	//this.obTable.appendChild(document.createElement('THEAD'));
	this.obTable.appendChild(document.createElement('TBODY'));

	// generate controls
	var obRow = this.obTable.tBodies[0].insertRow(-1);

	obRow.insertCell(-1);
	obRow.cells[0].className = 'bx-calendar-week-empty';
	obRow.cells[0].innerHTML = '&nbsp;';

	var date_finish = new Date(this.SETTINGS.DATE_FINISH.valueOf());
	date_finish.setDate(date_finish.getDate()-1);
	var text = this.SETTINGS.DATE_START.getDate();
	if (this.SETTINGS.DATE_START.getMonth() != date_finish.getMonth())
	{
		text += ' ' + this._parent.MONTHS_R[this.SETTINGS.DATE_START.getMonth()];
	}

	if (this.SETTINGS.DATE_START.getFullYear() != date_finish.getFullYear())
	{
		text += ' ' + this.SETTINGS.DATE_START.getFullYear();
	}

	text += ' - ' + date_finish.getDate() + ' ' + this._parent.MONTHS_R[date_finish.getMonth()] + ' ' + date_finish.getFullYear();

	//obRow.cells[0].innerHTML +=
	this._parent.CONTROLS.DATEROW.innerHTML =
	'<table class="bx-calendar-week-control-table" align="center"><tr>'
		+ '<td><a href="javascript:void(0)" class="bx-calendar-week-icon bx-calendar-week-back"></a></td>'
		+ '<td>' + text + '</td>'
		+ '<td><a href="javascript:void(0)" class="bx-calendar-week-icon bx-calendar-week-fwd"></a></td>'
		+ '</tr></table>';

	//var arLinks = obRow.cells[0].getElementsByTagName('A');
	var arLinks = this._parent.CONTROLS.DATEROW.getElementsByTagName('A');
	arLinks[0].onclick = function() {_this.changeWeek(-1)}
	arLinks[1].onclick = function() {_this.changeWeek(1)}

	var cur_date = new Date(this.SETTINGS.DATE_START.valueOf());
	var bDayViewRegistered = this._parent.isViewRegistered('day');
	for (var i = 0; i < 7; i++)
	{
		var obCell = obRow.insertCell(-1);

		obCell.className = 'bx-calendar-week-day';
		if (cur_date.valueOf() == today.valueOf())
			obCell.className += ' bx-calendar-week-today';
		if (cur_date.getDay() == 0 || cur_date.getDay() == 6)
			obCell.className += ' bx-calendar-week-holiday';

		var day = cur_date.getDate();
		if (day < 10) day = '0' + day;
		if (bDayViewRegistered)
		{
			var obLink = obCell.appendChild(document.createElement('A'));
			obLink.href = "javascript:void(0)";
			obLink.BX_DAY = new Date(cur_date.valueOf());
			obLink.onclick = function()
			{
				_this.SETTINGS.DATE_START = this.BX_DAY;
				_this.SETTINGS.DATE_FINISH = this.BX_DAY;
				_this._parent.SetView('day');
			}
			obLink.innerHTML = this._parent.DAYS[cur_date.getDay()] + ', ' + day;
		}
		else
		{
			obCell.innerHTML = this._parent.DAYS[cur_date.getDay()] + ', ' + day;
		}

		cur_date.setDate(cur_date.getDate() + 1);
	}

	this._parent.CONTROLS.CALENDAR.appendChild(document.createElement('BR'));
	this._parent.CONTROLS.CALENDAR.appendChild(document.createElement('BR'));

	this.obPageBar = document.createElement('DIV');
	this.obPageBar.style.padding = '10px 10px 3px';
	this.obPageBar.style.fontSize = '0.95em';
	this._parent.CONTROLS.CALENDAR.appendChild(this.obPageBar);

	this._parent.CONTROLS.CALENDAR.appendChild(document.createElement('BR'));
	this._parent.CONTROLS.CALENDAR.appendChild(document.createElement('BR'));
}

JCCalendarViewWeek.prototype.__drawData = function()
{
	var _this = this;

	var today = new Date();
	today.setHours(0);
	today.setMinutes(0);
	today.setSeconds(0);
	today.setMilliseconds(0);

	var date_start = this.SETTINGS.DATE_START;
		date_finish = new Date(date_start);

	date_finish.setDate(date_finish.getDate() + 7);
	date_finish.setSeconds(date_finish.getSeconds() - 1);

	for (var i = 0; i < (null == this.ENTRIES ? 0 : this.ENTRIES.length); i++)
	{
		// check user of actual range of absence
		for (var j = 0; j < this.ENTRIES[i]['DATA'].length; j++)
		{
			var ts_start = this.ENTRIES[i]['DATA'][j]['DATE_ACTIVE_FROM'];
			var ts_finish = this.ENTRIES[i]['DATA'][j]['DATE_ACTIVE_TO'];

			if (date_start.valueOf() > ts_finish.valueOf() || date_finish.valueOf() < ts_start.valueOf())
			{
				this.ENTRIES[i]['DATA'].splice(j, 1);
				j--;
			}
		}

		if (this.ENTRIES[i]['DATA'].length == 0 && !this._parent.FILTER.USERS_ALL)
		{
			continue;
		}

		var obRow = this.obTable.tBodies[0].insertRow(-1);

		//obRow.onmouseover = jsBXAC._hightlightRow;
		//obRow.onmouseout = jsBXAC._unhightlightRow;

		obRow.insertCell(-1);
		obRow.cells[0].className = 'bx-calendar-week-first-col';

		obRow.id = 'bx_calendar_user_' + this.ENTRIES[i]['ID'];

		var obNameContainer = obRow.cells[0].appendChild(document.createElement('DIV'));

		var strName = this._parent.FormatName(this.SETTINGS.NAME_TEMPLATE, this.ENTRIES[i]);

		obNameContainer.title = strName;

		if (this.ENTRIES[i]['DETAIL_URL'])
		{
			var obName = document.createElement('A');
			obName.appendChild(document.createTextNode(strName));
			obName.href = this.ENTRIES[i]['DETAIL_URL'];
		}
		else
		{
			var obName = document.createTextNode(strName);
		}

		obNameContainer.appendChild(obName);

		var cur_date = new Date(this.SETTINGS.DATE_START.valueOf());
		for (var j = 0; j < 7; j++)
		{
			var obCell = obRow.insertCell(-1);

			obCell.className = 'bx-calendar-week-day';

			if (cur_date.valueOf() == today.valueOf())
				obCell.className += ' bx-calendar-week-today';
			if (cur_date.getDay() == 0 || cur_date.getDay() == 6)
				obCell.className += ' bx-calendar-week-holiday';

			if (BX.browser.IsIE())
				obCell.innerHTML = '&nbsp;';

			cur_date.setDate(cur_date.getDate() + 1);
		}
	}

	var padding = 2;
	for (var i = 0; i < (null == this.ENTRIES ? 0 : this.ENTRIES.length); i++)
	{
		var obUserRow = BX('bx_calendar_user_' + this.ENTRIES[i]['ID']);

		if (obUserRow && this.ENTRIES[i]['DATA'])
		{
			var obRowPos = BX.pos(obUserRow, true);

			for (var j = 0; j < this.ENTRIES[i]['DATA'].length; j++)
			{
				var ts_start = this.ENTRIES[i]['DATA'][j]['DATE_ACTIVE_FROM'],
					ts_finish = this.ENTRIES[i]['DATA'][j]['DATE_ACTIVE_TO'];

				this.ENTRIES[i]['DATA'][j].VISUAL = document.createElement('DIV');

				this.ENTRIES[i]['DATA'][j].VISUAL.bx_color_variant = this.ENTRIES[i]['DATA'][j]['TYPE'].length ? this.ENTRIES[i]['DATA'][j]['TYPE'] : 'OTHER';

				this.ENTRIES[i]['DATA'][j].VISUAL.className = 'bx-calendar-entry bx-calendar-color-' + this.ENTRIES[i]['DATA'][j].VISUAL.bx_color_variant;
				this.ENTRIES[i]['DATA'][j].VISUAL.style.background = this.TYPE_BGCOLORS[(this.ENTRIES[i]['DATA'][j]['TYPE'].length ? this.ENTRIES[i]['DATA'][j]['TYPE'] : 'OTHER')];

				this.ENTRIES[i]['DATA'][j].VISUAL.style.top = (obRowPos.top) + 'px';

				if (date_start.valueOf() > ts_start.valueOf())
					ts_start = date_start;
				if (date_finish.valueOf() < ts_finish.valueOf())
					ts_finish = date_finish;

				var startIndex = ts_start.getDay() - date_start.getDay() + 1,
					finishIndex = ts_finish.getDay() - date_finish.getDay();

				var obStartCell = obUserRow.cells[startIndex > 0 ? startIndex : startIndex + 7],
					obFinishCell = obUserRow.cells[finishIndex > 0 ? finishIndex : finishIndex + 7];

				var obPos = BX.pos(obStartCell, true),
					start_pos = obPos.left;// + padding;

				var start_hours = ts_start.getHours();
				if (start_hours > this.SETTINGS.DAY_START)
				{
					var start_width = obPos.right - obPos.left;

					if (start_hours < this.SETTINGS.DAY_FINISH)
					{
						start_pos += Math.round((start_hours-this.SETTINGS.DAY_START)*start_width/(this.SETTINGS.DAY_FINISH-this.SETTINGS.DAY_START));
					}
					else
					{
						start_pos += start_width - 5;
					}
				}

				if (obStartCell != obFinishCell)
					obPos = BX.pos(obFinishCell, true);

				var finish_pos = obPos.right;
				var finish_hours = ts_finish.getHours();

				if (finish_hours < this.SETTINGS.DAY_FINISH)
				{
					var finish_width = obPos.right - obPos.left;

					if (finish_hours > this.SETTINGS.DAY_START)
					{
						finish_pos -= Math.round((this.SETTINGS.DAY_FINISH-finish_hours)*finish_width/(this.SETTINGS.DAY_FINISH-this.SETTINGS.DAY_START));
					}
					else
					{
						finish_pos -= finish_width - 5;
					}
				}

				var width = parseInt(finish_pos - start_pos - padding * 2);
				if (isNaN(width) || width < 5)
					width = 5;

				this.ENTRIES[i]['DATA'][j].VISUAL.style.left = parseInt(start_pos) + 'px';
				this.ENTRIES[i]['DATA'][j].VISUAL.style.width = width + 'px';
				this.ENTRIES[i]['DATA'][j].VISUAL.style.height = parseInt(obPos.height - 2*padding) + 'px';

				this.ENTRIES[i]['DATA'][j].VISUAL.innerHTML =
					'<nobr>'
					+ BX.util.htmlspecialchars(this.ENTRIES[i]['DATA'][j]['NAME'])
					+ ' (' + this.ENTRIES[i]['DATA'][j]['DATE_FROM'] + ' - ' + this.ENTRIES[i]['DATA'][j]['DATE_TO'] + ')'
					+ '</nobr>';

				//this.ENTRIES[i]['DATA'][j].VISUAL.onmouseover = this._hightlightRowDiv;
				//this.ENTRIES[i]['DATA'][j].VISUAL.onmouseout = this._unhightlightRowDiv;

				this.ENTRIES[i]['DATA'][j].VISUAL.__bx_user_id = this.ENTRIES[i]['ID'];

				this._parent.MAIN_LAYOUT.appendChild(this.ENTRIES[i]['DATA'][j].VISUAL);
				this._parent.RegisterEntry(this.ENTRIES[i].DATA[j]);
			}
		}
	}

	if (this.SETTINGS.PAGE_COUNT > 1 || this.SETTINGS.PAGE_NUMBER > 0)
	{
		this.obPageBar.innerHTML = this._parent.MESSAGES.INTR_ABSC_TPL_PAGE_BAR + ': ';

		for (var i = 0; i <= this.SETTINGS.PAGE_NUMBER; i++)
		{
			if (i == this.SETTINGS.PAGE_NUMBER)
			{
				var page_link = document.createTextNode(i+1);
			}
			else
			{
				var page_link = document.createElement('A');
				page_link.href = 'javascript:void(0);';
				page_link.innerHTML = i+1;
				page_link.onclick = (function(i)
				{
					return function()
					{
						_this.SETTINGS.PAGE_NUMBER = i;
						_this.Load();
					};
				})(i);
			}

			this.obPageBar.appendChild(page_link);
			this.obPageBar.appendChild(document.createTextNode(' '));

			if (i == 0 && this.SETTINGS.PAGE_NUMBER >= 6)
			{
				var page_link = document.createTextNode('...');
				this.obPageBar.appendChild(page_link);
				this.obPageBar.appendChild(document.createTextNode(' '));

				i = this.SETTINGS.PAGE_NUMBER-4;
			}
		}

		if (this.SETTINGS.PAGE_COUNT-1 > this.SETTINGS.PAGE_NUMBER)
		{
			var page_link = document.createElement('A');
			page_link.href = 'javascript:void(0);';
			page_link.innerHTML = this._parent.MESSAGES.INTR_ABSC_TPL_PAGE_NEXT;
			page_link.onclick = function()
			{
				_this.SETTINGS.PAGE_NUMBER += 1;
				_this.Load();
			};

			this.obPageBar.appendChild(page_link);
		}
	}
}