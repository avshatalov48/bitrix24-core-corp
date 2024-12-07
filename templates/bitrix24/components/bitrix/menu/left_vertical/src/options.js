import {Type} from 'main.core';

export default class Options {
	static version = '2021.10';
	static eventNameSpace = 'BX.Intranet.LeftMenu:';
	static eventName(name) {
		return ['BX.Intranet.LeftMenu:', ...(Type.isStringFilled(name) ? [name] : name)].join(':')
	}

	static isExtranet = false;
	static isAdmin = false;
	static isCustomPresetRestricted = false;
	static settingsPath = null;
	static isMainPageEnabled = false;

	static availablePresetTools = null;
}