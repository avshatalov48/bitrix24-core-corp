export class BookingBaseField<T, V>
{
	#field;

	constructor(field: T)
	{
		this.#field = field;
	}

	getValue(): V
	{
		return this.#field.getValue();
	}

	setValue(value: V): void
	{
		this.#field.setValue(value);
	}

	getField(): T
	{
		return this.#field;
	}

	getLayout(): HTMLElement
	{
		return this.#field.layout;
	}
}
