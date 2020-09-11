;(function ()
{
	BX.namespace('BX.Timeman.Component.Worktime');
	/**
	 * @extends BX.Timeman.Component.BaseComponent.prototype
	 * @param options
	 * @constructor
	 */
	BX.Timeman.Component.Worktime.Grid = function (options)
	{
		this.isSlider = options.isSlider;
		// todo delete this hack
		// it is here to prevent grid's title changing after filter apply in slider grid
		BX.ajax.UpdatePageData = (function ()
		{
		});
		this.flexibleScheduleTypeName = options.flexibleScheduleTypeName;
		this.gridId = options.gridId;
		this.todayPositionedLeft = options.todayPositionedLeft === true;
		this.filterId = options.filterId;
		this.useIndividualViolationRulesName = 'useIndividualViolationRules';
		this.showStatsColumnsName = 'showStatsColumns';
		this.showStartEndTimeName = 'showStartEndTime';
		this.useEmployeesTimezoneName = 'useEmployeesTimezone';
		options.container = this.container = document.querySelector(this.isSlider ? 'body' : '#content-table');
		BX.Timeman.Component.BaseComponent.apply(this, arguments);
		this.todayWord = options.todayWord;
		this.shiftedScheduleType = options.shiftedScheduleType;
		this.gridConfigOptions = options.gridConfigOptions;
		this.gridConfigBtn = this.selectOneByRole('worktime-grid-config-btn');
		this.shiftPlansBtn = this.selectOneByRole('shift-plans-btn');
		this.canManageSettings = options.canManageSettings;
		this.isShiftplan = options.isShiftplan;
		this.baseDepartmentId = options.baseDepartmentId !== undefined ? parseInt(options.baseDepartmentId) : -1;
		this.todayLink = this.selectOneByRole('tm-navigation-today');
		this.canReadSchedules = options.canReadSchedules;
		this.canUpdateSchedules = options.canUpdateSchedules;
		this.canDeleteSchedules = options.canDeleteSchedules;
		this.highlightDepartmentRows();
		this.schedules = {};
		this.settingsData = undefined;
		if (!this.isViolationsToggleEnabled() &&
			(options.defaultViolationShowIndividual === true || options.defaultViolationShowIndividual === false))
		{
			this.setCookie(this.useIndividualViolationRulesName, options.defaultViolationShowIndividual ? 'Y' : 'N');
		}
		this.scrollToToday();
		this.addEventHandlers();
		this.applyGridConfigOptions();
	};
	BX.Timeman.Component.Worktime.Grid.prototype = {
		__proto__: BX.Timeman.Component.BaseComponent.prototype,
		constructor: BX.Timeman.Component.Worktime.Grid,
		addEventHandlersInsideGrid: function ()
		{
			var navsArrows = this.selectAllByRole('navigation-period');
			for (var i = 0; i < navsArrows.length; i++)
			{
				BX.bind(navsArrows[i], 'click', BX.delegate(this.onNavigationArrowClick, this));
			}
			var toggles = this.selectAllByRole('timeman-settings-toggle');
			for (var i = 0; i < toggles.length; i++)
			{
				BX.bind(toggles[i], 'click', BX.delegate(this.onTimemanSettingsToggleClick, this));
			}
			BX.bind(this.selectOneByRole('dates-calendar-toggle'), 'click', BX.delegate(this.onNavigationCalendarToggleClick, this));
			var recordCells = this.selectAllByRole('worktime-record-cell');
			for (var i = 0; i < recordCells.length; i++)
			{
				BX.bind(recordCells[i], 'click', BX.delegate(this.onRecordCellClick, this));
			}
		},
		addEventHandlers: function ()
		{
			BX.bind(this.todayLink, 'click', BX.delegate(this.onTodayLinkClick, this));
			BX.bind(this.gridConfigBtn, 'click', BX.delegate(this.onGridConfigBtnClick, this));
			BX.bind(this.shiftPlansBtn, 'click', BX.delegate(this.onShiftPlansClick, this));

			this.onGridUpdated();
			BX.addCustomEvent('BX.Main.Filter:apply', this.onApplyFilter.bind(this));

			BX.addCustomEvent('Grid::updated', this.onGridUpdated.bind(this));
			if (this.getEventContainer())
			{
				BX.bind(this.getEventContainer(), 'TimemanWorktimeGridCellHtmlRedraw', BX.delegate(this.onCellHtmlRedraw, this));
				BX.bind(this.getEventContainer(), 'TimemanWorktimeGridCellHtmlUpdated', BX.delegate(this.onCellHtmlUpdated, this));
			}
			BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(function (event)
			{
				if (event.getEventId() === 'BX.Timeman.Record.Approve::Success')
				{
					var record = event.getData()['record'];
					var eventRedraw = new CustomEvent('TimemanWorktimeGridCellHtmlRedraw', {
						detail: {
							html: record.dayCellsHtml
						}
					});
					this.getEventContainer().dispatchEvent(eventRedraw);
				}
				else if (event.getEventId() === 'BX.Timeman.Schedule.Update::Success')
				{
					var scheduleData = event.getData()['schedule'];
					if (scheduleData)
					{
						for (var i = 0; i < this.gridConfigOptions.schedules.length; i++)
						{
							if (parseInt(this.gridConfigOptions.schedules[i].id) === parseInt(scheduleData.id))
							{
								this.gridConfigOptions.schedules[i] = this.buildScheduleItem(scheduleData);
							}
						}
						this.updateShiftPlansBtnVisibility();
					}
				}
				else if (event.getEventId() === 'BX.Timeman.Schedule.Add::Success')
				{
					var scheduleData = event.getData()['schedule'];
					if (scheduleData)
					{
						var item = this.buildScheduleItem(scheduleData);
						this.gridConfigOptions.schedules.push(item);
						this.updateShiftPlansBtnVisibility();
					}
				}
			}.bind(this)));
		},
		buildScheduleItem: function (scheduleData)
		{
			return {
				id: scheduleData.id,
				name: scheduleData.name,
				link: scheduleData.links.update,
				canReadShiftPlan: scheduleData.canReadShiftPlan,
				shiftplanLink: scheduleData.links.shiftPlan,
				scheduleType: scheduleData.scheduleType
			};
		},
		updateShiftPlansBtnVisibility: function ()
		{
			if (this.countShiftedSchedulesWithAccessToShiftPlan())
			{
				this.showElement(this.shiftPlansBtn);
			}
			else
			{
				this.hideElement(this.shiftPlansBtn);
			}
		},
		getEventContainer: function ()
		{
			return document.querySelector('[data-role="shift-records-container"]');
		},
		onCellHtmlRedraw: function (e)
		{
			if (e.detail.html === undefined || e.detail.html.length === undefined ||
				e.detail.html.length <= 0)
			{
				return;
			}
			var dayCellNodes = [];
			for (var dayCellIndex = 0; dayCellIndex < e.detail.html.length; dayCellIndex++)
			{
				var html = e.detail.html[dayCellIndex];
				if (!html)
				{
					continue;
				}
				var newCell = document.createElement('div');
				newCell.innerHTML = html;
				var key = false;
				if (newCell.dataset && newCell.dataset.dayCellKey)
				{
					key = newCell.dataset.dayCellKey;
				}
				else
				{
					var blockData = newCell.querySelector('[data-shift-block="true"]');
					if (blockData && blockData.dataset && blockData.dataset.dayCellKey)
					{
						key = blockData.dataset.dayCellKey;
					}
				}
				if (!key)
				{
					continue;
				}

				var dayCellsToRedraw = document.querySelectorAll('.js-' + key);
				for (var j = 0; j < dayCellsToRedraw.length; j++)
				{
					var timeCellsWrapper = dayCellsToRedraw[j].querySelector('.main-grid-cell-content');
					if (!timeCellsWrapper)
					{
						continue;
					}
					var timeCells = timeCellsWrapper.querySelectorAll('[data-shift-block="true"]');
					for (var childIndex = 0; childIndex < timeCells.length; childIndex++)
					{
						timeCells[childIndex].remove();
					}

					var newNode = document.createElement('div');
					newNode.innerHTML = html;
					timeCellsWrapper.appendChild(newNode);
					dayCellNodes.push(newNode);
				}
			}

			var event = new CustomEvent('TimemanWorktimeGridCellHtmlUpdated', {
				detail: {
					dayCellNodes: dayCellNodes
				}
			});
			document.querySelector('[data-role="shift-records-container"]').dispatchEvent(event);
		},
		onCellHtmlUpdated: function (e)
		{
			this.initHints();
			this.applyGridConfigOptions();
			this.addEventHandlersInsideGrid();
		},
		countShiftedSchedulesWithAccessToShiftPlan: function ()
		{
			var cnt = 0;
			for (var i = 0; i < this.gridConfigOptions.schedules.length; i++)
			{
				var scheduleData = this.gridConfigOptions.schedules[i];
				if (this.isShiftedSchedule(scheduleData) && scheduleData.canReadShiftPlan === true)
				{
					cnt++;
				}
			}
			return cnt;
		},
		countShiftedSchedules: function ()
		{
			var cnt = 0;
			for (var i = 0; i < this.gridConfigOptions.schedules.length; i++)
			{
				var scheduleData = this.gridConfigOptions.schedules[i];
				if (this.isShiftedSchedule(scheduleData))
				{
					cnt++;
				}
			}
			return cnt;
		},
		onShiftPlansClick: function (event)
		{
			var cnt = this.countShiftedSchedules();
			if (cnt === 0)
			{
				return;
			}
			var newId = 'tmWorktimeShiftedSchedulesPopup' + cnt;
			if (!this.shiftPlansPopup || this.shiftPlansPopup.id !== newId)
			{
				var items = [];
				for (var i = 0; i < this.gridConfigOptions.schedules.length; i++)
				{
					var schedule = this.gridConfigOptions.schedules[i];
					if (!this.isShiftedSchedule(schedule) || schedule.canReadShiftPlan === false)
					{
						continue;
					}
					items.push({
						text: BX.util.htmlspecialchars(schedule.name),
						id: BX.util.htmlspecialchars(schedule.id),
						href: schedule.shiftplanLink,
						onclick: function ()
						{
							if (this.shiftPlansPopup)
							{
								this.shiftPlansPopup.close();
							}
						}.bind(this)
					});
				}
				this.shiftPlansPopup = new BX.PopupMenuWindow({
					id: newId,
					bindElement: event.currentTarget,
					items: items
				});
			}

			this.shiftPlansPopup.show();
		},
		isShiftedSchedule: function (schedule)
		{
			return schedule.scheduleType === this.shiftedScheduleType;
		},
		onGridConfigBtnClick: function (event)
		{
			this.gridConfigPopup = this.buildGridConfigPopup(event);
			this.gridConfigPopup.show();
		},
		buildGridConfigPopup: function (event)
		{
			var item = this.buildGridConfigPopupItems();
			if (item.length > 0)
			{
				return BX.PopupMenu.create({
					items: item,
					id: 'tmWorktimeGridOptionsSettings' + BX.util.getRandomString(20),
					bindElement: event.currentTarget,
					offsetLeft: -60,
					angle: false,
					closeByEsc: true,
					autoHide: true
				});
			}
			return null;
		},
		buildGridConfigPopupItems: function ()
		{
			var items = [];

			if (this.gridConfigOptions.showStatsItem)
			{
				items.push({
					text: BX.util.htmlspecialchars(BX.message('TM_WORKTIME_GRID_CONFIG_MENU_TITLE_STATS')),
					id: 'showStats',
					className: (this.showStatsColumns() ? 'menu-popup-item-take' : 'menu-popup-no-icon') + ' js-id-showStats',
					onclick: function ()
					{
						this.setCookie(this.showStatsColumnsName, this.showStatsColumns() ? 'N' : 'Y');
						this.closeGridConfigPopup();
						this.reloadGrid();
					}.bind(this)
				});
			}
			if (this.gridConfigOptions.showStartEndItem)
			{
				items.push({
					text: BX.util.htmlspecialchars(BX.message('TM_WORKTIME_GRID_CONFIG_MENU_TITLE_START_END')),
					id: 'showStartEnd',
					className: (this.showStartEndTime() ? 'menu-popup-item-take' : 'menu-popup-no-icon') + ' js-id-showStartEnd',
					onclick: function ()
					{
						this.setCookie(this.showStartEndTimeName, this.showStartEndTime() ? 'N' : 'Y');
						this.closeGridConfigPopup();
						this.applyGridConfigOptions();
					}.bind(this)
				});
			}
			if (this.isViolationsToggleEnabled())
			{
				var classNameIndividual = this.useIndividualViolationRules() ? 'menu-popup-item-take' : 'menu-popup-no-icon';
				var classNameCommon = this.useIndividualViolationRules() ? 'menu-popup-no-icon' : 'menu-popup-item-take';
				items.push({
					text: BX.util.htmlspecialchars(BX.message('TM_WORKTIME_GRID_CONFIG_VIOLATIONS_MENU_TITLE')),
					id: 'violations',
					items: [
						{
							text: BX.util.htmlspecialchars(BX.message('TM_WORKTIME_GRID_CONFIG_VIOLATIONS_MENU_TITLE_PERSONAL')),
							id: 'violationsIndividual',
							className: classNameIndividual + ' js-id-violationsIndividual',
							onclick: function ()
							{
								this.setCookie(this.useIndividualViolationRulesName, 'Y');
								this.closeGridConfigPopup();
								this.applyGridConfigOptions();
							}.bind(this)
						},
						{
							text: BX.util.htmlspecialchars(BX.message('TM_WORKTIME_GRID_CONFIG_VIOLATIONS_MENU_TITLE_COMMON')),
							id: 'violationsCommon',
							className: classNameCommon + ' js-id-violationsCommon',
							onclick: function ()
							{
								this.setCookie(this.useIndividualViolationRulesName, 'N');
								this.closeGridConfigPopup();
								this.applyGridConfigOptions();
							}.bind(this)
						}
					]
				});
			}
			if (this.isTimezoneToggleEnabled())
			{
				var useMineClassName = this.useEmployeesTimezone() ? 'menu-popup-no-icon' : 'menu-popup-item-take';
				var useEmployeesClassName = this.useEmployeesTimezone() ? 'menu-popup-item-take' : 'menu-popup-no-icon';

				items.push({
					id: 'timezoneToggle',
					text: BX.util.htmlspecialchars(BX.message('TM_WORKTIME_GRID_TIMEZONE_TOGGLE_TITLE')),
					items: [
						{
							id: 'timezoneUseMine',
							className: useMineClassName + ' js-id-timezoneUseMine',
							text: BX.util.htmlspecialchars(BX.message('TM_WORKTIME_GRID_TIMEZONE_MINE')),
							onclick: function ()
							{
								this.closeGridConfigPopup();
								this.setCookie(this.useEmployeesTimezoneName, 'N');
								this.reloadGrid();
							}.bind(this)
						},
						{
							id: 'timezoneUseEmployees',
							className: useEmployeesClassName + ' js-id-timezoneUseEmployees',
							text: BX.util.htmlspecialchars(BX.message('TM_WORKTIME_GRID_TIMEZONE_EMPLOYEES')),
							onclick: function ()
							{
								this.closeGridConfigPopup();
								this.setCookie(this.useEmployeesTimezoneName, 'Y');
								this.reloadGrid();
							}.bind(this)
						}
					]
				});
			}
			if (this.gridConfigOptions.showSchedulesItem && this.gridConfigOptions.schedules.length > 0)
			{
				if (this.gridConfigOptions.schedules.length === 1 && this.canUpdateSchedules && this.isShiftplan)
				{
					items.push({
						text: BX.util.htmlspecialchars(BX.message('TM_WORKTIME_GRID_CONFIG_MENU_TITLE_SCHEDULE_EDIT')),
						id: 'editSchedule',
						className: 'menu-popup-no-icon js-id-editSchedule',
						onclick: this.closeGridConfigPopup.bind(this),
						href: this.gridConfigOptions.schedules[0].link
					});
				}
				else if (this.canReadSchedules)
				{
					var schedulesItems = [];
					for (var i = 0; i < this.gridConfigOptions.schedules.length; i++)
					{
						schedulesItems.push(this.createScheduleMenuItem(this.gridConfigOptions.schedules[i]));
					}
					items.push({
						text: BX.util.htmlspecialchars(BX.message('TM_WORKTIME_GRID_CONFIG_MENU_TITLE_SCHEDULES')),
						id: 'editSchedules',
						className: 'menu-popup-no-icon js-id-editSchedules',
						items: schedulesItems
					});
				}
			}

			if (this.exportManager)
			{
				items.push({
					text: BX.util.htmlspecialchars(BX.message('TM_WORKTIME_GRID_CONFIG_MENU_TITLE_EXPORT_EXCEL')),
					id: 'showExportExcel',
					className: 'menu-popup-no-icon js-id-editSchedules',
					onclick: function ()
					{
						this.exportManager.startExport(this.exportManager.getExcelExportType());
						this.closeGridConfigPopup();
					}.bind(this)
				});
			}

			return items;
		},
		closeGridConfigPopup: function ()
		{
			if (this.gridConfigPopup)
			{
				this.gridConfigPopup.close();
			}
		},
		onRecordCellClick: function (event)
		{
			if (event.currentTarget.dataset && event.currentTarget.dataset.href)
			{
				var url = event.currentTarget.dataset.href;
				BX.SidePanel.Instance.open(url, {
					width: 800,
					cacheable: false,
					allowChangeHistory: false,
					requestMethod: 'post',
					requestParams: {
						extraInfo: JSON.stringify({
							useEmployeesTimezone: this.useEmployeesTimezone(),
							isShiftplan: this.isShiftplan
						})
					}
				});
			}
		},
		useEmployeesTimezone: function ()
		{
			return this.getCookie(this.useEmployeesTimezoneName) === 'Y';
		},
		isTimezoneToggleEnabled: function ()
		{
			var enableTimezone = this.selectOneByRole('timezone-toggle-enabled', document);
			return enableTimezone && enableTimezone.value === 'Y';
		},
		isViolationsToggleEnabled: function ()
		{
			var enable = this.selectOneByRole('violations-toggle-enabled', document);
			return enable && enable.value === 'Y';
		},
		createScheduleMenuItem: function (schedule)
		{
			var items = [];
			var scheduleId = schedule.id.toString();
			if (this.canUpdateSchedules)
			{
				items.push({
					text: BX.util.htmlspecialchars(BX.message('TM_WORKTIME_GRID_CONFIG_MENU_ACTION_EDIT')),
					onclick: this.closeGridConfigPopup.bind(this),
					href: schedule.link
				});
			}
			if (this.canDeleteSchedules)
			{
				items.push({
					text: BX.util.htmlspecialchars(BX.message('TM_WORKTIME_GRID_CONFIG_MENU_ACTION_DELETE')),
					onclick: function (schedule)
					{
						this.closeGridConfigPopup();
						this.onDeleteScheduleClick(schedule);
					}.bind(this, schedule)
				});
			}

			var result = {
				text: BX.util.htmlspecialchars(schedule.name),
				id: BX.util.htmlspecialchars(scheduleId),
				onclick: this.closeGridConfigPopup.bind(this),
				href: schedule.link
			};
			if (items.length > 0)
			{
				result.items = items;
			}
			return result;
		},
		onDeleteScheduleClick: function (schedule)
		{
			this.deleteSchedulePopup = new BX.PopupWindow({
				id: 'tm-menu-confirm-delete-schedule-' + BX.util.htmlspecialchars(schedule.id),
				autoHide: true,
				draggable: true,
				closeByEsc: true,
				titleBar: BX.message('TM_WORKTIME_GRID_SCHEDULE_DELETE_CONFIRM_TITLE'),
				content: BX.util.htmlspecialchars(BX.message('TM_WORKTIME_GRID_SCHEDULE_DELETE_CONFIRM').replace('#SCHEDULE_NAME#', (schedule.name))),
				buttons: [
					new BX.UI.Button({
						text: BX.message('TM_WORKTIME_GRID_SCHEDULE_DELETE_CONFIRM_NO'),
						className: 'ui-btn ui-btn-danger',
						events: {
							click: function ()
							{
								this.deleteSchedulePopup.close();
							}.bind(this)
						}
					}),
					new BX.UI.Button({
						text: BX.message('TM_WORKTIME_GRID_SCHEDULE_DELETE_CONFIRM_YES'),
						className: 'ui-btn ui-btn-success',
						events: {
							click: function (schedule)
							{
								this.deleteSchedulePopup.close();
								BX.ajax.runAction(
									'timeman.schedule.delete',
									{
										data: {id: schedule.id}
									}
								).then(
									function (schedule)
									{
										for (var i = 0; i < this.gridConfigOptions.schedules.length; i++)
										{
											if (this.gridConfigOptions.schedules[i].id.toString() === schedule.id.toString())
											{
												this.gridConfigOptions.schedules.splice(i, 1);
												break;
											}
										}
										this.updateShiftPlansBtnVisibility();
									}.bind(this, schedule),
									function (response)
									{
									}.bind(this));
							}.bind(this, schedule)
						}
					})
				]
			});
			this.deleteSchedulePopup.show();
		},
		showStatsColumns: function ()
		{
			return this.getCookie(this.showStatsColumnsName) === 'Y';
		},
		showStartEndTime: function ()
		{
			return this.getCookie(this.showStartEndTimeName) === 'Y';
		},
		applyGridConfigOptions: function ()
		{
			var item = this.buildGridConfigPopupItems();
			this.hideElement(this.gridConfigBtn);
			if (item.length > 0)
			{
				this.showElement(this.gridConfigBtn);
			}
			this.updateShiftPlansBtnVisibility();
			if (this.gridConfigOptions.showStartEndItem)
			{
				var startFinishBlocks = this.selectAllByRole('start-end');
				for (var i = 0; i < startFinishBlocks.length; i++)
				{
					if (this.showStartEndTime())
					{
						this.showElement(startFinishBlocks[i])
					}
					else
					{
						this.hideElement(startFinishBlocks[i])
					}
				}
			}

			var blocks = this.selectAllByRole('violation-icon');
			for (var i = 0; i < blocks.length; i++)
			{
				if (
					(blocks[i].dataset.type === 'common' && !this.useIndividualViolationRules())
					||
					(blocks[i].dataset.type === 'individual' && this.useIndividualViolationRules())
				)
				{
					this.showElement(blocks[i]);
				}
				else
				{
					this.hideElement(blocks[i]);
				}
			}
			var percentageStats = this.selectAllByRole('violation-percentage-stat');
			for (var j = 0; j < percentageStats.length; j++)
			{
				if (
					(this.useIndividualViolationRules() && percentageStats[j].dataset.type === 'individual')
					||
					(!this.useIndividualViolationRules() && percentageStats[j].dataset.type === 'common')
				)
				{
					this.showElement(percentageStats[j]);
				}
				else
				{
					this.hideElement(percentageStats[j]);
				}
			}
		},
		useIndividualViolationRules: function ()
		{
			return this.getCookie(this.useIndividualViolationRulesName) === 'Y';
		},
		onNavigationCalendarToggleClick: function (e)
		{
			BX.calendar({
				node: e.currentTarget,
				field: this.selectOneByRole('month-navigation'),
				bTime: false,
				bHideTime: true,
				callback: function (selectedDate)
				{
					var fromDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
					var toDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth() + 1, 0);
					fromDate = BX.date.format(BX.date.convertBitrixFormat(BX.message("FORMAT_DATE")), fromDate);
					toDate = BX.date.format(BX.date.convertBitrixFormat(BX.message("FORMAT_DATE")), toDate);
					var newUrl = this.selectOneByRole('navigation-period').href.replace(new RegExp('REPORT_PERIOD_from' + '=[^&]*(&*)', 'gi'), 'REPORT_PERIOD_from=' + fromDate + '$1');
					newUrl = newUrl.replace(new RegExp('REPORT_PERIOD_to' + '=[^&]*(&*)', 'gi'), 'REPORT_PERIOD_to=' + toDate + '$1');
					this.applyFilterFromUrlData({
						href: newUrl,
						dataset: {startTo: toDate, startFrom: fromDate, startDatesel: 'RANGE'}
					})
				}.bind(this)
			});
		},
		onApplyFilter: function (id, data, filterInstance, promise, params)
		{
			if (id !== this.filterId)
			{
				return;
			}
			this.initHints();
			this.highlightDepartmentRows();
		},
		onNavigationArrowClick: function (e)
		{
			e.preventDefault();
			e.stopPropagation();
			this.applyFilterFromUrlData(e.currentTarget);
		},
		setCurrentSettingsData: function (data)
		{
			this.settingsData = data;
		},
		getCurrentSettingsData: function ()
		{
			return this.settingsData;
		},
		getCurrentUsersIds: function ()
		{
			var result = {};
			var toggles = this.selectAllByRole('timeman-settings-toggle');
			for (var i = 0; i < toggles.length; i++)
			{
				var data = toggles[i].dataset;
				if (data.type === 'user')
				{
					result[data.id] = true;
				}
			}
			return Object.keys(result);
		},
		getCurrentDepartmentsIds: function ()
		{
			var result = {};
			var toggles = this.selectAllByRole('timeman-settings-toggle');
			for (var i = 0; i < toggles.length; i++)
			{
				var data = toggles[i].dataset;
				if (data.type === 'department')
				{
					result[data.id] = true;
				}
			}
			return Object.keys(result);
		},
		onTimemanSettingsToggleClick: function (event)
		{
			if (this.settingsLoading === true)
			{
				return;
			}
			if (this.getCurrentSettingsData() === undefined)
			{
				this.settingsLoading = true;
				var queryData = {
					'DEPARTMENTS': this.getCurrentDepartmentsIds(),
					'USERS': this.getCurrentUsersIds()
				};

				BX.timeman_query('admin_data_settings', queryData,
					/** @this {BX.Timeman.Component.Worktime.Grid}*/
					function (event, data)
					{
						this.setCurrentSettingsData(data);
						this.settingsLoading = false;
						this.onTimemanSettingsToggleClick(event);
					}.bind(this, event));
				return;
			}
			var entityCode = this.buildEntityCode(event.target.dataset);
			if (this.schedules[entityCode] === undefined)
			{
				this.settingsLoading = true;
				BX.ajax.runAction(
					'timeman.schedule.getSchedulesForEntity',
					{
						data: {entityCode: entityCode}
					}
				).then(
					/** @this {BX.Timeman.Component.Worktime.Grid}*/
					function (event, response)
					{
						this.schedules[response.data.entityCode] = response.data.schedules;
						this.settingsLoading = false;
						this.onTimemanSettingsToggleClick(event);
					}.bind(this, event),
					/** @this {BX.Timeman.Component.Worktime.Grid}*/
					function (event, response)
					{
						this.settingsLoading = false;
					}.bind(this, event));
				return;
			}
			if (this.timemanSettingsMenu)
			{
				this.timemanSettingsMenu.close();
				this.timemanSettingsMenu.destroy();
			}
			var buttons = [];
			if (this.canManageSettings)
			{
				buttons = [
					new BX.PopupWindowCustomButton({
						text: BX.message('JS_CORE_WINDOW_SAVE'),
						className: "ui-btn ui-btn-primary",
						events: {
							click:
							/** @this {BX.Timeman.Component.Worktime.Grid}*/
								function (dataset)
								{
									var oldTmOption = this.getSettingsOption(dataset.type, dataset.id, 'UF_TIMEMAN');
									var selectedTmOption = this.timemanSettingsMenu.getContentContainer().querySelector('[name="UF_TIMEMAN"]').value;

									var oldReportOption = this.getSettingsOption(dataset.type, dataset.id, 'UF_TM_REPORT_REQ');
									var selectedReportOption = this.timemanSettingsMenu.getContentContainer().querySelector('[name="UF_TM_REPORT_REQ"]').value;
									if (oldTmOption === selectedTmOption && oldReportOption === selectedReportOption)
									{
										this.timemanSettingsMenu.close();
										return;
									}

									var data = {
										ID: dataset.id,
										source: dataset.type,
										UF_TIMEMAN: selectedTmOption,
										UF_TM_REPORT_REQ: selectedReportOption
									};
									BX.timeman_query('admin_data_settings',
										data,
										/** @this {BX.Timeman.Component.Worktime.Grid}*/
										function (dataset, data)
										{
											if (dataset.type === 'user')
											{
												for (var i = 0; i < this.getCurrentSettingsData().USERS.length; i++)
												{
													if (this.getCurrentSettingsData().USERS[i].ID === data.ID)
													{
														var curData = this.getCurrentSettingsData();
														curData.USERS[i] = data;
														this.setCurrentSettingsData(curData);
														break;
													}
												}
											}
											else if (dataset.type === 'department')
											{
												for (var i = 0; i < this.getCurrentSettingsData().DEPARTMENTS.length; i++)
												{
													if (this.getCurrentSettingsData().DEPARTMENTS[i].ID === data.ID)
													{
														var curData = this.getCurrentSettingsData();
														curData.DEPARTMENTS[i] = data;
														this.setCurrentSettingsData(curData);
														break;
													}
												}
											}

											this.timemanSettingsMenu.close();
										}.bind(this, dataset));
								}.bind(this, event.target.dataset)
						}
					}),
					new BX.PopupWindowCustomButton({
						text: BX.message('JS_CORE_WINDOW_CLOSE'),
						className: "ui-btn ui-btn-link",
						events: {
							click: function ()
							{
								if (this.timemanSettingsMenu)
								{
									this.timemanSettingsMenu.close();
								}
							}.bind(this)
						}
					})
				];
			}
			this.timemanSettingsMenu = new BX.PopupWindow(
				"tm-setting_form_" + event.target.dataset.id + event.target.dataset.type,
				event.target,
				{
					width: 320,
					closeIcon: {right: "12px", top: "10px"},
					autoHide: true,
					content: BX('tm-settings-popup-menu').innerHTML,
					events: {
						/** @this {BX.Timeman.Component.Worktime.Grid}*/
						onPopupShow: function (dataset)
						{
							var reportSelect = this.timemanSettingsMenu.getContentContainer().querySelector('[name="UF_TM_REPORT_REQ"]');
							var tmSelect = this.timemanSettingsMenu.getContentContainer().querySelector('[name="UF_TIMEMAN"]');
							this.showElement(tmSelect.querySelector('option[value=""]'));
							this.showElement(reportSelect.querySelector('option[value=""]'));
							var isTopSection = dataset.type === 'department' && this.baseDepartmentId && this.baseDepartmentId === parseInt(dataset.id);
							this.selectOptionBySettings('UF_TIMEMAN', dataset, isTopSection);
							this.selectOptionBySettings('UF_TM_REPORT_REQ', dataset, isTopSection);

							BX.bind(tmSelect, 'change', BX.proxy(this.onSettingOptionChange, this));
							BX.bind(
								this.selectOneByRole('schedules-list-toggle', this.timemanSettingsMenu.getContentContainer()),
								'click',
								this.showSchedulesListMenu.bind(this, dataset)
							);
							if (isTopSection)
							{
								this.hideElement(tmSelect.querySelector('option[value=""]'));
								this.hideElement(reportSelect.querySelector('option[value=""]'));
							}
							var schedules = this.schedules[this.buildEntityCode(dataset)];
							var schedulesBlock = this.selectOneByRole('schedule-personal-violations', this.timemanSettingsMenu.getContentContainer());
							var reportBlock = this.selectOneByRole('tm-settings-day-report', this.timemanSettingsMenu.getContentContainer());
							this.hideElement(schedulesBlock);
							this.showElement(reportBlock);
							if (schedules.length > 0)
							{
								for (var i = 0; i < schedules.length; i++)
								{
									if (!this.isFlexibleSchedule(schedules[i].SCHEDULE_TYPE))
									{
										this.showElement(schedulesBlock);
										break;
									}
								}
							}
						}.bind(this, event.target.dataset)
					},
					buttons: buttons
				}
			);
			this.timemanSettingsMenu.show();
		},
		selectOptionBySettings: function (name, entityDataset, inherit)
		{
			var optionValue = this.getSettingsOption(entityDataset.type, entityDataset.id, name, inherit);
			var selectNode = this.timemanSettingsMenu.getContentContainer().querySelector('[name="' + name + '"]');

			var selectedOption = selectNode.querySelector('option[value="' + optionValue + '"]');
			if (selectedOption)
			{
				selectedOption.selected = true;
				if (name === 'UF_TIMEMAN')
				{
					this.showViolationMenuBlock(optionValue);
				}
			}
		},
		onSettingOptionChange: function (event)
		{
			this.showViolationMenuBlock(event.currentTarget.value);
		},
		showSchedulesListMenu: function (dataset, e)
		{
			var items = [];
			var schedules = this.schedules[this.buildEntityCode(dataset)];
			if (schedules === undefined || this.settingsLoading === true)
			{
				return;
			}
			for (var i = 0; i < schedules.length; i++)
			{
				if (this.isFlexibleSchedule(schedules[i].SCHEDULE_TYPE))
				{
					continue;
				}
				var item = {
					text: BX.message('TIMEMAN_GRID_MENU_SCHEDULE_PERSONAL_VIOLATIONS_PREFIX') + ' "' + BX.util.htmlspecialchars(schedules[i].NAME) + '"',
					dataset: {
						url: schedules[i].LINKS.DETAIL,
						entityCode: this.buildEntityCode(dataset)
					}
				};
				item.onclick = function (event, item)
				{
					if (this.canReadSchedules)
					{
						var urlSchEdit = BX.util.add_url_param(item.dataset.url, {
							IFRAME: 'Y',
							VIOLATIONS_ONLY: 'Y',
							ENTITY_CODE: item.dataset.entityCode
						});
						BX.SidePanel.Instance.open(urlSchEdit, {width: 1400, cacheable: false});
					}
					item.menuWindow.close();
				}.bind(this);
				items.push(item);
			}
			var pop = BX.PopupMenu.create({
				items: items,
				maxHeight: 450,
				id: 'tmSettingsScheduleViolations' + this.buildEntityCode(dataset) + BX.util.getRandomString(20),
				bindElement: e ? (e.currentTarget ? e.currentTarget : e.target) : null,
				angle: {
					position: 'top'
				},
				closeByEsc: true,
				autoHide: true
			});
			pop.show();
		},
		showViolationMenuBlock: function (option)
		{
			var vioContainer = this.selectOneByRole('schedule-personal-violations', this.timemanSettingsMenu.getContentContainer());
			if (option === 'N')
			{
				this.hideElement(vioContainer);
			}
			else
			{
				this.showElement(vioContainer);
			}
		},
		isFlexibleSchedule: function (scheduleType)
		{
			return scheduleType === this.flexibleScheduleTypeName;
		},
		getSettingsOption: function (type, id, name, inherit)
		{
			var source = [];
			if (type === 'user')
			{
				source = this.getCurrentSettingsData().USERS;
			}
			else if (type === 'department')
			{
				source = this.getCurrentSettingsData().DEPARTMENTS;
			}

			for (var i = 0; i < source.length; i++)
			{
				if (source[i].ID == id)
				{
					var values = [];
					values.push(source[i].SETTINGS[name]);
					if (inherit)
					{
						values.push(this.getCurrentSettingsData().DEFAULTS[name]);
					}
					for (var j = 0; j < values.length; j++)
					{
						if (values[j] === true || values[j] === 'Y')
						{
							return 'Y';
						}
						if (values[j] === false || values[j] === 'N')
						{
							return 'N';
						}
						if (values[j] !== '')
						{
							return values[j];
						}
					}

					return '';
				}
			}
		},
		buildEntityCode: function (dataset)
		{
			return dataset.entityCode;
		},
		applyFilterFromUrlData: function (target)
		{
			if (!this.filterId)
			{
				window.location.href = target.href;
				return;
			}
			var data = target.dataset;
			var Filter = BX.Main.filterManager.getById(this.filterId);
			var filterFields = {};
			if (data.startFrom)
			{
				filterFields.REPORT_PERIOD_from = data.startFrom;
			}
			if (data.startTo)
			{
				filterFields.REPORT_PERIOD_to = data.startTo;
			}
			if (data.startDatesel)
			{
				filterFields.REPORT_PERIOD_datesel = data.startDatesel;
			}
			Filter.getApi().extendFilter(filterFields, true);
		},
		onTodayLinkClick: function (e)
		{
			e.preventDefault();
			e.stopPropagation();
			if (this.getTodayColumnHeader())
			{
				this.scrollToToday();
				return;
			}
			if (!this.filterId)
			{
				window.location.href = e.target.href;
				return;
			}
			this.applyFilterFromUrlData(e.currentTarget);
		},
		getTodayColumnHeader: function ()
		{
			return document.querySelector('.js-tm-header-today');
		},
		highlightDepartmentRows: function ()
		{
			var depRows = document.querySelectorAll('.tm-department-name');
			for (var i = 0; i < depRows.length; i++)
			{
				if (depRows[i].closest('tr'))
				{
					depRows[i].closest('tr').classList.add('tm-department-name-row');
				}
			}
		},
		reloadGrid: function ()
		{
			BX.Main.gridManager.reload(this.gridId);
		},
		scrollToToday: function ()
		{
			if (!this.getTodayColumnHeader())
			{
				return;
			}
			if (!BX.Main.gridManager.getById(this.gridId) && BX.Main.gridManager.getById(this.gridId).instance)
			{
				return;
			}
			if (!BX.Main.gridManager.getById(this.gridId).instance.getScrollContainer().scrollTo ||
				typeof BX.Main.gridManager.getById(this.gridId).instance.getScrollContainer().scrollTo !== "function")
			{
				return;
			}
			var allWidth = BX.Main.gridManager.getById(this.gridId).instance.getScrollContainer().clientWidth;
			var elements = document.querySelectorAll('.main-grid-cell-head');
			var cellWidth = 0;
			var todayIndex = null;
			var fixedCellsCount = 0;
			var fixedWidth = 0;
			for (var t = 0; t < elements.length; t++)
			{
				var elem = elements[t];
				if (elem.classList.contains('js-tm-fixed-columns') && elem.classList.contains('main-grid-fixed-column'))
				{
					fixedCellsCount++;
					fixedWidth += elem.offsetWidth;
				}
				if (!elem.classList.contains('js-tm-fixed-columns') && cellWidth === 0)
				{
					cellWidth = elem.offsetWidth;
				}
				if (elem === this.getTodayColumnHeader() && todayIndex === null)
				{
					todayIndex = t;
				}
			}
			if (cellWidth <= 0 || todayIndex === null)
			{
				return;
			}
			allWidth = allWidth - fixedWidth;
			var visibleCount = parseInt(allWidth / cellWidth);
			var scrollToX = cellWidth * (todayIndex - fixedCellsCount - visibleCount + 3) - fixedWidth;
			if (this.todayPositionedLeft)
			{
				scrollToX = (todayIndex - 2) * cellWidth - fixedWidth;
			}
			if (scrollToX > 0)
			{
				BX.Main.gridManager.getById(this.gridId).instance.getScrollContainer().scrollTo(scrollToX, 0);
			}
		},
		onGridUpdated: function ()
		{
			this.addEventHandlersInsideGrid();
			this.applyGridConfigOptions();
			this.initHints();
			this.highlightDepartmentRows();
			this.settingsData = undefined;
		},
		initHints: function ()
		{
			BX.UI.Hint.init(this.container);
		}
	};
})();