function JCEC(arConfig, arEvents, arSPEvents) // Javascript Class Event Calendar
{
	this.arConfig = arConfig;
	this.arEvents = arEvents;
	this.arSPEvents = arSPEvents || [];
	this.arCalendars = arConfig.arCalendars;
	this.id = this.arConfig.id;
	this.iblockId = this.arConfig.iblockId;
	this.bLoadAllEvents = this.arConfig.load_all_events || false;
	this.bReadOnly = this.arConfig.bReadOnly;
	this.bOnunload = false;
	this.SessionLostStr = 'BX_EC_DUBLICATE_ACTION_REQUEST';
	this.ownerType = this.arConfig.ownerType || false;
	this.ownerId = this.arConfig.ownerId || false;
	this.section_id = this.arConfig.section_id || false;
	this.bSuperpose = this.arConfig.bSuperpose || false;
	this.StartupEvent = this.arConfig.startupEvent;
	this.actionUrl = this.arConfig.page;
	this.bUser = this.ownerType == 'USER';
	this.meetingRooms = this.arConfig.meetingRooms || [];
	this.bUseMR = (this.arConfig.allowResMeeting || this.arConfig.allowVideoMeeting) && this.meetingRooms.length > 0;

	if (this.bUser)
	{
		this.meetingCalendarId = this.arConfig.Settings.MeetCalId;
		this.arConfig.Settings.blink = this.arConfig.Settings.blink !== false;
	}

	this.Init();
	window.BX_DATE_FORMAT = this.arConfig.dateFormat;
}

