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

export const NotificationFieldsMap = Object.freeze({
	NotificationOn,
	TemplateType,
});
