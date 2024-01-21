import {EventEmitter} from "main.core.events";
import type {BaseEvent} from "main.core.events";
import {SettingsDateFilterField} from "./field";

export class SettingsDateFilterFieldFactory
{
	constructor(entityEditorControlFactory = 'BX.UI.EntityEditorControlFactory')
	{
		EventEmitter.subscribe(entityEditorControlFactory + ':onInitialize', (event: BaseEvent) => {
			const [, eventArgs] = event.getCompatData();
			eventArgs.methods['dashboardSettings'] = this.factory.bind(this);
		});
	}

	factory(type, controlId, settings)
	{
		if (type === 'timePeriod')
		{
			return SettingsDateFilterField.create(controlId, settings);
		}

		return null;
	}
}
