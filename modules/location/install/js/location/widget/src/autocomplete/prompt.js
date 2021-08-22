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
	setMenuItems(locationsList: Array<Location>, searchPhrase: string, address: ?Address): Menu
	{
		this.getMenu().clearItems();

		if(Array.isArray(locationsList))
		{
			let isSeparatorSet = false;

			this.#locationList = locationsList.slice();

			locationsList.forEach((location, index) => {
				if(address && address.getFieldValue(AddressType.LOCALITY))
				{
					if (
						!isSeparatorSet
						&& location
						&& location.address
						&& location.address.getFieldValue(AddressType.LOCALITY)
					)
					{
						if (
							!this.#getAddressPossibleLocalities(location.address).includes(
								address.getFieldValue(AddressType.LOCALITY)
							)
						)
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
					this.#createMenuItem(index, location, searchPhrase)
				);
			});
		}
	}

	#getAddressPossibleLocalities(address: Address)
	{
		const result = [];

		if (address.getFieldValue(AddressType.LOCALITY))
		{
			result.push(address.getFieldValue(AddressType.LOCALITY));
		}

		/**
		 * Address break-down formed on frontend is very inaccurate so we can't rely only on the locality type field
		 * @see #142094
		 */
		if (address.getFieldValue(AddressType.ADM_LEVEL_1))
		{
			result.push(address.getFieldValue(AddressType.ADM_LEVEL_1));
		}

		return result;
	}

	/**
	 * @param {number} index
	 * @param {Location} location
	 * @param {string} searchPhrase
	 * @returns {{onclick: onclick, text: string}}
	 */
	#createMenuItem(index, location: Location, searchPhrase): Object
	{
		return {
			id: index,
			title: location.name,
			html: Prompt.createMenuItemText(location.name, searchPhrase, location),
			onclick: (event, item) => {
				this.#onItemSelect(index);
				this.close();
			}
		};
	}

	#onItemSelect(index: number): void
	{
		const location = this.#getLocationFromList(index);

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

		return '<div data-role="location-widget-menu-item" tabindex="-1">' + result + '</div>';
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

	#getLocationFromList(index: number): ?Location
	{
		let result = null;

		if (this.#locationList[index] !== undefined)
		{
			result = this.#locationList[index];
		}

		if(!result)
		{
			BX.debug(`Location with index ${index} was not found`);
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
				result = this.#getLocationFromList(this.getMenu().choseItemIdx);
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
				result = this.#getLocationFromList(this.getMenu().choseItemIdx);
			}
		}

		return result;
	}

	isItemChosen()
	{
		return this.getMenu().isItemChosen();
	}

	getChosenItem()
	{
		let result = null;
		const menuItem = this.getMenu().getChosenItem();

		if(menuItem && menuItem.id)
		{
			result = this.#getLocationFromList(this.getMenu().choseItemIdx);
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