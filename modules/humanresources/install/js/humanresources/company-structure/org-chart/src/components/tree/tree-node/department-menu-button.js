import { AnalyticsSourceType } from 'humanresources.company-structure.api';
import { EventEmitter } from 'main.core.events';
import { RouteActionMenu } from 'humanresources.company-structure.structure-components';
import { EntityTypes } from 'humanresources.company-structure.utils';
import 'ui.icon-set.main';
import { events } from '../../../consts';
import { EntityActionMenu } from '../../menu/menu/entity-action-menu';

// @vue/component
export const DepartmentMenuButton = {
	name: 'DepartmentMenuButton',

	components: {
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
		menu(): EntityActionMenu
		{
			return new EntityActionMenu(this.entityId, this.entityType, AnalyticsSourceType.CARD);
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
		closeMenu(): void
		{
			if (this.menuVisible)
			{
				this.menuVisible = false;
				EventEmitter.unsubscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.closeMenu);
				EventEmitter.unsubscribe(events.HR_DRAG_ENTITY, this.closeMenu);
			}
		},
		openMenu(): void
		{
			if (!this.menuVisible)
			{
				this.menuVisible = true;
				EventEmitter.subscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.closeMenu);
				EventEmitter.subscribe(events.HR_DRAG_ENTITY, this.closeMenu);
			}
		},
	},

	template: `
		<div
			v-if="menu.items.length"
			class="ui-icon-set --more humanresources-tree__node_department-menu-button"
			:class="{ '--focused': this.menuVisible }"
			ref="departmentMenuButton"
			data-test-id="tree-node-more-button"
			@click.stop="openMenu"
		>
		</div>

		<RouteActionMenu
			v-if="menuVisible"
			:id="'tree-node-department-menu-' + entityId"
			:width="302"
			:items="menu.items"
			:bindElement="this.$refs.departmentMenuButton"
			@action="onActionMenuItemClick"
			@close="closeMenu"
		/>
	`,
};
