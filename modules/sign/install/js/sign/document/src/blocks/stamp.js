import { Loc, Tag, Text as TextFormat } from 'main.core';
import Dummy from './dummy';

export default class Stamp extends Dummy
{
	/**
	 * Returns true if style panel mast be showed.
	 * @return {boolean}
	 */
	isStyleAllowed(): boolean
	{
		return false;
	}

	/**
	 * Returns initial dimension of block.
	 * @return {width: number, height: number}
	 */
	getInitDimension(): {width: number, height: number}
	{
		return {
			width: 151,
			height: 151
		};
	}

	/**
	 * Returns placeholder's label.
	 * @return {string}
	 */
	getPlaceholderLabel(): string
	{
		return Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA_STAMP');
	}

	/**
	 * Returns type's content in view mode.
	 * @return {HTMLElement | string}
	 */
	getViewContent(): HTMLElement | string
	{
		const {width, height} = this.block.getPosition();

		if (this.data.base64)
		{
			return Tag.render`
				<div class="sign-document__block-content_stamp" style="background-image: url(${src})"></div>
			`;
		}
		else
		{
			return Tag.render`
				<div class="sign-document__block-content_member-nodata">
					
				</div>
			`;
		}
	}
}
