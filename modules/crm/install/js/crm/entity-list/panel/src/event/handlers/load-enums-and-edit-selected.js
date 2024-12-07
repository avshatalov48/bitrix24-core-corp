import { ajax as Ajax, Text, Type } from 'main.core';
import { type BaseEvent, EventEmitter } from 'main.core.events';
import { BaseHandler } from './base-handler';

export class LoadEnumsAndEditSelected extends BaseHandler
{
	#entityTypeId: number;
	#categoryId: ?number = null;

	static #loadedFieldsCache: Map<string, Set<string>> = new Map();

	constructor({ entityTypeId, categoryId })
	{
		super();

		this.#entityTypeId = Text.toInteger(entityTypeId);
		if (!BX.CrmEntityType.isDefined(this.#entityTypeId))
		{
			throw new Error('entityTypeId is required');
		}

		if (!Type.isNil(categoryId))
		{
			this.#categoryId = Text.toInteger(categoryId);
		}
	}

	static getEventName(): string
	{
		return 'loadEnumsAndEditSelected';
	}

	execute(grid, selectedIds, forAll): void
	{
		void LoadEnumsAndEditSelected
			.loadEnums(grid, this.#entityTypeId, this.#categoryId)
			.finally(() => grid.editSelected())
		;
	}

	static loadEnums(grid: BX.Main.grid, entityTypeId: number, categoryId: ?number): Promise<void>
	{
		const fieldNames = this.#getEmptyItemsFieldNames(grid);
		if (fieldNames.length === 0)
		{
			return Promise.resolve();
		}

		return new Promise((resolve, reject) => {
			Ajax.runAction('crm.controller.list.userField.getData', {
				data: {
					entityTypeId,
					fieldNames,
					categoryId,
				},
			}).then(({ data: { fields } }) => {
				const alreadyLoaded = this.#getAlreadyLoadedFieldNames(grid.getId());

				for (const cell of this.#getCells(grid))
				{
					const { name } = cell.dataset;
					if (!fields[name])
					{
						continue;
					}

					cell.dataset.edit = `(${fields[name]})`;
					alreadyLoaded.add(name);
				}

				resolve();
			}).catch((response) => {
				console.error(
					'Could not load UF enum values for edit',
					{ response, grid, entityTypeId, categoryId, fieldNames },
				);

				reject();
			});
		});
	}

	static #getEmptyItemsFieldNames(grid: BX.Main.grid): string[]
	{
		const columnsAll = grid.getParam('COLUMNS_ALL');
		const alreadyLoaded = this.#getAlreadyLoadedFieldNames(grid.getId());

		const fields = [];

		for (const cell of this.#getCells(grid))
		{
			const name = cell.dataset.name ?? null;
			const columnData = columnsAll[name];

			const isListColumnWithEmptyData = (
				Type.isObjectLike(columnData?.editable)
				&& !columnData.editable.DATA
				&& columnData.type === 'list'
			);

			if (isListColumnWithEmptyData && !alreadyLoaded.has(name))
			{
				fields.push(name);
			}
		}

		return fields;
	}

	static #getAlreadyLoadedFieldNames(gridId: string): Set<string>
	{
		if (!this.#loadedFieldsCache.has(gridId))
		{
			this.#loadedFieldsCache.set(gridId, new Set());
		}

		return this.#loadedFieldsCache.get(gridId);
	}

	static #getCells(grid: BX.Main.grid): HTMLElement[]
	{
		const { cells } = grid.getRows().getHeadFirstChild().getNode();

		return [...cells];
	}

	/**
	 * @internal
	 */
	static onAfterGridUpdate(grid: BX.Main.grid): void
	{
		this.#loadedFieldsCache.delete(grid.getId());
	}
}

EventEmitter.subscribe('Grid::updated', (event: BaseEvent) => {
	const [grid] = event.getData();

	LoadEnumsAndEditSelected.onAfterGridUpdate(grid);
});
