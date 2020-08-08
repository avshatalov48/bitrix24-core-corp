import * as StringField from '../string/controller';

type Options = StringField.Options;

class Controller extends StringField.Controller
{
	constructor(options: Options)
	{
		super(options);
	}

	static type(): string
	{
		return 'last-name';
	}


	getInputName(): string
	{
		return 'lastname';
	}

	getInputAutocomplete(): string
	{
		return 'family-name';
	}
}

export {Controller, Options}