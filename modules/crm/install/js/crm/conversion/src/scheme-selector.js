import { Event, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { type MenuItemOptions, MenuManager } from 'main.popup';
import { Converter } from './converter';
import { EntitySelector } from './entity-selector';
import type { Scheme } from './scheme';
import { SchemeItem } from './scheme-item';

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
	#analytics: {c_element?: string} = {};

	constructor(
		converter: Converter,
		params: {
			entityId: number,
			containerId: string,
			buttonId: string,
			labelId: string,
			analytics?: {
				c_element: string,
			}
		},
	)
	{
		this.#converter = converter;
		this.#entityId = Number(params.entityId);
		this.#container = document.getElementById(params.containerId);
		this.#menuButton = document.getElementById(params.buttonId);
		this.#label = document.getElementById(params.labelId);
		this.#menuId = `crm_conversion_scheme_selector_${this.#entityId}_${Text.getRandom()}`;
		this.#isAutoConversionEnabled = false;
		if (Type.isStringFilled(params.analytics.c_element))
		{
			this.#analytics.c_element = params.analytics.c_element;
		}

		if (!this.#entityId || !this.#container || !this.#menuButton || !this.#label || !this.#converter)
		{
			console.error('Error SchemeSelector initializing', this);
		}
		else
		{
			this.#initUI();
			this.#bindEvents();
		}

		EventEmitter.makeObservable(this, 'BX.Crm.Conversion.SchemeSelector');
	}

	destroy(): void
	{
		this.#closeMenu();

		this.#unbindEvents();

		this.unsubscribeAll();
	}

	/**
	 * Alias for 'destroy'
	 */
	release(): void
	{
		this.destroy();
	}

	enableAutoConversion(): void
	{
		this.#isAutoConversionEnabled = true;
	}

	disableAutoConversion()
	{
		this.#isAutoConversionEnabled = false;
	}

	#initUI()
	{
		const currentSchemeItem: SchemeItem | null = this.#converter.getConfig().getScheme().getCurrentItem();
		if (currentSchemeItem)
		{
			this.#label.innerText = currentSchemeItem.getPhrase();
		}
	}

	#bindEvents(): void
	{
		Event.bind(this.#container, 'click', this.#handleContainerClick.bind(this));
		Event.bind(this.#menuButton, 'click', this.#handleMenuButtonClick.bind(this));
	}

	#unbindEvents(): void
	{
		Event.unbind(this.#container, 'click', this.#handleContainerClick.bind(this));
		Event.unbind(this.#menuButton, 'click', this.#handleMenuButtonClick.bind(this));
	}

	#handleContainerClick()
	{
		const event = new BaseEvent({
			data: {
				isCanceled: false,
			},
		});
		this.emit('onContainerClick', event);
		this.#converter.getConfig().updateFromSchemeItem();
		if (this.#isAutoConversionEnabled && !event.getData().isCanceled)
		{
			this.#converter.setAnalyticsElement(this.#analytics.c_element);

			this.#converter.convert(this.#entityId);
		}
	}

	#handleMenuButtonClick()
	{
		this.#showMenu();
	}

	#showMenu()
	{
		// eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
		const anchorPos = BX.pos(this.#container);

		MenuManager.show({
			id: this.#menuId,
			bindElement: this.#menuButton,
			items: this.#getMenuItems(),
			closeByEsc: true,
			cacheable: false,
			offsetLeft: -anchorPos.width,
		});
	}

	#closeMenu()
	{
		MenuManager.destroy(this.#menuId);
	}

	#getMenuItems(): MenuItemOptions[]
	{
		const scheme = this.#converter.getConfig().getScheme();

		const items = [];
		for (const item of scheme.getItems())
		{
			items.push({
				text: Text.encode(item.getPhrase()),
				onclick: () => {
					this.#handleItemClick(item);
				},
			});
		}

		const entitySelector = this.#prepareEntitySelector(scheme);
		if (entitySelector)
		{
			items.push({
				text: this.#converter.getMessagePublic('openEntitySelector'),
				onclick: () => {
					this.#closeMenu();

					void entitySelector.show();
				},
			});
		}

		return items;
	}

	#prepareEntitySelector(scheme: Scheme): ?EntitySelector
	{
		if (this.#converter.getEntityTypeId() !== BX.CrmEntityType.enumeration.lead)
		{
			return null;
		}

		const allEntityTypeIdsInScheme = scheme.getAllEntityTypeIds();

		const dstEntityTypeIds = [];
		if (allEntityTypeIdsInScheme.includes(BX.CrmEntityType.enumeration.contact))
		{
			dstEntityTypeIds.push(BX.CrmEntityType.enumeration.contact);
		}

		if (allEntityTypeIdsInScheme.includes(BX.CrmEntityType.enumeration.company))
		{
			dstEntityTypeIds.push(BX.CrmEntityType.enumeration.company);
		}

		if (!Type.isArrayFilled(dstEntityTypeIds))
		{
			return null;
		}

		return new EntitySelector(this.#converter, this.#entityId, dstEntityTypeIds);
	}

	#handleItemClick(item: SchemeItem)
	{
		this.#closeMenu();
		this.#label.innerText = item.getPhrase();
		this.#converter.getConfig().updateFromSchemeItem(item);

		const event = new BaseEvent({
			data: {
				isCanceled: false,
			},
		});
		this.emit('onSchemeSelected', event);
		if (this.#isAutoConversionEnabled && !event.getData().isCanceled)
		{
			this.#converter.setAnalyticsElement(this.#analytics.c_element);

			this.#converter.convert(this.#entityId);
		}
	}
}
