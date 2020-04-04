import * as BaseField from '../base/controller';
import * as Component from './component';

type Options = BaseField.Options;

let DefaultOptions: Options = {
	type: 'string',
	label: 'Default field name',
	multiple: false,
	visible: true,
	required: false,
	bigPic: true,
};

class Controller extends BaseField.Controller
{
	bigPic: Boolean = false;

	static type(): string
	{
		return 'list';
	}

	static component()
	{
		return Component.FieldList;
	}

	getOriginalType(): string
	{
		return 'list';
	}

	constructor(options: Options = DefaultOptions)
	{
		super(options);
		this.bigPic = !!options.bigPic;
	}

	/*
	adjust(options: Options = DefaultOptions)
	{
		super.adjust(options);
	}
	*/
}

export {Controller, Options, DefaultOptions}