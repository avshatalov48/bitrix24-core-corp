import {Item as BaseItem} from '../base/item';


type Options = {
	value: Options;
	label: ?string;
	selected: ?boolean;
};


class Item extends BaseItem
{
	value: Object;

	constructor(options: Options)
	{
		super(options);

		this.value = {};
		this.selected = false;
	}
}

export {Item, Options}