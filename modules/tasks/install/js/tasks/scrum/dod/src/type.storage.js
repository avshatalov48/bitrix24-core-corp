import {ItemType} from './item.type';
import {Type} from 'main.core';

export class TypeStorage
{
	constructor()
	{
		this.types = new Map();
	}

	setTypes(types: Map<number, ItemType>)
	{
		this.types = types;
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

	setActiveType(inputType: ?ItemType)
	{
		inputType = Type.isNil(inputType) ? this.types.values().next().value : inputType;

		this.types.forEach((type: ItemType) => type.setActive(inputType.getId() === type.getId()));
	}

	getActiveType(): ?ItemType
	{
		const foundItem = [...this.types.values()].find((type: ItemType) => type.isActive());
		if (Type.isNil(foundItem))
		{
			return this.types.values().next().value;
		}

		return foundItem;
	}

	isEmpty(): boolean
	{
		return this.types.size === 0;
	}
}