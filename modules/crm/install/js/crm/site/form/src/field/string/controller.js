import * as BaseField from '../base/controller';
import * as Component from './component';
import * as Transform from "../transform";

type Options = BaseField.Options;

class Controller extends BaseField.Controller
{
	static type(): string
	{
		return 'string';
	}

	static component()
	{
		return Component.FieldString;
	}

	constructor(options: Options)
	{
		super(options);

		const minSize = (options.size || {}).min || 0;
		const maxSize = (options.size || {}).max || 0;
		if (minSize || maxSize)
		{
			this.validators.push(Transform.Validator.makeStringLengthValidator(minSize, maxSize));
			this.normalizers.push(Transform.Normalizer.makeStringLengthNormalizer(maxSize));
		}
	}

	get isComponentDuplicable()
	{
		return true;
	}

	getOriginalType(): string
	{
		return 'string';
	}

	getInputType(): string
	{
		return 'string';
	}

	getInputName(): string
	{
		return null;
	}

	getInputAutocomplete(): string
	{
		return null;
	}
}

export {Controller, Options}