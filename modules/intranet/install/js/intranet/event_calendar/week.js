JCEC.prototype.BuildWeekDaysTable = function()
{
	var
		oTab = this.Tabs['week'],
		tbl = oTab.pBodyCont,
		pTitleR = tbl.rows[0],
		pMoreEvR = tbl.rows[1],
		pGridR = tbl.rows[2],
		i, width, c;

	for (var i = 0; i < 7; i++)
	{
		pTitleR.insertCell(i + 1);
		c = pMoreEvR.insertCell(i + 1);
		c.innerHTML = '<div class="bxec-wdv-more-ev">&nbsp;</div>';
	}
	pGridR.cells[0].colSpan = "9";

	width = bxInt(tbl.parentNode.offsetWidth) - 40 - 16; // width - Time Column - scroll width
	oTab.dayColWidth = Math.round(width / 7);

	oTab.arDayColWidth = [];
	var dW = width - (oTab.dayColWidth * 7);
	for (i = 0; i < 7; i++)
	{
		oTab.arDayColWidth[i] = oTab.dayColWidth;
		if (dW < 0)
		{
			oTab.arDayColWidth[i]--;
			dW++;
		}
		else if(dW > 0)
		{
			oTab.arDayColWidth[i]++;
			dW--;
		}
	}

	oTab.pTimelineCont = pGridR.cells[0].firstChild;
}

JCEC.prototype.FillWeekDaysTitle = function(P)
{
	var
		Tab = this.Tabs[P.tabId],
		tbl = Tab.pBodyCont,
		pTitleR = tbl.rows[0],
		pMoreEvR = tbl.rows[1],
		oDate = P.startDate,
		arDays = [],
		_this = this,
		i, cn, innerHtml, title, day, date, month, year, bCurDay, bHoliday, c1, c2;

	Tab.curDay = false;
	for (i = 1; i <= P.count; i++)
	{
		day = this.convertDayIndex(oDate.getDay());
		date = oDate.getDate();
		month = oDate.getMonth();
		year = oDate.getFullYear();

		innerHtml = this.arConfig.days[day][1] + ', ' + oDate.getDate();
		title = this.arConfig.days[day][0] + ', ' + oDate.getDate() + ' ' + this.arConfig.month_r[month] + ' ' + year;

		c1 = pTitleR.cells[i];
		c2 = pMoreEvR.cells[i];
		link = BX.create('A', {props: {className: 'bxec-day-link', href: 'javascript:void(0)', title: EC_MESS.GoToDay}, html: innerHtml}),
		BX.cleanNode(c1);

		c1.appendChild(this.CreateStrut(Tab.arDayColWidth[i - 1] - 2));
		c1.appendChild(BX.create("BR"));
		c1.appendChild(link);
		link.onmousedown = function(e){return BX.PreventDefault(e);};
		link.onclick = function(e)
		{
			var D = Tab.arDays[this.parentNode.cellIndex - 1];
			_this.SetTab('day', false, {bSetDay: false});
			_this.SetDay(D.date, D.month, D.year);
			return BX.PreventDefault(e);
		};
		c1.title = title;

		bHoliday = this.week_holidays[day] || this.year_holidays[date + '.' + month]; //It's Holliday
		bCurDay = date == this.currentDate.date && month == this.currentDate.month && year == this.currentDate.year;

		cn = '';
		if (bCurDay || bHoliday)
		{
			if (bCurDay)
			{
				cn = 'bxec-cur-day';
				Tab.curDay = {cellInd: i};
			}
			if (bHoliday)
				cn += ' bxec-hol-day';
		}
		c1.className = c2.className = cn;
		arDays.push({
			day: day,
			date: date,
			month: month,
			year: year,
			bHoliday: bHoliday,
			bCurDay: bCurDay,
			pWnd: c1,
			pMoreEvents: c2,
			Events: {begining: [], hidden: [], all: []},
			EventsCount: 0
		});

		if (!this.bReadOnly)
		{
			c1.onmousedown = c2.onmousedown = function(){_this.DayTitleOnMouseDown(this, P.tabId)};
			c1.onmouseup = c2.onmouseup = function(e){_this.DayTitleOnMouseUp(this, P.tabId); BX.PreventDefault(e);};
			c1.onmouseover = c2.onmouseover = function(){_this.DayTitleOnMouseOver(this, P.tabId)};
		}
		oDate.setDate(oDate.getDate() + 1); // Next day
		if (i == 1)
			Tab.activeFirst = new Date(year, month, date).getTime();
		if (i == P.count)
			Tab.activeLast = new Date(year, month, date, 23, 59).getTime();
	}
	return arDays;
}

