import * as Util from '../../util/registry';
import {Item as BaseItem} from '../base/item';

type Value = {
	id:  String;
	price:  Number;
	quantity:  Number;
};
type StringUrl = String;
type Quantity = {
	min: ?Number;
	max: ?Number;
	step: ?Number;
	unit: ?String;
};

type Options = {
	value: Value|String;
	label: ?string;
	selected: ?boolean;
	pics: ?Array<StringUrl>;
	price: Number,
	discount: ?Number,
	quantity: ?Quantity,
	changeablePrice: ?boolean,
};


class Item extends BaseItem
{
	value: Value;
	pics: Array<StringUrl> = [];
	discount: ?Number = 0;
	quantity: Quantity;
	changeablePrice: ?boolean = false;

	constructor(options: Options)
	{
		super(options);

		if (Array.isArray(options.pics))
		{
			this.pics = options.pics;
		}

		let price = Util.Conv.number(options.price);
		this.changeablePrice = !!options.changeablePrice;
		if (this.changeablePrice)
		{
			price = null;
		}

		this.discount = Util.Conv.number(options.discount);

		let quantity = Util.Type.object(options.quantity) ? options.quantity : {};
		this.quantity = {
			min: quantity.min ? Util.Conv.number(quantity.min) : 0,
			max: quantity.max ? Util.Conv.number(quantity.max) : 0,
			step: quantity.step ? Util.Conv.number(quantity.step) : 1,
			unit: quantity.unit || '',
		};

		let value;
		if (Util.Type.object(options.value))
		{
			value = options.value;
			value.quantity = value.quantity ? Util.Conv.number(value.quantity) : 0;
		}
		else
		{
			value = {id: options.value};
		}
		this.value = {
			id: value.id || '',
			quantity: value.quantity || this.quantity.min || this.quantity.step,
			price: price,
		};
		if (this.changeablePrice)
		{
			this.value.changeablePrice = true;
		}

		if (!options.price && !options.label && !options.value)
		{
			this.selected = false;
		}
	}

	get price()
	{
		return this.value.price;
	}

	set price(val)
	{
		this.value.price = val;
	}

	onSelect(value)
	{
		return (!this.value.id && !this.value.price && !this.label)
			? false
			: value
		;
	}

	getNextIncQuantity(): Number
	{
		let q = this.value.quantity + this.quantity.step;
		let max = this.quantity.max;
		return (max <= 0 || max >= q) ? q : 0;
	}

	getNextDecQuantity(): Number
	{
		let q = this.value.quantity - this.quantity.step;
		let min = this.quantity.min;
		return (q > 0 && (min <= 0 || min <= q)) ? q : 0;
	}

	incQuantity()
	{
		this.value.quantity = this.getNextIncQuantity();
	}

	decQuantity()
	{
		this.value.quantity = this.getNextDecQuantity();
	}

	getSummary(): Number
	{
		return (this.price + this.discount) * this.value.quantity;
	}

	getTotal(): Number
	{
		return this.price * this.value.quantity;
	}

	getDiscounts(): Number
	{
		return this.discount * this.value.quantity;
	}

	getComparableValue(): ?string
	{
		return this.value.id + '';
	}
}

export {Item, Options}