import { EntityTypes } from 'humanresources.company-structure.utils';
import type { BaseEvent } from 'main.core.events';
import { EventEmitter } from 'main.core.events';
import { events } from '../../consts';
import { DetailPanelCollapsedTitle } from './detail-panel-collapsed-title';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';
import { getMemberRoles, type MemberRolesType } from 'humanresources.company-structure.api';
import { DepartmentContent } from 'humanresources.company-structure.department-content';
import { Hint } from 'humanresources.company-structure.structure-components';
import { DetailPanelEditButton } from './detail-panel-edit-button';

import './style.css';

// @vue/component
export const DetailPanel = {
	name: 'detailPanel',

	components: { DepartmentContent, DetailPanelCollapsedTitle, DetailPanelEditButton },

	directives: { hint: Hint },

	props: {
		preventPanelSwitch: Boolean,
		modelValue: Boolean,
	},

	emits: ['showWizard', 'removeDepartment', 'update:modelValue'],

	data(): Object
	{
		return {
			title: '',
			isTeamEntity: false,
			teamColor: null,
			isCollapsed: true,
			isHidden: false,
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
				?.filter((employee) => employee.role === this.memberRoles.head)
				?.map((employee) => employee.avatar || this.defaultAvatar) ?? []
			;
		},
		entityType(): string
		{
			return this.departments.get(this.focusedNode).entityType;
		},
		memberRoles(): MemberRolesType
		{
			return getMemberRoles(this.entityType);
		},
		isPanelCollapsed(): boolean
		{
			return this.isCollapsed || this.isHidden;
		}
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

	created(): void
	{
		this.subscribeOnEvents();
	},

	beforeUnmount(): void
	{
		this.unsubscribeFromEvents();
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

			this.isTeamEntity = department.entityType === EntityTypes.team;
			this.title = department.name ?? '';
			this.teamColor = department.teamColor ?? null;
			this.isLoading = false;
		},
		showLoader(): void
		{
			this.needToShowLoader = true;
		},
		hideLoader(): void
		{
			this.needToShowLoader = false;
		},
		subscribeOnEvents(): void
		{
			this.events = {
				[events.HR_ENTITY_TOGGLE_ELEMENTS]: this.onToggleDetailPanel,
			};
			Object.entries(this.events).forEach(([event, handle]) => {
				EventEmitter.subscribe(event, handle);
			});
		},
		unsubscribeFromEvents(): void
		{
			Object.entries(this.events).forEach(([event, handle]) => {
				EventEmitter.unsubscribe(event, handle);
			});
		},
		onToggleDetailPanel({ data }: BaseEvent): void
		{
			const { shouldShowElements } = data;
			this.isHidden = !shouldShowElements;
		},
	},

	template: `
		<div
			:class="['humanresources-detail-panel', { '--collapsed': isPanelCollapsed }]"
			v-on="isPanelCollapsed ? { click: toggleCollapse } : {}"
			data-id="hr-department-detail-panel__container"
		>
			<div
				v-if="!isLoading"
				class="humanresources-detail-panel-container"
				:class="{ '--hide': needToShowLoader && !isPanelCollapsed }"
			>
				<div class="humanresources-detail-panel__head" 
					 :class="{ '--team': isTeamEntity, '--collapsed': isPanelCollapsed }"
					 :style="{ '--team-head-background': teamColor?.treeHeadBackground }"
				>
					<span
						v-if="!isPanelCollapsed"
						v-hint
						class="humanresources-detail-panel__title"
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
							v-if="!isPanelCollapsed"
							:entityId="focusedNode"
							:entityType="entityType"
						/>
						<div
							class="humanresources-detail-panel__collapse_button --icon"
							@click="toggleCollapse"
							:class="{ '--collapsed': isPanelCollapsed }"
							data-id="hr-department-detail-panel__collapse-button"
						/>
					</div>
				</div>
				<div class="humanresources-detail-panel__content" v-show="!isPanelCollapsed">
					<DepartmentContent
						@showDetailLoader="showLoader"
						@hideDetailLoader="hideLoader"
						:isCollapsed="isPanelCollapsed"
					/>
				</div>
			</div>
			<div v-if="needToShowLoader && !isPanelCollapsed" class="humanresources-detail-panel-loader-container"/>
		</div>
	`,
};
