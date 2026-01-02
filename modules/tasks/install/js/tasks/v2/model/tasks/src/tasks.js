/* eslint-disable no-param-reassign */
import { BuilderEntityModel, Store } from 'ui.vue3.vuex';
import type { ActionTree, GetterTree, MutationTree } from 'ui.vue3.vuex';

import { Model, TaskField } from 'tasks.v2.const';

import type { TaskModel, TasksModelState } from './types';

const aliasFields = {
	[TaskField.DatePlan]: new Set(['startPlanTs', 'endPlanTs', 'matchesSubTasksTime']),
	[TaskField.SubTasks]: new Set(['containsSubTasks']),
	[TaskField.RelatedTasks]: new Set(['containsRelatedTasks']),
};

export class Tasks extends BuilderEntityModel<TasksModelState, TaskModel>
{
	getName(): string
	{
		return Model.Tasks;
	}

	getState(): TasksModelState
	{
		return {
			partiallyLoadedIds: new Set(),
		};
	}

	getElementState(): TaskModel
	{
		return {
			id: 0,
			title: '',
			isImportant: false,
			description: '',
			creatorId: 0,
			createdTs: Date.now(),
			responsibleId: 0,
			deadlineTs: 0,
			startPlanTs: null,
			endPlanTs: null,
			fileIds: [],
			checklist: [],
			containsChecklist: false,
			storyPoints: '',
			epicId: 0,
			accomplicesIds: [],
			auditorsIds: [],
			tags: [],
			status: 'pending',
			statusChangedTs: Date.now(),
			needsControl: false,
			filledFields: {},
			parentId: 0,
			subTaskIds: [],
			containsSubTasks: false,
			relatedTaskIds: [],
			containsRelatedTasks: false,
			rights: {
				edit: true,
				deadline: true,
				datePlan: true,
				delegate: true,
			},
			inFavorite: [],
			inMute: [],
		};
	}

	getGetters(): GetterTree<TasksModelState>
	{
		return {
			/** @function tasks/wasFieldFilled */
			wasFieldFilled: (state: TasksModelState) => (id: number | string, fieldName: string): boolean => {
				return state.collection[id].filledFields?.[fieldName] ?? false;
			},
			/** @function tasks/isPartiallyLoaded */
			isPartiallyLoaded: (state: TasksModelState) => (id: number | string): boolean => {
				return state.partiallyLoadedIds.has(id);
			},
		};
	}

	getActions(): ActionTree<TasksModelState>
	{
		return {
			/** @function tasks/setFieldFilled */
			setFieldFilled: (store: Store, { id, fieldName }: { id: number, fieldName: string }): void => {
				store.commit('setFieldFilled', { id, fieldName });
			},
			/** @function tasks/clearFieldsFilled */
			clearFieldsFilled: (store: Store, id: number | string): void => {
				store.commit('clearFieldsFilled', id);
			},
			/** @function tasks/addPartiallyLoaded */
			addPartiallyLoaded: (store: Store, id: number | string): void => {
				store.commit('addPartiallyLoaded', id);
			},
			/** @function tasks/removePartiaalyLoaded */
			removePartiallyLoaded: (store: Store, id: number | string): void => {
				store.commit('removePartiallyLoaded', id);
			},
		};
	}

	getMutations(): MutationTree<TasksModelState>
	{
		return {
			upsert: (state: TasksModelState, task: ?TaskModel): void => {
				BuilderEntityModel.defaultModel.getMutations(this).upsert(state, task);

				this.#setFieldsFilled(state, task.id);
			},
			update: (state: TasksModelState, { id, fields }: { id: number | string, fields: TaskModel }): void => {
				BuilderEntityModel.defaultModel.getMutations(this).update(state, { id, fields });

				this.#setFieldsFilled(state, id);
			},
			setFieldFilled: (state: TasksModelState, { id, fieldName }: { id: number, fieldName: string }): void => {
				(state.collection[id].filledFields ??= {})[fieldName] = true;
			},
			clearFieldsFilled: (state: TasksModelState, id: number | string): void => {
				if (!state.collection[id])
				{
					return;
				}

				state.collection[id].filledFields = {};

				this.#setFieldsFilled(state, id);
			},
			addPartiallyLoaded: (state: TasksModelState, id: number | string): void => {
				state.partiallyLoadedIds.add(id);
			},
			removePartiallyLoaded: (state: TasksModelState, id: number | string): void => {
				state.partiallyLoadedIds.delete(id);
			},
		};
	}

	#setFieldsFilled(state: TasksModelState, id: number | string): void
	{
		const task = state.collection[id];
		const canEdit = task?.rights?.edit;

		task.filledFields ??= {};
		Object.entries(task).forEach(([fieldName: string, value: any]) => {
			const isFilled = Boolean(value) && (!Array.isArray(value) || value.length > 0);
			if (isFilled)
			{
				task.filledFields[this.#getAliasField(fieldName) ?? fieldName] = true;
			}

			if (!isFilled && !canEdit)
			{
				task.filledFields[fieldName] = false;
			}
		});
	}

	#getAliasField(fieldName: string): string
	{
		return Object.entries(aliasFields).find(([, alias]) => alias.has(fieldName))?.[0];
	}
}
