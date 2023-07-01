type TelegramSettings = {
	lineId: number,
	lineName: string,
	botName: string,
	userIds: Array<number>,
	url: string,
	qr: string,
	users: Array<TelegramUserData>,
	canEditLine: boolean,
	canEditConnector: boolean,
};

type TelegramUserData = {
	id: number,
	name: string,
	icon: string,
	workPosition: string,
};

type TelegramOpenLine = {
	lineId: number,
	canEditLine: boolean,
	canEditConnector: boolean,
};

type TelegramPermissions = {
	canEditLine: boolean,
	canEditConnector: boolean,
};

type TelegramIcon = 'toEdit' | 'toSend';