JCEC.prototype.BuildTimelineGrid = function(oTab, arDays)
{
	var
		tbl = BX.create('TABLE', {props: {className: 'bxec-wdv-timeline-tbl'}}),
		formatTime = function(num) {return ((num < 10) ? '0' : '') + num.toString() + ':00';},
		bHol = false,
		adCN = '',
		_this = this,
		tabId = oTab.id,
		i, r1, r2, c, cj1, cj2, ghostDiv, oDay, cn
		arTF = this.arConfig.workTime[0].split('.'),
		arTT = this.arConfig.workTime[1].split('.'),
		fromHour = bxIntEx(arTF[0]),
		fromMin = bxIntEx(arTF[1]),
		toHour = bxIntEx(arTT[0]),
		toMin = bxIntEx(arTT[1]);

	oTab.arDays = arDays; // save in oTab

	if (oTab.pTimelineCont)
		BX.cleanNode(oTab.pTimelineCont);

	for (i = 0; i < 24; i++) // Every hour
	{
		bHol1 = ((i < fromHour + 1 && fromMin == 30) || (i >= toHour && toMin == 0) || i > toHour || i < fromHour);
		bHol2 = ((i >= toHour && toMin == 30) || i < fromHour || i >= toHour);

		adCN = bHol1 ? ' bxec-wdv-hol-row' : '';
		adCN2 = bHol2 ? ' bxec-wdv-hol-row' : '';

		r1 = tbl.insertRow(-1);
		r1.className = 'bxec-half-time-row1' + adCN;
		c = r1.insertCell(-1);
		c.innerHTML = formatTime(i);
		c.rowSpan = '2';
		c.className = 'bxec-time';

		r2 = tbl.insertRow(-1);
		r2.className = 'bxec-half-time-row2' + adCN2;

		for (j = 0; j < oTab.daysCount; j++)
		{
			cj1 = r1.insertCell(-1);

			if (i == 0)
				cj1.appendChild(this.CreateStrut(oTab.arDayColWidth[j] - 2));
			
			cj2 = r2.insertCell(-1);

			if (!this.bReadOnly)
			{
				cj1.onmousedown = cj2.onmousedown = function(){_this.TimeCellOnMouseDown(this, tabId)};
				cj1.onmouseup = cj2.onmouseup = function(){_this.TimeCellOnMouseUp(this, tabId)};
				cj1.onmouseover = cj2.onmouseover = function(){_this.TimeCellOnMouseOver(this, tabId)};
			}

			oDay = arDays[j];
			if (!oDay.bHoliday && !oDay.bCurDay)
				continue;

			if (oDay.bHoliday)
			{
				BX.addClass(cj1, 'bxec-time-hol-c1');
				BX.addClass(cj2, 'bxec-time-hol-c2');
			}
			if (oDay.bCurDay)
			{
				BX.addClass(cj1, 'bxec-time-cur-c1');
				BX.addClass(cj2, 'bxec-time-cur-c2');
			}
		}
	}

	// Add Strut
	tbl.rows[0].cells[0].appendChild(this.CreateStrut(40)); 

	if (BX.browser.IsIE()) // Add scroll width for IE
	{	
		setTimeout(function(){
			try{
				var c = tbl.rows[0].cells[tbl.rows[0].cells.length - 1];
				var c2 = oTab.pBodyCont.rows[0].cells[oTab.pBodyCont.rows[0].cells.length - 2];

				if (c.offsetLeft - c2.offsetLeft > 2)
					c.style.width = oTab.dayColWidth + 50 + 'px';
			}
			catch(e){}
		}, 500);
	}

	oTab.pTimelineTable = tbl;
	oTab.pTimelineCont.appendChild(tbl);
	setTimeout(function() {oTab.pTimelineCont.scrollTop = 40 * bxInt(fromHour);}, 100); // Scroll to the start of the work time

	if (oTab.curDay)
		setTimeout(function(){_this.ShowCurTimePointer(tabId);}, 100);
	else
		this.HideCurTimePointer(tabId);

	this.BuildWeekEventHolder();
}

