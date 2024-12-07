import { BatchSetOpenedManager, ProcessRegistry, type ProgressBarRepository } from 'crm.autorun';
import { Text, Type } from 'main.core';
import type { SettingsCollection } from 'main.core.collections';
import { showAnotherProcessRunningNotification } from '../../../utils';
import { BaseHandler } from '../base-handler';

export class ExecuteSetOpened extends BaseHandler
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
		return 'BatchManager:executeSetOpened';
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
		let openedManager = BatchSetOpenedManager.getItem(grid.getId());
		if (!openedManager)
		{
			openedManager = BatchSetOpenedManager.create(
				grid.getId(),
				{
					gridId: grid.getId(),
					entityTypeId: this.#entityTypeId,
					container: this.#progressBarRepo.getOrCreateProgressBarContainer('set-opened').id,
				},
			);
		}

		if (openedManager.isRunning())
		{
			return;
		}

		if (ProcessRegistry.isProcessRunning(grid.getId()))
		{
			showAnotherProcessRunningNotification();

			return;
		}

		const isOpened = this.#valueElement.dataset.value;
		if (isOpened !== 'Y' && isOpened !== 'N')
		{
			console.error('Invalid isOpened in value element', isOpened, this);

			return;
		}

		openedManager.setIsOpened(isOpened);

		if (forAll)
		{
			openedManager.resetEntityIds();
		}
		else
		{
			openedManager.setEntityIds(selectedIds);
		}

		openedManager.execute();
	}
}
