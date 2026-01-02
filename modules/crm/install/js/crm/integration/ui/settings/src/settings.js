import { Extension } from 'main.core';

const EXTENSION_NAME = 'crm.integration.ui.settings';

export const Setting = {
	UseAirDesign: 'useAirDesign',
};

export class Settings
{
	static get(setting: $Values<typeof Setting>): any
	{
		return Extension.getSettings(EXTENSION_NAME).get(setting);
	}
}
