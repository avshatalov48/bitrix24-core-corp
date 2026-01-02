import { useChartStore } from 'humanresources.company-structure.chart-store';
import { RouteActionMenu } from 'humanresources.company-structure.structure-components';
import { mapState } from 'ui.vue3.pinia';
import { ChatsMenuLinkChannel, ChatsMenuLinkChat, ChatsMenuLinkCollab } from './consts';
import 'ui.icon-set.main';
import './styles/empty-tab-add-buttons.css';

// @vue/component
export const EmptyTabAddButtons = {
	name: 'emptyStateButtons',

	components: { RouteActionMenu },

	props: {
		canEditChat: {
			type: Boolean,
			required: true,
		},
		canEditChannel: {
			type: Boolean,
			required: true,
		},
		canEditCollab: {
			type: Boolean,
			required: true,
		},
		isTeamEntity: {
			type: Boolean,
			required: true,
		},
	},

	emits: ['emptyStateAddAction'],

	data(): Object
	{
		return {
			menuVisible: false,
		};
	},

	computed:
	{
		menu(): Object[]
		{
			const menu = [];

			if (this.canEditCollab)
			{
				menu.push(ChatsMenuLinkCollab);
			}

			if (this.canEditChannel)
			{
				menu.push(ChatsMenuLinkChannel);
			}

			if (this.canEditChat)
			{
				menu.push(ChatsMenuLinkChat);
			}

			return menu;
		},
		...mapState(useChartStore, ['focusedNode']),
	},

	methods:
	{
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		onClick(): void
		{
			this.menuVisible = true;
		},
		onActionMenuItemClick(actionId: string): void
		{
			this.$emit('emptyStateAddAction', actionId);
		},
	},

	template: `
		<div class="hr-department-detail-content__users-empty-tab-add_buttons-container">
			<button
				class="hr-add-communications-empty-tab-entity-btn ui-btn ui-btn ui-btn-sm ui-btn-primary ui-btn-round"
				ref="actionMenuButton"
				@click.stop="onClick"
				data-id="hr-department-detail-content__user-empty-tab_add-user-button"
			>
				<span class="hr-add-communications-empty-tab-entity-btn-text">
					{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_ADD_BUTTON')}}
				</span>
			</button>
			<RouteActionMenu
				v-if="menuVisible"
				:id="'empty-state-department-detail-add-communications-menu-' + focusedNode"
				:items="menu"
				:delimiter="false"
				:width="302"
				:bindElement="$refs.actionMenuButton"
				:containerDataTestId="'empty-state-department-detail-add-communications-menu'"
				@action="onActionMenuItemClick"
				@close="menuVisible = false"
			/>
		</div>
	`,
};
