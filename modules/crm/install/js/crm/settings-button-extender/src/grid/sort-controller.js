import { Text, Type } from 'main.core';
import { requireClass } from '../params-handling';
import { DefaultSort } from './default-sort';

export class SortController
{
	#entityTypeId: number;
	#grid: BX.Main.grid;

	constructor(entityTypeId: number, grid: BX.Main.grid)
	{
		this.#entityTypeId = Text.toInteger(entityTypeId);
		this.#grid = requireClass(grid, BX.Main.grid, 'grid');
	}

	isLastActivitySortSupported(): boolean
	{
		return this.#isColumnExists('LAST_ACTIVITY_TIME');
	}

	isLastActivitySortEnabled(): boolean
	{
		const options = this.#grid.getUserOptions().getCurrentOptions();

		const column = options.last_sort_by;
		const order = options.last_sort_order;

		return (
			column?.toLowerCase() === 'last_activity_time'
			&& order?.toLowerCase() === 'desc'
		);
	}

	toggleLastActivitySort(): void
	{
		if (this.isLastActivitySortEnabled())
		{
			this.#disableLastActivitySort();
		}
		else
		{
			this.#enableLastActivitySort();
		}
	}

	async #disableLastActivitySort(): Promise<void>
	{
		const sort = DefaultSort[this.#entityTypeId];

		let column: string;

		if (Type.isPlainObject(sort) && this.#isColumnExists(sort.column) && this.#isColumnSortable(sort.column))
		{
			column = sort.column;

			if (!this.#isColumnShowed(column))
			{
				await this.#showColumn(column);
			}

			this.#setSortOrder(column, sort.order);
		}
		else
		{
			// fist showed different sortable
			column = this.#getShowedColumnList().find((columnName) => {
				return (
					columnName !== 'LAST_ACTIVITY_TIME'
					&& this.#isColumnSortable(columnName)
				);
			});
		}

		this.#grid.sortByColumn(column);
	}

	async #enableLastActivitySort(): Promise<void>
	{
		if (!this.#isColumnShowed('LAST_ACTIVITY_TIME'))
		{
			await this.#showColumn('LAST_ACTIVITY_TIME');
		}

		this.#setSortOrder('LAST_ACTIVITY_TIME', 'desc');

		this.#grid.sortByColumn('LAST_ACTIVITY_TIME');
	}

	#isColumnExists(column: string): boolean
	{
		return this.#grid.getParam('COLUMNS_ALL', {}).hasOwnProperty(column);
	}

	#isColumnShowed(column: string): boolean
	{
		return this.#getShowedColumnList().includes(column);
	}

	#isColumnSortable(column: string): boolean
	{
		const columnParams = this.#grid.getColumnByName(column);

		return !!(columnParams && columnParams.sort !== false);
	}

	#getShowedColumnList(): string[]
	{
		return this.#grid.getSettingsWindow().getShowedColumns();
	}

	#setSortOrder(column: string, order: 'asc' | 'desc'): void
	{
		this.#grid.getColumnByName(column).sort_order = order;
	}

	#showColumn(column: string): Promise<void, string>
	{
		return new Promise((resolve, reject) => {
			if (!this.#isColumnExists(column))
			{
				reject(new Error(`Column ${column} does not exists`));

				return;
			}

			if (this.#isColumnShowed(column))
			{
				reject(new Error(`Column ${column} is showed already`));

				return;
			}

			this.#grid.getSettingsWindow().select(column);

			const showedColumns = this.#getShowedColumnList();
			showedColumns.push(column);

			this.#grid.getSettingsWindow().saveColumns(showedColumns, resolve);
		});
	}
}
