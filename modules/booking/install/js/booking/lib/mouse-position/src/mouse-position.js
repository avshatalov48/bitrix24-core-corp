import { Event } from 'main.core';
import { Core } from 'booking.core';
import { Model } from 'booking.const';

class MousePosition
{
	#isMousePressed: boolean = false;

	init(): void
	{
		this.#bindMouseMove();
		this.#bindMousePressed();
	}

	destroy(): void
	{
		Event.unbind(window, 'mousemove', this.onMouseMove);
		Event.unbind(window, 'mousedown', this.onMouseDown);
		Event.unbind(window, 'mouseup', this.onMouseUp);
	}

	isMousePressed(): boolean
	{
		return this.#isMousePressed;
	}

	#bindMousePressed(): void
	{
		if (this.onMouseDown)
		{
			return;
		}

		this.onMouseDown = this.#onMouseDown.bind(this);
		this.onMouseUp = this.#onMouseUp.bind(this);

		Event.bind(window, 'mousedown', this.onMouseDown);
		Event.bind(window, 'mouseup', this.onMouseUp);
	}

	#bindMouseMove(): void
	{
		if (this.onMouseMove)
		{
			return;
		}

		this.onMouseMove = this.#update.bind(this);

		Event.bind(window, 'mousemove', this.onMouseMove);
	}

	#update(event: MouseEvent): void
	{
		void Core.getStore().dispatch(`${Model.Interface}/setMousePosition`, {
			top: event.clientY + window.scrollY,
			left: event.clientX + window.scrollX,
		});
	}

	#onMouseDown(): void
	{
		this.#isMousePressed = true;
	}

	#onMouseUp(): void
	{
		this.#isMousePressed = false;
	}
}

export const mousePosition = new MousePosition();
