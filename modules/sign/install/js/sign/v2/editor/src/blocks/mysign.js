import { Cache, Loc, Tag, Text as TextFormat } from 'main.core';
import UI from '../../../../document/src/ui';
import Block from './block';
import Dummy from './dummy';

export default class MySign extends Dummy
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

	#getSignatureColor(): string
	{
		return this.#selectedSignatureColor ?? this.#defaultSignatureColor;
	}

	onClickOut()
	{
		super.onClickOut();
		this.#closeColorPickerPopup();
	}

	onRemove()
	{
		super.onRemove();
		this.#closeColorPickerPopup();
	}

	#closeColorPickerPopup()
	{
		const { colorPicker } = this.#getColorSelectorBtn();
		colorPicker.close();
	}

	onSave()
	{
		super.onSave();
		this.#closeColorPickerPopup();
	}

	getStyles(): { [styleName: string]: string }
	{
		return { backgroundPosition: 'center !important', color: this.#getSignatureColor() };
	}

	changeStyleColor(color: string, emitEvent: boolean = true): void
	{
		super.changeStyleColor(color);
		this.#selectedSignatureColor = color;

		const { layout, colorPicker } = this.#getColorSelectorBtn();
		UI.updateColorSelectorBtnColor(layout, color);
		colorPicker.setSelectedColor(color);

		if (emitEvent)
		{
			this.emit(this.events.onColorStyleChange);
		}
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
			(color) => this.changeStyleColor(color),
			{
				colors: [this.#availableSignatureColors],
				allowCustomColor: false,
				selectedColor: this.#selectedSignatureColor ?? this.#defaultSignatureColor,
				colorPreview: false,
			},
		));
	}
}
