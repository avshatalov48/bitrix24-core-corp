import { Type } from 'main.core';
import { ActionTree, BuilderModel, GetterTree, MutationTree } from 'ui.vue3.vuex';

import { formatFieldsWithConfig } from 'im.v2.model';

import { sessionsFieldsConfig } from './format/field-config';

import type { JsonObject } from 'main.core';
import type { RawSession } from 'imopenlines.v2.provider.service';
import type { Session as ImolModelSession } from '../type/sessions';

type SessionsState = {
	collection: {
		[id: number]: ImolModelSession
	}
}

export class SessionsModel extends BuilderModel
{
	getName(): string
	{
		return 'sessions';
	}

	getState(): SessionsState
	{
		return {
			collection: {},
		};
	}

	getElementState(): ImolModelSession
	{
		return {
			id: 0,
			chatId: 0,
			operatorId: 0,
			status: '',
			queueId: 0,
			pinned: false,
			isClosed: false,
		};
	}

	getGetters(): GetterTree
	{
		return {
			/** @function sessions/getById */
			getById: (state: SessionsState) => (id: number, getBlank: boolean = false): ?ImolModelSession => {
				if (!state.collection[id] && getBlank)
				{
					return this.getElementState();
				}

				if (!state.collection[id] && !getBlank)
				{
					return null;
				}

				return state.collection[id];
			},
			/** @function sessions/getByChatId */
			getByChatId: (state: SessionsState) => (chatId: number, getBlank: boolean = false): ?ImolModelSession => {
				const session = Object.values(state.collection).find((item: ImolModelSession) => item.chatId === chatId);

				if (!session && getBlank)
				{
					return this.getElementState();
				}

				if (!session && !getBlank)
				{
					return null;
				}

				return session;
			},
		};
	}

	getActions(): ActionTree
	{
		return {
			/** @function sessions/set */
			set: (store, payload: RawSession | RawSession[]) => {
				let sessions = payload;

				if (!Array.isArray(sessions) && Type.isPlainObject(sessions))
				{
					sessions = [sessions];
				}

				const itemsToAdd = [];

				sessions.map((element) => {
					return this.#formatFields(element);
				}).forEach((element) => {
					const existingItem = store.state.collection[element.id];

					if (existingItem)
					{
						store.commit('update', { id: existingItem.id, fields: { ...element } });
					}
					else
					{
						itemsToAdd.push({ ...this.getElementState(), ...element });
					}
				});

				if (itemsToAdd.length > 0)
				{
					store.commit('add', itemsToAdd);
				}
			},
			/** @function sessions/pin */
			pin: (store, payload: { id: string | number, action: boolean }) => {
				const existingItem = store.state.collection[payload.id];

				if (!existingItem)
				{
					return;
				}

				store.commit('update', {
					id: existingItem.id,
					fields: { pinned: payload.action },
				});
			},
		};
	}

	getMutations(): MutationTree
	{
		return {
			add: (state: SessionsState, payload: RawSession | RawSession[]) => {
				const sessions = payload;
				const sessionsState = state;

				const sessionChatId = sessions[0].chatId;

				const result = Object.values(sessionsState.collection).find((item) => item.chatId === sessionChatId);

				if (result)
				{
					delete sessionsState.collection[result.id];
				}

				sessions.forEach((item) => {
					sessionsState.collection[item.id] = item;
				});
			},
			update: (state: SessionsState, payload: { id: number, fields: RawSession }) => {
				const sessionsState = state;

				const currentElement = state.collection[payload.id];

				sessionsState.collection[payload.id] = { ...currentElement, ...payload.fields };
			},
		};
	}

	#formatFields(rawFields: JsonObject): Partial<ImolModelSession>
	{
		return formatFieldsWithConfig(rawFields, sessionsFieldsConfig);
	}
}
