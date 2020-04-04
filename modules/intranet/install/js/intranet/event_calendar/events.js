JCEC.prototype.LoadEvents_ex = function(m, y)
{
	var loacalCount = ++this.LoadEventsCount;
	var _this = this;
	setTimeout(function()
	{
		if (_this.LoadEventsCount > loacalCount)
			return;
		_this.LoadEvents(m, y);
		_this.LoadEventsCount = 0;
	}, 600);
}

JCEC.prototype.LoadEvents = function(m, y, P)
{
	var usedCalendars = [], i, _this = this, hidCals = [];
	for (i in this.oActiveCalendars)
	{
		i = bxInt(i);
		if (i > 0 && !isNaN(i))
		{
			if (this.oActiveCalendars[i])
				usedCalendars.push(i);
			else
				hidCals.push(i);
		}
	}
	var postData = this.GetPostData('load_events', {month: parseInt(m,10) + 1, year: y, usecl: 'Y', cl: usedCalendars, hcl: hidCals});
	var loadReqCount = ++this.loadReqCount;

	this.Request({
		postData: postData,
		errorText: EC_MESS.LoadEventsErr,
		handler: function(result)
		{
			return window._bx_ar_events ? _this.HandleLoadedEvents(window._bx_ar_events, m, y, loadReqCount, P) : false;
		}
	});
}

JCEC.prototype.ReloadEvents = function()
{
	this.arLoadedEventsId = {};
	this.arLoadedParentId = {};
	this.arLoadedMonth = {};
	this.arEvents = [];
	this.LoadEvents_ex(this.activeDate.month, this.activeDate.year);
}

JCEC.prototype.HandleLoadedEvents = function(arEvents, m, y, loadReqCount, P)
{
	if (this.loadReqCount > loadReqCount)
		return;
	this.loadReqCount = 0;
	var i, l, E, sid;

	for (i = 0, l = arEvents.length; i < l; i++)
	{
		E = arEvents[i];
		sid = this.GetEventSmartId(E);
		if (!E.ID || this.arLoadedEventsId[sid])
			continue;
		this.arEvents.push(E);
		this.arLoadedEventsId[sid] = true;
		if (E.HOST && E.HOST.parentId && !E.bSuperposed)
			this.arLoadedParentId[E.HOST.parentId] = true;
	}
	this.arLoadedMonth[m + '.' + y] = true;
	if (!P)
		P = {};
	if (isNaN(bxInt(P.month)))
		P.month = m;
	if (isNaN(bxInt(P.year)))
		P.year = y;
	this.SetView(P);
}

// BUILDING MONTH
JCEC.prototype.BuildEventHolder = function()
{
	if (this.EventHolderCont)
		this.EventHolderCont = null;

	this.EventHolderCont = this.DaysGridCont.appendChild(BX.create('DIV', {props: {className : 'bxec-event-holder'}}));

	var _this = this;
	var c = this.oDaysGridTable.rows[0].cells[0];
	setTimeout(function()
	{
		_this.arCellCoords = {};
		for (var d = 0; d < 7; d ++)
		{
			_this.arCellCoords[d] = {
				left: bxInt(_this.oDaysGridTable.rows[0].cells[d].offsetLeft),
				width: bxInt(_this.oDaysGridTable.rows[0].cells[d].offsetWidth) + bxGetPixel(true)
			};
			if (d / 2 == Math.round(d / 2))
				_this.arCellCoords[d].width += bxGetPixel();
		}
		_this.dayCellHeight = parseInt(c.offsetHeight);
		_this.dayCellWidth = parseInt(c.offsetWidth);

		_this.DisplayEventsMonth();
	},10);
}

JCEC.prototype.DisplayEventsMonth = function(bRefresh)
{
	var i, l;
	if (bRefresh || this.bJustRedraw) // Redisplay all events
	{
		BX.cleanNode(this.EventHolderCont);
		for (i = 0, l = this.activeDateObjDays.length; i < l; i++)
			this.activeDateObjDays[i].arEvents = {begining : [], all : []};
	}
	else
	{
		this.activeFirst = this.activeDateDays[0].getTime();
		this.activeLast = this.activeDateDays[this.activeDateDays.length - 1].getTime();
	}

	for (i = 0, l = this.arEvents.length; i < l; i++)
		if (this.arEvents[i])
			this.HandleEventMonth(this.arEvents[i], i);

	this.RefreshEventsOnWeeks([0, 1, 2, 3, 4, 5]);
}

JCEC.prototype.HandleEventMonth = function(el, ind, arPrehandle)
{
	var d_from, d_to, event_length, _d_from, _d_to;
	this.arLoadedEventsId[this.GetEventSmartId(el)] = true;

	el = this.HandleEventCommon(el, ind);
	if (!el)
		return;
	el.oParts = [];
	el.oWeeks = [];

	if (!arPrehandle)
	{
		d_from = bxGetDate(el.DATE_FROM, false, true);
		d_from = {date: d_from.date, month: d_from.month - 1, year: d_from.year};
		d_to = bxGetDate(el.DATE_TO, false, true);
		d_to = {date: d_to.date, month: d_to.month - 1, year: d_to.year};
		_d_from = new Date(d_from.year, d_from.month, d_from.date).getTime();
		_d_to = new Date(d_to.year, d_to.month, d_to.date).getTime();
	}
	else
	{
		d_from = arPrehandle.d_from;
		d_to = arPrehandle.d_to;
		_d_from = arPrehandle._d_from;
		_d_to = arPrehandle._d_to;
	}

	if (_d_from > _d_to || _d_to < this.activeFirst || _d_from > this.activeLast)
		return;
	var arInit = {real_from: d_from, real_to: d_to, from: _d_from, to: _d_to, real_from_t: _d_from, real_to_t: _d_to};
	if (_d_from < this.activeFirst && _d_to < this.activeLast) // event started earlier but ends in the active period
		arInit.from = this.activeFirst;
	else if (_d_from > this.activeFirst && _d_to > this.activeLast) // The event began in the active period, but will end in the future
		arInit.to = this.activeLast;
	else if (_d_from < this.activeFirst && _d_to > this.activeLast) // Event started earlier and ends later
	{
		arInit.from = this.activeFirst;
		arInit.to = this.activeLast;
	}

	el.display = true;
	this.DisplayEvent_M(arInit, el);

	if (el.STATUS == 'Q')
		this.BlinkEvent(el);
}

JCEC.prototype.HandleEventCommon = function(ev, ind)
{
	if (ev.IBLOCK_SECTION_ID && !this.oActiveCalendars[bxInt(ev.IBLOCK_SECTION_ID)])
		return false;

	if (ev.IS_MEETING && ev.bSuperposed &&
	((ev.HOST && ev.HOST.parentId && this.arLoadedEventsId[ev.HOST.parentId]) || this.arLoadedParentId[ev.ID]))
		return false;

	if (!ev.oParts)
		ev.oParts = [];
	if (!ev.oWeeks)
		ev.oWeeks = [];

	ev.ind = ind;
	ev = this.SetEventsColors(ev);
	//ev.displayColor = this.GetEventColor(ev);
	return ev;
}

