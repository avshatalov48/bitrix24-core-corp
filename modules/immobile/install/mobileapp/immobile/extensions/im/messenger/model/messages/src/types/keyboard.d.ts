export let KeyboardButtonType: {
	readonly button: "BUTTON";
	readonly newLine: "NEWLINE"
};

export let KeyboardButtonContext: {
	readonly all: "ALL";
	readonly desktop: "DESKTOP";
	readonly mobile: "MOBILE"
};

export let KeyboardButtonDisplay: {
	readonly line: "LINE";
	readonly block: "BLOCK"
};

export let KeyboardButtonAction: {
	readonly call: "CALL";
	readonly dialog: "DIALOG";
	readonly copy: "COPY";
	readonly send: "SEND";
	readonly put: "PUT"
};

type KeyboardButtonTypeValues = typeof KeyboardButtonType[keyof typeof KeyboardButtonType];
type KeyboardButtonContextValues = typeof KeyboardButtonContext[keyof typeof KeyboardButtonContext];
type KeyboardButtonDisplayValues = typeof KeyboardButtonDisplay[keyof typeof KeyboardButtonDisplay];
type KeyboardButtonActionValues = typeof KeyboardButtonAction[keyof typeof KeyboardButtonAction];

export type RawKeyboardButtonConfig = {
	TEXT: string,
	TYPE?: KeyboardButtonTypeValues,
	CONTEXT?: KeyboardButtonContextValues,
	LINK?: string,
	COMMAND?: string,
	COMMAND_PARAMS: string,
	DISPLAY: KeyboardButtonDisplayValues,
	WIDTH: number,
	BG_COLOR: string,
	BG_COLOR_TOKEN?: string,
	TEXT_COLOR: string,
	BLOCK: 'Y' | 'N',
	DISABLED: 'Y' | 'N',
	VOTE: 'Y' | 'N',
	WAIT: 'Y' | 'N',
	APP_ID: string,
	APP_PARAMS: string,
	BOT_ID: number,
	ACTION: KeyboardButtonActionValues,
	ACTION_VALUE: string,
};

export type KeyboardButtonConfig = {
	text: string,
	type?: KeyboardButtonTypeValues,
	context?: KeyboardButtonContextValues,
	link?: string,
	command?: string,
	commandParams?: string,
	display: KeyboardButtonDisplayValues,
	width: number,
	bgColor?: string,
	bgColorToken:string,
	textColor?: string,
	block?: boolean,
	disabled?: boolean,
	vote?: boolean,
	wait?: boolean,
	appId: string,
	appParams: string,
	botId: number,
	action: KeyboardButtonActionValues,
	actionValue: string, // PUT - text, SEND - text, COPY - text, CALL - number, DIALOG - dialogId
};
