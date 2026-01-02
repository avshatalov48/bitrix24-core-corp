import './style.css';

// @vue/component
export const Notice = {
	// eslint-disable-next-line vue/multi-word-component-names
	name: 'Notice',

	template: `
		<div class="sign-b2e-vue-util-notice">
			<div class="sign-b2e-vue-util-notice__content">
				<slot></slot>
			</div>
		</div>
	`,
};
