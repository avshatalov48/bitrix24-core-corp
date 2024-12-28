type EventReduxModel = {
	id: number,
	parentId: number,
	dateFromTs: number,
	dateToTs: number,
	dateFromFormatted: string,
	isFullDay: boolean,
	timezone: string,
	sectionId: number,
	name: string,
	description: string,
	location: string,
	color: string,
	eventType: string,
	meetingStatus: MeetingStatus,
	meetingHost: number,
	recurrenceRule: RecurrenceRule | null,
	recurrenceRuleDescription: string,
	recurrenceId: number | null,
	excludedDates: Array<string>,
	eventLength: number,
	calType: string,
	attendees: Array<Attendee>,
	reminders: Array<number>,
	permissions: Permissions,
	files: Array<Attachment>,
	importance: string,
	accessibility: string,
	privateEvent: number,
	collabId: number,
};

type Attendee = {
	id: number,
	meetingStatus: MeetingStatus,
};

type MeetingStatus = 'H' | 'Q' | 'Y' | 'N';

type RecurrenceRule = {
	UNTIL_TS: number,
	COUNT: number,
	FREQ: string,
	BYDAY: Array<string>,
	INTERVAL: number,
};

type Permissions = {
	delete: boolean,
	edit: boolean,
	editAttendees: boolean,
	editLocation: boolean,
	view_comments: boolean,
	view_full: boolean,
	view_time: boolean,
	view_title: boolean,
};

type Attachment = {
	id: number,
	objectId: number,
	name: string,
	type: string,
	url: string,
	previewUrl: string,
	width: number,
	height: number,
};
