import { BuilderEntityModel } from 'ui.vue3.vuex';
import { Model } from 'tasks.v2.const';

import type { EpicModel, EpicsModelState } from './types';

export class Epics extends BuilderEntityModel<EpicsModelState, EpicModel>
{
	getName(): string
	{
		return Model.Epics;
	}

	getElementState(): EpicModel
	{
		return {
			id: 0,
			title: '',
			color: '',
		};
	}
}
