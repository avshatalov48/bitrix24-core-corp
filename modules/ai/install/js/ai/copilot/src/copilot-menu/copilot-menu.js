import type { Role } from 'ai.engine';
import { Tag, Type, Text, Dom, bind, Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Menu, MenuItem, Popup } from 'main.popup';
import type { MenuItemOptions } from 'main.popup';
import { Icon, Main } from 'ui.icon-set.api.core';
import { KeyboardMenu, KeyboardMenuEvents } from './keyboard-menu';
import { CopilotMenuCommand } from './index';
import 'ui.icon-set.actions';
import 'ui.icon-set.main';
import 'ui.icon-set.editor';
import 'ui.icon-set.crm';
import { Label, LabelColor, LabelSize } from 'ui.label';
import { Loader } from 'main.loader';

import './copilot-menu.css';

export type CopilotMenuOptions = {
	items: CopilotMenuItem[];
	bindElement: Element,
	offsetTop?: number;
	offsetLeft?: number;
	cacheable?: boolean;
	keyboardControlOptions: CopilotMenuKeyboardControlOptions;
	forceTop: boolean;
	autoHide: boolean;
	angle: boolean | {offset: number, position?: ("top" | "bottom" | "left" | "right")},
	bordered: boolean;
	roleInfo: CopilotMenuItemRoleInfo;
}

export type CopilotMenuItemRoleInfo = {
	role: Role;
	onclick: Function;
	subtitle: string;
}

type CopilotMenuKeyboardControlOptions = {
	highlightFirstItemAfterShow: boolean;
	canGoOutFromTop: boolean;
	clearHighlightAfterType: boolean;
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
	id: string;
	code: string;
	text: string;
	icon?: string;
	section?: string;
	children?: CopilotMenuItem[];
	selected?: boolean;
	arrow?: boolean;
	href?: string;
	notHighlight?: boolean;
	highlightText?: boolean;
	disabled?: boolean;
	command?: Function | CopilotMenuCommand;
	labelText?: string;
	isFavourite?: boolean;
	isShowFavouriteIconOnHover?: boolean;
}

type CopilotMenuItemDelimiter = {
	separator: boolean;
	title?: string;
	section?: string;
	isNew?: boolean;
}

export class CopilotMenu extends EventEmitter
{
	#keyboardMenu: KeyboardMenu;
	#menuItems: CopilotMenuItem[];
	#cacheable: boolean;
	#keyboardControlOptions: CopilotMenuKeyboardControlOptions;
	#forceTop: boolean = true;
	#autoHide: boolean = false;
	#angle: boolean | { offset: number, position?: ("top" | "bottom" | "left" | "right") };
	#bordered: boolean = true;
	#roleInfo: CopilotMenuItemRoleInfo;
	#currentRole: Role;
	#roleInfoContainer: HTMLElement;
	#loader: Loader;

	constructor(options: CopilotMenuOptions)
	{
		super(options);

		this.setEventNamespace('AI.Copilot.Menu');

		this.#menuItems = options.items;
		this.#cacheable = options.cacheable ?? true;
		this.#forceTop = options.forceTop === undefined ? this.#forceTop : options.forceTop === true;
		this.#autoHide = options.autoHide === true;
		this.#angle = options.angle;
		this.#bordered = options.bordered ?? this.#bordered;

		this.#initRoleInfoFromOptions(options.roleInfo);

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
		this.enableArrowsKey();
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
		this.disableArrowsKey();
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
		return this.#keyboardMenu?.getMenu()?.getPopupWindow()?.isShown();
	}

	setBindElement(bindElement: HTMLElement, offset: { left: number, top: number })
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
		menuItem._items = this.#getMenuItems(newCopilotMenuItem.children, true);
		menuItem.addSubMenu(this.#getMenuItems(newCopilotMenuItem.children, true));
	}

	enableArrowsKey(): void
	{
		this.#keyboardMenu?.enableArrows();
	}

	disableArrowsKey(): void
	{
		this.#keyboardMenu?.disableArrows();
	}

	markMenuItemSelected(menuItemId: string): void
	{
		const menuItem = this.#getMenu().getMenuItem(menuItemId);
		const menuItemInnerContainer = menuItem.getContainer().querySelector('.ai__copilot-menu_item');

		Dom.addClass(menuItemInnerContainer, '--selected');
	}

