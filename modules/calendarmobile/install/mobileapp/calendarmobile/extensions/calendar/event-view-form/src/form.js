/**
 * @module calendar/event-view-form/form
 */
jn.define('calendar/event-view-form/form', (require, exports, module) => {
	const { Form, CompactMode } = require('layout/ui/form');
	const { Color, Component, Indent } = require('tokens');
	const { Type } = require('type');
	const { Loc } = require('loc');

	const { usersSelector } = require('statemanager/redux/slices/users');

	const { SectionManager } = require('calendar/data-managers/section-manager');
	const { CollabManager } = require('calendar/data-managers/collab-manager');
	const { LocationManager } = require('calendar/data-managers/location-manager');
	const { EventFormFields, EventMeetingStatus } = require('calendar/enums');

	const { DataLoader } = require('calendar/event-view-form/data-loader');

	const { NameField } = require('calendar/event-view-form/fields/name');
	const { LocationField } = require('calendar/event-view-form/fields/location');
	const { DateTimeField } = require('calendar/event-view-form/fields/date-time');
	const { DecisionButtonsField } = require('calendar/event-view-form/fields/decision-buttons');
	const { RecurrenceRuleField } = require('calendar/event-view-form/fields/recurrence-rule');
	const { RemindersField } = require('calendar/event-view-form/fields/reminders');
	const { IcsField } = require('calendar/event-view-form/fields/ics');
	const { UserWithChatButtonsField } = require('calendar/event-view-form/fields/user-with-chat-buttons');

	const { TextAreaField: Description } = require('layout/ui/fields/textarea/theme/air-description');
	const { UserField } = require('layout/ui/fields/user/theme/air');
	const { FileWithBackgroundAttachField } = require('layout/ui/fields/file-with-background-attach/theme/air');

	const { connect } = require('statemanager/redux/connect');
	const { selectByIdAndDate } = require('calendar/statemanager/redux/slices/events');

	const NullCompactCreateFactory = {
		create: () => null,
	};

	const EventViewForm = (props) => {
		const {
			layout,
			parentLayout,
			eventId,
			event,
			acceptedAttendees,
			declinedAttendees,
			questionedAttendees,
		} = props;

		return new Form({
			testId: 'calendar-view-form',
			parentWidget: parentLayout,
			useState: false,
			style: EventViewFormStyles,
			compactMode: CompactMode.NONE,
			compactFieldFactory: NullCompactCreateFactory,
			hideEmptyReadonlyFields: true,
			primaryFields: [
				{
					factory: NameField,
					props: {
						id: EventFormFields.TITLE,
						name: event.name,
						dateFromTs: event.dateFromTs,
						color: event.color,
						sectionName: event.sectionName,
						collabId: event.collabId,
						permissions: event.permissions,
						readOnly: true,
						placeholder: 'name',
						required: true,
					},
				},
				Type.isStringFilled(event.description) && {
					factory: Description,
					props: {
						id: EventFormFields.DESCRIPTION,
						testId: 'calendar-event-view-form-description',
						value: event.description,
						readOnly: true,
						required: true,
						placeholder: 'description',
						useBBCodeEditor: true,
						config: {
							fileField: {
								value: event.files,
							},
							allowFiles: false,
							autoFocus: false,
						},
					},
				},
				{
					factory: DateTimeField,
					props: {
						id: EventFormFields.DATE_TIME,
						dateFromTs: event.dateFromTs,
						dateToTs: event.dateToTs,
						isFullDay: event.isFullDay,
						readOnly: true,
						placeholder: 'dateFromTs',
						required: true,
					},
				},
				Type.isStringFilled(event.textLocation) && {
					factory: LocationField,
					props: {
						id: EventFormFields.LOCATION,
						value: event.textLocation,
						readOnly: true,
						required: false,
						placeholder: 'location',
					},
				},
				{
					factory: UserWithChatButtonsField,
					props: {
						id: EventFormFields.ATTENDEES,
						testId: 'calendar-event-view-form-attendees',
						value: getAttendees(event.attendees, EventMeetingStatus.ATTENDED, event.meetingHost),
						permissions: event.permissions,
						parentId: event.parentEventId,
						chatId: event.chatId,
						attendees: event.attendees,
						collabId: event.collabId,
						layout,
						readOnly: true,
						multiple: true,
						title: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_ATTENDEES'),
						useState: false,
						config: {
							canOpenUserList: true,
							items: acceptedAttendees.filter(Boolean),
							provider: {
								context: 'CALENDAR_EVENT_VIEW_SELECTOR_attendees',
							},
							useLettersForEmptyAvatar: true,
							selectorTitle: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_ATTENDEES'),
							textMultiple: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_ATTENDEES_COUNT'),
						},
					},
				},
				hasToShowDecisionButtons(event.meetingStatus, event.meetingHost) && {
					factory: DecisionButtonsField,
					props: {
						id: EventFormFields.DECISION_BUTTONS,
						readOnly: true,
						layout,
						eventId,
						meetingStatus: event.meetingStatus,
						parentEventId: event.parentEventId,
						dateFromTs: event.dateFromTs,
						isRecurrent: Type.isStringFilled(event.recurrenceRuleDescription),
					},
				},
			].filter(Boolean),
			secondaryFields: [
				{
					factory: RecurrenceRuleField,
					props: {
						id: EventFormFields.RECURRENCE_RULE,
						value: event.recurrenceRuleDescription,
						readOnly: true,
					},
				},
				{
					factory: FileWithBackgroundAttachField,
					props: {
						id: EventFormFields.FILES,
						testId: 'calendar-event-view-form-files',
						value: event.files ?? [],
						readOnly: true,
						required: false,
						title: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_FILES'),
						showTitle: true,
						multiple: true,
						showFilesName: true,
						config: {
							textMultiple: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_FILES_MULTI'),
						},
					},
				},
				{
					factory: UserField,
					props: {
						id: EventFormFields.QUESTIONED_ATTENDEES,
						testId: 'calendar-event-view-form-questioned-attendees',
						value: getAttendees(event.attendees, EventMeetingStatus.QUESTIONED),
						readOnly: true,
						multiple: true,
						required: false,
						showTitle: true,
						title: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_QUESTIONED'),
						useState: false,
						config: {
							canOpenUserList: true,
							items: questionedAttendees.filter(Boolean),
							provider: {
								context: 'CALENDAR_EVENT_VIEW_SELECTOR_questioned',
							},
							useLettersForEmptyAvatar: true,
							selectorTitle: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_QUESTIONED'),
							textMultiple: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_QUESTIONED_COUNT'),
						},
					},
				},
				{
					factory: UserField,
					props: {
						id: EventFormFields.DECLINED_ATTENDEES,
						testId: 'calendar-event-view-form-declined-attendees',
						value: getAttendees(event.attendees, EventMeetingStatus.DECLINED),
						readOnly: true,
						multiple: true,
						required: false,
						showTitle: true,
						title: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_DECLINED'),
						useState: false,
						config: {
							canOpenUserList: true,
							items: declinedAttendees.filter(Boolean),
							provider: {
								context: 'CALENDAR_EVENT_VIEW_SELECTOR_declined',
							},
							useLettersForEmptyAvatar: true,
							selectorTitle: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_DECLINED'),
							textMultiple: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_DECLINED_COUNT'),
						},
					},
				},
				{
					factory: RemindersField,
					props: {
						id: EventFormFields.REMINDERS,
						value: event.reminders,
						readOnly: true,
					},
				},
				env.isCollaber && {
					factory: IcsField,
					props: {
						id: EventFormFields.DOWNLOAD_ICS,
						value: event.permissions,
						eventId,
						readOnly: true,
					},
				},
			].filter(Boolean),
		});
	};

	const prepareDescription = (description) => {
		let result = description;

		if (Type.isStringFilled(description))
		{
			result = description
				.replaceAll('&#39;', '\'')
				.replaceAll('&quot;', '"')
				.replaceAll('&lt;', '<')
				.replaceAll('&gt;', '>')
				.replaceAll('&amp;', '&')
				.replaceAll('&nbsp;', '')
			;
		}

		return result;
	};

	const prepareColor = (color, sectionId, collabId = 0) => {
		if (collabId > 0)
		{
			return color || Color.collabAccentPrimary.toHex();
		}

		return color || SectionManager.getSectionColor(sectionId) || Color.accentSoftGreen1.toHex();
	};

	const prepareSectionName = (sectionId, collabId = 0) => {
		if (collabId > 0)
		{
			return CollabManager.getCollabName(collabId);
		}

		return SectionManager.getSectionName(sectionId);
	};

	const getAttendees = (attendees, status, meetingHost = 0) => {
		// eslint-disable-next-line init-declarations
		let result = [];
		if (status === EventMeetingStatus.ATTENDED)
		{
			const attendeesStatus = new Set([EventMeetingStatus.HOST, EventMeetingStatus.ATTENDED]);
			result = attendees.filter((attendee) => attendeesStatus.has(attendee.status));

			if (meetingHost !== 0 && result.length === 0)
			{
				result.push({ id: meetingHost, status: EventMeetingStatus.HOST });
			}
		}
		else
		{
			result = attendees.filter((attendee) => attendee.status === status);
		}

		return result.map((attendee) => attendee.id);
	};

	const getAttendeesInfo = (state, attendees, status, meetingHost = 0) => {
		return getAttendees(attendees, status, meetingHost).map((id) => selectMappedUserById(state, id));
	};

	const selectMappedUserById = (state, id) => {
		const user = usersSelector.selectById(state, id);

		return user ? {
			id: user.id,
			title: user.fullName,
			imageUrl: user.avatarSize100,
			customData: {
				position: user.workPosition,
			},
		} : undefined;
	};

	const hasToShowDecisionButtons = (meetingStatus, meetingHost) => {
		return meetingStatus !== EventMeetingStatus.HOST && meetingHost !== Number(env.userId);
	};

	const EventViewFormStyles = {
		primaryContainer: {
			backgroundColor: Color.bgContentPrimary.toHex(),
			paddingTop: Indent.L.toNumber(),
		},
		primaryField: (field) => ({
			paddingHorizontal: Component.areaPaddingLr.toNumber(),
			marginBottom: Indent.M.getValue(),
			marginTop: field.getId() === EventFormFields.ATTENDEES ? Indent.S.toNumber() : 0,
		}),
		secondaryContainer: {
			marginTop: Indent.XL2.toNumber(),
			paddingHorizontal: Component.areaPaddingLr.toNumber(),
		},
		secondaryField: {
			marginBottom: Component.cardListGap.toNumber(),
		},
	};

	const mapStateToProps = (state, { moreButton, eventId, dateFromTs }) => {
		const event = selectByIdAndDate(state, { eventId, dateFromTs });
		if (!event)
		{
			return {};
		}

		moreButton.setEventParams(event);

		if (event?.hasUploadedFiles)
		{
			const { parentId } = event;

			void DataLoader.loadFiles({
				eventId,
				parentId,
			});
		}

		return {
			event: {
				id: eventId,
				parentEventId: event.parentId,
				name: event.name,
				sectionId: event.sectionId,
				sectionName: prepareSectionName(event.sectionId, event.collabId),
				description: prepareDescription(event.description),
				dateFromTs: event.dateFromTs,
				dateToTs: event.dateToTs,
				isFullDay: event.isFullDay,
				color: prepareColor(event.color, event.sectionId, event.collabId),
				textLocation: LocationManager.getTextLocation(event.location),
				meetingHost: event.meetingHost,
				attendees: event.attendees,
				meetingStatus: event.meetingStatus,
				reminders: event.reminders,
				recurrenceRuleDescription: event.recurrenceRuleDescription,
				recurrenceId: event.recurrenceId,
				files: event.files,
				permissions: event.permissions,
				collabId: event.collabId,
				chatId: event.chatId,
			},
			acceptedAttendees: getAttendeesInfo(state, event.attendees, EventMeetingStatus.ATTENDED, event.meetingHost),
			declinedAttendees: getAttendeesInfo(state, event.attendees, EventMeetingStatus.DECLINED),
			questionedAttendees: getAttendeesInfo(state, event.attendees, EventMeetingStatus.QUESTIONED),
		};
	};

	module.exports = {
		EventViewForm: connect(mapStateToProps)(EventViewForm),
	};
});
