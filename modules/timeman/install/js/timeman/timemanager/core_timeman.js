;(function() {

var BX = window.BX;
if (BX.timeman) return;

var TMPoint = '/bitrix/tools/timeman.php',
	intervals = {
		OPENED: 60000,
		CLOSED: 30000,
		EXPIRED: 30000,
		START: 30000
	},

	selectedTimestamp = 0,
	errorReport = '',
	SITE_ID = BX.message('SITE_ID'),
	calendarLastParams = null,

	waitDiv = null,
	waitTime = 1000,
	waitPopup = null,
	waitTimeout = null;

BX.timeman = function(id, data, site_id)
{
	SITE_ID = site_id;
	new BX.CTimeMan(id, data);
};

BX.timeman.TASK_SUFFIXES = {"-1": "overdue", "-2": "new", 1: "new", 2: "accepted", 3: "in-progress", 4: "waiting", 5: "completed", 6: "delayed", 7: "declined"};

BX.timeman_query = function(action, data, callback, bForce)
{
	if (BX.type.isFunction(data))
	{
		callback = data;data = {};
	}
	data['device'] = 'browser';
	var query_data = {
		'method': 'POST',
		'dataType': 'json',
		'url': TMPoint + '?action=' + action + '&site_id=' + SITE_ID + '&sessid=' + BX.bitrix_sessid(),
		'data':  BX.ajax.prepareData(data),
		'onsuccess': function(data) {
			BX.timeman.closeWait();
			callback(data, action)
		},
		'onfailure': function(type, e) {
			BX.timeman.closeWait();
			if (e && e.type == 'json_failure')
			{
				(new BX.PopupWindow('timeman_failure_' + Math.random(), null, {
					content: BX.create('DIV', {
						style: {width: '300px'},
						html: BX.message('JS_CORE_TM_ERROR') + '<br /><br /><small>' + BX.util.strip_tags(e.data) + '</small>'
					}),
					buttons: [
						new BX.PopupWindowButton({
							text : BX.message('JS_CORE_WINDOW_CLOSE'),
							className : "popup-window-button-decline",
							events : {
								click : function() {this.popupWindow.close()}
							}
						})
					]
				})).show();
			}

		}
	};

	if (action == 'update')
	{
		query_data.lsId = 'tm-update';
		query_data.lsTimeout = intervals.START/1000 - 1;
		query_data.lsForce = !!bForce;
	}
	else if (action == 'report')
	{
		query_data.lsId = 'tm-report';
		query_data.lsTimeout = 29;
		query_data.lsForce = !!bForce;
	}

	return BX.ajax(query_data);
}

BX.timeman.formatTime = function(time, bSec, bSkipAmPm)
{
	if (typeof time == 'object' && time.constructor == Date)
		return BX.timeman.formatTimeOb(time, bSec);

	var mt = '';
	if (BX.isAmPmMode() && !bSkipAmPm)
	{
		if (parseInt(time/3600) > 12)
		{
			time = parseInt(time) - 12*3600;
			mt = ' pm';
		}
		else if (parseInt(time/3600) == 12)
		{
			mt = ' pm';
		}
		else if (parseInt(time/3600) == 0)
		{
			time = parseInt(time) + 12*3600;
			mt = ' am';
		}
		else
			mt = ' am';

		if (!!bSec)
			return parseInt(time/3600) + ':' + BX.util.str_pad(parseInt((time%3600)/60), 2, '0', 'left') + ':' + BX.util.str_pad(time%60, 2, '0', 'left') + mt;
		else
			return parseInt(time/3600) + ':' + BX.util.str_pad(parseInt((time%3600)/60), 2, '0', 'left') + mt;
	}
	else
	{
		if (!!bSec)
			return BX.util.str_pad(parseInt(time/3600), 2, '0', 'left') + ':' + BX.util.str_pad(parseInt((time%3600)/60), 2, '0', 'left') + ':' + BX.util.str_pad(time%60, 2, '0', 'left') + mt;
		else
			return BX.util.str_pad(parseInt(time/3600), 2, '0', 'left') + ':' + BX.util.str_pad(parseInt((time%3600)/60), 2, '0', 'left') + mt;
	}
}

BX.timeman.formatDate = function(tsDate, format)
{
	var date = new Date(tsDate*1000) || new Date();
	var str = !!format
			? format :
			BX.message('FORMAT_DATE');

	return str.replace(/YYYY/ig, date.getFullYear())
		.replace(/MMMM/ig, BX.util.str_pad_left((date.getMonth()+1).toString(), 2, '0'))
		.replace(/MM/ig, BX.util.str_pad_left((date.getMonth()+1).toString(), 2, '0'))
		.replace(/M/ig, BX.util.str_pad_left((date.getMonth()+1).toString(), 2, '0'))
		.replace(/DD/ig, BX.util.str_pad_left(date.getDate().toString(), 2, '0'))
		.replace(/HH/ig, BX.util.str_pad_left(date.getHours().toString(), 2, '0'))
		.replace(/MI/ig, BX.util.str_pad_left(date.getMinutes().toString(), 2, '0'))
		.replace(/SS/ig, BX.util.str_pad_left(date.getSeconds().toString(), 2, '0'));
}

BX.timeman.formatTimeOb = function(time, bSec)
{
	if (!!bSec)
		return BX.util.str_pad(time.getHours(), 2, '0', 'left') + ':' + BX.util.str_pad(time.getMinutes(), 2, '0', 'left') + ':' + BX.util.str_pad(time.getSeconds(), 2, '0', 'left');
	else
		return BX.util.str_pad(time.getHours(), 2, '0', 'left') + ':' + BX.util.str_pad(time.getMinutes(), 2, '0', 'left');
}

BX.timeman.unFormatTime = function(time)
{
	var q = time.split(/[\s:]+/);
	if (q.length == 3)
	{
		var mt = q[2];
		if (mt == 'pm' && q[0] < 12)
			q[0] = parseInt(q[0], 10) + 12;

		if (mt == 'am' && q[0] == 12)
			q[0] = 0;

	}
	return parseInt(q[0], 10) * 3600 + parseInt(q[1], 10) * 60;
}

BX.timeman.formatWorkTime = function(time, bSec)
{
	if (!!bSec)
		return parseInt(time/3600) + BX.message('JS_CORE_H') + ' ' + parseInt((time%3600)/60) + BX.message('JS_CORE_M') + ' ' + time%60 + BX.message('JS_CORE_S');
	else
		return parseInt(time/3600) + BX.message('JS_CORE_H') + ' ' + parseInt((time%3600)/60) + BX.message('JS_CORE_M');
}

BX.timeman.formatWorkTimeView = function(time, view)
{
	if (!view)
		return BX.timeman.formatWorkTime(time);

	if (BX.type.isString(view))
	{
		view = BX.timer.getHandler(view);
	}

	return view(parseInt(time/3600), parseInt((time%3600)/60), time%60);
}

BX.timeman.showWait = function(div, timeout)
{
	waitDiv = waitDiv || div;
	div = BX(div || waitDiv) || document.body;

	if (waitTimeout)
		BX.timeman.closeWait();

	if (timeout !== 0)
	{
		return (waitTimeout = setTimeout(function(){
			BX.timeman.showWait(div, 0)
		}, timeout || waitTime));
	}

	if (!waitPopup)
	{
		waitPopup = new BX.PopupWindow('timeman_wait', div, {
			autoHide: true,
			lightShadow: true,
			content: BX.create('DIV', {props: {className: 'tm_wait'}})
		});
	}
	else
	{
		waitPopup.setBindElement(div);
	}

	var height = div.offsetHeight, width = div.offsetWidth;
	if (height > 0 && width > 0)
	{
		waitPopup.setOffset({
			offsetTop: -parseInt(height/2+15),
			offsetLeft: parseInt(width/2-15)
		});

		waitPopup.show();
	}

	return waitPopup;
}

BX.timeman.closeWait = function()
{
	if (waitTimeout)
	{
		clearTimeout(waitTimeout);
		waitTimeout = null;
	}

	if (waitPopup)
	{
		waitPopup.close();
	}
}

function _unShowInputError()
{
	BX.removeClass(this, 'bx-tm-popup-report-error');
	this.onkeypress = null;
}

function _showInputError(inp)
{
	BX.addClass(inp, 'bx-tm-popup-report-error');
	inp.focus();
	inp.onkeypress = _unShowInputError;
}

BX.timeman.editTime = function(e)
{
	if(!this.BXTIMEINPUT)
		return true;

	var
		enterHandler = function(e) {if (e.keyCode == 13) save(e);}
		inputH = BX.create('INPUT', {
			props: {className: 'tm-time-edit-input'},
			attrs: {maxLength: 2},
			events: {keypress: enterHandler}
		}),
		inputM = BX.create('INPUT', {
			props: {className: 'tm-time-edit-input'},
			attrs: {maxLength: 2},
			events: {keypress: enterHandler}
		});

	var
		content = BX.create('DIV', {
			children: [
				inputH,
				BX.create('SPAN', {html: '&nbsp;' + BX.message('JS_CORE_H') + '&nbsp;&nbsp;'}),
				inputM,
				BX.create('SPAN', {html: '&nbsp;' + BX.message('JS_CORE_M')})
			]
		}),

		resultText = this, resultInput = this.BXTIMEINPUT, checkInput = this.BXCHECKINPUT,

		save = function(e) {
			var h = parseInt(inputH.value), m = parseInt(inputM.value)
			if (isNaN(h)) h = 0;
			if (isNaN(m)) m = 0;

			/*if (h < 0 || h > 23)
			{
				_showInputError(inputH);
				return BX.PreventDefault(e);
			}*/
			if (m < 0 || m > 59)
			{
				_showInputError(inputM);
				return BX.PreventDefault(e);
			}

			wnd.close();

			resultInput.value = h * 3600 + m * 60;
			resultText.innerHTML = BX.timeman.formatWorkTime(resultInput.value);

			checkInput.checked = resultInput.value > 0;

			return BX.PreventDefault(e);
		},

		wnd = BX.PopupWindowManager.create(
		'time_edit' + (parseInt(Math.random() * 100000)), this,
		{
			autoHide: true,
			lightShadow: true,
			content: content,
			buttons: [
				new BX.PopupWindowButton({
					text : 'OK',//BX.message('JS_CORE_TM_B_SAVE'),
					className : "popup-window-button-accept",
					events : {
						click : save
					}
				}),
				new BX.PopupWindowButtonLink({
					text : BX.message('JS_CORE_TM_B_CLOSE'),
					className : "popup-window-button-link-cancel",
					events : {
						click : function() {wnd.close()}
					}
				})
			]
		}
	);

	inputH.value = parseInt(resultInput.value / 3600);
	inputM.value = parseInt((resultInput.value % 3600) / 60);

	wnd.show();
	inputH.focus();

	return BX.PreventDefault(e);
}

BX.CTimeMan = function(div, DATA)
{
	window.BXTIMEMAN = this;
	this.bInited = false;
	this.DIV = div || 'bx_tm';

	this.INTERVAL = null;
	this.INTERVAL_TIMEOUT = intervals.START;

	this.PARTS = {};
	this.DATA = DATA;
	this.EVENTS = (DATA ? DATA.EVENTS : null) || [];
	this.TASKS = (DATA ? DATA.TASKS : null) || [];

	this.WND = new BX.CTimeManWindow(this, {
		node: this.DIV,
		type: ['right', 'top']
	});
	this.WND.ACTIONS = {
		OPEN: BX.delegate(this.OpenDay, this),
		CLOSE: BX.proxy(this.CloseDayShowForm, this),
		REOPEN: BX.delegate(this.ReOpenDay, this),
		PAUSE: BX.delegate(this.PauseDay, this)
	};
	this.blockedBtn = null;

	this.ERROR = false;
	this.DENY_QUERY = false;

	this.FREE_MODE = false;

	BX.ready(BX.delegate(this.Init, this));
	BX.addCustomEvent(window, 'onLocalStorageChange', BX.delegate(function(data) {
		if (data.key == 'ajax-tm-update' && data.value != 'BXAJAXWAIT')
		{
			var v = data.value;
			if (BX.type.isString(v))
				v = BX.parseJSON(v);
			if (v)
			{
				this._Update(v, 'update');
			}
		}
	}, this));
}

BX.CTimeMan.prototype.Init = function()
{
	this.DIV = BX(this.DIV);

	BX.unbindAll(this.DIV);
	BX.bind(this.DIV, 'click', BX.proxy(BXTIMEMAN.Open, BXTIMEMAN));

	this.bInited = true;

	if (!!this.DATA)
	{
		this.setData(this.DATA);
		BX.ajax.replaceLocalStorageValue('tm-update', this.DATA, intervals.START/1000 - 1)
	}

	BX.onCustomEvent(window, "onTimemanInit");
}

BX.CTimeMan.prototype.setBindOptions = function(bindOptions)
{
	this.WND.bindOptions = bindOptions;
}

BX.CTimeMan.prototype.setData = function(DATA)
{
	if (!DATA)
		return;

	if (!DATA.INFO)
		this.firstTime = true;

	this.DATA = DATA;
	this.FREE_MODE = !!this.DATA.TM_FREE;

	this.INTERVAL_TIMEOUT = intervals[this.DATA.STATE] || intervals.START;

	if (this.firstTime && this.DATA.INFO)
	{
		BX.onCustomEvent(this, 'onTimeManNeedRebuild', [this.DATA]);
		this.firstTime = false;
	}
	else
	{
		BX.onCustomEvent(this, 'onTimeManDataRecieved', [this.DATA]);
	}
}

BX.CTimeMan.prototype.Update = function(force)
{
	if(!!force) this._unsetError();
	this.Query('update', BX.proxy(this._Update, this), force);
}

BX.CTimeMan.prototype._Update = function(data, action)
{
	if (this._checkQueryError(data))
	{
		if (this.WND.CLOCKWND && !this.WND.CLOCKWND.SHOW)
		{
			this.WND.CLOCKWND.Clear();
		}

		this.setData(data);

		if (action != 'update')
		{
			BX.ajax.replaceLocalStorageValue('tm-update', data, intervals.START/1000 - 1)
			this.WND.clearTempData();
		}

		if (!!data.CLOSE_TIMESTAMP)
		{
			this.close_day_form_ts = data.CLOSE_TIMESTAMP;
			this.close_day_form_ts_report = data.CLOSE_TIMESTAMP_REPORT;
			this.CloseDayShowForm();
		}
	}

	this.unBlockActionBtn();
}

BX.CTimeMan.prototype.Query = function(action, data, callback, bForce)
{
	if (this.DENY_QUERY)
		return;

	if (BX.type.isFunction(data))
	{
		bForce = !!callback; callback = data; data = {};
	}

	if (this.WND && this.WND.SHOW)
	{
		data.full = 'Y';
		BX.timeman.showWait(this.WND.DIV || this.DIV);
	}

	BX.timeman_query(action, data, callback, bForce);
}

BX.CTimeMan.prototype._setFatalError = function(e)
{
	this.ERROR = true;

	if (this.INTERVAL)
		clearTimeout(this.INTERVAL);

	if (this.DIV)
	{
		this.DIV.innerHTML = e;

		BX.addClass(this.DIV, 'bx-tm-error');
	}
}

BX.CTimeMan.prototype._setDenyError = function(e)
{
	this.DENY_QUERY = true;

	if (this.INTERVAL)
		clearTimeout(this.INTERVAL);

	if (this.DIV)
	{
		BX.addClass(this.DIV, 'bx-tm-error');
	}
}

BX.CTimeMan.prototype._setRestrictionError = function(e)
{
	this.DENY_QUERY = true;

	var div = BX.create('DIV');

	BX.html(div, e.data).then(function(){
		(new BX.PopupWindow('timeman_failure_' + Math.random(), null, {
			content: div,
			titleBar: BX.message('JS_CORE_TM_RESTRICTION_TITLE'),
			closeIcon: true,
			autoHide: true,
			closeByEsc: true
		})).show();
	});


	BX.addClass(this.DIV, 'bx-tm-error');
}

BX.CTimeMan.prototype._unsetError = function()
{
	if (this.ERROR)
	{
		this.ERROR = false;
	}

	this.DENY_QUERY = false;

	if (this.DIV)
	{
		BX.addClass(this.DIV, 'bx-tm-error');

		BX.removeClass(this.DIV, 'bx-tm-error');
	}
}

BX.CTimeMan.prototype._checkQueryError = function(data)
{
	if (data && data.error)
	{
		if (data.type)
		{
			switch(data.type)
			{
				case 'fatal':
					this._setFatalError(data.error);
				break;

				case 'deny_query':
					this._setDenyError(data.error);
				break;
			}
		}
		else if (data.error_id == 'REPORT_NEEDED')
		{
			this._showReportField(data.error);
		}
		else if (data.error_id == 'ALERT_WARNING')
		{
			alert(data.error);
		}
		else if (data.error_id == 'CHOOSE_CALENDAR')
		{
			this._showCalendarField(data.error);
		}
		else if (data.error_id == 'WD_EXPIRED')
		{
			setTimeout(BX.proxy(function(){this.Update(true)}, this), 10);
		}
		else if (data.error_id == 'USER_RESTRICTION')
		{
			this._setRestrictionError(data.error);
		}

		return false;
	}

	this._unsetError();

	return true;
}

BX.CTimeMan.prototype._showReportField = function(error_string)
{
	this.WND.showReportField(error_string);
}

BX.CTimeMan.prototype._showCalendarField = function(calendars)
{
	(new BX.CTimeManCalendarSelector(this, {
		node: this.WND.EVENTS,
		data: calendars
	})).Show();
}

BX.CTimeMan.prototype.Open = function()
{
	if (this.WND.Show())
	{
		// todo http://jabber.bx/view.php?id=165797
		this.Query('check_module', {}, function (data) {
			if (data && data.error && data.type === 'fatal')
			{
				return;
			}
			this.Update(true);
		}.bind(this), true);
	}
}

BX.CTimeMan.prototype.setTimestamp = function(ts)
{
	selectedTimestamp = parseInt(ts);
	if (isNaN(selectedTimestamp))
		selectedTimestamp = 0;
}

BX.CTimeMan.prototype.setReport = function(report)
{
	errorReport = report;
}

BX.CTimeMan.prototype.CloseDayShowForm = function(e)
{
	if (this.CLOSE_DAY_FORM)
	{
		this.CLOSE_DAY_FORM.popup.close();
		this.CLOSE_DAY_FORM = null;
	}

	if (this.DATA.REPORT_REQ == 'A' || this.FREE_MODE)
	{
		if (this.DATA.STATE == 'EXPIRED' && !this.WND.TIMESTAMP)
			this.WND.ShowClock();
		else
			this.CloseDay(e);
	}
	else
	{
		this.CLOSE_DAY_FORM = new BX.CTimeManReportForm(
			this,
			{
				node: this.DIV,
				bind: this.DIV,
				mode: 'edit',
				external_finish_ts: this.close_day_form_ts,
				external_finish_ts_report: this.close_day_form_ts_report
			}
		);

		this.CLOSE_DAY_FORM.Show();
	}

	if (e || window.event)
		return BX.PreventDefault(e);

	return false;
}

BX.CTimeMan.prototype.CheckNeedToReportImm = function()
{
	this.CheckNeedToReport(true);
}

BX.CTimeMan.prototype.CheckNeedToReport = function(bForce)
{
	if (!this.WEEKLY_FORM)
	{
		bForce = (bForce)?"Y":"N";
		BX.timeman_query('check_report', {force:bForce}, BX.proxy(this.ShowFormWeekly, this));
	}
	else
		this.WEEKLY_FORM.popup.show();
	return false;
}

BX.CTimeMan.prototype.ShowFormWeekly = function(data)
{
	this.ShowCallReport = false;
	report_info = data.REPORT_INFO;
	report_data = data.REPORT_DATA;

	this.REPORT_FULL_MODE = report_info.MODE;

	if (report_info.IS_REPORT_DAY == "Y")
	{
		this.ShowCallReport = true;
		BX.addCustomEvent("OnWorkReportSend", function(){
			if (BX("work_report_call_link"))
				BX.hide(BX("work_report_call_link"));
		});
	}

	if (report_data.INFO)
	{
		if (!this.WEEKLY_FORM || this.WEEKLY_FORM == null)
		{
			this.WEEKLY_FORM = new BX.CTimeManReportFormWeekly(
				this,
				{
					node: this.DIV,
					bind: this.DIV,
					mode: 'edit'
				}
			);
			this.WEEKLY_FORM.data = report_data;
			this.WEEKLY_FORM.Show();
		}
		else
			this.WEEKLY_FORM.popup.show();
		window.WEEKLY_FORM = this.WEEKLY_FORM;
	}

	return false;
}

BX.CTimeMan.prototype.getActionBtn = function(e)
{
	if (
		e.target.classList.contains('popup-window-button')
		|| e.target.classList.contains('ui-btn')
	)
	{
		return e.target;
	}
	else
	{
		return e.target.parentElement;
	}
}

BX.CTimeMan.prototype.blockActionBtn = function(e)
{
	this.blockedBtn = this.getActionBtn(e);

	if (this.blockedBtn.classList.contains('popup-window-button'))
	{
		BX.addClass(this.blockedBtn, 'popup-window-button-wait');
	}
	else
	{
		BX.addClass(this.blockedBtn, 'ui-btn-wait');
	}
}

BX.CTimeMan.prototype.unBlockActionBtn = function()
{
	if (this.blockedBtn === null)
	{
		return;
	}

	if (this.blockedBtn.classList.contains('popup-window-button'))
	{
		BX.removeClass(this.blockedBtn, 'popup-window-button-wait');
	}
	else
	{
		BX.removeClass(this.blockedBtn, 'ui-btn-wait');
	}

	this.blockedBtn = null;
}

BX.CTimeMan.prototype.CloseDay = function(e)
{
	if (this.blockedBtn)
	{
		return BX.PreventDefault(e);
	}

	this.blockActionBtn(e);

	var data = {timestamp: selectedTimestamp, report: errorReport};
	if (this.WND && this.WND.CLOCKWND && (this.WND.CLOCKWND.customUserDate !== undefined))
	{
		data.customUserDate = this.WND.CLOCKWND.customUserDate;
	}
	if (this.DATA.RECORD_ID !== undefined)
	{
		data.recordId = this.DATA.RECORD_ID;
	}
	this.Query('close', data, BX.proxy(this._Update, this));
	this.setTimestamp(0);
	this.setReport('');
	return BX.PreventDefault(e);
}

BX.CTimeMan.prototype.OpenDay = function(e)
{
	if (this.blockedBtn)
	{
		return BX.PreventDefault(e);
	}

	this.blockActionBtn(e);

	var data = {timestamp: selectedTimestamp, report: errorReport};
	if (this.WND && this.WND.CLOCKWND && (this.WND.CLOCKWND.customUserDate !== undefined))
	{
		data.customUserDate = this.WND.CLOCKWND.customUserDate;
	}
	this.Query('open', data, BX.proxy(this._Update, this));
	this.setTimestamp(0);
	this.setReport('');
	return BX.PreventDefault(e);
}

BX.CTimeMan.prototype.ReOpenDay = function(e)
{
	if (this.blockedBtn)
	{
		return BX.PreventDefault(e);
	}

	this.blockActionBtn(e);

	var newActionName = 'reopen';
	if (this.DATA && this.DATA.INFO && this.DATA.INFO.PAUSED)
	{
		newActionName = 'continue';
	}
	this.setTimestamp(0);
	this.setReport('');
	this.Query('reopen', {newActionName: newActionName}, BX.proxy(this._Update, this));
	return BX.PreventDefault(e);
}

BX.CTimeMan.prototype.PauseDay = function(e)
{
	if (this.blockedBtn)
	{
		return BX.PreventDefault(e);
	}

	this.blockActionBtn(e);

	this.setTimestamp(0);
	this.setReport('');
	this.Query('pause', {}, BX.proxy(this._Update, this));
	return BX.PreventDefault(e);
};

BX.CTimeMan.prototype.calendarEntryAdd = function(params, cb)
{
	calendarLastParams = params;
	this.Query('calendar_add', params, cb || BX.proxy(this._Update, this));
};

BX.CTimeMan.prototype.taskPost = function(entry, callback)
{
	if (typeof entry == 'object')
	{
		this.TASK_CHANGES[entry.action].push(entry.id);

		if (this.TASK_CHANGE_TIMEOUT)
			clearTimeout(this.TASK_CHANGE_TIMEOUT);

		if (entry.action == 'add')
			this.taskPost();
		else
		{
			this.DENY_QUERY = true; // we should deny popup updating because of possible errors

			this.TASK_CHANGE_TIMEOUT = setTimeout(
				BX.proxy(this.taskPost, this), 1000
			);
		}
	}
	else
	{
		this.DENY_QUERY = false;

		this.TASKS = [];
		this.Query('task', this.TASK_CHANGES, BX.proxy(this._Update, this));
		this.TASK_CHANGES = {add: [], remove: []};
	}
};

BX.CTimeMan.prototype.taskEntryAdd = function(params, cb)
{
	this.TASKS = [];
	this.Query('task', params, cb || BX.proxy(this._Update, this));
};


/***********************************************************/

BX.CTimeManWindow = function(parent, bindOptions)
{
	this.PARENT = parent;
	this.DIV = null;
	this.POPUP = null;
	this.LAYOUT = null;

	this.bindOptions = bindOptions;

	this.DATA = {};
	this.ACTIONS = {};

	this.SHOW = false;
	this.CREATE = false;

	this.TIMESTAMP = false;
	this.ERROR_REPORT = '';
	this.MAIN_BTN_HANDLER = null;

	this.REPORT = null;

	this.DASHBOARD = null;

	this.TASKWND = {};
	this.EVENTWND = {};

	this.isPwtAlertDisplayed = false;

	BX.addCustomEvent(this.PARENT, 'onTimeManDataRecieved', BX.delegate(this.onTimeManChangeState, this));
	BX.addCustomEvent(this.PARENT, 'onTimeManNeedRebuild', BX.delegate(this.onTimeManNeedRebuild, this));
	BX.addCustomEvent('onTopPanelCollapse', BX.proxy(this.Align, this));
	BX.bind(window, 'resize', BX.proxy(this.Align, this));
}

BX.CTimeManWindow.prototype.onTimeManChangeState = function(DATA)
{
	if (!this.CREATE)
		return;

	this.DATA = DATA;

	if (this.SHOW)
		this.Align();
}

BX.CTimeManWindow.prototype.onTimeManNeedRebuild = function(DATA)
{
	this.CREATE = true;

	this.Create(DATA);

	if (this.SHOW)
		this.Align();
}

BX.CTimeManWindow.prototype.Create = function(DATA)
{
	if (!this.CREATE)
		return;

	if (this.NOTICE_TIMER)
	{
		BX.timer.stop(this.NOTICE_TIMER);
		this.NOTICE_TIMER = null;
	}

	if (this.bindOptions.mode == 'popup')
	{
		if (!this.POPUP)
		{
			var p = this.bindOptions.popupOptions || {
				autoHide: true,
				lightShadow: true,
				bindOptions : {
					forceBindPosition : true,
					forceTop : true
				},
				angle : {
					position: "top",
					offset : 50
				}
			};

			p.lightShadow = true;

			this.POPUP = new BX.PopupWindow('timeman_main', this.bindOptions.node, p);
		}

		this.POPUP.setContent(this.CreateLayoutTable(DATA));
	}
	else
	{
		if (this.DIV)
		{
			BX.cleanNode(this.DIV);
			this.DIV = null;
		}

		if (null == this.DIV)
		{
			this.DIV = document.body.appendChild(BX.create('DIV', {
				props: {id: 'tm-popup'},
				events: {
					click: BX.eventCancelBubble
				},
				style: {
					position: 'absolute',
					display: this.SHOW ? 'block' : 'none'
				}
			}));

			this.DIV.appendChild(this.CreateLayoutTable());
		}

		BX.cleanNode(this.LAYOUT);

		this.LAYOUT.appendChild(this.CreateDashboard(DATA));
		this.LAYOUT.appendChild(_createHR());
	}

	this.LAYOUT.appendChild(this.CreateNoticeRow(DATA));
	this.LAYOUT.appendChild(this.CreateMainRow(DATA));

	if (DATA.INFO)
	{
		if(DATA.PLANNER)
		{
			this.PLANNER = new BX.CPlanner(DATA.PLANNER);
			this.PLANNER.WND = this;

			this.LAYOUT.appendChild(this.PLANNER.drawAdditional());
		}

		this.TABCONTROL = new BX.CTimeManTabControl(
			this.LAYOUT.appendChild(BX.create('DIV'))
		);

		if (DATA.PLANNER)
		{
			this.TABCONTROL.addTab({
				id: 'plans',
				title: BX.message('JS_CORE_TM_PLAN'),
				content: [
					this.PLANNER.draw()
				]
			});
		}

		this.TABCONTROL.addTab({
			id: 'report',
			title: BX.message('JS_CORE_TM_REPORT'),
			content: this.CreateReport()
		});

		if(this.PARENT.REPORT_FULL_MODE)
		{
			this.call_report = BX.create("SPAN", {
				attrs: {id: "work_report_call_link"},
				props: {className: "wr-call-lable"},
				html: BX.message("JS_CORE_TMR_REPORT_FULL_" + this.PARENT.REPORT_FULL_MODE),
				events: {"click": BX.proxy(BXTIMEMAN.CheckNeedToReportImm, BXTIMEMAN)}
			});

			if (BXTIMEMAN.ShowCallReport == true)
			{
				this.call_report.style.display = "inline-block";
			}
			else
			{
				this.call_report.style.display = "none";
			}

			this.TABCONTROL.HEAD.appendChild(this.call_report);
		}

		BX.addCustomEvent(this.PARENT, 'onTimeManDataRecieved', BX.delegate(this.CreateDashboard, this));
		BX.addCustomEvent('onPlannerQueryResult', BX.delegate(function(data){
			this.DATA.PLANNER = data;
			this.CreateDashboard(this.DATA);
		}, this));
		BX.addCustomEvent(this.PARENT, 'onTimeManDataRecieved', BX.delegate(this.CreateNoticeRow, this));
		BX.addCustomEvent(this.PARENT, 'onTimeManDataRecieved', BX.delegate(this.CreateMainRow, this));

		BX.addCustomEvent(this.PARENT, 'onTimeManDataRecieved', BX.delegate(function(DATA){
			if(!!DATA.PLANNER)
				this.update(DATA.PLANNER);
		}, this.PLANNER));
	}

	BX.onCustomEvent(this, 'onTimeManWindowBuild', [this, this.LAYOUT, DATA])

	if (!this.isPwtAlertDisplayed)
	{
		this.addPwt();
		this.isPwtAlertDisplayed = true;
	}

	this.Align();
}

BX.CTimeManWindow.prototype.CreateDashboard = function(DATA)
{
	var event_time, clock, state, tasks_counter;

	if (null == this.DASHBOARD)
	{
		this.DASHBOARD = BX.create('SPAN', {
			props: {className: 'tm-popup-dashboard'},
			events: {
				click: BX.proxy(this.Hide, this)
			},
			children: [
				BX.create('SPAN', {props:{className:'tm-dashboard-arrow'}}),
				BX.create('SPAN', {
					props:{className:'tm-dashboard-title'},
					html: BX.message('JS_CORE_TM_POPUP_HIDE')
				}),
				(event_time = BX.create('SPAN', {
					children: [
						BX.create('SPAN', {props:{className:'tm-dashboard-bell'}}),
						BX.create('SPAN', {props:{className:'tm-dashboard-text'}})
					]
				})),
				BX.create('SPAN', {props:{className:'tm-dashboard-clock'}}),
				BX.create('SPAN', {
					props: {className:'tm-dashboard-text'},
					children: [
						(clock = BX.create('SPAN', {props: {id: 'bx_tm_clock'}})),
						(state = BX.create('SPAN', {
							props: {className: 'tm-dashboard-subtext', id: 'bx_tm_state'}
						}))
					]
				}),
				(tasks_counter = BX.create('SPAN', {
					children: [
						BX.create('SPAN', {props:{className:'tm-dashboard-flag'}}),
						BX.create('SPAN', {props: {className: 'tm-dashboard-text'}})
					]
				}))
			]
		});

		new BX.CHint({parent: event_time, hint: BX.message('JS_CORE_HINT_EVENTS')});
		new BX.CHint({parent: state.parentNode, hint: BX.message('JS_CORE_HINT_STATE')});
		new BX.CHint({parent: tasks_counter, hint: BX.message('JS_CORE_HINT_TASKS')});
	}
	else
	{
		event_time = this.DASHBOARD.firstChild.nextSibling.nextSibling;
		clock = event_time.nextSibling.nextSibling.firstChild;
		state = clock.nextSibling;
		tasks_counter = this.DASHBOARD.lastChild;
	}

	if (DATA.PLANNER.TASKS_ENABLED && DATA.PLANNER.TASKS_COUNT > 0)
	{
		tasks_counter.lastChild.innerHTML = DATA.PLANNER.TASKS_COUNT;
		BX.show(tasks_counter);
	}
	else
	{
		BX.hide(tasks_counter);
	}

	if (!!DATA.PLANNER.EVENT_TIME)
	{
		event_time.lastChild.innerHTML = DATA.PLANNER.EVENT_TIME;
		BX.show(event_time);
	}
	else
	{
		BX.hide(event_time);
	}

	if (!clock.TIMER)
		clock.TIMER = BX.timer.clock(clock);

	if (DATA.STATE == 'OPENED')
	{
		if (state.TIMER)
		{
			state.TIMER.setFrom(new Date(DATA.INFO.DATE_START*1000));
			state.TIMER.dt = -DATA.INFO.TIME_LEAKS * 1000;
		}
		else
		{
			state.TIMER = BX.timer(state, {
				from: new Date(DATA.INFO.DATE_START*1000),
				dt: -DATA.INFO.TIME_LEAKS * 1000,
				display: 'worktime_timeman'
			});
		}
	}
	else
	{
		if (state.TIMER)
		{
			BX.timer.stop(state.TIMER);
			state.TIMER = null;
			BX.cleanNode(state);
		}

		if (DATA.STATE == 'PAUSED' || DATA.STATE == 'CLOSED' && DATA.CAN_OPEN != 'OPEN')
		{
			var q = (DATA.INFO.DATE_FINISH - DATA.INFO.DATE_START - DATA.INFO.TIME_LEAKS);
			state.innerHTML = BX.timeman.formatWorkTimeView(q, 'worktime_timeman');
		}
	}

	return this.DASHBOARD;
}

BX.CTimeManWindow.prototype.CreateLayoutTable = function()
{
	if (this.bindOptions.mode == 'popup')
	{
		this.LAYOUT = BX.create('DIV', {props: {className: 'tm-popup-content'}});
		return this.LAYOUT;
	}
	else
	{
		var t = BX.create('TABLE', {
			attrs: {cellSpacing: '0'}, props: {className: 'tm-popup-layout'},
			children: [BX.create('TBODY')]
		});

		var r = t.tBodies[0].insertRow(-1);

		var leftBorder = r.insertCell(-1);
		leftBorder.className = 'tm-popup-layout-left';
		leftBorder.appendChild(BX.create('DIV', {props: {className: 'tm-popup-layout-left-spacer'}}));

		var c = r.insertCell(-1);
		c.className = 'tm-popup-layout-center';

		var rightBorder = r.insertCell(-1);
		rightBorder.className = 'tm-popup-layout-right';
		rightBorder.appendChild(BX.create('DIV', {props: {className: 'tm-popup-layout-right-spacer'}}));

		r = t.tBodies[0].insertRow(-1);
		r.insertCell(-1).className = 'tm-popup-layout-left-corner';
		r.insertCell(-1).className = 'tm-popup-layout-center-corner';
		r.insertCell(-1).className = 'tm-popup-layout-right-corner';

		this.LAYOUT = c.appendChild(BX.create('DIV', {props: {className: 'tm-popup-content'}}));

		return t;
	}
};

BX.CTimeManWindow.prototype.CreateNoticeRow = function(DATA)
{
	var row_notice, row_timer, row_edit;

	if (null == this.NOTICE)
	{
		this.NOTICE = BX.create('DIV', {
			props: {className: 'tm-popup-notice'},
			children: [
				BX.create('SPAN', {props: {className: 'tm-popup-notice-left'}}),
				(row_notice = BX.create('SPAN', {props: {className: 'tm-popup-notice-text'}})),
				(row_timer = BX.create('SPAN', {props: {className: 'tm-popup-notice-time'}})),
				(row_edit = BX.create('SPAN', {
					props: {className: 'tm-popup-notice-pencil'},
					events: {
						click: BX.proxy(this.ShowEdit, this)
					}
				})),
				BX.create('SPAN', {props: {className: 'tm-popup-notice-right'}})
			]
		});
		this.NOTICE_STATE = _getStateHash(DATA, ['STATE', 'CAN_OPEN', 'CAN_EDIT']) + '/' + _getStateHash(DATA.INFO, ['DATE_START', 'DATE_FINISH', 'TIME_LEAKS']);
	}
	else
	{
		var newState = _getStateHash(DATA, ['STATE', 'CAN_OPEN', 'CAN_EDIT']) + '/' + _getStateHash(DATA.INFO, ['DATE_START', 'DATE_FINISH', 'TIME_LEAKS']);

		if (newState == this.NOTICE_STATE)
			return null;

		this.NOTICE_STATE = newState;

		if (this.NOTICE_TIMER)
		{
			BX.timer.stop(this.NOTICE_TIMER);
			this.NOTICE_TIMER = null;
		}

		row_notice = this.NOTICE.firstChild.nextSibling;
		row_timer = row_notice.nextSibling;
		row_edit = row_timer.nextSibling;
	}

	if (!DATA.ID || DATA.CAN_EDIT !== 'Y' || (DATA.STATE == 'EXPIRED' && DATA.REPORT_REQ != 'A'))
		BX.hide(row_edit);
	else
		BX.show(row_edit);

	if (DATA.STATE != 'EXPIRED')
	{
		BX.adjust(row_notice, {
			text: BX.message('JS_CORE_TM_WD_OPENED') + ' '
		});

		BX.show(row_timer);

		if (DATA.STATE == 'OPENED')
		{
			this.NOTICE_TIMER = BX.timer(row_timer, {from: DATA.INFO.DATE_START * 1000, accuracy: 1, dt: -1000 * DATA.INFO.TIME_LEAKS, display: 'worktime_notice_timeman'});
		}
		else if (DATA.CAN_OPEN == 'OPEN')
		{
			row_timer.innerHTML = BX.timeman.formatWorkTimeView(0, 'worktime_notice_timeman');
		}
		else
		{
			var q = (DATA.INFO.DATE_FINISH - DATA.INFO.DATE_START - DATA.INFO.TIME_LEAKS);
			row_timer.innerHTML = BX.timeman.formatWorkTimeView(q, 'worktime_notice_timeman');

			//this.NOTICE_TIMER = BX.timer(row_timer, {from: DATA.INFO.DATE_FINISH * 1000, accuracy: 1, dt: 1000 * DATA.INFO.TIME_LEAKS, display: 'worktime_notice_timeman'});
		}
	}
	else
	{
		BX.hide(row_timer);
		BX.adjust(row_notice, {text: BX.message('JS_CORE_TM_WD_EXPIRED')});
	}

	return this.NOTICE;
};

BX.CTimeManWindow.prototype.isMonitorAvailable = function()
{
	return BX.ajax.runAction("bitrix:timeman.api.monitor.isAvailable")
		.then(function(response)
		{
			if (response.data)
			{
				return true;
			}
			else
			{
				return false;
			}
		})
		.catch(function()
		{
			return false;
		});
}

BX.CTimeManWindow.prototype.enableMonitorForCurrentUser = function()
{
	return BX.ajax.runAction("bitrix:timeman.api.monitor.enableForCurrentUser")
		.then(function(response)
		{
			if (response.data)
			{
				return true;
			}
			else
			{
				return false;
			}
		})
		.catch(function()
		{
			return false;
		});
}

BX.CTimeManWindow.prototype.isMonitorEnabled = function()
{
	return BX.ajax.runAction("bitrix:timeman.api.monitor.isEnableForCurrentUser")
		.then(function(response)
		{
			if (response.data)
			{
				return true;
			}
			else
			{
				return false;
			}
		})
		.catch(function()
		{
			return false;
		});
}

BX.CTimeManWindow.prototype.addPwt = function()
{
	if (BX.MessengerCommon.isDesktop())
	{
		return;
	}

	this.isMonitorAvailable().then(function(result) {
		if (!result)
		{
			return;
		}

		if (BXIM.desktopVersion < 55)
		{
			BX('timeman_main').appendChild(
				BX.create('div', {
					props: {
						id: 'timeman-pwt-container'
					},
					style: {
						textAlign: 'center',
						maxWidth: '450px',
						marginLeft: '12px',
						marginRight: '12px',
					},
					children: [
						new BX.UI.Alert({
							icon: BX.UI.Alert.Icon.INFO,
							color: BX.UI.Alert.Color.SUCCESS,
							text: BX.message("JS_CORE_TM_MONITOR_UPDATE_DESKTOP"),
						}).getContainer(),
						BX.create('button', {
							props: {
								className: 'ui-btn ui-btn-success ui-btn-icon-download',
								id: 'timeman-pwt-get-desktop'
							},
							text: BX.message("JS_CORE_TM_MONITOR_GET_DESKTOP_BUTTON"),
							style: {
								marginBottom: '8px',
							},
							events: {
								click : function() {
									window.open('https://www.bitrix24.ru/features/desktop.php', '_blank');
								}
							}
						})
					],
				})
			);

			BX.PULL.subscribe({
				type: BX.PullClient.SubscriptionType.Server,
				moduleId: 'im',
				command: 'desktopOnline',
				callback: function (params, extra, command) {
					this.setPwtStateReadyToEnable();
				}.bind(this)
			});

			return;
		}

		this.isMonitorEnabled().then(function(result) {
			BX('timeman_main').appendChild(
				BX.create('div', {
					props: {
						id: 'timeman-pwt-container'
					},
					style: {
						textAlign: 'center',
						marginLeft: '12px',
						marginRight: '12px',
					},
				})
			);

			if (result)
			{
				this.addPwtAlert();

				return;
			}

			if (
				BXIM === 'undefined'
				|| BXIM.desktopVersion < 55
			)
			{
				return;
			}

			this.setPwtStateReadyToEnable();

		}.bind(this)
	)}.bind(this));
}

BX.CTimeManWindow.prototype.setPwtStateReadyToEnable = function()
{
	var pwtContainer = BX('timeman-pwt-container');
	if (!pwtContainer)
	{
		return;
	}

	pwtContainer.innerHTML = '';

	pwtContainer.appendChild(
		BX.create('button', {
			props: {
				className: 'ui-btn ui-btn-success ui-btn-icon-start',
				id: 'timeman-pwt-enable'
			},
			text: BX.message("JS_CORE_TM_MONITOR_ENABLE_BUTTON"),
			style: {
				marginBottom: '8px',
				width: '100%',
			},
			events: {
				click : function() {
					this.enableMonitorForCurrentUser().then(function(result) {
						if (!result)
						{
							return;
						}

						BX.remove(BX('timeman-pwt-enable'));

						this.addPwtAlert();
					}.bind(this));
				}.bind(this)
			}
		})
	);
}

BX.CTimeManWindow.prototype.addPwtAlert = function()
{
	BX('timeman-pwt-container').appendChild(
		new BX.UI.Alert({
			icon: BX.UI.Alert.Icon.INFO,
			color: BX.UI.Alert.Color.SUCCESS,
			text: BX.message("JS_CORE_TM_MONITOR_ENABLED"),
			customClass: 'ui-alert-text-center'
		}).getContainer()
	);
}

BX.CTimeManWindow.prototype.openMonitorReport = function()
{
	var isUnsupportedApp =
		typeof BXDesktopSystem !== 'undefined'
		&& BXDesktopSystem.GetProperty('versionParts')[3] < 55;

	if (BXIM.desktopVersion < 55 || isUnsupportedApp)
	{
		BXIM.openConfirm({
			title: BX.message('JS_CORE_TM_MONITOR'),
			message: BX.message('JS_CORE_TM_MONITOR_OPEN_ERROR')
		});

		return false;
	}

	BX.desktopUtils.runningCheck(
		function()
		{
			BX.desktopUtils.goToBx("bx://timemanpwt");
		},
		function()
		{
			BXIM.openConfirm({
				title: BX.message('JS_CORE_TM_MONITOR'),
				message: BX.message('JS_CORE_TM_MONITOR_DESKTOP_CLOSED_ERROR')
			});

			return false;
		}
	);
}

BX.CTimeManWindow.prototype.CreateMainRow = function(DATA)
{
	var row_pause;

	if (null == this.MAIN_ROW)
	{
		this.MAIN_ROW = BX.create('DIV', {props: {className: 'tm-popup-timeman'}});

		row_pause = this.MAIN_ROW.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-timeman-pause'},
			children: [
				BX.create('SPAN', {
					props: {
						className: 'tm-popup-timeman-pause-timer-caption'
					},
					text: BX.message('JS_CORE_TM_WD_PAUSED')
				}),
				BX.create('SPAN', {props: {className: 'tm-popup-timeman-pause-time'}})
			]
		}));

		var t = this.MAIN_ROW.appendChild(BX.create('TABLE', {
			attrs: {cellSpacing: '0'},
			props: {className: 'tm-popup-timeman-layout'},
			children: [BX.create('TBODY')]
		})),
		r = t.tBodies[0].insertRow(-1);

		this.MAIN_ROW_CELL_TIMER = r.insertCell(-1);
		this.MAIN_ROW_CELL_TIMER.className = 'tm-popup-timeman-layout-time';

		this.MAIN_ROW_CELL_BTN = r.insertCell(-1);
		this.MAIN_ROW_CELL_BTN.className = 'tm-popup-timeman-layout-button';

		this.MAIN_ROW_CELL_TIMER.appendChild(this.CreateMainPauseControl(DATA));

		this.MAIN_ROW_STATE = _getStateHash(DATA, ['STATE', 'CAN_OPEN', 'CAN_EDIT']) + '/' + _getStateHash(DATA.INFO, ['DATE_START', 'DATE_FINISH', 'TIME_LEAKS']);
	}
	else
	{
		var newState = _getStateHash(DATA, ['STATE', 'CAN_OPEN', 'CAN_EDIT']) + '/' + _getStateHash(DATA.INFO, ['DATE_START', 'DATE_FINISH', 'TIME_LEAKS']);
		if (newState == this.MAIN_ROW_STATE)
			return null;

		this.MAIN_ROW_STATE = newState;

		this.MAIN_ROW.className = 'tm-popup-timeman';
		BX.cleanNode(this.MAIN_ROW_CELL_BTN);

		row_pause = this.MAIN_ROW.firstChild;

		if (!!this.CLOCKWND)
		{
			this.CLOCKWND.Clear();
			this.CLOCKWND = null;
		}

	}

	if (this.PAUSE_TIMER)
	{
		BX.timer.stop(this.PAUSE_TIMER);
		this.PAUSE_TIMER = null;
	}


	if (DATA.STATE != 'PAUSED')
	{
		this.MAIN_ROW_CELL_TIMER.firstChild.className = 'ui-btn ui-btn-icon-pause tm-btn-pause';
		BX.hide(row_pause);
	}
	else
	{
		this.MAIN_ROW_CELL_TIMER.firstChild.className = 'ui-btn ui-btn-icon-start tm-btn-start';
		BX.show(row_pause);

		this.PAUSE_TIMER = BX.timer(row_pause.lastChild, {
			from: DATA.INFO.DATE_FINISH * 1000,
			accuracy: 1,
			dt: 1000 * DATA.INFO.TIME_LEAKS,
			display: 'worktime_notice_timeman'
		});
	}

	var btn = 'OPEN';
	if (DATA.STATE == 'EXPIRED' || DATA.STATE == 'OPENED' || DATA.STATE == 'PAUSED')
		btn = 'CLOSE';
	else if (DATA.STATE == 'CLOSED' && DATA.CAN_OPEN == 'REOPEN')
		btn = 'REOPEN';

	this.MAIN_BTN_HANDLER = this.ACTIONS[btn];

	if (DATA.STATE != 'CLOSED' || DATA.CAN_OPEN)
	{
		this.MAIN_BUTTON = this.MAIN_ROW_CELL_BTN.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-button-handler'},
			children: [
				BX.create('button', {
					props: {className: 'ui-btn ' + (DATA.STATE != 'CLOSED' ? 'ui-btn-danger ui-btn-icon-stop' : 'ui-btn-success ui-btn-icon-start') },
					text: BX.message('JS_CORE_TM_' + btn),
					events: {
						click: BX.proxy(this.MainButtonClick, this)
					},
				})
			]
		}));

		if(DATA.CAN_OPEN_AND_RELAUNCH)
		{
			this.MAIN_ROW_CELL_BTN.appendChild(BX.create('span', {
				props: {className: 'tm-webform-small-button tm-popup-relaunch-btn', id: 'tm_popup_relaunch_new'},
				events: {
					click: BX.proxy(function(){
						this.ACTIONS.REOPEN();
					}, this)
				},
				text: BX.message('JS_CORE_TM_UNPAUSE')
			}));
		}

		if (DATA.CAN_EDIT && DATA.STATE != 'PAUSED')
		{
			this.MAIN_ROW_CELL_BTN.appendChild(BX.create('SPAN', {
				props: {className: 'tm-popup-change-time-link'},
				events: {
					click: BX.proxy(this.ShowClock, this)
				},
				text: BX.message('JS_CORE_TM_CHTIME_' + DATA.STATE)
			}));
		}
	}
	else
	{
		this.MAIN_ROW_CELL_BTN.innerHTML = BX.message('JS_CORE_TM_CLOSED');
	}

	var className = 'tm-popup-timeman';

	if (DATA.STATE == 'PAUSED')
	{
		className += ' tm-popup-timeman-paused-mode'
	}

	// single button mode: day is expired or day is closed and cannot be reopened
	if (DATA.STATE == 'CLOSED' && DATA.CAN_OPEN != 'REOPEN')
	{
		className += ' tm-popup-timeman-button-mode tm-popup-timeman-change-time-mode';
	}
	else if (DATA.STATE == 'CLOSED' || DATA.STATE == 'EXPIRED')
	{
		className += ' tm-popup-timeman-button-mode';
	}
	// only unpause button mode: day is paused
	/*else if  (DATA.STATE == 'CLOSED' && DATA.CAN_OPEN == 'REOPEN')
	{
		className += ' tm-popup-timeman-time-mode';
	}*/
	else
	{
		className += ' tm-popup-timeman-buttons-mode' + (!DATA.TM_FREE && DATA.REPORT_REQ != 'A' ? '' : ' tm-popup-timeman-change-time-mode');
	}
	this.MAIN_ROW.className = className;

	return this.MAIN_ROW;
};

