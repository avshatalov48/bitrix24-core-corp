import { BaseEvent, EventEmitter } from 'main.core.events';
import { Type, Dom } from 'main.core';

type Props = {
	filterId: string,
};

export class Filter
{
	#props: Props;
	#filter: BX.Main.Filter;
	#MIN_QUERY_LENGTH = 3;
	#fields: any;

	constructor(props: Props)
	{
		this.#props = props;
		this.#init();
	}

	#init()
	{
		this.#filter = BX.Main.filterManager.getById(this.#props.filterId);
		this.#updateFields();
		this.#subscribeToEvents();
	}

	#updateFields()
	{
		this.#fields = this.#filter.getFilterFieldsValues();
	}

	#unSubscribeToEvents()
	{
		EventEmitter.unsubscribe('BX.Filter.Search:input', this.#inputFilterHandler.bind(this));
		EventEmitter.unsubscribe('BX.Main.Filter:apply', this.#applyFilterHandler.bind(this));
		EventEmitter.unsubscribe('Tasks.Toolbar:onItem', this.#counterClickHandler.bind(this));
	}

	#subscribeToEvents()
	{
		EventEmitter.subscribe('BX.Filter.Search:input', this.#inputFilterHandler.bind(this));
		EventEmitter.subscribe('BX.Main.Filter:apply', this.#applyFilterHandler.bind(this));
		EventEmitter.subscribe('Tasks.Toolbar:onItem', this.#counterClickHandler.bind(this));
	}

	#inputFilterHandler()
	{
		this.#setActive(this.isFilterActive());
	}

	#applyFilterHandler()
	{
		this.#updateFields();
	}

	#counterClickHandler(baseEvent: BaseEvent)
	{
		const data = baseEvent.getData();

		if (data.counter && data.counter.filter)
		{
			this.#toggleByField({ [data.counter.filterField]: data.counter.filterValue });
		}
	}

	hasFilteredFields()
	{
		const filteredFields = this.#filter.getFilterFieldsValues();
		const fields = Object.values(filteredFields);
		for (const field of fields)
		{
			if (this.isArrayFieldFiller(field) || this.isStringFieldFilled(field))
			{
				return true;
			}
		}

		return false;
	}

	isFilterActive()
	{
		const isPresetApplied = !['default_filter', 'tmp_filter'].includes(this.#filter.getPreset().getCurrentPresetId());
		const isSearchFilled = !this.isSearchEmpty();
		const hasFilledFields = this.hasFilteredFields();

		return isPresetApplied || isSearchFilled || hasFilledFields;
	}

	isArrayFieldFiller(field)
	{
		return Type.isArrayFilled(field);
	}

	isStringFieldFilled(field)
	{
		return field !== 'NONE' && Type.isStringFilled(field);
	}

	isSearchEmpty()
	{
		const query = this.#filter.getSearch().getSearchString();

		return !query || query.length < this.#MIN_QUERY_LENGTH;
	}

	#toggleByField(field)
	{
		const name = Object.keys(field)[0];
		const value = field[name];

		if (!this.#isFilteredByFieldValue(name, value))
		{
			this.#filter.getApi().extendFilter({ [name]: value });

			return;
		}

		this.#filter.getFilterFields().forEach((field) => {
			if (field.getAttribute('data-name') === name)
			{
				this.#filter.getFields().deleteField(field);
			}
		});

		this.#filter.getSearch().apply();
	}

	#isFilteredByFieldValue(field, value)
	{
		return this.#isFilteredByField(field) && this.#fields[field] === value;
	}

	#isFilteredByField(field)
	{
		if (!Object.keys(this.#fields).includes(field))
		{
			return false;
		}

		if (Type.isArray(this.#fields[field]))
		{
			return this.#fields[field].length > 0;
		}

		return this.#fields[field] !== '';
	}

	#setActive(isActive)
	{
		const wrap = this.#filter.popupBindElement;

		if (isActive)
		{
			Dom.removeClass(wrap, 'main-ui-filter-default-applied');
			Dom.addClass(wrap, 'main-ui-filter-search--showed');
		}
		else
		{
			Dom.addClass(wrap, 'main-ui-filter-default-applied');
			Dom.removeClass(wrap, 'main-ui-filter-search--showed');
		}
	}
}