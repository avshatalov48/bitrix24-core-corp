import { Dialog, Item, type DialogOptions } from 'ui.entity-selector';

export { Item };
export type ItemId = [string, number];

export class EntitySelectorDialog extends Dialog
{
	constructor(dialogOptions: DialogOptions)
	{
		const minHeight = 280;
		const minTagSelectorHeight = 34;
		const options = {
			tagSelectorOptions: {
				maxHeight: minTagSelectorHeight * 2,
			},
			...dialogOptions,
			height: Math.max(minHeight, dialogOptions.height ?? (window.innerHeight / 2 - minTagSelectorHeight)),
			offsetAnimation: false,
		};

		super(options);
	}

	showTo(targetNode: HTMLElement): void
	{
		this.getPopup();
		this.setTargetNode(targetNode);
		this.show();
	}

	selectItemsByIds(items: ItemId[]): void
	{
		this.getItems().forEach((item: Item) => {
			const isSelected = this.#inIds(item, items);

			if (isSelected)
			{
				item.select(true);
			}

			if (!isSelected)
			{
				item.deselect(true);
			}
		});
	}

	setSelectableByIds({ selectable, unselectable }: { selectable: ItemId[], unselectable: ItemId[] }): void
	{
		this.getItems().forEach((item: Item) => {
			if (this.#inIds(item, selectable))
			{
				item.setDeselectable(true);
			}

			if (this.#inIds(item, unselectable))
			{
				item.setDeselectable(false);
			}
		});
	}

	getItemsByIds(items: ItemId[]): Item[]
	{
		return this.getItems().filter((item: Item) => this.#inIds(item, items));
	}

	#inIds(item: Item, items: ItemId[]): boolean
	{
		const itemId = [item.getEntityId(), item.getId()];

		return items.some((it) => itemId[0] === it[0] && itemId[1] === it[1]);
	}
}