BX.CTimeManWindow.prototype.CreateMainPauseControl = function(DATA)
{
	var c = BX.create('button', {
		events: {
			click: BX.proxy(this.PauseButtonClick, this)
		},
		children: [
			BX.create('span', {
				props: { className: "text-pause" },
				text : BX.message('JS_CORE_TM_UNPAUSE')
			}),
			BX.create('span', {
				props: { className: "text-start" },
				text : BX.message('JS_CORE_TM_PAUSE')
			}),
		]
	});

	return c;
};

BX.CTimeManWindow.prototype.CreateReport = function()
{
	if (!this.REPORT)
		this.REPORT = new BX.CTimeManReport(this);

	return this.REPORT.Create();
};

BX.CTimeManWindow.prototype.CreateEvent = function(event, additional_props, fulldate)
{
	additional_props = additional_props || {};
	additional_props.className = 'tm-popup-event-name';
	fulldate = fulldate || false;

	if(!!event.DATE_FROM)
		event.DATE_FROM = event.DATE_FROM.split(' ')[0];
	if(!!event.DATE_TO)
		event.DATE_TO = event.DATE_TO.split(' ')[0];

	if(!!event.DATE_FROM && event.DATE_FROM == parseInt(event.DATE_FROM))
		event.DATE_FROM = BX.timeman.formatDate(event.DATE_FROM, false);
	if(!!event.DATE_TO && event.DATE_TO == parseInt(event.DATE_TO))
		event.DATE_TO = BX.timeman.formatDate(event.DATE_TO, false);

	if(!!event.TIME_FROM && event.TIME_FROM == parseInt(event.TIME_FROM))
		event.TIME_FROM = BX.timeman.formatTime(event.TIME_FROM, false);
	if(!!event.TIME_TO && event.TIME_TO == parseInt(event.TIME_TO))
		event.TIME_TO = BX.timeman.formatTime(event.TIME_TO, false);

	return BX.create('DIV', {
		props: {
			className: 'tm-popup-event',
			bx_event_id: event.ID
		},
		children: [
			BX.create('DIV', {
				props: {className: 'tm-popup-event-datetime'},
				html: '<span class="tm-popup-event-time-start' + (event.DATE_FROM_TODAY ? '' : ' tm-popup-event-time-passed') + '">'+(fulldate?event.DATE_FROM+' ':'')+event.TIME_FROM + '</span><span class="tm-popup-event-separator">-</span><span class="tm-popup-event-time-end' + (event.DATE_TO_TODAY ? '' : ' tm-popup-event-time-passed') + '">' +(fulldate?event.DATE_TO+' ':'')+ event.TIME_TO + '</span>'
			}),
			BX.create('DIV', {
				props: additional_props,
				events: event.ID ? {click: BX.proxy(this.showEvent, this)} : null,
				html: '<span class="tm-popup-event-text">' + BX.util.htmlspecialchars(event.NAME) + '</span>'
			})
		]
	});
};

BX.CTimeManWindow.prototype.EVENTWND = {};

BX.CTimeManWindow.prototype.showEvent = function(e)
{
	var event_id = BX.proxy_context.parentNode.bx_event_id;
	if (this.EVENTWND[event_id] && this.EVENTWND[event_id].node != BX.proxy_context)
	{
		this.EVENTWND[event_id].Clear();
		this.EVENTWND[event_id] = null;
	}

	if (!this.EVENTWND[event_id])
	{
		this.EVENTWND[event_id] = new BX.CTimeManEventPopup(this, {
			node: BX.proxy_context,
			bind: BX.proxy_context.BXPOPUPBIND || this.EVENTS.firstChild,// this.PARENT.CLOSE_DAY_FORM ? this.PARENT.CLOSE_DAY_FORM.listEvents : this.EVENTS.firstChild,
			id: event_id,
			angle_offset: BX.proxy_context.BXPOPUPANGLEOFFSET
		});
	}

	BX.onCustomEvent(this, 'onEventWndShow', [this.EVENTWND[event_id]]);

	this.EVENTWND[event_id].Show();

	return BX.PreventDefault(e);
};


BX.CTimeManWindow.prototype.CreateEventsForm = function(cb)
{
	var mt_format_css = BX.isAmPmMode() ? '_am_pm' : '';

	var handler = BX.delegate(function(e, bEnterPressed)
	{
		inp_Name.value = BX.util.trim(inp_Name.value);
		if (inp_Name.value && inp_Name.value!=BX.message('JS_CORE_TM_EVENTS_ADD'))
		{
			cb({
				from: inp_TimeFrom.value,
				to: inp_TimeTo.value,
				name: inp_Name.value,
				absence: inp_Absence.checked ? 'Y' : 'N'
			});

			BX.timer.start(inp_TimeFrom.bxtimer);
			BX.timer.start(inp_TimeTo.bxtimer);

			if (!bEnterPressed)
			{
				BX.addClass(inp_Name.parentNode, 'tm-popup-event-form-disabled');
				inp_Name.value = BX.message('JS_CORE_TM_EVENTS_ADD');
			}
			else
			{
				inp_Name.value = '';
			}
		}

		return (e || window.event) ? BX.PreventDefault(e) : null;
	}, this),

	handler_name_focus = function()
	{
		BX.removeClass(this.parentNode, 'tm-popup-event-form-disabled');
		if (this.value == BX.message('JS_CORE_TM_EVENTS_ADD'))
			this.value = '';
	};

	var inp_TimeFrom = BX.create('INPUT', {
		props: {type: 'text', className: 'tm-popup-event-start-time-textbox' + mt_format_css}
	});

	inp_TimeFrom.onclick = BX.delegate(function()
	{
		var cb = BX.delegate(function(value) {
			this.CLOCK.closeWnd();

			var oldvalue_From = BX.timeman.unFormatTime(inp_TimeFrom.value),
				oldvalue_To = BX.timeman.unFormatTime(inp_TimeTo.value);

			var diff = 3600;
			if (oldvalue_From && oldvalue_To)
				diff = oldvalue_To - oldvalue_From;

			BX.timer.stop(inp_TimeFrom.bxtimer);
			BX.timer.stop(inp_TimeTo.bxtimer);

			inp_TimeFrom.value = value;

			inp_TimeTo.value = BX.timeman.formatTime(BX.timeman.unFormatTime(value) + diff);

			inp_TimeTo.focus();
			inp_TimeTo.onclick();
		}, this);

		if (!this.CLOCK)
		{
			this.CLOCK = new BX.CTimeManClock(this, {
				start_time: BX.timeman.unFormatTime(inp_TimeFrom.value),
				node: inp_TimeFrom,
				callback: cb
			});
		}
		else
		{
			this.CLOCK.setNode(inp_TimeFrom);
			this.CLOCK.setTime(BX.timeman.unFormatTime(inp_TimeFrom.value));
			this.CLOCK.setCallback(cb);
		}

		inp_TimeFrom.blur();
		this.CLOCK.Show();
	}, this);

	inp_TimeFrom.bxtimer = BX.timer(inp_TimeFrom, {dt: 3600000, accuracy: 3600});

	var inp_TimeTo = BX.create('INPUT', {
		props: {type: 'text', className: 'tm-popup-event-end-time-textbox' + mt_format_css}
	});

	inp_TimeTo.onclick = BX.delegate(function()
	{
		var cb = BX.delegate(function(value) {
			this.CLOCK.closeWnd();
			inp_TimeTo.value = value;

			BX.timer.stop(inp_TimeFrom.bxtimer);
			BX.timer.stop(inp_TimeTo.bxtimer);

			inp_Name.focus();
			handler_name_focus.apply(inp_Name);
		}, this);

		if (!this.CLOCK)
		{
			this.CLOCK = new BX.CTimeManClock(this, {
				start_time: BX.timeman.unFormatTime(inp_TimeTo.value),
				node: inp_TimeTo,
				callback: cb
			});
		}
		else
		{
			this.CLOCK.setNode(inp_TimeTo);
			this.CLOCK.setTime(BX.timeman.unFormatTime(inp_TimeTo.value));
			this.CLOCK.setCallback(cb);
		}

		inp_TimeTo.blur();
		this.CLOCK.Show();
	}, this);

	inp_TimeTo.bxtimer = BX.timer(inp_TimeTo, {dt: 7200000, accuracy: 3600});

	var inp_Name = BX.create('INPUT', {
		props: {type: 'text', className: 'tm-popup-event-form-textbox' + mt_format_css, value: BX.message('JS_CORE_TM_EVENTS_ADD')},
		events: {
			keypress: function(e) {
				return (e.keyCode == 13) ? handler(e, true) : true;
			},
			blur: function() {
				if (this.value == '')
				{
					BX.addClass(this.parentNode, 'tm-popup-event-form-disabled');
					this.value = BX.message('JS_CORE_TM_EVENTS_ADD');
				}
			},
			focus: handler_name_focus
		}
	});

	var id = 'bx_tm_absence_' + Math.random();
	var inp_Absence = BX.create('INPUT', {
		props: {type: 'checkbox', className: 'checkbox', id: id}
	});

	this.EVENTS_FORM = BX.create('DIV', {
		props: {className: 'tm-popup-event-form tm-popup-event-form-disabled'},
		children: [
			inp_TimeFrom, inp_TimeTo, inp_Name,
			BX.create('SPAN', {
				props: {className: 'tm-popup-event-form-submit'},
				events: {
					click: handler
				}
			}),
			BX.create('DIV', {
				props: {className:'tm-popup-event-form-options'},
				children: [
					inp_Absence,
					BX.create('LABEL', {props: {htmlFor: id}, text: BX.message('JS_CORE_TM_EVENT_ABSENT')})
				]
			})
		]
	});

	return this.EVENTS_FORM;
}

BX.CTimeManWindow.prototype.CreateTaskCallback = function(t)
{
	this.PARENT.taskEntryAdd({
		name: t.name
	});

	this.TASKS_LIST.appendChild(BX.create('LI', {
		props: {className: 'tm-popup-task'},
		text: t.name
	}));
}

BX.CTimeManWindow.prototype.CreateTasks = function(DATA)
{
	if (!DATA.TASKS_ENABLED || this.PARENT.TASK_CHANGES.add.length > 0 || this.PARENT.TASK_CHANGES.remove.length > 0)
		return null;

	if (DATA.FULL === false && !!this.TASKS)
		return this.TASKS;

	if (null == this.TASKS)
	{
		this.TASKS = BX.create('DIV');

		this.TASKS.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-section tm-popup-section-tasks'},
			children: [
				BX.create('SPAN', {props: {className: 'tm-popup-section-left'}}),
				BX.create('SPAN', {
					props: {className: 'tm-popup-section-text'},
					text: BX.message('JS_CORE_TM_TASKS')
				}),
				(this.TASKS_LINK = BX.create('span', {
					props: {className: 'tm-popup-section-right-link'},
					events: {click: BX.proxy(this.ShowTasks, this)},
					text: BX.message('JS_CORE_TM_TASKS_CHOOSE')
				})),
				BX.create('SPAN', {props: {className: 'tm-popup-section-right'}})
			]
		}));

		this.TASKS.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-tasks'},
			children: [
			(this.TASKS_LIST = BX.create('OL', {
				props: {
					className: 'tm-popup-task-list'
				}
			})),
			this.CreateTasksForm(BX.proxy(this.CreateTaskCallback, this))
		]}));

		//this.TASKS_STATE = DATA.STATE + ':' + DATA.TASKS.length;
	}
	else
	{
		// var newState = DATA.STATE + ':' + DATA.TASKS.length;

		// if (newState == this.TASKS_STATE)
			// return;

		// this.TASKS_STATE = newState;

		BX.cleanNode(this.TASKS_LIST);
	}

	if (/*DATA.STATE == 'OPENED' && */DATA.TASKS && DATA.TASKS.length > 0)
	{
		var LAST_TASK = null;
		BX.removeClass(this.TASKS, 'tm-popup-tasks-empty');
		for (var i=0,l=DATA.TASKS.length; i<l; i++)
		{
			var q = this.TASKS_LIST.appendChild(BX.create('LI', {
				props: {
					className: 'tm-popup-task tm-popup-task-status-' + BX.timeman.TASK_SUFFIXES[DATA.TASKS[i].STATUS],
					bx_task_id: DATA.TASKS[i].ID
				},
				children:
				[
					BX.create('SPAN', {props: {className: 'tm-popup-task-icon'}}),
					BX.create('SPAN', {
						props: {
							className: 'tm-popup-task-name',
							BXPOPUPBIND: this.TASKS.firstChild
						},
						text: DATA.TASKS[i].TITLE,
						events: {click: BX.proxy(this.showTask, this)}
					}),
					BX.create('SPAN', {
						props: {className: 'tm-popup-task-delete'},
						events: {click: BX.proxy(this.removeTask, this)}
					})
				]
			}));

			if (DATA.TASK_LAST_ID && DATA.TASKS[i].ID == DATA.TASK_LAST_ID)
			{
				LAST_TASK = q;
			}
		}

		if (LAST_TASK)
		{
			setTimeout(BX.delegate(function()
			{
				if (LAST_TASK.offsetTop < this.TASKS_LIST.scrollTop || LAST_TASK.offsetTop + LAST_TASK.offsetHeight > this.TASKS_LIST.scrollTop + this.TASKS_LIST.offsetHeight)
				{
					this.TASKS_LIST.scrollTop = LAST_TASK.offsetTop - parseInt(this.TASKS_LIST.offsetHeight/2);
				}
			}, this), 10);
		}
	}
	else
	{
		BX.addClass(this.TASKS, 'tm-popup-tasks-empty');
	}

	/*
	if (DATA.STATE !== 'OPENED')
		BX.hide(this.TASKS);
	else
		BX.show(this.TASKS);
	*/

	return this.TASKS;
}

