/**
 * @module calendar/event-list-view/section-list/manager
 */
jn.define('calendar/event-list-view/section-list/manager', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { SectionManager } = require('calendar/data-managers/section-manager');
	const { SettingsManager } = require('calendar/data-managers/settings-manager');
	const { CalendarType, SectionExternalTypes } = require('calendar/enums');

	/**
	 * @class SectionListManager
	 */
	class SectionListManager
	{
		constructor(props)
		{
			this.ownerId = props.ownerId;
			this.calType = props.calType;
			this.sections = SectionManager.getSortedSections();
		}

		getData()
		{
			return {
				local: this.#getLocalSections(),
				users: this.#getUsersSections(),
				additional: this.#getAdditionalSections(),
				sync: this.#getSyncSections(),
			};
		}

		#getLocalSections()
		{
			const sections = this.sections.filter((section) => {
				return section.isLocal() && this.#belongsToView(section);
			});

			return {
				sections,
				header: this.#getLocalSectionsTitle(),
			};
		}

		#getUsersSections()
		{
			const result = [];
			const trackingUserList = SectionManager.getTrackingUserList();

			if (!Type.isArrayFilled(trackingUserList))
			{
				return result;
			}

			for (const user of trackingUserList)
			{
				const userId = Number(user.ID);
				const sections = this.sections.filter((section) => {
					return !this.#belongsToView(section) && section.isUserCalendar(userId);
				});

				if (Type.isArrayFilled(sections))
				{
					result.push({
						sections,
						header: this.#getUsersSectionsTitle(user, userId),
					});
				}
			}

			return result;
		}

		#getAdditionalSections()
		{
			const result = [];

			const additionalSections = {
				company_calendar: this.sections.filter((section) => {
					return section.isCompanyCalendar() && !this.#belongsToView(section);
				}),
				group: this.sections.filter((section) => {
					return section.isGroupCalendar() && !section.isCollab() && !this.#belongsToView(section);
				}),
				collab: this.sections.filter((section) => {
					return section.isGroupCalendar() && section.isCollab() && !this.#belongsToView(section);
				}),
			};

			Object.entries(additionalSections).forEach(([calType, sections]) => {
				if (Type.isArrayFilled(sections))
				{
					result.push({
						sections,
						header: this.#getAdditionalSectionsTitle(calType),
					});
				}
			});

			return result;
		}

		#getSyncSections()
		{
			const result = [];
			const syncSections = this.sections.filter((section) => {
				return !section.isLocal() && this.#belongsToView(section);
			});

			if (!Type.isArrayFilled(syncSections))
			{
				return result;
			}

			const externalTypeSections = {
				google: syncSections.filter((section) => section.isGoogle()),
				icloud: syncSections.filter((section) => section.isIcloud()),
				office365: syncSections.filter((section) => section.isOffice365()),
				exchange: syncSections.filter((section) => section.isExchange()),
				external: syncSections.filter((section) => section.isExternal()),
			};

			Object.entries(externalTypeSections).forEach(([externalType, sections]) => {
				if (Type.isArrayFilled(sections))
				{
					result.push({
						sections,
						header: this.#getSyncSectionsTitle(externalType),
					});
				}
			});

			return result;
		}

		#getLocalSectionsTitle()
		{
			if (this.calType === CalendarType.USER)
			{
				return this.ownerId === Number(env.userId)
					? Loc.getMessage('M_CALENDAR_EVENT_LIST_SECTION_LIST_TITLE_MY')
					: Loc.getMessage('M_CALENDAR_EVENT_LIST_SECTION_LIST_TITLE_THIS_USER')
				;
			}

			if (this.calType === CalendarType.GROUP)
			{
				return SettingsManager.isCollabCalendarContext()
					? Loc.getMessage('M_CALENDAR_EVENT_LIST_SECTION_LIST_TITLE_THIS_COLLAB')
					: Loc.getMessage('M_CALENDAR_EVENT_LIST_SECTION_LIST_TITLE_THIS_GROUP')
				;
			}

			return Loc.getMessage('M_CALENDAR_EVENT_LIST_SECTION_LIST_TITLE_COMPANY');
		}

		#getUsersSectionsTitle(user, userId)
		{
			return Number(env.userId) === userId
				? Loc.getMessage('M_CALENDAR_EVENT_LIST_SECTION_LIST_TITLE_MY')
				: user.FORMATTED_NAME
			;
		}

		#getAdditionalSectionsTitle(calType)
		{
			switch (calType)
			{
				case CalendarType.COMPANY_CALENDAR:
					return Loc.getMessage('M_CALENDAR_EVENT_LIST_SECTION_LIST_TITLE_COMPANY');
				case CalendarType.GROUP:
					return Loc.getMessage('M_CALENDAR_EVENT_LIST_SECTION_LIST_TITLE_GROUP');
				default:
					return Loc.getMessage('M_CALENDAR_EVENT_LIST_SECTION_LIST_TITLE_COLLAB');
			}
		}

		#getSyncSectionsTitle(externalType)
		{
			switch (externalType)
			{
				case SectionExternalTypes.GOOGLE:
				case SectionExternalTypes.ICLOUD:
				case SectionExternalTypes.OFFICE365:
				case SectionExternalTypes.EXCHANGE:
					return Loc.getMessage(`M_CALENDAR_EVENT_LIST_SECTION_LIST_TITLE_${externalType.toUpperCase()}`);
				default:
					return Loc.getMessage('M_CALENDAR_EVENT_LIST_SECTION_LIST_TITLE_DEFAULT_EXTERNAL');
			}
		}

		#belongsToView(section)
		{
			return section.getType() === this.calType && section.getOwnerId() === this.ownerId;
		}
	}

	module.exports = { SectionListManager };
});
