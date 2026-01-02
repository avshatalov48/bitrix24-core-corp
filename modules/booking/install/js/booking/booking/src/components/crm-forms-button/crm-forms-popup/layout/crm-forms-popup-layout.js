import { HelpDeskLoc } from 'booking.component.help-desk-loc';
import { HelpDesk } from 'booking.const';
import './crm-forms-popup-layout.css';

// @vue/component
export const CrmFormsPopupLayout = {
	name: 'CrmFormsPopupLayout',
	components: {
		HelpDeskLoc,
	},
	setup(): Object
	{
		const helpDesk = HelpDesk.CrmFormsPopup;

		return {
			helpDesk,
		};
	},
	template: `
		<div class="booking--booking--crm-forms-popup__wrapper">
			<div class="booking--booking--crm-forms-popup__header">
				<span class="booking--booking--crm-forms-popup__header-title">
					{{ loc('BOOKING_OPEN_CRM_FORMS_POPUP_TITLE') }}
				</span>
				<div class="booking--booking--crm-forms-popup__header-description">
					<HelpDeskLoc
						:message="loc('BOOKING_OPEN_CRM_FORMS_POPUP_DESCRIPTION')"
						:code="helpDesk.code"
						:anchor="helpDesk.anchorCode"
					/>
				</div>
			</div>
			<div class="booking--booking--crm-forms-popup__toolbar">
				<slot name="toolbar"/>
			</div>
			<div class="booking--booking-crm-forms-popup__list-wrapper">
				<slot/>
			</div>
			<div class="booking--booking-crm-forms-popup__footer">
				<slot name="footer"/>
			</div>
		</div>
	`,
};
