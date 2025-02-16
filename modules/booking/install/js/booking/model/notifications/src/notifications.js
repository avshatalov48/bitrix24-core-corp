import { BuilderModel } from 'ui.vue3.vuex';
import type { GetterTree, ActionTree, MutationTree } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import type { NotificationsModel, NotificationsSenderModel, NotificationsState } from './types';

export class Notifications extends BuilderModel
{
	getName(): string
	{
		return Model.Notifications;
	}

	getState(): NotificationsState
	{
		return {
			notifications: {},
			senders: {},
		};
	}

	getElementState(): NotificationsModel
	{
		return {
			type: '',
			templates: [{
				type: '',
				text: '',
				textSms: '',
			}],
		};
	}

	getGetters(): GetterTree
	{
		return {
			/** @function notifications/get */
			get: (state: NotificationsState): NotificationsModel[] => Object.values(state.notifications),
			/** @function notifications/getByType */
			getByType: (state: NotificationsState) => (type: string): NotificationsModel => state.notifications[type],
			/** @function notifications/getSenders */
			getSenders: (state: NotificationsState): NotificationsSenderModel[] => Object.values(state.senders),
			/** @function notifications/isCurrentSenderAvailable */
			isCurrentSenderAvailable: (state: NotificationsState): boolean => {
				return Object.values(state.senders).some((sender: NotificationsSenderModel) => sender.canUse);
			},
		};
	}

	getActions(): ActionTree
	{
		return {
			/** @function notifications/upsert */
			upsert: (store: Store, notification: NotificationsModel): void => {
				store.commit('upsert', notification);
			},
			/** @function notifications/upsertMany */
			upsertMany: (store: Store, notifications: NotificationsModel[]): void => {
				notifications.forEach((notification: NotificationsModel) => store.commit('upsert', notification));
			},
			/** @function notifications/upsertManySenders */
			upsertManySenders: (store: Store, senders: NotificationsSenderModel[]): void => {
				senders.forEach((sender: NotificationsSenderModel) => store.commit('upsertSender', sender));
			},
		};
	}

	getMutations(): MutationTree
	{
		return {
			upsert: (state: NotificationsState, notification: NotificationsModel): void => {
				state.notifications[notification.type] ??= notification;
				Object.assign(state.notifications[notification.type], notification);
			},
			upsertSender: (state: NotificationsState, sender: NotificationsSenderModel): void => {
				state.senders[sender.code] ??= sender;
				Object.assign(state.senders[sender.code], sender);
			},
		};
	}
}
