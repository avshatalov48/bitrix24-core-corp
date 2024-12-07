import type { ProgressBarRepository } from 'crm.autorun';
import { BatchDeletionManager, ProcessRegistry } from 'crm.autorun';
import { Loc, Text } from 'main.core';
import type { SettingsCollection } from 'main.core.collections';
import { EventEmitter } from 'main.core.events';
import { UI } from 'ui.notification';
import { NOTIFICATION_AUTO_HIDE_DELAY, showAnotherProcessRunningNotification } from '../../../utils';
import { BaseHandler } from '../base-handler';

export class ExecuteDeletion extends BaseHandler
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
		return 'BatchManager:executeDeletion';
	}

	injectDependencies(progressBarRepo: ProgressBarRepository, extensionSettings: SettingsCollection): void
	{
		this.#progressBarRepo = progressBarRepo;
	}

	execute(grid: BX.Main.grid, selectedIds: number[], forAll: boolean): void
	{
		let deletionManager = BatchDeletionManager.getItem(grid.getId());
		if (deletionManager && deletionManager.isRunning())
		{
			return;
		}

		if (ProcessRegistry.isProcessRunning(grid.getId()))
		{
			showAnotherProcessRunningNotification();

			return;
		}

		if (!deletionManager)
		{
			deletionManager = BatchDeletionManager.create(
				grid.getId(),
				{
					gridId: grid.getId(),
					entityTypeId: this.#entityTypeId,
					container: this.#progressBarRepo.getOrCreateProgressBarContainer('delete').id,
				},
			);
		}

		if (forAll)
		{
			deletionManager.resetEntityIds();
		}
		else
		{
			deletionManager.setEntityIds(selectedIds);
		}

		deletionManager.execute();

		EventEmitter.subscribeOnce('BX.Crm.BatchDeletionManager:onProcessComplete', this.#notifyOnComplete.bind(this));
	}

	#notifyOnComplete(): void
	{
		UI.Notification.Center.notify({
			content: Loc.getMessage('CRM_ENTITY_LIST_PANEL_DELETION_ANALYTICS_WARNING'),
			actions:
				[
					{
						title: Loc.getMessage('CRM_ENTITY_LIST_PANEL_SHOW_DETAILS'),
						events: {
							click: (event, balloon) => {
								balloon.close();

								if (window.top.BX.Helper)
								{
									window.top.BX.Helper.show('redirect=detail&code=8969825');
								}
							},
						},
					},
				],
			autoHideDelay: NOTIFICATION_AUTO_HIDE_DELAY,
		});
	}
}
