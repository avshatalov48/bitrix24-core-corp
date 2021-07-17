import {Type} from 'main.core';
import {EventEmitter} from 'main.core.events';

export class Filter
{
	constructor(options)
	{
		this.filter = BX.Main.filterManager.getById(options.filterId);

		this.init();
		this.bindEvents();
	}

	init()
	{
		this.updateFields();
	}

	bindEvents()
	{
		EventEmitter.subscribe('BX.Main.Filter:apply', this.onFilterApply.bind(this));
		EventEmitter.subscribe('Tasks.Toolbar:onItem', this.onCounterClick.bind(this));
	}

	onFilterApply()
	{
		this.updateFields();
	}

	updateFields()
	{
		this.fields = this.filter.getFilterFieldsValues();
	}

	onCounterClick(event)
	{
		const data = event.getData();

		if (data.counter && data.counter.filter)
		{
			this.toggleByField({[data.counter.filterField]: data.counter.filterValue});
		}
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
			this.filter.getApi().extendFilter({[name]: value});
			return;
		}

		this.filter.getFilterFields().forEach((field) => {
			if (field.getAttribute('data-name') === name)
			{
				this.filter.getFields().deleteField(field);
			}
		});

		this.filter.getSearch().apply();
	}
}