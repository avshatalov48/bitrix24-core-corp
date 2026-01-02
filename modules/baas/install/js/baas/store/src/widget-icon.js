import { Dom, Event } from 'main.core';
import { Icon, IconOptions } from 'ui.icon-set.api.core';

export class WidgetIcon extends Icon
{
	#events: Object = {};

	constructor(params: IconOptions = {})
	{
		super(params);

		if (params.events)
		{
			this.#events = params.events;
		}
	}

	render(): Node
	{
		this.iconElement = super.render();
		Dom.style(this.iconElement, 'opacity', '60%');
		Object.keys(this.#events).forEach((eventName) => {
			Event.bind(this.iconElement, eventName, this.#events[eventName]);
		});

		return this.iconElement;
	}
}
