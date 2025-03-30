import { BaseActionMenu, BaseActionMenuPropsMixin } from './base-action-menu';
import { ActionMenuItem } from './items/action-menu-item';

export const ActionMenu = {
	name: 'ActionMenu',
	mixins: [BaseActionMenuPropsMixin],
	components: {
		BaseActionMenu,
		ActionMenuItem,
	},

	template: `
		<BaseActionMenu
			:id="id"
			:items="items"
			:bindElement="bindElement"
			:width="260"
			:delimiter="false"
			v-slot="{item}"
			@close="this.$emit('close')"
		>
			<ActionMenuItem
				:id="item.id"
				:title="item.title"
				:imageClass="item.imageClass"
				:color="item.color"
				@click="this.$emit('action', item.id)"
			/>
		</BaseActionMenu>
	`,
};
