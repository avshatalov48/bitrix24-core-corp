import { Event, Type } from 'main.core';
import { MenuManager } from 'main.popup';
import { EventEmitter } from 'main.core.events';

import './style.css';

type SimpleMenuOptions = {
	bindElement: HTMLElement,
	items: Array<string>,
	actionHandler: (selectedValue: string) => {},
	menuId?: string,
	currentValue: string
};

export class UI
{
	static simpleMenu = {};

	/**
	 * Binds Menu (simple structure) to the element.
	 * @param {SimpleMenuOptions} options
	 */
	static bindSimpleMenu(options: SimpleMenuOptions)
	{
		const { bindElement, items, actionHandler, currentValue } = options;
		const menuItems = [];
		const menuId = options.menuId || 'menu-' + Math.random();
		let currentActiveNode = null;

		if (UI.simpleMenu[menuId])
		{
			return;
		}

		// build from plain array
		if (Type.isArray(items))
		{
			items.map(item => {
				menuItems.push({
					text: item,
					className: currentValue === parseInt(item) ? 'sign-popupmenu-item --sign-popupmenu-active' : 'sign-popupmenu-item',
					onclick: (ev) => {
						actionHandler(item);
						MenuManager.getMenuById(menuId).close();
						EventEmitter.emit(BX.findParent(bindElement, {className: 'sign-document__block-wrapper'}), 'BX.Sign:setFontSize', {
							menuId: menuId,
							fontSize: parseInt(item)
						});

						let popupNode = UI.simpleMenu[menuId].getPopupWindow().getPopupContainer();

						if (popupNode)
						{
							let activeItemNode = popupNode.querySelector('.--sign-popupmenu-active');

							if (activeItemNode)
							{
								activeItemNode.classList.remove('--sign-popupmenu-active');
							}
						}
						//
						let currentNode = ev.target.closest('.sign-popupmenu-item');
						//
						if (currentNode)
						{
							currentActiveNode = currentNode;
							currentActiveNode.classList.add('--sign-popupmenu-active');
						}
					}
				});
			});
		}
		// or Object
		else
		{
			[...Object.keys(items)].map(key => {
				menuItems.push({
					html: items[key],
					onclick: () => {
						actionHandler(key);
						MenuManager.getMenuById(menuId).close();
					}
				});
			});
		}

		const documentContainer = document.body.querySelector('[data-role="sign-editor__content"]');
		const adjustMenu = () => {
			UI.simpleMenu[menuId].close()
		};

		UI.simpleMenu[menuId] = MenuManager.create({
			id: menuId,
			bindElement: bindElement,
			items: menuItems,
			autoHide: true,
			offsetLeft: -35,
			events: {
				onClose: () => {
					if (documentContainer)
					{
						Event.unbind(documentContainer, 'scroll', adjustMenu);
					}

					EventEmitter.unsubscribe('BX.Sign:resizeStart', adjustMenu);
					EventEmitter.unsubscribe('BX.Sign:moveStart', adjustMenu);
				},
				onShow: () => {
					if (documentContainer)
					{
						Event.bind(documentContainer, 'scroll', adjustMenu);
					}

					EventEmitter.subscribe('BX.Sign:moveStart', adjustMenu);
					EventEmitter.subscribe('BX.Sign:resizeStart', adjustMenu);
				},
				onPopupShow: () => {
					if (!currentActiveNode)
					{
						let popupNode = UI.simpleMenu[menuId].getPopupWindow().getPopupContainer();
						currentActiveNode = popupNode.querySelector('.--sign-popupmenu-active');
					}

					if (currentActiveNode)
					{
						let menuNode =  UI.simpleMenu[menuId].getPopupWindow().getContentContainer();
						let menuHeight = menuNode.offsetHeight;
						let scrollOffset = currentActiveNode.offsetTop;
						scrollOffset = scrollOffset + (currentActiveNode.offsetHeight / 2) - (menuHeight / 2);
						menuNode.scrollTop = scrollOffset;
					}
				}
			}
		});

		Event.bind(bindElement, 'click', () => {
			const pos = bindElement.getBoundingClientRect();
			const fixHeight = 233;
			const maxHeight = window.innerHeight - pos.top - (pos.height * 2);
			UI.simpleMenu[menuId].popupWindow.setMaxHeight(maxHeight < fixHeight ? maxHeight : fixHeight);
			UI.simpleMenu[menuId].show();
		});
	}
}
