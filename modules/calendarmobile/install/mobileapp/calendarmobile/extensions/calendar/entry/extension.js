/**
 * @module calendar/entry
 */
jn.define('calendar/entry', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { checkDisabledToolById } = require('settings/disabled-tools');
	const { InfoHelper } = require('layout/ui/info-helper');
	const { CalendarType } = require('calendar/enums');

	/**
	 * @class Entry
	 */
	class Entry
	{
		/**
		 * @public
		 * @param userId {number}
		 * @param title {string}
		 */
		static openUserCalendarView({ userId, title })
		{
			void this.#openCalendarView({
				title,
				calType: CalendarType.USER,
				ownerId: userId,
			});
		}

		/**
		 * @public
		 */
		static openCompanyCalendarView({ title })
		{
			void this.#openCalendarView({
				title,
				calType: CalendarType.COMPANY,
				ownerId: 0,
			});
		}

		/**
		 * @public
		 * @param groupId {number}
		 * @param title {string}
		 */
		static openGroupCalendarView({
			groupId,
			title = Loc.getMessage('M_CALENDAR_ENTRY_EVENT_COLLAB_LIST_TITLE'),
		})
		{
			void this.#openCalendarView({
				title,
				calType: CalendarType.GROUP,
				ownerId: groupId,
			});
		}

		/**
		 * @private
		 * @param calType {string}
		 * @param ownerId {number}
		 * @param title {string}
		 */
		static async #openCalendarView({
			calType,
			ownerId,
			title,
		})
		{
			const calendarAvailable = await Entry.checkToolAvailable();
			if (!calendarAvailable)
			{
				return;
			}

			PageManager.openComponent('JSStackComponent', {
				name: 'JSStackComponent',
				componentCode: 'calendar:calendar.event.list',
				canOpenInDefault: true,
				// eslint-disable-next-line no-undef
				scriptPath: availableComponents['calendar:calendar.event.list'].publicUrl,
				rootWidget: {
					name: 'layout',
					settings: {
						objectName: 'layout',
						useLargeTitleMode: true,
						titleParams: {
							text: title,
							type: 'section',
						},
					},
				},
				params: {
					CAL_TYPE: calType,
					OWNER_ID: ownerId,
				},
			});
		}

		/**
		 * @public
		 * @param params
		 * @param ownerId {number}
		 * @param calType {string}
		 * @param createChatId {number}
		 * @param uuid {string}
		 */
		static async openEventEditForm({
			parentLayout = null,
			ownerId,
			calType = 'user',
			description = '',
			participantsEntityList = [],
			createChatId = null,
			uuid = null,
		})
		{
			const calendarAvailable = await Entry.checkToolAvailable();
			if (!calendarAvailable)
			{
				return;
			}

			if (parentLayout)
			{
				const { EventEditForm } = await requireLazy('calendar:event-edit-form');

				await EventEditForm.initEditForm({
					ownerId,
					calType,
					participantsEntityList,
					description,
					createChatId,
					uuid,
					showLoading: true,
				});

				EventEditForm.openPage(parentLayout);
			}
			else
			{
				PageManager.openComponent('JSStackComponent', {
					name: 'JSStackComponent',
					componentCode: 'calendar:calendar.event-edit-form',
					canOpenInDefault: true,
					// eslint-disable-next-line no-undef
					scriptPath: availableComponents['calendar:calendar.event-edit-form'].publicUrl,
					rootWidget: {
						name: 'layout',
						settings: {
							enableNavigationBarBorder: false,
							objectName: 'layout',
							backgroundColor: Color.bgSecondary.toHex(),
							titleParams: {
								text: Loc.getMessage('M_CALENDAR_ENTRY_EVENT_VIEW_FORM_TITLE'),
								type: 'wizard',
							},
							backdrop: {
								navigationBarColor: Color.bgSecondary.toHex(),
								onlyMediumPosition: false,
								showOnTop: true,
								topPosition: 70,
								swipeAllowed: false,
								adoptHeightByKeyboard: false,
							},
						},
					},
					params: {
						OWNER_ID: ownerId,
						CAL_TYPE: calType,
						DESCRIPTION: description,
						PARTICIPANTS_ENTITY_LIST: participantsEntityList,
						CREATE_CHAT_ID: createChatId,
						UUID: uuid,
					},
				});
			}
		}

		/**
		 * @public
		 * @param eventId {number}
		 * @param eventDate {string}
		 * @return {void}
		 */
		static async openEventViewForm({ eventId, eventDate = '' })
		{
			const calendarAvailable = await Entry.checkToolAvailable();
			if (!calendarAvailable)
			{
				return;
			}

			PageManager.openComponent('JSStackComponent', {
				name: 'JSStackComponent',
				componentCode: 'calendar:calendar.event-view-form',
				canOpenInDefault: true,
				// eslint-disable-next-line no-undef
				scriptPath: availableComponents['calendar:calendar.event-view-form'].publicUrl,
				rootWidget: {
					name: 'layout',
					settings: {
						titleParams: {
							text: Loc.getMessage('M_CALENDAR_ENTRY_EVENT_EDIT_FORM_TITLE'),
							type: 'entity',
						},
						objectName: 'layout',
					},
				},
				params: {
					EVENT_ID: eventId,
					EVENT_DATE: eventDate,
				},
			});
		}

		static async checkToolAvailable()
		{
			const toolDisabled = await checkDisabledToolById('calendar');
			if (toolDisabled)
			{
				const sliderUrl = await InfoHelper.getUrlByCode('limit_office_calendar_off');
				helpdesk.openHelp(sliderUrl);

				return false;
			}

			return true;
		}
	}

	if (typeof jnComponent?.preload === 'function')
	{
		const componentCode = 'calendar:calendar.event-view-form';

		// eslint-disable-next-line no-undef
		const { publicUrl } = availableComponents[componentCode] || {};

		if (publicUrl)
		{
			setTimeout(() => jnComponent.preload(publicUrl), 1000);
		}
	}

	module.exports = { Entry };
});
