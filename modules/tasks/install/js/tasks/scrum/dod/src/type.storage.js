import {ItemType} from './item.type';

export class TypeStorage
{
	setTypes(types: Map<number, ItemType>)
	{
		this.types = types;
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

	getType(typeId: number): ?ItemType
	{
		return this.types.get(typeId);
	}

	removeType(type: ItemType)
	{
		this.types.delete(type.getId());
	}
}