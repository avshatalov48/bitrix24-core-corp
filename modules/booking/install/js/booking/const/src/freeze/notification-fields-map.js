const NotificationOn = Object.freeze({
	info: 'isInfoNotificationOn',
	confirmation: 'isConfirmationNotificationOn',
	reminder: 'isReminderNotificationOn',
	delayed: 'isDelayedNotificationOn',
	feedback: 'isFeedbackNotificationOn',
});

const TemplateType = Object.freeze({
	info: 'templateTypeInfo',
	confirmation: 'templateTypeConfirmation',
	reminder: 'templateTypeReminder',
	delayed: 'templateTypeDelayed',
	feedback: 'templateTypeFeedback',
});

const Settings = Object.freeze({
	info: ['infoNotificationDelay'],
	confirmation: ['confirmationNotificationDelay', 'confirmationNotificationRepetitions', 'confirmationNotificationRepetitionsInterval', 'confirmationCounterDelay'],
	reminder: ['reminderNotificationDelay'],
	delayed: ['delayedNotificationDelay', 'delayedCounterDelay'],
	feedback: [],
});

const Ordinal = Object.freeze({
	info: 1,
	confirmation: 2,
	reminder: 3,
	delayed: 4,
	feedback: 5,
});

export const NotificationFieldsMap = Object.freeze({
	NotificationOn,
	TemplateType,
	Settings,
	Ordinal,
});