JCEC.prototype.DisplayEvent_M = function(arInit, oEvent)
{
	var
		_day, _date, j, n,
		arEvParams = {partDaysCount: 0},
		bEventStart = false,
		bEventEnd = false;

	for (j = 0, n = this.activeDateDays.length; j < n; j++)
	{
		_date = this.activeDateDays[j];
		_day = this.convertDayIndex(_date.getDay());
		if (_date.getTime() == arInit.from)
		{
			bEventStart = true;
			arEvParams = {left: this.arCellCoords[_day].left + 1, arInit: arInit, dayIndex: j, partDaysCount: 0};
		}
		arEvParams.partDaysCount++;
		if (!bEventStart)
			continue;

		this.activeDateObjDays[j].arEvents.all.push({oEvent: oEvent, partInd: oEvent.oParts.length, daysCount: arEvParams.partDaysCount});
		if (_day == 6)
		{
			bEventEnd = _date.getTime() == arInit.to;
			arEvParams.width = this.arCellCoords[_day].left + this.arCellCoords[_day].width - arEvParams.left - 3;
			arEvParams.bEnd = bEventEnd && arInit.to == arInit.real_to_t;
			this.BuildEventDiv(arEvParams, oEvent);
			if (bEventEnd)
				break;
		}

		if (!bEventEnd && _day == 0 && _date.getTime() != arInit.from)
			arEvParams = {left: this.arCellCoords[0].left + 1, arInit: arInit, dayIndex: j, partDaysCount: 1};

		if (_date.getTime() == arInit.to)
		{
			bEventEnd = true;
			arEvParams.width = this.arCellCoords[_day].left + this.arCellCoords[_day].width - arEvParams.left - 3;
			arEvParams.bEnd = true;
			this.BuildEventDiv(arEvParams, oEvent);
			break;
		}
	}
}

JCEC.prototype.BuildEventDiv = function(arAtr, oEvent)
{
	var oDiv, d1, d2, t, r, c;
	this.activeDateObjDays[arAtr.dayIndex].arEvents.begining.push({oEvent: oEvent, partInd: oEvent.oParts.length, daysCount: arAtr.partDaysCount});

	var cn = 'bxec-event';
	if (oEvent.bDark)
		cn += ' bxec-dark';

	oDiv = BX.create('DIV', {props: {className : cn}, style: {left: arAtr.left + 'px', width: bxInt(arAtr.width) + 'px', display: 'none', backgroundColor: oEvent.displayColor}});

	t = oDiv.appendChild(BX.create('TABLE'));
	r = t.insertRow(-1);

	var _this = this;
	if (oEvent.oParts.length > 0 || arAtr.arInit.real_from_t < arAtr.arInit.from)
	{
		c = r.insertCell(-1);
		c.innerHTML = '<img class="bxec-iconkit" src="/bitrix/images/1.gif">';
		c.className = 'bxec-event-ar-l';
	}
	else
	{
		var ddCell = r.insertCell(-1);
		ddCell.innerHTML = '<img class="bxec-iconkit" src="/bitrix/images/1.gif">';
		ddCell.className = 'bxec-event-dd-dot';
	}

	 var
		bEnc = (oEvent.HOST || (oEvent.GUESTS && oEvent.GUESTS.length > 0)),
		encIcon = bEnc ? '<img class="bxec-iconkit bxec-enc-icon" src="/bitrix/images/1.gif" align="top">' : '',
		bStatQ = bEnc && oEvent.STATUS == 'Q',
		statQ = bStatQ ? '<b title="' + EC_MESS.NotConfirmed + '" class="bxec-stat-q">?</b>' : '',
		titleCell = r.insertCell(-1);

	titleCell.innerHTML = '<div class="bxec-event-title"><nobr' + this.GetEventLabelStyle(oEvent) + '>' + encIcon + statQ + oEvent.NAME + '</nobr></div>';

	this.BuildEventActions({cont: titleCell, oEvent: oEvent, evCont: oDiv});
	c = r.insertCell(-1);
	c.innerHTML = '<img class="bxec-iconkit" src="/bitrix/images/1.gif">';
	c.className = arAtr.bEnd ? 'bxec-event-resize' : 'bxec-event-ar-r';

	oDiv.onmouseover = function() {_this.HighlightEvent_M(oEvent, this);};
	oDiv.onmouseout = function() {_this.HighlightEvent_M(oEvent, this, true);}
	oDiv.ondblclick = function() {_this.ShowViewEventDialog(oEvent);};

	oEvent.oWeeks.push({dayIndex: arAtr.dayIndex, bEnd: arAtr.bEnd});
	oEvent.oParts.push(oDiv);

	this.EventHolderCont.appendChild(oDiv);

	//append Hint
	this.AppendHint2Event(oEvent, titleCell.firstChild);
}

JCEC.prototype.GetEventLabelStyle = function(ev)
{
	var
		labelStyle = ''
		imp = ev.IMPORTANCE;

	if (imp && imp != 'normal')
		labelStyle = ' style="' + (imp == 'high' ? 'font-weight: bold;' : 'color: #535353;') + '"';
	return labelStyle;
}

JCEC.prototype.AppendHint2Event = function(oEvent, oDiv)
{
	var
		hintContent = '<b>' + oEvent.NAME + '</b><br>' + oEvent.DATE_FROM + ' - ' + oEvent.DATE_TO,
		desc_len = oEvent.DETAIL_TEXT.length,
		max_len = 350,
		cal = this.oCalendars[oEvent.IBLOCK_SECTION_ID]; // Add information about calendars

	if (cal && cal.NAME)
	{
		if (cal.SP_PARAMS && cal.SP_PARAMS.NAME)
			hintContent += '<br>[' + cal.SP_PARAMS.NAME + ' :: ' + cal.NAME + ']';
		else
			hintContent += '<br>[' + cal.NAME + ']';
	}

	if (desc_len > 0)
	{
		if (desc_len < max_len)
			hintContent += '<br>' + oEvent.DETAIL_TEXT;
		else
			hintContent += '<br>' + oEvent.DETAIL_TEXT.substr(0, max_len) + '...';
	}

	oEvent.hintContent = hintContent;
	setTimeout(function()
	{
		if (oDiv.offsetWidth > 0)
			new BX.CHintSimple({parent: oDiv, hint: hintContent});
	}, 200);
}

JCEC.prototype.HighlightEvent_M = function(oEvent, pEl, bUn)
{
	if (!oEvent || !oEvent.oParts || oEvent.oParts.length == 0)
		return;

	var i, l, f = bUn ? BX.removeClass : BX.addClass;

	for (i = 0, l = oEvent.oParts.length; i < l; i++)
		f(oEvent.oParts[i], 'bxec-event-over');

	f(pEl, 'bxec-event-over');

	if (oEvent.pMoreDivs)
		for (i = 0, l = oEvent.pMoreDivs.length; i < l; i++)
			f(oEvent.pMoreDivs[i], 'bxec-event-over');
}

JCEC.prototype.GetEventWeeks = function(oEvent)
{
	var dind, j, arWeeks = [], i, l;
	for (i = 0, l = oEvent.oParts.length; i < l; i++)
	{
		dind = oEvent.oWeeks[i].dayIndex;
		for (j = 0; j < 6; j++)
		{
			if (dind >= j * 7 && dind < (j + 1) * 7)
			{
				arWeeks.push(j);
				break;
			}
		}
	}
	return arWeeks;
}

// ####################################################################################

JCEC.prototype.BuildWeekEventHolder = function()
{
	if (this._bBETimeOut)
		clearTimeout(this._bBETimeOut);

	var _this = this;

	this._bBETimeOut = setTimeout(
		function()
		{
			var Tab = _this.Tabs[_this.activeTabId || _this.startTabId];
			// Days title event holder;
			if (!Tab.pEventHolder)
				Tab.pEventHolder = Tab.pBodyCont.rows[0].cells[0].firstChild;
			else
				BX.cleanNode(Tab.pEventHolder);

			if (_this.bJustRedraw)
				_this.RelBuildEvents(Tab.id);
			else
				_this.DisplayWeekEvents(Tab);
		},
		50
	);

	return;

	var
		_this = this,
		Tab = this.Tabs[this.activeTabId || this.startTabId];
	// Days title event holder;
	if (!Tab.pEventHolder)
		Tab.pEventHolder = Tab.pBodyCont.rows[0].cells[0].firstChild;
	else
		BX.cleanNode(Tab.pEventHolder);

	if (this.bJustRedraw)
		this.RelBuildEvents(Tab.id);
	else
		setTimeout(function(){_this.DisplayWeekEvents(Tab);},10);
}

