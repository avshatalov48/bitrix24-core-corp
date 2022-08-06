;(function ()
{
	"use strict";
	BX.namespace('BX.Timeman.Component.Schedule.ShiftEdit');

	/**
	 * @param options
	 * @extends BX.Timeman.Component.Schedule.ShiftEdit
	 * @constructor
	 */
	BX.Timeman.Component.Schedule.ShiftEdit.Multiple = function (options)
	{
		this.observersData = [];
		BX.Timeman.Component.Schedule.ShiftEdit.apply(this, arguments);
		this.workdaysOptions = options.shiftWorkdaysOptions;
		this.customWorkdaysText = options.customWorkdaysText;
		this.shiftedScheduleTypeName = options.shiftedScheduleTypeName;
		this.uniqueIndex = options.uniqueIndex;
		this.options = options;
		this.isScheduleFixed = options.isScheduleFixed;
		if (options.visible)
		{
			var formFields = this.container.querySelectorAll('[name*="ShiftFormTemplate"]');
			for (var i = 0; i < formFields.length; i++)
			{
				formFields[i].name = formFields[i].name.replace(/ShiftFormTemplate/i, 'ShiftForm');
			}
			this.showElement(this.container);
		}
		if (this.uniqueIndex !== undefined)
		{
			var formFields = this.container.querySelectorAll('[name*="ShiftForm"]'); // hardcoded
			for (var i = 0; i < formFields.length; i++)
			{
				formFields[i].name = formFields[i].name.replace(/\[[0-9]+\]/i, '[' + this.uniqueIndex + ']')
			}
			var shiftId = this.selectOneByRole('shift-id');
			if (shiftId)
			{
				shiftId.value = '';
			}
		}
		this.workdaysToggle = this.selectOneByRole('timeman-schedule-workdays-toggle');
		this.workdaysInput = this.selectOneByRole('timeman-schedule-shift-workdays-input');
		this.workDaysSelector = this.selectOneByRole('timeman-schedule-shift-workdays-selector');
		this.pencil = this.selectOneByRole('timeman-schedule-shift-pencil-name');
		this.nameInput = this.selectOneByRole('timeman-schedule-shift-name-input');
		this.workdaysBlocks = this.selectAllByRole('timeman-schedule-shift-work-days');
		this.nameBlock = this.selectOneByRole('timeman-schedule-shift-name-input-block');
		this.workDaysBlock = this.selectOneByRole('timeman-schedule-shift-workdays-block');
		this.nameSpan = this.selectOneByRole('timeman-schedule-shift-name-span');
		this.deleteSelfBtn = this.selectOneByRole('timeman-schedule-form-worktime-delete-btn');
		if (this.uniqueIndex !== undefined && this.uniqueIndex > 0)
		{
			this.showElement(this.deleteSelfBtn);
			this.nameInput.value = options.defaultName;
			this.nameSpan.textContent = this.nameInput.value;
			if (options.prevShiftEnd)
			{
				this.workTimeStartLink.textContent = options.prevShiftEnd;
				this.workTimeStartInput.value = this.workTimeStartLink.textContent;
			}
			if (options.prevShiftStart)
			{
				var prevDuration = this.calculateDurationSeconds(options.prevShiftEnd, options.prevShiftStart);
				this.workTimeEndLink.textContent = this.beautifyTime((this.convertFormattedTimeToSecs(options.prevShiftEnd) + prevDuration) % 86400);
				this.workTimeEndInput.value = this.workTimeEndLink.textContent;
			}
			for (var i = 0; i < this.workdaysBlocks.length; i++)
			{
				var id = this.workdaysBlocks[i].querySelector('input').id.replace(/-[0-9]-/, '-' + this.uniqueIndex + '-');
				this.workdaysBlocks[i].querySelector('input').id = id;
				this.workdaysBlocks[i].querySelector('label').htmlFor = id;
			}
			if (options.isScheduleShifted)
			{
				this.setBreakSeconds(0);
			}
		}
		this.initDurationWithoutBreak();
		this.addSelfEventHandlers();
	};

	BX.Timeman.Component.Schedule.ShiftEdit.Multiple.prototype = {
		__proto__: BX.Timeman.Component.Schedule.ShiftEdit.prototype,
		constructor: BX.Timeman.Component.Schedule.ShiftEdit.Multiple,
		addSelfEventHandlers: function ()
		{
			BX.bind(this.workdaysToggle, 'click', BX.delegate(this.showWorkdaysOptionsPopup, this));
			BX.bind(this.pencil, 'click', BX.delegate(this.startNameEdit, this));
			BX.bind(this.nameInput, 'blur', this.endNameEdit.bind(this));
			BX.bind(this.deleteSelfBtn, 'click', BX.delegate(this.onDeleteSelfBtnClick, this));
		},
		processBeforeCollectFormData: function ()
		{
			if (this.workdaysToggle.innerText !== this.customWorkdaysText)
			{
				return;
			}
			var workDaysItems = this.selectAllByRole('timeman-schedule-shift-work-day-item');
			var workdays = [];
			for (var i = 0; i < workDaysItems.length; i++)
			{
				if (workDaysItems[i].checked)
				{
					workdays.push(workDaysItems[i].value);
				}
			}
			workdays = workdays.sort();
			this.workdaysInput.value = workdays.join('');
		},
		getFormattedDeltaTime: function (minuend, subtrahend)
		{
			return this.beautifyTimeLocal(this.calculateDurationSeconds(minuend, subtrahend));
		},
		onScheduleTypeSelected: function (selectedValue)
		{
			this.isScheduleFixed = selectedValue === this.options.fixedScheduleTypeName;
			this.hideElement(this.workDaysSelector);
			if (this.isScheduleFixed)
			{
				this.showWorkdaysBySelectedValue(this.workdaysToggle.textContent);
			}
			if (selectedValue === this.shiftedScheduleTypeName)
			{
				if (this.convertFormattedTimeToSecs(this.breakTimeLink.value) === 3600)
				{
					this.setBreakSeconds(0);
				}
				this.showElement(this.nameBlock);
				this.endNameEdit();
				this.hideElement(this.workDaysBlock);
			}
			else
			{
				this.hideElement(this.nameBlock);
				this.showElement(this.workDaysBlock);
			}
		},
		endNameEdit: function (event)
		{
			this.nameSpan.textContent = this.nameInput.value;

			this.hideElement(this.nameInput);
			this.showElement(this.nameSpan);
			this.showElement(this.pencil);
		},
		startNameEdit: function (event)
		{
			this.showElement(this.nameInput);
			this.hideElement(this.nameSpan);
			this.hideElement(this.pencil);
			this.nameInput.focus();
		},
		attachOnDeleteEvent: function (obj)
		{
			this.observersData.push({eventType: 'onDelete', observer: obj});
		},
		onDeleteSelfBtnClick: function (event)
		{
			if (document.querySelectorAll('[data-role^="timeman-schedule-shift-form-container"]').length > 1) // hardcoded
			{
				for (var i = 0; i < this.observersData.length; i++)
				{
					if (this.observersData[i].eventType === 'onDelete')
					{
						this.observersData[i].observer.updateOnShiftEvent({eventType: 'onDelete', shift: this});
					}
				}
				this.container.remove();
			}
		},
		buildWorkdaysPopup: function ()
		{
			var menuItems = [];
			for (var i = 0; i < this.workdaysOptions.length; i++)
			{
				var item = this.workdaysOptions[i];
				menuItems.push({
					text: BX.util.htmlspecialchars(item.title),
					dataset: {
						title: item.title,
						value: item.id
					},
					onclick: function (event, item)
					{
						this.showWorkdaysBySelectedValue(item.dataset.title);
						this.workdaysInput.value = BX.util.htmlspecialchars(item.dataset.value);
						this.workdaysToggle.textContent = item.dataset.title;
						this.workdaysPopup.close();
					}.bind(this)
				});
			}

			return BX.PopupMenu.create(
				'timeman-shift-workdays-' + Math.random(),
				this.workdaysToggle,
				menuItems,
				{
					autoHide: true
				}
			);
		},
		showWorkdaysBySelectedValue: function (value)
		{
			this.hideElement(this.workDaysSelector);
			if (value === this.options.customWorkdaysText && this.isScheduleFixed)
			{
				this.showElement(this.workDaysSelector)
			}
		},
		showWorkdaysOptionsPopup: function (event)
		{
			if (!this.workdaysPopup)
			{
				this.workdaysPopup = this.buildWorkdaysPopup();
			}
			this.workdaysPopup.show();
		}
	}
})();