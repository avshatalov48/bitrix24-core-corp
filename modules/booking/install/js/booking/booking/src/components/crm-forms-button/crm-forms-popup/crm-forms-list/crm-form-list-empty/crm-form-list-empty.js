import { CrmFormItemSkeleton } from './crm-form-item-skeleton/crm-form-item-skeleton';
import './crm-form-list-empty.css';

// @vue/component
export const CrmFormListEmpty = {
	name: 'CrmFormListEmpty',
	components: {
		CrmFormItemSkeleton,
	},
	template: `
		<CrmFormItemSkeleton v-for="i in 4" :key="i"/>
		<div class="booking--booking--crm-forms-popup--list__placeholder-bg"></div>
		<div class="booking--booking--crm-forms-popup--list__placeholder">
			<span>{{ loc('BOOKING_OPEN_CRM_FORMS_POPUP_FORMS_LIST_PLACEHOLDER') }}</span>
		</div>
	`,
};
