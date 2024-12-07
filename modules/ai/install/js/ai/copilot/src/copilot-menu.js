import { Tag, Type, Text, Dom } from 'main.core';
import { EventEmitter, BaseEvent } from 'main.core.events';
import { Menu, MenuItem, Popup } from 'main.popup';
import type { MenuItemOptions } from 'main.popup';
import { Icon, Main } from 'ui.icon-set.api.core';
import { KeyboardMenu, KeyboardMenuEvents } from './keyboard-menu';
import 'ui.icon-set.actions';
import 'ui.icon-set.main';
import 'ui.icon-set.editor';

import { CopilotCommands } from './copilot-commands';

const systemCommands = new Set([
	CopilotCommands.cancel,
	CopilotCommands.repeat,
	CopilotCommands.save,
	CopilotCommands.addBelow,
	CopilotCommands.edit,
	CopilotCommands.close,
	CopilotCommands.copy,
]);

type CopilotMenuOptions = {
	items: CopilotMenuItem[];
	bindElement: Element,
	offsetTop?: number;
	offsetLeft?: number;
	cacheable?: boolean;
	keyboardControlOptions: CopilotMenuKeyboardControlOptions;
	forceTop: boolean;
}

type CopilotMenuKeyboardControlOptions = {
	highlightFirstItemAfterShow: boolean;
	canGoOutFromTop: boolean;
	clearHighlightAfterType: boolean;
}

export type CopilotMenuSelectEventData = {
	command: string;
}

export const CopilotMenuEvents = Object.freeze({
	select: 'select',
	open: 'open',
	close: 'close',
	clearHighlight: 'clearHighlight',
	highlightMenuItem: 'highlightMenuItem',
});

export type CopilotMenuItem = CopilotMenuItemAbility | CopilotMenuItemDelimiter;

type CopilotMenuItemAbility = {
	code: string;
	text: string;
	icon: string;
	section?: string;
	children?: CopilotMenuItem[];
	selected?: boolean;
	arrow?: boolean;
	href?: string;
	notHighlight: boolean;
	disabled: boolean;
}

type CopilotMenuItemDelimiter = {
	separator: boolean;
	title?: string;
	section?: string;
}

export class CopilotMenu extends EventEmitter
{
	#keyboardMenu: KeyboardMenu;
	#menuItems: CopilotMenuItem[];
	#filter: string;
	#cacheable: boolean;
	#keyboardControlOptions: CopilotMenuKeyboardControlOptions;
	#forceTop: boolean = true;

