import * as BaseField from '../base/controller';
import * as Item from './item';
import * as Component from './component';

type Options = BaseField.Options;

class Controller extends BaseField.Controller
{
	static type(): string
	{
		return 'file';
	}

	static component()
	{
		return Component.FieldFile;
	}

	static createItem(options: Item.Options): Item.Item
	{
		return new Item.Item(options);
	}
}

export {Controller, Options}