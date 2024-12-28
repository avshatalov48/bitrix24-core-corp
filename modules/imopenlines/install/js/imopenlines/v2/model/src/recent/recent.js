import { Type } from 'main.core';
import { BuilderModel, GetterTree } from 'ui.vue3.vuex';

import { Core } from 'im.v2.application.core';
import { formatFieldsWithConfig } from 'im.v2.model';

import { recentFieldsConfig } from './format/field-config';

import type { JsonObject } from 'main.core';
import type { ActionTree, MutationTree } from 'ui.vue3.vuex';
import type { RawRecentItem } from 'imopenlines.v2.provider.service';
import type { RecentItem as ImolModelRecentItem } from '../type/recent';
import type { Session as ImolModelSession } from '../type/sessions';

type RecentState = {
	collection: {[dialogId: string]: ImolModelRecentItem},
};

export class OpenLinesRecentModel extends BuilderModel
{
	getName(): string
	{
		return 'recentOpenLines';
	}

	getState(): RecentState
	{
		return {
			collection: {},
		};
	}

	getElementState(): ImolModelRecentItem
	{
		return {
			dialogId: '0',
			chatId: 0,
			messageId: 0,
			sessionId: 0,
			draft: {
				text: '',
				date: null,
			},
			unread: false,
			pinned: false,
			liked: false,
		};
	}

	getGetters(): GetterTree
	{
		return {
			/** @function recentOpenLines/getOpenLinesCollection */
			getOpenLinesCollection: (state: RecentState): ImolModelRecentItem[] => {
				const openLinesItems = [];

				Object.keys(state.collection).forEach((dialogId) => {
					const dialog = this.store.getters['chats/get'](dialogId);

					if (dialog)
					{
						openLinesItems.push(state.collection[dialogId]);
					}
				});

				return openLinesItems;
			},
			/** @function recentOpenLines/getSession */
			getSession: (state: RecentState) => (dialogId: string, getBlank: boolean = false): ImolModelSession | null => {
				const session: number = state.collection[dialogId];

				if (!session && getBlank)
				{
					return this.getElementState();
				}

				if (!session && !getBlank)
				{
					return null;
				}

				const sessionId = session.sessionId;

				return Core.getStore().getters['sessions/getById'](sessionId);
			},
			/** @function recentOpenLines/get */
			get: (state: RecentState) => (dialogId: string): ImolModelRecentItem | null => {
				if (!state.collection[dialogId])
				{
					return null;
				}

				return state.collection[dialogId];
			},
			/** @function recentOpenLines/getChatIdByDialogId */
			getChatIdByDialogId: (state: RecentState) => (dialogId: string): number | null => {
				if (!state.collection[dialogId])
				{
					return null;
				}

				return state.collection[dialogId].chatId;
			},
		};
	}

	getActions(): ActionTree
	{
		return {
			/** @function recentOpenLines/set */
			set: (store, payload: RawRecentItem | RawRecentItem[]) => {
				let openLines = payload;

				if (!Array.isArray(openLines) && Type.isPlainObject(openLines))
				{
					openLines = [openLines];
				}

				const itemsToAdd = [];
				const itemsToUpdate = [];

				openLines.map((element) => {
					return this.#formatFields(element);
				}).forEach((element) => {
					const existingItem = store.state.collection[element.dialogId];

					if (existingItem)
					{
						itemsToUpdate.push({ dialogId: existingItem.dialogId, fields: { ...element } });
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

				if (itemsToUpdate.length > 0)
				{
					store.commit('update', itemsToUpdate);
				}
			},
			/** @function recentOpenLines/delete */
			delete: (store, payload: { id: string | number }) => {
				const existingItem = store.state.collection[payload.id];
				if (!existingItem)
				{
					return;
				}

				store.commit('delete', {
					id: existingItem.dialogId,
				});
			},
		};
	}

	getMutations(): MutationTree
	{
		return {
			add: (state: RecentState, payload: RawRecentItem | RawRecentItem[]) => {
				let openLines = payload;
				const openLinesState = state;

				if (!Array.isArray(openLines) && Type.isPlainObject(openLines))
				{
					openLines = [openLines];
				}

				openLines.forEach((item) => {
					openLinesState.collection[item.dialogId] = item;
				});
			},
			update: (state: RecentState, payload: RawRecentItem | RawRecentItem[]) => {
				let openLines = payload;
				const openLinesState = state;

				if (!Array.isArray(openLines) && Type.isPlainObject(openLines))
				{
					openLines = [openLines];
				}

				openLines.forEach(({ dialogId, fields }) => {
					const currentElement = state.collection[dialogId];

					openLinesState.collection[dialogId] = { ...currentElement, ...fields };
				});
			},
			delete: (state: RecentState, payload: {id: string}) => {
				// eslint-disable-next-line no-param-reassign
				delete state.collection[payload.id];
			},
		};
	}

	#formatFields(rawFields: JsonObject): Partial<ImolModelRecentItem>
	{
		return formatFieldsWithConfig(rawFields, recentFieldsConfig);
	}
}
