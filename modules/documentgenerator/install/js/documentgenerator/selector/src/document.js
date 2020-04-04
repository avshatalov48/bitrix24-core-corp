import {Type} from 'main.core';

export class Document
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

	getTitle(): string
	{
		return this.data.title;
	}

	getPublicUrl(): ?string
	{
		return this.data.publicUrl
	}

	static create(data): ?Document
	{
		if(Type.isPlainObject(data) && parseInt(data.id) > 0 && Type.isString(data.title))
		{
			return new Document(data);
		}

		return null;
	}
}