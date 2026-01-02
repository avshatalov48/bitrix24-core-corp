import { BIcon, Main } from 'ui.icon-set.api.vue';
import { Hint } from 'humanresources.company-structure.structure-components';
import { getColorCode, EntityTypes } from 'humanresources.company-structure.utils';
import { UserListActionMenu } from 'humanresources.company-structure.structure-components';
import { mapState } from 'ui.vue3.pinia';
import { events } from '../../../consts';
import { EventEmitter } from 'main.core.events';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import type { UserData } from 'humanresources.company-structure.utils';
import { Type } from 'main.core';

import './style.css';

// @vue/component
export const HeadList = {
	name: 'headList',

	components: {
		UserListActionMenu,
		BIcon,
	},

	directives: { hint: Hint },

	props: {
		entityId: {
			type: Number,
			required: false,
			default: 0,
		},
		items: {
			type: Array,
			required: false,
			default: () => [],
		},
		title: {
			type: String,
			required: false,
			default: '',
		},
		collapsed: {
			type: Boolean,
			required: false,
			default: false,
		},
		type: {
			type: String,
			required: false,
			default: 'head',
		},
		isTeamEntity: {
			type: Boolean,
			required: false,
			default: false,
		},
	},

	data(): Object
	{
		return {
			isCollapsed: false,
			isUpdating: true,
			headsVisible: false,
		};
	},

	computed:
	{
		defaultAvatar(): String
		{
			return '/bitrix/js/humanresources/company-structure/org-chart/src/images/default-user.svg';
		},
		dropdownItems(): Array<UserData>
		{
			return this.items.map((item: UserData): UserData => {
				const workPosition = this.getPositionText(item);

				return { ...item, workPosition };
			});
		},
		titleBar(): string
		{
			return this.type === this.userTypes.deputy
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_DEPUTY_TITLE')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_HEAD_TITLE')
			;
		},
		subordinatesCount(): number
		{
			return this.getSubtreeUserCount(this.entityId);
		},
		MainIcons: () => Main,
		...mapState(useChartStore, ['departments', 'multipleUsers']),
	},

	created(): void
	{
		this.userTypes = {
			head: 'head',
			deputy: 'deputy',
		};
	},

	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		handleUserClick(url: string): void
		{
			BX.SidePanel.Instance.open(url, {
				cacheable: false,
			});
		},
		closeHeadList(): void
		{
			if (this.headsVisible) // to prevent double unsubscription
			{
				this.headsVisible = false;
				EventEmitter.unsubscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.closeHeadList);
				EventEmitter.unsubscribe(events.HR_DRAG_ENTITY, this.closeHeadList);
			}
		},
		openHeadList(): void
		{
			if (!this.headsVisible) // to prevent double subscription
			{
				this.headsVisible = true;
				EventEmitter.subscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.closeHeadList);
				EventEmitter.subscribe(events.HR_DRAG_ENTITY, this.closeHeadList);
			}
		},
		getPositionText(item: UserData): string
		{
			return item.workPosition || this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_HEAD_POSITION');
		},
		getSubtreeUserCount(rootId: number): number
		{
			if (!rootId)
			{
				return 0;
			}

			let sum = 0;
			const checkedEntities = new Set();
			const countedEntities = new Set();
			const stack = [rootId];

			while (stack.length > 0)
			{
				const id = stack.pop();
				if (checkedEntities.has(id))
				{
					continue;
				}
				checkedEntities.add(id);

				const entity = this.departments.get(id);
				if (!entity)
				{
					continue;
				}

				if (entity.entityType === EntityTypes.team)
				{
					continue;
				}

				let count = Number(entity.userCount) || 0;

				// if we are at the root, we should not count heads that are already displayed
				if (id === rootId)
				{
					const headsAtRoot = this.items.length;
					count = Math.max(0, count - headsAtRoot);
				}

				if (count > 0)
				{
					sum += count;
					countedEntities.add(String(id));
				}

				if (entity.children?.length > 0)
				{
					for (const childId of entity.children)
					{
						if (!checkedEntities.has(childId))
						{
							stack.push(childId);
						}
					}
				}
			}

			let duplicatesToSubtract = 0;

			if (this.multipleUsers && countedEntities.size > 0)
			{
				const headUserIds = new Set(
					this.items.map((item) => String(item.id)),
				);

				// for each user that is in multiple departments
				// check how many of those departments are in the countedEntities set
				for (const [userId, entityIds] of Object.entries(this.multipleUsers))
				{
					if (!Type.isArray(entityIds) || entityIds.length === 0)
					{
						continue;
					}

					let cnt = 0;
					for (const entityId of entityIds)
					{
						if (countedEntities.has(String(entityId)))
						{
							cnt++;
						}
					}

					// if user is in more than one counted entity, we need to subtract the duplicates
					if (cnt > 1)
					{
						let duplicatesCount = cnt - 1;
						if (headUserIds.has(String(userId)))
						{
							duplicatesCount = Math.max(0, duplicatesCount - 1);
						}
						duplicatesToSubtract += duplicatesCount;
					}
				}
			}

			return Math.max(0, sum - duplicatesToSubtract);
		},
		getEmployeesCountIconColor(): string
		{
			return getColorCode('paletteGray70');
		},
		getEmployeesCountIconText(): string
		{
			return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_ALL_EMPLOYEES_COUNT_HINT_TEXT');
		},
	},

	template: `
		<div v-if="items.length">
			<p v-if="title" class="humanresources-tree__node_employees-title">
				{{ title }}
			</p>
			<div
				class="humanresources-tree__node_head"
				:class="{ '--collapsed': collapsed }"
				v-for="(item, index) in items.slice(0, 2)"
			>
				<img
					:src="item.avatar ? encodeURI(item.avatar) : defaultAvatar"
					class="humanresources-tree__node_avatar --head"
					:class="{ '--collapsed': collapsed }"
					@click.stop="handleUserClick(item.url)"
				/>
				<div class="humanresources-tree__node_head-text">
					<div class="humanresources-tree__node_head-name-container">
					<span
						:bx-tooltip-user-id="item.id"
						bx-tooltip-context="b24"
						class="humanresources-tree__node_head-name"
						@click.stop="handleUserClick(item.url)"
					>
						{{ item.name }}
					</span>
						<div
							v-if="index === 0 && type === userTypes.head && !isTeamEntity"
							v-hint="getEmployeesCountIconText()"
							class="humanresources-tree__node_head-subordinates"
							:class="{ '--active': headsVisible }"
							:data-test-id="'hr-company-structure_org-chart-tree__node-subordinates'"
						>
							<BIcon
								:name="MainIcons.PERSONS_2"
								:size="14"
								class="hr-company-structure_org-chart-tree__node-subordinates-icon"
								:color="getEmployeesCountIconColor()"
							/>
							<span class="hr-company-structure_org-chart-tree__node-subordinates-text">{{ String(subordinatesCount) }}</span>
						</div>
					</div>
					<span v-if="!collapsed" class="humanresources-tree__node_head-position">
						{{ getPositionText(item) }}
					</span>
				</div>
				<span
					v-if="index === 1 && items.length > 2"
					class="humanresources-tree__node_head-rest"
					:class="{ '--active': headsVisible }"
					:data-test-id="'hr-company-structure_org-chart-tree__node-' + type + '-rest'"
					ref="showMoreHeadList"
					@click.stop="openHeadList"
				>
					{{ '+' + String(items.length - 2) }}
				</span>
			</div>
		</div>
		<UserListActionMenu
			v-if="headsVisible"
			:id="type === userTypes.head ? 'head-list-popup-head' : 'head-list-popup-deputy'"
			:items="dropdownItems"
			:width="228"
			:bindElement="$refs.showMoreHeadList[0]"
			@close="closeHeadList"
			:titleBar="titleBar"
		/>
	`,
};
