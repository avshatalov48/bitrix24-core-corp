export type ResourceTypeDto = {
	id: number | null,
	moduleId: string,
	name: string,
	code: string | null,
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
