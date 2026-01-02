import { Dom } from 'main.core';

class HeightTransition
{
	animate(container: HTMLElement): void
	{
		const prevHeight = Dom.style(container, 'height');

		Dom.style(container, 'transition', 'height 0.2s');
		Dom.style(container, 'height', null);
		const height = container.offsetHeight;

		Dom.style(container, 'height', prevHeight);
		if (height === 0)
		{
			return;
		}

		setTimeout(() => Dom.style(container, 'height', `${height}px`));
	}
}

export const heightTransition = new HeightTransition();
