import { Tag, Event, Extension } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { BitrixVue, type VueCreateAppResult } from 'ui.vue3';
import { PromptMaster as PromptMasterApp } from './components/prompt-master';

import './css/prompt-master.css';

export type PromptMasterOptions = {
	authorId?: string;
	prompt?: string;
	type?: string;
	name?: string;
	accessCodes?: [];
	categories?: [];
	icon?: string;
	code?: string;
	analyticCategory: string;
}

export const PromptMasterEvents = {
	SAVE_SUCCESS: 'save-success',
	SAVE_FAILED: 'save-failed',
	CLOSE_MASTER: 'close-master',
};

export class PromptMaster extends Event.EventEmitter
{
	#promptOptions: PromptMasterOptions = {};
	#app: VueCreateAppResult;
	#successPromptSavingEventHandler: Function;
	#closeBtnClickHandler: Function;

	constructor(options: PromptMasterOptions)
	{
		super(options);

		this.setEventNamespace('AI.prompt-master');
		this.#promptOptions = options || {};
		this.#successPromptSavingEventHandler = this.#handleSuccessPromptSaving.bind(this);
		this.#closeBtnClickHandler = this.#handleClickOnCloseBtn.bind(this);
	}

	render(): HTMLElement
	{
		const container = Tag.render`<div class="ai__prompt-master-container"></div>`;

		const currentUserId = Extension.getSettings('ai.prompt-master').get('userId');

		let authorId = this.#promptOptions.authorId;

		if (!this.#promptOptions.code && currentUserId)
		{
			authorId = currentUserId;
		}

		this.#app = BitrixVue.createApp(PromptMasterApp, {
			authorId,
			code: this.#promptOptions.code,
			text: this.#promptOptions.prompt,
			type: this.#promptOptions.type,
			title: this.#promptOptions.name,
			accessCodes: this.#promptOptions.accessCodes,
			categories: this.#promptOptions.categories,
			icon: this.#promptOptions.icon,
			analyticCategory: this.#promptOptions.analyticCategory,
		});

		Event.EventEmitter.subscribe('AI.prompt-master-app:save-success', this.#successPromptSavingEventHandler);
		Event.EventEmitter.subscribe('AI.prompt-master-app:close-master', this.#closeBtnClickHandler);

		this.#app.mount(container);

		return container;
	}

	destroy(): void
	{
		this.#app.unmount();
		Event.EventEmitter.unsubscribe('AI.prompt-master-app:save-success', this.#successPromptSavingEventHandler);
		Event.EventEmitter.unsubscribe('AI.prompt-master-app:close-master', this.#closeBtnClickHandler);
	}

	#handleSuccessPromptSaving(event: BaseEvent): void
	{
		this.emit(PromptMasterEvents.SAVE_SUCCESS, event.getData());
	}

	#handleClickOnCloseBtn(): void
	{
		this.emit(PromptMasterEvents.CLOSE_MASTER);
	}
}