	unmarkMenuItemSelected(menuItemId: string): void
	{
		const menuItem = this.#getMenu().getMenuItem(menuItemId);

		const menuItemInnerContainer = menuItem.getContainer().querySelector('.ai__copilot-menu_item');
		Dom.removeClass(menuItemInnerContainer, '--selected');
	}

	updateRoleInfo(role: Role): void
	{
		this.#currentRole.avatar = role.avatar;
		this.#currentRole.name = role.name;
	}

	setItemIsFavourite(itemCode: string, isFavourite: boolean): void
	{
		const itemContainer = this.#getMenu().getMenuItem(itemCode)?.getContainer();

		if (!itemContainer)
		{
			return;
		}

		const favouriteLabelWrapper = itemContainer.querySelector('.ai__copilot-menu_item-favourite');

		if (!favouriteLabelWrapper)
		{
			return;
		}

		Dom.replace(favouriteLabelWrapper, this.#renderFavouriteLabel(itemCode, isFavourite));
	}

	insertItemBefore(itemCode: string, insertedItem: CopilotMenuItem): void
	{
		const menuItem = this.#getMenuItem(insertedItem, false);

		this.#getMenu().addMenuItem(menuItem, itemCode);
	}

	insertItemAfterRole(insertedItem: CopilotMenuItem): void
	{
		const roleItemPosition = this.#getMenu().getMenuItemPosition('role-item');
		const menuItemAfterRoleItem = this.#getMenu().getMenuItems()[roleItemPosition + 1];

		this.insertItemBefore(menuItemAfterRoleItem.getId(), insertedItem);
	}

	insertItemAfter(itemCode: string, insertedItem: CopilotMenuItem): void
	{
		const menuItemAfterTarget = this.#getMenu()
			.getMenuItems()[this.#getMenu().getMenuItemPosition(itemCode) + 1];

		this.insertItemBefore(menuItemAfterTarget.getId(), insertedItem);
	}

	removeItem(itemCode: string): void
	{
		this.#getMenu().removeMenuItem(itemCode);
	}

	setLoader(): void
	{
		const popupContainer = this.#getMenu().getPopupWindow()?.getPopupContainer();

		if (!popupContainer)
		{
			return;
		}

		const fade = Tag.render`<div class="ai__copilot-menu-popup_fade"></div>`;

		Dom.append(fade, popupContainer);

		this.#loader = new Loader({
			size: 55,
			target: popupContainer,
			color: getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary') || '#8e52ec',
		});

		this.#loader.show();
	}

	removeLoader(): void
	{
		const popupContainer = this.#getMenu().getPopupWindow()?.getPopupContainer();

		if (!popupContainer)
		{
			return;
		}

		const fade = popupContainer.querySelector('.ai__copilot-menu-popup_fade');

		Dom.remove(fade);

		this.#loader.destroy();
		this.#loader = null;
	}

	updateMenuItemsExceptRoleItem(copilotMenuItems: CopilotMenuItem[]): void
	{
		this.#removeMenuItemsExceptRoleItem();
		this.#addMenuItems(copilotMenuItems);
	}

	#removeMenuItemsExceptRoleItem(): void
	{
		const menuItems = this.#getMenu().getMenuItems();

		menuItems.forEach((currentMenuItem) => {
			const id = currentMenuItem.getId();

			if (id === 'role-item')
			{
				return;
			}

			requestAnimationFrame(() => {
				this.#getMenu().removeMenuItem(id);
			});
		});
	}

	#addMenuItems(copilotMenuItems: CopilotMenuItem[]): void
	{
		const newMenuItems = this.#getMenuItems(copilotMenuItems);

		newMenuItems.forEach((newMenuItem) => {
			requestAnimationFrame(() => {
				this.#getMenu().addMenuItem(newMenuItem);
			});
		});
	}

