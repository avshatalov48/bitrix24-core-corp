import { BuilderEntityModel } from 'ui.vue3.vuex';
import { Model } from 'tasks.v2.const';

import type { StageModel, StagesModelState } from './types';

export class Stages extends BuilderEntityModel<StagesModelState, StageModel>
{
	getName(): string
	{
		return Model.Stages;
	}

	getElementState(): StageModel
	{
		return {
			id: 0,
			title: '',
			color: '',
		};
	}
}
