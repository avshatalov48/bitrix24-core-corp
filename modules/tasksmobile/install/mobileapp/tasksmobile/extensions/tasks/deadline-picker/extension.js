/**
 * @module tasks/deadline-picker
 */
jn.define('tasks/deadline-picker', (require, exports, module) => {
	const { Alert } = require('alert');
	const { downloadImages } = require('asset-manager');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { Loc } = require('loc');
	const { Moment } = require('utils/date');
	const { dayOfWeekMonth, fullDate } = require('utils/date/formats');
	const { CalendarSettings } = require('tasks/task/calendar');

	const PickerItemType = {
		TODAY: 'TODAY',
		TOMORROW: 'TOMORROW',
		THIS_WEEK: 'THIS_WEEK',
		NEXT_WEEK_START: 'NEXT_WEEK_START',
		NEXT_WEEK_END: 'NEXT_WEEK_END',
		CUSTOM: 'CUSTOM',
	};

	const PickerSectionCode = {
		PREDEFINED: 'PREDEFINED',
		CUSTOM: 'CUSTOM',
	};

	class DeadlinePicker
	{
		/**
		 * @public
		 * @param {number} deadline
		 * @returns {Promise}
		 */
		show(deadline)
		{
			return new Promise((resolve) => {
				this.getItems(deadline, resolve).then((items) => {
					this.picker = new ContextMenu({
						params: {
							title: Loc.getMessage('TASKSMOBILE_DEADLINE_PICKER_TITLE'),
							isRawIcon: true,
							showCancelButton: true,
						},
						actions: items,
					});
					this.picker.show();
				}).catch(console.error);
			});
		}

		/**
		 * @private
		 * @param deadline
		 * @param onDeadlineSelect
		 * @returns {Promise<[Array]>}
		 */
		async getItems(deadline, onDeadlineSelect)
		{
			await CalendarSettings.loadSettings();

			return [
				...this.getPredefinedItems(deadline, onDeadlineSelect),
				{
					id: PickerItemType.CUSTOM,
					sectionCode: PickerSectionCode.CUSTOM,
					title: Loc.getMessage('TASKSMOBILE_DEADLINE_PICKER_ITEM_CUSTOM_DATE'),
					data: {
						svgUri: iconMap[PickerItemType.CUSTOM],
					},
					onClickCallback: () => {
						dialogs.showDatePicker(
							{
								title: Loc.getMessage('TASKSMOBILE_DEADLINE_PICKER_TITLE'),
								type: 'datetime',
								value: deadline,
								items: [],
							},
							(eventName, ts) => onDeadlineSelect(ts),
						);
					},
				},
			];
		}

		/**
		 * @private
		 * @param {number} deadline
		 * @param {function} onDeadlineSelect
		 * @returns {array}
		 */
		getPredefinedItems(deadline, onDeadlineSelect)
		{
			const predefinedItemValues = this.getPredefinedItemValues(CalendarSettings);
			const predefinedItems = [
				{
					id: PickerItemType.TODAY,
					sectionCode: PickerSectionCode.PREDEFINED,
					title: Loc.getMessage('TASKSMOBILE_DEADLINE_PICKER_ITEM_TODAY'),
					onClickCallback: () => {
						return this.checkForDateConfirm(
							PickerItemType.TODAY,
							predefinedItemValues.get(PickerItemType.TODAY),
							onDeadlineSelect,
							CalendarSettings,
						);
					},
				},
				{
					id: PickerItemType.TOMORROW,
					sectionCode: PickerSectionCode.PREDEFINED,
					title: Loc.getMessage('TASKSMOBILE_DEADLINE_PICKER_ITEM_TOMORROW'),
					onClickCallback: () => {
						return this.checkForDateConfirm(
							PickerItemType.TOMORROW,
							predefinedItemValues.get(PickerItemType.TOMORROW),
							onDeadlineSelect,
							CalendarSettings,
						);
					},
				},
				{
					id: PickerItemType.THIS_WEEK,
					sectionCode: PickerSectionCode.PREDEFINED,
					title: Loc.getMessage('TASKSMOBILE_DEADLINE_PICKER_ITEM_WEEK_END'),
					onClickCallback: () => onDeadlineSelect(predefinedItemValues.get(PickerItemType.THIS_WEEK)),
				},
				{
					id: PickerItemType.NEXT_WEEK_START,
					sectionCode: PickerSectionCode.PREDEFINED,
					title: Loc.getMessage('TASKSMOBILE_DEADLINE_PICKER_ITEM_NEXT_WEEK_START'),
					onClickCallback: () => onDeadlineSelect(predefinedItemValues.get(PickerItemType.NEXT_WEEK_START)),
				},
				{
					id: PickerItemType.NEXT_WEEK_END,
					sectionCode: PickerSectionCode.PREDEFINED,
					title: Loc.getMessage('TASKSMOBILE_DEADLINE_PICKER_ITEM_NEXT_WEEK_END'),
					onClickCallback: () => onDeadlineSelect(predefinedItemValues.get(PickerItemType.NEXT_WEEK_END)),
				},
			];

			return Object.values(predefinedItems)
				.filter((item) => predefinedItemValues.get(item.id))
				.map((item) => ({
					...item,
					subtitle: this.formatDate(predefinedItemValues.get(item.id)),
					isSelected: (deadline === predefinedItemValues.get(item.id)),
				}))
			;
		}

		/**
		 * @public
		 * @param {Calendar} calendarSettings
		 * @param {Date} now
		 * @returns {Map<string, null>}
		 */
		getPredefinedItemValues(calendarSettings, now = new Date())
		{
			const values = new Map([
				[PickerItemType.TODAY, null],
				[PickerItemType.TOMORROW, null],
				[PickerItemType.THIS_WEEK, null],
				[PickerItemType.NEXT_WEEK_START, null],
				[PickerItemType.NEXT_WEEK_END, null],
			]);

			// today
			const { hours, minutes } = calendarSettings.workTime[0].end;
			const serverToday = this.toServerDate(new Date(now), calendarSettings);
			serverToday.setHours(hours, minutes);
			const clientToday = this.toClientDate(serverToday, calendarSettings);
			if (now.getTime() < clientToday.getTime())
			{
				values.set(PickerItemType.TODAY, clientToday.getTime());
			}

			// tomorrow
			const tomorrow = new Date(clientToday);
			tomorrow.setDate(tomorrow.getDate() + 1);
			values.set(PickerItemType.TOMORROW, tomorrow.getTime());

			const thisWeekLastDay = this.toClientDate(this.getLastDayOfWeek(serverToday), calendarSettings);
			const nextWeekFirstDay = new Date(thisWeekLastDay);
			nextWeekFirstDay.setDate(nextWeekFirstDay.getDate() + 1);
			const nextWeekLastDay = new Date(thisWeekLastDay);
			nextWeekLastDay.setDate(nextWeekLastDay.getDate() + 7);

			// this week
			const thisWeekLastWorkDay = this.getClosestWorkDayBackward(
				calendarSettings,
				thisWeekLastDay,
				clientToday,
			);
			if ([...values.values()].every((value) => value < thisWeekLastWorkDay.getTime()))
			{
				values.set(PickerItemType.THIS_WEEK, thisWeekLastWorkDay.getTime());
			}

			// next week's start
			const nextWeekFirstWorkDay = this.getClosestWorkDayForward(
				calendarSettings,
				nextWeekFirstDay,
				nextWeekLastDay,
			);
			if (nextWeekFirstWorkDay)
			{
				if ([...values.values()].every((value) => value < nextWeekFirstWorkDay.getTime()))
				{
					values.set(PickerItemType.NEXT_WEEK_START, nextWeekFirstWorkDay.getTime());
				}

				// next week's end
				const nextWeekLastWorkDay = this.getClosestWorkDayBackward(
					calendarSettings,
					nextWeekLastDay,
					nextWeekFirstWorkDay,
				);
				if (nextWeekLastWorkDay && [...values.values()].every((value) => value < nextWeekLastWorkDay.getTime()))
				{
					values.set(PickerItemType.NEXT_WEEK_END, nextWeekLastWorkDay.getTime());
				}
			}

			return values;
		}

		/**
		 * @private
		 * @param {Date} date
		 * @returns {string}
		 */
		formatDate(date)
		{
			const moment = new Moment(date);

			return moment.format(moment.inThisYear ? dayOfWeekMonth() : fullDate());
		}

		/**
		 * @private
		 * @param {Date} d
		 * @returns {Date}
		 */
		getLastDayOfWeek(d)
		{
			const date = new Date(d);
			const day = date.getDay();

			const diff = date.getDate() - day + (day === 0 ? 0 : 7);

			return new Date(date.setDate(diff));
		}

		/**
		 * @private
		 * @param {Calendar} calendarSettings
		 * @param {Date|number} clientStartDate
		 * @param {Date|number} clientStopDate
		 * @returns {Date}
		 */
		getClosestWorkDayBackward(calendarSettings, clientStartDate, clientStopDate = new Date())
		{
			const serverDate = this.toServerDate(clientStartDate, calendarSettings);
			const serverStopDate = this.toServerDate(clientStopDate, calendarSettings);

			while (serverDate.getTime() > serverStopDate.getTime())
			{
				if (calendarSettings.isWeekendInLocal(serverDate) || calendarSettings.isHolidayInLocal(serverDate))
				{
					serverDate.setDate(serverDate.getDate() - 1);
				}
				else
				{
					break;
				}
			}

			return this.toClientDate(serverDate, calendarSettings);
		}

		/**
		 * @private
		 * @param {Calendar} calendarSettings
		 * @param {Date|number} clientStartDate
		 * @param {Date|number} clientStopDate
		 * @returns {Date}
		 */
		getClosestWorkDayForward(
			calendarSettings,
			clientStartDate,
			clientStopDate = new Date(Date.now() + 31 * 86_400_000),
		)
		{
			const serverDate = this.toServerDate(clientStartDate, calendarSettings);
			const serverStopDate = this.toServerDate(clientStopDate, calendarSettings);

			while (serverDate.getTime() <= serverStopDate.getTime())
			{
				if (calendarSettings.isWeekendInLocal(serverDate) || calendarSettings.isHolidayInLocal(serverDate))
				{
					serverDate.setDate(serverDate.getDate() + 1);
				}
				else
				{
					break;
				}
			}

			if (serverDate.getTime() > serverStopDate.getTime())
			{
				return null;
			}

			return this.toClientDate(serverDate, calendarSettings);
		}

		/**
		 * @private
		 * @param {string} itemId
		 * @param {number} itemValue
		 * @param {function} onDeadlineSelect
		 * @param {Calendar} calendarSettings
		 * @returns {Promise}
		 */
		async checkForDateConfirm(itemId, itemValue, onDeadlineSelect, calendarSettings)
		{
			const itemValueDate = new Date(itemValue);

			if (!calendarSettings.isHolidayInLocal(itemValueDate) && !calendarSettings.isWeekendInLocal(itemValueDate))
			{
				onDeadlineSelect(itemValue);

				return { closeMenu: true };
			}

			const confirmedValue = await this.showDateConfirm(itemId, itemValue, calendarSettings);
			if (confirmedValue)
			{
				onDeadlineSelect(confirmedValue);

				return { closeMenu: true };
			}

			return { closeMenu: false };
		}

		/**
		 * @private
		 * @param {string} itemId
		 * @param {number} itemValue
		 * @param {Calendar} calendarSettings
		 */
		showDateConfirm(itemId, itemValue, calendarSettings)
		{
			return new Promise((resolve) => {
				Alert.confirm(
					Loc.getMessage(`TASKSMOBILE_DEADLINE_PICKER_CONFIRM_TITLE_${itemId}`),
					Loc.getMessage('TASKSMOBILE_DEADLINE_PICKER_CONFIRM_DESCRIPTION'),
					[
						{
							type: 'default',
							text: Loc.getMessage('TASKSMOBILE_DEADLINE_PICKER_CONFIRM_YES'),
							onPress: () => {
								const closestWorkDay = this.getClosestWorkDayForward(calendarSettings, itemValue);
								if (closestWorkDay)
								{
									resolve(closestWorkDay.getTime());
								}
								else
								{
									console.error('Could not find work day in the next 31 days');
									resolve(null);
								}
							},
						},
						{
							type: 'default',
							text: Loc.getMessage(`TASKSMOBILE_DEADLINE_PICKER_CONFIRM_NO_${itemId}`),
							onPress: () => resolve(itemValue),
						},
						{
							type: 'cancel',
							onPress: () => resolve(null),
						},
					],
				);
			});
		}

		/**
		 * @private
		 * @param {Date|number} clientDate
		 * @param {Calendar} calendarSettings
		 * @returns {Date}
		 */
		toServerDate(clientDate, calendarSettings)
		{
			const { serverOffset, clientOffset } = calendarSettings;
			const serverDate = new Date(clientDate);
			serverDate.setSeconds(serverOffset - clientOffset, 0);

			return serverDate;
		}

		/**
		 * @private
		 * @param {Date|number} serverDate
		 * @param {Calendar} calendarSettings
		 * @returns {Date}
		 */
		toClientDate(serverDate, calendarSettings)
		{
			const { serverOffset, clientOffset } = calendarSettings;
			const clientDate = new Date(serverDate);
			clientDate.setSeconds(clientOffset - serverOffset, 0);

			return clientDate;
		}
	}

	const iconPrefix = `${currentDomain}/bitrix/mobileapp/tasksmobile/extensions/tasks/deadline-picker/images/`;
	const iconMap = {
		[PickerItemType.CUSTOM]: `${iconPrefix}custom.svg`,
	};
	setTimeout(() => downloadImages(Object.values(iconMap)), 1000);

	module.exports = { DeadlinePicker, PickerItemType };
});
