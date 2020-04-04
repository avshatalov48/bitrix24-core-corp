(function(){
var arPopups = {};
window.SLIDE=[];
function JCTimeManReport(id, params)
{
	this.DIV = id;
	this.icons = new Array();
	this.cells = {};
	this.obCommentCount = [];
	this.settingmode = false;
	this.SETTINGS = {
		DATE_START: params.date_start || new Date(),
		MONTHS: params.MONTHS,
		DAYS: params.DAYS,
		LANG: params.LANG,
		SITE_ID: params.SITE_ID,
		DEPARTMENTS: params.DEPARTMENTS,
		DEPARTMENTS: params.DEPARTMENTS,
		FILTER: params.FILTER,
		START_SHOW_ALL: params.START_SHOW_ALL,
		START_DEPARTMENT: params.START_DEPARTMENT,
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
	this.PopupForms = [];
	this.today = this.__nullifyDate();

	this.arViews = ['arrival', 'departure'];

	this.page = 0;

	BX.ready(BX.delegate(this.Init, this));
	BX.addCustomEvent('onWorkReportMarkChange', BX.proxy(this.UpdateCell,this));
}

JCTimeManReport.prototype.Init = function()
{
	this.DIV = BX(this.DIV);

	if (this.SETTINGS.FILTER && BX.type.isString(this.SETTINGS.FILTER))
		this.SETTINGS.FILTER = document.forms[this.SETTINGS.FILTER];


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

	this.PARTS.LAYOUT = this.DIV.appendChild(
		BX.create('DIV', {props: {className: 'tm-report bx-tm-additions-disabled'}}) //bx-tm-wide-mode
	);
}

JCTimeManReport.prototype._createLayout = function()
{
	window.SLIDE = [];
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
		html: '<div class="tm-inner">' + this.SETTINGS.LANG.TMR_REPORT_COUNT_MARK + '</div>'
	});
	/*BX.adjust(row.insertCell(-1), {
		props: {className: 'bx-stats-col bx-total-time'},
		html: '<div class="tm-inner">' + this.SETTINGS.LANG.OVERALL + '</div>'
	});*/
	BX.adjust(row.insertCell(-1), {
		props: {className: 'bx-stats-col bx-total-viol'},
		html: '<div class="tm-inner">' + this.SETTINGS.LANG.TMR_OVERALL_VIOL_GOOD + '</div>'
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
			var i;

			var cell = (nameTable.insertRow(-1)).insertCell(-1);
			setting_icon_id = "icon"+this.DATA.DEPARTMENTS[d].ID;
			cell.colSpan = 5;
			cell.className = 'bx-tm-departments-cell';


			var h = '<div class="bx-tm-spacer" style="z-index:20"><div class="bx-tm-departments-chain">';
				ldd = this.DATA.DEPARTMENTS[d].CHAIN.length;

			for (i = 0; i < ldd; i++)
			{
				h += (i > 0 ? '<span class="bx-tm-departments-delimiter">&mdash;</span>' : '')+ '<a href="' + this.DATA.DEPARTMENTS[d].CHAIN[i].URL + '">' + BX.util.htmlspecialchars(this.DATA.DEPARTMENTS[d].CHAIN[i].NAME) + '</a>';
			}
			h +="<span id='hint_"+setting_icon_id+"' class='bx-tm-dep-info'>&nbsp;&nbsp;&nbsp;&nbsp;</span>";
			h += "<span id='"+setting_icon_id+"' class='bx-tm-dep-settings-info bx-no-settings-light'>&nbsp;&nbsp;&nbsp;&nbsp;</span>"+'</div>&nbsp;</div>';
			cell.innerHTML = h;
			this.DATA.DEPARTMENTS[d].obHoverHint = new BX.CHint({
					parent: BX("hint_"+setting_icon_id),
					title: this.SETTINGS.LANG.TM_SETTINGS_REPORT,
					hint: this.GetHintDescription(this.DATA.DEPARTMENTS[d].SETTINGS),
					show_timeout: 10, hide_timeout: 9
				});
			this.DATA.DEPARTMENTS[d].obHoverHint.Init();
			cell.onmouseover = function()
			{
				var setting_icon = BX(setting_icon_id);
				if(!setting_icon || setting_icon.style.display != 'inline-block')
						BX.addClass(this,'bx-over');
			}

			cell.onmouseout = function()
			{
				var setting_icon = BX(setting_icon_id);
				if(!setting_icon || setting_icon.style.display != 'inline-block')
					BX.removeClass(this,'bx-over');
			}

			if(this.DATA.DEPARTMENTS[d].HAS_SETTINGS == "Y")
			{
				BX.removeClass(BX('icon'+this.DATA.DEPARTMENTS[d].ID),'bx-no-settings-light');
				BX.addClass(BX('icon'+this.DATA.DEPARTMENTS[d].ID),'bx-has-settings-light');
			}
			BX('icon'+this.DATA.DEPARTMENTS[d].ID).style.display = "none";
			if (this.DATA.DEPARTMENTS[d].CAN_EDIT_TIME == "Y"){
				this.icons[this.icons.length] = BX('icon'+this.DATA.DEPARTMENTS[d].ID);
				var settingform = new SettingForm(BX("icon"+this.DATA.DEPARTMENTS[d].ID),this.DATA.DEPARTMENTS[d],this,"dep");
				BX("icon"+this.DATA.DEPARTMENTS[d].ID).onclick = BX.proxy(settingform.Show,settingform);
				BX("icon"+this.DATA.DEPARTMENTS[d].ID).style.display = "inline-block";
			}

			var emptycell = dataTable.insertRow(-1).insertCell(-1);
			emptycell.className = 'bx-tm-departments-cell';
			emptycell.innerHTML = '<div class="bx-tm-spacer">&nbsp;</div>';
			emptycell.colSpan = cellCount;

			for (i = 0; i < l; i++)
			{
				if (this.DATA.USERS[i].DEPARTMENT != this.DATA.DEPARTMENTS[d].ID)
					continue;

				row = nameTable.insertRow(-1), hint = null;
				//carter //user_name create
				BX.adjust(row.insertCell(-1), {
					props: {
						className: 'bx-name-col' + (this.DATA.USERS[i].HEAD ? ' bx-head' : '')
					},
					children: [
						BX.create('DIV', {props: {className: 'tm-inner'}, children:
						[
							(hint = BX.create('SPAN', {props: {className: 'bx-tm-user-settings-info bx-no-settings-light'}})),
							(hoverhint = BX.create('SPAN', {props: {className: 'bx-tm-user-info'}})),
							BX.create("SPAN",{
								style:{overflow:"hidden", textOverflow:"ellipsis",display:"block"},
								children:[
									BX.create('A', {
										attrs: {href: this.DATA.USERS[i].URL},
										props: {
											className: 'tm-user-link',
											title: (this.DATA.USERS[i].HEAD ? this.SETTINGS.LANG.HEAD : '')},
										html: this.DATA.USERS[i].NAME
									})
								]
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
				this.DATA.USERS[i].obHoverHint = new BX.CHint({
					parent: hoverhint,
					title: this.SETTINGS.LANG.TM_SETTINGS_REPORT,
					hint: this.GetHintDescription(this.DATA.USERS[i].SETTINGS),
					show_timeout: 10, hide_timeout: 9
				});
				this.DATA.USERS[i].obHoverHint.Init();
				var settingform = new SettingForm(hint,this.DATA.USERS[i],this,"user");
				hint.onclick = BX.proxy(settingform.Show,settingform);
				this.icons[this.icons.length] = hint;
				if(this.DATA.USERS[i].SETTINGS.UF_REPORT_PERIOD && !this.DATA.USERS[i].SETTINGS.PARENT)
				{
					BX.removeClass(hint,'bx-no-settings-light');
					BX.addClass(hint,'bx-has-settings-light');
				}
				else
				{
					BX.addClass(hint,'bx-no-settings-light');
					BX.removeClass(hint,'bx-has-settings-light');
				}

				hint.style.display ="none";
				var obTotals = {
					TOTAL: null, TOTAL_REPORTS: null, TOTAL_VIOLATIONS: null
				}
				//carter days count col
				obTotals.TOTAL_REPORTS = BX.adjust(row.insertCell(-1), {
					props: {className: 'bx-stats-col bx-total-days'},
					html: '<span class="bx-days">' +(Math.round(this.DATA.USERS[i].FULL_REPORT_INFO.COUNT > 0 ? (100*this.DATA.USERS[i].FULL_REPORT_INFO.MARKED/this.DATA.USERS[i].FULL_REPORT_INFO.COUNT) : 0)) + '%'+ '</span>'
				});
				//carter total time count
				/*obTotals.TOTAL = BX.adjust(row.insertCell(-1), {
					props: {className: 'bx-stats-col bx-total-time'},
					text: BX.timeman.formatWorkTime(this.DATA.USERS[i].TOTAL)
				});*/
				//carter percent
				var v = Math.round(this.DATA.USERS[i].FULL_REPORT_INFO.MARKED > 0 ? (100*this.DATA.USERS[i].FULL_REPORT_INFO.GOOD/this.DATA.USERS[i].FULL_REPORT_INFO.MARKED) : 0) + '%';

				obTotals.TOTAL_VIOLATIONS = BX.adjust(row.insertCell(-1), {
					props: {
						className: 'bx-stats-col bx-total-viol',
						title: this.DATA.USERS[i].FULL_REPORT_INFO.COUNT + ' / ' + this.DATA.USERS[i].FULL_REPORT_INFO.BAD
					},

					text: v
				});

				if (!this.TOTALS['USER_' + this.DATA.USERS[i].ID])
					this.TOTALS['USER_' + this.DATA.USERS[i].ID] = []
				this.TOTALS['USER_' + this.DATA.USERS[i].ID].push(obTotals);

				var obUserRow = BX.clone(obSampleRow, true);
				obUserRow.BXUSERID = this.DATA.USERS[i].ID+"_"+this.DATA.USERS[i].DEPARTMENT;
				dataTable.appendChild(obUserRow);

				row.onmouseover = function()
				{
					var setting_icon = BX.findChild(this,{tag:'SPAN'},true);
					if(!setting_icon || setting_icon.style.display != 'inline-block')
						dataTable.rows[this.sectionRowIndex].className = nameTable.rows[this.sectionRowIndex].className = 'bx-over';
				}

				row.onmouseout = function()
				{
					var setting_icon = BX.findChild(this,{tag:'SPAN'},true);
					if(!setting_icon || setting_icon.style.display != 'inline-block')
						dataTable.rows[this.sectionRowIndex].className = nameTable.rows[this.sectionRowIndex].className = '';
				}
				for(var j=0,k=obUserRow.cells.length;j<k;j++)
					obUserRow.cells[j].innerHTML = '<div class="bx-tm-view-worktime">&nbsp;</div><div class="bx-tm-view-arrival">&nbsp;</div><div class="bx-tm-view-departure">&nbsp;</div>';
				if(this.DATA.USERS[i].FULL_REPORT)
					this.setReportsData(this.DATA.USERS[i]);
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

JCTimeManReport.prototype._setScrollersPosScroll = function()
{
	this.PARTS.LAYOUT_COLS.DATA.scrollLeft = 0;

	if (this.PARTS.TODAY_CELL)
	{
		var q = this.PARTS.TODAY_CELL.offsetLeft - this.PARTS.LAYOUT_COLS.DATA.offsetWidth + Math.ceil(this.PARTS.TODAY_CELL.offsetWidth * 1.6);
		this.PARTS.LAYOUT_COLS.DATA.scrollLeft = q > 0 ? q : 0;
	}
}

JCTimeManReport.prototype.GetHintDescription = function(settings_data)
{
	var result = "";

	if(settings_data.UF_REPORT_PERIOD && settings_data.UF_REPORT_PERIOD!="NONE")
	{
		result+=this.SETTINGS.LANG.PERIOD+": <b>"+this.SETTINGS.LANG[settings_data.UF_REPORT_PERIOD]+"</b><br>";
		switch (settings_data.UF_REPORT_PERIOD){
			case "WEEK":
				result+=this.SETTINGS.LANG.DAY+": <b>"+this.SETTINGS.LANG["TMR_DAY_FULL_"+settings_data.UF_TM_DAY]+"</b><br>";
			break;
			case "MONTH":
				result+=this.SETTINGS.LANG.TMR_DATE_MONTH+": <b>"+settings_data.UF_TM_REPORT_DATE+"</b><br>";
			break;
		}

		result+=this.SETTINGS.LANG.TIME+": <b>"+BX.timeman.formatTime(settings_data.UF_TM_TIME)+"</b><br>";
	}
	if (result=="")
		result = this.SETTINGS.LANG.NONE;
	return result;
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

JCTimeManReport.prototype.toggleStats = function(v)
{

	var d = this.PARTS.LAYOUT_COLS.DATA,
		scrollRight = d.scrollLeft + d.offsetWidth;

	BX[v ? 'removeClass' : 'addClass'](this.PARTS.LAYOUT, 'bx-tm-wide-mode');

	//setTimeout(function() {
		d.scrollLeft = scrollRight - d.offsetWidth;
	//}, 10);
}

JCTimeManReport.prototype.toggleAdditions = function(v)
{
	var d = this.PARTS.LAYOUT_COLS.DATA,
		scrollLeft = d.scrollLeft;

	BX[v ? 'removeClass' : 'addClass'](this.PARTS.LAYOUT, 'bx-tm-additions-disabled');

	d.scrollLeft = scrollLeft;
}

JCTimeManReport.prototype.InitSettingMode = function(button)
{
	var display = "inline-block"
	this.settingmode = true;
	BX.toggleClass(button,'tm-settings-item-active');
	if (!BX.hasClass(button,'tm-settings-item-active'))
	{
		display = "none";
		this.settingmode = false;
	}
	for(i=0;i<this.icons.length;i++)
		this.icons[i].style.display = display;
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
			this.scrollData(-70, this);
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
			this.scrollData(70, this);
			return BX.eventCancelBubble(e||window.event);
		}
	}, this);

	this.PARTS.SCROLLERS.LEFT.onmouseup = this.PARTS.SCROLLERS.RIGHT.onmouseup = function() {stopScrollData(this)}

	BX.bind(this.PARTS.LAYOUT_COLS.DATA, 'mousewheel', BX.proxy(this._wheelScroll, this));

	setTimeout(BX.delegate(this._setScrollersPosScroll, this), 50);
}

JCTimeManReport.prototype._wheelScroll = function(e)
{
	this.scrollData(-Math.ceil(70 * BX.getWheelData(e)/3));
	return BX.PreventDefault(e);
}

JCTimeManReport.prototype.scrollData = function(dx, ob)
{
	var q = this.DRAGDATA.startscroll || this.PARTS.LAYOUT_COLS.DATA.scrollLeft;
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

JCTimeManReport.prototype.changeMonth = function(dir)
{
	this.SETTINGS.DATE_START.setMonth(this.SETTINGS.DATE_START.getMonth() + dir);
	BX('bx_goto_date').value = BX.message('FORMAT_DATE').replace('YYYY', this.SETTINGS.DATE_START.getFullYear()).replace('MM',  this.SETTINGS.DATE_START.getMonth()+1).replace('DD', this.SETTINGS.DATE_START.getDate());
	BX('tm_datefilter_title', true).innerHTML = this.SETTINGS.MONTHS[this.SETTINGS.DATE_START.getMonth()] + ' ' + this.SETTINGS.DATE_START.getFullYear();
	this.Page(1);
}

JCTimeManReport.prototype.Filter = function(bClear)
{
	var dpt = '',
		show_all = 'Y';
	BX.timeman.showWait(this.DIV,0);
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
	var TS_START = (this.__nullifyDate(this.SETTINGS.DATE_START, true)).valueOf();
	var TS_FINISH = new Date(TS_START);
	TS_FINISH.setMonth(TS_FINISH.getMonth()+1);
	var offset = (TS_FINISH.getTimezoneOffset()/60)*3600;

	BX.timeman.showWait(this.DIV);
	BX.timeman_query('admin_data_report_full', {
		ts: parseInt((this.SETTINGS.DATE_START.valueOf()/1000)-offset),
		tf: parseInt(TS_FINISH.valueOf()/1000-1-offset),
		show_all: this.FILTER.SHOW_ALL,
		department: this.FILTER.DEPARTMENT,
		page: this.page,
		get_full_report:"Y"
	}, BX.proxy(this.setData, this));
}

JCTimeManReport.prototype.clearData = function()
{
	for (var i=0, l=this.arCellObjects.length; i<l; i++)
		this.arCellObjects[i].Clear();
	for (var i=0, l=this.PopupForms.length; i<l; i++)
	{
		this.PopupForms[i].close();
		this.PopupForms[i].destroy();
	}

	this.PARTS.LAYOUT_COLS = {NAME: null, DATA: null};
	this.PARTS.SCROLLERS = {LEFT: null, RIGHT: null};
	this.PARTS.TODAY_CELL = null;

	BX.cleanNode(this.PARTS.LAYOUT);
}

JCTimeManReport.prototype.setData = function(data)
{
	this.clearData();
	this.DATA = data;
	this._createLayout();
	BX.setUnselectable(this.PARTS.LAYOUT);
	BX.timeman.closeWait(this.DIV);
	var display = "inline-block";
	//setTimeout(BX.proxy(this.LoadReports, this), 20);
	if (BX("TMBUTTON")&&!BX.hasClass(BX("TMBUTTON"),'tm-settings-item-active'))
		display = "none";
	for(i=0;i<this.icons.length;i++)
		this.icons[i].style.display = display;
	this.PARTS.NAV.innerHTML = this.DATA.NAV;
	h = window.location.hash;
	if (h.length != 0)
	{
		urlReportID = 0;
		urlUser = 0;
		regUser = h.match(/user_id=[0-9]+/g);
		regReport = h.match(/report=[0-9]+/g);
		if (regReport && regReport.length>0)
			urlReportID = parseInt(regReport[0].replace("report=",""));
		if (regUser && regUser.length>0)
			urlUser = parseInt(regUser[0].replace("user_id=",""));
		if (urlReportID>0 && urlUser>0)
			BX.StartSlider(urlUser,urlReportID);
		window.location.hash = "";
	 }
	this.CheckOverdue();
}

JCTimeManReport.prototype.__nullifyDate = function(date, bDay)
{
	date = date || (new Date());
	date.setHours(0);date.setMinutes(0);date.setSeconds(0);date.setMilliseconds(0);
	if (!!bDay) date.setDate(1);

	return date;
}


JCTimeManReport.prototype.CheckOverdue = function()
{
	if (this.DATA.OVERDUE.REPORT_DATA.INFO && BX("bx-report-overdue").style.display == "none")
	{
		this.ShowOverdue(this.DATA.OVERDUE);
		BX("bx-report-overdue").innerHTML = "";
	}
}
JCTimeManReport.prototype.UpdateCell = function(data)
{
	for(var i in window.SLIDE)
	{
		if(window.SLIDE[i].report == data.INFO.ID)
		{

			if (data.INFO.MARK == "G")
				window.SLIDE[i].oCell.style.backgroundColor = "#87C477";
			else if(data.INFO.MARK == "B")
				window.SLIDE[i].oCell.style.backgroundColor = "#E96264";
			else if(data.INFO.MARK == "N")
			{
				window.SLIDE[i].oCell.style.backgroundColor = "#F8C557";
				window.SLIDE[i].oCell.style.color = "#000";
			}
			else
			{
				window.SLIDE[i].oCell.style.backgroundColor = "#C3C3C3";
				window.SLIDE[i].oCell.style.color = "#000";
			}
		}
	}
}

JCTimeManReport.prototype.ShowOverdue = function(data)
{
	var showFromHandler = BX.proxy(function(){BXTIMEMAN.ShowFormWeekly(data)},this);
	var animation = new BX.fx({
		start:0,
		finish :22,
		type:"linear",
		time:0.5,
		step:0.1,
		callback:function(value)
		{
			BX("bx-report-overdue").style.height = value+"px";
		},
		callback_start:function()
		{
			BX.show(BX("bx-report-overdue"));
		},
		callback_complete:BX.proxy(function()
		{
			var showFormHandler = BX.proxy(function(){BXTIMEMAN.ShowFormWeekly(this.DATA.OVERDUE)},this);
			BX("bx-report-overdue").appendChild(
				BX.create("SPAN",{
					html: BX.message("JS_CORE_TMR_OVERDUE_REPORT")+" "
				})
			);
			BX("bx-report-overdue").appendChild(
				BX.create("A",{
					events:{
						click: showFormHandler
					},
					text: this.DATA.OVERDUE.REPORT_DATA.INFO.DATE_TEXT

				})
			);
		},this)

		});
	animation.start();
	BX.addCustomEvent("OnWorkReportSend", function(){
			if(BX("bx-report-overdue"))
				BX.hide(BX("bx-report-overdue"));
		});

}

var user_id = false;
var report_id = false;
JCTimeManReport.prototype.setReportsData = function(data)
{
	var user = data;
	var data = data.FULL_REPORT;

	if (!this.PARTS.LAYOUT_COLS.DATA.firstChild)
		return;

	current_date = this.SETTINGS.DATE_START;

	dayCount = (new Date(this.SETTINGS.DATE_START.getFullYear(), this.SETTINGS.DATE_START.getMonth() + 1, 0).getDate());
	current_month = this.SETTINGS.DATE_START.getMonth();
	for (var i=0; i<data.length; i++)
	{
		var rows = BX.findChildren(this.PARTS.LAYOUT_COLS.DATA.firstChild.tBodies[0],
			{tag: 'TR', property: {BXUSERID: data[i].USER_ID+"_"+user.DEPARTMENT}
		}), row = null;
		celldelta = 0;

		if (!!rows && rows.length > 0)
		{
			for (var q=0,l=rows.length;q<l;q++)
			{
				row = rows[q]
				cell = row.cells[data[i].FOR_JS.CELL_FROM];

				if (cell)
				{
					cell.colSpan = data[i].FOR_JS.CELL_COUNT;
					cell.innerHTML = '<span style="font-weight:bold;color:#FFF;">'+this.SETTINGS.LANG.TMR_REPORT+'</span>';
					cell.innerHTML+= '<span id="report_comments_count_'+data[i].ID+'" class="tm-comment-count" style="color:#FFF;display:'+((data[i].COMMENTS_COUNT>0)?"inline-block":"none")+'">'+data[i].COMMENTS_COUNT+'</span>';
					cell.style.textAlign = 'left';
					cell.style.paddingRight = '0px';
					cell.style.cursor = 'pointer';
					var userdata =	data[i];
					/*if(!cell.bxentry)
						this.arCellObjects[this.arCellObjects.length] = cell.bxentry = BX.delegate(this.ShowSlider,this);//new BX.JSTimeManReportFullForm(userdata,cell,this.SETTINGS.LANG);	*/
						cell.onclick = this.ShowSlider;
						window.SLIDE[window.SLIDE.length] = {
							oCell:cell,
							report:userdata.ID,
							user_id:userdata.USER_ID
						}
					cell.style.paddingLeft = "5px";
					if (data[i].MARK == "G")
						cell.style.backgroundColor = "#87C477";
					else if(data[i].MARK == "B")
						cell.style.backgroundColor = "#E96264";
					else if(data[i].MARK == "N")
						cell.style.backgroundColor = "#f8c557";
					else
					{
						cell.style.backgroundColor = "#C3C3C3";
						cell.style.color = "#000";
					}

					for (k=1; k<data[i].FOR_JS.CELL_COUNT; k++)
					{
						row.deleteCell(cell.cellIndex+1);
					}

					cell = null;cellcnt = 0;
				}
			}
		}
	}

	BX.timeman.closeWait(this.DIV);
}
JCTimeManReport.prototype.ShowSlider = function()
{
	for(i=0;i<window.SLIDE.length;i++)
	{
		if(window.SLIDE[i].oCell == this)
		{
			BX.StartSlider(window.SLIDE[i].user_id,window.SLIDE[i].report);
			break;
		}
	}
}
/**************************************************************************/



function SettingForm(node,obj,parent,mode)
{

	this.obj = obj;
	this.mode = mode;
	this.icon = node;
	this.data =[];
	this.lang = parent.SETTINGS.LANG;
	this.parent = parent;

	this.popup = BX.PopupWindowManager.create(
				"setting_form_"+Math.random(),
				node,
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

	this.parent.PopupForms[this.parent.PopupForms.length] = this.popup;
	this.fields = [];
	this.fields.push(BX.create("DIV",{
			style:{marginBottom:"7px"},
			children:[
				BX.create("SPAN",{
					props:{className:"tm-settings-title"},
					html:this.lang.TM_SETTINGS_REPORT
				}),
				BX.create("DIV",{
					props:{className:"tm-popup-section-title-line"}
				})
			]

		}
	)
	);
	var bx_periods = new Array("DAY","WEEK","MONTH","NONE");
	if ((this.mode == "dep" && this.obj.IBLOCK_SECTION_ID!="")||this.mode == "user")
		bx_periods.push("PARENT");
	this.options = [];
	this.days = [];
	this.dates = [];
	this.SaveLable = BX.create('SPAN', {
							props:{className:'tm-success-change'},
							text:this.lang.TMR_SUCCESS
						});
	this.fields.push(this.SaveLable);
	this.fields.push(BX.create('DIV', {
	props:{className:'period-setting-label'},
	html: this.lang.PERIOD+"<br>"}));


	for (i=0;i<bx_periods.length;i++)
	{
		this.options.push(BX.create('OPTION', {
				attrs: {
					id:bx_periods[i],
					selected:((this.obj.SETTINGS.UF_REPORT_PERIOD == bx_periods[i])?"selected":"")
				},
				text:this.lang[bx_periods[i]]
			}));
	}

	for (i=1;i<=31;i++)
	{
		this.dates.push(BX.create('OPTION', {
				attrs: {
					selected:(((this.obj.SETTINGS.UF_TM_REPORT_DATE == i)?"selected":"")),
					id:("DATE_"+i)
				},
				text:i
			}));
	}

	for (i=1;i<8;i++)
	{
		this.days.push(BX.create('OPTION', {
				attrs: {
					selected:(((this.obj.SETTINGS.UF_TM_DAY == i)?"selected":"")),
					id:i
				},
				text:this.lang["TMR_DAY_FULL_"+i]
			}));
	}
	//period
	if (this.obj.CAN_EDIT_TIME == "Y")
	{
		this.selectPeriod = BX.create('SELECT', {
					props: {
						className: 'period-setting-select'
					},
					attrs: {
						size:1,
						disabled:(this.obj.CAN_EDIT_TIME == "N")?"disabled":""
					},
					children:this.options,
					events:{
						'change':BX.proxy(this.MakeDescription,this)
					}

				});
		//day week

		this.selectDay = BX.create('SELECT', {
					props: {
						className: "period-setting-select"
					},
					attrs: {
						size:1,
						disabled:(this.obj.CAN_EDIT_TIME == "N")?"disabled":""
						},
					children:this.days,
					events:{
						'change':BX.proxy(this.MakeDescription,this)
					}


				});
		//date of month
		this.selectDate = BX.create('SELECT', {
					props: {
						className: 'period-setting-select'
					},
					attrs: {size:1,
					disabled:(this.obj.CAN_EDIT_TIME == "N")?"disabled":""
					},
					children:this.dates,
					events:{
						'change':BX.proxy(this.MakeDescription,this)
					}

				});
	}
	else
	{
		this.selectPeriod = BX.create('SPAN', {
					props: {
						className: 'period-setting-select-non-active'
					}
				});
		//day week

		this.selectDay = BX.create('SPAN', {
					props: {
						className: "period-setting-select-non-active"
					}
		});


		//date of month
		this.selectDate = BX.create('SPAN', {
					props: {
						className: 'period-setting-select-non-active'
					}
				});
	}
	//time
	var current_time = ((new Date()).valueOf());
	this.buttonClock = BX.create('SPAN', {
				props: {
					className: 'tm-dashboard-clock tm-icon-clock'
				},
				events:{
					'click':((this.obj.CAN_EDIT_TIME == "Y")?BX.proxy(this.ShowClock,this):null)

				}
			});

	this.fields.push(this.selectPeriod);
	this.fields.push(this.detail_period_lable = BX.create('DIV', {
		props:{className:'period-setting-label'},
		html: this.lang.DATE+"<br>"}));

	this.fields.push(this.selectDay);
	this.fields.push(this.selectDate);
	this.fields.push(this.timeArea = BX.create('DIV', {
		props:{className:'tm-clock-area'},
		children:[
			BX.create("SPAN",{props:{className:"period-setting-label"},
				html:this.lang.TIME+":"
			}),
			BX.create("SPAN",{
				props:{className:((this.obj.CAN_EDIT_TIME != "Y")?"tm-clock-select-non-active":"")},
				children:[
					this.buttonClock,
					this.selectTime = BX.create('INPUT', {
						attrs:{readonly:"true",value:BX.timeman.formatTime(((this.obj.SETTINGS.UF_TM_TIME)?this.obj.SETTINGS.UF_TM_TIME:current_time))},
						props:{className:((this.obj.CAN_EDIT_TIME == "Y")?'tm-clock-select':'tm-clock-select-non-active')+(BX.isAmPmMode()?" tm-clock-select-ampm":"")},
						events:{
							'click':((this.obj.CAN_EDIT_TIME == "Y")?BX.proxy(this.ShowClock,this):null)

						}
						})
					]
				})
		]
		}));

	this.fields.push(this.parent_desc = BX.create('DIV', {
		props:{className:'period-setting-desc'},
		html: ""}));
	this.content_edit = BX.create('DIV', {
				props:{className:'period-setting-main'},
				children:this.fields
			});

	this.buttons = [];
	if (this.obj.CAN_EDIT_TIME == "Y")
	{
		this.SAVEBUTTON = new BX.PopupWindowButton({
				text : this.lang.SAVE,
				className : "popup-window-button-accept",
				events: {click: BX.proxy(this.Save,this)}
			});
		this.buttons.push(this.SAVEBUTTON);
	}
	this.buttons.push(new BX.PopupWindowButtonLink({
		text : this.lang.CLOSE,
		className : "popup-window-button-link-cancel",
		events : {click : BX.proxy(this.popup.close, this.popup)}
	}));


	this.popup.setContent(this.content_edit);
	this.popup.setButtons(this.buttons);
}

SettingForm.prototype.ShowClock = function()
{
	if(!this.clock)
	{
		this.clock = new BX.CTimeManClock({DIV: this.content}, {
				node: this.selectTime,
				start_time: BX.timeman.unFormatTime(this.selectTime.value),
				popup_id: 'tm_edit_d'+this.obj.ID,
				clock_id: 'tm_edit_d'+this.obj.ID,
				zIndex: 960,
				callback: BX.proxy(this.EditTime,this)
			});
	}
	this.clock.Show();
}

SettingForm.prototype.EditTime = function(time)
{
	this.selectTime.value = time;
	this.clock.closeWnd();
}

SettingForm.prototype.MakeDescription = function()
{
	if (!this.setting_data)
		return;



	for(i=0;i<this.options.length;i++)
	{
		if(this.options[i].selected == true)
		{
			//this.obj
			this.data.period = this.options[i].id;
			break;
		}
	}

	if (this.data.period == "PARENT")
	{

		this.detail_period_lable.style.display = "none";
		this.selectDay.style.display = "none";
		this.selectDate.style.display = "none";

	}
	else
	{
		if (this.data.period == "NONE")
		{
			this.detail_period_lable.style.display = "none";
			this.selectDay.style.display = "none";
			this.selectDate.style.display = "none";
			this.timeArea.style.display = "none";

		}
		else if (this.data.period == "WEEK")
		{
			this.detail_period_lable.style.display = "block";
			this.detail_period_lable.innerHTML = this.lang.TMR_DAY_WEEK;
			this.selectDate.style.display = "none";
			this.selectDay.style.display = "block";
			this.timeArea.style.display = "inline-block";
		}else if (this.data.period == "MONTH")
		{
			this.detail_period_lable.style.display = "block";
			this.selectDay.style.display = "none";
			this.timeArea.style.display = "inline-block";
			this.selectDate.style.display = "inline-block";
			this.detail_period_lable.innerHTML = this.lang.TMR_DATE_MONTH;

		}else if(this.data.period == "DAY")
		{
			this.detail_period_lable.style.display = "none";
			this.selectDay.style.display = "none";
			this.timeArea.style.display = "inline-block";
			this.selectDate.style.display = "none";

		}
	}

}
SettingForm.prototype.Show = function()
{
	var data = {
		id: this.obj.ID,
		object:this.mode
	};
	this.SaveLable.style.display = "none";
	BX.timeman_query('report_full_setting', data, BX.proxy(this.Reload, this));

}

SettingForm.prototype.Save = function()
{
	var date;

	if (this.data.period == "PARENT")
	{
		this.save = {
			mode : this.data.period,
			time : "",
			day:"",
			date: "",
			type: "time_report",
			id: this.obj.ID,
			object:this.mode
		}
	}
	else
		this.save = {
		mode : this.data.period,
		time : this.selectTime.value,
		day: this.selectDay.options[this.selectDay.selectedIndex].id,
		date: (this.selectDate.options[this.selectDate.selectedIndex].id).replace("DATE_",""),
		type: "time_report",
		id: this.obj.ID,
		object:this.mode
	}

	BX.timeman.showWait(this.popup.popupContainer,0);
	BX.timeman_query('admin_report_full', this.save, BX.proxy(this._SaveAndClose, this));
}

SettingForm.prototype.FormatDate = function(date, format)
{

	var val;
	var str = format;

	str = str.replace(/YYYY/ig, date.getFullYear());
	str = str.replace(/MM/ig, date.getMonth()+1);
	str = str.replace(/DD/ig, date.getDate());
	str = str.replace(/HH/ig, date.getHours());
	str = str.replace(/MI/ig, date.getMinutes());
	str = str.replace(/SS/ig, date.getSeconds());

	return str;
}

SettingForm.prototype._SaveAndClose = function(data)
{
	this.Reload(data);
	this.popup.close();
	this.obj.obHoverHint.setContent(this.parent.GetHintDescription(data));
}

SettingForm.prototype.Reload = function(data)
{
	this.setting_data = data;
	var DEPS = this.parent.DATA.DEPARTMENTS;
	if (this.setting_data.PARENT)
	{
		this.parent_desc.innerHTML = BX.message("JS_CORE_TMR_PARENT_SETTINGS")+"\""+BX.util.htmlspecialchars(this.setting_data.PARENT_NAME)+"\"";
		this.parent_desc.style.display="block";
	}
	else
	{
		this.parent_desc.innerHTML = "";
		this.parent_desc.style.display="none";
	}


		for(i=0;i<this.options.length;i++)
		{
			if(this.options[i].id == data.UF_REPORT_PERIOD)
			{
				if(this.obj.CAN_EDIT_TIME == "Y")
					this.options[i].selected = "selected";
				else
					this.selectPeriod.innerHTML = this.options[i].value;
				break;
			}else if(data.UF_REPORT_PERIOD == false)
			{
				if(this.obj.CAN_EDIT_TIME == "Y")
					this.options[3].selected = "selected";
				else
					this.selectPeriod.innerHTML = this.options[3].value;
					break;
			}
		}

	if(this.setting_data.UF_REPORT_PERIOD && (this.setting_data.PARENT == 'undefined' || !this.setting_data.PARENT) )
	{
		BX.removeClass(this.icon,'bx-no-settings-light');
		BX.addClass(this.icon,'bx-has-settings-light');
	}
	else
	{
		BX.addClass(this.icon,'bx-no-settings-light');
		BX.removeClass(this.icon,'bx-has-settings-light');
	}
	if(data.UF_TM_DAY && data.UF_TM_DAY != "N" )
	{
		if(this.obj.CAN_EDIT_TIME == "Y")
			this.selectDay.options[data.UF_TM_DAY-1].selected = "selected";
		else
			this.selectDay.innerHTML = this.days[data.UF_TM_DAY-1].value;
	}
	else
	{
		if(this.obj.CAN_EDIT_TIME == "Y")
			this.selectDay.options[0].selected = "selected";
		else
			this.selectDay.innerHTML = this.days[0].value;
	}
	if(data.UF_TM_TIME)
		this.selectTime.value = BX.timeman.formatTime(data.UF_TM_TIME);

	if(data.UF_TM_REPORT_DATE && data.UF_TM_REPORT_DATE != "N")
	{
		if(this.obj.CAN_EDIT_TIME == "Y")
			this.selectDate.options[data.UF_TM_REPORT_DATE-1].selected = "selected";
		else
			this.selectDate.innerHTML = this.dates[data.UF_TM_REPORT_DATE-1].value;
	}
	else
	{
		if(this.obj.CAN_EDIT_TIME == "Y")
			this.selectDate.options[0].selected = "selected";
		else
			this.selectDate.innerHTML = this.dates[0].value;
	}
	this.MakeDescription();
	this.popup.show();
}

SettingForm.prototype.Close = function()
{
	this.popup.close();
}

SettingForm.prototype.Close = function()
{
	this.SaveLable.style.display = "none";
	this.popup.show();
}
window.JCTimeManReport = JCTimeManReport;

})();