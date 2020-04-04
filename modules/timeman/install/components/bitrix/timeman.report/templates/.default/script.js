;(function(){
var arPopups = {};

function JCTimeManReport(id, params)
{
	this.DIV = id;

	this.SETTINGS = {
		DATE_START: params.date_start || new Date(),
		MONTHS: params.MONTHS,
		DAYS: params.DAYS,
		LANG: params.LANG,
		SITE_ID: params.SITE_ID,
		DEPARTMENTS: params.DEPARTMENTS,
		FILTER: params.FILTER,
		DATESELECTOR: params.DATESELECTOR
	};

	this.FILTER = {
		DEPARTMENT: this.SETTINGS.START_DEPARTMENT || '',
		SHOW_ALL: this.SETTINGS.START_SHOW_ALL || 'Y'
	};

	this.__nullifyDate(this.SETTINGS.DATE_START, true);

	this.PARTS = {
		DATESELECTOR: null,
		LAYOUT: null,
		LAYOUT_COLS: {NAME: null, DATA: null},
		SCROLLERS: {LEFT: null, RIGHT: null},
		TODAY_CELL: null,
		NAV: null
	};

	this.DATA = {DEPARTMENTS: [], USERS: []};
	this.DRAGDATA = {startx: null, lastx: null, startscroll: null, mode: 1, scrollTimer: null, dxscroll: 0};
	this.TOTALS = {};

	this.arCellObjects = [];

	this.today = this.__nullifyDate();
	this.DATA_SETTINGS = null;
	this.HINTS = [];

	this.arViews = ['arrival', 'departure'];

	this.page = 0;

	BX.ready(BX.delegate(this.Init, this));

	BX.addCustomEvent(this, 'onEntryReloaded', BX.delegate(this.onEntryReloaded, this));
}

JCTimeManReport.prototype.Init = function()
{
	this.DIV = BX(this.DIV);

	if (this.SETTINGS.FILTER && BX.type.isString(this.SETTINGS.FILTER))
		this.SETTINGS.FILTER = document.forms[this.SETTINGS.FILTER];

	this.loadFilterHash();

	this.CreateLayout();
	this.PARTS.NAV = this.DIV.appendChild(BX.create('DIV'));

}

JCTimeManReport.prototype.CreateLayout = function()
{
	this._createLayoutRow();

	setTimeout(BX.delegate(this.loadData, this), 50);
}

JCTimeManReport.prototype._createLayoutRow = function()
{
	if (this.PARTS.LAYOUT)
		BX.cleanNode(this.PARTS.LAYOUT, true);

	var className = 'tm-report';
	if (this.SETTINGS.FILTER.additional && !this.SETTINGS.FILTER.additional.checked)
		className += ' bx-tm-additions-disabled'
	if (this.SETTINGS.FILTER.stats && !this.SETTINGS.FILTER.stats.checked)
		className += ' bx-tm-wide-mode';

	this.PARTS.LAYOUT = this.DIV.appendChild(
		BX.create('DIV', {props: {className: className}, events: {'scroll': function(){this.scrollLeft = 0; this.scrollTop = 0;}}})
	);
}

JCTimeManReport.prototype._createLayout = function()
{
	this.PARTS.LAYOUT_COLS.NAME = this.PARTS.LAYOUT.appendChild(
		BX.create('DIV', {props: {className: 'tm-report-col-name'}})
	);

	this.PARTS.LAYOUT_COLS.DATA = this.PARTS.LAYOUT.appendChild(
		BX.create('DIV', {props: {className: 'tm-report-col-data'}})
	);

	var nameTable = (this.PARTS.LAYOUT_COLS.NAME.appendChild(BX.create('TABLE', {
		props: {className: 'tm-report-table-name bx-tm-data-table'},
		attrs: {cellSpacing: '0'},
		children: [document.createElement('THEAD'), document.createElement('TBODY')]
	})));

	var row = nameTable.tHead.insertRow(-1);

	BX.adjust(row.insertCell(-1), {
		props: {className: 'bx-name-col'},
		html: '<div class="tm-inner">' + this.SETTINGS.LANG.EMPLOYEE + '</div>'
	});

	BX.adjust(row.insertCell(-1), {
		props: {className: 'bx-stats-col bx-total-days'},
		html: '<div class="tm-inner">' + this.SETTINGS.LANG.OVERALL_DAYS + '</div>'
	});
	BX.adjust(row.insertCell(-1), {
		props: {className: 'bx-stats-col bx-total-time'},
		html: '<div class="tm-inner">' + this.SETTINGS.LANG.OVERALL + '</div>'
	});
	BX.adjust(row.insertCell(-1), {
		props: {className: 'bx-stats-col bx-total-viol'},
		html: '<div class="tm-inner">' + this.SETTINGS.LANG.OVERALL_VIOL + '</div>'
	});

	BX.adjust(row.insertCell(-1), {
		props: {className: 'bx-tm-scroller-cell'},
		children: [
			BX.create('DIV', {
				style: {position: 'absolute'},
				children: [
					(

this.PARTS.SCROLLERS.LEFT = BX.create('DIV', {
	props: {className: 'tm-report-scroller-left', id: 'tm_report_scroller_left'},
	html: '<div class="tm-report-scroller-arrow"></div>'
})
					)
				]
			}),
			BX.create('SPAN', {html: '&nbsp;'})
		]
	});

	var dataTable = (this.PARTS.LAYOUT_COLS.DATA.appendChild(BX.create('TABLE', {
			props: {className: 'tm-report-table-data bx-tm-data-table'},
			attrs: {cellSpacing: '0'},
			children: [document.createElement('THEAD'), document.createElement('TBODY')]
		})));

	var obSampleRow = dataTable.tHead.insertRow(-1);

	// generate dating cols
	var startMonth = this.SETTINGS.DATE_START.getMonth();
	var cur_date = this.__nullifyDate(new Date(this.SETTINGS.DATE_START.valueOf()), true);
	var cellCount = 0;

	while (cur_date.getMonth() == startMonth)
	{
		var obCell = obSampleRow.insertCell(-1);

		obCell.className = 'bx-tm-month-day';

		if (cur_date.valueOf() == this.today.valueOf())
		{
			obCell.className += ' bx-tm-month-today';
			this.PARTS.TODAY_CELL = obCell;
		}
		if (cur_date.getDay() == 0 || cur_date.getDay() == 6)
			obCell.className += ' bx-tm-month-holiday';

		var cur_day = cur_date.getDate();

		obCell.innerHTML = '<div class="bx-tm-day-title">' + cur_day + '</div><div class="bx-tm-day-weekday">' + this.SETTINGS.DAYS[cur_date.getDay()] + '</div>';

		cur_date.setDate(cur_date.getDate() + 1);

		cellCount++;
	}

	var l = this.DATA.USERS.length,
		ld = this.DATA.DEPARTMENTS.length;

	if (ld > 0)
	{
		dataTable = dataTable.tBodies[0];
		nameTable = nameTable.tBodies[0];

		for (var d = 0; d < ld; d++)
		{
			var i, hint;

			var cell = (nameTable.insertRow(-1)).insertCell(-1);
			cell.colSpan = 5;
			cell.className = 'bx-tm-departments-cell';


			var h = '<div class="bx-tm-spacer"><div class="bx-tm-departments-chain">'
				ldd = this.DATA.DEPARTMENTS[d].CHAIN.length;

			for (i = 0; i < ldd; i++)
			{
				h += (i > 0 ? '<span class="bx-tm-departments-delimiter">&mdash;</span>' : '') + '<a href="' + this.DATA.DEPARTMENTS[d].CHAIN[i].URL + '">' + BX.util.htmlspecialchars(this.DATA.DEPARTMENTS[d].CHAIN[i].NAME) + '</a>';
			}

			h += '</div>&nbsp;</div>'
			cell.innerHTML = h;

			hint = BX.create('SPAN', {props: {BXDPTID: this.DATA.DEPARTMENTS[d].ID,className: 'bx-tm-user-settings-info'}})
			cell.firstChild.firstChild.appendChild(hint);

			this.HINTS.push(hint);

			var emptycell = dataTable.insertRow(-1).insertCell(-1);
			emptycell.className = 'bx-tm-departments-cell';
			emptycell.innerHTML = '<div class="bx-tm-spacer">&nbsp;</div>';
			emptycell.colSpan = cellCount;

			for (i = 0; i < l; i++)
			{
//XXX: check this!
				if (this.DATA.USERS[i].DEPARTMENT != this.DATA.DEPARTMENTS[d].ID)
					continue;

				row = nameTable.insertRow(-1), hint = null;

				BX.adjust(row.insertCell(-1), {
					props: {
						className: 'bx-name-col' + (this.DATA.USERS[i].HEAD ? ' bx-head' : '')
					},
					children: [
						BX.create('DIV', {props: {className: 'tm-inner'}, children:
						[
							(hint = BX.create('SPAN', {props: {BXUSERID: this.DATA.USERS[i].ID,className: 'bx-tm-user-settings-info'}})),
							BX.create('A', {
								attrs: {href: this.DATA.USERS[i].URL},
								props: {
									className: 'tm-user-link',
									title: (this.DATA.USERS[i].HEAD ? this.SETTINGS.LANG.HEAD : '')},
								html: this.DATA.USERS[i].NAME
							}),
							BX.create('DIV', {
								props: {className: 'bx-tm-view-arrival bx-tm-view-caption'},
								text: ''//this.SETTINGS.LANG.ARRIVAL
							}),
							BX.create('DIV', {
								props: {className: 'bx-tm-view-departure bx-tm-view-caption'},
								text: ''//this.SETTINGS.LANG.DEPARTURE
							})
						]})
					]
				});

				this.HINTS.push(hint);
				this._createHint(hint, this.DATA.USERS[i].SETTINGS);

				var obTotals = {
					TOTAL: null, TOTAL_DAYS: null, TOTAL_VIOLATIONS: null
				}

				obTotals.TOTAL_DAYS = BX.adjust(row.insertCell(-1), {
					props: {className: 'bx-stats-col bx-total-days'},
					html: '<span class="bx-days">' + this.DATA.USERS[i].TOTAL_DAYS + '</span>' + (this.DATA.USERS[i].TOTAL_INACTIVE > 0 ? '<span class="bx-days-inactive">(' + this.DATA.USERS[i].TOTAL_INACTIVE + ')</span>' : '')
				});

				obTotals.TOTAL = BX.adjust(row.insertCell(-1), {
					props: {className: 'bx-stats-col bx-total-time'},
					text: BX.timeman.formatWorkTime(this.DATA.USERS[i].TOTAL)
				});

				var v = Math.round(this.DATA.USERS[i].TOTAL_DAYS > 0 ? (100*this.DATA.USERS[i].TOTAL_VIOLATIONS/this.DATA.USERS[i].TOTAL_DAYS) : 0) + '%';

				obTotals.TOTAL_VIOLATIONS = BX.adjust(row.insertCell(-1), {
					props: {
						className: 'bx-stats-col bx-total-viol',
						title: this.DATA.USERS[i].TOTAL_VIOLATIONS + ' / ' + this.DATA.USERS[i].TOTAL_DAYS
					},

					text: v
				});

				if (!this.TOTALS['USER_' + this.DATA.USERS[i].ID])
					this.TOTALS['USER_' + this.DATA.USERS[i].ID] = []
				this.TOTALS['USER_' + this.DATA.USERS[i].ID].push(obTotals);

				var obUserRow = BX.clone(obSampleRow, true);
				obUserRow.BXUSERID = this.DATA.USERS[i].ID;
				dataTable.appendChild(obUserRow);

				for(var j=0,k=obUserRow.cells.length;j<k;j++)
					obUserRow.cells[j].innerHTML = '<div class="bx-tm-view-worktime">&nbsp;</div><div class="bx-tm-view-arrival">&nbsp;</div><div class="bx-tm-view-departure">&nbsp;</div>';


				row.onmouseover = function()
				{
					dataTable.rows[this.sectionRowIndex].className = nameTable.rows[this.sectionRowIndex].className = 'bx-over';
				}

				row.onmouseout = function()
				{
					dataTable.rows[this.sectionRowIndex].className = nameTable.rows[this.sectionRowIndex].className = '';
				}

				for (j=0,k=this.DATA.USERS[i]['ENTRIES'].length;j<k;j++)
				{
					var day = parseInt(this.DATA.USERS[i]['ENTRIES'][j].DAY, 10)-1;
					this.arCellObjects[this.arCellObjects.length] =
						obUserRow.cells[day].bxentry = new JCTimeManReportEntry(this, obUserRow.cells[day], this.DATA.USERS[i]['ENTRIES'][j], this.DATA.USERS[i].SETTINGS);
				}
			}
		}
	}

	this.PARTS.SCROLLERS.RIGHT = this.PARTS.LAYOUT.appendChild(BX.create('DIV', {
		props: {className: 'tm-report-scroller-right', id: 'tm_report_scroller_right'},
		html: '<div class="tm-report-scroller-arrow"></div>'
	}));

	this.PARTS.LAYOUT.appendChild(BX.create('DIV', {props: {className: 'tm-report-right-border'}}));
	this.PARTS.LAYOUT.appendChild(BX.create('DIV', {props: {className: 'tm-report-bottom-border'}}));


	this._createScrollers();
}

JCTimeManReport.prototype._createHint = function(node, settings)
{
	node.BXHINT = new BX.CHint({
		parent: node,
		title: this.SETTINGS.LANG.HINT_TITLE,
		hint:
			settings.UF_TM_MAX_START
				? (!!settings.UF_TM_FREE
					? '<div class="bx-tm-user-hint">' + this.SETTINGS.LANG.HINT_FREE + '</div>'
					: '<div class="bx-tm-user-hint"><div>' + this.SETTINGS.LANG.HINT_MAX_START + ': <b>' + BX.timeman.formatTime(settings.UF_TM_MAX_START) + '</b></div><div>' + this.SETTINGS.LANG.HINT_MIN_FINISH + ': <b>' + BX.timeman.formatTime(settings.UF_TM_MIN_FINISH) + '</b></div><div>' + this.SETTINGS.LANG.HINT_MIN_DURATION + ': <b>' + BX.timeman.formatWorkTime(settings.UF_TM_MIN_DURATION) + '</b></div></div>')
				: '<div class="bx-tm-user-hint">' + this.SETTINGS.LANG.HINT_DISABLED + '</div>',

		show_timeout: 10, hide_timeout: 9
	});
}

JCTimeManReport.prototype._setScrollersPosScroll = function()
{
	this.PARTS.LAYOUT_COLS.DATA.scrollLeft = 0;

	if (this.PARTS.TODAY_CELL)
	{
		var q = this.PARTS.TODAY_CELL.offsetLeft - this.PARTS.LAYOUT_COLS.DATA.offsetWidth + Math.ceil(this.PARTS.TODAY_CELL.offsetWidth * 1.6);
		this.PARTS.LAYOUT_COLS.DATA.scrollLeft = q > 0 ? q : 0;
	}
}

JCTimeManReport.prototype.setToday = function()
{
	if (this.PARTS.TODAY_CELL)
	{
		this._setScrollersPosScroll();
	}
	else
	{
		this.SETTINGS.DATE_START.setMonth(this.today.getMonth());
		this.SETTINGS.DATE_START.setYear(this.today.getFullYear());
		BX('tm_datefilter_title', true).innerHTML = this.SETTINGS.MONTHS[this.SETTINGS.DATE_START.getMonth()] + ' ' + this.SETTINGS.DATE_START.getFullYear();
		this.Page(1);
	}
}

JCTimeManReport.prototype._createScrollers = function()
{
	this.PARTS.LAYOUT.onmousedown = BX.proxy(this.startDrag, this);

	var stopScrollData = BX.proxy(this.stopScrollData, this);

	this.PARTS.SCROLLERS.LEFT.onmousedown = BX.delegate(function(e) {
		if (this.PARTS.LAYOUT_COLS.DATA.scrollLeft <= 0)
		{
			this.changeMonth(-1);
		}
		else
		{
			this.scrollData(-75, this);
			return BX.eventCancelBubble(e||window.event);
		}
	}, this);

	this.PARTS.SCROLLERS.RIGHT.onmousedown = BX.delegate(function(e) {
		if (this.PARTS.LAYOUT_COLS.DATA.scrollWidth - this.PARTS.LAYOUT_COLS.DATA.scrollLeft - this.PARTS.LAYOUT_COLS.DATA.offsetWidth <= 0)
		{
			this.changeMonth(1);
		}
		else
		{
			this.scrollData(75, this);
			return BX.eventCancelBubble(e||window.event);
		}
	}, this);

	this.PARTS.SCROLLERS.LEFT.onmouseup = this.PARTS.SCROLLERS.RIGHT.onmouseup = function() {stopScrollData(this)}

	BX.bind(this.PARTS.LAYOUT_COLS.DATA, 'mousewheel', BX.proxy(this._wheelScroll, this));

	setTimeout(BX.delegate(this._setScrollersPosScroll, this), 50);
}

JCTimeManReport.prototype._wheelScroll = function(e)
{
	this.scrollData(-Math.ceil(75 * BX.getWheelData(e)/3));
	return BX.PreventDefault(e);
}

JCTimeManReport.prototype.scrollData = function(dx, ob)
{
	var q = this.DRAGDATA.startscroll === null ? this.PARTS.LAYOUT_COLS.DATA.scrollLeft : this.DRAGDATA.startscroll;
	this.PARTS.LAYOUT_COLS.DATA.scrollLeft = q + dx;

	if (ob)
		this.startScrollData(dx, ob);
}

JCTimeManReport.prototype.startScrollData = function(dx, ob)
{
	if (null != this.DRAGDATA.scrollTimer)
		this.stopScrollData(ob);

	this.DRAGDATA.startscroll = this.PARTS.LAYOUT_COLS.DATA.scrollLeft;
	this.DRAGDATA.scrollTimer = setTimeout(BX.delegate(function() {this.moveScrollData(dx, ob)}, this), 500);
}

JCTimeManReport.prototype.moveScrollData = function(dx, ob)
{
	this.DRAGDATA.dxscroll += dx;
	this.scrollData(this.DRAGDATA.dxscroll);
	var moveScrollData = BX.proxy(this.moveScrollData, this);
	this.DRAGDATA.scrollTimer = setTimeout(function() {moveScrollData(dx, ob)}, 50);
}

JCTimeManReport.prototype.stopScrollData = function(ob)
{
	if (this.DRAGDATA.scrollTimer)
	{
		clearTimeout(this.DRAGDATA.scrollTimer);
		this.DRAGDATA.scrollTimer = null;
	}

	this.DRAGDATA.dxscroll = 0;this.DRAGDATA.startscroll = null;
}
JCTimeManReport.prototype.startDrag = function(e)
{
	e=e||window.event;
	this.DRAGDATA.lastx = this.DRAGDATA.startx = e.clientX;
	this.DRAGDATA.startscroll = this.PARTS.LAYOUT_COLS.DATA.scrollLeft;
	document.onmousemove = BX.proxy(this.moveDrag, this);
	document.onmouseup = BX.proxy(this.stopDrag, this);
}

JCTimeManReport.prototype.moveDrag = function(e)
{
	e=e||window.event;
	var x = e.clientX;

	this.PARTS.LAYOUT.style.cursor = x > this.DRAGDATA.lastx ? 'e-resize' : 'w-resize';

	this.scrollData(/*Math.floor(this.DRAGDATA.mode * (*/this.DRAGDATA.startx - x/*))*/);
	this.DRAGDATA.lastx = x;
	//this.DRAGDATA.mode = Math.abs(this.DRAGDATA.startx - x)/200 + 1;
}

JCTimeManReport.prototype.stopDrag = function()
{
	this.DRAGDATA.startscroll = null;
	this.PARTS.LAYOUT.style.cursor = '';
	document.onmousemove = null;
	document.onmouseup = null;
}

JCTimeManReport.prototype.changeMonth = function(dir, bSkipQuery)
{
	this.SETTINGS.DATE_START.setMonth(this.SETTINGS.DATE_START.getMonth() + dir);
	BX('bx_goto_date').value = BX.message('FORMAT_DATE').replace('YYYY', this.SETTINGS.DATE_START.getFullYear()).replace('MM',  this.SETTINGS.DATE_START.getMonth()+1).replace('DD', this.SETTINGS.DATE_START.getDate());
	BX('tm_datefilter_title', true).innerHTML = this.SETTINGS.MONTHS[this.SETTINGS.DATE_START.getMonth()] + ' ' + this.SETTINGS.DATE_START.getFullYear();

	if (!bSkipQuery)
		this.Page(1);
}

JCTimeManReport.prototype.Filter = function(bClear)
{
	var dpt = '',
		show_all = 'Y';

	if (this.SETTINGS.FILTER.department)
	{
		if (!bClear)
		{
			dpt = this.SETTINGS.FILTER.department.value;
			show_all = this.SETTINGS.FILTER.show_all.value;
		}

		if (dpt != this.FILTER.DEPARTMENT || show_all != this.FILTER.SHOW_ALL)
		{
			this.FILTER.DEPARTMENT = dpt;
			this.FILTER.SHOW_ALL = show_all;

			this.Page(1);
		}
	}

	if (this.SETTINGS.FILTER.department.value)
		BX.removeClass(this.SETTINGS.FILTER.department.parentNode, 'inactive');
	else
		BX.addClass(this.SETTINGS.FILTER.department.parentNode, 'inactive');

	if (this.SETTINGS.FILTER.show_all.value == 'N')
		BX.removeClass(this.SETTINGS.FILTER.show_all.parentNode, 'inactive');
	else
		BX.addClass(this.SETTINGS.FILTER.show_all.parentNode, 'inactive');
}

JCTimeManReport.prototype.Page = function(page)
{
	page = page || this.page;
	if (page < 0) page = 1;
	this.page = page;

	this.loadData();
}

JCTimeManReport.prototype.loadData = function()
{
	BX.timeman.showWait(this.DIV);

	var query_data = {
		ts: parseInt(this.SETTINGS.DATE_START.valueOf()/1000),
		show_all: this.FILTER.SHOW_ALL,
		department: this.FILTER.DEPARTMENT,
		page: this.page
	};

	this.setFilterHash(query_data);
	BX.timeman_query('admin_data', query_data, BX.proxy(this.setData, this));
}

JCTimeManReport.prototype.setFilterHash = function(data)
{
	var hash_str = '!/', hash_data = [];

	if (data.ts)
		hash_data.push('ts:' + data.ts);
	if (data.show_all && data.show_all == 'N')
		hash_data.push('show_all:' + data.show_all);
	if (data.department && data.department > 0)
		hash_data.push('department:' + data.department);
	if (data.page && data.page > 1)
		hash_data.push('page:' + data.page);

	if (!this.SETTINGS.FILTER.stats.checked)
		hash_data.push('stats:0');
	if (this.SETTINGS.FILTER.additional.checked)
		hash_data.push('additional:1');

	hash_str += hash_data.join('|');
	window.location.hash = hash_str;
}

JCTimeManReport.prototype.setFilterHashParam = function(param, value)
{
	var h = window.location.hash, pos = h.indexOf(param+':');
	if (pos >= 0)
		h = h.replace(new RegExp(param + ':[^|]*[|]{0,1}'), '');

	var t = h.substring(h.length-1,h.length);
	h += (t == '/' || t == '|') ? '' : '|';
	h += param+':'+value;

	window.location.hash = h;
}

JCTimeManReport.prototype.loadFilterHash = function()
{
	var hash_str = window.location.hash;

	if (hash_str.substring(0, 1) == '#')
		hash_str = hash_str.substring(1, hash_str.length);

	if (hash_str.length > 0 && hash_str.substring(0, 2) == '!/')
	{
		var data = hash_str.substring(2, hash_str.length).split('|'), i, param;
		for (i=0; i<data.length; i++)
		{
			param = data[i].split(':');
			switch(param[0])
			{
				case 'ts':
					var new_date_start = new Date(param[1] * 1000),
						dir = 0;

					dir += new_date_start.getMonth() - this.SETTINGS.DATE_START.getMonth();
					dir += 12 * (new_date_start.getYear() - this.SETTINGS.DATE_START.getYear());

					this.changeMonth(dir, true);
				break;
				case 'show_all':
					this.SETTINGS.FILTER.show_all.value = this.FILTER.SHOW_ALL = param[1] == 'N' ? 'N' : 'Y';
					BX.removeClass(this.SETTINGS.FILTER.show_all.parentNode, 'inactive')
				break;
				case 'department':
					this.SETTINGS.FILTER.department.value = this.FILTER.DEPARTMENT = parseInt(param[1]);
					BX.removeClass(this.SETTINGS.FILTER.department.parentNode, 'inactive');
				break;
				case 'page':
					this.page = parseInt(param[1]);
				break;
				case 'stats':
					var v = !!parseInt(param[1]);
					this.SETTINGS.FILTER.stats.checked = v;
					this.toggleStats(v);
				break;
				case 'additional':
					var v = !!parseInt(param[1]);
					this.SETTINGS.FILTER.additional.checked = v;
					this.toggleAdditions(v);
				break;
			}
		}
	}
}

JCTimeManReport.prototype.clearData = function()
{
	if (this.SETTINGS_ENABLED)
	{
		this.InitSettingMode();
		this.DATA_SETTINGS = null;
	}

	for (var i=0, l=this.arCellObjects.length; i<l; i++)
		this.arCellObjects[i].Clear();

	this.PARTS.LAYOUT_COLS = {NAME: null, DATA: null};
	this.PARTS.SCROLLERS = {LEFT: null, RIGHT: null};
	this.PARTS.TODAY_CELL = null;

	BX.cleanNode(this.PARTS.LAYOUT);
}

JCTimeManReport.prototype.setData = function(data)
{
	this.clearData();

	this.HINTS = [];
	this.DATA_SETTINGS = null;

	this.DATA = data;
	this._createLayout();

	BX.setUnselectable(this.PARTS.LAYOUT);
	BX.setUnselectable(this.PARTS.LAYOUT_COLS.DATA);

	BX.timeman.closeWait(this.DIV);

	setTimeout(BX.proxy(this.loadAbsence, this), 10);
	this.PARTS.NAV.innerHTML = this.DATA.NAV;
}

JCTimeManReport.prototype.onEntryReloaded = function(params)
{
	if (this.TOTALS['USER_' + params.USER_ID])
	{
		var TOTAL_VALUES = {TOTAL: 0, TOTAL_DAYS: 0, TOTAL_VIOLATIONS: 0, TOTAL_INACTIVE: 0};
		BX.onCustomEvent(this, 'onRecountTotals', [params.USER_ID, TOTAL_VALUES, params.ROW]);

		for (var i = 0; i < this.TOTALS['USER_' + params.USER_ID].length; i++)
		{
			var obTotals = this.TOTALS['USER_' + params.USER_ID][i];
			obTotals.TOTAL.innerHTML = BX.timeman.formatWorkTime(TOTAL_VALUES.TOTAL);
			obTotals.TOTAL_DAYS.innerHTML = '<span class="bx-days">' + parseInt(TOTAL_VALUES.TOTAL_DAYS) + '</span>' + (TOTAL_VALUES.TOTAL_INACTIVE > 0 ? '<span class="bx-days-inactive">(' + parseInt(TOTAL_VALUES.TOTAL_INACTIVE) + ')</span>' : '');
			obTotals.TOTAL_VIOLATIONS.innerHTML = Math.round(TOTAL_VALUES.TOTAL_DAYS > 0 ? (100 * TOTAL_VALUES.TOTAL_VIOLATIONS/TOTAL_VALUES.TOTAL_DAYS) : 0) + '%';
			obTotals.TOTAL_VIOLATIONS.title = TOTAL_VALUES.TOTAL_VIOLATIONS + ' / ' + TOTAL_VALUES.TOTAL_DAYS
		}
	}
}

JCTimeManReport.prototype.__nullifyDate = function(date, bDay)
{
	date = date || (new Date());
	date.setHours(0);date.setMinutes(0);date.setSeconds(0);date.setMilliseconds(0);
	if (!!bDay) date.setDate(1);

	return date;
}

JCTimeManReport.prototype.loadAbsence = function()
{
	var TS_START = (this.__nullifyDate(this.SETTINGS.DATE_START, true)).valueOf();
	var TS_FINISH = new Date(TS_START);
	TS_FINISH.setMonth(TS_FINISH.getMonth()+1);

	var url =  '/bitrix/components/bitrix/intranet.absence.calendar/ajax.php?MODE=GET&TS_START=' + parseInt(TS_START.valueOf()/1000) + '&TS_FINISH=' + (parseInt(TS_FINISH.valueOf()/1000)-1) + '&SHORT_EVENTS=N&USERS_ALL=N&current_data_id=1111&site_id=' + this.SETTINGS.SITE_ID + '&sessid=' + BX.bitrix_sessid() + '&rnd=' + Math.random();

	BX.timeman.showWait(this.DIV);

	window.jsBXAC = {
		SetData: BX.proxy(this.setAbsenceData, this)
	};
	BX.loadScript(url);
}

JCTimeManReport.prototype.setAbsenceData = function(data)
{
	if (!this.PARTS.LAYOUT_COLS.DATA.firstChild)
		return;

	for (var i=0; i<data.length; i++)
	{
		var rows = BX.findChildren(this.PARTS.LAYOUT_COLS.DATA.firstChild.tBodies[0],
			{tag: 'TR', property: {BXUSERID: data[i].ID}
		}), row = null;

		if (!!rows && rows.length > 0)
		{
			for (var q=0,l=rows.length;q<l;q++)
			{
				row = rows[q];

				var celldelta=0, cur_m = this.SETTINGS.DATE_START.getMonth(), busy_dates = [],
					arNewData = [], j, k;
				for (j=0; j<data[i].DATA.length; j++)
				{
					var bFound = false;
					for (k = 0; k < arNewData.length; k++)
					{
						if (arNewData[k].DATE_ACTIVE_FROM >= data[i].DATA[j].DATE_ACTIVE_FROM
								&& arNewData[k].DATE_ACTIVE_TO <= data[i].DATA[j].DATE_ACTIVE_TO)
						{
							bFound = true;
							arNewData[k] = data[i].DATA[j];
						}
						else if (arNewData[k].DATE_ACTIVE_FROM <= data[i].DATA[j].DATE_ACTIVE_FROM
								&& arNewData[k].DATE_ACTIVE_TO >= data[i].DATA[j].DATE_ACTIVE_TO)
						{
							bFound = true;
						}
						else if (arNewData[k].DATE_ACTIVE_FROM > data[i].DATA[j].DATE_ACTIVE_FROM
								&& arNewData[k].DATE_ACTIVE_FROM <= data[i].DATA[j].DATE_ACTIVE_TO)
						{
							data[i].DATA[j].DATE_ACTIVE_TO = arNewData[k].DATE_ACTIVE_FROM-86400;
						}
						else if (arNewData[k].DATE_ACTIVE_TO >= data[i].DATA[j].DATE_ACTIVE_FROM
								&& arNewData[k].DATE_ACTIVE_TO < data[i].DATA[j].DATE_ACTIVE_TO)
						{
							data[i].DATA[j].DATE_ACTIVE_FROM = parseInt(arNewData[k].DATE_ACTIVE_TO)+86400;
						}

						if (data[i].DATA[j].DATE_ACTIVE_FROM > data[i].DATA[j].DATE_ACTIVE_TO)
							bFound = true;
					}

					if (!bFound)
						arNewData.push(data[i].DATA[j]);
				}

				for (j=0; j < arNewData.length-1; j++)
				{
					for (k=j+1; k<arNewData.length; k++)
					{
						if (arNewData[k].DATE_ACTIVE_FROM < arNewData[j].DATE_ACTIVE_FROM)
						{
							var tmp = arNewData[k];
							arNewData[k] = arNewData[j];
							arNewData[j] = tmp;
						}
					}
				}

				data[i].DATA = arNewData;

				for (j=0; j<data[i].DATA.length; j++)
				{
					var date_start = new Date((data[i].DATA[j].DATE_ACTIVE_FROM - BX.message('USER_TZ_OFFSET')) * 1000),
						date_finish = new Date((data[i].DATA[j].DATE_ACTIVE_TO - BX.message('USER_TZ_OFFSET')) * 1000);
					if (date_start.getMonth() < this.SETTINGS.DATE_START.getMonth() || date_start.getFullYear() < this.SETTINGS.DATE_START.getFullYear())
						date_start = new Date(this.SETTINGS.DATE_START.valueOf());

					busy_dates.push([data[i].DATA[j].DATE_ACTIVE_FROM, data[i].DATA[j].DATE_ACTIVE_TO]);

					var cell = null,cellcnt = 0;
					while (date_start.getMonth() == cur_m && date_start.valueOf() <= date_finish.valueOf())
					{
						var day = date_start.getDate(), k;

						if (!cell && !row.cells[day-1-celldelta].bxentry)
						{
							cell = row.cells[day-1-celldelta];
						}

						if (!row.cells[day-1-celldelta].bxentry)
						{
							cellcnt++;
						}
						else if (cell)
						{
							BX.addClass(row.cells[day-1-celldelta], 'bx-tm-absent bx-tm-absent-' + data[i].DATA[j].TYPE);

							cell.colSpan = cellcnt;
							cell.innerHTML = '<span style="width: ' + (75*cellcnt - 10) + 'px; display: block; white-space: nowrap; overflow: hidden; ">' + BX.util.htmlspecialchars(data[i].DATA[j].NAME) + '</span>';
							cell.style.textAlign = 'left';
							cell.style.paddingRight = '0px';

							celldelta += cellcnt-1;

							BX.addClass(cell, 'bx-tm-absent bx-tm-absent-' + data[i].DATA[j].TYPE);

							for (k=1; k<cellcnt; k++)
							{
								row.deleteCell(cell.cellIndex+1);
							}

							cell = null;cellcnt = 0;
						}
						else
						{
							BX.addClass(row.cells[day-1-celldelta], 'bx-tm-absent bx-tm-absent-' + data[i].DATA[j].TYPE);
						}

						date_start.setDate(day+1);
					}

					if (cell)
					{
						cell.colSpan = cellcnt;
						cell.innerHTML = '<span style="width: ' + (75*cellcnt - 10) + 'px; display: block; white-space: nowrap; overflow: hidden; ">' + BX.util.htmlspecialchars(data[i].DATA[j].NAME) + '</span><div class="bx-tm-view-arrival">&nbsp;</div><div class="bx-tm-view-departure">&nbsp;</div>';
						cell.style.textAlign = 'left';
						cell.style.paddingRight = '0px';

						celldelta += cellcnt-1;

						BX.addClass(cell, 'bx-tm-absent bx-tm-absent-' + data[i].DATA[j].TYPE);

						for (k=1; k<cellcnt; k++)
						{
							row.deleteCell(cell.cellIndex+1);
						}

						cell = null;cellcnt = 0;
					}
				}
			}
		}
	}

	BX.timeman.closeWait(this.DIV);
}

JCTimeManReport.prototype.toggleStats = function(v)
{
	var d = this.PARTS.LAYOUT_COLS.DATA,
		scrollRight = !!d ? d.scrollLeft + d.offsetWidth : 0;

	BX[v ? 'removeClass' : 'addClass'](this.PARTS.LAYOUT, 'bx-tm-wide-mode');

	if (!!d)
		d.scrollLeft = scrollRight - d.offsetWidth;
}

JCTimeManReport.prototype.toggleAdditions = function(v)
{
	var d = this.PARTS.LAYOUT_COLS.DATA,
		scrollLeft = !!d ? d.scrollLeft : 0;

	BX[v ? 'removeClass' : 'addClass'](this.PARTS.LAYOUT, 'bx-tm-additions-disabled');

	if (!!d)
		d.scrollLeft = scrollLeft;
}

JCTimeManReport.prototype.InitSettingMode = function(button)
{
	this.SETTINGS_BUTTON = button || this.SETTINGS_BUTTON;

	BX.toggleClass(this.SETTINGS_BUTTON, 'tm-settings-item-active');
	this.SETTINGS_ENABLED = BX.hasClass(this.SETTINGS_BUTTON, 'tm-settings-item-active');

	if (this.SETTINGS_ENABLED)
	{
		BX.addClass(this.DIV, 'tm-settings-enabled');

		if (this.DATA_SETTINGS === null)
		{
			var i;

			query_data = {
				'DEPARTMENTS': [],
				'USERS': []
			};

			for (i = 0; i < this.DATA.USERS.length; i++)
				query_data['USERS'].push(this.DATA.USERS[i].ID);
			for (i = 0; i < this.DATA.DEPARTMENTS.length; i++)
				query_data['DEPARTMENTS'].push(this.DATA.DEPARTMENTS[i].ID);

			BX.timeman_query('admin_data_settings', query_data, BX.proxy(this._InitSettingMode, this));
		}
		else
		{
			this._InitSettingMode();
		}

	}
	else
	{
		BX.removeClass(this.DIV, 'tm-settings-enabled');
		for (var j=0; j < this.HINTS.length; j++)
		{
			this.HINTS[j].style.display = '';

			BX.unbind(this.HINTS[j], 'click', BX.proxy(this.Settings, this));

			if (this.HINTS[j].BXHINT)
				this.HINTS[j].BXHINT.enable();

			BX.removeClass(this.HINTS[j], 'bx-no-settings-light');
			BX.removeClass(this.HINTS[j], 'bx-has-settings-light');
		}
	}
}

JCTimeManReport.prototype._InitSettingMode = function(settings)
{
	var i, j, k;
	this.DATA_SETTINGS = settings || this.DATA_SETTINGS;

	for (i=0; i<this.DATA_SETTINGS.USERS.length; i++)
	{
		for (j=0; j < this.HINTS.length; j++)
		{
			if (this.DATA_SETTINGS.USERS[i].ID == this.HINTS[j].BXUSERID)
			{
				this.HINTS[j].style.display = 'inline-block';
				BX.bind(this.HINTS[j], 'click', BX.proxy(this.Settings, this));

				if (this.HINTS[j].BXHINT)
					this.HINTS[j].BXHINT.disable();

				var className = 'bx-no-settings-light';

				if (this.HINTS[j].BXSETTINGSFORM)
					this.DATA_SETTINGS.USERS[i].SETTINGS = this.HINTS[j].BXSETTINGSFORM.data.SETTINGS;

				for (k in this.DATA_SETTINGS.USERS[i].SETTINGS)
				{
					if (this.DATA_SETTINGS.USERS[i].SETTINGS[k] !== '')
					{
						className = 'bx-has-settings-light';
						break;
					}
				}

				BX.addClass(this.HINTS[j], className);
			}
		}
	}

	for (i=0; i<this.DATA_SETTINGS.DEPARTMENTS.length; i++)
	{
		for (j=0; j < this.HINTS.length; j++)
		{
			if (this.DATA_SETTINGS.DEPARTMENTS[i].ID == this.HINTS[j].BXDPTID)
			{
				this.HINTS[j].style.display = 'inline-block';
				BX.bind(this.HINTS[j], 'click', BX.proxy(this.SettingsDpt, this));

				var className = 'bx-no-settings-light';

				if (this.HINTS[j].BXSETTINGSFORM)
					this.DATA_SETTINGS.DEPARTMENTS[i].SETTINGS = this.HINTS[j].BXSETTINGSFORM.data.SETTINGS;

				for (k in this.DATA_SETTINGS.DEPARTMENTS[i].SETTINGS)
				{
					if (this.DATA_SETTINGS.DEPARTMENTS[i].SETTINGS[k] !== '')
					{
						className = 'bx-has-settings-light';
						break;
					}
				}

				BX.addClass(this.HINTS[j], className);
			}
		}
	}
}

JCTimeManReport.prototype.Settings = function(e)
{
	var USER_ID = BX.proxy_context.BXUSERID;
	if (USER_ID > 0)
	{
		for (var i = 0; i < this.DATA_SETTINGS.USERS.length; i++)
		{
			if (this.DATA_SETTINGS.USERS[i].ID == USER_ID)
			{
				if (!BX.proxy_context.BXSETTINGSFORM)
				{
					BX.proxy_context.BXSETTINGSFORM = new JCTimeManSettingsForm({
						parent: this,
						node: BX.proxy_context,
						data: this.DATA_SETTINGS.USERS[i],
						data_default: this.DATA_SETTINGS.DEFAULTS,
						source: 'user'
					});
				}

				BX.proxy_context.BXSETTINGSFORM.Show();

				break;
			}
		}
	}
}

JCTimeManReport.prototype.SettingsDpt = function(e)
{
	var DPT_ID = BX.proxy_context.BXDPTID;
	if (DPT_ID > 0)
	{
		for (var i = 0; i < this.DATA_SETTINGS.DEPARTMENTS.length; i++)
		{
			if (this.DATA_SETTINGS.DEPARTMENTS[i].ID == DPT_ID)
			{
				if (!BX.proxy_context.BXSETTINGSFORM)
				{
					this.DATA_SETTINGS.DEPARTMENTS[i].TOP_SECTION = false;
					for (var j = 0; j < this.DATA.DEPARTMENTS.length; j++)
					{
						if (this.DATA.DEPARTMENTS[j].ID == DPT_ID)
						{
							this.DATA_SETTINGS.DEPARTMENTS[i].TOP_SECTION = !!this.DATA.DEPARTMENTS[j].TOP_SECTION;
							break;
						}
					}

					BX.proxy_context.BXSETTINGSFORM = new JCTimeManSettingsForm({
						parent: this,
						node: BX.proxy_context,
						data: this.DATA_SETTINGS.DEPARTMENTS[i],
						data_default: this.DATA_SETTINGS.DEFAULTS,
						source: 'department'
					});
				}

				BX.proxy_context.BXSETTINGSFORM.Show();

				break;
			}
		}
	}
}

/**************************************************************************/

function JCTimeManSettingsForm(params)
{
	this.data = params.data;
	this.data_default = params.data_default;
	this.icon = params.node;
	this.source = params.source || 'user';
	this.parent = params.parent;
	this.uniqid = parseInt(Math.random() * 100000)

	this._checkData();

	this.popup = BX.PopupWindowManager.create(
			"setting_form_"+this.uniqid,
			this.icon,
			{
				autoHide : true,
				lightShadow : true,
				angle:{position:"left",offset:20},
				offsetTop:-35,
				offsetLeft:20,
				closeIcon : {right: "12px", top: "10px"},
				bindOptions:{
					forceTop:true,
					forceLeft:true
				}
			}
		);

	this.popup.setContent(this.Content());
	this.popup.setButtons(this.Buttons());

	this.bChanged = false;
}

JCTimeManSettingsForm.prototype._checkData = function()
{
	if (this.data.SETTINGS_ALL.UF_TIMEMAN === 'Y')
		this.data.SETTINGS_ALL.UF_TIMEMAN = true;
	else if (this.data.SETTINGS_ALL.UF_TIMEMAN === 'N')
		this.data.SETTINGS_ALL.UF_TIMEMAN = false;
	if (this.data.SETTINGS_ALL.UF_TM_FREE === 'Y')
		this.data.SETTINGS_ALL.UF_TM_FREE = true;
	else if (this.data.SETTINGS_ALL.UF_TM_FREE === 'N')
		this.data.SETTINGS_ALL.UF_TM_FREE = false;
	if (this.data.SETTINGS.UF_TIMEMAN === 'Y')
		this.data.SETTINGS.UF_TIMEMAN = true;
	else if (this.data.SETTINGS.UF_TIMEMAN === 'N')
		this.data.SETTINGS.UF_TIMEMAN = false;
	if (this.data.SETTINGS.UF_TM_FREE === 'Y')
		this.data.SETTINGS.UF_TM_FREE = true;
	else if (this.data.SETTINGS.UF_TM_FREE === 'N')
		this.data.SETTINGS.UF_TM_FREE = false;

	if (this.data.TOP_SECTION)
	{
		for (var i in this.data_default)
		{
			if (this.data.SETTINGS[i] === '')
				this.data.SETTINGS[i] = this.data_default[i];
		}
	}
}

JCTimeManSettingsForm.prototype.Content = function()
{
	this.DIV = BX.create('DIV', {
		props: {className: 'period-setting-main'},
		children: [BX.create('FORM')]
	});

	var UF_TIMEMAN_container, UF_TM_FREE_container;

	this.needParamsHint = false;
	this.DIV.firstChild.appendChild(
		this._Control({
			name: 'UF_TIMEMAN',
			label: BX.message('JS_CORE_TM'),
			input_type: 'select',
			value_type: 'boolean',
			input_values: {
				'Y': BX.message('JS_CORE_TMR_ON'),
				'N': BX.message('JS_CORE_TMR_OFF')
			},
			callback: BX.delegate(function() {
				var ob = BX.proxy_context;
				if (ob.value == 'N' || ob.value == '' && !this.data.SETTINGS_ALL.UF_TIMEMAN)
					BX.hide(UF_TIMEMAN_container);
				else
					BX.show(UF_TIMEMAN_container);
			}, this)
		})
	);

	UF_TIMEMAN_container = this.DIV.firstChild.appendChild(BX.create('DIV', {
		children: [
			this._Control({
				name: 'UF_TM_FREE',
				label: this.parent.SETTINGS.LANG.HINT_FREE,
				input_type: 'select',
				value_type: 'boolean',
				input_values: {
					'Y': BX.message('JS_CORE_TMR_ON'),
					'N': BX.message('JS_CORE_TMR_OFF')
				},
				callback: BX.delegate(function() {
					var ob = BX.proxy_context;
					if (ob.value == 'Y' || ob.value == '' && this.data.SETTINGS_ALL.UF_TM_FREE)
						BX.hide(UF_TM_FREE_container);
					else
						BX.show(UF_TM_FREE_container);
				}, this)
			}),
			(UF_TM_FREE_container = BX.create('DIV', {children: [
				this._Control({
					name: 'UF_TM_MAX_START',
					label: this.parent.SETTINGS.LANG.HINT_MAX_START,
					input_type: 'clock',
					value_type: 'time'
				}),
				this._Control({
					name: 'UF_TM_MIN_FINISH',
					label: this.parent.SETTINGS.LANG.HINT_MIN_FINISH,
					input_type: 'clock',
					value_type: 'time'
				}),
				this._Control({
					name: 'UF_TM_MIN_DURATION',
					label: this.parent.SETTINGS.LANG.HINT_MIN_DURATION,
					input_type: 'text',
					value_type: 'time'
				}),
				this._Control({
					name: 'UF_TM_ALLOWED_DELTA',
					label: this.parent.SETTINGS.LANG.HINT_ALLOWED_DELTA,
					input_type: 'text',
					value_type: 'time'
				}),
				this._Control({
					name: 'UF_TM_REPORT_REQ',
					label: this.parent.SETTINGS.LANG.HINT_REPORT_REQ,
					input_type: 'select',
					value_type: 'enum',
					input_values: {
						'Y': this.parent.SETTINGS.LANG.HINT_REPORT_REQ_Y,
						'N': this.parent.SETTINGS.LANG.HINT_REPORT_REQ_N,
						'A': this.parent.SETTINGS.LANG.HINT_REPORT_REQ_A
					}
				})
			]}))
		]
	}));

	if (this.needParamsHint)
	{
		UF_TIMEMAN_container.appendChild(BX.create('SPAN', {
			html: '<sup>*</sup> ' + BX.message('JS_CORE_TMR_SN')
		}))
	}

	if (!this.data.SETTINGS_ALL['UF_TIMEMAN'])
		BX.hide(UF_TIMEMAN_container);
	if (this.data.SETTINGS_ALL['UF_TM_FREE'])
		BX.hide(UF_TM_FREE_container);

	return this.DIV;
}

JCTimeManSettingsForm.prototype.Buttons = function()
{
	var buttons = [];

	this.SAVEBUTTON = new BX.PopupWindowButton({
		text: BX.message('JS_CORE_WINDOW_SAVE'),
		events: {click: BX.proxy(this.Save,this)}
	});

	window.SAVEBUTTON = this.SAVEBUTTON;

	buttons.push(this.SAVEBUTTON);

	buttons.push(new BX.PopupWindowButtonLink({
		text: BX.message('JS_CORE_WINDOW_CLOSE'),
		className: "popup-window-button-link-cancel",
		events: {click : BX.proxy(this.popup.close, this.popup)}
	}));

	return buttons
}

JCTimeManSettingsForm.prototype.Show = function()
{
	this.popup.show();
}

JCTimeManSettingsForm.prototype.Save = function()
{
	if (this.bChanged)
	{
		var form = this.DIV.firstChild,
			arFields = ['UF_TIMEMAN', 'UF_TM_FREE', 'UF_TM_ALLOWED_DELTA', 'UF_TM_MAX_START', 'UF_TM_MIN_DURATION', 'UF_TM_MIN_FINISH', 'UF_TM_REPORT_REQ'];

		var data = {ID: this.data.ID, source: this.source};
		for (var i=0; i < arFields.length; i++)
		{
			data[arFields[i]] = form[arFields[i]]
				? (
					form[arFields[i]].type == 'checkbox'
					? (form[arFields[i]].checked ? 'Y' : 'N')
					: (
						arFields[i] == 'UF_TM_ALLOWED_DELTA'
						? BX.timeman.unFormatTime(form[arFields[i]].value)
						: form[arFields[i]].value
					)
				)
				: '';
		}

		BX.timeman_query('admin_data_settings', data, BX.delegate(this._Save, this));

		this.bChanged = false;
		this.SAVEBUTTON.setClassName("");
	}
}

JCTimeManSettingsForm.prototype._Save = function(data)
{
	data.TOP_SECTION = this.data.TOP_SECTION;
	this.data = data;
	//this.popup.close();

	this._checkData();

	var className = 'bx-no-settings-light';

	for (k in this.data.SETTINGS)
	{
		if (this.data.SETTINGS[k] !== '')
		{
			className = 'bx-has-settings-light';
			break;
		}
	}

	BX.removeClass(this.icon, 'bx-no-settings-light');
	BX.removeClass(this.icon, 'bx-has-settings-light');
	BX.addClass(this.icon, className);

	if (this.icon.BXHINT)
		this.icon.BXHINT.Destroy();

	this.parent._createHint(this.icon, data.SETTINGS_ALL);

	if (this.icon.BXHINT)
		this.icon.BXHINT.disable();

	this.popup.setContent(this.Content());
}

/*
bSkipInherit = true - show input with inherited value event if current value doesn't exist
bSkipInherit = false - show text with inherited value even if current value exists
bSkipInherit = undefined  - show if;
*/

JCTimeManSettingsForm.prototype._Control = function(params, bSkipInherit)
{
	var c = BX.create('DIV'), v = this.data.SETTINGS[params.name], bInherit = !!bSkipInherit, uniqid_control = parseInt(Math.random() * 100000);

	if (!!params.label)
	{
		c.appendChild(BX.create('LABEL', {
			props:
			{
				className: 'period-setting-label'
			},
			style: {display: 'block'},
			text: params.label
		}));
	}

	if (params.input_type === 'select')
	{
		var s = c.appendChild(BX.create('SELECT', {
			props: {
				name: params.name,
				className: 'period-setting-select'
			},
			events: {
				change:
					BX.delegate(function(e) {
						this._OnChange();
						if (params.callback)
							return params.callback.apply(BX.proxy_context, arguments);
					}, this)
			}
		}));

		if (!this.data.TOP_SECTION)
		{
			try{s.add(new Option(BX.message('JS_CORE_TMR_INHERIT'), ''), null);}catch(e){s.add(new Option(BX.message('JS_CORE_TMR_INHERIT'), ''),-1);}
		}

		for (var i in params.input_values)
		{
			var opt = new Option(params.input_values[i], i);
			opt.selected = params.value_type == 'boolean' ? (i=='Y'&&v===true||i=='N'&&v===false) : v==i

			try{s.add(opt, null);}catch(e){s.add(opt, -1);}
		}


	}
	else
	{
		if (v === '' || bSkipInherit === false)
		{
			bInherit = true;
			v = this.data.SETTINGS_ALL[params.name];
			if (v === '')
				v = this.data_default[params.name];
		}

		if (bSkipInherit === true)
			bInherit = false;

		if (bInherit)
		{
			var value_text = '', bHint = false;
			switch(params.value_type)
			{
				case 'boolean':
					value_text = !!v ? BX.message('JS_CORE_TMR_ON') : BX.message('JS_CORE_TMR_OFF');
				break;

				case 'time':
					if (typeof v == 'undefined')
					{
						bHint = this.needParamsHint = true;

						value_text = '- - - <sup>*</sup>';
					}
					else
					{
						value_text = /^\d{1,2}:\d\d\s*(am|pm){0,1}$/ig.test(v) ? v : BX.timeman.formatTime(v, false, params.input_type != 'clock');
					}
				break;

				case 'enum':
					value_text = params.input_values[v];
				break;

				default:
					value_text = v;
			}

			c.appendChild(BX.create('DIV', {
				children: [
					BX.create('SPAN', bHint
						? {html: '<i>' + value_text + '</i>'}
						: {
							props: {className: 'period-setting-value'},
							text: value_text,
							events: {
								click: BX.delegate(function() {
									c.parentNode.replaceChild(this._Control(params, true), c)
								}, this)
							}
						}
					)
				]
			}));
		}
		else
		{
			var control = [];
			switch(params.input_type)
			{
				case 'checkbox':
					control = [
						BX.create('INPUT', {
							props: {
								type: 'checkbox',
								name: params.name,
								id: 'tm_setting_' + params.name + '_' + this.uniqid,
								value: 'Y',
								defaultChecked: !!v,
								checked: !!v
							},
							events: {
								click: BX.delegate(function(e) {
									this._OnChange();
									if (params.callback)
										return params.callback.apply(BX.proxy_context, arguments);
								}, this)
							}
						}),
						BX.create('LABEL', {
							props: {
								htmlFor: 'tm_setting_' + params.name + '_' + this.uniqid
							},
							text: BX.message('JS_CORE_TMR_ON')
						})
					];
				break;

				case 'clock':
					control = [
						BX.create('SPAN', {
							props: {className: 'tm-clock-area'},
							children: [
								BX.create('SPAN', {props: {
									className: 'tm-dashboard-clock tm-icon-clock'
								}}),
								BX.create('INPUT', {
									props: {
										className: 'tm-clock-select' + (BX.isAmPmMode() ? ' tm-clock-select-ampm' : ''),
										name: params.name,
										value: params.value_type == 'time'
											? BX.timeman.formatTime(v)
											: v,
										readOnly: true,
										BXUNIQID: uniqid_control
									},
									events: {
										click: BX.proxy(this.ShowClock, this)
									}
								})
							]
						})
					];
				break;

				case 'select':
					var select = BX.create('SELECT', {props: {className: 'period-setting-select'}});

					for (var i in params.input_values)
					{
						var o = new Option(params.input_values[i], i);
						o.selected = i == v;
						try{select.add(o, null);}catch(e){select.add(o, -1);}
					}

					control = [select];
				break;

				default:
					control = [
						BX.create('INPUT', {
							props: {
								type: 'text',
								name: params.name,
								value: params.value_type == 'time'
									? BX.timeman.formatTime(v, false, true)
									: v
							},
							events: {
								change: BX.delegate(function(e) {
									this._OnChange();
									if (params.callback)
										return params.callback.apply(BX.proxy_context, arguments);
								}, this)
							}
						})
					];
			}

			if (!this.data.TOP_SECTION)
			{
				control.push(BX.create('A', {
					props: {className: 'settings-restore', title: BX.message('JS_CORE_TMR_INHERIT_T')},
					attrs: {href: 'javascript:void(0)'},
					events: {
						click: BX.delegate(function() {
							c.parentNode.replaceChild(this._Control(params, false), c);
							try{delete this.DIV.firstChild[params.name];}catch(e){} // hack: clear browser DOM cache
						}, this)
					}
				}));
			}

			BX.adjust(c, {children: control});
		}
	}

	if (typeof bSkipInherit != 'undefined')
		this._OnChange();

	return c;
}

JCTimeManSettingsForm.prototype.ShowClock = function(e)
{
	var node = BX.proxy_context;
	if(!node.BXCLOCK)
	{
		node.BXCLOCK = new BX.CTimeManClock({DIV: node}, {
			node: node,
			start_time: BX.timeman.unFormatTime(node.value),
			popup_id: 'tm_edit_d_'+node.name+'_'+node.BXUNIQID,
			clock_id: 'tm_edit_d_'+node.name+'_'+node.BXUNIQID,
			zIndex: 960,
			callback: BX.delegate(function(time)
			{
				node.value = time;
				node.BXCLOCK.closeWnd();
				this._OnChange();
			}, this)
		});
	}

	node.BXCLOCK.Show();
}

JCTimeManSettingsForm.prototype._OnChange = function(e)
{
	this.bChanged = true;
	this.SAVEBUTTON.setClassName("popup-window-button-accept");
}

/**************************************************************************/

function JCTimeManReportEntry(parent, cell, entry, settings)
{
	this.parent = parent;
	this.cell = cell;

	this.entry = entry;
	this.settings = settings;

	this.lang = this.parent.SETTINGS.LANG

	BX.cleanNode(this.cell);
	this.DIV = this.cell.appendChild(BX.create('DIV', {props:{className:'bx-tm-entry'}}));

	this.bFinished = false;
	this.bExpired = false;
	this.bDataLoaded = false;

	this.TIMER = {};
	this.WND = null;
	this.SAVEBUTTON = null;

	this.DATA_FOR_SAVE = [];

	this.arViews = BX.util.array_merge(['worktime'], this.parent.arViews);

	this.checkEntry();
	this.Redraw(true);

	BX.bind(this.cell, 'click', BX.proxy(this.Click, this));
	BX.addCustomEvent(this.parent, 'onRecountTotals', BX.proxy(this.onRecountTotals, this));
	BX.addCustomEvent('onEntryNeedReload', BX.proxy(this.onEntryNeedReload, this));

	this.bInit = false;
}

JCTimeManReportEntry.prototype.onRecountTotals = function(USER_ID, TOTALS, ROW)
{

	if ((this.entry.USER_ID == USER_ID) && !!this.entry.TIME_FINISH && ROW == this.cell.parentNode.rowIndex)
	{
		if (this.entry.ACTIVE)
		{
			TOTALS.TOTAL += parseInt(this.entry.DURATION);
			TOTALS.TOTAL_DAYS++;

			if (
				this.settings.UF_TM_MAX_START < this.entry.TIME_START
				|| this.settings.UF_TM_MIN_FINISH > this.entry.TIME_FINISH
				|| this.settings.UF_TM_MIN_DURATION > this.entry.DURATION
			)
			{
				TOTALS.TOTAL_VIOLATIONS++;
			}
		}
		else
		{
			TOTALS.TOTAL_INACTIVE++;
		}
	}
}

JCTimeManReportEntry.prototype.checkEntry = function()
{
	this.entry.TIME_START = parseInt(this.entry.TIME_START);
	this.entry.TIME_FINISH = parseInt(this.entry.TIME_FINISH);
	this.entry.DURATION = parseInt(this.entry.DURATION);

	this.date_start = new Date(this.entry.DATE_START * 1000);
	this.timezone_diff = (this.date_start.getHours() - Math.floor(this.entry.TIME_START/3600))*3600000;
	this.today = (new Date(this.parent.today.valueOf()/*-this.timezone_diff*/));

	this.entry.DAY = (new Date(this.date_start.valueOf()/* - this.timezone_diff*/));
	this.bFinished = !!this.entry.TIME_FINISH && !this.entry.PAUSED;
	this.bExpired = !this.bFinished && (
		this.entry.DAY.getDate() != this.today.getDate() || this.entry.DAY.getMonth() != this.today.getMonth() || this.entry.DAY.getYear() != this.today.getYear()
	);
}

JCTimeManReportEntry.prototype.onEntryNeedReload = function(data)
{
	if (this != BX.proxy_context && this.entry.ID == (BX.proxy_context.entry||data).ID)
	{
		this.entry = data;
		this.bDataLoaded = true;

		this.checkEntry();
		this.Redraw(true);
	}
}

JCTimeManReportEntry.prototype.Redraw = function(bDenyEvent)
{
	var q;

	BX.cleanNode(this.DIV);

	for (var i=0, l=this.arViews.length; i<l; i++)
	{
		var MODE = this.arViews[i];
		if (this.TIMER[MODE])
		{
			this.TIMER[MODE].Finish();
			this.TIMER[MODE] = null;
		}

		switch(MODE)
		{
			case 'worktime':
				if (this.bFinished)
				{
					this.DIV.appendChild(BX.create('DIV', {
						props: {className: 'bx-tm-view-' + MODE},
						text:BX.timeman.formatWorkTime(this.entry.DURATION)
					}));
				}
				else if (this.bExpired)
				{
					this.DIV.appendChild(BX.create('DIV', {
						props: {className: 'bx-tm-report-expired bx-tm-view-' + MODE},
						text:this.lang.EXP
					}));
				}
				else if (this.entry.PAUSED)
				{
					this.DIV.appendChild(BX.create('DIV', {
						props: {className: 'bx-tm-view-' + MODE},
						text:BX.timeman.formatWorkTime(this.entry.TIME_FINISH - this.entry.TIME_START-this.entry.TIME_LEAKS)
					}));
				}
				else
				{
					q = this.DIV.appendChild(BX.create('DIV', {
						props: {className: 'bx-tm-view-' + MODE}
					}));
					this.TIMER[MODE] = BX.timer(q, {dt: -this.entry.TIME_LEAKS*1000,from: this.date_start, display: 'worktime'});
				}

			break;
			case 'arrival':
				this.DIV.appendChild(BX.create('DIV', {
					props: {className: 'bx-tm-view-' + MODE},
					text:BX.timeman.formatTime(this.entry.TIME_START)
				}));

			break;
			case 'departure':

				if (this.bFinished)
					this.DIV.appendChild(BX.create('DIV', {
						props: {className: 'bx-tm-view-' + MODE},
						text:BX.timeman.formatTime(this.entry.TIME_FINISH)
					}));
				else if (this.bExpired)
					this.DIV.appendChild(BX.create('DIV', {
						props: {className: 'bx-tm-report-expired bx-tm-view-' + MODE},
						text:BX.isAmPmMode() ? '11:59 pm' : '23:59'
					}));
				else
				{
					q = this.DIV.appendChild(BX.create('DIV', {
						props: {className: 'bx-tm-view-' + MODE}
					}));
					this.TIMER[MODE] = BX.timer(q, {dt: -this.timezone_diff});
				}
			break;
		}
	}

	if (!this.entry.ACTIVE)
		BX.addClass(this.cell, 'bx-tm-report-inactive');
	else
		BX.removeClass(this.cell, 'bx-tm-report-inactive');

	if (this.entry.ACTIVATED)
		BX.addClass(this.cell, 'bx-tm-report-activated');
	else
		BX.removeClass(this.cell, 'bx-tm-report-activated');

	if (!bDenyEvent)
	{
		BX.onCustomEvent(this, 'onEntryNeedReload', [this.entry]);
	}

	BX.onCustomEvent(this, 'onTimeManEntryDraw', [this.entry]);
}

JCTimeManReportEntry.prototype.Click = function()
{
	BX.StartNotifySlider(this.entry.USER_ID, this.entry.ID, 0);

	// if (!this.bDataLoaded)
	// {
		// BX.timeman_query('admin_entry', {
			// ID: this.entry.ID
		// }, BX.proxy(this.Show, this));
	// }
	// else
	// {
		// this.Show();
	// }
}

JCTimeManReportEntry.prototype.Reset = function(data)
{
	if (!data || !data.ID)
		return;

	this.Show(data);
	this.Redraw();

	BX.onCustomEvent(this.parent, 'onEntryReloaded', [{ID: data.ID, USER_ID: data.USER_ID, ROW: this.cell.parentNode.rowIndex}]);
}

JCTimeManReportEntry.prototype.Show = function(data)
{
	if (null == this.WND || !!this.WND.bCleared)
	{
		this.WND = null;

		this.WND = new BX.CTimeManReportForm(
			window.BXTIMEMAN || {}, // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			{
				bind: this.cell.firstChild,
				node: this.cell.firstChild,
				mode: 'admin',
				data: data,
				offsetLeft: -100,
				parent_object: this
			}
		);
	}

	this.WND.Show();

	if (data)
	{
		if (data.INFO)
		{
			var tmp_data = BX.clone(data.INFO.INFO, true);
			tmp_data.ACTIVE = tmp_data.ACTIVE == 'Y';
			tmp_data.PAUSED = tmp_data.PAUSED == 'Y';
			tmp_data.ACTIVATED = tmp_data.ACTIVATED == 'Y';
			tmp_data.CAN_EDIT = data.INFO.CAN_EDIT == 'Y' ? 'Y' : 'N';

			data = tmp_data;
		}

		this.bDataLoaded = true;
		this.entry = data;
		this.checkEntry();

		this.Redraw();
	}
}

JCTimeManReportEntry.prototype.Approve = function()
{
	this.DATA_FOR_SAVE[this.DATA_FOR_SAVE.length] = {fld: 'approve', val: true};

	if (this.TIMEOUT_SAVE)
		clearTimeout(this.TIMEOUT_SAVE);

	this.saveData();

	BX.addClass(this.cell, 'bx-tm-report-activated');
}

JCTimeManReportEntry.prototype.getPopupContent = function()
{
	var cont = {}, row = null;
	this.DIV_CONTENT = BX.create('DIV', {
		props: {className: 'bx-tm-report-popup'},
		children: [
			BX.create('DIV', {
				props: {className: 'bx-tm-report-popup-title'},
				text: this.entry.DAY_START + ' ' + this.entry.NAME
			}),
			BX.create('DIV')
		]
	});

	var tabControl = new BX.CTimeManTabControl(this.DIV_CONTENT.lastChild);

	tabControl.addTab({
		id: 'time',
		title: BX.message('JS_CORE_TM_WD'),
		content: BX.create('DIV', {props: {className: 'bx-tm-report-worktime'}, children: [
			(cont.TIME_START = BX.create('DIV', {
				children: [
					BX.create('SPAN', {props: {className: 'bx-tm-report-caption'}, text: this.lang.CAPTION_ARRIVAL + ': '}),
					BX.create('SPAN', {
						props: {className: 'bx-tm-report-field', bx_tm_tag: 'TIME_START'},
						text: BX.timeman.formatTime(this.entry.TIME_START),
						events: {click: BX.proxy(this.Edit, this)}
					})
				]
			})),
			(cont.TIME_FINISH = BX.create('DIV', {
				children: [
					BX.create('SPAN', {props: {className: 'bx-tm-report-caption'}, text: this.lang.CAPTION_DEPARTURE + ': '}),
					BX.create('SPAN', {
						props: {className: 'bx-tm-report-field' + (this.bExpired ? ' bx-tm-report-field-expired' : ''), bx_tm_tag: 'TIME_FINISH'},
						html: this.entry.TIME_FINISH
							? BX.timeman.formatTime(this.entry.TIME_FINISH)
							: (this.bExpired ? (BX.isAmPmMode() ? '11:59 pm' : '23:59') : '&nbsp;'),
						events: {click: BX.proxy(this.Edit, this)}
					})
				]
			}))
		]})
	});

	if (!this.bExpired)
	{
		cont.TIME_FINISH.parentNode.appendChild(cont.DURATION = BX.create('DIV', {
			children: [
				BX.create('SPAN', {props: {className: 'bx-tm-report-caption'}, text: this.lang.CAPTION_DURATION + ': '}),
				BX.create('SPAN', {
					props: {className: 'bx-tm-report-field', bx_tm_tag: 'DURATION'},
					html: this.entry.DURATION > 0
						? BX.timeman.formatWorkTime(this.entry.DURATION)
						: (this.entry.PAUSED
							? BX.timeman.formatWorkTime(this.entry.TIME_FINISH-this.entry.TIME_START-this.entry.TIME_LEAKS)
							: BX.timeman.formatWorkTime(0)
						),
					events: this.entry.TIME_FINISH?{click: BX.proxy(this.EditWorkTime, this)}:null
				})
			]
		}));

		if (!this.entry.TIME_FINISH && this.entry.CAN_EDIT)
		{
			new BX.CHint({
				parent: cont.DURATION,
				hint: this.lang.DAY_NOT_FINISHED,
				show_timeout: 10, hide_timeout: 10
			});
		}
	}

	if (this.entry.REPORT || this.entry.TASKS && this.entry.TASKS.length > 0 || this.entry.EVENTS && this.entry.EVENTS.length > 0)
	{
		var q = null, w = null, report = null, i, l;
		tabControl.addTab({
			id: 'plan',
			title: BX.message('JS_CORE_TM_PLAN'),
			content: BX.create('DIV', {children: [
				(this.entry.REPORT ? BX.create('DIV', {
					props: {className: 'bx-tm-section'},
					children: [
						BX.create('DIV', {
							props: {className: 'tm-popup-section'},
							html: '<span class="tm-popup-section-left"></span><span class="tm-popup-section-text">' + BX.message('JS_CORE_TM_REPORT') + '</span><span class="tm-popup-section-right"></span>'
						}),
						(report = BX.create('DIV', {
							props: {className: 'bx-tm-popup-report'},
							html: '<p>' + (
								this.entry.REPORT
								? BX.util.trim(this.entry.REPORT).replace(/[\n]{2}/g, '</p><p>').replace(/\n/g, '<br />')
								: '&nbsp;'
							) + '</p>'
						}))
					]
				}) : null),
				(this.entry.TASKS && this.entry.TASKS.length > 0 ? BX.create('DIV', {
					props: {className: 'bx-tm-section bx-tm-popup-tasks'},
					children: [
						BX.create('DIV', {
							props: {className: 'tm-popup-section'},
							html: '<span class="tm-popup-section-left"></span><span class="tm-popup-section-text">' + BX.message('JS_CORE_TM_TASKS') + '</span><span class="tm-popup-section-right"></span>'
						}),
						(q = BX.create('OL', {
							props: {className: 'tm-popup-task-list'}
						}))
					]
				}) : null),
				(this.entry.EVENTS && this.entry.EVENTS.length > 0 ? BX.create('DIV', {
					props: {className: 'bx-tm-section bx-tm-popup-events'},
					children: [
						BX.create('DIV', {
							props: {className: 'tm-popup-section'},
							html: '<span class="tm-popup-section-left"></span><span class="tm-popup-section-text">' + BX.message('JS_CORE_TM_EVENTS') + '</span><span class="tm-popup-section-right"></span>'
						}),
						(w = BX.create('DIV', {
							props: {className: 'tm-popup-event-list tm-popup-events'}
						}))
					]
				}) : null)
			]})
		});

		if (this.entry.REPORT_FULL)
		{
			new BX.CHint({
				parent: report, hint: '<p>' + BX.util.trim(BX.util.htmlspecialchars(this.entry.REPORT_FULL)).replace(/[\n]{2}/g, '</p><p>').replace(/\n/g, '<br />') + '</p>',
				show_timeout: 100, hide_timeout: 90
			});
		}

		if (q)
		{
			for (i = 0, l = this.entry.TASKS.length; i<l; i++)
			{
				q.appendChild(BX.create('LI', {
					props: {
						className: 'tm-popup-task tm-popup-task-status-' + BX.timeman.TASK_SUFFIXES[this.entry.TASKS[i].STATUS],
						bx_task_id: this.entry.TASKS[i].ID
					},
					html: '<span class="tm-popup-task-icon"></span><a href="' + this.entry.TASKS[i].URL + '" target="_blank" class="tm-popup-task-name">' + BX.util.htmlspecialchars(this.entry.TASKS[i].TITLE) + '</a>'
				}));
			}
		}

		if (w)
		{
			for (i = 0, l = this.entry.EVENTS.length; i<l; i++)
			{
				w.appendChild(BX.create('DIV', {
					props: {
						className: 'tm-popup-event',
						bx_event_id: this.entry.EVENTS[i].ID
					},
					html: '<div class="tm-popup-event-datetime">\
<span class="tm-popup-event-time-start' + (this.entry.EVENTS[i].DATE_FROM_TODAY ? '' : ' tm-popup-event-time-passed') + '">'
						+ BX.timeman.formatTime(this.entry.EVENTS[i].TIME_FROM) +
'</span><span class="tm-popup-event-separator">-</span><span class="tm-popup-event-time-end' + (this.entry.EVENTS[i].DATE_TO_TODAY ? '' : ' tm-popup-event-time-passed') + '">'
						+ BX.timeman.formatTime(this.entry.EVENTS[i].TIME_TO) +
'</span></div><div class="tm-popup-event-name"><a href="' + this.entry.EVENTS[i].URL + '" target="_blank" class="tm-popup-event-text">' + this.entry.EVENTS[i].NAME + '</span></div>'
				}));
			}
		}

		if (!this.bInit && this.entry.ACTIVE && !this.bExpired)
			tabControl.selectTab('plan');
	}

	if (!this.entry.TIME_FINISH && !this.bExpired)
	{
		this.TIMER['WND_TIME_FINISH'] = BX.timer(cont.TIME_FINISH.lastChild, {dt: -this.timezone_diff});

		if (!this.entry.DURATION)
		{
			this.TIMER['WND_DURATION'] = BX.timer(cont.DURATION.lastChild, {dt: -this.entry.TIME_LEAKS * 1000, from: this.date_start, display: 'worktime'});
		}
	}

	var arReportTypes = ['TIME_START', 'TIME_FINISH', 'DURATION'];

	for (var k=0,kl=arReportTypes.length; k < kl; k++)
	{
		var key = arReportTypes[k];
		if (this.entry.REPORTS[key])
		{
			i = 0;

			if (this.entry.REPORTS[key][i].ACTIVE)
				BX.addClass(cont[key], 'bx-tm-report-warning');
			else
				BX.addClass(cont[key], 'bx-tm-report-approve');

			var original_time = new Date(this.entry.REPORTS[key][i].TIME * 1000), str_original_time = '';
			if (original_time.getDate() != this.date_start.getDate())
			{
				str_original_time = this.lang.FIXED + ' ' + this.entry.REPORTS[key][i].DATE_TIME;
			}
			else
			{
				str_original_time = this.lang.FIXED_AT + ' ' + BX.timeman.formatTime(original_time.getHours() * 3600 + original_time.getMinutes() * 60);
			}

			var warn = BX.create('SPAN', {
				props: {className: 'bx-tm-report-warning-info'},
				children: [
					BX.create('SPAN', {
						props: {className: 'bx-tm-report-warning-info-time'},
						text: '(' + str_original_time + ')'
					})
				]
			})

			cont[key].appendChild(warn);

			row = BX.create('DIV', {
				props: {className: 'bx-tm-report-warn-info'},
				children: [
					BX.create('DIV', {children: [
						BX.create('SPAN', {props: {className: 'bx-tm-report-caption'}, text: this.lang.FIXED_CAPTION + ': '}),
						BX.create('SPAN', {
							props: {className: 'bx-tm-report-field'},
							html: this.entry.REPORTS[key][i].REPORT || ' '
						})
					]}),
					(this.entry.REPORTS[key][i].ACTIVE ? null :
						BX.create('DIV', {children: [
							BX.create('SPAN', {props: {className: 'bx-tm-report-caption'}, text: this.lang.FIXED_APPROVER + ': '}),
							BX.create('SPAN', {
								props: {className: 'bx-tm-report-field'},
								html: this.entry.REPORTS[key][i].USER_NAME || ' '
							})
						]})
					)
				]
			});

			if (this.entry.REPORTS[key][i].REPORT_FULL)
			{
				new BX.CHint({
					parent: row.firstChild.lastChild,
					hint: '<p>' + BX.util.trim(this.entry.REPORTS[key][i].REPORT_FULL).replace(/[\n]{2}/g, '</p><p>').replace(/\n/g, '<br />') + '</p>',
					show_timeout: 100, hide_timeout: 90
				});
			}

			if (cont[key].nextSibling)
				cont[key].parentNode.insertBefore(row, cont[key].nextSibling);
			else
				cont[key].parentNode.appendChild(row);
		}
	}

	if (Math.abs(this.entry.TIME_LEAKS) >= 60)
	{
		row = BX.create('DIV', {
			props: {className: 'bx-tm-report-warn-info'},
			children: [
				BX.create('DIV', {children: [
					BX.create('SPAN', {props: {className: 'bx-tm-report-caption'}, text: this.lang.LEAKS_CAPTION + ': '}),
					BX.create('SPAN', {
						props: {className: 'bx-tm-report-field'},
						text: BX.timeman.formatWorkTime(this.entry.TIME_LEAKS)
					})
				]})
			]
		});

		cont.DURATION.parentNode.appendChild(row);
	}

	this.bInit = true;

	return this.DIV_CONTENT;
}

JCTimeManReportEntry.prototype.getPopupButtons = function()
{
	var b = [];

	if (this.entry.CAN_EDIT)
	{
		this.SAVEBUTTON = new BX.PopupWindowButton({
			text : this.entry.ACTIVE ? this.lang.SAVE : this.lang.APPROVE,
			className : this.entry.ACTIVE ? "" : "popup-window-button-accept",
			events: {click: BX.proxy(this.saveData, this)}
		});
		b.push(this.SAVEBUTTON);
	}

	b.push(new BX.PopupWindowButtonLink({
		text : this.lang.CLOSE,
		className : "popup-window-button-link-cancel",
		events : {click : BX.proxy(this.closeWnd, this)}
	}));

	return b;
}

JCTimeManReportEntry.prototype.closeWnd = function(e)
{
	this.WND.close();
	return BX.PreventDefault(e);
}

JCTimeManReportEntry.prototype._Edit = function(param, value)
{
	this.DATA_FOR_SAVE[this.DATA_FOR_SAVE.length] = {
		fld: param,
		val: value
	};

	this.SAVEBUTTON.setClassName("popup-window-button-accept");
}

JCTimeManReportEntry.prototype.Edit = function()
{
	if (!this.entry.CAN_EDIT)
		return false;

	var cont = BX.proxy_context,
		tag = cont.bx_tm_tag,
		value = cont.innerText || cont.textContent,
		editCB = BX.delegate(function(val) {
			this.parent.CLOCK.closeWnd();
			val = BX.timeman.unFormatTime(val);
			if (!isNaN(val) && val != BX.timeman.unFormatTime(value))
			{
				cont.innerHTML = BX.timeman.formatTime(val)
				this._Edit(tag, val);
			}
		}, this);


	if (!this.parent.CLOCK)
	{
		this.parent.CLOCK = new BX.CTimeManClock({DIV: this.DIV_CONTENT}, {
			node: cont,
			popup_id: 'tm_report_edit',
			clock_id: 'tm_repopt_edit_clock',
			zIndex: 1,
			callback: editCB
		});
	}
	else
	{
		this.parent.CLOCK.setNode(cont);
		this.parent.CLOCK.setCallback(editCB);
	}

	this.parent.CLOCK.setTime(BX.timeman.unFormatTime(value));
	this.parent.CLOCK.Show();

	return true;
}

JCTimeManReportEntry.prototype.EditWorkTime = function()
{
	if (!this.entry.CAN_EDIT || !this.entry.TIME_FINISH)
		return false;

	var cont = BX.proxy_context,
		tag = cont.bx_tm_tag,
		value = BX.timeman.unFormatTime(cont.innerText || cont.textContent),
		editCB = BX.delegate(function(val) {
			val = BX.timeman.unFormatTime(val);
			if (!isNaN(val) && val != value)
			{
				cont.innerHTML = BX.timeman.formatWorkTime(val)
				this._Edit(tag, val);
			}
		}, this);

	var input = BX.create('INPUT', {
		props: {
			type: 'text',
			value: BX.timeman.formatTime(value)
		},
		style: {
			height: (cont.offsetHeight-5) + 'px', width: (cont.offsetWidth) + 'px',
			marginLeft: '10px',
			position: 'absolute'
		},
		events: {
			blur: function() {editCB(this.value);this.parentNode.removeChild(this);}
		}
	});

	cont.parentNode.insertBefore(input, cont);
	input.focus();

	return true;
}

JCTimeManReportEntry.prototype.saveData = function()
{
	if (!this.entry.CAN_EDIT)
		return false;

	if (!this.entry.ACTIVE)
		this.DATA_FOR_SAVE[this.DATA_FOR_SAVE.length] = {fld: 'approve', val: true};

	var i=0, l=this.DATA_FOR_SAVE.length, data = {ID: this.entry.ID};
	if (l > 0)
	{
		for (; i<l; i++)
		{
			data[this.DATA_FOR_SAVE[i].fld] = this.DATA_FOR_SAVE[i].val;
		}

		BX.timeman.showWait(this.DIV_CONTENT);
		BX.timeman_query('admin_save', data, BX.proxy(this.Reset, this));
	}

	this.DATA_FOR_SAVE = [];

	return true;
}

JCTimeManReportEntry.prototype.Clear = function()
{
	for (var i in this.TIMER)
	{
		if (this.TIMER[i].Finish)
			this.TIMER[i].Finish();
	}

	this.WND = null;

	BX.removeCustomEvent(this.parent, 'onRecountTotals', BX.proxy(this.onRecountTotals, this));
	BX.removeCustomEvent('onEntryNeedReload', BX.proxy(this.onEntryNeedReload, this));
	BX.unbind(this.cell, 'click', BX.proxy(this.Click, this));
	BX.cleanNode(this.cell);
}

window.JCTimeManReport = JCTimeManReport;
window.JCTimeManReportEntry = JCTimeManReportEntry;
})();