import { TagSelectorWrapperComponent } from '../common/tag-selector-wrapper-component';

export const UserSelector = {
	components: {
		TagSelectorWrapperComponent,
	},

	props: {
		userIds: {
			type: Array,
			required: true,
		},
		readOnly: {
			type: Boolean,
			default: false,
		},
	},

	data(): Object
	{
		return {
			preselectedItems: this.userIds.map((userId) => {
				return ['user', userId];
			}),
		};
	},

	created()
	{
		this.entities = [
			{
				id: 'user',
				options: {
					inviteEmployeeLink: false,
					emailUsers: false,
					inviteGuestLink: false,
					intranetUsersOnly: true,
				},
			},
			{
				id: 'structure-node',
				options: {
					selectMode: 'usersOnly',
				},
			},
		];
	},

	// language=Vue
	template: `
		<TagSelectorWrapperComponent 
			:entities="entities"
			:preselected-items="preselectedItems"
			:show-avatars="true"
			:multiple="true"
			:read-only="readOnly"
		/>
	`,
};
