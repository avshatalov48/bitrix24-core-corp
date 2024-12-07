import type { ProgressBarRepository } from 'crm.autorun';
import { BatchExclusionManager, ProcessRegistry } from 'crm.autorun';
import { Text } from 'main.core';
import type { SettingsCollection } from 'main.core.collections';
import { showAnotherProcessRunningNotification } from '../../../utils';
import { BaseHandler } from '../base-handler';

export class ExecuteExclusion extends BaseHandler
{
	#entityTypeId: number;

	#progressBarRepo: ProgressBarRepository;

	constructor({ entityTypeId })
	{
		super();

		this.#entityTypeId = Text.toInteger(entityTypeId);
		if (!BX.CrmEntityType.isDefined(this.#entityTypeId))
		{
			throw new Error('entityTypeId is required');
		}
	}

	static getEventName(): string
	{
		return 'BatchManager:executeExclusion';
	}

	injectDependencies(progressBarRepo: ProgressBarRepository, extensionSettings: SettingsCollection): void
	{
		this.#progressBarRepo = progressBarRepo;
	}

	execute(grid: BX.Main.grid, selectedIds: number[], forAll: boolean): void
	{
		let exclusionManager = BatchExclusionManager.getItem(grid.getId());
		if (exclusionManager && exclusionManager.isRunning())
		{
			return;
		}

		if (ProcessRegistry.isProcessRunning(grid.getId()))
		{
			showAnotherProcessRunningNotification();

			return;
		}

		if (!exclusionManager)
		{
			exclusionManager = BatchExclusionManager.create(
				grid.getId(),
				{
					gridId: grid.getId(),
					entityTypeId: this.#entityTypeId,
					container: this.#progressBarRepo.getOrCreateProgressBarContainer('exclude').id,
				},
			);
		}

		if (forAll)
		{
			exclusionManager.resetEntityIds();
		}
		else
		{
			exclusionManager.setEntityIds(selectedIds);
		}

		exclusionManager.execute();
	}
}
