import { Dom, Event, Loc, Tag, Text as TextFormat, Reflection, Type } from 'main.core';
import { UI as SignUI } from 'sign.ui';
import { MemberItem } from './types/document';

import './style.css';
import 'color_picker';

type BlockLayoutOptions = {
	onRemove: () => {},
	onSave: () => {}
};

export type ColorPickerOptions = {
	colors?: Array<Array<string>>,
	allowCustomColor?: boolean,
	selectedColor?: string,
	colorPreview?: boolean,
}

const ColorPicker = Reflection.getClass('BX.ColorPicker');

export default class UI
{
	/**
	 * Sets width/height/top/left to element.
	 * @param {HTMLElement} element
	 * @param {{[key: string]: number}} rect
	 */
	static setRect(element: HTMLElement, rect: {[key: string]: number})
	{
		Object.keys(rect).map(key => {
			rect[key] = parseInt(rect[key]) + 'px';
		});
		Dom.style(element, rect);
	}

	/**
	 * Returns block's layout.
	 * @param {BlockLayoutOptions} options Layout options.
	 * @return {HTMLElement}
	 */
	static getBlockLayout(options: BlockLayoutOptions): HTMLElement
	{
		return Tag.render`
			<div class="sign-document__block-wrapper">
				<div class="sign-document__block-panel--wrapper" data-role="sign-block__actions">
				</div>
				<div class="sign-document__block-content">
				</div>
				<div class="sign-document__block-actions">
					<div class="sign-document__block-actions--wrapper">
						<button class="sign-document__block-actions-btn --remove sign-block-action-remove" data-role="removeAction" onclick="${options.onRemove}"></button>
						<button class="sign-document__block-actions-btn --save sign-block-action-save" data-role="saveAction" onclick="${options.onSave}"></button>
					</div>
				</div>
			</div>
		`;
	}

	/**
	 * Returns member selector for block.
	 * @param {Array<MemberItem>} members All document's members.
	 * @param {number} selectedValue Selected member.
	 * @param {() => {}} onChange Handler on change value.
	 * @return {HTMLElement}
	 */
	static getMemberSelector(members: Array<MemberItem>, selectedValue: number,  onChange: () => {}): HTMLElement
	{
		const menuItems = {};
		let selectedName = Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NAME_NOT_SET');

		members.map(member => {
			member.name = member.name || Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NAME_NOT_SET');
			menuItems[member.part] = member.name;

			if (member.part === selectedValue)
			{
				selectedName = member.name;
			}
		});

		const memberSelector = (members.length > 1)
			? Tag.render`<span>${TextFormat.encode(selectedName)}</span>`
			: Tag.render`<i>${TextFormat.encode(selectedName)}</i>`;

		if (members.length > 1)
		{
			SignUI.bindSimpleMenu({
				bindElement: memberSelector,
				items: menuItems,
				actionHandler: value => {
					memberSelector.innerHTML = menuItems[value];
					onChange(parseInt(value));
				}
			});
		}

		return Tag.render`
			<div class="sign-document-block-member-wrapper">
				${memberSelector}
			</div>
		`;
	}

	/**
	 * Returns resizing area's layout.
	 * @return {HTMLElement}
	 */
	static getResizeArea(): HTMLElement
	{
		return Tag.render`
			<div class="sign-document__resize-area">
				<div class="sign-area-resizable-controls">
					<span class="sign-document__move-control"></span>
					<div class="sign-document__resize-control --middle-top"></div>
					<div class="sign-document__resize-control --right-top"></div>
					<div class="sign-document__resize-control --middle-right"></div>
					<div class="sign-document__resize-control --right-bottom"></div>
					<div class="sign-document__resize-control --middle-bottom"></div>
					<div class="sign-document__resize-control --left-bottom"></div>
					<div class="sign-document__resize-control --middle-left"></div>
				</div>
			</div>
		`;
	}

