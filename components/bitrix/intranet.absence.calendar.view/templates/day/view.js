function JCCalendarViewDay(date)
{
	this.ID = 'day';
	this._parent = null;

	this.SETTINGS = {};
	this.ENTRIES = [];

	if (!window._DAY_STYLE_LOADED)
	{
		BX.loadCSS('/bitrix/components/bitrix/intranet.absence.calendar.view/templates/day/view.css');
		window._DAY_STYLE_LOADED = true;
	}

	BX.bind(window, 'resize', BX.proxy(this.__onresize, this));
}

JCCalendarViewDay.prototype.__onresize = function ()
{
	this.UnloadData(true);
	this.__drawData();
}


JCCalendarViewDay.prototype.Load = function()
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

JCCalendarViewDay.prototype.LoadData = function(DATA)
{
	this.ENTRIES = DATA;

	if (BX.browser.IsIE())
	{
		setTimeout(BX.proxy(this.__drawData, this), 10);
	}
	else
	{
		this.__drawData();
	}
}

JCCalendarViewDay.prototype.UnloadData = function(bClearOnlyVisual)
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
				BX.cleanNode(this.ENTRIES[i].DATA[j].VISUAL, true);
				this.ENTRIES[i].DATA[j].VISUAL = null;
			}
		}

		var obRow = document.getElementById('bx_calendar_user_' + this.ENTRIES[i]['ID']);
		if (null != obRow) obRow.parentNode.removeChild(obRow);
	}

	if (!bClearOnlyVisual) this.ENTRIES = null;
}

JCCalendarViewDay.prototype.SetSettings = function (SETTINGS)
{
	this.SETTINGS = SETTINGS;

	var today = new Date();

	if (null == this.SETTINGS.DATE_START || today >= this.SETTINGS.DATE_START && today <= this.SETTINGS.DATE_FINISH)
		this.SETTINGS.DATE_START = today

	this.SETTINGS.DATE_START.setHours(this.SETTINGS.DAY_SHOW_NONWORK ? 0 : this.SETTINGS.DAY_START);
	this.SETTINGS.DATE_START.setMinutes(0);
	this.SETTINGS.DATE_START.setSeconds(0);
	this.SETTINGS.DATE_START.setMilliseconds(0);

	this.SETTINGS.DATE_FINISH = new Date(this.SETTINGS.DATE_START.valueOf());
	this.SETTINGS.DATE_FINISH.setHours(this.SETTINGS.DAY_SHOW_NONWORK ? 24 : this.SETTINGS.DAY_FINISH);
}

JCCalendarViewDay.prototype.Unload = function()
{
	BX.unbind(window, 'resize', BX.proxy(this.__onresize, this));
	this.UnloadData();
}

JCCalendarViewDay.prototype.changeDay = function(dir)
{
	if (dir != -1) dir = 1;

	this.SETTINGS.DATE_START.setDate(this.SETTINGS.DATE_START.getDate() + dir);
	this.SETTINGS.DATE_FINISH.setDate(this.SETTINGS.DATE_FINISH.getDate() + dir);

	this.Load();
}

