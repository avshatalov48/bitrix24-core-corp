import { Loc, Tag, Text as TextFormat, Type } from 'main.core';
import Dummy from '../dummy';

export default class DocumentDate extends Dummy
{
	#content: HTMLElement;
	/**
	 * Returns type's content in view mode.
	 * @return {HTMLElement | string}
	 */
	getViewContent(): HTMLElement | string
	{
		if (Type.isStringFilled(this.data?.text))
		{
			const dateString = TextFormat.encode(this.data.text || '');

			this.#content = Tag.render`
				<div class="sign-document__block-b2e-document-date">
					${dateString}
				</div>
			`;
		}
		else
		{
			const text = Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_DOCUMENT_PLACEHOLDER', {
				'#PLACEHOLDER#': Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_DOCUMENT_DATE_TITLE'),
			});
			this.#content = Tag.render`
				<div class="sign-document__block-content_member-nodata">
					${text}
				</div>
			`;
		}

		return this.#content;
	}
}