BX.CTimeManWindow.prototype.CreateTasksForm = function(cb)
{
	var handler = BX.delegate(function(e, bEnterPressed) {
		inp_Task.value = BX.util.trim(inp_Task.value);
		if (inp_Task.value && inp_Task.value!=BX.message('JS_CORE_TM_TASKS_ADD'))
		{
			cb({
				name: inp_Task.value
			});

			if (!bEnterPressed)
			{
				BX.addClass(inp_Task.parentNode, 'tm-popup-task-form-disabled')
				inp_Task.value = BX.message('JS_CORE_TM_TASKS_ADD');
			}
			else
			{
				inp_Task.value = '';
			}
		}

		return BX.PreventDefault(e);
	}, this);

	var inp_Task = BX.create('INPUT', {
		props: {type: 'text', className: 'tm-popup-task-form-textbox', value: BX.message('JS_CORE_TM_TASKS_ADD')},
		events: {
			keypress: function(e) {
				return (e.keyCode == 13) ? handler(e, true) : true;
			},
			blur: function() {
				if (this.value == '')
				{
					BX.addClass(this.parentNode, 'tm-popup-task-form-disabled');
					this.value = BX.message('JS_CORE_TM_TASKS_ADD');
				}
			},
			focus: function() {
				BX.removeClass(this.parentNode, 'tm-popup-task-form-disabled');
				if (this.value == BX.message('JS_CORE_TM_TASKS_ADD'))
					this.value = '';
			}
		}
	});

	BX.focusEvents(inp_Task);

	return BX.create('DIV', {
		props: {
			className: 'tm-popup-task-form tm-popup-task-form-disabled'
		},
		children: [
			inp_Task,
			BX.create('SPAN', {
				props: {className: 'tm-popup-task-form-submit'},
				events: {click: handler}
			})
		]
	});
}

BX.CTimeManWindow.prototype.ShowTasks = function()
{
	if (null == this.TASKSWND)
	{
		this.TASKSWND = new BX.CTimeManTasksSelector(this, {
			node: BX.proxy_context,
			onselect: BX.proxy(this.addTask, this)
		});
	}
	else
	{
		this.TASKSWND.setNode(BX.proxy_context);
	}

	this.TASKSWND.Show();
}

BX.CTimeManWindow.prototype.addTask = function(task_data)
{
	this.TASKS_LIST.appendChild(BX.create('LI', {
		props: {className: 'tm-popup-task'},
		text: task_data.name
	}));

	this.PARENT.taskPost({action: 'add', id: task_data.id});
}

BX.CTimeManWindow.prototype.removeTask = function(e)
{
	this.PARENT.taskPost({action: 'remove', id: BX.proxy_context.parentNode.bx_task_id});
	BX.cleanNode(BX.proxy_context.parentNode, true);

	return BX.PreventDefault(e);
}

BX.CTimeManWindow.prototype.showTask = function(e)
{
	var task_id = BX.proxy_context.parentNode.bx_task_id;

	var tasks = (this.data && this.data.INFO) ? this.data.INFO.TASKS : this.DATA.TASKS,
		arTasks = [];
	if (tasks.length > 0)
	{
		for(var i=0; i<tasks.length; i++)
			arTasks.push(tasks[i].ID);
		taskIFramePopup.tasksList = arTasks;
		taskIFramePopup.view(task_id);
	}

	return false;
}

BX.CTimeManWindow.prototype.ShowClock = function(error_string, start_time)
{
	if (!BX.type.isString(error_string))
		error_string = null;

	if (null == this.CLOCKWND)
	{
		this.CLOCKWND = new BX.CTimeManTimeSelector(this, {
			node: this.MAIN_BUTTON,
			error: error_string,
			start_time: start_time || (this.DATA.STATE == 'EXPIRED' && this.DATA.EXPIRED_DATE ? this.DATA.EXPIRED_DATE : null),
			free_mode: this.PARENT.FREE_MODE
		});
	}
	else
	{
		//this.CLOCKWND.CreateContent();
		this.CLOCKWND.setError(error_string);
		this.CLOCKWND.setNode(this.MAIN_BUTTON);

		if (this.DATA.STATE == 'EXPIRED' && this.DATA.EXPIRED_DATE)
			this.CLOCKWND.setTime(this.DATA.EXPIRED_DATE);
	}

	this.CLOCKWND.Show();
}

BX.CTimeManWindow.prototype.ShowEdit = function()
{
	if (null == this.EDITWND)
	{
		this.EDITWND = new BX.CTimeManEditPopup(this, {
			node: this.NOTICE,
			bind: this.NOTICE,
			entry: this.PARENT.DATA,
			free_mode: this.PARENT.FREE_MODE
		});
	}
	else
	{
		this.EDITWND.setNode(this.NOTICE);
		this.EDITWND.setData(this.PARENT.DATA);
	}

	this.EDITWND.Show();
}

BX.CTimeManWindow.prototype.PauseButtonClick = function(e)
{
	var action = this.PARENT.DATA.INFO.PAUSED ? this.ACTIONS.REOPEN : this.ACTIONS.PAUSE;
	return action(e);
}

BX.CTimeManWindow.prototype.MainButtonClick = function(e)
{
	this.PARENT.setTimestamp(this.TIMESTAMP);
	this.PARENT.setReport(this.ERROR_REPORT);

	if ((this.MAIN_BTN_HANDLER == this.ACTIONS.OPEN) && this.REPORT)
	{
		this.REPORT.Reset();
	}

	if ((this.MAIN_BTN_HANDLER == this.ACTIONS.CLOSE))
	{
		this.isMonitorEnabled().then(function(result) {
			if (!result)
			{
				return;
			}

			BX.desktopUtils.runningCheck(
				function()
				{
					var notification = BX.UI.Notification.Center.notify({
						content: BX.message('JS_CORE_TM_MONITOR_REPORT_NOTIFICATION'),
						autoHideDelay: 5000,
						actions: [
							{
								title: BX.message('JS_CORE_TM_MONITOR_REPORT_OPEN'),
								events:
									{
										click: function() {
											this.openMonitorReport();
											notification.close();
										}.bind(this),
									}
							},
						],
					});
				}.bind(this),
				function()
				{
					BX.UI.Notification.Center.notify({
						content: BX.message('JS_CORE_TM_MONITOR_REPORT_NOTIFICATION_DESKTOP_DISABLED'),
						autoHideDelay: 5000,
					});
				}.bind(this)
			);

		}.bind(this));
	}

	return this.MAIN_BTN_HANDLER(e);
}

BX.CTimeManWindow.prototype.clearTempData = function()
{
	this.TIMESTAMP = 0;
	this.ERROR_REPORT = '';
}

BX.CTimeManWindow.prototype.showReportField = function(error_string)
{
	this.ShowClock(error_string);
}

BX.CTimeManWindow.prototype.Align = function()
{
	if (!this.SHOW)
		return;

	if (this.bindOptions.mode != 'popup')
	{
		var wndSize = BX.GetWindowInnerSize();

		this.bindOptions.node = BX(this.bindOptions.node);
		if (this.bindOptions.node)
		{
			var pos = BX.pos(this.bindOptions.node),
				top = 0, left = 0;

			left = this.bindOptions.type[0] == 'right' || this.bindOptions.type[1] == 'right'
				? pos.right - 460
				: pos.left;

			top = this.bindOptions.type[0] == 'top' || this.bindOptions.type[1] == 'top'
				? pos.top
				: pos.bottom;

			if (this.bindOptions.offsetLeft)
				left += this.bindOptions.offsetLeft;
			if (this.bindOptions.offsetTop)
				top += this.bindOptions.offsetTop;
		}

		if (left <= 0)
			left = pos.left;

		this.DIV.style.left = left + 'px';
		this.DIV.style.top = top + 'px';
	}
}

BX.CTimeManWindow.prototype.isShown = function()
{
	if(this.bindOptions.mode == 'popup')
	{
		return !!this.POPUP && this.POPUP.isShown()
	}
	else
	{
		return !!this.SHOW;
	}
}

BX.CTimeManWindow.prototype.Show = function()
{
	if (!this.PARENT.DATA || !this.PARENT.DATA.STATE)
		return false;

	this.CREATE = true;

	if (null == this.DIV && null == this.POPUP)
		this.Create(this.PARENT.DATA);

	this.DATA = this.PARENT.DATA;

	if (this.bindOptions.mode == 'popup')
	{
		if (this.POPUP.isShown())
		{
			this.Hide();
			return false;
		}
		else
		{
			this.POPUP.show();
		}
	}
	else
	{
		if (this.DIV.style.display == 'block')
		{
			this.Hide()
			return false;
		}
		else
		{
			this.SHOW = true;
			this.Align();
			this.DIV.style.display = 'block';

			setTimeout(BX.proxy(this.onAfterShow, this), 10);

		}
	}

	BX.onCustomEvent('onTimeManWindowOpen', [this]);
	return true;
}

BX.CTimeManWindow.prototype.onAfterShow = function()
{
	BX.bind(document, 'click', BX.proxy(this.HideClick, this));
}

BX.CTimeManWindow.prototype.HideClick = function(e)
{
	if (e.button == 2) return true;
	this.Hide();
}

BX.CTimeManWindow.prototype.Hide = function()
{
	if (this.bindOptions.mode == 'popup')
	{
		this.POPUP.close();
	}
	else
	{
		BX.unbind(document, 'click', BX.proxy(this.HideClick, this));

		this.DIV.style.display = 'none';
		this.SHOW = false;
	}

	BX.onCustomEvent('onTimeManWindowClose', [this]);
}

/********************************************/

BX.CTimeManClock = function(parent, params)
{
	this.parent = parent;
	this.params = params;

	this.params.popup_buttons = this.params.popup_buttons || [
		new BX.PopupWindowButton({
			text : BX.message('JS_CORE_TM_EVENT_SET'),
			className : "popup-window-button-create",
			events : {click : BX.proxy(this.setValue, this)}
		})
	];

	this.isReady = false;

	var p = this.params.popup_config || {
		offsetLeft: -45,
		offsetTop: -135,
		autoHide: true,
		closeIcon: true,
		closeByEsc: true
	};

	p.lightShadow = true;

	this.WND = new BX.PopupWindow(
		this.params.popup_id || 'timeman_clock_popup',
		this.params.node,
		p
	);

	this.SHOW = false;
	BX.addCustomEvent(this.WND, "onPopupClose", BX.delegate(this.onPopupClose, this));

	this.obClocks = {};
	this.CLOCK_ID = this.params.clock_id || 'timeman_clock';
}

BX.CTimeManClock.prototype.Show = function()
{
	if (!this.isReady)
	{
		BX.timeman.showWait(this.parent.DIV);
		BX.addCustomEvent('onTMClockRegister', BX.proxy(this.onTMClockRegister, this));
		return BX.ajax.get(TMPoint, {action:'clock', start_time: this.params.start_time, clock_id: this.CLOCK_ID, sessid: BX.bitrix_sessid()}, BX.delegate(this.Ready, this));
	}

	this.WND.setButtons(this.params.popup_buttons);
	this.WND.show();

	this.SHOW = true;

	if (window['bxClock_' + this.obClocks[this.CLOCK_ID]])
	{
		setTimeout("window['bxClock_" + this.obClocks[this.CLOCK_ID] + "'].CalculateCoordinates()", 40);
	}

	return true;
}

BX.CTimeManClock.prototype.onTMClockRegister = function(obClocks)
{
	if (obClocks[this.CLOCK_ID])
	{
		this.obClocks[this.CLOCK_ID] = obClocks[this.CLOCK_ID];
		BX.removeCustomEvent('onTMClockRegister', BX.proxy(this.onTMClockRegister, this));
	}
}

BX.CTimeManClock.prototype.Ready = function(data)
{
	this.content = this.CreateContent(data);
	this.WND.setContent(this.content);
	if (window.BXTIMEMAN && window.BXTIMEMAN.WND && window.BXTIMEMAN.WND.onSelectDateLinkClick)
	{
		var dateLinks = this.content.querySelectorAll('[data-role="date-picker"]');
		for (var i = 0; i < dateLinks.length; i++)
		{
			BX.bind(dateLinks[i], 'click', BX.proxy(window.BXTIMEMAN.WND.onSelectDateLinkClick, this));
		}
	}

	this.isReady = true;
	BX.timeman.closeWait();

	setTimeout(BX.proxy(this.Show, this), 30);
}

BX.CTimeManClock.prototype.CreateContent = function(data)
{
	return BX.create('DIV', {
		events: {click: BX.PreventDefault},
		html:
			'<div class="bx-tm-popup-clock-wnd-title">' + BX.message('JS_CORE_CL') + '</div>'
			+ _createHR(true)
			+ '<div class="bx-tm-popup-clock">' + data + '</div>'
	});
}

BX.CTimeManClock.prototype.setValue = function(e)
{
	if (this.params.callback)
	{
		var input = BX.findChild(this.content, {tagName: 'INPUT'}, true);
		this.params.callback.apply(this.params.node, [input.value]);
	}

	return BX.PreventDefault(e);
}

BX.CTimeManClock.prototype.closeWnd = function(e)
{
	this.WND.close();
	return (e || window.event) ? BX.PreventDefault(e) : true;
}

BX.CTimeManClock.prototype.setNode = function(node)
{
	this.WND.setBindElement(node);
}

BX.CTimeManClock.prototype.setTime = function(timestamp)
{
	this.params.start_time = timestamp;
	if (window['bxClock_' + this.obClocks[this.CLOCK_ID]])
	{
		window['bxClock_' +  this.obClocks[this.CLOCK_ID]].SetTime(parseInt(timestamp/3600), parseInt((timestamp%3600)/60));
	}
}

BX.CTimeManClock.prototype.setCallback = function(cb)
{
	this.params.callback = cb;
}

BX.CTimeManClock.prototype.onPopupClose = function()
{
	this.SHOW = false;
}

/*********************************************************************/

BX.CTimeManTimeSelector = function(parent, params)
{
	params = params || {};

	params.popup_id = 'timeman_time_selector_popup' + Math.random();
	params.popup_config = {
		offsetLeft: -50,
		offsetTop: -30,
		autoHide: true,
		closeIcon: true,
		closeByEsc: true
	};

	this.free_mode = !!params.free_mode;

	params.popup_buttons = [
		new BX.PopupWindowButton({
			text : parent.MAIN_BUTTON.textContent || parent.MAIN_BUTTON.innerText,
			className : parent.DATA.STATE == "CLOSED" ? "popup-window-button-accept" : "popup-window-button-decline",
			events : {click : BX.proxy(this.setValue, this)}
		}),
		new BX.PopupWindowButtonLink({
			text : BX.message('JS_CORE_TM_B_CLOSE'),
			className : "popup-window-button-link-cancel",
			events : {click : BX.proxy(this.closeWnd, this)}
		})
	];

	BX.CTimeManTimeSelector.superclass.constructor.apply(this, [parent, params]);

	this.CLOCK_ID = 'timeman_report_clock';
}
BX.extend(BX.CTimeManTimeSelector, BX.CTimeManClock);

BX.CTimeManWindow.prototype.onSelectDateLinkClick = function (event)
{
	var defaultDate = new Date();
	if (this.parent && this.parent.DATA && this.parent.DATA.INFO && this.parent.DATA.INFO.DATE_START
		&& this.parent.DATA.INFO.CURRENT_STATUS && this.parent.DATA.INFO.CURRENT_STATUS !== 'CLOSED')
	{
		if (this.parent.DATA.INFO.RECOMMENDED_CLOSE_TIMESTAMP && this.parent.DATA.INFO.RECOMMENDED_CLOSE_TIMESTAMP > 0)
		{
			defaultDate = new Date(this.parent.DATA.INFO.RECOMMENDED_CLOSE_TIMESTAMP * 1000);
		}
		else
		{
			defaultDate = new Date(this.parent.DATA.INFO.DATE_START * 1000);
		}
	}
	var defaultDateValue = BX.date.format(
		BX.date.convertBitrixFormat(BX.message("FORMAT_DATE")),
		defaultDate
	);
	var title = BX.create('INPUT', {
		props: {
			type: 'text',
			className: 'bx-tm-popup-clock-wnd-custom-date-picker',
			value: defaultDateValue
		},
		events: {
			click: function (event)
			{
				BX.calendar({node: event.currentTarget, field: event.currentTarget, bTime: false});
			},
			change: BX.delegate(function (event)
				{
					if (window.BXTIMEMAN && window.BXTIMEMAN.WND)
					{
						if (event.currentTarget.dataset.type === 'start')
						{
							window.BXTIMEMAN.WND.startUserDate = event.currentTarget.value
						}
						else if (event.currentTarget.dataset.type === 'end')
						{
							window.BXTIMEMAN.WND.endUserDate = event.currentTarget.value
						}
						else if (window.BXTIMEMAN.WND.CLOCKWND && event.currentTarget.dataset.type === 'single')
						{
							window.BXTIMEMAN.WND.CLOCKWND.customUserDate = event.currentTarget.value
						}
					}
				}, this
			)
		}
	});
	if (window.BXTIMEMAN && window.BXTIMEMAN.WND)
	{
		if (event.currentTarget.dataset.type === 'start')
		{
			window.BXTIMEMAN.WND.startUserDate = event.currentTarget.value;
			this.bChanged = true;
			if (this.SetSaveButton)
			{
				this.SetSaveButton({className: "popup-window-button-create"});
			}
		}
		else if (event.currentTarget.dataset.type === 'end')
		{
			window.BXTIMEMAN.WND.endUserDate = event.currentTarget.value;
			this.bChanged = true;
			if (this.SetSaveButton)
			{
				this.SetSaveButton({className: "popup-window-button-create"});
			}
		}
		else if (window.BXTIMEMAN.WND.CLOCKWND && event.currentTarget.dataset.type === 'single')
		{
			window.BXTIMEMAN.WND.CLOCKWND.customUserDate = event.currentTarget.value
		}
	}
	title.dataset.role = event.currentTarget.dataset.role;
	title.dataset.type = event.currentTarget.dataset.type;

	event.currentTarget.parentNode.appendChild(title);
	title.style.width = title.value.length.toString() + 'px!important';
	event.currentTarget.classList.add('timeman-hide');
	BX.calendar({node: title, field: title, bTime: false});
};
BX.CTimeManTimeSelector.prototype.buildCustomDatePicker = function()
{
	return (BX.create('SPAN', {
		text: BX.message('JS_CORE_TM_WD_CLOCK_SET_CUSTOM_DATE'),
		props: {
			className: 'bx-tm-popup-clock-wnd-custom-date-link'
		},
		dataset: {role: 'date-picker', type: 'single'}
	}));
};
BX.CTimeManTimeSelector.prototype.CreateContent = function(data)
{
	if (!this.content)
	{
		var table = BX.create('TABLE'),
			row = (table.appendChild(BX.create('TBODY'))).insertRow(-1);

		var cell = row.insertCell(-1);
		cell.innerHTML = '<div class="bx-tm-popup-clock-wnd-clock">' + data + '</div>';

		var contentChildren = [table];

		if (!this.free_mode)
		{
			cell = row.insertCell(-1);
			cell.appendChild(BX.create('DIV', {
				props: {className: 'bx-tm-popup-clock-wnd-report'},
				children: [
					(this.content_subtitle = BX.create('DIV', {
						props: {
							className: 'bx-tm-popup-clock-wnd-subtitle'
						},
						html: BX.message('JS_CORE_TM_CHTIME_CAUSE')
					})),
					(this.REPORT = BX.create('TEXTAREA', {
						props: {
							className: 'bx-tm-popup-clock-wnd-reason'
						}
					})),
					(BX.create('DIV', {
						props: {
							className: 'bx-tm-popup-clock-wnd-custom-date-block'
						},
						children: [this.buildCustomDatePicker.bind(this)()]
					}))
				]
			}));
		}
		else
		{
			table.setAttribute('align', 'center');
			contentChildren.push(
				(BX.create('DIV', {
					props: {
						className: 'bx-tm-popup-clock-wnd-custom-date-link-wrapper'
					},
					children: [this.buildCustomDatePicker.bind(this)()]
				}))
			);
		}
		this.content = (BX.create('DIV', {
			props: {className: 'bx-tm-popup-clock-wnd bx-tm-popup-time-selector-wnd bx-tm-popup-date-selector-wnd'},
			children: [
				(this.content_title = BX.create('DIV', {
					props: {className: 'bx-tm-popup-clock-wnd-title bx-tm-popup-clock-wnd-title-sm'},
					html: BX.message('JS_CORE_TM_CHTIME_' + this.parent.DATA.STATE)
				})),
				_createHR(),
				BX.create('DIV', {
					props: {className: this.free_mode ? 'bx-tm-popup-clock-free-mode' : ''},
					children: contentChildren
				})
			]
		}));
	}
	else
	{
		this.content_title.innerHTML = BX.message('JS_CORE_TM_CHTIME_' + this.parent.DATA.STATE);
		this.content_subtitle.innerHTML = this.parent.DATA.STATE != 'EXPIRED' ? BX.message('JS_CORE_TM_CHTIME_CAUSE') : '&nbsp;';
	}

	//this.setError(this.params.error);
	return this.content;
}

BX.CTimeManTimeSelector.prototype.setValue = function(e)
{
	var r = this.REPORT ? BX.util.trim(this.REPORT.value) : '';

	if (this.free_mode || r.length > 0)
	{
		var input = BX.findChild(this.content, {tagName: 'INPUT'}, true);

		this.parent.TIMESTAMP = BX.timeman.unFormatTime(input.value);
		this.parent.ERROR_REPORT = this.free_mode ? '' : r;
		this.parent.MainButtonClick(e);

		this.SHOW = false;
		//this.REPORT.value = '';
		//this.WND.close();
	}
	else
	{
		this.REPORT.className = 'bx-tm-popup-clock-wnd-report-error';
		this.REPORT.focus();
		this.REPORT.onkeypress = function() {this.className = '';this.onkeypress = null;};
	}
}

BX.CTimeManTimeSelector.prototype.setError = function(error)
{
	if (error)
	{
		if (confirm(error))
		{
			this.setValue();
			this.WND.close();
		}
	}
}

BX.CTimeManTimeSelector.prototype.setNode = function(node)
{
	BX.CTimeManTimeSelector.superclass.setNode.apply(this, arguments);
	this.params.popup_buttons[0].setName(node.textContent || node.innerText);
}

BX.CTimeManTimeSelector.prototype.Clear = function()
{
	// if (this.REPORT)
		// this.REPORT.value = '';

	// var now = new Date();
	// window.bxClock_timeman_report_clock.SetTime(now.getHours(), now.getMinutes());

	this.closeWnd();
}

/************************************************************************/

BX.CTimeManEditPopup = function(parent, params)
{
	params = params || {};

	this.mode = params.mode = params.mode || 'edit';
	this.free_mode = !!params.free_mode;

	params.popup_id = 'timeman_edit_popup_' + (Math.random() * 100000);
	params.popup_config = {
		offsetLeft: -50,
		offsetTop: -30,
		autoHide: true,
		closeIcon: true,
		closeByEsc: true
	};

	this.bChanged = false;
	params.popup_buttons = [
		(this.SAVEBUTTON = new BX.PopupWindowButton({
			text : BX.message('JS_CORE_TM_B_SAVE'),
			className : "popup-window-button",
			events : {click : BX.proxy(this.setValue, this)}
		})),
		new BX.PopupWindowButtonLink({
			text : BX.message('JS_CORE_TM_B_CLOSE'),
			className : "popup-window-button-link-cancel",
			events : {click : BX.proxy(this.closeWnd, this)}
		})
	];

	BX.CTimeManEditPopup.superclass.constructor.apply(this, [parent, params]);

	this.checkEntry();

	this.CLOCK_ID = 'timeman_edit_from';
	this.CLOCK_ID_1 = 'timeman_edit_to';
	this.obClocks = {};

	this.arInputs = {};
	this.arPause = [];

	BX.addCustomEvent(this.parent.PARENT, 'onTimeManDataRecieved', BX.proxy(this.setData, this));
}
BX.extend(BX.CTimeManEditPopup, BX.CTimeManClock);

BX.CTimeManEditPopup.prototype.setData = function(data)
{
	this.params.entry = data;

	if (this.isReady)
	{
		this.CreateContent();
	}
}

BX.CTimeManEditPopup.prototype.checkEntry = function()
{
	this.params.entry.INFO.TIME_START = parseInt(this.params.entry.INFO.TIME_START);
	this.params.entry.INFO.TIME_FINISH = parseInt(this.params.entry.INFO.TIME_FINISH);
	this.params.entry.INFO.DURATION = parseInt(this.params.entry.INFO.DURATION);

	this.date_start = new Date(this.params.entry.INFO.DATE_START * 1000);
	this.date_finish = this.params.entry.INFO.DATE_FINISH
		? new Date(this.params.entry.INFO.DATE_FINISH * 1000)
		: new Date();

	this.timezone_diff = (this.date_start.getHours() - Math.floor(this.params.entry.INFO.TIME_START/3600))*3600000;

	this.today = (new Date((new Date).valueOf()-this.timezone_diff));

	this.bFinished = this.params.entry.STATE == 'CLOSED';
	this.bExpired = this.params.entry.STATE == 'EXPIRED';

	if (this.bExpired)
	{
		this.bChanged = true;
		this.params.entry.EXPIRED_DATE = parseInt(this.params.entry.EXPIRED_DATE);

		this.SetSaveButton({caption: BX.message('JS_CORE_TM_CLOSE'), className: 'popup-window-button-decline'});
	}
}

BX.CTimeManEditPopup.prototype.SetSaveButton = function(params)
{
	if (!params)
		params = {caption: BX.message('JS_CORE_TM_B_SAVE'), className: ''};

	if (params.className || params.className === '')
		this.SAVEBUTTON.setClassName('popup-window-button ' + params.className);
	if (params.caption)
		this.SAVEBUTTON.setName(params.caption);
}

BX.CTimeManEditPopup.prototype.Show = function()
{
	if (!this.isReady)
	{
		BX.addCustomEvent('onTMClockRegister', BX.proxy(this.onTMClockRegister, this));
		BX.timeman.showWait(this.parent.DIV);
		return BX.ajax.get(TMPoint, {
			action:'clock',
			clock_id: this.CLOCK_ID,
			clock_id_1: this.CLOCK_ID_1,
			start_time: this.params.entry.INFO.TIME_START,
			start_time_1: this.params.entry.INFO.TIME_FINISH || this.params.entry.EXPIRED_DATE || '',
			sessid: BX.bitrix_sessid()
		}, BX.delegate(this.Ready, this));
	}

	BX.CTimeManEditPopup.superclass.Show.apply(this, arguments);

	if (!this.bOnChangeSet)
		setTimeout(BX.proxy(this.SetClockOnChange, this), 20);

	return true;
}

BX.CTimeManEditPopup.prototype.onTMClockRegister = function(obClocks)
{
	if (obClocks[this.CLOCK_ID])
	{
		this.obClocks[this.CLOCK_ID] = obClocks[this.CLOCK_ID];
		this.obClocks[this.CLOCK_ID_1] = obClocks[this.CLOCK_ID_1];

		BX.removeCustomEvent('onTMClockRegister', BX.proxy(this.onTMClockRegister, this));
	}
}

BX.CTimeManEditPopup.prototype.SetClockOnChange = function()
{
	if (this.bOnChangeSet)
		return;

	var arInputs = BX.findChildren(this.CLOCKS_CONTAINER, {tagName: 'INPUT', property: {type: 'hidden'}}, true);
	for (var i=0; i<arInputs.length; i++)
	{
		this.arInputs[arInputs[i].name] = arInputs[i];
		this.arInputs[arInputs[i].name].BXORIGINALVALUE = ''
		this.arInputs[arInputs[i].name].onchange = BX.proxy(this._input_onchange, this);
	}

	this.bOnChangeSet = true;
}

BX.CTimeManEditPopup.prototype._input_onchange = function()
{
	var input = BX.proxy_context,
		v1 = this.arInputs.timeman_edit_from.value.split(':'),
		v2 = this.arInputs.timeman_edit_to.value.split(':');

	if (input.BXORIGINALVALUE === '')
	{
		input.BXORIGINALVALUE = (this.bExpired && input.name == 'timeman_edit_to') ? 0 : input.value;
	}
	else if (input.value != input.BXORIGINALVALUE)
	{
		if (input.name == 'timeman_edit_to' && !this.bFinished && !this.bExpired)
		{
			this.SetSaveButton({className: "popup-window-button-decline", caption: BX.message('JS_CORE_TM_CLOSE')});
		}
		else if (this.SAVEBUTTON.text != BX.message('JS_CORE_TM_CLOSE'))
		{
			this.SetSaveButton({className: "popup-window-button-create"})
		}

		this.bChanged = true;
	}

	v1[0] = parseInt(v1[0], 10);
	v2[0] = parseInt(v2[0], 10);

	if (BX.isAmPmMode() && v1[0] < 12 && /pm/i.test(v1[1]))
		v1[0] += 12;

	if (BX.isAmPmMode() && v2[0] < 12 && /pm/i.test(v2[1]))
		v2[0] += 12;

	v1[1] = parseInt(v1[1], 10);
	v2[1] = parseInt(v2[1], 10);
}

