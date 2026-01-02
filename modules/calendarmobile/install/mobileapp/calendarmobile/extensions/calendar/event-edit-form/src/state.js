/**
 * @module calendar/event-edit-form/state
 */
jn.define('calendar/event-edit-form/state', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { isEqual } = require('utils/object');

	const { BaseState, observeState } = require('calendar/state');
	const { DateHelper } = require('calendar/date-helper');
	const { AccessibilityManager } = require('calendar/event-edit-form/data-managers/accessibility-manager');
	const { LocationAccessibilityManager } = require('calendar/event-edit-form/data-managers/location-accessibility-manager');
	const { LocationManager } = require('calendar/data-managers/location-manager');
	const { SettingsManager } = require('calendar/data-managers/settings-manager');
	const { UserManager } = require('calendar/data-managers/user-manager');
	const {
		EventMeetingStatus,
		CalendarType,
		EventAccessibility,
		EventImportance,
		BooleanParams,
		AnalyticsSubSection,
	} = require('calendar/enums');

	const defaultEventName = Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_DEFAULT_EVENT_NAME');

	/**
	 * @class State
	 * @property {User} user
	 * @property {string} uuid
	 * @property {number} firstWeekday
	 * @property {string} recursionMode
	 * @property {Boolean} editAttendeesMode
	 *
	 * @property {number} parentId
	 * @property {number} sectionId
	 * @property {string} eventNameValue
	 * @property {string} description
	 * @property {string} color
	 * @property {User[]} attendees
	 * @property {boolean} fullDay
	 * @property {string} timezone
	 * @property {string} location
	 * @property {number} slotSize
	 * @property {?any} selectedSlot
	 * @property {number} reminder
	 * @property {array} reminderList
	 * @property {{[userId: number]: User}} excludedUsers
	 * @property {Object} recurrenceRule
	 * @property {string} privateEvent
	 * @property {string} accessibility
	 * @property {string} importance
	 * @property {array} existingFiles
	 * @property {array} uploadedFiles
	 * @property {array} permissions
	 * @property {number} collabId
	 * @property {number} meetingHost
	 * @property {number} customDateFrom
	 * @property {number} customDateTo
	 * @property {boolean} isCustomSelected
	 * @property {number} eventLength
	 *
	 * @property {function(value)} setEventNameValue
	 * @property {function(value)} setDescription
	 * @property {function(value)} setColor
	 * @property {function(value)} setAttendees
	 * @property {function(value)} setSelectedDayCode
	 * @property {function(value)} setSelectedSlot
	 * @property {function(value)} setReminder
	 * @property {function(value)} setSectionId
	 * @property {function(value)} setLocation
	 * @property {function(value)} setAccessibility
	 * @property {function(value)} setImportance
	 * @property {function(value)} setPrivateEvent
	 * @property {function(value)} setCustomDateFrom
	 * @property {function(value)} setCustomDateTo
	 * @property {function(value)} setIsCustomSelected
	 * @property {function(value)} setEventLength
 	 */
	class State extends BaseState
	{
		get excludedUserIds()
		{
			return Object.keys(this.excludedUsers).map((userId) => parseInt(userId, 10));
		}

		get selectedDate()
		{
			return DateHelper.getDateFromDayCode(this.selectedDayCode);
		}

		get slots()
		{
			const { userIds, date } = this.getAccessibilityParams();

			const slotSize = this.slotSize;
			const skipEventId = this.parentId;

			return AccessibilityManager.calculateSlots({ userIds, date, slotSize, skipEventId });
		}

		get hasAccessibility()
		{
			const { userIds, date } = this.getAccessibilityParams();

			const today = new Date().setHours(0, 0, 0, 0);
			if (date.getTime() < today)
			{
				return true;
			}

			return AccessibilityManager.hasAccessibility({ userIds, date });
		}

		get locationReservedInfo()
		{
			if (!this.selectedSlot && !this.isCustomSelected)
			{
				return {};
			}

			const { date, fromTs, toTs } = this.getLocationAccessibilityParams();

			const skipEventId = this.parentId;

			return LocationAccessibilityManager.calculateReservedLocations({ date, fromTs, toTs, skipEventId });
		}

		get hasLocationAccessibility()
		{
			const date = this.selectedDate;
			const today = new Date().setHours(0, 0, 0, 0);
			if (date.getTime() < today)
			{
				return true;
			}

			return LocationAccessibilityManager.hasAccessibility({ date });
		}

		get hasCustomPeriod()
		{
			return this.customDateFrom > 0 && this.customDateTo > 0;
		}

		/**
		 * @param props
		 * @param {User} [props.user]
		 * @param {Event} [props.event]
		 * @param {number} [props.ownerId]
		 * @param {string} [props.calType]
		 * @param {number} [props.sectionId]
		 * @param {number} [props.firstWeekday]
		 * @param {string} [props.recursionMode]
		 * @param {number} [props.createChatId]
		 * @param {string} [props.uuid]
		 * @param {number} [props.selectedDayTs]
		 * @param {array} [props.participantsEntityList]
		 * @param {string} [props.description]
		 * @param {boolean} [props.editAttendeesMode]
		 */
		initNewForm(props)
		{
			this.initBaseParams(props);

			if (props.event)
			{
				this.initEditFormParams(props);
			}
			else
			{
				this.initCreateFormParams(props);
			}

			Object.assign(this, this.getInitialProps());

			AccessibilityManager.init();
			LocationAccessibilityManager.init();
		}

		initBaseParams(props)
		{
			this.id = `tmp-id-${Date.now()}`;
			this.uuid = props.uuid ?? `calendar-view-form-${Date.now()}`;
			this.initialProps = {};
			this.editAttendeesMode = props.editAttendeesMode ?? false;

			this.ownerId = props.ownerId;
			this.calType = props.calType;
			this.createChatId = props.createChatId;
			this.sectionId = props.sectionId;
			this.user = props.user;
			this.firstWeekday = props.firstWeekday;
			this.recursionMode = props.recursionMode;
			this.todayButtonClick = false;
			this.originalDateFrom = null;
		}

		initCreateFormParams(props)
		{
			this.selectedDayCode = props.selectedDayTs
				? DateHelper.getDayCode(new Date(Math.max(props.selectedDayTs, Date.now())))
				: DateHelper.getDayCode(new Date())
			;

			if (Type.isArrayFilled(props.participantsEntityList))
			{
				const attendeesIds = props.participantsEntityList.map((userId) => parseInt(userId, 10));
				const users = UserManager.getUsers(attendeesIds);
				const attendees = users.map((user) => this.#getUserData(
					user,
					user.id === Number(env.userId) ? EventMeetingStatus.HOST : EventMeetingStatus.QUESTIONED,
				));

				this.initialProps.attendees = attendees.sort((a, b) => this.#compareUser(a, b));
			}

			if (Type.isStringFilled(props.description))
			{
				this.initialProps.description = props.description;
			}
		}

		initEditFormParams(props)
		{
			const event = props.event;
			const from = event.dateFromTs;
			const to = event.dateToTs - (event.fullDay ? DateHelper.dayLength : 0);

			this.selectedDayCode = DateHelper.getDayCode(new Date(Math.max(from, Date.now())));
			this.originalDateFrom = new Date(from);

			const attendeesIds = event.attendees.map((user) => user?.id);
			const users = UserManager.getUsers(attendeesIds);
			const attendees = users.map((user) => this.#getUserData(
				user,
				this.#getAttendeeStatus(event.attendees, user.id, EventMeetingStatus.HOST),
			));

			this.id = event.id;
			this.initialProps = {
				parentId: event.parentId,
				sectionId: event.sectionId,
				eventNameValue: event.name,
				description: event.description,
				color: event.color,
				attendees: attendees.sort((a, b) => this.#compareUser(a, b)),
				timezone: event.timezone,
				location: LocationManager.getTextLocation(event.location),
				fullDay: event.fullDay,
				meetingHost: event.meetingHost,
				recurrenceRule: event.recurrenceRule,
				reminder: event.reminders[0]?.count ?? -1,
				reminderList: event.reminders,
				accessibility: event.accessibility,
				importance: event.importance,
				privateEvent: event.privateEvent ? BooleanParams.YES : BooleanParams.NO,
				permissions: event.permissions,
				collabId: event.collabId,
				existingFiles: event.files ?? [],
				uploadedFiles: [],
				excludedUsers: {},
				customDateFrom: from,
				customDateTo: to,
				isCustomSelected: true,
				eventLength: event.eventLength - (event.fullDay ? DateHelper.dayLength / 1000 : 0),
			};
		}

		hasChanges()
		{
			const initialProps = this.getInitialProps();

			return Object.keys(initialProps).some((prop) => !isEqual(this[prop], initialProps[prop]));
		}

		/**
		 * @private
		 */
		getInitialProps()
		{
			const defaultProps = {
				parentId: 0,
				sectionId: this.sectionId,
				eventNameValue: '',
				description: '',
				color: '',
				attendees: this.#getDefaultAttendee(),
				location: '',
				fullDay: false,
				meetingHost: 0,
				timezone: SettingsManager.getUserTimezoneName(),
				recurrenceRule: null,
				slotSize: 60,
				selectedSlot: null,
				reminder: 15,
				accessibility: EventAccessibility.BUSY,
				importance: EventImportance.NORMAL,
				privateEvent: BooleanParams.NO,
				excludedUsers: {},
				existingFiles: [],
				uploadedFiles: [],
				permissions: {},
				collabId: 0,
				customDateFrom: 0,
				customDateTo: 0,
				eventLength: 0,
				isCustomSelected: false,
			};

			return { ...defaultProps, ...this.initialProps };
		}

		setSlotSize(slotSize)
		{
			this.slotSize = slotSize;
			this.selectedSlot = null;
		}

		setSelectedDayCode(selectedDayCode)
		{
			this.selectedDayCode = selectedDayCode;
			this.todayButtonClick = false;

			if (!this.isEditForm())
			{
				this.selectedSlot = null;
				this.isCustomSelected = false;
				this.customDateFrom = 0;
				this.customDateTo = 0;
				this.eventLength = 0;
				this.fullDay = false;
			}
		}

		setSelectedSlot(selectedSlot)
		{
			this.selectedSlot = selectedSlot;
			this.isCustomSelected = false;
		}

		setIsCustomSelected(isCustomSelected)
		{
			this.isCustomSelected = isCustomSelected;
			this.selectedSlot = null;
		}

		setCustomDateFrom(timestamp)
		{
			this.customDateFrom = timestamp;

			if (this.eventLength === 0)
			{
				if (this.customDateTo > 0)
				{
					this.eventLength = (this.customDateTo - this.customDateFrom) / 1000;
				}
				else
				{
					this.eventLength = this.fullDay ? 0 : 3600;
				}
			}

			this.customDateTo = timestamp + this.eventLength * 1000;
		}

		setCustomDateTo(timestamp)
		{
			if (timestamp < this.customDateFrom)
			{
				return;
			}

			this.customDateTo = timestamp;

			if (this.customDateFrom > 0)
			{
				this.eventLength = (timestamp - this.customDateFrom) / 1000;
			}
		}

		setTodayButtonClick(todayButtonClick)
		{
			this.todayButtonClick = todayButtonClick;
		}

		setDescriptionParams(descriptionParams)
		{
			const { description, existingFiles, uploadedFiles } = descriptionParams;

			this.description = description;
			this.existingFiles = existingFiles;
			this.uploadedFiles = uploadedFiles;
		}

		addExcludedUsers(users)
		{
			for (const user of users)
			{
				this.excludedUsers[user.ID] = user;
			}
		}

		clearExcludedUsers()
		{
			this.excludedUsers = {};
		}

		getFields()
		{
			const id = this.id.toString().startsWith('tmp') ? 0 : this.id;
			const from = this.isCustomSelected
				? new Date(this.customDateFrom)
				: new Date(this.selectedSlot.from)
			;
			const to = this.isCustomSelected
				? new Date(this.customDateTo)
				: new Date(this.selectedSlot.to)
			;
			const skipTime = this.isCustomSelected && this.fullDay
				? BooleanParams.YES
				: BooleanParams.NO
			;

			const fields = {
				id,
				section: this.sectionId,
				name: this.eventNameValue || defaultEventName,
				desc: this.description,
				color: this.color,
				EVENT_RRULE: null,
				date_from: DateHelper.formatDate(from),
				date_to: DateHelper.formatDate(to),
				skip_time: skipTime,
				time_from: DateHelper.formatTime(from),
				time_to: DateHelper.formatTime(to),
				location: LocationManager.prepareTextLocation(this.location),
				tz_from: this.timezone,
				tz_to: this.timezone,
				reminder: this.#getReminderForEdit(),
				meeting_notify: BooleanParams.YES,
				exclude_users: this.excludedUserIds.join(','),
				attendeesEntityList: this.attendees.map((user) => ({
					entityId: 'user',
					id: user.id,
				})),
				sendInvitesAgain: BooleanParams.NO,
				hide_guests: BooleanParams.YES,
				private_event: this.privateEvent,
				accessibility: this.accessibility,
				importance: this.importance,
				checkCurrentUsersAccessibility: BooleanParams.YES,
				doCheckOccupancy: BooleanParams.YES,
				analyticsSubSection: this.#getFormAnalyticsContext(),
				uploaded_files: this.uploadedFiles.map((file) => file.token),
				UF_WEBDAV_CAL_EVENT: this.existingFiles.map((file) => file.id),
			};

			if (id > 0 && Boolean(this.recurrenceRule))
			{
				fields.EVENT_RRULE = this.recurrenceRule;
			}

			if (this.recursionMode)
			{
				fields.rec_edit_mode = this.recursionMode;
				fields.current_date_from = DateHelper.formatDate(this.originalDateFrom);
			}

			if (this.createChatId)
			{
				fields.analyticsChatId = this.createChatId;
			}

			return fields;
		}

		getReduxFields()
		{
			const initialAttendees = this.getInitialProps().attendees;

			return {
				id: this.id,
				parentId: this.parentId,
				dateFromTs: this.isCustomSelected ? this.customDateFrom : this.selectedSlot.from,
				dateToTs: this.isCustomSelected ? this.customDateTo : this.selectedSlot.to,
				fullDay: this.isCustomSelected ? this.fullDay : false,
				sectionId: this.sectionId,
				name: this.eventNameValue || defaultEventName,
				description: this.description,
				location: this.location,
				accessibility: this.accessibility,
				importance: this.importance,
				privateEvent: this.privateEvent,
				color: this.color,
				calType: this.calType,
				ownerId: this.ownerId,
				attendees: this.attendees.map((user) => ({
					id: user.id,
					status: this.#getAttendeeStatus(initialAttendees, user.id, EventMeetingStatus.QUESTIONED),
				})),
				reminders: this.#getReminderForEdit(),
				files: this.existingFiles,
				permissions: this.permissions,
				collabId: this.collabId,
				meetingStatus: this.#getAttendeeStatus(initialAttendees, Number(env.userId), EventMeetingStatus.HOST),
				hasUploadedFiles: Type.isArrayFilled(this.uploadedFiles),
				eventLength: this.isCustomSelected
					? ((this.customDateTo - this.customDateFrom) / 1000)
					: ((this.selectedSlot.to - this.selectedSlot.from) / 1000)
				,
			};
		}

		getInitialReduxFields()
		{
			if (!this.isEditForm())
			{
				return null;
			}

			const initialProps = this.getInitialProps();

			return {
				id: this.id,
				parentId: initialProps.parentId,
				dateFromTs: initialProps.customDateFrom,
				dateToTs: initialProps.customDateTo,
				fullDay: initialProps.fullDay,
				sectionId: initialProps.sectionId,
				name: initialProps.eventNameValue || defaultEventName,
				description: initialProps.description,
				location: initialProps.location,
				accessibility: initialProps.accessibility,
				importance: initialProps.importance,
				privateEvent: initialProps.privateEvent,
				color: initialProps.color,
				calType: this.calType,
				attendees: initialProps.attendees,
				reminders: initialProps.reminderList,
				files: initialProps.existingFiles,
				permissions: initialProps.permissions,
				collabId: initialProps.collabId,
				meetingStatus: this.#getAttendeeStatus(initialProps.attendees, Number(env.userId), EventMeetingStatus.HOST),
				eventLength: (this.customDateTo - this.customDateFrom) / 1000,
			};
		}

		isEditForm()
		{
			return typeof this.id === 'number';
		}

		async loadAccessibility()
		{
			const { userIds, date } = this.getAccessibilityParams();

			const today = new Date().setHours(0, 0, 0, 0);
			if (date.getTime() >= today)
			{
				await AccessibilityManager.loadAccessibility({ userIds, date });
			}

			this.emit();
		}

		async loadLocationAccessibility()
		{
			const date = this.selectedDate;
			const today = new Date().setHours(0, 0, 0, 0);
			if (date.getTime() >= today)
			{
				await LocationAccessibilityManager.loadAccessibility({ date });
			}

			this.emit();
		}

		/**
		 * @private
		 */
		getAccessibilityParams()
		{
			const date = this.selectedDate;
			const userIds = this.attendees.map((attendee) => attendee.id);

			return { userIds, date };
		}

		/**
		 * @private
		 */
		getLocationAccessibilityParams()
		{
			const date = this.selectedDate;
			const fromTs = this.isCustomSelected
				? this.customDateFrom
				: this.selectedSlot.from
			;
			const toTs = this.isCustomSelected
				? this.customDateTo
				: this.selectedSlot.to
			;

			return { date, fromTs, toTs };
		}

		#getDefaultAttendee()
		{
			return [{
				id: this.user.id,
				name: this.user.name,
				workPosition: this.user.workPosition,
				isCollaber: this.user.isCollaber,
				isExtranet: this.user.isExtranet,
				status: EventMeetingStatus.HOST,
			}];
		}

		#getUserData(user, status)
		{
			const { id, fullName, workPosition, isCollaber, isExtranet } = user;

			return { id, name: fullName, workPosition, isCollaber, isExtranet, status };
		}

		#getAttendeeStatus(attendees, userId, defaultStatus = EventMeetingStatus.QUESTIONED)
		{
			return attendees.find((it) => it.id === userId)?.status ?? defaultStatus;
		}

		#getReminderForEdit()
		{
			const currentReminder = this.reminder.toString();

			if (this.reminder === -1)
			{
				return [];
			}

			if (!Type.isArrayFilled(this.reminderList))
			{
				return this.reminder >= 0 ? [currentReminder] : [];
			}

			const reminders = this.reminderList.map((reminder) => this.#formatReminder(reminder));
			if (!reminders.includes(currentReminder))
			{
				reminders.push(currentReminder);
			}

			return reminders;
		}

		#formatReminder(reminder)
		{
			if (Type.isInteger(parseInt(reminder.before, 10)) && Type.isInteger(parseInt(reminder.time, 10)))
			{
				return `daybefore|${reminder.before}|${reminder.time}`;
			}

			if (reminder.type === 'date')
			{
				return `date|${reminder.value}`;
			}

			if (reminder.type === 'min')
			{
				return reminder.count.toString();
			}

			if (reminder.type === 'hour')
			{
				return (parseInt(reminder.count, 10) * 60).toString();
			}

			if (reminder.type === 'day')
			{
				return (parseInt(reminder.count, 10) * 60 * 24).toString();
			}

			return reminder.toString();
		}

		#getFormAnalyticsContext()
		{
			if (this.createChatId)
			{
				return AnalyticsSubSection.CHAT;
			}

			if (this.calType === CalendarType.GROUP)
			{
				return AnalyticsSubSection.COLLAB;
			}

			return AnalyticsSubSection.PERSONAL;
		}

		#compareUser(first, second)
		{
			if (first.id === this.ownerId)
			{
				return -1;
			}

			if (second.id === this.ownerId)
			{
				return 1;
			}

			if (first.status === EventMeetingStatus.HOST)
			{
				return -1;
			}

			if (second.status === EventMeetingStatus.HOST)
			{
				return 1;
			}

			return first.id > second.id ? 1 : -1;
		}
	}

	const state = new State();

	module.exports = {
		State: state,
		observeState: (component, mapStateToProps) => observeState(component, mapStateToProps, state),
	};
});
