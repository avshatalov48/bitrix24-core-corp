import { Tag, Dom } from 'main.core';
import { Dialog, type ItemOptions } from 'ui.entity-selector';
import './style.css';

export type { ItemOptions };

export class SignDropdown
{
	#dom: HTMLElement;
	#selector: Dialog;
	#selectedItemId: string = '';

	constructor(dialogOptions = {})
	{
		const { className, withCaption } = dialogOptions;
		const titleNode = withCaption
			? Tag.render`
				<div class="sign-b2e-dropdown__text">
					<span class="sign-b2e-dropdown__text_title"></span>
					<span class="sign-b2e-dropdown__text_caption"></span>
				</div>
			`
			: Tag.render`<span class="sign-b2e-dropdown__text"></span>`;
		this.#dom = Tag.render`
			<div
				class="sign-b2e-dropdown"
				onclick="${() => {
					this.#selector.show();
				}}"
			>
				${titleNode}
				<span class="sign-b2e-dropdown__btn"></span>
			</div>
		`;
		this.#selector = new Dialog({
			targetNode: this.#dom,
			width: 500,
			height: 350,
			showAvatars: false,
			dropdownMode: true,
			multiple: false,
			enableSearch: true,
			hideOnSelect: true,
			events: {
				'Item:OnSelect': ({ data }) => this.#onSelect(data.item),
			},
			...dialogOptions,
		});
		if (className)
		{
			const container = this.#selector.getContainer();
			Dom.addClass(container, className);
		}
	}

	addItem(item: ItemOptions): void
	{
		this.#selector.addItem(item);
	}

	selectItem(id: string): void
	{
		const items = this.#selector.getItems();
		const foundItem = items.find((item) => item.id === id);
		if (!foundItem)
		{
			return;
		}

		foundItem.select();
	}

	getLayout(): HTMLElement
	{
		return this.#dom;
	}

	getSelectedId(): string
	{
		return this.#selectedItemId;
	}

	#onSelect(item: ItemOptions): void
	{
		this.#selectedItemId = item.id;
		const { title, caption } = item;
		const { firstElementChild: titleNode } = this.#dom;
		if (!caption)
		{
			titleNode.textContent = title;
			titleNode.title = title;

			return;
		}

		titleNode.title = `${title} ${caption}`;
		titleNode.firstElementChild.textContent = title;
		titleNode.lastElementChild.textContent = caption;
	}
}
