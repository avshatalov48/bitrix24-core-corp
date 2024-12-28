import { Loc } from 'main.core';
import { memberRoles } from 'humanresources.company-structure.api';
import { HeadUsers } from './head-users';
import type { DepartmentData } from '../../types';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';

export const TreeNode = {
	name: 'treeNode',

	components: { HeadUsers },

	props: {
		department: Object,
		name: String,
		heads: Array,
		userCount: Number,
		nodeId: Number,
	},

	computed:
	{
		departmentData(): DepartmentData | { name: string, ...Partial<DepartmentData> }
		{
			if (this.isExistingDepartment)
			{
				return this.department;
			}

			return {
				name: this.name,
				heads: this.heads,
				userCount: this.userCount,
			};
		},
		isExistingDepartment(): boolean
		{
			return Boolean(this.nodeId);
		},
		employeesCount(): number
		{
			return (this.userCount || 0) - (this.heads?.length || 0);
		},
		headUsers(): Array<DepartmentData['heads']>
		{
			return this.departmentData.heads.filter((head) => {
				return head.role === memberRoles.head;
			});
		},
		deputyUsers(): Array<DepartmentData['heads']>
		{
			return this.departmentData.heads.filter((head) => {
				return head.role === memberRoles.deputyHead;
			});
		},
		showInfo(): boolean
		{
			return this.nodeId
				? PermissionChecker.getInstance().hasPermission(PermissionActions.structureView, this.nodeId)
				: true;
		},
	},

	methods: {
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		locPlural(phraseCode: string, count: number): string
		{
			return Loc.getMessagePlural(phraseCode, count, { '#COUNT#': count });
		},
	},

	template: `
		<div
			class="chart-wizard-tree-preview__node"
			:class="{ '--new': !isExistingDepartment }"
		>
			<div class="chart-wizard-tree-preview__node_summary">
				<p class="chart-wizard-tree-preview__node_name --crop">
					{{departmentData.name}}
				</p>
				<HeadUsers
					v-if="showInfo"
					:users="headUsers"
					:showPlaceholder="!isExistingDepartment"
				/>
				<div
					v-if="showInfo && !isExistingDepartment"
					class="chart-wizard-tree-preview__node_employees"
				>
					<div>
						<p class="chart-wizard-tree-preview__node_employees-title">
							{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_EMPLOYEES_TITLE')}}
						</p>
						<span class="chart-wizard-tree-preview__node_employees_count">
							{{locPlural('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_EMPLOYEES_COUNT', employeesCount)}}
						</span>
					</div>
					<div class="chart-wizard-tree-preview__node_deputies">
						<p class="chart-wizard-tree-preview__node_employees-title">
							{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_DEPUTIES_TITLE')}}
						</p>
						<HeadUsers
							:users="deputyUsers"
							userType="deputy"
						/>
					</div>
				</div>
			</div>
			<slot v-if="isExistingDepartment"></slot>
		</div>
	`,
};
