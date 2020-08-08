import {Type} from 'main.core';
import FormatFieldCollection from './format/formatfieldcollection';

/**
 * Class defines how the Address will look like
 */
export default class Format
{
	#code;
	#name;
	#description;
	#languageId;
	#template;
	#fieldCollection;

	constructor(props)
	{
		if(Type.isUndefined(props.languageId))
		{
			throw new TypeError('LanguageId must be defined');
		}

		this.#languageId = props.languageId;
		this.#code = props.code || '';
		this.#name = props.name || '';
		this.#template = props.template || '';
		this.#description = props.description || '';

		this.#fieldCollection = new FormatFieldCollection();

		if(Type.isObject(props.fieldCollection))
		{
			this.#fieldCollection.initFields(props.fieldCollection);
		}
	}

	get languageId(): string
	{
		return this.#languageId;
	}

	get name(): string
	{
		return this.#name;
	}

	get description(): string
	{
		return this.#description;
	}

	get code(): string
	{
		return this.#code;
	}

	get fieldCollection(): FormatFieldCollection
	{
		return this.#fieldCollection;
	}

	get template(): string
	{
		return this.#template;
	}

	set template(template: string): void
	{
		this.#template = template;
	}

	getField(type)
	{
		return this.#fieldCollection.getField(type);
	}

	isFieldExists(type)
	{
		return this.#fieldCollection.isFieldExists(type);
	}
}