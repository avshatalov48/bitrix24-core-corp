(function ()
{
	'use strict';

	BX.namespace('BX.Timeman.Component.Schedule.Edit');
	/**
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

		this.defaultSelectedAssignmentCodesExcluded = options.selectedAssignmentCodesExcluded;
		this.defaultSelectedAssignmentCodes = options.selectedAssignmentCodes;
		var asKey = this.buildAssignmentsKey(this.defaultSelectedAssignmentCodes, this.defaultSelectedAssignmentCodesExcluded);
		this.assignmentsMap = {};
		this.assignmentsMap[asKey] = this.prepareSchedulesAssignmentsMap(this.options.assignmentsMap);
		this.assignmentsLoading = false;
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
		new BX.Timeman.Component.Popup.DurationPicker({
			durationInput: this.selectOneByRole('max-shift-start-offset-input'),
			durationPopupToggle: this.selectOneByRole('max-shift-start-offset-link'),
			containerSelector: '[data-role="worktime-restrictions"]'
		});

		this.addEventHandlers(options);
		var shiftForms = document.querySelectorAll('[data-role^="timeman-schedule-shift-form-container"]');
		for (var i = 0; i < shiftForms.length; i++)
		{
			this.createShift('[data-role="' + shiftForms[i].dataset.role + '"]', {visible: i !== 0});
		}
		this.redrawFormByScheduleType(this.typeSelector.value, this.controlledActionsSelector.value);
		BX.UI.Hint.init(this.container);
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
		getCurrentExcludedCodes: function ()
		{
			var result = [];
			var nodes = this.excludedContainer.querySelectorAll('[data-role="tile-item"][data-bx-id]');
			for (var i = 0; i < nodes.length; i++)
			{
				if (!nodes[i].dataset.bxId)
				{
					continue;
				}
				result.push(nodes[i].dataset.bxId);
			}
			return result;
		},
		getCurrentSelectedCodes: function ()
		{
			var result = [];
			var nodes = this.assignmentsWrapper.querySelectorAll('[data-role="tile-item"][data-bx-id]');
			for (var i = 0; i < nodes.length; i++)
			{
				if (!nodes[i].dataset.bxId)
				{
					continue;
				}
				result.push(nodes[i].dataset.bxId);
			}
			return result;
		},
		buildAssignmentsKey: function (selectedAssignments, excludedAssignments)
		{
			if (selectedAssignments === undefined)
			{
				selectedAssignments = this.getCurrentSelectedCodes();
			}
			if (excludedAssignments === undefined)
			{
				excludedAssignments = this.getCurrentExcludedCodes();
			}

			var keys = [];
			for (var i = 0; i < selectedAssignments.length; i++)
			{
				keys.push(selectedAssignments[i]);
			}
			var excludedKeys = [];
			for (var i = 0; i < excludedAssignments.length; i++)
			{
				excludedKeys.push(excludedAssignments[i]);
			}

			if (keys.length !== 0)
			{
				keys.sort();
				excludedKeys.sort();
				return JSON.stringify(keys) + '|' + JSON.stringify(excludedKeys);
			}
			return null;
		},
		updateAssignmentsWarnings: function ()
		{
			this.clearErrors(this.assignmentsErrorBlock);
			var key = this.buildAssignmentsKey();
			if (key === null)
			{
				return;
			}
			if (this.assignmentsMap[key] !== undefined)
			{
				this.drawUserAssignmentsWarning(this.assignmentsMap[key]);
			}
			else
			{
				if (this.assignmentsLoading)
				{
					return;
				}
				this.assignmentsLoading = true;
				this.disableSaveBtn();
				var checkingScheduleAssignmentCodes = [];
				var keysActive = this.getCurrentSelectedCodes();
				var keysExcluded = this.getCurrentExcludedCodes();
				for (var i = 0; i < keysActive.length; i++)
				{
					checkingScheduleAssignmentCodes.push({
						code: keysActive[i],
						excluded: false
					})
				}
				for (var i = 0; i < keysExcluded.length; i++)
				{
					checkingScheduleAssignmentCodes.push({
						code: keysExcluded[i],
						excluded: true
					})
				}
				BX.ajax.runAction(
					'timeman.schedule.getSchedulesForScheduleForm',
					{
						data: {
							exceptScheduleAssignmentCodes: checkingScheduleAssignmentCodes,
							exceptScheduleId: this.scheduleId,
							checkNestedEntities: true
						}
					}
				).then(
					function (key, response)
					{
						this.assignmentsLoading = false;
						this.enableSaveBtn();
						this.assignmentsMap[key] = this.prepareSchedulesAssignmentsMap(response.data.schedules);
						this.drawUserAssignmentsWarning(this.assignmentsMap[key]);
					}.bind(this, key),
					function (response)
					{
						this.assignmentsLoading = false;
						this.showErrors(response.errors);
						this.enableSaveBtn();
					}.bind(this));
			}
		},
		drawUserAssignmentsWarning: function (data)
		{
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
					var scheduleName = '&nbsp;<a class="timeman-schedule-form-error-link" data-role="schedule-link" data-id="' + parseInt(schedule.id) + '"'
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
					this.showElement(erMsg);
				}
			}
		},
		prepareSchedulesAssignmentsMap: function (schedules)
		{
			var result = [];
			var keys = Object.keys(schedules);
			for (var i = 0; i < keys.length; i++)
			{
				var entityCode = keys[i];
				var schedulesIds = Object.keys(schedules[entityCode]);
				for (var j = 0; j < schedulesIds.length; j++)
				{
					var schedule = schedules[entityCode][schedulesIds[j]];
					if (parseInt(this.scheduleId) !== parseInt(schedule.ID))
					{
						var name = schedule.entityName;
						if (!name)
						{
							var nameWrapperNode = this.container.querySelector('[data-role="tile-item"][data-bx-id="' + entityCode + '"]');
							if (nameWrapperNode && nameWrapperNode.querySelector('[data-role="tile-item-name"]'))
							{
								name = nameWrapperNode.querySelector('[data-role="tile-item-name"]').textContent;
							}
						}
						var item = {
							'id': schedule.ID,
							'name': schedule.NAME,
							'link': schedule.LINKS.DETAIL,
							'forAllUsers': schedule.IS_FOR_ALL_USERS,
							'entityName': name,
							'entityCode': entityCode,
							'entityGender': schedule.entityGender
						};
						result.push(item);
					}
				}
			}
			return result;
		},
		onAssignmentUnselected: function (params)
		{
			if (params.selectorId === "ScheduleForm-exclude-assignments-id" ||
				params.selectorId === "ScheduleForm-assignments-id")
			{
				if (this.getCurrentSelectedCodes().length === 0)
				{
					this.enableSaveBtn();
				}
				this.updateAssignmentsWarnings();
			}
		},
		onAssignmentSelected: function (params)
		{
			if (params.selectorId === "ScheduleForm-exclude-assignments-id" ||
				params.selectorId === "ScheduleForm-assignments-id")
			{
				if (this.initedAssignments(params))
				{
					this.updateAssignmentsWarnings();
				}
			}
		},
		initedAssignments: function (params)
		{
			if (params.selectorId === "ScheduleForm-exclude-assignments-id")
			{
				for (var i = 0; i < this.defaultSelectedAssignmentCodesExcluded.length; i++)
				{
					if (this.defaultSelectedAssignmentCodesExcluded[i] === params.item.id)
					{
						this.defaultSelectedAssignmentCodesExcluded.splice(i, 1);
						break;
					}
				}
			}
			else if (params.selectorId === "ScheduleForm-assignments-id")
			{
				for (var i = 0; i < this.defaultSelectedAssignmentCodes.length; i++)
				{
					if (this.defaultSelectedAssignmentCodes[i] === params.item.id)
					{
						this.defaultSelectedAssignmentCodes.splice(i, 1);
						break;
					}
				}
			}
			return this.defaultSelectedAssignmentCodes.length === 0
				&& this.defaultSelectedAssignmentCodesExcluded.length === 0;
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
			this.hideElement(this.selectOneByRole('max-shift-start-offset-block'));
			if (this.isShiftedSchedule(selectedType))
			{
				this.showElement(this.selectOneByRole('max-shift-start-offset-block'));
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
						isScheduleShifted: this.isShiftedSchedule(this.typeSelector.value),
						containerSelector: containerSelector,
						uniqueIndex: options && options.uniqueIndex !== undefined ? options.uniqueIndex : undefined,
						defaultName: options && options.defaultName !== undefined ? options.defaultName : undefined,
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
		buildShiftUniqueName: function ()
		{
			var defaultName = BX.message('TIMEMAN_SHIFT_EDIT_DEFAULT_NAME');
			var hasName = false;
			var uniqueNameIndex = 1;
			do
			{
				hasName = false;
				for (var shIndex = 0; shIndex < this.shifts.length; shIndex++)
				{
					if (this.shifts[shIndex].nameInput.value === defaultName)
					{
						hasName = true;
						uniqueNameIndex++;
						defaultName = BX.message('TIMEMAN_SHIFT_EDIT_DEFAULT_NAME') + ' ' + uniqueNameIndex;
					}
				}
			}
			while (hasName);
			return defaultName;
		},
		buildShiftUniqueIndex: function ()
		{
			var uniqueIndex = document.querySelectorAll('[data-role^="timeman-schedule-shift-form-container"]').length;
			var hasUniqueIndex = true;
			while (hasUniqueIndex)
			{
				hasUniqueIndex = false;
				uniqueIndex++;
				for (var shIndex = 0; shIndex < this.shifts.length; shIndex++)
				{
					if (parseInt(this.shifts[shIndex].uniqueIndex) === parseInt(uniqueIndex))
					{
						hasUniqueIndex = true;
					}
				}
			}
			return uniqueIndex;
		},
		onAddShiftBtnClick: function ()
		{
			var shift = document.querySelector('[data-role^="timeman-schedule-shift-form-container"]');
			var cln = shift.cloneNode(true);
			var uniqueIndex = this.buildShiftUniqueIndex();

			var prevShiftEnd = null;
			var prevShiftStart = null;
			if (this.isShiftedSchedule(this.typeSelector.value) && this.shifts.length > 1)
			{
				prevShiftEnd = this.shifts[this.shifts.length - 1].workTimeEndLink.textContent;
				prevShiftStart = this.shifts[this.shifts.length - 1].workTimeStartLink.textContent;
			}

			cln.dataset.role = "timeman-schedule-shift-form-container-" + uniqueIndex;
			this.selectOneByRole('timeman-shifts-wrapper').appendChild(cln);
			this.createShift('[data-role="' + cln.dataset.role + '"]', {
				uniqueIndex: uniqueIndex,
				visible: true,
				defaultName: this.buildShiftUniqueName(),
				prevShiftEnd: prevShiftEnd,
				prevShiftStart: prevShiftStart
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
			if (actionName === 'timeman.schedule.add' && this.isShiftedSchedule(this.typeSelector.value))
			{
				var loader = new BX.Loader({size: 250, target: this.container});
				loader.show();
			}
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
			if (actionName === 'timeman.schedule.add' &&
				response.data.schedule.links && response.data.schedule.links.shiftPlan)
			{
				var url = BX.util.add_url_param(response.data.schedule.links.shiftPlan, {
					IFRAME: this.isSlider ? 'Y' : 'N'
				});
				document.location.href = url;
			}
			else
			{
				if (this.isSlider)
				{
					this.closeSlider();
				}
				else
				{
					document.location.reload();
				}
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
				erMsg.textContent = errorMessages[i].message;
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