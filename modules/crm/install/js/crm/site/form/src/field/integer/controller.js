import * as StringField from '../string/controller';
import * as Transform from "../transform";

type Options = StringField.Options;

class Controller extends StringField.Controller
{
	constructor(options: Options)
	{
		super(options);

		this.validators.push(Transform.Validator.Integer);
		this.normalizers.push(Transform.Normalizer.Integer);
		this.filters.push(Transform.Normalizer.Integer);
	}

	static type(): string
	{
		return 'integer';
	}

	getInputType(): string
	{
		return 'number';
	}
}

export {Controller, Options}