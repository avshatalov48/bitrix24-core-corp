;(function(){
	if (BX["MTimeMan"])
		return;
	var TMPoint = BX.message("SITE_DIR") + 'mobile/ajax.php?mobile_action=timeman',
		//TMPoint = '/bitrix/tools/timeman.php?',
		intervals = {
			OPENED: 60000,
			CLOSED: 30000,
			EXPIRED: 30000,
			START: 30000
		},
		SITE_ID = BX.message('SITE_ID'),
		_worktime_timeman = function(h, m, s) {
			m = m+'';
			s = s+'';
			return '<span>' + h + '</span>:<span>' + ("00".substring(0, 2 - m.length) + m) + '</span>:<span>' + ("00".substring(0, 2 - s.length) + s) + '</span>';
		},
		_initPage = function(menuItems){
			var title = BX.message('PAGE_TITLE');
			if (BX.type.isNotEmptyString(title))
			{
				if (BX.type.isArray(menuItems) && menuItems.length > 0)
				{
					var menu = new window.BXMobileApp.UI.Menu({
						items: menuItems
					});
					window.BXMobileApp.UI.Page.TopBar.title.setCallback(BX.delegate(menu.show, menu));
				}
				window.BXMobileApp.UI.Page.TopBar.title.setText(title);
				window.BXMobileApp.UI.Page.TopBar.title.show();
			}
		},
		initTimestamp = (function () {
			var d = function(node, container, format) {
				this.node = node;
				this.container = container;
				this.click = BX.delegate(this.click, this);
				this.callback = BX.delegate(this.callback, this);
				BX.bind(this.container, "click", this.click);
				BX.addCustomEvent(this.node, "onFormat", BX.proxy(function() {
					var value = parseInt(this.node.value);
					value = value > 0 ? value : 0;
					this.container.innerHTML = BX.date.format(BX.clone(this.format.visible), (value + this.offset));
				}, this));
				this.init(format);
			};
			d.prototype = {
				type : 'time',
				format : {
					inner : 'H:mm',
					visible : null
				},
				node : null,
				click : function(e) {
					BX.eventCancelBubble(e);
					this.show();
					return BX.PreventDefault(e);
				},
				show : function() {
					var res = {
						type: this.type,
						start_date: this.getStrDate(parseInt(this.node.value)),
						format: this.format.inner,
						callback: this.callback
					};

					if (res["start_date"] == "")
						delete res["start_date"];
					BXMobileApp.UI.DatePicker.setParams(res);
					BXMobileApp.UI.DatePicker.show();
				},
				callback : function(data) {
					this.node.value = this.makeTimestamp(data);
					BX.onCustomEvent(this.node, "onFormat", []);
					BX.onCustomEvent(this.node, "onChange", [this.node]);
				},
				makeTimestamp : function(str) {
					//Format: "hour:minute"
					var timestamp = 0;
					if (BX.type.isNotEmptyString(str))
					{
						var timeR = new RegExp("(\\d{1,2}):(\\d{1,2})"),
							m;
						if (timeR.test(str) && (m = timeR.exec(str)) && m)
						{
							timestamp = parseInt(m[1]) * 3600 + parseInt(m[2]) * 60;
						}
					}
					return timestamp;
				},
				getStrDate : function(value) {
					var d = new Date((parseInt(value)+this.offset) * 1000);
					return BX.util.str_pad_left(d.getHours().toString(), 2, "0") + ':' + d.getMinutes().toString();
				},
				init : function(formats) {
					var DATETIME_FORMAT = BX.date.convertBitrixFormat(BX.message("FORMAT_DATETIME")),
						DATE_FORMAT = BX.date.convertBitrixFormat(BX.message("FORMAT_DATE")),
						TIME_FORMAT;
					if ((DATETIME_FORMAT.substr(0, DATE_FORMAT.length) == DATE_FORMAT))
						TIME_FORMAT = BX.util.trim(DATETIME_FORMAT.substr(DATE_FORMAT.length));
					else
						TIME_FORMAT = BX.date.convertBitrixFormat(DATETIME_FORMAT.indexOf('T') >= 0 ? 'H:MI:SS T' : 'HH:MI:SS');

					this.format.bitrix = TIME_FORMAT;

					formats = (formats || {});
					this.format.visible = (formats["time"] || TIME_FORMAT.replace(':s', ''));
					this.date = new Date();
					this.offset = this.date.getTime() - this.date.getTime() % 86400 + this.date.getTimezoneOffset() * 60;
					BX.onCustomEvent(this.node, "onFormat", []);
				}
			};
			return d;
		})(),
		initTimePeriod = (function () {
			var d = function(node, container, editable) {
				this.node = node;
				this.container = container;
				this.click = BX.delegate(this.click, this);
				this.callback = BX.delegate(this.callback, this);
				if (editable)
					BX.bind(this.container, "click", this.click);
				BX.addCustomEvent(this.node, "onFormat", BX.proxy(function() {
					var value = (BX.type.isNotEmptyString(this.node.value) ? parseInt(this.node.value) : 0),
						h = parseInt(value / 3600) + '',
						m = parseInt((value % 3600) / 60) + '';
					this.container.innerHTML = '<span>' + h + '</span>:<span>' + ("00".substring(0, 2 - m.length) + m) + '</span>';
				}, this));
				this.init();
			};
			d.prototype = {
				type : 'time',
				format : {
					inner : 'H:mm',
					visible : null
				},
				node : null,
				click : function(e) {
					BX.eventCancelBubble(e);
					this.show();
					return BX.PreventDefault(e);
				},
				show : function() {
					var res = {
						type: this.type,
						start_date: this.getStrDate(parseInt(this.node.value)),
						format: this.format.inner,
						callback: this.callback
					};
					if (res["start_date"] == "")
						delete res["start_date"];
					BXMobileApp.UI.DatePicker.setParams(res);
					BXMobileApp.UI.DatePicker.show();
				},
				callback : function(data) {
					this.node.value = this.makeTimestamp(data);
					BX.onCustomEvent(this.node, "onFormat", []);
					BX.onCustomEvent(this.node, "onChange", [this.node]);
				},
				makeTimestamp : function(str) {
					//Format: "hour:minute"
					var timestamp = 0;
					if (BX.type.isNotEmptyString(str))
					{
						var timeR = new RegExp("(\\d{1,2}):(\\d{1,2})"),
							m;
						if (timeR.test(str) && (m = timeR.exec(str)) && m)
						{
							timestamp = parseInt(m[1]) * 3600 + parseInt(m[2]) * 60;
						}
					}
					return timestamp;
				},
				getStrDate : function(value) {
					value = parseInt(value);
					var h = parseInt(value / 3600),
						m = parseInt((value % 3600) / 60),
						p = "00";
					return h + ":" + (p.substring(0, 2 - m.length) + m);
				},
				init : function() {
					BX.onCustomEvent(this.node, "onFormat", []);
				}
			};
			return d;
		})(),
		location = null;
	BX.ready(function(){
		window.app.pullDown({
			enable:   true,
			pulltext: BX.message('PULLDOWN_PULL'),
			downtext: BX.message('PULLDOWN_DOWN'),
			loadtext: BX.message('PULLDOWN_LOADING'),
			action:   'RELOAD',
			callback: function(){ window.app.reload(); }
		});
		app.getCurrentLocation({
			onsuccess : function(l) {
				location = l;
			}
		});
		BXMobileApp.onCustomEvent('onPullExtendWatch', {id : "TIMEMANWORKINGDAY_" + BX.message("USER_ID")});
	});
	BX.timer.registerFormat('worktime_timeman', _worktime_timeman);
	var query = function(action, data, callback, bForce) {
		data["site_id"] = BX.message("SITE_ID");
		data["sessid"] = BX.bitrix_sessid();
		if (location)
		{
			data["lat"] = location.coords.latitude;
			data["lon"] = location.coords.longitude;
		}
		data = BX.ajax.prepareData(data);
		var query_data = {
			'method': 'POST',
			'dataType': 'json',
			'url': TMPoint + '&action=' + action,
			'data': data,
			'onsuccess': function(data) {
				window.app.hidePopupLoader();
				if (query_data && query_data.xhr && query_data.xhr.getResponseHeader('BX-Mobile-Action') === 'timeman')
					callback(data, action)
			},
			'onfailure': function(type, e) {
				window.app.hidePopupLoader();
				if (e && e.type === 'json_failure')
				{
					throw BX.util.strip_tags(e.data);
				}
			}
		};

		if (action === 'update')
		{
			query_data.lsId = 'tm-update';
			query_data.lsTimeout = intervals.START/1000 - 1;
			query_data.lsForce = !!bForce;
		}
		else if (action === 'report')
		{
			query_data.lsId = 'tm-report';
			query_data.lsTimeout = 29;
			query_data.lsForce = !!bForce;
		}
		window.app.showPopupLoader();
		return BX.ajax(query_data);
	},
		baseObj = (function(){
			var obj = function(node, DATA, formats) {
				this.date = new Date();

				this.id = (this.date.valueOf() + Math.round(Math.random() * 1000000));

				this.node = node;

				this.DATA = [];

				this.ERROR = false;

				this.FREE_MODE = false;

				this.formats = formats;

				BX.onCustomEvent("onMobileTimeManInit", [this.id]);

				BX.ready(BX.proxy(function(){this.init(DATA);}, this));

				this.onUpdate = BX.delegate(this.onUpdate, this);
				BX.addCustomEvent('onMobileTimeManDayHasBeenChanged', this.onUpdate);

				this.onPull = BX.delegate(this.onPull, this);
				BX.addCustomEvent('onPull-timeman', this.onPull);

				this.onMobileTimeManDailyReportHasBeenChanged = BX.delegate(function(data){
					this.DATA.REPORT = data.REPORT;
					this.DATA.REPORT_TS = data.REPORT_TS;
				}, this);
				BXMobileApp.addCustomEvent("onMobileTimeManDailyReportHasBeenChanged", this.onMobileTimeManDailyReportHasBeenChanged);

				this.destroy = BX.delegate(this.destroy, this);
				BX.addCustomEvent('onMobileTimeManInit', this.destroy);
			};
			obj.prototype = {
				status : "ready",
				inited : false,
				buttons : {},
				nodes : {
					main : null
				},
				init : function(DATA) {
					this.node = BX(this.node);
					if (!this.node)
						throw "Timeman: node is not DOM node.";
					if (!this.setData(DATA))
						throw "Timeman: initial data is not valid.";
					this.nodes.main = this.node;
					this.collectNodes();
					this.bind();
					this.check(true);
					this.inited = true;
				},
				collectNodes : function(){ },
				destroy : function(id) {
					if (id == this.id)
						return;
					BX.removeCustomEvent('onPull-timeman', this.onPull);
					BX.removeCustomEvent('onMobileTimeManDayHasBeenChanged', this.onUpdate);
					BX.removeCustomEvent("onMobileTimeManDailyReportHasBeenChanged", this.onMobileTimeManDailyReportHasBeenChanged);
					BX.removeCustomEvent('onMobileTimeManInit', this.destroy);

					this.unbind();
					var ii, j;
					for (ii in this.buttons)
					{
						if (this.buttons.hasOwnProperty(ii))
						{
							if (this.buttons[ii])
							{
								for (j=0;j<this.buttons[ii]["nodes"].length;j++)
									delete this.buttons[ii]["nodes"][j];
								delete this.buttons[ii]["f"];
							}
						}
					}
					for (ii in this.nodes)
					{
						if (this.nodes.hasOwnProperty(ii))
						{
							if (this.nodes)
							{
								for (j=0;j<this.nodes[ii].length;j++)
									delete this.nodes[ii][j];
								this.nodes[ii] = null;
							}
						}
					}
					delete this.node;
				},
				setData : function(DATA) {
					if (BX.type.isPlainObject(DATA))
					{
						this.node = BX(this.node);
						this.DATA = DATA;

						return true;
					}
					return false;
				},
				bind : function() {
					var ii, j;
					for (ii in this.buttons)
					{
						if (this.buttons.hasOwnProperty(ii))
						{
							this.buttons[ii] = {
								nodes : BX.findChild(this.node, {attribute : {"data-bx-timeman" : ii + "-button"}}, true, true),
								f : BX.delegate(this[ii], this)
							};
							for (j=0;j<this.buttons[ii]["nodes"].length;j++)
							{
								BX.bind(this.buttons[ii]["nodes"][j], "click", this.buttons[ii]["f"]);
							}
						}
					}
				},
				unbind : function() {
					var ii, j;
					for (ii in this.buttons)
					{
						if (this.buttons.hasOwnProperty(ii))
						{
							if (this.buttons[ii] && this.buttons[ii]["nodes"])
							{
								for (j=0;j<this.buttons[ii]["nodes"].length;j++)
								{
									BX.unbind(this.buttons[ii]["nodes"][j], "click", this.buttons[ii]["f"]);
								}
							}
						}
					}
				},
				check : function(set) {
					var status = "ready",
						pauseTimer = (this.DATA["INFO"] ? this.DATA.INFO.TIME_LEAKS : 0),
						stateTimer = 0;

					if (this.DATA["LAST_PAUSE"] && !this.DATA["LAST_PAUSE"]["DATE_FINISH"])
					{
						pauseTimer += this.date.valueOf() - (new Date(this.DATA["LAST_PAUSE"]["DATE_START"] * 1000)).valueOf();
					}
					if (this.DATA["STATE"] == "OPENED")
					{
						status = "opened";
						stateTimer = this.date.valueOf() - (new Date(this.DATA["INFO"]["DATE_START"] * 1000)).valueOf() - (new Date(this.DATA["INFO"]["TIME_LEAKS"] * 1000)).valueOf();
					}
					else
					{
						if (this.DATA["INFO"] &&
							this.DATA["INFO"]["DATE_START"] &&
							this.DATA["INFO"]["DATE_FINISH"])
						{
							stateTimer = (this.DATA["INFO"]["DATE_FINISH"] - this.DATA["INFO"]["DATE_START"] - this.DATA["INFO"]["TIME_LEAKS"]);
						}
						if (this.DATA["STATE"] == "CLOSED")
						{
							if (this.DATA["CAN_OPEN"] == "REOPEN" || !this.DATA["CAN_OPEN"])
							{
								status = "completed";
							}
							else
							{
								status = "start";
								pauseTimer = 0;
								stateTimer = 0;
							}
						}
						else if (this.DATA["STATE"] == "PAUSED")
						{
							status = "paused";
						}
						else if (this.DATA["STATE"] == "EXPIRED")
						{
							status = "expired";
						}
					}

					this.checkActions(status, set);
					_initPage(this.getMenu(status, set));

					window["app"].onCustomEvent("onMobileTimeManStatusHasBeenChanged", [status]);

					this.nodes.main.setAttribute("data-bx-timeman-status", status);
					this.nodes.main.setAttribute("data-bx-timeman-pause", (pauseTimer + ""));
					this.nodes.main.setAttribute("data-bx-timeman-state", (stateTimer + ""));
				},
				checkActions : function(status) { },
				getMenu : function(/*status*/) { return [];},
				checkQuery : function(data, action) {
					if (data["error"] || data["error_id"])
					{
						window["app"].alert({
							title : BX.message("ERROR_TITLE"), text : (data["error"] || data["error_id"])
						});
						return false;
					}
					window["app"].onCustomEvent("onMobileTimeManDayHasBeenChanged", [this.id, action, data]);
					return this.checkData(data, action);
				},
				checkData : function(data, action) {
					if (action == "close" && data["REPORT_REQ"] == "Y" && data["REPORT"] == "" && data["fromPull"] !== "Y")
					{
						return this.report();
					}
					this.DATA = data;
					this.check(true);
					return true;
				},
				onUpdate : function(id, action, data) {
					if (BX.type.isArray(id))
					{
						data = id[2];
						id = id[0];
					}
					if (this.id != id)
					{
						if (data && !(data["error"] || data["error_id"]))
						{
							this.checkData(data, action);
							return true;
						}
						window.app.reload();
					}
					return false;
				},
				onPull : function(data) {
					var command = data["command"];
					data = data["params"];
					if ((data["request_id"] + "") != (this.id + ""))
					{
						data["fromPull"] = "Y";
						this.checkData(data, command);
					}
				}
			};
			return obj;
		})(),
		timeManager = (function(){
			var d = function(node, DATA, formats) {
				d.superclass.constructor.apply(this, arguments);
			};
			BX.extend(d, baseObj);
			d.prototype.buttons = {
				start : null,
					resume : null,
					pause : null,
					stop : null,
					edit : null
			};
			d.prototype.nodes = {
				main : null,
				workingTimeTimer : null,
				stateTimer : null,
				pauseTimer : null,
				stopTimestamp : null,
				stopReason : null
			};
			d.prototype.collectNodes = function() {
				this.nodes["main"] = BX(this.node);
				this.nodes["workingTimeTimer"] = BX.findChild(this.node, {attribute : {"data-bx-timeman" : "working-time-timer"}}, true, true);

				this.nodes["stateTimer"] = BX.findChild(this.node, {attribute : {"data-bx-timeman" : "state-timer"}}, true, true);
				this.nodes["pauseTimer"] = BX.findChild(this.node, {attribute : {"data-bx-timeman" : "pause-timer"}}, true, true);

				this.nodes["startTimestamp"] = BX.findChild(this.node, {attribute : {"data-bx-timeman" : "start-timestamp"}}, true);
				new initTimestamp(this.nodes["startTimestamp"], this.nodes["startTimestamp"].previousSibling, this.formats.time);
				this.nodes["startReason"] = BX.findChild(this.node, {attribute : {"data-bx-timeman" : "start-reason"}}, true);

				this.nodes["stopTimestamp"] = BX.findChild(this.node, {attribute : {"data-bx-timeman" : "stop-timestamp"}}, true);
				new initTimestamp(this.nodes["stopTimestamp"], this.nodes["stopTimestamp"].previousSibling, this.formats.time);
				this.nodes["stopReason"] = BX.findChild(this.node, {attribute : {"data-bx-timeman" : "stop-reason"}}, true);
			};
			d.prototype.checkActions = function(status, set) {
				if (status == "opened")
					this.startStateTimers(set);
				else
					this.stopStateTimers(set);
				if (status == "paused")
					this.startPauseTimers(set);
				else
					this.stopPauseTimers(set);
				this.showStopForm("normal");
				this.showStartForm("normal");
			};
			d.prototype.getMenu = function(status) {
				var menu = [];
				if (this.DATA["ID"] > 0)
					menu.push({
						name : BX.message("TM_MENU_REPORT"),
						icon : "edit",
						action : BX.proxy(this.report, this)
					});
				if (status == "start")
				{
					if (this.nodes.main.getAttribute("data-bx-timeman-start-state") == "extended")
					{
						menu.push({
							name: BX.message("TM_MENU_START"),
							icon: 'play',
							action: BX.proxy(function(){
								this.showStartForm("normal");
								_initPage(this.getMenu(status, false));
							}, this)
						});
					}
					else
					{
						menu.push({
							name: BX.message("TM_MENU_START1"),
							icon: 'play',
							action: BX.proxy(function() {
								this.showStartForm("extended");
								_initPage(this.getMenu(status, false));
							}, this)
						});
					}
				}
				else if (status == "expired")
				{
				}
				else
				{
					menu.push({
						name: BX.message("TM_MENU_EDIT"),
						icon: 'edit',
						action: BX.proxy(this.edit, this)
					});
					if (status != "completed")
					{
						if (this.nodes.main.getAttribute("data-bx-timeman-stop-state") == "extended")
						{
							menu.push({
								name: BX.message("TM_MENU_STOP"),
								icon: 'finish',
								action: BX.proxy(function() {
									this.showStopForm("normal");
									_initPage(this.getMenu(status, false));
								}, this)
							});
						}
						else
						{
							menu.push({
								name: BX.message("TM_MENU_STOP1"),
								icon: 'finish',
								action: BX.proxy(function(){
									this.showStopForm("extended");
									_initPage(this.getMenu(status, false));
								}, this)
							});
						}
					}
				}

				return menu;
			};
			d.prototype.startStateTimers = function() {
				var i;
				if (this.nodes["stateTimer"])
				{
					for (i=0;i<this.nodes["stateTimer"].length;i++)
					{
						if (BX(this.nodes["stateTimer"][i]))
						{
							if (!this.nodes["stateTimer"][i]["timer"])
							{
								this.nodes["stateTimer"][i]["timer"] = BX.timer(this.nodes["stateTimer"][i], {
									from: new Date(this.DATA.INFO.DATE_START*1000),
									dt: -this.DATA.INFO.TIME_LEAKS * 1000,
									display: 'worktime_timeman'
								})
							}
							else
							{
								this.nodes["stateTimer"][i]["timer"].setFrom(new Date(this.DATA.INFO.DATE_START*1000));
								this.nodes["stateTimer"][i]["timer"].dt = -this.DATA.INFO.TIME_LEAKS * 1000;
							}
						}
					}
				}
			};
			d.prototype.stopStateTimers = function(set) {
				var i;
				var stateTimer = 0;

				if (this.DATA["STATE"] == "OPENED")
				{
					stateTimer = this.date.valueOf() - (new Date(this.DATA["INFO"]["DATE_START"] * 1000)).valueOf() - (new Date(this.DATA["INFO"]["TIME_LEAKS"] * 1000)).valueOf();
				}
				else if (this.DATA["STATE"] == "CLOSED" && !(this.DATA["CAN_OPEN"] == "REOPEN" || !this.DATA["CAN_OPEN"]))
				{
					stateTimer = 0;
				}
				else if (this.DATA["INFO"] &&
					this.DATA["INFO"]["DATE_START"] &&
					this.DATA["INFO"]["DATE_FINISH"])
				{
					stateTimer = (this.DATA["INFO"]["DATE_FINISH"] - this.DATA["INFO"]["DATE_START"] - this.DATA["INFO"]["TIME_LEAKS"]);
				}


				if (this.nodes["stateTimer"])
				{
					for (i=0;i<this.nodes["stateTimer"].length;i++)
					{
						if (BX(this.nodes["stateTimer"][i]))
						{
							if (this.nodes["stateTimer"][i]["timer"])
							{
								BX.timer.stop(this.nodes["stateTimer"][i]["timer"]);
								this.nodes["stateTimer"][i]["timer"] = null;
							}
							if (set)
							{
								this.nodes["stateTimer"][i].innerHTML = _worktime_timeman(
									parseInt(stateTimer / 3600),
									parseInt(stateTimer % 3600 / 60),
									(stateTimer % 3600) % 60
								);
							}
						}
					}
				}
			};
			d.prototype.startPauseTimers = function() {
				var i;
				if (this.nodes["pauseTimer"])
				{
					for (i=0;i<this.nodes["pauseTimer"].length;i++)
					{
						if (BX(this.nodes["pauseTimer"][i]))
						{
							if (!this.nodes["pauseTimer"][i]["timer"])
							{
								this.nodes["pauseTimer"][i]["timer"] = BX.timer(this.nodes["pauseTimer"][i], {
									from: new Date(this.DATA.INFO.DATE_FINISH * 1000),
									accuracy: 1,
									dt: this.DATA.INFO.TIME_LEAKS * 1000,
									display: 'worktime_timeman'
								});
							}
							else
							{
								this.nodes["pauseTimer"][i]["timer"].setFrom(new Date(this.DATA.INFO.DATE_FINISH*1000));
								this.nodes["pauseTimer"][i]["timer"].dt = this.DATA.INFO.TIME_LEAKS * 1000;
							}
						}
					}
				}
			};
			d.prototype.stopPauseTimers = function(set) {
				var i,
					pauseTimer = (this.DATA["INFO"] ? this.DATA.INFO.TIME_LEAKS : 0);

				if (this.DATA["LAST_PAUSE"] && !this.DATA["LAST_PAUSE"]["DATE_FINISH"])
				{
					pauseTimer += this.date.valueOf() - (new Date(this.DATA["LAST_PAUSE"]["DATE_START"] * 1000)).valueOf();
				}
				if (this.DATA["STATE"] == "CLOSED" && !(this.DATA["CAN_OPEN"] == "REOPEN" || !this.DATA["CAN_OPEN"]))
				{
					pauseTimer = 0;
				}

				if (this.nodes["pauseTimer"])
				{
					for (i=0;i<this.nodes["pauseTimer"].length;i++)
					{
						if (BX(this.nodes["pauseTimer"][i]))
						{
							if (this.nodes["pauseTimer"][i]["timer"])
							{
								BX.timer.stop(this.nodes["pauseTimer"][i]["timer"]);
								this.nodes["pauseTimer"][i]["timer"] = null;
							}
							if (set)
							{
								this.nodes["pauseTimer"][i].innerHTML = _worktime_timeman(
									parseInt(pauseTimer / 3600),
									parseInt(pauseTimer % 3600 / 60),
									(pauseTimer % 3600) % 60
								);
							}
						}
					}
				}
			};
			d.prototype.start = function(e) {
				var selectedTimestamp = 0,
					errorReport = '';

				if (this.nodes.main.getAttribute("data-bx-timeman-start-state") == "extended")
				{
					selectedTimestamp = this.nodes["startTimestamp"].value;
					if (BX.type.isNotEmptyString(this.nodes["startReason"].value))
					{
						errorReport = this.nodes["startReason"].value;
					}
					else
					{
						BX.focus(this.nodes["startReason"]);
						return BX.PreventDefault(e);
					}
				}
				query('open', {timestamp: selectedTimestamp, report: errorReport, request_id : this.id}, BX.proxy(this.checkQuery, this));
				return BX.PreventDefault(e);
			};
			d.prototype.pause = function(e) {
				query('pause', {request_id : this.id}, BX.proxy(this.checkQuery, this));
				return BX.PreventDefault(e);
			};
			d.prototype.resume = function(e) {
				query('reopen', {request_id : this.id}, BX.proxy(this.checkQuery, this));
				return BX.PreventDefault(e);
			};
			d.prototype.stop = function(e) {
				if (this.DATA["REPORT_REQ"] == "Y" && this.DATA["REPORT"] == "")
					return this.report();

				var selectedTimestamp = 0,
					errorReport = '';
				if (this.nodes.main.getAttribute("data-bx-timeman-stop-state") == "extended" ||
					this.nodes.main.getAttribute("data-bx-timeman-status") == "expired")
				{
					selectedTimestamp = this.nodes["stopTimestamp"].value;
					if (BX.type.isNotEmptyString(this.nodes["stopReason"].value))
					{
						errorReport = this.nodes["stopReason"].value;
					}
					else
					{
						BX.focus(this.nodes["stopReason"]);
						return BX.PreventDefault(e);
					}
				}
				var q = {timestamp: selectedTimestamp, report: errorReport, request_id : this.id};
				if (this.DATA["REPORT_REQ"] != "A")
				{
					q["REPORT"] = this.DATA["REPORT"];
					q["ready"] = "Y";
				}
				query('close', q, BX.proxy(this.checkQuery, this));
				return BX.PreventDefault(e);
			};
			d.prototype.edit = function() {
				window.BXMobileApp.PageManager.loadPageModal({
					url: BX.message("SITE_DIR") + "mobile/timeman/index.php?edit=Y",
					bx24ModernStyle : true,
					cache : false
				});
			};
			d.prototype.report = function() {
				window.BXMobileApp.PageManager.loadPageModal({
					url: BX.message("SITE_DIR") + "mobile/timeman/index.php?report=Y",
					bx24ModernStyle : true,
					cache : false
				});
			};
			d.prototype.showStartForm = function(status) {
				this.nodes.main.setAttribute("data-bx-timeman-start-state", status);
			};
			d.prototype.showStopForm = function(status) {
				this.nodes.main.setAttribute("data-bx-timeman-stop-state", status);
			};
			return d;
		})(),
		timeManagerEdit = (function(){
			var d = function(node, DATA, formats) {
				this.onChange = BX.delegate(this.onChange, this);
				d.superclass.constructor.apply(this, arguments);
			};
			BX.extend(d, baseObj);
			d.prototype.buttons = {
				save : null
			};
			d.prototype.nodes = {
				main : null,
				startTimestamp : null,
				finishTimestamp : null,
				pauseTimestamp : null,
				durationTimestamp : null,
				editReason : null
			};
			d.prototype.init = function(){
				d.superclass.init.apply(this, arguments);
			};
			d.prototype.collectNodes = function(){
				this.nodes["startTimestamp"] = BX.findChild(this.node, {attribute : {"data-bx-timeman" : "start-timestamp"}}, true);
				if (this.nodes["startTimestamp"])
				{
					new initTimestamp(this.nodes["startTimestamp"], this.nodes["startTimestamp"].previousSibling, this.formats.time);
					BX.addCustomEvent(this.nodes["startTimestamp"], "onChange", this.onChange);
				}
				this.nodes["finishTimestamp"] = BX.findChild(this.node, {attribute : {"data-bx-timeman" : "finish-timestamp"}}, true);
				if (this.nodes["finishTimestamp"])
				{
					new initTimestamp(this.nodes["finishTimestamp"], this.nodes["finishTimestamp"].previousSibling, this.formats.time);
					BX.addCustomEvent(this.nodes["finishTimestamp"], "onChange", this.onChange);
				}

				this.nodes["pauseTimestamp"] = BX.findChild(this.node, {attribute : {"data-bx-timeman" : "pause-timestamp"}}, true);
				if (this.nodes["pauseTimestamp"])
				{
					new initTimePeriod(this.nodes["pauseTimestamp"], this.nodes["pauseTimestamp"].previousSibling, true);
					BX.addCustomEvent(this.nodes["pauseTimestamp"], "onChange", this.onChange);
				}
				this.nodes["durationTimestamp"] = BX.findChild(this.node, {attribute : {"data-bx-timeman" : "duration-timestamp"}}, true);
				if (this.nodes["durationTimestamp"])
				{
					new initTimePeriod(this.nodes["durationTimestamp"], this.nodes["durationTimestamp"].previousSibling, false);
				}

				this.nodes["editReason"] = BX.findChild(this.node, {attribute : {"data-bx-timeman" : "edit-reason"}}, true);
			};
			d.prototype.bind = function(){
				d.superclass.bind.apply(this, arguments);
				this.onFocus = BX.delegate(this.onFocus, this);
				this.onBlur = BX.delegate(this.onBlur, this);
				BX.bind(this.nodes["editReason"], "focus", this.onFocus);
				BX.bind(this.nodes["editReason"], "blur", this.onBlur);
			};
			d.prototype.unbind = function(){
				d.superclass.unbind.apply(this, arguments);
				BX.unbindAll(this.nodes["editReason"]);
			};
			d.prototype.onFocus = function() {
				this.onBlur();
				if (!BX.type.isFunction(this.checkF))
				{
					this.checkF = BX.delegate(function(){
						if (BX.type.isNotEmptyString(this.nodes["editReason"].value))
							BX.removeClass(this.nodes["editReason"], "error");
						else
							BX.addClass(this.nodes["editReason"], "error");
					}, this);
				}
				this.checkF();
				this.onFocusInterval = setInterval(this.checkF, 500);
			};
			d.prototype.onBlur = function() {
				if (this.onFocusInterval > 0)
				{
					clearInterval(this.onFocusInterval);
					this.onFocusInterval = 0;
				}
			};
			d.prototype.checkActions = function(status, set){

				var pauseTimer = (this.DATA["INFO"] ? this.DATA.INFO.TIME_LEAKS*1000 : 0);

				if (this.DATA["LAST_PAUSE"] && !this.DATA["LAST_PAUSE"]["DATE_FINISH"])
				{
					pauseTimer += this.date.valueOf() - (new Date(this.DATA["LAST_PAUSE"]["DATE_START"] * 1000)).valueOf();
				}
				if (this.DATA["STATE"] == "CLOSED" && (this.DATA["CAN_OPEN"] && this.DATA["CAN_OPEN"] !== "REOPEN"))
				{
					pauseTimer = 0;
				}
				var date = this.date,
					j,
					finishTime = this.DATA["INFO"]["TIME_FINISH"] || this.DATA["EXPIRED_DATE"]||
						((date.getHours()*60 + date.getMinutes() + date.getTimezoneOffset()) * 60 + date.getSeconds() + parseInt(BX.message('SERVER_TZ_OFFSET')) + parseInt(BX.message('USER_TZ_OFFSET'))),
					nodes = {
						startTimestamp : this.DATA["INFO"]["TIME_START"],
						finishTimestamp : finishTime,
						pauseTimestamp : pauseTimer/1000,
						durationTimestamp : (finishTime - this.DATA["INFO"]["TIME_START"] - this.DATA["INFO"]["TIME_LEAKS"])
					};

				for (j in nodes)
				{
					if (nodes.hasOwnProperty(j))
					{
						if (this.nodes[j])
						{
							nodes[j] = isNaN(nodes[j]) ? 0 : nodes[j];
							this.nodes[j].value = nodes[j] + "";
							if (!this.nodes[j]["originalValue"] || set===true)
								this.nodes[j].originalValue = this.nodes[j].value;
							BX.onCustomEvent(this.nodes[j], "onFormat", []);
						}
					}
				}
			};
			d.prototype.onChange = function(node) {
				if (node == this.nodes["startTimestamp"])
				{
					this.DATA["INFO"]["TIME_START"] = parseInt(this.nodes["startTimestamp"].value);
				}
				else if (node == this.nodes["finishTimestamp"])
				{
					this.DATA["INFO"]["TIME_FINISH"] = parseInt(this.nodes["finishTimestamp"].value);
				}
				else if (node == this.nodes["pauseTimestamp"])
				{
					this.DATA.INFO.TIME_LEAKS = parseInt(this.nodes["pauseTimestamp"].value);
				}
				this.check(false);
			};
			d.prototype.checkQuery = function() {
				if (d.superclass.checkQuery.apply(this, arguments))
					window.app.closeModalDialog({});
			};
			d.prototype.save = function() {
				if (!this._callback)
					this._callback = BX.proxy(this.checkQuery, this);
				if (!BX(this.nodes["editReason"]) || BX.type.isNotEmptyString(this.nodes["editReason"].value))
				{
					var data = {}, nodes = ["startTimestamp", "finishTimestamp", "pauseTimestamp"], j, i, d = {request_id : this.id};
					for (i=0;i<nodes.length;i++)
					{
						if ((j=nodes[i]) && this.nodes[j] &&
							this.nodes[j]["originalValue"] &&
							((this.nodes[j].originalValue + "") != (this.nodes[j].value + "")))
						{
							data[j] = this.nodes[j].value;
						}
					}

					if (data["startTimestamp"])
						d["timeman_edit_from"] = data["startTimestamp"];
					if (data["finishTimestamp"])
						d["timeman_edit_to"] = data["finishTimestamp"];
					if (data["pauseTimestamp"])
						d["TIME_LEAKS"] = data["pauseTimestamp"];
					if (this.DATA["REPORT_REQ"] != "A")
					{
						d["REPORT"] = this.DATA["REPORT"];
						d["ready"] = "Y";
					}
					d.report = (this.nodes["editReason"] ? this.nodes["editReason"].value : "");
					query('save', d, this._callback);
				}
				else
				{
					BX.focus(this.nodes["editReason"]);
				}
			};
			return d;
		})(),
		timeManagerReport = (function(){
			var d = function(node, DATA, formats) {
				d.superclass.constructor.apply(this, arguments);
				this.onChange = BX.delegate(this.onChange, this);
				window.BXMobileApp.UI.Page.TopBar.updateButtons({
					cancel: {
						type: "back_text", // @param buttons.type The type of the button (plus|back|refresh|right_text|back_text|users|cart)
						callback: BX.delegate(this.cancel, this),
						name: BX.message("TM_MENU_CANCEL"),
						bar_type: "navbar", //("toolbar"|"navbar")
						position: "left"//("left"|"right")
					},
					ok: {
						type: "back_text", // @param buttons.type The type of the button (plus|back|refresh|right_text|back_text|users|cart)
						callback: BX.delegate(this.apply, this),
						name: BX.message("TM_MENU_SAVE"),
						bar_type: "navbar", //("toolbar"|"navbar")
						position: "right"//("left"|"right")
					}
				});
			};
			BX.extend(d, baseObj);
			d.prototype.buttons = {
				save : null
			};
			d.prototype.nodes = {
				main : null,
				report : null
			};
			d.prototype.init = function(){
				d.superclass.init.apply(this, arguments);
				this.nodes["report"].value = this.DATA.REPORT;
				BX.focus(this.nodes["report"]);
			};
			d.prototype.collectNodes = function(){
				this.nodes["report"] = BX.findChild(this.node, {attribute : {"data-bx-timeman" : "report"}}, true);
			};
			d.prototype.checkActions = function(status, set){
			};
			d.prototype.checkQuery = function(data) {
				BXMobileApp.onCustomEvent("onMobileTimeManDailyReportHasBeenChanged", data);
				this.checkData(data);
			};
			d.prototype.checkData = function(data) {
				this.DATA.REPORT_TS = data["REPORT_TS"];
				this.DATA.REPORT = data["REPORT"];
				this.nodes["report"].value = data["REPORT"];
			};
			d.prototype.save = function() {
				if (this.DATA.REPORT != this.nodes["report"].value)
				{
					if (!this._callback)
						this._callback = BX.proxy(this.checkQuery, this);
					var d = {
						entry_id : this.DATA.ID,
						report : this.nodes["report"].value,
						report_ts : new Date(this.DATA.REPORT_TS * 1000).valueOf()
					};
					query('report', d, this._callback);
				}
				else
				{
					BX.focus(this.nodes["report"]);
				}
			};
			d.prototype.cancel = function() {
				window.app.closeModalDialog( { } );
			};
			d.prototype.apply = function() {
				this.save();
				this.cancel();
			};
			return d;
		})();

	BX.MTimeMan = function(div, params, formats) {
		new timeManager(div, params, formats);
	};
	BX.MTimeManEdit = function(div, params, formats) {
		new timeManagerEdit(div, params, formats);
	};
	BX.MTimeManReport = function(div, params) {
		new timeManagerReport(div, params, {});
	}
})();