JCEC.prototype.DisplayWeekEvents = function(Tab)
{
	for (var i = 0, l = this.arEvents.length; i < l; i++)
	{
		if (this.arEvents[i])
			this.HandleEventWeek({Tab : Tab, Event: this.arEvents[i], ind: i});
	}

	var _this = this;

	setTimeout(function()
	{
		_this.RefreshEventsInDayT(Tab);
		_this.ArrangeEventsInTL(Tab);
	}, 50);
}

JCEC.prototype.RelBuildEvents = function(tabId)
{
	var
		Tab = this.Tabs[tabId],
		cont = Tab.pTimelineCont,
		bStop = false,
		node, i, l;

	for (i = 0; i < Tab.daysCount; i++) // Clean days params
	{
		oDay = Tab.arDays[i];
		oDay.TLine = {};
		oDay.Events = {begining: [], hidden: [], all: []};
		oDay.EventsCount = 0;
	}

	l = cont.childNodes.length;
	i = 0;
	while (i < l)
	{
		node = cont.childNodes[i];
		if (node.className.toString().indexOf('bxec-tl-event') == -1)
		{
			i++;
			continue;
		}
		cont.removeChild(node);
		l = cont.childNodes.length;
	}
	this.DisplayWeekEvents(Tab);
}

JCEC.prototype.HandleEventWeek = function(P)
{
	var ev = this.HandleEventCommon(P.Event, P.ind);
	if (!ev)
		return;

	if (!ev.oDaysT)
		ev.oDaysT = {};
	if (!ev.oTLParts)
		ev.oTLParts = {};

	ev.oTLParts[P.Tab.id] = [];

	var
		d_from = bxGetDate(ev.DATE_FROM, false, true),
		d_to = bxGetDate(ev.DATE_TO, false, true, true),
		_d_from = d_from.oDate.getTime(),
		_d_to = d_to.oDate.getTime();

	if (_d_from > _d_to || _d_to < P.Tab.activeFirst || _d_from > P.Tab.activeLast)
		return;

	var arInit = {
		real_from: d_from,
		real_to: d_to,
		from: _d_from,
		to: _d_to,
		real_from_t: _d_from,
		real_to_t: _d_to
	};

	if (_d_from < P.Tab.activeFirst && _d_to <= P.Tab.activeLast) // event started earlier but ends in the active period
	{
		arInit.from = P.Tab.activeFirst;
	}
	else if (_d_from >= P.Tab.activeFirst && _d_to > P.Tab.activeLast) // The event began in the active period, but will end in the future
	{
		arInit.to = P.Tab.activeLast;
	}
	else if (_d_from < P.Tab.activeFirst && _d_to > P.Tab.activeLast) // Event started earlier and ends later
	{
		arInit.from = P.Tab.activeFirst;
		arInit.to = P.Tab.activeLast;
	}
	ev.display = true;

	if(!d_from.bTime && !d_to.bTime) // Display event on the "daysT" sector
		this.DisplayEvent_DT(arInit, ev, P.Tab);
	else  // Display event on the TIMELINE
		this.DisplayEvent_TL(arInit, ev, P.Tab);

	if (ev.STATUS == 'Q')
		this.BlinkEvent(ev);
}

JCEC.prototype.DisplayEvent_DT = function(arInit, oEvent, Tab)
{
	var
		_this = this,
		bEventStart = false,
		bReadOnly = oEvent.bSuperposed || this.bReadOnly,
		day_from = this.convertDayIndex(new Date(arInit.from).getDay()),
		day_to = this.convertDayIndex(new Date(arInit.to).getDay()),
		dWidth = 100,
		_event = {oEvent : oEvent, daysCount: day_to - day_from + 1},
		startDay,
		endDay,
		i, oDay, day, date, ts, left;

	for (var i = 0; i < Tab.daysCount; i++)
	{
		oDay = Tab.arDays[i];
		if (oDay.day == day_from)
		{
			startDay = oDay;
			bEventStart = true;
			oDay.Events.begining.push(_event);
		}
		if (!bEventStart)
			continue;
		oDay.Events.all.push(_event);
		oDay.EventsCount++;
		if (oDay.day == day_to)
		{
			endDay = oDay;
			break;
		}
	}


	var
		left = bxInt(startDay.pWnd.offsetLeft) + 2 - bxGetPixel(),
		right = bxInt(endDay.pWnd.offsetLeft) + bxInt(endDay.pWnd.offsetWidth),
		width = right - left - 1,
		dW = (width - 40 - (bReadOnly ? 40 : 80)) + 'px';
		// Build div
		oDiv = BX.create('DIV', {props: {className : 'bxec-event'}, style: {left: left.toString()+ 'px', width: width.toString() + 'px', backgroundColor: oEvent.displayColor}}),
		t = oDiv.appendChild(BX.create('TABLE')),
		r = t.insertRow(-1);
	oEvent.oDaysT[Tab.id] = oDiv;

	if (arInit.real_from_t < arInit.from)
	{
		c = r.insertCell(-1);
		c.innerHTML = '<img class="bxec-iconkit" src="/bitrix/images/1.gif">';
		c.className = 'bxec-event-ar-l';
	}
	else
	{
		var ddCell = r.insertCell(-1);
		ddCell.innerHTML = '<img class="bxec-iconkit" src="/bitrix/images/1.gif">';
		ddCell.className = 'bxec-event-dd-dot';
	}

	 var
		bEnc = (oEvent.HOST || (oEvent.GUESTS && oEvent.GUESTS.length > 0)),
		bStatQ = bEnc && oEvent.STATUS == 'Q',
		statQ = bStatQ ? '<b title="' + EC_MESS.NotConfirmed + '" class="bxec-stat-q">?</b>' : '',
		encIcon = bEnc ? '<img class="bxec-iconkit bxec-enc-icon" src="/bitrix/images/1.gif" align="top">' : '',
		titleCell = r.insertCell(-1);
	titleCell.innerHTML = '<div class="bxec-event-title"><nobr>' + encIcon + statQ + oEvent.NAME + '</nobr></div>';

	this.BuildEventActions({cont: titleCell, oEvent: oEvent, evCont: oDiv});

	c = r.insertCell(-1);
	c.innerHTML = '<img class="bxec-iconkit" src="/bitrix/images/1.gif">';
	c.className = (arInit.real_to_t > arInit.to) ? 'bxec-event-ar-r' : 'bxec-event-resize';

	oDiv.onmouseover = function() {_this.HighlightEvent_DT(this);};
	oDiv.onmouseout = function() {_this.HighlightEvent_DT(this, true);}
	oDiv.ondblclick = function() {_this.ShowViewEventDialog(oEvent);};

	Tab.pEventHolder.appendChild(oDiv);
	this.AppendHint2Event(oEvent, titleCell.firstChild);
}

JCEC.prototype.DisplayEvent_TL = function(arInit, oEvent, Tab)
{
	var
		_this = this,
		bEventStart = false,
		bEventEnd = false,
		nd_f = new Date(arInit.from),
		nd_t = new Date(arInit.to),
		day_from = this.convertDayIndex(nd_f.getDay()),
		day_to = this.convertDayIndex(nd_t.getDay()),
		h_from = nd_f.getHours() || 0,
		m_from = nd_f.getMinutes() || 0,
		h_to = nd_t.getHours(),
		m_to = nd_t.getMinutes(),
		dWidth = 100,
		_event = {oEvent : oEvent, daysCount: day_to - day_from + 1},
		startDay,
		endDay,
		i, oDay, day, date, ts, left;

	if (!nd_t)
	{
		h_to = 23;
		m_to = 59;
	}

	for (var i = 0; i < Tab.daysCount; i++)
	{
		oDay = Tab.arDays[i];
		if (oDay.day == day_from)
		{
			startDay = oDay;
			bEventStart = true;
		}
		if (!bEventStart)
			continue;
		if (oDay.day == day_to)
		{
			endDay = oDay;
			break;
		}
	}
	this._SetTimeEvent(startDay, h_from, m_from, {oEvent : oEvent, bStart: true, arInit: arInit});
	this._SetTimeEvent(endDay, h_to, m_to, {oEvent : oEvent, bStart: false, arInit: arInit});
}

