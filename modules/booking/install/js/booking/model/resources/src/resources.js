import { BuilderModel, Store } from 'ui.vue3.vuex';
import type { GetterTree, ActionTree, MutationTree } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import type { ResourceModel, ResourcesState, SlotRange } from './types';

export class Resources extends BuilderModel
{
	getName(): string
	{
		return Model.Resources;
	}

	getState(): ResourcesState
	{
		return {
			collection: {},
		};
	}

	getElementState(): ResourceModel
	{
		return {
			id: 0,
			type: '',
			name: '',
			description: '',
			linkedResources: [],
			slotRanges: [],
			workLoad: null,
			counter: null,
			isMain: false,
			isConfirmationNotificationOn: true,
			isFeedbackNotificationOn: true,
			isInfoNotificationOn: false,
			isDelayedNotificationOn: false,
			isReminderNotificationOn: false,
			templateTypeConfirmation: '',
			templateTypeFeedback: '',
			templateTypeInfo: '',
			templateTypeDelayed: '',
			templateTypeReminder: '',
			createdBy: 0,
			createdAt: 0,
			updatedAt: 0,
		};
	}

	getGetters(): GetterTree
	{
		return {
			/** @function resources/get */
			get: (state: ResourcesState): ResourceModel[] => Object.values(state.collection),
			/** @function resources/getById */
			getById: (state: ResourcesState) => (id: number): ResourceModel => state.collection[id],
			/** @function resources/getByIds */
			getByIds: (state: ResourcesState) => (ids: number[]): ResourceModel[] => {
				return ids.map((id: number): ResourceModel => state.collection[id]);
			},
		};
	}

	getActions(): ActionTree
	{
		return {
			/** @function resources/insertMany */
			insertMany: (store: Store, resources: ResourceModel[]): void => {
				resources.forEach((resource: ResourceModel) => store.commit('insert', resource));
			},
			/** @function resources/upsert */
			upsert: (store: Store, resource: ResourceModel): void => {
				store.commit('upsert', resource);
			},
			/** @function resources/upsertMany */
			upsertMany: (store: Store, resources: ResourceModel[]): void => {
				resources.forEach((resource: ResourceModel) => store.commit('upsert', resource));
			},
			/** @function resources/delete */
			delete: (store: Store, resourceId: number): void => {
				store.commit('delete', resourceId);
			},
		};
	}

	getMutations(): MutationTree
	{
		return {
			insert: (state: ResourcesState, resource: ResourceModel): void => {
				resource.slotRanges = this.#updateSlotRangesTimezone(resource.slotRanges);
				state.collection[resource.id] ??= resource;
			},
			upsert: (state: ResourcesState, resource: ResourceModel): void => {
				resource.slotRanges = this.#updateSlotRangesTimezone(resource.slotRanges);
				state.collection[resource.id] ??= resource;
				Object.assign(state.collection[resource.id], resource);
			},
			delete: (state: ResourcesState, resourceId: number): void => {
				delete state.collection[resourceId];
			},
		};
	}

	#updateSlotRangesTimezone(slotRanges: SlotRange[]): SlotRange[]
	{
		return slotRanges.map((slotRange: SlotRange) => {
			return {
				...slotRange,
				timezone: (
					slotRange.timezone === ''
						? Intl.DateTimeFormat().resolvedOptions().timeZone
						: slotRange.timezone
				),
			};
		});
	}
}
