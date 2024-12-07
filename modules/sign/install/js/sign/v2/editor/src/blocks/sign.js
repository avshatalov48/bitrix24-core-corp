import { Cache, Loc, Tag, Text as TextFormat } from 'main.core';
import UI from '../../../../document/src/ui';
import Stamp from './stamp';

export default class Sign extends Stamp
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

	getStyles(): { [styleName: string]: string }
	{
		return { backgroundPosition: 'center !important', color: this.#getSignatureColor() };
	}

	updateColor(color: string)
	{
		super.updateColor(color);
		const { layout, colorPicker } = this.#getColorSelectorBtn();
		this.#selectedSignatureColor = color;

		UI.updateColorSelectorBtnColor(layout, color);
		colorPicker.setSelectedColor(color);
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

	#getSignatureColor(): string
	{
		return this.#selectedSignatureColor ?? this.#defaultSignatureColor;
	}

	onSave()
	{
		super.onSave();
		this.#closeColorPickerPopup();
	}

	onRemove()
	{
		super.onRemove();
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
}