BX.CTimeManEditPopup.prototype.CreateContent = function(data)
{
	if (!this.content)
	{
		var arChildren = [
			(this.content_title = BX.create('DIV', {
				props: {className: 'bx-tm-popup-clock-wnd-title'},
				html: BX.message('JS_CORE_TM_CHTIME_DAY')
			})),
			_createHR(),
			BX.create('DIV', {
				props: {className: 'bx-tm-popup-edit-clock-wnd-clock'},
				html: '<span class="bx-tm-clock-caption">'+BX.message('JS_CORE_TM_ARR')+'</span><span class="bx-tm-clock-caption">'+BX.message('JS_CORE_TM_DEP')+'</span>'
			}),
			(this.CLOCKS_CONTAINER = BX.create('DIV', {
				props: {className: 'bx-tm-popup-edit-clock-wnd-clock'},
				html: data
			}))
		];

		if (this.mode == 'edit' && !this.free_mode)
		{
			arChildren.push(_createHR());
			arChildren.push(BX.create('DIV', {
				props: {className: 'bx-tm-popup-clock-wnd-report'},
				children: [
					BX.create('DIV', {
						props: {
							className: 'bx-tm-popup-clock-wnd-subtitle'
						},
						html: BX.message('JS_CORE_TM_CHTIME_CAUSE')
					}),
					(this.REPORT = BX.create('TEXTAREA'))
				]
			}));
		}
		arChildren.push(BX.create('DIV', {style: {height: '1px', clear: 'both'}}));

		this.content = (BX.create('DIV', {
			props: {className: 'bx-tm-popup-clock-wnd bx-tm-popup-edit-clock-wnd bx-tm-popup-edit-time-date-wnd'},
			children: arChildren
		}));

		this.WNDSTATE = _getStateHash(this.params.entry, ['STATE', 'CAN_EDIT']) + '/' + _getStateHash(this.params.entry.INFO, ['DATE_START', 'DATE_FINISH', 'TIME_LEAKS']);
		if (window.BXTIMEMAN && window.BXTIMEMAN.WND && window.BXTIMEMAN.WND.onSelectDateLinkClick)
		{
			var dateLinks = this.content.querySelectorAll('[data-role="date-picker"]');
			for (var i = 0; i < dateLinks.length; i++)
			{
				BX.bind(dateLinks[i], 'click', BX.proxy(window.BXTIMEMAN.WND.onSelectDateLinkClick, this));
			}
		}
	}
	else
	{
		var newState = _getStateHash(this.params.entry, ['STATE', 'CAN_EDIT']) + '/' + _getStateHash(this.params.entry.INFO, ['DATE_START', 'DATE_FINISH', 'TIME_LEAKS']);

		if (newState == this.WNDSTATE)
			return true;

		this.WNDSTATE = newState;

		this.restoreButtons();

		// window.bxClock_timeman_edit_from.SetTime(this.date_start.getHours(), this.date_start.getMinutes());
		// window.bxClock_timeman_edit_to.SetTime(this.date_finish.getHours(), this.date_finish.getMinutes());

		if (this.CONT_PAUSEEDITOR)
		{
			if (this.HINT_PAUSEEDITOR)
			{
				this.HINT_PAUSEEDITOR.Destroy();
				this.HINT_PAUSEEDITOR = null;
			}

			this.CONT_PAUSEEDITOR.parentNode.removeChild(this.CONT_PAUSEEDITOR);
			this.CONT_PAUSEEDITOR = null;
		}

		if (this.CONT_DURATION)
		{
			this.CONT_DURATION.parentNode.removeChild(this.CONT_DURATION);
			this.CONT_DURATION = null;
		}
	}

	if (this.params.entry.CAN_EDIT || this.params.entry.INFO.CAN_EDIT == 'Y')
	{
		this.INPUT_TIME_LEAKS = BX.create('INPUT', {
			props: {
				type: 'text',
				className: 'bx-tm-report-edit',
				value: BX.timeman.formatTime(this.params.entry.INFO.TIME_LEAKS || 0, false, true)
			},
			style: {
				width: '40px'
			},
			events: {
				change: BX.proxy(this._input_onchange, this)
			}
		});
	}
	else
	{
		this.INPUT_TIME_LEAKS = BX.create('INPUT', {
			props: {
				disabled: true,
				type: 'text',
				className: 'bx-tm-report-edit',
				value: BX.timeman.formatTime(this.params.entry.INFO.TIME_LEAKS || 0, false, true)
			},
			style: {
				width: '40px'
			}
		})
	}

	this.CONT_PAUSEEDITOR = this.content.insertBefore(BX.create('DIV', {
		props: {className: 'bx-tm-popup-clock-wnd-report'},
		children: [
			BX.create('DIV', {
				props: {
					className: 'bx-tm-popup-clock-wnd-subtitle'
				},
				html: BX.message('JS_CORE_TM_WD_PAUSED_1')
			}),
			BX.create('DIV', {
				props: {className: 'bx-tm-edit-section'},
				children:
				[
					this.INPUT_TIME_LEAKS
				]
			})
		]
	}), this.CLOCKS_CONTAINER.nextSibling);

	this.INPUT_TIME_LEAKS.BXORIGINALVALUE = this.INPUT_TIME_LEAKS.value;
	if (!!this.PAUSE_TIMER)
		BX.timer.stop(this.PAUSE_TIMER);



	this.content.insertBefore(_createHR(), this.CLOCKS_CONTAINER.nextSibling);

	var d = this.params.entry.INFO.DURATION > 0
		? this.params.entry.INFO.DURATION
		: (!!this.params.entry.INFO.TIME_FINISH && !isNaN(this.params.entry.INFO.TIME_FINISH)
			? this.params.entry.INFO.TIME_FINISH - this.params.entry.INFO.TIME_START - this.params.entry.INFO.TIME_LEAKS
			: (
				this.bExpired
				? this.params.entry.EXPIRED_DATE - this.params.entry.INFO.TIME_START - this.params.entry.INFO.TIME_LEAKS
				: parseInt((new Date()).valueOf()/1000) - parseInt(this.params.entry.INFO.DATE_START) - parseInt(this.params.entry.INFO.TIME_LEAKS)
			)
		);

	this.CONT_DURATION = this.content.insertBefore(BX.create('DIV', {
		props: {className: 'bx-tm-field'},
		children: [
			BX.create('SPAN', {props: {className: 'bx-tm-report-caption'}, text: BX.message('JS_CORE_TM_WD_OPENED') + ' '}),
			BX.create('SPAN', {
				props: {className: 'bx-tm-report-field', bx_tm_tag: 'DURATION'},
				html: BX.timeman.formatWorkTime(d)
			})
		]
	}), this.CLOCKS_CONTAINER.nextSibling);

	return this.content;
}

BX.CTimeManEditPopup.prototype.setValue = function(e)
{
	if (!this.bChanged)
		return;

	var v, r = this.free_mode ? '' : (this.mode == 'edit' ? BX.util.trim(this.REPORT.value) : 'modified by admin');

	if (this.free_mode || r.length > 0)
	{
		var data = {};

		if (this.arInputs[this.CLOCK_ID].value != this.arInputs[this.CLOCK_ID].BXORIGINALVALUE)
		{
			data[this.CLOCK_ID] = BX.timeman.unFormatTime(this.arInputs[this.CLOCK_ID].value);
		}

		if (this.arInputs[this.CLOCK_ID_1].value != this.arInputs[this.CLOCK_ID_1].BXORIGINALVALUE)
		{
			data[this.CLOCK_ID_1] = BX.timeman.unFormatTime(this.arInputs[this.CLOCK_ID_1].value);
		}

		if (this.INPUT_TIME_LEAKS.value != this.INPUT_TIME_LEAKS.BXORIGINALVALUE)
		{
			data.TIME_LEAKS = BX.timeman.unFormatTime(this.INPUT_TIME_LEAKS.value);
		}

		data.report = r;
		if (window.BXTIMEMAN && window.BXTIMEMAN.WND && (window.BXTIMEMAN.WND.startUserDate !== undefined))
		{
			data.startUserDate = window.BXTIMEMAN.WND.startUserDate;
		}
		if (window.BXTIMEMAN && window.BXTIMEMAN.WND && (window.BXTIMEMAN.WND.endUserDate !== undefined))
		{
			data.endUserDate = window.BXTIMEMAN.WND.endUserDate;
		}
		this.parent.PARENT.Query('save', data, BX.proxy(this.parent.PARENT._Update, this.parent.PARENT));

		this.bChanged = false;
		this.restoreButtons();

		this.SHOW = false;

		if (this.REPORT)
			this.REPORT.value = '';

		this.arInputs[this.CLOCK_ID].value = this.arInputs[this.CLOCK_ID].BXORIGINALVALUE;
		this.arInputs[this.CLOCK_ID_1].value = this.arInputs[this.CLOCK_ID_1].BXORIGINALVALUE;
		this.arPause = [];

		this.WND.close();
	}
	else
	{
		this.REPORT.className = 'bx-tm-popup-clock-wnd-report-error';
		this.REPORT.focus();
		this.REPORT.onkeypress = function() {this.className = '';this.onkeypress = null;};
	}
}

BX.CTimeManEditPopup.prototype.Clear = function()
{
	window.bxClock_timeman_edit_from = null;
	window.bxClock_timeman_edit_to = null;

	if (this.WND)
	{
		this.WND.close();
		this.WND.destroy();
		this.WND = null;
	}

}

BX.CTimeManEditPopup.prototype.restoreButtons = function()
{
	this.SetSaveButton();
	this.checkEntry();
}

/************************************************************/

BX.CTimeManTasksSelector = function(parent, params)
{
	this.parent = parent;
	this.params = params;

	this.isReady = false;
	this.WND = BX.PopupWindowManager.create(
		'timeman_tasks_selector_' + parseInt(Math.random() * 10000), this.params.node,
		{
			autoHide: true,
			content: (this.content = BX.create('DIV')),
			buttons: [
				new BX.PopupWindowButtonLink({
					text : BX.message('JS_CORE_TM_B_CLOSE'),
					className : "popup-window-button-link-cancel",
					events : {click : function(e) {this.popupWindow.close();return BX.PreventDefault(e);}}
				})
			]
		}
	);
}

BX.CTimeManTasksSelector.prototype.Show = function()
{
	if (!this.isReady)
	{
		var suffix = parseInt(Math.random() * 10000);
		window['TIMEMAN_ADD_TASK_' + suffix] = BX.proxy(this.setValue, this);

		BX.timeman.showWait();
		return BX.ajax.get(TMPoint, {action:'tasks', suffix: suffix, sessid: BX.bitrix_sessid()}, BX.delegate(this.Ready, this));
	}

	return this.WND.show();
}

BX.CTimeManTasksSelector.prototype.Hide = function()
{
	this.WND.close();
}

BX.CTimeManTasksSelector.prototype.Ready = function(data)
{
	this.content.innerHTML = data;

	this.isReady = true;
	this.Show();
	BX.timeman.closeWait();
}

BX.CTimeManTasksSelector.prototype.setValue = function(task)
{
	this.params.onselect(task)
	this.WND.close();
}

BX.CTimeManTasksSelector.prototype.setNode = function(node)
{
	this.WND.setBindElement(node);
}
/***************************************************************************/

BX.CTimeManPopup = function(node, bind, popup_id, popup_additional)
{
	this.node = node;
	this.popup_id = popup_id;

	popup_additional = popup_additional || {};

	var ie7 = false;
	/*@cc_on
		 @if (@_jscript_version <= 5.7)
			ie7 = true;
		/*@end
	@*/

	this.popup = BX.PopupWindowManager.create(this.popup_id, bind, {
		closeIcon : {right: "12px", top: "10px"},
		offsetLeft : ie7 || (document.documentMode && document.documentMode <= 7) ? -347 : -340,
		autoHide: true,
		bindOptions : {
			forceBindPosition : true,
			forceTop : true
		},
		angle : {
			position: "right",
			offset : popup_additional.angle_offset || 27
		}
	});
}

BX.CTimeManPopup.prototype.Show = function()
{
	this.popup.setTitleBar({content: this.GetTitle()});
	this.popup.setContent(this.GetContent());
	this.popup.setButtons(this.GetButtons());

	var offset = 0;
	if (this.node && this.node.parentNode && this.node.parentNode.parentNode)
		offset = this.node.parentNode.offsetTop - this.node.parentNode.parentNode.scrollTop;

	this.popup.setOffset({offsetTop: this.params.offsetTop || (offset - 20)});
	//popup.setAngle({ offset : 27 });
	this.popup.adjustPosition();
	this.popup.show();
}

BX.CTimeManPopup.prototype.setNode = function(node)
{
	this.node = node;
	this.popup.setBindElement(node);
}

BX.CTimeManPopup.prototype.Clear = function()
{
	if (this.popup)
	{
		this.popup.close();
		this.popup.destroy();
		this.popup = null;
	}

	this.node = null;
}

BX.CTimeManPopup.prototype.GetTitle = function(){return '';}
BX.CTimeManPopup.prototype.GetContent = function(){return '';}
BX.CTimeManPopup.prototype.GetButtons = function(){return [];}

/**************************/
BX.CTimeManEventPopup = function(parent, params)
{
	this.parent = parent;
	this.params = params;

	BX.CTimeManEventPopup.superclass.constructor.apply(this, [this.params.node, this.params.bind, 'event_' + this.params.id, this.params]);

	BX.addCustomEvent(this.parent, 'onTaskWndShow', BX.delegate(this.onEventWndShow, this))
	BX.addCustomEvent(this.parent, 'onEventWndShow', BX.delegate(this.onEventWndShow, this))

	this.bSkipShow = false;
	this.isReady = false;
}
BX.extend(BX.CTimeManEventPopup, BX.CTimeManPopup);

BX.CTimeManEventPopup.prototype.onEventWndShow = function(wnd)
{
	if (wnd != this)
	{
		if (this.popup)
			this.popup.close();
		else
			this.bSkipShow = true;
	}
}

BX.CTimeManEventPopup.prototype.Show = function(data)
{
	data = data || this.data;

	if (data && data.error)
		return;

	if (!data)
	{
		BX.timeman.showWait();
		return BX.timeman_query('calendar_show', {id: this.params.id}, BX.proxy(this.Show, this));
	}
	else if (BX.type.isArray(data) && data.length == 0)
	{
		if (this.popup)
			this.popup.close();

		if (this.parent.PARENT)
			this.parent.PARENT.Update();

		return false;
	}

	this.data = data;

	if (this.bSkipShow)
		this.bSkipShow = true;
	else
		BX.CTimeManEventPopup.superclass.Show.apply(this);

	return true;
}

BX.CTimeManEventPopup.prototype.GetContent = function()
{
	var html = '<div class="tm-event-popup">'
	html += '<div class="tm-popup-title"><a class="tm-popup-title-link" href="' + this.data.URL + '">' + BX.util.htmlspecialchars(this.data.NAME) +'</a></div>';
	if (this.data.DESCRIPTION)
	{
		html += _createHR(true);
		html += '<div class="tm-event-popup-description">' + this.data.DESCRIPTION + '</div>';
	}

	html += _createHR(true);

	html += '<div class="tm-event-popup-time"><div class="tm-event-popup-time-interval">' + this.data.DATE_F + '</div>';
	if (this.data.DATE_F_TO)
		html += '<div class="tm-event-popup-time-hint">(' + this.data.DATE_F_TO + ')</div></div>'


	if (this.data.GUESTS)
	{
		html += _createHR(true);
		html += '<div class="tm-event-popup-participants">';

		if (this.data.HOST)
		{
			html += '<div class="tm-event-popup-participant"><div class="tm-event-popup-participant-status tm-event-popup-participant-status-accept"></div><div class="tm-event-popup-participant-name"><a class="tm-event-popup-participant-link" href="' + this.data.HOST.url + '">' + BX.util.htmlspecialchars(this.data.HOST.name) + '</a><span class="tm-event-popup-participant-hint">' + BX.message('JS_CORE_HOST') + '</span></div></div>';
		}

		if (this.data.GUESTS.length > 0)
		{
			html += '<table cellspacing="0" class="tm-event-popup-participants-grid"><tbody><tr>';

			var d = Math.ceil(this.data.GUESTS.length/2),
				grids = ['',''];

			for (var i=0;i<this.data.GUESTS.length; i++)
			{
				var status = '';
				if (this.data.GUESTS[i].status == 'Y')
					status = 'tm-event-popup-participant-status-accept';
				else if (this.data.GUESTS[i].status == 'N')
					status = 'tm-event-popup-participant-status-decline';

				grids[i<d?0:1] += '<div class="tm-event-popup-participant"><div class="tm-event-popup-participant-status ' + status + '"></div><div class="tm-event-popup-participant-name"><a class="tm-event-popup-participant-link" href="' + this.data.GUESTS[i].url + '">' + BX.util.htmlspecialchars(this.data.GUESTS[i].name) + '</a></div></div>';
			}

			html += '<td class="tm-event-popup-participants-grid-left">' + grids[0] + '</td><td class="tm-event-popup-participants-grid-right">' + grids[1] + '</td>';

			html += '</tr></tbody></table>';

		}

		html += '</div>';
	}

	html += '</div>';

	return html;
}

BX.CTimeManEventPopup.prototype.Query = function(str)
{
	BX.ajax({
		method: 'GET',
		url: this.data.URL + '&' + str,
		processData: false,
		onsuccess: BX.proxy(this._Query, this)
	});
}

BX.CTimeManEventPopup.prototype._Query = function()
{
	this.data = null;this.Show();
}

BX.CTimeManEventPopup.prototype.GetButtons = function()
{
	var btns = [], q = BX.proxy(this.Query, this);

	if (this.data.STATUS === 'Q')
	{
		btns.push(new BX.PopupWindowButton({
			text : BX.message('JS_CORE_TM_CONFIRM'),
			className : "popup-window-button-create",
			events : {
				click: function() {q('CONFIRM=Y');}
			}
		}));
		btns.push(new BX.PopupWindowButton({
			text : BX.message('JS_CORE_TM_REJECT'),
			className : "popup-window-button-cancel",
			events : {
				click: function() {q('CONFIRM=N');}
			}
		}));
	}
	else
	{
		btns.push(new BX.PopupWindowButtonLink({
			text : BX.message('JS_CORE_TM_B_CLOSE'),
			className : "popup-window-button-link-cancel",
			events : {click : function(e) {this.popupWindow.close();return BX.PreventDefault(e);}}
		}));

	}

	return btns;
}


/*************************************************************/

BX.CTimeManCalendarSelector = function(parent, params)
{
	this.parent = parent;
	this.params = params;

	this.WND = BX.PopupWindowManager.create(
		'timeman_calendar_selector', this.params.node,
		{
			autoHide: true,
			content: (this.content = BX.create('DIV'))
		}
	);

	this.current_calendar = null;
	this.current_row = null;

	this.bRemember = false;
}

BX.CTimeManCalendarSelector.prototype.Show = function()
{
	this.content.appendChild(BX.create('B', {text: this.params.data.TEXT}));
	var q = this.content.appendChild(BX.create('DIV', {props: {className: 'bx-tm-calendars-list'}}));

	for (var i=0,l=this.params.data.CALENDARS.length; i<l; i++)
	{
		var c = this.params.data.CALENDARS[i];
		q.appendChild(BX.create('DIV', {
			props: {bx_calendar_id: c.ID},
			style: {backgroundColor: c.COLOR, cursor: 'pointer'},
			events: {
				click: BX.proxy(this.Click, this)
			},
			html: c.NAME
		}))
	}

	var id = 'tm_calendar_remember_' + Math.random();
	this.content.appendChild(BX.create('DIV', {
		children: [
			BX.create('INPUT', {
				props: {
					type: 'checkbox',
					id: id
				},
				events: {
					click: BX.delegate(function() {this.bRemember=BX.proxy_context.checked}, this)
				}
			}),
			BX.create('LABEL', {
				props: {htmlFor: id},
				text: BX.message('JS_CORE_TM_REM')
			})
		]
	}))

	this.WND.setButtons([
		new BX.PopupWindowButton({
			text : BX.message('JS_CORE_TM_B_ADD'),
			className : "popup-window-button-create",
			events : {click : BX.proxy(this.setValue, this)}
		}),
		new BX.PopupWindowButtonLink({
			text : BX.message('JS_CORE_TM_B_CLOSE'),
			className : "popup-window-button-link-cancel",
			events : {click : BX.proxy(this.closeWnd, this)}
		})
	]);

	this.WND.show();
}

BX.CTimeManCalendarSelector.prototype.Click = function(e)
{
	if (this.current_row)
		BX.removeClass(this.current_row, 'bx-tm-calendar-current');

	this.current_row = BX.proxy_context;
	this.current_calendar = this.current_row.bx_calendar_id;

	BX.addClass(this.current_row, 'bx-tm-calendar-current');

	return BX.PreventDefault(e);
}

BX.CTimeManCalendarSelector.prototype.closeWnd = function(e)
{
	this.WND.close();
	this.WND.destroy();
	this.parent.Update();
	return BX.PreventDefault(e);
}

BX.CTimeManCalendarSelector.prototype.setValue = function(e)
{
	if (this.current_calendar)
	{
		calendarLastParams.cal = this.current_calendar;

		if (this.bRemember)
			calendarLastParams.cal_set_default = 'Y'

		this.parent.calendarEntryAdd(calendarLastParams);
		return this.closeWnd(e);
	}

	return BX.PreventDefault(e);
}
/*************************************************************/
BX.CTimeManReport = function(parent)
{
	this.parent = parent;

	this.REPORT_CONTAINER = null;
	this.REPORT_BTN = null;
	this.REPORT = null;

	this.REPORT_TEXT = '';
	this.REPORT_SAVE_TIME = 0;
	this.REPORT_CLIENT_SAVE_TIME = 0;

	this.bChanged = false;
	this.bCanSave = false;

	this.ENTRY_ID = this.parent.PARENT.DATA.ID;

	BX.addCustomEvent(this.parent.PARENT, 'onTimeManDataRecieved', BX.delegate(function(data){
		if (data.FULL !== false)
		{

			this.ENTRY_ID = data.ID;
			if (typeof this.parent.DATA.REPORT != 'undefined')
			{
				this.REPORT_TEXT = this.REPORT.value = this.parent.DATA.REPORT;
				if (this.parent.DATA.REPORT_TS > 0)
					this.REPORT_SAVE_TIME = new Date(this.parent.DATA.REPORT_TS * 1000);
			}

			this.REPORT.disabled = (data.STATE == 'CLOSED' && data.REPORT_REQ != 'A');
		}
	}, this));

	this.save_timer = null;
}

BX.CTimeManReport.prototype.Create = function()
{
	if (this.REPORT_CONTAINER)
	{
		if (this.REPORT_CONTAINER.parentNode)
			this.REPORT_CONTAINER.parentNode.removeChild(this.REPORT_CONTAINER)

		this.Reset();

		return this.REPORT_CONTAINER;
	}

	this.REPORT = BX.create('TEXTAREA', {
		props: {
			className: 'tm-popup-report-textarea',
			placeholder: BX.message('JS_CORE_TM_REPORT_PH'),
			value: this.REPORT_TEXT || '',
			disabled: (this.parent.DATA.STATE == 'CLOSED' && this.parent.DATA.REPORT_REQ != 'A')
		},
		events: {
			blur: BX.delegate(this._reportBlur, this),
			keyup: BX.delegate(this._reportKeyPress, this),
			paste: BX.delegate(this._reportKeyPress, this)
		}
	});

	BX.focusEvents(this.REPORT);

	this.REPORT_CONTAINER = BX.create('DIV', {
		props: {className: 'tm-popup-report'},
		children: [
			BX.create('DIV', {
				props: {className: 'tm-popup-report-text'},
				children: [
					this.REPORT
				]
			}),
			BX.create('DIV', {
				props: {className: 'tm-popup-report-buttons'},
				children: [
					(this.REPORT_BTN = BX.create('SPAN', {
						props: {className: 'ui-btn ui-btn-success ui-btn-disabled'},
						events: {click: BX.proxy(this._btnClick, this)},
						html: BX.message('JS_CORE_TM_B_SAVE')
					}))
				]
			})
		]
	});

	BX.addCustomEvent(window, 'onTimeManReportChange', BX.delegate(function(report, ts) {
		this.SaveFinished({REPORT: report, REPORT_TS: ts});
	}, this));
	BX.addCustomEvent(window, 'onTimeManReportChangeText', BX.delegate(function(report) {
		this.REPORT.value = report;
	}, this));

	return this.REPORT_CONTAINER;
}

BX.CTimeManReport.prototype.setEditMode = function(f)
{
	this.bCanSave = !!f;

	if (this.bCanSave)
	{
		BX.addClass(this.REPORT_CONTAINER, 'tm-popup-report-editmode');
		BX.removeClass(this.REPORT_BTN, 'ui-btn-disabled');
		BX.adjust(this.REPORT_BTN, {props: {disabled: false}});
	}
	else
	{
		BX.removeClass(this.REPORT_CONTAINER, 'tm-popup-report-editmode');
		BX.addClass(this.REPORT_BTN, 'ui-btn-disabled');
		BX.adjust(this.REPORT_BTN, {props: {disabled: true}});
	}
};

BX.CTimeManReport.prototype._reportKeyPress = function(e)
{
	this.bChanged = true;
	this.setEditMode(true);
}

BX.CTimeManReport.prototype._reportBlur = function(e)
{
	BX.onGlobalCustomEvent('onTimeManReportChangeText', [this.REPORT.value], true);

	if (this.bChanged)
		this.Save();
}

BX.CTimeManReport.prototype._btnClick = function(e)
{
	if (this.bCanSave)
		this.Save();
}

BX.CTimeManReport.prototype.Save = function()
{
	if (this.bChanged && !!this.saveXhr)
	{
		if (this.save_timer)
			clearTimeout(this.save_timer);

		this.save_timer = setTimeout(BX.proxy(this.Save, this), 1000);
		return;
	}

	this.setEditMode(false);

	this.REPORT_BTN.innerHTML = BX.message('JS_CORE_TM_B_SAVING');

	this.REPORT_TEXT = this.REPORT.value;

	BX.timeman.showWait();
	this.saveXhr = BX.timeman_query('report', {
		entry_id: this.ENTRY_ID,
		report: this.REPORT_TEXT,
		report_ts: this.REPORT_SAVE_TIME ? parseInt(this.REPORT_SAVE_TIME.valueOf() / 1000) : 0
	}, BX.proxy(this.SaveFinished, this), this.bChanged);

	if (!this.save_timer)
		this.bChanged = false;
}

BX.CTimeManReport.prototype.SaveFinished = function(data)
{
	if (!data)
	{
		this.saveXhr = null;
		return;
	}

	if (!this.REPORT_SAVE_TIME)
	{
		this.REPORT_TEXT = this.REPORT.value = this.parent.DATA.REPORT = data.REPORT || '';
		this.setEditMode(false);
	}
	else
	{
		this.REPORT_TEXT = this.parent.DATA.REPORT = data.REPORT;

		if (!this.bChanged)
		{
			this.REPORT.value = this.REPORT_TEXT || '';
			this.setEditMode(false);
		}
		else
		{
			this.setEditMode(true);
		}
	}

	this.parent.DATA.REPORT_TS = data.REPORT_TS;
	this.REPORT_SAVE_TIME = new Date(data.REPORT_TS * 1000);
	this.REPORT_CLIENT_SAVE_TIME = new Date();
	this.REPORT_BTN.innerHTML = BX.message('JS_CORE_TM_B_SAVE');

	if (this.saveXhr)
		BX.onGlobalCustomEvent('onTimeManReportChange', [this.REPORT_TEXT, parseInt(this.REPORT_SAVE_TIME.valueOf() / 1000)], true);

	this.saveXhr = null;
}

BX.CTimeManReport.prototype.ForceReload = function()
{
	if (!this.bChanged)
	{
		this.Reset();
		setTimeout(BX.proxy(this.Save, this), 10);
	}
}

BX.CTimeManReport.prototype.Reset = function()
{
	this.REPORT_TEXT = this.REPORT.value = this.parent.DATA.REPORT;
	if (this.parent.DATA.REPORT_TS > 0)
		this.REPORT_SAVE_TIME = new Date(this.parent.DATA.REPORT_TS * 1000);
}

/*************************************************************/
BX.CTimeManTabControl = function(DIV)
{
	this.DIV = DIV;
	this.DIV.className = 'tm-tabs-box';
	this.HEAD = this.DIV.appendChild(BX.create('DIV', {props: {className: 'tm-tabs'}}));
	this.DIV.appendChild(_createHR(false, 'tm-tabs-hr'));
	this.TABS = this.DIV.appendChild(BX.create('DIV', {props: {className: 'tm-tabs-content'}}));

	this.arTabs = null;

	this.selectedTab = BX.localStorage.get('tm_tab');
}

BX.CTimeManTabControl.prototype.addTab = function(params)
{
	if (!this.arTabs)
	{
		params.first = true;
		this.arTabs = {};

		if(!this.selectedTab)
		{
			this.selectedTab = params.id;
		}
	}
	else
	{
		params.first = false;
	}

	this.arTabs[params.id] = {
		title: params.title,
		content: params.content,
		first: params.first
	};

	this.createTab(params.id);
}

BX.CTimeManTabControl.prototype.createTab = function(id)
{
	this.arTabs[id].tab = this.HEAD.appendChild(BX.create('SPAN', {
		props: {BXTABID: id, className: 'tm-tab' + (id==this.selectedTab ? ' tm-tab-selected' : '')},
		events: {click: BX.delegate(function(){
			this.selectTab(id);
		}, this)},
		html: this.arTabs[id].title
	}));

	this.arTabs[id].tab_content = this.TABS.appendChild(BX.create('DIV', {
		props: {className: 'tm-tab-content' + (id==this.selectedTab ? ' tm-tab-content-selected' : '')},
		children: BX.type.isArray(this.arTabs[id].content)
			? this.arTabs[id].content
			: (
				BX.type.isDomNode(this.arTabs[id].content)
					? [this.arTabs[id].content]
					: null
				)
	}));

	if (BX.type.isNotEmptyString(this.arTabs[id].content))
	{
		this.arTabs[id].tab_content.innerHTML = this.arTabs[id].content;
	}
}

BX.CTimeManTabControl.prototype.selectTab = function(id)
{
	BX.removeClass(this.arTabs[this.selectedTab].tab, 'tm-tab-selected');
	BX.removeClass(this.arTabs[this.selectedTab].tab_content, 'tm-tab-content-selected');
	this.selectedTab = id;
	BX.addClass(this.arTabs[this.selectedTab].tab, 'tm-tab-selected');
	BX.addClass(this.arTabs[this.selectedTab].tab_content, 'tm-tab-content-selected');

	if(!!BX.PopupMenu.currentItem)
	{
		BX.PopupMenu.currentItem.popupWindow.close();
	}

	this.saveTab();
}

BX.CTimeManTabControl.prototype.saveTab = function()
{
	BX.localStorage.set('tm_tab', this.selectedTab, 86400*30);
}

BX.CTimeManTabEditorControl = function(params)
{
	this.div = params.div||BX.create("DIV");
	this.tabs = {};
	this.mode = "view";
	this.isLHEinit = false;
	if(params.uselocalstorage && params.localstorage_key)
	{
		this.uselocalstorage = true;
		this.localstorage_key = params.localstorage_key;
		BX.addCustomEvent(window, 'onLocalStorageSet', BX.proxy(function(data) {
			if (data.key == this.localstorage_key && data.value)
			{
				for (i in this.tabs)
				{
					if (data.value[i])
						this.SetTabContent(i,data.value[i]);
				}
			}
		}, this));

	}
	else
		this.uselocalstorage = false;
	this.first_tab = false;
	this.parent = params.parent || false;
	this.current_tab_id = false;
	this.lhename = params.lhename||"obTimemanEditor";
	this.TABCONTROL = new BX.CTimeManTabControl(this.div);
	this.TABCONTROL.saveTab = BX.DoNothing;
	this.TABCONTROL.selectedTab = null;

	this.TABCONTROL._selectTab = this.TABCONTROL.selectTab;
	this.TABCONTROL.selectTab = BX.proxy(function(id)
	{
		if (!this.isLHEinit && this.mode == "edit")
			return false;

		this.TABCONTROL._selectTab(id);
	},this);
}
BX.CTimeManTabEditorControl.prototype.addTab = function(params)
{
	var tab = {};
	tab.PARAMS = params;
	tab.ID = tab.PARAMS.ID;
	tab.TITLE = tab.PARAMS.TITLE;
	tab.CONTENT = tab.PARAMS.CONTENT;
	if (this.first_tab == false)
	{
		this.current_tab_id = tab.ID;
		this.first_tab = true;
	}
	this.TABCONTROL.addTab({
		id: tab.PARAMS.ID,
		title: tab.PARAMS.TITLE,
		content: [
			tab.LHEDIV = BX.create('DIV', {
				attrs:{id: tab.ID+"_editor"},
				style:{
					border:"1px solid #D9D9D9",height:"200px",display:"none"
				}
			}),
			tab.VIEWDIV =  BX.create('DIV', {
				style:{border:"none",maxHeight:"200px",padding:"5px",overflow:"auto"},
				html:tab.PARAMS.CONTENT
			})
		]
	});
	tab.CONTROL = this.TABCONTROL.arTabs[tab.ID];
	this.tabs[tab.ID] = tab;
	BX.bind(this.TABCONTROL.arTabs[tab.ID].tab, "click",BX.proxy(function()
	{
		var prev_tab = this.current_tab_id;
		this.current_tab_id = tab.ID;
		if (this.mode == "view")
			return;
		else if((!this.isLHEinit && this.mode == "edit")
		||(prev_tab ==this.current_tab_id)
		)
		{
			this.current_tab_id = prev_tab;
			return;
		}

		this.tabs[prev_tab].CONTENT = this.editor.GetEditorContent();
		var pEditorCont = BX("bxlhe_frame_" + this.editor.id);
		this.tabs[tab.ID].LHEDIV.appendChild(pEditorCont);

		if (BX.browser.IsIE())
		{
			var _this = this;
			pEditorCont.style.visibility = 'hidden';
			setTimeout(function()
			{
				_this.editor.ReInit(_this.tabs[tab.ID].CONTENT);
				pEditorCont.style.visibility = 'visible';
				BX.bind(_this.editor.pEditorDocument, 'keyup', BX.proxy(_this.SaveToLocalStorage, _this));
			}, 100);
		}
		else
		{
			this.editor.ReInit(this.tabs[tab.ID].CONTENT);
			BX.bind(this.editor.pEditorDocument, 'keyup', BX.proxy(this.SaveToLocalStorage, this));
		}
	},this));
}

