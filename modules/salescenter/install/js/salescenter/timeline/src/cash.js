import { Base } from './base';

class Cash extends Base
{
	static type()
	{
		return 'cash';
	}

	getType()
	{
		return Cash.type();
	}

	getIcon()
	{
		return 'cash';
	}
}

export
{
	Cash,
};
