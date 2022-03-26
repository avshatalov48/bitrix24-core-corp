import * as ListField from '../list/controller';
import * as Item from './item';
import * as Component from "./component";
import * as Util from "../../util/registry";
import * as Transform from "../transform";

type Options = {
	type: ?string;
	id: ?string;
	name: ?string;
	label: ?string;
	multiple: ?Boolean;
	visible: ?Boolean;
	required: ?Boolean;
	items: ?Array;
	value: ?string;
	checked: ?Boolean;
	values: ?Array;
	bigPic: ?Boolean;
	currencyFormat: ?Boolean;
};

let DefaultOptions: Options = {
	type: 'string',
	label: 'Default field name',
	multiple: false,
	visible: true,
	required: false,
};

class Controller extends ListField.Controller
{
	currency: Object;

	static type(): string
	{
		return 'product';
	}

	static component()
	{
		return Component.FieldProduct;
	}

	static createItem(options: Item.Options): Item.Item
	{
		return new Item.Item(options);
	}

	constructor(options: Options)
	{
		super(options);
		if (this.hasChangeablePrice())
		{
			this.multiple = false;
		}
		this.currency = options.currency;
		this.validators.push(value => !value.changeablePrice || value.price > 0);
		this.validators.push(value => !value.changeablePrice || Transform.Validator.Money(value.price));
		this.filters.push(value => !value.changeablePrice || Transform.Filter.Money(value.price));
		this.normalizers.push(value => !value.changeablePrice ? value : Transform.Normalizer.Money(value.price));
	}

	getOriginalType(): string
	{
		return this.hasChangeablePrice() ? 'product' : 'list';
	}

	hasChangeablePrice(): boolean
	{
		return this.items.some(item => item.changeablePrice);
	}

	formatMoney(val)
	{
		return Util.Conv.formatMoney(val, this.currency.format);
	}

	getCurrencyFormatArray()
	{
		return this.currency.format
			.replace('&#', '|||||')
			.replace('&amp;#', '|-|||-|')
			.split('#')
			.map(item => item.replace('|-|||-|', '&amp;#').replace('|||||', '&#'))
		;
	}

	addItem(options: Options): ?Item
	{
		if (!options.value && !options.label && !options.price)
		{
			options.selected = false;
		}

		return super.addItem(options);
	}
}

export {Controller, Options, DefaultOptions}