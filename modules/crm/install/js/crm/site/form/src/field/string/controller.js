import * as BaseField from '../base/controller';
import * as Component from './component';

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