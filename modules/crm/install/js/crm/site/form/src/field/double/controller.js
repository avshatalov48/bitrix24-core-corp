import * as StringField from '../string/controller';
import * as Transform from "../transform";

type Options = StringField.Options;

class Controller extends StringField.Controller
{
	constructor(options: Options)
	{
		super(options);

		this.validators.push(Transform.Validator.Double);
		this.normalizers.push(Transform.Normalizer.Double);
		this.filters.push(Transform.Normalizer.Double);
	}

	static type(): string
	{
		return 'double';
	}

	getInputType(): string
	{
		return 'number';
	}
}

export {Controller, Options}