import { AnalyticsSourceType } from 'humanresources.company-structure.api';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';
import { RouteActionMenu } from 'humanresources.company-structure.structure-components';
import { UsersTabActionMenu } from 'humanresources.company-structure.org-chart';

import 'ui.icon-set.crm';
import './styles/list-action-button.css';

// @vue/component
export const UserListActionButton = {
	name: 'userListActionButton',
	emits: ['addToDepartment', 'editDepartmentUsers'],

	props:
	{
		role: {
			type: String,
			default: 'employee',
		},
		departmentId: {
			type: Number,
			required: true,
		},
	},

	components:
	{
		RouteActionMenu,
	},

	computed:
	{
		...mapState(useChartStore, ['focusedNode']),
		menu(): UsersTabActionMenu
		{
			return new UsersTabActionMenu(this.departmentId, AnalyticsSourceType.DETAIL, this.role);
		},
	},

	data(): Object
	{
		return {
			menuVisible: false,
		};
	},

	methods: {
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		onActionMenuItemClick(actionId: string): void
		{
			this.menu.onActionMenuItemClick(actionId);
		},
	},

	template: `
		<button
			v-if="menu.items.length"
			class="hr-department-detail-content__list-header-button"
			:class="{ '--focused': menuVisible }"
			:ref="'actionMenuButton' + role"
			@click.stop="menuVisible = true"
			:data-ld="'hr-department-detail-content__' + role + '_list-header-button'"
		>
			{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_LIST_ACTION_BUTTON_TITLE') }}
		</button>
		<RouteActionMenu
			v-if="menuVisible"
			:id="'tree-node-department-menu-' + role + '-' + focusedNode"
			:items="menu.items"
			:width="302"
			:bindElement="$refs['actionMenuButton' + role]"
			@action="onActionMenuItemClick"
			@close="menuVisible = false"
		/>
	`,
};
