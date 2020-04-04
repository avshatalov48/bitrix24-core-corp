import * as ListField from '../list/controller';
import * as Item from './item';
import * as Component from "./component";
import * as Util from "../../util/registry";

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
		this.currency = options.currency;
	}

	getOriginalType(): string
	{
		return 'list';
	}

	formatMoney(val)
	{
		return Util.Conv.formatMoney(val, this.currency.format);
	}
}

export {Controller, Options, DefaultOptions}