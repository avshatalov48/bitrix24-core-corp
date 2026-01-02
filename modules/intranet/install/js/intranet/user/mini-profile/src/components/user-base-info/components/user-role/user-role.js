import { LocMixin } from '../../../../mixins/loc-mixin';
import { UserRoleTitleByCode } from './const';

import './style.css';

// @vue/component
export const UserRole = {
	name: 'UserRole',
	mixins: [LocMixin],
	props: {
		role: {
			type: [String, null],
			required: true,
		},
	},
	computed: {
		title(): ?string
		{
			if (!this.role)
			{
				return null;
			}

			return UserRoleTitleByCode[this.role] ?? null;
		},
	},
	template: `
		<div v-if="title"
			class="intranet-user-mini-profile__role"
			data-test-id="usermp_role-title"
		>
			<div class="intranet-user-mini-profile__role-inner-text">
				{{ title }}
			</div>
		</div>
	`,
};
