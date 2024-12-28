import { bind, Dom, Event } from 'main.core';
import { Popup } from 'main.popup';
import '../css/role-master-hint.css';

export const clickableHint = {
	beforeMount(bindElement: HTMLElement, bindings: string): void {
		let popup: Popup = null;
		let isMouseOnHintPopup = false;

		const destroyPopup = () => {
			popup?.destroy();
			popup = null;
			isMouseOnHintPopup = false;
		};

		Event.bind(bindElement, 'mouseenter', () => {
			if (popup === null)
			{
				popup = createHintPopup(bindElement, bindings.value);
				popup.show();
				Event.bind(popup.getPopupContainer(), 'mouseenter', () => {
					isMouseOnHintPopup = true;
				});
			}
		});
		Event.bind(bindElement, 'mouseleave', () => {
			const popupContainer = popup?.getPopupContainer();

			setTimeout(() => {
				if (isMouseOnHintPopup)
				{
					bind(popupContainer, 'mouseleave', (e: MouseEvent) => {
						if (bindElement.contains(e.relatedTarget) === false)
						{
							destroyPopup();
						}
					});
				}
				else
				{
					destroyPopup();
				}
			}, 100);
		});
	},
};

function createHintPopup(bindElement: HTMLElement, html: string): Popup
{
	const bindElementPosition = Dom.getPosition(bindElement);

	return new Popup({
		bindElement: {
			top: bindElementPosition.top + 10,
			left: bindElementPosition.left + bindElementPosition.width / 2,
		},
		className: 'ai__role-master_hint-popup',
		darkMode: true,
		content: html,
		maxWidth: 266,
		maxHeight: 300,
		animation: 'fading-slide',
		angle: true,
		bindOptions: {
			position: 'top',
		},
	});
}
