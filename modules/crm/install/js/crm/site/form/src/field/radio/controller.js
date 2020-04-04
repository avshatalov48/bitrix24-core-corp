import * as BaseField from '../base/controller';
import * as Component from '../checkbox/component';

type Options = BaseField.Options;

class Controller extends BaseField.Controller
{
	static type(): string
	{
		return 'radio';
	}

	static component()
	{
		return Component.FieldCheckbox;
	}

	constructor(options: Options)
	{
		options.multiple = false;
		super(options);
	}
}

export {Controller, Options}