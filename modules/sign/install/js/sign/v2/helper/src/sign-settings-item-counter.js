import { Dom, Tag } from 'main.core';

export class SignSettingsItemCounter
{
	static numerate(layout: HTMLElement): void
	{
		const layoutItems = [...layout.children].filter((child) => {
			return Dom.hasClass(child, 'sign-b2e-settings__item');
		});
		const hasCounter = layoutItems.some((node) => {
			return Dom.hasClass(node.firstElementChild, 'sign-b2e-settings__counter');
		});
		if (hasCounter)
		{
			document.documentElement.scrollTop = 0;

			return;
		}

		layoutItems.forEach((node, index) => {
			const connectionNode = index === layoutItems.length - 1
				? Tag.render`<span class="sign-b2e-settings__counter_connect">`
				: null;
			const counter = Tag.render`
				<div class="sign-b2e-settings__counter">
					<span class="sign-b2e-settings__counter_num" data-num="${index + 1}"></span>
					${connectionNode}
				</div>
			`;
			Dom.prepend(counter, node);
			document.documentElement.scrollTop = 0;
		});
	}
}
