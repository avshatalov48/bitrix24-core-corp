import type { ProgressBarRepository } from 'crm.autorun';
import { BatchRestartAutomationManager, ProcessRegistry } from 'crm.autorun';
import { Text } from 'main.core';
import type { SettingsCollection } from 'main.core.collections';
import { showAnotherProcessRunningNotification } from '../../../utils';
import { BaseHandler } from '../base-handler';

export class ExecuteRestartAutomation extends BaseHandler
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
		return 'BatchManager:restartAutomation';
	}

	injectDependencies(progressBarRepo: ProgressBarRepository, extensionSettings: SettingsCollection): void
	{
		this.#progressBarRepo = progressBarRepo;
	}

	execute(grid: BX.Main.grid, selectedIds: number[], forAll: boolean): void
	{
		let restartAutomationManager = BatchRestartAutomationManager.getItem(grid.getId());
		if (restartAutomationManager && restartAutomationManager.isRunning())
		{
			return;
		}

		if (ProcessRegistry.isProcessRunning(grid.getId()))
		{
			showAnotherProcessRunningNotification();

			return;
		}

		if (!restartAutomationManager)
		{
			restartAutomationManager = BatchRestartAutomationManager.create(
				grid.getId(),
				{
					gridId: grid.getId(),
					entityTypeId: this.#entityTypeId,
					container: this.#progressBarRepo.getOrCreateProgressBarContainer('restartAutomation').id,
				},
			);
		}

		if (forAll)
		{
			restartAutomationManager.resetEntityIds();
		}
		else
		{
			restartAutomationManager.setEntityIds(selectedIds);
		}

		restartAutomationManager.execute();
	}
}
