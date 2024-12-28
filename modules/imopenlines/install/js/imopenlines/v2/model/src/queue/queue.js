import { Type } from 'main.core';
import { ActionTree, BuilderModel, MutationTree, GetterTree } from 'ui.vue3.vuex';

import { formatFieldsWithConfig } from 'im.v2.model';
import { QueueTypeName } from 'imopenlines.v2.const';

import { queueFieldsConfig } from './format/field-config';

import type { JsonObject } from 'main.core';
import type { ImolModelQueue } from 'imopenlines.v2.model';
import type { RawQueue } from 'imopenlines.v2.provider.service';

type QueueState = {
	collection: {
		[id: number]: ImolModelQueue
	}
}

export class QueueModel extends BuilderModel
{
	getName(): string
	{
		return 'queue';
	}

	getState(): QueueState
	{
		return {
			collection: {},
		};
	}

	getElementState(): ImolModelQueue
	{
		return {
			id: 0,
			lineName: '',
			type: '',
			isActive: true,
		};
	}

	getGetters(): GetterTree
	{
		return {
			/** @function queue/getTypeById */
			getTypeById: (state: QueueState) => (id: number, getBlank: boolean = false): ?QueueTypeName => {
				if (!state.collection[id] && getBlank)
				{
					return this.getElementState();
				}

				if (!state.collection[id] && !getBlank)
				{
					return null;
				}

				return state.collection[id].type;
			},
		};
	}

	getActions(): ActionTree
	{
		return {
			/** @function queue/set */
			set: (store, payload: RawQueue | RawQueue[]) => {
				let queues = payload;

				if (!Array.isArray(queues) && Type.isPlainObject(queues))
				{
					queues = [queues];
				}

				const itemsToAdd = [];

				queues.map((element) => {
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
			/** @function queue/delete */
			delete: (store, payload: { id: number }) => {
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
			add: (state: QueueState, payload: QueueState | QueueState[]) => {
				const queues = payload;
				const queueState = state;

				queues.forEach((item) => {
					queueState.collection[item.id] = item;
				});
			},
			update: (state: QueueState, payload: { id: number, fields: RawQueue}) => {
				const queueState = state;

				const currentElement = state.collection[payload.id];

				queueState.collection[payload.id] = { ...currentElement, ...payload.fields };
			},
			delete: (state: QueueState, payload: { id: number }) => {
				// eslint-disable-next-line no-param-reassign
				delete state.collection[payload.id];
			},
		};
	}

	#formatFields(rawFields: JsonObject): Partial<ImolModelQueue>
	{
		return formatFieldsWithConfig(rawFields, queueFieldsConfig);
	}
}
