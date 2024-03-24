import { EventEmitter } from 'main.core.events';
import type { BaseEvent } from 'main.core.events';

import { DateFilterField, DashboardDateFilterField } from './entities/index';
import { KeyInfoField } from './entities/key-info-field';

export class FieldFactory
{
	constructor(entityEditorControlFactory: string = 'BX.UI.EntityEditorControlFactory')
	{
		EventEmitter.subscribe(entityEditorControlFactory + ':onInitialize', (event: BaseEvent) => {
			const [, eventArgs] = event.getCompatData();
			eventArgs.methods['dashboardSettings'] = this.factory.bind(this);
		});
	}

	factory(type, controlId, settings): ?BX.UI.EntityEditorField
	{
		switch (type)
		{
			case 'timePeriod':
				return DateFilterField.create(controlId, settings);
			case 'dashboardTimePeriod':
				return DashboardDateFilterField.create(controlId, settings);
			case 'keyInfo':
				return KeyInfoField.create(controlId, settings);
			default:
				return null;
		}
	}
}
