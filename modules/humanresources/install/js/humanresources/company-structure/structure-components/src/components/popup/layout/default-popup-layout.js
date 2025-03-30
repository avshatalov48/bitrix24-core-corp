import './style.css';

export const DefaultPopupLayout = {
	name: 'DefaultPopupLayout',

	template: `
		<div
			v-if="$slots.content"
			class="hr-default-popup-layout__content"
		>
			<slot name="content"></slot>
		</div>
	`,
};
