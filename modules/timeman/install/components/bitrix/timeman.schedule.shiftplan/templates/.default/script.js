;(function ()
{
	BX.namespace('BX.Timeman.Component.Schedule');
	BX.Timeman.Component.Schedule.ShiftPlan = function (options)
	{
		this.isSlider = options.isSlider;
		options.containerSelector = options.containerSelector
			? options.containerSelector : (this.isSlider ? 'body' : '#content-table');
		BX.Timeman.Component.BaseComponent.apply(this, arguments);

		this.scheduleId = options.scheduleId;
		this.gridId = options.gridId;
		options.container = this.container = document.querySelector(this.isSlider ? 'body' : '#content-table');
		this.printShiftplanBtn = this.selectOneByRole('print-shift-plan-btn');
		this.shiftPlanTable = new BX.Timeman.Component.Schedule.ShiftPlan.Table(options);
		this.addEventHandlers();
	};
	BX.Timeman.Component.Schedule.ShiftPlan.prototype = {
		__proto__: BX.Timeman.Component.BaseComponent.prototype,
		constructor: BX.Timeman.Component.Schedule.ShiftPlan,
		addEventHandlers: function ()
		{
			BX.bind(this.printShiftplanBtn, 'click', BX.delegate(this.onPrintClick, this));

			BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(function (event)
			{
				if (event.getEventId() === 'BX.Timeman.Schedule.Update::Success')
				{
					this.reloadGrid();
				}
				else if (event.getEventId() === 'BX.Timeman.Schedule.Shift.Add::Success')
				{
					this.reloadGrid();
				}
			}.bind(this)));
		},
		onPrintClick: function ()
		{
			setTimeout(function ()
			{
				window.print();
			}, 1000);
		},
		reloadGrid: function ()
		{
			BX.Main.gridManager.reload(this.gridId);
		}
	};
})();