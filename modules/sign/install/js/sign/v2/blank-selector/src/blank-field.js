import { Cache, Tag, Dom, Type, Loc, Text } from 'main.core';
import { type BaseCache } from 'main.core.cache';
import { EventEmitter, BaseEvent } from 'main.core.events';
import { Api } from 'sign.v2.api';
import { TagSelector } from 'ui.entity-selector';
import type { BlankSelectorConfig } from './index';
import { BlankSelector } from './index';

export type BlankFieldOptions = {
	selectorOptions?: BlankSelectorConfig,
	data?: {
		blankId: number | string | null,
	},
	events?: {
		[key: string]: (event: BaseEvent) => void,
	},
};

/**
 * @namespace BX.Sign.V2
 */
export class BlankField extends EventEmitter
{
	#cache: BaseCache = new Cache.MemoryCache();

	constructor(options: BlankFieldOptions)
	{
		super();
		this.setEventNamespace('BX.Sign.BlankSelector.BlankField');
		this.subscribeFromOptions(options?.events);
		this.#setOptions(options);

		const blankId = options?.data?.blankId;
		if (Type.isStringFilled(blankId) || Type.isNumber(blankId))
		{
			this.#getApi()
				.getBlankById(options.data.blankId)
				.then(({ id, title }) => {
					this.#getTagSelector().addTag({
						id,
						title,
						entityId: 'blank',
					});
				})
			;
		}
	}

	#setOptions(options: BlankFieldOptions)
	{
		this.#cache.set('options', options);
	}

	#getOptions(): BlankFieldOptions
	{
		return this.#cache.get('options', {});
	}

	#getApi(): Api
	{
		return this.#cache.remember('api', () => new Api());
	}

	#getBlankSelector(): BlankSelector
	{
		return this.#cache.remember('blankSelector', () => {
			return new BlankSelector({
				...(this.#getOptions().selectorOptions),
				events: {
					toggleSelection: (event: BaseEvent) => {
						const { id, title, selected } = event.getData();
						const tagSelector = this.#getTagSelector();
						if (selected)
						{
							tagSelector.addTag({
								id,
								title,
								entityId: 'blank',
							});

							tagSelector.showAddButton();
							this.emit('onSelect', event);

							return;
						}

						if (tagSelector.getTags().length === 0)
						{
							tagSelector.showAddButton();
						}

						this.emit('onCancel');
					},
					onSliderClose: () => {
						const tagSelector = this.#getTagSelector();
						if (tagSelector.getTags().length === 0)
						{
							tagSelector.showAddButton();
						}
						this.#resetBlankSelector();
					},
				},
			});
		});
	}

	#getTagSelector(): TagSelector
	{
		return this.#cache.remember('tagSelector', () => {
			return new TagSelector({
				id: Text.getRandom(),
				multiple: false,
				showTextBox: false,
				addButtonCaption: Loc.getMessage('SIGN_BLANK_SELECTOR_FIELD_ADD_BUTTON_LABEL'),
				tagMaxWidth: 500,
				events: {
					onAddButtonClick: () => {
						this.#getBlankSelector().openInSlider();
						this.#getTagSelector().hideTextBox();
					},
					onAfterTagRemove: () => {
						this.#getTagSelector().hideTextBox();
						this.#getTagSelector().showAddButton();
						this.emit('onRemove');
					},
				},
			});
		});
	}

	getLayout(): HTMLDivElement
	{
		return this.#cache.remember('layout', () => {
			const layout = Tag.render`
				<div class="sign-blank-selector-field">
				</div>
			`;

			this.#getTagSelector().renderTo(layout);

			return layout;
		});
	}

	renderTo(targetContainer: HTMLElement)
	{
		if (Type.isDomNode(targetContainer))
		{
			Dom.append(this.getLayout(), targetContainer);
		}
	}

	#resetBlankSelector(): void
	{
		this.#cache.delete('blankSelector');
	}
}
