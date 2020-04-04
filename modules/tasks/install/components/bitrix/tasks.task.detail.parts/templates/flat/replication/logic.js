BX.namespace('Tasks.Component');

(function() {

	if(typeof BX.Tasks.Component.TaskDetailPartsReplication != 'undefined')
	{
		return;
	}

	BX.Tasks.Component.TaskDetailPartsReplication = BX.Tasks.Util.Widget.extend({
		sys: {
			code: 'replication'
		},
		options: {
			data: false,
			can: false
		},
		constants: {
			PERIOD_DAILY: 'daily',
			PERIOD_WEEKLY: 'weekly',
			PERIOD_MONTHLY: 'monthly',
			PERIOD_YEARLY: 'yearly',

			REPEAT_TILL_ENDLESS: 'endless',
			REPEAT_TILL_TIMES: 'times',
			REPEAT_TILL_DATE: 'date',

			MONDAY: 0, //monday starting from 0
			TUESDAY: 1,
			THURSDAY: 3,
			SUNDAY: 6
		},
		methods: {
			construct: function ()
			{
				this.callConstruct(BX.Tasks.Util.Widget);

				var date = this.control('start-date-datepicker');
				if (BX.type.isElementNode(date))
				{
					this.instances.startDate = new BX.Tasks.Util.DatePicker({
						scope: date,
						displayFormat: 'system-short',
						defaultTime: {H: 0, M: 0, S:0}
					});
				}

				date = this.control('end-date-datepicker');
				if (BX.type.isElementNode(date))
				{
					this.instances.endDate = new BX.Tasks.Util.DatePicker({
						scope: date,
						displayFormat: 'system-short',
						defaultTime: {H: 0, M: 0, S:0}
					});
				}

				this.instances.time = new timePicker({
					scope: this.control('timepicker'),
					inputId: 'taskReplicationTimeFake',
					value: this.option('data').TIME
				});

				this.vars.prevPeriod = this.getCurrentPeriod();

				this.bindEvents();
				this.onPeriodChange();
			},

			bindEvents: function()
			{
				var handler = BX.delegate(this.onUpdateHint, this);

				this.bindDelegateControl('period-type', 'change', BX.delegate(this.onPeriodChange, this));
				this.bindDelegateControl('period-type-option', 'click', this.passCtx(this.onSetPeriodValue));

				// update hint on period change and repeat till type change
				this.bindDelegateControl('day-type', 'change', handler);

				// update hint on all selectboxes, checkboxes and radiobuttons
				BX.bindDelegate(this.scope(), 'change', {tag: 'select'}, handler);
				BX.bindDelegate(this.scope(), 'change', {tag: 'input', attr: {type: 'checkbox'}}, handler);
				BX.bindDelegate(this.scope(), 'change', {tag: 'input', attr: {type: 'radio'}}, handler);

				// update hint instantly on all texts
				BX.Tasks.Util.bindInstantChange(this.control('every-day'), handler);
				BX.Tasks.Util.bindInstantChange(this.control('daily-month-interval'), handler);
				BX.Tasks.Util.bindInstantChange(this.control('every-week'), handler);
				BX.Tasks.Util.bindInstantChange(this.control('monthly-day-num'), handler);
				BX.Tasks.Util.bindInstantChange(this.control('monthly-month-num-1'), handler);
				BX.Tasks.Util.bindInstantChange(this.control('monthly-month-num-2'), handler);
				BX.Tasks.Util.bindInstantChange(this.control('yearly-day-num'), handler);

				// update hint on repeat constraints change
				BX.Tasks.Util.bindInstantChange(this.control('times'), handler);
				this.instances.startDate.bindEvent('change', handler);
				this.instances.endDate.bindEvent('change', handler);
				this.instances.time.bindEvent('change', handler);

				BX.Tasks.Util.hintManager.bindHelp(this.scope());
			},

			getCurrentPeriod: function()
			{
				return this.getValueString('period-type');
			},

			onSetPeriodValue: function(node)
			{
				var type = BX.data(node, 'type');
				if (BX.util.in_array(type, [this.PERIOD_DAILY, this.PERIOD_WEEKLY, this.PERIOD_MONTHLY, this.PERIOD_YEARLY]))
				{
					this.control('period-type').value = type;
					this.onPeriodChange();
				}
			},

			setConstraintPanelHeight: function(period)
			{
				var nodeToShow = this.control('panel-' + period);
				if (nodeToShow)
				{
					var height = BX.pos(nodeToShow).height;
					this.control('panel').style.height = height + 'px';
				}
			},

			onPeriodChange: function()
			{
				var period = this.getCurrentPeriod();

				this.dropCSSFlags('period-*');
				this.setCSSFlag('period-' + BX.util.htmlspecialchars(period));

				// show\hide blocks...
				if (this.vars.prevPeriod != period)
				{
					var nodeToHide = this.control('panel-' + this.vars.prevPeriod);
					var nodeToShow = this.control('panel-' + period);

					if (nodeToHide && nodeToShow)
					{
						this.setConstraintPanelHeight(this.vars.prevPeriod);

						// hide previous
						BX.addClass(nodeToHide, 'nodisplay');
						BX.removeClass(nodeToShow, 'nodisplay');

						this.setConstraintPanelHeight(period);

						this.vars.prevPeriod = period;
					}
				}

				this.onUpdateHint();
			},

			onUpdateHint: function()
			{
				var hint = this.control('hint');
				if (!hint)
				{
					return;
				}

				var time = this.parseTime(this.getValueString('time'));

				// todo: preserve this object to prevent getting values of all controls at the time
				var params = {
					'PERIOD': this.getValueString('period-type'),
					'EVERY_DAY': this.getValueInt('every-day'),
					'WORKDAY_ONLY': this.getValueString('day-type'),
					'DAILY_MONTH_INTERVAL': this.getValueInt('daily-month-interval'),
					'EVERY_WEEK': this.getValueInt('every-week'),
					'WEEK_DAYS': this.getWeekDays(-1), // in this field, values start from 1
					'MONTHLY_DAY_NUM': this.getValueInt('monthly-day-num'),
					'MONTHLY_MONTH_NUM_1': this.getValueInt('monthly-month-num-1'),
					'MONTHLY_TYPE': this.getCheckedControlValues('monthly-type')[0],
					'MONTHLY_WEEK_DAY_NUM': this.getValueInt('monthly-week-day-num'),
					'MONTHLY_WEEK_DAY': this.getValueInt('monthly-week-day'),
					'MONTHLY_MONTH_NUM_2': this.getValueInt('monthly-month-num-2'),
					'YEARLY_TYPE': this.getCheckedControlValues('yearly-type')[0],
					'YEARLY_DAY_NUM': this.getValueInt('yearly-day-num'),
					'YEARLY_MONTH_1': this.getValueInt('yearly-month-1'),
					'YEARLY_WEEK_DAY_NUM': this.getValueInt('yearly-week-day-num'),
					'YEARLY_WEEK_DAY': this.getValueInt('yearly-week-day'),
					'YEARLY_MONTH_2': this.getValueInt('yearly-month-2'),
					'START_DATE': this.instances.startDate.getValue(),
					'END_DATE': this.instances.endDate.getValue(),
					'TIME': time == '' ? '05:00' : time,
					'TIMES': this.getValueInt('times'),
					'REPEAT_TILL': this.getRepeatTill()
				};

				hint.innerHTML = this.makeHintText(params);
			},

			makeHintText: function (params)
			{
				var tzOffset = parseInt(this.option('tzOffset'));
				var condition = BX.message('TASKS_TTDP_REPLICATION_HINT_AT_TIME').replace('#TIME#', params.TIME+' (UTC '+(tzOffset > 0 ? '+' : '-')+BX.Tasks.Util.formatTimeAmount(tzOffset, 'HH:MI')+')');

				if(params.PERIOD == this.PERIOD_DAILY)
				{
					var dayNumber = params.EVERY_DAY;

					condition += BX.message('TASKS_TTDP_REPLICATION_DAILY_EXT').replace('#NUMBER#', (dayNumber > 1 ? ' ' + dayNumber : '')).replace('#DAY_TYPE#', params.WORK_DAY == 'Y' ? ' ' + BX.message('TASKS_TTDP_REPLICATION_DAILY_WORK') : '');

					var timesInMonth = params.DAILY_MONTH_INTERVAL;
					if(timesInMonth > 0)
					{
						condition += BX.message('TASKS_TTDP_REPLICATION_HINT_DAILY_MONTH_INTERVAL').replace('#TIMES#', timesInMonth).replace('#TIMES_PLURAL#', BX.Tasks.Util.getMessagePlural(timesInMonth, 'TASKS_TTDP_REPLICATION_HINT_DAILY_MONTH_INTERVAL'));
					}
				}
				else if(params.PERIOD == this.PERIOD_WEEKLY)
				{
					var number = params.EVERY_WEEK;

					condition += BX.message('TASKS_TTDP_REPLICATION_WEEKLY_EXT').replace('#NUMBER#', (number > 1 ? ' ' + number : ''));

					var wd = '';
					if (params.WEEK_DAYS.length == 7)
					{
						wd = BX.message('TASKS_TTDP_REPLICATION_EVERY_DAY');
					}
					else
					{
						wd = [];
						for (var k = 0; k < params.WEEK_DAYS.length; k++)
						{
							wd.push(BX.message('TASKS_TTDP_REPLICATION_WD_' + params.WEEK_DAYS[k]));
						}
						wd = wd.join(', ');
					}

					condition += ' (' + wd + ')';
				}
				else if(params.PERIOD == this.PERIOD_MONTHLY)
				{
					if (params.MONTHLY_TYPE == 1)
					{
						var number = params.MONTHLY_DAY_NUM;
						var mNumber = params.MONTHLY_MONTH_NUM_1;
						condition += BX.message('TASKS_TTDP_REPLICATION_MONTHLY_EXT_TYPE_1').replace('#DAY_NUMBER#', number).replace('#MONTH_NUMBER#', (mNumber > 1 ? ' ' + mNumber : ''));
					}
					else
					{
						var wdGender = this.getWeekDayGender(params.MONTHLY_WEEK_DAY);
						var wdName = this.getWeekDayName(params.MONTHLY_WEEK_DAY);

						var number = BX.message('TASKS_TTDP_REPLICATION_NUMBER_' + params.MONTHLY_WEEK_DAY_NUM + wdGender);
						var each = BX.message('TASKS_TTDP_REPLICATION_EACH' + this.getWeekDayGender(params.MONTHLY_WEEK_DAY));
						var mNumber = params.MONTHLY_MONTH_NUM_2;
						condition += BX.message('TASKS_TTDP_REPLICATION_MONTHLY_EXT_TYPE_2').replace('#EACH#', each).replace('#DAY_NUMBER#', number).replace('#WEEKDAY_NAME#', wdName).replace('#MONTH_NUMBER#', (mNumber > 1 ? ' ' + mNumber : ''));
					}
				}
				else
				{
					if (params.YEARLY_TYPE == 1)
					{
						var number = params.YEARLY_DAY_NUM;
						var mName = BX.message('TASKS_TTDP_REPLICATION_MONTH_' + params.YEARLY_MONTH_1);
						condition += BX.message('TASKS_TTDP_REPLICATION_YEARLY_EXT_TYPE_1').replace('#DAY_NUMBER#', number).replace('#MONTH_NAME#', mName);
					}
					else
					{
						var wdGender = this.getWeekDayGender(params.YEARLY_WEEK_DAY);
						var wdName = this.getWeekDayName(params.YEARLY_WEEK_DAY);

						var number = BX.message('TASKS_TTDP_REPLICATION_NUMBER_' + params.YEARLY_WEEK_DAY_NUM + wdGender);
						var each = BX.message('TASKS_TTDP_REPLICATION_EACH' + this.getWeekDayGender(params.YEARLY_WEEK_DAY));
						var mName = BX.message('TASKS_TTDP_REPLICATION_MONTH_' + params.YEARLY_MONTH_2);
						condition += BX.message('TASKS_TTDP_REPLICATION_YEARLY_EXT_TYPE_2').replace('#EACH#', each).replace('#DAY_NUMBER#', number).replace('#WEEKDAY_NAME#', wdName).replace('#MONTH_NAME#', mName);
					}
				}

				var constraint = '';

				var repeatTimes = params.TIMES;

				if (params.START_DATE != '')
				{
					// start date to short format
					var short = BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATE')), BX.parseDate(params.START_DATE, true), false, true);

					constraint += BX.message('TASKS_TTDP_REPLICATION_HINT_START_CONSTRAINT').replace('#DATETIME#', short);
				}
				else
				{
					constraint += BX.message('TASKS_TTDP_REPLICATION_HINT_START_CONSTRAINT_NONE');
				}

				var till = this.getRepeatTill();

				var endless = true;

				if (params.END_DATE != '' && till == this.REPEAT_TILL_DATE)
				{
					constraint += BX.message('TASKS_TTDP_REPLICATION_HINT_END_CONSTRAINT').replace('#DATETIME#', params.END_DATE);
					endless = false;
				}
				else if (repeatTimes > 0 && till == this.REPEAT_TILL_TIMES)
				{
					constraint += BX.message('TASKS_TTDP_REPLICATION_HINT_END_CONSTRAINT_TIMES').replace('#TIMES#', repeatTimes).replace('#TIMES_PLURAL#', BX.Tasks.Util.getMessagePlural(repeatTimes, 'TASKS_TTDP_REPLICATION_HINT_END_CONSTRAINT_TIMES'));
					endless = false;
				}

				if (endless)
				{
					constraint += BX.message('TASKS_TTDP_REPLICATION_HINT_END_CONSTRAINT_NONE');
				}

				return BX.message('TASKS_TTDP_REPLICATION_HINT_BASE').replace('#CONDITION#', condition).replace('#CONSTRAINT#', constraint);
			},

			getWeekDayName: function (num) {
				var wdGender = this.getWeekDayGender(num);
				return BX.message('TASKS_TTDP_REPLICATION_WD_' + num + (wdGender == '_F' ? '_ALT' : ''));
			},

			getValueString: function (controlName) {
				var control = this.control(controlName);
				if (BX.type.isElementNode(control)) {
					return BX.util.htmlspecialchars(control.value.toString());
				}
				return '';
			},

			getValueInt: function (controlName) {
				var control = this.control(controlName);
				if (BX.type.isElementNode(control)) {
					var val = parseInt(control.value.toString());
					if (isNaN(val)) {
						return 0;
					}

					return val;
				}
				return 0;
			},

			getCheckedControlValues: function (id) {
				var result = [];

				var nodes = this.controlAll(id);
				for (var k in nodes) {
					if (nodes[k].checked) {
						result.push(nodes[k].value);
					}
				}

				return result;
			},

			parseTime: function (time) {
				time = time.toString().replace(/^\s+/, '').replace(/\s+$/, '');

				if (time.length > 0) {
					var found = time.match(/^(\d{1,2}):(\d{1,2})$/);
					if (found === null) {
						return '';
					}

					var h = parseInt(found[1]);
					var m = parseInt(found[2]);

					if (h > 23 || m > 59) {
						return '';
					}

					var pad = function (n) {
						n = n.toString();
						if (n.length == 1) {
							return '0' + n;
						}

						return n;
					}

					return pad(h) + ':' + pad(m);
				}

				return time;
			},

			getRepeatTill: function () {
				var repeat = this.getCheckedControlValues('repeat-till');
				if (typeof repeat[0] == 'undefined') {
					return this.REPEAT_TILL_ENDLESS;
				}

				return repeat[0];
			},

			getWeekDays: function (base) {
				var wd = this.getCheckedControlValues('week-days');
				if (wd.length == 0) {
					wd.push(this.MONDAY);
				}
				else {
					for (var k = 0; k < wd.length; k++) {
						wd[k] = parseInt(wd[k]) + base;
					}
				}

				return wd;
			},

			getWeekDayGender: function (num) {
				if (num == this.MONDAY || num == this.TUESDAY || num == this.THURSDAY) {
					return '_M';
				}
				if (num == this.SUNDAY) {
					return '';
				}
				return '_F';
			}
		}
	});

	var timePicker = BX.Tasks.Util.Widget.extend({
		sys: {
			code: 'timepicker'
		},
		options: {
			value: '',
			inputId: ''
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.Widget);

				this.bindDelegateControl('display', 'click', BX.delegate(this.openClock, this));

				this.vars.formatDisplay = BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME').replace(BX.message('FORMAT_DATE'), '').replace(':SS', '').replace('/SS', '').trim());
				this.vars.formatValue = BX.date.convertBitrixFormat('HH:MI');

				this.setTime(this.parseTime(this.option('value'))); // 24-hour format of value here!!!!

				BX.bind(BX(this.option('inputId')), 'change', this.passCtx(this.onTimeChange));
			},

			openClock: function()
			{
				// see clock.js for details
				var cbName = 'bxShowClock_'+this.option('inputId');
				if(BX.type.isFunction(window[cbName]))
				{
					window[cbName].call(window);
				}
			},

			onTimeChange: function(node)
			{
				var time = this.parseTime(node.value);
				this.setTime(time);

				this.fireEvent('change', [time]);
			},

			setTime: function(time)
			{
				var ts = 3600*(time.h) + 60*(time.m);

				this.control('display').value = this.dateStampToString(ts, this.vars.formatDisplay);
				this.control('value').value = this.dateStampToString(ts, this.vars.formatValue);
			},

			parseTime: function(value)
			{
				var time = value.toString().trim();
				var h = 0;
				var m = 0;

				// there will be troubles if they will switch places of hour and minute in date format :-|
				// todo: make parseTime() behave current format normally, but beware of passing 24-hour time in construct() then
				var found = time.match(new RegExp('^(\\d{1,2})[^\\d]+(\\d{2})', 'i'));
				if(found)
				{
					h = found[1] ? parseInt(found[1]) : 0;
					m = found[2] ? parseInt(found[2]) : 0;
				}

				found = time.match(new RegExp('(am|pm)', 'i'));
				var hasAmPm = found && found[1];
				var pm = (hasAmPm && found[1].toLowerCase() == 'pm');

				if (!isNaN(h) && !isNaN(m) && (h >= 0 && h <= 23) && (m >= 0 && m <= 59))
				{
					if(hasAmPm)
					{
						if(pm) // pm
						{
							if(h != 12) // 12:00 pm (12) => 12:00 (24), but
							{
								h += 12; // 1:00 pm (12) => 13:00 (24)
							}
						}
						else // am
						{
							if(h == 12)
							{
								h = 0; // 12:00 am (12) => 00:00 (24)
							}
						}

					}

					return {h: h, m: m}
				}

				return false;
			},

			dateStampToString: function(stamp, format)
			{
				return BX.date.format(format, new Date(stamp * 1000), false, true);
			}
		}
	});

}).call(this);