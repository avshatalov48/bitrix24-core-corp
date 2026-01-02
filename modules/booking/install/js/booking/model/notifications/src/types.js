export type NotificationsState = {
	notifications: { [type: string]: NotificationsModel },
	senders: { [type: string]: NotificationsSenderModel },
};

export type NotificationsModel = {
	type: string,
	templates: NotificationsTemplateModel[],
	managerNotification: string,
	isExpanded: boolean,
	settings: {
		counter: {
			delayValues: SettingsValue[],
		},
		notification: {
			delayValues: SettingsValue[],
			repeatValues: SettingsValue[],
			repeatIntervalValues: SettingsValue[],
		},
	},
};

export type SettingsValue = {
	name: string,
	value: number,
};

export type NotificationsTemplateModel = {
	type: string,
	text: string,
	textSms: string,
};

export type NotificationsSenderModel = {
	moduleId: string,
	code: string,
	canUse: boolean,
};
