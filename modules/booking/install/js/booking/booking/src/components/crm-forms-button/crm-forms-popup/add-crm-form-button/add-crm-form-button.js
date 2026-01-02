import { Button as UiButton, ButtonColor, ButtonSize, ButtonStyle } from 'booking.component.button';
import { Model } from 'booking.const';

// @vue/component
export const AddCrmFormButton = {
	name: 'AddCrmFormButton',
	components: {
		UiButton,
	},
	setup(): Object
	{
		return {
			ButtonColor,
			ButtonSize,
			ButtonStyle,
		};
	},
	computed: {
		createFormLink(): string
		{
			return this.$store.state[Model.FormsMenu].createFormLink;
		},
		addBtnLabel(): string
		{
			return this.loc('BOOKING_OPEN_CRM_FORMS_POPUP_ADD_FORM_BUTTON_LABEL').replace('[plus]', '+');
		},
	},
	template: `
		<a :href="createFormLink" target="_blank">
			<UiButton
				:text="addBtnLabel"
				:color="ButtonColor.LIGHT_BORDER"
				:size="ButtonSize.SMALL"
				:buttonClass="['--air', ButtonStyle.NO_CAPS]"
			/>
		</a>
	`,
};
