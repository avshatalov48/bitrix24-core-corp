import type { BaseEvent } from 'main.core.events';
import { EventEmitter } from 'main.core.events';

import { SettingController, IconController } from './entities/index';

export class ControllerFactory
{
	constructor(eventName: string)
	{
		EventEmitter.subscribe(`${eventName}:onInitialize`, (event: BaseEvent) => {
			const [, eventArgs] = event.getCompatData();
			eventArgs.methods.dashboardSettings = this.factory.bind(this);
		});
	}

	factory(type: string, controlId: string, settings: Object): ?BX.UI.EntityEditorController
	{
		switch (type)
		{
			case 'settingComponentController':
				return new SettingController(controlId, settings);
			case 'iconController':
				return new IconController(controlId, settings);
			default:
				return null;
		}
	}
}
