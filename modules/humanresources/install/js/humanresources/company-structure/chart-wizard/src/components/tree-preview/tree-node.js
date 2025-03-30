import { mapState } from 'ui.vue3.pinia';
import { Loader } from 'main.loader';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { Loc } from 'main.core';
import { memberRoles } from 'humanresources.company-structure.api';
import { HeadUsers } from './head-users';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';
import type { DepartmentData } from '../../types';

export const TreeNode = {
	name: 'treeNode',

	components: { HeadUsers },

	props: {
		name: String,
		heads: Array,
		userCount: Number,
		nodeId: Number,
	},

	data(): { isShowLoader: boolean; }
	{
		return { isShowLoader: false };
	},

	watch:
	{
		isShowLoader(newValue: boolean): void
		{
			if (!newValue)
			{
				return;
			}

			this.$nextTick(() => {
				const { loaderContainer } = this.$refs;
				const loader = new Loader({ size: 30 });
				loader.show(loaderContainer);
			});
		},
	},
	computed:
	{
		departmentData(): DepartmentData | { name: string, ...Partial<DepartmentData> }
		{
			if (this.isExistingDepartment)
			{
				if (!this.isHeadsLoaded)
				{
					this.loadHeads([this.nodeId]);
				}

				return this.departments.get(this.nodeId);
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
		headUsers(): ?Array<DepartmentData['heads']>
		{
			return this.departmentData.heads?.filter((head) => {
				return head.role === memberRoles.head;
			});
		},
		deputyUsers(): ?Array<DepartmentData['heads']>
		{
			return this.departmentData.heads?.filter((head) => {
				return head.role === memberRoles.deputyHead;
			});
		},
		showInfo(): boolean
		{
			return this.nodeId
				? PermissionChecker.getInstance().hasPermission(PermissionActions.structureView, this.nodeId)
				: true;
		},
		isHeadsLoaded(departmentId: number): boolean
		{
			const { heads } = this.departments.get(this.nodeId);

			return Boolean(heads);
		},
		...mapState(useChartStore, ['departments']),
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
		async loadHeads(departmentIds: number[]): Promise<void>
		{
			const store = useChartStore();
			try
			{
				this.isShowLoader = true;
				await store.loadHeads(departmentIds);
			}
			finally
			{
				this.isShowLoader = false;
			}
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
					v-if="showInfo && headUsers"
					:users="headUsers"
					:showPlaceholder="!isExistingDepartment"
				/>
				<div v-if="isShowLoader" ref="loaderContainer"></div>
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
