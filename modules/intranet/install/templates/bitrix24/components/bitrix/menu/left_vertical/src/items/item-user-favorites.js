import {Loc, Text, Tag} from 'main.core';
import {EventEmitter, BaseEvent} from 'main.core.events';
import {PopupManager} from 'main.popup'
import Item from './item';
import Utils from '../utils';
import Backend from '../backend';
import Options from "../options";
import ItemAdminShared from "./item-admin-shared";

export default class ItemUserFavorites extends Item
{
	static code = 'standard';
	static #currentPageInTopMenu = null;

	canDelete(): boolean
	{
		return true;
	}

	delete()
	{
		Backend
			.deleteFavoritesItemMenu({id: this.getId(), storage: this.storage})
			.then(() => {
				this.destroy();
				EventEmitter.emit(this, Options.eventName('onItemDelete'), {animate: true});
				const context = this.getSimilarToUrl(
					Utils.getCurUri()
				).length > 0 ? window : {'doesnotmatter': ''};

				BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemDeleted", [{id: this.getId()}, this]);
				BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
					isActive: false,
					context: context
				}]);
			});
	}

	getDropDownActions(): Array
	{
		const contextMenuItems = [];
		contextMenuItems.push({
			text: Loc.getMessage("MENU_RENAME_ITEM"),
			onclick: () => {
				this.constructor
					.showUpdate(this)
					.then(this.update.bind(this))
					.catch(this.showError);
			}
		});
		contextMenuItems.push({
			text: Loc.getMessage("MENU_REMOVE_STANDARD_ITEM"),
			onclick: () => {
				this.delete();
			}
		});

		if (Options.isAdmin)
		{
			contextMenuItems.push({
				text: Loc.getMessage("MENU_ADD_ITEM_TO_ALL"),
				onclick: () => {
					const itemLinkNode = this.container.querySelector('a');
					Backend
						.addAdminSharedItemMenu({
							id: this.getId(),
							link: this.links[0],
							text: this.getName(),
							counterId: this.container.dataset.counterId,
							openInNewPage: itemLinkNode && itemLinkNode.getAttribute("target") === "_blank" ? "Y" : "N"
						})
						.then(() => {
							this.showMessage(Loc.getMessage('MENU_ITEM_WAS_ADDED_TO_ALL'));
							this.container.dataset.type = ItemAdminShared.code;
							this.storage.push(ItemUserFavorites.code);
							this.container.dataset.storage = this.storage.join(',');
							EventEmitter.emit(this, Options.eventName('onItemConvert'), this);
						})
						.catch(this.showError);
				}
			});
		}
		return contextMenuItems;
	}

	static backendSaveItem(itemInfoToSave): Promise
	{
		return Backend.updateFavoritesItemMenu(itemInfoToSave);
	}

	static searchCurrentPageInTopMenu()
	{
		if (this.#currentPageInTopMenu)
		{
			return this.#currentPageInTopMenu;
		}

		if (!BX.Main
			|| !BX.Main.interfaceButtonsManager)
		{
			return null;
		}

		const firstTopMenuItem = Array.from(
			Object.values(
				BX.Main.interfaceButtonsManager
					.getObjects()
			)
		).shift();

		if (firstTopMenuItem)
		{
			const pointNotItem = firstTopMenuItem.getActive();

			if (pointNotItem && typeof pointNotItem === "object")
			{
				const link = document.createElement("a");
				link.href = pointNotItem.URL;
				//IE11 omits slash in the pathname
				const path = link.pathname[0] !== "/" ? ("/" + link.pathname) : link.pathname;

				this.#currentPageInTopMenu = {
					ID: pointNotItem['ID'] || null,
					NODE: pointNotItem['NODE'] || null,
					URL: Text.encode(path + link.search),
					TEXT: Text.encode(pointNotItem['TEXT']),
					DATA_ID: pointNotItem['DATA_ID'],
					COUNTER_ID: pointNotItem['COUNTER_ID'],
					COUNTER: pointNotItem['COUNTER'],
					SUB_LINK: pointNotItem['SUB_LINK'],
				};
			}
		}

		return this.#currentPageInTopMenu;
	}

	static isCurrentPageStandard(): boolean
	{
		const topPoint = this.searchCurrentPageInTopMenu();
		if (topPoint)
		{
			const currentFullPath = document.location.pathname + document.location.search;
			return topPoint.URL === currentFullPath && topPoint.URL.indexOf('workgroups') < 0;
		}
		return false;
	}

	static saveCurrentPage({pageTitle, pageLink})
	{
		const topPoint = this.searchCurrentPageInTopMenu();
		let itemInfo, startX, startY;

		if (this.isCurrentPageStandard()
			&& topPoint
			&& topPoint.NODE
			&& (pageLink === Utils.getCurPage() || pageLink === topPoint.URL || !pageLink)
		)
		{
			const menuNodeCoord = topPoint.NODE.getBoundingClientRect();
			startX = menuNodeCoord.left;
			startY = menuNodeCoord.top;

			itemInfo = {
				id: topPoint.DATA_ID,
				text: pageTitle || topPoint.TEXT,
				link: Utils.getCurPage() || topPoint.URL,
				counterId: topPoint.COUNTER_ID,
				counterValue: topPoint.COUNTER,
				isStandardItem: true,
				subLink: topPoint.SUB_LINK
			};
		}
		else
		{
			itemInfo = {
				text: pageTitle || document.getElementById('pagetitle').innerText,
				link: pageLink || Utils.getCurPage(),
				isStandardItem: pageLink === Utils.getCurPage()
			};
			const titleCoord = BX("pagetitle").getBoundingClientRect();
			startX = titleCoord.left;
			startY = titleCoord.top
		}

		return Backend
			.addFavoritesItemMenu(itemInfo)
			.then(({data: {itemId}}) => {
				itemInfo.id = itemId;
				itemInfo.topMenuId = itemInfo.id;
				return {node: this.createNode(itemInfo), animateFromPoint: {startX, startY}, itemInfo: itemInfo};
			})
		;
	}

	static deleteCurrentPage({pageLink})
	{
		const topPoint = this.searchCurrentPageInTopMenu();

		var itemInfo = {}, startX, startY;

		if (this.isCurrentPageStandard()
			&& topPoint
		)
		{
			itemInfo['id'] = topPoint.DATA_ID;

			const menuNodeCoord = topPoint.NODE.getBoundingClientRect();
			startX = menuNodeCoord.left;
			startY = menuNodeCoord.top;
		}
		else
		{
			itemInfo['link'] = pageLink || Utils.getCurPage();
			const titleCoord = BX("pagetitle").getBoundingClientRect();
			startX = titleCoord.left;
			startY = titleCoord.top
		}
		return Backend
			.deleteFavoritesItemMenu(itemInfo)
			.then(({data}) => {
				if (!itemInfo.id && data && data['itemId'])
				{
					itemInfo.id = data['itemId'];
				}
				EventEmitter.emit(EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'), {id: itemInfo.id});
				return {itemInfo: itemInfo, animateToPoint: {startX, startY}};
			});
	}

	static saveStandardPage({DATA_ID, TEXT, SUB_LINK, COUNTER_ID, COUNTER, NODE, URL})
	{
		const itemInfo = {
			id: DATA_ID,
			text: Text.encode(TEXT),
			link: URL,
			subLink: SUB_LINK,
			counterId: COUNTER_ID,
			counterValue: COUNTER
		};

		const pos = NODE.getBoundingClientRect();
		const startX = pos.left;
		const startY = pos.top
		return Backend
			.addFavoritesItemMenu(itemInfo)
			.then(({data: {itemId}}) => {
				itemInfo.id = itemId;
				itemInfo.topMenuId = itemInfo.id;

				const topPoint = this.searchCurrentPageInTopMenu();
				BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemAdded", [itemInfo, this]);
				BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
					isActive: true,
					context: topPoint && topPoint.DATA_ID === DATA_ID ? window : null,
				}]);

				return {node: this.createNode(itemInfo), animateFromPoint: {startX, startY}};
			})
		;
	}

	static deleteStandardPage({DATA_ID})
	{
		const itemInfo = {id: DATA_ID};
		return Backend
			.deleteFavoritesItemMenu(itemInfo)
			.then(() => {
				EventEmitter.emit(EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'), {id: itemInfo.id});
				BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemDeleted", [itemInfo, this]);
				BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
					isActive: false,
				}]);
				return {itemInfo: itemInfo};
			});
	}
}
