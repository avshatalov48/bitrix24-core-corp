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
		this.addShiftBtn = this.selectOneByRole('add-shift-btn');
		this.editScheduleBtn = this.selectOneByRole('edit-schedule-btn');
		this.printShiftplanBtn = this.selectOneByRole('print-shift-plan-btn');
		this.addUserBtn = this.selectOneByRole('add-user-btn');
		this.userSelector = this.selectOneByRole('user-selector');

		this.shiftPlanTable = new BX.Timeman.Component.Schedule.ShiftPlan.Table(options);
		this.addEventHandlers();
	};
	BX.Timeman.Component.Schedule.ShiftPlan.prototype = {
		__proto__: BX.Timeman.Component.BaseComponent.prototype,
		constructor: BX.Timeman.Component.Schedule.ShiftPlan,
		addEventHandlers: function ()
		{
			BX.bind(this.addShiftBtn, 'click', BX.delegate(this.onAddShiftClick, this));
			BX.bind(this.editScheduleBtn, 'click', BX.delegate(this.onEditScheduleClick, this));
			BX.bind(this.printShiftplanBtn, 'click', BX.delegate(this.onPrintClick, this));
			BX.bind(this.addUserBtn, 'click', BX.delegate(this.onAddUserClick, this));

			BX.addCustomEvent('BX.Main.User.SelectorController:select', BX.delegate(this.onUserSelected, this));
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
		},
		onUserSelected: function (event)
		{
			// close selector popup and unselect all selected tiles
			// please delete if you know a better way to do it
			this.userSelectorPopup.contentContainer.querySelector('[data-role="remove"]').click();
			document.querySelector('#pagetitle').click();

			if (this.container.querySelector('[data-user-id="' + event.item.entityId + '"][data-role="delete-user-btn"]'))
			{
				return;
			}
			BX.Main.gridManager.getInstanceById(this.gridId).tableFade();
			BX.ajax.runAction(
				'timeman.schedule.addUser',
				{
					data: {id: this.scheduleId, userId: event.item.entityId}
				}
			).then(
				function (response)
				{
					this.reloadGrid();
				}.bind(this),
				function (response)
				{
					this.reloadGrid();
				}.bind(this));
		},
		onAddUserClick: function (event)
		{
			if (!this.userSelectorPopup)
			{
				this.userSelectorPopup = new BX.PopupWindow('userSelectorPopup-' + Math.random(), event.currentTarget, {
					autoHide: true,
					width: 650,
					closeByEsc: true,
					angle: true,
					contentColor: 'white',
					contentNoPaddings: true,
				});
				this.userSelectorPopup.setContent(this.userSelector);
			}

			this.userSelectorPopup.show();
		},
		onEditScheduleClick: function (event)
		{
			event.stopPropagation();
			event.preventDefault();
			var url = BX.util.add_url_param("/bitrix/components/bitrix/timeman.schedule.edit/slider.php", {SCHEDULE_ID: this.scheduleId});
			window.top.BX.SidePanel.Instance.open(url, {width: 1200, cacheable: false});
		},
		onAddShiftClick: function (event)
		{
			event.stopPropagation();
			event.preventDefault();
			var url = BX.util.add_url_param("/bitrix/components/bitrix/timeman.schedule.shift.edit/slider.php", {SCHEDULE_ID: this.scheduleId});
			window.top.BX.SidePanel.Instance.open(url, {width: 1200, cacheable: false});
		}
	};
})();