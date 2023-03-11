class Pool
{
	#items = [];

	add(index, fields)
	{
		this.#items.push({
			[index]: {fields}
		});
	}

	getItems()
	{
		return this.#items;
	}

	clean()
	{
		this.#items = [];
	}

	count()
	{
		return this.#items.length;
	}

	isEmpty()
	{
		return this.#items.length === 0;
	}
}

export {
	Pool
}