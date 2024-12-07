import type { ProgressBarRepository } from 'crm.autorun';
import { Reflection, Text, Type } from 'main.core';
import type { SettingsCollection } from 'main.core.collections';
import { BaseHandler } from './../base-handler';
import { saveEntitiesToSegment } from './internals/functions';

export class AddLetter extends BaseHandler
{
	#settings: SettingsCollection;

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
		return 'Sender:addLetter';
	}

	injectDependencies(progressBarRepo: ProgressBarRepository, extensionSettings: SettingsCollection)
	{
		this.#settings = extensionSettings;
	}

	execute(grid, selectedIds, forAll)
	{
		const letterCode = this.#valueElement.dataset.value;
		if (!Type.isStringFilled(letterCode))
		{
			return;
		}

		if (
			!this.#getAvailableLetterCodes().includes(letterCode)
			&& Reflection.getClass('BX.Sender.B24License')
		)
		{
			BX.Sender.B24License.showMailingPopup();

			return;
		}

		void saveEntitiesToSegment(
			null,
			this.#entityTypeId,
			selectedIds,
			forAll ? grid.getId() : null,
		).then(({ segment }) => {
			const url = this.#settings.get('sender.letterAddUrl')
				.replace('#code#', letterCode)
				.replace('#segment_id#', segment.id)
			;

			BX.SidePanel.Instance.open(url, { cacheable: false });
		});
	}

	#getAvailableLetterCodes(): string[]
	{
		return this.#settings.get('sender.availableLetterCodes') || [];
	}
}
