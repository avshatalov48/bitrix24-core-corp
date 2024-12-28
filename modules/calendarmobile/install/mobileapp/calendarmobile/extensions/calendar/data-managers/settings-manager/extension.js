/**
 * @module calendar/data-managers/settings-manager
 */
jn.define('calendar/data-managers/settings-manager', (require, exports, module) => {
	const { SettingsAjax } = require('calendar/ajax');

	class SettingsManager
	{
		setSettings(settings)
		{
			this.setBaseSettings(settings);
			this.setUserSettings(settings);
			this.setCalendarSettings(settings);
		}

		setBaseSettings(settings)
		{
			this.setFirstWeekday(settings.firstWeekday);
			this.setMeetSectionId(settings.meetSectionId);
			this.setPathToUserCalendar(settings.pathToUserCalendar);
			this.setUserTimezoneName(settings.userTimezoneName);
		}

		setUserSettings(settings)
		{
			this.setDenyBusyInvitation(settings.denyBusyInvitation);
			this.setShowWeekNumbers(settings.showWeekNumbers);
			this.setShowDeclined(settings.showDeclined);
		}

		setCalendarSettings(settings)
		{
			this.setWorkTimeStart(BX.prop.getNumber(settings, 'workTimeStart', 9));
			this.setWorkTimeEnd(BX.prop.getNumber(settings, 'workTimeEnd', 19));
			this.setWeekHolidays(settings.weekHolidays);
			this.setYearHolidays(settings.yearHolidays);
			this.setUserTimezoneName(settings.userTimezoneName);
		}

		setFirstWeekday(firstWeekday)
		{
			this.firstWeekday = firstWeekday;
		}

		setMeetSectionId(meetSectionId)
		{
			this.meetSectionId = meetSectionId;
		}

		setPathToUserCalendar(pathToUserCalendar)
		{
			this.pathToUserCalendar = pathToUserCalendar;
		}

		setDenyBusyInvitation(denyBusyInvitation)
		{
			this.denyBusyInvitation = denyBusyInvitation;
		}

		setShowWeekNumbers(showWeekNumbers)
		{
			this.showWeekNumbers = showWeekNumbers;
		}

		setShowDeclined(showDeclined)
		{
			this.showDeclined = showDeclined;
		}

		setUserTimezoneName(timezoneName)
		{
			this.userTimezoneName = timezoneName;
		}

		setWorkTimeStart(workTimeStart)
		{
			this.workTimeStart = Math.round(
				(Math.floor(workTimeStart) + 5 * (workTimeStart - Math.floor(workTimeStart)) / 3) * 1000
			) / 1000;
		}

		setWorkTimeEnd(workTimeEnd)
		{
			this.workTimeEnd = Math.round(
				(Math.floor(workTimeEnd) + 5 * (workTimeEnd - Math.floor(workTimeEnd)) / 3) * 1000
			) / 1000;
		}

		setWeekHolidays(weekHolidays)
		{
			this.weekHolidays = weekHolidays;
		}

		setYearHolidays(yearHolidays)
		{
			this.yearHolidays = yearHolidays;
		}

		getFirstWeekday()
		{
			return this.firstWeekday;
		}

		getMeetSectionId()
		{
			return this.meetSectionId;
		}

		getPathToUserCalendar()
		{
			return this.pathToUserCalendar;
		}

		getUserTimezoneName()
		{
			return this.userTimezoneName;
		}

		getWorkTimeStart()
		{
			return this.workTimeStart;
		}

		getWorkTimeEnd()
		{
			return this.workTimeEnd;
		}

		getWeekHolidays()
		{
			return this.weekHolidays;
		}

		getYearHolidays()
		{
			return this.yearHolidays;
		}

		isDenyBusyInvitationEnabled()
		{
			return this.denyBusyInvitation;
		}

		isShowWeekNumbersEnabled()
		{
			return this.showWeekNumbers;
		}

		isShowDeclinedEnabled()
		{
			return this.showDeclined;
		}

		/**
		 * @param {Boolean} denyBusyInvitation
		 */
		switchDenyBusyInvitation(denyBusyInvitation)
		{
			const data = denyBusyInvitation ? 'Y' : 'N';
			void SettingsAjax.setDenyBusyInvitation(data);
			this.setDenyBusyInvitation(denyBusyInvitation);
		}

		/**
		 * @param {Boolean} showWeekNumbers
		 */
		switchShowWeekNumbers(showWeekNumbers)
		{
			const data = showWeekNumbers ? 'Y' : 'N';
			void SettingsAjax.setShowWeekNumbers(data);
			this.setShowWeekNumbers(showWeekNumbers);
		}

		/**
		 * @param {Boolean} showDeclined
		 */
		switchShowDeclined(showDeclined)
		{
			const data = showDeclined ? 'Y' : 'N';
			void SettingsAjax.setShowDeclined(data);
			this.setShowDeclined(showDeclined);
		}
	}

	module.exports = { SettingsManager: new SettingsManager() };
});
