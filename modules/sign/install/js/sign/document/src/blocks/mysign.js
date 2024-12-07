import { Cache, Loc, Tag, Text as TextFormat, Type } from 'main.core';
import UI from './../ui';
import { BlockWithSynchronizableStyleColor } from './syncable-style-color';

export default class MySign extends BlockWithSynchronizableStyleColor
{
	dataSrc: string;

	#cache = new Cache.MemoryCache();
	#defaultSignatureColor: string = '#0047ab';
	#availableSignatureColors: string = ['#000', this.#defaultSignatureColor, '#8b00ff'];
	#selectedSignatureColor: string | null = null;

	/**
	 * Returns true if block is in singleton mode.
	 * @return {boolean}
	 */
	isSingleton(): boolean
	{
		return true;
	}

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
	getInitDimension(): { width: number, height: number }
	{
		return {
			width: 200,
			height: 70,
		};
	}

	updateColor(color: string)
	{
		super.updateColor(color);
		const { layout, colorPicker } = this.#getColorSelectorBtn();
		this.#selectedSignatureColor = color;

		UI.updateColorSelectorBtnColor(layout, color);
		colorPicker.setSelectedColor(color);
	}

	/**
	 * @return {HTMLElement | null}
	 */
	getBlockCaption(): ?HTMLElement
	{
		const { layout: colorSelectorBtnLayout } = this.#getColorSelectorBtn();

		return Tag.render`
			<div style="display: flex; flex-direction: row;">
			${colorSelectorBtnLayout}
			<div class="sign-document__block-style--separator"></div>
			<div class="sign-document-block-member-wrapper">
				<i>${Loc.getMessage('SIGN_JS_DOCUMENT_SIGN_ACTION_BUTTON')}</i>
			</div>
			</div>
		`;
	}

	#getColorSelectorBtn(): { layout: HTMLElement, colorPicker: BX.ColorPicker }
	{
		return this.#cache.remember('colorSelectorBtn', () => UI.getColorSelectorBtn(
			this.#selectedSignatureColor ?? this.#defaultSignatureColor,
			(color) => {
				this.#selectedSignatureColor = color;
				this.onStyleChange();
			},
			{
				colors: [this.#availableSignatureColors],
				allowCustomColor: false,
				selectedColor: this.#selectedSignatureColor ?? this.#defaultSignatureColor,
				colorPreview: false,
			},
		));
	}

	/**
	 * Returns type's content in view mode.
	 * @return {HTMLElement | string}
	 */
	getViewContent(): HTMLElement | string
	{
		const { width, height } = this.block.getPosition();

		let src = null;
		if (this.dataSrc)
		{
			src = this.dataSrc;
		}
		else if (this.data.base64)
		{
			src = 'data:image;base64,' + this.data.base64;
		}

		if (src)
		{
			return Tag.render`
				<img src="${src}" alt="">
			`;
		}
		else
		{
			return Tag.render`
				<div class="sign-document__block-content_member-nodata">
					${TextFormat.encode(this.data.text || this.getPlaceholderLabel())}
				</div>
			`;
		}
	}

	/**
	 * Returns placeholder's label.
	 * @return {string}
	 */
	getPlaceholderLabel(): string
	{
		return Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA_MY_SIGN');
	}

	getStyles(): { [styleName: string]: string }
	{
		return { 'background-position': 'center !important', color: this.#getSignatureColor() };
	}

	#getSignatureColor(): string
	{
		return this.#selectedSignatureColor ?? this.#defaultSignatureColor;
	}

	onSave()
	{
		super.onSave();
		this.#closeColorPickerPopup();
	}

	onClickOut()
	{
		super.onClickOut();
		this.#closeColorPickerPopup();
	}

	#closeColorPickerPopup()
	{
		const { colorPicker } = this.#getColorSelectorBtn();
		colorPicker.close();
	}

	onStyleRender(styles: Object)
	{
		super.onStyleRender(styles);
		if (!Type.isNil(styles.color))
		{
			this.updateColor(styles.color);
		}
	}
}
