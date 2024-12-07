import { BatchMergeManager } from 'crm.merger.batchmergemanager';
import { Text, Type } from 'main.core';
import { BaseHandler } from '../base-handler';

export class ExecuteMerge extends BaseHandler
{
	#entityTypeId: number;
	#mergerUrl: ?string;

	constructor({ entityTypeId, mergerUrl })
	{
		super();

		this.#entityTypeId = Text.toInteger(entityTypeId);
		this.#mergerUrl = Type.isStringFilled(mergerUrl) ? mergerUrl : null;

		if (!BX.CrmEntityType.isDefined(this.#entityTypeId))
		{
			throw new Error('entityTypeId is required');
		}
	}

	static getEventName(): string
	{
		return 'BatchManager:executeMerge';
	}

	execute(grid: BX.Main.grid, selectedIds: number[], forAll: boolean): void
	{
		let mergeManager = BatchMergeManager.getItem(grid.getId());
		if (!mergeManager)
		{
			mergeManager = BatchMergeManager.create(
				grid.getId(),
				{
					gridId: grid.getId(),
					entityTypeId: this.#entityTypeId,
					mergerUrl: this.#mergerUrl,
				},
			);
		}

		if (!mergeManager.isRunning() && selectedIds.length > 1)
		{
			mergeManager.setEntityIds(selectedIds);
			mergeManager.execute();
		}
	}
}
