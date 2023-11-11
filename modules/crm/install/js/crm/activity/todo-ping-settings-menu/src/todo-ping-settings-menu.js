import { Dom, Loc, Type } from 'main.core';
import { MenuItem } from 'main.popup';

import 'ui.hint';

declare type TodoPingSettingsMenuParams = {
	entityTypeId: number,
	settings: {
		optionName: string,
		offsetList: Array,
		currentOffsets: Array,
	}
}

const MENU_ITEM_CLASS_ACTIVE = 'menu-popup-item-accept';
const MENU_ITEM_CLASS_INACTIVE = 'menu-popup-item-none';
const SAVE_OFFSETS_REQUEST_DELAY = 750;

export class TodoPingSettingsMenu
{
	#entityTypeId: number = null;
	#settings: Object = null;
	#selectedOffsets: ?Array = null;
	#isLoadingMenuItem: Boolean = false;

	constructor(params: TodoPingSettingsMenuParams)
	{
		this.#entityTypeId = params.entityTypeId;
		this.#settings = params.settings;
		if (!Type.isStringFilled(this.#settings.optionName))
		{
			throw 'Option name are not defined.';
		}

		this.#selectedOffsets = this.#settings.currentOffsets || [];
		if (!Type.isArrayFilled(this.#settings.currentOffsets))
		{
			throw 'Offsets are not defined.';
		}

	}

	setSelectedValues(values: Array): void
	{
		this.#selectedOffsets = values;
	}

	getItems(): Array
	{
		const items = [];
		items.push({
			id: 'askForSetupTodoPing',
			text: Loc.getMessage('CRM_ACTIVITY_TODO_PING_SETTINGS_MENU_ITEM'),
			className: MENU_ITEM_CLASS_INACTIVE,
			items: this.#getPintSettingsMenuItems()
		});

		return items;
	}

	#getPintSettingsMenuItems(): Array
	{
		if (
			Type.isNull(this.#settings.offsetList)
			|| !Type.isArrayFilled(this.#settings.offsetList)
		)
		{
			return [];
		}

		const items = [];

		this.#settings.offsetList.forEach((item) => {
			items.push({
				id: item.id,
				text: item.title,
				className: this.#getMenuItemClass(item.offset),
				disabled: this.#isLoading(),
				onclick: this.#onMenuItemClick.bind(this, item.offset),
			});
		});

		return items;
	}

	#getMenuItemClass(offset: Number): string
	{
		return this.#selectedOffsets.includes(offset)
			? MENU_ITEM_CLASS_ACTIVE
			: MENU_ITEM_CLASS_INACTIVE;
	}

	#isLoading(): boolean
	{
		return this.#isLoadingMenuItem;
	}

	#onMenuItemClick(offset: Number, event: PointerEvent, item: MenuItem): void
	{
		this.#isLoadingMenuItem = true;

		if (this.#selectedOffsets.includes(offset))
		{
			if (this.#selectedOffsets.length === 1)
			{
				BX.UI.Hint.show(item.getContainer(), 'At least one item must be selected');

				return;
			}

			this.#selectedOffsets = this.#selectedOffsets.filter(value => value !== offset);

			Dom.removeClass(item.getContainer(), MENU_ITEM_CLASS_ACTIVE);
			Dom.addClass(item.getContainer(), MENU_ITEM_CLASS_INACTIVE);
		}
		else
		{
			this.#selectedOffsets.push(offset);

			Dom.removeClass(item.getContainer(), MENU_ITEM_CLASS_INACTIVE);
			Dom.addClass(item.getContainer(), MENU_ITEM_CLASS_ACTIVE);
		}

		if (this.#selectedOffsets.length === 0)
		{
			throw 'Offsets are not defined.';

			return;
		}

		setTimeout(() => {
			BX.userOptions.save('crm', this.#settings.optionName, 'offsets', this.#selectedOffsets.join(','));

			this.#isLoadingMenuItem = false;
		}, SAVE_OFFSETS_REQUEST_DELAY);
	}
}
