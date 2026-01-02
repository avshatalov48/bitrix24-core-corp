import { RouteActionMenu } from 'humanresources.company-structure.structure-components';

const MenuOption = Object.freeze({
	withChildren: 'withChildren',
	withoutChildren: 'withoutChildren',
});

// @vue/component
export const ChildrenModeSelector = {
	name: 'childrenModeSelector',

	components: { RouteActionMenu },

	props:
	{
		isTeamEntity: {
			type: Boolean,
			required: true,
		},
		dataTestId: {
			type: String,
			required: true,
		},
		hasPermission: {
			type: Boolean,
			required: true,
		},
		text: {
			type: String,
			required: true,
		},
	},

	emits: ['saveChildrenMode'],

	data(): { menuVisible: boolean; withChildren: boolean }
	{
		return {
			menuVisible: false,
			withChildren: false,
		};
	},

	computed:
	{
		getControlButtonText(): string
		{
			return this.getValueText(this.withChildren);
		},
	},

	created(): void
	{
		this.menuItems = this.getMenuItems();
	},

	methods:
	{
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		showMenu(): void
		{
			if (this.hasPermission)
			{
				this.menuVisible = true;
			}
		},
		onActionMenuItemClick(actionId: number): void
		{
			if (this.hasPermission)
			{
				this.withChildren = actionId === MenuOption.withChildren;
				this.$emit('saveChildrenMode', this.withChildren);
			}
		},
		getMenuItems(): Array
		{
			return [
				{
					id: MenuOption.withoutChildren,
					title: this.getValueText(false),
					itemClass: 'hr-department-detail-content__children-mode-selector_menu-item',
					dataTestId: 'hr-chat-children-mode-selector_menu-item-without',
				},
				{
					id: MenuOption.withChildren,
					title: this.getValueText(true),
					itemClass: 'hr-department-detail-content__children-mode-selector_menu-item',
					dataTestId: 'hr-chat-children-mode-selector_menu-item-with',
				},
			];
		},
		getValueText(value: boolean): string
		{
			if (this.isTeamEntity)
			{
				return value
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_WITH_SUBTEAMS_LABEL')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_ONLY_TEAM_LABEL')
				;
			}

			return value
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_WITH_SUBDEPARTMENTS_LABEL')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_ONLY_DEPARTMENT_LABEL')
			;
		},
	},

	template: `
		<div class="hr-department-detail-content__change-save-mode-control-container">
			<span>{{ text }}</span>
			<a
				class="hr-department-detail-content__change-save-mode-control-button"
				:class="{ '--focused': menuVisible, '--disabled': !hasPermission }"
				:dataTestId="dataTestId"
				ref='saveChildrenModeButton'
				@click="showMenu"
			>
				{{ getControlButtonText }}
			</a>
		</div>
		<RouteActionMenu
			v-if="menuVisible"
			:id="'hr-department-detail-children-mode-selector-menu'"
			:items="menuItems"
			:delimiter="false"
			:width="302"
			:bindElement="$refs.saveChildrenModeButton"
			@action="onActionMenuItemClick"
			@close="menuVisible = false"
		/>
	`,
};
