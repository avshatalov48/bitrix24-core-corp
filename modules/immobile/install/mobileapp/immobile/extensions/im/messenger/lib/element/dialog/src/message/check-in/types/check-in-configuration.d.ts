declare const CheckInType: {
	readonly withLocation: 'withLocation';
	readonly withoutLocation: 'withoutLocation';
}

type CheckInButton = {
	text: string;
	callback: ({ dialogId, chatTitle }) => void;
}

type CheckInMetaDataValue = {
	button: CheckInButton;
}

export type CheckInMetaData = Record<keyof typeof CheckInType, CheckInMetaDataValue>

export type CheckInMessageData = {
	imageUrl: string,
	chipsText: null | string,
	addressText: null | string,
	buttonText: string,
}
