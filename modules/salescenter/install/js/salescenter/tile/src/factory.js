import {More} from "./more";
import {Offer} from "./offer";
import {Cashbox} from "./cashbox";
import {Delivery} from "./delivery";
import {PaySystem} from "./paysystem";
import {Marketplace} from "./marketplace";

let tiles = [
	More,
	Offer,
	Cashbox,
	Delivery,
	PaySystem,
	Marketplace
];

class Factory
{
	static create(options)
	{
		let tile = tiles
			.filter(item => options.type === item.type())[0];

		if (!tile)
		{
			throw new Error(`Unknown field type '${options.type}'`);
		}

		return new tile(options);
	}
}

export
{
	Factory
};