import type { ProgressBarRepository } from 'crm.autorun';
import { BatchRefreshAccountingDataManager, ProcessRegistry } from 'crm.autorun';
import { Text } from 'main.core';
import type { SettingsCollection } from 'main.core.collections';
import { showAnotherProcessRunningNotification } from '../../../utils';
import { BaseHandler } from '../base-handler';

export class ExecuteRefreshAccountingData extends BaseHandler
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
		return 'BatchManager:executeRefreshAccountingData';
	}

	injectDependencies(progressBarRepo: ProgressBarRepository, extensionSettings: SettingsCollection): void
	{
		this.#progressBarRepo = progressBarRepo;
	}

	execute(grid: BX.Main.grid, selectedIds: number[], forAll: boolean): void
	{
		let accountingManager = BatchRefreshAccountingDataManager.getItem(grid.getId());
		if (accountingManager && accountingManager.isRunning())
		{
			return;
		}

		if (ProcessRegistry.isProcessRunning(grid.getId()))
		{
			showAnotherProcessRunningNotification();

			return;
		}

		if (!accountingManager)
		{
			accountingManager = BatchRefreshAccountingDataManager.create(
				grid.getId(),
				{
					gridId: grid.getId(),
					entityTypeId: this.#entityTypeId,
					container: this.#progressBarRepo.getOrCreateProgressBarContainer('refresh-accounting-data').id,
				},
			);
		}

		if (forAll)
		{
			accountingManager.resetEntityIds();
		}
		else
		{
			accountingManager.setEntityIds(selectedIds);
		}

		accountingManager.execute();
	}
}
