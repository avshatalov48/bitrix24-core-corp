import { DetailPanelCollapsedTitle } from './detail-panel-collapsed-title';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';
import { memberRoles, AnalyticsSourceType } from 'humanresources.company-structure.api';
import { DepartmentContent } from 'humanresources.company-structure.department-content';
import { AddUserDialog } from 'humanresources.company-structure.add-user-dialog';
import { MoveUserFromDialog } from 'humanresources.company-structure.move-user-from-dialog';
import { DetailPanelEditButton } from './detail-panel-edit-button';

export const DetailPanel = {
	name: 'detailPanel',

	emits: ['showWizard', 'removeDepartment', 'update:modelValue'],

	components: { DepartmentContent, DetailPanelCollapsedTitle, DetailPanelEditButton },

	props: {
		preventPanelSwitch: Boolean,
		modelValue: Boolean,
	},

	data(): Object
	{
		return {
			title: '',
			isCollapsed: true,
			isLoading: true,
			needToShowLoader: false,
		};
	},

	computed:
	{
		...mapState(useChartStore, ['focusedNode', 'departments']),
		defaultAvatar(): String
		{
			return '/bitrix/js/humanresources/company-structure/org-chart/src/images/default-user.svg';
		},
		headAvatarsArray(): Array
		{
			const heads = this.departments.get(this.focusedNode).heads ?? [];

			return heads
				?.filter((employee) => employee.role === memberRoles.head)
				?.map((employee) => employee.avatar || this.defaultAvatar) ?? []
			;
		},
	},

	methods:
	{
		toggleCollapse(): void
		{
			this.$emit('update:modelValue', !this.isCollapsed);
		},
		updateDetailPageHandler(nodeId: number, oldId: number): void
		{
			if (!this.preventPanelSwitch && oldId !== 0)
			{
				this.$emit('update:modelValue', false);
			}

			this.isLoading = true;
			const department = this.departments.get(nodeId);

			this.title = department.name ?? '';
			this.isLoading = false;
		},
		addEmployee(): void
		{
			AddUserDialog.openDialog();
		},
		userInvite(): void
		{
			const departmentToInvite = this.departments.get(this.focusedNode).accessCode.slice(1);

			BX.SidePanel.Instance.open(
				'/bitrix/services/main/ajax.php?action=getSliderContent'
				+ '&c=bitrix%3Aintranet.invitation&mode=ajax'
				+ `&departments[]=${departmentToInvite}&firstInvitationBlock=invite-with-group-dp`,
				{ cacheable: false, allowChangeHistory: false, width: 1100 },
			);
		},
		moveEmployee(): void
		{
			const nodeId = this.focusedNode;
			MoveUserFromDialog.openDialog(nodeId);
		},
		editEmployee(): void
		{
			this.$emit('showWizard', {
				nodeId: this.focusedNode,
				isEditMode: true,
				type: 'employees',
				source: AnalyticsSourceType.DETAIL,
			});
		},
		editDepartment(): void
		{
			this.$emit('showWizard', {
				nodeId: this.focusedNode,
				isEditMode: true,
				type: 'department',
				source: AnalyticsSourceType.DETAIL,
			});
		},
		addDepartment(): void
		{
			this.$emit('showWizard', {
				nodeId: this.focusedNode,
				isEditMode: false,
				showEntitySelector: false,
				source: AnalyticsSourceType.DETAIL,
			});
		},
		removeDepartment(): void
		{
			this.$emit('removeDepartment', this.focusedNode);
		},
		showLoader(): void
		{
			this.needToShowLoader = true;
		},
		hideLoader(): void
		{
			this.needToShowLoader = false;
		},
	},

	watch: {
		focusedNode(newId: number, oldId: number): void
		{
			this.updateDetailPageHandler(newId, oldId);
		},
		modelValue(collapsed: boolean): void
		{
			this.isCollapsed = collapsed;
		},
		departments: {
			handler(newDepartments): void
			{
				const department = newDepartments.get(this.focusedNode);
				if (department)
				{
					this.title = department.name ?? '';
				}
			},
			deep: true,
		},
	},

	template: `
		<div
			:class="['humanresources-detail-panel', { '--collapsed': isCollapsed }]"
			v-on="isCollapsed ? { click: toggleCollapse } : {}"
		>
			<div
				v-if="!isLoading"
				class="humanresources-detail-panel-container"
				:class="{ '--hide': needToShowLoader && !isCollapsed }"
			>
				<div class="humanresources-detail-panel__head">
					<span
						v-if="!isCollapsed"
						class="humanresources-detail-panel__title"
						:title="title"
					>
						{{ title }}
					</span>
					<DetailPanelCollapsedTitle
						v-else
						:title="title"
						:avatars="headAvatarsArray"
					>
					</DetailPanelCollapsedTitle>
					<div class="humanresources-detail-panel__header_buttons_container">
						<DetailPanelEditButton
							v-if="!isCollapsed"
							@addEmployee="addEmployee"
							@editEmployee="editEmployee"
							@editDepartment="editDepartment"
							@addDepartment="addDepartment"
							@moveEmployee="moveEmployee"
							@removeDepartment="removeDepartment"
							@userInvite="userInvite"
						/>
						<div
							class="humanresources-detail-panel__collapse_button --icon"
							@click="toggleCollapse"
							:class="{ '--collapsed': isCollapsed }"
						/>
					</div>
				</div>
				<div class="humanresources-detail-panel__content" v-show="!isCollapsed">
					<DepartmentContent
						@editEmployee="editEmployee"
						@showDetailLoader="showLoader"
						@hideDetailLoader="hideLoader"
					/>
				</div>
			</div>
			<div v-if="needToShowLoader && !isCollapsed" class="humanresources-detail-panel-loader-container"/>
		</div>
	`,
};
