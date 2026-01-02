import { Runtime } from 'main.core';

import { Core } from 'im.v2.application.core';
import { Logger } from 'im.v2.lib.logger';
import { RestMethod } from 'im.v2.const';
import { runAction } from 'im.v2.lib.rest';

import type { Store } from 'ui.vue3.vuex';

export class MessagesAutoDeleteService
{
	#store: Store;

	#sendRequestDebounced: Function;

	constructor()
	{
		this.#store = Core.getStore();

		const DEBOUNCE_TIME = 500;
		this.#sendRequestDebounced = Runtime.debounce(this.#sendRequest, DEBOUNCE_TIME);
	}

	setDelay(dialogId: string, hours: number): void
	{
		Logger.warn('MessagesAutoDeleteService: setDelay', dialogId, hours);

		const chatId = this.#getChatId(dialogId);
		const previousDelay = this.#store.getters['chats/autoDelete/getDelay'](chatId);

		void this.#store.dispatch('chats/autoDelete/set', {
			chatId,
			delay: hours,
		});

		this.#sendRequestDebounced({ dialogId, hours, previousDelay });
	}

	#sendRequest(queryParams: { dialogId: string, hours: number, previousDelay: number }): Promise
	{
		const { dialogId, hours, previousDelay } = queryParams;

		return runAction(RestMethod.imV2ChatSetMessagesAutoDeleteDelay, {
			data: { dialogId, hours },
		}).catch((error) => {
			console.error('MessagesAutoDeleteService: Error setting auto delete delay', error);

			void this.#store.dispatch('chats/autoDelete/set', {
				chatId: this.#getChatId(dialogId),
				delay: previousDelay,
			});
		});
	}

	#getChatId(dialogId: string): number
	{
		return this.#store.getters['chats/get'](dialogId).chatId;
	}
}
