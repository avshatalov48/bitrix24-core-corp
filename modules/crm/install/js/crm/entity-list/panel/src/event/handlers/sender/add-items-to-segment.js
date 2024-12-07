import { Text, Type } from 'main.core';
import { UI } from 'ui.notification';
import { NOTIFICATION_AUTO_HIDE_DELAY } from '../../../utils';
import { BaseHandler } from './../base-handler';
import { saveEntitiesToSegment } from './internals/functions';

export class AddItemsToSegment extends BaseHandler
{
	#entityTypeId: number;
	#valueElement: HTMLElement;

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
		return 'Sender:addItemsToSegment';
	}

	execute(grid, selectedIds, forAll)
	{
		const segmentId = Text.toInteger(this.#valueElement.dataset.value);

		grid.disableActionsPanel();

		void saveEntitiesToSegment(
			segmentId <= 0 ? null : segmentId,
			this.#entityTypeId,
			selectedIds,
			forAll ? grid.getId() : null,
		).then(({ segment }) => {
			if (segment.textSuccess)
			{
				UI.Notification.Center.notify({
					content: segment.textSuccess,
					autoHide: true,
					autoHideDelay: NOTIFICATION_AUTO_HIDE_DELAY,
				});
			}
		}).finally(() => grid.enableActionsPanel());
	}
}
