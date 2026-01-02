import { BaseActionMenu, BaseActionMenuPropsMixin } from './base-action-menu';
import { UserActionMenuItem } from './items/user-action-menu-item';

export const UserListActionMenu = {
	name: 'UserListActionMenu',
	mixins: [BaseActionMenuPropsMixin],
	components: {
		BaseActionMenu,
		UserActionMenuItem,
	},

	methods: {
		openUserUrl(url): void
		{
			if (!url)
			{
				return;
			}

			BX.SidePanel.Instance.open(url, {
				cacheable: false,
			});
		},
	},

	template: `
		<BaseActionMenu 
			:id="id"
			className="hr-user-list-action-menu"
			:items="items" 
			:bindElement="bindElement"
			:width="260"
			:delimiter="false"
			:titleBar="titleBar"
			:containerDataTestId="containerDataTestId"
			:angleOffset="35"
			v-slot="{item}"
			@close="this.$emit('close')"
		>
			<UserActionMenuItem
				:id="item.id" 
				:name="item.name"
				:avatar="item.avatar"
				:workPosition="item.workPosition"
				:color="item.color"
				:dataTestId="item.dataTestId"
				@click="this.openUserUrl(item.url)"
			/>
		</BaseActionMenu>
	`,
};