JCEC.prototype.TimeCellOnMouseOver = function(pCell, tabId)
{
	if (this.selectTimeMode)
	{
		this.selectTimeEndObj = pCell;
		this.SelectTime(tabId);
	}
}

JCEC.prototype.TimeCellOnMouseDown = function(pCell, tabId)
{
	this.selectTimeMode = true;
	this.selectTimeStartObj = this.selectTimeEndObj = pCell;
	if (pCell.className.indexOf('bxec-time-selected') == -1)
		return this.SelectTime(tabId);
	this.selectTimeMode = false;
	this.DeSelectTime(tabId);
	this.CloseAddEventDialog();
}

JCEC.prototype.TimeCellOnMouseUp = function(pCell, tabId)
{
	if (!this.selectTimeMode)
		return;
	this.ShowAddEventDialog();
	this.selectTimeMode = false;
	//this.selectDayTMode = false;
}


JCEC.prototype.DayTitleOnMouseOver = function(pCell, tabId)
{
	if (this.selectTimeMode && !this.selectDayTMode)
	{
		var o = this.__GetRelativeCell(pCell);
		this.selectTimeEndObj = this.Tabs[tabId].pTimelineTable.rows[o.rowInd].cells[o.cellInd]; // Select 00:00 time cell in the timeline
		this.SelectTime(tabId);
		return;
	}

	if (this.selectDayTMode)
	{
		this.selectDayTEndObj = pCell;
		this.SelectDaysT(tabId);
	}
}

JCEC.prototype.DayTitleOnMouseDown = function(pCell, tabId)
{
	this.selectDayTMode = true;
	this.selectDayTStartObj = this.selectDayTEndObj = pCell;

	if (pCell.className.indexOf('bxec-day-t-selected') == -1)
	{
		if (this.arSelectedTime && this.arSelectedTime.length > 0)
			this.SelectTime(tabId);
		this.SelectDaysT(tabId);
		return;
	}

	this.selectDayTMode = false;
	this.DeSelectDaysT();
	this.CloseAddEventDialog();
}

JCEC.prototype.DayTitleOnMouseUp = function(pCell, tabId)
{
	if (this.selectTimeMode && !this.selectDayTMode)
	{
		var o = this.__GetRelativeCell(pCell);
		this.selectTimeEndObj = this.Tabs[tabId].pTimelineTable.rows[o.rowInd].cells[o.cellInd]; // Select 00:00 time cell in the timeline
		this.TimeCellOnMouseOver(this.selectTimeEndObj, tabId);
		this.TimeCellOnMouseUp(this.selectTimeEndObj, tabId);
		return;
	}
	if (!this.selectDayTMode)
		return;
	this.ShowAddEventDialog();
	this.selectDayTMode = false;
}

JCEC.prototype.__GetRelativeCell = function(pCell)
{
	var cellInd = pCell.cellIndex, rowInd = 0;
	if (cellInd > this.__ConvertCellIndex(this.selectTimeStartObj.parentNode.rowIndex, this.selectTimeStartObj.cellIndex))
	{
		cellInd--;
		rowInd = 47;
	}
	return {cellInd: cellInd, rowInd: rowInd};
}

JCEC.prototype.SelectDaysT = function(tabId)
{
	if (!this.arSelectedDaysT)
		this.arSelectedDaysT = [];

	if (!this.selectDayTStartObj || !this.selectDayTEndObj)
		return;

	if (this.arSelectedDaysT.length > 0)
		this.DeSelectDaysT();

	var
		oTab = this.Tabs[tabId],
		sCell = this.selectDayTStartObj, // Start cell
		eCell = this.selectDayTEndObj, // End cell
		sCol = sCell.cellIndex,
		eCol = eCell.cellIndex,
		tbl = oTab.pBodyCont,
		pTitleR = tbl.rows[0],
		pMoreEvR = tbl.rows[1],
		i;

	if (sCol > eCol) // Swap
	{
		sCell = this.selectDayTEndObj;
		eCell = this.selectDayTStartObj;
		sCol = sCell.cellIndex;
		eCol = eCell.cellIndex;
	}
	this.curDayTSelection = {sDay: oTab.arDays[sCol - 1], eDay: oTab.arDays[eCol - 1]};

	for (i = sCol; i <= eCol; i++)
	{
		c1 = pTitleR.cells[i];
		c2 = pMoreEvR.cells[i];

		BX.addClass(c1, 'bxec-day-t-selected');
		BX.addClass(c2, 'bxec-day-t-selected');
		this.arSelectedDaysT.push({pCell1 : c1, pCell2 : c2});
	}
}

