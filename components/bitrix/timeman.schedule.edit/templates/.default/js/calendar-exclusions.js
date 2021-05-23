(function ()
{
	'use strict';

	BX.namespace('BX.Timeman.Component.Schedule.Edit.Calendar');
	/**
	 * @extends BX.Timeman.Component.BaseComponent
	 * @param options
	 * @constructor
	 */
	BX.Timeman.Component.Schedule.Edit.Calendar.Exclusions = function (options)
	{
		options.containerSelector = '[data-role="timeman-schedule-calendars-container"]';
		BX.Timeman.Component.BaseComponent.apply(this, arguments);
		this.calendarParentId = this.selectOneByRole('calendar-parent-id');
		this.settingsBtn = this.selectOneByRole('calendars-settings-btn');
		this._currentYear = (new Date()).getFullYear();
		this.calendarYear = this.selectOneByRole('calendar-year');
		this._months = {
			1: [0, 1, 2],
			2: [3, 4, 5],
			3: [6, 7, 8],
			4: [9, 10, 11]
		};
		this.calendarTemplates = options.calendarTemplates || {};
		this.exclusionsData = options.calendarExclusions || {};
		if (this.exclusionsData.length !== 'undefined' && this.exclusionsData.length === 0)
		{
			this.exclusionsData = this._getDefaultExclusionsData();
		}
		this._holidays = this.selectOneByRole('calendar-holidays');
		this._weekends = this.selectOneByRole('calendar-weekends');
		this._createCalendars();

		this._addEventHandlers(options);
	};

	BX.Timeman.Component.Schedule.Edit.Calendar.Exclusions.prototype = {
		__proto__: BX.Timeman.Component.BaseComponent.prototype,
		constructor: BX.Timeman.Component.Schedule.Edit.Calendar.Exclusions,
		_addEventHandlers: function ()
		{
			this.quarterSwitchers = this.selectAllByRole('quarter-switcher');
			for (var i = 0; i < this.quarterSwitchers.length; i++)
			{
				BX.bind(this.quarterSwitchers[i], 'change', BX.delegate(this._onQuarterSwitcherClick, this));
			}
			BX.bind(this.settingsBtn, 'click', BX.delegate(this._onSettingsBtnClick, this));
			BX.bind(this.calendarYear, 'change', this.onCalendarYearChange.bind(this));

			this._addDateClickListeners();
		},
		onCalendarYearChange: function (e)
		{
			if (this.calendarYear.value !== this._currentYear)
			{
				this._currentYear = this.calendarYear.value;
				this._onQuarterSwitcherClick(this.selectedQuarter);
			}
		},
		_addDateClickListeners: function ()
		{
			var cells = this.container.querySelectorAll('.bx-calendar-cell:not(.bx-calendar-date-hidden)');
			for (var i = 0; i < cells.length; i++)
			{
				if (!cells[i].dataset['hasTimemanDateClickHandler'])
				{
					BX.bind(cells[i], 'click', BX.delegate(this._onDateClick, this));
					cells[i].dataset['hasTimemanDateClickHandler'] = true;
				}
			}

			var controls = this.container.querySelectorAll('.bx-calendar-top-year, .bx-calendar-date-hidden');
			for (var i = 0; i < controls.length; i++)
			{
				if (!controls[i].dataset['hasTimemanClickHandler'])
				{
					BX.bind(controls[i], 'click', BX.delegate(this._disableHandler, this));
					BX.bind(controls[i], 'dblclick', BX.delegate(this._disableHandler, this));
					controls[i].dataset['hasTimemanClickHandler'] = true;
				}
			}
		},
		_disableHandler: function (e)
		{
			e.stopPropagation();
			e.preventDefault();
		},
		_removeAllIconsFromSettingsPopup: function ()
		{
			if (this._settingsPopup && this._settingsPopup.layout)
			{
				var popupItems = this._settingsPopup.layout.menuContainer.querySelectorAll('[class*="js-id-"]');
				this.removeTakeIcons(popupItems);
				for (var j = 0; j < this._settingsPopup.getMenuItems().length; j++)
				{
					var item = this._settingsPopup.getMenuItems()[j];
					this.setDefaultClassOptions(item);
					if (item.getSubMenu() && item.getSubMenu().layout.menuContainer)
					{
						for (var k = 0; k < item.getSubMenu().getMenuItems().length; k++)
						{
							this.setDefaultClassOptions(item.getSubMenu().getMenuItems()[k]);
						}
						var items = item.getSubMenu().layout.menuContainer.querySelectorAll('[class*="js-id-"]');
						this.removeTakeIcons(items);
					}
				}
			}
		},
		setDefaultClassOptions: function (item)
		{
			if (item && item.options && item.options.className)
			{
				item.options.className = 'js-id-' + item.id + ' menu-popup-no-icon';
			}
		},
		removeTakeIcons: function (popupItems)
		{
			for (var i = 0; i < popupItems.length; i++)
			{
				popupItems[i].classList.remove('menu-popup-item-take');
				popupItems[i].classList.add('menu-popup-no-icon');
			}
		},
		_onClearHolidaysClick: function (e)
		{
			this._removeAllIconsFromSettingsPopup();
			this._settingsPopup.close();
			this.exclusionsData = this._getDefaultExclusionsData();
			this.calendarParentId.value = '';
			this._onQuarterSwitcherClick(this.selectedQuarter);
		},
		_markSelectedItemInMenuPopup: function (calendarData)
		{
			this._removeAllIconsFromSettingsPopup();
			if (this.calendarParentId.value != calendarData.id && this._settingsPopup && this._settingsPopup.layout)
			{
				var selected = this.getMenuItemSelected(calendarData.id);
				if (selected)
				{
					if (selected.layout.item)
					{
						selected.layout.item.classList.add('menu-popup-item-take');
						selected.layout.item.classList.remove('menu-popup-no-icon');
					}
					if (selected.options && selected.options.className)
					{
						selected.options.className = 'js-id-' + calendarData.id + ' menu-popup-item-take';
					}
				}
			}

			this._settingsPopup.close();
		},
		getMenuItemSelected: function (selectedItemId)
		{
			var selectedItem = this._settingsPopup.getMenuItem(selectedItemId);
			if (!selectedItem)
			{
				for (var i = 0; i < this._settingsPopup.getMenuItems().length; i++)
				{
					var item = this._settingsPopup.getMenuItems()[i];
					if (item.getSubMenu() && item.getSubMenu().getMenuItems && item.getSubMenu().getMenuItems().length > 0)
					{
						if (item.getSubMenu().getMenuItem(selectedItemId))
						{
							selectedItem = item.getSubMenu().getMenuItem(selectedItemId);
							break;
						}
					}
				}
			}

			return selectedItem;
		},
		_onUseCalendarTemplateClick: function (calendarData)
		{
			this._markSelectedItemInMenuPopup(calendarData);
			if (this.calendarParentId.value != calendarData.id)
			{
				this.calendarParentId.value = BX.util.htmlspecialchars(calendarData.id);
			}
			else
			{
				this.calendarParentId.value = '';
			}
			this._settingsPopup.close();
			this.exclusionsData = Object.assign({}, this.exclusionsData, calendarData.exclusions);
			this._onQuarterSwitcherClick(this.selectedQuarter);
		},
		_onSettingsBtnClick: function (e)
		{
			if (!this._settingsPopup)
			{
				var currentCountryData = {};
				var items = [];
				var systemCalendarItems = [];
				for (var i = 0; i < this.calendarTemplates.length; i++)
				{
					var className = this.calendarParentId.value == this.calendarTemplates[i].id ? 'menu-popup-item-take' : 'menu-popup-no-icon';
					var data = {
						text: BX.util.htmlspecialchars(this.calendarTemplates[i].name),
						id: BX.util.htmlspecialchars(this.calendarTemplates[i].id),
						className: className + ' js-id-' + BX.util.htmlspecialchars(this.calendarTemplates[i].id),
						onclick: this._onUseCalendarTemplateClick.bind(this, this.calendarTemplates[i])
					};
					if (this.calendarTemplates[i].title)
					{
						data.title = this.calendarTemplates[i].title;
					}
					if (this.calendarTemplates[i].systemCode.length > 0)
					{
						data.text = BX.util.htmlspecialchars(this.calendarTemplates[i].nameList);
						if (this.calendarTemplates[i].isCurrentCountry)
						{
							data.text = BX.util.htmlspecialchars(this.calendarTemplates[i].nameSingle);
							currentCountryData = data;
						}
						else
						{
							systemCalendarItems.push(data);
						}
					}
					else
					{
						items.push(data);
					}
				}

				if (systemCalendarItems.length > 0)
				{
					var titleHolidays = BX.util.htmlspecialchars(BX.message('TIMEMAN_SCHEDULE_EDIT_SYSTEM_CALENDAR_HOLIDAYS_OF_COUNTRIES_TITLE'));
					if (Object.keys(currentCountryData).length > 0)
					{
						titleHolidays = BX.util.htmlspecialchars(BX.message('TIMEMAN_SCHEDULE_EDIT_SYSTEM_CALENDAR_HOLIDAYS_OTHER_TITLE'));
					}
					items.unshift({
						text: titleHolidays,
						id: 'other_countries',
						items: systemCalendarItems
					});
				}
				if (Object.keys(currentCountryData).length > 0)
				{
					items.unshift(currentCountryData);
				}
				if (items.length > 0 || systemCalendarItems.length > 0)
				{
					items.unshift({
						text: BX.message('TIMEMAN_SCHEDULE_EDIT_CALENDAR_TEMPLATES'),
						delimiter: true,
						id: 'templates_title'
					});
				}
				items.push({
					text: BX.message('TIMEMAN_SCHEDULE_EDIT_CALENDAR_MANAGE'),
					delimiter: true,
					id: 'manage'
				});

				items.push({
					text: BX.message('TIMEMAN_SCHEDULE_EDIT_CALENDAR_CLEAR_HOLIDAYS'),
					id: 'clear_holidays',
					onclick: this._onClearHolidaysClick.bind(this)
				});

				this._settingsPopup = BX.PopupMenu.create(
					'timeman-schedule-calendars-settings',
					this.settingsBtn,
					items,
					{
						angle: true,
						maxHeight: 350,
						closeByEsc: true,
						autoHide: true
					}
				);
			}
			this._settingsPopup.show();
		},
		_createCalendars: function ()
		{
			this._calendars = [];
			var calendarItems = document.querySelectorAll('[data-role="calendars-wrap"] [data-role="calendar"]');
			for (var i = 0; i < calendarItems.length; i++)
			{
				var calendar = new BX.JCCalendar();
				calendar.Show({node: calendarItems[i], bTime: false});
				calendarItems[i].appendChild(calendar.DIV);
				calendar.popup.close();
				this._calendars.push(calendar);
			}
			var curMonth = (new Date()).getMonth();
			for (var k = 1; k <= 4; k++)
			{
				for (var j = 0; j < this._months[k].length; j++)
				{
					if (this._months[k][j] === curMonth)
					{
						this.container.querySelector('#quarter-' + k).checked = 'checked';
						this._onQuarterSwitcherClick(k);
					}
				}
			}
		},
		_initCalendar: function (calendar, month)
		{
			calendar.__month = month;
			calendar.SetMonth(month);
			calendar.SetYear(this._currentYear);

			this._redrawExclusions(calendar, month);
		},
		_redrawExclusions: function (calendar, month)
		{
			var calendarCells = calendar.DIV.querySelectorAll('.bx-calendar-cell');
			for (var i = 0; i < calendarCells.length; i++)
			{
				var node = calendarCells[i];
				BX.removeClass(node, 'bx-calendar-active');
				BX.removeClass(node, 'bx-calendar-shortcut');
				BX.removeClass(node, 'bx-calendar-holiday');
			}

			if (this.exclusionsData[this._currentYear] && this.exclusionsData[this._currentYear][month])
			{
				for (var day in this.exclusionsData[this._currentYear][month])
				{
					this._drawOneMoreHoliday(calendar, day);
				}
			}
		},
		_drawOneMoreHoliday: function (calendar, day)
		{
			var all = calendar.DIV.querySelectorAll('.bx-calendar-active');
			calendar.SetDay(day);
			for (var i = 0; i < all.length; i++)
			{
				all[i].classList.add('bx-calendar-active');
			}
		},
		_onQuarterSwitcherClick: function (event)
		{
			var selectedQuarter = (typeof event === "object") ? event.currentTarget.dataset.quarter : event;
			this.selectedQuarter = selectedQuarter;
			for (var i = 0; i < this._calendars.length; i++)
			{
				var calendar = this._calendars[i];
				var month = this._months[selectedQuarter][i];

				this._initCalendar(calendar, month);
			}
			this._addDateClickListeners();

			this._updateCounters(selectedQuarter);
		},
		_onDateClick: function (e)
		{
			e.preventDefault();
			e.stopPropagation();
			var date = new Date();
			date.setTime(e.currentTarget.dataset.date);

			var selectedMonth = date.getMonth();
			this._toggleExclusion(date.getDate(), selectedMonth);

			for (var i = 0; i < this._calendars.length; i++)
			{
				var calendar = this._calendars[i];
				if (calendar.__month == selectedMonth)
				{
					this._redrawExclusions(calendar, selectedMonth);
				}
			}

			this._updateHolidays(this._getMonthInQuarterByMonth(selectedMonth));
		},
		_toggleExclusion: function (date, selectedMonth)
		{
			if (this._hasExclusion(date, selectedMonth))
			{
				this._removeExclusion(date, selectedMonth);
			}
			else
			{
				this._addExclusion(date, selectedMonth);
			}
		},
		_getMonthInQuarterByMonth: function (month)
		{
			for (var i in this._months)
			{
				var quarterMonths = this._months[i];
				if (quarterMonths.includes(month))
				{
					return quarterMonths;
				}
			}
		},
		_updateHolidays: function (months)
		{
			var holidays = {};

			var data = [this.exclusionsData];
			for (var i = 0; i < months.length; i++)
			{
				for (var j = 0; j < data.length; j++)
				{
					if (data[j] && data[j][this._currentYear] && data[j][this._currentYear][months[i]])
					{
						for (var key in data[j][this._currentYear][months[i]])
						{
							if (data[j][this._currentYear][months[i]].hasOwnProperty(key))
							{
								holidays[this._currentYear.toString() + months[i].toString() + key.toString()] = 1;
							}
						}
					}
				}
			}
			var cnt = 0;
			for (key in holidays)
			{
				if (holidays.hasOwnProperty(key))
				{
					cnt++;
				}
			}
			this._holidays.textContent = cnt;
		},
		_updateWeekends: function (months)
		{
			var weekends = 0;
			for (var i = 0; i < months.length; i++)
			{
				var d = new Date(this._currentYear, (months[i] + 1), 0);
				var getTot = d.getDate();
				for (var j = 1; j <= getTot; j++)
				{
					var newDate = new Date(d.getFullYear(), d.getMonth(), j);
					if (newDate.getDay() === 0 || newDate.getDay() === 6)
					{
						weekends += 1;
					}
				}
			}
			this._weekends.textContent = weekends;
		},
		_updateCounters: function (quarter)
		{
			var months = this._months[quarter];
			this._updateHolidays(months);
			this._updateWeekends(months);
		},
		_hasExclusion: function (day, month)
		{
			return this.exclusionsData[this._currentYear]
				&& this.exclusionsData[this._currentYear][month]
				&& this.exclusionsData[this._currentYear][month][day];
		},
		_removeExclusion: function (day, month)
		{
			delete this.exclusionsData[this._currentYear][month][day];
		},
		_addExclusion: function (day, month)
		{
			this.exclusionsData[this._currentYear] = this.exclusionsData[this._currentYear] || {};
			this.exclusionsData[this._currentYear][month] = this.exclusionsData[this._currentYear][month] || {};
			this.exclusionsData[this._currentYear][month][day] = this.exclusionsData[this._currentYear][month][day] || '0';
		},
		_getDefaultExclusionsData: function ()
		{
			var data = {};
			data[this._currentYear] = {};
			return data;
		}
	}
})();