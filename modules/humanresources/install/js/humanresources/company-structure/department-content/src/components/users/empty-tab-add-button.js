import { AnalyticsSourceType } from 'humanresources.company-structure.api';
import { EmptyUsersTabActionMenu } from 'humanresources.company-structure.org-chart';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { EntityTypes } from 'humanresources.company-structure.utils';
import { mapState } from 'ui.vue3.pinia';
import { RouteActionMenu } from 'humanresources.company-structure.structure-components';

import 'ui.icon-set.crm';
import 'ui.icon-set.main';
import './styles/empty-tab-add-buttons.css';

export const EmptyTabAddButton = {
	name: 'emptyStateContainer',

	components: { RouteActionMenu },

	props: {
		departmentId: {
			type: Number,
			required: true,
		},
		entityType: {
			type: String,
			default: EntityTypes.department,
		},
	},

	data(): Object
	{
		return {
			menuVisible: false,
		};
	},

	computed: {
		menu(): EmptyUsersTabActionMenu
		{
			return new EmptyUsersTabActionMenu(this.departmentId, AnalyticsSourceType.DETAIL, null, this.entityType);
		},
		...mapState(useChartStore, ['focusedNode']),
	},

	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		onClick(): void
		{
			if ((this.entityType === EntityTypes.team) && (this.menu.items.length === 1))
			{
				// for teams for now there can be only one item, so we invoke action right away
				this.onActionMenuItemClick(this.menu.items[0].id);
			}
			else
			{
				this.menuVisible = true;
			}
		},
		onActionMenuItemClick(actionId: string): void
		{
			this.menu.onActionMenuItemClick(actionId);
		},
	},

	template: `
		<div class="hr-department-detail-content__users-empty-tab-add_buttons-container">
			<button
				class="hr-add-employee-empty-tab-entity-btn ui-btn ui-btn ui-btn-sm ui-btn-primary ui-btn-round"
				ref="actionMenuButton"
				@click.stop="onClick"
				data-id="hr-department-detail-content__user-empty-tab_add-user-button"
			>
				<span class="hr-add-employee-empty-tab-entity-btn-text">{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_ADD_USER_ADD_BUTTON')}}</span>
			</button>
			<RouteActionMenu
				v-if="menuVisible"
				:id="'empty-state-department-detail-add-menu-' + focusedNode"
				:items="menu.items"
				:width="302"
				:bindElement="$refs['actionMenuButton']"
				@action="onActionMenuItemClick"
				@close="menuVisible = false"
			/>
		</div>
	`,
};
