import { Type as SortType } from "./type";
import { Type } from "main.core";

/**
 * @memberOf BX.CRM.Kanban.Sort.Settings
 */
export class Settings
{
	#supportedTypes: string[];
	#currentType: string;

	constructor(supportedTypes: string[], currentType: string)
	{
		supportedTypes = Type.isArray(supportedTypes) ? supportedTypes : [];
		this.#supportedTypes = supportedTypes.filter((type) => SortType.isDefined(type));

		if (this.#supportedTypes.length <= 0)
		{
			throw new Error('No valid supported types provided');
		}

		if (!Type.isString(currentType) || !SortType.isDefined(currentType))
		{
			throw new Error('currentType is not a valid sort type');
		}
		if (!this.#supportedTypes.includes(currentType))
		{
			throw new Error('currentType is not supported')
		}

		this.#currentType = currentType;
	}

	getSupportedTypes(): string[]
	{
		return this.#supportedTypes;
	}

	isTypeSupported(sortType: string): boolean
	{
		return this.#supportedTypes.includes(sortType);
	}

	getCurrentType(): string
	{
		return this.#currentType;
	}

	static createFromJson(json: string): Settings
	{
		const {supportedTypes, currentType} = JSON.parse(json);

		return new Settings(supportedTypes, currentType);
	}
}