BX.CTimeManTabEditorControl.prototype.InitLHE = function()
{
	if (this.tabs == 0)
		return;

	var rand = Math.round(Math.random()*100000);

	var current_tab = this.tabs[this.current_tab_id];
	BX.ajax.get("/bitrix/tools/timeman.php?action=editor&obname="+this.lhename+"_"+rand+"&sessid=" + BX.bitrix_sessid(), function(data){
		current_tab.LHEDIV.innerHTML = data;
	});
	BX.addCustomEvent(window, 'LHE_OnInit', BX.proxy(function(data){
		if(data.id == this.lhename+"_"+rand)
		{
			if (!BX.CDialog)
			{
				BX.Runtime.loadExtension('window');
			}

			var content = this.tabs[this.current_tab_id].CONTENT||'';



			if(/^<i[^>]+data-placeholder/.test(content))
			{
				content = '';
			}

			this.editor = data;
			this.editor.SetContent(content);
			this.editor.SetEditorContent(content);
			BX.bind(this.editor.pEditorDocument, 'keyup', BX.proxy(this.SaveToLocalStorage,this));
			this.isLHEinit = true;

			this.TABCONTROL.selectTab(this.current_tab_id);
		}
	},this));
}

BX.CTimeManTabEditorControl.prototype.SwitchEdit = function()
{
	if (this.tabs.length == 0 || this.mode == "edit")
		return;
	for (i in this.tabs)
	{
		this.tabs[i].VIEWDIV.style.display = "none";
		this.tabs[i].LHEDIV.style.display = "block";
		this.tabs[i].LHEDIV.innerHTML = "";
	}
	this.mode = "edit";
	this.InitLHE();
}

BX.CTimeManTabEditorControl.prototype.SwitchView = function()
{
	if (this.tabs == 0 || this.mode == "view")
		return;
	for (i in this.tabs)
	{
		if (i ==  this.current_tab_id)
		{
			this.tabs[i].VIEWDIV.innerHTML = this.editor.GetEditorContent();
			this.tabs[i].CONTENT = this.editor.GetEditorContent();
		}
		else
			this.tabs[i].VIEWDIV.innerHTML = this.tabs[i].CONTENT;
		this.tabs[i].VIEWDIV.style.display = "block";
		this.tabs[i].LHEDIV.style.display = "none";

	}
	this.mode = "view";
}

BX.CTimeManTabEditorControl.prototype.GetTabContent = function(tabId)
{
	var tabId = tabId||false;
	var tabContent = "";
	if (this.tabs == 0 || tabId == false || !this.tabs[tabId])
		return;
	if (this.current_tab_id == tabId && this.mode == "edit")
		tabContent = this.editor.GetEditorContent();
	else
		tabContent = this.tabs[tabId].CONTENT;

	return tabContent;
}

BX.CTimeManTabEditorControl.prototype.SetTabContent = function(tabId,content)
{
	var tabId = tabId||false;
	var tabContent = "";
	if (this.tabs == 0 || tabId == false || !this.tabs[tabId])
		return false;
	if (this.current_tab_id == tabId && this.mode == "edit")
		this.editor.SetEditorContent(content);
	else
		this.tabs[i].CONTENT = content;
	BX.bind(this.editor.pEditorDocument, 'keyup', BX.proxy(this.SaveToLocalStorage,this));//condition inside
	return true;
}

BX.CTimeManTabEditorControl.prototype.SaveToLocalStorage = function()
{
	if (this.timerID)
		clearTimeout(this.timerID);
	if (this.uselocalstorage != true)
		return;
	var data = {};
	for (i in this.tabs)
		data[i] = this.GetTabContent(i);
	this.timerID = setTimeout(BX.proxy(function(){
		BX.localStorage.set(this.localstorage_key,data);
	},this), 1000);
}

BX.CTimeManUploadForm = function(params)
{
	this.id = "TimemanUpload"+((!params.id)?"0":params.id);
	this.report_id = params.id||0;
	this.user_id = params.user_id;//||BX.message('USER_ID');
	this.files = params.files_list||[];
	window[this.id] = this;
	this.DIV = ((params.div)
		?params.div
		:BX.create("DIV", {props: {className: 'ui-form-row'}})
	);
	this.mode = params.mode||"view";
}

BX.CTimeManUploadForm.prototype.UploadFile = function()
{
	var files = [];
	if (this.fileinput.files!=undefined)
	{
		if(this.fileinput.files.length > 0)
		{
			for(var i=0; i < this.fileinput.files.length; i++)
			{
				var n = this.fileinput.files[i].name||this.fileinput.files[i].fileName;
				if(!!n)
				{
					files.push({
						fileName: n
					});
				}
			}
		}
	} else {
		var filePath = this.fileinput.value;
		var fileTitle = filePath.replace(/.*\\(.*)/, "$1");
		fileTitle = fileTitle.replace(/.*\/(.*)/, "$1");
		files = [
			{ fileName : fileTitle}
		];
	}

	var uniqueID;
	do {
		uniqueID = Math.floor(Math.random() * 99999);
	} while(BX("iframe_" + uniqueID));

	var list = BX("webform-upload-"+this.report_id);
	var items = [];
	for (var i = 0; i < files.length; i++)
	{
		var li = BX.create("li", {
			props : { className : "uploading",  id : "file-" + files[i].fileName + "-" + uniqueID},
			children : [
				BX.create("a", {
					props : { href : "", target : "_blank", className : "upload-file-name"},
					text : files[i].fileName,
					events : { click : function(e) {
						BX.PreventDefault(e);
					}}
				}),
				BX.create("i", { }),
				BX.create("a", {
					props : { href : "", className : "delete-file"},
					events : { click : function(e) {
						BX.PreventDefault(e);
					}}
				})
			]
		});

		list.appendChild(li);
		items.push(li);
	}

	var iframeName = "iframe-" + uniqueID;
	var iframe = BX.create("iframe", {
		props : {name : iframeName, id : iframeName},
		style : {display : "none"}
	});
	document.body.appendChild(iframe);
	var originalParent = this.fileinput.parentNode;
	var form = BX.create("form", {
		props : {
			method : "post",
			action : "/bitrix/tools/timeman.php",
			enctype : "multipart/form-data",
			encoding : "multipart/form-data",
			target : iframeName
		},
		style : {display : "none"},
		children : [
			this.fileinput,
			BX.create("input", {
				props : {
					type : "hidden",
					name : "sessid",
					value : BX.bitrix_sessid()
				}
			}),
			BX.create("input", {
				props : {
					type : "hidden",
					name : "uniqueID",
					value : uniqueID
				}
			}),
			BX.create("input", {
				props : {
					type : "hidden",
					name : "mode",
					value : "upload"
				}
			}),
			BX.create("input", {
				props : {
					type : "hidden",
					name : "action",
					value : "upload_attachment"
				}
			}),
			BX.create("input", {
				props : {
					type : "hidden",
					name : "report_id",
					value : this.report_id
				}
			}),
			BX.create("input", {
				props : {
					type : "hidden",
					name : "user_id",
					value : this.user_id
				}
			}),
			BX.create("input", {
				props : {
					type : "hidden",
					name : "form_id",
					value : this.id
				}
			})
		]
	});
	document.body.appendChild(form);
	form.appendChild(this.fileinput);
	BX.submit(form, null, null, BX.delegate(function(){
		originalParent.appendChild(this.fileinput);
		BX.cleanNode(form, true);
	}, this));
}


BX.CTimeManUploadForm.prototype.DeleteFile = function(e)
{
	_this = BX.proxy_context;
	if (confirm(BX.message("JS_CORE_TM_CONFIRM_TO_DELETE"))) {
		if (!BX.hasClass(_this.parentNode, "saved"))
		{
			var data = {
				fileID : _this.nextSibling.value,
				sessid : BX.bitrix_sessid(),
				mode : "delete",
				action: "upload_attachment",
				report_id:this.report_id,
				user_id:this.user_id
			}
			var url = "/bitrix/tools/timeman.php";
			BX.ajax.post(url, data);
		}
		BX.remove(_this.parentNode);
		for(i=0;i<this.files.length;i++)
		{
			if (_this.nextSibling.value == this.files[i].fileID)
			{
				this.files.splice(i,1);
				break;
			}
		}
	}
	BX.onCustomEvent(this, 'OnUploadFormRefresh', []);
	BX.PreventDefault(e);
}

BX.CTimeManUploadForm.prototype.GetUploadForm = function()
{
	var filelist = BX.create("OL",{
						props:{className:"report-webform-field-upload-list"},
						attrs:{id:"webform-upload-"+this.report_id}
					});
	if (this.files && this.files.length>0)
	{
		var files =this.files;
		for(i=0;i<files.length;i++)
		{
			filelist.appendChild(
				BX.create("li", {
				props : {id : "file-" + files[i].name + "-" + files[i].uniqueID},
				children : [
					BX.create("a", {
						props : { href : "/bitrix/tools/timeman.php?action=get_attachment&fid="+files[i].fileID+"&report_id="+this.report_id+"&user_id="+this.user_id+"&sessid="+BX.bitrix_sessid(), target : "_blank", className : "upload-file-name"},
						text : files[i].name
					}),
					((this.mode == "edit")?BX.create("a", {
						props : {className : "delete-file"},
						events:{"click":BX.proxy(this.DeleteFile,this)}
						}):null),
					BX.create("INPUT",{
						attrs:{type:"hidden", name:"FILES[]", value:files[i].fileID}
					})
				]
			})
			);
		}
	}
	this.uploadform = BX.create("DIV",{
		props:{className:"task-attachments-row"},
		children:[
			BX.create("DIV",{
				props:{className:"ui-form-content"},
				children:[
					BX.create("DIV",{
						props:{className:"tm-popup-section-title"},
						children:[
							BX.create("DIV",{
								props:{className:"tm-popup-section-title-text"},
								html:BX.message("JS_CORE_TM_FILES")
							}),
							BX.create("DIV",{
								props:{className:"tm-popup-section-title-line"}
							})
						]
					}),
					filelist,
					((this.mode == "edit")
					?BX.create("DIV",{
						props:{className:"report-webform-field-upload"},
						children:[
							BX.create("SPAN",{
								props:{className:"tm-webform-small-button report-webform-button-upload"},
								children:[
									BX.message('JS_CORE_TM_UPLOAD_FILES')
								]
							}),
							(this.fileinput = BX.create("INPUT",{
								attrs:{type:"file",name:"report-attachments[]", size:"1", multiple:"multiple", id:"report-upload"}
							}))
						]
					})
					:null
					)
				]
			})
		]
	});

	if(this.mode == "edit")
		this.fileinput.onchange = BX.proxy(this.UploadFile,this);
	this.DIV.appendChild(this.uploadform);

	return this.DIV;
}

BX.CTimeManUploadForm.prototype.RefreshUpload = function(files,uniqueID)
{
	for(i = 0; i < files.length; i++)
	{
		var elem = BX("file-" + files[i].name + "-" + uniqueID);
		if (files[i].fileID)
		{
			BX.removeClass(elem, "uploading");
			BX.adjust(elem.firstChild, {props : {href : "/bitrix/tools/timeman.php?action=get_attachment&fid="+files[i].fileID+"&report_id="+this.report_id+"&user_id="+this.user_id+"&sessid="+BX.bitrix_sessid()}});
			BX.unbindAll(elem.firstChild);
			BX.unbindAll(elem.lastChild);
			BX.bind(elem.lastChild, "click", BX.proxy(this.DeleteFile,this));

			elem.appendChild(BX.create("input", {
				props : {
					type : "hidden",
					name : "FILES[]",
					value : files[i].fileID
				}
			}));
			files[i].uniqueID = uniqueID;
			this.files.push(files[i]);
		}
		else
		{
			BX.cleanNode(elem, true);
		}
	}
	if(BX("iframe-" + uniqueID))
		BX.cleanNode(BX("iframe-" + uniqueID), true);
	BX.onCustomEvent(this, 'OnUploadFormRefresh', []);
}

/********************************************************************************/
//weekly


BX.CTimeManReportFormWeekly = function(parent, params)
{
	this.parent = parent;
	this.params = params;
	this.files = new Array();
	this.post = false;
	this.node = this.params.node;
	this.mode = this.params.mode || 'edit';

	this.data = params.data;

	this.popup_id = 'timeman_weekly_report_popup_' + parseInt(Math.random() * 100000);

	this.bLoaded = !!this.data;
	this.bTimeEdited = false;

	this.params.offsetTop = 5;
	this.params.offsetLeft = -50;
	this.ACTIVE = "Y";

	this.ie7 = false;
	/*@cc_on
		 @if (@_jscript_version <= 5.7)
			this.ie7 = true;
		/*@end
	@*/
	this.table = BX.create("TABLE",{
			props:{className:"report-popup-main-table"},
			children:[
				BX.create("TBODY",{
					children:[
						BX.create("TR",{
							children:[
								this.prev = BX.create("TD",{
									props:{className:"report-popup-prev-slide-wrap"}
								}),
								this.popup_place = BX.create("TD",{
									attrs:{valign:"top"},
									style:{paddingTop:"20px"},
									props:{className:"report-popup-main-block-wrap"}
								}),
								this.next = BX.create("TD",{
									props:{className:"report-popup-next-slide-wrap"},
									children:[
										this.closeLink = BX.create("A",{
											attrs:{href:"javascript: void(0)"},
											events:{"click":BX.proxy(this.PopupClose,this)},
											props:{className:"report-popup-close"},
											children:[
												BX.create("SPAN",{
													props:{className:"report-popup-close"}
													})
											]
										})
									]

								})
							]

						})
					]

				})
			]
		}
	);
	this.overlay = BX.create("DIV",{
			props:{className:"report-fixed-overlay"},
			children:[
				(this.coreoverlay = BX.create("DIV",{
					props:{className:"bx-tm-dialog-overlay"}
					//attrs:{width:"100%",heigth:"100%"}
					}
				)),
				this.table
				]
			}
			);
	document.body.appendChild(this.overlay);
	this.popup = new BX.PopupWindow(this.popup_id, null, {
		closeIcon : {right: "12px", top: "10px"},
		autoHide: false,
		draggable:false,
		closeByEsc:true,
		titleBar: true,
		toFrontOnShow: false
	});

	BX.ZIndexManager.register(this.overlay);

	BX.addCustomEvent(this.popup, "onPopupClose", BX.proxy(function(){
		this.overlay.style.display = "none";
		//BX.removeClass(document.body, "report-body-overflow");
	}, this));

	BX.addCustomEvent(this.popup, "onAfterPopupShow", BX.proxy(function(){
		this.overlay.style.display = "block";
		BX.ZIndexManager.bringToFront(this.overlay);

		//BX.addClass(document.body, "report-body-overflow");
		setTimeout(BX.proxy(this.FixOverlay,this),10);
		//this.FixOverlay();
	}, this));

	this.popup_place.appendChild(
			BX.create("DIV",{
			style:{display:"inline-block"},
			children:[
				this.popup.popupContainer
				]
			})
		);

	this.FixOverlay();
	BX.bind(window.top, 'resize', BX.proxy(this.FixOverlay, this));
	this.ACTIONS = {
		delay: BX.proxy(this.ActionDelay, this),
		edit: BX.proxy(this.ActionEdit, this),
		save: BX.proxy(this.ActionSave, this),
		send: BX.proxy(this.ActionSend, this)

	};
};

BX.extend(BX.CTimeManReportFormWeekly, BX.CTimeManPopup);

BX.CTimeManReportFormWeekly.prototype.FixOverlay = function()
{
	this.popup.popupContainer.style.position = "relative";
	this.popup.popupContainer.style.left = "0px";
	var size = BX.GetWindowInnerSize();
	this.overlay.style.height = size.innerHeight + "px";
	this.overlay.style.width = size.innerWidth + "px";
	var scroll = BX.GetWindowScrollPos();
	this.overlay.firstChild.style.height = Math.max(this.popup.popupContainer.offsetHeight+50, this.overlay.clientHeight)+"px";
	this.overlay.firstChild.style.width = Math.max(1024, this.overlay.clientWidth) + "px";
	this.popup.popupContainer.style.top = "0px";
	this.closeLink.style.width = this.closeLink.parentNode.clientWidth + 'px';
}

BX.CTimeManReportFormWeekly.prototype.PopupClose = function()
{
	this.popup.close();
}
BX.CTimeManReportFormWeekly.prototype.Show = function(data)
{
	if (!data && !this.data)
	{
		BX.timeman.showWait();
		if (this.mode == 'edit')
			BX.timeman_query('check_report', {}, BX.proxy(this.Show, this));
		return;
	}
	if (window.BXTIMEMANREPORTFORMWEEKLY && window.BXTIMEMANREPORTFORMWEEKLY != this)
		window.BXTIMEMANREPORTFORMWEEKLY.popup.close();

	window.BXTIMEMANREPORTFORMWEEKLY = this;

	BX.timeman.closeWait();
	if (!this.data)
		this.data = data.REPORT_DATA;
	if(!this.data.INFO)
		return;
	BX.addCustomEvent(window, 'onLocalStorageSet', BX.proxy(
		function(data)
		{
			var key = this.data.REPORT_DATE_FROM+"#"+this.data.REPORT_DATE_TO+"_ACTION";
			if (data.key == key && data.value.LAST_ACTION == "SEND_REPORT")
			{
				this.parent.ShowCallReport = false;
				BX.onCustomEvent(this, 'OnWorkReportSend', []);
				this.popup.close();
			}
		}, this)
	);
	this.popup.setContent(this.GetContent());
	this.popup.setButtons(this.GetButtons());
	this.popup.setTitleBar({content :this.GetTitle()});
	//closeByEsc disable fix for task-popup
	try
	{
		BX.addCustomEvent(taskIFramePopup, 'onBeforeShow', BX.proxy(function(){
			this.popup.setClosingByEsc(false);
		},this));
		BX.addCustomEvent(taskIFramePopup, 'onBeforeHide', BX.proxy(function(){
			this.popup.setClosingByEsc(true);
		},this));
	}catch(e){}
	this.popup.show();
	this.popup.setOffset({offsetTop: 1});

	return true;
}


BX.CTimeManReportFormWeekly.prototype.GetContentPeopleRow = function()
{
	return BX.create('DIV', {
		props: {className: 'tm-report-popup-people'},
		html: '<div class="tm-report-popup-r1"></div><div class="tm-report-popup-r0"></div>\
<div class="tm-report-popup-people-inner">\
	<div class="tm-report-popup-user tm-report-popup-employee">\
		<span class="tm-report-popup-user-label">' + BX.message('JS_CORE_TMR_FROM') + ':</span><a href="' + this.data.FROM.URL + '" class="tm-report-popup-user-avatar"' + (this.data.FROM.PHOTO ? ' style="background: url(\'' + encodeURI(this.data.FROM.PHOTO) + '\') no-repeat scroll center center transparent; background-size: cover;"' : '') + '></a><span class="tm-report-popup-user-info"><a href="' + this.data.FROM.URL + '" class="tm-report-popup-user-name">' + this.data.FROM.NAME + '</a><span class="tm-report-popup-user-position">' + (this.data.FROM.WORK_POSITION || '&nbsp;') + '</span></span>\
	</div>\
	<div class="tm-report-popup-user tm-report-popup-director">\
		<span class="tm-report-popup-user-label">' + BX.message('JS_CORE_TMR_TO') + ':</span><a href="' + this.data.TO[0].URL + '" class="tm-report-popup-user-avatar"' + (this.data.TO[0].PHOTO ? ' style="background: url(\'' + encodeURI(this.data.TO[0].PHOTO) + '\') no-repeat scroll center center transparent; background-size: cover;"' : '') + '></a><span class="tm-report-popup-user-info"><a href="' + this.data.TO[0].URL + '" class="tm-report-popup-user-name">' + this.data.TO[0].NAME + '</a><span class="tm-report-popup-user-position">' + (this.data.TO[0].WORK_POSITION || '&nbsp;') + '</span></span>\
	</div>\
</div>\
<div class="tm-report-popup-r0"></div><div class="tm-report-popup-r1"></div>'
	});
}

BX.CTimeManReportFormWeekly.prototype.GetContentReportRow = function(report_value)
{
	this.TABCONTROL = new BX.CTimeManTabEditorControl({
			lhename:"obReportWeekly",
			parent:this,
			uselocalstorage: true,
			localstorage_key: this.data.INFO.REPORT_DATE_FROM+"#"+this.data.INFO.REPORT_DATE_TO
		}
	);
	this.TABCONTROL.addTab({
		ID:"report_text",
		TITLE:BX.message('JS_CORE_TMR_REPORT'),
		CONTENT:this.data.REPORT
	});
	this.TABCONTROL.addTab({
		ID:"plan_text",
		TITLE:BX.message('JS_CORE_TMR_PLAN'),
		CONTENT:this.data.PLANS
	});
	this.TABCONTROL.SwitchEdit();
	return  BX.create('DIV', {
			props: {className: 'tm-report-popup-desc'},
			children: [
				BX.create('DIV', {
					props: {className: 'tm-report-popup-desc-text'},
					children: [
						this.TABCONTROL.div
					]
				})
			]
		});
}

BX.CTimeManReportFormWeekly.prototype._addTask = function(task_data)
{
	if (this.data.INFO.TASKS && this.data.INFO.TASKS.length>0)
		for(i=0;i<this.data.INFO.TASKS.length;i++)
		{
			if (this.data.INFO.TASKS[i].ID == task_data.id)
				return;
		}
	BX.timeman_query('get_task', {task_id:task_data.id}, BX.proxy(this.addTask, this));
}

BX.CTimeManReportFormWeekly.prototype.addTask = function(task_data)
{

	var inp, inpTime;

	if (typeof task_data.TIME == 'undefined')
		task_data.TIME = 0;

	var taskTime = 0;

	this.listTasks.appendChild(
		BX.create('LI', {
			props: {
				className: 'tm-popup-task tm-popup-task-status-' + BX.timeman.TASK_SUFFIXES[task_data.STATUS],
				bx_task_id: task_data.ID
			},
			children:
			[
				(inp = this.mode == 'admin' ? null : BX.create('INPUT', {
					props: {
						className: 'tm-report-popup-include-checkbox',
						value: task_data.ID,
						checked: typeof tasks_unchecked == 'undefined'
							? taskTime > 0 : false,
						defaultChecked: true
					},
					attrs: {type: 'checkbox'}
				})),
				BX.create('SPAN', {
					props: {
						className: 'tm-popup-task-name',
						BXPOPUPBIND: this.tdTasks.firstChild,
						BXPOPUPPARENT: this.listTasks,
						BXPOPUPANGLEOFFSET: 44
					},
					text: task_data.TITLE,
					events: {click: BX.proxy(this.parent.WND.showTask, this)}
				}),
				(inpTime = this.mode == 'admin' ? null : BX.create('INPUT', {
					props: {value: taskTime},
					attrs: {type: 'hidden'}
				})),
				BX.create('SPAN', {
					props: {
						className: 'tm-popup-task-time' + (this.mode == 'admin' ? '-admin' : ''),
						BXTIMEINPUT: this.mode == 'admin' ? null : inpTime,
						BXCHECKINPUT: this.mode == 'admin' ? null : inp
					},
					events: this.mode == 'admin' ? null : {click:BX.timeman.editTime},
					text: BX.timeman.formatWorkTime(taskTime)
				})

				/*BX.create('SPAN', {
					props: {className: 'tm-popup-task-delete'},
					events: {click: BX.proxy(this.parent.WND.removeTask, this.parent.WND)}
				})*/
			]
		})
	);

	if (inp)
	{
		this.listInputTasks.push(inp);
		this.listInputTasksTime.push(inpTime);
	}
		this.incLabel.style.display = "block";
		this.data.INFO.TASKS[this.data.INFO.TASKS.length] = task_data;
}

BX.CTimeManReportFormWeekly.prototype.GetContentTasks = function(tdTasks, tasks_unchecked, tasks_time)
{
	this.tdTasks = tdTasks;
	tdTasks.className = 'tm-report-popup-tasks';
	this.selectorLink = BX.create("DIV",{
				props:{className:"tm-popup-section-title-link tm-popup-section-title-link-weekly"},
				html:BX.message('JS_CORE_TM_TASKS_CHOOSE')

		});
	if(this.TASKSWND == null)
	{
		this.TASKSWND = new BX.CTimeManTasksSelector(this, {
				node: this.selectorLink,
				onselect: BX.proxy(this._addTask, this)
			});
	}
	else
		this.TASKSWND.setNode(this.selectorLink);
	this.selectorLink.onclick = BX.proxy(this.TASKSWND.Show, this.TASKSWND);
	tdTasks.appendChild(BX.create('DIV', {
		props: {className: 'tm-popup-section-title'},
		children:[
			BX.create("DIV",{
				props:{className:"tm-popup-section-title-text"},
				html:BX.message('JS_CORE_TM_TASKS')

		}),
		this.selectorLink,
		BX.create("DIV",{
				props:{className:"tm-popup-section-title-line"}

		})
		]
	}));
		this.listTasks = null;
		tdTasks.appendChild(BX.create('DIV', {

			props: {className: 'tm-popup-tasks'},
			children: [
				this.mode == 'admin' ? null :
				this.incLabel = BX.create('DIV', {
					props: {className: 'tm-report-popup-inlude-tasks'},
					style:{display:"none"},
					html: '<span class="tm-report-popup-inlude-arrow"></span><span class="tm-report-popup-inlude-hint">' + BX.message('JS_CORE_TMR_REPORT_INC') + '</span>'
				}),
				(this.listTasks = BX.create('OL', {props: {className: 'tm-popup-task-list'}}))
			]
		}));

	this.listInputTasks = []; this.listInputTasksTime = [];
	if (this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0)
	{
		this.incLabel.style.display = "block";
		for (var i=0;i<this.data.INFO.TASKS.length;i++)
		{
			var inp, inpTime;

			if (typeof this.data.INFO.TASKS[i].TIME == 'undefined')
			{
				this.data.INFO.TASKS[i].TIME = 0;
				// if(typeof this.data.INFO.TASKS[i].TIME_ESTIMATE != 'undefined')
				// {
				// 	this.data.INFO.TASKS[i].TIME += parseInt(this.data.INFO.TASKS[i].TIME_ESTIMATE)||0;
				// }

				if(typeof this.data.INFO.TASKS[i].TIME_SPENT_IN_LOGS != 'undefined')
				{
					this.data.INFO.TASKS[i].TIME += parseInt(this.data.INFO.TASKS[i].TIME_SPENT_IN_LOGS)||0;
				}
			}

			var taskTime = typeof tasks_time[i] == 'undefined' ? this.data.INFO.TASKS[i].TIME : tasks_time[i];

			this.listTasks.appendChild(
				BX.create('LI', {
					props: {
						className: 'tm-popup-task tm-popup-task-status-' + BX.timeman.TASK_SUFFIXES[this.data.INFO.TASKS[i].STATUS],
						bx_task_id: this.data.INFO.TASKS[i].ID
					},
					children:
					[
						(inp = this.mode == 'admin' ? null : BX.create('INPUT', {
							props: {
								className: 'tm-report-popup-include-checkbox',
								value: this.data.INFO.TASKS[i].ID,
								checked: typeof tasks_unchecked[i] == 'undefined'
									? taskTime > 0 : false,
								defaultChecked: true
							},
							attrs: {type: 'checkbox'}
						})),
						BX.create('SPAN', {
							props: {
								className: 'tm-popup-task-name',
								BXPOPUPBIND: tdTasks.firstChild,
								BXPOPUPPARENT: this.listTasks,
								BXPOPUPANGLEOFFSET: 44
							},
							text: this.data.INFO.TASKS[i].TITLE,
							events: {click: BX.proxy(this.parent.WND.showTask, this)}
						}),
						(inpTime = this.mode == 'admin' ? null : BX.create('INPUT', {
							props: {value: taskTime},
							attrs: {type: 'hidden'}
						})),
						BX.create('SPAN', {
							props: {
								className: 'tm-popup-task-time' + (this.mode == 'admin' ? '-admin' : ''),
								BXTIMEINPUT: this.mode == 'admin' ? null : inpTime,
								BXCHECKINPUT: this.mode == 'admin' ? null : inp
							},
							events: this.mode == 'admin' ? null : {click:BX.timeman.editTime},
							text: BX.timeman.formatWorkTime(taskTime)
						})

						/*BX.create('SPAN', {
							props: {className: 'tm-popup-task-delete'},
							events: {click: BX.proxy(this.parent.WND.removeTask, this.parent.WND)}
						})*/
					]
				})
			);

			if (inp)
			{
				this.listInputTasks.push(inp);
				this.listInputTasksTime.push(inpTime);
			}
		}
	}

	else
		this.data.INFO.TASKS = [];
}

BX.CTimeManReportFormWeekly.prototype.GetContentEvents = function(tdEvents)
{


	if (this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0)
	{
		tdEvents.className = 'tm-report-popup-events' + (BX.isAmPmMode() ? " tm-popup-events-ampm" : "");
			tdEvents.appendChild(BX.create('DIV', {
				props: {className: 'tm-popup-section-title'},
				html: '<div class="tm-popup-section-title-text">' + BX.message('JS_CORE_TM_EVENTS') + '</div><div class="tm-popup-section-title-line"></div>'
			}));
		this.listEvents = null;
		tdEvents.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-events' },
			children: [
				(this.listEvents = BX.create('DIV', {props: {className: 'tm-popup-event-list'}}))
			]
		}));

		for (var i=0;i<this.data.INFO.EVENTS.length;i++)
		{
			this.listEvents.appendChild(
				this.parent.WND.CreateEvent(this.data.INFO.EVENTS[i], {
					BXPOPUPBIND: tdEvents.firstChild,
					BXPOPUPANGLEOFFSET: 44
				},
				true
			)
			)
		}
	}
}

BX.CTimeManReportFormWeekly.prototype.taskEntryAdd = function(task)
{
	this.parent.taskEntryAdd(task, BX.delegate(function(data) {
		this.parent._Update(data);
		this.data = null;
		this.Show();
	}, this));
}

BX.CTimeManReportFormWeekly.prototype.calendarEntryAdd = function(ev)
{
	this.parent.calendarEntryAdd(ev, BX.delegate(function(data) {
		if (data && data.error && data.error_id == 'CHOOSE_CALENDAR')
		{
			this.Update = BX.proxy(this.parent.Update, this.parent); // hack
			(new BX.CTimeManCalendarSelector(this, {
				node: this.eventsForm,
				data: data.error
			})).Show();
		}
		else
		{
			this.parent._Update(data);
			this.data = null;
			this.Show();
		}
	}, this));
}

