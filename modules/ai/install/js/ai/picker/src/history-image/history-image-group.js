import { HistoryItem } from '../types';
import { HistoryImageGroupItem, HistoryImageGroupItemState } from './history-image-group-item';
import { Base } from '../ui/base';
import { Dom, Tag, Type } from 'main.core';

type HistoryImageGroupProps = {
	title?: string;
	size?: number;
	item: HistoryItem,
	isNew: boolean,
	onSelect: Function,
}

export class HistoryImageGroup extends Base
{
	#title: string;
	#size: number;
	#item: HistoryItem;
	#itemsContainer: HTMLElement | null;
	#items: HistoryImageGroupItem[];
	#layout: null | HTMLElement;
	#isNew: boolean;
	#onSelect: Function | null;

	constructor(props: HistoryImageGroupProps = {}) {
		super(props);

		this.#title = Type.isString(props?.item?.payload) ? props.item.payload : '';
		this.#size = Type.isInteger(props.size) ? props.size : 4;
		this.#item = props.item || [];
		this.#isNew = Type.isBoolean(props.isNew) ? props.isNew : false;
		this.#onSelect = Type.isFunction(props.onSelect) ? props.onSelect : null;

		this.#items = [];
		this.#layout = null;
	}

	render(): HTMLElement
	{
		this.#layout = Tag.render`
			<div class="ai__picker_history-image-group">
				<div class="ai__picker_history-image-group-title">${BX.util.htmlspecialchars(this.#title)}</div>
				<div class="ai__picker_history-image-group-items">
					${this.#renderItems()}
				</div>
			</div>
		`;

		return this.#layout;
	}

	getLayout(): HTMLElement
	{
		return this.#layout;
	}

	remove(): void {
		if (this.getLayout())
		{
			this.getLayout().remove();
		}
	}

	addImage(image: string): void {
		const loadingItem = this.#items.find((item) => item.isGenerating());
		if (!loadingItem)
		{
			return;
		}

		loadingItem.setImage(image);

		const emptyItem = this.#items.find((item) => item.isInQueue());
		if (!emptyItem)
		{
			return;
		}

		emptyItem.setGeneratingState();
	}

	geGeneratedImagesCount(): number
	{
		return this.#items.filter((item) => {
			return item.getState() === HistoryImageGroupItemState.IMAGE_LOADING
				|| item.getState() === HistoryImageGroupItemState.IMAGE_LOADING_ERROR
				|| item.getState() === HistoryImageGroupItemState.IMAGE_LOADING_SUCCESS;
		}).length;
	}

	#renderItems(): HTMLElement
	{
		this.#itemsContainer = Tag.render`<div class="ai__picker_history-image-group-items"></div>`;

		this.#getGroupImages().forEach((image) => {
			const state = this.#isNew ? HistoryImageGroupItemState.GENERATING : HistoryImageGroupItemState.EMPTY;

			const newItem = new HistoryImageGroupItem({
				image,
				state,
				onSelect: this.#onSelect,
			});

			newItem.subscribe('select', (event) => {
				this.emit('select', { item: event.data.item });
			});

			this.#items.push(newItem);
			Dom.append(newItem.render(), this.#itemsContainer);
		});

		return this.#itemsContainer;
	}

	#getGroupImages(): string[]
	{
		const result = [];
		const images = this.#item.groupData || JSON.parse(this.#item.data);

		for (let imageIndex = 0; imageIndex < this.#size; imageIndex++)
		{
			const image = images[imageIndex] || '';
			result.push(image);
		}

		return result;
	}
}
