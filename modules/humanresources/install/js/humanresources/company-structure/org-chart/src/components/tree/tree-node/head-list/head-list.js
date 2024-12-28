import { SidePanel } from 'main.sidepanel';
import './style.css';

export const HeadList = {
	name: 'headList',

	props: {
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
	},

	data(): Object
	{
		return {
			isCollapsed: false,
			isUpdating: true,
		};
	},

	computed:
	{
		defaultAvatar(): String
		{
			return '/bitrix/js/humanresources/company-structure/org-chart/src/images/default-user.svg';
		},
	},

	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		handleUserClick(url: string): void
		{
			SidePanel.Instance.open(url, {
				cacheable: false,
			});
		},
	},

	template: `
		<div v-if="items.length" class="humanresources-tree__node_employees-container">
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
					<span
						:bx-tooltip-user-id="item.id"
						class="humanresources-tree__node_head-name"
						@click.stop="handleUserClick(item.url)"
					>
						{{ item.name }}
					</span>
					<span v-if="!collapsed" class="humanresources-tree__node_head-position">
						{{ item.workPosition || loc('HUMANRESOURCES_COMPANY_STRUCTURE_HEAD_POSITION') }}
					</span>
				</div>
				<span
					v-if="index === 1 && items.length > 2"
					class="humanresources-tree__node_head-rest"
				>
					{{ '+' + String(items.length - 2) }}
				</span>
			</div>
		</div>
	`,
};
