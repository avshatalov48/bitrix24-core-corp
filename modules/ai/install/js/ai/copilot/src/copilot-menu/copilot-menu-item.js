import { EventEmitter } from 'main.core.events';

export type BaseMenuItemOptions = {
	id?: string;
	code: string;
	text?: string;
	href?: string;
	icon?: string;
	children?: BaseMenuItem[],
	onClick?: Function;
	disabled: boolean;
}

export type BaseMenuItemGetOptions = {
	id: string;
	code: string;
	text: string;
	icon: string;
	command: Function;
	notHighlight?: boolean;
}

export class BaseMenuItem extends EventEmitter
{
	id: string = '';
	code: string;
	text: string;
	icon: string;
	href: string;
	children: BaseMenuItem[];
	onClick: Function;
	notHighlight: boolean;
	disabled: boolean;

	constructor(options: BaseMenuItemOptions)
	{
		super();
		this.setEventNamespace('AI.CopilotMenuItem');

		if (options.id) {
			this.id = options.id;
		}

		this.code = options.code;
		this.text = options.text;
		this.icon = options.icon;
		this.href = options.href;
		this.children = options.children ?? [];
		this.onClick = options.onClick;
		this.disabled = options.disabled;
	}

	getOptions(): BaseMenuItemGetOptions
	{
		return {
			id: this.id,
			code: this.code,
			text: this.text,
			icon: this.icon,
			href: this.href,
			command: this.onClick,
			disabled: this.disabled,
			children: this.children.map((childrenMenuItem) => {
				if (childrenMenuItem instanceof BaseMenuItem)
				{
					return childrenMenuItem.getOptions();
				}

				return childrenMenuItem;
			}),
		};
	}
}
