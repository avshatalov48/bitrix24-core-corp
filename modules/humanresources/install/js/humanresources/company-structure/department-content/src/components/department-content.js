import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { EntityTypes } from 'humanresources.company-structure.utils';
import { UsersTab } from './users/tab';
import { ChatsTab } from './chats/tab';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';

import '../style.css';

// @vue/component
export const DepartmentContent = {
	name: 'departmentContent',

	components: { UsersTab, ChatsTab },

	props: {
		isCollapsed: Boolean,
	},

	emits: ['showDetailLoader', 'hideDetailLoader', 'editEmployee'],

	data(): Object
	{
		return {
			activeTab: 'usersTab',
			tabs: [
				{ name: 'usersTab', component: 'UsersTab', id: 'users-tab' },
				{ name: 'chatsTab', component: 'ChatsTab', id: 'chats-tab' },
			],
			isDescriptionOverflowed: false,
			isDescriptionExpanded: false,
		};
	},

	computed:
	{
		isCollabsAvailable(): boolean
		{
			return PermissionChecker.getInstance().isCollabsAvailable;
		},
		activeTabComponent(): UsersTab | ChatsTab | null
		{
			const activeTab = this.tabs.find((tab) => tab.name === this.activeTab);

			return activeTab ? activeTab.component : null;
		},
		usersCount(): Number
		{
			return this.departments.get(this.focusedNode)?.userCount ?? 0;
		},
		communicationsCount(): Number
		{
			return this.departments.get(this.focusedNode)?.communicationsCount ?? null;
		},
		tabArray(): Array
		{
			return this.tabs.map((tab) => {
				if (tab.name === 'usersTab')
				{
					return {
						...tab,
						count: this.usersCount,
					};
				}

				if (tab.name === 'chatsTab')
				{
					return {
						...tab,
						count: this.communicationsCount,
					};
				}

				return tab;
			});
		},
		description(): ?string
		{
			const department = this.departments.get(this.focusedNode);
			if (!department.description)
			{
				return null;
			}

			return department.description;
		},
		isTeamEntity(): boolean
		{
			return this.departments.get(this.focusedNode)?.entityType === EntityTypes.team;
		},
		...mapState(useChartStore, ['focusedNode', 'departments']),
	},

	watch:
	{
		description(): void
		{
			this.$nextTick(() => {
				this.isDescriptionOverflowed = this.checkDescriptionOverflowed();
			});
		},
		focusedNode(): void
		{
			this.isDescriptionExpanded = false;
			this.selectTab('usersTab');
		},
		isCollapsed(): void
		{
			this.$nextTick(() => {
				this.isDescriptionOverflowed = this.checkDescriptionOverflowed();
			});
		},
	},

	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		selectTab(tabName): void
		{
			if (this.isTabDisabled(tabName))
			{
				return;
			}
			this.activeTab = tabName;
		},
		getTabLabel(name: string): string
		{
			if (name === 'usersTab')
			{
				return this.isTeamEntity
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_TEAM_USERS_TITLE')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TITLE');
			}

			if (name === 'chatsTab')
			{
				return this.isCollabsAvailable
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_TITLE_W_COLLABS')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_TITLE')
				;
			}

			return '';
		},
		toggleDescriptionExpand(): void
		{
			this.isDescriptionExpanded = !this.isDescriptionExpanded;
		},
		checkDescriptionOverflowed(): boolean
		{
			const descriptionContainer = this.$refs.descriptionContainer ?? null;
			if (descriptionContainer)
			{
				return descriptionContainer.scrollHeight > descriptionContainer.clientHeight;
			}

			return false;
		},
		hideDetailLoader(): void
		{
			this.$emit('hideDetailLoader');
			this.$nextTick(() => {
				this.isDescriptionOverflowed = this.checkDescriptionOverflowed();
			});
		},
		isTabDisabled(tabName: string): boolean
		{
			if (tabName === 'chatsTab' && this.tabArray.find((tab) => tab.name === tabName).count === 0)
			{
				const permissionChecker = PermissionChecker.getInstance();
				const canEditChats = this.isTeamEntity
					? (
						permissionChecker.hasPermission(PermissionActions.teamChatEdit, this.focusedNode)
						|| permissionChecker.hasPermission(PermissionActions.teamChannelEdit, this.focusedNode)
						|| permissionChecker.hasPermission(PermissionActions.teamCollabEdit, this.focusedNode)
					)
					: (
						permissionChecker.hasPermission(PermissionActions.departmentChatEdit, this.focusedNode)
						|| permissionChecker.hasPermission(PermissionActions.departmentChannelEdit, this.focusedNode)
						|| permissionChecker.hasPermission(PermissionActions.departmentCollabEdit, this.focusedNode)
					)
				;

				if (!canEditChats)
				{
					return true;
				}
			}

			return false;
		},
	},

	template: `
		<div class="hr-department-detail-content hr-department-detail-content__scope">
			<div
				ref="descriptionContainer"
				v-show="description"
				:class="[
					'hr-department-detail-content-description',
					{ '--expanded': isDescriptionExpanded },
					{ '--overflowed': isDescriptionOverflowed},
				]"
				v-on="isDescriptionOverflowed ? { click: toggleDescriptionExpand } : {}"
			>
				{{ description }}
			</div>
			<div class="hr-department-detail-content__tab-list">
				<button
					v-for="tab in tabArray"
					:key="tab.name"
					class="hr-department-detail-content__tab-item"
					:class="[{'--active-tab' : activeTab === tab.name, '--disabled-tab': isTabDisabled(tab.name)}]"
					@click="selectTab(tab.name)"
					:data-id="tab.id ? 'hr-department-detail-content__' + tab.id + '_button' : null"
				>
					{{ this.getTabLabel(tab.name) }}
					<span
						class="hr-department-detail-content__tab-count"
						:data-id="tab.id ? 'hr-department-detail-content__' + tab.id + '_counter' : null"
					>{{ tab.count }}
					</span>
				</button>
			</div>
			<UsersTab
				v-show="activeTab === 'usersTab'"
				@showDetailLoader="$emit('showDetailLoader')"
				@hideDetailLoader="hideDetailLoader"
			/>
			<ChatsTab
				v-show="activeTab === 'chatsTab'"
				:is="activeTabComponent"
				@showDetailLoader="$emit('showDetailLoader')"
				@hideDetailLoader="hideDetailLoader"
			/>
		</div>
	`,
};
