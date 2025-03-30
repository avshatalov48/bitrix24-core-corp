import { Tag, Dom, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Dialog, type ItemOptions } from 'ui.entity-selector';
import './style.css';

export type { ItemOptions };

export class SignDropdown extends EventEmitter
{
	events = {
		onSelect: 'onSelect',
	};

	#dom: HTMLElement;
	#selector: Dialog;
	#selectedItemId: string = '';
	#selectedItemCaption: string = '';

	constructor(dialogOptions: {
		className?: string,
		entities: Array<{ id: string, searchFields: Array<{ id: string, system: boolean}> }>,
		isEnableSearch?: boolean,
		width?: number,
		height?: number,
		withCaption?: boolean,
		tabs: Array<{ id: string, title: string }>})
	{
		super();
		this.setEventNamespace('BX.V2.B2e.SignDropdown');

		const { className, withCaption, isEnableSearch, width, height } = dialogOptions;
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
			width: width ?? 500,
			height: height ?? 350,
			showAvatars: false,
			dropdownMode: true,
			multiple: false,
			enableSearch: isEnableSearch ?? true,
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

	addItems(items: ItemOptions[]): void
	{
		items.forEach((item) => this.#selector.addItem(item));
	}

	removeItems(): void
	{
		this.#selector.removeItems();
	}

	selectFirstItem(): void
	{
		const [firstItem] = this.#selector.getItems();
		if (!Type.isUndefined(firstItem))
		{
			firstItem.select();
		}
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

	getSelectedCaption(): string
	{
		return this.#selectedItemCaption;
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
			this.emit(this.events.onSelect, { item });

			return;
		}
		this.#selectedItemCaption = caption.text;
		titleNode.title = `${title} ${caption}`;
		titleNode.firstElementChild.textContent = title;
		titleNode.lastElementChild.textContent = caption;
		this.emit(this.events.onSelect, { item });
	}
}
