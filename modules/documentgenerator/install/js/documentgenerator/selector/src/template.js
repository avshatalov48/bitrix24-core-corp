import {Type} from 'main.core';

export class Template
{
	data;

	constructor(data)
	{
		this.data = data;
	}

	getId(): number
	{
		return parseInt(this.data.id);
	}

	getName(): string
	{
		return this.data.name;
	}

	static create(data): ?Template
	{
		if(Type.isPlainObject(data) && parseInt(data.id) > 0 && Type.isString(data.name))
		{
			return new Template(data);
		}

		return null;
	}
}