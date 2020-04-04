import * as BaseField from '../base/controller';
import * as Component from './component';

type Content = {
	type: ?string;
	html: ?string;
}

type Options = {
	label: ?string;
	content: Content;
};

class Controller extends BaseField.Controller
{
	content: Content = {
		type: '',
		html: '',
	};

	constructor(options: Options)
	{
		super(options);

		this.multiple = false;
		this.required = false;

		if (typeof options.content === 'object')
		{
			if (options.content.type)
			{
				this.content.type = options.content.type;
			}
			if (options.content.html)
			{
				this.content.html = options.content.html;
			}
		}
	}

	static type(): string
	{
		return 'layout';
	}

	static component()
	{
		return Component.FieldLayout;
	}
}

export {Controller, Options}