import { Loc, Dom, Tag, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Menu, MenuItem } from 'main.popup';
import { MessageBox } from 'ui.dialogs.messagebox';

export class Item
{
	ACCEPTED_ITEM_CLASS: string = 'menu-popup-item-accept';
	DISABLED_ITEM_CLASS: string = 'menu-popup-item-disabled';

	MENU_ID: string;
	HINT_ID: string;
	TITLE: string;

	#item: MenuItem;
	#hintManager: BX.UI.Hint;

	getItem(): MenuItem | null
	{
		if (!Type.isUndefined(this.#item))
		{
			return this.#item;
		}

		if (Type.isUndefined(this.MENU_ID) || Type.isUndefined(this.HINT_ID) || Type.isUndefined(this.TITLE))
		{
			return null;
		}

		this.#item = {
			dataset: { id: this.MENU_ID },
			id: this.MENU_ID,
			html: this.#getHtml(),
			className: `menu-popup-item-none menu-popup-${this.TITLE}-deadline`,
			events: {
				onSubMenuShow: this.#onSubMenuShow.bind(this),
			},
			items: this.getSubItems(),
		};

		return this.#item;
	}

	onPromiseError(error: Object): void
	{
		const errors = error.errors;
		if (Type.isUndefined(errors))
		{
			console.error(error);

			return;
		}

		MessageBox.alert(
			Loc.getMessage('TASKS_DEADLINE_MENU_ERROR_DESCRIPTION'),
			Loc.getMessage('TASKS_DEADLINE_MENU_ERROR_TITLE'),
		);
	}

	getSubItems(): MenuItem[]
	{
		return [];
	}

	refreshIcons(subItems: MenuItem[], subMenu: Menu): void
	{}

	#getHtml(): string
	{
		const node = Tag.render`
			<div class="tasks-deadline-menu_${this.TITLE}-deadline-item__text">
				${Loc.getMessage(`TASKS_DEADLINE_MENU_${this.TITLE.toUpperCase()}_ITEM_TITLE`)}
			</div>
		`;

		this.#hintManager = BX.UI.Hint.createInstance({
			id: this.HINT_ID,
		});
		const hint = this.#hintManager.createNode(Loc.getMessage(`TASKS_DEADLINE_MENU_${this.TITLE.toUpperCase()}_ITEM_HINT`));

		Dom.append(hint, node);

		return node;
	}

	#onSubMenuShow(event: BaseEvent): void
	{
		const item = event.getTarget();

		const subMenu = item.getSubMenu();
		const subItems = subMenu.getMenuItems();

		this.refreshIcons(subItems, subMenu);
		this.#bringHintToFront(subMenu);
	}

	#bringHintToFront(subMenu: Menu): void
	{
		const subMenuComponent = subMenu.getPopupWindow().getZIndexComponent();

		const hintPopup = this.#hintManager.popup;
		if (!hintPopup)
		{
			return;
		}
		const hintComponent = hintPopup.getZIndexComponent();

		hintComponent.setSort(subMenuComponent.getSort() + 1);

		const stack = hintComponent.getStack();
		stack.sort();
	}
}
