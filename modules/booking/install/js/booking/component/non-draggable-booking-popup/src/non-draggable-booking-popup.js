import { Event, Loc, Tag } from 'main.core';
import { Popup } from 'main.popup';

import './non-draggable-booking-popup.css';

let popup = null;

export class NonDraggableBookingPopup
{
	popupId: string;
	#bindElement: HTMLElement;

	constructor(options: NonDraggableBookingPopupOptions)
	{
		this.popupId = options.id;
		this.#bindElement = options.bindElement;
	}

	show(): void
	{
		if (!popup)
		{
			this.#initPopup();
		}

		popup.show();

		this.setBindElement(this.#bindElement);
		this.adjustPosition();
		Event.bind(document, 'scroll', this.adjustPosition, true);
	}

	destroy(popupId: ?string): void
	{
		if (popupId && popupId !== popup?.getId())
		{
			return;
		}

		popup?.destroy();
		popup = null;

		Event.unbind(document, 'scroll', this.adjustPosition, true);
	}

	hasPopup(): boolean
	{
		return popup !== null;
	}

	setBindElement(bindElement): void
	{
		popup?.setBindElement(bindElement);
	}

	adjustPosition(): void
	{
		popup?.adjustPosition();
	}

	#initPopup(): void
	{
		if (popup)
		{
			return;
		}

		popup = new Popup({
			id: this.popupId,
			bindElement: this.#bindElement,
			content: this.#getPopupContent(),
			offsetLeft: this.#bindElement.offsetWidth / 2,
			maxWidth: 250,
			minWidth: 200,
			minHeight: 42,
			background: '#2878ca',
			angle: {
				offset: 20,
				position: 'top',
			},
			angleBorderRadius: '4px 0',
			onPopupClose: () => {
				popup?.destroy();
			},
		});
	}

	#getPopupContent(): HTMLElement
	{
		return Tag.render`
			<div class="booking--booking--non-draggable-booking-popup-content">
				<div class="booking--booking--non-draggable-booking-popup-content__text">
					${this.message}
				</div>
			</div>
		`;
	}

	get message(): string
	{
		return Loc.getMessage('BOOKING_NON_DRAGGING_BOOKING_FROM_DELETED_RESOURCE');
	}
}

type NonDraggableBookingPopupOptions = {
	id: string,
	bindElement: HTMLElement,
}