BX.CTimeManReportFormWeekly.prototype.GetContent = function()
{
	var report_value = '', tasks_unchecked = {}, tasks_time = {};

	if (this.DIV)
	{
		report_value = this.data.REPORT ? this.data.REPORT : '';

		if (this.listInputTasks)
		{
			for (i = 0, l = this.listInputTasks.length; i < l; i++)
			{
				if (!this.listInputTasks[i].checked)
				{
					tasks_unchecked[i] = true;
				}
				tasks_time[i] = this.listInputTasksTime[i].value;
			}
		}

		BX.cleanNode(this.DIV);
		this.DIV = null;
	}

	this.DIV = BX.create('DIV', {
		props: {className: 'tm-report-popup ui-form' + (this.mode == 'edit' ? '' : ' tm-report-popup-read-mode') + (this.ie7 ? ' tm-report-popup-ie7' : '')},
		children: [
			this.GetContentPeopleRow(),
			this.GetContentReportRow(report_value)
		]
	});
	this.fileForm = new BX.CTimeManUploadForm({
		id:this.data.REPORT_ID,
		user_id: this.data.FROM.ID,
		files_list:this.data.INFO.FILES,
		mode:"edit",
		div:this.DIV
	});
	this.fileForm.GetUploadForm();
	BX.addCustomEvent(this.fileForm, "OnUploadFormRefresh", BX.proxy(function(){
		this.FixOverlay();
	},this));
	if (this.mode == 'edit' ||
		this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0 || this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0)
	{

		var tr = this.DIV.appendChild(BX.create('TABLE', {
			props: {className: 'tm-report-popup-items'},
			attrs: {cellSpacing: '0'},
			children: [BX.create('TBODY')]
		})).tBodies[0].insertRow(-1);

		if (this.data.INFO.TASKS_ENABLED && (this.mode == 'edit' || this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0))
			this.GetContentTasks(tr.insertCell(-1), tasks_unchecked, tasks_time);
		if (this.data.INFO.CALENDAR_ENABLED && this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0)
			this.GetContentEvents(tr.insertCell(-1));
	}

	return this.DIV;
}

BX.CTimeManReportFormWeekly.prototype.GetTitle = function()
{
	var title = BX.create('DIV', {
		props: {className: 'tm-report-popup-titlebar'},
		children: [
			BX.create('DIV', {
				props: {className: 'tm-report-popup-title'},
				text: BX.message('JS_CORE_TMR_REPORT_WEEKLY')
			}),
			BX.create('SPAN', {
				props: {className: 'tm-report-popup-title-date'},
				text: this.data.INFO.DATE_TEXT
			})
		]
	});

	if (false && this.mode == 'admin') // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	{
		title.insertBefore(BX.create('SPAN', {
			props: {
				className: 'tm-report-popup-title-left'
			},
			events:
			{
				click: BX.proxy(this.ClickPrevious, this)
			}
		}), title.lastChild);

		title.appendChild(BX.create('SPAN', {
			props: {
				className: 'tm-report-popup-title-right'
			},
			events:
			{
				click: BX.proxy(this.ClickNext, this)
			}
		}))
	}

	return title;
}

BX.CTimeManReportFormWeekly.prototype.GetButtons = function()
{
	var b = [];
	if (this.mode == 'edit')
	{
		b.push(
			new BX.PopupWindowButton({
				text : BX.message('JS_CORE_TMR_SUBMIT_WEEKLY'),
				id:"tm-work-report-send",
				className : "popup-window-button-accept",
				events : {click: this.ACTIONS["send"]}
			})
		);
		b.push(
			new BX.PopupWindowButton({
				text : BX.message('JS_CORE_TM_B_SAVE'),
				id:"tm-work-report-save",
				className : "popup-window-button",
				events : {click: this.ACTIONS['save']}
			})
		);
		b.push(
			new BX.PopupWindowButton({
				text : BX.message('JS_CORE_TMR_DELAY_WEEKLY'),
				id:"tm-work-report-delay",
				className : "popup-window-button",
				events : {click: this.ACTIONS['delay']}
			})
		);

	}
	return b;
}


BX.CTimeManReportFormWeekly.prototype.ActionEdit = function()
{
	BX.timeman.showWait(this.popup.popupContainer,0);
	if (this.post == true)
	{
		return;
	}
	var i, l, data = {
		REPORT_ID:this.data.REPORT_ID,
		DATE_FROM:this.data.INFO.REPORT_DATE_FROM,
		DATE_TO:this.data.INFO.REPORT_DATE_TO,
		TO_USER: this.data.TO[0].ID,
		FILES:this.fileForm.files,
		PLANS:this.TABCONTROL.GetTabContent("plan_text"),
		REPORT:this.TABCONTROL.GetTabContent("report_text"),
		TASKS:[],
		TASKS_TIME:[],
		EVENTS:[],
		ACTIVE: this.ACTIVE,
		DELAY:this.DELAY
	};

	if (this.data.INFO.EVENTS)
	{
		for(i = 0, l = this.data.INFO.EVENTS.length; i < l; i++)
		{
			data.EVENTS.push(this.data.INFO.EVENTS[i]);
		}
	}

	if (this.listInputTasks)
	{
		for (i = 0, l = this.listInputTasks.length; i < l; i++)
		{
			if (this.listInputTasks[i].checked)
			{
				data.TASKS.push(this.data.INFO.TASKS[i]);
				data.TASKS_TIME.push(this.listInputTasksTime[i].value);
			}
		}
	}

	if (data.EVENTS.length)
	{
		data.EVENTS = JSON.stringify(data.EVENTS);
	}

	this.post = true;
	this.parent.Query('save_full_report', data, BX.proxy(this._ActionEdit, this));
};

BX.CTimeManReportFormWeekly.prototype.ActionSave = function(e)
{
		this.ACTIVE = "N";
		this.DELAY = "N";
		this.closeAfterPost = false;
		this.ActionEdit();
}

BX.CTimeManReportFormWeekly.prototype.ActionSend = function(e)
{
		this.ACTIVE = "Y";
		this.DELAY = "Y";
		this.closeAfterPost = true;
		this.ActionEdit();
}

BX.CTimeManReportFormWeekly.prototype.ActionDelay = function(e)
{
	this.ACTIVE = "N";
	this.DELAY = "Y";
	this.closeAfterPost = true;
	this.ActionEdit();
}

BX.CTimeManReportFormWeekly.prototype._ActionEdit = function(report_id)
{
	this.post = false;
	if (this.ACTIVE == "Y")
	{
		BX.localStorage.set(this.data.REPORT_DATE_FROM+"#"+this.data.REPORT_DATE_TO+"_ACTION",{LAST_ACTION:"SEND_REPORT"});
		BX.localStorage.remove(this.data.REPORT_DATE_FROM+"#"+this.data.REPORT_DATE_TO+"_ACTION");
		this.parent.ShowCallReport = false;
		BX.onCustomEvent(this, 'OnWorkReportSend', []);
	}
	if(report_id)
		this.data.REPORT_ID = report_id;
	if(this.closeAfterPost == true)
		this.popup.close();
}


BX.CTimeManReportFormWeekly.prototype.ActionAdmin = function()
{
	var data = {
		ID: this.data.INFO.INFO.ID
	};

	if (this.bTimeEdited)
	{
		data.INFO = {
			TIME_START: this.data.INFO.INFO.TIME_START,
			TIME_FINISH: this.data.INFO.INFO.TIME_FINISH
		};
	}

	this.parent.Query('admin_save', data, BX.proxy(this._ActionAdmin, this));
}

BX.CTimeManReportFormWeekly.prototype._ActionAdmin = function(data)
{
	//TODO: we should update report cell here
	this.data = data;

	var tmp_data = BX.clone(this.data.INFO.INFO, true);
	tmp_data.ACTIVE = tmp_data.ACTIVE == 'Y';
	tmp_data.PAUSED = tmp_data.PAUSED == 'Y';
	tmp_data.ACTIVATED = tmp_data.ACTIVATED == 'Y';
	tmp_data.CAN_EDIT = this.data.INFO.CAN_EDIT == 'Y';

	this.params.parent_object.Reset(tmp_data);
}

BX.CTimeManReportFormWeekly.prototype.TimeEdit = function()
{
	if (this.POPUP_TIME)
	{
		this.POPUP_TIME.Clear();
		this.POPUP_TIME = null;
	}

	var tmp_data = {
		ID: this.data.INFO.INFO.ID,
		CAN_EDIT: this.mode == 'edit' || this.data.INFO.CAN_EDIT == 'Y',
		INFO: {
			DATE_START: this.data.INFO.INFO.DATE_START,
			TIME_START: this.data.INFO.INFO.TIME_START,
			TIME_FINISH: this.data.INFO.INFO.TIME_FINISH || this.data.INFO.EXPIRED_DATE,
			TIME_LEAKS: this.data.INFO.INFO.TIME_LEAKS,
			DURATION: this.data.INFO.INFO.DURATION
		}
	};

	if (!tmp_data.INFO.TIME_FINISH)
	{
		var q = new Date();
		tmp_data.INFO.TIME_FINISH = q.getHours()*3600 + q.getMinutes() * 60 - (this.params.parent_object ? this.params.parent_object.timezone_diff/1000 : 0);
	}

	this.POPUP_TIME = new BX.CTimeManEditPopup(this.parent, {
		node: this.TIME_EDIT_BUTTON || this.node,
		bind: this.TIME_EDIT_BUTTON || this.node,
		entry: tmp_data,
		mode: this.mode
	});

	this.POPUP_TIME.params.popup_buttons = [
		(this.POPUP_TIME.SAVEBUTTON = new BX.PopupWindowButton({
			text : BX.message('JS_CORE_TM_B_SAVE'),
			className : "popup-window-button-create",
			events : {click : BX.proxy(this.setEditValue, this)}
		})),
		new BX.PopupWindowButtonLink({
			text : BX.message('JS_CORE_TM_B_CLOSE'),
			className : "popup-window-button-link-cancel",
			events : {click : BX.proxy(this.POPUP_TIME.closeWnd, this.POPUP_TIME)}
		})
	];
	this.POPUP_TIME.WND.setButtons(this.POPUP_TIME.params.popup_buttons);

	this.POPUP_TIME._SetSaveButton = this.POPUP_TIME.SetSaveButton;
	this.POPUP_TIME.SetSaveButton = BX.DoNothing;
	this.POPUP_TIME.restoreButtons();
	this.POPUP_TIME.Show();
}

BX.CTimeManReportFormWeekly.prototype.setEditValue = function(e)
{
	var v, r = this.mode == 'edit' ? BX.util.trim(this.POPUP_TIME.REPORT.value) : BX.message('JS_CORE_TMR_ADMIN');
	if (r.length <= 0)
	{
		this.POPUP_TIME.REPORT.className = 'bx-tm-popup-clock-wnd-report-error';
		this.POPUP_TIME.REPORT.focus();
		this.POPUP_TIME.REPORT.onkeypress = function() {this.className = '';this.onkeypress = null;};
	}
	else
	{
		/*
		if (this.arPause.length > 0)
		{
			for(var i=0;i<this.arPause.length;i++)
				data[this.arPause[i].fld] = this.arPause[i].val
		}

		data.report = r;

		this.parent.PARENT.Query('save', data, BX.proxy(this.parent.PARENT._Update, this.parent.PARENT));

		this.bChanged = false;
		this.restoreButtons();

		this.SHOW = false;
		this.REPORT.value = '';

		this.arInputs[this.CLOCK_ID].value = this.arInputs[this.CLOCK_ID].BXORIGINALVALUE;
		this.arInputs[this.CLOCK_ID_1].value = this.arInputs[this.CLOCK_ID_1].BXORIGINALVALUE;
		this.arPause = [];

		this.WND.close();

		*/

		this.data.INFO.INFO.TIME_START = BX.timeman.unFormatTime(this.POPUP_TIME.arInputs.timeman_edit_from.value);
		this.data.INFO.INFO.TIME_FINISH = BX.timeman.unFormatTime(this.POPUP_TIME.arInputs.timeman_edit_to.value);

		this.data.INFO.INFO.DURATION = this.data.INFO.INFO.TIME_FINISH - this.data.INFO.INFO.TIME_FINISH - this.data.INFO.INFO.TIME_LEAKS;

		if (this.data.INFO.STATE == 'EXPIRED')
			this.data.INFO.EXPIRED_DATE = this.data.INFO.INFO.TIME_FINISH;

		var now = new Date();

		if (!this.data.REPORTS.DURATION)
			this.data.REPORTS.DURATION = [];

		this.data.REPORTS.DURATION[0] = {
			ACTIVE: true,
			REPORT: r,
			TIME: now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds(),
			DATE_TIME: parseInt(now.valueOf() / 1000)
		};

		this.POPUP_TIME.WND.close();

		this.bTimeEdited = true;
		this.Show(this.data);
	}

	return BX.PreventDefault(e)
}

BX.CTimeManReportFormWeekly.prototype.ShowTpls = function(e)
{
	if (!this.TPLWND)
	{
		var content = BX.create('DIV', {props: {className: 'bx-tm-report-tpl'}}), TPLWND;

		var rep = this.REPORT_TEXT;
		var handler = function() {rep.value = this.BXTEXT; TPLWND.close();};

		for (var i=0; i<this.data.REPORT_TPL.length; i++)
		{
			content.appendChild(BX.create('SPAN', {
				props: {className: 'bx-tm-report-tpl-item',BXTEXT: BX.util.trim(this.data.REPORT_TPL[i])},
				events: {click: handler},
				text: BX.util.trim(this.data.REPORT_TPL[i])
			}));
		}

		TPLWND = this.TPLWND = BX.PopupWindowManager.create(
			'timeman_template_selector', BX.proxy_context,
			{
				autoHide: true,
				content: content
			}
		);
	}
	else
	{
		this.TPLWND.setBindElement(BX.proxy_context)
	}

	this.TPLWND.show();

	return BX.PreventDefault(e);
}

BX.CTimeManReportFormWeekly.prototype.ClickPrevious = function()
{
	alert('Previous!');

}

BX.CTimeManReportFormWeekly.prototype.ClickNext = function()
{
	alert('Next!');
}

BX.CTimeManReportFormWeekly.prototype.Clear = function()
{
	this.bCleared = true;

	if (this.POPUP_TIME)
	{
		this.POPUP_TIME.WND.close();
		this.POPUP_TIME.WND.destroy();
		this.POPUP_TIME.Clear();
		this.POPUP_TIME = null;
	}

	this.popup.close();
	this.popup.destroy();
}


/*******************************************************************************/
BX.CTimeManReportForm = function(parent, params)
{
	this.parent = parent;
	this.params = params;

	this.node = this.params.node;
	this.mode = this.params.mode || 'edit';

	this.data = params.data;
	this.external_finish_ts = params.external_finish_ts;
	this.external_finish_ts_report = params.external_finish_ts_report;

	this.popup_id = 'timeman_daily_report_popup_' + parseInt(Math.random() * 100000);

	this.bLoaded = !!this.data;
	this.bTimeEdited = false;

	this.params.offsetTop = 5;
	this.params.offsetLeft = -50;

	this.ie7 = false;
	/*@cc_on
		 @if (@_jscript_version <= 5.7)
			this.ie7 = true;
		/*@end
	@*/

	this.popup = new BX.PopupWindow(this.popup_id, this.params.bind, {
		closeIcon : {right: "12px", top: "10px"},
		offsetLeft : this.params.offsetLeft || 500,
		draggable: false, // !params.type,
		autoHide: false,
		closeByEsc: true,
		titleBar: true,
		bindOptions : {
			forceBindPosition : true,
			forceTop : false
		}
	});

	this.ACTIONS = {
		edit: BX.proxy(this.ActionEdit, this),
		admin: BX.proxy(this.ActionAdmin, this)
	}
}

BX.extend(BX.CTimeManReportForm, BX.CTimeManPopup)

BX.CTimeManReportForm.prototype.Show = function(data)
{
	if (!data && !this.data)
	{
		BX.timeman.showWait();
		if (this.mode == 'edit')
			BX.timeman_query('close', {}, BX.proxy(this.Show, this));
		return;
	}

	if (window.BXTIMEMANREPORTFORM && window.BXTIMEMANREPORTFORM != this)
		window.BXTIMEMANREPORTFORM.popup.close();

	window.BXTIMEMANREPORTFORM = this;

	BX.timeman.closeWait();

	this.data = data || this.data;

	if (this.external_finish_ts)
	{
		this.data.INFO.INFO.TIME_FINISH = this.external_finish_ts;
	}

	if (this.mode == 'edit' && this.data.INFO.STATE === 'EXPIRED' && !this.bTimeEdited)
	{
		this.TimeEdit();
		return;
	}
	else
	{
		BX.CTimeManReportForm.superclass.Show.apply(this);
		this.popup.setOffset({offsetTop: 1});
		return true;
	}
}

BX.CTimeManReportForm.prototype.GetContentTimeRow = function()
{
	var
		now = new Date(),

		tz_emp = parseInt(this.data.INFO.INFO.TIME_OFFSET),
		tz_self = parseInt(BX.message('USER_TZ_OFFSET')),

		time_finish = this.data.INFO.INFO.TIME_FINISH
			? this.data.INFO.INFO.TIME_FINISH
			: (
				this.data.INFO.STATE == 'EXPIRED'
					? this.data.INFO.EXPIRED_DATE
					: now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds() - tz_self + tz_emp
			),
		duration = time_finish - this.data.INFO.INFO.TIME_START - this.data.INFO.INFO.TIME_LEAKS;

	var obTime = BX.create('DIV', {props: {className: 'tm-report-popup-time-brief'}});

	obTime.appendChild(BX.create('SPAN', {
		props: {className: 'tm-report-popup-time-title'},
		text: BX.message('JS_CORE_TMR_WORKTIME')
	}));

	var bBrief = this.data.REPORTS && (
			this.data.REPORTS.DURATION && this.data.REPORTS.DURATION.length > 0
			|| this.data.REPORTS.TIME_START && this.data.REPORTS.TIME_START.length > 0
			|| this.data.REPORTS.TIME_FINISH && this.data.REPORTS.TIME_FINISH.length > 0
		);

	if (!bBrief)
	{
		var children = [
			BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-label'},
				html: BX.message('JS_CORE_TM_ARR') + ':'
			}),
			BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-value'},
				html: BX.timeman.formatTime(this.data.INFO.INFO.TIME_START, false)
			}),
			BX.create('SPAN', {props: {className: 'tm-report-popup-time-separator'}})
		]

		if (this.data.INFO.INFO.TIME_LEAKS > 0)
		{
			children = BX.util.array_merge(children, [
				BX.create('SPAN', {
					props: {className: 'tm-report-popup-time-label'},
					html: BX.message('JS_CORE_TMR_PAUSE') + ':'
				}),
				BX.create('SPAN', {
					props: {className: 'tm-report-popup-time-value'},
					html: BX.timeman.formatWorkTime(this.data.INFO.INFO.TIME_LEAKS, false)
				}),
				BX.create('SPAN', {props: {className: 'tm-report-popup-time-separator'}})
			]);
		}

		children = BX.util.array_merge(children, [
			BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-label'},
				html: BX.message('JS_CORE_TM_DEP') + ':'
			}),
			BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-value'},
				html: BX.timeman.formatTime(time_finish, false)
			}),
			BX.create('SPAN', {props: {className: 'tm-report-popup-time-separator'}}),

			BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-label'},
				html: BX.message('JS_CORE_TMR_DURATION') + ':'
			}),
			BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-value'},
				html: BX.timeman.formatWorkTime(duration, false)
			})
		]);

		BX.adjust(obTime, {children: [BX.create('SPAN', {
			props: {className: 'tm-report-popup-time-data'},
			children: children
		})]});
	}



	if (this.data.INFO.CAN_EDIT == 'Y' || this.data.CAN_EDIT == 'Y')
	{
		this.TIME_EDIT_BUTTON = obTime.appendChild(BX.create('SPAN', {
			props: {className: 'tm-report-popup-time-edit'},
			events: {click: BX.proxy(this.TimeEdit, this)}
		}));
	}

	obTime = [obTime];

	if (bBrief)
	{
		var time_extra = '', obTable = null;

		obTime[1] = BX.create('DIV', {
			props: {className: 'tm-report-popup-time-full'},
			children: [
				BX.create('TABLE', {
					attrs: {cellSpacing: 0},
					props: {className: 'tm-report-popup-time-grid' + (this.data.INFO.INFO.TIME_LEAKS > 0 ? '' :  ' tm-report-popup-time-grid-minimal')},
					children: [
						(obTable = BX.create('TBODY'))
					]
				})
			]
		});

		var obRow = obTable.insertRow(-1);
		var obCell = BX.adjust(obRow.insertCell(-1), {
			props: {
				className: 'tm-report-popup-time-start' +
					(!this.data.REPORTS.TIME_START
						? ''
						: (this.data.REPORTS.TIME_START[0].ACTIVE
							? ' tm-report-popup-time-changed'
							: ' tm-report-popup-time-approved'
						))
			},
			html: '<span class="tm-report-popup-time-label">' + BX.message('JS_CORE_TM_ARR') + ':</span><span class="tm-report-popup-time-value">' + BX.timeman.formatTime(this.data.INFO.INFO.TIME_START, false) + '</span>'
		});

		if (this.data.REPORTS.TIME_START)
		{
			time_extra += '<tr><td class="tm-report-popup-time-extra-label">' + BX.message('JS_CORE_TMR_REPORT_START') + ':</td><td class="tm-report-popup-time-extra-text">' + this.data.REPORTS.TIME_START[0].REPORT + '</td></tr>';

			obCell.appendChild(BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-real'},
				html: '(<span class="tm-report-popup-time-label">' + BX.message('JS_CORE_TMR_REPORT_ORIG') + ':</span><span class="tm-report-popup-time-value">' + BX.timeman.formatTime(this.data.REPORTS.TIME_START[0].TIME%86400, false) + '</span>)'
			}));

			obCell.appendChild(BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-fixed'},
				html: this.data.REPORTS.TIME_START[0].ACTIVE
					? BX.message('JS_CORE_TMR_NA')
					: BX.message('JS_CORE_TMR_A') + ' ' + this.data.REPORTS.TIME_START[0].DATE_TIME
			}));
		}

		if (this.data.INFO.INFO.TIME_LEAKS > 0)
		{
			var pauseCont;

			obCell = BX.adjust(obRow.insertCell(-1), {
				props: {
					className: 'tm-report-popup-time-break'
				},
				children: [
					BX.create('SPAN', {props: {className: 'tm-report-popup-time-label'}, html: BX.message('JS_CORE_TMR_PAUSE') + ':'}),
					(pauseCont = BX.create('SPAN', {
						props: {className: 'tm-report-popup-time-value'},
						html: BX.timeman.formatWorkTime(this.data.INFO.INFO.TIME_LEAKS)
					}))
				]
			});

			if (this.data.INFO.INFO.PAUSED === 'Y' || this.data.INFO.INFO.PAUSED === true)
			{
				BX.timer(pauseCont, {
					from: (this.data.INFO.INFO.DATE_FINISH * 1000) || new Date(),
					dt: (1000 * this.data.INFO.INFO.TIME_LEAKS),
					display: 'worktime'
				});
			}
		}

		var finishCont, durationCont;

		obCell = BX.adjust(obRow.insertCell(-1), {
			props: {
				className: 'tm-report-popup-time-end' +
					(!this.data.REPORTS.TIME_FINISH
						? ''
						: (this.data.REPORTS.TIME_FINISH[0].ACTIVE
							? ' tm-report-popup-time-changed'
							: ' tm-report-popup-time-approved'
						))
			},
			children: [
				BX.create('SPAN', {props: {className: 'tm-report-popup-time-label'}, html: BX.message('JS_CORE_TM_DEP') + ':'}),
				(finishCont = BX.create('SPAN', {
					props: {className: 'tm-report-popup-time-value' + (this.data.INFO.STATE == 'EXPIRED' ? ' tm-report-popup-time-expired' : '')},
					html: BX.timeman.formatTime(time_finish, false)
				}))
			]
		});

		if (time_finish === 0)
			BX.timer.clock(finishCont, this.params.parent_object.timezone_diff ? this.params.parent_object.timezone_diff : 0);

		if (this.data.REPORTS.TIME_FINISH)
		{
			time_extra += '<tr><td class="tm-report-popup-time-extra-label">' + BX.message('JS_CORE_TMR_REPORT_FINISH') + ':</td><td class="tm-report-popup-time-extra-text">' + this.data.REPORTS.TIME_FINISH[0].REPORT + '</td></tr>';

			obCell.appendChild(BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-real'},
				html: '(<span class="tm-report-popup-time-label">' + BX.message('JS_CORE_TMR_REPORT_ORIG') + ':</span><span class="tm-report-popup-time-value">' + BX.timeman.formatTime(this.data.REPORTS.TIME_FINISH[0].TIME%86400, false) + '</span>)'
			}))

			obCell.appendChild(BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-fixed'},
				html: this.data.REPORTS.TIME_FINISH[0].ACTIVE
					? BX.message('JS_CORE_TMR_NA')
					: BX.message('JS_CORE_TMR_A') + ' ' + this.data.REPORTS.TIME_FINISH[0].DATE_TIME
			}));
		}

		obCell = BX.adjust(obRow.insertCell(-1), {
			props: {
				className: 'tm-report-popup-time-duration' +
					(!this.data.REPORTS.DURATION
						? ''
						: (this.data.REPORTS.DURATION[0].ACTIVE
							? ' tm-report-popup-time-changed'
							: ' tm-report-popup-time-approved'
						))
			},
			children: [
				BX.create('SPAN', {props: {className: 'tm-report-popup-time-label'}, html: BX.message('JS_CORE_TMR_DURATION') + ':'}),
				(durationCont = BX.create('SPAN', {
					props: {className: 'tm-report-popup-time-value' + (this.data.INFO.STATE == 'EXPIRED' ? ' tm-report-popup-time-expired' : '')},
					html: BX.timeman.formatWorkTime(duration)
				}))
			]
		});

		if (this.data.REPORTS.DURATION)
		{
			time_extra += '<tr><td class="tm-report-popup-time-extra-label">' + BX.message('JS_CORE_TMR_REPORT_DURATION') + ':</td><td class="tm-report-popup-time-extra-text">' + this.data.REPORTS.DURATION[0].REPORT + '</td></tr>';

			obCell.appendChild(BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-real'},
				html: '(<span class="tm-report-popup-time-label">' + BX.message('JS_CORE_TMR_REPORT_ORIG') + ':</span><span class="tm-report-popup-time-value">' + BX.timeman.formatTime(this.data.REPORTS.DURATION[0].TIME%86400, false) + '</span>)'
			}))

			obCell.appendChild(BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-fixed'},
				html: this.data.REPORTS.DURATION[0].ACTIVE
					? BX.message('JS_CORE_TMR_NA')
					: BX.message('JS_CORE_TMR_A') + ' ' + this.data.REPORTS.DURATION[0].DATE_TIME
			}));
		}

		if (time_extra)
		{
			obTime[2] = BX.create('DIV', {
				props: {className: 'tm-report-popup-time-extra'},
				html: '<div class="tm-report-popup-time-extra-inner"><table class="tm-report-popup-time-extra-layout" cellspacing="0"><tbody>'
				+ time_extra
				+ '</tbody></table></div>'
			});
		}
	}

	this.ROW_TIME = BX.create('DIV', {
		props: {className: 'tm-report-popup-time'},
		children: [
			BX.create('DIV', {props: {className: 'tm-report-popup-r1'}}),
			BX.create('DIV', {props: {className: 'tm-report-popup-r0'}}),
			BX.create('DIV', {
				props: {className: 'tm-report-popup-time-inner'},
				children: obTime
			}),
			BX.create('DIV', {props: {className: 'tm-report-popup-r0'}}),
			BX.create('DIV', {props: {className: 'tm-report-popup-r1'}})
		]
	});

	return this.ROW_TIME;
}

BX.CTimeManReportForm.prototype.GetContentPeopleRow = function()
{
	return BX.create('DIV', {
		props: {className: 'tm-report-popup-people'},
		html: '<div class="tm-report-popup-r1"></div><div class="tm-report-popup-r0"></div>\
<div class="tm-report-popup-people-inner">\
	<div class="tm-report-popup-user tm-report-popup-employee">\
		<span class="tm-report-popup-user-label">' + BX.message('JS_CORE_TMR_FROM') + ':</span><a href="' + this.data.FROM.URL + '" class="tm-report-popup-user-avatar"' + (this.data.FROM.PHOTO ? ' style="background: url(\'' + encodeURI(this.data.FROM.PHOTO) + '\') no-repeat scroll center center transparent; background-size: cover;"' : '') + '></a><span class="tm-report-popup-user-info"><a href="' + this.data.FROM.URL + '" class="tm-report-popup-user-name">' + this.data.FROM.NAME + '</a><span class="tm-report-popup-user-position">' + (this.data.FROM.WORK_POSITION || '&nbsp;') + '</span></span>\
	</div>\
	<div class="tm-report-popup-user tm-report-popup-director">\
		<span class="tm-report-popup-user-label">' + BX.message('JS_CORE_TMR_TO') + ':</span><a href="' + this.data.TO[0].URL + '" class="tm-report-popup-user-avatar"' + (this.data.TO[0].PHOTO ? ' style="background: url(\'' + encodeURI(this.data.TO[0].PHOTO) + '\') no-repeat scroll center center transparent; background-size: cover;"' : '') + '></a><span class="tm-report-popup-user-info"><a href="' + this.data.TO[0].URL + '" class="tm-report-popup-user-name">' + BX.util.htmlspecialchars(this.data.TO[0].NAME) + '</a><span class="tm-report-popup-user-position">' + (BX.util.htmlspecialchars(this.data.TO[0].WORK_POSITION || '') || '&nbsp;') + '</span></span>\
	</div>\
</div>\
<div class="tm-report-popup-r0"></div><div class="tm-report-popup-r1"></div>'
	});
}

BX.CTimeManReportForm.prototype.GetContentReportRow = function(report_value)
{
	return this.mode == 'edit'
		? BX.create('DIV', {
			props: {className: 'tm-report-popup-desc'},
			children: [
				BX.create('DIV', {
					props: {className: 'tm-report-popup-desc-title'},
					children: [
						BX.create('DIV', {props: {className: 'tm-report-popup-desc-label'}, text: BX.message('JS_CORE_TMR_REPORT')}),
						(this.data.REPORT_TPL.length > 0 ? BX.create('DIV', {
							props: {className: 'tm-report-popup-desc-templates'},
							events: {
								click: BX.delegate(this.ShowTpls, this)
							},
							children: [
								BX.create('SPAN', {
									props: {className: 'tm-report-popup-desc-templates-label'},
									text: BX.message('JS_CORE_TMR_REPORT_TPL')
								}),
								BX.create('SPAN', {
									props: {className: 'tm-report-popup-desc-templates-arrow'}
								})
							]
						}) : null)
					]
				}),
				BX.create('DIV', {
					props: {className: 'tm-report-popup-desc-text'},
					children: [
						(this.REPORT_TEXT = BX.create('TEXTAREA', {
							props: {
								className: 'tm-report-popup-desc-textarea',
								value: report_value || this.data.REPORT
							},
							attrs: {
								rows: '5', cols: '65'
							}

						}))
					]
				})
			]
		})
		: (
			this.data.REPORT.length > 0
				? BX.create('DIV', {
					props: {className: 'tm-report-popup-desc'},
					html: '<div class="tm-popup-section-title"><div class="tm-popup-section-title-text">' + BX.message('JS_CORE_TMR_REPORT') + '</div><div class="tm-popup-section-title-line"></div></div><div class="tm-report-popup-desc-text">' + this.data.REPORT + '</div>'
				})
				: null
		);
}

