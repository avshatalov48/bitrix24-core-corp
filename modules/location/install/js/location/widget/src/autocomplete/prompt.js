import {EventEmitter} from 'main.core.events';
import {Loc, Tag} from 'main.core';
import Menu from './menu';

export default class Prompt extends EventEmitter
{
	static onItemSelectedEvent = 'onItemSelected';

	/** Element */
	#inputNode;

	/** Element */
	#menuNode;

	/** {Menu} */
	#menu;

	#locationList;

	constructor(props)
	{
		super(props);
		this.setEventNamespace('BX.Location.Widget.Prompt');

		this.#inputNode = props.inputNode;

		if (props.menuNode)
		{
			this.#menuNode = props.menuNode;
		}
	}

	#createMenu()
	{
		return new Menu({
			bindElement: this.#menuNode ? this.#menuNode : this.#inputNode,
			autoHide: false,
			closeByEsc: true,
			className: 'location-widget-prompt-menu',
		});
	}

	getMenu(): Menu
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
			this.setMenuItems(locationsList, searchPhrase);
			this.getMenu().show();
		}
	}

	close(): void
	{
		this.getMenu().close();
	}

	/**
	 * @param {array<Location>} locationsList
	 * @param {string} searchPhrase
	 * @returns {*}
	 */
	setMenuItems(locationsList: array<Location>, searchPhrase: string): Menu
	{
		this.getMenu().clearItems();

		if(Array.isArray(locationsList))
		{
			this.#locationList = locationsList.slice();

			locationsList.forEach((location) => {
				this.getMenu().addMenuItem(
					this.#createMenuItem(location, searchPhrase)
				);
			});
		}
	}

	/**
	 * @param {callback} onclick
	 * @param {string} text
	 */
	addShowOnMapMenuItem(onclick: () => void, text: string)
	{
		const showOnMapNode = Tag.render`
			<div data-show-on-map="" tabindex="-1" class="location-map-popup-item--show-on-map">
				${Loc.getMessage('LOCATION_WIDGET_SHOW_ON_MAP')}
			</div>
		`;

		this.getMenu().addMenuItem({
			className: 'location-map-popup-item--info',
			text,
			onclick: (event, item) => {
				if (event.target === showOnMapNode)
				{
					onclick();
				}

				this.close();

				event.stopPropagation();
			}
		});

		//@TODO find out if there is a better way to do the same (i.e. via the html option)
		this.getMenu().menuItems[this.getMenu().menuItems.length - 1].getContainer().appendChild(showOnMapNode);
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
			title: location.name,
			html: Prompt.createMenuItemText(location.name, searchPhrase),
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

		const pattern = new RegExp(
			BX.util.escapeRegExp(
				`(${spWords.join('|')})`
			),
			'gi'
		);

		result = locationName.replace(pattern, match => `<strong>${match}</strong>`);

		return result;
	}

	#getLocationFromList(externalId: string): ?Location
	{
		let result = null;

		for(let location of this.#locationList)
		{
			if(location.externalId === externalId)
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
		const item = this.getMenu().choosePrevItem();

		if(item)
		{
			result = this.#getLocationFromList(item.id);
		}

		return result;
	}

	chooseNextItem()
	{
		let result = null;
		const item = this.getMenu().chooseNextItem();

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
		return this.getMenu().isShown();
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