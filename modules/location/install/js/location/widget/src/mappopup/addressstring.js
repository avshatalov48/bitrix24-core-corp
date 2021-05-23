import {Tag} from "main.core";
import {Address, Format, AddressStringConverter} from "location.core";

export default class AddressString
{
	#address;
	#element;
	#stringElement;
	#addressFormat;

	constructor(props)
	{
		if(!(props.addressFormat instanceof Format))
		{
			throw new Error('addressFormat must be instance of Format');
		}

		this.#addressFormat = props.addressFormat;
	}

	set address(address: ?Address): void
	{
		this.#address = address;

		if(!this.#stringElement)
		{
			return;
		}

		this.#stringElement.innerHTML = this.#convertAddressToString(address);

		if(!address && !this.isHidden())
		{
			this.hide();
		}
		else if(address && this.isHidden())
		{
			this.show();
		}
	}

	#convertAddressToString(address: ?Address): string
	{
		if(!address)
		{
			return '';
		}

		return address.toString(this.#addressFormat, AddressStringConverter.STRATEGY_TYPE_FIELD_SORT);
	}

	render(props): Element
	{
		this.#address = props.address;
		const addresStr = this.#convertAddressToString(this.#address);
		this.#stringElement = Tag.render`<div class="location-map-address-text">${addresStr}</div>`;

		this.#element = Tag.render`
			<div class="location-map-address-container">
				<div class="location-map-address-icon"></div>
				${this.#stringElement}
			</div>`;

		if(addresStr === '')
		{
			this.hide();
		}

		return this.#element;
	}

	show()
	{
		if(this.#element)
		{
			this.#element.style.display = 'block';
		}
	}

	hide()
	{
		if(this.#element)
		{
			this.#element.style.display = 'none';
		}
	}

	isHidden()
	{
		return !this.#element || this.#element.style.display === 'none';
	}
}