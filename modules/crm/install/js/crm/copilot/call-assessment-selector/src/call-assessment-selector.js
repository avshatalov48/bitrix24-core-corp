import { Dom, Event, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import type { PopupOptions } from 'main.popup';
import { Dialog, DialogOptions, Item, type ItemOptions } from 'ui.entity-selector';
import type { DisplayStrategy } from './display-strategy';
import { PullManager } from './pull-manager';

import 'ui.design-tokens';

const ENTITY_ID = 'copilot_call_script'; /** @php \Bitrix\Crm\Copilot\CallAssessment\EntitySelector\CallScriptProvider */

declare type CallAssessmentSelectorOptions = {
	id?: string,
	currentCallAssessment: CallAssessmentItemIdentifier,
	displayStrategy: DisplayStrategy,
	additionalSelectorOptions ?: AdditionalSelectorOptions,
	emptyScriptListTitle ?: string,
	isPullSubscribe?: boolean,
	events: {
		onCallAssessmentUpdate?: Function,
	},
};

type AdditionalSelectorOptions = {
	dialog?: DialogOptions,
	popup?: PopupOptions,
}

export type CallAssessmentItemIdentifier = {
	id?: number;
	title?: string;
}

// todo: maybe put it in a separate model class?
declare type CallAssessmentCustomData = {
	id: number,
	title: string,
	prompt: string,
	gist: ?string,
	clientTypeIds: Array<number>,
	callTypeId: ?number,
	autoCheckTypeId: ?number,
	isEnabled: boolean,
	isDefault: boolean,
	jobId: number,
	status: string,
	sort: number,
};

export class CallAssessmentSelector
{
	#id: string;

	#currentCallAssessmentId: ?number = null;
	#additionalSelectorOptions: AdditionalSelectorOptions;
	#displayStrategy: DisplayStrategy;
	#emptyScriptListTitle: ?string;

	#container: HTMLElement;
	#currentSelectorItem: ?Item = null;
	#dialog: Dialog = null;
	#eventEmitter: EventEmitter;
	#pull: PullManager;

	#isDisplayLoadingState: boolean = true;
	#isDisabled: boolean = false;

	isSelectsByPull: boolean = false;
	#changesByPullQueue: ItemOptions[] = [];

	constructor(options: CallAssessmentSelectorOptions)
	{
		this.#id = Type.isStringFilled(options.id) ? options.id : Text.getRandom(16);

		const currentCallAssessment = options.currentCallAssessment;
		if (Type.isNumber(currentCallAssessment.id) && currentCallAssessment.id > 0)
		{
			this.#currentCallAssessmentId = currentCallAssessment.id;
		}

		this.#additionalSelectorOptions = options.additionalSelectorOptions ?? {};
		this.#emptyScriptListTitle = options.emptyScriptListTitle ?? null;

		this.#displayStrategy = options.displayStrategy;
		this.#displayStrategy.updateTitle(currentCallAssessment.title ?? this.#emptyScriptListTitle);

		this.#container = this.#displayStrategy.getTargetNode();
		Event.bind(this.#container, 'click', this.#toggleDialog.bind(this));

		this.#bindEvents(options.events);
		this.#subscribePull();
	}

	getId(): string
	{
		return this.#id;
	}

	isSelectByPull(): boolean
	{
		return this.isSelectsByPull;
	}

	getCurrentCallAssessmentId(): ?number
	{
		return this.#currentCallAssessmentId;
	}

	getCurrentCallAssessmentItem(): ?CallAssessmentCustomData
	{
		const customData = this.#currentSelectorItem?.getCustomData() ?? null;
		if (customData === null)
		{
			return null;
		}

		return Object.fromEntries(customData);
	}

	getCurrentSelectorItem(): ?Item
	{
		return this.#currentSelectorItem;
	}

	#toggleDialog(): void
	{
		if (this.#isDisabled)
		{
			return;
		}

		const dialog = this.getDialog();
		if (dialog.isOpen())
		{
			dialog.hide();
		}
		else
		{
			dialog.show();
		}
	}

	getContainer(): HTMLElement
	{
		return this.#container;
	}

	#loading(isLoading: boolean): void
	{
		if (this.#isDisplayLoadingState)
		{
			this.#displayStrategy.setLoading(isLoading);
		}
	}

	getDialog(): Dialog
	{
		if (this.#dialog === null)
		{
			const parentPopupContainer = this.#container.closest('body');

			const dialogOptions: DialogOptions = {
				...this.#additionalSelectorOptions.dialog,
				targetNode: this.#container,
				multiple: false,
				dropdownMode: true,
				enableSearch: true,
				showAvatars: false,
				preselectedItems: [
					[ENTITY_ID, this.#currentCallAssessmentId],
				],
				entities: [
					{
						id: ENTITY_ID,
						dynamicLoad: true,
						dynamicSearch: true,
					},
				],
				popupOptions: this.#additionalSelectorOptions.popup ?? {},
				events: {
					...(this.#additionalSelectorOptions.dialog?.events),
					onLoad: this.#onLoadDialog.bind(this),
					'Item:onBeforeSelect': this.#onItemBeforeSelect.bind(this),
					'Item:onBeforeDeselect': this.#onItemBeforeDeselect.bind(this),
					onShow: (event: BaseEvent) => {
						Event.bindOnce(parentPopupContainer, 'click', this.#onPopupContainerClick.bind(this));
					},
					onHide: () => {
						Event.unbind(parentPopupContainer, this.#onPopupContainerClick);
					},
				},
			};

			this.#dialog = new Dialog(dialogOptions);
		}

		return this.#dialog;
	}

	#onLoadDialog(event: BaseEvent): void
	{
		this.#applyChangesByPull();

		const item = this.#getDialogItem(this.#currentCallAssessmentId);
		if (item === null)
		{
			return;
		}

		this.#updateCurrentSelectorItem(item);
		this.#callAdditionalEvent(event, 'onLoad');
	}

	#onItemBeforeSelect(event: BaseEvent): void
	{
		const targetItem: Item = event.getData().item;
		if (targetItem === null)
		{
			return;
		}

		this.#updateCurrentSelectorItem(targetItem);
		this.#callAdditionalEvent(event, 'Item:onBeforeSelect');
	}

	#onItemBeforeDeselect(event: BaseEvent): void
	{
		this.#preventDeselectCurrentSelectorItem(event);
		this.#callAdditionalEvent(event, 'Item:onBeforeDeselect');
	}

	#callAdditionalEvent(event: BaseEvent, eventName: string): void
	{
		const eventCallback = this.#additionalSelectorOptions?.dialog?.events?.[eventName];
		if (Type.isFunction(eventCallback))
		{
			eventCallback(event);
		}
	}

	#preventDeselectCurrentSelectorItem(event: BaseEvent): void
	{
		const targetItem: Item = event.getData().item;
		if (targetItem === null)
		{
			return;
		}

		if (targetItem.id === this.#currentCallAssessmentId)
		{
			event.preventDefault();
		}

		event.getTarget().hide();
	}

	#updateCurrentSelectorItem(item: ?Item): void
	{
		this.#currentSelectorItem = item ?? null;
		this.#currentCallAssessmentId = item?.getId() ?? null;

		this.#adjustTitle();
	}

	#adjustTitle(): void
	{
		const title = this.#currentSelectorItem?.getTitle() ?? this.#emptyScriptListTitle;
		this.#displayStrategy.updateTitle(title);
	}

	#onPopupContainerClick(clickEvent: BaseEvent): void
	{
		const { target } = clickEvent;
		if (
			target?.closest('.call-quality__script-selector') === null
			&& target?.closest('.ui-selector-dialog') === null
			&& this.#displayStrategy.getTargetNode() !== target
		)
		{
			this.close();
		}
	}

	destroy(): void
	{
		this.#dialog?.destroy();
		this.#pull.unsubscribe();
	}

	close(): void
	{
		this.#dialog?.hide();
	}

	disable(): void
	{
		this.#isDisabled = true;
		const node = this.#displayStrategy?.getTargetNode();

		Dom.addClass(node, '--disabled');
		Dom.style(node, {
			cursor: 'not-allowed',
			opacity: '.6',
		});
	}

	enable(): void
	{
		this.#isDisabled = false;
		const node = this.#displayStrategy?.getTargetNode();

		Dom.removeClass(node, '--disabled');
		Dom.style(node, {
			cursor: 'inherit',
			opacity: '1',
		});
	}

	#subscribePull(): void
	{
		this.#pull = new PullManager({
			onUpdate: this.#onUpdateItemByPull.bind(this),
			onSelect: (eventData: Object): void => {
				this.isSelectsByPull = true;
				this.#onSelectItemByPull(eventData);
				this.isSelectsByPull = false;
			},
		});
	}

	#bindEvents(events: { [eventName: string]: Function }): void
	{
		this.#eventEmitter = new EventEmitter();
		this.#eventEmitter.setEventNamespace('Crm.Copilot.CallAssessmentSelector');

		if (Type.isObject(events))
		{
			Object.entries(events).forEach(([eventName, listener]) => {
				this.#eventEmitter.subscribe(eventName, listener);
			});
		}
	}

	#onSelectItemByPull(eventData: Object): void
	{
		const { selectorId, itemOptions } = eventData;
		if (selectorId !== this.getId())
		{
			return;
		}

		if (!this.#isDialogLoaded())
		{
			const item = new Item(itemOptions);
			const event = new BaseEvent({ data: { item } });

			this.#updateCurrentSelectorItem(item);
			this.#callAdditionalEvent(event, 'Item:onBeforeSelect');

			return;
		}

		if (!itemOptions.id)
		{
			this.#currentCallAssessmentId = null;
			this.getDialog().deselectAll();
			this.#adjustTitle();

			return;
		}

		this.#getDialogItem(itemOptions.id)?.select();
	}

	#onUpdateItemByPull(eventData: Object): void
	{
		const { itemOptions } = eventData;
		if (itemOptions.id === this.#currentCallAssessmentId)
		{
			this.#displayStrategy.updateTitle(itemOptions.title ?? this.#emptyScriptListTitle);
		}

		if (this.#isDialogLoaded())
		{
			this.#updateItem(itemOptions);

			return;
		}

		this.#addChangeByPull(itemOptions);
		this.#eventEmitter.emit('onCallAssessmentUpdate', { callAssessment: itemOptions?.customData });
	}

	#updateItem(itemOptions: ItemOptions, isEmit: boolean = true): void
	{
		const item = this.#getDialogItem(String(itemOptions.id));
		if (item === null)
		{
			return;
		}

		item.setTitle(itemOptions.title);
		item.setSupertitle(itemOptions.supertitle);
		item.setBadges(itemOptions.badgesOptions);
		item.customData = new Map(Object.entries(itemOptions.customData));

		if (isEmit)
		{
			this.#eventEmitter.emit('onCallAssessmentUpdate', { callAssessment: itemOptions.customData });
		}
	}

	#addChangeByPull(callAssessment: ItemOptions): void
	{
		this.#changesByPullQueue.push(callAssessment);
	}

	#applyChangesByPull(): void
	{
		this.#changesByPullQueue.forEach((itemOptions) => this.#updateItem(itemOptions, false));
		this.#changesByPullQueue = [];
	}

	#isDialogLoaded(): boolean
	{
		return this.#dialog !== null && !this.getDialog().isLoading();
	}

	#getDialogItem(id: string | number | null): ?Item
	{
		if (id === null)
		{
			return null;
		}

		return this.getDialog().getItem([ENTITY_ID, id]);
	}
}
