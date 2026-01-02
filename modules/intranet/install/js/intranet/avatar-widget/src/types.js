import { Popup } from 'main.popup';

export type GeneralOptions = {
	buttonWrapper: HTMLElement,
	loader: Popup,
	data: Object,
}

export type PopupOptions = {
	target: HTMLElement,
	loader: Popup,
	content: Object,
}

export type ConfigOptions = {
	[key: string]: any,
}

export type DesktopContentOptions = {
	installInfo: {
		linux: boolean,
		mac: boolean,
		windows: boolean,
	},
	downloadLinks: {
		linuxDeb: string,
		linuxRpm: string,
		macos: string,
		windows: string,
	},
	linuxMenuTitles: {
		deb: string,
		rpm: string,
	},
	title: {
		linux: string,
		mac: string,
		windows: string,
	},
	linkName: string,
};

export type ExtensionContentOptions = {
	items: Array<Object>,
	text: string,
};

export type LogoutContentOptions = {
	title: string,
	path: string,
};

export type MainContentOptions = {
	id: number,
	fullName: string,
	userPhotoSrc: string,
	role: string,
	status: string,
	isTimemanAvailable: boolean,
	url: string,
	menuItems: Array<Object>,
};

export type OtpContentOptions = {
	title: string,
	settingsButtonTitle: string,
	connectButtonTitle: string,
	connectStatus: string,
	settingsPath: string,
	isActive: boolean,
};

export type QrAuthContentOptions = {
	title: string,
	buttonTitle: string,
	popup: {
		title: string,
		description: string,
	},
	isSmall: boolean,
};

export type SalaryVacationContentOptions = {
	title: string,
	disabledHint: string,
	disabled: boolean,
};

export type SettingsContentOptions = {
	path: string,
	title: string,
};

export type SignDocumentsContentOptions = {
	title: string,
	description: string,
	counter: number,
	counterEventName: string,
	isLocked: boolean,
};

export type PerfReviewContentOptions = {
	title: string,
	path: string,
};

export type BaseContentOptions =
	DesktopContentOptions
	| SignDocumentsContentOptions
	| SalaryVacationContentOptions
	| LogoutContentOptions
	| ExtensionContentOptions
	| MainContentOptions
	| OtpContentOptions
	| QrAuthContentOptions
	| SettingsContentOptions
;
