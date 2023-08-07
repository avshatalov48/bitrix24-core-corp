import * as Util from '../../util/registry';
import {Item as BaseItem} from '../base/item';

export type FileData = {
	name: String,
	size: ?Number,
	content: String,
	isContentFull: Boolean,
	token: String,
	file: ?File,
	type: String,
};

type Options = {
	value: ?FileData;
	label: ?string;
	selected: ?boolean;
};

class Item extends BaseItem
{
	value: FileData;

	constructor(options: Options)
	{
		super(options);

		/*
		let value;
		if (Util.Type.object(options.value))
		{
			value = options.value;
			value.quantity = value.quantity ? Util.Conv.number(value.quantity) : 0;
		}
		else
		{
			value = {id: options.value};
		}
		this.value = {
			id: value.id || '',
			quantity: value.quantity || this.quantity.min || this.quantity.step,
		};
		*/
	}

	getFileData(): FileData
	{

	}

	setFileData(data: FileData)
	{

	}

	clearFileData()
	{
		this.value = null;
	}
}

export {Item, Options}