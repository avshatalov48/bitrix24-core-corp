import { CounterPanel } from 'ui.counterpanel';
import { Type } from 'main.core';
import { EventEmitter } from 'main.core.events';

export class InvitationCounter
{
	#counterPanel: CounterPanel;
	#presetRelation: Object;

	constructor(options)
	{
		this.#counterPanel = new CounterPanel({
			target: options.target,
			items: this.#prepareItems(options.items),
			title: options.title,
			multiselect: options.multiselect === true,
		});

		this.#presetRelation = Type.isObject(options.presetRelation) ? options.presetRelation : null

		BX.addCustomEvent("onPullEvent-main", this.#onReceiveCounterValue.bind(this));
		this.#subscribeFilterEvents(options.filterEvents)
	}

	#onReceiveCounterValue(command, params): void
	{
		if (command === "user_counter" && params[BX.message("SITE_ID")])
		{
			const counters = BX.clone(params[BX.message('SITE_ID')]);

			this.#counterPanel.getItems().forEach((counterItem) => {

				const counterValue = counters[counterItem.id];
				if (!Type.isNumber(counterValue))
				{
					return;
				}
				counterItem.updateValue(counterValue);
				counterItem.updateColor(counterValue > 0 ? 'DANGER' : 'THEME');
			});
		}
	}

	#subscribeFilterEvents(events: Object): void
	{
		if (Type.isObject(events))
		{
			for (const eventType in events)
			{
				const handler = events[eventType];
				if (Type.isFunction(handler))
				{
					EventEmitter.subscribe('BX.Main.Filter:'+eventType, handler);
				}
			}
		}
	}

	getCounterPanel(): CounterPanel
	{
		return this.#counterPanel;
	}

	#prepareItems(items: Array): Array
	{
		return items.map((value, index) => {
			if (Type.isNil(value.id))
			{
				throw new Error('Field "id" is required');
			}

			value.value = Type.isNumber(value.value) ? value.value : 0;
			value.color =  value.value > 0 ? 'DANGER' : 'THEME';

			return value;
		});
	}

	show()
	{
		this.#counterPanel.init();
	}
}