import {Dom, Event} from 'main.core';

export function handlerToggleClickMode(container: Element, checked: boolean)
{
	const section = container.closest('.ui-slider-section');
	const headerBlock = section.querySelector('[data-roll="heading-block"]');
	const moreSettingsBtn = section.querySelector('[data-roll="data-more-settings"]');
	const blockCode = section.querySelector('[data-roll="crm-form-embed__settings"]');

	if (checked)
	{
		if (moreSettingsBtn)
		{
			Dom.addClass(moreSettingsBtn, "--visible");
		}
		Dom.removeClass(headerBlock, "--collapse");
		Dom.style(blockCode, 'height', blockCode.scrollHeight + "px");
	}
	else
	{
		if (moreSettingsBtn)
		{
			Dom.removeClass(moreSettingsBtn, "--visible");
		}
		Dom.addClass(headerBlock, "--collapse");
		Dom.style(blockCode, 'height', blockCode.scrollHeight + "px");
		// blockCode.clientHeight;
		Dom.style(blockCode, 'height', "0");
	}

	Event.unbind(blockCode, "transitionend", transitionHandlerForSettingsSection);
	Event.bind(blockCode, "transitionend", transitionHandlerForSettingsSection);
}

function transitionHandlerForSettingsSection(event)
{
	const section = event.currentTarget.closest('.ui-slider-section');
	const blockCode = section.querySelector('[data-roll="crm-form-embed__settings"]');
	if (Dom.style(blockCode, 'height') !== "0px") {
		Dom.style(blockCode, 'height', 'auto');
	}
}