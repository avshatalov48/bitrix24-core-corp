/* eslint-disable no-param-reassign */
import { BuilderModel } from 'ui.vue3.vuex';
import type { ActionTree, GetterTree, MutationTree } from 'ui.vue3.vuex';
import { Model } from 'tasks.v2.const';

import type {
	InterfaceModelParams,
	InterfaceModelState,
	CheckListCompletionCallback,
	CheckListCompletionCallbacks,
	DefaultDeadline,
} from './types';

export class Interface extends BuilderModel
{
	static createWithVariables(params: InterfaceModelParams): BuilderModel
	{
		return Interface.create().setVariables({
			currentUserId: params.currentUserId,
			defaultDeadline: params.defaultDeadline,
		});
	}

	getName(): string
	{
		return Model.Interface;
	}

	getState(): InterfaceModelState
	{
		return {
			currentUserId: this.getVariable('currentUserId', 0),
			titleFieldOffsetHeight: this.getVariable('titleFieldOffsetHeight', null),
			defaultDeadline: this.getVariable('defaultDeadline', {
				defaultDeadlineInSeconds: 0,
				defaultDeadlineDate: '',
			}),
			deletingCheckListIds: {},
			disableCheckListAnimations: false,
			checkListCompletionCallbacks: {},
		};
	}

	getGetters(): GetterTree<InterfaceModelState>
	{
		return {
			/** @function interface/currentUserId */
			currentUserId: (state: InterfaceModelState): number => state.currentUserId,
			/** @function interface/titleFieldOffsetHeight */
			titleFieldOffsetHeight: (state: InterfaceModelState): number => state.titleFieldOffsetHeight,
			/** @function interface/defaultDeadline */
			defaultDeadline: (state: InterfaceModelState): DefaultDeadline => state.defaultDeadline,
			/** @function interface/deletingCheckListIds */
			deletingCheckListIds: (state: InterfaceModelState): { [id: number]: number } => state.deletingCheckListIds,
			/** @function interface/disableCheckListAnimations */
			disableCheckListAnimations: (state: InterfaceModelState): number => state.disableCheckListAnimations,
		};
	}

	getActions(): ActionTree<InterfaceModelState>
	{
		return {
			/** @function interface/updateTitleFieldOffsetHeight */
			updateTitleFieldOffsetHeight: (store, titleFieldOffsetHeight: number) => {
				store.commit('setTitleFieldOffsetHeight', titleFieldOffsetHeight);
			},
			/** @function interface/updateDefaultDeadline */
			updateDefaultDeadline: (store, defaultDeadline: DefaultDeadline) => {
				store.commit('setDefaultDeadline', defaultDeadline);
			},
			/** @function interface/addCheckListItemToDeleting */
			addCheckListItemToDeleting: (store, itemId: number | string) => {
				store.commit('addCheckListItemToDeleting', itemId);
			},
			/** @function interface/removeCheckListItemFromDeleting */
			removeCheckListItemFromDeleting: (store, itemId: number | string) => {
				store.commit('removeCheckListItemFromDeleting', itemId);
			},
			/** @function interface/addCheckListCompletionCallback */
			addCheckListCompletionCallback: (store, { id, callback }) => {
				store.commit('addCheckListCompletionCallback', { id, callback });
			},
			/** @function interface/executeCheckListCompletionCallbacks */
			executeCheckListCompletionCallbacks: (store) => {
				store.commit('executeCheckListCompletionCallbacks');
			},
			/** @function interface/setDisableCheckListAnimations */
			setDisableCheckListAnimations: (store, disableCheckListAnimations: boolean) => {
				store.commit('setDisableCheckListAnimations', disableCheckListAnimations);
			},
		};
	}

	getMutations(): MutationTree<InterfaceModelState>
	{
		return {
			setTitleFieldOffsetHeight: (state: InterfaceModelState, titleFieldOffsetHeight: number) => {
				state.titleFieldOffsetHeight = titleFieldOffsetHeight;
			},
			setDefaultDeadline: (state: InterfaceModelState, defaultDeadline: DefaultDeadline) => {
				state.defaultDeadline = defaultDeadline;
			},
			addCheckListItemToDeleting: (state: InterfaceModelState, itemId: number | string) => {
				state.deletingCheckListIds[itemId] = itemId;
			},
			removeCheckListItemFromDeleting: (state: InterfaceModelState, itemId: number) => {
				delete state.deletingCheckListIds[itemId];
			},
			addCheckListCompletionCallback: (state: InterfaceModelState, { id, callback }) => {
				state.checkListCompletionCallbacks[id] = callback;
			},
			executeCheckListCompletionCallbacks: (state: InterfaceModelState) => {
				Object.values(state.checkListCompletionCallbacks).forEach((cb: CheckListCompletionCallback) => cb());
				state.checkListCompletionCallbacks = {};
			},
			setDisableCheckListAnimations: (state: InterfaceModelState, disableCheckListAnimations: boolean) => {
				state.disableCheckListAnimations = disableCheckListAnimations === true;
			},
		};
	}
}
