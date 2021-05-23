(function ()
{
	'use strict';

	BX.namespace('BX.Timeman.Component.Schedule.Edit.Violations');
	/**
	 * @extends BX.Timeman.Component.BaseComponent
	 * @param options
	 * @constructor
	 */
	BX.Timeman.Component.Schedule.Edit.Violations = function (options)
	{
		options.containerSelector = '[data-role="violations-container"]';
		BX.Timeman.Component.BaseComponent.apply(this, arguments);
		this.containerShowToggle = this.selectOneByRole('timeman-schedule-violation-toggle');
		this.exactTimeBlock = this.selectOneByRole('exact-time-block-toggle');
		this.relativeTimeRadioBtn = this.selectOneByRole('relative-time-block-input');
		this.exactTimeRadioBtn = this.selectOneByRole('exact-time-block-input');
		this.offsetTimeRadioBtn = this.selectOneByRole('offset-time-block-input');
		this.errorMsgTemplate = this.selectOneByRole('timeman-violations-error-msg-block');
		this.errorBlock = this.selectOneByRole('timeman-violations-edit-error-block');
		this.options = options;
		this.isSlider = options.isSlider;
		this.relativeTimeBlock = this.selectOneByRole('relative-time-block-toggle');
		this.offsetTimeBlock = this.selectOneByRole('offset-time-block-toggle');
		this.cssClassSelectedBlock = 'timeman-schedule-form-violation-option-selected';
		this.startControls = this.selectAllByRole('start-control');
		this.endControls = this.selectAllByRole('end-control');
		this.minExactEndLink = this.selectOneByRole('min-exact-end-link');
		this.maxExactStartLink = this.selectOneByRole('max-exact-start-link');

		new BX.Timeman.Component.Popup.DurationPicker({
			durationInput: this.selectOneByRole('min-offset-end-input'),
			durationPopupToggle: this.selectOneByRole('min-offset-end-link'),
			containerSelector: options.containerSelector
		});
		new BX.Timeman.Component.Popup.DurationPicker({
			durationInput: this.selectOneByRole('max-offset-start-input'),
			durationPopupToggle: this.selectOneByRole('max-offset-start-link'),
			containerSelector: options.containerSelector
		});

		new BX.Timeman.Component.Popup.DurationPicker({
			durationInput: this.selectOneByRole('min-day-duration-input'),
			durationPopupToggle: this.selectOneByRole('min-day-duration-link'),
			containerSelector: options.containerSelector
		});

		new BX.Timeman.Component.Popup.DurationPicker({
			durationInput: this.selectOneByRole('allow-manual-change-time-input'),
			durationPopupToggle: this.selectOneByRole('allow-manual-change-time-link'),
			containerSelector: options.containerSelector
		});
		this.relativeStartTo = this.selectOneByRole('relative-start-to-link');
		this.relativeStartFrom = this.selectOneByRole('relative-start-from-link');
		this.relativeEndTo = this.selectOneByRole('relative-end-to-link');
		this.relativeEndFrom = this.selectOneByRole('relative-end-from-link');
		new BX.Timeman.Component.Popup.DurationPicker({
			durationInput: this.selectOneByRole('allow-shift-start-delay-input'),
			durationPopupToggle: this.selectOneByRole('allow-shift-start-delay-link'),
			containerSelector: options.containerSelector
		});
		this.plainTimeContent = this.selectOneByRole('plain-time-content');
		this.plainTimeHiddenInput = this.container.querySelector('[name="plainTimeHidden"]');
		this.fixViolations = this.selectAllByRole('violation-fix-schedule');
		this.shiftViolations = this.selectAllByRole('violation-shift-schedule');
		this.universalViolations = this.selectAllByRole('violation-any-schedule');
		this.form = this.selectOneByRole('timeman-violations-personal-form');
		this.savePersonalBtn = document.querySelector('#tm-schedule-personal-violations-save');
		this._addEventHandlers();
		if (this.options.scheduleType)
		{
			this.redrawFormByScheduleType(this.options.scheduleType, this.options.controlType);
		}
		BX.UI.Hint.init(this.container);
	};

	BX.Timeman.Component.Schedule.Edit.Violations.prototype = {
		__proto__: BX.Timeman.Component.BaseComponent.prototype,
		constructor: BX.Timeman.Component.Schedule.Edit.Violations,
		_addEventHandlers: function ()
		{
			BX.bind(this.savePersonalBtn, 'click', BX.delegate(this._onSaveClick, this));
			BX.bind(this.containerShowToggle, 'click', BX.delegate(this._onContainerShow, this));
			BX.bind(this.exactTimeBlock, 'click', BX.delegate(this._onExactTimeBlockClick, this));
			BX.bind(this.offsetTimeBlock, 'click', BX.delegate(this._onOffsetTimeBlockClick, this));
			BX.bind(this.relativeTimeBlock, 'click', BX.delegate(this._onRelativeTimeBlockClick, this));

			BX.bind(this.minExactEndLink, 'click', BX.delegate(this.onPlainTimePickerToggleClick, this));
			BX.bind(this.maxExactStartLink, 'click', BX.delegate(this.onPlainTimePickerToggleClick, this));
			BX.bind(this.relativeStartFrom, 'click', BX.delegate(this.onPlainTimePickerToggleClick, this));
			BX.bind(this.relativeStartTo, 'click', BX.delegate(this.onPlainTimePickerToggleClick, this));
			BX.bind(this.relativeEndFrom, 'click', BX.delegate(this.onPlainTimePickerToggleClick, this));
			BX.bind(this.relativeEndTo, 'click', BX.delegate(this.onPlainTimePickerToggleClick, this));

			BX.bind(this.plainTimeHiddenInput, 'change', BX.delegate(this.onPlainTimeChange, this));
		},
		redrawFormByScheduleType: function (scheduleType, controlType)
		{
			this.showElement(this.container);
			if (this.isShiftedSchedule(scheduleType))
			{
				this.showViolations(this.universalViolations);
				this.hideViolations(this.fixViolations);
				this.showViolations(this.shiftViolations);
			}
			else if (this.isFlextimeSchedule(scheduleType))
			{
				this.hideElement(this.container);
			}
			else
			{
				this.showViolations(this.universalViolations);
				this.showViolations(this.fixViolations);
				this.hideViolations(this.shiftViolations);
			}
			if (controlType)
			{
				if (this.isControlledStart(controlType))
				{
					for (var i = 0; i < this.endControls.length; i++)
					{
						this.hideElement(this.endControls[i]);
					}
					for (var j = 0; j < this.startControls.length; j++)
					{
						this.showElement(this.startControls[j]);
					}
				}
				else if (this.isControlledStartEnd(controlType))
				{
					for (var i = 0; i < this.endControls.length; i++)
					{
						this.showElement(this.endControls[i]);
					}
					for (var j = 0; j < this.startControls.length; j++)
					{
						this.showElement(this.startControls[j]);
					}
				}
			}
		},
		isControlledStart: function (type)
		{
			return parseInt(type) === parseInt(this.options.controlledStart);
		},
		isControlledStartEnd: function (type)
		{
			return parseInt(type) === parseInt(this.options.controlledStartEnd);
		},
		isFixedSchedule: function (value)
		{
			return value === this.options.fixedScheduleTypeName;
		},
		isFlextimeSchedule: function (value)
		{
			return value === this.options.flextimeScheduleTypeName;
		},
		isShiftedSchedule: function (value)
		{
			return value === this.options.shiftedScheduleTypeName;
		},
		showViolations: function (violations)
		{
			for (var i = 0; i < violations.length; i++)
			{
				this.showElement(violations[i]);
			}
		},
		hideViolations: function (violations)
		{
			for (var i = 0; i < violations.length; i++)
			{
				this.hideElement(violations[i]);
			}
		},
		onPlainTimeChange: function ()
		{
			if (!BX.Timeman.Component.Schedule.Edit.Violations._plainTimePopup || !BX.Timeman.Component.Schedule.Edit.Violations._plainTimePopup.__currentContainer)
			{
				return;
			}
			var container = BX.Timeman.Component.Schedule.Edit.Violations._plainTimePopup.__currentContainer;
			container.textContent = this.plainTimeHiddenInput.value;
			if (container.dataset && container.dataset.inputSelectorRole)
			{
				var input = this.selectOneByRole(container.dataset.inputSelectorRole);
				if (input)
				{
					input.value = container.textContent;
				}
			}
		},
		onPlainTimePickerToggleClick: function (event)
		{
			if (!BX.Timeman.Component.Schedule.Edit.Violations._plainTimePopup)
			{
				BX.Timeman.Component.Schedule.Edit.Violations._plainTimePopup = this.buildPlainTimePopup(event);
				this.showElement(this.plainTimeContent);
				BX.Timeman.Component.Schedule.Edit.Violations._plainTimePopup.setContent(this.plainTimeContent);
			}
			BX.Timeman.Component.Schedule.Edit.Violations._plainTimePopup.setBindElement(event.currentTarget);
			BX.Timeman.Component.Schedule.Edit.Violations._plainTimePopup.__currentContainer = null;
			this.setClockTime('bxClock_plainTimeClock', this.convertFormattedTimeToSecs(event.target.textContent));

			BX.Timeman.Component.Schedule.Edit.Violations._plainTimePopup.__currentContainer = event.target;
			BX.Timeman.Component.Schedule.Edit.Violations._plainTimePopup.show();
		},
		setClockTime: function (clockName, timestamp)
		{
			if (window[clockName])
			{
				window[clockName].config.AmPm = '';
				window[clockName].SetTime(parseInt(timestamp / 3600), parseInt((timestamp % 3600) / 60));
			}
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
		},
		buildPlainTimePopup: function (event)
		{
			return new BX.PopupWindow('timeman-plain-time-' + Math.random(), event.currentTarget, {
				titleBar: BX.message('TIMEMAN_SHIFT_EDIT_POPUP_PICK_TIME_TITLE'),
				autoHide: true,
				closeByEsc: true,
				angle: true,
				contentColor: 'white',
				contentNoPaddings: true,
				buttons: [
					new BX.PopupWindowButton({
						text: BX.message('TIMEMAN_SHIFT_EDIT_BTN_SET_TITLE'),
						className: 'popup-window-button-blue',
						events: {
							click: BX.delegate(function ()
							{
								BX.Timeman.Component.Schedule.Edit.Violations._plainTimePopup.close();
							}, this)
						}
					})
				]
			});
		},
		_onRelativeTimeBlockClick: function (e)
		{
			this.relativeTimeBlock.classList.add(this.cssClassSelectedBlock);
			this.offsetTimeBlock.classList.remove(this.cssClassSelectedBlock);
			this.exactTimeBlock.classList.remove(this.cssClassSelectedBlock);
			this.relativeTimeRadioBtn.checked = true;
			this.exactTimeRadioBtn.checked = false;
			this.offsetTimeRadioBtn.checked = false;
		},
		_onExactTimeBlockClick: function (e)
		{
			this.offsetTimeBlock.classList.remove(this.cssClassSelectedBlock);
			this.relativeTimeBlock.classList.remove(this.cssClassSelectedBlock);
			this.exactTimeBlock.classList.add(this.cssClassSelectedBlock);
			this.exactTimeRadioBtn.checked = true;
			this.relativeTimeRadioBtn.checked = false;
			this.offsetTimeRadioBtn.checked = false;
		},
		_onOffsetTimeBlockClick: function (e)
		{
			this.offsetTimeBlock.classList.add(this.cssClassSelectedBlock);
			this.relativeTimeBlock.classList.remove(this.cssClassSelectedBlock);
			this.exactTimeBlock.classList.remove(this.cssClassSelectedBlock);
			this.offsetTimeRadioBtn.checked = true;
			this.relativeTimeRadioBtn.checked = false;
			this.exactTimeRadioBtn.checked = false;
		},
		_onContainerShow: function ()
		{
			this.container.classList.toggle('timeman-schedule-form-wrap-open')
		},
		clearErrors: function (errorBlock)
		{
			if (errorBlock.childNodes)
			{
				for (var i = errorBlock.childNodes.length - 1; i >= 0; i--)
				{
					errorBlock.childNodes[i].remove();
				}
			}
		},
		_onSaveClick: function (event)
		{
			if (this.saveDisabled)
			{
				return;
			}
			this.saveDisabled = true;
			this.clearErrors(this.errorBlock);
			var formData = new FormData(this.form);
			BX.ajax.runAction(
				'timeman.violationRules.save',
				{
					data: formData
				}
			).then(
				function (response)
				{
					this.closeSlider();
				}.bind(this),
				function (response)
				{
					this.saveDisabled = false;
					this.savePersonalBtn.classList.remove('ui-btn-wait');
					this.showErrors(response.errors);
				}.bind(this)
			);
		},

		showErrors: function (errorMessages)
		{
			for (var i = 0; i < errorMessages.length; i++)
			{
				var erMsg = this.errorMsgTemplate.cloneNode(true);
				erMsg.textContent = errorMessages[i].message;
				this.errorBlock.appendChild(erMsg);
				this.showElement(erMsg);
			}
			if (errorMessages.length > 0)
			{
				window.scrollTo(0, 0);
			}
		},
		getSlider: function ()
		{
			if (window.top.BX.SidePanel && window.top.BX.SidePanel.Instance)
			{
				return window.top.BX.SidePanel.Instance;
			}
			return null;
		},
		closeSlider: function ()
		{
			if (this.isSlider && this.getSlider())
			{
				this.getSlider().getTopSlider().close();
			}
		}
	}
})();