import { mapState } from 'ui.vue3.pinia';
import { Loader } from 'main.loader';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { Loc } from 'main.core';
import { getMemberRoles } from 'humanresources.company-structure.api';
import { EntityTypes } from 'humanresources.company-structure.utils';
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
		entityType: {
			type: String,
			default: EntityTypes.department,
		},
		/** @type NodeColorSettingsType | null */
		teamColor: {
			type: [Object, null],
			default: null,
		},
	},

	data(): { isShowLoader: boolean; }
	{
		return { isShowLoader: false };
	},

	created(): void
	{
		this.memberRoles = getMemberRoles(this.departmentData.entityType);
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
				entityType: this.entityType,
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
				return head.role === this.memberRoles.head;
			});
		},
		deputyUsers(): ?Array<DepartmentData['heads']>
		{
			return this.departmentData.heads?.filter((head) => {
				return head.role === this.memberRoles.deputyHead;
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
		isTeamEntity(): boolean
		{
			return this.departmentData.entityType === EntityTypes.team;
		},
		dataTestIdSuffix(): string
		{
			return this.isExistingDepartment ? '' : '_new';
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
		formatDataTestId(prefix: string): string
		{
			return prefix + this.dataTestIdSuffix;
		},
	},

	template: `
		<div
			class="chart-wizard-tree-preview__node"
			:class="{ '--new': !isExistingDepartment, '--old': isExistingDepartment,'--team': isTeamEntity }"
		>
			<div 
				class="chart-wizard-tree-preview__node_summary" 
				:class="{ '--old': isExistingDepartment, '--team': isTeamEntity }"
				:style="{ '--team-border-color': teamColor?.previewBorder }"
			>
				<div 
					class="chart-wizard-tree-preview__node_name --crop"
					:data-test-id="formatDataTestId('chart-wizard-tree-preview__node_name')"
					:style="{ 'background-color': teamColor ? teamColor.headBackground : false }"
				>
					<span
						v-if="!departmentData.name" 
						class="chart-wizard-tree-preview__placeholder_node_name"
						:class="{ '--team': isTeamEntity }"
						:style="{ 'background-color': teamColor ? teamColor.namePlaceholder : false }"
					></span>
					{{departmentData.name}}
				</div>
				<div 
					class="chart-wizard-tree-preview__node_content"
					:data-test-id="formatDataTestId('chart-wizard-tree-preview__head_list')"
				>
					<div
						class="chart-wizard-tree-preview__head_list"
						:data-test-id="formatDataTestId('chart-wizard-tree-preview__head_list')"
					>
						<HeadUsers
							v-if="showInfo && headUsers"
							:users="headUsers"
							:showPlaceholder="!isExistingDepartment"
							:isTeamEntity="isTeamEntity"
							:isExistingDepartment="isExistingDepartment"
							:teamColor="teamColor"
						/> 
					</div>
					<div v-if="isShowLoader" ref="loaderContainer"></div>
					<div
						v-if="showInfo && !isExistingDepartment"
						class="chart-wizard-tree-preview__node_employees"
					>
						<div
							class="chart-wizard-tree-preview__node_employees-list"
							:data-test-id="formatDataTestId('chart-wizard-tree-preview__node_employees-list')"
						>
							<p class="chart-wizard-tree-preview__node_employees-title">
								{{
									isTeamEntity 
										? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_TEAM_EMPLOYEES_TITLE') 
										: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_EMPLOYEES_TITLE')
								}}
							</p>
							<span class="chart-wizard-tree-preview__node_employees_count">
								{{ locPlural('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_EMPLOYEES_COUNT', employeesCount) }}
							</span>
						</div>
						<div 
							class="chart-wizard-tree-preview__node_deputies"
							:data-test-id="formatDataTestId('chart-wizard-tree-preview__node_deputies-list')"
						>
							<p class="chart-wizard-tree-preview__node_employees-title">
								{{
									isTeamEntity
										? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_TEAM_DEPUTIES_TITLE') 
										: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_DEPUTIES_TITLE') 
								}}
							</p>
							<HeadUsers
								:users="deputyUsers"
								:isTeamEntity="isTeamEntity"
								:isExistingDepartment="isExistingDepartment"
								userType="deputy"
								:teamColor="teamColor"
							/>
						</div>
					</div>
				</div>
			</div>
			<slot v-if="isExistingDepartment"></slot>
		</div>
	`,
};
