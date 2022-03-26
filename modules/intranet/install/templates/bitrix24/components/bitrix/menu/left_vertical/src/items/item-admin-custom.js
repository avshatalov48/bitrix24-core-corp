import {Loc} from 'main.core';
import {EventEmitter} from 'main.core.events';
import Item from './item';
import Backend from "../backend";
import Options from "../options";
import ItemUserFavorites from "./item-user-favorites";

export default class ItemAdminCustom extends Item
{
	static code = 'custom';

	canDelete(): boolean
	{
		return this.container.dataset.deletePerm === 'Y';
	}

	delete()
	{
		if (this.canDelete())
		{
			Backend
				.deleteCustomItem(this.getId())
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
	}

	getDropDownActions(): Array
	{
		const actions = [];
		if (this.canDelete())
		{
			actions.push({
				text: Loc.getMessage("MENU_DELETE_ITEM_FROM_ALL"),
				onclick: this.delete.bind(this)
			});
		}
		return actions;
	}
}