JCEC.prototype._SetTimeEvent = function(oDay, h, m, oEv)
{
	if (!oDay.TLine)
		oDay.TLine = {};
	h = bxInt(h);
	m = bxInt(m);

	if (!oDay.TLine[h])
		oDay.TLine[h] = {};
	if (!oDay.TLine[h][m])
		oDay.TLine[h][m] = [];

	oDay.TLine[h][m].push(oEv);
}

JCEC.prototype.BuildEventActions = function(P)
{
	var
		_this = this,
		oEvent = P.oEvent,
		count = 1,
		oDiv = BX.create('DIV', {props:{className : 'bxec-event-actions'}}),
		oDiv_ = oDiv.appendChild(BX.create('DIV', {props: {className : P.bTimeline ? 'bxec-icon-cont-tl' : 'bxec-icon-cont'}}));

	var pView = oDiv_.appendChild(BX.create('IMG', {props: {className : 'bxec-iconkit bxec-ev-view-icon', src: "/bitrix/images/1.gif", title: EC_MESS.ViewEvent}}));
	pView.onclick = function(){_this.ShowViewEventDialog(oEvent);};

	if (!this.bReadOnly && !oEvent.bSuperposed)
	{
		var pEdit = oDiv_.appendChild(BX.create('IMG', {props: {className : 'bxec-iconkit bxec-ev-edit-icon', src: "/bitrix/images/1.gif", title: EC_MESS.EditEvent}}));
		pEdit.onclick = function(){_this.ShowEditEventDialog({oEvent: oEvent});};
		count++;

		// Add del button
		var pDel = oDiv_.appendChild(BX.create('IMG', {props: {className : 'bxec-iconkit bxec-ev-del-icon', src: "/bitrix/images/1.gif", title: oEvent.HOST ? EC_MESS.DelEncounter : EC_MESS.DelEvent}}));
		pDel.onclick = function(){_this.DeleteEvent(oEvent);};
		count++;
	}

	if (count < 3)
	{
		if (P.bTimeline)
		{
			oDiv_.style.width = '18px';
			oDiv_.style.height = (18 * count) + 'px';
		}
		else
		{
			oDiv_.style.height = '18px';
			oDiv_.style.width = (18 * count) + 'px';
			oDiv_.style.left = '-' + (18 * count) + 'px';
		}
	}

	P.cont.appendChild(oDiv);
}

JCEC.prototype.HighlightEvent_DT = function(pWnd, bHide)
{
	var f = bHide ? BX.removeClass : BX.addClass;
	f(pWnd, 'bxec-event-over');
}

JCEC.prototype.RefreshEventsInDayT = function(Tab)
{
	var
		slots = [],
		step = 0,
		max = 3,
		day, i, arEv, j, ev, arAll, dis, arHid, top;

	for(j = 0; j < max; j++)
		slots[j] = 0;

	for (i = 0; i < Tab.daysCount; i++)
	{
		day = Tab.arDays[i];
		arEv = day.Events.begining;
		n = arEv.length;
		arHid = [];
		if (n > 0)
		{
			arEv.sort(function(a, b){return b.daysCount - a.daysCount});
			eventloop:
			for(k = 0; k < n; k++)
			{
				ev = arEv[k];
				if (!ev)
					continue;

				if (!this.arEvents[ev.oEvent.ind])
				{
					day.Events.begining = arEv = deleteFromArray(arEv, k);
					ev = arEv[k];
					if (!ev)
						continue;
				}

				for(j = 0; j < max; j++)
				{
					if (slots[j] - step <= 0)
					{
						slots[j] = step + ev.daysCount;
						top = 21 + j * 18;
						ev.oEvent.oDaysT[Tab.id].style.top = (21 + j * 18).toString() + 'px';
						continue eventloop;
					}
				}
				arHid[ev.oEvent.ID] = true;
				day.Events.hidden.push(ev);
			}
		}
		// For all events in the day
		arAll = day.Events.all;
		for (var x = 0, f = arAll.length; x < f; x++)
		{
			ev = arAll[x];
			if (!ev || arHid[ev.oEvent.ID])
				continue;
			if (!this.arEvents[ev.oEvent.ind])
			{
				day.Events.all = arAll = deleteFromArray(arAll, x);
				ev = arAll[x];
				if (!ev)
					continue;
			}
			dis = ev.oEvent.oDaysT[Tab.id].style.display;
			if (dis && dis.toLowerCase() == 'none')
				day.Events.hidden.push(ev);
		}
		this.ShowMoreEventsSelectWeek(day, Tab.id);
		step++;
	}
}

JCEC.prototype.ShowMoreEventsSelectWeek = function(oDay, tabId)
{
	var
		_this = this,
		arEv = oDay.Events.hidden,
		l = arEv.length,
		arHidden = [],
		pMoreDiv = oDay.pMoreEvents.firstChild,
		i, el, p;

	if (l <= 0)
	{
		pMoreDiv.style.display = 'none';
		return;
	}

	for (i = 0; i < l; i++)
	{
		el = arEv[i];
		p = el.oEvent.oDaysT[tabId];
		p.style.display = "none"; // Hide event
		arHidden.push({pDiv: p, oEvent: el.oEvent});
	}

	pMoreDiv.style.display = 'block';
	pMoreDiv.innerHTML = EC_MESS.MoreEvents + ' (' + l + ' ' + EC_MESS.Item + ')';
	pMoreDiv.onmousedown = function(e){if(!e) e = window.event; BX.PreventDefault(e);};
	pMoreDiv.onclick = function(e){_this.ShowMoreEventsWin({Events: arHidden, id: 'day_t_' + oDay.day, pDay: oDay.pWnd, mode: 'day_t'});};
}

