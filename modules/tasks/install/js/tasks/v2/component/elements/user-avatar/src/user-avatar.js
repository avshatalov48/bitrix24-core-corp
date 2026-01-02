import { AvatarBase, AvatarRoundGuest } from 'ui.avatar';
import { UserAvatarSize, UserAvatarSizeMap } from './user-avatar-size';
import './user-avatar.css';

// @vue/component
export const UserAvatar = {
	name: 'UiUserAvatar',
	props: {
		src: {
			type: String,
			default: '',
		},
		type: {
			type: String,
			default: 'employee',
		},
		size: {
			type: String,
			default: UserAvatarSize.S,
		},
	},
	watch: {
		src(): void
		{
			this.render();
		},
	},
	mounted(): void
	{
		this.render();
	},
	methods: {
		render(): void
		{
			this.avatar?.getContainer()?.remove();
			const AvatarClass = this.type === 'collaber' ? AvatarRoundGuest : AvatarBase;
			this.avatar = new (AvatarClass)({
				size: UserAvatarSizeMap[this.size],
				picPath: this.src,
				baseColor: '#858D95',
			});
			this.avatar.renderTo(this.$refs.container);
		},
	},
	template: `
		<div
			ref="container"
			class="b24-user-avatar"
		></div>
	`,
};