	#closeAllSubmenus(): void
	{
		this.#getMenu().getMenuItems().forEach((menuItem) => {
			menuItem.closeSubMenu();
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
			angle: this.#angle,
			closeByEsc: false,
			closeIcon: false,
			items: this.#getMenuItems(this.#menuItems),
			toFrontOnShow: true,
			autoHide: this.#autoHide,
			className: `ai__copilot-scope ai__copilot-menu-popup ${this.#bordered ? '--bordered' : ''}`,
			cacheable: this.#cacheable,
			events: {
				onPopupClose: (popup: Popup) => {
					this.emit(CopilotMenuEvents.close);
					Dom.style(popup.getPopupContainer(), 'border', 'none');
				},
				onPopupAfterClose: (popup: Popup) => {
					Dom.style(popup.getPopupContainer(), 'border', null);
				},
				onPopupShow: () => {
					if (this.#forceTop && this.#isMenuVisible() === false)
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

	#isMenuVisible(): boolean
	{
		const popupContainer: HTMLElement = this.#getMenu().getPopupWindow().getPopupContainer();
		const popupContainerPosition = popupContainer.getBoundingClientRect();

		return popupContainerPosition.bottom < window.innerHeight;
	}

	#scrollForMenuVisibility(): void
	{
		const popupContainer: HTMLElement = this.#getMenu().getPopupWindow().getPopupContainer();
		const popupContainerPosition = Dom.getPosition(popupContainer);

		window.scrollTo({
			top: popupContainerPosition.bottom + 20 - window.innerHeight,
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

		const menuItems = items.map((item): MenuItemOptions => {
			return this.#getMenuItem(item, isSubmenu);
		});

		if (this.#roleInfo && isSubmenu === false)
		{
			menuItems.unshift(this.#getRoleMenuItem());
		}

		return menuItems;
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

		const label = item.labelText
			? (new Label({
				text: item.labelText,
				color: LabelColor.PRIMARY,
				fill: true,
				size: LabelSize.SM,
			})).render()
			: null;

		const labelWrapper = label ? Tag.render(`<div>${label}</div>`) : null;
		const favouriteLabel = Type.isBoolean(item.isFavourite)
			? this.#renderFavouriteLabel(item.code, item.isFavourite)
			: null
		;

		const html = Tag.render`
			<div class="${this.#getMenuItemClassname(item, isSubmenuItem, item.selected)}">
				<div class="ai__copilot-menu_item-left">
					${menuIcon}
					<div class="ai__copilot-menu_item-text">${Text.encode(item.text)}</div>
				</div>
				<div class="ai__copilot-menu_item-right">
					${favouriteLabel}
					<div class="ai__copilot-menu_item-check">
						${checkIcon.render()}
					</div>
					${labelWrapper}
				</div>
			</div>
		`;

		return {
			html,
			id: item.id || '',
			text: item.text,
			href: item.href,
			className: `menu-popup-no-icon ${item.arrow ? 'menu-popup-item-submenu' : ''}`,
			onclick: this.#handleMenuItemClick(item.command).bind(this),
			items: this.#getMenuItems(item.children, true),
			cacheable: false,
			disabled: item.disabled,
		};
	}

	#renderFavouriteLabel(promptCode: string, isFavourite: boolean = false): HTMLElement
	{
		const favouriteIcon = new Icon({
			icon: Main.BOOKMARK_1,
			size: 24,
		});

		const iconWrapperClassname = `ai__copilot-menu_item-favourite ${isFavourite ? '--is-favourite' : ''}`;
		const title = isFavourite
			? Loc.getMessage('AI_COPILOT_REMOVE_PROMPT_FROM_FAVOURITE')
			: Loc.getMessage('AI_COPILOT_ADD_PROMPT_TO_FAVOURITE')
		;

		const wrapper = Tag.render`
			<div title="${title}" class="${iconWrapperClassname}">
				${favouriteIcon.render()}
			</div>
		`;

		bind(wrapper, 'click', (event: PointerEvent) => {
			event.preventDefault();
			event.stopImmediatePropagation();

			const newIsFavourite = !isFavourite;

			this.emit('set-favourite', {
				promptCode,
				isFavourite: newIsFavourite,
			});
		});

		return wrapper;
	}

	#getRoleMenuItem(): MenuItemOptions
	{
		return {
			id: 'role-item',
			html: this.#getRoleMenuItemHtml(),
			className: `menu-popup-no-icon ${this.#roleInfo.onclick ? 'menu-popup-item-submenu' : ''} --role-item`,
			onclick: this.#handleMenuItemClick(this.#roleInfo.onclick).bind(this),
		};
	}

	#getRoleMenuItemHtml(): HTMLElement
	{
		if (this.#roleInfoContainer)
		{
			return this.#roleInfoContainer;
		}

		const { name, avatar } = this.#roleInfo.role;
		const subtitle = this.#roleInfo.subtitle;

		this.#roleInfoContainer = Tag.render`
			<div class="ai__copilot-menu_item">
				<div class="ai__copilot-menu_role">
					<div class="ai__copilot-menu_role-left">
						<img class="ai__copilot-menu_role-avatar" src="${avatar.small}" alt="">
					</div>
					<div class="ai__copilot-menu_role-right">
						<span
							class="ai__copilot-menu_role-title"
							title="${name}"
						>
							${name}
						</span>
						<span class="ai__copilot-menu_role-subtitle">${subtitle}</span>
					</div>
				</div>
			</div>
		`;

		return this.#roleInfoContainer;
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

		if (item.notHighlight)
		{
			classNames = [...classNames, '--system'];
		}

		if (item.highlightText)
		{
			classNames = [...classNames, '--highlight-text'];
		}

		if (item.selected)
		{
			classNames = [...classNames, '--selected'];
		}

		if (item.isShowFavouriteIconOnHover)
		{
			classNames = [...classNames, '--favourite-icon-on-hover'];
		}

		return classNames.join(' ');
	}

	#handleMenuItemClick(command: CopilotMenuCommand): Function
	{
		return async (event, menuItem: MenuItem) => {
			if (menuItem?.hasSubMenu())
			{
				return;
			}

			menuItem.getMenuWindow()?.getParentMenuItem()?.closeSubMenu();

			if (menuItem.href)
			{
				return;
			}

			this.#showMenuItemLoader(menuItem);

			if (Type.isFunction(command))
			{
				await command(event, menuItem, this);
			}
			else
			{
				await command?.execute();
			}

			this.#destroyMenuItemLoader(menuItem);
		};
	}

	#showMenuItemLoader(menuItem: MenuItem): void
	{
		const loaderSize = 18;
		const loaderColor = getComputedStyle(document.body.querySelector('.ai__copilot-scope')).getPropertyValue('--ai__copilot_color-main');
		const loaderWrapper = Tag.render`<div class="ai__copilot-menu_item-loader"></div>`;
		const menuItemContent = menuItem.getContainer().querySelector('.ai__copilot-menu_item-right');

		Dom.addClass(menuItem.getContainer(), 'menu-popup-item-loading');
		Dom.append(loaderWrapper, menuItemContent);

		const loader = new Loader({
			size: loaderSize,
			target: loaderWrapper,
			color: loaderColor,
		});

		loader.show();
	}

	#destroyMenuItemLoader(menuItem: MenuItem): void
	{
		const loaderWrapper = menuItem.getContainer().querySelector('.ai__copilot-menu_item-loader');

		Dom.removeClass(menuItem.getContainer(), 'menu-popup-item-loading');
		Dom.remove(loaderWrapper);
	}

	#getSectionSeparatorMenuItem(item: CopilotMenuItemDelimiter): MenuItemOptions
	{
		return {
			id: item.code || item.title || '',
			text: item.title,
			title: item.title,
			delimiter: true,
			html: item.title
				? `
					<span>${item.title}</span>
					${item.isNew ? this.#renderSeparatorMenuItemNewLabel().outerHTML : ''}
				`
				: undefined,
		};
	}

	#renderSeparatorMenuItemNewLabel(): HTMLElement
	{
		return this.#renderSeparatorMenuItemLabel(Loc.getMessage('AI_COPILOT_MENU_ITEM_LABEL_NEW'));
	}

	#renderSeparatorMenuItemLabel(text: string): HTMLElement
	{
		const newLabel = new Label({
			text,
			color: Label.Color.PRIMARY,
			size: Label.Size.SM,
			fill: true,
		});

		return Tag.render`<span class="ai__copilot-menu_delimiter-label">${newLabel.render().outerHTML}</span>`;
	}

	#initRoleInfoFromOptions(roleInfoOption: CopilotMenuItemRoleInfo): void
	{
		if (roleInfoOption)
		{
			this.#roleInfo = roleInfoOption;
			this.#currentRole = new Proxy(roleInfoOption.role, {
				set: (target: Role, p: string, newValue: any): boolean => {
					if (this.#roleInfoContainer && p === 'name')
					{
						const nameContainer = this.#roleInfoContainer.querySelector('.ai__copilot-menu_role-title');

						Dom.attr(nameContainer, 'title', newValue);
						nameContainer.innerText = newValue;
					}

					if (this.#roleInfoContainer && p === 'avatar')
					{
						const avatarImg: HTMLImageElement = this.#roleInfoContainer.querySelector('.ai__copilot-menu_role-avatar');

						avatarImg.src = newValue.small;
					}

					return Reflect.set(target, p, newValue);
				},
			});
		}
	}
}
