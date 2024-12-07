import { PopupComponentsMaker } from 'ui.popupcomponentsmaker';

export type SettingsWidgetHoldingOptions = {
	isHolding: boolean;
	affiliate: ?Object;
	canBeHolding: boolean;
	canBeAffiliate: boolean;
}

export type MainPageConfiguration = {
	isAvailable?: boolean,
	isNew?: boolean,
	settingsPath?: string,
}

export type SettingsWidgetOptions = {
	popup: PopupComponentsMaker;
	button?: HTMLElement,
	theme?: Object;
	otp: string;
	affiliate?: Object;
	marketUrl: string;
	isBitrix24: boolean;
	isFreeLicense?: boolean;
	isAdmin?: boolean;
	requisite?: Object;
	settingsPath?: string;
	holding?: SettingsWidgetHoldingOptions;
	isRenameable?: boolean;
	mainPage?: MainPageConfiguration;
}
