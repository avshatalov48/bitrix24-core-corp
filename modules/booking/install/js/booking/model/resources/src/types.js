export type ResourcesState = {
	collection: { [resourceId: string]: ResourceModel },
};

export type ResourceModel = {
	id: number | null,
	typeId: number,
	name: string,
	description: string | null,
	slotRanges: SlotRange[],
	counter: number | null,
	isMain: false,
	isConfirmationNotificationOn: boolean,
	isFeedbackNotificationOn: boolean,
	isInfoNotificationOn: boolean,
	isDelayedNotificationOn: boolean,
	isReminderNotificationOn: boolean,
	templateTypeConfirmation: string,
	templateTypeFeedback: string,
	templateTypeInfo: string,
	templateTypeDelayed: string,
	templateTypeReminder: string,
	createdBy: number,
	createdAt: number,
	updatedAt: number | null,
};

export type SlotRange = {
	id: number | string | null,
	from: number,
	to: number,
	weekDays: string[],
	slotSize: number,
	timezone: string,
};
