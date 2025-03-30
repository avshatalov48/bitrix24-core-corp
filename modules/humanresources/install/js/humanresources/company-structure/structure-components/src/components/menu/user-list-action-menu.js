import { BaseActionMenu, BaseActionMenuPropsMixin } from './base-action-menu';
import { UserActionMenuItem } from './items/user-action-menu-item';
import { SidePanel } from 'main.sidepanel';

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

			SidePanel.Instance.open(url, {
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
				@click="this.openUserUrl(item.url)"
			/>
		</BaseActionMenu>
	`,
};
