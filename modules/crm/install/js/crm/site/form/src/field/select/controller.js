import * as BaseField from '../base/controller';
import * as Component from './component';

type Options = BaseField.Options;

class Controller extends BaseField.Controller
{
	static type(): string
	{
		return 'select';
	}

	static component()
	{
		return Component.FieldSelect;
	}
}

export {Controller, Options}