;(function(){

window.JCTimeManTpl = function(DIV)
{
	this.bInited = false;

	this.DIV = DIV;
	this.DATA = null;
	this.PARTS = {};

	BX.addCustomEvent('onTimeManDataRecieved', BX.proxy(this.setData, this));
	BX.addCustomEvent('onPlannerQueryResult', BX.proxy(this.setPlannerData, this));

	this.WD_TIMER = null;

	BX.template(this.DIV, BX.proxy(this.Init, this));
}

JCTimeManTpl.prototype.setPlannerData = function(DATA)
{
	this.DATA.PLANNER = DATA;

	if (this.bInited)
		this.Redraw();
}

JCTimeManTpl.prototype.setData = function(DATA)
{
	this.DATA = DATA;

	if (this.bInited)
		this.Redraw();
}

JCTimeManTpl.prototype.Init = function(parts)
{
	this.bInited = true;
	this.DIV = BX(this.DIV);

	BXTIMEMAN.setBindOptions({
		node: this.DIV,
		type: ['right', 'top']
	});

	this.PARTS = parts;

	new BX.CHint({parent: this.PARTS.event, hint: BX.message('JS_CORE_HINT_EVENTS')});
	new BX.CHint({parent: this.PARTS.state.parentNode, hint: BX.message('JS_CORE_HINT_STATE')});
	new BX.CHint({parent: this.PARTS.tasks, hint: BX.message('JS_CORE_HINT_TASKS')});

	if (this.DATA)
		this.Redraw();
}

JCTimeManTpl.prototype.Redraw = function()
{
	this.DIV.className = 'tm-dashboard-inner bx-tm-' + this.DATA.STATE.toLowerCase()

	this.DATA.PLANNER.TASKS_COUNT = parseInt(this.DATA.PLANNER.TASKS_COUNT);
	if (this.DATA.PLANNER.TASKS_ENABLED && this.DATA.PLANNER.TASKS_COUNT > 0)
	{
		this.PARTS.tasks_counter.innerHTML = this.DATA.PLANNER.TASKS_COUNT;
		BX.show(this.PARTS.tasks, 'inline-block');
	}
	else
	{
		BX.hide(this.PARTS.tasks, 'inline-block');
	}

	if (!!this.DATA.PLANNER.EVENT_TIME)
	{
		this.PARTS.event_time.innerHTML = this.DATA.PLANNER.EVENT_TIME;
		BX.show(this.PARTS.event, 'inline-block');
	}
	else
	{
		BX.hide(this.PARTS.event, 'inline-block');
	}

	if (!this.PARTS.clock.TIMER)
		this.PARTS.clock.TIMER = BX.timer.clock(this.PARTS.clock);

	if (this.DATA.STATE == 'OPENED')
	{
		if (this.PARTS.state.TIMER)
		{
			this.PARTS.state.TIMER.setFrom(new Date(this.DATA.INFO.DATE_START*1000));
			this.PARTS.state.TIMER.dt = -this.DATA.INFO.TIME_LEAKS * 1000;
		}
		else
		{
			this.PARTS.state.TIMER = BX.timer(this.PARTS.state, {
				from: new Date(this.DATA.INFO.DATE_START*1000),
				dt: -this.DATA.INFO.TIME_LEAKS * 1000,
				display: 'worktime_timeman'
			});
		}
	}
	else
	{
		if (this.PARTS.state.TIMER)
		{
			BX.timer.stop(this.PARTS.state.TIMER);
			this.PARTS.state.TIMER = null;
			BX.cleanNode(this.PARTS.state);
		}

		if (this.DATA.STATE == 'PAUSED' || this.DATA.STATE == 'CLOSED' && this.DATA.CAN_OPEN != 'OPEN')
		{
			var q = (this.DATA.INFO.DATE_FINISH - this.DATA.INFO.DATE_START - this.DATA.INFO.TIME_LEAKS);
			this.PARTS.state.innerHTML = BX.timeman.formatWorkTimeView(q, 'worktime_timeman');
		}
	}
}

})();