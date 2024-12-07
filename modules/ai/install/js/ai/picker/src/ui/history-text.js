import { Dom, Tag, Loc, bindOnce, Text } from 'main.core';
import { HistoryBase } from './history-base';
import type { HistoryBaseProps } from './history-base';
import { TextLoader } from './text-loader';
import { HistoryItem } from '../types';

import '../css/ui/history-text.css';

type HistoryTextProps = HistoryBaseProps | {
	onCopy: Function;
}

export class HistoryText extends HistoryBase
{
	#previousItemsContainer: HTMLElement | null;
	#previousItemsListContainer: HTMLElement | null;
	#previousItemsLabel: HTMLElement | null;
	#lastItem: HTMLElement | null;
	#lastItemContainer: HTMLElement | null;
	#loaderContainer: HTMLElement | null;
	#isShowCapacityLabel: boolean;
	#onCopy: Function;

	constructor(props: HistoryTextProps)
	{
		super(props);

		this.#onCopy = props.onCopy;

		this.#isShowCapacityLabel = false;
	}

	async generate(message: string): Promise | null
	{
		if (!this.onGenerate)
		{
			return null;
		}

		this.emit('ai-generate-start');
		await Promise.all([this.#showLoader(), this.moveLastToHistory()]);

		return this.#generateNewItem(message);
	}

	async #generateNewItem(message: string): Promise
	{
		try
		{
			const res = await this.onGenerate(message);
			await this.#addNewItem(res.data.last);
			this.emit('ai-generate-finish');

			return res;
		}
		catch (err)
		{
			this.#handleFailedGenerate(err);
			throw err;
		}
	}

	render(): HTMLElement
	{
		this.buildHistory();

		return this.getWrapper();
	}

	/**
	 * Called when user want to copy HistoryItem in buffer.
	 *
	 * @param {HistoryItem} item
	 * @param {PointerEvent} event
	 */
	onCopyClick(item: HistoryItem, event: PointerEvent)
	{
		this.showNotify(
			event.target,
			`action_copy_notify_${item.id}`,
			this.getMessage('action_copy_notify'),
		);

		this.#onCopy(item);
	}

	#showLoader(): Promise
	{
		return new Promise((resolve) => {
			if (!this.#lastItemContainer)
			{
				resolve(true);
			}

			Dom.style(this.#lastItem, 'height', `${this.#lastItemContainer?.scrollHeight}px`);
			bindOnce(this.#lastItem, 'transitionend', () => {
				resolve(true);
			});

			this.#loaderContainer.hidden = false;

			Dom.append((new TextLoader()).render(), this.#loaderContainer);

			Dom.style(this.#loaderContainer, 'opacity', 1);

			Dom.style(this.#lastItem, 'height', `${this.#loaderContainer.offsetHeight}px`);
		});
	}

	#hideLoader(): Promise
	{
		return new Promise((resolve) => {
			if (this.#loaderContainer.hidden === true)
			{
				resolve(true);
			}

			Dom.style(this.#loaderContainer, 'opacity', 0);

			bindOnce(this.#loaderContainer, 'transitionend', () => {
				Dom.clean(this.#loaderContainer);
				this.#loaderContainer.hidden = true;

				resolve(true);
			});
		});
	}

	/**
	 * Builds History container after loading History items.
	 *
	 */
	buildHistory(): void
	{
		Dom.style(this.listWrapper, 'opacity', 0);
		Dom.style(this.listWrapper, 'transform', 'translateY(-30px)');
		Dom.clean(this.listWrapper);
		setTimeout(() => {
			const firstItem = this.items[0];

			this.#renderLastItem(firstItem);

			this.#renderPreviousItems(this.items.slice(1));
			this.#addCapacityLabelIfNeeded();

			Dom.style(this.listWrapper, {
				opacity: 1,
				transform: 'translateY(0)',
			});

			bindOnce(this.listWrapper, 'transitionend', () => {
				Dom.style(this.listWrapper, 'transform', null);
			});
		}, 50);
	}

	#renderLastItem(item: HistoryItem): HTMLElement
	{
		this.#lastItem = Tag.render`<div class="ai__picker__text-history-last"></div>`;

		this.#renderLoaderContainer();

		this.#lastItemContainer = Tag.render`<div class="ai__picker__text-history-last-item"></div>`;

		Dom.append(this.#lastItemContainer, this.#lastItem);
		Dom.append(this.#loaderContainer, this.#lastItem);

		Dom.clean(this.listWrapper);

		if (item)
		{
			const itemWrapper = this.#renderHistoryItemWrapper();
			const itemNode = this.#renderHistoryItem(item, true);

			Dom.append(itemNode, itemWrapper);
			Dom.append(itemWrapper, this.#lastItemContainer);
		}

		Dom.prepend(this.#lastItem, this.listWrapper);
	}

	#renderLoaderContainer(): HTMLElement
	{
		this.#loaderContainer = Tag.render`
			<div class="ai__picker__text-history-loader"></div>
		`;

		this.#loaderContainer.hidden = true;
		Dom.style(this.#loaderContainer, 'opacity', 0);

		return this.#loaderContainer;
	}

	#renderPreviousItems(items: Array<HistoryItem>): HTMLElement
	{
		this.#previousItemsContainer = Tag.render`
			<div class="ai__picker__text-history-previous"></div>
		`;

		if (items.length > 1)
		{
			this.#previousItemsLabel = this.#renderHistoryItemDivider(Loc.getMessage('AI_JS_PICKER_TEXT_PREVIOUS_ITEMS_LABEL'));
			Dom.append(this.#previousItemsLabel, this.#previousItemsContainer);
		}

		this.#previousItemsListContainer = Tag.render`<div class="ai__picker__text-history-previous-items"></div>`;

		Dom.append(this.#previousItemsListContainer, this.#previousItemsContainer);

		items.slice(1).forEach((item) => {
			const node = this.#renderHistoryItem(item);
			const nodeWrapper = this.#renderHistoryItemWrapper();

			Dom.append(node, nodeWrapper);

			Dom.append(nodeWrapper, this.#previousItemsListContainer);
		});

		Dom.append(this.#previousItemsContainer, this.listWrapper);
	}

	#addNewItem(item: HistoryItem): Promise
	{
		return new Promise((resolve) => {
			Dom.style(this.#lastItemContainer, {
				opacity: 0,
				transform: 'translateY(-5px)',
			});

			this.items.unshift(item);
			this.#addCapacityLabelIfNeeded();

			this.#hideLoader()
				.then(() => {
					const firstItemWrapper = this.#renderHistoryItemWrapper();
					const firstItemNode = this.#renderHistoryItem(item, true);
					Dom.append(firstItemNode, firstItemWrapper);
					Dom.append(firstItemWrapper, this.#lastItemContainer);
					this.#lastItemContainer.style = null;

					Dom.style(this.#lastItem, 'height', `${this.#lastItem.scrollHeight}px`);

					const clearLastItemContainerStyle = () => {
						this.#lastItemContainer.removeAttribute('style');
					};

					bindOnce(this.#lastItemContainer, 'transitionend', () => {
						clearLastItemContainerStyle();

						resolve(true);
					});
				})
				.catch((err) => {
					// eslint-disable-next-line no-console
					console.error(err);
				});
		});
	}

	async #handleFailedGenerate(): void
	{
		await this.#hideLoader();

		if (this.items.length === 0)
		{
			this.emit('ai-generate-failed');

			return;
		}

		await this.moveTopHistoryItem();
		this.emit('ai-generate-failed');
	}

	moveLastToHistory(): Promise
	{
		return new Promise((resolve) => {
			const lastNodeWrapper = this.#lastItemContainer?.firstElementChild;
			if (!lastNodeWrapper)
			{
				resolve(true);
			}

			if (this.items.length > 0 && !this.#previousItemsLabel)
			{
				this.#previousItemsLabel = this.#renderHistoryItemDivider(Loc.getMessage('AI_JS_PICKER_TEXT_PREVIOUS_ITEMS_LABEL'));
				this.#previousItemsContainer.prepend(this.#previousItemsLabel);
			}

			Dom.removeClass(lastNodeWrapper.firstElementChild, '--first');

			this.#makeElemFixedWithSavingPosition(lastNodeWrapper);

			const spaceNodeForNewItem = this.#addSpaceNodeForHistoryItem();

			bindOnce(lastNodeWrapper, 'transitionend', () => {
				spaceNodeForNewItem.remove();
				Dom.prepend(lastNodeWrapper, this.#previousItemsListContainer);
				lastNodeWrapper.style = null;
				resolve(true);
			});

			const loaderHeight = this.#loaderContainer?.offsetHeight || 0;
			const lastNodeHeight = lastNodeWrapper.offsetHeight;
			const shift = -Dom.getRelativePosition(lastNodeWrapper, spaceNodeForNewItem).y - (lastNodeHeight - loaderHeight);

			Dom.style(lastNodeWrapper, 'transform', `translateY(${shift}px)`);
			Dom.style(spaceNodeForNewItem, 'height', `${lastNodeWrapper.offsetHeight}px`);
		});
	}

	moveTopHistoryItem(): Promise
	{
		return new Promise((resolve) => {
			this.#lastItemContainer.style = null;

			const firstHistoryItem = this.#previousItemsListContainer.children[0];
			this.#makeElemFixedWithSavingPosition(firstHistoryItem);
			const spaceNodeForHistoryItem = this.#addSpaceNodeForHistoryItem(firstHistoryItem);

			requestAnimationFrame(() => {
				const shift = -Dom.getRelativePosition(this.#lastItem, spaceNodeForHistoryItem).y;

				bindOnce(firstHistoryItem, 'transitionend', () => {
					firstHistoryItem.style = null;
					this.#lastItemContainer.prepend(firstHistoryItem);
					resolve(true);
				});

				bindOnce(spaceNodeForHistoryItem, 'transitionend', () => {
					spaceNodeForHistoryItem.remove();
				});

				Dom.style(firstHistoryItem, 'transform', `translateY(${-shift}px`);
				Dom.addClass(firstHistoryItem.children[0], '--first');

				Dom.style(spaceNodeForHistoryItem, 'height', '0px');
				const firstHistoryItemHeight = Dom.getPosition(firstHistoryItem).height;
				Dom.style(this.#lastItem, 'height', `${firstHistoryItemHeight}px`);
			});
		});
	}

	#renderHistoryItem(item: HistoryItem, justAdded: boolean): HTMLElement | null
	{
		if (!item)
		{
			return null;
		}

		const itemClassname = `ai__picker_text-history-item ${justAdded ? '--first' : ''}`;

		const actionBtnAccentModifier = justAdded ? '--accent' : '';

		const actionCopyBtnClassname = 'ai__picker_text-history-item-action-btn --copy';
		const actionUseBtnClassname = `ai__picker_text-history-item-action-btn --paste ${actionBtnAccentModifier}`;

		return Tag.render`
			<article
				class="${itemClassname}"
			>
				<div class="ai__picker_text-history-item-text">
					${Text.encode(item.data).replaceAll(/(\r\n|\r|\n)/g, '<br>')}
				</div>
				<div class="ai__picker_text-history-item-actions">
					<div class="ai__picker_text-history-item-action">
						<button
							class="${actionUseBtnClassname}"
							onclick="${this.onSelectClick.bind(this, item)}"
						>
							<span class="ai__picker_text-history-item-action-icon"></span>
							${this.getMessage('action_use')}
						</button>
					</div>
					<div class="ai__picker_text-history-item-action">
						<button
							class="${actionCopyBtnClassname}"
							onclick="${this.onCopyClick.bind(this, item)}"
						>
							<span class="ai__picker_text-history-item-action-icon"></span>
							${this.getMessage('action_copy')}
						</button>
					</div>
				</div>
			</article>
		`;
	}

	#renderHistoryItemWrapper(): HTMLElement
	{
		return Tag.render`<div class="ai__picker_text-history-item-wrapper"></div>`;
	}

	#renderHistoryItemDivider(text?: string): HTMLElement
	{
		const textElem = text
			? Tag.render`<span class="ai__picker_text-history-item-divider-text">${text}</span>`
			: ''
		;

		return Tag.render`
			<div class="ai__picker_text-history-item-divider">
				<hr class="ai__picker_text-history-item-divider-line"/>
				${textElem}
			</div>
		`;
	}

	#makeElemFixedWithSavingPosition(elem: HTMLElement): HTMLElement
	{
		const position = Dom.getPosition(elem);
		Dom.style(elem, {
			position: 'fixed',
			top: `${position.y}px`,
			left: `${position.x}px`,
			width: `${position.width}px`,
		});

		return elem;
	}

	#addSpaceNodeForHistoryItem(historyItem?: HTMLElement): HTMLElement
	{
		const historyItemHeight = Dom.getPosition(historyItem).height;
		const spaceNodeForHistoryItem = Tag.render`<div class="ai__picker_text-history-space-for-new-item"></div>`;
		Dom.style(spaceNodeForHistoryItem, 'height', `${historyItemHeight}px`);
		if (historyItem)
		{
			Dom.insertBefore(spaceNodeForHistoryItem, historyItem.nextSibling);
		}
		else
		{
			this.#previousItemsListContainer.prepend(spaceNodeForHistoryItem);
		}

		return spaceNodeForHistoryItem;
	}

	#addCapacityLabelIfNeeded(): void
	{
		if (this.items.length > Math.round(this.capacity / 2) && !this.#isShowCapacityLabel)
		{
			Dom.append(this.getCapacityLabel(this.capacity), this.listWrapper);
			this.#isShowCapacityLabel = true;
		}
	}
}