JCEC.prototype.ArrangeEventsInTL = function(Tab)
{
	try{ //
	var
		bStarted = false,
		h, m, e, pDiv,
		arProceed = {},
		procCnt = 0,
		procRows = 0,
		_row,
		RowSet,
		Rows,
		Row,
		bClosedAllRows, // All rows finished, start new row in rowset
		startedEvents = {},
		startedEventsCount = 0,
		arAll = [],
		Day, i, arEv, ev;

	for (i = 0; i < Tab.daysCount; i++) // For every day
	{
		Day = Tab.arDays[i];
		RowSet = [];

		if (startedEventsCount > 0)
		{
			if (!Day.TLine)
				Day.TLine = {};
			for (_e in startedEvents)
			{
				if (startedEvents[_e] && typeof startedEvents[_e] == 'object' && startedEvents[_e].oEvent)
				{
					if (!Day.TLine['0'])
						Day.TLine['0'] = {'0' : []};
					Day.TLine['0']['0'].push({oEvent : startedEvents[_e].oEvent, bStart: true, dontClose: false, arInit: startedEvents[_e].arInit});
				}
			}
		}
		if (!bStarted && !Day.TLine)
			continue;
		bClosedAllRows = true;

		if (Day.TLine) // some events starts or ends in this day
		{
			for (h = 0; h <= 23; h++) // hour loop
			{
				if (!Day.TLine[h] && h != 23)
					continue;
				for (m = 0; m < 60; m++) // minutes loop
				{
					arEv = Day.TLine[h] && Day.TLine[h][m] ? Day.TLine[h][m] : false;
					if (h == 23 && m == 59)
					{
						if (arEv === false)
							arEv = [];
						for (_e in startedEvents)
						{
							if (startedEvents[_e] && typeof startedEvents[_e] == 'object' && startedEvents[_e].oEvent)
								arEv.push({oEvent : startedEvents[_e].oEvent, bStart: false, dontClose: true, arInit: startedEvents[_e].arInit});
						}
					}

					if (!arEv)
						continue;

					// TODO: Sort by event length
					for (e = 0, el = arEv.length; e < el; e++) // events in current moment
					{
						ev = arEv[e];
						if (ev.bStart) // Event START
						{
							startedEvents[ev.oEvent.ID] = ev;
							startedEventsCount++;
							if (bClosedAllRows)
								RowSet.push([]);
							Rows = RowSet[RowSet.length - 1];
							freeRowId = false;
							bClosedAllRows = false;
							if (Rows.length > 1)
							{
								for(r = 0, rl = Rows.length; r < rl; r++)
								{
									Row = Rows[r];
									if (!Row.bFilled)
									{
										freeRowId = r;
										break;
									}
								}
							}
							_row = {
								bFilled: true,
								evId: ev.oEvent.ID,
								h_f: h,
								m_f: m
							};
							if (freeRowId !== false) // we have free row
							{
								_row.arEvents = Rows[freeRowId].arEvents;
								Rows[freeRowId] = _row;
							}
							else // push new row
							{
								Rows.push(_row);
							}
						}
						else // Event END
						{
							bClosedAllRows = true;
							if (!ev.dontClose)
							{
								startedEvents[ev.oEvent.ID] = false;
								startedEventsCount--;
							}

							for(r = 0, rl = Rows.length; r < rl; r++)
							{
								Row = Rows[r];
								if (Row.bFilled && Row.evId == ev.oEvent.ID)
								{
									Row.bFilled = false;
									pDiv = this.BuildEventDiv_TL(
										{
											Tab: Tab,
											dayInd: i,
											from: {h: Row.h_f, m: Row.m_f},
											to: {h: h, m: m},
											oEvent: ev.oEvent,
											arInit: ev.arInit
										}
									); // Build div
									if (!Row.arEvents)
										Row.arEvents = [pDiv];
									else
										Row.arEvents.push(pDiv);
								}
								if (Row.bFilled && bClosedAllRows)
									bClosedAllRows = false;
							}
						}
					}
				}
			}
		}

		var
			cell = Tab.pTimelineTable.rows[0].cells[i + 1],
			arRS, rs, rsl, rowsCount, rowWidth, r, rl, rw,
			sWidth = cell.offsetWidth - 15;

		for (rs = 0, rsl = RowSet.length; rs < rsl; rs++) // For each rowset
		{
			arRS = RowSet[rs];
			rowsCount = arRS.length;
			rowWidth = Math.round((sWidth - rowsCount) / rowsCount);
			for (r = 0; r < arRS.length; r++) // For each row
			{
				Row = arRS[r];
				if (r == 0) // first row
				{
					rw = rowWidth;
					leftDrift = bxInt(Row.arEvents[0].style.left);
					rl = false;
				}
				else
				{
					leftDrift += rowWidth + 1;
					rl = leftDrift;
					if (r == arRS.length- 1) // last row
						rw = sWidth - (rowWidth + 1) * (arRS.length- 1) - 1;
					else
						rw = rowWidth;
				}
				for (e = 0; e < Row.arEvents.length; e++) // For each event
				{
					pEv = Row.arEvents[e];
					pEv.style.width = rw + 'px';
					if (rl !== false)
						pEv.style.left = rl + 'px';
				}
			}
		}
	}
	}catch(e){}
}

JCEC.prototype.BuildEventDiv_TL = function(P)
{
	var
		_this = this,
		oEvent = P.oEvent,
		m_f = P.from.m,
		m_t = P.to.m,
		rowInd_f = Math.floor((P.from.h + m_f / 60) * 2),
		rowInd_t = Math.floor((P.to.h + m_t / 60) * 2),
		cellStart = P.Tab.pTimelineTable.rows[rowInd_f].cells[this.__ConvertCellIndex(rowInd_f, P.dayInd + 1, true)],
		cellEnd = P.Tab.pTimelineTable.rows[rowInd_t].cells[this.__ConvertCellIndex(rowInd_t, P.dayInd + 1, true)],
		top = bxInt(cellStart.offsetTop) + 1 + bxGetPixel(true),
		bottom = bxInt(cellEnd.offsetTop) - 1 - bxGetPixel(),
		left = bxInt(cellStart.offsetLeft) + 2 - bxGetPixel(),
		// Build div
		oDiv = BX.create('DIV', {
			props: {className : 'bxec-tl-event' + (oEvent.bDark ? ' bxec-dark' : '')},
			style: {left: left+ 'px', backgroundColor: oEvent.displayColor},
			events: {
				mouseover: function(e) {_this.HighlightEvent_TL(oEvent, this, false, P.Tab.id, e || window.event);},
				mouseout: function(e) {_this.HighlightEvent_TL(oEvent, this, true, P.Tab.id, e || window.event);},
				dblclick: function() {_this.ShowViewEventDialog(oEvent);}
			}
		});

	oEvent._originalWidth = false;
	oEvent._originalHeight = false;
	oEvent._eventViewed = false;
	oEvent._contentSpan = false;

	//this.BuildEventActions({cont: oDiv, oEvent: oEvent, evCont: oDiv, bTimeline: true});

	var
		rf = P.arInit.real_from,
		rt = P.arInit.real_to,
		bEnc = (oEvent.HOST || (oEvent.GUESTS && oEvent.GUESTS.length > 0)),
		bStatQ = bEnc && oEvent.STATUS == 'Q',
		statQ = bStatQ ? '<b title="' + EC_MESS.NotConfirmed + '" class="bxec-stat-q">?</b>' : '',
		encIcon = bEnc ? '<img class="bxec-iconkit bxec-enc-icon" src="/bitrix/images/1.gif" align="top">' : '',
		innerHTML = encIcon + statQ+ '<u ' + this.GetEventLabelStyle(oEvent) + '>' + oEvent.NAME + '</u><br />',
		t1 = zeroInt(rf.hour) + ':' + zeroInt(rf.min);

	if (isNaN(bxInt(rt.hour)))
	{
		rt.hour = 23;
		rt.min = 59;
	}

	var t2 = zeroInt(rt.hour) + ':' + zeroInt(rt.min);
	 // consider minutes
	if (m_f != 30 && m_f != 0)
		top += Math.round((m_f > 30 ? m_f - 30 : m_f) * 40 / 60) - 1;
	if (m_t != 30 && m_t != 0)
		bottom += Math.round((m_t > 30 ? m_t - 30 : m_t) * 40 / 60) + 2;
	var height = bottom - top;

	oDiv.style.top = top + 'px';
	oDiv.style.height = height + 'px';

	if (rf.year == rt.year && rf.month == rt.month && rf.date == rt.date) // during one day
		innerHTML += t1 + ' - ' + t2;
	else
		innerHTML += oEvent.DATE_FROM + ' - ' + oEvent.DATE_TO;

	var pCont = oDiv.appendChild(BX.create("DIV", {children: [BX.create("SPAN", {props: {className: 'bxec-cnt-sp'}, html: innerHTML})]}));

	this.BuildEventActions({cont: oDiv, oEvent: oEvent, evCont: oDiv, bTimeline: true});

	P.Tab.pTimelineCont.appendChild(oDiv);
	this.AppendHint2Event(oEvent, pCont);

	if (!oEvent.oTLParts[P.Tab.id])
		oEvent.oTLParts[P.Tab.id] = [];
	oEvent.oTLParts[P.Tab.id].push(oDiv);
	return oDiv;
}

