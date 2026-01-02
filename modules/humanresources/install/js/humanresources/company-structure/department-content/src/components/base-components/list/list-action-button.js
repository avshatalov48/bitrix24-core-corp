import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';
import { RouteActionMenu } from 'humanresources.company-structure.structure-components';
import './styles/list-action-button.css';

export const ListActionButton = {
	name: 'userListActionButton',
	emits: ['tabListAction', 'close'],

	props:
		{
			id: {
				type: String,
				required: true,
			},
			menuItems: {
				type: Array,
				required: true,
			},
			dataTestIds: {
				type: Object,
				required: false,
				default: {},
			},
		},

	components:
	{
		RouteActionMenu,
	},

	computed:
	{
		...mapState(useChartStore, ['focusedNode']),
	},

	data(): Object
	{
		return {
			menuVisible: false,
		};
	},

	methods: {
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		onActionMenuItemClick(itemId: string): void
		{
			this.$emit('tabListAction', itemId);
		},
	},

	template: `
		<button
			v-if="menuItems.length"
			class="hr-department-detail-content__tab-list_header-button"
			:class="{ '--focused': menuVisible }"
			:ref="id + '-route-action-menu'"
			@click.stop="menuVisible = true"
			:data-test-id="dataTestIds.buttonDataTestId"
		>
			{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_LIST_ACTION_BUTTON_TITLE') }}
		</button>
		<RouteActionMenu
			v-if="menuVisible"
			:id="id + '-route-action-menu'"
			:items="menuItems"
			:width="302"
			:bindElement="$refs[id + '-route-action-menu']"
			:containerDataTestId="dataTestIds.containerDataTestId"
			@action="onActionMenuItemClick"
			@close="menuVisible = false"
		/>
	`,
};
