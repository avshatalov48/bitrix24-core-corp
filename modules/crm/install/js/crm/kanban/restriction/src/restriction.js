import { Reflection, Type } from 'main.core';

let instance = null;

export type Options = {
	isUniversalActivityScenarioEnabled: ?boolean,
	isLastActivityEnabled: ?boolean,
};

/**
 * @memberOf BX.CRM.Kanban
 */
export class Restriction
{
	#options: Options;

	static get Instance(): Restriction
	{
		if ((window.top !== window) && Reflection.getClass('top.BX.CRM.Kanban.Restriction'))
		{
			return window.top.BX.CRM.Kanban.Restriction;
		}

		if (!instance)
		{
			throw new Error('Restriction must be inited before use');
		}

		return instance;
	}

	static init(options: Options): void
	{
		if (instance)
		{
			console.warn('Attempt to re-init Restriction');

			return;
		}

		instance = new Restriction(options);
	}

	constructor(options: Options)
	{
		if (instance)
		{
			throw new Error('Restriction is a singleton, another instance exists already. Use Instance to access it');
		}

		this.#options = Type.isPlainObject(options) ? options : {};
	}

	isSortTypeChangeAvailable(): boolean
	{
		return (
			this.#isUniversalActivityScenarioEnabled()
			&& this.#isLastActivityEnabled()
		);
	}

	isLastActivityInfoInKanbanItemAvailable(): boolean
	{
		return (
			this.#isUniversalActivityScenarioEnabled()
			&& this.#isLastActivityEnabled()
		);
	}

	isTodoActivityCreateAvailable(): boolean
	{
		return this.#isUniversalActivityScenarioEnabled();
	}

	#isUniversalActivityScenarioEnabled(): boolean
	{
		if (Type.isBoolean(this.#options.isUniversalActivityScenarioEnabled))
		{
			return this.#options.isUniversalActivityScenarioEnabled;
		}

		return true;
	}

	#isLastActivityEnabled(): boolean
	{
		if (Type.isBoolean(this.#options.isLastActivityEnabled))
		{
			return this.#options.isLastActivityEnabled;
		}

		return true;
	}
}
