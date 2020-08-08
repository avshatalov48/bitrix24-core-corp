import * as StringField from '../string/controller';
import * as Transform from "../transform";

type Options = StringField.Options;

class Controller extends StringField.Controller
{
	constructor(options: Options)
	{
		super(options);

		this.formatters.push(Transform.Formatter.Phone);
		this.validators.push(Transform.Validator.Phone);
		this.normalizers.push(Transform.Normalizer.Phone);
		this.filters.push(Transform.Filter.Phone);
	}

	static type(): string
	{
		return 'phone';
	}

	getInputType(): string
	{
		return 'tel';
	}

	getInputName(): string
	{
		return 'phone';
	}

	getInputAutocomplete(): string
	{
		return 'tel';
	}
}

export {Controller, Options}