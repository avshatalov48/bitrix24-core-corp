import { BuilderEntityModel } from 'ui.vue3.vuex';
import { Model } from 'tasks.v2.const';

import { CheckListModel, CheckListModelState } from './types';

export class CheckList extends BuilderEntityModel<CheckListModelState, CheckListModel>
{
	getName(): string
	{
		return Model.CheckList;
	}

	getElementState(): CheckListModel
	{
		return {
			id: 0,
			nodeId: null,
			title: '',
			creator: null,
			toggledBy: null,
			toggledDate: null,
			accomplices: [],
			auditors: [],
			attachments: [],
			isComplete: false,
			isImportant: false,
			parentId: 0,
			parentNodeId: null,
			sortIndex: 0,
			actions: {
				modify: true,
				remove: true,
				toggle: true,
			},
			panelIsShown: false,
			myFilterActive: false,
			collapsed: false,
			expanded: false,
			localCompleteState: null,
			localCollapsedState: null,
			areCompletedCollapsed: false,
			hidden: false,
			groupMode: {
				active: false,
				selected: false,
			},
		};
	}
}
