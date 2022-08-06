import {B24Options} from "../type";
import {Font, Type} from "./registry";
import "./css/button.css";

export class Button
{
	static create(b24options: B24Options): HTMLElement
	{
		const btnOptions = Type.object(b24options?.views?.click?.button)
			? b24options?.views?.click?.button
			: {}
		;

		const outlined = btnOptions?.outlined === '1',
			plain = btnOptions?.plain === '1',
			rounded = btnOptions?.rounded === '1';

		const newButton = plain
			? document.createElement("a")
			: document.createElement("button")
		;
		newButton.classList.add('b24-form-click-btn');
		newButton.classList.add('b24-form-click-btn-' + b24options.id);

		const wrapper = document.createElement("div");
		wrapper.classList.add('b24-form-click-btn-wrapper');
		wrapper.classList.add('b24-form-click-btn-wrapper-' + b24options.id);

		wrapper.appendChild(newButton);

		newButton.textContent = btnOptions?.text || 'Click';

		wrapper.classList.add(plain ? '--b24-mod-plain' : '--b24-mod-button');
		if (outlined)
		{
			wrapper.classList.add('--b24-mod-outlined');
		}
		if (rounded)
		{
			wrapper.classList.add('--b24-mod-rounded');
		}
		Button.#applyDecoration(wrapper, btnOptions?.decoration);
		Button.#applyAlign(wrapper, btnOptions?.align);

		const fontStyle = btnOptions?.font;
		Button.#loadFont(fontStyle);
		Button.#applyFont(newButton, fontStyle);

		Button.#applyColors(newButton, btnOptions, b24options);

		return wrapper;
	}

	static #applyDecoration(button: HTMLElement, decoration: string)
	{
		switch (decoration) {
			case 'dotted':
			case 'solid':
				button.classList.add('--b24-mod-' + decoration);
				break;
		}
	}

	static #applyAlign(button: HTMLElement, align: string)
	{
		switch (align) {
			case 'center':
			case 'left':
			case 'right':
			case 'inline':
				button.classList.add('--b24-mod-' + align);
				break;
		}
	}

	static #loadFont(fontStyle: string)
	{
		switch (fontStyle)
		{
			case 'modern':
			default:
				Font.load('opensans');
				break;
		}
	}

	static #applyFont(button: HTMLElement, fontStyle: string)
	{
		switch (fontStyle) {
			case 'classic':
			case 'elegant':
			case 'modern':
				button.classList.add("b24-form-click-btn-font-" + fontStyle);
				break;
		}
	}

	static #applyColors(button: HTMLElement, buttonParams = {}, b24options: B24Options)
	{
		const outlined = buttonParams?.outlined === '1',
			plain = buttonParams?.plain === '1';

		const colorText = buttonParams?.color?.text || '#fff',
			colorTextHover = buttonParams?.color?.textHover || '#fff',
			colorBackground = buttonParams?.color?.background || '#3bc8f5',
			colorBackgroundHover = buttonParams?.color?.backgroundHover || '#3eddff',
			colorBorder = colorBackground,
			colorBorderHover = colorBackgroundHover;

		const outlinedColorText = buttonParams?.color?.text || '#535b69',
			outlinedColorTextHover = buttonParams?.color?.textHover || '#535b69',
			outlinedColorBackground = 'transparent',
			outlinedColorBackgroundHover = buttonParams?.color?.backgroundHover || '#cfd4d8',
			outlinedColorBorder = buttonParams?.color?.background || '#c6cdd3',
			outlinedColorBorderHover = buttonParams?.color?.backgroundHover || '#c6cdd3';

		button.style.color = outlined ? outlinedColorText : colorText;
		if (!plain)
		{
			button.style.borderColor = outlined ? outlinedColorBorder : colorBorder;
			button.style.backgroundColor = outlined ? outlinedColorBackground : colorBackground;
		}

		const hoverStyle = `
			.b24-form-click-btn-wrapper-${b24options.id} > button:hover {
				color: ${outlined ? outlinedColorTextHover : colorTextHover} !important;
				background-color: ${outlined ? outlinedColorBackgroundHover : colorBackgroundHover} !important;
				border-color: ${outlined ? outlinedColorBorderHover : colorBorderHover} !important;
			}
			.b24-form-click-btn-wrapper-${b24options.id} > a:hover {
				color: ${outlined ? outlinedColorTextHover : colorTextHover} !important;
			}
		`;
		const styleElem = document.createElement('style');
		if (styleElem.styleSheet) {
			styleElem.styleSheet.cssText = hoverStyle;
		} else {
			styleElem.appendChild(document.createTextNode(hoverStyle));
		}
		document.getElementsByTagName('head')[0].appendChild(styleElem);
	}
}