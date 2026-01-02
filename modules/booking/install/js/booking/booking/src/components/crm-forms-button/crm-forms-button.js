import { ref } from 'ui.vue3';
import { Button as UiButton, AirButtonStyle, ButtonColor, ButtonSize, ButtonStyle } from 'booking.component.button';
import { CrmFormsPopup } from './crm-forms-popup/crm-forms-popup';

// @vue/component
export const CrmFormsButton = {
	name: 'CrmFormsButton',
	components: {
		CrmFormsPopup,
		UiButton,
	},
	props: {
		container: {
			type: HTMLElement,
			required: true,
		},
	},
	setup(): Object
	{
		const isPopupShown = ref(false);

		const openMenu = (): void => {
			isPopupShown.value = true;
		};

		return {
			isPopupShown,
			AirButtonStyle,
			ButtonColor,
			ButtonSize,
			ButtonStyle,
			openMenu,
		};
	},
	computed: {
		label(): string
		{
			return this.loc('BOOKING_OPEN_CRM_FORMS_BUTTON_LABEL');
		},
	},
	mounted()
	{
		this.container.replaceWith(this.$refs.button.$el);
	},
	template: `
		<UiButton
			ref="button"
			:buttonClass="['--air', ButtonStyle.NO_CAPS, AirButtonStyle.OUTLINE_NO_ACCENT]"
			:text="label"
			:color="ButtonColor.LIGHT_BORDER"
			:size="ButtonSize.SMALL"
			@click="openMenu"
		/>
		<CrmFormsPopup
			v-if="isPopupShown"
			v-model:visible="isPopupShown"
			:bindElement="$refs.button.$el"
		/>
	`,
};
