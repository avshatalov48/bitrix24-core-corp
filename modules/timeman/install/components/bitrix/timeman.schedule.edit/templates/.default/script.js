(function ()
{
	'use strict';

	BX.namespace('BX.Timeman.Component.Schedule.Edit');
	/**
	 *
	 * @extends BX.Timeman.Component.BaseComponent
	 * @constructor
	 */
	BX.Timeman.Component.Schedule.Edit = function (options)
	{
		BX.Timeman.Component.BaseComponent.apply(this, arguments);
		this.calendarExclusionsFormName = options.calendarExclusionsFormName;
		this.scheduleIdFormName = options.scheduleIdFormName;
		this.scheduleId = options.scheduleId;
		this.isSlider = options.isSlider;
		this.options = options;
		var assignmentsMapRaw = options.assignmentsMap;
		this.assignmentsMap = {};
		var keys = Object.keys(assignmentsMapRaw);
		for (var i = 0; i < keys.length; i++)
		{
			this.fillSchedulesAssignmentsMap(assignmentsMapRaw, keys[i]);
		}

		this.assignmentsLoading = {};
		this.scheduleFormName = options.scheduleFormName;
		this.scheduleNamePostfix = options.scheduleNamePostfix;

		this.addShiftBtn = this.selectOneByRole('timeman-schedule-form-shift-add');
		this.typeSelector = this.selectOneByRole('timeman-schedule-type-select');
		this.reportPeriodSelector = this.selectOneByRole('timeman-report-period-select');
		this.reportPeriodStartWeekDayBlock = this.selectOneByRole('timeman-report-period-start-week-day-block');
		this.cancelButton = this.selectOneByRole('timeman-schedule-btn-cancel');
		this.saveButton = this.selectOneByRole('timeman-schedule-btn-save');
		this.assignmentsWrapper = this.selectOneByRole('assignments-wrapper');
		this.scheduleForm = this.selectOneByRole('timeman-schedule-form');
		this.assignmentsErrorBlock = this.selectOneByRole('timeman-schedule-assignments-error-block');
		this.excludedUsersToggle = this.selectOneByRole('timeman-excluded-users-show-btn');
		this.excludedContainer = this.selectOneByRole('excluded-container');
		this.controlledActionsSelector = this.selectOneByRole('controlled-actions');
		this.errorBlock = this.selectOneByRole('timeman-schedule-edit-error-block');
		this.errorMsgTemplate = this.selectOneByRole('timeman-schedule-error-msg-block');
		this.shifts = [];
		this.scheduleNameInput = this.selectOneByRole("schedule-name");
		this.calendarExclusions = new BX.Timeman.Component.Schedule.Edit.Calendar.Exclusions(options);
		this.violations = new BX.Timeman.Component.Schedule.Edit.Violations(options);
		this.setScheduleNameFromTypeOption(0);

		this.addEventHandlers(options);
		var shiftForms = document.querySelectorAll('[data-role^="timeman-schedule-shift-form-container"]');
		for (var i = 0; i < shiftForms.length; i++)
		{
			this.createShift('[data-role="' + shiftForms[i].dataset.role + '"]', {visible: i !== 0});
		}
		this.redrawFormByScheduleType(this.typeSelector.value, this.controlledActionsSelector.value);
	};

	BX.Timeman.Component.Schedule.Edit.prototype = {
		__proto__: BX.Timeman.Component.BaseComponent.prototype,
		constructor: BX.Timeman.Component.Schedule.Edit,
		addEventHandlers: function ()
		{
			BX.bind(this.scheduleNameInput, 'focus', BX.delegate(this.onScheduleInputFocus, this));
			BX.bind(this.excludedUsersToggle, 'click', BX.delegate(this.onExcludedUsersToggleClick, this));
			BX.bind(this.addShiftBtn, 'click', BX.delegate(this.onAddShiftBtnClick, this));
			BX.bind(this.typeSelector, 'change', BX.delegate(this.onTypeSelectorChange, this));
			BX.bind(this.reportPeriodSelector, 'change', BX.delegate(this.onReportPeriodChange, this));
			BX.bind(this.controlledActionsSelector, 'change', BX.delegate(this.onControlledActionsChange, this));
			BX.bind(this.saveButton, 'click', BX.delegate(this.onSaveClick, this));
			BX.bind(this.cancelButton, 'click', BX.delegate(this.onCancelClick, this));
			BX.bind(this.cancelButton, 'click', BX.delegate(this.onCancelClick, this));

			BX.addCustomEvent('BX.Main.User.SelectorController:select', BX.delegate(this.onAssignmentSelected, this));
			BX.addCustomEvent('BX.Main.User.SelectorController:unSelect', BX.delegate(this.onAssignmentUnselected, this));
			BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(function (event)
			{
				if (event.getEventId() === 'BX.Timeman.Schedule.Update::Success')
				{
					this.assignmentsMap = {};
					this.updateAssignmentsWarnings();
				}
			}.bind(this)));
			var checkboxes = document.querySelectorAll('[data-role^="startTimeAllowedDevice"]');
			for (var i = 0; i < checkboxes.length; i++)
			{
				BX.bind(checkboxes[i], 'click', BX.delegate(this.onAllowDeviceClick, this));
			}
		},
		updateAssignmentsWarnings: function ()
		{
			this.clearErrors(this.assignmentsErrorBlock);
			var selectedAssignments = this.assignmentsWrapper.querySelectorAll('[data-role="tile-item"][data-bx-id]');
			for (var i = 0; i < selectedAssignments.length; i++)
			{
				if (!selectedAssignments[i].dataset.bxId)
				{
					continue;
				}
				this.drawUserAssignmentsWarning(selectedAssignments[i].dataset.bxId);
			}
		},
		drawUserAssignmentsWarning: function (entityCode)
		{
			if (this.assignmentsMap[entityCode] === undefined)
			{
				if (!this.assignmentsLoading[entityCode])
				{
					this.assignmentsLoading[entityCode] = true;
					this.disableSaveBtn();
					BX.ajax.runAction(
						'timeman.schedule.getSchedulesForEntity',
						{
							data: {
								entityCode: entityCode,
								checkNestedEntities: true,
								currentScheduleId: this.scheduleId
							}
						}
					).then(
						function (entityCode, response)
						{
							this.assignmentsLoading[entityCode] = false;
							this.enableSaveBtn();
							this.assignmentsMap[entityCode] = [];
							this.fillSchedulesAssignmentsMap(response.data, entityCode);

							this.drawUserAssignmentsWarning(entityCode);
						}.bind(this, entityCode),
						function (entityCode, response)
						{
							this.assignmentsLoading[entityCode] = false;
							this.showErrors(response.errors);
							this.enableSaveBtn();
						}.bind(this, entityCode));

				}
				return;
			}
			var data = this.assignmentsMap[entityCode];
			if (data && data.length > 0)
			{
				for (var i = 0; i < data.length; i++)
				{
					var schedule = data[i];
					var uniqueId = schedule.id + schedule.entityCode;
					if (this.assignmentsErrorBlock.querySelector('[data-role="timeman-schedule-error-msg-block"][data-unique-id="' + uniqueId + '"]'))
					{
						continue;
					}
					var erMsg = this.errorMsgTemplate.cloneNode(true);
					erMsg.dataset.uniqueId = uniqueId;
					var scheduleName = '&nbsp;<a class="timeman-schedule-form-error-link" data-role="schedule-link" data-id="' + parseInt(schedule.id) + '" target="_blank"'
						+ '" href="' + schedule.link + '">' + BX.util.htmlspecialchars(schedule.name) + '</a>';

					if (schedule.entityCode.substr(0, 2) === 'DR')
					{
						erMsg.dataset.department = true;
						erMsg.dataset.name = schedule.entityName;
						erMsg.dataset.scheduleName = schedule.name;
						this.selectOneByRole('timeman-schedule-error-msg', erMsg).innerHTML = BX.message('TIMEMAN_SCHEDULE_EDIT_ALREADY_ASSIGNED_DEPARTMENT_WARNING')
								.replace('#SCHEDULE_NAME#', scheduleName)
								.replace('#ASSIGNMENT_NAME#', BX.util.htmlspecialchars(schedule.entityName))
							+ '. ' + BX.message('TIMEMAN_SCHEDULE_EDIT_DEPARTMENT_WILL_BE_EXCLUDED');
					}
					else if (schedule['forAllUsers'] && schedule.entityCode === 'UA')
					{
						this.selectOneByRole('timeman-schedule-error-msg', erMsg).innerHTML = BX.message('TIMEMAN_SCHEDULE_FOR_ALL_USERS_WARNING')
							.replace('#SCHEDULE_NAME#', scheduleName + '&nbsp;');
					}
					else
					{
						var errorTitle = 'TIMEMAN_SCHEDULE_EDIT_ALREADY_ASSIGNED_MALE_WARNING';
						if (schedule.entityGender === 'F')
						{
							errorTitle = 'TIMEMAN_SCHEDULE_EDIT_ALREADY_ASSIGNED_FEMALE_WARNING';
						}
						this.selectOneByRole('timeman-schedule-error-msg', erMsg).innerHTML = BX.message(errorTitle)
							.replace('#SCHEDULE_NAME#', scheduleName)
							.replace('#ASSIGNMENT_NAME#', BX.util.htmlspecialchars(schedule.entityName));
						erMsg.classList.remove('ui-alert-danger');
						erMsg.classList.add('ui-alert-success');
					}
					this.assignmentsErrorBlock.appendChild(erMsg);
					BX.bind(this.selectOneByRole('schedule-link', erMsg), 'click', BX.delegate(this.onScheduleLinkClick, this));
					this.showElement(erMsg);
				}
			}
		},
		fillSchedulesAssignmentsMap: function (schedules, entityCode)
		{
			var keys = Object.keys(schedules);
			for (var i = 0; i < keys.length; i++)
			{
				for (var j = 0; j < Object.keys(schedules[keys[i]]).length; j++)
				{
					var schedule = schedules[keys[i]][Object.keys(schedules[keys[i]])[j]];
					if (parseInt(this.scheduleId) !== parseInt(schedule.ID))
					{
						var name = schedule.entityName;
						if (!name)
						{
							var nameWrapperNode = this.container.querySelector('[data-role="tile-item"][data-bx-id="' + keys[i] + '"]');
							if (nameWrapperNode && nameWrapperNode.querySelector('[data-role="tile-item-name"]'))
							{
								name = nameWrapperNode.querySelector('[data-role="tile-item-name"]').textContent;
							}
						}
						var scheduleEntityCode = schedule.entityCode ? schedule.entityCode : keys[i];
						var item = {
							'id': schedule.ID,
							'name': schedule.NAME,
							'link': schedule.LINKS.DETAIL,
							'forAllUsers': schedule.IS_FOR_ALL_USERS,
							'entityName': name,
							'entityCode': scheduleEntityCode,
							'entityGender': schedule.entityGender
						};
						if (this.assignmentsMap[entityCode] === undefined)
						{
							this.assignmentsMap[entityCode] = [];
						}

						this.assignmentsMap[entityCode].push(item);
					}
				}
			}
		},
		onScheduleLinkClick: function (event)
		{
			event.stopPropagation();
			event.preventDefault();
			var url = BX.util.add_url_param("/bitrix/components/bitrix/timeman.schedule.edit/slider.php", {SCHEDULE_ID: event.currentTarget.dataset.id});
			window.top.BX.SidePanel.Instance.open(url, {width: 1200, cacheable: false});
		},
		onAssignmentUnselected: function (params)
		{
			this.updateAssignmentsWarnings();
		},
		onAssignmentSelected: function (params)
		{
			this.updateAssignmentsWarnings();
		},
		onExcludedUsersToggleClick: function ()
		{
			this.toggleElementVisibility(this.excludedContainer);
		},
		onAllowDeviceClick: function (event)
		{
			var selected = document.querySelectorAll('[data-role^="startTimeAllowedDevice"]:checked');
			if (selected.length === 0)
			{
				event.stopPropagation();
				event.preventDefault();
			}
		},
		onControlledActionsChange: function ()
		{
			this.controlledActionsSelector.dataset.autoaction = false;
			this.violations.redrawFormByScheduleType(this.typeSelector.value, this.controlledActionsSelector.value);
		},
		onScheduleInputFocus: function ()
		{
			this.scheduleNameInput.dataset.autoname = false;
		},
		setScheduleNameFromTypeOption: function (index)
		{
			if (this.scheduleNameInput.dataset.autoname === 'false')
			{
				return;
			}
			if (this.isFixedSchedule(this.typeSelector.value))
			{
				this.scheduleNameInput.value = BX.util.htmlspecialchars(BX.message('TIMEMAN_SCHEDULE_EDIT_NAME_DEFAULT_FIXED'));
			}
			else if (this.isShiftedSchedule(this.typeSelector.value))
			{
				this.scheduleNameInput.value = BX.util.htmlspecialchars(BX.message('TIMEMAN_SCHEDULE_EDIT_NAME_DEFAULT_SHIFT'));
			}
			else if (this.isFlextimeSchedule(this.typeSelector.value))
			{
				this.scheduleNameInput.value = BX.util.htmlspecialchars(BX.message('TIMEMAN_SCHEDULE_EDIT_NAME_DEFAULT_FLEXTIME'));
			}
		},
		onReportPeriodChange: function (event)
		{
			var option = event.currentTarget.querySelectorAll('option')[event.currentTarget.selectedIndex];

			if (option.value === 'WEEK' || option.value === 'TWO_WEEKS')
			{
				this.showElement(this.reportPeriodStartWeekDayBlock);
			}
			else
			{
				this.hideElement(this.reportPeriodStartWeekDayBlock);
			}
		},
		onTypeSelectorChange: function (event)
		{
			this.setScheduleNameFromTypeOption(event.currentTarget.selectedIndex);
			var option = event.currentTarget.querySelectorAll('option')[event.currentTarget.selectedIndex];

			this.redrawFormByScheduleType(option.value);
		},
		redrawFormByScheduleType: function (selectedType)
		{
			this.showElement(this.selectOneByRole('timeman-schedule-calendars-container'));
			this.showElement(this.selectOneByRole('work-time-block'));
			this.violations.redrawFormByScheduleType(selectedType, this.controlledActionsSelector.value);
			if (this.isShiftedSchedule(selectedType))
			{
				this.addShiftBtn.textContent = BX.message('TIMEMAN_SCHEDULE_EDIT_ADD_WORK_SHIFT_TITLE');
				this.hideElement(this.selectOneByRole('timeman-schedule-calendars-container'));
				if (this.controlledActionsSelector.dataset.autoaction === 'true')
				{
					this.controlledActionsSelector.value = this.options.controlledStart;
				}
			}
			else if (this.isFlextimeSchedule(selectedType))
			{
				this.hideElement(this.selectOneByRole('timeman-schedule-calendars-container'));
				this.hideElement(this.selectOneByRole('work-time-block'));
			}
			else
			{
				this.addShiftBtn.textContent = BX.message('TIMEMAN_SCHEDULE_EDIT_ADD_WORK_TIME_TITLE');
				if (this.isFixedSchedule(selectedType) &&
					this.controlledActionsSelector.dataset.autoaction === 'true')
				{
					this.controlledActionsSelector.value = this.options.controlledStartEnd;
				}
			}
			for (var i = 0; i < this.shifts.length; i++)
			{
				this.shifts[i].onScheduleTypeSelected(selectedType);
			}
		},
		createShift: function (containerSelector, options)
		{
			var shift = new BX.Timeman.Component.Schedule.ShiftEdit.Multiple(
				Object.assign(
					{},
					this.options,
					{
						containerSelector: containerSelector,
						uniqueIndex: options && options.uniqueIndex !== undefined ? options.uniqueIndex : undefined,
						visible: options && options.visible !== undefined ? options.visible : undefined,
						prevShiftEnd: options && options.prevShiftEnd !== undefined ? options.prevShiftEnd : undefined,
						prevShiftStart: options && options.prevShiftStart !== undefined ? options.prevShiftStart : undefined,
						isSlider: this.isSlider
					}
				)
			);

			shift.attachOnDeleteEvent(this);
			this.addDaysEventHandlers(shift.container);

			this.shifts.push(shift);
		},
		addDaysEventHandlers: function (container)
		{
			var workDaysItems = this.selectAllByRole('timeman-schedule-shift-work-day-item', container);
			for (var i = 0; i < workDaysItems.length; i++)
			{
				BX.bind(workDaysItems[i], 'click', BX.delegate(this.onWorkDayClick, this));
			}
		},
		onWorkDayClick: function (event)
		{
			var workDaysItems = this.selectAllByRole('timeman-schedule-shift-work-day-item', this.container);
			for (var i = 0; i < workDaysItems.length; i++)
			{
				if (workDaysItems[i].checked === true)
				{
					if (workDaysItems[i] !== event.currentTarget
						&& workDaysItems[i].value === event.currentTarget.value)
					{
						event.preventDefault();
						event.stopPropagation();
					}
				}
			}
		},
		updateOnShiftEvent: function (params)
		{
			for (var i = 0; i < this.shifts.length; i++)
			{
				if (this.shifts[i] === params.shift)
				{
					this.shifts.splice(i, 1);
				}
			}
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
		onAddShiftBtnClick: function ()
		{
			var shift = document.querySelector('[data-role^="timeman-schedule-shift-form-container"]');
			var cln = shift.cloneNode(true);
			var shifts = document.querySelectorAll('[data-role^="timeman-schedule-shift-form-container"]');
			var uniqueIndex = shifts.length;
			var prevShiftEnd = null;
			var prevShiftStart = null;
			if (this.isShiftedSchedule(this.typeSelector.value))
			{
				prevShiftEnd = shifts[shifts.length - 1].querySelector('[data-role="timeman-shift-link-end-time"]').value;
				prevShiftStart = shifts[shifts.length - 1].querySelector('[data-role="timeman-shift-link-start-time"]').value;
			}

			cln.dataset.role = "timeman-schedule-shift-form-container-" + uniqueIndex;
			this.selectOneByRole('timeman-shifts-wrapper').appendChild(cln);
			this.createShift('[data-role="' + cln.dataset.role + '"]', {
				uniqueIndex: uniqueIndex,
				visible: true,
				prevShiftEnd: prevShiftEnd,
				prevShiftStart: prevShiftStart,
			});
		},
		disableSaveBtn: function ()
		{
			this.saveButtonDisabled = true;
			if (this.saveButton)
			{
				this.saveButton.classList.add('timeman-schedule-disabled');
			}
		},
		enableSaveBtn: function ()
		{
			this.saveButtonDisabled = false;
			if (this.saveButton)
			{
				this.saveButton.classList.remove('timeman-schedule-disabled');
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
			if (this.saveButtonDisabled)
			{
				return;
			}
			this.disableSaveBtn();
			this.clearErrors(this.errorBlock);
			for (var i = 0; i < this.shifts.length; i++)
			{
				this.shifts[i].processBeforeCollectFormData();
			}
			var formData = new FormData(this.scheduleForm);
			formData.append(this.calendarExclusionsFormName, JSON.stringify(this.calendarExclusions.exclusionsData));
			var actionName = formData.get(this.scheduleIdFormName) ? 'timeman.schedule.update' : 'timeman.schedule.add';
			this.lastActionName = actionName;

			this.processSave(actionName, formData);
		},
		processSave: function (actionName, formData)
		{
			BX.ajax.runAction(
				actionName,
				{
					data: formData,
					analyticsLabel: {
						type: formData.get('ScheduleForm[type]')
					}
				}
			).then(
				function (response)
				{
					this.onSuccess(response, this.lastActionName);
					this.enableSaveBtn();
				}.bind(this),
				function (response)
				{
					this.showErrors(response.errors);
					this.enableSaveBtn();
				}.bind(this));
		},
		onSuccess: function (response, actionName)
		{
			if (this.isSlider && this.getSlider())
			{
				this.getSlider().postMessageAll(window,
					actionName === 'timeman.schedule.add' ? 'BX.Timeman.Schedule.Add::Success' : 'BX.Timeman.Schedule.Update::Success',
					{
						schedule: response.data.schedule
					}
				);
			}

			if (this.isSlider)
			{
				this.closeSlider();
			}
			else
			{
				document.location.reload();
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
		showErrors: function (errorMessages)
		{
			for (var i = 0; i < errorMessages.length; i++)
			{
				var erMsg = this.errorMsgTemplate.cloneNode(true);
				erMsg.textContent = BX.util.htmlspecialchars(errorMessages[i].message);
				this.errorBlock.appendChild(erMsg);
				this.showElement(erMsg);
			}
			if (errorMessages.length > 0)
			{
				window.scrollTo(0, 0);
			}
		}
	}
})();