import { BatchSetExportManager, ProcessRegistry, type ProgressBarRepository } from 'crm.autorun';
import { Text, Type } from 'main.core';
import type { SettingsCollection } from 'main.core.collections';
import { showAnotherProcessRunningNotification } from '../../../utils';
import { BaseHandler } from '../base-handler';

export class ExecuteSetExport extends BaseHandler
{
	#entityTypeId: number;
	#valueElement: HTMLElement;

	#progressBarRepo: ProgressBarRepository;

	constructor({ entityTypeId, valueElementId })
	{
		super();

		this.#entityTypeId = Text.toInteger(entityTypeId);
		if (!BX.CrmEntityType.isDefined(this.#entityTypeId))
		{
			throw new Error('entityTypeId is required');
		}

		this.#valueElement = document.getElementById(valueElementId);
		if (!Type.isElementNode(this.#valueElement))
		{
			throw new Error('value element not found');
		}
	}

	static getEventName(): string
	{
		return 'BatchManager:executeSetExport';
	}

	injectDependencies(
		progressBarRepo: ProgressBarRepository,
		extensionSettings: SettingsCollection,
	): void
	{
		this.#progressBarRepo = progressBarRepo;
	}

	execute(grid: BX.Main.grid, selectedIds: number[], forAll: boolean): void
	{
		let setExportManager = BatchSetExportManager.getItem(grid.getId());
		if (!setExportManager)
		{
			setExportManager = BatchSetExportManager.create(
				grid.getId(),
				{
					gridId: grid.getId(),
					entityTypeId: this.#entityTypeId,
					container: this.#progressBarRepo.getOrCreateProgressBarContainer('set-export').id,
				},
			);
		}

		if (setExportManager.isRunning())
		{
			return;
		}

		if (ProcessRegistry.isProcessRunning(grid.getId()))
		{
			showAnotherProcessRunningNotification();

			return;
		}

		const isExport = this.#valueElement.dataset.value;
		if (isExport !== 'Y' && isExport !== 'N')
		{
			console.error('Invalid isExport in value element', isExport, this);

			return;
		}

		setExportManager.setExport(isExport);

		if (forAll)
		{
			setExportManager.resetEntityIds();
		}
		else
		{
			setExportManager.setEntityIds(selectedIds);
		}

		setExportManager.execute();
	}
}
