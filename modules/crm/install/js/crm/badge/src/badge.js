import { Dom, Event, Runtime, Type } from 'main.core';

import 'ui.design-tokens';
import './badge.css';

type BadgeParams = {
	hint?: string;
}

export class Badge
{
	#target: HTMLElement;

	constructor(target: HTMLElement)
	{
		this.#target = target;
	}

	init(params: ?BadgeParams = null): void
	{
		const hint = params?.hint ?? Dom.attr(this.#target, 'data-badgehint');

		if (!Type.isStringFilled(hint))
		{
			return;
		}

		Dom.addClass(this.#target, '--hint');

		Event.unbindAll(this.#target, 'mouseover');
		Event.unbindAll(this.#target, 'mouseleave');

		Event.bind(this.#target, 'mouseover', (event) => {
			Runtime.debounce(
				() => {
					BX.UI.Hint.show(this.#target, hint);

					// hide the title in the grid so that it does not overlap the hint
					const parentRow = event.target.closest('tr');
					if (!parentRow)
					{
						return;
					}

					const title = parentRow.getAttribute('title');
					if (Type.isStringFilled(title))
					{
						parentRow.setAttribute('data-title', title);
						parentRow.removeAttribute('title');
					}
				},
				50,
				this,
			)();
		});

		Event.bind(this.#target, 'mouseleave', (event) => {
			BX.UI.Hint.hide(this.#target);

			const parentRow = event.target.closest('tr');
			if (!parentRow)
			{
				return;
			}

			const title = parentRow.getAttribute('data-title');
			if (Type.isStringFilled(title))
			{
				parentRow.setAttribute('title', title);
			}

			parentRow.removeAttribute('data-title');
		});
	}
}
