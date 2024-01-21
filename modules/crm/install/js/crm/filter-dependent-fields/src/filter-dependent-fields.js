import { Type } from 'main.core';
import { ActivityFastSearchMutator } from './activity-fast-search-mutator';
import type { FilterFieldMutator } from './filter-field-mutator';

/**
 * FilterDependentFields extension provide functionality to manipulate filter fields before then send to the backend.
 * You can create your own mutator and add it to the list. Just implement the FilterFieldMutator interface and
 * add instance to the #mutators array.
 */
export class FilterDependentFields
{
	#mutators: FilterFieldMutator[] = [];

	#oldFields: {[k: string]: any} = '';

	initialize(): void
	{
		BX.Event.EventEmitter.subscribe('BX.Main.Filter:beforeApply', this.#onBeforeApply.bind(this));

		this.#mutators = [
			new ActivityFastSearchMutator(),
		];

		const filterManager = this.getFilterManager();
		if (filterManager)
		{
			this.#oldFields = filterManager.getFilterFieldsValues();
		}
	}

	#onBeforeApply(event)
	{
		const filterManager = this.getFilterManager();

		if (!filterManager)
		{
			return;
		}

		const api = filterManager.getApi();

		let currentFields = filterManager.getFilterFieldsValues();

		let isFilterModified = false;

		for (const mutator of this.#mutators)
		{
			let hasChanges = false;
			[currentFields, hasChanges] = mutator.mutate(currentFields, this.#oldFields);

			if (hasChanges)
			{
				isFilterModified = true;
			}
		}

		if (!isFilterModified)
		{
			return;
		}
		api.setFields(currentFields);

		this.#oldFields = currentFields;
	}

	getFilterManager(): ?BX.Main.Filter
	{
		if (!Type.isObject(BX.Main.filterManager || !Type.isFunction(BX.Main.filterManager)))
		{
			return null;
		}

		const filters = Object.prototype.hasOwnProperty.call(BX.Main.filterManager, 'getList')
			? BX.Main.filterManager.getList()
			: Object.values(BX.Main.filterManager.data);

		return (Type.isArray(filters) && filters.length > 0) ? filters[0] : null;
	}
}
