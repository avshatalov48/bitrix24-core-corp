import * as BaseField from '../base/controller';
import * as Component from './component';

type Options = BaseField.Options;

class Controller extends BaseField.Controller
{
	static type(): string
	{
		return 'checkbox';
	}

	static component()
	{
		return Component.FieldCheckbox;
	}

	constructor(options: Options)
	{
		options.multiple = true;
		super(options);
	}
}

export {Controller, Options}