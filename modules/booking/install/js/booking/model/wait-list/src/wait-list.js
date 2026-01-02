/* eslint-disable no-param-reassign */

import { BuilderModel, Store } from 'ui.vue3.vuex';
import type { GetterTree, ActionTree, MutationTree } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import type { WaitListItemModel, WaitListState, UpdatePayload } from './types';

export class WaitList extends BuilderModel
{
	getName(): string
	{
		return Model.WaitList;
	}

	getState(): WaitListState
	{
		return {
			collection: {},
		};
	}

	getElementState(): WaitListItemModel
	{
		return {
			id: 0,
			createdBy: 0,
			createdAt: 0,
			updatedAt: 0,
			clients: [],
			note: '',
			externalData: [],
		};
	}

	getGetters(): GetterTree<WaitListState>
	{
		return {
			/** @function wait-list/get */
			get: (state: WaitListState, getters, rootState, rootGetters): WaitListItemModel[] => {
				const deletingWaitListItems = rootGetters[`${Model.Interface}/deletingWaitListItems`];

				return Object.values(state.collection).filter(({ id }) => !deletingWaitListItems[id]);
			},
			getById: (state) => (id: number): WaitListItemModel => state.collection[id],
		};
	}

	getActions(): ActionTree<WaitListState, any>
	{
		return {
			/** @function wait-list/add */
			add: (store: Store, waitListItem: WaitListItemModel): void => {
				store.commit('add', waitListItem);
			},
			/** @function wait-list/insertMany */
			insertMany: (store: Store, waitListItems: WaitListItemModel[]): void => {
				waitListItems.forEach((waitListItem: WaitListItemModel) => store.commit('insert', waitListItem));
			},
			/** @function wait-list/upsert */
			upsert: (store: Store, waitListItem: WaitListItemModel): void => {
				store.commit('upsert', waitListItem);
			},
			/** @function wait-list/upsertMany */
			upsertMany: (store: Store, waitListItems: WaitListItemModel[]): void => {
				waitListItems.forEach((waitListItem: WaitListItemModel) => store.commit('upsert', waitListItem));
			},
			/** @function wait-list/update */
			update: (store: Store, payload: UpdatePayload): void => {
				store.commit('update', payload);
			},
			/** @function wait-list/delete */
			delete: (store: Store, waitListItemId: number | string): void => {
				store.commit('delete', waitListItemId);
			},
			/** @function wait-list/deleteMany */
			deleteMany: (store: Store, waitListItemIds: number[]): void => {
				store.commit('deleteMany', waitListItemIds);
			},
		};
	}

	getMutations(): MutationTree<WaitListState>
	{
		return {
			add: (state: WaitListState, waitListItem: WaitListItemModel): void => {
				state.collection[waitListItem.id] = waitListItem;
			},
			insert: (state: WaitListState, waitListItem: WaitListItemModel): void => {
				state.collection[waitListItem.id] ??= waitListItem;
			},
			upsert: (state: WaitListState, waitListItem: WaitListItemModel): void => {
				state.collection[waitListItem.id] ??= waitListItem;
				Object.assign(state.collection[waitListItem.id], waitListItem);
			},
			update: (state: WaitListState, { id, waitListItem }: UpdatePayload): void => {
				const updatedWaitListItem = { ...state.collection[id], ...waitListItem };
				delete state.collection[id];
				state.collection[waitListItem.id] = updatedWaitListItem;
			},
			delete: (state: WaitListState, waitListItemId: number | string): void => {
				delete state.collection[waitListItemId];
			},
			deleteMany: (state, waitListItemIds: number[]): void => {
				for (const id of waitListItemIds)
				{
					delete state.collection[id];
				}
			},
		};
	}
}
