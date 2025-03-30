import { emptyStateTypes } from './empty-state-types';
import { CRM, Main } from 'ui.icon-set.api.core';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';
import { getColorCode } from 'humanresources.company-structure.utils';
import { RouteActionMenu } from 'humanresources.company-structure.structure-components';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';

import 'ui.icon-set.crm';
import 'ui.icon-set.main';
import './styles/empty-state-container.css';

const MenuOption = Object.freeze({
	moveUser: 'moveUser',
	userInvite: 'userInvite',
	addToDepartment: 'addToDepartment',
});

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

	created(): void
	{
		this.permissionChecker = PermissionChecker.getInstance();
		this.menuItems = this.getMenuItems();
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
			this.$emit(actionId, { type: 'employee', bindElement: this.$refs.actionMenuButton });
		},
		getMenuItems(): Array
		{
			if (!this.permissionChecker)
			{
				return [];
			}

			return [
				{
					id: MenuOption.moveUser,
					title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_CONTENT_EDIT_MENU_MOVE_EMPLOYEE_TITLE'),
					description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_CONTENT_EDIT_MENU_MOVE_EMPLOYEE_SUBTITLE'),
					bIcon: {
						name: Main.PERSON_ARROW_LEFT_1,
						size: 20,
						color: getColorCode('paletteBlue50'),
					},
					permission: { action: PermissionActions.employeeAddToDepartment },
				},
				{
					id: MenuOption.userInvite,
					title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_CONTENT_EDIT_MENU_INVITE_EMPLOYEE_TITLE'),
					description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_CONTENT_EDIT_MENU_INVITE_EMPLOYEE_SUBTITLE'),
					bIcon: {
						name: Main.PERSON_LETTER,
						size: 20,
						color: getColorCode('paletteBlue50'),
					},
					permission: { action: PermissionActions.inviteToDepartment },
				},
				{
					id: MenuOption.addToDepartment,
					title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_CONTENT_EDIT_MENU_ADD_EMPLOYEE_TITLE'),
					description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_CONTENT_EDIT_MENU_ADD_EMPLOYEE_SUBTITLE'),
					bIcon: {
						name: CRM.PERSON_PLUS_2,
						size: 20,
						color: getColorCode('paletteBlue50'),
					},
					permission: { action: PermissionActions.employeeAddToDepartment },
				},
			].filter((item) => {
				if (!item.permission)
				{
					return false;
				}

				return this.permissionChecker.hasPermission(item.permission.action, this.focusedNode);
			});
		},
	},

	watch:
	{
		departmentId()
		{
			this.menuItems = this.getMenuItems();
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
						:items="menuItems"
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
