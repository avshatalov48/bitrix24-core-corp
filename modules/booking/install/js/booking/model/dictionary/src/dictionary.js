import { Store, BuilderModel } from 'ui.vue3.vuex';
import type { GetterTree, ActionTree, MutationTree } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import type { DictionaryModel, DictionaryState } from './types';

export class Dictionary extends BuilderModel
{
	getName(): string
	{
		return Model.Dictionary;
	}

	getState(): DictionaryState
	{
		return {
			counters: [],
			notifications: [],
			pushCommands: [],
			notificationTemplates: [],
		};
	}

	getGetters(): GetterTree
	{
		return {
			/** @function dictionary/getNotifications */
			getNotifications: (state: DictionaryState): DictionaryModel => state.notifications,
			/** @function dictionary/getCounters */
			getCounters: (state: DictionaryState): DictionaryModel => state.counters,
			/** @function dictionary/getPushCommands */
			getPushCommands: (state: DictionaryState): DictionaryModel => state.pushCommands,
			/** @function dictionary/getNotificationTemplates */
			getNotificationTemplates: (state: DictionaryState): DictionaryModel => state.notificationTemplates,
			/** @function dictionary/getBookingVisitStatuses */
			getBookingVisitStatuses: (state: DictionaryState): DictionaryModel => state.bookings.visitStatuses,
		};
	}

	getActions(): ActionTree
	{
		return {
			/** @function dictionary/setNotifications */
			setNotifications: (store: Store, items: DictionaryModel): void => {
				store.commit('set', { key: 'notifications', items });
			},
			/** @function dictionary/setCounters */
			setCounters: (store: Store, items: DictionaryModel): void => {
				store.commit('set', { key: 'counters', items });
			},
			/** @function dictionary/setPushCommands */
			setPushCommands: (store: Store, items: DictionaryModel): void => {
				store.commit('set', { key: 'pushCommands', items });
			},
			/** @function dictionary/setNotificationTemplates */
			setNotificationTemplates: (store: Store, items: DictionaryModel): void => {
				store.commit('set', { key: 'notificationTemplates', items });
			},
			/** @function dictionary/setBookings */
			setBookings: (store: Store, items: DictionaryModel): void => {
				store.commit('set', { key: 'bookings', items });
			},
		};
	}

	getMutations(): MutationTree
	{
		return {
			set: (state: DictionaryState, payload: { key: string, items: DictionaryModel }): void => {
				state[payload.key] = payload.items;
			},
		};
	}
}
