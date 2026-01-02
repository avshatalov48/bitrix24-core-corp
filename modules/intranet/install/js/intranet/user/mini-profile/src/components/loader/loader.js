import './style.css';

// @vue/component
export const UserMiniProfileLoader = {
	name: 'UserMiniProfileLoader',
	props: {
		isShort: {
			type: Boolean,
			default: false,
		},
	},
	template: `
		<div class="intranet-user-mini-profile-loader" :class="{ '--short': isShort }"></div>
	`,
};
