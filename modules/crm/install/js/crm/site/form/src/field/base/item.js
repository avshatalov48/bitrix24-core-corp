import * as Util from '../../util/registry';

type Options = {
	value: ?string;
	label: ?string;
	selected: ?boolean;
};


class Item
{
	value: ?string = '';
	label: string = '';
	selected: boolean = false;

	constructor(options: Options)
	{
		this.selected = !!options.selected;
		if (Util.Type.defined(options.label))
		{
			this.label = options.label;
		}
		if (Util.Type.defined(options.value))
		{
			this.value = options.value;
		}
	}
}

export {Item, Options}