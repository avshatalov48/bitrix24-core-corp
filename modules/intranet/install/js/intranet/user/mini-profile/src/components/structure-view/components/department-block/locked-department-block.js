import { BIcon } from 'ui.icon-set.api.vue';

import { IconSetMixin } from '../../../../mixins/icon-set-mixin';

import './style.css';

// @vue/component
export const LockedDepartmentBlock = {
	name: 'LockedDepartmentBlock',
	components: {
		BIcon,
	},
	mixins: [IconSetMixin],
	template: `
		<div 
			class="intranet-user-mini-profile__structure-view-department-block --locked"
		>
			<div class="intranet-user-mini-profile__structure-view-department-block-lock">
				<BIcon 
					:size="30" 
					:name="outlineSet.LOCK_L"
				/>
			</div>
		</div>
	`,
};
