import * as BaseField from '../base/controller';
import * as Component from './component';

type Options = BaseField.Options;

class Controller extends BaseField.Controller
{
	static type(): string
	{
		return 'bool';
	}

	static component()
	{
		return Component.FieldBool;
	}

	constructor(options: Options)
	{
		options.multiple = false;
		super(options);
	}
}

export {Controller, Options}