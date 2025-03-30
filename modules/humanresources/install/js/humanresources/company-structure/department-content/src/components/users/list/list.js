import { RouteActionMenu } from 'humanresources.company-structure.structure-components';
import { UserListItem } from './item/item';

import 'ui.icon-set.main';
import './styles/list.css';

export const UserList = {
	name: 'userList',

	props: {
		items: {
			type: Array,
			required: true,
		},
		selectedUserId: {
			type: Number,
			required: false,
			default: null,
		},
	},

	components: { RouteActionMenu, UserListItem },

	data(): Object
	{
		return {
			draggedEmployee: null,
			draggedIndex: null,
		};
	},

	methods: {
		onDragStart(item, targetElement)
		{
			this.$emit('dragstart', item, targetElement);
		},
		onDrop(item, targetIndex)
		{
			this.$emit('drop', item, targetIndex);
		},
		updateEmployeeRole(employee, newRole)
		{
			employee.role = newRole;
		},
	},

	template: `
		<div 
			class="hr-department-detail-content__user-list-container"
		>
			<UserListItem
				v-for="user in items"
				:user="user"
				:selectedUserId="selectedUserId"
			/>
		</div>
	`,
};
