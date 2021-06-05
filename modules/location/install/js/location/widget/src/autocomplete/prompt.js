import {EventEmitter} from 'main.core.events';
import {Loc} from 'main.core';
import {Location, Address, AddressType, LocationType} from 'location.core';
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

	/** {Array<Location>} */
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
	 * @param {Address} address
	 * @returns {*}
	 */
	setMenuItems(locationsList: Array<Location>, searchPhrase: string, address: Address): Menu
	{
		this.getMenu().clearItems();

		if(Array.isArray(locationsList))
		{
			let isSeparatorSet = false;

			this.#locationList = locationsList.slice();

			locationsList.forEach((location) => {

				if(address && address.getFieldValue(AddressType.LOCALITY))
				{
					if (!isSeparatorSet && location && location.address && location.address.getFieldValue(AddressType.LOCALITY))
					{
						if (address.getFieldValue(AddressType.LOCALITY) !== location.address.getFieldValue(AddressType.LOCALITY))
						{
							isSeparatorSet = true;
							this.getMenu().addMenuItem({
								html: Loc.getMessage('LOCATION_WIDGET_PROMPT_IN_OTHER_CITY'),
								delimiter: true
							});
						}
					}
				}

				this.getMenu().addMenuItem(
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
			title: location.name,
			html: Prompt.createMenuItemText(location.name, searchPhrase, location),
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

	static createMenuItemText(locationName: string, searchPhrase: string, location: Location): string
	{
		let result = `
		<div>
			<strong>${locationName}</strong>
		</div>`;

		let clarification;

		if(location.getFieldValue(LocationType.TMP_TYPE_CLARIFICATION))
		{
			clarification = location.getFieldValue(LocationType.TMP_TYPE_CLARIFICATION);

			if(clarification)
			{
				if(location.getFieldValue(LocationType.TMP_TYPE_HINT))
				{
					clarification += ` <i>(${location.getFieldValue(LocationType.TMP_TYPE_HINT)})</i>`;
				}

				result += `<div>${clarification}</div>`;
			}
		}

		return result;
	}

	static #extractClarification(location: Location): string
	{
		let clarification = '';

		if(location.getFieldValue(LocationType.TMP_TYPE_CLARIFICATION))
		{
			clarification = location.getFieldValue(LocationType.TMP_TYPE_CLARIFICATION);
		}

		return clarification;
	}

	#getLocationFromList(externalId: string): ?Location
	{
		let result = null;

		for(const location of this.#locationList)
		{
			if(location.externalId === externalId)
			{
				result = location;
				break;
			}
		}

		if(!result)
		{
			BX.debug(`Location with externalId ${externalId} was not found`);
		}

		return result;
	}

	choosePrevItem(isRecursive: boolean = false)
	{
		let result = null;
		const item = this.getMenu().choosePrevItem();

		if (item)
		{
			if (item.delimiter && item.delimiter === true)
			{
				result = isRecursive ? this.getMenu().chooseNextItem() : this.choosePrevItem(true);
			}
			else
			{
				result = this.#getLocationFromList(item.id);
			}
		}

		return result;
	}

	chooseNextItem()
	{
		let result = null;
		const item = this.getMenu().chooseNextItem();

		if (item)
		{
			if (item.delimiter && item.delimiter === true)
			{
				result = this.chooseNextItem();
			}
			else
			{
				result = this.#getLocationFromList(item.id);
			}
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