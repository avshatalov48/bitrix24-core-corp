import * as Field from "../field/registry";

class Navigation
{
	index: Number = 1;
	pages: Array<Page> = [];

	constructor()
	{

	}

	add(page: Page)
	{
		this.pages.push(page);
	}

	next()
	{
		if (this.current().validate())
		{
			this.index += (this.index >= this.count()) ? 0 : 1;
		}
	}

	prev()
	{
		this.index -= (this.index > 1) ? 1 : 0;
	}

	first()
	{
		this.index = 1;
	}

	last(validate = true)
	{
		if (!validate || this.current().validate())
		{
			this.index = this.count();
		}
	}

	current(): Page
	{
		return this.pages[this.index - 1];
	}

	iterable(): boolean
	{
		return this.count() > 1;
	}

	ended(): boolean
	{
		return this.index >= this.count();
	}

	beginning(): boolean
	{
		return this.index === 1;
	}

	count(): number
	{
		return this.pages.length;
	}

	removeEmpty()
	{
		if (this.count() <= 1)
		{
			return;
		}

		this.pages = this.pages.filter(page => {
			return page.fields.length > 0;
		});
	}

	validate()
	{
		return this.pages.filter((page) => !page.validate()).length === 0;
	}
}

class Page
{
	title: String;
	fields: Array<Field.BaseField> = [];

	constructor(title: String)
	{
		this.title = title;
	}

	addField(field: Field.BaseField)
	{
		this.fields.push(field);
	}

	getTitle()
	{
		return this.title;
	}

	validate()
	{
		return this.fields.filter((field) => !field.valid()).length === 0;
	}
}

export {
	Navigation,
	Page
}