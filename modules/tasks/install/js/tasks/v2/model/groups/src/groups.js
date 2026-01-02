import { BuilderEntityModel } from 'ui.vue3.vuex';
import { Model } from 'tasks.v2.const';

import type { GroupModel, GroupsModelState } from './types';

export class Groups extends BuilderEntityModel<GroupsModelState, GroupModel>
{
	getName(): string
	{
		return Model.Groups;
	}

	getElementState(): GroupModel
	{
		return {
			id: 0,
			name: '',
			image: '',
		};
	}
}
