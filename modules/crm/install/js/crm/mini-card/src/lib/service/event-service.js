import { Event } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';

import { EVENTS } from '../../mini-card';

export class EventService
{
	registerChildPopup(eventEmitter: EventEmitter, popup: Popup): void
	{
		const popupContainer = popup.getPopupContainer();

		Event.bind(popupContainer, 'mouseenter', () => {
			eventEmitter.emit(EVENTS.INTERNAL_ON_MOUSE_ENTER_CHILD_POPUP);
		});

		Event.bind(popupContainer, 'mouseleave', () => {
			eventEmitter.emit(EVENTS.INTERNAL_ON_MOUSE_LEAVE_CHILD_POPUP);
		});

		eventEmitter.subscribe(EVENTS.INTERNAL_ON_CLOSE_MAIN_POPUP, () => {
			popup.close();
		});
	}
}
