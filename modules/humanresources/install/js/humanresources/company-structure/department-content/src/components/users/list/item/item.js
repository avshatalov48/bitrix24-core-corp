import { memberRoles } from 'humanresources.company-structure.api';
import { UserListItemActionButton } from './action-button';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { SidePanel } from 'main.sidepanel';
import { mapState } from 'ui.vue3.pinia';

import 'ui.tooltip';
import './styles/item.css';

export const UserListItem = {
	name: 'userList',

	props: {
		user: {
			type: Object,
			required: true,
		},
		selectedUserId: {
			type: Number,
			required: false,
			default: null,
		},
	},

	components: { UserListItemActionButton },

	computed: {
		memberRoles(): typeof memberRoles
		{
			return memberRoles;
		},
		defaultAvatar(): string
		{
			return '/bitrix/js/humanresources/company-structure/org-chart/src/images/default-user.svg';
		},
		...mapState(useChartStore, ['focusedNode']),
	},

	methods: {
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		handleUserClick(item): void
		{
			SidePanel.Instance.open(item.url, {
				cacheable: false,
			});
		},
	},

	template: `
		<div 
			:key="user.id"
			class="hr-department-detail-content__user-container"
			:class="{ '--searched': user.id === selectedUserId }"
			:data-id="'hr-department-detail-content__user-' + user.id + '-item'"
		>
			<div class="hr-department-detail-content__user-avatar-container" @click="handleUserClick(user)">
				<img 
					class="hr-department-detail-content__user-avatar-img"
					:src="user.avatar ? encodeURI(user.avatar) : defaultAvatar"
				/>
				<div v-if="user.role === memberRoles.head" class="hr-department-detail-content__user-avatar-overlay"></div>
			</div>
			<div class="hr-department-detail-content-user__text-container">
				<div class="hr-department-detail-content__user-title">
					<div 
						class="hr-department-detail-content__user-name" 
						@click="handleUserClick(user)"
						:bx-tooltip-user-id="user.id"
						:data-id="'hr-department-detail-content__user-' + user.id + '-item-name'"
					>
						{{ user.name }}
					</div>
					<div v-if="user.badgeText" class="hr-department-detail-content-user__name-badge">{{ user.badgeText }}</div>
				</div>
				<div 
					class="hr-department-detail-content__user-subtitle" 
					:class="{ '--without-work-position': !user.subtitle }"
				>
					{{ (user.subtitle?.length ?? 0) > 0 ? user.subtitle : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_LIST_DEFAULT_WORK_POSITION') }}
				</div>
				<div v-if="user.isInvited" class="hr-department-detail-content-user__item-badge">
					{{ this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_LIST_INVITED_BADGE_TEXT') }}
				</div>
			</div>
			<UserListItemActionButton
				:user="user"
				:departmentId="focusedNode"
			/>
		</div>
	`,
};