BX.CTimeManReportForm.prototype.GetContentTasks = function(tdTasks, tasks_unchecked, tasks_time)
{
	tdTasks.className = 'tm-report-popup-tasks';
	tdTasks.appendChild(BX.create('DIV', {
		props: {className: 'tm-popup-section-title'},
		html: '<div class="tm-popup-section-title-text">' + BX.message('JS_CORE_TM_TASKS') + '</div>'
		+ (false && this.mode == 'edit' ? '<div class="tm-popup-section-title-link">' + BX.message('JS_CORE_TM_TASKS_CHOOSE') + '</div>' : '') +
		'<div class="tm-popup-section-title-line"></div>'
	}));

	if (this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0)
	{
		this.listTasks = null;
		tdTasks.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-tasks' + (this.data.INFO.TASKS.length > 10 ? ' tm-popup-tasks-tens' : '')},
			children: [
				this.mode == 'admin' ? null : BX.create('DIV', {
					props: {className: 'tm-report-popup-inlude-tasks'},
					html: '<span class="tm-report-popup-inlude-arrow"></span><span class="tm-report-popup-inlude-hint">' + BX.message('JS_CORE_TMR_REPORT_INC') + '</span>'
				}),
				(this.listTasks = BX.create('OL', {props: {className: 'tm-popup-task-list'}}))
			]
		}));

		this.listInputTasks = []; this.listInputTasksTime = [];
		for (var i=0;i<this.data.INFO.TASKS.length;i++)
		{
			var inp, inpTime;

			if (typeof this.data.INFO.TASKS[i].TIME == 'undefined')
				this.data.INFO.TASKS[i].TIME = 0;

			var taskTime = typeof tasks_time[i] == 'undefined' ? this.data.INFO.TASKS[i].TIME : tasks_time[i];

			this.listTasks.appendChild(
				BX.create('LI', {
					props: {
						className: 'tm-popup-task tm-popup-task-status-' + BX.timeman.TASK_SUFFIXES[this.data.INFO.TASKS[i].STATUS],
						bx_task_id: this.data.INFO.TASKS[i].ID
					},
					children:
					[
						(inp = this.mode == 'admin' ? null : BX.create('INPUT', {
							props: {
								className: 'tm-report-popup-include-checkbox',
								value: this.data.INFO.TASKS[i].ID,
								checked: typeof tasks_unchecked[i] == 'undefined'
									? taskTime > 0 : false,
								defaultChecked: true
							},
							attrs: {type: 'checkbox'}
						})),
						BX.create('SPAN', {
							props: {
								className: 'tm-popup-task-name',
								BXPOPUPBIND: tdTasks.firstChild,
								BXPOPUPPARENT: this.listTasks,
								BXPOPUPANGLEOFFSET: 44
							},
							text: this.data.INFO.TASKS[i].TITLE,
							events: {click: BX.proxy(BX.CTimeManWindow.prototype.showTask, this)}
						}),
						(inpTime = this.mode == 'admin' ? null : BX.create('INPUT', {
							props: {value: taskTime},
							attrs: {type: 'hidden'}
						})),
						BX.create('SPAN', {
							props: {
								className: 'tm-popup-task-time' + (this.mode == 'admin' ? '-admin' : ''),
								BXTIMEINPUT: this.mode == 'admin' ? null : inpTime,
								BXCHECKINPUT: this.mode == 'admin' ? null : inp
							},
							events: this.mode == 'admin' ? null : {click:BX.timeman.editTime},
							text: BX.timeman.formatWorkTime(taskTime)
						})

						/*BX.create('SPAN', {
							props: {className: 'tm-popup-task-delete'},
							events: {click: BX.proxy(this.parent.WND.removeTask, this.parent.WND)}
						})*/
					]
				})
			);

			if (inp)
			{
				this.listInputTasks.push(inp);
				this.listInputTasksTime.push(inpTime);
			}
		}
	}

	if (this.mode == 'edit')
	{
		this.tasksForm = tdTasks.appendChild(this.parent.WND.CreateTasksForm(BX.proxy(this.taskEntryAdd, this)));
		if (!this.data.INFO.TASKS || this.data.INFO.TASKS.length <= 0)
		{
			this.tasksForm.style.marginLeft = '0px';
		}
	}
}

BX.CTimeManReportForm.prototype.taskEntryAdd = function(task)
{
	this.parent.taskEntryAdd(task, BX.delegate(function(data) {
		this.parent._Update(data);
		this.data = null;
		this.Show();
	}, this));
}

BX.CTimeManReportForm.prototype.GetContentEvents = function(tdEvents)
{
	tdEvents.className = 'tm-report-popup-events' + (BX.isAmPmMode() ? " tm-popup-events-ampm" : "");
	tdEvents.appendChild(BX.create('DIV', {
		props: {className: 'tm-popup-section-title'},
		html: '<div class="tm-popup-section-title-text">' + BX.message('JS_CORE_TM_EVENTS') + '</div>\
<div class="tm-popup-section-title-line"></div>'
	}));

	if (this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0)
	{
		this.listEvents = null;
		tdEvents.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-events' },
			children: [
				(this.listEvents = BX.create('DIV', {props: {className: 'tm-popup-event-list'}}))
			]
		}));

		for (var i=0;i<this.data.INFO.EVENTS.length;i++)
		{
			this.listEvents.appendChild(
				BX.CTimeManWindow.prototype.CreateEvent(this.data.INFO.EVENTS[i], {
					BXPOPUPBIND: tdEvents.firstChild,
					BXPOPUPANGLEOFFSET: 44
				})
			)
		}
	}

	if (this.mode == 'edit')
	{
		this.eventsForm = tdEvents.appendChild(this.parent.WND.CreateEventsForm(BX.proxy(this.calendarEntryAdd, this)));
	}
}

BX.CTimeManReportForm.prototype.calendarEntryAdd = function(ev)
{
	this.parent.calendarEntryAdd(ev, BX.delegate(function(data) {
		if (data && data.error && data.error_id == 'CHOOSE_CALENDAR')
		{
			this.Update = BX.proxy(this.parent.Update, this.parent); // hack
			(new BX.CTimeManCalendarSelector(this, {
				node: this.eventsForm,
				data: data.error
			})).Show();
		}
		else
		{
			this.parent._Update(data);
			this.data = null;
			this.Show();
		}
	}, this));
}

BX.CTimeManReportForm.prototype.GetContentComments = function()
{
	var comment_link_span, comment_area_edit, comment_text,
		entry_id = this.data.INFO.ID || this.data.INFO.INFO.ID,
		owner_id = this.data.FROM.ID || this.data.INFO.INFO.USER_ID;

	var sendComment = function()
	{
		if (comment_text.value.length<=0)
			return;

		var data = {
			comment_text: comment_text.value,
			entry_id: entry_id,
			owner_id: owner_id
		};

		// comments_div.style.minHeight = "50px";
		// BX.timeman.showWait(this.comments_div,0);
		comment_area_edit.style.display = "none";
		comment_link_span.style.display = "block";
		comment_text.value = "";

		BX.timeman_query("add_comment_entry", data, BX.proxy(function(data){
			if (data.COMMENTS)
				comment_form.firstChild.innerHTML = data.COMMENTS;
			comment_form.parentNode.scrollTop = comment_form.offsetHeight;
		},this));
	};

	var enterHandler = function(e) {
		if(((e.keyCode == 0xA)||(e.keyCode == 0xD)) && e.ctrlKey == true)
			sendComment.apply(this, []);
	};

	var comment_form = BX.create("DIV",{
		props:{className:"tm-comment-link-div"},
		children:[
			BX.create('DIV', {html: this.data.COMMENTS}),
			comment_link_span = BX.create("SPAN",{
				props:{className:"tm-item-comments-add"},
				children:[
					BX.create("A",{
						attrs:{href:"javascript:void(0)"},
						html:BX.message("JS_CORE_TMR_ADD_COMMENT"),
						events:{"click":
							BX.delegate(
								function()
								{
									comment_area_edit.style.display = "block";
									comment_link_span.style.display = "none";
									comment_form.parentNode.scrollTop = comment_form.offsetHeight;
//										this.slider.FixOverlay();
								},
								this
							)
						}
					})
				]

			}),
			(comment_area_edit = BX.create("DIV",{
				style:{display:"none"},
				children:[
					(comment_text = BX.create("TEXTAREA",{
						props:{className:"tm_comment_text"},
						attrs:{cols:35,rows:4},
						events:{keypress:BX.proxy(enterHandler,this)}
					})),
					BX.create("DIV",{
						children:[
							BX.create("INPUT",{
								attrs:{type:"button",value:BX.message("JS_CORE_TMR_SEND_COMMENT")},
								events:{"click":BX.proxy(sendComment,this)}
							})
					]})
				]
			}))
		]
	});
	return BX.create('DIV', {
		style: {
			marginTop: '6px',
			overflow: 'auto',
			maxHeight: '200px'
		},
		children: [comment_form]
	});
}


BX.CTimeManReportForm.prototype.GetContent = function()
{
	var report_value = '', tasks_unchecked = {}, tasks_time = {};

	if (this.DIV)
	{
		report_value = this.REPORT_TEXT ? this.REPORT_TEXT.value : '';

		if (this.listInputTasks)
		{
			for (i = 0, l = this.listInputTasks.length; i < l; i++)
			{
				if (!this.listInputTasks[i].checked)
				{
					tasks_unchecked[i] = true;
				}
				tasks_time[i] = this.listInputTasksTime[i].value;
			}
		}

		BX.cleanNode(this.DIV);
		this.DIV = null;
	}

	this.DIV = BX.create('DIV', {
		props: {className: 'tm-report-popup ui-form' + (this.mode == 'edit' ? '' : ' tm-report-popup-read-mode') + (this.ie7 ? ' tm-report-popup-ie7' : '')},
		children: [
			this.GetContentPeopleRow(),
			this.GetContentTimeRow(),
			this.GetContentReportRow(report_value)
		]
	});

	if (this.mode == 'edit' ||
		this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0 || this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0)
	{

		var tr = this.DIV.appendChild(BX.create('TABLE', {
			props: {className: 'tm-report-popup-items'},
			attrs: {cellSpacing: '0'},
			children: [BX.create('TBODY')]
		})).tBodies[0].insertRow(-1);

		if (this.data.INFO.TASKS_ENABLED && (this.mode == 'edit' || this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0))
			this.GetContentTasks(tr.insertCell(-1), tasks_unchecked, tasks_time);
		if (this.data.INFO.CALENDAR_ENABLED && (this.mode == 'edit' || this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0))
			this.GetContentEvents(tr.insertCell(-1));
	}

	this.DIV.appendChild(BX.create('DIV', {props: {className: 'tm-popup-section-title-line'}}));
	this.DIV.appendChild(this.GetContentComments());

	return this.DIV;
}

BX.CTimeManReportForm.prototype.GetTitle = function()
{
	var
		title = BX.create('DIV', {
			props: {className: 'tm-report-popup-titlebar'},
			html: '<div class="tm-report-popup-title"><span>'+BX.util.htmlspecialchars(BX.message('JS_CORE_TMR_TITLE'))+'</span></div><span class="tm-report-popup-title-date">'+BX.util.htmlspecialchars(this.data.INFO.DATE_TEXT)+'</span>'
		});

	if (this.mode != 'edit')
	{
		var
			tz_emp = parseInt(this.data.INFO.INFO.TIME_OFFSET)+parseInt(BX.message('SERVER_TZ_OFFSET')),
			tz_self = parseInt(BX.message('USER_TZ_OFFSET'))+parseInt(BX.message('SERVER_TZ_OFFSET'));

		title.firstChild.appendChild(BX.create('SPAN', {
			props: {className: 'tm-report-popup-title-additional'},
			events: {
				mouseover: BX.delegate(function() {
					BX.hint(BX.proxy_context, '<div class="tm-report-popup-titlebar-hint">' + BX.message('JS_CORE_TMR_TITLE_HINT').replace('#IP_OPEN#', this.data.INFO.INFO.IP_OPEN).replace('#IP_CLOSE#', this.data.INFO.INFO.IP_CLOSE||'N/A').replace('#TIME_OFFSET#', (tz_emp > 0 ? '+' : '-')+BX.timeman.formatTime(Math.abs(tz_emp), false, true)).replace('#TIME_OFFSET_SELF#', (tz_self > 0 ? '+' : '-')+BX.timeman.formatTime(Math.abs(tz_self), false, true))+'</div>');
				}, this)
			}
		}));
	}

	return title;
}

BX.CTimeManReportForm.prototype.GetButtons = function()
{
	var b = [];
	if (this.mode == 'edit')
	{
		b.push(
			new BX.PopupWindowButton({
				text : BX.message('JS_CORE_TM_CLOSE'),
				className : "popup-window-button-decline",
				events : {click: this.ACTIONS[this.mode]}
			})
		);
	}
	else
	{
		if (this.data.INFO.CAN_EDIT == 'Y' || this.data.CAN_EDIT == 'Y')
		{
			this.SAVEBUTTON = new BX.PopupWindowButton({
				text : this.data.INFO.INFO.ACTIVE == 'Y' ? BX.message('JS_CORE_TM_B_SAVE') : BX.message('JS_CORE_TM_CONFIRM'),
				className : this.data.INFO.INFO.ACTIVE == 'Y' ? "" : "popup-window-button-accept",
				events: {click: this.ACTIONS[this.mode]}
			});
			b.push(this.SAVEBUTTON);
		}
	}

	b.push(new BX.PopupWindowButtonLink({
		text : BX.message('JS_CORE_TM_B_CLOSE'),
		className : "popup-window-button-link-cancel",
		events : {click : function(e) {this.popupWindow.close();return BX.PreventDefault(e);}}
	}));

	return b;
}

BX.CTimeManReportForm.prototype.CheckReport = function()
{
	if (this.data.REPORT_REQ == 'Y')
	{
		var r = this.REPORT_TEXT.value.replace(/\s/g, '');
		if (r.length <= 0)
			return false;

		if (this.data.REPORT_TPL && this.data.REPORT_TPL.length > 0)
		{
			for (var i=0; i < this.data.REPORT_TPL.length; i++)
			{
				if (r == this.data.REPORT_TPL[i].replace(/\s/g, ''))
					return false;
			}
		}
	}

	return true;
}

BX.CTimeManReportForm.prototype.ActionEdit = function(e)
{
	if (this.data.INFO.STATE === 'EXPIRED' && !this.bTimeEdited)
	{
		this.TimeEdit();
	}
	else if (!this.CheckReport())
	{
		BX.addClass(this.REPORT_TEXT, 'bx-tm-popup-report-error');
		this.REPORT_TEXT.focus();
		this.REPORT_TEXT.onkeypress = function() {BX.removeClass(this, 'bx-tm-popup-report-error'); this.onkeypress = null;};
	}
	else
	{
		var i, l, data = {
			REPORT: BX.util.trim(this.REPORT_TEXT.value),
			ready: 'Y'
		}

		data.TASKS = []; data.TASKS_TIME = [];
		if (this.listInputTasks)
		{
			for (i = 0, l = this.listInputTasks.length; i < l; i++)
			{
				if (this.listInputTasks[i].checked)
				{
					data.TASKS.push(this.listInputTasks[i].value);
					data.TASKS_TIME.push(this.listInputTasksTime[i].value);
				}
			}
		}

		if (this.bTimeEdited)
		{
			if (this.POPUP_TIME.arInputs[this.POPUP_TIME.CLOCK_ID].value != this.POPUP_TIME.arInputs[this.POPUP_TIME.CLOCK_ID].BXORIGINALVALUE)
			{
				data[this.POPUP_TIME.CLOCK_ID] = BX.timeman.unFormatTime(this.POPUP_TIME.arInputs[this.POPUP_TIME.CLOCK_ID].value);
			}

			if (this.POPUP_TIME.arInputs[this.POPUP_TIME.CLOCK_ID_1].value != this.POPUP_TIME.arInputs[this.POPUP_TIME.CLOCK_ID_1].BXORIGINALVALUE || this.data.INFO.STATE === 'EXPIRED')
			{
				data[this.POPUP_TIME.CLOCK_ID_1] = BX.timeman.unFormatTime(this.POPUP_TIME.arInputs[this.POPUP_TIME.CLOCK_ID_1].value);
			}

			if (this.POPUP_TIME.INPUT_TIME_LEAKS.value != this.POPUP_TIME.INPUT_TIME_LEAKS.BXORIGINALVALUE)
			{
				data['TIME_LEAKS'] = BX.timeman.unFormatTime(this.POPUP_TIME.INPUT_TIME_LEAKS.value);
			}

			data.report = this.data.REPORTS.DURATION && this.data.REPORTS.DURATION[0] ? this.data.REPORTS.DURATION[0].REPORT : '';
		}
		else if (this.external_finish_ts)
		{
			data.timeman_edit_to = this.external_finish_ts
			data.report = this.external_finish_ts_report;
		}
		if (window.BXTIMEMAN && window.BXTIMEMAN.WND && (window.BXTIMEMAN.WND.startUserDate !== undefined))
		{
			data.startUserDate = window.BXTIMEMAN.WND.startUserDate;
		}
		if (window.BXTIMEMAN && window.BXTIMEMAN.WND && (window.BXTIMEMAN.WND.endUserDate !== undefined))
		{
			data.endUserDate = window.BXTIMEMAN.WND.endUserDate;
		}
		this.parent.Query('close', data, BX.proxy(this._ActionEdit, this));
	}

	return BX.PreventDefault(e);
}

BX.CTimeManReportForm.prototype._ActionEdit = function()
{
	this.popup.close();
	this.parent._Update.apply(this.parent, arguments);
}


BX.CTimeManReportForm.prototype.ActionAdmin = function()
{
	if (this.bTimeEdited || this.data.INFO.INFO.ACTIVE != 'Y')
	{
		var data = {
			ID: this.data.INFO.INFO.ID
		};

		if (this.bTimeEdited)
		{
			data.INFO = {
				TIME_START: this.data.INFO.INFO.TIME_START,
				TIME_FINISH: this.data.INFO.INFO.TIME_FINISH,
				TIME_LEAKS: this.data.INFO.INFO.TIME_LEAKS
			};
		}

		BX.timeman_query('admin_save', data, BX.proxy(this._ActionAdmin, this));
	}
	else
	{
		this.popup.close();
	}
}

BX.CTimeManReportForm.prototype._ActionAdmin = function(data)
{
	//TODO: we should update report cell here

	this.data = data;

	var tmp_data = BX.clone(this.data.INFO.INFO, true);
	tmp_data.ACTIVE = tmp_data.ACTIVE == 'Y';
	tmp_data.PAUSED = tmp_data.PAUSED == 'Y';
	tmp_data.ACTIVATED = tmp_data.ACTIVATED == 'Y';
	tmp_data.CAN_EDIT = (this.data.INFO.CAN_EDIT == 'Y' || this.data.CAN_EDIT == 'Y') ? 'Y' : 'N';

	BX.onCustomEvent(this, 'onEntryNeedReload', [tmp_data]);
	//this.params.parent_object.Reset(tmp_data);

	this.bTimeEdited = false;
}

BX.CTimeManReportForm.prototype.TimeEdit = function()
{
	if (this.POPUP_TIME)
	{
		this.POPUP_TIME.Clear();
		this.POPUP_TIME = null;
	}

	var tmp_data = {
		ID: this.data.INFO.INFO.ID,
		INFO: {
			CAN_EDIT: /*this.mode == 'edit' || */this.data.INFO.CAN_EDIT == 'Y' || this.data.CAN_EDIT == 'Y' ? 'Y' : 'N',
			DATE_START: this.data.INFO.INFO.DATE_START,
			TIME_START: this.data.INFO.INFO.TIME_START,
			TIME_FINISH: this.data.INFO.INFO.TIME_FINISH || this.data.INFO.EXPIRED_DATE,
			TIME_LEAKS: this.data.INFO.INFO.TIME_LEAKS,
			DURATION: this.data.INFO.INFO.DURATION
		}
	};

	if (!tmp_data.INFO.TIME_FINISH)
	{
		var q = new Date();
		tmp_data.INFO.TIME_FINISH = q.getHours()*3600 + q.getMinutes() * 60 - (this.params.parent_object ? this.params.parent_object.timezone_diff/1000 : 0);
	}

	this.POPUP_TIME = new BX.CTimeManEditPopup(this.parent, {
		node: this.TIME_EDIT_BUTTON || this.node,
		bind: this.TIME_EDIT_BUTTON || this.node,
		entry: tmp_data,
		mode: this.mode
	});

	this.POPUP_TIME.params.popup_buttons = [
		(this.POPUP_TIME.SAVEBUTTON = new BX.PopupWindowButton({
			text : BX.message('JS_CORE_TM_B_SAVE'),
			className : "popup-window-button-create",
			events : {click : BX.proxy(this.setEditValue, this)}
		})),
		new BX.PopupWindowButtonLink({
			text : BX.message('JS_CORE_TM_B_CLOSE'),
			className : "popup-window-button-link-cancel",
			events : {click : BX.proxy(this.POPUP_TIME.closeWnd, this.POPUP_TIME)}
		})
	];
	this.POPUP_TIME.WND.setButtons(this.POPUP_TIME.params.popup_buttons);

	this.POPUP_TIME._SetSaveButton = this.POPUP_TIME.SetSaveButton;
	this.POPUP_TIME.SetSaveButton = BX.DoNothing;
	this.POPUP_TIME.restoreButtons();
	this.POPUP_TIME.Show();
}

BX.CTimeManReportForm.prototype.setEditValue = function(e)
{
	var v, r = this.mode == 'edit' ? BX.util.trim(this.POPUP_TIME.REPORT.value) : BX.message('JS_CORE_TMR_ADMIN');
	if (r.length <= 0)
	{
		this.POPUP_TIME.REPORT.className = 'bx-tm-popup-clock-wnd-report-error';
		this.POPUP_TIME.REPORT.focus();
		this.POPUP_TIME.REPORT.onkeypress = function() {this.className = '';this.onkeypress = null;};
	}
	else
	{
		if (this.data.INFO.CAN_EDIT == 'Y' || this.data.CAN_EDIT == 'Y')
		{
			this.data.INFO.INFO.TIME_START = BX.timeman.unFormatTime(this.POPUP_TIME.arInputs.timeman_edit_from.value);
			this.data.INFO.INFO.TIME_LEAKS = BX.timeman.unFormatTime(this.POPUP_TIME.INPUT_TIME_LEAKS.value);
		}

		if (this.data.INFO.CAN_EDIT == 'Y' || this.data.CAN_EDIT == 'Y' || this.data.INFO.STATE == 'EXPIRED')
		{
			this.data.INFO.INFO.TIME_FINISH = BX.timeman.unFormatTime(this.POPUP_TIME.arInputs.timeman_edit_to.value);
		}

		if (this.data.INFO.STATE == 'EXPIRED')
		{
			this.data.INFO.EXPIRED_DATE = this.data.INFO.INFO.TIME_FINISH;
		}

		this.data.INFO.INFO.DURATION = this.data.INFO.INFO.TIME_FINISH - this.data.INFO.INFO.TIME_FINISH - this.data.INFO.INFO.TIME_LEAKS;

		var now = new Date();

		if (!this.data.REPORTS.DURATION)
			this.data.REPORTS.DURATION = [];

		this.data.REPORTS.DURATION[0] = {
			ACTIVE: true,
			REPORT: BX.util.htmlspecialchars(r),
			TIME: now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds(),
			DATE_TIME: parseInt(now.valueOf() / 1000)
		};

		this.POPUP_TIME.WND.close();

		this.bTimeEdited = true;
		this.Show(this.data);

		if (this.mode == 'admin')
		{
			this.SAVEBUTTON.setClassName('popup-window-button-accept');
		}
	}

	return BX.PreventDefault(e)
}

BX.CTimeManReportForm.prototype.ShowTpls = function(e)
{
	if (!this.TPLWND)
	{
		var content = BX.create('DIV', {props: {className: 'bx-tm-report-tpl'}}), TPLWND;

		var rep = this.REPORT_TEXT;
		var handler = function() {rep.value = this.BXTEXT; TPLWND.close();};

		for (var i=0; i<this.data.REPORT_TPL.length; i++)
		{
			var text = BX.util.trim(this.data.REPORT_TPL[i]);
			content.appendChild(BX.create('SPAN', {
				props: {className: 'bx-tm-report-tpl-item',BXTEXT: text},
				events: {click: handler},
				text: text || BX.message('JS_CORE_EMPTYTPL')
			}));
		}

		TPLWND = this.TPLWND = BX.PopupWindowManager.create(
			'timeman_template_selector_' + Math.random(), BX.proxy_context,
			{
				autoHide: true,
				content: content
			}
		);
	}
	else
	{
		this.TPLWND.setBindElement(BX.proxy_context)
	}

	this.TPLWND.show();

	return BX.PreventDefault(e);
}

BX.CTimeManReportForm.prototype.Clear = function()
{
	this.bCleared = true;

	if (this.POPUP_TIME)
	{
		this.POPUP_TIME.WND.close();
		this.POPUP_TIME.WND.destroy();
		this.POPUP_TIME.Clear();
		this.POPUP_TIME = null;
	}

	this.popup.close();
	this.popup.destroy();
}

/****view********************************************************************/

BX.JSTimeManReportFullForm = function(userdata, slider)
{

	this.popupform = null;
	this.slider = slider;
	this.report_data = userdata;
	this.cell = false;
	this.data = null;
	this.empty_slider = "Y";
	if (this.popupform == null)
	{
		this.popupform = new BX.PopupWindow(
				"popup_report_"+Math.random(),
				null,
				{
					autoHide : false,
					closeIcon: { right: "12px", top: "10px"},
					draggable:false,
					titleBar:true,
					closeByEsc:true,
					bindOnResize:false,
					toFrontOnShow: false
				}
			);
		//closebyEsc disable fix for task-popup
		try
		{
			BX.addCustomEvent(taskIFramePopup, 'onBeforeShow', BX.proxy(function(){
				this.popupform.setClosingByEsc(false);
				if(this.slider){
					this.slider.nextReportLink.style.visibility = "hidden";
					this.slider.prevReportLink.style.visibility = "hidden";
					this.slider.closeLink.style.visibility = "hidden";
				}
			},this));
			BX.addCustomEvent(taskIFramePopup, 'onBeforeHide', BX.proxy(function(){
				this.popupform.setClosingByEsc(true);
				if(this.slider){
					this.slider.nextReportLink.style.visibility = "visible";
					this.slider.prevReportLink.style.visibility = "visible";
					this.slider.closeLink.style.visibility = "visible";
				}
			},this));
		}catch(e){}
	}
	BX.bind(this.cell, 'click', BX.proxy(this.Click, this));
}

BX.JSTimeManReportFullForm.prototype.setData = function(data)
{
	this.data = data;
	if(this.data.REPORT_LIST.length>0 && this.empty_slider == "Y")
	{
		this.empty_slider = "N";
		this.report_list = this.data.REPORT_LIST;
	}
	this.GetContent();
	/*if (this.popupform.isShown() != true)*/
		this.Show();

}

BX.JSTimeManReportFullForm.prototype.Click = function()
{
		BX.timeman.showWait(this.popupform.popupContainer,0);
		BX.timeman_query('admin_report_full', {
			report_id: this.report_data.ID,
			user_id: this.report_data.USER_ID,
			empty_slider:this.empty_slider
		},BX.proxy(this.setData,this));
}
BX.JSTimeManReportFullForm.prototype.Show = function(data)
{
	this.popupform.show();
}

BX.JSTimeManReportFullForm.prototype.Clear = function()
{
	this.bCleared = true;
	if (this.popupform)
	{
		this.popupform.close();
		this.popupform.destroy();
	}
}

BX.JSTimeManReportFullForm.prototype.EditMode = function()
{
	this.TABCONTROL.SwitchEdit();
	this.EditLink.style.display = "none";
	this.SaveLink.style.display = "inline-block";
	this.EditLink.click = BX.proxy(this.SaveReportText,this);
	this.slider.FixOverlay();
}

BX.JSTimeManReportFullForm.prototype.SaveReportText = function()
{
	var data = {
		report_id: this.data.INFO.ID,
		user_id:this.data.INFO.USER_ID,
		report_text:this.TABCONTROL.GetTabContent('report_text_'+this.data.INFO.USER_ID),
		plan_text:this.TABCONTROL.GetTabContent('plan_text_'+this.data.INFO.USER_ID),
		edit_report:"Y"
	};
	BX.timeman.showWait(this.popupform.popupContainer,0);
	BX.timeman_query('user_report_edit',data,BX.proxy(this.UpdateReportArea,this));
}

BX.JSTimeManReportFullForm.prototype.UpdateReportArea = function(data)
{
	if (data.success == true)
	{
		this.TABCONTROL.SwitchView();
		this.SaveLink.style.display = "none";
		this.EditLink.style.display = "inline-block";
	}
	this.slider.FixOverlay();
}