JCCalendarViewDay.prototype.__drawLayout = function()
{
	var _this = this;

	var time_start = this.SETTINGS.DAY_SHOW_NONWORK ? 0 : this.SETTINGS.DAY_START;
	var time_finish = this.SETTINGS.DAY_SHOW_NONWORK ? 24 : this.SETTINGS.DAY_FINISH;

	this._parent.CONTROLS.CALENDAR.innerHTML = '';

	this.obTable = document.createElement('TABLE');
	this.obTable.className = 'bx-calendar-day-main-table';
	this.obTable.setAttribute('cellSpacing', '0');

	this._parent.CONTROLS.CALENDAR.appendChild(this.obTable);

	//this.obTable.appendChild(document.createElement('THEAD'));
	this.obTable.appendChild(document.createElement('TBODY'));

//	// generate controls
//	var obRow = this.obTable.tBodies[0].insertRow(-1);
//
//	obRow.insertCell(-1);
//	obRow.cells[0].className = 'bx-calendar-day-empty';
//	obRow.cells[0].innerHTML = '&nbsp;';

	var date_finish = new Date(this.SETTINGS.DATE_FINISH.valueOf());
	date_finish.setSeconds(date_finish.getSeconds()-1);

	var text = this._parent.DAYS_FULL[this.SETTINGS.DATE_START.getDay()] + ', ' + this.SETTINGS.DATE_START.getDate() + ' ' + this._parent.MONTHS_R[this.SETTINGS.DATE_START.getMonth()] + ' ' + this.SETTINGS.DATE_START.getFullYear();


	//obRow.cells[0].innerHTML +=
	this._parent.CONTROLS.DATEROW.innerHTML =
	this._parent.CONTROLS.DATEROW.innerHTML =
	'<table class="bx-calendar-day-control-table" align="center"><tr>'
		+ '<td><a href="javascript:void(0)" class="bx-calendar-day-icon bx-calendar-day-back"></a></td>'
		+ '<td>' + text + '</td>'
		+ '<td><a href="javascript:void(0)" class="bx-calendar-day-icon bx-calendar-day-fwd"></a></td>'
		+ '</tr></table>';

	//var arLinks = obRow.cells[0].getElementsByTagName('A');
	var arLinks = this._parent.CONTROLS.DATEROW.getElementsByTagName('A');
	arLinks[0].onclick = function() {_this.changeDay(-1)}
	arLinks[1].onclick = function() {_this.changeDay(1)}

//	var obCell = obRow.insertCell(-1);
//	obCell.setAttribute('colSpan', time_finish-time_start);

//	if (BX.browser.IsIE())
//		obCell.innerHTML = '&nbsp;';


	this.obDelimRow = this.obTable.tBodies[0].insertRow(-1);

	this.obDelimRow.insertCell(-1);
	this.obDelimRow.cells[0].className = 'bx-calendar-day-empty';

	if (BX.browser.IsIE())
		this.obDelimRow.cells[0].innerHTML = '&nbsp;';

	var cur_date = new Date(this.SETTINGS.DATE_START.valueOf());
	var today = new Date();
	today.setMinutes(0);
	today.setSeconds(0);
	today.setMilliseconds(0);
	for (var i = time_start; i < time_finish; i++)
	{
		var obCell = this.obDelimRow.insertCell(-1);

		obCell.className = 'bx-calendar-day-hour' + (this.SETTINGS.DAY_SHOW_NONWORK ? '' : '-long');

		if (cur_date.valueOf() == today.valueOf())
			obCell.className += ' bx-calendar-day-now';
		if (i < this.SETTINGS.DAY_START || i >= this.SETTINGS.DAY_FINISH)
			obCell.className += ' bx-calendar-day-hour-nonwork';

		var time_label = BX.isAmPmMode() ? (cur_date.getHours()%12 > 0 ? cur_date.getHours()%12 : 12) : cur_date.getHours();
			time_label += BX.isAmPmMode() ? (cur_date.getHours() < 12 ? 'am' : 'pm') : '<sup>00</sup>';

		obCell.innerHTML = time_label;

		cur_date.setHours(cur_date.getHours() + 1);
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

JCCalendarViewDay.prototype.__drawData = function()
{
	var _this = this;

	var today = new Date();
	today.setMinutes(0);
	today.setSeconds(0);
	today.setMilliseconds(0);

	var time_start = this.SETTINGS.DAY_SHOW_NONWORK ? 0 : this.SETTINGS.DAY_START;
	var time_finish = this.SETTINGS.DAY_SHOW_NONWORK ? 24 : this.SETTINGS.DAY_FINISH;

	var date_start = this.SETTINGS.DATE_START;

	var date_finish = new Date(this.SETTINGS.DATE_FINISH);
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

		if (this.ENTRIES[i]['DATA'].length == 0 && this._parent.FILTER.USERS_ALL == 'N')
		{
			continue;
		}
		var row_index = -1;

		if (null != this.ENTRIES[i]['DATA'] && this.ENTRIES[i]['DATA'].length > 0)
		{
			for (var j = 0; j < this.ENTRIES[i]['DATA'].length; j++)
			{
				var ts_start = this.ENTRIES[i]['DATA'][j]['DATE_ACTIVE_FROM'];
				var ts_finish = this.ENTRIES[i]['DATA'][j]['DATE_ACTIVE_TO'];

				if (this.SETTINGS.DATE_START.valueOf() >= ts_start.valueOf() && this.SETTINGS.DATE_FINISH.valueOf() <= ts_finish.valueOf()+1000)
				{
					row_index = this.obDelimRow.rowIndex;
					break;
				}
			}
		}

		var obRow = this.obTable.tBodies[0].insertRow(row_index);

		obRow.insertCell(-1);
		obRow.cells[0].className = 'bx-calendar-day-first-col';

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
		for (var j = time_start; j < time_finish; j++)
		{
			var obCell = obRow.insertCell(-1);

			obCell.className = 'bx-calendar-day-hour' + (this.SETTINGS.DAY_SHOW_NONWORK ? '' : '-long');

			if (cur_date.valueOf() == today.valueOf())
				obCell.className += ' bx-calendar-day-now';

			if (j < this.SETTINGS.DAY_START || j >= this.SETTINGS.DAY_FINISH)
				obCell.className += ' bx-calendar-day-hour-nonwork';

			if (BX.browser.IsIE())
				obCell.innerHTML = '&nbsp;';

			cur_date.setHours(cur_date.getHours() + 1);
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
				var ts_start = this.ENTRIES[i]['DATA'][j]['DATE_ACTIVE_FROM'];
				var ts_finish = this.ENTRIES[i]['DATA'][j]['DATE_ACTIVE_TO'];

				this.ENTRIES[i]['DATA'][j].VISUAL = document.createElement('DIV');

				this.ENTRIES[i]['DATA'][j].VISUAL.bx_color_variant = this.ENTRIES[i]['DATA'][j]['TYPE'].length ? this.ENTRIES[i]['DATA'][j]['TYPE'] : 'OTHER';

				this.ENTRIES[i]['DATA'][j].VISUAL.className = 'bx-calendar-entry bx-calendar-color-' + this.ENTRIES[i]['DATA'][j].VISUAL.bx_color_variant;
				this.ENTRIES[i]['DATA'][j].VISUAL.style.background = this.TYPE_BGCOLORS[(this.ENTRIES[i]['DATA'][j]['TYPE'].length ? this.ENTRIES[i]['DATA'][j]['TYPE'] : 'OTHER')];

				this.ENTRIES[i]['DATA'][j].VISUAL.style.top = (obRowPos.top + padding) + 'px';

				if (date_start.valueOf() > ts_start.valueOf())
					ts_start = date_start;
				if (date_finish.valueOf() < ts_finish.valueOf())
					ts_finish = date_finish;

				var startIndex = ts_start.getHours() + 1 - time_start,
					finishIndex = ts_finish.getHours() + 1 - time_start;

				if(finishIndex<startIndex)
					finishIndex = startIndex;

				var obStartCell = obUserRow.cells[startIndex],
					obFinishCell = obUserRow.cells[finishIndex];

				var obPos = BX.pos(obStartCell, true),
					start_pos = obPos.left + padding;

				if (ts_start.getMinutes() > 40)
					start_pos += (obPos.right-obPos.left);
				else if (ts_start.getMinutes() > 20)
					start_pos += Math.round((obPos.right-obPos.left)/2);

				if (obStartCell != obFinishCell)
					obPos = BX.pos(obFinishCell, true);

				var finish_pos = obPos.left;

				if (ts_finish.getMinutes() > 40)
					finish_pos = obPos.right;
				else if (ts_finish.getMinutes() > 20)
					finish_pos += Math.round((obPos.right-obPos.left)/2);

				var width = Math.abs(parseInt(finish_pos - start_pos - padding * 2));

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