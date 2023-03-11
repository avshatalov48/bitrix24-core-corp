import {Text, Type} from "main.core";
import {TaskOption} from "./task-option";
import {EventEmitter} from "main.core.events";
import {ErrorCollection} from "./error-collection";
import {FieldCollection} from "./field-collection";

class TaskModel
{
	#fieldCollection = null;
	#errorCollection = null;

	constructor(options: TaskOption = {})
	{
		this.options = options || {};

		this.#errorCollection = new ErrorCollection(this);
		this.#fieldCollection = new FieldCollection(this);

		if (Type.isObject(options.fields))
		{
			this.initFields(options.fields, false);
		}
	}

	getErrorCollection(): ErrorCollection
	{
		return this.#errorCollection;
	}

	getFields(): {}
	{
		return this.#fieldCollection.getFields();
	}

	getField(fieldName: string): any
	{
		return this.#fieldCollection.getField(fieldName);
	}

	setField(fieldName: string, value: any): TaskModel
	{
		this.#fieldCollection.setField(fieldName, value);

		return this;
	}

	setFields(fields): ProductModel
	{
		Object.keys(fields).forEach((key) => {
			this.setField(key, fields[key]);
		});

		return this;
	}

	initFields(fields: {}): TaskModel
	{
		this.#fieldCollection.initFields(fields);

		return this;
	}

	removeField(fieldName): TaskModel
	{
		this.#fieldCollection.removeField(fieldName);

		return this;
	}

	isChanged(): boolean
	{
		return this.#fieldCollection.isChanged();
	}

	getId()
	{
		return this.getField('id');
	}
}

export
{
	TaskModel
}