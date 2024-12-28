import { Dom, Event, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import type { PopupOptions } from 'main.popup';
import { Dialog, DialogOptions, Item } from 'ui.entity-selector';
import type { DisplayStrategy } from './display-strategy';
import { CallCardReplacement } from './display-strategy/call-card-replacement';

import 'ui.design-tokens';
import 'ui.fonts.opensans';

const ENTITY_ID = 'copilot_call_script'; /** @php \Bitrix\Crm\Copilot\CallAssessment\EntitySelector\CallScriptProvider */

declare type CallAssessmentSelectorOptions = {
	currentCallAssessment: CallAssessmentItemIdentifier,
	additionalSelectorOptions ?: AdditionalSelectorOptions,
	displayStrategy ?: DisplayStrategy,
	emptyScriptListTitle ?: string,
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
	#currentCallAssessmentId: ?number = null;
	#additionalSelectorOptions: AdditionalSelectorOptions;
	#displayStrategy: DisplayStrategy;
	#emptyScriptListTitle: ?string;

	#container: HTMLElement;
	#currentSelectorItem: ?Item = null;
	#dialog: Dialog = null;

	#isDisplayLoadingState: boolean = true;
	#isDisabled: boolean = false;

	constructor(options: CallAssessmentSelectorOptions)
	{
		const currentCallAssessment = options.currentCallAssessment;
		if (Type.isNumber(currentCallAssessment.id) && currentCallAssessment.id > 0)
		{
			this.#currentCallAssessmentId = currentCallAssessment.id;
		}

		this.#additionalSelectorOptions = options.additionalSelectorOptions ?? {};
		this.#emptyScriptListTitle = options.emptyScriptListTitle ?? null;

		this.#displayStrategy = options.displayStrategy ?? new CallCardReplacement();
		this.#displayStrategy.updateTitle(currentCallAssessment.title ?? this.#emptyScriptListTitle);

		this.#container = this.#displayStrategy.getTargetNode();
		Event.bind(this.#container, 'click', this.#toggleDialog.bind(this));
	}

	getCurrentCallAssessmentId(): ?number
	{
		return this.#currentCallAssessmentId;
	}

	setCurrentCallAssessment(
		callAssessment: CallAssessmentCustomData,
		isTouchDialog: boolean = false,
	): void
	{
		if (callAssessment.id === this.#currentCallAssessmentId)
		{
			return;
		}

		const isUpdateByDialog = (!isTouchDialog && this.#dialog === null) || this.getDialog().isLoading();
		this.#currentCallAssessmentId = callAssessment?.id ?? null;

		if (isUpdateByDialog)
		{
			const title = callAssessment?.title ?? this.#emptyScriptListTitle;
			this.#displayStrategy.updateTitle(title);

			// other changes will be made in onLoadDialog
			return;
		}

		if (!callAssessment.id)
		{
			this.#currentCallAssessmentId = null;
			this.getDialog().deselectAll();
			this.#adjustTitle();

			return;
		}

		this.getDialog().getItems().forEach((item) => {
			if (item.getId() === callAssessment.id)
			{
				item.select();
			}
		});
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
				...(this.#additionalSelectorOptions.dialog ?? {}),
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
				popupOptions: {
					targetContainer: parentPopupContainer,
					...(this.#additionalSelectorOptions.popup ?? {}),
				},
				events: {
					...(this.#additionalSelectorOptions.dialog?.events ?? {}),
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
		this.#doLoadCurrentSelectorItemByFirstLoad(event);
		this.#callAdditionalEvent(event, 'onLoad');
	}

	#onItemBeforeSelect(event: BaseEvent): void
	{
		this.#updateCurrentSelectorItemByEvent(event);
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

	#doLoadCurrentSelectorItemByFirstLoad(event: BaseEvent): void
	{
		if (this.#currentSelectorItem !== null)
		{
			return;
		}

		const dialog: Dialog = event.getTarget();
		let currentSelectorItem = null;
		dialog.getItems().forEach((item) => {
			if (item.getId() === this.#currentCallAssessmentId)
			{
				currentSelectorItem = item;
			}
		});

		this.#updateCurrentSelectorItem(currentSelectorItem);
	}

	#updateCurrentSelectorItemByEvent(event: BaseEvent): void
	{
		const targetItem: Item = event.getData().item;
		if (targetItem === null)
		{
			return;
		}

		this.#updateCurrentSelectorItem(targetItem);
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
			target?.closest('.ui-selector-dialog') === null
			&& this.#displayStrategy.getTargetNode() !== target
		)
		{
			this.#dialog?.hide();
		}
	}

	destroy(): void
	{
		this.#dialog?.destroy();
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
}
