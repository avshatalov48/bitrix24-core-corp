/**
 * @module calendar/enums
 */
jn.define('calendar/enums', (require, exports, module) => {
	const CalendarType = {
		USER: 'user',
		GROUP: 'group',
		COMPANY_CALENDAR: 'company_calendar',
		LOCATION: 'location',
		OPEN_EVENTS: 'open_events',
	};

	const EventTypes = {
		SHARED: '#shared#',
		SHARED_CRM: '#shared_crm#',
		COLLAB: '#collab#',
		SHARED_COLLAB: '#shared_collab#',
	};

	const EventFormFields = {
		TITLE: 'title',
		DESCRIPTION: 'description',
		DATE_TIME: 'dateTime',
		LOCATION: 'location',
		ATTENDEES: 'attendees',
		DECISION_BUTTONS: 'decision-buttons',
		QUESTIONED_ATTENDEES: 'questioned-attendees',
		DECLINED_ATTENDEES: 'declined-attendees',
		REMINDERS: 'reminders',
		RECURRENCE_RULE: 'recurrence-rule',
		DOWNLOAD_ICS: 'download-ics',
		FILES: 'files',
	};

	const EventMeetingStatus = {
		HOST: 'H',
		QUESTIONED: 'Q',
		ATTENDED: 'Y',
		DECLINED: 'N',
	};

	const EventAccessibility = {
		BUSY: 'busy',
		FREE: 'free',
		ABSENT: 'absent',
	};

	const EventImportance = {
		NORMAL: 'normal',
		HIGH: 'high',
	};

	const RecursionMode = {
		THIS: 'this',
		NEXT: 'next',
		ALL: 'all',
	};

	const PullCommand = {
		EDIT_EVENT: 'edit_event',
		DELETE_EVENT: 'delete_event',
		SET_MEETING_STATUS: 'set_meeting_status',
		EDIT_SECTION: 'edit_section',
		DELETE_SECTION: 'delete_section',
		REFRESH_SYNC_STATUS: 'refresh_sync_status',
		DELETE_SYNC_CONNECTION: 'delete_sync_connection',
		HANDLE_SUCCESSFUL_CONNECTION: 'handle_successful_connection',
		UPDATE_USER_COUNTERS: 'update_user_counters',
		UPDATE_GROUP_COUNTERS: 'update_group_counters',
	};

	const FeatureId = {
		CALENDAR_SHARING: 'calendar_sharing',
		CALENDAR_LOCATION: 'calendar_location',
		CALENDAR_EVENTS_WITH_PLANNER: 'calendar_events_with_planner',
		CRM_EVENT_SHARING: 'crm_event_sharing',
	};

	const Counters = {
		TOTAL: 'calendar',
		INVITES: 'calendar_invites',
		GROUP_INVITES: 'calendar_group_invites',
		SYNC_ERRORS: 'calendar_sync_errors',
	};

	const BooleanParams = {
		YES: 'Y',
		NO: 'N',
	};

	const AnalyticsSubSection = {
		PERSONAL: 'calendar_personal',
		COLLAB: 'calendar_collab',
		CHAT: 'chat_textarea',
	};

	const SectionPermissionActions = {
		ACCESS: 'access',
		ADD: 'add',
		EDIT: 'edit',
		EDIT_SECTION: 'edit_section',
		VIEW_FULL: 'view_full',
		VIEW_TIME: 'view_time',
		VIEW_TITLE: 'view_title',
	};

	const SectionExternalTypes = {
		LOCAL: 'local',
		GOOGLE: 'google',
		GOOGLE_READONLY: 'google_readonly',
		GOOGLE_WRITE_READ: 'google_write_read',
		GOOGLE_FREEBUSY: 'google_freebusy',
		ICLOUD: 'icloud',
		OFFICE365: 'office365',
		ARCHIVE: 'archive',
	};

	const EventPermissionActions = {
		EDIT: 'edit',
		EDIT_ATTENDEES: 'editAttendees',
		EDIT_LOCATION: 'editLocation',
		VIEW_FULL: 'view_full',
		VIEW_TIME: 'view_time',
		VIEW_TITLE: 'view_title',
		VIEW_COMMENTS: 'view_comments',
		DELETE: 'delete',
	};

	module.exports = {
		CalendarType,
		EventTypes,
		EventFormFields,
		EventMeetingStatus,
		EventAccessibility,
		EventImportance,
		RecursionMode,
		PullCommand,
		FeatureId,
		Counters,
		BooleanParams,
		AnalyticsSubSection,
		SectionPermissionActions,
		SectionExternalTypes,
		EventPermissionActions,
	};
});