JCEC.prototype.HighlightEvent_TL = function(oEvent, pWnd, bHide, tabId, e)
{
	var _this = this;

	if (!bHide && !oEvent._eventViewed)
	{
		if (this._highlightIntKeypWnd == pWnd && this._highlightInt)
			return;

		if (this._highlightInt)
			clearInterval(this._highlightInt);

		if (!oEvent._originalWidth)
			oEvent._originalWidth = parseInt(pWnd.style.width);

		if (!oEvent._originalHeight)
			oEvent._originalHeight = parseInt(pWnd.style.height);

		//if (!oEvent._contentSpan)
			oEvent._contentSpan = BX.findChild(pWnd, {className: 'bxec-cnt-sp'}, true);

		var
			d = 0,
			w1 = oEvent._originalWidth,
			w2 = parseInt(oEvent._contentSpan.offsetWidth) + 20,
			h1 = oEvent._originalHeight,
			h2 = parseInt(oEvent._contentSpan.offsetHeight) + 30;

		if (w2 <= 60)
			w2 = 60;

		if (h2 <= 55)
			h2 = 55;

		if (w2 - w1 > 0 || h2 - h1 > 0)
		{
			this._highlightIntKeypWnd = pWnd;
			this._highlightInt = setInterval(function(){
				var
					bWidth = (w2 - w1) <= 0,
					bHeight = (h2 - h1) <= 0;

				if (bWidth && bHeight)
				{
					oEvent._eventViewed = true;
					return clearInterval(_this._highlightInt);
				}

				d += 12;
				if (!bWidth)
				{
					w1 += d;
					if (w1 > w2)
						w1 = w2 + 2;
					pWnd.style.width = w1 + 'px';
				}

				if (!bHeight)
				{
					h1 += d;
					if (h1 > h2)
						h1 = h2 + 2;
					pWnd.style.height = h1 + 'px';
				}
			}, 5);
		}
	}
	else
	{
		if (this.CheckMouseInCont(pWnd, e, -2))
			return true;

		this._highlightIntKeypWnd == false;
		if (this._highlightInt)
			clearInterval(this._highlightInt);
		this._highlightInt = false;

		if (oEvent._originalWidth)
		{
			pWnd.style.width = oEvent._originalWidth + "px";
			oEvent._eventViewed = false;
		}
		if (oEvent._originalHeight)
		{
			pWnd.style.height = oEvent._originalHeight + "px";
			oEvent._eventViewed = false;
		}
	}

	var f = bHide ? BX.removeClass : BX.addClass;
	f(pWnd, 'bxec-event-over');

	if (oEvent.oTLParts && oEvent.oTLParts[tabId])
	{
		var arParts = oEvent.oTLParts[tabId], pl = arParts.length, p;
		for (p = 0; p < pl; p++)
			f(arParts[p], 'bxec-tl-ev-hlt');
	}
}

JCEC.prototype.DeleteEvent = function(oEvent)
{
	if (!oEvent || !oEvent.ID)
		return false;

	if (oEvent.IS_MEETING)
	{
		if ((oEvent.HOST && this.arConfig.userId == oEvent.HOST.id) || !oEvent.HOST) // owner
		{
			if (!confirm(EC_MESS.DelMeetingConfirm))
				return false;
		}
		else// guest
		{
			if (!confirm(EC_MESS.DelMeetingGuestConfirm))
				return false;
		}
	}
	else if (!confirm(EC_MESS.DelEventConfirm))
		return false;

	var postData = this.GetPostData('delete', {id : bxInt(oEvent.ID), name : oEvent.NAME, calendar : bxInt(oEvent.IBLOCK_SECTION_ID)});
	var _this = this;

	this.Request({
		postData: postData,
		errorText: EC_MESS.DelEventError,
		handler: function(result)
		{
			return window._bx_result ? _this.DeleteEventClientSide(oEvent) : false;
		}
	});
	return true;
}

JCEC.prototype.DeleteEventClientSide = function(oEvent, bHandlePeriodic)
{
	if (oEvent.PERIOD && bHandlePeriodic !== false) // Delete all examples of the periodic event
	{
		var E, i, l;
		for (i = 0, l = this.arEvents.length; i < l; i++)
			if (this.arEvents[i] && this.arEvents[i].ID == oEvent.ID && oEvent.ind != i)
				this.DeleteEventClientSide(this.arEvents[i], false);
	}

	this.ClearBlink(oEvent);
	var dind, j, arWeeks = [];
	for (var i = 0, l = oEvent.oParts.length; i < l; i++)
	{
		dind = oEvent.oWeeks[i].dayIndex;
		for (j = 0; j < 6; j++)
		{
			if (dind >= j * 7 && dind < (j + 1) * 7)
			{
				arWeeks.push(j);
				break;
			}
		}
		oEvent.oParts[i].parentNode.removeChild(oEvent.oParts[i]);
	}

	if (oEvent.oDaysT && oEvent.oDaysT[this.activeTabId])
		oEvent.oDaysT[this.activeTabId].parentNode.removeChild(oEvent.oDaysT[this.activeTabId]);

	if (oEvent.oTLParts && oEvent.oTLParts[this.activeTabId])
		for (i = 0, l = oEvent.oTLParts[this.activeTabId].length; i < l; i++)
			if (oEvent.oTLParts[this.activeTabId][i])
				oEvent.oTLParts[this.activeTabId][i].parentNode.removeChild(oEvent.oTLParts[this.activeTabId][i]);

	this.arLoadedEventsId[this.GetEventSmartId(oEvent)] = false;
	if (oEvent.HOST && oEvent.HOST.parentId)
		this.arLoadedParentId[oEvent.HOST.parentId] = false;
	this.arEvents[oEvent.ind] = null;

	if (bHandlePeriodic !== false)
	{
		this.SetTabNeedRefresh(this.activeTabId);
		switch (this.activeTabId)
		{
			case 'month':
				this.RefreshEventsOnWeeks(arWeeks);
				break;
			case 'week':
			case 'day':
				this.RelBuildEvents(this.activeTabId);
				break;
		}
	}
}

JCEC.prototype.SimpleSaveNewEvent = function(arParams)
{
	var Ob = this.oAddEventDialog;
	if (Ob.oName.value.length <= 0)
	{
		Ob.bHold = true;
		alert(EC_MESS.EventNameError);
		setTimeout(function(){Ob.bHold = false;}, 100);
		this.bAddEventDialogOver = true;
		return false;
	}

	var
		f = Ob.curDialogParams.from,
		t = Ob.curDialogParams.to,
		res = {
			name: Ob.oName.value,
			desc: Ob.oDesc.value,
			calendar: Ob.oCalendSelect.value,
			from: bxFormatDate(f.getDate(), f.getMonth() + 1, f.getFullYear()) + ' ' + Ob.curDialogParams.time_f,
			to: bxFormatDate(t.getDate(), t.getMonth() + 1, t.getFullYear()) + ' ' + Ob.curDialogParams.time_t
		};

	if (this.bUser)
	{
		res.private_event = this.oCalendars[res.calendar] && this.oCalendars[res.calendar].PRIVATE_STATUS == 'private';
		res.accessibility = Ob.oAccessibility.value;
	}

	this.SaveEvent(res);
	return true;
}

