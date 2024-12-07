import { Cache, Loc, Tag, Text as TextFormat, Type } from 'main.core';
import UI from './../ui';
import { BlockWithSynchronizableStyleColor } from './syncable-style-color';

export default class Sign extends BlockWithSynchronizableStyleColor
{
	#cache = new Cache.MemoryCache();
	#defaultSignatureColor: string = '#0047ab';
	#availableSignatureColors: string = ['#000', this.#defaultSignatureColor, '#8b00ff'];
	#selectedSignatureColor: string | null = null;

	/**
	 * Returns placeholder's label.
	 * @return {string}
	 */
	getPlaceholderLabel(): string
	{
		return Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA_SIGN');
	}

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

	getBlockCaption(): ?HTMLElement
	{
		const { layout: colorSelectorBtnLayout } = this.#getColorSelectorBtn();

		return Tag.render`
			<div style="display: flex; flex-direction: row;">
			${colorSelectorBtnLayout}
			<div class="sign-document__block-style--separator"></div>
			</div>
		`;
	}

	/**
	 * Returns type's content in view mode.
	 * @return {HTMLElement | string}
	 */
	getViewContent(): HTMLElement | string
	{
		return Tag.render`
			<div class="sign-document__block-content_member-nodata">
				${TextFormat.encode(this.getPlaceholderLabel())}
			</div>
		`;
	}

	updateColor(color: string)
	{
		super.updateColor(color);
		const { layout, colorPicker } = this.#getColorSelectorBtn();
		this.#selectedSignatureColor = color;

		UI.updateColorSelectorBtnColor(layout, color);
		colorPicker.setSelectedColor(color);
	}

	getStyles(): { [styleName: string]: string }
	{
		return { 'background-position': 'center !important', color: this.#getSignatureColor() };
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
