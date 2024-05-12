import { Type } from 'main.core';

export type ConfigItemData = {
	entityTypeId: number,
	active: boolean,
	enableSync: boolean,
	initData: Object,
	title: string,
};

/**
 * @memberOf BX.Crm.Conversion
 */
export class ConfigItem
{
	#active: boolean;
	#enableSync: boolean;
	#initData: Object = {};
	#entityTypeId: number;
	#title: string;

	constructor(params: ConfigItemData)
	{
		this.#entityTypeId = Number(params.entityTypeId);
		this.#active = this.#internalizeBooleanValue(params.active);
		this.#enableSync = this.#internalizeBooleanValue(params.enableSync);
		if (Type.isPlainObject(params.initData))
		{
			this.#initData = params.initData;
		}
		this.#title = String(params.title);
	}

	#internalizeBooleanValue(value: any): boolean
	{
		if (Type.isBoolean(value))
		{
			return value;
		}

		if (Type.isString(value))
		{
			return (value === 'Y');
		}

		return Boolean(value);
	}

	externalize(): Object
	{
		return {
			entityTypeId: this.getEntityTypeId(),
			title: this.getTitle(),
			initData: this.getInitData(),
			active: this.isActive() ? 'Y' : 'N',
			enableSync: this.isEnableSync() ? 'Y' : 'N',
		};
	}

	isActive(): boolean
	{
		return this.#active;
	}

	setActive(active: boolean): ConfigItem
	{
		this.#active = active;

		return this;
	}

	isEnableSync(): boolean
	{
		return this.#enableSync;
	}

	setEnableSync(enableSync: boolean): ConfigItem
	{
		this.#enableSync = enableSync;

		return this;
	}

	getInitData(): Object
	{
		return this.#initData || {};
	}

	setInitData(data: Object): ConfigItem
	{
		this.#initData = data;

		return this;
	}

	getEntityTypeId(): number
	{
		return this.#entityTypeId;
	}

	getTitle(): string
	{
		return this.#title;
	}

	setTitle(title: string): ConfigItem
	{
		this.#title = title;

		return this;
	}
}
