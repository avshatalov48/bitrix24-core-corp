import {Cash} from './cash'
import {Check} from './check'
import {CheckSent} from './check-sent'
import {Payment} from './payment'
import {Sent} from './sent'
import {Watch} from './watch'

let items = [
	Cash,
	Check,
	CheckSent,
	Payment,
	Sent,
	Watch
];
class Factory
{
	static create(options)
	{
		let item = items
			.filter(item => options.type === item.type())[0];

		if (!item)
		{
			throw new Error(`Unknown field type '${options.type}'`);
		}

		return new item(options);
	}
}

export
{
	Factory
};