JCEC.prototype.DeSelectDaysT = function()
{
	if (!this.arSelectedDaysT)
		return;
	var i, l;
	for (i = 0, l = this.arSelectedDaysT.length; i < l; i++)
	{
		BX.removeClass(this.arSelectedDaysT[i].pCell1, 'bxec-day-t-selected');
		BX.removeClass(this.arSelectedDaysT[i].pCell2, 'bxec-day-t-selected');
	}
	this.arSelectedDaysT = [];
}


JCEC.prototype.SelectTime = function(tabId)
{
	if (!this.arSelectedTime)
		this.arSelectedTime = [];

	if (!this.selectTimeStartObj || !this.selectTimeEndObj)
		return;

	if (this.arSelectedTime.length > 0)
		this.DeSelectTime(tabId);

	var
		oTab = this.Tabs[tabId],
		sCell = this.selectTimeStartObj, // Start cell
		sRow = sCell.parentNode.rowIndex,
		sCol = this.__ConvertCellIndex(sRow, sCell.cellIndex),
		eCell = this.selectTimeEndObj, // End cell
		eRow = eCell.parentNode.rowIndex,
		eCol = this.__ConvertCellIndex(eRow, eCell.cellIndex),
		oDays = {
			sDay: oTab.arDays[sCol - 1],
			eDay: oTab.arDays[eCol - 1],
			sTime: sRow / 2,
			eTime: eRow / 2 + 0.5
		},
		i, min, max, r1, r2, sRow_, eRow_;

	if (sRow > eRow && sCol == eCol || sCol > eCol) // Reverse selection
	{
		oDays.sTime += 0.5;
		oDays.eTime -= 0.5;
	}

	oDays.sHour = Math.floor(oDays.sTime);
	oDays.eHour = Math.floor(oDays.eTime);
	oDays.sMin = (oDays.sTime - oDays.sHour) * 60;
	oDays.eMin = (oDays.eTime - oDays.eHour) * 60;

	this.curTimeSelection = oDays;
	// Show selection in the timeline
	if (sCol == eCol) // during one day
	{
		this._SelectTime({sRow: sRow, eRow: eRow, col: sCol, bShowNotifier: true, tabId: tabId, oDays: oDays});
	}
	else // Several days
	{
		if (sCol < eCol)
		{
			min = sCol;
			max = eCol;
			sRow_ = sRow;
			eRow_ = eRow;
		}
		else
		{
			min = eCol;
			max = sCol;
			sRow_ = eRow;
			eRow_ = sRow;

			_d = oDays.sDay; oDays.sDay = oDays.eDay; oDays.eDay = _d; // Swap days
			_t = oDays.sTime; oDays.sTime = oDays.eTime; oDays.eTime = _t;  // Swap time
		}

		for (i = min; i <= max; i++)
		{
			r1 = (i == min) ? sRow_ : 0;
			r2 = (i == max) ? eRow_ : 47;
			this._SelectTime({sRow: r1, eRow: r2, col: i, bShowNotifier: i == min, tabId: tabId, oDays: oDays});
		}
	}
}

JCEC.prototype._SelectTime = function(P)
{
	var
		pTable = this.Tabs[P.tabId].pTimelineTable,
		min = Math.min(P.eRow, P.sRow),
		max = Math.max(P.eRow, P.sRow),
		i, pCell;

	for (i = min; i <= max; i++)
	{
		pCell = pTable.rows[i].cells[this.__ConvertCellIndex(i, P.col, true)];
		BX.addClass(pCell, 'bxec-time-selected');
		xxx = this.arSelectedTime.push({pCell : pCell});
	}

	if (P.bShowNotifier)
	{
		if (min == P.eRow && (P.eRow != P.sRow))
		{
			_d = P.oDays.sDay; P.oDays.sDay = P.oDays.eDay; P.oDays.eDay = _d; // Swap days
			_t = P.oDays.sTime; P.oDays.sTime = P.oDays.eTime; P.oDays.eTime = _t; // Swap time
		}
		this.ShowSelectTimeNotifier({tabId: P.tabId, rowInd: min, colInd: P.col, oDays: P.oDays});
	}
}

