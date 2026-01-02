import { BuilderEntityModel } from 'ui.vue3.vuex';
import { Model } from 'tasks.v2.const';

import type { UserModel, UsersModelState } from './types';

export class Users extends BuilderEntityModel<UsersModelState, UserModel>
{
	getName(): string
	{
		return Model.Users;
	}

	getElementState(): UserModel
	{
		return {
			id: 0,
			name: '',
			image: '',
		};
	}
}
