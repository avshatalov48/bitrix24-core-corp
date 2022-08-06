import {Dom, Text, Event} from 'main.core';

export function handlerToggleSwitcher()
{
	const selection = window.getSelection().toString();
	if (selection === "")
	{
		this.parentElement.querySelector('.crm-form-embed-widgets-control > .ui-switcher').click();
	}
}

export function handlerToggleCodeBlock(event)
{
	const section = event.currentTarget.closest('.ui-slider-section');
	const toggleBtn = section.querySelector('[data-roll="data-show-code"]');
	const blockCode = section.querySelector('[data-roll="crm-form-embed__code"]');

	if (Dom.style(blockCode, 'height') === "0px")
	{
		Dom.addClass(toggleBtn, "--up");
		Dom.addClass(blockCode, "--open");
		Dom.style(blockCode, "height", Text.encode(blockCode.scrollHeight) + "px");
	}
	else
	{
		Dom.removeClass(toggleBtn, "--up");
		Dom.removeClass(blockCode, "--open");
		Dom.style(blockCode, "height", Text.encode(blockCode.scrollHeight) + "px");
		// blockCode.clientHeight;
		Dom.style(blockCode, "height", "0");
	}

	Event.unbind(blockCode, 'transitionend', transitionHandlerForCodeBlock);
	Event.bind(blockCode, 'transitionend', transitionHandlerForCodeBlock);
}

function transitionHandlerForCodeBlock(event)
{
	const section = event.currentTarget.closest('.ui-slider-section');
	const blockCode = section.querySelector('[data-roll="crm-form-embed__code"]');
	if (Dom.style(blockCode, "height") !== "0px") {
		Dom.style(blockCode, "height", "auto");
	}
}