JCEC.prototype.__ConvertCellIndex = function(rowInd, cellInd, bInv)
{
	if ((rowInd / 2) !== Math.round((rowInd / 2)))
		cellInd += bInv ? -1 : 1;
	return cellInd;
}

JCEC.prototype.DeSelectTime = function(tabId)
{
	if (!this.arSelectedTime)
		return;
	var i, l;
	for (i = 0, l = this.arSelectedTime.length; i < l; i++)
		BX.removeClass(this.arSelectedTime[i].pCell, 'bxec-time-selected');
	this.HideSelectTimeNotifier({tabId: tabId});
	this.arSelectedTime = [];
}

JCEC.prototype.ShowSelectTimeNotifier = function(P, bShow)
{
	var
		oTab = this.Tabs[P.tabId],
		pTable = oTab.pTimelineTable,
		pCell = pTable.rows[P.rowInd].cells[this.__ConvertCellIndex(P.rowInd, P.colInd, true)],
		left = bxInt(pCell.offsetLeft),
		top = bxInt(pCell.offsetTop),
		sHour = Math.floor(P.oDays.sTime),
		eHour = Math.floor(P.oDays.eTime),
		sMin = (P.oDays.sTime - sHour) * 60,
		eMin = (P.oDays.eTime - eHour) * 60,
		d1 = P.oDays.sDay,
		d2 = P.oDays.eDay,
		dTop = -19,
		dLeft = 15,
		innnerHTML, t1, t2;

	if (!oTab.pSTNotifier)
		oTab.pSTNotifier = pTable.parentNode.appendChild(BX.create('DIV', {props: {className: 'bxec-st-notifier'}}));

	if (sMin < 10)
		sMin = '0' + sMin.toString();
	if (eMin < 10)
		eMin = '0' + eMin.toString();

	t1 = sHour + ':' + sMin;
	t2 = eHour + ':' + eMin;
	if (P.oDays.sDay == P.oDays.eDay) // during one day
		innnerHTML = '<nobr>' + t1 + ' - ' + t2 + '</nobr>';
	else
		innnerHTML = '<nobr>' + bxFormatDate(d1.date, d1.month + 1, d1.year) + ' ' + t1 + ' - ' +
				bxFormatDate(d2.date, d2.month + 1, d2.year) + ' ' + t2 + '</nobr>';

	if (bxInt(sHour) <= 0) // If start time 00:00
		dTop = 20;

	oTab.pSTNotifier.innerHTML = '<img class="bxec-iconkit" src="/bitrix/images/1.gif">' + innnerHTML;
	oTab.pSTNotifier.style.left = left + dLeft + 'px';
	oTab.pSTNotifier.style.top = top + dTop + 'px';
	oTab.pSTNotifier.style.display = 'block';
}

JCEC.prototype.HideSelectTimeNotifier = function(P)
{
	var oTab = this.Tabs[P.tabId];
	if (!oTab.pSTNotifier)
		return;
	oTab.pSTNotifier.style.display = 'none';
}

JCEC.prototype.BuildSingleDayTable = function()
{
	var
		oTab = this.Tabs['day'],
		tbl = oTab.pBodyCont,
		pTitleR = tbl.rows[0],
		pMoreEvR = tbl.rows[1],
		pGridR = tbl.rows[2],
		i, width, c;

	pTitleR.insertCell(1);
	c = pMoreEvR.insertCell(1);
	c.innerHTML = '<div class="bxec-wdv-more-ev">&nbsp;</div>';
	pGridR.cells[0].colSpan = "3";

	width = bxInt(tbl.parentNode.offsetWidth) - 40 - 16; // width - Time Column - scroll width
	oTab.dayColWidth = width;
	oTab.arDayColWidth = [oTab.dayColWidth];
	oTab.pTimelineCont = pGridR.cells[0].firstChild;
}

