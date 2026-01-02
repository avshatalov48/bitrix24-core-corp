import './crm-forms-toolbar.css';

// @vue/component
export const CrmFormsToolbar = {
	name: 'CrmFormsToolbar',
	template: `
		<div class="booking--booking--crm-forms-popup-sort">
			<span>{{ loc('BOOKING_OPEN_CRM_FORMS_POPUP_SORT_LATEST_CREATED_LABEL') }}</span>
			<div class="ui-icon-set --o-change-order-2 booking--booking--crm-forms-popup--sort-icon"></div>
		</div>
	`,
};
