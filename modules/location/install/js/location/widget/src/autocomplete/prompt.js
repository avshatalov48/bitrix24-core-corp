import {EventEmitter} from 'main.core.events';
import Menu from './menu';

export default class Prompt extends EventEmitter
{
	static onItemSelectedEvent = 'onItemSelected';

	/** Element */
	#inputNode;
	/** {Menu} */
	#menu;

	#locationList;

	constructor(props)
	{
		super(props);
		this.setEventNamespace('BX.Location.Widget.Prompt');

		this.#inputNode = props.inputNode;
	}

	#createMenu()
	{
		return new Menu({
			bindElement: this.#inputNode,
			autoHide: false,
			closeByEsc: true
		});
	}

	#getMenu(): Menu
	{
		if(!this.#menu || this.#menu.isDestroyed())
		{
			this.#menu = this.#createMenu();
		}

		return this.#menu;
	}

	/**
	 * Show menu with list of locations
	 * @param {array} locationsList
	 * @param {string} searchPhrase
	 * @returns void
	 */
	show(locationsList: Array<Location>, searchPhrase: string): void
	{
		if(locationsList.length > 0)
		{
			this.#setMenuItems(locationsList, searchPhrase);
			this.#getMenu().show();
		}
	}

	close(): void
	{
		this.#getMenu().close();
	}

	/**
	 * @param {array<Location>} locationsList
	 * @param {string} searchPhrase
	 * @returns {*}
	 */
	#setMenuItems(locationsList: array<Location>, searchPhrase: string): Menu
	{
		this.#getMenu().clearItems();

		if(Array.isArray(locationsList))
		{
			this.#locationList = locationsList.slice();

			locationsList.forEach((location) => {
				this.#getMenu().addMenuItem(
					this.#createMenuItem(location, searchPhrase)
				);
			});
		}
	}

	/**
	 * @param {Location} location
	 * @param {string} searchPhrase
	 * @returns {{onclick: onclick, text: string}}
	 */
	#createMenuItem(location: Location, searchPhrase): Object
	{
		const externalId = location.externalId;

		return {
			id: externalId,
			text: Prompt.createMenuItemText(location.name, searchPhrase),
			onclick: (event, item) => {
				this.#onItemSelect(externalId);
				this.close();
			}
		};
	}

	#onItemSelect(externalId: string): void
	{
		const location = this.#getLocationFromList(externalId);

		if(location)
		{
			this.emit(Prompt.onItemSelectedEvent, {location: location});
		}
	}

	static createMenuItemText(locationName: string, searchPhrase: string): string
	{
		let result = locationName.slice();

		if(!searchPhrase || searchPhrase.length <= 0)
		{
			return result;
		}

		const spWords = searchPhrase
			.replace(/,+/gi, '')
			.split(new RegExp(/\s+/g));

		/*
		 * todo: case
		 */
		for(let word of spWords)
		{
			word = word.trim();

			if(word.length <= 0)
			{
				continue;
			}

			result = result.replace(
				new RegExp(word, 'gi'),
				`###@@@${word}@@@###`
			);
		}

		result = result.replace(/###@@@/g, '<strong>');
		result = result.replace(/@@@###/g, '</strong>');
		return result;
	}

	#getLocationFromList(externalId: string): ?Location
	{
		let result = null;

		for(let location of this.#locationList)
		{
			if(location.externalId === externalId )
			{
				result = location;
				break;
			}
		}

		if(!result)
		{
			BX.debug('Location with externalId ' + externalId + ' was not found');
		}

		return result;
	}

	choosePrevItem()
	{
		let result = null;
		const item = this.#getMenu().choosePrevItem();

		if(item)
		{
			result = this.#getLocationFromList(item.id);
		}

		return result;
	}

	chooseNextItem()
	{
		let result = null;
		const item = this.#getMenu().chooseNextItem();

		if(item)
		{
			result = this.#getLocationFromList(item.id);
		}

		return result;
	}

	isItemChosen()
	{
		return this.#menu.isItemChosen();
	}

	getChosenItem()
	{
		let result = null;
		const menuItem = this.#menu.getChosenItem();

		if(menuItem && menuItem.id)
		{
			result = this.#getLocationFromList(menuItem.id);
		}

		return result;
	}

	isShown(): boolean
	{
		return this.#getMenu().isShown();
	}

	destroy()
	{
		if(this.#menu)
		{
			this.#menu.destroy();
			this.#menu = null;
		}

		this.#locationList = null;
	}
}