import {Loc, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';
import Item from './item';
import Backend from "../backend";
import Options from "../options";
import {MessageBox} from 'ui.dialogs.messagebox';
import ItemUserFavorites from "./item-user-favorites";
import ItemAdminShared from "./item-admin-shared";

export default class ItemUserSelf extends Item
{
	static code = 'self';

	canDelete(): boolean
	{
		return true;
	}

	delete()
	{
		return Backend
			.deleteSelfITem(this.getId())
			.then(() => {
				if (this.storage.indexOf(ItemUserFavorites.code) >= 0)
				{
					Backend.deleteFavoritesItemMenu({id: this.getId()});
				}
				EventEmitter.emit(this, Options.eventName('onItemDelete'), {animate: true});
			})
			.catch(this.showError)
		;
	}

	getDropDownActions(): Array
	{
		const contextMenuItems = [];
		contextMenuItems.push({
			text: Loc.getMessage("MENU_EDIT_ITEM"),
			onclick: () => {
				this.constructor
					.showUpdate(this)
					.then(this.update.bind(this))
					.catch(this.showError);
			}
		});
		contextMenuItems.push({
			text: Loc.getMessage('MENU_DELETE_SELF_ITEM'),
			onclick: () => {
				MessageBox.confirm(
					Loc.getMessage('MENU_DELETE_SELF_ITEM_CONFIRM'),
					Loc.getMessage('MENU_DELETE_SELF_ITEM'),
					(messageBox) => {
						this.delete();
						messageBox.close();
					},
					Loc.getMessage('MENU_DELETE'),
				);
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
							this.storage.push(ItemUserSelf.code);
							this.container.dataset.storage = this.storage.join(',');
							EventEmitter.emit(this, Options.eventName('onItemConvert'), this);
						})
						.catch(this.showError);
				}
			});
		}
		return contextMenuItems;
	}

	getEditFields(): Object
	{
		return {id: this.getId(), text: this.getName(), link: this.links[0],  openInNewPage: this.container.getAttribute('data-new-page')};
	}

	static backendSaveItem(itemInfo): Promise
	{
		return Backend.saveSelfItemMenu(itemInfo)
			.then(({data}) => {
				if (data && data['itemId'])
				{
					itemInfo.id = data['itemId'];
				}
				return itemInfo;
			});
	}

	static showAdd(bindElement)
	{
		return new Promise((resolve1, reject2) => {
			this.showForm(
				bindElement,
				{id: 0, name: '', link: '', openInNewPage: 'Y'},
				resolve1, reject2
			)
		})
		.then((itemInfo) => {
			return {node: this.createNode(itemInfo)}
		});
	}
}

