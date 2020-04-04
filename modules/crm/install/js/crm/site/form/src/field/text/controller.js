import * as BaseField from '../base/controller';
import * as Component from './component';

type Options = BaseField.Options;

let DefaultOptions: Options = {
	type: 'string',
	label: 'Default field name',
	multiple: false,
	visible: true,
	required: false,
};

class Controller extends BaseField.Controller
{
	static type(): string
	{
		return 'text';
	}

	static component()
	{
		return Component.FieldText;
	}

	get isComponentDuplicable()
	{
		return true;
	}
}

export {Controller, Options, DefaultOptions}