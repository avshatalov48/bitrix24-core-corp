import { Dom, Type, bind, unbind } from 'main.core';
import { Menu, MenuItem } from 'main.popup';
import { BaseEvent, EventEmitter } from 'main.core.events';

type KeyboardMenuOptions = {
	menu: Menu;
	clearHighlightAfterType: boolean;
	highlightFirstItemAfterShow: boolean;
	canGoOutFromTop: boolean;
}

const highlightedMenuItemClassname: string = '--highlight';

export const KeyboardMenuEvents = Object.freeze({
	highlightMenuItem: 'highlightMenuItem',
	clearHighlight: 'clearHighlight',
});

export class KeyboardMenu extends EventEmitter
{
	#menu: Menu;
	#openMenu: Menu;
	#highlightedMenuItem: MenuItem | null = null;
	#clearHighlightAfterType: boolean = false;
	#highlightFirstItemAfterShow: boolean = false;
	#canGoOutFromTop: boolean = false;
	#keyDownHandler: Function;
	#menuItemMouseEnterHandler;
	#menuItemMouseLeaveHandler;
	#menuItemSubmenuOnShowHandler;
	#menuItemSubmenuOnCloseHandler;

	constructor(options: KeyboardMenuOptions) {
		super();

		this.setEventNamespace('AI:KeyboardMenu');

		this.#menu = options.menu;
		this.#clearHighlightAfterType = options.clearHighlightAfterType;
		this.#highlightFirstItemAfterShow = options.highlightFirstItemAfterShow;
		this.#canGoOutFromTop = options.canGoOutFromTop;

		this.#keyDownHandler = this.#handleKeyDown.bind(this);
		this.#menuItemMouseEnterHandler = this.#handleMenuItemMouseEnter.bind(this);
		this.#menuItemMouseLeaveHandler = this.#handleMenuItemMouseLeave.bind(this);
		this.#menuItemSubmenuOnShowHandler = this.#handleMenuItemSubmenuOnShow.bind(this);
		this.#menuItemSubmenuOnCloseHandler = this.#handleMenuItemSubmenuOnClose.bind(this);
		const handleMenuItemEvents = this.#handleMenuItemEvents.bind(this);

		this.#menu.getPopupWindow().subscribeFromOptions({
			onAfterShow: () => {
				this.#openMenu = this.#menu;
				Dom.addClass(this.#menu.getPopupWindow().getPopupContainer(), '--keyboard-control');

				if (this.#highlightFirstItemAfterShow)
				{
					this.highlightFirstItem();
					bind(document, 'keydown', this.#keyDownHandler);
				}
			},
			onAfterClose: () => {
				unbind(document, 'keydown', this.#keyDownHandler);
				this.#clearHighlight();
			},
			onPopupFirstShow: () => {
				this.#menu.getMenuItems().forEach((menuItem) => {
					handleMenuItemEvents(menuItem);
				});

				this.#observeMenuChanges();
			},
		});
	}

	enableArrows(): void
	{
		bind(document, 'keydown', this.#keyDownHandler);
	}

	disableArrows(): void
	{
		unbind(document, 'keydown', this.#keyDownHandler);
	}

	getMenu(): Menu
	{
		return this.#menu;
	}

	highlightFirstItem(): void
	{
		const firstNotDelimiterItem = this.#menu.getMenuItems().find((menuItem) => {
			return menuItem.delimiter !== true;
		});

		this.#highlightMenuItem(firstNotDelimiterItem);
	}

