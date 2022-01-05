import {Text, Type} from 'main.core';

export type ItemTypeParams = {
	id?: number|string,
	name?: string,
	sort?: number,
	dodRequired?: 'Y' | 'N'
}

export class ItemType
{
	constructor(params: ItemTypeParams = {})
	{
		this.setId(params.id);
		this.setName(params.name);
		this.setSort(params.sort);
		this.setDodRequired(params.dodRequired);
	}

	setId(id: number)
	{
		this.id = (
			Type.isInteger(id)
				? parseInt(id, 10)
				: (Type.isString(id) && id) ? id : Text.getRandom()
		);
	}

	getId(): number
	{
		return this.id;
	}

	setName(name: string)
	{
		this.name = (Type.isString(name) ? name : '');
	}

	getName(): string
	{
		return this.name;
	}

	setSort(sort: number)
	{
		this.sort = (Type.isInteger(sort) ? parseInt(sort, 10) : 0);
	}

	getSort(): number
	{
		return this.sort;
	}

	setDodRequired(value: string)
	{
		this.dodRequired = (value === 'Y');
	}

	isDodRequired(): boolean
	{
		return this.dodRequired;
	}
}