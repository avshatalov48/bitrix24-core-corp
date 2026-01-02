import { AnalyticsSourceType } from 'humanresources.company-structure.api';
import { EmptyUsersTabActionMenu } from 'humanresources.company-structure.org-chart';
import { emptyStateTypes } from './empty-state-types';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';
import { RouteActionMenu } from 'humanresources.company-structure.structure-components';

import 'ui.icon-set.crm';
import 'ui.icon-set.main';
import './styles/empty-state-container.css';

// @vue/component
export const EmptyStateContainer = {
	name: 'emptyStateContainer',

	props: {
		type: {
			type: String,
			required: true,
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

	data(): Object
	{
		return {
			menuVisible: false,
		};
	},

	computed: {
		showAddButtons(): boolean
		{
			return this.type === emptyStateTypes.NO_MEMBERS_WITH_ADD_PERMISSION;
		},
		menu(): EmptyUsersTabActionMenu
		{
			return new EmptyUsersTabActionMenu(this.departmentId, AnalyticsSourceType.DETAIL, 'employee');
		},
		...mapState(useChartStore, ['focusedNode']),
	},

	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		emptyStateIconClass(): ?string
		{
			if (this.type === emptyStateTypes.NO_SEARCHED_USERS_RESULTS)
			{
				return '--user-not-found';
			}

			if (this.type === emptyStateTypes.NO_MEMBERS_WITH_ADD_PERMISSION)
			{
				return '--with-add-permission';
			}

			if (this.type === emptyStateTypes.NO_MEMBERS_WITHOUT_ADD_PERMISSION)
			{
				return '--without-add-permission';
			}

			return null;
		},
		emptyStateTitle(): ?string
		{
			if (this.type === emptyStateTypes.NO_SEARCHED_USERS_RESULTS)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_SEARCHED_EMPLOYEES_TITLE');
			}

			if (this.type === emptyStateTypes.NO_MEMBERS_WITH_ADD_PERMISSION)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_ADD_USER_TITLE');
			}

			if (this.type === emptyStateTypes.NO_MEMBERS_WITHOUT_ADD_PERMISSION)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_TITLE');
			}

			return null;
		},
		emptyStateDescription(): ?string
		{
			if (this.type === emptyStateTypes.NO_SEARCHED_USERS_RESULTS)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_SEARCHED_EMPLOYEES_SUBTITLE');
			}

			if (this.type === emptyStateTypes.NO_MEMBERS_WITH_ADD_PERMISSION)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_ADD_USER_SUBTITLE');
			}

			return null;
		},
		onActionMenuItemClick(actionId: string): void
		{
			this.menu.onActionMenuItemClick(actionId);
		},
	},

	template: `
		<div class="hr-department-detail-content__tab-container --empty">
			<div :class="['hr-department-detail-content__tab-entity-icon', this.emptyStateIconClass()]"></div>
			<div class="hr-department-detail-content__tab-entity-content">
				<span class="hr-department-detail-content__empty-tab-entity-title">
					{{ this.emptyStateTitle() }}
				</span>
				<span class="hr-department-detail-content__empty-tab-entity-subtitle">
					{{ this.emptyStateDescription() }}
				</span>
				<div v-if="showAddButtons" class="hr-department-detail-content__empty-tab-entity-buttons-container">
					<button
						class="hr-add-employee-empty-tab-entity-btn ui-btn ui-btn ui-btn-sm ui-btn-primary ui-btn-round"
						ref="actionMenuButton"
						@click.stop="menuVisible = true"
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
			</div>
		</div>
	`,
};
