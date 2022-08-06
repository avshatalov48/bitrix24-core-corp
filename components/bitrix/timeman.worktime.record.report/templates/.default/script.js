;(function ()
{
	BX.namespace('BX.Timeman.Component.Worktime.Record');
	/**
	 * @param options
	 * @extends BX.Timeman.Component.BaseComponent
	 * @constructor
	 */
	BX.Timeman.Component.Worktime.Record.Report = function (options)
	{
		BX.Timeman.Component.BaseComponent.apply(this, arguments);
		this.isSlider = options.isSlider;
		this.isShiftplan = options.isShiftplan;
		this.useEmployeesTimezone = options.useEmployeesTimezone;
		this.editWorktimeBtn = this.selectOneByRole('edit-worktime-btn');
		this.changeWorktimeBtn = this.selectOneByRole('change-worktime-btn');
		this.workTimePickerContent = this.selectOneByRole('timeman-time-picker-content');
		this.saveButton = this.selectOneByRole('tm-record-btn-save');
		this.changeButton = this.selectOneByRole('tm-record-btn-change');
		this.cancelBtn = this.selectOneByRole('tm-record-btn-cancel');
		this.recordForm = this.selectOneByRole('worktime-record-form');

		this.formEndInput = this.container.querySelector('[name="' + options.endTimeFormHiddenInputName + '"]');
		this.formStartInput = this.container.querySelector('[name="' + options.startTimeFormHiddenInputName + '"]');
		this.formBreakInput = this.container.querySelector('[name="' + options.breakLengthTimeFormHiddenInputName + '"]');

		this.timeEndClockHiddenInput = this.container.querySelector('[name="endTime"]');
		this.timeStartClockHiddenInput = this.container.querySelector('[name="startTime"]');
		this.breakLengthClockHiddenInput = this.container.querySelector('[name="breakLength"]');

		this.startTimeSpan = this.selectOneByRole('start-time');
		this.endTimeSpan = this.selectOneByRole('end-time');
		this.breakTimeSpan = this.selectOneByRole('break-time');
		this.errorsBlock = this.selectOneByRole('timeman-record-report-errors-block');
		this.errorBlockTemplate = this.selectOneByRole('timeman-error-msg');
		this.breakTimeContainer = this.selectOneByRole('break-time-container');
		this.durationTimeSpan = this.selectOneByRole('duration-time');
		this.edited = false;
		BX.UI.Hint.init(this.container);
		this.addEventHandlers();
	};
	BX.Timeman.Component.Worktime.Record.Report.prototype = {
		__proto__: BX.Timeman.Component.BaseComponent.prototype,
		constructor: BX.Timeman.Component.Worktime.Record.Report,
		addEventHandlers: function ()
		{
			BX.bind(this.editWorktimeBtn, 'click', BX.delegate(this.onEditWorktimeClick, this));
			BX.bind(this.changeWorktimeBtn, 'click', BX.delegate(this.onChangeWorktimeClick, this));
			BX.bind(this.saveButton, 'click', BX.delegate(this.onSaveClick, this));
			BX.bind(this.changeButton, 'click', BX.delegate(this.onChangeClick, this));
			BX.bind(this.cancelBtn, 'click', BX.delegate(this.closeSlider, this));
			BX.bind(document, 'keydown', BX.proxy(this.onKeyDown, this));
			var navBtns = this.selectAllByRole('navigation-record', document);
			for (var i = 0; i < navBtns.length; i++)
			{
				BX.bind(navBtns[i], 'click', BX.delegate(this.onRecordNavigationClick, this));
			}
			var moreActivityBtns = this.selectAllByRole('show-more', document);
			for (var i = 0; i < moreActivityBtns.length; i++)
			{
				BX.bind(moreActivityBtns[i], 'click', BX.delegate(this.onShowMoreActivityClick, this));
			}
		},
		onShowMoreActivityClick: function (event)
		{
			if (!(event.currentTarget.dataset && event.currentTarget.dataset.showId))
			{
				return;
			}

			var elements = this.selectAllByRole(event.currentTarget.dataset.showId);

			if (event.currentTarget.dataExpanded)
			{
				event.currentTarget.innerText = event.currentTarget.dataOldTitle;
				for (var i = 0; i < elements.length; i++)
				{
					this.hideElement(elements[i]);
				}
			}
			else
			{
				for (var i = 0; i < elements.length; i++)
				{
					this.showElement(elements[i]);
				}
				if (!event.currentTarget.dataOldTitle)
				{
					event.currentTarget.dataOldTitle = event.currentTarget.innerText;
				}
				event.currentTarget.innerText = BX.util.htmlspecialchars(BX.message('TM_RECORD_REPORT_ROLL_UP_TITLE'));
			}

			event.currentTarget.dataExpanded = !event.currentTarget.dataExpanded;
		},

		onRecordNavigationClick: function (e)
		{
			event.stopPropagation();
			event.preventDefault();
			if (e.currentTarget.dataset.url)
			{
				document.location.href = e.currentTarget.dataset.url;
			}
		},

		onKeyDown: function (e)
		{
			if (e.keyCode === 27) // escape
			{
				this.closeSlider();
			}
		},

		onEditWorktimeClick: function (event)
		{
			if (!this._workTimePickerPopup)
			{
				this._workTimePickerPopup = this.buildWorkTimePopup(event);
				this._workTimePickerPopup.setContent(this.workTimePickerContent);
			}

			this._workTimePickerPopup.show();
		},

		onChangeWorktimeClick: function (event)
		{
			if (!this._workTimePickerPopup)
			{
				this._workTimePickerPopup = this.buildWorkTimePopup(event);
				this._workTimePickerPopup.setContent(this.workTimePickerContent);
			}

			this._workTimePickerPopup.show();
		},

		onSaveClick: function (event)
		{
			event.stopPropagation();
			event.preventDefault();
			this.clearErrors();
			if (event.currentTarget.dataset && event.currentTarget.dataset.action === 'save' && !this.edited)
			{
				return;
			}
			if (this.saving === true)
			{
				return;
			}
			this.saving = true;
			this.saveButton.classList.add('ui-btn-wait');
			var formData = new FormData(this.recordForm);
			formData.append('isShiftplan', this.isShiftplan);
			formData.append('useEmployeesTimezone', this.useEmployeesTimezone);
			BX.ajax.runAction(
				'timeman.worktime.approveRecord',
				{
					data: formData
				}
			).then(
				function (response)
				{
					this.saving = false;
					if (this.isSlider)
					{
						if (this.getSlider())
						{
							this.getSlider().postMessageAll(window,
								'BX.Timeman.Record.Approve::Success',
								{
									record: response.data.record
								}
							);
						}
						this.closeSlider();
					}
					else
					{
						BX.reload();
					}
				}.bind(this),
				function (response)
				{
					this.saving = false;
					this.saveButton.classList.remove('ui-btn-wait');
					this.showErrors(response.errors);
				}.bind(this));
		},

		onChangeClick: function (event)
		{
			event.stopPropagation();
			event.preventDefault();

			this.clearErrors();

			if (
				event.currentTarget.dataset
				&& event.currentTarget.dataset.action === 'save'
				&& !this.edited
			)
			{
				return;
			}

			if (this.saving === true)
			{
				return;
			}
			this.saving = true;

			this.changeButton.classList.add('ui-btn-wait');

			var formData = new FormData(this.recordForm);
			formData.append('isShiftplan', this.isShiftplan);
			formData.append('useEmployeesTimezone', this.useEmployeesTimezone);
			BX.ajax.runAction(
				'timeman.worktime.changeRecord',
				{
					data: formData
				}
			).then(
				function (response)
				{
					this.saving = false;
					if (this.isSlider)
					{
						if (this.getSlider())
						{
							this.getSlider().postMessageAll(window,
								'BX.Timeman.Record.Approve::Success',
								{
									record: response.data.record
								}
							);
						}
						this.closeSlider();
					}
					else
					{
						BX.reload();
					}
				}.bind(this),
				function (response)
				{
					this.saving = false;
					this.changeButton.classList.remove('ui-btn-wait');
					this.showErrors(response.errors);
				}.bind(this));
		},

		getSlider: function ()
		{
			if (window.top.BX.SidePanel && window.top.BX.SidePanel.Instance)
			{
				return window.top.BX.SidePanel.Instance;
			}
			return null;
		},

		clearErrors: function ()
		{
			if (this.errorsBlock.childNodes)
			{
				for (var i = 0; i < this.errorsBlock.childNodes.length; i++)
				{
					this.errorsBlock.removeChild(this.errorsBlock.childNodes[i]);
				}
			}
		},

		showErrors: function (errorMessages)
		{
			for (var i = 0; i < errorMessages.length; i++)
			{
				var erMsg = this.errorBlockTemplate.cloneNode(true);
				this.showElement(erMsg);
				erMsg.textContent = errorMessages[i].message;
				this.errorsBlock.appendChild(erMsg);
			}
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

		onRecordedTimeUpdated: function ()
		{
			this.startTimeSpan.textContent = this.timeStartClockHiddenInput.value;
			this.formStartInput.value = this.timeStartClockHiddenInput.value;
			this.endTimeSpan.textContent = this.timeEndClockHiddenInput.value;
			this.formEndInput.value = this.timeEndClockHiddenInput.value;
			if (this.convertFormattedTimeToSecs(this.breakLengthClockHiddenInput.value) > 0)
			{
				this.showElement(this.breakTimeContainer);
			}
			this.breakTimeSpan.textContent = this.breakLengthClockHiddenInput.value;
			this.formBreakInput.value = this.breakLengthClockHiddenInput.value;
			this.durationTimeSpan.textContent = this.selectOneByRole('timeman-work-time-start-end-delta', this.workTimePickerContent).textContent;
		},

		buildWorkTimePopup: function (event)
		{
			return new BX.PopupWindow({
				bindElement: event.currentTarget,
				titleBar: BX.message('TIMEMAN_POPUP_WORK_TIME_TITLE'),
				autoHide: false,
				closeIcon: true,
				closeByEsc: true,
				angle: true,
				contentColor: 'white',
				contentNoPaddings: true,
				buttons: [
					new BX.PopupWindowButton({
						text: BX.message('TIMEMAN_BTN_SAVE_TITLE'),
						className: "popup-window-button-accept",
						events: {
							click: BX.delegate(function ()
							{
								this.edited = true;

								if (this.saveButton)
								{
									this.saveButton.classList.remove('ui-btn-disabled');
								}

								if (this.changeButton)
								{
									this.changeButton.classList.remove('ui-btn-disabled');
								}

								this._workTimePickerPopup.close();
								this.onRecordedTimeUpdated();
							}, this)
						}
					}),
					new BX.PopupWindowButton({
						text: BX.message('TIMEMAN_BTN_CANCEL_TITLE'),
						events: {
							click: BX.delegate(function ()
							{
								this._workTimePickerPopup.close();
							}, this)
						}
					})
				],
			});
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