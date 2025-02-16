import { BuilderModel } from 'ui.vue3.vuex';
import type { ActionTree, GetterTree, MutationTree } from 'ui.vue3.vuex';
import { Model } from 'booking.const';
import type { MainResourcesState } from './types';

export class MainResources extends BuilderModel
{
	getName(): string
	{
		return Model.MainResources;
	}

	getState(): MainResourcesState
	{
		return {
			resources: [],
		};
	}

	getGetters(): GetterTree<MainResourcesState, any>
	{
		return {
			resources: (state) => state.resources,
		};
	}

	getActions(): ActionTree<MainResourcesState, any>
	{
		return {
			setMainResources({ commit }, resourcesIds: number[]): void
			{
				commit('setMainResources', resourcesIds);
			},
		};
	}

	getMutations(): MutationTree<MainResourcesState>
	{
		return {
			setMainResources(state, resourcesIds: number[]): void
			{
				state.resources = resourcesIds;
			},
		};
	}
}
