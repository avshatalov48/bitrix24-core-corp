import { AvatarRound } from 'ui.avatar';

// @vue/component
export const Avatar = {
	name: 'UiAvatar',
	props: {
		size: {
			type: Number,
			default: 36,
		},
		userName: {
			type: String,
			default: '',
		},
		userpicPath: {
			type: String,
			default: null,
		},
	},
	created(): void
	{
		this.createAvatar();
	},
	mounted(): void
	{
		this.renderAvatar();
	},
	updated()
	{
		this.renderAvatar();
	},
	methods: {
		createAvatar(): void
		{
			this.avatar = new AvatarRound({
				size: this.size,
				userName: this.userName,
				userpicPath: this.userpicPath,
			});
		},
		renderAvatar(): void
		{
			if (!this.avatar)
			{
				this.createAvatar();
			}

			this.avatar.renderTo(this.$refs.avatar);
		},
	},
	template: `
		<div ref="avatar"></div>
	`,
};
