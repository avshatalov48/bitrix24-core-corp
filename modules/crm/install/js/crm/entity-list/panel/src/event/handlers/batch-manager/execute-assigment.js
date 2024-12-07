import { BatchAssignmentManager, ProcessRegistry, type ProgressBarRepository } from 'crm.autorun';
import { Loc, Text, Type } from 'main.core';
import type { SettingsCollection } from 'main.core.collections';
import { UI } from 'ui.notification';
import { NOTIFICATION_AUTO_HIDE_DELAY, showAnotherProcessRunningNotification } from '../../../utils';
import { BaseHandler } from '../base-handler';

export class ExecuteAssigment extends BaseHandler
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
		return 'BatchManager:executeAssigment';
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
		let assignManager = BatchAssignmentManager.getItem(grid.getId());
		if (!assignManager)
		{
			assignManager = BatchAssignmentManager.create(
				grid.getId(),
				{
					gridId: grid.getId(),
					entityTypeId: this.#entityTypeId,
					container: this.#progressBarRepo.getOrCreateProgressBarContainer('assign').id,
				},
			);
		}

		if (assignManager.isRunning())
		{
			return;
		}

		if (ProcessRegistry.isProcessRunning(grid.getId()))
		{
			showAnotherProcessRunningNotification();

			return;
		}

		const userId = Text.toInteger(this.#valueElement.dataset.value);
		if (userId <= 0)
		{
			UI.Notification.Center.notify({
				content: Loc.getMessage('CRM_ENTITY_LIST_PANEL_SELECT_ASSIGNED_BY_ID'),
				autoHide: true,
				autoHideDelay: NOTIFICATION_AUTO_HIDE_DELAY,
			});

			return;
		}

		assignManager.setAssignedById(userId);

		if (forAll)
		{
			assignManager.resetEntityIds();
		}
		else
		{
			assignManager.setEntityIds(selectedIds);
		}

		assignManager.execute();
	}
}
