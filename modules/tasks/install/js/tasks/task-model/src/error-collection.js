import {TaskModel} from "tasks.task-model";

export class ErrorCollection
{
	errors: Map = new Map();

	constructor(model: TaskModel = {})
	{
		this.model = model;
	}

	getErrors()
	{
		return Object.fromEntries(this.errors);
	}

	setError(code: string, text: string): ErrorCollection
	{
		this.errors.set(code, {
			code,
			text
		});
		this.model.onErrorCollectionChange();

		return this;
	}

	removeError(code: string): ErrorCollection
	{
		if (this.errors.has(code))
		{
			this.errors.delete(code);
		}
		this.model.onErrorCollectionChange();

		return this;
	}

	clearErrors(): ErrorCollection
	{
		this.errors.clear();
		this.model.onErrorCollectionChange();

		return this;
	}

	hasErrors()
	{
		return this.errors.size > 0;
	}
}
