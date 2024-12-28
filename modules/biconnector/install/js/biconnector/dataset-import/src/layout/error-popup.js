import { Tag } from 'main.core';
import { Popup, PopupOptions } from 'main.popup';

export class ErrorPopup
{
	static create(message: string, element: HTMLElement): Popup
	{
		const content = Tag.render`<span class='ui-hint-content'></span>`;
		content.innerHTML = message;

		const popupOptions: PopupOptions = {
			bindElement: element,
			darkMode: true,
			content,
			autoHide: false,
			bindOptions: {
				position: 'top',
			},
			angle: {
				position: 'bottom',
			},
			cacheable: false,
		};

		Popup.setOptions({
			angleMinBottom: 43,
		});

		return new Popup(popupOptions);
	}
}
