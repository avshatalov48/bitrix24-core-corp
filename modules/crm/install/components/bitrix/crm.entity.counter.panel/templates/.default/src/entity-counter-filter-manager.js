import { Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import EntityCounterType from './entity-counter-type';

export default class EntityCounterFilterManager
{
	static COUNTER_TYPE_FIELD = 'ACTIVITY_COUNTER';

	static EXCLUDED_FIELDS = [
		'FIND'
	];

	static FILTER_OTHER_USERS = 'other-users';

	#filterManager: BX.Main.Filter;
	#fields: Object;
	#isActive = true;

	constructor()
	{
		const filters = Type.isObject(BX.Main.filterManager) && BX.Main.filterManager.hasOwnProperty('getList')
			? BX.Main.filterManager.getList()
			: Object.values(BX.Main.filterManager.data);

		if (filters.length === 0)
		{
			console.warn('BX.Crm.EntityCounterFilter: Unable to define filter.');
			this.#isActive = false;
		}
		else
		{
			this.#filterManager = filters[0]; // use first filter to work
			this.#bindEvents();
			this.updateFields();
		}
	}

	#bindEvents(): void
	{
		EventEmitter.subscribe('BX.Main.Filter:apply', this.#onFilterApply.bind(this));
	}

	#onFilterApply(): void
	{
		this.updateFields();
	}

	#isFilteredByField(field: string): boolean
	{
		if (Type.isArray(this.#fields[field]))
		{
			return this.#fields[field].length > 0;
		}

		if (Type.isObject(this.#fields[field]))
		{
			return Object.values(this.#fields[field]).length > 0;
		}

		return this.#fields[field] !== '';
	}

	getManager(): BX.Main.filterManager
	{
		return this.#filterManager;
	}

	isActive(): boolean
	{
		return this.#isActive;
	}

	getFields(isFilterEmpty: boolean = false): Object
	{
		if (isFilterEmpty)
		{
			const filtered = Object.entries(this.#fields).filter(([field, value]) => this.#isFilteredByField(field));

			return Object.fromEntries(filtered);
		}

		return this.#fields;
	}

	getApi(): BX.Filter.Api
	{
		return this.#filterManager.getApi();
	}

	updateFields(): void
	{
		this.#fields = this.#filterManager.getFilterFieldsValues();
	}

	isFilteredByFieldEx(field: string): boolean
	{
		if (
			!Object.keys(this.#fields).includes(field)
			|| field.endsWith('_datesel')
			|| field.endsWith('_numsel')
			|| field.endsWith('_label')
		)
		{
			return false;
		}
		return this.#isFilteredByField(field);
	}
	
	isFiltered(
		userId: number,
		typeId: number,
		entityTypeId: number,
		isOtherUsersFilter: boolean,
		counterUserFieldName: string
	): boolean
	{
		if (userId === 0 || typeId === EntityCounterType.UNDEFINED)
		{
			return false;
		}

		const isFilteredByUser = this.isFilteredByFieldEx(counterUserFieldName)
			&& Type.isArray(this.#fields[counterUserFieldName])
			&& this.#fields[counterUserFieldName].length === 1
			&& (
				isOtherUsersFilter
					? this.#fields[counterUserFieldName][0] === EntityCounterFilterManager.FILTER_OTHER_USERS
					: parseInt(this.#fields[counterUserFieldName][0], 10) === userId
			)
		;

		const hasFilteredByTypeValue = this.isFilteredByFieldEx(EntityCounterFilterManager.COUNTER_TYPE_FIELD)
			&& Type.isObject(this.#fields[EntityCounterFilterManager.COUNTER_TYPE_FIELD])
		;

		const filteredTypeValues = hasFilteredByTypeValue
			? Object.values(this.#fields[EntityCounterFilterManager.COUNTER_TYPE_FIELD])
				.map((item) => parseInt(item, 10))
				.sort()
			: []
		;

		const isFilteredByType =
			(filteredTypeValues.length === 1 && filteredTypeValues[0] === typeId)
			|| (
				filteredTypeValues.length === 2
				&& typeId === EntityCounterType.CURRENT
				&& filteredTypeValues[0] === EntityCounterType.READY_TODO
				&& filteredTypeValues[1] === EntityCounterType.OVERDUE
			)
		;

		const counterFields = [
			counterUserFieldName,
			EntityCounterFilterManager.COUNTER_TYPE_FIELD,
			... EntityCounterFilterManager.EXCLUDED_FIELDS
		];

		const keysFields = Object.keys(this.#fields);
		const otherFields = counterFields
			.filter(item => !keysFields.includes(item))
			.concat(keysFields.filter(x => !counterFields.includes(x))); // exclude checked fields
		const isOtherFilterUsed = otherFields.some(item => this.isFilteredByFieldEx(item));

		return isFilteredByUser && isFilteredByType && !isOtherFilterUsed;
	}
}
