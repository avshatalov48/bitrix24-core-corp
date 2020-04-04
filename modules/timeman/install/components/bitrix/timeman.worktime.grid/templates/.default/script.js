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
		this.filterId = options.filterId;
		options.container = this.container = document.querySelector(this.isSlider ? 'body' : '#content-table');
		BX.Timeman.Component.BaseComponent.apply(this, arguments);
		this.todayWord = options.todayWord;
		this.usersIds = options.usersIds;
		this.canManageSettings = options.canManageSettings;
		this.departmentsIds = options.departmentsIds;
		this.departmentsData = options.departmentsData;
		this.scheduleCreateBtn = this.selectOneByRole('timeman-add-schedule-btn');
		this.todayLink = this.selectOneByRole('tm-navigation-today');
		this.canReadSchedules = options.canReadSchedules;
		this.highlightDepartmentRows();
		this.schedules = {};
		this.scrollToToday();
		this.addEventHandlers();
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
		},
		addEventHandlers: function ()
		{
			BX.bind(this.scheduleCreateBtn, 'click', BX.delegate(this.onScheduleCreateBtnClick, this));
			BX.bind(this.todayLink, 'click', BX.delegate(this.onTodayLinkClick, this));

			this.onGridUpdated();
			BX.addCustomEvent('BX.Main.Filter:apply', this.onApplyFilter.bind(this));

			BX.addCustomEvent('Grid::updated', this.onGridUpdated.bind(this));
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
			this.scrollToToday();
		},
		onNavigationArrowClick: function (e)
		{
			e.preventDefault();
			e.stopPropagation();
			this.applyFilterFromUrlData(e.currentTarget);
		},
		onTimemanSettingsToggleClick: function (event)
		{
			if (this.settingsLoading === true)
			{
				return;
			}
			if (!this.settingsData)
			{
				this.settingsLoading = true;
				var queryData = {
					'DEPARTMENTS': this.departmentsIds,
					'USERS': this.usersIds
				};

				BX.timeman_query('admin_data_settings', queryData,
					/** @this {BX.Timeman.Component.Worktime.Grid}*/
					function (event, data)
					{
						this.settingsData = data;
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
						if (this.loader)
						{
							this.showSchedulesListMenu(event.target.dataset)
						}
					}.bind(this, event),
					/** @this {BX.Timeman.Component.Worktime.Grid}*/
					function (event, response)
					{
						this.settingsLoading = false;
					}.bind(this, event));
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
												for (var i = 0; i < this.settingsData.USERS.length; i++)
												{
													if (this.settingsData.USERS[i].ID === data.ID)
													{
														this.settingsData.USERS[i] = data;
														break;
													}
												}
											}
											else if (dataset.type === 'department')
											{
												for (var i = 0; i < this.settingsData.DEPARTMENTS.length; i++)
												{
													if (this.settingsData.DEPARTMENTS[i].ID === data.ID)
													{
														this.settingsData.DEPARTMENTS[i] = data;
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
							var isTopSection = dataset.type === 'department' && this.departmentsData[dataset.id] && this.departmentsData[dataset.id].TOP_SECTION === true;
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
			if (!(schedules && schedules.length > 0))
			{
				if (this.settingsLoading === true && !this.loader)
				{
					this.loader = new BX.Loader({
						target: e.currentTarget
					});
					this.loader.show();
				}
				return;
			}
			for (var i = 0; i < schedules.length; i++)
			{
				var item = {
					text: BX.message('TIMEMAN_GRID_MENU_SCHEDULE_PERSONAL_VIOLATIONS_PREFIX') + ' "' + BX.util.htmlspecialchars(schedules[i].NAME) + '"',
					dataset: {
						url: schedules[i].LINKS.DETAIL,
						entityCode: this.buildEntityCode(dataset),
						isFlexible: this.isScheduleFlexible(schedules[i].SCHEDULE_TYPE)
					}
				};
				item.onclick = function (event, item)
				{
					if (!item.dataset.isFlexible && this.canReadSchedules)
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
				bindElement: e ? (e.currentTarget ? e.currentTarget : e.target) : (this.loader ? this.loader.currentTarget : null),
				angle: {
					position: 'top'
				},
				closeByEsc: true,
				autoHide: true
			});
			pop.show();
			if (this.loader)
			{
				this.loader.hide();
				this.loader = null;
			}
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
		isScheduleFlexible: function (scheduleType)
		{
			return scheduleType === this.flexibleScheduleTypeName;
		},
		getSettingsOption: function (type, id, name, inherit)
		{
			var source = [];
			if (type === 'user')
			{
				source = this.settingsData.USERS;
			}
			else if (type === 'department')
			{
				source = this.settingsData.DEPARTMENTS;
			}

			for (var i = 0; i < source.length; i++)
			{
				if (source[i].ID == id)
				{
					var values = [];
					values.push(source[i].SETTINGS[name]);
					if (inherit)
					{
						values.push(this.settingsData.DEFAULTS[name]);
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
			return (dataset.type === 'user' ? 'U' : 'DR') + dataset.id;
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
		onScheduleCreateBtnClick: function (event)
		{
			event.stopPropagation();
			event.preventDefault();
			BX.SidePanel.Instance.open('/bitrix/components/bitrix/timeman.schedule.edit/slider.php', {
				width: 1200,
				cacheable: false
			});
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
			var scrollCellsCount = todayIndex - fixedCellsCount - visibleCount + 3;
			if (cellWidth * scrollCellsCount > 0)
			{
				BX.Main.gridManager.getById(this.gridId).instance.getScrollContainer().scrollTo(cellWidth * scrollCellsCount - fixedWidth, 0);
			}
		},
		onGridUpdated: function ()
		{
			this.addEventHandlersInsideGrid();
			this.initHints();
			this.highlightDepartmentRows();
			this.scrollToToday();
		},
		initHints: function ()
		{
			BX.UI.Hint.init(this.container);
		}
	};
})();