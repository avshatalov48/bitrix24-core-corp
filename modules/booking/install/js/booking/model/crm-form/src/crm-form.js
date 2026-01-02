import {Type} from 'main.core';

export class CrmForm
{
	constructor(options = {name: 'CrmForm'})
	{
		this.name = options.name;
	}

	setName(name)
	{
		if (Type.isString(name))
		{
			this.name = name;
		}
	}

	getName()
	{
		return this.name;
	}
}
