;(function ()
{
	BX.namespace('BX.Timeman.Component.Schedule.Shift');
	/**
	 * @param options
	 * @extends BX.Timeman.Component.BaseComponent
	 * @constructor
	 */
	BX.Timeman.Component.Schedule.ShiftEdit = function (options)
	{
		BX.Timeman.Component.BaseComponent.apply(this, arguments);
		this.isSlider = options.isSlider;
		this.selfContainerSelector = options.containerSelector;
		this.breakTimeLink = this.selectOneByRole('timeman-shift-break-time');
		this.workTimeStartLink = this.selectOneByRole('timeman-shift-link-start-time');
		this.workTimeEndLink = this.selectOneByRole('timeman-shift-link-end-time');
		this.saveButton = this.selectOneByRole('timeman-shift-btn-save');
		this.cancelButton = this.selectOneByRole('timeman-shift-btn-cancel');
		this.workTimeStartInput = this.selectOneByRole('start-seconds-input');
		this.workTimeEndInput = this.selectOneByRole('end-seconds-input');
		this.shiftForm = this.selectOneByRole('timeman-shift-edit-form');
		this.workTimeToggle = this.selectOneByRole('timeman-shift-work-time-toggle');
		this.breakTimeToggle = this.selectOneByRole('timeman-shift-break-toggle');
		new BX.Timeman.Component.Popup.DurationPicker({
			durationInput: this.breakTimeLink,
			durationPopupToggle: this.breakTimeToggle,
			containerSelector: this.selfContainerSelector
		});
		this.durationWithoutBreak = this.selectOneByRole('duration-without-break');

		this.errorBlock = this.selectOneByRole('timeman-shift-edit-error-block');
		this.errorMsgTemplate = this.selectOneByRole('timeman-shift-edit-error-msg');

		this.initDurationWithoutBreak();

		var me = BX.Timeman.Component.Schedule.ShiftEdit;
		me.workTimeContent = this.getOrSetStaticElement('workTimeContent', '[data-role="timeman-shift-work-time-content"]');
		me.clockStartEndDeltaTime = this.getOrSetStaticElement('clockStartEndDeltaTime', '[data-role="timeman-work-time-start-end-delta"]');
		me.timeStartClockHiddenInput = this.getOrSetStaticElement('timeStartClockHiddenInput', '[name="startTimeHidden"]');
		me.timeEndClockHiddenInput = this.getOrSetStaticElement('timeEndClockHiddenInput', '[name="endTimeHidden"]');

		this.addEventHandlers();
	};
	BX.Timeman.Component.Schedule.ShiftEdit.prototype = {
		__proto__: BX.Timeman.Component.BaseComponent.prototype,
		constructor: BX.Timeman.Component.Schedule.ShiftEdit,
		getOrSetStaticElement: function (element, nodeSelector)
		{
			return BX.Timeman.Component.Schedule.ShiftEdit[element] ? BX.Timeman.Component.Schedule.ShiftEdit[element]
				: document.querySelector(nodeSelector);
		},
		addEventHandlers: function ()
		{
			BX.bind(this.saveButton, 'click', BX.delegate(this.onSaveClick, this));
			BX.bind(this.cancelButton, 'click', BX.delegate(this.onCancelClick, this));
			BX.bind(this.workTimeToggle, 'click', BX.delegate(this.onWorkTimeToggleClick, this));
			BX.bind(this.breakTimeLink, 'updateValue', BX.delegate(this.updateDurationWithoutBreak, this));
			BX.bind(this.breakTimeToggle, 'click', BX.delegate(this.onBreakTimeToggleClick, this));

			BX.bind(BX.Timeman.Component.Schedule.ShiftEdit.timeStartClockHiddenInput, 'change', BX.delegate(this.onWorkTimeChange, this));
			BX.bind(BX.Timeman.Component.Schedule.ShiftEdit.timeEndClockHiddenInput, 'change', BX.delegate(this.onWorkTimeChange, this));
		},
		onBreakTimeToggleClick: function (event)
		{
			this.initWorktimePopup(event);
		},
		onWorkTimeChange: function ()
		{
			if (!BX.Timeman.Component.Schedule.ShiftEdit._workTimePopup || BX.Timeman.Component.Schedule.ShiftEdit._workTimePopup.__currentContainer !== this.selfContainerSelector)
			{
				return;
			}
			this.workTimeStartLink.textContent = BX.Timeman.Component.Schedule.ShiftEdit.timeStartClockHiddenInput.value;
			this.workTimeEndLink.textContent = BX.Timeman.Component.Schedule.ShiftEdit.timeEndClockHiddenInput.value;
			this.workTimeStartInput.value = BX.Timeman.Component.Schedule.ShiftEdit.timeStartClockHiddenInput.value;
			this.workTimeEndInput.value = BX.Timeman.Component.Schedule.ShiftEdit.timeEndClockHiddenInput.value;
			this.updateDurationWithoutBreak();
		},
		setBreakSeconds: function(seconds)
		{
			this.breakTimeLink.value = this.beautifyTime(seconds);
			this.breakTimeToggle.textContent = this.beautifyTime(seconds);
			this.initDurationWithoutBreak();
		},
		updateDurationWithoutBreak: function ()
		{
			this.setDeltaTimeTo(this.durationWithoutBreak, BX.Timeman.Component.Schedule.ShiftEdit.clockStartEndDeltaTime, this.breakTimeLink);
		},
		setDeltaTimeTo: function (targetElement, minuend, subtrahend)
		{
			var delta = this.calculateDurationSeconds(minuend, subtrahend);
			targetElement.textContent = this.beautifyTimeLocal(delta);
			return targetElement.textContent;
		},
		calculateDurationSeconds: function (minuend, subtrahend)
		{
			var startSeconds = subtrahend;
			var endSeconds = minuend;
			if (subtrahend.tagName !== undefined)
			{
				startSeconds = subtrahend.tagName === 'INPUT' ? subtrahend.value : subtrahend.textContent;
			}
			if (minuend.tagName !== undefined)
			{
				endSeconds = minuend.tagName === 'INPUT' ? minuend.value : minuend.textContent;
			}
			startSeconds = this.convertFormattedTimeToSecs(startSeconds);
			endSeconds = this.convertFormattedTimeToSecs(endSeconds);
			var delta = 0;
			if (startSeconds < endSeconds)
			{
				delta = endSeconds - startSeconds;
			}
			else if (startSeconds > endSeconds)
			{
				delta = ((24 * 3600) - startSeconds) + endSeconds;
			}

			return delta;
		},
		convertFormattedTimeToSecs: function (time)
		{
			var q = time.split(/[\s:]+/);
			if (q.length === 3)
			{
				var mt = q[2];
				if (mt === 'pm' && q[0] < 12)
				{
					q[0] = parseInt(q[0], 10) + 12;
				}
				if (mt === 'am' && parseInt(q[0]) === 12)
				{
					q[0] = 0;
				}
			}
			return parseInt(q[0], 10) * 3600 + parseInt(q[1], 10) * 60;
		},
		initDurationWithoutBreak: function ()
		{
			var deltaEndStart = this.calculateDurationSeconds(this.workTimeEndLink, this.workTimeStartLink);
			this.durationWithoutBreak.textContent = this.beautifyTimeLocal(deltaEndStart - this.convertFormattedTimeToSecs(this.breakTimeLink.value));
		},
		beautifyTime: function (time, bSec)
		{
			var hours = parseInt(time / 3600);
			var mins = parseInt((time % 3600) / 60);
			var secs = time % 60;
			if (!!bSec)
			{
				return BX.util.str_pad(hours, 2, '0', 'left') + ':' + BX.util.str_pad(mins, 2, '0', 'left') + ':' + BX.util.str_pad(secs, 2, '0', 'left');
			}
			else
			{
				return BX.util.str_pad(hours, 2, '0', 'left') + ':' + BX.util.str_pad(mins, 2, '0', 'left');
			}
		},
		beautifyTimeLocal: function (time, bSec)
		{
			if (!!bSec)
			{
				return parseInt(time / 3600) + BX.message('JS_CORE_H') + ' ' + parseInt((time % 3600) / 60) + BX.message('JS_CORE_M') + ' ' + time % 60 + BX.message('JS_CORE_S');
			}
			else
			{
				return parseInt(time / 3600) + BX.message('JS_CORE_H') + ' ' + parseInt((time % 3600) / 60) + BX.message('JS_CORE_M');
			}
		},
		initWorktimePopup: function (event)
		{
			if (!BX.Timeman.Component.Schedule.ShiftEdit._workTimePopup)
			{
				BX.Timeman.Component.Schedule.ShiftEdit._workTimePopup = this.buildWorkTimePopup(event);
				BX.Timeman.Component.Schedule.ShiftEdit._workTimePopup.setContent(BX.Timeman.Component.Schedule.ShiftEdit.workTimeContent);
			}
			BX.Timeman.Component.Schedule.ShiftEdit._workTimePopup.setBindElement(event.currentTarget);
			BX.Timeman.Component.Schedule.ShiftEdit._workTimePopup.__currentContainer = '';

			this.setClockTime('bxClock_shiftStartTimeClock', this.convertFormattedTimeToSecs(this.workTimeStartLink.textContent));
			this.setClockTime('bxClock_shiftEndTimeClock', this.convertFormattedTimeToSecs(this.workTimeEndLink.textContent));

			BX.Timeman.Component.Schedule.ShiftEdit._workTimePopup.__currentContainer = this.selfContainerSelector;
		},
		onWorkTimeToggleClick: function (event)
		{
			this.initWorktimePopup(event);
			BX.Timeman.Component.Schedule.ShiftEdit._workTimePopup.show();
		},
		setClockTime: function (clockName, timestamp)
		{
			if (window[clockName])
			{
				window[clockName].config.AmPm = '';
				window[clockName].SetTime(parseInt(timestamp / 3600), parseInt((timestamp % 3600) / 60));
			}
		},
		buildWorkTimePopup: function (event)
		{
			return new BX.PopupWindow('js-timeman-clock-popup-' + Math.random(), event.currentTarget, {
				titleBar: BX.message('TIMEMAN_SHIFT_EDIT_POPUP_WORK_TIME_TITLE'),
				autoHide: true,
				closeIcon: true,
				closeByEsc: true,
				angle: true,
				contentColor: 'white',
				contentNoPaddings: true,
				buttons: [
					new BX.PopupWindowButton({
						text: BX.message('TIMEMAN_SHIFT_EDIT_BTN_SAVE_TITLE'),
						className: "popup-window-button-accept",
						events: {
							click: BX.delegate(function ()
							{
								BX.Timeman.Component.Schedule.ShiftEdit._workTimePopup.close();
							}, this)
						}
					}),
					new BX.PopupWindowButton({
						text: BX.message('TIMEMAN_SHIFT_EDIT_BTN_CANCEL_TITLE'),
						events: {
							click: BX.delegate(function ()
							{
								BX.Timeman.Component.Schedule.ShiftEdit._workTimePopup.close();
							}, this)
						}
					})
				],
			});
		},
		closeSlider: function ()
		{
			if (this.isSlider && window.top.BX.SidePanel && window.top.BX.SidePanel.Instance)
			{
				var slider = window.top.BX.SidePanel.Instance.getTopSlider();
				if (slider)
				{
					slider.close();
				}
			}
		},
		onCancelClick: function (event)
		{
			event.preventDefault();
			this.closeSlider();
		},
		onSaveClick: function (event)
		{
			event.stopPropagation();
			event.preventDefault();
			this.clearErrors();
			var formData = new FormData(this.shiftForm);
			BX.ajax.runAction(
				'timeman.shift.add',
				{
					data: formData
				}
			).then(
				function (response)
				{
					if (window.top.BX.SidePanel && window.top.BX.SidePanel.Instance)
					{
						window.top.BX.SidePanel.Instance.postMessageAll(window,
							formData.get('shiftId') ? 'BX.Timeman.Schedule.Shift.Update::Success' : 'BX.Timeman.Schedule.Shift.Add::Success',
							{
								schedule: response.data.schedule
							}
						);
					}
					this.closeSlider();
				}.bind(this),
				function (response)
				{
					this.showErrors(response.errors);
				}.bind(this));
		},
		clearErrors: function ()
		{
			if (this.errorBlock.childNodes)
			{
				for (var i = 0; i < this.errorBlock.childNodes.length; i++)
				{
					this.errorBlock.removeChild(this.errorBlock.childNodes[i]);
				}
			}
		},
		showErrors: function (errorMessages)
		{
			for (var i = 0; i < errorMessages.length; i++)
			{
				var erMsg = this.errorMsgTemplate.cloneNode(true);
				erMsg.classList.remove('main-ui-hide');
				erMsg.textContent = errorMessages[i].message;
				this.errorBlock.appendChild(erMsg);
			}
		},
	};

})();