BX.JSTimeManReportFullForm.prototype.GetContent = function()
{
	var report_value = '', tasks_unchecked = {}, tasks_time = {};
	if(!this.WND)
		this.WND = new BX.CTimeManWindow(this.popupform);
	for (var key in this.WND.EVENTWND) {
		var val = this.WND.EVENTWND[key];
		val.Clear();
	}
	var title = BX.create('DIV', {
		props: {className: 'tm-report-popup-titlebar'},
		children: [
			BX.create('DIV', {
				props: {className: 'tm-report-popup-title'},
				text: BX.message('JS_CORE_TMR_REPORT_WEEKLY')
			}),
			BX.create('SPAN', {
				props: {className: 'tm-report-popup-title-date'},
				text: this.data.INFO.TEXT_TITLE
			})
		]
	});

	this.fileForm = false;
	if(this.data.INFO.CAN_EDIT_TEXT == "Y" || (this.data.INFO.FILES && this.data.INFO.FILES.length>0))
	{
		this.fileForm = new BX.CTimeManUploadForm({
			id:this.data.INFO.ID,
			user_id: this.data.INFO.USER_ID,
			files_list:this.data.INFO.FILES,
			mode:(this.data.INFO.CAN_EDIT_TEXT == "Y"?"edit":"view")
		});
			BX.addCustomEvent(this.fileForm, "OnUploadFormRefresh", BX.proxy(function(){
				this.slider.FixOverlay();
			},this));
	}

	var content = BX.create("DIV",{
		props:{className:'tm-report-popup ui-form tm-report-popup-read-mode'+(this.ie7 ? ' tm-report-popup-ie7' : '')},
		children:
		[
			this.GetPeople(),
			this.GetReportRow(),
			((this.fileForm)?this.fileForm.GetUploadForm():null)
		]
	}
	);
	if(this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0 || this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0)
	{

		var tr = content.appendChild(BX.create('TABLE', {
			props: {className: 'tm-report-popup-items'},
			attrs: {cellSpacing: '0'},
			children: [BX.create('TBODY')]
		})).tBodies[0].insertRow(-1);

		if (this.data.INFO.TASKS_ENABLED && (this.mode == 'edit' || this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0))
			this.GetTasks(tr.insertCell(-1), tasks_unchecked, tasks_time);
		if (this.data.INFO.CALENDAR_ENABLED && (this.mode == 'edit' || this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0))
			this.GetEvents(tr.insertCell(-1));
	}
	this.selectMark = this.data.INFO.MARK;

	BX.onCustomEvent(this, 'onWorkReportMarkChange', [this.data]);
	if (this.data.INFO.CAN_EDIT)
	{
		this.mark_div = BX.create('DIV', {
			props: {className: "tm-popup-estimate-popup-center"},
			children:[
						(this.markg = BX.create('DIV',{
							props:{className:'tm-popup-estimate-but tm-but-plus'+((this.selectMark == "G")?' tm-but-active':'')},
							html:'<span class="tm-popup-estimate-but-l"></span><span class="tm-popup-estimate-but-c"><span class="tm-popup-estimate-but-icon"></span><span class="tm-popup-estimate-but-text">'+BX.message("JS_CORE_TMR_MARK_G_W")+'</span></span><span class="tm-popup-estimate-but-r"></span>',
							events:{'click':BX.proxy(function(){this.ChangeMark("G")},this)}
						})),
						(this.markb = BX.create('DIV',{
							props:{className:'tm-popup-estimate-but tm-but-minus'+((this.selectMark == "B")?' tm-but-active':'')},
							html:'<span class="tm-popup-estimate-but-l"></span><span class="tm-popup-estimate-but-c"><span class="tm-popup-estimate-but-icon"></span><span class="tm-popup-estimate-but-text">'+BX.message("JS_CORE_TMR_MARK_B_W")+'</span></span><span class="tm-popup-estimate-but-r"></span>',
							events:{'click':BX.proxy(function(){this.ChangeMark("B")},this)}
						})),
						(this.markn = BX.create('DIV',{
							props:{className:'tm-popup-estimate-but tm-but-not-rated'+((this.selectMark == "N")?' tm-but-active':'')},
							html:'<span class="tm-popup-estimate-but-l"></span><span class="tm-popup-estimate-but-c"><span class="tm-popup-estimate-but-icon"></span><span class="tm-popup-estimate-but-text">'+BX.message("JS_CORE_TMR_MARK_N_W")+'</span></span><span class="tm-popup-estimate-but-r"></span>',
							events:{'click':BX.proxy(function(){this.ChangeMark("N")},this)}
						})),
						(this.markx = BX.create('DIV',{
							props:{className:'tm-popup-estimate-but tm-but-notconfirm'+((this.selectMark == "X")?' tm-but-active':'')},
							html:'<span class="tm-popup-estimate-but-l"></span><span class="tm-popup-estimate-but-c"><span class="tm-popup-estimate-but-icon"></span><span class="tm-popup-estimate-but-text">'+BX.message("JS_CORE_TMR_MARK_X")+'</span></span><span class="tm-popup-estimate-but-r"></span>',
							events:{'click':BX.proxy(function(){this.ChangeMark("X")},this)}
						}))
			]
		});
	}
	else
	{
		this.mark_div = BX.create('DIV', {
			props: {className: "tm-popup-estimate-popup-center"},
			children:[
				BX.create("DIV",{
					props:{className:"mark-clean report-mark-"+this.selectMark +"-clean"},
					html:BX.message("JS_CORE_TMR_MARK_"+this.selectMark)

				}
				)
			]
			}
		);

	}
		var approve_info = "";
		approve_info = "<div class=\"tm-not-approve\">"+BX.message('JS_CORE_TMR_NOT_ACCEPT')+"</div>";
		if (this.data.INFO.APPROVER<=0 && !this.data.INFO.CAN_EDIT)
		{
			this.info = BX.create('DIV', {
							props:{className:"tm-popup-estimate-right"},
							style:{width:"100%"},
							html:approve_info
						});
			this.mark_area = BX.create('DIV', {
				props:{className:"tm-popup-estimate-item"},
				children:[
					'<div class="tm-popup-section-title-line"></div>',
					this.info

				]
			});
		}
		else
		{
			if (this.data.INFO.APPROVER>0)
			{
				approve_info= "<span class=\'tm-popup-est-right-item tm-popup-item-name\'>"+BX.message('JS_CORE_TMR_REPORT_APPROVER')+":</span><span class='tm-popup-est-right-item'>"+"<a href=\""+this.data.INFO.APPROVER_INFO.URL+"\">"+this.data.INFO.APPROVER_INFO.NAME+"</a>"+"</span>";
				approve_info+="<span class=\'tm-popup-est-right-item tm-popup-item-name\'>"+BX.message('JS_CORE_TMR_ACCEPT_DATE')+":</span><span class='tm-popup-est-right-item'>"+this.data.INFO.APPROVE_DATE+"</span>";
			}

			this.info = BX.create('DIV', {
							props:{className:"tm-popup-estimate-right"},
							html:approve_info
						});
			this.mark_area = BX.create('DIV', {
				props:{className:"tm-popup-estimate-item"},
				children:[
					'<div class="tm-popup-section-title-line"></div>',
					BX.create("DIV",{
						props:{className:"tm-popup-estimate-popup-left"},
						children:[
							'<div class="tm-popup-estimate-cont">'+((this.data.INFO.CAN_EDIT)?BX.message("JS_CORE_TMR_APPROVING_REPORT"):BX.message("JS_CORE_TMR_MARK"))+'</div>'
						]
					}
					),
					this.mark_div,
					this.info

				]
			});
		}
		this.enterHandler = function(e) {if(((e.keyCode == 0xA)||(e.keyCode == 0xD)) && e.ctrlKey == true) this.AddComment();}
		this.comment_form = BX.create("DIV",{
			props:{className:"tm-comment-link-div"},
			children:[
				this.comment_link_span = BX.create("SPAN",{
					props:{className:"tm-item-comments-add"},
					children:[
						BX.create("A",{
							attrs:{href:"javascript:void(0)"},
							html:BX.message("JS_CORE_TMR_ADD_COMMENT"),
							events:{"click":
								BX.proxy(
									function()
									{
										this.comment_area_edit.style.display = "block";
										this.comment_link_span.style.display = "none";
										this.slider.FixOverlay();
									},
									this
								)
							}
						})
					]

				}),
				(this.comment_area_edit = BX.create("DIV",{
					style:{display:"none"},
					children:[
						(this.comment_text = BX.create("TEXTAREA",{
							props:{className:"tm_comment_text"},
							attrs:{cols:35,rows:4},
							events:{keypress:BX.proxy(this.enterHandler,this)}
						})),
						BX.create("DIV",{
							children:[
								BX.create("INPUT",{
									attrs:{type:"button",value:BX.message("JS_CORE_TMR_SEND_COMMENT")},
									events:{"click":BX.proxy(this.AddComment,this)}
								})
						]})
					]
				}))
			]
		});
		content.appendChild(
				BX.create('DIV', {
				children:[
					((this.mark_area)?this.mark_area:null),
					BX.create("DIV",{
						props:{className:"tm-popup-section-title-line"}
					}),
					(this.comments_div = BX.create("DIV",{
						style:{marginTop:"6px"},
						html:this.data.COMMENTS

					})),
					this.comment_form
				]
			})
		);
	this.popupform.setContent(content);
	this.popupform.setTitleBar({content :title});
	BX.timeman.closeWait();
}

BX.JSTimeManReportFullForm.prototype.GetEvents = function(tdEvents)
{
	tdEvents.className = 'tm-report-popup-events';
		tdEvents.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-section-title'},
			html: '<div class="tm-popup-section-title-text">' + BX.message('JS_CORE_TM_EVENTS') + '</div>\
	<div class="tm-popup-section-title-line"></div>'
		}));

		if (this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0)
		{
			this.listEvents = null;
			tdEvents.appendChild(BX.create('DIV', {
				props: {className: 'tm-popup-events'},
				children: [
					(this.listEvents = BX.create('DIV', {props: {className: 'tm-popup-event-list'}}))
				]
			}));

			for (var i=0;i<this.data.INFO.EVENTS.length;i++)
			{
				this.listEvents.appendChild(
					this.WND.CreateEvent(this.data.INFO.EVENTS[i], {
						BXPOPUPBIND: tdEvents.firstChild,
						BXPOPUPANGLEOFFSET: 44
						},true)
					)
			}
		}
}

BX.JSTimeManReportFullForm.prototype.GetPeople = function()
{
	return BX.create('DIV', {
		props: {className: 'tm-report-popup-people'},
		html: '<div class="tm-report-popup-r1"></div><div class="tm-report-popup-r0"></div>\
<div class="tm-report-popup-people-inner">\
	<div class="tm-report-popup-user tm-report-popup-employee">\
		<span class="tm-report-popup-user-label">' + BX.message('JS_CORE_TMR_FROM') + ':</span><a href="' + this.data.FROM.URL + '" class="tm-report-popup-user-avatar"' + (this.data.FROM.PHOTO ? ' style="background: url(\'' + encodeURI(this.data.FROM.PHOTO) + '\') no-repeat scroll center center transparent; background-size: cover;"' : '') + '></a><span class="tm-report-popup-user-info"><a href="' + this.data.FROM.URL + '" class="tm-report-popup-user-name">' + this.data.FROM.NAME + '</a><span class="tm-report-popup-user-position">' + (BX.util.htmlspecialchars(this.data.FROM.WORK_POSITION || '') || '&nbsp;') + '</span></span>\
	</div>\
	<div class="tm-report-popup-user tm-report-popup-director">\
		<span class="tm-report-popup-user-label">' + BX.message('JS_CORE_TMR_TO') + ':</span><a href="' + this.data.TO[0].URL + '" class="tm-report-popup-user-avatar"' + (this.data.TO[0].PHOTO ? ' style="background: url(\'' + encodeURI(this.data.TO[0].PHOTO) + '\') no-repeat scroll center center transparent; background-size: cover;"' : '') + '></a><span class="tm-report-popup-user-info"><a href="' + this.data.TO[0].URL + '" class="tm-report-popup-user-name">' + this.data.TO[0].NAME + '</a><span class="tm-report-popup-user-position">' + (BX.util.htmlspecialchars(this.data.TO[0].WORK_POSITION || '') || '&nbsp;') + '</span></span>\
	</div>\
</div>\
<div class="tm-report-popup-r0"></div><div class="tm-report-popup-r1"></div>'
	});
}

BX.JSTimeManReportFullForm.prototype.GetReportRow = function()
{
	this.TABCONTROL = new BX.CTimeManTabEditorControl({
			lhename:"obReportForm"+this.data.INFO.USER_ID,
			parent:this
		}
	);
	this.TABCONTROL.addTab({
		ID:"report_text_"+this.data.INFO.USER_ID,
		TITLE:BX.message('JS_CORE_TMR_REPORT'),
		CONTENT:((this.data.INFO.REPORT_STRIP_TAGS.length>0)?this.data.INFO.REPORT:'<i data-placeholder="1" style="color:#999">'+BX.message('JS_CORE_TMR_REPORT_EMPTY'))+'</i>'
	});
	this.TABCONTROL.addTab({
		ID:"plan_text_"+this.data.INFO.USER_ID,
		TITLE:BX.message('JS_CORE_TMR_PLAN'),
		CONTENT: ((this.data.INFO.PLAN_STRIP_TAGS.length > 0) ? this.data.INFO.PLANS : '<i data-placeholder="1" style="color:#999">' + BX.message('JS_CORE_TMR_PLAN_EMPTY')) + '</i>'
	});
	return  BX.create('DIV', {
					props: {className: 'tm-report-popup-desc'},
					children:[
						BX.create("DIV",{
								props:{className:"tm-report-popup-desc-text-view"},
								children:[
									((this.data.INFO.CAN_EDIT_TEXT=="Y")?
										BX.create("SPAN",{
											props:{className:"tm-link-div"},
											children:[
												this.EditLink = BX.create("A",{
														props:{className:"tm-edit-link"},
														text:BX.message("JS_CORE_TMR_PARENT_EDIT"),
														events:{"click":BX.proxy(this.EditMode,this)}
													}),
												this.SaveLink = BX.create("DIV",{
														props:{className:"tm-ag-buttons-save"},
														style:{display:"none"},
														children:[
															BX.create("SPAN",{
																props:{className:"tm-ag-buttons-left"}
															}),
															BX.create("SPAN",{
																props:{className:"tm-ag-buttons-cont"},
																children:[
																	BX.create("SPAN",{
																		props:{className:"tm-ag-buttons-icon"}
																	}),
																	"<span>"+BX.message("JS_CORE_TM_B_SAVE")+"</span>"
																]
															}),
															BX.create("SPAN",{
																props:{className:"tm-ag-buttons-right"}
															})
														],
														events:{"click":BX.proxy(this.SaveReportText,this)}
													})
											]

											}):null),
											this.TABCONTROL.div

								]
							})

					]
				});
}

BX.JSTimeManReportFullForm.prototype.GetTasks = function(tdTasks, tasks_unchecked, tasks_time)
{
	tdTasks.className = 'tm-report-popup-tasks';
	tdTasks.appendChild(BX.create('DIV', {
		props: {className: 'tm-popup-section-title'},
		html: '<div class="tm-popup-section-title-text">' + BX.message('JS_CORE_TM_TASKS') + '</div>'
		+ (false && this.mode == 'edit' ? '<div class="tm-popup-section-title-link">' + BX.message('JS_CORE_TM_TASKS_CHOOSE') + '</div>' : '') +
		'<div class="tm-popup-section-title-line"></div>'
	}));
	if (this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0)
	{
		this.listTasks = null;
		tdTasks.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-tasks' + (this.data.INFO.TASKS.length > 10 ? ' tm-popup-tasks-tens' : '')},
			children: [
				(this.listTasks = BX.create('OL', {props: {className: 'tm-popup-task-list'}}))
			]
		}));

		this.listInputTasks = []; this.listInputTasksTime = [];
		for (var i=0;i<this.data.INFO.TASKS.length;i++)
		{
			var inp, inpTime;

			if (typeof this.data.INFO.TASKS[i].TIME == 'undefined')
				this.data.INFO.TASKS[i].TIME = 0;

			var taskTime = typeof tasks_time[i] == 'undefined' ? this.data.INFO.TASKS[i].TIME : tasks_time[i];
			this.listTasks.appendChild(
				BX.create('LI', {
					props: {
						className: 'tm-popup-task tm-popup-task-status-' + BX.timeman.TASK_SUFFIXES[this.data.INFO.TASKS[i].STATUS],
						bx_task_id: this.data.INFO.TASKS[i].ID
					},
					children:
					[
						BX.create('SPAN', {
							props: {
								className: 'tm-popup-task-name'
							},
							text: this.data.INFO.TASKS[i].TITLE,
							events: {click: BX.proxy(this.WND.showTask, this)}
						}),
						BX.create('SPAN', {
							props: {
								className: 'tm-popup-task-time-admin'
							},
						text: BX.timeman.formatWorkTime(taskTime)
						})

						/*BX.create('SPAN', {
							props: {className: 'tm-popup-task-delete'},
							events: {click: BX.proxy(this.parent.WND.removeTask, this.parent.WND)}
						})*/
					]
				})
			);

			if (inp)
			{
				this.listInputTasks.push(inp);
				this.listInputTasksTime.push(inpTime);
			}
		}
	}
}


BX.JSTimeManReportFullForm.prototype.Approve = function()
{
	var data = {
		mark: this.selectMark,
		approve:"Y",
		user_id:this.data.FROM.ID,
		report_id:this.data.INFO.ID
	}
	BX.timeman.showWait(this.mark_div,0);
	BX.timeman_query("admin_report_full",data,BX.proxy(
	function(data){
		BX.onCustomEvent(this, 'onWorkReportMarkChange', [data]);
		this.setData(data);
	},this));
}

BX.JSTimeManReportFullForm.prototype.AddComment = function()
{
	if (this.comment_text.value.length<=0)
		return;
	var data = {
		comment_text:this.comment_text.value,
		report_owner:this.data.FROM.ID,
		report_id:this.data.INFO.ID,
		add_comment:"Y"

	};
	this.comments_div.style.minHeight = "50px";
	BX.timeman.showWait(this.comments_div,0);
	this.comment_area_edit.style.display = "none";
	this.comment_link_span.style.display = "block";
	this.comment_text.value = "";
	BX.timeman_query("add_comment_full_report",data,BX.proxy(this.RefreshComments,this));
}

BX.JSTimeManReportFullForm.prototype.RefreshComments = function(data)
{
	if(data.COMMENTS)
	{
		this.comments_div.innerHTML = data.COMMENTS;
		if (BX("report_comments_count_"+this.data.INFO.ID) && data.COMMENTS_COUNT>0)
		{
			var comments = BX("report_comments_count_"+this.data.INFO.ID);
			comments.style.display = "inline-block";
			comments.innerHTML = data.COMMENTS_COUNT;
		}
		this.slider.FixOverlay();
	}
}

/************************************************************************/
BX.bindFullReport = function(report_id,user_id)
{
	if (!window["report"+report_id])
		window["report"+report_id] = new BX.JSTimeManReportFullForm({ID:report_id,USER_ID:user_id});
	window["report"+report_id].Click();
}

/************************************************************************/

BX.JSTimeManReportFullForm.prototype.ChangeMark = function(mark)
{
	var report_mark = mark.toLowerCase();
	BX.removeClass(this.markb,"tm-but-active");
	BX.removeClass(this.markn,"tm-but-active");
	BX.removeClass(this.markx,"tm-but-active");
	BX.removeClass(this.markg,"tm-but-active");
	BX.addClass(this["mark"+report_mark],"tm-but-active");
	if(this.selectMark!=mark)
	{
		this.selectMark = mark;
		this.Approve();
	}
}

/************************************************************************/
BX.StartSlider = function(user,start)
{
	BX.timeman.showWait(document.body,0);
	if (!window["report"+user])
	{
		window["report"+user] = new BX.ReportSlider(user,start);
	}

	window["report"+user].ShowReport(start);
}
BX.ReportSlider = function(user,start_report_id)
{
	this.cur_report = start_report_id || false;
	this.reports = {};
	this.user_id = user;
	this.popup = false;

	this.table = BX.create("TABLE",{
			props:{className:"report-popup-main-table"},
			children:[
				BX.create("TBODY",{
					children:[
						BX.create("TR",{
							children:[
								this.prev = BX.create("TD",{
									props:{className:"report-popup-prev-slide-wrap"},
									children:[
										this.prevReportLink = BX.create("A",{
											props:{className:"report-popup-prev-slide"},
											attrs:{href:"javascript: void(0)"},
											children:[
												BX.create("SPAN",{})
											],
											events:{"click":BX.proxy(this.PrevReport,this)}
										})
									]
								}),
								this.popup_place = BX.create("TD",{
									attrs:{valign:"top"},
									style:{paddingTop:"20px"},
									props:{className:"report-popup-main-block-wrap"}
								}),
								this.next = BX.create("TD",{
									props:{className:"report-popup-next-slide-wrap"},
									children:[
										this.closeLink = BX.create("A",{
											attrs:{href:"javascript: void(0)"},
											events:{"click":BX.proxy(this.PopupClose,this)},
											props:{className:"report-popup-close"},
											children:[
												BX.create("SPAN",{
													props:{className:"report-popup-close"}
													})
											]
										}),
										this.nextReportLink = BX.create("A",{
											attrs:{href:"javascript: void(0)"},
											props:{className:"report-popup-next-slide"},
											children:[
												BX.create("SPAN",{})
											],
											events:{"click":BX.proxy(this.NextReport,this)}
										})
									]

								})
							]

						})
					]

				})
			]
		}
	);
	this.overlay = BX.create("DIV",{
		props:{className:"report-fixed-overlay"},
		children:[
			(this.coreoverlay = BX.create("DIV",{
				props:{className:"bx-tm-dialog-overlay"}
				//attrs:{width:"100%",heigth:"100%"}
				}
			)),
			this.table
		]
	}
	);
	document.body.appendChild(this.overlay);

	BX.ZIndexManager.register(this.overlay);

	BX.bind(window.top, "resize", BX.proxy(this.FixOverlay, this));
};

BX.ReportSlider.prototype.ShowReport = function(report_id)
{
	this.cur_report = report_id;

	if(!this.report_form)
	{
		this.report_form = new BX.JSTimeManReportFullForm({ID:this.cur_report,USER_ID:this.user_id},this);
		BX.addCustomEvent(this.report_form.popupform, "onPopupClose", BX.proxy(function(){
			this.overlay.style.display = "none";
			//BX.removeClass(document.body, "report-body-overflow");
			}, this));
		BX.addCustomEvent(this.report_form.popupform, "onPopupShow", BX.proxy(function(){
			this.overlay.style.display = "block";
			BX.ZIndexManager.bringToFront(this.overlay);
			//BX.addClass(document.body, "report-body-overflow");
			}, this));
		BX.addCustomEvent(this.report_form.popupform, "onAfterPopupShow", BX.proxy(function(){
		this.report_form.popupform.popupContainer.style.position = "relative";
		this.report_form.popupform.popupContainer.style.left = "0px";
		this.FixOverlay();
		}, this));

		this.popup_place.appendChild(
			BX.create("DIV",{
			style:{display:"inline-block"},
			children:[
				BX(this.report_form.popupform.uniquePopupId)
			]
			}
			)

			);

	}

	this.report_form.report_data = {ID:this.cur_report,USER_ID:this.user_id};

	this.report_form.Click();

}
BX.ReportSlider.prototype.FixOverlay = function()
{
		this.report_form.popupform.popupContainer.style.position = "relative";
		this.report_form.popupform.popupContainer.style.left = "0px";
		var size = BX.GetWindowInnerSize();
		this.overlay.style.height = size.innerHeight + "px";
		this.overlay.style.width = size.innerWidth + "px";
		var scroll = BX.GetWindowScrollPos();

		if (BX.browser.IsIE() && !BX.browser.IsIE9())
		{
			this.table.style.width = (size.innerWidth - 20) + "px";
		}
		//this.table.style.height = this.overlay.style.height + "px";
		this.overlay.firstChild.style.height = Math.max(this.report_form.popupform.popupContainer.offsetHeight+50, this.overlay.clientHeight)+"px";
		this.overlay.firstChild.style.width = Math.max(1024, this.overlay.clientWidth) + "px";
		this.report_form.popupform.popupContainer.style.top = "0px";
		this.Recalc();
		this.__adjustControls();
}

BX.ReportSlider.prototype.__adjustControls = function(){
		/*if (this.lastAction != "view" || ((!this.currentList || this.currentList.length <= 1 || this.__indexOf(this.currentTaskId, this.currentList) == -1) && (this.tasksList.length <= 1 || this.__indexOf(this.currentTaskId, this.tasksList) == -1)))
		{
			this.nextReportLink.style.display = this.prevReportLink.style.display = "none";
		}
		else*/
		{
			if(!BX.browser.IsDoctype() && BX.browser.IsIE())
			{
				this.nextReportLink.style.height = this.prevReportLink.style.height = document.documentElement.offsetHeight + "px";
				this.prevReportLink.style.width = (this.prevReportLink.parentNode.clientWidth - 1) + 'px';
				this.nextReportLink.style.width = (this.nextReportLink.parentNode.clientWidth - 1) + 'px';
			}
			else
			{
				this.nextReportLink.style.height = this.prevReportLink.style.height = document.documentElement.clientHeight + "px";
				this.prevReportLink.style.width = this.prevReportLink.parentNode.clientWidth + 'px';
				this.nextReportLink.style.width = this.nextReportLink.parentNode.clientWidth + 'px';
			}
			this.prevReportLink.firstChild.style.left = (this.prevReportLink.parentNode.clientWidth * 4 / 10) + 'px';
			this.nextReportLink.firstChild.style.right = (this.nextReportLink.parentNode.clientWidth * 4 / 10) + 'px';

		}
		this.closeLink.style.width = this.closeLink.parentNode.clientWidth + 'px';
}

BX.ReportSlider.prototype.Recalc = function()
{
	if(!this.report_form || this.report_form.empty_slider != "N")
		return;
	var len = this.report_form.report_list.length;
	if (len == 1)
	{
		this.nextReportLink.style.display = "none";
		this.prevReportLink.style.display = "none";
	}
	else
	{
		for(i=0;i<len;i++)
		{
			if(this.report_form.report_list[i] == this.cur_report)
			{
				if (len == (i+1))
				{
					this.nextReportLink.style.display = "none";
					this.prevReportLink.style.display = "block";
				}
				else if(i==0)
				{
					this.nextReportLink.style.display = "block";
					this.prevReportLink.style.display = "none";
				}
				else
				{
					this.nextReportLink.style.display = "block";
					this.prevReportLink.style.display = "block";
				}
				break;
			}

		}
	}
};
BX.ReportSlider.prototype.NextReport = function()
{
	var nextreport = this.cur_report;
	for(i=0;i<this.report_form.report_list.length;i++)
	{
		if((this.report_form.report_list[i] == this.cur_report)
			&& ((i+1)!=this.report_form.report_list.length)
		)
		{
			nextreport = this.report_form.report_list[i+1];
			break;
		}
	}
	if(nextreport!=this.cur_report)
		this.ShowReport(nextreport)
	this.Recalc();
};
BX.ReportSlider.prototype.PrevReport = function()
{
	var prevreport = this.cur_report;
	for(i=0;i<this.report_form.report_list.length;i++)
	{
		if((this.report_form.report_list[i] == this.cur_report)
			&& i!=0)
		{
			prevreport = this.report_form.report_list[i-1];
			break;
		}
	}
	if(prevreport!=this.cur_report)
		this.ShowReport(prevreport)
	this.Recalc();
};

BX.ReportSlider.prototype.PopupClose = function()
{
	this.report_form.popupform.close();
}

BX.StartNotifySlider = function(user, start, type)
{
	BX.timeman.showWait(document.body, 0);
	if (!window["timeman_notify_"+user])
	{
		window["timeman_notify_"+user] = new BX.TimeManSlider(user,start,type);
	}
	else
	{
		window["timeman_notify_"+user].Load(start);
	}
}

BX.TimeManSlider = function(user,start,type)
{
	this.WND = null;

	this.user = user;
	this.type = type;

	this.Load(start);
	BX.addCustomEvent('onEntryNeedReload', BX.proxy(this.Reset, this));
}

BX.TimeManSlider.prototype.Load = function(ID)
{
	BX.timeman.closeWait();
	BX.SidePanel.Instance.open('/timeman/worktime/records/' + ID + '/report/', {width: 800, cacheable: false});
}

BX.TimeManSlider.prototype.Show = function(data)
{
	this.data = data;
	if (null == this.WND || !!this.WND.bCleared)
	{
		this.WND = null;

		this.WND = new BX.CTimeManReportForm(
			window.BXTIMEMAN || {}, // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			{
				node: document.body,
				mode: 'admin',
				data: data,
				offsetLeft: -100,
				parent_object: this,
				type: this.type
			}
		);

		this.ShowOverlay();

		BX.addCustomEvent(this.WND.popup, "onPopupClose", BX.proxy(function(){
			this.overlay.style.display = "none";
			//BX.removeClass(document.body, "report-body-overflow");
		}, this));
		BX.addCustomEvent(this.WND.popup, "onPopupShow", BX.proxy(function(){
			this.overlay.style.display = "block";
			BX.ZIndexManager.bringToFront(this.overlay);
			//BX.addClass(document.body, "report-body-overflow");
		}, this));

		this.WND.Show();
	}
	else
	{
		this.WND.Show(data);
		setTimeout(BX.proxy(this.FixOverlay, this),100);
	}

	if (this.data.NEIGHBOURS.NEXT > 0)
		BX.show(this.nextLink);
	else
		BX.hide(this.nextLink);

	if (this.data.NEIGHBOURS.PREV > 0)
		BX.show(this.prevLink);
	else
		BX.hide(this.prevLink);

	setTimeout(BX.proxy(this.FixOverlay, this),100);
}

// used
BX.TimeManSlider.prototype.Reset = function(data)
{
	this.Load(data.ID);
	// redraw LF entry here
}

BX.TimeManSlider.prototype.ShowOverlay = function()
{
	this.prevLink = BX.create("A",{
		props:{className:"timeman-popup-prev-slide"},
		attrs:{href:"javascript: void(0)"},
		children:[
			BX.create("SPAN")
		],
		events:{"click": BX.proxy(this.Prev, this)}
	});

	this.nextLink = BX.create("A", {
		props:{className:"timeman-popup-next-slide"},
		attrs:{href:"javascript: void(0)"},
		children:[
			BX.create("SPAN")
		],
		events:{"click": BX.proxy(this.Next,this)}
	});

	this.overlay = BX.create("DIV",{
		props:{className:"report-fixed-overlay"},
		children:[
			(this.coreoverlay = BX.create("DIV",{
				props:{className:"bx-tm-dialog-overlay"}
				//attrs:{width:"100%",heigth:"100%"}
				}
			)),

			this.nextLink, this.prevLink,

			(this.closeLink = BX.create("A",{
				attrs:{href:"javascript: void(0)"},
				events:{"click":BX.proxy(this.WND.popup.close,this.WND.popup)},
				props:{className:"timeman-popup-close"},
				children:[
					BX.create("SPAN")
				]
			}))
		]
	});
	document.body.appendChild(this.overlay);

	BX.ZIndexManager.register(this.overlay);

	BX.bind(top, "resize", BX.proxy(this.FixOverlay, this))
}

BX.TimeManSlider.prototype.Prev = function()
{
	if (this.data.NEIGHBOURS && this.data.NEIGHBOURS.PREV)
	{
		this.Load(this.data.NEIGHBOURS.PREV);
	}
	BX.proxy_context.blur();
}

BX.TimeManSlider.prototype.Next = function()
{
	if (this.data.NEIGHBOURS && this.data.NEIGHBOURS.NEXT)
	{
		this.Load(this.data.NEIGHBOURS.NEXT);
	}
	BX.proxy_context.blur();
}

BX.TimeManSlider.prototype.FixOverlay = function()
{
	var wnd_size = BX.GetWindowInnerSize();
	var popup_size = BX.pos(this.WND.popup.popupContainer);

	this.overlay.style.height = wnd_size.innerHeight + "px";
	this.overlay.style.width = wnd_size.innerWidth + "px";

	if(!!this.data.NEIGHBOURS)
	{
		if (this.data.NEIGHBOURS.NEXT > 0)
		{
			this.nextLink.firstChild.style.right = parseInt((wnd_size.innerWidth - popup_size.right) / 2) + 'px';
		}
		if (this.data.NEIGHBOURS.PREV > 0)
		{
			this.prevLink.firstChild.style.left = parseInt((popup_size.left) / 2) + 'px';
		}
	}
}
/**************************************************************************************************/

function _getStateHash(DATA, keys)
{
	var hash = '';
	if (DATA)
	{
		for (var i=0,l=keys.length;i<l;i++)
			hash += (i>0 ? '|' : '') + keys[i] + ':' + (DATA[keys[i]] ? DATA[keys[i]].valueOf() : 'null');
	}

	return hash;
}

function _createHR(bHtml, className)
{
	return bHtml ? '<div class="' + (className || 'popup-window-hr') + '"><i></i></div>' : BX.create('DIV', {
		props: {className: className || 'popup-window-hr'}, html: '<i></i>'
	});
}

function _worktime_timeman(h, m, s)
{
	var r = (
		(h > 0 ? h + BX.message('JS_CORE_H') + ' ' : '')
		+ (m > 0 ? m + BX.message('JS_CORE_M') : '')
	) || s + BX.message('JS_CORE_S');

	return '(' + BX.util.trim(r) + ')';
}

function _worktime_notice_timeman(h, m, s)
{
	return '<span class="tm-popup-notice-time-hours"><span class="tm-popup-notice-time-number">' + h + '</span><span class="tm-popup-notice-time-unit">' + BX.message('JS_CORE_H') + '</span></span><span class="tm-popup-notice-time-minutes"><span class="tm-popup-notice-time-number">' + m + '</span><span class="tm-popup-notice-time-unit">' + BX.message('JS_CORE_M') + '</span></span><span class="tm-popup-notice-time-seconds"><span class="tm-popup-notice-time-number">' + s + '</span><span class="tm-popup-notice-time-unit">' + BX.message('JS_CORE_S') + '</span></span>';
}

/*customize timer */
BX.timer.registerFormat('worktime_timeman', _worktime_timeman);
BX.timer.registerFormat('worktime_notice_timeman', _worktime_notice_timeman);

})();
