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
		NO_DEADLINE: 'NO_DEADLINE',
		CUSTOM: 'CUSTOM',
	};

	const PickerSectionCode = {
		PREDEFINED: 'PREDEFINED',
		CUSTOM: 'CUSTOM',
	};

	class DeadlinePicker
	{
		constructor(params = {})
		{
			this.parentWidget = (params.parentWidget || PageManager);
			this.canSetNoDeadline = BX.prop.getBoolean(params, 'canSetNoDeadline', false);
		}

		/**
		 * @public
		 * @param {number} deadline
		 * @returns {Promise}
		 */
		show(deadline)
		{
			return new Promise((resolve, reject) => {
				this.getItems(deadline, resolve, reject)
					.then((items) => {
						new ContextMenu({
							params: {
								title: Loc.getMessage('TASKSMOBILE_DEADLINE_PICKER_TITLE'),
								isRawIcon: true,
								showCancelButton: true,
							},
							actions: items,
							onCancel: () => reject(),
						}).show(this.parentWidget);
					})
					.catch(console.error)
				;
			});
		}

		/**
		 * @private
		 * @param {number} deadline
		 * @param {function} onDeadlineSelect
		 * @param {function} onCancel
		 * @returns {Promise<[Array]>}
		 */
		async getItems(deadline, onDeadlineSelect, onCancel)
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
							(eventName, ts) => {
								if (eventName === 'onPick' && ts)
								{
									onDeadlineSelect(ts);
								}
								else
								{
									onCancel();
								}
							},
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
				{
					id: PickerItemType.NO_DEADLINE,
					sectionCode: PickerSectionCode.PREDEFINED,
					title: Loc.getMessage('TASKSMOBILE_DEADLINE_PICKER_ITEM_NO_DEADLINE'),
					onClickCallback: () => onDeadlineSelect(predefinedItemValues.get(PickerItemType.NO_DEADLINE)),
				},
			];

			return Object.values(predefinedItems)
				.filter((item) => (
					(this.canSetNoDeadline && item.id === PickerItemType.NO_DEADLINE)
					|| predefinedItemValues.get(item.id)
				))
				.map((item) => {
					const itemValue = predefinedItemValues.get(item.id);

					return {
						...item,
						subtitle: (item.id !== PickerItemType.NO_DEADLINE && this.formatDate(itemValue)),
						isSelected: (deadline === itemValue),
					};
				})
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
				[PickerItemType.NO_DEADLINE, null],
			]);

			// today
			const { hours, minutes } = calendarSettings.workTime[0].end;
			const today = new Date(new Date(now).setHours(hours, minutes, 0, 0));
			if (now.getTime() < today.getTime())
			{
				values.set(PickerItemType.TODAY, today.getTime());
			}

			// tomorrow
			const tomorrow = new Date(today);
			tomorrow.setDate(tomorrow.getDate() + 1);
			values.set(PickerItemType.TOMORROW, tomorrow.getTime());

			const thisWeekLastDay = this.getLastDayOfWeek(today);
			const nextWeekFirstDay = new Date(thisWeekLastDay);
			nextWeekFirstDay.setDate(nextWeekFirstDay.getDate() + 1);
			const nextWeekLastDay = new Date(thisWeekLastDay);
			nextWeekLastDay.setDate(nextWeekLastDay.getDate() + 7);

			// this week
			const thisWeekLastWorkDay = this.getClosestWorkDayBackward(
				calendarSettings,
				thisWeekLastDay,
				today,
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
			const startDate = new Date(clientStartDate);
			const stopDate = new Date(clientStopDate);

			while (startDate.getTime() > stopDate.getTime())
			{
				if (calendarSettings.isWeekendInLocal(startDate) || calendarSettings.isHolidayInLocal(startDate))
				{
					startDate.setDate(startDate.getDate() - 1);
				}
				else
				{
					break;
				}
			}

			return new Date(startDate);
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
			const startDate = new Date(clientStartDate);
			const stopDate = new Date(clientStopDate);

			while (startDate.getTime() <= stopDate.getTime())
			{
				if (calendarSettings.isWeekendInLocal(startDate) || calendarSettings.isHolidayInLocal(startDate))
				{
					startDate.setDate(startDate.getDate() + 1);
				}
				else
				{
					break;
				}
			}

			if (startDate.getTime() > stopDate.getTime())
			{
				return null;
			}

			return new Date(startDate);
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
	}

	const iconPrefix = `${currentDomain}/bitrix/mobileapp/tasksmobile/extensions/tasks/deadline-picker/images/`;
	const iconMap = {
		[PickerItemType.CUSTOM]: `${iconPrefix}custom.svg`,
	};
	setTimeout(() => downloadImages(Object.values(iconMap)), 1000);

	module.exports = { DeadlinePicker, PickerItemType };
});
