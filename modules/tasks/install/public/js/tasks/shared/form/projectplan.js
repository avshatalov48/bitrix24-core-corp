BX.namespace('BX.Tasks.Shared.Form');

BX.Tasks.Shared.Form.ProjectPlan = BX.Tasks.Util.Widget.extend({
	sys: {
		code: 'dateplanmanager'
	},
	options: {
		matchWorkTime: false,
		companyWorkTime: false
	},
	methods: {
		construct: function()
		{
			this.callConstruct(BX.Tasks.Util.Widget);

			if(typeof this.instances == 'undefined')
			{
				this.instances = {};
			}

			this.vars.blockSignal = false;
			this.vars.matchWorkTime = this.option('matchWorkTime');

			var data = this.optionP('data');
			var cwt = this.optionP('auxData').COMPANY_WORKTIME;

			this.vars.TIME_UNIT_TYPE_DAY = 'days';
			this.vars.TIME_UNIT_TYPE_HOUR = 'hours';
			this.vars.TIME_UNIT_TYPE_MINUTE = 'mins';
			this.vars.WORKDAY_DURATION = this.getWorkDayDuration();

			this.vars.unit = data.TASK.DURATION_TYPE || this.vars.TIME_UNIT_TYPE_DAY;

			var d = null;

			// datepickers init
			this.getDeadlinePicker(cwt);
			var sdpp = this.getStartDatePlanPicker(cwt);
			var edpp = this.getEndDatePlanPicker(cwt);

			var duration = 0;
			if (sdpp && edpp)
			{
				var start = sdpp.getTimeStamp();
				var startDate = start ? new Date(start * 1000) : null;

				var end = edpp.getTimeStamp();
				var endDate = end ? new Date(end * 1000) : null;

				if (startDate && end && startDate < endDate)
				{
					duration = this.calculateDuration(startDate, endDate) / 1000;
				}
			}

			this.setDurationSeconds(duration);

			// duration input
			BX.Tasks.Util.bindInstantChange(
				this.getDurationControl(),
				this.passCtx(BX.debounce(this.onDurationChange, 300, this))
			);

			this.bindDelegateControl('duration', 'keydown', this.preventEnter);

			// duration type selector
			this.bindDelegateControl('unit-setter', 'click', this.passCtx(this.onUnitChange));
		},

		getDurationControl: function()
		{
			return this.control('duration');
		},

		getStartDatePlanPicker: function(cwt)
		{
			if(!this.instances.startDatePlanPicker)
			{
				var scope = this.control('start-date-plan');
				if(BX.type.isElementNode(scope))
				{
					var d = new BX.Tasks.Util.DatePicker({
						scope: scope,
						defaultTime: cwt.HOURS.START
					});
					d.bindEvent('change', BX.delegate(this.onStartDatePlanChange, this));

					this.instances.startDatePlanPicker = d;
				}
			}

			return this.instances.startDatePlanPicker;
		},

		getEndDatePlanPicker: function(cwt)
		{
			if(!this.instances.endDatePlanPicker)
			{
				var scope = this.control('end-date-plan');
				if(BX.type.isElementNode(scope))
				{
					var d = new BX.Tasks.Util.DatePicker({
						scope: scope,
						defaultTime: cwt.HOURS.END
					});
					d.bindEvent('change', BX.delegate(this.onEndDatePlanChange, this));

					this.instances.endDatePlanPicker = d;
				}
			}

			return this.instances.endDatePlanPicker;
		},

		getDeadlinePicker: function(cwt)
		{
			if(!this.instances.deadlinePicker)
			{
				var scope = this.control('deadline');
				if(BX.type.isElementNode(scope))
				{
					var d = new BX.Tasks.Util.DatePicker({
						scope: scope,
						defaultTime: cwt.HOURS.END,
						calendarSettings: this.optionP('calendarSettings')
					});
					d.bindEvent('change', BX.delegate(this.onDeadLineChange, this));

					this.instances.deadlinePicker = d;
				}
			}

			return this.instances.deadlinePicker;
		},

		onDeadLineChange: function(stamp, value, display, local)
		{
			if (this.checkNoWorkDays(this.matchWorkTime()))
			{
				return;
			}

			this.solveDeadline(stamp);
			this.fireEvent('change-deadline', [this.getDeadlinePicker().getValue(), local]);
		},

		onStartDatePlanChange: function()
		{
			if (this.checkNoWorkDays(this.matchWorkTime()))
			{
				return;
			}

			this.solveTriangle(true, false, false);
		},

		onEndDatePlanChange: function()
		{
			if (this.checkNoWorkDays(this.matchWorkTime()))
			{
				return;
			}

			this.solveTriangle(false, true, false);
		},

		preventEnter: function(e)
		{
			if(BX.Tasks.Util.isEnter(e))
			{
				BX.PreventDefault(e);
				return false;
			}
		},

		checkNoWorkDays: function(matchWorkTime)
		{
			if (!matchWorkTime)
			{
				return false;
			}

			var result = true;
			var calender = this.getCalendar();
			var weekends = calender.weekends;
			var dayNumbers = [0, 1, 2, 3, 4, 5, 6];

			dayNumbers.forEach(function(dayNumber)
			{
				if (!(dayNumber in weekends))
				{
					result = false;
				}
			});

			return result;
		},

		setMatchWorkTime: function(way)
		{
			this.vars.matchWorkTime = way;

			if (this.checkNoWorkDays(way))
			{
				return;
			}

			var startProjectPlanPicker = this.getStartDatePlanPicker();
			var endProjectPlanPicker = this.getEndDatePlanPicker();

			this.recalculateDuration();
			if (startProjectPlanPicker && startProjectPlanPicker.getTimeStamp() !== null)
			{
				this.solveTriangle(true, false, false);
			}
			else if (endProjectPlanPicker && endProjectPlanPicker.getTimeStamp() !== null)
			{
				this.solveTriangle(false, true, false);
			}

			var deadlinePicker = this.getDeadlinePicker();
			if(deadlinePicker)
			{
				this.solveDeadline(deadlinePicker.getTimeStamp());
			}
		},

		matchWorkTime: function()
		{
			return this.vars.matchWorkTime; //this.parent().control("flag-worktime").checked;
		},

		getCalendar: function()
		{
			if(this.parent() && this.parent().instances.calendar)
			{
				return this.parent().instances.calendar;
			}

			if(this.instances.calendar == false)
			{
				this.instances.calendar = new BX.Tasks.Calendar(BX.Tasks.Calendar.adaptSettings(this.option('companyWorkTime')));
			}

			return this.instances.calendar;
		},

		setDeadline: function(date)
		{
			this.getDeadlinePicker().disableChangeEvent();
			this.getDeadlinePicker().setTimeStamp(date.getTime() / 1000);
			this.getDeadlinePicker().enableChangeEvent();
		},

		fixDeadline: function(date)
		{
			var calendar = this.getCalendar();
			if (this.matchWorkTime() && !calendar.isWorkTime(date))
			{
				date = calendar.getClosestWorkTime(date, true);
				this.setDeadline(date);
			}

			return date;
		},

		setStartDate: function(date)
		{
			this.getStartDatePlanPicker().disableChangeEvent();
			this.getStartDatePlanPicker().setTimeStamp(date.getTime() / 1000);
			this.getStartDatePlanPicker().enableChangeEvent();
		},

		fixStartDate: function(date)
		{
			var calendar = this.getCalendar();
			if (this.matchWorkTime() && !calendar.isWorkTime(date))
			{
				date = calendar.getClosestWorkTime(date, true);
				this.setStartDate(date);
			}

			return date;
		},

		setEndDate: function(date)
		{
			this.getEndDatePlanPicker().disableChangeEvent();
			this.getEndDatePlanPicker().setTimeStamp(date.getTime() / 1000);
			this.getEndDatePlanPicker().enableChangeEvent();
		},

		fixEndDate: function(date)
		{
			var calendar = this.getCalendar();
			if (this.matchWorkTime() && !calendar.isWorkTime(date))
			{
				date = calendar.getClosestWorkTime(date, false);
				this.setEndDate(date);
			}

			return date;
		},

		showHintPopup: function(picker)
		{
			if (picker)
			{
				BX.Tasks.Util.hintManager.show(
					picker.control("display"),
					BX.message("TASKS_TASK_COMPONENT_TEMPLATE_DATE_CORRECTION_TOOLTIP"),
					null,
					null,
					{ autoHide: true }
				);
			}
		},

		calculateDuration: function(startDate, endDate)
		{
			if (this.matchWorkTime())
			{
				var duration = this.getCalendar().calculateDuration(startDate, endDate);
				return duration > 0 ? duration : endDate - startDate;
			}
			else
			{
				return endDate - startDate;
			}
		},

		calculateStartDate: function(endDate, duration)
		{
			if (this.matchWorkTime())
			{
				return this.getCalendar().calculateStartDate(endDate, duration);
			}
			else
			{
				return new Date(endDate.getTime() - duration);
			}
		},

		calculateEndDate: function(startDate, duration)
		{
			if (this.matchWorkTime())
			{
				return this.getCalendar().calculateEndDate(startDate, duration);
			}
			else
			{
				return new Date(startDate.getTime() + duration);
			}
		},

		solveDeadline: function(stamp)
		{
			if (stamp !== null)
			{
				var deadline = new Date(stamp * 1000);
				var fixedDeadline = this.fixDeadline(deadline);
				if (deadline.getTime() !== fixedDeadline.getTime())
				{
					this.showHintPopup(this.getDeadlinePicker());
				}
			}
		},

		solveTriangle: function(startChange, endChange, durationChange)
		{
			if (this.vars.blockSignal)
			{
				return;
			}

			this.vars.blockSignal = true;

			var start = this.getStartDatePlanPicker().getTimeStamp();
			var startDate = start ? new Date(start * 1000) : null;

			var end = this.getEndDatePlanPicker().getTimeStamp();
			var endDate = end ? new Date(end * 1000) : null;

			var duration = this.vars.duration;
			var durationMs = duration * 1000;
			var defaultDuration = this.getMultiplier(this.vars.TIME_UNIT_TYPE_DAY);
			var defaultDurationMs = defaultDuration * 1000;

			if (durationChange)
			{
				if (duration) // changed to non-zero
				{
					if (startDate)
					{
						//Dates from database can be wrong
						startDate = this.fixStartDate(startDate);
						if (!this.isMaxDurationReached(durationMs))
						{
							this.setEndDate(this.calculateEndDate(startDate, durationMs));
						}
					}
					else if (endDate)
					{
						endDate = this.fixEndDate(endDate);
						if (!this.isMaxDurationReached(durationMs))
						{
							this.setStartDate(this.calculateStartDate(endDate, durationMs));
						}
					}
				}
			}
			else if (startChange)
			{
				if (startDate)
				{
					var fixedStartDate = this.fixStartDate(startDate);
					if (fixedStartDate.getTime() !== startDate.getTime())
					{
						startDate = fixedStartDate;
						this.showHintPopup(this.getStartDatePlanPicker());
					}

					if (duration)
					{
						if (!this.isMaxDurationReached(durationMs))
						{
							this.setEndDate(this.calculateEndDate(startDate, durationMs));
						}
					}
					else if (endDate)
					{
						endDate = this.fixEndDate(endDate);
						if (startDate < endDate)
						{
							durationMs = this.calculateDuration(startDate, endDate);
							duration = durationMs / 1000;

							this.setDurationSeconds(duration);
							this.isMaxDurationReached(durationMs);
						}
						else
						{
							this.setDurationSeconds(defaultDuration);
							this.setEndDate(this.calculateEndDate(startDate, defaultDurationMs));
						}
					}
				}
			}
			else if (endChange)
			{
				if (endDate)
				{
					startDate = startDate ? this.fixStartDate(startDate) : null;
					var fixedEndDate = this.fixEndDate(endDate);
					if (fixedEndDate.getTime() !== endDate.getTime())
					{
						endDate = fixedEndDate;
						this.showHintPopup(this.getEndDatePlanPicker());
					}

					if (startDate)
					{
						if (startDate < endDate)
						{
							durationMs = this.calculateDuration(startDate, endDate);
							duration = durationMs / 1000;

							this.setDurationSeconds(duration);
							this.isMaxDurationReached(durationMs);
						}
						else
						{
							this.setDurationSeconds(defaultDuration);
							this.setStartDate(this.calculateStartDate(endDate, defaultDurationMs));
						}
					}
					else if (duration)
					{
						this.setDurationSeconds(duration);
						if (!this.isMaxDurationReached(durationMs))
						{
							this.setStartDate(this.calculateStartDate(endDate, durationMs));
						}
					}
				}
			}

			this.vars.blockSignal = false;
		},

		onDurationChange: function(node)
		{
			if (this.checkNoWorkDays(this.matchWorkTime()))
			{
				return;
			}

			this.vars.duration = this.getDuration(this.vars.unit, node.value);
			this.solveTriangle(false, false, true);
		},

		onUnitChange: function(node)
		{
			if (this.checkNoWorkDays(this.matchWorkTime()))
			{
				return;
			}

			var unit = BX.data(node, 'unit');
			if(BX.type.isNotEmptyString(unit))
			{
				this.setDurationUnit(unit, true);
			}
		},

		getWorkDayDuration: function()
		{
			var duration = this.getCalendar().getWorkDayDuration(new Date());
			return duration > 0 ? duration / 1000 : 86400;
		},

		isMaxDurationReached: function(duration)
		{
			var result = true;

			if (!isNaN(duration) && (duration / 1000 < this.getMaxDuration()))
			{
				result = false;
			}

			this.switchControlMode(this.getDurationControl().parentElement, result);
			return result;
		},

		getMaxDuration: function()
		{
			return 2147483647;
		},

		switchControlMode: function(control, isErrorMode)
		{
			if (isErrorMode)
			{
				BX.addClass(control, 'task-field-error');
			}
			else
			{
				BX.removeClass(control, 'task-field-error');
			}
		},

		getMultiplier: function(unit)
		{
			if (unit == this.vars.TIME_UNIT_TYPE_DAY)
			{
				return this.matchWorkTime() ? this.vars.WORKDAY_DURATION : 86400;
			}

			if (unit == this.vars.TIME_UNIT_TYPE_HOUR)
			{
				return 3600;
			}

			if (unit == this.vars.TIME_UNIT_TYPE_MINUTE)
			{
				return 60;
			}

			return 1;
		},

		setDurationUnit: function(unit, recalc)
		{
			this.vars.unit = unit;

			// update task data here?

			this.dropCSSFlags('mode-unit-selected-*');
			this.setCSSFlag('mode-unit-selected-'+unit);

			this.control('duration-type-value').value = unit;

			if (recalc === true)
			{
				this.vars.duration = this.getDuration(this.vars.unit, this.getDurationControl().value);
				this.solveTriangle(false, false, true);
			}

		},

		getUnitByDuration: function(duration)
		{
			var units = [
				this.vars.TIME_UNIT_TYPE_DAY,
				this.vars.TIME_UNIT_TYPE_HOUR,
				this.vars.TIME_UNIT_TYPE_MINUTE
			];

			for (var i = 0; i < units.length; i++)
			{
				var unit = units[i];
				var durationInUnit = this.getMultiplier(unit);

				if (duration % durationInUnit === 0)
				{
					return unit;
				}
			}

			return this.vars.TIME_UNIT_TYPE_MINUTE;
		},

		setDurationSeconds: function(duration) // duration in seconds
		{
			if(isNaN(duration))
			{
				return;
			}

			this.vars.duration = duration;

			if (duration)
			{
				var unit = this.getUnitByDuration(duration);
				if (unit != this.vars.unit)
				{
					this.setDurationUnit(unit);
				}
			}

			var value = Math.floor(this.vars.duration / this.getMultiplier(this.vars.unit));
			if (value > 0)
			{
				this.getDurationControl().value = value;
			}
		},

		getDuration: function(unit, duration) // duration in unit
		{
			duration = parseInt(duration, 10);
			if(!isNaN(duration) && duration > 0)
			{
				return this.getMultiplier(unit) * duration;
			}

			return 0;
		},

		recalculateDuration: function()
		{
			this.vars.duration = this.getDuration(this.vars.unit, this.control("duration").value);
		}
	}
});
