import * as Field from "../field/registry";
import * as Type from "./types";
import * as Util from "../util/registry";

class Basket
{
	#currency: Type.Currency;
	#fields: Array<Field.BaseField> = [];

	constructor(fields: Array<Field.BaseField>, currency: Type.Currency)
	{
		this.#currency = currency;
		this.#fields = fields.filter((field) => field.type === 'product');
	}

	has()
	{
		if (this.#fields.some(field => field.hasChangeablePrice()))
		{
			return false;
		}

		return this.#fields.length > 0;
	}

	items(): Array<Object>
	{
		return this.#fields
		.filter((field) => field.visible)
		.reduce((accumulator, field) => {
			return accumulator.concat(field.selectedItems());
		}, [])
		.filter((item) => item.price);
	}

	formatMoney(val)
	{
		return Util.Conv.formatMoney(val.toFixed(2), this.#currency.format);
	}

	sum(): Number
	{
		return this.items().reduce((sum, item) => sum + item.getSummary(), 0);
	}

	total(): Number
	{
		return this.items().reduce((sum, item) => sum + item.getTotal(), 0);
	}

	discount(): Number
	{
		return this.items().reduce((sum, item) => sum + item.getDiscounts(), 0);
	}

	printSum(): String
	{
		return this.formatMoney(this.sum());
	}

	printTotal(): String
	{
		return this.formatMoney(this.total());
	}

	printDiscount(): String
	{
		return this.formatMoney(this.discount());
	}
}

export {
	Basket,
}