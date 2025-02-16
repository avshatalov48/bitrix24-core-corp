import { Store, BuilderModel } from 'ui.vue3.vuex';
import type { GetterTree, ActionTree, MutationTree } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import type { ResourceTypeModel, ResourceTypesState } from './types';

export class ResourceTypes extends BuilderModel
{
	getName(): string
	{
		return Model.ResourceTypes;
	}

	getState(): ResourceTypesState
	{
		return {
			collection: {},
		};
	}

	getElementState(): ResourceTypeModel
	{
		return {
			id: 0,
			moduleId: '',
			name: '',
			code: '',
		};
	}

	getGetters(): GetterTree
	{
		return {
			/** @function resourceTypes/get */
			get: (state: ResourceTypesState): ResourceTypeModel[] => Object.values(state.collection),
			/** @function resourceTypes/getById */
			getById: (state: ResourceTypesState) => (id: number): ResourceTypeModel => state.collection[id],
		};
	}

	getActions(): ActionTree
	{
		return {
			/** @function resourceTypes/upsert */
			upsert: (store: Store, resourceType: ResourceTypeModel): void => {
				store.commit('upsert', resourceType);
			},
			/** @function resourceTypes/upsertMany */
			upsertMany: (store: Store, resourceTypes: ResourceTypeModel[]): void => {
				resourceTypes.forEach((resourceType: ResourceTypeModel) => store.commit('upsert', resourceType));
			},
		};
	}

	getMutations(): MutationTree
	{
		return {
			upsert: (state: ResourceTypesState, resourceType: ResourceTypeModel): void => {
				state.collection[resourceType.id] ??= resourceType;
				Object.assign(state.collection[resourceType.id], resourceType);
			},
		};
	}
}
