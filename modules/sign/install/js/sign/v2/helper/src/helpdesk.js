import { Event, Tag } from 'main.core';

import './style.css';

const defaultHelpdeskTag = 'helpdesklink';
const defaultRedirectValue = 'detail';

export class Helpdesk
{
	static defaultRedirectValue = defaultRedirectValue;

	static show(code: string, redirect: string = defaultRedirectValue): void
	{
		if (top.BX.Helper)
		{
			const redirectLink = Tag.safe`${redirect}`;
			const helpdeskCode = Tag.safe`${code}`;

			top.BX.Helper.show(`redirect=${redirectLink}&code=${helpdeskCode}`);
		}
	}

	static bindHandler(element: HTMLElement, code: string, redirect: string = defaultRedirectValue): void
	{
		Event.bind(element, 'click', (event) => {
			this.show(code, redirect);
			event.preventDefault();
		});
	}

	static replaceLink(
		text: string,
		code: string,
		redirect: string = defaultRedirectValue,
		extraClasses: Array<string> = [],
	): string
	{
		return text
			.replace(
				`[${defaultHelpdeskTag}]`,
				`
					<a class="sign-v2e-helper__link ${extraClasses.join(' ')}"
						href="javascript:top.BX.Helper.show('redirect=${Tag.safe`${redirect}`}&code=${Tag.safe`${code}`}');"
					>
				`,
			)
			.replace(`[/${defaultHelpdeskTag}]`, '</a>')
		;
	}
}
