import * as StringField from '../string/controller';
import * as Transform from "../transform";

type Options = StringField.Options;

class Controller extends StringField.Controller
{
	constructor(options: Options)
	{
		super(options);

		this.validators.push(Transform.Validator.Email);
		this.normalizers.push(Transform.Normalizer.Email);
		this.filters.push(Transform.Filter.Email);
	}

	static type(): string
	{
		return 'email';
	}

	getInputType(): string
	{
		return 'email';
	}

	getInputName(): string
	{
		return 'email';
	}

	getInputAutocomplete(): string
	{
		return 'email';
	}
}

export {Controller, Options}