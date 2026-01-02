import { BaseActionMenu, BaseActionMenuPropsMixin } from './base-action-menu';
import { RouteActionMenuItem } from './items/route-action-menu-item';

export const RouteActionMenu = {
	name: 'RouteActionMenu',
	mixins: [BaseActionMenuPropsMixin],
	components: {
		BaseActionMenu,
		RouteActionMenuItem,
	},

	template: `
		<BaseActionMenu
			:id="id"
			:items="items"
			:bindElement="bindElement"
			:containerDataTestId="containerDataTestId"
			:width="260"
			v-slot="{item}"
			@close="this.$emit('close')"
		>
			<RouteActionMenuItem
				:id="item.id"
				:title="item.title"
				:description="item.description"
				:itemClass="item.itemClass"
				:imageClass="item.imageClass"
				:dataTestId="item.dataTestId"
				:bIcon="item.bIcon"
			/>
		</BaseActionMenu>
	`,
};
