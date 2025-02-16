import { Event } from 'main.core';
import { Core } from 'booking.core';
import { Model } from 'booking.const';

export class MousePosition
{
	bindMouseMove(): void
	{
		if (this.onMouseMove)
		{
			return;
		}

		this.onMouseMove = this.#update.bind(this);

		Event.bind(document, 'mousemove', this.onMouseMove);
	}

	#update(event: MouseEvent): void
	{
		void Core.getStore().dispatch(`${Model.Interface}/setMousePosition`, {
			top: event.clientY + window.scrollY,
			left: event.clientX + window.scrollX,
		});
	}
}

export const mousePosition = new MousePosition();
