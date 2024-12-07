import {Loc, Type, Dom, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';
import getItem from '../items/index';
import Item from '../items/item';
import ItemActive from '../items/item-active';
import { ItemMainPage } from '../items/item-main-page';
import ItemUserSelf from '../items/item-user-self';
import ItemUserFavorites from '../items/item-user-favorites';
import Options from "../options";
import DefaultController from "./default-controller";
import Utils from "../utils";
import Backend from "../backend";
import ItemAdminCustom from "../items/item-admin-custom";
import {Menu, MenuItem} from 'main.popup';
import ItemGroup from "../items/item-group";

import { DesktopApi } from 'im.v2.lib.desktop-api';

export default class ItemsController extends DefaultController{
	parentContainer: Element;
	container: Element;
	hiddenContainer: Element;
	items: Map = new Map();
	#activeItem: ItemActive = new ItemActive;
	#isEditMode = false;

	constructor(container, {events})
	{
		super(container, {events});
		this.parentContainer = container;
		this.container = container.querySelector(".menu-items");

		this.hiddenContainer = container
			.querySelector('#left-menu-hidden-items-block');

		container
			.querySelectorAll('li.menu-item-block')
			.forEach(this.registerItem.bind(this))
		;
		container
			.querySelector('#left-menu-hidden-separator')
			.addEventListener('click', this.toggleHiddenContainer.bind(this))
		;

		if (this.getActiveItem()
			&& this.getActiveItem().container
				.getAttribute('data-status') === 'hide'
		)
		{
			this.#showHiddenContainer(false);
		}
	}

	registerItem(node): Item
	{
		const itemClass = getItem(node);
		const item = new itemClass(this.container, node);
		this.items.set(item.getId(), item);

		if (!(item instanceof ItemMainPage))
		{
			this.#registerDND(item);
		}

		if (this.#activeItem.checkAndSet(
			item,
			item.getSimilarToUrl(
				Utils.getCurUri()
			)
		) === true)
		{
			let parentItem = this.#getParentItemFor(item);
			while (parentItem)
			{
				parentItem.markAsActive();
				parentItem = this.#getParentItemFor(parentItem);
			}
		}

		EventEmitter.subscribe(item, Options.eventName('onItemDelete'), ({data}) => {this.deleteItem(item, data)});
		EventEmitter.subscribe(item, Options.eventName('onItemConvert'), ({data}) => {this.convertItem(item, data)});

		[...item.container.querySelectorAll('a')]
			.forEach((node) => {
				node.addEventListener('click', (event) => {
					if (this.#isEditMode === true)
					{
						event.preventDefault();
						event.stopPropagation();
						return false;
					}
				}, true)
			})
		;
		item.container
			.querySelector('[data-role="item-edit-control"]')
			?.addEventListener('click', (event) => {
				this.openItemMenu(item, event.target)
			});
		return item;
	}

	unregisterItem(item: Item)
	{
		if (!this.items.has(item.getId()))
		{
			return;
		}

		this.items.delete(item.getId());

		this.#activeItem.checkAndUnset(
			item,
			item.getSimilarToUrl(
				Utils.getCurUri()
			)
		);

		EventEmitter.unsubscribeAll(item, Options.eventName('onItemDelete'));
		EventEmitter.unsubscribeAll(item, Options.eventName('onItemConvert'));
		item.container.parentNode.replaceChild(item.container.cloneNode(true), item.container);
	}

	get isEditMode()
	{
		return this.#isEditMode;
	}

	switchToEditMode()
	{
		if (this.#isEditMode)
		{
			return;
		}

		this.#isEditMode = true;

		EventEmitter.emit(
			this,
			Options.eventName('EditMode'),
		);
	}

	switchToViewMode()
	{
		if (!this.#isEditMode)
		{
			return;
		}

		this.#isEditMode = false;

		EventEmitter.emit(
			this,
			Options.eventName('ViewMode'),
		);
	}

	isHiddenContainerVisible()
	{
		return this.hiddenContainer.classList.contains('menu-item-favorites-more-open');
	}

	toggleHiddenContainer(animate)
	{
		if (this.hiddenContainer.classList.contains('menu-item-favorites-more-open'))
		{
			this.#hideHiddenContainer(animate);
		}
		else
		{
			this.#showHiddenContainer(animate);
		}
	}

	#showHiddenContainer(animate)
	{
		EventEmitter.emit(
			this,
			Options.eventName('onHiddenBlockIsVisible')
		);
		if (animate === false)
		{
			return this.hiddenContainer.classList.add('menu-item-favorites-more-open');
		}

		this.hiddenContainer.style.height = "0px";
		this.hiddenContainer.style.opacity = 0;
		this.#animation(true, this.hiddenContainer, this.hiddenContainer.scrollHeight);
	}

	#hideHiddenContainer(animate)
	{
		EventEmitter.emit(
			this,
			Options.eventName('onHiddenBlockIsHidden')
		);
		if (animate === false)
		{
			return this.hiddenContainer.classList.remove('menu-item-favorites-more-open');
		}
		this.#animation(false, this.hiddenContainer, this.hiddenContainer.offsetHeight);
	}

	#animation(opening, hiddenBlock, maxHeight)
	{
		hiddenBlock.style.overflow = "hidden";
		(new BX.easing({
			duration: 200,
			start: {opacity: opening ? 0 : 100, height: opening ? 0 : maxHeight},
			finish: {opacity: opening ? 100 : 0, height: opening ? maxHeight : 0},
			transition: BX.easing.transitions.linear,
			step: function (state) {
				hiddenBlock.style.opacity = state.opacity / 100;
				hiddenBlock.style.height = state.height + "px";
			},
			complete: function () {
				if (opening)
				{
					hiddenBlock.classList.add('menu-item-favorites-more-open');
				}
				else
				{
					hiddenBlock.classList.remove('menu-item-favorites-more-open');
				}
				hiddenBlock.style.opacity = "";
				hiddenBlock.style.overflow = "";
				hiddenBlock.style.height = "";
			}

		})).animate();
	}

	setItemAsAMainPage(item: Item)
	{
		const node = item.container;
		node.setAttribute("data-status", "show");
		const startTop = node.offsetTop;
		const dragElement = Dom.create("div", {
			attrs: {className: "menu-draggable-wrap"},
			style: {top: startTop}
		});

		const insertBeforeElement = node.nextElementSibling;
		if (insertBeforeElement)
		{
			node.parentNode.insertBefore(dragElement, insertBeforeElement);
		}
		else
		{
			node.parentNode.appendChild(dragElement);
		}

		dragElement.appendChild(node);

		Dom.addClass(node, "menu-item-draggable");

		(new BX.easing({
			duration: 500,
			start: {top: startTop},
			finish: {top: 0},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: function (state) { dragElement.style.top = state.top + "px"; },
			complete: () => {
				this.container.insertBefore(node, BX("left-menu-empty-item").nextSibling);
				Dom.removeClass(node, "menu-item-draggable");
				Dom.remove(dragElement);
				this.#saveItemsSort({
					action: 'mainPageIsSet',
					itemId: item.getId()
				});
			}
		})).animate();
	}

	showItem(item: Item)
	{
		const oldParent = this.#getParentItemFor(item);
		const container = this.container;

		item.container.setAttribute('data-status', 'show');
		if (this.#canChangePaternity(item))
		{
			container.appendChild(item.container);
			this.#refreshActivity(item, oldParent);
		}
		else if (oldParent)
		{
			container.appendChild(oldParent.container);
			oldParent.container.setAttribute('data-status', 'show');
			container.appendChild(oldParent.groupContainer);
		}

		if (this.hiddenContainer.querySelector('.menu-item-block') === null)
		{
			EventEmitter.emit(this, Options.eventName('onHiddenBlockIsEmpty'));
			this.#hideHiddenContainer(false);
		}

		this.#recalculateCounters(item);
		this.#saveItemsSort({
			action: 'showItem',
			itemId: item.getId()
		});
	}

	hideItem(item: Item)
	{
		const oldParent = this.#getParentItemFor(item);
		const container = this.hiddenContainer.querySelector('#left-menu-hidden-items-list');
		const emitEvent = container.querySelector('.menu-item-block') === null;

		item.container.setAttribute('data-status', 'hide');
		if (this.#canChangePaternity(item))
		{
			container.appendChild(item.container);
			this.#refreshActivity(item, oldParent);
		}
		else if (oldParent)
		{
			container.appendChild(oldParent.container);
			oldParent.container.setAttribute('data-status', 'hide');
			container.appendChild(oldParent.groupContainer);
		}

		if (emitEvent)
		{
			EventEmitter.emit(this, Options.eventName('onHiddenBlockIsNotEmpty'));
		}

		this.#recalculateCounters(item);

		this.#saveItemsSort({
			action: 'hideItem',
			itemId: item.getId()
		});
	}

	#recalculateCounters(item: Item)
	{
		let counterValue = 0;
		if (item.container.querySelector('[data-role="counter"]'))
		{
			counterValue = item.container.querySelector('[data-role="counter"]').dataset.counterValue;
		}
		if (counterValue <= 0)
		{
			return;
		}

		[...this.items.entries()]
			.forEach(([id, itemGroup]) => {
				if (itemGroup instanceof ItemGroup)
				{
					itemGroup.updateCounter();
				}
			})
		;

		let hiddenCounterValue = 0;
		[...this.parentContainer
			.querySelectorAll(`.menu-item-block[data-status="hide"][data-role='item']`)
		].forEach((node) => {
				const counterNode = node.querySelector('[data-role="counter"]');
				if (counterNode)
				{
					hiddenCounterValue += parseInt(counterNode.dataset.counterValue);
				}
			})
		;

		const hiddenCounterNode = this.parentContainer.querySelector('#menu-hidden-counter');
		hiddenCounterNode.dataset.counterValue = Math.max(0, hiddenCounterValue);
		if (hiddenCounterNode.dataset.counterValue > 0)
		{
			hiddenCounterNode.classList.remove('menu-hidden-counter');
			hiddenCounterNode.innerHTML = hiddenCounterNode.dataset.counterValue > 99 ? '99+' : hiddenCounterNode.dataset.counterValue;
		}
		else
		{
			hiddenCounterNode.classList.add('menu-hidden-counter');
			hiddenCounterNode.innerHTML = '';
		}
	}

	#refreshActivity(item: Item, oldParent:? ItemGroup)
	{
		if (this.getActiveItem() !== item)
		{
			return;
		}

		const newParent = this.#getParentItemFor(item);

		if (oldParent !== newParent)
		{
			if (oldParent instanceof ItemGroup)
			{
				oldParent.markAsInactive();
			}
			if (newParent instanceof ItemGroup)
			{
				newParent.markAsActive();
			}
		}
	}

	#updateCountersLastValue = null
	updateCounters(counters, send)
	{
		const countersDynamic = {};
		send = send !== false;
		[...Object.entries(counters)]
			.forEach(([counterId, counterValue]) => {
				[...this.#getItemsByCounterId(counterId)]
					.forEach((item) => {
						const {oldValue, newValue} = item.updateCounter(counterValue);
						const state = item.container.getAttribute('data-status');

						if (
							(counterId.indexOf('crm_') < 0 || counterId.indexOf('crm_all') >= 0)
							&&
							(counterId.indexOf('tasks_') < 0 || counterId.indexOf('tasks_total') >= 0)
						)
						{
							countersDynamic[state] = countersDynamic[state] || 0;
							countersDynamic[state] += (newValue - oldValue);
						}
						let parentItem = this.#getParentItemFor(item);
						while (parentItem)
						{
							parentItem.updateCounter();
							parentItem = this.#getParentItemFor(parentItem);
						}
					})
				;

				if (send)
				{
					BX.localStorage.set('lmc-' + counterId, counterValue, 5);
				}
			})
		;

		if (countersDynamic['hide'] !== undefined && countersDynamic['hide'] !== 0)
		{
			const hiddenCounterNode = this.parentContainer.querySelector('#menu-hidden-counter');
			hiddenCounterNode.dataset.counterValue = Math.max(0, Number(hiddenCounterNode.dataset.counterValue) + Number(countersDynamic['hide']));
			if (hiddenCounterNode.dataset.counterValue > 0)
			{
				hiddenCounterNode.classList.remove('menu-hidden-counter');
				hiddenCounterNode.innerHTML = hiddenCounterNode.dataset.counterValue > 99 ? '99+' : hiddenCounterNode.dataset.counterValue;
			}
			else
			{
				hiddenCounterNode.classList.add('menu-hidden-counter');
				hiddenCounterNode.innerHTML = '';
			}
		}

		if (typeof BXIM !== 'undefined')
		{
			if (this.#updateCountersLastValue === null)
			{
				this.#updateCountersLastValue = 0;
				[...this.items.entries()]
					.forEach(([id, item]) => {
						if (item instanceof ItemGroup)
						{
							return;
						}
						const res = item.getCounterValue();

						if (res > 0)
						{
							let counterId = 'doesNotMatter';
							if (id.indexOf('menu_crm') >= 0 || id.indexOf('menu_tasks') >= 0)
							{
								const counterNode = item.container.querySelector('[data-role="counter"]');
								if (counterNode)
								{
									counterId = counterNode.id;
								}
							}
							if (
								counterId === 'doesNotMatter'
								|| counterId.indexOf('crm_all') >= 0
								|| counterId.indexOf('tasks_total') >= 0
							)
							{
								this.#updateCountersLastValue += res;
							}
						}
					});
			}
			else
			{
				this.#updateCountersLastValue += (countersDynamic['show'] !== undefined ? countersDynamic['show'] : 0);
				this.#updateCountersLastValue += (countersDynamic['hide'] !== undefined ? countersDynamic['hide'] : 0);
			}
			const visibleValue = (this.#updateCountersLastValue > 99 ? '99+' : (
				this.#updateCountersLastValue < 0 ? '0' : this.#updateCountersLastValue
			));

			if (DesktopApi.isDesktop())
			{
				DesktopApi.setBrowserIconBadge(visibleValue);
			}
		}

		[...this.items.entries()]
			.forEach(([id, itemGroup]) => {
				if (itemGroup instanceof ItemGroup)
				{
					itemGroup.updateCounter();
				}
			})
		;
	}

	decrementCounter(counters)
	{
		[...Object.entries(counters)]
			.forEach(([counterId, counterValue]) => {
				const item = this.#getItemsByCounterId(counterId).shift();
				if (item)
				{
					const value = item.getCounterValue();
					counters[counterId] = value > counterValue ? (value - counterValue) : 0;
				}
				else
				{
					delete counters[counterId];
				}
			})
		;
		this.updateCounters(counters, false);
	}

	#getItemsByCounterId(counterId: string)
	{
		const result = [];
		[...this.items.values()]
			.forEach((item: Item) => {
				const node = item.container.querySelector('[data-role="counter"]');
				if (node && node.id.indexOf(counterId) >= 0)
				{
					result.push(item);
				}
			});
		return result;
	}

	#getItemsToSave()
	{
		const saveSortItems = {
			show: [],
			hide: [],
		};
		const customItems = [];

		let firstItemLink = null;
		['show', 'hide']
			.forEach((state) => {
				let items = saveSortItems[state];
				let currentGroupId = null;
				const chain = [];

				Array.from(
					this.parentContainer
						.querySelectorAll(`.menu-item-block[data-status="${state}"]`)
				).forEach((node) =>
				{
					if (node.dataset.role === 'group')
					{
						const groupId = node.parentNode.hasAttribute('data-group-id') ?
							node.parentNode.getAttribute('data-group-id') : null;
						items = saveSortItems[state];
						let groupItem
						while (groupItem = chain.pop())
						{
							if (groupItem['group_id'] === groupId)
							{
								chain.push(groupItem);
								items = groupItem.items;
								break;
							}
						}
						const item = {
							group_id: node.dataset.id,
							items: []
						};
						items.push(item);
						chain.push(item);
						items = item.items;
						currentGroupId = node.dataset.id;
					}
					else
					{
						if ([ItemAdminCustom.code, ItemUserSelf.code, ItemUserFavorites.code]
							.indexOf(node.getAttribute('data-type')) >= 0)
						{
							const item = {
								ID: node.getAttribute('data-id'),
								LINK: node.getAttribute('data-link'),
								TEXT: Text.decode(node.querySelector("[data-role='item-text']").innerHTML)
							};
							if (node.getAttribute("data-new-page") === "Y")
							{
								item.NEW_PAGE = "Y";
							}

							customItems.push(item);
						}

						if (firstItemLink === null && Type.isStringFilled(node.getAttribute("data-link")))
						{
							firstItemLink = node.getAttribute("data-link");
						}

						if (node.closest(`[data-group-id="${currentGroupId}"][data-role="group-content"]`))
						{
							items.push(node.dataset.id);
						}
						else
						{
							const groupId = node.parentNode.hasAttribute('data-group-id') ?
								node.parentNode.getAttribute('data-group-id') : null;
							items = saveSortItems[state];
							let groupItem
							while (groupItem = chain.pop())
							{
								if (groupItem['group_id'] === groupId)
								{
									chain.push(groupItem);
									items = groupItem.items;
									break;
								}
							}
							items.push(node.dataset.id);
						}
					}
				});
			})
		;
		return {saveSortItems, firstItemLink, customItems};
	}

	#saveItemsSort(analyticsLabel)
	{
		const {saveSortItems, firstItemLink} = this.#getItemsToSave();
		Backend.saveItemsSort(saveSortItems, firstItemLink, analyticsLabel || {action: 'sortItem'});
	}

	addItem({node, animateFromPoint})
	{
		if (!(node instanceof Element))
		{
			return;
		}

		const styleValue = node.style.display;
		if (animateFromPoint)
		{
			node.dataset.styleDisplay = node.style.display;
			node.style.display = 'none';
		}
		if (this.items.has(node.dataset.id) && node.dataset.type === ItemUserFavorites.code)
		{
			const item = this.items.get(node.dataset.id);
			item.storage.push(ItemUserFavorites.code);
			item.container.dataset.storage = item.storage.join(',');
			node = item.container;
		}
		else
		{
			this.container.appendChild(node);
			this.registerItem(node);
			this.#saveItemsSort();
		}

		if (animateFromPoint)
		{
			this
				.#animateTopItemToLeft(node, animateFromPoint)
				.then(() => {
					node.style.display = node.dataset.styleDisplay;
				})
			;
		}
	}

	updateItem(data)
	{
		let {id} = data;
		if (this.items.has(id))
		{
			this.items
				.get(id)
				.update(data)
			;
		}
	}

	deleteItem(item: Item, {animate})
	{
		this.items.delete(item.getId());
		this.#activeItem.checkAndUnset(item);

		if (item instanceof ItemUserFavorites || animate)
		{
			this
				.#animateTopItemFromLeft(item.container)
				.then(() => {
					item.container.parentNode.removeChild(item.container);
					this.#saveItemsSort();
				})
			;
		}
		else if (item.container)
		{
			item.container.parentNode.removeChild(item.container);
			this.#saveItemsSort();
		}
	}

	convertItem(item: Item)
	{
		this.unregisterItem(item);
		this.registerItem(this.parentContainer.querySelector(`li.menu-item-block[data-id="${item.getId()}"]`));
	}

	getActiveItem(): ?Item
	{
		return this.#activeItem.item;
	}

	#getParentItemFor(item: Item): ?ItemGroup
	{
		if (!(item instanceof Item))
		{
			return null;
		}
		const parentContainer = item.container.closest('[data-role="group-content"]');
		if (parentContainer && this.items.has(parentContainer.getAttribute('data-group-id')))
		{
			return this.items.get(parentContainer.getAttribute('data-group-id'));
		}
		return null;
	}

	#canChangePaternity(item: Item): boolean
	{
		if (item instanceof ItemGroup)
		{
			return false;
		}
		const oldParent = this.#getParentItemFor(item);
		if (oldParent instanceof ItemGroup
			&& item.container.parentNode.querySelectorAll('li.menu-item-block').length <= 1
		)
		{
			return false;
		}
		return true;
	}

	export()
	{
		return this.#getItemsToSave();
	}

	//region DropdownActions
	#openItemMenuPopup;
	openItemMenu(item: Item, target)
	{
		if (this.#openItemMenuPopup)
		{
			this.#openItemMenuPopup.close();
		}
		const contextMenuItems = [];
		// region hide/show item

		if (item instanceof ItemMainPage)
		{
			contextMenuItems.push({
				text: Loc.getMessage('MENU_OPEN_SETTINGS_MAIN_PAGE'),
				onclick: () => {
					item.openSettings();
				},
			});
		}
		else if (item.container.getAttribute("data-status") === "show")
		{
			contextMenuItems.push({
				text: Loc.getMessage("hide_item"),
				onclick: () => {
					this.hideItem(item);
				}
			});
		}
		else
		{
			contextMenuItems.push({
				text: Loc.getMessage("show_item"),
				onclick: (target, contextMenuItem) => {
					this.showItem(item);
				}
			});
		}
		//endregion

		//region set as main page
		if (
			!Options.isExtranet
			&& !Options.isMainPageEnabled
			&& !(item instanceof ItemUserSelf)
			&& !(item instanceof ItemGroup)
			&& this.container.querySelector('li.menu-item-block[data-role="item"]') !== item.container
		)
		{
			contextMenuItems.push({
				text: Loc.getMessage("MENU_SET_MAIN_PAGE"),
				onclick: () => {
					this.setItemAsAMainPage(item);
				}
			});
		}
		//endregion

		item
			.getDropDownActions()
			.forEach((actionItem) => {
				contextMenuItems.push(actionItem);
			});

		if (!(item instanceof ItemMainPage))
		{
			contextMenuItems.push({
				text: this.#isEditMode ? Loc.getMessage("MENU_EDIT_READY_FULL") : Loc.getMessage("MENU_SETTINGS_MODE"),
				onclick: () => {
					this.#isEditMode ? this.switchToViewMode() : this.switchToEditMode();
				}
			});
		}

		contextMenuItems.forEach((item) => {
			item['className'] = ["menu-popup-no-icon", item['className'] ?? ''].join(' ');
			const {onclick} = item;
			item['onclick'] = (event, item) => {
				item.getMenuWindow().close();
				onclick.call(event, item);
			};
		});
		this.#openItemMenuPopup = new Menu({
			bindElement: target,
			items: contextMenuItems,
			offsetTop: 0,
			offsetLeft: 12,
			angle: true,
			events: {
				onClose: () => {
					EventEmitter.emit(this, Options.eventName('onClose'));
					item.container.classList.remove('menu-item-block-hover');
					this.#openItemMenuPopup = null;
				},
				onShow: () => {
					item.container.classList.add('menu-item-block-hover');
					EventEmitter.emit(this, Options.eventName('onShow'));
				},
			}
		});
		this.#openItemMenuPopup.show();
	}
	//endregion

	//region Visible sliding
	#animateTopItemToLeft(node, animateFromPoint)
	{
		return new Promise((resolve) => {
			let {startX, startY} = animateFromPoint;
			const topMenuNode = document.createElement('DIV');
			topMenuNode.style = `position: absolute; z-index: 1000; top: ${startY + 25}px;`

			const cloneNode = node.cloneNode(true);
			cloneNode.style.display = node.dataset.styleDisplay;
			document.body.appendChild(topMenuNode);
			topMenuNode.appendChild(cloneNode);

			let finishY = this.hiddenContainer.getBoundingClientRect().top;

			(new BX.easing({
				duration: 500,
				start: {left: startX},
				finish: {left: 30},
				transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: function (state) {
					topMenuNode.style.left = state.left + "px";
				},
				complete: () => {
					(new BX.easing({
						duration: 500,
						start: {top: startY + 25},
						finish: {top: finishY},
						transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
						step: function (state) {
							topMenuNode.style.top = state.top + "px";
						},
						complete: () => {
							Dom.remove(topMenuNode);
							resolve();
						}
					})).animate();
				}
			})).animate();
		});
	}
	#animateTopItemFromLeft(node)
	{
		return new Promise((resolve) => {
			(new BX.easing({
				duration: 700,
				start: {left: node.offsetLeft, opacity: 1},
				finish: {left: 400, opacity: 0},
				transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: function (state) {
					node.style.paddingLeft = state.left + "px";
					node.style.opacity = state.opacity;
				},
				complete: function () {
					resolve();
				}
			})).animate();
		});
	}
	//endregion

	/* region D&D */
	#registerDND(item)
	{
		//drag&drop
		jsDD.Enable();
		item.container.onbxdragstart = this.#menuItemDragStart.bind(this, item);
		item.container.onbxdrag = (x, y) => { this.#menuItemDragMove(/*item,*/ x, y); };
		item.container.onbxdraghover = (dest, x, y) => { this.#menuItemDragHover(/*item, */dest, x, y); };
		item.container.onbxdragstop = this.#menuItemDragStop.bind(this, item);
		jsDD.registerObject(item.container);
	}

	#menuItemDragStart(item)
	{
		EventEmitter.emit(EventEmitter.GLOBAL_TARGET, 'BX.Bitrix24.LeftMenuClass:onDragStart');

		if (!(item instanceof ItemGroup)
			&& item.container.parentNode.querySelectorAll('li.menu-item-block').length <= 1
			&& this.#getParentItemFor(item) !== null
		)
		{
			item = this.#getParentItemFor(item);
		}

		EventEmitter.emit(
			this,
			Options.eventName('onDragModeOn')
		);

		this.dnd = {
			container: this.container.parentNode,
			itemDomBlank: Dom.create('div', {
				style: {
					display: 'none',
					// border: '2px solid navy'
				}}),
			itemMoveBlank: Dom.create('div', {
				style: {
					height: item.container.offsetHeight + 'px',
					// border: '2px solid red',
				}}
			),
			draggableBlock: Dom.create('div', {             //div to move
				attrs: {
					className: "menu-draggable-wrap",
				},
				style: {
					top: [(
						item.container.offsetTop - item.container.offsetHeight
					), 'px'].join(''),
					// border: '2px solid black'
				}
			}),
			item: item,
			oldParent: this.#getParentItemFor(item),
			isHiddenContainerVisible: this.isHiddenContainerVisible()
		}

		this.#showHiddenContainer(false);

		const registerItems = () => {
			[...this.parentContainer
				.querySelectorAll('li.menu-item-block')]
				.forEach((node) => {
					if (item instanceof ItemGroup
						&& this.#getParentItemFor(
							this.items.get(node.getAttribute('data-id'))
						) !== null)
					{
						return;
					}
					jsDD.registerDest(node, 100);
				});

			const firstNode = this.parentContainer
				.querySelector("#left-menu-empty-item");
			if (item instanceof ItemUserSelf)
			{
				jsDD.unregisterDest(firstNode);
			}
			else
			{
				jsDD.registerDest(firstNode, 100);
			}
			jsDD.registerDest(this.parentContainer
				.querySelector("#left-menu-hidden-empty-item"), 100);
			jsDD.registerDest(this.parentContainer
				.querySelector("#left-menu-hidden-separator"), 100);
		};

		if (item instanceof ItemGroup)
		{
			item
				.collapse(true)
				.then(() => {
					if (this.dnd)
					{
						this.dnd.pos = BX.pos(this.container.parentNode);
						registerItems();
					}
				});
		}
		else
		{
			registerItems();
		}

		const dragElement = item.container;
		Dom.addClass(this.dnd.container, "menu-drag-mode")
		Dom.addClass(dragElement, "menu-item-draggable");
		dragElement
			.parentNode
			.insertBefore(this.dnd.itemDomBlank, dragElement); //remember original item place
		dragElement
			.parentNode
			.insertBefore(this.dnd.itemMoveBlank, dragElement); //empty div
		this.dnd.draggableBlock.appendChild(item.container);

		this.dnd.container.style.position = 'relative';
		this.dnd.container.appendChild(this.dnd.draggableBlock);
		this.dnd.pos = BX.pos(this.container.parentNode);
	}

	#menuItemDragMove(/*item,*/ x, y)
	{
		const item = this.dnd.item;
		var menuItemsBlockHeight = this.dnd.pos.height;
		y = Math.max(0, y - this.dnd.pos.top);
		this.dnd.draggableBlock.style.top = [
			Math.min(menuItemsBlockHeight - item.container.offsetHeight, y) - 5,
			'px'
		].join('');
	}

	#menuItemDragHover(/*item, */dest, x, y)
	{
		const item = this.dnd.item;
		const dragElement = item.container;

		if (dest === dragElement)
		{
			this.dnd.itemDomBlank
				.parentNode.insertBefore(this.dnd.itemMoveBlank, this.dnd.itemDomBlank);
			return;
		}

		if (dest.id === "left-menu-empty-item" &&
			(
				dragElement.getAttribute("data-type") === "self"
				|| dragElement.getAttribute("data-disable-first-item") === "Y"
			)
		)
		{
			return; // self-item cannot be moved on the first place
		}

		if (dest.getAttribute('data-role') === 'group')
		{
			const groupHolder = dest.parentNode.querySelector(`[data-group-id="${dest.getAttribute('data-id')}"]`);
			if (dest.getAttribute('data-collapse-mode') === 'collapsed')
			{
				Dom.insertAfter(this.dnd.itemMoveBlank, groupHolder);
			}
			else if (item instanceof ItemGroup)
			{
				Dom.insertBefore(this.dnd.itemMoveBlank, dest);
			}
			else
			{
				Dom.prepend(this.dnd.itemMoveBlank, groupHolder.querySelector('ul'));
			}
		}
		else if (this.dnd.container.contains(dest))
		{
			let itemPlaceHolder = dest;
			if (item instanceof ItemGroup &&
				dest.closest('[data-role="group-content"]'))
			{
				itemPlaceHolder = dest.closest('[data-role="group-content"]');
			}
			Dom.insertAfter(this.dnd.itemMoveBlank, itemPlaceHolder);
		}
	}

	#menuItemDragStop(/*item*/)
	{
		const item = this.dnd.item;
		console.log(item);
		const oldParent = this.dnd.oldParent;

		const dragElement = item.container;

		Dom.removeClass(this.dnd.container, "menu-drag-mode");
		Dom.removeClass(dragElement, "menu-item-draggable");
		this.dnd.container.style.position = '';

		let error = null;
		let onHiddenBlockIsEmptyEmitted = false;
		if (this.parentContainer.querySelector('.menu-item-block') === item.container)
		{
			if (item instanceof ItemUserSelf)
			{
				error = Loc.getMessage('MENU_SELF_ITEM_FIRST_ERROR');
			}
			else if (item.container.getAttribute("data-disable-first-item") === "Y")
			{
				error = Loc.getMessage("MENU_FIRST_ITEM_ERROR");
			}
		}
		if (error !== null)
		{
			this.dnd.itemDomBlank.parentNode.replaceChild(dragElement, this.dnd.itemDomBlank);
			item.showMessage(error);
		}
		else if (!this.dnd.container.contains(this.dnd.itemMoveBlank))
		{
			this.dnd.itemDomBlank.parentNode.replaceChild(dragElement, this.dnd.itemDomBlank);
		}
		else
		{
			try {
				this.dnd.itemMoveBlank.parentNode.replaceChild(dragElement, this.dnd.itemMoveBlank);

				if (this.hiddenContainer.contains(dragElement))
				{
					item.container.setAttribute("data-status", "hide");
					if (this.dnd.itemDomBlank.closest('#left-menu-hidden-items-block') === null
						&& this.hiddenContainer.querySelectorAll('.menu-item-block').length === 1)
					{
						EventEmitter.emit(this, Options.eventName('onHiddenBlockIsNotEmpty'));
					}
				}
				else
				{
					item.container.setAttribute("data-status", "show");
					if (this.hiddenContainer.querySelectorAll('.menu-item-block').length <= 0)
					{
						onHiddenBlockIsEmptyEmitted = true;
						EventEmitter.emit(this, Options.eventName('onHiddenBlockIsEmpty'));
					}
				}

				if (item instanceof ItemGroup)
				{
					item
						.checkAndCorrect()
						.expand(true);
				}
				this.#refreshActivity(item, oldParent);
				this.#recalculateCounters(item);

				const analyticsLabel = {action: 'sortItem'};
				if (this.parentContainer.querySelector('.menu-item-block') === item.container
					&& !this.isExtranet)
				{
					item.showMessage(Loc.getMessage("MENU_ITEM_MAIN_PAGE"));
					analyticsLabel.action = 'mainPage';
					analyticsLabel.itemId = item.getId();
				}
				this.#saveItemsSort(analyticsLabel);
			}
			catch(e)
			{
				this.dnd.itemDomBlank.parentNode.replaceChild(dragElement, this.dnd.itemDomBlank);
			}
		}

		Dom.remove(this.dnd.draggableBlock);
		Dom.remove(this.dnd.itemDomBlank);
		Dom.remove(this.dnd.itemMoveBlank);

		jsDD.enableDest(dragElement);
		this.container.style.position = 'static';
		if (!this.dnd.isHiddenContainerVisible || onHiddenBlockIsEmptyEmitted === true)
		{
			this.#hideHiddenContainer(false);
		}
		delete this.dnd;

		[...this.parentContainer
			.querySelectorAll('li.menu-item-block')]
			.forEach((node) => {
				jsDD.registerDest(node);
			});
		const firstNode = this.parentContainer
			.querySelector("#left-menu-empty-item");
		jsDD.unregisterDest(firstNode);
		jsDD.unregisterDest(this.parentContainer
			.querySelector("#left-menu-hidden-empty-item"));
		jsDD.unregisterDest(this.parentContainer
			.querySelector("#left-menu-hidden-separator"));
		jsDD.refreshDestArea();
		EventEmitter.emit(
			this,
			Options.eventName('onDragModeOff')
		);
	}
	/*endregion*/
}
