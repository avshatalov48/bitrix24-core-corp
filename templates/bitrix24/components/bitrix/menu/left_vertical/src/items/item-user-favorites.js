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

	static getActiveTopMenuItem()
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

		const firstTopMenuInstance = Array.from(
			Object.values(
				BX.Main.interfaceButtonsManager
					.getObjects()
			)
		).shift();

		if (firstTopMenuInstance)
		{
			const topMenuItem = firstTopMenuInstance.getActive();

			if (topMenuItem && typeof topMenuItem === "object")
			{
				const link = document.createElement("a");
				link.href = topMenuItem['URL'];
				//IE11 omits slash in the pathname
				const path = link.pathname[0] !== "/" ? ("/" + link.pathname) : link.pathname;

				this.#currentPageInTopMenu = {
					ID: topMenuItem['ID'] || null,
					NODE: topMenuItem['NODE'] || null,
					URL: path + link.search,
					TEXT: topMenuItem['TEXT'],
					DATA_ID: topMenuItem['DATA_ID'],
					COUNTER_ID: topMenuItem['COUNTER_ID'],
					COUNTER: topMenuItem['COUNTER'],
					SUB_LINK: topMenuItem['SUB_LINK'],
				};
			}
		}

		return this.#currentPageInTopMenu;
	}

	static isCurrentPageStandard(topMenuPoint): boolean
	{
		if (topMenuPoint && topMenuPoint['URL'])
		{
			const currentFullPath = document.location.pathname + document.location.search;
			return topMenuPoint.URL === currentFullPath && topMenuPoint.URL.indexOf('workgroups') < 0;
		}
		return false;
	}

	static saveCurrentPage({pageTitle, pageLink})
	{
		const topMenuPoint = this.getActiveTopMenuItem();
		let itemInfo, startX, startY;

		if (topMenuPoint
			&& topMenuPoint.NODE
			&& this.isCurrentPageStandard(topMenuPoint)
			&& (pageLink === Utils.getCurPage() || pageLink === topMenuPoint.URL || !pageLink)
		)
		{
			const menuNodeCoord = topMenuPoint.NODE.getBoundingClientRect();
			startX = menuNodeCoord.left;
			startY = menuNodeCoord.top;

			itemInfo = {
				id: topMenuPoint.DATA_ID,
				text: pageTitle || topMenuPoint.TEXT,
				link: Utils.getCurPage() || topMenuPoint.URL,
				counterId: topMenuPoint.COUNTER_ID,
				counterValue: topMenuPoint.COUNTER,
				isStandardItem: true,
				subLink: topMenuPoint.SUB_LINK
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
		const topPoint = this.getActiveTopMenuItem();

		var itemInfo = {}, startX, startY;

		if (topPoint && this.isCurrentPageStandard(topPoint))
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
			text: TEXT,
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

				const topPoint = this.getActiveTopMenuItem();
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
