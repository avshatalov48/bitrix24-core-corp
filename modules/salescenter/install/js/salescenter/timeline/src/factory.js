import { Cash } from './cash';
import { Check } from './check';
import { CheckSent } from './check-sent';
import { Custom } from './custom';
import { Payment } from './payment';
import { Sent } from './sent';
import { Watch } from './watch';

const items = [
	Cash,
	Check,
	CheckSent,
	Custom,
	Payment,
	Sent,
	Watch,
];
class Factory
{
	static create(options)
	{
		const item = items.find((item) => options.type === item.type());

		if (!item)
		{
			throw new Error(`Unknown field type '${options.type}'`);
		}

		return new item(options);
	}
}

export
{
	Factory,
};
