import { AvatarRound } from 'ui.avatar';

import '../styles/user-item.css';

export const UserItem = {
	name: 'UserItem',

	props: {
		item: {
			type: Object,
			required: true,
		},
		mode: {
			type: String,
			required: true,
		},
	},

	mounted(): void
	{
		if (this.mode === 'direct')
		{
			this.getUserAvatarEntity().renderTo(this.$refs.avatarContainer);
		}
	},

	methods: {
		getUserAvatarEntity(): AvatarRound {
			return new AvatarRound({
				size: 36,
				userName: this.item.name,
				baseColor: '#FF7C78',
				userpicPath: this.item.avatarLink,
			});
		},
	},

	template: `
		<div 
			class="hr-hcmlink-item-user__container"
			:class="{'hr-hcmlink-item-user__container_person': mode === 'reverse'}"
			ref="container"
		>
			<div v-if="this.mode === 'direct'" class="hr-hcmlink-item-user__avatar" ref="avatarContainer"></div>
			<div class="hr-hcmlink-item-user_info">
				<div class="hr-hcmlink-item-user__info-name">{{ item.name }}</div>
				<div class="hr-hcmlink-item-user__info-position">{{ item.position }}</div>
			</div>
		</div>
	`,
};
