;(function ()
{
	BX.namespace('BX.Timeman.Component.Popup');
	/**
	 * @param options
	 * @extends BX.Timeman.Component.BaseComponent
	 * @constructor
	 */
	BX.Timeman.Component.Popup.TimePicker = function (options)
	{
		BX.Timeman.Component.BaseComponent.apply(this, arguments);
		this.startHiddenInput = options.inputStartId ? this.container.querySelector('#' + options.inputStartId) : null;
		this.endHiddenInput = options.inputEndId ? this.container.querySelector('#' + options.inputEndId) : null;
		this.clockStartEndDeltaTime = this.selectOneByRole('timeman-work-time-start-end-delta');
		this.breakLengthInput = this.selectOneByRole('tm-time-picker-break-length');
		this.startDateInput = options.startDateInputSelector ? this.selectOneByRole(options.startDateInputSelector, document) : null;
		this.endDateInput = options.endDateInputSelector ? this.selectOneByRole(options.endDateInputSelector, document) : null;
		this.startDateDefault = options.startDateDefault;
		this.endDateDefault = options.endDateDefault;
		var datePickers = this.selectAllByRole('date-picker');
		for (var i = 0; i < datePickers.length; i++)
		{
			if (datePickers[i].dataset.type === 'start')
			{
				this.startDateLink = datePickers[i];
			}
			else if (datePickers[i].dataset.type === 'end')
			{
				this.endDateLink = datePickers[i];
			}
		}
		this.addEventHandlers();
	};
	BX.Timeman.Component.Popup.TimePicker.prototype = {
		__proto__: BX.Timeman.Component.BaseComponent.prototype,
		constructor: BX.Timeman.Component.Popup.TimePicker,
		addEventHandlers: function ()
		{
			BX.bind(this.startHiddenInput, 'change', BX.delegate(this.onStartHiddenInputChange, this));
			BX.bind(this.endHiddenInput, 'change', BX.delegate(this.onEndHiddenInputChange, this));
			BX.bind(this.breakLengthInput, 'change', BX.delegate(this.onBreakLengthInputChange, this));
			BX.bind(this.startDateLink, 'click', BX.delegate(this.onDateLinkClick, this));
			BX.bind(this.endDateLink, 'click', BX.delegate(this.onDateLinkClick, this));
		},
		updateDuration: function (event)
		{
			this.setDeltaTimeTo(this.clockStartEndDeltaTime, this.endHiddenInput, this.startHiddenInput);
			if (this.breakLengthInput)
			{
				this.setDeltaTimeTo(this.clockStartEndDeltaTime, this.clockStartEndDeltaTime, this.breakLengthInput);
			}
		},
		onDateLinkClick: function (event)
		{
			var defaultDate = new Date();
			if (event.currentTarget.dataset.type === 'start' && this.startDateDefault !== undefined)
			{
				defaultDate = new Date(this.startDateDefault);
			}
			else if (event.currentTarget.dataset.type === 'end' && this.endDateDefault !== undefined)
			{
				defaultDate = new Date(this.endDateDefault);
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
							if (event.currentTarget.dataset.type === 'start')
							{
								this.startDateInput.value = event.currentTarget.value;
							}
							else if (event.currentTarget.dataset.type === 'end')
							{
								this.endDateInput.value = event.currentTarget.value;
							}
						}, this
					)
				}
			});
			title.dataset.role = event.currentTarget.dataset.role;
			title.dataset.type = event.currentTarget.dataset.type;

			event.currentTarget.parentNode.appendChild(title);
			title.style.width = title.value.length.toString() + 'px!important';
			event.currentTarget.classList.add('timeman-hide');
			BX.calendar({node: title, field: title, bTime: false});
		},
		onBreakLengthInputChange: function (event)
		{
			if (this.breakLengthInput.value.match(/^[0-9]$/))
			{
				this.breakLengthInput.value = '0' + this.breakLengthInput.value + ':00';
			}
			else if (this.breakLengthInput.value.match(/^[0-9]{2}$/))
			{
				this.breakLengthInput.value = this.breakLengthInput.value + ':00';
			}
			else if (!this.breakLengthInput.value.match(/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/))
			{
				this.breakLengthInput.value = '00:00';
			}

			this.updateDuration(event);
		},
		onEndHiddenInputChange: function (event)
		{
			this.updateDuration(event);
		},
		onStartHiddenInputChange: function (event)
		{
			this.updateDuration(event);
		},
		setDeltaTimeTo: function (targetElement, minuend, subtrahend)
		{
			var delta = this.getDeltaTime(minuend, subtrahend);
			targetElement.textContent = this.beautifyTime(delta);
			return targetElement.textContent;
		},
		beautifyTime: function (time, bSec)
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
		getDeltaTime: function (minuend, subtrahend)
		{
			var startSeconds = this.convertFormattedTimeToSecs(subtrahend.tagName === 'INPUT' ? subtrahend.value : subtrahend.textContent);
			var endSeconds = this.convertFormattedTimeToSecs(minuend.tagName === 'INPUT' ? minuend.value : minuend.textContent);
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
				if (mt === 'am' && q[0] === 12)
				{
					q[0] = 0;
				}
			}
			return parseInt(q[0], 10) * 3600 + parseInt(q[1], 10) * 60;
		}
	};
})();