import { BuilderModel } from 'ui.vue3.vuex';
import type { GetterTree, ActionTree, MutationTree } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import type { CountersModel } from './types';

export class Counters extends BuilderModel
{
	getName(): string
	{
		return Model.Counters;
	}

	getState(): Object
	{
		return {
			counters: {
				total: 0,
				unConfirmed: 0,
				delayed: 0,
			},
		};
	}

	getGetters(): GetterTree
	{
		return {
			/** @function counters/get */
			get: (state): number => state.counters,
		};
	}

	getActions(): ActionTree
	{
		return {
			/** @function counters/set */
			set: (store, counters: CountersModel) => {
				store.commit('set', counters);
			},
		};
	}

	getMutations(): MutationTree
	{
		return {
			set: (state, counters: CountersModel) => {
				state.counters = counters;
			},
		};
	}
}
