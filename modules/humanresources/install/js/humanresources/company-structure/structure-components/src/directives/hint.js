import { Reflection, Event, Text } from 'main.core';
import 'ui.hint';

export const Hint = {
	mounted(el: HTMLElement)
	{
		let hint = null;
		Event.bind(el, 'mouseenter', () => {
			if (el.scrollWidth === el.offsetWidth)
			{
				return;
			}

			hint = Reflection.getClass('BX.UI.Hint').createInstance({
				popupParameters: {
					cacheable: false,
					angle: { offset: 0 },
					offsetLeft: el.getBoundingClientRect().width / 2,
				},
			});
			hint.show(el, Text.encode(el.textContent));
		});
		Event.bind(el, 'mouseleave', () => {
			hint?.hide();
		});
	},
};
