import {Type} from 'main.core';
import FormatFieldCollection from './format/formatfieldcollection';
import {AddressType} from "../core";

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
	#delimiter;
	#fieldForUnRecognized;

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
		this.#delimiter = props.delimiter || ', ';
		this.#fieldForUnRecognized = props.fieldForUnRecognized || AddressType.UNKNOWN;

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

	get delimiter(): string
	{
		return this.#delimiter;
	}

	set delimiter(delimiter: string)
	{
		this.#delimiter = delimiter;
	}

	get fieldForUnRecognized(): string
	{
		return this.#fieldForUnRecognized;
	}

	set fieldForUnRecognized(fieldForUnRecognized: number)
	{
		this.#fieldForUnRecognized = fieldForUnRecognized;
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