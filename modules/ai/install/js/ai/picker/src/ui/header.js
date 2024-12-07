import { Base } from './base';
import { Tag, Type, bind } from 'main.core';
import { IconClose } from './icon-close';

import '../css/ui/header.css';

type HeaderProps = {
	articleCode: number;
	className: string;
}

export class Header extends Base
{
	#articleCode: number | null;
	#className: string;

	constructor(props: HeaderProps) {
		super(props);

		this.#articleCode = Type.isNumber(props.articleCode) ? props.articleCode : null;
		this.#className = Type.isString(props.className) ? props.className : '';

		this.setEventNamespace('AI:Picker:Header');
	}

	render(): HTMLElement
	{
		const closeIcon = new IconClose(
			{
				onClick: () => {
					this.emit('click-close-icon');
				},
			},
		);

		return Tag.render`
			<div class="ai__picker_header ${this.#className}">
				<div class="ai__picker_header-icon"></div>
				<div style="margin-top: -10px;">
					<h3 class="ui-typography-heading-h3 ui- ai__picker_header-title">
						${this.getMessage('header')}
					</h3>
					${this.#renderHelpLink()}
				</div>

				${closeIcon.render()}
			</div>
		`;
	}

	#renderHelpLink(): HTMLElement | null
	{
		if (!this.#articleCode || !top.BX || !top.BX.Helper)
		{
			return null;
		}

		const helpLink = Tag.render`
			<a href="" class="ai__picker_header-subtitle">
				${this.getMessage('help_link')}
			</a>
		`;

		const articleCode = this.#articleCode;

		bind(helpLink, 'click', (e) => {
			if (top.BX && top.BX.Helper)
			{
				top.BX.Helper.show(`redirect=detail&code=${articleCode}`);
			}

			e.preventDefault();

			return false;
		});

		return helpLink;
	}
}