JCEC.prototype.ShowCurTimePointer = function(tabId)
{
	var oTab = this.Tabs[tabId];
	if (!oTab.oCurTimePointer)
		this.CreateCurTimePointer(tabId);

	var
		oCTP = oTab.oCurTimePointer,
		c = oTab.pTimelineTable.rows[0].cells[oTab.curDay.cellInd],
		_this = this,
		fMove = function()
		{
			var
				curTime = new Date(),
				h = bxInt(curTime.getHours()),
				m = bxInt(curTime.getMinutes());

			cnt = oTab.pTimelineTable.rows[2 * h].cells[1];
			dTop = cnt.offsetTop + bxInt(2 * cnt.offsetHeight * m / 60) - 3;

			oTab.oCurTimePointer.pWnd.style.top = dTop + 'px';
			if (m < 10)
				m = '0' + m.toString();
			oTab.oCurTimePointer.pWnd.title = EC_MESS.CurTime +' - ' +  h + ':' + m;
		};
	if (!c)
		return;
	oTab.pTimelineCont.appendChild(oCTP.pWnd);
	oCTP.pWnd.style.display = 'block';
	oCTP.pWnd.style.left = bxInt(c.offsetLeft) + 'px';
	oCTP.pWnd.style.width = bxInt(c.offsetWidth) + 'px';
	oCTP.interval = setInterval(fMove, 60000);

	fMove();
}

JCEC.prototype.CreateCurTimePointer = function(tabId)
{
	this.Tabs[tabId].oCurTimePointer = {pWnd : BX.create('DIV', {props: {className: 'bxec-time-pointer'}, html: '<img class="bxec-iconkit" src="/bitrix/images/1.gif">'})};
}

JCEC.prototype.HideCurTimePointer = function(tabId)
{
	var oTab = this.Tabs[tabId];
	if (!oTab.oCurTimePointer)
		return;

	if (oTab.oCurTimePointer.interval)
		clearInterval(oTab.oCurTimePointer.interval);
	oTab.oCurTimePointer.pWnd.style.display = 'none';
}

JCEC.prototype.BuildDaySelector = function()
{
	var _this = this;
	var selTable = BX.create('TABLE', {props: {align: 'center'}});
	var r = selTable.insertRow(-1);

	var PrevDayButtCell = r.insertCell(-1);
	var CD_Cell = r.insertCell(-1);
	var NextDayButtCell = r.insertCell(-1);
	CD_Cell.className = 'bxec-cm';

	var but = PrevDayButtCell.appendChild(BX.create('IMG', {props: {src: '/bitrix/images/1.gif', className: 'bxec-iconkit bxec-pr-m-but', title: EC_MESS.ShowPrevDay}}));
	but.onclick = function(){_this._IncreaseCurDay(-1);};

	but = NextDayButtCell.appendChild(BX.create('IMG', {props: {src: '/bitrix/images/1.gif', className: 'bxec-iconkit bxec-nx-m-but', title: EC_MESS.ShowNextDay}}));
	but.onclick = function(){_this._IncreaseCurDay(1);};

	this.DaySelector = {pWnd: BX(this.id + '_day_selector'), cd: CD_Cell};
	this.DaySelector.pWnd.appendChild(selTable);
	this.DaySelector.pWnd.style.display = 'block';
}

JCEC.prototype.DaySelectorOnChange = function(date, month, year)
{
	var oDate = new Date();
	oDate.setFullYear(year, month, date);
	var
		day = this.convertDayIndex(oDate.getDay()),
		date = oDate.getDate(),
		month = oDate.getMonth(),
		year = oDate.getFullYear(),
		innerHtml = '<nobr>' + this.arConfig.days[day][0] + ', ' + date + ' ' + this.arConfig.month_r[month] + ' ' + year + '</nobr>';
	this.DaySelector.cd.innerHTML = innerHtml;
	return {day: day, date: date, month: month, year: year, oDate: oDate};
}

JCEC.prototype._IncreaseCurDay = function(delta)
{
	this.SetDay(this.activeDate.date + delta, this.activeDate.month, this.activeDate.year);
}

JCEC.prototype.BuildWeekSelector = function()
{
	var _this = this;
	var selTable = BX.create('TABLE', {props: {align: 'center'}});
	var r = selTable.insertRow(-1);

	var PrevWeekButtCell = r.insertCell(-1);
	var CW_Cell = r.insertCell(-1);
	var NextWeekButtCell = r.insertCell(-1);
	CW_Cell.className = 'bxec-cm';

	var but = PrevWeekButtCell.appendChild(BX.create('IMG', {props: {src: '/bitrix/images/1.gif', className: 'bxec-iconkit bxec-pr-m-but', title: EC_MESS.ShowPrevWeek}}));
	but.onclick = function(){_this._IncreaseCurWeek(-1);};

	but = NextWeekButtCell.appendChild(BX.create('IMG', {props: {src: '/bitrix/images/1.gif', className: 'bxec-iconkit bxec-nx-m-but', title: EC_MESS.ShowNextWeek}}));
	but.onclick = function(){_this._IncreaseCurWeek(1);};

	this.WeekSelector = {pWnd: BX(this.id + '_week_selector'), cw: CW_Cell};
	this.WeekSelector.pWnd.appendChild(selTable);
}

