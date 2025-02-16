import { BatchObserversManager, ProcessRegistry, type ProgressBarRepository } from 'crm.autorun';
import { UI } from 'ui.notification';
import { NOTIFICATION_AUTO_HIDE_DELAY, showAnotherProcessRunningNotification } from '../../../utils';
import { BaseHandler } from '../base-handler';
import { Dom, Loc, Text, Type } from 'main.core';

export class ExecuteObservers extends BaseHandler
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
		return 'BatchManager:executeObservers';
	}

	injectDependencies(
		progressBarRepo: ProgressBarRepository,
		extensionSettings: SettingsCollection,
	): void
	{
		this.#progressBarRepo = progressBarRepo;
	}

	execute(grid, selectedIds, forAll)
	{
		let observersManager = BatchObserversManager.getItem(grid.getId());
		if (!observersManager)
		{
			observersManager = BatchObserversManager.create(
				grid.getId(),
				{
					gridId: grid.getId(),
					entityTypeId: this.#entityTypeId,
					container: this.#progressBarRepo.getOrCreateProgressBarContainer('observers').id,
				},
			);
		}

		if (observersManager.isRunning())
		{
			return;
		}

		if (ProcessRegistry.isProcessRunning(grid.getId()))
		{
			showAnotherProcessRunningNotification();

			return;
		}

		let userIdList = Dom.attr(this.#valueElement, 'data-observers');
		if (Type.isNull(userIdList))
		{
			userIdList = '';
		}
		userIdList = userIdList.toString().split(',').map(Number).filter(Boolean);
		if (!Type.isArrayFilled(userIdList))
		{
			UI.Notification.Center.notify({
				content: Loc.getMessage('CRM_ENTITY_LIST_PANEL_SELECT_OBSERVERS_BY_ID'),
				autoHide: true,
				autoHideDelay: NOTIFICATION_AUTO_HIDE_DELAY,
			});

			return;
		}

		observersManager.setObserverIdList(userIdList);

		if (forAll)
		{
			observersManager.resetEntityIds();
		}
		else
		{
			observersManager.setEntityIds(selectedIds);
		}

		observersManager.execute();
	}
}
