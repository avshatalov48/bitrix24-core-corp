import { BuilderModel, Store } from 'ui.vue3.vuex';
import type { GetterTree, ActionTree, MutationTree } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import { MessageStatusState, MessageStatusModel } from './types';

export type { MessageStatusModel } from './types';

export class MessageStatus extends BuilderModel
{
	getName(): string
	{
		return Model.MessageStatus;
	}

	getState(): MessageStatusState
	{
		return {
			collection: {},
		};
	}

	getElementState(): MessageStatusModel
	{
		return {
			title: '',
			description: '',
			semantic: '',
			isDisabled: true,
		};
	}

	getGetters(): GetterTree<MessageStatusState>
	{
		return {
			getById: (state: MessageStatusState) => (bookingId: number): MessageStatusModel => {
				return state.collection[bookingId];
			},
		};
	}

	getActions(): ActionTree<MessageStatusState, any>
	{
		return {
			/** @function message-status/upsert */
			upsert: (store: Store, payload: { bookingId: number, status: MessageStatusModel }): void => {
				store.commit('upsert', payload);
			},
		};
	}

	getMutations(): MutationTree<MessageStatusState>
	{
		return {
			upsert: (state: MessageStatusState, payload: { bookingId: number, status: MessageStatusModel }): void => {
				const { bookingId, status } = payload;
				// eslint-disable-next-line no-param-reassign
				state.collection[bookingId] ??= status;
				Object.assign(state.collection[bookingId], status);
			},
		};
	}
}
