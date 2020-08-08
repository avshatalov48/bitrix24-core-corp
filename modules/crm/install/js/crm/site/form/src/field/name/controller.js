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
		return 'name';
	}

	getInputName(): string
	{
		return 'name';
	}

	getInputAutocomplete(): string
	{
		return 'given-name';
	}
}

export {Controller, Options}