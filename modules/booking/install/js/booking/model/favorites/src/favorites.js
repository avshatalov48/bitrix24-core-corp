import { Store, BuilderModel } from 'ui.vue3.vuex';
import type { GetterTree, ActionTree, MutationTree } from 'ui.vue3.vuex';
import { Model } from 'booking.const';

type FavoritesState = {
	ids: number[],
};

export class Favorites extends BuilderModel
{
	getName(): string
	{
		return Model.Favorites;
	}

	getState(): FavoritesState
	{
		return {
			ids: [],
		};
	}

	getGetters(): GetterTree
	{
		return {
			/** @function favorites/get */
			get: (state: FavoritesState): number[] => state.ids,
		};
	}

	getActions(): ActionTree
	{
		return {
			/** @function favorites/set */
			set: (store: Store, ids: number[]): void => {
				store.commit('set', ids);
			},
			/** @function favorites/add */
			add: (store: Store, id: number): void => {
				store.commit('add', id);
			},
			/** @function favorites/addMany */
			addMany: (store: Store, ids: number[]): void => {
				store.commit('addMany', ids);
			},
			/** @function favorites/delete */
			delete: (store: Store, id: number): void => {
				store.commit('delete', id);
			},
			/** @function favorites/deleteMany */
			deleteMany: (store: Store, ids: number[]): void => {
				store.commit('deleteMany', ids);
			},
		};
	}

	getMutations(): MutationTree
	{
		return {
			set: (state: FavoritesState, ids: number[]): void => {
				state.ids = ids;
			},
			add: (state: FavoritesState, id: number): void => {
				state.ids = [...state.ids, id];
			},
			addMany: (state: FavoritesState, ids: number[]): void => {
				const uniqueIds = ids.filter((id) => !state.ids.includes(id));
				state.ids = [...state.ids, ...uniqueIds];
			},
			delete: (state: FavoritesState, id: number): void => {
				state.ids = state.ids.filter((it: number) => it !== id);
			},
			deleteMany: (state: FavoritesState, ids: number[]): void => {
				state.ids = state.ids.filter((id: number) => !ids.includes(id));
			},
		};
	}
}
