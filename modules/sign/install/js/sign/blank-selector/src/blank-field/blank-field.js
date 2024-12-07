import { Cache, Tag, Dom, Type, Loc, Text } from 'main.core';
import { EventEmitter, BaseEvent } from 'main.core.events';
import { TagSelector } from 'ui.entity-selector';
import BlankSelector, { BlankSelectorOptions } from '../blank-selector/blank-selector';
import Backend from '../backend/backend';

type BlankFieldOptions = {
	targetContainer?: HTMLDivElement,
	selectorOptions?: BlankSelectorOptions,
	data?: {
		blankId?: number | string,
	},
	events?: {
		[key: string]: (event: BaseEvent) => void,
	},
};

/**
 * @namespace BX.Sign
 */
export default class BlankField extends EventEmitter
{
	#cache = new Cache.MemoryCache();

	constructor(options: BlankFieldOptions)
	{
		super();
		this.setEventNamespace('BX.Sign.BlankSelector.BlankField');
		this.subscribeFromOptions(options?.events);
		this.#setOptions(options);

		if (Type.isDomNode(options?.targetContainer))
		{
			this.renderTo(options.targetContainer);
		}

		if (Type.isStringFilled(options?.data?.blankId) || Type.isNumber(options?.data?.blankId))
		{
			this.#getBackend()
				.getBlankById(options.data.blankId)
				.then((result) => {
					this.#getTagSelector().addTag({
						id: result.data.id,
						title: result.data.title,
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

	#getBackend(): Backend
	{
		return this.#cache.remember('backend', () => {
			return new Backend();
		});
	}

	#getBlankSelector(): BlankSelector
	{
		return this.#cache.remember('blankSelector', () => {
			return new BlankSelector({
				...(this.#getOptions().selectorOptions || {}),
				events: {
					onSelect: (event: BaseEvent) => {
						const data = event.getData();
						this.#getTagSelector().addTag({
							id: data.id,
							title: data.title,
							entityId: 'blank',
						});

						this.emit('onSelect', data);
					},
					onCancel: () => {
						const tagSelector: TagSelector = this.#getTagSelector();
						if (tagSelector.getTags().length === 0)
						{
							tagSelector.showAddButton();
						}
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
						this.#getBlankSelector().openSlider();
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
}
