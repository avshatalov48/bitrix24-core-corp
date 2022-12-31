import {Text, Tag, Loc} from 'main.core';
import {PopupManager, Popup} from 'main.popup';
import {EventEmitter} from 'main.core.events';
import Backend from '../backend';
import Options from '../options';
import DefaultController from './default-controller';
import ItemUserSelf from '../items/item-user-self';
import ItemUserFavorites from "../items/item-user-favorites";
import Utils from "../utils";

export default class ItemDirector extends DefaultController
{

	constructor(container, params)
	{
		super(container, params);
	}

	saveCurrentPage(page: ?Object)
	{
		return ItemUserFavorites
			.saveCurrentPage(page)
			.then((data) => {
				EventEmitter.emit(
					this,
					Options.eventName('onItemHasBeenAdded'),
					data
				);
				return data;
			})
			.catch(Utils.catchError);
	}

	saveStandardPage(topItem)
	{
		return ItemUserFavorites
			.saveStandardPage(topItem)
			.then((data) => {
				EventEmitter.emit(
					this,
					Options.eventName('onItemHasBeenAdded'),
					data
				);
				return data;
			})
			.catch(Utils.catchError);
	}

	deleteCurrentPage({pageLink})
	{
		return ItemUserFavorites
			.deleteCurrentPage({pageLink})
			.then((data) => {

				EventEmitter.emit(this,
					Options.eventName('onItemHasBeenDeleted'),
					data
				);
				return data;
			})
			.catch(Utils.catchError);
	}

	deleteStandardPage(topItem)
	{
		return ItemUserFavorites
			.deleteStandardPage(topItem)
			.then((data) => {
				EventEmitter.emit(this,
					Options.eventName('onItemHasBeenDeleted'),
					data
				);
				return data;
			})
			.catch(Utils.catchError);
	}

	showAddToSelf(bindElement)
	{
		ItemUserSelf
			.showAdd(bindElement)
			.then((data) => {
				EventEmitter.emit(this,
					Options.eventName('onItemHasBeenAdded'),
					data
				);
			}).catch(Utils.catchError);
	}
}
