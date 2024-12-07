import { Event } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Popup } from 'main.popup';
import { PromptMaster, PromptMasterEvents } from './prompt-master';
import { sendData } from 'ui.analytics';

import './css/prompt-master-popup.css';

import type { PromptMasterOptions } from './prompt-master';

export type PromptMasterPopupAnalyticFields = {
	c_section?: string;
}

export type PromptMasterPopupOptions = {
	masterOptions: PromptMasterOptions;
	popupEvents: Object;
	analyticFields: PromptMasterPopupAnalyticFields;
}

export const PromptMasterPopupEvents = Object.freeze({
	SAVE_SUCCESS: 'save-success',
	SAVE_FAILED: 'save-success',
});

export class PromptMasterPopup extends Event.EventEmitter
{
	#masterOptions: PromptMasterOptions = {};
	#popupEvents: Object = {};
	#analyticFields: PromptMasterPopupAnalyticFields;
	#popup: Popup | null;
	#successPromptSavingHandler: Function;
	#closeMasterHandler: Function;
	#isPromptWasSaved: boolean;

	constructor(options: PromptMasterPopupOptions)
	{
		super(options);

		this.setEventNamespace('AI.prompt-master-popup');
		this.#masterOptions = options.masterOptions || {};
		this.#popupEvents = options.popupEvents || {};
		this.#analyticFields = options.analyticFields || {};
		this.#isPromptWasSaved = false;
	}

	show(): void
	{
		if (!this.#popup)
		{
			this.#popup = this.#initPopup();
		}

		this.#popup.show();
	}

	hide(): void
	{
		this.#popup?.close();
	}

	#initPopup(): Popup
	{
		const masterOptions = {
			analyticCategory: this.#analyticFields.c_section,
			...this.#masterOptions,
		};

		const promptMaster = new PromptMaster(masterOptions);

		this.#successPromptSavingHandler = this.#handleSuccessPromptSaving.bind(this);
		this.#closeMasterHandler = this.#handleCloseMasterEvent.bind(this);

		promptMaster.subscribe(PromptMasterEvents.SAVE_SUCCESS, this.#successPromptSavingHandler);
		promptMaster.subscribe(PromptMasterEvents.CLOSE_MASTER, this.#closeMasterHandler);

		return new Popup({
			id: 'prompt-master-popup',
			content: promptMaster.render(),
			width: 360,
			cacheable: false,
			closeIcon: true,
			autoHide: false,
			closeByEsc: false,
			className: 'ai__prompt-master-popup',
			padding: 0,
			borderRadius: '12px',
			overlay: true,
			events: {
				...this.#popupEvents,
				onAfterShow: () => {
					this.#popup.setHeight(this.#popup.getPopupContainer().offsetHeight);
				},
				onPopupDestroy: () => {
					this.#popup = null;
					promptMaster.unsubscribe(PromptMasterEvents.SAVE_SUCCESS, this.#successPromptSavingHandler);
					promptMaster.unsubscribe(PromptMasterEvents.CLOSE_MASTER, this.#closeMasterHandler);
					promptMaster.destroy();
					if (this.#popupEvents.onPopupDestroy)
					{
						this.#popupEvents.onPopupDestroy();
					}

					if (this.#isPromptWasSaved === false)
					{
						this.#sendAnalyticCancelLabel();
					}
				},
				onPopupShow: () => {
					if (this.#popupEvents.onPopupShow)
					{
						this.#popupEvents.onPopupShow();
					}
					this.#sendAnalyticOpenLabel();
				},
			},
		});
	}

	#handleSuccessPromptSaving(event: BaseEvent): void
	{
		this.emit(PromptMasterPopupEvents.SAVE_SUCCESS, event.getData());
		this.#popup.setAutoHide(true);
		this.#popup.setClosingByEsc(true);
		this.#isPromptWasSaved = true;
	}

	#handleCloseMasterEvent(): void
	{
		this.hide();
	}

	#sendAnalyticOpenLabel(): void
	{
		sendData({
			tool: 'ai',
			category: 'prompt_saving',
			event: 'open',
			c_section: this.#analyticFields.c_section,
			status: 'success',
		});
	}

	#sendAnalyticCancelLabel(): void
	{
		sendData({
			tool: 'ai',
			category: 'prompt_saving',
			event: 'cancel',
			c_section: this.#analyticFields.c_section,
			status: 'success',
		});
	}
}
