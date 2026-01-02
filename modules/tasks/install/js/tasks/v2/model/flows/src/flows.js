import { BuilderEntityModel } from 'ui.vue3.vuex';
import { Model } from 'tasks.v2.const';

import type { FlowModel, FlowsModelState } from './types';

export class Flows extends BuilderEntityModel<FlowsModelState, FlowModel>
{
	getName(): string
	{
		return Model.Flows;
	}

	getElementState(): FlowModel
	{
		return {
			id: 0,
			name: '',
			efficiency: 100,
		};
	}
}
