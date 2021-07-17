import {Type} from 'main.core';
import {EventEmitter} from 'main.core.events';

class Filter
{
	constructor(options)
	{
		this.filterId = options.filterId;
		this.filterManager = BX.Main.filterManager.getById(this.filterId);

		this.bindEvents();
		this.updateFields();
	}

	bindEvents()
	{
		EventEmitter.subscribe('BX.Main.Filter:apply', this.onFilterApply.bind(this));
	}

	onFilterApply()
	{
		this.updateFields();
	}

	updateFields()
	{
		this.fields = this.filterManager.getFilterFieldsValues();
	}

	isFilteredByField(field)
	{
		if (!Object.keys(this.fields).includes(field))
		{
			return false;
		}

		if (Type.isArray(this.fields[field]))
		{
			return this.fields[field].length > 0;
		}

		return this.fields[field] !== '';
	}

	isFilteredByFieldValue(field, value)
	{
		return this.isFilteredByField(field) && this.fields[field] === value;
	}

	toggleByField(field)
	{
		const name = Object.keys(field)[0];
		const value = field[name];

		if (!this.isFilteredByFieldValue(name, value))
		{
			this.filterManager.getApi().extendFilter(
				{[name]: value},
				false,
				{COUNTER_TYPE: 'TASKS_COUNTER_TYPE_' + value}
			);
			return;
		}

		this.filterManager.getFilterFields().forEach((field) => {
			if (field.getAttribute('data-name') === name)
			{
				this.filterManager.getFields().deleteField(field);
			}
		});

		this.filterManager.getSearch().apply();
	}

	getFilter()
	{
		return this.filterManager;
	}
}

export {
	Filter
}