JCEC.prototype = {
Init: function()
{
	this.DaysTitleCont = BX(this.id + '_days_title');
	this.DaysGridCont = BX(this.id + '_days_grid');

	//Prevent selection while drag
	DenyDragEx(this.DaysGridCont);

	this.maxEventCount = 3; // max count of visible events in day
	this.activeDateDays = {};
	this._bScelTableSixRows = false;
	this.oDate = new Date();
	this.accessColors = this.arConfig.accessColors;

	this.currentDate =
	{
		date: this.oDate.getDate(),
		day: this.convertDayIndex(this.oDate.getDay()),
		month: this.oDate.getMonth(),
		year: this.oDate.getFullYear()
	};

	this.activeDate = clone(this.currentDate);

	if (this.arConfig.init_month && this.arConfig.init_year)
	{
		this.activeDate.month = this.arConfig.init_month - 1;
		this.activeDate.year = this.arConfig.init_year;
	}

	this.activeDate.week = this.GetWeekByDate(this.activeDate);
	this.LoadEventsCount = 0;
	this.loadReqCount = 0;
	this.arLoadedMonth = {};
	this.arLoadedMonth[this.activeDate.month + '.' + this.activeDate.year] = true;
	this.arLoadedEventsId = {};
	this.arLoadedParentId = {};

	var i, l;
	for (i = 0, l = this.arEvents.length; i < l; i++)
	{
		this.arLoadedEventsId[this.GetEventSmartId(this.arEvents[i])] = true;
		if (this.arEvents[i].HOST && this.arEvents[i].HOST.parentId)
			this.arLoadedParentId[this.arEvents[i].HOST.parentId] = true;
	}
	//Days selection init
	this.selectDaysMode = false;
	this.selectDaysStartObj = false;
	this.selectDaysEndObj = false;
	this.curTimeSelection = {};
	this.curDayTSelection = {};
	this.CalMenu = new ECCalMenu(this);

	if (!window.phpVars || !window.phpVars.ADMIN_THEME_ID)  // For anonymus  users
		window.phpVars = {ADMIN_THEME_ID: '.default'};

	this.week_holidays = {};
	var wh = this.arConfig.week_holidays;
	for (i = 0, l = wh.length; i < l; i++)
		this.week_holidays[wh[i]] = true;

	this.year_holidays = {};
	var
		_this = this,
		dm, i, l,
		yh = this.arConfig.year_holidays;

	for (i = 0, l = yh.length; i < l; i++)
	{
		dm = yh[i].split('.');
		this.year_holidays[parseInt(dm[0]) + '.' + (parseInt(dm[1]) - 1)] = true;
	}

	window.onbeforeunload = function(){_this.bOnunload = true;};

	this.BuildCalendarSelector();
	this.AddSuperposedEvents(); // Add events to this.arEvents
	this.BuildSPCalendarSelector();

	this.BuildButtonsCont();
	this.InitTabControl();
	this.InitDialogCore();

	BX.bind(window, "resize", function(){_this.OnResize();});

	if (this.arConfig.bCalDAV)
	{
		this.arConnections = this.arConfig.connections;
	}

	if (this.arConfig.bShowBanner)
		new ECBanner(this);
},

InitTabControl: function()
{
	this.Tabs = {};

	if (!this._sceleton_table)
		this._sceleton_table = BX(this.id + '_sceleton_table');

	this.startTabId = this.arConfig.Settings.tabId;
	this.InitTab({id: 'month', tabContId: this.id + '_tab_month', bodyContId: this.id + '_scel_table_month'});
	this.InitTab({id: 'week', tabContId: this.id + '_tab_week', bodyContId: this.id + '_scel_table_week', daysCount: 7});
	this.InitTab({id: 'day', tabContId: this.id + '_tab_day', bodyContId: this.id + '_scel_table_day', daysCount: 1});

	this.SetTab(this.startTabId, true);
},

InitTab : function(arParams)
{
	var pTabCont = BX(arParams.tabContId);
	if (!pTabCont)
		return;

	var _this = this;
	pTabCont.onclick = function() {_this.SetTab(arParams.id);};

	this.Tabs[arParams.id] = {
		id : arParams.id,
		pTabCont : pTabCont,
		bodyContId : arParams.bodyContId,
		daysCount : arParams.daysCount || false,
		needRefresh: false,
		setActiveDate : false
	}
},

SetTab : function(tabId, bFirst, P)
{
	var oTab = this.Tabs[tabId];
	if (tabId == this.activeTabId)
		return;

	var
		prevTabId = this.activeTabId;
		tblDis = BX.browser.IsIE() && !BX.browser.IsIE9() ? 'inline' : 'table';
	if (!oTab.bLoaded || bFirst)
	{
		oTab.pBodyCont = BX(oTab.bodyContId);
		//Prevent selection while drag
		DenyDragEx(oTab.pBodyCont);
	}

	if (this.activeTabId)
	{
		this.ShowSelector(this.activeTabId, false) // Hide selector
		BX.removeClass(this.Tabs[this.activeTabId].pTabCont, 'bxec-tab-div-act'); // Deactivate TAB
		this.Tabs[this.activeTabId].pBodyCont.style.display = 'none'; // Hide body cont
	}

	BX.addClass(oTab.pTabCont, 'bxec-tab-div-act'); // Activate cur tab
	this.activeTabId = tabId;
	if (!oTab.bLoaded || bFirst)
	{
		var
			ad = this.activeDate,
			cd = this.currentDate,
			d, w, m, y;
		if ((ad.month && ad.month != cd.month) || (ad.year && ad.year != cd.year))
		{
			d = 1;
			w = 0;
			m = ad.month;
			y = ad.year;
		}
		else
		{
			var xd = (prevTabId == 'day' && ad) ? ad : cd;
			w = this.GetWeekByDate(xd);
			d = xd.date;
			m = xd.month;
			y = xd.year;
		}

		this.activeTabId = tabId;

		switch (tabId)
		{
			case 'month':
				this.MonthSelector = new ECMonthSelector(this);
				this.BuildDaysTitle();
				this.SetMonth(m, y);
				break;
			case 'week':
				this.BuildWeekSelector();
				this.BuildWeekDaysTable();
				this.SetWeek(w, m, y);
				break;
			case 'day':
				this.BuildDaySelector();
				this.BuildSingleDayTable();
				if (!P || P.bSetDay !== false)
					this.SetDay(d, m, y);
				break;
		}
		oTab.bLoaded = true;
	}
	else if(!P || P.bSetDay !== false)
	{
		if (prevTabId == 'day' && tabId == 'week')
			oTab.setActiveDate = true;

		if (oTab.needRefresh)
		{
			var _this = this;
			if (tabId == 'month')
				this.DisplayEventsMonth(true);
			else
				setTimeout(function(){_this.RelBuildEvents(tabId);}, 20);
		}
		else if (oTab.setActiveDate)
		{
			switch (tabId)
			{
				case 'month':
					this.SetMonth(this.activeDate.month, this.activeDate.year);
					break;
				case 'week':
					this.SetWeek(this.GetWeekByDate(this.activeDate), this.activeDate.month, this.activeDate.year);
					break;
				case 'day':
					this.SetDay(1, this.activeDate.month, this.activeDate.year);
					break;
			}
		}
	}

	if (this.StartupEvent)
	{
		for (var i = 0, l = this.arEvents.length; i < l; i++)
		{
			if (this.StartupEvent.id == this.arEvents[i].ID)
				this.ShowStartUpEvent(this.arEvents[i]);
		}
	}
	oTab.needRefresh = false;
	oTab.setActiveDate = false;
	this.ShowSelector(tabId, true) // Show new selector
	oTab.pBodyCont.style.display = tblDis; // Show tab content
	oTab.bLoaded = true;

	if (this._bScelTableSixRows && this._sceleton_table)
	{
		if (this.activeTabId == 'month')
			BX.addClass(this._sceleton_table, 'BXECSceleton-six-rows');
		else
			BX.removeClass(this._sceleton_table, 'BXECSceleton-six-rows');
	}

	if (!bFirst)
		this.SaveSettings();
},

GetWeekByDate : function(oDate)
{
	var D1 = new Date();
	D1.setFullYear(oDate.year, oDate.month, 1); // 1'st day of month
	w = Math.floor((oDate.date + this.convertDayIndex(D1.getDay()) - 1) / 7);
	return w;
},

SetTabNeedRefresh : function(tabId, bNewDate)
{
	var i, Tab;
	for (i in this.Tabs)
	{
		Tab = this.Tabs[i];
		if (typeof Tab != 'object' || Tab.id == tabId)
			continue;
		if (!bNewDate && Tab.needRefresh === false)
			Tab.needRefresh = true;
		else if (bNewDate && Tab.setActiveDate === false)
			Tab.setActiveDate = true;
	}
},

BuildButtonsCont : function()
{
	this.ButtonsCont = BX(this.id + '_buttons_cont');
	var _this = this, but;
	if (!this.bReadOnly)
	{
		this.ButtonsCont.appendChild(BX.create('IMG', {
			props: {src: '/bitrix/images/1.gif', className: 'bxec-panel-but bxec-add-new-but', title: EC_MESS.AddNewEvent},
			events: {click: function() {_this.ShowEditEventDialog({});}}
		}));

		if (this.arConfig.bSocNet)
		{
			this.ButtonsCont.appendChild(BX.create('IMG', {
				props: {src: '/bitrix/images/1.gif', className: 'bxec-panel-but bxec-add-pl-but', title: EC_MESS.AddNewEventPl},
				events: {click: function(){_this.ShowEditEventDialog({bRunPlanner: true});}}
			}));
		}

		if (this.ownerType == 'USER') // User settings
		{
			this.ButtonsCont.appendChild(BX.create('IMG', {
				props: {src: '/bitrix/images/1.gif', className: 'bxec-panel-but bxec-user-set-but', title: EC_MESS.UserSettings},
				events: {click: function(){_this.ShowUSetDialog();}}
			}));
		}
	}

	if (this.arConfig.reserveMeetingReadonlyMode)
	{
		var pCont = this.ButtonsCont.appendChild(BX.create('DIV', {props: {className: 'bx-reserve-meeting-cont'}}));
		pCont.appendChild(BX.create('I', {props: {className: 'bx-reserve-meeting-icon'}}));
		pCont.appendChild(BX.create('A', {props: {className: 'bx-reserve-meeting-link',href: this.arConfig.pathToReserveNew,title: EC_MESS.ReserveRoomTitle},text: EC_MESS.ReserveRoom}));
	}
},

ShowSelector : function(tabId, bShow)
{
	var pWnd;
	switch (tabId)
	{
		case 'month':
			pWnd = this.MonthSelector.pWnd;
			break;
		case 'week':
			pWnd = this.WeekSelector.pWnd;
			break;
		case 'day':
			pWnd = this.DaySelector.pWnd;
			break;
	}
	pWnd.style.display = bShow ? 'block' : 'none';
},

SetView : function(P)
{
	if (!bxInt(P.week) && P.week !== 0)
		P.week = this.activeDate.week;
	if (!bxInt(P.date))
		P.date = this.activeDate.date;

	switch (this.activeTabId)
	{
		case 'month':
			return this.SetMonth(P.month, P.year);
		case 'week':
			return this.SetWeek(P.week, P.month, P.year);
		case 'day':
			return this.SetDay(P.date || 1, P.month, P.year);
	}
},

SetMonth : function(m, y)
{
	if (!this.arLoadedMonth[m + '.'+ y] && !this.bLoadAllEvents)
		return this.LoadEvents(m, y);
	var bSetActiveDate = this.activeDate.month != m || this.activeDate.year != y;
	this.activeDate.month = m;
	this.activeDate.year = y;
	if (!this.activeDate.week)
		this.activeDate.week = 0;
	if (bSetActiveDate)
		this.SetTabNeedRefresh('month', true);

	this.MonthSelector.OnChange(m, y);
	this.BuildDaysGrid(m, y);
},

BuildDaysTitle : function()
{
	var i, left, width, r, c;
	this.arDaysTitle = [];
	r = this.DaysTitleCont.rows[0];
	for (i = 0; i < 7; i++)
	{
		c = r.cells[i];
		c.innerHTML = this.arConfig.days[i][1];
		c.title = this.arConfig.days[i][0];
		if (this.week_holidays[i])
			c.className = 'bxec-holiday';
	}
	r.cells[6].style.border = '0px';
},

BuildDaysGrid : function(month, year)
{
	BX.cleanNode(this.DaysGridCont);
	var oDate = new Date();
	oDate.setFullYear(year, month, 1);

	this.activeDateDays = {};
	this.activeDateDays = [];
	this.activeDateObjDays = [];
	this.arWeeks = [];

	this.oDaysGridTable = BX.create('TABLE', {props: {className : 'bxec-days-grid-table', cellPadding: 0, cellSpacing: 0}});
	var firstDay = this.convertDayIndex(oDate.getDay());
	if (firstDay > 0) // build previous month days
		this.BuildPrevMonthDays(firstDay, month, year);

	var date, day;
	while(oDate.getMonth() == month)
	{
		date = oDate.getDate();
		day = this.convertDayIndex(oDate.getDay());
		this.BuildDayCell(date, day, true, month, year);
		oDate.setDate(date + 1);
	}

	if (day != 6) // build next month days
		this.BuildNextMonthDays(day, month, year);

	this.maxEventCount = this.oDaysGridTable.rows.length > 5 ? 2 : 3;

	this.DaysGridCont.appendChild(this.oDaysGridTable);
	var rowLength = this.oDaysGridTable.rows.length;
	if (rowLength == 6 && !this._bScelTableSixRows)
	{
		if (!this._sceleton_table)
			this._sceleton_table = BX(this.id + '_sceleton_table');
		this._bScelTableSixRows = true;
		BX.addClass(this._sceleton_table, 'BXECSceleton-six-rows');
	}
	else if(this._sceleton_table && this._bScelTableSixRows && rowLength < 6)
	{
		this._bScelTableSixRows = false;
		BX.removeClass(this._sceleton_table, 'BXECSceleton-six-rows');
	}
	this.BuildEventHolder();
},

BuildPrevMonthDays : function(day, curMonth, curYear)
{
	var date, i, month, year;
	var oDate = new Date();
	oDate.setFullYear(curYear, curMonth, 1);
	oDate.setDate(oDate.getDate() - day);
	for (i = 0; i < day; i++)
	{
		date = oDate.getDate();
		month = oDate.getMonth();
		year = oDate.getFullYear();
		oDate.setDate(oDate.getDate() + 1);
		this.BuildDayCell(date, i, false, month, year);
	}
},

BuildNextMonthDays : function(day, curMonth, curYear)
{
	var date, i;
	if (curMonth == 11)
	{
		curMonth = 0;
		curYear++;
	}
	else
		curMonth++;

	var oDate = new Date();
	oDate.setFullYear(curYear, curMonth, 1);
	for (i = day + 1; i < 7; i++)
	{
		var date = oDate.getDate();
		oDate.setDate(oDate.getDate() + 1);
		this.BuildDayCell(date, i, false, curMonth, curYear);
	}
},

BuildDayCell : function(date, day, bCurMonth, month, year)
{
	var width, left, top, oDay, cn, _this = this;
	if (day == 0)
		this._curRow = this.oDaysGridTable.insertRow(-1);

	// Make className
	var bHol = this.week_holidays[day] || this.year_holidays[date + '.' + month]; //It's Holliday
	cn = 'bxec-day';
	if (!bCurMonth && !bHol)
		cn += ' bxec-day-past';
	else if(!bCurMonth)
		cn += ' bxec-day-past-hol';
	else if (bHol)
		cn += ' bxec-holiday';

	if (date == this.currentDate.date && month == this.currentDate.month && year == this.currentDate.year)
		cn += ' bxec-current-day';
	oDay = this._curRow.insertCell(-1);
	oDay.className = cn;
	oDay.innerHTML = '<table class="bxec-daytbl"><tr><td valign="top"><a class="bxec-day-link" href="javascript:void(0)" title="' + EC_MESS.GoToDay + '">' + date + '</a></td></tr>' +
	'<tr><td class="bxec-more-events"><div>&nbsp;</div></td></tr>' +
	'</table>';
	var link = oDay.firstChild.rows[0].cells[0].firstChild;
	link.onmousedown = function(e){return BX.PreventDefault(e);};
	link.onclick = function(e)
	{
		var D = _this.activeDateDays[parseInt(BX.findParent(this, {tagName: 'table', className: 'bxec-daytbl'}).parentNode.id.substr(9))];
		_this.SetTab('day', false, {bSetDay: false});
		_this.SetDay(D.getDate(), D.getMonth(), D.getFullYear());
		return BX.PreventDefault(e);
	};
	if (day == 6)
		oDay.style.borderRight = '0px';

	if (!this.bReadOnly)
	{
		oDay.onmouseover = function(){_this.oDayOnMouseOver(this);};
		oDay.onmousedown = function(){_this.oDayOnMouseDown(this)};
		oDay.onmouseup = function() {_this.oDayOnMouseUp(this)};
	}
	this.addToActiveDateDays(year, month, date, oDay)
},

oDayOnMouseOver : function(pDay)
{
	if (this.selectDaysMode)
	{
		this.selectDaysEndObj = pDay;
		this.SelectDays();
	}
},

oDayOnMouseDown : function(pDay)
{
	this.selectDaysMode = true;
	this.selectDaysStartObj = this.selectDaysEndObj = pDay;
	if (pDay.className.indexOf('bxec-day-selected') == -1)
		return this.SelectDays();
	this.selectDaysMode = false;
	this.DeSelectDays();
	this.CloseAddEventDialog();
},

oDayOnMouseUp : function(pDay)
{
	if (!this.selectDaysMode)
		return;
	this.selectDaysEndObj = pDay;
	this.SelectDays();
	this.ShowAddEventDialog();
	this.selectDaysMode = false;
},

oDayOnDoubleClick : function(pDay) {},
oDayOnContextMenu : function(pDay) {},

addToActiveDateDays : function(year, month, date, oDay)
{
	oDay.id = 'bxec_ind_' + this.activeDateDays.length;
	this.activeDateDays.push(new Date(year, month, date));
	this.activeDateObjDays.push(
	{
		pDiv: oDay,
		arEvents: {begining : [], all : []}
	});
},

RefreshEventsOnWeeks : function(arWeeks)
{
	for (var i = 0, l = arWeeks.length; i < l; i++)
		this.RefreshEventsOnWeek(arWeeks[i]);
},

RefreshEventsOnWeek : function(ind)
{
	var
		startDayInd = ind * 7,
		endDayInd = (ind + 1) * 7,
		day, i, arEv, j, ev, arAll, displ, arHid,
		slots = [],
		step = 0;

	for(j = 0; j < this.maxEventCount; j++)
		slots[j] = 0;

	for (i = startDayInd; i < endDayInd; i++)
	{
		day = this.activeDateObjDays[i];
		if (!day) continue;
		day.arEvents.hidden = [];
		arEv = day.arEvents.begining;
		n = arEv.length;
		arHid = [];

		if (n > 0)
		{
			arEv.sort(function(a, b){return b.daysCount - a.daysCount});
			eventloop:
			for(k = 0; k < n; k++)
			{
				ev = arEv[k];
				if (!ev) continue;

				if (!this.arEvents[ev.oEvent.ind])
				{
					day.arEvents.begining = arEv = deleteFromArray(arEv, k);
					ev = arEv[k];
					if (!ev) continue; //break ?
				}

				for(j = 0; j < this.maxEventCount; j++)
				{
					if (slots[j] - step <= 0)
					{
						slots[j] = step + ev.daysCount;
						this.ShowEventOnLevel(ev.oEvent.oParts[ev.partInd], j, ind);
						continue eventloop;
					}
				}
				arHid[ev.oEvent.ID] = true;
				day.arEvents.hidden.push(ev);
			}
		}
		// For all events in the day
		arAll = day.arEvents.all;
		for (var x = 0, f = arAll.length; x < f; x++)
		{
			ev = arAll[x];
			if (!ev || arHid[ev.oEvent.ID])
				continue;
			if (!this.arEvents[ev.oEvent.ind])
			{
				day.arEvents.all = arAll = deleteFromArray(arAll, x);
				ev = arAll[x];
				if (!ev) continue;
			}
			displ = ev.oEvent.oParts[ev.partInd].style.display;
			if (displ && displ.toLowerCase() == 'none')
				day.arEvents.hidden.push(ev);
		}
		this.ShowMoreEventsSelect(day);
		step++;
	}
},

ShowEventOnLevel : function(pDiv, level, week)
{
	if (!this.arWeeks[week])
		this.arWeeks[week] = {top: parseInt(this.oDaysGridTable.rows[week].cells[0].offsetTop) + 22};

	var top = this.arWeeks[week].top + level * 18;
	pDiv.style.display = 'block';
	pDiv.style.top = top + 'px';
},

ShowMoreEventsSelect : function(oDay)
{
	var
		_this = this,
		i, el, part, arHidden = [],
		pMoreDiv = oDay.pDiv.firstChild.rows[1].cells[0].firstChild, //More events element
		arEv = oDay.arEvents.hidden,
		l = arEv.length;

	if (l <= 0)
	{
		pMoreDiv.style.display = 'none';
		return;
	}

	for (i = 0; i < l; i++)
	{
		el = arEv[i];
		part = el.oEvent.oParts[el.partInd];
		part.style.display = "none"; // Hide event from calendar grid

		if (!el.oEvent.pMoreDivs)
			el.oEvent.pMoreDivs = [];
		el.oEvent.pMoreDivs.push(pMoreDiv);
		arHidden.push({pDiv: part, oEvent: el.oEvent});
	}

	BX.adjust(pMoreDiv, {
		style: {display: 'block'},
		html: EC_MESS.MoreEvents + ' (' + arHidden.length + ' ' + EC_MESS.Item + ')'
	});

	pMoreDiv.onmousedown = function(e){if(!e) e = window.event; BX.PreventDefault(e);};
	pMoreDiv.onclick = function(){_this.ShowMoreEventsWin({Events: arHidden, id: oDay.pDiv.id, pDay: oDay.pDiv});};
},

SelectDays : function()
{
	if (!this.arSelectedDays)
		this.arSelectedDays = [];
	this.bInvertedDaysSelection = false;

	if (this.arSelectedDays.length > 0)
		this.DeSelectDays();

	if (!this.selectDaysStartObj || !this.selectDaysEndObj)
		return;

	var
		start_ind = parseInt(this.selectDaysStartObj.id.substr(9)),
		end_ind = parseInt(this.selectDaysEndObj.id.substr(9)),
		el, i, _a;

	if (start_ind > end_ind) // swap start_ind and end_ind
	{
		_a = end_ind;
		end_ind = start_ind;
		start_ind = _a;
		this.bInvertedDaysSelection = true;
	}

	for (i = start_ind; i <= end_ind; i++)
	{
		el = this.activeDateObjDays[i];
		if (!el || !el.pDiv)
			continue;
		BX.addClass(el.pDiv, 'bxec-day-selected');
		this.arSelectedDays.push(el.pDiv);
	}
},

DeSelectDays : function()
{
	if (!this.arSelectedDays)
		return;
	var el, i, l;
	for (i = 0, l = this.arSelectedDays.length; i < l; i++)
		BX.removeClass(this.arSelectedDays[i], 'bxec-day-selected');
	this.arSelectedDays = [];
},

DisplayError : function(str, bReloadPage)
{
	var _this = this;
	setTimeout(function(){
		if (!_this.bOnunload)
		{
			alert(str || '[Event Calendar] Error!');
			if (bReloadPage)
				window.location = window.location;
		}
	}, 200);
},

GetEventColor : function(oEvent)
{
	var id = oEvent.IBLOCK_SECTION_ID;
	if (id && this.oCalendars[id] && this.oCalendars[id].COLOR)
		return this.oCalendars[id].COLOR;
	return '#CEE669';
},

SetEventsColors : function(oEvent)
{
	var id = oEvent.IBLOCK_SECTION_ID, color = '#CEE669';
	if (id && this.oCalendars[id] && this.oCalendars[id].COLOR)
		color = this.oCalendars[id].COLOR;

	oEvent.displayColor = color;
	oEvent.bDark = this.ColorIsDark(color);
	//oEvent.displayText = color;
	return oEvent;
},

GetEventSmartId : function(E)
{
	if (!E.PERIOD)
		return E.ID;
	return E.ID + E.DATE_FROM;
},

BuildCalendarSelector : function()
{
	this.oCalendars = {};
	this.oSpCalendars = {};
	this.oActiveCalendars = {};

	if (this.arCalendars.length < 1 && this.bReadOnly)
		return;
	this.CalendarSelCont = BX(this.id + '_calendar_div');

	var pFliper = BX(this.id + '_cal_bar_fliper');
	this.InitFliper(pFliper, 'CalendarSelCont');
	this.InitCalBarGlobChecker(false);

	if (!this.CalendarSelCont)
		return;
	BX.cleanNode(this.CalendarSelCont);
	this.CalendarSelCont.style.display = 'block';

	var arIds = this.arConfig.arCalendarIds || [];

	var i, l = this.arCalendars.length, j, n = arIds.length, bChecked;
	var _bChecked = 'none';
	if (l > 0)
	{
		_bChecked = true;
		for (i = 0; i < l; i++)
		{
			bChecked = false;
			for (j = 0; j < n; j++)
			{
				if (bxInt(arIds[j]) == bxInt(this.arCalendars[i].ID))
				{
					bChecked = true;
					break;
				}
			}
			if (!bChecked)
				_bChecked = bChecked;
			this.DisplayCalendarElement(this.arCalendars[i], bChecked);
		}

		this.defaultCalendarId = this.arCalendars[0]['ID'];
	}
	this.CheckCalBarGlobChecker(_bChecked);

	if (this.bReadOnly)
		return;

	var _this = this;
	BX(this.id + '_add_calendar_link').onclick = function(){_this.ShowEditCalDialog();};

	if (this.arConfig.bCalDAV)
		BX(this.id + '_external').onclick = function(){_this.ShowExternalDialog();};
},

DisplayCalendarElement : function(el, bChecked, bSuperpose)
{
	bSuperpose = !!bSuperpose;
	// Determine container
	var pCont = bSuperpose ? this.SPCalendarSelCont : this.CalendarSelCont;

	if (!bSuperpose)
	{
		// External CalDav calendar
		if (el.CALDAV_CON)
		{
			if (!this.pCalDAVCalCont)
			{
				this.pCalDAVCalCont = this.CalendarSelCont.appendChild(BX.create("DIV"));
				this.pCalDAVCalCont.appendChild(BX.create("DIV", {props: {className: 'bxec-caldav-title'}, html: EC_MESS.CalDavTitle}));
			}

			// We put elements to subconteiner
			pCont = this.pCalDAVCalCont;
		}
		else
		{
			if (!this.pCalSubCont)
				this.pCalSubCont = this.CalendarSelCont.appendChild(BX.create("DIV"));

			pCont = this.pCalSubCont;
		}
	}

	el.bDark = this.ColorIsDark(el.COLOR);
	var
		bActive = !this.bReadOnly || el.EXPORT,
		pEl = pCont.appendChild(BX.create('DIV', {
			props: {className: 'bxec-calendar-el' + (el.bDark && bChecked ? ' bxec-cal-dark' : '')},
			html: '<table class="bxec-tbl"><tr><td class="' + (bActive ? 'bxec-cal-menu' : 'bxec-cal-menu-dis')+ '"><img class="bxec-iconkit" src="/bitrix/images/1.gif"/></td><td class="bxec-title"><nobr>' + el.NAME + '</nobr></td><td><img class="bxec-iconkit" src="/bitrix/images/1.gif"/></td></tr></table>'
		}));

	var _this = this;
	var pCh = pEl.firstChild.rows[0].cells[2];
	pCh.className = bChecked ? 'bxec-checkbox' : 'bxec-checkbox-off';
	el._pElement = pEl;
	el.pCh = pCh;

	this.AppendCalendarHint(el, bSuperpose);

	pCh.onclick = function()
	{
		var checked = (this.className == 'bxec-checkbox-off');
		if (el.bDark)
		{
			if (checked)
				BX.addClass(el._pElement, 'bxec-cal-dark');
			else
				BX.removeClass(el._pElement, 'bxec-cal-dark');
		}
		_this.ShowCalendar(el, checked);
		this.focus();
	};
	pEl.firstChild.rows[0].cells[1].onclick = function() {this.nextSibling.onclick();};
	if (bActive)
		pEl.firstChild.rows[0].cells[0].onclick = function(){_this.CalMenu.Show(el, this, bSuperpose);};

	this.oCalendars[el['ID']] = el;
	pEl.style.backgroundColor = bChecked ? el.COLOR : 'transparent';
	this.oActiveCalendars[el['ID']] = bChecked;
},

ColorIsDark: function(color)
{
	if (color.charAt(0) == "#")
		color = color.substring(1, 7);
	var
		r = parseInt(color.substring(0, 2), 16),
		g = parseInt(color.substring(2, 4), 16),
		b = parseInt(color.substring(4, 6), 16),
		light = (r * 0.8 + g + b * 0.2) / 510 * 100;
	return light < 50;
},

AppendCalendarHint: function(el, bSuperpose)
{
	if (el.oHint && el.oHint.Destroy)
		el.oHint.Destroy();

	//append Hint
	var hintContent;
	if (bSuperpose && el.SP_PARAMS)
		hintContent = '<b>' + el.SP_PARAMS.GROUP_TITLE + ' > ' + el.SP_PARAMS.NAME + ' > ' + el.NAME + '</b>';
	else
		hintContent = '<b>' + el.NAME + '</b>';

	var desc_len = el.DESCRIPTION.length, max_len = 350;
	if (desc_len > 0)
	{
		if (desc_len < max_len)
			hintContent += "<br>" + el.DESCRIPTION;
		else
			hintContent += "<br>" + el.DESCRIPTION.substr(0, max_len) + '...';
	}

	el.oHint = new BX.CHintSimple({parent: el._pElement, hint: hintContent});
},

ShowCalendar : function(el, bShow, bDontReload, bEffect2Bro)
{
	if (!el)
		return;

	var bc = bShow ? el.COLOR : 'transparent';
	var cn = bShow ? 'bxec-checkbox' : 'bxec-checkbox-off';
	if (bEffect2Bro !== false)
		bEffect2Bro = true;

	if (el._bro && !bEffect2Bro && !bShow)
	{
		el._pElement.style.backgroundColor = bc;
		el.pCh.className = cn;
	}
	else
	{
		if (el._bro)
		{
			el._bro.pCh.className = cn;
			el._bro.pElement.style.backgroundColor = bc;
		}
		el._pElement.style.backgroundColor = bc;
		el.pCh.className = cn;
		this.oActiveCalendars[el.ID] = bShow;
	}

	if (!bDontReload)
	{
		this.SetTabNeedRefresh(this.activeTabId);
		this.ReloadEvents();
	}
},

SaveCalendar : function()
{
	var el = this.oEdCalDialog.currentCalendar;
	if (this.oEdCalDialog.oName.value.length <= 0)
	{
		alert(EC_MESS.CalenNameErr);
		this.bEditCalDialogOver = true;
		return false;
	}

	var postData = this.GetPostData('calendar_edit', {name : this.oEdCalDialog.oName.value, desc : this.oEdCalDialog.oDesc.value, color : this.oEdCalDialog.colorInput.value});

	if (el.ID)
		postData.id = bxInt(el.ID);
	else if (this.oEdCalDialog.pExch)
		postData.is_exchange = this.oEdCalDialog.pExch.checked ? 'Y' : 'N';

	if (this.bUser)
		postData.private_status = this.oEdCalDialog.oStatus.value;

	if (this.bUser && this.oEdCalDialog.oMeetingCalendarCh.checked)
		postData.is_def_meet_calendar = 'Y';

	if (this.oEdCalDialog.oExpAllow.checked)
	{
		postData['export'] = 'Y';
		if (this.oEdCalDialog.oExpSet.value != 'all')
			postData.exp_set = this.oEdCalDialog.oExpSet.value;
	}

	var _this = this;
	this.Request({
		postData: postData,
		errorText: EC_MESS.CalenSaveErr,
		handler: function(result)
		{
			if (_this.section_id === false)
				_this.UpdateSectionId();

			if (window._bx_calendar && window._bx_calendar.ID)
			{
				window._bx_calendar.NAME = _this.oEdCalDialog.oName.value;
				window._bx_calendar.DESCRIPTION = _this.oEdCalDialog.oDesc.value;
				window._bx_calendar.COLOR = _this.oEdCalDialog.colorInput.value;

				if (_this.bUser && _this.oEdCalDialog.oMeetingCalendarCh.checked)
					_this.meetingCalendarId = window._bx_calendar.ID;

				_this.SaveCalendarClientSide(window._bx_calendar);
			}
			else
				return false;
			return true;
		}
	});
	return true;
},

SaveCalendarClientSide : function(arParams)
{
	var
		name = bxSpCh(arParams.NAME),
		desc = bxSpCh(arParams.DESCRIPTION),
		color = bxSpCh(arParams.COLOR);

	this.DeActualizeCalendarSelectors();
	if (arParams.bNew || this.oEdCalDialog.bNew)
	{
		var O = {
			ID: bxInt(arParams.ID),
			NAME: name,
			DESCRIPTION: bxSpCh(arParams.DESCRIPTION),
			COLOR: color,
			IBLOCK_SECTION_ID: bxInt(this.section_id),
			EXPORT: arParams.EXPORT || false,
			EXPORT_SET: arParams.EXPORT_SET || 'all',
			EXPORT_LINK: arParams.EXPORT_LINK || false,
			OUTLOOK_JS: arParams.OUTLOOK_JS || ''
		};
		if (this.bUser)
			O.PRIVATE_STATUS = this.oEdCalDialog ? this.oEdCalDialog.oStatus.value : 'full';
		this.arCalendars.push(O);
		this.DisplayCalendarElement(O, true);

		if (this.bSuperpose)
			this.Add2SPCalendar(O, (!this.oEdCalDialog || this.oEdCalDialog.add2SP.checked));

		return true;
	}
	else
	{
		var cal = this.oEdCalDialog.currentCalendar;
		var bCol = cal.COLOR != color;
		cal.NAME = name;
		cal.DESCRIPTION = desc;
		cal.EXPORT = arParams.EXPORT || false;
		cal.EXPORT_SET = arParams.EXPORT_SET || 'all';
		cal.EXPORT_LINK = arParams.EXPORT_LINK || false;
		cal.COLOR = color;
		cal.OUTLOOK_JS = arParams.OUTLOOK_JS || '';
		if (this.bUser)
			cal.PRIVATE_STATUS = this.oEdCalDialog.oStatus.value;

		this._RenameCalendar(cal._pElement, name);
		if (bCol)
			this._RecolourCalendar(cal._pElement, color, cal);

		this.AppendCalendarHint(cal);

		if (cal._bro)
		{
			this._RenameCalendar(cal._bro.pElement, name);
			if (bCol)
			{
				this._RecolourCalendar(cal._bro.pElement, color);
				this.arSPCalendarsShow[cal._bro.ind].COLOR = color;
			}
		}
		// Change name in SP calendars array
		if(this.bSuperpose)
		{
			var i, l, j, n, items;
			loop:
			for (i = 0, l = this.arSPCalendars.length; i < l; i++)
			{
				items = this.arSPCalendars[i].ITEMS;
				for (j = 0, n = items.length; j < n; j++)
				{
					if (cal.ID == items[j].ID)
					{
						items[j].NAME = name;
						this.SPD_Renew(); // null  superpose dialog
						break loop;
					}
				}
			}
		}
	}
},

DeleteCalendar : function(el)
{
	if (!el.ID || !confirm(EC_MESS.DelCalendarConfirm))
		return;
	var _this = this;
	this.Request({
		postData: this.GetPostData('calendar_delete', {id : el.ID}),
		errorText: EC_MESS.DelCalendarErr,
		handler: function(result) {return window._bx_result ? _this.DeleteCalendarClientSide(el) : false;}
	});
},

DeleteCalendarClientSide : function(el)
{
	el._pElement.parentNode.removeChild(el._pElement);

	if (el._bro) // Calendar in SP
	{
		// Del from displayed
		this.arSPCalendarsShow = deleteFromArray(this.arSPCalendarsShow, el._bro.ind);
		// Remove div
		el._bro.pElement.parentNode.removeChild(el._bro.pElement);
	}
	var i, l, j, n, items;
	// Del from array of SP calendars
	if(this.bSuperpose)
	{
		loop:
		for (i = 0, l = this.arSPCalendars.length; i < l; i++)
		{
			items = this.arSPCalendars[i].ITEMS;
			for (j = 0, n = items.length; j < n; j++)
			{
				if (el.ID == items[j].ID)
				{
					this.arSPCalendars[i].ITEMS = deleteFromArray(items, j);
					this.SPD_Renew(); // null  superpose dialog
					break loop;
				}
			}
		}
	}

	var i, l = this.arCalendars.length;
	for (i = 0; i < l; i++)
	{
		if (this.arCalendars[i].ID == el.ID)
		{
			this.arCalendars = deleteFromArray(this.arCalendars, i);
			break;
		}
	}
	this.oCalendars[el.ID] = null;
	el = null;

	this.ReloadEvents();
	this.DeActualizeCalendarSelectors();
},

_RenameCalendar : function(pEl, name)
{
	pEl.firstChild.rows[0].cells[1].innerHTML = name;
},

_RecolourCalendar : function(pEl, color, oCalen)
{
	pEl.style.backgroundColor = color;
	if (!oCalen)
		return;


	var
		keys = [['oTLParts', 'week'], ['oTLParts', 'day'], ['oDaysT', 'week'], ['oDaysT', 'day']],
		i, l = this.arEvents.length, ev, j, n, x, y;

	for (i = 0; i < l; i++)
	{
		ev = this.arEvents[i];
		if (!ev)
			continue;
		if (ev.IBLOCK_SECTION_ID != oCalen.ID)
			continue;
		// Month
		n = ev.oParts.length;
		for (j = 0; j < n; j++)
			ev.oParts[j].style.backgroundColor = color;

		n = keys.length;
		for (j = 0; j < n; j++)
		{
			if (ev[keys[j][0]] && ev[keys[j][0]][keys[j][1]])
			{
				y = ev[keys[j][0]][keys[j][1]];
				if (typeof y == 'object' && y.nodeType)
					y.style.backgroundColor = color;
				else
					for (x = 0; x < y.length; x++)
						y[x].style.backgroundColor = color;
			}
		}
		this.arEvents.displayColor = color;
	}
},

InitCalBarGlobChecker : function(bSP)
{
	var id, GlCh;
	if (bSP)
	{
		id = this.id + '_sp_cal_bar_check';
		GlCh = 'CalBarGlobCheckerSP';
	}
	else
	{
		id = this.id + '_cal_bar_check';
		GlCh = 'CalBarGlobChecker';
	}

	this[GlCh] = {};
	this[GlCh].pWnd = BX(id);

	this[GlCh].flag = false; //
	this[GlCh].pWnd.title = EC_MESS.DeSelectAll; //

	var _this = this;
	this[GlCh].pWnd.onclick = function()
	{
		if (_this[GlCh].flag) // Show
		{
			_this[GlCh].flag = false;
			_this.ShowAllCalendars(true, bSP);
			_this[GlCh].pWnd.className = 'bxec-iconkit bxec-cal-bar-check';
			_this[GlCh].pWnd.title = EC_MESS.DeSelectAll;
		}
		else // Hide
		{
			_this[GlCh].flag = true;
			_this.ShowAllCalendars(false, bSP);
			_this[GlCh].pWnd.className = 'bxec-iconkit bxec-cal-bar-uncheck';
			_this[GlCh].pWnd.title = EC_MESS.SelectAll;
		}

	};
},

ShowAllCalendars : function(bShow, bSP)
{
	var arCals = bSP ? this.arSPCalendarsShow : this.arCalendars;
	var i, l = arCals.length;
	for (i = 0; i < l; i++)
	{
		el = arCals[i];
		this.ShowCalendar(el, bShow, true, !bSP);
	}
	this.ReloadEvents();
},

CheckCalBarGlobChecker : function(bCheck, bSP)
{
	var GlCh = bSP ? 'CalBarGlobCheckerSP' : 'CalBarGlobChecker';

	if (bCheck == 'none')
	{
		this[GlCh].pWnd.className = 'bxec-cal-bar-none';
		this[GlCh].pWnd.title = '';
	}
	else if (bCheck)
	{

		this[GlCh].flag = false;
		this[GlCh].pWnd.className = 'bxec-iconkit bxec-cal-bar-check';
		this[GlCh].pWnd.title = EC_MESS.DeSelectAll;
	}
	else
	{
		this[GlCh].flag = true;
		this[GlCh].pWnd.className = 'bxec-iconkit bxec-cal-bar-uncheck';
		this[GlCh].pWnd.title = EC_MESS.SelectAll;
	}
},

// * * * *  * * * *  * * * * SUPERPOSED CALENDARS, EVENTS  * * * *  * * * *  * * * *
BuildSPCalendarSelector : function(bRefresh)
{
	if (!this.bSuperpose)
		return;
	var _this = this;
	if (!bRefresh)
	{
		this.SPCalendarSelCont = BX(this.id + '_sp_calendar_div');
		this.arSPCalendars = this.arConfig.arSPCalendars;
		this.arSPCalendarsShow = this.arConfig.arSPCalendarsShow;
		this.bAllowPush2SP = true;
		var pFliper = BX(this.id + '_sp_cal_bar_fliper');
		this.InitFliper(pFliper, 'SPCalendarSelCont');
		this.InitCalBarGlobChecker(true);
	}

	if (!this.SPCalendarSelCont)
		return;
	BX.cleanNode(this.SPCalendarSelCont);
	this.SPCalendarSelCont.style.display = 'block';

	var arIds = this.arConfig.arCalendarIds || [];
	var i, l = this.arSPCalendarsShow.length, j, n = arIds.length, bChecked;
	var m = this.arCalendars.length, spcal, cal;
	var _bChecked = 'none';

	if (l > 0)
	{
		_bChecked = true;
		for (i = 0; i < l; i++)
		{
			spcal = this.arSPCalendarsShow[i];
			bChecked = false;
			for (j = 0; j < n; j++)
			{
				if (bxInt(arIds[j]) == bxInt(spcal.ID))
				{
					bChecked = true;
					break;
				}
			}
			if (!bChecked)
				_bChecked = bChecked;

			this.DisplayCalendarElement(spcal, bChecked, true);

			for (k = 0; k < m; k++)
			{
				cal = this.arCalendars[k];
				if (spcal.ID == cal.ID)
				{
					spcal._bro = {pElement: cal._pElement, pCh: cal.pCh, ind: k};
					cal._bro = {pElement: spcal._pElement, pCh: spcal.pCh, ind: i};
					break;
				}
			}
			if (spcal._bro)
				this.ShowCalendar(spcal, bChecked, true);
		}
	}
	this.CheckCalBarGlobChecker(_bChecked, true);

	if (!bRefresh)
	{
		var addCalendarLink = BX(this.id + '_sp_add_calendar');
		addCalendarLink.onclick = function(){_this.ShowSuperposeDialog()};

		var pSPExLink = BX(this.id + '_export_sp_cals');
		pSPExLink.onclick = function() {_this.ShowExportCalDialog();}
	}
},

// Add superposed events to arEvents - runs at start only
AddSuperposedEvents : function()
{
	var l = this.arSPEvents.length;
	if (!this.bSuperpose || l < 1)
		return;

	var arSPEvents_ = [], ev;
	for (var i = 0; i < l; i++)
	{
		ev = this.arSPEvents[i];
		if (this.oCalendars[ev.IBLOCK_SECTION_ID])
			continue;
		ev.bReadOnly = true;
		arSPEvents_.push(ev)
	}
	this.arSPEvents = arSPEvents_;
	this.arEvents = this.arEvents.concat(this.arSPEvents);
},

HideSPCalendar : function(el)
{
	var _this = this;
	this.Request({
		postData: this.GetPostData('spcal_hide', {id : el.ID}),
		errorText: EC_MESS.HideSPCalendarErr,
		handler: function(result){return window._bx_result ? _this.HideSPCalendarClientSide(el) : false;}
	});
},

HideSPCalendarClientSide : function(el)
{
	el._pElement.parentNode.removeChild(el._pElement);
	var i, l = this.arSPCalendarsShow.length;
	for (i = 0; i < l; i++)
	{
		if (this.arSPCalendarsShow[i].ID == el.ID)
		{
			this.arSPCalendarsShow = deleteFromArray(this.arSPCalendarsShow, i);
			break;
		}
	}
	if (!el._bro)
	{
		this.oActiveCalendars[el.ID] = false;
		this.oCalendars[el.ID] = null;
	}
	else
	{
		this.arCalendars[el._bro.ind]._bro = null;
	}

	el = null;
	this.ReloadEvents();
},

// Add calendar to all superposed calendars list
Add2SPCalendar : function(el, bDisplay)
{
	var i, l, j, n, items, need2load = true, spcal, sp_group = false, ar;
	loop:
	for (i = 0, l = this.arSPCalendars.length; i < l; i++)
	{
		items = this.arSPCalendars[i].ITEMS;
		for (j = 0, n = items.length; j < n; j++)
		{
			if (el.ID == items[j].ID) // if calendar already exist in SP calendars list
			{
				spcal = items[j];
				need2load = false;
				break loop;
			}
		}

		if (this.arSPCalendars[i].NAME == this.arConfig.SP.NAME)
		{
			sp_group = this.arSPCalendars[i];
			break;
		}
		else if (this.arSPCalendars[i].GROUP == this.arConfig.SP.GROUP && this.bUser)
		{
			sp_group = clone(this.arSPCalendars[i], false);
			sp_group.ITEMS = [];
			sp_group.NAME = this.arConfig.SP.NAME;
			sp_group.USER_ID = this.arConfig.SP.USER_ID;
			this.arSPCalendars.push(sp_group);
		}
	}
	if (need2load)
	{
		if (!sp_group && this.bUser)  // Create new group ONLY for USERs calendars
		{
			sp_group = {
				ID: this.iblockId,
				REARONLY: true,
				bDeletable: true,
				ITEMS : [],
				GROUP: this.arConfig.SP.GROUP,
				GROUP_TITLE: this.arConfig.SP.GROUP_TITLE,
				NAME : this.arConfig.SP.NAME,
				USER_ID: this.arConfig.SP.USER_ID
			};
			this.arSPCalendars.push(sp_group);
		}
		if (sp_group)
		{
			spcal = clone(el, false); // Copy el
			spcal._bro = spcal.pCh = spcal._pElement = null; // Null redundant props
			sp_group.ITEMS.push(spcal);
			this.SPD_Renew();
			this.SetCals2SP();
		}
	}

	if (spcal && bDisplay !== false)
	{
		ar = this.arSPCalendarsShow.concat([spcal]);
		this.AppendSPCalendars(ar);
	}
},

SetCals2SP : function()
{
	var _this = this;
	this.Request({
		postData: this.GetPostData('add_cal2sp'),
		errorText: EC_MESS.AppendSPCalendarErr,
		handler: function(result){return window._bx_result ? true : false;}
	});
},

AppendSPCalendars : function(arCals)
{
	var spcl = [];
	for (var i = 0, l = arCals.length; i < l; i++)
		spcl.push(bxInt(arCals[i].ID));
	var _this = this;
	this.Request({
		postData: this.GetPostData('spcal_disp_save', {spcl: spcl}),
		errorText: EC_MESS.AppendSPCalendarErr,
		handler: function(result){return window._bx_result ? _this.AppendSPCalendarsClientSide(arCals) : false;}
	});
},

AppendSPCalendarsClientSide : function(arCals)
{
	var i, l = this.arSPCalendarsShow.length, id, j, n, bEx;
	var arIds = this.arConfig.arCalendarIds;
	for (i = 0; i < l; i++)
	{
		if (!this.arSPCalendarsShow[i]._bro)
		{
			id = this.arSPCalendarsShow[i].ID;
			this.oActiveCalendars[id] = null;
			delete this.oActiveCalendars[id];
			this.oCalendars[id] = null;
		}
		else
		{
			this.arCalendars[this.arSPCalendarsShow[i]._bro.ind]._bro = null;
		}
	}

	l = arCals.length;
	for (i = 0; i < l; i++)
	{
		id = arCals[i].ID;
		this.oActiveCalendars[id] = true;
		this.oSpCalendars[id] = true;
		if (!this.oCalendars[id])
			this.oCalendars[id] = arCals[i];
		bEx = false;
		for (j = 0, n = arIds.length; j < n; j++)
		{
			if (arIds[j] == id)
			{
				bEx = true;
				break;
			}
		}
		if (!bEx)
			arIds.push(id);
	}
	this.arSPCalendarsShow = arCals;
	this.BuildSPCalendarSelector(true);
	this.ReloadEvents();
},

UpdateSectionId : function()
{
	if (this.section_id === false && bxInt(window._bx_section_id) > 0)
		this.section_id = bxInt(window._bx_section_id);
},

NullServerVars : function()
{
	window._bx_calendar = window._bx_result = window._bx_new_event = window._bx_existent_event = window._bx_section_id = window._bx_def_calendar = window._bx_add_cur_user = null;
},

GetPostData : function(action, O)
{
	if (!O) O = {};
	O.sessid = this.arConfig.sessid;
	O.bx_event_calendar_request = 'Y';
	O.section_id = this.section_id === false ? 'none' : this.section_id;
	if (action)
		O.action = action;
	return O;
},

GetCenterWindowPos : function(w, h)
{
	if (!w) w = 400;
	if (!h) h = 300;
	var S = BX.GetWindowSize(document);
	var top = bxInt(bxInt(S.scrollTop) + (S.innerHeight - h) / 2 - 30);
	var left = bxInt(bxInt(S.scrollLeft) + (S.innerWidth - w) / 2 - 30);
	return {top: top, left: left};
},

ShowWaitWindow : function()
{
	BX.showWait(this._sceleton_table);
},

CloseWaitWindow : function()
{
	BX.closeWait(this._sceleton_table);
},

ShowStartUpEvent : function(el)
{
	if (el.PERIOD && this.StartupEvent.date != el.DATE_FROM)
		return;
	var _this = this;
	setTimeout(function(){_this.ShowViewEventDialog(el);}, 50);
	this.StartupEvent = false;
},

InitFliper : function(pFliper, strCont)
{
	var
		_this = this,
		td = pFliper.parentNode,
		tr = _this[strCont].parentNode.parentNode,
		tbl = BX.findParent(tr, {tagName: 'TABLE'}),
		flag = 'b' + strCont + 'Hidden';

	td.title = EC_MESS.FlipperHide;
	_this[flag] = this.arConfig.Settings[strCont];
	var Hide = function(flag)
	{
		if (_this[flag])
		{
			pFliper.className = 'bxec-iconkit bxec-hide-arrow';
			tbl.style.width = null;
			tr.style.display = BX.browser.IsIE() ? 'inline' : 'table-row';
			td.title = EC_MESS.FlipperHide;
		}
		else
		{
			pFliper.className = 'bxec-iconkit bxec-show-arrow';
			tbl.style.width = tbl.offsetWidth + 'px';
			tr.style.display = 'none';
			td.title = EC_MESS.FlipperShow;
		}
		_this[flag] = !_this[flag];
	};
	td.onclick = function() {Hide(flag); _this.SaveSettings();};
	if (_this[flag])
	{
		_this[flag] = false;
		Hide(flag);
	}
},

SaveSettings : function(bClear)
{
	if (bClear === true)
	{
		var _this = this;

		BX.ajax.post(this.actionUrl, this.GetPostData('set_settings', {clear_all: true}), function()
			{
				setTimeout(
				function()
				{
					_this.arConfig.Settings = window._bx_result;
					if (_this.bUser)
						_this.meetingCalendarId = false;
				}, 300);
			});
	}
	else
	{
		var Set = {
			tab_id : this.activeTabId,
			cal_sec : this.bCalendarSelContHidden ? '1' : '',
			sp_cal_sec : this.bSPCalendarSelContHidden ? '1' : ''
		};

		if (this.bUser)
		{
			Set.meet_cal_id = this.meetingCalendarId || false;
			Set.blink = this.arConfig.Settings.blink;
		}

		if (this.Planner && this.Planner.bCreated)
			Set = this.Planner.AttachSettings(Set);

		if (this.arConfig.Settings.ShowBanner)
			Set.show_ban = this.arConfig.Settings.ShowBanner != 'N' ? 1 : 0;

		BX.ajax.post(this.actionUrl, this.GetPostData('set_settings', Set));
	}
},

GetUserProfileLink : function(uid, bHtml, User, cn, bOwner)
{
	var path = this.arConfig.pathToUser.toLowerCase();
	path = path.replace('#user_id#', uid);

	cn = cn ? ' class="' + cn + '"' : '';

	if (!bHtml)
		return path;

	var html = BX.util.htmlspecialchars(User.name);
	if (bOwner)
		html += ' <span style="font-weight: normal !important;">(' + EC_MESS.Host + ')</span>';

	return '<a' + cn + ' href="' + path + '" target="_blank" title="' + EC_MESS.UserProfile + ': ' + BX.util.htmlspecialchars(User.name) + '" >' + html + '</a>';
},

convertDayIndex : function(i)
{
	if (i == 0)
		return 6;
	return i - 1;
},

Request : function(P)
{
	if (!P.url)
		P.url = this.actionUrl;
	if (P.bIter !== false)
		P.bIter = true;
	if (!P.postData)
		P.postData = this.GetPostData();
	if (!P.errorText)
		errorText = false;

	var _this = this, iter = 0;
	var handler = function(result)
	{
		var handleRes = function()
		{
			_this.CloseWaitWindow();
			var erInd = result.toLowerCase().indexOf('bx_event_calendar_action_error');
			if (!result || result.length <= 0 || erInd != -1)
			{
				var errorText = '';
				if (erInd >= 0)
				{
					var
						ind1 = erInd + 'BX_EVENT_CALENDAR_ACTION_ERROR:'.length,
						ind2 = result.indexOf('-->', ind1);

					errorText = result.substr(ind1, ind2 - ind1);
				}

				return _this.DisplayError(errorText || P.errorText || '');
			}

			if (result.indexOf(_this.SessionLostStr) != -1)
			{
				if (P.bReqestReply)
				{
					_this.DisplayError(EC_MESS.LostSessionError, true);
				}
				else
				{
					var i1 = result.indexOf(_this.SessionLostStr) + _this.SessionLostStr.length;
					var sessid = result.substr(i1, result.indexOf('-->') - i1);
					_this.arConfig.sessid = P.postData.sessid = sessid; // Renew sessid;
					P.bReqestReply = true;
					result = '';
					setTimeout(function(){_this.Request(P);}, 50);
				}
				return;
			}

			var res = P.handler(result);
			if(res === false && ++iter < 20 && P.bIter)
				setTimeout(handleRes, 3);
		};
		setTimeout(handleRes, 10);
	};

	this.NullServerVars();
	this.ShowWaitWindow();
	BX.ajax.post(P.url, P.postData, handler);
},

ExtendUserSearchInput : function()
{
	if (!window.SonetTCJsUtils)
		return;
	var _this = this;
	if (!SonetTCJsUtils.EC__GetRealPos)
		SonetTCJsUtils.EC__GetRealPos = SonetTCJsUtils.GetRealPos;

	SonetTCJsUtils.GetRealPos = function(el)
	{
		var res = SonetTCJsUtils.EC__GetRealPos(el);
		if (_this.oSuperposeDialog && _this.oSuperposeDialog.bShow)
		{
			scrollTop = _this.oSuperposeDialog.oCont.scrollTop;
			res.top = bxInt(res.top) - scrollTop;
			res.bottom = bxInt(res.bottom) - scrollTop;
		}
		return res;
	}
},

ParseLocation : function(str, bGetMRParams)
{
	var res = {mrid : false, mrevid : false, str : str};
	if (str.length > 5 && str.substr(0, 5) == 'ECMR_')
	{
		var ar_ = str.split('_');
		if (ar_.length >= 2)
		{
			if (!isNaN(parseInt(ar_[1])) && parseInt(ar_[1]) > 0)
				res.mrid = parseInt(ar_[1]);
			if (!isNaN(parseInt(ar_[2])) && parseInt(ar_[2]) > 0)
				res.mrevid = parseInt(ar_[2]);
		}
	}

	if (res.mrid && bGetMRParams === true)
	{
		for (var i = 0, l = this.meetingRooms.length; i < l; i++)
		{
			if (this.meetingRooms[i].ID == res.mrid)
			{
				res.mrind = i;
				res.MR = this.meetingRooms[i];
				break;
			}
		}
	}
	return res;
},

RunPlanner: function(params)
{
	if (!params)
		params = {};

	if (!window.ECPlanner)
		return BX.loadScript(this.arConfig.planner_js_src, BX.delegate(function(){this.RunPlanner(params);}, this));

	if (!this.Planner)
		this.Planner = new ECPlanner(this);
	this.Planner.OpenDialog(params);
},

OnResize: function()
{
	this.bJustRedraw = true;
	this.SetView({month: this.activeDate.month, year: this.activeDate.year});
	var _this = this;
	setTimeout(function(){_this.bJustRedraw = false;}, 500);
},

CreateStrut: function(width)
{
	return BX.create("IMG", {props: {src: '/bitrix/images/1.gif'}, style: {width: width + 'px', height: '1px'}});
},

CheckMouseInCont: function(pWnd, e, d)
{
	var
		pos = BX.pos(pWnd),
		wndSize = BX.GetWindowScrollPos(),
		x = e.clientX + wndSize.scrollLeft,
		y = e.clientY + wndSize.scrollTop;

	if (typeof d == 'undefined')
		d = 0;

	return (x >= pos.left - d && x <= pos.right + d && y <= pos.bottom + d && y >= pos.top - d);
},

SaveConnections: function(Calback)
{
	var connections = [], i, l = this.arConnections.length, con;
	for (i = 0; i < l; i++)
	{
		con = this.arConnections[i];
		connections.push({
			id: con.id || 0,
			name: con.name,
			link: con.link,
			user_name: con.user_name,
			pass: typeof con.pass == 'undefined' ? 'bxec_not_modify_pass' : con.pass,
			del: con.del ? 'Y' : 'N',
			del_calendars: con.pDelCalendars.checked ? 'Y' : 'N'
		});
	}
	var postData = this.GetPostData('connections_edit', {connections : connections});

	var _this = this;
	this.Request({
		postData: postData,
		handler: function(result)
		{
			setTimeout(function(){
				if (Calback && typeof Calback == 'function')
					Calback(true);

				if (_this.section_id === false)
					_this.UpdateSectionId();
			}, 100);
		}
	});
	return true;
},

IsDavCalendar: function(id)
{
	return this.oCalendars[id] && (this.oCalendars[id].IS_EXCHANGE || this.oCalendars[id].CALDAV_CON);
},

SyncExchange: function()
{
	var _this = this;
	window._bx_result_sync = '';
	this.Request({
		postData: this.GetPostData('exchange_sync'),
		handler: function(result)
		{
			setTimeout(function(){
				if (window._bx_result_sync === true)
					window.location = window.location;
				else if (window._bx_result_sync === false)
					alert(EC_MESS.ExchNoSync);
			}, 100);
		}
	});
}
};

