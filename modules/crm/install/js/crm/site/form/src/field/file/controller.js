import * as BaseField from '../base/controller';
import * as Item from './item';
import * as Component from './component';
import {DefaultOptions} from "../list/controller";

type Options = BaseField.Options;

class Controller extends BaseField.Controller
{
	contentTypes: Array<string> = [];

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

	constructor(options: Options = DefaultOptions)
	{
		super(options);
		this.contentTypes = options.contentTypes || [];
	}

	getAcceptTypes()
	{
		return this.contentTypes.join(',');
	}
}

export {Controller, Options}