	constructor(options: CopilotMenuOptions)
	{
		super(options);

		this.setEventNamespace('AI.Copilot.Menu');

		this.#menuItems = options.items;
		this.#cacheable = options.cacheable ?? true;
		this.#forceTop = options.forceTop === undefined ? this.#forceTop : options.forceTop === true;

		if (options.keyboardControlOptions)
		{
			this.#keyboardControlOptions = options.keyboardControlOptions;
		}
		else
		{
			this.#keyboardControlOptions = {
				canGoOutFromTop: true,
				highlightFirstItemAfterShow: false,
				clearHighlightAfterType: false,
			};
		}
	}

	open(): void
	{
		this.#getMenu().show();
		this.adjustPosition();
		this.emit(CopilotMenuEvents.open);
	}

	show(): void
	{
		Dom.style(this.getPopup()?.getPopupContainer(), 'border', null);
		this.getPopup()?.setMaxWidth(null);
		this.getPopup()?.setMinWidth(258);
		this.adjustPosition();
	}

	close(): void
	{
		this.#getMenu().close();
		this.#closeAllSubmenus();
		this.emit(CopilotMenuEvents.close);
	}

	hide(): void
	{
		Dom.style(this.getPopup()?.getPopupContainer(), 'border', 'none');
		this.getPopup()?.setMaxWidth(0);
		this.getPopup()?.setMinWidth(0);
		this.adjustPosition();
		this.#closeAllSubmenus();
	}

	contains(target: HTMLElement): boolean
	{
		for (const menuItem of this.#getMenu().getMenuItems())
		{
			const itemPopup = menuItem.getSubMenu()?.getPopupWindow();
			if (itemPopup?.getPopupContainer()?.contains(target))
			{
				return true;
			}
		}

		return this.getPopup().getPopupContainer().contains(target);
	}

	isShown(): boolean
	{
		return this.#getMenu().getPopupWindow().isShown();
	}

	setFilter(filter: string)
	{
		return;

		// eslint-disable-next-line no-unreachable
		this.#filter = Type.isString(filter) ? filter : '';

		if (this.#getMenu())
		{
			this.#closeAllSubmenus();
			this.#filterMenuItems();
		}
	}

	setBindElement(bindElement: HTMLElement, offset: { left: number, top: number})
	{
		this.#getMenu().getPopupWindow().setBindElement(bindElement);
		this.#getMenu().getPopupWindow().setOffset({
			offsetLeft: offset?.left,
			offsetTop: offset?.top,
		});
		this.#getMenu().getPopupWindow().adjustPosition();
	}

	getPopup(): Popup
	{
		return this.#getMenu().getPopupWindow();
	}

	adjustPosition()
	{
		this.#getMenu().getPopupWindow().adjustPosition({
			forceBindPosition: true,
			forceTop: this.#forceTop,
		});
	}

	replaceMenuItemSubmenu(newCopilotMenuItem: CopilotMenuItem): void
	{
		const menuItem: MenuItem = this.#getMenu().getMenuItems().find((currentMenuItem: MenuItem) => {
			return newCopilotMenuItem.code === currentMenuItem.getId();
		});

		menuItem.destroySubMenu();
		// eslint-disable-next-line no-underscore-dangle,@bitrix24/bitrix24-rules/no-pseudo-private
		menuItem._items = this.#getMenuItems(newCopilotMenuItem.children);
		menuItem.addSubMenu(this.#getMenuItems(newCopilotMenuItem.children));
	}

	enableArrowsKey(): void
	{
		this.#keyboardMenu?.enableArrows();
	}

	disableArrowsKey(): void
	{
		this.#keyboardMenu?.disableArrows();
	}

	#closeAllSubmenus(): void
	{
		this.#getMenu().getMenuItems().forEach((menuItem) => {
			menuItem.closeSubMenu();
		});
	}

	#filterMenuItems(): void
	{
		this.#getMenu().menuItems = [];
		const sortedMenuItemsWithAllDelimiters: CopilotMenuItem[] = this.#menuItems.filter((menuItem) => {
			return menuItem.text.toLowerCase().indexOf(this.#filter ? this.#filter.toLowerCase() : '') === 0 || menuItem.separator;
		});

		const sortedMenuItems = sortedMenuItemsWithAllDelimiters.filter((menuItem, index, arr) => {
			return !menuItem.separator || (arr[index + 1] && !arr[index + 1].separator);
		});

		this.#getMenu().layout.itemsContainer.innerHTML = '';
		if (sortedMenuItems.length === 0)
		{
			Dom.style(this.#getMenu().layout.menuContainer, 'padding', 0);
			Dom.style(this.#getMenu().getPopupWindow().getPopupContainer(), 'border', 0);
		}
		else
		{
			Dom.style(this.#getMenu().getPopupWindow().getPopupContainer(), 'border', null);
			Dom.style(this.#getMenu().layout.menuContainer, 'padding', null);
		}

		this.#getMenuItems(sortedMenuItems).forEach((menuItem) => {
			this.#getMenu().addMenuItem(menuItem);
		});
	}

	#getMenu(): Menu
	{
		if (!this.#keyboardMenu)
		{
			this.#initKeyboardMenu();
		}

		return this.#keyboardMenu.getMenu();
	}

	#initKeyboardMenu(): void
	{
		const menu = new Menu({
			minWidth: 258,
			maxHeight: 372,
			closeByEsc: false,
			closeIcon: false,
			items: this.#getMenuItems(this.#menuItems),
			toFrontOnShow: true,
			autoHide: false,
			className: 'ai__copilot-scope ai__copilot-menu-popup',
			cacheable: this.#cacheable,
			events: {
				onPopupClose: (popup: Popup) => {
					Dom.style(popup.getPopupContainer(), 'border', 'none');
				},
				onPopupAfterClose: (popup: Popup) => {
					Dom.style(popup.getPopupContainer(), 'border', null);
				},
				onPopupShow: () => {
					if (this.#forceTop && this.#isEntireMenuVisible() === false)
					{
						this.#scrollForMenuVisibility();
					}
				},
			},
		});

		const keyBoardMenu = new KeyboardMenu({
			menu,
			...this.#keyboardControlOptions,
		});

		keyBoardMenu.subscribe(KeyboardMenuEvents.clearHighlight, () => {
			this.emit(CopilotMenuEvents.clearHighlight);
		});

		keyBoardMenu.subscribe(KeyboardMenuEvents.highlightMenuItem, () => {
			this.emit(CopilotMenuEvents.highlightMenuItem);
		});

		this.#keyboardMenu = keyBoardMenu;
	}

	#isEntireMenuVisible(): boolean
	{
		const popupContainer: HTMLElement = this.#getMenu().getPopupWindow().getPopupContainer();
		const popupContainerBottom = popupContainer.getBoundingClientRect().bottom;

		return popupContainerBottom < window.innerHeight;
	}

	#scrollForMenuVisibility(): void
	{
		const popupContainer: HTMLElement = this.#getMenu().getPopupWindow().getPopupContainer();
		const popupContainerPosition = Dom.getPosition(popupContainer);

		window.scrollTo({
			top: window.scrollY + popupContainer.offsetHeight,
			behavior: 'smooth',
		});

		if (popupContainerPosition.bottom > document.body.scrollHeight)
		{
			Dom.style(document.body, 'min-height', `${popupContainerPosition.bottom}px`);
		}
	}

	#getMenuItems(items?: CopilotMenuItem[], isSubmenu: boolean = false): MenuItemOptions[]
	{
		if (!items)
		{
			return [];
		}

		return items.map((item): MenuItemOptions => {
			return this.#getMenuItem(item, isSubmenu);
		});
	}

	#getMenuItem(item: CopilotMenuItem, isSubmenuItem: boolean): MenuItemOptions
	{
		return this.#isSeparatorMenuItem(item)
			? this.#getSectionSeparatorMenuItem(item)
			: this.#getAbilityMenuItem(item, isSubmenuItem);
	}

	#isSeparatorMenuItem(menuItem: CopilotMenuItem): boolean
	{
		return menuItem.separator;
	}

	#getAbilityMenuItem(item: CopilotMenuItemAbility, isSubmenuItem: boolean = false): MenuItemOptions
	{
		const iconElem = this.#renderAbilityMenuItemIcon(item);
		const checkIcon = this.#getCheckIcon();
		const menuIcon: HTMLElement | null = item.icon ? Tag.render`<div class="ai__copilot-menu_item-icon">${iconElem}</div>` : null;

		const html = Tag.render`
			<div class="${this.#getMenuItemClassname(item, isSubmenuItem)}">
				<div class="ai__copilot-menu_item-left">
					${menuIcon}
					<div class="ai__copilot-menu_item-text">${Text.encode(item.text)}</div>
				</div>
				<div class="ai__copilot-menu_item-check">
					${item.selected ? checkIcon.render() : ''}
				</div>
			</div>
		`;

		return {
			html,
			id: item.code || '',
			text: item.text,
			href: item.href,
			className: `menu-popup-no-icon ${item.arrow ? 'menu-popup-item-submenu' : ''}`,
			onclick: this.#handleMenuItemClick.bind(this),
			items: this.#getMenuItems(item.children, true),
			cacheable: false,
			disabled: item.disabled,
		};
	}

	#renderAbilityMenuItemIcon(item: CopilotMenuItemAbility): HTMLElement | null
	{
		let iconElem = null;
		if (item.icon)
		{
			try
			{
				const icon = new Icon({
					size: 24,
					icon: item.icon || undefined,
				});

				iconElem = icon.render();
			}
			catch
			{
				iconElem = null;
			}
		}

		return iconElem;
	}

	#getCheckIcon(): Icon
	{
		const checkIconColor = getComputedStyle(document.body).getPropertyValue('--ui-color-link-primary-base');

		return new Icon({
			icon: Main.CHECK,
			size: 18,
			color: checkIconColor,
		});
	}

	#getMenuItemClassname(item: CopilotMenuItemAbility, isSubMenuItem: boolean): string
	{
		let classNames = ['ai__copilot-menu_item'];

		if (isSubMenuItem)
		{
			classNames = [...classNames, '--no-icon'];
		}

		if (systemCommands.has(item.code) || item.notHighlight)
		{
			classNames = [...classNames, '--system'];
		}

		if (item.selected)
		{
			classNames = [...classNames, '--selected'];
		}

		return classNames.join(' ');
	}

	#handleMenuItemClick(event, menuItem: MenuItem)
	{
		if (menuItem?.hasSubMenu())
		{
			return;
		}

		menuItem.getMenuWindow()?.getParentMenuItem()?.closeSubMenu();

		if (menuItem.href)
		{
			return;
		}

		this.emit(CopilotMenuEvents.select, new BaseEvent({
			data: {
				command: menuItem.getId(),
			},
		}));
	}

	#getSectionSeparatorMenuItem(item: CopilotMenuItemDelimiter): MenuItemOptions
	{
		return {
			id: item.title || '',
			text: item.title,
			title: item.title,
			delimiter: true,
		};
	}
}
