import {ItemType} from './item.type';

type Params = {
	types: Map<number, ItemType>
}

export class TypeStorage
{
	constructor(params: Params)
	{
		this.types = params.types;
	}

	getNextType(): ItemType
	{
		return this.types.values().next().value;
	}

	getTypes(): Map<number, ItemType>
	{
		return this.types;
	}

	addType(type: ItemType)
	{
		this.types.set(type.getId(), type);
	}

	removeType(type: ItemType)
	{
		this.types.delete(type.getId());
	}
}