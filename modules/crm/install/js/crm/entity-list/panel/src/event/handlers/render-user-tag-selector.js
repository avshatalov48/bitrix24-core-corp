import { Type } from 'main.core';
import type { BaseEvent } from 'main.core.events';
import { TagSelector } from 'ui.entity-selector';
import { BaseHandler } from './base-handler';

export class RenderUserTagSelector extends BaseHandler
{
	#targetElement: HTMLElement;

	constructor({ targetElementId })
	{
		super();

		this.#targetElement = document.getElementById(targetElementId);
		if (!Type.isElementNode(this.#targetElement))
		{
			throw new Error('target element not found');
		}
	}

	static getEventName(): string
	{
		return 'renderUserTagSelector';
	}

	execute(grid: BX.Main.grid, selectedIds: number[], forAll: boolean)
	{
		const tagSelector = new TagSelector({
			multiple: false,
			dialogOptions: {
				context: `crm.entity-list.${RenderUserTagSelector.getEventName()}.${grid.getId()}`,
				entities: [
					{ id: 'user' },
				],
			},
			events: {
				onTagAdd: (event: BaseEvent) => {
					const { tag } = event.getData();

					this.#targetElement.dataset.value = String(tag.getId());
				},
				onTagRemove: (event: BaseEvent) => {
					const { tag } = event.getData();

					if (String(this.#targetElement.dataset.value) === String(tag.getId()))
					{
						delete this.#targetElement.dataset.value;
					}
				},
			},
		});

		tagSelector.renderTo(this.#targetElement);
	}
}