JCEC.prototype.ExtendedSaveEvent = function(Params)
{
	var
		Ob = this.oEditEventDialog,
		CE = Ob.currentEvent,
		_this = this, i,
		err = function(str){alert(str); this.bEditEventDialogOver = true; return false;};

	if (Ob.oName.value.length <= 0)
		return err(EC_MESS.EventNameError);

	var res = {name: Ob.oName.value, calendar: Ob.oCalendSelect.value};

	// Only for editing events
	if (CE.ID > 0 && CE.IBLOCK_SECTION_ID != res.calendar && (this.IsDavCalendar(CE.IBLOCK_SECTION_ID) || this.IsDavCalendar(res.calendar)))
	{
		res.bRecreate = true;
		res.oldCalendar = CE.IBLOCK_SECTION_ID;
	}

	// Get HTML Editor content
	res.desc = window.pLHEEvDesc.GetContent();

	var fd = bxGetDate(Ob.oFrom.value + ' ' + Ob.oFromTime.value);
	if (fd)
		res.from = bxFormatDate(fd.date, fd.month, fd.year) + (fd.bTime ? ' ' + zeroInt(fd.hour) + ':' + zeroInt(fd.min) : '');

	var td = bxGetDate(Ob.oTo.value + ' ' + Ob.oToTime.value);
	if (td)
		res.to = bxFormatDate(td.date, td.month, td.year) + (td.bTime ? ' ' + zeroInt(td.hour) + ':' + zeroInt(td.min) : '');

	res.guests = [];
	res.arGuests = [];

	if (this.arConfig.bSocNet && !this.IsDavCalendar(res.calendar))
	{
		// ***** MEETING *****
		if (Ob.GuestsLength && Ob.GuestsLength > 0)
		{
			for(i in Ob.Guests)
			{
				if (Ob.Guests[i] && typeof Ob.Guests[i] == 'object' && Ob.Guests[i].user)
				{
					res.guests.push(bxInt(i));
					res.arGuests.push(Ob.Guests[i].user)
				}
			}
		}

		res.isMeeting = res.guests.length > 0;
		if (!res.isMeeting && CE && CE.GUESTS)
		{
			for(i in CE.GUESTS)
				if (typeof CE.GUESTS[i] == 'object')
				{
					res.isMeeting = true;
					break;
				}
		}

		res.meetingText = (Ob.oMeetTextCont.style.display == 'block') ? Ob.oMeetText.value.toString() : '';
	}

	if (CE.HOST)
		res.host = CE.HOST;

	if (CE.ID)
		res.id = CE.ID;

	// Location
	res.loc_old = Ob.loc_old;
	res.loc_new = Ob.loc_new;
	res.loc_change = Ob.loc_change || (res.from != CE.DATE_FROM || res.to != CE.DATE_TO);

	if (!res.from)
		return err(EC_MESS.EventDiapStartError);

	if (!res.to)
	{
		var d = bxGetDate(res.from);
		if (d)
			res.to = bxFormatDate(d.date, d.month, d.year);
	}

	if (Ob.oRepeatSelect.value != 'none')
	{
		res.per_type = Ob.oRepeatSelect.value;
		res.per_count = Ob.oRepeatCount.value;

		if (Ob.bRepSetDiapFrom)
			res.per_from = res.from;
		else
			res.per_from = Ob.oRepeatDiapFrom;

		res.per_to = Ob.oRepeatDiapTo.value == EC_MESS.NoLimits ? 'no_limit' : Ob.oRepeatDiapTo.value;

		if (res.per_type == 'weekly')
		{
			var ar = [];
			for (i = 0; i < 7; i++)
				if (Ob.oRepeatWeekDaysCh[i].checked)
					ar.push(i);
			if (ar.length == 0)
				res.per_type = false;
			else
				res.per_week_days = ar.join(',');
		}
	}
	else if (!Ob.bNew && CE.PERIOD && CE.PERIOD.TYPE && CE.PERIOD.TYPE != 'NONE')
	{
		res.per_type = 'none';
	}

	// Check Meeting and Video Meeting rooms accessibility
	if (res.loc_new.length > 5 && res.loc_new.substr(0, 5) == 'ECMR_' && !Params.bLocationChecked)
	{
		var postData = this.GetPostData('check_mr_vr_accessability',
			{
				id : res.id || 0,
				from : res.from,
				to : res.to,
				location_new : res.loc_new || '',
				location_old : res.loc_old || '',
				per_type : res.per_type || '',
				guest : res.guests.length > 0 ? res.guests : [0]
			});

		this.Request({
			postData: postData,
			handler: function(result)
			{
				if (_this.section_id === false)
					_this.UpdateSectionId();

				if (window._bx_result === true)
				{
					Params.bLocationChecked = true;
					_this.ExtendedSaveEvent(Params);
				}
				else
				{
					alert(window._bx_result == 'reserved' ? EC_MESS.MRNotReservedErr : EC_MESS.MRReserveErr);
				}

				return true;
			}
		});
		return false;
	}

	// Reminder
	if (this.arConfig.bSocNet)
	{
		res.remind = Ob.oRemCheck.checked;
		res.remind_count = Ob.oRemCount.value || '';
		res.remind_count = res.remind_count.replace(/,/g, '.');
		res.remind_count = res.remind_count.replace(/[^\d|\.]/g, '');
		res.remind_type = Ob.oRemType.value;
	}
	// Other
	res.importance = Ob.oImportance.value;
	if (this.bUser)
	{
		res.accessibility = Ob.oAccessibility.value;
		res.private_event = Ob.oPrivate.checked;
	}

	if (!Ob.bNew)
	{
		res.id = CE.ID;
		if (CE.STATUS)
			res.status = CE.STATUS;
	}

	res.oEvent = CE;

	this.SaveEvent(res);

	if (Params.callback)
		Params.callback();
}

JCEC.prototype.AddEventClientSide = function(new_el, arParams)
{
	var calId = arParams.calendar || new_el.IBLOCK_SECTION_ID || 0;
	if (!calId && window._bx_def_calendar && window._bx_def_calendar.ID)
		calId = window._bx_def_calendar.ID;
	if (!calId)
		return;

	if (!this.oActiveCalendars[calId]) // Show calendar if user add calendar to 'hidden' calendar
		return this.ShowCalendar(this.oCalendars[calId], true);

	var Loc = '';
	if (new_el.LOC == 'bxec_error_reserved')
		alert(EC_MESS.MRNotReservedErr);
	else if (new_el.LOC == 'bxec_error_expire')
		alert(EC_MESS.MRNotExpireErr);
	else if (new_el.LOC == 'bxec_error')
		alert(EC_MESS.MRReserveErr);
	else
		Loc = new_el.LOC;

	var el = {
		ID : bxInt(new_el.ID),
		IBLOCK_ID : new_el.iblockId || this.iblockId,
		IBLOCK_SECTION_ID : calId,
		NAME : bxSpCh(arParams.name),
		DATE_FROM : arParams.from,
		DATE_TO : arParams.to,
		DETAIL_TEXT: arParams.desc,
		GUESTS: arParams.arGuests || [],
		REMIND: (arParams.remind && arParams.remind_count && arParams.remind_type) ? arParams.remind_count + '_' + arParams.remind_type : '',
		IMPORTANCE : arParams.importance || 'normal',
		LOCATION : Loc,
		IS_MEETING: arParams.isMeeting
	};

	if (arParams.isMeeting && new_el.arGuestConfirm && false)
	{
		var o;
		for (var i in arParams.arGuests)
		{
			o = arParams.arGuests[i];
			if (typeof o == 'object' && o.status && new_el.arGuestConfirm[o.id])
				arParams.arGuests[i].status = new_el.arGuestConfirm[o.id];
		}
	}

	if (this.bUser)
	{
		el.ACCESSIBILITY = arParams.accessibility || 'busy';
		el.PRIVATE = arParams.private_event || false;
	}

	var ind = this.arEvents.length;
	this.arEvents.push(el);
	//this.arLoadedEventsId[new_el.ID] = true;
	this.arLoadedEventsId[this.GetEventSmartId(new_el)] = true;
	if (el.HOST && el.HOST.parentId)
		this.arLoadedParentId[el.HOST.parentId] = true;

	this.SetTabNeedRefresh(this.activeTabId);
	switch (this.activeTabId)
	{
		case 'month':
			this.HandleEventMonth(el, ind);
			this.RefreshEventsOnWeeks(this.GetEventWeeks(el));
			break;
		case 'week':
		case 'day':
			this.DeSelectTime(this.activeTabId);
			this.RelBuildEvents(this.activeTabId);
			break;
	}
}

