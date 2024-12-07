import {EventEmitter, BaseEvent} from 'main.core.events';
import {Tag, Dom, Text, Type, Cache, Loc} from 'main.core';
import {LoadingStatus} from './loading-status/loading-status';

import './css/style.css';

type ListItemOptions = {
	id: string | number,
	iconClass?: string,
	title?: string,
	description?: string,
	iconBackground?: 'blue',
	selected?: boolean,
	events?: {
		[key: string]: (event: BaseEvent) => void,
	},
	targetContainer?: HTMLElement,
	editable?: boolean,
	loading?: boolean,
};

export default class ListItem extends EventEmitter
{
	#cache = new Cache.MemoryCache();

	constructor(options: ListItemOptions)
	{
		super();
		this.setEventNamespace('BX.Sign.TemplateSelector.ListItem');
		this.subscribeFromOptions(options?.events);
		this.setOptions(options);
		this.setSelected(options?.selected);
		this.setLoading(options?.loading);

		if (Type.isDomNode(options?.targetContainer))
		{
			this.appendTo(options?.targetContainer);
		}
	}

	setOptions(options: ListItemOptions)
	{
		this.#cache.set('options', {...options});
	}

	getOptions(): ListItemOptions
	{
		return this.#cache.get('options', {});
	}

	setLoading(value: boolean)
	{
		this.#cache.set('loading', value);
		if (value)
		{
			this.getLoadingStatus().show();
		}
		else
		{
			this.getLoadingStatus().hide();
		}
	}

	updateStatus(value: number | string)
	{
		this.getLoadingStatus().updateStatus(value);
	}

	getLoading(): boolean
	{
		return this.#cache.get('loading', false);
	}

	hasTitle(): boolean
	{
		return Type.isStringFilled(this.getOptions()?.title);
	}

	hasDescription(): boolean
	{
		return Type.isStringFilled(this.getOptions()?.description);
	}

	getIconLayout(): HTMLDivElement
	{
		return this.#cache.remember('iconLayout', () => {
			const {iconClass = '', iconBackground} = this.getOptions();
			const additionalClass = (() => {
				if (Type.isStringFilled(iconBackground))
				{
					return ` sign-template-selector-list-item-icon-${Text.encode(iconBackground)}`;
				}

				return '';
			})();

			return Tag.render`
				<div class="sign-template-selector-list-item-icon${additionalClass}">
					<div class="sign-template-selector-list-item-icon-wrapper">
						<div class="${iconClass}">
							<i></i>
						</div>
					</div>
				</div>
			`;
		});
	}

	getTitleLayout(): HTMLDivElement
	{
		return this.#cache.remember('titleLayout', () => {
			const {title = ''} = this.getOptions();
			return Tag.render`
				<div class="sign-template-selector-list-item-text-title">
					${Text.encode(title)}
				</div>
			`;
		});
	}

	getDescriptionLayout(): HTMLDivElement
	{
		return this.#cache.remember('descriptionLayout', () => {
			const {description = ''} = this.getOptions();
			return Tag.render`
				<div class="sign-template-selector-list-item-text-description">
					${Text.encode(description)}
				</div>
			`;
		});
	}

	getTextLayout(): HTMLDivElement
	{
		return this.#cache.remember('textLayout', () => {
			return Tag.render`
				<div class="sign-template-selector-list-item-text">
					${this.hasTitle() ? this.getTitleLayout() : ''}
					${this.hasDescription() ? this.getDescriptionLayout(): ''}
				</div>
			`;
		});
	}

	getAdditionalTextLayout(): HTMLDivElement
	{
		return this.#cache.remember('additionalTextLayout', () => {
			return Tag.render`
				<div class="sign-template-selector-list-item-additional-text"></div>
			`;
		});
	}

	onEditClick(event: MouseEvent)
	{
		event.preventDefault();

		this.emit('onEditClick');
	}

	getEditButton(): HTMLDivElement
	{
		return this.#cache.remember('editButton', () => {
			return Tag.render`
				<div 
					class="sign-template-selector-list-item-edit-button"
					onclick="${this.onEditClick.bind(this)}"
					title="${Loc.getMessage('SIGN_TEMPLATE_SELECTOR_EDIT_BUTTON_TITLE')}"
				></div>
			`;
		});
	}

	onClick(event: MouseEvent)
	{
		event.preventDefault();
		this.emit('onClick');
	}

	getLoadingStatus(): LoadingStatus
	{
		return this.#cache.remember('loadingStatus', () => {
			return new LoadingStatus({
				targetContainer: this.getLayout(),
			});
		});
	}

	getLayout(): HTMLDivElement
	{
		return this.#cache.remember('layout', () => {
			const {title = '', editable} = this.getOptions();
			return Tag.render`
				<div 
					class="sign-template-selector-list-item" 
					title="${Text.encode(title)}"
					onclick="${this.onClick.bind(this)}"
				>
					${this.getIconLayout()}
					${this.getTextLayout()}
					${editable ? this.getEditButton() : ''}
				</div>
			`;
		});
	}

	setSelected(value: boolean)
	{
		if (value)
		{
			Dom.addClass(this.getLayout(), 'sign-template-selector-list-item-selected');
			this.emit('onSelect');
		}
		else
		{
			Dom.removeClass(this.getLayout(), 'sign-template-selector-list-item-selected');
		}
	}

	appendTo(targetContainer: HTMLElement)
	{
		Dom.append(this.getLayout(), targetContainer);
	}

	prependTo(targetContainer: HTMLElement)
	{
		Dom.prepend(this.getLayout(), targetContainer);
	}

	getId(): string | number
	{
		return this.getOptions().id;
	}
}