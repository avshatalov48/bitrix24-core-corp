export type NotificationsState = {
	notifications: { [type: string]: NotificationsModel },
	senders: { [type: string]: NotificationsSenderModel },
};

export type NotificationsModel = {
	type: string,
	templates: NotificationsTemplateModel[],
};

export type NotificationTemplateType = string;

export type NotificationsTemplateModel = {
	type: NotificationTemplateType,
	text: string,
	textSms: string,
};

export type NotificationsSenderModel = {
	moduleId: string,
	code: string,
	canUse: boolean,
};