	/**
	 * Returns style panel layout.
	 * @return {HTMLElement}
	 */
	static getStylePanel(
		actionHandler: (actionName: string, actionValue: string) => {},
		collectStyles: {[key: string]: string},
	): HTMLElement
	{
		// font family selector
		const fonts = {
			'"Times New Roman", Times':   '<span style="font-family: \'Times New Roman\', Times">Times New Roman</span>',
			'"Courier New"':              '<span style="font-family: \'Courier New\'">Courier New</span>',
			'Arial, Helvetica':           '<span style="font-family: Arial, Helvetica">Arial / Helvetica</span>',
			'"Arial Black", Gadget':      '<span style="font-family: \'Arial Black\', Gadget">Arial Black</span>',
			'Tahoma, Geneva':             '<span style="font-family: Tahoma, Geneva">Tahoma / Geneva</span>',
			'Verdana':                    '<span style="font-family: Verdana">Verdana</span>',
			'Georgia, serif':             '<span style="font-family: Georgia, serif">Georgia</span>',
			'monospace':                  '<span style="font-family: monospace">monospace</span>',
		};
		const fontFamily = Tag.render`<div class="sign-document__block-style-btn --btn-font-family">${fonts[collectStyles['font-family']] || 'Font'}</div>`;
		SignUI.bindSimpleMenu({
			bindElement: fontFamily,
			items: fonts,
			actionHandler: value => {
				fontFamily.innerHTML = fonts[value];
				actionHandler('family', value);
			}
		});

		// font size selector
		
		let fontSizereal = parseInt(collectStyles['font-size']);
		let fontSizeValue = 14;
		
		if (fontSizereal) 
		{
			fontSizeValue = fontSizereal;
		}

		const fontSize = Tag.render`<div class="sign-document__block-style-btn --btn-fontsize">${fontSizeValue+ 'px' || '<i></i>'}</div>`;
		SignUI.bindSimpleMenu({
			bindElement: fontSize,
			items: [
				'6px', '7px', '8px', '9px', '10px', '11px', '12px', '13px', '14px', '15px', '16px',
				'18px', '20px', '22px', '24px', '26px', '28px', '36px', '48px', '72px'
			],
			actionHandler: value => {
				fontSize.innerHTML = parseInt(value) + 'px';
				actionHandler('size', value);
			},
			currentValue: fontSizeValue,
		});

		// color
		const { layout: fontColor } = UI.getColorSelectorBtn(
			collectStyles.color ?? '#000',
			(color) => actionHandler('color', color),
		);

		return Tag.render`
			<div class="sign-document__block-style--panel">
<!--				<div class="sign-document__block-style&#45;&#45;move-control"></div>-->
				${fontFamily}
				${fontSize}
				${fontColor}
				<div class="sign-document__block-style--separator"></div>
				<div class="sign-document__block-style-btn --btn-bold" data-action="bold"><i></i></div>
				<div class="sign-document__block-style-btn --btn-italic" data-action="italic"><i></i></div>
				<div class="sign-document__block-style-btn --btn-underline" data-action="underline"><i></i></div>
				<div class="sign-document__block-style-btn --btn-strike" data-action="through"><i></i></div>
				<div class="sign-document__block-style-btn --btn-align-left" data-action="left"><i></i></div>
				<div class="sign-document__block-style-btn --btn-align-center" data-action="center"><i></i></div>
				<div class="sign-document__block-style-btn --btn-align-right" data-action="right"><i></i></div>
				<div class="sign-document__block-style-btn --btn-align-justify" data-action="justify"><i></i></div>
			</div>
		`;
	}

	static getColorSelectorBtn(
		defaultColorPickerColor: string,
		onColorSelect: (string) => any,
		colorPickerOptions: ColorPickerOptions = {},
	): { layout: HTMLElement, colorPicker: BX.ColorPicker }
	{
		const layout = Tag.render`<div class="sign-document__block-style-btn --btn-color">
				<span class="sign-document__block-style-btn--color-block"></span> 
				<span>${Loc.getMessage('SIGN_JS_DOCUMENT_STYLE_COLOR')}</span>
			</div>`
		;
		UI.updateColorSelectorBtnColor(layout, defaultColorPickerColor);
		const updatedColorPickerOptions = {
			...colorPickerOptions,
			bindElement: layout,
			onColorSelected:
				(color) => {
					onColorSelect(color);
					UI.updateColorSelectorBtnColor(layout, color);
				},
		};

		const picker = new ColorPicker(updatedColorPickerOptions);

		Event.bind(layout, 'click', () => {
			picker.open();
		});

		return { layout, colorPicker: picker };
	}

	static updateColorSelectorBtnColor(layout: HTMLElement, color: string): void
	{
		const circleColor = layout.querySelector('.sign-document__block-style-btn--color-block');
		if (Type.isNil(circleColor))
		{
			return;
		}

		Dom.style(
			circleColor,
			'background-color',
			color,
		);
	}
}
