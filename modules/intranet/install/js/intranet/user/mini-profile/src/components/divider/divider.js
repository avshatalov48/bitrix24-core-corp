import './style.css';

// @vue/component
export const Divider = {
	name: 'UserMiniProfileDivider',
	props: {
		isVertical: {
			type: Boolean,
			default: false,
		},
	},
	template: `
		<div 
			class="intranet-user-mini-profile__divider"
			:class="isVertical ? '--vertical' : '--horizontal'"
		>
			<div class="intranet-user-mini-profile__divider-inner"></div>
		</div>
	`,
};
