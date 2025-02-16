export type ResourceTypesState = {
	collection: { [id: number]: ResourceTypeModel },
};

export type ResourceTypeModel = {
	id: number | null,
	moduleId: string | null,
	name: string,
	code: string,
	isConfirmationNotificationOn: boolean | null,
	isFeedbackNotificationOn: boolean | null,
	isInfoNotificationOn: boolean | null,
	isDelayedNotificationOn: boolean | null,
	isReminderNotificationOn: boolean | null,
	templateTypeConfirmation: string | null,
	templateTypeFeedback: string | null,
	templateTypeInfo: string | null,
	templateTypeDelayed: string | null,
	templateTypeReminder: string | null,
};