JCEC.prototype.WeekSelectorOnChange = function(week, month, year)
{
	var oMonDate = new Date();
	oMonDate.setFullYear(year, month, 1);
	var day = this.convertDayIndex(oMonDate.getDay());
	if(day > 0)
		oMonDate.setDate(oMonDate.getDate() - day); // Now it-s monday

	if (week != 0)
		oMonDate.setDate(oMonDate.getDate() + (7 * week));

	var oSunDate = new Date(oMonDate.getTime());
	oSunDate.setDate(oSunDate.getDate() + 6);
	var
		d_f = oMonDate.getDate(),
		m_f = oMonDate.getMonth(),
		y_f = oMonDate.getFullYear(),
		d_t = oSunDate.getDate(),
		m_t = oSunDate.getMonth(),
		y_t = oSunDate.getFullYear();

	var innerHTML;
	if (m_f == m_t)
		innerHTML = d_f + ' - ' + d_t + ' ' + this.arConfig.month_r[m_f] + ' ' + y_f;
	else if(y_f == y_t)
		innerHTML = d_f + ' ' + this.arConfig.month_r[m_f] + ' - ' + d_t + ' ' + this.arConfig.month_r[m_t] + ' ' + y_f;
	else
		innerHTML = d_f + ' ' + this.arConfig.month_r[m_f] + ' ' + y_f + ' - ' + d_t + ' ' + this.arConfig.month_r[m_t] + ' ' + y_t;

	this.WeekSelector.cw.innerHTML = '<nobr>' + innerHTML + '</nobr>';
	return {
		monthFrom: m_f,
		yearFrom: y_f,
		weekStartDate: oMonDate,
		monthTo: m_t,
		yearTo: y_t,
		weekEndDate: oSunDate
	};
}

JCEC.prototype._IncreaseCurWeek = function(delta)
{
	this.SetWeek(this.activeDate.week + delta, this.activeDate.month, this.activeDate.year);
}

JCEC.prototype.SetWeek = function(w1, m1, y1)
{
	var
		res = this.WeekSelectorOnChange(w1, m1, y1),
		m = res.monthTo,
		y = res.yearTo,
		w;

	if (m != this.activeDate.month || y != this.activeDate.year)
	{
		if (w1 >= 0)
			w = 0;
		else
			w = Math.ceil(res.weekEndDate.getDate() / 7) - 1;
	}
	else
		w = w1;

	if (!this.arLoadedMonth[m + '.'+ y] && !this.bLoadAllEvents)
		return this.LoadEvents(m, y, {week: w1, month: m1, year: y1});
	var bSetActiveDate = this.activeDate.month != m || this.activeDate.year != y;
	this.activeDate.week = w;
	this.activeDate.month = m;
	this.activeDate.year = y;
	if (bSetActiveDate)
		this.SetTabNeedRefresh('week', true);
	this.BuildTimelineGrid(this.Tabs['week'], this.FillWeekDaysTitle({tabId: 'week', count: 7, startDate: res.weekStartDate}));
}

JCEC.prototype.SetDay = function(d, m1, y1)
{
	var
		res = this.DaySelectorOnChange(d, m1, y1),
		m = res.month,
		y = res.year;

	if (!this.arLoadedMonth[m + '.'+ y] && !this.bLoadAllEvents)
		return this.LoadEvents(m, y, {date: d, month: m1, year: y1});

	var bSetActiveDate = this.activeDate.month != res.month || this.activeDate.year != res.year;
	this.activeDate.date = res.date;
	this.activeDate.month = res.month;
	this.activeDate.year = res.year;
	if (bSetActiveDate)
		this.SetTabNeedRefresh('day', true);

	var arDays = this.FillWeekDaysTitle({tabId: 'day', count: 1, startDate: res.oDate});
	this.BuildTimelineGrid(this.Tabs['day'], arDays);
}