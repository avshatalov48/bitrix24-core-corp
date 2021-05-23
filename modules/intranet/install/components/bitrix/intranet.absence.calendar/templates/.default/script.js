var jsBXAC = {
/* properties */
	VIEWS:{},
	VIEWS_ID:[],

	SETTINGS:{
		SITE_ID: '',
		NAME_TEMPLATE:'#NAME# #LAST_NAME#',
		SERVER_TIMEZONE_OFFSET:0,
		FIRST_DAY:1,
		DAY_START:8,
		DAY_FINISH:18,
		CONTROLS: {DATEPICKER: 'on', TYPEFILTER: 'on', SHOW_ALL: 'on', DEPARTMENT: 'on'}
	},

	FILTER:{
		SHORT_EVENTS:'Y',
		USERS_ALL:'N',
		DEPARTMENT:'',
		TYPE:{}
	},

	DATA:[],

	LOADER: '',

	CURRENT_VIEW: '',

	MONTHS:[],
	MONTHS_R:[],
	DAYS:[],
	DAYS_FULL:[],

	LAYOUT:null,
	MAIN_LAYOUT:null,

	CONTROLS: {
		VIEWSWITCHER:null,
		CALENDAR:null,
		TYPEFILTER:null,
		SHOW_ALL:null,
		DEPARTMENT:null,
		DATEPICKER:null
	},

	__last_date_params: {},
	__processing: false,
	__current_data_id: null,

	ERRORS: {
		'ERR_NO_VIEWS_REGISTERED': 'No calendar views registered',
		'ERR_VIEW_NOT_REGISTERED': 'View not registered',
		'ERR_WRONG_LAYOUT': 'Wrong layout',
		'ERR_WRONG_HANDLER': 'Wrong calendar view handler',
		'ERR_RUNTIME_NO_VIEW': 'Runtime error! Unable to initialize current view'
	},

	TYPES: {},
	TYPE_BGCOLORS: {},

	bInitFinished: false,

/* events */
	onBeforeShow: null,
	onShow: null,

/* methods  */
	Init: function (arParams)
	{
		this.LOADER = arParams.LOADER;

		this.SETTINGS.SITE_ID = arParams.SITE_ID;

		this.SETTINGS.NAME_TEMPLATE = arParams.NAME_TEMPLATE != '' ? arParams.NAME_TEMPLATE : this.SETTINGS.NAME_TEMPLATE;
		this.SETTINGS.SERVER_TIMEZONE_OFFSET = arParams.SERVER_TIMEZONE_OFFSET + (new Date()).getTimezoneOffset() * 60;
		this.SETTINGS.FIRST_DAY = null != arParams.FIRST_DAY ? arParams.FIRST_DAY : 1;
		this.SETTINGS.DAY_START = null != arParams.DAY_START ? arParams.DAY_START : 8;
		this.SETTINGS.DAY_FINISH = null != arParams.DAY_FINISH ? arParams.DAY_FINISH : 18;
		this.SETTINGS.DAY_SHOW_NONWORK = null != arParams.DAY_SHOW_NONWORK ? arParams.DAY_SHOW_NONWORK : false;
		this.SETTINGS.DETAIL_URL_PERSONAL = arParams.DETAIL_URL_PERSONAL;
		this.SETTINGS.DETAIL_URL_DEPARTMENT = arParams.DETAIL_URL_DEPARTMENT;
		this.SETTINGS.PAGE_NUMBER = arParams.PAGE_NUMBER > 0 ? arParams.PAGE_NUMBER : 0;

		this.SETTINGS.IBLOCK_ID = arParams.IBLOCK_ID;
		this.SETTINGS.CALENDAR_IBLOCK_ID = arParams.CALENDAR_IBLOCK_ID;

		if (null != arParams.CONTROLS)
			this.SETTINGS.CONTROLS = arParams.CONTROLS;

		this.MONTHS = arParams.MONTHS;
		this.MONTHS_R = arParams.MONTHS_R;
		this.DAYS = arParams.DAYS;
		this.DAYS_FULL = arParams.DAYS_FULL;

		this.TYPES = arParams.TYPES;
		this.TYPE_BGCOLORS = arParams.TYPE_BGCOLORS;

		this.MESSAGES = arParams.MESSAGES;
		this.ERRORS = arParams.ERRORS;
	},

	Show: function(MAIN_LAYOUT, VIEW)
	{
		this.MAIN_LAYOUT = MAIN_LAYOUT;
		this.CURRENT_VIEW = VIEW;

		if (null != this.onBeforeShow)
			this.onBeforeShow();

		if (null == this.MAIN_LAYOUT) return this.__showError('ERR_WRONG_LAYOUT');

		for (var i=0,cnt=0,len=this.VIEWS_ID.length;i<len;i++)
			if (null != this.VIEWS[this.VIEWS_ID[i]])
				cnt++;

		delete this.VIEWS_ID;

		if (cnt <= 0) return this.__showError('ERR_NO_VIEWS_REGISTERED');
		if (null == this.VIEWS[this.CURRENT_VIEW]) return this.__showError('ERR_VIEW_NOT_REGISTERED', this.CURRENT_VIEW);

		this.bInitFinished = true;

		// display calendar
		this.ShowLayout();
		this.__loadPosition();

		this.SetView(this.CURRENT_VIEW);

		if (null != this.onShow)
			this.onShow();
	},

	SetViewHandler: function(obHandler)
	{
		if (!this.bInitFinished)
			return false;

		if (null == obHandler || null == obHandler.ID)
			return this.__showError('ERR_WRONG_HANDLER');

		if (obHandler.ID == this.CURRENT_VIEW)
		{
			this.CURRENT_VIEW_HANDLER && this.CURRENT_VIEW_HANDLER.Unload && this.CURRENT_VIEW_HANDLER.Unload();

			delete this.CURRENT_VIEW_HANDLER;
			this.CONTROLS.CALENDAR.innerHTML = '';

			this.CURRENT_VIEW_HANDLER = obHandler;
			this.CURRENT_VIEW_HANDLER._parent = this;

			this.CURRENT_VIEW_HANDLER.SetSettings && this.CURRENT_VIEW_HANDLER.SetSettings(this.SETTINGS);
			this.CURRENT_VIEW_HANDLER.Load && this.CURRENT_VIEW_HANDLER.Load();
		}
	},

	SetDataFilter: function(field, value)
	{
		if (null == value)
		{
			delete this.FILTER[field];
		}
		else
		{
			this.FILTER[field] = value;
		}

		if (null != this.CURRENT_VIEW_HANDLER.Load)
			this.CURRENT_VIEW_HANDLER.Load();
	},

	GetDataFilter: function()
	{
		var str = '';

		if (null != this.FILTER.SHORT_EVENTS)
			str += '&SHORT_EVENTS=' + this.FILTER.SHORT_EVENTS;

		if (null != this.FILTER.USERS_ALL)
			str += '&USERS_ALL=' + this.FILTER.USERS_ALL;

		if (null != this.FILTER.DEPARTMENT)
			str += '&DEPARTMENT=' + this.FILTER.DEPARTMENT;

		var type_filter = ''
		for (var i in this.FILTER.TYPE)
			if (this.FILTER.TYPE[i] === true)
				type_filter += (type_filter.length > 0 ? ',' : '') + i;

		if (type_filter.length > 0)
			str += '&TYPES=' + type_filter;
		else if (null != i)
			str += '&TYPES=none';

		return str;
	},

	LoadData: function(ts_start, ts_finish)
	{
		if (null == ts_start) ts_start = this.__last_date_params.TS_START;
		if (null == ts_finish) ts_finish = this.__last_date_params.TS_FINISH;

		if (!ts_start || !ts_finish) return;

		document.getElementById('bx_goto_date').value = BX.calendar.ValueToString(ts_start, false, false);

		BX.showWait(jsBXAC.LAYOUT);

		this.__last_date_params = {'TS_START':ts_start,'TS_FINISH':ts_finish};
		this.__current_data_id = parseInt(Math.random() * 100000);

		this.__savePosition();

		var url = this.LOADER
			+ '?MODE=GET'
			+ '&TS_START=' + (parseInt(ts_start.valueOf()/1000) - this.SETTINGS.SERVER_TIMEZONE_OFFSET)
			+ '&TS_FINISH=' + (parseInt(ts_finish.valueOf()/1000)-1 - this.SETTINGS.SERVER_TIMEZONE_OFFSET)
			+ this.GetDataFilter()
			+ '&PAGE_NUMBER=' + parseInt(this.SETTINGS.PAGE_NUMBER)
			+ '&current_data_id=' + this.__current_data_id
			+ '&site_id=' + this.SETTINGS.SITE_ID
			+ '&iblock_id=' + this.SETTINGS.IBLOCK_ID
			+ '&calendar_iblock_id=' + this.SETTINGS.CALENDAR_IBLOCK_ID
			+ '&sessid=' + BX.message('bitrix_sessid')
			+ '&rnd=' + Math.random();

		BX.loadScript(url);
	},

	SetData: function(DATA, current_data_id, page_number, page_count)
	{
		if (current_data_id != this.__current_data_id || this.__processing)
			return;

		BX.closeWait(this.LAYOUT);

		this.__processing = true;

		this.CURRENT_VIEW_HANDLER.UnloadData && this.CURRENT_VIEW_HANDLER.UnloadData();

		for (var i = 0, dateTo; i < DATA.length; i++)
		{
			if (null != DATA[i].DATA)
			{
				for (var j = 0; j < DATA[i].DATA.length; j++)
				{
					DATA[i].DATA[j].DATE_ACTIVE_FROM = BX.parseDate(DATA[i].DATA[j].DATE_FROM);
					DATA[i].DATA[j].DATE_ACTIVE_TO   = BX.parseDate(DATA[i].DATA[j].DATE_TO);

					if (DATA[i].DATA[j].DATE_ACTIVE_FROM > DATA[i].DATA[j].DATE_ACTIVE_TO)
					{
						var tmp = DATA[i].DATA[j].DATE_ACTIVE_FROM;
						DATA[i].DATA[j].DATE_ACTIVE_FROM = DATA[i].DATA[j].DATE_ACTIVE_TO;
						DATA[i].DATA[j].DATE_ACTIVE_TO = tmp;
					}

					dateTo = DATA[i].DATA[j].DATE_ACTIVE_TO;
					if (DATA[i].DATA[j].DATE_ACTIVE_FROM.valueOf() != dateTo.valueOf())
					{
						if (dateTo.getHours() + dateTo.getMinutes() + dateTo.getSeconds() > 0)
						{
							continue;
						}
					}

					DATA[i].DATA[j].DATE_ACTIVE_TO.setDate(DATA[i].DATA[j].DATE_ACTIVE_TO.getDate() + 1);
					DATA[i].DATA[j].DATE_ACTIVE_TO.setSeconds(DATA[i].DATA[j].DATE_ACTIVE_TO.getSeconds() - 1);
				}
			}
		}

		this.DATA = DATA;

		if (page_number >= 0 && this.SETTINGS.PAGE_NUMBER != page_number)
			this.SETTINGS.PAGE_NUMBER = page_number;
		this.SETTINGS.PAGE_COUNT = page_count > 0 ? page_count : 0;

		this.CURRENT_VIEW_HANDLER.LoadData && this.CURRENT_VIEW_HANDLER.LoadData(this.DATA);

		this.__processing = false;
	},

	__createMainTable: function()
	{
		var obTable = document.createElement('TABLE');
		obTable.className = 'bx-calendar-layout-table';

		if (BX.browser.IsIE())
			obTable.style.borderCollapse = 'collapse';

		obTable.createTHead();
		obTable.appendChild(document.createElement('TBODY'));

		var obTr = obTable.tHead.insertRow(-1);
		var obTd = obTr.insertCell(-1);
		obTd.className = 'bx-table-head';
		obTd.innerHTML = this.MESSAGES.IAC_MAIN_TITLE;

		var obTr = obTable.tBodies[0].insertRow(-1);
		this.CONTROLS.DATEROW = obTr.insertCell(-1);
		this.CONTROLS.DATEROW.className = 'bx-table-datecontrol';
		this.CONTROLS.DATEROW.innerHTML = '';

		var obTr = obTable.tBodies[0].insertRow(-1);
		this.LAYOUT = obTr.insertCell(-1);
		this.LAYOUT.className = 'bx-table-main';
		this.LAYOUT.id = 'bx_calendar_layout_inner';
		this.LAYOUT.innerHTML = '';

		return obTable;
	},

	ShowLayout: function()
	{
		var _this = this;

		this.MAIN_LAYOUT.className = 'bx-calendar-layout';

		this.TOOLBAR = document.createElement('TABLE');
		this.TOOLBAR.appendChild(document.createElement('TBODY'));
		this.TOOLBAR.className = 'bx-calendar-toolbar';
		this.TOOLBAR.tBodies[0].insertRow(-1);

		this.MAIN_LAYOUT.appendChild(this.TOOLBAR);

		this.TOOLBAR.BXAddControl = function(n,o)
		{
			var r = this.tBodies[0].rows[0];

			if (r.cells.length > 0)
				r.insertCell(r.cells.length-1).className = 'bx-calendar-toolbar-delimiter';
			else
				r.insertCell(-1).className = 'bx-calendar-toolbar-last';

			var c = r.insertCell(r.cells.length-1);

			if (null != n)
			{
				o.id = 'filter_control_' + parseInt(Math.random() * 10000);

				c.appendChild(document.createElement('LABEL')).appendChild(document.createTextNode(n+': '));
				c.appendChild(o);
				c.firstChild.htmlFor = o.id;
			}
			else
			{
				c.appendChild(o);
			}

			return c;
		};

		(this.__ShowViewSwitcher()) && (this.MAIN_LAYOUT.appendChild(this.CONTROLS.VIEWSWITCHER));

		this.MAIN_TABLE = this.__createMainTable();
		this.MAIN_LAYOUT.appendChild(this.MAIN_TABLE);

		this.CONTROLS.CALENDAR = document.createElement('DIV');
		this.CONTROLS.CALENDAR.className = 'bx-absence-calendar';

		this.LAYOUT.appendChild(this.CONTROLS.CALENDAR);

		if (null != this.SETTINGS.CONTROLS.DATEPICKER)
		{
			var obCalendarContainer = document.getElementById('bx_calendar_control_datepicker');
			if (null != obCalendarContainer)
			{
				this.CONTROLS.DATEPICKER = document.createElement('DIV');
				this.TOOLBAR.BXAddControl(null, this.CONTROLS.DATEPICKER);

				var contents = obCalendarContainer.innerHTML;
				obCalendarContainer.innerHTML = '';
				this.CONTROLS.DATEPICKER.innerHTML = contents;
			}
		}

		if (null != this.SETTINGS.CONTROLS.TYPEFILTER)
		{
			this.CONTROLS.TYPEFILTER = document.createElement('SPAN');
			this.CONTROLS.TYPEFILTER.className = 'bx-indicator bx-indicator-off';
			this.CONTROLS.TYPEFILTER.innerHTML = '&nbsp;&nbsp;&nbsp;'

			this.TOOLBAR.BXAddControl(this.MESSAGES.IAC_FILTER_TYPEFILTER, this.CONTROLS.TYPEFILTER);

			new JCCalendarFilter(function(a,b){_this.SetDataFilter(a,b)}, this.CONTROLS.TYPEFILTER, this.TYPES, this.MESSAGES, this.TYPE_BGCOLORS);
		}

		if (null != this.SETTINGS.CONTROLS.SHOW_ALL)
		{
			this.CONTROLS.SHOW_ALL = document.createElement('INPUT');
			this.CONTROLS.SHOW_ALL.type = 'checkbox';
			this.CONTROLS.SHOW_ALL.checked = true;
			this.CONTROLS.SHOW_ALL.defaultChecked = true;

			this.CONTROLS.SHOW_ALL.onclick = function() {_this.SetDataFilter('USERS_ALL', this.checked ? 'N' : 'Y')};

			this.TOOLBAR.BXAddControl(this.MESSAGES.IAC_FILTER_SHOW_ALL, this.CONTROLS.SHOW_ALL);
		}

		if (null != this.SETTINGS.CONTROLS.DEPARTMENT)
		{
			var obDepartmentsContainer = document.getElementById('bx_calendar_conrol_departments');
			if (null != obDepartmentsContainer)
			{
				this.CONTROLS.DEPARTMENT = obDepartmentsContainer.firstChild;
				while (this.CONTROLS.DEPARTMENT && this.CONTROLS.DEPARTMENT.tagName != 'SELECT')
					this.CONTROLS.DEPARTMENT = this.CONTROLS.DEPARTMENT.nextSibling;

				this.CONTROLS.DEPARTMENT.parentNode.removeChild(this.CONTROLS.DEPARTMENT);

				this.CONTROLS.DEPARTMENT.onchange = function() {_this.SetDataFilter('DEPARTMENT', this.value)};

				this.TOOLBAR.BXAddControl(this.MESSAGES.IAC_FILTER_DEPARTMENT, this.CONTROLS.DEPARTMENT);
			}
		}
	},

	InsertDate: function(value)
	{
		if (BX.type.isDate(value))
			value = value.valueOf();
		else
			value = parseInt(value);

		jsBXAC.SETTINGS.DATE_START = new Date(value);
		jsBXAC.SETTINGS.DATE_FINISH = new Date(value);
		jsBXAC.CURRENT_VIEW_HANDLER.SetSettings(jsBXAC.SETTINGS);
		jsBXAC.CURRENT_VIEW_HANDLER.Load();
	},

	__GetViewList: function()
	{
		var arList = [], cnt = 0;
		for (var i in this.VIEWS)
		{
			if (this.VIEWS[i].ID)
				arList[arList.length] = this.VIEWS[i];
		}

		if ((cnt = arList.length) > 1)
		{
			for (var i = 0; i < cnt-1; i++)
			{
				for (var j = i+1; j < cnt; j++)
				{
					if (arList[i].SORT < arList[j].SORT)
					{
						var tmp = arList[i]; arList[i] = arList[j]; arList[j] = tmp;
					}
				}
			}
		}

		return arList;
	},

	__ShowViewSwitcher: function()
	{
		var arViewList = this.__GetViewList(), obViewSwitcher = null, cnt = 0;

		if ((cnt=arViewList.length) > 1)
		{
			var _this = this;
			var __SwitchView = function() {_this.SetView(this.parentNode.BXVIEWID)};

			this.CONTROLS.VIEWSWITCHER = document.createElement('UL');
			this.CONTROLS.VIEWSWITCHER.className = 'bx-calendar-view-switcher';

			this.CONTROLS.VIEWSWITCHER.style.position = 'relative';
			this.CONTROLS.VIEWSWITCHER.style.top = '2px';

			for (var i = 0; i < cnt; i++)
			{
				var obItem = this.CONTROLS.VIEWSWITCHER.appendChild(document.createElement('LI'));
				obItem.BXVIEWID = arViewList[i].ID;

				var obLink = obItem.appendChild(document.createElement('A'));
				obLink.id = 'bx_view_switcher_' + obItem.BXVIEWID;
				obLink.href = 'javascript:void(0)';
				obLink.onclick = __SwitchView;
				obLink.onfocus = function() {this.blur();};

				(obLink.appendChild(document.createElement('SPAN'))).className = 'bx-l';

				var obSpan = obLink.appendChild(document.createElement('SPAN'));
				obSpan.className = 'bx-c';
				obSpan.innerHTML = arViewList[i].NAME;

				(obLink.appendChild(document.createElement('SPAN'))).className = 'bx-r';
			}
		}

		return this.CONTROLS.VIEWSWITCHER;
	},

	SetView: function(view)
	{
		if (null == view)
			view = this.CURRENT_VIEW;

		if (null == view) return this.__showError('ERR_RUNTIME_NO_VIEW');
		if (null == this.VIEWS[view]) return this.__showError('ERR_VIEW_NOT_REGISTERED', view);

		this.CURRENT_VIEW = view;

		if (null != this.CURRENT_VIEW && null != this.CONTROLS.VIEWSWITCHER)
		{
			var obItem = this.CONTROLS.VIEWSWITCHER.firstChild;
			do
			{
				if (obItem.BXVIEWID == this.CURRENT_VIEW)
					obItem.className = 'bx-absence-current-view';
				else
					obItem.className = '';
			} while (obItem = obItem.nextSibling);
		}

		this.CURRENT_VIEW_HANDLER && this.CURRENT_VIEW_HANDLER.BeforeUnload && this.CURRENT_VIEW_HANDLER.BeforeUnload();

		BX.ajax.insertToNode(this.LOADER + '?MODE=VIEW&VIEW=' + this.CURRENT_VIEW, this.CONTROLS.CALENDAR, false);
	},

	RegisterView: function(arViewParams)
	{
		if (!this.bInitFinished && null != arViewParams.ID)
		{
			if (null == arViewParams.SORT)
				arViewParams.SORT = 1000;

			this.VIEWS[arViewParams.ID] = arViewParams;
			this.VIEWS_ID[this.VIEWS_ID.length] = arViewParams.ID;

			return true;
		}

		return false;
	},

	isViewRegistered: function(ID)
	{
		return (null != this.VIEWS[ID]);
	},

	UnRegisterView: function(ID)
	{
		if (!this.bInitFinished)
		{
			if (null != this.VIEWS[ID])
			{
				delete this.VIEWS[ID];
				return true;
			}
		}

		return false;
	},

	__entry_onclick: function(e) {this.INFO.Show(e)},

	RegisterEntry: function(arEntry)
	{
		var _this = this;

		if (null != arEntry.VISUAL)
		{
			if (null == this._INFO_CACHE)
				this._INFO_CACHE = {};

			if (null == this._INFO_CACHE[arEntry.ENTRY_TYPE+':'+arEntry.ID])
				this._INFO_CACHE[arEntry.ENTRY_TYPE+':'+arEntry.ID] = new JCCalendarInfoWin(arEntry.ID, arEntry.ENTRY_TYPE, arEntry.USER_ID, this.LOADER, this.TYPE_BGCOLORS);

			arEntry.VISUAL.INFO = this._INFO_CACHE[arEntry.ENTRY_TYPE+':'+arEntry.ID];
			arEntry.VISUAL.onclick = this.__entry_onclick;

			//append Hint
			var hintContent = '';
			hintContent += arEntry.DATE_FROM + ' - ' + arEntry.DATE_TO + '<br />';
			var desc_len = arEntry.DETAIL_TEXT.length, max_len = 350;
			if (desc_len > 0)
			{
				if (desc_len < max_len)
					hintContent += '<br>' + arEntry.DETAIL_TEXT;
				else
					hintContent += '<br>' + arEntry.DETAIL_TEXT.substr(0, max_len) + '...';
			}

			arEntry.VISUAL.BXHINT = new BX.CHint({
				parent: arEntry.VISUAL,
				title: BX.util.htmlspecialcharsback(arEntry.NAME),
				hint: hintContent
			});
		}
	},

	UnRegisterEntry: function(arEntry)
	{
		if (null != arEntry.VISUAL)
		{
			if (null != this._INFO_CACHE && null != this._INFO_CACHE[arEntry.ENTRY_TYPE+':'+arEntry.ID])
			{
				if (null != arEntry.VISUAL.INFO)
				{
					arEntry.VISUAL.INFO.Clear();
					arEntry.VISUAL.INFO = null;
				}

				this._INFO_CACHE[arEntry.ENTRY_TYPE+':'+arEntry.ID] = null;
			}

			arEntry.VISUAL.BXHINT.Destroy();
			arEntry.VISUAL.BXHINT = null;
			arEntry.VISUAL.onclick = null;
		}
	},

	SetControlsListL: function(list)
	{
		this.arControlsConfig = list;
	},

	__showError: function(err_code, err_explain)
	{
		var err_str = '[' + err_code + ']';
		if (null != this.ERRORS[err_code])
			err_str += ' ' + this.ERRORS[err_code];
		if (null != err_explain)
			err_str += ': ' + err_explain;

		alert(err_str);
		return false;
	},

	FormatName: function(FORMAT, arData)
	{
		var NAME = BX.util.trim(arData['NAME']);
		var LAST_NAME = BX.util.trim(arData['LAST_NAME']);
		var SECOND_NAME = BX.util.trim(arData['SECOND_NAME']);

		var NAME_SHORT = NAME ? NAME.substring(0, 1) + '.' : '';
		var LAST_NAME_SHORT = LAST_NAME ? LAST_NAME.substring(0, 1) + '.' : '';
		var SECOND_NAME_SHORT = SECOND_NAME ? SECOND_NAME.substring(0, 1) + '.' : '';

		var res = FORMAT.replace('#NAME#', NAME)
			.replace('#LAST_NAME#', LAST_NAME)
			.replace('#SECOND_NAME#', SECOND_NAME)
			.replace('#NAME_SHORT#', NAME_SHORT)
			.replace('#LAST_NAME_SHORT#', LAST_NAME_SHORT)
			.replace('#SECOND_NAME_SHORT#', SECOND_NAME_SHORT)
			.replace(/#NOBR#|#\/NOBR#/ig, '');

		var res_check = '';
		if (FORMAT.indexOf('#NAME#') >= 0 || FORMAT.indexOf('#NAME_SHORT#') >= 0)
			res_check += arData['NAME'];
		if (FORMAT.indexOf('#LAST_NAME#') >= 0 || FORMAT.indexOf('#LAST_NAME_SHORT#') >= 0)
			res_check += arData['LAST_NAME'];
		if (FORMAT.indexOf('#SECOND_NAME#') >= 0 || FORMAT.indexOf('#SECOND_NAME_SHORT#') >= 0)
			res_check += arData['SECOND_NAME'];

		res_check = BX.util.trim(res_check);
		if (res_check.length <= 0)
			res = BX.util.htmlspecialchars(BX.util.trim(arData['LOGIN']));

		return res;
	},

	getEditUrl: function(INFO, bPublicEdit)
	{
		if (null != window.jsBXCalendarAdmin)
		{
			if (null == bPublicEdit)
				bPublicEdit = true;

			var url = '/bitrix/admin/iblock_element_edit.php'
				+ '?type=' + jsBXCalendarAdmin.IBLOCK_TYPE + '&lang=' + jsBXCalendarAdmin.LANG;

			if (null != INFO)
			{
				url += '&IBLOCK_ID=' + INFO.IBLOCK_ID
					+ '&ID=' + INFO.ID;
			}
			else
			{
				url += '&IBLOCK_ID=' + jsBXCalendarAdmin.IBLOCK_ID
			}

			if (bPublicEdit)
				url += '&bxpublic=Y&from_module=iblock&return_url=reload_absence_calendar';

			return url;
		}
	},

	__savePosition: function()
	{
		window.location.hash =
			'AP:' + this.CURRENT_VIEW + '|' + this.__last_date_params.TS_START.valueOf() + '|' + this.__last_date_params.TS_FINISH.valueOf();
	},

	__loadPosition: function()
	{
		var hash = window.location.hash;
		if (hash.substring(0, 1) == '#')
			hash = hash.substring(1);

		if (hash != '' && hash != '#' && hash.substring(0, 3) == 'AP:')
		{
			hash = hash.substring(3);
			var arHash = hash.split('|');

			if (this.isViewRegistered(arHash[0]))
				this.CURRENT_VIEW = arHash[0];

			var TS_START = new Date(parseInt(arHash[1]));

			if (TS_START.getYear() && TS_START.valueOf())
				this.SETTINGS.DATE_START = TS_START;

			var TS_FINISH = new Date(parseInt(arHash[2]));

			if (TS_START.getYear() && TS_START.valueOf())
				this.SETTINGS.DATE_FINISH = TS_FINISH;
		}
	},

	__reloadCurrentView: function() {
		if (window.BX)
		{
			var wnd = BX.WindowManager.Get(); wnd.Close(); BX.closeWait(); this.LoadData();
		}
		else
		{
			jsPopup.AllowClose(); jsPopup.CloseDialog(); CloseWaitWindow(); this.LoadData();
		}
	}
}

function JCCalendarFilter(_callback, _parentNode, _arTypes, MESSAGES, typeBgColors)
{
	var _this = this;

	this.TIMER = null;
	this.TIMEOUT = 300;

	this._parentNode = _parentNode;

	this.LAYOUT = document.body.appendChild(document.createElement('DIV'));
	this.LAYOUT.className = 'bx-calendar-filter';

	var pos = BX.pos(_parentNode.parentNode.parentNode, true);

	this.LAYOUT.style.top = (pos.bottom - 5) + 'px';
	this.LAYOUT.style.left = (pos.left + 5) + 'px';

	this.LAYOUT.onclick = BX.PreventDefault;

	this.arTypes = _arTypes;

	this.arCurrentChecked = {};

	this.LAYOUT.innerHTML = '<div class="bx-filter-title">'+
			'<table cellspacing="0" style="width:100% !important; padding:0px !important; ">'+
			'	<tr>'+
			'		<td class="bx-filter-title-text" id="bx_filter_title_text">' + MESSAGES.INTR_ABSC_TPL_FILTER_OFF + '</td><td width="0%"><a class="bx-filter-close" id="bx_filter_close"></a></td></tr>'+
			'</table>'+
			'</div>';


	var obCloseBtn = BX('bx_filter_close');

	var obDiv = this.LAYOUT.appendChild(document.createElement('DIV'));
	obDiv.className = 'bx-calendar-color-all';

	this.CHECK_ALL = obDiv.appendChild(BX.create('INPUT', {
		props: {
			type: 'checkbox',
			id: 'bx_abs_show_all_' + parseInt(Math.random() * 100000),
			defaultChecked: true,
			checked: true
		}
	}));

	var obLabel = obDiv.appendChild(document.createElement('LABEL'));
	obLabel.innerHTML = MESSAGES.IAC_FILTER_TYPEFILTER_ALL;
	obLabel.htmlFor = this.CHECK_ALL.id;

	obLabel.onclick = this.CHECK_ALL.onclick = function (e)
	{
		if (null == e) e = window.event;
		e.cancelBubble = true;

		if (null != _this.TIMER)
			clearTimeout(_this.TIMER);

		for (var i = 0; i < _this.arTypes.length; i++)
		{
			_this.arTypes[i].INPUT.checked = _this.arCurrentChecked[_this.arTypes[i].NAME] = this.checked;
		}

		if (this.checked)
			_this.Run();
		else
			_this.TIMER = setTimeout(_this.Run, _this.TIMEOUT);
	}

	for (var i=0; i < this.arTypes.length; i++)
	{
		if (typeof this.arTypes[i] != "undefined")
		{
			this.arCurrentChecked[this.arTypes[i].NAME] = true;

		obDiv = this.LAYOUT.appendChild(document.createElement('DIV'));
		obDiv.className = 'bx-calendar-color-' + this.arTypes[i].NAME;


		obDiv.style.background = typeBgColors[this.arTypes[i].NAME];


		this.arTypes[i].INPUT = obDiv.appendChild(BX.create('INPUT', {
			props: {
				type: 'checkbox',
				checked: true,
				defaultChecked: true,
				id: 'filter_' + this.arTypes[i].NAME
			}
		}));

		this.arTypes[i].INPUT.BX_FILTER_ID = this.arTypes[i].NAME;

		var obLabel = obDiv.appendChild(document.createElement('LABEL'));
		obLabel.htmlFor = this.arTypes[i].INPUT.id;
		obLabel.innerHTML = this.arTypes[i].TITLE;

		obLabel.onclick = this.arTypes[i].INPUT.onclick = function(e)
		{
			if (null == e) e = window.event;
			e.cancelBubble = true;

			if (null != _this.TIMER)
				clearTimeout(_this.TIMER);

			_this.arCurrentChecked[this.BX_FILTER_ID] = this.checked;

				_this.TIMER = setTimeout(_this.Run, _this.TIMEOUT);
			}
		}
	}

	this._parentNode.parentNode.appendChild(this.LAYOUT);
	var obLabel = document.createElement('SPAN'); obLabel.className = 'bx-label';
	obLabel.innerHTML = this._parentNode.previousSibling.innerHTML;
	this._parentNode.previousSibling.parentNode.insertBefore(obLabel, this._parentNode.previousSibling);
	this._parentNode.previousSibling.parentNode.removeChild(this._parentNode.previousSibling);

	this._parentNode.onclick = this._parentNode.previousSibling.onclick = function()
	{
		if (_this.LAYOUT.style.display == 'block')
		{
			_this.LAYOUT.style.display = 'none';
			BX.unbind(document, "click", _this.CheckClick);
		}
		else
		{
			_this.LAYOUT.style.display = 'block';
			setTimeout(function(){BX.bind(document, "click", _this.CheckClick)}, 10);
		}
	}

	this.Run = function()
	{
		_this.TIMER = null;

		_this._parentNode.className = 'bx-indicator bx-indicator-off';
		document.getElementById('bx_filter_title_text').innerHTML = MESSAGES.INTR_ABSC_TPL_FILTER_OFF;
		_this.CHECK_ALL.checked = true;

		for (var i = 0; i < _this.arTypes.length; i++)
		{
			if (!_this.arCurrentChecked[_this.arTypes[i].NAME])
			{
				_this._parentNode.className = 'bx-indicator bx-indicator-on';
				document.getElementById('bx_filter_title_text').innerHTML = MESSAGES.INTR_ABSC_TPL_FILTER_ON;
				_this.CHECK_ALL.checked = false;
				break;
			}
		}

		_callback('TYPE', _this.arCurrentChecked);
	}

	this.CheckClick = function(e)
	{
		if (!e) e = window.event;

		if (_this.LAYOUT.style.display == 'block')
		{
			_this.LAYOUT.style.display = 'none';
			BX.unbind(document, "click", _this.CheckClick);
		}

		return true;
	}

	obCloseBtn.onclick = this.CheckClick;
}

function JCCalendarInfoWin(entry_id, entry_type, user_id, loader, typeBgColors)
{
	this.ID = entry_id
	this.TYPE = entry_type;
	this.USER_ID = user_id;

	var _this = this;
	this.LOADER = loader;

	this.INFO = null;

	this.height = 315; this.width = 500;

	this.DIV = null;

	this._Show = function(data)
	{
		BX.closeWait(jsBXAC.LAYOUT);
		var windowSize = BX.GetWindowInnerSize();
		var windowScroll = BX.GetWindowScrollPos();

		if (null == _this.DIV)
		{
			_this.DIV = document.body.appendChild(document.createElement('DIV'));
			_this.DIV.className = 'bx-calendar-info';

			_this.DIV.style.height = _this.height + 'px';
			_this.DIV.style.width = _this.width + 'px';
			_this.DIV.style.position = 'absolute';
			_this.DIV.style.zIndex = '1000';
		}

		if (null != data)
		{
			eval('_this.INFO = ' + data);

			var departments = '';
			for (var i = 0; i < _this.INFO.USER.UF_DEPARTMENT.length; i++)
			{
				departments += (departments.length > 0 ? ', ' : '')
					+ '<a href="' + jsBXAC.SETTINGS.DETAIL_URL_DEPARTMENT.replace(/#ID#/g, _this.INFO.USER.UF_DEPARTMENT[i].ID) + '">'
					+ BX.util.htmlspecialchars(_this.INFO.USER.UF_DEPARTMENT[i].NAME) + '</a>';
			}

			var strAdmin = '';
			if (_this.TYPE == 1)
			{
				if (null != window.jsBXCalendarAdmin)
				{
					strAdmin += '<div class="bx-calendar-info-admin">'
						+ '<a href="javascript:void(0)" class="bx-calendar-delete" id="bx_calendar_entry_delete_' + _this.ID + '">'
							+ window.jsBXCalendarAdmin.DELETE
						+ '</a>'
						+ '<a href="javascript:void(0)" onclick="'+ GetAbsenceDialog(_this.ID) + '" class="bx-calendar-edit" id="bx_calendar_entry_edit_' + _this.ID + '">'
							+ window.jsBXCalendarAdmin.EDIT
						+ '</a>'
					+ '</div>';
				}

				var strDate = _this.INFO.ENTRY.DATE_ACTIVE_FROM + ' - ' + _this.INFO.ENTRY.DATE_ACTIVE_TO;
			}
			else
			{
				strAdmin += '<div class="bx-calendar-info-admin">'
					+ '<a href="'
						+ jsBXAC.SETTINGS.DETAIL_URL_PERSONAL.replace(/#USER_ID#/g, _this.USER_ID).replace(/#EVENT_ID#/g, _this.ID)
					+ '" class="bx-calendar-personal">'
						+ jsBXAC.MESSAGES.INTR_ABSC_TPL_PERSONAL_LINK_TITLE
					+ '</a>'
				+ '</div>';

				if ('NONE' == _this.INFO.ENTRY.PROPERTY_PERIOD_TYPE_VALUE || '' == _this.INFO.ENTRY.PROPERTY_PERIOD_TYPE_VALUE)
				{
					var strDate = _this.INFO.ENTRY.DATE_ACTIVE_FROM + ' - ' + _this.INFO.ENTRY.DATE_ACTIVE_TO;
				}
				else
				{
					var strDate = jsBXAC.MESSAGES.INTR_ABSC_TPL_REPEATING_EVENT
						+ ' (' + jsBXAC.MESSAGES['INTR_ABSC_TPL_REPEATING_EVENT_' + _this.INFO.ENTRY.PROPERTY_PERIOD_TYPE_VALUE] + ')';
				}
			}

			for (var i = 0; i < jsBXAC.TYPES.length; i++)
			{
				if (jsBXAC.TYPES[i].NAME == _this.INFO.ENTRY.TYPE)
				{
					_this.INFO.ENTRY.PROPERTY_ABSENCE_TYPE_VALUE = jsBXAC.TYPES[i].TITLE;
					break;
				}
			}

			_this.DIV.innerHTML = '<div class="bx-calendar-info-header"><a class="bx-calendar-info-close" id="bx_top_close_' + _this.TYPE + '_' + _this.ID + '"></a></div>'
				+ '<div class="bx-calendar-info-data">'
					+ '<div class="bx-calendar-info-data-photo' + (_this.INFO.USER.PERSONAL_PHOTO ? '' : ' no-photo') + '">'
						+ (_this.INFO.USER.PERSONAL_PHOTO ? _this.INFO.USER.PERSONAL_PHOTO : '')
					+ '</div>'
					+ '<div class="bx-calendar-info-data-cont">'
						+ '<div class="bx-calendar-info-data-name">'
							+ '<a href="' + _this.INFO.USER.DETAIL_URL.replace(/#USER_ID#/g, _this.USER_ID) + '">'
								+ BX.util.htmlspecialchars(jsBXAC.FormatName(jsBXAC.SETTINGS.NAME_TEMPLATE, _this.INFO.USER))
							+ '</a>'
						+ '</div>'
						+ '<div class="bx-calendar-info-data-info">'
							+ departments + '<br />'
							+ BX.util.htmlspecialchars(_this.INFO.USER.WORK_POSITION)
						+ '</div>'
						+ '<div class="bx-info-entry bx-calendar-color-' + _this.INFO.ENTRY.TYPE + '" style="background: '+typeBgColors[_this.INFO.ENTRY.TYPE]+'">'
							+ BX.util.htmlspecialchars(_this.INFO.ENTRY.PROPERTY_ABSENCE_TYPE_VALUE)
						+ '</div>'
						+ '<div class="bx-calendar-info-data-date">' + strDate + '</div>'
						+ '<div class="bx-calendar-info-data-title">' + BX.util.htmlspecialchars(_this.INFO.ENTRY.NAME) + '</div>'
						+ '<div class="bx-calendar-info-data-detail">'
							+ BX.util.htmlspecialchars(_this.INFO.ENTRY.DETAIL_TEXT ? _this.INFO.ENTRY.DETAIL_TEXT : _this.INFO.ENTRY.PREVIEW_TEXT)
						+ '</div>'
					+ '</div>'
					+ strAdmin
				+ '</div>'
				+ '<div class="bx-calendar-info-footer"><input type="button" value="' + jsBXAC.MESSAGES.INTR_ABSC_TPL_INFO_CLOSE + '" id="bx_bottom_close_' + _this.TYPE + '_' + _this.ID + '" /></div>';

			if (_this.TYPE == 1)
			{
				if (null != window.jsBXCalendarAdmin)
				{
					document.getElementById('bx_calendar_entry_delete_' + _this.ID).onclick = function() {return _this.deleteEntry();}
				}
			}
		}

		var left = parseInt(windowScroll.scrollLeft + windowSize.innerWidth / 2 - _this.width / 2);
		var top = parseInt(windowScroll.scrollTop + windowSize.innerHeight / 2 - _this.height / 2);

		_this.DIV.style.display = 'block';

		jsFloatDiv.Show(_this.DIV, left, top, 5, true, false);

		document.getElementById('bx_top_close_' + _this.TYPE + '_' + _this.ID).onclick = document.getElementById('bx_bottom_close_' + _this.TYPE + '_' + _this.ID).onclick = function() {_this.Close();}
	}
}

JCCalendarInfoWin.prototype.Show = function(e)
{
	if (null == e) e = window.event;

	if (null == this.DIV)
	{
		var url = this.LOADER + '?MODE=INFO&ID=' + this.ID + '&TYPE=' + this.TYPE
			+ '&SITE_ID=' + jsBXAC.SETTINGS.SITE_ID + '&IBLOCK=' + (this.TYPE == 2 ? jsBXAC.SETTINGS.CALENDAR_IBLOCK_ID : jsBXAC.SETTINGS.IBLOCK_ID) + '&sessid=' + BX.message('bitrix_sessid');

		BX.showWait(jsBXAC.LAYOUT);
		BX.ajax.get(url, this._Show);
	}
	else
	{
		this._Show();
	}
}

JCCalendarInfoWin.prototype.Close = function()
{
	if (null != this.DIV)
	{
		jsFloatDiv.Close(this.DIV);
		this.DIV.style.display = 'none';
	}
}

JCCalendarInfoWin.prototype.Clear = function()
{
	if (null != this.DIV)
	{
		jsFloatDiv.Close(this.DIV);
		this.DIV.parentNode.removeChild(this.DIV);
	}
}

JCCalendarInfoWin.prototype.getEditUrl = function()
{
	return jsBXAC.getEditUrl(this.INFO.ENTRY, false);
}

JCCalendarInfoWin.prototype.deleteEntry = function()
{
	if (confirm(jsBXCalendarAdmin.DELETE_CONFIRM + ' "' + BX.util.htmlspecialcharsback(this.INFO.ENTRY.NAME) +'"?'))
	{
		BX.showWait();

		BX.ajax.get(
			'/bitrix/tools/intranet_absence.php?action=delete'+ '&sessid=' + BX.message('bitrix_sessid') + '&absenceID='+ this.INFO.ENTRY.ID + '&js=1',
			BX.delegate(
				function()
				{
					BX.closeWait(); jsBXAC.LoadData();
				},
				this
			)
		);
	}

	return false;
}

/**************************/

BX.AbsenceCalendar =
{
	bInit: false,
	popup: null,
	arParams: {}
}

BX.AbsenceCalendar.Init = function(arParams)
{
	if(arParams)
		BX.AbsenceCalendar.arParams = arParams;

	if(BX.AbsenceCalendar.bInit)
		return;

	BX.message(arParams['MESS']);

	BX.AbsenceCalendar.bInit = true;

	BX.ready(BX.delegate(function()
	{
		BX.AbsenceCalendar.popup = new BX.PopupWindow("BXAbsence", null, {
			autoHide: false,
			zIndex: 0,
			offsetLeft: 0,
			offsetTop: 0,
			draggable: {restrict:true},
			closeByEsc: true,
			titleBar: BX.message('INTR_ABSENCE_TITLE'),
			closeIcon: { right : "12px", top : "10px"},
			buttons: [
				new BX.PopupWindowButton({
					className : "popup-window-button-accept",
					events : { click : function()
					{
						var form = BX('ABSENCE_FORM');
						handler = BX.delegate(function(result) {
							if (result == "close")
							{
								BX.AbsenceCalendar.popup.close();
								jsBXAC.LoadData();
							}
							else if (/^error:/.test(result))
							{
								var obErrors = BX.create('DIV', {
									html: '<div class="webform-round-corners webform-error-block" style="margin-top:5px" id="error">\
												<div class="webform-corners-top"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>\
												<div class="webform-content">\
													<ul class="webform-error-list">'+result.substring(6, result.length)+'</ul>\
												</div>\
												<div class="webform-corners-bottom"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>\
											</div>'
								})

								if (BX.findChild(BX.AbsenceCalendar.popup.contentContainer, {className: 'webform-error-block'}, true))
								{
									BX.AbsenceCalendar.popup.contentContainer.replaceChild(obErrors, BX.AbsenceCalendar.popup.contentContainer.firstChild);
								}
								else
								{
									BX.AbsenceCalendar.popup.contentContainer.insertBefore(obErrors, BX.AbsenceCalendar.popup.contentContainer.firstChild);
								}

							}
							else
							{
								BX.AbsenceCalendar.popup.setContent(result);
								jsBXAC.LoadData();
							}
						});
						if(form)
						{
							if (!form.reload)
							{
								BX.ajax.submit(form, handler);
							}
							else
							{
								BX.ajax.get(form.action, handler);
							}
						}
					}}
				}),

				new BX.PopupWindowButtonLink({
					text: BX.message('INTR_CLOSE_BUTTON'),
					className: "popup-window-button-link-cancel",
					events: { click : function()
					{
						this.popupWindow.close();
					}}
				})
			],
			content: '<div style="width:450px;height:230px"></div>',
			events: {
				onAfterPopupShow: function()
				{
					this.setContent('<div style="width:450px;height:230px">'+BX.message('INTR_LOADING')+'</div>');
					BX.ajax.post(
						'/bitrix/tools/intranet_absence.php',
						{
							lang: BX.message('LANGUAGE_ID'),
							site_id: BX.message('SITE_ID') || '',
							arParams: BX.AbsenceCalendar.arParams
						},
						BX.delegate(function(result)
						{
							this.setContent(result);
						},
						this)
					);
				}
			}
		});
	}, this));
}

BX.AbsenceCalendar.ShowForm = function(arParams)
{
	BX.AbsenceCalendar.Init(arParams);
	BX.AbsenceCalendar.popup.show();
}