JCEC.prototype.ChandeEventClientSide = function(el, arParams)
{
	var Loc = '';
	if (el.LOC == 'bxec_error_reserved')
		alert(EC_MESS.MRNotReservedErr);
	else if (el.LOC == 'bxec_error_expire')
		alert(EC_MESS.MRNotExpireErr);
	else if (el.LOC == 'bxec_error')
		alert(EC_MESS.MRReserveErr);
	else
		Loc = el.LOC;

	var E = this.oEditEventDialog.currentEvent;
	E.NAME = bxSpCh(arParams.name);
	E.DETAIL_TEXT = arParams.desc;
	E.IBLOCK_SECTION_ID = bxInt(arParams.calendar);
	E.DATE_FROM = el.DATE_FROM;
	E.DATE_TO = el.DATE_TO;
	E.REMIND = (arParams.remind && arParams.remind_count && arParams.remind_type) ? arParams.remind_count + '_' + arParams.remind_type : '';
	E.ACCESSIBILITY = arParams.accessibility || 'busy';
	E.IMPORTANCE = arParams.importance || 'normal';
	E.PRIVATE = arParams.private_event || false;
	E.LOCATION = Loc;
	E.MEETING_TEXT = arParams.meetingText;
	E.GUESTS = arParams.arGuests || [];
	E.IS_MEETING = arParams.isMeeting;

	if (arParams.isMeeting && el.arGuestConfirm)
	{
		var o;
		for (var i in arParams.arGuests)
		{
			o = arParams.arGuests[i];
			if (typeof o == 'object' && o.status && el.arGuestConfirm[o.id])
				arParams.arGuests[i].status = el.arGuestConfirm[o.id];
		}
	}

	this.SetTabNeedRefresh(this.activeTabId);
	switch (this.activeTabId)
	{
		case 'month':
			this.DisplayEventsMonth(true);
			break;
		case 'week':
		case 'day':
			this.DeSelectTime(this.activeTabId);
			this.RelBuildEvents(this.activeTabId);
			break;
	}
}

JCEC.prototype.SaveEvent = function(arParams)
{
	var postData = this.GetPostData();
	if (arParams.id)
	{
		postData.action = 'edit';
		postData.id = arParams.id;
	}
	else
	{
		postData.action = 'add';
	}

	postData.name = arParams.name;
	postData.desc = arParams.desc || '';
	postData.from = arParams.from;
	postData.to = arParams.to;
	postData.calendar = arParams.calendar;

	postData.location_old = arParams.loc_old || '';
	postData.location_new = arParams.loc_new || '';
	postData.location_change = arParams.loc_change ? 'Y' : 'N';

	if (arParams.bRecreate)
	{
		postData.b_recreate = "Y";
		postData.old_calendar = arParams.oldCalendar;
	}

	if (arParams.per_type)
	{
		postData.per_type = arParams.per_type || '';
		postData.per_count = arParams.per_count || '';
		postData.per_from = arParams.per_from || '';
		postData.per_to = arParams.per_to || '';
		if (arParams.per_type == 'weekly')
			postData.per_week_days = arParams.per_week_days;
	}
	if (arParams.remind)
	{
		postData.rem = 'Y';
		postData.rem_type = arParams.remind_type;
		postData.rem_count = arParams.remind_count;
	}

	if (this.arConfig.bSocNet)
	{
		postData.is_meeting = arParams.isMeeting ? '1' : '0';
		if (arParams.isMeeting)
		{
			postData.meeting_text = arParams.meetingText || '';
			if (arParams.guests)
				postData.guest = arParams.guests.length > 0 ? arParams.guests : [0];
			if (arParams.host)
				postData.host = arParams.host.parentId;
			if (arParams.status)
				postData.status = arParams.status;
		}
	}

	// Other
	if (arParams.accessibility)
		postData.accessibility = arParams.accessibility;
	if (arParams.importance)
		postData.importance = arParams.importance;
	if (arParams.private_event)
		postData.private_event = arParams.private_event;

	var _this = this;
	this.Request({
		postData: postData,
		errorText: EC_MESS.EventSaveError,
		handler: function(result)
		{
			if (_this.section_id === false)
				_this.UpdateSectionId();

			if (window._bx_def_calendar)
				_this.SaveCalendarClientSide(window._bx_def_calendar);

			if (arParams.per_type)
				return _this.ReloadEvents();

			if (window._bx_new_event)
			{
				_this.AddEventClientSide(window._bx_new_event, arParams);
				if (arParams.bRecreate)
					_this.DeleteEventClientSide(arParams.oEvent);
			}
			else if(window._bx_existent_event)
				_this.ChandeEventClientSide(window._bx_existent_event, arParams);
			else
				return false;
			return true;
		}
	});
}

JCEC.prototype.ConfirmEvent = function(oEvent)
{
	var _this = this;
	this.Request({
		postData: this.GetPostData('confirm_event', {id : oEvent.ID}),
		handler: function(result){_this.ReloadEvents();return true;}
	});
}

JCEC.prototype.BlinkEvent = function(oEvent)
{
	if (this.bReadOnly || !oEvent || !oEvent.display || oEvent._blink || !this.arConfig.Settings.blink || oEvent.bSuperposed)
		return;
	oEvent._blink = {};
	var
		_this = this,
		i, _x_ClassName, len2,
		len = oEvent.oParts.length;

	oEvent._blink.interval = setInterval(function(){_this.BlinkInterval(oEvent);}, 550);
};

JCEC.prototype.BlinkInterval = function(oEvent)
{
	if (!this.arConfig.Settings.blink)
		return this.ClearBlink(oEvent);

	_x_ClassName = oEvent._blink.bRed ? BX.removeClass : BX.addClass;
	var i, len, cn = "bxec-event-blink";

	switch (this.activeTabId)
	{
		case 'month':
			if (oEvent.oParts)
			{
				len = oEvent.oParts.length;
				for (i = 0; i < len; i++)
					if (oEvent.oParts[i])
						_x_ClassName(oEvent.oParts[i], cn);
			}
			break;
		case 'week':
		case 'day':
			if (oEvent.oDaysT && oEvent.oTLParts)
			{
				if (oEvent.oDaysT[this.activeTabId])
					_x_ClassName(oEvent.oDaysT[this.activeTabId], cn);

				len = oEvent.oTLParts[this.activeTabId].length;
				for (i = 0; i < len; i++)
					if (oEvent.oTLParts[this.activeTabId][i])
						_x_ClassName(oEvent.oTLParts[this.activeTabId][i], cn);
			}
			break;
	}

	oEvent._blink.bRed = !oEvent._blink.bRed;
}

JCEC.prototype.ClearBlink = function(oEvent)
{
	if (oEvent._blink && !oEvent._blink.bCleared)
	{
		clearInterval(oEvent._blink.interval);

		var i, len, cn = "bxec-event-blink";

		if (oEvent.oParts)
		{
			len = oEvent.oParts.length;
			for (i = 0; i < len; i++)
				if (oEvent.oParts[i])
					BX.removeClass(oEvent.oParts[i], cn);
		}

		if (oEvent.oDaysT)
		{
			if (oEvent.oDaysT.week)
				BX.removeClass(oEvent.oDaysT.week, cn);

			if (oEvent.oDaysT.day)
				BX.removeClass(oEvent.oDaysT.day, cn);
		}

		if (oEvent.oTLParts)
		{
			if (oEvent.oTLParts.week)
			{
				len2 = oEvent.oTLParts.week.length;
				for (i = 0; i < len2; i++)
					if (oEvent.oTLParts.week[i])
						BX.removeClass(oEvent.oTLParts.week[i], cn);
			}

			if (oEvent.oTLParts.day)
			{
				len2 = oEvent.oTLParts.day.length;
				for (i = 0; i < len2; i++)
					if (oEvent.oTLParts.day[i])
						BX.removeClass(oEvent.oTLParts.day[i], cn);
			}
		}

		oEvent._blink.bCleared = true;
	}
}