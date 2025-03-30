import { UsersTab } from './users/tab';
import { ChatTab } from './chat/tab';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';

import '../style.css';

export const DepartmentContent = {
	name: 'departmentContent',

	components: { UsersTab, ChatTab },

	emits: ['showDetailLoader', 'hideDetailLoader', 'editEmployee'],

	data(): Object
	{
		return {
			activeTab: 'usersTab',
			tabs: [
				{ name: 'usersTab', component: 'usersTab', id: 'users-tab' },
				{ name: 'chatTab', component: 'ChatTab', id: 'chats-tab', soon: true },
			],
			isDescriptionOverflowed: false,
			isDescriptionExpanded: false,
		};
	},

	mounted(): void
	{
		this.isDescriptionOverflowed = this.checkDescriptionOverflowed();
	},

	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		selectTab(tabName): void
		{
			this.activeTab = tabName;
		},
		getTabLabel(name: string): string
		{
			if (name === 'usersTab')
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TITLE');
			}

			if (name === 'chatTab')
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_TITLE');
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
				return descriptionContainer.scrollWidth > descriptionContainer.clientWidth;
			}

			return false;
		},
		hideDetailLoader(): boolean
		{
			this.$emit('hideDetailLoader');
			this.$nextTick(() => {
				this.isDescriptionOverflowed = this.checkDescriptionOverflowed();
			});
		},
	},

	computed: {
		...mapState(useChartStore, ['focusedNode', 'departments']),
		activeTabComponent(): EmployeeTab | ChatTab | null
		{
			const activeTab = this.tabs.find((tab) => tab.name === this.activeTab);

			return activeTab ? activeTab.component : null;
		},
		count(): Number
		{
			return this.departments.get(this.focusedNode)?.userCount ?? 0;
		},
		tabArray(): Array
		{
			return this.tabs.map((tab) => {
				if (tab.name === 'usersTab')
				{
					return {
						...tab,
						count: this.count,
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
	},

	watch: {
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
					:class="[{'--active-tab' : activeTab === tab.name}, {'--soon' : tab.soon}]"
					:data-title="tab.soon ? loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_BADGE_SOON') : null"
					@click="selectTab(tab.name)"
					:data-id="tab.id ? 'hr-department-detail-content__' + tab.id + '_button' : null"
				>
					{{ this.getTabLabel(tab.name) }}
					<span
						v-if="!tab.soon"
						class="hr-department-detail-content__tab-count"
						:data-id="tab.id ? 'hr-department-detail-content__' + tab.id + '_counter' : null"
					>{{ tab.count }}
					</span>
				</button>
			</div>
			<component
				:is="activeTabComponent"
				@editDepartmentUsers="$emit('editEmployee')"
				@showDetailLoader="$emit('showDetailLoader')"
				@hideDetailLoader="hideDetailLoader"
			/>
		</div>
	`,
};
