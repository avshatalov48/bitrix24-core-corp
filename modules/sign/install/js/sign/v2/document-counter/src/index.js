import { BaseEvent, EventEmitter } from 'main.core.events';
import { CounterPanel, CounterItem } from 'ui.counterpanel';
import { Type } from 'main.core';
import { Filter } from './filter';

export class DocumentCounter extends CounterPanel
{
	#filter: Filter;

	constructor(options: Object)
	{
		super({
			target: options.target,
			items: DocumentCounter.getCounterItems(options.items),
			multiselect: false,
			title: options.title,
		});

		this.#filter = new Filter({
			filterId: options.filterId,
		});

		this.active = false;

		EventEmitter.subscribe('BX.UI.CounterPanel.Item:activate', this.#onActivateItem.bind(this));
		EventEmitter.subscribe('BX.UI.CounterPanel.Item:deactivate', this.#onDeactivateItem.bind(this));
		EventEmitter.subscribe('BX.Main.Filter:apply', this.#onFilterApply.bind(this));
	}

	static getCounterItems(items: Array)
	{
		return items.map((item) => {
			return {
				id: item.id,
				title: item.title,
				value: Number.parseInt(item.value, 10),
				isRestricted: item.isRestricted,
				color: item.color === 'THEME' ? 'GRAY' : item.color,
				hideValue: item.hideValue || false,
			};
		});
	}

	#onActivateItem(event: BaseEvent): void
	{
		const { name, value } = this.#getFieldData(event.getData());

		if (!this.#processItemSelection(name, value))
		{
			event.preventDefault();
		}
	}

	#onDeactivateItem(event: BaseEvent): void
	{
		if (this.#isAllDeactivated())
		{
			this.#filter.deactivate();
		}
	}

	#processItemSelection(name: string, value: string): boolean
	{
		this.#filter.toggleField(name, value);

		return true;
	}

	#getFieldData(item): Object
	{
		const fieldData = item.id.split('__');

		return {
			name: fieldData[0].toUpperCase(),
			value: fieldData[1].toUpperCase(),
		};
	}

	#isAllDeactivated(): Boolean
	{
		return this.getItems().every((record: CounterItem) => {
			return !record.isActive;
		});
	}

	#onFilterApply(): void
	{
		let compoundId = '';
		const filterRows = this.#filter.getFilterRows();
		const counterItemIds = new Set(this.items.map((item) => item.id.toLowerCase()));

		const activeField = Object.entries(filterRows).find((row) => {
			if (!Type.isPlainObject(row[1]))
			{
				return false;
			}

			const values = Object.values(row[1]);
			const result = [
				row[0],
				values.join('_'),
			];

			compoundId = result.join('__').toLowerCase();

			return counterItemIds.has(compoundId);
		});

		this.getItems().forEach((item) => {
			item.deactivate(false);
			if (activeField && (item.id.toLowerCase() === compoundId))
			{
				// eslint-disable-next-line no-param-reassign
				item.activate(false);
			}
		});
	}
}
