import {Tag} from 'main.core';
import {Address, Format, AddressStringConverter} from 'location.core';
import {EventEmitter} from 'main.core.events';

/**
 * Class AddressRestorer
 * It is responsible for the address restoring.
 * If user saved the unrecognized address and then changes the marker position on the map.
 * The address changes.
 * We show the dialog where the user can restore the address entered earlier.
 */
export default class AddressRestorer extends EventEmitter
{
	static #onRestoreEvent = 'onRestore';

	/** {Format} */
	#addressFormat;
	/** {Address} */
	#address;
	/** {Element} */
	#element;
	/** {Element} */
	#stringElement;
	/** {Element} */
	#button;

	constructor(props)
	{
		super();

		this.setEventNamespace('BX.Location.Widget.MapPopup.AddressRestorer');

		if (!(props.addressFormat instanceof Format))
		{
			throw new Error('addressFormat must be instance of Format');
		}

		this.#addressFormat = props.addressFormat;
	}

	render(props): Element
	{
		this.address = props.address;

		this.#stringElement = Tag.render`
			<div class="location-map-address-changed-text">
				${this.#convertAddressToString(this.#address)}
			</div>`;

		this.#button = Tag.render`
			<button type="button" class="location-map-address-changed-btn">
				${BX.message('LOCATION_WIDGET_AUI_ADDRESS_RESTORE')}
			</button>`;

		this.#button.addEventListener('click', this.#onRestoreButtonClick.bind(this));

		this.#element = Tag.render`				
			<div class="location-map-address-changed hidden">
				<div class="location-map-address-changed-inner">
					<div class="location-map-address-changed-title">
						${BX.message('LOCATION_WIDGET_AUI_ADDRESS_CHANGED')}:
					</div>
					${this.#stringElement}
				</div>
				${this.#button}
			</div>`;

		this.#element.style.display = 'none';
		return this.#element;
	}

	// eslint-disable-next-line no-unused-vars
	#onRestoreButtonClick(e)
	{
		this.emit(AddressRestorer.#onRestoreEvent, {address: this.#address});
	}

	#convertAddressToString(address: ?Address): string
	{
		if (!address)
		{
			return '';
		}

		return address.toString(this.#addressFormat, AddressStringConverter.STRATEGY_TYPE_TEMPLATE_COMMA);
	}

	set address(address: ?Address): void
	{
		this.#address = address;

		// Not rendered yet
		if (!this.#stringElement || !this.#address)
		{
			return;
		}

		this.#stringElement.innerHTML = this.#convertAddressToString(this.#address);
	}

	show()
	{
		if (this.#element && this.#address && this.isHidden())
		{
			this.#element.style.display = 'flex';
			this.#element.classList.remove('hidden');
		}
	}

	hide()
	{
		if (this.#element && !this.isHidden())
		{
			this.#element.classList.add('hidden');

			setTimeout(() => {
				this.#element.style.display = 'none';
			}, 600);
		}
	}

	isHidden()
	{
		let result = false;

		if (this.#element)
		{
			result = this.#element.classList.contains('hidden');
		}

		return result;
	}

	onRestoreEventSubscribe(listener: Function): void
	{
		this.subscribe(AddressRestorer.#onRestoreEvent, listener);
	}
}