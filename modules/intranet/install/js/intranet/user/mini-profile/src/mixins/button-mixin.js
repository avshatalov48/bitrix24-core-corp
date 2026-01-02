import { AirButtonStyle, Button, ButtonIcon, ButtonSize } from 'ui.vue3.components.button';
import { BitrixVueComponentProps } from 'ui.vue3';

export const ButtonMixin: BitrixVueComponentProps = {
	components: {
		Button,
	},
	computed: {
		buttonSize: () => ButtonSize,
		buttonStyle: () => AirButtonStyle,
		buttonIcon: () => ButtonIcon,
	},
};