window.clone = function(obj, bCopyObj)
{
	var _obj = {};
	if (bCopyObj !== false)
		bCopyObj = true;
	for(i in obj)
	{
		if (typeof obj[i] == 'object' && bCopyObj)
			_obj[i] = window.clone(obj[i], bCopyObj);
		else
			_obj[i] = obj[i];
	}
	return _obj;
}

window.deleteFromArray =function(ar, ind)
{
	return ar.slice(0, ind).concat(ar.slice(ind + 1));
}

window.bxInt = function(x)
{
	return parseInt(x, 10);
}

window.bxIntEx = function(x)
{
	x = parseInt(x, 10);
	if (isNaN(x)) x = 0;
	return x;
}

window.bxSpCh = function(str)
{
	if (!str)
		return '';
	str = str.replace(/script_>/g, 'script>');
	str = str.replace(/&/g, '&amp;');
	str = str.replace(/"/g, '&quot;');
	str = str.replace(/</g, '&lt;');
	str = str.replace(/>/g, '&gt;');
	return str;
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

window.EnterAndNotTextArea = function(e, id)
{
	if(e.keyCode == 13)
	{
		var targ = e.target || e.srcElement;
		if (targ && targ.nodeName && targ.nodeName.toLowerCase() != 'textarea' && targ.id.indexOf(id) == -1)
		{
			BX.PreventDefault(e);
			return true;
		}
	}
	return false;
}

function bxGetDate(str, getObject, getBoth, bEndOfTheDay)
{
	if (!bxGetDate.prototype.fRes) // Do it once
	{
		var fRE = new RegExp('(\\w+)[^\\w](\\w+)[^\\w](\\w+)', 'ig');
		fRE.lastIndex = 0;
		bxGetDate.prototype.fRes = fRE.exec(window.BX_DATE_FORMAT);
	}

	var dRE = new RegExp('(\\d+)[^\\d](\\d+)[^\\d](\\d+)(?:\\s*(\\d{1,2}):(\\d{1,2})(?::\\d{1,2})?)?', 'ig');
	dRE.lastIndex = 0;

	var
		dRes = dRE.exec(str),
		fRes = bxGetDate.prototype.fRes;

	if (!fRes || !dRes || fRes.length > dRes.length)
		return false;

	var
		d, m, y, oDate,
		ho = bxInt(dRes[4]),
		mi = bxInt(dRes[5]) || 0,
		bTime;

	for (var i = 1, l = fRes.length; i < l; i++)
	{
		switch(fRes[i].toLowerCase())
		{
			case 'dd':
				d = dRes[i];
				break;
			case 'mm':
				m = dRes[i];
				break;
			case 'yyyy':
				y = dRes[i];
				break;
		}
	}

	if (isNaN(ho))
	{
		ho = bEndOfTheDay ? 23 : 0;
		mi = bEndOfTheDay ? 59 : 0;
		bTime = false;
	}
	oDate = (getObject || getBoth) ? new Date(y, m - 1, d, ho, mi) : false;
	if (!getObject)
	{
		oDate = {date: d, month: m, year: y, oDate: oDate};
		if (bTime !== false)
			bTime = ho.toString().length > 0 && mi.toString().length > 0;
		oDate.bTime = bTime;
		if (oDate.bTime)
		{
			oDate.hour = ho;
			oDate.min = mi;
		}
	}
	return oDate;
}

window.bxGetDate_h = function(str)
{
	return bxGetDate(str, false, true);
}

window.bxFormatDate = function(d, m, y)
{
	var str = window.BX_DATE_FORMAT;
	d = zeroInt(d);
	m = zeroInt(m);
	str = str.replace(/DD/ig, d);
	str = str.replace(/MM/ig, m);
	str = str.replace(/M/ig, m);
	str = str.replace(/YY(YY)?/ig, y);
	return str;
}

window.bxGetPixel = function(bFlip)
{
	var q = BX.browser.IsIE() || BX.browser.IsOpera();
	if (bFlip)
		q = !q;
	return q ? 0 : 1;
}

window.zeroInt = function(x)
{
	x = bxInt(x);
	if (isNaN(x))
		x = 0;
	return x < 10 ? '0' + x.toString() : x.toString();
}

window.DenyDragEx = function(pEl)
{
	pEl.style.MozUserSelect = 'none';
	pEl.ondrag = BX.False;
	pEl.ondragstart = BX.False;
	pEl.onselectstart = BX.False;
}
