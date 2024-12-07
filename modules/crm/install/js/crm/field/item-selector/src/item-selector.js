import { Dom, Event, Runtime, Tag, Text, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { MenuItem, MenuManager } from 'main.popup';
import { ItemSelectorButton, ItemSelectorButtonState } from './item-selector-button';
import { ItemSelectorOptions } from './item-selector-options';

import 'ui.design-tokens';
import './item-selector.css';

const MENU_ITEM_CLASS_ACTIVE = 'menu-popup-item-accept';
const MENU_ITEM_CLASS_INACTIVE = 'menu-popup-item-none';

type Item = {
	id: string | number;
	title: string;
}

export const CompactIcons = {
	NONE: null,
	BELL: 'bell',
};

export const Events = {
	EVENT_ITEMSELECTOR_OPEN: 'crm.field.itemselector:open',
	EVENT_ITEMSELECTOR_VALUE_CHANGE: 'crm.field.itemselector:change',
};

export class ItemSelector
{
	// options
	#id: ?string;
	#target: ?HTMLElement = null;
	#valuesList: Item[] = [];
	#selectedValues: Array<string | number> = [];
	#readonlyMode: boolean = false;
	#compactMode: boolean = false;
	#icon: ?string = null;
	#htmlItemCallback: ?Function = null;
	#multiple: boolean = true;

	// local
	#containerEl: ?HTMLElement = null;
	#selectedElementList: Object = {};
	#selectedHiddenElementList: Object = {};
	#selectedValueWrapperEl: ?HTMLElement = null;
	#valuesMenuPopup: ?Menu = null;
	#addButton: ?ItemSelectorButton = null;
	#addButtonCompact: ?HTMLElement = null;

	constructor(params: ItemSelectorOptions)
	{
		this.#assertValidParams(params);

		this.#id = params.id || `item-selector-${Text.getRandom()}`;
		this.#target = Type.isDomNode(params.target) ? params.target : null;
		this.#valuesList = Type.isArrayFilled(params.valuesList) ? params.valuesList : [];
		this.#selectedValues = Type.isArrayFilled(params.selectedValues) ? params.selectedValues : [];
		this.#readonlyMode = params.readonlyMode === true;
		this.#compactMode = params.compactMode === true;

		if (Type.isStringFilled(params.icon) && Object.values(CompactIcons).includes(params.icon))
		{
			this.#icon = params.icon;
		}

		this.#htmlItemCallback = Type.isFunction(params.htmlItemCallback) ? params.htmlItemCallback : null;
		this.#multiple = Boolean(params.multiple ?? true);

		this.#create();
		this.#bindEvents();
		this.#applyCurrentValue(100);
	}

	// region Data management
	getValue(): Array
	{
		return this.#selectedValues;
	}

	setValue(values: Array, isEmitEvent: boolean = false): void
	{
		this.clearAll();

		values.forEach((value: string) => {
			this.addValue(value, isEmitEvent);
		});
	}

	addValue(value: mixed, isEmitEvent: boolean = false): void
	{
		const rawValue = this.#valuesList.find((element: Item) => {
			return element?.id?.toString() === value?.toString();
		});

		if (!rawValue)
		{
			return;
		}

		if (!this.#compactMode)
		{
			const itemEl: HTMLElement = Tag.render`
			<span class="crm-field-item-selector__value">
				<span class="crm-field-item-selector__value-title">
					${Text.encode(rawValue.title)}
				</span>
			</span>
		`;

			if (!this.#readonlyMode)
			{
				Dom.append(
					Tag.render`
					<span class="crm-field-item-selector__value-clear-icon" data-item-selector-id="${rawValue.id}"/>
					</span>
				`,
					itemEl,
				);
			}

			Dom.append(itemEl, this.#selectedValueWrapperEl);

			const itemElWidth = itemEl.offsetWidth;

			Dom.addClass(itemEl, '--hidden');

			if (this.#isTargetOverflown(itemElWidth))
			{
				this.#selectedHiddenElementList[rawValue.id] = itemEl;
			}
			else
			{
				this.#animateAdd(itemEl); // add animation
			}

			this.#selectedElementList[rawValue.id] = itemEl;
			this.#applyAddButtonState(itemElWidth);
		}

		this.#selectedValues.push(rawValue.id);

		this.#adjustAddButtonCompact();

		if (isEmitEvent)
		{
			this.#emitEvent();
		}
	}

	removeValue(value: string | number, isEmitEvent: boolean = false): void
	{
		if (!this.#compactMode)
		{
			if (this.#selectedElementList[value] && Type.isDomNode(this.#selectedElementList[value]))
			{
				this.#animateRemove(this.#selectedElementList[value]);
				Dom.remove(this.#selectedElementList[value]);

				delete this.#selectedElementList[value];
			}

			const isHiddenElementNeedApply = this.#selectedHiddenElementList[value]
				&& Type.isDomNode(this.#selectedHiddenElementList[value])
			;

			if (isHiddenElementNeedApply)
			{
				delete this.#selectedHiddenElementList[value];
			}

			if (!this.#isTargetOverflown() || isHiddenElementNeedApply)
			{
				const itemEl = Object.values(this.#selectedHiddenElementList)[0];
				if (Type.isDomNode(itemEl) && !this.#isTargetOverflown(itemEl.offsetWidth))
				{
					this.#animateAdd(itemEl);
					delete this.#selectedHiddenElementList[Object.keys(this.#selectedHiddenElementList)[0]];
				}
			}

			this.#applyAddButtonState();
		}

		this.#selectedValues = this.#selectedValues?.filter((item: string | number) => {
			return item?.toString() !== value?.toString();
		});

		this.#adjustAddButtonCompact();

		if (isEmitEvent)
		{
			this.#emitEvent();
		}
	}

	clearAll(): void
	{
		if (!Type.isArrayFilled(this.#selectedValues))
		{
			return;
		}

		this.#selectedValues.forEach((value) => this.removeValue(value));

		this.#selectedValues = [];
		this.#selectedElementList = {};
		this.#selectedHiddenElementList = {};

		this.#adjustAddButtonCompact();
	}
	// endregion

	// region DOM management
	#create(): void
	{
		if (!this.#target)
		{
			return;
		}

		if (!this.#compactMode)
		{
			this.#containerEl = Tag.render`<div class="crm-field-item-selector crm-field-item-selector__scope"></div>`;
			this.#selectedValueWrapperEl = Tag.render`<span class="crm-field-item-selector__values"></span>`;

			Dom.append(this.#selectedValueWrapperEl, this.#containerEl);
		}

		if (!this.#readonlyMode)
		{
			if (this.#compactMode)
			{
				this.#addButtonCompact = Tag.render`
					<span 
						class="crm-field-item-selector-compact-icon ${Type.isStringFilled(this.#icon) ? `--${this.#icon}` : ''}"
					></span>
				`;

				this.#adjustAddButtonCompact();
			}
			else
			{
				this.#addButton = new ItemSelectorButton();

				Dom.append(this.#getAddButtonEl(), this.#containerEl);
			}
		}

		if (this.#compactMode)
		{
			Dom.append(this.#getAddButtonEl(), this.#target);
		}
		else
		{
			Dom.append(this.#containerEl, this.#target);
		}
	}

	#adjustAddButtonCompact(): void
	{
		if (!this.#compactMode)
		{
			return;
		}

		if (Type.isArrayFilled(this.#selectedValues))
		{
			Dom.removeClass(this.#addButtonCompact, '--empty');
		}
		else
		{
			Dom.addClass(this.#addButtonCompact, '--empty');
		}
	}

	#getAddButtonEl(): ?HTMLElement
	{
		return (
			this.#compactMode
				? this.#addButtonCompact
				: this.#addButton?.getContainer()
		);
	}

	#animateAdd(element: HTMLElement): void
	{
		Dom.removeClass(element, ['--hidden', '--removing']);
		Dom.addClass(element, '--adding');
	}

	#animateRemove(element: HTMLElement): void
	{
		Dom.removeClass(element, '--adding');
		Dom.addClass(element, '--removing');
	}

	#applyAddButtonState(portion: number = 0): void
	{
		if (!Type.isDomNode(this.#getAddButtonEl()))
		{
			return;
		}

		const hiddenElementsCnt = Object.keys(this.#selectedHiddenElementList).length;

		if (this.#selectedValues.length === 0)
		{
			this.#addButton.applyState(ItemSelectorButtonState.ADD);
		}
		else if (this.#isTargetOverflown(portion) && hiddenElementsCnt > 0)
		{
			this.#addButton.applyState(ItemSelectorButtonState.COUNTER_ADD, hiddenElementsCnt);
		}
		else
		{
			this.#addButton.applyState(ItemSelectorButtonState.MORE_ADD);
		}
	}
	// endregion

	// region Event handlers
	#bindEvents(): void
	{
		if (Type.isDomNode(this.#addButtonCompact))
		{
			Event.bind(this.#addButtonCompact, 'click', this.#onShowPopup.bind(this));
		}
		else if (Type.isDomNode(this.#getAddButtonEl()))
		{
			Event.bind(this.#getAddButtonEl(), 'click', this.#onShowPopup.bind(this));
		}

		if (Type.isDomNode(this.#selectedValueWrapperEl))
		{
			Event.bind(this.#selectedValueWrapperEl, 'click', this.#onRemoveValue.bind(this));
		}

		Event.unbind(window, 'resize', this.#onWindowResize);
		Event.bind(window, 'resize', this.#onWindowResize.bind(this));
	}

	#onShowPopup(event: Event): void
	{
		const menuItems = this.#getPreparedMenuItems();

		// @todo temporary, need other fix
		const angle = this.#compactMode ? { offset: 29, position: 'top' } : true;

		const menuParams = {
			closeByEsc: true,
			autoHide: true,
			offsetLeft: this.#getAddButtonEl().offsetWidth - 16,
			angle,
			cacheable: false,
		};

		this.#valuesMenuPopup = MenuManager.create(this.#id, this.#getAddButtonEl(), menuItems, menuParams);
		this.#valuesMenuPopup.show();

		EventEmitter.emit(this, Events.EVENT_ITEMSELECTOR_OPEN);
	}

	#getPreparedMenuItems(): []
	{
		return this.#valuesList.map((item: Item) => this.#getPreparedMenuItem(item));
	}

	#getPreparedMenuItem(item: Item): Object
	{
		const menuItem = {
			id: `item-selector-menu-id-${item.id}`,
			className: this.#isValueSelected(item.id) ? MENU_ITEM_CLASS_ACTIVE : MENU_ITEM_CLASS_INACTIVE,
			onclick: this.#onMenuItemClick.bind(this, item.id),
		};

		if (this.#htmlItemCallback)
		{
			menuItem.html = this.#htmlItemCallback(item);
		}
		else
		{
			menuItem.html = Text.encode(item.title);
		}

		return menuItem;
	}

	#onRemoveValue(event: Event): void
	{
		const target = event.target || event.srcElement;
		const itemIdToRemove = target.getAttribute('data-item-selector-id');
		if (Type.isNull(itemIdToRemove))
		{
			return; // nothing to do
		}

		if (this.#isValueSelected(itemIdToRemove))
		{
			this.removeValue(itemIdToRemove, true);
		}
	}

	#onMenuItemClick(value: mixed, event: PointerEvent, item: MenuItem): void
	{
		if (!this.#multiple)
		{
			this.clearAll();

			this.#valuesMenuPopup.menuItems.forEach((menuItem: MenuItem) => {
				Dom.removeClass(menuItem.getContainer(), MENU_ITEM_CLASS_ACTIVE);
			});

			this.#valuesMenuPopup.close();
		}

		if (this.#isValueSelected(value))
		{
			this.removeValue(value, true);

			Dom.removeClass(item.getContainer(), MENU_ITEM_CLASS_ACTIVE);
			Dom.addClass(item.getContainer(), MENU_ITEM_CLASS_INACTIVE);
		}
		else
		{
			this.addValue(value, true);

			Dom.removeClass(item.getContainer(), MENU_ITEM_CLASS_INACTIVE);
			Dom.addClass(item.getContainer(), MENU_ITEM_CLASS_ACTIVE);
		}
	}

	#onWindowResize(): void
	{
		this.#applyCurrentValue(750);
	}

	#emitEvent(): void
	{
		EventEmitter.emit(this, Events.EVENT_ITEMSELECTOR_VALUE_CHANGE, {
			value: this.getValue(),
		});
	}
	// endregion

	// region Utils
	#assertValidParams(params: ItemSelectorOptions): void
	{
		if (!Type.isPlainObject(params))
		{
			throw new TypeError('BX.Crm.Field.ItemSelector: The "params" argument must be object');
		}

		if (!Type.isDomNode(params.target))
		{
			throw new Error('BX.Crm.Field.ItemSelector: The "target" argument must be DOM node');
		}

		if (!Type.isArrayFilled(params.valuesList))
		{
			throw new Error('BX.Crm.Field.ItemSelector: The "valuesList" argument must be filled');
		}
	}

	#applyCurrentValue(delay: number): void
	{
		Runtime.debounce(
			() => {
				this.setValue(this.#selectedValues || []);
			},
			delay,
			this,
		)();
	}

	#isValueSelected(value: string | number): boolean
	{
		return !Type.isUndefined(
			this.#selectedValues.find((item: string | number) => item.toString() === value.toString()),
		);
	}

	#isTargetOverflown(portion: number = 0): boolean
	{
		if (this.#readonlyMode || this.#compactMode)
		{
			return false;
		}

		const targetWidth = this.#target.offsetWidth;
		const selectedValuesWidth = this.#selectedValueWrapperEl.offsetWidth;
		const addBtnWidth = this.#getAddButtonEl().offsetWidth;
		const result = targetWidth - (selectedValuesWidth + addBtnWidth + portion);

		return result <= 20;
	}
	// endregion
}
