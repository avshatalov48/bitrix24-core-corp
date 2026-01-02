import { Reflection, Event, Text, Type } from 'main.core';
import 'ui.hint';

export const Hint = {
	mounted(el: HTMLElement, binding): void
	{
		const value = Type.isString(binding?.value) ? binding.value.trim() : '';
		let hint = null;

		const shouldShow = () => (value ? true : el.scrollWidth !== el.offsetWidth);
		const getText = () => Text.encode(value || el.textContent);

		const onMouseEnter = () => {
			if (!shouldShow())
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
			hint.show(el, getText());
		};

		const onMouseLeave = () => {
			hint?.hide();
		};

		Event.bind(el, 'mouseenter', onMouseEnter);
		Event.bind(el, 'mouseleave', onMouseLeave);
	},
};
