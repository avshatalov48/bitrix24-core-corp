import { Type, Event } from 'main.core';
import type { FilterFieldsOptions } from './options';

export class FilterFieldsRestriction
{
	constructor(options: FilterFieldsOptions)
	{
		this.options = options;
		this.bindAddFilterItemEvent();
		this.bindGridSortEvent();
	}

	bindAddFilterItemEvent()
	{
		const filterId = this.options.filterId ?? null;
		if (filterId && BX.Main.filterManager)
		{
			const filter = BX.Main.filterManager.getById(filterId);
			if (filter)
			{
				filter.getEmitter().subscribe(
					'onBeforeAddFilterItem',
					(event) => {
						const eventData = event.getData();
						if (eventData.hasOwnProperty('NAME') && this.isRestrictedFilterField(eventData.NAME))
						{
							event.preventDefault();
							this.callRestrictionCallback();
						}
					}
				);
			}
		}
	}

	bindGridSortEvent()
	{
		const gridId = this.options.gridId ?? null;
		if (gridId && BX.Main.gridManager)
		{
			Event.EventEmitter.subscribe(
				'BX.Main.grid:onBeforeSort',
				(event) => {
					const {grid, columnName} = event.getData();
					if (grid.getId() === gridId && this.isRestrictedGridField(columnName))
					{
						event.preventDefault();
						this.callRestrictionCallback();
					}
				}
			);
		}
	}

	isRestrictedFilterField(fieldName: string): boolean
	{
		const fields = this.options.filterFields ?? [];

		return (
			Type.isArray(fields)
			&& fields.indexOf(fieldName) > -1
		);
	}

	isRestrictedGridField(fieldName: string): boolean
	{
		const fields = this.options.gridFields ?? [];

		return (
			Type.isArray(fields)
			&& fields.indexOf(fieldName) > -1
		);
	}

	callRestrictionCallback()
	{
		if (Type.isStringFilled(this.options.callback))
		{
			eval(this.options.callback);
		}
	}
}
