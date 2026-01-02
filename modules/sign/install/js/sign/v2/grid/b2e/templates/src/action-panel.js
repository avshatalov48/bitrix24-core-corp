import { Dom, Type } from 'main.core';

let moveBtnOnClick = null;

type ActionButtonOptions = {
	id: string;
	disabled: boolean;
	title: string;
};

export function toggleActionButton(options: ActionButtonOptions): void
{
	const button = document.getElementById(options.id);
	if (!Type.isElementNode(button))
	{
		return;
	}

	if (options.disabled && !Dom.hasClass(button, 'ui-action-panel-item-is-disabled'))
	{
		Dom.addClass(button, 'ui-action-panel-item-is-disabled');
		Dom.attr(
			button,
			'title',
			options.title,
		);
		Dom.style(button, 'user-select', 'none');
		moveBtnOnClick = button.onclick;
		button.onclick = () => 0;
	}
	else if (!options.disabled && Dom.hasClass(button, 'ui-action-panel-item-is-disabled'))
	{
		Dom.removeClass(button, 'ui-action-panel-item-is-disabled');
		Dom.attr(button, 'title', '');
		Dom.style(button, 'user-select', 'auto');
		button.onclick = moveBtnOnClick;
	}
}
