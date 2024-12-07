import { Dom, Event, Tag, Text, Type } from 'main.core';

import 'ui.design-tokens';
import './color-selector.css';
import { EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';

export const ColorSelectorEvents = {
	EVENT_COLORSELECTOR_VALUE_CHANGE: 'crm.field.colorselector:change',
};

export class ColorSelector
{
	#target: ?HTMLElement = null;
	#colorList: Item[] = [];
	#selectedColorId: string = 'default';
	#readOnlyMode: boolean = false;

	#popup: Popup = null;
	#icon: ?HTMLElement = null;
	#container: ?HTMLElement = null;

	constructor(params: Object)
	{
		this.#target = Type.isDomNode(params.target) ? params.target : null;
		this.#colorList = Type.isArrayFilled(params.colorList) ? params.colorList : [];
		this.#selectedColorId = Type.isStringFilled(params.selectedColorId) ? params.selectedColorId : [];
		this.#readOnlyMode = params.readOnlyMode === true;

		this.togglePopup = this.togglePopup.bind(this);

		this.#create();
	}

	// region DOM management
	#create(): void
	{
		if (!this.#target)
		{
			return;
		}

		this.#icon = Tag.render`
			<div 
				class="crm-field-color-selector ${this.#readOnlyMode ? '--readonly' : ''}"
			></div>
		`;

		Dom.append(this.#icon, this.#target);

		const background = this.#getColorById(this.#selectedColorId).color;
		Dom.style(this.#icon, { '--crm-field-color-selector-color': background });

		Event.bind(this.#icon, 'click', (event) => {
			event.preventDefault();
			this.togglePopup();
		});
	}

	#getColorById(id: string): Object
	{
		return this.#colorList.find((item) => item.id === id);
	}

	togglePopup(): void
	{
		if (this.#readOnlyMode)
		{
			return;
		}

		const popup = this.#getPopup();

		if (popup.isShown())
		{
			popup.close();
		}
		else
		{
			popup.show();
		}
	}

	#getPopup(): Popup
	{
		if (!this.#popup)
		{
			this.#popup = new Popup({
				id: `crm-todo-color-selector-popup-${Text.getRandom()}`,
				autoHide: true,
				bindElement: this.#target,
				content: this.#getContent(),
				closeByEsc: true,
				closeIcon: false,
				draggable: false,
				width: 188,
				padding: 0,
				angle: true,
				offsetLeft: 6,
				offsetTop: 14,
			});
		}

		return this.#popup;
	}

	#getContent(): HTMLElement
	{
		this.#container = Tag.render`<div class="crm-field-color-selector-menu-container"></div>`;

		this.#colorList.forEach((item) => {
			const id = Text.encode(`crm-field-color-selector-menu-item-${item.id}`);
			const element = Tag.render`
				<span 
					id="${id}"
					class="crm-field-color-selector-menu-item ${item.id === this.#selectedColorId ? '--selected' : ''}"
					onclick="${this.onSelectColor.bind(this, item.id)}"
				>
				</span>
			`;
			Dom.append(element, this.#container);

			Dom.style(element, { '--crm-field-color-selector-color': item.color });
		});

		return Tag.render`
			<div class="crm-field-color-selector-popup">
				${this.#container}
			</div>
		`;
	}

	onSelectColor(id: string): void
	{
		this.#getPopup().close();
		this.setValue(id);

		EventEmitter.emit(this, ColorSelectorEvents.EVENT_COLORSELECTOR_VALUE_CHANGE, {
			value: id,
		});
	}

	setValue(id: string): void
	{
		this.#selectedColorId = id;

		const backgroundColor = this.#getColorById(this.#selectedColorId).color;
		Dom.style(this.#icon, { backgroundColor });

		if (!this.#container)
		{
			return;
		}

		Dom.removeClass(this.#container.querySelector('.--selected'), '--selected');

		const target = this.#container.querySelector(`#crm-field-color-selector-menu-item-${id}`);
		if (target)
		{
			Dom.addClass(target, '--selected');
		}
	}

	onKeyUpHandler(event): void
	{
		if (event.keyCode === 13)
		{
			this.#popup?.close();
		}
	}
}
