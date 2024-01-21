import type { BaseEvent } from 'main.core.events';
import { EventEmitter } from 'main.core.events';
import { SettingController } from './setting-controller';

export class Factory
{
	constructor(eventName)
	{
		EventEmitter.subscribe(eventName + ':onInitialize', (event: BaseEvent) => {
			const [, eventArgs] = event.getCompatData();
			eventArgs.methods['dashboardSettings'] = this.factory.bind(this);
		});
	}

	factory(type, controlId, settings)
	{
		if (type === 'settingComponentController')
		{
			return new SettingController(controlId, settings);
		}

		return null;
	}
}