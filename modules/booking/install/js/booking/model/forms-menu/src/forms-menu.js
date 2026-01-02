/* eslint-disable no-param-reassign */

import { BuilderModel } from 'ui.vue3.vuex';
import type { ActionTree, GetterTree, MutationTree } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import type { FormsMenuState, FormsMenuModel, FormsMenuItem } from './types';

export class FormsMenu extends BuilderModel
{
	getName(): string
	{
		return Model.FormsMenu;
	}

	getState(): FormsMenuState
	{
		return {
			canEdit: false,
			createFormLink: '',
			formList: [],
			formsListLink: '',
		};
	}

	getGetters(): GetterTree<FormsMenuState, any>
	{
		return {
			formList: (state): FormsMenuItem => {
				return state.formList;
			},
		};
	}

	getActions(): ActionTree<FormsMenuState>
	{
		return {
			setFormsMenu: ({ commit }, formsMenu: FormsMenuModel): void => {
				commit('setFormsMenu', formsMenu);
			},
		};
	}

	getMutations(): MutationTree<FormsMenuState>
	{
		return {
			setFormsMenu: (state, formsMenu: FormsMenuModel): void => {
				state.canEdit = formsMenu.canEdit;
				state.createFormLink = formsMenu.createFormLink;
				state.formList = formsMenu.formList || [];
				state.formsListLink = formsMenu.formsListLink;
			},
		};
	}
}
