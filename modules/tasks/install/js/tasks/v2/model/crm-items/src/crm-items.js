import { BuilderEntityModel } from 'ui.vue3.vuex';
import { Model } from 'tasks.v2.const';

import type { CrmItemModel, CrmItemsModelState } from './types';

export class CrmItems extends BuilderEntityModel<CrmItemsModelState, CrmItemModel>
{
	getName(): string
	{
		return Model.CrmItems;
	}
}
