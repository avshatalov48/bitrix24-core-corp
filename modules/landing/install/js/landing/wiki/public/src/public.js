import {Event, Type, Text, Dom} from 'main.core';
import {SliderHacks} from 'landing.sliderhacks';

Event.bind(document, 'click', (event: MouseEvent) => {
	if (Type.isDomNode(event.target))
	{
		const link = event.target.closest('a:not(.ui-btn):not([data-fancybox])');
		if (Type.isDomNode(link))
		{
			if (Type.isStringFilled(link.href) && link.target !== '_blank')
			{
				event.preventDefault();
				void SliderHacks.reloadSlider(link.href);
			}
		}

		const pseudoLink = event.target.closest('[data-pseudo-url]');
		if (Type.isDomNode(pseudoLink))
		{
			const urlParams = Dom.attr(pseudoLink, 'data-pseudo-url');

			if (
				Text.toBoolean(urlParams.enabled)
				&& Type.isStringFilled(urlParams.href)
			)
			{
				if (urlParams.target === '_self')
				{
					event.stopImmediatePropagation();
					void SliderHacks.reloadSlider(urlParams.href);
				}
				else
				{
					top.open(urlParams.href, urlParams.target);
				}
			}
		}
	}
});