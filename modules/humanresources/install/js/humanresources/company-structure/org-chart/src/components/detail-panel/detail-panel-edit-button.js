import { AnalyticsSourceType } from 'humanresources.company-structure.api';
import { BIcon, Set } from 'ui.icon-set.api.vue';
import { EntityTypes } from 'humanresources.company-structure.utils';
import { RouteActionMenu } from 'humanresources.company-structure.structure-components';
import { EntityActionMenu } from '../menu/menu/entity-action-menu';

import '../../style.css';
import 'ui.icon-set.main';
import 'ui.icon-set.crm';

/**
 * Component for displaying icon that shows EntityActionMenu
 */
// @vue/component
export const DetailPanelEditButton = {
	name: 'detailPanelEditButton',

	components: {
		BIcon,
		RouteActionMenu,
	},

	props: {
		entityId: {
			type: Number,
			required: true,
		},
		entityType: {
			type: String,
			default: EntityTypes.department,
		},
	},

	data(): Object
	{
		return {
			menuVisible: false,
		};
	},

	computed: {
		set(): Set
		{
			return Set;
		},
		menu(): EntityActionMenu
		{
			return new EntityActionMenu(this.entityId, this.entityType, AnalyticsSourceType.NODE_DETAIL);
		},
	},

	methods: {
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		onActionMenuItemClick(actionId: string): void
		{
			this.menu.onActionMenuItemClick(actionId);
		},
	},

	template: `
		<div
			v-if="menu.items.length"
			class="humanresources-detail-panel__edit-button"
			:class="{ '--focused': menuVisible }"
			:ref="'detailPanelEditButton'"
			data-id="hr-department-detail-panel__edit-menu-button"
			@click.stop="menuVisible = true"
		>
			<BIcon
				class="humanresources-detail-panel__edit-button-icon"
				:name="set.MORE"
				:size="20"
			/>
		</div>
		<RouteActionMenu
			v-if="menuVisible"
			id="department-detail-content-edit-menu"
			:items="menu.items"
			:width="302"
			:bindElement="$refs.detailPanelEditButton"
			@action="onActionMenuItemClick"
			@close="menuVisible = false"
		/>
	`,
};
