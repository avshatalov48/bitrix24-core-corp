import { Reflection, Dom } from 'main.core';
import { type PopupOptions } from 'main.popup';
import './style.css';

export class Hint
{
	static create(dom: HTMLElement, popupParameters: PopupOptions = {}): BX.UI.Hint
	{
		const popupHint = Reflection.getClass('BX.UI.Hint').createInstance({
			popupParameters: {
				autoHide: true,
				...popupParameters,
			},
		});
		popupHint.init(dom);
		Dom.addClass(dom, '--with-sign-hint');

		return popupHint;
	}
}
