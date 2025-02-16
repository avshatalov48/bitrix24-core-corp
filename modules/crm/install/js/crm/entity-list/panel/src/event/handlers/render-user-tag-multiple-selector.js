import { Dom, Type } from 'main.core';
import { TagSelector } from 'ui.entity-selector';
import { BaseHandler } from './base-handler';

export class RenderUserTagMultipleSelector extends BaseHandler
{
	#targetElement: HTMLElement;
	#tagSelector: TagSelector;
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
		return 'renderUserTagMultipleSelector';
	}

	execute(grid: BX.Main.grid, selectedIds: number[], forAll: boolean)
	{
		this.#tagSelector = new TagSelector({
			multiple: true,
			dialogOptions: {
				context: `crm.entity-list.${RenderUserTagMultipleSelector.getEventName()}.${grid.getId()}`,
				entities: [
					{ id: 'user' },
				],
			},
			events: {
				onTagAdd: () => {
					this.updateDatasetValue();
				},
				onTagRemove: () => {
					this.updateDatasetValue();
				},
			},
		});

		this.#tagSelector.renderTo(this.#targetElement);
	}

	updateDatasetValue(): void
	{
		const tags = this.#tagSelector.getTags();
		Dom.attr(this.#targetElement, 'data-observers', tags.map((tag) => tag.id).toString());
	}
}
