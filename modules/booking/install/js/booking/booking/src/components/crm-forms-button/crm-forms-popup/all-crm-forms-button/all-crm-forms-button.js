import { Button as UiButton, AirButtonStyle, ButtonColor, ButtonSize, ButtonStyle } from 'booking.component.button';

// @vue/component
export const AllCrmFormsButton = {
	name: 'AllCrmFormsButton',
	components: {
		UiButton,
	},
	setup(): Object
	{
		return {
			AirButtonStyle,
			ButtonColor,
			ButtonSize,
			ButtonStyle,
		};
	},
	template: `
		<a href="/crm/webform/" target="_blank">
			<UiButton
				:text="loc('BOOKING_OPEN_CRM_FORMS_POPUP_ALL_FORMS_BUTTON_LABEL')"
				:color="ButtonColor.LIGHT_BORDER"
				:size="ButtonSize.SMALL"
				:buttonClass="['--air', AirButtonStyle.OUTLINE_NO_ACCENT, ButtonStyle.NO_CAPS]"
			/>
		</a>
	`,
};
