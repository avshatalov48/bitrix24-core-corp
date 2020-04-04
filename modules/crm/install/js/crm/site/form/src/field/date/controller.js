import * as DateTimeField from '../datetime/controller';

type Options = DateTimeField.Options;

class Controller extends DateTimeField.Controller
{
	static type(): string
	{
		return 'date';
	}

	get hasTime()
	{
		return false;
	}
}

export {Controller, Options}