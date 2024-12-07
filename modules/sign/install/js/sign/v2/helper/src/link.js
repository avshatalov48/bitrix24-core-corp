import { Uri } from 'main.core';

import './style.css';

export class Link
{
	static replaceInLoc(text: string, link: Uri, linkTag: string = 'link'): string
	{
		return text
			.replace(
				`[${linkTag}]`,
				`
					<a class="sign-v2e-helper__link" 
						href="${link}"
						target="_blank"
					>
				`,
			)
			.replace(`[/${linkTag}]`, '</a>')
		;
	}
}
