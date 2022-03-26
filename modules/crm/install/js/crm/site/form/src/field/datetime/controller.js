import * as BaseField from '../base/controller';
import * as Component from './component';

type Options = {
	id: ?string;
	name: ?string;
	label: ?string;
	multiple: ?Boolean;
	visible: ?Boolean;
	required: ?Boolean;
	items: ?Array;
	value: ?string;
	checked: ?Boolean;
	values: ?Array;
	format: string;
	sundayFirstly: ?Boolean;
};

class Controller extends BaseField.Controller
{
	dateFormat: string;
	sundayFirstly: Boolean;

	static type(): string
	{
		return 'datetime';
	}

	static component()
	{
		return Component.FieldDateTime;
	}

	constructor(options: Options)
	{
		super(options);

		this.dateFormat = options.format;
		this.sundayFirstly = !!options.sundayFirstly;
	}

	get isComponentDuplicable()
	{
		return true;
	}

	get hasTime()
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

	getInputAutocomplete (): string
	{
		return null;
	}
}

export {Controller, Options}