	#observeMenuChanges(): void
	{
		const observer = new MutationObserver((mutationsList) => {
			mutationsList.some((mutation): boolean => {
				if (mutation.type === 'childList')
				{
					this.highlightFirstItem();
					this.#menu.getMenuItems().forEach((menuItem) => {
						this.#unsubscribeMenuItemEvents(menuItem);
						this.#handleMenuItemEvents(menuItem);
					});

					return true;
				}

				return false;
			});
		});

		const config = { childList: true, subtree: true };
		observer.observe(this.#menu.getMenuContainer(), config);
	}

	// eslint-disable-next-line consistent-return
	#handleKeyDown(e: KeyboardEvent): void
	{
		if (this.#menu?.getPopupWindow()?.isShown() && this.#menu.getMenuItems().length > 0)
		{
			switch (e.key)
			{
				case 'Enter': return this.#handleEnterKey();
				case 'ArrowUp': return this.#handleArrowUpKey(e);
				case 'ArrowDown': return this.#handleArrowDownKey(e);
				case 'ArrowRight': return this.#handleArrowRightKey(e);
				case 'ArrowLeft': return this.#handleArrowLeftKey(e);
				default:
					if (this.#clearHighlightAfterType)
					{
						this.#clearHighlight(true);
					}
			}
		}
	}

	#handleEnterKey(): void
	{
		if (this.#highlightedMenuItem && this.#highlightedMenuItem.href)
		{
			this.#highlightedMenuItem.getContainer().click();
		}
		else if (this.#highlightedMenuItem && Type.isFunction(this.#highlightedMenuItem.onclick))
		{
			this.#highlightedMenuItem.onclick(null, this.#highlightedMenuItem);
		}
	}

	#handleArrowUpKey(event: KeyboardEvent): void
	{
		event.preventDefault();

		this.#highlightedMenuItem?.closeSubMenu();
		const prevMenuItem = this.#getMenuItemBeforeHighlighted();
		if (
			this.#canGoOutFromTop
			&& prevMenuItem === null
			&& this.#highlightedMenuItem?.getMenuWindow().getParentMenuItem() === null
		)
		{
			this.#clearHighlight();

			return;
		}

		this.#highlightMenuItem(prevMenuItem);
	}

	#handleArrowDownKey(event: KeyboardEvent): void
	{
		event.preventDefault();
		this.#highlightedMenuItem?.closeSubMenu();
		const nextMenuItem = this.#getMenuItemAfterHighlighted();
		this.#highlightMenuItem(nextMenuItem);
	}

	#handleArrowLeftKey(event: KeyboardEvent): void
	{
		event.preventDefault();

		const parentMenuItem = this.#highlightedMenuItem.getMenuWindow().getParentMenuItem();

		if (parentMenuItem)
		{
			this.#highlightMenuItem(parentMenuItem);
			parentMenuItem.closeSubMenu();
		}
	}

	#handleArrowRightKey(event: KeyboardEvent): void
	{
		event.preventDefault();
		this.#showActiveItemSubmenuIfExist();
	}

	#showActiveItemSubmenuIfExist(): void
	{
		if (this.#highlightedMenuItem?.hasSubMenu())
		{
			this.#highlightedMenuItem.showSubMenu();
			const subMenu = this.#highlightedMenuItem.getSubMenu();
			this.#highlightMenuItem(subMenu.getMenuItems()[0]);
		}
	}

	#handleMenuItemEvents(menuItem: MenuItem): void
	{
		menuItem.subscribe('onMouseEnter', this.#menuItemMouseEnterHandler);
		menuItem.subscribe('onMouseLeave', this.#menuItemMouseLeaveHandler);
		menuItem.subscribe('SubMenu:onShow', this.#menuItemSubmenuOnShowHandler);
		menuItem.subscribe('SubMenu:onClose', this.#menuItemSubmenuOnCloseHandler);
	}

	#unsubscribeMenuItemEvents(menuItem: MenuItem): void
	{
		menuItem.unsubscribe('onMouseEnter', this.#menuItemMouseEnterHandler);
		menuItem.unsubscribe('onMouseLeave', this.#menuItemMouseLeaveHandler);
		menuItem.unsubscribe('SubMenu:onShow', this.#menuItemSubmenuOnShowHandler);
		menuItem.unsubscribe('SubMenu:onClose', this.#menuItemSubmenuOnCloseHandler);
	}

	#handleMenuItemMouseEnter(event: BaseEvent): void
	{
		this.#highlightMenuItem(event.getTarget());
		bind(document, 'keydown', this.#keyDownHandler);
	}

	#handleMenuItemMouseLeave(): void
	{
		this.#clearHighlight();
	}

	#handleMenuItemSubmenuOnShow(event: BaseEvent): void
	{
		const eventMenuItem: MenuItem = event.getTarget();

		this.#openMenu = eventMenuItem.getSubMenu();

		Dom.addClass(this.#openMenu.getPopupWindow().getPopupContainer(), '--keyboard-control');

		eventMenuItem.getSubMenu().getMenuItems().forEach((subMenuItem) => {
			this.#handleMenuItemEvents(subMenuItem);
		});
	}

	#handleMenuItemSubmenuOnClose(event: BaseEvent): void
	{
		const eventMenuItem: MenuItem = event.getTarget();

		this.#openMenu = eventMenuItem.getMenuWindow();
	}

	#highlightMenuItem(menuItem: MenuItem | null): void
	{
		if (!menuItem)
		{
			return;
		}

		Dom.removeClass(this.#highlightedMenuItem?.getContainer(), highlightedMenuItemClassname);
		Dom.addClass(menuItem?.getContainer(), highlightedMenuItemClassname);
		this.#highlightedMenuItem = menuItem;
		this.#scrollToActiveElem();

		this.emit(KeyboardMenuEvents.highlightMenuItem);
	}

	#getMenuItemBeforeHighlighted(): MenuItem | null
	{
		if (this.#highlightedMenuItem === null)
		{
			return this.#openMenu.getMenuItems()[0];
		}

		const highlightedMenuItemPosition = this.#getHighlightedMenuItemPosition();
		const menuItems = this.#highlightedMenuItem.getMenuWindow().getMenuItems();
		if (menuItems[highlightedMenuItemPosition - 1]?.delimiter)
		{
			return menuItems[highlightedMenuItemPosition - 2] || null;
		}

		return menuItems[highlightedMenuItemPosition - 1] || null;
	}

	#getMenuItemAfterHighlighted(): MenuItem
	{
		if (this.#highlightedMenuItem === null)
		{
			return this.#openMenu?.getMenuItems()[0] || null;
		}

		const highlightedMenuItemPosition = this.#getHighlightedMenuItemPosition();
		const menuItems = this.#highlightedMenuItem?.getMenuWindow()?.getMenuItems();
		if (menuItems[highlightedMenuItemPosition + 1]?.delimiter)
		{
			return menuItems[highlightedMenuItemPosition + 2] || null;
		}

		return menuItems[highlightedMenuItemPosition + 1] || null;
	}

	#getHighlightedMenuItemPosition(): number
	{
		const menu = this.#highlightedMenuItem.getMenuWindow();

		return menu.getMenuItemPosition(this.#highlightedMenuItem.getId());
	}

	#clearMenuItemHighlight(menuItem: MenuItem): void
	{
		Dom.removeClass(menuItem?.getContainer(), highlightedMenuItemClassname);
	}

	#clearHighlight(closeSubmenu: boolean = false): void
	{
		if (!this.#highlightedMenuItem)
		{
			return;
		}

		this.#clearMenuItemHighlight(this.#highlightedMenuItem);

		if (closeSubmenu && this.#highlightedMenuItem.getMenuWindow().getParentMenuItem())
		{
			this.#highlightedMenuItem.getMenuWindow().getParentMenuItem().closeSubMenu();
		}

		this.#highlightedMenuItem = null;
		this.emit(KeyboardMenuEvents.clearHighlight);
	}

	#scrollToActiveElem(): boolean
	{
		const menuItem = this.#highlightedMenuItem;
		const menuWrapper: HTMLElement = this.#highlightedMenuItem.getMenuWindow().getPopupWindow().getContentContainer();

		const relativePosition = Dom.getRelativePosition(menuWrapper, menuItem.getContainer());

		if (-relativePosition.y < 0)
		{
			menuWrapper.scrollTop -= menuWrapper.offsetHeight;
		}
		else if (-relativePosition.y + 10 > relativePosition.height)
		{
			menuWrapper.scrollTop += menuWrapper.offsetHeight;
		}
	}
}

type addKeyboardControlToMenuOptions = {
	clearHighlightAfterType: boolean;
	canGoOutFromTop: boolean;
}

// eslint-disable-next-line max-lines-per-function
export function addKeyboardControlToMenu(menu: Menu, options: addKeyboardControlToMenuOptions): void
{
	let openMenu: Menu = menu;
	let highlightedMenuItem: MenuItem | null = null;
	const clearHighlightAfterType = options?.clearHighlightAfterType === true;
	const canGoOutFromTop = options?.canGoOutFromTop === true;

	Dom.addClass(menu.getPopupWindow().getPopupContainer(), '--keyboard-control');

	menu.getPopupWindow().subscribeFromOptions({
		onAfterShow: () => {
			bind(document, 'keydown', handleArrowKeys);
			bind(document, 'keyup', handleEnterKey);

			highlightFirstItem();
		},
		onAfterClose: () => {
			unbind(document, 'keydown', handleArrowKeys);
			unbind(document, 'keyup', handleEnterKey);
		},
		onPopupFirstShow: () => {
			menu.getMenuItems().forEach((menuItem) => {
				handleMenuItemEvents(menuItem);
			});

			observeMenuChanges();
		},
	});

	const observeMenuChanges = () => {
		const observer = new MutationObserver((mutationsList) => {
			mutationsList.some((mutation): boolean => {
				if (mutation.type === 'childList')
				{
					highlightFirstItem();
					menu.getMenuItems().forEach((menuItem) => {
						unsubscribeMenuItemEvents(menuItem);
						handleMenuItemEvents(menuItem);
					});

					return true;
				}

				return false;
			});
		});

		const config = { childList: true, subtree: true };
		observer.observe(menu.getMenuContainer(), config);
	};

	const highlightFirstItem = (): void => {
		const firstNotDelimiterItem = menu.getMenuItems().find((menuItem) => {
			return menuItem.delimiter !== true;
		});

		highlightMenuItem(firstNotDelimiterItem);
	};

	const handleArrowKeys = (e: KeyboardEvent): void => {
		if (openMenu.getPopupWindow().isShown() && openMenu.getMenuItems().length > 0)
		{
			switch (e.key)
			{
				case 'ArrowUp': return handleArrowUpKey();
				case 'ArrowDown': return handleArrowDownKey();
				case 'ArrowRight': return handleArrowRightKey();
				case 'ArrowLeft': return handleArrowLeftKey();
				case 'Enter': return undefined;
				default:
				{
					if (clearHighlightAfterType)
					{
						clearHighlight();
					}
				}
			}
		}

		return undefined;
	};

	const handleEnterKey = (e: KeyboardEvent): void => {
		if (e.key === 'Enter' && highlightedMenuItem && Type.isFunction(highlightedMenuItem.onclick))
		{
			const container: HTMLElement = highlightedMenuItem.getContainer();

			container.click();
			e.stopPropagation();
			e.stopImmediatePropagation();
			e.preventDefault();
		}
	};

	const handleArrowUpKey = (): void => {
		highlightedMenuItem?.closeSubMenu();
		const prevMenuItem = getMenuItemBeforeHighlighted();
		if (
			canGoOutFromTop
			&& prevMenuItem === null
			&& highlightedMenuItem?.getMenuWindow().getParentMenuItem() === null
		)
		{
			clearHighlight();

			return;
		}

		highlightMenuItem(prevMenuItem);
	};

	const handleArrowDownKey = (): void => {
		highlightedMenuItem?.closeSubMenu();
		const nextMenuItem = getMenuItemAfterHighlighted();
		highlightMenuItem(nextMenuItem);
	};

	const handleArrowLeftKey = (): void => {
		const parentMenuItem = highlightedMenuItem?.getMenuWindow().getParentMenuItem();

		if (parentMenuItem)
		{
			highlightMenuItem(parentMenuItem);
			parentMenuItem.closeSubMenu();
		}
	};

	const handleArrowRightKey = (): void => {
		showActiveItemSubmenuIfExist();
	};

	const showActiveItemSubmenuIfExist = (): void => {
		if (highlightedMenuItem?.hasSubMenu())
		{
			highlightedMenuItem.showSubMenu();
			const subMenu = highlightedMenuItem.getSubMenu();
			highlightMenuItem(subMenu.getMenuItems()[0]);
		}
	};

	const handleMenuItemEvents = (menuItem: MenuItem): void => {
		menuItem.subscribe('onMouseEnter', handleMenuItemMouseEnter);
		menuItem.subscribe('onMouseLeave', handleMenuItemMouseLeave);
		menuItem.subscribe('SubMenu:onShow', handleMenuItemSubmenuOnShow);
		menuItem.subscribe('SubMenu:onClose', handleMenuItemSubmenuOnClose);
	};

	const unsubscribeMenuItemEvents = (menuItem: MenuItem): void => {
		menuItem.unsubscribe('onMouseEnter', handleMenuItemMouseEnter);
		menuItem.unsubscribe('onMouseLeave', handleMenuItemMouseLeave);
		menuItem.unsubscribe('SubMenu:onShow', handleMenuItemSubmenuOnShow);
		menuItem.unsubscribe('SubMenu:onClose', handleMenuItemSubmenuOnClose);
	};

	const handleMenuItemMouseEnter = (event: BaseEvent): void => {
		highlightMenuItem(event.getTarget());
	};

	const handleMenuItemMouseLeave = (event: BaseEvent): void => {
		highlightedMenuItem = null;
		clearMenuItemHighlight(event.getTarget());
	};

	const handleMenuItemSubmenuOnShow = (event: BaseEvent): void => {
		const eventMenuItem: MenuItem = event.getTarget();

		openMenu = eventMenuItem.getSubMenu();

		Dom.addClass(openMenu.getPopupWindow().getPopupContainer(), '--keyboard-control');

		eventMenuItem.getSubMenu().getMenuItems().forEach((subMenuItem) => {
			handleMenuItemEvents(subMenuItem);
		});
	};

	const handleMenuItemSubmenuOnClose = (event: BaseEvent): void => {
		const eventMenuItem: MenuItem = event.getTarget();

		openMenu = eventMenuItem.getMenuWindow();
	};

	const highlightMenuItem = (menuItem: MenuItem | null): void => {
		if (!menuItem)
		{
			return;
		}

		Dom.removeClass(highlightedMenuItem?.getContainer(), highlightedMenuItemClassname);
		Dom.addClass(menuItem?.getContainer(), highlightedMenuItemClassname);
		highlightedMenuItem = menuItem;
		scrollToActiveElem();
	};

	const getMenuItemBeforeHighlighted = (): MenuItem | null => {
		if (highlightedMenuItem === null)
		{
			return null;
		}

		const highlightedMenuItemPosition = getHighlightedMenuItemPosition();
		const menuItems = highlightedMenuItem.getMenuWindow().getMenuItems();
		if (menuItems[highlightedMenuItemPosition - 1]?.delimiter)
		{
			return menuItems[highlightedMenuItemPosition - 2] || null;
		}

		return menuItems[highlightedMenuItemPosition - 1] || null;
	};

	const getMenuItemAfterHighlighted = (): MenuItem => {
		if (highlightedMenuItem === null)
		{
			return openMenu.getMenuItems()[0];
		}

		const highlightedMenuItemPosition = getHighlightedMenuItemPosition();
		const menuItems = highlightedMenuItem.getMenuWindow().getMenuItems();
		if (menuItems[highlightedMenuItemPosition + 1]?.delimiter)
		{
			return menuItems[highlightedMenuItemPosition + 2] || null;
		}

		return menuItems[highlightedMenuItemPosition + 1] || null;
	};

	const getHighlightedMenuItemPosition = (): number => {
		const currentMenu = highlightedMenuItem.getMenuWindow();

		return currentMenu.getMenuItemPosition(highlightedMenuItem.getId());
	};

	const clearMenuItemHighlight = (menuItem: MenuItem): void => {
		Dom.removeClass(menuItem?.getContainer(), highlightedMenuItemClassname);
	};

	const clearHighlight = (): void => {
		if (!highlightedMenuItem)
		{
			return;
		}
		clearMenuItemHighlight(highlightedMenuItem);

		if (highlightedMenuItem.getMenuWindow().getParentMenuItem())
		{
			highlightedMenuItem.getMenuWindow().getParentMenuItem().closeSubMenu();
		}

		highlightedMenuItem = null;
	};

	const scrollToActiveElem = (): boolean => {
		const menuItem = highlightedMenuItem;
		const menuWrapper: HTMLElement = highlightedMenuItem.getMenuWindow().getPopupWindow().getContentContainer();

		const relativePosition = Dom.getRelativePosition(menuWrapper, menuItem.getContainer());

		if (-relativePosition.y < 0)
		{
			menuWrapper.scrollTop -= menuWrapper.offsetHeight;
		}
		else if (-relativePosition.y + 10 > relativePosition.height)
		{
			menuWrapper.scrollTop += menuWrapper.offsetHeight;
		}
	};
}
