import { Event, Text } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { MenuManager } from "main.popup";
import { Converter } from "./converter";
import { SchemeItem } from "./scheme-item";

/**
 * @memberOf BX.Crm.Conversion
 * @mixes EventEmitter
 */
export class SchemeSelector
{
	#entityId: number;
	#container: HTMLElement;
	#menuButton: HTMLElement;
	#label: HTMLElement;
	#converter: Converter;
	#menuId: string;
	#isAutoConversionEnabled: boolean;

	constructor(
		converter: Converter,
		params: {
			entityId: number,
			containerId: string,
			buttonId: string,
			labelId: string,
		}
	) {
		this.#converter = converter;
		this.#entityId = Number(params.entityId);
		this.#container = document.getElementById(params.containerId);
		this.#menuButton = document.getElementById(params.buttonId);
		this.#label = document.getElementById(params.labelId);
		this.#menuId = 'crm_conversion_scheme_selector_' + this.#entityId + '_' + Text.getRandom();
		this.#isAutoConversionEnabled = false;

		if (!this.#entityId || !this.#container || !this.#menuButton || !this.#label || !this.#converter)
		{
			console.error('Error SchemeSelector initializing', this);
		}
		else
		{
			this.#initUI();
			this.#bindEvents();
		}

		EventEmitter.makeObservable(this, 'BX.Crm.Conversion');
	}

	enableAutoConversion()
	{
		this.#isAutoConversionEnabled = true;
	}

	disableAutoConversion()
	{
		this.#isAutoConversionEnabled = false;
	}

	#initUI()
	{
		const currentSchemeItem: SchemeItem|null = this.#converter.getConfig().getScheme().getCurrentItem();
		if (currentSchemeItem)
		{
			this.#label.innerText = currentSchemeItem.getPhrase();
		}
	}

	#bindEvents()
	{
		Event.bind(this.#container, "click", this.#handleContainerClick.bind(this));
		Event.bind(this.#menuButton, "click", this.#handleMenuButtonClick.bind(this));
	}

	#handleContainerClick()
	{
		const event = new BaseEvent({
			data: {
				isCanceled: false
			},
		});
		this.emit('SchemeSelector:onContainerClick', event);
		this.#converter.getConfig().updateFromSchemeItem();
		if (this.#isAutoConversionEnabled && !event.getData().isCanceled)
		{
			this.#converter.convert(this.#entityId);
		}
	}

	#handleMenuButtonClick()
	{
		this.#showMenu();
	}

	#showMenu()
	{
		const anchorPos = BX.pos(this.#container);

		MenuManager.show({
			id: this.#menuId,
			bindElement: this.#menuButton,
			items: this.#getMenuItems(),
			closeByEsc: true,
			cacheable: false,
			offsetLeft: -anchorPos['width'],
		})
	}

	#closeMenu()
	{
		MenuManager.destroy(this.#menuId);
	}

	#getMenuItems()
	{
		const items = [];

		this.#converter.getConfig().getScheme().getItems().forEach((item: SchemeItem) => {
			items.push({
				text: Text.encode(item.getPhrase()),
				onclick: () => {
					this.#handleItemClick(item);
				}
			})
		});

		return items;
	}

	#handleItemClick(item: SchemeItem)
	{
		this.#closeMenu();
		this.#label.innerText = item.getPhrase();
		this.#converter.getConfig().updateFromSchemeItem(item);

		const event = new BaseEvent({
			data: {
				isCanceled: false
			},
		});
		this.emit('SchemeSelector:onSchemeSelected', event);
		if (this.#isAutoConversionEnabled && !event.getData().isCanceled)
		{
			this.#converter.convert(this.#entityId);
		}
	}
}
