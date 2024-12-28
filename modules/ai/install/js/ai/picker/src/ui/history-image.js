import { HistoryBase } from './history-base';
import { HistoryImageGroup } from '../history-image/history-image-group';
import { HistoryItem } from '../types';
import { ImageHistoryEmptyState } from './image-history-empty-state';
import { Tag, Dom, bind } from 'main.core';

import './css/history-image.css';

export class HistoryImage extends HistoryBase
{
	static imagesInItem = 1;

	render(): HTMLElement
	{
		this.buildHistory();

		return this.getWrapper();
	}

	generate(prompt: string): Promise | null
	{
		if (!this.onGenerate)
		{
			return null;
		}

		if (this.items.length === 0)
		{
			Dom.clean(this.listWrapper);
		}

		const item = this.#createItemWithPrompt(prompt);

		const historyImageGroup = this.#addNewHistoryItem(item);

		return new Promise((resolve, reject) => {
			this.onGenerate(prompt)
				.then((res) => {
					this.items.push(res.data.last);
					const images = JSON.parse(res.data.result);
					images.forEach((image) => {
						historyImageGroup.addImage(image);
					});

					resolve(res);
				})
				.catch((err) => {
					this.#removeHistoryImageGroup(historyImageGroup);
					reject(err);
				});
		});
	}

	buildHistory(): void
	{
		Dom.clean(this.listWrapper);

		if (this.items.length === 0)
		{
			const emptyState = new ImageHistoryEmptyState();
			Dom.append(emptyState.render(), this.listWrapper);
		}

		this.items.forEach((historyItem) => {
			try
			{
				this.#addHistoryItem(historyItem);
			}
			catch (e)
			{
				console.error('AI.Picker: history item error', e, historyItem);
			}
		});

		if (this.items.length > 3)
		{
			Dom.append(this.getCapacityLabel(this.capacity), this.listWrapper);
		}
	}

	#addHistoryItem(item: HistoryItem): HistoryImageGroup
	{
		const imageGroup = this.#createImageGroup(item);
		const imageGroupWrapper = this.#renderImageGroup(imageGroup);

		Dom.append(imageGroupWrapper, this.listWrapper);
		Dom.style(imageGroupWrapper, 'opacity', 1);

		return imageGroup;
	}

	#addNewHistoryItem(item: HistoryItem): HistoryImageGroup
	{
		const imageGroup = this.#createImageGroup(item, true);
		const imageGroupWrapper = this.#renderImageGroup(imageGroup);
		Dom.prepend(imageGroupWrapper, this.listWrapper);

		const { height } = Dom.getPosition(imageGroupWrapper);
		Dom.style(imageGroupWrapper, 'height', 0);

		requestAnimationFrame(() => {
			Dom.style(imageGroupWrapper, {
				opacity: 1,
				height: `${height}px`,
			});
		});

		return imageGroup;
	}

	#renderImageGroup(imageGroup: HistoryImageGroup): HTMLElement
	{
		const wrapper = Tag.render`
			<div class="ai__history-image_item-wrapper"></div>
		`;

		Dom.append(imageGroup.render(), wrapper);

		return wrapper;
	}

	#createImageGroup(item: HistoryItem, isNew: boolean = false): HistoryImageGroup
	{
		return new HistoryImageGroup({
			item,
			size: HistoryImage.imagesInItem,
			isNew,
			onSelect: this.onSelect,
		});
	}

	#createItemWithPrompt(payload): Object
	{
		return {
			payload,
			id: Math.random(),
			groupData: [],
			data: '',
		};
	}

	#removeHistoryImageGroup(group: HistoryImageGroup): void
	{
		if (!group.getLayout() || !group.getLayout().parentElement)
		{
			return;
		}

		const groupWrapper = group.getLayout().parentElement;
		Dom.style(groupWrapper, 'height', 0);
		Dom.style(groupWrapper, 'padding-bottom', 0);
		bind(groupWrapper, 'transitionend', () => {
			Dom.remove(groupWrapper);
		});
	}
}
